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

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$summaryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$summaryId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Summary ID is required']);
    exit;
}

try {
    $db = getDB();
    
    // Check if summary exists
    $checkStmt = $db->prepare("SELECT id FROM utilization_summaries WHERE id = ?");
    $checkStmt->execute([$summaryId]);
    
    if ($checkStmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Summary not found']);
        exit;
    }
    
    // Delete the summary
    $deleteStmt = $db->prepare("DELETE FROM utilization_summaries WHERE id = ?");
    $deleteStmt->execute([$summaryId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Summary deleted successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
