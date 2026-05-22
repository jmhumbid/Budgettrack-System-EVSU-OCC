<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
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

$summaryId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$summaryId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Summary ID is required']);
    exit;
}

try {
    $db = getDB();
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'utilization_summaries'");
    if ($checkTable->rowCount() == 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Summary not found']);
        exit;
    }
    
    // Check if honoraria_entries column exists, add it if not
    try {
        $checkColumn = $db->query("SHOW COLUMNS FROM utilization_summaries LIKE 'honoraria_entries'");
        if ($checkColumn->rowCount() == 0) {
            $db->exec("ALTER TABLE utilization_summaries ADD COLUMN honoraria_entries TEXT AFTER travels_entries");
        }
    } catch (Exception $e) {
        // Column might already exist or there's a permission issue, continue anyway
        error_log('Note: Could not add honoraria_entries column: ' . $e->getMessage());
    }
    
    // Check if deduction breakdown columns exist
    $hasPrDeductions = false;
    $hasTravelsDeductions = false;
    $hasHonorariaDeductions = false;
    
    try {
        $checkPrDeductions = $db->query("SHOW COLUMNS FROM utilization_summaries LIKE 'pr_deductions'");
        $hasPrDeductions = $checkPrDeductions->rowCount() > 0;
    } catch (Exception $e) {}
    
    try {
        $checkTravelsDeductions = $db->query("SHOW COLUMNS FROM utilization_summaries LIKE 'travels_deductions'");
        $hasTravelsDeductions = $checkTravelsDeductions->rowCount() > 0;
    } catch (Exception $e) {}
    
    try {
        $checkHonorariaDeductions = $db->query("SHOW COLUMNS FROM utilization_summaries LIKE 'honoraria_deductions'");
        $hasHonorariaDeductions = $checkHonorariaDeductions->rowCount() > 0;
    } catch (Exception $e) {}
    
    // Build SELECT query based on available columns
    $selectFields = "id, department_id, fiscal_year, department_name, utilization_entries, pr_entries, travels_entries, honoraria_entries, totals, created_at, updated_at";
    
    if ($hasPrDeductions) {
        $selectFields .= ", pr_deductions";
    }
    if ($hasTravelsDeductions) {
        $selectFields .= ", travels_deductions";
    }
    if ($hasHonorariaDeductions) {
        $selectFields .= ", honoraria_deductions";
    }
    
    $stmt = $db->prepare("SELECT $selectFields FROM utilization_summaries WHERE id = ?");
    $stmt->execute([$summaryId]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$summary) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Summary not found']);
        exit;
    }
    
    // Ensure optional fields are set to empty array if null or missing
    if (!isset($summary['honoraria_entries']) || $summary['honoraria_entries'] === null) {
        $summary['honoraria_entries'] = '[]';
    }
    
    // Always ensure deduction fields exist, even if columns don't exist in database
    // This handles cases where summaries were created before these columns were added
    if (!isset($summary['pr_deductions']) || $summary['pr_deductions'] === null || $summary['pr_deductions'] === '') {
        $summary['pr_deductions'] = '[]';
    }
    if (!isset($summary['travels_deductions']) || $summary['travels_deductions'] === null || $summary['travels_deductions'] === '') {
        $summary['travels_deductions'] = '[]';
    }
    if (!isset($summary['honoraria_deductions']) || $summary['honoraria_deductions'] === null || $summary['honoraria_deductions'] === '') {
        $summary['honoraria_deductions'] = '[]';
    }
    
    // Debug: Log the deduction data to help troubleshoot
    error_log('Summary deduction data: pr=' . $summary['pr_deductions'] . ', travels=' . $summary['travels_deductions'] . ', honoraria=' . $summary['honoraria_deductions']);
    
    echo json_encode(['success' => true, 'summary' => $summary]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error loading summary: ' . $e->getMessage()]);
}

