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

            $raw = file_get_contents('php://input');
            $input = !empty($raw) ? json_decode($raw, true) : $_POST;
            $requestedSection = trim($input['section'] ?? '');

            // If section not provided, get default section for this teacher from class_roster
            if (empty($requestedSection)) {
                $rDefault = $db->prepare("SELECT section FROM class_roster WHERE teacher_id = ? ORDER BY section ASC LIMIT 1");
                $rDefault->execute([$teacherId]);
                $requestedSection = $rDefault->fetchColumn() ?: '31001';
            }

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
            // Auto-deactivate any sessions whose 30m window has already passed
            $db->exec("UPDATE qr_sessions SET is_active = 0 WHERE is_active = 1 AND `end` <= NOW()");

            // Close and deactivate any previously active sessions for this teacher / section to avoid duplication
            $closeOld = $db->prepare("UPDATE qr_sessions SET is_active = 0, `end` = LEAST(`end`, NOW()) WHERE teacher_id = ? AND (section = ? OR is_active = 1)");
            $closeOld->execute([$teacherId, $requestedSection]);

            // Insert new 30-minute QR session with is_active = 1 (default true)
            $stmt = $db->prepare("
                INSERT INTO qr_sessions (teacher_id, section, qr_code, start, `end`, is_active, created_at)
                VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 30 MINUTE), 1, NOW())
            ");
            $stmt->execute([$teacherId, $requestedSection, $qrCode]);
            $sessionId = (int)$db->lastInsertId();

            // Fetch created session details
            $sStmt = $db->prepare("
                SELECT qr_session_id, teacher_id, section, qr_code, start, `end`, is_active, created_at,
                       TIMESTAMPDIFF(SECOND, NOW(), `end`) AS expires_in_seconds
                FROM qr_sessions
                WHERE qr_session_id = ?
            ");
            $sStmt->execute([$sessionId]);
            $session = $sStmt->fetch(PDO::FETCH_ASSOC);

            // Get class/roster info for this teacher and section
            $rStmt = $db->prepare("
                SELECT course_code, course_title, section, room_number, scheduled_time, schedule_day
                FROM class_roster
                WHERE teacher_id = ? AND section = ?
                LIMIT 1
            ");
            $rStmt->execute([$teacherId, $requestedSection]);
            $roster = $rStmt->fetch(PDO::FETCH_ASSOC);
            if (!$roster) {
                // Fallback to any row for this teacher
                $rStmt2 = $db->prepare("
                    SELECT course_code, course_title, section, room_number, scheduled_time, schedule_day
                    FROM class_roster
                    WHERE teacher_id = ?
                    LIMIT 1
                ");
                $rStmt2->execute([$teacherId]);
                $roster = $rStmt2->fetch(PDO::FETCH_ASSOC) ?: [
                    'course_code'    => 'IT301',
                    'course_title'   => 'Web Systems and Technologies',
                    'section'        => $requestedSection ?: '31001',
                    'room_number'    => '402',
                    'scheduled_time' => '08:00:00',
                    'schedule_day'   => 'Monday'
                ];
                $roster['section'] = $requestedSection;
            }

            echo json_encode([
                'status'  => 'success',
                'message' => 'Dynamic 6-digit QR session created for 30 minutes',
                'session' => [
                    'qr_session_id'      => (int)$session['qr_session_id'],
                    'qr_code'            => $session['qr_code'],
                    'start'              => $session['start'],
                    'end'                => $session['end'],
                    'is_active'          => (bool)$session['is_active'],
                    'expires_in_seconds' => max(0, (int)$session['expires_in_seconds']),
                    'course_code'        => $roster['course_code'],
                    'course_title'       => $roster['course_title'],
                    'section'            => $session['section'] ?: $roster['section'],
                    'room_number'        => $roster['room_number'] ?? '402',
                    'scheduled_time'     => $roster['scheduled_time'] ?? '08:00:00',
                    'schedule_day'       => $roster['schedule_day'] ?? 'Monday'
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
     * Fetches current active QR session for the logged-in teacher (if within 30 minutes and is_active = 1).
     */
    public function getActiveQrSession(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);
            $reqSection = trim($_GET['section'] ?? '');

            // Auto-deactivate any sessions whose 30m window has already passed
            $db->exec("UPDATE qr_sessions SET is_active = 0 WHERE is_active = 1 AND `end` <= NOW()");

            if (!empty($reqSection)) {
                $stmt = $db->prepare("
                    SELECT qr_session_id, teacher_id, section, qr_code, start, `end`, is_active, created_at,
                           TIMESTAMPDIFF(SECOND, NOW(), `end`) AS expires_in_seconds
                    FROM qr_sessions
                    WHERE teacher_id = ? AND section = ? AND is_active = 1 AND `end` > NOW()
                    ORDER BY qr_session_id DESC
                    LIMIT 1
                ");
                $stmt->execute([$teacherId, $reqSection]);
            } else {
                $stmt = $db->prepare("
                    SELECT qr_session_id, teacher_id, section, qr_code, start, `end`, is_active, created_at,
                           TIMESTAMPDIFF(SECOND, NOW(), `end`) AS expires_in_seconds
                    FROM qr_sessions
                    WHERE teacher_id = ? AND is_active = 1 AND `end` > NOW()
                    ORDER BY qr_session_id DESC
                    LIMIT 1
                ");
                $stmt->execute([$teacherId]);
            }
            // Fetch all sections with an active QR session for this teacher
            $allActStmt = $db->prepare("
                SELECT DISTINCT section
                FROM qr_sessions
                WHERE teacher_id = ? AND is_active = 1 AND `end` > NOW()
            ");
            $allActStmt->execute([$teacherId]);
            $activeSectionsList = $allActStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $session = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($session && (int)$session['expires_in_seconds'] > 0) {
                $activeSection = $session['section'] ?: '31001';
                // Get class info for this section
                $rStmt = $db->prepare("
                    SELECT course_code, course_title, section, room_number, scheduled_time, schedule_day
                    FROM class_roster
                    WHERE teacher_id = ? AND (section = ? OR ? = '')
                    ORDER BY (section = ?) DESC
                    LIMIT 1
                ");
                $rStmt->execute([$teacherId, $activeSection, $activeSection, $activeSection]);
                $roster = $rStmt->fetch(PDO::FETCH_ASSOC) ?: [
                    'course_code'    => 'IT301',
                    'course_title'   => 'Web Systems and Technologies',
                    'section'        => $activeSection,
                    'room_number'    => '402',
                    'scheduled_time' => '08:00:00',
                    'schedule_day'   => 'Monday'
                ];

                echo json_encode([
                    'status'             => 'success',
                    'has_active_session' => true,
                    'active_sections'    => $activeSectionsList,
                    'session'            => [
                        'qr_session_id'      => (int)$session['qr_session_id'],
                        'qr_code'            => $session['qr_code'],
                        'start'              => $session['start'],
                        'end'                => $session['end'],
                        'expires_in_seconds' => (int)$session['expires_in_seconds'],
                        'course_code'        => $roster['course_code'],
                        'course_title'       => $roster['course_title'],
                        'section'            => $session['section'] ?: $roster['section'],
                        'room_number'        => $roster['room_number'] ?? '402',
                        'scheduled_time'     => $roster['scheduled_time'] ?? '08:00:00',
                        'schedule_day'       => $roster['schedule_day'] ?? 'Monday'
                    ]
                ]);
            } else {
                echo json_encode([
                    'status'             => 'success',
                    'has_active_session' => false,
                    'active_sections'    => $activeSectionsList,
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
            $sessionSection = trim($input['section'] ?? '');

            // Close session(s) and resolve section
            if ($sessionId) {
                $sessInfo = $db->prepare("SELECT section FROM qr_sessions WHERE qr_session_id = ? AND teacher_id = ?");
                $sessInfo->execute([$sessionId, $teacherId]);
                $sessRow = $sessInfo->fetch(PDO::FETCH_ASSOC);
                if ($sessRow && !empty($sessRow['section'])) {
                    $sessionSection = $sessRow['section'];
                }
                $closeStmt = $db->prepare("UPDATE qr_sessions SET is_active = 0, `end` = NOW() WHERE qr_session_id = ? AND teacher_id = ?");
                $closeStmt->execute([$sessionId, $teacherId]);
            } else {
                if (!empty($sessionSection)) {
                    $closeStmt = $db->prepare("UPDATE qr_sessions SET is_active = 0, `end` = NOW() WHERE teacher_id = ? AND section = ? AND is_active = 1");
                    $closeStmt->execute([$teacherId, $sessionSection]);
                } else {
                    $closeStmt = $db->prepare("UPDATE qr_sessions SET is_active = 0, `end` = NOW() WHERE teacher_id = ? AND is_active = 1");
                    $closeStmt->execute([$teacherId]);
                }
            }

            // Find enrolled students in this section who do not have an attendance record for today
            if (!empty($sessionSection)) {
                $rosterStmt = $db->prepare("
                    SELECT cr.student_id, cr.course_title
                    FROM class_roster cr
                    WHERE cr.teacher_id = ? AND cr.section = ?
                ");
                $rosterStmt->execute([$teacherId, $sessionSection]);
            } else {
                $rosterStmt = $db->prepare("
                    SELECT cr.student_id, cr.course_title
                    FROM class_roster cr
                    WHERE cr.teacher_id = ?
                ");
                $rosterStmt->execute([$teacherId]);
            }
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
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);
            $today = date('Y-m-d');
            $reqSection = trim($_GET['section'] ?? '');

            // 1. Check if teacher currently has an active QR session for this section
            $activeSession = null;
            if (!empty($reqSection)) {
                $sessStmt = $db->prepare("
                    SELECT qr_session_id, section, qr_code, is_active 
                    FROM qr_sessions 
                    WHERE teacher_id = ? AND section = ? AND is_active = 1 AND `end` > NOW() 
                    ORDER BY qr_session_id DESC 
                    LIMIT 1
                ");
                $sessStmt->execute([$teacherId, $reqSection]);
                $activeSession = $sessStmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $sessStmt = $db->prepare("
                    SELECT qr_session_id, section, qr_code, is_active 
                    FROM qr_sessions 
                    WHERE teacher_id = ? AND is_active = 1 AND `end` > NOW() 
                    ORDER BY qr_session_id DESC 
                    LIMIT 1
                ");
                $sessStmt->execute([$teacherId]);
                $activeSession = $sessStmt->fetch(PDO::FETCH_ASSOC);
            }

            $hasActiveSession = $activeSession && !empty($activeSession['is_active']);
            $currentSessionId = $activeSession ? (int)$activeSession['qr_session_id'] : null;
            $activeSection = !empty($reqSection) ? $reqSection : ($activeSession['section'] ?? '31001');

            // 2. Fetch all check-in records for today strictly filtered by section
            if (!empty($reqSection)) {
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
                        u.avatar_path,
                        cr.section AS roster_section,
                        qs.section AS session_section
                    FROM attendance a
                    JOIN users u ON u.user_id = a.student_id
                    JOIN class_roster cr ON cr.student_id = a.student_id AND cr.teacher_id = a.teacher_id AND cr.section = ?
                    LEFT JOIN qr_sessions qs ON qs.qr_session_id = a.qr_session_id
                    WHERE a.teacher_id = ? AND a.date = ?
                    ORDER BY a.time DESC, a.attendance_id DESC
                ");
                $feedStmt->execute([$reqSection, $teacherId, $today]);
            } else {
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
                        u.avatar_path,
                        cr.section AS roster_section,
                        qs.section AS session_section
                    FROM attendance a
                    JOIN users u ON u.user_id = a.student_id
                    LEFT JOIN class_roster cr ON cr.student_id = a.student_id AND cr.teacher_id = a.teacher_id
                    LEFT JOIN qr_sessions qs ON qs.qr_session_id = a.qr_session_id
                    WHERE a.teacher_id = ? AND a.date = ?
                    ORDER BY a.time DESC, a.attendance_id DESC
                ");
                $feedStmt->execute([$teacherId, $today]);
            }
            $allRows = $feedStmt->fetchAll(PDO::FETCH_ASSOC);

            // Format check-in rows
            $allFormatted = [];
            $activeFormatted = [];

            foreach ($allRows as $row) {
                $itemSection = $row['roster_section'] ?: $row['session_section'];

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
                    'avatar_path'    => $row['avatar_path'],
                    'section'        => $itemSection
                ];

                $allFormatted[] = $item;

                // Only include in active feed if it belongs to current active session
                if ($hasActiveSession && $currentSessionId && (int)$row['qr_session_id'] === $currentSessionId) {
                    $activeFormatted[] = $item;
                }
            }

            // 3. Count total enrolled students in class_roster for this teacher and section (NO FALLBACKS - Real Data Only)
            if (!empty($reqSection)) {
                $enrolledStmt = $db->prepare("
                    SELECT COUNT(DISTINCT student_id) AS total_enrolled
                    FROM class_roster
                    WHERE teacher_id = ? AND section = ?
                ");
                $enrolledStmt->execute([$teacherId, $reqSection]);
            } else {
                $enrolledStmt = $db->prepare("
                    SELECT COUNT(DISTINCT student_id) AS total_enrolled
                    FROM class_roster
                    WHERE teacher_id = ?
                ");
                $enrolledStmt->execute([$teacherId]);
            }
            $enrolledRow = $enrolledStmt->fetch(PDO::FETCH_ASSOC);
            $totalEnrolled = $enrolledRow ? (int)$enrolledRow['total_enrolled'] : 0;

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
                'section'            => $activeSection,
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
     * POST /api/teacher/attendance/void-proxy
     * Voids a student's check-in for the session and marks them as absent
     * when a teacher detects a proxy or intruder scan.
     */
    public function voidProxyAttendance(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);
            $raw = file_get_contents('php://input');
            $input = !empty($raw) ? json_decode($raw, true) : $_POST;

            $attendanceId = (int)($input['attendance_id'] ?? 0);
            $reason = trim($input['reason'] ?? 'Suspected remote proxy scan / Not physically present in room');

            if ($attendanceId <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Attendance ID is required.']);
                exit;
            }

            // Verify attendance record belongs to this teacher or session
            $st = $db->prepare("
                SELECT a.attendance_id, a.student_id, a.status, a.date, a.time,
                       u.first_name, u.last_name, u.student_id as student_number
                FROM attendance a
                JOIN users u ON u.user_id = a.student_id
                WHERE a.attendance_id = ? AND a.teacher_id = ?
                LIMIT 1
            ");
            $st->execute([$attendanceId, $teacherId]);
            $att = $st->fetch(PDO::FETCH_ASSOC);

            if (!$att) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Attendance record not found or unauthorized.']);
                exit;
            }

            // Update status to 'absent'
            $upd = $db->prepare("UPDATE attendance SET status = 'absent' WHERE attendance_id = ?");
            $upd->execute([$attendanceId]);

            // Insert audit log
            try {
                $audit = $db->prepare("
                    INSERT INTO audit_logs (user_id, action, description, reference_type, reference_id, created_at)
                    VALUES (?, 'update', ?, 'attendance', ?, NOW())
                ");
                $audit->execute([
                    $teacherId,
                    "Attendance voided for {$att['first_name']} {$att['last_name']} ({$att['student_number']}) - Marked Absent. Reason: $reason",
                    $attendanceId
                ]);
            } catch (Throwable $e) {}

            echo json_encode([
                'status'  => 'success',
                'message' => "Attendance for {$att['first_name']} {$att['last_name']} has been voided and marked as Absent.",
                'record'  => [
                    'attendance_id' => $attendanceId,
                    'status'        => 'absent'
                ]
            ]);
            exit;

        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to void attendance: ' . $e->getMessage()]);
            exit;
        }
    }

    /**
     * POST /api/attendance/check-in
     * Records an attendance scan (validates 6-digit QR session, checks student enrollment in section via `class_roster`, and inserts into `attendance` table).
     */
    public function recordCheckIn(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
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

            // 1. Verify active QR session (must have is_active = 1 and end > NOW())
            $sessStmt = $db->prepare("
                SELECT qr_session_id, teacher_id, section, qr_code, start, `end`, is_active
                FROM qr_sessions
                WHERE qr_code = ? AND is_active = 1 AND `end` > NOW()
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
            $sessionSection = trim($session['section'] ?? '');

            // 2. Resolve student quickly via indexed lookup
            $student = null;
            if (!empty($studentIdentifier)) {
                if (is_numeric($studentIdentifier) && (int)$studentIdentifier > 0) {
                    $stStmt = $db->prepare("
                        SELECT user_id, student_id, first_name, last_name, email
                        FROM users
                        WHERE (user_id = :id_num OR student_id = :st_num) AND role = 'student'
                        LIMIT 1
                    ");
                    $stStmt->execute([
                        ':id_num' => (int)$studentIdentifier,
                        ':st_num' => $studentIdentifier
                    ]);
                } else {
                    $stStmt = $db->prepare("
                        SELECT user_id, student_id, first_name, last_name, email
                        FROM users
                        WHERE (student_id = :st_num OR LOWER(email) = LOWER(:email)) AND role = 'student'
                        LIMIT 1
                    ");
                    $stStmt->execute([
                        ':st_num' => $studentIdentifier,
                        ':email'  => $studentIdentifier
                    ]);
                }
                $student = $stStmt->fetch(PDO::FETCH_ASSOC);

                if (!$student) {
                    http_response_code(404);
                    echo json_encode([
                        'status'    => 'error',
                        'scan_code' => 'STUDENT_NOT_FOUND',
                        'message'   => 'Student record not found.'
                    ]);
                    exit;
                }
            } else {
                // Fallback to logged in student
                if (session_status() === PHP_SESSION_NONE) session_start();
                if (!empty($_SESSION['user_id'])) {
                    $stStmt = $db->prepare("SELECT user_id, student_id, first_name, last_name, email FROM users WHERE user_id = ? LIMIT 1");
                    $stStmt->execute([(int)$_SESSION['user_id']]);
                    $student = $stStmt->fetch(PDO::FETCH_ASSOC);
                }

                if (!$student) {
                    // Pick a default student from roster for testing if no student specified or logged in
                    if (!empty($sessionSection)) {
                        $pick = $db->prepare("
                            SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email
                            FROM class_roster cr
                            JOIN users u ON u.user_id = cr.student_id
                            WHERE cr.teacher_id = ? AND cr.section = ?
                            LIMIT 1
                        ");
                        $pick->execute([$teacherId, $sessionSection]);
                    } else {
                        $pick = $db->prepare("
                            SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email
                            FROM class_roster cr
                            JOIN users u ON u.user_id = cr.student_id
                            WHERE cr.teacher_id = ?
                            LIMIT 1
                        ");
                        $pick->execute([$teacherId]);
                    }
                    $student = $pick->fetch(PDO::FETCH_ASSOC);
                }
            }

            if (!$student) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Student record not found.']);
                exit;
            }

            $studentUserId = (int)$student['user_id'];
            $today = date('Y-m-d');
            $nowTime = date('H:i:s');

            // 3. CHECK CLASS_ROSTER TABLE: Validate student is part of the section
            $subject = 'Web Systems and Technologies';

            if (!empty($sessionSection)) {
                $rosterCheck = $db->prepare("
                    SELECT roster_id, course_code, course_title, section
                    FROM class_roster
                    WHERE student_id = ? AND teacher_id = ? AND section = ?
                    LIMIT 1
                ");
                $rosterCheck->execute([$studentUserId, $teacherId, $sessionSection]);
                $rosterMatch = $rosterCheck->fetch(PDO::FETCH_ASSOC);

                // Fallback check if student_id is user_id or vice versa
                if (!$rosterMatch && !empty($student['student_id'])) {
                    $rosterCheck2 = $db->prepare("
                        SELECT cr.roster_id, cr.course_code, cr.course_title, cr.section
                        FROM class_roster cr
                        JOIN users u ON u.user_id = cr.student_id
                        WHERE (u.student_id = :st_num OR cr.student_id = :st_uid) AND cr.teacher_id = :t_id AND cr.section = :sec
                        LIMIT 1
                    ");
                    $rosterCheck2->execute([
                        ':st_num' => $student['student_id'],
                        ':st_uid' => $studentUserId,
                        ':t_id'   => $teacherId,
                        ':sec'    => $sessionSection
                    ]);
                    $rosterMatch = $rosterCheck2->fetch(PDO::FETCH_ASSOC);
                }

                if (!$rosterMatch) {
                    http_response_code(403);
                    echo json_encode([
                        'status'    => 'error',
                        'scan_code' => 'WRONG_SECTION',
                        'message'   => "Access Denied: Student {$student['first_name']} {$student['last_name']} is not enrolled in section {$sessionSection} for this class."
                    ]);
                    exit;
                }
                $subject = $rosterMatch['course_title'] ?: $subject;
            } else {
                // If session had no section specified, check teacher roster
                $cStmt = $db->prepare("SELECT course_title FROM class_roster WHERE teacher_id = ? AND student_id = ? LIMIT 1");
                $cStmt->execute([$teacherId, $studentUserId]);
                $cRow = $cStmt->fetch(PDO::FETCH_ASSOC);
                if (!$cRow) {
                    http_response_code(403);
                    echo json_encode([
                        'status'    => 'error',
                        'scan_code' => 'WRONG_SECTION',
                        'message'   => "Access Denied: Student {$student['first_name']} {$student['last_name']} is not in this teacher's class roster."
                    ]);
                    exit;
                }
                $subject = $cRow['course_title'] ?: $subject;
            }

            // 4. Check for Duplicate scan today
            $dupStmt = $db->prepare("
                SELECT attendance_id, status, `time`
                FROM attendance
                WHERE student_id = ? AND teacher_id = ? AND `date` = ?
                LIMIT 1
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

            // 6. Fast Atomic Insert in Transaction
            $db->beginTransaction();

            $insStmt = $db->prepare("
                INSERT INTO attendance (student_id, teacher_id, qr_session_id, `date`, `time`, subject, status, schedule_date, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insStmt->execute([$studentUserId, $teacherId, $sessionId, $today, $nowTime, $subject, $status, $today]);
            $attendanceId = (int)$db->lastInsertId();

            // 7. Insert audit log within same transaction
            try {
                $auditStmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, action, description, reference_type, reference_id, created_at)
                    VALUES (?, 'create', ?, 'attendance', ?, NOW())
                ");
                $auditStmt->execute([
                    $studentUserId,
                    "Student marked $status in section " . ($sessionSection ?: 'N/A') . " via Dynamic 6-digit QR (Token: $qrCode)",
                    $attendanceId
                ]);
            } catch (Exception $e) {}

            $db->commit();

            echo json_encode([
                'status'        => 'success',
                'message'       => "Attendance recorded: {$student['first_name']} {$student['last_name']} marked " . ucfirst($status),
                'attendance_id' => $attendanceId,
                'record'        => [
                    'attendance_id'  => $attendanceId,
                    'student_id'     => $studentUserId,
                    'student_name'   => "{$student['first_name']} {$student['last_name']}",
                    'student_number' => $student['student_id'],
                    'status'         => $status,
                    'time'           => date('h:i:s A', strtotime($nowTime)),
                    'subject'        => $subject,
                    'section'        => $sessionSection
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

    /**
     * Escapes cell values to prevent CSV Formula Injection (CWE-1236).
     * Any cell starting with =, +, -, or @ is prefixed with a single quote (').
     */
    private function escapeCsvCell(string $value): string {
        $trimmed = ltrim($value);
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@'], true)) {
            return "'" . $value;
        }
        return $value;
    }

    /**
     * GET /api/teacher/roster/students
     * Returns enrolled students for the authenticated teacher's class roster.
     * Enforces teacher_id filtering at the SQL level.
     */
    public function apiGetRosterStudents(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            // Filter class_roster by resolved teacher_id at SQL level
            $stmt = $db->prepare("
                SELECT 
                    cr.roster_id,
                    cr.student_id,
                    COALESCE(u.student_id, '2026-00000') AS student_number,
                    cr.first_name,
                    cr.last_name,
                    CONCAT(cr.last_name, ', ', cr.first_name) AS full_name,
                    cr.section,
                    cr.course_code,
                    cr.course_title,
                    cr.room_number,
                    cr.scheduled_time,
                    cr.schedule_day,
                    cr.year_level,
                    u.email,
                    u.avatar_path
                FROM class_roster cr
                LEFT JOIN users u ON u.user_id = cr.student_id
                WHERE cr.teacher_id = :teacher_id
                ORDER BY cr.last_name ASC, cr.first_name ASC
            ");
            $stmt->execute([':teacher_id' => $teacherId]);
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'status'     => 'success',
                'teacher_id' => $teacherId,
                'count'      => count($students),
                'students'   => $students
            ]);
            exit;

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to retrieve roster students: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * GET /api/attendance/daily
     * Retrieves the daily attendance records, KPI stats, and tardy/absence breakdowns.
     * Enforces teacher_id filtering at SQL level, scopes minutes_late by (student_id, date, subject),
     * and supports sanitized CSV export protected against formula injection.
     */
    public function apiDailyAttendance(): void {
        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            $date = trim($_GET['date'] ?? date('Y-m-d'));
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $date = date('Y-m-d');
            }

            $sectionFilter = trim($_GET['section'] ?? '');
            $statusFilter  = trim($_GET['status'] ?? '');
            $searchQuery   = trim($_GET['search'] ?? '');
            $isExportCsv   = (isset($_GET['export']) && strtolower(trim($_GET['export'])) === 'csv');

            // 1. Fetch distinct sections for this teacher (for dynamic filter dropdown)
            $secStmt = $db->prepare("
                SELECT DISTINCT section 
                FROM class_roster 
                WHERE teacher_id = :teacher_id 
                ORDER BY section ASC
            ");
            $secStmt->execute([':teacher_id' => $teacherId]);
            $availableSections = $secStmt->fetchAll(PDO::FETCH_COLUMN);

            // 2. Base Query: Enforce cr.teacher_id = :teacher_id at SQL level
            // Scopes attendance and excuse_slips by student_id, teacher_id, date, and subject (multi-period safe)
            $sql = "
                SELECT 
                    cr.roster_id,
                    cr.student_id,
                    COALESCE(u.student_id, '2026-00000') AS student_number,
                    cr.first_name,
                    cr.last_name,
                    CONCAT(cr.last_name, ', ', cr.first_name) AS full_name,
                    cr.section,
                    cr.course_code,
                    cr.course_title,
                    cr.scheduled_time,
                    cr.room_number,
                    u.email,
                    u.avatar_path,
                    a.attendance_id,
                    a.date AS attendance_date,
                    a.time AS time_in,
                    a.status AS raw_status,
                    a.qr_session_id,
                    es.excuse_slip_id,
                    es.status AS excuse_status,
                    es.reason AS excuse_reason,
                    pa.parent_alert_id,
                    pa.alert_time
                FROM class_roster cr
                LEFT JOIN users u ON u.user_id = cr.student_id
                LEFT JOIN attendance a ON a.student_id = cr.student_id 
                                      AND a.teacher_id = cr.teacher_id 
                                      AND a.date = :att_date 
                                      AND a.subject = cr.course_title
                LEFT JOIN excuse_slips es ON es.student_id = cr.student_id 
                                         AND es.teacher_id = cr.teacher_id 
                                         AND es.date_of_absence = :exc_date 
                                         AND es.subject = cr.course_title
                LEFT JOIN parent_alerts pa ON (pa.attendance_id = a.attendance_id 
                                            OR (pa.student_id = cr.student_id AND pa.alert_date = :pa_date))
                WHERE cr.teacher_id = :teacher_id
            ";

            $params = [
                ':teacher_id' => $teacherId,
                ':att_date'   => $date,
                ':exc_date'   => $date,
                ':pa_date'    => $date,
            ];

            if ($sectionFilter !== '' && strtolower($sectionFilter) !== 'all') {
                $sql .= " AND cr.section = :section";
                $params[':section'] = $sectionFilter;
            }

            if ($searchQuery !== '') {
                $sql .= " AND (cr.first_name LIKE :sq1 OR cr.last_name LIKE :sq2 OR u.student_id LIKE :sq3 OR u.email LIKE :sq4)";
                $like = "%{$searchQuery}%";
                $params[':sq1'] = $like;
                $params[':sq2'] = $like;
                $params[':sq3'] = $like;
                $params[':sq4'] = $like;
            }

            $sql .= " ORDER BY cr.last_name ASC, cr.first_name ASC";

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $rawRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Process and format rows
            $processedRecords = [];
            $kpi = [
                'total_enrolled' => count($rawRows),
                'present'        => 0,
                'tardy'          => 0,
                'absent'         => 0,
                'excused'        => 0,
                'unrecorded'     => 0
            ];

            foreach ($rawRows as $row) {
                // Determine method: QR vs Manual vs None
                $method = '—';
                if (!empty($row['attendance_id'])) {
                    $method = !empty($row['qr_session_id']) ? 'QR' : 'Manual';
                }

                // Determine display status
                $rawStatus = $row['raw_status']; // 'present', 'tardy', 'absent', or NULL
                $displayStatus = 'unrecorded';

                if ($rawStatus === 'present') {
                    $displayStatus = 'present';
                    $kpi['present']++;
                } elseif ($rawStatus === 'tardy') {
                    $displayStatus = 'tardy';
                    $kpi['tardy']++;
                } elseif ($rawStatus === 'absent') {
                    if (!empty($row['excuse_status']) && strtolower($row['excuse_status']) === 'approved') {
                        $displayStatus = 'excused';
                        $kpi['excused']++;
                    } else {
                        $displayStatus = 'absent';
                        $kpi['absent']++;
                    }
                } else {
                    $kpi['unrecorded']++;
                }

                // Calculate minutes_late scoped by (student_id, date, subject)
                $minutesLate = 0;
                if ($rawStatus === 'tardy' && !empty($row['time_in']) && !empty($row['scheduled_time'])) {
                    $scheduledSec = strtotime("1970-01-01 " . $row['scheduled_time']);
                    $arrivalSec   = strtotime("1970-01-01 " . $row['time_in']);
                    if ($arrivalSec > $scheduledSec) {
                        $minutesLate = (int)round(($arrivalSec - $scheduledSec) / 60);
                    }
                }

                // Filter by status if requested
                if ($statusFilter !== '' && strtolower($statusFilter) !== 'all') {
                    if (strtolower($statusFilter) !== strtolower($displayStatus)) {
                        continue;
                    }
                }

                $timeFormatted = !empty($row['time_in']) ? date('h:i A', strtotime($row['time_in'])) : '—';

                $processedRecords[] = [
                    'roster_id'          => (int)$row['roster_id'],
                    'student_id'         => (int)$row['student_id'],
                    'student_number'     => (string)$row['student_number'],
                    'full_name'          => $row['full_name'],
                    'first_name'         => $row['first_name'],
                    'last_name'          => $row['last_name'],
                    'section'            => $row['section'],
                    'course_code'        => $row['course_code'],
                    'course_title'       => $row['course_title'],
                    'scheduled_time'     => $row['scheduled_time'],
                    'attendance_id'      => $row['attendance_id'] ? (int)$row['attendance_id'] : null,
                    'time_in'            => $row['time_in'],
                    'time_formatted'     => $timeFormatted,
                    'status'             => $displayStatus,
                    'raw_status'         => $rawStatus,
                    'method'             => $method,
                    'minutes_late'       => $minutesLate,
                    'excuse_slip_id'     => $row['excuse_slip_id'] ? (int)$row['excuse_slip_id'] : null,
                    'excuse_status'      => $row['excuse_status'],
                    'excuse_reason'      => $row['excuse_reason'],
                    'parent_alert_sent'  => !empty($row['parent_alert_id']),
                    'parent_alert_time'  => $row['alert_time'] ? date('h:i A', strtotime($row['alert_time'])) : null
                ];
            }

            // 4. If CSV Export requested, stream with formula injection escaping
            if ($isExportCsv) {
                if (!headers_sent()) {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="attendance_ledger_' . $date . '.csv"');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                }

                $output = fopen('php://output', 'w');
                // CSV headers
                fputcsv($output, [
                    'Student Number',
                    'Student Name',
                    'Section',
                    'Course / Subject',
                    'Date',
                    'Time In',
                    'Status',
                    'Method',
                    'Minutes Late',
                    'Parent Alert'
                ]);

                foreach ($processedRecords as $r) {
                    $rowCells = [
                        $r['student_number'],
                        $r['full_name'],
                        $r['section'],
                        $r['course_title'],
                        $date,
                        $r['time_formatted'],
                        ucfirst($r['status']),
                        $r['method'],
                        $r['minutes_late'] > 0 ? "+{$r['minutes_late']} min" : '—',
                        $r['parent_alert_sent'] ? 'Sent' : 'Pending'
                    ];
                    // Escape every cell against CSV Formula Injection (=, +, -, @)
                    $escaped = array_map([$this, 'escapeCsvCell'], $rowCells);
                    fputcsv($output, $escaped);
                }

                fclose($output);
                exit;
            }

            // 5. Normal JSON response
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }

            echo json_encode([
                'status'             => 'success',
                'teacher_id'         => $teacherId,
                'date'               => $date,
                'available_sections' => $availableSections,
                'metrics'            => $kpi,
                'total_records'      => count($processedRecords),
                'records'            => $processedRecords
            ]);
            exit;

        } catch (Exception $e) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to load daily attendance: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * POST /api/attendance/manual-entry
     * Records or updates a manual attendance entry.
     * Enforces teacher_id filtering and student ownership at SQL level before write.
     * Detects existing records for (student_id, date, subject); if QR session exists,
     * manual entry overwrite-wins, and logs old_status and old_method into audit_logs.
     */
    public function apiManualEntry(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            $raw = file_get_contents('php://input');
            $input = !empty($raw) ? json_decode($raw, true) : $_POST;

            $studentId     = !empty($input['student_id']) ? (int)$input['student_id'] : 0;
            $date          = trim($input['date'] ?? date('Y-m-d'));
            $statusInput   = strtolower(trim($input['status'] ?? 'present'));
            $timeInput     = trim($input['time'] ?? date('H:i:s'));
            $subjectInput  = trim($input['subject'] ?? '');
            $notes         = trim($input['notes'] ?? $input['override_reason'] ?? '');

            // Basic validation
            if ($studentId <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Valid student_id is required.']);
                exit;
            }

            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                $date = date('Y-m-d');
            }

            // Normalise status to enum('present','tardy','absent')
            $status = in_array($statusInput, ['present', 'tardy', 'absent'], true) ? $statusInput : 'present';

            // Ensure time format is valid HH:MM:SS
            if (strlen($timeInput) === 5 && preg_match('/^\d{2}:\d{2}$/', $timeInput)) {
                $timeInput .= ':00';
            } elseif (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $timeInput)) {
                $timeInput = date('H:i:s');
            }

            // 1. Enforce student_id belongs to this teacher's class_roster at SQL level
            $rosterStmt = $db->prepare("
                SELECT cr.roster_id, cr.student_id, cr.first_name, cr.last_name, cr.section, cr.course_title
                FROM class_roster cr
                WHERE cr.student_id = :student_id AND cr.teacher_id = :teacher_id
                LIMIT 1
            ");
            $rosterStmt->execute([
                ':student_id' => $studentId,
                ':teacher_id' => $teacherId
            ]);
            $rosterRow = $rosterStmt->fetch(PDO::FETCH_ASSOC);

            if (!$rosterRow) {
                http_response_code(403);
                echo json_encode([
                    'status'  => 'error',
                    'message' => 'Authorization failed: Student does not belong to your assigned class roster.'
                ]);
                exit;
            }

            // Resolve subject from roster if not provided
            $subject = !empty($subjectInput) ? $subjectInput : ($rosterRow['course_title'] ?: 'Web Systems and Technologies');
            $studentName = "{$rosterRow['first_name']} {$rosterRow['last_name']}";

            // 2. Check if row already exists for this (student_id, date, subject)
            $checkStmt = $db->prepare("
                SELECT attendance_id, teacher_id, qr_session_id, status, `time`, subject
                FROM attendance
                WHERE student_id = :student_id 
                  AND `date` = :date 
                  AND subject = :subject
                LIMIT 1
            ");
            $checkStmt->execute([
                ':student_id' => $studentId,
                ':date'       => $date,
                ':subject'    => $subject
            ]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

            $action = 'create';
            $oldStatus = null;
            $oldMethod = null;
            $attendanceId = null;

            // Single transaction wrapping status update + audit insert
            $db->beginTransaction();

            if ($existing) {
                // OVERWRITE WINS (Manual entry replaces existing QR or earlier manual record)
                $action = 'update';
                $attendanceId = (int)$existing['attendance_id'];
                $oldStatus = $existing['status'];
                $oldMethod = !empty($existing['qr_session_id']) ? 'QR' : 'manual';

                $updateStmt = $db->prepare("
                    UPDATE attendance
                    SET teacher_id = :teacher_id,
                        qr_session_id = NULL, -- manual entry wins; cleared from QR session
                        `time` = :time,
                        status = :status,
                        schedule_date = :schedule_date,
                        updated_at = NOW()
                    WHERE attendance_id = :attendance_id
                ");
                $updateStmt->execute([
                    ':teacher_id'     => $teacherId,
                    ':time'           => $timeInput,
                    ':status'         => $status,
                    ':schedule_date'  => $date,
                    ':attendance_id'  => $attendanceId
                ]);

                // Audit Log with explicit old_status and old_method fields
                $auditDesc = "Manual override by Teacher #{$teacherId} for student {$studentName} (#{$studentId}): "
                           . "replaced {$oldMethod} (old_status: {$oldStatus}) with manual entry (new_status: {$status}, time: {$timeInput}).";
                if (!empty($notes)) {
                    $auditDesc .= " Reason: {$notes}";
                }

                $auditStmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, action, description, reference_type, reference_id, created_at)
                    VALUES (?, 'update', ?, 'attendance', ?, NOW())
                ");
                $auditStmt->execute([$teacherId, $auditDesc, $attendanceId]);

                $db->commit();

                echo json_encode([
                    'status'        => 'success',
                    'action'        => 'overwrite',
                    'message'       => "Attendance for {$studentName} successfully updated to " . ucfirst($status) . " (overrode {$oldMethod}: {$oldStatus}).",
                    'attendance_id' => $attendanceId,
                    'old_status'    => $oldStatus,
                    'old_method'    => $oldMethod,
                    'new_status'    => $status,
                    'new_method'    => 'manual',
                    'record'        => [
                        'student_id'   => $studentId,
                        'student_name' => $studentName,
                        'section'      => $rosterRow['section'],
                        'subject'      => $subject,
                        'date'         => $date,
                        'time'         => date('h:i:s A', strtotime($timeInput)),
                        'status'       => $status,
                        'method'       => 'Manual'
                    ]
                ]);
                exit;

            } else {
                // INSERT NEW ROW
                $insStmt = $db->prepare("
                    INSERT INTO attendance (
                        student_id, teacher_id, qr_session_id, `date`, `time`, subject, status, schedule_date, created_at, updated_at
                    ) VALUES (
                        :student_id, :teacher_id, NULL, :date, :time, :subject, :status, :schedule_date, NOW(), NOW()
                    )
                ");
                $insStmt->execute([
                    ':student_id'    => $studentId,
                    ':teacher_id'    => $teacherId,
                    ':date'          => $date,
                    ':time'          => $timeInput,
                    ':subject'       => $subject,
                    ':status'        => $status,
                    ':schedule_date' => $date
                ]);
                $attendanceId = (int)$db->lastInsertId();

                // Audit Log
                $auditDesc = "Manual attendance entry by Teacher #{$teacherId} for student {$studentName} (#{$studentId}): "
                           . "marked {$status} at {$timeInput} in {$subject}.";
                if (!empty($notes)) {
                    $auditDesc .= " Reason: {$notes}";
                }

                $auditStmt = $db->prepare("
                    INSERT INTO audit_logs (user_id, action, description, reference_type, reference_id, created_at)
                    VALUES (?, 'create', ?, 'attendance', ?, NOW())
                ");
                $auditStmt->execute([$teacherId, $auditDesc, $attendanceId]);

                $db->commit();

                echo json_encode([
                    'status'        => 'success',
                    'action'        => 'created',
                    'message'       => "Manual attendance for {$studentName} recorded as " . ucfirst($status) . ".",
                    'attendance_id' => $attendanceId,
                    'old_status'    => null,
                    'old_method'    => null,
                    'new_status'    => $status,
                    'new_method'    => 'manual',
                    'record'        => [
                        'student_id'   => $studentId,
                        'student_name' => $studentName,
                        'section'      => $rosterRow['section'],
                        'subject'      => $subject,
                        'date'         => $date,
                        'time'         => date('h:i:s A', strtotime($timeInput)),
                        'status'       => $status,
                        'method'       => 'Manual'
                    ]
                ]);
                exit;
            }

        } catch (Exception $e) {
            if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
                $db->rollBack();
            }
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => 'Manual entry failed: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * GET/POST /api/teacher/awards/calculate
     * Evaluates attendance records for assigned sections over a date range,
     * identifying eligible perfect attendance (100%) and high honors (98%+) candidates.
     */
    public function apiCalculateAwards(): void {
        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            $section = trim($_GET['section'] ?? $_POST['section'] ?? 'all');
            $startDate = trim($_GET['start_date'] ?? $_POST['start_date'] ?? date('Y-m-01'));
            $endDate = trim($_GET['end_date'] ?? $_POST['end_date'] ?? date('Y-m-t'));
            $threshold = (int)($_GET['threshold'] ?? $_POST['threshold'] ?? 100);
            if ($threshold !== 98) {
                $threshold = 100;
            }
            $isExportCsv = (isset($_GET['export']) && strtolower($_GET['export']) === 'csv')
                        || (isset($_POST['export']) && strtolower($_POST['export']) === 'csv');

            // 1. Determine total session dates held for this teacher in the date range
            $sessParams = [
                ':teacher_id' => $teacherId,
                ':start_date' => $startDate,
                ':end_date'   => $endDate
            ];
            $sessSql = "
                SELECT COUNT(DISTINCT a.date) AS session_count
                FROM attendance a
                WHERE a.teacher_id = :teacher_id
                  AND a.date BETWEEN :start_date AND :end_date
            ";
            if ($section !== '' && strtolower($section) !== 'all') {
                $sessSql .= " AND a.subject IN (SELECT DISTINCT course_title FROM class_roster WHERE teacher_id = :sec_teacher_id AND section = :sec_name)";
                $sessParams[':sec_teacher_id'] = $teacherId;
                $sessParams[':sec_name'] = $section;
            }
            $sessStmt = $db->prepare($sessSql);
            $sessStmt->execute($sessParams);
            $totalSessionsHeld = (int)($sessStmt->fetchColumn() ?: 0);

            // 2. Fetch aggregate attendance statistics for all enrolled students of this teacher
            $rosterParams = [
                ':teacher_id'  => $teacherId,
                ':start_date'  => $startDate,
                ':end_date'    => $endDate,
            ];
            $rosterSql = "
                SELECT 
                    cr.student_id,
                    cr.first_name,
                    cr.last_name,
                    CONCAT(cr.last_name, ', ', cr.first_name) AS full_name,
                    COALESCE(u.student_id, '230110001') AS student_number,
                    COALESCE(u.email, '') AS email,
                    cr.section,
                    cr.course_code,
                    cr.course_title,
                    COUNT(DISTINCT CASE WHEN a.date IS NOT NULL THEN a.date END) AS sessions_recorded,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
                    SUM(CASE WHEN a.status = 'tardy' THEN 1 ELSE 0 END) AS tardy_count,
                    SUM(CASE WHEN a.status = 'absent' AND (es.status IS NULL OR LOWER(es.status) != 'approved') THEN 1 ELSE 0 END) AS absent_count,
                    SUM(CASE WHEN a.status = 'absent' AND LOWER(es.status) = 'approved' THEN 1 ELSE 0 END) AS excused_count
                FROM class_roster cr
                JOIN users u ON u.user_id = cr.student_id
                LEFT JOIN attendance a ON a.student_id = cr.student_id 
                                      AND a.teacher_id = cr.teacher_id 
                                      AND a.subject = cr.course_title
                                      AND a.date BETWEEN :start_date AND :end_date
                LEFT JOIN excuse_slips es ON es.student_id = cr.student_id 
                                         AND es.teacher_id = cr.teacher_id 
                                         AND es.date_of_absence = a.date
                                         AND es.subject = cr.course_title
                WHERE cr.teacher_id = :teacher_id
            ";

            if ($section !== '' && strtolower($section) !== 'all') {
                $rosterSql .= " AND cr.section = :roster_section";
                $rosterParams[':roster_section'] = $section;
            }

            $rosterSql .= " GROUP BY cr.student_id, cr.first_name, cr.last_name, u.student_id, u.email, cr.section, cr.course_code, cr.course_title ORDER BY cr.last_name ASC, cr.first_name ASC";

            $stmt = $db->prepare($rosterSql);
            $stmt->execute($rosterParams);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Filter candidates based on threshold
            $candidates = [];
            foreach ($rows as $r) {
                $presentCount = (int)$r['present_count'];
                $tardyCount   = (int)$r['tardy_count'];
                $absentCount  = (int)$r['absent_count'];
                $excusedCount = (int)$r['excused_count'];

                // Effective sessions for this student: maximum of total sessions held or recorded
                $baseSessions = max($totalSessionsHeld, (int)$r['sessions_recorded']);
                if ($baseSessions === 0) {
                    continue; // No sessions held in this window
                }

                // If student missed recording on held days, those count as unrecorded/absent
                $unrecordedDays = max(0, $baseSessions - ($presentCount + $tardyCount + $absentCount + $excusedCount));
                $effectiveAbsent = $absentCount + $unrecordedDays;

                $attendanceRate = round(($presentCount / $baseSessions) * 100, 1);
                $effectiveRate = round((($presentCount + $excusedCount) / $baseSessions) * 100, 1);

                $isEligible = false;
                if ($threshold === 100) {
                    // Flawless 100%: All sessions present, 0 tardies, 0 absences
                    $isEligible = ($presentCount === $baseSessions) && ($tardyCount === 0) && ($effectiveAbsent === 0);
                } elseif ($threshold === 98) {
                    // 98%+ High Honors: At least 98% attendance (excused count), max 1 tardy
                    $isEligible = ($effectiveRate >= 98.0) && ($tardyCount <= 1);
                }

                if ($isEligible) {
                    $candidates[] = [
                        'student_id'      => (int)$r['student_id'],
                        'student_number'  => (string)$r['student_number'],
                        'full_name'       => $r['full_name'],
                        'first_name'      => $r['first_name'],
                        'last_name'       => $r['last_name'],
                        'email'           => $r['email'],
                        'section'         => $r['section'],
                        'course_code'     => $r['course_code'],
                        'course_title'    => $r['course_title'],
                        'total_sessions'  => $baseSessions,
                        'present_count'   => $presentCount,
                        'tardy_count'     => $tardyCount,
                        'absent_count'    => $effectiveAbsent,
                        'excused_count'   => $excusedCount,
                        'attendance_rate' => $attendanceRate,
                        'effective_rate'  => $effectiveRate
                    ];
                }
            }

            // 4. Sort candidates: highest attendance rate first, then fewest tardies, then alphabetical
            usort($candidates, function($a, $b) {
                if ($b['effective_rate'] !== $a['effective_rate']) {
                    return $b['effective_rate'] <=> $a['effective_rate'];
                }
                if ($a['tardy_count'] !== $b['tardy_count']) {
                    return $a['tardy_count'] <=> $b['tardy_count'];
                }
                return strcmp($a['last_name'], $b['last_name']);
            });

            // Assign ranks (1, 2, 3...)
            $rank = 1;
            foreach ($candidates as &$c) {
                $c['rank'] = $rank++;
            }
            unset($c);

            // 5. CSV Export streaming if requested
            if ($isExportCsv) {
                if (!headers_sent()) {
                    header('Content-Type: text/csv; charset=utf-8');
                    header('Content-Disposition: attachment; filename="perfect_attendance_awards_' . $startDate . '_to_' . $endDate . '.csv"');
                    header('Pragma: no-cache');
                    header('Expires: 0');
                }

                $output = fopen('php://output', 'w');
                fputcsv($output, [
                    'Rank',
                    'Student Number',
                    'Student Name',
                    'Email',
                    'Section',
                    'Course Title',
                    'Total Sessions',
                    'Present Days',
                    'Tardy Days',
                    'Absent Days',
                    'Excused Days',
                    'Attendance Rate'
                ]);

                foreach ($candidates as $cand) {
                    $row = [
                        $cand['rank'],
                        $cand['student_number'],
                        $cand['full_name'],
                        $cand['email'],
                        $cand['section'],
                        $cand['course_title'],
                        $cand['total_sessions'],
                        $cand['present_count'],
                        $cand['tardy_count'],
                        $cand['absent_count'],
                        $cand['excused_count'],
                        $cand['effective_rate'] . '%'
                    ];
                    $escaped = array_map([$this, 'escapeCsvCell'], $row);
                    fputcsv($output, $escaped);
                }
                fclose($output);
                exit;
            }

            // 6. JSON Response
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'status'              => 'success',
                'teacher_id'          => $teacherId,
                'section'             => $section,
                'start_date'          => $startDate,
                'end_date'            => $endDate,
                'threshold'           => $threshold,
                'total_sessions_held' => $totalSessionsHeld,
                'total_eligible'      => count($candidates),
                'candidates'          => $candidates
            ]);
            exit;

        } catch (Exception $e) {
            if (!headers_sent()) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
            }
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to calculate awards: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * POST/GET /api/teacher/awards/seed-sample
     * Generates a realistic sample dataset of 10 class sessions for testing.
     */
    public function apiSeedAwardsSample(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            require_once dirname(__DIR__, 2) . '/database/seed_awards_sample.php';
            $result = seedAwardsSampleData($db, $teacherId);

            echo json_encode([
                'status'        => 'success',
                'message'       => 'Test sample data generated successfully (10 held sessions for September 2026).',
                'sessions_held' => $result['sessions_held'] ?? 10,
                'section'       => $result['section'] ?? '31001',
                'start_date'    => $result['start_date'] ?? '2026-09-01',
                'end_date'      => $result['end_date'] ?? '2026-09-30'
            ]);
            exit;
        } catch (Exception $e) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to seed sample: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * POST /api/teacher/awards/update-candidate
     * Allows teacher to correct candidate student details (name, student number, email, section, course title)
     */
    public function apiUpdateAwardCandidate(): void {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }

        try {
            $db = Database::getConnection();
            $teacherId = $this->resolveTeacherId($db);

            $studentId = (int)($_POST['student_id'] ?? 0);
            if ($studentId <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid student ID.']);
                exit;
            }

            $firstName = trim($_POST['first_name'] ?? '');
            $lastName  = trim($_POST['last_name'] ?? '');
            $studentNumber = trim($_POST['student_number'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $section = trim($_POST['section'] ?? '');
            $courseTitle = trim($_POST['course_title'] ?? '');

            if ($firstName === '' || $lastName === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'First name and last name are required.']);
                exit;
            }

            // Update users table
            $userUpdateSql = "UPDATE users SET first_name = :fn, last_name = :ln";
            $userParams = [
                ':fn'  => $firstName,
                ':ln'  => $lastName,
                ':uid' => $studentId
            ];

            if ($studentNumber !== '') {
                $userUpdateSql .= ", student_id = :sn";
                $userParams[':sn'] = (int)$studentNumber;
            }
            if ($email !== '') {
                $userUpdateSql .= ", email = :em";
                $userParams[':em'] = $email;
            }
            $userUpdateSql .= " WHERE user_id = :uid";
            $stmt = $db->prepare($userUpdateSql);
            $stmt->execute($userParams);

            // Update class_roster table for this teacher and student
            $rosterUpdateSql = "UPDATE class_roster SET first_name = :fn, last_name = :ln";
            $rosterParams = [
                ':fn'  => $firstName,
                ':ln'  => $lastName,
                ':sid' => $studentId,
                ':tid' => $teacherId
            ];
            if ($section !== '') {
                $rosterUpdateSql .= ", section = :sec";
                $rosterParams[':sec'] = $section;
            }
            if ($courseTitle !== '') {
                $rosterUpdateSql .= ", course_title = :ct";
                $rosterParams[':ct'] = $courseTitle;
            }
            $rosterUpdateSql .= " WHERE student_id = :sid AND teacher_id = :tid";
            $stmtRoster = $db->prepare($rosterUpdateSql);
            $stmtRoster->execute($rosterParams);

            echo json_encode([
                'status'         => 'success',
                'message'        => 'Student information updated successfully.',
                'student_id'     => $studentId,
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'full_name'      => $lastName . ', ' . $firstName,
                'student_number' => $studentNumber,
                'email'          => $email,
                'section'        => $section,
                'course_title'   => $courseTitle
            ]);
            exit;

        } catch (Exception $e) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode([
                'status'  => 'error',
                'message' => 'Failed to update student info: ' . $e->getMessage()
            ]);
            exit;
        }
    }
}


