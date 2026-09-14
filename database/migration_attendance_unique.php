<?php
/**
 * Migration: Add UNIQUE composite key to `attendance` table
 * Key: (student_id, date, subject)
 * Prevents race conditions and duplicate check-ins across Live QR and Manual Entry.
 */
require_once dirname(__DIR__) . '/includes/core/Database.php';

try {
    $db = Database::getConnection();
    echo "Connecting to MySQL...\n";

    // 1. Check if index already exists
    $checkStmt = $db->query("
        SELECT COUNT(*) 
        FROM information_schema.statistics 
        WHERE table_schema = DATABASE() 
          AND table_name = 'attendance' 
          AND index_name = 'uq_attendance_student_date_subject'
    ");
    $exists = (int)$checkStmt->fetchColumn();

    if ($exists > 0) {
        echo "UNIQUE index `uq_attendance_student_date_subject` already exists on `attendance`.\n";
    } else {
        echo "Adding UNIQUE index `uq_attendance_student_date_subject` (student_id, date, subject)...\n";
        $db->exec("
            ALTER TABLE `attendance` 
            ADD UNIQUE KEY `uq_attendance_student_date_subject` (`student_id`, `date`, `subject`)
        ");
        echo "Successfully added UNIQUE index `uq_attendance_student_date_subject`.\n";
    }

    // 2. Output SHOW CREATE TABLE to verify
    $verify = $db->query("SHOW CREATE TABLE `attendance`")->fetch(PDO::FETCH_ASSOC);
    echo "\nVerified Table Definition:\n" . $verify['Create Table'] . "\n";

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
