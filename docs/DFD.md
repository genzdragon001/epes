# EPES — Data Flow Diagrams (Updated)

**Document Version:** 2.0  
**Last Updated:** June 2026  
**Original Manuscript:** Figure 4 (Context Diagram DFD Level 0), Figure 5 (DFD Level 1)  

---

## CONTEXT DIAGRAM (DFD Level 0) — Updated

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                        EXTERNAL ENTITIES                                     │
│                                                                             │
│  ┌──────────┐    ┌──────────────────┐    ┌──────────┐    ┌──────────┐      │
│  │ FACULTY  │    │  PROGRAM HEAD /  │    │   DEAN   │    │  ADMIN   │      │
│  │ (type 0) │    │   SUPERVISOR     │    │ (type 1) │    │ (type 2) │      │
│  │          │    │    (type 0)      │    │          │    │          │      │
│  │ • Submit │    │ • Review MOVs    │    │ • View   │    │ • Manage │      │
│  │   tasks  │    │ • Rate E/T/Q    │    │   dash-  │    │   users  │      │
│  │ • Upload │    │ • Verify        │    │   board  │    │ • Config │      │
│  │   MOVs   │    │   submissions   │    │ • DPCR/  │    │   periods│      │
│  │ • View   │    │ • Provide       │    │   OPCR   │    │ • System │      │
│  │   IPCR   │    │   feedback      │    │ • Recom- │    │   settings│     │
│  │ • Track  │    │ • Generate      │    │   menda- │    │ • Backup │      │
│  │   status │    │   recommend-    │    │   tions  │    │ • Audit  │      │
│  │          │    │   ations        │    │ • Inter- │    │   logs   │      │
│  └────┬─────┘    └────────┬─────────┘    │   vention│    └────┬─────┘      │
│       │                   │              │   flags  │         │           │
│       │    Submissions,   │              └────┬─────┘         │           │
│       │    MOVs, IPCR     │   Ratings,         │               │           │
│       │    requests       │   Feedback,        │  Dashboards,  │  User CRUD,│
│       │                   │   Verifications    │  DPCR/OPCR,   │  Period    │
│       │                   │                   │  Reports      │  config,   │
│       │                   │                   │               │  Backups   │
│       │                   │                   │               │           │
│       ▼                   ▼                   ▼               ▼           │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                                                                     │   │
│  │         FACULTY PERFORMANCE DOCUMENTATION & EVALUATION SYSTEM        │   │
│  │                         (EPES — DEBESMSCAT)                          │   │
│  │                                                                     │   │
│  │  ┌──────────┐ ┌──────────┐ ┌───────────┐ ┌──────────┐ ┌─────────┐  │   │
│  │  │ Account  │ │ Report   │ │ Rating &  │ │ Document │ │Dashboard│  │   │
│  │  │ Mgmt     │ │ Submit   │ │ Evaluation│ │ Generate │ │& Monitor│  │   │
│  │  └──────────┘ └──────────┘ └───────────┘ └──────────┘ └─────────┘  │   │
│  │                                                                     │   │
│  │  ┌──────────┐ ┌──────────┐ ┌───────────┐ ┌──────────┐ ┌─────────┐  │   │
│  │  │Feedback &│ │Cascading │ │Intervention│ │Notification│ │ Backup │  │   │
│  │  │Comm      │ │Compute   │ │Tracking   │ │System    │ │ System │  │   │
│  │  └──────────┘ └──────────┘ └───────────┘ └──────────┘ └─────────┘  │   │
│  │                                                                     │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
│                                                                             │
│                         DATA STORES                                         │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐   │
│  │ Faculty DB   │  │ Evaluation   │  │ Performance  │  │ System       │   │
│  │ (employee_   │  │ Records      │  │ Document     │  │ Infrastructure│  │
│  │  list,       │  │ (ratings,    │  │ Archive      │  │ (audit_trail, │  │
│  │  evaluator_  │  │  cascading_  │  │ (performance │  │  backups,    │  │
│  │  list, users)│  │  ratings,    │  │  _documents) │  │  settings,   │  │
│  │              │  │  renewal_)   │  │              │  │  help_docs)  │  │
│  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘   │
└─────────────────────────────────────────────────────────────────────────────┘
```

### External Entities (4)

| Entity | Role | Data Flows IN | Data Flows OUT |
|--------|------|--------------|----------------|
| **Faculty** (login_type=0) | Primary user — submits work, views ratings | Submissions, MOV uploads, IPCR requests, feedback messages | Task list, submission status, IPCR forms, ratings, notifications |
| **Program Head / Supervisor** (evaluator type=0) | Evaluator — reviews and rates | Ratings (E/T/Q), verifications, feedback, recommendations | Pending submissions, faculty data, notification alerts |
| **Dean** (evaluator type=1) | College oversight — decisions, reports | Dashboard views, DPCR/OPCR requests, recommendation decisions, intervention review | Department summaries, cascading data, intervention flags, notifications |
| **Administrator** (login_type=2) | System manager — view-only | User CRUD, period config, system settings, backup triggers, data migration | All system data (read-only), audit logs, backup files |

### Core Processes (10)

| # | Process | Manuscript Process | Status |
|---|---------|-------------------|--------|
| 1 | **Account Management** | Process 1 (Faculty Account Management) | ENHANCED — 3 user tables, password reset, remember-me, first-login setup, privacy consent |
| 2 | **Report Submission & Management** | Process 2 | ENHANCED — task_progress + mov_uploads, duplicate detection, file validation |
| 3 | **Rating & Evaluation** | Process 3 (Performance Rating & Evaluation) | ENHANCED — E/T/Q criteria, percentage allocation weighting, renewal recommendations |
| 4 | **Document Generation** | Process 4 (Performance Documentation) | ENHANCED — IPCR + DPCR PDF generation, print preview, auto-archive |
| 5 | **Dashboard & Monitoring** | Process 5 | ENHANCED — real-time charts, cascading display, intervention alerts, department summaries |
| 6 | **Feedback & Communication** | Process 6 | **NEW** — threaded comments, notifications, two-way messaging |
| 7 | **Cascading Computation** | (not in original DFD) | **NEW** — IPCR→DP + IPCR→OPCR aggregation, auto-cascade |
| 8 | **Intervention Tracking** | (not in original DFD) | **NEW** — 3-consecutive-low detection, flag acknowledgment |
| 9 | **Notification System** | (not in original DFD) | **NEW** — in-app bell, submission alerts, verification notices, email reminders |
| 10 | **Backup System** | (not in original DFD) | **NEW** — daily automated mysqldump, 30-day retention, restore capability |

---

## DATA FLOW DIAGRAM (DFD Level 1) — Updated

### Process 1: Account Management
```
FACULTY ──(login credentials)──▶ 1.1 Login/Auth ──▶ login_audit_trail
                                  │
                                  ├──(valid)──▶ 1.2 Session Setup ──▶ $_SESSION
                                  │                │
                                  │                ├──(first login?)──▶ 1.3 First-Login Setup
                                  │                │                   (password change + privacy consent)
                                  │                │
                                  │                └──(remember me?)──▶ remember_tokens
                                  │
                                  └──(invalid)──▶ 1.4 Failed Login ──▶ login_audit_trail
                                                   (rate limiting, account lockout)

