<?php
/**
 * Migration script to add additional_amount and additional_description fields
 * to budget_allocations table
 */

require_once __DIR__ . '/config/database.php';

try {
    $conn = getDB();
    
    echo "Starting migration...\n";
    
    // Check if columns already exist
    $columns = $conn->query("SHOW COLUMNS FROM budget_allocations")->fetchAll(PDO::FETCH_COLUMN);
    
    $changes = [];
    
    if (!in_array('additional_amount', $columns)) {
        $conn->exec("ALTER TABLE budget_allocations ADD COLUMN additional_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER overall_total");
        $changes[] = "Added additional_amount column";
        echo "✓ Added additional_amount column\n";
    } else {
        echo "- additional_amount column already exists\n";
    }
    
    if (!in_array('additional_description', $columns)) {
        $conn->exec("ALTER TABLE budget_allocations ADD COLUMN additional_description TEXT NULL AFTER additional_amount");
        $changes[] = "Added additional_description column";
        echo "✓ Added additional_description column\n";
    } else {
        echo "- additional_description column already exists\n";
    }
    
    if (empty($changes)) {
        echo "\nNo changes needed. Database is up to date.\n";
    } else {
        echo "\nMigration completed successfully!\n";
        echo "Changes made:\n";
        foreach ($changes as $change) {
            echo "  - $change\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
