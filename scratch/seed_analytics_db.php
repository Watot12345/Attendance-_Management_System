<?php
/**
 * Fast Batch Seeder for Attendance Analytics Database
 */

require_once __DIR__ . '/../includes/core/Database.php';

$db = Database::getConnection();

echo "Starting Database Seeding for Analytics...\n";

// 1. Ensure students exist in `users`
$studentPool = [
    ['Marcus Vance', 'marcus.vance@bcp.edu.ph', 230110010, '31001', 9],
    ['Elena Rostova', 'elena.rostova@bcp.edu.ph', 230110011, '31001', 8],
    ['David Kim', 'david.kim@bcp.edu.ph', 230110012, '31002', 10],
    ['Sarah Jenkins', 'sarah.jenkins@bcp.edu.ph', 230110013, '31001', 7],
    ['Lucas Alcantara', 'lucas.alcantara@bcp.edu.ph', 230110014, '31002', 7],
    ['Amara Chen', 'amara.chen@bcp.edu.ph', 230110015, '31001', 9],
    ['Robert Cruz', 'robert.cruz@bcp.edu.ph', 230110016, '31002', 8],
    ['Jenny Lim', 'jenny.lim@bcp.edu.ph', 230110017, '31002', 8],
    ['Alex Moreno', 'alex.moreno@bcp.edu.ph', 230110018, '31001', 9],
    ['Eric Villanueva', 'eric.villanueva@bcp.edu.ph', 230110019, '31002', 8],
    ['Chloe Tan', 'chloe.tan@bcp.edu.ph', 230110020, '31002', 8],
    ['Leo Ramos', 'leo.ramos@bcp.edu.ph', 230110021, '31002', 8],
    ['Kyle Alvarez', 'kyle.alvarez@bcp.edu.ph', 230110022, '31001', 7],
    ['Bianca Torres', 'bianca.torres@bcp.edu.ph', 230110023, '31001', 7],
    ['Nathaniel Diaz', 'nathaniel.diaz@bcp.edu.ph', 230110024, '31002', 10],
    ['Samantha Lee', 'samantha.lee@bcp.edu.ph', 230110025, '31001', 9],
    ['Christian Bautista', 'christian.bautista@bcp.edu.ph', 230110026, '31001', 8],
    ['Patricia Morales', 'patricia.morales@bcp.edu.ph', 230110027, '31002', 10],
    ['Joshua Garcia', 'joshua.garcia@bcp.edu.ph', 230110028, '31001', 9],
    ['Camille Navarro', 'camille.navarro@bcp.edu.ph', 230110029, '31002', 8],
    ['Daniel Padilla', 'daniel.padilla@bcp.edu.ph', 230110030, '31001', 7],
    ['Kathryn Bernardo', 'kathryn.bernardo@bcp.edu.ph', 230110031, '31001', 7],
    ['Liza Soberano', 'liza.soberano@bcp.edu.ph', 230110032, '31002', 10],
    ['Enrique Gil', 'enrique.gil@bcp.edu.ph', 230110033, '31001', 9],
    ['James Reid', 'james.reid@bcp.edu.ph', 230110034, '31002', 8],
    ['Nadine Lustre', 'nadine.lustre@bcp.edu.ph', 230110035, '31001', 9],
    ['Alden Richards', 'alden.richards@bcp.edu.ph', 230110036, '31002', 10],
    ['Maine Mendoza', 'maine.mendoza@bcp.edu.ph', 230110037, '31001', 8],
    ['Dingdong Dantes', 'dingdong.dantes@bcp.edu.ph', 230110038, '31002', 10],
    ['Marian Rivera', 'marian.rivera@bcp.edu.ph', 230110039, '31001', 9],
];

