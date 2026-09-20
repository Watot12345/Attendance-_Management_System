<?php
require_once __DIR__ . '/../includes/core/Mailer.php';

echo "=== TESTING LIVE SMTP WITH CREDENTIALS ===\n";
$res = Mailer::sendOtp('managementattendance6@gmail.com', '123456', 'login', 'Test Admin');
echo "Result:\n";
print_r($res);
