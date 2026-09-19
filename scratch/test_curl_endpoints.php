<?php
$endpoints = [
    'http://localhost:8000/api/analytics/all',
    'http://localhost:8000/api/dashboard/overview',
    'http://localhost:8000/dashboard/analytics',
    'http://localhost:8000/teacher/consecutive-absences',
    'http://localhost:8000/calendar'
];

echo "=== HTTP ENDPOINTS LATENCY TEST ===" . PHP_EOL;

foreach ($endpoints as $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $duration = (microtime(true) - $start) * 1000;
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $size = strlen($response);
    curl_close($ch);

    echo sprintf("[HTTP %d] %s -> %.2f ms (Size: %d bytes)\n", $httpCode, $url, $duration, $size);
}

echo "=== TEST COMPLETED ===" . PHP_EOL;
