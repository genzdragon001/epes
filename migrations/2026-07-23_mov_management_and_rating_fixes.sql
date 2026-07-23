-- ============================================================================
-- Migration: MOV Management Improvements + Rating Fixes
-- Date: 2026-07-23
-- Applies schema changes required by the evaluation / MOV management updates.
-- Run on a fresh or existing EPES database. Safe to re-run: each statement is
-- guarded with existence checks where possible.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 1. task_progress: add actual_accomplishment column
--    Stores the faculty's free-text description of what was actually
--    accomplished for a target (entered in target_list.php submit modal
--    and editable until the target is verified).
-- ----------------------------------------------------------------------------
SET @dbname = DATABASE();
SET @tablename = 'task_progress';
SET @columnname = 'actual_accomplishment';
SET @preparedStatement = (
    SELECT IF(
        (
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @dbname
              AND TABLE_NAME   = @tablename
              AND COLUMN_NAME  = @columnname
        ) > 0,
        'SELECT 1',  -- column already exists, no-op
        'ALTER TABLE task_progress
            ADD COLUMN actual_accomplishment TEXT NOT NULL DEFAULT ''''
            AFTER mfo'
    )
);
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ----------------------------------------------------------------------------
-- 2. target_comments: new per-target evaluator comment storage
--    Separated from the legacy `comments` table (which holds overall
--    faculty feedback). One row per evaluator comment on a specific task.
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS target_comments (
    id            INT(11)      NOT NULL AUTO_INCREMENT,
    task_id       INT(11)      NOT NULL,
    faculty_id    INT(11)      NOT NULL,
    evaluator_id  INT(11)      NOT NULL,
    comment_text  TEXT,
    rating_period VARCHAR(100),
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_task_faculty (task_id, faculty_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------------------------------------------
-- Notes for deployment:
--   * The `mov_uploads` table already contains status / verified_by /
--     verified_date / remarks columns used by the verify/reject flow —
--     no schema change needed there.
--   * Rating fixes (COS designation fallback) were code-only
--     (includes/rating_functions.php, rating.php) and require no DDL.
-- ============================================================================
