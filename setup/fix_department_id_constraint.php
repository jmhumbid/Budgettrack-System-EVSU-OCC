<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    echo "<h2>Fixing Department ID Constraint Issues</h2>";
    
    // First, check if the table exists and what the current constraint is
    $check_query = "SHOW COLUMNS FROM file_submissions LIKE 'department_id'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->execute();
    $column_info = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column_info) {
        echo "<p>Current department_id column info: " . json_encode($column_info) . "</p>";
        
        // Check if it allows NULL
        if (strpos($column_info['Null'], 'YES') !== false) {
            echo "<p style='color: green;'>✓ department_id already allows NULL values</p>";
        } else {
            echo "<p style='color: orange;'>⚠ department_id does not allow NULL, attempting to fix...</p>";
            
            // Try to modify the column to allow NULL
            try {
                $sql = "ALTER TABLE file_submissions MODIFY COLUMN department_id INT NULL";
                $conn->exec($sql);
                echo "<p style='color: green;'>✓ Successfully modified department_id to allow NULL values</p>";
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Failed to modify column: " . $e->getMessage() . "</p>";
                
                // Alternative approach: drop and recreate the column
                try {
                    echo "<p>Attempting alternative approach...</p>";
                    
                    // First, add a temporary column
                    $conn->exec("ALTER TABLE file_submissions ADD COLUMN department_id_temp INT NULL");
                    
                    // Copy data from old column to new column
                    $conn->exec("UPDATE file_submissions SET department_id_temp = department_id");
                    
                    // Drop the old column
                    $conn->exec("ALTER TABLE file_submissions DROP COLUMN department_id");
                    
                    // Rename the new column
                    $conn->exec("ALTER TABLE file_submissions CHANGE COLUMN department_id_temp department_id INT NULL");
                    
                    echo "<p style='color: green;'>✓ Successfully recreated department_id column to allow NULL values</p>";
                } catch (Exception $e2) {
                    echo "<p style='color: red;'>✗ Alternative approach also failed: " . $e2->getMessage() . "</p>";
                }
            }
        }
    } else {
        echo "<p style='color: red;'>✗ file_submissions table or department_id column not found</p>";
    }
    
    // Verify the fix
    $verify_query = "SHOW COLUMNS FROM file_submissions LIKE 'department_id'";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->execute();
    $final_info = $verify_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($final_info && strpos($final_info['Null'], 'YES') !== false) {
        echo "<p style='color: green; font-weight: bold;'>✅ SUCCESS: department_id now allows NULL values!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ FAILED: department_id still does not allow NULL values</p>";
    }
    
    // Test the fix by trying to insert a record with NULL department_id
    echo "<h3>Testing the fix...</h3>";
    try {
        $test_query = "INSERT INTO file_submissions (user_id, department_id, submission_type, fiscal_year, file_name, file_path, file_size, file_type) 
                       VALUES (1, NULL, 'TEST', 2025, 'test.txt', '/tmp/test.txt', 100, 'text/plain')";
        $conn->exec($test_query);
        echo "<p style='color: green;'>✓ Test insert with NULL department_id succeeded!</p>";
        
        // Clean up test record
        $conn->exec("DELETE FROM file_submissions WHERE submission_type = 'TEST'");
        echo "<p>✓ Test record cleaned up</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Test insert failed: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>Setup completed!</h3>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
