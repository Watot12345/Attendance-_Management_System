# Data Model Document
# Library Attendance Monitoring System (LAMS)

## 1. Canonical Conventions

| Convention | Value | Rationale |
|------------|-------|-----------|
| **Primary Key Type** | `INT UNSIGNED AUTO_INCREMENT` | Native PHP + MySQL simplicity; sufficient for single-library scale |
| **Foreign Key Naming** | `{referenced_table}_id` | Clarity and consistency |
| **Timestamp Fields** | `created_at`, `updated_at` | `DATETIME` with `DEFAULT CURRENT_TIMESTAMP` / `ON UPDATE CURRENT_TIMESTAMP` |
| **Soft Deletes** | `deleted_at DATETIME NULL` | Preserve data integrity; actual deletion only for test data by admin |
| **Status Fields** | `ENUM` for fixed states, `VARCHAR` for extensible states | Performance vs flexibility trade-off documented per table |
| **Boolean Fields** | `TINYINT(1)` with `DEFAULT 0` | MySQL standard |
| **Email Fields** | `VARCHAR(255)` with application-level validation | RFC 5321 length limit |
| **Phone Fields** | `VARCHAR(20)` | International format support |
| **File Paths** | `VARCHAR(500)` | Relative path from project root |
| **QR/RFID Codes** | `VARCHAR(100) UNIQUE` | Allows alphanumeric + symbols; indexed for scan lookup |
| **Naming** | `snake_case` for tables, columns, and files | PHP/MySQL convention |

## 2. Entities

### 2.1 users
Authentication and base identity for all human users.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| user_id | INT UNSIGNED | PK, AUTO_INCREMENT | Unique identifier |
| role | ENUM('student','teacher','staff','admin','parent') | NOT NULL, DEFAULT 'student' | System role |
| email | VARCHAR(255) | NOT NULL, UNIQUE | Login credential; also used for alerts to parents |
| password_hash | VARCHAR(255) | NOT NULL | bcrypt hash via `password_hash()` |
| first_name | VARCHAR(100) | NOT NULL | |
| last_name | VARCHAR(100) | NOT NULL | |
| phone | VARCHAR(20) | NULL | Optional contact number |
| avatar_path | VARCHAR(500) | NULL | Relative path to profile image |
| status | ENUM('active','inactive','suspended') | NOT NULL, DEFAULT 'active' | |
| last_login_at | DATETIME | NULL | |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | |
| updated_at | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |
| deleted_at | DATETIME | NULL | Soft delete timestamp |

**Validation Rules:**
- `email`: valid email format, unique across system
- `password_hash`: minimum 8 characters at registration (hashed before storage)
- `first_name`, `last_name`: 2-100 characters, letters/spaces/hyphens only
- `phone`: optional, digits/spaces/plus/hyphens only

**Delete Behavior:**
- Soft delete only. `deleted_at` populated by application.
- Cascading: student records, attendance records, and excuse slips retained for audit.
- Parent deletion: alerts history retained; future alerts stopped.

---

### 2.2 students
Role-specific extension for students. One-to-one with `users`.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| student_id | INT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NOT NULL, UNIQUE, FK → users(user_id) ON DELETE CASCADE | |
| student_code | VARCHAR(50) | NOT NULL, UNIQUE | Institution-issued ID (e.g., LRN) |
| grade_level | VARCHAR(20) | NOT NULL | e.g., "Grade 7", "Grade 8" |
| section | VARCHAR(50) | NOT NULL | e.g., "Section A", "Section B" |
| qr_code | VARCHAR(100) | NOT NULL, UNIQUE | System-generated unique QR string |
| rfid_tag | VARCHAR(100) | NULL, UNIQUE | Reserved for future RFID; nullable |
| parent_user_id | INT UNSIGNED | NULL, FK → users(user_id) ON DELETE SET NULL | Linked parent account |
| enrollment_date | DATE | NOT NULL | |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | |
| updated_at | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Validation Rules:**
- `student_code`: alphanumeric, 3-50 characters, unique
- `grade_level`: 1-20 characters
- `section`: 1-50 characters
- `qr_code`: 10-100 characters, URL-safe base64 or UUID v4 string
- `rfid_tag`: if provided, 5-100 characters, unique
- `parent_user_id`: must reference a user with `role = 'parent'`

**Uniqueness:**
- UNIQUE(`user_id`)
- UNIQUE(`student_code`)
- UNIQUE(`qr_code`)
- UNIQUE(`rfid_tag`) — partial, NULL values allowed

---

### 2.3 teachers
Role-specific extension for teachers. One-to-one with `users`.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| teacher_id | INT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NOT NULL, UNIQUE, FK → users(user_id) ON DELETE CASCADE | |
| employee_id | VARCHAR(50) | NOT NULL, UNIQUE | Institution-issued employee ID |
| department | VARCHAR(100) | NULL | e.g., "Science", "Mathematics" |
| subject | VARCHAR(100) | NULL | Primary subject taught |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | |
| updated_at | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Validation Rules:**
- `employee_id`: alphanumeric, 3-50 characters, unique
- `department`, `subject`: 1-100 characters

