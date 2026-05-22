<?php
session_start();

// Check if user is logged in and has allocations access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

try {
    $conn = getDB();
    
    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'budget_allocations'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode(['success' => true, 'allocations' => []]);
        exit;
    }
    
    // Get department filter if provided
    $departmentFilter = isset($_GET['department_id']) && !empty($_GET['department_id']) 
        ? intval($_GET['department_id']) 
        : null;
    
    // Get year filter if provided
    $yearFilter = isset($_GET['year']) && !empty($_GET['year']) 
        ? intval($_GET['year']) 
        : null;
    
    $sql = "
        SELECT 
            ba.*,
            d.dept_name as department_name,
            d.fiduciary_type,
            CASE 
                WHEN ba.updated_at IS NOT NULL AND ba.updated_at != ba.created_at THEN 'updated'
                ELSE 'created'
            END as status
        FROM budget_allocations ba
        LEFT JOIN departments d ON ba.department_id = d.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($departmentFilter !== null) {
        $sql .= " AND ba.department_id = ?";
        $params[] = $departmentFilter;
    }
    
    if ($yearFilter !== null) {
        $sql .= " AND (YEAR(ba.created_at) = ? OR YEAR(ba.updated_at) = ?)";
        $params[] = $yearFilter;
        $params[] = $yearFilter;
    }
    
    $sql .= " ORDER BY COALESCE(ba.updated_at, ba.created_at) DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'allocations' => $allocations
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

