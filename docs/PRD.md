# Product Requirements Document (PRD)
# Attendance Monitoring System (AMS)

## 1. Project Goal
Build a web-based attendance monitoring system for a library that tracks daily visits of students and teachers via QR code scanning, logs tardiness and absences, manages excuse slips, alerts parents via email, and provides analytics with export capabilities.

## 2. Problem Statement
- Manual attendance tracking in libraries is error-prone and time-consuming.
- No real-time visibility into who is in the library, who is late, or who is absent.
- Parents are not promptly informed when their child is tardy or absent.
- No systematic way to recognize perfect attendance or analyze attendance trends.
- Paper-based excuse slips are easily lost and hard to track.

## 3. Users

| Role | Description | Primary Actions |
|------|-------------|-----------------|
| **Student** | Library visitors | Scan QR to log entry/exit, view personal attendance history |
| **Teacher / Staff** | Library staff and visiting teachers | Mark own attendance, view student attendance, approve excuse slips, generate reports |
| **Librarian / Admin** | System administrators | Full system management, user management, configuration, analytics, exports |
| **Parent** | Guardians of students | Receive email alerts (tardy, absent, perfect attendance), view child's attendance calendar |

## 4. Core Features

### F1. Daily Attendance Marking (QR Scanning)
- Students scan a personal QR code using a webcam or phone camera at the library entrance/exit.
- System records timestamp, direction (entry/exit), and determines status (present / tardy).
- Manual override by librarian for forgotten QR codes.

### F2. Tardy & Absence Logs
- Automatic tardy flag if entry is after configured threshold past library opening time.
- Absence auto-logged if no entry recorded by closing time.
- Excused absences override unexcused status.

### F3. Teacher Attendance
- Teachers scan QR or manually log their library visits.
- Separate reporting view for teacher attendance.

### F4. Excuse Slip Submission
- Teachers or parents submit digital excuse slips with reason and optional attachment.
- Librarian reviews and approves/rejects.
- Approved slips update attendance status to "excused."

### F5. Attendance Calendar
- Monthly calendar view per student showing daily status (present, tardy, absent, excused).
- Color-coded indicators.

### F6. Parent Email Alerts
- Automatic email to parent when student is marked tardy or absent.
- Email notification for perfect attendance awards.
- Configurable alert types per parent.

### F7. Analytics Dashboard
- Daily/weekly/monthly attendance summaries.
- Tardiness trends by grade/section/student.
- Absence heatmap.
- Current occupancy (who is in the library now).

### F8. Perfect Attendance Award Tool
- Identify students with zero unexcused absences and zero tardies over a configurable period.
- Generate award list with printable/exportable certificates.

### F9. CSV / Excel Export Tool
- Export attendance records, tardy logs, absence logs, and analytics to CSV.
- Filter by date range, grade, section, or individual student.

## 5. User Flows

### Flow 1: Student Entry (QR Scan)
1. Student approaches scanner station (webcam/phone).
2. QR code is scanned.
3. System validates QR code → identifies student.
4. System records entry timestamp.
5. If after tardy threshold → mark "tardy" + trigger parent email.
6. Display confirmation on screen.

### Flow 2: Librarian Daily Review
1. Librarian logs in.
2. Views dashboard → sees today's attendance summary.
3. Reviews auto-flagged tardies/absences.
4. Reviews pending excuse slips.
5. Approves/rejects excuse slips.
6. Exports daily report to CSV.

### Flow 3: Parent Receives Alert
1. Student is marked tardy or absent.
2. System queues email alert to linked parent email.
3. Parent receives email with student name, date, time, and status.
4. Parent may log in to view attendance calendar (read-only).

### Flow 4: Excuse Slip Submission
1. Teacher/Parent fills digital excuse slip form (student, date range, reason, attachment optional).
2. Submits to pending queue.
3. Librarian reviews and approves/rejects.
4. If approved → linked attendance records updated to "excused."

