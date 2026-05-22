<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $sheetId = (int)($_POST['sheet_id'] ?? 0);
    
    if ($sheetId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid sheet ID']);
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("DELETE FROM saved_sheets WHERE id = :id AND created_by = :user");
    $stmt->execute([':id' => $sheetId, ':user' => $_SESSION['user_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Sheet deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Sheet not found or unauthorized']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

