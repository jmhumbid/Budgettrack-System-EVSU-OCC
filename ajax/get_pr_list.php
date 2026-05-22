<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'procurement') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/PurchaseRequest.php';

$filters = [];
if (isset($_GET['department_id']) && $_GET['department_id']) {
    $filters['department_id'] = (int)$_GET['department_id'];
}
if (isset($_GET['status']) && $_GET['status']) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['date_from']) && $_GET['date_from']) {
    $filters['date_from'] = $_GET['date_from'];
}
if (isset($_GET['date_to']) && $_GET['date_to']) {
    $filters['date_to'] = $_GET['date_to'];
}

try {
    $pr = new PurchaseRequest();
    $prs = $pr->getPRsForProcurement($filters);
    
    echo json_encode([
        'success' => true,
        'prs' => $prs
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

