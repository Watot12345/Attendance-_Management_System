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

            // Student ID (Juan Dela Cruz default = 1)
            $studentId = !empty($_POST['student_id']) ? (int)$_POST['student_id'] : 1;

            // Map Teacher ID from Subject
            // IT301 / IT302 -> Prof. Manuel Ramirez (ID 2), IT303 -> Prof. Jose Santos (ID 3)
            $teacherId = 2;
            if (stripos($subject, 'IT303') !== false || stripos($subject, 'Santos') !== false) {
                $teacherId = 3;
            }
            if (!empty($_POST['teacher_id'])) {
                $teacherId = (int)$_POST['teacher_id'];
            }

            // 2. Validate required inputs
            $errors = [];
            if (empty($subject)) {
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
            self::ensureUserExists($db, $teacherId, 'teacher');

            // 5. Insert Excuse Slip into database
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

            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                $studentId,
                $teacherId,
                $subject,
                $dateOfAbsence,
                $reason,
                $explanation,
                $supportingDocumentUrl
            ]);

            $newSlipId = (int)$db->lastInsertId();

            // 6. Create Instructor Notification
            try {
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
                $notifStmt->execute([
                    $teacherId,
                    "A new excuse slip was submitted for {$subject} (Absence: {$dateOfAbsence}). Please review and update attendance.",
                    $newSlipId
                ]);
            } catch (Exception $ne) {
                // Non-critical, continue
            }

            // 7. Return success response with newly created record details
            http_response_code(201);
            echo json_encode([
                'status'  => 'success',
                'message' => 'Excuse slip submitted successfully! Your instructor has been notified to review the attached documentation.',
                'data'    => [
                    'excuse_slip_id'      => $newSlipId,
                    'student_id'          => $studentId,
                    'teacher_id'          => $teacherId,
                    'subject'             => $subject,
                    'date_of_absence'     => $dateOfAbsence,
                    'reason'              => $reason,
                    'explanation'         => $explanation,
                    'status'              => 'pending',
                    'supporting_document' => $supportingDocumentUrl,
                    'created_at'          => date('Y-m-d H:i:s'),
                ]
            ]);
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

            // Teacher mapping
            $teacherId = $existing['teacher_id'];
            if (stripos($subject, 'IT303') !== false || stripos($subject, 'Santos') !== false) {
                $teacherId = 3;
            } elseif (stripos($subject, 'IT301') !== false || stripos($subject, 'IT302') !== false) {
                $teacherId = 2;
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

            // Remove storage object from Supabase if present
            if (!empty($slip['supporting_document'])) {
                SupabaseStorage::delete($slip['supporting_document']);
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
