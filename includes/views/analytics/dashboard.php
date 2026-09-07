<?php
// Forward query parameters or default to overview tab on analytics.php
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '?tab=overview';
header('Location: /Attendance _Management_System/includes/views/dashboard/analytics.php' . $query);
exit;
