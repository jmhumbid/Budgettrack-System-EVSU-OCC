<?php
/**
 * Setup script to create Purchase Request tables
 * Run this once to set up the database tables
 */

require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Create purchase_requests table
    $sql1 = "CREATE TABLE IF NOT EXISTS purchase_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        pr_number VARCHAR(50) UNIQUE NOT NULL,
        procurement_user_id INT NOT NULL,
        department_id INT NOT NULL,
        status ENUM('pending', 'processing', 'delivered', 'received', 'complete') DEFAULT 'pending',
        fiscal_year YEAR NOT NULL,
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        processed_at TIMESTAMP NULL,
        delivered_at TIMESTAMP NULL,
        received_at TIMESTAMP NULL,
        completed_at TIMESTAMP NULL,
        notes TEXT NULL,
        FOREIGN KEY (procurement_user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
        INDEX idx_department (department_id),
        INDEX idx_status (status),
        INDEX idx_submitted_at (submitted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql1);
    echo "✓ Created purchase_requests table<br>";
    
    // Create purchase_request_files table
    $sql2 = "CREATE TABLE IF NOT EXISTS purchase_request_files (
        id INT AUTO_INCREMENT PRIMARY KEY,
        purchase_request_id INT NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (purchase_request_id) REFERENCES purchase_requests(id) ON DELETE CASCADE,
        INDEX idx_pr_id (purchase_request_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql2);
    echo "✓ Created purchase_request_files table<br>";
    
    echo "<br><strong>Setup completed successfully!</strong><br>";
    echo "You can now use the PR Submission feature.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

