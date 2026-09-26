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
                CASE WHEN r.student_id IS NOT NULL THEN COALESCE(r.course, 'BSIT') ELSE 'Not Enrolled' END AS course,
                CASE 
                    WHEN r.year_level IN (1,2,3,4) THEN r.year_level
                    WHEN r.section REGEXP '^[1-4]' THEN CAST(SUBSTRING(r.section, 1, 1) AS UNSIGNED)
                    ELSE NULL
                END AS year_level,
                CASE 
                    WHEN r.student_id IS NULL THEN 'Not Enrolled Yet'
                    WHEN (CASE WHEN r.year_level IN (1,2,3,4) THEN r.year_level WHEN r.section REGEXP '^[1-4]' THEN CAST(SUBSTRING(r.section, 1, 1) AS UNSIGNED) ELSE 1 END) = 1 THEN '1st Year'
                    WHEN (CASE WHEN r.year_level IN (1,2,3,4) THEN r.year_level WHEN r.section REGEXP '^[1-4]' THEN CAST(SUBSTRING(r.section, 1, 1) AS UNSIGNED) ELSE 1 END) = 2 THEN '2nd Year'
                    WHEN (CASE WHEN r.year_level IN (1,2,3,4) THEN r.year_level WHEN r.section REGEXP '^[1-4]' THEN CAST(SUBSTRING(r.section, 1, 1) AS UNSIGNED) ELSE 1 END) = 3 THEN '3rd Year'
                    WHEN (CASE WHEN r.year_level IN (1,2,3,4) THEN r.year_level WHEN r.section REGEXP '^[1-4]' THEN CAST(SUBSTRING(r.section, 1, 1) AS UNSIGNED) ELSE 1 END) = 4 THEN '4th Year'
                    ELSE 'Not Enrolled Yet'
                END AS grade_level,
                COALESCE(r.section, 'Not Enrolled Yet') AS section,
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

        // Query current section student counts for real-time section allocation and 50-capacity rollover
        $secStmt = $db->query("
            SELECT section, COUNT(*) as student_count
            FROM class_roster
            WHERE section IS NOT NULL AND section != ''
            GROUP BY section
        ");
        $sectionCounts = [];
        while ($secRow = $secStmt->fetch(PDO::FETCH_ASSOC)) {
            $sectionCounts[$secRow['section']] = (int) $secRow['student_count'];
        }

        $page_title = 'Official Student Master Accounts';
        
        include dirname(__DIR__) . '/views/admin/students.php';
    }

    /**
     * Resolve assigned section according to 5-digit [Year][Semester][Sequence] convention and 50-student capacity rollover
     * Format: [Year (1-4)][Semester (1-2)][Sequence (001-999)]
     * e.g.
     * 1st Year, 1st Sem: 11001 (rolls over to 11002 once 50 students reached)
     * 1st Year, 2nd Sem: 12001 (rolls over to 12002 once 50 students reached)
     * 2nd Year, 1st Sem: 21001 | 2nd Sem: 22001
     * 3rd Year, 1st Sem: 31001 | 2nd Sem: 32001
     * 4th Year, 1st Sem: 41001 | 2nd Sem: 42001
     * Pure 5 digits (no course prefix).
     */
    public static function resolveSection(PDO $db, string $course, int $yearLevel, $semesterOrRequested = 1, string $requested = ''): string {
        $course = strtoupper(trim($course));
        if ($yearLevel < 1 || $yearLevel > 4) {
            $yearLevel = 1;
        }

        // Handle backward compatibility: resolveSection($db, $course, $yearLevel, $requestedString)
        $semester = 1;
        if (is_numeric($semesterOrRequested)) {
            $semVal = (int)$semesterOrRequested;
            $semester = ($semVal === 2) ? 2 : 1;
        } elseif (is_string($semesterOrRequested) && !empty($semesterOrRequested)) {
            $requested = $semesterOrRequested;
        }

        // Clean requested: strip any leading non-digits/course prefix if entered (e.g. "BSIT 31001" -> "31001")
        $requested = trim(preg_replace('/^[a-zA-Z\s-]+/', '', $requested));

        // If a specific section was requested and still has capacity (< 50)
        if (!empty($requested)) {
            $chkStmt = $db->prepare("SELECT COUNT(*) FROM class_roster WHERE section = :sec OR section = :sec_legacy");
            $chkStmt->execute([':sec' => $requested, ':sec_legacy' => $course . ' ' . $requested]);
            $cnt = (int) $chkStmt->fetchColumn();
            if ($cnt < 50) {
                return $requested;
            }
        }

        // Auto-assign next available sequence in [Year][Semester]XXX series (e.g. 11001, 12001, etc.)
        $seq = 1;
        while ($seq <= 999) {
            $candidate = sprintf('%d%d%03d', $yearLevel, $semester, $seq);
            $chkStmt = $db->prepare("SELECT COUNT(*) FROM class_roster WHERE section = :sec OR section = :sec_legacy");
            $chkStmt->execute([':sec' => $candidate, ':sec_legacy' => $course . ' ' . $candidate]);
            $cnt = (int) $chkStmt->fetchColumn();
            if ($cnt < 50) {
                return $candidate;
            }
            $seq++;
        }

        return sprintf('%d%d001', $yearLevel, $semester);
    }

    /**
     * POST /admin/students/store
     * Create a new student master account manually
     */
    public function store(): void {
        $studentIdRaw  = trim($_POST['student_id'] ?? '');
        $fullName      = trim($_POST['full_name'] ?? '');
        $emailRaw      = trim($_POST['email_prefix'] ?? $_POST['email'] ?? '');
        $course        = trim($_POST['course'] ?? 'BSIT');
        $yearLevelRaw  = trim($_POST['year_level'] ?? '1st Year');
        $sectionRaw    = trim($_POST['section'] ?? '');
        $parentContact = trim($_POST['parent_contact'] ?? '');

        // Normalize institutional email: automatically attach @bcp.edu.ph
        $emailPrefix   = preg_replace('/@.*$/', '', $emailRaw);
        $emailPrefix   = trim($emailPrefix);
        $email         = !empty($emailPrefix) ? strtolower($emailPrefix) . '@bcp.edu.ph' : '';

        // Validation
        if (empty($studentIdRaw) || empty($fullName) || empty($email)) {
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

        // Default initial password: # + First Uppercase of Last Name + Second Lowercase of Last Name + 8080
        $cleanLast = preg_replace('/[^a-zA-Z]/', '', $lastName);
        if (strlen($cleanLast) >= 2) {
            $c1 = strtoupper(substr($cleanLast, 0, 1));
            $c2 = strtolower(substr($cleanLast, 1, 1));
        } elseif (strlen($cleanLast) === 1) {
            $c1 = strtoupper(substr($cleanLast, 0, 1));
            $c2 = 'x';
        } else {
            $c1 = 'S';
            $c2 = 't';
        }
        $defaultPassword = '#' . $c1 . $c2 . '8080';
        $hashedPassword = password_hash($defaultPassword, PASSWORD_BCRYPT);

        $db = Database::getConnection();

        // Resolve section: Course + Year1001 with 50-student capacity rollover
        $section = self::resolveSection($db, $course, $yearLevel, $sectionRaw);

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

            // Dynamically resolve active teacher ID
            $tStmt = $db->query("SELECT user_id FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY user_id ASC LIMIT 1");
            $defaultTeacherId = (int) ($tStmt->fetchColumn() ?: 2);

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
                    :teacher_id,
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
                ':teacher_id'   => $defaultTeacherId,
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
        fputcsv($output, ['230110150', 'Jerome A. Valdez', 'jerome.valdez@bcp.edu.ph', 'BSIT', '3rd Year', '3-A', '09123456789'], ',', '"', "\\");
        fputcsv($output, ['230110151', 'Alyssa Jane Mercado', 'alyssa.mercado@bcp.edu.ph', 'BSIT', '3rd Year', '3-A', 'alyssa.parent@gmail.com'], ',', '"', "\\");
        fputcsv($output, ['230110152', 'Gabriel Kyle Soriano', 'gabriel.soriano@bcp.edu.ph', 'BSIS', '2nd Year', '2-B', '09987654321'], ',', '"', "\\");
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

        // Sample data rows matching verified students (format: 23011XXXX)
        fputcsv($output, ['230110001', 'Juan', 'A.', 'Dela Cruz', 'Jr.'], ',', '"', "\\");
        fputcsv($output, ['230110002', 'Maria', 'C.', 'Santos', ''], ',', '"', "\\");
        fputcsv($output, ['230110003', 'Pedro', 'M.', 'Reyes', 'III'], ',', '"', "\\");
        fputcsv($output, ['230110004', 'Ana', 'B.', 'Mendoza', ''], ',', '"', "\\");

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
                    :teacher_id,
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

            // Dynamically resolve active teacher ID for imported roster mappings
            $tStmt = $db->query("SELECT user_id FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY user_id ASC LIMIT 1");
            $defaultTeacherId = (int) ($tStmt->fetchColumn() ?: 2);

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

                // Resolve institutional email
                $email = $colEmail !== null ? strtolower(trim($row[$colEmail] ?? '')) : '';
                if (empty($email)) {
                    $cleanF = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($firstName));
                    $cleanL = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($lastName));
                    $email = "{$cleanF}.{$cleanL}@bcp.edu.ph";
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

                // Dynamically resolve section if missing or legacy
                $section = $colSection !== null ? trim($row[$colSection] ?? '') : '';
                if (empty($section) || $section === '3-A') {
                    $section = self::resolveSection($db, $course, $yearLevel);
                }

                // Generate dynamic initial password: # + Last Name Initials + 8080
                $cleanLast = preg_replace('/[^a-zA-Z]/', '', $lastName);
                if (strlen($cleanLast) >= 2) {
                    $c1 = strtoupper(substr($cleanLast, 0, 1));
                    $c2 = strtolower(substr($cleanLast, 1, 1));
                } elseif (strlen($cleanLast) === 1) {
                    $c1 = strtoupper(substr($cleanLast, 0, 1));
                    $c2 = 'x';
                } else {
                    $c1 = 'S';
                    $c2 = 't';
                }
                $rowPassword = '#' . $c1 . $c2 . '8080';
                $rowPasswordHash = password_hash($rowPassword, PASSWORD_BCRYPT);

                // Resolve contact info
                $contact = $colContact !== null ? trim($row[$colContact] ?? '') : '';
                $isEmailContact = filter_var($contact, FILTER_VALIDATE_EMAIL);

                // Insert into users
                $userInsert->execute([
                    ':student_id'    => $studentId,
                    ':email'         => $email,
                    ':password_hash' => $rowPasswordHash,
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
                    ':teacher_id'   => $defaultTeacherId,
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
     * GET/POST /api/teacher/roster/resolve-section
     * Resolves the next available 5-digit section and its current capacity under the school section policy
     */
    public function apiResolveSection(): void {
        header('Content-Type: application/json; charset=utf-8');

        require_once dirname(__DIR__) . '/controllers/SettingsController.php';
        $activeTermSetting = strtolower(trim((string)SettingsController::get('semester', 'first semester')));
        $defaultSemester = (str_contains($activeTermSetting, 'second') || str_contains($activeTermSetting, '2')) ? 2 : 1;

        $course = strtoupper(trim($_GET['course'] ?? $_POST['course'] ?? 'BSIT'));
        $yearLevel = (int)preg_replace('/\D/', '', (string)($_GET['year_level'] ?? $_POST['year_level'] ?? 3)) ?: 3;
        $semesterParam = $_GET['semester'] ?? $_POST['semester'] ?? null;
        $semester = !empty($semesterParam) ? ((int)preg_replace('/\D/', '', (string)$semesterParam) ?: $defaultSemester) : $defaultSemester;

        if ($yearLevel < 1 || $yearLevel > 4) $yearLevel = 3;
        if ($semester < 1 || $semester > 2) $semester = $defaultSemester;

        try {
            $db = Database::getConnection();
            $section = self::resolveSection($db, $course, $yearLevel, $semester);

            $cntStmt = $db->prepare("SELECT COUNT(*) FROM class_roster WHERE section = :sec OR section = :sec_legacy");
            $cntStmt->execute([':sec' => $section, ':sec_legacy' => $course . ' ' . $section]);
            $currentCount = (int) $cntStmt->fetchColumn();

            echo json_encode([
                'success'         => true,
                'course'          => $course,
                'year_level'      => $yearLevel,
                'semester'        => $semester,
                'section'         => $section,
                'current_count'   => $currentCount,
                'max_capacity'    => 50,
                'available_slots' => max(0, 50 - $currentCount)
            ]);
        } catch (\Throwable $e) {
            echo json_encode([
                'success'         => false,
                'message'         => 'Error resolving section: ' . $e->getMessage(),
                'section'         => sprintf('%d%d001', $yearLevel, $semester),
                'current_count'   => 0,
                'max_capacity'    => 50,
                'available_slots' => 50
            ]);
        }
        exit;
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
        $course = strtoupper(trim($input['course'] ?? 'BSIT'));
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

            $secCountStmt = $db->prepare("SELECT COUNT(*) FROM class_roster WHERE section = :section OR section = :sec_legacy");
            $secCountStmt->execute([':section' => $section, ':sec_legacy' => $course . ' ' . $section]);
            $currentSectionEnrolled = (int)$secCountStmt->fetchColumn();

            echo json_encode([
                'success'                => true,
                'status'                 => 'success',
                'teacher_id'             => $teacherId,
                'course_code'            => $courseCode,
                'section'                => $section,
                'section_enrolled_count' => $currentSectionEnrolled,
                'max_capacity'           => 50,
                'available_slots'        => max(0, 50 - $currentSectionEnrolled),
                'would_exceed_capacity'  => (($currentSectionEnrolled + $validCount) > 50),
                'students'               => $validatedRows,
                'total'                  => count($students),
                'valid_count'            => $validCount,
                'duplicate_count'        => $duplicateCount,
                'unregistered_count'     => $unregisteredCount,
                'all_duplicate'          => (count($students) > 0 && $duplicateCount === count($students)),
                'all_unregistered'       => (count($students) > 0 && $unregisteredCount === count($students)),
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

        $course      = strtoupper(trim($input['course'] ?? 'BSIT'));
        $yearLevel   = (int)preg_replace('/\D/', '', (string)($input['year_level'] ?? '3')) ?: 3;
        $semester    = (int)preg_replace('/\D/', '', (string)($input['semester'] ?? '1')) ?: 1;
        $section     = trim($input['section'] ?? '');
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

        if (empty($section)) {
            $section = self::resolveSection($db, $course, $yearLevel, $semester);
        }

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

    /**
     * Resolve the current student ID from session or database fallback
     */
    public static function resolveCurrentStudentId(): int {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!empty($_GET['student_id']) && is_numeric($_GET['student_id'])) {
            return (int)$_GET['student_id'];
        }
        if (!empty($_SESSION['student_id'])) {
            return (int)$_SESSION['student_id'];
        }
        if (!empty($_SESSION['user']['user_id']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'student') {
            return (int)$_SESSION['user']['user_id'];
        }
        if (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student') {
            return (int)$_SESSION['user_id'];
        }
        require_once dirname(__DIR__) . '/core/Cache.php';
        return (int)Cache::remember('default_student_user_id', 3600, function() {
            try {
                $db = Database::getConnection();
                $row = $db->query("SELECT user_id FROM users WHERE role = 'student' ORDER BY user_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return (int)$row['user_id'];
                }
            } catch (Exception $e) {}
            return 1;
        });
    }

    /**
     * Get official and institutional holidays / class suspensions
     * Fetches from system_settings (key: academic_calendar_events) merged with standard Philippine Academic Calendar
     */
    public static function getAcademicCalendarEvents(int $year): array {
        require_once dirname(__DIR__) . '/core/Cache.php';
        return Cache::remember("academic_events_{$year}", 86400, function() use ($year) {
            $events = [
                // Standard Philippine National & Academic Holidays
                sprintf('%04d-01-01', $year) => ['name' => "New Year's Day", 'type' => 'holiday', 'desc' => 'Regular Public Holiday'],
                sprintf('%04d-04-09', $year) => ['name' => "Araw ng Kagitingan (Day of Valor)", 'type' => 'holiday', 'desc' => 'Regular Public Holiday'],
                sprintf('%04d-05-01', $year) => ['name' => "Labor Day", 'type' => 'holiday', 'desc' => 'Regular Public Holiday'],
                sprintf('%04d-06-12', $year) => ['name' => "Independence Day", 'type' => 'holiday', 'desc' => 'Regular Public Holiday'],
                sprintf('%04d-08-21', $year) => ['name' => "Ninoy Aquino Day", 'type' => 'holiday', 'desc' => 'Special Non-Working Holiday'],
                sprintf('%04d-08-31', $year) => ['name' => "National Heroes Day", 'type' => 'holiday', 'desc' => 'Regular Public Holiday'],
                // School Suspensions & Academic Breaks
                sprintf('%04d-09-03', $year) => ['name' => "Class Suspension (Inclement Weather / Typhoon)", 'type' => 'suspension', 'desc' => 'DepEd/CHED & LGU Weather Class Suspension'],
                sprintf('%04d-09-21', $year) => ['name' => "BCP Institutional Foundation Day", 'type' => 'suspension', 'desc' => 'College-wide Non-Working Academic Break'],
                sprintf('%04d-11-01', $year) => ['name' => "All Saints' Day", 'type' => 'holiday', 'desc' => 'Special Non-Working Holiday'],
                sprintf('%04d-11-02', $year) => ['name' => "All Souls' Day", 'type' => 'holiday', 'desc' => 'Special Non-Working Day'],
                sprintf('%04d-11-30', $year) => ['name' => "Bonifacio Day", 'type' => 'holiday', 'desc' => 'Regular Public Holiday'],
                sprintf('%04d-12-08', $year) => ['name' => "Feast of the Immaculate Conception", 'type' => 'holiday', 'desc' => 'Special Non-Working Holiday'],
                sprintf('%04d-12-25', $year) => ['name' => "Christmas Day", 'type' => 'holiday', 'desc' => 'Regular Public Holiday'],
                sprintf('%04d-12-30', $year) => ['name' => "Rizal Day", 'type' => 'holiday', 'desc' => 'Regular Public Holiday'],
                sprintf('%04d-12-31', $year) => ['name' => "New Year's Eve", 'type' => 'holiday', 'desc' => 'Special Non-Working Holiday'],
            ];

            try {
                $db = Database::getConnection();
                $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'academic_calendar_events' LIMIT 1");
                $stmt->execute();
                $val = $stmt->fetchColumn();
                if ($val) {
                    $custom = json_decode($val, true);
                    if (is_array($custom)) {
                        foreach ($custom as $item) {
                            if (!empty($item['date']) && !empty($item['name'])) {
                                $events[$item['date']] = [
                                    'name' => $item['name'],
                                    'type' => $item['type'] ?? 'holiday',
                                    'desc' => $item['desc'] ?? ($item['type'] === 'suspension' ? 'Institutional Class Suspension' : 'Official Holiday')
                                ];
                            }
                        }
                    }
                }
            } catch (Exception $e) {}

            return $events;
        });
    }

    /**
     * Compute comprehensive attendance calendar data for a student for a specific month and year (Cached)
     */
    public static function getStudentCalendarData(int $studentId, int $year, int $month): array {
        require_once dirname(__DIR__) . '/core/Cache.php';
        $cacheKey = "student_calendar_{$studentId}_{$year}_{$month}";

        return Cache::remember($cacheKey, 120, function() use ($studentId, $year, $month) {
            $db = Database::getConnection();

            // Constrain year and month
            $year = max(2020, min(2035, $year));
            $month = max(1, min(12, $month));

            // 1. Fetch Student Profile & Roster details
            $studentInfo = [
                'student_id'   => $studentId,
                'student_code' => '230110001',
                'name'         => 'Juan Dela Cruz',
                'course'       => 'BSIT',
                'section'      => 'BSIT 3-A',
                'grade_level'  => '3rd Year'
            ];
            try {
                $uStmt = $db->prepare("
                    SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email,
                           r.course, r.section, r.year_level
                    FROM users u
                    LEFT JOIN class_roster r ON r.student_id = u.user_id
                    WHERE u.user_id = ?
                    LIMIT 1
                ");
                $uStmt->execute([$studentId]);
                $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
                if ($uRow) {
                    $sec = $uRow['section'] ?? null;
                    $isEnrolled = !empty($sec);
                    $yrNum = null;
                    if ($isEnrolled) {
                        if (!empty($uRow['year_level']) && in_array((int)$uRow['year_level'], [1,2,3,4])) {
                            $yrNum = (int)$uRow['year_level'];
                        } elseif (preg_match('/^[1-4]/', $sec)) {
                            $yrNum = (int)$sec[0];
                        } else {
                            $yrNum = 1;
                        }
                    }
                    $yrLabels = [1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year'];
                    $studentInfo = [
                        'student_id'   => (int)$uRow['user_id'],
                        'student_code' => !empty($uRow['student_id']) ? $uRow['student_id'] : '230110001',
                        'name'         => trim($uRow['first_name'] . ' ' . $uRow['last_name']),
                        'course'       => $isEnrolled ? ($uRow['course'] ?? 'BSIT') : 'Not Enrolled',
                        'section'      => $isEnrolled ? $sec : 'Not Enrolled Yet',
                        'grade_level'  => $isEnrolled ? ($yrLabels[$yrNum] ?? '1st Year') : 'Not Enrolled Yet'
                    ];
                }
            } catch (Exception $e) {}

            // 2. Fetch Academic Events (Holidays & Class Suspensions)
            $events = self::getAcademicCalendarEvents($year);

            // 3. Consolidated Query: Fetch ALL Student Attendance Records in ONE single roundtrip
            $attendanceMap = [];
            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $endDate = sprintf('%04d-%02d-%02d', $year, $month, (int)date('t', strtotime($startDate)));

            $consecutiveAbsences = 0;
            $totalAllAbsences = 0;
            $streakActive = true;

            try {
                $aStmt = $db->prepare("
                    SELECT attendance_id, `date`, `time`, subject, `status`, schedule_date
                    FROM attendance
                    WHERE student_id = ?
                    ORDER BY `date` DESC, `time` DESC
                ");
                $aStmt->execute([$studentId]);
                $allLogs = $aStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

                foreach ($allLogs as $r) {
                    // Filter records for the current calendar month view
                    if ($r['date'] >= $startDate && $r['date'] <= $endDate) {
                        $d = $r['date'];
                        if (!isset($attendanceMap[$d])) {
                            $attendanceMap[$d] = [];
                        }
                        $attendanceMap[$d][] = $r;
                    }

                    // Compute consecutive unexcused absence streak & total absences
                    if ($r['status'] === 'absent') {
                        $totalAllAbsences++;
                        if ($streakActive) {
                            $consecutiveAbsences++;
                        }
                    } elseif ($r['status'] === 'present' || $r['status'] === 'tardy') {
                        $streakActive = false;
                    }
                }
            } catch (Exception $e) {}

            // 4. Fetch Approved Excuse Slips for this month
            $excuseMap = [];
            try {
                $eStmt = $db->prepare("
                    SELECT excuse_slip_id, subject, date_of_absence, reason, explanation, status
                    FROM excuse_slips
                    WHERE student_id = ? AND status = 'approved' AND date_of_absence BETWEEN ? AND ?
                ");
                $eStmt->execute([$studentId, $startDate, $endDate]);
                $eRows = $eStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($eRows as $er) {
                    $excuseMap[$er['date_of_absence']] = $er;
                }
            } catch (Exception $e) {}

            // 5. Month & Calendar Grid Calculations
            $monthTime = strtotime(sprintf('%04d-%02d-01', $year, $month));
            $monthName = date('F', $monthTime);
            $daysInMonth = (int)date('t', $monthTime);
            $firstDayOfWeek = (int)date('w', $monthTime); // 0 = Sunday, 1 = Monday, ... 6 = Saturday
            $todayStr = date('Y-m-d');

            // Navigation links
            $prevMonthTime = strtotime('-1 month', $monthTime);
            $nextMonthTime = strtotime('+1 month', $monthTime);
            $prevMonth = (int)date('n', $prevMonthTime);
            $prevYear = (int)date('Y', $prevMonthTime);
            $nextMonth = (int)date('n', $nextMonthTime);
            $nextYear = (int)date('Y', $nextMonthTime);

            // Days generation & Counters
            $presentDays = 0;
            $tardyDays = 0;
            $absentDays = 0;
            $excusedDays = 0;
            $noClassDays = 0;
            $days = [];

            for ($d = 1; $d <= $daysInMonth; $d++) {
                $dateStr = sprintf('%04d-%02d-%02d', $year, $month, $d);
                $dayOfWeek = (int)date('w', strtotime($dateStr));
                $isWeekend = ($dayOfWeek === 0 || $dayOfWeek === 6);
                $isToday = ($dateStr === $todayStr);
                $isFuture = ($dateStr > $todayStr);

                $status = 'unrecorded';
                $badgeText = '';
                $badgeClass = '';
                $details = '';
                $bgClass = 'bg-white border-slate-200/80 hover:bg-slate-50';

                // Check Precedence 1: Holiday or School Suspension
                if (isset($events[$dateStr])) {
                    $ev = $events[$dateStr];
                    $noClassDays++;
                    if ($ev['type'] === 'suspension') {
                        $status = 'suspension';
                        $badgeText = 'Suspension';
                        $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                        $bgClass = 'bg-white border-slate-200 hover:border-slate-300';
                        $details = "School Suspension: {$ev['name']} ({$ev['desc']})";
                    } else {
                        $status = 'holiday';
                        $badgeText = 'Holiday';
                        $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                        $bgClass = 'bg-white border-slate-200 hover:border-slate-300';
                        $details = "Official Holiday: {$ev['name']} ({$ev['desc']})";
                    }
                }
                // Check Precedence 2: Weekend
                elseif ($isWeekend) {
                    $status = 'weekend';
                    $badgeText = '';
                    $badgeClass = '';
                    $bgClass = 'bg-slate-50/60 border-slate-100 text-slate-400';
                    $details = 'Weekend — No scheduled classes';
                    $noClassDays++;
                }
                // Check Precedence 3: Approved Excuse Slip
                elseif (isset($excuseMap[$dateStr])) {
                    $status = 'excused';
                    $badgeText = 'Excused';
                    $badgeClass = 'bg-sky-50 text-sky-700 border-sky-200';
                    $bgClass = 'bg-white border-slate-200 hover:border-slate-300';
                    $sl = $excuseMap[$dateStr];
                    $details = "Excused Slip Approved: {$sl['reason']}";
                    $excusedDays++;
                }
                // Check Precedence 4: Attendance Log
                elseif (isset($attendanceMap[$dateStr])) {
                    $recs = $attendanceMap[$dateStr];
                    $hasPresent = false;
                    $hasTardy = false;
                    $hasAbsent = false;
                    $subjList = [];

                    foreach ($recs as $r) {
                        $timeFormatted = !empty($r['time']) ? date('h:i A', strtotime($r['time'])) : '';
                        $subjList[] = "{$r['subject']} ({$r['status']}" . ($timeFormatted ? " at {$timeFormatted}" : "") . ")";
                        if ($r['status'] === 'present') $hasPresent = true;
                        if ($r['status'] === 'tardy') $hasTardy = true;
                        if ($r['status'] === 'absent') $hasAbsent = true;
                    }

                    if ($hasPresent) {
                        $status = 'present';
                        $badgeText = 'Present';
                        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                        $bgClass = 'bg-white border-slate-200 hover:border-slate-300';
                        $details = implode(' • ', $subjList);
                        $presentDays++;
                    } elseif ($hasTardy) {
                        $status = 'tardy';
                        $badgeText = 'Late';
                        $badgeClass = 'bg-amber-50 text-amber-700 border-amber-200';
                        $bgClass = 'bg-white border-slate-200 hover:border-slate-300';
                        $details = implode(' • ', $subjList);
                        $tardyDays++;
                    } else {
                        $status = 'absent';
                        $badgeText = 'Absent';
                        $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                        $bgClass = 'bg-white border-slate-200 hover:border-slate-300';
                        $details = implode(' • ', $subjList);
                        $absentDays++;
                    }
                }
                // Check Precedence 5: Future or Current Day
                elseif ($isFuture) {
                    $status = 'upcoming';
                    $badgeText = '';
                    $badgeClass = '';
                    $bgClass = 'bg-white border-slate-100 hover:border-slate-200';
                    $details = 'Scheduled upcoming semester class day';
                } elseif ($isToday) {
                    $status = 'today_pending';
                    $badgeText = 'Today';
                    $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                    $bgClass = 'bg-white border-blue-400 ring-1 ring-blue-400';
                    $details = 'Today: Attendance scan in progress or awaiting session';
                } else {
                    // Past weekday with no attendance and no excuse
                    $status = 'unrecorded';
                    $badgeText = '';
                    $badgeClass = '';
                    $bgClass = 'bg-slate-50/40 border-slate-100 text-slate-400';
                    $details = 'No class session attendance recorded';
                }

                $days[] = [
                    'day'         => $d,
                    'date'        => $dateStr,
                    'day_of_week' => $dayOfWeek,
                    'is_weekend'  => $isWeekend,
                    'is_today'    => $isToday,
                    'status'      => $status,
                    'badge_text'  => $badgeText,
                    'badge_class' => $badgeClass,
                    'bg_class'    => $bgClass,
                    'details'     => $details
                ];
            }

            // Overall Monthly Rate Calculation
            $totalSessions = $presentDays + $tardyDays + $absentDays;
            $ratePercentage = $totalSessions > 0 ? round((($presentDays + $tardyDays) / $totalSessions) * 100, 1) : 100.0;
            $hasDropoutWarning = ($consecutiveAbsences >= 3 || $absentDays >= 3 || $totalAllAbsences >= 3);
            $warningLevel = $consecutiveAbsences >= 3 ? 'critical' : ($absentDays >= 3 || $totalAllAbsences >= 3 ? 'warning' : 'normal');

            return [
                'student'              => $studentInfo,
                'year'                 => $year,
                'month'                => $month,
                'month_name'           => $monthName,
                'month_label'          => "{$monthName} {$year}",
                'prev_month'           => $prevMonth,
                'prev_year'            => $prevYear,
                'next_month'           => $nextMonth,
                'next_year'            => $nextYear,
                'days_in_month'        => $daysInMonth,
                'first_day_offset'     => $firstDayOfWeek,
                'days'                 => $days,
                'consecutive_absences' => $consecutiveAbsences,
                'total_all_absences'   => $totalAllAbsences,
                'has_dropout_warning'  => $hasDropoutWarning,
                'warning_level'        => $warningLevel,
                'kpis'                 => [
                    'present_days'         => $presentDays,
                    'tardy_days'           => $tardyDays,
                    'absent_days'          => $absentDays,
                    'excused_days'         => $excusedDays,
                    'no_class_days'        => $noClassDays,
                    'total_sessions'       => $totalSessions,
                    'rate_percentage'      => $ratePercentage,
                    'consecutive_absences' => $consecutiveAbsences,
                    'total_all_absences'   => $totalAllAbsences,
                    'has_dropout_warning'  => $hasDropoutWarning,
                    'warning_level'        => $warningLevel
                ]
            ];
        });
    }

    /**
     * API endpoint: GET /api/student/calendar
     */
    public function apiCalendarData(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $studentId = self::resolveCurrentStudentId();
            $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
            $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

            $data = self::getStudentCalendarData($studentId, $year, $month);
            echo json_encode([
                'success' => true,
                'status'  => 'success',
                'data'    => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}


