-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 20, 2026 at 02:38 AM
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
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
-- Table structure for table `allocation_drafts`
--

CREATE TABLE `allocation_drafts` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `draft_data` longtext NOT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `allocation_drafts`
--

INSERT INTO `allocation_drafts` (`id`, `department_id`, `draft_data`, `updated_by`, `updated_at`) VALUES
(1, 13, '{\"type\":\"department\",\"timestamp\":\"2026-04-15T06:02:28.201Z\",\"numStudents\":\"1144\",\"totalTuitionFee\":\"\\u20b12,879,700.00\",\"instructionalAmount\":\"\\u20b11,439,850.00\",\"additionalAmount\":\"\\u20b11,000,000.00\",\"additionalDescription\":\"\",\"nonFiduciary\":{\"facultyStaff\":{\"instructional\":\"\\u20b1251,973.75\",\"budgetAllocation\":\"\\u20b1-476,589.17\",\"deductions\":[{\"amount\":\"\\u20b1728,562.92\",\"remarks\":\"Honoraria Overload\"}]},\"curriculum\":{\"instructional\":\"\\u20b1251,973.75\",\"budgetAllocation\":\"\\u20b1-735,416.25\",\"deductions\":[{\"amount\":\"\\u20b1987,390.00\",\"remarks\":\"Part-time\"}]},\"student\":{\"instructional\":\"\\u20b1107,988.75\",\"budgetAllocation\":\"\\u20b1-83,411.25\",\"deductions\":[{\"amount\":\"\\u20b1191,400.00\",\"remarks\":\"Water\"}]},\"facilities\":{\"instructional\":\"\\u20b1107,988.75\",\"budgetAllocation\":\"\\u20b1-1,188,810.28\",\"deductions\":[{\"amount\":\"\\u20b1432,266.34\",\"remarks\":\"COS\"},{\"amount\":\"\\u20b1432,266.34\",\"remarks\":\"Security\"},{\"amount\":\"\\u20b1432,266.35\",\"remarks\":\"Electricity\"}]}},\"fiduciary\":{\"1\":{\"itemName\":\"Laboratory Fee\",\"instructional\":\"\\u20b11,416,600.00\",\"deductions\":[]},\"2\":{\"itemName\":\"Computer Fee\",\"instructional\":\"\\u20b1756,400.00\",\"deductions\":[]},\"3\":{\"itemName\":\"Computer Lab\",\"instructional\":\"\\u20b11,505,700.00\",\"deductions\":[]},\"4\":{\"itemName\":\"Internet Fee\",\"instructional\":\"\\u20b1114,400.00\",\"deductions\":[]},\"5\":{\"itemName\":\"CCNA\",\"instructional\":\"\\u20b1309,600.00\",\"deductions\":[]},\"6\":{\"itemName\":\"Development Fee\",\"instructional\":\"\\u20b1572,000.00\",\"deductions\":[]}}}', 5, '2026-04-15 06:02:28'),
(317, 15, '{\"type\":\"department\",\"timestamp\":\"2026-04-07T08:53:51.611Z\",\"numStudents\":\"3052\",\"totalTuitionFee\":\"\\u20b110,655,850.00\",\"instructionalAmount\":\"\\u20b15,327,925.00\",\"additionalAmount\":\"\\u20b1200,000.00\",\"additionalDescription\":\"wearedevs1\",\"nonFiduciary\":{\"facultyStaff\":{\"instructional\":\"\\u20b11,331,981.25\",\"budgetAllocation\":\"\\u20b1-494,055.70\",\"deductions\":[{\"amount\":\"\\u20b11,826,036.95\",\"remarks\":\"Honoraria Overload\"}]},\"curriculum\":{\"instructional\":\"\\u20b11,065,585.00\",\"budgetAllocation\":\"\\u20b1-3,980,715.00\",\"deductions\":[{\"amount\":\"\\u20b15,046,300.00\",\"remarks\":\"Part-time\"}]},\"student\":{\"instructional\":\"\\u20b11,065,585.00\",\"budgetAllocation\":\"\\u20b1681,985.00\",\"deductions\":[{\"amount\":\"\\u20b1191,800.00\",\"remarks\":\"Labor & Wages\"},{\"amount\":\"\\u20b1191,800.00\",\"remarks\":\"Water\"}]},\"facilities\":{\"instructional\":\"\\u20b11,065,585.00\",\"budgetAllocation\":\"\\u20b1166,875.18\",\"deductions\":[{\"amount\":\"\\u20b1449,354.91\",\"remarks\":\"Security\"},{\"amount\":\"\\u20b1449,354.91\",\"remarks\":\"Electricity\"}]}},\"fiduciary\":{\"1\":{\"itemName\":\"Laboratory Fee\",\"instructional\":\"\\u20b10.00\",\"deductions\":[]},\"2\":{\"itemName\":\"Computer Fee\",\"instructional\":\"\\u20b10.00\",\"deductions\":[]},\"3\":{\"itemName\":\"Computer Lab\",\"instructional\":\"\\u20b10.00\",\"deductions\":[]},\"4\":{\"itemName\":\"Internet Fee\",\"instructional\":\"\\u20b10.00\",\"deductions\":[]},\"5\":{\"itemName\":\"CCNA\",\"instructional\":\"\\u20b10.00\",\"deductions\":[]},\"6\":{\"itemName\":\"Development Fee\",\"instructional\":\"\\u20b10.00\",\"deductions\":[]}}}', 5, '2026-04-07 08:53:52'),
(529, 27, '{\"type\":\"department\",\"timestamp\":\"2026-04-07T07:34:15.545Z\",\"numStudents\":\"1415\",\"totalTuitionFee\":\"\\u20b13,641,250.00\",\"instructionalAmount\":\"\\u20b11,820,625.00\",\"additionalAmount\":\"\",\"additionalDescription\":\"\",\"nonFiduciary\":{\"facultyStaff\":{\"percent\":\"\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b1-994,475.66\",\"deductions\":[{\"amount\":\"\\u20b1994,475.66\",\"remarks\":\"Honoraria Overload\"}]},\"curriculum\":{\"percent\":\"\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b1-2,042,820.00\",\"deductions\":[{\"amount\":\"\\u20b12,042,820.00\",\"remarks\":\"Part-time\"}]},\"student\":{\"percent\":\"\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b1-87,000.00\",\"deductions\":[{\"amount\":\"\\u20b187,000.00\",\"remarks\":\"Water\"}]},\"facilities\":{\"percent\":\"\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b1-944,433.87\",\"deductions\":[{\"amount\":\"\\u20b1314,811.29\",\"remarks\":\"COS\"},{\"amount\":\"\\u20b1314,811.29\",\"remarks\":\"Electricity\"},{\"amount\":\"\\u20b1314,811.29\",\"remarks\":\"Security\"}]}},\"fiduciary\":{\"1\":{\"itemName\":\"Laboratory Fee\",\"instructional\":\"\\u20b11,430,550.00\",\"deductions\":[]},\"2\":{\"itemName\":\"Computer Fee\",\"instructional\":\"\\u20b196,000.00\",\"deductions\":[]},\"3\":{\"itemName\":\"Computer Lab\",\"instructional\":\"\\u20b1216,000.00\",\"deductions\":[]},\"4\":{\"itemName\":\"Internet Fee\",\"instructional\":\"\\u20b1141,500.00\",\"deductions\":[]},\"5\":{\"itemName\":\"\",\"instructional\":\"\",\"deductions\":[]},\"6\":{\"itemName\":\"Development Fee\",\"instructional\":\"\\u20b1707,500.00\",\"deductions\":[]}}}', 5, '2026-04-07 07:34:16'),
(590, 28, '{\"type\":\"department\",\"timestamp\":\"2026-04-07T07:18:25.692Z\",\"numStudents\":\"1,214\",\"totalTuitionFee\":\"\",\"instructionalAmount\":\"\\u20b10.00\",\"additionalAmount\":\"\",\"additionalDescription\":\"\",\"nonFiduciary\":{\"facultyStaff\":{\"percent\":\"35%\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b10.00\",\"deductions\":[]},\"curriculum\":{\"percent\":\"40%\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b10.00\",\"deductions\":[]},\"student\":{\"percent\":\"7.5%\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b10.00\",\"deductions\":[]},\"facilities\":{\"percent\":\"17.5%\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b10.00\",\"deductions\":[]}},\"fiduciary\":{\"1\":{\"itemName\":\"Laboratory Fee\",\"instructional\":\"\",\"deductions\":[]},\"2\":{\"itemName\":\"Computer Fee\",\"instructional\":\"\",\"deductions\":[]},\"3\":{\"itemName\":\"Computer Lab\",\"instructional\":\"\",\"deductions\":[]},\"4\":{\"itemName\":\"Internet Fee\",\"instructional\":\"\",\"deductions\":[]},\"5\":{\"itemName\":\"CCNA\",\"instructional\":\"\",\"deductions\":[]},\"6\":{\"itemName\":\"Development Fee\",\"instructional\":\"\",\"deductions\":[]}}}', 5, '2026-04-07 07:18:26'),
(644, 14, '{\"type\":\"department\",\"timestamp\":\"2026-04-07T07:25:39.974Z\",\"numStudents\":\"2347\",\"totalTuitionFee\":\"\\u20b17,396,500.00\",\"instructionalAmount\":\"\\u20b13,698,250.00\",\"additionalAmount\":\"\",\"additionalDescription\":\"\",\"nonFiduciary\":{\"facultyStaff\":{\"percent\":\"\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b1-829,104.98\",\"deductions\":[{\"amount\":\"\\u20b1829,104.98\",\"remarks\":\"Honoraria Overload\"}]},\"curriculum\":{\"percent\":\"\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b1-3,751,830.43\",\"deductions\":[{\"amount\":\"\\u20b13,751,830.43\",\"remarks\":\"Part-time\"}]},\"student\":{\"percent\":\"\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b1-36,000.00\",\"deductions\":[{\"amount\":\"\\u20b136,000.00\",\"remarks\":\"Water\"}]},\"facilities\":{\"percent\":\"\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b1-1,011,885.54\",\"deductions\":[{\"amount\":\"\\u20b1337,295.18\",\"remarks\":\"COS\"},{\"amount\":\"\\u20b1337,295.18\",\"remarks\":\"Security\"},{\"amount\":\"\\u20b1337,295.18\",\"remarks\":\"Electricity\"}]}},\"fiduciary\":{\"1\":{\"itemName\":\"Laboratory Fee\",\"instructional\":\"\\u20b13,409,200.00\",\"deductions\":[]},\"2\":{\"itemName\":\"Computer Fee\",\"instructional\":\"\\u20b184,000.00\",\"deductions\":[]},\"3\":{\"itemName\":\"Computer Lab\",\"instructional\":\"\\u20b1189,000.00\",\"deductions\":[]},\"4\":{\"itemName\":\"Internet Fee\",\"instructional\":\"\\u20b1234,700.00\",\"deductions\":[]},\"5\":{\"itemName\":\"CCNA\",\"instructional\":\"\\u20b10.00\",\"deductions\":[]},\"6\":{\"itemName\":\"Development Fee\",\"instructional\":\"\\u20b11,173,500.00\",\"deductions\":[]}}}', 5, '2026-04-07 07:25:40'),
(1109, 26, '{\"type\":\"office\",\"timestamp\":\"2026-03-23T05:55:27.089Z\",\"budgetAllocated\":\"\",\"deductions\":[]}', 5, '2026-03-23 05:55:27'),
(2312, 23, '{\"type\":\"office\",\"timestamp\":\"2026-04-07T06:24:48.048Z\",\"budgetAllocated\":\"\\u20b14,000,000.00\",\"deductions\":[]}', 5, '2026-04-07 06:24:48'),
(2313, 18, '{\"type\":\"office\",\"timestamp\":\"2026-03-23T05:55:06.462Z\",\"budgetAllocated\":\"\",\"deductions\":[]}', 5, '2026-03-23 05:55:06'),
(3274, 21, '{\"type\":\"office\",\"timestamp\":\"2026-03-23T05:56:03.737Z\",\"budgetAllocated\":\"\",\"deductions\":[]}', 5, '2026-03-23 05:56:04'),
(4826, 22, '{\"type\":\"department\",\"timestamp\":\"2026-04-07T06:33:42.538Z\",\"numStudents\":\"\",\"totalTuitionFee\":\"\",\"instructionalAmount\":\"\",\"additionalAmount\":\"\",\"additionalDescription\":\"\",\"nonFiduciary\":{\"facultyStaff\":{\"percent\":\"17.5\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b10.00\",\"deductions\":[]},\"curriculum\":{\"percent\":\"17.5\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b10.00\",\"deductions\":[]},\"student\":{\"percent\":\"7.5\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b10.00\",\"deductions\":[]},\"facilities\":{\"percent\":\"7.5\",\"instructional\":\"\\u20b10.00\",\"budgetAllocation\":\"\\u20b10.00\",\"deductions\":[]}},\"fiduciary\":{\"1\":{\"itemName\":\"\",\"instructional\":\"\",\"deductions\":[]},\"2\":{\"itemName\":\"\",\"instructional\":\"\",\"deductions\":[]},\"3\":{\"itemName\":\"\",\"instructional\":\"\",\"deductions\":[]},\"4\":{\"itemName\":\"\",\"instructional\":\"\",\"deductions\":[]},\"5\":{\"itemName\":\"\",\"instructional\":\"\",\"deductions\":[]},\"6\":{\"itemName\":\"\",\"instructional\":\"\",\"deductions\":[]}}}', 5, '2026-04-07 06:33:43');

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
  `additional_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `additional_description` text DEFAULT NULL,
  `allocation_data` longtext NOT NULL,
  `allocated_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `utilized_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `remaining_amount` decimal(15,2) GENERATED ALWAYS AS (`allocated_amount` - `utilized_amount`) STORED,
  `status` enum('active','inactive','closed') DEFAULT 'active',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_allocations`
--

INSERT INTO `budget_allocations` (`id`, `department_id`, `fiscal_year`, `num_students`, `total_tuition_fee`, `instructional_amount`, `budget_allocated`, `overall_total`, `additional_amount`, `additional_description`, `allocation_data`, `allocated_amount`, `utilized_amount`, `status`, `created_by`, `created_at`, `updated_at`) VALUES
(93, 13, '2027', 1144, 2879700.00, 1439850.00, 0.00, 5790473.05, 3600000.00, '', '{\"non_fiduciary\":{\"facultyStaff\":{\"percent\":\"17.5\",\"instructional\":\"\\u20b1251,973.75\",\"deductions\":[{\"amount\":\"\\u20b1728,562.92\",\"remarks\":\"Honoraria Overload\"}],\"budget_allocation\":\"\\u20b1-476,589.17\"},\"curriculum\":{\"percent\":\"17.5\",\"instructional\":\"\\u20b1251,973.75\",\"deductions\":[{\"amount\":\"\\u20b1987,390.00\",\"remarks\":\"Part-time\"}],\"budget_allocation\":\"\\u20b1-735,416.25\"},\"student\":{\"percent\":\"7.5\",\"instructional\":\"\\u20b1107,988.75\",\"deductions\":[{\"amount\":\"\\u20b1191,400.00\",\"remarks\":\"Water\"}],\"budget_allocation\":\"\\u20b1-83,411.25\"},\"facilities\":{\"percent\":\"7.5\",\"instructional\":\"\\u20b1107,988.75\",\"deductions\":[{\"amount\":\"\\u20b1432,266.34\",\"remarks\":\"COS\"},{\"amount\":\"\\u20b1432,266.34\",\"remarks\":\"Security\"},{\"amount\":\"\\u20b1432,266.35\",\"remarks\":\"Electricity\"}],\"budget_allocation\":\"\\u20b1-1,188,810.28\"}},\"fiduciary\":{\"1\":{\"item_name\":\"Laboratory Fee\",\"instructional\":\"\\u20b11,416,600.00\",\"deductions\":[],\"total_budget\":\"\\u20b11,416,600.00\"},\"2\":{\"item_name\":\"Computer Fee\",\"instructional\":\"\\u20b1756,400.00\",\"deductions\":[],\"total_budget\":\"\\u20b1756,400.00\"},\"3\":{\"item_name\":\"Computer Lab\",\"instructional\":\"\\u20b11,505,700.00\",\"deductions\":[],\"total_budget\":\"\\u20b11,505,700.00\"},\"4\":{\"item_name\":\"Internet Fee\",\"instructional\":\"\\u20b1114,400.00\",\"deductions\":[],\"total_budget\":\"\\u20b1114,400.00\"},\"5\":{\"item_name\":\"CCNA\",\"instructional\":\"\\u20b1309,600.00\",\"deductions\":[],\"total_budget\":\"\\u20b1309,600.00\"},\"6\":{\"item_name\":\"Development Fee\",\"instructional\":\"\\u20b1572,000.00\",\"deductions\":[],\"total_budget\":\"\\u20b1572,000.00\"}},\"is_office\":false}', 0.00, 0.00, 'active', 5, '2026-04-15 04:47:16', '2026-04-15 04:47:16'),
(94, 13, '2026', 1144, 2879700.00, 1439850.00, 0.00, 3190473.05, 1000000.00, '', '{\"non_fiduciary\":{\"facultyStaff\":{\"percent\":\"17.5\",\"instructional\":\"\\u20b1251,973.75\",\"deductions\":[{\"amount\":\"\\u20b1728,562.92\",\"remarks\":\"Honoraria Overload\"}],\"budget_allocation\":\"\\u20b1-476,589.17\"},\"curriculum\":{\"percent\":\"17.5\",\"instructional\":\"\\u20b1251,973.75\",\"deductions\":[{\"amount\":\"\\u20b1987,390.00\",\"remarks\":\"Part-time\"}],\"budget_allocation\":\"\\u20b1-735,416.25\"},\"student\":{\"percent\":\"7.5\",\"instructional\":\"\\u20b1107,988.75\",\"deductions\":[{\"amount\":\"\\u20b1191,400.00\",\"remarks\":\"Water\"}],\"budget_allocation\":\"\\u20b1-83,411.25\"},\"facilities\":{\"percent\":\"7.5\",\"instructional\":\"\\u20b1107,988.75\",\"deductions\":[{\"amount\":\"\\u20b1432,266.34\",\"remarks\":\"COS\"},{\"amount\":\"\\u20b1432,266.34\",\"remarks\":\"Security\"},{\"amount\":\"\\u20b1432,266.35\",\"remarks\":\"Electricity\"}],\"budget_allocation\":\"\\u20b1-1,188,810.28\"}},\"fiduciary\":{\"1\":{\"item_name\":\"Laboratory Fee\",\"instructional\":\"\\u20b11,416,600.00\",\"deductions\":[],\"total_budget\":\"\\u20b11,416,600.00\"},\"2\":{\"item_name\":\"Computer Fee\",\"instructional\":\"\\u20b1756,400.00\",\"deductions\":[],\"total_budget\":\"\\u20b1756,400.00\"},\"3\":{\"item_name\":\"Computer Lab\",\"instructional\":\"\\u20b11,505,700.00\",\"deductions\":[],\"total_budget\":\"\\u20b11,505,700.00\"},\"4\":{\"item_name\":\"Internet Fee\",\"instructional\":\"\\u20b1114,400.00\",\"deductions\":[],\"total_budget\":\"\\u20b1114,400.00\"},\"5\":{\"item_name\":\"CCNA\",\"instructional\":\"\\u20b1309,600.00\",\"deductions\":[],\"total_budget\":\"\\u20b1309,600.00\"},\"6\":{\"item_name\":\"Development Fee\",\"instructional\":\"\\u20b1572,000.00\",\"deductions\":[],\"total_budget\":\"\\u20b1572,000.00\"}},\"is_office\":false}', 0.00, 0.00, 'active', 5, '2026-04-15 06:01:22', '2026-04-15 06:01:22');

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
-- Table structure for table `budget_utilization_deduction_sources`
--

CREATE TABLE `budget_utilization_deduction_sources` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `entry_id` varchar(50) NOT NULL,
  `category_name` varchar(255) NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `source_entries` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`source_entries`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `budget_utilization_deduction_sources`
--

INSERT INTO `budget_utilization_deduction_sources` (`id`, `department_id`, `fiscal_year`, `entry_id`, `category_name`, `source_type`, `amount`, `source_entries`, `created_at`, `updated_at`) VALUES
(616, 29, 2025, '1', 'xcvbcvxb', 'purchase_request', 1000.00, '[{\"sourceEntryId\":\"1032\",\"amount\":1000}]', '2026-02-27 05:30:10', '2026-02-27 05:30:10'),
(664, 23, 2025, '1', 'TEST ENTRY 1', 'purchase_request', 3000.00, '[{\"sourceEntryId\":\"1034\",\"amount\":3000}]', '2026-03-02 03:25:13', '2026-03-02 03:25:13'),
(792, 14, 2026, '1', 'Honoraria - Overload', 'purchase_request', 20000.00, '[{\"sourceEntryId\":\"1024\",\"amount\":20000}]', '2026-03-05 03:45:05', '2026-03-05 03:45:05'),
(2423, 13, 2026, '7', 'Fuel, Oil and Lubricants Expenses', 'purchase_request', 5000.00, '[{\"sourceEntryId\":\"1433\",\"amount\":5000}]', '2026-04-15 07:51:20', '2026-04-15 07:51:20');

-- --------------------------------------------------------

--
-- Table structure for table `budget_utilization_entries`
--

