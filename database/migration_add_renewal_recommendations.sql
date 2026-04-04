-- Add renewal_recommendations table for COS faculty renewal tracking
CREATE TABLE IF NOT EXISTS `renewal_recommendations` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `faculty_id` int(30) NOT NULL,
  `evaluator_id` int(30) NOT NULL,
  `rating_period` varchar(100) NOT NULL,
  `overall_score` decimal(5,2) NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
