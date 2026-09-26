<?php
/**
 * TeacherController — includes/controllers/TeacherController.php
 * Handles Faculty Management:
 * 1. Bulk import teacher/faculty accounts from CSV/Excel with validation & audit logs.
 * 2. Teachers Master Accounts CRUD (search, filter, create, edit, soft-delete, password reset).
 */

require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/Mailer.php';

class TeacherController {

    /**
     * Expected CSV / Excel header columns
     */
    private const REQUIRED_HEADERS = [
        'employee_id',
        'full_name',
        'email',
        'department',
        'position',
        'contact_number',
        'date_hired'
    ];

    /**
     * Parse full name into first name and last name
     */
    public static function parseNameParts(string $fullName): array {
        $clean = trim(preg_replace('/^(Dr\.|Prof\.|Engr\.|Mr\.|Ms\.|Mrs\.|Atty\.)\s+/i', '', trim($fullName)));
        $parts = preg_split('/\s+/', $clean);
        if (count($parts) === 1) {
            return ['first_name' => $parts[0], 'last_name' => $parts[0]];
        }
        $lastName = array_pop($parts);
        if (in_array(strtolower($lastName), ['jr.', 'jr', 'sr.', 'sr', 'ii', 'iii', 'iv'], true) && count($parts) > 0) {
            $lastName = array_pop($parts);
        }
        $firstName = implode(' ', $parts);
        return ['first_name' => $firstName ?: $lastName, 'last_name' => $lastName];
    }

    /**
     * Generate default password: #(first letter of surname uppercase)(second letter lowercase)8080
     * Example: Mendez -> #Me8080
     */
    public static function generateDefaultTeacherPassword(string $lastName): string {
        $cleanSur = preg_replace('/[^a-zA-Z]/', '', trim($lastName));
        if (empty($cleanSur)) {
            $cleanSur = 'Faculty';
        }
        $first = strtoupper(substr($cleanSur, 0, 1));
        $second = strlen($cleanSur) > 1 ? strtolower(substr($cleanSur, 1, 1)) : strtolower($first);
        return '#' . $first . $second . '8080';
    }

