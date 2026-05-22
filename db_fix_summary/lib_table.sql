-- Line Item Budget (LIB) Table
CREATE TABLE IF NOT EXISTS `line_item_budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `fund_type` enum('Internally Generated Fund','Other Fund') NOT NULL DEFAULT 'Internally Generated Fund',
  `status` enum('draft','pending_approval','approved','rejected') NOT NULL DEFAULT 'draft',
  `approved_by_budget_office` tinyint(1) DEFAULT 0,
  `approved_date` datetime DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by_user_id` (`approved_by_user_id`),
  CONSTRAINT `lib_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lib_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lib_approved_by_fk` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Line Item Budget Items Table
CREATE TABLE IF NOT EXISTS `line_item_budget_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lib_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `particulars` varchar(255) NOT NULL,
  `account_code` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lib_id` (`lib_id`),
  CONSTRAINT `lib_items_lib_fk` FOREIGN KEY (`lib_id`) REFERENCES `line_item_budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
