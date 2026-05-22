<?php
/**
 * Script to recalculate deductions in budget_utilization_entries from purchase requests
 * This ensures deductions are properly calculated and saved in the database
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Recalculate Deductions</title></head><body>";
    echo "<h2>Recalculating Deductions from Purchase Requests</h2>";
    echo "<pre>";
    
    // Get all purchase requests with amounts and deducted_from_entry_id
    $stmt = $db->query("
        SELECT deducted_from_entry_id, SUM(amount) as total_amount
        FROM utilization_purchase_requests
        WHERE deducted_from_entry_id IS NOT NULL AND amount > 0
        GROUP BY deducted_from_entry_id
    ");
    
    $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($deductions) . " entries to update\n\n";
    
    $db->beginTransaction();
    
    // First, reset all deductions to 0
    $db->exec("UPDATE budget_utilization_entries SET deductions = 0, total_balance = allocated_budget");
    echo "✓ Reset all deductions to 0\n";
    
    // Update deductions based on purchase requests
    foreach ($deductions as $deduction) {
        $entryId = $deduction['deducted_from_entry_id'];
        $totalAmount = $deduction['total_amount'];
        
        // Update the deduction and recalculate total_balance
        $updateStmt = $db->prepare("
            UPDATE budget_utilization_entries 
            SET deductions = :amount,
                total_balance = allocated_budget - :amount
            WHERE id = :entry_id
        ");
        $updateStmt->execute([':amount' => $totalAmount, ':entry_id' => $entryId]);
        
        echo "✓ Updated entry ID {$entryId} with deduction: " . number_format($totalAmount, 2) . "\n";
    }
    
    $db->commit();
    
    echo "\n✅ All deductions recalculated successfully!\n";
    echo "</pre>";
    echo "<p style='color: green; font-weight: bold;'>Recalculation complete!</p>";
    echo "</body></html>";
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo "<pre>";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "</pre>";
}





