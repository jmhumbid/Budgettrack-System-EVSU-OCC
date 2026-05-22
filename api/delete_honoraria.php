<?php
session_start();

// Check if user is logged in and has budget access
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/utilization_deductions_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['honoraria_id']) || !isset($data['department_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$honoraria_id = (int)$data['honoraria_id'];
$department_id = (int)$data['department_id'];

try {
    $db = getDB();
    $db->beginTransaction();
    
    $user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
    
    // Check if deducted_from_entry_id column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM utilization_honoraria LIKE 'deducted_from_entry_id'");
    $hasDeductedFromColumn = $checkColumn->rowCount() > 0;
    
    // Build select fields based on column existence
    if ($hasDeductedFromColumn) {
        $selectFields = "id, amount, deducted_from_entry_id, department_id";
    } else {
        $selectFields = "id, amount, department_id";
    }
    
    // For budget role users, allow deletion regardless of department_id match
    // For other roles, require department_id to match
    if ($user_role === 'budget') {
        // Get the honoraria entry without department_id check for budget users
        $stmt = $db->prepare("SELECT $selectFields FROM utilization_honoraria WHERE id = :id");
        $stmt->execute([':id' => $honoraria_id]);
        $honoraria = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Get the honoraria entry to reverse deductions (with department_id check)
        $stmt = $db->prepare("SELECT $selectFields FROM utilization_honoraria WHERE id = :id AND department_id = :dept_id");
        $stmt->execute([':id' => $honoraria_id, ':dept_id' => $department_id]);
        $honoraria = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$honoraria) {
        $db->rollBack();
        // Log for debugging
        error_log("Honoraria Delete - Entry not found. Honoraria ID: $honoraria_id, Department ID: $department_id, User Role: $user_role");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Honoraria entry not found. It may have been already deleted or the ID is invalid.']);
        exit;
    }
    
    // Parse amount - handle both string and numeric values, remove currency symbols
    $amountStr = $honoraria['amount'];
    if (is_string($amountStr)) {
        $amountStr = str_replace(['₱', ',', ' '], '', $amountStr);
    }
    $honorariaAmount = (float)$amountStr;
    
    $deductedFromEntryId = null;
    if ($hasDeductedFromColumn && !empty($honoraria['deducted_from_entry_id'])) {
        $deductedFromEntryId = (int)$honoraria['deducted_from_entry_id'];
    }
    
    // Delete the honoraria entry
    // For budget role users, delete without department_id check
    if ($user_role === 'budget') {
        $deleteStmt = $db->prepare("DELETE FROM utilization_honoraria WHERE id = :id");
        $deleteStmt->execute([':id' => $honoraria_id]);
    } else {
        $deleteStmt = $db->prepare("DELETE FROM utilization_honoraria WHERE id = :id AND department_id = :dept_id");
        $deleteStmt->execute([':id' => $honoraria_id, ':dept_id' => $department_id]);
    }
    
    // Check if deletion was successful
    if ($deleteStmt->rowCount() === 0) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Honoraria entry not found or could not be deleted']);
        exit;
    }
    
    // Recalculate deductions for the affected entry (only if column exists and value is set)
    if ($deductedFromEntryId && $hasDeductedFromColumn) {
        recalculateDeductionsForEntry($db, $deductedFromEntryId);
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Honoraria entry deleted successfully and deduction removed']);
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    $errorMessage = 'Database error: ' . $e->getMessage();
    error_log('Honoraria Delete PDO Error: ' . $errorMessage);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    $errorMessage = 'Error deleting honoraria entry: ' . $e->getMessage();
    error_log('Honoraria Delete Error: ' . $errorMessage);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}

