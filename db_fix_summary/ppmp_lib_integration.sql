-- Add LIB integration columns to ppmp_items table
ALTER TABLE ppmp_items 
ADD COLUMN IF NOT EXISTS lib_category VARCHAR(255) DEFAULT NULL COMMENT 'LIB category (A/B/C)',
ADD COLUMN IF NOT EXISTS lib_particulars VARCHAR(500) DEFAULT NULL COMMENT 'LIB expense description',
ADD COLUMN IF NOT EXISTS lib_account_code VARCHAR(50) DEFAULT NULL COMMENT 'UACS account code',
ADD COLUMN IF NOT EXISTS lib_synced TINYINT(1) DEFAULT 0 COMMENT 'Whether synced to LIB';

-- Create index for faster lookups
CREATE INDEX IF NOT EXISTS idx_ppmp_lib_sync ON ppmp_items(lib_synced, ppmp_id);
CREATE INDEX IF NOT EXISTS idx_ppmp_lib_category ON ppmp_items(lib_category);

-- Add metadata to track PPMP-LIB relationships
CREATE TABLE IF NOT EXISTS ppmp_lib_mappings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ppmp_id INT NOT NULL,
    ppmp_item_id INT NOT NULL,
    lib_id INT DEFAULT NULL,
    lib_item_id INT DEFAULT NULL,
    fiscal_year VARCHAR(20) NOT NULL,
    department_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ppmp_id) REFERENCES ppmps(id) ON DELETE CASCADE,
    FOREIGN KEY (ppmp_item_id) REFERENCES ppmp_items(id) ON DELETE CASCADE,
    FOREIGN KEY (lib_id) REFERENCES libs(id) ON DELETE SET NULL,
    FOREIGN KEY (lib_item_id) REFERENCES lib_items(id) ON DELETE SET NULL,
    UNIQUE KEY unique_ppmp_item (ppmp_item_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
