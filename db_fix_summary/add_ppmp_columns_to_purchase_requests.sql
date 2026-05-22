-- Add PPMP reference columns to utilization_purchase_requests table
-- Run this script to fix the "Column not found: ppmp_item_id" error

-- Check if columns exist before adding them
SET @dbname = DATABASE();
SET @tablename = 'utilization_purchase_requests';

-- Add ppmp_item_id column if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'ppmp_item_id');
SET @query = IF(@col_exists = 0, 
    'ALTER TABLE utilization_purchase_requests ADD COLUMN ppmp_item_id INT NULL COMMENT "Reference to ppmp_items.id"',
    'SELECT "Column ppmp_item_id already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add ppmp_id column if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'ppmp_id');
SET @query = IF(@col_exists = 0, 
    'ALTER TABLE utilization_purchase_requests ADD COLUMN ppmp_id INT NULL COMMENT "Reference to ppmp.id"',
    'SELECT "Column ppmp_id already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add ppmp_description column if it doesn't exist
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'ppmp_description');
SET @query = IF(@col_exists = 0, 
    'ALTER TABLE utilization_purchase_requests ADD COLUMN ppmp_description TEXT NULL COMMENT "Formatted PPMP item description"',
    'SELECT "Column ppmp_description already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes for better performance
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = 'idx_ppmp_item');
SET @query = IF(@index_exists = 0, 
    'ALTER TABLE utilization_purchase_requests ADD INDEX idx_ppmp_item (ppmp_item_id)',
    'SELECT "Index idx_ppmp_item already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND INDEX_NAME = 'idx_ppmp');
SET @query = IF(@index_exists = 0, 
    'ALTER TABLE utilization_purchase_requests ADD INDEX idx_ppmp (ppmp_id)',
    'SELECT "Index idx_ppmp already exists" AS message');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify the columns were added
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'utilization_purchase_requests'
    AND COLUMN_NAME IN ('ppmp_item_id', 'ppmp_id', 'ppmp_description')
ORDER BY ORDINAL_POSITION;
