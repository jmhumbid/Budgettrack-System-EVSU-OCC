-- PPMP-Utilization Integration Database Schema
-- This file contains all database changes needed for PPMP-Utilization integration

-- 1. Add PPMP reference columns to purchase_requests table
ALTER TABLE purchase_requests 
ADD COLUMN IF NOT EXISTS ppmp_item_id INT NULL COMMENT 'Reference to ppmp_items.id',
ADD COLUMN IF NOT EXISTS ppmp_id INT NULL COMMENT 'Reference to ppmp.id',
ADD COLUMN IF NOT EXISTS ppmp_description TEXT NULL COMMENT 'Formatted PPMP item description',
ADD INDEX IF NOT EXISTS idx_ppmp_item (ppmp_item_id),
ADD INDEX IF NOT EXISTS idx_ppmp (ppmp_id);

-- 2. Add deduction tracking columns to ppmp_items table
ALTER TABLE ppmp_items 
ADD COLUMN IF NOT EXISTS deduction_remarks TEXT NULL COMMENT 'Expense category from deduction',
ADD COLUMN IF NOT EXISTS deducted_amount DECIMAL(15,2) DEFAULT 0 COMMENT 'Total amount deducted',
ADD COLUMN IF NOT EXISTS expense_category VARCHAR(255) NULL COMMENT 'Linked expense category';

-- 3. Create ppmp_deductions tracking table
CREATE TABLE IF NOT EXISTS ppmp_deductions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ppmp_id INT NOT NULL COMMENT 'Reference to ppmp.id',
    ppmp_item_id INT NOT NULL COMMENT 'Reference to ppmp_items.id',
    purchase_request_id INT NOT NULL COMMENT 'Reference to utilization_purchase_requests.id',
    utilization_entry_id INT NOT NULL COMMENT 'Reference to utilization entry',
    department_id INT NOT NULL COMMENT 'Department that owns this deduction',
    expense_category VARCHAR(255) NOT NULL COMMENT 'Expense category name',
    amount DECIMAL(15,2) NOT NULL COMMENT 'Deduction amount',
    fiscal_year VARCHAR(10) NOT NULL COMMENT 'Fiscal year',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_ppmp (ppmp_id),
    INDEX idx_ppmp_item (ppmp_item_id),
    INDEX idx_pr (purchase_request_id),
    INDEX idx_utilization (utilization_entry_id),
    INDEX idx_department (department_id),
    INDEX idx_fiscal_year (fiscal_year),
    FOREIGN KEY (ppmp_id) REFERENCES ppmp(id) ON DELETE CASCADE,
    FOREIGN KEY (ppmp_item_id) REFERENCES ppmp_items(id) ON DELETE CASCADE,
    FOREIGN KEY (purchase_request_id) REFERENCES utilization_purchase_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks PPMP item deductions through purchase requests';

-- 4. Add notification flag to ppmp table
ALTER TABLE ppmp
ADD COLUMN IF NOT EXISTS notification_sent BOOLEAN DEFAULT FALSE COMMENT 'Whether budget office was notified';

-- 5. Create index for faster PPMP lookups by department and status
CREATE INDEX IF NOT EXISTS idx_ppmp_dept_status ON ppmp(department_id, status, is_final);
CREATE INDEX IF NOT EXISTS idx_ppmp_fiscal_year ON ppmp(fiscal_year);
