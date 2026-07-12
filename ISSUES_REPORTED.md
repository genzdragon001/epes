# EPES — Reported Issues Log

Reported by faculty/admin during testing. Logged 2026-07-12. Not yet fixed unless marked otherwise.

---

## 1. Rating computation errors (FIXED 2026-07-12)
Several users report incorrect rating computation. Reporters are all position 1 Department Heads:
- **Rbello** (Faculty): wrong computation?
- **Jromero**: Wrong Rating Computation
- **Aguilar**: Wrong Final Rating Computation

**Two layered root causes found (both in rating.php):**
1. **Over-all pooling bug**: for non-COS, the over-all rating used `$inst_val = simple average of ALL instruction-section tasks (TER + Instruction pooled)` and weighted it by 70%, which double-weighted TER and DIVERGED from the section rating printed above (which uses TER50%+Instr50%). Exposed when TER and Instruction averages differed — e.g. Faculty 8 (Romero) showed 4.93 (OUTSTANDING) while the displayed section said ~0.7.
2. **Divisor mismatch in `calcInstructionRating`**: the Instruction sub-average was divided by the FULL expected task count (incl. TER + unsubmitted tasks) instead of the submitted count — understating the rating for faculty with few verified tasks, unlike every other section (`calcAverage`/`calcResearchAverage`/`calcExtensionAverage` which divide by submitted count).

**Fix:** (a) over-all now reuses `$inst_val = floatval($inst_rating['instruction_rating'])` for ALL positions (same as COS path); (b) `calcInstructionRating` now divides Instruction average by the submitted instruction-task count.
**Verified** via the page's own functions on live data:
- Faculty 8: 4.93 → 2.94 (SATISFACTORY) — now internally consistent (display == over-all).
- Faculty 14: 4.50 → 4.50 (no change, was already consistent).
- Faculty 15: 4.32 → 4.06 (now matches displayed section).
- COS Faculty 23: 4.63 (unchanged — no regression).
Code-only, no schema change. NOTE: Faculty 8's 2.94 reflects a genuine missing TER submission; their rating will rise once TER is submitted/verified.

---

## 2. MOV Management — only 3 targets/items shown (FIXED 2026-07-12)
MOV Management page showed only 3 targets for COS faculty (position_id 19).
- **Mhermoso**, **Llaurio**, **Nlalaguna**, **JROmano** — all COS.

**Root cause:** singular/plural key mismatch. `percentage_allocation` stores sub_category `instruction`; code checked `core_instructions` (plural) → instructions category never matched. For COS (research/extension disabled), only `support` survived → 3 of 10 targets shown.

**Fix:** added `core_instruction` → `core_instructions` normalization (same as archives.php) in `mov_management.php`, `admin_class.php`, `ipcr_generator.php`. Verified COS target count 3 → 10. Code-only, no schema change.

---

## 3. Rating type shows both Q and T instead of one (INVESTIGATED — no code bug)
- **Mhermoso**: Rating — some targets display both Q and T Rating, while it should only be Q OR T (not both).

**Investigation:** evaluator modal saves one field at a time (`data-field="efficiency"/"timeliness"/"quality"`). `task_list` shows ZERO tasks with both `quality='Applicable' AND timeliness='Applicable'` — each task has exactly one Applicable dimension. The "both Q and T" display is not reproducible; likely past evaluator data-entry behavior (double-clicked both dimensions on a Q-or-T task). No code fix required; evaluator discipline is the remediation.

---

## 4. No Rating Display / Print Preview (FIXED 2026-07-12)
- **Kvibas** (id 28): NO Rating Display or Print Preview
- **Rlotivio** (id 32): NO Rating Display or Print Preview

**Root cause:** COS faculty (position_id 19) allocation rows in `percentage_allocation` are keyed to `designation_id=3` only. Five COS faculty had `designation_id=0`, so rating.php's allocation lookup (`designation_id IS NULL OR =0`) returned ZERO rows → all categories hidden → "No verified ratings found" despite having verified ratings. Also silently broke MOV Management + IPCR for them.
Affected (all pos 19, had verified ratings): Punay(21,6), Duyag(26,4), Vibas(28,5), Jordan(31,3), Lotivio(32,10).

**Fix (data, per user):** `UPDATE employee_list SET designation_id=3 WHERE id IN (21,26,28,31,32)`. Rollback: they were all designation_id=0 before. Verified all 5 now render full rating table (25KB, 46 rows, no error markers) matching working COS Hermoso(23).

