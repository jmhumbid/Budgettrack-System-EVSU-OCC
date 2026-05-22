<?php
/**
 * Fix existing LIB items to set proper source values
 * This updates all existing items to 'auto' since they were auto-generated
 * Future manually added items will be marked as 'manual'
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Fixing existing LIB items source field...\n\n";
    
    // Update all existing items to 'auto' (since they were auto-generated from allocations)
    $stmt = $db->query("UPDATE line_item_budget_items SET source = 'auto' WHERE source = 'manual'");
    $updated = $stmt->rowCount();
    
    echo "✓ Updated $updated items to source = 'auto'\n";
    echo "✓ These items will now be read-only (no edit/delete buttons)\n";
    echo "✓ New items added via 'Add Item' button will be marked as 'manual' and editable\n";
    echo "\nFix completed successfully!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
