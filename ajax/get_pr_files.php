<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/PurchaseRequest.php';

$prId = isset($_GET['pr_id']) ? (int)$_GET['pr_id'] : 0;

if (!$prId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'PR ID required']);
    exit;
}

try {
    $pr = new PurchaseRequest();
    $files = $pr->getPRFiles($prId);
    
    echo json_encode([
        'success' => true,
        'files' => $files
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

