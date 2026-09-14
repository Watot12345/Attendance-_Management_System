<?php
/**
 * Test Suite: Perfect Attendance Award Tool Backend Verification
 * Tests the calculation endpoint, criteria thresholds (100% vs 98%),
 * section filtering, formula-safe CSV export, and ranking.
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
        require_once 'includes/core/Router.php';
        require_once 'includes/controllers/AttendanceController.php';
        {$code}
    ";
    $cmd = 'php -r ' . escapeshellarg($script);
    $out = shell_exec($cmd);
    return (string)$out;
}

echo "===============================================================\n";
echo "  TEST SUITE: Perfect Attendance Award Tool Backend           \n";
echo "===============================================================\n\n";

try {
    $db = Database::getConnection();

    // -------------------------------------------------------------
    // Test 1: Router Registration
    // -------------------------------------------------------------
    echo "1. Router Route Registration Check:\n";
    $codeRouter = '
        $ref = new ReflectionClass("Router");
        $prop = $ref->getProperty("routes");
        $prop->setAccessible(true);
        $routes = $prop->getValue();
        echo json_encode($routes["/api/teacher/awards/calculate"] ?? null);
    ';
    $routeJson = runPhpSnippet($codeRouter);
    $routeTarget = json_decode($routeJson, true);
    assertTest($routeTarget === 'AttendanceController@apiCalculateAwards', "Route '/api/teacher/awards/calculate' registered to 'AttendanceController@apiCalculateAwards'");

    // -------------------------------------------------------------
    // Test 2: apiCalculateAwards() Baseline JSON Response
    // -------------------------------------------------------------
    echo "\n2. apiCalculateAwards() Baseline Endpoint:\n";
    $codeBaseline = '
        $_SESSION["teacher_id"] = 2;
        $_GET = [
            "section"    => "31001",
            "start_date" => "2026-09-01",
            "end_date"   => "2026-09-30",
            "threshold"  => "100"
        ];
        $c = new AttendanceController();
        $c->apiCalculateAwards();
    ';
    $baseJson = runPhpSnippet($codeBaseline);
    $baseData = json_decode($baseJson, true);
    assertTest(is_array($baseData) && ($baseData['status'] ?? '') === 'success', "apiCalculateAwards returns success JSON");
    assertTest(($baseData['teacher_id'] ?? 0) === 2, "Response resolves teacher_id 2");
    assertTest(isset($baseData['total_sessions_held']), "Response includes total_sessions_held integer");
    assertTest(isset($baseData['candidates']) && is_array($baseData['candidates']), "Response includes candidates array");

    // -------------------------------------------------------------
    // Test 3: Fixture-based Verification of 100% Flawless Threshold
    // -------------------------------------------------------------
    echo "\n3. 100% Flawless Attendance Threshold Verification:\n";
    // Setup test dates in November 2026
    $d1 = '2026-11-02';
    $d2 = '2026-11-03';
    $d3 = '2026-11-04';
    $fixtureSubject = 'Web Systems and Technologies';

    // Students in section 31001: 1 (Juan), 4 (Maria), 6 (Pedro)
    $db->prepare("UPDATE class_roster SET section = '31001' WHERE student_id = 6")->execute();
    // Clean any prior fixture rows in that range
    $db->prepare("DELETE FROM attendance WHERE subject = ? AND date BETWEEN '2026-11-01' AND '2026-11-05'")
       ->execute([$fixtureSubject]);

    // Student 6: Flawless 3 out of 3 sessions present
    $ins = $db->prepare("INSERT INTO attendance (student_id, teacher_id, date, time, subject, status, schedule_date, created_at) VALUES (?, 2, ?, '08:00:00', ?, ?, ?, NOW())");
    $ins->execute([6, $d1, $fixtureSubject, 'present', $d1]);
    $ins->execute([6, $d2, $fixtureSubject, 'present', $d2]);
    $ins->execute([6, $d3, $fixtureSubject, 'present', $d3]);

    // Student 1: 2 present, 1 tardy
    $ins->execute([1, $d1, $fixtureSubject, 'present', $d1]);
    $ins->execute([1, $d2, $fixtureSubject, 'present', $d2]);
    $ins->execute([1, $d3, $fixtureSubject, 'tardy',   $d3]);

    // Student 4: 2 present, 1 absent
    $ins->execute([4, $d1, $fixtureSubject, 'present', $d1]);
    $ins->execute([4, $d2, $fixtureSubject, 'present', $d2]);
    $ins->execute([4, $d3, $fixtureSubject, 'absent',  $d3]);

    // Query 100% threshold
    $code100 = '
        $_SESSION["teacher_id"] = 2;
        $_GET = [
            "section"    => "31001",
            "start_date" => "2026-11-01",
            "end_date"   => "2026-11-05",
            "threshold"  => "100"
        ];
        $c = new AttendanceController();
        $c->apiCalculateAwards();
    ';
    $res100Json = runPhpSnippet($code100);
    $res100 = json_decode($res100Json, true);

    assertTest($res100['total_sessions_held'] === 3, "Identifies exactly 3 distinct session dates held");
    assertTest(count($res100['candidates']) === 1, "Only 1 student qualifies for 100% flawless attendance");
    $winner100 = $res100['candidates'][0] ?? [];
    assertTest(($winner100['student_id'] ?? 0) === 6, "Student 6 (Pedro) is the 100% flawless winner");
    assertTest(($winner100['rank'] ?? 0) === 1, "Student 6 is assigned Rank #1");
    assertTest(($winner100['present_count'] ?? 0) === 3 && ($winner100['tardy_count'] ?? 0) === 0 && ($winner100['absent_count'] ?? 0) === 0,
        "Student 6 metrics: 3 present, 0 tardy, 0 absent");

    // -------------------------------------------------------------
    // Test 4: 98%+ High Honors Threshold with Excused Absence & Max 1 Tardy
    // -------------------------------------------------------------
    echo "\n4. 98%+ High Honors Threshold Verification:\n";
    // For 98% threshold:
    // Suppose we test a window of 50 sessions, or evaluate with excused slip.
    // Let's add an approved excuse slip for student 4 on 2026-11-03
    $db->prepare("DELETE FROM excuse_slips WHERE student_id = 4 AND date_of_absence = ?")->execute([$d3]);
    $db->prepare("
        INSERT INTO excuse_slips (student_id, teacher_id, subject, date_of_absence, reason, explanation, status, created_at, updated_at)
        VALUES (4, 2, ?, ?, 'Medical emergency', 'Doctor certificate attached', 'approved', NOW(), NOW())
    ")->execute([$fixtureSubject, $d3]);

    // Student 6: 3/3 (100%) -> eligible (effective 100%)
    // Student 4: 2 present + 1 approved excused out of 3 = 3/3 (100% effective, 0 tardies) -> eligible!
    // Student 1: 2 present + 1 tardy out of 3 = 66.7% -> ineligible for 98% threshold
    $code98 = '
        $_SESSION["teacher_id"] = 2;
        $_GET = [
            "section"    => "31001",
            "start_date" => "2026-11-01",
            "end_date"   => "2026-11-05",
            "threshold"  => "98"
        ];
        $c = new AttendanceController();
        $c->apiCalculateAwards();
    ';
    $res98Json = runPhpSnippet($code98);
    $res98 = json_decode($res98Json, true);

    assertTest(count($res98['candidates']) === 2, "2 students qualify under 98%+ criteria (Student 6 and Student 4 with approved excuse slip)");
    $candIds = array_column($res98['candidates'], 'student_id');
    assertTest(in_array(6, $candIds) && in_array(4, $candIds), "Candidates contain Student 6 and Student 4");
    assertTest(!in_array(1, $candIds), "Student 1 (tardy on 3 sessions = 66.7%) is excluded");

    // -------------------------------------------------------------
    // Test 5: Section Filter Scoping
    // -------------------------------------------------------------
    echo "\n5. Section Filtering Scoping:\n";
    $codeSecMismatch = '
        $_SESSION["teacher_id"] = 2;
        $_GET = [
            "section"    => "NON-EXISTENT-999",
            "start_date" => "2026-11-01",
            "end_date"   => "2026-11-05",
            "threshold"  => "100"
        ];
        $c = new AttendanceController();
        $c->apiCalculateAwards();
    ';
    $resMismatchJson = runPhpSnippet($codeSecMismatch);
    $resMismatch = json_decode($resMismatchJson, true);
    assertTest(count($resMismatch['candidates']) === 0, "Non-matching section returns 0 candidates");

    // -------------------------------------------------------------
    // Test 6: Formula-Safe CSV Export Streaming
    // -------------------------------------------------------------
    echo "\n6. Formula Injection Prevention in CSV Export:\n";
    $codeCsvAwards = '
        $_SESSION["teacher_id"] = 2;
        $_GET = [
            "section"    => "31001",
            "start_date" => "2026-11-01",
            "end_date"   => "2026-11-05",
            "threshold"  => "100",
            "export"     => "csv"
        ];
        $c = new AttendanceController();
        $c->apiCalculateAwards();
    ';
    $csvAwardsOut = runPhpSnippet($codeCsvAwards);
    assertTest(!empty($csvAwardsOut), "CSV stream produces non-empty output");
    assertTest(strpos($csvAwardsOut, 'Rank') !== false && strpos($csvAwardsOut, 'Student Number') !== false && strpos($csvAwardsOut, 'Attendance Rate') !== false, "CSV contains correct standard header columns");
    assertTest(strpos($csvAwardsOut, 'Pedro') !== false, "CSV contains Pedro Reyes (eligible candidate)");

    // Test formula injection characters on escapeCsvCell directly
    $codeCsvInjection = '
        $c = new AttendanceController();
        $ref = new ReflectionMethod("AttendanceController", "escapeCsvCell");
        $ref->setAccessible(true);
        echo json_encode([
            "formula_equals"=> $ref->invoke($c, "=SUM(A1:A10)"),
            "formula_plus"  => $ref->invoke($c, "+2+5"),
            "formula_minus" => $ref->invoke($c, "-5-2"),
            "formula_at"    => $ref->invoke($c, "@SUM(1,2)"),
            "safe_string"   => $ref->invoke($c, "Pedro Reyes")
        ]);
    ';
    $injData = json_decode(runPhpSnippet($codeCsvInjection), true);
    assertTest($injData['formula_equals'] === "'=SUM(A1:A10)", "Formula starting with '=' is sanitized with leading single quote");
    assertTest($injData['formula_plus'] === "'+2+5", "Formula starting with '+' is sanitized with leading single quote");
    assertTest($injData['formula_minus'] === "'-5-2", "Formula starting with '-' is sanitized with leading single quote");
    assertTest($injData['formula_at'] === "'@SUM(1,2)", "Formula starting with '@' is sanitized with leading single quote");
    assertTest($injData['safe_string'] === "Pedro Reyes", "Standard text is preserved unmodified");

    // -------------------------------------------------------------
    // Clean up test fixtures
    // -------------------------------------------------------------
    $db->prepare("DELETE FROM attendance WHERE subject = ? AND date BETWEEN '2026-11-01' AND '2026-11-05'")
       ->execute([$fixtureSubject]);
    $db->prepare("DELETE FROM excuse_slips WHERE student_id = 4 AND date_of_absence = ?")
       ->execute([$d3]);

} catch (Exception $e) {
    echo "Exception during test suite: " . $e->getMessage() . "\n";
    $failCount++;
}

echo "\n---------------------------------------------------------------\n";
echo "  RESULTS: {$passCount} PASSED, {$failCount} FAILED\n";
echo "---------------------------------------------------------------\n";

exit($failCount === 0 ? 0 : 1);
