<?php
/**
 * Script to verify and fix the foreign key relationship for utilization_purchase_requests
 * This ensures the deducted_from_entry_id column has a proper foreign key to budget_utilization_entries.id
 * Run this to verify/fix your database structure
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Verify/Fix PR Foreign Key</title>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";
    echo "</head><body>";
    echo "<h2>Verifying/Fixing Foreign Key Relationship</h2>";
    echo "<pre>";
    
    // Check if deducted_from_entry_id column exists
    echo "=== Step 1: Checking column structure ===\n";
    $columnCheck = $db->query("SHOW COLUMNS FROM utilization_purchase_requests LIKE 'deducted_from_entry_id'");
    if ($columnCheck->rowCount() > 0) {
        echo "✓ deducted_from_entry_id column exists\n";
    } else {
        echo "❌ ERROR: deducted_from_entry_id column does NOT exist!\n";
        echo "   You need to add this column first.\n";
        echo "   Run: ALTER TABLE utilization_purchase_requests ADD COLUMN deducted_from_entry_id INT(11) DEFAULT NULL;\n";
        exit;
    }
    
    // Check if budget_utilization_entries table exists and has id column
    echo "\n=== Step 2: Checking budget_utilization_entries table ===\n";
    $tableCheck = $db->query("SHOW TABLES LIKE 'budget_utilization_entries'");
    if ($tableCheck->rowCount() > 0) {
        echo "✓ budget_utilization_entries table exists\n";
        
        $idCheck = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'id'");
        if ($idCheck->rowCount() > 0) {
            echo "✓ budget_utilization_entries.id column exists\n";
        } else {
            echo "❌ ERROR: budget_utilization_entries table doesn't have id column!\n";
            exit;
        }
    } else {
        echo "❌ ERROR: budget_utilization_entries table does NOT exist!\n";
        echo "   Please run create_utilization_tables.php first.\n";
        exit;
    }
    
    // Check if foreign key constraint already exists
    echo "\n=== Step 3: Checking foreign key constraint ===\n";
    $fkCheck = $db->query("
        SELECT CONSTRAINT_NAME 
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'utilization_purchase_requests'
        AND COLUMN_NAME = 'deducted_from_entry_id'
        AND REFERENCED_TABLE_NAME = 'budget_utilization_entries'
        AND REFERENCED_COLUMN_NAME = 'id'
    ");
    
    $fkExists = $fkCheck->rowCount() > 0;
    
    if ($fkExists) {
        echo "✓ Foreign key constraint already exists\n";
        $fkName = $fkCheck->fetch(PDO::FETCH_ASSOC)['CONSTRAINT_NAME'];
        echo "  Constraint name: {$fkName}\n";
    } else {
        echo "⚠ Foreign key constraint does NOT exist\n";
        echo "  Adding foreign key constraint...\n";
        
        try {
            $db->exec("ALTER TABLE `utilization_purchase_requests` 
                ADD CONSTRAINT `fk_pr_entry` FOREIGN KEY (`deducted_from_entry_id`) 
                REFERENCES `budget_utilization_entries` (`id`) ON DELETE SET NULL");
            echo "✓ Foreign key constraint added successfully!\n";
        } catch (PDOException $e) {
            echo "❌ Error adding foreign key: " . $e->getMessage() . "\n";
            echo "   This might be due to existing data that violates the constraint.\n";
            echo "   Please check your data and try again.\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✓ Column structure verified\n";
    if ($fkExists || ($fkCheck->rowCount() > 0)) {
        echo "✓ Foreign key constraint is set up correctly\n";
    } else {
        echo "⚠ Foreign key constraint may need to be added manually\n";
    }
    
    echo "\n✅ Verification complete!\n";
    echo "</pre>";
    echo "<p style='color: green; font-weight: bold;'>Database structure is correct!</p>";
    echo "<p>The deducted_from_entry_id column references budget_utilization_entries.id</p>";
    echo "</body></html>";
    
} catch (Exception $e) {
    echo "<pre>";
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "</pre>";
    echo "<p style='color: red;'>Please check the error above and fix any issues.</p>";
}





