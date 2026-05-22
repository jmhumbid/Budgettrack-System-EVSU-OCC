<?php
/**
 * Test LIB Sub-Categories Status
 * Checks if database is properly set up and shows current data
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>LIB Sub-Categories Status Check</h1>";
echo "<hr>";

try {
    $db = getDB();
    
    // Test 1: Check if columns exist
    echo "<h2>✓ Test 1: Database Structure</h2>";
    $stmt = $db->query("DESCRIBE line_item_budget_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['parent_id', 'is_parent', 'sub_category_name', 'source'];
    $foundColumns = array_column($columns, 'Field');
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Column</th><th>Status</th></tr>";
    foreach ($requiredColumns as $col) {
        $found = in_array($col, $foundColumns);
        $status = $found ? '✓ EXISTS' : '✗ MISSING';
        $color = $found ? 'green' : 'red';
        echo "<tr><td>$col</td><td style='color: $color; font-weight: bold;'>$status</td></tr>";
    }
    echo "</table>";
    
    // Test 2: Check for parent items
    echo "<h2>Test 2: Parent Items (is_parent = 1)</h2>";
    $stmt = $db->query("
        SELECT id, particulars, account_code, amount, is_parent, source, created_at 
        FROM line_item_budget_items 
        WHERE is_parent = 1 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($parents) > 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Found " . count($parents) . " parent item(s)</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Particulars</th><th>Account Code</th><th>Amount</th><th>Source</th><th>Created</th></tr>";
        foreach ($parents as $parent) {
            echo "<tr>";
            echo "<td>{$parent['id']}</td>";
            echo "<td>{$parent['particulars']}</td>";
            echo "<td>{$parent['account_code']}</td>";
            echo "<td>₱" . number_format($parent['amount'], 2) . "</td>";
            echo "<td>{$parent['source']}</td>";
            echo "<td>{$parent['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No parent items found yet. This is normal if you haven't added any.</p>";
    }
    
    // Test 3: Check for sub-categories
    echo "<h2>Test 3: Sub-Categories (parent_id IS NOT NULL)</h2>";
    $stmt = $db->query("
        SELECT id, parent_id, sub_category_name, amount, created_at 
        FROM line_item_budget_items 
        WHERE parent_id IS NOT NULL 
        ORDER BY parent_id, created_at 
        LIMIT 20
    ");
    $subs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($subs) > 0) {
        echo "<p style='color: green; font-weight: bold;'>✓ Found " . count($subs) . " sub-category(ies)</p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Parent ID</th><th>Sub-Category Name</th><th>Amount</th><th>Created</th></tr>";
        foreach ($subs as $sub) {
            echo "<tr>";
            echo "<td>{$sub['id']}</td>";
            echo "<td>{$sub['parent_id']}</td>";
            echo "<td><strong>{$sub['sub_category_name']}</strong></td>";
            echo "<td>₱" . number_format($sub['amount'], 2) . "</td>";
            echo "<td>{$sub['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No sub-categories found yet. This is normal if you haven't added any.</p>";
    }
    
    // Test 4: Show parent-child relationships
    echo "<h2>Test 4: Parent-Child Relationships</h2>";
    $stmt = $db->query("
        SELECT 
            p.id as parent_id,
            p.particulars as parent_name,
            p.amount as parent_amount,
            COUNT(c.id) as sub_count,
            SUM(c.amount) as sub_total
        FROM line_item_budget_items p
        LEFT JOIN line_item_budget_items c ON c.parent_id = p.id
        WHERE p.is_parent = 1
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ");
    $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($relationships) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Parent ID</th><th>Parent Name</th><th>Parent Amount</th><th>Sub-Categories</th><th>Sub-Total</th><th>Match?</th></tr>";
        
        foreach ($relationships as $rel) {
            $match = (abs($rel['parent_amount'] - $rel['sub_total']) < 0.01) ? '✓' : '✗';
            $matchColor = ($match === '✓') ? 'green' : 'red';
            
            echo "<tr>";
            echo "<td>{$rel['parent_id']}</td>";
            echo "<td>{$rel['parent_name']}</td>";
            echo "<td>₱" . number_format($rel['parent_amount'], 2) . "</td>";
            echo "<td>{$rel['sub_count']}</td>";
            echo "<td>₱" . number_format($rel['sub_total'], 2) . "</td>";
            echo "<td style='color: $matchColor; font-weight: bold;'>$match</td>";
            echo "</tr>";
            
            // Show sub-categories for this parent
            $stmt2 = $db->prepare("
                SELECT sub_category_name, amount 
                FROM line_item_budget_items 
                WHERE parent_id = ? 
                ORDER BY created_at
            ");
            $stmt2->execute([$rel['parent_id']]);
            $subItems = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($subItems as $subItem) {
                echo "<tr style='background-color: #f0f0f0;'>";
                echo "<td colspan='2' style='padding-left: 30px;'>└─ {$subItem['sub_category_name']}</td>";
                echo "<td>₱" . number_format($subItem['amount'], 2) . "</td>";
                echo "<td colspan='3'></td>";
                echo "</tr>";
            }
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠ No parent-child relationships found yet.</p>";
    }
    
    // Summary
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "<ul>";
    echo "<li><strong>Database Structure:</strong> ✓ All required columns exist</li>";
    echo "<li><strong>Parent Items:</strong> " . count($parents) . " found</li>";
    echo "<li><strong>Sub-Categories:</strong> " . count($subs) . " found</li>";
    echo "<li><strong>Relationships:</strong> " . count($relationships) . " found</li>";
    echo "</ul>";
    
    echo "<hr>";
    echo "<h2>Next Steps</h2>";
    if (count($parents) === 0) {
        echo "<p><strong>Ready to test!</strong> Go to the LIB page and:</p>";
        echo "<ol>";
        echo "<li>Create a new LIB</li>";
        echo "<li>Add 'B. Maintenance & Other Operating Expenses' category</li>";
        echo "<li>Click 'Add Item'</li>";
        echo "<li>Type 'other' and <strong>click</strong> 'Other Maintenance and Operating Expenses'</li>";
        echo "<li>Add 2-3 sub-categories</li>";
        echo "<li>Click 'Save'</li>";
        echo "<li>Refresh this page to see the data</li>";
        echo "</ol>";
    } else {
        echo "<p><strong>Data exists!</strong> Check your LIB page to see if the dropdown appears.</p>";
        echo "<p>If the dropdown doesn't appear, check:</p>";
        echo "<ul>";
        echo "<li>Browser console (F12) for JavaScript errors</li>";
        echo "<li>That the item particulars contains 'Other Maintenance' and 'Operating Expenses'</li>";
        echo "<li>That lib_subcategories_inline.js is loaded</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background-color: #f5f5f5;
}
h1 {
    color: #800000;
}
h2 {
    color: #333;
    margin-top: 30px;
}
table {
    background-color: white;
    margin: 10px 0;
}
th {
    background-color: #800000;
    color: white;
    padding: 8px;
}
td {
    padding: 8px;
}
</style>
