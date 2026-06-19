# EPES — Entity-Relationship Diagram (Updated)

**Document Version:** 2.0  
**Last Updated:** June 2026  
**Original Manuscript ERD:** Figure 6 (8 entities)  
**Actual Implementation:** 29 tables  

---

## Manuscript-to-Actual Entity Mapping

| Manuscript Entity | Actual Table(s) | Relationship |
|-------------------|-----------------|--------------|
| **Faculty** | `employee_list` | Expanded — added position_id, designation_id, evaluator_id, login/auth fields |
| **Faculty_Account** | Merged into `employee_list`, `evaluator_list`, `users` | Denormalized — auth fields (email, password, reset_token) live in each user table |
| **Report** | `task_list` + `task_progress` + `mov_uploads` | Split into 3 tables — task definitions, progress tracking, evidence files |
| **Rating** | `ratings` | Enhanced — E/T/Q criteria instead of single Score; period_type column |
| **Performance_Document** | `performance_documents` | NEW — stores generated IPCR/DPCR/OPCR PDFs with metadata |
| **Dean** | `evaluator_list` (type=1) | Merged with Program Head table; distinguished by `type` column |
| **Program Head / Supervisor** | `evaluator_list` (type=0) | Merged with Dean table; distinguished by `type` column |
| **Evaluation_Record** | `ratings` + `cascading_ratings` + `renewal_recommendations` | Split across 3 tables for different aggregation levels |

---

## Complete Table Inventory (29 tables)

### CORE ENTITIES (User/Role)

| # | Table | Rows | Description |
|---|-------|------|-------------|
| 1 | `employee_list` | 29 | Faculty members — personal info, department, position, designation, evaluator assignment, login credentials |
| 2 | `evaluator_list` | 6 | Evaluators (type=0: Program Head, type=1: Dean) — login, department, assigned faculty |
| 3 | `users` | 0 | Administrators (login_type=2) — system-wide view-only access |

### ORGANIZATIONAL STRUCTURE

| # | Table | Rows | Description |
|---|-------|------|-------------|
| 4 | `department_list` | 5 | Academic departments (BSCS, BPA, AB Econ, BS Entrep, etc.) |
| 5 | `designation_list` | 5 | Faculty designations (Department Head, Director, etc.) |
| 6 | `position_list` | 19 | Academic ranks (Instructor I–III, Assistant Professor, Associate Professor, Professor) |
| 7 | `academic_rank_list` | — | Academic rank reference (linked from task_list) |

### PERFORMANCE EVALUATION CORE

| # | Table | Rows | Description |
|---|-------|------|-------------|
| 8 | `task_list` | 30 | Task definitions — MFO, category, sub_category, success indicators, targets, E/T/Q applicability |
| 9 | `task_progress` | 679 | Faculty task submissions — file uploads, progress status (For Verification/Verified), dates |
| 10 | `ratings` | 579 | Individual IPCR ratings — E/T/Q scores per task per faculty per period |
| 11 | `mov_uploads` | 13 | Means of Verification — uploaded evidence files (PDF, DOCX, images) |
| 12 | `mov_summary` | 13 | MOV aggregation — counts per faculty per period (total, verified, pending, rejected) |
| 13 | `comments` | 8 | Evaluator feedback — threaded comments per faculty per period |

### CASCADING & AGGREGATION

| # | Table | Rows | Description |
|---|-------|------|-------------|
| 14 | `rating_period` | 9 | Evaluation periods — semester, year, date range, period_type (IPCR/DP/OPCR), auto_cascade flag |
| 15 | `cascading_ratings` | 34 | DP/OPCR computed aggregates — IPCR→DP (per department) and IPCR→OPCR (office-wide) |
| 16 | `intervention_flags` | 1 | Faculty flagged for 3 consecutive low IPCR ratings (≤2.60) |

### FUNCTION TAXONOMY & WEIGHTS

| # | Table | Rows | Description |
|---|-------|------|-------------|
| 17 | `function_categories` | 5 | Performance categories (Strategic, Core, Support) with sub-categories |
| 18 | `function_list` | 16 | Function definitions linked to categories and designations |
| 19 | `percentage_allocation` | 277 | Weight distribution — per position + designation + category + sub_category |

