<?php
/**
 * Seed Test Data for Attendance History & Audit Logs
 */
require_once __DIR__ . '/../../includes/core/Database.php';

$db = Database::getConnection();

echo "=== ATTENDANCE HISTORY & AUDIT SEEDER ===\n";

// 1. Find all teachers or primary teacher
$teachers = $db->query("SELECT user_id, first_name, last_name, email FROM users WHERE role = 'teacher'")->fetchAll(PDO::FETCH_ASSOC);
if (empty($teachers)) {
    echo "No teachers found in database!\n";
    exit(1);
}

echo "Found " . count($teachers) . " teacher(s):\n";
foreach ($teachers as $t) {
    echo " - ID {$t['user_id']}: {$t['first_name']} {$t['last_name']} ({$t['email']})\n";
}

// 2. We will seed for each teacher (especially teacher #2 if present, or all teachers)
$teacherIds = array_column($teachers, 'user_id');

// 3. Find students enrolled in class_roster
$rosterStudents = $db->query("
    SELECT cr.roster_id, cr.teacher_id, cr.student_id, cr.section, cr.course_code, cr.course_title,
           u.student_id AS student_number, u.first_name, u.last_name
    FROM class_roster cr
    JOIN users u ON cr.student_id = u.user_id
")->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($rosterStudents) . " roster assignments.\n";

if (empty($rosterStudents)) {
    // If class_roster is empty, grab all student users and create some roster links
    $allStudents = $db->query("SELECT user_id, student_id AS student_number, first_name, last_name FROM users WHERE role = 'student'")->fetchAll(PDO::FETCH_ASSOC);
    echo "No roster found. Auto-linking " . count($allStudents) . " students to Teacher {$teacherIds[0]}...\n";
    $insRoster = $db->prepare("INSERT INTO class_roster (teacher_id, student_id, section, course_code, course_title) VALUES (?, ?, ?, ?, ?)");
    foreach ($allStudents as $idx => $st) {
        $sec = ($idx % 3 === 0) ? '31001' : (($idx % 3 === 1) ? '31002' : '31003');
        $insRoster->execute([$teacherIds[0], $st['user_id'], $sec, 'IT301', 'Web Systems and Technologies']);
    }
    // Re-fetch
    $rosterStudents = $db->query("
        SELECT cr.roster_id, cr.teacher_id, cr.student_id, cr.section, cr.course_code, cr.course_title,
               u.student_id AS student_number, u.first_name, u.last_name
        FROM class_roster cr
        JOIN users u ON cr.student_id = u.user_id
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// 4. Generate dates: today, yesterday, past 14 days, and past 30 days
$dates = [];
$today = new DateTime('2026-09-22');
for ($i = 0; $i <= 25; $i++) {
    $d = clone $today;
    $d->modify("-{$i} days");
    // Skip Sundays
    if ($d->format('N') != 7) {
        $dates[] = $d->format('Y-m-d');
    }
}

echo "Generating attendance records across " . count($dates) . " session dates...\n";

// Group roster students by teacher
$rosterByTeacher = [];
foreach ($rosterStudents as $rs) {
    $rosterByTeacher[$rs['teacher_id']][] = $rs;
}

$insAtt = $db->prepare("
    INSERT INTO attendance (student_id, teacher_id, qr_session_id, date, time, subject, status, schedule_date, created_at, updated_at)
    VALUES (:student_id, :teacher_id, :qr_session_id, :date, :time, :subject, :status, :schedule_date, :created_at, :updated_at)
");

// Pre-load existing attendance keys to avoid hundreds of network roundtrips
$existingMap = [];
$existingRows = $db->query("SELECT student_id, teacher_id, date FROM attendance")->fetchAll(PDO::FETCH_ASSOC);
foreach ($existingRows as $er) {
    $existingMap[$er['student_id'] . '_' . $er['teacher_id'] . '_' . $er['date']] = true;
}

$insAudit = $db->prepare("
    INSERT INTO audit_logs (user_id, action, description, reference_type, reference_id, created_at)
    VALUES (:user_id, :action, :description, :reference_type, :reference_id, :created_at)
");

$recordsAdded = 0;
$auditAdded = 0;

$statuses = ['present', 'present', 'present', 'present', 'tardy', 'absent'];
$reasons = [
    'Student presented official clinic pass after medical consultation.',
    'Late arrival due to public transport LRT-1 signal disruption.',
    'Kiosk camera hardware scanner timeout; verified physically in classroom.',
    'Student forgot physical QR ID badge; verified student handbook photo.',
    'Approved athletic training excusal approved by Academic Head.',
    'Emergency family situation documented with excuse letter from guardian.',
    'Laboratory machine malfunction delayed station check-in.'
];

foreach ($rosterByTeacher as $tId => $students) {
    echo "Seeding for Teacher ID #$tId (" . count($students) . " students)...\n";
    $db->beginTransaction();
    
    foreach ($dates as $date) {
        foreach ($students as $st) {
            $studentId = (int)$st['student_id'];
            $key = "{$studentId}_{$tId}_{$date}";
            if (isset($existingMap[$key])) {
                continue; // Already has record for this date
            }
            $existingMap[$key] = true;
            
            // Pick status
            $randStatus = $statuses[array_rand($statuses)];
            $isQr = (mt_rand(1, 10) <= 7); // 70% QR, 30% manual
            $qrSessionId = $isQr ? 101 : null;
            
            // Generate realistic time
            if ($randStatus === 'present') {
                $hour = str_pad(mt_rand(7, 8), 2, '0', STR_PAD_LEFT);
                $min = str_pad(mt_rand(0, 45), 2, '0', STR_PAD_LEFT);
                $sec = str_pad(mt_rand(0, 59), 2, '0', STR_PAD_LEFT);
                $time = "$hour:$min:$sec";
            } elseif ($randStatus === 'tardy') {
                $hour = '08';
                $min = str_pad(mt_rand(46, 59), 2, '0', STR_PAD_LEFT);
                $sec = str_pad(mt_rand(0, 59), 2, '0', STR_PAD_LEFT);
                $time = "$hour:$min:$sec";
            } else {
                // absent
                $time = '00:00:00';
                $qrSessionId = null;
            }
            
            $subject = $st['course_title'] ?: 'Web Systems and Technologies';
            $createdAt = "$date " . ($time ?: '09:00:00');
            
            $insAtt->execute([
                ':student_id' => $studentId,
                ':teacher_id' => $tId,
                ':qr_session_id' => $qrSessionId,
                ':date' => $date,
                ':time' => $time,
                ':subject' => $subject,
                ':status' => $randStatus,
                ':schedule_date' => $date,
                ':created_at' => $createdAt,
                ':updated_at' => $createdAt
            ]);
            $attId = (int)$db->lastInsertId();
            $recordsAdded++;
            
            // Seed a realistic audit log for ~10% of records
            if (mt_rand(1, 10) === 1) {
                $reason = $reasons[array_rand($reasons)];
                $prevStatus = ($randStatus === 'present') ? 'absent' : (($randStatus === 'tardy') ? 'present' : 'tardy');
                $desc = "Manual override by Teacher #{$tId} for student {$st['first_name']} {$st['last_name']} (#{$st['student_number']}): replaced {$prevStatus} with manual entry (new_status: {$randStatus}, time: " . ($time ?: '08:00:00') . "). Reason: {$reason}";
                
                $insAudit->execute([
                    ':user_id' => $tId,
                    ':action' => 'update',
                    ':description' => $desc,
                    ':reference_type' => 'attendance',
                    ':reference_id' => $attId,
                    ':created_at' => $createdAt
                ]);
                $auditAdded++;
            }
        }
    }
    $db->commit();
    echo "  Committed batch for Teacher ID #$tId.\n";
}

// Check total counts after seeding
$totalAtt = (int)$db->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
$totalAudit = (int)$db->query("SELECT COUNT(*) FROM audit_logs WHERE reference_type = 'attendance'")->fetchColumn();

echo "\n=== SEEDING SUMMARY ===\n";
echo "New Attendance Records Added : $recordsAdded\n";
echo "New Audit Log Entries Added  : $auditAdded\n";
echo "Total Attendance in Database : $totalAtt records\n";
echo "Total Attendance Audit Logs  : $totalAudit logs\n";
echo "Status: COMPLETED SUCCESSFULLY!\n";
