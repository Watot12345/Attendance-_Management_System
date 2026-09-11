<?php
/**
 * Test script to verify session clearance on screen while retaining modal data
 */

function httpGet($url) {
    $options = [
        'http' => [
            'method'  => 'GET',
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'ignore_errors' => true
        ]
    ];
    $res = file_get_contents($url, false, stream_context_create($options));
    return json_decode($res, true);
}

function httpPost($url, $data = []) {
    $options = [
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAccept: application/json\r\n",
            'content' => json_encode($data),
            'ignore_errors' => true
        ]
    ];
    $res = file_get_contents($url, false, stream_context_create($options));
    return json_decode($res, true);
}

require_once 'includes/core/Database.php';
$db = Database::getConnection();
$db->exec("DELETE FROM attendance WHERE teacher_id = 2");
$db->exec("DELETE FROM qr_sessions WHERE teacher_id = 2");

$baseUrl = 'http://localhost:8000';

echo "=== TESTING FEED RESET ON SESSION CLOSE & MODAL DATA RETENTION ===\n\n";

// 1. Generate new session
echo "1. Generating fresh 6-digit session...\n";
$gen = httpPost("$baseUrl/api/teacher/qr-session/generate");
$qrCode = $gen['session']['qr_code'];
$sessionId = $gen['session']['qr_session_id'];
echo "Session ID: $sessionId, Code: $qrCode\n";

// 2. Perform check-in for Student 6 (Pedro Reyes)
echo "2. Checking in student 6 (Pedro Reyes)...\n";
$chk = httpPost("$baseUrl/api/attendance/check-in", [
    'qr_code'    => $qrCode,
    'student_id' => '6',
    'status'     => 'present'
]);
echo "Check-in response: " . json_encode($chk) . "\n";

// 3. Verify Live feed during active session
echo "\n3. Checking Live feed during active session...\n";
$feedActive = httpGet("$baseUrl/api/teacher/attendance/live-feed?session_id=$sessionId");
echo "Active checkins count: " . count($feedActive['checkins']) . "\n";
echo "All today checkins count: " . count($feedActive['all_today_checkins']) . "\n";
assert(count($feedActive['checkins']) >= 1, "Active feed must contain the student during session");

// 4. Close Session
echo "\n4. Closing session $sessionId...\n";
$close = httpPost("$baseUrl/api/teacher/qr-session/close", ['qr_session_id' => $sessionId]);
echo "Closed: " . json_encode($close) . "\n";

// 5. Verify Live feed AFTER session close (NO active session)
echo "\n5. Checking Live feed AFTER session close...\n";
$feedClosed = httpGet("$baseUrl/api/teacher/attendance/live-feed");
echo "Has active session: " . ($feedClosed['has_active_session'] ? 'YES' : 'NO') . "\n";
echo "Live feed on-screen checkins count: " . count($feedClosed['checkins']) . " (Expected: 0 to reset screen feed)\n";
echo "Modal all_today_checkins count: " . count($feedClosed['all_today_checkins']) . " (Expected: > 0 to preserve modal data)\n";

assert(count($feedClosed['checkins']) === 0, "Live feed checkins must be 0 after session close");
assert(count($feedClosed['all_today_checkins']) >= 1, "Modal data must be retained after session close");

echo "\n✅ SUCCESS: Screen feed is cleared for next session while all student records are retained in the modal!\n";
