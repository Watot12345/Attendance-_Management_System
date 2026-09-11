<?php
require_once __DIR__ . '/../includes/core/Database.php';
require_once __DIR__ . '/../includes/controllers/AuthController.php';

$db = Database::getConnection();
AuthController::ensureCoreUsersExist($db);

$users = $db->query("SELECT user_id, student_id, employee_id, role, email, password_hash, first_name, last_name, status FROM users LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
echo "Users in DB:\n";
print_r($users);
