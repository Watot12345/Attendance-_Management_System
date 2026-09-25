<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Scan Attendance QR';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__) . '/partials/header.php';

$db = Database::getConnection();

// Handle testing/switching student
$selectedStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($selectedStudentId > 0) {
    $_SESSION['active_student_test_id'] = $selectedStudentId;
}

$activeStudentId = $_SESSION['active_student_test_id'] ?? (int)($_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? 1);

// Fetch student profile from database
$stStmt = $db->prepare("
    SELECT user_id, student_id, first_name, last_name, email 
    FROM users 
    WHERE user_id = ? AND role = 'student' 
    LIMIT 1
");
$stStmt->execute([$activeStudentId]);
$currentStudent = $stStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentStudent) {
    // Fallback to first student in users table
    $currentStudent = $db->query("
        SELECT user_id, student_id, first_name, last_name, email 
        FROM users 
        WHERE role = 'student' 
        ORDER BY user_id ASC 
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
}

$studentUserId = (int)($currentStudent['user_id'] ?? 1);
$studentName = ($currentStudent['first_name'] ?? 'Juan') . ' ' . ($currentStudent['last_name'] ?? 'Dela Cruz');
$studentNumber = $currentStudent['student_id'] ?? '230110001';
$studentEmail = $currentStudent['email'] ?? 'juan.delacruz@bcp.edu.ph';

// Fetch enrolled sections and courses for this student
$enrolledStmt = $db->prepare("
    SELECT DISTINCT cr.section, cr.course_code, cr.course_title, cr.room_number,
           CONCAT(t.first_name, ' ', t.last_name) AS instructor_name
    FROM class_roster cr
    JOIN users t ON t.user_id = cr.teacher_id
    WHERE cr.student_id = ?
    ORDER BY cr.section ASC
");
$enrolledStmt->execute([$studentUserId]);
$enrolledClasses = $enrolledStmt->fetchAll(PDO::FETCH_ASSOC);
$studentSections = !empty($enrolledClasses) ? array_column($enrolledClasses, 'section') : ['31001'];

// Fetch all available student accounts for quick switching in demo/review mode
$allStudents = $db->query("
    SELECT u.user_id, u.student_id, u.first_name, u.last_name,
           (SELECT GROUP_CONCAT(DISTINCT section SEPARATOR ', ') FROM class_roster WHERE student_id = u.user_id) as sections
    FROM users u
    WHERE u.role = 'student'
    ORDER BY u.user_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch any currently active QR Session
$activeSession = null;
try {
    $qsStmt = $db->prepare("
        SELECT qs.qr_session_id, qs.teacher_id, qs.section, qs.qr_code, qs.start, qs.end,
               TIMESTAMPDIFF(SECOND, NOW(), qs.end) AS remaining_seconds,
               CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
               cr.course_code, cr.course_title, cr.room_number, cr.scheduled_time
        FROM qr_sessions qs
        JOIN users t ON t.user_id = qs.teacher_id
        LEFT JOIN class_roster cr ON cr.teacher_id = qs.teacher_id AND cr.section = qs.section
        WHERE qs.is_active = 1 AND qs.end > NOW()
        ORDER BY qs.qr_session_id DESC
        LIMIT 1
    ");
    $qsStmt->execute();
    $activeSession = $qsStmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$isSectionMatch = $activeSession && in_array($activeSession['section'], $studentSections, true);
$remainingSec = $activeSession ? max(0, (int)$activeSession['remaining_seconds']) : 0;
?>

<style>
  #camera-stream-box {
    max-width: 240px;
    max-height: 240px;
  }
  #camera-stream-box video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 0.75rem;
  }
  #camera-stream-box #qr-shaded-region {
    border-width: 20px !important;
  }
</style>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body w-full space-y-5">

      <!-- Student Profile Header Card -->
      <div class="bg-white rounded-xl p-4 sm:p-5 border border-slate-200 shadow-xs flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-[#1e3b8a] text-white font-bold text-base flex items-center justify-center shrink-0">
            <?= strtoupper(substr($currentStudent['first_name'] ?? 'J', 0, 1) . substr($currentStudent['last_name'] ?? 'D', 0, 1)) ?>
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base font-bold text-slate-900 leading-tight"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></h1>
              <span class="text-[11px] text-slate-400 font-mono">ID: <?= htmlspecialchars((string)$studentNumber, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <p class="text-xs text-slate-500 mt-0.5">
              Enrolled: <strong class="text-slate-700 font-semibold"><?= !empty($studentSections) ? 'Section ' . implode(', ', array_map('htmlspecialchars', $studentSections)) : 'Unassigned' ?></strong>
            </p>
          </div>
        </div>

        <!-- Student Switcher for Testing -->
        <div class="flex items-center gap-2 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200 w-full sm:w-auto">
          <label for="test-student-select" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Testing As:</label>
          <select id="test-student-select" onchange="window.location.href='?student_id=' + this.value" class="text-xs font-semibold text-slate-700 bg-transparent border-0 focus:ring-0 focus:outline-none cursor-pointer">
            <?php foreach ($allStudents as $st): ?>
              <option value="<?= (int)$st['user_id'] ?>" <?= ((int)$st['user_id'] === $studentUserId) ? 'selected' : '' ?>>
                <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($st['student_id'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Live Active Session Detection Banner -->
      <?php if ($activeSession): ?>
        <div class="rounded-xl p-4 border <?= $isSectionMatch ? 'bg-emerald-50/90 border-emerald-300' : 'bg-amber-50/90 border-amber-300' ?> flex flex-col md:flex-row items-start md:items-center justify-between gap-3 shadow-xs">
          <div class="space-y-0.5">
            <div class="flex items-center gap-2">
              <span class="relative flex h-2.5 w-2.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?= $isSectionMatch ? 'bg-emerald-400 opacity-75' : 'bg-amber-400 opacity-75' ?>"></span>
                <span class="relative inline-flex rounded-full h-2.5 w-2.5 <?= $isSectionMatch ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
              </span>
              <span class="text-xs font-bold uppercase tracking-wider <?= $isSectionMatch ? 'text-emerald-800' : 'text-amber-800' ?>">
                <?= $isSectionMatch ? 'Live Attendance Session' : 'Active Session (Section Mismatch)' ?>
              </span>
              <span class="px-2 py-0.2 rounded text-[10px] font-bold bg-white border border-slate-200 text-slate-700">
                Sec <?= htmlspecialchars($activeSession['section'], ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
            <p class="text-xs text-slate-700 font-medium">
              <strong><?= htmlspecialchars($activeSession['course_title'] ?? 'Web Systems', ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars($activeSession['course_code'] ?? 'IT301', ENT_QUOTES, 'UTF-8') ?>)
              • Instructor: <?= htmlspecialchars($activeSession['teacher_name'], ENT_QUOTES, 'UTF-8') ?> • Room <?= htmlspecialchars($activeSession['room_number'] ?? '402', ENT_QUOTES, 'UTF-8') ?>
            </p>
          </div>

          <div class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-end">
            <div class="text-left md:text-right">
              <span class="text-[10px] text-slate-500 uppercase tracking-wider block leading-none">Closes In</span>
              <span id="session-countdown-timer" class="font-mono font-bold text-sm text-slate-800" data-seconds="<?= $remainingSec ?>">
                <?= sprintf('%02d:%02d', floor($remainingSec / 60), $remainingSec % 60) ?>
              </span>
            </div>
            <button type="button" onclick="autofillToken('<?= htmlspecialchars($activeSession['qr_code'], ENT_QUOTES, 'UTF-8') ?>')" class="btn btn-sm px-3 py-1.5 rounded-lg text-xs font-bold text-white <?= $isSectionMatch ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-amber-600 hover:bg-amber-700' ?> transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap">
              <span>Autofill (<?= htmlspecialchars($activeSession['qr_code'], ENT_QUOTES, 'UTF-8') ?>)</span>
            </button>
          </div>
        </div>
      <?php else: ?>
        <div class="rounded-xl p-3 bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <span class="text-slate-400">ℹ️</span>
            <span><strong>No Active Live Session:</strong> You can scan a code or enter tokens below to verify attendance.</span>
          </div>
          <a href="<?php echo url('teacher/live-session'); ?>" class="text-[#1e3b8a] hover:underline font-semibold whitespace-nowrap text-xs">
            Teacher Live Session →
          </a>
        </div>
      <?php endif; ?>

      <!-- Minimalist Dual Column Layout -->
        <div class="flex flex-col md:flex-row gap-5 items-stretch">
          
          <!-- Left: Compact Camera Viewfinder (50% width) with Light Gray Neumorphic Theme -->
          <div class="scanner-camera-card flex-1 min-w-0 rounded-xl p-5 shadow-xs flex flex-col items-center justify-between" style="background-color: #f1f5f9 !important; color: #0f172a !important; border: 1px solid #e2e8f0 !important;">
            <div class="w-full flex items-center justify-between mb-3.5 pb-2 border-b border-slate-200/80">
              <div class="flex items-center gap-2">
                <span id="camera-status-dot" class="w-2.5 h-2.5 rounded-full bg-slate-400"></span>
                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider">Live Camera Scanner</span>
              </div>
              <div class="flex items-center gap-2">
                <button type="button" id="switch-camera-btn" class="hidden text-xs text-[#0284c7] hover:underline font-semibold flex items-center gap-1 cursor-pointer" onclick="toggleCamera()">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  <span>Switch</span>
                </button>
                <button type="button" id="header-camera-toggle-btn" class="neuro-btn text-xs px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 cursor-pointer text-[#1e3b8a] shadow-xs" onclick="toggleCameraPower()">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                  <span id="header-camera-toggle-text">Open Camera</span>
                </button>
              </div>
            </div>

            <!-- Full-Width Viewfinder Box (White Neumorphic Surface) -->
            <div id="camera-stream-box" class="relative w-full h-72 sm:h-80 rounded-2xl flex flex-col items-center justify-center overflow-hidden my-auto">
              <!-- Camera Off / Placeholder UI (White Neumorphic Style) -->
              <div id="camera-placeholder" class="relative z-20 flex flex-col items-center justify-center p-6 text-center w-full">
                <!-- Neumorphic Sunken/Raised Circular Icon -->
                <div class="neuro-icon-circle w-16 h-16 rounded-full flex items-center justify-center mb-3.5">
                  <svg class="w-8 h-8 text-[#1e3b8a]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                
                <p class="text-sm font-bold text-slate-700 mb-4 tracking-tight">Camera is Closed</p>
                
                <!-- Neumorphic Raised Action Button -->
                <button type="button" onclick="startCamera()" class="neuro-btn px-6 py-2.5 rounded-xl text-xs font-bold flex items-center gap-2.5 cursor-pointer">
                  <svg class="w-4 h-4 text-[#0284c7]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>Open Camera</span>
                </button>
              </div>

              <!-- Reticle Frame & Laser (shown when active) -->
              <div id="camera-overlay-frame" class="hidden absolute inset-0 pointer-events-none flex flex-col items-center justify-center z-10">
                <!-- Scan Laser Line -->
                <div class="absolute left-6 right-6 h-0.5 bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,1)] animate-bounce pointer-events-none z-20"></div>

                <div class="w-44 h-44 sm:w-48 sm:h-48 relative flex flex-col justify-between pointer-events-none">
                  <div class="flex justify-between">
                    <div class="w-6 h-6 border-t-2 border-l-2 border-emerald-400 rounded-tl"></div>
                    <div class="w-6 h-6 border-t-2 border-r-2 border-emerald-400 rounded-tr"></div>
                  </div>
                  <div class="text-center">
                    <span class="px-2.5 py-1 rounded bg-black/75 text-[11px] font-semibold text-white">Align QR Code</span>
                  </div>
                  <div class="flex justify-between">
                    <div class="w-6 h-6 border-b-2 border-l-2 border-emerald-400 rounded-bl"></div>
                    <div class="w-6 h-6 border-b-2 border-r-2 border-emerald-400 rounded-br"></div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Viewfinder Footer Info -->
            <div class="w-full flex items-center justify-between mt-3 pt-2.5 text-[11px] text-slate-500 border-t border-slate-200/80">
              <span>Status: <strong id="camera-status-text" class="text-slate-700 font-semibold">Inactive</strong></span>
              <span id="camera-hint-text" class="text-slate-400">Click Open Camera to scan</span>
            </div>
          </div>

          <!-- Right: Manual Entry & Test Scenarios (50% width) -->
          <div class="flex-1 min-w-0 flex flex-col gap-4">
            
            <!-- Manual Token Entry Card -->
            <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-xs flex-1 flex flex-col justify-center">
              <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider mb-1">Manual 6-Digit Token</h3>
              <p class="text-xs text-slate-500 mb-3">If camera is unavailable, enter the code from your instructor's screen:</p>

              <form id="token-attendance-form" onsubmit="submitTokenAttendance(event)" class="space-y-3">
                <div>
                  <input type="text" id="manual-token" name="token" maxlength="8" placeholder="e.g. 748291" required class="w-full px-3 py-2 text-center font-mono font-bold text-base uppercase tracking-widest rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-[#1e3b8a] bg-slate-50 focus:bg-white transition">
                </div>

                <div id="token-error-msg" class="hidden text-xs text-rose-600 font-medium bg-rose-50 border border-rose-200 p-2 rounded-lg text-center"></div>

                <button type="submit" id="submit-token-btn" class="w-full py-2 px-4 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white font-semibold text-xs shadow-xs transition flex items-center justify-center gap-2 cursor-pointer">
                  <span>Submit Attendance Token</span>
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
              </form>
            </div>

            <!-- Quick Outcome Simulation -->
            <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-xs">
              <div class="flex items-center gap-1.5 mb-2.5">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Test QR Scan Outcomes</span>
              </div>

              <div class="grid grid-cols-2 gap-2">
                <a href="<?php echo url('student/scan-result?status=success'); ?>" class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/40 transition text-xs group">
                  <span class="w-2 h-2 rounded-full bg-emerald-500 shrink-0"></span>
                  <span class="font-medium text-slate-700 truncate">1. Success</span>
                </a>

                <a href="<?php echo url('student/scan-result?status=duplicate'); ?>" class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 hover:border-blue-500 hover:bg-blue-50/40 transition text-xs group">
                  <span class="w-2 h-2 rounded-full bg-blue-500 shrink-0"></span>
                  <span class="font-medium text-slate-700 truncate">2. Duplicate</span>
                </a>

                <a href="<?php echo url('student/scan-result?status=wrong_section'); ?>" class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 hover:border-amber-500 hover:bg-amber-50/40 transition text-xs group">
                  <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
                  <span class="font-medium text-slate-700 truncate">3. Section Error</span>
                </a>

                <a href="<?php echo url('student/scan-result?status=expired'); ?>" class="flex items-center gap-2 p-2 rounded-lg bg-slate-50 border border-slate-200 hover:border-rose-500 hover:bg-rose-50/40 transition text-xs group">
                  <span class="w-2 h-2 rounded-full bg-rose-500 shrink-0"></span>
                  <span class="font-medium text-slate-700 truncate">4. Expired QR</span>
                </a>
              </div>
            </div>

          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<style>
  .scanner-camera-card {
    background-color: #f1f5f9 !important;
    color: #0f172a !important;
    border: 1px solid #e2e8f0 !important;
  }
  
  /* Neumorphic Stream Box (White/Soft Grey Sunken Well) */
  #camera-stream-box {
    background: #eef2f7 !important;
    border: 1px solid rgba(255, 255, 255, 0.9) !important;
    box-shadow: inset 4px 4px 10px rgba(166, 180, 200, 0.45), inset -4px -4px 10px #ffffff !important;
    width: 100% !important;
    position: relative;
  }
  
  /* Neumorphic Circular Icon */
  .neuro-icon-circle {
    background: #eef2f7 !important;
    color: #1e3b8a !important;
    box-shadow: 6px 6px 14px rgba(166, 180, 200, 0.5), -6px -6px 14px #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.95) !important;
  }
  
  /* Neumorphic Action Button */
  .neuro-btn {
    background: #eef2f7 !important;
    color: #1e3b8a !important;
    box-shadow: 4px 4px 10px rgba(166, 180, 200, 0.45), -4px -4px 10px #ffffff !important;
    border: 1px solid rgba(255, 255, 255, 0.95) !important;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1) !important;
  }
  .neuro-btn:hover {
    box-shadow: 6px 6px 14px rgba(166, 180, 200, 0.55), -6px -6px 14px #ffffff !important;
    transform: translateY(-1px);
    color: #0284c7 !important;
  }
  .neuro-btn:active {
    box-shadow: inset 3px 3px 6px rgba(166, 180, 200, 0.55), inset -3px -3px 6px #ffffff !important;
    transform: translateY(1px);
  }

  #camera-stream-box video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 1rem !important;
  }
  #camera-stream-box #qr-shaded-region {
    display: none !important;
  }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
var html5QrScanner = null;
var currentCameraFacingMode = "environment";
var isScanningActive = false;

function autofillToken(token) {
  const tokenInput = document.getElementById('manual-token');
  if (tokenInput) {
    tokenInput.value = token;
    tokenInput.focus();
  }
}

async function startCamera() {
  const placeholder = document.getElementById('camera-placeholder');
  const overlayFrame = document.getElementById('camera-overlay-frame');
  const statusDot = document.getElementById('camera-status-dot');
  const statusText = document.getElementById('camera-status-text');
  const hintText = document.getElementById('camera-hint-text');
  const headerBtn = document.getElementById('header-camera-toggle-btn');
  const headerBtnText = document.getElementById('header-camera-toggle-text');
  const switchBtn = document.getElementById('switch-camera-btn');

  if (statusText) statusText.textContent = 'Starting camera...';

  try {
    if (!html5QrScanner) {
      html5QrScanner = new Html5Qrcode("camera-stream-box");
    }

    if (placeholder) placeholder.classList.add('hidden');
    if (overlayFrame) overlayFrame.classList.remove('hidden');

    const config = { 
      fps: 15, 
      qrbox: (viewfinderWidth, viewfinderHeight) => {
        const minEdge = Math.min(viewfinderWidth, viewfinderHeight);
        const qrboxSize = Math.floor(minEdge * 0.75);
        return {
          width: Math.min(qrboxSize, 280),
          height: Math.min(qrboxSize, 280)
        };
      }
    };

    await html5QrScanner.start(
      { facingMode: currentCameraFacingMode },
      config,
      onQrCodeSuccess,
      onQrCodeError
    );

    isScanningActive = true;
    if (statusDot) {
      statusDot.className = 'w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse';
    }
    if (statusText) {
      statusText.textContent = `Active (${currentCameraFacingMode === 'environment' ? 'Back' : 'Front'})`;
    }
    if (hintText) {
      hintText.textContent = 'Point QR code into box';
    }
    if (switchBtn) {
      switchBtn.classList.remove('hidden');
    }
    if (headerBtn && headerBtnText) {
      headerBtn.className = 'neuro-btn text-xs px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 cursor-pointer text-slate-700 shadow-xs';
      headerBtnText.textContent = 'Close Camera';
    }
  } catch (err) {
    console.warn('Camera access unavailable or denied:', err);
    if (placeholder) placeholder.classList.remove('hidden');
    if (overlayFrame) overlayFrame.classList.add('hidden');
    if (statusDot) statusDot.className = 'w-2.5 h-2.5 rounded-full bg-amber-500';
    if (statusText) statusText.textContent = 'Access Denied / Not Found';
    if (hintText) hintText.textContent = 'Use manual 6-digit code';
    if (headerBtn && headerBtnText) {
      headerBtn.className = 'neuro-btn text-xs px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 cursor-pointer text-[#1e3b8a] shadow-xs';
      headerBtnText.textContent = 'Retry Camera';
    }
  }
}

async function stopCamera() {
  const placeholder = document.getElementById('camera-placeholder');
  const overlayFrame = document.getElementById('camera-overlay-frame');
  const statusDot = document.getElementById('camera-status-dot');
  const statusText = document.getElementById('camera-status-text');
  const hintText = document.getElementById('camera-hint-text');
  const headerBtn = document.getElementById('header-camera-toggle-btn');
  const headerBtnText = document.getElementById('header-camera-toggle-text');
  const switchBtn = document.getElementById('switch-camera-btn');

  if (html5QrScanner && isScanningActive) {
    try {
      await html5QrScanner.stop();
    } catch (_) {}
    isScanningActive = false;
  }

  if (placeholder) placeholder.classList.remove('hidden');
  if (overlayFrame) overlayFrame.classList.add('hidden');
  if (statusDot) statusDot.className = 'w-2.5 h-2.5 rounded-full bg-slate-400';
  if (statusText) statusText.textContent = 'Inactive';
  if (hintText) hintText.textContent = 'Click Open Camera to scan';
  if (switchBtn) switchBtn.classList.add('hidden');
  if (headerBtn && headerBtnText) {
    headerBtn.className = 'neuro-btn text-xs px-3 py-1.5 rounded-lg font-bold transition flex items-center gap-1.5 cursor-pointer text-[#1e3b8a] shadow-xs';
    headerBtnText.textContent = 'Open Camera';
  }
}

function toggleCameraPower() {
  if (isScanningActive) {
    stopCamera();
  } else {
    startCamera();
  }
}

function onQrCodeSuccess(decodedText) {
  if (!decodedText) return;
  // Stop scanning once code detected to prevent multiple submissions
  stopCamera();
  
  // Extract 6-digit code if full URL or token
  let token = decodedText.trim();
  const match = token.match(/\b\d{6}\b/);
  if (match) {
    token = match[0];
  }

  const tokenInput = document.getElementById('manual-token');
  if (tokenInput) tokenInput.value = token;
  
  processAttendanceCheckIn(token);
}

function onQrCodeError(errorMessage) {
  // Silent scan frame tick
}

async function toggleCamera() {
  if (!html5QrScanner) return;
  try {
    if (isScanningActive) {
      await html5QrScanner.stop();
      isScanningActive = false;
    }
    currentCameraFacingMode = (currentCameraFacingMode === "environment") ? "user" : "environment";
    await startCamera();
  } catch (e) {
    console.error('Failed to switch camera:', e);
  }
}

async function submitTokenAttendance(e) {
  if (e) e.preventDefault();
  const tokenInput = document.getElementById('manual-token');
  const token = tokenInput ? tokenInput.value.trim() : '';
  if (!token) return;
  processAttendanceCheckIn(token);
}

async function processAttendanceCheckIn(token) {
  const errBox = document.getElementById('token-error-msg');
  const btn = document.getElementById('submit-token-btn');

  if (errBox) errBox.classList.add('hidden');
  if (btn && window.APP && typeof APP.setLoading === 'function') {
    APP.setLoading(btn, true, 'Verifying...');
  } else if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span>Verifying Token...</span>';
  }

  try {
    const endpoint = (typeof window.url === 'function') 
      ? window.url('api/attendance/check-in') 
      : '/api/attendance/check-in';

    const resp = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({
        qr_code: token,
        student_id: <?= json_encode($studentUserId) ?>
      })
    });

    const data = await resp.json();

    if (resp.ok && data.status === 'success') {
      try {
        sessionStorage.setItem('last_attendance_record', JSON.stringify(data.record || {}));
        
        // Instant Real-Time Cross-Window / Live Feed Signal
        const eventPayload = {
          type: 'SCAN_RECORDED',
          section: data.record?.section || '',
          attendance_id: data.attendance_id || data.record?.attendance_id,
          student_name: data.record?.student_name,
          status: data.record?.status,
          timestamp: Date.now()
        };
        localStorage.setItem('ams_live_scan_event', JSON.stringify(eventPayload));
        
        if (typeof window.BroadcastChannel === 'function') {
          const channel = new BroadcastChannel('ams_attendance_channel');
          channel.postMessage(eventPayload);
          channel.close();
        }
      } catch (e) {}
      const targetUrl = (typeof window.url === 'function')
        ? window.url('student/scan-result?status=success')
        : '/student/scan-result?status=success';
      window.location.href = targetUrl;
    } else {
      let statusParam = 'expired';
      if (data.scan_code === 'DUPLICATE' || resp.status === 409) {
        statusParam = 'duplicate';
      } else if (data.scan_code === 'WRONG_SECTION' || resp.status === 403) {
        statusParam = 'wrong_section';
      }
      try {
        sessionStorage.setItem('last_attendance_error', data.message || 'Check-in failed');
      } catch (e) {}
      const targetUrl = (typeof window.url === 'function')
        ? window.url(`student/scan-result?status=${statusParam}&msg=${encodeURIComponent(data.message || '')}`)
        : `/student/scan-result?status=${statusParam}&msg=${encodeURIComponent(data.message || '')}`;
      window.location.href = targetUrl;
    }
  } catch (err) {
    if (errBox) {
      errBox.textContent = 'Network error while contacting attendance service. Please check connection.';
      errBox.classList.remove('hidden');
    }
    if (btn && window.APP && typeof APP.setLoading === 'function') {
      APP.setLoading(btn, false);
    } else if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span>Submit Attendance Token</span>';
    }
  }
}

// Session countdown timer ticker
document.addEventListener('DOMContentLoaded', () => {
  const timerEl = document.getElementById('session-countdown-timer');
  if (timerEl) {
    let sec = parseInt(timerEl.getAttribute('data-seconds'), 10) || 0;
    if (sec > 0) {
      const interval = setInterval(() => {
        sec--;
        if (sec <= 0) {
          clearInterval(interval);
          timerEl.textContent = '00:00 (Expired)';
          timerEl.classList.add('text-rose-600');
        } else {
          const m = Math.floor(sec / 60);
          const s = sec % 60;
          timerEl.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
        }
      }, 1000);
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