ADMIN ──(CRUD operations)──▶ 1.5 User Management ──▶ employee_list / evaluator_list / users
                             (create, edit, activate, block, reset password)
```

### Process 2: Report Submission & Management
```
FACULTY ──(select task, upload file)──▶ 2.1 File Upload ──▶ task_progress
                                         │                  (status: For Verification)
                                         ├──(duplicate?)──▶ 2.2 Duplicate Check
                                         │                  (warn → overwrite or cancel)
                                         │
                                         ├──(MOV upload)──▶ 2.3 MOV Upload ──▶ mov_uploads
                                         │                  └──▶ mov_summary (aggregate)
                                         │
                                         └──(success)──▶ 2.4 Notify Evaluator ──▶ notifications
                                                          (evaluator + deans alerted)

FACULTY ──(view status)──▶ 2.5 Status Tracking ──▶ task_progress
                            (Pending / For Verification / Verified)
```

### Process 3: Rating & Evaluation
```
PROGRAM HEAD ──(review submission)──▶ 3.1 View Submission ──▶ task_progress + mov_uploads
                                      │
                                      ├──(rate)──▶ 3.2 Save Rating ──▶ ratings
                                      │             (E/T/Q scores, period_type='IPCR')
                                      │
                                      ├──(verify)──▶ 3.3 Verify Submission ──▶ task_progress
                                      │               (progress='Verified', date_verified)
                                      │               └──▶ 3.4 Notify Faculty ──▶ notifications
                                      │
                                      ├──(comment)──▶ 3.5 Leave Feedback ──▶ comments
                                      │
                                      └──(COS faculty?)──▶ 3.6 Renewal Recommendation ──▶ renewal_recommendations
                                                            (system-generated + dean review)
```

### Process 4: Document Generation
```
FACULTY ──(request IPCR)──▶ 4.1 Generate IPCR ──▶ ratings + task_progress + percentage_allocation
                            │                    (weighted E/T/Q → overall + adjectival)
                            ├──(print)──▶ 4.2 Print Preview (browser @media print)
                            ├──(PDF)───▶ 4.3 PDF Export (TCPDF, landscape A4)
                            │            └──▶ 4.4 Auto-Archive ──▶ performance_documents
                            │
DEAN ──(request DPCR)──▶ 4.5 Generate DPCR ──▶ cascading_ratings (level='DP')
                         │                     (per-department + faculty breakdown + OPCR reference)
                         └──(PDF)──▶ 4.6 PDF Export ──▶ performance_documents
