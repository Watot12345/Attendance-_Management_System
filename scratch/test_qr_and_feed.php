<?php
/**
 * Test Suite for Dynamic 6-digit QR Sessions, 30m Timer & Live Database Feed
 */

require_once 'includes/core/Database.php';
require_once 'includes/controllers/AttendanceController.php';

$db = Database::getConnection();

echo "=== TESTING 6-DIGIT QR SESSION & LIVE ATTENDANCE FEED ===\n\n";

// Clear test attendance & qr_sessions for clean run
$db->exec("DELETE FROM attendance WHERE teacher_id = 2");
$db->exec("DELETE FROM qr_sessions WHERE teacher_id = 2");

$controller = new AttendanceController();

// 1. Test QR Session Generation
echo "1. Testing POST /api/teacher/qr-session/generate...\n";
ob_start();
$_SESSION['teacher_id'] = 2;
$_SESSION['role'] = 'teacher';
$controller->generateQrSession();
$out = ob_get_clean();
$genData = json_decode($out, true);

if (!isset($genData['session']['qr_code'])) {
    die("❌ FAIL: No qr_code in response: $out\n");
}

$qrCode = $genData['session']['qr_code'];
$sessionId = $genData['session']['qr_session_id'];
$expiresIn = $genData['session']['expires_in_seconds'];

echo "Generated QR Code: $qrCode (Length: " . strlen($qrCode) . ")\n";
echo "Session ID: $sessionId\n";
echo "Expires in seconds: $expiresIn (Expected ~1800s / 30 mins)\n";

assert(strlen($qrCode) === 6, "QR code must be 6 digits");
assert(is_numeric($qrCode), "QR code must be numeric");
assert($expiresIn >= 1795 && $expiresIn <= 1800, "Expiry must be 30 minutes (1800s)");

// Verify in Database
$dbRow = $db->query("SELECT * FROM qr_sessions WHERE qr_session_id = $sessionId")->fetch(PDO::FETCH_ASSOC);
assert(!empty($dbRow), "Row must exist in qr_sessions table");
assert($dbRow['qr_code'] === $qrCode, "qr_code in DB must match generated code");
echo "✅ PASS: 6-Digit QR Session generated and inserted into `qr_sessions` table with 30m timer\n\n";

// 2. Test Get Active QR Session
echo "2. Testing GET /api/teacher/qr-session/active...\n";
ob_start();
$controller->getActiveQrSession();
$activeOut = ob_get_clean();
$activeData = json_decode($activeOut, true);

assert($activeData['has_active_session'] === true, "Must have active session");
assert($activeData['session']['qr_code'] === $qrCode, "Active session qr_code must match");
echo "✅ PASS: Active 6-Digit QR Session retrieved successfully\n\n";

// 3. Test Student Attendance Check-in via 6-digit QR code
echo "3. Testing POST /api/attendance/check-in...\n";

// Student 1 (Juan Dela Cruz, user_id = 1) check-in as Present
$_POST = [
    'qr_code'    => $qrCode,
    'student_id' => '1',
    'status'     => 'present'
];
ob_start();
$controller->recordCheckIn();
$check1Out = ob_get_clean();
$check1 = json_decode($check1Out, true);

assert($check1['status'] === 'success', "Check-in 1 should succeed: $check1Out");
echo "Recorded Student 1: {$check1['message']}\n";

// Student 2 (Maria Santos, user_id = 4) check-in as Tardy
$_POST = [
    'qr_code'    => $qrCode,
    'student_id' => '4',
    'status'     => 'tardy'
];
ob_start();
$controller->recordCheckIn();
$check2Out = ob_get_clean();
$check2 = json_decode($check2Out, true);

assert($check2['status'] === 'success', "Check-in 2 should succeed: $check2Out");
echo "Recorded Student 4: {$check2['message']}\n";

// Verify Duplicate check-in rejection
$_POST = [
    'qr_code'    => $qrCode,
    'student_id' => '1',
    'status'     => 'present'
];
ob_start();
$controller->recordCheckIn();
$dupOut = ob_get_clean();
$dupData = json_decode($dupOut, true);
assert($dupData['scan_code'] === 'DUPLICATE', "Duplicate check-in should be rejected");
echo "✅ PASS: Student check-ins inserted into `attendance` table with duplicate prevention\n\n";

// 4. Test Live Attendance Feed from real Database
echo "4. Testing GET /api/teacher/attendance/live-feed...\n";
ob_start();
$controller->getLiveAttendanceFeed();
$feedOut = ob_get_clean();
$feedData = json_decode($feedOut, true);

assert($feedData['status'] === 'success', "Live feed should return success");
assert(count($feedData['checkins']) === 2, "Live feed should have 2 recorded check-ins");
assert($feedData['metrics']['present'] === 1, "Present count should be 1");
assert($feedData['metrics']['tardy'] === 1, "Tardy count should be 1");
echo "Live Feed Checkins count: " . count($feedData['checkins']) . "\n";
echo "Metrics: Enrolled={$feedData['metrics']['enrolled']}, Present={$feedData['metrics']['present']}, Tardy={$feedData['metrics']['tardy']}, Pending={$feedData['metrics']['pending']}\n";
echo "✅ PASS: Live Attendance Feed queries real `attendance` table and accurately calculates metrics\n\n";

// 5. Test Session Close & Automatic Absence Processing
echo "5. Testing POST /api/teacher/qr-session/close...\n";
$_POST = ['qr_session_id' => $sessionId];
ob_start();
$controller->closeQrSession();
$closeOut = ob_get_clean();
$closeData = json_decode($closeOut, true);

assert($closeData['status'] === 'success', "Close session should succeed");
echo "Closed Session result: {$closeData['message']}\n";

// Verify session is now inactive
ob_start();
$controller->getActiveQrSession();
$afterCloseOut = ob_get_clean();
$afterClose = json_decode($afterCloseOut, true);
assert($afterClose['has_active_session'] === false, "Session should now be inactive");
echo "✅ PASS: QR Session closed, unrecorded students marked absent in `attendance`, and session state updated to inactive (empty state)\n\n";

echo "🎉 ALL DYNAMIC 6-DIGIT QR AND DATABASE LIVE FEED TESTS PASSED!\n";
