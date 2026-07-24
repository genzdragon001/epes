-- ============================================================================
-- Migration: per-dimension rating scale on task_list
-- Date: 2026-07-24
-- Adds free-text rating-scale definitions for Quality / Timeliness / Efficiency
-- on each target. Shown in the Edit Target modal only when the dimension is
-- "Applicable", and later rendered as the legend in generate_mov_summary.
-- Safe to re-run: each ADD COLUMN is guarded by an existence check.
-- ============================================================================

SET @dbname = DATABASE();
SET @tablename = 'task_list';

DELIMITER //
CREATE PROCEDURE add_scale_col(IN col_name VARCHAR(64))
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = col_name
    ) THEN
        SET @sql = CONCAT('ALTER TABLE task_list ADD COLUMN ', col_name, ' TEXT NULL AFTER efficiency');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END //
DELIMITER ;

CALL add_scale_col('quality_scale');
CALL add_scale_col('timeliness_scale');
CALL add_scale_col('efficiency_scale');

DROP PROCEDURE add_scale_col;
