# EPES — System Architecture Document

**Document Version:** 2.0  
**Last Updated:** June 2026  
**System:** Employee Performance Evaluation System (EPES)  
**Institution:** Dr. Emilio B. Espinosa Sr. Memorial State College of Agriculture and Technology (DEBESMSCAT)  

---

## 1. TECHNOLOGY STACK

| Layer | Technology | Version | Purpose |
|-------|-----------|---------|---------|
| **Web Server** | Apache (XAMPP) | 8.x | HTTP request handling, URL rewriting, SSL termination |
| **Database** | MySQL / MariaDB | 10.x | Relational data storage, ACID transactions, 29 tables |
| **Backend** | PHP | 8.x | Server-side logic, database interaction, session management, PDF generation |
| **Frontend Framework** | AdminLTE 3 + Bootstrap 4 | 3.x / 4.x | Responsive UI, dashboard components, navigation, charts |
| **JavaScript** | jQuery + AJAX | 3.x | Client-side interactivity, async data exchange, DOM manipulation |
| **Charts** | Chart.js (AdminLTE bundled) | 2.x | Dashboard analytics, performance trend visualization |
| **PDF Generation** | TCPDF | 6.11 | IPCR/DPCR form PDF export (landscape A4) |
| **Email** | PHPMailer | 6.x | SMTP email for password reset, notifications, reminders |
| **Icons** | Font Awesome | 5.x / 6.x | UI iconography |
| **Notifications** | SweetAlert2 + Toastr | — | In-app toast alerts, session expiry warnings |
| **DataTables** | DataTables + Buttons | — | Sortable/searchable tables with export |
| **Version Control** | Git | — | Source code management, change tracking |
| **Backup** | Hermes Cron + mysqldump | — | Daily automated full database backup |
| **Dev Tools** | Composer, VS Code | — | Dependency management, code editing |

---

## 2. DIRECTORY STRUCTURE

```
C:\xampp\htdocs\epes\
├── index.php                  # Entry point / router (?page=xxx)
├── login.php                  # Authentication page
├── register.php               # Self-registration
├── reset_password.php         # Password reset (token-based)
├── verify.php                 # Email verification
├── first_login.php            # First-login setup (password change + privacy consent)
├── forgot_password.php        # Forgot password request
│
├── db_connect.php             # Database connection ($conn)
├── config.php                 # Configuration constants
├── .env                       # Environment variables (DB, SMTP)
├── .htaccess                  # Security headers, HTTPS redirect, file protection
│
├── admin_class.php            # Core backend logic (Action class)
│   ├── login()                # Authentication + remember-me
│   ├── save_rating()          # IPCR rating save (E/T/Q)
│   ├── save_comment()         # Feedback save
│   ├── save_status()          # Submission verification
│   ├── update_period()        # Rating period CRUD (IPCR+DP+OPCR)
│   ├── cascade_compute()      # IPCR→DP + IPCR→OPCR + intervention flags
│   └── ...                    # 30+ other methods
│
├── ajax.php                   # AJAX endpoint router (?action=xxx)
│
├── ipcr_generator.php         # IPCR form generator class (HTML + PDF via TCPDF)
├── dpcr_generator.php         # DPCR form generator class
├── notification_helper.php   # Notification creation/fetching functions
├── document_archive_helper.php # Document archiving functions
├── csrf_helper.php            # CSRF token generation/validation
├── session_check.php          # Session inactivity check
│
├── backup_run.php             # Standalone daily backup script (CLI)
├── backup_system.php          # Backup management class
├── notification_system.php    # Email notification class
├── help_system.php            # Help content initialization
│
├── header.php                 # HTML <head> + CSS includes
├── footer.php                 # JS includes + session timeout timer
├── topbar.php                 # Navbar (bell notifications, user menu, period display)
├── sidebar.php                # Navigation menu (role-conditional routes)
│
├── home.php                   # Dashboard (stats, charts, cascading display, intervention alerts)
├── faculty_list.php           # Faculty table (ratings, intervention badges, dept summary)
├── faculty_trends.php         # Performance trend charts (E/T/Q/Overall across periods)
├── rating.php                 # Faculty self-view: task list with E/T/Q ratings
├── rating_period.php          # Period management + cascade compute button
├── target_list.php            # Faculty task submission page
│
├── evaluation.php             # Evaluator: view/rate faculty submissions
├── employee_eval_status.php   # Evaluator: faculty evaluation status overview
├── recommendation.php         # Evaluator: COS renewal recommendations
├── rec_admin.php              # Admin: COS recommendation review
│
├── ipcr_view.php              # IPCR print preview + PDF download
├── dpcr_view.php              # DPCR print preview + PDF download
├── document_archive.php       # Archived IPCR/DPCR/OPCR document browser
│
├── feedback.php               # Two-way threaded feedback/communication
├── notifications.php          # Full notification inbox
├── help.php                   # Help & training portal (FAQ, glossary, guides)
├── data_migration.php         # CSV import wizard for historical IPCR data
│
├── manage_user.php            # User CRUD (faculty, evaluator, admin)
├── manage_task.php            # Task definition management
├── manage_department.php      # Department CRUD
├── manage_designation.php     # Designation CRUD
├── manage_academic_rank.php   # Academic rank management
├── manage_progress.php        # Task progress management
├── percentage_allocation.php  # Weight allocation management
├── function_categories.php    # Function category management
│
├── mov_management.php         # MOV upload/view (faculty)
├── upload_mov.php             # MOV file upload handler
├── upload_file.php            # Task file upload handler (with duplicate detection)
├── generate_mov_summary.php   # MOV summary generation
│
├── archives.php               # Faculty archives
├── status.php                 # Faculty status log
├── view_employee.php          # Employee detail view
├── view_evaluation.php        # Evaluation detail view
│
├── vendor/                    # Composer dependencies
│   ├── tecnickcom/tcpdf/      # PDF generation library
│   └── phpmailer/phpmailer/   # Email library
│
├── assets/                    # AdminLTE assets (CSS, JS, plugins, images)
├── uploads/                   # User-uploaded files
│   ├── mov/                   # MOV evidence files
│   ├── ipcr/                  # Generated IPCR PDFs
│   └── dpcr/                  # Generated DPCR PDFs
├── backups/                   # Database backup files (.sql, .htaccess protected)
├── database/                  # SQL migration scripts
├── migrations/                # Index optimization scripts
├── docs/                      # Documentation (ERD.md, DFD.md, DRP.md, ARCHITECTURE.md)
└── manuscript/                # Capstone project PDF
```

