<?php
$feed = file_get_contents('http://localhost:8000/api/teacher/attendance/live-feed');
echo "Feed response:\n";
$data = json_decode($feed, true);
print_r($data);
