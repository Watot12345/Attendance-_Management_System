<?php
/**
 * Test Suite: Daily Attendance Backend & Component 1 Verification
 * Runs each controller endpoint test in isolated processes to test full execution and exit handling.
 */

require_once dirname(__DIR__) . '/includes/core/Database.php';

$passCount = 0;
$failCount = 0;

function assertTest(bool $condition, string $testName, string $details = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        echo "  PASS: {$testName}\n";
        $passCount++;
    } else {
        echo "  FAIL: {$testName} - {$details}\n";
        $failCount++;
    }
}

function runPhpSnippet(string $code): string {
    $script = "
        require_once 'includes/core/Database.php';
        require_once 'includes/controllers/AttendanceController.php';
        {$code}
    ";
    $cmd = 'php -r ' . escapeshellarg($script);
    $out = shell_exec($cmd);
    return (string)$out;
}

echo "===============================================================\n";
echo "  TEST SUITE: Daily Attendance Backend (Component 1)          \n";
echo "===============================================================\n\n";

try {
    $db = Database::getConnection();

    // -------------------------------------------------------------
    // Test 1: Verify UNIQUE constraint on attendance table
    // -------------------------------------------------------------
    echo "1. Database Schema & Constraint Check:\n";
    $idxStmt = $db->query("
        SELECT COUNT(*) 
        FROM information_schema.statistics 
        WHERE table_schema = DATABASE() 
          AND table_name = 'attendance' 
          AND index_name = 'uq_attendance_student_date_subject'
    ");
    $hasUnique = ((int)$idxStmt->fetchColumn() > 0);
    assertTest($hasUnique, "UNIQUE index `uq_attendance_student_date_subject` exists on `attendance`");

    // -------------------------------------------------------------
    // Test 2: apiGetRosterStudents() - Teacher SQL-level scoping
    // -------------------------------------------------------------
    echo "\n2. apiGetRosterStudents() Endpoint:\n";
    $codeRoster = '
        $_SESSION["teacher_id"] = 2;
        $c = new AttendanceController();
        $c->apiGetRosterStudents();
    ';
    $rosterJson = runPhpSnippet($codeRoster);
    $rosterData = json_decode($rosterJson, true);
    assertTest(is_array($rosterData) && ($rosterData['status'] ?? '') === 'success', "apiGetRosterStudents returns success JSON");
    assertTest(($rosterData['teacher_id'] ?? 0) === 2, "apiGetRosterStudents resolves teacher_id 2");
    assertTest(!empty($rosterData['students']), "apiGetRosterStudents returns non-empty students list");

    $firstStudent = $rosterData['students'][0] ?? null;
    assertTest($firstStudent !== null && isset($firstStudent['student_id'], $firstStudent['full_name'], $firstStudent['section']),
        "Student record contains student_id, full_name, section");

    // -------------------------------------------------------------
    // Test 3: apiDailyAttendance() - JSON Ledger & Metrics
    // -------------------------------------------------------------
    echo "\n3. apiDailyAttendance() Endpoint (JSON):\n";
    $codeDaily = '
        $_SESSION["teacher_id"] = 2;
        $_GET = ["date" => "2026-09-11"];
        $c = new AttendanceController();
        $c->apiDailyAttendance();
    ';
    $dailyJson = runPhpSnippet($codeDaily);
    $dailyData = json_decode($dailyJson, true);
    assertTest(is_array($dailyData) && ($dailyData['status'] ?? '') === 'success', "apiDailyAttendance returns success JSON");
    assertTest(isset($dailyData['metrics']), "apiDailyAttendance contains metrics KPI structure");
    assertTest(isset($dailyData['records']) && is_array($dailyData['records']), "apiDailyAttendance contains records array");

    // Verify QR checkin was detected
    $foundQrPresent = false;
    foreach ($dailyData['records'] as $rec) {
        if ($rec['student_id'] === 6 && $rec['status'] === 'present' && $rec['method'] === 'QR') {
            $foundQrPresent = true;
            break;
        }
    }
    assertTest($foundQrPresent, "Student 6 correctly loaded with status 'present' and method 'QR' for 2026-09-11");

    // -------------------------------------------------------------
    // Test 4: apiDailyAttendance() - Formula Injection Escaping in CSV
    // -------------------------------------------------------------
    echo "\n4. apiDailyAttendance() CSV Export & Formula Injection Prevention:\n";
    $codeCsv = '
        $_SESSION["teacher_id"] = 2;
        $_GET = ["date" => "2026-09-11", "export" => "csv"];
        $c = new AttendanceController();
        $c->apiDailyAttendance();
    ';
    $csvOutput = runPhpSnippet($codeCsv);
    assertTest(!empty($csvOutput) && strpos($csvOutput, 'Student Number') !== false && strpos($csvOutput, 'Student Name') !== false,
        "CSV export generates valid CSV header");

    // Test cell formula escaping
    $codeEscape = '
        $c = new AttendanceController();
        $ref = new ReflectionMethod("AttendanceController", "escapeCsvCell");
        $ref->setAccessible(true);
        echo json_encode([
            "equals" => $ref->invoke($c, "=SUM(A1:A10)"),
            "plus"   => $ref->invoke($c, "+123456"),
            "minus"  => $ref->invoke($c, "-CMD|"),
            "at"     => $ref->invoke($c, "@echo off"),
            "normal" => $ref->invoke($c, "Juan Dela Cruz")
        ]);
    ';
    $escapeJson = runPhpSnippet($codeEscape);
    $escaped = json_decode($escapeJson, true);

    assertTest($escaped['equals'] === "'=SUM(A1:A10)", "Formula starting with '=' is prefixed with single quote");
    assertTest($escaped['plus'] === "'+123456", "Formula starting with '+' is prefixed with single quote");
    assertTest($escaped['minus'] === "'-CMD|", "Formula starting with '-' is prefixed with single quote");
    assertTest($escaped['at'] === "'@echo off", "Formula starting with '@' is prefixed with single quote");
    assertTest($escaped['normal'] === "Juan Dela Cruz", "Normal string is preserved without modification");

    // -------------------------------------------------------------
    // Test 5: apiManualEntry() - Authorization check
    // -------------------------------------------------------------
    echo "\n5. apiManualEntry() Roster Authorization Check:\n";
    $codeAuth = '
        $_SESSION["teacher_id"] = 2;
        $_POST = [
            "student_id" => 999999,
            "date"       => "2026-09-14",
            "status"     => "present"
        ];
        $c = new AttendanceController();
        $c->apiManualEntry();
    ';
    $authJson = runPhpSnippet($codeAuth);
    $authData = json_decode($authJson, true);
    assertTest(is_array($authData) && ($authData['status'] ?? '') === 'error', "Non-roster student rejected with error status");
    assertTest(strpos($authData['message'] ?? '', 'Authorization failed') !== false, "Rejection message cites authorization failure");

    // -------------------------------------------------------------
    // Test 6: apiManualEntry() - Create & Overwrite-Wins with Audit
    // -------------------------------------------------------------
    echo "\n6. apiManualEntry() Overwrite-Wins & Audit Trail:\n";
    $testStudentId = 7; // Ana Gonzales from roster
    $testDate = '2026-09-14';
    $testSubject = 'Web Systems and Technologies';

    // Clean initial state
    $db->prepare("DELETE FROM attendance WHERE student_id = ? AND date = ? AND subject = ?")
       ->execute([$testStudentId, $testDate, $testSubject]);

    // Step A: Insert a mock QR check-in
    $db->prepare("
        INSERT INTO attendance (student_id, teacher_id, qr_session_id, date, time, subject, status, schedule_date, created_at)
        VALUES (?, ?, 8, ?, '08:05:00', ?, 'present', ?, NOW())
    ")->execute([$testStudentId, 2, $testDate, $testSubject, $testDate]);
    $insertedQrId = (int)$db->lastInsertId();

    $codeManual = '
        $_SESSION["teacher_id"] = 2;
        $_POST = [
            "student_id" => 7,
            "date"       => "2026-09-14",
            "status"     => "tardy",
            "time"       => "08:25:00",
            "subject"    => "Web Systems and Technologies",
            "notes"      => "Arrived late with clinic hall pass"
        ];
        $c = new AttendanceController();
        $c->apiManualEntry();
    ';
    $manualJson = runPhpSnippet($codeManual);
    $manualData = json_decode($manualJson, true);

    assertTest(is_array($manualData) && ($manualData['status'] ?? '') === 'success', "apiManualEntry returns success JSON on override");
    assertTest(($manualData['action'] ?? '') === 'overwrite', "apiManualEntry detects existing row and executes 'overwrite'");
    assertTest(($manualData['old_method'] ?? '') === 'QR', "apiManualEntry captures old_method as 'QR'");
    assertTest(($manualData['old_status'] ?? '') === 'present', "apiManualEntry captures old_status as 'present'");
    assertTest(($manualData['new_status'] ?? '') === 'tardy', "apiManualEntry updates new_status to 'tardy'");

    // Verify DB state
    $updatedRow = $db->query("SELECT * FROM attendance WHERE attendance_id = $insertedQrId")->fetch(PDO::FETCH_ASSOC);
    assertTest($updatedRow['status'] === 'tardy', "Database row status successfully updated to 'tardy'");
    assertTest($updatedRow['qr_session_id'] === null, "Database row qr_session_id is cleared (manual wins)");
    assertTest($updatedRow['time'] === '08:25:00', "Database row arrival time updated to 08:25:00");

    // Verify Audit Log
    $auditStmt = $db->prepare("
        SELECT * FROM audit_logs 
        WHERE reference_type = 'attendance' AND reference_id = ? 
        ORDER BY audit_log_id DESC LIMIT 1
    ");
    $auditStmt->execute([$insertedQrId]);
    $auditRow = $auditStmt->fetch(PDO::FETCH_ASSOC);
    assertTest($auditRow !== false, "Audit log created for manual entry override");
    assertTest(strpos($auditRow['description'], 'replaced QR') !== false && strpos($auditRow['description'], 'old_status: present') !== false,
        "Audit log description records replaced method and old_status");

    // Clean up test row
    $db->prepare("DELETE FROM attendance WHERE student_id = ? AND date = ? AND subject = ?")
       ->execute([$testStudentId, $testDate, $testSubject]);
    $db->prepare("DELETE FROM audit_logs WHERE reference_type = 'attendance' AND reference_id = ?")
       ->execute([$insertedQrId]);

} catch (Exception $e) {
    echo "Exception during testing: " . $e->getMessage() . "\n";
    $failCount++;
}

echo "\n---------------------------------------------------------------\n";
echo "  RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "---------------------------------------------------------------\n";

exit($failCount === 0 ? 0 : 1);
