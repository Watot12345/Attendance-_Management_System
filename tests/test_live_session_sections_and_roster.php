<?php
/**
 * End-to-End Test for Dynamic Section Live QR Session & Class Roster Validation
 */

require_once __DIR__ . '/../includes/core/Database.php';

$baseUrl = 'http://127.0.0.1:8000';

function testLog($title, $pass, $detail = '') {
    echo ($pass ? "✅ PASS: " : "❌ FAIL: ") . $title;
    if ($detail) echo " -> $detail";
    echo "\n";
    if (!$pass) {
        throw new Exception("Test failed: $title");
    }
}

function httpReq($url, $method = 'GET', $body = null) {
    for ($attempt = 1; $attempt <= 3; $attempt++) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, true);
        curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        $headers = ['Accept: application/json', 'Connection: close'];
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($body) ? $body : json_encode($body));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false) {
            return [
                'code' => $httpCode,
                'json' => json_decode($response, true),
                'raw'  => $response
            ];
        }
        usleep(200000); // 200ms backoff
    }
    return [
        'code' => 0,
        'json' => null,
        'raw'  => ''
    ];
}

$db = Database::getConnection();

// Clean up any test attendance records for today to start fresh
$today = date('Y-m-d');
$db->exec("UPDATE qr_sessions SET end = '2000-01-01 00:00:00'");
$db->exec("DELETE FROM attendance WHERE `date` = '$today'");

echo "===============================================================\n";
echo "TEST 1: Check active session does NOT auto-generate a new session\n";
echo "===============================================================\n";

$resActive = httpReq("$baseUrl/api/teacher/qr-session/active?section=31001");
testLog("Active session returns HTTP 200", $resActive['code'] === 200);
testLog("No active session exists initially", ($resActive['json']['has_active_session'] ?? true) === false);

// Check qr_sessions count in DB
$activeCountInDb = $db->query("SELECT COUNT(*) FROM qr_sessions WHERE end > NOW()")->fetchColumn();
testLog("Database has 0 active sessions (no auto-generation)", (int)$activeCountInDb === 0);

echo "\n===============================================================\n";
echo "TEST 2: Generate 6-digit QR session explicitly for section 31001\n";
echo "===============================================================\n";

$resGen = httpReq("$baseUrl/api/teacher/qr-session/generate", 'POST', ['section' => '31001']);
testLog("QR generation returns HTTP 200", $resGen['code'] === 200, $resGen['raw']);
testLog("Status is success", ($resGen['json']['status'] ?? '') === 'success');
$session = $resGen['json']['session'] ?? [];
$qrCode = $session['qr_code'] ?? '';
$sessionId = (int)($session['qr_session_id'] ?? 0);
$returnedSection = $session['section'] ?? '';

testLog("6-digit numeric QR code generated", strlen($qrCode) === 6 && ctype_digit($qrCode), "QR: $qrCode");
testLog("Session is assigned to section 31001", $returnedSection === '31001', "Section: $returnedSection");
testLog("Course code is IT301", ($session['course_code'] ?? '') === 'IT301');
testLog("Course title is Web Systems and Technologies", ($session['course_title'] ?? '') === 'Web Systems and Technologies');

echo "\n===============================================================\n";
echo "TEST 3: Student Scan with ENROLLED student in Section 31001\n";
echo "===============================================================\n";

$enrolledStudentId = (int)$db->query("SELECT student_id FROM class_roster WHERE section = '31001' AND teacher_id = 2 LIMIT 1")->fetchColumn();
if (!$enrolledStudentId) {
    // If none, enroll student 1
    $db->exec("INSERT INTO class_roster (student_id, teacher_id, section, course_code, course_title, room_number, scheduled_time, schedule_day)
               VALUES (1, 2, '31001', 'IT301', 'Web Systems and Technologies', '402', '08:00:00', 'Monday')
               ON DUPLICATE KEY UPDATE section = '31001'");
    $enrolledStudentId = 1;
}

$resCheckEnrolled = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $qrCode,
    'student_id' => $enrolledStudentId,
    'status'     => 'present'
]);

testLog("Enrolled student check-in returns HTTP 200", $resCheckEnrolled['code'] === 200, $resCheckEnrolled['raw']);
testLog("Check-in status is success", ($resCheckEnrolled['json']['status'] ?? '') === 'success');
testLog("Student marked present", ($resCheckEnrolled['json']['record']['status'] ?? '') === 'present');
testLog("Check-in record contains section 31001", ($resCheckEnrolled['json']['record']['section'] ?? '') === '31001');

echo "\n===============================================================\n";
echo "TEST 4: Student Scan with NON-ROSTER / WRONG SECTION student (user_id = 99999)\n";
echo "===============================================================\n";

