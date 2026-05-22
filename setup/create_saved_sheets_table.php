<?php
/**
 * Create saved_sheets table for Excel-like sheet management
 * Run this once to create the table
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    $db->exec("CREATE TABLE IF NOT EXISTS saved_sheets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sheet_name VARCHAR(255) NOT NULL,
        department_id INT NOT NULL,
        fiscal_year YEAR NOT NULL,
        headers TEXT NOT NULL,
        columns TEXT NOT NULL,
        data TEXT NOT NULL,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
        INDEX idx_department (department_id),
        INDEX idx_fiscal_year (fiscal_year),
        INDEX idx_created_by (created_by)
    )");
    
    echo "Table 'saved_sheets' created successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

