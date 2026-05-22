-- Add account_code column to budget_utilization_entries table
ALTER TABLE `budget_utilization_entries` 
ADD COLUMN `account_code` VARCHAR(50) NULL AFTER `expense_category`,
ADD COLUMN `is_auto_filled` TINYINT(1) DEFAULT 0 AFTER `account_code`,
ADD COLUMN `lib_id` INT NULL AFTER `is_auto_filled`,
ADD INDEX `idx_lib_id` (`lib_id`);

-- Add foreign key constraint to automatically delete utilization entries when LIB is deleted
ALTER TABLE `budget_utilization_entries`
ADD CONSTRAINT `fk_utilization_lib_id` 
FOREIGN KEY (`lib_id`) REFERENCES `line_item_budgets`(`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;

-- This migration adds:
-- 1. account_code: To store the UACS account code from LIB
-- 2. is_auto_filled: Flag to indicate if this entry was auto-filled from LIB (1) or manually created (0)
-- 3. lib_id: Reference to the source LIB record for tracking
-- 4. Foreign key constraint: Automatically deletes utilization entries when LIB is deleted
