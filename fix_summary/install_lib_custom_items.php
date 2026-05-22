<?php
require_once 'config/database.php';

echo "Installing lib_custom_items table...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sql = file_get_contents('database/lib_custom_items_table.sql');
    
    $db->exec($sql);
    
    echo "✓ lib_custom_items table created successfully!\n";
    echo "\nThis table allows departments to add custom items to their LIB\n";
    echo "that are not derived from allocations.\n";
    
} catch (PDOException $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
