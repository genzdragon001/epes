-- Migration: Add percentage_allocation table for faculty-specific function percentages
-- Allows setting different percentages based on academic rank and designation

CREATE TABLE IF NOT EXISTS `percentage_allocation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `position_id` int(11) DEFAULT NULL COMMENT 'Academic rank (NULL = all)',
  `designation_id` int(30) DEFAULT NULL COMMENT 'Designation (NULL = no designation/all)',
  `category` varchar(50) NOT NULL COMMENT 'strategic, core, support',
  `sub_category` varchar(50) DEFAULT NULL COMMENT 'instructions, research, extension (for core)',
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `position_id` (`position_id`),
  KEY `designation_id` (`designation_id`),
  CONSTRAINT `alloc_position_fk` FOREIGN KEY (`position_id`) REFERENCES `position_list` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alloc_designation_fk` FOREIGN KEY (`designation_id`) REFERENCES `designation_list` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default allocations (COS Faculty - no designation)
-- Strategic
INSERT INTO `percentage_allocation` (`position_id`, `designation_id`, `category`, `sub_category`, `percentage`) VALUES
(NULL, NULL, 'strategic', NULL, 10.00),
-- Core - Instructions
(NULL, NULL, 'core', 'instructions', 50.00),
-- Core - Research
(NULL, NULL, 'core', 'research', 20.00),
-- Core - Extension
(NULL, NULL, 'core', 'extension', 10.00),
-- Support
(NULL, NULL, 'support', NULL, 10.00);

-- Default for faculty with designations (higher research and extension)
INSERT INTO `percentage_allocation` (`position_id`, `designation_id`, `category`, `sub_category`, `percentage`) VALUES
(NULL, 1, 'strategic', NULL, 5.00),
(NULL, 1, 'core', 'instructions', 60.00),
(NULL, 1, 'core', 'research', 15.00),
(NULL, 1, 'core', 'extension', 10.00),
(NULL, 1, 'support', NULL, 10.00);
