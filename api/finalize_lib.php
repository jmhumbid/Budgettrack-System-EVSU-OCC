<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    // Get parameters
    $libId = $_POST['lib_id'] ?? null;
    $checkOnly = isset($_POST['check_only']) && $_POST['check_only'] == '1';
    
    if (!$libId) {
        echo json_encode(['success' => false, 'message' => 'LIB ID required']);
        exit;
    }
    
    // Check if LIB exists and is a draft
    $stmt = $db->prepare("SELECT l.*, d.dept_name FROM line_item_budgets l JOIN departments d ON l.department_id = d.id WHERE l.id = ?");
    $stmt->execute([$libId]);
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        echo json_encode(['success' => false, 'message' => 'LIB not found']);
        exit;
    }
    
    if ($lib['status'] !== 'draft') {
        echo json_encode(['success' => false, 'message' => 'LIB is already finalized']);
        exit;
    }
    
    // Verify user has access to this LIB's department
    $userDeptId = $_SESSION['department_id'] ?? null;
    if ($userDeptId != $lib['department_id']) {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // NEW VALIDATION: Check if there is at least one finalized PPMP in the same fiscal year
    // This is required before allowing LIB finalization
    // Handle both fiscal year formats: "2026" and "FY 2026"
    $libFiscalYear = $lib['fiscal_year'];
    
    // Extract just the year number (e.g., "FY 2026" -> "2026", "2026" -> "2026")
    preg_match('/\d{4}/', $libFiscalYear, $matches);
    $yearNumber = $matches[0] ?? $libFiscalYear;
    
    $ppmpCheckStmt = $db->prepare("
        SELECT COUNT(*) as finalized_count, 
               GROUP_CONCAT(ppmp_number SEPARATOR ', ') as ppmp_numbers
        FROM ppmp
        WHERE department_id = ?
        AND (fiscal_year = ? OR fiscal_year = ? OR fiscal_year LIKE ?)
        AND is_final = 1
        AND status = 'approved'
    ");
    
    // Check for multiple formats: "2026", "FY 2026", "%2026%"
    $ppmpCheckStmt->execute([
        $lib['department_id'],
        $yearNumber,
        'FY ' . $yearNumber,
        '%' . $yearNumber . '%'
    ]);
    
    $ppmpCheck = $ppmpCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($ppmpCheck['finalized_count'] == 0) {
        // No finalized PPMP found for this fiscal year
        echo json_encode([
            'success' => false,
            'message' => "Cannot finalize LIB for {$lib['fiscal_year']}: No finalized PPMP found for this fiscal year.\n\nPlease finalize at least one PPMP for {$lib['fiscal_year']} before finalizing the LIB."
        ]);
        exit;
    }
    
    // If check_only mode, return success without finalizing
    if ($checkOnly) {
        echo json_encode([
            'success' => true,
            'message' => 'PPMP validation passed',
            'finalized_ppmps' => $ppmpCheck['ppmp_numbers']
        ]);
        exit;
    }
    
    // Get LIB items
    $stmt = $db->prepare("SELECT * FROM line_item_budget_items WHERE lib_id = ? ORDER BY sort_order, id");
    $stmt->execute([$libId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Update status to approved (final)
        $stmt = $db->prepare("UPDATE line_item_budgets SET status = 'approved', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$libId]);
        
        // Sync to Utilization (Budget Office)
        // Delete existing auto-filled entries for this department/fiscal year
        $fiscalYear = $lib['fiscal_year'];
        $departmentId = $lib['department_id'];
        
        // Extract year from fiscal_year (e.g., "FY 2026" -> 2026)
        preg_match('/\d{4}/', $fiscalYear, $matches);
        $year = $matches[0] ?? date('Y');
        
        $deleteStmt = $db->prepare("DELETE FROM budget_utilization_entries WHERE department_id = ? AND fiscal_year = ? AND is_auto_filled = 1");
        $deleteStmt->execute([$departmentId, $year]);
        
        // Get next entry ID
        $maxStmt = $db->prepare("SELECT COALESCE(MAX(deducted_from_entry_id), 0) as max_val FROM budget_utilization_entries WHERE fiscal_year = ?");
        $maxStmt->execute([$year]);
        $maxResult = $maxStmt->fetch(PDO::FETCH_ASSOC);
        $nextDeductedFromEntryId = max(1, (int)$maxResult['max_val'] + 1);
        
        // Insert utilization entries
        $utilizationStmt = $db->prepare("
            INSERT INTO budget_utilization_entries 
            (department_id, expense_category, account_code, allocated_budget, deductions, total_balance, 
             fiscal_year, created_by, deducted_from_entry_id, is_auto_filled, lib_id)
            VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, 1, ?)
        ");
        
        foreach ($items as $item) {
            $particular = $item['particulars'];
            $accountCode = $item['account_code'];
            $amount = (float)$item['amount'];
            
            // Skip empty entries
            if (empty($particular)) {
                continue;
            }
            
            $utilizationStmt->execute([
                $departmentId,
                $particular,
                $accountCode,
                $amount,
                $amount,
                $year,
                $userId,
                $nextDeductedFromEntryId,
                $libId
            ]);
            
            $nextDeductedFromEntryId++;
        }
        
        $db->commit();
        
        // Send notification to Budget Office users
        try {
            $notification = new Notification();
            
            // Get department name
            $deptName = $lib['dept_name'] ?? 'Unknown Department';
            
            // Get user name
            $userStmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
            $userStmt->execute([$userId]);
            $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
            $userName = $userRow ? $userRow['full_name'] : 'Unknown User';
            
            // Create notification for budget office users
            $title = "LIB Finalized - {$deptName}";
            $message = "{$userName} from {$deptName} has finalized their Line-Item Budget (LIB) for {$fiscalYear}. The budget is now available for utilization tracking.";
            
            // Notify all budget role users
            $notification->notifyUsersByRoles(['budget'], $title, $message, 'success');
            
        } catch (Exception $e) {
            // Log notification error but don't fail the finalization
            error_log("Error sending LIB finalization notification: " . $e->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'LIB finalized and synced to utilization successfully'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error in finalize_lib.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
