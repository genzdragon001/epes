# EPES System Testing Guide
## Comprehensive Testing Procedures for Version 2.0.0

---

## 1. PRE-DEPLOYMENT CHECKLIST

### 1.1 Environment Setup
- [ ] PHP 7.4+ installed
- [ ] MySQL 8.0+ installed
- [ ] Apache/Nginx configured
- [ ] PHPMailer installed via Composer
- [ ] `.env` file created with correct credentials
- [ ] File permissions set correctly (755 for directories, 644 for files)

### 1.2 Database Setup
```bash
# Import complete database schema
mysql -u root -p epes_db < database/epes_db_complete.sql

# Verify tables created
mysql -u root -p epes_db -e "SHOW TABLES;"
```

### 1.3 Configuration Verification
- [ ] Database connection working
- [ ] SMTP credentials configured
- [ ] SYSTEM_URL set correctly
- [ ] Upload directory writable
- [ ] Session configuration correct

---

## 2. SECURITY TESTING

### 2.1 SQL Injection Testing
**Test Cases:**
```
Login Form:
- Email: ' OR '1'='1
- Password: ' OR '1'='1

Search Fields:
- Input: '; DROP TABLE users; --
- Input: 1' AND '1'='1
```
**Expected Result:** All inputs sanitized, no SQL execution

### 2.2 XSS Testing
**Test Cases:**
```
Name Fields: <script>alert('XSS')</script>
Comments: <img src=x onerror=alert('XSS')>
File Upload: Upload file with script in metadata
```
**Expected Result:** Scripts escaped, not executed

### 2.3 CSRF Testing
**Test:**
1. Login to system
2. Copy CSRF token from form
3. Logout
4. Try to submit form with old token
**Expected Result:** Request rejected

### 2.4 Authentication Testing
**Test Cases:**
- [ ] Login with valid credentials
- [ ] Login with invalid credentials (5 times)
- [ ] Verify account lockout after 5 attempts
- [ ] Password reset functionality
- [ ] Session timeout after 15 minutes
- [ ] Session regeneration on login

### 2.5 Authorization Testing
**Test:**
1. Login as Faculty
2. Try to access Admin pages via URL
3. Try to access Evaluator pages
**Expected Result:** Access denied, redirect to appropriate page

---

## 3. FUNCTIONAL TESTING

### 3.1 User Management

#### Admin Functions
| Test ID | Test Case | Expected Result | Status |
|---------|-----------|-----------------|--------|
| UM-01 | Create new user | User created, email sent | ☐ |
| UM-02 | Update user | User data updated | ☐ |
| UM-03 | Delete user | User deleted, audit logged | ☐ |
| UM-04 | Duplicate email | Error: Email exists | ☐ |
| UM-05 | Password reset | Reset email sent | ☐ |

#### Faculty Management
| Test ID | Test Case | Expected Result | Status |
|---------|-----------|-----------------|--------|
| FM-01 | Add faculty | Faculty created | ☐ |
| FM-02 | Assign evaluator | Evaluator linked | ☐ |
| FM-03 | Upload avatar | Image saved | ☐ |
| FM-04 | Delete faculty | Cascade delete related data | ☐ |

### 3.2 Task Management

| Test ID | Test Case | Expected Result | Status |
|---------|-----------|-----------------|--------|
| TM-01 | Create task | Task saved | ☐ |
| TM-02 | Assign to position | Task visible to faculty | ☐ |
| TM-03 | Set exemption | Exempted faculty cannot see | ☐ |
| TM-04 | Delete task | Task removed | ☐ |

### 3.3 Progress Tracking

| Test ID | Test Case | Expected Result | Status |
|---------|-----------|-----------------|--------|
| PT-01 | Submit task | Progress saved, status=For Verification | ☐ |
| PT-02 | Upload file | File saved, path stored | ☐ |
| PT-03 | Verify task | Status=Verified, date recorded | ☐ |
| PT-04 | Return for revision | Status=Pending | ☐ |
| PT-05 | Delete submission | File and record deleted | ☐ |

### 3.4 Rating System

