<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget','school_admin'])) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$departmentId = isset($_GET['department_id']) && $_GET['department_id'] !== '' ? (int)$_GET['department_id'] : null;
$fiscalYear = isset($_GET['fiscal_year']) && $_GET['fiscal_year'] !== '' ? (int)$_GET['fiscal_year'] : (int)date('Y');

try {
    $db = getDB();
    $params = [':fiscal_year' => $fiscalYear];
    $where = 'ba.fiscal_year = :fiscal_year';
    if ($departmentId) {
        $where .= ' AND ba.department_id = :department_id';
        $params[':department_id'] = $departmentId;
    }

    $sql = "SELECT ba.*, d.dept_name, bc.category_name, bc.category_code
            FROM budget_allocations ba
            LEFT JOIN departments d ON ba.department_id = d.id
            LEFT JOIN budget_categories bc ON ba.category_id = bc.id
            WHERE $where
            ORDER BY d.dept_name, bc.category_name";

    $stmt = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v);
    }
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($rows);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([]);
}
?>


