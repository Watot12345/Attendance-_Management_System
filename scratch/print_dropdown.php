<?php
$html = file_get_contents('http://localhost:8000/student/excuse-slips');
preg_match('/<select id="excuse-subject"[^>]*>(.*?)<\/select>/is', $html, $m);
echo "Dropdown content:\n" . ($m[1] ?? 'NOT FOUND') . "\n";
