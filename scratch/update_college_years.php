<?php
require 'includes/core/Database.php';
$pdo = Database::getConnection();

echo "=== Updating class_roster to College Years 1 to 4 ===\n";

// 1. Update 7 -> 1, 8 -> 2, 9 -> 3, 10 -> 4
$pdo->exec("UPDATE class_roster SET year_level = 1 WHERE year_level = 7");
$pdo->exec("UPDATE class_roster SET year_level = 2 WHERE year_level = 8");
$pdo->exec("UPDATE class_roster SET year_level = 3 WHERE year_level = 9");
$pdo->exec("UPDATE class_roster SET year_level = 4 WHERE year_level = 10");

// 2. Ensure any student users missing in class_roster are inserted
$missingStudents = $pdo->query('
    SELECT u.user_id, u.first_name, u.last_name 
    FROM users u 
    LEFT JOIN class_roster cr ON cr.student_id = u.user_id 
    WHERE u.role = "student" AND cr.roster_id IS NULL
')->fetchAll();

$insStmt = $pdo->prepare('
    INSERT INTO class_roster (student_id, teacher_id, first_name, last_name, section, scheduled_time, schedule_day, course_code, course_title, course, year_level)
    VALUES (?, 2, ?, ?, ?, "08:00:00", "Monday", "CS101", "Computer Science", "BSCS", ?)
');

$secList = ['31001', '31002', '31003'];
$yrIndex = 0;
foreach ($missingStudents as $st) {
    $yr = ($yrIndex % 4) + 1; // 1 to 4
    $sec = $secList[$yrIndex % count($secList)];
    $insStmt->execute([$st['user_id'], $st['first_name'], $st['last_name'], $sec, $yr]);
    $yrIndex++;
    echo "Added missing student ID {$st['user_id']} ({$st['first_name']} {$st['last_name']}) -> Year $yr, Section $sec\n";
}

echo "\n=== New Year Level Distribution in class_roster ===\n";
$dist = $pdo->query('SELECT year_level, COUNT(*) as student_count FROM class_roster GROUP BY year_level ORDER BY year_level')->fetchAll();
foreach ($dist as $d) {
    echo "Year {$d['year_level']}: {$d['student_count']} students\n";
}
