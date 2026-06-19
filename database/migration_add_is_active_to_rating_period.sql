-- Migration: Add is_active column to rating_period table
-- Date: 2026-04-17

ALTER TABLE `rating_period` 
ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `code`;

-- Optional: Set existing periods to inactive if you want to close them
-- UPDATE `rating_period` SET `is_active` = 0;

-- Optional: Set a specific period as active
-- UPDATE `rating_period` SET `is_active` = 1 WHERE `code` = '1-2526';
