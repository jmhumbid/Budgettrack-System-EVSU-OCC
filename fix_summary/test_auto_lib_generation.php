<?php
/**
 * Test script for Auto-Generate LIB functionality
 * This script tests the complete auto-generation workflow
 */

require_once 'config/database.php';

echo "<h1>Testing Auto-Generate LIB System</h1>";
echo "<hr>";

// Test 1: Check if lib_custom_items table exists
echo "<h2>Test 1: Database Table Check</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    
    $query = "SHOW TABLES LIKE 'lib_custom_items'";
    $stmt = $db->query($query);
    $result = $stmt->fetch();
    
    if ($result) {
        echo "✅ lib_custom_items table exists<br>";
    } else {
        echo "❌ lib_custom_items table NOT found. Run install_lib_custom_items.php<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 2: Check table structure
echo "<h2>Test 2: Table Structure Check</h2>";
try {
    $query = "DESCRIBE lib_custom_items";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $requiredColumns = ['id', 'department_id', 'year', 'uacs_code', 'general_desc', 
                        'total_amount', 'quarter_1', 'quarter_2', 'quarter_3', 'quarter_4',
                        'created_by', 'created_at', 'updated_at', 'deleted_at', 'deleted_by'];
    
    $foundColumns = array_column($columns, 'Field');
    $missingColumns = array_diff($requiredColumns, $foundColumns);
    
    if (empty($missingColumns)) {
        echo "✅ All required columns present<br>";
        echo "<details><summary>Show columns</summary><pre>";
        print_r($foundColumns);
        echo "</pre></details>";
    } else {
        echo "❌ Missing columns: " . implode(', ', $missingColumns) . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 3: Check for approved allocations
echo "<h2>Test 3: Approved Allocations Check</h2>";
try {
    $query = "SELECT 
                d.dept_name,
                a.year,
                COUNT(*) as allocation_count,
                SUM(a.total_amount) as total_budget
              FROM budget_allocations a
              JOIN departments d ON a.department_id = d.id
              WHERE a.status = 'approved'
              GROUP BY d.dept_name, a.year
              ORDER BY a.year DESC, d.dept_name
              LIMIT 10";
    
    $stmt = $db->query($query);
    $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($allocations) > 0) {
        echo "✅ Found " . count($allocations) . " department/year combinations with approved allocations<br>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; margin-top: 10px;'>";
        echo "<tr><th>Department</th><th>Year</th><th>Allocation Count</th><th>Total Budget</th></tr>";
        foreach ($allocations as $alloc) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($alloc['dept_name']) . "</td>";
            echo "<td>" . $alloc['year'] . "</td>";
            echo "<td>" . $alloc['allocation_count'] . "</td>";
            echo "<td>₱" . number_format($alloc['total_budget'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "⚠️ No approved allocations found. Create some allocations first.<br>";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

// Test 4: Check API files
echo "<h2>Test 4: API Files Check</h2>";
$apiFiles = [
    'api/generate_auto_lib.php',
    'api/add_lib_custom_item.php',
    'api/update_lib_custom_item.php',
    'api/delete_lib_custom_item.php'
];

foreach ($apiFiles as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT found<br>";
    }
}

// Test 5: Test generate_auto_lib.php endpoint
echo "<h2>Test 5: API Endpoint Test</h2>";
if (count($allocations) > 0) {
    $testAlloc = $allocations[0];
    
    // Get department ID
    $query = "SELECT id FROM departments WHERE dept_name = :dept_name LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute(['dept_name' => $testAlloc['dept_name']]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($dept) {
        echo "Testing with Department: " . htmlspecialchars($testAlloc['dept_name']) . " (ID: " . $dept['id'] . "), Year: " . $testAlloc['year'] . "<br>";
        
        // Simulate API call
        $_POST['department_id'] = $dept['id'];
        $_POST['year'] = $testAlloc['year'];
        
        // Start session for API
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = 1; // Simulate logged in user
        
        // Capture output
        ob_start();
        include 'api/generate_auto_lib.php';
        $output = ob_get_clean();
        
        $result = json_decode($output, true);
        
        if ($result && isset($result['success']) && $result['success']) {
            echo "✅ API call successful<br>";
            echo "Items returned: " . count($result['items']) . "<br>";
            echo "Department: " . htmlspecialchars($result['department_name']) . "<br>";
            echo "Year: " . $result['year'] . "<br>";
            
            if (count($result['items']) > 0) {
                echo "<details><summary>Show first 3 items</summary>";
                echo "<pre>";
                print_r(array_slice($result['items'], 0, 3));
                echo "</pre></details>";
            }
        } else {
            echo "❌ API call failed<br>";
            echo "Response: <pre>" . htmlspecialchars($output) . "</pre>";
        }
    }
} else {
    echo "⚠️ Skipping API test (no allocations available)<br>";
}

// Test 6: Check frontend integration
echo "<h2>Test 6: Frontend Integration Check</h2>";
if (file_exists('pages/lib.php')) {
    $libContent = file_get_contents('pages/lib.php');
    
    $checks = [
        'showAutoGenerateLIBModal' => 'Auto-generate modal function',
        'autoGenerateLIBModal' => 'Auto-generate modal HTML',
        'addCustomItemModal' => 'Custom item modal HTML',
        'generateAutoLIB' => 'Generate LIB function',
        'displayAutoGeneratedItems' => 'Display items function',
        'saveAutoGeneratedLIB' => 'Save LIB function'
    ];
    
    foreach ($checks as $search => $description) {
        if (strpos($libContent, $search) !== false) {
            echo "✅ $description found<br>";
        } else {
            echo "❌ $description NOT found<br>";
        }
    }
} else {
    echo "❌ pages/lib.php not found<br>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests pass, the Auto-Generate LIB system is ready to use!</p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Login to the system as a department user</li>";
echo "<li>Navigate to the LIB page</li>";
echo "<li>Click 'Auto-Generate from Allocations' button</li>";
echo "<li>Select a year and click 'Generate LIB'</li>";
echo "<li>Add custom items if needed</li>";
echo "<li>Save the LIB</li>";
echo "</ol>";