## 6. Functional Requirements

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-01 | System must generate unique QR codes per student | Must |
| FR-02 | QR scan must record entry/exit within 2 seconds | Must |
| FR-03 | System must auto-calculate tardy based on opening time + threshold | Must |
| FR-04 | System must auto-mark absent if no entry by closing time | Must |
| FR-05 | Librarian must be able to manually add/edit attendance | Must |
| FR-06 | Excuse slips must support text reason and file upload (max 5MB, PDF/JPG/PNG) | Must |
| FR-07 | Parent email alerts must be sent within 5 minutes of tardy/absence flag | Must |
| FR-08 | Analytics dashboard must load within 3 seconds for 30-day view | Must |
| FR-09 | CSV export must include UTF-8 BOM for Excel compatibility | Must |
| FR-10 | Perfect attendance tool must allow custom date range selection | Should |
| FR-11 | System must show real-time "currently in library" count | Should |
| FR-12 | Attendance calendar must be printable | Should |

## 7. Non-Functional Requirements

| ID | Requirement |
|----|-------------|
| NFR-01 | Web app must work on Chrome, Firefox, Safari, Edge (latest 2 versions) |
| NFR-02 | QR scanning must work on any device with a camera and modern browser |
| NFR-03 | Database must support at least 10,000 students and 1M attendance records without performance degradation |
| NFR-04 | System must be deployable on standard LAMP stack (Linux, Apache, MySQL, PHP) |
| NFR-05 | All pages must load initial content within 2 seconds on standard broadband |
| NFR-06 | Email alerts must use queue/batch if volume exceeds 50/hour |
| NFR-07 | System must maintain 99.5% uptime during library operating hours |
| NFR-08 | Data must be backed up daily |

## 8. Business Rules

| ID | Rule |
|----|------|
| BR-01 | A student can only have one entry record per day unless exit is also recorded (re-entry allowed). |
| BR-02 | Tardy threshold is configurable per library (default: 15 minutes after opening time). |
| BR-03 | Absence is auto-calculated at closing time if no entry exists for that day. |
| BR-04 | An approved excuse slip converts all affected absence/tardy records to "excused" status. |
| BR-05 | A rejected excuse slip does not change attendance status. |
| BR-06 | Perfect attendance requires zero unexcused absences AND zero tardies in the selected period. |
| BR-07 | Parent alerts are sent only for unexcused tardies and unexcused absences. |
| BR-08 | Only librarians/admins can delete attendance records; deletions are logged. |
| BR-09 | Teachers can submit excuse slips only for students in their assigned sections (if section assignment exists). |
| BR-10 | CSV exports are limited to 10,000 rows per request to prevent memory exhaustion. |

## 9. Non-Goals (Explicitly Out of Scope)

- **No RFID hardware integration** — QR scanning via camera only. RFID fields exist for future expansion but are not functional.
- **No mobile native app** — web app responsive design only.
- **No SMS alerts** — email only.
- **No payment/fine system** — no financial transactions.
- **No integration with external school information systems** — standalone system.
- **No multi-library / multi-branch support** — single library instance.
- **No real-time push notifications** — email alerts only (no WebSockets/SSE).
- **No student self-registration** — all accounts created by admin.

## 10. Acceptance Criteria

### AC-01: QR Scanning
- Given a valid student QR code, when scanned, then an attendance record is created within 2 seconds with correct student ID and timestamp.

### AC-02: Tardy Detection
- Given library opens at 08:00 with 15-minute threshold, when a student scans at 08:16, then the record is marked "tardy" and a parent email is queued.

### AC-03: Absence Detection
- Given a student has no entry record for a day, when the daily batch runs at closing time, then an absence record is created.

### AC-04: Excuse Slip Workflow
- Given a submitted excuse slip, when a librarian approves it, then all affected attendance records change to "excused" and no parent alert is sent for those records.

### AC-05: Analytics Dashboard
- Given 30 days of attendance data, when the librarian opens the dashboard, then summary cards and charts render within 3 seconds.

### AC-06: CSV Export
- Given filtered attendance data, when export is requested, then a valid UTF-8 CSV file downloads with correct headers and data.

### AC-07: Parent Alert
- Given a student is marked tardy, when the alert job runs, then the linked parent receives an email within 5 minutes containing student name, date, and time.

### AC-08: Perfect Attendance
- Given a date range selection, when the award tool runs, then only students with zero unexcused absences and zero tardies in that range are listed.
