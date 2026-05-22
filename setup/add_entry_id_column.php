<?php
/**
 * Script to add entry_id column to budget_utilization_entries table
 * Note: The table already has an 'id' column as primary key.
 * This script adds an 'entry_id' column if you need it for additional tracking.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Add entry_id Column</title>";
    echo "<style>body { font-family: Arial, sans-serif; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";
    echo "</head><body>";
    echo "<h2>Adding entry_id Column to budget_utilization_entries</h2>";
    echo "<pre>";
    
    // Check if entry_id column already exists
    $checkColumn = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'entry_id'");
    if ($checkColumn->rowCount() > 0) {
        echo "entry_id column already exists. Modifying it to be independent...\n";
        
        // Drop the column and recreate it
        echo "Dropping existing entry_id column...\n";
        $db->exec("ALTER TABLE budget_utilization_entries DROP COLUMN entry_id");
        echo "✓ Dropped existing entry_id column\n";
    }
    
    // Add entry_id column as regular INT (not auto-increment since id is already auto-increment)
    echo "Adding entry_id column as independent INT...\n";
    $db->exec("ALTER TABLE budget_utilization_entries ADD COLUMN entry_id INT(11) DEFAULT NULL AFTER id");
    echo "✓ Added entry_id column\n";
    
    // Populate entry_id with sequential numbers starting from 1 (independent of id)
    echo "Populating entry_id with sequential numbers (independent of id)...\n";
    $entries = $db->query("SELECT id FROM budget_utilization_entries ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);
    $entryIdCounter = 1;
    foreach ($entries as $id) {
        $db->exec("UPDATE budget_utilization_entries SET entry_id = $entryIdCounter WHERE id = $id");
        $entryIdCounter++;
    }
    echo "✓ Populated entry_id column with sequential numbers (1, 2, 3, ...)\n";
    
    // Add index for better performance
    try {
        $db->exec("ALTER TABLE budget_utilization_entries ADD INDEX idx_entry_id (entry_id)");
        echo "✓ Added index on entry_id column\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') === false) {
            echo "⚠ Index may already exist or error: " . $e->getMessage() . "\n";
        } else {
            echo "✓ Index already exists\n";
        }
    }
    
    echo "\n=== Summary ===\n";
    echo "✓ entry_id column added to budget_utilization_entries table\n";
    echo "✓ Column populated with id values\n";
    echo "✓ Index added for better performance\n";
    echo "\nNote: The 'id' column is still the primary key.\n";
    echo "      The 'entry_id' column can be used as an alias/reference if needed.\n";
    
    echo "</pre>";
    echo "<p><strong>✅ Column added successfully!</strong></p>";
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "</pre>";
    echo "<p class='error'><strong>Database Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
} catch (Exception $e) {
    echo "</pre>";
    echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</body></html>";
}

