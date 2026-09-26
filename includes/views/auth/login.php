<?php
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

// 1. If actively logged in and not explicitly logging out, redirect to role dashboard
if (!empty($_SESSION['user_id']) && !empty($_SESSION['user']) && empty($_GET['logged_out'])) {
    $role = $_SESSION['user']['role'] ?? ($_SESSION['role'] ?? 'admin');
    $redirectUrl = match ($role) {
        'teacher' => url('teacher/dashboard'),
        'student' => url('student/calendar'),
        default   => url('dashboard'),
    };
    header("Location: {$redirectUrl}");
    exit;
}

// 2. If remembered token cookie exists and user did not explicitly click log out, auto-login
if (empty($_SESSION['user_id']) && !empty($_COOKIE['ams_remember_token']) && empty($_GET['logged_out'])) {
    try {
        $db = Database::getConnection();
        $token = $_COOKIE['ams_remember_token'];
        $remStmt = $db->prepare("
            SELECT * FROM users 
            WHERE remember_token = :token 
              AND remember_expires_at > NOW() 
              AND status = 'active' 
            LIMIT 1
        ");
        $remStmt->execute([':token' => $token]);
        $rememberedUser = $remStmt->fetch(PDO::FETCH_ASSOC);

        if ($rememberedUser) {
            $_SESSION['user_id'] = (int)$rememberedUser['user_id'];
            $_SESSION['role']    = $rememberedUser['role'];
            $_SESSION['user']    = [
                'user_id'     => (int)$rememberedUser['user_id'],
                'student_id'  => $rememberedUser['student_id'] ?? null,
                'employee_id' => $rememberedUser['employee_id'] ?? null,
                'first_name'  => $rememberedUser['first_name'],
                'last_name'   => $rememberedUser['last_name'],
                'full_name'   => trim("{$rememberedUser['first_name']} {$rememberedUser['last_name']}"),
                'email'       => $rememberedUser['email'],
                'role'        => $rememberedUser['role'],
                'avatar_path' => $rememberedUser['avatar_path'] ?? null,
            ];

            if ($rememberedUser['role'] === 'teacher') {
                $_SESSION['teacher_id'] = (int)$rememberedUser['user_id'];
            } elseif ($rememberedUser['role'] === 'student') {
                $_SESSION['student_id'] = (int)$rememberedUser['user_id'];
            }

            $upStmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE user_id = ?");
            $upStmt->execute([$rememberedUser['user_id']]);

            $role = $rememberedUser['role'];
            $redirectUrl = match ($role) {
                'teacher' => url('teacher/dashboard'),
                'student' => url('student/calendar'),
                default   => url('dashboard'),
            };
            header("Location: {$redirectUrl}");
            exit;
        }
    } catch (Throwable $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Attendance Management System</title>
  <meta name="description" content="Attendance Management Portal">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?php echo url('Project_theme.css'); ?>">
  <style>
    :root {
      --primary-blue: #1e3b8a;
      --primary-blue-hover: #172554;
      --primary-sky: #0284c7;
      --primary-sky-light: #e0f2fe;
      --dark-surface: #0F172A;
      --border-color: #E2E8F0;
      --bg-surface: #f2f6fa;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    html, body {
      min-height: 100vh;
      margin: 0;
      padding: 0;
      background-color: #FFFFFF;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
      color: #0F172A;
      -webkit-font-smoothing: antialiased;
    }

    /* Universal Hidden Utility */
    .hidden {
      display: none !important;
    }

    /* ── Split Screen Canvas ─────────────────────────────── */
    .app-canvas {
      display: flex;
      min-height: 100vh;
      width: 100%;
      background: #FFFFFF;
    }

    /* ── Left Hero Side (Desktop) ────────────────────────── */
    .hero-side {
      flex: 1.15;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 56px 68px;
      position: relative;
      background: #FFFFFF;
      overflow: hidden;
      border-right: 1px solid #F1F5F9;
    }

    .hero-inner {
      position: relative;
      z-index: 2;
      height: 100%;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }

    .brand-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .brand-logo-wrap {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .brand-text-col {
      display: flex;
      flex-direction: column;
    }

    .brand-title-main {
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      font-size: 17px;
      letter-spacing: -0.01em;
      color: #0F172A;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .brand-subtitle-sub {
      font-size: 11px;
      color: #64748B;
      font-weight: 500;
    }

    .term-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 12px;
      border-radius: 9999px;
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      font-size: 11px;
      font-weight: 600;
      color: #475569;
    }

    .hero-center {
      margin: 48px 0;
      max-width: 580px;
    }

    .pill-tagline {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.18em;
      color: var(--primary-sky);
      text-transform: uppercase;
      margin-bottom: 20px;
    }

    .hero-h1 {
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      font-size: 48px;
      font-weight: 900;
      line-height: 1.12;
      letter-spacing: -0.03em;
      color: #0F172A;
      margin-bottom: 20px;
    }

    .hero-h1 span.highlight {
      background: linear-gradient(135deg, #1e3b8a 0%, #0284c7 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    .hero-paragraph {
      font-size: 15px;
      line-height: 1.65;
      color: #64748B;
      margin-bottom: 32px;
      max-width: 480px;
    }

    .feature-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 12px;
      max-width: 520px;
    }

    .feature-card {
      background: #F8FAFC;
      border: 1px solid #E2E8F0;
      border-radius: 12px;
      padding: 12px 14px;
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .feature-title {
      font-size: 12px;
      font-weight: 700;
      color: #1E293B;
    }

    .feature-desc {
      font-size: 10.5px;
      color: #64748B;
      line-height: 1.35;
    }

    .hero-footer-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding-top: 24px;
      border-top: 1px solid #F1F5F9;
    }

    .footer-security-text {
      font-size: 12px;
      color: #94A3B8;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .live-status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 14px;
      border-radius: 9999px;
      background: #F0FDF4;
      border: 1px solid #BBF7D0;
      font-size: 10.5px;
      font-weight: 700;
      letter-spacing: 0.08em;
      color: #15803D;
      text-transform: uppercase;
    }

    .pulsing-dot {
      width: 6px;
      height: 6px;
      border-radius: 50%;
      background: #16A34A;
      box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.7);
      animation: pulseGreen 1.8s infinite;
    }

    @keyframes pulseGreen {
      0% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0.6); }
      70% { box-shadow: 0 0 0 6px rgba(22, 163, 74, 0); }
      100% { box-shadow: 0 0 0 0 rgba(22, 163, 74, 0); }
    }

    /* ── Right Auth Panel ────────────────────────────────── */
    .auth-side {
      width: 480px;
      flex-shrink: 0;
      background: #FFFFFF;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      padding: 48px 40px;
      position: relative;
      min-height: 100vh;
    }

    .auth-card-inner {
      width: 100%;
      max-width: 400px;
      display: flex;
      flex-direction: column;
    }

    .auth-mobile-logo {
      display: none;
    }

    .auth-top-eyebrow {
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.18em;
      color: var(--primary-sky);
      text-transform: uppercase;
      margin-bottom: 6px;
    }

    .auth-heading {
      font-family: 'Plus Jakarta Sans', system-ui, sans-serif;
      font-size: 26px;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #0F172A;
      margin-bottom: 6px;
    }

    .auth-subtext {
      font-size: 13px;
      color: #64748B;
      margin-bottom: 22px;
    }

    /* Form Fields Styling */
    .form-group {
      margin-bottom: 18px;
      position: relative;
    }

    .form-label-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }

    .form-label-text {
      font-size: 11px;
      font-weight: 700;
      letter-spacing: 0.08em;
      color: #334155;
      text-transform: uppercase;
    }

    .custom-field-box {
      position: relative;
      display: flex;
      align-items: center;
      border: 1.5px solid #E2E8F0;
      border-radius: 10px;
      background: #FFFFFF;
      transition: all 0.2s ease;
      padding: 0 12px;
    }

    .custom-field-box:focus-within {
      border-color: #0F172A;
      box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.06);
    }

    .field-icon {
      color: #94A3B8;
      width: 16px;
      height: 16px;
      margin-right: 8px;
      flex-shrink: 0;
    }

    .custom-input-field {
      width: 100%;
      border: none;
      outline: none;
      padding: 11px 0;
      font-size: 13.5px;
      color: #0F172A;
      background: transparent;
      font-family: inherit;
    }

    .custom-input-field::placeholder {
      color: #94A3B8;
      font-size: 13px;
    }

    .toggle-pass-btn {
      background: none;
      border: none;
      color: #94A3B8;
      cursor: pointer;
      padding: 4px;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: color 0.15s ease;
    }

    .toggle-pass-btn:hover {
      color: #334155;
    }

    /* Remember Me & Options Row */
    .form-options-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-top: 4px;
      margin-bottom: 20px;
    }

    .remember-label {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12.5px;
      color: #475569;
      cursor: pointer;
      user-select: none;
    }

    .remember-checkbox {
      width: 16px;
      height: 16px;
      border-radius: 4px;
      accent-color: #1e3b8a;
      cursor: pointer;
    }

    .forgot-link-btn {
      background: none;
      border: none;
      color: #0284c7;
      font-size: 12.5px;
      font-weight: 500;
      padding: 0;
    }

    .forgot-link-btn:hover {
      color: #0F172A;
      text-decoration: underline;
    }

    /* Primary Submit Button */
    .btn-auth-submit {
      width: 100%;
      background: #1e3b8a;
      color: #FFFFFF;
      border: none;
      border-radius: 10px;
      padding: 12px 18px;
      font-size: 14px;
      font-weight: 600;
      letter-spacing: 0.01em;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      transition: all 0.2s ease;
      font-family: inherit;
    }

    .btn-auth-submit:hover {
      background: #172554;
      transform: translateY(-1px);
      box-shadow: 0 6px 16px -2px rgba(30, 59, 138, 0.25);
    }

    .btn-auth-submit:active {
      transform: translateY(0);
    }

    .btn-auth-submit:disabled {
      opacity: 0.7;
      cursor: not-allowed;
      transform: none !important;
    }

    /* ── Step 2: OTP Verification UI ──────────────────────── */
    #otp-step-container {
      display: none;
      animation: fadeInStep 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #credentials-step-container {
      animation: fadeInStep 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    #approval-step-container {
      display: none;
      animation: fadeInStep 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes pulseRing {
      0% { transform: scale(0.85); opacity: 0.6; }
      50% { transform: scale(1.35); opacity: 0.1; }
      100% { transform: scale(1.6); opacity: 0; }
    }

    @keyframes blink {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.3; }
    }

    @keyframes fadeInStep {
      from { opacity: 0; transform: translateY(8px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .otp-inputs-grid {
      display: grid;
      grid-template-columns: repeat(6, 1fr);
      gap: 8px;
      margin: 20px 0;
    }

    .otp-digit-box {
      height: 50px;
      width: 100%;
      text-align: center;
      font-family: 'Plus Jakarta Sans', monospace, sans-serif;
      font-size: 22px;
      font-weight: 800;
      color: #0F172A;
      border: 1.5px solid #CBD5E1;
      border-radius: 10px;
      background: #FFFFFF;
      outline: none;
      transition: all 0.15s ease;
    }

    .otp-digit-box:focus {
      border-color: #0F172A;
      box-shadow: 0 0 0 3px rgba(15, 23, 42, 0.08);
    }

    .otp-digit-box.filled {
      border-color: #1e3b8a;
      background: #F0FDF4;
    }

    .otp-footer-controls {
      display: flex;
      align-items: center;
      justify-content: space-between;
      font-size: 12.5px;
      color: #64748B;
      margin-bottom: 20px;
    }

    .resend-btn {
      background: none;
      border: none;
      color: #0284c7;
      font-weight: 600;
      cursor: pointer;
      padding: 0;
      display: inline-flex;
      align-items: center;
      gap: 4px;
    }

    .resend-btn:hover {
      text-decoration: underline;
    }

    .resend-btn:disabled {
      color: #94A3B8;
      cursor: not-allowed;
      text-decoration: none;
    }

    .back-btn {
      background: none;
      border: none;
      color: #64748B;
      font-size: 13px;
      font-weight: 500;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      margin-top: 14px;
    }

    .back-btn:hover {
      color: #0F172A;
    }

    /* Support Callout */
    .auth-help-box {
      background: #F8FAFC;
      border-radius: 12px;
      padding: 14px 18px;
      text-align: center;
      font-size: 12px;
      color: #475569;
      border: 1px solid #E2E8F0;
      margin-top: 28px;
      width: 100%;
    }

    .auth-help-email {
      color: var(--primary-pink);
      font-weight: 700;
      text-decoration: none;
      display: inline-block;
      margin-top: 2px;
    }

    .auth-help-email:hover {
      text-decoration: underline;
    }

    /* Notification Banner */
    .auth-alert {
      padding: 10px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 500;
      display: flex;
      align-items: flex-start;
      gap: 8px;
      margin-bottom: 16px;
    }

    .auth-alert.error {
      background: #FEF2F2;
      color: #991B1B;
      border: 1px solid #FECACA;
    }

    .auth-alert.success {
      background: #ECFDF5;
      color: #065F46;
      border: 1px solid #A7F3D0;
    }

    @keyframes spin {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    /* Modal Styles */
    .modal-backdrop {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.65);
      backdrop-filter: blur(8px);
      -webkit-backdrop-filter: blur(8px);
      z-index: 9999;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      animation: fadeInModal 0.2s ease-out;
    }

    .modal-backdrop.open {
      display: flex !important;
    }

    .modal-dialog {
      background: #FFFFFF;
      border-radius: 20px;
      max-width: 420px;
      width: 100%;
      box-shadow: 0 25px 60px -15px rgba(15, 23, 42, 0.3);
      border: 1px solid #E2E8F0;
      padding: 28px 24px;
      position: relative;
      animation: scaleUpModal 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes fadeInModal {
      from { opacity: 0; }
      to { opacity: 1; }
    }

    @keyframes scaleUpModal {
      from { opacity: 0; transform: scale(0.96) translateY(10px); }
      to { opacity: 1; transform: scale(1) translateY(0); }
    }

    .modal-close-btn {
      position: absolute;
      top: 18px;
      right: 18px;
      background: none;
      border: none;
      color: #94A3B8;
      cursor: pointer;
      padding: 4px;
      border-radius: 6px;
    }

    .modal-close-btn:hover {
      color: #0F172A;
      background: #F1F5F9;
    }

    .password-rule-row {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11.5px;
      color: #64748B;
      margin-top: 4px;
    }

    .password-rule-row.valid {
      color: #059669;
      font-weight: 600;
    }

    .rule-bullet {
      width: 12px;
      height: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    /* Responsive Breakpoints */
    @media (max-width: 1023px) {
      html, body {
        background-color: #F8FAFC;
        overflow-y: auto;
      }
      .hero-side {
        display: none !important;
      }
      .app-canvas {
        background: #F8FAFC;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        height: auto;
        padding: 32px 16px;
      }
      .auth-side {
        width: 100%;
        max-width: 440px;
        min-height: auto;
        background: #FFFFFF;
        border: 1px solid #E2E8F0;
        border-radius: 16px;
        box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.08), 0 4px 6px -4px rgba(15, 23, 42, 0.04);
        padding: 36px 32px;
      }
      .auth-mobile-logo {
        display: flex !important;
        justify-content: center;
        margin-bottom: 16px;
      }
      .auth-top-eyebrow,
      .auth-heading,
      .auth-subtext {
        text-align: center;
      }
    }

    @media (max-width: 480px) {
      .auth-side {
        padding: 28px 20px;
        border-radius: 14px;
      }
      .auth-heading {
        font-size: 23px;
      }
      .otp-inputs-grid {
        gap: 6px;
      }
      .otp-digit-box {
        height: 46px;
        font-size: 18px;
      }
    }
  </style>
</head>
<body>

  <div class="app-canvas">
    
    <!-- ══════════════════════════════════════════════════════════
         LEFT HERO SIDE (Desktop)
         ══════════════════════════════════════════════════════════ -->
    <div class="hero-side">
      <div class="hero-inner">
        
        <!-- Top Brand Bar -->
        <div class="brand-header">
          <div class="brand-logo-wrap">
            <img src="<?php echo url('assets/images/bcp-logo.png'); ?>" alt="BCP Logo" style="width: 42px; height: 42px; object-fit: contain;">
            <div class="brand-text-col">
              <span class="brand-title-main">
                BCP ATTENDANCE
              </span>
              <span class="brand-subtitle-sub">Bestlink College of the Philippines</span>
            </div>
          </div>

          <div class="term-pill">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #10B981;"></span>
            <span>AY 2025–2026</span>
          </div>
        </div>

        <!-- Center Main Title -->
        <div class="hero-center">
          <div class="pill-tagline">
            <svg style="width: 14px; height: 14px;" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 1.944A11.954 11.954 0 012.166 5C2.056 5.649 2 6.319 2 7c0 5.225 3.34 9.67 8 11.317C14.66 16.67 18 12.225 18 7c0-.682-.057-1.35-.166-2.001A11.954 11.954 0 0110 1.944zM11 14a1 1 0 11-2 0 1 1 0 012 0zm0-7a1 1 0 10-2 0v3a1 1 0 102 0V7z" clip-rule="evenodd"/>
            </svg>
            <span>SECURE ACCESS PORTAL</span>
          </div>
          
          <h1 class="hero-h1">
            Student & Faculty<br>
            <span class="highlight">Attendance</span><br>
            Management
          </h1>

          <p class="hero-paragraph">
            Institutional portal for live RFID attendance tracking, digital excuse slip processing, biometric sync, and analytics.
          </p>

          <!-- 3 Highlight Features -->
          <div class="feature-grid">
            <div class="feature-card">
              <span class="feature-title">⚡ Instant Tap-In</span>
              <span class="feature-desc">Fast RFID & QR code scanning</span>
            </div>
            <div class="feature-card">
              <span class="feature-title">📋 Excuse Slips</span>
              <span class="feature-desc">Digital submission & faculty review</span>
            </div>
            <div class="feature-card">
              <span class="feature-title">📊 Live Reports</span>
              <span class="feature-desc">Automated DTR & anomaly detection</span>
            </div>
          </div>
        </div>

        <!-- Bottom Security / Status Footer -->
        <div class="hero-footer-bar">
          <div class="footer-security-text">
            <span style="width: 6px; height: 6px; border-radius: 50%; background: #94A3B8;"></span>
            <span>Institutional Security Protocol · Bestlink College</span>
          </div>

          <div class="live-status-pill">
            <span class="pulsing-dot"></span>
            <span>PORTAL ONLINE</span>
          </div>
        </div>

      </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════
         RIGHT AUTHENTICATION FORM SIDE
         ══════════════════════════════════════════════════════════ -->
    <div class="auth-side">
      <div class="auth-card-inner">
        
        <!-- Institution Logo (Mobile View Only) -->
        <div class="auth-mobile-logo">
          <img src="<?php echo url('assets/images/bcp-logo.png'); ?>" alt="BCP Logo" style="width: 54px; height: 54px; object-fit: contain;">
        </div>

        <!-- Header Section -->
        <div class="auth-top-eyebrow" id="auth-flow-eyebrow">SECURE SIGN IN</div>
        <h2 class="auth-heading" id="auth-flow-title">Sign In to Portal</h2>
        <p class="auth-subtext" id="auth-flow-subtitle">Use your institutional credentials to authenticate.</p>     

        <!-- Notification Banner -->
        <div id="alert-banner" class="auth-alert hidden">
          <svg id="alert-icon" style="width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
          <span id="alert-message"></span>
        </div>

        <?php if (!empty($_GET['logged_out'])): ?>
        <div id="logged-out-alert" class="auth-alert success">
          <svg style="width: 16px; height: 16px; flex-shrink: 0; color: #059669;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>You have been successfully signed out.</span>
        </div>
        <?php elseif (!empty($_GET['session_expired'])): ?>
        <div id="session-expired-alert" class="auth-alert error">
          <svg style="width: 16px; height: 16px; flex-shrink: 0; color: #E11D48;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <span>Your session has expired or you are not logged in. Please sign in.</span>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════════════════════════════════
             STEP 1: CREDENTIALS FORM (EMAIL / ID + PASSWORD)
             ══════════════════════════════════════════════════════════ -->
        <div id="credentials-step-container">
          <form id="credentials-form" onsubmit="event.preventDefault(); submitCredentials();">
            
            <div class="form-group">
              <div class="form-label-row">
                <label for="login-identifier" class="form-label-text">Institutional Email or ID</label>
              </div>
              <div class="custom-field-box">
                <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                </svg>
                <input 
                  type="text" 
                  id="login-identifier" 
                  name="email" 
                  class="custom-input-field" 
                  placeholder="e.g. admin@bcp.edu.ph" 
                  required 
                  autocomplete="username">
              </div>
            </div>

            <div class="form-group">
              <div class="form-label-row">
                <label for="login-password" class="form-label-text">Password</label>
              </div>
              <div class="custom-field-box">
                <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
                <input 
                  type="password" 
                  id="login-password" 
                  name="password" 
                  class="custom-input-field" 
                  placeholder="••••••••" 
                  required 
                  autocomplete="current-password">
                <button 
                  type="button" 
                  class="toggle-pass-btn" 
                  onclick="togglePasswordVisibility('login-password', 'eye-icon')" 
                  aria-label="Toggle password visibility">
                  <svg id="eye-icon" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Forgot Password Link -->
            <div class="form-options-row" style="justify-content: flex-end;">
              <button type="button" class="forgot-link-btn" onclick="openForgotPasswordModal()">Forgot password?</button>
            </div>

            <!-- Step 1 Submit Button -->
            <button type="submit" id="credentials-submit-btn" class="btn-auth-submit">
              <span id="btn-login-text">Sign In</span>
              <svg id="btn-login-arrow" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
              </svg>
              <svg id="btn-login-spinner" style="display: none; width: 16px; height: 16px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>

          </form>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             STEP 2: 2FA ONE-TIME PASSWORD (OTP) VERIFICATION FORM
             ══════════════════════════════════════════════════════════ -->
        <div id="otp-step-container" style="display: none;">
          <form id="otp-form" onsubmit="event.preventDefault(); submitOtpVerification();">
            
            <p style="font-size: 13px; color: #475569; line-height: 1.5;">
              Please enter the 6-digit security code sent to <strong id="otp-masked-email-display" style="color: #0F172A;">your email</strong>.
            </p>

            <!-- 6 Numeric OTP Digit Boxes -->
            <div class="otp-inputs-grid">
              <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit-box" data-index="0" autocomplete="one-time-code">
              <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit-box" data-index="1">
              <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit-box" data-index="2">
              <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit-box" data-index="3">
              <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit-box" data-index="4">
              <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]*" class="otp-digit-box" data-index="5">
            </div>

            <!-- Hidden aggregated OTP field -->
            <input type="hidden" id="full-otp-value" name="otp">

            <!-- Remember This Device Checkbox -->
            <div style="margin-top: 14px; margin-bottom: 12px;">
              <label class="remember-label" style="cursor: pointer; display: inline-flex; align-items: center; gap: 8px;">
                <input type="checkbox" id="otp-remember-me" name="remember_me" class="remember-checkbox" checked>
                <span style="font-size: 12.5px; color: #475569; font-weight: 600;">Remember this device for 15 days (Skip OTP on next login)</span>
              </label>
            </div>

            <div class="otp-footer-controls">
              <span>Didn't receive code?</span>
              <button type="button" id="resend-otp-btn" class="resend-btn" onclick="handleResendOtp()">
                Resend Code <span id="resend-timer-text"></span>
              </button>
            </div>

            <!-- OTP Submit Button -->
            <button type="submit" id="otp-submit-btn" class="btn-auth-submit">
              <span id="btn-otp-text">Verify & Continue</span>
              <svg id="btn-otp-arrow" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
              </svg>
              <svg id="btn-otp-spinner" style="display: none; width: 16px; height: 16px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
                <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </button>

            <!-- Back to Step 1 Button -->
            <div style="text-align: center;">
              <button type="button" class="back-btn" onclick="backToCredentialsStep()">
                <svg style="width: 14px; height: 14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <span>Back to Sign In</span>
              </button>
            </div>

          </form>
        </div>

        <!-- ══════════════════════════════════════════════════════════
             STEP 3: CONCURRENT LOGIN - ACTIVE SESSION APPROVAL
             ══════════════════════════════════════════════════════════ -->
        <div id="approval-step-container" style="display: none;">
          <!-- WAITING SUB-STATE -->
          <div id="approval-state-waiting">
            <div class="text-center p-6 bg-slate-50 border border-slate-200/80 rounded-2xl mb-5">
              <div class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center mx-auto mb-3.5">
                <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
              </div>

              <h4 class="text-base font-bold text-slate-900 mb-1">
                Check Your Active Device
              </h4>
              <p class="text-xs text-slate-500 leading-relaxed mb-4 max-w-xs mx-auto">
                An active session was detected. Please approve the prompt on your other device to continue.
              </p>

              <!-- Live Timer & Progress -->
              <div class="bg-white border border-slate-200/80 rounded-xl p-3 text-left">
                <div class="flex justify-between items-center text-xs font-semibold text-slate-600 mb-2">
                  <span class="flex items-center gap-1.5 text-slate-600">
                    <span class="w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                    Awaiting response...
                  </span>
                  <span id="approval-timer-badge" class="font-mono font-bold text-blue-600">5:00</span>
                </div>
                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div id="approval-progress-fill" class="h-full w-full bg-blue-600 rounded-full transition-all duration-1000 ease-linear"></div>
                </div>
              </div>
            </div>

            <!-- Cancel Button -->
            <button type="button" class="btn-auth-submit" onclick="cancelApprovalWaiting()" style="background: #ffffff; color: #475569; border: 1px solid #cbd5e1; box-shadow: none;">
              <span>Cancel Sign In</span>
            </button>
          </div>

          <!-- DENIED SUB-STATE -->
          <div id="approval-state-denied" style="display: none;">
            <div class="text-center p-6 bg-slate-50 border border-slate-200/80 rounded-2xl mb-5">
              <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center mx-auto mb-3.5">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
              </div>

              <h4 class="text-base font-bold text-slate-900 mb-1">
                Login Request Denied
              </h4>
              <p class="text-xs text-slate-500 leading-relaxed mb-3">
                The user signed into this account on another device rejected this sign-in attempt.
              </p>
              <div class="bg-white border border-slate-200/80 rounded-xl p-2.5 text-xs text-slate-600 text-left">
                If you suspect unauthorized access, please reset your password immediately.
              </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
              <button type="button" class="btn-auth-submit" onclick="backToCredentialsStep()">
                <span>Try Again</span>
              </button>
              <button type="button" class="btn-auth-submit" onclick="openForgotPasswordModal()" style="background: #ffffff; color: #334155; border: 1px solid #cbd5e1; box-shadow: none;">
                <span>Reset Password</span>
              </button>
            </div>
          </div>

          <!-- EXPIRED SUB-STATE -->
          <div id="approval-state-expired" style="display: none;">
            <div class="text-center p-6 bg-slate-50 border border-slate-200/80 rounded-2xl mb-5">
              <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-700 border border-slate-200 flex items-center justify-center mx-auto mb-3.5">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>

              <h4 class="text-base font-bold text-slate-900 mb-1">
                Request Expired
              </h4>
              <p class="text-xs text-slate-500 leading-relaxed">
                The active device did not respond within the time limit.
              </p>
            </div>

            <button type="button" class="btn-auth-submit" onclick="backToCredentialsStep()">
              <span>Return to Sign In</span>
            </button>
          </div>
        </div>

        <!-- Support / Assistance Callout -->
        <div class="auth-help-box">
          <div>Trouble signing in? Contact Campus IT at</div>
          <a href="mailto:attendance.admin@bcp.edu.ph" class="auth-help-email">
            attendance.admin@bcp.edu.ph
          </a>
        </div>

      </div>
    </div>

  </div>

  <!-- ══════════════════════════════════════════════════════════
       FORGOT PASSWORD & RESET MODAL (Minimalist Layout)
       ══════════════════════════════════════════════════════════ -->
  <div id="forgot-password-modal" class="modal-backdrop">
    <div class="modal-dialog">
      <button type="button" class="modal-close-btn" onclick="closeForgotPasswordModal()" aria-label="Close modal">
        <svg style="width: 18px; height: 18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>

      <!-- Modal Alert -->
      <div id="modal-alert-banner" class="auth-alert hidden">
        <svg id="modal-alert-icon" style="width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
        <span id="modal-alert-message"></span>
      </div>

      <!-- Modal Step 1: Request Email -->
      <div id="modal-step-email">
        <div style="margin-bottom: 20px;">
          <span style="font-size: 11px; font-weight: 700; color: #2563eb; letter-spacing: 0.05em; text-transform: uppercase; display: block; margin-bottom: 4px;">
            ACCOUNT RECOVERY
          </span>
          <h3 style="font-size: 18px; font-weight: 800; color: #0f172a;">
            Reset Your Password
          </h3>
          <p style="font-size: 12.5px; color: #64748b; margin-top: 3px;">
            Enter your registered institutional email to receive a 6-digit security code.
          </p>
        </div>

        <form onsubmit="event.preventDefault(); handleSendResetOtp();">
          <div class="form-group">
            <label for="reset-email-input" class="form-label-text">Institutional Email</label>
            <div class="custom-field-box">
              <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
              </svg>
              <input type="email" id="reset-email-input" class="custom-input-field" placeholder="e.g. yourname@bcp.edu.ph" required autocomplete="email">
            </div>
          </div>

          <button type="submit" id="btn-send-reset-otp" class="btn-auth-submit" style="margin-top: 18px;">
            <span id="btn-send-reset-text">Send Verification Code</span>
            <svg id="btn-send-reset-spinner" style="display: none; width: 16px; height: 16px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
              <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>
        </form>
      </div>

      <!-- Modal Step 2: Enter & Verify 6-Digit OTP -->
      <div id="modal-step-otp" style="display: none;">
        <div style="margin-bottom: 20px;">
          <span style="font-size: 11px; font-weight: 700; color: #2563eb; letter-spacing: 0.05em; text-transform: uppercase; display: block; margin-bottom: 4px;">
            SECURITY VERIFICATION
          </span>
          <h3 style="font-size: 18px; font-weight: 800; color: #0f172a;">
            Verify Security Code
          </h3>
          <p style="font-size: 12.5px; color: #64748b; margin-top: 3px;">
            Enter the 6-digit code sent to <strong id="modal-masked-email" style="color: #0f172a;">your email</strong>.
          </p>
        </div>

        <form onsubmit="event.preventDefault(); handleVerifyResetOtp();">
          <div class="form-group" style="margin-bottom: 18px;">
            <label for="reset-otp-input" class="form-label-text">6-Digit Verification Code</label>
            <div class="custom-field-box">
              <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              <input type="text" id="reset-otp-input" class="custom-input-field" maxlength="6" placeholder="e.g. 123456" required inputmode="numeric" style="letter-spacing: 4px; font-weight: 700; font-size: 16px;">
            </div>
          </div>

          <button type="submit" id="btn-verify-reset-otp" class="btn-auth-submit" style="margin-bottom: 12px;">
            <span id="btn-verify-otp-text">Verify Code &amp; Continue</span>
            <svg id="btn-verify-otp-spinner" style="display: none; width: 16px; height: 16px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
              <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>

          <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; padding: 4px 2px;">
            <button type="button" onclick="backToResetEmailStep()" style="background: none; border: none; color: #64748b; font-weight: 600; cursor: pointer; padding: 0; text-decoration: underline;">
              ← Change Email
            </button>
            <button type="button" id="modal-resend-btn" onclick="handleResendResetOtp()" style="background: none; border: none; color: #2563eb; font-weight: 700; cursor: pointer; padding: 0;">
              Resend Code <span id="modal-resend-timer"></span>
            </button>
          </div>
        </form>
      </div>

      <!-- Modal Step 3: Enter New Password & Confirm New Password -->
      <div id="modal-step-new-pass" style="display: none;">
        <div style="margin-bottom: 20px;">
          <span style="font-size: 11px; font-weight: 700; color: #059669; letter-spacing: 0.05em; text-transform: uppercase; display: block; margin-bottom: 4px;">
            ✓ IDENTITY VERIFIED
          </span>
          <h3 style="font-size: 18px; font-weight: 800; color: #0f172a;">
            Set New Password
          </h3>
          <p style="font-size: 12.5px; color: #64748b; margin-top: 3px;">
            Create a secure new password for <strong id="modal-verified-email" style="color: #0f172a;">your account</strong>.
          </p>
        </div>

        <form onsubmit="event.preventDefault(); handleUpdateNewPassword();">
          <!-- New Password -->
          <div class="form-group" style="margin-bottom: 8px;">
            <label for="new-pass-input" class="form-label-text">New Password</label>
            <div class="custom-field-box">
              <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
              </svg>
              <input type="password" id="new-pass-input" class="custom-input-field" placeholder="••••••••" required oninput="validatePasswordLive()">
              <button type="button" class="toggle-pass-btn" onclick="togglePasswordVisibility('new-pass-input', 'modal-eye-1')" aria-label="Toggle password visibility">
                <svg id="modal-eye-1" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
            </div>
          </div>

          <!-- Password Security Requirements Indicator -->
          <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 12px; margin-bottom: 14px;">
            <div class="password-rule-row" id="rule-length">
              <span class="rule-bullet">•</span>
              <span>At least 6 characters</span>
            </div>
            <div class="password-rule-row" id="rule-capital">
              <span class="rule-bullet">•</span>
              <span>At least 1 uppercase letter (A-Z)</span>
            </div>
          </div>

          <!-- Confirm Password -->
          <div class="form-group" style="margin-bottom: 20px;">
            <label for="confirm-pass-input" class="form-label-text">Confirm New Password</label>
            <div class="custom-field-box">
              <svg class="field-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              <input type="password" id="confirm-pass-input" class="custom-input-field" placeholder="••••••••" required oninput="validatePasswordLive()">
              <button type="button" class="toggle-pass-btn" onclick="togglePasswordVisibility('confirm-pass-input', 'modal-eye-2')" aria-label="Toggle password visibility">
                <svg id="modal-eye-2" style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
              </button>
            </div>
          </div>

          <button type="submit" id="btn-save-new-pass" class="btn-auth-submit">
            <span id="btn-save-pass-text">Save Password & Sign In</span>
            <svg id="btn-save-pass-spinner" style="display: none; width: 16px; height: 16px; animation: spin 1s linear infinite;" fill="none" viewBox="0 0 24 24">
              <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
          </button>
        </form>
      </div>

    </div>
  </div>

  <!-- ══════════════════════════════════════════════════════════
       PRODUCTION AUTHENTICATION, OTP & PASSWORD RESET JAVASCRIPT
       ══════════════════════════════════════════════════════════ -->
  <script>
    // Clean ?logged_out=1 or ?session_expired=1 from address bar so page refreshes don't re-trigger
    if (window.location.search.includes('logged_out=1') || window.location.search.includes('session_expired=1')) {
      window.history.replaceState({}, document.title, window.location.pathname);
      setTimeout(() => {
        const loAlert = document.getElementById('logged-out-alert');
        if (loAlert) {
          loAlert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
          loAlert.style.opacity = '0';
          loAlert.style.transform = 'translateY(-4px)';
          setTimeout(() => loAlert.remove(), 400);
        }
        const seAlert = document.getElementById('session-expired-alert');
        if (seAlert) {
          seAlert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
          seAlert.style.opacity = '0';
          seAlert.style.transform = 'translateY(-4px)';
          setTimeout(() => seAlert.remove(), 400);
        }
      }, 5000);
    }

    var resendCountdown = 60;
    var resendInterval = null;
    var modalResendCountdown = 60;
    var modalResendInterval = null;
    var resetActiveEmail = '';
    var resetActiveOtp = '';
    var approvalPollInterval = null;
    var approvalCountdownInterval = null;
    var activeApprovalRequestId = null;

    // Password Visibility Toggle
    function togglePasswordVisibility(inputId, iconId) {
      const input = document.getElementById(inputId);
      const icon = document.getElementById(iconId);
      if (!input || !icon) return;

      if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.11 6.11m3.768 3.768L6.11 6.11m0 0L3 3m3.11 3.11l4.242 4.243m6.768 6.768L21 21m-3.11-3.11l-4.243-4.243m4.243 4.243l-4.242-4.242"/>';
      } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
      }
    }

    // Alert Banner Functions
    function showAlert(msg, isError = true) {
      const banner = document.getElementById('alert-banner');
      const text = document.getElementById('alert-message');
      const icon = document.getElementById('alert-icon');
      
      banner.classList.remove('hidden', 'error', 'success');
      
      if (isError) {
        banner.classList.add('error');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
      } else {
        banner.classList.add('success');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
      }
      
      text.textContent = msg;
    }

    function hideAlert() {
      document.getElementById('alert-banner').classList.add('hidden');
    }

    // Modal Alert
    function showModalAlert(msg, isError = true) {
      const banner = document.getElementById('modal-alert-banner');
      const text = document.getElementById('modal-alert-message');
      const icon = document.getElementById('modal-alert-icon');
      
      banner.classList.remove('hidden', 'error', 'success');
      if (isError) {
        banner.classList.add('error');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
      } else {
        banner.classList.add('success');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
      }
      text.textContent = msg;
    }

    function hideModalAlert() {
      document.getElementById('modal-alert-banner').classList.add('hidden');
    }

    // Step 2 OTP Transition
    function showOtpStep(maskedEmail) {
      hideAlert();
      if (approvalPollInterval) clearInterval(approvalPollInterval);
      if (approvalCountdownInterval) clearInterval(approvalCountdownInterval);
      activeApprovalRequestId = null;

      document.getElementById('credentials-step-container').style.display = 'none';
      document.getElementById('approval-step-container').style.display = 'none';
      document.getElementById('otp-step-container').style.display = 'block';

      document.getElementById('auth-flow-eyebrow').textContent = '2-FACTOR VERIFICATION';
      document.getElementById('auth-flow-title').textContent = 'Verify Security Code';
      document.getElementById('auth-flow-subtitle').textContent = 'Enter the 6-digit verification code sent to your registered email.';
      document.getElementById('otp-masked-email-display').textContent = maskedEmail || 'your email';

      const otpBoxes = document.querySelectorAll('.otp-digit-box');
      otpBoxes.forEach(b => { b.value = ''; b.classList.remove('filled'); });
      updateFullOtpValue();

      showAlert(`A 6-digit verification code has been sent to ${maskedEmail}. Please check your inbox.`, false);

      if (otpBoxes[0]) otpBoxes[0].focus();
      startResendCountdown();
    }

    var isApprovalFinished = false;

    function formatApprovalCountdown(sec) {
      const s = Math.max(0, parseInt(sec, 10) || 0);
      const mins = Math.floor(s / 60);
      const rem = s % 60;
      return `${mins}:${rem < 10 ? '0' : ''}${rem}`;
    }

    // Step 3 Device Approval Waiting Transition
    function showApprovalWaitingStep(data) {
      hideAlert();
      isApprovalFinished = false;
      if (resendInterval) clearInterval(resendInterval);
      if (approvalCountdownInterval) clearInterval(approvalCountdownInterval);
      if (approvalPollInterval) clearInterval(approvalPollInterval);

      document.getElementById('credentials-step-container').style.display = 'none';
      document.getElementById('otp-step-container').style.display = 'none';
      document.getElementById('approval-step-container').style.display = 'block';

      document.getElementById('approval-state-waiting').style.display = 'block';
      document.getElementById('approval-state-denied').style.display = 'none';
      document.getElementById('approval-state-expired').style.display = 'none';

      document.getElementById('auth-flow-eyebrow').textContent = 'DEVICE AUTHORIZATION';
      document.getElementById('auth-flow-title').textContent = 'Authorize This Device';
      document.getElementById('auth-flow-subtitle').textContent = 'Confirming identity with your active device session.';

      activeApprovalRequestId = data.request_id;
      const totalSeconds = data.expires_in_seconds || 300;
      let remainingSeconds = totalSeconds;

      const timerBadge = document.getElementById('approval-timer-badge');
      const progressFill = document.getElementById('approval-progress-fill');

      if (timerBadge) timerBadge.textContent = formatApprovalCountdown(remainingSeconds);
      if (progressFill) progressFill.style.width = '100%';

      approvalCountdownInterval = setInterval(() => {
        if (isApprovalFinished) return;
        remainingSeconds--;
        if (timerBadge) timerBadge.textContent = formatApprovalCountdown(Math.max(0, remainingSeconds));
        if (progressFill) {
          const pct = Math.max(0, (remainingSeconds / totalSeconds) * 100);
          progressFill.style.width = `${pct}%`;
        }

        if (remainingSeconds <= 0) {
          clearInterval(approvalCountdownInterval);
          if (!isApprovalFinished) {
            handleApprovalExpired();
          }
        }
      }, 1000);

      // Fast polling every 500ms for immediate reaction when active device approves/rejects
      approvalPollInterval = setInterval(async () => {
        if (!activeApprovalRequestId || isApprovalFinished) return;
        try {
          const res = await fetch(`<?php echo url("api/auth/login-request-status"); ?>?request_id=${encodeURIComponent(activeApprovalRequestId)}`, {
            headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' }
          });
          const pollData = await res.json();
          if (!res.ok || isApprovalFinished) return;

          if (pollData.status === 'approved' || pollData.status === 'completed') {
            isApprovalFinished = true;
            clearInterval(approvalPollInterval);
            clearInterval(approvalCountdownInterval);
            activeApprovalRequestId = null;
            
            showAlert(pollData.message || 'Login approved! Loading your workspace...', false);
            if (typeof APP !== 'undefined' && APP.showLoadingScreen) {
              APP.showLoadingScreen({
                title: 'Session Authorized',
                subtitle: 'Redirecting to your dashboard...'
              });
            }
            setTimeout(() => {
              window.location.href = pollData.redirect_url || '<?php echo url("dashboard"); ?>';
            }, 300);
          } else if (pollData.status === 'rejected') {
            isApprovalFinished = true;
            clearInterval(approvalPollInterval);
            clearInterval(approvalCountdownInterval);
            activeApprovalRequestId = null;
            handleApprovalDenied(pollData.message);
          } else if (pollData.status === 'expired') {
            if (!isApprovalFinished) {
              isApprovalFinished = true;
              clearInterval(approvalPollInterval);
              clearInterval(approvalCountdownInterval);
              activeApprovalRequestId = null;
              handleApprovalExpired();
            }
          }
        } catch (e) {
          console.warn('Poll approval error:', e);
        }
      }, 500);
    }

    function handleApprovalDenied(msg) {
      if (isApprovalFinished && document.getElementById('approval-state-denied').style.display === 'block') return;
      document.getElementById('approval-state-waiting').style.display = 'none';
      document.getElementById('approval-state-expired').style.display = 'none';
      document.getElementById('approval-state-denied').style.display = 'block';

      document.getElementById('auth-flow-eyebrow').textContent = 'ACCESS DENIED';
      document.getElementById('auth-flow-title').textContent = 'Login Denied';
      document.getElementById('auth-flow-subtitle').textContent = 'This sign-in attempt was rejected.';
    }

    function handleApprovalExpired() {
      if (isApprovalFinished) return;
      document.getElementById('approval-state-waiting').style.display = 'none';
      document.getElementById('approval-state-denied').style.display = 'none';
      document.getElementById('approval-state-expired').style.display = 'block';

      document.getElementById('auth-flow-eyebrow').textContent = 'REQUEST TIMED OUT';
      document.getElementById('auth-flow-title').textContent = 'Authorization Expired';
      document.getElementById('auth-flow-subtitle').textContent = 'The active device did not respond in time.';
    }

    function cancelApprovalWaiting() {
      isApprovalFinished = true;
      if (approvalPollInterval) clearInterval(approvalPollInterval);
      if (approvalCountdownInterval) clearInterval(approvalCountdownInterval);
      activeApprovalRequestId = null;
      backToCredentialsStep();
    }

    function backToCredentialsStep() {
      hideAlert();
      isApprovalFinished = true;
      if (approvalPollInterval) clearInterval(approvalPollInterval);
      if (approvalCountdownInterval) clearInterval(approvalCountdownInterval);
      activeApprovalRequestId = null;

      document.getElementById('otp-step-container').style.display = 'none';
      document.getElementById('approval-step-container').style.display = 'none';
      document.getElementById('credentials-step-container').style.display = 'block';

      document.getElementById('auth-flow-eyebrow').textContent = 'SECURE SIGN IN';
      document.getElementById('auth-flow-title').textContent = 'Sign In to Portal';
      document.getElementById('auth-flow-subtitle').textContent = 'Use your institutional credentials to authenticate.';

      if (resendInterval) clearInterval(resendInterval);
    }

    function startResendCountdown() {
      if (resendInterval) clearInterval(resendInterval);
      resendCountdown = 60;
      const resendBtn = document.getElementById('resend-otp-btn');
      const timerText = document.getElementById('resend-timer-text');

      resendBtn.disabled = true;
      timerText.textContent = `(${resendCountdown}s)`;

      resendInterval = setInterval(() => {
        resendCountdown--;
        if (resendCountdown <= 0) {
          clearInterval(resendInterval);
          resendBtn.disabled = false;
          timerText.textContent = '';
        } else {
          timerText.textContent = `(${resendCountdown}s)`;
        }
      }, 1000);
    }

    // 6-Digit OTP Box Auto-Advance
    var otpBoxes = document.querySelectorAll('.otp-digit-box');
    otpBoxes.forEach((box, idx) => {
      box.addEventListener('input', (e) => {
        const val = e.target.value.replace(/\D/g, '');
        e.target.value = val ? val.slice(-1) : '';

        if (e.target.value) {
          box.classList.add('filled');
          if (idx < otpBoxes.length - 1) {
            otpBoxes[idx + 1].focus();
          }
        } else {
          box.classList.remove('filled');
        }

        updateFullOtpValue();
      });

      box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && idx > 0) {
          otpBoxes[idx - 1].focus();
          otpBoxes[idx - 1].value = '';
          otpBoxes[idx - 1].classList.remove('filled');
          updateFullOtpValue();
        }
      });

      box.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasteData = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        if (pasteData) {
          const digits = pasteData.slice(0, 6).split('');
          digits.forEach((d, i) => {
            if (otpBoxes[i]) {
              otpBoxes[i].value = d;
              otpBoxes[i].classList.add('filled');
            }
          });
          const targetIdx = Math.min(digits.length, 5);
          otpBoxes[targetIdx].focus();
          updateFullOtpValue();

          if (digits.length === 6) {
            submitOtpVerification();
          }
        }
      });
    });

    function updateFullOtpValue() {
      let fullOtp = '';
      otpBoxes.forEach(b => { fullOtp += b.value; });
      document.getElementById('full-otp-value').value = fullOtp;
      return fullOtp;
    }

    // STEP 1: Submit Credentials
    async function submitCredentials() {
      const identifier = document.getElementById('login-identifier').value.trim();
      const password   = document.getElementById('login-password').value;

      const btn        = document.getElementById('credentials-submit-btn');
      const btnText    = document.getElementById('btn-login-text');
      const btnArrow   = document.getElementById('btn-login-arrow');
      const btnSpinner = document.getElementById('btn-login-spinner');

      if (!identifier || !password) {
        showAlert('Please provide both institutional email/ID and password.');
        return;
      }

      btn.disabled = true;
      btnText.textContent = 'Verifying Credentials...';
      if (btnArrow) btnArrow.style.display = 'none';
      if (btnSpinner) btnSpinner.style.display = 'inline-block';
      hideAlert();

      try {
        const res = await fetch('<?php echo url("api/auth/login"); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            identifier, 
            password
          })
        });

        const data = await res.json();

        if (res.ok && (data.status === 'authenticated' || data.status === 'success')) {
          showAlert(data.message || 'Authentication successful! Loading workspace...', false);
          
          if (typeof APP !== 'undefined' && APP.showLoadingScreen) {
            APP.showLoadingScreen({
              title: 'Authenticating Workspace...',
              subtitle: 'Redirecting to your dashboard...'
            });
          }

          setTimeout(() => {
            window.location.href = data.redirect_url;
          }, 300);
        } else if (res.ok && data.status === 'otp_required') {
          showOtpStep(data.masked_email);
        } else if (res.ok && data.status === 'awaiting_device_approval') {
          showApprovalWaitingStep(data);
        } else {
          showAlert(data.message || 'Invalid institutional ID/email or password.');
        }
      } catch (err) {
        console.error('Login error:', err);
        showAlert('Authentication service encountered an error. Please try again.');
      } finally {
        btn.disabled = false;
        btnText.textContent = 'Sign In';
        if (btnArrow) btnArrow.style.display = 'inline-block';
        if (btnSpinner) btnSpinner.style.display = 'none';
      }
    }

    // STEP 2: Submit OTP Verification
    async function submitOtpVerification() {
      const otp = updateFullOtpValue();
      const rememberMe = document.getElementById('otp-remember-me') ? document.getElementById('otp-remember-me').checked : true;
      const btn        = document.getElementById('otp-submit-btn');
      const btnText    = document.getElementById('btn-otp-text');
      const btnArrow   = document.getElementById('btn-otp-arrow');
      const btnSpinner = document.getElementById('btn-otp-spinner');

      if (!otp || otp.length < 6) {
        showAlert('Please enter the complete 6-digit verification code.');
        return;
      }

      btn.disabled = true;
      btnText.textContent = 'Verifying Code...';
      if (btnArrow) btnArrow.style.display = 'none';
      if (btnSpinner) btnSpinner.style.display = 'inline-block';
      hideAlert();

      try {
        const res = await fetch('<?php echo url("api/auth/verify-otp"); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            otp,
            remember_me: rememberMe
          })
        });

        const data = await res.json();

        if (res.ok && (data.status === 'success' || data.status === 'authenticated')) {
          showAlert(data.message || 'Verification successful! Loading dashboard...', false);
          
          if (typeof APP !== 'undefined' && APP.showLoadingScreen) {
            APP.showLoadingScreen({
              title: 'Authenticating Workspace...',
              subtitle: 'Redirecting to your dashboard...'
            });
          }

          setTimeout(() => {
            window.location.href = data.redirect_url;
          }, 350);
        } else if (res.ok && data.status === 'awaiting_device_approval') {
          showApprovalWaitingStep(data);
        } else {
          showAlert(data.message || 'Invalid or expired verification code.');
          btn.disabled = false;
          btnText.textContent = 'Verify & Continue';
          if (btnArrow) btnArrow.style.display = 'inline-block';
          if (btnSpinner) btnSpinner.style.display = 'none';
        }
      } catch (err) {
        console.error('OTP verify error:', err);
        showAlert('Security service error. Please try again.');
        btn.disabled = false;
        btnText.textContent = 'Verify & Continue';
        if (btnArrow) btnArrow.style.display = 'inline-block';
        if (btnSpinner) btnSpinner.style.display = 'none';
      }
    }

    // Resend OTP
    async function handleResendOtp() {
      const btn = document.getElementById('resend-otp-btn');
      btn.disabled = true;
      hideAlert();

      try {
        const res = await fetch('<?php echo url("api/auth/resend-otp"); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }
        });

        const data = await res.json();
        if (res.ok && data.status === 'success') {
          showAlert(`A fresh verification code has been sent to ${data.masked_email}.`, false);
          const otpBoxes = document.querySelectorAll('.otp-digit-box');
          otpBoxes.forEach(b => { b.value = ''; b.classList.remove('filled'); });
          updateFullOtpValue();
          if (otpBoxes[0]) otpBoxes[0].focus();
          startResendCountdown();
        } else {
          showAlert(data.message || 'Unable to resend code at this time.');
          btn.disabled = false;
        }
      } catch (err) {
        showAlert('Failed to connect to authentication server.');
        btn.disabled = false;
      }
    }

    // ── FORGOT PASSWORD MODAL HANDLERS ──────────────────────
    function openForgotPasswordModal() {
      hideAlert();
      hideModalAlert();
      resetActiveEmail = '';
      resetActiveOtp = '';
      document.getElementById('modal-step-email').style.display = 'block';
      document.getElementById('modal-step-otp').style.display = 'none';
      document.getElementById('modal-step-new-pass').style.display = 'none';

      document.getElementById('reset-email-input').value = document.getElementById('login-identifier').value || '';
      document.getElementById('reset-otp-input').value = '';
      document.getElementById('new-pass-input').value = '';
      document.getElementById('confirm-pass-input').value = '';

      if (modalResendInterval) clearInterval(modalResendInterval);

      document.getElementById('forgot-password-modal').classList.add('open');
    }

    function closeForgotPasswordModal() {
      document.getElementById('forgot-password-modal').classList.remove('open');
      hideModalAlert();
      if (modalResendInterval) clearInterval(modalResendInterval);
    }

    // Modal Step 1: Send Reset OTP
    async function handleSendResetOtp() {
      const email = document.getElementById('reset-email-input').value.trim();
      const btn = document.getElementById('btn-send-reset-otp');
      const text = document.getElementById('btn-send-reset-text');
      const spinner = document.getElementById('btn-send-reset-spinner');

      if (!email) {
        showModalAlert('Please enter your institutional email address.');
        return;
      }

      btn.disabled = true;
      text.textContent = 'Sending Code...';
      if (spinner) spinner.style.display = 'inline-block';
      hideModalAlert();

      try {
        const res = await fetch('<?php echo url("api/auth/forgot-password"); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ email })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
          resetActiveEmail = email;
          document.getElementById('modal-masked-email').textContent = data.masked_email || email;
          document.getElementById('modal-step-email').style.display = 'none';
          document.getElementById('modal-step-otp').style.display = 'block';
          document.getElementById('modal-step-new-pass').style.display = 'none';
          document.getElementById('reset-otp-input').value = '';
          document.getElementById('reset-otp-input').focus();
          
          startModalResendCountdown();
          showModalAlert(`Verification code sent to ${data.masked_email}. Please check your inbox.`, false);
        } else {
          showModalAlert(data.message || 'No user account found with this email.');
        }
      } catch (err) {
        showModalAlert('Connection error. Please try again.');
      } finally {
        btn.disabled = false;
        text.textContent = 'Send Verification Code';
        if (spinner) spinner.style.display = 'none';
      }
    }

    // Modal Step 2: Go back to Email Step
    function backToResetEmailStep() {
      hideModalAlert();
      document.getElementById('modal-step-otp').style.display = 'none';
      document.getElementById('modal-step-new-pass').style.display = 'none';
      document.getElementById('modal-step-email').style.display = 'block';
      document.getElementById('reset-email-input').focus();
    }

    // Modal Step 2: Resend Reset OTP
    async function handleResendResetOtp() {
      const btn = document.getElementById('modal-resend-btn');
      if (!resetActiveEmail) return;

      btn.disabled = true;
      hideModalAlert();

      try {
        const res = await fetch('<?php echo url("api/auth/forgot-password"); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ email: resetActiveEmail })
        });

        const data = await res.json();
        if (res.ok && data.status === 'success') {
          document.getElementById('reset-otp-input').value = '';
          document.getElementById('reset-otp-input').focus();
          showModalAlert(`A fresh verification code has been sent to ${data.masked_email}.`, false);
          startModalResendCountdown();
        } else {
          showModalAlert(data.message || 'Unable to resend code.');
          btn.disabled = false;
        }
      } catch (err) {
        showModalAlert('Connection error resending code.');
        btn.disabled = false;
      }
    }

    function startModalResendCountdown() {
      if (modalResendInterval) clearInterval(modalResendInterval);
      modalResendCountdown = 60;
      const resendBtn = document.getElementById('modal-resend-btn');
      const timerText = document.getElementById('modal-resend-timer');

      resendBtn.disabled = true;
      timerText.textContent = `(${modalResendCountdown}s)`;

      modalResendInterval = setInterval(() => {
        modalResendCountdown--;
        if (modalResendCountdown <= 0) {
          clearInterval(modalResendInterval);
          resendBtn.disabled = false;
          timerText.textContent = '';
        } else {
          timerText.textContent = `(${modalResendCountdown}s)`;
        }
      }, 1000);
    }

    // Modal Step 2: Verify OTP
    async function handleVerifyResetOtp() {
      const otp = document.getElementById('reset-otp-input').value.trim();
      const btn = document.getElementById('btn-verify-reset-otp');
      const text = document.getElementById('btn-verify-otp-text');
      const spinner = document.getElementById('btn-verify-otp-spinner');

      if (!otp || otp.length < 6) {
        showModalAlert('Please enter the full 6-digit verification code.');
        return;
      }

      btn.disabled = true;
      text.textContent = 'Verifying Code...';
      if (spinner) spinner.style.display = 'inline-block';
      hideModalAlert();

      try {
        const res = await fetch('<?php echo url("api/auth/verify-reset-otp"); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            email: resetActiveEmail,
            otp: otp
          })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
          resetActiveOtp = otp;
          // Transition to Step 3 (New Password + Confirm Password)
          document.getElementById('modal-verified-email').textContent = resetActiveEmail;
          document.getElementById('modal-step-email').style.display = 'none';
          document.getElementById('modal-step-otp').style.display = 'none';
          document.getElementById('modal-step-new-pass').style.display = 'block';

          document.getElementById('new-pass-input').value = '';
          document.getElementById('confirm-pass-input').value = '';
          document.getElementById('new-pass-input').focus();
          validatePasswordLive();

          showModalAlert('Code verified! Please create your new password.', false);
        } else {
          showModalAlert(data.message || 'Invalid or expired verification code.');
        }
      } catch (err) {
        showModalAlert('Verification service error. Please try again.');
      } finally {
        btn.disabled = false;
        text.textContent = 'Verify Code & Continue';
        if (spinner) spinner.style.display = 'none';
      }
    }

    // Live Password Strength Validation (Min 6 chars + 1 capital letter)
    function validatePasswordLive() {
      const pass = document.getElementById('new-pass-input').value;
      const ruleLength = document.getElementById('rule-length');
      const ruleCapital = document.getElementById('rule-capital');

      const isLongEnough = pass.length >= 6;
      const hasCapital = /[A-Z]/.test(pass);

      if (isLongEnough) {
        ruleLength.classList.add('valid');
        ruleLength.querySelector('.rule-bullet').textContent = '✓';
      } else {
        ruleLength.classList.remove('valid');
        ruleLength.querySelector('.rule-bullet').textContent = '•';
      }

      if (hasCapital) {
        ruleCapital.classList.add('valid');
        ruleCapital.querySelector('.rule-bullet').textContent = '✓';
      } else {
        ruleCapital.classList.remove('valid');
        ruleCapital.querySelector('.rule-bullet').textContent = '•';
      }

      return isLongEnough && hasCapital;
    }

    // Modal Step 3: Save New Password (after OTP is verified)
    async function handleUpdateNewPassword() {
      const otp = resetActiveOtp || document.getElementById('reset-otp-input').value.trim();
      const newPassword = document.getElementById('new-pass-input').value;
      const confirmPass = document.getElementById('confirm-pass-input').value;

      const btn = document.getElementById('btn-save-new-pass');
      const text = document.getElementById('btn-save-pass-text');
      const spinner = document.getElementById('btn-save-pass-spinner');

      if (!validatePasswordLive()) {
        showModalAlert('Password must be at least 6 characters and contain at least 1 capital letter.');
        return;
      }

      if (newPassword !== confirmPass) {
        showModalAlert('Passwords do not match. Please re-check.');
        return;
      }

      btn.disabled = true;
      text.textContent = 'Updating Password...';
      if (spinner) spinner.style.display = 'inline-block';
      hideModalAlert();

      try {
        const res = await fetch('<?php echo url("api/auth/reset-password"); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            email: resetActiveEmail,
            otp: otp,
            new_password: newPassword,
            confirm_password: confirmPass
          })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
          closeForgotPasswordModal();
          showAlert('Password successfully updated! You can now sign in with your new password.', false);
          document.getElementById('login-identifier').value = resetActiveEmail;
          document.getElementById('login-password').value = newPassword;
        } else {
          showModalAlert(data.message || 'Invalid or expired OTP code.');
        }
      } catch (err) {
        showModalAlert('Server error updating password.');
      } finally {
        btn.disabled = false;
        text.textContent = 'Save Password & Sign In';
        if (spinner) spinner.style.display = 'none';
      }
    }
  </script>
  <!-- Global App JS for Utilities -->
  <script src="<?php echo url('assets/js/app.js'); ?>"></script>
</body>
</html>