| Test ID | Test Case | Expected Result | Status |
|---------|-----------|-----------------|--------|
| RT-01 | Submit rating | Rating saved | ☐ |
| RT-02 | Update rating | Rating updated | ☐ |
| RT-03 | Verify without rating | Error: Rating required | ☐ |
| RT-04 | Add comment | Comment saved | ☐ |

### 3.5 IPCR Generation

| Test ID | Test Case | Expected Result | Status |
|---------|-----------|-----------------|--------|
| IP-01 | Generate IPCR | Form generated with data | ☐ |
| IP-02 | Export PDF | PDF downloaded | ☐ |
| IP-03 | View historical | Past periods shown | ☐ |
| IP-04 | Print IPCR | Print-friendly format | ☐ |

### 3.6 OPCR Consolidation

| Test ID | Test Case | Expected Result | Status |
|---------|-----------|-----------------|--------|
| OP-01 | Generate OPCR | Department summary created | ☐ |
| OP-02 | View faculty list | All faculty shown | ☐ |
| OP-03 | Calculate averages | Correct computations | ☐ |

### 3.7 Recommendations

| Test ID | Test Case | Expected Result | Status |
|---------|-----------|-----------------|--------|
| RC-01 | Auto-generate | Recommendation created | ☐ |
| RC-02 | System reason | Reason text generated | ☐ |
| RC-03 | Dean decision | Decision saved | ☐ |
| RC-04 | Update status | Status changed | ☐ |

---

## 4. INTEGRATION TESTING

### 4.1 Email System
**Test:**
- [ ] Password reset email sent
- [ ] Account verification email sent
- [ ] Deadline reminder sent
- [ ] Verification pending notification sent

**Verify:**
- Email content correct
- Links working
- Sender name shows correctly

### 4.2 File Upload
**Test:**
```
Allowed Files:
- PDF (✓)
- DOC/DOCX (✓)
- JPG/PNG (✓)
- XLS/XLSX (✓)

Disallowed Files:
- EXE (✗)
- PHP (✗)
- ZIP (✗)
```

**File Size Test:**
- 5MB file: Should upload
- 15MB file: Should reject

### 4.3 Database Backup
**Test:**
1. Create backup
2. Verify file created
3. Check file size > 0
4. Test restore function
5. Verify data intact

---

## 5. PERFORMANCE TESTING

### 5.1 Load Testing
**Using Apache Bench:**
```bash
# Test login page
ab -n 1000 -c 10 http://localhost/epes/login.php

# Test dashboard
ab -n 500 -c 5 http://localhost/epes/index.php?page=home
```

**Expected Results:**
- Response time < 2 seconds
- No errors under load
- Memory usage stable

### 5.2 Database Performance
```sql
-- Test query performance
EXPLAIN SELECT * FROM ratings WHERE employee_id = 1;
-- Should use index

-- Check slow queries
SELECT * FROM information_schema.processlist 
WHERE TIME > 2;
```

### 5.3 Page Load Times
| Page | Target Load Time | Actual | Status |
|------|-----------------|--------|--------|
| Login | < 1s | ___ | ☐ |
| Dashboard | < 2s | ___ | ☐ |
| Faculty List | < 2s | ___ | ☐ |
| Rating Page | < 3s | ___ | ☐ |
| IPCR Report | < 3s | ___ | ☐ |

---

## 6. USER ACCEPTANCE TESTING

### 6.1 Faculty User Tests
**Scenario:** Complete semester workflow
1. [ ] Login successfully
2. [ ] View assigned tasks
3. [ ] Submit task with file
4. [ ] Check progress status
5. [ ] View ratings received
6. [ ] Generate IPCR
7. [ ] Update profile

### 6.2 Evaluator User Tests
**Scenario:** Evaluate faculty submissions
1. [ ] Login successfully
2. [ ] View faculty list
3. [ ] Review submissions
4. [ ] Provide ratings
5. [ ] Verify tasks
6. [ ] Generate recommendations
7. [ ] View department analytics

