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
    
    $libId = $_POST['libId'] ?? 0;
    $fiscalYear = $_POST['fiscalYear'] ?? '';
    $fundType = $_POST['fundType'] ?? 'Internally Generated Fund';
    $markAsFinal = isset($_POST['markAsFinal']) && $_POST['markAsFinal'] == '1';
    $status = $markAsFinal ? 'approved' : 'draft';
    $categories = $_POST['category'] ?? [];
    $particulars = $_POST['particulars'] ?? [];
    $accountCodes = $_POST['account_code'] ?? [];
    $amounts = $_POST['amount'] ?? [];
    
    if (!$libId || empty($fiscalYear) || empty($categories)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Verify ownership (allow editing of both draft and approved LIBs for now)
    $sql = "SELECT * FROM line_item_budgets WHERE id = ? AND department_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$libId, $departmentId]);
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        echo json_encode(['success' => false, 'message' => 'LIB not found or you do not have permission to edit it']);
        exit;
    }
    
    // Step 1: Update the LIB (main operation)
    $db->beginTransaction();
    
    // Update LIB record
    $sql = "UPDATE line_item_budgets SET fiscal_year = ?, fund_type = ?, status = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$fiscalYear, $fundType, $status, $libId]);
    
    // Delete existing items
    $sql = "DELETE FROM line_item_budget_items WHERE lib_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$libId]);
    
    // Insert new items
    $sql = "INSERT INTO line_item_budget_items (lib_id, category, particulars, account_code, amount, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $db->prepare($sql);
    
    foreach ($categories as $index => $category) {
        $stmt->execute([
            $libId,
            $category,
            $particulars[$index] ?? '',
            $accountCodes[$index] ?? '',
            $amounts[$index] ?? 0,
            $index
        ]);
    }
    
    // Commit the LIB update operation
    $db->commit();
    
    // Step 2: Handle status changes and sync operations
    if ($status === 'approved') {
        // LIB is being marked as FINAL - notify and sync
        // Get department name
        $deptStmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
        $deptStmt->execute([$departmentId]);
        $deptName = $deptStmt->fetchColumn() ?: 'Unknown Department';
        
        // Notify Budget Office users (this doesn't need a transaction)
        $notification = new Notification();
        $notification->notifyBudgetAdmins('LIB', $userId, $deptName, true);
        
        // Step 3: Sync to Utilization (separate transaction) - ONLY FOR FINAL LIBs
        try {
            $db->beginTransaction();
            
            // Ensure columns exist
            try {
                // Check if columns exist first
                $stmt = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'account_code'");
                if ($stmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE budget_utilization_entries ADD COLUMN account_code VARCHAR(50) NULL AFTER expense_category");
                }
                
                $stmt = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'is_auto_filled'");
                if ($stmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE budget_utilization_entries ADD COLUMN is_auto_filled TINYINT(1) DEFAULT 0 AFTER account_code");
                }
                
                $stmt = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'lib_id'");
                if ($stmt->rowCount() == 0) {
                    $db->exec("ALTER TABLE budget_utilization_entries ADD COLUMN lib_id INT NULL AFTER is_auto_filled");
                }
            } catch (Exception $e) {
                error_log("Column creation failed: " . $e->getMessage());
            }
            
            // CRITICAL: Delete ALL existing auto-filled entries from ANY previous LIB for this department/fiscal year
            // This ensures new FINAL LIB replaces old ones completely
            // ONLY delete when saving as FINAL (approved status)
            $deleteStmt = $db->prepare("DELETE FROM budget_utilization_entries WHERE department_id = ? AND fiscal_year = ? AND is_auto_filled = 1");
            $deleteStmt->execute([$departmentId, $fiscalYear]);
            
            // Get the next deducted_from_entry_id for this fiscal year
            $maxStmt = $db->prepare("SELECT COALESCE(MAX(deducted_from_entry_id), 0) as max_val FROM budget_utilization_entries WHERE fiscal_year = ?");
            $maxStmt->execute([$fiscalYear]);
            $maxResult = $maxStmt->fetch(PDO::FETCH_ASSOC);
            $nextDeductedFromEntryId = max(1, (int)$maxResult['max_val'] + 1);
            
            // Insert utilization entries from LIB items
            $utilizationStmt = $db->prepare("
                INSERT INTO budget_utilization_entries 
                (department_id, expense_category, account_code, allocated_budget, deductions, total_balance, 
                 fiscal_year, created_by, deducted_from_entry_id, is_auto_filled, lib_id)
                VALUES (?, ?, ?, ?, 0, ?, ?, ?, ?, 1, ?)
            ");
            
            foreach ($categories as $index => $category) {
                $particular = $particulars[$index] ?? '';
                $accountCode = $accountCodes[$index] ?? '';
                $amount = (float)($amounts[$index] ?? 0);
                
                // Skip empty entries (but allow 0 amounts to be synced)
                if (empty($particular)) {
                    continue;
                }
                
                $utilizationStmt->execute([
                    $departmentId,
                    $particular,  // PARTICULAR → EXPENSE CATEGORY
                    $accountCode, // ACCOUNT CODE → ACCOUNT CODE
                    $amount,      // AMOUNT → ALLOCATED BUDGET
                    $amount,      // TOTAL BALANCE = ALLOCATED BUDGET (initially)
                    $fiscalYear,
                    $userId,
                    $nextDeductedFromEntryId,
                    $libId
                ]);
                
                $nextDeductedFromEntryId++;
            }
            
            $db->commit();
            
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Utilization sync failed: " . $e->getMessage());
            // Don't fail the entire operation, LIB was already updated successfully
        }
        
        // Step 4: Sync to Prior Years (no transaction needed)
        try {
            $db->exec("CREATE TABLE IF NOT EXISTS prior_years_entries (
                id INT PRIMARY KEY AUTO_INCREMENT,
                department_id INT NOT NULL,
                expense_category VARCHAR(500) NOT NULL,
                student_development DECIMAL(15,2) DEFAULT 0,
                faculty_development DECIMAL(15,2) DEFAULT 0,
                curriculum_development DECIMAL(15,2) DEFAULT 0,
                facilities_development DECIMAL(15,2) DEFAULT 0,
                development_fee DECIMAL(15,2) DEFAULT 0,
                laboratory_fee DECIMAL(15,2) DEFAULT 0,
                computer_fee DECIMAL(15,2) DEFAULT 0,
                fiscal_year INT NOT NULL,
                sort_order INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_dept_year (department_id, fiscal_year),
                INDEX idx_category (expense_category)
            )");
            
            // Insert or update prior years entries with expense categories from LIB
            foreach ($categories as $index => $category) {
                $particular = $particulars[$index] ?? '';
                
                if (empty($particular)) {
                    continue;
                }
                
                // Check if entry already exists
                $checkStmt = $db->prepare("SELECT id FROM prior_years_entries WHERE department_id = ? AND expense_category = ? AND fiscal_year = ?");
                $checkStmt->execute([$departmentId, $particular, $fiscalYear]);
                
                if ($checkStmt->rowCount() == 0) {
                    $insertStmt = $db->prepare("
                        INSERT INTO prior_years_entries 
                        (department_id, expense_category, fiscal_year, sort_order)
                        VALUES (?, ?, ?, ?)
                    ");
                    $insertStmt->execute([
                        $departmentId,
                        $particular,
                        $fiscalYear,
                        $index
                    ]);
                }
            }
        } catch (Exception $e) {
            // Prior years sync failed, but don't fail the entire operation
            error_log("Prior years sync failed: " . $e->getMessage());
        }
        
    } else {
        // LIB is being marked as DRAFT - remove auto-filled utilization entries
        try {
            $deleteStmt = $db->prepare("DELETE FROM budget_utilization_entries WHERE lib_id = ? AND is_auto_filled = 1");
            $deleteStmt->execute([$libId]);
        } catch (Exception $e) {
            error_log("Failed to remove auto-filled utilization entries: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Line Item Budget updated successfully' . 
                    ($status === 'approved' ? ' and synced to Utilization' : 
                    ($lib['status'] === 'approved' && $status === 'draft' ? ' and utilization entries removed' : ''))
    ]);
    
} catch (Exception $e) {
    // Only rollback if we're still in the main transaction
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error in update_lib.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>