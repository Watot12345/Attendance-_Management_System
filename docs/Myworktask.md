# Sierra Task Spec — Teacher Attendance System (PHP + MySQL)

Context doc for agentic AI coder. Covers Sierra's 4 modules + how they wire into Dolo (exports/analytics) and Canicon (parent alerts).

## Sierra's Modules

### 1. Import Teacher Faculty Master
Bulk import teacher/faculty accounts from CSV/Excel.

**Input file columns (expected):**
`employee_id, full_name, email, department, position, contact_number, date_hired`

**Flow:**
1. Upload file → validate headers → parse rows (PhpSpreadsheet for xlsx, `fgetcsv` for csv)
2. Row-level validation: required fields, email format, duplicate `employee_id` check
3. Insert valid rows into `teachers` table, log skipped/failed rows
4. Return import summary: `{total, inserted, skipped, errors[]}`

**DB table: `teachers`**
```sql
CREATE TABLE teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  employee_id VARCHAR(20) UNIQUE NOT NULL,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(100) UNIQUE NOT NULL,
  password_hash VARCHAR(255),
  department VARCHAR(50),
  position VARCHAR(50),
  contact_number VARCHAR(20),
  date_hired DATE,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Import log table (audit trail — Dolo's export module can pull from this too):**
```sql
CREATE TABLE faculty_import_logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255),
  imported_by INT,          -- admin user id
  total_rows INT,
  inserted_rows INT,
  failed_rows INT,
  error_detail JSON,
  imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2. Teachers Master Accounts
CRUD screen over `teachers` table.
- List/search/filter (by dept, status)
- Create single account (manual, complements bulk import)
- Edit / deactivate (soft delete via `status`)
- Reset password → generates temp password, should trigger same notification path Canicon uses for alerts (reuse mail/SMS layer, don't build a second one)

**Endpoints (suggested):**
```
GET    /api/teachers
POST   /api/teachers
PUT    /api/teachers/{id}
DELETE /api/teachers/{id}   -- soft delete
```

### 3. Overview Dashboard
High-level summary cards + widgets. This is the "front page" — pulls aggregate counts, NOT raw analytics (that's Dolo's Analytics Dashboard — keep scope split clean so you don't build two dashboards doing the same thing).

**Suggested widgets:**
- Total teachers (active/inactive)
- Today's attendance rate (needs attendance table — owned by whoever builds attendance-taking module, not listed here; assume table `attendance_records`)
- Alerts sent today (count from Canicon's `parent_alerts` table)
- Recent faculty imports (from `faculty_import_logs`)

**Key integration point:** Overview Dashboard and Dolo's Analytics Dashboard should query the **same underlying views**, not duplicate logic. Suggest a shared SQL view layer:

```sql
CREATE VIEW v_attendance_summary AS
SELECT DATE(a.attendance_date) AS day,
       COUNT(*) AS total_records,
       SUM(a.status='present') AS present_count,
       SUM(a.status='absent') AS absent_count
FROM attendance_records a
GROUP BY DATE(a.attendance_date);
```
Overview Dashboard = today's row from this view. Analytics Dashboard (Dolo) = full trend over this view.

### 4. System Settings
Global config table, consumed by other modules — build this FIRST since Canicon + Dolo both depend on it.

```sql
CREATE TABLE system_settings (
  setting_key VARCHAR(50) PRIMARY KEY,
  setting_value TEXT,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Keys Canicon needs (parent alerts):**
- `alert_enabled` (bool) — master on/off switch
- `alert_channel` (sms/email/both)
- `alert_absent_only` (bool) — alert only on absent, or every attendance mark

**Keys Dolo needs (export/analytics):**
- `export_default_format` (csv/xlsx)
- `analytics_date_range_default` (e.g. 30 days)

Build settings page with a generic key-value form so new keys don't need schema changes.

---

## Cross-Module Contracts

| From → To | What's shared | How |
|---|---|---|
| Sierra `teachers` → Canicon alerts | teacher_id on attendance record → resolves to which class/section → parent contact | FK: `attendance_records.teacher_id → teachers.id` |
| Sierra `system_settings` → Canicon | alert toggle/channel/threshold | Canicon reads settings before sending, don't hardcode |
| Sierra `faculty_import_logs` → Dolo export | import history as exportable report | Dolo's export module just needs table name, no new logic |
| Sierra `teachers`, `attendance_records` → Dolo analytics | raw data source | Use `v_attendance_summary` view above, don't re-query raw tables separately |
| Sierra Overview Dashboard ↔ Dolo Analytics Dashboard | same view layer | prevents duplicate/conflicting numbers |

## Build Order (recommended)
1. `teachers` table + Import module (unblocks everything downstream)
2. `system_settings` (Canicon + Dolo need this early)
3. Teachers Master Accounts CRUD
4. `v_attendance_summary` view (coordinate w/ whoever owns `attendance_records`)
5. Overview Dashboard (consumes view)
6. Hand off to Canicon/Dolo to build their pieces against the above contracts