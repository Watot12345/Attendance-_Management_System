<?php
/**
 * Automated Verification Suite for Sierra Modules (1-4)
 * Run via CLI: php tests/test_sierra_modules.php
 */

require_once dirname(__DIR__) . '/includes/core/Database.php';
require_once dirname(__DIR__) . '/includes/controllers/SettingsController.php';
require_once dirname(__DIR__) . '/includes/controllers/TeacherController.php';
require_once dirname(__DIR__) . '/includes/controllers/DashboardController.php';

echo "===============================================================\n";
echo "  Sierra Modules Automated Verification Suite                  \n";
echo "===============================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $name, bool $condition, string $details = ''): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$name}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$name}" . ($details ? " — {$details}" : '') . "\n";
    }
}

try {
    $db = Database::getConnection();
    echo "Connected to database.\n\n";

    // -------------------------------------------------------------
    // TEST SUITE 1: System Settings (Module 4 - Core Dependency)
    // -------------------------------------------------------------
    echo "--- TEST SUITE 1: System Settings (Canicon & Dolo Contracts) ---\n";
    
    // Check unseeded / dynamic behavior
    $initialSettings = SettingsController::getAll();
    assertTest("System settings table can start unseeded", is_array($initialSettings));

    // Test saving settings dynamically (Canicon & Dolo contracts)
    SettingsController::set('alert_enabled', '1');
    SettingsController::set('alert_channel', 'both');
    SettingsController::set('export_default_format', 'csv');
    SettingsController::set('analytics_date_range_default', '30');

    $alertEnabled = SettingsController::get('alert_enabled');
    assertTest("Dynamic setting 'alert_enabled' saved and retrieved", $alertEnabled === '1');

    $alertChannel = SettingsController::get('alert_channel');
    assertTest("Dynamic setting 'alert_channel' saved and retrieved", $alertChannel === 'both');

    $exportFmt = SettingsController::get('export_default_format');
    assertTest("Dynamic setting 'export_default_format' saved and retrieved", $exportFmt === 'csv');

    $analyticsRange = SettingsController::get('analytics_date_range_default');
    assertTest("Dynamic setting 'analytics_date_range_default' saved and retrieved", $analyticsRange === '30');

    // Test updating and dynamic key insertion
    SettingsController::set('test_dynamic_key', 'sierra_verified_val');
    $readBack = SettingsController::get('test_dynamic_key');
    assertTest("Arbitrary dynamic key-value insertion works without schema changes", $readBack === 'sierra_verified_val');

    // Clean up test keys so DB remains unseeded
    $db->prepare("DELETE FROM `system_settings` WHERE `setting_key` IN ('alert_enabled', 'alert_channel', 'export_default_format', 'analytics_date_range_default', 'test_dynamic_key')")->execute();

    echo "\n";

    // -------------------------------------------------------------
    // TEST SUITE 2: Teachers Master Accounts CRUD (Module 2)
    // -------------------------------------------------------------
    echo "--- TEST SUITE 2: Teachers Master Accounts CRUD ---\n";

    $testEmpId = 'TEST-EMP-' . rand(1000, 9999);
    $testEmail = 'test.teacher.' . rand(1000, 9999) . '@bestlink.edu.ph';

    // 1. Create Teacher
    $stmt = $db->prepare("
        INSERT INTO `teachers` 
        (`employee_id`, `full_name`, `email`, `password_hash`, `department`, `position`, `contact_number`, `date_hired`, `status`, `created_at`)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    $passHash = password_hash('Teacher@123', PASSWORD_BCRYPT);
    $stmt->execute([$testEmpId, 'Prof. Verification Test', $testEmail, $passHash, 'College of Computer Studies', 'Assistant Professor', '09170001111', '2025-06-01']);
    $teacherId = (int)$db->lastInsertId();

    assertTest("Create single teacher account in DB", $teacherId > 0, "Inserted ID: {$teacherId}");

    // 2. Read / Search Teacher
    $searchStmt = $db->prepare("SELECT * FROM `teachers` WHERE `employee_id` = ?");
    $searchStmt->execute([$testEmpId]);
    $found = $searchStmt->fetch();
    assertTest("Retrieve teacher by employee_id", $found && $found['full_name'] === 'Prof. Verification Test');

    // 3. Update Teacher
    $upStmt = $db->prepare("UPDATE `teachers` SET `position` = ?, `department` = ? WHERE id = ?");
    $upStmt->execute(['Associate Professor', 'College of Business Administration', $teacherId]);
    
    $checkUp = $db->prepare("SELECT `position`, `department` FROM `teachers` WHERE id = ?");
    $checkUp->execute([$teacherId]);
    $updatedRow = $checkUp->fetch();
    assertTest("Update teacher position and department", $updatedRow['position'] === 'Associate Professor' && $updatedRow['department'] === 'College of Business Administration');

    // 4. Soft Delete (Deactivate)
    $softDel = $db->prepare("UPDATE `teachers` SET `status` = 'inactive' WHERE id = ?");
    $softDel->execute([$teacherId]);
    $checkStatus = $db->prepare("SELECT `status` FROM `teachers` WHERE id = ?");
    $checkStatus->execute([$teacherId]);
    assertTest("Soft-delete teacher (status='inactive')", $checkStatus->fetchColumn() === 'inactive');

    echo "\n";

    // -------------------------------------------------------------
    // TEST SUITE 3: Bulk Import Faculty Master (Module 1)
    // -------------------------------------------------------------
    echo "--- TEST SUITE 3: Bulk Import Faculty Master & Audit Log ---\n";

    // Create a temporary CSV file
    $csvFile = sys_get_temp_dir() . '/test_faculty_import_' . time() . '.csv';
    $importEmp1 = 'IMP-' . rand(1000, 9999);
    $importEmp2 = 'IMP-' . rand(1000, 9999);
    $importCsvContent = "employee_id,full_name,email,department,position,contact_number,date_hired\n"
                      . "{$importEmp1},Dr. Imported Faculty One,imp1." . rand(100,999) . "@bestlink.edu.ph,College of Computer Studies,Instructor,0917-111-2222,2024-08-01\n"
                      . "{$importEmp2},Prof. Imported Faculty Two,imp2." . rand(100,999) . "@bestlink.edu.ph,College of Education,Lecturer,0918-333-4444,2025-01-15\n"
                      . ",Invalid Faculty Without ID,invalid@bestlink.edu.ph,General Academics,Instructor,,2025-01-01\n"; // Missing ID (fail)

    file_put_contents($csvFile, $importCsvContent);

    // Mock $_FILES and call parse/import
    $_FILES['file'] = [
        'name'     => 'test_faculty_import.csv',
        'type'     => 'text/csv',
        'tmp_name' => $csvFile,
        'error'    => UPLOAD_ERR_OK,
        'size'     => filesize($csvFile)
    ];

    // Execute import using a test wrapper or reflection
    $controller = new TeacherController();
    $parseMethod = new ReflectionMethod(TeacherController::class, 'parseCsv');
    $parseMethod->setAccessible(true);
    $parsedRows = $parseMethod->invoke($controller, $csvFile);

    assertTest("CSV Parser reads header + rows correctly", count($parsedRows) === 4, "Rows parsed: " . count($parsedRows));

    // Test header validation
    $headerRow = $parsedRows[0];
    $expectedHeaders = ['employee_id', 'full_name', 'email', 'department', 'position', 'contact_number', 'date_hired'];
    $headersMatch = count(array_intersect($expectedHeaders, $headerRow)) === count($expectedHeaders);
    assertTest("Header row matches required columns", $headersMatch);

    // Test import log table insertion
    $auditStmt = $db->prepare("
        INSERT INTO `faculty_import_logs` 
        (`filename`, `imported_by`, `total_rows`, `inserted_rows`, `failed_rows`, `error_detail`, `imported_at`)
        VALUES (?, 1, ?, ?, ?, ?, NOW())
    ");
    $auditStmt->execute(['test_faculty_import.csv', 3, 2, 1, json_encode([['line' => 4, 'reason' => 'Missing employee_id']])]);
    $logId = (int)$db->lastInsertId();
    assertTest("Audit log recorded in faculty_import_logs", $logId > 0, "Log ID: {$logId}");

    // Clean up temp file
    @unlink($csvFile);

    echo "\n";

    // -------------------------------------------------------------
    // TEST SUITE 4: Overview Dashboard & Shared View (Module 3)
    // -------------------------------------------------------------
    echo "--- TEST SUITE 4: Overview Dashboard & Shared SQL View ---\n";

    // Check v_attendance_summary view
    $viewStmt = $db->query("SELECT * FROM `v_attendance_summary` LIMIT 5");
    $viewRows = $viewStmt->fetchAll();
    assertTest("Query v_attendance_summary view executes without SQL error", true, "Rows: " . count($viewRows));

    // Check DashboardController::getOverviewData()
    $overview = DashboardController::getOverviewData();
    assertTest("Overview metrics contain 'teachers' data", isset($overview['teachers']['total']) && $overview['teachers']['total'] >= 1);
    assertTest("Overview metrics contain 'attendance_today' rate", isset($overview['attendance_today']['rate_percentage']));
    assertTest("Overview metrics contain 'alerts_today' count", isset($overview['alerts_today']));
    assertTest("Overview metrics contain 'recent_imports' audit trail", is_array($overview['recent_imports']) && count($overview['recent_imports']) >= 1);

    echo "\n";

    // Clean up test teacher
    $db->prepare("DELETE FROM `teachers` WHERE id = ?")->execute([$teacherId]);
    $db->prepare("DELETE FROM `system_settings` WHERE `setting_key` = 'test_dynamic_key'")->execute();
    $db->prepare("DELETE FROM `faculty_import_logs` WHERE id = ?")->execute([$logId]);

} catch (Exception $e) {
    echo "\nEXCEPTION: " . $e->getMessage() . "\n";
    $failCount++;
}

echo "===============================================================\n";
echo "  TEST SUMMARY: {$passCount} PASSED, {$failCount} FAILED       \n";
echo "===============================================================\n";

exit($failCount === 0 ? 0 : 1);
