<?php
require 'includes/core/Database.php';
$pdo = Database::getConnection();

echo "Current Year Levels in class_roster:\n";
$rows = $pdo->query('SELECT student_id, first_name, last_name, year_level, course, section FROM class_roster ORDER BY student_id')->fetchAll();
foreach ($rows as $r) {
    echo "ID: {$r['student_id']} | {$r['first_name']} {$r['last_name']} | Year: {$r['year_level']} | Section: {$r['section']}\n";
}
