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
    $departmentId = $_SESSION['department_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? '';
    
    // For budget role with no session department, look up from users table,
    // then fall back to the Fiduciary (Budget Office) department
    if (!$departmentId && $userRole === 'budget') {
        $stmt = $db->prepare("SELECT department_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['department_id']) {
            $departmentId = $row['department_id'];
        } else {
            $stmt = $db->prepare("SELECT u.department_id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'budget' AND u.department_id IS NOT NULL LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $departmentId = $row['department_id'];
        }
    }
    
    $ppmpId = $_POST['ppmpId'] ?? 0;
    $fiscalYear = $_POST['fiscalYear'] ?? '';
    $ppmpType = $_POST['ppmpType'] ?? 'ppmp'; // 'ppmp' or 'supplemental'
    $isIndicative = isset($_POST['isIndicative']) && $_POST['isIndicative'] == '1';
    $isFinal = isset($_POST['isFinal']) && $_POST['isFinal'] == '1';
    
    $generalDescriptions = $_POST['general_description'] ?? [];
    $projectTypes = $_POST['project_type'] ?? [];
    $quantities = $_POST['quantity'] ?? [];
    $units = $_POST['unit'] ?? [];
    $recommendedModes = $_POST['recommended_mode'] ?? [];
    $preProcurements = $_POST['pre_procurement'] ?? [];
    $startProcurements = $_POST['start_procurement'] ?? [];
    $endAdsPostings = $_POST['end_ads_posting'] ?? [];
    $expectedDeliveries = $_POST['expected_delivery'] ?? [];
    $sourcesOfFunds = $_POST['source_of_funds'] ?? [];
    $estimatedBudgets = $_POST['estimated_budget'] ?? [];
    $allocatedSupportings = $_POST['allocated_supporting'] ?? [];
    $remarks = $_POST['remarks'] ?? [];
    
    // LIB mapping fields
    $libCategories = $_POST['lib_category'] ?? [];
    $libParticulars = $_POST['lib_particulars'] ?? [];
    $libAccountCodes = $_POST['lib_account_code'] ?? [];
    
    if (!$ppmpId || empty($fiscalYear) || empty($generalDescriptions)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Verify ownership and draft status
    $sql = "SELECT * FROM ppmp WHERE id = ? AND department_id = ? AND status = 'draft'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$ppmpId, $departmentId]);
    $ppmp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ppmp) {
        echo json_encode(['success' => false, 'message' => 'PPMP not found or cannot be edited (only draft PPMPs can be edited)']);
        exit;
    }
    
    // Keep the existing PPMP number (don't regenerate on update)
    $ppmpNumber = $ppmp['ppmp_number'];
    
    $db->beginTransaction();
    
    // Determine status based on Final checkbox
    $status = $isFinal ? 'approved' : 'draft';
    
    // Update PPMP record
    $sql = "UPDATE ppmp SET fiscal_year = ?, ppmp_number = ?, ppmp_type = ?, is_indicative = ?, is_final = ?, status = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fiscalYear, $ppmpNumber, $ppmpType, $isIndicative ? 1 : 0, $isFinal ? 1 : 0, $status, $ppmpId]);
    
    // Delete existing items
    $sql = "DELETE FROM ppmp_items WHERE ppmp_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$ppmpId]);
    
    // Before re-syncing, we need to clean up old LIB items from this PPMP
    // Find the LIB for this department and fiscal year
    $libQuery = "SELECT id FROM line_item_budgets 
                 WHERE department_id = ? AND fiscal_year = ? 
                 ORDER BY created_at DESC LIMIT 1";
    $libStmt = $db->prepare($libQuery);
    $libStmt->execute([$departmentId, $fiscalYear]);
    $existingLib = $libStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingLib) {
        // Delete old LIB items that reference this PPMP
        $ppmpPattern = "(PPMP #{$ppmpNumber} - Item #%";
        $deleteLibStmt = $db->prepare("
            DELETE FROM line_item_budget_items 
            WHERE lib_id = ? AND particulars LIKE ?
        ");
        $deleteLibStmt->execute([$existingLib['id'], $ppmpPattern]);
    }
    
    // Insert new items
    $sql = "INSERT INTO ppmp_items (ppmp_id, general_description, project_type, quantity, unit, recommended_mode, 
            pre_procurement_conference, start_procurement, end_ads_posting, expected_delivery, source_of_funds, 
            estimated_budget, allocated_supporting_funds, remarks, lib_category, lib_particulars, lib_account_code, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    foreach ($generalDescriptions as $index => $description) {
        $libCategory = !empty($libCategories[$index]) ? $libCategories[$index] : null;
        $libParticular = !empty($libParticulars[$index]) ? $libParticulars[$index] : null;
        $libAccountCode = !empty($libAccountCodes[$index]) ? $libAccountCodes[$index] : null;
        
        $stmt->execute([
            $ppmpId,
            $description,
            $projectTypes[$index] ?? '',
            $quantities[$index] ?? 0,
            $units[$index] ?? '',
            $recommendedModes[$index] ?? '',
            $preProcurements[$index] ?? 'N',
            !empty($startProcurements[$index]) ? $startProcurements[$index] : null,
            !empty($endAdsPostings[$index]) ? $endAdsPostings[$index] : null,
            !empty($expectedDeliveries[$index]) ? $expectedDeliveries[$index] : null,
            $sourcesOfFunds[$index] ?? '',
            $estimatedBudgets[$index] ?? 0,
            $allocatedSupportings[$index] ?? 0,
            $remarks[$index] ?? '',
            $libCategory,
            $libParticular,
            $libAccountCode,
            $index
        ]);
    }
    
    $db->commit();
    
    // Sync to LIB if PPMP is saved (draft or final) and has LIB mappings
    $hasLibMappings = false;
    foreach ($libCategories as $index => $category) {
        if (!empty($category) && !empty($libParticulars[$index]) && !empty($libAccountCodes[$index])) {
            $hasLibMappings = true;
            break;
        }
    }
    
    if ($hasLibMappings) {
        try {
            // Use direct function call instead of HTTP request to avoid timeouts
            require_once __DIR__ . '/sync_ppmp_to_lib_helper.php';
            $syncResult = syncPPMPToLIB($ppmpId, $userId);
            
            if (!$syncResult['success']) {
                error_log("Warning: PPMP updated but LIB sync failed: " . ($syncResult['message'] ?? 'Unknown error'));
            }
        } catch (Exception $syncError) {
            error_log("Warning: PPMP updated but LIB sync failed: " . $syncError->getMessage());
        }
    }
    
    // Send notification to Budget Office if PPMP is marked as FINAL and notification hasn't been sent yet
    if ($isFinal && !$ppmp['notification_sent']) {
        try {
            // Get department name
            $deptQuery = "SELECT dept_name FROM departments WHERE id = ?";
            $deptStmt = $db->prepare($deptQuery);
            $deptStmt->execute([$departmentId]);
            $deptName = $deptStmt->fetchColumn() ?: 'Unknown Department';
            
            // Send notification to budget/admin users
            $notification = new Notification();
            $notificationType = $ppmpType === 'supplemental' ? 'Supplemental PPMP' : 'PPMP';
            $notification->notifyBudgetAdmins($notificationType, $userId, $deptName, true);
            
            // Mark notification as sent
            $updateNotifQuery = "UPDATE ppmp SET notification_sent = 1 WHERE id = ?";
            $updateNotifStmt = $db->prepare($updateNotifQuery);
            $updateNotifStmt->execute([$ppmpId]);
        } catch (Exception $notifError) {
            // Log error but don't fail the PPMP update
            error_log("Error sending PPMP notification: " . $notifError->getMessage());
        }
    }
    
    // Get the LIB ID if sync was successful
    $libIdForRefresh = null;
    if ($hasLibMappings && isset($syncResult) && $syncResult['success'] && isset($syncResult['lib_id'])) {
        $libIdForRefresh = $syncResult['lib_id'];
    }
    
    echo json_encode([
        'success' => true,
        'message' => ($ppmpType === 'supplemental' ? 'Supplemental PPMP' : 'PPMP') . ' updated successfully' . ($libIdForRefresh ? '. Items synced to LIB.' : ''),
        'ppmp_type' => $ppmpType,
        'notification_sent' => ($isFinal && !$ppmp['notification_sent']),
        'lib_synced' => $hasLibMappings,
        'lib_id' => $libIdForRefresh
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error in update_ppmp.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
