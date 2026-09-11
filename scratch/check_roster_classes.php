<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

echo "=== CLASS ROSTER RECORDS ===\n";
$stmt = $db->query("
    SELECT cr.*, 
           CONCAT(t.first_name, ' ', t.last_name) AS teacher_full_name,
           t.email AS teacher_email
    FROM class_roster cr
    LEFT JOIN users t ON cr.teacher_id = t.user_id
    LIMIT 20
");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);

echo "\n=== DISTINCT STUDENTS IN CLASS ROSTER ===\n";
$stmt2 = $db->query("
    SELECT DISTINCT cr.student_id, cr.first_name, cr.last_name, cr.course, cr.year_level, cr.section
    FROM class_roster cr
");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));

echo "\n=== DISTINCT SUBJECTS & TEACHERS IN CLASS ROSTER ===\n";
$stmt3 = $db->query("
    SELECT cr.course_code, cr.course_title, cr.section, cr.teacher_id, 
           CONCAT(t.first_name, ' ', t.last_name) AS teacher_full_name
    FROM class_roster cr
    LEFT JOIN users t ON cr.teacher_id = t.user_id
    GROUP BY cr.course_code, cr.course_title, cr.section, cr.teacher_id
");
print_r($stmt3->fetchAll(PDO::FETCH_ASSOC));
