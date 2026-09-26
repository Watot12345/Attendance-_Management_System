<?php
$file = __DIR__ . '/../includes/views/teacher/classes.php';
$content = file_get_contents($file);
$content = str_replace("\r\n", "\n", $content);
file_put_contents($file, $content);
echo "OK: " . strlen($content) . "\n";
