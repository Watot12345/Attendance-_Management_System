<?php
require_once __DIR__ . '/../includes/core/Cache.php';
require_once __DIR__ . '/../includes/core/Database.php';
require_once __DIR__ . '/../includes/controllers/AnalyticsController.php';
require_once __DIR__ . '/../includes/controllers/StudentController.php';
require_once __DIR__ . '/../includes/controllers/TeacherController.php';
require_once __DIR__ . '/../includes/controllers/DashboardController.php';

echo "=== BENCHMARK SPEED TEST ===" . PHP_EOL;

// 1. Analytics ML Payload
$t0 = microtime(true);
$ac = new AnalyticsController();
$data = $ac->getMlPayload();
$t1 = microtime(true);
echo "1. Analytics ML Payload: " . round(($t1 - $t0) * 1000, 2) . " ms" . PHP_EOL;

// 2. Student Calendar query (Cold vs Warm)
Cache::forget('student_calendar_1_' . date('Y') . '_' . (int)date('m'));
$t0 = microtime(true);
$sc = new StudentController();
$cal1 = $sc->getStudentCalendarData(1, (int)date('Y'), (int)date('m'));
$t1 = microtime(true);
echo "2. Student Calendar (Consolidated Cold Query): " . round(($t1 - $t0) * 1000, 2) . " ms" . PHP_EOL;

$t0 = microtime(true);
$cal2 = $sc->getStudentCalendarData(1, (int)date('Y'), (int)date('m'));
$t1 = microtime(true);
echo "3. Student Calendar (Warm Cache Hit): " . round(($t1 - $t0) * 1000, 4) . " ms" . PHP_EOL;

// 4. Teacher Dashboard Overview (Cold vs Warm)
Cache::forget('teacher_overview_2');
$t0 = microtime(true);
$tover1 = TeacherController::getTeacherDashboardOverview(2);
$t1 = microtime(true);
echo "4. Teacher Dashboard (Batch Cold Query): " . round(($t1 - $t0) * 1000, 2) . " ms" . PHP_EOL;

$t0 = microtime(true);
$tover2 = TeacherController::getTeacherDashboardOverview(2);
$t1 = microtime(true);
echo "5. Teacher Dashboard (Warm Cache Hit): " . round(($t1 - $t0) * 1000, 4) . " ms" . PHP_EOL;

// 6. Admin Dashboard Overview (Cold vs Warm)
Cache::forget('dashboard_overview_data');
$t0 = microtime(true);
$aover1 = DashboardController::getOverviewData();
$t1 = microtime(true);
echo "6. Admin Overview (Cold Query): " . round(($t1 - $t0) * 1000, 2) . " ms" . PHP_EOL;

$t0 = microtime(true);
$aover2 = DashboardController::getOverviewData();
$t1 = microtime(true);
echo "7. Admin Overview (Warm Cache Hit): " . round(($t1 - $t0) * 1000, 4) . " ms" . PHP_EOL;

echo "=== BENCHMARK COMPLETED SUCCESSFULLY ===" . PHP_EOL;
