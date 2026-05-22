-- Add source column to line_item_budget_items table
-- This tracks whether an item is from PPMP (ppmp) or manually added (manual)

ALTER TABLE `line_item_budget_items` 
ADD COLUMN `source` ENUM('manual', 'ppmp') NOT NULL DEFAULT 'manual' AFTER `amount`;

-- Add index for faster filtering
ALTER TABLE `line_item_budget_items` 
ADD INDEX `idx_source` (`source`);

-- Update existing items to set source based on whether they have PPMP reference in particulars
-- This is a one-time migration for existing data
UPDATE `line_item_budget_items` 
SET `source` = 'ppmp' 
WHERE `particulars` LIKE '%PPMP #%';
