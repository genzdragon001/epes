-- Table for Academic Ranks
CREATE TABLE IF NOT EXISTS `academic_rank_list` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `rank` varchar(200) NOT NULL,
  `description` text NOT NULL,
  `order_num` int(11) NOT NULL DEFAULT 0,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample academic ranks
INSERT INTO `academic_rank_list` (`rank`, `description`, `order_num`) VALUES
('Instructor I', 'Entry-level teaching position', 1),
('Instructor II', 'Second-level teaching position', 2),
('Instructor III', 'Third-level teaching position', 3),
('Assistant Professor', 'Entry-level professorial rank', 4),
('Associate Professor', 'Mid-level professorial rank', 5),
('Professor', 'Senior professorial rank', 6),
('Professor III', 'Highest professorial rank', 7);

-- Modify task_list to support designation and academic rank filtering
ALTER TABLE `task_list` 
ADD COLUMN `designation_id` INT(30) DEFAULT NULL AFTER `mfo`,
ADD COLUMN `academic_rank_id` INT(30) DEFAULT NULL AFTER `designation_id`,
ADD COLUMN `success_indicators` TEXT DEFAULT NULL AFTER `academic_rank_id`,
ADD COLUMN `targets_measures` TEXT DEFAULT NULL AFTER `success_indicators`,
ADD COLUMN `quality` VARCHAR(50) DEFAULT 'N/A' AFTER `targets_measures`,
ADD COLUMN `timeliness` VARCHAR(50) DEFAULT 'N/A' AFTER `quality`,
ADD COLUMN `efficiency` VARCHAR(50) DEFAULT 'N/A' AFTER `timeliness`,
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `efficiency`;

-- Create task_visibility table for more granular control
CREATE TABLE IF NOT EXISTS `task_visibility` (
  `id` int(30) NOT NULL AUTO_INCREMENT,
  `task_id` int(30) NOT NULL,
  `academic_rank_id` int(30) DEFAULT NULL,
  `designation_id` int(30) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `academic_rank_id` (`academic_rank_id`),
  KEY `designation_id` (`designation_id`),
  CONSTRAINT `task_visibility_task_fk` FOREIGN KEY (`task_id`) REFERENCES `task_list` (`id`) ON DELETE CASCADE,
  CONSTRAINT `task_visibility_rank_fk` FOREIGN KEY (`academic_rank_id`) REFERENCES `academic_rank_list` (`id`) ON DELETE SET NULL,
  CONSTRAINT `task_visibility_designation_fk` FOREIGN KEY (`designation_id`) REFERENCES `designation_list` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
