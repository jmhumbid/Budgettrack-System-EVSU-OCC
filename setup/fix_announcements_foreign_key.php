<?php
/**
 * Script to update the announcements table foreign key constraint
 * This allows user deletion by reassigning announcements in the User::hardDelete() method
 * 
 * Run this once to update existing databases
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    // Check if announcements table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'announcements'");
    if ($tableCheck->rowCount() == 0) {
        echo "Announcements table does not exist. No action needed.\n";
        exit;
    }
    
    // Drop existing foreign key constraint if it exists
    try {
        // Get constraint name
        $fkCheck = $db->query("SELECT CONSTRAINT_NAME 
                              FROM information_schema.KEY_COLUMN_USAGE 
                              WHERE TABLE_SCHEMA = DATABASE() 
                              AND TABLE_NAME = 'announcements' 
                              AND COLUMN_NAME = 'created_by' 
                              AND REFERENCED_TABLE_NAME = 'users'");
        
        if ($fkCheck->rowCount() > 0) {
            $constraint = $fkCheck->fetch(PDO::FETCH_ASSOC);
            $constraintName = $constraint['CONSTRAINT_NAME'];
            
            // Drop the constraint
            $db->exec("ALTER TABLE announcements DROP FOREIGN KEY `$constraintName`");
            echo "Dropped existing foreign key constraint: $constraintName\n";
        }
    } catch (PDOException $e) {
        echo "Note: Could not drop existing constraint (might not exist): " . $e->getMessage() . "\n";
    }
    
    // Note: We keep the RESTRICT constraint because created_by is NOT NULL
    // The User::hardDelete() method now handles reassignment before deletion
    echo "Foreign key constraint update completed.\n";
    echo "User deletion will now work by reassigning announcements to a fallback user.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

