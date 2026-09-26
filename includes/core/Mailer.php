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
            foreach (['/.env', '/prod.env'] as $file) {
                $envPath = dirname(__DIR__, 2) . $file;
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
     * Send an HTML Email via HTTPS REST API (Port 443) or SMTP fallback (Port 465/587)
     */
    public static function send(string $toEmail, string $subject, string $htmlBody): array {
        // 1. Try HTTPS REST API first (Port 443 - 100% allowed on Railway, Render, Heroku)
        $httpResult = self::deliverViaHttpApi($toEmail, $subject, $htmlBody);
        if ($httpResult['attempted'] && $httpResult['success']) {
            return $httpResult;
        }

        // 2. Fallback to Gmail SMTP (Ports 465/587)
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
     * Sends email via HTTPS REST APIs (Port 443)
     * Supports: Resend, Brevo (Sendinblue), SendGrid
     */
    private static function deliverViaHttpApi(string $toEmail, string $subject, string $htmlBody): array {
        // Provider 1: Brevo / Sendinblue (https://brevo.com) — Supports ALL recipient emails with 0 domain setup
        $brevoKey = self::getEnv('BREVO_API_KEY') ?: self::getEnv('SENDINBLUE_API_KEY');
        if (!empty($brevoKey)) {
            $fromEmail = self::getEnv('BREVO_FROM_EMAIL', self::getEnv('Email', 'managementattendance6@gmail.com'));
            $fromName  = self::getEnv('BREVO_FROM_NAME', 'BCP Attendance Management System');

            $payload = [
                'sender'      => ['name' => $fromName, 'email' => $fromEmail],
                'to'          => [['email' => $toEmail]],
                'subject'     => $subject,
                'htmlContent' => $htmlBody
            ];

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'api-key: ' . trim($brevoKey),
                'Content-Type: application/json',
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return ['attempted' => true, 'success' => true, 'provider' => 'brevo'];
            }
            error_log("[Brevo API Error] HTTP {$httpCode}: {$resp} {$err}");
        }

        // Provider 2: Resend (https://resend.com)
        $resendKey = self::getEnv('RESEND_API_KEY') ?: self::getEnv('RESEND_KEY');
        if (!empty($resendKey)) {
            $from = self::getEnv('RESEND_FROM', 'Bestlink Attendance Portal <onboarding@resend.dev>');
            $payload = [
                'from'    => $from,
                'to'      => [$toEmail],
                'subject' => $subject,
                'html'    => $htmlBody
            ];

            $ch = curl_init('https://api.resend.com/emails');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . trim($resendKey),
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return ['attempted' => true, 'success' => true, 'provider' => 'resend'];
            }
            error_log("[Resend API Error] HTTP {$httpCode}: {$resp} {$err}");
        }

        // Provider 3: SendGrid (https://sendgrid.com)
        $sendgridKey = self::getEnv('SENDGRID_API_KEY');
        if (!empty($sendgridKey)) {
            $fromEmail = self::getEnv('SENDGRID_FROM_EMAIL', 'managementattendance6@gmail.com');
            $payload = [
                'personalizations' => [['to' => [['email' => $toEmail]]]],
                'from'             => ['email' => $fromEmail, 'name' => 'BCP Attendance System'],
                'subject'          => $subject,
                'content'          => [['type' => 'text/html', 'value' => $htmlBody]]
            ];

            $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . trim($sendgridKey),
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $resp = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return ['attempted' => true, 'success' => true, 'provider' => 'sendgrid'];
            }
        }

        return ['attempted' => false, 'success' => false];
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

    /**
     * Send Attendance Early-Warning Notice to Parent/Guardian
     */
    public static function sendParentAlert(string $toEmail, string $studentName, string $riskLevel = 'High Risk', string $details = '', string $actionPlan = ''): array {
        $subject = "BCP Attendance Notice: Early-Warning Alert for {$studentName}";

        $badgeColor = '#E11D48';
        if (stripos($riskLevel, 'moderate') !== false) {
            $badgeColor = '#D97706';
        }

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset='UTF-8'>
          <title>{$subject}</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background-color: #F8FAFC; color: #0F172A;'>
          <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #F8FAFC; padding: 40px 16px;'>
            <tr>
              <td align='center'>
                <table width='100%' max-width='560' border='0' cellspacing='0' cellpadding='0' style='max-width: 560px; background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden;'>
                  <tr>
                    <td style='background-color: #0F172A; padding: 24px 32px; text-align: left;'>
                      <table border='0' cellspacing='0' cellpadding='0'>
                        <tr>
                          <td style='font-size: 18px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.01em;'>
                            BESTLINK COLLEGE OF THE PHILIPPINES
                          </td>
                        </tr>
                        <tr>
                          <td style='font-size: 12px; color: #94A3B8;'>
                            Attendance &amp; Academic Welfare Monitoring
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td style='padding: 32px;'>
                      <div style='display: inline-block; padding: 4px 12px; border-radius: 9999px; background-color: #FFF1F2; border: 1px solid #FECDD3; font-size: 12px; font-weight: 800; color: {$badgeColor}; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;'>
                        " . htmlspecialchars($riskLevel) . " Advisory
                      </div>
                      <h2 style='font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 14px 0;'>
                        Official Attendance Early-Warning Notification
                      </h2>
                      <p style='font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 18px 0;'>
                        Dear Parent / Guardian of <strong>" . htmlspecialchars($studentName) . "</strong>,
                      </p>
                      <p style='font-size: 13px; line-height: 1.6; color: #475569; margin: 0 0 18px 0;'>
                        Our institutional attendance monitoring system has flagged recent attendance irregularities (such as consecutive unexcused absences or low attendance velocity) for your student.
                      </p>

                      " . (!empty($details) ? "
                      <div style='background-color: #F8FAFC; border-radius: 10px; padding: 14px 16px; border-left: 4px solid {$badgeColor}; margin-bottom: 18px; font-size: 13px; color: #334155;'>
                        <strong>Recorded Concern:</strong> " . htmlspecialchars($details) . "
                      </div>" : "") . "

                      " . (!empty($actionPlan) ? "
                      <div style='background-color: #EFF6FF; border-radius: 10px; padding: 14px 16px; border-left: 4px solid #3B82F6; margin-bottom: 18px; font-size: 13px; color: #1E3A8A;'>
                        <strong>Recommended Next Step:</strong> " . htmlspecialchars($actionPlan) . "
                      </div>" : "") . "

                      <p style='font-size: 12px; line-height: 1.5; color: #64748B; margin: 0 0 12px 0;'>
                        Please coordinate with the student's department head or class adviser to submit any pending excuse documentation or arrange an academic counseling session.
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <td style='background-color: #F8FAFC; padding: 16px 32px; border-top: 1px solid #E2E8F0; text-align: center; font-size: 11px; color: #94A3B8;'>
                      Bestlink College of the Philippines · Student Affairs &amp; Attendance Office<br>
                      Sent automatically via BCP Attendance Management Portal
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>";

        return self::send($toEmail, $subject, $html);
    }

    /**
     * Send Welcome & Account Credentials Notification to newly registered/imported Faculty
     */
    public static function sendTeacherWelcomeEmail(string $toEmail, string $teacherName, string $employeeId, string $surname): array {
        $subject = "Welcome to BCP Attendance System — Your Faculty Account Details";
        $appUrl = self::getEnv('APP_URL', 'http://localhost/login');
        
        $cleanSur = preg_replace('/[^a-zA-Z]/', '', trim($surname));
        if (empty($cleanSur)) {
            $cleanSur = 'Faculty';
        }
        $firstChar = strtoupper(substr($cleanSur, 0, 1));
        $secondChar = strlen($cleanSur) > 1 ? strtolower(substr($cleanSur, 1, 1)) : strtolower($firstChar);
        $examplePass = '#' . $firstChar . $secondChar . '8080';

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset='UTF-8'>
          <title>{$subject}</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background-color: #F8FAFC; color: #0F172A;'>
          <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #F8FAFC; padding: 40px 16px;'>
            <tr>
              <td align='center'>
                <table width='100%' max-width='560' border='0' cellspacing='0' cellpadding='0' style='max-width: 560px; background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden;'>
                  <tr>
                    <td style='background-color: #1E3B8A; padding: 24px 32px; text-align: left;'>
                      <table border='0' cellspacing='0' cellpadding='0'>
                        <tr>
                          <td style='font-size: 18px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.01em;'>
                            BESTLINK COLLEGE OF THE PHILIPPINES
                          </td>
                        </tr>
                        <tr>
                          <td style='font-size: 12px; color: #BAE6FD;'>
                            Attendance Management &amp; Faculty Portal
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <tr>
                    <td style='padding: 32px;'>
                      <div style='display: inline-block; padding: 4px 12px; border-radius: 9999px; background-color: #EFF6FF; border: 1px solid #BFDBFE; font-size: 12px; font-weight: 800; color: #1E3B8A; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;'>
                        Faculty Account Created
                      </div>
                      <h2 style='font-size: 20px; font-weight: 800; color: #0F172A; margin: 0 0 14px 0;'>
                        Welcome to the Attendance Management System
                      </h2>
                      <p style='font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 18px 0;'>
                        Dear <strong>" . htmlspecialchars($teacherName) . "</strong>,<br>
                        Your faculty account has been successfully created and provisioned in the BCP Attendance Management System. You can now log in to manage your classes, launch live QR attendance sessions, track student attendance, and generate reports.
                      </p>

                      <div style='background-color: #F8FAFC; border-radius: 12px; padding: 20px; border: 1px solid #E2E8F0; margin-bottom: 24px;'>
                        <h4 style='font-size: 13px; font-weight: 800; color: #1E3B8A; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px 0;'>
                          Your Account Credentials
                        </h4>
                        <table width='100%' border='0' cellspacing='0' cellpadding='0' style='font-size: 13px; color: #334155;'>
                          <tr>
                            <td style='padding: 6px 0; color: #64748B; width: 150px; font-weight: 600;'>Institutional Email:</td>
                            <td style='padding: 6px 0; font-weight: 700; color: #0F172A;'>" . htmlspecialchars($toEmail) . "</td>
                          </tr>
                          <tr>
                            <td style='padding: 6px 0; color: #64748B; font-weight: 600;'>Employee ID / Username:</td>
                            <td style='padding: 6px 0; font-weight: 700; font-family: monospace; color: #0F172A;'>" . htmlspecialchars($employeeId) . "</td>
                          </tr>
                          <tr>
                            <td style='padding: 6px 0; color: #64748B; font-weight: 600; vertical-align: top;'>Default Password:</td>
                            <td style='padding: 6px 0;'>
                              <div style='background-color: #FEF3C7; border: 1px solid #FDE68A; padding: 10px 12px; border-radius: 8px; color: #92400E; font-size: 12px; line-height: 1.5;'>
                                <strong>Default Password Instruction:</strong><br>
                                Your default password is formatted as:<br>
                                <span style='display: inline-block; font-family: monospace; background-color: #FDE68A; padding: 2px 6px; border-radius: 4px; font-weight: 700; color: #78350F; margin: 4px 0;'>
                                  #(First letter of your surname capitalized and second letter in lowercase)8080
                                </span><br>
                                <span style='display: inline-block; margin-top: 4px; color: #78350F;'>
                                  <em>(For your surname <strong>" . htmlspecialchars($surname) . "</strong>, your default password is <strong>" . htmlspecialchars($examplePass) . "</strong>)</em>
                                </span>
                              </div>
                            </td>
                          </tr>
                        </table>
                      </div>

                      <div style='text-align: center; margin-bottom: 24px;'>
                        <a href='{$appUrl}' style='display: inline-block; background-color: #1E3B8A; color: #FFFFFF; font-weight: 700; font-size: 13px; text-decoration: none; padding: 12px 28px; border-radius: 10px; box-shadow: 0 4px 10px rgba(30, 59, 138, 0.2);'>
                          Sign In to Faculty Portal &rarr;
                        </a>
                      </div>

                      <p style='font-size: 12px; line-height: 1.5; color: #64748B; margin: 0 0 8px 0;'>
                        <strong>Security Recommendation:</strong> For your security, we recommend changing your default password after your first successful login under Account Settings.
                      </p>
                    </td>
                  </tr>
                  <tr>
                    <td style='background-color: #F8FAFC; padding: 16px 32px; border-top: 1px solid #E2E8F0; text-align: center; font-size: 11px; color: #94A3B8;'>
                      Bestlink College of the Philippines · Faculty &amp; Academic Affairs Office<br>
                      Sent automatically via BCP Attendance Management Portal
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>";

        return self::send($toEmail, $subject, $html);
    }

    /**
     * Send Student Account Creation Credentials & Welcome Notice
     */
    public static function sendStudentWelcome(
        string $toEmail,
        string $studentName,
        string $studentId,
        string $temporaryPassword,
        string $course,
        string $yearLevel,
        string $section
    ): array {
        $subject = "Your BCP Student Account Credentials (ID: {$studentId})";
        $loginUrl = function_exists('url') ? url('login') : '/login';

        $html = "
        <!DOCTYPE html>
        <html>
        <head>
          <meta charset='UTF-8'>
          <title>{$subject}</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, Helvetica, Arial, sans-serif; background-color: #F8FAFC; color: #0F172A;'>
          <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #F8FAFC; padding: 40px 16px;'>
            <tr>
              <td align='center'>
                <table width='100%' border='0' cellspacing='0' cellpadding='0' style='max-width: 580px; background-color: #FFFFFF; border-radius: 16px; border: 1px solid #E2E8F0; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden;'>
                  <!-- Header -->
                  <tr>
                    <td style='background: linear-gradient(135deg, #1E3B8A 0%, #172554 100%); padding: 28px 32px; text-align: left;'>
                      <table border='0' cellspacing='0' cellpadding='0'>
                        <tr>
                          <td style='font-size: 19px; font-weight: 800; color: #FFFFFF; letter-spacing: -0.01em;'>
                            BESTLINK COLLEGE OF THE PHILIPPINES
                          </td>
                        </tr>
                        <tr>
                          <td style='font-size: 12px; color: #93C5FD; padding-top: 4px;'>
                            Student Attendance Management System · Official Master Account
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <!-- Body -->
                  <tr>
                    <td style='padding: 32px;'>
                      <div style='display: inline-block; padding: 4px 12px; border-radius: 9999px; background-color: #EFF6FF; border: 1px solid #BFDBFE; font-size: 11px; font-weight: 700; color: #1D4ED8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 14px;'>
                        Student Account Provisioned
                      </div>
                      <h2 style='font-size: 22px; font-weight: 800; color: #0F172A; margin: 0 0 12px 0;'>
                        Welcome, " . htmlspecialchars($studentName) . "!
                      </h2>
                      <p style='font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 24px 0;'>
                        Your official student account has been created by the administrator. You can now sign in to your student portal to track daily attendance, generate QR passes, and submit excuse slips.
                      </p>

                      <!-- Credentials Card -->
                      <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin-bottom: 24px;'>
                        <tr>
                          <td style='padding: 20px;'>
                            <div style='font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 12px;'>
                              Your Login Credentials:
                            </div>
                            <table width='100%' border='0' cellspacing='0' cellpadding='6'>
                              <tr>
                                <td style='font-size: 13px; color: #64748B; width: 140px;'>Student ID:</td>
                                <td style='font-size: 14px; font-weight: 700; color: #0F172A; font-family: monospace;'>" . htmlspecialchars($studentId) . "</td>
                              </tr>
                              <tr>
                                <td style='font-size: 13px; color: #64748B;'>Registered Gmail:</td>
                                <td style='font-size: 14px; font-weight: 700; color: #0F172A; font-family: monospace;'>" . htmlspecialchars($toEmail) . "</td>
                              </tr>
                              <tr>
                                <td style='font-size: 13px; color: #64748B;'>Temporary Password:</td>
                                <td>
                                  <span style='display: inline-block; background-color: #FEF3C7; border: 1px solid #FDE68A; padding: 4px 10px; border-radius: 6px; font-family: monospace; font-size: 14px; font-weight: 800; color: #92400E;'>
                                    " . htmlspecialchars($temporaryPassword) . "
                                  </span>
                                </td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>

                      <!-- Enrollment Details Card -->
                      <table width='100%' border='0' cellspacing='0' cellpadding='0' style='background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; margin-bottom: 24px;'>
                        <tr>
                          <td style='padding: 16px 20px;'>
                            <div style='font-size: 11px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;'>
                              Enrolled Class Details:
                            </div>
                            <table width='100%' border='0' cellspacing='0' cellpadding='4'>
                              <tr>
                                <td style='font-size: 12px; color: #15803D; width: 140px;'>Course / Program:</td>
                                <td style='font-size: 13px; font-weight: 700; color: #14532D;'>" . htmlspecialchars($course) . "</td>
                              </tr>
                              <tr>
                                <td style='font-size: 12px; color: #15803D;'>Year Level:</td>
                                <td style='font-size: 13px; font-weight: 700; color: #14532D;'>" . htmlspecialchars($yearLevel) . "</td>
                              </tr>
                              <tr>
                                <td style='font-size: 12px; color: #15803D;'>Assigned Section:</td>
                                <td style='font-size: 13px; font-weight: 700; color: #14532D; font-family: monospace;'>" . htmlspecialchars($section) . "</td>
                              </tr>
                            </table>
                          </td>
                        </tr>
                      </table>

                      <!-- Security Notice -->
                      <p style='font-size: 12px; line-height: 1.5; color: #64748B; margin: 0 0 24px 0;'>
                        ⚠️ <strong>First Login Notice:</strong> Please sign in using your Gmail or Student ID and the temporary password above. You will be prompted to choose a new private password upon your first sign in.
                      </p>

                      <!-- Action Button -->
                      <table border='0' cellspacing='0' cellpadding='0'>
                        <tr>
                          <td align='center' style='border-radius: 10px; background-color: #1E3B8A;'>
                            <a href='{$loginUrl}' target='_blank' style='display: inline-block; padding: 12px 28px; font-size: 14px; font-weight: 700; color: #FFFFFF; text-decoration: none; border-radius: 10px;'>
                              Sign In to Student Portal &rarr;
                            </a>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                  <!-- Footer -->
                  <tr>
                    <td style='background-color: #F8FAFC; padding: 18px 32px; border-top: 1px solid #E2E8F0; text-align: center; font-size: 11px; color: #94A3B8; line-height: 1.5;'>
                      Bestlink College of the Philippines · Student Affairs &amp; Attendance Office<br>
                      This is an automated institutional notification.
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </body>
        </html>";

        return self::send($toEmail, $subject, $html);
    }
}