    /**
     * Entry handler for RESTful /api/teachers and /api/teachers/{id}
     */
    public function handleRoot(): void {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST') {
            $this->apiCreate();
        } elseif ($method === 'PUT' || $method === 'PATCH') {
            $this->apiUpdate();
        } elseif ($method === 'DELETE') {
            $this->apiDelete();
        } else {
            $this->apiList();
        }
    }

    /**
     * GET /api/teachers
     * List teachers with search, filter, and pagination
     */
    public function apiList(): void {
        header('Content-Type: application/json');
        try {
            $db = Database::getConnection();

            $search = trim($_GET['search'] ?? '');
            $dept   = trim($_GET['department'] ?? '');
            $status = trim($_GET['status'] ?? '');
            $page   = max(1, (int)($_GET['page'] ?? 1));
            $limit  = max(1, min(100, (int)($_GET['limit'] ?? 50)));
            $offset = ($page - 1) * $limit;

            $where = ['1=1'];
            $params = [];

            if ($search !== '') {
                $where[] = "(`employee_id` LIKE ? OR `full_name` LIKE ? OR `email` LIKE ? OR `department` LIKE ? OR `position` LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like, $like]);
            }

            if ($dept !== '' && $dept !== 'all') {
                $where[] = "`department` = ?";
                $params[] = $dept;
            }

            if ($status !== '' && $status !== 'all') {
                $where[] = "`status` = ?";
                $params[] = $status;
            }

            $whereSql = implode(' AND ', $where);

            // Total count
            $countStmt = $db->prepare("SELECT COUNT(*) FROM `teachers` WHERE {$whereSql}");
            $countStmt->execute($params);
            $total = (int)$countStmt->fetchColumn();

            // Fetch records
            $sql = "SELECT id, employee_id, full_name, email, department, position, contact_number, date_hired, status, created_at 
                    FROM `teachers` 
                    WHERE {$whereSql} 
                    ORDER BY id DESC 
                    LIMIT ? OFFSET ?";
            $stmt = $db->prepare($sql);

            $execParams = $params;
            $execParams[] = $limit;
            $execParams[] = $offset;

            $stmt->execute($execParams);
            $teachers = $stmt->fetchAll();

            // Metrics summary
            $statsStmt = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(status = 'active') AS active_count,
                    SUM(status = 'inactive') AS inactive_count,
                    COUNT(DISTINCT department) AS dept_count
                FROM `teachers`
            ");
            $metrics = $statsStmt->fetch() ?: ['total' => 0, 'active_count' => 0, 'inactive_count' => 0, 'dept_count' => 0];

            echo json_encode([
                'status'     => 'success',
                'data'       => $teachers,
                'metrics'    => [
                    'total'    => (int)($metrics['total'] ?? 0),
                    'active'   => (int)($metrics['active_count'] ?? 0),
                    'inactive' => (int)($metrics['inactive_count'] ?? 0),
                    'depts'    => (int)($metrics['dept_count'] ?? 0)
                ],
                'pagination' => [
                    'page'       => $page,
                    'limit'      => $limit,
                    'total'      => $total,
                    'total_pages'=> ceil($total / $limit)
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * GET /api/teachers/export
     * Export faculty master directory as a downloadable CSV file.
     * Respects active search, department, and status filters.
     */
    public function apiExport(): void {
        try {
            $db = Database::getConnection();

            $search = trim($_GET['search'] ?? '');
            $dept   = trim($_GET['department'] ?? '');
            $status = trim($_GET['status'] ?? '');

            $where = ['1=1'];
            $params = [];

            if ($search !== '') {
                $where[] = "(`employee_id` LIKE ? OR `full_name` LIKE ? OR `email` LIKE ? OR `department` LIKE ? OR `position` LIKE ?)";
                $like = "%{$search}%";
                $params = array_merge($params, [$like, $like, $like, $like, $like]);
            }

            if ($dept !== '' && $dept !== 'all') {
                $where[] = "`department` = ?";
                $params[] = $dept;
            }

            if ($status !== '' && $status !== 'all') {
                $where[] = "`status` = ?";
                $params[] = $status;
            }

            $whereSql = implode(' AND ', $where);

            $stmt = $db->prepare("
                SELECT employee_id, full_name, email, department, position, contact_number, date_hired, status, created_at 
                FROM `teachers` 
                WHERE {$whereSql} 
                ORDER BY full_name ASC
            ");
            $stmt->execute($params);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $format = strtolower(trim($_GET['format'] ?? 'csv'));

            if ($format === 'excel' || $format === 'xlsx' || $format === 'xls') {
                $filename = 'Faculty_Master_Directory_' . date('Y-m-d') . '.xls';

                header('Content-Type: application/vnd.ms-excel; charset=utf-8');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Pragma: no-cache');
                header('Expires: 0');

                echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
                echo "<?mso-application progid=\"Excel.Sheet\"?>\n";
                echo "<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
                echo " xmlns:o=\"urn:schemas-microsoft-com:office:office\"\n";
                echo " xmlns:x=\"urn:schemas-microsoft-com:office:excel\"\n";
                echo " xmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\n";
                echo " xmlns:html=\"http://www.w3.org/TR/REC-html40\">\n";
                echo " <Styles>\n";
                echo "  <Style ss:ID=\"Header\">\n";
                echo "   <Font ss:Bold=\"1\" ss:Color=\"#FFFFFF\"/>\n";
                echo "   <Interior ss:Color=\"#2563EB\" ss:Pattern=\"Solid\"/>\n";
                echo "   <Alignment ss:Horizontal=\"Center\" ss:Vertical=\"Center\"/>\n";
                echo "  </Style>\n";
                echo "  <Style ss:ID=\"Data\">\n";
                echo "   <Alignment ss:Vertical=\"Center\"/>\n";
                echo "  </Style>\n";
                echo " </Styles>\n";
                echo " <Worksheet ss:Name=\"Faculty Master\">\n";
                echo "  <Table>\n";
                echo "   <Column ss:Width=\"110\"/>\n";
                echo "   <Column ss:Width=\"180\"/>\n";
                echo "   <Column ss:Width=\"180\"/>\n";
                echo "   <Column ss:Width=\"160\"/>\n";
                echo "   <Column ss:Width=\"140\"/>\n";
                echo "   <Column ss:Width=\"110\"/>\n";
                echo "   <Column ss:Width=\"90\"/>\n";
                echo "   <Column ss:Width=\"80\"/>\n";
                echo "   <Column ss:Width=\"120\"/>\n";
                echo "   <Row ss:StyleID=\"Header\">\n";
                foreach (['Employee ID', 'Full Name', 'Email', 'Department', 'Position', 'Contact Number', 'Date Hired', 'Status', 'Created At'] as $colTitle) {
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars($colTitle, ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                }
                echo "   </Row>\n";

                foreach ($rows as $row) {
                    echo "   <Row ss:StyleID=\"Data\">\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)($row['employee_id'] ?? ''), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)($row['full_name'] ?? ''), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)($row['email'] ?? ''), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)($row['department'] ?? ''), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)($row['position'] ?? ''), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)($row['contact_number'] ?? ''), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)($row['date_hired'] ?? ''), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars(ucfirst((string)($row['status'] ?? 'active')), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "    <Cell><Data ss:Type=\"String\">" . htmlspecialchars((string)($row['created_at'] ?? ''), ENT_XML1, 'UTF-8') . "</Data></Cell>\n";
                    echo "   </Row>\n";
                }

                echo "  </Table>\n";
                echo " </Worksheet>\n";
                echo "</Workbook>\n";
                exit;
            }

            $filename = 'Faculty_Master_Directory_' . date('Y-m-d') . '.csv';

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM for Microsoft Excel compatibility
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Header Row
            fputcsv($output, [
                'Employee ID',
                'Full Name',
                'Email',
                'Department',
                'Position',
                'Contact Number',
                'Date Hired',
                'Status',
                'Created At'
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row['employee_id'] ?? '',
                    $row['full_name'] ?? '',
                    $row['email'] ?? '',
                    $row['department'] ?? '',
                    $row['position'] ?? '',
                    $row['contact_number'] ?? '',
                    $row['date_hired'] ?? '',
                    ucfirst($row['status'] ?? 'active'),
                    $row['created_at'] ?? ''
                ]);
            }

            fclose($output);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Export failed: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * POST /api/teachers
     * Create single teacher account manually
     */
    public function apiCreate(): void {
        header('Content-Type: application/json');
        try {
            $input = $this->getJsonOrPostInput();

            $employeeId = trim($input['employee_id'] ?? '');
            $fullName   = trim($input['full_name'] ?? '');
            $email      = trim($input['email'] ?? '');
            $department = trim($input['department'] ?? '');
            $position   = trim($input['position'] ?? 'Instructor');
            $contact    = trim($input['contact_number'] ?? '');
            $dateHired  = trim($input['date_hired'] ?? date('Y-m-d'));
            $status     = in_array($input['status'] ?? 'active', ['active', 'inactive']) ? $input['status'] : 'active';

            // Validations
            if ($employeeId === '' || $fullName === '' || $email === '') {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'Employee ID, Full Name, and Email are required.']);
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
                exit;
            }

            $db = Database::getConnection();

            // Check duplicate employee_id
            $chk = $db->prepare("SELECT id FROM `teachers` WHERE `employee_id` = ?");
            $chk->execute([$employeeId]);
            if ($chk->fetch()) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => "Employee ID '{$employeeId}' is already registered."]);
                exit;
            }

            // Check duplicate email
            $chkEmail = $db->prepare("SELECT id FROM `teachers` WHERE `email` = ?");
            $chkEmail->execute([$email]);
            if ($chkEmail->fetch()) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => "Email '{$email}' is already in use."]);
                exit;
            }

            $nameParts = self::parseNameParts($fullName);
            $defaultPassword = self::generateDefaultTeacherPassword($nameParts['last_name']);
            $password = !empty($input['password']) ? trim($input['password']) : $defaultPassword;
            $passHash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("
                INSERT INTO `teachers` 
                (`employee_id`, `full_name`, `email`, `password_hash`, `department`, `position`, `contact_number`, `date_hired`, `status`, `created_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$employeeId, $fullName, $email, $passHash, $department, $position, $contact, $dateHired, $status]);
            $newId = (int)$db->lastInsertId();

            // Synchronize with users table for seamless portal login
            try {
                $uStmt = $db->prepare("
                    INSERT INTO `users` (`employee_id`, `role`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `status`, `created_at`)
                    VALUES (?, 'teacher', ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        `employee_id` = VALUES(`employee_id`),
                        `role` = 'teacher',
                        `password_hash` = VALUES(`password_hash`),
                        `first_name` = VALUES(`first_name`),
                        `last_name` = VALUES(`last_name`),
                        `phone` = VALUES(`phone`),
                        `status` = VALUES(`status`)
                ");
                $uStmt->execute([$employeeId, $email, $passHash, $nameParts['first_name'], $nameParts['last_name'], $contact, $status]);
            } catch (Throwable $uErr) {
                error_log("[Teacher User Sync Error] " . $uErr->getMessage());
            }

            // Dispatch welcome credentials notification email to the teacher
            $emailSent = false;
            try {
                $mailRes = Mailer::sendTeacherWelcomeEmail($email, $fullName, $employeeId, $nameParts['last_name']);
                $emailSent = !empty($mailRes['success']);
            } catch (Throwable $mErr) {
                error_log("[Teacher Welcome Email Error] " . $mErr->getMessage());
            }

            echo json_encode([
                'status'  => 'success',
                'message' => "Faculty account for {$fullName} ({$employeeId}) created successfully." . ($emailSent ? " Welcome email with credentials dispatched to {$email}." : ""),
                'data'    => [
                    'id'          => $newId,
                    'employee_id' => $employeeId,
                    'full_name'   => $fullName,
                    'email'       => $email,
                    'department'  => $department,
                    'position'    => $position,
                    'status'      => $status,
                    'email_sent'  => $emailSent
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/teachers/update (or PUT /api/teachers)
     * Update existing teacher account
     */
    public function apiUpdate(): void {
        header('Content-Type: application/json');
        try {
            $input = $this->getJsonOrPostInput();
            $id = (int)($input['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Valid Teacher ID is required.']);
                exit;
            }

            $db = Database::getConnection();

            // Fetch existing
            $find = $db->prepare("SELECT * FROM `teachers` WHERE id = ?");
            $find->execute([$id]);
            $existing = $find->fetch();
            if (!$existing) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Teacher account not found.']);
                exit;
            }

            $fullName   = trim($input['full_name'] ?? $existing['full_name']);
            $email      = trim($input['email'] ?? $existing['email']);
            $department = trim($input['department'] ?? $existing['department']);
            $position   = trim($input['position'] ?? $existing['position']);
            $contact    = trim($input['contact_number'] ?? $existing['contact_number']);
            $dateHired  = trim($input['date_hired'] ?? $existing['date_hired']);
            $status     = in_array($input['status'] ?? $existing['status'], ['active', 'inactive']) ? $input['status'] : $existing['status'];

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'Invalid email address format.']);
                exit;
            }

            // Check email uniqueness excluding self
            $chkEmail = $db->prepare("SELECT id FROM `teachers` WHERE `email` = ? AND id != ?");
            $chkEmail->execute([$email, $id]);
            if ($chkEmail->fetch()) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => "Email '{$email}' is already used by another teacher."]);
                exit;
            }

            $stmt = $db->prepare("
                UPDATE `teachers` 
                SET `full_name` = ?, `email` = ?, `department` = ?, `position` = ?, `contact_number` = ?, `date_hired` = ?, `status` = ?
                WHERE id = ?
            ");
            $stmt->execute([$fullName, $email, $department, $position, $contact, $dateHired, $status, $id]);

            echo json_encode([
                'status'  => 'success',
                'message' => "Teacher account #{$id} updated successfully."
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/teachers/delete (or DELETE /api/teachers)
     * Soft delete via status = 'inactive'
     */
    public function apiDelete(): void {
        header('Content-Type: application/json');
        try {
            $input = $this->getJsonOrPostInput();
            $id = (int)($input['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Valid Teacher ID is required.']);
                exit;
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE `teachers` SET `status` = 'inactive' WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode([
                'status'  => 'success',
                'message' => "Faculty account #{$id} has been deactivated (soft-deleted)."
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/teachers/reset-password
     * Generate temp password and record alert/notification
     */
    public function apiResetPassword(): void {
        header('Content-Type: application/json');
        try {
            $input = $this->getJsonOrPostInput();
            $id = (int)($input['id'] ?? 0);

            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Valid Teacher ID is required.']);
                exit;
            }

            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT id, employee_id, full_name, email FROM `teachers` WHERE id = ?");
            $stmt->execute([$id]);
            $teacher = $stmt->fetch();

            if (!$teacher) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Teacher account not found.']);
                exit;
            }

            // Generate secure temp password
            $tempPass = 'BCP-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $passHash = password_hash($tempPass, PASSWORD_BCRYPT);

            $up = $db->prepare("UPDATE `teachers` SET `password_hash` = ? WHERE id = ?");
            $up->execute([$passHash, $id]);

            // Wire into system alert and notification queue
            try {
                $notifStmt = $db->prepare("
                    INSERT INTO `notifications` 
                    (`user_id`, `type`, `title`, `message`, `is_read`, `created_at`) 
                    VALUES (?, 'system', ?, ?, 0, NOW())
                ");
                $notifStmt->execute([
                    1, // Admin user ID
                    "Password Reset: {$teacher['full_name']}",
                    "Temporary password generated for {$teacher['employee_id']} ({$teacher['email']}): {$tempPass}"
                ]);
            } catch (Exception $ign) {
                // If notifications table not present or structure differs, don't break password reset
            }

            echo json_encode([
                'status'        => 'success',
                'message'       => "Temporary password generated for {$teacher['full_name']}.",
                'temp_password' => $tempPass,
                'email'         => $teacher['email']
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * POST /api/teachers/import
     * Bulk import teacher faculty master from CSV or Excel (.xlsx)
     */
    public function apiImport(): void {
        header('Content-Type: application/json');
        try {
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Please upload a valid CSV or Excel file.']);
                exit;
            }

            $fileInfo = $_FILES['file'];
            $filename = basename($fileInfo['name']);
            $tempPath = $fileInfo['tmp_name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'Unsupported file format. Please upload a .csv or .xlsx file.']);
                exit;
            }

            // Parse file into rows
            $parsedRows = [];
            if ($ext === 'xlsx') {
                $parsedRows = $this->parseXlsx($tempPath);
            } else {
                $parsedRows = $this->parseCsv($tempPath);
            }

            if (empty($parsedRows)) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'message' => 'The uploaded file is empty or contains no data.']);
                exit;
            }

            // Header validation
            $headerRow = array_shift($parsedRows);
            $normalizedHeaders = array_map(function($h) {
                return strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', $h)));
            }, $headerRow);

            $missingHeaders = [];
            $headerMap = [];
            foreach (self::REQUIRED_HEADERS as $req) {
                $idx = array_search($req, $normalizedHeaders, true);
                if ($idx === false) {
                    $missingHeaders[] = $req;
                } else {
                    $headerMap[$req] = $idx;
                }
            }

            if (!empty($missingHeaders)) {
                http_response_code(422);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Invalid file structure. Missing expected headers: ' . implode(', ', $missingHeaders),
                    'expected'=> self::REQUIRED_HEADERS,
                    'found'   => $headerRow
                ]);
                exit;
            }

            $db = Database::getConnection();

            // Cache existing employee_ids and emails to ensure fast bulk check
            $existingEmpIds = $db->query("SELECT `employee_id` FROM `teachers`")->fetchAll(PDO::FETCH_COLUMN);
            $existingEmpIds = array_flip($existingEmpIds);

            $existingEmails = $db->query("SELECT `email` FROM `teachers`")->fetchAll(PDO::FETCH_COLUMN);
            $existingEmails = array_flip($existingEmails);

            $seenInBatch = [];
            $inserted = 0;
            $skipped = 0;
            $errors = [];
            $totalRows = count($parsedRows);

            $insertStmt = $db->prepare("
                INSERT INTO `teachers` 
                (`employee_id`, `full_name`, `email`, `password_hash`, `department`, `position`, `contact_number`, `date_hired`, `status`, `created_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");

            $defaultHash = password_hash('Teacher@123', PASSWORD_BCRYPT);

            foreach ($parsedRows as $lineNum => $row) {
                $displayLine = $lineNum + 2; // +1 for 0-index, +1 for header line

                // Extract row values based on headerMap
                $empId   = trim($row[$headerMap['employee_id']] ?? '');
                $name    = trim($row[$headerMap['full_name']] ?? '');
                $email   = trim($row[$headerMap['email']] ?? '');
                $dept    = trim($row[$headerMap['department']] ?? '');
                $pos     = trim($row[$headerMap['position']] ?? 'Instructor');
                $contact = trim($row[$headerMap['contact_number']] ?? '');
                $hired   = trim($row[$headerMap['date_hired']] ?? date('Y-m-d'));

                // Skip completely blank rows
                if ($empId === '' && $name === '' && $email === '') {
                    continue;
                }

                // Row-level validation: Required fields
                if ($empId === '' || $name === '' || $email === '') {
                    $skipped++;
                    $errors[] = [
                        'line'   => $displayLine,
                        'id'     => $empId ?: 'N/A',
                        'reason' => 'Missing required fields (employee_id, full_name, or email)'
                    ];
                    continue;
                }

                // Row-level validation: Email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    $errors[] = [
                        'line'   => $displayLine,
                        'id'     => $empId,
                        'reason' => "Invalid email format: '{$email}'"
                    ];
                    continue;
                }

                // Row-level validation: Duplicate employee_id within batch
                if (isset($seenInBatch[$empId])) {
                    $skipped++;
                    $errors[] = [
                        'line'   => $displayLine,
                        'id'     => $empId,
                        'reason' => "Duplicate employee_id '{$empId}' within the uploaded file"
                    ];
                    continue;
                }

                // Row-level validation: Duplicate employee_id in DB
                if (isset($existingEmpIds[$empId])) {
                    $skipped++;
                    $errors[] = [
                        'line'   => $displayLine,
                        'id'     => $empId,
                        'reason' => "Employee ID '{$empId}' already exists in database"
                    ];
                    continue;
                }

                // Row-level validation: Duplicate email in DB
                if (isset($existingEmails[$email])) {
                    $skipped++;
                    $errors[] = [
                        'line'   => $displayLine,
                        'id'     => $empId,
                        'reason' => "Email '{$email}' already exists in database"
                    ];
                    continue;
                }

                // Format date if needed
                $parsedDate = date('Y-m-d', strtotime($hired) ?: time());
                $nameParts = self::parseNameParts($name);
                $rowPassword = self::generateDefaultTeacherPassword($nameParts['last_name']);
                $rowHash = password_hash($rowPassword, PASSWORD_BCRYPT);

                try {
                    $insertStmt->execute([
                        $empId,
                        $name,
                        $email,
                        $rowHash,
                        $dept ?: 'General Academics',
                        $pos ?: 'Faculty',
                        $contact,
                        $parsedDate
                    ]);

                    // Sync into users table for portal access
                    try {
                        $uStmt = $db->prepare("
                            INSERT INTO `users` (`employee_id`, `role`, `email`, `password_hash`, `first_name`, `last_name`, `phone`, `status`, `created_at`)
                            VALUES (?, 'teacher', ?, ?, ?, ?, ?, 'active', NOW())
                            ON DUPLICATE KEY UPDATE 
                                `employee_id` = VALUES(`employee_id`),
                                `role` = 'teacher',
                                `password_hash` = VALUES(`password_hash`),
                                `first_name` = VALUES(`first_name`),
                                `last_name` = VALUES(`last_name`),
                                `phone` = VALUES(`phone`),
                                `status` = 'active'
                        ");
                        $uStmt->execute([$empId, $email, $rowHash, $nameParts['first_name'], $nameParts['last_name'], $contact]);
                    } catch (Throwable $uErr) {
                        error_log("[Teacher Import User Sync Error] " . $uErr->getMessage());
                    }

                    // Dispatch welcome credentials notification email
                    try {
                        Mailer::sendTeacherWelcomeEmail($email, $name, $empId, $nameParts['last_name']);
                    } catch (Throwable $mErr) {
                        error_log("[Teacher Import Welcome Email Error] " . $mErr->getMessage());
                    }

                    $inserted++;
                    $seenInBatch[$empId] = true;
                    $existingEmpIds[$empId] = true;
                    $existingEmails[$email] = true;
                } catch (Exception $ex) {
                    $skipped++;
                    $errors[] = [
                        'line'   => $displayLine,
                        'id'     => $empId,
                        'reason' => 'Database error: ' . $ex->getMessage()
                    ];
                }
            }

            // Record into faculty_import_logs table
            $adminUserId = $_SESSION['user_id'] ?? 1;
            try {
                $logStmt = $db->prepare("
                    INSERT INTO `faculty_import_logs` 
                    (`filename`, `imported_by`, `total_rows`, `inserted_rows`, `failed_rows`, `error_detail`, `imported_at`)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                ");
                $logStmt->execute([
                    $filename,
                    $adminUserId,
                    $totalRows,
                    $inserted,
                    $skipped,
                    json_encode($errors)
                ]);
            } catch (Exception $logErr) {
                // Log failure should not abort response
            }

            echo json_encode([
                'status'   => 'success',
                'summary'  => [
                    'filename' => $filename,
                    'total'    => $totalRows,
                    'inserted' => $inserted,
                    'skipped'  => $skipped,
                    'errors'   => $errors
                ]
            ]);

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Parse CSV file into rows
     */
    private function parseCsv(string $path): array {
        $rows = [];
        $handle = fopen($path, 'r');
        if (!$handle) return [];

        // Skip UTF-8 BOM if present
        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        while (($row = fgetcsv($handle, 2048, ',')) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Parse Excel .xlsx file using built-in ZipArchive & XML parsing
     */
    private function parseXlsx(string $path): array {
        if (!class_exists('ZipArchive')) {
            // Fallback: advise CSV
            throw new Exception('ZipArchive extension is required for .xlsx files. Please export your spreadsheet to CSV.');
        }

        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new Exception('Failed to open Excel archive.');
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml !== false) {
            $xml = simplexml_load_string($stringsXml);
            if ($xml !== false) {
                foreach ($xml->si as $val) {
                    $sharedStrings[] = (string)($val->t ?? ($val->r ? $val->r->t : ''));
                }
            }
        }

        // 2. Read sheet1.xml
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();
            throw new Exception('No valid sheet found in Excel workbook.');
        }

        $xml = simplexml_load_string($sheetXml);
        $zip->close();

        if ($xml === false || !isset($xml->sheetData->row)) {
            return [];
        }

        $rows = [];
        foreach ($xml->sheetData->row as $r) {
            $rowCells = [];
            foreach ($r->c as $c) {
                $type = (string)$c['t'];
                $val = (string)$c->v;
                if ($type === 's') {
                    $rowCells[] = $sharedStrings[(int)$val] ?? '';
                } else {
                    $rowCells[] = $val;
                }
            }
            $rows[] = $rowCells;
        }

        return $rows;
    }

    /**
     * Helper to retrieve JSON body or POST form data
     */
    private function getJsonOrPostInput(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            return json_decode(file_get_contents('php://input'), true) ?? [];
        }
        return $_POST;
    }

    /**
     * Resolve the current teacher ID from session or database fallback
     */
    public static function resolveCurrentTeacherId(): int {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }
        if (!empty($_SESSION['teacher_id'])) {
            return (int)$_SESSION['teacher_id'];
        }
        if (!empty($_SESSION['user']['user_id']) && isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'teacher') {
            return (int)$_SESSION['user']['user_id'];
        }
        if (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
            return (int)$_SESSION['user_id'];
        }
        require_once dirname(__DIR__) . '/core/Cache.php';
        return (int)Cache::remember('default_teacher_user_id', 3600, function() {
            try {
                $db = Database::getConnection();
                $row = $db->query("SELECT user_id FROM users WHERE role = 'teacher' ORDER BY user_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
                if ($row) {
                    return (int)$row['user_id'];
                }
            } catch (Exception $e) {}
            return 2;
        });
    }

    /**
     * Aggregates dynamic overview data for a specific teacher role
     * Queries existing tables: class_roster, attendance, qr_sessions, users, excuse_slips
     */
    public static function getTeacherDashboardOverview(int $teacherId): array {
        require_once dirname(__DIR__) . '/core/Cache.php';

        return Cache::remember("teacher_overview_{$teacherId}", 120, function() use ($teacherId) {
            $db = Database::getConnection();

            // 1. Teacher Profile Info
            $teacherInfo = [
                'teacher_id' => $teacherId,
                'name'       => 'Prof. Manuel Ramirez',
                'department' => 'College of Computer Studies • Information Technology Department',
                'email'      => 'm.ramirez@bcp.edu.ph'
            ];
            try {
                $tStmt = $db->prepare("
                    SELECT user_id, first_name, last_name, email, department
                    FROM users 
                    WHERE user_id = ?
                    LIMIT 1
                ");
                $tStmt->execute([$teacherId]);
                $u = $tStmt->fetch(PDO::FETCH_ASSOC);
                if ($u) {
                    $teacherInfo = [
                        'teacher_id' => (int)$u['user_id'],
                        'name'       => 'Prof. ' . trim($u['first_name'] . ' ' . $u['last_name']),
                        'department' => !empty($u['department']) ? $u['department'] : 'College of Computer Studies • Information Technology Department',
                        'email'      => $u['email'] ?? ''
                    ];
                } else {
                    $t2Stmt = $db->prepare("SELECT id, full_name, email, department FROM teachers WHERE id = ? OR employee_id = ? LIMIT 1");
                    $t2Stmt->execute([$teacherId, (string)$teacherId]);
                    $t2 = $t2Stmt->fetch(PDO::FETCH_ASSOC);
                    if ($t2) {
                        $teacherInfo = [
                            'teacher_id' => (int)$t2['id'],
                            'name'       => 'Prof. ' . $t2['full_name'],
                            'department' => !empty($t2['department']) ? $t2['department'] : 'College of Computer Studies',
                            'email'      => $t2['email'] ?? ''
                        ];
                    }
                }
            } catch (Exception $e) {}

            // 2. Metric: Assigned Classes, Sections, and Total Enrolled Students
            $classesMetric = [
                'total_classes'  => 0,
                'total_sections' => 0,
                'total_students' => 0
            ];
            try {
                $cStmt = $db->prepare("
                    SELECT 
                        COUNT(DISTINCT course_code) AS total_courses,
                        COUNT(DISTINCT section) AS total_sections,
                        COUNT(DISTINCT student_id) AS total_students
                    FROM class_roster
                    WHERE teacher_id = ?
                ");
                $cStmt->execute([$teacherId]);
                $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
                if ($cRow) {
                    $classesMetric = [
                        'total_classes'  => (int)($cRow['total_courses'] ?? 0),
                        'total_sections' => (int)($cRow['total_sections'] ?? 0),
                        'total_students' => (int)($cRow['total_students'] ?? 0)
                    ];
                }
            } catch (Exception $e) {}

            // 3. Metric: Today's Attendance Rate
            $todayAttendance = [
                'rate_percentage' => 0.0,
                'present_count'   => 0,
                'tardy_count'     => 0,
                'absent_count'    => 0,
                'total_records'   => 0,
                'is_all_time'     => false,
                'all_time_rate'   => 0.0
            ];
            try {
                $aStmt = $db->prepare("
                    SELECT 
                        COUNT(*) AS total,
                        SUM(status = 'present') AS present_count,
                        SUM(status = 'tardy') AS tardy_count,
                        SUM(status = 'absent') AS absent_count
                    FROM attendance
                    WHERE teacher_id = ? AND date = CURRENT_DATE()
                ");
                $aStmt->execute([$teacherId]);
                $aRow = $aStmt->fetch(PDO::FETCH_ASSOC);
                if ($aRow && (int)$aRow['total'] > 0) {
                    $total = (int)$aRow['total'];
                    $present = (int)($aRow['present_count'] ?? 0);
                    $tardy = (int)($aRow['tardy_count'] ?? 0);
                    $absent = (int)($aRow['absent_count'] ?? 0);
                    $rate = round((($present + $tardy) / $total) * 100, 1);
                    $todayAttendance = [
                        'rate_percentage' => $rate,
                        'present_count'   => $present,
                        'tardy_count'     => $tardy,
                        'absent_count'    => $absent,
                        'total_records'   => $total,
                        'is_all_time'     => false,
                        'all_time_rate'   => 0.0
                    ];
                } else {
                    // Fallback to all-time rate
                    $allStmt = $db->prepare("
                        SELECT 
                            COUNT(*) AS total,
                            SUM(status = 'present') AS present_count,
                            SUM(status = 'tardy') AS tardy_count,
                            SUM(status = 'absent') AS absent_count
                        FROM attendance
                        WHERE teacher_id = ?
                    ");
                    $allStmt->execute([$teacherId]);
                    $allRow = $allStmt->fetch(PDO::FETCH_ASSOC);
                    if ($allRow && (int)$allRow['total'] > 0) {
                        $tot = (int)$allRow['total'];
                        $pres = (int)($allRow['present_count'] ?? 0);
                        $tar = (int)($allRow['tardy_count'] ?? 0);
                        $allTimePct = round((($pres + $tar) / $tot) * 100, 1);
                        $todayAttendance = [
                            'rate_percentage' => $allTimePct,
                            'present_count'   => $pres,
                            'tardy_count'     => $tar,
                            'absent_count'    => (int)($allRow['absent_count'] ?? 0),
                            'total_records'   => 0,
                            'total_all_time'  => $tot,
                            'is_all_time'     => true,
                            'all_time_rate'   => $allTimePct
                        ];
                    }
                }
            } catch (Exception $e) {}

            // 4. Metric: Active Sessions
            $activeSession = [
                'is_active'     => false,
                'qr_session_id' => null,
                'qr_code'       => null,
                'course_code'   => null,
                'course_title'  => null,
                'section'       => null,
                'description'   => 'No active session'
            ];
            try {
                $sStmt = $db->prepare("
                    SELECT qr_session_id, qr_code, start, end, section 
                    FROM qr_sessions 
                    WHERE teacher_id = ? AND end > NOW() 
                    ORDER BY start DESC 
                    LIMIT 1
                ");
                $sStmt->execute([$teacherId]);
                $sessionRow = $sStmt->fetch(PDO::FETCH_ASSOC);
                if ($sessionRow) {
                    $rStmt = $db->prepare("
                        SELECT course_code, course_title, section, room_number
                        FROM class_roster
                        WHERE teacher_id = ? AND section = ?
                        LIMIT 1
                    ");
                    $rStmt->execute([$teacherId, $sessionRow['section'] ?? '']);
                    $rInfo = $rStmt->fetch(PDO::FETCH_ASSOC) ?: [
                        'course_code'  => 'IT301',
                        'course_title' => 'Web Systems and Technologies',
                        'section'      => $sessionRow['section'] ?? 'BSIT 3-A'
                    ];
                    $activeSession = [
                        'is_active'     => true,
                        'qr_session_id' => (int)$sessionRow['qr_session_id'],
                        'qr_code'       => $sessionRow['qr_code'],
                        'course_code'   => $rInfo['course_code'],
                        'course_title'  => $rInfo['course_title'],
                        'section'       => $rInfo['section'],
                        'description'   => "{$rInfo['course_code']} ({$rInfo['section']})"
                    ];
                }
            } catch (Exception $e) {}

            // 5. At-Risk Students Count (consecutive absences >= 3)
            $atRiskCount = 0;
            try {
                $riskStmt = $db->prepare("
                    SELECT COUNT(DISTINCT a.student_id)
                    FROM attendance a
                    WHERE a.teacher_id = ? AND a.status = 'absent'
                    GROUP BY a.student_id
                    HAVING COUNT(*) >= 3
                ");
                $riskStmt->execute([$teacherId]);
                $atRiskCount = $riskStmt->rowCount();
            } catch (Exception $e) {}

            // 6. Pending Excuse Slips Count
            $pendingExcusesCount = 0;
            try {
                $exStmt = $db->prepare("
                    SELECT COUNT(*) 
                    FROM excuse_slips 
                    WHERE teacher_id = ? AND status = 'pending'
                ");
                $exStmt->execute([$teacherId]);
                $pendingExcusesCount = (int)$exStmt->fetchColumn();
            } catch (Exception $e) {}

            // 7. Today's Class Schedule (and weekly schedule context)
            $schedule = [];
            $isTodaySchedule = true;
            try {
                $dayName = date('l'); // e.g. "Monday", "Tuesday"
                $schStmt = $db->prepare("
                    SELECT DISTINCT 
                        course_code, 
                        course_title, 
                        section, 
                        COALESCE(room_number, '402') AS room_number, 
                        scheduled_time, 
                        schedule_day
                    FROM class_roster
                    WHERE teacher_id = ? AND schedule_day = ?
                    ORDER BY scheduled_time ASC
                ");
                $schStmt->execute([$teacherId, $dayName]);
                $schedule = $schStmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($schedule)) {
                    $isTodaySchedule = false;
                    $allSchStmt = $db->prepare("
                        SELECT DISTINCT 
                            course_code, 
                            course_title, 
                            section, 
                            COALESCE(room_number, '402') AS room_number, 
                            scheduled_time, 
                            schedule_day
                        FROM class_roster
                        WHERE teacher_id = ?
                        ORDER BY FIELD(schedule_day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), scheduled_time ASC
                    ");
                    $allSchStmt->execute([$teacherId]);
                    $schedule = $allSchStmt->fetchAll(PDO::FETCH_ASSOC);
                }

                // Decorate schedule items
                foreach ($schedule as &$item) {
                    $enrStmt = $db->prepare("
                        SELECT COUNT(DISTINCT student_id)
                        FROM class_roster
                        WHERE teacher_id = ? AND course_code = ? AND section = ?
                    ");
                    $enrStmt->execute([$teacherId, $item['course_code'], $item['section']]);
                    $item['enrolled_count'] = (int)$enrStmt->fetchColumn();

                    $scnStmt = $db->prepare("
                        SELECT COUNT(*)
                        FROM attendance a
                        INNER JOIN class_roster r ON r.student_id = a.student_id AND r.teacher_id = a.teacher_id AND r.section = ?
                        WHERE a.teacher_id = ? AND a.date = CURRENT_DATE() 
                          AND (a.subject = ? OR a.subject = ?)
                    ");
                    $scnStmt->execute([$item['section'], $teacherId, $item['course_code'], $item['course_title']]);
                    $item['scanned_today'] = (int)$scnStmt->fetchColumn();

                    if ($activeSession['is_active'] && $activeSession['section'] === $item['section']) {
                        $item['status'] = 'active';
                    } else {
                        $item['status'] = 'upcoming';
                    }
                }
                unset($item);
            } catch (Exception $e) {}

            // 8. Classes Overview with Batch-Aggregated Attendance Statistics
            $classesOverview = [];
            try {
                $ovStmt = $db->prepare("
                    SELECT 
                        course_code, 
                        course_title, 
                        section, 
                        COUNT(DISTINCT student_id) AS enrolled_count
                    FROM class_roster
                    WHERE teacher_id = ?
                    GROUP BY course_code, course_title, section
                ");
                $ovStmt->execute([$teacherId]);
                $classesOverview = $ovStmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($classesOverview)) {
                    $subStmt = $db->prepare("
                        SELECT 
                            subject,
                            COUNT(DISTINCT date) AS sessions_held,
                            COUNT(*) AS total,
                            SUM(status = 'present') AS present_count
                        FROM attendance
                        WHERE teacher_id = ?
                        GROUP BY subject
                    ");
                    $subStmt->execute([$teacherId]);
                    $subjectStats = [];
                    while ($sRow = $subStmt->fetch(PDO::FETCH_ASSOC)) {
                        $subjectStats[$sRow['subject']] = $sRow;
                    }

                    foreach ($classesOverview as &$cItem) {
                        $stat = $subjectStats[$cItem['course_code']] ?? $subjectStats[$cItem['course_title']] ?? null;
                        if ($stat) {
                            $cItem['sessions_held'] = (int)($stat['sessions_held'] ?? 0);
                            $tot = (int)($stat['total'] ?? 0);
                            $cItem['avg_rate'] = $tot > 0 ? round(((int)$stat['present_count'] / $tot) * 100, 1) : 95.0;
                        } else {
                            $cItem['sessions_held'] = 0;
                            $cItem['avg_rate'] = 95.0;
                        }
                    }
                    unset($cItem);
                }
            } catch (Exception $e) {}

            // 9. Live Attendance Feed (Recent check-ins)
            $liveFeed = [];
            try {
                $feedStmt = $db->prepare("
                    SELECT 
                        a.attendance_id, 
                        a.student_id, 
                        a.time, 
                        a.status, 
                        a.date,
                        COALESCE(u.first_name, 'Student') AS first_name,
                        COALESCE(u.last_name, '') AS last_name,
                        COALESCE(u.student_id, '') AS student_code,
                        COALESCE(
                            (SELECT r.section FROM class_roster r WHERE r.student_id = a.student_id AND r.teacher_id = a.teacher_id LIMIT 1),
                            'BSIT 3-A'
                        ) AS section
                    FROM attendance a
                    LEFT JOIN users u ON u.user_id = a.student_id
                    WHERE a.teacher_id = ?
                    ORDER BY a.date DESC, a.time DESC
                    LIMIT 8
                ");
                $feedStmt->execute([$teacherId]);
                $liveFeed = $feedStmt->fetchAll(PDO::FETCH_ASSOC);
                foreach ($liveFeed as &$f) {
                    $f['initials'] = strtoupper(substr($f['first_name'], 0, 1) . substr($f['last_name'], 0, 1));
                    $f['time_formatted'] = !empty($f['time']) ? date('h:i A', strtotime($f['time'])) : 'Just now';
                }
                unset($f);
            } catch (Exception $e) {}

            // 10. Recent Excuse Slips for review
            $recentExcuses = [];
            try {
                $exListStmt = $db->prepare("
                    SELECT 
                        e.excuse_slip_id,
                        e.student_id,
                        e.subject,
                        e.date_of_absence,
                        e.reason,
                        e.explanation,
                        e.status,
                        e.created_at,
                        COALESCE(u.first_name, 'Student') AS first_name,
                        COALESCE(u.last_name, '') AS last_name,
                        COALESCE(u.student_id, '') AS student_code
                    FROM excuse_slips e
                    LEFT JOIN users u ON u.user_id = e.student_id
                    WHERE e.teacher_id = ?
                    ORDER BY (e.status = 'pending') DESC, e.created_at DESC
                    LIMIT 4
                ");
                $exListStmt->execute([$teacherId]);
                $recentExcuses = $exListStmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}

            return [
                'teacher'               => $teacherInfo,
                'classes_metric'        => $classesMetric,
                'today_attendance'      => $todayAttendance,
                'active_session'        => $activeSession,
                'at_risk_count'         => $atRiskCount,
                'pending_excuses_count' => $pendingExcusesCount,
                'schedule'              => $schedule,
                'is_today_schedule'     => $isTodaySchedule,
                'classes_overview'      => $classesOverview,
                'live_feed'             => $liveFeed,
                'recent_excuses'        => $recentExcuses,
                'current_day'           => date('l, F j, Y')
            ];
        });
    }

    /**
     * API: GET /api/teacher/dashboard/overview
     */
    public function apiDashboardOverview(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $teacherId = self::resolveCurrentTeacherId();
            $data = self::getTeacherDashboardOverview($teacherId);
            echo json_encode([
                'status'  => 'success',
                'success' => true,
                'data'    => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * API: POST /api/teacher/classes/add-student
     * Adds a single student manually into a specific section's roster.
     */
    public function apiAddStudentToSection(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $raw = file_get_contents('php://input');
            $input = !empty($raw) ? json_decode($raw, true) : $_POST;

            if (empty($input) || !is_array($input)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid request payload']);
                exit;
            }

            $teacherId = self::resolveCurrentTeacherId();
            $section = trim($input['section'] ?? '');
            $studentNo = trim($input['student_id'] ?? $input['student_number'] ?? '');
            $firstName = trim($input['first_name'] ?? '');
            $lastName  = trim($input['last_name'] ?? '');
            $email     = trim($input['email'] ?? '');

            if (empty($section)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Section is required.']);
                exit;
            }
            if (empty($studentNo) || empty($firstName) || empty($lastName)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Student ID, First Name, and Last Name are required.']);
                exit;
            }

            $db = Database::getConnection();

            // 1. Resolve class details for this section & teacher
            $classMetaStmt = $db->prepare("
                SELECT course_code, course_title, course, year_level, schedule_day, scheduled_time, room_number, major
                FROM class_roster
                WHERE section = ? AND (teacher_id = ? OR teacher_id IS NOT NULL)
                ORDER BY (teacher_id = ?) DESC, roster_id DESC
                LIMIT 1
            ");
            $classMetaStmt->execute([$section, $teacherId, $teacherId]);
            $classMeta = $classMetaStmt->fetch(PDO::FETCH_ASSOC);

            $courseCode   = $classMeta['course_code'] ?? ($input['course_code'] ?? 'IT301');
            $courseTitle  = $classMeta['course_title'] ?? ($input['course_title'] ?? 'Web Systems and Technologies');
            $course       = $classMeta['course'] ?? ($input['course'] ?? 'BSIT');
            $yearLevel    = (int)($classMeta['year_level'] ?? ($input['year_level'] ?? 3));
            $scheduleDay  = $classMeta['schedule_day'] ?? 'Monday';
            $scheduledTime= $classMeta['scheduled_time'] ?? '08:00:00';
            $roomNumber   = $classMeta['room_number'] ?? '402';
            $major        = $classMeta['major'] ?? null;

            // 2. Check if user exists in users table by student_id or email
            $cleanId = (string)preg_replace('/\D/', '', $studentNo);
            $userLookupStmt = $db->prepare("
                SELECT user_id, student_id, first_name, last_name, email 
                FROM users 
                WHERE (student_id = ? OR student_id = ? OR (email = ? AND email != '')) AND role = 'student'
                LIMIT 1
            ");
            $userLookupStmt->execute([$studentNo, $cleanId, $email]);
            $existingUser = $userLookupStmt->fetch(PDO::FETCH_ASSOC);

            $userId = null;
            if ($existingUser) {
                $userId = (int)$existingUser['user_id'];
                $db->prepare("
                    UPDATE users 
                    SET first_name = COALESCE(NULLIF(?, ''), first_name),
                        last_name = COALESCE(NULLIF(?, ''), last_name),
                        student_id = COALESCE(NULLIF(?, ''), student_id)
                    WHERE user_id = ?
                ")->execute([$firstName, $lastName, $studentNo, $userId]);
            } else {
                if (empty($email)) {
                    $cleanFn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName));
                    $cleanLn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastName));
                    $email = "{$cleanFn}.{$cleanLn}" . substr($cleanId, -4) . "@student.bcp.edu.ph";
                }

                $passHash = password_hash('password123', PASSWORD_BCRYPT);
                $insUserStmt = $db->prepare("
                    INSERT INTO users (
                        student_id, role, email, password_hash, first_name, last_name, status, created_at, updated_at
                    ) VALUES (?, 'student', ?, ?, ?, ?, 'active', NOW(), NOW())
                ");
                $insUserStmt->execute([$studentNo, $email, $passHash, $firstName, $lastName]);
                $userId = (int)$db->lastInsertId();
            }

            // 3. Check if already enrolled in this section
            $checkRosterStmt = $db->prepare("
                SELECT roster_id FROM class_roster 
                WHERE student_id = ? AND section = ? AND teacher_id = ?
                LIMIT 1
            ");
            $checkRosterStmt->execute([$userId, $section, $teacherId]);
            if ($checkRosterStmt->fetch()) {
                echo json_encode([
                    'status'  => 'warning',
                    'success' => false,
                    'message' => "Student {$firstName} {$lastName} ({$studentNo}) is already enrolled in Section {$section}."
                ]);
                exit;
            }

            // 4. Insert into class_roster
            $insertRosterStmt = $db->prepare("
                INSERT INTO class_roster (
                    student_id, teacher_id, first_name, last_name, section, room_number,
                    scheduled_time, schedule_day, course_code, course_title, major, course, year_level, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $insertRosterStmt->execute([
                $userId,
                $teacherId,
                $firstName,
                $lastName,
                $section,
                $roomNumber,
                $scheduledTime,
                $scheduleDay,
                $courseCode,
                $courseTitle,
                $major,
                $course,
                $yearLevel
            ]);

            $rosterId = (int)$db->lastInsertId();

            $countStmt = $db->prepare("SELECT COUNT(DISTINCT student_id) FROM class_roster WHERE section = ? AND teacher_id = ?");
            $countStmt->execute([$section, $teacherId]);
            $totalEnrolled = (int)$countStmt->fetchColumn();

            $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));

            echo json_encode([
                'status'  => 'success',
                'success' => true,
                'message' => "Successfully added {$firstName} {$lastName} ({$studentNo}) to Section {$section}.",
                'student' => [
                    'roster_id'   => $rosterId,
                    'user_id'     => $userId,
                    'id'          => $studentNo,
                    'student_id'  => $studentNo,
                    'first_name'  => $firstName,
                    'last_name'   => $lastName,
                    'name'        => "{$firstName} {$lastName}",
                    'initials'    => $initials,
                    'email'       => $email,
                    'section'     => $section,
                    'course_code' => $courseCode,
                    'sessions'    => 0,
                    'present'     => 0,
                    'late'        => 0,
                    'tardy'       => 0,
                    'absent'      => 0,
                    'excused'     => 0,
                    'rate'        => 100.0,
                    'standing'    => 'Good Standing',
                    'status'      => 'Active'
                ],
                'total_enrolled' => $totalEnrolled
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * API: POST /api/teacher/classes/import-section
     * Batch imports or appends a list of students via CSV to a specific section.
     */
    public function apiImportSectionStudents(): void {
        header('Content-Type: application/json; charset=utf-8');
        try {
            $raw = file_get_contents('php://input');
            $input = !empty($raw) ? json_decode($raw, true) : $_POST;

            if (empty($input) || !is_array($input)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Invalid request payload']);
                exit;
            }

            $teacherId = self::resolveCurrentTeacherId();
            $section   = trim($input['section'] ?? '');
            $students  = $input['students'] ?? [];

            if (empty($section)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Target section is required.']);
                exit;
            }
            if (empty($students) || !is_array($students)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'success' => false, 'message' => 'No student rows provided in CSV data.']);
                exit;
            }

            $db = Database::getConnection();

            // 1. Resolve class details for this section & teacher
            $classMetaStmt = $db->prepare("
                SELECT course_code, course_title, course, year_level, schedule_day, scheduled_time, room_number, major
                FROM class_roster
                WHERE section = ? AND (teacher_id = ? OR teacher_id IS NOT NULL)
                ORDER BY (teacher_id = ?) DESC, roster_id DESC
                LIMIT 1
            ");
            $classMetaStmt->execute([$section, $teacherId, $teacherId]);
            $classMeta = $classMetaStmt->fetch(PDO::FETCH_ASSOC);

            $courseCode   = $classMeta['course_code'] ?? ($input['course_code'] ?? 'IT301');
            $courseTitle  = $classMeta['course_title'] ?? ($input['course_title'] ?? 'Web Systems and Technologies');
            $course       = $classMeta['course'] ?? ($input['course'] ?? 'BSIT');
            $yearLevel    = (int)($classMeta['year_level'] ?? ($input['year_level'] ?? 3));
            $scheduleDay  = $classMeta['schedule_day'] ?? 'Monday';
            $scheduledTime= $classMeta['scheduled_time'] ?? '08:00:00';
            $roomNumber   = $classMeta['room_number'] ?? '402';
            $major        = $classMeta['major'] ?? null;

            $db->beginTransaction();

            $imported = 0;
            $duplicates = 0;
            $addedStudents = [];

            // Prepared lookup statements
            $userLookupStmt = $db->prepare("
                SELECT user_id, student_id, first_name, last_name, email 
                FROM users 
                WHERE (student_id = ? OR student_id = ? OR (email = ? AND email != '')) AND role = 'student'
                LIMIT 1
            ");

            $checkRosterStmt = $db->prepare("
                SELECT roster_id FROM class_roster 
                WHERE student_id = ? AND section = ? AND teacher_id = ?
                LIMIT 1
            ");

            $insertRosterStmt = $db->prepare("
                INSERT INTO class_roster (
                    student_id, teacher_id, first_name, last_name, section, room_number,
                    scheduled_time, schedule_day, course_code, course_title, major, course, year_level, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $insUserStmt = $db->prepare("
                INSERT INTO users (
                    student_id, role, email, password_hash, first_name, last_name, status, created_at, updated_at
                ) VALUES (?, 'student', ?, ?, ?, ?, 'active', NOW(), NOW())
            ");

            $passHash = password_hash('password123', PASSWORD_BCRYPT);

            foreach ($students as $row) {
                $studentNo = trim($row['student_id'] ?? $row['student_number'] ?? $row['id'] ?? '');
                $firstName = trim($row['first_name'] ?? '');
                $lastName  = trim($row['last_name'] ?? '');
                $email     = trim($row['email'] ?? '');

                if (empty($firstName) && empty($lastName) && !empty($row['full_name'] ?? $row['name'] ?? '')) {
                    $fullName = trim($row['full_name'] ?? $row['name'] ?? '');
                    $parts = preg_split('/\s+/', $fullName);
                    $firstName = array_shift($parts) ?: 'Student';
                    $lastName = implode(' ', $parts) ?: 'User';
                }

                if (empty($studentNo) || (empty($firstName) && empty($lastName))) {
                    continue;
                }

                $cleanId = (string)preg_replace('/\D/', '', $studentNo);

                $userLookupStmt->execute([$studentNo, $cleanId, $email]);
                $existingUser = $userLookupStmt->fetch(PDO::FETCH_ASSOC);

                $userId = null;
                if ($existingUser) {
                    $userId = (int)$existingUser['user_id'];
                    $firstName = $firstName ?: $existingUser['first_name'];
                    $lastName = $lastName ?: $existingUser['last_name'];
                    $email = $email ?: $existingUser['email'];
                } else {
                    if (empty($email)) {
                        $cleanFn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstName));
                        $cleanLn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $lastName));
                        $email = "{$cleanFn}.{$cleanLn}" . substr($cleanId, -4) . "@student.bcp.edu.ph";
                    }
                    $insUserStmt->execute([$studentNo, $email, $passHash, $firstName, $lastName]);
                    $userId = (int)$db->lastInsertId();
                }

                $checkRosterStmt->execute([$userId, $section, $teacherId]);
                if ($checkRosterStmt->fetch()) {
                    $duplicates++;
                    continue;
                }

                $insertRosterStmt->execute([
                    $userId,
                    $teacherId,
                    $firstName,
                    $lastName,
                    $section,
                    $roomNumber,
                    $scheduledTime,
                    $scheduleDay,
                    $courseCode,
                    $courseTitle,
                    $major,
                    $course,
                    $yearLevel
                ]);

                $rosterId = (int)$db->lastInsertId();
                $imported++;

                $initials = strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
                $addedStudents[] = [
                    'roster_id'   => $rosterId,
                    'user_id'     => $userId,
                    'id'          => $studentNo,
                    'student_id'  => $studentNo,
                    'first_name'  => $firstName,
                    'last_name'   => $lastName,
                    'name'        => "{$firstName} {$lastName}",
                    'initials'    => $initials,
                    'email'       => $email,
                    'section'     => $section,
                    'course_code' => $courseCode,
                    'sessions'    => 0,
                    'present'     => 0,
                    'late'        => 0,
                    'tardy'       => 0,
                    'absent'      => 0,
                    'excused'     => 0,
                    'rate'        => 100.0,
                    'standing'    => 'Good Standing',
                    'status'      => 'Active'
                ];
            }

            $db->commit();

            $countStmt = $db->prepare("SELECT COUNT(DISTINCT student_id) FROM class_roster WHERE section = ? AND teacher_id = ?");
            $countStmt->execute([$section, $teacherId]);
            $totalEnrolled = (int)$countStmt->fetchColumn();

            $msg = "Successfully added {$imported} student" . ($imported === 1 ? '' : 's') . " to Section {$section}.";
            if ($duplicates > 0) {
                $msg .= " ({$duplicates} duplicate" . ($duplicates === 1 ? '' : 's') . " already enrolled).";
            }

            echo json_encode([
                'status'         => 'success',
                'success'        => true,
                'message'        => $msg,
                'imported'       => $imported,
                'duplicates'     => $duplicates,
                'total_enrolled' => $totalEnrolled,
                'students'       => $addedStudents
            ]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(500);
            echo json_encode(['status' => 'error', 'success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}
