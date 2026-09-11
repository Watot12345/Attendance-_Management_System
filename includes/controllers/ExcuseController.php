<?php
/**
 * Excuse Slip Controller — includes/controllers/ExcuseController.php
 * Handles student excuse slip submissions, Supabase storage uploads,
 * and database operations on the `excuse_slips` table.
 */

require_once dirname(__DIR__) . '/core/Database.php';
require_once dirname(__DIR__) . '/core/SupabaseStorage.php';

class ExcuseController {
    /**
     * POST /api/excuses/submit — Handle Excuse Slip Submission
     */
    public function submit(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            exit;
        }

        try {
            $db = Database::getConnection();

            // 1. Gather and sanitize input fields
            $subject = trim($_POST['subject'] ?? '');
            $dateOfAbsence = trim($_POST['date_of_absence'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            $explanation = trim($_POST['explanation'] ?? '');
            $isSendToAll = !empty($_POST['send_to_all']) && ($_POST['send_to_all'] === '1' || $_POST['send_to_all'] === 'true' || $_POST['send_to_all'] === 'on');

            // Student ID (Juan Dela Cruz default = 1)
            $studentId = !empty($_POST['student_id']) ? (int)$_POST['student_id'] : 1;

            // Check if "All Subject Teachers" is requested via dropdown or flag
            if (!$isSendToAll && (strtoupper($subject) === 'ALL' || stripos($subject, 'All Subject Teachers') !== false || stripos($subject, 'All Enrolled') !== false)) {
                $isSendToAll = true;
            }

            // Query student's enrolled subjects & faculty exclusively from class_roster
            $uStmt = $db->prepare("SELECT user_id, student_id FROM users WHERE user_id = ? OR student_id = ? LIMIT 1");
            $uStmt->execute([$studentId, $studentId]);
            $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
            $studentUid = $uRow ? (int)$uRow['user_id'] : $studentId;
            $studentNum = $uRow && !empty($uRow['student_id']) ? (int)$uRow['student_id'] : $studentUid;

            $rosterStmt = $db->prepare("
                SELECT 
                    cr.course_code,
                    cr.course_title,
                    cr.section,
                    cr.teacher_id,
                    t.first_name,
                    t.last_name,
                    CONCAT(t.first_name, ' ', t.last_name) AS teacher_full_name
                FROM class_roster cr
                LEFT JOIN users t ON cr.teacher_id = t.user_id
                WHERE cr.student_id = :uid OR cr.student_id = :student_num
                GROUP BY cr.course_code, cr.course_title, cr.section, cr.teacher_id, t.first_name, t.last_name
                ORDER BY cr.course_code ASC
            ");
            $rosterStmt->execute([
                ':uid'         => $studentUid,
                ':student_num' => $studentNum,
            ]);
            $rosterRows = $rosterStmt->fetchAll(PDO::FETCH_ASSOC);

            $availableCourses = [];
            foreach ($rosterRows as $rr) {
                $tLastName = !empty($rr['last_name']) ? $rr['last_name'] : (!empty($rr['first_name']) ? $rr['first_name'] : 'Faculty');
                $tName = "Prof. {$tLastName}";
                $title = !empty($rr['course_title']) ? $rr['course_title'] : $rr['course_code'];
                $subjectFormatted = "{$rr['course_code']} — {$title} ({$tName})";

                $availableCourses[] = [
                    'course_code'       => $rr['course_code'],
                    'course_title'      => $title,
                    'section'           => $rr['section'],
                    'subject'           => $subjectFormatted,
                    'teacher_id'        => (int)$rr['teacher_id'],
                    'teacher_name'      => $tName,
                    'teacher_full_name' => $rr['teacher_full_name'] ?: $tName,
                ];
            }

            // Determine target courses
            $targetCourses = [];
            if ($isSendToAll) {
                if (empty($availableCourses)) {
                    http_response_code(400);
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'You are not currently enrolled in any class rosters. Please contact your instructor.'
                    ]);
                    exit;
                }
                $targetCourses = $availableCourses;
            } elseif (!empty($_POST['subjects']) && is_array($_POST['subjects'])) {
                foreach ($_POST['subjects'] as $s) {
                    $s = trim($s);
                    if (empty($s)) continue;
                    
                    // Match against student's class_roster
                    $matched = null;
                    foreach ($availableCourses as $ac) {
                        if ($ac['subject'] === $s || $ac['course_code'] === $s || stripos($s, $ac['course_code']) !== false) {
                            $matched = $ac;
                            break;
                        }
                    }

                    if ($matched) {
                        $targetCourses[] = $matched;
                    } else {
                        $tId = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 2;
                        $targetCourses[] = [
                            'subject'      => $s,
                            'teacher_id'   => $tId,
                            'teacher_name' => 'Instructor'
                        ];
                    }
                }
            } else {
                // Single subject
                $matched = null;
                foreach ($availableCourses as $ac) {
                    if ($ac['subject'] === $subject || $ac['course_code'] === $subject || stripos($subject, $ac['course_code']) !== false) {
                        $matched = $ac;
                        break;
                    }
                }

                if ($matched) {
                    $targetCourses[] = $matched;
                } else {
                    $teacherId = !empty($_POST['teacher_id']) ? (int)$_POST['teacher_id'] : 2;
                    $targetCourses[] = [
                        'subject'      => $subject,
                        'teacher_id'   => $teacherId,
                        'teacher_name' => 'Instructor'
                    ];
                }
            }

            // 2. Validate required inputs
            $errors = [];
            if (empty($targetCourses) || (empty($subject) && !$isSendToAll)) {
                $errors[] = 'Subject / Class is required.';
            }
            if (empty($dateOfAbsence) || !strtotime($dateOfAbsence)) {
                $errors[] = 'A valid date of absence is required.';
            }
            if (empty($reason)) {
                $errors[] = 'Reason category is required.';
            }
            if (empty($explanation)) {
                $errors[] = 'Detailed explanation is required.';
            }

            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode([
                    'status'  => 'error',
                    'message' => implode(' ', $errors),
                    'errors'  => $errors,
                ]);
                exit;
            }

            // 2b. Check for existing pending or approved excuse slips for that date and professor
            $conflictTeachers = [];
            $conflictStatus = 'pending';
            $filteredTargetCourses = [];

            foreach ($targetCourses as $course) {
                $cTeacherId = (int)$course['teacher_id'];
                $cTeacherName = $course['teacher_name'] ?? ($cTeacherId === 3 ? 'Prof. Jose Santos' : 'Prof. Manuel Ramirez');

                $checkStmt = $db->prepare("
                    SELECT excuse_slip_id, subject, status, created_at
                    FROM excuse_slips
                    WHERE student_id = ? 
                      AND teacher_id = ? 
                      AND date_of_absence = ? 
                      AND LOWER(status) IN ('pending', 'approved')
                    ORDER BY FIELD(LOWER(status), 'pending', 'approved')
                    LIMIT 1
                ");
                $checkStmt->execute([$studentId, $cTeacherId, $dateOfAbsence]);
                $existingSlip = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if ($existingSlip) {
                    if (strtolower($existingSlip['status']) === 'approved') {
                        $conflictStatus = 'approved';
                    }
                    $shortName = (stripos($cTeacherName, 'Ramirez') !== false) ? 'Prof. Ramirez' : ((stripos($cTeacherName, 'Santos') !== false) ? 'Prof. Santos' : $cTeacherName);
                    $conflictTeachers[$cTeacherId] = $shortName;
                } else {
                    $filteredTargetCourses[] = $course;
                }
            }

            if (!empty($conflictTeachers)) {
                if (!$isSendToAll || empty($filteredTargetCourses)) {
                    $teacherListStr = implode(' & ', array_values($conflictTeachers));
                    $conflictMsg = "Already {$conflictStatus} for {$teacherListStr}.";
                    http_response_code(409); // Conflict
                    echo json_encode([
                        'status'    => 'error',
                        'code'      => 'ALREADY_PENDING',
                        'message'   => $conflictMsg,
                        'conflicts' => $conflictTeachers,
                    ]);
                    exit;
                }
                // When submitting to all teachers and some already have pending slips, proceed only for the remaining teachers
                $targetCourses = $filteredTargetCourses;
            }

            // 3. Handle Supporting Document Upload to Supabase Storage
            $supportingDocumentUrl = null;
            $fileKey = isset($_FILES['document']) ? 'document' : (isset($_FILES['supporting_document']) ? 'supporting_document' : null);

            if ($fileKey && isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES[$fileKey];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    http_response_code(400);
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'File upload error code: ' . $file['error'],
                    ]);
                    exit;
                }

