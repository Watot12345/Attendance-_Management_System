<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();
$stmt = $db->query("SELECT user_id, email, password_hash, role, status FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$passwordsToTest = ['attendance1234', 'admin123', 'teacher123', 'student123', 'password123', 'ttendance-123', 'password'];
foreach ($users as $u) {
    $matched = [];
    foreach ($passwordsToTest as $p) {
        if (password_verify($p, $u['password_hash']) || ($p === $u['password_hash'])) {
            $matched[] = $p;
        }
    }
    echo "ID: {$u['user_id']} | Email: '{$u['email']}' | Matches: " . implode(', ', $matched) . "\n";
}