```

### Process 5: Dashboard & Monitoring
```
ALL USERS ──(view dashboard)──▶ 5.1 Home Dashboard
                                 │  ├── Submission stats (total, verified, pending)
                                 │  ├── Department charts (bar: employees, verified, pending)
                                 │  ├── Rating period overview (IPCR/DP/OPCR cards)
                                 │  ├── Cascading performance table (DP per dept + OPCR)
                                 │  └── Intervention alert (unacknowledged flags)

DEAN/ADMIN ──(view faculty list)──▶ 5.2 Faculty List
                                    │  ├── Per-faculty: tasks, verified, IPCR rating, status
                                    │  ├── Intervention badges (⚠ flagged faculty)
                                    │  └── Department summary (coverage progress bars)

DEAN/ADMIN ──(view trends)──▶ 5.3 Performance Trends
                               └── Line/bar charts: E/T/Q/Overall across periods
```

### Process 6: Feedback & Communication
```
FACULTY ──(send message)──▶ 6.1 New Comment ──▶ comments
                            │                  └──▶ 6.2 Notify Evaluator ──▶ notifications
                            │
PROGRAM HEAD ──(reply)──▶ 6.3 Reply to Comment ──▶ comments (reply_to chain)
                          │                        └──▶ 6.4 Notify Faculty ──▶ notifications
                          │
ALL ──(view thread)──▶ 6.5 View Feedback ──▶ comments (threaded, per period)
                       └──▶ 6.6 Mark as Read ──▶ comments.is_read = 1
```

### Process 7: Cascading Computation
```
ADMIN ──(trigger compute)──▶ 7.1 Cascade Compute
                              │
                              ├── STEP 1: IPCR→DP ──▶ ratings (per department)
                              │   └──▶ cascading_ratings (level='DP', per dept)
                              │
                              ├── STEP 2: IPCR→OPCR ──▶ ratings (all faculty)
                              │   └──▶ cascading_ratings (level='OPCR', office-wide)
                              │
                              └── STEP 3: Intervention Check ──▶ ratings (3 consecutive ≤2.60)
                                  └──▶ intervention_flags
```

### Process 8: Intervention Tracking
```
SYSTEM ──(auto on cascade)──▶ 8.1 Detect Low Ratings ──▶ ratings (3 consecutive ≤2.60)
                              └──▶ intervention_flags (flag_type='3_CONSECUTIVE_LOW')

DEAN/ADMIN ──(review flags)──▶ 8.2 View Intervention Flags ──▶ intervention_flags
                               │  (faculty list badges, dashboard alerts)
                               │
                               └──(acknowledge)──▶ 8.3 Acknowledge Flag ──▶ intervention_flags
                                                    (acknowledged=1, acknowledged_by, notes)
```

### Process 9: Notification System
```
SYSTEM ──(auto on events)──▶ 9.1 Create Notification ──▶ notifications
                              (submission → evaluator, verification → faculty, feedback → both)

ALL USERS ──(bell icon)──▶ 9.2 View Notifications ──▶ notifications (dropdown: 8 recent)
                           │                          └──▶ 9.3 Full Inbox (paginated, mark read)
                           │
                           └──(mark read)──▶ 9.4 Mark as Read ──▶ notifications.is_read = 1
```

### Process 10: Backup System
```
CRON ──(daily 02:00)──▶ 10.1 Automated Backup ──▶ system_backups
                        │  (full mysqldump → .sql file, 30-day rotation)
                        │
ADMIN ──(manual)──▶ 10.2 Manual Backup ──▶ system_backups
                    │
                    └──(restore)──▶ 10.3 Restore Backup ──▶ MySQL import
```

---

## Data Stores (4)

| Store | Tables | Content |
|-------|--------|---------|
| **Faculty Database** | employee_list, evaluator_list, users, department_list, designation_list, position_list, academic_rank_list | User profiles, credentials, organizational hierarchy |
| **Evaluation Records** | ratings, task_progress, task_list, mov_uploads, mov_summary, cascading_ratings, renewal_recommendations, intervention_flags, comments, percentage_allocation, function_categories, function_list, efficiency_attendance, target_deadlines, target_exemptions | All performance data — submissions, ratings, aggregates, recommendations |
| **Performance Document Archive** | performance_documents | Generated IPCR/DPCR/OPCR PDFs with metadata |
| **System Infrastructure** | system_settings, login_audit_trail, remember_tokens, notifications, system_backups, help_docs | Configuration, security logs, notifications, backups, help content |

---

## Manuscript DFD vs Actual: Changes

| Manuscript Element | Actual | Change |
|-------------------|--------|--------|
| External Entities: 3 (Faculty, Dean, Program Head) | 4 (+ Administrator) | Admin added as separate entity |
| Processes: 6 | 10 | +Cascading, +Intervention, +Notification, +Backup |
| Data Stores: 3 | 4 | +System Infrastructure store |
| Data Flows: 5 named flows | 30+ detailed flows | Fully specified with table destinations |
