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
    $sheetId = (int)($_GET['sheet_id'] ?? 0);
    
    if ($sheetId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid sheet ID']);
        exit;
    }

    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM saved_sheets WHERE id = :id");
    $stmt->execute([':id' => $sheetId]);
    $sheet = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sheet) {
        echo json_encode(['success' => false, 'message' => 'Sheet not found']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'sheet' => [
            'id' => $sheet['id'],
            'sheet_name' => $sheet['sheet_name'],
            'department_id' => $sheet['department_id'],
            'fiscal_year' => $sheet['fiscal_year'],
            'headers' => json_decode($sheet['headers'], true),
            'columns' => json_decode($sheet['columns'], true),
            'data' => json_decode($sheet['data'], true),
            'cell_formats' => isset($sheet['cell_formats']) && $sheet['cell_formats'] ? json_decode($sheet['cell_formats'], true) : null
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

