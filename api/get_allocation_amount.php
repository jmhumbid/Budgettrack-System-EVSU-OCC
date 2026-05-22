<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    $departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
    $fiscalYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    if ($departmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department ID']);
        exit;
    }
    
    // Get budget allocation for the specified department and fiscal year
    $stmt = $conn->prepare("
        SELECT overall_total 
        FROM budget_allocations 
        WHERE department_id = ? AND fiscal_year = ?
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$departmentId, $fiscalYear]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'amount' => floatval($result['overall_total']),
            'fiscal_year' => $fiscalYear
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'amount' => 0,
            'fiscal_year' => $fiscalYear
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching allocation: ' . $e->getMessage()
    ]);
}