---

### 2.4 attendance_records
Central attendance log for all users (students and teachers).

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| attendance_id | INT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NOT NULL, FK → users(user_id) ON DELETE CASCADE | |
| record_date | DATE | NOT NULL | Date of attendance |
| entry_time | TIME | NULL | Time of entry |
| exit_time | TIME | NULL | Time of exit |
| direction | ENUM('entry','exit') | NULL | For scan-based records |
| method | ENUM('qr_scan','rfid_scan','manual') | NOT NULL, DEFAULT 'manual' | How recorded |
| status | ENUM('present','tardy','absent','excused') | NOT NULL, DEFAULT 'present' | Computed status |
| is_excused | TINYINT(1) | NOT NULL, DEFAULT 0 | Set by approved excuse slip |
| scanned_by | INT UNSIGNED | NULL, FK → users(user_id) ON DELETE SET NULL | Librarian who manually entered |
| notes | TEXT | NULL | Optional notes |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | |
| updated_at | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Validation Rules:**
- `record_date`: not in future
- `entry_time`: if provided, must be before `exit_time` on same date
- `exit_time`: optional; null if student still in library
- `scanned_by`: must reference a user with `role IN ('admin','staff')`
- Composite: A user may have multiple entry/exit pairs per day (re-entry allowed)

**Indexes:**
- INDEX(`user_id`, `record_date`) — primary lookup
- INDEX(`record_date`, `status`) — daily reporting
- INDEX(`status`, `record_date`) — tardy/absence queries

**Business Logic:**
- Status is computed: 
  - If `entry_time` exists and ≤ (opening_time + threshold) → `present`
  - If `entry_time` exists and > (opening_time + threshold) → `tardy`
  - If no `entry_time` by closing time → `absent`
  - If `is_excused = 1` → `excused` (overrides tardy/absent)

---

### 2.5 excuse_slips
Digital excuse slip submissions.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| slip_id | INT UNSIGNED | PK, AUTO_INCREMENT | |
| student_user_id | INT UNSIGNED | NOT NULL, FK → users(user_id) ON DELETE CASCADE | |
| submitted_by | INT UNSIGNED | NOT NULL, FK → users(user_id) ON DELETE CASCADE | Teacher or parent |
| reason | TEXT | NOT NULL | Explanation |
| attachment_path | VARCHAR(500) | NULL | Relative path to uploaded file |
| date_from | DATE | NOT NULL | Start of affected period |
| date_to | DATE | NOT NULL | End of affected period |
| status | ENUM('pending','approved','rejected') | NOT NULL, DEFAULT 'pending' | |
| reviewed_by | INT UNSIGNED | NULL, FK → users(user_id) ON DELETE SET NULL | Librarian/admin |
| reviewed_at | DATETIME | NULL | |
| review_notes | TEXT | NULL | |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | |
| updated_at | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Validation Rules:**
- `date_from` ≤ `date_to`
- `date_to` not more than 30 days from `date_from` (prevent abuse)
- `date_from` not in future (slip must be for past or current date)
- `attachment_path`: if provided, file must exist in `/uploads/excuse-slips/`
- `submitted_by`: must be teacher, parent, or admin
- `reviewed_by`: must be admin or staff

**Business Logic:**
- On approval: all `attendance_records` for `student_user_id` between `date_from` and `date_to` with status `tardy` or `absent` are updated to `excused` (`is_excused = 1`).
- On rejection: no attendance records modified.

---

### 2.6 parent_alerts
Email alert log.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| alert_id | INT UNSIGNED | PK, AUTO_INCREMENT | |
| recipient_email | VARCHAR(255) | NOT NULL | Parent email (denormalized for audit) |
| student_user_id | INT UNSIGNED | NOT NULL, FK → users(user_id) ON DELETE CASCADE | |
| alert_type | ENUM('tardy','absent','perfect_attendance','system') | NOT NULL | |
| subject | VARCHAR(255) | NOT NULL | Email subject |
| body | TEXT | NOT NULL | Email body (HTML or plain) |
| sent_at | DATETIME | NULL | NULL until successfully sent |
| status | ENUM('pending','sent','failed') | NOT NULL, DEFAULT 'pending' | |
| error_message | TEXT | NULL | If failed |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | |

**Validation Rules:**
- `recipient_email`: valid email format
- `student_user_id`: must reference a student

**Indexes:**
- INDEX(`student_user_id`, `alert_type`, `created_at`) — alert history lookup
- INDEX(`status`, `created_at`) — pending alerts queue

---

