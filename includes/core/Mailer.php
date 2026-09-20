<?php
/**
 * SMTP Mailer Utility — includes/core/Mailer.php
 * Handles direct SMTP delivery via Gmail / custom SMTP servers using credentials in .env.
 */

class Mailer {
    private static array $envCache = [];

    /**
     * Load environment variables from .env or server environment with case insensitivity
     */
    public static function getEnv(string $key, string $default = ''): string {
        if (empty(self::$envCache)) {
            $envPath = dirname(__DIR__, 2) . '/.env';
            if (file_exists($envPath)) {
                $lines = @file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines) {
                    foreach ($lines as $line) {
                        $line = trim($line);
                        if (empty($line) || str_starts_with($line, '#')) continue;
                        if (strpos($line, '=') !== false) {
                            list($k, $v) = explode('=', $line, 2);
                            self::$envCache[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
                        }
                    }
                }
            }
        }

        // 1. Direct key match
        if (isset(self::$envCache[$key]) && self::$envCache[$key] !== '') {
            return self::$envCache[$key];
        }
        if (getenv($key) !== false && getenv($key) !== '') {
            return getenv($key);
        }
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }

        // 2. Case-insensitive search across all sources
        $allEnv = array_merge($_SERVER, $_ENV, self::$envCache);
        foreach ($allEnv as $k => $v) {
            if (strcasecmp($k, $key) === 0 && !empty($v)) {
                return (string)$v;
            }
        }

