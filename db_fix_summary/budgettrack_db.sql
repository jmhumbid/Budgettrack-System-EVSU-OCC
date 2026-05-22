-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 14, 2026 at 02:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `budgettrack_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `activity_logs`
--

CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `module` varchar(50) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `allocations_files`
--

CREATE TABLE `allocations_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `department_id` int(11) DEFAULT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_edited_at` timestamp NULL DEFAULT NULL,
  `last_edited_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `allocations_files`
--

INSERT INTO `allocations_files` (`id`, `file_name`, `file_path`, `file_size`, `file_type`, `uploaded_by`, `department_id`, `uploaded_at`, `last_edited_at`, `last_edited_by`) VALUES
(52, '2025 OFFICES UTILIZATION.xlsx', 'uploads/allocations/ARCHIVE_1765645355.xlsx', 277742, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 32, NULL, '2025-12-13 17:02:35', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `allocations_history`
--

CREATE TABLE `allocations_history` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `allocation_grid_metadata`
--

CREATE TABLE `allocation_grid_metadata` (
  `fiscal_year` year(4) NOT NULL,
  `headers` text NOT NULL,
  `columns` text NOT NULL,
  `updated_by` int(11) NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `allocation_grid_metadata`
--

INSERT INTO `allocation_grid_metadata` (`fiscal_year`, `headers`, `columns`, `updated_by`, `updated_at`) VALUES
('2025', '[\"CONTRIBUTION AWARD: \\u203c\\ufe0f\\r\\n\\r\\n10k contri - 1,500 pts + 1 set\\r\\n20k contri - 3,000 pts + 1 set\\r\\n30k contri - 5,000 pts + 2 sets\\r\\n50k contri - 10,000 pts + 1 mount\",\"MEMBER\",\"CONTRIBUTION\",\"CLAIMED PTS.\",\"UNCLAIMED PTS.\"]', '[{\"data\":\"CONTRIBUTION AWARD: \\u203c\\ufe0f\\r\\n\\r\\n10k contri - 1,500 pts + 1 set\\r\\n20k contri - 3,000 pts + 1 set\\r\\n30k contri - 5,000 pts + 2 sets\\r\\n50k contri - 10,000 pts + 1 mount\",\"type\":\"text\",\"width\":200,\"className\":\"htLeft\"},{\"data\":\"MEMBER\",\"type\":\"text\",\"width\":200,\"className\":\"htLeft\"},{\"data\":\"CONTRIBUTION\",\"type\":\"text\",\"width\":200,\"className\":\"htLeft\"},{\"data\":\"CLAIMED PTS.\",\"type\":\"text\",\"width\":200,\"className\":\"htLeft\"},{\"data\":\"UNCLAIMED PTS.\",\"type\":\"numeric\",\"width\":150,\"className\":\"htRight\",\"numericFormat\":{\"pattern\":\"0,0.00\"}}]', 5, '2025-11-06 17:11:01');

-- --------------------------------------------------------

--
-- Table structure for table `announcements`
--

CREATE TABLE `announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `announcement_departments`
--

CREATE TABLE `announcement_departments` (
  `id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `automated_reports`
--

CREATE TABLE `automated_reports` (
  `id` int(11) NOT NULL,
  `report_type` enum('weekly','monthly','yearly') NOT NULL,
  `report_period_start` date NOT NULL,
  `report_period_end` date NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_allocations`
--

CREATE TABLE `budget_allocations` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `num_students` int(11) DEFAULT NULL,
  `total_tuition_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
  `instructional_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `budget_allocated` decimal(15,2) NOT NULL DEFAULT 0.00,
  `overall_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allocation_data` longtext NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `utilized_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(15,2) GENERATED ALWAYS AS (`allocated_amount` - `utilized_amount`) STORED,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_categories`
--

CREATE TABLE `budget_categories` (
  `id` int(11) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `category_code` varchar(20) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_categories`
--

INSERT INTO `budget_categories` (`id`, `category_name`, `category_code`, `description`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Personnel Services', 'PS', 'Salaries, wages, and benefits for employees', 1, '2025-09-21 18:39:29', '2025-09-21 18:39:29'),
(2, 'Maintenance and Other Operating Expenses', 'MOOE', 'Office supplies, utilities, maintenance, and operational expenses', 1, '2025-09-21 18:39:29', '2025-09-21 18:39:29'),
(3, 'Capital Outlay', 'CO', 'Equipment, furniture, and infrastructure investments', 1, '2025-09-21 18:39:29', '2025-09-21 18:39:29'),
(4, 'Special Purpose Fund', 'SPF', 'Special projects and programs', 1, '2025-09-21 18:39:29', '2025-09-21 18:39:29'),
(5, 'Research and Development', 'R&D', 'Research activities and development projects', 1, '2025-09-21 18:39:29', '2025-09-21 18:39:29');

-- --------------------------------------------------------

--
-- Table structure for table `budget_files`
--

CREATE TABLE `budget_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_edited_at` timestamp NULL DEFAULT NULL,
  `last_edited_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `budget_utilization_entries`
--

CREATE TABLE `budget_utilization_entries` (
  `id` int(11) NOT NULL,
  `deducted_from_entry_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `expense_category` varchar(255) NOT NULL,
  `allocated_budget` decimal(15,2) DEFAULT 0.00,
  `deductions` decimal(15,2) DEFAULT 0.00,
  `total_balance` decimal(15,2) DEFAULT 0.00,
  `fiscal_year` year(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Triggers `budget_utilization_entries`
--
DELIMITER $$
CREATE TRIGGER `set_deducted_from_entry_id` BEFORE INSERT ON `budget_utilization_entries` FOR EACH ROW BEGIN
                    IF NEW.deducted_from_entry_id = 0 OR NEW.deducted_from_entry_id IS NULL THEN
                        SET NEW.deducted_from_entry_id = (
                            SELECT COALESCE(MAX(deducted_from_entry_id), 0) + 1 
                            FROM budget_utilization_entries
                        );
                    END IF;
                END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `cabac_fiduciary_entries`
--

CREATE TABLE `cabac_fiduciary_entries` (
  `id` int(11) NOT NULL,
  `particulars` varchar(255) NOT NULL,
  `sub_particular` varchar(255) DEFAULT NULL,
  `programs` varchar(255) NOT NULL,
  `approved_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_allotment` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allotment_details` text DEFAULT NULL COMMENT 'JSON array of allotment entries',
  `fiscal_year` int(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cabac_fiduciary_entries`
--

INSERT INTO `cabac_fiduciary_entries` (`id`, `particulars`, `sub_particular`, `programs`, `approved_budget`, `total_allotment`, `balance`, `allotment_details`, `fiscal_year`, `created_by`, `created_at`, `updated_at`) VALUES
(9, 'ps', 'honoraria-part-time', 'Laboratory Fee', 0.00, 0.00, 0.00, '[]', 2026, 5, '2026-01-12 15:33:18', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cabac_fiduciary_programs`
--

CREATE TABLE `cabac_fiduciary_programs` (
  `id` int(11) NOT NULL,
  `sub_particular_id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `approved_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_allotment` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allotment_details` text DEFAULT NULL COMMENT 'JSON array of allotment entries: [{month/date, amount}, ...]',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabac_fiduciary_sub_particulars`
--

CREATE TABLE `cabac_fiduciary_sub_particulars` (
  `id` int(11) NOT NULL,
  `entry_id` int(11) NOT NULL,
  `sub_particular_name` varchar(255) NOT NULL,
  `sub_particular_type` varchar(100) DEFAULT NULL COMMENT 'e.g., honoraria, mooe-co, mooe-coe, etc.',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabac_files`
--

CREATE TABLE `cabac_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_edited_at` timestamp NULL DEFAULT NULL,
  `last_edited_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabac_history`
--

CREATE TABLE `cabac_history` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabac_non_fiduciary_entries`
--

CREATE TABLE `cabac_non_fiduciary_entries` (
  `id` int(11) NOT NULL,
  `particulars` varchar(255) NOT NULL,
  `sub_particular` varchar(255) DEFAULT NULL,
  `programs` varchar(255) NOT NULL,
  `approved_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_allotment` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allotment_details` text DEFAULT NULL COMMENT 'JSON array of allotment entries',
  `fiscal_year` int(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cabac_non_fiduciary_entries`
--

INSERT INTO `cabac_non_fiduciary_entries` (`id`, `particulars`, `sub_particular`, `programs`, `approved_budget`, `total_allotment`, `balance`, `allotment_details`, `fiscal_year`, `created_by`, `created_at`, `updated_at`) VALUES
(82, 'ps', 'honoraria', 'Faculty & Staff Development', 0.00, 0.00, 0.00, '[]', 2026, 32, '2026-01-12 20:02:32', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `cabac_non_fiduciary_programs`
--

CREATE TABLE `cabac_non_fiduciary_programs` (
  `id` int(11) NOT NULL,
  `sub_particular_id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `approved_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_allotment` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `allotment_details` text DEFAULT NULL COMMENT 'JSON array of allotment entries: [{month/date, amount}, ...]',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabac_non_fiduciary_sub_particulars`
--

CREATE TABLE `cabac_non_fiduciary_sub_particulars` (
  `id` int(11) NOT NULL,
  `entry_id` int(11) NOT NULL,
  `sub_particular_name` varchar(255) NOT NULL,
  `sub_particular_type` varchar(100) DEFAULT NULL COMMENT 'e.g., honoraria, mooe-co, mooe-coe, etc.',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabac_programs`
--

CREATE TABLE `cabac_programs` (
  `id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `type` enum('fiduciary','non-fiduciary') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cabac_programs`
--

INSERT INTO `cabac_programs` (`id`, `program_name`, `type`, `created_at`, `updated_at`) VALUES
(1, 'Faculty and Staff Development', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(2, 'Curriculum Development', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(3, 'Student Development', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(4, 'Facilities Development', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(5, 'Research', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(6, 'Production', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(7, 'Extension', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(8, 'Administrator', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(9, 'Mandatory Reserve', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(10, 'Petition', 'non-fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(11, 'Athletics', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(12, 'Library Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(13, 'Laboratory Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(14, 'NSTP', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(15, 'SCUAA Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(16, 'Computer Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(17, 'Internet Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(18, 'CCNA', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(19, 'Cultural', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(20, 'Development Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(21, 'Student Activity Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(22, 'Student Council Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(23, 'School Organ Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(24, 'Guidance Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(25, 'Medical Dental Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(26, 'Insurance Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(27, 'School ID Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(28, 'Graduation Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(29, 'Handbook', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(30, 'OJT Fee', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(31, 'Documentary Stamp', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(32, 'Trust Fund', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(33, 'Other Services Income', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20'),
(34, 'Rent Income', 'fiduciary', '2026-01-13 09:16:20', '2026-01-13 09:16:20');

-- --------------------------------------------------------

--
-- Table structure for table `cabac_programs_entries`
--

CREATE TABLE `cabac_programs_entries` (
  `id` int(11) NOT NULL,
  `programs_id` int(11) NOT NULL,
  `program` varchar(255) NOT NULL,
  `approved_budget` decimal(12,2) NOT NULL,
  `available_allotement` decimal(12,2) NOT NULL,
  `balance` decimal(12,2) NOT NULL,
  `action` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cabac_program_entries`
--

CREATE TABLE `cabac_program_entries` (
  `id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  `program_name` varchar(255) NOT NULL,
  `approved_budget` decimal(15,2) NOT NULL DEFAULT 0.00,
  `available_allotment` decimal(15,2) NOT NULL DEFAULT 0.00,
  `balance` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `cabac_program_entries`
--

INSERT INTO `cabac_program_entries` (`id`, `program_id`, `program_name`, `approved_budget`, `available_allotment`, `balance`, `created_at`, `updated_at`) VALUES
(5, 11, 'wearedevs', 100000.00, 1000.00, 99000.00, '2026-01-13 12:45:58', '2026-01-13 12:45:58'),
(6, 4, 'waeasdwa', 1123121.00, 223.00, 1122898.00, '2026-01-13 12:48:59', '2026-01-13 12:48:59'),
(9, 8, 'waesdwas', 12312312.00, 0.00, 12312312.00, '2026-01-13 12:51:13', '2026-01-13 12:51:13'),
(355, 1, 'Honoraria', 2000000.00, 1528875.00, 471125.00, '2026-01-13 17:01:30', '2026-01-13 17:01:30'),
(356, 1, 'Traveling Expenses - Local', 500000.00, 200000.00, 300000.00, '2026-01-13 17:01:30', '2026-01-13 17:01:30'),
(357, 1, 'Training Expenses', 300000.00, 100000.00, 200000.00, '2026-01-13 17:01:30', '2026-01-13 17:01:30'),
(358, 1, 'Office Supplies Expenses', 642291.00, 100000.00, 542291.00, '2026-01-13 17:01:30', '2026-01-13 17:01:30'),
(359, 1, 'Other Supplies and Materials', 900000.00, 300000.00, 600000.00, '2026-01-13 17:01:30', '2026-01-13 17:01:30'),
(360, 1, 'Telephone Expenses - Mobile', 150000.00, 50000.00, 100000.00, '2026-01-13 17:01:30', '2026-01-13 17:01:30'),
(361, 1, 'Rewards and Incentives', 50000.00, 10000.00, 40000.00, '2026-01-13 17:01:30', '2026-01-13 17:01:30'),
(362, 1, 'Other MOOE', 600000.00, 200000.00, 400000.00, '2026-01-13 17:01:30', '2026-01-13 17:01:30');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(20) NOT NULL,
  `fiduciary_type` enum('Fiduciary','Non-Fiduciary') DEFAULT 'Non-Fiduciary',
  `dept_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_name`, `dept_code`, `fiduciary_type`, `dept_description`, `is_active`, `created_at`, `updated_at`) VALUES
(13, 'Computer Studies', 'CS', 'Non-Fiduciary', 'Computer Studies Department', 1, '2025-09-28 19:14:10', '2025-12-15 11:58:29'),
(14, 'Engineering', 'ENGR', 'Non-Fiduciary', 'ajkfafa', 1, '2025-10-17 16:43:48', '2025-12-15 11:58:47'),
(15, 'Education', 'EDUC', 'Non-Fiduciary', '', 1, '2025-11-19 12:36:43', '2025-12-15 11:58:36'),
(16, 'Procurement Office', 'PR', 'Fiduciary', '', 1, '2025-11-22 11:24:09', '2025-11-22 11:43:58'),
(17, 'SSG', 'SSG', 'Fiduciary', NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(18, 'Guidance Office', 'GUID', 'Fiduciary', NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(19, 'Culture and Arts', 'C&A', 'Fiduciary', NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(20, 'IGP Production Office', 'IGP', 'Fiduciary', NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(21, 'Library', 'LIB', 'Fiduciary', NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(22, 'Research', 'RES', 'Non-Fiduciary', NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(23, 'Admin', 'ADMIN', 'Fiduciary', NULL, 1, '2025-11-22 11:46:46', '2025-12-15 11:57:54'),
(24, 'Extension Services', 'EXT', 'Fiduciary', NULL, 1, '2025-11-22 11:46:46', '2025-12-15 11:58:09'),
(25, 'Supply Office', 'SP', 'Fiduciary', '', 1, '2025-11-26 23:38:04', '2025-12-15 11:58:55'),
(26, 'Budget Office', 'BO', 'Fiduciary', '', 1, '2025-11-27 07:35:24', '2025-12-15 11:57:43'),
(27, 'Industrial Technology', 'INDTECH', 'Non-Fiduciary', '', 1, '2025-12-22 09:57:33', '2025-12-22 09:57:33'),
(28, 'Hospitality Management', 'BSHM', 'Non-Fiduciary', '', 1, '2025-12-22 09:57:46', '2025-12-22 09:57:46');

-- --------------------------------------------------------

--
-- Table structure for table `department_budgets`
--

CREATE TABLE `department_budgets` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `total_allocated` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_utilized` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_remaining` decimal(15,2) GENERATED ALWAYS AS (`total_allocated` - `total_utilized`) STORED,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `department_budgets`
--

INSERT INTO `department_budgets` (`id`, `department_id`, `fiscal_year`, `total_allocated`, `total_utilized`, `last_updated`) VALUES
(7, 13, '2025', 5480000.00, 0.00, '2025-12-29 12:25:48'),
(16, 18, '2025', 80000.00, 0.00, '2025-12-29 12:45:26'),
(18, 16, '2025', 150000.00, 0.00, '2025-12-30 15:21:09'),
(23, 26, '2025', 80000.00, 0.00, '2025-12-23 13:14:23'),
(66, 23, '2025', 110000.00, 0.00, '2025-12-28 06:41:13');

-- --------------------------------------------------------

--
-- Table structure for table `file_submissions`
--

CREATE TABLE `file_submissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `submission_type` enum('PPMP','LIB','APP','PR','SUPPLEMENTAL') NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `removed_by_user_at` timestamp NULL DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `review_notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','warning','success','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `read_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `password_reset_tokens`
--

CREATE TABLE `password_reset_tokens` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `permission_name` varchar(100) NOT NULL,
  `permission_description` text DEFAULT NULL,
  `module` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `permission_name`, `permission_description`, `module`, `created_at`) VALUES
(1, 'create_users', 'Create new user accounts', 'user_management', '2025-09-21 04:41:14'),
(2, 'edit_users', 'Edit existing user accounts', 'user_management', '2025-09-21 04:41:14'),
(3, 'delete_users', 'Delete user accounts', 'user_management', '2025-09-21 04:41:14'),
(4, 'view_users', 'View user accounts', 'user_management', '2025-09-21 04:41:14'),
(5, 'assign_roles', 'Assign roles to users', 'user_management', '2025-09-21 04:41:14'),
(6, 'create_roles', 'Create new roles', 'role_management', '2025-09-21 04:41:14'),
(7, 'edit_roles', 'Edit existing roles', 'role_management', '2025-09-21 04:41:14'),
(8, 'delete_roles', 'Delete roles', 'role_management', '2025-09-21 04:41:14'),
(9, 'view_roles', 'View roles', 'role_management', '2025-09-21 04:41:14'),
(10, 'manage_permissions', 'Manage role permissions', 'role_management', '2025-09-21 04:41:14'),
(11, 'create_departments', 'Create new departments', 'department_management', '2025-09-21 04:41:14'),
(12, 'edit_departments', 'Edit existing departments', 'department_management', '2025-09-21 04:41:14'),
(13, 'delete_departments', 'Delete departments', 'department_management', '2025-09-21 04:41:14'),
(14, 'view_departments', 'View departments', 'department_management', '2025-09-21 04:41:14'),
(15, 'create_budget', 'Create budget allocations', 'budget_management', '2025-09-21 04:41:14'),
(16, 'edit_budget', 'Edit budget allocations', 'budget_management', '2025-09-21 04:41:14'),
(17, 'view_budget', 'View budget information', 'budget_management', '2025-09-21 04:41:14'),
(18, 'approve_budget', 'Approve budget requests', 'budget_management', '2025-09-21 04:41:14'),
(19, 'create_ppmp', 'Create PPMP submissions', 'ppmp_management', '2025-09-21 04:41:14'),
(20, 'edit_ppmp', 'Edit PPMP submissions', 'ppmp_management', '2025-09-21 04:41:14'),
(21, 'view_ppmp', 'View PPMP submissions', 'ppmp_management', '2025-09-21 04:41:14'),
(22, 'approve_ppmp', 'Approve PPMP submissions', 'ppmp_management', '2025-09-21 04:41:14'),
(23, 'view_reports', 'View system reports', 'reports', '2025-09-21 04:41:14'),
(24, 'generate_reports', 'Generate custom reports', 'reports', '2025-09-21 04:41:14'),
(25, 'export_reports', 'Export reports', 'reports', '2025-09-21 04:41:14'),
(26, 'view_dashboard', 'View dashboard', 'dashboard', '2025-09-21 04:41:14'),
(27, 'view_admin_dashboard', 'View admin dashboard', 'dashboard', '2025-09-21 04:41:14'),
(28, 'view_notifications', 'View notifications', 'notifications', '2025-09-21 04:41:14'),
(29, 'send_notifications', 'Send notifications', 'notifications', '2025-09-21 04:41:14'),
(30, 'control_admin', 'Control admin role permissions and access', 'system_control', '2025-09-21 04:41:14'),
(31, 'manage_all_roles', 'Manage all roles including admin', 'system_control', '2025-09-21 04:41:14'),
(32, 'system_override', 'Override any system restrictions', 'system_control', '2025-09-21 04:41:14'),
(34, 'view_purchase_orders', 'View purchase orders queue', 'purchase_order', '2025-11-27 05:47:03'),
(35, 'manage_purchase_orders', 'Manage purchase orders', 'purchase_order', '2025-11-27 05:47:03');

-- --------------------------------------------------------

--
-- Table structure for table `procurement_announcements`
--

CREATE TABLE `procurement_announcements` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `priority` enum('high','medium','low') DEFAULT 'medium',
  `target_department_id` int(11) DEFAULT NULL,
  `target_all` tinyint(1) DEFAULT 0,
  `target_emails` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposed_budget_files`
--

CREATE TABLE `proposed_budget_files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proposed_budget_history`
--

CREATE TABLE `proposed_budget_history` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

CREATE TABLE `purchase_requests` (
  `id` int(11) NOT NULL,
  `pr_number` varchar(50) NOT NULL,
  `procurement_user_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `status` enum('pending','processing','delivered','received','complete') DEFAULT 'pending',
  `fiscal_year` year(4) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `processed_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `received_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_files`
--

CREATE TABLE `purchase_request_files` (
  `id` int(11) NOT NULL,
  `purchase_request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` int(11) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `id` int(11) NOT NULL,
  `role_name` varchar(50) NOT NULL,
  `role_description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `roles`
--

INSERT INTO `roles` (`id`, `role_name`, `role_description`, `created_at`, `updated_at`) VALUES
(1, 'budget', 'Budget/Finance Office - System Administrator with full control over everything', '2025-09-21 07:35:15', '2025-09-21 07:35:15'),
(3, 'offices', 'Department Offices - Manages department budget and submits PPMP', '2025-09-21 07:35:15', '2025-09-21 07:35:15'),
(5, 'procurement', 'Procurement Office - limited sidebar and access', '2025-10-23 07:45:14', '2025-10-23 07:45:14'),
(8, 'supply_office', 'Supply Office - Handles purchase orders and inventory coordination', '2025-11-27 05:47:03', '2025-11-27 05:47:03');

-- --------------------------------------------------------

--
-- Table structure for table `role_permissions`
--

CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `role_permissions`
--

INSERT INTO `role_permissions` (`id`, `role_id`, `permission_id`, `created_at`) VALUES
(227, 1, 18, '2025-09-21 07:35:15'),
(228, 1, 22, '2025-09-21 07:35:15'),
(229, 1, 5, '2025-09-21 07:35:15'),
(230, 1, 30, '2025-09-21 07:35:15'),
(231, 1, 15, '2025-09-21 07:35:15'),
(232, 1, 11, '2025-09-21 07:35:15'),
(233, 1, 19, '2025-09-21 07:35:15'),
(234, 1, 6, '2025-09-21 07:35:15'),
(235, 1, 1, '2025-09-21 07:35:15'),
(236, 1, 13, '2025-09-21 07:35:15'),
(237, 1, 8, '2025-09-21 07:35:15'),
(238, 1, 3, '2025-09-21 07:35:15'),
(239, 1, 16, '2025-09-21 07:35:15'),
(240, 1, 12, '2025-09-21 07:35:15'),
(241, 1, 20, '2025-09-21 07:35:15'),
(242, 1, 7, '2025-09-21 07:35:15'),
(243, 1, 2, '2025-09-21 07:35:15'),
(244, 1, 25, '2025-09-21 07:35:15'),
(245, 1, 24, '2025-09-21 07:35:15'),
(246, 1, 31, '2025-09-21 07:35:15'),
(247, 1, 10, '2025-09-21 07:35:15'),
(248, 1, 29, '2025-09-21 07:35:15'),
(249, 1, 32, '2025-09-21 07:35:15'),
(250, 1, 27, '2025-09-21 07:35:15'),
(251, 1, 17, '2025-09-21 07:35:15'),
(252, 1, 26, '2025-09-21 07:35:15'),
(253, 1, 14, '2025-09-21 07:35:15'),
(254, 1, 28, '2025-09-21 07:35:15'),
(255, 1, 21, '2025-09-21 07:35:15'),
(256, 1, 23, '2025-09-21 07:35:15'),
(257, 1, 9, '2025-09-21 07:35:15'),
(258, 1, 4, '2025-09-21 07:35:15'),
(297, 3, 19, '2025-09-21 07:35:15'),
(298, 3, 20, '2025-09-21 07:35:15'),
(299, 3, 17, '2025-09-21 07:35:15'),
(300, 3, 26, '2025-09-21 07:35:15'),
(301, 3, 14, '2025-09-21 07:35:15'),
(302, 3, 28, '2025-09-21 07:35:15'),
(303, 3, 21, '2025-09-21 07:35:15'),
(304, 3, 23, '2025-09-21 07:35:15'),
(305, 3, 4, '2025-09-21 07:35:15'),
(316, 5, 17, '2025-10-23 07:45:14'),
(317, 5, 26, '2025-10-23 07:45:14'),
(318, 5, 14, '2025-10-23 07:45:14'),
(319, 5, 28, '2025-10-23 07:45:14'),
(320, 5, 21, '2025-10-23 07:45:14'),
(321, 5, 23, '2025-10-23 07:45:14'),
(322, 5, 4, '2025-10-23 07:45:14'),
(349, 8, 26, '2025-11-27 05:47:03'),
(350, 8, 28, '2025-11-27 05:47:03'),
(351, 8, 34, '2025-11-27 05:47:03');

-- --------------------------------------------------------

--
-- Table structure for table `saved_sheets`
--

CREATE TABLE `saved_sheets` (
  `id` int(11) NOT NULL,
  `sheet_name` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `headers` text NOT NULL,
  `columns` text NOT NULL,
  `data` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cell_formats` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `supplemental_files`
--

CREATE TABLE `supplemental_files` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `submitted_by` int(11) NOT NULL,
  `status` enum('pending','reviewed','processed') NOT NULL DEFAULT 'pending',
  `excel_file_name` varchar(255) NOT NULL,
  `excel_file_path` varchar(500) NOT NULL,
  `scan_file_name` varchar(255) NOT NULL,
  `scan_file_path` varchar(500) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `supplemental_files`
--

INSERT INTO `supplemental_files` (`id`, `department_id`, `submitted_by`, `status`, `excel_file_name`, `excel_file_path`, `scan_file_name`, `scan_file_path`, `submitted_at`) VALUES
(1, 13, 18, 'processed', 'Employee-Management-Sample-Data.xlsx', 'uploads/supplemental/excel/SUPPLEMENTAL_EXCEL_1764093415.xlsx', 'Copy of Physical Education Physical Fitness Educational Presentation in Bei_20251012_085051_0000.pdf', 'uploads/supplemental/scans/SUPPLEMENTAL_SCAN_1764093415.pdf', '2025-11-25 17:56:55'),
(2, 13, 18, 'pending', 'Employee-Management-Sample-Data.xlsx', 'uploads/supplemental/excel/SUPPLEMENTAL_EXCEL_1764093475.xlsx', 'Copy of Physical Education Physical Fitness Educational Presentation in Bei_20251012_085051_0000.pdf', 'uploads/supplemental/scans/SUPPLEMENTAL_SCAN_1764093475.pdf', '2025-11-25 17:57:55'),
(3, 13, 18, 'pending', 'Employee-Management-Sample-Data.xlsx', 'uploads/supplemental/excel/SUPPLEMENTAL_EXCEL_1764093495.xlsx', 'Copy of Physical Education Physical Fitness Educational Presentation in Bei_20251012_085051_0000.pdf', 'uploads/supplemental/scans/SUPPLEMENTAL_SCAN_1764093495.pdf', '2025-11-25 17:58:15');

-- --------------------------------------------------------

--
-- Table structure for table `temporary_office_allocations`
--

CREATE TABLE `temporary_office_allocations` (
  `id` int(11) NOT NULL,
  `office_id` int(11) NOT NULL,
  `budget_allocated` varchar(50) DEFAULT NULL,
  `allocation_data` longtext NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `middle_name` varchar(50) DEFAULT NULL,
  `employee_id` varchar(20) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` timestamp NULL DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `profile_photo` varchar(500) DEFAULT NULL,
  `password_change_required` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `email`, `password_hash`, `first_name`, `last_name`, `middle_name`, `employee_id`, `department_id`, `role_id`, `is_active`, `last_login`, `created_by`, `created_at`, `updated_at`, `profile_photo`, `password_change_required`) VALUES
(5, 'budget@evsu.edu.ph', '$2y$10$eRpG2g0Gs5IfknmV/c4fDeOn0bAEE8QS0kc26TmRWWtaAZ3zL7Kem', 'Super', 'Admin', NULL, 'BUDGET001', NULL, 1, 1, '2026-01-14 01:21:08', NULL, '2025-09-21 07:35:15', '2026-01-14 01:21:08', 'uploads/profile_photos/profile_5_1764328234.jpg', 0),
(25, 'lovely.funa@evsu.edu.ph', '$2y$10$AUMHvt39md5mEjCSkwK8Aer39ppwJAXBgvJl5UoksjIbmGeIJYty6', 'Lovely', 'Aseo', 'Funa', 'F090121LR', 26, 1, 1, NULL, 5, '2025-11-27 07:35:57', '2025-11-27 07:35:57', NULL, 0),
(32, 'budget@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Budget', 'Office', '', NULL, 26, 1, 1, '2026-01-12 17:21:58', NULL, '2025-11-29 05:42:58', '2026-01-12 17:21:58', NULL, 0),
(34, 'bac@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Procurement', 'Office', '', NULL, 16, 5, 1, '2025-12-30 15:20:18', 32, '2025-11-29 05:42:58', '2025-12-30 15:20:18', NULL, 0),
(36, 'supply@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Supply', 'Office', '', NULL, 25, 8, 1, '2025-12-30 15:19:40', 32, '2025-11-29 05:42:58', '2025-12-30 15:19:40', NULL, 0),
(38, 'dept1@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'One', '', NULL, 13, 3, 1, '2026-01-13 19:16:40', 32, '2025-11-29 05:42:58', '2026-01-13 19:16:40', NULL, 0),
(39, 'dept2@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'Two', '', NULL, 18, 3, 1, '2025-12-29 21:18:07', 32, '2025-11-29 05:42:58', '2025-12-29 21:18:07', NULL, 0),
(40, 'dept3@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'Three', '', NULL, 20, 3, 1, NULL, 32, '2025-11-29 05:42:58', '2025-11-29 05:45:12', NULL, 0),
(42, 'jmhumbid@gmail.com', '$2y$10$dQGp4o7JCgc.Br1ugWw/E..hE6tm66UCp.JEId6xq8ifs8VLmf5ji', 'Mark Joseph', 'Humbid', '', NULL, 26, 1, 1, '2026-01-05 13:49:24', 5, '2026-01-05 13:48:09', '2026-01-05 13:49:24', NULL, 0);

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `activity_type` enum('login','logout','password_change','profile_update') NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `activity_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`activity_details`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `activity_type`, `ip_address`, `user_agent`, `activity_details`, `created_at`) VALUES
(1, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-13 16:13:48\",\"action\":\"user_login\"}', '2025-12-13 15:13:48'),
(2, 32, '', NULL, NULL, '{\"module\":\"Utilization\",\"file_name\":\"2025 OFFICES UTILIZATION.xlsx\",\"action\":\"attached\",\"year\":\"2025\",\"context\":\"all departments\"}', '2025-12-13 17:02:35'),
(3, 34, '', NULL, NULL, '{\"module\":\"Utilization\",\"file_name\":\"2025 OFFICES UTILIZATION.xlsx\",\"action\":\"attached\",\"year\":\"2025\",\"context\":\"all departments\"}', '2025-12-13 17:02:35'),
(4, 36, '', NULL, NULL, '{\"module\":\"Utilization\",\"file_name\":\"2025 OFFICES UTILIZATION.xlsx\",\"action\":\"attached\",\"year\":\"2025\",\"context\":\"all departments\"}', '2025-12-13 17:02:35'),
(5, 38, '', NULL, NULL, '{\"module\":\"Utilization\",\"file_name\":\"2025 OFFICES UTILIZATION.xlsx\",\"action\":\"attached\",\"year\":\"2025\",\"context\":\"all departments\"}', '2025-12-13 17:02:35'),
(6, 39, '', NULL, NULL, '{\"module\":\"Utilization\",\"file_name\":\"2025 OFFICES UTILIZATION.xlsx\",\"action\":\"attached\",\"year\":\"2025\",\"context\":\"all departments\"}', '2025-12-13 17:02:35'),
(7, 40, '', NULL, NULL, '{\"module\":\"Utilization\",\"file_name\":\"2025 OFFICES UTILIZATION.xlsx\",\"action\":\"attached\",\"year\":\"2025\",\"context\":\"all departments\"}', '2025-12-13 17:02:35'),
(8, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-13 21:50:50\",\"action\":\"user_login\"}', '2025-12-13 20:50:50'),
(9, 39, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-13 21:51:24\",\"action\":\"user_logout\"}', '2025-12-13 20:51:24'),
(10, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-13 21:51:48\",\"action\":\"user_login\"}', '2025-12-13 20:51:48'),
(11, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-15 12:59:32\",\"action\":\"user_login\"}', '2025-12-15 11:59:32'),
(12, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-15 13:31:34\",\"action\":\"user_login\"}', '2025-12-15 12:31:34'),
(13, 39, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-15 14:46:14\",\"action\":\"user_logout\"}', '2025-12-15 13:46:14'),
(14, 34, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-15 14:46:47\",\"action\":\"user_login\"}', '2025-12-15 13:46:47'),
(15, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-16 11:50:44\",\"action\":\"user_login\"}', '2025-12-16 10:50:44'),
(16, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-16 15:24:20\",\"action\":\"user_login\"}', '2025-12-16 14:24:20'),
(17, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-22 10:32:23\",\"action\":\"user_login\"}', '2025-12-22 09:32:23'),
(18, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-22 10:34:11\",\"action\":\"user_login\"}', '2025-12-22 09:34:11'),
(19, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-23 14:16:04\",\"action\":\"user_logout\"}', '2025-12-23 13:16:04'),
(20, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-23 14:16:13\",\"action\":\"user_login\"}', '2025-12-23 13:16:13'),
(21, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '{\"timestamp\":\"2025-12-23 16:21:55\",\"action\":\"user_login\"}', '2025-12-23 15:21:55'),
(22, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-26 08:41:18\",\"action\":\"user_login\"}', '2025-12-26 07:41:18'),
(23, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-26 09:24:28\",\"action\":\"user_login\"}', '2025-12-26 08:24:28'),
(24, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-26 18:26:26\",\"action\":\"user_login\"}', '2025-12-26 17:26:26'),
(25, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-26 19:10:20\",\"action\":\"user_login\"}', '2025-12-26 18:10:20'),
(26, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-26 22:47:02\",\"action\":\"user_login\"}', '2025-12-26 21:47:02'),
(27, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 03:37:01\",\"action\":\"user_login\"}', '2025-12-28 02:37:01'),
(28, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 03:51:11\",\"action\":\"user_login\"}', '2025-12-28 02:51:11'),
(29, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 04:50:34\",\"action\":\"user_login\"}', '2025-12-28 03:50:34'),
(30, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 04:59:27\",\"action\":\"user_login\"}', '2025-12-28 03:59:27'),
(31, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 06:48:55\",\"action\":\"user_login\"}', '2025-12-28 05:48:55'),
(32, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 07:04:03\",\"action\":\"user_login\"}', '2025-12-28 06:04:03'),
(33, 39, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 07:05:19\",\"action\":\"user_logout\"}', '2025-12-28 06:05:19'),
(34, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 07:05:24\",\"action\":\"user_login\"}', '2025-12-28 06:05:24'),
(35, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 07:05:33\",\"action\":\"user_login\"}', '2025-12-28 06:05:33'),
(36, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 18:24:26\",\"action\":\"user_login\"}', '2025-12-28 17:24:26'),
(37, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 19:46:10\",\"action\":\"user_logout\"}', '2025-12-28 18:46:10'),
(38, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-28 19:46:22\",\"action\":\"user_login\"}', '2025-12-28 18:46:22'),
(39, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 03:39:16\",\"action\":\"user_login\"}', '2025-12-29 02:39:16'),
(40, 39, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 13:25:22\",\"action\":\"user_logout\"}', '2025-12-29 12:25:22'),
(41, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 13:25:39\",\"action\":\"user_login\"}', '2025-12-29 12:25:39'),
(42, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 16:59:22\",\"action\":\"user_login\"}', '2025-12-29 15:59:22'),
(43, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 19:36:58\",\"action\":\"user_logout\"}', '2025-12-29 18:36:58'),
(44, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 19:37:05\",\"action\":\"user_login\"}', '2025-12-29 18:37:05'),
(45, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 19:37:11\",\"action\":\"user_logout\"}', '2025-12-29 18:37:11'),
(46, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 19:37:18\",\"action\":\"user_login\"}', '2025-12-29 18:37:18'),
(47, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 20:21:23\",\"action\":\"user_login\"}', '2025-12-29 19:21:23'),
(48, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 22:17:54\",\"action\":\"user_logout\"}', '2025-12-29 21:17:54'),
(49, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-29 22:18:07\",\"action\":\"user_login\"}', '2025-12-29 21:18:07'),
(50, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 13:59:22\",\"action\":\"user_login\"}', '2025-12-30 12:59:22'),
(51, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 14:01:12\",\"action\":\"user_login\"}', '2025-12-30 13:01:12'),
(52, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 14:01:29\",\"action\":\"user_login\"}', '2025-12-30 13:01:29'),
(53, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 14:04:07\",\"action\":\"user_login\"}', '2025-12-30 13:04:07'),
(54, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 15:44:46\",\"action\":\"user_login\"}', '2025-12-30 14:44:46'),
(55, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 15:52:25\",\"action\":\"user_login\"}', '2025-12-30 14:52:25'),
(56, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 15:52:58\",\"action\":\"user_login\"}', '2025-12-30 14:52:58'),
(57, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 16:19:31\",\"action\":\"user_logout\"}', '2025-12-30 15:19:31'),
(58, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 16:19:40\",\"action\":\"user_login\"}', '2025-12-30 15:19:40'),
(59, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 16:20:06\",\"action\":\"user_logout\"}', '2025-12-30 15:20:06'),
(60, 34, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2025-12-30 16:20:18\",\"action\":\"user_login\"}', '2025-12-30 15:20:18'),
(61, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-02 10:57:17\",\"action\":\"user_login\"}', '2026-01-02 09:57:17'),
(62, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 08:21:10\",\"action\":\"user_login\"}', '2026-01-03 07:21:10'),
(63, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 08:38:05\",\"action\":\"user_logout\"}', '2026-01-03 07:38:05'),
(64, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 08:38:19\",\"action\":\"user_login\"}', '2026-01-03 07:38:19'),
(65, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:06:45\",\"action\":\"user_logout\"}', '2026-01-03 09:06:45'),
(66, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:06:49\",\"action\":\"user_logout\"}', '2026-01-03 09:06:49'),
(67, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:06:57\",\"action\":\"user_login\"}', '2026-01-03 09:06:57'),
(68, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:07:05\",\"action\":\"user_login\"}', '2026-01-03 09:07:05'),
(69, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:07:28\",\"action\":\"user_login\"}', '2026-01-03 09:07:28'),
(70, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:21:12\",\"action\":\"user_logout\"}', '2026-01-03 09:21:12'),
(71, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:21:15\",\"action\":\"user_logout\"}', '2026-01-03 09:21:15'),
(72, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:21:27\",\"action\":\"user_login\"}', '2026-01-03 09:21:27'),
(73, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 10:21:35\",\"action\":\"user_login\"}', '2026-01-03 09:21:35'),
(74, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 18:35:42\",\"action\":\"user_logout\"}', '2026-01-03 17:35:42'),
(75, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-03 18:35:50\",\"action\":\"user_login\"}', '2026-01-03 17:35:50'),
(76, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-04 06:22:31\",\"action\":\"user_login\"}', '2026-01-04 05:22:31'),
(77, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-04 08:54:21\",\"action\":\"user_login\"}', '2026-01-04 07:54:21'),
(78, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-04 09:32:45\",\"action\":\"user_logout\"}', '2026-01-04 08:32:45'),
(79, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-04 09:32:59\",\"action\":\"user_login\"}', '2026-01-04 08:32:59'),
(80, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-04 17:26:25\",\"action\":\"user_login\"}', '2026-01-04 16:26:25'),
(81, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-04 17:26:46\",\"action\":\"user_logout\"}', '2026-01-04 16:26:46'),
(82, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-04 17:27:12\",\"action\":\"user_login\"}', '2026-01-04 16:27:12'),
(83, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-05 14:43:02\",\"action\":\"user_login\"}', '2026-01-05 13:43:02'),
(84, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-05 14:46:55\",\"action\":\"user_login\"}', '2026-01-05 13:46:55'),
(85, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-05 14:49:19\",\"action\":\"user_logout\"}', '2026-01-05 13:49:19'),
(86, 42, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-05 14:49:24\",\"action\":\"user_login\"}', '2026-01-05 13:49:24'),
(87, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-06 01:11:39\",\"action\":\"user_login\"}', '2026-01-06 00:11:39'),
(88, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-06 02:02:16\",\"action\":\"user_login\"}', '2026-01-06 01:02:16'),
(89, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-06 05:26:49\",\"action\":\"user_login\"}', '2026-01-06 04:26:49'),
(90, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 15:34:37\",\"action\":\"user_login\"}', '2026-01-07 14:34:37'),
(91, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 15:36:37\",\"action\":\"user_logout\"}', '2026-01-07 14:36:37'),
(92, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 15:40:11\",\"action\":\"user_login\"}', '2026-01-07 14:40:11'),
(93, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 15:41:04\",\"action\":\"user_logout\"}', '2026-01-07 14:41:04'),
(94, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 15:41:10\",\"action\":\"user_login\"}', '2026-01-07 14:41:10'),
(95, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 15:42:01\",\"action\":\"user_logout\"}', '2026-01-07 14:42:01'),
(96, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 15:42:13\",\"action\":\"user_login\"}', '2026-01-07 14:42:13'),
(97, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 16:03:23\",\"action\":\"user_logout\"}', '2026-01-07 15:03:23'),
(98, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 16:03:39\",\"action\":\"user_login\"}', '2026-01-07 15:03:39'),
(99, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-07 16:03:56\",\"action\":\"user_logout\"}', '2026-01-07 15:03:56'),
(100, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-10 11:30:22\",\"action\":\"user_login\"}', '2026-01-10 10:30:22'),
(101, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-10 21:25:57\",\"action\":\"user_login\"}', '2026-01-10 20:25:57'),
(102, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 12:25:03\",\"action\":\"user_login\"}', '2026-01-12 11:25:03'),
(103, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 17:24:36\",\"action\":\"user_login\"}', '2026-01-12 16:24:36'),
(104, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.106.0 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 18:13:12\",\"action\":\"user_login\"}', '2026-01-12 17:13:12'),
(105, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.106.0 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 18:13:26\",\"action\":\"user_login\"}', '2026-01-12 17:13:26'),
(106, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Code/1.106.0 Chrome/138.0.7204.251 Electron/37.7.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 18:14:00\",\"action\":\"user_login\"}', '2026-01-12 17:14:00'),
(107, 32, 'logout', '::1', 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Mobile Safari/537.36', '{\"timestamp\":\"2026-01-12 18:14:23\",\"action\":\"user_logout\"}', '2026-01-12 17:14:23'),
(108, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 18:14:32\",\"action\":\"user_login\"}', '2026-01-12 17:14:32'),
(109, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 18:21:47\",\"action\":\"user_logout\"}', '2026-01-12 17:21:47'),
(110, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 18:21:58\",\"action\":\"user_login\"}', '2026-01-12 17:21:58'),
(111, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 21:14:04\",\"action\":\"user_login\"}', '2026-01-12 20:14:04'),
(112, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 21:14:56\",\"action\":\"user_login\"}', '2026-01-12 20:14:56'),
(113, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-12 21:41:43\",\"action\":\"user_login\"}', '2026-01-12 20:41:43'),
(114, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-13 10:53:49\",\"action\":\"user_login\"}', '2026-01-13 09:53:49'),
(115, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-13 13:57:51\",\"action\":\"user_login\"}', '2026-01-13 12:57:51'),
(116, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-13 19:16:11\",\"action\":\"user_login\"}', '2026-01-13 18:16:11'),
(117, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-13 19:17:18\",\"action\":\"user_login\"}', '2026-01-13 18:17:18'),
(118, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-13 19:18:36\",\"action\":\"user_login\"}', '2026-01-13 18:18:36'),
(119, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-13 19:24:59\",\"action\":\"user_login\"}', '2026-01-13 18:24:59'),
(120, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-13 20:16:40\",\"action\":\"user_login\"}', '2026-01-13 19:16:40'),
(121, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-14 02:21:08\",\"action\":\"user_login\"}', '2026-01-14 01:21:08');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `session_token` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `expires_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilization_amount_deductions`
--

CREATE TABLE `utilization_amount_deductions` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `source_entry_id` int(11) NOT NULL,
  `date` varchar(7) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `deducted_from_entry_id` int(11) DEFAULT NULL,
  `fiscal_year` year(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilization_history`
--

CREATE TABLE `utilization_history` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL,
  `file_type` varchar(100) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `status` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilization_honoraria`
--

CREATE TABLE `utilization_honoraria` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `date` varchar(7) DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `is_deducted` tinyint(1) DEFAULT 0,
  `fiscal_year` year(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilization_honoraria`
--

INSERT INTO `utilization_honoraria` (`id`, `department_id`, `date`, `amount`, `is_deducted`, `fiscal_year`, `created_by`, `created_at`, `updated_at`) VALUES
(112, 26, '2026-02', 1000.00, 0, '2026', 42, '2026-01-05 20:39:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `utilization_purchase_requests`
--

CREATE TABLE `utilization_purchase_requests` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `purchase_request` varchar(255) NOT NULL,
  `particulars` text DEFAULT NULL,
  `pr_number` varchar(100) DEFAULT NULL,
  `po_number` varchar(100) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `entry_id` int(11) DEFAULT NULL,
  `fiscal_year` year(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilization_summaries`
--

CREATE TABLE `utilization_summaries` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` year(4) NOT NULL,
  `department_name` varchar(255) NOT NULL,
  `utilization_entries` text DEFAULT NULL,
  `pr_entries` text DEFAULT NULL,
  `travels_entries` text DEFAULT NULL,
  `honoraria_entries` text DEFAULT NULL,
  `pr_deductions` text DEFAULT NULL,
  `travels_deductions` text DEFAULT NULL,
  `honoraria_deductions` text DEFAULT NULL,
  `totals` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `utilization_travels`
--

CREATE TABLE `utilization_travels` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `travelled` varchar(255) NOT NULL,
  `event_activity` text DEFAULT NULL,
  `date` date DEFAULT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `fiscal_year` year(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_deducted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendors`
--

CREATE TABLE `vendors` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `contact` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `allocations_files`
--
ALTER TABLE `allocations_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`),
  ADD KEY `idx_department_id` (`department_id`);

--
-- Indexes for table `allocations_history`
--
ALTER TABLE `allocations_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `allocation_grid_metadata`
--
ALTER TABLE `allocation_grid_metadata`
  ADD PRIMARY KEY (`fiscal_year`),
  ADD KEY `updated_by` (`updated_by`);

--
-- Indexes for table `announcements`
--
ALTER TABLE `announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_announcement_department` (`announcement_id`,`department_id`),
  ADD KEY `idx_announcement_id` (`announcement_id`),
  ADD KEY `idx_department_id` (`department_id`);

--
-- Indexes for table `automated_reports`
--
ALTER TABLE `automated_reports`
  ADD PRIMARY KEY (`id`),
  ADD KEY `generated_by` (`generated_by`),
  ADD KEY `idx_report_type` (`report_type`,`report_period_start`),
  ADD KEY `idx_generated_at` (`generated_at`);

--
-- Indexes for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_dept_fiscal` (`department_id`,`fiscal_year`);

--
-- Indexes for table `budget_categories`
--
ALTER TABLE `budget_categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `category_code` (`category_code`);

--
-- Indexes for table `budget_files`
--
ALTER TABLE `budget_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `budget_utilization_entries`
--
ALTER TABLE `budget_utilization_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `cabac_fiduciary_entries`
--
ALTER TABLE `cabac_fiduciary_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_sub_particular` (`sub_particular`);

--
-- Indexes for table `cabac_fiduciary_programs`
--
ALTER TABLE `cabac_fiduciary_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sub_particular_id` (`sub_particular_id`);

--
-- Indexes for table `cabac_fiduciary_sub_particulars`
--
ALTER TABLE `cabac_fiduciary_sub_particulars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entry_id` (`entry_id`);

--
-- Indexes for table `cabac_files`
--
ALTER TABLE `cabac_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_uploaded_by` (`uploaded_by`);

--
-- Indexes for table `cabac_history`
--
ALTER TABLE `cabac_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `cabac_non_fiduciary_entries`
--
ALTER TABLE `cabac_non_fiduciary_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_sub_particular` (`sub_particular`);

--
-- Indexes for table `cabac_non_fiduciary_programs`
--
ALTER TABLE `cabac_non_fiduciary_programs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_sub_particular_id` (`sub_particular_id`);

--
-- Indexes for table `cabac_non_fiduciary_sub_particulars`
--
ALTER TABLE `cabac_non_fiduciary_sub_particulars`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_entry_id` (`entry_id`);

--
-- Indexes for table `cabac_programs`
--
ALTER TABLE `cabac_programs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_program` (`program_name`,`type`);

--
-- Indexes for table `cabac_programs_entries`
--
ALTER TABLE `cabac_programs_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_programs` (`programs_id`);

--
-- Indexes for table `cabac_program_entries`
--
ALTER TABLE `cabac_program_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cabac_program` (`program_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dept_code` (`dept_code`);

--
-- Indexes for table `department_budgets`
--
ALTER TABLE `department_budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dept_year` (`department_id`,`fiscal_year`);

--
-- Indexes for table `file_submissions`
--
ALTER TABLE `file_submissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reviewed_by` (`reviewed_by`),
  ADD KEY `idx_file_submissions_user` (`user_id`),
  ADD KEY `idx_file_submissions_dept` (`department_id`),
  ADD KEY `idx_file_submissions_type` (`submission_type`),
  ADD KEY `idx_file_submissions_status` (`status`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_expires_at` (`expires_at`);

--
-- Indexes for table `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `permission_name` (`permission_name`);

--
-- Indexes for table `procurement_announcements`
--
ALTER TABLE `procurement_announcements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `target_department_id` (`target_department_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `proposed_budget_files`
--
ALTER TABLE `proposed_budget_files`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `proposed_budget_history`
--
ALTER TABLE `proposed_budget_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pr_number` (`pr_number`),
  ADD KEY `procurement_user_id` (`procurement_user_id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_submitted_at` (`submitted_at`);

--
-- Indexes for table `purchase_request_files`
--
ALTER TABLE `purchase_request_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pr_id` (`purchase_request_id`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role_name` (`role_name`);

--
-- Indexes for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_role_permission` (`role_id`,`permission_id`),
  ADD KEY `permission_id` (`permission_id`);

--
-- Indexes for table `saved_sheets`
--
ALTER TABLE `saved_sheets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `supplemental_files`
--
ALTER TABLE `supplemental_files`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department_id` (`department_id`),
  ADD KEY `idx_status` (`status`);

--
-- Indexes for table `temporary_office_allocations`
--
ALTER TABLE `temporary_office_allocations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_office` (`office_id`),
  ADD KEY `office_id` (`office_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `employee_id` (`employee_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user_activity` (`user_id`,`created_at`),
  ADD KEY `idx_activity_type` (`activity_type`,`created_at`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_token` (`session_token`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `utilization_amount_deductions`
--
ALTER TABLE `utilization_amount_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_source_entry` (`source_entry_id`),
  ADD KEY `idx_deducted_from` (`deducted_from_entry_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `utilization_history`
--
ALTER TABLE `utilization_history`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `utilization_honoraria`
--
ALTER TABLE `utilization_honoraria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `utilization_purchase_requests`
--
ALTER TABLE `utilization_purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `utilization_summaries`
--
ALTER TABLE `utilization_summaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `utilization_travels`
--
ALTER TABLE `utilization_travels`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`);

--
-- Indexes for table `vendors`
--
ALTER TABLE `vendors`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `activity_logs`
--
ALTER TABLE `activity_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `allocations_files`
--
ALTER TABLE `allocations_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=53;

--
-- AUTO_INCREMENT for table `allocations_history`
--
ALTER TABLE `allocations_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcements`
--
ALTER TABLE `announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `automated_reports`
--
ALTER TABLE `automated_reports`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `budget_categories`
--
ALTER TABLE `budget_categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `budget_files`
--
ALTER TABLE `budget_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `budget_utilization_entries`
--
ALTER TABLE `budget_utilization_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23614;

--
-- AUTO_INCREMENT for table `cabac_fiduciary_entries`
--
ALTER TABLE `cabac_fiduciary_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `cabac_fiduciary_programs`
--
ALTER TABLE `cabac_fiduciary_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cabac_fiduciary_sub_particulars`
--
ALTER TABLE `cabac_fiduciary_sub_particulars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cabac_files`
--
ALTER TABLE `cabac_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `cabac_history`
--
ALTER TABLE `cabac_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cabac_non_fiduciary_entries`
--
ALTER TABLE `cabac_non_fiduciary_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=83;

--
-- AUTO_INCREMENT for table `cabac_non_fiduciary_programs`
--
ALTER TABLE `cabac_non_fiduciary_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `cabac_non_fiduciary_sub_particulars`
--
ALTER TABLE `cabac_non_fiduciary_sub_particulars`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `cabac_programs`
--
ALTER TABLE `cabac_programs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `cabac_programs_entries`
--
ALTER TABLE `cabac_programs_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cabac_program_entries`
--
ALTER TABLE `cabac_program_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=363;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=29;

--
-- AUTO_INCREMENT for table `department_budgets`
--
ALTER TABLE `department_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `file_submissions`
--
ALTER TABLE `file_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `procurement_announcements`
--
ALTER TABLE `procurement_announcements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `proposed_budget_files`
--
ALTER TABLE `proposed_budget_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT for table `proposed_budget_history`
--
ALTER TABLE `proposed_budget_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `purchase_request_files`
--
ALTER TABLE `purchase_request_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `role_permissions`
--
ALTER TABLE `role_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=352;

--
-- AUTO_INCREMENT for table `saved_sheets`
--
ALTER TABLE `saved_sheets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `supplemental_files`
--
ALTER TABLE `supplemental_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `temporary_office_allocations`
--
ALTER TABLE `temporary_office_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=122;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `utilization_amount_deductions`
--
ALTER TABLE `utilization_amount_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `utilization_history`
--
ALTER TABLE `utilization_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `utilization_honoraria`
--
ALTER TABLE `utilization_honoraria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=766;

--
-- AUTO_INCREMENT for table `utilization_purchase_requests`
--
ALTER TABLE `utilization_purchase_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=970;

--
-- AUTO_INCREMENT for table `utilization_summaries`
--
ALTER TABLE `utilization_summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `utilization_travels`
--
ALTER TABLE `utilization_travels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

--
-- AUTO_INCREMENT for table `vendors`
--
ALTER TABLE `vendors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `activity_logs`
--
ALTER TABLE `activity_logs`
  ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `allocation_grid_metadata`
--
ALTER TABLE `allocation_grid_metadata`
  ADD CONSTRAINT `allocation_grid_metadata_ibfk_1` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `announcements`
--
ALTER TABLE `announcements`
  ADD CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `announcement_departments`
--
ALTER TABLE `announcement_departments`
  ADD CONSTRAINT `announcement_departments_ibfk_1` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `announcement_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `automated_reports`
--
ALTER TABLE `automated_reports`
  ADD CONSTRAINT `automated_reports_ibfk_1` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `budget_allocations`
--
ALTER TABLE `budget_allocations`
  ADD CONSTRAINT `budget_allocations_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `budget_allocations_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `budget_utilization_entries`
--
ALTER TABLE `budget_utilization_entries`
  ADD CONSTRAINT `fk_util_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_util_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cabac_fiduciary_programs`
--
ALTER TABLE `cabac_fiduciary_programs`
  ADD CONSTRAINT `cabac_fiduciary_programs_ibfk_1` FOREIGN KEY (`sub_particular_id`) REFERENCES `cabac_fiduciary_sub_particulars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cabac_fiduciary_sub_particulars`
--
ALTER TABLE `cabac_fiduciary_sub_particulars`
  ADD CONSTRAINT `cabac_fiduciary_sub_particulars_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `cabac_fiduciary_entries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cabac_non_fiduciary_programs`
--
ALTER TABLE `cabac_non_fiduciary_programs`
  ADD CONSTRAINT `cabac_non_fiduciary_programs_ibfk_1` FOREIGN KEY (`sub_particular_id`) REFERENCES `cabac_non_fiduciary_sub_particulars` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cabac_non_fiduciary_sub_particulars`
--
ALTER TABLE `cabac_non_fiduciary_sub_particulars`
  ADD CONSTRAINT `cabac_non_fiduciary_sub_particulars_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `cabac_non_fiduciary_entries` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cabac_programs_entries`
--
ALTER TABLE `cabac_programs_entries`
  ADD CONSTRAINT `fk_programs` FOREIGN KEY (`programs_id`) REFERENCES `cabac_programs` (`id`) ON UPDATE CASCADE;

--
-- Constraints for table `cabac_program_entries`
--
ALTER TABLE `cabac_program_entries`
  ADD CONSTRAINT `fk_cabac_program` FOREIGN KEY (`program_id`) REFERENCES `cabac_programs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `department_budgets`
--
ALTER TABLE `department_budgets`
  ADD CONSTRAINT `department_budgets_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `file_submissions`
--
ALTER TABLE `file_submissions`
  ADD CONSTRAINT `file_submissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_submissions_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_submissions_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `password_reset_tokens`
--
ALTER TABLE `password_reset_tokens`
  ADD CONSTRAINT `password_reset_tokens_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_ibfk_1` FOREIGN KEY (`procurement_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_requests_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_request_files`
--
ALTER TABLE `purchase_request_files`
  ADD CONSTRAINT `purchase_request_files_ibfk_1` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `role_permissions`
--
ALTER TABLE `role_permissions`
  ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `saved_sheets`
--
ALTER TABLE `saved_sheets`
  ADD CONSTRAINT `saved_sheets_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `saved_sheets_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`),
  ADD CONSTRAINT `users_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `user_activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `user_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `utilization_amount_deductions`
--
ALTER TABLE `utilization_amount_deductions`
  ADD CONSTRAINT `fk_amount_deducted_from` FOREIGN KEY (`deducted_from_entry_id`) REFERENCES `budget_utilization_entries` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_amount_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_amount_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `utilization_purchase_requests`
--
ALTER TABLE `utilization_purchase_requests`
  ADD CONSTRAINT `fk_pr_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pr_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `utilization_travels`
--
ALTER TABLE `utilization_travels`
  ADD CONSTRAINT `fk_travel_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_travel_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
