<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/BudgetAllocation.php';
require_once __DIR__ . '/../config/database.php';

$department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
$category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$fiscal_year = isset($_POST['fiscal_year']) ? (int)$_POST['fiscal_year'] : (int)date('Y');
$allocated_amount = isset($_POST['allocated_amount']) ? (float)$_POST['allocated_amount'] : 0;
$created_by = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

if (!$department_id || !$category_id || $allocated_amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided']);
    exit;
}

try {
    $alloc = new BudgetAllocation();
    $ok = $alloc->createAllocation($department_id, $category_id, $fiscal_year, $allocated_amount, $created_by);
    
    if ($ok) {
        // Recalculate department totals and upsert department_budgets
        $db = getDB();
        
        // Compute totals from budget_allocations for this department/year
        $sumStmt = $db->prepare('SELECT SUM(allocated_amount) AS total_allocated, SUM(utilized_amount) AS total_utilized FROM budget_allocations WHERE department_id = :dept AND fiscal_year = :yr');
        $sumStmt->execute([':dept' => $department_id, ':yr' => $fiscal_year]);
        $sums = $sumStmt->fetch(PDO::FETCH_ASSOC);

        $totalAllocated = (float)($sums['total_allocated'] ?? 0);
        $totalUtilized = (float)($sums['total_utilized'] ?? 0);

        // Upsert into department_budgets
        $upsert = $db->prepare('INSERT INTO department_budgets (department_id, fiscal_year, total_allocated, total_utilized) VALUES (:dept,:yr,:alloc,:util)
            ON DUPLICATE KEY UPDATE total_allocated = VALUES(total_allocated), total_utilized = VALUES(total_utilized)');
        $upsert->execute([':dept' => $department_id, ':yr' => $fiscal_year, ':alloc' => $totalAllocated, ':util' => $totalUtilized]);
    }
    
    echo json_encode(['success' => (bool)$ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
