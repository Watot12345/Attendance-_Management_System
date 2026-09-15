<?php
require_once __DIR__ . '/../includes/core/Database.php';

try {
    $db = Database::getConnection();
    $sql = "
        SELECT 
            cr.roster_id,
            cr.student_id as roster_student_id,
            cr.first_name,
            cr.last_name,
            cr.section,
            cr.course_code,
            cr.course_title,
            cr.course,
            cr.year_level,
            cr.room_number,
            cr.scheduled_time,
            cr.schedule_day,
            u.user_id,
            u.student_id as user_student_number,
            u.email as user_email,
            u.status as user_account_status,
            CASE WHEN u.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_in_users_table,
            COALESCE(att.total_attendance, 0) AS total_attendance,
            COALESCE(att.present_count, 0) AS present_count,
            COALESCE(att.tardy_count, 0) AS tardy_count,
            COALESCE(att.absent_count, 0) AS absent_count,
            CASE 
                WHEN COALESCE(att.total_attendance, 0) > 0 
                THEN ROUND(((COALESCE(att.present_count, 0) + COALESCE(att.tardy_count, 0)) / att.total_attendance) * 100, 1)
                ELSE 100.0 
            END AS attendance_rate
        FROM class_roster cr
        LEFT JOIN users u ON (u.user_id = cr.student_id OR (cr.student_id IS NOT NULL AND u.student_id = cr.student_id))
        LEFT JOIN (
            SELECT 
                student_id,
                COUNT(*) as total_attendance,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                SUM(CASE WHEN status = 'tardy' THEN 1 ELSE 0 END) as tardy_count,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
            FROM attendance
            GROUP BY student_id
        ) att ON (att.student_id = cr.student_id OR att.student_id = u.user_id)
        ORDER BY cr.section ASC, cr.last_name ASC, cr.first_name ASC
    ";
    
    $stmt = $db->query($sql);
    $rows = $stmt->fetchAll();
    echo "Total roster joined records: " . count($rows) . "\n";
    if (!empty($rows)) {
        echo "Sample row:\n";
        print_r($rows[0]);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
