# Phase 5 — Final Foundation Report
# Library Attendance Monitoring System (LAMS)

---

## 1. Project Summary

**Name:** Library Attendance Monitoring System (LAMS)

**Purpose:** A web-based system for tracking library visits by students and teachers using QR code scanning. It automates tardiness/absence detection, manages digital excuse slips, alerts parents via email, and provides analytics with export capabilities.

**Scope:** Single-library deployment. Web app only. No mobile app, no RFID hardware, no SMS alerts, no payment system, no external school system integration.

**Users:** Students (scan QR, view history), Teachers/Staff (mark attendance, submit excuse slips, view analytics), Librarian/Admin (full system management), Parents (receive email alerts, view child's calendar).

---

## 2. Selected Tech Stack

| Layer | Technology | Justification |
|-------|-----------|---------------|
| Backend | Native PHP 8.2+ | User requirement; sufficient for scope |
| Database | MySQL 8.0+ | User requirement; JSON support for audit logs |
| Frontend CSS | Tailwind CSS 3.4+ | User requirement; rapid UI development |
| Frontend JS | Vanilla ES6+ | No framework needed; keeps stack minimal |
| QR Scanning | html5-qrcode | Pure JS, works with any camera |
| Email | PHPMailer | Reliable SMTP; better than native `mail()` |
| Charts | Chart.js | Lightweight, canvas-based |
| Web Server | Apache 2.4 | Native PHP integration, `.htaccess` routing |

**Intentionally NOT included:** Docker, Redis, queues, WebSockets/SSE, load balancers, microservices, Kubernetes, PHP frameworks (Laravel/Symfony), JS frameworks (React/Vue).

---

## 3. Architecture Overview

### Structure
Simple MVC-like organization within native PHP:
- `index.php` as front controller / router
- `/includes/models/` for data access (PDO prepared statements)
- `/includes/views/` for HTML templates
- `/includes/core/` for shared utilities (Auth, Database, Router, Validator, Response)
- `/api/` for AJAX endpoints returning JSON
- `/scripts/` for CLI cron jobs (absence batch, alert processing, cleanup)

### Key Patterns
- **Auth:** PHP native sessions with regeneration, 8-hour absolute timeout, 24-hour idle timeout
- **Routing:** Simple URL-to-file mapping via Apache `mod_rewrite`
- **API:** REST-like conventions with standardized JSON responses
- **Email:** Database-backed queue (`parent_alerts` table) processed by cron every 5 minutes
- **File Storage:** Outside web root, served via PHP proxy with auth checks

### Data Flow Highlights
1. **QR Scan:** Browser camera → html5-qrcode → POST `/api/attendance/scan.php` → validate → record attendance → compute status → queue alert if tardy → JSON response → UI confirmation
2. **Daily Absence:** Cron at closing time + 30 min → find students without entry → create absence records → queue parent alerts
3. **Excuse Slip:** Submit → pending queue → librarian approves → update affected attendance to "excused" → audit log
4. **Analytics:** AJAX request → aggregated MySQL queries → Chart.js rendering

---

## 4. Data Model Overview

### Core Entities (9 tables)

| Entity | Purpose | Key Relationships |
|--------|---------|-------------------|
| **users** | Authentication base for all roles | PK: user_id (INT UNSIGNED) |
| **students** | Student-specific data (grade, section, QR code) | 1:1 with users; FK to parent user |
| **teachers** | Teacher-specific data (employee ID, department) | 1:1 with users |
| **attendance_records** | Central attendance log (entry/exit, status) | Many:1 with users |
| **excuse_slips** | Digital excuse submissions | Many:1 with student user; 1:1 with reviewer |
| **parent_alerts** | Email alert queue and history | Many:1 with student user |
| **perfect_attendance_awards** | Award records | Many:1 with student user |
| **library_settings** | Key-value configuration (open time, thresholds) | Singleton table |
| **audit_logs** | Administrative action tracking | Many:1 with actor user |

### Key Conventions
- Primary keys: `INT UNSIGNED AUTO_INCREMENT`
- Foreign keys: `{table}_id` naming
- Soft deletes: `deleted_at DATETIME NULL`
- Timestamps: `created_at`, `updated_at` on all tables
- QR codes: System-generated unique strings (not derived from PII)

### Data Lifecycle
- Attendance records: Retain 3 years, then archive
- Parent alerts: Retain 1 year, then purge
- Audit logs: Retain 2 years, then purge
- Users: Soft delete only; never hard delete

---

## 5. Security Model

### Authentication
- Session-based with PHP native sessions
- Passwords: bcrypt with cost 12
- Session cookies: HttpOnly, Secure, SameSite=Strict
- Login rate limiting: 5 attempts / 15 min, 10 attempts / 1 hour

### Authorization
- Fixed RBAC: student, teacher, staff, admin, parent
- Middleware checks on every protected route
- Data-level filtering (students see only own data, parents see only linked child)

### Input Protection
- **SQL Injection:** 100% prepared statements (PDO); no exceptions
- **XSS:** `htmlspecialchars()` on all output; CSP headers
- **CSRF:** Tokens on all state-changing forms and AJAX requests
- **File Uploads:** Type validation (finfo), size limits, extension whitelist, renamed storage, served via proxy

### Infrastructure
- HTTPS mandatory in production
- `.env` protected by `.htaccess`
- Database user has minimal privileges (no DROP/ALTER)
- Secrets rotated every 6-12 months

---

## 6. Main Features

| # | Feature | User | Priority |
|---|---------|------|----------|
| 1 | QR Code Scanning (entry/exit) | Student/Teacher | Must |
| 2 | Tardy & Absence Auto-Detection | System | Must |
| 3 | Manual Attendance Entry | Librarian | Must |
| 4 | Excuse Slip Submission & Approval | Teacher/Parent → Librarian | Must |
| 5 | Attendance Calendar (monthly view) | All roles | Must |
| 6 | Parent Email Alerts (tardy/absent/awards) | System → Parent | Must |
| 7 | Analytics Dashboard (trends, summaries) | Admin/Staff/Teacher | Must |
| 8 | Perfect Attendance Award Tool | Admin/Staff | Must |
| 9 | CSV/Excel Export | Admin/Staff | Must |
| 10 | Real-time Occupancy Counter | Admin/Staff | Should |

---

## 7. Important User Flows

### Flow A: Student Library Entry
1. Student approaches scanner kiosk (webcam/phone)
2. QR code scanned via html5-qrcode
3. System validates QR → identifies student
4. Records entry with timestamp
5. Compares against opening time + threshold
6. If tardy: marks status, queues parent email alert
7. Displays confirmation on screen (name, status, time)

### Flow B: Librarian End-of-Day Review
1. Librarian logs in → Dashboard loads
2. Views today's summary (present/tardy/absent counts)
3. Reviews pending excuse slips → approves/rejects
4. Exports daily report to CSV
5. Checks parent alert queue status

### Flow C: Parent Alert
1. Student marked tardy/absent
2. System creates `parent_alerts` record with status='pending'
3. Cron job (every 5 min) picks up pending alerts
4. PHPMailer sends email via SMTP
5. Record updated to 'sent' or 'failed'
6. Parent receives email with student name, date, time, status

### Flow D: Excuse Slip Approval
1. Teacher/Parent submits slip (student, dates, reason, optional attachment)
2. Librarian reviews in pending queue
3. On approval: all affected attendance records updated to 'excused'
4. Audit log created
5. Parent alert suppressed for excused records

---

## 8. Success / Failure Considerations

### Success Path (QR Scan)
```
Request → QR valid → Student found → Entry recorded → 
Status computed (present/tardy) → Alert queued if needed → 
JSON response → UI confirmation
```

### Failure Paths & Expected Behavior

| Failure | Cause | Behavior |
|---------|-------|----------|
| **Unauthenticated** | No session / expired | Redirect to login; API returns 401 |
| **Unauthorized** | Wrong role for action | API returns 403; page shows access denied |
| **Invalid QR** | Unknown/malformed code | API returns 404; UI shows "Invalid QR code" |
| **Duplicate scan** | Same QR scanned twice rapidly | Record second scan as re-entry; allow multiple entries per day |
| **Missing data** | Required field empty | API returns 400 with field-level errors |
| **Database failure** | Connection lost, deadlock | API returns 500; logged; generic message to user |
| **Email failure** | SMTP down, invalid address | Alert marked 'failed' with error; retried on next cron run |
| **File upload failure** | Too large, wrong type | API returns 400; specific validation message |
| **Timeout** | Slow query, large export | CSV limited to 10,000 rows; analytics paginated |
| **Race condition** | Simultaneous excuse approval | Database transaction locks row; second request waits or gets conflict error |
| **Malicious input** | SQL injection attempt | Prepared statement neutralizes; logged; no data exposure |
| **Invalid state** | Approving already-approved slip | API returns 400; "Already processed" message |
| **Network failure** | Client loses connection | User retries; idempotent scan handling (same QR within 30 sec = no duplicate) |

---

## 9. Document Conflicts

**Status: NO CONFLICTS DETECTED**

All six documents have been reconciled:
- `PRD.md` features map to `Data.md` entities
- `Data.md` schema aligns with `Architecture.md` tech stack (MySQL, PDO)
- `Architecture.md` API contracts reference `Data.md` fields
- `Security.md` requirements are implementable within `Architecture.md` structure
- `Skills.md` patterns match `Architecture.md` conventions
- `Agents.md` rules enforce consistency across all documents

**Canonical Decisions Made:**

| Decision | Source | Rationale |
|----------|--------|-----------|
| Primary key: `INT UNSIGNED AUTO_INCREMENT` | Architecture + Data | Native PHP/MySQL simplicity; sufficient scale |
| Session-based auth (not JWT) | Architecture + Security | Native PHP; no external dependencies; fits single-domain app |
| No framework | Architecture | User requirement; scope bounded |
| Email queue in DB (not Redis) | Architecture | Volume too low to justify extra infrastructure |
| Prepared statements only | Security + Data | Non-negotiable; zero exceptions |
| Soft deletes on users | Data + Security | Preserve audit trail; prevent accidental data loss |

---

## 10. Risks

| Risk | Likelihood | Impact | Mitigation |
|------|-----------|--------|------------|
| QR scanning fails in poor lighting | Medium | Medium | Allow manual entry fallback; position scanner near good lighting |
| Email alerts marked as spam | Medium | High | Use institutional SMTP; configure SPF/DKIM; monitor bounce rates |
| Database performance with 1M+ records | Low | Medium | Proper indexing; archive old data; query optimization |
| Concurrent scans overwhelm server | Low | Medium | Rate limiting; simple queue for scan processing |
| Parent email outdated/incorrect | Medium | Medium | Admin can update; validation on entry; bounce handling |
| Browser camera permission denied | Medium | Medium | Clear UI instructions; manual entry fallback |
| Admin password compromised | Low | Critical | Strong password policy; rate limiting; audit logging |
| Data loss (server failure) | Low | Critical | Daily backups; backup encryption; tested restore procedure |

---

## 11. Recommended Implementation Order

### Phase 1: Foundation (Week 1)
1. Project structure and routing (`index.php`, `.htaccess`)
2. Database connection (`Database.php`)
3. Configuration system (`config.php`, `.env`)
4. Base models (`User.php`)
5. Authentication system (login, logout, session management)
6. Tailwind CSS setup and base layout (header, sidebar, footer)

### Phase 2: Core Data (Week 1-2)
7. Database schema creation (all tables)
8. Student/Teacher/Admin CRUD
9. QR code generation for students
10. Settings management UI

### Phase 3: Attendance (Week 2)
11. QR scanner interface (`html5-qrcode` integration)
12. Attendance recording API (`/api/attendance/scan.php`)
13. Manual attendance entry form
14. Tardy threshold logic
15. Daily attendance view

### Phase 4: Excuse Slips & Alerts (Week 3)
16. Excuse slip submission form
17. Excuse slip review queue
18. Parent alert database queue
19. PHPMailer integration
20. Cron jobs (absence batch, alert processing)

### Phase 5: Analytics & Exports (Week 3-4)
21. Attendance calendar view
22. Analytics dashboard (Chart.js)
23. CSV export tool
24. Perfect attendance award calculation
25. Award notification emails

### Phase 6: Polish & Security (Week 4)
26. RBAC enforcement across all pages
27. CSRF tokens on all forms
28. Input validation and output escaping audit
29. Rate limiting implementation
30. Security hardening (CSP, headers, file upload proxy)
31. Audit logging integration
32. Backup scripts and cron configuration

---

## 12. Pre-Coding Validation Checklist

| # | Item | Status |
|---|------|--------|
| | Problem defined | ✅ |
| | Users defined | ✅ |
| | Features defined | ✅ |
| | Non-goals defined | ✅ |
| | User flows defined | ✅ |
| | Acceptance criteria defined | ✅ |
| | Entities defined | ✅ |
| | IDs defined | ✅ |
| | Relationships defined | ✅ |
| | Validation defined | ✅ |
| | Tech stack chosen | ✅ |
| | Architecture chosen | ✅ |
| | API strategy chosen | ✅ |
| | Deployment target chosen | ✅ |
| | Authentication decided | ✅ |
| | Authorization decided | ✅ |
| | Security requirements defined | ✅ |
| | Secrets strategy defined | ✅ |
| | .env.example exists | ✅ |
| | .gitignore exists | ✅ |
| | Six documentation files created | ✅ |
| | Documents reconciled | ✅ |
| | Success paths considered | ✅ |
| | Failure paths considered | ✅ |

---

## 13. Approval Request

**The foundation is complete and ready for implementation.**

Before writing application code, please review:
1. The tech stack (Native PHP, MySQL, Tailwind, Vanilla JS)
2. The architecture (simple MVC-like native PHP)
3. The security model (session-based auth, prepared statements, RBAC)
4. The implementation order (6 phases, 4-week estimate)
5. Any risks or concerns you have

**Reply with:**
- "Approved — start coding" to begin implementation
- Specific changes or concerns to address first
- Questions about any section

---

*Documents created:*
- `docs/PRD.md`
- `docs/Data.md`
- `docs/Architecture.md`
- `docs/Security.md`
- `docs/Skills.md`
- `docs/Agents.md`
- `.env.example`
- `.gitignore`
