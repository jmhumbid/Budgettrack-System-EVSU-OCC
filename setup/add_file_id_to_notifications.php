<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Add file_id column to notifications table
    $sql = "ALTER TABLE notifications ADD COLUMN file_id INT NULL AFTER type";
    $conn->exec($sql);
    echo "Added file_id column to notifications table successfully!<br>";
    
    // Add foreign key constraint
    $sql = "ALTER TABLE notifications ADD CONSTRAINT notifications_ibfk_2 FOREIGN KEY (file_id) REFERENCES file_submissions(id) ON DELETE SET NULL";
    $conn->exec($sql);
    echo "Added foreign key constraint for file_id successfully!<br>";
    
    echo "Setup completed successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