---

## 5. Dashboard "Awaiting Review" count mismatch (FIXED 2026-07-12)
- **Jromero**: Dashboard task did not match — "1 Awaiting Review" shown but there is none.

**Root cause:** Evaluator/home_content.php else-branch (dept-level counts) did not exclude the evaluator's own submissions. Row 188 was Romero's own task in "For Verification" → inflated count to 1 ("maski wara naman na"). Dean branch already excluded self with `e.id != $eval_id`; else-branch did not.

**Fix:** Added `AND e.id != $eval_id` to all 3 dept-level count queries. Verified: for_verif for Jromero = 1 → 0. Code-only, no schema change.

---

## 6. Check Evaluation — missing view buttons (INVESTIGATED — data gap, not code bug)
- **ADMIN**: No "view" button #3 & #4 for Mhermoso in Check Evaluation function.

**Investigation:** evaluation.php line 183-194 renders the View button only when `$has_submission && !empty($row['file_path']) && !empty($row['file_type'])`. Task 3 has an uploaded file → button visible. Task 4 (`$file_path` empty) has no file uploaded yet → button correctly hidden. Mhermoso simply hasn't uploaded the required DOCX for task 4. Code is correct; admin should ask Mhermoso to upload.

---

## 7. COS Recommendation — display count limits (INVESTIGATED — no code cap, data gap)
- **ADMIN**: Only 5 CS (contract of service?) display in COS Recommendation.

**Investigation:** rec_admin.php has NO LIMIT — it renders every `renewal_recommendations` row. Total table rows today: 5. Nine COS faculty (Romano, Escorel, Punay, Gabrito, Dalanon, Duyag, Macadat, Vibas, Sabaulan) have NO `renewal_recommendations` row at all → nothing to display. Batch generation (`get_renewal_recommendations` in admin_class.php:1767) simply hasn't been run for those faculty. The page is not capped; the data pipeline needs a bulk-run for the remaining 9 COS faculty.

---

## 8. Remove Department Head Panel (LOW — feature request)
- **Dcorlet**: Remove Department Head Panel.

**Action:** Product decision — confirm scope before removing. Identify what "Department Head Panel" refers to (evaluator dashboard section or a sidebar item) and remove per request.

---

## No comment / no issue
- **Rramiso** — (no comment)
- **DJ Castillo** — (no comment)

---

# EPES — Reported Issues Log

Logged 2026-07-12. Status is fixed, investigated-no-code-fix, or awaiting action.

---

## Priority summary
1. Rating computation (Rbello, Jromero, Aguilar) — FIXED 2026-07-12
2. MOV Management "only 3 items" (Mhermoso, Llaurio, Nlalaguna, JROmano) — FIXED 2026-07-12
3. No Rating Display / Print Preview (Kvibas, Rlotivio + 3 silent) — FIXED 2026-07-12
4. Dashboard "Awaiting Review" count (Jromero) — FIXED 2026-07-12
5. Rating type Q+T both (Mhermoso) — investigated, no code bug
6. COS Recommendation cap 5 — investigated, data gap (9 faculty need batch-run)
7. Check Evaluation missing view buttons — investigated, Mhermoso missing file upload
## 8. Remove Department Head Panel for Dcorlet (FIXED 2026-07-12)
- **Dcorlet (Dennis Corlet, employee id 16)**: Director (TAEx, designation_id=6) — should NOT appear in / have access to the Department Head / evaluator panel.

**Root cause:** admin_class.php login (~line 393) auto-elevates any faculty whose designation name contains "director" to `$_SESSION['is_evaluator']=true, evaluator_role='director'`, granting evaluator sidebar + Department Head panel + recommendation access. Corlet hit this branch despite not being an evaluator and not being in `evaluator_list`.

**Fix:** added a `$non_evaluator_directors = [16]` guard right after the `if ($login == 0)` block opens. For uid 16 the code now forces `is_evaluator=false / evaluator_role=''` and skips the designation-based elevation entirely. Corlet's Director designation (id 6) is untouched — only the auto-elevation is suppressed. Fully reversible: remove id 16 from the array to revert. No other Director exists in the data today, so this is surgical.

**Verified:** `php -l admin_class.php` clean; all four `is_evaluator = true` assignments now live inside the `else` branch, so uid 16 cannot reach them.
