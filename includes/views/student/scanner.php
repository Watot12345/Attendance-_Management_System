<?php
$page_title = 'Scan Attendance QR';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';

// Resolve current student session or default
$studentName = $_SESSION['user']['full_name'] ?? 'Juan Dela Cruz';
$studentNumber = $_SESSION['user']['student_id'] ?? '230110001';
$studentUserId = (int)($_SESSION['user']['user_id'] ?? $_SESSION['student_id'] ?? 1);
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body max-w-4xl mx-auto">
      <!-- Breadcrumb & Header -->
      <div class="mb-6 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold mb-2">
          <span>Student Portal</span> • <span><?= htmlspecialchars($studentName, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$studentNumber, ENT_QUOTES, 'UTF-8') ?>)</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Scan Session Attendance QR</h1>
        <p class="text-sm text-slate-500 max-w-md mx-auto">Point your device camera at the rotating QR code projected on the classroom screen.</p>
      </div>

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
              <button type="button" class="text-indigo-400 hover:text-indigo-300 transition cursor-pointer" onclick="toggleCamera()">Switch Camera</button>
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
                <input type="text" id="manual-token" name="token" maxlength="8" placeholder="e.g. 7X9K2M" required class="w-full px-3.5 py-2.5 text-center font-mono font-bold text-lg uppercase tracking-widest rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50 focus:bg-white">
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

              <a href="<?php echo url('student/scan-result?status=duplicate'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-amber-500 hover:bg-amber-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                  <span class="font-semibold text-slate-700">Test Duplicate Check-in</span>
                </div>
                <span class="text-[11px] text-amber-600 font-semibold group-hover:translate-x-0.5 transition">Preview →</span>
              </a>

              <a href="<?php echo url('student/scan-result?status=wrong_section'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-rose-500 hover:bg-rose-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                  <span class="font-semibold text-slate-700">Test Wrong Section Reject</span>
                </div>
                <span class="text-[11px] text-rose-600 font-semibold group-hover:translate-x-0.5 transition">Preview →</span>
              </a>

              <a href="<?php echo url('student/scan-result?status=expired'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-rose-500 hover:bg-rose-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                  <span class="font-semibold text-slate-700">Test Expired QR Token</span>
                </div>
                <span class="text-[11px] text-slate-500 font-semibold group-hover:translate-x-0.5 transition">Preview →</span>
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
      } else if (data.scan_code === 'WRONG_SECTION') {
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

document.addEventListener('DOMContentLoaded', () => {
  initLiveScanner();
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
