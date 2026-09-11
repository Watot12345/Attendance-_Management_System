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
            LEFT JOIN class_roster r ON u.user_id = r.student_id
            WHERE u.role = 'student'
            ORDER BY u.user_id DESC
        ");
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute metrics for stat cards
        $totalStudents = count($students);
        $activeStudents = 0;
        $qrPairedStudents = 0;
        $pendingSetup = 0;
        $maxStudentSeq = 0;
        $seqLength = 4;

        foreach ($students as $st) {
            if ($st['status'] === 'active') {
                $activeStudents++;
            } else {
                $pendingSetup++;
            }
            if (!empty($st['student_id'])) $qrPairedStudents++;

            $sid = trim((string)($st['student_id'] ?? ''));
            if (preg_match('/^23011(\d+)$/', $sid, $m)) {
                $seqVal = (int)$m[1];
                if ($seqVal > $maxStudentSeq) {
                    $maxStudentSeq = $seqVal;
                    $seqLength = max($seqLength, strlen($m[1]));
                }
            }
        }

        $nextSeq = $maxStudentSeq > 0 ? $maxStudentSeq + 1 : 1;
        $nextStudentId = '23011' . str_pad((string)$nextSeq, $seqLength, '0', STR_PAD_LEFT);

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

        // Check for duplicate student_id
        $dupStmt = $db->prepare("SELECT user_id, first_name, last_name FROM users WHERE student_id = :student_id LIMIT 1");
        $dupStmt->execute([':student_id' => $studentId]);
        $existingStudent = $dupStmt->fetch(PDO::FETCH_ASSOC);
        if ($existingStudent) {
            $ownerName = trim($existingStudent['first_name'] . ' ' . $existingStudent['last_name']);
            header('Location: ' . url('admin/students?error=' . urlencode("Student number {$studentId} is already assigned to {$ownerName}.")));
            exit;
        }

        // Check for duplicate email
        $dupEmailStmt = $db->prepare("SELECT user_id FROM users WHERE email = :email LIMIT 1");
        $dupEmailStmt->execute([':email' => $email]);
        if ($dupEmailStmt->fetch()) {
            header('Location: ' . url('admin/students?error=' . urlencode("Email address {$email} is already registered.")));
            exit;
        }

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
                    student_id,
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
                    :student_id,
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
                ':student_id'   => $userId,
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
        fputcsv($output, ['230110150', 'Jerome A. Valdez', 'jerome.valdez@bestlink.edu.ph', 'BSIT', '3rd Year', '3-A', '09123456789'], ',', '"', "\\");
        fputcsv($output, ['230110151', 'Alyssa Jane Mercado', 'alyssa.mercado@bestlink.edu.ph', 'BSIT', '3rd Year', '3-A', 'alyssa.parent@gmail.com'], ',', '"', "\\");
        fputcsv($output, ['230110152', 'Gabriel Kyle Soriano', 'gabriel.soriano@bestlink.edu.ph', 'BSIS', '2nd Year', '2-B', '09987654321'], ',', '"', "\\");
        fclose($output);
        exit;
    }

    /**
     * GET /teacher/roster/template
     * Download sample CSV spreadsheet template for class roster
     * Contains student fields: student_id, first_name, middle_initial, last_name, extension (optional)
     * as class, course, room, and schedule are captured via Target Class & Section Details form inputs.
     */
    public function downloadRosterTemplate(): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="class_roster_template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // Student identification columns with middle initial and optional name extension
        fputcsv($output, ['student_id', 'first_name', 'middle_initial', 'last_name', 'extension'], ',', '"', "\\");

        // Sample data rows matching verified students
        fputcsv($output, ['2026-00123', 'Juan', 'A.', 'Dela Cruz', 'Jr.'], ',', '"', "\\");
        fputcsv($output, ['2026-00124', 'Maria', 'C.', 'Santos', ''], ',', '"', "\\");
        fputcsv($output, ['2026-00125', 'Pedro', 'M.', 'Reyes', 'III'], ',', '"', "\\");
        fputcsv($output, ['2026-00126', 'Ana', 'B.', 'Mendoza', ''], ',', '"', "\\");

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
        $colMiddle  = $findCol(['middle_initial', 'middle_name', 'mi']);
        $colLast    = $findCol(['last_name', 'lastname']);
        $colExt     = $findCol(['extension', 'suffix', 'name_extension', 'ext']);
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
                    student_id,
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
                    :student_id,
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
                    $middleInitial = $colMiddle !== null ? trim($row[$colMiddle] ?? '') : '';
                    $ext = $colExt !== null ? trim($row[$colExt] ?? '') : '';

                    if ($middleInitial !== '') {
                        $mFormatted = str_ends_with($middleInitial, '.') ? $middleInitial : $middleInitial . '.';
                        $firstName = "{$firstName} {$mFormatted}";
                    }
                    if ($ext !== '') {
                        $lastName = "{$lastName} {$ext}";
                    }
                    $fullName  = trim("{$firstName} {$lastName}");
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
                    ':student_id'   => $userId,
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

    /**
     * POST /api/teacher/roster/validate
     * Validates an uploaded student list against users and class_roster for duplicates
     */
    public function validateRoster(): void {
        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $input = !empty($raw) ? json_decode($raw, true) : $_POST;

        if (empty($input) || !is_array($input)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
            exit;
        }

        $courseCode = strtoupper(trim($input['course_code'] ?? 'IT301'));
        $section = trim($input['section'] ?? '1');
        $teacherId = !empty($_SESSION['user']['user_id']) ? (int)$_SESSION['user']['user_id'] : (!empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (!empty($input['teacher_id']) ? (int)$input['teacher_id'] : 2));
        $students = $input['students'] ?? [];

        if (!is_array($students)) {
            echo json_encode(['success' => false, 'message' => 'No student data provided']);
            exit;
        }

        try {
            $db = Database::getConnection();

            // Pre-fetch all students in users table
            $userStmt = $db->query("SELECT user_id, student_id, first_name, last_name FROM users WHERE role = 'student'");
            $userRows = $userStmt->fetchAll(PDO::FETCH_ASSOC);

            $usersByCleanId = [];
            foreach ($userRows as $u) {
                if (!empty($u['student_id'])) {
                    $clean = (string)preg_replace('/\D/', '', (string)$u['student_id']);
                    $usersByCleanId[$clean] = $u;
                }
            }

            // Pre-fetch existing class_roster enrollments for this teacher + course_code + section
            $rosterCheckStmt = $db->prepare("
                SELECT student_id FROM class_roster
                WHERE teacher_id = :teacher_id AND course_code = :course_code AND section = :section
            ");
            $rosterCheckStmt->execute([
                ':teacher_id'   => $teacherId,
                ':course_code'  => $courseCode,
                ':section'      => $section,
            ]);
            $enrolledUserIds = [];
            foreach ($rosterCheckStmt->fetchAll(PDO::FETCH_COLUMN) as $uid) {
                $enrolledUserIds[(int)$uid] = true;
            }

            $validatedRows = [];
            $validCount = 0;
            $duplicateCount = 0;
            $unregisteredCount = 0;

            foreach ($students as $row) {
                $rawId = trim($row['student_id'] ?? $row['student_number'] ?? '');
                $cleanId = (string)preg_replace('/\D/', '', $rawId);
                $fn = trim($row['first_name'] ?? '');
                $mi = trim($row['middle_initial'] ?? $row['middle_name'] ?? $row['mi'] ?? '');
                $ln = trim($row['last_name'] ?? '');
                $ext = trim($row['extension'] ?? $row['suffix'] ?? $row['name_extension'] ?? '');

                $miStr = $mi !== '' ? (str_ends_with($mi, '.') ? $mi : $mi . '.') : '';
                $nameParts = array_filter([$fn, $miStr, $ln, $ext], fn($p) => $p !== '');
                $excelName = !empty($nameParts) ? implode(' ', $nameParts) : trim($row['full_name'] ?? $row['name'] ?? 'Student');

                $matchedUser = $usersByCleanId[$cleanId] ?? null;
                $isMasterMatch = $matchedUser !== null;
                $userId = $matchedUser ? (int)$matchedUser['user_id'] : null;

                $isDuplicate = false;
                $statusType = 'valid';

                if (!$isMasterMatch) {
                    $statusType = 'unregistered';
                    $unregisteredCount++;
                } elseif ($userId !== null && isset($enrolledUserIds[$userId])) {
                    $isDuplicate = true;
                    $statusType = 'duplicate';
                    $duplicateCount++;
                } else {
                    $statusType = 'valid';
                    $validCount++;
                }

                $validatedRows[] = [
                    'student_id'      => !empty($rawId) ? $rawId : $cleanId,
                    'clean_id'        => $cleanId,
                    'excel_name'      => $excelName,
                    'first_name'      => $fn,
                    'middle_initial'  => $mi,
                    'last_name'       => $ln,
                    'extension'       => $ext,
                    'is_master_match' => $isMasterMatch,
                    'master_name'     => $matchedUser ? "{$matchedUser['first_name']} {$matchedUser['last_name']}" : 'Not found in Student Master',
                    'is_duplicate'    => $isDuplicate,
                    'status_type'     => $statusType,
                    'user_id'         => $userId,
                ];
            }

            echo json_encode([
                'success'           => true,
                'status'            => 'success',
                'teacher_id'        => $teacherId,
                'course_code'       => $courseCode,
                'section'           => $section,
                'students'          => $validatedRows,
                'total'             => count($students),
                'valid_count'       => $validCount,
                'duplicate_count'   => $duplicateCount,
                'unregistered_count'=> $unregisteredCount,
                'all_duplicate'     => (count($students) > 0 && $duplicateCount === count($students)),
                'all_unregistered'  => (count($students) > 0 && $unregisteredCount === count($students)),
            ]);
            exit;

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Validation error: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * POST /api/teacher/roster/import
     * Enrolls students into class_roster table in Strict Master Mode (skips unregistered, skips duplicates)
     */
    public function importClassRoster(): void {
        header('Content-Type: application/json; charset=utf-8');

        $raw = file_get_contents('php://input');
        $input = !empty($raw) ? json_decode($raw, true) : $_POST;

        if (empty($input) || !is_array($input)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request payload']);
            exit;
        }

        $course      = trim($input['course'] ?? 'BSIT');
        $yearLevel   = (int)preg_replace('/\D/', '', (string)($input['year_level'] ?? '3')) ?: 3;
        $section     = trim($input['section'] ?? '1');
        $major       = trim($input['major'] ?? '');
        $courseCode  = strtoupper(trim($input['course_code'] ?? 'IT301'));
        $courseTitle = trim($input['course_title'] ?? 'Web Systems and Technologies');
        $scheduleDay = trim($input['schedule_day'] ?? 'Monday');
        $scheduledTime = trim($input['scheduled_time'] ?? '08:00:00');
        if (strlen($scheduledTime) === 5) {
            $scheduledTime .= ':00';
        }
        $roomNumber  = trim($input['room_number'] ?? $input['room_num'] ?? '402');
        $teacherId   = !empty($_SESSION['user']['user_id']) ? (int)$_SESSION['user']['user_id'] : (!empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : (!empty($input['teacher_id']) ? (int)$input['teacher_id'] : 2));
        $students    = $input['students'] ?? [];

        if (empty($students) || !is_array($students)) {
            echo json_encode(['success' => false, 'message' => 'No student rows found to import.']);
            exit;
        }

        $db = Database::getConnection();

        try {
            $db->beginTransaction();

            // Pre-load existing official students from users table
            $userStmt = $db->query("SELECT user_id, student_id, first_name, last_name FROM users WHERE role = 'student'");
            $existingUsers = [];
            foreach ($userStmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
                if (!empty($u['student_id'])) {
                    $clean = (string)preg_replace('/\D/', '', (string)$u['student_id']);
                    $existingUsers[$clean] = $u;
                }
            }

            // Prepare statements
            $checkRosterStmt = $db->prepare("
                SELECT roster_id FROM class_roster
                WHERE teacher_id = :teacher_id AND course_code = :course_code AND section = :section AND student_id = :student_id
            ");

            $insertRosterStmt = $db->prepare("
                INSERT INTO class_roster (
                    student_id,
                    teacher_id,
                    first_name,
                    last_name,
                    section,
                    room_number,
                    scheduled_time,
                    schedule_day,
                    course_code,
                    course_title,
                    major,
                    course,
                    year_level
                ) VALUES (
                    :student_id,
                    :teacher_id,
                    :first_name,
                    :last_name,
                    :section,
                    :room_number,
                    :scheduled_time,
                    :schedule_day,
                    :course_code,
                    :course_title,
                    :major,
                    :course,
                    :year_level
                )
            ");

            $importedCount = 0;
            $duplicateCount = 0;
            $unregisteredCount = 0;
            $duplicateNames = [];
            $unregisteredNames = [];
            $enrolledNames = [];

            foreach ($students as $row) {
                $rawId = trim($row['student_id'] ?? $row['student_number'] ?? '');
                $cleanId = (string)preg_replace('/\D/', '', $rawId);

                $fn = trim($row['first_name'] ?? '');
                $mi = trim($row['middle_initial'] ?? $row['middle_name'] ?? $row['mi'] ?? '');
                $ln = trim($row['last_name'] ?? '');
                $ext = trim($row['extension'] ?? $row['suffix'] ?? $row['name_extension'] ?? '');

                $miStr = $mi !== '' ? (str_ends_with($mi, '.') ? $mi : $mi . '.') : '';
                $nameParts = array_filter([$fn, $miStr, $ln, $ext], fn($p) => $p !== '');
                $displayFullName = !empty($nameParts) ? implode(' ', $nameParts) : trim($row['full_name'] ?? "Student {$rawId}");

                // 1. Strict Master Check: Must exist in official users table
                if (!isset($existingUsers[$cleanId])) {
                    $unregisteredCount++;
                    $unregisteredNames[] = "{$displayFullName} (ID: {$rawId})";
                    continue; // Skip! Do not create fake accounts
                }

                $officialUser = $existingUsers[$cleanId];
                $userId = (int)$officialUser['user_id'];

                // Use official master name if provided, or spreadsheet name
                $formattedFn = !empty($fn) ? trim($fn . ($mi !== '' ? ' ' . $miStr : '')) : $officialUser['first_name'];
                $formattedLn = !empty($ln) ? trim($ln . ($ext !== '' ? ' ' . $ext : '')) : $officialUser['last_name'];

                // 2. Check if already enrolled in this class roster
                $checkRosterStmt->execute([
                    ':teacher_id'   => $teacherId,
                    ':course_code'  => $courseCode,
                    ':section'      => $section,
                    ':student_id'   => $userId,
                ]);

                if ($checkRosterStmt->fetch()) {
                    $duplicateCount++;
                    $duplicateNames[] = $displayFullName;
                    continue; // Skip duplicate
                }

                // 3. Insert into class_roster
                $insertRosterStmt->execute([
                    ':student_id'    => $userId,
                    ':teacher_id'    => $teacherId,
                    ':first_name'    => $formattedFn,
                    ':last_name'     => $formattedLn,
                    ':section'       => $section,
                    ':room_number'   => $roomNumber,
                    ':scheduled_time'=> $scheduledTime,
                    ':schedule_day'  => $scheduleDay,
                    ':course_code'   => $courseCode,
                    ':course_title'  => $courseTitle,
                    ':major'         => !empty($major) ? $major : null,
                    ':course'        => $course,
                    ':year_level'    => $yearLevel,
                ]);

                $importedCount++;
                $enrolledNames[] = "{$formattedFn} {$formattedLn}";
            }

            $db->commit();

            $total = count($students);
            $totalSkipped = $duplicateCount + $unregisteredCount;
            $allDuplicate = ($total > 0 && $duplicateCount === $total);
            $allUnregistered = ($total > 0 && $unregisteredCount === $total);

            if ($allDuplicate) {
                $message = "All {$total} students in this spreadsheet are already enrolled in {$courseCode} (Section {$section}).";
            } elseif ($allUnregistered) {
                $message = "None of the {$total} student IDs in this spreadsheet exist in the official Student Master records. Please contact the Registrar.";
            } elseif ($totalSkipped > 0) {
                $notes = [];
                if ($duplicateCount > 0) $notes[] = "{$duplicateCount} duplicate(s) skipped";
                if ($unregisteredCount > 0) $notes[] = "{$unregisteredCount} unregistered ID(s) skipped";
                $message = "{$importedCount} student(s) successfully enrolled. (" . implode(', ', $notes) . ").";
            } else {
                $message = "{$importedCount} student(s) successfully enrolled into {$courseCode} (Section {$section}).";
            }

            echo json_encode([
                'success'            => true,
                'status'             => 'success',
                'imported'           => $importedCount,
                'skipped'            => $totalSkipped,
                'duplicate_count'    => $duplicateCount,
                'unregistered_count' => $unregisteredCount,
                'total'              => $total,
                'all_duplicate'      => $allDuplicate,
                'all_unregistered'   => $allUnregistered,
                'duplicate_names'    => $duplicateNames,
                'unregistered_names' => $unregisteredNames,
                'enrolled_names'     => $enrolledNames,
                'course_code'        => $courseCode,
                'section'            => $section,
                'message'            => $message,
                'details'            => [
                    'course'         => $course,
                    'year_level'     => $yearLevel,
                    'section'        => $section,
                    'major'          => $major,
                    'course_code'    => $courseCode,
                    'course_title'   => $courseTitle,
                    'schedule_day'   => $scheduleDay,
                    'scheduled_time' => $scheduledTime,
                    'room_number'    => $roomNumber,
                ],
            ]);
            exit;

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            echo json_encode([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}


