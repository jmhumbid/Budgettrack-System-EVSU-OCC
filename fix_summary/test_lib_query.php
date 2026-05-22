<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Simulate budget office user
$_SESSION['user_role'] = 'budget';
$_SESSION['user_id'] = 1;

echo "<h2>Testing LIB Query</h2>";

// Test with a specific department ID
$testDepartmentId = isset($_GET['dept_id']) ? $_GET['dept_id'] : 1;

echo "<p>Testing with department_id: $testDepartmentId</p>";
echo "<p>User role: " . $_SESSION['user_role'] . "</p>";

try {
    $db = getDB();
    
    // First, check if there are any LIBs in the database
    echo "<h3>All LIBs in database:</h3>";
    $stmt = $db->query("SELECT l.id, l.lib_number, l.department_id, l.status, d.dept_name 
                        FROM line_item_budgets l 
                        LEFT JOIN departments d ON l.department_id = d.id 
                        ORDER BY l.id");
    $allLibs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($allLibs);
    echo "</pre>";
    
    // Now test the actual query from get_lib_list.php
    echo "<h3>Query with status filter (final only):</h3>";
    $sql = "SELECT l.*, d.dept_name, CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
            (SELECT SUM(amount) FROM line_item_budget_items WHERE lib_id = l.id) as total_amount
            FROM line_item_budgets l
            LEFT JOIN departments d ON l.department_id = d.id
            LEFT JOIN users u ON l.created_by = u.id
            WHERE l.department_id = ? AND l.status = ?
            ORDER BY l.created_at DESC";
    
    echo "<p>SQL: $sql</p>";
    echo "<p>Parameters: department_id=$testDepartmentId, status=final</p>";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$testDepartmentId, 'final']);
    $libs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<pre>";
    print_r($libs);
    echo "</pre>";
    
    if (empty($libs)) {
        echo "<p style='color: red;'><strong>No LIBs found with status='final' for department $testDepartmentId</strong></p>";
        
        // Check what statuses exist
        echo "<h3>Checking all statuses for this department:</h3>";
        $stmt = $db->prepare("SELECT DISTINCT status FROM line_item_budgets WHERE department_id = ?");
        $stmt->execute([$testDepartmentId]);
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($statuses);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p>Try different department: <a href='?dept_id=1'>Dept 1</a> | <a href='?dept_id=2'>Dept 2</a> | <a href='?dept_id=3'>Dept 3</a></p>";
?>
