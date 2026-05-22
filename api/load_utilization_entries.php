<?php
session_start();

// Allow budget, school_admin, and users from Admin department
$allowedRoles = ['budget', 'school_admin'];
$isAdminDepartment = false;

if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Check if user is from Admin department/office
if (isset($_SESSION['department_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
        $stmt->execute([$_SESSION['department_id']]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dept && stripos($dept['dept_name'], 'admin') !== false) {
            $isAdminDepartment = true;
        }
    } catch (Exception $e) {
        // Continue with normal access check
    }
}

// Check access: must be in allowed roles OR from Admin department
if (!in_array($_SESSION['user_role'], $allowedRoles) && !$isAdminDepartment) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;
$fiscal_year = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : null;

if (!$department_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit;
}

try {
    $db = getDB();
    
    // Check if budget_utilization_entries table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'budget_utilization_entries'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode(['success' => true, 'entries' => [], 'deduction_sources' => [], 'fiscal_year' => null]);
        exit;
    }
    
    // If no fiscal year specified, use current year
    if (!$fiscal_year) {
        $fiscal_year = date('Y');
    }
    
    // Load entries from budget_utilization_entries table
    $stmt = $db->prepare("
        SELECT id, deducted_from_entry_id, expense_category, account_code, allocated_budget, deductions, total_balance, is_auto_filled, lib_id
        FROM budget_utilization_entries
        WHERE department_id = :dept_id AND fiscal_year = :year
        ORDER BY id ASC
    ");
    $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Load deduction sources from the new table
    $deductionSources = [];
    $checkDeductionTable = $db->query("SHOW TABLES LIKE 'budget_utilization_deduction_sources'");
    if ($checkDeductionTable->rowCount() > 0) {
        $sourcesStmt = $db->prepare("
            SELECT entry_id, category_name, source_type, amount, source_entries
            FROM budget_utilization_deduction_sources
            WHERE department_id = :dept_id AND fiscal_year = :year
        ");
        $sourcesStmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
        $sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sources as $source) {
            $deductionSources[] = [
                'entry_id' => $source['entry_id'],
                'categoryName' => $source['category_name'],
                'sourceType' => $source['source_type'],
                'amount' => (float)$source['amount'],
                'entries' => json_decode($source['source_entries'], true)
            ];
        }
    }
    
    // Format entries for response
    $formattedEntries = [];
    foreach ($entries as $entry) {
        $formattedEntries[] = [
            'id' => $entry['id'],
            'deducted_from_entry_id' => $entry['deducted_from_entry_id'],
            'expense_category' => $entry['expense_category'],
            'account_code' => $entry['account_code'] ?? '',
            'allocated_budget' => (float)$entry['allocated_budget'],
            'deductions' => (float)$entry['deductions'],
            'total_balance' => (float)$entry['total_balance'],
            'is_auto_filled' => (bool)($entry['is_auto_filled'] ?? false),
            'lib_id' => $entry['lib_id'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'entries' => $formattedEntries, 
        'deduction_sources' => $deductionSources, 
        'fiscal_year' => $fiscal_year
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error loading entries: ' . $e->getMessage()]);
}


