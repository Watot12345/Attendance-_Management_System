<?php
/**
 * Student Master Accounts Controller
 * includes/controllers/StudentController.php
 */

require_once dirname(__DIR__) . '/core/Database.php';

class StudentController {
    /**
     * GET /admin/students
     * Fetch all students from database and render the master directory
     */
    public function index(): void {
        $db = Database::getConnection();

        // Query students from users table with role = 'student' and roster details
        $stmt = $db->query("
            SELECT 
                u.user_id,
                u.student_id,
                COALESCE(u.student_id, u.user_id) AS student_code,
                u.first_name,
                u.last_name,
                u.email,
                u.phone,
                u.status,
                COALESCE(r.course, 'BSIT') AS course,
                CONCAT(COALESCE(r.year_level, 3), ' Year') AS grade_level,
                COALESCE(r.section, '3-A') AS section,
                u.student_id AS qr_code
            FROM users u
            LEFT JOIN class_roster r ON u.user_id = r.user_id
            WHERE u.role = 'student'
            ORDER BY u.user_id DESC
        ");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute metrics for stat cards
        $totalStudents = count($students);
        $activeStudents = 0;
        $qrPairedStudents = 0;

        foreach ($students as $st) {
            if ($st['status'] === 'active') $activeStudents++;
            if (!empty($st['student_id'])) $qrPairedStudents++;
        }

        $page_title = 'Official Student Master Accounts';
        
        include dirname(__DIR__) . '/views/admin/students.php';
    }
}
