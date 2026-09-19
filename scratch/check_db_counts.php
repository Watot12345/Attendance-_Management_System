<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

echo "STUDENTS in users table:\n";
$students = $db->query("SELECT user_id, student_id, first_name, last_name, email, role FROM users WHERE role='student'")->fetchAll(PDO::FETCH_ASSOC);
print_r($students);

echo "\nTOTAL ATTENDANCE RECORDS: " . $db->query("SELECT COUNT(*) FROM attendance_records")->fetchColumn() . "\n";
$records = $db->query("SELECT * FROM attendance_records LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($records);

echo "\nCLASS ROSTER:\n";
$roster = $db->query("SELECT * FROM class_roster LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($roster);

echo "\nEXCUSE SLIPS:\n";
$slips = $db->query("SELECT * FROM excuse_slips LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
print_r($slips);
