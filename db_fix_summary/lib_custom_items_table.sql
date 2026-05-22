-- Create lib_custom_items table for manually added LIB entries
CREATE TABLE IF NOT EXISTS lib_custom_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    year INT NOT NULL,
    uacs_code VARCHAR(50) NOT NULL,
    general_desc TEXT NOT NULL,
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    quarter_1 DECIMAL(15,2) DEFAULT 0.00,
    quarter_2 DECIMAL(15,2) DEFAULT 0.00,
    quarter_3 DECIMAL(15,2) DEFAULT 0.00,
    quarter_4 DECIMAL(15,2) DEFAULT 0.00,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    deleted_by INT NULL,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (deleted_by) REFERENCES users(id),
    INDEX idx_dept_year (department_id, year),
    INDEX idx_deleted (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