### 6.3 Admin User Tests
**Scenario:** Manage system
1. [ ] Login successfully
2. [ ] Add new faculty
3. [ ] Configure rating period
4. [ ] Manage departments
5. [ ] Generate OPCR
6. [ ] View system logs
7. [ ] Create backup

---

## 7. BROWSER COMPATIBILITY

| Browser | Version | Login | Dashboard | Rating | IPCR | Status |
|---------|---------|-------|-----------|--------|------|--------|
| Chrome | Latest | ☐ | ☐ | ☐ | ☐ | |
| Firefox | Latest | ☐ | ☐ | ☐ | ☐ | |
| Safari | Latest | ☐ | ☐ | ☐ | ☐ | |
| Edge | Latest | ☐ | ☐ | ☐ | ☐ | |
| Mobile Chrome | Latest | ☐ | ☐ | ☐ | ☐ | |

---

## 8. MOBILE RESPONSIVENESS

| Device Type | Screen Size | Layout | Navigation | Forms | Status |
|-------------|-------------|--------|------------|-------|--------|
| iPhone 12 | 390px | ☐ | ☐ | ☐ | |
| iPhone 12 Pro Max | 428px | ☐ | ☐ | ☐ | |
| iPad | 768px | ☐ | ☐ | ☐ | |
| Android Phone | 360px | ☐ | ☐ | ☐ | |
| Android Tablet | 1024px | ☐ | ☐ | ☐ | |

---

## 9. SECURITY AUDIT

### 9.1 Password Security
- [ ] Passwords hashed with bcrypt
- [ ] Minimum 8 characters enforced
- [ ] MD5 legacy passwords detected and upgraded

### 9.2 Session Security
- [ ] Session ID regeneration on login
- [ ] Inactivity timeout (15 min)
- [ ] Secure session cookies
- [ ] CSRF tokens on all forms

### 9.3 Data Protection
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] File upload validation
- [ ] Directory traversal prevention

### 9.4 Audit Trail
- [ ] Login attempts logged
- [ ] Critical actions logged
- [ ] File operations logged
- [ ] Data changes logged

---

## 10. DEPLOYMENT TESTING

### 10.1 Production Checklist
- [ ] Debug mode disabled
- [ ] Error logging enabled
- [ ] HTTPS configured
- [ ] Database credentials updated
- [ ] SMTP credentials updated
- [ ] Backup schedule configured
- [ ] Monitoring enabled

### 10.2 Rollback Plan
1. Keep previous version backup
2. Database backup before deployment
3. Test rollback procedure
4. Document rollback steps

---

## 11. POST-DEPLOYMENT VERIFICATION

### 11.1 Smoke Tests
```
1. Homepage loads: ✓
2. Login works: ✓
3. Dashboard displays: ✓
4. Can submit task: ✓
5. Can verify task: ✓
6. Email sending works: ✓
7. Backup creation works: ✓
```

### 11.2 Monitoring Setup
- [ ] Error tracking enabled
- [ ] Performance monitoring active
- [ ] Database slow query log on
- [ ] Server resources monitored
- [ ] Backup verification scheduled

---

## 12. TEST REPORT TEMPLATE

### Test Summary
- **Total Test Cases:** ___
- **Passed:** ___
- **Failed:** ___
- **Blocked:** ___
- **Pass Rate:** ___%

### Critical Issues
| ID | Description | Severity | Status |
|----|-------------|----------|--------|
| | | | |

### Recommendations
1. 
2. 
3. 

**Tested By:** _______________
**Date:** _______________
**Approved By:** _______________

---

## 13. AUTOMATED TESTING SCRIPTS

### Database Connection Test
```php
<?php
require_once 'config.php';
require_once 'db_connect.php';

try {
    $db = getDB();
    $result = $db->query("SELECT 1");
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
}
```

### Email Configuration Test
```php
<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USER;
    $mail->Password = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    echo "✓ SMTP configuration valid\n";
} catch (Exception $e) {
    echo "✗ SMTP configuration error: " . $e->getMessage() . "\n";
}
```

---

**END OF TESTING GUIDE**
