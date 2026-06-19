-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: epes_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `cascading_ratings`
--

DROP TABLE IF EXISTS `cascading_ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cascading_ratings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source_period_id` int(11) NOT NULL COMMENT 'rating_period.id of source period',
  `target_period_id` int(11) NOT NULL COMMENT 'rating_period.id of target (higher) period',
  `employee_id` int(11) DEFAULT NULL COMMENT 'NULL for department/office-level DP/OPCR',
  `department_id` int(11) DEFAULT NULL COMMENT 'NULL for individual IPCR',
  `level` enum('DP','OPCR') NOT NULL,
  `avg_efficiency` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_timeliness` decimal(5,2) NOT NULL DEFAULT 0.00,
  `avg_quality` decimal(5,2) NOT NULL DEFAULT 0.00,
  `overall_rating` decimal(5,2) NOT NULL DEFAULT 0.00,
  `computed_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cascade` (`source_period_id`,`target_period_id`,`employee_id`,`department_id`,`level`),
  KEY `idx_target_period` (`target_period_id`),
  KEY `idx_employee` (`employee_id`),
  CONSTRAINT `cascading_ratings_ibfk_1` FOREIGN KEY (`target_period_id`) REFERENCES `rating_period` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `comments`
--

DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `comment_text` text NOT NULL,
  `employee_id` int(30) NOT NULL,
  `rater_id` int(10) NOT NULL,
  `rating_period` varchar(100) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reply_to` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_comments_employee` (`employee_id`),
  KEY `idx_comments_rater` (`rater_id`),
  KEY `idx_employee_period` (`employee_id`,`rating_period`),
  KEY `idx_rater` (`rater_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `department_list`
--

DROP TABLE IF EXISTS `department_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `department_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `department` varchar(200) NOT NULL,
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `designation_list`
--

DROP TABLE IF EXISTS `designation_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `designation_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `designation` varchar(200) NOT NULL DEFAULT 'Faculty',
  `description` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `efficiency_attendance`
--

DROP TABLE IF EXISTS `efficiency_attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `efficiency_attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `rating_period` varchar(50) NOT NULL,
  `activity_title` varchar(255) NOT NULL,
  `date_conducted` date NOT NULL,
  `percentage` decimal(5,2) NOT NULL,
  `rating` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `target_id` (`target_id`),
  CONSTRAINT `efficiency_attendance_ibfk_1` FOREIGN KEY (`faculty_id`) REFERENCES `employee_list` (`id`) ON DELETE CASCADE,
  CONSTRAINT `efficiency_attendance_ibfk_2` FOREIGN KEY (`target_id`) REFERENCES `task_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `employee_list`
--

DROP TABLE IF EXISTS `employee_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `employee_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `firstname` varchar(200) NOT NULL,
  `middlename` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `is_activated` int(10) NOT NULL DEFAULT 0,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` varchar(30) DEFAULT NULL,
  `failed_login` int(11) NOT NULL DEFAULT 0,
  `isBlocked` int(11) NOT NULL DEFAULT 0,
  `password` text NOT NULL,
  `password_changed` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_accepted_at` datetime DEFAULT NULL,
  `department_id` int(30) NOT NULL,
  `position_id` int(10) NOT NULL,
  `designation_id` int(30) NOT NULL DEFAULT 3,
  `evaluator_id` int(30) NOT NULL,
  `avatar` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_employee_list_email` (`email`),
  KEY `idx_employee_list_reset_token` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `evaluator_list`
--

