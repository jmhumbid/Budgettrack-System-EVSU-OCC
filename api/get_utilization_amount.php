<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    $departmentId = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
    $fiscalYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    
    if ($departmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid department ID']);
        exit;
    }
    
    $totalBalance = 0;
    $utilizationCount = 0;
    
    // First, try to get data from utilization_summaries table
    $foundSummary = false;
    $checkSummaryTable = $conn->query("SHOW TABLES LIKE 'utilization_summaries'");
    if ($checkSummaryTable->rowCount() > 0) {
        $summaryStmt = $conn->prepare("
            SELECT totals, fiscal_year
            FROM utilization_summaries 
            WHERE department_id = ? AND fiscal_year = ?
            ORDER BY updated_at DESC, created_at DESC
            LIMIT 1
        ");
        $summaryStmt->execute([$departmentId, $fiscalYear]);
        $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($summaryData && $summaryData['totals']) {
            $totals = json_decode($summaryData['totals'], true);
            if ($totals && isset($totals['totalBalance'])) {
                $totalBalance = floatval($totals['totalBalance']);
                $foundSummary = true;
            }
            // Count entries from the totals data (if available)
            if ($totals && isset($totals['entries']) && is_array($totals['entries'])) {
                $utilizationCount = count($totals['entries']);
            }
        }
    }
    
    // Always get the count from budget_utilization_entries table (more reliable)
    $checkTable = $conn->query("SHOW TABLES LIKE 'budget_utilization_entries'");
    if ($checkTable->rowCount() > 0) {
        $countStmt = $conn->prepare("
            SELECT COUNT(*) as entry_count
            FROM budget_utilization_entries 
            WHERE department_id = ? AND fiscal_year = ?
        ");
        $countStmt->execute([$departmentId, $fiscalYear]);
        $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
        if ($countData) {
            $utilizationCount = intval($countData['entry_count']);
        }
        
        // If no summary was found, also get the balance from entries
        if (!$foundSummary) {
            $balanceStmt = $conn->prepare("
                SELECT COALESCE(SUM(CAST(total_balance AS DECIMAL(15,2))), 0) as total_balance 
                FROM budget_utilization_entries 
                WHERE department_id = ? AND fiscal_year = ?
            ");
            $balanceStmt->execute([$departmentId, $fiscalYear]);
            $balanceData = $balanceStmt->fetch(PDO::FETCH_ASSOC);
            if ($balanceData) {
                $totalBalance = floatval($balanceData['total_balance']);
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'amount' => $totalBalance,
        'count' => $utilizationCount,
        'fiscal_year' => $fiscalYear
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching utilization: ' . $e->getMessage()
    ]);
}
