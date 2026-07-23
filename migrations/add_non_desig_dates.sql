-- =====================================================
-- EPES Migration: add Non-Designated / COS rating window
-- =====================================================
-- The Rating Period admin page, admin_class.php save logic, and
-- faculty_list.php already read/write non_desig_start_date /
-- non_desig_end_date, but the columns were never added to the
-- rating_period table. Add them so the Non-Designated / COS
-- date-range feature works and the "Undefined array key" warning
-- on rating_period.php line 160 is resolved.
--
-- Run order: apply this BEFORE pushing the code that references it.

ALTER TABLE `rating_period`
    ADD COLUMN `non_desig_start_date` date DEFAULT NULL
        AFTER `end_date`;

ALTER TABLE `rating_period`
    ADD COLUMN `non_desig_end_date` date DEFAULT NULL
        AFTER `non_desig_start_date`;

-- Backfill sensible defaults for any existing active period so the
-- UI shows meaningful values immediately (NULL stays allowed too).
UPDATE `rating_period`
SET non_desig_start_date = end_date,
    non_desig_end_date   = DATE_ADD(end_date, INTERVAL 30 DAY)
WHERE (non_desig_start_date IS NULL OR non_desig_end_date IS NULL)
  AND end_date IS NOT NULL;
