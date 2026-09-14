<?php
/**
 * TeacherController — includes/controllers/TeacherController.php
 * Handles Faculty Management:
 * 1. Bulk import teacher/faculty accounts from CSV/Excel with validation & audit logs.
 * 2. Teachers Master Accounts CRUD (search, filter, create, edit, soft-delete, password reset).
 */

require_once dirname(__DIR__) . '/core/Database.php';

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
            $password   = $input['password'] ?? 'Teacher@123';

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

            $passHash = password_hash($password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("
                INSERT INTO `teachers` 
                (`employee_id`, `full_name`, `email`, `password_hash`, `department`, `position`, `contact_number`, `date_hired`, `status`, `created_at`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$employeeId, $fullName, $email, $passHash, $department, $position, $contact, $dateHired, $status]);
            $newId = (int)$db->lastInsertId();

            echo json_encode([
                'status'  => 'success',
                'message' => "Faculty account for {$fullName} ({$employeeId}) created successfully.",
                'data'    => [
                    'id'          => $newId,
                    'employee_id' => $employeeId,
                    'full_name'   => $fullName,
                    'email'       => $email,
                    'department'  => $department,
                    'position'    => $position,
                    'status'      => $status
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
                    VALUES (?, 'security', ?, ?, 0, NOW())
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

                try {
                    $insertStmt->execute([
                        $empId,
                        $name,
                        $email,
                        $defaultHash,
                        $dept ?: 'General Academics',
                        $pos ?: 'Faculty',
                        $contact,
                        $parsedDate
                    ]);

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
}
