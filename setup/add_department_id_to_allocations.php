<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();

    // Check if department_id column exists
    $checkCol = $conn->query("SHOW COLUMNS FROM allocations_files LIKE 'department_id'");
    if ($checkCol->rowCount() == 0) {
        // Add department_id column
        $conn->exec("ALTER TABLE allocations_files 
                     ADD COLUMN department_id INT NULL DEFAULT NULL AFTER uploaded_by,
                     ADD INDEX idx_department_id (department_id)");
        echo "Column 'department_id' added successfully to allocations_files table!<br>";
    } else {
        echo "Column 'department_id' already exists in allocations_files table.<br>";
    }

    echo "Setup completed successfully!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>

