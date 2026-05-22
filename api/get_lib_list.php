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
    $sessionDepartmentId = $_SESSION['department_id'] ?? null;
    
    // For budget role with no session department, look up from users table,
    // then fall back to the Fiduciary (Budget Office) department
    if (!$sessionDepartmentId && $userRole === 'budget') {
        $stmt = $db->prepare("SELECT department_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['department_id']) {
            $sessionDepartmentId = $row['department_id'];
        } else {
            $stmt = $db->prepare("SELECT u.department_id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'budget' AND u.department_id IS NOT NULL LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $sessionDepartmentId = $row['department_id'];
        }
    }
    
    // Check if department_id is provided in query (for admin/budget office view)
    $requestedDepartmentId = $_GET['department_id'] ?? null;
    
    // Check if user is from Admin department
    $isAdminDepartment = false;
    if ($sessionDepartmentId) {
        $stmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
        $stmt->execute([$sessionDepartmentId]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dept && stripos($dept['dept_name'], 'admin') !== false) {
            $isAdminDepartment = true;
        }
    }
    
    // Determine which department to query and status filter
    $departmentId = null;
    $statusFilter = null;
    
    if ($requestedDepartmentId && (in_array($userRole, ['budget', 'school_admin']) || $isAdminDepartment)) {
        // If budget/admin is requesting their OWN department, show all statuses
        // If requesting another department, show only approved
        if ($sessionDepartmentId && $requestedDepartmentId == $sessionDepartmentId) {
            $departmentId = $requestedDepartmentId;
            // No status filter - own department sees all
        } else {
            $departmentId = $requestedDepartmentId;
            $statusFilter = 'approved'; // Viewing another dept - only approved
        }
    } else if ($sessionDepartmentId) {
        // Regular users can see their own department's LIBs (both draft and approved)
        $departmentId = $sessionDepartmentId;
        // No status filter for department users - they can see all their LIBs
    } else {
        echo json_encode(['success' => false, 'message' => 'No department specified']);
        exit;
    }
    
    $sql = "SELECT l.*, d.dept_name, d.dept_code, CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
            (SELECT SUM(amount) FROM line_item_budget_items WHERE lib_id = l.id) as total_amount
            FROM line_item_budgets l
            LEFT JOIN departments d ON l.department_id = d.id
            LEFT JOIN users u ON l.created_by = u.id
            WHERE l.department_id = ?";
    
    $params = [$departmentId];
    
    // Add year filter if provided
    $yearFilter = $_GET['year'] ?? null;
    if ($yearFilter) {
        $sql .= " AND l.fiscal_year LIKE ?";
        $params[] = "%$yearFilter%";
    }
    
    // Add status filter for budget office users
    if ($statusFilter) {
        $sql .= " AND l.status = ?";
        $params[] = $statusFilter;
    }
    
    $sql .= " ORDER BY l.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $libs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug logging
    error_log("get_lib_list.php - Department ID: $departmentId, Status Filter: " . ($statusFilter ?? 'none') . ", Found: " . count($libs) . " LIBs");
    error_log("get_lib_list.php - SQL: $sql");
    error_log("get_lib_list.php - Params: " . json_encode($params));
    
    echo json_encode([
        'success' => true,
        'libs' => $libs,
        'debug' => [
            'department_id' => $departmentId,
            'status_filter' => $statusFilter,
            'user_role' => $userRole,
            'is_admin_dept' => $isAdminDepartment,
            'count' => count($libs)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_lib_list.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
