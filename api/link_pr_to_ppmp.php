<?php
/**
 * Link Purchase Request to PPMP Item and Track Deduction
 * Updates purchase request with PPMP reference and creates deduction tracking
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    $purchaseRequestId = $data['purchase_request_id'] ?? null;
    $ppmpItemId = $data['ppmp_item_id'] ?? null;
    $ppmpId = $data['ppmp_id'] ?? null;
    $utilizationEntryId = $data['utilization_entry_id'] ?? null;
    $expenseCategory = $data['expense_category'] ?? null;
    $amount = $data['amount'] ?? 0;
    $departmentId = $data['department_id'] ?? null;
    $fiscalYear = $data['fiscal_year'] ?? date('Y');
    
    if (!$purchaseRequestId || !$ppmpItemId || !$ppmpId || !$utilizationEntryId || !$expenseCategory) {
        throw new Exception('Missing required parameters');
    }
    
    // 1. Update purchase request with PPMP reference
    $updatePrQuery = "
        UPDATE purchase_requests 
        SET ppmp_item_id = :ppmp_item_id,
            ppmp_id = :ppmp_id
        WHERE id = :pr_id
    ";
    $updatePrStmt = $db->prepare($updatePrQuery);
    $updatePrStmt->execute([
        ':ppmp_item_id' => $ppmpItemId,
        ':ppmp_id' => $ppmpId,
        ':pr_id' => $purchaseRequestId
    ]);
    
    // 2. Update PPMP item with deduction information
    $updatePpmpItemQuery = "
        UPDATE ppmp_items 
        SET deducted_amount = COALESCE(deducted_amount, 0) + :amount,
            expense_category = :expense_category,
            deduction_remarks = CONCAT(
                COALESCE(deduction_remarks, ''),
                IF(COALESCE(deduction_remarks, '') = '', '', ', '),
                :expense_category
            )
        WHERE id = :ppmp_item_id
    ";
    $updatePpmpItemStmt = $db->prepare($updatePpmpItemQuery);
    $updatePpmpItemStmt->execute([
        ':amount' => $amount,
        ':expense_category' => $expenseCategory,
        ':ppmp_item_id' => $ppmpItemId
    ]);
    
    // 3. Create deduction tracking record
    $insertDeductionQuery = "
        INSERT INTO ppmp_deductions 
        (ppmp_id, ppmp_item_id, purchase_request_id, utilization_entry_id, 
         department_id, expense_category, amount, fiscal_year)
        VALUES 
        (:ppmp_id, :ppmp_item_id, :pr_id, :utilization_entry_id,
         :department_id, :expense_category, :amount, :fiscal_year)
    ";
    $insertDeductionStmt = $db->prepare($insertDeductionQuery);
    $insertDeductionStmt->execute([
        ':ppmp_id' => $ppmpId,
        ':ppmp_item_id' => $ppmpItemId,
        ':pr_id' => $purchaseRequestId,
        ':utilization_entry_id' => $utilizationEntryId,
        ':department_id' => $departmentId,
        ':expense_category' => $expenseCategory,
        ':amount' => $amount,
        ':fiscal_year' => $fiscalYear
    ]);
    
    // 4. Get department user for notification
    $deptQuery = "
        SELECT u.id, d.dept_name 
        FROM ppmp p
        INNER JOIN departments d ON p.department_id = d.id
        INNER JOIN users u ON p.created_by = u.id
        WHERE p.id = :ppmp_id
        LIMIT 1
    ";
    $deptStmt = $db->prepare($deptQuery);
    $deptStmt->execute([':ppmp_id' => $ppmpId]);
    $deptInfo = $deptStmt->fetch(PDO::FETCH_ASSOC);
    
    // 5. Send notification to department user
    if ($deptInfo) {
        $notification = new Notification();
        $title = "PPMP Deduction Applied";
        $message = sprintf(
            "A deduction of ₱%s has been applied to your PPMP under the expense category '%s'. Fiscal Year: %s",
            number_format($amount, 2),
            $expenseCategory,
            $fiscalYear
        );
        $notification->createNotification($deptInfo['id'], $title, $message, 'info');
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Purchase request linked to PPMP successfully',
        'deduction_id' => $db->lastInsertId()
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error linking PR to PPMP: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
