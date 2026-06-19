# EPES — Disaster Recovery Plan (DRP)

**Document Version:** 1.0  
**Last Updated:** June 2026  
**System:** Employee Performance Evaluation System (EPES)  
**Institution:** DEBESMSCAT  

---

## 1. PURPOSE

In the case of a significant disruption — natural disaster, cyberattack, hardware failure, or data corruption — this plan ensures the quick restoration of system operations and data integrity. It safeguards faculty performance records and institutional evaluation workflows.

---

## 2. KEY PERSONNEL

| Role | Name/Title | Responsibility |
|------|-----------|----------------|
| **DRP Coordinator** | Project Manager / System Administrator | Overall coordinator; activates DRP; stakeholder communication |
| **Technical Lead** | Lead Developer | System restoration; code redeployment; database recovery |
| **IT Support** | Institutional IT Staff | Server access; backup retrieval; network restoration |
| **Communications Lead** | Dean's Office Representative | Stakeholder notification; rollback announcements |

**Escalation Path:** IT Support → Technical Lead → DRP Coordinator → Dean

---

## 3. RECOVERY OBJECTIVES

| Metric | Target | Rationale |
|--------|--------|-----------|
| **RTO** (Recovery Time Objective) | **48 hours** | Maximum acceptable downtime from disruption start to system operational |
| **RPO** (Recovery Point Objective) | **24 hours** | Maximum acceptable data loss based on daily backup frequency |

---

## 4. BACKUP STRATEGY

