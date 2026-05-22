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
    $ppmpId = $_GET['id'] ?? 0;
    $departmentId = $_SESSION['department_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? '';
    
    if (!$ppmpId) {
        echo json_encode(['success' => false, 'message' => 'PPMP ID required']);
        exit;
    }
    
    // Budget office and school_admin can view any PPMP
    $canViewAll = in_array($userRole, ['budget', 'school_admin']);
    
    // Get PPMP details
    if ($canViewAll) {
        // Admin can view any PPMP
        $sql = "SELECT p.*, d.dept_name, d.dept_code
                FROM ppmp p
                LEFT JOIN departments d ON p.department_id = d.id
                WHERE p.id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$ppmpId]);
    } else {
        // Regular users can only view their department's PPMP
        $sql = "SELECT p.*, d.dept_name, d.dept_code
                FROM ppmp p
                LEFT JOIN departments d ON p.department_id = d.id
                WHERE p.id = ? AND p.department_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$ppmpId, $departmentId]);
    }
    
    $ppmp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ppmp) {
        echo json_encode(['success' => false, 'message' => 'PPMP not found']);
        exit;
    }
    
    // Get PPMP items with expense category from deductions
    $sql = "SELECT pi.*, 
            GROUP_CONCAT(DISTINCT pd.expense_category SEPARATOR ', ') as deducted_from_categories
            FROM ppmp_items pi
            LEFT JOIN ppmp_deductions pd ON pi.id = pd.ppmp_item_id
            WHERE pi.ppmp_id = ? 
            GROUP BY pi.id
            ORDER BY pi.sort_order";
    $stmt = $db->prepare($sql);
    $stmt->execute([$ppmpId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get department info
    $department = [
        'dept_name' => $ppmp['dept_name'],
        'dept_code' => $ppmp['dept_code']
    ];
    
    echo json_encode([
        'success' => true,
        'ppmp' => $ppmp,
        'items' => $items,
        'department' => $department
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_ppmp_details.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
