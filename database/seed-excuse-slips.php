<?php
/**
 * Database Excuse Slips Seeder — database/seed-excuse-slips.php
 * Populates realistic student excuse slips in `excuse_slips` and creates any missing student records in `users`.
 */

require_once dirname(__DIR__) . '/includes/core/Database.php';

try {
    $db = Database::getConnection();
    echo "Connected to MySQL successfully.\n";

    // 1. Seed Students into `users` table
    $students = [
        [
            'id' => 1,
            'student_id' => 202600123,
            'first' => 'Juan',
            'last' => 'Dela Cruz',
            'email' => 'juan.delacruz@bcp.edu.ph',
            'phone' => '09123456781'
        ],
        [
            'id' => 4,
            'student_id' => 202600124,
            'first' => 'Maria',
            'last' => 'Santos',
            'email' => 'maria.santos@bcp.edu.ph',
            'phone' => '09123456782'
        ],
        [
            'id' => 5,
            'student_id' => 202600129,
            'first' => 'Gabriel',
            'last' => 'Fernandez',
            'email' => 'gabriel.fernandez@bcp.edu.ph',
            'phone' => '09123456783'
        ],
        [
            'id' => 6,
            'student_id' => 202600125,
            'first' => 'Pedro',
            'last' => 'Reyes',
            'email' => 'pedro.reyes@bcp.edu.ph',
            'phone' => '09123456784'
        ],
        [
            'id' => 7,
            'student_id' => 202600128,
            'first' => 'Ana',
            'last' => 'Gonzales',
            'email' => 'ana.gonzales@bcp.edu.ph',
            'phone' => '09123456785'
        ],
    ];

    $hash = password_hash('student123', PASSWORD_BCRYPT);
    foreach ($students as $s) {
        $stmt = $db->prepare("
            INSERT INTO users (user_id, student_id, role, email, password_hash, first_name, last_name, phone, status, created_at)
            VALUES (?, ?, 'student', ?, ?, ?, ?, ?, 'active', NOW())
            ON DUPLICATE KEY UPDATE 
                student_id = VALUES(student_id),
                first_name = VALUES(first_name), 
                last_name = VALUES(last_name),
                email = VALUES(email)
        ");
        $stmt->execute([$s['id'], $s['student_id'], $s['email'], $hash, $s['first'], $s['last'], $s['phone']]);
        echo " Ensured student: {$s['first']} {$s['last']} (user_id: {$s['id']})\n";
    }

    // 2. Check if excuse slips already exist
    $count = $db->query("SELECT count(*) FROM excuse_slips")->fetchColumn();
    if ($count == 0) {
        $slips = [
            [
                'student_id' => 1,
                'teacher_id' => 2,
                'subject' => 'IT301 — Web Development 2 (Prof. Ramirez)',
                'date_of_absence' => date('Y-m-d'),
                'reason' => "Medical / Illness (with doctor's note)",
                'explanation' => 'High fever and viral flu. Medical certificate from QC General Hospital attached.',
                'status' => 'pending',
                'declined_reason' => null,
                'supporting_document' => 'https://hcpbtbxudiyeznuvimsz.supabase.co/storage/v1/object/public/documents/excuses/medical_cert_delacruz.png',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                'student_id' => 4,
                'teacher_id' => 2,
                'subject' => 'IT302 — Database Systems 2 (Prof. Ramirez)',
                'date_of_absence' => date('Y-m-d', strtotime('+1 day')),
                'reason' => "Medical / Illness (with doctor's note)",
                'explanation' => 'Emergency tooth extraction and dental surgery. Medical clearance from clinic attached.',
                'status' => 'pending',
                'declined_reason' => null,
                'supporting_document' => 'https://hcpbtbxudiyeznuvimsz.supabase.co/storage/v1/object/public/documents/excuses/dental_clearance_santos.png',
                'created_at' => date('Y-m-d H:i:s', strtotime('-5 hours'))
            ],
            [
                'student_id' => 5,
                'teacher_id' => 3,
                'subject' => 'IT303 — Systems Integration (Prof. Santos)',
                'date_of_absence' => date('Y-m-d'),
                'reason' => 'Family / Personal Emergency',
                'explanation' => 'Urgent family emergency in province. Signed endorsement letter from parent provided.',
                'status' => 'pending',
                'declined_reason' => null,
                'supporting_document' => 'https://hcpbtbxudiyeznuvimsz.supabase.co/storage/v1/object/public/documents/excuses/parent_letter_fernandez.png',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'student_id' => 6,
                'teacher_id' => 2,
                'subject' => 'IT301 — Web Development 2 (Prof. Ramirez)',
                'date_of_absence' => date('Y-m-d', strtotime('-3 days')),
                'reason' => "Medical / Illness (with doctor's note)",
                'explanation' => 'Hospitalized due to dengue fever. Medical certificate and official discharge slip verified.',
                'status' => 'approved',
                'declined_reason' => null,
                'supporting_document' => 'https://hcpbtbxudiyeznuvimsz.supabase.co/storage/v1/object/public/documents/excuses/dengue_discharge_reyes.png',
                'created_at' => date('Y-m-d H:i:s', strtotime('-4 days'))
            ],
            [
                'student_id' => 7,
                'teacher_id' => 2,
                'subject' => 'IT302 — Database Systems 2 (Prof. Ramirez)',
                'date_of_absence' => date('Y-m-d', strtotime('-5 days')),
                'reason' => 'Other Valid Reason',
                'explanation' => 'Missed hands-on laboratory assessment due to personal schedule conflict.',
                'status' => 'declined',
                'declined_reason' => 'Unexcused: Lack of medical proof or advance official department authorization.',
                'supporting_document' => null,
                'created_at' => date('Y-m-d H:i:s', strtotime('-6 days'))
            ],
        ];

        $ins = $db->prepare("
            INSERT INTO excuse_slips (
                student_id, teacher_id, subject, date_of_absence, reason, explanation, 
                status, declined_reason, supporting_document, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($slips as $slip) {
            $ins->execute([
                $slip['student_id'],
                $slip['teacher_id'],
                $slip['subject'],
                $slip['date_of_absence'],
                $slip['reason'],
                $slip['explanation'],
                $slip['status'],
                $slip['declined_reason'],
                $slip['supporting_document'],
                $slip['created_at'],
                $slip['created_at']
            ]);
            echo " Seeded excuse slip for student_id: {$slip['student_id']} ({$slip['status']})\n";
        }
    } else {
        echo "ℹ excuse_slips table already has {$count} records.\n";
    }

    echo "\nSeeding completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
