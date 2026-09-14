<?php
/**
 * DashboardController — includes/controllers/DashboardController.php
 * Handles Institutional Overview Dashboard.
 * Aggregates high-level metrics across teachers, v_attendance_summary, parent_alerts, and faculty_import_logs.
 */

require_once dirname(__DIR__) . '/core/Database.php';

class DashboardController {

    /**
     * Get aggregate overview statistics
     */
    public static function getOverviewData(): array {
        $db = Database::getConnection();

        // 1. Teachers: Total active and inactive
        $teachersData = ['total' => 0, 'active' => 0, 'inactive' => 0];
        try {
            $stmt = $db->query("
                SELECT 
                    COUNT(*) AS total,
                    SUM(status = 'active') AS active_count,
                    SUM(status = 'inactive') AS inactive_count
                FROM `teachers`
            ");
            $res = $stmt->fetch();
            if ($res) {
                $teachersData = [
                    'total'    => (int)($res['total'] ?? 0),
                    'active'   => (int)($res['active_count'] ?? 0),
                    'inactive' => (int)($res['inactive_count'] ?? 0)
                ];
            }
        } catch (Exception $e) {}

        // 2. Today's attendance rate via v_attendance_summary view
        $attendanceToday = [
            'day'             => date('Y-m-d'),
            'total_records'   => 0,
            'present_count'   => 0,
            'absent_count'    => 0,
            'rate_percentage' => 0.0
        ];
        try {
            $stmt = $db->prepare("SELECT * FROM `v_attendance_summary` WHERE `day` = CURRENT_DATE() LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row) {
                $total = (int)($row['total_records'] ?? 0);
                $present = (int)($row['present_count'] ?? 0);
                $absent = (int)($row['absent_count'] ?? 0);
                $rate = $total > 0 ? round(($present / $total) * 100, 1) : 0.0;
                $attendanceToday = [
                    'day'             => $row['day'] ?? date('Y-m-d'),
                    'total_records'   => $total,
                    'present_count'   => $present,
                    'absent_count'    => $absent,
                    'rate_percentage' => $rate
                ];
            }
        } catch (Exception $e) {}

        // 3. Alerts sent today from parent_alerts table
        $alertsTodayCount = 0;
        try {
            // Check if parent_alerts exists
            $stmt = $db->query("
                SELECT COUNT(*) FROM `parent_alerts` 
                WHERE `alert_date` = CURRENT_DATE() 
                   OR DATE(`created_at`) = CURRENT_DATE()
            ");
            $alertsTodayCount = (int)$stmt->fetchColumn();
        } catch (Exception $e) {}

        // 4. Recent faculty imports from faculty_import_logs table
        $recentImports = [];
        try {
            $stmt = $db->query("
                SELECT id, filename, imported_by, total_rows, inserted_rows, failed_rows, imported_at 
                FROM `faculty_import_logs` 
                ORDER BY imported_at DESC 
                LIMIT 5
            ");
            $recentImports = $stmt->fetchAll();
        } catch (Exception $e) {}

        return [
            'teachers'         => $teachersData,
            'attendance_today' => $attendanceToday,
            'alerts_today'     => $alertsTodayCount,
            'recent_imports'   => $recentImports
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
