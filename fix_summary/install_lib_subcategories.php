<?php
/**
 * Install LIB Sub-categories Feature
 * Run this script once to add sub-category support to the LIB system
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Installing LIB Sub-categories Feature...\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/database/lib_subcategories.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            echo "Executing: " . substr($statement, 0, 50) . "...\n";
            $db->exec($statement);
        }
    }
    
    echo "\n✓ LIB Sub-categories feature installed successfully!\n";
    echo "\nYou can now:\n";
    echo "- Add sub-categories to 'Other Maintenance and Operating Expenses'\n";
    echo "- Parent item amounts will auto-calculate from sub-category totals\n";
    
} catch (PDOException $e) {
    echo "\n✗ Error installing LIB Sub-categories feature:\n";
    echo $e->getMessage() . "\n";
    exit(1);
}
