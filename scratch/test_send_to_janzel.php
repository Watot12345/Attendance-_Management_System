<?php
require_once __DIR__ . '/../includes/core/Mailer.php';

echo "Sending test OTP to janzeldols@gmail.com...\n";
$res = Mailer::sendOtp('janzeldols@gmail.com', '987654', 'login', 'jj Dela Cruz');
print_r($res);
