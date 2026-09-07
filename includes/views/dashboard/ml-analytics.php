<?php
// Forward query parameters or redirect to unified analytics.php
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: /Attendance _Management_System/includes/views/dashboard/analytics.php' . $query);
exit;
