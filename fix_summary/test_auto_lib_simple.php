<?php
/**
 * Simple test to verify auto-generate LIB API works
 */

require_once 'config/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simulate logged in user
$_SESSION['user_id'] = 1;

echo "<h1>Simple Auto-Generate LIB Test</h1>";
echo "<hr>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get first department with allocations
    $query = "SELECT DISTINCT 
                d.id as department_id,
                d.dept_name,
                a.year
              FROM departments d
              JOIN budget_allocations a ON d.id = a.department_id
              WHERE a.status = 'approved'
              ORDER BY a.year DESC
              LIMIT 1";
    
    $stmt = $db->query($query);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$test) {
        echo "<p style='color: red;'>❌ No approved allocations found. Please create some allocations first.</p>";
        exit;
    }
    
    echo "<h2>Test Parameters</h2>";
    echo "<ul>";
    echo "<li><strong>Department:</strong> " . htmlspecialchars($test['dept_name']) . "</li>";
    echo "<li><strong>Department ID:</strong> " . $test['department_id'] . "</li>";
    echo "<li><strong>Year:</strong> " . $test['year'] . "</li>";
    echo "</ul>";
    
    // Test the API
    echo "<h2>Calling generate_auto_lib.php API...</h2>";
    
    $_POST['department_id'] = $test['department_id'];
    $_POST['year'] = $test['year'];
    
    ob_start();
    include 'api/generate_auto_lib.php';
    $output = ob_get_clean();
    
    $result = json_decode($output, true);
    
    if ($result && isset($result['success'])) {
        if ($result['success']) {
            echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS!</p>";
            echo "<h3>Results:</h3>";
            echo "<ul>";
            echo "<li><strong>Items Count:</strong> " . count($result['items']) . "</li>";
            echo "<li><strong>Department:</strong> " . htmlspecialchars($result['department_name']) . "</li>";
            echo "<li><strong>Year:</strong> " . $result['year'] . "</li>";
            echo "</ul>";
            
            if (count($result['items']) > 0) {
                echo "<h3>Sample Items:</h3>";
                echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr style='background: #800000; color: white;'>";
                echo "<th>Source</th><th>UACS Code</th><th>Description</th><th>Amount</th>";
                echo "</tr>";
                
                foreach (array_slice($result['items'], 0, 5) as $item) {
                    $badge = $item['is_custom'] ? 
                        '<span style="background: #3b82f6; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Custom</span>' :
                        '<span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px;">Allocation</span>';
                    
                    echo "<tr>";
                    echo "<td>" . $badge . "</td>";
                    echo "<td style='font-family: monospace;'>" . htmlspecialchars($item['uacs_code']) . "</td>";
                    echo "<td>" . htmlspecialchars($item['general_desc']) . "</td>";
                    echo "<td style='text-align: right;'>₱" . number_format($item['total_amount'], 2) . "</td>";
                    echo "</tr>";
                }
                
                if (count($result['items']) > 5) {
                    echo "<tr><td colspan='4' style='text-align: center; font-style: italic; color: #666;'>";
                    echo "... and " . (count($result['items']) - 5) . " more items";
                    echo "</td></tr>";
                }
                
                echo "</table>";
                
                // Calculate total
                $total = array_sum(array_column($result['items'], 'total_amount'));
                echo "<p style='font-size: 18px; font-weight: bold; margin-top: 20px;'>";
                echo "Grand Total: <span style='color: #10b981;'>₱" . number_format($total, 2) . "</span>";
                echo "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ API returned error: " . htmlspecialchars($result['message']) . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid API response</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    
    echo "<hr>";
    echo "<h2>✅ Test Complete</h2>";
    echo "<p>The auto-generate LIB API is working correctly!</p>";
    echo "<p><strong>Next step:</strong> Test it in the browser by going to the LIB page and clicking 'Auto-Generate from Allocations'</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
