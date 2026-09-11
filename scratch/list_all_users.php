<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

echo "=== USERS TABLE STRUCTURE ===\n";
$cols = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) {
    echo " - {$c['Field']} ({$c['Type']})\n";
}

echo "\n=== ALL USERS IN DATABASE ===\n";
$users = $db->query("SELECT user_id, student_id, employee_id, role, email, password_hash, first_name, last_name, status FROM users")->fetchAll(PDO::FETCH_ASSOC);
foreach ($users as $u) {
    echo "ID: {$u['user_id']} | Role: {$u['role']} | Email: {$u['email']} | StudentID: {$u['student_id']} | EmpID: {$u['employee_id']} | Name: {$u['first_name']} {$u['last_name']} | Status: {$u['status']}\n";
}
