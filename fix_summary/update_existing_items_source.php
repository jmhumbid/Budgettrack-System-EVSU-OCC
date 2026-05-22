<?php
/**
 * Update existing items to have source = 'manual'
 * Run this once to update all existing items
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Updating existing line_item_budget_items to set source = 'manual'...\n";
    
    // Update all items that don't have a source set
    $stmt = $db->exec("UPDATE line_item_budget_items SET source = 'manual' WHERE source IS NULL OR source = ''");
    
    echo "✓ Updated items successfully\n";
    
    // Show count
    $stmt = $db->query("SELECT COUNT(*) as total FROM line_item_budget_items WHERE source = 'manual'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Total manual items: {$result['total']}\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM line_item_budget_items WHERE source = 'auto'");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✓ Total auto items: {$result['total']}\n";
    
    echo "\nUpdate completed successfully!\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
