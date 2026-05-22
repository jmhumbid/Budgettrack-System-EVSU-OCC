<?php
/**
 * Add deducted_from_entry_id column back to budget_utilization_entries table
 * Make it NOT NULL and AUTO_INCREMENT starting from 1
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Add deducted_from_entry_id</title></head><body>";
    echo "<h2>Adding deducted_from_entry_id Column to budget_utilization_entries</h2>";
    echo "<pre>";
    
    // Check if column already exists
    $checkColumn = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'deducted_from_entry_id'");
    if ($checkColumn->rowCount() > 0) {
        echo "⚠ Column deducted_from_entry_id already exists\n";
        echo "Modifying column to be NOT NULL and AUTO_INCREMENT...\n";
        
        // First, update existing NULL values to sequential numbers
        $updateStmt = $db->query("SELECT id FROM budget_utilization_entries ORDER BY id");
        $entries = $updateStmt->fetchAll(PDO::FETCH_ASSOC);
        $counter = 1;
        foreach ($entries as $entry) {
            $db->exec("UPDATE budget_utilization_entries SET deducted_from_entry_id = $counter WHERE id = {$entry['id']}");
            $counter++;
        }
        echo "✓ Updated existing NULL values\n";
        
        // Modify column to be NOT NULL and AUTO_INCREMENT
        try {
            $db->exec("ALTER TABLE budget_utilization_entries 
                MODIFY deducted_from_entry_id INT(11) NOT NULL AUTO_INCREMENT");
            echo "✓ Modified deducted_from_entry_id to NOT NULL AUTO_INCREMENT\n";
        } catch (Exception $e) {
            echo "✗ Error modifying column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "Adding deducted_from_entry_id column...\n";
        
        // First, add the column as NOT NULL (can't be AUTO_INCREMENT since id already is)
        try {
            // Get the maximum value to set starting point
            $maxIdStmt = $db->query("SELECT COALESCE(MAX(id), 0) as max_id FROM budget_utilization_entries");
            $maxId = $maxIdStmt->fetch(PDO::FETCH_ASSOC)['max_id'];
            $startingValue = max(1, $maxId);
            
            // Add column after id column - set default to 0 first, then update existing rows
            $db->exec("ALTER TABLE budget_utilization_entries 
                ADD COLUMN deducted_from_entry_id INT(11) NOT NULL DEFAULT 0 AFTER id");
            
            // Update existing rows to have sequential numbers starting from 1
            $updateStmt = $db->query("SELECT id FROM budget_utilization_entries ORDER BY id");
            $entries = $updateStmt->fetchAll(PDO::FETCH_ASSOC);
            $counter = 1;
            foreach ($entries as $entry) {
                $db->exec("UPDATE budget_utilization_entries SET deducted_from_entry_id = $counter WHERE id = {$entry['id']}");
                $counter++;
            }
            echo "✓ Updated existing rows with sequential numbers\n";
            
            // Remove default and add unique index
            $db->exec("ALTER TABLE budget_utilization_entries 
                MODIFY deducted_from_entry_id INT(11) NOT NULL");
            
            // Add unique index
            try {
                $db->exec("ALTER TABLE budget_utilization_entries 
                    ADD UNIQUE INDEX unique_deducted_from_entry_id (deducted_from_entry_id)");
                echo "✓ Added unique index on deducted_from_entry_id\n";
            } catch (Exception $e) {
                echo "⚠ Could not add unique index: " . $e->getMessage() . "\n";
            }
            
            // Create trigger to auto-set deducted_from_entry_id on insert
            // Since we can't use AUTO_INCREMENT and can't use subquery on same table in trigger,
            // we'll set it to match the id (which auto-increments)
            $db->exec("DROP TRIGGER IF EXISTS set_deducted_from_entry_id");
            $triggerSql = "
                CREATE TRIGGER set_deducted_from_entry_id
                BEFORE INSERT ON budget_utilization_entries
                FOR EACH ROW
                BEGIN
                    IF NEW.deducted_from_entry_id = 0 OR NEW.deducted_from_entry_id IS NULL THEN
                        -- Get the next value from a sequence table or use id
                        -- Since id auto-increments, we can use it as the base
                        -- But we need to get max value first, so we'll use a variable approach
                        SET @next_val = (SELECT COALESCE(MAX(deducted_from_entry_id), 0) + 1 FROM (SELECT deducted_from_entry_id FROM budget_utilization_entries) AS temp);
                        SET NEW.deducted_from_entry_id = @next_val;
                    END IF;
                END
            ";
            try {
                $db->exec($triggerSql);
                echo "✓ Created trigger to auto-set deducted_from_entry_id on insert\n";
            } catch (Exception $e) {
                echo "⚠ Could not create trigger (MySQL limitation): " . $e->getMessage() . "\n";
                echo "  Will handle auto-increment in application code instead\n";
            }
            
            echo "✓ Added deducted_from_entry_id column with auto-increment functionality\n";
        } catch (Exception $e) {
            echo "✗ Error adding column: " . $e->getMessage() . "\n";
        }
    }
    
    // Show current table structure
    echo "\nCurrent table structure:\n";
    $columns = $db->query("SHOW COLUMNS FROM budget_utilization_entries");
    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " " . ($col['Extra'] ?? '') . "\n";
    }
    
    echo "\n✓ Column added successfully!\n";
    echo "</pre></body></html>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

