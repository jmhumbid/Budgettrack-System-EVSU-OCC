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
    $db = getDB();
    
    // Check if table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'saved_sheets'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode(['success' => true, 'sheets' => []]);
        exit;
    }
    
    $departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
    $fiscalYear = isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : null;
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Remove any special characters that might cause issues
    $search = preg_replace('/[^a-zA-Z0-9\s]/', '', $search);
    
    $query = "SELECT s.*, d.dept_name, u.first_name, u.last_name 
              FROM saved_sheets s
              LEFT JOIN departments d ON s.department_id = d.id
              LEFT JOIN users u ON s.created_by = u.id
              WHERE 1=1";
    
    $params = [];
    
    if ($departmentId && $departmentId > 0) {
        $query .= " AND s.department_id = :dept";
        $params[':dept'] = $departmentId;
    }
    
    if ($fiscalYear && $fiscalYear > 0) {
        $query .= " AND s.fiscal_year = :year";
        $params[':year'] = $fiscalYear;
    }
    
    if ($search && strlen($search) > 0) {
        $query .= " AND (s.sheet_name LIKE :search OR d.dept_name LIKE :search)";
        $params[':search'] = "%$search%";
    }
    
    $query .= " ORDER BY s.updated_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $sheets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get unique departments and years for filters
    $deptQuery = "SELECT DISTINCT d.id, d.dept_name as name 
                  FROM saved_sheets s
                  LEFT JOIN departments d ON s.department_id = d.id
                  WHERE d.id IS NOT NULL
                  ORDER BY d.dept_name";
    $deptStmt = $db->prepare($deptQuery);
    $deptStmt->execute();
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $yearQuery = "SELECT DISTINCT fiscal_year as year 
                  FROM saved_sheets 
                  WHERE fiscal_year IS NOT NULL
                  ORDER BY fiscal_year DESC";
    $yearStmt = $db->prepare($yearQuery);
    $yearStmt->execute();
    $years = $yearStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true, 
        'sheets' => $sheets,
        'departments' => $departments,
        'years' => $years
    ]);
} catch (Exception $e) {
    http_response_code(500);
    error_log("get_saved_sheets.php error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

