<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Modify department_id column to allow NULL values in file_submissions table
    $sql = "ALTER TABLE file_submissions MODIFY COLUMN department_id INT NULL";
    $conn->exec($sql);
    echo "Modified file_submissions.department_id column to allow NULL values successfully!<br>";
    
    echo "Setup completed successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
