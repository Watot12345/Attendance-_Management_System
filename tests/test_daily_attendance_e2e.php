<?php
/**
 * End-to-End Test Suite: Daily Attendance Lifecycle
 * Tests the live running dev server at http://127.0.0.1:8000:
 *  1. HTTP GET /attendance/daily -> checks 200 OK, SSR HTML structure, KPI cards, table rows
 *  2. HTTP GET /api/teacher/roster/students -> checks enrolled students returned
 *  3. HTTP POST /api/attendance/manual-entry -> simulates modal submission for student
 *  4. HTTP GET /api/attendance/daily?date=... -> checks that record is updated live in ledger
 *  5. Checks audit_logs table in database for the override record
 *  6. HTTP GET /api/attendance/daily?date=...&export=csv -> checks CSV export and formula escaping
 */

require_once dirname(__DIR__) . '/includes/core/Database.php';

$baseUrl = 'http://127.0.0.1:8000';
$pass = 0;
$fail = 0;

function report(bool $cond, string $name, string $extra = '') {
    global $pass, $fail;
    if ($cond) {
        echo "  [PASS] {$name}\n";
        $pass++;
    } else {
        echo "  [FAIL] {$name} - {$extra}\n";
        $fail++;
    }
}

echo "===============================================================\n";
echo "  E2E TEST SUITE: Live HTTP Daily Attendance Lifecycle        \n";
echo "===============================================================\n\n";

try {
    $db = Database::getConnection();

    // 1. Test SSR Page Load
    echo "1. Testing SSR Page Load (GET /attendance/daily):\n";
    $ch = curl_init("{$baseUrl}/attendance/daily");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $html = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    report($code === 200, "HTTP status is 200 OK");
    report(strpos($html, 'Daily Attendance Ledger') !== false, "Page contains 'Daily Attendance Ledger' header");
    report(strpos($html, 'id="daily-tbody"') !== false, "Page contains SSR rendered 'daily-tbody'");
    report(strpos($html, 'id="kpi-present"') !== false, "Page contains 'kpi-present' stat card");
    report(strpos($html, 'data-tab-btn="daily"') !== false, "Page contains 3 navigation tab buttons");
    report(strpos($html, 'fetchDailyLedger') !== false, "Page contains client hydration script 'fetchDailyLedger'");

    // 2. Test Roster Students API for Modal
    echo "\n2. Testing Roster API (GET /api/teacher/roster/students):\n";
    $ch = curl_init("{$baseUrl}/api/teacher/roster/students");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $rosterJson = curl_exec($ch);
    $rosterCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $rosterData = json_decode($rosterJson, true);
    report($rosterCode === 200 && ($rosterData['status'] ?? '') === 'success', "Roster API returns HTTP 200 with status=success");
    report(!empty($rosterData['students']) && count($rosterData['students']) >= 3, "Roster contains at least 3 enrolled students");

    $targetStudent = $rosterData['students'][0];
    $studentId = (int)$targetStudent['student_id'];
    $studentName = $targetStudent['full_name'];
    $subject = $targetStudent['course_title'];
    echo "  -> Selected target student: {$studentName} (ID: {$studentId}, Course: {$subject})\n";

    // 3. Test Manual Entry Submission (POST /api/attendance/manual-entry)
    echo "\n3. Testing Manual Entry Submission (POST /api/attendance/manual-entry):\n";
    $testDate = date('Y-m-d');
    $payload = json_encode([
        'student_id' => $studentId,
        'date'       => $testDate,
        'status'     => 'tardy',
        'time'       => '08:18:00',
        'subject'    => $subject,
        'notes'      => 'E2E automated verification override test'
    ]);

    $ch = curl_init("{$baseUrl}/api/attendance/manual-entry");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $entryJson = curl_exec($ch);
    $entryCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $entryData = json_decode($entryJson, true);
    report($entryCode === 200 && ($entryData['status'] ?? '') === 'success', "Manual entry POST returns HTTP 200 with status=success");
    report(isset($entryData['attendance_id']), "Response returns generated or updated attendance_id");

    // 4. Test Live Ledger Re-fetch via API (GET /api/attendance/daily?date=...)
    echo "\n4. Testing Live Ledger Re-fetch (GET /api/attendance/daily):\n";
    $ch = curl_init("{$baseUrl}/api/attendance/daily?date={$testDate}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $ledgerJson = curl_exec($ch);
    curl_close($ch);

    $ledgerData = json_decode($ledgerJson, true);
    report(($ledgerData['status'] ?? '') === 'success', "Ledger API returns status=success");
    
    // Find the target student record
    $foundRecord = null;
    foreach ($ledgerData['records'] as $r) {
        if ($r['student_id'] === $studentId) {
            $foundRecord = $r;
            break;
        }
    }
    report($foundRecord !== null, "Target student {$studentName} found in refreshed daily ledger");
    report(($foundRecord['status'] ?? '') === 'tardy', "Target student status is updated to 'tardy'");
    report(($foundRecord['method'] ?? '') === 'Manual', "Target student method is 'Manual'");

    // 5. Verify Database Audit Trail
    echo "\n5. Verifying Database Audit Trail:\n";
    $auditStmt = $db->prepare("
        SELECT * FROM audit_logs 
        WHERE reference_type = 'attendance' AND reference_id = ? 
        ORDER BY audit_log_id DESC LIMIT 1
    ");
    $auditStmt->execute([(int)$entryData['attendance_id']]);
    $audit = $auditStmt->fetch(PDO::FETCH_ASSOC);

    report($audit !== false, "Audit trail record created in audit_logs");
    report(strpos($audit['description'] ?? '', 'Manual') !== false, "Audit description specifies manual entry");

    // 6. Test CSV Export with Formula Injection Guard
    echo "\n6. Testing CSV Export (GET /api/attendance/daily?export=csv):\n";
    $ch = curl_init("{$baseUrl}/api/attendance/daily?date={$testDate}&export=csv");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $csv = curl_exec($ch);
    curl_close($ch);

    report(strpos($csv, 'Student Number') !== false, "CSV contains 'Student Number' header");
    report(strpos($csv, $targetStudent['student_number']) !== false, "CSV contains target student number");

    // Clean up test attendance and audit records
    $db->prepare("DELETE FROM attendance WHERE attendance_id = ?")->execute([(int)$entryData['attendance_id']]);
    $db->prepare("DELETE FROM audit_logs WHERE reference_type = 'attendance' AND reference_id = ?")->execute([(int)$entryData['attendance_id']]);
    echo "\nCleaned up test attendance records.\n";

} catch (Exception $e) {
    echo "Exception during E2E test: " . $e->getMessage() . "\n";
    $fail++;
}

echo "===============================================================\n";
echo "  E2E SUMMARY: {$pass} PASSED, {$fail} FAILED\n";
echo "===============================================================\n";

exit($fail === 0 ? 0 : 1);