                // Check file size (limit 10MB)
                if ($file['size'] > 10 * 1024 * 1024) {
                    http_response_code(400);
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'Uploaded file exceeds the maximum allowed size of 10MB.',
                    ]);
                    exit;
                }

                // Upload to Supabase Storage in 'documents' bucket, 'excuses' subfolder
                $uploadResult = SupabaseStorage::upload(
                    $file['tmp_name'],
                    $file['name'],
                    $file['type'],
                    'excuses'
                );

                if (!$uploadResult['success']) {
                    http_response_code(500);
                    echo json_encode([
                        'status'  => 'error',
                        'message' => 'Failed to upload document to Supabase Storage: ' . ($uploadResult['error'] ?? 'Unknown error'),
                        'detail'  => $uploadResult,
                    ]);
                    exit;
                }

                $supportingDocumentUrl = $uploadResult['url'];
            }

            // 4. Ensure foreign key users exist (auto-seed if missing)
            self::ensureUserExists($db, $studentId, 'student');

            // 5. Insert Excuse Slip(s) for each target course / teacher
            $insertSql = "
                INSERT INTO excuse_slips (
                    student_id,
                    teacher_id,
                    subject,
                    date_of_absence,
                    reason,
                    explanation,
                    status,
                    supporting_document,
                    created_at,
                    updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            ";
            $insertStmt = $db->prepare($insertSql);

            $notifStmt = $db->prepare("
                INSERT INTO notifications (
                    user_id,
                    title,
                    message,
                    type,
                    reference_type,
                    reference_id,
                    is_read,
                    created_at
                ) VALUES (?, 'New Excuse Slip Submitted', ?, 'excuse_slip', 'excuse_slips', ?, 0, NOW())
            ");

            $createdSlips = [];
            foreach ($targetCourses as $course) {
                $cSubject = $course['subject'];
                $cTeacherId = (int)$course['teacher_id'];
                $cTeacherName = $course['teacher_name'] ?? ($cTeacherId === 3 ? 'Prof. Jose Santos' : 'Prof. Manuel Ramirez');

                self::ensureUserExists($db, $cTeacherId, 'teacher');

                $insertStmt->execute([
                    $studentId,
                    $cTeacherId,
                    $cSubject,
                    $dateOfAbsence,
                    $reason,
                    $explanation,
                    $supportingDocumentUrl
                ]);

                $newSlipId = (int)$db->lastInsertId();

                // Notification for this instructor
                try {
                    $notifStmt->execute([
                        $cTeacherId,
                        "A new excuse slip was submitted for {$cSubject} (Absence: {$dateOfAbsence}). Please review and update attendance.",
                        $newSlipId
                    ]);
                } catch (Exception $ne) {
                    // Non-critical, continue
                }

                $createdSlips[] = [
                    'excuse_slip_id'      => $newSlipId,
                    'student_id'          => $studentId,
                    'teacher_id'          => $cTeacherId,
                    'teacher_name'        => $cTeacherName,
                    'subject'             => $cSubject,
                    'date_of_absence'     => $dateOfAbsence,
                    'reason'              => $reason,
                    'explanation'         => $explanation,
                    'status'              => 'pending',
                    'supporting_document' => $supportingDocumentUrl,
                    'created_at'          => date('Y-m-d H:i:s'),
                ];
            }

            // 6. Return success response
            http_response_code(201);
            $profNames = array_unique(array_filter(array_column($createdSlips, 'teacher_name')));
            $profStr = !empty($profNames) ? implode(' & ', $profNames) : 'instructors';

            if (count($createdSlips) > 1) {
                echo json_encode([
                    'status'  => 'success',
                    'count'   => count($createdSlips),
                    'message' => 'Excuse slip successfully submitted to all subject teachers (' . count($createdSlips) . ' classes: ' . $profStr . ').',
                    'data'    => $createdSlips
                ]);
            } else {
                echo json_encode([
                    'status'  => 'success',
                    'count'   => 1,
                    'message' => 'Excuse slip submitted successfully! Your instructor (' . $profStr . ') has been notified to review the attached documentation.',
                    'data'    => $createdSlips[0]
                ]);
            }
            exit;

        } catch (PDOException $pe) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Database error: ' . $pe->getMessage(),
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ]);
            exit;
        }
    }

    /**
     * GET /api/excuses/list — Fetch Excuse Slips for Student
     */
    public function listStudent(): void {
        header('Content-Type: application/json');

        try {
            $db = Database::getConnection();
            $studentId = !empty($_GET['student_id']) ? (int)$_GET['student_id'] : 1;

            $stmt = $db->prepare("
                SELECT 
                    es.excuse_slip_id,
                    es.student_id,
                    es.teacher_id,
                    es.subject,
                    es.date_of_absence,
                    es.reason,
                    es.explanation,
                    es.status,
                    es.declined_reason,
                    es.supporting_document,
                    es.created_at,
                    CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
                FROM excuse_slips es
                LEFT JOIN users t ON es.teacher_id = t.user_id
                WHERE es.student_id = ?
                ORDER BY es.created_at DESC, es.excuse_slip_id DESC
            ");
            $stmt->execute([$studentId]);
            $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status' => 'success',
                'count'  => count($slips),
                'data'   => $slips,
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * POST /api/excuses/update — Update an existing excuse slip
     */
    public function update(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            exit;
        }

        try {
            $db = Database::getConnection();

            $slipId = !empty($_POST['excuse_slip_id']) ? (int)$_POST['excuse_slip_id'] : 0;
            $studentId = !empty($_POST['student_id']) ? (int)$_POST['student_id'] : 1;

            if ($slipId <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Valid Excuse Slip ID is required.']);
                exit;
            }

            // Check if slip exists and belongs to student
            $stmt = $db->prepare("SELECT * FROM excuse_slips WHERE excuse_slip_id = ? AND student_id = ?");
            $stmt->execute([$slipId, $studentId]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Excuse slip not found or permission denied.']);
                exit;
            }

            if ($existing['status'] === 'approved') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Approved excuse slips cannot be modified.']);
                exit;
            }

            $subject = trim($_POST['subject'] ?? $existing['subject']);
            $dateOfAbsence = trim($_POST['date_of_absence'] ?? $existing['date_of_absence']);
            $reason = trim($_POST['reason'] ?? $existing['reason']);
            $explanation = trim($_POST['explanation'] ?? $existing['explanation']);

            // Teacher mapping based on class_roster
            $teacherId = $existing['teacher_id'];
            $uStmt = $db->prepare("SELECT user_id, student_id FROM users WHERE user_id = ? OR student_id = ? LIMIT 1");
            $uStmt->execute([$studentId, $studentId]);
            $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
            $studentUid = $uRow ? (int)$uRow['user_id'] : $studentId;
            $studentNum = $uRow && !empty($uRow['student_id']) ? (int)$uRow['student_id'] : $studentUid;

            $rMatchStmt = $db->prepare("
                SELECT teacher_id FROM class_roster 
                WHERE (student_id = ? OR student_id = ?) AND (course_code = ? OR ? LIKE CONCAT('%', course_code, '%'))
                LIMIT 1
            ");
            $rMatchStmt->execute([$studentUid, $studentNum, $subject, $subject]);
            $matchedTeacherId = $rMatchStmt->fetchColumn();
            if ($matchedTeacherId) {
                $teacherId = (int)$matchedTeacherId;
            } elseif (!empty($_POST['teacher_id'])) {
                $teacherId = (int)$_POST['teacher_id'];
            }

            // Handle optional replacement document upload
            $newDocumentUrl = $existing['supporting_document'];
            $fileKey = isset($_FILES['document']) ? 'document' : (isset($_FILES['supporting_document']) ? 'supporting_document' : null);

            if ($fileKey && isset($_FILES[$fileKey]) && $_FILES[$fileKey]['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES[$fileKey];
                if ($file['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = SupabaseStorage::upload($file['tmp_name'], $file['name'], $file['type'], 'excuses');
                    if ($uploadResult['success']) {
                        if (!empty($existing['supporting_document'])) {
                            SupabaseStorage::delete($existing['supporting_document']);
                        }
                        $newDocumentUrl = $uploadResult['url'];
                    } else {
                        http_response_code(500);
                        echo json_encode([
                            'status'  => 'error',
                            'message' => 'Failed to upload replacement document: ' . ($uploadResult['error'] ?? 'Unknown error')
                        ]);
                        exit;
                    }
                }
            }

            $updateStmt = $db->prepare("
                UPDATE excuse_slips SET
                    subject = ?,
                    teacher_id = ?,
                    date_of_absence = ?,
                    reason = ?,
                    explanation = ?,
                    supporting_document = ?,
                    updated_at = NOW()
                WHERE excuse_slip_id = ? AND student_id = ?
            ");
            $updateStmt->execute([
                $subject,
                $teacherId,
                $dateOfAbsence,
                $reason,
                $explanation,
                $newDocumentUrl,
                $slipId,
                $studentId
            ]);

            echo json_encode([
                'status'  => 'success',
                'message' => 'Excuse slip updated successfully.',
                'data'    => [
                    'excuse_slip_id'      => $slipId,
                    'student_id'          => $studentId,
                    'teacher_id'          => $teacherId,
                    'subject'             => $subject,
                    'date_of_absence'     => $dateOfAbsence,
                    'reason'              => $reason,
                    'explanation'         => $explanation,
                    'status'              => $existing['status'],
                    'supporting_document' => $newDocumentUrl,
                    'updated_at'          => date('Y-m-d H:i:s'),
                ]
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * POST /api/excuses/delete — Delete an existing excuse slip
     */
    public function delete(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            exit;
        }

        try {
            $db = Database::getConnection();

            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $slipId = !empty($_POST['excuse_slip_id']) ? (int)$_POST['excuse_slip_id'] : (!empty($rawInput['excuse_slip_id']) ? (int)$rawInput['excuse_slip_id'] : 0);
            $studentId = !empty($_POST['student_id']) ? (int)$_POST['student_id'] : (!empty($rawInput['student_id']) ? (int)$rawInput['student_id'] : 1);

            if ($slipId <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Valid Excuse Slip ID is required.']);
                exit;
            }

            // Check slip
            $stmt = $db->prepare("SELECT * FROM excuse_slips WHERE excuse_slip_id = ? AND student_id = ?");
            $stmt->execute([$slipId, $studentId]);
            $slip = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$slip) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Excuse slip not found or already deleted.']);
                exit;
            }

            // Remove storage object from Supabase if present and not shared by other slips
            if (!empty($slip['supporting_document'])) {
                $otherCheck = $db->prepare("SELECT COUNT(*) FROM excuse_slips WHERE supporting_document = ? AND excuse_slip_id != ?");
                $otherCheck->execute([$slip['supporting_document'], $slipId]);
                $otherCount = (int)$otherCheck->fetchColumn();
                if ($otherCount === 0) {
                    SupabaseStorage::delete($slip['supporting_document']);
                }
            }

            // Remove notifications
            try {
                $delNotif = $db->prepare("DELETE FROM notifications WHERE reference_type = 'excuse_slips' AND reference_id = ?");
                $delNotif->execute([$slipId]);
            } catch (Exception $ne) {}

            // Delete slip
            $delStmt = $db->prepare("DELETE FROM excuse_slips WHERE excuse_slip_id = ? AND student_id = ?");
            $delStmt->execute([$slipId, $studentId]);

            echo json_encode([
                'status'  => 'success',
                'message' => "Excuse slip #{$slipId} deleted successfully.",
                'slip_id' => $slipId,
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * POST /api/excuses/bulk-delete — Delete multiple excuse slips simultaneously
     */
    public function bulkDelete(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            exit;
        }

        try {
            $db = Database::getConnection();

            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $slipIds = !empty($_POST['excuse_slip_ids']) 
                ? (is_array($_POST['excuse_slip_ids']) ? $_POST['excuse_slip_ids'] : explode(',', (string)$_POST['excuse_slip_ids']))
                : (!empty($rawInput['excuse_slip_ids']) ? $rawInput['excuse_slip_ids'] : []);
            
            // Clean integer IDs
            $cleanIds = [];
            foreach ($slipIds as $id) {
                $num = (int)$id;
                if ($num > 0) {
                    $cleanIds[] = $num;
                }
            }
            $cleanIds = array_values(array_unique($cleanIds));

            if (empty($cleanIds)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Please select at least one valid excuse slip to delete.']);
                exit;
            }

            // Resolve student ID
            $studentId = !empty($_SESSION['user']['user_id']) 
                ? (int)$_SESSION['user']['user_id'] 
                : (!empty($_POST['student_id']) ? (int)$_POST['student_id'] : (!empty($rawInput['student_id']) ? (int)$rawInput['student_id'] : 1));

            $uStmt = $db->prepare("SELECT user_id, student_id FROM users WHERE user_id = ? OR student_id = ? LIMIT 1");
            $uStmt->execute([$studentId, $studentId]);
            $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
            $studentUid = $uRow ? (int)$uRow['user_id'] : $studentId;
            $studentNum = $uRow && !empty($uRow['student_id']) ? (int)$uRow['student_id'] : $studentUid;

            // Find all matching slips owned by this student
            $inPlaceholders = implode(',', array_fill(0, count($cleanIds), '?'));
            $params = array_merge($cleanIds, [$studentUid, $studentNum]);

            $stmt = $db->prepare("
                SELECT excuse_slip_id, supporting_document 
                FROM excuse_slips 
                WHERE excuse_slip_id IN ($inPlaceholders) AND (student_id = ? OR student_id = ?)
            ");
            $stmt->execute($params);
            $slipsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($slipsToDelete)) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'No matching excuse slips found for your account.']);
                exit;
            }

            $matchedIds = array_map('intval', array_column($slipsToDelete, 'excuse_slip_id'));
            $matchedPlaceholders = implode(',', array_fill(0, count($matchedIds), '?'));

            // Delete storage documents from Supabase if not shared
            foreach ($slipsToDelete as $slip) {
                if (!empty($slip['supporting_document'])) {
                    $doc = $slip['supporting_document'];
                    $otherCheck = $db->prepare("
                        SELECT COUNT(*) FROM excuse_slips 
                        WHERE supporting_document = ? AND excuse_slip_id NOT IN ($matchedPlaceholders)
                    ");
                    $otherCheck->execute(array_merge([$doc], $matchedIds));
                    $otherCount = (int)$otherCheck->fetchColumn();
                    if ($otherCount === 0) {
                        try {
                            SupabaseStorage::delete($doc);
                        } catch (Exception $se) {}
                    }
                }
            }

            // Remove notifications
            try {
                $delNotifs = $db->prepare("
                    DELETE FROM notifications 
                    WHERE reference_type = 'excuse_slips' AND reference_id IN ($matchedPlaceholders)
                ");
                $delNotifs->execute($matchedIds);
            } catch (Exception $ne) {}

            // Delete excuse slips
            $deleteParams = array_merge($matchedIds, [$studentUid, $studentNum]);
            $delStmt = $db->prepare("
                DELETE FROM excuse_slips 
                WHERE excuse_slip_id IN ($matchedPlaceholders) AND (student_id = ? OR student_id = ?)
            ");
            $delStmt->execute($deleteParams);
            $deletedCount = count($matchedIds);

            echo json_encode([
                'status'        => 'success',
                'message'       => "Successfully deleted {$deletedCount} excuse slip" . ($deletedCount === 1 ? '' : 's') . ".",
                'deleted_count' => $deletedCount,
                'deleted_ids'   => $matchedIds,
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * POST /api/excuses/review — Approve or Decline an excuse slip (Teacher/Faculty)
     */
    public function review(): void {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
            exit;
        }

        try {
            $db = Database::getConnection();

            $rawInput = json_decode(file_get_contents('php://input'), true) ?? [];
            $slipId = !empty($_POST['excuse_slip_id']) ? (int)$_POST['excuse_slip_id'] : (!empty($rawInput['excuse_slip_id']) ? (int)$rawInput['excuse_slip_id'] : 0);
            $action = trim($_POST['action'] ?? ($rawInput['action'] ?? ''));
            $notes = trim($_POST['notes'] ?? ($rawInput['notes'] ?? ''));

            if ($slipId <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Valid Excuse Slip ID is required.']);
                exit;
            }

            if (!in_array($action, ['approve', 'reject', 'decline'])) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Action must be either "approve" or "reject".']);
                exit;
            }

            // Fetch slip
            $stmt = $db->prepare("SELECT * FROM excuse_slips WHERE excuse_slip_id = ?");
            $stmt->execute([$slipId]);
            $slip = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$slip) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Excuse slip not found.']);
                exit;
            }

            $newStatus = ($action === 'approve') ? 'approved' : 'declined';
            $declinedReason = ($newStatus === 'declined') ? ($notes ?: 'Declined by instructor during review.') : null;

            $update = $db->prepare("
                UPDATE excuse_slips 
                SET status = ?, 
                    declined_reason = ?, 
                    updated_at = NOW() 
                WHERE excuse_slip_id = ?
            ");
            $update->execute([$newStatus, $declinedReason, $slipId]);

            // Notify student
            try {
                $notifTitle = ($newStatus === 'approved') ? 'Excuse Slip Approved' : 'Excuse Slip Declined';
                $notifMsg = ($newStatus === 'approved')
                    ? "Your absence request for {$slip['subject']} on {$slip['date_of_absence']} was approved by your instructor."
                    : "Your absence request for {$slip['subject']} was declined. Reason: {$declinedReason}";

                $notifStmt = $db->prepare("
                    INSERT INTO notifications (
                        user_id, title, message, type, reference_type, reference_id, is_read, created_at
                    ) VALUES (?, ?, ?, 'excuse_slip', 'excuse_slips', ?, 0, NOW())
                ");
                $notifStmt->execute([$slip['student_id'], $notifTitle, $notifMsg, $slipId]);
            } catch (Exception $ne) {}

            echo json_encode([
                'status'  => 'success',
                'message' => "Excuse slip #{$slipId} has been " . ($newStatus === 'approved' ? 'approved' : 'declined') . ".",
                'data'    => [
                    'excuse_slip_id'  => $slipId,
                    'status'          => $newStatus,
                    'declined_reason' => $declinedReason,
                    'updated_at'      => date('Y-m-d H:i:s')
                ]
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
            exit;
        }
    }

    /**
     * Ensure a user row exists to prevent foreign key errors
     */
    private static function ensureUserExists(PDO $db, int $userId, string $role): void {
        $check = $db->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $check->execute([$userId]);
        if (!$check->fetch()) {
            if ($role === 'student') {
                $stmt = $db->prepare("
                    INSERT INTO users (user_id, student_id, role, email, password_hash, first_name, last_name, status, created_at)
                    VALUES (?, 202600123, 'student', 'student@bcp.edu.ph', 'hash', 'Juan', 'Dela Cruz', 'active', NOW())
                ");
            } else {
                $stmt = $db->prepare("
                    INSERT INTO users (user_id, employee_id, role, email, password_hash, first_name, last_name, status, created_at)
                    VALUES (?, 'EMP-1000', 'teacher', 'teacher@bcp.edu.ph', 'hash', 'Faculty', 'Instructor', 'active', NOW())
                ");
            }
            $stmt->execute([$userId]);
        }
    }
}