---

## 3. ROUTE / ROLE ACCESS MATRIX

29 sidebar routes mapped to 3 roles. ✓ = accessible, — = hidden.

| # | Route (page=) | File | Faculty (0) | Dean (1) | Admin (2) | Description |
|---|--------------|------|:-----------:|:--------:|:---------:|-------------|
| 1 | `target_list` | target_list.php | ✓ | — | — | Submit tasks, upload files, track progress |
| 2 | `mov_management` | mov_management.php | ✓ | — | — | Upload/view Means of Verification |
| 3 | `status` | status.php | ✓ | — | — | Submission status log |
| 4 | `rating` | rating.php | ✓ | — | — | View own IPCR ratings (E/T/Q breakdown) |
| 5 | `archives` | archives.php | ✓ | — | — | Archived submissions |
| 6 | `ipcr_view` | ipcr_view.php | ✓ | ✓ | ✓ | IPCR form — print preview + PDF download |
| 7 | `help` | help.php | ✓ | ✓ | ✓ | Help & training (FAQ, glossary, guides) |
| 8 | `faculty_list` | faculty_list.php | — | ✓ | ✓ | Faculty table with ratings, intervention badges |
| 9 | `employee_eval_status` | employee_eval_status.php | — | ✓ | — | Faculty evaluation status overview |
| 10 | `evaluation` | evaluation.php | — | ✓ | ✓ | View/rate faculty submissions |
| 11 | `recommendation` | recommendation.php | — | ✓ | — | COS renewal recommendations |
| 12 | `faculty_trends` | faculty_trends.php | — | ✓ | ✓ | Performance trend charts |
| 13 | `dpcr_view` | dpcr_view.php | — | ✓ | ✓ | DPCR form — print preview + PDF download |
| 14 | `document_archive` | document_archive.php | — | ✓ | ✓ | Archived IPCR/DPCR/OPCR document browser |
| 15 | `rec_admin` | rec_admin.php | — | — | ✓ | Admin COS recommendation review |
| 16 | `department` | department.php | — | — | ✓ | Department CRUD |
| 17 | `designation` | designation.php | — | — | ✓ | Designation CRUD |
| 18 | `academic_rank_list` | academic_rank_list.php | — | — | ✓ | Academic rank management |
| 19 | `function_categories` | function_categories.php | — | — | ✓ | Function category management |
| 20 | `percentage_allocation` | percentage_allocation.php | — | — | ✓ | Weight allocation per position/designation |
| 21 | `sample_evaluation` | sample_evaluation.php | — | — | ✓ | Sample evaluation reference |
| 22 | `rating_period` | rating_period.php | — | — | ✓ | Period settings + cascade compute |
| 23 | `data_migration` | data_migration.php | — | — | ✓ | CSV import for historical IPCR data |
| 24 | `new_employee` | new_employee.php | — | — | ✓ | Add new faculty |
| 25 | `employee_list` | employee_list.php | — | — | ✓ | Faculty list management |
| 26 | `new_evaluator` | new_evaluator.php | — | — | ✓ | Add new evaluator |
| 27 | `evaluator_list` | evaluator_list.php | — | — | ✓ | Evaluator list management |
| 28 | `new_admin` | new_admin.php | — | — | ✓ | Add new administrator |
| 29 | `admin_list` | admin_list.php | — | — | ✓ | Administrator list management |

