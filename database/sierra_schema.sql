-- ==============================================================================
-- Sierra Modules Schema Definition (Teachers, Import Logs, Settings, Views)
-- Based on docs/Myworktask.md
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Teachers Table
CREATE TABLE IF NOT EXISTS `teachers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` VARCHAR(20) UNIQUE NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `department` VARCHAR(50) DEFAULT NULL,
  `position` VARCHAR(50) DEFAULT NULL,
  `contact_number` VARCHAR(20) DEFAULT NULL,
  `date_hired` DATE DEFAULT NULL,
  `status` ENUM('active','inactive') DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_teachers_department` (`department`),
  KEY `idx_teachers_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Faculty Import Logs (Audit trail for bulk imports)
CREATE TABLE IF NOT EXISTS `faculty_import_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `filename` VARCHAR(255),
  `imported_by` INT,
  `total_rows` INT,
  `inserted_rows` INT,
  `failed_rows` INT,
  `error_detail` JSON,
  `imported_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_import_logs_date` (`imported_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. System Settings (Global generic key-value store)
CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` VARCHAR(50) PRIMARY KEY,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Attendance Records View (Cross-module compatibility)
CREATE OR REPLACE VIEW `attendance_records` AS
SELECT 
  `attendance_id`,
  `student_id`,
  `teacher_id`,
  `qr_session_id`,
  `date` AS `attendance_date`,
  `date` AS `record_date`,
  `time` AS `entry_time`,
  `subject`,
  `status`,
  `created_at`,
  `updated_at`
FROM `attendance`;

-- 5. Shared SQL View: v_attendance_summary (Sierra Overview & Dolo Analytics)
CREATE OR REPLACE VIEW `v_attendance_summary` AS
SELECT DATE(a.attendance_date) AS `day`,
       COUNT(*) AS `total_records`,
       SUM(a.status = 'present') AS `present_count`,
       SUM(a.status = 'absent') AS `absent_count`,
       SUM(a.status = 'tardy') AS `tardy_count`
FROM (
  SELECT `date` AS `attendance_date`, `status` FROM `attendance`
) a
GROUP BY DATE(a.attendance_date);

SET FOREIGN_KEY_CHECKS = 1;
