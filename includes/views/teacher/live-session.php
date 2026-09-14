<?php
$page_title = 'Live Attendance Session';

require_once dirname(__DIR__, 2) . '/core/Database.php';

// Fetch active teacher class info
$db = Database::getConnection();
$teacherId = 2;
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['teacher_id'])) {
    $teacherId = (int)$_SESSION['teacher_id'];
} elseif (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
    $teacherId = (int)$_SESSION['user_id'];
}

$targetSection = trim($_GET['section'] ?? '');
if (!empty($targetSection)) {
    $rStmt = $db->prepare("
        SELECT course_code, course_title, section, room_number, scheduled_time
        FROM class_roster
        WHERE (teacher_id = ? OR 1=1) AND section = ?
        LIMIT 1
    ");
    $rStmt->execute([$teacherId, $targetSection]);
    $rosterInfo = $rStmt->fetch(PDO::FETCH_ASSOC);
}

if (empty($rosterInfo)) {
    $rStmt = $db->prepare("
        SELECT course_code, course_title, section, room_number, scheduled_time
        FROM class_roster
        WHERE teacher_id = ? AND section IS NOT NULL AND section != ''
        ORDER BY section ASC
        LIMIT 1
    ");
    $rStmt->execute([$teacherId]);
    $rosterInfo = $rStmt->fetch(PDO::FETCH_ASSOC) ?: [
        'course_code'    => 'IT301',
        'course_title'   => 'Web Systems and Technologies',
        'section'        => '31001',
        'room_number'    => '402',
        'scheduled_time' => '08:00:00'
    ];
}

$startTimeFormatted = date('h:i A', strtotime($rosterInfo['scheduled_time']));
?>
<?php include dirname(__DIR__) . '/partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include dirname(__DIR__) . '/partials/navbar.php'; ?>

      <!-- Content Area -->
      <main class="flex-1 p-6 bg-surface">
        <!-- Live Header with Status Indicator -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <div>
            <div class="flex items-center gap-2 mb-1.5">
              <span id="header-status-indicator" class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
              </span>
              <span id="header-status-badge" class="px-2 py-0.5 text-xs font-bold uppercase rounded bg-emerald-100 text-emerald-800 tracking-wider">
                Live Attendance Session Active
              </span>
              <span class="text-xs font-mono text-text-muted" id="header-session-id">ID: 6-Digit Dynamic QR</span>
            </div>
            <h1 class="text-2xl font-bold text-text-primary" id="header-course-title">
              <?= htmlspecialchars($rosterInfo['section']) ?> · <?= htmlspecialchars($rosterInfo['course_code']) ?> (<?= htmlspecialchars($rosterInfo['course_title']) ?>)
            </h1>
            <p class="text-sm text-text-secondary" id="header-course-sub">
              Started at <?= $startTimeFormatted ?> · Late threshold: 15 mins · Room <?= htmlspecialchars($rosterInfo['room_number']) ?>
            </p>
          </div>

          <!-- Controls -->
          <div class="flex items-center gap-3">
            <button type="button" class="btn btn-primary text-xs font-bold flex items-center gap-2 shadow-xs" onclick="manualGenerateQR()" title="Immediately generate a new 6-digit QR session in database">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span>Generate 6-Digit QR Code</span>
            </button>
            <button type="button" class="btn btn-secondary" onclick="toggleFullscreen()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
              <span>Fullscreen</span>
            </button>
            <button type="button" class="btn btn-danger font-semibold shadow-sm flex items-center gap-2" onclick="openCloseSessionModal()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span>Close Session &amp; Mark Absences</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
          <!-- ════ LEFT COLUMN: DYNAMIC QR CODE CARD ════ -->
          <div class="lg:col-span-5 bg-white rounded-2xl shadow-card border border-slate-100 p-6 flex flex-col items-center justify-between text-center relative overflow-hidden">
            <div class="w-full">
              <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Dynamic Anti-Screenshot QR</span>
                <span class="text-xs font-mono bg-teal-50 border border-teal-200 text-teal-800 font-bold px-2.5 py-0.5 rounded-full" id="token-display">---</span>
              </div>

              <!-- Animated Rotating Countdown Bar -->
              <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mb-5">
                <div id="qr-timer-bar" class="bg-gradient-to-r from-teal-500 to-emerald-500 h-2 transition-all duration-1000 ease-linear" style="width: 100%;"></div>
              </div>

              <!-- Active Dynamic QR Box Container -->
              <div id="qr-active-box" class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col items-center justify-center mx-auto max-w-[320px] transition-all">
                <div id="qrcode-container" class="w-64 h-64 flex items-center justify-center bg-white p-2 rounded-xl shadow-xs border border-slate-100">
                  <!-- QR Code Rendered dynamically via qrcode-generator.js -->
                </div>
                <div class="flex items-center gap-2 mt-3 text-xs font-semibold text-slate-700">
                  <svg class="w-4 h-4 text-teal-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  <span>Rotates in <span id="countdown-text" class="text-teal-700 font-extrabold text-sm">30m 00s</span></span>
                </div>
                <div class="text-[11px] text-slate-400 mt-1">
                  6-Digit Session Token: <strong id="token-sub-display" class="font-mono text-slate-700 tracking-widest text-xs">------</strong>
                </div>
              </div>

              <!-- Empty State Box (When timer expires / session ends) -->
              <div id="qr-empty-box" class="hidden p-8 bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl flex-col items-center justify-center mx-auto max-w-[320px] text-center animate-fade-in">
                <div class="w-16 h-16 rounded-2xl bg-slate-200/80 text-slate-400 flex items-center justify-center mx-auto mb-3 shadow-inner">
                  <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-800">QR Session Expired</h4>
                <p class="text-xs text-slate-500 mt-1 mb-4 leading-relaxed">
                  The 30-minute attendance window has ended. Generate a new 6-digit dynamic QR code to resume scanning.
                </p>
                <button type="button" class="btn btn-primary text-xs font-bold py-2.5 px-5 shadow-sm rounded-xl flex items-center gap-2 mx-auto" onclick="manualGenerateQR()">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  <span>Generate New 6-Digit QR</span>
                </button>
              </div>

              <!-- Generate QR Button in Card -->
              <div id="qr-refresh-btn-wrap" class="mt-4 flex items-center justify-center">
                <button type="button" class="btn btn-primary text-xs font-bold flex items-center gap-2 py-2 px-4 shadow-sm rounded-xl" onclick="manualGenerateQR()" title="Generate new 6-digit QR code in database">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  <span>Generate 6-Digit QR</span>
                </button>
              </div>

              <p id="qr-footer-note" class="text-xs text-text-muted mt-3">
                Students scan with an authenticated device enrolled in <strong><?= htmlspecialchars($rosterInfo['section']) ?></strong>. Screenshots expire after 30 minutes.
              </p>
            </div>

            <!-- Simulation Controls (Real Database Check-Ins) -->
            <div class="w-full mt-6 pt-4 border-t border-slate-100 bg-slate-50 -mx-6 -mb-6 p-4 rounded-b-2xl text-left">
              <div class="flex items-center justify-between mb-2">
                <p class="text-[11px] font-bold uppercase text-text-muted tracking-wider">Simulate Live Student Scans (Testing):</p>
                <span class="text-[10px] text-teal-700 bg-teal-50 px-2 py-0.5 rounded border border-teal-200 font-semibold">DB Connected</span>
              </div>
              <div class="flex flex-wrap items-center justify-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm text-xs cursor-pointer" onclick="simulateScan('1', 'Pedro Reyes', 'present')">+ Juan (Present)</button>
                <button type="button" class="btn btn-secondary btn-sm text-xs cursor-pointer" onclick="simulateScan('4', 'Maria Santos', 'present')">+ Maria (Present)</button>
                <button type="button" class="btn btn-secondary btn-sm text-xs cursor-pointer" onclick="simulateScan('6', 'Pedro Reyes', 'tardy')">+ Pedro (Tardy)</button>
                <button type="button" class="btn btn-secondary btn-sm text-xs cursor-pointer" onclick="simulateScan('1', 'Juan Dela Cruz', 'duplicate')">+ Duplicate Scan</button>
              </div>
            </div>
          </div>

          <!-- ════ RIGHT COLUMN: LIVE FEED & METRICS FROM DATABASE ════ -->
          <div class="lg:col-span-7 flex flex-col gap-5">
            <!-- Attendance Counters -->
            <div class="grid grid-cols-4 gap-3">
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center">
                <p class="text-xs font-semibold text-text-muted uppercase">Enrolled</p>
                <p class="text-2xl font-bold text-slate-800 mt-0.5" id="metric-enrolled">0</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Present</p>
                <p class="text-2xl font-bold text-emerald-600 mt-0.5" id="metric-present">0</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center">
                <p class="text-xs font-semibold text-amber-600 uppercase">Tardy</p>
                <p class="text-2xl font-bold text-amber-600 mt-0.5" id="metric-tardy">0</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center">
                <p class="text-xs font-semibold text-rose-600 uppercase">Pending</p>
                <p class="text-2xl font-bold text-rose-600 mt-0.5" id="metric-pending">0</p>
              </div>
            </div>

            <!-- Live Scan Stream Card -->
            <div class="bg-white rounded-xl shadow-card border border-slate-100 flex-1 flex flex-col overflow-hidden min-h-[420px]">
              <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-text-primary flex items-center gap-2">
                  <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-teal-500"></span>
                  </span>
                  Live Student Check-In Feed
                </h3>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full" id="feed-count">0 checked in</span>
                  <button type="button" id="view-all-btn" onclick="openPresentStudentsModal()" class="px-2.5 py-1 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 rounded-lg transition flex items-center gap-1.5 shadow-2xs cursor-pointer" title="View all present students">
                    <span>View All</span>
                    <span id="view-all-count-badge" class="px-1.5 py-0.2 bg-teal-600 text-white rounded-full text-[10px] font-bold">0</span>
                  </button>
                  <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer" onclick="loadLiveFeed()" title="Refresh feed">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  </button>
                </div>
              </div>

              <!-- Live Stream List from DB -->
              <div class="p-4 overflow-y-auto space-y-2.5 max-h-[380px] flex-1" id="live-feed-list">
                <!-- Live check-ins loaded via AJAX from `attendance` table -->
                <div class="flex flex-col items-center justify-center py-12 text-slate-400 text-center">
                  <svg class="w-8 h-8 mb-2 animate-spin text-teal-500" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                  <p class="text-xs">Connecting to live attendance database...</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- ════ CLOSE SESSION CONFIRMATION MODAL ════ -->
  <div id="close-session-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
      <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>

      <h3 class="text-xl font-bold text-center text-text-primary mb-1">Close Attendance Session?</h3>
      <p class="text-sm text-text-secondary text-center mb-5">
        Closing the session stops QR scanning and will <strong>automatically create Absence records</strong> in the database for all enrolled students who have not checked in yet.
      </p>

      <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs space-y-1.5 mb-6">
        <div class="flex justify-between font-medium text-slate-700">
          <span>Present Students:</span>
          <span class="text-emerald-600 font-bold" id="modal-present-count">0</span>
        </div>
        <div class="flex justify-between font-medium text-slate-700">
          <span>Tardy Students:</span>
          <span class="text-amber-600 font-bold" id="modal-tardy-count">0</span>
        </div>
        <div class="flex justify-between font-bold text-rose-700 border-t border-slate-200 pt-1.5">
          <span>Unrecorded ➔ Marked Absent:</span>
          <span id="modal-pending-count">0</span>
        </div>
      </div>

      <div class="flex justify-end gap-3">
        <button type="button" class="btn btn-secondary flex-1 cursor-pointer" onclick="closeModal()">Cancel</button>
        <button type="button" id="confirm-close-btn" class="btn btn-danger flex-1 cursor-pointer" onclick="executeCloseSession()">Confirm &amp; Process Absences</button>
      </div>
    </div>
  </div>

  <!-- ════ ALL PRESENT STUDENTS MODAL ════ -->
  <div id="present-students-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[88vh] animate-scale-in overflow-hidden">
      <!-- Modal Header -->
      <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-2xs font-bold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Present &amp; Checked-In Students</h3>
            <p class="text-xs text-slate-500">Live attendance records for today's session</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span id="modal-present-badge-count" class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
            0 Students
          </span>
          <button type="button" onclick="closePresentStudentsModal()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Filter / Search Bar -->
      <div class="p-4 border-b border-slate-100 bg-white flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div class="relative w-full sm:w-72">
          <input type="text" id="present-search-input" oninput="filterPresentModalList()" placeholder="Search by student name or ID..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-teal-500 transition">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <div class="flex items-center gap-1.5 w-full sm:w-auto">
          <button type="button" onclick="setPresentFilter('all')" id="filter-tab-all" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-teal-600 text-white shadow-2xs cursor-pointer">All (<span id="modal-tab-all-count">0</span>)</button>
          <button type="button" onclick="setPresentFilter('present')" id="filter-tab-present" class="px-2.5 py-1 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer">Present (<span id="modal-tab-present-count">0</span>)</button>
          <button type="button" onclick="setPresentFilter('tardy')" id="filter-tab-tardy" class="px-2.5 py-1 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer">Tardy (<span id="modal-tab-tardy-count">0</span>)</button>
        </div>
      </div>

      <!-- Modal Body (Scrollable List) -->
      <div class="p-5 overflow-y-auto space-y-2.5 flex-1 min-h-[260px] max-h-[460px]" id="modal-present-list">
        <!-- Rendered dynamically -->
      </div>

      <!-- Modal Footer -->
      <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
        <span id="modal-footer-stats">Showing 0 verified records</span>
        <button type="button" onclick="closePresentStudentsModal()" class="btn btn-secondary btn-sm py-1.5 px-4 cursor-pointer">Close</button>
      </div>
    </div>
  </div>

  <?php include dirname(__DIR__) . '/partials/footer.php'; ?>
  <script src="<?= url('assets/js/qrcode-generator.js') ?>"></script>

  <script>
    const ROTATION_INTERVAL_SECONDS = 1800; // 30 Minutes
    let remainingSeconds = 0;
    let activeQrCode = null;
    let activeSessionId = null;
    let qrGenerator = null;
    let timerInterval = null;
    let liveFeedPolling = null;

    function formatCountdown(totalSecs) {
      if (totalSecs <= 0) return '00m 00s';
      const mins = Math.floor(totalSecs / 60);
      const secs = totalSecs % 60;
      return `${mins.toString().padStart(2, '0')}m ${secs.toString().padStart(2, '0')}s`;
    }

    function renderQRCode(code6Digits) {
      const container = document.getElementById('qrcode-container');
      if (!container) return;
      container.innerHTML = '';
      
      qrGenerator = new QRCode(container, {
        width: 240,
        height: 240,
        colorDark: '#0f172a',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
      });
      // Generate standard 6-digit QR code
      qrGenerator.makeCode(String(code6Digits));
    }

    function showActiveQrState(session) {
      document.getElementById('qr-active-box').classList.remove('hidden');
      document.getElementById('qr-empty-box').classList.add('hidden');
      document.getElementById('qr-refresh-btn-wrap').classList.remove('hidden');

      activeQrCode = session.qr_code;
      activeSessionId = session.qr_session_id;
      remainingSeconds = session.expires_in_seconds || ROTATION_INTERVAL_SECONDS;

      document.getElementById('token-display').textContent = session.qr_code;
      document.getElementById('token-sub-display').textContent = session.qr_code;
      document.getElementById('header-session-id').textContent = `Session #${session.qr_session_id} · Code: ${session.qr_code}`;

      // Set Active Badge
      const statusBadge = document.getElementById('header-status-badge');
      statusBadge.className = 'px-2 py-0.5 text-xs font-bold uppercase rounded bg-emerald-100 text-emerald-800 tracking-wider';
      statusBadge.textContent = 'Live Attendance Session Active';

      const statusIndicator = document.getElementById('header-status-indicator');
      statusIndicator.innerHTML = `
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
      `;

      renderQRCode(session.qr_code);
      updateTimerDisplay();

      if (timerInterval) clearInterval(timerInterval);
      timerInterval = setInterval(tickTimer, 1000);
    }

    function showEmptyQrState() {
      document.getElementById('qr-active-box').classList.add('hidden');
      document.getElementById('qr-empty-box').classList.remove('hidden');
      document.getElementById('qr-refresh-btn-wrap').classList.add('hidden');

      document.getElementById('token-display').textContent = 'EXPIRED';
      document.getElementById('token-sub-display').textContent = '------';
      document.getElementById('header-session-id').textContent = 'Session Inactive';

      // Set Inactive Badge
      const statusBadge = document.getElementById('header-status-badge');
      statusBadge.className = 'px-2 py-0.5 text-xs font-bold uppercase rounded bg-slate-100 text-slate-600 tracking-wider';
      statusBadge.textContent = 'Session Inactive / Expired';

      const statusIndicator = document.getElementById('header-status-indicator');
      statusIndicator.innerHTML = `
        <span class="relative inline-flex rounded-full h-3 w-3 bg-slate-400"></span>
      `;

      document.getElementById('countdown-text').textContent = '00m 00s';
      document.getElementById('qr-timer-bar').style.width = '0%';

      if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
      }
    }

    function tickTimer() {
      if (remainingSeconds <= 0) {
        showEmptyQrState();
        return;
      }
      remainingSeconds--;
      updateTimerDisplay();
      if (remainingSeconds <= 0) {
        showEmptyQrState();
        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast('QR session expired (30 mins limit reached).', 'warning');
        }
      }
    }

    function updateTimerDisplay() {
      document.getElementById('countdown-text').textContent = formatCountdown(remainingSeconds);
      const pct = Math.min(100, Math.max(0, (remainingSeconds / ROTATION_INTERVAL_SECONDS) * 100));
      const bar = document.getElementById('qr-timer-bar');
      bar.style.width = pct + '%';
      
      // Color shifts when under 5 minutes
      if (remainingSeconds <= 300) {
        bar.className = 'bg-gradient-to-r from-amber-500 to-rose-500 h-2 transition-all duration-1000 ease-linear';
      } else {
        bar.className = 'bg-gradient-to-r from-teal-500 to-emerald-500 h-2 transition-all duration-1000 ease-linear';
      }
    }

    // Generate / Rotate 6-digit QR session in database
    async function manualGenerateQR() {
      try {
        const res = await fetch('<?= url("api/teacher/qr-session/generate") ?>', {
          method: 'POST',
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
          showActiveQrState(data.session);
          if (window.APP && typeof APP.showToast === 'function') {
            APP.showToast(`New 6-digit QR generated (${data.session.qr_code}) — 30m window started!`, 'success');
          }
        } else {
          alert(data.message || 'Could not generate QR session');
        }
      } catch (err) {
        console.error('Error generating QR:', err);
      }
    }

    // Check active session on load
    async function checkActiveSession() {
      try {
        const res = await fetch('<?= url("api/teacher/qr-session/active") ?>', {
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (res.ok && data.has_active_session && data.session) {
          showActiveQrState(data.session);
        } else {
          // If no active session found, generate a fresh 6-digit session automatically
          manualGenerateQR();
        }
      } catch (err) {
        console.error('Error loading active session:', err);
        manualGenerateQR();
      }
    }

    // Client-side cache manager to prevent flickering and enable instant rendering
    const AttendanceFeedCache = {
      KEY: 'bcp_live_attendance_cache_v2',
      get() {
        try {
          const raw = sessionStorage.getItem(this.KEY);
          return raw ? JSON.parse(raw) : null;
        } catch (e) {
          return null;
        }
      },
      set(data) {
        try {
          sessionStorage.setItem(this.KEY, JSON.stringify(data));
        } catch (e) {}
      },
      clearActiveFeed() {
        try {
          const cached = this.get();
          if (cached) {
            cached.checkins = [];
            this.set(cached);
          }
        } catch (e) {}
      }
    };

    window.cachedActiveCheckins = [];
    window.cachedAllTodayCheckins = [];
    window.currentPresentFilter = 'all';
    window.lastRenderSignature = '';

    // Load Live Attendance Feed & Metrics directly from database with caching
    async function loadLiveFeed(isManualRefresh = false) {
      try {
        const url = activeSessionId 
          ? `<?= url("api/teacher/attendance/live-feed") ?>?session_id=${encodeURIComponent(activeSessionId)}`
          : '<?= url("api/teacher/attendance/live-feed") ?>';

        const res = await fetch(url, {
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
          // Cache latest response
          AttendanceFeedCache.set(data);

          // Render only if data actually changed or if manual refresh
          const signature = JSON.stringify({
            activeCount: (data.checkins || []).length,
            allCount: (data.all_today_checkins || []).length,
            present: data.metrics?.present,
            tardy: data.metrics?.tardy,
            hasActive: data.has_active_session,
            lastId: data.checkins?.[0]?.attendance_id || 0
          });

          if (isManualRefresh || signature !== window.lastRenderSignature) {
            window.lastRenderSignature = signature;
            renderLiveFeed(data.checkins || [], data.metrics || {}, data.all_today_checkins || []);
          }
        }
      } catch (err) {
        console.error('Error fetching live feed:', err);
      }
    }

    function renderLiveFeed(activeCheckins, metrics, allTodayCheckins) {
      window.cachedActiveCheckins = activeCheckins || [];
      window.cachedAllTodayCheckins = (allTodayCheckins && allTodayCheckins.length > 0) 
        ? allTodayCheckins 
        : (window.cachedAllTodayCheckins.length > 0 ? window.cachedAllTodayCheckins : activeCheckins || []);

      // Update counters
      document.getElementById('metric-enrolled').textContent = metrics.enrolled || 0;
      document.getElementById('metric-present').textContent = metrics.present || 0;
      document.getElementById('metric-tardy').textContent = metrics.tardy || 0;
      document.getElementById('metric-pending').textContent = metrics.pending || 0;
      
      const totalCheckedIn = metrics.total_checked_in !== undefined ? metrics.total_checked_in : window.cachedAllTodayCheckins.length;
      const activeCheckedIn = (activeCheckins && activeCheckins.length > 0) ? activeCheckins.length : 0;
      
      document.getElementById('feed-count').textContent = activeQrCode 
        ? `${activeCheckedIn} in active session` 
        : `${totalCheckedIn} checked in today`;
      document.getElementById('view-all-count-badge').textContent = totalCheckedIn;

      // Update Modal summary numbers
      document.getElementById('modal-present-count').textContent = metrics.present || 0;
      document.getElementById('modal-tardy-count').textContent = metrics.tardy || 0;
      document.getElementById('modal-pending-count').textContent = `${metrics.pending || 0} students`;

      // Update Modal tab counts from all today records
      document.getElementById('modal-tab-all-count').textContent = totalCheckedIn;
      document.getElementById('modal-tab-present-count').textContent = metrics.present || 0;
      document.getElementById('modal-tab-tardy-count').textContent = metrics.tardy || 0;
      document.getElementById('modal-present-badge-count').textContent = `${totalCheckedIn} Students`;

      const list = document.getElementById('live-feed-list');

      // If session is closed / no active checkins, show clean waiting state for the upcoming session
      if (!activeCheckins || activeCheckins.length === 0) {
        list.innerHTML = `
          <div class="flex flex-col items-center justify-center py-12 text-slate-400 text-center animate-fade-in">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-2 text-slate-400">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <p class="text-sm font-semibold text-slate-600">${activeQrCode ? 'Waiting for Student Scans' : 'No Active Live Scans'}</p>
            <p class="text-xs text-slate-400 mt-0.5">
              ${activeQrCode ? 'Students scanning the current 6-digit dynamic QR will appear here.' : 'Previous session data saved in View All modal. Generate QR to start a new live stream.'}
            </p>
            ${totalCheckedIn > 0 ? `
              <button type="button" onclick="openPresentStudentsModal()" class="mt-4 px-3 py-1.5 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-2xs">
                <span>View All ${totalCheckedIn} Students Recorded Today →</span>
              </button>
            ` : ''}
          </div>
        `;
        return;
      }

      // ONLY SHOW TOP 5 ITEMS IN THE ACTIVE LIVE FEED
      const top5Checkins = activeCheckins.slice(0, 5);

      let html = top5Checkins.map(item => {
        let badgeClass = 'badge-present';
        let badgeText = '● Present';
        let borderBg = 'bg-emerald-50/70 border-emerald-200';
        let avatarBg = 'bg-emerald-600';

        if (item.status === 'tardy') {
          badgeClass = 'badge-tardy';
          badgeText = '● Tardy';
          borderBg = 'bg-amber-50/70 border-amber-200';
          avatarBg = 'bg-amber-600';
        } else if (item.status === 'absent') {
          badgeClass = 'bg-rose-100 text-rose-800 border border-rose-200 text-xs px-2 py-0.5 rounded-full font-semibold';
          badgeText = '● Absent';
          borderBg = 'bg-rose-50/70 border-rose-200';
          avatarBg = 'bg-rose-600';
        }

        return `
          <div class="p-3 ${borderBg} border rounded-xl flex items-center justify-between animate-fade-in transition hover:shadow-2xs">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full ${avatarBg} text-white flex items-center justify-center font-bold text-xs shadow-xs">
                ${escapeHtml(item.initials)}
              </div>
              <div>
                <h4 class="text-sm font-bold text-slate-800">${escapeHtml(item.student_name)}</h4>
                <p class="text-xs font-mono text-slate-600">${escapeHtml(item.student_number)} · Dynamic 6-Digit QR</p>
              </div>
            </div>
            <div class="text-right">
              <span class="${badgeClass}">${badgeText}</span>
              <p class="text-[11px] text-text-muted mt-0.5 font-mono">${escapeHtml(item.time)}</p>
            </div>
          </div>
        `;
      }).join('');

      // If more than 5 in active session, or if multiple sessions exist today, show the View All banner
      if (activeCheckins.length > 5 || totalCheckedIn > activeCheckins.length) {
        html += `
          <div class="pt-2 text-center">
            <button type="button" onclick="openPresentStudentsModal()" class="w-full py-2 px-3 text-xs font-bold text-teal-700 hover:text-teal-800 bg-teal-50/70 hover:bg-teal-100/80 border border-teal-200 rounded-xl transition flex items-center justify-center gap-2 cursor-pointer shadow-2xs">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <span>View All ${totalCheckedIn} Present &amp; Checked-In Students →</span>
            </button>
          </div>
        `;
      }

      list.innerHTML = html;

      // If modal is currently open, refresh its content too
      if (!document.getElementById('present-students-modal').classList.contains('hidden')) {
        renderPresentModalList();
      }
    }

    function openPresentStudentsModal() {
      document.getElementById('present-students-modal').classList.remove('hidden');
      document.getElementById('present-search-input').value = '';
      window.currentPresentFilter = 'all';
      updateFilterTabsUI();
      renderPresentModalList();
    }

    function closePresentStudentsModal() {
      document.getElementById('present-students-modal').classList.add('hidden');
    }

    function setPresentFilter(filter) {
      window.currentPresentFilter = filter;
      updateFilterTabsUI();
      renderPresentModalList();
    }

    function updateFilterTabsUI() {
      const tabs = ['all', 'present', 'tardy'];
      tabs.forEach(t => {
        const btn = document.getElementById(`filter-tab-${t}`);
        if (!btn) return;
        if (window.currentPresentFilter === t) {
          btn.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-teal-600 text-white shadow-2xs cursor-pointer';
        } else {
          btn.className = 'px-2.5 py-1 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer';
        }
      });
    }

    function filterPresentModalList() {
      renderPresentModalList();
    }

    function renderPresentModalList() {
      const listEl = document.getElementById('modal-present-list');
      const searchVal = (document.getElementById('present-search-input').value || '').trim().toLowerCase();
      const filter = window.currentPresentFilter || 'all';

      // Use all today checkins (retained even when session is closed)
      let items = (window.cachedAllTodayCheckins && window.cachedAllTodayCheckins.length > 0)
        ? window.cachedAllTodayCheckins
        : window.cachedActiveCheckins;

      // Filter by status tab
      if (filter === 'present') {
        items = items.filter(i => i.status === 'present');
      } else if (filter === 'tardy') {
        items = items.filter(i => i.status === 'tardy');
      }

      // Filter by search keyword
      if (searchVal) {
        items = items.filter(i => 
          (i.student_name && i.student_name.toLowerCase().includes(searchVal)) ||
          (i.student_number && String(i.student_number).toLowerCase().includes(searchVal)) ||
          (i.subject && i.subject.toLowerCase().includes(searchVal))
        );
      }

      const totalRecords = window.cachedAllTodayCheckins.length || window.cachedActiveCheckins.length;
      document.getElementById('modal-footer-stats').textContent = `Showing ${items.length} of ${totalRecords} total student records`;

      if (items.length === 0) {
        listEl.innerHTML = `
          <div class="flex flex-col items-center justify-center py-12 text-slate-400 text-center">
            <svg class="w-10 h-10 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-semibold text-slate-600">No matching students found</p>
            <p class="text-xs text-slate-400 mt-0.5">Try clearing your search query or selecting a different status tab.</p>
          </div>
        `;
        return;
      }

      listEl.innerHTML = items.map((item, idx) => {
        let badgeClass = 'badge-present';
        let badgeText = '● Present';
        let borderBg = 'bg-white border-slate-200';
        let avatarBg = 'bg-emerald-600';

        if (item.status === 'tardy') {
          badgeClass = 'badge-tardy';
          badgeText = '● Tardy';
          borderBg = 'bg-amber-50/40 border-amber-200';
          avatarBg = 'bg-amber-600';
        } else if (item.status === 'absent') {
          badgeClass = 'bg-rose-100 text-rose-800 border border-rose-200 text-xs px-2 py-0.5 rounded-full font-semibold';
          badgeText = '● Absent';
          borderBg = 'bg-rose-50/40 border-rose-200';
          avatarBg = 'bg-rose-600';
        }

        return `
          <div class="p-3.5 ${borderBg} border rounded-xl flex items-center justify-between hover:bg-slate-50 transition shadow-2xs">
            <div class="flex items-center gap-3">
              <span class="text-xs font-mono font-bold text-slate-400 w-5 text-right">${idx + 1}.</span>
              <div class="w-10 h-10 rounded-full ${avatarBg} text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                ${escapeHtml(item.initials)}
              </div>
              <div>
                <h4 class="text-sm font-bold text-slate-900">${escapeHtml(item.student_name)}</h4>
                <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mt-0.5">
                  <span class="font-semibold text-slate-700">${escapeHtml(item.student_number)}</span>
                  <span>•</span>
                  <span>${escapeHtml(item.subject || 'Web Systems')}</span>
                </div>
              </div>
            </div>
            <div class="text-right shrink-0">
              <span class="${badgeClass}">${badgeText}</span>
              <p class="text-[11px] text-slate-500 mt-1 font-mono">${escapeHtml(item.time)}</p>
            </div>
          </div>
        `;
      }).join('');
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    // Simulate real database check-in
    async function simulateScan(studentId, studentName, status = 'present') {
      if (!activeQrCode) {
        APP.showToast('No active QR code. Please generate a QR code first.', 'warning');
        return;
      }

      try {
        const res = await fetch('<?= url("api/attendance/check-in") ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            qr_code: activeQrCode,
            student_id: studentId,
            status: status
          })
        });

        const data = await res.json();
        if (res.ok && data.status === 'success') {
          APP.showToast(data.message, 'success');
          loadLiveFeed(true); // Immediately reload database feed
        } else {
          APP.showToast(data.message || 'Check-in failed', res.status === 409 ? 'info' : 'error');
        }
      } catch (err) {
        console.error('Scan simulation error:', err);
        APP.showToast('Error processing scan', 'error');
      }
    }

    function toggleFullscreen() {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(err => alert(err.message));
      } else {
        document.exitFullscreen();
      }
    }

    function openCloseSessionModal() {
      document.getElementById('close-session-modal').classList.remove('hidden');
    }

    function closeModal() {
      document.getElementById('close-session-modal').classList.add('hidden');
    }

    async function executeCloseSession() {
      const btn = document.getElementById('confirm-close-btn');
      btn.disabled = true;
      btn.textContent = 'Processing Absences...';

      try {
        const res = await fetch('<?= url("api/teacher/qr-session/close") ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ qr_session_id: activeSessionId })
        });
        const data = await res.json();

        closeModal();
        showEmptyQrState();

        // Clear active session in local state & cache while keeping modal data
        activeQrCode = null;
        activeSessionId = null;
        window.cachedActiveCheckins = [];
        AttendanceFeedCache.clearActiveFeed();

        // Reload feed to get updated absences & today's totals
        loadLiveFeed(true);

        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast(data.message || 'Session closed successfully.', 'success');
        }
      } catch (err) {
        console.error('Error closing session:', err);
        closeModal();
      } finally {
        btn.disabled = false;
        btn.textContent = 'Confirm & Process Absences';
      }
    }

    // Lifecycle setup with Instant Cache Hydration
    document.addEventListener('DOMContentLoaded', () => {
      // 1. Instant Cache Hydration to eliminate initial loading flash
      const cached = AttendanceFeedCache.get();
      if (cached) {
        renderLiveFeed(cached.checkins || [], cached.metrics || {}, cached.all_today_checkins || []);
      }

      // 2. Fetch fresh status from server
      checkActiveSession();
      loadLiveFeed();

      // 3. Poll real database feed every 3 seconds
      liveFeedPolling = setInterval(() => loadLiveFeed(false), 3000);
    });

    window.addEventListener('beforeunload', () => {
      if (timerInterval) clearInterval(timerInterval);
      if (liveFeedPolling) clearInterval(liveFeedPolling);
    });
  </script>
</body>
</html>
<script>APP.highlightNav('classes');</script>

