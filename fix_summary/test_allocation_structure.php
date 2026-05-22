<?php
/**
 * Test script to check allocation_data structure
 */

require_once 'config/database.php';

echo "<h1>Budget Allocations Structure Test</h1>";
echo "<hr>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get a sample allocation
    $query = "SELECT 
                a.id,
                a.department_id,
                a.fiscal_year,
                a.allocation_data,
                a.status,
                d.dept_name
              FROM budget_allocations a
              JOIN departments d ON a.department_id = d.id
              WHERE a.status = 'active'
              ORDER BY a.created_at DESC
              LIMIT 1";
    
    $stmt = $db->query($query);
    $allocation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$allocation) {
        echo "<p style='color: red;'>❌ No active allocations found.</p>";
        exit;
    }
    
    echo "<h2>Allocation Record</h2>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> " . $allocation['id'] . "</li>";
    echo "<li><strong>Department:</strong> " . htmlspecialchars($allocation['dept_name']) . "</li>";
    echo "<li><strong>Fiscal Year:</strong> " . $allocation['fiscal_year'] . "</li>";
    echo "<li><strong>Status:</strong> " . $allocation['status'] . "</li>";
    echo "</ul>";
    
    echo "<h2>Allocation Data Structure</h2>";
    
    $allocation_data = json_decode($allocation['allocation_data'], true);
    
    if ($allocation_data === null) {
        echo "<p style='color: red;'>❌ Failed to parse allocation_data JSON</p>";
        echo "<pre>" . htmlspecialchars($allocation['allocation_data']) . "</pre>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ Successfully parsed allocation_data JSON</p>";
    
    // Check if it's an array or object
    if (is_array($allocation_data)) {
        if (isset($allocation_data[0])) {
            // It's a numeric array
            echo "<p><strong>Type:</strong> Numeric Array</p>";
            echo "<p><strong>Number of items:</strong> " . count($allocation_data) . "</p>";
            
            echo "<h3>First Item Structure:</h3>";
            echo "<pre>";
            print_r($allocation_data[0]);
            echo "</pre>";
        } else {
            // It's an associative array (object)
            echo "<p><strong>Type:</strong> Associative Array (Object)</p>";
            echo "<p><strong>Number of keys:</strong> " . count($allocation_data) . "</p>";
            
            echo "<h3>Full Structure:</h3>";
            echo "<pre>";
            print_r($allocation_data);
            echo "</pre>";
            
            echo "<h3>Available Keys:</h3>";
            echo "<ul>";
            foreach (array_keys($allocation_data) as $key) {
                echo "<li><code>" . htmlspecialchars($key) . "</code>";
                if (is_array($allocation_data[$key])) {
                    echo " (contains " . count($allocation_data[$key]) . " items)";
                }
                echo "</li>";
            }
            echo "</ul>";
        }
    }
    
    echo "<hr>";
    echo "<h2>✅ Test Complete</h2>";
    echo "<p>Use this information to understand the allocation_data structure.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
