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
  ADD COLUMN IF NOT EXISTS deadline_note VARCHAR(255) NULL;

-- 3. Add timeliness/efficiency rating fields to task_progress (for auto-scoring)
ALTER TABLE task_progress 
  ADD COLUMN IF NOT EXISTS timeliness_rating DECIMAL(3,2) NULL,
  ADD COLUMN IF NOT EXISTS efficiency_rating DECIMAL(3,2) NULL;

-- 4. Clean up redundant core/instructions rows in percentage_allocation
-- (These are now computed as TER + Instruction, not stored separately)
DELETE FROM percentage_allocation 
  WHERE category = 'core' AND sub_category = 'instructions';

-- 5. Verify row counts after migration
SELECT 'Migration complete. Check row counts:' AS status;
SELECT COUNT(*) AS evaluator_list_rows FROM evaluator_list;
SELECT COUNT(*) AS task_list_rows FROM task_list;
SELECT COUNT(*) AS task_progress_rows FROM task_progress;
SELECT COUNT(*) AS percentage_allocation_rows FROM percentage_allocation;
