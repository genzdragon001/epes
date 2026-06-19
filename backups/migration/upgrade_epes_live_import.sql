-- Upgrade epes_live_import schema to match current local EPES code
-- Apply: mysql -u root < upgrade_epes_live_import.sql

SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

-- ============================================================
-- 1. Add missing columns to existing tables
-- ============================================================

ALTER TABLE `epes_live_import`.`comments`
  ADD COLUMN IF NOT EXISTS `rating_period` varchar(100) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `reply_to` int(11) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  ADD COLUMN IF NOT EXISTS `is_read` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `epes_live_import`.`employee_list`
  ADD COLUMN IF NOT EXISTS `privacy_accepted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `privacy_accepted_at` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `password_changed` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `epes_live_import`.`evaluator_list`
  ADD COLUMN IF NOT EXISTS `privacy_accepted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `privacy_accepted_at` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `password_changed` tinyint(1) NOT NULL DEFAULT 0;

ALTER TABLE `epes_live_import`.`rating_period`
  ADD COLUMN IF NOT EXISTS `start_date` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `end_date` date DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `auto_cascade` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Auto-accumulate from lower level',
  ADD COLUMN IF NOT EXISTS `period_type` varchar(10) NOT NULL DEFAULT 'IPCR' COMMENT 'IPCR, DP, OPCR',
  ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) NOT NULL DEFAULT 1;

ALTER TABLE `epes_live_import`.`ratings`
  ADD COLUMN IF NOT EXISTS `period_type` varchar(10) NOT NULL DEFAULT 'IPCR' COMMENT 'IPCR, DP, OPCR';

ALTER TABLE `epes_live_import`.`users`
  ADD COLUMN IF NOT EXISTS `privacy_accepted` tinyint(1) NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `privacy_accepted_at` datetime DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS `password_changed` tinyint(1) NOT NULL DEFAULT 0;

-- ============================================================
-- 2. Create missing tables
-- ============================================================

CREATE TABLE IF NOT EXISTS `epes_live_import`.`cascading_ratings` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `epes_live_import`.`help_docs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `epes_live_import`.`intervention_flags` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `epes_live_import`.`notifications` (
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

CREATE TABLE IF NOT EXISTS `epes_live_import`.`performance_documents` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `epes_live_import`.`remember_tokens` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `epes_live_import`.`system_backups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `backup_file` varchar(255) NOT NULL,
  `backup_size` bigint(20) NOT NULL DEFAULT 0,
  `backup_type` varchar(50) NOT NULL DEFAULT 'Full',
  `status` varchar(20) NOT NULL DEFAULT 'Success',
  `error_message` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ============================================================
-- 3. Backfill live rating_period metadata
-- ============================================================

-- Existing live row is IPCR period 1-2526
UPDATE `epes_live_import`.`rating_period`
   SET `period_type` = 'IPCR',
       `start_date` = '2025-08-01',
       `end_date` = '2025-12-31',
       `auto_cascade` = 1,
       `is_active` = 1
 WHERE `id` = 1;

-- Create matching DP and OPCR periods for the live data period
INSERT INTO `epes_live_import`.`rating_period`
  (`semester`, `year`, `start_date`, `end_date`, `auto_cascade`, `code`, `period_type`, `is_active`)
VALUES
  ('1st Semester', '2025-2026', '2026-01-01', '2026-01-31', 1, 'DP-1-2526', 'DP', 1),
  ('1st Semester', '2025-2026', '2026-02-01', '2026-02-28', 0, 'OPCR-1-2526', 'OPCR', 1);

-- ============================================================
-- 4. Backfill ratings that lack a rating_period
-- ============================================================

UPDATE `epes_live_import`.`ratings`
   SET `rating_period` = '1-2526',
       `period_type` = 'IPCR'
 WHERE `rating_period` IS NULL OR `rating_period` = '';

-- ============================================================
-- 5. Seed help_docs from local reference data
-- ============================================================

