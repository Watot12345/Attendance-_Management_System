<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();
$stmt = $db->query("SELECT user_id, email, password_hash, role, status FROM users WHERE role = 'admin' OR email LIKE '%attendance%' OR email LIKE '%admin%'");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($users) . " matching accounts:\n";
foreach ($users as $u) {
    echo "ID: {$u['user_id']} | Email: '{$u['email']}' | Role: {$u['role']} | Status: {$u['status']}\n";
    $passwordsToTest = ['attendance1234', 'admin123', 'password123', 'ttendance-123'];
    foreach ($passwordsToTest as $p) {
        $matches = password_verify($p, $u['password_hash']) || ($p === $u['password_hash']);
        echo "  - Password '$p': " . ($matches ? 'MATCHES' : 'NO') . "\n";
    }
}
