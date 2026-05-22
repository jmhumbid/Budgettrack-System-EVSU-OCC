<?php
// Database migration to add profile_photo column to users table
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Check if profile_photo column already exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_photo'");
    
    if ($checkColumn->rowCount() == 0) {
        // Add profile_photo column
        $sql = "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(500) NULL AFTER updated_at";
        $conn->exec($sql);
        echo "Successfully added profile_photo column to users table.\n";
    } else {
        echo "profile_photo column already exists in users table.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
