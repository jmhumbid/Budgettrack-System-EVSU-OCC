-- PPMP (Project Procurement Management Plan) Tables

-- Main PPMP table
CREATE TABLE IF NOT EXISTS ppmp (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    fiscal_year VARCHAR(10) NOT NULL,
    ppmp_number VARCHAR(50) NOT NULL,
    is_indicative BOOLEAN DEFAULT 0,
    is_final BOOLEAN DEFAULT 0,
    status ENUM('draft', 'approved', 'rejected') DEFAULT 'draft',
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PPMP Items table
CREATE TABLE IF NOT EXISTS ppmp_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ppmp_id INT NOT NULL,
    general_description TEXT NOT NULL,
    project_type VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) NOT NULL,
    recommended_mode VARCHAR(100) NOT NULL,
    pre_procurement_conference VARCHAR(10) DEFAULT 'N',
    start_procurement DATE,
    end_ads_posting DATE,
    expected_delivery DATE,
    source_of_funds VARCHAR(100) NOT NULL,
    estimated_budget DECIMAL(15,2) NOT NULL,
    allocated_supporting_funds DECIMAL(15,2) DEFAULT 0,
    remarks TEXT,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ppmp_id) REFERENCES ppmp(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- PPMP History table (for tracking changes)
CREATE TABLE IF NOT EXISTS ppmp_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ppmp_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    changes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ppmp_id) REFERENCES ppmp(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
