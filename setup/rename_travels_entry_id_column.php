<?php
/**
 * Setup script to rename entry_id column to deducted_from_entry_id in utilization_travels table
 * This ensures consistency across all utilization tables
 * Access via: http://localhost/budgettrack/setup/rename_travels_entry_id_column.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Rename Travels Entry ID Column</title></head><body>";
    echo "<h2>Renaming entry_id to deducted_from_entry_id in utilization_travels</h2>";
    echo "<pre>";
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'utilization_travels'");
    if ($checkTable->rowCount() === 0) {
        echo "❌ Table utilization_travels does not exist.\n";
        echo "</pre>";
        echo "<p style='color: red;'>Please create the table first.</p>";
        echo "</body></html>";
        exit;
    }
    
    // Check if entry_id column exists
    $checkEntryIdColumn = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'entry_id'");
    $hasEntryIdColumn = $checkEntryIdColumn && $checkEntryIdColumn->rowCount() > 0;
    
    // Check if deducted_from_entry_id column already exists
    $checkDeductedFromColumn = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'deducted_from_entry_id'");
    $hasDeductedFromColumn = $checkDeductedFromColumn && $checkDeductedFromColumn->rowCount() > 0;
    
    if ($hasEntryIdColumn && !$hasDeductedFromColumn) {
        echo "Found entry_id column, renaming to deducted_from_entry_id...\n";
        
        // Drop existing foreign key if it exists (for entry_id)
        try {
            $checkFK = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'utilization_travels' 
                AND COLUMN_NAME = 'entry_id'
                AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
            ");
            
            if ($checkFK->rowCount() > 0) {
                $fkRow = $checkFK->fetch(PDO::FETCH_ASSOC);
                $fkName = $fkRow['CONSTRAINT_NAME'];
                $db->exec("ALTER TABLE utilization_travels DROP FOREIGN KEY `$fkName`");
                echo "✓ Dropped existing foreign key: $fkName\n";
            }
        } catch (PDOException $e) {
            echo "⚠ Could not drop foreign key (may not exist): " . $e->getMessage() . "\n";
        }
        
        // Rename the column
        try {
            $db->exec("
                ALTER TABLE utilization_travels 
                CHANGE COLUMN entry_id deducted_from_entry_id INT(11) DEFAULT NULL
            ");
            echo "✓ Successfully renamed entry_id to deducted_from_entry_id\n";
        } catch (PDOException $e) {
            echo "❌ Error renaming column: " . $e->getMessage() . "\n";
            echo "</pre>";
            echo "<p style='color: red;'>Failed to rename column. Please check the error above.</p>";
            echo "</body></html>";
            exit;
        }
        
        // Add foreign key constraint with new column name
        try {
            $checkFK = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'utilization_travels' 
                AND COLUMN_NAME = 'deducted_from_entry_id'
                AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
            ");
            
            if ($checkFK->rowCount() === 0) {
                $db->exec("
                    ALTER TABLE utilization_travels 
                    ADD CONSTRAINT fk_travel_entry 
                    FOREIGN KEY (deducted_from_entry_id) 
                    REFERENCES budget_utilization_entries(id) 
                    ON DELETE SET NULL 
                    ON UPDATE CASCADE
                ");
                echo "✓ Added foreign key constraint for deducted_from_entry_id\n";
            } else {
                echo "⚠ Foreign key already exists for deducted_from_entry_id\n";
            }
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate foreign key') === false) {
                echo "⚠ Error adding foreign key: " . $e->getMessage() . "\n";
            } else {
                echo "⚠ Foreign key already exists\n";
            }
        }
        
        echo "\n✅ Column rename completed successfully!\n";
        echo "✓ entry_id has been renamed to deducted_from_entry_id\n";
        echo "✓ Foreign key constraint has been added\n";
        
    } elseif ($hasDeductedFromColumn) {
        echo "✓ deducted_from_entry_id column already exists\n";
        
        // If entry_id also exists, we might want to merge data or drop entry_id
        if ($hasEntryIdColumn) {
            echo "⚠ Both entry_id and deducted_from_entry_id exist\n";
            echo "  Checking if entry_id has data that needs to be migrated...\n";
            
            $checkData = $db->query("
                SELECT COUNT(*) as count 
                FROM utilization_travels 
                WHERE entry_id IS NOT NULL AND deducted_from_entry_id IS NULL
            ");
            $dataRow = $checkData->fetch(PDO::FETCH_ASSOC);
            $count = $dataRow['count'];
            
            if ($count > 0) {
                echo "  Found $count rows with entry_id but no deducted_from_entry_id\n";
                echo "  Migrating data...\n";
                
                $db->exec("
                    UPDATE utilization_travels 
                    SET deducted_from_entry_id = entry_id 
                    WHERE entry_id IS NOT NULL AND deducted_from_entry_id IS NULL
                ");
                echo "✓ Migrated $count rows from entry_id to deducted_from_entry_id\n";
            }
            
            // Drop entry_id column after migration
            try {
                // Drop foreign key if exists
                $checkFK = $db->query("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'utilization_travels' 
                    AND COLUMN_NAME = 'entry_id'
                    AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
                ");
                
                if ($checkFK->rowCount() > 0) {
                    $fkRow = $checkFK->fetch(PDO::FETCH_ASSOC);
                    $fkName = $fkRow['CONSTRAINT_NAME'];
                    $db->exec("ALTER TABLE utilization_travels DROP FOREIGN KEY `$fkName`");
                    echo "✓ Dropped foreign key for entry_id\n";
                }
                
                $db->exec("ALTER TABLE utilization_travels DROP COLUMN entry_id");
                echo "✓ Dropped entry_id column\n";
            } catch (PDOException $e) {
                echo "⚠ Could not drop entry_id column: " . $e->getMessage() . "\n";
            }
        }
        
    } else {
        echo "⚠ entry_id column does not exist in utilization_travels\n";
        echo "  The column may have already been renamed or never existed.\n";
    }
    
    echo "</pre>";
    echo "<p style='color: green; font-weight: bold;'>✅ Column rename process completed!</p>";
    echo "<p><a href='../pages/utilization.php'>Go to Utilization Page</a></p>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "</pre>";
    echo "<p style='color: red;'>Please check your database connection and try again.</p>";
}

