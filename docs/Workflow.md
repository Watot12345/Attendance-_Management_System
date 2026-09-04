# Workflow Document
# Library Attendance Monitoring System (LAMS)

> No-code planning. Every user flow, decision point, success path, and failure path mapped out before implementation.

---

## Table of Contents

1. [Role-Based Workflows](#1-role-based-workflows)
   - [Student](#11-student)
   - [Teacher / Staff](#12-teacher--staff)
   - [Librarian / Admin](#13-librarian--admin)
   - [Parent](#14-parent)
2. [Feature-Based Workflows](#2-feature-based-workflows)
   - [F1: QR Scanning](#f1-qr-code-scanning)
   - [F2: Tardy & Absence](#f2-tardy--absence-detection)
   - [F3: Teacher Attendance](#f3-teacher-attendance)
   - [F4: Excuse Slips](#f4-excuse-slip-submission--approval)
   - [F5: Attendance Calendar](#f5-attendance-calendar)
   - [F6: Parent Alerts](#f6-parent-email-alerts)
   - [F7: Analytics Dashboard](#f7-analytics-dashboard)
   - [F8: Perfect Attendance Awards](#f8-perfect-attendance-awards)
   - [F9: CSV Export](#f9-csv--excel-export)
3. [System Workflows](#3-system-workflows)
4. [State Transition Diagrams](#4-state-transition-diagrams)
5. [Decision Trees](#5-decision-trees)

---

## 1. Role-Based Workflows

### 1.1 Student

```
[START]
    │
    ▼
┌─────────────┐
│ 1. Arrive   │
│    at       │
│  Library    │
└──────┬──────┘
       │
       ▼
┌─────────────┐     NO     ┌─────────────────┐
│ 2. Has QR   │───────────▶│ 3. Ask Librarian│
│    card?    │            │   for manual    │
└──────┬──────┘            │   entry         │
       │ YES               └─────────────────┘
       ▼
┌─────────────┐
│ 4. Scan QR  │
│   at kiosk  │
└──────┬──────┘
       │
       ▼
┌─────────────┐     FAIL   ┌─────────────────┐
│ 5. Scan     │───────────▶│ 6. Retry or     │
│   successful?│            │   ask Librarian │
└──────┬──────┘            └─────────────────┘
       │ YES
       ▼
┌─────────────┐
│ 7. View     │
│ confirmation│
│ on screen   │
│ (name, time,│
│  status)    │
└──────┬──────┘
       │
       ▼
┌─────────────┐     NO     ┌─────────────────┐
│ 8. Need to  │───────────▶│ 9. Leave library│
│   leave and │            │   (no action    │
│   re-enter? │            │   needed)       │
└──────┬──────┘            └─────────────────┘
       │ YES
       ▼
┌─────────────┐
│10. Scan QR  │
│   again for │
│   exit      │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│11. View     │
│   personal  │
│   attendance│
│   history   │
│   (optional)│
└──────┬──────┘
       │
       ▼
    [END]
```

**Student Web Interface:**
- Login → Student Dashboard
- My Attendance Calendar (read-only)
- Today's Status
- Total Present / Tardy / Absent
- Perfect Attendance Streak
- **NO access to:** Other students' data, Excuse slips, Analytics, User management, Settings

---

### 1.2 Teacher / Staff

```
[START]
    │
    ▼
┌─────────────┐
│ 1. Login    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ 2. Teacher  │
│   Dashboard │
└──────┬──────┘
       │
       ├──┬──┬──┬──┬──┐
       │  │  │  │  │  │
       ▼  ▼  ▼  ▼  ▼  ▼
   ┌────┐┌──┐┌──┐┌──┐┌──┐
   │Scan││My││Sub││View││View│
   │QR  ││Att││mit ││All ││Anal│
   │    ││end││Excu││Stu-││ytics│
   │    ││ance││se  ││dent││(lim)│
   └─┬──┘└─┬─┘└─┬─┘└─┬─┘└─┬─┘
     │     │    │    │    │
     ▼     ▼    ▼    ▼    ▼
```

**Teacher Paths:**
- **A: Scan QR** → Self attendance (no tardy threshold, no alerts)
- **B: My Attendance** → View own history
- **C: Submit Excuse Slip** → Select student → Date range → Reason → Optional attachment → Submit to pending queue
- **D: View Student Attendance** → Search/filter by grade/section → View list or individual
- **E: View Analytics** → Read-only summaries and trends

**Teacher Permissions:**
- Scan QR for self, View own history, Submit excuse slips for any student
- View all student attendance (read-only), View analytics (read-only), View settings (read-only)
- **Cannot:** Approve excuse slips, Manage users, Export data, Delete records

---

### 1.3 Librarian / Admin

```
[START]
    │
    ▼
┌─────────────┐
│ 1. Login    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ 2. Admin    │
│   Dashboard │
│   (full     │
│   overview) │
└──────┬──────┘
       │
       ├──┬──┬──┬──┬──┬──┬──┬──┬──┬──┐
       │  │  │  │  │  │  │  │  │  │  │
       ▼  ▼  ▼  ▼  ▼  ▼  ▼  ▼  ▼  ▼  ▼
   ┌────┐┌──┐┌──┐┌──┐┌──┐┌──┐┌──┐┌──┐┌──┐┌──┐
   │Dash││QR ││Man││Exc ││Cal ││Ana ││Awd ││Exp ││User││Set │
   │board││Scan││ual││use ││endar││lyt ││ards ││ort ││Mgmt││ings│
   └─┬──┘└─┬─┘└─┬─┘└─┬─┘└─┬─┘└─┬─┘└─┬─┘└─┬─┘└─┬─┘└─┬─┘
     │     │    │    │    │    │    │    │    │    │
     ▼     ▼    ▼    ▼    ▼    ▼    ▼    ▼    ▼    ▼
```

**Admin Paths:**
- **A: Dashboard** → Today's summary, Recent tardies, Pending excuses, Current occupancy, Quick actions
- **B: QR Scan Kiosk** → Fullscreen scanner → Large confirmation (name, photo, time, status)
- **C: Manual Attendance** → Select date → Select student → Enter times → Select status → Add notes → Save (audited)
- **D: Excuse Slip Review** → View pending queue → Open detail → View student, dates, reason, attachment → Approve / Reject / Hold
- **E: Attendance Calendar** → Select student → Navigate month → View color-coded days → Click day for detail
- **F: Analytics Dashboard** → Select date range → Apply filters (grade, section) → View summary cards, trend charts, heatmap, comparison tables
- **G: Perfect Attendance Awards** → Select award type (monthly/quarterly/semester/custom) → Calculate → View results → Notify parents / Print / Export CSV
- **H: Export Data** → Select data type → Date range → Filters → Preview row count → Export CSV
- **I: User Management** → View list → Create / Edit / Delete / Reset Password
- **J: Settings** → Edit library hours, tardy threshold, alert toggles, timezone, max excuse days, CSV max rows → Save (audited)

**Admin Permissions:** FULL ACCESS — only role that can delete records, manage users, change settings, approve excuse slips, export data, run award tool.

---

### 1.4 Parent

```
[START]
    │
    ▼
┌─────────────┐
│ 1. Receives │
│    email    │
│    alert    │
│    (tardy/  │
│    absent/  │
│    award)   │
└──────┬──────┘
       │
       ├──┬──┐
       │  │  │
       ▼  ▼  ▼
   ┌────┐┌──┐┌──┐
   │Read││Log││Ign│
   │only││in ││ore │
   └─┬──┘└─┬─┘└─┬──┘
     │     │    │
     ▼     ▼    ▼
  [END] ┌────┐[END]
        │View│
        │Chld│
        │Att │
        │end │
        │ance│
        │Cal │
        └────┘
```

**Parent Web Interface:**
- Login → Parent Dashboard
- Child's Attendance Calendar (read-only, color-coded)
- Recent Alerts History
- Child's Statistics
- Contact Info (read-only)
- **NO access to:** Other children's data, Excuse slip submission, Analytics, User management, Settings, QR scanning

---

## 2. Feature-Based Workflows

### F1: QR Code Scanning

```
TRIGGER: Student/Teacher approaches scanner station

STEP 1: SCANNER INITIALIZATION
┌─────────────┐
│ Browser     │
│ loads       │
│ scanner.js  │
└──────┬──────┘
       │
       ▼
┌─────────────┐     NO
│ Camera      │───────────▶ Show error: "Camera access required"
│ available?  │
└──────┬──────┘
       │ YES
       ▼
┌─────────────┐
│ html5-qrcode│
│ initializes │
│ camera feed │
│ Show scan   │
│ overlay     │
└──────┬──────┘
       │
       ▼

STEP 2: QR DETECTION
┌─────────────┐
│ QR code     │
│ detected    │
└──────┬──────┘
       │
       ▼
┌─────────────┐     INVALID     ┌─────────────────┐
│ Decode QR   │────────────────▶│ Show "Invalid   │
│ string      │                 │ QR Code"        │
└──────┬──────┘                 │ Allow retry     │
       │ VALID                  └─────────────────┘
       ▼
┌─────────────┐
│ Extract     │
│ qr_code     │
│ value       │
└──────┬──────┘
       │
       ▼

STEP 3: API REQUEST
┌─────────────┐
│ POST        │
│ /api/       │
│ attendance/ │
│ scan.php    │
│ {qr_code,   │
│  direction} │
└──────┬──────┘
       │
       ▼

STEP 4: SERVER VALIDATION
┌─────────────┐     MISSING     ┌─────────────────┐
│ Validate    │────────────────▶│ 400 Bad Request │
│ input       │                 │ "Missing fields"│
└──────┬──────┘                 └─────────────────┘
       │ VALID
       ▼
┌─────────────┐     NOT FOUND   ┌─────────────────┐
│ Find student│────────────────▶│ 404 Not Found   │
│ by QR code  │                 │ "Invalid QR code│
└──────┬──────┘                 └─────────────────┘
       │ FOUND
       ▼
┌─────────────┐     INACTIVE    ┌─────────────────┐
│ Check user  │────────────────▶│ 403 Forbidden   │
│ status      │                 │ "Account        │
└──────┬──────┘                 │  suspended"     │
       │ ACTIVE                 └─────────────────┘
       ▼

STEP 5: ATTENDANCE RECORDING
┌─────────────┐
│ Check for   │
│ existing    │
│ entry today │
└──────┬──────┘
       │
       ├──┬──┐
       │  │  │
       ▼  ▼  ▼
   ┌────┐┌──┐┌──┐
   │No  ││Has││Has│
   │entry││ent││exit│
   │     ││ry ││too │
   └─┬───┘└─┬─┘└─┬─┘
     │      │    │
     ▼      ▼    ▼
  ┌─────┐┌────┐┌────┐
  │Creat││Crea││Crea│
  │e new││te  ││te  │
  │entry││exit││re- │
  │     ││rec ││entry│
  └─────┘└────┘└────┘
       │
       ▼
┌─────────────┐     BEFORE     ┌─────────────────┐
│ Compare     │───────────────▶│ status =        │
│ entry_time  │   THRESHOLD    │ "present"       │
│ to cutoff   │                └─────────────────┘
│ (open_time +│     AFTER
│  threshold) │───────────────▶┌─────────────────┐
└──────┬──────┘                │ status =        │
       │                        │ "tardy"         │
       │                        │ Queue parent    │
       │                        │ alert           │
       │                        └─────────────────┘
       ▼

STEP 6: RESPONSE & UI
┌─────────────┐
│ Return JSON │
│ {success,   │
│  message,   │
│  data: {    │
│    name,    │
│    status,  │
│    time}}   │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Browser     │
│ shows       │
│ confirmation│
│ overlay:    │
│ • Photo     │
│ • Name      │
│ • Status    │
│   (color)   │
│ • Timestamp │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Auto-reset  │
│ scanner     │
│ after 3 sec │
└─────────────┘
```

**Failure Handling:**
| Failure | Code | Message |
|---------|------|---------|
| Missing qr_code | 400 | "Please scan a valid QR code" |
| QR not found | 404 | "Student not found" |
| Account suspended | 403 | "Account access restricted" |
| Database error | 500 | "System error. Please try again" |
| Duplicate scan <30s | 429 | "Already scanned. Please wait" |
| Camera denied | Client | "Allow camera access to scan" |
| Network failure | Client | "Check connection and retry" |

---

### F2: Tardy & Absence Detection

```
┌────────────────────────────────────────┐
│           TARDY DETECTION              │
└────────────────────────────────────────┘

TRIGGER: QR scan records entry_time

Get setting: library_open_time (e.g. 08:00)
Get setting: tardy_threshold_minutes (e.g. 15)
Calculate cutoff: 08:00 + 15 min = 08:15

entry_time <= cutoff? ──YES──▶ status = "present"
        │
        NO
        ▼
    status = "tardy"
        │
        ▼
    alert_tardy_enabled? ──NO──▶ [END]
        │
        YES
        ▼
    Has linked parent with email?
        │
        ├──YES──▶ Check: already alerted today?
        │            │
        │            ├──YES──▶ Skip (prevent duplicate)
        │            │
        │            NO──▶ Queue tardy alert
        │
        └──NO──▶ Skip (no parent)

┌────────────────────────────────────────┐
│          ABSENCE DETECTION             │
└────────────────────────────────────────┘

TRIGGER: Daily cron job (library_close_time + 30 min)

auto_absence_enabled? ──NO──▶ [END]
        │
        YES
        ▼
    Get today's date
    Get all active students
        │
        ▼
    For each student:
        │
        ├──Has entry today? ──YES──▶ Skip
        │
        ├──Has excused absence today? ──YES──▶ Skip
        │
        NO──▶ Create absence record (status = "absent")
                │
                ▼
            alert_absent_enabled? ──NO──▶ Next student
                │
                YES
                ▼
            Has parent email?
                │
                ├──YES──▶ Queue absent alert
                │
                └──NO──▶ Skip

    Log batch results
```

---

### F3: Teacher Attendance

```
IDENTICAL TO STUDENT QR SCANNING with differences:

QR code maps to teacher record in teachers table
    │
    ▼
No tardy threshold for teachers (always "present")
    │
    ▼
No parent alerts sent for teachers
    │
    ▼
Separate reporting view: "Teacher Attendance" in analytics
```

---

### F4: Excuse Slip Submission & Approval

```
┌────────────────────────────────────────┐
│      SUBMISSION (Teacher/Parent)       │
└────────────────────────────────────────┘

[START]
    │
    ▼
Navigate to "Submit Excuse Slip" page
    │
    ▼
Logged in? ──NO──▶ Redirect to login
    │
    YES
    ▼
Role = teacher or parent? ──NO──▶ 403 Access Denied
    │
    YES
    ▼
Select Student (dropdown or search)
    │
    ▼
Validate student selection ──INVALID──▶ Error: "Invalid student"
    │
    VALID
    ▼
Enter Date From
Enter Date To
    │
    ▼
Validate date range:
  • from <= to
  • to - from <= max_excuse_days
  • from not in future
  ──INVALID──▶ Error: "Date range invalid or too long"
    │
    VALID
    ▼
Enter Reason (text area)
    │
    ▼
Attach Document? ──NO──▶ Skip to submit
    │
    YES
    ▼
Validate file:
  • type: PDF/JPG/PNG
  • size <= 5MB
  ──INVALID──▶ Error: "Invalid file. PDF/JPG/PNG only, max 5MB"
    │
    VALID
    ▼
Upload to /uploads/excuse-slips/ (renamed)
    │
    ▼
Submit form (POST with CSRF token)
    │
    ▼
Server validates and saves ──FAIL──▶ Error: "Submission failed. Please retry"
    │
    SUCCESS
    ▼
Create excuse_slip record (status = "pending")
    │
    ▼
Show confirmation with slip ID

┌────────────────────────────────────────┐
│      APPROVAL (Librarian/Admin)        │
└────────────────────────────────────────┘

[START]
    │
    ▼
Navigate to "Review Excuse Slips" page
    │
    ▼
View pending queue (sorted by date, paginated)
    │
    ▼
Click slip to review
    │
    ▼
View full details:
  • Student info
  • Date range
  • Reason
  • Attachment (preview/download)
  • Submitted by
  • Affected attendance records
    │
    ├──┬──┬──┐
    │  │  │  │
    ▼  ▼  ▼  ▼
┌────┐┌──┐┌──┐┌──┐
│App ││Rej││Hol││Back│
│rove││ect││d  ││to  │
└─┬──┘└─┬─┘└─┬─┘└─┬──┘
  │     │    │    │
  ▼     ▼    ▼    ▼
```

**APPROVE PATH:**
```
Start DB transaction
    │
    ▼
Update slip status to "approved"
Set reviewed_by, reviewed_at
    │
    ▼
Find all attendance records for student in date range
with status "tardy" or "absent"
    │
    ├──NONE──▶ Skip attendance update
    │
    FOUND
    ▼
For each record:
  • status = "excused"
  • is_excused = 1
    │
    ▼
Commit transaction
    │
    ▼
Log to audit_logs (old_values, new_values)
    │
    ▼
Show success: "Approved. X records updated"
```

**REJECT PATH:**
```
Update slip status to "rejected"
Set reviewed_by, reviewed_at, review_notes
    │
    ▼
NO attendance records modified
    │
    ▼
Log to audit_logs
    │
    ▼
Show success: "Rejected"
```

**Failure Handling:**
| Failure | Code | Message |
|---------|------|---------|
| Missing fields | 400 | "All fields required" |
| Invalid student | 400 | "Invalid student selected" |
| Invalid date range | 400 | "Date range invalid or too long" |
| File too large | 400 | "File must be under 5MB" |
| Invalid file type | 400 | "Only PDF, JPG, PNG allowed" |
| Slip not found | 404 | "Excuse slip not found" |
| Already processed | 400 | "Slip already reviewed" |
| Unauthorized | 403 | "Access denied" |
| DB transaction fail | 500 | "Review failed. Please retry" |

---

### F5: Attendance Calendar

```
[START]
    │
    ▼
Navigate to "Attendance Calendar" page
    │
    ▼
Determine viewer role:
    │
    ├──Student──▶ Load own data only
    ├──Parent──▶ Load linked child's data only
    ├──Teacher──▶ Show student selector, can view any student
    └──Admin──▶ Show student selector, can view any student
    │
    ▼
Select student (optional for admin, default self for others)
    │
    ▼
Select month/year (default: current)
    │
    ▼
Fetch attendance records for selected student + month
    │
    ▼
Render calendar grid:
  • Days of month
  • Color per status:
    🟢 present  🟡 tardy  🔴 absent  🔵 excused  ⚪ no data
    │
    ▼
Click on a day? ──NO──▶ [END]
    │
    YES
    ▼
Show modal with:
  • Date
  • Status
  • Entry time
  • Exit time
  • Method
  • Notes
  • Excuse slip link (if any)

PRINT WORKFLOW:
Click "Print" button ──▶ Open print dialog with styled calendar (CSS print media)
```

---

### F6: Parent Email Alerts

```
┌────────────────────────────────────────┐
│           ALERT CREATION               │
└────────────────────────────────────────┘

TRIGGER: Student marked tardy or absent

alert_type_enabled? (tardy or absent) ──NO──▶ [END]
    │
    YES
    ▼
Has linked parent with email? ──NO──▶ [END]
    │
    YES
    ▼
Check: Was this already alerted today? (prevent duplicate)
    │
    ├──Already alerted──▶ Skip
    ├──Not alerted, excused──▶ Skip
    └──Not alerted, unexcused──▶ Create alert
                                    │
                                    ▼
                                Create parent_alert record:
                                  • recipient_email
                                  • student_user_id
                                  • alert_type
                                  • subject
                                  • body
                                  • status = "pending"

┌────────────────────────────────────────┐
│        ALERT PROCESSING (Cron)         │
└────────────────────────────────────────┘

TRIGGER: cron_alerts.php runs every 5 minutes

Fetch up to 50 pending alerts ordered by created_at
    │
    ├──None pending──▶ Log "No pending alerts"
    ├──1-49 pending──▶ Send each one
    └──50+ pending──▶ Send 50, log remaining
    │
    ▼
PHPMailer sends email via SMTP
    │
    ├──FAIL──▶ Mark alert "failed"
    │            Store error_message
    │            Will retry next cron run (max 3 tries)
    │            After 3 fails: mark "permanently failed", log to admin
    │
    └──SUCCESS──▶ Mark alert "sent"
                    sent_at = now()
                    │
                    ▼
                Log to cron_alerts.log

EMAIL TEMPLATES:

TARDY ALERT:
  Subject: "[LAMS] Tardy Alert - [Student Name] - [Date]"
  Body: Student marked tardy at [Time]. Library opens at [Open Time].
        Threshold is [Threshold] minutes. Contact library if error.

ABSENT ALERT:
  Subject: "[LAMS] Absence Alert - [Student Name] - [Date]"
  Body: Student marked absent. No entry record found.
        Contact library if present. Submit excuse: [Link]

PERFECT ATTENDANCE AWARD:
  Subject: "[LAMS] Perfect Attendance Award - [Student Name]"
  Body: Congratulations! Perfect attendance for [Period].
        Award details: [Link to certificate]
```

---

### F7: Analytics Dashboard

```
[START]
    │
    ▼
Navigate to "Analytics" page
    │
    ▼
Role check: admin, staff, or teacher? ──NO──▶ 403 Access Denied
    │
    YES
    ▼
Load default view:
  • Range: last 30 days
  • All grades
  • All sections
    │
    ▼
Fetch data via AJAX: /api/analytics/summary.php
    │
    ▼
Data loaded? ──FAIL──▶ Show error: "Failed to load data"
    │
    SUCCESS
    ▼
Render dashboard:
  ┌─────────┐
  │Summary  │  Cards: Total Present, Tardy, Absent, Excused, Occupancy
  │Cards    │
  ├─────────┤
  │Trend    │  Chart.js line chart: daily attendance over time
  │Chart    │
  ├─────────┤
  │Heatmap  │  Absence heatmap by day of week / time
  │         │
  ├─────────┤
  │Breakdown│  Bar chart: by grade, by section
  │Charts   │
  ├─────────┤
  │Comparison│ Table: compare sections side by side
  │Table    │
  └─────────┘
    │
    ▼
User applies filters?
    │
    ├──NO──▶ [END]
    │
    YES
    ▼
Select date range
Select grade (optional)
Select section (optional)
Select individual student (optional)
    │
    ▼
Click "Apply Filters"
    │
    ▼
Re-fetch data with filters
    │
    ▼
Re-render charts and tables
```

---

### F8: Perfect Attendance Awards

```
[START]
    │
    ▼
Navigate to "Perfect Attendance Awards" page
    │
    ▼
Role check: admin or staff? ──NO──▶ 403 Access Denied
    │
    YES
    ▼
Select award type:
    │
    ├──Monthly──▶ Auto-fill: 1st to last day of selected month
    ├──Quarterly──▶ Auto-fill: quarter date range
    ├──Semester──▶ Auto-fill: semester date range
    └──Custom──▶ Manually enter date range
    │
    ▼
Validate date range ──INVALID──▶ Show error
    │
    VALID
    ▼
Click "Calculate"
    │
    ▼
System checks each active student:
    │
    ├──Has unexcused absence in period? ──YES──▶ Exclude
    ├──Has tardy in period? ──YES──▶ Exclude
    └──Both NO──▶ Include in results
    │
    ▼
Display results table:
  • Student name
  • Grade/Section
  • Days in period
  • Present count
  • Tardy count (should be 0)
  • Absent count (should be 0)
    │
    ▼
Results actions:
    │
    ├──Notify Parents──▶ Queue perfect attendance alerts for each
    ├──Print Certificates──▶ Generate printable certificate view
    └──Export CSV──▶ Download results as CSV
```

---

### F9: CSV / Excel Export

```
[START]
    │
    ▼
Navigate to "Export Data" page
    │
    ▼
Role check: admin or staff? ──NO──▶ 403 Access Denied
    │
    YES
    ▼
Select data type:
  • Attendance records
  • Tardies
  • Absences
  • Excuse Slips
    │
    ▼
Select date range
    │
    ▼
Apply filters:
  • Grade (optional)
  • Section (optional)
  • Student (optional)
  • Status (optional)
    │
    ▼
Preview row count
    │
    ▼
Row count > 10,000? ──YES──▶ Show warning: "Too many rows. Narrow your filters."
    │
    NO
    ▼
Click "Export CSV"
    │
    ▼
Server generates CSV:
  • UTF-8 with BOM (for Excel compatibility)
  • Proper headers
  • Sanitized data
  • Stored temporarily in /uploads/exports/
    │
    ▼
File ready ──▶ Browser auto-downloads
    │
    ▼
Temp file auto-deleted after 24 hours (cron cleanup)
```

---

## 3. System Workflows

### 3.1 Daily Batch Jobs

```
┌────────────────────────────────────────┐
│  cron_absence.php (at close + 30 min)  │
└────────────────────────────────────────┘

1. Check auto_absence_enabled
2. Get today's date
3. Get all active students
4. For each student:
   a. Check if entry exists today → YES: skip
   b. Check if excused absence exists → YES: skip
   c. Create absence record
   d. If alert_absent_enabled AND has parent email:
      - Check not already alerted today
      - Create parent_alert (status = pending)
5. Log batch summary (count created)

┌────────────────────────────────────────┐
│     cron_alerts.php (every 5 min)      │
└────────────────────────────────────────┘

1. Fetch up to 50 pending alerts
2. For each alert:
   a. Load PHPMailer with SMTP settings
   b. Send email
   c. On success: mark "sent", set sent_at
   d. On failure: mark "failed", store error, increment retry count
   e. If retry count >= 3: mark "permanently_failed"
3. Log batch results

┌────────────────────────────────────────┐
│    cron_cleanup.php (weekly, Sunday)   │
└────────────────────────────────────────┘

1. Delete parent_alerts older than 1 year
2. Delete audit_logs older than 2 years
3. Delete error logs older than 30 days
4. Delete temp export files older than 24 hours
5. Log cleanup summary
```

### 3.2 Authentication Flow

```
[START]
    │
    ▼
User visits login page
    │
    ▼
Already logged in? ──YES──▶ Redirect to dashboard
    │
    NO
    ▼
Show login form (email, password)
    │
    ▼
Submit form
    │
    ▼
Validate input ──INVALID──▶ Show errors
    │
    VALID
    ▼
Find user by email ──NOT FOUND──▶ Generic error (don't reveal email exists)
    │
    FOUND
    ▼
Check rate limit ──LOCKED──▶ "Account temporarily locked"
    │
    OK
    ▼
Verify password ──FAIL──▶ Increment failed attempts, generic error
    │
    SUCCESS
    ▼
Check user status:
    │
    ├──active──▶ Continue
    ├──inactive──▶ "Account inactive. Contact admin."
    └──suspended──▶ "Account suspended. Contact admin."
    │
    ▼
Regenerate session ID
    │
    ▼
Store in session:
  • user_id
  • role
  • email
  • last_activity
  • ip_address
  • user_agent
    │
    ▼
Update last_login_at
    │
    ▼
Log to audit_logs (login.success)
    │
    ▼
Redirect to role-appropriate dashboard
```

### 3.3 Authorization Check

```
Every protected page/API:
    │
    ▼
Session active? ──NO──▶ Redirect to login / 401
    │
    YES
    ▼
Session valid?
  • last_activity < 24h?
  • ip_address matches?
  • user_agent matches?
  ──NO──▶ Destroy session, redirect to login
    │
    YES
    ▼
Update last_activity
    │
    ▼
Role in allowed list? ──NO──▶ 403 Access Denied
    │
    YES
    ▼
Data-level authorization:
  • Student: only own data
  • Parent: only linked child's data
  • Teacher: all student data (read), own data
  • Admin/Staff: all data
  ──FAIL──▶ 403 Access Denied
    │
    PASS
    ▼
Continue to page logic
```

---

## 4. State Transition Diagrams

### 4.1 Attendance Record Status

```
                    ┌─────────────┐
                    │   (none)    │
                    │  no record  │
                    └──────┬──────┘
                           │ QR scan (entry)
                           ▼
                    ┌─────────────┐
              ┌────▶│   present   │◀────┐
              │     │  (on time)  │     │
              │     └─────────────┘     │
              │                           │
              │     ┌─────────────┐     │
              │     │   tardy     │     │
              └────│  (late)     │─────┘
                    └──────┬──────┘
                           │ excuse slip approved
                           ▼
                    ┌─────────────┐
                    │   excused   │
                    │ (overrides  │
                    │  tardy)     │
                    └─────────────┘

                    ┌─────────────┐
                    │   (none)    │
                    │  no record  │
                    └──────┬──────┘
                           │ cron absence batch
                           ▼
                    ┌─────────────┐
                    │   absent    │
                    │ (no entry   │
                    │  by close)  │
                    └──────┬──────┘
                           │ excuse slip approved
                           ▼
                    ┌─────────────┐
                    │   excused   │
                    │ (overrides  │
                    │  absent)    │
                    └─────────────┘

Allowed transitions:
  present  → excused (via excuse slip)
  tardy    → excused (via excuse slip)
  absent   → excused (via excuse slip)
  excused  → (no further transitions)
```

### 4.2 Excuse Slip Status

```
┌─────────────┐
│  (created)  │
│   pending   │
└──────┬──────┘
       │
       ├──┬──┐
       │  │  │
       ▼  ▼  ▼
   ┌────┐┌──┐┌──┐
   │App ││Rej││Hol│
   │rove││ect││d  │
   └────┘└──┘└──┘

Allowed transitions:
  pending → approved (by admin/staff)
  pending → rejected (by admin/staff)
  pending → pending (hold, no change)
  approved → (no further transitions)
  rejected → (no further transitions)
```

### 4.3 Parent Alert Status

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   pending   │────▶│    sent     │     │   failed    │
│  (queued)   │     │ (delivered) │     │ (SMTP error)│
└─────────────┘     └─────────────┘     └──────┬──────┘
                                               │
                                               │ retry < 3
                                               ▼
                                        ┌─────────────┐
                                        │ permanently │
                                        │   failed    │
                                        │ (max retries│
                                        │  exceeded)  │
                                        └─────────────┘

Allowed transitions:
  pending → sent (successful delivery)
  pending → failed (SMTP error)
  failed → pending (retry on next cron)
  failed → permanently_failed (after 3 retries)
```

### 4.4 User Status

```
┌─────────────┐
│   active    │
└──────┬──────┘
       │
       ├──┬──┐
       │  │  │
       ▼  ▼  ▼
   ┌────┐┌──┐┌──┐
   │Inac││Sus-││Soft│
   │tive││pend││Del │
   └────┘└──┘└──┘

Allowed transitions:
  active → inactive (admin action)
  active → suspended (admin action)
  active → soft deleted (admin action)
  inactive → active (admin action)
  suspended → active (admin action)
```

---

## 5. Decision Trees

### 5.1 QR Scan Decision Tree

```
                    ┌─────────────┐
                    │ QR scanned? │
                    └──────┬──────┘
                           │
              ┌────────────┴────────────┐
              │ YES                      │ NO
              ▼                          ▼
    ┌─────────────────┐        ┌─────────────────┐
    │ Decode QR string│        │ Show camera     │
    │                 │        │ access prompt   │
    └────────┬────────┘        └─────────────────┘
             │
    ┌────────┴────────┐
    │ Valid format?   │
    └────────┬────────┘
             │
    ┌────────┴────────┐
    │ YES              │ NO
    ▼                  ▼
┌─────────┐    ┌─────────────────┐
│Find user│    │ Show "Invalid   │
│by QR    │    │ QR" + retry     │
└────┬────┘    └─────────────────┘
     │
┌────┴────┐
│ Found?  │
└────┬────┘
     │
┌────┴────────┐
│ YES          │ NO
▼              ▼
┌─────────┐  ┌─────────────────┐
│Active   │  │ Show "Student   │
│account? │  │ not found"      │
└────┬────┘  └─────────────────┘
     │
┌────┴────────┐
│ YES          │ NO
▼              ▼
┌─────────┐  ┌─────────────────┐
│Record   │  │ Show "Account   │
│attend-  │  │ suspended"      │
│ance     │  └─────────────────┘
└────┬────┘
     │
┌────┴──────────────┐
│ Entry or exit?    │
└────┬──────────────┘
     │
┌────┴────┐
│entry    │exit
▼         ▼
┌─────────┐  ┌─────────┐
│Check    │  │Record   │
│tardy    │  │exit time│
│threshold│  │         │
└────┬────┘  └────┬────┘
     │            │
┌────┴────┐       │
│On time  │Late   │
▼         ▼       │
┌───┐   ┌───┐     │
│✅ │   │⚠️ │     │
│   │   │   │     │
└───┘   └───┘     │
        │         │
        ▼         ▼
   ┌─────────────────┐
   │ Queue parent    │
   │ alert if tardy  │
   └─────────────────┘
```

### 5.2 Absence Batch Decision Tree

```
                    ┌─────────────┐
                    │ Cron runs   │
                    │ at close+30 │
                    └──────┬──────┘
                           │
              ┌────────────┴────────────┐
              │                          │
              ▼                          ▼
    ┌─────────────────┐        ┌─────────────────┐
    │ auto_absence    │        │ Skip batch      │
    │ _enabled = true│        │                 │
    └────────┬────────┘        └─────────────────┘
             │
             ▼
    ┌─────────────────┐
    │ Get all active  │
    │ students        │
    └────────┬────────┘
             │
             ▼
    ┌─────────────────┐
    │ For each student│
    └────────┬────────┘
             │
             ▼
    ┌─────────────────┐
    │ Entry today?    │
    └────────┬────────┘
             │
    ┌────────┴────────┐
    │ YES              │ NO
    ▼                  ▼
┌─────────┐    ┌─────────────────┐
│ Skip    │    │ Excused absence │
│         │    │ today?          │
└─────────┘    └────────┬────────┘
                        │
               ┌────────┴────────┐
               │ YES              │ NO
               ▼                  ▼
         ┌─────────┐      ┌─────────────────┐
         │ Skip    │      │ Create absence  │
         │         │      │ record          │
         └─────────┘      └────────┬────────┘
                                   │
                                   ▼
                          ┌─────────────────┐
                          │ alert_absent    │
                          │ _enabled?       │
                          └────────┬────────┘
                                   │
                          ┌────────┴────────┐
                          │ YES              │ NO
                          ▼                  ▼
                   ┌─────────────┐    ┌─────────┐
                   │ Has parent  │    │ Skip    │
                   │ email?      │    │ alert   │
                   └──────┬──────┘    └─────────┘
                          │
                 ┌────────┴────────┐
                 │ YES              │ NO
                 ▼                  ▼
          ┌─────────────┐    ┌─────────┐
          │ Already     │    │ Skip    │
          │ alerted?    │    │ alert   │
          └──────┬──────┘    └─────────┘
                 │
        ┌────────┴────────┐
        │ YES              │ NO
        ▼                  ▼
   ┌─────────┐      ┌─────────────┐
   │ Skip    │      │ Queue alert │
   │ alert   │      │             │
   └─────────┘      └─────────────┘
```

### 5.3 Excuse Slip Approval Decision Tree

```
                    ┌─────────────┐
                    │ Librarian   │
                    │ reviews slip│
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
              ▼            ▼            ▼
        ┌─────────┐  ┌─────────┐  ┌─────────┐
        │ Approve │  │ Reject  │  │  Hold   │
        └────┬────┘  └────┬────┘  └────┬────┘
             │            │            │
             ▼            ▼            ▼
    ┌─────────────────┐  ┌─────────────────┐  ┌─────────┐
    │ Start DB        │  │ Update status   │  │ No      │
    │ transaction     │  │ to "rejected"   │  │ change  │
    └────────┬────────┘  │ Set review info │  └─────────┘
             │           └─────────────────┘
             ▼
    ┌─────────────────┐
    │ Update slip     │
    │ status to       │
    │ "approved"      │
    └────────┬────────┘
             │
             ▼
    ┌─────────────────┐
    │ Find affected   │
    │ attendance      │
    │ records         │
    └────────┬────────┘
             │
    ┌────────┴────────┐
    │ Records found?  │
    └────────┬────────┘
             │
    ┌────────┴────────┐
    │ YES              │ NO
    ▼                  ▼
┌─────────────┐  ┌─────────────┐
│ Update each │  │ Skip update │
│ to excused  │  │             │
└──────┬──────┘  └──────┬──────┘
       │                │
       ▼                ▼
┌─────────────┐  ┌─────────────┐
│ Commit      │  │ Commit      │
│ transaction │  │ transaction │
└──────┬──────┘  └──────┬──────┘
       │                │
       ▼                ▼
┌─────────────┐  ┌─────────────┐
│ Log audit   │  │ Log audit   │
│ (old/new)   │  │             │
└──────┬──────┘  └──────┬──────┘
       │                │
       ▼                ▼
┌─────────────────────────────┐
│ Show success message        │
│ Approve: "X records updated"│
│ Reject: "Rejected"          │
│ Hold: "On hold"             │
└─────────────────────────────┘
```
