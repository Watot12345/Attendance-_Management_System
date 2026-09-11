<?php
require_once __DIR__ . '/../includes/core/Database.php';
$db = Database::getConnection();

// Delete test row where teacher_id = 1 (Juan Dela Cruz is student, not teacher)
$db->exec("DELETE FROM class_roster WHERE teacher_id = 1");

$rows = $db->query("
    SELECT cr.roster_id, cr.student_id, cr.teacher_id, cr.course_code, cr.course_title, cr.section 
    FROM class_roster cr
")->fetchAll(PDO::FETCH_ASSOC);

echo "Current clean class_roster rows:\n";
print_r($rows);
