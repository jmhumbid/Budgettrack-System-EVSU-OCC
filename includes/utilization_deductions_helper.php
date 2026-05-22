<?php
/**
 * Helper functions for automatically calculating and syncing deductions
 * in budget_utilization_entries based on amounts from:
 * - utilization_honoraria
 * - utilization_purchase_requests
 * - utilization_travels
 */

/**
 * Recalculate deductions for a specific budget_utilization_entries entry
 * This function sums all amounts from the three utilization tables
 * that reference this deducted_from_entry_id
 * 
 * @param PDO $db Database connection
 * @param int $deducted_from_entry_id The deducted_from_entry_id value from budget_utilization_entries
 * @return bool True on success, false on failure
 */
function recalculateDeductionsForEntry($db, $deducted_from_entry_id) {
    try {
        // Find the entry by deducted_from_entry_id and get its allocated budget and id
        $stmt = $db->prepare("SELECT id, allocated_budget FROM budget_utilization_entries WHERE deducted_from_entry_id = :deducted_from_entry_id");
        $stmt->execute([':deducted_from_entry_id' => $deducted_from_entry_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            error_log("recalculateDeductionsForEntry: Entry with deducted_from_entry_id $deducted_from_entry_id not found");
            return false;
        }
        
        $entryId = (int)$entry['id']; // Use id for the UPDATE query
        $allocatedBudget = (float)$entry['allocated_budget'];
        
        // Sum amounts from utilization_honoraria
        $honorariaSum = 0;
        try {
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM utilization_honoraria 
                WHERE deducted_from_entry_id = :deducted_from_entry_id
            ");
            $stmt->execute([':deducted_from_entry_id' => $deducted_from_entry_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $honorariaSum = (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error calculating honoraria sum: " . $e->getMessage());
        }
        
        // Sum amounts from utilization_purchase_requests
        $prSum = 0;
        try {
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM utilization_purchase_requests 
                WHERE deducted_from_entry_id = :deducted_from_entry_id
            ");
            $stmt->execute([':deducted_from_entry_id' => $deducted_from_entry_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $prSum = (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error calculating purchase request sum: " . $e->getMessage());
        }
        
        // Sum amounts from utilization_travels
        $travelsSum = 0;
        try {
            $stmt = $db->prepare("
                SELECT COALESCE(SUM(amount), 0) as total 
                FROM utilization_travels 
                WHERE deducted_from_entry_id = :deducted_from_entry_id
            ");
            $stmt->execute([':deducted_from_entry_id' => $deducted_from_entry_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $travelsSum = (float)($result['total'] ?? 0);
        } catch (Exception $e) {
            error_log("Error calculating travels sum: " . $e->getMessage());
        }
        
        // Calculate total deductions
        $totalDeductions = $honorariaSum + $prSum + $travelsSum;
        $totalBalance = $allocatedBudget - $totalDeductions;
        
        // Update the entry using id (primary key)
        $updateStmt = $db->prepare("
            UPDATE budget_utilization_entries 
            SET deductions = :deductions,
                total_balance = :balance
            WHERE id = :entry_id
        ");
        $result = $updateStmt->execute([
            ':deductions' => $totalDeductions,
            ':balance' => $totalBalance,
            ':entry_id' => $entryId
        ]);
        
        if ($result) {
            error_log("✓ Recalculated deductions for deducted_from_entry_id $deducted_from_entry_id (id=$entryId): Honoraria=$honorariaSum, PR=$prSum, Travels=$travelsSum, Total=$totalDeductions");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error recalculating deductions for entry $entry_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Recalculate deductions for multiple entries
 * 
 * @param PDO $db Database connection
 * @param array $entry_ids Array of entry IDs to recalculate
 * @return void
 */
function recalculateDeductionsForEntries($db, $entry_ids) {
    foreach ($entry_ids as $entry_id) {
        recalculateDeductionsForEntry($db, $entry_id);
    }
}

/**
 * Recalculate deductions for all entries in a department and fiscal year
 * 
 * @param PDO $db Database connection
 * @param int $department_id Department ID
 * @param int|string $fiscal_year Fiscal year
 * @return void
 */
function recalculateDeductionsForDepartment($db, $department_id, $fiscal_year) {
    try {
        // Get all deducted_from_entry_id values for this department and fiscal year
        // We need to recalculate based on deducted_from_entry_id, not id
        $stmt = $db->prepare("
            SELECT DISTINCT deducted_from_entry_id 
            FROM budget_utilization_entries 
            WHERE department_id = :dept_id AND fiscal_year = :year AND deducted_from_entry_id IS NOT NULL
        ");
        $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($entries as $entry) {
            if ($entry['deducted_from_entry_id']) {
                recalculateDeductionsForEntry($db, $entry['deducted_from_entry_id']);
            }
        }
    } catch (Exception $e) {
        error_log("Error recalculating deductions for department $department_id: " . $e->getMessage());
    }
}

