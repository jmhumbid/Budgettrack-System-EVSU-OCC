-- Add additional_amount and additional_description columns to budget_allocations table
-- These fields allow adding extra amounts with descriptions to the overall total

ALTER TABLE `budget_allocations` 
ADD COLUMN `additional_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `overall_total`,
ADD COLUMN `additional_description` TEXT NULL AFTER `additional_amount`;

-- Update existing records to have 0.00 for additional_amount if NULL
UPDATE `budget_allocations` SET `additional_amount` = 0.00 WHERE `additional_amount` IS NULL;
