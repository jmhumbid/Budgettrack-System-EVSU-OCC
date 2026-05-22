<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$departmentId = $_GET['department_id'] ?? null;

if (!$departmentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit;
}

try {
    $conn = getDB();
    
    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'allocation_drafts'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'No drafts found']);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT * FROM allocation_drafts 
        WHERE department_id = ?
        LIMIT 1
    ");
    
    $stmt->execute([$departmentId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Decode draft_data if it's a string
        if (is_string($data['draft_data'])) {
            $data['draft_data'] = json_decode($data['draft_data'], true);
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No draft found for this department'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
