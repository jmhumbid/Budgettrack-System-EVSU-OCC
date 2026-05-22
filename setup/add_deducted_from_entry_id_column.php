<?php
/**
 * Script to add deducted_from_entry_id column to utilization_purchase_requests table
 * Run this to add the missing column to your database
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Add deducted_from_entry_id Column</title>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";
    echo "</head><body>";
    echo "<h2>Adding deducted_from_entry_id Column</h2>";
    echo "<pre>";
    
    // Check if column already exists
    echo "=== Checking if column exists ===\n";
    $columnCheck = $db->query("SHOW COLUMNS FROM utilization_purchase_requests LIKE 'deducted_from_entry_id'");
    if ($columnCheck->rowCount() > 0) {
        echo "✓ deducted_from_entry_id column already exists\n";
        echo "  No changes needed.\n";
    } else {
        echo "⚠ deducted_from_entry_id column does NOT exist\n";
        echo "  Adding column...\n";
        
        try {
            // Add the column
            $db->exec("ALTER TABLE `utilization_purchase_requests` 
                ADD COLUMN `deducted_from_entry_id` INT(11) DEFAULT NULL 
                AFTER `amount`");
            echo "✓ Column added successfully!\n";
            
            // Add index for better performance
            try {
                $db->exec("ALTER TABLE `utilization_purchase_requests` 
                    ADD INDEX `idx_deducted_from` (`deducted_from_entry_id`)");
                echo "✓ Index added successfully!\n";
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key') === false) {
                    echo "⚠ Warning: Could not add index: " . $e->getMessage() . "\n";
                } else {
                    echo "✓ Index already exists\n";
                }
            }
            
        } catch (PDOException $e) {
            echo "❌ Error adding column: " . $e->getMessage() . "\n";
            exit;
        }
    }
    
    // Now check if foreign key constraint exists
    echo "\n=== Checking foreign key constraint ===\n";
    $fkCheck = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'utilization_purchase_requests'
        AND COLUMN_NAME = 'deducted_from_entry_id'
        AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
        AND REFERENCED_COLUMN_NAME = 'id'
    ");
    
    if ($fkCheck->rowCount() > 0) {
        echo "✓ Foreign key constraint already exists\n";
    } else {
        echo "⚠ Foreign key constraint does NOT exist\n";
        echo "  Adding foreign key constraint...\n";
        
        try {
            $db->exec("ALTER TABLE `utilization_purchase_requests` 
                ADD CONSTRAINT `fk_pr_entry` FOREIGN KEY (`deducted_from_entry_id`) 
                REFERENCES `budget_utilization_entries` (`id`) ON DELETE SET NULL");
            echo "✓ Foreign key constraint added successfully!\n";
        } catch (PDOException $e) {
            echo "⚠ Warning: Could not add foreign key: " . $e->getMessage() . "\n";
            echo "  This might be due to existing data that violates the constraint.\n";
            echo "  You may need to clean up data first or add the constraint manually.\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✓ Column structure updated\n";
    echo "✓ Ready to use deducted_from_entry_id in purchase requests\n";
    
    echo "\n✅ Setup complete!\n";
    echo "</pre>";
    echo "<p style='color: green; font-weight: bold;'>Column added successfully!</p>";
    echo "<p>You can now use the purchase request system with automatic deductions.</p>";
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<pre>";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "</pre>";
    echo "<p style='color: red;'>Please check the error above and fix any issues.</p>";
}





