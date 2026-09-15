<?php
/**
 * End-to-End Test for qr_sessions `is_active` column, anti-duplication, 30m auto-close, and active session check.
 */
require_once __DIR__ . '/../includes/core/Database.php';

function testLog($name, $pass, $details = '') {
    echo ($pass ? "✅ PASS: " : "❌ FAIL: ") . $name . ($details ? " -> $details" : "") . "\n";
    if (!$pass) {
        exit(1);
    }
}

$db = Database::getConnection();

echo "===============================================================\n";
echo "TEST 1: Verify `is_active` column exists in `qr_sessions` table\n";
echo "===============================================================\n";

$stmt = $db->query("SHOW COLUMNS FROM qr_sessions LIKE 'is_active'");
$col = $stmt->fetch(PDO::FETCH_ASSOC);
testLog("`is_active` column exists", !empty($col), "Type: " . ($col['Type'] ?? 'N/A'));
testLog("`is_active` default is 1", (string)$col['Default'] === '1', "Default: " . $col['Default']);

echo "\n===============================================================\n";
echo "TEST 2: Generate active session via API and check is_active = 1\n";
echo "===============================================================\n";

// Clean up test section 31001 sessions first
$db->exec("UPDATE qr_sessions SET is_active = 0, `end` = '2000-01-01 00:00:00' WHERE teacher_id = 2 AND section = '31001'");

$ch = curl_init('http://127.0.0.1:8000/api/teacher/qr-session/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['section' => '31001']));
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($res, true);
testLog("Generate session returns HTTP 200", $httpCode === 200, "Response: $res");
testLog("Session returned with is_active = true", !empty($data['session']['is_active']), "is_active: " . var_export($data['session']['is_active'] ?? null, true));

$session1Id = $data['session']['qr_session_id'];
$session1Code = $data['session']['qr_code'];

// Check database value directly
$chkStmt = $db->prepare("SELECT is_active, `end` > NOW() as not_expired FROM qr_sessions WHERE qr_session_id = ?");
$chkStmt->execute([$session1Id]);
$dbRow = $chkStmt->fetch(PDO::FETCH_ASSOC);
testLog("Database has is_active = 1 for newly created session", (int)$dbRow['is_active'] === 1);
testLog("Session not_expired is 1", (int)$dbRow['not_expired'] === 1);

echo "\n===============================================================\n";
echo "TEST 3: Generating a 2nd session must DEACTIVATE previous session (No Duplicate Active)\n";
echo "===============================================================\n";

$ch = curl_init('http://127.0.0.1:8000/api/teacher/qr-session/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['section' => '31001']));
$res2 = curl_exec($ch);
curl_close($ch);

$data2 = json_decode($res2, true);
$session2Id = $data2['session']['qr_session_id'];
$session2Code = $data2['session']['qr_code'];

testLog("Second session created", $session2Id > $session1Id, "New Session ID: $session2Id (Old was $session1Id)");

// Verify Session 1 was set to is_active = 0
$chk1 = $db->prepare("SELECT is_active FROM qr_sessions WHERE qr_session_id = ?");
$chk1->execute([$session1Id]);
$s1Active = (int)$chk1->fetchColumn();
testLog("Previous Session 1 is deactivated (is_active = 0)", $s1Active === 0, "s1Active: $s1Active");

// Verify Session 2 is active
$chk2 = $db->prepare("SELECT is_active FROM qr_sessions WHERE qr_session_id = ?");
$chk2->execute([$session2Id]);
$s2Active = (int)$chk2->fetchColumn();
testLog("Current Session 2 is active (is_active = 1)", $s2Active === 1, "s2Active: $s2Active");

// Verify only 1 active session in database for section 31001
$countStmt = $db->prepare("SELECT COUNT(*) FROM qr_sessions WHERE teacher_id = 2 AND section = '31001' AND is_active = 1 AND `end` > NOW()");
$countStmt->execute();
$activeCount = (int)$countStmt->fetchColumn();
testLog("Strictly 1 active session exists for section 31001", $activeCount === 1, "Active count: $activeCount");

echo "\n===============================================================\n";
echo "TEST 4: Active Session Endpoint returns active session for section\n";
echo "===============================================================\n";

$ch = curl_init('http://127.0.0.1:8000/api/teacher/qr-session/active?section=31001');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$resAct = curl_exec($ch);
curl_close($ch);

$dataAct = json_decode($resAct, true);
testLog("Active session endpoint returns has_active_session = true", !empty($dataAct['has_active_session']));
testLog("Active session matches Session 2 ID", (int)$dataAct['session']['qr_session_id'] === (int)$session2Id);
testLog("Active sections array includes 31001", in_array('31001', $dataAct['active_sections'] ?? []));

echo "\n===============================================================\n";
echo "TEST 5: Old Deactivated Token (Session 1) is rejected on Student Scan\n";
echo "===============================================================\n";

$ch = curl_init('http://127.0.0.1:8000/api/attendance/check-in');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'qr_code'    => $session1Code,
    'student_id' => 1
]));
$resScanOld = curl_exec($ch);
$httpCodeScanOld = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$dataScanOld = json_decode($resScanOld, true);
testLog("Old deactivated token rejected", $httpCodeScanOld === 400 && ($dataScanOld['scan_code'] ?? '') === 'EXPIRED_OR_INVALID', "HTTP: $httpCodeScanOld, Code: " . ($dataScanOld['scan_code'] ?? ''));

