<?php
$html = file_get_contents('http://localhost:8000/teacher/dashboard');
preg_match_all('/(<b>)?Warning(<\/b>)?:.*?(<br>|\n)/i', $html, $matches);
print_r($matches[0]);