### Additional AJAX-only endpoints (no sidebar link)

| Action | Purpose | Access |
|--------|---------|--------|
| `export_ipcr_pdf` | Download IPCR as PDF | Faculty, Dean, Admin |
| `export_dpcr_pdf` | Download DPCR as PDF | Dean, Admin |
| `cascade_compute` | Trigger IPCR→DP+OPCR computation | Admin |
| `update_period` | Save rating period (IPCR+DP+OPCR) | Admin |
| `logout` | Destroy session + audit log | All |
| `save_rating` | Save E/T/Q rating | Evaluator, Dean |
| `save_status` | Verify/reject submission | Evaluator, Dean |
| `save_comment` | Save feedback comment | All |

### Standalone pages (no sidebar, direct access)

| File | Purpose | Access |
|------|---------|--------|
| `login.php` | Authentication | Public |
| `register.php` | Self-registration | Public |
| `reset_password.php` | Password reset (token link) | Public |
| `verify.php` | Email verification (token link) | Public |
| `forgot_password.php` | Request password reset | Public |
| `first_login.php` | First-login setup (redirected) | All (first login only) |
| `notifications.php` | Full notification inbox | All (via bell dropdown) |
| `feedback.php` | Feedback/communication | All (via notification links) |

---

## 4. DATA FLOW: CASCADING RATINGS MODEL

```
                    ┌──────────────────┐
                    │   IPCR RATINGS   │
                    │  (individual     │
                    │   faculty E/T/Q) │
                    └────────┬─────────┘
                             │
              ┌──────────────┴──────────────┐
              │                             │
              ▼                             ▼
    ┌──────────────────┐          ┌──────────────────┐
    │  STEP 1: IPCR→DP │          │ STEP 2: IPCR→OPCR│
    │                  │          │                  │
    │ Per department:  │          │ All faculty:     │
    │ AVG(E,T,Q) for   │          │ AVG(E,T,Q) for   │
    │ faculty in dept  │          │ entire office    │
    │                  │          │                  │
    │ cascading_ratings│          │ cascading_ratings│
    │ level='DP'       │          │ level='OPCR'     │
    │ department_id=X  │          │ department_id=0  │
    └──────────────────┘          └──────────────────┘
              │                             │
              ▼                             ▼
    ┌──────────────────┐          ┌──────────────────┐
    │  DP DISPLAY      │          │ OPCR DISPLAY     │
    │  (home.php,      │          │ (home.php,       │
    │   dpcr_view.php) │          │  dpcr_view.php   │
    │                  │          │  as reference)    │
    └──────────────────┘          └──────────────────┘

    DP and OPCR are PEER aggregates — both sourced directly from IPCR.
    DP does NOT feed OPCR. They are computed independently.
```

---

## 5. AUTHENTICATION & SECURITY

| Feature | Implementation |
|---------|---------------|
| **Password Hashing** | bcrypt via `password_hash()` / `password_verify()` |
| **Session Management** | PHP sessions with `session_regenerate_id(true)` on login |
| **Session Timeout** | Server: 15 min inactivity → destroy + audit log. Client: 14 min SweetAlert2 warning → 15 min redirect |
| **Remember Me** | Persistent cookie (selector:validator), token rotation on each use, SHA-256 hashed validator, 30-day expiry |
| **CSRF Protection** | Token per form via `csrf_helper.php`, validated on 20+ AJAX actions |
| **Rate Limiting** | Failed login counter → account lockout after 5 attempts |
| **Account Activation** | Email verification token required before first login |
| **First-Login Setup** | Mandatory password change + Data Privacy Act (RA 10173) consent |
| **Role-Based Access** | Server-side guards: Admin (type=2) blocked from save_rating/save_status/save_comment |
| **SQL Injection Prevention** | Prepared statements with `bind_param()` for all user-input queries |
| **File Upload Security** | MIME type validation, `is_uploaded_file()` check, random filenames, 10MB limit |
| **Security Headers** | X-Content-Type-Options, X-XSS-Protection, X-Frame-Options, CSP, Referrer-Policy, Permissions-Policy |
| **Audit Trail** | All login attempts logged (success/failure, IP, user agent, session ID) — 1,147 records |
| **Backup Protection** | `.htaccess` denies web access to backup directory |

