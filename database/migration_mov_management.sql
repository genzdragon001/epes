-- MOV Management Module Database Migration
-- Run this SQL to add MOV management tables

CREATE TABLE IF NOT EXISTS `mov_uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(11) NOT NULL,
  `task_id` int(11) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int(11) DEFAULT 0,
  `date_submitted` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('Pending','Verified','Rejected') DEFAULT 'Pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_date` datetime DEFAULT NULL,
  `remarks` text,
  `rating_period` varchar(100) DEFAULT NULL,
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `task_id` (`task_id`),
  KEY `target_id` (`target_id`),
  KEY `status` (`status`),
  KEY `rating_period` (`rating_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `mov_summary` (
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
  `date_created` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_updated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `faculty_period_target` (`faculty_id`, `rating_period`, `target_id`),
  KEY `faculty_id` (`faculty_id`),
  KEY `rating_period` (`rating_period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add index for faster queries
ALTER TABLE `mov_uploads` ADD INDEX `idx_date_submitted` (`date_submitted`);
ALTER TABLE `mov_uploads` ADD INDEX `idx_faculty_status` (`faculty_id`, `status`);