DROP TABLE IF EXISTS `evaluator_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `evaluator_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `department_id` int(10) NOT NULL,
  `type` int(11) NOT NULL DEFAULT 0,
  `firstname` varchar(200) NOT NULL,
  `middlename` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` varchar(30) DEFAULT NULL,
  `failed_login` int(11) NOT NULL DEFAULT 0,
  `isBlocked` int(11) NOT NULL DEFAULT 0,
  `password` text NOT NULL,
  `password_changed` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_accepted_at` datetime DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_evaluator_list_email` (`email`),
  KEY `idx_evaluator_list_reset_token` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `function_categories`
--

DROP TABLE IF EXISTS `function_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `function_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL COMMENT 'strategic, core, support',
  `sub_category` varchar(50) DEFAULT NULL COMMENT 'instructions, research, extension (for core only)',
  `name` varchar(200) NOT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `function_list`
--

DROP TABLE IF EXISTS `function_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `function_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `function_category_id` int(11) NOT NULL,
  `designation_id` int(30) DEFAULT NULL COMMENT 'NULL means applies to all',
  `name` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `function_category_id` (`function_category_id`),
  KEY `designation_id` (`designation_id`),
  CONSTRAINT `function_list_cat_fk` FOREIGN KEY (`function_category_id`) REFERENCES `function_categories` (`id`) ON DELETE CASCADE,
  CONSTRAINT `function_list_desig_fk` FOREIGN KEY (`designation_id`) REFERENCES `designation_list` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `help_docs`
--

DROP TABLE IF EXISTS `help_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `help_docs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_active_order` (`is_active`,`order`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intervention_flags`
--

DROP TABLE IF EXISTS `intervention_flags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intervention_flags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `employee_id` int(11) NOT NULL,
  `flag_type` enum('3_CONSECUTIVE_LOW') NOT NULL DEFAULT '3_CONSECUTIVE_LOW',
  `consecutive_periods` text NOT NULL COMMENT 'JSON array of period IDs',
  `overall_ratings` text NOT NULL COMMENT 'JSON array of ratings for those periods',
  `flagged_at` datetime NOT NULL DEFAULT current_timestamp(),
  `acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `acknowledged_by` int(11) DEFAULT NULL,
  `acknowledged_at` datetime DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_flag` (`employee_id`,`flag_type`,`consecutive_periods`(255)),
  KEY `idx_employee_period` (`employee_id`),
  KEY `idx_acknowledged` (`acknowledged`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `login_audit_trail`
--

DROP TABLE IF EXISTS `login_audit_trail`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_audit_trail` (
  `audit_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) DEFAULT NULL,
  `username` varchar(150) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `login_status` enum('SUCCESS','FAILED') NOT NULL,
  `failure_reason` text DEFAULT NULL,
  `login_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `session_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `idx_audit_ip_status` (`ip_address`,`login_status`,`login_time`),
  KEY `idx_audit_user` (`user_id`),
  KEY `idx_audit_created` (`login_time`)
) ENGINE=InnoDB AUTO_INCREMENT=1268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mov_summary`
--

DROP TABLE IF EXISTS `mov_summary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mov_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `rating_period` varchar(100) NOT NULL,
  `target_id` int(11) DEFAULT NULL,
  `total_movs` int(11) DEFAULT 0,
  `verified_movs` int(11) DEFAULT 0,
  `pending_movs` int(11) DEFAULT 0,
  `rejected_movs` int(11) DEFAULT 0,
  `total_file_size` bigint(20) DEFAULT 0,
  `last_submission` datetime DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `faculty_period_target` (`faculty_id`,`rating_period`,`target_id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `rating_period` (`rating_period`)
) ENGINE=InnoDB AUTO_INCREMENT=36 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mov_uploads`
--

DROP TABLE IF EXISTS `mov_uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mov_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `rating_period` varchar(100) DEFAULT NULL,
  `mov_type` varchar(50) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `task_id` (`task_id`),
  KEY `target_id` (`target_id`),
  KEY `status` (`status`),
  KEY `rating_period` (`rating_period`),
  KEY `idx_date_submitted` (`date_submitted`),
  KEY `idx_faculty_status` (`faculty_id`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` tinyint(4) NOT NULL DEFAULT 0,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('Info','Warning','Success','Danger') NOT NULL DEFAULT 'Info',
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_unread` (`user_id`,`is_read`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `percentage_allocation`
--

DROP TABLE IF EXISTS `percentage_allocation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `percentage_allocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position_id` int(11) DEFAULT NULL COMMENT 'Academic rank (NULL = all)',
  `designation_id` int(30) DEFAULT 3 COMMENT 'Designation (NULL = no designation/all)',
  `category` varchar(50) NOT NULL COMMENT 'strategic, core, support',
  `sub_category` varchar(50) DEFAULT NULL COMMENT 'instructions, research, extension (for core)',
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `position_id` (`position_id`),
  KEY `designation_id` (`designation_id`),
  CONSTRAINT `alloc_designation_fk` FOREIGN KEY (`designation_id`) REFERENCES `designation_list` (`id`) ON DELETE SET NULL,
  CONSTRAINT `alloc_position_fk` FOREIGN KEY (`position_id`) REFERENCES `position_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1102 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `performance_documents`
--

DROP TABLE IF EXISTS `performance_documents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `performance_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `document_type` enum('IPCR','DPCR','OPCR') NOT NULL,
  `faculty_id` int(11) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `rating_period_id` int(11) NOT NULL,
  `rating_period_label` varchar(100) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_size` bigint(20) NOT NULL DEFAULT 0,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_faculty_period` (`faculty_id`,`rating_period_id`),
  KEY `idx_dept_period` (`department_id`,`rating_period_id`),
  KEY `idx_type` (`document_type`),
  KEY `rating_period_id` (`rating_period_id`),
  CONSTRAINT `performance_documents_ibfk_1` FOREIGN KEY (`rating_period_id`) REFERENCES `rating_period` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `position_list`
--

DROP TABLE IF EXISTS `position_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `position_list` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `position` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `rating_period`
--

DROP TABLE IF EXISTS `rating_period`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `rating_period` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `semester` varchar(30) NOT NULL,
  `year` varchar(30) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `auto_cascade` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Auto-accumulate from lower level',
  `code` varchar(30) NOT NULL,
  `period_type` varchar(10) NOT NULL DEFAULT 'IPCR' COMMENT 'IPCR, DP, OPCR',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ratings`
--

DROP TABLE IF EXISTS `ratings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ratings` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `employee_id` int(30) NOT NULL,
  `task_id` int(30) NOT NULL,
  `evaluator_id` int(30) NOT NULL,
  `efficiency` float NOT NULL,
  `timeliness` float NOT NULL,
  `quality` float NOT NULL,
  `remarks` text NOT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `ratedOn` datetime NOT NULL DEFAULT current_timestamp(),
  `rating_period` varchar(30) NOT NULL,
  `period_type` varchar(10) NOT NULL DEFAULT 'IPCR' COMMENT 'IPCR, DP, OPCR',
  PRIMARY KEY (`id`),
  KEY `idx_ratings_employee` (`employee_id`),
  KEY `idx_ratings_task` (`task_id`),
  KEY `idx_ratings_period` (`rating_period`),
  KEY `idx_ratings_emp_period_type` (`employee_id`,`rating_period`,`period_type`),
  KEY `idx_ratings_period_type` (`rating_period`,`period_type`)
) ENGINE=InnoDB AUTO_INCREMENT=745 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `remember_tokens`
--

DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `user_type` tinyint(4) NOT NULL COMMENT '0=faculty,1=evaluator,2=admin',
  `selector` char(32) NOT NULL,
  `hashed_validator` char(64) NOT NULL,
  `expires` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_selector` (`selector`),
  KEY `idx_user` (`user_id`,`user_type`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `renewal_recommendations`
--

DROP TABLE IF EXISTS `renewal_recommendations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `renewal_recommendations` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(30) NOT NULL,
  `evaluator_id` int(30) NOT NULL,
  `rating_period` varchar(100) NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
  `instruction_ave` decimal(5,2) DEFAULT NULL,
  `support_ave` decimal(5,2) DEFAULT NULL,
  `total_tasks` int(11) NOT NULL DEFAULT 0,
  `verified_tasks` int(11) NOT NULL DEFAULT 0,
  `avg_efficiency` decimal(3,2) DEFAULT NULL,
  `avg_timeliness` decimal(3,2) DEFAULT NULL,
  `avg_quality` decimal(3,2) DEFAULT NULL,
  `recommendation_status` enum('Pending','Recommended','Not Recommended','For Review') DEFAULT 'Pending',
  `system_generated_reason` text NOT NULL,
  `dean_reason` text DEFAULT NULL,
  `dean_decision` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `dean_decision_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `evaluator_id` (`evaluator_id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_backups`
--

DROP TABLE IF EXISTS `system_backups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_file` varchar(255) NOT NULL,
  `backup_size` bigint(20) NOT NULL DEFAULT 0,
  `backup_type` varchar(50) NOT NULL DEFAULT 'Full',
  `status` varchar(20) NOT NULL DEFAULT 'Success',
  `error_message` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `system_settings`
--

DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `name` text NOT NULL,
  `email` varchar(200) NOT NULL,
  `contact` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `cover_img` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `target_deadlines`
--

DROP TABLE IF EXISTS `target_deadlines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `target_deadlines` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `target_id` int(11) NOT NULL,
  `deadline` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `target_id` (`target_id`),
  CONSTRAINT `target_deadlines_ibfk_1` FOREIGN KEY (`target_id`) REFERENCES `task_list` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `target_exemptions`
--

DROP TABLE IF EXISTS `target_exemptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `target_exemptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_id` int(11) NOT NULL,
  `position_id` int(11) DEFAULT NULL,
  `designation_id` int(11) DEFAULT NULL,
  `sub_category` varchar(50) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_exemption` (`task_id`,`position_id`,`designation_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task_list`
--

DROP TABLE IF EXISTS `task_list`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_list` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `mfo` int(11) NOT NULL,
  `designation_id` int(30) DEFAULT NULL,
  `academic_rank_id` int(30) DEFAULT NULL,
  `category` varchar(20) DEFAULT NULL,
  `sub_category` varchar(50) DEFAULT NULL,
  `major_output` varchar(255) DEFAULT NULL,
  `success_indicators` text DEFAULT NULL,
  `targets_measures` text DEFAULT NULL,
  `quality` enum('Applicable','Not Applicable') DEFAULT 'Applicable',
  `timeliness` enum('Applicable','Not Applicable') DEFAULT 'Applicable',
  `efficiency` enum('Applicable','Not Applicable') DEFAULT 'Applicable',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `date_created` datetime DEFAULT current_timestamp(),
  `date_updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_task_list_active` (`is_active`),
  CONSTRAINT `task_list_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=38 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `task_progress`
--

DROP TABLE IF EXISTS `task_progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login_type` int(11) NOT NULL DEFAULT 0,
  `task_id` int(30) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `mfo` text NOT NULL,
  `progress` text NOT NULL,
  `is_complete` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0=no,1=Yes',
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `file_path` varchar(50) NOT NULL,
  `file_type` varchar(10) NOT NULL,
  `date_submitted` datetime NOT NULL DEFAULT current_timestamp(),
  `date_verified` datetime DEFAULT NULL,
  `rating_period` varchar(30) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_task_progress_faculty` (`faculty_id`),
  KEY `idx_task_progress_task` (`task_id`),
  KEY `idx_task_progress_date` (`date_created`),
  KEY `idx_tp_faculty_period` (`faculty_id`,`rating_period`)
) ENGINE=InnoDB AUTO_INCREMENT=836 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `firstname` varchar(200) NOT NULL,
  `lastname` varchar(200) NOT NULL,
  `email` varchar(200) NOT NULL,
  `password` text NOT NULL,
  `password_changed` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `privacy_accepted_at` datetime DEFAULT NULL,
  `avatar` text DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  `reset_token` varchar(100) NOT NULL,
  `reset_expires` varchar(30) NOT NULL,
  `failed_login` int(11) NOT NULL,
  `isBlocked` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_users_email` (`email`),
  KEY `idx_users_reset_token` (`reset_token`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-06-19  8:35:59