### 2.7 perfect_attendance_awards
Award records.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| award_id | INT UNSIGNED | PK, AUTO_INCREMENT | |
| student_user_id | INT UNSIGNED | NOT NULL, FK → users(user_id) ON DELETE CASCADE | |
| period_start | DATE | NOT NULL | |
| period_end | DATE | NOT NULL | |
| award_type | ENUM('monthly','quarterly','semester','custom') | NOT NULL | |
| generated_at | DATETIME | NOT NULL | When award was computed |
| generated_by | INT UNSIGNED | NOT NULL, FK → users(user_id) ON DELETE CASCADE | Admin who ran tool |
| certificate_path | VARCHAR(500) | NULL | Path to generated certificate PDF/image |
| notified_parent | TINYINT(1) | NOT NULL, DEFAULT 0 | Whether parent was emailed |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | |

**Validation Rules:**
- `period_start` ≤ `period_end`
- `student_user_id`: must reference a student with zero unexcused absences and zero tardies in the period

---

### 2.8 library_settings
Key-value configuration.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| setting_id | INT UNSIGNED | PK, AUTO_INCREMENT | |
| setting_key | VARCHAR(100) | NOT NULL, UNIQUE | |
| setting_value | TEXT | NOT NULL | |
| description | VARCHAR(255) | NULL | Human-readable description |
| updated_by | INT UNSIGNED | NULL, FK → users(user_id) ON DELETE SET NULL | |
| updated_at | DATETIME | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | |

**Required Settings:**

| Key | Default | Description |
|-----|---------|-------------|
| library_open_time | 08:00:00 | Daily opening time |
| library_close_time | 17:00:00 | Daily closing time |
| tardy_threshold_minutes | 15 | Minutes after open_time before tardy |
| auto_absence_enabled | 1 | Whether to auto-mark absent at close_time |
| alert_tardy_enabled | 1 | Send email on tardy |
| alert_absent_enabled | 1 | Send email on absent |
| alert_perfect_attendance_enabled | 1 | Send email on perfect attendance award |
| max_excuse_days | 30 | Max days per excuse slip |
| csv_max_rows | 10000 | Export row limit |
| timezone | Asia/Manila | PHP timezone |

---

### 2.9 audit_logs
Administrative action tracking.

| Field | Type | Constraints | Description |
|-------|------|-------------|-------------|
| log_id | INT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | INT UNSIGNED | NULL, FK → users(user_id) ON DELETE SET NULL | Actor |
| action | VARCHAR(100) | NOT NULL | e.g., 'attendance.delete', 'excuse.approve' |
| entity_type | VARCHAR(50) | NOT NULL | Table name affected |
| entity_id | INT UNSIGNED | NOT NULL | PK of affected record |
| old_values | JSON | NULL | Previous state |
| new_values | JSON | NULL | New state |
| ip_address | VARCHAR(45) | NULL | IPv4 or IPv6 |
| user_agent | VARCHAR(255) | NULL | Browser agent |
| created_at | DATETIME | DEFAULT CURRENT_TIMESTAMP | |

**Indexes:**
- INDEX(`user_id`, `created_at`)
- INDEX(`entity_type`, `entity_id`)
- INDEX(`action`, `created_at`)

---

## 3. Entity Relationship Diagram (Text)

```
users ||--o{ students : "has one"
users ||--o{ teachers : "has one"
users ||--o{ attendance_records : "has many"
users ||--o{ excuse_slips : "submits many"
users ||--o{ excuse_slips : "reviews many"
users ||--o{ parent_alerts : "receives about"
users ||--o{ perfect_attendance_awards : "receives"
users ||--o{ audit_logs : "performs"

students ||--o{ attendance_records : "has many"
students ||--o{ excuse_slips : "has many"
students ||--o{ parent_alerts : "alerted about"
students ||--o{ perfect_attendance_awards : "has many"

attendance_records }o--|| excuse_slips : "excused by"
```

## 4. Data Lifecycle

### 4.1 Attendance Records
- **Created**: On QR scan, manual entry, or batch absence job.
- **Updated**: On exit scan, excuse slip approval, or manual correction.
- **Retained**: Minimum 3 academic years (configurable).
- **Archived**: After 3 years, moved to `attendance_records_archive` table or exported and deleted.

### 4.2 Excuse Slips
- **Created**: On submission.
- **Updated**: On review (approve/reject).
- **Retained**: Permanently for audit.

### 4.3 Parent Alerts
- **Created**: On trigger event.
- **Updated**: On send success/failure.
- **Retained**: 1 year, then purged.

### 4.4 Audit Logs
- **Created**: On every destructive or sensitive action.
- **Retained**: 2 years, then purged.

### 4.5 Users
- **Soft Deleted**: On admin action.
- **Hard Deleted**: Never (unless explicitly requested and approved).

## 5. Constraints Summary

| Constraint | Tables | Enforcement |
|------------|--------|-------------|
| Email uniqueness | users | DB UNIQUE + app validation |
| QR code uniqueness | students | DB UNIQUE |
| Student code uniqueness | students | DB UNIQUE |
| Employee ID uniqueness | teachers | DB UNIQUE |
| Parent must be role=parent | students.parent_user_id | Application validation |
| Date range validity | excuse_slips | Application validation |
| File existence | excuse_slips.attachment_path | Application validation |
| Status transitions | excuse_slips | Application logic (pending→approved/rejected only) |
