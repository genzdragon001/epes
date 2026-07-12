-- Migration: align COS faculty designation_id with percentage_allocation mapping
-- Date: 2026-07-12
-- Reason: All COS faculty (position_id=19) have their percentage_allocation rows
--         keyed to designation_id=3 (Faculty). Five COS faculty were stored with
--         designation_id=0, causing empty allocation lookups -> blank rating/print pages.
--         This sets them to designation_id=3 to match the working COS faculty.
--
-- Safe to re-run: UPDATE only touches the exact 5 rows where
-- position_id=19 AND designation_id=0. Idempotent (re-running changes nothing).
-- No table/column changes. Code fixes from this session are NOT schema-dependent.

UPDATE `employee_list`
SET `designation_id` = 3
WHERE `position_id` = 19
  AND `designation_id` = 0;

-- Optional verification (read-only, safe to leave in):
-- SELECT id, lastname, position_id, designation_id
-- FROM employee_list WHERE position_id = 19 ORDER BY id;
