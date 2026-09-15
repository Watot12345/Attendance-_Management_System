<?php
require_once __DIR__ . '/../includes/core/Router.php';
require_once __DIR__ . '/../includes/core/Database.php';

$_SESSION['user'] = [
    'user_id' => 1,
    'first_name' => 'Juan',
    'last_name' => 'Dela Cruz',
    'role' => 'student',
    'student_id' => '2026-00123'
];

ob_start();
include __DIR__ . '/../includes/views/student/excuse-slips.php';
$studentOut = ob_get_clean();

echo "Student Excuse Slips Rendered: " . strlen($studentOut) . " bytes\n";
echo "Contains 'Excuse Slips &amp; Absence Clearance': " . (strpos($studentOut, 'Excuse Slips &amp; Absence Clearance') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'kpi-pending-count': " . (strpos($studentOut, 'kpi-pending-count') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'filter-search': " . (strpos($studentOut, 'filter-search') !== false ? 'YES' : 'NO') . "\n";

ob_start();
include __DIR__ . '/../includes/views/dashboard/excuse-slips.php';
$teacherOut = ob_get_clean();

echo "Teacher Excuse Slips Rendered: " . strlen($teacherOut) . " bytes\n";
echo "Contains 'kpi-pending': " . (strpos($teacherOut, 'kpi-pending') !== false ? 'YES' : 'NO') . "\n";
echo "Contains 'search-student': " . (strpos($teacherOut, 'search-student') !== false ? 'YES' : 'NO') . "\n";