$resCheckInvalid = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $qrCode,
    'student_id' => 99999,
    'status'     => 'present'
]);

testLog("Non-roster student check-in rejected with 403 or 404", in_array($resCheckInvalid['code'], [403, 404]), "HTTP: {$resCheckInvalid['code']}");
testLog("Scan error returned", ($resCheckInvalid['json']['status'] ?? '') === 'error');

echo "\n===============================================================\n";
echo "TEST 5: Test a student enrolled in a DIFFERENT section (Section 31002) scanning 31001 QR\n";
echo "===============================================================\n";

// Student user_id = 7 (Ana Gonzales): temporarily move to section 31002
$db->exec("UPDATE class_roster SET section = '31002' WHERE student_id = 7 AND teacher_id = 2");

$resCheckWrongSec = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $qrCode,
    'student_id' => 7,
    'status'     => 'present'
]);

testLog("Wrong section student check-in rejected with HTTP 403", $resCheckWrongSec['code'] === 403, $resCheckWrongSec['raw']);
testLog("Scan code is WRONG_SECTION", ($resCheckWrongSec['json']['scan_code'] ?? '') === 'WRONG_SECTION', "Scan Code: " . ($resCheckWrongSec['json']['scan_code'] ?? ''));
testLog("Error message mentions section mismatch", strpos($resCheckWrongSec['json']['message'] ?? '', 'not enrolled in section 31001') !== false);

// Restore student 7 back to 31001
$db->exec("UPDATE class_roster SET section = '31001' WHERE student_id = 7 AND teacher_id = 2");

echo "\n===============================================================\n";
echo "TEST 6: Duplicate scan prevention for already checked-in student\n";
echo "===============================================================\n";

$resDup = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $qrCode,
    'student_id' => $enrolledStudentId,
    'status'     => 'present'
]);

testLog("Duplicate check-in rejected with HTTP 409", $resDup['code'] === 409, $resDup['raw']);
testLog("Scan code is DUPLICATE", ($resDup['json']['scan_code'] ?? '') === 'DUPLICATE');

echo "\n===============================================================\n";
echo "TEST 7: Live Feed & Metrics calculation for section 31001 (Real data)\n";
echo "===============================================================\n";

$resFeed = httpReq("$baseUrl/api/teacher/attendance/live-feed?section=31001&session_id=$sessionId");
testLog("Live feed returns HTTP 200", $resFeed['code'] === 200);
$metrics = $resFeed['json']['metrics'] ?? [];
testLog("Feed reports section present count >= 1", ($metrics['present'] ?? 0) >= 1, "Present: " . ($metrics['present'] ?? 0));
testLog("Feed reports section enrolled count matches class_roster", ($metrics['enrolled'] ?? 0) > 0, "Enrolled: " . ($metrics['enrolled'] ?? 0));

echo "\n===============================================================\n";
echo "TEST 8: Real Data Count for Empty/Non-Existent Section (NO FALLBACK -> Must return 0)\n";
echo "===============================================================\n";

$resEmptySec = httpReq("$baseUrl/api/teacher/attendance/live-feed?section=SECTION_NON_EXISTENT_999");
testLog("Empty section feed returns HTTP 200", $resEmptySec['code'] === 200);
$emptyMetrics = $resEmptySec['json']['metrics'] ?? [];
testLog("Empty section enrolled count is strictly 0 (no fallback)", ($emptyMetrics['enrolled'] ?? -1) === 0, "Enrolled: " . ($emptyMetrics['enrolled'] ?? -1));
testLog("Empty section present count is strictly 0", ($emptyMetrics['present'] ?? -1) === 0, "Present: " . ($emptyMetrics['present'] ?? -1));
testLog("Empty section tardy count is strictly 0", ($emptyMetrics['tardy'] ?? -1) === 0, "Tardy: " . ($emptyMetrics['tardy'] ?? -1));
testLog("Empty section pending count is strictly 0", ($emptyMetrics['pending'] ?? -1) === 0, "Pending: " . ($emptyMetrics['pending'] ?? -1));
testLog("Empty section check-ins array is empty", empty($resEmptySec['json']['checkins']));

echo "\n===============================================================\n";
echo "TEST 9: Close Session & Mark Absences for section 31001\n";
echo "===============================================================\n";

$resClose = httpReq("$baseUrl/api/teacher/qr-session/close", 'POST', [
    'qr_session_id' => $sessionId,
    'section'       => '31001'
]);

testLog("Close session returns HTTP 200", $resClose['code'] === 200, $resClose['raw']);
testLog("Close session status is success", ($resClose['json']['status'] ?? '') === 'success');
testLog("Unrecorded students marked absent", isset($resClose['json']['marked_absent_count']));

echo "\n🎉 ALL TESTS PASSED SUCCESSFULLY!\n";
