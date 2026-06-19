-- Backfill task_list.deadline from sample Excel IPCR sheets
SET SQL_SAFE_UPDATES = 0;
UPDATE task_list SET deadline = '2025-06-15', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%DTR%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2026-01-15', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%quarterly accomplishment report%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2026-05-25', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%Test Questions%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2026-01-12', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%Syllabi Submission%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2026-01-14', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%Course Outline Distribution%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2026-05-30', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%returning of outputs%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2026-06-16', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%Grade Submission%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2025-12-19', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%College Wide Activities%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2026-03-31', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%SALN%' AND (deadline IS NULL OR deadline = '');
UPDATE task_list SET deadline = '2026-03-31', deadline_note = 'Backfilled from IPCR Excel sample' WHERE success_indicators LIKE '%PDS%' AND (deadline IS NULL OR deadline = '');
SET SQL_SAFE_UPDATES = 1;
