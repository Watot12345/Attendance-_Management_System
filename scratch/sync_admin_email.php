<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

// Check if managementattendance6@gmail.com exists
$stmt = $db->prepare("SELECT * FROM users WHERE LOWER(email) = 'managementattendance6@gmail.com' LIMIT 1");
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "managementattendance6@gmail.com does not exist. Creating/updating...\n";
    
    // Update admin user (user_id = 26 or role = 'admin') to managementattendance6@gmail.com and password attendance1234
    $upStmt = $db->prepare("
        UPDATE users 
        SET email = 'managementattendance6@gmail.com',
            password_hash = ?,
            status = 'active'
        WHERE user_id = 26 OR role = 'admin'
    ");
    $upStmt->execute([password_hash('attendance1234', PASSWORD_BCRYPT)]);
    echo "Updated Admin account to managementattendance6@gmail.com with password 'attendance1234'!\n";
} else {
    echo "User exists with ID: {$user['user_id']}. Updating password to attendance1234...\n";
    $upStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $upStmt->execute([password_hash('attendance1234', PASSWORD_BCRYPT), $user['user_id']]);
    echo "Password updated!\n";
}

// Check all admin users now
$stmt = $db->query("SELECT user_id, first_name, last_name, email, role, status FROM users WHERE role = 'admin'");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($admins);
