<?php
$html = file_get_contents(__DIR__ . '/../includes/views/auth/login.php');
// Replace PHP tags with dummy values for JS checking
$html = preg_replace('/<\?php.*?\?>/s', '"/dummy"', $html);
preg_match('/<script>(.*?)<\/script>/s', $html, $matches);
if (!empty($matches[1])) {
    file_put_contents(__DIR__ . '/test_login_script.js', $matches[1]);
    echo "Extracted script successfully\n";
} else {
    echo "No script tag found\n";
}
