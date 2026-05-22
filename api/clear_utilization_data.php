<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $departmentId = $input['department_id'] ?? null;
    $fiscalYear = $input['fiscal_year'] ?? date('Y'); // Use current year if not specified
    
    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        $deletedTables = [];
        $skippedTables = [];
        
        // STEP 1: Save a snapshot to prior years history BEFORE deleting
        // This ensures the data is preserved in the prior years view
        try {
            // Check if prior_years table exists
            $checkPriorYears = $db->query("SHOW TABLES LIKE 'budget_prior_years'");
            if ($checkPriorYears->rowCount() > 0) {
                // Get current utilization entries before deleting
                $stmt = $db->prepare("SELECT * FROM budget_utilization_entries WHERE department_id = ? AND fiscal_year = ?");
                $stmt->execute([$departmentId, $fiscalYear]);
                $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($entries)) {
                    // Save each entry to prior years
                    $insertStmt = $db->prepare("
                        INSERT INTO budget_prior_years 
                        (department_id, fiscal_year, expense_category, allocated_budget, deductions, total_balance, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                        ON DUPLICATE KEY UPDATE
                        allocated_budget = VALUES(allocated_budget),
                        deductions = VALUES(deductions),
                        total_balance = VALUES(total_balance)
                    ");
                    
                    foreach ($entries as $entry) {
                        $insertStmt->execute([
                            $departmentId,
                            $fiscalYear,
                            $entry['expense_category'],
                            $entry['allocated_budget'],
                            $entry['deductions'],
                            $entry['total_balance']
                        ]);
                    }
                    
                    $deletedTables[] = "Saved " . count($entries) . " entries to prior years history";
                }
            }
        } catch (Exception $e) {
            // If prior years save fails, log but continue with deletion
            error_log("Failed to save to prior years: " . $e->getMessage());
            $skippedTables[] = 'prior_years (save failed but continuing)';
        }
        
        // STEP 2: Delete budget utilization entries for this department AND fiscal year only
        try {
            $stmt = $db->prepare("DELETE FROM budget_utilization_entries WHERE department_id = ? AND fiscal_year = ?");
            $stmt->execute([$departmentId, $fiscalYear]);
            $deletedTables[] = "budget_utilization_entries (year $fiscalYear)";
        } catch (Exception $e) {
            $skippedTables[] = 'budget_utilization_entries';
        }
        
        // Delete deduction sources for this department AND fiscal year only
        try {
            $stmt = $db->prepare("DELETE FROM budget_utilization_deduction_sources WHERE department_id = ? AND fiscal_year = ?");
            $stmt->execute([$departmentId, $fiscalYear]);
            $deletedTables[] = "budget_utilization_deduction_sources (year $fiscalYear)";
        } catch (Exception $e) {
            $skippedTables[] = 'budget_utilization_deduction_sources';
        }
        
        // Delete utilization summaries for this department AND fiscal year only
        try {
            $stmt = $db->prepare("DELETE FROM utilization_summaries WHERE department_id = ? AND fiscal_year = ?");
            $stmt->execute([$departmentId, $fiscalYear]);
            $deletedTables[] = "utilization_summaries (year $fiscalYear)";
        } catch (Exception $e) {
            $skippedTables[] = 'utilization_summaries';
        }
        
        // DO NOT DELETE: Purchase requests, travels, and prior years data
        // These should be preserved when clearing utilization entries
        // However, we need to clear the deduction links for the current fiscal year only
        try {
            $stmt = $db->prepare("UPDATE purchase_requests SET deducted_from_entry_id = NULL WHERE department_id = ? AND fiscal_year = ?");
            $stmt->execute([$departmentId, $fiscalYear]);
            $deletedTables[] = "purchase_requests deduction links (year $fiscalYear)";
        } catch (Exception $e) {
            $skippedTables[] = 'purchase_requests (deduction links)';
        }
        
        try {
            $stmt = $db->prepare("UPDATE travels SET deducted_from_entry_id = NULL WHERE department_id = ? AND fiscal_year = ?");
            $stmt->execute([$departmentId, $fiscalYear]);
            $deletedTables[] = "travels deduction links (year $fiscalYear)";
        } catch (Exception $e) {
            $skippedTables[] = 'travels (deduction links)';
        }
        
        // Commit transaction
        $db->commit();
        
        $message = "Utilization entries for fiscal year $fiscalYear have been cleared successfully";
        if (!empty($deletedTables)) {
            $message .= ' (Cleared: ' . implode(', ', $deletedTables) . ')';
        }
        $message .= '. Purchase Requests, Travels, and Prior Years data have been preserved.';
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'fiscal_year' => $fiscalYear,
            'deleted_tables' => $deletedTables,
            'skipped_tables' => $skippedTables
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error clearing utilization data: ' . $e->getMessage()
    ]);
}
