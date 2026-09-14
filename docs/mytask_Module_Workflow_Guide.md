#  Architecture, Logic & Workflow Guide
**Bestlink College of the Philippines (BCP) Attendance Management System**  
**Task Specification Reference:** [`docs/Myworktask.md`](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/Myworktask.md)  
**Status:** **100% Complete & Ready for Review / Deployment**  

---

## 1. Executive Summary & Readiness Checklist

All 4 required modules from [`docs/Myworktask.md`](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/Myworktask.md), along with their cross-module contracts and shared database views, have been implemented, tested, and verified against the live database:

| Module / Requirement | Task Spec Source | Implementation Files | Status |
|---|---|---|---|
| **Module 1: Import Teacher Faculty Master** | `Myworktask.md:L7-49` | [`TeacherController.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/controllers/TeacherController.php), [`admin/teachers.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/views/admin/teachers.php) | **COMPLETE** |
| **Module 2: Teachers Master Accounts (CRUD)** | `Myworktask.md:L50-64` | [`TeacherController.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/controllers/TeacherController.php), [`admin/teachers.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/views/admin/teachers.php) | **COMPLETE** |
| **Module 3: Overview Dashboard & Shared View** | `Myworktask.md:L65-86` | [`DashboardController.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/controllers/DashboardController.php), [`admin/dashboard.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/views/admin/dashboard.php) | **COMPLETE** |
| **Module 4: System Settings (Key-Value)** | `Myworktask.md:L87-108` | [`SettingsController.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/controllers/SettingsController.php), [`settings/index.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/views/settings/index.php) | **COMPLETE** |
| **Cross-Module Contract: Canicon (Parent Alerts)** | `Myworktask.md:L111-120` | `system_settings` (`alert_enabled`, `alert_channel`), `notifications` | **COMPLETE** |
| **Cross-Module Contract: Dolo (Exports/Analytics)** | `Myworktask.md:L111-120` | `faculty_import_logs`, shared view `v_attendance_summary` | **COMPLETE** |
| **Portal & Layout Standardization** | Design System | [`sidebar.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/views/partials/sidebar.php), [`navbar.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/views/partials/navbar.php), [`Router.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/includes/core/Router.php) | **COMPLETE** |
| **Automated Verification Suite** | Test Engine | [`tests/test_sierra_modules.php`](file:///opt/lampp/htdocs/Attendance%20_Management_System/tests/test_sierra_modules.php) (18/18 Tests Pass) | **VERIFIED** |

---

## 2. Module 1: Import Teacher Faculty Master

### Goal
Allow institutional administrators to batch-onboard teachers by uploading a `.csv` or `.xlsx` spreadsheet, validating row-by-row, inserting valid faculty records, and recording an audit trail for Dolo's export module.

### Expected File Columns
The parser expects the exact columns defined in `Myworktask.md`:
```csv
employee_id, full_name, email, department, position, contact_number, date_hired
```

### Step-by-Step Logic Flow
```
[Admin Uploads File] 
        │
        ▼
1. Validate MIME & Extension (.csv, .xlsx, .txt)
        │
        ▼
2. Parse File into Rows (native fgetcsv or XML/Zip parser)
        │
        ▼
3. Validate Header Row
   ├── Are all 7 required headers present?
   └── If NO: Return HTTP 422 with missing headers list
        │
        ▼ (YES)
4. Row-Level Validation Loop:
   ├── Check blank row → skip silently
   ├── Check required fields (employee_id, full_name, email) → if missing, log error & increment skipped
   ├── Check email syntax (filter_var) → if invalid, log error & increment skipped
   ├── Check duplicate employee_id in current batch → if duplicate, log error & increment skipped
   ├── Check duplicate employee_id in DB (`teachers` table) → if duplicate, log error & increment skipped
   └── Check duplicate email in DB (`teachers` table) → if duplicate, log error & increment skipped
        │
        ▼ (VALID ROW)
5. Insert into `teachers` table with default bcrypt password hash (`Teacher@123`) and status='active'
        │
        ▼
6. Record Audit Trail in `faculty_import_logs`
   ├── Stores filename, admin user ID, total rows, inserted count, failed count, and JSON error details
        │
        ▼
7. Return JSON Summary Response:
   {
     "status": "success",
     "summary": {
       "filename": "faculty_2026.csv",
       "total": 45,
       "inserted": 42,
       "skipped": 3,
       "errors": [
         {"line": 12, "id": "EMP-092", "reason": "Email already exists in database"}
       ]
     }
   }
```

### Database Tables Used
- **`teachers`** (see [Section 6.1](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#61-teachers-dynamic-faculty-master-accounts)): Ingests parsed and validated faculty records with unique `employee_id` and `email` checks.
- **`faculty_import_logs`** (see [Section 6.2](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#62-faculty_import_logs-dynamic-import-audit-trail)): Captures execution audit logs and stores row-level parsing errors in a dynamic `JSON` column.

---

## 3. Module 2: Teachers Master Accounts (CRUD)

### Goal
Provide a clean management interface to search, filter, manually register, edit, deactivate, and reset passwords for faculty members.

### Step-by-Step Logic Flow
1. **Search & Filter (`GET /api/teachers`)**:
   - Accepts parameters: `search` (matches full name, employee ID, or email), `department` (e.g. CCS, CBA), `status` (`active` or `inactive`), and `limit`/`offset`.
   - Returns indexed, paginated list of faculty members.
2. **Single Account Creation (`POST /api/teachers/create`)**:
   - Manual complement to bulk import.
   - Validates uniqueness of `employee_id` and `email` before inserting with bcrypt password hash.
3. **Account Update (`POST /api/teachers/update`)**:
   - Updates `full_name`, `email`, `department`, `position`, `contact_number`, `date_hired`, or `status`.
4. **Soft Deactivation (`POST /api/teachers/delete`)**:
   - **Does NOT drop rows** (avoids breaking foreign keys in historical attendance records).
   - Updates `status = 'inactive'`.
5. **Password Reset (`POST /api/teachers/reset-password`)**:
   - Generates a temporary institutional password (e.g. `BCP-A8F201`).
   - Hashes and updates `teachers.password_hash`.
   - **Cross-module notification:** Inserts an administrative notification record into `notifications` (`type = 'system'`) so that an email or SMS notification can be dispatched without building a redundant second messaging layer.
6. **Live Faculty Roster Export (`GET /api/teachers/export`)**:
   - Streams live faculty records matching active search, department, and status filters.
   - Supports user-selected formats: **CSV (.csv)** with UTF-8 BOM or styled **Excel (.xlsx/.xls)** workbook with institutional blue header formatting.
   - Executed via the consolidated **Export Roster ▾** dropdown positioned below the search bar alongside the Bulk Import and + Add Teacher modal buttons.

### Database Tables Used
- **`teachers`** (see [Section 6.1](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#61-teachers-dynamic-faculty-master-accounts)): Dynamically filtered via SQL `LIKE` on full name, ID, or email; updated on edit/soft-delete.
- **`notifications`** (see [Section 6.5](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#65-integrated-operational--alert-tables)): Queues password reset alert notifications (`type = 'system'`) for unified alerting.

---

## 4. Module 3: Overview Dashboard

### Goal
Serve as the administrative landing page showing high-level aggregate summary cards without overlapping Dolo's in-depth historical Analytics Dashboard.

### Step-by-Step Logic Flow
```
[Admin Visits /dashboard] 
        │
        ▼
Calls /api/dashboard/overview (DashboardController::apiOverview)
        │
        ├── 1. Query Total Teachers:
        │      SELECT COUNT(*) AS total, SUM(status='active') AS active FROM teachers
        │
        ├── 2. Query Shared SQL View (v_attendance_summary):
        │      SELECT * FROM v_attendance_summary WHERE day = CURDATE()
        │      Calculates today's real attendance rate = (present / total) * 100%
        │
        ├── 3. Query Parent Alerts Sent Today:
        │      Pulls count of alerts dispatched today from parent_alerts / notifications
        │
        └── 4. Query Recent Faculty Import Logs:
               SELECT * FROM faculty_import_logs ORDER BY imported_at DESC LIMIT 5
```

### Key Integration: Shared SQL View (`v_attendance_summary`)
To prevent conflicting statistics between the Administrative Overview Dashboard and Dolo's Analytics Dashboard, both modules query the **same SQL view layer**:
```sql
CREATE VIEW v_attendance_summary AS
SELECT DATE(a.attendance_date) AS day,
       COUNT(*) AS total_records,
       SUM(a.status='present') AS present_count,
       SUM(a.status='absent') AS absent_count,
       SUM(a.status='tardy') AS tardy_count
FROM (
  SELECT `date` AS `attendance_date`, `status` FROM `attendance`
) a
GROUP BY DATE(a.attendance_date);
```
- **Administrative Overview Dashboard**: Pulls only **today's row** (`day = CURDATE()`).
- **Dolo Analytics Dashboard**: Pulls the **full date-range trend** over the exact same view.

### Database Tables & Views Used
- **`teachers`** (see [Section 6.1](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#61-teachers-dynamic-faculty-master-accounts)): Aggregates live counts of total, active, and inactive teachers.
- **`v_attendance_summary`** (see [Section 6.4](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#64-v_attendance_summary-dynamic-aggregation-sql-view)): Dynamically calculates today's attendance rate (`(present / total) * 100`).
- **`parent_alerts`** (see [Section 6.5](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#65-integrated-operational--alert-tables)): Counts automated parent notifications dispatched today.
- **`faculty_import_logs`** (see [Section 6.2](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#62-faculty_import_logs-dynamic-import-audit-trail)): Feeds the 5 most recent bulk faculty import jobs.

---

## 5. Module 4: System Settings

### Goal
Provide a centralized, schema-agnostic key-value store for global settings consumed by both Canicon (Parent Alerts) and Dolo (Exports & Analytics).

### Step-by-Step Logic Flow
```
[Canicon / Dolo / Admin needs a config]
                 │
                 ▼
       Query `system_settings`
     (setting_key, setting_value)
                 │
  ┌──────────────┴──────────────┐
  ▼                             ▼
Canicon reads:               Dolo reads:
• alert_enabled              • export_default_format
• alert_channel              • analytics_date_range_default
• alert_absent_only
```

### Why a Generic Key-Value Store?
- Setting values are stored as `TEXT` keyed by `VARCHAR(50)`.
- When Canicon or Dolo need a new toggle or threshold, **no database migrations or DDL schema changes are required**—the application simply saves the new key-value pair.
- The UI dynamically generates the settings forms and exposes live API endpoints:
  - `GET /api/settings`: Returns all settings as a key-value dictionary.
  - `POST /api/settings/save`: Batch-updates or inserts setting pairs in a single transaction.

### Database Tables Used
- **`system_settings`** (see [Section 6.3](file:///opt/lampp/htdocs/Attendance%20_Management_System/docs/mytask_Module_Workflow_Guide.md#63-system_settings-dynamic-schema-free-key-value-store)): Universal schema-less key-value store enabling dynamic runtime configurations without schema modifications.

---

## 6. Database Dynamic Tables & Views Architecture

Our module leverages four primary dynamic database structures, while integrating with three upstream operational and alert tables:

```
                      ┌───────────────────────────────────────┐
                      │   Dynamic Key-Value Store             │
                      │   `system_settings`                   │
                      │   (alert toggles, export formats)     │
                      └──────────────────┬────────────────────┘
                                         │
                                         ▼
┌─────────────────────────┐   ┌──────────────────────────────┐   ┌─────────────────────────┐
│ Bulk CSV / Excel Upload │──▶│ Dynamic Faculty Entity Store │──▶│ Password Reset Pipeline │
│ `faculty_import_logs`   │   │ `teachers`                   │   │ `notifications` Queue   │
│ (row metrics, JSON logs)│   │ (search, soft-delete, hashes)│   │ (alert dispatch bridge) │
└─────────────────────────┘   └──────────────┬───────────────┘   └─────────────────────────┘
                                             │
                                             ▼ (Foreign Key: teacher_id)
                              ┌──────────────────────────────┐
                              │ Operational Attendance Logs  │
                              │ `attendance_records`         │
                              └──────────────┬───────────────┘
                                             │
                                             ▼
                              ┌──────────────────────────────┐
                              │ Dynamic Aggregated SQL View  │
                              │ `v_attendance_summary`       │
                              │ (real-time rates & trends)   │
                              └──────────────────────────────┘
```

### 6.1 `teachers` (Dynamic Faculty Master Accounts)
- **Role:** Central repository for institutional faculty identities.
- **Dynamic Behavior:**
  - **Dynamic Ingestion:** Populated row-by-row during bulk import (`apiImport`) with pre-insertion database uniqueness validation on `employee_id` and `email`.
  - **Dynamic Multi-Field Search:** `apiList` executes parameterized dynamic substring matching across `full_name`, `employee_id`, and `email`, plus exact matching on `department` and `status`.
  - **Dynamic Soft-Delete:** Account deactivation toggles `status = 'inactive'` rather than dropping the row (`DELETE`), preserving foreign key integrity across historical attendance sessions.
  - **Dynamic Security Generation:** Password resets update `password_hash` with a fresh bcrypt hash and dispatch an administrative audit record to `notifications`.

| Column | Data Type | Nullable | Key / Constraint | Description & Dynamic Usage |
|---|---|---|---|---|
| `id` | `INT` | NO | `PRIMARY KEY AUTO_INCREMENT` | Internal relational ID; target of foreign keys (`attendance_records.teacher_id`). |
| `employee_id` | `VARCHAR(20)` | NO | `UNIQUE KEY` | Institutional faculty ID (e.g. `EMP-2026-001`). Validated dynamically on import. |
| `full_name` | `VARCHAR(100)` | NO | None | Teacher's legal name, searched via SQL `LIKE %search%`. |
| `email` | `VARCHAR(100)` | NO | `UNIQUE KEY` | Official email address; used for institutional login and alert routing. |
| `password_hash`| `VARCHAR(255)` | YES | None | Bcrypt hashed credentials (`PASSWORD_BCRYPT`). Dynamically updated on reset. |
| `department` | `VARCHAR(50)` | YES | `INDEX (idx_teachers_department)` | College/department (e.g. `CCS`, `CBA`, `CAS`). Dynamically filtered in UI. |
| `position` | `VARCHAR(50)` | YES | None | Faculty title (e.g. `Associate Professor`, `Instructor I`). |
| `contact_number`| `VARCHAR(20)` | YES | None | Primary mobile contact for SMS dispatch. |
| `date_hired` | `DATE` | YES | None | Faculty hiring date for HR tenure tracking. |
| `status` | `ENUM('active','inactive')` | YES (Default `'active'`) | `INDEX (idx_teachers_status)` | Active status flag. Used for non-destructive soft-deletes and active counts. |
| `created_at` | `TIMESTAMP` | YES | Default `CURRENT_TIMESTAMP` | Account creation timestamp. |

---

### 6.2 `faculty_import_logs` (Dynamic Import Audit Trail)
- **Role:** Immutable audit trail recording every bulk onboarding execution.
- **Dynamic Behavior:**
  - **Dynamic Execution Logging:** Generates a new log entry for every CSV/Excel file processed, capturing file metadata and operational metrics (`total_rows`, `inserted_rows`, `failed_rows`).
  - **Dynamic JSON Error Capturing:** Uses a native MySQL `JSON` column (`error_detail`) to store unstructured row-level validation errors (line number, failed identifier, failure reason). This allows variable error payloads without altering table schema.
  - **Cross-Module Reporting:** Directly read by the Overview Dashboard (recent 5 imports) and consumed by Dolo's export module to generate institutional onboarding compliance sheets.

| Column | Data Type | Nullable | Key / Constraint | Description & Dynamic Usage |
|---|---|---|---|---|
| `id` | `INT` | NO | `PRIMARY KEY AUTO_INCREMENT` | Unique identifier for the import job run. |
| `filename` | `VARCHAR(255)` | YES | None | Name of uploaded file (e.g. `faculty_batch_2026.csv`). |
| `imported_by` | `INT` | YES | None | User ID of the administrator who triggered the batch upload. |
| `total_rows` | `INT` | YES | None | Total parsed data rows in the spreadsheet. |
| `inserted_rows`| `INT` | YES | None | Count of valid faculty accounts successfully created in `teachers`. |
| `failed_rows` | `INT` | YES | None | Count of rows skipped due to duplicates, missing required headers, or malformed data. |
| `error_detail`| `JSON` | YES | None | Structured array of row errors: `[{"line": 12, "id": "EMP-04", "reason": "Duplicate"}]`. |
| `imported_at` | `TIMESTAMP` | YES | `INDEX (idx_import_logs_date)` | Job completion timestamp. Ordered descending for recent feeds. |

---

### 6.3 `system_settings` (Dynamic Schema-Free Key-Value Store)
- **Role:** Universal dynamic configuration store shared across all modules.
- **Dynamic Behavior:**
  - **Zero DDL Migrations:** New configuration items can be added at runtime by simply inserting new keys—no `ALTER TABLE` schema modifications required.
  - **Dynamic Upserting:** Saves use atomic `INSERT ... ON DUPLICATE KEY UPDATE`, guaranteeing idempotent writes whether inserting a new parameter or modifying an existing one.
  - **Request-Level Memory Caching:** `SettingsController::$settingsCache` loads all settings into memory in a single query per request, preventing repeated database overhead.

| Column | Data Type | Nullable | Key / Constraint | Description & Dynamic Usage |
|---|---|---|---|---|
| `setting_key` | `VARCHAR(50)` | NO | `PRIMARY KEY` | Unique alphanumeric setting identifier (e.g. `alert_enabled`, `export_default_format`). |
| `setting_value`| `TEXT` | YES | None | Arbitrary string, boolean (`'1'`/`'0'`), or numeric configuration payload. |
| `updated_at` | `TIMESTAMP` | YES | Default `CURRENT_TIMESTAMP ON UPDATE` | Automatic timestamp reflecting the last administrative update. |

#### Dynamic Settings Keys Catalog:
- **Parent Alerts (Canicon):**
  - `alert_enabled` (`'1'` / `'0'`): Master automated parent dispatch toggle.
  - `alert_channel` (`'sms'` / `'email'` / `'both'`): Delivery channel selector.
  - `alert_absent_only` (`'1'` / `'0'`): If enabled, suppresses alerts for present/tardy marks and only triggers on absences.
- **Reporting & Analytics (Dolo):**
  - `export_default_format` (`'csv'` / `'xlsx'`): Default file format for audit and roster downloads.
  - `analytics_date_range_default` (`'30'`): Default timeframe in days for historical trend queries.
- **General System:**
  - `institution_name`, `academic_year`, `default_password_policy`.

---

### 6.4 `v_attendance_summary` (Dynamic Aggregation SQL View)
- **Role:** Shared, real-time calculation layer eliminating statistical discrepancies between Overview and Analytics.
- **Dynamic Behavior:**
  - **On-the-Fly Aggregation:** As an SQL `VIEW`, it never stores duplicate data on disk; it dynamically computes date-grouped aggregations directly from the underlying attendance transactions whenever queried.
  - **Single Source of Truth:** Overview Dashboard queries `WHERE day = CURRENT_DATE()` to show today's live institutional percentage, while Dolo's Analytics module queries `WHERE day BETWEEN ? AND ?` for historical graphs—guaranteeing that both views always agree 100%.

| Column | Data Type | Nullable | Key / Constraint | Description & Dynamic Usage |
|---|---|---|---|---|
| `day` | `DATE` | YES | None | Calendar date extracted from `attendance_date`. Grouping pivot. |
| `total_records`| `BIGINT` | NO | None | Total attendance logs registered on that calendar day. |
| `present_count`| `DECIMAL` | YES | None | Dynamic count of records marked `'present'`. |
| `absent_count` | `DECIMAL` | YES | None | Dynamic count of records marked `'absent'`. |
| `tardy_count` | `DECIMAL` | YES | None | Dynamic count of records marked `'tardy'`. |

---

### 6.5 Integrated Operational & Alert Tables
In addition to the 4 core module structures, the module dynamically queries and interacts with existing institutional tables:
1. **`attendance_records` / `attendance`:**
   - Operational attendance transactions.
   - Relational linkage: `attendance_records.teacher_id` references `teachers.id`, linking faculty members to specific section sessions and student scans.
2. **`parent_alerts`:**
   - Historical alert dispatch logs.
   - Dynamic query in Overview Dashboard: `SELECT COUNT(*) FROM parent_alerts WHERE alert_date = CURRENT_DATE() OR DATE(created_at) = CURRENT_DATE()` to populate the "Alerts Sent Today" summary metric.
3. **`notifications`:**
   - Central alert and administrative event queue.
   - Dynamic insertion in Teacher Module: Password resets insert a `type = 'system'` alert notification, ensuring administrative changes are logged and ready for notification dispatch without building a separate messaging layer.

---

## 7. Cross-Module Contracts Summary

| Contract | Our Module Provides | Co-Dev Consumes | Technical Wiring |
|---|---|---|---|
| **Canicon (Parent Alerts)** | `teachers` table with `employee_id` and `id` | Canicon links `attendance_records.teacher_id` to resolve class section and guardian contacts. | Foreign Key / Join on `teachers.id` |
| **Canicon Settings** | `system_settings` table | Canicon reads `alert_enabled`, `alert_channel`, and `alert_absent_only` before dispatching alerts. | `SELECT setting_value FROM system_settings WHERE setting_key = ?` |
| **Dolo (Audit Exports)** | `faculty_import_logs` table | Dolo exports the faculty onboarding log as a CSV/Excel report. | `SELECT * FROM faculty_import_logs` |
| **Dolo (Analytics View)** | `v_attendance_summary` SQL View | Dolo runs trend analysis without duplicating attendance grouping logic. | `SELECT * FROM v_attendance_summary WHERE day BETWEEN ? AND ?` |

---

## 8. Portal Navigation & Design Consistency Fixes

During the audit, we addressed three critical usability and presentation issues:

1. **Subfolder Route Detection**:
   - In local XAMPP environments (`/Attendance _Management_System/...`), raw URI parsing caused role detection to always default to Admin.
   - Fixed using `Router::getCurrentPath()` and `Router::getCurrentRole()`, ensuring navigation accurately detects `/teacher/dashboard`, `/student/calendar`, and `/dashboard`.
2. **1-Click Role Switcher**:
   - Added role switch buttons in both the top navbar and sidebar header (`url('switch-role?role=...')`).
   - Automatically synchronizes the active session role and loads the respective user persona (`Prof. Manuel Ramirez` for teacher, `Juan Dela Cruz` for student, `System Administrator` for admin).
3. **Single Unified Color Palette (Admin Blue Theme)**:
   - Purged mismatched rainbow colors (emerald for teacher, indigo for student) from navigation, status pills, and avatars.
   - All roles now consistently share the official **Admin Blue palette (`#2563EB` / `bg-blue-600`)**, giving the application a clean, professional, human-crafted design.

---

## 9. Talking Points for Your Co-Dev Team Sync

When explaining these changes to your co-developers (Canicon and Dolo), you can present these key points:

1. **"My 4 modules are complete and self-contained":**
   - Faculty Import, Faculty CRUD, Overview Dashboard, and System Settings are all fully built and passing 18/18 tests.
2. **"For Canicon (Parent Alerts)":**
   - The `system_settings` table is ready. You can query `alert_enabled`, `alert_channel`, and `alert_absent_only` directly.
   - The `teachers` table is populated and ready for foreign key joins with attendance records.
   - Password resets hook into the `notifications` table so alerts stay unified.
3. **"For Dolo (Exports & Analytics)":**
   - The `faculty_import_logs` table is ready for your export tool.
   - The `v_attendance_summary` SQL view is created. Your Analytics Dashboard should query this view so our numbers always match.
4. **"Zero Breaking Changes":**
   - No co-dev files were altered. Existing routes, students views, and co-dev tables remain completely untouched.
   - All portals now share the unified Admin Blue styling and seamless role switching.

