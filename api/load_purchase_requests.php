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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$fiscal_year = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : date('Y');
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// For budget role users, show ALL entries regardless of who created them
// For other roles, still require department_id
if ($user_role !== 'budget' && !$department_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit;
}

try {
    $db = getDB();
    
    // For budget role: show all entries for the selected department (shared across all budget accounts)
    // For other roles: show only their department's entries
    if ($user_role === 'budget' && $department_id) {
        // Budget role users see ALL entries for the selected department (shared database)
        $stmt = $db->prepare("
            SELECT pr.id, pr.purchase_request, pr.particulars, pr.pr_number, pr.po_number, 
                   pr.date, pr.amount, pr.created_by, pr.entry_id,
                   pr.ppmp_item_id, pr.ppmp_id, pr.ppmp_description,
                   COALESCE(p.ppmp_type, 'ppmp') as ppmp_type
            FROM utilization_purchase_requests pr
            LEFT JOIN ppmp p ON pr.ppmp_id = p.id
            WHERE pr.department_id = :dept_id AND pr.fiscal_year = :year
            ORDER BY pr.id ASC
        ");
        $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    } else if ($user_role === 'budget' && !$department_id) {
        // Budget role users can also see all entries across all departments if no department selected
        $stmt = $db->prepare("
            SELECT pr.id, pr.purchase_request, pr.particulars, pr.pr_number, pr.po_number, 
                   pr.date, pr.amount, pr.created_by, pr.entry_id,
                   pr.ppmp_item_id, pr.ppmp_id, pr.ppmp_description,
                   COALESCE(p.ppmp_type, 'ppmp') as ppmp_type
            FROM utilization_purchase_requests pr
            LEFT JOIN ppmp p ON pr.ppmp_id = p.id
            WHERE pr.fiscal_year = :year
            ORDER BY pr.department_id ASC, pr.id ASC
        ");
        $stmt->execute([':year' => $fiscal_year]);
    } else {
        // Other roles: only their department
        $stmt = $db->prepare("
            SELECT pr.id, pr.purchase_request, pr.particulars, pr.pr_number, pr.po_number, 
                   pr.date, pr.amount, pr.created_by, pr.entry_id,
                   pr.ppmp_item_id, pr.ppmp_id, pr.ppmp_description,
                   COALESCE(p.ppmp_type, 'ppmp') as ppmp_type
            FROM utilization_purchase_requests pr
            LEFT JOIN ppmp p ON pr.ppmp_id = p.id
            WHERE pr.department_id = :dept_id AND pr.fiscal_year = :year
            ORDER BY pr.id ASC
        ");
        $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    }
    
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'entries' => $entries]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error loading purchase requests: ' . $e->getMessage()]);
}

