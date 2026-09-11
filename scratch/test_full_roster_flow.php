<?php
$step = $argv[1] ?? '1';

require_once __DIR__ . '/../includes/core/Database.php';
require_once __DIR__ . '/../includes/controllers/StudentController.php';

$controller = new StudentController();
$db = Database::getConnection();

$testStudents = [
    [
        'student_id'     => '2026-099901',
        'first_name'     => 'Carlos',
        'middle_initial' => 'A',
        'last_name'      => 'Montemayor',
        'extension'      => 'Jr.',
    ],
    [
        'student_id'     => '2026-099902',
        'first_name'     => 'Bea',
        'middle_initial' => 'B',
        'last_name'      => 'Aquino',
        'extension'      => '',
    ],
    [
        'student_id'     => '2026-099903',
        'first_name'     => 'Rico',
        'middle_initial' => '',
        'last_name'      => 'Salazar',
        'extension'      => 'III',
    ],
];

$classPayload = [
    'course'         => 'BSIT',
    'year_level'     => '3',
    'section'        => 'BSIT 3-1',
    'section_num'    => '1',
    'major'          => 'NA',
    'course_code'    => 'TEST101',
    'course_title'   => 'Automated Test Systems',
    'schedule_day'   => 'Monday',
    'scheduled_time' => '08:30:00',
    'room_number'    => '402',
    'teacher_id'     => 2,
    'students'       => $testStudents,
];

$_POST = $classPayload;

if ($step === 'clean') {
    $db->exec("DELETE FROM class_roster WHERE course_code = 'TEST101'");
    $db->exec("DELETE FROM users WHERE student_id IN (2026099901, 2026099902, 2026099903, 99901, 99902, 99903)");
    echo "CLEANED\n";
} elseif ($step === 'validate') {
    $controller->validateRoster();
} elseif ($step === 'import') {
    $controller->importClassRoster();
}