echo "\n===============================================================\n";
echo "TEST 6: Close Session sets is_active = 0\n";
echo "===============================================================\n";

$ch = curl_init('http://127.0.0.1:8000/api/teacher/qr-session/close');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Accept: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'qr_session_id' => $session2Id,
    'section'       => '31001'
]));
$resClose = curl_exec($ch);
curl_close($ch);

$dataClose = json_decode($resClose, true);
testLog("Close session API success", !empty($dataClose['status']) && $dataClose['status'] === 'success');

// Verify in DB is_active = 0
$chkClose = $db->prepare("SELECT is_active FROM qr_sessions WHERE qr_session_id = ?");
$chkClose->execute([$session2Id]);
$s2ClosedActive = (int)$chkClose->fetchColumn();
testLog("Closed session has is_active = 0 in database", $s2ClosedActive === 0, "is_active: $s2ClosedActive");

// Verify active session endpoint returns has_active_session = false
$ch = curl_init('http://127.0.0.1:8000/api/teacher/qr-session/active?section=31001');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
$resActAfter = curl_exec($ch);
curl_close($ch);

$dataActAfter = json_decode($resActAfter, true);
testLog("Active session endpoint reports has_active_session = false after close", empty($dataActAfter['has_active_session']));

echo "\n===============================================================\n";
echo "TEST 7: Auto-Close after 30 minutes deactivates session\n";
echo "===============================================================\n";

// Insert an expired session with end in the past but is_active = 1
$db->exec("INSERT INTO qr_sessions (teacher_id, section, qr_code, start, `end`, is_active, created_at) VALUES (2, '31001', '999888', DATE_SUB(NOW(), INTERVAL 35 MINUTE), DATE_SUB(NOW(), INTERVAL 5 MINUTE), 1, NOW())");
$expiredId = $db->lastInsertId();

// Calling active session endpoint triggers auto-deactivation
$ch = curl_init('http://127.0.0.1:8000/api/teacher/qr-session/active?section=31001');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_exec($ch);
curl_close($ch);

$chkExp = $db->prepare("SELECT is_active FROM qr_sessions WHERE qr_session_id = ?");
$chkExp->execute([$expiredId]);
$expActive = (int)$chkExp->fetchColumn();
testLog("Expired session automatically deactivated (is_active = 0)", $expActive === 0, "is_active: $expActive");

echo "\n🎉 ALL IS_ACTIVE TESTS PASSED WITH 100% SUCCESS!\n";
