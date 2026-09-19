<?php
require 'includes/core/Database.php';
$pdo = Database::getConnection();

echo "=== class_roster Structure ===\n";
print_r($pdo->query('DESCRIBE class_roster')->fetchAll());

echo "=== class_roster Grade Levels in DB ===\n";
print_r($pdo->query('SELECT year_level, COUNT(*) AS student_count FROM class_roster GROUP BY year_level ORDER BY year_level')->fetchAll());

echo "=== Grade Level Attendance Query Result ===\n";
$rows = $pdo->query("
    SELECT 
        CONCAT('Grade ', COALESCE(cr.year_level, 9)) AS grade_label,
        COUNT(*) AS total_records,
        SUM(CASE WHEN a.`status` = 'absent' THEN 1 ELSE 0 END) AS total_absences,
        SUM(CASE WHEN a.`status` = 'tardy' THEN 1 ELSE 0 END) AS total_tardies
    FROM attendance a
    LEFT JOIN class_roster cr ON cr.student_id = a.student_id
    GROUP BY cr.year_level
    ORDER BY cr.year_level ASC
")->fetchAll();
print_r($rows);
