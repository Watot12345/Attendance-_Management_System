<?php
/**
 * End-to-End Test: Student Session Token Scan + Standalone Manual Entry + Daily Ledger Integration
 */

require_once __DIR__ . '/../includes/core/Database.php';

$baseUrl = 'http://127.0.0.1:8000';
$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $message): void {
    global $passed, $failed;
    if ($condition) {
        echo "  [PASS] $message\n";
        $passed++;
    } else {
        echo "  [FAIL] $message\n";
        $failed++;
    }
}

function httpReq(string $url, string $method = 'GET', array $data = []): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_COOKIE, 'ams_session_role=teacher; ams_session_user=2');

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
    }

    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'body' => $body,
        'json' => json_decode($body, true)
    ];
}

echo "===============================================================\n";
echo "  E2E TEST: Token Scan & Standalone Manual Entry Workflow      \n";
echo "===============================================================\n\n";

$db = Database::getConnection();

// 1. Setup: Ensure Teacher 2 has an active QR session for testing
$testToken = '998877';
$db->exec("DELETE FROM qr_sessions WHERE qr_code = '$testToken'");
$db->exec("
    INSERT INTO qr_sessions (teacher_id, qr_code, start, `end`, created_at)
    VALUES (2, '$testToken', NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())
");
$sessionId = (int)$db->lastInsertId();
assertTest($sessionId > 0, "Created active test QR session with token $testToken");

// Clean any existing attendance for student 1 today
$today = date('Y-m-d');
$db->exec("DELETE FROM attendance WHERE student_id = 1 AND teacher_id = 2 AND date = '$today'");

// 2. Test Check-In with Valid Token
echo "\n2. Testing POST /api/attendance/check-in with active session token:\n";
$resCheck = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $testToken,
    'student_id' => 1,
    'status'     => 'present'
]);

assertTest($resCheck['code'] === 200, "Check-in returns HTTP 200");
assertTest(($resCheck['json']['status'] ?? '') === 'success', "Check-in response status is success");
assertTest(isset($resCheck['json']['attendance_id']), "Check-in returns attendance_id");
$attId = $resCheck['json']['attendance_id'] ?? 0;

// 3. Test That Daily Ledger Reflects the QR Check-in
echo "\n3. Testing GET /api/attendance/daily reflects the token scan:\n";
$resLedger = httpReq("$baseUrl/api/attendance/daily?teacher_id=2&date=$today");
assertTest($resLedger['code'] === 200, "Daily ledger returns HTTP 200");
assertTest(($resLedger['json']['status'] ?? '') === 'success', "Daily ledger status is success");

$records = $resLedger['json']['records'] ?? [];
$student1Rec = null;
foreach ($records as $r) {
    if ((int)$r['student_id'] === 1) {
        $student1Rec = $r;
        break;
    }
}
assertTest($student1Rec !== null, "Student 1 found in daily attendance ledger");
assertTest(($student1Rec['status'] ?? '') === 'present', "Student 1 status is 'present'");
assertTest(($student1Rec['method'] ?? '') === 'QR', "Student 1 method is 'QR'");

// 4. Test Duplicate Token Scan Rejection
echo "\n4. Testing Duplicate Token Scan Rejection:\n";
$resDup = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $testToken,
    'student_id' => 1
]);
assertTest($resDup['code'] === 409, "Duplicate scan returns HTTP 409 Conflict");
assertTest(($resDup['json']['scan_code'] ?? '') === 'DUPLICATE', "Duplicate scan returns scan_code DUPLICATE");

// 5. Test Expired / Invalid Token Rejection
echo "\n5. Testing Expired / Invalid Token Rejection:\n";
$resExp = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => '000000',
    'student_id' => 1
]);
assertTest($resExp['code'] === 400, "Invalid token returns HTTP 400 Bad Request");
assertTest(($resExp['json']['scan_code'] ?? '') === 'EXPIRED_OR_INVALID', "Invalid token returns EXPIRED_OR_INVALID");

// 6. Test Standalone Manual Entry Page Load
echo "\n6. Testing GET /attendance/manual-entry:\n";
$resManualPage = httpReq("$baseUrl/attendance/manual-entry");
assertTest($resManualPage['code'] === 200, "Manual entry page returns HTTP 200 OK");
assertTest(strpos($resManualPage['body'], 'Manual Attendance Entry') !== false, "Page contains 'Manual Attendance Entry' title");
assertTest(strpos($resManualPage['body'], 'submitStandaloneManualEntry') !== false, "Page contains dynamic form submission function");
assertTest(strpos($resManualPage['body'], 'page-manual-student') !== false, "Page contains student select input");

// 7. Test Manual Entry Override Over Token Check-in
echo "\n7. Testing POST /api/attendance/manual-entry overrides QR attendance:\n";
$resOverride = httpReq("$baseUrl/api/attendance/manual-entry", 'POST', [
    'student_id' => 1,
    'date'       => $today,
    'status'     => 'tardy',
    'time'       => '08:35',
    'subject'    => 'Web Systems and Technologies',
    'notes'      => 'Overridden by teacher due to late bus'
]);
assertTest($resOverride['code'] === 200, "Manual entry override returns HTTP 200 OK");
assertTest(($resOverride['json']['status'] ?? '') === 'success', "Manual entry status is success");

// 8. Re-query Daily Ledger to Verify Manual Override
echo "\n8. Re-querying Daily Ledger to Verify Override Reflection:\n";
$resLedger2 = httpReq("$baseUrl/api/attendance/daily?teacher_id=2&date=$today");
$records2 = $resLedger2['json']['records'] ?? [];
$student1Rec2 = null;
foreach ($records2 as $r) {
    if ((int)$r['student_id'] === 1) {
        $student1Rec2 = $r;
        break;
    }
}
assertTest(($student1Rec2['status'] ?? '') === 'tardy', "Student 1 status successfully changed to 'tardy'");
assertTest(($student1Rec2['method'] ?? '') === 'Manual', "Student 1 method successfully switched to 'Manual'");

// Clean up
$db->exec("DELETE FROM qr_sessions WHERE qr_session_id = $sessionId");
$db->exec("DELETE FROM attendance WHERE student_id = 1 AND teacher_id = 2 AND date = '$today'");
$db->exec("DELETE FROM audit_logs WHERE reference_type = 'attendance' AND reference_id = $attId");

echo "\n===============================================================\n";
echo "  RESULTS: $passed PASSED, $failed FAILED\n";
echo "===============================================================\n";

exit($failed > 0 ? 1 : 0);
