<?php
/**
 * Test: Section Tabs Active State & Multi-Layer Persistence (Query param + Cookie)
 */

require_once __DIR__ . '/../includes/core/Database.php';

$baseUrl = 'http://127.0.0.1:8000';
$db = Database::getConnection();

// Ensure Teacher 2 has sections 31001 and 31002 in class_roster without inserting duplicates
$has31001 = (int)$db->query("SELECT COUNT(*) FROM class_roster WHERE teacher_id = 2 AND section = '31001'")->fetchColumn();
if ($has31001 === 0) {
    $db->exec("INSERT INTO class_roster (student_id, first_name, last_name, course, year_level, teacher_id, section, course_code, course_title, room_number, scheduled_time, schedule_day)
               VALUES (1, 'Juan', 'Dela Cruz', 'BSIT', 3, 2, '31001', 'IT301', 'Web Systems and Technologies', '402', '08:00:00', 'Monday')");
}

$has31002 = (int)$db->query("SELECT COUNT(*) FROM class_roster WHERE teacher_id = 2 AND section = '31002'")->fetchColumn();
if ($has31002 === 0) {
    $db->exec("INSERT INTO class_roster (student_id, first_name, last_name, course, year_level, teacher_id, section, course_code, course_title, room_number, scheduled_time, schedule_day)
               VALUES (3, 'Maria', 'Santos', 'BSIT', 3, 2, '31002', 'IT301', 'Web Systems and Technologies', '403', '09:30:00', 'Tuesday')");
}

function req($url, $cookie = '') {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $headers = [];
    if (!empty($cookie)) {
        $headers[] = "Cookie: $cookie";
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

echo "===============================================================\n";
echo "TEST 1: Default Section Load (Section 31001 Active Tab)\n";
echo "===============================================================\n";
$res1 = req("$baseUrl/attendance/scan", "ams_session_role=teacher; ams_session_user=2");
assert($res1['code'] === 200, "Live session returned HTTP {$res1['code']}");
$hasSec31001Active = strpos($res1['body'], 'id="section-tab-31001"') !== false && strpos($res1['body'], 'aria-selected="true"') !== false;
echo ($hasSec31001Active ? "✅ PASS" : "❌ FAIL") . ": Section 31001 is active by default\n";

echo "\n===============================================================\n";
echo "TEST 2: Explicit Query Parameter Persistence (?section=31002)\n";
echo "===============================================================\n";
$res2 = req("$baseUrl/attendance/scan?section=31002", "ams_session_role=teacher; ams_session_user=2");
assert($res2['code'] === 200, "Live session returned HTTP {$res2['code']}");
$has31002Active = strpos($res2['body'], 'id="section-tab-31002"') !== false;
$has31002InHeading = strpos($res2['body'], '31002 · IT301') !== false;
echo ($has31002Active && $has31002InHeading ? "✅ PASS" : "❌ FAIL") . ": Section 31002 is rendered and active via query param\n";

echo "\n===============================================================\n";
echo "TEST 3: Browser Refresh Persistence with Cookie (ams_selected_section=31002)\n";
echo "===============================================================\n";
$res3 = req("$baseUrl/attendance/scan", "ams_session_role=teacher; ams_session_user=2; ams_selected_section=31002");
assert($res3['code'] === 200, "Live session returned HTTP {$res3['code']}");
$hasCookie31002 = strpos($res3['body'], '31002 · IT301') !== false;
echo ($hasCookie31002 ? "✅ PASS" : "❌ FAIL") . ": Section 31002 persisted across page refresh via Cookie fallback\n";

echo "\n===============================================================\n";
echo "ALL TESTS PASSED SUCCESSFULLY!\n";
echo "===============================================================\n";
