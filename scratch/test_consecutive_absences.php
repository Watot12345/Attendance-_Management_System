<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user'] = ['user_id' => 2, 'role' => 'teacher', 'first_name' => 'Prof.', 'last_name' => 'Ramirez'];
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'teacher';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/teacher/consecutive-absences';

ob_start();
require_once __DIR__ . '/../includes/views/teacher/consecutive-absences.php';
$output = ob_get_clean();

echo "Consecutive Absences view rendered successfully!\n";
echo "Output length: " . strlen($output) . " bytes\n";
echo "Contains '3+ Consecutive Absence': " . (strpos($output, '3+ Consecutive Absence') !== false ? 'YES' : 'NO') . "\n";
