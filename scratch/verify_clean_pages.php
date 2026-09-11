<?php
$pages = [
    'http://localhost:8000/login',
    'http://localhost:8000/teacher/dashboard',
    'http://localhost:8000/student/calendar',
    'http://localhost:8000/dashboard',
];

foreach ($pages as $page) {
    $content = file_get_contents($page);
    if (stripos($content, 'headers have already been sent') !== false || stripos($content, 'session cannot be started') !== false) {
        echo "❌ PHP Session Warning found on $page\n";
    } else {
        echo "✅ Clean response (zero PHP session warnings): $page\n";
    }
}