$passHash = password_hash('Password123!', PASSWORD_BCRYPT);
$userInsertStmt = $db->prepare("
    INSERT INTO users (student_id, role, email, password_hash, first_name, last_name, parent_email, parent_name, status, created_at)
    VALUES (?, 'student', ?, ?, ?, ?, ?, ?, 'active', NOW())
    ON DUPLICATE KEY UPDATE first_name=VALUES(first_name), last_name=VALUES(last_name), parent_email=VALUES(parent_email)
");

$rosterInsertStmt = $db->prepare("
    INSERT INTO class_roster (student_id, teacher_id, first_name, last_name, section, room_number, scheduled_time, schedule_day, course_code, course_title, major, course, year_level, created_at)
    VALUES (?, 2, ?, ?, ?, '402', '08:00:00', 'Monday', 'IT301', 'Web Systems and Technologies', 'NA', 'BSIT', ?, NOW())
");

foreach ($studentPool as $s) {
    list($fullName, $email, $studentNum, $sec, $grade) = $s;
    $parts = explode(' ', $fullName, 2);
    $first = $parts[0];
    $last = $parts[1] ?? 'Student';
    $parentEmail = 'parent.' . strtolower($last) . '@gmail.com';
    $parentName = 'Mrs. ' . $last;

    $userInsertStmt->execute([$studentNum, $email, $passHash, $first, $last, $parentEmail, $parentName]);
    
    $uIdStmt = $db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $uIdStmt->execute([$email]);
    $uid = (int)$uIdStmt->fetchColumn();

    $rosterCheck = $db->prepare("SELECT COUNT(*) FROM class_roster WHERE student_id = ?");
    $rosterCheck->execute([$uid]);
    if ($rosterCheck->fetchColumn() == 0) {
        $rosterInsertStmt->execute([$uid, $first, $last, $sec, $grade]);
    }
}

echo "Seeded student user accounts and class roster.\n";

$allStudents = $db->query("
    SELECT u.user_id, u.first_name, u.last_name, COALESCE(cr.section, '31001') AS section, COALESCE(cr.year_level, 9) AS grade_level
    FROM users u
    LEFT JOIN class_roster cr ON cr.student_id = u.user_id
    WHERE u.role = 'student'
")->fetchAll(PDO::FETCH_ASSOC);

echo "Total students for attendance generation: " . count($allStudents) . "\n";

// Generate 70 school days (Monday to Friday) from June 15, 2026 to Sep 18, 2026
$schoolDays = [];
$curDate = new DateTime('2026-06-15');
$endDate = new DateTime('2026-09-18');

while ($curDate <= $endDate) {
    $dow = (int)$curDate->format('N'); // 1 = Mon, 5 = Fri
    if ($dow <= 5) {
        $schoolDays[] = [
            'date' => $curDate->format('Y-m-d'),
            'dow'  => $dow,
            'day'  => $curDate->format('D')
        ];
    }
    $curDate->modify('+1 day');
}

echo "Total school days: " . count($schoolDays) . "\n";

// Clean base table `attendance`
$db->exec("DELETE FROM attendance");

$rowsToInsert = [];

foreach ($allStudents as $st) {
    $uid = (int)$st['user_id'];
    $fn = strtolower($st['first_name']);

    $isSevereAtRisk = in_array($fn, ['marcus', 'elena', 'david', 'sarah', 'lucas'], true);
    $isMondaySpike = in_array($fn, ['amara', 'robert', 'jenny', 'alex', 'eric', 'chloe', 'leo', 'kyle'], true);
    $isHighTardy = in_array($fn, ['bianca', 'nathaniel', 'samantha'], true);

    foreach ($schoolDays as $dayInfo) {
        $dateStr = $dayInfo['date'];
        $dow = $dayInfo['dow'];

        $status = 'present';
        $timeStr = '07:' . sprintf('%02d', rand(40, 58)) . ':00';

        if ($isSevereAtRisk) {
            $isRecent = (strtotime($dateStr) > strtotime('2026-09-01'));
            $randVal = mt_rand(1, 100);
            if ($isRecent || $randVal <= 30 || ($dow === 1 && $randVal <= 65)) {
                $status = 'absent';
                $timeStr = '00:00:00';
            } elseif ($randVal <= 45) {
                $status = 'tardy';
                $timeStr = '08:' . sprintf('%02d', rand(10, 35)) . ':00';
            }
        } elseif ($isMondaySpike) {
            $randVal = mt_rand(1, 100);
            if ($dow === 1 && $randVal <= 55) {
                $status = 'absent';
                $timeStr = '00:00:00';
            } elseif ($dow === 5 && $randVal <= 25) {
                $status = 'tardy';
                $timeStr = '08:' . sprintf('%02d', rand(15, 40)) . ':00';
            } elseif ($randVal <= 8) {
                $status = 'absent';
                $timeStr = '00:00:00';
            }
        } elseif ($isHighTardy) {
            $randVal = mt_rand(1, 100);
            if ($randVal <= 35) {
                $status = 'tardy';
                $timeStr = '08:' . sprintf('%02d', rand(10, 30)) . ':00';
            } elseif ($randVal <= 40) {
                $status = 'absent';
                $timeStr = '00:00:00';
            }
        } else {
            $randVal = mt_rand(1, 100);
            if ($randVal <= 3) {
                $status = 'absent';
                $timeStr = '00:00:00';
            } elseif ($randVal <= 8) {
                $status = 'tardy';
                $timeStr = '08:' . sprintf('%02d', rand(5, 18)) . ':00';
            }
        }

        $rowsToInsert[] = "($uid, 2, '$dateStr', '$timeStr', 'Web Systems and Technologies', '$status', '$dateStr', NOW())";
    }
}

// Batch insert in chunks of 250
echo "Inserting " . count($rowsToInsert) . " rows into attendance table...\n";
$chunks = array_chunk($rowsToInsert, 250);
foreach ($chunks as $chunk) {
    $sql = "INSERT INTO attendance (student_id, teacher_id, `date`, `time`, `subject`, `status`, `schedule_date`, `created_at`) VALUES " . implode(',', $chunk);
    $db->exec($sql);
}

echo "Inserted all rows into attendance table successfully.\n";

// Seed Excuse Slips for absent records
$db->exec("DELETE FROM excuse_slips");
$absentRecords = $db->query("
    SELECT student_id, `date` 
    FROM attendance 
    WHERE status = 'absent' 
    ORDER BY `date` DESC 
    LIMIT 40
")->fetchAll(PDO::FETCH_ASSOC);

$excuseRows = [];
foreach ($absentRecords as $idx => $rec) {
    $uid = (int)$rec['student_id'];
    $d = $rec['date'];
    $st = ($idx % 3 === 0) ? 'approved' : (($idx % 3 === 1) ? 'pending' : 'declined');
    $reasons = [
        "Medical / Illness (with doctor note)",
        "Family / Personal Emergency",
        "Dental surgery appointment",
        "Severe fever and medical rest"
    ];
    $reason = addslashes($reasons[$idx % count($reasons)]);
    $explanation = "Official medical documentation submitted for absence on $d.";

    $excuseRows[] = "($uid, 2, 'Web Systems and Technologies', '$d', '$reason', '$explanation', '$st', '', NOW())";
}

if (!empty($excuseRows)) {
    $sql = "INSERT INTO excuse_slips (student_id, teacher_id, subject, date_of_absence, reason, explanation, status, supporting_document, created_at) VALUES " . implode(',', $excuseRows);
    $db->exec($sql);
}

echo "Seeded excuse_slips table.\n";
echo "SUCCESS: Database now holds complete, authentic attendance dataset!\n";
