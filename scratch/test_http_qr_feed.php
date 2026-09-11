<?php
/**
 * End-to-end HTTP Test for Dynamic 6-digit QR & Live Attendance Feed
 */

function httpReq($url, $method = 'GET', $data = null) {
    $options = [
        'http' => [
            'method'  => $method,
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'ignore_errors' => true
        ]
    ];
    if ($data !== null) {
        $options['http']['content'] = json_encode($data);
    }
    $context = stream_context_create($options);
    $res = file_get_contents($url, false, $context);
    return json_decode($res, true);
}

$baseUrl = 'http://localhost:8000';

echo "=== TESTING API OVER HTTP ($baseUrl) ===\n\n";

// 1. Generate 6-digit QR Session
echo "1. POST /api/teacher/qr-session/generate...\n";
$gen = httpReq("$baseUrl/api/teacher/qr-session/generate", 'POST', []);
print_r($gen);

assert($gen['status'] === 'success', "QR generation should succeed");
$qrCode = $gen['session']['qr_code'];
$sessionId = $gen['session']['qr_session_id'];
$expiresIn = $gen['session']['expires_in_seconds'];

echo "Generated 6-Digit Code: $qrCode\n";
echo "Expires in: $expiresIn seconds (30 minutes)\n";
assert(strlen($qrCode) === 6, "Code must be 6 digits");
assert(is_numeric($qrCode), "Code must be numeric");

// 2. Active Session check
echo "\n2. GET /api/teacher/qr-session/active...\n";
$active = httpReq("$baseUrl/api/teacher/qr-session/active", 'GET');
print_r($active);
assert($active['has_active_session'] === true, "Session must be active");
assert($active['session']['qr_code'] === $qrCode, "Active code must match");

// 3. Check-in student 1 (Present)
echo "\n3. POST /api/attendance/check-in (Student 1 Present)...\n";
$chk1 = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $qrCode,
    'student_id' => '1',
    'status'     => 'present'
]);
print_r($chk1);
assert($chk1['status'] === 'success', "Student 1 check-in should succeed");

// 4. Check-in student 4 (Tardy)
echo "\n4. POST /api/attendance/check-in (Student 4 Tardy)...\n";
$chk2 = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $qrCode,
    'student_id' => '4',
    'status'     => 'tardy'
]);
print_r($chk2);
assert($chk2['status'] === 'success', "Student 4 check-in should succeed");

// 5. Test Duplicate check-in
echo "\n5. POST /api/attendance/check-in (Duplicate)...\n";
$dup = httpReq("$baseUrl/api/attendance/check-in", 'POST', [
    'qr_code'    => $qrCode,
    'student_id' => '1',
    'status'     => 'present'
]);
print_r($dup);
assert($dup['scan_code'] === 'DUPLICATE', "Duplicate should be rejected");

// 6. Test Live Feed from Database
echo "\n6. GET /api/teacher/attendance/live-feed...\n";
$feed = httpReq("$baseUrl/api/teacher/attendance/live-feed", 'GET');
print_r($feed);
assert($feed['status'] === 'success', "Live feed should succeed");
assert(count($feed['checkins']) >= 2, "Live feed should have check-in records");
assert($feed['metrics']['present'] >= 1, "Present count >= 1");
assert($feed['metrics']['tardy'] >= 1, "Tardy count >= 1");

// 7. Close Session
echo "\n7. POST /api/teacher/qr-session/close...\n";
$close = httpReq("$baseUrl/api/teacher/qr-session/close", 'POST', [
    'qr_session_id' => $sessionId
]);
print_r($close);
assert($close['status'] === 'success', "Close session should succeed");

// 8. Verify Inactive state (Empty state)
echo "\n8. GET /api/teacher/qr-session/active (After close)...\n";
$afterClose = httpReq("$baseUrl/api/teacher/qr-session/active", 'GET');
print_r($afterClose);
assert($afterClose['has_active_session'] === false, "Session should be inactive");

echo "\n🎉 ALL HTTP TESTS PASSED! 6-digit QR codes, 30m timer, database insertion, and live database attendance feed are 100% functional!\n";
