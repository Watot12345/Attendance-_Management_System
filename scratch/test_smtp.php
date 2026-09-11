<?php
$env = parse_ini_file('.env');
$email = $env['Email'] ?? 'bcpattendance@gmail.com';
$appPassword = str_replace(' ', '', $env['APP_PASSWORD'] ?? '');

echo "Testing SMTP to smtp.gmail.com:587 for $email...\n";

function sendSmtpMail($to, $subject, $body, $from, $appPassword) {
    $timeout = 10;
    $smtp = stream_socket_client("tcp://smtp.gmail.com:587", $errno, $errstr, $timeout);
    if (!$smtp) {
        return "Socket connection error: $errstr ($errno)";
    }

    $response = fgets($smtp, 515);
    if (!str_starts_with($response, '220')) {
        return "Server greeting error: $response";
    }

    $sendCmd = function($cmd, $expectedCode) use ($smtp) {
        fwrite($smtp, $cmd . "\r\n");
        $res = '';
        while ($line = fgets($smtp, 515)) {
            $res .= $line;
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        if (!str_starts_with($res, (string)$expectedCode)) {
            throw new Exception("Command '$cmd' failed with response: $res");
        }
        return $res;
    };

    try {
        $sendCmd("EHLO localhost", 250);
        $sendCmd("STARTTLS", 220);
        stream_socket_enable_crypto($smtp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $sendCmd("EHLO localhost", 250);
        $sendCmd("AUTH LOGIN", 334);
        $sendCmd(base64_encode($from), 334);
        $sendCmd(base64_encode($appPassword), 235);
        $sendCmd("MAIL FROM: <$from>", 250);
        $sendCmd("RCPT TO: <$to>", 250);
        $sendCmd("DATA", 354);

        $headers = [
            "From: BCP Attendance System <$from>",
            "To: <$to>",
            "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=UTF-8",
            "Content-Transfer-Encoding: 8bit",
            "Date: " . date('r')
        ];

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n";
        fwrite($smtp, $message);
        $res = fgets($smtp, 515);
        if (!str_starts_with($res, '250')) {
            throw new Exception("DATA send failed: $res");
        }
        $sendCmd("QUIT", 221);
        fclose($smtp);
        return "SUCCESS: Email sent successfully!";
    } catch (Exception $e) {
        fclose($smtp);
        return "ERROR: " . $e->getMessage();
    }
}

$res = sendSmtpMail($email, "BCP Attendance Login OTP Test", "<h2>Test OTP</h2><p>Your verification code is: <b>123456</b></p>", $email, $appPassword);
echo $res . "\n";
