<?php
/**
 * Script to add departments/offices to the database
 * Run this once to populate the departments table
 */

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Ensure fiduciary_type column exists
    try {
        $checkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'fiduciary_type'");
        if ($checkCol->rowCount() == 0) {
            $conn->exec("ALTER TABLE departments ADD COLUMN fiduciary_type ENUM('Fiduciary', 'Non-Fiduciary') DEFAULT 'Non-Fiduciary' AFTER dept_code");
            echo "✓ Added fiduciary_type column to departments table\n";
        }
    } catch (Exception $e) {
        echo "Note: " . $e->getMessage() . "\n";
    }
    
    // Define departments
    $departments = [
        // FIDUCIARY departments
        ['dept_name' => 'SSG', 'dept_code' => 'SSG', 'fiduciary_type' => 'Fiduciary'],
        ['dept_name' => 'Guidance Office', 'dept_code' => 'GUID', 'fiduciary_type' => 'Fiduciary'],
        ['dept_name' => 'Culture and Arts', 'dept_code' => 'C&A', 'fiduciary_type' => 'Fiduciary'],
        ['dept_name' => 'IGP Production Office', 'dept_code' => 'IGP', 'fiduciary_type' => 'Fiduciary'],
        ['dept_name' => 'Library', 'dept_code' => 'LIB', 'fiduciary_type' => 'Fiduciary'],
        
        // NON-FIDUCIARY departments
        ['dept_name' => 'Research', 'dept_code' => 'RES', 'fiduciary_type' => 'Non-Fiduciary'],
        ['dept_name' => 'Admin', 'dept_code' => 'ADMIN', 'fiduciary_type' => 'Non-Fiduciary'],
        ['dept_name' => 'Extension Services', 'dept_code' => 'EXT', 'fiduciary_type' => 'Non-Fiduciary'],
    ];
    
    $inserted = 0;
    $updated = 0;
    $skipped = 0;
    
    foreach ($departments as $dept) {
        // Check if department code already exists
        $checkStmt = $conn->prepare("SELECT id, dept_name, fiduciary_type FROM departments WHERE dept_code = :code");
        $checkStmt->bindParam(':code', $dept['dept_code']);
        $checkStmt->execute();
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing department
            $updateStmt = $conn->prepare("UPDATE departments SET dept_name = :name, fiduciary_type = :fiduciary WHERE id = :id");
            $updateStmt->bindParam(':name', $dept['dept_name']);
            $updateStmt->bindParam(':fiduciary', $dept['fiduciary_type']);
            $updateStmt->bindParam(':id', $existing['id'], PDO::PARAM_INT);
            
            if ($updateStmt->execute()) {
                echo "✓ Updated: {$dept['dept_name']} ({$dept['dept_code']}) - {$dept['fiduciary_type']}\n";
                $updated++;
            } else {
                echo "✗ Failed to update: {$dept['dept_name']}\n";
                $skipped++;
            }
        } else {
            // Insert new department
            $insertStmt = $conn->prepare("INSERT INTO departments (dept_name, dept_code, fiduciary_type) VALUES (:name, :code, :fiduciary)");
            $insertStmt->bindParam(':name', $dept['dept_name']);
            $insertStmt->bindParam(':code', $dept['dept_code']);
            $insertStmt->bindParam(':fiduciary', $dept['fiduciary_type']);
            
            if ($insertStmt->execute()) {
                echo "✓ Added: {$dept['dept_name']} ({$dept['dept_code']}) - {$dept['fiduciary_type']}\n";
                $inserted++;
            } else {
                echo "✗ Failed to add: {$dept['dept_name']}\n";
                $skipped++;
            }
        }
    }
    
    echo "\n";
    echo "Summary:\n";
    echo "- Inserted: $inserted\n";
    echo "- Updated: $updated\n";
    echo "- Skipped/Failed: $skipped\n";
    echo "\nDone!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

