<?php
/**
 * Add SUPPLEMENTAL to submission_type ENUM in file_submissions table
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
        
        // Check if SUPPLEMENTAL is already in the ENUM
        if (strpos($type, 'SUPPLEMENTAL') === false) {
            echo "Updating ENUM to include SUPPLEMENTAL...\n";
            
            // Modify the ENUM to include SUPPLEMENTAL
            // Get existing values first
            $existingValues = ['PPMP', 'LIB', 'APP', 'PR'];
            if (strpos($type, 'PR') === false) {
                $existingValues = ['PPMP', 'LIB', 'APP'];
            }
            $existingValues[] = 'SUPPLEMENTAL';
            
            $enumValues = "'" . implode("','", $existingValues) . "'";
            $alterQuery = "ALTER TABLE file_submissions 
                          MODIFY COLUMN submission_type ENUM($enumValues) NOT NULL";
            
            $conn->exec($alterQuery);
            echo "Successfully updated submission_type ENUM to include SUPPLEMENTAL.\n";
        } else {
            echo "ENUM already includes SUPPLEMENTAL. No changes needed.\n";
        }
    } else {
        echo "Error: Could not find submission_type column.\n";
    }
    
    // Create uploads/supplemental directory if it doesn't exist
    $supplementalDir = __DIR__ . '/../uploads/supplemental/';
    if (!is_dir($supplementalDir)) {
        mkdir($supplementalDir, 0777, true);
        echo "Created directory: uploads/supplemental/\n";
    } else {
        echo "Directory uploads/supplemental/ already exists.\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Please ensure the file_submissions table exists and you have ALTER permissions.\n";
}
?>

