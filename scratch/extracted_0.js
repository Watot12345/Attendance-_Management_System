
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
  