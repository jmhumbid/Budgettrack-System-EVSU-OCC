<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$fiscalYear = isset($_POST['fiscal_year']) && $_POST['fiscal_year'] !== '' ? (int)$_POST['fiscal_year'] : (int)date('Y');
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;

try {
    $db = getDB();
    $db->beginTransaction();

    $filePath = $_FILES['file']['tmp_name'];
    $handle = fopen($filePath, 'r');
    if ($handle === false) { throw new Exception('Cannot open file'); }

    // Expect header: department_code,category_code,fiscal_year,allocated_amount
    $header = fgetcsv($handle);
    $imported = 0; $updated = 0; $skipped = 0;

    // Prepare lookups
    $deptStmt = $db->prepare('SELECT id FROM departments WHERE dept_code = :code');
    $catStmt = $db->prepare('SELECT id FROM budget_categories WHERE category_code = :code');

    // Upsert
    $upsert = $db->prepare('INSERT INTO budget_allocations (department_id, category_id, fiscal_year, allocated_amount, created_by) VALUES (:dept,:cat,:yr,:amt,:uid)
        ON DUPLICATE KEY UPDATE allocated_amount = VALUES(allocated_amount)');

    // Track affected department/year pairs to recompute department_budgets afterward
    $affected = [];

    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < 4) { $skipped++; continue; }
        $deptCode = trim($row[0]);
        $catCode = trim($row[1]);
        $yr = trim($row[2]) !== '' ? (int)$row[2] : $fiscalYear;
        $amt = (float)$row[3];

        if ($deptCode === '' || $catCode === '') { $skipped++; continue; }

        $deptStmt->execute([':code' => $deptCode]);
        $deptId = $deptStmt->fetchColumn();
        $catStmt->execute([':code' => $catCode]);
        $catId = $catStmt->fetchColumn();
        if (!$deptId || !$catId) { $skipped++; continue; }

        $upsert->execute([':dept'=>$deptId, ':cat'=>$catId, ':yr'=>$yr, ':amt'=>$amt, ':uid'=>$userId]);
        if ($upsert->rowCount() === 1) { $imported++; } else { $updated++; }

        // Remember affected department/year
        $affectedKey = $deptId . ':' . $yr;
        $affected[$affectedKey] = ['dept' => (int)$deptId, 'yr' => (int)$yr];
    }
    fclose($handle);

    // Recalculate totals for each affected department/year and upsert department_budgets
    if (!empty($affected)) {
        $sumStmt = $db->prepare('SELECT SUM(allocated_amount) AS total_allocated, SUM(utilized_amount) AS total_utilized FROM budget_allocations WHERE department_id = :dept AND fiscal_year = :yr');
        $upsertBudget = $db->prepare('INSERT INTO department_budgets (department_id, fiscal_year, total_allocated, total_utilized) VALUES (:dept,:yr,:alloc,:util)
            ON DUPLICATE KEY UPDATE total_allocated = VALUES(total_allocated), total_utilized = VALUES(total_utilized)');
        foreach ($affected as $pair) {
            $sumStmt->execute([':dept' => $pair['dept'], ':yr' => $pair['yr']]);
            $sums = $sumStmt->fetch(PDO::FETCH_ASSOC);
            $totalAllocated = (float)($sums['total_allocated'] ?? 0);
            $totalUtilized = (float)($sums['total_utilized'] ?? 0);
            $upsertBudget->execute([':dept' => $pair['dept'], ':yr' => $pair['yr'], ':alloc' => $totalAllocated, ':util' => $totalUtilized]);
        }
    }

    $db->commit();
    echo json_encode(['success'=>true,'imported'=>$imported,'updated'=>$updated,'skipped'=>$skipped]);
} catch (Exception $e) {
    if ($db && $db->inTransaction()) { $db->rollBack(); }
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Import failed']);
}
?>


