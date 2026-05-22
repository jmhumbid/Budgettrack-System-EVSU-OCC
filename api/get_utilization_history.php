<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Allow budget, school_admin, and department/office users to view history
// Department/office users can only view their own department's history
$allowedRoles = ['budget', 'school_admin', 'offices', 'supply_office', 'procurement', 'dept_head', 'department'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    // If role is not in the list, check if user has a department_id (department user)
    // Department users should be able to view their own department's history
    $userDepartmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;
    if (!$userDepartmentId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$fiscal_year = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : null;

// For department/office users, ensure they can only access their own department's history or their child departments
$userDepartmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';

// Non-budget/admin users can only view their own department or child departments
if (!in_array($userRole, ['budget', 'school_admin']) && $userDepartmentId) {
    // Check if requested department is user's own department or a child department
    $db = getDB();
    $isChildDept = false;
    
    if ($department_id != $userDepartmentId) {
        // Check if requested department is a child of user's department
        $childCheckStmt = $db->prepare("SELECT id FROM departments WHERE id = ? AND parent_department_id = ?");
        $childCheckStmt->execute([$department_id, $userDepartmentId]);
        $isChildDept = $childCheckStmt->rowCount() > 0;
    }
    
    // If not their own department and not a child department, override with user's department
    if ($department_id != $userDepartmentId && !$isChildDept) {
        $department_id = $userDepartmentId;
    }
}

if (!$department_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit;
}

try {
    $db = getDB();
    $history = [];
    
    // Get utilization entries history
    if ($fiscal_year) {
        $stmt = $db->prepare("
            SELECT 
                id,
                expense_category,
                allocated_budget,
                deductions,
                total_balance,
                fiscal_year,
                created_at,
                updated_at,
                'utilization_entry' as type
            FROM budget_utilization_entries
            WHERE department_id = :dept_id AND fiscal_year = :year
            ORDER BY fiscal_year DESC, COALESCE(updated_at, created_at) DESC
        ");
        $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    } else {
        $stmt = $db->prepare("
            SELECT 
                id,
                expense_category,
                allocated_budget,
                deductions,
                total_balance,
                fiscal_year,
                created_at,
                updated_at,
                'utilization_entry' as type
            FROM budget_utilization_entries
            WHERE department_id = :dept_id
            ORDER BY fiscal_year DESC, COALESCE(updated_at, created_at) DESC
        ");
        $stmt->execute([':dept_id' => $department_id]);
    }
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($entries as $entry) {
        $history[] = [
            'type' => 'utilization_entry',
            'action' => $entry['updated_at'] ? 'Updated' : 'Created',
            'category' => $entry['expense_category'],
            'allocated_budget' => $entry['allocated_budget'],
            'deductions' => $entry['deductions'],
            'total_balance' => $entry['total_balance'],
            'fiscal_year' => $entry['fiscal_year'],
            'timestamp' => $entry['updated_at'] ? $entry['updated_at'] : $entry['created_at'],
            'created_at' => $entry['created_at'],
            'updated_at' => $entry['updated_at']
        ];
    }
    
    // Get purchase requests history
    if ($fiscal_year) {
        $stmt = $db->prepare("
            SELECT 
                id,
                purchase_request,
                particulars,
                pr_number,
                po_number,
                date,
                amount,
                fiscal_year,
                created_at,
                updated_at,
                'purchase_request' as type
            FROM utilization_purchase_requests
            WHERE department_id = :dept_id AND fiscal_year = :year
            ORDER BY fiscal_year DESC, COALESCE(updated_at, created_at) DESC
        ");
        $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    } else {
        $stmt = $db->prepare("
            SELECT 
                id,
                purchase_request,
                particulars,
                pr_number,
                po_number,
                date,
                amount,
                fiscal_year,
                created_at,
                updated_at,
                'purchase_request' as type
            FROM utilization_purchase_requests
            WHERE department_id = :dept_id
            ORDER BY fiscal_year DESC, COALESCE(updated_at, created_at) DESC
        ");
        $stmt->execute([':dept_id' => $department_id]);
    }
    $prs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($prs as $pr) {
        $history[] = [
            'type' => 'purchase_request',
            'action' => $pr['updated_at'] ? 'Updated' : 'Created',
            'purchase_request' => $pr['purchase_request'],
            'particulars' => $pr['particulars'],
            'pr_number' => $pr['pr_number'],
            'po_number' => $pr['po_number'],
            'date' => $pr['date'],
            'amount' => $pr['amount'],
            'fiscal_year' => $pr['fiscal_year'],
            'timestamp' => $pr['updated_at'] ? $pr['updated_at'] : $pr['created_at'],
            'created_at' => $pr['created_at'],
            'updated_at' => $pr['updated_at']
        ];
    }
    
    // Get travels history
    if ($fiscal_year) {
        $stmt = $db->prepare("
            SELECT 
                id,
                travelled,
                event_activity,
                date,
                amount,
                fiscal_year,
                created_at,
                updated_at,
                'travel' as type
            FROM utilization_travels
            WHERE department_id = :dept_id AND fiscal_year = :year
            ORDER BY fiscal_year DESC, COALESCE(updated_at, created_at) DESC
        ");
        $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    } else {
        $stmt = $db->prepare("
            SELECT 
                id,
                travelled,
                event_activity,
                date,
                amount,
                fiscal_year,
                created_at,
                updated_at,
                'travel' as type
            FROM utilization_travels
            WHERE department_id = :dept_id
            ORDER BY fiscal_year DESC, COALESCE(updated_at, created_at) DESC
        ");
        $stmt->execute([':dept_id' => $department_id]);
    }
    $travels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($travels as $travel) {
        $history[] = [
            'type' => 'travel',
            'action' => $travel['updated_at'] ? 'Updated' : 'Created',
            'travelled' => $travel['travelled'],
            'event_activity' => $travel['event_activity'],
            'date' => $travel['date'],
            'amount' => $travel['amount'],
            'fiscal_year' => $travel['fiscal_year'],
            'timestamp' => $travel['updated_at'] ? $travel['updated_at'] : $travel['created_at'],
            'created_at' => $travel['created_at'],
            'updated_at' => $travel['updated_at']
        ];
    }
    
    // Get summary submissions history (if table exists)
    try {
        $checkTable = $db->query("SHOW TABLES LIKE 'utilization_summaries'");
        if ($checkTable->rowCount() > 0) {
            if ($fiscal_year) {
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        department_name,
                        fiscal_year,
                        totals,
                        created_at,
                        updated_at,
                        'summary' as type
                    FROM utilization_summaries
                    WHERE department_id = :dept_id AND fiscal_year = :year
                    ORDER BY fiscal_year DESC, COALESCE(updated_at, created_at) DESC
                ");
                $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
            } else {
                $stmt = $db->prepare("
                    SELECT 
                        id,
                        department_name,
                        fiscal_year,
                        totals,
                        created_at,
                        updated_at,
                        'summary' as type
                    FROM utilization_summaries
                    WHERE department_id = :dept_id
                    ORDER BY fiscal_year DESC, COALESCE(updated_at, created_at) DESC
                ");
                $stmt->execute([':dept_id' => $department_id]);
            }
            $summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($summaries as $summary) {
                // Parse totals JSON
                $totals = [];
                if ($summary['totals']) {
                    $totals = json_decode($summary['totals'], true);
                    if (!is_array($totals)) {
                        $totals = [];
                    }
                }
                
                $history[] = [
                    'type' => 'summary',
                    'id' => $summary['id'],
                    'action' => $summary['updated_at'] ? 'Updated' : 'Submitted',
                    'department_name' => $summary['department_name'],
                    'fiscal_year' => $summary['fiscal_year'],
                    'totalAllocated' => $totals['totalAllocated'] ?? 0,
                    'totalDeductions' => $totals['totalDeductions'] ?? 0,
                    'totalBalance' => $totals['totalBalance'] ?? 0,
                    'timestamp' => $summary['updated_at'] ? $summary['updated_at'] : $summary['created_at'],
                    'created_at' => $summary['created_at'],
                    'updated_at' => $summary['updated_at']
                ];
            }
        }
    } catch (Exception $e) {
        // Table doesn't exist, skip summaries
        error_log('utilization_summaries table not found: ' . $e->getMessage());
    }
    
    // Sort all history by timestamp (most recent first)
    usort($history, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    echo json_encode([
        'success' => true,
        'history' => $history,
        'count' => count($history)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error loading history: ' . $e->getMessage()
    ]);
}

