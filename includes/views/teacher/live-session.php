<?php $page_title = 'Live Attendance Session'; ?>
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
              <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
              </span>
              <span class="px-2 py-0.5 text-xs font-bold uppercase rounded bg-emerald-100 text-emerald-800 tracking-wider">
                Live Attendance Session Active
              </span>
              <span class="text-xs font-mono text-text-muted">ID: ses-101</span>
            </div>
            <h1 class="text-2xl font-bold text-text-primary">BSIT 3-A · IT311 (Web Systems)</h1>
            <p class="text-sm text-text-secondary">Started at 08:00 AM · Late threshold: 15 mins · Room 402</p>
          </div>

          <!-- Controls -->
          <div class="flex items-center gap-3">
            <button type="button" class="btn btn-secondary" onclick="toggleFullscreen()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
              Fullscreen Mode
            </button>
            <button type="button" class="btn btn-danger font-semibold shadow-sm flex items-center gap-2" onclick="openCloseSessionModal()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              Close Session &amp; Mark Absences
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
          <!-- ════ LEFT COLUMN: DYNAMIC QR CODE CARD (Spec Section 15 & 16) ════ -->
          <div class="lg:col-span-5 bg-white rounded-2xl shadow-card border border-slate-100 p-6 flex flex-col items-center justify-between text-center relative overflow-hidden">
            <div class="w-full">
              <div class="flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Dynamic Anti-Screenshot QR</span>
                <span class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded text-text-secondary" id="token-display">LAMS-8A2F1C</span>
              </div>

              <!-- Animated Rotating Countdown Bar -->
              <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden mb-5">
                <div id="qr-timer-bar" class="bg-teal-500 h-1.5 transition-all duration-1000 ease-linear" style="width: 100%;"></div>
              </div>

              <!-- Dynamic QR Box -->
              <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl flex flex-col items-center justify-center mx-auto max-w-[300px]">
                <div id="qrcode-container" class="w-64 h-64 flex items-center justify-center">
                  <!-- QR Code Rendered dynamically via qrcode-generator.js -->
                </div>
                <div class="flex items-center gap-2 mt-3 text-xs font-semibold text-slate-600">
                  <svg class="w-3.5 h-3.5 text-teal-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  <span>Rotates in <span id="countdown-text" class="text-teal-700 font-bold">15s</span></span>
                </div>
              </div>

              <p class="text-xs text-text-muted mt-4">
                Students must scan with an authenticated device enrolled in <strong>BSIT 3-A</strong>. Screenshots expire after 15 seconds.
              </p>
            </div>

            <!-- Demo Simulation Controls -->
            <div class="w-full mt-6 pt-4 border-t border-slate-100 bg-slate-50 -mx-6 -mb-6 p-4 rounded-b-2xl">
              <p class="text-[11px] font-bold uppercase text-text-muted tracking-wider mb-2">Simulate Live Student Scans (Testing):</p>
              <div class="flex flex-wrap items-center justify-center gap-2">
                <button type="button" class="btn btn-secondary btn-sm text-xs" onclick="simulateScan('2026-00125', 'Pedro Reyes')">+ Pedro Reyes (Valid)</button>
                <button type="button" class="btn btn-secondary btn-sm text-xs" onclick="simulateScan('2026-00128', 'Jenny Lim', 'WRONG_SECTION')">+ Jenny Lim (Wrong Sec)</button>
                <button type="button" class="btn btn-secondary btn-sm text-xs" onclick="simulateScan('2026-00123', 'Juan Dela Cruz', 'ALREADY_RECORDED')">+ Duplicate Scan</button>
              </div>
            </div>
          </div>

          <!-- ════ RIGHT COLUMN: LIVE FEED & METRICS (Spec Section 35) ════ -->
          <div class="lg:col-span-7 flex flex-col gap-5">
            <!-- Attendance Counters -->
            <div class="grid grid-cols-4 gap-3">
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center">
                <p class="text-xs font-semibold text-text-muted uppercase">Enrolled</p>
                <p class="text-2xl font-bold text-slate-800 mt-0.5" id="metric-enrolled">6</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center">
                <p class="text-xs font-semibold text-emerald-600 uppercase">Present</p>
                <p class="text-2xl font-bold text-emerald-600 mt-0.5" id="metric-present">3</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center">
                <p class="text-xs font-semibold text-amber-600 uppercase">Tardy</p>
                <p class="text-2xl font-bold text-amber-600 mt-0.5" id="metric-tardy">1</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center">
                <p class="text-xs font-semibold text-rose-600 uppercase">Pending</p>
                <p class="text-2xl font-bold text-rose-600 mt-0.5" id="metric-pending">2</p>
              </div>
            </div>

            <!-- Live Scan Stream Card -->
            <div class="bg-white rounded-xl shadow-card border border-slate-100 flex-1 flex flex-col overflow-hidden">
              <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-text-primary flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-teal-500"></span>
                  Live Student Check-In Feed
                </h3>
                <span class="text-xs text-text-muted" id="feed-count">4 checked in</span>
              </div>

              <!-- Live Stream List -->
              <div class="p-4 overflow-y-auto space-y-2.5 max-h-[360px]" id="live-feed-list">
                <!-- Row 1 -->
                <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between animate-fade-in">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">JD</div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-800">Juan Dela Cruz</h4>
                      <p class="text-xs font-mono text-emerald-800">2026-00123 · Dynamic QR Scan</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="badge badge-present">● Present</span>
                    <p class="text-[11px] text-text-muted mt-0.5">08:04:12 AM</p>
                  </div>
                </div>

                <!-- Row 2 -->
                <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between animate-fade-in">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">MS</div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-800">Maria Santos</h4>
                      <p class="text-xs font-mono text-emerald-800">2026-00124 · Dynamic QR Scan</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="badge badge-present">● Present</span>
                    <p class="text-[11px] text-text-muted mt-0.5">08:07:33 AM</p>
                  </div>
                </div>

                <!-- Row 3 -->
                <div class="p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between animate-fade-in">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">AM</div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-800">Ana Mendoza</h4>
                      <p class="text-xs font-mono text-emerald-800">2026-00126 · Dynamic QR Scan</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="badge badge-present">● Present</span>
                    <p class="text-[11px] text-text-muted mt-0.5">08:11:05 AM</p>
                  </div>
                </div>

                <!-- Row 4 -->
                <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl flex items-center justify-between animate-fade-in">
                  <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-amber-600 text-white flex items-center justify-center font-bold text-xs">CV</div>
                    <div>
                      <h4 class="text-sm font-bold text-slate-800">Carlo Villanueva</h4>
                      <p class="text-xs font-mono text-amber-800">2026-00127 · Late (+22 mins)</p>
                    </div>
                  </div>
                  <div class="text-right">
                    <span class="badge badge-tardy">● Tardy</span>
                    <p class="text-[11px] text-text-muted mt-0.5">08:22:45 AM</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- ════ CLOSE SESSION CONFIRMATION MODAL (Spec Section 19 & 20) ════ -->
  <div id="close-session-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
      <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>

      <h3 class="text-xl font-bold text-center text-text-primary mb-1">Close Attendance Session?</h3>
      <p class="text-sm text-text-secondary text-center mb-5">
        Closing the session stops QR scanning and will <strong>automatically create Absence records</strong> for all 2 enrolled students who did not check in.
      </p>

      <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs space-y-1.5 mb-6">
        <div class="flex justify-between font-medium text-slate-700">
          <span>Present Students:</span>
          <span class="text-emerald-600 font-bold">3</span>
        </div>
        <div class="flex justify-between font-medium text-slate-700">
          <span>Tardy Students:</span>
          <span class="text-amber-600 font-bold">1</span>
        </div>
        <div class="flex justify-between font-bold text-rose-700 border-t border-slate-200 pt-1.5">
          <span>Unrecorded ➔ Marked Absent:</span>
          <span>2 (Pedro Reyes, Robert Cruz)</span>
        </div>
      </div>

      <div class="flex justify-end gap-3">
        <button type="button" class="btn btn-secondary flex-1" onclick="closeModal()">Cancel</button>
        <button type="button" class="btn btn-danger flex-1" onclick="executeCloseSession()">Confirm &amp; Process Absences</button>
      </div>
    </div>
  </div>

  <?php include dirname(__DIR__) . '/partials/footer.php'; ?>
  <script src="<?= url('assets/js/qrcode-generator.js') ?>"></script>

  <script>
    let remainingSeconds = 15;
    let qrGenerator = null;

    document.addEventListener('DOMContentLoaded', () => {
      // Initialize dynamic QR renderer
      qrGenerator = new QRCode('qrcode-container', {
        width: 250,
        height: 250,
        colorDark: '#0B1929'
      });
      generateNewToken();
      setInterval(tickTimer, 1000);
    });

    function tickTimer() {
      remainingSeconds--;
      if (remainingSeconds <= 0) {
        generateNewToken();
        remainingSeconds = 15;
      }
      document.getElementById('countdown-text').textContent = remainingSeconds + 's';
      const pct = (remainingSeconds / 15) * 100;
      document.getElementById('qr-timer-bar').style.width = pct + '%';
    }

    function generateNewToken() {
      const randomHex = Math.random().toString(16).substr(2, 6).toUpperCase();
      const token = 'LAMS-' + randomHex;
      document.getElementById('token-display').textContent = token;

      const payload = JSON.stringify({
        session_id: 'ses-101',
        class_id: 'cls-1',
        section: 'BSIT 3-A',
        token: token,
        created: Date.now()
      });

      if (qrGenerator) {
        qrGenerator.makeCode(payload);
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

    function executeCloseSession() {
      closeModal();
      APP.showToast('Session closed. 2 students automatically marked Absent.', 'success');
      setTimeout(() => {
        window.location.href = '<?= url("teacher/attendance/history") ?>';
      }, 1500);
    }

    function simulateScan(studentNo, studentName, simulatedState = 'SUCCESS') {
      if (simulatedState === 'WRONG_SECTION') {
        APP.showToast(`Scan Rejected: ${studentName} (${studentNo}) is NOT enrolled in BSIT 3-A.`, 'error');
        return;
      }
      if (simulatedState === 'ALREADY_RECORDED') {
        APP.showToast(`Duplicate Scan: ${studentName} (${studentNo}) already marked present.`, 'info');
        return;
      }

      // Add to feed
      const list = document.getElementById('live-feed-list');
      const row = document.createElement('div');
      row.className = 'p-3 bg-emerald-50/70 border border-emerald-200 rounded-xl flex items-center justify-between animate-fade-in';
      row.innerHTML = `
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-full bg-emerald-600 text-white flex items-center justify-center font-bold text-xs">${studentName.split(' ').map(n=>n[0]).join('')}</div>
          <div>
            <h4 class="text-sm font-bold text-slate-800">${studentName}</h4>
            <p class="text-xs font-mono text-emerald-800">${studentNo} · Live Dynamic QR</p>
          </div>
        </div>
        <div class="text-right">
          <span class="badge badge-present">● Present</span>
          <p class="text-[11px] text-text-muted mt-0.5">${new Date().toLocaleTimeString()}</p>
        </div>
      `;
      list.prepend(row);

      const presentCount = parseInt(document.getElementById('metric-present').textContent) + 1;
      document.getElementById('metric-present').textContent = presentCount;
      const pendingCount = Math.max(0, parseInt(document.getElementById('metric-pending').textContent) - 1);
      document.getElementById('metric-pending').textContent = pendingCount;
      document.getElementById('feed-count').textContent = (presentCount + 1) + ' checked in';

      APP.showToast(`Logged: ${studentName} (Present)`, 'success');
    }
  </script>
</body>
</html>
<script>APP.highlightNav('classes');</script>
