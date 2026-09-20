<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();
$stmt = $db->query("SELECT user_id, first_name, last_name, email, role, otp_code, otp_expires_at, status FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total users: " . count($users) . "\n";
foreach ($users as $u) {
    echo "ID: {$u['user_id']} | Name: {$u['first_name']} {$u['last_name']} | Email: '{$u['email']}' | Role: {$u['role']} | OTP: {$u['otp_code']} | Status: {$u['status']}\n";
}
