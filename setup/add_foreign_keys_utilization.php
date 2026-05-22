<?php
/**
 * Setup script to add foreign key constraints for utilization tables
 * This ensures referential integrity between budget_utilization_entries and the three utilization tables
 * Access via: http://localhost/budgettrack/setup/add_foreign_keys_utilization.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Add Foreign Keys for Utilization Tables</title></head><body>";
    echo "<h2>Adding Foreign Key Constraints for Utilization Tables</h2>";
    echo "<pre>";
    
    // Check if tables exist first
    $tables = ['budget_utilization_entries', 'utilization_honoraria', 'utilization_purchase_requests', 'utilization_travels'];
    foreach ($tables as $table) {
        try {
            $check = $db->query("SHOW TABLES LIKE '$table'");
            if ($check->rowCount() === 0) {
                echo "⚠ Table $table does not exist. Please run create_utilization_tables.php first.\n";
                echo "</pre>";
                echo "<p style='color: red;'>Please create the tables first before adding foreign keys.</p>";
                echo "</body></html>";
                exit;
            }
        } catch (Exception $e) {
            echo "⚠ Error checking table $table: " . $e->getMessage() . "\n";
        }
    }
    
    // 1. Add foreign key for utilization_honoraria.deducted_from_entry_id
    try {
        // Check if column exists
        $checkColumn = $db->query("SHOW COLUMNS FROM utilization_honoraria LIKE 'deducted_from_entry_id'");
        if ($checkColumn && $checkColumn->rowCount() > 0) {
            // Check if foreign key already exists
            $checkFK = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'utilization_honoraria' 
                AND COLUMN_NAME = 'deducted_from_entry_id'
                AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
            ");
            
            if ($checkFK->rowCount() === 0) {
                $db->exec("
                    ALTER TABLE utilization_honoraria 
                    ADD CONSTRAINT fk_honoraria_entry 
                    FOREIGN KEY (deducted_from_entry_id) 
                    REFERENCES budget_utilization_entries(id) 
                    ON DELETE SET NULL 
                    ON UPDATE CASCADE
                ");
                echo "✓ Added foreign key for utilization_honoraria.deducted_from_entry_id\n";
            } else {
                echo "⚠ Foreign key for utilization_honoraria.deducted_from_entry_id already exists\n";
            }
        } else {
            echo "⚠ Column deducted_from_entry_id does not exist in utilization_honoraria\n";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false && 
            strpos($e->getMessage(), 'already exists') === false) {
            echo "⚠ Error adding foreign key for utilization_honoraria: " . $e->getMessage() . "\n";
        } else {
            echo "⚠ Foreign key for utilization_honoraria already exists\n";
        }
    }
    
    // 2. Add foreign key for utilization_purchase_requests.deducted_from_entry_id
    try {
        // Check if column exists
        $checkColumn = $db->query("SHOW COLUMNS FROM utilization_purchase_requests LIKE 'deducted_from_entry_id'");
        if ($checkColumn && $checkColumn->rowCount() > 0) {
            // Check if foreign key already exists
            $checkFK = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'utilization_purchase_requests' 
                AND COLUMN_NAME = 'deducted_from_entry_id'
                AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
            ");
            
            if ($checkFK->rowCount() === 0) {
                $db->exec("
                    ALTER TABLE utilization_purchase_requests 
                    ADD CONSTRAINT fk_pr_entry 
                    FOREIGN KEY (deducted_from_entry_id) 
                    REFERENCES budget_utilization_entries(id) 
                    ON DELETE SET NULL 
                    ON UPDATE CASCADE
                ");
                echo "✓ Added foreign key for utilization_purchase_requests.deducted_from_entry_id\n";
            } else {
                echo "⚠ Foreign key for utilization_purchase_requests.deducted_from_entry_id already exists\n";
            }
        } else {
            echo "⚠ Column deducted_from_entry_id does not exist in utilization_purchase_requests\n";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false && 
            strpos($e->getMessage(), 'already exists') === false) {
            echo "⚠ Error adding foreign key for utilization_purchase_requests: " . $e->getMessage() . "\n";
        } else {
            echo "⚠ Foreign key for utilization_purchase_requests already exists\n";
        }
    }
    
    // 3. Add foreign key for utilization_travels - check both deducted_from_entry_id and entry_id
    try {
        // First check for deducted_from_entry_id column
        $checkDeductedFromColumn = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'deducted_from_entry_id'");
        $hasDeductedFromColumn = $checkDeductedFromColumn && $checkDeductedFromColumn->rowCount() > 0;
        
        // Check for entry_id column
        $checkEntryIdColumn = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'entry_id'");
        $hasEntryIdColumn = $checkEntryIdColumn && $checkEntryIdColumn->rowCount() > 0;
        
        if ($hasDeductedFromColumn) {
            // Check if foreign key already exists
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
                echo "✓ Added foreign key for utilization_travels.deducted_from_entry_id\n";
            } else {
                echo "⚠ Foreign key for utilization_travels.deducted_from_entry_id already exists\n";
            }
        }
        
        // Also add foreign key for entry_id if it exists (some databases might have both)
        if ($hasEntryIdColumn) {
            // Check if foreign key already exists for entry_id
            $checkFK = $db->query("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'utilization_travels' 
                AND COLUMN_NAME = 'entry_id'
                AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
            ");
            
            if ($checkFK->rowCount() === 0) {
                $db->exec("
                    ALTER TABLE utilization_travels 
                    ADD CONSTRAINT fk_travel_entry_id 
                    FOREIGN KEY (entry_id) 
                    REFERENCES budget_utilization_entries(id) 
                    ON DELETE SET NULL 
                    ON UPDATE CASCADE
                ");
                echo "✓ Added foreign key for utilization_travels.entry_id\n";
            } else {
                echo "⚠ Foreign key for utilization_travels.entry_id already exists\n";
            }
        }
        
        if (!$hasDeductedFromColumn && !$hasEntryIdColumn) {
            echo "⚠ Neither deducted_from_entry_id nor entry_id column exists in utilization_travels\n";
        }
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate foreign key') === false && 
            strpos($e->getMessage(), 'already exists') === false) {
            echo "⚠ Error adding foreign key for utilization_travels: " . $e->getMessage() . "\n";
        } else {
            echo "⚠ Foreign key for utilization_travels already exists\n";
        }
    }
    
    echo "\n✅ Foreign key constraints setup complete!\n";
    echo "</pre>";
    echo "<p style='color: green; font-weight: bold;'>Foreign keys have been added successfully!</p>";
    echo "<p><a href='../pages/utilization.php'>Go to Utilization Page</a></p>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "<pre>";
    echo "❌ Error adding foreign keys: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getCode() . "\n";
    echo "</pre>";
    echo "<p style='color: red;'>Please check your database connection and try again.</p>";
    echo "<p>Note: Make sure all tables exist and have the required columns before running this script.</p>";
}

