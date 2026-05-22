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

$departmentId = $_GET['id'] ?? null;

if (!$departmentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit;
}

try {
    $conn = getDB();
    
    $stmt = $conn->prepare("
        SELECT id, dept_name, dept_code, fiduciary_type, dept_description, is_active
        FROM departments 
        WHERE id = ? AND is_active = 1
    ");
    
    $stmt->execute([$departmentId]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($department) {
        echo json_encode([
            'success' => true,
            'department' => $department
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Department not found'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}