        return $default;
    }

    /**
     * Send an HTML Email via SMTP with automatic Port 465 (SSL) -> Port 587 (STARTTLS) fallback
     */
    public static function send(string $toEmail, string $subject, string $htmlBody): array {
        // Resolve SMTP credentials (supports Email, EMAIL, SMTP_USER, APP_PASSWORD, SMTP_PASS)
        $smtpUser = self::getEnv('Email') 
                 ?: self::getEnv('SMTP_USER') 
                 ?: self::getEnv('EMAIL') 
                 ?: self::getEnv('MAIL_USERNAME', 'managementattendance6@gmail.com');

        $smtpPass = self::getEnv('APP_PASSWORD') 
                 ?: self::getEnv('SMTP_PASS') 
                 ?: self::getEnv('SMTP_PASSWORD') 
                 ?: self::getEnv('MAIL_PASSWORD', 'mpix egaf qisd gssq');

        $cleanPass = str_replace(' ', '', $smtpPass);

        if (empty($smtpUser) || empty($cleanPass)) {
            return [
                'success' => false,
                'error'   => 'SMTP credentials missing. Please configure Email and APP_PASSWORD in Railway variables or .env.'
            ];
        }

        // Try primary transport (Port 465 SSL) then fallback to Port 587 (STARTTLS)
        $transports = [
            ['host' => 'ssl://smtp.gmail.com', 'port' => 465, 'mode' => 'ssl'],
            ['host' => 'tcp://smtp.gmail.com', 'port' => 587, 'mode' => 'tls'],
        ];

        $lastError = '';

        foreach ($transports as $transport) {
            $result = self::deliverViaSocket($transport, $smtpUser, $cleanPass, $toEmail, $subject, $htmlBody);
            if ($result['success']) {
                return $result;
            }
            $lastError = $result['error'] ?? 'Unknown SMTP error';
        }

        return [
            'success' => false,
            'error'   => "SMTP Delivery failed across all ports: {$lastError}"
        ];
    }

    /**
     * Low-level socket SMTP client supporting SSL stream contexts
     */
    private static function deliverViaSocket(array $transport, string $smtpUser, string $cleanPass, string $toEmail, string $subject, string $htmlBody): array {
        $host = $transport['host'];
        $port = $transport['port'];
        $mode = $transport['mode'];
        $timeout = 3;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true
            ]
        ]);

        $remoteSocket = "{$host}:{$port}";
        $socket = @stream_socket_client($remoteSocket, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);

        if (!$socket) {
            return [
                'success' => false,
                'error'   => "Socket connection to {$remoteSocket} failed: {$errstr} ({$errno})"
            ];
        }

        stream_set_timeout($socket, $timeout);

        $read = function() use ($socket) {
            $response = '';
            while ($line = fgets($socket, 515)) {
                $response .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $response;
        };

        $write = function($cmd) use ($socket) {
            fputs($socket, $cmd . "\r\n");
        };

        try {
            $res = $read();
            if (substr($res, 0, 3) !== '220') {
                fclose($socket);
                return ['success' => false, 'error' => "Initial banner rejected ({$port}): {$res}"];
            }

            $write("EHLO localhost");
            $read();

            // Handle STARTTLS on port 587
            if ($mode === 'tls') {
                $write("STARTTLS");
                $res = $read();
                if (substr($res, 0, 3) !== '220') {
                    fclose($socket);
                    return ['success' => false, 'error' => "STARTTLS rejected: {$res}"];
                }
                $crypto = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$crypto) {
                    fclose($socket);
                    return ['success' => false, 'error' => "TLS encryption handshake failed."];
                }
                $write("EHLO localhost");
                $read();
            }

            $write("AUTH LOGIN");
            $res = $read();
            if (substr($res, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'error' => "AUTH LOGIN rejected: {$res}"];
            }

            $write(base64_encode($smtpUser));
            $res = $read();
            if (substr($res, 0, 3) !== '334') {
                fclose($socket);
                return ['success' => false, 'error' => "Username rejected: {$res}"];
            }

            $write(base64_encode($cleanPass));
            $res = $read();
            if (substr($res, 0, 3) !== '235') {
                fclose($socket);
                return ['success' => false, 'error' => "Authentication failed: {$res}"];
            }

            $write("MAIL FROM: <{$smtpUser}>");
            $read();

            $write("RCPT TO: <{$toEmail}>");
            $res = $read();
            if (substr($res, 0, 3) !== '250') {
                fclose($socket);
                return ['success' => false, 'error' => "Recipient rejected: {$res}"];
            }

            $write("DATA");
            $res = $read();
            if (substr($res, 0, 3) !== '354') {
                fclose($socket);
                return ['success' => false, 'error' => "DATA rejected: {$res}"];
            }

            $headers = [
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "From: BCP Attendance System <{$smtpUser}>",
                "To: <{$toEmail}>",
                "Subject: {$subject}",
                "Date: " . date('r'),
            ];

            $emailData = implode("\r\n", $headers) . "\r\n\r\n" . $htmlBody . "\r\n.\r\n";
            $write($emailData);
            $res = $read();

            $write("QUIT");
            fclose($socket);

            if (substr($res, 0, 3) === '250') {
                return ['success' => true, 'message' => "Email sent successfully via port {$port}"];
            }

            return ['success' => false, 'error' => "Failed to send: {$res}"];

        } catch (Throwable $e) {
            if (is_resource($socket)) fclose($socket);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Send One-Time Password (OTP) template
     */
    public static function sendOtp(string $toEmail, string $otp, string $purpose = 'login', string $recipientName = 'User'): array {
        $isReset = ($purpose === 'password_reset');
        $title   = $isReset ? 'Password Reset Verification' : 'Portal Login Verification Code';
        $action  = $isReset ? 'reset your password' : 'sign in to your attendance portal';

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset='UTF-8'>
          <title>{$title}</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background-color: #F8FAFC; color: #0F172A;'>
          <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #F8FAFC; padding: 40px 16px;'>
            <tr>
              <td align='center'>
                <table width='100%' max-width='500' border='0' cellspacing='0' cellpadding='0' style='max-width: 500px; background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden;'>
                  <tr>
                    <td style='background-color: #0F172A; padding: 24px 32px; text-align: left;'>
                      <table border='0' cellspacing='0' cellpadding='0'>
                        <tr>
                          <td style='font-size: 18px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.01em;'>
                            BCP ATTENDANCE
                          </td>
                        </tr>
                        <tr>
                          <td style='font-size: 12px; color: #94A3B8;'>
                            Bestlink College of the Philippines
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td style='padding: 32px;'>
                      <p style='font-size: 14px; font-weight: 600; color: #E11D48; text-transform: uppercase; letter-spacing: 0.1em; margin: 0 0 8px 0;'>
                        Security Verification
                      </p>
                      <h2 style='font-size: 22px; font-weight: 800; color: #0F172A; margin: 0 0 16px 0;'>
                        {$title}
                      </h2>
                      <p style='font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 24px 0;'>
                        Hello <strong>" . htmlspecialchars($recipientName) . "</strong>,<br>
                        Use the 6-digit verification code below to {$action}. This code is valid for <strong>10 minutes</strong>.
                      </p>

                      <div style='background-color: #F1F5F9; border-radius: 12px; padding: 20px; text-align: center; margin-bottom: 24px; border: 1px dashed #CBD5E1;'>
                        <span style='font-family: monospace; font-size: 34px; font-weight: 800; letter-spacing: 8px; color: #0F172A;'>
                          {$otp}
                        </span>
                      </div>

                      <p style='font-size: 12px; line-height: 1.5; color: #64748B; margin: 0 0 16px 0;'>
                        If you did not request this verification code, please ignore this email or notify campus IT security immediately.
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <td style='background-color: #F8FAFC; padding: 16px 32px; border-top: 1px solid #E2E8F0; text-align: center; font-size: 11px; color: #94A3B8;'>
                      Bestlink College of the Philippines · Attendance Management System
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>";

        return self::send($toEmail, "Your BCP Attendance Code: {$otp}", $html);
    }
}