INSERT INTO `epes_live_import`.`help_docs` (`id`, `category`, `title`, `content`, `order`, `is_active`, `created_at`, `updated_at`) VALUES
(1,'Getting Started','System Overview','\n                <h3>Welcome to EPES</h3>\n                <p>The Employee Performance Evaluation System (EPES) is designed to streamline faculty performance documentation and evaluation at DEBESMSCAT.</p>\n                <h4>Key Features:</h4>\n                <ul>\n                    <li>Digital IPCR submission and tracking</li>\n                    <li>Automated performance calculations</li>\n                    <li>Real-time progress monitoring</li>\n                    <li>Comprehensive analytics and reporting</li>\n                    <li>Evidence-based decision making support</li>\n                </ul>\n            ',1,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(2,'Getting Started','User Roles and Permissions','\n                <h3>User Roles</h3>\n                <p>EPES has three main user roles:</p>\n                <h4>1. Faculty Member</h4>\n                <ul>\n                    <li>Submit tasks and accomplishments</li>\n                    <li>View performance ratings</li>\n                    <li>Generate IPCR reports</li>\n                    <li>Track progress in real-time</li>\n                </ul>\n                <h4>2. Evaluator/Department Head</h4>\n                <ul>\n                    <li>Review and verify faculty submissions</li>\n                    <li>Provide ratings and feedback</li>\n                    <li>Generate department reports</li>\n                    <li>Monitor faculty performance</li>\n                </ul>\n                <h4>3. Administrator</h4>\n                <ul>\n                    <li>Manage users and roles</li>\n                    <li>Configure system settings</li>\n                    <li>Generate institutional reports</li>\n                    <li>Oversee evaluation periods</li>\n                </ul>\n            ',2,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(3,'Faculty Guide','How to Submit Tasks','\n                <h3>Submitting Your Tasks</h3>\n                <ol>\n                    <li>Navigate to <strong>Targets</strong> menu</li>\n                    <li>Select the task you want to submit</li>\n                    <li>Click <strong>Submit File</strong></li>\n                    <li>Upload your supporting documents (PDF, DOC, DOCX, images)</li>\n                    <li>Add progress notes if needed</li>\n                    <li>Click <strong>Submit</strong></li>\n                </ol>\n                <h4>File Requirements:</h4>\n                <ul>\n                    <li>Maximum file size: 10MB</li>\n                    <li>Allowed formats: PDF, DOC, DOCX, JPG, PNG, XLS, XLSX</li>\n                    <li>Ensure files are clearly labeled</li>\n                </ul>\n            ',3,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(4,'Faculty Guide','Viewing Your Performance','\n                <h3>Performance Dashboard</h3>\n                <p>Your dashboard shows:</p>\n                <ul>\n                    <li><strong>Total Targets:</strong> Number of tasks assigned to your position</li>\n                    <li><strong>Submitted:</strong> Tasks you have submitted</li>\n                    <li><strong>For Verification:</strong> Tasks awaiting evaluator review</li>\n                    <li><strong>Verified:</strong> Tasks approved by your evaluator</li>\n                </ul>\n                <h3>Performance Metrics:</h3>\n                <ul>\n                    <li><strong>Efficiency:</strong> How well you utilize resources</li>\n                    <li><strong>Timeliness:</strong> Meeting deadlines</li>\n                    <li><strong>Quality:</strong> Quality of work submitted</li>\n                </ul>\n            ',4,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(5,'Evaluator Guide','How to Verify Tasks','\n                <h3>Verifying Faculty Submissions</h3>\n                <ol>\n                    <li>Go to <strong>Faculty Evaluation</strong> menu</li>\n                    <li>Select the faculty member</li>\n                    <li>Review submitted tasks and evidence</li>\n                    <li>Provide ratings for:</li>\n                    <ul>\n                        <li>Efficiency (1-5 scale)</li>\n                        <li>Timeliness (1-5 scale)</li>\n                        <li>Quality (1-5 scale)</li>\n                    </ul>\n                    <li>Add comments or feedback</li>\n                    <li>Click <strong>Verify</strong> to approve</li>\n                </ol>\n                <h4>Rating Guidelines:</h4>\n                <ul>\n                    <li>5 - Outstanding</li>\n                    <li>4 - Very Satisfactory</li>\n                    <li>3 - Satisfactory</li>\n                    <li>2 - Unsatisfactory</li>\n                    <li>1 - Poor</li>\n                </ul>\n            ',5,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(6,'Evaluator Guide','Generating Recommendations','\n                <h3>Contract Renewal Recommendations</h3>\n                <p>For COS faculty, evaluators must provide renewal recommendations:</p>\n                <ol>\n                    <li>Navigate to <strong>Recommendation</strong> menu</li>\n                    <li>Review faculty performance data</li>\n                    <li>System calculates overall score automatically</li>\n                    <li>Provide recommendation:</li>\n                    <ul>\n                        <li><strong>Recommended</strong> - For renewal</li>\n                        <li><strong>Not Recommended</strong> - Not for renewal</li>\n                    </ul>\n                    <li>Add statement of reason</li>\n                    <li>Submit for Dean review</li>\n                </ol>\n            ',6,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(7,'Admin Guide','Managing Users','\n                <h3>User Management</h3>\n                <h4>Adding New Faculty:</h4>\n                <ol>\n                    <li>Go to <strong>Employees > Add New</strong></li>\n                    <li>Fill in required information</li>\n                    <li>Assign department and position</li>\n                    <li>Assign evaluator</li>\n                    <li>Save and notify user</li>\n                </ol>\n                <h4>Resetting Password:</h4>\n                <ol>\n                    <li>Go to user list</li>\n                    <li>Click <strong>Reset Password</strong></li>\n                    <li>System sends reset link via email</li>\n                </ol>\n            ',7,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(8,'Admin Guide','Configuring Rating Period','\n                <h3>Setting Rating Period</h3>\n                <ol>\n                    <li>Navigate to <strong>Rating Period</strong></li>\n                    <li>Select semester:</li>\n                    <ul>\n                        <li>1st Semester</li>\n                        <li>2nd Semester</li>\n                        <li>Summer</li>\n                    </ul>\n                    <li>Enter academic year (e.g., 2024-2025)</li>\n                    <li>Set as active period</li>\n                </ol>\n                <p><strong>Note:</strong> Only one rating period can be active at a time.</p>\n            ',8,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(9,'Reports','Generating IPCR Report','\n                <h3>Individual Performance Commitment and Review</h3>\n                <ol>\n                    <li>Go to your <strong>Dashboard</strong></li>\n                    <li>Click <strong>Generate IPCR</strong></li>\n                    <li>Select rating period</li>\n                    <li>Review the generated form</li>\n                    <li>Download as PDF</li>\n                </ol>\n                <p>The IPCR form includes:</p>\n                <ul>\n                    <li>Performance ratings by criterion</li>\n                    <li>Task accomplishments</li>\n                    <li>Overall score and adjectival rating</li>\n                    <li>Signature sections</li>\n                </ul>\n            ',9,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(10,'Reports','Generating OPCR Summary','\n                <h3>Office Performance Commitment and Review</h3>\n                <p>For Administrators and Deans:</p>\n                <ol>\n                    <li>Go to <strong>Reports > OPCR Summary</strong></li>\n                    <li>Select department</li>\n                    <li>Select rating period</li>\n                    <li>Generate report</li>\n                </ol>\n                <p>The OPCR summary includes:</p>\n                <ul>\n                    <li>Department performance metrics</li>\n                    <li>Faculty count and participation</li>\n                    <li>Overall averages</li>\n                    <li>Comparative analysis</li>\n                </ul>\n            ',10,1,'2026-06-18 15:26:03','2026-06-18 15:26:03'),
(11,'Troubleshooting','Common Issues','\n                <h3>Frequently Encountered Problems</h3>\n                <h4>Cannot Login</h4>\n                <ul>\n                    <li>Verify email and password</li>\n                    <li>Check if account is activated</li>\n                    <li>Use \"Forgot Password\" if needed</li>\n                    <li>Contact admin if account is locked</li>\n                </ul>\n                <h4>File Upload Failed</h4>\n                <ul>\n                    <li>Check file size (max 10MB)</li>\n                    <li>Verify file format is allowed</li>\n                    <li>Ensure stable internet connection</li>\n                </ul>\n                <h4>Cannot Submit Task</h4>\n                <ul>\n                    <li>Ensure task is assigned to you</li>\n                    <li>Check if rating period is active</li>\n                    <li>Verify all required fields are filled</li>\n                </ul>\n            ',11,1,'2026-06-18 15:26:03','2026-06-18 15:26:03');

SET FOREIGN_KEY_CHECKS=1;
