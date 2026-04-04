-- Migration: Add target management columns to task_list
-- This migration adds the required columns for the target management module

-- Add mfo column if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `mfo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `status`;

-- Add designation_id column if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `designation_id` INT(30) DEFAULT NULL AFTER `mfo`;

-- Add academic_rank_id column (maps to position_list) if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `academic_rank_id` INT(30) DEFAULT NULL AFTER `designation_id`;

-- Add success_indicators column if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `success_indicators` TEXT DEFAULT NULL AFTER `academic_rank_id`;

-- Add targets_measures column if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `targets_measures` TEXT DEFAULT NULL AFTER `success_indicators`;

-- Add quality column if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `quality` VARCHAR(50) DEFAULT 'N/A' AFTER `targets_measures`;

-- Add timeliness column if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `timeliness` VARCHAR(50) DEFAULT 'N/A' AFTER `quality`;

-- Add efficiency column if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `efficiency` VARCHAR(50) DEFAULT 'N/A' AFTER `timeliness`;

-- Add is_active column if not exists
ALTER TABLE `task_list` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1 AFTER `efficiency`;
