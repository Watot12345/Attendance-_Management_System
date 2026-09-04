# Architecture Document
# Library Attendance Monitoring System (LAMS)

## 1. Tech Stack

| Layer | Technology | Version | Justification |
|-------|-----------|---------|---------------|
| **Server OS** | Linux (Ubuntu/Debian) | 22.04 LTS | Stable, widely supported |
| **Web Server** | Apache | 2.4+ | Native PHP integration, `.htaccess` for routing |
| **Language** | PHP | 8.2+ | User requirement; modern features (typed properties, match expressions, readonly classes) |
| **Database** | MySQL | 8.0+ | User requirement; JSON support for audit logs |
| **Frontend CSS** | Tailwind CSS | 3.4+ | User requirement; utility-first, rapid UI development |
| **Frontend JS** | Vanilla JavaScript | ES6+ | No framework needed; keeps stack minimal |
| **QR Scanning** | html5-qrcode | 2.3+ | Pure JS library, no backend dependency, works with webcam/phone camera |
| **Email** | PHPMailer | 6.9+ | Reliable SMTP/email sending, better than `mail()` |
| **Charts** | Chart.js | 4.4+ | Lightweight, canvas-based, no heavy dependencies |
| **CSV Export** | Native PHP `fputcsv()` | — | No library needed; native function sufficient |
| **Build Tool** | Tailwind CLI | 3.4+ | Single command to compile CSS; no Node.js runtime required in production |

### Why Not a PHP Framework?
**Requirement:** Native PHP only.
**Rationale:** The system is bounded in scope (single library, ~10 core features). A framework would add abstraction overhead without proportional benefit. Instead, we apply disciplined structure (MVC-like organization) within native PHP.

### Why No WebSockets/SSE?
**Requirement:** Email alerts only; no real-time push.
**Rationale:** The only "real-time" need is QR scan confirmation (local UI feedback). Email alerts are asynchronous by nature. Polling or simple page refresh is sufficient for dashboard updates.

### Why No Caching Layer (Redis/Memcached)?
**Rationale:** Expected load is moderate (single library, <10,000 users). MySQL query cache + proper indexing is sufficient. Add caching only if analytics queries degrade below NFR thresholds.

### Why No Docker?
**Rationale:** Single-instance deployment on LAMP. Docker adds complexity without benefit for this scale. Document manual deployment steps instead.

### Why No Queue System (RabbitMQ/Redis)?
**Rationale:** Email volume is low (<50/hour expected). PHPMailer with SMTP + a simple database-backed retry queue (`parent_alerts` table with `status='pending'`) is sufficient. Upgrade to a queue only if volume exceeds 100/hour sustained.

## 2. Project Structure

