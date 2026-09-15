<?php
$jsonStr = file_get_contents('http://127.0.0.1:8000/exports?ajax=1');
if (!$jsonStr) {
    echo "FAILED: Could not fetch AJAX endpoint\n";
    exit(1);
}

$data = json_decode($jsonStr, true);
if (!$data || ($data['status'] ?? '') !== 'success') {
    echo "FAILED: Invalid JSON returned\n";
    print_r($jsonStr);
    exit(1);
}

echo "AJAX JSON ENDPOINT VERIFIED SUCCESS:\n";
echo "Total Students: " . ($data['summary']['totalStudents'] ?? 'N/A') . "\n";
echo "Total Present: " . ($data['summary']['totalPresent'] ?? 'N/A') . "\n";
echo "Unique Courses: " . implode(', ', $data['summary']['uniqueCourses'] ?? []) . "\n";
echo "Unique Majors: " . implode(', ', $data['summary']['allMajors'] ?? []) . "\n";
echo "Records Count: " . count($data['data'] ?? []) . "\n";
