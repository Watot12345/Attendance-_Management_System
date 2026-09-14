<?php
/**
 * Perfect Attendance Award Tool — Sample Data Seeder
 * Populates realistic September 2026 attendance sessions and excuse slips
 * for Teacher 2 (Section 31001, Web Systems and Technologies).
 */

require_once dirname(__DIR__) . '/includes/core/Database.php';

function seedAwardsSampleData(PDO $db, int $teacherId = 2): array {
    $subject = 'Web Systems and Technologies';
    $dates = [
        '2026-09-01',
        '2026-09-03',
        '2026-09-05',
        '2026-09-08',
        '2026-09-10',
        '2026-09-11',
        '2026-09-15',
        '2026-09-17',
        '2026-09-19',
        '2026-09-22',
    ];

    // Clean existing attendance in this date range for this teacher and subject
    $delStmt = $db->prepare("DELETE FROM attendance WHERE teacher_id = ? AND subject = ? AND date BETWEEN '2026-09-01' AND '2026-09-25'");
    $delStmt->execute([$teacherId, $subject]);

    $insAtt = $db->prepare("
        INSERT INTO attendance (student_id, teacher_id, qr_session_id, date, time, subject, status, schedule_date, created_at, updated_at)
        VALUES (:student_id, :teacher_id, :qr_session_id, :date, :time, :subject, :status, :schedule_date, NOW(), NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), time = VALUES(time), qr_session_id = VALUES(qr_session_id), updated_at = NOW()
    ");

    foreach ($dates as $date) {
        // 1. Pedro Reyes (Student 6): 100% Present (Flawless 10/10, QR on 2026-09-11)
        $qrSessionId = ($date === '2026-09-11') ? 14 : null;
        $insAtt->execute([
            ':student_id'    => 6,
            ':teacher_id'    => $teacherId,
            ':qr_session_id' => $qrSessionId,
            ':date'          => $date,
            ':time'          => '07:54:00',
            ':subject'       => $subject,
            ':status'        => 'present',
            ':schedule_date' => $date
        ]);

        // 2. Maria Santos (Student 4): 100% Present (Flawless 10/10)
        $insAtt->execute([
            ':student_id'    => 4,
            ':teacher_id'    => $teacherId,
            ':qr_session_id' => null,
            ':date'          => $date,
            ':time'          => '07:58:00',
            ':subject'       => $subject,
            ':status'        => 'present',
            ':schedule_date' => $date
        ]);

        // 3. Ana Gonzales (Student 7): 9 Present, 1 Approved Excused Absence on 2026-09-15
        $anaStatus = ($date === '2026-09-15') ? 'absent' : 'present';
        $anaTime   = ($date === '2026-09-15') ? '00:00:00' : '07:50:00';
        $insAtt->execute([
            ':student_id'    => 7,
            ':teacher_id'    => $teacherId,
            ':qr_session_id' => null,
            ':date'          => $date,
            ':time'          => $anaTime,
            ':subject'       => $subject,
            ':status'        => $anaStatus,
            ':schedule_date' => $date
        ]);

        // 4. Gabriel Fernandez (Student 5): 9 Present, 1 Tardy on 2026-09-10
        $gabStatus = ($date === '2026-09-10') ? 'tardy' : 'present';
        $gabTime   = ($date === '2026-09-10') ? '08:24:00' : '07:48:00';
        $insAtt->execute([
            ':student_id'    => 5,
            ':teacher_id'    => $teacherId,
            ':qr_session_id' => null,
            ':date'          => $date,
            ':time'          => $gabTime,
            ':subject'       => $subject,
            ':status'        => $gabStatus,
            ':schedule_date' => $date
        ]);

        // 5. Juan Dela Cruz (Student 1): 7 Present, 2 Absent, 1 Tardy
        if ($date === '2026-09-08' || $date === '2026-09-17') {
            $juanStatus = 'absent';
            $juanTime = '00:00:00';
        } elseif ($date === '2026-09-22') {
            $juanStatus = 'tardy';
            $juanTime = '08:35:00';
        } else {
            $juanStatus = 'present';
            $juanTime = '08:05:00';
        }
        $insAtt->execute([
            ':student_id'    => 1,
            ':teacher_id'    => $teacherId,
            ':qr_session_id' => null,
            ':date'          => $date,
            ':time'          => $juanTime,
            ':subject'       => $subject,
            ':status'        => $juanStatus,
            ':schedule_date' => $date
        ]);
    }

    // Seed approved excuse slip for Ana Gonzales on 2026-09-15
    $db->prepare("DELETE FROM excuse_slips WHERE student_id = 7 AND date_of_absence = '2026-09-15'")->execute();
    $excStmt = $db->prepare("
        INSERT INTO excuse_slips (
            student_id, teacher_id, subject, date_of_absence, reason, explanation, status, created_at, updated_at
        ) VALUES (
            7, :teacher_id, :subject, '2026-09-15',
            'Medical emergency: Acute gastroenteritis',
            'Hospital admission certificate and physician clearance attached by Dr. R. Hernandez, MD.',
            'approved', NOW(), NOW()
        )
    ");
    $excStmt->execute([
        ':teacher_id' => $teacherId,
        ':subject'    => $subject
    ]);

    return [
        'status'         => 'success',
        'sessions_held'  => count($dates),
        'start_date'     => '2026-09-01',
        'end_date'       => '2026-09-30',
        'section'        => '31001',
        'subject'        => $subject,
        'students_count' => 5
    ];
}

// CLI execution
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    try {
        $db = Database::getConnection();
        echo "Connected to MySQL successfully.\n";
        $res = seedAwardsSampleData($db, 2);
        echo "Sample data successfully seeded:\n";
        echo "  - {$res['sessions_held']} distinct session dates held in September 2026\n";
        echo "  - Student 6 (Pedro Reyes): 10/10 present (100% Flawless)\n";
        echo "  - Student 4 (Maria Santos): 10/10 present (100% Flawless)\n";
        echo "  - Student 7 (Ana Gonzales): 9 present + 1 approved medical excused absence (98%+ Honors)\n";
        echo "  - Student 5 (Gabriel Fernandez): 9 present + 1 tardy\n";
        echo "  - Student 1 (Juan Dela Cruz): 7 present + 2 absent + 1 tardy\n";
    } catch (Exception $e) {
        echo "Error seeding sample data: " . $e->getMessage() . "\n";
        exit(1);
    }
}