### SUPPORTING TABLES

| # | Table | Rows | Description |
|---|-------|------|-------------|
| 20 | `renewal_recommendations` | 3 | COS faculty contract renewal — system-generated + dean decision workflow |
| 21 | `efficiency_attendance` | 7 | Efficiency/attendance tracking for MOV submissions |
| 22 | `target_deadlines` | 15 | Per-target submission deadlines |
| 23 | `target_exemptions` | 8 | Task exemptions per position/designation |

### SYSTEM INFRASTRUCTURE

| # | Table | Rows | Description |
|---|-------|------|-------------|
| 24 | `system_settings` | 0 | Institution name, email, contact info |
| 25 | `login_audit_trail` | 1,147 | Security log — all login attempts (success/failure), IP, user agent, session ID |
| 26 | `remember_tokens` | 3 | Persistent login — selector:validator token pairs with expiry |
| 27 | `notifications` | 0 | In-app notification inbox — per-user, typed (Info/Warning/Success/Danger), read tracking |
| 28 | `performance_documents` | 1 | Generated document archive — IPCR/DPCR/OPCR PDFs with metadata |
| 29 | `system_backups` | 1 | Backup history log — filename, size, status, timestamps |
| 30 | `help_docs` | 0 | Help system articles — categorized, ordered, active/inactive |

---

## Key Relationships

```
employee_list (Faculty)
    ├── 1:N → task_progress (submissions)
    ├── 1:N → ratings (IPCR scores)
    ├── 1:N → mov_uploads (evidence files)
    ├── 1:N → comments (feedback)
    ├── 1:N → intervention_flags (low performance alerts)
    ├── 1:N → renewal_recommendations (COS renewal)
    ├── N:1 → department_list (department)
    ├── N:1 → position_list (academic rank)
    ├── N:1 → designation_list (designation)
    └── N:1 → evaluator_list (assigned evaluator)

evaluator_list (Program Head / Dean)
    ├── type=0: Program Head — rates assigned faculty
    ├── type=1: Dean — oversees all departments
    ├── 1:N → ratings (as rater)
    └── 1:N → comments (as feedback author)

task_list (Performance Targets)
    ├── 1:N → task_progress (faculty submissions)
    ├── 1:N → ratings (scored tasks)
    ├── 1:N → target_deadlines (submission deadlines)
    └── 1:N → target_exemptions (exempted positions)

rating_period (Evaluation Cycles)
    ├── period_type='IPCR' → source for ratings
    ├── period_type='DP' → target for department cascading
    ├── period_type='OPCR' → target for office-wide cascading
    └── 1:N → cascading_ratings (computed aggregates)

cascading_ratings (Computed Aggregates)
    ├── level='DP' → per-department averages (IPCR→DP)
    ├── level='OPCR' → office-wide average (IPCR→OPCR)
    ├── N:1 → rating_period (source_period_id = IPCR period)
    └── N:1 → rating_period (target_period_id = DP or OPCR period)
```

---

## Manuscript ERD vs Actual: Gap Summary

| Manuscript Entity | Status | Notes |
|-------------------|--------|-------|
| Faculty | PRESENT | `employee_list` — richer than manuscript (20 columns vs 5) |
| Faculty_Account | PRESENT | Merged into user tables (denormalized for simplicity) |
| Report | PRESENT | Split into `task_list` + `task_progress` + `mov_uploads` |
| Rating | PRESENT | `ratings` — E/T/Q instead of single Score |
| Performance_Document | **ADDED** | `performance_documents` — not in original ERD |
| Dean | PRESENT | `evaluator_list` type=1 |
| Program Head | PRESENT | `evaluator_list` type=0 |
| Evaluation_Record | PRESENT | Split across `ratings` + `cascading_ratings` + `renewal_recommendations` |

**Enhancements beyond manuscript ERD:** 22 additional tables for cascading, intervention tracking, notifications, document archiving, security logging, MOV management, percentage allocation, function taxonomy, and system infrastructure.
