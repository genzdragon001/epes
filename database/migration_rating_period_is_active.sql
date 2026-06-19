-- Add is_active column to rating_period table
ALTER TABLE `rating_period` 
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `code`;

-- Set the current active period to inactive (if you want to close it)
-- UPDATE `rating_period` SET `is_active` = 0 WHERE `code` = '1-2526';
