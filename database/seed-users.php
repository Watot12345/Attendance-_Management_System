<?php
/**
 * Database Users Seeder — database/seed-users.php
 * Seeds foundational student, teacher, and admin records in `users` table
 * to satisfy foreign keys for attendance, class_roster, and excuse_slips.
 */

require_once dirname(__DIR__) . '/includes/core/Database.php';

try {
    $db = Database::getConnection();
    echo "Connected to MySQL successfully.\n";

    // 1. Seed Student: Juan Dela Cruz
    $checkStudent = $db->query("SELECT user_id FROM users WHERE role = 'student' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$checkStudent) {
        $stmt = $db->prepare("
            INSERT INTO users (user_id, student_id, role, email, password_hash, first_name, last_name, phone, status, created_at)
            VALUES (1, 202600123, 'student', 'juan.delacruz@bcp.edu.ph', ?, 'Juan', 'Dela Cruz', '09123456789', 'active', NOW())
            ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name)
        ");
        $stmt->execute([password_hash('student123', PASSWORD_BCRYPT)]);
        echo " Seeded student: Juan Dela Cruz (user_id: 1)\n";
    } else {
        echo "ℹ Student already exists with user_id: {$checkStudent['user_id']}\n";
    }

    // 2. Seed Teachers: Prof. Ramirez & Prof. Santos
    $teachers = [
        [
            'id' => 2,
            'emp_id' => 'EMP-1001',
            'first' => 'Manuel',
            'last' => 'Ramirez',
            'email' => 'm.ramirez@bcp.edu.ph',
            'dept' => 'College of Computer Studies'
        ],
        [
            'id' => 3,
            'emp_id' => 'EMP-1002',
            'first' => 'Jose',
            'last' => 'Santos',
            'email' => 'j.santos@bcp.edu.ph',
            'dept' => 'College of Computer Studies'
        ],
    ];

    foreach ($teachers as $t) {
        $check = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $check->execute([$t['email']]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if (!$existing) {
            $stmt = $db->prepare("
                INSERT INTO users (user_id, employee_id, role, email, password_hash, first_name, last_name, status, created_at)
                VALUES (?, ?, 'teacher', ?, ?, ?, ?, 'active', NOW())
                ON DUPLICATE KEY UPDATE first_name = VALUES(first_name), last_name = VALUES(last_name)
            ");
            $stmt->execute([
                $t['id'],
                $t['emp_id'],
                $t['email'],
                password_hash('teacher123', PASSWORD_BCRYPT),
                $t['first'],
                $t['last']
            ]);
            echo " Seeded teacher: Prof. {$t['first']} {$t['last']} (user_id: {$t['id']})\n";
        } else {
            echo "ℹ Teacher already exists: Prof. {$t['first']} {$t['last']} (user_id: {$existing['user_id']})\n";
        }
    }

    echo "\nAll essential users are seeded and ready for Excuse Slip submissions.\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
