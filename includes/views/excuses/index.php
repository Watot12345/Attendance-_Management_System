<?php
// Forward query parameters if any (e.g. ?tab=submit)
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: /Attendance _Management_System/includes/views/dashboard/excuse-slips.php' . $query);
exit;