---

## 6. PERFORMANCE METRICS

| Page | Render Time | Queries | Target | Status |
|------|------------|---------|--------|--------|
| `home.php` (Dashboard) | ~150ms | ~15 | <2s | ✓ |
| `faculty_list.php` | ~128ms | ~12 | <2s | ✓ |
| `rating.php` | ~133ms | ~10 | <2s | ✓ |
| `rating_period.php` | <100ms | ~5 | <2s | ✓ |
| `ipcr_view.php` | <200ms | ~8 | <2s | ✓ |

**Database:** 29 tables, ~2,800 total rows, indexed on all frequent query patterns.

---

## 7. DEPLOYMENT ARCHITECTURE

```
┌─────────────────────────────────────────────────────────┐
│                   PRODUCTION (Cloud)                     │
│                                                         │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────────┐   │
│  │ Apache   │  │ PHP 8.x  │  │ MySQL / MariaDB      │   │
│  │ (SSL)    │──│ (FPM)    │──│ (epes_db, 29 tables) │   │
│  └──────────┘  └──────────┘  └──────────────────────┘   │
│       │                            │                    │
│       │ HTTPS :443                 │ Daily backup       │
│       ▼                            ▼                    │
│  ┌──────────┐              ┌──────────────┐             │
│  │ Browser  │              │ Backup Store │             │
│  │ (Chrome, │              │ (cloud +     │             │
│  │  Firefox,│              │  local)      │             │
│  │  Edge)   │              └──────────────┘             │
│  └──────────┘                                           │
│                                                         │
│  Current: XAMPP localhost (development)                 │
│  Target: Cloud-hosted LAMP stack with SSL               │
│  Cron: Hermes scheduler (daily backup at 02:00)         │
└─────────────────────────────────────────────────────────┘
```

---

## 8. MANUSCRIPT COMPLIANCE CHECKLIST

| Manuscript Requirement | Status | Evidence |
|----------------------|--------|----------|
| Digital encoding and submission of IPCR data | ✓ | `task_progress` + `mov_uploads` tables |
| Manual input of ratings per criterion | ✓ | `save_rating()` — E/T/Q per task |
| Automated overall rating computation | ✓ | `cascade_compute()` + `rating.php` weighted average |
| Real-time tracking of accomplishments | ✓ | `home.php` dashboard + `target_list.php` |
| Role-based access (faculty, deans, staff) | ✓ | 3-tier: Faculty(0), Dean(1), Admin(2) |
| Report generation (IPCR/OPCR) | ✓ | `ipcr_generator.php` + `dpcr_generator.php` (PDF) |
| Basic analytics for decision-making | ✓ | `faculty_trends.php` charts + `home.php` cascading display |
| Login and Registration | ✓ | `login.php`, `register.php`, `verify.php` |
| Faculty Dashboard and IPCR Tracking | ✓ | `home.php` + `rating.php` |
| Administrative Control Panel | ✓ | 15 admin-only routes |
| Evaluation Form Submission | ✓ | `upload_file.php` + `upload_mov.php` |
| Performance Rating Calculator | ✓ | Weighted E/T/Q with percentage allocation |
| Report Export and Print View | ✓ | IPCR/DPCR PDF + `@media print` CSS |
| System Load Speed <2s | ✓ | All pages benchmarked <200ms |
| Secure Login Protocol | ✓ | bcrypt, CSRF, session regeneration, audit trail |
| Mobile Responsiveness | ✓ | Bootstrap 4 + AdminLTE responsive |
| Daily Backup and Restore | ✓ | Hermes cron `96a0f55a177f` + `backup_run.php` |
| Concurrent User Support (30) | ✓ | Indexed queries, lightweight pages |
| Data Privacy Act (RA 10173) Compliance | ✓ | Privacy consent on first login, role-based access, audit trail |
| IPCR and SPMS Standards Alignment | ✓ | E/T/Q criteria, 5-point scale, adjectival ratings |
| User-Centered System Design | ✓ | AdminLTE interface, SweetAlert2, help portal |
| Data-Driven Reporting Capability | ✓ | Charts, dashboards, cascading summaries |
| Scalability and Integration | ✓ | Modular PHP, standard LAMP stack |
| Intellectual Property Feasibility | ✓ | Original cascading model, intervention flags, document archive |

---

## 9. VERSION HISTORY

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | Dec 2025 | Initial capstone manuscript — 8 entities, 6 processes |
| 2.0 | Jun 2026 | Full implementation — 29 tables, 10 processes, 29 routes, 3 roles |
