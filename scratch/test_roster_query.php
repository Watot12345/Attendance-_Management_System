<?php
require_once __DIR__ . '/../includes/core/Database.php';
$db = Database::getConnection();

$studentId = 1;

$rosterStmt = $db->prepare("
    SELECT 
        MIN(cr.roster_id) AS roster_id,
        cr.course_code,
        cr.course_title,
        cr.section,
        cr.teacher_id,
        MIN(cr.schedule_day) AS schedule_day,
        MIN(cr.scheduled_time) AS scheduled_time,
        MIN(cr.room_number) AS room_number,
        MIN(cr.course) AS course,
        MIN(cr.year_level) AS year_level,
        t.first_name AS teacher_first_name,
        t.last_name AS teacher_last_name,
        CONCAT(t.first_name, ' ', t.last_name) AS teacher_full_name
    FROM class_roster cr
    LEFT JOIN users t ON cr.teacher_id = t.user_id
    WHERE cr.user_id = ?
    GROUP BY cr.course_code, cr.course_title, cr.section, cr.teacher_id, t.first_name, t.last_name
    ORDER BY cr.course_code ASC
");
$rosterStmt->execute([$studentId]);
$rawClasses = $rosterStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Enrolled classes for Student #{$studentId}:\n";
print_r($rawClasses);
