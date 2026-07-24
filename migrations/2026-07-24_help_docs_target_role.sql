-- ============================================================================
-- Migration: help_docs role-based filtering
-- Date: 2026-07-24
-- Adds a target_role column to help_docs so the in-app Guides page can filter
-- help articles per role (faculty / evaluator / admin / all) instead of showing
-- every article to everyone.
-- Safe to re-run: the ADD COLUMN is guarded by an existence check.
-- ============================================================================

SET @dbname = DATABASE();
SET @tablename = 'help_docs';
SET @columnname = 'target_role';
SET @preparedStatement = (
    SELECT IF(
        (
            SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = @dbname
              AND TABLE_NAME   = @tablename
              AND COLUMN_NAME  = @columnname
        ) > 0,
        'SELECT 1',  -- column already exists, no-op
        'ALTER TABLE help_docs
            ADD COLUMN target_role VARCHAR(20) NOT NULL DEFAULT ''all''
            AFTER content'
    )
);
PREPARE stmt FROM @preparedStatement;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- Backfill existing rows. Existing seed rows are categorized so they can be
-- assigned a role; any uncategorized row stays 'all'.
-- ----------------------------------------------------------------------------
UPDATE help_docs SET target_role = 'faculty'   WHERE target_role = 'all' AND category IN ('Faculty Training');
UPDATE help_docs SET target_role = 'evaluator' WHERE target_role = 'all' AND category IN ('Evaluator Training');
UPDATE help_docs SET target_role = 'admin'     WHERE target_role = 'all' AND category IN ('Admin Training');
-- Rows with other categories (Getting Started, Reports, Troubleshooting, Glossary, ...)
-- remain 'all' and are visible to every role.
