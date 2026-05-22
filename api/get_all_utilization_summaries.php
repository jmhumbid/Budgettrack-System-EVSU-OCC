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

try {
    $db = getDB();
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'utilization_summaries'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode(['success' => true, 'summaries' => []]);
        exit;
    }
    
    // Get filters
    $departmentFilter = isset($_GET['department_id']) && !empty($_GET['department_id']) 
        ? intval($_GET['department_id']) 
        : null;
    
    // Get year filter if provided
    $yearFilter = isset($_GET['year']) && !empty($_GET['year']) 
        ? intval($_GET['year']) 
        : null;
    
    $sql = "
        SELECT 
            us.*,
            d.dept_name,
            d.fiduciary_type,
            CASE 
                WHEN us.updated_at IS NOT NULL AND us.updated_at != us.created_at THEN 'updated'
                ELSE 'created'
            END as status
        FROM utilization_summaries us
        LEFT JOIN departments d ON us.department_id = d.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($departmentFilter !== null) {
        $sql .= " AND us.department_id = ?";
        $params[] = $departmentFilter;
    }
    
    if ($yearFilter !== null) {
        $sql .= " AND (us.fiscal_year = ? OR YEAR(us.created_at) = ? OR YEAR(us.updated_at) = ?)";
        $params[] = $yearFilter;
        $params[] = $yearFilter;
        $params[] = $yearFilter;
    }
    
    $sql .= " ORDER BY COALESCE(us.updated_at, us.created_at) DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summaries' => $summaries
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
