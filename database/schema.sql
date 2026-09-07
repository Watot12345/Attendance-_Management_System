-- ==============================================================================
-- Bestlink College of the Philippines — Library Attendance Monitoring System
-- Database Schema Definition (Aiven MySQL / MySQL 8.0+)
-- ==============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Users Table (Base identity & credentials)
CREATE TABLE IF NOT EXISTS `users` (
  `user_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `role` ENUM('student','teacher','staff','admin','parent') NOT NULL DEFAULT 'student',
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `first_name` VARCHAR(100) NOT NULL,
  `last_name` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `avatar_path` VARCHAR(500) DEFAULT NULL,
  `status` ENUM('active','inactive','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `idx_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Students Table (Role extension for students)
CREATE TABLE IF NOT EXISTS `students` (
  `student_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `student_code` VARCHAR(50) NOT NULL,
  `grade_level` VARCHAR(20) NOT NULL,
  `section` VARCHAR(50) NOT NULL,
  `qr_code` VARCHAR(100) NOT NULL,
  `rfid_tag` VARCHAR(100) DEFAULT NULL,
  `parent_user_id` INT UNSIGNED DEFAULT NULL,
  `enrollment_date` DATE NOT NULL DEFAULT (CURRENT_DATE),
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`student_id`),
  UNIQUE KEY `idx_students_user_id` (`user_id`),
  UNIQUE KEY `idx_students_code` (`student_code`),
  UNIQUE KEY `idx_students_qr` (`qr_code`),
  UNIQUE KEY `idx_students_rfid` (`rfid_tag`),
  KEY `idx_students_grade_section` (`grade_level`, `section`),
  KEY `fk_students_parent` (`parent_user_id`),
  CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_students_parent` FOREIGN KEY (`parent_user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Teachers Table (Role extension for faculty)
CREATE TABLE IF NOT EXISTS `teachers` (
  `teacher_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `employee_id` VARCHAR(50) NOT NULL,
  `department` VARCHAR(100) DEFAULT NULL,
  `subject` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`teacher_id`),
  UNIQUE KEY `idx_teachers_user_id` (`user_id`),
  UNIQUE KEY `idx_teachers_employee_id` (`employee_id`),
  KEY `idx_teachers_department` (`department`),
  CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Attendance Records Table
CREATE TABLE IF NOT EXISTS `attendance_records` (
  `attendance_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `record_date` DATE NOT NULL,
  `entry_time` TIME DEFAULT NULL,
  `exit_time` TIME DEFAULT NULL,
  `direction` ENUM('entry','exit') DEFAULT 'entry',
  `method` ENUM('qr_scan','rfid_scan','manual') NOT NULL DEFAULT 'manual',
  `status` ENUM('present','tardy','absent','excused') NOT NULL DEFAULT 'present',
  `is_excused` TINYINT(1) NOT NULL DEFAULT 0,
  `scanned_by` INT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `attachment_path` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`attendance_id`),
  KEY `idx_att_user_date` (`user_id`, `record_date`),
  KEY `idx_att_date_status` (`record_date`, `status`),
  KEY `idx_att_status_date` (`status`, `record_date`),
  KEY `fk_att_scanned_by` (`scanned_by`),
  CONSTRAINT `fk_att_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_scanned_by` FOREIGN KEY (`scanned_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Excuse Slips Table
CREATE TABLE IF NOT EXISTS `excuse_slips` (
  `slip_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_user_id` INT UNSIGNED NOT NULL,
  `submitted_by` INT UNSIGNED NOT NULL,
  `reason` TEXT NOT NULL,
  `attachment_path` VARCHAR(500) DEFAULT NULL,
  `date_from` DATE NOT NULL,
  `date_to` DATE NOT NULL,
  `status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `review_notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`slip_id`),
  KEY `idx_slips_student` (`student_user_id`),
  KEY `idx_slips_status` (`status`),
  KEY `idx_slips_dates` (`date_from`, `date_to`),
  KEY `fk_slips_reviewer` (`reviewed_by`),
  CONSTRAINT `fk_slips_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_slips_submitter` FOREIGN KEY (`submitted_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_slips_reviewer` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Parent Alerts Table
CREATE TABLE IF NOT EXISTS `parent_alerts` (
  `alert_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient_email` VARCHAR(255) NOT NULL,
  `student_user_id` INT UNSIGNED NOT NULL,
  `alert_type` ENUM('tardy','absent','perfect_attendance','system') NOT NULL,
  `subject` VARCHAR(255) NOT NULL,
  `body` TEXT NOT NULL,
  `sent_at` DATETIME DEFAULT NULL,
  `status` ENUM('pending','sent','failed') NOT NULL DEFAULT 'pending',
  `error_message` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`alert_id`),
  KEY `idx_alerts_student_type` (`student_user_id`, `alert_type`, `created_at`),
  KEY `idx_alerts_status` (`status`, `created_at`),
  CONSTRAINT `fk_alerts_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Perfect Attendance Awards Table
CREATE TABLE IF NOT EXISTS `perfect_attendance_awards` (
  `award_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_user_id` INT UNSIGNED NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `award_type` ENUM('monthly','quarterly','semester','custom') NOT NULL,
  `generated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `generated_by` INT UNSIGNED NOT NULL,
  `certificate_path` VARCHAR(500) DEFAULT NULL,
  `notified_parent` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`award_id`),
  KEY `idx_awards_student` (`student_user_id`),
  KEY `idx_awards_period` (`period_start`, `period_end`),
  KEY `fk_awards_generator` (`generated_by`),
  CONSTRAINT `fk_awards_student` FOREIGN KEY (`student_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `fk_awards_generator` FOREIGN KEY (`generated_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Library Settings Table
CREATE TABLE IF NOT EXISTS `library_settings` (
  `setting_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `idx_settings_key` (`setting_key`),
  KEY `fk_settings_updater` (`updated_by`),
  CONSTRAINT `fk_settings_updater` FOREIGN KEY (`updated_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `log_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `entity_type` VARCHAR(50) NOT NULL,
  `entity_id` INT UNSIGNED NOT NULL,
  `old_values` JSON DEFAULT NULL,
  `new_values` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`log_id`),
  KEY `idx_audit_user` (`user_id`, `created_at`),
  KEY `idx_audit_entity` (`entity_type`, `entity_id`),
  KEY `idx_audit_action` (`action`, `created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==============================================================================
-- Initial Seed Data: Default Settings & Administrator Account
-- ==============================================================================

-- Default Library Settings
INSERT INTO `library_settings` (`setting_key`, `setting_value`, `description`) VALUES
('library_open_time', '08:00:00', 'Daily library opening time'),
('library_close_time', '17:00:00', 'Daily library closing time'),
('tardy_threshold_minutes', '15', 'Grace period in minutes after opening before marked tardy'),
('auto_absence_enabled', '1', 'Automatically log absent records after closing time'),
('alert_tardy_enabled', '1', 'Send automated notification to parents upon student tardiness'),
('alert_absent_enabled', '1', 'Send automated notification to parents upon student absence'),
('alert_perfect_attendance_enabled', '1', 'Send notification when perfect attendance award is generated'),
('max_excuse_days', '30', 'Maximum days allowed per excuse slip request'),
('csv_max_rows', '10000', 'Maximum row limit for data exports'),
('timezone', 'Asia/Manila', 'Application timezone')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Default Administrator User (Password: admin123)
-- Hash generated via password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO `users` (`user_id`, `role`, `email`, `password_hash`, `first_name`, `last_name`, `status`) VALUES
(1, 'admin', 'admin@bestlink.edu.ph', '$2y$12$R.3lI7sKdgDqZ0VwLg6.5uW5Vd0NcfwN5o7tq9z3uK8tM0T7Xk9d.', 'System', 'Administrator', 'active')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);
