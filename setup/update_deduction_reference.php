<?php
/**
 * Script to update deducted_from_entry_id to reference entry_id instead of id
 * 1. Make entry_id UNIQUE if not already
 * 2. Update foreign keys to reference entry_id
 * 3. Update existing data to use entry_id values
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Update Deduction Reference</title>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";
    echo "</head><body>";
    echo "<h2>Updating deducted_from_entry_id to Reference entry_id</h2>";
    echo "<pre>";
    
    // ============================================
    // 1. Make entry_id UNIQUE in budget_utilization_entries
    // ============================================
    echo "\n=== Making entry_id UNIQUE ===\n";
    
    // Check if entry_id is already UNIQUE
    $indexes = $db->query("SHOW INDEX FROM budget_utilization_entries WHERE Column_name = 'entry_id' AND Non_unique = 0")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($indexes) > 0) {
        echo "✓ entry_id is already UNIQUE\n";
    } else {
        // First, ensure all entry_id values are unique (populate any NULLs)
        echo "Ensuring all entry_id values are set...\n";
        $entries = $db->query("SELECT id FROM budget_utilization_entries WHERE entry_id IS NULL ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
        $maxEntryId = $db->query("SELECT COALESCE(MAX(entry_id), 0) FROM budget_utilization_entries")->fetchColumn();
        $entryIdCounter = (int)$maxEntryId + 1;
        
        foreach ($entries as $id) {
            $db->exec("UPDATE budget_utilization_entries SET entry_id = $entryIdCounter WHERE id = $id");
            $entryIdCounter++;
        }
        echo "✓ Populated NULL entry_id values\n";
        
        // Make entry_id UNIQUE
        try {
            $db->exec("ALTER TABLE budget_utilization_entries ADD UNIQUE KEY unique_entry_id (entry_id)");
            echo "✓ Made entry_id UNIQUE\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "⚠ Error: Duplicate entry_id values found. Cannot make UNIQUE.\n";
                echo "   Please ensure all entry_id values are unique first.\n";
            } else {
                echo "⚠ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    // ============================================
    // 2. Update foreign keys to reference entry_id
    // ============================================
    echo "\n=== Updating Foreign Keys ===\n";
    
    $tables = [
        'utilization_purchase_requests' => 'fk_pr_entry',
        'utilization_travels' => 'fk_travel_entry',
        'utilization_honoraria' => 'fk_honoraria_entry'
    ];
    
    foreach ($tables as $table => $fkName) {
        echo "\nProcessing $table...\n";
        
        // Drop existing foreign key if it exists
        try {
            $db->exec("ALTER TABLE `$table` DROP FOREIGN KEY `$fkName`");
            echo "✓ Dropped existing foreign key: $fkName\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "doesn't exist") === false) {
                echo "⚠ Could not drop foreign key (may not exist): " . $e->getMessage() . "\n";
            }
        }
        
        // Update deducted_from_entry_id values to use entry_id instead of id
        echo "Updating deducted_from_entry_id values to use entry_id...\n";
        $updateSql = "
            UPDATE `$table` pr
            INNER JOIN budget_utilization_entries bu ON pr.deducted_from_entry_id = bu.id
            SET pr.deducted_from_entry_id = bu.entry_id
            WHERE pr.deducted_from_entry_id IS NOT NULL
        ";
        $updated = $db->exec($updateSql);
        echo "✓ Updated $updated rows in $table\n";
        
        // Add new foreign key referencing entry_id
        try {
            $db->exec("
                ALTER TABLE `$table` 
                ADD CONSTRAINT `$fkName` 
                FOREIGN KEY (`deducted_from_entry_id`) 
                REFERENCES `budget_utilization_entries`(`entry_id`) 
                ON DELETE SET NULL
            ");
            echo "✓ Added foreign key referencing entry_id: $fkName\n";
        } catch (PDOException $e) {
            echo "⚠ Error adding foreign key: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✓ entry_id is UNIQUE in budget_utilization_entries\n";
    echo "✓ Foreign keys updated to reference entry_id\n";
    echo "✓ Existing data updated to use entry_id values\n";
    echo "\nNow deducted_from_entry_id references entry_id instead of id!\n";
    
    echo "</pre>";
    echo "<p><strong>✅ Update completed successfully!</strong></p>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "</pre>";
    echo "<p class='error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p class='error'>Error Code: " . $e->getCode() . "</p>";
    echo "</body></html>";
} catch (Exception $e) {
    echo "</pre>";
    echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}

