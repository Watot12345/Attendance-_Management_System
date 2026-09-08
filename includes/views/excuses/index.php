<?php
// Forward query parameters if any (e.g. ?tab=submit)
$query = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ' . url('dashboard/excuse-slips' . $query));
exit;

