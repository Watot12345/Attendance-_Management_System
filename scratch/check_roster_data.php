<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

echo "=== CLASS ROSTER SAMPLE ROWS ===\n";
$rosterRows = $db->query("SELECT * FROM class_roster LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($rosterRows);

echo "\n=== USERS SAMPLE ROWS ===\n";
$users = $db->query("SELECT user_id, student_id, role, email, first_name, last_name FROM users WHERE role = 'student' LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);