```
/lams-project/
├── .env                          # Environment variables (NOT in git)
├── .env.example                  # Template for .env
├── .gitignore                    # Git ignore rules
├── config.php                    # Central configuration loader
├── index.php                     # Entry point / router
├── tailwind.config.js            # Tailwind configuration
├── package.json                  # Dev dependencies (Tailwind CLI)
│
├── /assets                       # Public static assets
│   ├── /css
│   │   └── output.css            # Compiled Tailwind (generated)
│   ├── /js
│   │   ├── app.js                # Global utilities
│   │   ├── scanner.js            # QR scanner logic
│   │   ├── dashboard.js          # Dashboard charts/interactions
│   │   └── calendar.js           # Attendance calendar
│   └── /images
│       └── logo.png
│
├── /includes                     # Private PHP includes
│   ├── /core
│   │   ├── Database.php          # PDO wrapper + connection
│   │   ├── Router.php            # Simple URL router
│   │   ├── Auth.php              # Session authentication
│   │   ├── Validator.php         # Input validation utilities
│   │   └── Response.php          # JSON/API response helper
│   ├── /models
│   │   ├── User.php
│   │   ├── Student.php
│   │   ├── Teacher.php
│   │   ├── Attendance.php
│   │   ├── ExcuseSlip.php
│   │   ├── Alert.php
│   │   ├── Award.php
│   │   ├── Setting.php
│   │   └── AuditLog.php
│   ├── /helpers
│   │   ├── functions.php         # Global helper functions
│   │   ├── Mailer.php            # PHPMailer wrapper
│   │   └── Exporter.php          # CSV export generator
│   └── /views
│       ├── /partials
│       │   ├── header.php
│       │   ├── footer.php
│       │   ├── sidebar.php
│       │   └── flash.php         # Alert/notification messages
│       ├── /auth
│       │   ├── login.php
│       │   └── logout.php
│       ├── /dashboard
│       │   └── index.php
│       ├── /attendance
│       │   ├── scan.php          # QR scanner interface
│       │   ├── daily.php         # Daily attendance view
│       │   └── history.php       # Individual history
│       ├── /excuses
│       │   ├── submit.php
│       │   └── review.php
│       ├── /calendar
│       │   └── index.php
│       ├── /analytics
│       │   └── dashboard.php
│       ├── /awards
│       │   └── index.php
│       ├── /exports
│       │   └── index.php
│       ├── /settings
│       │   └── index.php
│       └── /users
│           ├── list.php
│           ├── create.php
│           └── edit.php
│
├── /api                          # AJAX/API endpoints
│   ├── /attendance
│   │   ├── scan.php              # Process QR scan
│   │   ├── mark.php              # Manual attendance entry
│   │   └── update.php            # Update record (exit time, status)
│   ├── /excuses
│   │   ├── submit.php
│   │   ├── review.php
│   │   └── list.php
│   ├── /alerts
│   │   ├── send.php              # Trigger pending alerts
│   │   └── history.php
│   ├── /analytics
│   │   ├── summary.php
│   │   ├── trends.php
│   │   └── occupancy.php
│   ├── /awards
│   │   ├── calculate.php
│   │   └── notify.php
│   ├── /exports
│   │   ├── attendance.php
│   │   └── tardies.php
│   └── /settings
│       └── update.php
│
├── /scripts                      # CLI / Cron scripts
│   ├── cron_absence.php          # Daily absence batch job
│   ├── cron_alerts.php           # Process pending email alerts
│   └── cron_cleanup.php          # Purge old alerts/logs
│
├── /uploads                      # User uploads (web server writable)
│   ├── /excuse-slips
│   ├── /avatars
│   └── /exports                  # Temporary export files
│
├── /logs                         # Application logs (web server writable)
│   ├── /errors
│   └── /audit
│
└── /docs                         # Project documentation
    ├── PRD.md
    ├── Data.md
    ├── Architecture.md
    ├── Security.md
    ├── Skills.md
    └── Agents.md
```

## 3. Data Flow

### 3.1 QR Scan Flow
```
[Browser Camera] → html5-qrcode reads QR string
    → POST /api/attendance/scan.php {qr_code, direction}
        → Router validates request
        → Auth middleware (optional: public endpoint for kiosk mode)
        → Student::findByQrCode(qr_code)
        → Attendance::createEntry(student_id, timestamp)
        → Setting::get('tardy_threshold_minutes')
        → Compute status (present / tardy)
        → If tardy: Alert::queueTardyAlert(student_id, parent_email)
        → JSON response {success, student_name, status, timestamp}
    → Browser displays confirmation overlay
```

### 3.2 Dashboard Analytics Flow
```
[Browser] → GET /api/analytics/summary.php?range=30
    → Auth middleware (role check: admin/staff/teacher)
    → Attendance::getSummary(start_date, end_date)
    → Database aggregation queries
    → JSON response {total_present, total_tardy, total_absent, trends[]}
    → Browser renders Chart.js charts
```

### 3.3 Excuse Slip Approval Flow
```
[Browser] → POST /api/excuses/review.php {slip_id, action, notes}
    → Auth middleware (role check: admin/staff)
    → ExcuseSlip::find(slip_id)
    → Validate transition (pending → approved/rejected)
    → If approved:
        → Attendance::excuseRange(student_id, date_from, date_to)
        → AuditLog::create('excuse.approve', ...)
    → JSON response {success, message}
    → Browser updates UI
```

