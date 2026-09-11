<?php
require_once 'includes/core/Database.php';
$db = Database::getConnection();
$students = $db->query("SELECT user_id, student_id, first_name, last_name, email, role FROM users WHERE role = 'student'")->fetchAll(PDO::FETCH_ASSOC);
print_r($students);
