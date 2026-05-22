<?php
/**
 * Test LIB Sub-Categories Feature
 * This script tests the sub-category functionality
 */

require_once __DIR__ . '/config/database.php';

echo "<h1>LIB Sub-Categories Test</h1>";
echo "<hr>";

try {
    $db = getDB();
    
    // Test 1: Check if columns exist
    echo "<h2>Test 1: Database Structure</h2>";
    $stmt = $db->query("DESCRIBE line_item_budget_items");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['parent_id', 'is_parent', 'sub_category_name'];
    $foundColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $col) {
        if (in_array($col, $foundColumns)) {
            echo "✓ Column '$col' exists<br>";
        } else {
            echo "✗ Column '$col' MISSING<br>";
        }
    }
    
    // Test 2: Check for parent items
    echo "<h2>Test 2: Parent Items</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM line_item_budget_items WHERE is_parent = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Parent items found: " . $result['count'] . "<br>";
    
    // Test 3: Check for sub-categories
    echo "<h2>Test 3: Sub-Categories</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM line_item_budget_items WHERE parent_id IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Sub-categories found: " . $result['count'] . "<br>";
    
    // Test 4: Show sample parent with sub-categories
    echo "<h2>Test 4: Sample Parent-Child Relationships</h2>";
    $stmt = $db->query("
        SELECT 
            p.id as parent_id,
            p.particulars as parent_name,
            p.amount as parent_amount,
            p.is_parent,
            COUNT(c.id) as sub_count,
            SUM(c.amount) as sub_total
        FROM line_item_budget_items p
        LEFT JOIN line_item_budget_items c ON c.parent_id = p.id
        WHERE p.is_parent = 1
        GROUP BY p.id
        LIMIT 5
    ");
    
    $parents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($parents) > 0) {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>Parent ID</th><th>Parent Name</th><th>Parent Amount</th><th>Sub-Categories</th><th>Sub-Total</th><th>Match?</th></tr>";
        
        foreach ($parents as $parent) {
            $match = (abs($parent['parent_amount'] - $parent['sub_total']) < 0.01) ? '✓' : '✗';
            echo "<tr>";
            echo "<td>{$parent['parent_id']}</td>";
            echo "<td>{$parent['parent_name']}</td>";
            echo "<td>₱" . number_format($parent['parent_amount'], 2) . "</td>";
            echo "<td>{$parent['sub_count']}</td>";
            echo "<td>₱" . number_format($parent['sub_total'], 2) . "</td>";
            echo "<td>{$match}</td>";
            echo "</tr>";
            
            // Show sub-categories
            $stmt2 = $db->prepare("SELECT sub_category_name, amount FROM line_item_budget_items WHERE parent_id = ?");
            $stmt2->execute([$parent['parent_id']]);
            $subs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($subs as $sub) {
                echo "<tr style='background-color: #f0f0f0;'>";
                echo "<td colspan='2' style='padding-left: 30px;'>└─ {$sub['sub_category_name']}</td>";
                echo "<td>₱" . number_format($sub['amount'], 2) . "</td>";
                echo "<td colspan='3'></td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
    } else {
        echo "No parent items with sub-categories found yet.<br>";
        echo "This is normal if you haven't created any yet.<br>";
    }
    
    // Test 5: API Endpoints
    echo "<h2>Test 5: API Endpoints</h2>";
    $apiFiles = [
        'add_lib_subcategory.php',
        'update_lib_subcategory.php',
        'delete_lib_subcategory.php',
        'get_lib_subcategories.php'
    ];
    
    foreach ($apiFiles as $file) {
        $path = __DIR__ . '/api/' . $file;
        if (file_exists($path)) {
            echo "✓ API file exists: $file<br>";
        } else {
            echo "✗ API file MISSING: $file<br>";
        }
    }
    
    // Test 6: JavaScript File
    echo "<h2>Test 6: JavaScript File</h2>";
    $jsPath = __DIR__ . '/assets/js/lib_subcategories.js';
    if (file_exists($jsPath)) {
        echo "✓ JavaScript file exists: lib_subcategories.js<br>";
        $jsSize = filesize($jsPath);
        echo "File size: " . number_format($jsSize) . " bytes<br>";
    } else {
        echo "✗ JavaScript file MISSING: lib_subcategories.js<br>";
    }
    
    echo "<hr>";
    echo "<h2>Summary</h2>";
    echo "All tests completed. Review results above.<br>";
    echo "<br>";
    echo "<strong>Next Steps:</strong><br>";
    echo "1. If any columns are missing, run: php install_lib_subcategories.php<br>";
    echo "2. Test the feature in the LIB page<br>";
    echo "3. Add 'Other Maintenance and Operating Expenses' item<br>";
    echo "4. Click 'Manage Sub-Categories' button<br>";
    echo "5. Add sub-categories and verify calculations<br>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
