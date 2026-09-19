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

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body max-w-4xl mx-auto space-y-6">

      <!-- Header & Student Context Card -->
      <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-500 to-blue-600 text-white font-bold text-xl flex items-center justify-center shadow-md shadow-indigo-100 flex-shrink-0">
            <?= strtoupper(substr($currentStudent['first_name'] ?? 'J', 0, 1) . substr($currentStudent['last_name'] ?? 'D', 0, 1)) ?>
          </div>
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="px-2.5 py-0.5 rounded-full bg-indigo-50 border border-indigo-200 text-indigo-700 text-xs font-semibold">Student Portal Check-In</span>
              <span class="text-xs text-slate-400 font-mono">ID: <?= htmlspecialchars((string)$studentNumber, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <h1 class="text-xl font-bold text-slate-800"><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="text-xs text-slate-500 mt-0.5">
              Enrolled: <span class="font-semibold text-slate-700"><?= !empty($studentSections) ? 'Section ' . implode(', ', array_map('htmlspecialchars', $studentSections)) : 'No assigned section' ?></span>
            </p>
          </div>
        </div>

        <!-- Quick Student Switcher for Test & Grading Review -->
        <div class="w-full md:w-auto flex flex-col sm:flex-row items-start sm:items-center gap-2 bg-slate-50 p-2.5 rounded-xl border border-slate-200">
          <label for="test-student-select" class="text-[11px] font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Testing As:</label>
          <select id="test-student-select" onchange="window.location.href='?student_id=' + this.value" class="text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 focus:ring-2 focus:ring-indigo-500 focus:outline-none cursor-pointer w-full sm:w-auto">
            <?php foreach ($allStudents as $st): ?>
              <option value="<?= (int)$st['user_id'] ?>" <?= ((int)$st['user_id'] === $studentUserId) ? 'selected' : '' ?>>
                <?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($st['student_id'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?> - Sec <?= htmlspecialchars($st['sections'] ?: 'None', ENT_QUOTES, 'UTF-8') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Live Active Session Detection Banner -->
      <?php if ($activeSession): ?>
        <div class="rounded-2xl p-5 border <?= $isSectionMatch ? 'bg-gradient-to-r from-emerald-500/10 via-teal-500/10 to-indigo-500/10 border-emerald-300 shadow-sm' : 'bg-gradient-to-r from-amber-500/10 via-orange-500/10 to-rose-500/10 border-amber-300' ?> flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div class="space-y-1">
            <div class="flex items-center gap-2">
              <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?= $isSectionMatch ? 'bg-emerald-400 opacity-75' : 'bg-amber-400 opacity-75' ?>"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 <?= $isSectionMatch ? 'bg-emerald-500' : 'bg-amber-500' ?>"></span>
              </span>
              <span class="text-xs font-bold uppercase tracking-wider <?= $isSectionMatch ? 'text-emerald-800' : 'text-amber-800' ?>">
                <?= $isSectionMatch ? 'Live Attendance Session in Progress' : 'Active Session (Section Mismatch Warning)' ?>
              </span>
              <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-white border border-slate-200 text-slate-700 shadow-xs">
                Section <?= htmlspecialchars($activeSession['section'], ENT_QUOTES, 'UTF-8') ?>
              </span>
            </div>
            <p class="text-sm font-semibold text-slate-800">
              <?= htmlspecialchars($activeSession['course_title'] ?? 'Web Systems and Technologies', ENT_QUOTES, 'UTF-8') ?>
              <span class="text-xs font-normal text-slate-500">(<?= htmlspecialchars($activeSession['course_code'] ?? 'IT301', ENT_QUOTES, 'UTF-8') ?>)</span>
            </p>
            <p class="text-xs text-slate-600">
              Instructor: <strong class="text-slate-800"><?= htmlspecialchars($activeSession['teacher_name'], ENT_QUOTES, 'UTF-8') ?></strong> • Room <?= htmlspecialchars($activeSession['room_number'] ?? '402', ENT_QUOTES, 'UTF-8') ?>
              <?php if (!$isSectionMatch): ?>
                • <span class="text-amber-700 font-medium">Your assigned section is <?= htmlspecialchars(implode(', ', $studentSections), ENT_QUOTES, 'UTF-8') ?>.</span>
              <?php endif; ?>
            </p>
          </div>

          <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 w-full sm:w-auto">
            <div class="text-left sm:text-right">
              <span class="text-[10px] text-slate-500 uppercase tracking-wider font-semibold block">Session Closes In</span>
              <span id="session-countdown-timer" class="font-mono font-bold text-base text-slate-800" data-seconds="<?= $remainingSec ?>">
                <?= sprintf('%02d:%02d', floor($remainingSec / 60), $remainingSec % 60) ?>
              </span>
            </div>
            <button type="button" onclick="autofillToken('<?= htmlspecialchars($activeSession['qr_code'], ENT_QUOTES, 'UTF-8') ?>')" class="btn btn-sm px-3.5 py-2 rounded-xl text-xs font-bold text-white <?= $isSectionMatch ? 'bg-emerald-600 hover:bg-emerald-700 shadow-sm' : 'bg-amber-600 hover:bg-amber-700 shadow-sm' ?> transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap">
              <span>Autofill Code (<?= htmlspecialchars($activeSession['qr_code'], ENT_QUOTES, 'UTF-8') ?>)</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
          </div>
        </div>
      <?php else: ?>
        <div class="rounded-2xl p-4 bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between gap-4">
          <div class="flex items-center gap-2.5">
            <span class="text-slate-400">ℹ️</span>
            <span><strong>No Active Live Session:</strong> Instructors generate live attendance QR sessions from their Teacher Portal. You can still scan or submit tokens below to test validation states.</span>
          </div>
          <a href="<?php echo url('teacher/live-session'); ?>" class="text-indigo-600 hover:text-indigo-700 font-semibold whitespace-nowrap text-xs flex items-center gap-1">
            <span>Teacher Live Session</span> →
          </a>
        </div>
      <?php endif; ?>

      <!-- Main Scanning & Manual Entry Grid -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
        <!-- Main Scanner Viewfinder (7 cols) -->
        <div class="md:col-span-7 space-y-4">
          <div class="bg-slate-900 rounded-2xl p-6 shadow-xl border border-slate-800 text-white relative overflow-hidden">
            <!-- Camera Viewfinder with Live Stream -->
            <div id="camera-stream-box" class="relative w-full aspect-square max-w-[340px] mx-auto rounded-xl bg-slate-950 border-2 border-slate-700 flex flex-col items-center justify-center overflow-hidden">
              <!-- Grid background simulation -->
              <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:24px_24px] opacity-40"></div>

              <!-- Animated Scan Laser line -->
              <div class="absolute left-6 right-6 h-0.5 bg-gradient-to-r from-indigo-500 via-emerald-400 to-indigo-500 shadow-[0_0_12px_rgba(52,211,153,0.8)] animate-bounce pointer-events-none z-20"></div>

              <!-- Reticle Corners -->
              <div class="w-48 h-48 relative z-10 flex flex-col justify-between pointer-events-none">
                <div class="flex justify-between">
                  <div class="w-6 h-6 border-t-4 border-l-4 border-emerald-400 rounded-tl-lg"></div>
                  <div class="w-6 h-6 border-t-4 border-r-4 border-emerald-400 rounded-tr-lg"></div>
                </div>
                <div class="text-center">
                  <span class="px-2.5 py-1 rounded bg-black/60 backdrop-blur text-[11px] font-medium text-slate-300">Align QR within frame</span>
                </div>
                <div class="flex justify-between">
                  <div class="w-6 h-6 border-b-4 border-l-4 border-emerald-400 rounded-bl-lg"></div>
                  <div class="w-6 h-6 border-b-4 border-r-4 border-emerald-400 rounded-br-lg"></div>
                </div>
              </div>
            </div>

            <!-- Viewfinder Controls -->
            <div class="flex items-center justify-between mt-5 pt-4 border-t border-slate-800 text-xs text-slate-400">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Camera: <strong id="camera-status-text" class="text-white">Active (Back)</strong></span>
              </div>
              <button type="button" class="text-indigo-400 hover:text-indigo-300 transition cursor-pointer font-medium" onclick="toggleCamera()">Switch Camera</button>
            </div>
          </div>
        </div>

        <!-- Right Side: Manual Token Fallback & Simulation Panel (5 cols) -->
        <div class="md:col-span-5 space-y-4">
          <!-- Manual Token Entry Card -->
          <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <h3 class="font-bold text-slate-800 text-sm mb-1">Backup: Enter Token Manually</h3>
            <p class="text-xs text-slate-500 mb-4">If your camera cannot scan the screen, enter the 6-character session token displayed below the teacher's QR code.</p>

            <form id="token-attendance-form" onsubmit="submitTokenAttendance(event)" class="space-y-3">
              <div>
                <label for="manual-token" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">6-Digit Session Token</label>
                <input type="text" id="manual-token" name="token" maxlength="8" placeholder="e.g. 748291" required class="w-full px-3.5 py-2.5 text-center font-mono font-bold text-lg uppercase tracking-widest rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50 focus:bg-white">
              </div>

              <div id="token-error-msg" class="hidden text-xs text-rose-600 font-medium bg-rose-50 border border-rose-200 p-2.5 rounded-lg text-center"></div>

              <button type="submit" id="submit-token-btn" class="btn btn-primary w-full py-2.5 px-4 rounded-lg font-semibold text-xs shadow transition flex items-center justify-center gap-2 cursor-pointer">
                <span>Submit Attendance Token</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </button>
            </form>
          </div>

          <!-- Interactive Test Scanner Simulator -->
          <div class="bg-slate-50 rounded-xl p-5 border border-slate-200">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-sm">🧪</span>
              <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Test QR Scan Outcomes</h3>
            </div>
            <p class="text-xs text-slate-500 mb-3">Simulate scanning various tokens to test the 4 distinct feedback states defined in the specification:</p>

            <div class="space-y-2">
              <a href="<?php echo url('student/scan-result?status=success'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span class="font-semibold text-slate-700">Test Success Check-in</span>
                </div>
                <span class="text-[11px] text-emerald-600 font-semibold group-hover:translate-x-0.5 transition">Preview →</span>
              </a>

              <a href="<?php echo url('student/scan-result?status=duplicate'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-blue-500 hover:bg-blue-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                  <span class="font-semibold text-slate-700">Test Duplicate Check-in</span>
                </div>
                <span class="text-[11px] text-blue-600 font-semibold group-hover:translate-x-0.5 transition">Preview →</span>
              </a>

              <a href="<?php echo url('student/scan-result?status=wrong_section'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-amber-500 hover:bg-amber-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                  <span class="font-semibold text-slate-700">Test Wrong Section Reject</span>
                </div>
                <span class="text-[11px] text-amber-600 font-semibold group-hover:translate-x-0.5 transition">Preview →</span>
              </a>

              <a href="<?php echo url('student/scan-result?status=expired'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-rose-500 hover:bg-rose-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                  <span class="font-semibold text-slate-700">Test Expired QR Token</span>
                </div>
                <span class="text-[11px] text-rose-600 font-semibold group-hover:translate-x-0.5 transition">Preview →</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js"></script>
<script>
let html5QrScanner = null;
let currentCameraFacingMode = "environment";
let isScanningActive = false;

function autofillToken(token) {
  const tokenInput = document.getElementById('manual-token');
  if (tokenInput) {
    tokenInput.value = token;
    tokenInput.focus();
  }
}

async function initLiveScanner() {
  const readerElement = document.getElementById('camera-stream-box');
  if (!readerElement || typeof Html5Qrcode === 'undefined') return;

  try {
    html5QrScanner = new Html5Qrcode("camera-stream-box");
    const config = { 
      fps: 10, 
      qrbox: { width: 220, height: 220 },
      aspectRatio: 1.0
    };

    await html5QrScanner.start(
      { facingMode: currentCameraFacingMode },
      config,
      onQrCodeSuccess,
      onQrCodeError
    );
    isScanningActive = true;
    const statusText = document.getElementById('camera-status-text');
    if (statusText) statusText.textContent = `Active (${currentCameraFacingMode === 'environment' ? 'Back' : 'Front'})`;
  } catch (err) {
    console.warn('Camera access unavailable or denied:', err);
    const statusText = document.getElementById('camera-status-text');
    if (statusText) statusText.textContent = 'Manual Entry Ready';
  }
}

function onQrCodeSuccess(decodedText) {
  if (!decodedText) return;
  // Stop scanning once code detected to prevent multiple submissions
  if (html5QrScanner && isScanningActive) {
    html5QrScanner.stop().catch(() => {});
    isScanningActive = false;
  }
  
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
    await initLiveScanner();
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
  initLiveScanner();

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
