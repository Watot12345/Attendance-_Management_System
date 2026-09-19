<?php
/**
 * DashboardController — includes/controllers/DashboardController.php
 * Handles Institutional Overview Dashboard.
 * Aggregates high-level metrics across users, teachers, attendance, excuse_slips, parent_alerts, class_roster, and audit_logs.
 */

require_once dirname(__DIR__) . '/core/Database.php';

class DashboardController {

    /**
     * Get aggregate overview statistics directly from the database
     */
    public static function getOverviewData(): array {
        $db = Database::getConnection();

        // 1. Students: Total, active, inactive from users table
        $studentsData = ['total' => 0, 'active' => 0, 'inactive' => 0];
        try {
            $sStmt = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(status = 'active') AS active_count,
                    SUM(status != 'active') AS inactive_count
                FROM `users` 
                WHERE `role` = 'student'
            ");
            $sRes = $sStmt->fetch(PDO::FETCH_ASSOC);
            if ($sRes) {
                $studentsData = [
                    'total'    => (int)($sRes['total'] ?? 0),
                    'active'   => (int)($sRes['active_count'] ?? 0),
                    'inactive' => (int)($sRes['inactive_count'] ?? 0)
                ];
            }
        } catch (Exception $e) {}

        // 2. Teachers: Total active and inactive from teachers / users table
        $teachersData = ['total' => 0, 'active' => 0, 'inactive' => 0];
        try {
            $stmt = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(status = 'active') AS active_count,
                    SUM(status = 'inactive') AS inactive_count
                FROM `teachers`
            ");
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res && (int)$res['total'] > 0) {
                $teachersData = [
                    'total'    => (int)($res['total'] ?? 0),
                    'active'   => (int)($res['active_count'] ?? 0),
                    'inactive' => (int)($res['inactive_count'] ?? 0)
                ];
            } else {
                // Fallback to users table where role = teacher
                $tStmt = $db->query("
                    SELECT 
                        COUNT(*) AS total,
                        SUM(status = 'active') AS active_count,
                        SUM(status != 'active') AS inactive_count
                    FROM `users`
                    WHERE `role` = 'teacher'
                ");
                $tRes = $tStmt->fetch(PDO::FETCH_ASSOC);
                if ($tRes) {
                    $teachersData = [
                        'total'    => (int)($tRes['total'] ?? 0),
                        'active'   => (int)($tRes['active_count'] ?? 0),
                        'inactive' => (int)($tRes['inactive_count'] ?? 0)
                    ];
                }
            }
        } catch (Exception $e) {}

        // 3. Attendance Metrics: Today, Latest Session, and Overall Campus Rate
        $attendanceToday = [
            'day'             => date('Y-m-d'),
            'total_records'   => 0,
            'present_count'   => 0,
            'tardy_count'     => 0,
            'absent_count'    => 0,
            'rate_percentage' => 0.0,
            'is_today'        => false,
            'session_label'   => 'Today\'s Campus Rate'
        ];

        $attendanceOverall = [
            'total_records'   => 0,
            'present_count'   => 0,
            'tardy_count'     => 0,
            'absent_count'    => 0,
            'rate_percentage' => 0.0,
            'latest_date'     => null
        ];

        try {
            // First check if records exist for CURRENT_DATE()
            $stmt = $db->prepare("
                SELECT 
                    COUNT(*) AS total_records,
                    SUM(status = 'present') AS present_count,
                    SUM(status = 'tardy') AS tardy_count,
                    SUM(status = 'absent') AS absent_count
                FROM `attendance`
                WHERE `date` = CURRENT_DATE()
            ");
            $stmt->execute();
            $rowToday = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($rowToday && (int)$rowToday['total_records'] > 0) {
                $tTotal = (int)$rowToday['total_records'];
                $tPres  = (int)($rowToday['present_count'] ?? 0);
                $tTar   = (int)($rowToday['tardy_count'] ?? 0);
                $tAbs   = (int)($rowToday['absent_count'] ?? 0);
                $tRate  = round((($tPres + $tTar) / $tTotal) * 100, 1);
                $attendanceToday = [
                    'day'             => date('Y-m-d'),
                    'total_records'   => $tTotal,
                    'present_count'   => $tPres,
                    'tardy_count'     => $tTar,
                    'absent_count'    => $tAbs,
                    'rate_percentage' => $tRate,
                    'is_today'        => true,
                    'session_label'   => 'Today\'s Campus Rate'
                ];
            } else {
                // If 0 records today, fetch the latest held session from attendance
                $latestStmt = $db->query("
                    SELECT 
                        date,
                        COUNT(*) AS total_records,
                        SUM(status = 'present') AS present_count,
                        SUM(status = 'tardy') AS tardy_count,
                        SUM(status = 'absent') AS absent_count
                    FROM `attendance`
                    GROUP BY date
                    ORDER BY date DESC
                    LIMIT 1
                ");
                $rowLatest = $latestStmt->fetch(PDO::FETCH_ASSOC);
                if ($rowLatest) {
                    $lTotal = (int)$rowLatest['total_records'];
                    $lPres  = (int)($rowLatest['present_count'] ?? 0);
                    $lTar   = (int)($rowLatest['tardy_count'] ?? 0);
                    $lAbs   = (int)($rowLatest['absent_count'] ?? 0);
                    $lRate  = $lTotal > 0 ? round((($lPres + $lTar) / $lTotal) * 100, 1) : 0.0;
                    $lDateFormatted = date('M j, Y', strtotime($rowLatest['date']));
                    $attendanceToday = [
                        'day'             => $rowLatest['date'],
                        'total_records'   => $lTotal,
                        'present_count'   => $lPres,
                        'tardy_count'     => $lTar,
                        'absent_count'    => $lAbs,
                        'rate_percentage' => $lRate,
                        'is_today'        => false,
                        'session_label'   => 'Latest Session (' . $lDateFormatted . ')'
                    ];
                }
            }

            // All-time attendance aggregates
            $allStmt = $db->query("
                SELECT 
                    COUNT(*) AS total_records,
                    SUM(status = 'present') AS present_count,
                    SUM(status = 'tardy') AS tardy_count,
                    SUM(status = 'absent') AS absent_count,
                    MAX(date) AS latest_date
                FROM `attendance`
            ");
            $rowAll = $allStmt->fetch(PDO::FETCH_ASSOC);
            if ($rowAll && (int)$rowAll['total_records'] > 0) {
                $aTotal = (int)$rowAll['total_records'];
                $aPres  = (int)($rowAll['present_count'] ?? 0);
                $aTar   = (int)($rowAll['tardy_count'] ?? 0);
                $aAbs   = (int)($rowAll['absent_count'] ?? 0);
                $attendanceOverall = [
                    'total_records'   => $aTotal,
                    'present_count'   => $aPres,
                    'tardy_count'     => $aTar,
                    'absent_count'    => $aAbs,
                    'rate_percentage' => round((($aPres + $aTar) / $aTotal) * 100, 1),
                    'latest_date'     => $rowAll['latest_date']
                ];
            }
        } catch (Exception $e) {}

        // 4. Excuse Slips Metrics
        $excuseSlipsData = [
            'total'    => 0,
            'pending'  => 0,
            'approved' => 0,
            'declined' => 0
        ];
        try {
            $eStmt = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(status = 'pending') AS pending_count,
                    SUM(status = 'approved') AS approved_count,
                    SUM(status = 'declined') AS declined_count
                FROM `excuse_slips`
            ");
            $eRes = $eStmt->fetch(PDO::FETCH_ASSOC);
            if ($eRes) {
                $excuseSlipsData = [
                    'total'    => (int)($eRes['total'] ?? 0),
                    'pending'  => (int)($eRes['pending_count'] ?? 0),
                    'approved' => (int)($eRes['approved_count'] ?? 0),
                    'declined' => (int)($eRes['declined_count'] ?? 0)
                ];
            }
        } catch (Exception $e) {}

        // 5. Parent alerts
        $alertsTodayCount = 0;
        $alertsTotalCount = 0;
        try {
            $stmt = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(alert_date = CURRENT_DATE() OR DATE(created_at) = CURRENT_DATE()) AS today_count
                FROM `parent_alerts`
            ");
            $aRow = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($aRow) {
                $alertsTotalCount = (int)($aRow['total'] ?? 0);
                $alertsTodayCount = (int)($aRow['today_count'] ?? 0);
            }
        } catch (Exception $e) {}

        // 6. Academic Roster Overview
        $academicData = [
            'total_sections' => 0,
            'total_courses'  => 0,
            'total_students' => 0,
            'sections'       => []
        ];
        try {
            $rStmt = $db->query("
                SELECT 
                    section,
                    course_code,
                    course_title,
                    COUNT(DISTINCT student_id) AS student_count
                FROM `class_roster`
                GROUP BY section, course_code, course_title
                ORDER BY section ASC
            ");
            $secList = $rStmt->fetchAll(PDO::FETCH_ASSOC);
            $academicData['sections'] = $secList;
            $academicData['total_sections'] = count(array_unique(array_column($secList, 'section')));
            $academicData['total_courses']  = count(array_unique(array_column($secList, 'course_code')));
            $academicData['total_students'] = array_sum(array_column($secList, 'student_count'));
        } catch (Exception $e) {}

        // 7. Live Recent Attendance Check-ins (latest 6 records with student details)
        $recentAttendance = [];
        try {
            $feedStmt = $db->query("
                SELECT 
                    a.attendance_id,
                    a.date,
                    a.time,
                    a.subject,
                    a.status,
                    CONCAT(u.first_name, ' ', u.last_name) AS student_name,
                    COALESCE(u.student_id, '230110001') AS student_number,
                    COALESCE(cr.section, 'Section 31001') AS section,
                    CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
                FROM `attendance` a
                JOIN `users` u ON u.user_id = a.student_id
                LEFT JOIN `users` t ON t.user_id = a.teacher_id
                LEFT JOIN `class_roster` cr ON cr.student_id = a.student_id AND cr.teacher_id = a.teacher_id AND cr.course_title = a.subject
                ORDER BY a.date DESC, a.time DESC, a.attendance_id DESC
                LIMIT 6
            ");
            $recentAttendance = $feedStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // 8. Recent Audit Logs (latest 5 audit activities)
        $recentAuditLogs = [];
        try {
            $alStmt = $db->query("
                SELECT 
                    al.audit_log_id,
                    al.action,
                    al.description,
                    al.created_at,
                    al.reference_type,
                    al.reference_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS actor_name,
                    u.role AS actor_role
                FROM `audit_logs` al
                LEFT JOIN `users` u ON u.user_id = al.user_id
                ORDER BY al.audit_log_id DESC
                LIMIT 5
            ");
            $recentAuditLogs = $alStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // 9. Recent faculty imports from faculty_import_logs table
        $recentImports = [];
        try {
            $stmt = $db->query("
                SELECT id, filename, imported_by, total_rows, inserted_rows, failed_rows, imported_at 
                FROM `faculty_import_logs` 
                ORDER BY imported_at DESC 
                LIMIT 5
            ");
            $recentImports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        return [
            'students'           => $studentsData,
            'teachers'           => $teachersData,
            'attendance_today'   => $attendanceToday,
            'attendance_overall' => $attendanceOverall,
            'excuse_slips'       => $excuseSlipsData,
            'alerts_today'       => $alertsTodayCount,
            'alerts_total'       => $alertsTotalCount,
            'academic'           => $academicData,
            'recent_attendance'  => $recentAttendance,
            'recent_audit_logs'  => $recentAuditLogs,
            'recent_imports'     => $recentImports
        ];
    }

    /**
     * API: GET /api/dashboard/overview
     */
    public function apiOverview(): void {
        header('Content-Type: application/json');
        try {
            $data = self::getOverviewData();
            echo json_encode([
                'status' => 'success',
                'data'   => $data
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'status'  => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}

