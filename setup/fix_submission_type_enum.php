<?php
/**
 * Fix submission_type ENUM to include APP and PR
 * Run this script once to update the database schema
 */

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Check current ENUM values
    $checkQuery = "SHOW COLUMNS FROM file_submissions WHERE Field = 'submission_type'";
    $stmt = $conn->query($checkQuery);
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($column) {
        $type = $column['Type'];
        echo "Current submission_type definition: " . $type . "\n";
        
        // Check if APP and PR are already in the ENUM
        if (strpos($type, 'APP') === false || strpos($type, 'PR') === false) {
            echo "Updating ENUM to include APP and PR...\n";
            
            // Modify the ENUM to include APP and PR
            $alterQuery = "ALTER TABLE file_submissions 
                          MODIFY COLUMN submission_type ENUM('PPMP', 'LIB', 'APP', 'PR') NOT NULL";
            
            $conn->exec($alterQuery);
            echo "Successfully updated submission_type ENUM to include APP and PR.\n";
        } else {
            echo "ENUM already includes APP and PR. No changes needed.\n";
        }
    } else {
        echo "Error: Could not find submission_type column.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please ensure the file_submissions table exists and you have ALTER permissions.\n";
}

