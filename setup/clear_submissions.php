<?php
/**
 * Clear all file submissions from the database
 * WARNING: This will delete all PPMP, LIB, APP, and PR submissions
 * Run this script once to empty the file_submissions table
 */

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Delete all submissions
    $deleteQuery = "DELETE FROM file_submissions";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->execute();
    
    $count = $stmt->rowCount();
    echo "Successfully deleted {$count} submission(s) from the database.\n";
    echo "The file_submissions table is now empty.\n";
    
    // Note: Physical files in uploads/ directory are NOT deleted
    // You may want to manually clean up the uploads/ppmp/, uploads/lib/, uploads/app/, and uploads/pr/ directories
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

