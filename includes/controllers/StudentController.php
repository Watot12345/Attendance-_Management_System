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
                COALESCE(r.year_level, 3) AS year_level,
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

    /**
     * GET /admin/students/template
     * Download sample CSV spreadsheet template
     */
    public function downloadTemplate(): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="student_master_template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['student_id', 'full_name', 'email', 'course', 'year_level', 'section', 'parent_contact'], ',', '"', "\\");
        fputcsv($output, ['2026-00150', 'Jerome A. Valdez', 'jerome.valdez@bestlink.edu.ph', 'BSIT', '3rd Year', '3-A', '09123456789'], ',', '"', "\\");
        fputcsv($output, ['2026-00151', 'Alyssa Jane Mercado', 'alyssa.mercado@bestlink.edu.ph', 'BSIT', '3rd Year', '3-A', 'alyssa.parent@gmail.com'], ',', '"', "\\");
        fputcsv($output, ['2026-00152', 'Gabriel Kyle Soriano', 'gabriel.soriano@bestlink.edu.ph', 'BSIS', '2nd Year', '2-B', '09987654321'], ',', '"', "\\");
        fclose($output);
        exit;
    }

    /**
     * POST /admin/students/import
     * Batch import student master accounts from an uploaded CSV file
     */
    public function import(): void {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            header('Location: ' . url('admin/students?error=' . urlencode('Please select a valid CSV file to upload.')));
            exit;
        }

        $fileTmp = $_FILES['csv_file']['tmp_name'];
        $fileName = $_FILES['csv_file']['name'];
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'txt'])) {
            header('Location: ' . url('admin/students?error=' . urlencode('Only .csv format is supported for batch import.')));
            exit;
        }

        $handle = fopen($fileTmp, 'r');
        if ($handle === false) {
            header('Location: ' . url('admin/students?error=' . urlencode('Unable to read the uploaded CSV file.')));
            exit;
        }

        // Read header row
        $rawHeaders = fgetcsv($handle, 0, ',', '"', "\\");
        if ($rawHeaders === false || empty($rawHeaders)) {
            fclose($handle);
            header('Location: ' . url('admin/students?error=' . urlencode('The uploaded CSV file is empty.')));
            exit;
        }

        // Normalize header columns
        $headerMap = [];
        foreach ($rawHeaders as $idx => $h) {
            $cleaned = strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '', str_replace(' ', '_', $h))));
            $headerMap[$cleaned] = $idx;
        }

        // Helper function to resolve column index by multiple possible alias names
        $findCol = function(array $aliases) use ($headerMap) {
            foreach ($aliases as $alias) {
                if (isset($headerMap[$alias])) {
                    return $headerMap[$alias];
                }
            }
            return null;
        };

        $colId      = $findCol(['student_id', 'student_number', 'id', 'student_no']);
        $colName    = $findCol(['full_name', 'student_name', 'name']);
        $colFirst   = $findCol(['first_name', 'firstname']);
        $colLast    = $findCol(['last_name', 'lastname']);
        $colEmail   = $findCol(['email', 'student_email', 'email_address']);
        $colCourse  = $findCol(['course', 'program', 'course_code']);
        $colYear    = $findCol(['year_level', 'year', 'grade_level']);
        $colSection = $findCol(['section', 'class_section']);
        $colContact = $findCol(['parent_contact', 'parent_number', 'phone', 'contact_number', 'parent_email']);

        $db = Database::getConnection();

        try {
            // Pre-fetch existing student_ids and emails to skip duplicates
            $existingStmt = $db->query("SELECT student_id, email FROM users WHERE role = 'student'");
            $existingRows = $existingStmt->fetchAll(PDO::FETCH_ASSOC);

            $existingIds = [];
            $existingEmails = [];
            foreach ($existingRows as $er) {
                if (!empty($er['student_id'])) {
                    $existingIds[(int)$er['student_id']] = true;
                }
                if (!empty($er['email'])) {
                    $existingEmails[strtolower(trim($er['email']))] = true;
                }
            }

            $userInsert = $db->prepare("
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

            $rosterInsert = $db->prepare("
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

            $hashedPassword = password_hash('BCP@2026', PASSWORD_BCRYPT);
            $importedCount = 0;
            $skippedCount = 0;

            $db->beginTransaction();

            while (($row = fgetcsv($handle, 0, ',', '"', "\\")) !== false) {
                // Skip empty lines
                if (empty(array_filter($row, fn($val) => trim($val) !== ''))) {
                    continue;
                }

                // Resolve student ID
                $rawId = $colId !== null ? trim($row[$colId] ?? '') : '';
                $studentId = (int) preg_replace('/\D/', '', $rawId);
                if ($studentId <= 0) {
                    $studentId = time() + $importedCount;
                }

                // Resolve name
                if ($colFirst !== null && $colLast !== null) {
                    $firstName = trim($row[$colFirst] ?? '');
                    $lastName  = trim($row[$colLast] ?? 'Student');
                    $fullName  = $firstName . ' ' . $lastName;
                } elseif ($colName !== null) {
                    $fullName = trim($row[$colName] ?? '');
                    $parts = preg_split('/\s+/', $fullName);
                    $lastName = count($parts) > 1 ? array_pop($parts) : 'Student';
                    $firstName = implode(' ', $parts);
                } else {
                    $firstName = 'Student';
                    $lastName = (string)$studentId;
                    $fullName = $firstName . ' ' . $lastName;
                }

                if (empty($firstName)) {
                    $firstName = 'Student';
                }

                // Resolve email
                $email = $colEmail !== null ? strtolower(trim($row[$colEmail] ?? '')) : '';
                if (empty($email)) {
                    $cleanF = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($firstName));
                    $cleanL = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($lastName));
                    $email = "{$cleanF}.{$cleanL}@bestlink.edu.ph";
                }

                // Check for duplicates
                if (isset($existingIds[$studentId]) || isset($existingEmails[$email])) {
                    $skippedCount++;
                    continue;
                }

                // Resolve course & section
                $course = $colCourse !== null ? trim($row[$colCourse] ?? 'BSIT') : 'BSIT';
                if (empty($course)) $course = 'BSIT';

                $yearRaw = $colYear !== null ? trim($row[$colYear] ?? '3') : '3';
                preg_match('/\d+/', $yearRaw, $mYear);
                $yearLevel = isset($mYear[0]) ? (int)$mYear[0] : 3;

                $section = $colSection !== null ? trim($row[$colSection] ?? '3-A') : '3-A';
                if (empty($section)) $section = '3-A';

                // Resolve contact info
                $contact = $colContact !== null ? trim($row[$colContact] ?? '') : '';
                $isEmailContact = filter_var($contact, FILTER_VALIDATE_EMAIL);

                // Insert into users
                $userInsert->execute([
                    ':student_id'    => $studentId,
                    ':email'         => $email,
                    ':password_hash' => $hashedPassword,
                    ':first_name'    => $firstName,
                    ':last_name'     => $lastName,
                    ':phone'         => !$isEmailContact ? $contact : null,
                    ':parent_number' => !$isEmailContact ? $contact : null,
                    ':parent_email'  => $isEmailContact ? $contact : null,
                ]);

                $userId = (int) $db->lastInsertId();

                // Insert into class_roster
                $rosterInsert->execute([
                    ':user_id'      => $userId,
                    ':first_name'   => $firstName,
                    ':last_name'    => $lastName,
                    ':section'      => $section,
                    ':course_code'  => $course,
                    ':course_title' => $course . ' Program',
                    ':course'       => $course,
                    ':year_level'   => $yearLevel,
                ]);

                $existingIds[$studentId] = true;
                $existingEmails[$email] = true;
                $importedCount++;
            }

            fclose($handle);
            $db->commit();

            header('Location: ' . url('admin/students?imported=' . $importedCount . '&skipped=' . $skippedCount));
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if (is_resource($handle)) {
                fclose($handle);
            }
            header('Location: ' . url('admin/students?error=' . urlencode('Import failed: ' . $e->getMessage())));
            exit;
        }
    }
}

