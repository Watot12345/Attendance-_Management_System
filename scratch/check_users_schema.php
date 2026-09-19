<?php
require 'includes/core/Database.php';
$pdo = Database::getConnection();
print_r($pdo->query('DESCRIBE users')->fetchAll());

echo "\n--- Students missing in class_roster ---\n";
print_r($pdo->query('SELECT u.user_id, u.first_name, u.last_name, cr.year_level FROM users u LEFT JOIN class_roster cr ON cr.student_id = u.user_id WHERE u.role = "student" AND cr.year_level IS NULL')->fetchAll());
