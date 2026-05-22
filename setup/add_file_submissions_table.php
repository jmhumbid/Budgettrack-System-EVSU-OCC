<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Create file_submissions table
    $sql = "CREATE TABLE IF NOT EXISTS file_submissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        department_id INT,
        submission_type ENUM('PPMP', 'LIB', 'APP', 'PR') NOT NULL,
        fiscal_year YEAR NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size INT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL,
        reviewed_by INT NULL,
        comments TEXT NULL,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
        FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    $conn->exec($sql);
    echo "File submissions table created successfully!<br>";
    
    // Create uploads directories
    $upload_dirs = [
        __DIR__ . '/../uploads/ppmp/',
        __DIR__ . '/../uploads/lib/',
        __DIR__ . '/../uploads/app/',
        __DIR__ . '/../uploads/pr/',
    ];
    
    foreach ($upload_dirs as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
            echo "Created directory: " . $dir . "<br>";
        }
    }
    
    echo "Setup completed successfully!";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