CREATE TABLE `budget_utilization_entries` (
  `id` int(11) NOT NULL,
  `deducted_from_entry_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `expense_category` varchar(255) NOT NULL,
  `account_code` varchar(50) DEFAULT NULL,
  `is_auto_filled` tinyint(1) DEFAULT 0,
  `lib_id` int(11) DEFAULT NULL,
  `allocated_budget` decimal(15,2) DEFAULT 0.00,
  `deductions` decimal(15,2) DEFAULT 0.00,
  `total_balance` decimal(15,2) DEFAULT 0.00,
  `fiscal_year` year(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `budget_utilization_entries`
--

INSERT INTO `budget_utilization_entries` (`id`, `deducted_from_entry_id`, `department_id`, `expense_category`, `account_code`, `is_auto_filled`, `lib_id`, `allocated_budget`, `deductions`, `total_balance`, `fiscal_year`, `created_by`, `created_at`, `updated_at`) VALUES
(61556, 1416, 22, 'TEST', NULL, 0, NULL, 0.00, 0.00, 0.00, '2023', 5, '2026-02-25 04:52:46', NULL),
(67098, 1167, 18, 'test23123', NULL, 0, NULL, 50000.00, 10000.00, 40000.00, '2025', 5, '2026-02-25 06:12:41', NULL),
(68523, 1185, 33, 'Internet Subscription', NULL, 0, NULL, 584000.00, 0.00, 584000.00, '2025', 5, '2026-02-25 08:42:22', NULL),
(68524, 1186, 33, 'ICT Equipment', NULL, 0, NULL, 87000.00, 0.00, 87000.00, '2025', 5, '2026-02-25 08:42:22', NULL),
(68553, 1, 33, 'ENTIRHASKF', NULL, 0, NULL, 40000.00, 0.00, 40000.00, '2026', 5, '2026-02-26 16:19:25', NULL),
(70440, 1208, 29, 'xcvbcvxb', NULL, 0, NULL, 2000.00, 1000.00, 1000.00, '2025', 5, '2026-02-27 05:30:10', NULL),
(71178, 1225, 23, 'TEST ENTRY 1', NULL, 0, NULL, 25000.00, 3000.00, 22000.00, '2025', 5, '2026-03-02 03:25:13', NULL),
(96436, 97, 16, 'Honoraria - Overload', '5010210001', 1, 34, 1000000.00, 0.00, 1000000.00, '2026', 5, '2026-03-18 02:00:33', NULL),
(97839, 98, 26, 'Honoraria - Overload', '5010210001', 1, 35, 1000000.00, 0.00, 1000000.00, '2026', 5, '2026-04-15 04:14:01', NULL),
(97970, 1226, 26, 'test2', '', 0, NULL, 20000.00, 0.00, 20000.00, '2025', 5, '2026-04-15 07:46:13', NULL),
(97978, 99, 13, 'Honoraria - Overload', '5010210001', 1, 76, 728562.92, 0.00, 728562.92, '2026', 5, '2026-04-15 07:51:20', NULL),
(97979, 100, 13, 'Honoraria - Part-time', '5010210001', 1, 76, 987390.00, 0.00, 987390.00, '2026', 5, '2026-04-15 07:51:20', NULL),
(97980, 101, 13, 'Water Expenses', '5020401000', 1, 76, 191400.00, 0.00, 191400.00, '2026', 5, '2026-04-15 07:51:20', NULL),
(97981, 102, 13, 'Labor and Wages', '5021601000', 1, 76, 432266.34, 0.00, 432266.34, '2026', 5, '2026-04-15 07:51:20', NULL),
(97982, 103, 13, 'Security Services', '5021203000', 1, 76, 432266.34, 0.00, 432266.34, '2026', 5, '2026-04-15 07:51:20', NULL),
(97983, 104, 13, 'Electricity Expenses', '5020402000', 1, 76, 432266.35, 0.00, 432266.35, '2026', 5, '2026-04-15 07:51:20', NULL),
(97984, 105, 13, 'Fuel, Oil and Lubricants Expenses', '5020309000', 1, 76, 5000.00, 5000.00, 0.00, '2026', 5, '2026-04-15 07:51:20', NULL);

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
(523, 1, 'Honoraria', 2000000.00, 1528875.00, 471125.00, '2026-02-10 05:36:14', '2026-02-10 05:36:14'),
(524, 1, 'Traveling Expenses - Local', 500000.00, 200000.00, 300000.00, '2026-02-10 05:36:14', '2026-02-10 05:36:14'),
(525, 1, 'Training Expenses', 300000.00, 100000.00, 200000.00, '2026-02-10 05:36:14', '2026-02-10 05:36:14'),
(526, 1, 'Office Supplies Expenses', 642291.00, 100000.00, 542291.00, '2026-02-10 05:36:14', '2026-02-10 05:36:14'),
(527, 1, 'Other Supplies and Materials', 900000.00, 300000.00, 600000.00, '2026-02-10 05:36:14', '2026-02-10 05:36:14'),
(528, 1, 'Telephone Expenses - Mobile', 150000.00, 50000.00, 100000.00, '2026-02-10 05:36:14', '2026-02-10 05:36:14'),
(529, 1, 'Rewards and Incentives', 50000.00, 10000.00, 40000.00, '2026-02-10 05:36:14', '2026-02-10 05:36:14'),
(530, 1, 'Other MOOE', 600000.00, 200000.00, 400000.00, '2026-02-10 05:36:14', '2026-02-10 05:36:14'),
(743, 8, 'Traveling Expenses - Local', 400000.00, 155621.00, 244379.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(744, 8, 'Training Expenses', 200000.00, 50000.00, 150000.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(745, 8, 'Office Supplies Expenses', 500000.00, 209230.00, 290770.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(746, 8, 'Fuel, Oil and Lubricants Expenses', 100000.00, 50000.00, 50000.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(747, 8, 'Other Supplies and Materials', 400000.00, 364425.50, 35574.50, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(748, 8, 'Water Expenses', 200000.00, 100000.00, 100000.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(749, 8, 'Electricity Expenses', 200000.00, 133915.00, 66085.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(750, 8, 'Security Services', 978597.04, 200000.00, 778597.04, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(751, 8, 'Repairs and Maintenance - Office Equipment', 50000.00, 0.00, 50000.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(752, 8, 'Labor and Wages', 200000.00, 200000.00, 0.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(753, 8, 'Fedility Bond Prem.', 15000.00, 0.00, 15000.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(754, 8, 'Disaster Response and Rescue Equipment', 150000.00, 0.00, 150000.00, '2026-02-17 01:36:17', '2026-02-17 01:36:17'),
(856, 2, 'Honoraria', 4487201.23, 1574857.25, 2912343.98, '2026-02-17 01:41:21', '2026-02-17 01:41:21'),
(857, 2, 'Traveling Expenses - Local', 100000.00, 100000.00, 0.00, '2026-02-17 01:41:21', '2026-02-17 01:41:21'),
(858, 2, 'Traveling Expenses - Foreign', 0.00, 0.00, 0.00, '2026-02-17 01:41:21', '2026-02-17 01:41:21'),
(859, 2, 'Training Expenses', 100000.00, 100000.00, 0.00, '2026-02-17 01:41:21', '2026-02-17 01:41:21'),
(860, 2, 'Office Supplies Expenses', 100000.00, 100000.00, 0.00, '2026-02-17 01:41:21', '2026-02-17 01:41:21'),
(861, 2, 'Other Supplies and Materials', 400000.00, 200000.00, 200000.00, '2026-02-17 01:41:21', '2026-02-17 01:41:21'),
(862, 2, 'Telephone Expenses - Mobile', 360000.00, 100000.00, 260000.00, '2026-02-17 01:41:21', '2026-02-17 01:41:21'),
(863, 2, 'Other MOOE', 349702.77, 349702.77, 0.00, '2026-02-17 01:41:21', '2026-02-17 01:41:21'),
(1068, 3, 'Honoraria', 16576.22, 5943.63, 10632.59, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1069, 3, 'Traveling Expenses - Local', 181640.00, 100000.00, 81640.00, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1070, 3, 'Training Expenses', 50000.00, 50000.00, 0.00, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1071, 3, 'Office Supplies Expenses', 200000.00, 100000.00, 100000.00, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1072, 3, 'Other Supplies and Materials', 250000.00, 100000.00, 150000.00, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1073, 3, 'Rewards and Incentives', 0.00, 0.00, 0.00, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1074, 3, 'Security Services', 0.00, 0.00, 0.00, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1075, 3, 'Labor and Wages', 150000.00, 0.00, 150000.00, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1076, 3, 'Other MOOE', 103703.28, 68968.13, 34735.15, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1077, 3, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-02-17 01:57:47', '2026-02-17 01:57:47'),
(1974, 5, 'Honoraria', 300000.00, 200000.00, 100000.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1975, 5, 'Traveling Expenses - Local', 200000.00, 200000.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1976, 5, 'Traveling Expenses - Foreign', 200000.00, 103765.00, 96235.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1977, 5, 'Training Expenses', 350000.00, 300000.00, 50000.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1978, 5, 'Office Supplies Expenses', 50000.00, 50000.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1979, 5, 'Fuel, Oil and Lubricants Expenses', 50000.00, 1856.00, 48144.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1980, 5, 'Other Supplies and Materials', 233923.88, 0.00, 233923.88, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1981, 5, 'Water Expenses', 100000.00, 0.00, 100000.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1982, 5, 'Electricity Expenses', 200000.00, 0.00, 200000.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1983, 5, 'Telephone Expenses - Mobile', 10800.00, 0.00, 10800.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1984, 5, 'Rewards and Incentives', 50000.00, 49900.00, 100.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1985, 5, 'Other Professional Services', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1986, 5, 'Repairs & Main. - Bldgs & Other Structures', 185328.12, 0.00, 185328.12, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1987, 5, 'Repairs and Maintenance-ICT', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1988, 5, 'Labor and Wages', 150000.00, 150000.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1989, 5, 'Printing and Publication Expenses', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1990, 5, 'Taxes, Duties and Licenses', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1991, 5, 'Subscription Expenses', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1992, 5, 'Membership Dues', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1993, 5, 'Other MOOE', 678400.00, 494246.00, 184154.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1994, 5, 'Office Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1995, 5, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1996, 5, 'Other Machinery and Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1997, 5, 'ICT Software', 180000.00, 0.00, 180000.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(1998, 5, 'Furniture\'s and Fixtures', 0.00, 0.00, 0.00, '2026-02-17 02:05:14', '2026-02-17 02:05:14'),
(2416, 4, 'Fuel, Oil and Lubricants Expenses', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2417, 4, 'Other Supplies and Materials', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2418, 4, 'Water Expenses', 400000.00, 201624.00, 198376.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2419, 4, 'Janitorial Services', 200000.00, 200000.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2420, 4, 'Security Services', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2421, 4, 'Repairs and Maintenance - Printing Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2422, 4, 'Repairs & Main. - Bldgs & Other Structures', 1000000.00, 523172.14, 476827.86, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2423, 4, 'Repairs and Maintenance-MV', 150000.00, 150000.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2424, 4, 'Labor and Wages', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2425, 4, 'Fuel Oil & Lubricants', 100000.00, 100000.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2426, 4, 'Other MOOE', 50000.00, 50000.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2427, 4, 'Power Supply, Building, School Buildings', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2428, 4, 'Office Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2429, 4, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2430, 4, 'Other Machinery and Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2431, 4, 'Motor Vehicle', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(2432, 4, 'Furniture\'s and Fixtures', 0.00, 0.00, 0.00, '2026-02-17 02:20:40', '2026-02-17 02:20:40'),
(3457, 6, 'Traveling Expenses - Local', 99023.88, 35000.00, 64023.88, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3458, 6, 'Training Expenses', 0.00, 0.00, 0.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3459, 6, 'Office Supplies Expenses', 423900.00, 205621.00, 218279.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3460, 6, 'Other Supplies and Materials', 247545.00, 197545.00, 50000.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3461, 6, 'Electricity Expenses', 467983.12, 351370.00, 116613.12, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3462, 6, 'Janitorial Services', 600000.00, 0.00, 600000.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3463, 6, 'Repairs & Main. - Bldgs & Other Structures', 300000.00, 200000.00, 100000.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3464, 6, 'Repairs and Maintenance-MV', 0.00, 0.00, 0.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3465, 6, 'Labor and Wages', 600000.00, 200000.00, 400000.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3466, 6, 'Other MOOE', 200000.00, 200000.00, 0.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3467, 6, 'Office Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3468, 6, 'Power Supply, Building, School Buildings', 0.00, 0.00, 0.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3469, 6, 'Office Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3470, 6, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3471, 6, 'ICT Software', 0.00, 0.00, 0.00, '2026-02-17 02:40:22', '2026-02-17 02:40:22'),
(3474, 9, 'Other MOOE', 2938452.00, 1399767.00, 1538685.00, '2026-02-17 02:43:28', '2026-02-17 02:43:28'),
(4329, 13, 'Honoraria - Overload', 2000000.00, 2000000.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4330, 13, 'Honoraria - Part-time', 3000000.00, 1082650.97, 1917349.03, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4331, 13, 'Traveling Expenses', 100000.00, 0.00, 100000.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4332, 13, 'Traveling Expenses - Foreign', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4333, 13, 'Training Expenses', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4334, 13, 'Office Supplies', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4335, 13, 'Other Supplies Expenses', 550000.00, 0.00, 550000.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4336, 13, 'Telephone - Mobile', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4337, 13, 'Electricity', 1400000.00, 0.00, 1400000.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4338, 13, 'Fuel Oil and Lubricants', 200000.00, 0.00, 200000.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4339, 13, 'Water Expenses', 21850.00, 0.00, 21850.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4340, 13, 'Rep and Maint-School Building', 300000.00, 0.00, 300000.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4341, 13, 'Rep and Maint-Other Mach & Equip', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4342, 13, 'Rep and Maint-ICT Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4343, 13, 'Rep and Maint Other Structures', 200000.00, 0.00, 200000.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4344, 13, 'Labor and Wages', 325900.47, 0.00, 325900.47, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4345, 13, 'Janitorial Services', 456299.53, 0.00, 456299.53, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4346, 13, 'Other MOOE', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4347, 13, 'School Building', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4348, 13, 'Buildings & Other Structures', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4349, 13, 'Power Supply', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4350, 13, 'Machinery', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4351, 13, 'Office Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4352, 13, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4353, 13, 'Technical and Scientific Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4354, 13, 'Other Machinery and Equipment', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4355, 13, 'Furniture and Fixtures', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4356, 13, 'Computer Software', 0.00, 0.00, 0.00, '2026-02-17 02:55:25', '2026-02-17 02:55:25'),
(4454, 11, 'Honoraria', 173468.66, 99503.10, 73965.56, '2026-02-17 03:03:41', '2026-02-17 03:03:41'),
(4455, 11, 'Traveling Expenses', 364436.80, 364436.80, 0.00, '2026-02-17 03:03:41', '2026-02-17 03:03:41'),
(4456, 11, 'Training Expenses', 100000.00, 0.00, 100000.00, '2026-02-17 03:03:41', '2026-02-17 03:03:41'),
(4457, 11, 'Office Supplies', 50000.00, 0.00, 50000.00, '2026-02-17 03:03:41', '2026-02-17 03:03:41'),
(4458, 11, 'Other Supplies Expenses', 260000.00, 0.00, 260000.00, '2026-02-17 03:03:41', '2026-02-17 03:03:41'),
(4459, 11, 'Telephone - Mobile', 7200.00, 0.00, 7200.00, '2026-02-17 03:03:41', '2026-02-17 03:03:41'),
(4460, 11, 'Labor and Wages', 150000.00, 28800.00, 121200.00, '2026-02-17 03:03:41', '2026-02-17 03:03:41'),
(4461, 11, 'Other MOOE', 367426.54, 367426.54, 0.00, '2026-02-17 03:03:41', '2026-02-17 03:03:41'),
(4550, 19, 'Traveling Expenses', 217414.54, 218314.54, -900.00, '2026-02-17 04:54:12', '2026-02-17 04:54:12'),
(4551, 19, 'Training Expenses', 100000.00, 0.00, 100000.00, '2026-02-17 04:54:12', '2026-02-17 04:54:12'),
(4552, 19, 'Office Supplies', 28700.00, 28700.00, 0.00, '2026-02-17 04:54:12', '2026-02-17 04:54:12'),
(4553, 19, 'Other Supplies Expenses', 93000.00, 0.00, 93000.00, '2026-02-17 04:54:12', '2026-02-17 04:54:12'),
(4554, 19, 'Labor and Wages', 150000.00, 150000.00, 0.00, '2026-02-17 04:54:12', '2026-02-17 04:54:12'),
(4555, 19, 'Other MOOE', 772605.46, 467551.90, 305053.56, '2026-02-17 04:54:12', '2026-02-17 04:54:12'),
(5558, 20, 'Honoraria - Part Time', 2000000.00, 2000000.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5559, 20, 'Honoraria', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5560, 20, 'Traveling Expenses', 150000.00, 3000.00, 147000.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5561, 20, 'Traveling Expenses - Foreign', 150000.00, 0.00, 150000.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5562, 20, 'Training Expenses', 10000.00, 0.00, 10000.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5563, 20, 'Office Supplies', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5564, 20, 'Other Supplies Expenses', 36515.12, 0.00, 36515.12, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5565, 20, 'Telephone - Mobile', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5566, 20, 'Electricity', 1000000.00, 383221.48, 616778.52, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5567, 20, 'Rep and Maint-School Building', 100000.00, 0.00, 100000.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5568, 20, 'Rep and Maint-Other Mach & Equip', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5569, 20, 'Rep and Maint- Water System', 100000.00, 0.00, 100000.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5570, 20, 'Rep and Maint - Other Land Improvements', 100000.00, 0.00, 100000.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5571, 20, 'Rep and Maint- Power Supply system', 100000.00, 0.00, 100000.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5572, 20, 'Rep and Maint- Office Equipment', 150000.00, 96000.00, 54000.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5573, 20, 'Rep and Main- Motor Vehicles', 170696.00, 0.00, 170696.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5574, 20, 'Labor and Wages', 400000.00, 400000.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5575, 20, 'Other MOOE', 772605.46, 467551.90, 305053.56, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5576, 20, 'School Building', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5577, 20, 'Buildings & Other Structures', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5578, 20, 'Machinery', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5579, 20, 'Office Equipment', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5580, 20, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5581, 20, 'Technical and Scientific Equipment', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5582, 20, 'Other Machinery and Equipment', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5583, 20, 'Furniture and Fixtures', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5584, 20, 'Other Property', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5585, 20, 'Computer Software', 0.00, 0.00, 0.00, '2026-02-17 05:55:13', '2026-02-17 05:55:13'),
(5599, 30, 'Honoraria', 321600.00, 208500.00, 113100.00, '2026-02-17 06:00:06', '2026-02-17 06:00:06'),
(5600, 30, 'Traveling Expenses', 160800.00, 0.00, 160800.00, '2026-02-17 06:00:06', '2026-02-17 06:00:06'),
(5601, 30, 'Office Supplies', 53600.00, 0.00, 53600.00, '2026-02-17 06:00:06', '2026-02-17 06:00:06'),
(5650, 14, 'Honoraria', 400000.00, 196781.93, 203218.07, '2026-02-17 06:12:02', '2026-02-17 06:12:02'),
(5651, 14, 'Traveling Expenses', 59000.00, 59000.00, 0.00, '2026-02-17 06:12:02', '2026-02-17 06:12:02'),
(5652, 14, 'Training Expenses', 20000.00, 20000.00, 0.00, '2026-02-17 06:12:02', '2026-02-17 06:12:02'),
(5653, 14, 'Office Supplies', 14405.00, 0.00, 14405.00, '2026-02-17 06:12:02', '2026-02-17 06:12:02'),
(5654, 14, 'Other Supplies Expenses', 20000.00, 0.00, 20000.00, '2026-02-17 06:12:02', '2026-02-17 06:12:02'),
(5655, 14, 'Labor and Wages', 0.00, 0.00, 0.00, '2026-02-17 06:12:02', '2026-02-17 06:12:02'),
(5656, 14, 'Other MOOE', 0.00, 0.00, 0.00, '2026-02-17 06:12:02', '2026-02-17 06:12:02'),
(6014, 16, 'Honoraria - Part-time', 361755.00, 313585.15, 48169.85, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6015, 16, 'Rep and Maint Other Structures', 600000.00, 300000.00, 300000.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6016, 16, 'Computer Software', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6017, 16, 'Traveling Expenses', 300000.00, 194368.72, 105631.28, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6018, 16, 'Training Expenses', 250000.00, 0.00, 250000.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6019, 16, 'Office Supplies', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6020, 16, 'Other Supplies Expenses', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6021, 16, 'Cable, Satellite, Tel and Radio Exp', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6022, 16, 'Telephone - Mobile', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6023, 16, 'Internet Subscription', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6024, 16, 'Rep and Maint-School Building', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6025, 16, 'Rep and Maint-Other Mach & Equip', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6026, 16, 'Rep and Maint-ICT Equipment', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6027, 16, 'Labor and Wages', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6028, 16, 'Subscription Exp', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6029, 16, 'Other MOOE', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6030, 16, 'Office Equipment', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6031, 16, 'ICT Equipment', 567775.00, 567775.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6032, 16, 'Furniture and Fixtures', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6033, 16, 'Computer Software', 0.00, 0.00, 0.00, '2026-02-17 06:37:25', '2026-02-17 06:37:25'),
(6039, 15, 'Traveling Expenses', 147240.00, 180876.64, -33636.64, '2026-02-17 06:50:11', '2026-02-17 06:50:11'),
(6220, 24, 'Honoraria', 5000.00, 0.00, 5000.00, '2026-02-19 02:03:53', '2026-02-19 02:03:53'),
(6221, 24, 'Traveling Expenses', 100000.00, 5940.00, 94060.00, '2026-02-19 02:03:53', '2026-02-19 02:03:53'),
(6222, 24, 'Training Expenses', 50000.00, 0.00, 50000.00, '2026-02-19 02:03:53', '2026-02-19 02:03:53'),
(6223, 24, 'Office Supplies', 50000.00, 0.00, 50000.00, '2026-02-19 02:03:53', '2026-02-19 02:03:53'),
(6224, 24, 'Other Supplies Expenses', 100000.00, 0.00, 100000.00, '2026-02-19 02:03:53', '2026-02-19 02:03:53'),
(6225, 24, 'Labor and Wages', 150000.00, 150000.00, 0.00, '2026-02-19 02:03:53', '2026-02-19 02:03:53'),
(6226, 24, 'Other MOOE', 84924.00, 16993.29, 67930.71, '2026-02-19 02:03:53', '2026-02-19 02:03:53'),
(6230, 28, 'Other MOOE', 506378.50, 506378.50, 0.00, '2026-02-19 02:07:23', '2026-02-19 02:07:23'),
(6233, 26, 'Insurance Expenses', 426280.00, 426280.00, 0.00, '2026-02-19 02:11:40', '2026-02-19 02:11:40'),
(6256, 17, 'Other Supplies Expenses', 87000.00, 0.00, 87000.00, '2026-02-19 02:16:10', '2026-02-19 02:16:10'),
(6257, 17, 'Internet Subscription', 584000.00, 603200.00, -19200.00, '2026-02-19 02:16:10', '2026-02-19 02:16:10'),
(6258, 17, 'Electricity', 310600.00, 3244.30, 307355.70, '2026-02-19 02:16:10', '2026-02-19 02:16:10'),
(6379, 12, 'Traveling Expenses', 50000.00, 50900.00, -900.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6380, 12, 'Training Expenses', 20000.00, 20000.00, 0.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6381, 12, 'Office Supplies', 0.00, 0.00, 0.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6382, 12, 'Textbooks and Instructional Materials', 367414.54, 0.00, 367414.54, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6383, 12, 'Other Supplies Expenses', 250000.00, 200000.00, 50000.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6384, 12, 'Telephone - Mobile', 21600.00, 21600.00, 0.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6385, 12, 'Labor and Wages', 450000.00, 450000.00, 0.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6386, 12, 'Other MOOE', 20000.00, 0.00, 20000.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6387, 12, 'ICT Equipement', 0.00, 0.00, 0.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6388, 12, 'Books', 0.00, 0.00, 0.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6389, 12, 'Furniture and Fixtures', 0.00, 0.00, 0.00, '2026-02-19 02:20:55', '2026-02-19 02:20:55'),
(6552, 25, 'Traveling Expenses', 0.00, 0.00, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6553, 25, 'Training Expenses', 0.00, 0.00, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6554, 25, 'Office Supplies', 0.00, 0.00, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6555, 25, 'Medical, Dental Laboratory Exp', 25126.00, 25126.00, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6556, 25, 'Drugs and Medicines Exp', 169161.02, 169161.02, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6557, 25, 'Other Supplies Expenses', 0.00, 0.00, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6558, 25, 'Electricity', 387569.98, 100000.00, 287569.98, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6559, 25, 'Rep and Maint-Other Mach & Equip', 0.00, 0.00, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6560, 25, 'Labor and Wages', 0.00, 0.00, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6561, 25, 'Other MOOE', 64000.00, 0.00, 64000.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6562, 25, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-02-19 02:24:53', '2026-02-19 02:24:53'),
(6632, 23, 'Honoraria', 0.00, 0.00, 0.00, '2026-02-19 02:46:52', '2026-02-19 02:46:52'),
(6633, 23, 'Traveling Expenses', 53800.00, 73600.00, -19800.00, '2026-02-19 02:46:52', '2026-02-19 02:46:52'),
(6634, 23, 'Training Expenses', 145243.02, 145243.02, 0.00, '2026-02-19 02:46:52', '2026-02-19 02:46:52'),
(6635, 23, 'Office Supplies', 9950.00, 9950.00, 0.00, '2026-02-19 02:46:52', '2026-02-19 02:46:52'),
(6636, 23, 'Other Supplies Expenses', 91450.00, 91450.00, 0.00, '2026-02-19 02:46:52', '2026-02-19 02:46:52'),
(6637, 23, 'Printing and Binding Expenses', 235200.00, 235200.00, 0.00, '2026-02-19 02:46:52', '2026-02-19 02:46:52'),
(6638, 23, 'ICT Equipment', 140000.00, 0.00, 140000.00, '2026-02-19 02:46:52', '2026-02-19 02:46:52'),
(6679, 22, 'Traveling Expenses', 60000.00, 69900.00, -9900.00, '2026-02-19 02:49:48', '2026-02-19 02:49:48'),
(6680, 22, 'Training Expenses', 50000.00, 50000.00, 0.00, '2026-02-19 02:49:48', '2026-02-19 02:49:48'),
(6681, 22, 'Office Supplies', 45610.00, 45610.00, 0.00, '2026-02-19 02:49:48', '2026-02-19 02:49:48'),
(6682, 22, 'Other Supplies Expenses', 0.00, 0.00, 0.00, '2026-02-19 02:49:48', '2026-02-19 02:49:48'),
(6683, 22, 'Other MOOE', 279330.00, 122712.15, 156617.85, '2026-02-19 02:49:48', '2026-02-19 02:49:48'),
(6684, 22, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-02-19 02:49:48', '2026-02-19 02:49:48'),
(6708, 34, 'Rent Income', 0.00, 450000.00, -450000.00, '2026-02-27 01:08:10', '2026-02-27 01:08:10'),
(6712, 32, 'Trust Fund', 0.00, 3000.00, -3000.00, '2026-02-27 01:13:43', '2026-02-27 01:13:43'),
(6716, 29, 'Handbook', 0.00, 69750.00, -69750.00, '2026-02-27 01:23:11', '2026-02-27 01:23:11'),
(6717, 7, 'Honoraria', 300000.00, 200000.00, 100000.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6718, 7, 'Traveling Expenses - Local', 400000.00, 200000.00, 200000.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6719, 7, 'Traveling Expenses - Foreign', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6720, 7, 'Training Expenses', 300000.00, 100000.00, 200000.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6721, 7, 'Office Supplies Expenses', 200000.00, 103765.00, 96235.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6722, 7, 'Fuel, Oil and Lubricants Expenses', 200000.00, 1856.00, 198144.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6723, 7, 'Other Supplies and Materials', 366450.00, 200000.00, 166450.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6724, 7, 'Water Expenses', 150000.00, 50000.00, 100000.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6725, 7, 'Electricity Expenses', 150000.00, 126715.00, 23285.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6726, 7, 'Telephone Expenses - Mobile', 11000.00, 7200.00, 3800.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6727, 7, 'Rewards and Incentives', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6728, 7, 'Consultancy Services', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6729, 7, 'Other Professional Services', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6730, 7, 'Security Services', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6731, 7, 'Repairs and Maintenance - Other Machinery', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6732, 7, 'Labor and Wages', 200000.00, 0.00, 200000.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6733, 7, 'Printing and Publication Expenses', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6734, 7, 'Subscription Expenses', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6735, 7, 'Other MOOE', 661002.00, 410231.00, 250771.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6736, 7, 'Office Equipment', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6737, 7, 'ICT Equipment', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6738, 7, 'Other Machinery and Equipment', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6739, 7, 'Furniture\'s and Fixtures', 0.00, 0.00, 0.00, '2026-03-07 06:24:32', '2026-03-07 06:24:32'),
(6740, 21, 'Traveling Expenses', 22471.51, 22471.00, 0.51, '2026-03-17 06:17:16', '2026-03-17 06:17:16'),
(6741, 21, 'Training Expenses', 45610.00, 45910.00, -300.00, '2026-03-17 06:17:16', '2026-03-17 06:17:16'),
(6742, 21, 'Office Supplies', 0.00, 0.00, 0.00, '2026-03-17 06:17:16', '2026-03-17 06:17:16'),
(6743, 21, 'Other Supplies Expenses', 2300.00, 2300.00, 0.00, '2026-03-17 06:17:16', '2026-03-17 06:17:16'),
(6744, 21, 'Rep and Maint Other Structures', 5000.00, 5000.00, 0.00, '2026-03-17 06:17:16', '2026-03-17 06:17:16'),
(6745, 21, 'Labor and Wages', 300000.00, 234712.15, 65287.85, '2026-03-17 06:17:16', '2026-03-17 06:17:16'),
(6746, 21, 'Other MOOE', 115458.49, 0.00, 115458.49, '2026-03-17 06:17:16', '2026-03-17 06:17:16');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `dept_name` varchar(100) NOT NULL,
  `dept_code` varchar(20) NOT NULL,
  `fiduciary_type` enum('Fiduciary','Non-Fiduciary') DEFAULT 'Non-Fiduciary',
  `parent_department_id` int(11) DEFAULT NULL,
  `dept_description` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `dept_name`, `dept_code`, `fiduciary_type`, `parent_department_id`, `dept_description`, `is_active`, `created_at`, `updated_at`) VALUES
(13, 'Computer Studies', 'CS', 'Non-Fiduciary', NULL, 'Computer Studies Department', 1, '2025-09-28 19:14:10', '2026-03-06 03:14:43'),
(14, 'Engineering', 'ENGR', 'Non-Fiduciary', NULL, 'ajkfafa', 1, '2025-10-17 16:43:48', '2025-12-15 11:58:47'),
(15, 'Education', 'EDUC', 'Non-Fiduciary', NULL, '', 1, '2025-11-19 12:36:43', '2025-12-15 11:58:36'),
(16, 'Procurement Office', 'PR', 'Fiduciary', NULL, '', 1, '2025-11-22 11:24:09', '2025-11-22 11:43:58'),
(17, 'SSG', 'SSG', 'Fiduciary', NULL, NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(18, 'Guidance Office', 'GUID', 'Fiduciary', NULL, NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(19, 'Culture and Arts', 'C&A', 'Fiduciary', NULL, NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(20, 'IGP Production Office', 'IGP', 'Fiduciary', NULL, NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(21, 'Library', 'LIB', 'Fiduciary', NULL, NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(22, 'Research', 'RES', 'Non-Fiduciary', NULL, NULL, 1, '2025-11-22 11:46:46', '2025-11-22 11:46:46'),
(23, 'Admin', 'ADMIN', 'Fiduciary', NULL, NULL, 1, '2025-11-22 11:46:46', '2025-12-15 11:57:54'),
(24, 'Extension Services', 'EXT', 'Fiduciary', NULL, NULL, 1, '2025-11-22 11:46:46', '2025-12-15 11:58:09'),
(25, 'Supply Office', 'SP', 'Fiduciary', NULL, '', 1, '2025-11-26 23:38:04', '2025-12-15 11:58:55'),
(26, 'Budget Office', 'BO', 'Fiduciary', NULL, '', 1, '2025-11-27 07:35:24', '2025-12-15 11:57:43'),
(27, 'Industrial Technology', 'INDTECH', 'Non-Fiduciary', NULL, '', 1, '2025-12-22 09:57:33', '2025-12-22 09:57:33'),
(28, 'Hospitality Management', 'BSHM', 'Non-Fiduciary', NULL, '', 1, '2025-12-22 09:57:46', '2025-12-22 09:57:46'),
(29, 'Registrar', 'RGTR', 'Fiduciary', NULL, '', 1, '2026-02-16 02:01:06', '2026-02-16 02:25:58'),
(33, 'ICT', 'ict', 'Fiduciary', NULL, '', 1, '2026-02-16 02:19:11', '2026-03-06 03:28:51'),
(35, 'Maintenance and Engineering Service Office (MESO)', 'MESO', 'Fiduciary', NULL, '', 1, '2026-02-24 07:08:57', '2026-02-24 07:08:57');

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
(66, 23, '2025', 110000.00, 0.00, '2025-12-28 06:41:13'),
(80, 13, '2026', 3190473.05, 0.00, '2026-04-15 06:01:22'),
(81, 33, '2026', 60000.00, 0.00, '2026-02-16 03:13:53'),
(82, 18, '2026', 140000.00, 0.00, '2026-02-16 03:08:48'),
(90, 15, '2026', -3125910.52, 0.00, '2026-04-07 08:11:55'),
(107, 27, '2026', 343445.48, 0.00, '2026-02-24 01:54:40'),
(108, 28, '2026', 556701.77, 0.00, '2026-02-24 01:57:38'),
(109, 14, '2026', 3159829.05, 0.00, '2026-02-24 02:01:26'),
(116, 13, '2027', 5790473.05, 0.00, '2026-04-15 04:47:16'),
(128, 23, '2026', 4000000.00, 0.00, '2026-04-07 06:24:47');

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

--
-- Dumping data for table `file_submissions`
--

INSERT INTO `file_submissions` (`id`, `user_id`, `department_id`, `submission_type`, `fiscal_year`, `file_name`, `file_path`, `file_size`, `file_type`, `status`, `submitted_at`, `removed_by_user_at`, `reviewed_at`, `reviewed_by`, `review_notes`) VALUES
(32, 38, 13, 'SUPPLEMENTAL', '2026', 'Utilization_Computer_Studies_2026.pdf', 'uploads/supplemental/SUPPLEMENTAL_38_1771873812_02892d.pdf', 19706, 'application/pdf', 'pending', '2026-02-23 19:10:12', '2026-02-23 19:10:40', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `lib_custom_items`
--

CREATE TABLE `lib_custom_items` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `year` int(11) NOT NULL,
  `uacs_code` varchar(50) NOT NULL,
  `general_desc` text NOT NULL,
  `total_amount` decimal(15,2) DEFAULT 0.00,
  `quarter_1` decimal(15,2) DEFAULT 0.00,
  `quarter_2` decimal(15,2) DEFAULT 0.00,
  `quarter_3` decimal(15,2) DEFAULT 0.00,
  `quarter_4` decimal(15,2) DEFAULT 0.00,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `deleted_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `line_item_budgets`
--

CREATE TABLE `line_item_budgets` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `fund_type` enum('Internally Generated Fund','Other Fund') NOT NULL DEFAULT 'Internally Generated Fund',
  `status` enum('draft','pending_approval','approved','rejected') NOT NULL DEFAULT 'draft',
  `approved_by_budget_office` tinyint(1) DEFAULT 0,
  `approved_date` datetime DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `line_item_budgets`
--

INSERT INTO `line_item_budgets` (`id`, `department_id`, `fiscal_year`, `fund_type`, `status`, `approved_by_budget_office`, `approved_date`, `approved_by_user_id`, `created_by`, `created_at`, `updated_at`) VALUES
(34, 16, '2026', 'Internally Generated Fund', 'approved', 0, NULL, NULL, 5, '2026-03-18 02:00:33', '2026-03-18 02:00:33'),
(35, 26, '2026', 'Internally Generated Fund', 'approved', 0, NULL, NULL, 5, '2026-03-18 02:06:57', '2026-03-18 02:06:57'),
(76, 13, 'FY 2026', 'Internally Generated Fund', 'approved', 0, NULL, NULL, 38, '2026-04-15 07:22:38', '2026-04-15 07:23:27');

-- --------------------------------------------------------

--
-- Table structure for table `line_item_budget_items`
--

CREATE TABLE `line_item_budget_items` (
  `id` int(11) NOT NULL,
  `lib_id` int(11) NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `is_parent` tinyint(1) DEFAULT 0,
  `category` varchar(100) NOT NULL,
  `particulars` varchar(255) NOT NULL,
  `sub_category_name` varchar(255) DEFAULT NULL,
  `account_code` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `source` enum('auto','manual') NOT NULL DEFAULT 'manual',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `line_item_budget_items`
--

INSERT INTO `line_item_budget_items` (`id`, `lib_id`, `parent_id`, `is_parent`, `category`, `particulars`, `sub_category_name`, `account_code`, `amount`, `source`, `sort_order`, `created_at`, `updated_at`) VALUES
(457, 34, NULL, 0, 'A. PERSONAL SERVICES', 'Honoraria - Overload', NULL, '5010210001', 1000000.00, 'auto', 0, '2026-03-18 02:00:33', '2026-04-11 01:45:07'),
(458, 35, NULL, 0, 'A. PERSONAL SERVICES', 'Honoraria - Overload', NULL, '5010210001', 1000000.00, 'auto', 0, '2026-03-18 02:06:57', '2026-04-11 01:45:07'),
(730, 76, NULL, 0, 'A. PERSONAL SERVICES', 'Honoraria - Overload', NULL, '5010210001', 728562.92, 'auto', 0, '2026-04-15 07:22:38', '2026-04-15 07:22:38'),
(731, 76, NULL, 0, 'A. PERSONAL SERVICES', 'Honoraria - Part-time', NULL, '5010210001', 987390.00, 'auto', 1, '2026-04-15 07:22:38', '2026-04-15 07:22:38'),
(732, 76, NULL, 0, 'B. Maintenance & Other Operating Expenses', 'Water Expenses', NULL, '5020401000', 191400.00, 'auto', 2, '2026-04-15 07:22:38', '2026-04-15 07:22:38'),
(733, 76, NULL, 0, 'B. Maintenance & Other Operating Expenses', 'Labor and Wages', NULL, '5021601000', 432266.34, 'auto', 3, '2026-04-15 07:22:38', '2026-04-15 07:22:38'),
(734, 76, NULL, 0, 'B. Maintenance & Other Operating Expenses', 'Security Services', NULL, '5021203000', 432266.34, 'auto', 4, '2026-04-15 07:22:38', '2026-04-15 07:22:38'),
(735, 76, NULL, 0, 'B. Maintenance & Other Operating Expenses', 'Electricity Expenses', NULL, '5020402000', 432266.35, 'auto', 5, '2026-04-15 07:22:38', '2026-04-15 07:22:38'),
(736, 76, NULL, 0, 'B. Maintenance & Other Operating Expenses', 'Fuel, Oil and Lubricants Expenses', NULL, '5020309000', 5000.00, 'auto', 6, '2026-04-15 07:22:38', '2026-04-15 07:23:11');

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

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`, `read_at`) VALUES
(85, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱230,000.00. (Feb 10, 2026 5:52 AM)', 'success', 1, '2026-02-10 04:52:03', '2026-02-10 04:54:49'),
(87, 25, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:35 AM)', 'info', 0, '2026-02-10 05:35:21', NULL),
(88, 32, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:35 AM)', 'info', 0, '2026-02-10 05:35:21', NULL),
(89, 34, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:35 AM)', 'info', 1, '2026-02-10 05:35:21', '2026-02-25 07:43:06'),
(90, 36, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:35 AM)', 'info', 0, '2026-02-10 05:35:21', NULL),
(91, 38, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:35 AM)', 'info', 1, '2026-02-10 05:35:21', '2026-02-16 00:45:27'),
(92, 39, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:35 AM)', 'info', 1, '2026-02-10 05:35:21', '2026-02-25 06:15:31'),
(93, 40, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:35 AM)', 'info', 0, '2026-02-10 05:35:21', NULL),
(96, 25, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:36 AM)', 'info', 0, '2026-02-10 05:36:14', NULL),
(97, 32, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:36 AM)', 'info', 0, '2026-02-10 05:36:14', NULL),
(98, 34, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:36 AM)', 'info', 1, '2026-02-10 05:36:14', '2026-02-25 07:43:06'),
(99, 36, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:36 AM)', 'info', 0, '2026-02-10 05:36:14', NULL),
(100, 38, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:36 AM)', 'info', 1, '2026-02-10 05:36:14', '2026-02-16 00:45:27'),
(101, 39, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:36 AM)', 'info', 1, '2026-02-10 05:36:14', '2026-02-25 06:15:31'),
(102, 40, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Faculty and Staff Development (non-fiduciary) (Feb 10, 2026 6:36 AM)', 'info', 0, '2026-02-10 05:36:14', NULL),
(104, 25, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Budget Office (Fiscal Year 2026). Total Expenditures: ₱71,000.00. (Feb 16, 2026 1:43 AM)', 'info', 0, '2026-02-16 00:43:53', NULL),
(105, 32, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Budget Office (Fiscal Year 2026). Total Expenditures: ₱71,000.00. (Feb 16, 2026 1:43 AM)', 'info', 0, '2026-02-16 00:43:53', NULL),
(107, 38, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱30,000.00. (Feb 16, 2026 1:56 AM)', 'info', 1, '2026-02-16 00:56:29', '2026-02-16 00:58:32'),
(109, 25, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Administrator (non-fiduciary) (Feb 16, 2026 2:03 AM)', 'info', 0, '2026-02-16 01:03:45', NULL),
(110, 32, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Administrator (non-fiduciary) (Feb 16, 2026 2:03 AM)', 'info', 0, '2026-02-16 01:03:45', NULL),
(111, 34, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Administrator (non-fiduciary) (Feb 16, 2026 2:03 AM)', 'info', 1, '2026-02-16 01:03:45', '2026-02-25 07:43:06'),
(112, 36, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Administrator (non-fiduciary) (Feb 16, 2026 2:03 AM)', 'info', 0, '2026-02-16 01:03:45', NULL),
(113, 38, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Administrator (non-fiduciary) (Feb 16, 2026 2:03 AM)', 'info', 1, '2026-02-16 01:03:45', '2026-02-16 01:03:57'),
(114, 39, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Administrator (non-fiduciary) (Feb 16, 2026 2:03 AM)', 'info', 1, '2026-02-16 01:03:45', '2026-02-25 06:15:31'),
(115, 40, 'CABAC Entries Updated', 'Super Admin saved CABAC entries for Administrator (non-fiduciary) (Feb 16, 2026 2:03 AM)', 'info', 0, '2026-02-16 01:03:45', NULL),
(118, 25, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 0, '2026-02-16 01:09:21', NULL),
(119, 32, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 0, '2026-02-16 01:09:21', NULL),
(120, 34, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 1, '2026-02-16 01:09:21', '2026-02-25 07:43:06'),
(121, 36, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 0, '2026-02-16 01:09:21', NULL),
(122, 38, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 1, '2026-02-16 01:09:21', '2026-02-16 01:09:31'),
(123, 39, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 1, '2026-02-16 01:09:21', '2026-02-25 06:15:31'),
(124, 40, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 0, '2026-02-16 01:09:21', NULL),
(126, 25, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Budget Office (Fiscal Year 2026). Total Expenditures: ₱61,000.00. (Feb 16, 2026 2:35 AM)', 'info', 0, '2026-02-16 01:35:44', NULL),
(127, 32, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Budget Office (Fiscal Year 2026). Total Expenditures: ₱61,000.00. (Feb 16, 2026 2:35 AM)', 'info', 0, '2026-02-16 01:35:44', NULL),
(129, 39, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Guidance Office (Fiscal Year 2026). Overall Total: ₱140,000.00. (Feb 16, 2026 4:08 AM)', 'success', 1, '2026-02-16 03:08:48', '2026-02-25 06:15:31'),
(130, 38, 'Sub-Department Allocation Updated', 'Super Admin has updated the budget allocation for your sub-department ICT (Fiscal Year 2026). Overall Total: ₱60,000.00. (Feb 16, 2026 4:13 AM)', 'info', 1, '2026-02-16 03:13:53', '2026-02-16 03:13:57'),
(131, 38, 'Sub-Department Utilization Updated', 'Super Admin has updated the budget utilization summary for sub-department ICT (Fiscal Year 2026). Total Expenditures: ₱70,000.00. (Feb 17, 2026 2:12 AM)', 'info', 1, '2026-02-17 01:12:53', '2026-02-17 01:13:05'),
(132, 38, 'Sub-Department ICT Utilization Updated', 'The budget utilization for ICT has been updated. Fiscal Year 2026 - Total Expenditures: ₱50,000.00.', 'info', 1, '2026-02-17 01:17:19', '2026-02-17 01:17:23'),
(134, 25, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 0, '2026-02-17 01:36:17', NULL),
(135, 32, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 0, '2026-02-17 01:36:17', NULL),
(136, 34, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 1, '2026-02-17 01:36:17', '2026-02-25 07:43:06'),
(137, 36, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 0, '2026-02-17 01:36:17', NULL),
(138, 38, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 1, '2026-02-17 01:36:17', '2026-02-17 01:36:23'),
(139, 39, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 1, '2026-02-17 01:36:17', '2026-02-25 06:15:31'),
(140, 40, 'CABAC Administrator Updated', 'Super Admin updated Administrator (Non fiduciary)', 'info', 0, '2026-02-17 01:36:17', NULL),
(143, 25, 'CABAC Curriculum Development Updated', 'Super Admin updated Curriculum Development (Non fiduciary)', 'info', 0, '2026-02-17 01:41:21', NULL),
(144, 32, 'CABAC Curriculum Development Updated', 'Super Admin updated Curriculum Development (Non fiduciary)', 'info', 0, '2026-02-17 01:41:21', NULL),
(145, 34, 'CABAC Curriculum Development Updated', 'Super Admin updated Curriculum Development (Non fiduciary)', 'info', 1, '2026-02-17 01:41:21', '2026-02-25 07:43:06'),
(146, 36, 'CABAC Curriculum Development Updated', 'Super Admin updated Curriculum Development (Non fiduciary)', 'info', 0, '2026-02-17 01:41:21', NULL),
(147, 38, 'CABAC Curriculum Development Updated', 'Super Admin updated Curriculum Development (Non fiduciary)', 'info', 1, '2026-02-17 01:41:21', '2026-02-17 01:41:25'),
(148, 39, 'CABAC Curriculum Development Updated', 'Super Admin updated Curriculum Development (Non fiduciary)', 'info', 1, '2026-02-17 01:41:21', '2026-02-25 06:15:31'),
(149, 40, 'CABAC Curriculum Development Updated', 'Super Admin updated Curriculum Development (Non fiduciary)', 'info', 0, '2026-02-17 01:41:21', NULL),
(152, 25, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 0, '2026-02-17 01:51:03', NULL),
(153, 32, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 0, '2026-02-17 01:51:03', NULL),
(154, 34, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 1, '2026-02-17 01:51:03', '2026-02-25 07:43:06'),
(155, 36, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 0, '2026-02-17 01:51:03', NULL),
(156, 38, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 1, '2026-02-17 01:51:03', '2026-02-17 01:57:30'),
(157, 39, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 1, '2026-02-17 01:51:03', '2026-02-25 06:15:31'),
(158, 40, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 0, '2026-02-17 01:51:03', NULL),
(161, 25, 'CABAC Student Development Updated', '10 entries have been updated in Student Development.', 'info', 0, '2026-02-17 01:57:26', NULL),
(162, 32, 'CABAC Student Development Updated', '10 entries have been updated in Student Development.', 'info', 0, '2026-02-17 01:57:26', NULL),
(163, 34, 'CABAC Student Development Updated', '10 entries have been updated in Student Development.', 'info', 1, '2026-02-17 01:57:26', '2026-02-25 07:43:06'),
(164, 36, 'CABAC Student Development Updated', '10 entries have been updated in Student Development.', 'info', 0, '2026-02-17 01:57:26', NULL),
(165, 38, 'CABAC Student Development Updated', '10 entries have been updated in Student Development.', 'info', 1, '2026-02-17 01:57:26', '2026-02-17 01:57:30'),
(166, 39, 'CABAC Student Development Updated', '10 entries have been updated in Student Development.', 'info', 1, '2026-02-17 01:57:26', '2026-02-25 06:15:31'),
(167, 40, 'CABAC Student Development Updated', '10 entries have been updated in Student Development.', 'info', 0, '2026-02-17 01:57:26', NULL),
(170, 25, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 0, '2026-02-17 01:57:47', NULL),
(171, 32, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 0, '2026-02-17 01:57:47', NULL),
(172, 34, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 1, '2026-02-17 01:57:47', '2026-02-25 07:43:06'),
(173, 36, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 0, '2026-02-17 01:57:47', NULL),
(174, 38, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 1, '2026-02-17 01:57:47', '2026-02-17 01:57:51'),
(175, 39, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 1, '2026-02-17 01:57:47', '2026-02-25 06:15:31'),
(176, 40, 'CABAC Student Development Updated', 'Super Admin updated Student Development (Non fiduciary)', 'info', 0, '2026-02-17 01:57:47', NULL),
(179, 25, 'CABAC Research Updated', 'Super Admin updated Research (Non fiduciary)', 'info', 0, '2026-02-17 02:05:13', NULL),
(180, 32, 'CABAC Research Updated', 'Super Admin updated Research (Non fiduciary)', 'info', 0, '2026-02-17 02:05:13', NULL),
(181, 34, 'CABAC Research Updated', 'Super Admin updated Research (Non fiduciary)', 'info', 1, '2026-02-17 02:05:13', '2026-02-25 07:43:06'),
(182, 36, 'CABAC Research Updated', 'Super Admin updated Research (Non fiduciary)', 'info', 0, '2026-02-17 02:05:13', NULL),
(183, 38, 'CABAC Research Updated', 'Super Admin updated Research (Non fiduciary)', 'info', 1, '2026-02-17 02:05:13', '2026-02-17 02:05:19'),
(184, 39, 'CABAC Research Updated', 'Super Admin updated Research (Non fiduciary)', 'info', 1, '2026-02-17 02:05:13', '2026-02-25 06:15:31'),
(185, 40, 'CABAC Research Updated', 'Super Admin updated Research (Non fiduciary)', 'info', 0, '2026-02-17 02:05:13', NULL),
(188, 25, 'CABAC Facilities Development Updated', 'Super Admin updated Facilities Development (Non fiduciary)', 'info', 0, '2026-02-17 02:20:40', NULL),
(189, 32, 'CABAC Facilities Development Updated', 'Super Admin updated Facilities Development (Non fiduciary)', 'info', 0, '2026-02-17 02:20:40', NULL),
(190, 34, 'CABAC Facilities Development Updated', 'Super Admin updated Facilities Development (Non fiduciary)', 'info', 1, '2026-02-17 02:20:40', '2026-02-25 07:43:06'),
(191, 36, 'CABAC Facilities Development Updated', 'Super Admin updated Facilities Development (Non fiduciary)', 'info', 0, '2026-02-17 02:20:40', NULL),
(192, 38, 'CABAC Facilities Development Updated', 'Super Admin updated Facilities Development (Non fiduciary)', 'info', 1, '2026-02-17 02:20:40', '2026-02-17 02:31:14'),
(193, 39, 'CABAC Facilities Development Updated', 'Super Admin updated Facilities Development (Non fiduciary)', 'info', 1, '2026-02-17 02:20:40', '2026-02-25 06:15:31'),
(194, 40, 'CABAC Facilities Development Updated', 'Super Admin updated Facilities Development (Non fiduciary)', 'info', 0, '2026-02-17 02:20:40', NULL),
(197, 25, 'CABAC Extension Updated', 'Super Admin updated Extension (Non fiduciary)', 'info', 0, '2026-02-17 02:31:10', NULL),
(198, 32, 'CABAC Extension Updated', 'Super Admin updated Extension (Non fiduciary)', 'info', 0, '2026-02-17 02:31:10', NULL),
(199, 34, 'CABAC Extension Updated', 'Super Admin updated Extension (Non fiduciary)', 'info', 1, '2026-02-17 02:31:10', '2026-02-25 07:43:06'),
(200, 36, 'CABAC Extension Updated', 'Super Admin updated Extension (Non fiduciary)', 'info', 0, '2026-02-17 02:31:10', NULL),
(201, 38, 'CABAC Extension Updated', 'Super Admin updated Extension (Non fiduciary)', 'info', 1, '2026-02-17 02:31:10', '2026-02-17 02:31:14'),
(202, 39, 'CABAC Extension Updated', 'Super Admin updated Extension (Non fiduciary)', 'info', 1, '2026-02-17 02:31:10', '2026-02-25 06:15:31'),
(203, 40, 'CABAC Extension Updated', 'Super Admin updated Extension (Non fiduciary)', 'info', 0, '2026-02-17 02:31:10', NULL),
(206, 25, 'CABAC Production Updated', 'Super Admin updated Production (Non fiduciary)', 'info', 0, '2026-02-17 02:40:21', NULL),
(207, 32, 'CABAC Production Updated', 'Super Admin updated Production (Non fiduciary)', 'info', 0, '2026-02-17 02:40:21', NULL),
(208, 34, 'CABAC Production Updated', 'Super Admin updated Production (Non fiduciary)', 'info', 1, '2026-02-17 02:40:21', '2026-02-25 07:43:06'),
(209, 36, 'CABAC Production Updated', 'Super Admin updated Production (Non fiduciary)', 'info', 0, '2026-02-17 02:40:21', NULL),
(210, 38, 'CABAC Production Updated', 'Super Admin updated Production (Non fiduciary)', 'info', 1, '2026-02-17 02:40:21', '2026-02-17 05:56:05'),
(211, 39, 'CABAC Production Updated', 'Super Admin updated Production (Non fiduciary)', 'info', 1, '2026-02-17 02:40:21', '2026-02-25 06:15:31'),
(212, 40, 'CABAC Production Updated', 'Super Admin updated Production (Non fiduciary)', 'info', 0, '2026-02-17 02:40:21', NULL),
(215, 25, 'CABAC Laboratory Fee Updated', 'Super Admin updated Laboratory Fee (Fiduciary)', 'info', 0, '2026-02-17 02:55:24', NULL),
(216, 32, 'CABAC Laboratory Fee Updated', 'Super Admin updated Laboratory Fee (Fiduciary)', 'info', 0, '2026-02-17 02:55:24', NULL),
(217, 34, 'CABAC Laboratory Fee Updated', 'Super Admin updated Laboratory Fee (Fiduciary)', 'info', 1, '2026-02-17 02:55:24', '2026-02-25 07:43:06'),
(218, 36, 'CABAC Laboratory Fee Updated', 'Super Admin updated Laboratory Fee (Fiduciary)', 'info', 0, '2026-02-17 02:55:24', NULL),
(219, 38, 'CABAC Laboratory Fee Updated', 'Super Admin updated Laboratory Fee (Fiduciary)', 'info', 1, '2026-02-17 02:55:24', '2026-02-17 05:56:05'),
(220, 39, 'CABAC Laboratory Fee Updated', 'Super Admin updated Laboratory Fee (Fiduciary)', 'info', 1, '2026-02-17 02:55:24', '2026-02-25 06:15:31'),
(221, 40, 'CABAC Laboratory Fee Updated', 'Super Admin updated Laboratory Fee (Fiduciary)', 'info', 0, '2026-02-17 02:55:24', NULL),
(224, 25, 'CABAC Athletics Updated', 'Super Admin updated Athletics (Fiduciary)', 'info', 0, '2026-02-17 03:03:41', NULL),
(225, 32, 'CABAC Athletics Updated', 'Super Admin updated Athletics (Fiduciary)', 'info', 0, '2026-02-17 03:03:41', NULL),
(226, 34, 'CABAC Athletics Updated', 'Super Admin updated Athletics (Fiduciary)', 'info', 1, '2026-02-17 03:03:41', '2026-02-25 07:43:06'),
(227, 36, 'CABAC Athletics Updated', 'Super Admin updated Athletics (Fiduciary)', 'info', 0, '2026-02-17 03:03:41', NULL),
(228, 38, 'CABAC Athletics Updated', 'Super Admin updated Athletics (Fiduciary)', 'info', 1, '2026-02-17 03:03:41', '2026-02-17 05:56:05'),
(229, 39, 'CABAC Athletics Updated', 'Super Admin updated Athletics (Fiduciary)', 'info', 1, '2026-02-17 03:03:41', '2026-02-25 06:15:31'),
(230, 40, 'CABAC Athletics Updated', 'Super Admin updated Athletics (Fiduciary)', 'info', 0, '2026-02-17 03:03:41', NULL),
(233, 25, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 0, '2026-02-17 03:15:39', NULL),
(234, 32, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 0, '2026-02-17 03:15:39', NULL),
(235, 34, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 1, '2026-02-17 03:15:39', '2026-02-25 07:43:06'),
(236, 36, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 0, '2026-02-17 03:15:39', NULL),
(237, 38, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 1, '2026-02-17 03:15:39', '2026-02-17 05:56:05'),
(238, 39, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 1, '2026-02-17 03:15:39', '2026-02-25 06:15:31'),
(239, 40, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 0, '2026-02-17 03:15:39', NULL),
(242, 25, 'CABAC Cultural Updated', 'Super Admin updated Cultural (Fiduciary)', 'info', 0, '2026-02-17 04:54:11', NULL),
(243, 32, 'CABAC Cultural Updated', 'Super Admin updated Cultural (Fiduciary)', 'info', 0, '2026-02-17 04:54:11', NULL),
(244, 34, 'CABAC Cultural Updated', 'Super Admin updated Cultural (Fiduciary)', 'info', 1, '2026-02-17 04:54:11', '2026-02-25 07:43:06'),
(245, 36, 'CABAC Cultural Updated', 'Super Admin updated Cultural (Fiduciary)', 'info', 0, '2026-02-17 04:54:11', NULL),
(246, 38, 'CABAC Cultural Updated', 'Super Admin updated Cultural (Fiduciary)', 'info', 1, '2026-02-17 04:54:11', '2026-02-17 05:56:05'),
(247, 39, 'CABAC Cultural Updated', 'Super Admin updated Cultural (Fiduciary)', 'info', 1, '2026-02-17 04:54:11', '2026-02-25 06:15:31'),
(248, 40, 'CABAC Cultural Updated', 'Super Admin updated Cultural (Fiduciary)', 'info', 0, '2026-02-17 04:54:11', NULL),
(251, 25, 'CABAC Development Fee Updated', 'Super Admin updated Development Fee (Fiduciary)', 'info', 0, '2026-02-17 05:55:12', NULL),
(252, 32, 'CABAC Development Fee Updated', 'Super Admin updated Development Fee (Fiduciary)', 'info', 0, '2026-02-17 05:55:12', NULL),
(253, 34, 'CABAC Development Fee Updated', 'Super Admin updated Development Fee (Fiduciary)', 'info', 1, '2026-02-17 05:55:12', '2026-02-25 07:43:06'),
(254, 36, 'CABAC Development Fee Updated', 'Super Admin updated Development Fee (Fiduciary)', 'info', 0, '2026-02-17 05:55:12', NULL),
(255, 38, 'CABAC Development Fee Updated', 'Super Admin updated Development Fee (Fiduciary)', 'info', 1, '2026-02-17 05:55:12', '2026-02-17 05:56:05'),
(256, 39, 'CABAC Development Fee Updated', 'Super Admin updated Development Fee (Fiduciary)', 'info', 1, '2026-02-17 05:55:12', '2026-02-25 06:15:31'),
(257, 40, 'CABAC Development Fee Updated', 'Super Admin updated Development Fee (Fiduciary)', 'info', 0, '2026-02-17 05:55:12', NULL),
(260, 25, 'CABAC OJT Fee Updated', 'Super Admin updated OJT Fee (Fiduciary)', 'info', 0, '2026-02-17 06:00:06', NULL),
(261, 32, 'CABAC OJT Fee Updated', 'Super Admin updated OJT Fee (Fiduciary)', 'info', 0, '2026-02-17 06:00:06', NULL),
(262, 34, 'CABAC OJT Fee Updated', 'Super Admin updated OJT Fee (Fiduciary)', 'info', 1, '2026-02-17 06:00:06', '2026-02-25 07:43:06'),
(263, 36, 'CABAC OJT Fee Updated', 'Super Admin updated OJT Fee (Fiduciary)', 'info', 0, '2026-02-17 06:00:06', NULL),
(264, 38, 'CABAC OJT Fee Updated', 'Super Admin updated OJT Fee (Fiduciary)', 'info', 1, '2026-02-17 06:00:06', '2026-02-17 06:37:52'),
(265, 39, 'CABAC OJT Fee Updated', 'Super Admin updated OJT Fee (Fiduciary)', 'info', 1, '2026-02-17 06:00:06', '2026-02-25 06:15:31'),
(266, 40, 'CABAC OJT Fee Updated', 'Super Admin updated OJT Fee (Fiduciary)', 'info', 0, '2026-02-17 06:00:06', NULL),
(269, 25, 'CABAC NSTP Updated', 'Super Admin updated NSTP (Fiduciary)', 'info', 0, '2026-02-17 06:12:02', NULL),
(270, 32, 'CABAC NSTP Updated', 'Super Admin updated NSTP (Fiduciary)', 'info', 0, '2026-02-17 06:12:02', NULL),
(271, 34, 'CABAC NSTP Updated', 'Super Admin updated NSTP (Fiduciary)', 'info', 1, '2026-02-17 06:12:02', '2026-02-25 07:43:06'),
(272, 36, 'CABAC NSTP Updated', 'Super Admin updated NSTP (Fiduciary)', 'info', 0, '2026-02-17 06:12:02', NULL),
(273, 38, 'CABAC NSTP Updated', 'Super Admin updated NSTP (Fiduciary)', 'info', 1, '2026-02-17 06:12:02', '2026-02-17 06:37:52'),
(274, 39, 'CABAC NSTP Updated', 'Super Admin updated NSTP (Fiduciary)', 'info', 1, '2026-02-17 06:12:02', '2026-02-25 06:15:31'),
(275, 40, 'CABAC NSTP Updated', 'Super Admin updated NSTP (Fiduciary)', 'info', 0, '2026-02-17 06:12:02', NULL),
(278, 25, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 0, '2026-02-17 06:37:24', NULL),
(279, 32, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 0, '2026-02-17 06:37:24', NULL),
(280, 34, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 1, '2026-02-17 06:37:24', '2026-02-25 07:43:06'),
(281, 36, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 0, '2026-02-17 06:37:24', NULL),
(282, 38, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 1, '2026-02-17 06:37:24', '2026-02-17 06:37:52'),
(283, 39, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 1, '2026-02-17 06:37:24', '2026-02-25 06:15:31'),
(284, 40, 'CABAC Computer Fee Updated', 'Super Admin updated Computer Fee (Fiduciary)', 'info', 0, '2026-02-17 06:37:24', NULL),
(287, 25, 'CABAC SCUAA Fee Updated', 'Super Admin updated SCUAA Fee (Fiduciary)', 'info', 0, '2026-02-17 06:50:11', NULL),
(288, 32, 'CABAC SCUAA Fee Updated', 'Super Admin updated SCUAA Fee (Fiduciary)', 'info', 0, '2026-02-17 06:50:11', NULL),
(289, 34, 'CABAC SCUAA Fee Updated', 'Super Admin updated SCUAA Fee (Fiduciary)', 'info', 1, '2026-02-17 06:50:11', '2026-02-25 07:43:06'),
(290, 36, 'CABAC SCUAA Fee Updated', 'Super Admin updated SCUAA Fee (Fiduciary)', 'info', 0, '2026-02-17 06:50:11', NULL),
(291, 38, 'CABAC SCUAA Fee Updated', 'Super Admin updated SCUAA Fee (Fiduciary)', 'info', 1, '2026-02-17 06:50:11', '2026-02-19 02:05:34'),
(292, 39, 'CABAC SCUAA Fee Updated', 'Super Admin updated SCUAA Fee (Fiduciary)', 'info', 1, '2026-02-17 06:50:11', '2026-02-25 06:15:31'),
(293, 40, 'CABAC SCUAA Fee Updated', 'Super Admin updated SCUAA Fee (Fiduciary)', 'info', 0, '2026-02-17 06:50:11', NULL),
(296, 25, 'CABAC Student Activity Fee Updated', 'Super Admin updated Student Activity Fee (Fiduciary)', 'info', 0, '2026-02-17 06:52:13', NULL),
(297, 32, 'CABAC Student Activity Fee Updated', 'Super Admin updated Student Activity Fee (Fiduciary)', 'info', 0, '2026-02-17 06:52:13', NULL),
(298, 34, 'CABAC Student Activity Fee Updated', 'Super Admin updated Student Activity Fee (Fiduciary)', 'info', 1, '2026-02-17 06:52:13', '2026-02-25 07:43:06'),
(299, 36, 'CABAC Student Activity Fee Updated', 'Super Admin updated Student Activity Fee (Fiduciary)', 'info', 0, '2026-02-17 06:52:13', NULL),
(300, 38, 'CABAC Student Activity Fee Updated', 'Super Admin updated Student Activity Fee (Fiduciary)', 'info', 1, '2026-02-17 06:52:13', '2026-02-19 02:05:34'),
(301, 39, 'CABAC Student Activity Fee Updated', 'Super Admin updated Student Activity Fee (Fiduciary)', 'info', 1, '2026-02-17 06:52:13', '2026-02-25 06:15:31'),
(302, 40, 'CABAC Student Activity Fee Updated', 'Super Admin updated Student Activity Fee (Fiduciary)', 'info', 0, '2026-02-17 06:52:13', NULL),
(305, 25, 'CABAC Guidance Fee Updated', 'Super Admin updated Guidance Fee (Fiduciary)', 'info', 0, '2026-02-19 02:03:53', NULL),
(306, 32, 'CABAC Guidance Fee Updated', 'Super Admin updated Guidance Fee (Fiduciary)', 'info', 0, '2026-02-19 02:03:53', NULL),
(307, 34, 'CABAC Guidance Fee Updated', 'Super Admin updated Guidance Fee (Fiduciary)', 'info', 1, '2026-02-19 02:03:53', '2026-02-25 07:43:06'),
(308, 36, 'CABAC Guidance Fee Updated', 'Super Admin updated Guidance Fee (Fiduciary)', 'info', 0, '2026-02-19 02:03:53', NULL),
(309, 38, 'CABAC Guidance Fee Updated', 'Super Admin updated Guidance Fee (Fiduciary)', 'info', 1, '2026-02-19 02:03:53', '2026-02-19 02:05:34'),
(310, 39, 'CABAC Guidance Fee Updated', 'Super Admin updated Guidance Fee (Fiduciary)', 'info', 1, '2026-02-19 02:03:53', '2026-02-25 06:15:31'),
(311, 40, 'CABAC Guidance Fee Updated', 'Super Admin updated Guidance Fee (Fiduciary)', 'info', 0, '2026-02-19 02:03:53', NULL),
(314, 25, 'CABAC Graduation Fee Updated', 'Super Admin updated Graduation Fee (Fiduciary)', 'info', 0, '2026-02-19 02:07:22', NULL),
(315, 32, 'CABAC Graduation Fee Updated', 'Super Admin updated Graduation Fee (Fiduciary)', 'info', 0, '2026-02-19 02:07:22', NULL),
(316, 34, 'CABAC Graduation Fee Updated', 'Super Admin updated Graduation Fee (Fiduciary)', 'info', 1, '2026-02-19 02:07:22', '2026-02-25 07:43:06'),
(317, 36, 'CABAC Graduation Fee Updated', 'Super Admin updated Graduation Fee (Fiduciary)', 'info', 0, '2026-02-19 02:07:22', NULL),
(318, 38, 'CABAC Graduation Fee Updated', 'Super Admin updated Graduation Fee (Fiduciary)', 'info', 1, '2026-02-19 02:07:22', '2026-02-19 02:50:16'),
(319, 39, 'CABAC Graduation Fee Updated', 'Super Admin updated Graduation Fee (Fiduciary)', 'info', 1, '2026-02-19 02:07:22', '2026-02-25 06:15:31'),
(320, 40, 'CABAC Graduation Fee Updated', 'Super Admin updated Graduation Fee (Fiduciary)', 'info', 0, '2026-02-19 02:07:22', NULL),
(323, 25, 'CABAC Insurance Fee Updated', 'Super Admin updated Insurance Fee (Fiduciary)', 'info', 0, '2026-02-19 02:11:40', NULL),
(324, 32, 'CABAC Insurance Fee Updated', 'Super Admin updated Insurance Fee (Fiduciary)', 'info', 0, '2026-02-19 02:11:40', NULL),
(325, 34, 'CABAC Insurance Fee Updated', 'Super Admin updated Insurance Fee (Fiduciary)', 'info', 1, '2026-02-19 02:11:40', '2026-02-25 07:43:06'),
(326, 36, 'CABAC Insurance Fee Updated', 'Super Admin updated Insurance Fee (Fiduciary)', 'info', 0, '2026-02-19 02:11:40', NULL),
(327, 38, 'CABAC Insurance Fee Updated', 'Super Admin updated Insurance Fee (Fiduciary)', 'info', 1, '2026-02-19 02:11:40', '2026-02-19 02:50:16'),
(328, 39, 'CABAC Insurance Fee Updated', 'Super Admin updated Insurance Fee (Fiduciary)', 'info', 1, '2026-02-19 02:11:40', '2026-02-25 06:15:31'),
(329, 40, 'CABAC Insurance Fee Updated', 'Super Admin updated Insurance Fee (Fiduciary)', 'info', 0, '2026-02-19 02:11:40', NULL),
(332, 25, 'CABAC Internet Fee Updated', 'Super Admin updated Internet Fee (Fiduciary)', 'info', 0, '2026-02-19 02:16:10', NULL),
(333, 32, 'CABAC Internet Fee Updated', 'Super Admin updated Internet Fee (Fiduciary)', 'info', 0, '2026-02-19 02:16:10', NULL),
(334, 34, 'CABAC Internet Fee Updated', 'Super Admin updated Internet Fee (Fiduciary)', 'info', 1, '2026-02-19 02:16:10', '2026-02-25 07:43:06'),
(335, 36, 'CABAC Internet Fee Updated', 'Super Admin updated Internet Fee (Fiduciary)', 'info', 0, '2026-02-19 02:16:10', NULL),
(336, 38, 'CABAC Internet Fee Updated', 'Super Admin updated Internet Fee (Fiduciary)', 'info', 1, '2026-02-19 02:16:10', '2026-02-19 02:50:16'),
(337, 39, 'CABAC Internet Fee Updated', 'Super Admin updated Internet Fee (Fiduciary)', 'info', 1, '2026-02-19 02:16:10', '2026-02-25 06:15:31'),
(338, 40, 'CABAC Internet Fee Updated', 'Super Admin updated Internet Fee (Fiduciary)', 'info', 0, '2026-02-19 02:16:10', NULL),
(341, 25, 'CABAC Library Fee Updated', 'Super Admin updated Library Fee (Fiduciary)', 'info', 0, '2026-02-19 02:20:55', NULL),
(342, 32, 'CABAC Library Fee Updated', 'Super Admin updated Library Fee (Fiduciary)', 'info', 0, '2026-02-19 02:20:55', NULL),
(343, 34, 'CABAC Library Fee Updated', 'Super Admin updated Library Fee (Fiduciary)', 'info', 1, '2026-02-19 02:20:55', '2026-02-25 07:43:06'),
(344, 36, 'CABAC Library Fee Updated', 'Super Admin updated Library Fee (Fiduciary)', 'info', 0, '2026-02-19 02:20:55', NULL),
(345, 38, 'CABAC Library Fee Updated', 'Super Admin updated Library Fee (Fiduciary)', 'info', 1, '2026-02-19 02:20:55', '2026-02-19 02:50:16'),
(346, 39, 'CABAC Library Fee Updated', 'Super Admin updated Library Fee (Fiduciary)', 'info', 1, '2026-02-19 02:20:55', '2026-02-25 06:15:31'),
(347, 40, 'CABAC Library Fee Updated', 'Super Admin updated Library Fee (Fiduciary)', 'info', 0, '2026-02-19 02:20:55', NULL),
(350, 25, 'CABAC Medical Dental Fee Updated', 'Super Admin updated Medical Dental Fee (Fiduciary)', 'info', 0, '2026-02-19 02:24:52', NULL),
(351, 32, 'CABAC Medical Dental Fee Updated', 'Super Admin updated Medical Dental Fee (Fiduciary)', 'info', 0, '2026-02-19 02:24:52', NULL),
(352, 34, 'CABAC Medical Dental Fee Updated', 'Super Admin updated Medical Dental Fee (Fiduciary)', 'info', 1, '2026-02-19 02:24:52', '2026-02-25 07:43:06'),
(353, 36, 'CABAC Medical Dental Fee Updated', 'Super Admin updated Medical Dental Fee (Fiduciary)', 'info', 0, '2026-02-19 02:24:52', NULL),
(354, 38, 'CABAC Medical Dental Fee Updated', 'Super Admin updated Medical Dental Fee (Fiduciary)', 'info', 1, '2026-02-19 02:24:52', '2026-02-19 02:50:16'),
(355, 39, 'CABAC Medical Dental Fee Updated', 'Super Admin updated Medical Dental Fee (Fiduciary)', 'info', 1, '2026-02-19 02:24:52', '2026-02-25 06:15:31'),
(356, 40, 'CABAC Medical Dental Fee Updated', 'Super Admin updated Medical Dental Fee (Fiduciary)', 'info', 0, '2026-02-19 02:24:52', NULL),
(359, 25, 'CABAC School Organ Fee Updated', 'Super Admin updated School Organ Fee (Fiduciary)', 'info', 0, '2026-02-19 02:46:52', NULL),
(360, 32, 'CABAC School Organ Fee Updated', 'Super Admin updated School Organ Fee (Fiduciary)', 'info', 0, '2026-02-19 02:46:52', NULL),
(361, 34, 'CABAC School Organ Fee Updated', 'Super Admin updated School Organ Fee (Fiduciary)', 'info', 1, '2026-02-19 02:46:52', '2026-02-25 07:43:06'),
(362, 36, 'CABAC School Organ Fee Updated', 'Super Admin updated School Organ Fee (Fiduciary)', 'info', 0, '2026-02-19 02:46:52', NULL),
(363, 38, 'CABAC School Organ Fee Updated', 'Super Admin updated School Organ Fee (Fiduciary)', 'info', 1, '2026-02-19 02:46:52', '2026-02-19 02:50:16'),
(364, 39, 'CABAC School Organ Fee Updated', 'Super Admin updated School Organ Fee (Fiduciary)', 'info', 1, '2026-02-19 02:46:52', '2026-02-25 06:15:31'),
(365, 40, 'CABAC School Organ Fee Updated', 'Super Admin updated School Organ Fee (Fiduciary)', 'info', 0, '2026-02-19 02:46:52', NULL),
(368, 25, 'CABAC Student Council Fee Updated', 'Super Admin updated Student Council Fee (Fiduciary)', 'info', 0, '2026-02-19 02:49:47', NULL),
(369, 32, 'CABAC Student Council Fee Updated', 'Super Admin updated Student Council Fee (Fiduciary)', 'info', 0, '2026-02-19 02:49:47', NULL),
(370, 34, 'CABAC Student Council Fee Updated', 'Super Admin updated Student Council Fee (Fiduciary)', 'info', 1, '2026-02-19 02:49:47', '2026-02-25 07:43:06'),
(371, 36, 'CABAC Student Council Fee Updated', 'Super Admin updated Student Council Fee (Fiduciary)', 'info', 0, '2026-02-19 02:49:47', NULL),
(372, 38, 'CABAC Student Council Fee Updated', 'Super Admin updated Student Council Fee (Fiduciary)', 'info', 1, '2026-02-19 02:49:47', '2026-02-19 02:50:16'),
(373, 39, 'CABAC Student Council Fee Updated', 'Super Admin updated Student Council Fee (Fiduciary)', 'info', 1, '2026-02-19 02:49:47', '2026-02-25 06:15:31'),
(374, 40, 'CABAC Student Council Fee Updated', 'Super Admin updated Student Council Fee (Fiduciary)', 'info', 0, '2026-02-19 02:49:47', NULL),
(376, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱250,000.00. (Feb 19, 2026 4:18 AM)', 'success', 1, '2026-02-19 03:18:12', '2026-02-19 03:46:36'),
(377, 38, 'Sub-Department ICT Utilization Updated', 'The budget utilization for ICT has been updated. Fiscal Year 2026 - Total Expenditures: ₱267,270.85.', 'info', 1, '2026-02-19 20:47:30', '2026-02-19 20:47:37'),
(378, 38, 'Sub-Department ICT Utilization Updated', 'The budget utilization for ICT has been updated. Fiscal Year 2026 - Total Expenditures: ₱267,270.85.', 'info', 1, '2026-02-19 21:45:43', '2026-02-19 21:46:24'),
(379, 38, 'Sub-Department ICT Utilization Updated', 'The budget utilization for ICT has been updated. Fiscal Year 2026 - Total Expenditures: ₱25,000.00.', 'info', 1, '2026-02-19 22:07:19', '2026-02-21 02:09:06'),
(380, 38, 'Sub-Department ICT Utilization Updated', 'The budget utilization for ICT has been updated. Fiscal Year 2026 - Total Expenditures: ₱25,000.00.', 'info', 1, '2026-02-19 22:07:54', '2026-02-21 02:09:06'),
(381, 38, 'Sub-Department ICT Utilization Updated', 'The budget utilization for ICT has been updated. Fiscal Year 2026 - Total Expenditures: ₱25,000.00.', 'info', 1, '2026-02-20 01:48:29', '2026-02-21 02:09:06'),
(382, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.04. (Feb 23, 2026 1:44 AM)', 'success', 1, '2026-02-23 00:44:14', '2026-02-23 06:03:42'),
(383, 38, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱872,901.33. (Feb 23, 2026 6:26 AM)', 'info', 1, '2026-02-23 05:26:54', '2026-02-23 06:03:42'),
(384, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱872,901.33. (Feb 23, 2026 7:03 AM)', 'info', 1, '2026-02-23 06:03:37', '2026-02-23 06:03:42'),
(385, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱1,827,016.12. (Feb 23, 2026 7:36 AM)', 'info', 1, '2026-02-23 06:36:00', '2026-02-23 08:30:32'),
(386, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱1,827,016.12. (Feb 23, 2026 7:56 AM)', 'info', 1, '2026-02-23 06:56:02', '2026-02-23 08:30:32'),
(387, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱1,827,016.12. (Feb 23, 2026 8:19 AM)', 'info', 1, '2026-02-23 07:19:38', '2026-02-23 08:30:32'),
(388, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.04. (Feb 23, 2026 7:09 PM)', 'success', 1, '2026-02-23 18:09:59', '2026-02-23 18:13:57'),
(390, 25, 'New File Submission', 'New SUPPLEMENTAL submission from Department One (Computer Studies) on Feb 23, 2026 8:10 PM (Submission ID: 32)', 'info', 0, '2026-02-23 19:10:12', NULL),
(391, 32, 'New File Submission', 'New SUPPLEMENTAL submission from Department One (Computer Studies) on Feb 23, 2026 8:10 PM (Submission ID: 32)', 'info', 0, '2026-02-23 19:10:12', NULL),
(394, 25, 'SUPPLEMENTAL Submitted', 'Department One from Computer Studies has submitted a SUPPLEMENTAL file. (Feb 23, 2026 8:10 PM)', 'info', 0, '2026-02-23 19:10:12', NULL),
(395, 32, 'SUPPLEMENTAL Submitted', 'Department One from Computer Studies has submitted a SUPPLEMENTAL file. (Feb 23, 2026 8:10 PM)', 'info', 0, '2026-02-23 19:10:12', NULL),
(396, 34, 'SUPPLEMENTAL Submitted', 'Department One from Computer Studies has submitted a SUPPLEMENTAL file. (Feb 23, 2026 8:10 PM)', 'info', 1, '2026-02-23 19:10:12', '2026-02-25 07:43:06'),
(398, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.04. (Feb 23, 2026 8:30 PM)', 'success', 1, '2026-02-23 19:30:11', '2026-02-23 19:36:11'),
(399, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.04. (Feb 23, 2026 8:36 PM)', 'success', 1, '2026-02-23 19:36:27', '2026-02-23 19:36:58'),
(400, 38, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱1,461,031.62. (Feb 23, 2026 9:21 PM)', 'info', 1, '2026-02-23 20:21:13', '2026-02-23 20:21:53'),
(401, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱1,501,103.62. (Feb 23, 2026 9:44 PM)', 'info', 1, '2026-02-23 20:44:12', '2026-02-23 21:10:40'),
(402, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱1,809,162.12. (Feb 23, 2026 10:44 PM)', 'info', 1, '2026-02-23 21:44:19', '2026-02-24 03:27:01'),
(403, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:31 AM)', 'success', 1, '2026-02-24 00:31:39', '2026-02-24 03:27:01'),
(404, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:31 AM)', 'success', 1, '2026-02-24 00:31:47', '2026-02-24 03:27:01'),
(405, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:31 AM)', 'success', 1, '2026-02-24 00:31:53', '2026-02-24 03:27:01'),
(406, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:31 AM)', 'success', 1, '2026-02-24 00:31:58', '2026-02-24 03:27:01'),
(407, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:32 AM)', 'success', 1, '2026-02-24 00:32:03', '2026-02-24 03:27:01'),
(408, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:32 AM)', 'success', 1, '2026-02-24 00:32:07', '2026-02-24 03:27:01'),
(409, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:35 AM)', 'success', 1, '2026-02-24 00:35:52', '2026-02-24 03:27:01'),
(410, 38, 'Budget Allocation Updated', 'Mark Joseph Humbid has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:35 AM)', 'success', 1, '2026-02-24 00:35:58', '2026-02-24 03:27:01'),
(411, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:36 AM)', 'success', 1, '2026-02-24 00:36:25', '2026-02-24 03:27:01'),
(412, 38, 'Budget Allocation Updated', 'Mark Joseph Humbid has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:36 AM)', 'success', 1, '2026-02-24 00:36:29', '2026-02-24 03:27:01'),
(413, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:36 AM)', 'success', 1, '2026-02-24 00:36:50', '2026-02-24 03:27:01'),
(414, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:37 AM)', 'success', 1, '2026-02-24 00:37:03', '2026-02-24 03:27:01'),
(415, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Feb 24, 2026 1:46 AM)', 'success', 1, '2026-02-24 00:46:32', '2026-02-24 03:27:01'),
(416, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱1,439,850.00. (Feb 24, 2026 1:56 AM)', 'success', 1, '2026-02-24 00:56:25', '2026-02-24 03:27:01'),
(417, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.05. (Feb 24, 2026 2:19 AM)', 'success', 1, '2026-02-24 01:19:11', '2026-02-24 03:27:01'),
(418, 25, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Budget Office (Fiscal Year 2026). Total Expenditures: ₱61,000.00. (Feb 24, 2026 2:29 AM)', 'info', 0, '2026-02-24 01:29:06', NULL),
(419, 32, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Budget Office (Fiscal Year 2026). Total Expenditures: ₱61,000.00. (Feb 24, 2026 2:29 AM)', 'info', 0, '2026-02-24 01:29:06', NULL),
(421, 38, 'Sub-Department ICT Utilization Created', 'A new budget utilization has been created for ICT. Fiscal Year 2026 - Total Expenditures: ₱25,000.00.', 'info', 1, '2026-02-24 02:51:13', '2026-02-24 03:27:01'),
(423, 25, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Budget Office (Fiscal Year 2026). Total Expenditures: ₱61,000.00. (Feb 25, 2026 1:52 AM)', 'info', 0, '2026-02-25 00:52:57', NULL),
(424, 32, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Budget Office (Fiscal Year 2026). Total Expenditures: ₱61,000.00. (Feb 25, 2026 1:52 AM)', 'info', 0, '2026-02-25 00:52:57', NULL),
(426, 38, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Computer Studies (Fiscal Year 2024). Total Expenditures: ₱10,000.00. (Feb 25, 2026 4:05 AM)', 'info', 1, '2026-02-25 03:05:23', '2026-02-25 03:05:37'),
(428, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2024). Total Expenditures: ₱10,000.00. (Feb 25, 2026 4:06 AM)', 'info', 1, '2026-02-25 03:06:23', '2026-02-25 03:06:40'),
(430, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2024). Total Expenditures: ₱50,000.00. (Feb 25, 2026 4:29 AM)', 'info', 1, '2026-02-25 03:29:30', '2026-02-25 03:29:34'),
(432, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2024). Total Expenditures: ₱50,000.00. (Feb 25, 2026 4:30 AM)', 'info', 1, '2026-02-25 03:30:47', '2026-02-25 03:36:54'),
(434, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2024). Total Expenditures: ₱50,000.00. (Feb 25, 2026 4:31 AM)', 'info', 1, '2026-02-25 03:31:07', '2026-02-25 03:36:54'),
(436, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2024). Total Expenditures: ₱50,000.00. (Feb 25, 2026 4:35 AM)', 'info', 1, '2026-02-25 03:35:02', '2026-02-25 03:36:54'),
(438, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱382,727.50. (Feb 25, 2026 4:39 AM)', 'info', 1, '2026-02-25 03:39:25', '2026-02-25 04:38:40'),
(440, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2024). Total Expenditures: ₱50,000.00. (Feb 25, 2026 5:36 AM)', 'info', 1, '2026-02-25 04:36:58', '2026-02-25 04:38:40'),
(442, 38, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Computer Studies (Fiscal Year 2025). Total Expenditures: ₱1,624,757.29. (Feb 25, 2026 6:50 AM)', 'info', 1, '2026-02-25 05:50:11', '2026-02-25 05:51:34'),
(444, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2025). Total Expenditures: ₱1,624,757.29. (Feb 25, 2026 6:55 AM)', 'info', 1, '2026-02-25 05:55:18', '2026-02-25 05:55:28'),
(446, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2025). Total Expenditures: ₱1,624,757.29. (Feb 25, 2026 6:57 AM)', 'info', 1, '2026-02-25 05:57:56', '2026-02-25 05:58:10'),
(448, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2025). Total Expenditures: ₱1,624,757.29. (Feb 25, 2026 7:05 AM)', 'info', 1, '2026-02-25 06:05:29', '2026-02-25 06:06:00'),
(450, 25, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Budget Office (Fiscal Year 2025). Total Expenditures: ₱20,000.00. (Feb 25, 2026 7:10 AM)', 'info', 0, '2026-02-25 06:10:21', NULL),
(451, 32, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Budget Office (Fiscal Year 2025). Total Expenditures: ₱20,000.00. (Feb 25, 2026 7:10 AM)', 'info', 0, '2026-02-25 06:10:21', NULL),
(453, 39, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Guidance Office (Fiscal Year 2025). Total Expenditures: ₱20,000.00. (Feb 25, 2026 7:12 AM)', 'info', 1, '2026-02-25 06:12:45', '2026-02-25 06:15:31'),
(454, 25, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Budget Office (Fiscal Year 2025). Total Expenditures: ₱20,000.00. (Feb 25, 2026 7:14 AM)', 'info', 0, '2026-02-25 06:14:07', NULL),
(455, 32, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Budget Office (Fiscal Year 2025). Total Expenditures: ₱20,000.00. (Feb 25, 2026 7:14 AM)', 'info', 0, '2026-02-25 06:14:07', NULL),
(457, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,838,405.55. (Feb 25, 2026 8:04 AM)', 'success', 1, '2026-02-25 07:04:21', '2026-02-25 07:05:09'),
(459, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2025). Total Expenditures: ₱1,474,207.29. (Feb 25, 2026 8:16 AM)', 'info', 1, '2026-02-25 07:16:13', '2026-02-25 07:16:28'),
(461, 39, 'New Purchase Request', 'A new Purchase Request (PR #PR-2026-0001) has been submitted for Guidance Office. Your purchase request is being processed, please wait for the delivery. You will get informed right away.', 'info', 1, '2026-02-25 07:42:50', '2026-02-25 07:44:56'),
(462, 36, 'New Purchase Request', 'A new Purchase Request (PR #PR-2026-0001) has been submitted for Guidance Office. Please check your Purchase Orders page.', 'info', 0, '2026-02-25 07:42:50', NULL),
(464, 25, 'Purchase Request Status Updated', 'Purchase Request (PR #PR-2026-0001) for Guidance Office has been marked as PROCESSING.', 'info', 0, '2026-02-25 07:42:50', NULL),
(465, 32, 'Purchase Request Status Updated', 'Purchase Request (PR #PR-2026-0001) for Guidance Office has been marked as PROCESSING.', 'info', 0, '2026-02-25 07:42:50', NULL),
(467, 39, 'Purchase Request Delivered', 'Your Purchase Request (PR #PR-2026-0001) has been delivered to the Supply Office and is ready for pickup. Please click \'Order Received\' once you have received the items.', 'success', 1, '2026-02-25 07:44:20', '2026-02-25 07:44:56'),
(469, 25, 'Purchase Request Status Updated', 'Purchase Request (PR #PR-2026-0001) for Guidance Office has been marked as DELIVERED.', 'info', 0, '2026-02-25 07:44:20', NULL),
(470, 32, 'Purchase Request Status Updated', 'Purchase Request (PR #PR-2026-0001) for Guidance Office has been marked as DELIVERED.', 'info', 0, '2026-02-25 07:44:20', NULL);
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`, `read_at`) VALUES
(472, 34, 'Purchase Request Completed', 'Purchase Request (PR #PR-2026-0001) has been received and marked as complete by the department.', 'success', 1, '2026-02-25 07:45:10', '2026-03-09 01:52:39'),
(473, 36, 'Purchase Request Completed', 'Purchase Request (PR #PR-2026-0001) has been received and marked as complete by the department.', 'success', 0, '2026-02-25 07:45:10', NULL),
(475, 25, 'Purchase Request Completed', 'Purchase Request (PR #PR-2026-0001) has been received and marked as complete by the department.', 'success', 0, '2026-02-25 07:45:10', NULL),
(476, 32, 'Purchase Request Completed', 'Purchase Request (PR #PR-2026-0001) has been received and marked as complete by the department.', 'success', 0, '2026-02-25 07:45:10', NULL),
(478, 38, 'Sub-Department ICT Utilization Created', 'A new budget utilization has been created for ICT. Fiscal Year 2025 - Total Expenditures: ₱217,270.85.', 'info', 1, '2026-02-25 08:09:47', '2026-02-25 08:09:55'),
(480, 38, 'Sub-Department ICT Utilization Updated', 'The budget utilization for ICT has been updated. Fiscal Year 2025 - Total Expenditures: ₱217,270.85.', 'info', 1, '2026-02-25 08:14:31', '2026-02-25 08:17:32'),
(482, 38, 'Sub-Department ICT Utilization Updated', 'The budget utilization for ICT has been updated. Fiscal Year 2025 - Total Expenditures: ₱217,270.85.', 'info', 1, '2026-02-25 08:19:30', '2026-02-25 08:23:18'),
(484, 25, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Budget Office (Fiscal Year 2025). Total Expenditures: ₱10,000.00. (Feb 25, 2026 9:42 AM)', 'info', 0, '2026-02-25 08:42:39', NULL),
(485, 32, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Budget Office (Fiscal Year 2025). Total Expenditures: ₱10,000.00. (Feb 25, 2026 9:42 AM)', 'info', 0, '2026-02-25 08:42:39', NULL),
(487, 47, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱5,000.00. (Feb 26, 2026 5:19 PM)', 'info', 1, '2026-02-26 16:19:48', '2026-02-26 16:19:52'),
(489, 25, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 0, '2026-02-27 01:03:39', NULL),
(490, 32, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 0, '2026-02-27 01:03:39', NULL),
(491, 34, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 1, '2026-02-27 01:03:39', '2026-03-09 01:52:39'),
(492, 36, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 0, '2026-02-27 01:03:39', NULL),
(493, 38, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 1, '2026-02-27 01:03:39', '2026-02-27 01:03:48'),
(494, 39, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 1, '2026-02-27 01:03:39', '2026-03-03 08:30:32'),
(495, 40, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 0, '2026-02-27 01:03:39', NULL),
(497, 47, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 1, '2026-02-27 01:03:39', '2026-02-27 01:28:19'),
(499, 25, 'CABAC Rent Income Updated', 'Super Admin updated Rent Income (Fiduciary)', 'info', 0, '2026-02-27 01:08:10', NULL),
(500, 32, 'CABAC Rent Income Updated', 'Super Admin updated Rent Income (Fiduciary)', 'info', 0, '2026-02-27 01:08:10', NULL),
(501, 34, 'CABAC Rent Income Updated', 'Super Admin updated Rent Income (Fiduciary)', 'info', 1, '2026-02-27 01:08:10', '2026-03-09 01:52:39'),
(502, 36, 'CABAC Rent Income Updated', 'Super Admin updated Rent Income (Fiduciary)', 'info', 0, '2026-02-27 01:08:10', NULL),
(503, 38, 'CABAC Rent Income Updated', 'Super Admin updated Rent Income (Fiduciary)', 'info', 1, '2026-02-27 01:08:10', '2026-02-27 01:08:20'),
(504, 39, 'CABAC Rent Income Updated', 'Super Admin updated Rent Income (Fiduciary)', 'info', 1, '2026-02-27 01:08:10', '2026-03-03 08:30:32'),
(505, 40, 'CABAC Rent Income Updated', 'Super Admin updated Rent Income (Fiduciary)', 'info', 0, '2026-02-27 01:08:10', NULL),
(507, 47, 'CABAC Rent Income Updated', 'Super Admin updated Rent Income (Fiduciary)', 'info', 1, '2026-02-27 01:08:10', '2026-02-27 01:28:19'),
(509, 25, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 0, '2026-02-27 01:13:42', NULL),
(510, 32, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 0, '2026-02-27 01:13:42', NULL),
(511, 34, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 1, '2026-02-27 01:13:42', '2026-03-09 01:52:39'),
(512, 36, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 0, '2026-02-27 01:13:42', NULL),
(513, 38, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 1, '2026-02-27 01:13:42', '2026-02-27 01:13:53'),
(514, 39, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 1, '2026-02-27 01:13:42', '2026-03-03 08:30:32'),
(515, 40, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 0, '2026-02-27 01:13:42', NULL),
(517, 47, 'CABAC Trust Fund Updated', 'Super Admin updated Trust Fund (Fiduciary)', 'info', 1, '2026-02-27 01:13:42', '2026-02-27 01:28:19'),
(519, 25, 'CABAC Handbook Updated', 'Super Admin updated Handbook (Fiduciary)', 'info', 0, '2026-02-27 01:23:11', NULL),
(520, 32, 'CABAC Handbook Updated', 'Super Admin updated Handbook (Fiduciary)', 'info', 0, '2026-02-27 01:23:11', NULL),
(521, 34, 'CABAC Handbook Updated', 'Super Admin updated Handbook (Fiduciary)', 'info', 1, '2026-02-27 01:23:11', '2026-03-09 01:52:39'),
(522, 36, 'CABAC Handbook Updated', 'Super Admin updated Handbook (Fiduciary)', 'info', 0, '2026-02-27 01:23:11', NULL),
(523, 38, 'CABAC Handbook Updated', 'Super Admin updated Handbook (Fiduciary)', 'info', 1, '2026-02-27 01:23:11', '2026-02-27 01:23:22'),
(524, 39, 'CABAC Handbook Updated', 'Super Admin updated Handbook (Fiduciary)', 'info', 1, '2026-02-27 01:23:11', '2026-03-03 08:30:32'),
(525, 40, 'CABAC Handbook Updated', 'Super Admin updated Handbook (Fiduciary)', 'info', 0, '2026-02-27 01:23:11', NULL),
(527, 47, 'CABAC Handbook Updated', 'Super Admin updated Handbook (Fiduciary)', 'info', 1, '2026-02-27 01:23:11', '2026-02-27 01:28:19'),
(528, 47, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Admin (Fiscal Year 2025). Total Expenditures: ₱91,000.00. (Feb 27, 2026 2:58 AM)', 'info', 1, '2026-02-27 01:58:47', '2026-02-27 02:22:14'),
(529, 47, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱1,000.00. (Feb 27, 2026 4:20 AM)', 'info', 1, '2026-02-27 03:20:12', '2026-02-27 03:39:55'),
(530, 47, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Admin (Fiscal Year 2025). Total Expenditures: ₱22,000.00. (Feb 27, 2026 4:39 AM)', 'info', 1, '2026-02-27 03:39:21', '2026-02-27 03:39:55'),
(531, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2025). Total Expenditures: ₱6,600.00. (Feb 27, 2026 6:12 AM)', 'info', 1, '2026-02-27 05:12:27', '2026-02-27 05:12:35'),
(533, 25, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:27', NULL),
(534, 32, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:27', NULL),
(536, 34, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 1, '2026-03-03 08:20:27', '2026-03-09 01:52:39'),
(538, 25, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:31', NULL),
(539, 32, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:31', NULL),
(541, 34, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 1, '2026-03-03 08:20:31', '2026-03-09 01:52:39'),
(543, 25, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:39', NULL),
(544, 32, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:39', NULL),
(546, 34, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 1, '2026-03-03 08:20:39', '2026-03-09 01:52:39'),
(548, 25, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:51', NULL),
(549, 32, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:51', NULL),
(551, 34, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 1, '2026-03-03 08:20:51', '2026-03-09 01:52:39'),
(553, 25, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:58', NULL),
(554, 32, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 0, '2026-03-03 08:20:58', NULL),
(556, 34, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:20 AM)', 'info', 1, '2026-03-03 08:20:58', '2026-03-09 01:52:39'),
(558, 25, 'LIB Updated', 'Department Two from Engineering has updated their Line-Item Budget (LIB). Please review the changes. (Mar 3, 2026 9:31 AM)', 'info', 0, '2026-03-03 08:31:29', NULL),
(559, 32, 'LIB Updated', 'Department Two from Engineering has updated their Line-Item Budget (LIB). Please review the changes. (Mar 3, 2026 9:31 AM)', 'info', 0, '2026-03-03 08:31:29', NULL),
(561, 34, 'LIB Updated', 'Department Two from Engineering has updated their Line-Item Budget (LIB). Please review the changes. (Mar 3, 2026 9:31 AM)', 'info', 1, '2026-03-03 08:31:29', '2026-03-09 01:52:39'),
(563, 25, 'LIB Updated', 'Department Two from Engineering has updated their Line-Item Budget (LIB). Please review the changes. (Mar 3, 2026 9:38 AM)', 'info', 0, '2026-03-03 08:38:07', NULL),
(564, 32, 'LIB Updated', 'Department Two from Engineering has updated their Line-Item Budget (LIB). Please review the changes. (Mar 3, 2026 9:38 AM)', 'info', 0, '2026-03-03 08:38:07', NULL),
(566, 34, 'LIB Updated', 'Department Two from Engineering has updated their Line-Item Budget (LIB). Please review the changes. (Mar 3, 2026 9:38 AM)', 'info', 1, '2026-03-03 08:38:07', '2026-03-09 01:52:39'),
(568, 25, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:39 AM)', 'info', 0, '2026-03-03 08:39:11', NULL),
(569, 32, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:39 AM)', 'info', 0, '2026-03-03 08:39:11', NULL),
(571, 34, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:39 AM)', 'info', 1, '2026-03-03 08:39:11', '2026-03-09 01:52:39'),
(573, 25, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:43 AM)', 'info', 0, '2026-03-03 08:43:53', NULL),
(574, 32, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:43 AM)', 'info', 0, '2026-03-03 08:43:53', NULL),
(576, 34, 'LIB Submitted', 'Department Two from Engineering has submitted a new Line-Item Budget (LIB). Please review it. (Mar 3, 2026 9:43 AM)', 'info', 1, '2026-03-03 08:43:53', '2026-03-09 01:52:39'),
(577, 39, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Engineering (Fiscal Year 2026). Total Expenditures: ₱42,000.00. (Mar 4, 2026 6:01 AM)', 'info', 1, '2026-03-04 05:01:36', '2026-03-04 05:31:32'),
(579, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 5, 2026 5:14 AM)', 'info', 0, '2026-03-05 04:14:06', NULL),
(580, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 5, 2026 5:14 AM)', 'info', 0, '2026-03-05 04:14:06', NULL),
(582, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 5, 2026 5:14 AM)', 'info', 1, '2026-03-05 04:14:06', '2026-03-09 01:52:39'),
(584, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 5, 2026 5:15 AM)', 'info', 0, '2026-03-05 04:15:16', NULL),
(585, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 5, 2026 5:15 AM)', 'info', 0, '2026-03-05 04:15:16', NULL),
(587, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 5, 2026 5:15 AM)', 'info', 1, '2026-03-05 04:15:16', '2026-03-09 01:52:39'),
(588, 38, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱9,340.00. (Mar 5, 2026 5:18 AM)', 'info', 1, '2026-03-05 04:18:15', '2026-03-05 04:18:28'),
(589, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱1,400.00. (Mar 5, 2026 5:42 AM)', 'info', 1, '2026-03-05 04:42:47', '2026-03-05 06:39:10'),
(590, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱4,280.00. (Mar 5, 2026 7:38 AM)', 'info', 1, '2026-03-05 06:38:25', '2026-03-05 06:39:10'),
(592, 25, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 5, 2026 8:46 AM)', 'info', 0, '2026-03-05 07:46:49', NULL),
(593, 32, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 5, 2026 8:46 AM)', 'info', 0, '2026-03-05 07:46:49', NULL),
(595, 34, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 5, 2026 8:46 AM)', 'info', 1, '2026-03-05 07:46:49', '2026-03-09 01:52:39'),
(596, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱400.00. (Mar 5, 2026 8:47 AM)', 'info', 1, '2026-03-05 07:47:46', '2026-03-05 07:47:54'),
(597, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱400.00. (Mar 5, 2026 8:54 AM)', 'info', 1, '2026-03-05 07:54:19', '2026-03-05 07:54:25'),
(598, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱400.00. (Mar 5, 2026 9:06 AM)', 'info', 1, '2026-03-05 08:06:50', '2026-03-05 08:26:30'),
(599, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱400.00. (Mar 5, 2026 9:26 AM)', 'info', 1, '2026-03-05 08:26:24', '2026-03-05 08:26:30'),
(600, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱400.00. (Mar 5, 2026 9:28 AM)', 'info', 1, '2026-03-05 08:28:39', '2026-03-05 08:40:06'),
(601, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱400.00. (Mar 5, 2026 9:36 AM)', 'info', 1, '2026-03-05 08:36:28', '2026-03-05 08:40:06'),
(603, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 5, 2026 9:38 AM)', 'info', 0, '2026-03-05 08:38:27', NULL),
(604, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 5, 2026 9:38 AM)', 'info', 0, '2026-03-05 08:38:27', NULL),
(606, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 5, 2026 9:38 AM)', 'info', 1, '2026-03-05 08:38:27', '2026-03-09 01:52:39'),
(608, 25, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 5, 2026 9:39 AM)', 'info', 0, '2026-03-05 08:39:18', NULL),
(609, 32, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 5, 2026 9:39 AM)', 'info', 0, '2026-03-05 08:39:18', NULL),
(611, 34, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 5, 2026 9:39 AM)', 'info', 1, '2026-03-05 08:39:18', '2026-03-09 01:52:39'),
(612, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱400.00. (Mar 5, 2026 9:39 AM)', 'info', 1, '2026-03-05 08:39:59', '2026-03-05 08:40:06'),
(614, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 6, 2026 4:01 AM)', 'info', 0, '2026-03-06 03:01:37', NULL),
(615, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 6, 2026 4:01 AM)', 'info', 0, '2026-03-06 03:01:37', NULL),
(617, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 6, 2026 4:01 AM)', 'info', 1, '2026-03-06 03:01:37', '2026-03-09 01:52:39'),
(618, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱200.00. (Mar 6, 2026 4:44 AM)', 'info', 1, '2026-03-06 03:44:46', '2026-03-06 03:47:37'),
(619, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱0.00. (Mar 6, 2026 4:46 AM)', 'info', 1, '2026-03-06 03:46:44', '2026-03-06 03:47:37'),
(621, 25, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 6, 2026 7:50 AM)', 'info', 0, '2026-03-06 06:50:37', NULL),
(622, 32, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 6, 2026 7:50 AM)', 'info', 0, '2026-03-06 06:50:37', NULL),
(624, 34, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 6, 2026 7:50 AM)', 'info', 1, '2026-03-06 06:50:37', '2026-03-09 01:52:39'),
(626, 25, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 6, 2026 8:12 AM)', 'info', 0, '2026-03-06 07:12:26', NULL),
(627, 32, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 6, 2026 8:12 AM)', 'info', 0, '2026-03-06 07:12:26', NULL),
(629, 34, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 6, 2026 8:12 AM)', 'info', 1, '2026-03-06 07:12:26', '2026-03-09 01:52:39'),
(631, 25, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 6, 2026 8:14 AM)', 'info', 0, '2026-03-06 07:14:07', NULL),
(632, 32, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 6, 2026 8:14 AM)', 'info', 0, '2026-03-06 07:14:07', NULL),
(634, 34, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 6, 2026 8:14 AM)', 'info', 1, '2026-03-06 07:14:07', '2026-03-09 01:52:39'),
(636, 25, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 6, 2026 8:33 AM)', 'info', 0, '2026-03-06 07:33:31', NULL),
(637, 32, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 6, 2026 8:33 AM)', 'info', 0, '2026-03-06 07:33:31', NULL),
(639, 34, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 6, 2026 8:33 AM)', 'info', 1, '2026-03-06 07:33:31', '2026-03-09 01:52:39'),
(641, 25, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 6, 2026 8:34 AM)', 'info', 0, '2026-03-06 07:34:08', NULL),
(642, 32, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 6, 2026 8:34 AM)', 'info', 0, '2026-03-06 07:34:08', NULL),
(644, 34, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 6, 2026 8:34 AM)', 'info', 1, '2026-03-06 07:34:08', '2026-03-09 01:52:39'),
(646, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 6, 2026 8:46 AM)', 'info', 0, '2026-03-06 07:46:38', NULL),
(647, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 6, 2026 8:46 AM)', 'info', 0, '2026-03-06 07:46:38', NULL),
(649, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 6, 2026 8:46 AM)', 'info', 1, '2026-03-06 07:46:38', '2026-03-09 01:52:39'),
(650, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱600.00. (Mar 6, 2026 8:55 AM)', 'info', 1, '2026-03-06 07:55:15', '2026-03-07 02:28:36'),
(652, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:00 AM)', 'info', 0, '2026-03-07 02:00:38', NULL),
(653, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:00 AM)', 'info', 0, '2026-03-07 02:00:38', NULL),
(655, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:00 AM)', 'info', 1, '2026-03-07 02:00:38', '2026-03-09 01:52:39'),
(657, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:06 AM)', 'info', 0, '2026-03-07 02:06:12', NULL),
(658, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:06 AM)', 'info', 0, '2026-03-07 02:06:12', NULL),
(660, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:06 AM)', 'info', 1, '2026-03-07 02:06:12', '2026-03-09 01:52:39'),
(662, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:08 AM)', 'info', 0, '2026-03-07 02:08:37', NULL),
(663, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:08 AM)', 'info', 0, '2026-03-07 02:08:37', NULL),
(665, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 7, 2026 3:08 AM)', 'info', 1, '2026-03-07 02:08:37', '2026-03-09 01:52:39'),
(667, 25, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 7, 2026 3:26 AM)', 'info', 0, '2026-03-07 02:26:31', NULL),
(668, 32, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 7, 2026 3:26 AM)', 'info', 0, '2026-03-07 02:26:31', NULL),
(670, 34, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 7, 2026 3:26 AM)', 'info', 1, '2026-03-07 02:26:31', '2026-03-09 01:52:39'),
(672, 25, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 7, 2026 3:27 AM)', 'info', 0, '2026-03-07 02:27:03', NULL),
(673, 32, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 7, 2026 3:27 AM)', 'info', 0, '2026-03-07 02:27:03', NULL),
(675, 34, 'Supplemental PPMP Submitted', 'Department One from Computer Studies has submitted a Supplemental PPMP file. (Mar 7, 2026 3:27 AM)', 'info', 1, '2026-03-07 02:27:03', '2026-03-09 01:52:39'),
(676, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱500.00. (Mar 7, 2026 3:28 AM)', 'info', 1, '2026-03-07 02:28:30', '2026-03-07 02:28:36'),
(678, 25, 'LIB Updated', 'Admin One from Admin has updated their Line-Item Budget (LIB). Please review the changes. (Mar 7, 2026 3:33 AM)', 'info', 0, '2026-03-07 02:33:32', NULL),
(679, 32, 'LIB Updated', 'Admin One from Admin has updated their Line-Item Budget (LIB). Please review the changes. (Mar 7, 2026 3:33 AM)', 'info', 0, '2026-03-07 02:33:32', NULL),
(681, 34, 'LIB Updated', 'Admin One from Admin has updated their Line-Item Budget (LIB). Please review the changes. (Mar 7, 2026 3:33 AM)', 'info', 1, '2026-03-07 02:33:32', '2026-03-09 01:52:39'),
(683, 25, 'PPMP Updated', 'Admin One from Admin has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 7, 2026 5:43 AM)', 'info', 0, '2026-03-07 04:43:45', NULL),
(684, 32, 'PPMP Updated', 'Admin One from Admin has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 7, 2026 5:43 AM)', 'info', 0, '2026-03-07 04:43:45', NULL),
(686, 34, 'PPMP Updated', 'Admin One from Admin has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 7, 2026 5:43 AM)', 'info', 1, '2026-03-07 04:43:45', '2026-03-09 01:52:39'),
(687, 47, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱1,600.00. (Mar 7, 2026 5:44 AM)', 'info', 1, '2026-03-07 04:44:55', '2026-03-07 04:45:26'),
(689, 25, 'Supplemental PPMP Submitted', 'Admin One from Admin has submitted a Supplemental PPMP file. (Mar 7, 2026 5:47 AM)', 'info', 0, '2026-03-07 04:47:04', NULL),
(690, 32, 'Supplemental PPMP Submitted', 'Admin One from Admin has submitted a Supplemental PPMP file. (Mar 7, 2026 5:47 AM)', 'info', 0, '2026-03-07 04:47:04', NULL),
(692, 34, 'Supplemental PPMP Submitted', 'Admin One from Admin has submitted a Supplemental PPMP file. (Mar 7, 2026 5:47 AM)', 'info', 1, '2026-03-07 04:47:04', '2026-03-09 01:52:39'),
(693, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱3,600.00. (Mar 7, 2026 5:48 AM)', 'info', 1, '2026-03-07 04:48:45', '2026-03-07 05:06:28'),
(694, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.05. (Mar 7, 2026 7:24 AM)', 'success', 1, '2026-03-07 06:24:13', '2026-03-07 08:19:59'),
(696, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 3:50 AM)', 'info', 0, '2026-03-09 02:50:59', NULL),
(697, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 3:50 AM)', 'info', 0, '2026-03-09 02:50:59', NULL),
(699, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 3:50 AM)', 'info', 0, '2026-03-09 02:50:59', NULL),
(701, 25, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 9, 2026 3:58 AM)', 'info', 0, '2026-03-09 02:58:33', NULL),
(702, 32, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 9, 2026 3:58 AM)', 'info', 0, '2026-03-09 02:58:33', NULL),
(704, 34, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 9, 2026 3:58 AM)', 'info', 0, '2026-03-09 02:58:33', NULL),
(705, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱200.00. (Mar 9, 2026 3:59 AM)', 'info', 1, '2026-03-09 02:59:23', '2026-03-09 02:59:29'),
(706, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱100.00. (Mar 9, 2026 4:00 AM)', 'info', 1, '2026-03-09 03:00:13', '2026-03-09 03:18:39'),
(707, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱100.00. (Mar 9, 2026 4:15 AM)', 'info', 1, '2026-03-09 03:15:40', '2026-03-09 03:18:39'),
(708, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱0.00. (Mar 9, 2026 4:19 AM)', 'info', 1, '2026-03-09 03:19:03', '2026-03-09 03:24:11'),
(710, 25, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 9, 2026 4:22 AM)', 'info', 0, '2026-03-09 03:22:32', NULL),
(711, 32, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 9, 2026 4:22 AM)', 'info', 0, '2026-03-09 03:22:32', NULL),
(713, 34, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 9, 2026 4:22 AM)', 'info', 0, '2026-03-09 03:22:32', NULL),
(714, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱200.00. (Mar 9, 2026 4:24 AM)', 'info', 1, '2026-03-09 03:24:04', '2026-03-09 03:24:11'),
(715, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱0.00. (Mar 9, 2026 8:30 AM)', 'info', 1, '2026-03-09 07:30:31', '2026-03-09 07:37:28'),
(717, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 10:12 AM)', 'info', 0, '2026-03-09 09:12:34', NULL),
(718, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 10:12 AM)', 'info', 0, '2026-03-09 09:12:34', NULL),
(720, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 10:12 AM)', 'info', 0, '2026-03-09 09:12:34', NULL),
(722, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 11:42 AM)', 'info', 0, '2026-03-09 10:42:05', NULL),
(723, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 11:42 AM)', 'info', 0, '2026-03-09 10:42:05', NULL),
(725, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 9, 2026 11:42 AM)', 'info', 0, '2026-03-09 10:42:05', NULL),
(727, 25, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 9, 2026 11:45 AM)', 'info', 0, '2026-03-09 10:45:52', NULL),
(728, 32, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 9, 2026 11:45 AM)', 'info', 0, '2026-03-09 10:45:52', NULL),
(730, 34, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 9, 2026 11:45 AM)', 'info', 0, '2026-03-09 10:45:52', NULL),
(731, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱18,520.00. (Mar 9, 2026 11:50 AM)', 'info', 1, '2026-03-09 10:50:26', '2026-03-09 10:50:32'),
(732, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱10,010.00. (Mar 9, 2026 11:52 AM)', 'info', 1, '2026-03-09 10:52:35', '2026-03-09 10:54:30'),
(733, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱16,180.00. (Mar 10, 2026 2:47 AM)', 'info', 1, '2026-03-10 01:47:59', '2026-03-10 01:48:23'),
(734, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱33,490.00. (Mar 10, 2026 3:12 AM)', 'info', 1, '2026-03-10 02:12:27', '2026-03-10 05:24:04'),
(735, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱0.00. (Mar 10, 2026 3:15 AM)', 'info', 1, '2026-03-10 02:15:37', '2026-03-10 05:24:04'),
(736, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱19,690.00. (Mar 10, 2026 3:33 AM)', 'info', 1, '2026-03-10 02:33:41', '2026-03-10 05:24:05'),
(738, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:21 AM)', 'info', 0, '2026-03-10 05:21:12', NULL),
(739, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:21 AM)', 'info', 0, '2026-03-10 05:21:12', NULL),
(741, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:21 AM)', 'info', 0, '2026-03-10 05:21:12', NULL),
(743, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:32 AM)', 'info', 0, '2026-03-10 05:32:42', NULL),
(744, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:32 AM)', 'info', 0, '2026-03-10 05:32:42', NULL),
(746, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:32 AM)', 'info', 0, '2026-03-10 05:32:42', NULL),
(748, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:52 AM)', 'info', 0, '2026-03-10 05:52:43', NULL),
(749, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:52 AM)', 'info', 0, '2026-03-10 05:52:43', NULL),
(751, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Mar 10, 2026 6:52 AM)', 'info', 0, '2026-03-10 05:52:43', NULL),
(753, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:01 AM)', 'info', 0, '2026-03-10 06:01:55', NULL),
(754, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:01 AM)', 'info', 0, '2026-03-10 06:01:55', NULL),
(756, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:01 AM)', 'info', 0, '2026-03-10 06:01:55', NULL),
(758, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:28 AM)', 'info', 0, '2026-03-10 06:28:02', NULL),
(759, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:28 AM)', 'info', 0, '2026-03-10 06:28:02', NULL),
(761, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:28 AM)', 'info', 0, '2026-03-10 06:28:02', NULL),
(763, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:30 AM)', 'info', 0, '2026-03-10 06:30:19', NULL),
(764, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:30 AM)', 'info', 0, '2026-03-10 06:30:19', NULL),
(766, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:30 AM)', 'info', 0, '2026-03-10 06:30:19', NULL),
(768, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:31 AM)', 'info', 0, '2026-03-10 06:31:17', NULL),
(769, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:31 AM)', 'info', 0, '2026-03-10 06:31:17', NULL),
(771, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 10, 2026 7:31 AM)', 'info', 0, '2026-03-10 06:31:17', NULL),
(772, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱14,880.00. (Mar 10, 2026 7:34 AM)', 'info', 1, '2026-03-10 06:34:58', '2026-03-16 00:33:12'),
(773, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱14,880.00. (Mar 10, 2026 7:46 AM)', 'info', 1, '2026-03-10 06:46:42', '2026-03-16 00:33:12'),
(774, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱14,880.00. (Mar 10, 2026 7:54 AM)', 'info', 1, '2026-03-10 06:54:42', '2026-03-16 00:33:12'),
(775, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱14,880.00. (Mar 10, 2026 8:01 AM)', 'info', 1, '2026-03-10 07:01:33', '2026-03-16 00:33:12'),
(776, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱14,880.00. (Mar 10, 2026 8:11 AM)', 'info', 1, '2026-03-10 07:11:51', '2026-03-16 00:33:12'),
(777, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱14,880.00. (Mar 10, 2026 8:38 AM)', 'info', 1, '2026-03-10 07:38:05', '2026-03-16 00:33:12'),
(778, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱16,880.00. (Mar 10, 2026 9:02 AM)', 'info', 1, '2026-03-10 08:02:45', '2026-03-16 00:33:12'),
(779, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱76,880.00. (Mar 10, 2026 9:15 AM)', 'info', 1, '2026-03-10 08:15:02', '2026-03-16 00:33:12'),
(780, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱116,880.00. (Mar 10, 2026 9:25 AM)', 'info', 1, '2026-03-10 08:25:12', '2026-03-16 00:33:12'),
(781, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱116,880.00. (Mar 10, 2026 11:55 PM)', 'info', 1, '2026-03-10 22:55:50', '2026-03-16 00:33:12'),
(782, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱51,000.00. (Mar 16, 2026 1:43 AM)', 'info', 1, '2026-03-16 00:43:19', '2026-03-16 00:45:21'),
(783, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱51,000.00. (Mar 16, 2026 1:45 AM)', 'info', 1, '2026-03-16 00:45:14', '2026-03-16 00:45:21'),
(784, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱51,000.00. (Mar 16, 2026 2:15 AM)', 'info', 1, '2026-03-16 01:15:04', '2026-03-16 03:39:47'),
(785, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱63,480.00. (Mar 16, 2026 2:57 AM)', 'info', 1, '2026-03-16 01:57:44', '2026-03-16 03:39:47'),
(786, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱52,280.00. (Mar 16, 2026 4:03 AM)', 'info', 1, '2026-03-16 03:03:26', '2026-03-16 03:39:47'),
(787, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱67,420.00. (Mar 16, 2026 4:05 AM)', 'info', 1, '2026-03-16 03:05:00', '2026-03-16 03:39:47'),
(788, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱68,790.00. (Mar 16, 2026 4:14 AM)', 'info', 1, '2026-03-16 03:14:00', '2026-03-16 03:39:47'),
(789, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱68,790.00. (Mar 16, 2026 4:19 AM)', 'info', 1, '2026-03-16 03:19:38', '2026-03-16 03:39:47'),
(790, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱68,790.00. (Mar 16, 2026 4:25 AM)', 'info', 1, '2026-03-16 03:25:05', '2026-03-16 03:39:47'),
(791, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱88,990.00. (Mar 16, 2026 4:26 AM)', 'info', 1, '2026-03-16 03:26:51', '2026-03-16 03:39:47'),
(792, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱200.00. (Mar 16, 2026 5:13 AM)', 'info', 1, '2026-03-16 04:13:53', '2026-03-16 04:20:14'),
(794, 25, 'LIB Updated', 'Admin One from Admin has updated their Line-Item Budget (LIB). Please review the changes. (Mar 16, 2026 6:56 AM)', 'info', 0, '2026-03-16 05:56:01', NULL),
(795, 32, 'LIB Updated', 'Admin One from Admin has updated their Line-Item Budget (LIB). Please review the changes. (Mar 16, 2026 6:56 AM)', 'info', 0, '2026-03-16 05:56:01', NULL),
(797, 34, 'LIB Updated', 'Admin One from Admin has updated their Line-Item Budget (LIB). Please review the changes. (Mar 16, 2026 6:56 AM)', 'info', 0, '2026-03-16 05:56:01', NULL),
(799, 25, 'PPMP Updated', 'Admin One from Admin has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 16, 2026 6:56 AM)', 'info', 0, '2026-03-16 05:56:22', NULL),
(800, 32, 'PPMP Updated', 'Admin One from Admin has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 16, 2026 6:56 AM)', 'info', 0, '2026-03-16 05:56:22', NULL),
(802, 34, 'PPMP Updated', 'Admin One from Admin has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Mar 16, 2026 6:56 AM)', 'info', 0, '2026-03-16 05:56:22', NULL),
(803, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱190,200.00. (Mar 16, 2026 6:57 AM)', 'info', 1, '2026-03-16 05:57:21', '2026-03-16 05:57:28'),
(804, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱200.00. (Mar 16, 2026 7:13 AM)', 'info', 1, '2026-03-16 06:13:47', '2026-03-16 06:13:53'),
(806, 25, 'PPMP Submitted', 'Admin One from Admin has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 16, 2026 7:15 AM)', 'info', 0, '2026-03-16 06:15:44', NULL),
(807, 32, 'PPMP Submitted', 'Admin One from Admin has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 16, 2026 7:15 AM)', 'info', 0, '2026-03-16 06:15:44', NULL),
(809, 34, 'PPMP Submitted', 'Admin One from Admin has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Mar 16, 2026 7:15 AM)', 'info', 0, '2026-03-16 06:15:44', NULL),
(811, 25, 'Supplemental PPMP Submitted', 'Admin One from Admin has submitted a Supplemental PPMP file. (Mar 16, 2026 7:16 AM)', 'info', 0, '2026-03-16 06:16:52', NULL),
(812, 32, 'Supplemental PPMP Submitted', 'Admin One from Admin has submitted a Supplemental PPMP file. (Mar 16, 2026 7:16 AM)', 'info', 0, '2026-03-16 06:16:52', NULL),
(814, 34, 'Supplemental PPMP Submitted', 'Admin One from Admin has submitted a Supplemental PPMP file. (Mar 16, 2026 7:16 AM)', 'info', 0, '2026-03-16 06:16:52', NULL),
(816, 25, 'Supplemental PPMP Updated', 'Admin One from Admin has updated a Supplemental PPMP file. (Mar 16, 2026 7:18 AM)', 'info', 0, '2026-03-16 06:18:42', NULL),
(817, 32, 'Supplemental PPMP Updated', 'Admin One from Admin has updated a Supplemental PPMP file. (Mar 16, 2026 7:18 AM)', 'info', 0, '2026-03-16 06:18:42', NULL),
(819, 34, 'Supplemental PPMP Updated', 'Admin One from Admin has updated a Supplemental PPMP file. (Mar 16, 2026 7:18 AM)', 'info', 0, '2026-03-16 06:18:42', NULL),
(820, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱2,600.00. (Mar 16, 2026 7:19 AM)', 'info', 1, '2026-03-16 06:19:46', '2026-03-16 06:19:57'),
(821, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱4,464.00. (Mar 16, 2026 7:24 AM)', 'info', 1, '2026-03-16 06:24:36', '2026-03-16 06:24:47'),
(822, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱4,864.00. (Mar 16, 2026 7:33 AM)', 'info', 0, '2026-03-16 06:33:15', NULL),
(823, 47, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Admin (Fiscal Year 2026). Total Expenditures: ₱200.00. (Mar 16, 2026 7:53 AM)', 'info', 0, '2026-03-16 06:53:56', NULL),
(825, 25, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 17, 2026 3:54 AM)', 'info', 0, '2026-03-17 02:54:47', NULL),
(826, 32, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 17, 2026 3:54 AM)', 'info', 0, '2026-03-17 02:54:47', NULL);
INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `created_at`, `read_at`) VALUES
(828, 34, 'LIB Updated', 'Department One from Computer Studies has updated their Line-Item Budget (LIB). Please review the changes. (Mar 17, 2026 3:54 AM)', 'info', 0, '2026-03-17 02:54:47', NULL),
(829, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱19,840.00. (Mar 17, 2026 9:18 AM)', 'info', 1, '2026-03-17 08:18:06', '2026-03-17 08:18:31'),
(831, 25, 'LIB Updated', 'Super Admin from Procurement Office has updated their Line-Item Budget (LIB). Please review the changes. (Mar 18, 2026 2:39 AM)', 'info', 0, '2026-03-18 01:39:19', NULL),
(832, 32, 'LIB Updated', 'Super Admin from Procurement Office has updated their Line-Item Budget (LIB). Please review the changes. (Mar 18, 2026 2:39 AM)', 'info', 0, '2026-03-18 01:39:19', NULL),
(834, 34, 'LIB Updated', 'Super Admin from Procurement Office has updated their Line-Item Budget (LIB). Please review the changes. (Mar 18, 2026 2:39 AM)', 'info', 0, '2026-03-18 01:39:19', NULL),
(836, 25, 'LIB Updated', 'Super Admin from Procurement Office has updated their Line-Item Budget (LIB). Please review the changes. (Mar 18, 2026 2:59 AM)', 'info', 0, '2026-03-18 01:59:32', NULL),
(837, 32, 'LIB Updated', 'Super Admin from Procurement Office has updated their Line-Item Budget (LIB). Please review the changes. (Mar 18, 2026 2:59 AM)', 'info', 0, '2026-03-18 01:59:32', NULL),
(839, 34, 'LIB Updated', 'Super Admin from Procurement Office has updated their Line-Item Budget (LIB). Please review the changes. (Mar 18, 2026 2:59 AM)', 'info', 0, '2026-03-18 01:59:32', NULL),
(841, 25, 'LIB Submitted', 'Super Admin from Procurement Office has submitted a new Line-Item Budget (LIB). Please review it. (Mar 18, 2026 3:00 AM)', 'info', 0, '2026-03-18 02:00:33', NULL),
(842, 32, 'LIB Submitted', 'Super Admin from Procurement Office has submitted a new Line-Item Budget (LIB). Please review it. (Mar 18, 2026 3:00 AM)', 'info', 0, '2026-03-18 02:00:33', NULL),
(844, 34, 'LIB Submitted', 'Super Admin from Procurement Office has submitted a new Line-Item Budget (LIB). Please review it. (Mar 18, 2026 3:00 AM)', 'info', 0, '2026-03-18 02:00:33', NULL),
(845, 25, 'LIB Submitted', 'Super Admin from Budget Office has submitted a new Line-Item Budget (LIB). Please review it. (Mar 18, 2026 3:06 AM)', 'info', 0, '2026-03-18 02:06:57', NULL),
(846, 32, 'LIB Submitted', 'Super Admin from Budget Office has submitted a new Line-Item Budget (LIB). Please review it. (Mar 18, 2026 3:06 AM)', 'info', 0, '2026-03-18 02:06:57', NULL),
(848, 34, 'LIB Submitted', 'Super Admin from Budget Office has submitted a new Line-Item Budget (LIB). Please review it. (Mar 18, 2026 3:06 AM)', 'info', 0, '2026-03-18 02:06:57', NULL),
(849, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.05. (Mar 23, 2026 6:14 AM)', 'success', 1, '2026-03-23 05:14:55', '2026-03-23 05:15:12'),
(850, 38, 'Budget Utilization Summary Updated', 'Super Admin has updated the budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱37,090.00. (Mar 23, 2026 6:33 AM)', 'info', 1, '2026-03-23 05:33:50', '2026-03-23 05:34:01'),
(851, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱5,190,473.05. (Apr 7, 2026 4:29 AM)', 'success', 1, '2026-04-07 02:29:14', '2026-04-07 02:39:25'),
(852, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱5,190,473.05. (Apr 7, 2026 4:35 AM)', 'success', 1, '2026-04-07 02:35:14', '2026-04-07 02:39:25'),
(853, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱5,190,473.05. (Apr 7, 2026 4:48 AM)', 'success', 1, '2026-04-07 02:48:16', '2026-04-07 03:13:03'),
(854, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2027). Overall Total: ₱6,190,473.05. (Apr 7, 2026 4:51 AM)', 'success', 1, '2026-04-07 02:51:14', '2026-04-07 03:13:03'),
(855, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱5,190,473.05. (Apr 7, 2026 6:12 AM)', 'success', 1, '2026-04-07 04:12:36', '2026-04-07 04:26:07'),
(856, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱5,190,473.05. (Apr 7, 2026 6:22 AM)', 'success', 1, '2026-04-07 04:22:32', '2026-04-07 04:26:07'),
(857, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱5,190,473.05. (Apr 7, 2026 6:25 AM)', 'success', 1, '2026-04-07 04:25:07', '2026-04-07 04:26:07'),
(858, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱5,190,473.05. (Apr 7, 2026 6:26 AM)', 'success', 1, '2026-04-07 04:26:39', '2026-04-07 04:26:45'),
(859, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Apr 7, 2026 6:32 AM)', 'success', 1, '2026-04-07 04:32:33', '2026-04-08 05:41:51'),
(860, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱3,910,398.05. (Apr 7, 2026 6:36 AM)', 'success', 1, '2026-04-07 04:36:55', '2026-04-08 05:41:51'),
(861, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Apr 7, 2026 6:39 AM)', 'success', 1, '2026-04-07 04:39:21', '2026-04-08 05:41:51'),
(862, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱0.00. (Apr 7, 2026 6:46 AM)', 'success', 1, '2026-04-07 04:46:43', '2026-04-08 05:41:51'),
(863, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.05. (Apr 7, 2026 7:01 AM)', 'success', 1, '2026-04-07 05:01:17', '2026-04-08 05:41:51'),
(864, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,910,398.05. (Apr 7, 2026 7:01 AM)', 'success', 1, '2026-04-07 05:01:27', '2026-04-08 05:41:51'),
(865, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱5,910,398.05. (Apr 7, 2026 7:07 AM)', 'success', 1, '2026-04-07 05:07:30', '2026-04-08 05:41:51'),
(866, 47, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Admin (Fiscal Year 2026). Overall Total: ₱4,000,000.00. (Apr 7, 2026 8:24 AM)', 'success', 0, '2026-04-07 06:24:47', NULL),
(867, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2027). Overall Total: ₱7,176,074.55. (Apr 7, 2026 8:26 AM)', 'success', 1, '2026-04-07 06:26:29', '2026-04-08 05:41:51'),
(868, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,176,074.55. (Apr 7, 2026 8:27 AM)', 'success', 1, '2026-04-07 06:27:32', '2026-04-08 05:41:51'),
(869, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2027). Overall Total: ₱6,176,074.55. (Apr 7, 2026 8:47 AM)', 'success', 1, '2026-04-07 06:47:43', '2026-04-08 05:41:51'),
(870, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱4,176,074.55. (Apr 7, 2026 8:52 AM)', 'success', 1, '2026-04-07 06:52:59', '2026-04-08 05:41:51'),
(871, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱3,622,428.05. (Apr 7, 2026 9:41 AM)', 'success', 1, '2026-04-07 07:41:56', '2026-04-08 05:41:51'),
(872, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱3,194,420.55. (Apr 7, 2026 10:12 AM)', 'success', 1, '2026-04-07 08:12:21', '2026-04-08 05:41:51'),
(873, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,894,420.55. (Apr 7, 2026 10:39 AM)', 'success', 1, '2026-04-07 08:39:23', '2026-04-08 05:41:51'),
(874, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,794,420.55. (Apr 7, 2026 10:49 AM)', 'success', 1, '2026-04-07 08:49:02', '2026-04-08 05:41:51'),
(875, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,794,420.55. (Apr 7, 2026 10:54 AM)', 'success', 1, '2026-04-07 08:54:05', '2026-04-08 05:41:51'),
(876, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,622,428.05. (Apr 8, 2026 10:31 AM)', 'success', 1, '2026-04-08 08:31:48', '2026-04-12 16:32:13'),
(877, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 3:52 AM)', 'info', 1, '2026-04-10 01:52:32', '2026-04-11 03:18:30'),
(878, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 3:52 AM)', 'info', 0, '2026-04-10 01:52:32', NULL),
(879, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 3:52 AM)', 'info', 0, '2026-04-10 01:52:32', NULL),
(881, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 3:52 AM)', 'info', 0, '2026-04-10 01:52:32', NULL),
(882, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:03 AM)', 'info', 1, '2026-04-10 02:03:46', '2026-04-11 03:18:30'),
(883, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:03 AM)', 'info', 0, '2026-04-10 02:03:46', NULL),
(884, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:03 AM)', 'info', 0, '2026-04-10 02:03:46', NULL),
(886, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:03 AM)', 'info', 0, '2026-04-10 02:03:46', NULL),
(887, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱2,622,428.05. (Apr 10, 2026 4:04 AM)', 'success', 1, '2026-04-10 02:04:53', '2026-04-12 16:32:13'),
(888, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:06 AM)', 'info', 1, '2026-04-10 02:06:41', '2026-04-11 03:18:30'),
(889, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:06 AM)', 'info', 0, '2026-04-10 02:06:41', NULL),
(890, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:06 AM)', 'info', 0, '2026-04-10 02:06:41', NULL),
(892, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:06 AM)', 'info', 0, '2026-04-10 02:06:41', NULL),
(893, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:07 AM)', 'info', 1, '2026-04-10 02:07:47', '2026-04-11 03:18:30'),
(894, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:07 AM)', 'info', 0, '2026-04-10 02:07:47', NULL),
(895, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:07 AM)', 'info', 0, '2026-04-10 02:07:47', NULL),
(897, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:07 AM)', 'info', 0, '2026-04-10 02:07:47', NULL),
(898, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:21 AM)', 'info', 1, '2026-04-10 02:21:41', '2026-04-11 03:18:30'),
(899, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:21 AM)', 'info', 0, '2026-04-10 02:21:41', NULL),
(900, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:21 AM)', 'info', 0, '2026-04-10 02:21:41', NULL),
(902, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:21 AM)', 'info', 0, '2026-04-10 02:21:41', NULL),
(903, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:27 AM)', 'info', 1, '2026-04-10 02:27:53', '2026-04-11 03:18:30'),
(904, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:27 AM)', 'info', 0, '2026-04-10 02:27:53', NULL),
(905, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:27 AM)', 'info', 0, '2026-04-10 02:27:53', NULL),
(907, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 4:27 AM)', 'info', 0, '2026-04-10 02:27:53', NULL),
(908, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2027). Overall Total: ₱2,422,739.40. (Apr 10, 2026 5:09 AM)', 'success', 1, '2026-04-10 03:09:26', '2026-04-12 16:32:13'),
(909, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 5:09 AM)', 'info', 1, '2026-04-10 03:09:58', '2026-04-11 03:18:30'),
(910, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 5:09 AM)', 'info', 0, '2026-04-10 03:09:58', NULL),
(911, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 5:09 AM)', 'info', 0, '2026-04-10 03:09:58', NULL),
(913, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 5:09 AM)', 'info', 0, '2026-04-10 03:09:58', NULL),
(914, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 6:32 AM)', 'info', 1, '2026-04-10 04:32:04', '2026-04-11 03:18:30'),
(915, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 6:32 AM)', 'info', 0, '2026-04-10 04:32:04', NULL),
(916, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 6:32 AM)', 'info', 0, '2026-04-10 04:32:04', NULL),
(918, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 6:32 AM)', 'info', 0, '2026-04-10 04:32:04', NULL),
(919, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 8:28 AM)', 'info', 1, '2026-04-10 06:28:01', '2026-04-11 03:18:30'),
(920, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 8:28 AM)', 'info', 0, '2026-04-10 06:28:01', NULL),
(921, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 8:28 AM)', 'info', 0, '2026-04-10 06:28:01', NULL),
(923, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 8:28 AM)', 'info', 0, '2026-04-10 06:28:01', NULL),
(924, 5, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 8:29 AM)', 'info', 1, '2026-04-10 06:29:51', '2026-04-11 03:18:30'),
(925, 25, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 8:29 AM)', 'info', 0, '2026-04-10 06:29:51', NULL),
(926, 32, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 8:29 AM)', 'info', 0, '2026-04-10 06:29:51', NULL),
(928, 34, 'LIB Submitted', 'Department One from Computer Studies has submitted a new Line-Item Budget (LIB). Please review it. (Apr 10, 2026 8:29 AM)', 'info', 0, '2026-04-10 06:29:51', NULL),
(929, 5, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 11, 2026 9:56 AM)', 'info', 1, '2026-04-11 07:56:09', '2026-04-14 03:31:09'),
(930, 25, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 11, 2026 9:56 AM)', 'info', 0, '2026-04-11 07:56:09', NULL),
(931, 32, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 11, 2026 9:56 AM)', 'info', 0, '2026-04-11 07:56:09', NULL),
(933, 34, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 11, 2026 9:56 AM)', 'info', 0, '2026-04-11 07:56:09', NULL),
(934, 5, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Apr 12, 2026 5:10 AM)', 'info', 1, '2026-04-12 03:10:59', '2026-04-14 03:31:09'),
(935, 25, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Apr 12, 2026 5:10 AM)', 'info', 0, '2026-04-12 03:10:59', NULL),
(936, 32, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Apr 12, 2026 5:10 AM)', 'info', 0, '2026-04-12 03:10:59', NULL),
(938, 34, 'PPMP Submitted', 'Department One from Computer Studies has submitted a new Project Procurement Management Plan (PPMP). Please review it. (Apr 12, 2026 5:10 AM)', 'info', 0, '2026-04-12 03:10:59', NULL),
(939, 38, 'Budget Allocation Updated', 'Super Admin has updated the budget allocation for Computer Studies (Fiscal Year 2027). Overall Total: ₱5,554,694.40. (Apr 14, 2026 5:24 AM)', 'success', 1, '2026-04-14 03:24:23', '2026-04-14 03:24:28'),
(940, 5, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 5:30 AM)', 'info', 1, '2026-04-14 03:30:41', '2026-04-14 03:31:09'),
(941, 25, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 5:30 AM)', 'info', 0, '2026-04-14 03:30:41', NULL),
(942, 32, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 5:30 AM)', 'info', 0, '2026-04-14 03:30:41', NULL),
(944, 34, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 5:30 AM)', 'info', 0, '2026-04-14 03:30:41', NULL),
(945, 38, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Computer Studies (Fiscal Year 2027). Total Expenditures: ₱800.00. (Apr 14, 2026 5:32 AM)', 'info', 1, '2026-04-14 03:32:44', '2026-04-14 03:32:50'),
(946, 5, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 9:02 AM)', 'info', 1, '2026-04-14 07:02:21', '2026-04-14 08:04:47'),
(947, 25, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 9:02 AM)', 'info', 0, '2026-04-14 07:02:21', NULL),
(948, 32, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 9:02 AM)', 'info', 0, '2026-04-14 07:02:21', NULL),
(950, 34, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 9:02 AM)', 'info', 0, '2026-04-14 07:02:21', NULL),
(951, 5, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 10:07 AM)', 'info', 1, '2026-04-14 08:07:14', '2026-04-14 08:07:32'),
(952, 25, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 10:07 AM)', 'info', 0, '2026-04-14 08:07:14', NULL),
(953, 32, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 10:07 AM)', 'info', 0, '2026-04-14 08:07:14', NULL),
(955, 34, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 14, 2026 10:07 AM)', 'info', 0, '2026-04-14 08:07:14', NULL),
(956, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2027). Overall Total: ₱5,790,473.05. (Apr 15, 2026 6:47 AM)', 'success', 1, '2026-04-15 04:47:16', '2026-04-15 04:47:22'),
(957, 38, 'Budget Allocation Created', 'Super Admin has created a new budget allocation for Computer Studies (Fiscal Year 2026). Overall Total: ₱3,190,473.05. (Apr 15, 2026 8:01 AM)', 'success', 1, '2026-04-15 06:01:22', '2026-04-15 06:01:29'),
(958, 5, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 15, 2026 9:23 AM)', 'info', 0, '2026-04-15 07:23:11', NULL),
(959, 25, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 15, 2026 9:23 AM)', 'info', 0, '2026-04-15 07:23:11', NULL),
(960, 34, 'PPMP Updated', 'Department One from Computer Studies has updated their Project Procurement Management Plan (PPMP). Please review the changes. (Apr 15, 2026 9:23 AM)', 'info', 0, '2026-04-15 07:23:11', NULL),
(961, 5, 'LIB Finalized - Computer Studies', 'Department One from Computer Studies has finalized their Line-Item Budget (LIB) for FY 2026. The budget is now available for utilization tracking.', 'success', 0, '2026-04-15 07:23:27', NULL),
(962, 25, 'LIB Finalized - Computer Studies', 'Department One from Computer Studies has finalized their Line-Item Budget (LIB) for FY 2026. The budget is now available for utilization tracking.', 'success', 0, '2026-04-15 07:23:27', NULL),
(963, 38, 'Budget Utilization Summary Created', 'Super Admin has created a new budget utilization summary for Computer Studies (Fiscal Year 2026). Total Expenditures: ₱16,200.00. (Apr 15, 2026 9:27 AM)', 'info', 1, '2026-04-15 07:27:46', '2026-04-15 07:27:59');

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
-- Table structure for table `ppmp`
--

CREATE TABLE `ppmp` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `ppmp_number` varchar(50) NOT NULL,
  `ppmp_type` enum('ppmp','supplemental') DEFAULT 'ppmp',
  `is_indicative` tinyint(1) DEFAULT 0,
  `is_final` tinyint(1) DEFAULT 0,
  `status` enum('draft','approved','rejected') DEFAULT 'draft',
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `notification_sent` tinyint(1) DEFAULT 0 COMMENT 'Whether budget office was notified'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ppmp`
--

INSERT INTO `ppmp` (`id`, `department_id`, `fiscal_year`, `ppmp_number`, `ppmp_type`, `is_indicative`, `is_final`, `status`, `created_by`, `created_at`, `updated_at`, `notification_sent`) VALUES
(68, 13, '2026', 'CS-2026-001', 'ppmp', 0, 1, 'approved', 38, '2026-04-15 07:22:20', '2026-04-15 07:23:11', 1);

-- --------------------------------------------------------

--
-- Table structure for table `ppmp_deductions`
--

CREATE TABLE `ppmp_deductions` (
  `id` int(11) NOT NULL,
  `ppmp_id` int(11) NOT NULL COMMENT 'Reference to ppmp.id',
  `ppmp_item_id` int(11) NOT NULL COMMENT 'Reference to ppmp_items.id',
  `purchase_request_id` int(11) NOT NULL COMMENT 'Reference to purchase_requests.id',
  `utilization_entry_id` int(11) NOT NULL COMMENT 'Reference to utilization entry',
  `department_id` int(11) NOT NULL COMMENT 'Department that owns this deduction',
  `expense_category` varchar(255) NOT NULL COMMENT 'Expense category name',
  `amount` decimal(15,2) NOT NULL COMMENT 'Deduction amount',
  `fiscal_year` varchar(10) NOT NULL COMMENT 'Fiscal year',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tracks PPMP item deductions through purchase requests';

--
-- Dumping data for table `ppmp_deductions`
--

INSERT INTO `ppmp_deductions` (`id`, `ppmp_id`, `ppmp_item_id`, `purchase_request_id`, `utilization_entry_id`, `department_id`, `expense_category`, `amount`, `fiscal_year`, `created_at`, `updated_at`) VALUES
(97, 68, 670, 1433, 0, 13, 'Fuel, Oil and Lubricants Expenses', 5000.00, '2026', '2026-04-15 07:27:46', '2026-04-15 07:27:46');

-- --------------------------------------------------------

--
-- Table structure for table `ppmp_history`
--

CREATE TABLE `ppmp_history` (
  `id` int(11) NOT NULL,
  `ppmp_id` int(11) NOT NULL,
  `action` varchar(50) NOT NULL,
  `changed_by` int(11) NOT NULL,
  `changes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ppmp_items`
--

CREATE TABLE `ppmp_items` (
  `id` int(11) NOT NULL,
  `ppmp_id` int(11) NOT NULL,
  `general_description` text NOT NULL,
  `project_type` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(50) NOT NULL,
  `recommended_mode` varchar(100) NOT NULL,
  `pre_procurement_conference` varchar(10) DEFAULT 'N',
  `start_procurement` date DEFAULT NULL,
  `end_ads_posting` date DEFAULT NULL,
  `expected_delivery` date DEFAULT NULL,
  `source_of_funds` varchar(100) NOT NULL,
  `estimated_budget` decimal(15,2) NOT NULL,
  `allocated_supporting_funds` decimal(15,2) DEFAULT 0.00,
  `remarks` text DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `deduction_remarks` text DEFAULT NULL COMMENT 'Expense category from deduction',
  `deducted_amount` decimal(15,2) DEFAULT 0.00 COMMENT 'Total amount deducted',
  `expense_category` varchar(255) DEFAULT NULL COMMENT 'Linked expense category',
  `lib_category` varchar(255) DEFAULT NULL COMMENT 'LIB category (A/B/C)',
  `lib_particulars` varchar(500) DEFAULT NULL COMMENT 'LIB expense description',
  `lib_account_code` varchar(50) DEFAULT NULL COMMENT 'UACS account code',
  `lib_synced` tinyint(1) DEFAULT 0 COMMENT 'Whether synced to LIB'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `ppmp_items`
--

INSERT INTO `ppmp_items` (`id`, `ppmp_id`, `general_description`, `project_type`, `quantity`, `unit`, `recommended_mode`, `pre_procurement_conference`, `start_procurement`, `end_ads_posting`, `expected_delivery`, `source_of_funds`, `estimated_budget`, `allocated_supporting_funds`, `remarks`, `sort_order`, `created_at`, `deduction_remarks`, `deducted_amount`, `expense_category`, `lib_category`, `lib_particulars`, `lib_account_code`, `lib_synced`) VALUES
(670, 68, 'ITEM 1', 'Goods', 2.00, 'pcs', 'Agency to Agency', 'N', '2026-04-01', '2026-05-01', '2026-06-01', 'IGF', 5000.00, 0.00, '', 0, '2026-04-15 07:23:11', NULL, 0.00, NULL, 'B. Maintenance & Other Operating Expenses', 'Fuel, Oil and Lubricants Expenses', '5020309000', 0);

-- --------------------------------------------------------

--
-- Table structure for table `ppmp_lib_mappings`
--

CREATE TABLE `ppmp_lib_mappings` (
  `id` int(11) NOT NULL,
  `ppmp_id` int(11) NOT NULL,
  `ppmp_item_id` int(11) NOT NULL,
  `lib_id` int(11) DEFAULT NULL,
  `lib_item_id` int(11) DEFAULT NULL,
  `fiscal_year` varchar(20) NOT NULL,
  `department_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prior_years_custom_columns`
--

CREATE TABLE `prior_years_custom_columns` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `col_key` varchar(100) NOT NULL,
  `col_name` varchar(255) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prior_years_custom_values`
--

CREATE TABLE `prior_years_custom_values` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `fiscal_year` int(11) NOT NULL,
  `col_key` varchar(100) NOT NULL,
  `expense_category` varchar(500) NOT NULL,
  `value` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `prior_years_entries`
--

CREATE TABLE `prior_years_entries` (
  `id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `expense_category` varchar(500) NOT NULL,
  `student_development` decimal(15,2) DEFAULT 0.00,
  `faculty_development` decimal(15,2) DEFAULT 0.00,
  `curriculum_development` decimal(15,2) DEFAULT 0.00,
  `facilities_development` decimal(15,2) DEFAULT 0.00,
  `development_fee` decimal(15,2) DEFAULT 0.00,
  `laboratory_fee` decimal(15,2) DEFAULT 0.00,
  `computer_fee` decimal(15,2) DEFAULT 0.00,
  `fiscal_year` int(11) NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `prior_years_entries`
--

INSERT INTO `prior_years_entries` (`id`, `department_id`, `expense_category`, `student_development`, `faculty_development`, `curriculum_development`, `facilities_development`, `development_fee`, `laboratory_fee`, `computer_fee`, `fiscal_year`, `sort_order`, `created_at`, `updated_at`) VALUES
(5, 26, 'ENTRY 1', 10000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-02-19 21:48:19', '2026-02-19 21:48:19'),
(31, 33, 'ENTIRHASKF', 10000.00, 10000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-02-20 01:57:36', '2026-02-20 01:57:36'),
(961, 23, '35745', 0.00, 0.00, 0.00, 0.00, 10000.00, 0.00, 0.00, 2025, 0, '2026-02-27 03:39:08', '2026-02-27 03:39:08'),
(962, 23, 'Travel', 1000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2025, 1, '2026-02-27 03:39:08', '2026-02-27 03:39:08'),
(963, 23, 'ICT', 1000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2025, 2, '2026-02-27 03:39:08', '2026-02-27 03:39:08'),
(964, 23, 'TEST ENTRY 1', 1000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2025, 3, '2026-02-27 03:39:08', '2026-02-27 03:39:08'),
(965, 23, 'TEST ENTRY 2', 1000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2025, 4, '2026-02-27 03:39:08', '2026-02-27 03:39:08'),
(966, 23, 'teest', 1000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2024, 0, '2026-02-27 05:12:00', '2026-02-27 05:12:00'),
(967, 23, 'test122313', 1000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2024, 1, '2026-02-27 05:12:00', '2026-02-27 05:12:00'),
(968, 23, 'TEST ENTRY 1', 10000.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2024, 2, '2026-02-27 05:12:00', '2026-02-27 05:12:00'),
(986, 14, 'Honoraria', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-03 08:20:27', '2026-03-03 08:20:27'),
(987, 14, 'Honoraria - Overload', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 1, '2026-03-03 08:20:27', '2026-03-03 08:20:27'),
(988, 14, 'Honoraria - Part-time', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 2, '2026-03-03 08:20:27', '2026-03-03 08:20:27'),
(992, 13, 'Honoraria - Overload', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-06 03:01:37', '2026-03-06 03:01:37'),
(993, 13, 'Office Supplies Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-06 07:46:38', '2026-03-06 07:46:38'),
(994, 13, 'Other Supplies and Materials Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 1, '2026-03-06 07:46:38', '2026-03-06 07:46:38'),
(995, 13, 'Textbooks and Instructional Materials Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 1, '2026-03-07 02:00:38', '2026-03-07 02:00:38'),
(996, 13, 'Other General Services', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 1, '2026-03-07 02:06:12', '2026-03-07 02:06:12'),
(997, 13, 'Auditing Services', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 1, '2026-03-07 02:08:37', '2026-03-07 02:08:37'),
(998, 23, 'Training Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-07 02:33:32', '2026-03-07 02:33:32'),
(999, 23, 'Auditing Services', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 1, '2026-03-07 02:33:32', '2026-03-07 02:33:32'),
(1000, 13, 'Honoraria', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1001, 13, 'Honoraria - Part-time', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 2, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1002, 13, 'Traveling Expenses - Local', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 3, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1003, 13, 'Training Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 4, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1004, 13, 'Accountable Forms Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 6, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1005, 13, 'Fuel, Oil and Lubricants Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 7, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1006, 13, 'Water Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 9, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1007, 13, 'Electricity Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 10, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1008, 13, 'Telephone Expenses - Mobile', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 11, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1009, 13, 'Telephone Expenses - Landline', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 12, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1010, 13, 'Internet Subscription', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 13, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1011, 13, 'Rewards and Incentives', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 14, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1012, 13, 'Janitorial Services', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 15, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1013, 13, 'Security Services', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 16, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1014, 13, 'Repairs and Maintenance - Power Supply Systems', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 17, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1015, 13, 'Repairs and Maintenance - School Buildings', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 18, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1016, 13, 'Repairs and Maintenance - Other Structures', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 19, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1017, 13, 'Repairs and Maintenance - Office Equipment', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 20, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1018, 13, 'Repairs and Maintenance - Motor Vehicles', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 21, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1019, 13, 'Insurance Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 22, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1020, 13, 'Labor and Wages', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 23, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1021, 13, 'ICT Software Subscription', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 24, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1022, 13, 'Other Maintenance and Operating Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 25, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1023, 13, 'School Buildings', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 26, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1024, 13, 'Office Equipment Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 27, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1025, 13, 'Information and Communication Technology Equipment', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 28, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1026, 13, 'Other Machinery and Equipment', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 29, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1027, 13, 'Furniture and Fixtures', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 30, '2026-03-09 02:50:59', '2026-03-09 02:50:59'),
(1028, 13, 'Honoraria - Military/Uniformed Personnel', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-10 06:01:55', '2026-03-10 06:01:55'),
(1029, 13, 'Office Supplies Expense', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 5, '2026-03-10 06:28:02', '2026-03-10 06:28:02'),
(1030, 13, 'Telephone Expenses-Mobile', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 11, '2026-03-10 06:28:02', '2026-03-10 06:28:02'),
(1031, 13, 'Telephone Expenses-Landline', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 12, '2026-03-10 06:28:02', '2026-03-10 06:28:02'),
(1032, 13, 'Repairs and Maintenance - Machinery and Equipment -Office Equipment', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 20, '2026-03-10 06:28:02', '2026-03-10 06:28:02'),
(1033, 13, 'Subscription Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 24, '2026-03-10 06:28:02', '2026-03-10 06:28:02'),
(1034, 23, 'Office Supplies Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-16 05:56:01', '2026-03-16 05:56:01'),
(1035, 16, 'Honoraria - Overload', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-18 01:39:19', '2026-03-18 01:39:19'),
(1036, 26, 'Honoraria - Overload', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 2026, 0, '2026-03-18 02:06:57', '2026-03-18 02:06:57'),
(1037, 13, 'Honoraria Overload', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, '2026-04-10 01:52:32', '2026-04-10 01:52:32'),
(1038, 13, 'Part-time', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 1, '2026-04-10 01:52:32', '2026-04-10 01:52:32'),
(1039, 13, 'Water', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 2, '2026-04-10 01:52:32', '2026-04-10 01:52:32'),
(1040, 13, 'COS', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 3, '2026-04-10 01:52:32', '2026-04-10 01:52:32'),
(1041, 13, 'Security', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 4, '2026-04-10 01:52:32', '2026-04-10 01:52:32'),
(1042, 13, 'Electricity', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 5, '2026-04-10 01:52:32', '2026-04-10 01:52:32'),
(1043, 13, 'Honoraria - Overload', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 0, '2026-04-10 02:21:41', '2026-04-10 02:21:41'),
(1044, 13, 'Honoraria - Part-time', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 1, '2026-04-10 02:21:41', '2026-04-10 02:21:41'),
(1045, 13, 'Water Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 2, '2026-04-10 02:21:41', '2026-04-10 02:21:41'),
(1046, 13, 'Security Services', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 4, '2026-04-10 02:21:42', '2026-04-10 02:21:42'),
(1047, 13, 'Electricity Expenses', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 5, '2026-04-10 02:21:42', '2026-04-10 02:21:42'),
(1048, 13, 'Labor and Wages', 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0, 3, '2026-04-10 02:27:53', '2026-04-10 02:27:53');

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
  `notes` text DEFAULT NULL,
  `ppmp_item_id` int(11) DEFAULT NULL COMMENT 'Reference to ppmp_items.id',
  `ppmp_id` int(11) DEFAULT NULL COMMENT 'Reference to ppmp.id',
  `ppmp_description` text DEFAULT NULL COMMENT 'Formatted PPMP item description'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `purchase_requests`
--

INSERT INTO `purchase_requests` (`id`, `pr_number`, `procurement_user_id`, `department_id`, `status`, `fiscal_year`, `submitted_at`, `processed_at`, `delivered_at`, `received_at`, `completed_at`, `notes`, `ppmp_item_id`, `ppmp_id`, `ppmp_description`) VALUES
(5, 'PR-2026-0001', 34, 18, 'complete', '2026', '2026-02-25 07:42:50', '2026-02-25 07:42:50', '2026-02-25 07:44:20', '2026-02-25 07:45:10', '2026-02-25 07:45:10', '', NULL, NULL, NULL);

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

--
-- Dumping data for table `purchase_request_files`
--

INSERT INTO `purchase_request_files` (`id`, `purchase_request_id`, `file_name`, `file_path`, `file_size`, `file_type`, `uploaded_at`) VALUES
(12, 5, 'acceptance form2.pdf', 'uploads/pr/PR_5_1772005370_0_02119061.pdf', 268309, 'application/pdf', '2026-02-25 07:42:50');

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
(5, 'budget@evsu.edu.ph', '$2y$10$eRpG2g0Gs5IfknmV/c4fDeOn0bAEE8QS0kc26TmRWWtaAZ3zL7Kem', 'Super', 'Admin', NULL, 'BUDGET001', NULL, 1, 1, '2026-04-15 04:11:57', NULL, '2025-09-21 07:35:15', '2026-04-15 04:11:57', 'uploads/profile_photos/profile_5_1764328234.jpg', 0),
(25, 'lovely.funa@evsu.edu.ph', '$2y$10$bDYsvZJOWW9mpWQlEPycNerGrw0P1HEboux0SnAg8BN/sUPJmc/cC', 'Lovely', 'Aseo', 'Funa', 'F090121LR', 26, 1, 1, NULL, 5, '2025-11-27 07:35:57', '2026-02-25 07:20:04', NULL, 0),
(32, 'budget@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Budget', 'Office', '', NULL, 26, 3, 1, '2026-04-14 08:12:24', NULL, '2025-11-29 05:42:58', '2026-04-14 08:12:24', NULL, 0),
(34, 'bac@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Procurement', 'Office', '', NULL, 16, 5, 1, '2026-03-09 01:39:35', 32, '2025-11-29 05:42:58', '2026-03-09 01:39:35', NULL, 0),
(36, 'supply@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Supply', 'Office', '', NULL, 25, 8, 1, '2026-03-09 01:38:52', 32, '2025-11-29 05:42:58', '2026-03-09 01:38:52', NULL, 0),
(38, 'dept1@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'One', '', NULL, 13, 3, 1, '2026-04-16 06:43:59', 32, '2025-11-29 05:42:58', '2026-04-16 06:43:59', NULL, 0),
(39, 'dept2@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'Two', '', NULL, 14, 3, 1, '2026-03-09 02:52:59', 32, '2025-11-29 05:42:58', '2026-03-09 02:52:59', NULL, 0),
(40, 'dept3@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'Three', '', NULL, 20, 3, 1, NULL, 32, '2025-11-29 05:42:58', '2025-11-29 05:45:12', NULL, 0),
(47, 'jmhumbid215@gmail.com', '$2y$10$nEZZdwxQhqq0BMI7/3aAneomAFlg0nQCwf/TEC57myOBVK7oqkdAe', 'Admin', 'One', '', NULL, 23, 3, 1, '2026-03-17 07:03:05', 5, '2026-02-26 15:01:02', '2026-03-17 07:03:05', NULL, 0);

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
(121, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-01-14 02:21:08\",\"action\":\"user_login\"}', '2026-01-14 01:21:08'),
(122, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-10 05:45:10\",\"action\":\"user_login\"}', '2026-02-10 04:45:10'),
(123, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-10 05:53:09\",\"action\":\"user_login\"}', '2026-02-10 04:53:09'),
(124, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-10 05:57:09\",\"action\":\"user_logout\"}', '2026-02-10 04:57:09'),
(125, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-10 05:57:46\",\"action\":\"user_login\"}', '2026-02-10 04:57:46'),
(126, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:01:21\",\"action\":\"user_login\"}', '2026-02-16 00:01:21'),
(127, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:01:41\",\"action\":\"user_login\"}', '2026-02-16 00:01:41'),
(128, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:28:26\",\"action\":\"user_logout\"}', '2026-02-16 00:28:26'),
(129, 34, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:28:41\",\"action\":\"user_login\"}', '2026-02-16 00:28:41'),
(130, 34, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:29:22\",\"action\":\"user_logout\"}', '2026-02-16 00:29:22'),
(131, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:30:01\",\"action\":\"user_login\"}', '2026-02-16 00:30:01'),
(132, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:30:36\",\"action\":\"user_login\"}', '2026-02-16 00:30:36'),
(133, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:31:25\",\"action\":\"user_logout\"}', '2026-02-16 00:31:25'),
(134, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '{\"timestamp\":\"2026-02-16 01:31:42\",\"action\":\"user_login\"}', '2026-02-16 00:31:42'),
(135, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-16 04:07:56\",\"action\":\"user_login\"}', '2026-02-16 03:07:56'),
(136, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-16 14:56:48\",\"action\":\"user_login\"}', '2026-02-16 13:56:48'),
(137, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-17 01:46:29\",\"action\":\"user_login\"}', '2026-02-17 00:46:29'),
(138, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-17 01:50:26\",\"action\":\"user_login\"}', '2026-02-17 00:50:26'),
(139, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-19 02:50:05\",\"action\":\"user_login\"}', '2026-02-19 01:50:05'),
(140, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-19 03:05:32\",\"action\":\"user_login\"}', '2026-02-19 02:05:32'),
(141, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-21 02:34:33\",\"action\":\"user_login\"}', '2026-02-21 01:34:33'),
(142, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-21 02:35:09\",\"action\":\"user_login\"}', '2026-02-21 01:35:09'),
(143, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-23 01:00:14\",\"action\":\"user_login\"}', '2026-02-23 00:00:14'),
(144, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-23 06:30:27\",\"action\":\"user_login\"}', '2026-02-23 05:30:27'),
(145, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-23 18:13:31\",\"action\":\"user_login\"}', '2026-02-23 17:13:31'),
(146, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-23 18:27:30\",\"action\":\"user_login\"}', '2026-02-23 17:27:30'),
(147, 38, '', NULL, NULL, '{\"submission_id\":32,\"submission_type\":\"SUPPLEMENTAL\",\"file_name\":\"Utilization_Computer_Studies_2026.pdf\",\"action\":\"uploaded\",\"year\":2026}', '2026-02-23 19:10:12'),
(148, 38, '', NULL, NULL, '{\"submission_id\":32,\"submission_type\":\"SUPPLEMENTAL\",\"file_name\":\"Utilization_Computer_Studies_2026.pdf\",\"action\":\"removed\",\"year\":2026}', '2026-02-23 19:10:40'),
(149, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-23 22:44:34\",\"action\":\"user_login\"}', '2026-02-23 21:44:34'),
(153, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-24 01:44:46\",\"action\":\"user_logout\"}', '2026-02-24 00:44:46'),
(154, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-24 01:44:55\",\"action\":\"user_login\"}', '2026-02-24 00:44:55'),
(158, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-24 01:55:10\",\"action\":\"user_logout\"}', '2026-02-24 00:55:10'),
(160, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-24 01:55:21\",\"action\":\"user_login\"}', '2026-02-24 00:55:21'),
(169, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-24 03:43:01\",\"action\":\"user_login\"}', '2026-02-24 02:43:01'),
(170, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-24 03:49:21\",\"action\":\"user_logout\"}', '2026-02-24 02:49:21'),
(171, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-24 03:49:29\",\"action\":\"user_login\"}', '2026-02-24 02:49:29'),
(172, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-24 03:49:59\",\"action\":\"user_login\"}', '2026-02-24 02:49:59'),
(173, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-24 04:26:57\",\"action\":\"user_login\"}', '2026-02-24 03:26:57'),
(174, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-24 04:50:52\",\"action\":\"user_login\"}', '2026-02-24 03:50:52'),
(175, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-24 07:45:49\",\"action\":\"user_login\"}', '2026-02-24 06:45:49'),
(176, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '{\"timestamp\":\"2026-02-25 01:37:19\",\"action\":\"user_login\"}', '2026-02-25 00:37:19'),
(177, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '{\"timestamp\":\"2026-02-25 01:43:47\",\"action\":\"user_logout\"}', '2026-02-25 00:43:47'),
(178, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '{\"timestamp\":\"2026-02-25 01:45:36\",\"action\":\"user_login\"}', '2026-02-25 00:45:36'),
(179, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '{\"timestamp\":\"2026-02-25 02:08:12\",\"action\":\"user_logout\"}', '2026-02-25 01:08:12'),
(180, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 OPR/127.0.0.0', '{\"timestamp\":\"2026-02-25 02:12:26\",\"action\":\"user_login\"}', '2026-02-25 01:12:26'),
(181, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 02:16:30\",\"action\":\"user_login\"}', '2026-02-25 01:16:30'),
(182, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 02:17:50\",\"action\":\"user_logout\"}', '2026-02-25 01:17:50'),
(183, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 02:17:57\",\"action\":\"user_login\"}', '2026-02-25 01:17:57'),
(184, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 02:18:08\",\"action\":\"user_logout\"}', '2026-02-25 01:18:08'),
(185, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 02:18:22\",\"action\":\"user_login\"}', '2026-02-25 01:18:22'),
(186, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 02:18:56\",\"action\":\"user_login\"}', '2026-02-25 01:18:56'),
(187, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 02:26:58\",\"action\":\"user_login\"}', '2026-02-25 01:26:58'),
(188, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 04:03:12\",\"action\":\"user_logout\"}', '2026-02-25 03:03:12'),
(189, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 04:03:20\",\"action\":\"user_login\"}', '2026-02-25 03:03:20'),
(190, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 06:51:27\",\"action\":\"user_login\"}', '2026-02-25 05:51:27'),
(191, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 07:11:23\",\"action\":\"user_logout\"}', '2026-02-25 06:11:23'),
(192, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 07:11:41\",\"action\":\"user_login\"}', '2026-02-25 06:11:41'),
(193, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-25 08:00:57\",\"action\":\"user_login\"}', '2026-02-25 07:00:57'),
(194, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-25 08:04:35\",\"action\":\"user_logout\"}', '2026-02-25 07:04:35'),
(195, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-25 08:04:50\",\"action\":\"user_login\"}', '2026-02-25 07:04:50'),
(196, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-25 08:06:23\",\"action\":\"user_logout\"}', '2026-02-25 07:06:23'),
(197, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-25 08:06:29\",\"action\":\"user_login\"}', '2026-02-25 07:06:29'),
(198, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-25 08:16:26\",\"action\":\"user_login\"}', '2026-02-25 07:16:26'),
(199, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:35:50\",\"action\":\"user_login\"}', '2026-02-25 07:35:50'),
(200, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:40:35\",\"action\":\"user_logout\"}', '2026-02-25 07:40:35'),
(201, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:40:48\",\"action\":\"user_login\"}', '2026-02-25 07:40:48'),
(202, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:41:05\",\"action\":\"user_logout\"}', '2026-02-25 07:41:05'),
(203, 34, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:41:28\",\"action\":\"user_login\"}', '2026-02-25 07:41:28'),
(204, 34, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:43:12\",\"action\":\"user_logout\"}', '2026-02-25 07:43:12'),
(205, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:43:19\",\"action\":\"user_login\"}', '2026-02-25 07:43:19'),
(206, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:43:47\",\"action\":\"user_logout\"}', '2026-02-25 07:43:47'),
(207, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:44:09\",\"action\":\"user_login\"}', '2026-02-25 07:44:09'),
(208, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:44:36\",\"action\":\"user_logout\"}', '2026-02-25 07:44:36'),
(209, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:44:53\",\"action\":\"user_login\"}', '2026-02-25 07:44:53'),
(210, 39, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:45:58\",\"action\":\"user_logout\"}', '2026-02-25 07:45:58'),
(211, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:46:18\",\"action\":\"user_login\"}', '2026-02-25 07:46:18'),
(212, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:48:53\",\"action\":\"user_logout\"}', '2026-02-25 07:48:53'),
(213, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-25 08:49:00\",\"action\":\"user_login\"}', '2026-02-25 07:49:00'),
(214, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-25 09:08:34\",\"action\":\"user_login\"}', '2026-02-25 08:08:34'),
(215, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 02:49:52\",\"action\":\"user_login\"}', '2026-02-26 01:49:52'),
(216, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 02:50:23\",\"action\":\"user_login\"}', '2026-02-26 01:50:23'),
(217, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 03:33:58\",\"action\":\"user_logout\"}', '2026-02-26 02:33:58'),
(218, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 03:48:26\",\"action\":\"user_login\"}', '2026-02-26 02:48:26'),
(219, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 03:48:47\",\"action\":\"user_login\"}', '2026-02-26 02:48:47'),
(221, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-26 16:00:29\",\"action\":\"user_login\"}', '2026-02-26 15:00:29'),
(222, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-26 16:02:59\",\"action\":\"user_logout\"}', '2026-02-26 15:02:59'),
(223, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-26 16:03:04\",\"action\":\"user_login\"}', '2026-02-26 15:03:04'),
(224, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 16:03:59\",\"action\":\"user_login\"}', '2026-02-26 15:03:59'),
(225, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-26 16:11:17\",\"action\":\"user_login\"}', '2026-02-26 15:11:17'),
(226, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 18:31:03\",\"action\":\"user_login\"}', '2026-02-26 17:31:03'),
(227, 47, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 18:39:28\",\"action\":\"user_logout\"}', '2026-02-26 17:39:28'),
(230, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 18:39:56\",\"action\":\"user_login\"}', '2026-02-26 17:39:56'),
(231, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 18:40:13\",\"action\":\"user_logout\"}', '2026-02-26 17:40:13');
INSERT INTO `user_activity_log` (`id`, `user_id`, `activity_type`, `ip_address`, `user_agent`, `activity_details`, `created_at`) VALUES
(232, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 18:40:54\",\"action\":\"user_login\"}', '2026-02-26 17:40:54'),
(233, 47, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 18:51:26\",\"action\":\"user_logout\"}', '2026-02-26 17:51:26'),
(234, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-26 18:51:41\",\"action\":\"user_login\"}', '2026-02-26 17:51:41'),
(235, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-02-26 18:53:10\",\"action\":\"user_login\"}', '2026-02-26 17:53:10'),
(236, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 01:59:59\",\"action\":\"user_login\"}', '2026-02-27 00:59:59'),
(237, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 02:00:46\",\"action\":\"user_login\"}', '2026-02-27 01:00:46'),
(238, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 02:27:15\",\"action\":\"user_logout\"}', '2026-02-27 01:27:15'),
(239, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 02:28:15\",\"action\":\"user_login\"}', '2026-02-27 01:28:15'),
(240, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 03:20:20\",\"action\":\"user_logout\"}', '2026-02-27 02:20:20'),
(241, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 03:20:27\",\"action\":\"user_login\"}', '2026-02-27 02:20:27'),
(242, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 03:46:00\",\"action\":\"user_logout\"}', '2026-02-27 02:46:00'),
(243, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 03:46:10\",\"action\":\"user_login\"}', '2026-02-27 02:46:10'),
(244, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-02-27 04:39:54\",\"action\":\"user_login\"}', '2026-02-27 03:39:54'),
(245, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-02 01:42:34\",\"action\":\"user_login\"}', '2026-03-02 00:42:34'),
(246, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-02 02:54:14\",\"action\":\"user_login\"}', '2026-03-02 01:54:14'),
(247, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-02 02:54:40\",\"action\":\"user_login\"}', '2026-03-02 01:54:40'),
(248, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-02 02:58:38\",\"action\":\"user_login\"}', '2026-03-02 01:58:38'),
(249, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-02 03:41:08\",\"action\":\"user_login\"}', '2026-03-02 02:41:08'),
(250, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-02 03:48:23\",\"action\":\"user_logout\"}', '2026-03-02 02:48:23'),
(251, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-02 03:48:39\",\"action\":\"user_login\"}', '2026-03-02 02:48:39'),
(252, 47, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-02 03:49:07\",\"action\":\"user_logout\"}', '2026-03-02 02:49:07'),
(253, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-02 03:49:43\",\"action\":\"user_login\"}', '2026-03-02 02:49:43'),
(254, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-02 09:50:59\",\"action\":\"user_logout\"}', '2026-03-02 08:50:59'),
(255, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-02 09:51:16\",\"action\":\"user_login\"}', '2026-03-02 08:51:16'),
(256, 47, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-02 09:51:37\",\"action\":\"user_logout\"}', '2026-03-02 08:51:37'),
(257, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-02 09:51:39\",\"action\":\"user_login\"}', '2026-03-02 08:51:39'),
(258, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-02 09:52:13\",\"action\":\"user_login\"}', '2026-03-02 08:52:13'),
(259, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-03 01:53:59\",\"action\":\"user_login\"}', '2026-03-03 00:53:59'),
(260, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-03 06:03:32\",\"action\":\"user_login\"}', '2026-03-03 05:03:32'),
(261, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-03 06:20:38\",\"action\":\"user_login\"}', '2026-03-03 05:20:38'),
(262, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-03 08:35:08\",\"action\":\"user_login\"}', '2026-03-03 07:35:08'),
(263, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-03 08:35:30\",\"action\":\"user_login\"}', '2026-03-03 07:35:30'),
(264, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-03 09:17:11\",\"action\":\"user_logout\"}', '2026-03-03 08:17:11'),
(265, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-03 09:18:12\",\"action\":\"user_login\"}', '2026-03-03 08:18:12'),
(266, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-04 08:57:12\",\"action\":\"user_login\"}', '2026-03-04 07:57:12'),
(267, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-05 02:28:33\",\"action\":\"user_login\"}', '2026-03-05 01:28:33'),
(268, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-05 02:35:25\",\"action\":\"user_login\"}', '2026-03-05 01:35:25'),
(269, 39, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-05 02:35:36\",\"action\":\"user_logout\"}', '2026-03-05 01:35:36'),
(270, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-05 02:35:44\",\"action\":\"user_login\"}', '2026-03-05 01:35:44'),
(271, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-06 01:37:21\",\"action\":\"user_login\"}', '2026-03-06 00:37:21'),
(272, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-07 02:16:22\",\"action\":\"user_login\"}', '2026-03-07 01:16:22'),
(273, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-07 02:17:17\",\"action\":\"user_login\"}', '2026-03-07 01:17:17'),
(274, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-07 02:18:46\",\"action\":\"user_login\"}', '2026-03-07 01:18:46'),
(275, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-07 02:59:11\",\"action\":\"user_logout\"}', '2026-03-07 01:59:11'),
(276, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-07 02:59:14\",\"action\":\"user_login\"}', '2026-03-07 01:59:14'),
(277, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-07 03:30:23\",\"action\":\"user_logout\"}', '2026-03-07 02:30:23'),
(278, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-07 03:31:38\",\"action\":\"user_login\"}', '2026-03-07 02:31:38'),
(279, 47, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-07 09:08:52\",\"action\":\"user_logout\"}', '2026-03-07 08:08:52'),
(280, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-07 09:09:00\",\"action\":\"user_login\"}', '2026-03-07 08:09:00'),
(281, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-09 01:51:30\",\"action\":\"user_login\"}', '2026-03-09 00:51:30'),
(282, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 01:51:37\",\"action\":\"user_login\"}', '2026-03-09 00:51:37'),
(283, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 02:38:06\",\"action\":\"user_logout\"}', '2026-03-09 01:38:06'),
(284, 36, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 02:38:52\",\"action\":\"user_login\"}', '2026-03-09 01:38:52'),
(285, 36, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 02:39:14\",\"action\":\"user_logout\"}', '2026-03-09 01:39:14'),
(286, 34, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 02:39:35\",\"action\":\"user_login\"}', '2026-03-09 01:39:35'),
(287, 34, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 02:52:53\",\"action\":\"user_logout\"}', '2026-03-09 01:52:53'),
(288, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 02:53:11\",\"action\":\"user_login\"}', '2026-03-09 01:53:11'),
(289, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 03:52:52\",\"action\":\"user_logout\"}', '2026-03-09 02:52:52'),
(290, 39, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 03:52:59\",\"action\":\"user_login\"}', '2026-03-09 02:52:59'),
(291, 39, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 03:53:12\",\"action\":\"user_logout\"}', '2026-03-09 02:53:12'),
(292, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 03:53:19\",\"action\":\"user_login\"}', '2026-03-09 02:53:19'),
(293, 47, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 03:53:39\",\"action\":\"user_logout\"}', '2026-03-09 02:53:39'),
(294, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-09 03:53:45\",\"action\":\"user_login\"}', '2026-03-09 02:53:45'),
(295, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-09 08:15:09\",\"action\":\"user_login\"}', '2026-03-09 07:15:09'),
(296, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-10 01:26:05\",\"action\":\"user_login\"}', '2026-03-10 00:26:05'),
(297, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-10 01:31:59\",\"action\":\"user_login\"}', '2026-03-10 00:31:59'),
(298, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-10 02:33:20\",\"action\":\"user_login\"}', '2026-03-10 01:33:20'),
(299, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-10 02:48:19\",\"action\":\"user_login\"}', '2026-03-10 01:48:19'),
(300, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-10 08:13:36\",\"action\":\"user_logout\"}', '2026-03-10 07:13:36'),
(301, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-10 08:13:45\",\"action\":\"user_login\"}', '2026-03-10 07:13:45'),
(302, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-11 05:13:22\",\"action\":\"user_login\"}', '2026-03-11 04:13:22'),
(303, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-16 01:30:44\",\"action\":\"user_login\"}', '2026-03-16 00:30:44'),
(304, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-16 01:31:03\",\"action\":\"user_login\"}', '2026-03-16 00:31:03'),
(305, 38, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-16 01:45:24\",\"action\":\"user_logout\"}', '2026-03-16 00:45:24'),
(306, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36 Edg/145.0.0.0', '{\"timestamp\":\"2026-03-16 01:45:38\",\"action\":\"user_login\"}', '2026-03-16 00:45:38'),
(307, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-16 03:29:32\",\"action\":\"user_logout\"}', '2026-03-16 02:29:32'),
(308, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-16 03:29:41\",\"action\":\"user_login\"}', '2026-03-16 02:29:41'),
(309, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-16 04:25:31\",\"action\":\"user_login\"}', '2026-03-16 03:25:31'),
(310, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-16 06:41:00\",\"action\":\"user_login\"}', '2026-03-16 05:41:01'),
(311, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-16 06:55:46\",\"action\":\"user_login\"}', '2026-03-16 05:55:46'),
(312, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-16 07:56:37\",\"action\":\"user_login\"}', '2026-03-16 06:56:37'),
(313, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-17 02:58:35\",\"action\":\"user_login\"}', '2026-03-17 01:58:35'),
(314, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-17 03:05:01\",\"action\":\"user_login\"}', '2026-03-17 02:05:01'),
(315, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-17 03:05:44\",\"action\":\"user_logout\"}', '2026-03-17 02:05:44'),
(316, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-17 03:05:56\",\"action\":\"user_login\"}', '2026-03-17 02:05:56'),
(317, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-17 07:24:41\",\"action\":\"user_login\"}', '2026-03-17 06:24:41'),
(318, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-17 08:02:39\",\"action\":\"user_logout\"}', '2026-03-17 07:02:39'),
(319, 47, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-17 08:03:05\",\"action\":\"user_login\"}', '2026-03-17 07:03:05'),
(320, 47, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-17 08:28:36\",\"action\":\"user_logout\"}', '2026-03-17 07:28:36'),
(321, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-17 08:35:47\",\"action\":\"user_login\"}', '2026-03-17 07:35:47'),
(322, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-18 06:22:14\",\"action\":\"user_login\"}', '2026-03-18 05:22:14'),
(323, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-20 02:13:35\",\"action\":\"user_login\"}', '2026-03-20 01:13:35'),
(324, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-20 02:19:59\",\"action\":\"user_login\"}', '2026-03-20 01:19:59'),
(325, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-20 06:49:07\",\"action\":\"user_login\"}', '2026-03-20 05:49:07'),
(326, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36 Edg/146.0.0.0', '{\"timestamp\":\"2026-03-23 05:57:02\",\"action\":\"user_login\"}', '2026-03-23 04:57:03'),
(327, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-23 06:15:08\",\"action\":\"user_login\"}', '2026-03-23 05:15:08'),
(328, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-03-25 07:18:24\",\"action\":\"user_login\"}', '2026-03-25 06:18:24'),
(329, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-07 03:49:31\",\"action\":\"user_login\"}', '2026-04-07 01:49:31'),
(330, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-07 03:57:20\",\"action\":\"user_login\"}', '2026-04-07 01:57:20'),
(331, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-07 04:39:23\",\"action\":\"user_login\"}', '2026-04-07 02:39:23'),
(332, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-07 06:12:55\",\"action\":\"user_login\"}', '2026-04-07 04:12:55'),
(333, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-07 08:26:41\",\"action\":\"user_login\"}', '2026-04-07 06:26:41'),
(334, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-07 09:05:57\",\"action\":\"user_logout\"}', '2026-04-07 07:05:57'),
(335, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-07 09:06:07\",\"action\":\"user_login\"}', '2026-04-07 07:06:07'),
(336, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-08 04:56:07\",\"action\":\"user_login\"}', '2026-04-08 02:56:07'),
(337, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-08 04:56:23\",\"action\":\"user_login\"}', '2026-04-08 02:56:23'),
(338, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-08 05:02:40\",\"action\":\"user_login\"}', '2026-04-08 03:02:40'),
(339, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-08 05:57:23\",\"action\":\"user_login\"}', '2026-04-08 03:57:23'),
(340, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-08 10:25:52\",\"action\":\"user_login\"}', '2026-04-08 08:25:52'),
(341, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-10 03:11:16\",\"action\":\"user_login\"}', '2026-04-10 01:11:16'),
(342, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-10 03:45:10\",\"action\":\"user_login\"}', '2026-04-10 01:45:10'),
(343, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-10 07:12:54\",\"action\":\"user_login\"}', '2026-04-10 05:12:54'),
(344, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-10 07:13:16\",\"action\":\"user_login\"}', '2026-04-10 05:13:16'),
(345, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-10 08:27:49\",\"action\":\"user_login\"}', '2026-04-10 06:27:49'),
(346, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-11 03:31:25\",\"action\":\"user_login\"}', '2026-04-11 01:31:25'),
(347, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-11 05:18:01\",\"action\":\"user_login\"}', '2026-04-11 03:18:01'),
(348, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-11 15:26:24\",\"action\":\"user_login\"}', '2026-04-11 13:26:24'),
(349, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-12 15:36:39\",\"action\":\"user_login\"}', '2026-04-12 13:36:39'),
(350, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-13 02:17:36\",\"action\":\"user_login\"}', '2026-04-13 00:17:36'),
(351, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-13 04:05:39\",\"action\":\"user_login\"}', '2026-04-13 02:05:39'),
(352, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-13 04:55:58\",\"action\":\"user_login\"}', '2026-04-13 02:55:58'),
(353, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-14 03:22:30\",\"action\":\"user_login\"}', '2026-04-14 01:22:30'),
(354, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-14 04:54:15\",\"action\":\"user_login\"}', '2026-04-14 02:54:15'),
(355, 5, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-14 10:12:17\",\"action\":\"user_logout\"}', '2026-04-14 08:12:17'),
(356, 32, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-14 10:12:24\",\"action\":\"user_login\"}', '2026-04-14 08:12:24'),
(357, 32, 'logout', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-14 10:12:44\",\"action\":\"user_logout\"}', '2026-04-14 08:12:44'),
(358, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-14 10:12:48\",\"action\":\"user_login\"}', '2026-04-14 08:12:48'),
(359, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-15 02:21:59\",\"action\":\"user_login\"}', '2026-04-15 00:21:59'),
(360, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-15 05:46:37\",\"action\":\"user_login\"}', '2026-04-15 03:46:37'),
(361, 5, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-15 06:11:57\",\"action\":\"user_login\"}', '2026-04-15 04:11:57'),
(362, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-16 04:21:32\",\"action\":\"user_login\"}', '2026-04-16 02:21:32'),
(363, 38, 'login', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/147.0.0.0 Safari/537.36', '{\"timestamp\":\"2026-04-16 08:43:59\",\"action\":\"user_login\"}', '2026-04-16 06:43:59');

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
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `ppmp_item_id` int(11) DEFAULT NULL COMMENT 'Reference to ppmp_items.id',
  `ppmp_id` int(11) DEFAULT NULL COMMENT 'Reference to ppmp.id',
  `ppmp_description` text DEFAULT NULL COMMENT 'Formatted PPMP item description'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilization_purchase_requests`
--

INSERT INTO `utilization_purchase_requests` (`id`, `department_id`, `purchase_request`, `particulars`, `pr_number`, `po_number`, `date`, `amount`, `entry_id`, `fiscal_year`, `created_by`, `created_at`, `updated_at`, `ppmp_item_id`, `ppmp_id`, `ppmp_description`) VALUES
(973, 26, 'PR1', 'ENTRY 1', 'TEST 2026', '', '2026-02-17', 25000.00, NULL, '2026', 5, '2026-02-16 00:41:34', '2026-02-16 00:41:54', NULL, NULL, NULL),
(1011, 13, 'Meals & Accommodation', 'Budget Planning & Preparation for 2026, Faculty and Staff Dev\'t. & Curriculum Review on March 21-22, 2025', 'P.R. No.:2025-02-024P.O. No.:2025-03-006', '', '2025-03-01', 54864.00, NULL, '2025', 5, '2026-02-25 05:38:12', '2026-02-25 05:38:30', NULL, NULL, NULL),
(1012, 13, 'Meals', 'Harampang and Campus Visitation', 'P.R. No.:2025-03-064P.O. No.:2025-03-004', '', '2025-03-01', 17500.00, NULL, '2025', 5, '2026-02-25 05:41:47', '2026-02-25 05:42:09', NULL, NULL, NULL),
(1013, 13, 'Meals', 'For Training/Workshop Vision 2025: Crafting Strategic Planning Curriculum Review for BSIT, BSIS, BMMA Program Blueprint on March 27, 2025', 'P.R. No.:2025-03-051P.O. No.:2025-03-007', '', '2025-03-01', 33000.00, NULL, '2025', 5, '2026-02-25 05:42:19', '2026-02-25 05:42:38', NULL, NULL, NULL),
(1014, 13, 'Meals & Accommodation', 'Syllabi Revision', 'P.R. No.:2025-03-052P.O. No.:2025-03-008', '', '2025-03-01', 55965.00, NULL, '2025', 5, '2026-02-25 05:43:02', '2026-02-25 05:43:27', NULL, NULL, NULL),
(1015, 13, 'Office Supplies', '30pcs file folder A4, 30pcs file folder long, 2 boxes paper fastener metal', 'P.R. No.:2025-04-042P.O. No.:2025-03-019', '', '2025-04-01', 610.00, NULL, '2025', 5, '2026-02-25 05:44:22', '2026-02-25 05:45:03', NULL, NULL, NULL),
(1016, 13, 'Office Supplies', 'Various Office Supplies', 'P.R. No.:2025-04-042P.O. No.:2025-03-018', '', '2025-04-01', 19426.00, NULL, '2025', 5, '2026-02-25 05:45:18', '2026-02-25 05:45:38', NULL, NULL, NULL),
(1017, 13, 'Meals & Snacks', 'Meals, lunch, am snacks and pm snacks for stakeholders meeting on proposed curriculum for BSIT and drafting BSIS curriculum', 'P.R. No.:2025-04-042P.O. No.:2025-03-018', '', '2025-04-01', 65000.00, NULL, '2025', 5, '2026-02-25 05:46:16', '2026-02-25 05:46:51', NULL, NULL, NULL),
(1018, 13, 'Meals', 'Dinner meals for evsunistas awards and fellowship night on August 29, 2025 at Leyte Academic Center, Palo Leyte', 'P.R. No.:2025-07-190P.O. No.:2025-08-140', '', '2025-07-01', 4812.50, NULL, '2025', 5, '2026-02-25 05:47:20', '2026-02-25 05:47:50', NULL, NULL, NULL),
(1019, 13, 'Charter Day Polo Shirt', 'Purchase of Charter Day Polo Shirt 2025 for the Charter Day Celebration', 'P.R. No.:2025-04-090P.O. No.:2025-07-114', '', '2025-04-01', 6550.00, NULL, '2025', 5, '2026-02-25 05:48:04', '2026-02-25 05:48:26', NULL, NULL, NULL),
(1020, 13, 'Air Condition', '2HP Airconditioner with free installation', 'P.R. No.:2025-05-130P.O. No.:2025-08-137', '', '2025-05-01', 80000.00, NULL, '2025', 5, '2026-02-25 05:48:38', '2026-02-25 05:49:04', NULL, NULL, NULL),
(1021, 26, 'test', 'test', 'test', '', '2025-01-01', 10000.00, NULL, '2025', 5, '2026-02-25 06:10:00', '2026-02-25 06:10:14', NULL, NULL, NULL),
(1022, 18, 'test', 'test', 'test', '', '2025-03-01', 10000.00, NULL, '2025', 5, '2026-02-25 06:12:05', '2026-02-25 06:12:18', NULL, NULL, NULL),
(1023, 13, 'test', 'test', 'test', '', '2026-02-25', 1000.00, NULL, '2025', 5, '2026-02-25 07:07:52', '2026-02-25 07:07:59', NULL, NULL, NULL),
(1024, 14, 'PR1', 'test', 'test', '', '2026-02-27', 20000.00, NULL, '2026', 5, '2026-02-27 01:40:46', '2026-03-05 01:40:54', NULL, NULL, NULL),
(1032, 29, 'wadasd', 'asdawds', '34rqwafa', '', '2026-02-27', 1000.00, NULL, '2025', 5, '2026-02-27 03:50:52', '2026-02-27 03:50:57', NULL, NULL, NULL),
(1033, 29, 'tsdsa', 'fsdfsdfsdf', 'wrffesfesgfs', '', '2026-02-27', 3000.00, NULL, '2026', 5, '2026-02-27 03:57:31', '2026-02-27 03:57:37', NULL, NULL, NULL),
(1034, 23, 'Jmhumbid', 'wdasdad', 'sdfdsgdsgfs', '', '2026-02-27', 3000.00, NULL, '2025', 5, '2026-02-27 04:41:00', '2026-02-27 04:41:05', NULL, NULL, NULL),
(1431, 13, 'ITEM #1, Type: Goods, Qty: 2.00, Unit: pcs, Amount: ₱100.00', '', '', '', '2026-04-14', 100.00, NULL, '2027', 5, '2026-04-14 03:31:57', '2026-04-14 03:32:00', 656, 60, 'ITEM #1, Type: Goods, Qty: 2.00, Unit: pcs, Amount: ₱100.00'),
(1432, 13, 'ITEM #2, Type: Goods, Qty: 1.00, Unit: rolls, Amount: ₱300.00', NULL, NULL, NULL, '2026-04-14', 300.00, NULL, '2027', 5, '2026-04-14 03:31:57', NULL, 657, 60, 'ITEM #2, Type: Goods, Qty: 1.00, Unit: rolls, Amount: ₱300.00'),
(1433, 13, 'ITEM 1, Type: Goods, Qty: 2.00, Unit: pcs, Amount: ₱5,000.00', 'test', 'testt', '', '2026-04-15', 5000.00, NULL, '2026', 5, '2026-04-15 07:27:24', '2026-04-15 07:27:31', 670, 68, 'ITEM 1, Type: Goods, Qty: 2.00, Unit: pcs, Amount: ₱5,000.00');

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

--
-- Dumping data for table `utilization_summaries`
--

INSERT INTO `utilization_summaries` (`id`, `department_id`, `fiscal_year`, `department_name`, `utilization_entries`, `pr_entries`, `travels_entries`, `honoraria_entries`, `pr_deductions`, `travels_deductions`, `honoraria_deductions`, `totals`, `created_by`, `created_at`, `updated_at`) VALUES
(19, 18, '2025', 'Guidance Office', '[{\"category\":\"test23123\",\"allocated\":50000,\"deduction\":10000,\"balance\":40000}]', '[{\"id\":1022,\"purchaseRequest\":\"test\",\"particulars\":\"test\",\"prNumber\":\"test\",\"date\":\"2025-03-01\",\"amount\":10000,\"deductedFrom\":\"\"}]', '[]', '[]', '[{\"category\":\"test23123\",\"amount\":10000}]', '[]', '[]', '{\"totalAllocated\":50000,\"totalDeductions\":10000,\"totalBalance\":40000,\"prTotal\":10000,\"travelsTotal\":0,\"honorariaTotal\":0}', 5, '2026-02-25 06:12:45', NULL),
(20, 26, '2025', 'Budget Office', '[{\"category\":\"test2\",\"allocated\":20000,\"deduction\":0,\"balance\":20000}]', '[{\"id\":1021,\"purchaseRequest\":\"test\",\"particulars\":\"test\",\"prNumber\":\"test\",\"date\":\"2025-01-01\",\"amount\":10000,\"deductedFrom\":\"\"}]', '[]', '[]', '[]', '[]', '[]', '{\"totalAllocated\":20000,\"totalDeductions\":0,\"totalBalance\":20000,\"prTotal\":10000,\"travelsTotal\":0,\"honorariaTotal\":0}', 5, '2026-02-25 06:14:07', '2026-02-25 08:42:39'),
(21, 33, '2025', 'ICT', '[{\"category\":\"Internet Subscription\",\"allocated\":584000,\"deduction\":217270.85,\"balance\":366729.15},{\"category\":\"ICT Equipment\",\"allocated\":87000,\"deduction\":0,\"balance\":87000}]', '[]', '[]', '[]', '[]', '[]', '[]', '{\"totalAllocated\":671000,\"totalDeductions\":217270.85,\"totalBalance\":453729.15,\"prTotal\":0,\"travelsTotal\":0,\"honorariaTotal\":0}', 5, '2026-02-25 08:09:47', '2026-02-25 08:19:30'),
(25, 23, '2025', 'Admin', '[{\"category\":\"TEST ENTRY 1\",\"allocated\":25000,\"deduction\":3300,\"balance\":21700}]', '[{\"id\":1034,\"purchaseRequest\":\"Jmhumbid\",\"particulars\":\"wdasdad\",\"prNumber\":\"sdfdsgdsgfs\",\"date\":\"2026-02-27\",\"amount\":3000,\"deductedFrom\":\"\"},{\"id\":1035,\"purchaseRequest\":\"asdasdasd\",\"particulars\":\"fdsdfggj,kjkhgfdfsghjj\",\"prNumber\":\"fedgdsgdsfg\",\"date\":\"2026-02-27\",\"amount\":300,\"deductedFrom\":\"\"}]', '[]', '[]', '[{\"category\":\"TEST ENTRY 1\",\"amount\":3300}]', '[]', '[]', '{\"totalAllocated\":25000,\"totalDeductions\":3300,\"totalBalance\":21700,\"prTotal\":3300,\"travelsTotal\":0,\"honorariaTotal\":0}', 5, '2026-02-27 03:39:21', '2026-02-27 05:12:27'),
(26, 14, '2026', 'Engineering', '[{\"category\":\"Honoraria - Overload\",\"accountCode\":\"5010210001\",\"allocated\":2000000,\"deduction\":20000,\"balance\":1980000},{\"category\":\"Honoraria - Part-time\",\"accountCode\":\"5010210001\",\"allocated\":900000,\"deduction\":0,\"balance\":900000}]', '[{\"id\":1024,\"purchaseRequest\":\"test\",\"particulars\":\"test\",\"prNumber\":\"test\",\"date\":\"2026-02-27\",\"amount\":20000,\"deductedFrom\":\"\"}]', '[{\"id\":231,\"travelled\":\"test\",\"event\":\"test\",\"date\":\"2026-02-27\",\"amount\":2000,\"deductedFrom\":\"\"}]', '[]', '[{\"category\":\"Honoraria - Overload\",\"amount\":20000}]', '[]', '[]', '{\"totalAllocated\":2900000,\"totalDeductions\":20000,\"totalBalance\":2880000,\"prTotal\":20000,\"travelsTotal\":2000,\"honorariaTotal\":0}', 5, '2026-03-04 05:01:36', NULL),
(28, 23, '2026', 'Admin', '[{\"category\":\"Office Supplies Expenses\",\"accountCode\":\"5020201000\",\"allocated\":100000,\"deduction\":0,\"balance\":100000}]', '[]', '[{\"id\":238,\"travelled\":\"dwasd\",\"event_activity\":\"sadwa\",\"event\":\"sadwa\",\"date\":\"2026-02-27\",\"amount\":200,\"deductedFrom\":\"\"}]', '[]', '[]', '[]', '[]', '{\"totalAllocated\":100000,\"totalDeductions\":0,\"totalBalance\":100000,\"prTotal\":0,\"travelsTotal\":200,\"honorariaTotal\":0}', 5, '2026-03-07 04:44:55', '2026-03-16 06:53:56'),
(30, 13, '2026', 'Computer Studies', '[{\"category\":\"Honoraria - Overload\",\"accountCode\":\"5010210001\",\"allocated\":728562.92,\"deduction\":0,\"balance\":728562.92},{\"category\":\"Honoraria - Part-time\",\"accountCode\":\"5010210001\",\"allocated\":987390,\"deduction\":0,\"balance\":987390},{\"category\":\"Water Expenses\",\"accountCode\":\"5020401000\",\"allocated\":191400,\"deduction\":0,\"balance\":191400},{\"category\":\"Labor and Wages\",\"accountCode\":\"5021601000\",\"allocated\":432266.34,\"deduction\":0,\"balance\":432266.34},{\"category\":\"Security Services\",\"accountCode\":\"5021203000\",\"allocated\":432266.34,\"deduction\":0,\"balance\":432266.34},{\"category\":\"Electricity Expenses\",\"accountCode\":\"5020402000\",\"allocated\":432266.35,\"deduction\":0,\"balance\":432266.35},{\"category\":\"Fuel, Oil and Lubricants Expenses\",\"accountCode\":\"5020309000\",\"allocated\":5000,\"deduction\":5000,\"balance\":0}]', '[{\"id\":1433,\"purchaseRequest\":\"ITEM 1, Type: Goods, Qty: 2.00, Unit: pcs, Amount: \\u20b15,000.00\",\"particulars\":\"test\",\"prNumber\":\"testt\",\"date\":\"2026-04-15\",\"amount\":5000,\"deductedFrom\":\"\"}]', '[{\"id\":248,\"travelled\":\"John Doe\",\"event_activity\":\"Trip to Japan\",\"event\":\"Trip to Japan\",\"date\":\"2026-03-17\",\"amount\":5000,\"deductedFrom\":\"\"},{\"id\":249,\"travelled\":\"John Fex\",\"event_activity\":\"Trip to Tacloban\",\"event\":\"Trip to Tacloban\",\"date\":\"2026-03-17\",\"amount\":1200,\"deductedFrom\":\"\"}]', '[]', '[{\"category\":\"Fuel, Oil and Lubricants Expenses\",\"items\":[{\"purchaseRequest\":\"ITEM 1, Type: Goods, Qty: 2.00, Unit: pcs, Amount: \\u20b15,000.00\",\"amount\":5000}],\"amount\":5000}]', '[]', '[]', '{\"totalAllocated\":3209151.9499999997,\"totalDeductions\":5000,\"totalBalance\":3204151.9499999997,\"prTotal\":5000,\"travelsTotal\":6200,\"honorariaTotal\":0}', 5, '2026-04-15 07:27:46', NULL);

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
  `entry_id` int(11) DEFAULT NULL COMMENT 'Reference to budget_utilization_entries for deduction tracking',
  `fiscal_year` year(4) NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `is_deducted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `utilization_travels`
--

INSERT INTO `utilization_travels` (`id`, `department_id`, `travelled`, `event_activity`, `date`, `amount`, `entry_id`, `fiscal_year`, `created_by`, `created_at`, `updated_at`, `is_deducted`) VALUES
(227, 26, 'Dexie', 'TEST', '2026-02-17', 10000.00, NULL, '2026', 5, '2026-02-16 00:42:03', '2026-02-16 00:42:29', 0),
(231, 14, 'test', 'test', '2026-02-27', 2000.00, NULL, '2026', 5, '2026-02-27 01:40:56', '2026-02-27 01:41:07', 0),
(238, 23, 'dwasd', 'sadwa', '2026-02-27', 200.00, NULL, '2026', 5, '2026-02-27 03:09:39', '2026-02-27 03:09:44', 0),
(248, 13, 'John Doe', 'Trip to Japan', '2026-03-17', 5000.00, NULL, '2026', 5, '2026-03-17 07:44:33', '2026-03-17 08:17:28', 0),
(249, 13, 'John Fex', 'Trip to Tacloban', '2026-03-17', 1200.00, NULL, '2026', 5, '2026-03-17 08:17:29', '2026-03-17 08:17:47', 0);

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
-- Indexes for table `allocation_drafts`
--
ALTER TABLE `allocation_drafts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_dept_draft` (`department_id`),
  ADD KEY `department_id` (`department_id`);

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
-- Indexes for table `budget_utilization_deduction_sources`
--
ALTER TABLE `budget_utilization_deduction_sources`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_dept_year` (`department_id`,`fiscal_year`),
  ADD KEY `idx_entry` (`entry_id`);

--
-- Indexes for table `budget_utilization_entries`
--
ALTER TABLE `budget_utilization_entries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_lib_id` (`lib_id`);

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
-- Indexes for table `lib_custom_items`
--
ALTER TABLE `lib_custom_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `deleted_by` (`deleted_by`),
  ADD KEY `idx_dept_year` (`department_id`,`year`),
  ADD KEY `idx_deleted` (`deleted_at`);

--
-- Indexes for table `line_item_budgets`
--
ALTER TABLE `line_item_budgets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `approved_by_user_id` (`approved_by_user_id`);

--
-- Indexes for table `line_item_budget_items`
--
ALTER TABLE `line_item_budget_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lib_id` (`lib_id`),
  ADD KEY `parent_id` (`parent_id`);

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
-- Indexes for table `ppmp`
--
ALTER TABLE `ppmp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_ppmp_dept_status` (`department_id`,`status`,`is_final`),
  ADD KEY `idx_ppmp_fiscal_year` (`fiscal_year`),
  ADD KEY `idx_ppmp_type` (`ppmp_type`),
  ADD KEY `idx_dept_type` (`department_id`,`ppmp_type`,`fiscal_year`);

--
-- Indexes for table `ppmp_deductions`
--
ALTER TABLE `ppmp_deductions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ppmp` (`ppmp_id`),
  ADD KEY `idx_ppmp_item` (`ppmp_item_id`),
  ADD KEY `idx_pr` (`purchase_request_id`),
  ADD KEY `idx_utilization` (`utilization_entry_id`),
  ADD KEY `idx_department` (`department_id`),
  ADD KEY `idx_fiscal_year` (`fiscal_year`);

--
-- Indexes for table `ppmp_history`
--
ALTER TABLE `ppmp_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ppmp_id` (`ppmp_id`),
  ADD KEY `changed_by` (`changed_by`);

--
-- Indexes for table `ppmp_items`
--
ALTER TABLE `ppmp_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ppmp_id` (`ppmp_id`),
  ADD KEY `idx_ppmp_lib_sync` (`lib_synced`,`ppmp_id`),
  ADD KEY `idx_ppmp_lib_category` (`lib_category`);

--
-- Indexes for table `ppmp_lib_mappings`
--
ALTER TABLE `ppmp_lib_mappings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_ppmp_item` (`ppmp_item_id`),
  ADD KEY `idx_ppmp_id` (`ppmp_id`),
  ADD KEY `idx_ppmp_item_id` (`ppmp_item_id`),
  ADD KEY `idx_lib_id` (`lib_id`),
  ADD KEY `idx_lib_item_id` (`lib_item_id`);

--
-- Indexes for table `prior_years_custom_columns`
--
ALTER TABLE `prior_years_custom_columns`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_col` (`department_id`,`fiscal_year`,`col_key`),
  ADD KEY `idx_dept_year_col` (`department_id`,`fiscal_year`);

--
-- Indexes for table `prior_years_custom_values`
--
ALTER TABLE `prior_years_custom_values`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_val` (`department_id`,`fiscal_year`,`col_key`,`expense_category`),
  ADD KEY `idx_dept_year_val` (`department_id`,`fiscal_year`);

--
-- Indexes for table `prior_years_entries`
--
ALTER TABLE `prior_years_entries`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_entry` (`department_id`,`fiscal_year`,`expense_category`),
  ADD KEY `idx_dept_year` (`department_id`,`fiscal_year`),
  ADD KEY `idx_category` (`expense_category`);

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
  ADD KEY `idx_submitted_at` (`submitted_at`),
  ADD KEY `idx_ppmp_item` (`ppmp_item_id`),
  ADD KEY `idx_ppmp` (`ppmp_id`);

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
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_ppmp_item` (`ppmp_item_id`),
  ADD KEY `idx_ppmp` (`ppmp_id`);

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
  ADD KEY `idx_created_by` (`created_by`),
  ADD KEY `idx_entry_id` (`entry_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `allocations_history`
--
ALTER TABLE `allocations_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `allocation_drafts`
--
ALTER TABLE `allocation_drafts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6620;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=95;

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
-- AUTO_INCREMENT for table `budget_utilization_deduction_sources`
--
ALTER TABLE `budget_utilization_deduction_sources`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2424;

--
-- AUTO_INCREMENT for table `budget_utilization_entries`
--
ALTER TABLE `budget_utilization_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97985;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6747;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT for table `department_budgets`
--
ALTER TABLE `department_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=145;

--
-- AUTO_INCREMENT for table `file_submissions`
--
ALTER TABLE `file_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `lib_custom_items`
--
ALTER TABLE `lib_custom_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `line_item_budgets`
--
ALTER TABLE `line_item_budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `line_item_budget_items`
--
ALTER TABLE `line_item_budget_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=737;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=964;

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
-- AUTO_INCREMENT for table `ppmp`
--
ALTER TABLE `ppmp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=69;

--
-- AUTO_INCREMENT for table `ppmp_deductions`
--
ALTER TABLE `ppmp_deductions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=98;

--
-- AUTO_INCREMENT for table `ppmp_history`
--
ALTER TABLE `ppmp_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ppmp_items`
--
ALTER TABLE `ppmp_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=671;

--
-- AUTO_INCREMENT for table `ppmp_lib_mappings`
--
ALTER TABLE `ppmp_lib_mappings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prior_years_custom_columns`
--
ALTER TABLE `prior_years_custom_columns`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `prior_years_custom_values`
--
ALTER TABLE `prior_years_custom_values`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `prior_years_entries`
--
ALTER TABLE `prior_years_entries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1049;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchase_request_files`
--
ALTER TABLE `purchase_request_files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=364;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1049;

--
-- AUTO_INCREMENT for table `utilization_purchase_requests`
--
ALTER TABLE `utilization_purchase_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1434;

--
-- AUTO_INCREMENT for table `utilization_summaries`
--
ALTER TABLE `utilization_summaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `utilization_travels`
--
ALTER TABLE `utilization_travels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=250;

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
  ADD CONSTRAINT `fk_util_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_utilization_lib_id` FOREIGN KEY (`lib_id`) REFERENCES `line_item_budgets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

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
-- Constraints for table `lib_custom_items`
--
ALTER TABLE `lib_custom_items`
  ADD CONSTRAINT `lib_custom_items_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lib_custom_items_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `lib_custom_items_ibfk_3` FOREIGN KEY (`deleted_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `line_item_budgets`
--
ALTER TABLE `line_item_budgets`
  ADD CONSTRAINT `lib_approved_by_fk` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `lib_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lib_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `line_item_budget_items`
--
ALTER TABLE `line_item_budget_items`
  ADD CONSTRAINT `lib_items_lib_fk` FOREIGN KEY (`lib_id`) REFERENCES `line_item_budgets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lib_items_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `line_item_budget_items` (`id`) ON DELETE CASCADE;

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
-- Constraints for table `ppmp`
--
ALTER TABLE `ppmp`
  ADD CONSTRAINT `ppmp_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`),
  ADD CONSTRAINT `ppmp_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `ppmp_deductions`
--
ALTER TABLE `ppmp_deductions`
  ADD CONSTRAINT `fk_ppmp_deductions_dept` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ppmp_deductions_ppmp` FOREIGN KEY (`ppmp_id`) REFERENCES `ppmp` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ppmp_deductions_ppmp_item` FOREIGN KEY (`ppmp_item_id`) REFERENCES `ppmp_items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ppmp_deductions_pr` FOREIGN KEY (`purchase_request_id`) REFERENCES `utilization_purchase_requests` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ppmp_history`
--
ALTER TABLE `ppmp_history`
  ADD CONSTRAINT `ppmp_history_ibfk_1` FOREIGN KEY (`ppmp_id`) REFERENCES `ppmp` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ppmp_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `ppmp_items`
--
ALTER TABLE `ppmp_items`
  ADD CONSTRAINT `ppmp_items_ibfk_1` FOREIGN KEY (`ppmp_id`) REFERENCES `ppmp` (`id`) ON DELETE CASCADE;

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
