<?php
/**
 * Fix budget_utilization_entries table structure
 * - Ensure id column has AUTO_INCREMENT
 * - Remove deducted_from_entry_id column if it exists (it shouldn't be here)
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Fix Budget Utilization Entries</title></head><body>";
    echo "<h2>Fixing budget_utilization_entries Table Structure</h2>";
    echo "<pre>";
    
    // Check if deducted_from_entry_id column exists (it shouldn't)
    $checkColumn = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'deducted_from_entry_id'");
    if ($checkColumn->rowCount() > 0) {
        echo "⚠ Found deducted_from_entry_id column in budget_utilization_entries (should not be here)\n";
        
        // First, find and drop any foreign key constraints that reference this column
        // Check both: constraints on this table and constraints from other tables that reference this column
        $fkQuery1 = $db->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'budget_utilization_entries' 
            AND COLUMN_NAME = 'deducted_from_entry_id'
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $foreignKeys1 = $fkQuery1->fetchAll(PDO::FETCH_ASSOC);
        
        // Also check for foreign keys from other tables that reference budget_utilization_entries.deducted_from_entry_id
        $fkQuery2 = $db->query("
            SELECT TABLE_NAME, CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND REFERENCED_TABLE_NAME = 'budget_utilization_entries' 
            AND REFERENCED_COLUMN_NAME = 'deducted_from_entry_id'
        ");
        $foreignKeys2 = $fkQuery2->fetchAll(PDO::FETCH_ASSOC);
        
        // Drop foreign keys on this table
        foreach ($foreignKeys1 as $fk) {
            $fkName = $fk['CONSTRAINT_NAME'];
            echo "Dropping foreign key constraint on budget_utilization_entries: $fkName\n";
            try {
                $db->exec("ALTER TABLE budget_utilization_entries DROP FOREIGN KEY $fkName");
                echo "✓ Dropped foreign key constraint: $fkName\n";
            } catch (Exception $e) {
                echo "⚠ Could not drop foreign key: " . $e->getMessage() . "\n";
            }
        }
        
        // Drop foreign keys from other tables that reference this column
        foreach ($foreignKeys2 as $fk) {
            $tableName = $fk['TABLE_NAME'];
            $fkName = $fk['CONSTRAINT_NAME'];
            echo "Dropping foreign key constraint on $tableName: $fkName\n";
            try {
                $db->exec("ALTER TABLE $tableName DROP FOREIGN KEY $fkName");
                echo "✓ Dropped foreign key constraint: $fkName from $tableName\n";
            } catch (Exception $e) {
                echo "⚠ Could not drop foreign key: " . $e->getMessage() . "\n";
            }
        }
        
        // Drop all indexes on this column
        $indexQuery = $db->query("SHOW INDEX FROM budget_utilization_entries WHERE Column_name = 'deducted_from_entry_id'");
        $indexes = $indexQuery->fetchAll(PDO::FETCH_ASSOC);
        foreach ($indexes as $idx) {
            $idxName = $idx['Key_name'];
            if ($idxName !== 'PRIMARY') {
                echo "Dropping index: $idxName\n";
                try {
                    $db->exec("ALTER TABLE budget_utilization_entries DROP INDEX $idxName");
                    echo "✓ Dropped index: $idxName\n";
                } catch (Exception $e) {
                    echo "⚠ Could not drop index: " . $e->getMessage() . "\n";
                }
            }
        }
        
        // Now remove the column
        echo "Removing deducted_from_entry_id column...\n";
        try {
            $db->exec("ALTER TABLE budget_utilization_entries DROP COLUMN deducted_from_entry_id");
            echo "✓ Removed deducted_from_entry_id column\n";
        } catch (Exception $e) {
            echo "✗ Error removing column: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ No deducted_from_entry_id column found (correct)\n";
    }
    
    // Check if id column has AUTO_INCREMENT
    $checkId = $db->query("SHOW COLUMNS FROM budget_utilization_entries WHERE Field = 'id'");
    $idColumn = $checkId->fetch(PDO::FETCH_ASSOC);
    
    if ($idColumn) {
        $extra = $idColumn['Extra'] ?? '';
        if (strpos($extra, 'auto_increment') === false) {
            echo "⚠ id column does not have AUTO_INCREMENT\n";
            echo "Adding AUTO_INCREMENT to id column...\n";
            try {
                // First, make sure it's a primary key
                $db->exec("ALTER TABLE budget_utilization_entries MODIFY id INT(11) NOT NULL AUTO_INCREMENT");
                echo "✓ Added AUTO_INCREMENT to id column\n";
            } catch (Exception $e) {
                echo "✗ Error adding AUTO_INCREMENT: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✓ id column has AUTO_INCREMENT (correct)\n";
        }
    }
    
    // Show current table structure
    echo "\nCurrent table structure:\n";
    $columns = $db->query("SHOW COLUMNS FROM budget_utilization_entries");
    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "  - {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " " . ($col['Extra'] ?? '') . "\n";
    }
    
    // Recreate correct foreign keys that reference budget_utilization_entries.id (not deducted_from_entry_id)
    echo "\nRecreating correct foreign key constraints...\n";
    
    // Check and recreate foreign key for utilization_purchase_requests
    $checkPrFk = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'utilization_purchase_requests' 
        AND COLUMN_NAME = 'deducted_from_entry_id'
        AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
        AND REFERENCED_COLUMN_NAME = 'id'
    ");
    if ($checkPrFk->rowCount() == 0) {
        try {
            $db->exec("ALTER TABLE utilization_purchase_requests 
                ADD CONSTRAINT fk_pr_entry 
                FOREIGN KEY (deducted_from_entry_id) 
                REFERENCES budget_utilization_entries(id) 
                ON DELETE SET NULL");
            echo "✓ Recreated foreign key for utilization_purchase_requests\n";
        } catch (Exception $e) {
            echo "⚠ Could not recreate PR foreign key: " . $e->getMessage() . "\n";
        }
    }
    
    // Check and recreate foreign key for utilization_travels
    $checkTravelFk = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'utilization_travels' 
        AND COLUMN_NAME = 'deducted_from_entry_id'
        AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
        AND REFERENCED_COLUMN_NAME = 'id'
    ");
    if ($checkTravelFk->rowCount() == 0) {
        try {
            $db->exec("ALTER TABLE utilization_travels 
                ADD CONSTRAINT fk_travel_entry 
                FOREIGN KEY (deducted_from_entry_id) 
                REFERENCES budget_utilization_entries(id) 
                ON DELETE SET NULL");
            echo "✓ Recreated foreign key for utilization_travels\n";
        } catch (Exception $e) {
            echo "⚠ Could not recreate Travel foreign key: " . $e->getMessage() . "\n";
        }
    }
    
    // Check and recreate foreign key for utilization_honoraria
    $checkHonorariaFk = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'utilization_honoraria' 
        AND COLUMN_NAME = 'deducted_from_entry_id'
        AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
        AND REFERENCED_COLUMN_NAME = 'id'
    ");
    if ($checkHonorariaFk->rowCount() == 0) {
        try {
            $db->exec("ALTER TABLE utilization_honoraria 
                ADD CONSTRAINT fk_honoraria_entry 
                FOREIGN KEY (deducted_from_entry_id) 
                REFERENCES budget_utilization_entries(id) 
                ON DELETE SET NULL");
            echo "✓ Recreated foreign key for utilization_honoraria\n";
        } catch (Exception $e) {
            echo "⚠ Could not recreate Honoraria foreign key: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n✓ Table structure fixed!\n";
    echo "</pre></body></html>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

