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

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$amount = isset($_POST['allocated_amount']) ? (float)$_POST['allocated_amount'] : null;

if (!$id || $amount === null) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $alloc = new BudgetAllocation();
    $ok = $alloc->updateAllocation($id, $amount);
    if ($ok) {
        // Recalculate department totals for the allocation's department/year and upsert department_budgets
        $db = getDB();

        // Get department_id and fiscal_year for this allocation id
        $stmt = $db->prepare('SELECT department_id, fiscal_year FROM budget_allocations WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $departmentId = (int)$row['department_id'];
            $fiscalYear = (int)$row['fiscal_year'];

            // Compute totals from budget_allocations
            $sumStmt = $db->prepare('SELECT SUM(allocated_amount) AS total_allocated, SUM(utilized_amount) AS total_utilized FROM budget_allocations WHERE department_id = :dept AND fiscal_year = :yr');
            $sumStmt->execute([':dept' => $departmentId, ':yr' => $fiscalYear]);
            $sums = $sumStmt->fetch(PDO::FETCH_ASSOC);

            $totalAllocated = (float)($sums['total_allocated'] ?? 0);
            $totalUtilized = (float)($sums['total_utilized'] ?? 0);

            // Upsert into department_budgets
            $upsert = $db->prepare('INSERT INTO department_budgets (department_id, fiscal_year, total_allocated, total_utilized) VALUES (:dept,:yr,:alloc,:util)
                ON DUPLICATE KEY UPDATE total_allocated = VALUES(total_allocated), total_utilized = VALUES(total_utilized)');
            $upsert->execute([':dept' => $departmentId, ':yr' => $fiscalYear, ':alloc' => $totalAllocated, ':util' => $totalUtilized]);
        }
    }
    echo json_encode(['success' => (bool)$ok]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?>


