<?php
session_start();

// Check if user is logged in and has budget access
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['entry_id']) || !isset($data['department_id']) || !isset($data['deduction'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$entry_id = (int)$data['entry_id'];
$department_id = (int)$data['department_id'];
$deduction = (float)$data['deduction'];

try {
    $db = getDB();
    
    // Get current allocated budget
    $stmt = $db->prepare("SELECT allocated_budget FROM budget_utilization_entries WHERE id = :entry_id AND department_id = :dept_id");
    $stmt->execute([':entry_id' => $entry_id, ':dept_id' => $department_id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entry) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utilization entry not found']);
        exit;
    }
    
    $allocated_budget = (float)$entry['allocated_budget'];
    $total_balance = $allocated_budget - $deduction;
    
    // Update deduction and total balance
    $updateStmt = $db->prepare("
        UPDATE budget_utilization_entries 
        SET deductions = :deduction,
            total_balance = :total_balance
        WHERE id = :entry_id AND department_id = :dept_id
    ");
    
    $updateStmt->execute([
        ':deduction' => $deduction,
        ':total_balance' => $total_balance,
        ':entry_id' => $entry_id,
        ':dept_id' => $department_id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Deduction updated successfully']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error updating deduction: ' . $e->getMessage()]);
}

