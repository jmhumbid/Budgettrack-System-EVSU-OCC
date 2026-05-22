<?php
require_once 'config/database.php';

$db = getDB();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

echo "Tables with 'ppmp' or 'lib':\n";
foreach ($tables as $table) {
    if (stripos($table, 'ppmp') !== false || stripos($table, 'lib') !== false) {
        echo "- $table\n";
    }
}

// Check ppmp table structure
echo "\nChecking ppmp table structure:\n";
$stmt = $db->query("DESCRIBE ppmp");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "  {$col['Field']} ({$col['Type']})\n";
}

echo "\nChecking ppmp_items table structure:\n";
$stmt = $db->query("DESCRIBE ppmp_items");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "  {$col['Field']} ({$col['Type']})\n";
}

// Check if libs table exists
$libsExists = in_array('libs', $tables);
if ($libsExists) {
    echo "\nChecking libs table structure:\n";
    $stmt = $db->query("DESCRIBE libs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  {$col['Field']} ({$col['Type']})\n";
    }
} else {
    echo "\nlibs table does not exist\n";
}

// Check if lib_items table exists
$libItemsExists = in_array('lib_items', $tables);
if ($libItemsExists) {
    echo "\nChecking lib_items table structure:\n";
    $stmt = $db->query("DESCRIBE lib_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  {$col['Field']} ({$col['Type']})\n";
    }
} else {
    echo "\nlib_items table does not exist\n";
}
?>
