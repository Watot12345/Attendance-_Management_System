<?php
$res = file_get_contents('http://localhost:8000/api/analytics/overview');
$data = json_decode($res, true);
echo "=== Grade / Year Level Comparison in Overview API ===\n";
print_r($data['overview']['grade_comparison']);
