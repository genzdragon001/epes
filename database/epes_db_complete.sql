-- ============================================
-- EPES Complete Database Schema
-- Version 2.0.0 - 2025
-- ============================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+08:00";
START TRANSACTION;

-- ============================================
-- CORE USER MANAGEMENT TABLES
-- ============================================

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `avatar` text DEFAULT NULL,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `failed_login` int(11) DEFAULT 0,
  `isBlocked` tinyint(1) DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `employee_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `middlename` varchar(200) DEFAULT '',
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` int(30) NOT NULL,
  `designation_id` int(30) NOT NULL,
  `position_id` int(30) DEFAULT NULL,
  `evaluator_id` int(30) NOT NULL,
  `avatar` text DEFAULT NULL,
  `failed_login` int(11) DEFAULT 0,
  `isBlocked` tinyint(1) DEFAULT 0,
  `is_activated` tinyint(1) DEFAULT 0,
  `reset_token` varchar(255) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `employment_status` enum('Permanent','COS','Contractual') DEFAULT 'Permanent',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `employee_id` (`employee_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `evaluator_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `middlename` varchar(200) DEFAULT '',
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` varchar(255) NOT NULL,
  `department_id` int(30) DEFAULT NULL,
  `type` tinyint(1) DEFAULT 0 COMMENT '0=Dept Head, 1=Dean',
  `avatar` text DEFAULT NULL,
  `failed_login` int(11) DEFAULT 0,
  `isBlocked` tinyint(1) DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ORGANIZATIONAL STRUCTURE
-- ============================================

CREATE TABLE IF NOT EXISTS `department_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `department` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `user_ids` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `department` (`department`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `designation_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `designation` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `user_ids` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `designation` (`designation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `position_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `position` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `position` (`position`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- PERFORMANCE MANAGEMENT TABLES
-- ============================================

CREATE TABLE IF NOT EXISTS `rating_period` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `semester` varchar(50) NOT NULL,
  `year` varchar(50) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `function_categories` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `sub_category` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `weight` decimal(5,2) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `percentage_allocation` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `position_id` int(30) NOT NULL,
  `designation_id` int(30) DEFAULT NULL,
  `category` varchar(100) NOT NULL,
  `sub_category` varchar(100) DEFAULT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `task` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `sub_category` varchar(100) DEFAULT NULL,
  `success_indicators` text DEFAULT NULL,
  `efficiency` varchar(50) DEFAULT 'Applicable',
  `timeliness` varchar(50) DEFAULT 'Applicable',
  `quality` varchar(50) DEFAULT 'Applicable',
  `weight` decimal(5,2) DEFAULT 0,
  `academic_rank_id` int(30) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `status` int(1) NOT NULL DEFAULT 0 COMMENT '0=pending, 1=on-progress, 2=completed',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `target_exemptions` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `task_id` int(30) NOT NULL,
  `position_id` int(30) NOT NULL,
  `reason` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task_progress` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `task_id` int(30) NOT NULL,
  `faculty_id` int(30) NOT NULL,
  `progress` enum('Pending','On Progress','For Verification','Verified','Completed') DEFAULT 'Pending',
  `file_path` text DEFAULT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `date_verified` datetime DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- EVALUATION & RATINGS TABLES
-- ============================================

CREATE TABLE IF NOT EXISTS `ratings` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `employee_id` int(30) NOT NULL,
  `task_id` int(30) NOT NULL,
  `evaluator_id` int(30) NOT NULL,
  `efficiency` decimal(5,2) DEFAULT 0,
  `timeliness` decimal(5,2) DEFAULT 0,
  `quality` decimal(5,2) DEFAULT 0,
  `accuracy` decimal(5,2) DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `semester` varchar(50) DEFAULT NULL,
  `year` varchar(50) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `employee_id` int(30) NOT NULL,
  `rater_id` int(30) NOT NULL,
  `comment_text` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- RECOMMENDATIONS TABLES
-- ============================================

CREATE TABLE IF NOT EXISTS `renewal_recommendations` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(30) NOT NULL,
  `evaluator_id` int(30) NOT NULL,
  `rating_period` varchar(50) NOT NULL,
  `overall_score` decimal(5,2) DEFAULT 0,
  `instruction_ave` decimal(5,2) DEFAULT NULL,
  `support_ave` decimal(5,2) DEFAULT NULL,
  `total_tasks` int(11) DEFAULT 0,
  `verified_tasks` int(11) DEFAULT 0,
  `avg_efficiency` decimal(5,2) DEFAULT NULL,
  `avg_timeliness` decimal(5,2) DEFAULT NULL,
  `avg_quality` decimal(5,2) DEFAULT NULL,
  `recommendation_status` enum('Pending','Recommended','Not Recommended') DEFAULT 'Pending',
  `system_generated_reason` text DEFAULT NULL,
  `dean_decision` enum('Approved','Disapproved','Pending') DEFAULT 'Pending',
  `dean_reason` text DEFAULT NULL,
  `dean_decision_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- SYSTEM & AUDIT TABLES
-- ============================================

CREATE TABLE IF NOT EXISTS `system_settings` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(200) DEFAULT NULL,
  `contact` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `cover_img` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `login_audit_trail` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `user_id` int(30) DEFAULT NULL,
  `username` varchar(200) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_status` enum('SUCCESS','FAILED') NOT NULL,
  `failure_reason` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `system_logs` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `user_id` int(30) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_affected` varchar(100) DEFAULT NULL,
  `record_id` int(30) DEFAULT NULL,
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- IPCR & OPCR TABLES (NEW)
-- ============================================

CREATE TABLE IF NOT EXISTS `ipcr_forms` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(30) NOT NULL,
  `rating_period` varchar(50) NOT NULL,
  `instruction_rating` decimal(5,2) DEFAULT 0,
  `research_rating` decimal(5,2) DEFAULT 0,
  `extension_rating` decimal(5,2) DEFAULT 0,
  `production_rating` decimal(5,2) DEFAULT 0,
  `overall_rating` decimal(5,2) DEFAULT 0,
  `adjectival_rating` varchar(50) DEFAULT NULL,
  `strengths` text DEFAULT NULL,
  `areas_for_improvement` text DEFAULT NULL,
  `faculty_comments` text DEFAULT NULL,
  `evaluator_comments` text DEFAULT NULL,
  `status` enum('Draft','Submitted','Evaluated','Finalized') DEFAULT 'Draft',
  `submitted_at` datetime DEFAULT NULL,
  `evaluated_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `opcr_summary` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `department_id` int(30) NOT NULL,
  `rating_period` varchar(50) NOT NULL,
  `total_faculty` int(11) DEFAULT 0,
  `avg_instruction` decimal(5,2) DEFAULT 0,
  `avg_research` decimal(5,2) DEFAULT 0,
  `avg_extension` decimal(5,2) DEFAULT 0,
  `avg_production` decimal(5,2) DEFAULT 0,
  `overall_average` decimal(5,2) DEFAULT 0,
  `recommendations` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- BACKUP & NOTIFICATION TABLES (NEW)
-- ============================================

CREATE TABLE IF NOT EXISTS `system_backups` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `backup_file` varchar(255) NOT NULL,
  `backup_size` bigint(20) DEFAULT 0,
  `backup_type` enum('Full','Incremental') DEFAULT 'Full',
  `status` enum('Success','Failed') DEFAULT 'Success',
  `created_by` int(30) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `user_id` int(30) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('Info','Warning','Error','Success') DEFAULT 'Info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- HELP DOCUMENTATION TABLE (NEW)
-- ============================================

CREATE TABLE IF NOT EXISTS `help_docs` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DEFAULT DATA
-- ============================================

INSERT INTO `system_settings` (`id`, `name`, `email`, `contact`, `address`) VALUES
(1, 'Employee Performance Evaluation System', 'admin@epes.edu.ph', '+63-XXX-XXX-XXXX', 'Philippines');

INSERT INTO `users` (`id`, `firstname`, `lastname`, `email`, `password`) VALUES
(1, 'Administrator', 'Admin', 'admin@admin.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO `rating_period` (`semester`, `year`, `is_active`) VALUES
('1st Semester', '2024-2025', 1);

INSERT INTO `function_categories` (`category`, `sub_category`, `description`, `weight`) VALUES
('Core', 'Instructions', 'Teaching and Instructional Activities', 0),
('Core', 'Research', 'Research and Innovation Activities', 0),
('Core', 'Extension', 'Community Extension Services', 0),
('Support', NULL, 'Administrative and Support Services', 0),
('Strategic', NULL, 'Strategic Institutional Projects', 0);

COMMIT;