### 3.4 Email Alert Flow (Async)
```
[Cron Job: cron_alerts.php every 5 minutes]
    → Alert::getPending(limit=50)
    → For each pending alert:
        → PHPMailer sends email via SMTP
        → On success: Alert::markSent(alert_id)
        → On failure: Alert::markFailed(alert_id, error_message)
    → Log batch results
```

### 3.5 Daily Absence Batch Flow
```
[Cron Job: cron_absence.php at library_close_time + 30 minutes]
    → Setting::get('library_close_time')
    → Attendance::findStudentsWithoutEntry(today)
    → For each missing student:
        → Attendance::createAbsence(student_id, today)
        → Alert::queueAbsentAlert(student_id, parent_email)
    → Log batch results
```

## 4. API Contracts

### 4.1 Standard Response Format
```json
{
  "success": true|false,
  "message": "Human-readable message",
  "data": { ... },
  "errors": { "field_name": "Error message" }
}
```

### 4.2 Key Endpoints

#### POST /api/attendance/scan.php
**Request:**
```json
{
  "qr_code": "STU-2024-7A-001-ABC123",
  "direction": "entry"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Attendance recorded",
  "data": {
    "student_name": "Juan Dela Cruz",
    "status": "present",
    "timestamp": "2024-09-04 08:05:23",
    "direction": "entry"
  }
}
```

**Failure Response (400/404):**
```json
{
  "success": false,
  "message": "Invalid QR code",
  "data": null
}
```

#### GET /api/analytics/summary.php
**Request:** `?range=30&grade=Grade+7&section=Section+A`

**Success Response (200):**
```json
{
  "success": true,
  "data": {
    "period": { "start": "2024-08-05", "end": "2024-09-04" },
    "totals": { "present": 450, "tardy": 23, "absent": 12, "excused": 5 },
    "by_grade": [
      { "grade": "Grade 7", "present": 150, "tardy": 8, "absent": 4 }
    ],
    "daily_trend": [
      { "date": "2024-09-01", "present": 45, "tardy": 2, "absent": 1 }
    ]
  }
}
```

#### POST /api/excuses/review.php
**Request:**
```json
{
  "slip_id": 42,
  "action": "approved",
  "notes": "Medical certificate verified"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Excuse slip approved. 3 attendance records updated.",
  "data": { "updated_count": 3 }
}
```

## 5. Authentication & Authorization

### 5.1 Authentication
- **Method:** PHP native sessions (`$_SESSION`)
- **Session Storage:** Filesystem (default PHP session handler)
- **Session Security:** Regenerate ID on login; 24-hour idle timeout; 8-hour absolute timeout
- **Password Storage:** `password_hash($password, PASSWORD_BCRYPT)`
- **Login Flow:**
  1. POST email + password
  2. Verify `password_verify()`
  3. Check `status = 'active'`
  4. Regenerate session ID
  5. Store `user_id`, `role`, `email` in session
  6. Update `last_login_at`

### 5.2 Authorization (Role-Based)

| Feature | Admin | Staff | Teacher | Parent | Student |
|---------|-------|-------|---------|--------|---------|
| QR Scan (self) | ✓ | ✓ | ✓ | — | ✓ |
| View Dashboard | ✓ | ✓ | ✓ | — | — |
| View All Attendance | ✓ | ✓ | ✓ | — | — |
| View Own Attendance | ✓ | ✓ | ✓ | ✓ | ✓ |
| View Child Attendance | — | — | — | ✓ | — |
| Manual Attendance Entry | ✓ | ✓ | — | — | — |
| Approve Excuse Slips | ✓ | ✓ | — | — | — |
| Submit Excuse Slips | ✓ | ✓ | ✓ | ✓ | — |
| Manage Users | ✓ | — | — | — | — |
| System Settings | ✓ | — | — | — | — |
| Export Data | ✓ | ✓ | — | — | — |
| View Analytics | ✓ | ✓ | ✓ | — | — |
| Run Award Tool | ✓ | ✓ | — | — | — |
| Delete Records | ✓ | — | — | — | — |

**Implementation:** Middleware function `requireRole(array $roles)` checks `$_SESSION['role']` against allowed roles. Returns 403 if unauthorized.

## 6. File Storage

