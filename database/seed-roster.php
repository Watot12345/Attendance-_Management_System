<?php
require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

// Let's seed default class_roster records if empty or missing
$rosterCount = (int)$db->query("SELECT count(*) FROM class_roster")->fetchColumn();
echo "Current class_roster count: $rosterCount\n";

$classesToSeed = [
    [
        'student_id' => 1, // Juan Dela Cruz
        'teacher_id' => 2, // Prof. Manuel Ramirez
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'section' => 'BSIT 3-1',
        'room_number' => '402',
        'scheduled_time' => '08:00:00',
        'schedule_day' => 'Monday',
        'course_code' => 'IT301',
        'course_title' => 'Web Systems and Technologies',
        'major' => 'NA',
        'course' => 'BSIT',
        'year_level' => 3
    ],
    [
        'student_id' => 1, // Juan Dela Cruz
        'teacher_id' => 3, // Prof. Jose Santos
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'section' => 'BSIT 3-1',
        'room_number' => '405',
        'scheduled_time' => '10:30:00',
        'schedule_day' => 'Tuesday',
        'course_code' => 'IT303',
        'course_title' => 'Systems Integration and Architecture',
        'major' => 'NA',
        'course' => 'BSIT',
        'year_level' => 3
    ],
    [
        'student_id' => 4, // Maria Santos
        'teacher_id' => 2, // Prof. Manuel Ramirez
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'section' => 'BSIT 3-1',
        'room_number' => '402',
        'scheduled_time' => '08:00:00',
        'schedule_day' => 'Monday',
        'course_code' => 'IT301',
        'course_title' => 'Web Systems and Technologies',
        'major' => 'NA',
        'course' => 'BSIT',
        'year_level' => 3
    ],
    [
        'student_id' => 6, // Pedro Reyes
        'teacher_id' => 2, // Prof. Manuel Ramirez
        'first_name' => 'Pedro',
        'last_name' => 'Reyes',
        'section' => 'BSIT 3-1',
        'room_number' => '402',
        'scheduled_time' => '08:00:00',
        'schedule_day' => 'Monday',
        'course_code' => 'IT301',
        'course_title' => 'Web Systems and Technologies',
        'major' => 'NA',
        'course' => 'BSIT',
        'year_level' => 3
    ]
];

$ins = $db->prepare("
    INSERT INTO class_roster (
        student_id, teacher_id, first_name, last_name, section, room_number,
        scheduled_time, schedule_day, course_code, course_title, major, course, year_level, created_at, updated_at
    ) VALUES (
        :student_id, :teacher_id, :first_name, :last_name, :section, :room_number,
        :scheduled_time, :schedule_day, :course_code, :course_title, :major, :course, :year_level, NOW(), NOW()
    )
");

foreach ($classesToSeed as $c) {
    $check = $db->prepare("
        SELECT roster_id FROM class_roster 
        WHERE student_id = ? AND teacher_id = ? AND course_code = ? AND section = ?
    ");
    $check->execute([$c['student_id'], $c['teacher_id'], $c['course_code'], $c['section']]);
    if (!$check->fetch()) {
        $ins->execute($c);
        echo "✓ Seeded class_roster for Student #{$c['student_id']} - {$c['course_code']} ({$c['course_title']})\n";
    }
}
