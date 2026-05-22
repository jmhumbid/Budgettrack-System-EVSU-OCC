<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Check if column already exists
    $checkQuery = "SHOW COLUMNS FROM file_submissions LIKE 'removed_by_user_at'";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult->rowCount() == 0) {
        // Add removed_by_user_at column
        $sql = "ALTER TABLE file_submissions 
                ADD COLUMN removed_by_user_at TIMESTAMP NULL DEFAULT NULL 
                AFTER submitted_at";
        
        $conn->exec($sql);
        echo "Column 'removed_by_user_at' added successfully to file_submissions table!<br>";
    } else {
        echo "Column 'removed_by_user_at' already exists.<br>";
    }
    
    echo "Setup completed successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

