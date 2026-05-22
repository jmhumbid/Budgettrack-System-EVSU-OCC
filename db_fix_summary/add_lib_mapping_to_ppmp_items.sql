-- Add LIB mapping fields to ppmp_items table
-- These fields store the link between PPMP items and LIB expense categories

ALTER TABLE ppmp_items 
ADD COLUMN IF NOT EXISTS lib_category VARCHAR(100) DEFAULT NULL COMMENT 'LIB expense category (e.g., B. Maintenance & Other Operating Expenses)',
ADD COLUMN IF NOT EXISTS lib_particulars VARCHAR(255) DEFAULT NULL COMMENT 'LIB expense particulars (e.g., Office Supplies Expenses)',
ADD COLUMN IF NOT EXISTS lib_account_code VARCHAR(50) DEFAULT NULL COMMENT 'UACS account code for the expense';

-- Add index for faster lookups
ALTER TABLE ppmp_items 
ADD INDEX IF NOT EXISTS idx_lib_mapping (lib_category, lib_particulars, lib_account_code);
