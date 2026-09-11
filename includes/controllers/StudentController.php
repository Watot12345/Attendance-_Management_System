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
        $pendingSetup = 0;

        foreach ($students as $st) {
            if ($st['status'] === 'active') {
                $activeStudents++;
            } else {
                $pendingSetup++;
            }
            if (!empty($st['student_id'])) $qrPairedStudents++;
        }

        $page_title = 'Official Student Master Accounts';
        
        include dirname(__DIR__) . '/views/admin/students.php';
    }

    /**
     * POST /admin/students/store
     * Create a new student master account manually
     */
    public function store(): void {
        $studentIdRaw  = trim($_POST['student_id'] ?? '');
        $fullName      = trim($_POST['full_name'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $course        = trim($_POST['course'] ?? 'BSIT');
        $yearLevelRaw  = trim($_POST['year_level'] ?? '1st Year');
        $section       = trim($_POST['section'] ?? '');
        $parentContact = trim($_POST['parent_contact'] ?? '');

        // Validation
        if (empty($studentIdRaw) || empty($fullName) || empty($email) || empty($section)) {
            header('Location: ' . url('admin/students?error=' . urlencode('Please fill in all required fields.')));
            exit;
        }

        // Convert student ID to integer (e.g. "2026-00127" -> 202600127)
        $studentId = (int) preg_replace('/\D/', '', $studentIdRaw);
        if ($studentId <= 0) {
            $studentId = time();
        }

        // Split full name into first_name and last_name
        $nameParts = preg_split('/\s+/', $fullName);
        $lastName  = count($nameParts) > 1 ? array_pop($nameParts) : 'Student';
        $firstName = implode(' ', $nameParts);

        // Extract numeric year level (e.g. "3rd Year" -> 3)
        preg_match('/\d+/', $yearLevelRaw, $matches);
        $yearLevel = isset($matches[0]) ? (int) $matches[0] : 1;

        // Default initial password: BCP@2026
        $hashedPassword = password_hash('BCP@2026', PASSWORD_BCRYPT);

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            // 1. Insert student account into users table
            $userStmt = $db->prepare("
                INSERT INTO users (
                    student_id,
                    role,
                    email,
                    password_hash,
                    first_name,
                    last_name,
                    phone,
                    parent_number,
                    parent_email,
                    status
                ) VALUES (
                    :student_id,
                    'student',
                    :email,
                    :password_hash,
                    :first_name,
                    :last_name,
                    :phone,
                    :parent_number,
                    :parent_email,
                    'active'
                )
            ");

            $isEmail = filter_var($parentContact, FILTER_VALIDATE_EMAIL);
            $userStmt->execute([
                ':student_id'    => $studentId,
                ':email'         => $email,
                ':password_hash' => $hashedPassword,
                ':first_name'    => $firstName,
                ':last_name'     => $lastName,
                ':phone'         => !$isEmail ? $parentContact : null,
                ':parent_number' => !$isEmail ? $parentContact : null,
                ':parent_email'  => $isEmail ? $parentContact : null,
            ]);

            $userId = (int) $db->lastInsertId();

            // 2. Insert class section details into class_roster table
            $rosterStmt = $db->prepare("
                INSERT INTO class_roster (
                    user_id,
                    teacher_id,
                    first_name,
                    last_name,
                    section,
                    scheduled_time,
                    schedule_day,
                    course_code,
                    course_title,
                    course,
                    year_level
                ) VALUES (
                    :user_id,
                    2,
                    :first_name,
                    :last_name,
                    :section,
                    '08:00:00',
                    'Monday',
                    :course_code,
                    :course_title,
                    :course,
                    :year_level
                )
            ");

            $rosterStmt->execute([
                ':user_id'      => $userId,
                ':first_name'   => $firstName,
                ':last_name'    => $lastName,
                ':section'      => $section,
                ':course_code'  => $course,
                ':course_title' => $course . ' Program',
                ':course'       => $course,
                ':year_level'   => $yearLevel,
            ]);

            $db->commit();

            // Redirect back with success message
            header('Location: ' . url('admin/students?created=' . urlencode($fullName)));
            exit;

        } catch (Exception $e) {
            $db->rollBack();
            header('Location: ' . url('admin/students?error=' . urlencode($e->getMessage())));
            exit;
        }
    }
}
