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
    $libId = $_GET['id'] ?? 0;
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'] ?? '';
    $departmentId = $_SESSION['department_id'] ?? null;
    
    if (!$libId) {
        echo json_encode(['success' => false, 'message' => 'Invalid LIB ID']);
        exit;
    }
    
    // Get LIB details
    $sql = "SELECT l.*, d.dept_name, d.dept_code, CONCAT(u.first_name, ' ', u.last_name) as created_by_name
            FROM line_item_budgets l
            LEFT JOIN departments d ON l.department_id = d.id
            LEFT JOIN users u ON l.created_by = u.id
            WHERE l.id = ?";
    
    // Add department restriction for non-budget users
    if ($userRole !== 'budget') {
        $sql .= " AND l.department_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$libId, $departmentId]);
    } else {
        $stmt = $db->prepare($sql);
        $stmt->execute([$libId]);
    }
    
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        echo json_encode(['success' => false, 'message' => 'LIB not found']);
        exit;
    }
    
    // Get budget items (only parent items and items without parents)
    $sql = "SELECT * FROM line_item_budget_items WHERE lib_id = ? AND (parent_id IS NULL OR parent_id = 0) ORDER BY sort_order, id";
    $stmt = $db->prepare($sql);
    $stmt->execute([$libId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each parent item, get its sub-categories
    foreach ($items as &$item) {
        // Add is_ppmp_linked flag for easier frontend handling
        $item['is_ppmp_linked'] = ($item['source'] === 'ppmp');
        
        if ($item['is_parent'] == 1) {
            $sql = "SELECT * FROM line_item_budget_items WHERE parent_id = ? ORDER BY created_at ASC";
            $stmt = $db->prepare($sql);
            $stmt->execute([$item['id']]);
            $item['sub_categories'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $item['sub_categories'] = [];
        }
    }
    unset($item);
    
    // Get department info
    $sql = "SELECT * FROM departments WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$lib['department_id']]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'lib' => $lib,
        'items' => $items,
        'department' => $department
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_lib_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
