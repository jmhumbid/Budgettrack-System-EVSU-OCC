<?php
/**
 * Migration Script: Add source field to line_item_budget_items
 * This adds a source column to track whether items are from PPMP or manually added
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "<h2>LIB Items Source Field Migration</h2>";
    echo "<hr>";
    
    // Check if source column already exists
    $stmt = $db->query("SHOW COLUMNS FROM line_item_budget_items LIKE 'source'");
    $columnExists = $stmt->fetch();
    
    if ($columnExists) {
        echo "<p style='color: orange;'>✓ Source column already exists. Skipping creation.</p>";
    } else {
        echo "<p>Adding 'source' column to line_item_budget_items table...</p>";
        
        // Add source column
        $db->exec("
            ALTER TABLE `line_item_budget_items` 
            ADD COLUMN `source` ENUM('manual', 'ppmp') NOT NULL DEFAULT 'manual' AFTER `amount`
        ");
        
        echo "<p style='color: green;'>✓ Source column added successfully!</p>";
        
        // Add index
        $db->exec("
            ALTER TABLE `line_item_budget_items` 
            ADD INDEX `idx_source` (`source`)
        ");
        
        echo "<p style='color: green;'>✓ Index added successfully!</p>";
    }
    
    // Update existing items based on particulars
    echo "<p>Updating existing items to set source based on PPMP references...</p>";
    
    $stmt = $db->exec("
        UPDATE `line_item_budget_items` 
        SET `source` = 'ppmp' 
        WHERE `particulars` LIKE '%PPMP #%'
    ");
    
    echo "<p style='color: green;'>✓ Updated {$stmt} existing items with PPMP references</p>";
    
    // Show summary
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    
    $stmt = $db->query("SELECT source, COUNT(*) as count FROM line_item_budget_items GROUP BY source");
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f0f0;'><th>Source</th><th>Count</th></tr>";
    
    foreach ($summary as $row) {
        $color = $row['source'] === 'ppmp' ? '#e6f7ff' : '#f0f0f0';
        echo "<tr style='background: {$color};'>";
        echo "<td><strong>" . ucfirst($row['source']) . "</strong></td>";
        echo "<td>{$row['count']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<hr>";
    echo "<p style='color: green; font-weight: bold;'>✅ Migration completed successfully!</p>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ul>";
    echo "<li>PPMP-linked items will now be marked as read-only in the LIB</li>";
    echo "<li>These items can only be edited/deleted through the PPMP</li>";
    echo "<li>Manual items can still be edited/deleted normally</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
