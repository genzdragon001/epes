-- Migration: Add function_categories table for strategic, core, and support functions
-- Strategic Functions (%)
-- Core Functions (%) - divided into: Instructions, Research, Extension
-- Support Functions (%)

-- Create function_categories table
CREATE TABLE IF NOT EXISTS `function_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL COMMENT 'strategic, core, support',
  `sub_category` varchar(50) DEFAULT NULL COMMENT 'instructions, research, extension (for core only)',
  `name` varchar(200) NOT NULL,
  `percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `date_created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default categories
INSERT INTO `function_categories` (`category`, `sub_category`, `name`, `percentage`) VALUES
-- Strategic Functions
('strategic', NULL, 'Strategic Function', 20.00),
-- Core Functions - Instructions
('core', 'instructions', 'Instructions', 26.67),
-- Core Functions - Research
('core', 'research', 'Research', 26.67),
-- Core Functions - Extension
('core', 'extension', 'Extension', 26.66),
-- Support Functions
('support', NULL, 'Support Function', 20.00);

-- Create function_list table for detailed items under each category
CREATE TABLE IF NOT EXISTS `function_list` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample function items
INSERT INTO `function_list` (`function_category_id`, `designation_id`, `name`, `percentage`) VALUES
-- Strategic (applies to all)
(1, NULL, 'Strategic Planning & Management', 10.00),
(1, NULL, 'Policy Development', 5.00),
(1, NULL, 'Stakeholder Engagement', 5.00),
-- Instructions (applies to all by default)
(2, NULL, 'Curriculum Development', 8.89),
(2, NULL, 'Classroom Teaching', 8.89),
(2, NULL, 'Student Advising', 4.44),
(2, NULL, 'Laboratory/Skills Development', 4.45),
-- Research (applies to all by default)
(3, NULL, 'Research Publication', 13.34),
(3, NULL, 'Research Presentation', 6.67),
(3, NULL, 'Research Community Extension', 6.66),
-- Extension (applies to all by default)
(4, NULL, 'Community Outreach Programs', 8.89),
(4, NULL, 'Technical Assistance', 8.88),
(4, NULL, 'Partnership Development', 8.89),
-- Support Functions (applies to all by default)
(5, NULL, 'Administrative Tasks', 10.00),
(5, NULL, 'Committee Work', 5.00),
(5, NULL, 'Professional Development', 5.00);
