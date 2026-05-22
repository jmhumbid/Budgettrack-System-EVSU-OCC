<?php
// Test script to verify deduction sources table and data
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'budget_utilization_deduction_sources'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ Table 'budget_utilization_deduction_sources' exists\n\n";
        
        // Show table structure
        echo "Table structure:\n";
        $structure = $db->query("DESCRIBE budget_utilization_deduction_sources");
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "  - {$row['Field']} ({$row['Type']})\n";
        }
        
        // Count records
        echo "\nRecord count:\n";
        $count = $db->query("SELECT COUNT(*) as total FROM budget_utilization_deduction_sources")->fetch();
        echo "  Total records: {$count['total']}\n";
        
        // Show sample data
        if ($count['total'] > 0) {
            echo "\nSample records:\n";
            $samples = $db->query("SELECT * FROM budget_utilization_deduction_sources LIMIT 5");
            while ($row = $samples->fetch(PDO::FETCH_ASSOC)) {
                echo "  - Dept: {$row['department_id']}, Year: {$row['fiscal_year']}, Category: {$row['category_name']}, Type: {$row['source_type']}, Amount: {$row['amount']}\n";
                echo "    Entries: {$row['source_entries']}\n";
            }
        }
    } else {
        echo "✗ Table 'budget_utilization_deduction_sources' does NOT exist\n";
        echo "  It will be created automatically when you save utilization data.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
