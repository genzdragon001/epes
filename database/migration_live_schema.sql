-- ============================================================
-- EPES Live Migration Script
-- Run this ONCE on your Hostinger MySQL database via phpMyAdmin
-- or the hPanel MySQL Manager.
-- 
-- SAFE: uses IF NOT EXISTS / IF EXISTS so it won't break
-- if some changes are already applied.
-- ============================================================

-- 1. Add designation_id to evaluator_list (for VP / Strategic Plan restriction)
ALTER TABLE evaluator_list 
  ADD COLUMN IF NOT EXISTS designation_id INT DEFAULT 0 AFTER type;

-- Set existing Dean (Rowelyn Ramiso) as VP if the row exists
UPDATE evaluator_list SET designation_id = 4 
  WHERE email = 'rmramiso@debesmscat.edu.ph' AND designation_id = 0;

-- 2. Add deadline fields to task_list (for IPCR Excel alignment)
ALTER TABLE task_list 
  ADD COLUMN IF NOT EXISTS deadline DATE NULL,
  ADD COLUMN IF NOT EXISTS deadline_note VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS status TINYINT DEFAULT 0 AFTER is_active;

-- 3. Add timeliness/efficiency rating fields to task_progress (for auto-scoring)
ALTER TABLE task_progress 
  ADD COLUMN IF NOT EXISTS timeliness_rating DECIMAL(3,2) NULL,
  ADD COLUMN IF NOT EXISTS efficiency_rating DECIMAL(3,2) NULL;

-- 4. Clean up redundant core/instructions rows in percentage_allocation
-- (These are now computed as TER + Instruction, not stored separately)
DELETE FROM percentage_allocation 
  WHERE category = 'core' AND sub_category = 'instructions';

-- 5. Add target_role column to help_docs (for role-based training visibility)
ALTER TABLE help_docs 
  ADD COLUMN IF NOT EXISTS target_role VARCHAR(20) DEFAULT 'all' AFTER category;

-- 6. Rating period schema upgrade: one row per semester, designated vs non-designated dates
-- Add non_desig_start_date and non_desig_end_date for COS + no-designation faculty
ALTER TABLE rating_period 
  ADD COLUMN IF NOT EXISTS non_desig_start_date DATE NULL AFTER end_date,
  ADD COLUMN IF NOT EXISTS non_desig_end_date DATE NULL AFTER non_desig_start_date;

-- Drop old cos_end_date if it exists (replaced by non_desig_end_date)
ALTER TABLE rating_period 
  DROP COLUMN IF EXISTS cos_end_date;

-- Merge duplicate IPCR/DP/OPCR rows: keep the IPCR row, delete DP/OPCR duplicates
-- (Back up first if you have cascading_ratings data referencing those IDs)
DELETE rp FROM rating_period rp
  INNER JOIN rating_period rp2 ON rp.semester = rp2.semester AND rp.year = rp2.year
  WHERE rp.period_type IN ('DP', 'OPCR') AND rp2.period_type = 'IPCR' AND rp.id != rp2.id;

-- Drop the period_type column (no longer needed — one row per semester)
ALTER TABLE rating_period 
  DROP COLUMN IF EXISTS period_type;

-- Set is_active = 1 on the remaining row(s)
UPDATE rating_period SET is_active = 1 WHERE is_active = 0 LIMIT 1;

-- 7. Verify row counts after migration
SELECT 'Migration complete. Check row counts:' AS status;
SELECT COUNT(*) AS evaluator_list_rows FROM evaluator_list;
SELECT COUNT(*) AS task_list_rows FROM task_list;
SELECT COUNT(*) AS task_progress_rows FROM task_progress;
SELECT COUNT(*) AS percentage_allocation_rows FROM percentage_allocation;
