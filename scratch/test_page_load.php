<?php
$url = 'http://localhost:8000/teacher/live-session';
$res = file_get_contents($url);
echo "HTTP Status: " . ($http_response_header[0] ?? 'None') . "\n";
echo "Page HTML Length: " . strlen($res) . "\n";
if (strpos($res, 'Fatal error') !== false || strpos($res, 'Parse error') !== false) {
    echo "❌ Contains PHP error!\n";
} else {
    echo "✅ Clean response without PHP errors!\n";
}
