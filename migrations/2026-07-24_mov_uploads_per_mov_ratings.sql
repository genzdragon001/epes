-- ============================================================================
-- Migration: per-MOV rating columns on mov_uploads
-- Date: 2026-07-24
-- Adds timeliness_rating, quality_rating, efficiency_submitted to mov_uploads
-- so that generate_mov_summary.php can persist and load per-MOV ratings.
-- ============================================================================

-- Add columns if they don't exist (idempotent)
SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mov_uploads'
   AND COLUMN_NAME = 'timeliness_rating') = 0,
  'ALTER TABLE mov_uploads ADD COLUMN timeliness_rating TINYINT DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mov_uploads'
   AND COLUMN_NAME = 'quality_rating') = 0,
  'ALTER TABLE mov_uploads ADD COLUMN quality_rating TINYINT DEFAULT NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = IF(
  (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mov_uploads'
   AND COLUMN_NAME = 'efficiency_submitted') = 0,
  'ALTER TABLE mov_uploads ADD COLUMN efficiency_submitted TINYINT DEFAULT 0',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;