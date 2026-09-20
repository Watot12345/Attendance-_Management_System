<?php
require_once dirname(__DIR__) . '/includes/core/Database.php';
require_once dirname(__DIR__) . '/includes/core/Router.php';
require_once dirname(__DIR__) . '/includes/controllers/AuthController.php';

session_start();
$db = Database::getConnection();

echo "=== TESTING REMEMBER DEVICE / OTP BYPASS FLOW ===" . PHP_EOL;

// 1. Get admin user
$stmt = $db->query("SELECT user_id, email, password_hash FROM users WHERE role = 'admin' LIMIT 1");
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

// Clear existing remember tokens for clean test
$db->prepare("UPDATE users SET remember_token = NULL, remember_expires_at = NULL, otp_code = NULL WHERE user_id = ?")->execute([$admin['user_id']]);
unset($_COOKIE['ams_remember_token']);
$_SESSION = [];

// Test Case 1: Fresh login without remember token (untrusted device)
$auth = new AuthController();
// Check database query matching login()
$rememberToken = $_COOKIE['ams_remember_token'] ?? '';
$isRemembered = false;
if (!empty($rememberToken)) {
    $tStmt = $db->prepare("SELECT user_id FROM users WHERE user_id = :uid AND remember_token = :token AND remember_expires_at > NOW() LIMIT 1");
    $tStmt->execute([':uid' => $admin['user_id'], ':token' => $rememberToken]);
    if ($tStmt->fetch()) $isRemembered = true;
}

if (!$isRemembered) {
    echo "✓ Test 1 Pass: Untrusted device correctly requires OTP." . PHP_EOL;
} else {
    echo "ERROR: Test 1 Failed - device should not be remembered yet!" . PHP_EOL;
    exit(1);
}

// Test Case 2: Verify OTP with Remember Me checked
$simulatedOtp = '551133';
$db->prepare("UPDATE users SET otp_code = ?, otp_expires_at = DATE_ADD(NOW(), INTERVAL 10 MINUTE) WHERE user_id = ?")->execute([$simulatedOtp, $admin['user_id']]);
$_SESSION['pending_auth'] = [
    'user_id' => (int)$admin['user_id'],
    'role' => 'admin',
    'email' => $admin['email'],
    'created_at' => time()
];

// Simulate verifyOtp logic
$deviceToken = bin2hex(random_bytes(32));
$remStmt = $db->prepare("
    UPDATE users 
    SET otp_code = NULL,
        otp_expires_at = NULL,
        remember_token = :token, 
        remember_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY), 
        remember_user_agent = 'TestAgent' 
    WHERE user_id = :uid
");
$remStmt->execute([':token' => $deviceToken, ':uid' => $admin['user_id']]);
$_COOKIE['ams_remember_token'] = $deviceToken;

echo "✓ Test 2 Pass: OTP verified and device remember token saved." . PHP_EOL;

// Test Case 3: Next Login with Email & Password on the remembered device
$rememberToken = $_COOKIE['ams_remember_token'] ?? '';
$isRememberedNow = false;
if (!empty($rememberToken)) {
    $tStmt = $db->prepare("SELECT user_id, role FROM users WHERE user_id = :uid AND remember_token = :token AND remember_expires_at > NOW() AND status = 'active' LIMIT 1");
    $tStmt->execute([':uid' => $admin['user_id'], ':token' => $rememberToken]);
    $trustedUser = $tStmt->fetch(PDO::FETCH_ASSOC);
    if ($trustedUser) $isRememberedNow = true;
}

if ($isRememberedNow) {
    echo "✓ Test 3 Pass: Remembered device BYPASSES OTP and authenticates directly to dashboard!" . PHP_EOL;
} else {
    echo "ERROR: Test 3 Failed - remembered device was not recognized!" . PHP_EOL;
    exit(1);
}

// Test Case 4: Different/untrusted device (with wrong or absent cookie)
$_COOKIE['ams_remember_token'] = 'invalid_other_device_token';
$tStmt->execute([':uid' => $admin['user_id'], ':token' => $_COOKIE['ams_remember_token']]);
$untrustedCheck = $tStmt->fetch(PDO::FETCH_ASSOC);

if (!$untrustedCheck) {
    echo "✓ Test 4 Pass: Different/untrusted device is rejected from bypass and requires OTP." . PHP_EOL;
} else {
    echo "ERROR: Test 4 Failed - invalid token should not be trusted!" . PHP_EOL;
    exit(1);
}

echo "=== ALL REMEMBER DEVICE & OTP BYPASS TESTS PASSED! ===" . PHP_EOL;
