<?php
/**
 * Fix recently created LIB items that should be 'auto' but are marked as 'manual'
 * This updates items 542-547 which were auto-generated but incorrectly marked
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Fixing recently created LIB items source field...\n\n";
    
    // Update items 542-547 to 'auto' (these are the auto-generated ones)
    $stmt = $db->prepare("UPDATE line_item_budget_items SET source = 'auto' WHERE id BETWEEN 542 AND 547");
    $stmt->execute();
    $updated = $stmt->rowCount();
    
    echo "✓ Updated $updated items to source = 'auto'\n";
    
    // Show the updated items
    echo "\nVerifying updates:\n";
    $stmt = $db->query("SELECT id, particulars, source FROM line_item_budget_items WHERE id BETWEEN 542 AND 547 ORDER BY id");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$row['id']} | {$row['particulars']} | Source: {$row['source']}\n";
    }
    
    echo "\n✓ Fix completed successfully!\n";
    echo "✓ These items will now show NO edit/delete buttons\n";
    echo "✓ Only manually added items will be editable\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
