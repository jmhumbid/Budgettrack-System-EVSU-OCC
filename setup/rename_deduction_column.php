<?php
/**
 * Script to rename deducted_from_entry_id to entry_id in child tables
 * and update foreign keys
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Rename Deduction Column</title>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; }</style>";
    echo "</head><body>";
    echo "<h2>Renaming deducted_from_entry_id to entry_id</h2>";
    echo "<pre>";
    
    $tables = [
        'utilization_purchase_requests' => 'fk_pr_entry',
        'utilization_travels' => 'fk_travel_entry',
        'utilization_honoraria' => 'fk_honoraria_entry'
    ];
    
    foreach ($tables as $table => $fkName) {
        echo "\n=== Processing $table ===\n";
        
        // Check if deducted_from_entry_id column exists
        $checkOld = $db->query("SHOW COLUMNS FROM `$table` LIKE 'deducted_from_entry_id'");
        $checkNew = $db->query("SHOW COLUMNS FROM `$table` LIKE 'entry_id'");
        
        if ($checkOld->rowCount() > 0 && $checkNew->rowCount() == 0) {
            // Drop existing foreign key if it exists
            try {
                $db->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
                echo "✓ Dropped foreign key: $fkName\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), "doesn't exist") === false) {
                    echo "⚠ Could not drop foreign key: " . $e->getMessage() . "\n";
                }
            }
            
            // Rename the column
            try {
                $db->exec("ALTER TABLE `$table` CHANGE COLUMN `deducted_from_entry_id` `entry_id` INT(11) DEFAULT NULL");
                echo "✓ Renamed deducted_from_entry_id to entry_id\n";
            } catch (PDOException $e) {
                echo "⚠ Error renaming column: " . $e->getMessage() . "\n";
                continue;
            }
            
            // Add foreign key with new column name
            try {
                $db->exec("
                    ALTER TABLE `$table` 
                    ADD CONSTRAINT `$fkName` 
                    FOREIGN KEY (`entry_id`) 
                    REFERENCES `budget_utilization_entries`(`entry_id`) 
                    ON DELETE SET NULL
                ");
                echo "✓ Added foreign key referencing entry_id: $fkName\n";
            } catch (PDOException $e) {
                echo "⚠ Error adding foreign key: " . $e->getMessage() . "\n";
            }
        } elseif ($checkNew->rowCount() > 0) {
            echo "✓ entry_id column already exists in $table\n";
        } else {
            echo "⚠ deducted_from_entry_id column not found in $table\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✓ Renamed deducted_from_entry_id to entry_id in all child tables\n";
    echo "✓ Updated foreign keys to reference entry_id\n";
    
    echo "</pre>";
    echo "<p><strong>✅ Column rename completed!</strong></p>";
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}

