<?php
// Mock session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'Admin';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/exports';

ob_start();
require_once __DIR__ . '/../includes/views/admin/reports.php';
$output = ob_get_clean();

echo "Reports view rendered successfully!\n";
echo "Output length: " . strlen($output) . " bytes\n";
echo "Contains 'Class Roster &amp; Attendance Registry Ledger': " . (strpos($output, 'Class Roster &amp; Attendance Registry Ledger') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'chartAttendanceStatus': " . (strpos($output, 'chartAttendanceStatus') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'exportToExcel': " . (strpos($output, 'exportToExcel') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'exportToWord': " . (strpos($output, 'exportToWord') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'exportToPdf': " . (strpos($output, 'exportToPdf') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'In Users Table?': " . (strpos($output, 'In Users Table?') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'AMS_EXPORTS_CACHE': " . (strpos($output, 'AMS_EXPORTS_CACHE') !== false ? 'YES' : 'NO') . "\n";
