<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'] ?? '';
    $departmentId = $_SESSION['department_id'] ?? null;
    
    // For budget role with no session department, look up from users table,
    // then fall back to the Fiduciary (Budget Office) department
    if (!$departmentId && $userRole === 'budget') {
        $stmt = $db->prepare("SELECT department_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['department_id']) {
            $departmentId = $row['department_id'];
        } else {
            $stmt = $db->prepare("SELECT u.department_id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'budget' AND u.department_id IS NOT NULL LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $departmentId = $row['department_id'];
        }
    }
    
    // Check if admin is requesting specific department
    $requestedDeptId = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
    $fiscalYear = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : null;
    $status = isset($_GET['status']) ? $_GET['status'] : null;
    $ppmpType = isset($_GET['ppmp_type']) ? $_GET['ppmp_type'] : null; // 'ppmp' or 'supplemental'
    
    // Budget office and school_admin can view any department
    if (in_array($userRole, ['budget', 'school_admin']) && $requestedDeptId) {
        $departmentId = $requestedDeptId;
    }
    
    // All users can only see their own department's PPMPs (unless admin viewing specific dept)
    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'No department assigned']);
        exit;
    }
    
    $sql = "SELECT p.*, d.dept_name, CONCAT(u.first_name, ' ', u.last_name) as created_by_name
            FROM ppmp p
            LEFT JOIN departments d ON p.department_id = d.id
            LEFT JOIN users u ON p.created_by = u.id
            WHERE p.department_id = ?";
    
    $params = [$departmentId];
    
    if ($fiscalYear) {
        $sql .= " AND p.fiscal_year = ?";
        $params[] = $fiscalYear;
    }
    
    if ($status) {
        $sql .= " AND p.status = ?";
        $params[] = $status;
    }
    
    if ($ppmpType) {
        $sql .= " AND p.ppmp_type = ?";
        $params[] = $ppmpType;
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $ppmps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'ppmps' => $ppmps
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_ppmp_list.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
