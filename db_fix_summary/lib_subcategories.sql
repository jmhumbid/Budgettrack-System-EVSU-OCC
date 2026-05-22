-- Add support for sub-categories in LIB items
-- This allows items like "Other Maintenance and Operating Expenses" to have sub-items

ALTER TABLE `line_item_budget_items` 
ADD COLUMN `parent_id` int(11) DEFAULT NULL AFTER `lib_id`,
ADD COLUMN `is_parent` tinyint(1) DEFAULT 0 AFTER `parent_id`,
ADD COLUMN `sub_category_name` varchar(255) DEFAULT NULL AFTER `particulars`,
ADD KEY `parent_id` (`parent_id`),
ADD CONSTRAINT `lib_items_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `line_item_budget_items` (`id`) ON DELETE CASCADE;

-- Update existing items to mark them as non-parent items
UPDATE `line_item_budget_items` SET `is_parent` = 0 WHERE `is_parent` IS NULL;
