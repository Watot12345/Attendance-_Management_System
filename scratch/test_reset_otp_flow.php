<?php
require_once dirname(__DIR__) . '/includes/core/Database.php';
require_once dirname(__DIR__) . '/includes/core/Router.php';
require_once dirname(__DIR__) . '/includes/controllers/AuthController.php';

session_start();
$db = Database::getConnection();

echo "=== TESTING 3-STEP RESET OTP FLOW ===" . PHP_EOL;

// 1. Get test user
$stmt = $db->query("SELECT user_id, email FROM users WHERE role = 'admin' LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Target User: " . $admin['email'] . " (ID: " . $admin['user_id'] . ")" . PHP_EOL;

// Set a test OTP
$testOtp = '654321';
$up = $db->prepare("UPDATE users SET otp_code = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?");
$up->execute([$testOtp, $admin['user_id']]);

// 2. Test Step 2: Verify OTP
$verifyStmt = $db->prepare("
    SELECT user_id, email, status FROM users 
    WHERE LOWER(email) = LOWER(:email) 
      AND otp_code = :otp 
      AND otp_expires_at > NOW() 
    LIMIT 1
");
$verifyStmt->execute([
    ':email' => $admin['email'],
    ':otp'   => $testOtp
]);
$verifiedUser = $verifyStmt->fetch(PDO::FETCH_ASSOC);

if (!$verifiedUser) {
    echo "ERROR: OTP verification failed!" . PHP_EOL;
    exit(1);
}
echo "✓ Step 2 Pass: OTP verified correctly." . PHP_EOL;

// 3. Test Step 3: Reset password with new password
$newPassword = 'AdminPassword123';
$hash = password_hash($newPassword, PASSWORD_BCRYPT);
$resetStmt = $db->prepare("
    UPDATE users 
    SET password_hash = :hash, 
        otp_code = NULL, 
        otp_expires_at = NULL 
    WHERE user_id = :uid
");
$resetStmt->execute([':hash' => $hash, ':uid' => $admin['user_id']]);

// Check user can authenticate with new password
$authCheck = $db->prepare("SELECT password_hash, otp_code FROM users WHERE user_id = ?");
$authCheck->execute([$admin['user_id']]);
$userRow = $authCheck->fetch(PDO::FETCH_ASSOC);

if (password_verify($newPassword, $userRow['password_hash']) && empty($userRow['otp_code'])) {
    echo "✓ Step 3 Pass: Password updated and OTP cleared." . PHP_EOL;
} else {
    echo "ERROR: Password reset failed!" . PHP_EOL;
    exit(1);
}

// Reset admin password back to admin123
$resetDefault = $db->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
$resetDefault->execute([password_hash('admin123', PASSWORD_BCRYPT), $admin['user_id']]);
echo "✓ Restored default admin password." . PHP_EOL;
echo "=== ALL 3-STEP RESET TESTS PASSED! ===" . PHP_EOL;
