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
    
    if ($user_role === 'budget' && $department_id) {
        // Budget role users see ALL entries for the selected department (shared database)
        $sql = "
            SELECT id, travelled, event_activity, date, amount, created_by, entry_id
            FROM utilization_travels
            WHERE department_id = :dept_id AND fiscal_year = :year
            ORDER BY id ASC
        ";
    } else if ($user_role === 'budget' && !$department_id) {
        // Budget role users can also see all entries across all departments if no department selected
        $sql = "
            SELECT id, travelled, event_activity, date, amount, created_by, entry_id
            FROM utilization_travels
            WHERE fiscal_year = :year
            ORDER BY department_id ASC, id ASC
        ";
    } else {
        // Other roles: only their department
        $sql = "
            SELECT id, travelled, event_activity, date, amount, created_by, entry_id
            FROM utilization_travels
            WHERE department_id = :dept_id AND fiscal_year = :year
            ORDER BY id ASC
        ";
    }
    
    $stmt = $db->prepare($sql);
    if ($user_role === 'budget' && !$department_id) {
        $stmt->execute([':year' => $fiscal_year]);
    } else {
        $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    }
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'entries' => $entries]);
    
    } catch (Exception $e) {
        http_response_code(500);
        $errorMessage = 'Error loading travels: ' . $e->getMessage();
        error_log($errorMessage);
        echo json_encode(['success' => false, 'message' => $errorMessage, 'entries' => []]);
    }