| Type | Location | Max Size | Allowed Types | Naming |
|------|----------|----------|---------------|--------|
| Excuse Slip Attachments | `/uploads/excuse-slips/` | 5MB | PDF, JPG, PNG | `{slip_id}_{timestamp}.{ext}` |
| User Avatars | `/uploads/avatars/` | 2MB | JPG, PNG | `{user_id}_{timestamp}.{ext}` |
| CSV Exports | `/uploads/exports/` | N/A | CSV | `export_{type}_{timestamp}.csv` |
| Award Certificates | `/uploads/awards/` | N/A | PDF | `award_{award_id}_{timestamp}.pdf` |

**Security:**
- `.htaccess` in `/uploads/` denies direct access to all files.
- Files served via PHP proxy that checks authentication and authorization.
- Filename sanitized: alphanumeric + hyphen + underscore + dot only.

## 7. External Services

| Service | Purpose | Integration |
|---------|---------|-------------|
| SMTP Server | Email alerts to parents | PHPMailer via `.env` credentials |

**No other external services required.**

## 8. Deployment

### 8.1 Server Requirements
- Linux server (Ubuntu 22.04 LTS recommended)
- Apache 2.4+ with `mod_rewrite` enabled
- PHP 8.2+ with extensions: `pdo`, `pdo_mysql`, `mbstring`, `json`, `fileinfo`, `openssl`
- MySQL 8.0+
- Composer (for PHPMailer dependency)

### 8.2 Deployment Steps
1. Clone repository to `/var/www/lams/`
2. Copy `.env.example` to `.env`, fill in DB and SMTP credentials
3. Run `composer install` (installs PHPMailer)
4. Run `npm install` and `npx tailwindcss -i ./assets/css/input.css -o ./assets/css/output.css` (dev/build step)
5. Create MySQL database and run schema SQL
6. Set file permissions: `chmod 755 uploads/ logs/`; `chmod 644` for PHP files
7. Configure Apache virtual host with `DocumentRoot /var/www/lams/` and `AllowOverride All`
8. Enable and configure cron jobs (see `/scripts/`)
9. Set PHP timezone in `php.ini` or via `.env`

### 8.3 Cron Jobs
```bash
# Process pending email alerts every 5 minutes
*/5 * * * * /usr/bin/php /var/www/lams/scripts/cron_alerts.php >> /var/www/lams/logs/cron_alerts.log 2>&1

# Mark absences at 17:30 daily (adjust to library_close_time + 30 min)
30 17 * * * /usr/bin/php /var/www/lams/scripts/cron_absence.php >> /var/www/lams/logs/cron_absence.log 2>&1

# Cleanup old data weekly (Sundays at 2 AM)
0 2 * * 0 /usr/bin/php /var/www/lams/scripts/cron_cleanup.php >> /var/www/lams/logs/cron_cleanup.log 2>&1
```

## 9. Monitoring & Logging

### 9.1 Error Logging
- PHP errors: `logs/errors/error_YYYY-MM-DD.log`
- Log format: `[TIMESTAMP] [LEVEL] [FILE:LINE] Message`
- Levels: ERROR, WARNING, NOTICE
- Production: `display_errors = Off`; log to file only

### 9.2 Audit Logging
- All destructive actions logged to `audit_logs` table
- Admin record deletions require explicit confirmation and are logged with old_values/new_values

### 9.3 Performance Monitoring
- Slow query log enabled in MySQL (threshold: 2 seconds)
- Application logs API response times for endpoints > 3 seconds
- CSV export size limited to 10,000 rows to prevent memory exhaustion

## 10. Scalability Considerations (Future)

| Trigger | Solution |
|---------|----------|
| > 100 emails/hour | Implement Redis-backed queue or use AWS SES with batching |
| > 50 concurrent QR scans | Add read replicas for MySQL; cache student QR lookups in APCu |
| > 1M attendance records/year | Partition `attendance_records` by `record_date`; archive old data |
| Multi-library expansion | Add `libraries` table; scope all queries by `library_id` |
| Real-time dashboard | Add Server-Sent Events (SSE) for occupancy counter only |

**Current Decision:** None of these are implemented in MVP. Architecture supports them without rewrite.
