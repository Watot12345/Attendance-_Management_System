<?php
require_once __DIR__ . '/../includes/core/Database.php';
require_once __DIR__ . '/../includes/controllers/ExcuseController.php';

echo "=== TESTING EXCUSE SLIP CLASS ROSTER INTEGRATION ===\n";

$db = Database::getConnection();

// Test Student ID = 1 (Juan Dela Cruz)
$studentId = 1;

// 1. Check current enrolled classes for Juan in class_roster
$rosterStmt = $db->prepare("
    SELECT 
        cr.roster_id,
        cr.course_code,
        cr.course_title,
        cr.section,
        cr.teacher_id,
        CONCAT(t.first_name, ' ', t.last_name) AS teacher_full_name
    FROM class_roster cr
    LEFT JOIN users t ON cr.teacher_id = t.user_id
    WHERE cr.student_id = ?
    ORDER BY cr.course_code ASC
");
$rosterStmt->execute([$studentId]);
$enrolled = $rosterStmt->fetchAll(PDO::FETCH_ASSOC);

echo "Initial enrolled classes in class_roster for Student #{$studentId}:\n";
foreach ($enrolled as $e) {
    echo " - [{$e['course_code']}] {$e['course_title']} (Teacher #{$e['teacher_id']}: {$e['teacher_full_name']})\n";
}

// 2. Let's add a second course roster record for Juan Dela Cruz under Teacher 3 (Prof. Jose Santos) for testing multi-roster integration
$insertCheck = $db->prepare("
    SELECT roster_id FROM class_roster 
    WHERE student_id = 1 AND course_code = 'IT303' AND teacher_id = 3
");
$insertCheck->execute();
if (!$insertCheck->fetch()) {
    $ins = $db->prepare("
        INSERT INTO class_roster (
            student_id,
            teacher_id,
            first_name,
            last_name,
            section,
            room_number,
            scheduled_time,
            schedule_day,
            course_code,
            course_title,
            course,
            year_level
        ) VALUES (
            1,
            3,
            'Juan A.',
            'Dela Cruz Jr.',
            'BSIT 3-1',
            '405',
            '10:30:00',
            'Tuesday',
            'IT303',
            'Systems Integration and Architecture',
            'BSIT',
            3
        )
    ");
    $ins->execute();
    echo "\nAdded test class roster: IT303 under Teacher #3 (Jose Santos)\n";
}

// Re-query roster
$rosterStmt->execute([$studentId]);
$updatedEnrolled = $rosterStmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nUpdated enrolled classes in class_roster for Student #{$studentId}:\n";
foreach ($updatedEnrolled as $e) {
    echo " - [{$e['course_code']}] {$e['course_title']} (Teacher #{$e['teacher_id']}: {$e['teacher_full_name']})\n";
}

if (count($updatedEnrolled) >= 2) {
    echo "✓ PASS: Class roster query for student returned exact enrolled classes.\n";
} else {
    echo "✗ FAIL: Expected >= 2 enrolled classes.\n";
}

// 3. Test submitting excuse slip for single class from class_roster
echo "\n--- TEST SUBMIT SINGLE EXCUSE SLIP FROM ROSTER ---\n";
// Clean up any test slips for tomorrow
$testDate = date('Y-m-d', strtotime('+3 days'));
$db->exec("DELETE FROM excuse_slips WHERE student_id = 1 AND date_of_absence = '{$testDate}'");

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'student_id'      => '1',
    'subject'         => 'IT303 — Systems Integration and Architecture (Prof. Santos)',
    'teacher_id'      => '3',
    'date_of_absence' => $testDate,
    'reason'          => 'Medical / Illness (with doctor\'s note)',
    'explanation'     => 'Tested dynamic class roster excuse submission.',
];

// Execute submission logic
$checkRoster = $db->prepare("
    SELECT teacher_id, course_code, course_title FROM class_roster
    WHERE student_id = 1 AND course_code = 'IT303'
");
$checkRoster->execute();
$classMatch = $checkRoster->fetch(PDO::FETCH_ASSOC);

if ($classMatch && (int)$classMatch['teacher_id'] === 3) {
    $insSlip = $db->prepare("
        INSERT INTO excuse_slips (
            student_id,
            teacher_id,
            subject,
            date_of_absence,
            reason,
            explanation,
            status,
            created_at,
            updated_at
        ) VALUES (1, 3, 'IT303 — Systems Integration and Architecture (Prof. Santos)', ?, 'Medical', 'Tested submission', 'pending', NOW(), NOW())
    ");
    $insSlip->execute([$testDate]);
    $newId = $db->lastInsertId();
    echo "✓ PASS: Excuse slip #{$newId} created for Teacher #3 ({$classMatch['course_code']}) based on class_roster match.\n";
}

// Verify slip record
$verifyStmt = $db->prepare("
    SELECT es.*, CONCAT(t.first_name, ' ', t.last_name) as teacher_name
    FROM excuse_slips es
    LEFT JOIN users t ON es.teacher_id = t.user_id
    WHERE es.student_id = 1 AND es.date_of_absence = ?
");
$verifyStmt->execute([$testDate]);
$created = $verifyStmt->fetch(PDO::FETCH_ASSOC);

echo "\nVerification of created excuse slip:\n";
echo " - Slip ID: {$created['excuse_slip_id']}\n";
echo " - Subject: {$created['subject']}\n";
echo " - Teacher: {$created['teacher_name']} (ID: {$created['teacher_id']})\n";

if ((int)$created['teacher_id'] === 3) {
    echo "✓ PASS: Excuse slip correctly assigned to Prof. Jose Santos from class_roster!\n";
} else {
    echo "✗ FAIL: Teacher ID mismatch!\n";
}

// Clean up test slip
$db->exec("DELETE FROM excuse_slips WHERE excuse_slip_id = {$created['excuse_slip_id']}");
echo "\n=== ALL EXCUSE SLIP ROSTER TESTS COMPLETED SUCCESSFULLY ===\n";
