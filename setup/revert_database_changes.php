<?php
/**
 * Script to revert all database changes:
 * 1. Remove columns from budget_utilization_entries
 * 2. Drop stored procedure
 * 3. Drop all triggers
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Revert Database Changes</title>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; }</style>";
    echo "</head><body>";
    echo "<h2>Reverting Database Changes</h2>";
    echo "<pre>";
    
    // ============================================
    // 1. Drop all triggers
    // ============================================
    echo "\n=== Dropping Triggers ===\n";
    
    $triggers = [
        'pr_after_insert_deduction',
        'pr_after_update_deduction',
        'pr_after_delete_deduction',
        'travel_after_insert_deduction',
        'travel_after_update_deduction',
        'travel_after_delete_deduction',
        'honoraria_after_insert_deduction',
        'honoraria_after_update_deduction',
        'honoraria_after_delete_deduction'
    ];
    
    foreach ($triggers as $trigger) {
        try {
            $db->exec("DROP TRIGGER IF EXISTS $trigger");
            echo "✓ Dropped trigger: $trigger\n";
        } catch (PDOException $e) {
            echo "⚠ Error dropping trigger $trigger: " . $e->getMessage() . "\n";
        }
    }
    
    // ============================================
    // 2. Drop stored procedure
    // ============================================
    echo "\n=== Dropping Stored Procedure ===\n";
    
    try {
        $db->exec("DROP PROCEDURE IF EXISTS update_utilization_deductions");
        echo "✓ Dropped stored procedure: update_utilization_deductions\n";
    } catch (PDOException $e) {
        echo "⚠ Error dropping stored procedure: " . $e->getMessage() . "\n";
    }
    
    // ============================================
    // 3. Remove columns from budget_utilization_entries
    // ============================================
    echo "\n=== Removing Columns from budget_utilization_entries ===\n";
    
    // Check if is_deducted column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'is_deducted'");
    if ($checkColumn->rowCount() > 0) {
        try {
            $db->exec("ALTER TABLE budget_utilization_entries DROP COLUMN is_deducted");
            echo "✓ Removed is_deducted column\n";
        } catch (PDOException $e) {
            echo "⚠ Error removing is_deducted column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ is_deducted column does not exist\n";
    }
    
    // Check if deducted_from_entry_id column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'deducted_from_entry_id'");
    if ($checkColumn->rowCount() > 0) {
        try {
            $db->exec("ALTER TABLE budget_utilization_entries DROP COLUMN deducted_from_entry_id");
            echo "✓ Removed deducted_from_entry_id column\n";
        } catch (PDOException $e) {
            echo "⚠ Error removing deducted_from_entry_id column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ deducted_from_entry_id column does not exist\n";
    }
    
    // ============================================
    // Summary
    // ============================================
    echo "\n=== Summary ===\n";
    echo "✓ Dropped all triggers\n";
    echo "✓ Dropped stored procedure\n";
    echo "✓ Removed columns from budget_utilization_entries\n";
    echo "\n✅ Database changes reverted successfully!\n";
    echo "\nNote: The deducted_from_entry_id and is_deducted columns in\n";
    echo "      utilization_travels, utilization_honoraria, and utilization_purchase_requests\n";
    echo "      remain unchanged (they were already there).\n";
    
    echo "</pre>";
    echo "<p><strong>✅ Reversion completed successfully!</strong></p>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "</pre>";
    echo "<p class='error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
} catch (Exception $e) {
    echo "</pre>";
    echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}

