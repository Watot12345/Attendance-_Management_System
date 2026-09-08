<?php
// Forward query parameters or default to overview tab on analytics.php
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '?tab=overview';
header('Location: ' . url('dashboard/analytics' . $query));
exit;
