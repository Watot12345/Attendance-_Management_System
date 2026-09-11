<?php
/**
 * Attendance & QR Session Controller — includes/controllers/AttendanceController.php
 * Handles dynamic 6-digit QR session generation, 30-minute expiry, database persistence in `qr_sessions`,
 * and real-time live attendance feed from the `attendance` table.
 */

require_once dirname(__DIR__) . '/core/Database.php';

class AttendanceController {

    /**
     * Resolves the current logged-in teacher ID (with fallback to first teacher in DB for testing).
     */
    private function resolveTeacherId(PDO $db): int {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        if (!empty($_SESSION['teacher_id'])) {
            return (int)$_SESSION['teacher_id'];
        }
        if (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
            return (int)$_SESSION['user_id'];
        }

        // Fallback: pick teacher from database
        $row = $db->query("SELECT user_id FROM users WHERE role = 'teacher' ORDER BY user_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        return $row ? (int)$row['user_id'] : 2;
    }

    /**
     * POST /api/teacher/qr-session/generate
     * Generates a 6-digit QR code, creates a 30-minute session in `qr_sessions`, and returns session data.
     */
    public function generateQrSession(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            // Generate a random 6-digit numeric QR code
            $codeNum = mt_rand(100000, 999999);
            $qrCode = (string)$codeNum;

            // Make sure qr_code is unique
            for ($i = 0; $i < 5; $i++) {
                $check = $db->prepare("SELECT qr_session_id FROM qr_sessions WHERE qr_code = ?");
                $check->execute([$qrCode]);
                if (!$check->fetch()) {
                    break;
                }
                $qrCode = (string)mt_rand(100000, 999999);
            }

            // Close any existing active sessions for this teacher
            $closeOld = $db->prepare("UPDATE qr_sessions SET end = NOW() WHERE teacher_id = ? AND end > NOW()");
            $closeOld->execute([$teacherId]);

            // Insert new 30-minute QR session
            $stmt = $db->prepare("
                INSERT INTO qr_sessions (teacher_id, qr_code, start, `end`, created_at)
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())
            ");
            $stmt->execute([$teacherId, $qrCode]);
            $sessionId = (int)$db->lastInsertId();

            // Fetch created session details
            $sStmt = $db->prepare("
                SELECT qr_session_id, teacher_id, qr_code, start, `end`, created_at,
                       TIMESTAMPDIFF(SECOND, NOW(), `end`) AS expires_in_seconds
                FROM qr_sessions
                WHERE qr_session_id = ?
            ");
            $sStmt->execute([$sessionId]);
            $session = $sStmt->fetch(PDO::FETCH_ASSOC);

            // Get class/roster info for this teacher
            $rStmt = $db->prepare("
                SELECT course_code, course_title, section, room_number
                FROM class_roster
                WHERE teacher_id = ?
                LIMIT 1
            ");
            $rStmt->execute([$teacherId]);
            $roster = $rStmt->fetch(PDO::FETCH_ASSOC) ?: [
                'course_code'  => 'IT301',
                'course_title' => 'Web Systems and Technologies',
                'section'      => 'BSIT 3-1',
                'room_number'  => '402'
            ];

            echo json_encode([
                'status'  => 'success',
                'message' => 'Dynamic 6-digit QR session created for 30 minutes',
                'session' => [
                    'qr_session_id'      => (int)$session['qr_session_id'],
                    'qr_code'            => $session['qr_code'],
                    'start'              => $session['start'],
                    'end'                => $session['end'],
                    'expires_in_seconds' => max(0, (int)$session['expires_in_seconds']),
                    'course_code'        => $roster['course_code'],
                    'course_title'       => $roster['course_title'],
                    'section'            => $roster['section'],
                    'room_number'        => $roster['room_number']
                ]
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to generate QR session: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * GET /api/teacher/qr-session/active
     * Fetches current active QR session for the logged-in teacher (if within 30 minutes).
     */
    public function getActiveQrSession(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            $stmt = $db->prepare("
                SELECT qr_session_id, teacher_id, qr_code, start, `end`, created_at,
                       TIMESTAMPDIFF(SECOND, NOW(), `end`) AS expires_in_seconds
                FROM qr_sessions
                WHERE teacher_id = ? AND `end` > NOW()
                ORDER BY qr_session_id DESC
                LIMIT 1
            ");
            $stmt->execute([$teacherId]);
            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($session && (int)$session['expires_in_seconds'] > 0) {
                // Get class info
                $rStmt = $db->prepare("
                    SELECT course_code, course_title, section, room_number
                    FROM class_roster
                    WHERE teacher_id = ?
                    LIMIT 1
                ");
                $rStmt->execute([$teacherId]);
                $roster = $rStmt->fetch(PDO::FETCH_ASSOC) ?: [
                    'course_code'  => 'IT301',
                    'course_title' => 'Web Systems and Technologies',
                    'section'      => 'BSIT 3-1',
                    'room_number'  => '402'
                ];

                echo json_encode([
                    'status'             => 'success',
                    'has_active_session' => true,
                    'session'            => [
                        'qr_session_id'      => (int)$session['qr_session_id'],
                        'qr_code'            => $session['qr_code'],
                        'start'              => $session['start'],
                        'end'                => $session['end'],
                        'expires_in_seconds' => (int)$session['expires_in_seconds'],
                        'course_code'        => $roster['course_code'],
                        'course_title'       => $roster['course_title'],
                        'section'            => $roster['section'],
                        'room_number'        => $roster['room_number']
                    ]
                ]);
            } else {
                echo json_encode([
                    'status'             => 'success',
                    'has_active_session' => false,
                    'session'            => null
                ]);
            }
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to retrieve active session: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * POST /api/teacher/qr-session/close
     * Closes the active QR session immediately and marks unrecorded students as absent.
     */
    public function closeQrSession(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            $raw = file_get_contents('php://input');
            $input = !empty($raw) ? json_decode($raw, true) : $_POST;
            $sessionId = !empty($input['qr_session_id']) ? (int)$input['qr_session_id'] : null;

            // Close session(s)
            if ($sessionId) {
                $closeStmt = $db->prepare("UPDATE qr_sessions SET `end` = NOW() WHERE qr_session_id = ? AND teacher_id = ?");
                $closeStmt->execute([$sessionId, $teacherId]);
            } else {
                $closeStmt = $db->prepare("UPDATE qr_sessions SET `end` = NOW() WHERE teacher_id = ? AND `end` > NOW()");
                $closeStmt->execute([$teacherId]);
            }

            // Find enrolled students who do not have an attendance record for today
            $rosterStmt = $db->prepare("
                SELECT cr.student_id, cr.course_title
                FROM class_roster cr
                WHERE cr.teacher_id = ?
            ");
            $rosterStmt->execute([$teacherId]);
            $enrolled = $rosterStmt->fetchAll(PDO::FETCH_ASSOC);

            $markedAbsentCount = 0;
            $today = date('Y-m-d');
            $nowTime = date('H:i:s');

            foreach ($enrolled as $enr) {
                $stId = (int)$enr['student_id'];
                $subject = $enr['course_title'] ?: 'Web Systems and Technologies';

                // Check if already has record for today
                $chk = $db->prepare("SELECT attendance_id FROM attendance WHERE student_id = ? AND teacher_id = ? AND `date` = ?");
                $chk->execute([$stId, $teacherId, $today]);
                if (!$chk->fetch()) {
                    // Mark Absent
                    $ins = $db->prepare("
                        INSERT INTO attendance (student_id, teacher_id, qr_session_id, `date`, `time`, subject, status, schedule_date, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, 'absent', ?, NOW())
                    ");
                    $ins->execute([$stId, $teacherId, $sessionId, $today, $nowTime, $subject, $today]);
                    $markedAbsentCount++;
                }
            }

            echo json_encode([
                'status'              => 'success',
                'message'             => "Session closed. $markedAbsentCount unrecorded students marked Absent.",
                'marked_absent_count' => $markedAbsentCount
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to close session: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * GET /api/teacher/attendance/live-feed
     * Returns real database check-ins from `attendance` table and live counts.
     */
    public function getLiveAttendanceFeed(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);
            $today = date('Y-m-d');

            // 1. Check if teacher currently has an active QR session
            $reqSessionId = !empty($_GET['session_id']) ? (int)$_GET['session_id'] : null;
            $activeSession = null;

            if ($reqSessionId) {
                $sessStmt = $db->prepare("SELECT qr_session_id, qr_code, `end` > NOW() AS is_active FROM qr_sessions WHERE qr_session_id = ? AND teacher_id = ?");
                $sessStmt->execute([$reqSessionId, $teacherId]);
                $activeSession = $sessStmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $sessStmt = $db->prepare("SELECT qr_session_id, qr_code, 1 AS is_active FROM qr_sessions WHERE teacher_id = ? AND `end` > NOW() ORDER BY qr_session_id DESC LIMIT 1");
                $sessStmt->execute([$teacherId]);
                $activeSession = $sessStmt->fetch(PDO::FETCH_ASSOC);
            }

            $hasActiveSession = $activeSession && !empty($activeSession['is_active']);
            $currentSessionId = $activeSession ? (int)$activeSession['qr_session_id'] : null;

            // 2. Fetch all check-in records for today (for modal and caching)
            $feedStmt = $db->prepare("
                SELECT 
                    a.attendance_id,
                    a.student_id,
                    a.teacher_id,
                    a.qr_session_id,
                    a.date,
                    a.time,
                    a.subject,
                    a.status,
                    u.first_name,
                    u.last_name,
                    COALESCE(u.student_id, '2026-00000') AS student_number,
                    u.email,
                    u.avatar_path
                FROM attendance a
                JOIN users u ON u.user_id = a.student_id
                WHERE a.teacher_id = ? AND a.date = ?
                ORDER BY a.time DESC, a.attendance_id DESC
            ");
            $feedStmt->execute([$teacherId, $today]);
            $allRows = $feedStmt->fetchAll(PDO::FETCH_ASSOC);

            // Format all check-in rows
            $allFormatted = [];
            $activeFormatted = [];

            foreach ($allRows as $row) {
                $fullName = trim("{$row['first_name']} {$row['last_name']}");
                $timeFormatted = date('h:i:s A', strtotime($row['time']));
                $initials = '';
                $parts = explode(' ', $fullName);
                foreach ($parts as $p) {
                    if (!empty($p)) $initials .= strtoupper($p[0]);
                }
                $initials = substr($initials, 0, 2) ?: 'ST';

                $item = [
                    'attendance_id'  => (int)$row['attendance_id'],
                    'student_id'     => (int)$row['student_id'],
                    'qr_session_id'  => $row['qr_session_id'] ? (int)$row['qr_session_id'] : null,
                    'student_name'   => $fullName,
                    'student_number' => (string)$row['student_number'],
                    'initials'       => $initials,
                    'time'           => $timeFormatted,
                    'subject'        => $row['subject'],
                    'status'         => $row['status'],
                    'avatar_path'    => $row['avatar_path']
                ];

                $allFormatted[] = $item;

                // Only include in active feed if it belongs to current active session
                if ($hasActiveSession && $currentSessionId && (int)$row['qr_session_id'] === $currentSessionId) {
                    $activeFormatted[] = $item;
                }
            }

            // 3. Count total enrolled students in class_roster for this teacher
            $enrolledStmt = $db->prepare("
                SELECT COUNT(DISTINCT student_id) AS total_enrolled
                FROM class_roster
                WHERE teacher_id = ?
            ");
            $enrolledStmt->execute([$teacherId]);
            $enrolledRow = $enrolledStmt->fetch(PDO::FETCH_ASSOC);
            $totalEnrolled = $enrolledRow ? (int)$enrolledRow['total_enrolled'] : 0;
            if ($totalEnrolled === 0) {
                $fallbackEnrolled = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
                $totalEnrolled = max(1, (int)$fallbackEnrolled);
            }

            // 4. Aggregate metrics
            $presentCount = 0;
            $tardyCount   = 0;
            $absentCount  = 0;

            foreach ($allFormatted as $c) {
                if ($c['status'] === 'present') $presentCount++;
                elseif ($c['status'] === 'tardy') $tardyCount++;
                elseif ($c['status'] === 'absent') $absentCount++;
            }

            $pendingCount = max(0, $totalEnrolled - ($presentCount + $tardyCount + $absentCount));

            // Active session counters (for current live session card)
            $activePresent = 0;
            $activeTardy   = 0;
            foreach ($activeFormatted as $ac) {
                if ($ac['status'] === 'present') $activePresent++;
                elseif ($ac['status'] === 'tardy') $activeTardy++;
            }

            echo json_encode([
                'status'             => 'success',
                'has_active_session' => $hasActiveSession,
                'current_session_id' => $currentSessionId,
                'checkins'           => $hasActiveSession ? $activeFormatted : [], // Cleared on screen when no active session
                'all_today_checkins' => $allFormatted, // Persisted for modal and historical view
                'metrics'  => [
                    'enrolled' => $totalEnrolled,
                    'present'  => $presentCount,
                    'tardy'    => $tardyCount,
                    'absent'   => $absentCount,
                    'pending'  => $pendingCount,
                    'total_checked_in'        => count($allFormatted),
                    'active_session_checked_in'=> count($activeFormatted)
                ]
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to load live attendance feed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * POST /api/attendance/check-in
     * Records an attendance scan (validates 6-digit QR session and inserts into `attendance` table).
     */
    public function recordCheckIn(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $raw = file_get_contents('php://input');
            $input = !empty($raw) ? json_decode($raw, true) : $_POST;

            $qrCode = trim($input['qr_code'] ?? $input['token'] ?? '');
            $studentIdentifier = trim($input['student_id'] ?? $input['student_number'] ?? '');
            $statusOverride = $input['status'] ?? null;

            if (empty($qrCode)) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => '6-digit QR Code token is required.']);
                exit;
            }

            // 1. Verify active QR session
            $sessStmt = $db->prepare("
                SELECT qr_session_id, teacher_id, qr_code, start, `end`
                FROM qr_sessions
                WHERE qr_code = ? AND `end` > NOW()
                ORDER BY qr_session_id DESC
                LIMIT 1
            ");
            $sessStmt->execute([$qrCode]);
            $session = $sessStmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                http_response_code(400);
                echo json_encode([
                    'status'    => 'error',
                    'scan_code' => 'EXPIRED_OR_INVALID',
                    'message'   => 'This 6-digit QR code is expired or invalid. Please scan the current code on screen.'
                ]);
                exit;
            }

            $teacherId = (int)$session['teacher_id'];
            $sessionId = (int)$session['qr_session_id'];

            // 2. Resolve student
            $student = null;
            if (!empty($studentIdentifier)) {
                $stStmt = $db->prepare("
                    SELECT user_id, student_id, first_name, last_name, email
                    FROM users
                    WHERE (user_id = :id_num OR student_id = :st_num OR LOWER(email) = LOWER(:email))
                      AND role = 'student'
                    LIMIT 1
                ");
                $stStmt->execute([
                    ':id_num' => is_numeric($studentIdentifier) ? (int)$studentIdentifier : 0,
                    ':st_num' => $studentIdentifier,
                    ':email'  => $studentIdentifier
                ]);
                $student = $stStmt->fetch(PDO::FETCH_ASSOC);
            } else {
                // Fallback to logged in student
                if (session_status() === PHP_SESSION_NONE) session_start();
                if (!empty($_SESSION['user_id'])) {
                    $stStmt = $db->prepare("SELECT user_id, student_id, first_name, last_name, email FROM users WHERE user_id = ? LIMIT 1");
                    $stStmt->execute([(int)$_SESSION['user_id']]);
                    $student = $stStmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if (!$student) {
                // Pick a default student from roster for testing if not provided
                $pick = $db->prepare("
                    SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email
                    FROM class_roster cr
                    JOIN users u ON u.user_id = cr.student_id
                    WHERE cr.teacher_id = ?
                    LIMIT 1
                ");
                $pick->execute([$teacherId]);
                $student = $pick->fetch(PDO::FETCH_ASSOC);
            }

            if (!$student) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Student record not found.']);
                exit;
            }

            $studentUserId = (int)$student['user_id'];
            $today = date('Y-m-d');
            $nowTime = date('H:i:s');

            // 3. Get Course/Subject Title
            $cStmt = $db->prepare("SELECT course_title FROM class_roster WHERE teacher_id = ? LIMIT 1");
            $cStmt->execute([$teacherId]);
            $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
            $subject = $cRow['course_title'] ?? 'Web Systems and Technologies';

            // 4. Check for Duplicate scan today
            $dupStmt = $db->prepare("
                SELECT attendance_id, status, `time`
                FROM attendance
                WHERE student_id = ? AND teacher_id = ? AND `date` = ?
            ");
            $dupStmt->execute([$studentUserId, $teacherId, $today]);
            $existing = $dupStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                http_response_code(409);
                echo json_encode([
                    'status'    => 'error',
                    'scan_code' => 'DUPLICATE',
                    'message'   => "Attendance already recorded for {$student['first_name']} {$student['last_name']} today at {$existing['time']} ({$existing['status']}).",
                    'existing'  => $existing
                ]);
                exit;
            }

            // 5. Determine attendance status (present / tardy)
            $status = in_array($statusOverride, ['present', 'tardy', 'absent']) ? $statusOverride : 'present';

            // 6. Insert into `attendance` table
            $insStmt = $db->prepare("
                INSERT INTO attendance (student_id, teacher_id, qr_session_id, `date`, `time`, subject, status, schedule_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insStmt->execute([$studentUserId, $teacherId, $sessionId, $today, $nowTime, $subject, $status, $today]);
            $attendanceId = (int)$db->lastInsertId();

            // 7. Insert audit log
            try {
                $auditStmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, action, description, reference_type, reference_id, created_at)
                    VALUES (?, 'create', ?, 'attendance', ?, NOW())
                ");
                $auditStmt->execute([
                    $studentUserId,
                    "Student marked $status via Dynamic 6-digit QR (Token: $qrCode)",
                    $attendanceId
                ]);
            } catch (Exception $e) {}

            echo json_encode([
                'status'        => 'success',
                'message'       => "Attendance recorded: {$student['first_name']} {$student['last_name']} marked " . ucfirst($status),
                'attendance_id' => $attendanceId,
                'record'        => [
                    'student_id'     => $studentUserId,
                    'student_name'   => "{$student['first_name']} {$student['last_name']}",
                    'student_number' => $student['student_id'],
                    'status'         => $status,
                    'time'           => date('h:i:s A', strtotime($nowTime)),
                    'subject'        => $subject
                ]
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Check-in error: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}
