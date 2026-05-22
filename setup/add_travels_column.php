<?php
/**
 * Script to add deducted_from_entry_id column to utilization_travels table
 * Run this once to fix the missing column issue
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    // Check if column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'deducted_from_entry_id'");
    
    if ($checkColumn->rowCount() > 0) {
        echo "✓ Column 'deducted_from_entry_id' already exists in utilization_travels table.\n";
    } else {
        // Add the column
        $db->exec("ALTER TABLE `utilization_travels` 
            ADD COLUMN `deducted_from_entry_id` INT(11) DEFAULT NULL");
        echo "✓ Successfully added 'deducted_from_entry_id' column to utilization_travels table.\n";
    }
    
    // Also check for is_deducted column (used in save_travels.php)
    $checkIsDeducted = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'is_deducted'");
    
    if ($checkIsDeducted->rowCount() > 0) {
        echo "✓ Column 'is_deducted' already exists in utilization_travels table.\n";
    } else {
        // Add the column
        $db->exec("ALTER TABLE `utilization_travels` 
            ADD COLUMN `is_deducted` TINYINT(1) DEFAULT 0");
        echo "✓ Successfully added 'is_deducted' column to utilization_travels table.\n";
    }
    
    echo "\n✅ Database update completed successfully!\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