| Parameter | Value |
|-----------|-------|
| **Frequency** | Daily incremental (automated); weekly full (manual verification) |
| **Time** | 2:00 AM daily (Asia/Manila) |
| **Location** | `C:\xampp\htdocs\epes\backups\` (local) + cloud replication (recommended) |
| **Type** | Full MySQL dump (structure + data, all 25+ tables) |
| **Format** | `.sql` plaintext, UTF-8 encoded |
| **Retention** | 30-day rolling cycle; minimum 10 most recent always kept |
| **Protection** | `.htaccess` denies web access; directory permissions 0755 |
| **Automation** | Hermes cron job `96a0f55a177f` (daily at 02:00) |
| **Verification** | Weekly spot-check: restore latest backup to test database, verify row counts |

### Backup Contents
- All database tables (employee_list, evaluator_list, users, ratings, task_progress, task_list, cascading_ratings, rating_period, intervention_flags, comments, notifications, performance_documents, mov_uploads, mov_summary, renewal_recommendations, percentage_allocation, function_categories, function_list, department_list, designation_list, position_list, academic_rank_list, system_settings, login_audit_trail, remember_tokens, system_backups, help_docs, target_deadlines, target_exemptions, efficiency_attendance)
- Source code is version-controlled via Git (`C:\xampp\htdocs\epes\.git`)

---

## 5. RECOVERY PROCEDURE

### Step 1: Assess Disruption
1. Determine nature and scope (hardware failure, data corruption, cyber incident, natural disaster)
2. Identify affected components (database, web server, file storage)
3. Log incident time and initial findings

### Step 2: Activate DRP
1. DRP Coordinator notifies key personnel via email/phone
2. Communications Lead notifies stakeholders: "EPES is temporarily unavailable. Restoration in progress. Manual submission process activated if needed."
3. IT Support prepares recovery environment

### Step 3: Restore from Backup
1. Retrieve latest backup from `C:\xampp\htdocs\epes\backups\`
2. Verify backup file integrity (check file size, SQL syntax)
3. Restore database:
   ```bash
   /c/xampp/mysql/bin/mysql.exe -u root epes_db < backups/epes_backup_YYYY-MM-DD_HH-MM-SS.sql
   ```
4. Verify restoration:
   ```sql
   SELECT COUNT(*) FROM employee_list;
   SELECT COUNT(*) FROM ratings;
   SELECT COUNT(*) FROM task_progress;
   ```
   Compare against pre-incident baselines

### Step 4: Reconfigure Environment
1. Verify XAMPP services running (Apache, MySQL)
2. Check PHP version and extensions
3. Restore `.env` configuration if needed
4. Verify file permissions on `uploads/`, `backups/`

### Step 5: Test System Modules
1. Login page accessible
2. Dashboard loads with data
3. Faculty can view targets
4. Evaluator can view submissions
5. IPCR/DPCR generation works
6. Notifications functional
7. File upload/download works

### Step 6: Resume Operations
1. Communications Lead notifies stakeholders: "EPES restored and operational."
2. Document incident in post-recovery report
3. Update deployment checklist with lessons learned

---

## 6. ROLLBACK PLAN (Deployment Failure)

If deployment of new code causes unresolved issues:

1. **Immediate Suspension:** Temporarily disable system access; notify stakeholders via email
2. **Revert to Manual:** Re-enable previous documentation workflow (spreadsheets, email)
3. **Data Restoration:** Restore database from pre-deployment backup
4. **Code Rollback:** `git revert` to last stable commit; redeploy
5. **Issue Resolution:** Diagnose root cause; apply fixes; retest
6. **Post-Rollback Review:** Document incident; update deployment checklist; add preventive measures

---

## 7. TESTING SCHEDULE

| Test Type | Frequency | Scope |
|-----------|-----------|-------|
| **Backup Verification** | Weekly | Restore latest backup to test DB; verify row counts |
| **DRP Simulation (Quarterly)** | Every 3 months | Mock disruption; execute Steps 1-6; measure RTO |
| **Full Recovery Test (Annual)** | Once per year | End-to-end restoration; full system validation |

### Quarterly DRP Simulation Checklist
- [ ] Backup file accessible and intact
- [ ] Database restores successfully
- [ ] All tables present with correct row counts
- [ ] Login functional
- [ ] Core workflows tested (submit → verify → rate → generate IPCR)
- [ ] RTO measured and documented
- [ ] Issues logged for improvement

---

## 8. CONTACT INFORMATION

| Resource | Detail |
|----------|--------|
| **System URL** | (institutional domain — configured at deployment) |
| **Database** | MySQL/MariaDB via XAMPP; `localhost`, database `epes_db` |
| **Backup Location** | `C:\xampp\htdocs\epes\backups\` |
| **Source Code** | `C:\xampp\htdocs\epes\` (Git repository) |
| **PHP Binary** | `C:\xampp\php\php.exe` |
| **MySQL Binary** | `C:\xampp\mysql\bin\mysql.exe` |
| **Backup Script** | `C:\xampp\htdocs\epes\backup_run.php` |
| **Cron Job ID** | `96a0f55a177f` (Hermes scheduler) |

---

## 9. DISASTER SCENARIOS & RESPONSE MATRIX

| Scenario | Likelihood | Impact | Response |
|----------|-----------|--------|----------|
| Database corruption | Low | High | Restore latest backup; investigate cause |
| Server hardware failure | Low | High | Provision new server; restore backup; redeploy code |
| Accidental data deletion | Medium | Medium | Restore from backup; implement stricter access controls |
| Cyberattack / breach | Low | High | Disable system; restore clean backup; security audit; notify affected users per RA 10173 |
| Natural disaster (flood, fire) | Low | High | Activate offsite backup; provision cloud-hosted recovery environment |
| XAMPP service failure | Medium | Low | Restart Apache/MySQL services; verify configuration |

---

## 10. POST-RECOVERY REVIEW TEMPLATE

After any DRP activation, complete this review:

```
INCIDENT REPORT
Date: _______________
Time of Disruption: _______________
Time of Restoration: _______________
RTO Achieved: _______________ (target: 48 hours)
RPO Achieved: _______________ (target: 24 hours)

Root Cause: _______________________________________________
Affected Components: ______________________________________
Recovery Steps Taken: _____________________________________
Data Loss (if any): _______________________________________
Lessons Learned: __________________________________________
Preventive Measures Added: ________________________________
```

---

*This DRP is reviewed and updated annually or after any significant system change.*
