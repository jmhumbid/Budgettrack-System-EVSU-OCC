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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['department_id']) || !isset($data['entries'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$department_id = $data['department_id'];
$entries = $data['entries'];
$deduction_sources = isset($data['deduction_sources']) ? $data['deduction_sources'] : [];
$fiscal_year = isset($data['fiscal_year']) ? $data['fiscal_year'] : date('Y');

try {
    $db = getDB();
    
    // Create deduction_sources table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS budget_utilization_deduction_sources (
        id INT PRIMARY KEY AUTO_INCREMENT,
        department_id INT NOT NULL,
        fiscal_year INT NOT NULL,
        entry_id VARCHAR(50) NOT NULL,
        category_name VARCHAR(255) NOT NULL,
        source_type VARCHAR(50) NOT NULL,
        amount DECIMAL(15,2) NOT NULL DEFAULT 0,
        source_entries JSON NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_dept_year (department_id, fiscal_year),
        INDEX idx_entry (entry_id)
    )");
    
    $db->beginTransaction();
    
    // Get existing entries to preserve deductions from purchase requests AND auto-filled flags
    $existingStmt = $db->prepare("
        SELECT id, expense_category, deductions, is_auto_filled, lib_id, account_code
        FROM budget_utilization_entries 
        WHERE department_id = :dept_id AND fiscal_year = :year
    ");
    $existingStmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    $existingEntries = $existingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a map of existing data by category
    $existingData = [];
    foreach ($existingEntries as $existing) {
        $existingData[$existing['expense_category']] = [
            'deductions' => (float)$existing['deductions'],
            'is_auto_filled' => (int)$existing['is_auto_filled'],
            'lib_id' => $existing['lib_id'],
            'account_code' => $existing['account_code']
        ];
    }
    
    // Delete existing entries for this department and fiscal year
    $stmt = $db->prepare("DELETE FROM budget_utilization_entries WHERE department_id = :dept_id AND fiscal_year = :year");
    $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    
    // Delete existing deduction sources for this department and fiscal year
    $stmt = $db->prepare("DELETE FROM budget_utilization_deduction_sources WHERE department_id = :dept_id AND fiscal_year = :year");
    $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    
    // Insert new entries (only if there are entries to insert)
    // deducted_from_entry_id will auto-increment starting from 1 FOR EACH FISCAL YEAR
    if (count($entries) > 0) {
        // Get the next deducted_from_entry_id value for THIS fiscal year (max + 1, starting from 1)
        // IMPORTANT: Scope to fiscal year to ensure each year has independent entry IDs
        $maxStmt = $db->prepare("SELECT COALESCE(MAX(deducted_from_entry_id), 0) as max_val FROM budget_utilization_entries WHERE fiscal_year = :year");
        $maxStmt->execute([':year' => $fiscal_year]);
        $maxResult = $maxStmt->fetch(PDO::FETCH_ASSOC);
        $nextDeductedFromEntryId = max(1, (int)$maxResult['max_val'] + 1);
        
        $stmt = $db->prepare("
            INSERT INTO budget_utilization_entries 
            (department_id, expense_category, account_code, allocated_budget, deductions, total_balance, fiscal_year, created_by, deducted_from_entry_id, is_auto_filled, lib_id)
            VALUES (:dept_id, :category, :account_code, :allocated, :deductions, :balance, :year, :user_id, :deducted_from_entry_id, :is_auto_filled, :lib_id)
        ");
        
        foreach ($entries as $entry) {
            $category = trim($entry['category'] ?? '');
            
            // Skip empty categories
            if (empty($category)) {
                continue;
            }
            
            $allocated = (float)($entry['allocated'] ?? 0);
            
            // Preserve existing data for auto-filled entries
            $existingInfo = $existingData[$category] ?? null;
            
            if ($existingInfo && $existingInfo['is_auto_filled']) {
                // This is an auto-filled entry - preserve all auto-filled data
                $accountCode = $existingInfo['account_code']; // Keep original account code
                $isAutoFilled = 1;
                $libId = $existingInfo['lib_id'];
                $deductions = $existingInfo['deductions']; // Keep existing deductions
            } else {
                // This is a manual entry - use provided data
                $accountCode = $entry['account_code'] ?? '';
                $isAutoFilled = 0;
                $libId = null;
                $deductions = (float)($entry['deductions'] ?? 0);
            }
            
            $balance = $allocated - $deductions;
            
            $stmt->execute([
                ':dept_id' => $department_id,
                ':category' => $category,
                ':account_code' => $accountCode,
                ':allocated' => $allocated,
                ':deductions' => $deductions,
                ':balance' => $balance,
                ':year' => $fiscal_year,
                ':user_id' => $user_id,
                ':deducted_from_entry_id' => $nextDeductedFromEntryId,
                ':is_auto_filled' => $isAutoFilled,
                ':lib_id' => $libId
            ]);
            
            $nextDeductedFromEntryId++; // Increment for next entry
        }
    }
    
    // Insert deduction sources
    if (count($deduction_sources) > 0) {
        $stmt = $db->prepare("
            INSERT INTO budget_utilization_deduction_sources 
            (department_id, fiscal_year, entry_id, category_name, source_type, amount, source_entries)
            VALUES (:dept_id, :year, :entry_id, :category_name, :source_type, :amount, :source_entries)
        ");
        
        foreach ($deduction_sources as $source) {
            $stmt->execute([
                ':dept_id' => $department_id,
                ':year' => $fiscal_year,
                ':entry_id' => $source['entry_id'],
                ':category_name' => $source['category_name'],
                ':source_type' => $source['source_type'],
                ':amount' => $source['amount'],
                ':source_entries' => json_encode($source['entries'])
            ]);
        }
    }
    
    // IMPORTANT: Do NOT recalculate deductions here when saving utilization entries
    // Deductions should only be recalculated when PR/Travel/Honoraria entries are saved
    // This ensures that deductions persist even if utilization entries are saved (e.g., when changing allocated budget)
    // Deductions are the source of truth from PR/Travel/Honoraria entries, not from utilization entries
    // Only recalculate if explicitly requested (which should not happen during normal utilization entry saves)
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Utilization entries saved successfully']);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving entries: ' . $e->getMessage()]);
}

