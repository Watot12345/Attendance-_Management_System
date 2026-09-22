/**
 * AI-Supported Attendance Management System
 * Global JS — app.js
 * Sidebar toggle, toast notifications, modal, confirm dialog,
 * tab switching, notification dropdown, user menu, loading screen, ripple effects
 */

const APP = {
  /* ── Sidebar (Mobile) ─────────────────────────────────────── */
  toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebar) sidebar.classList.toggle('open');
    if (overlay) overlay.classList.toggle('open');
  },
  closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    if (sidebar) sidebar.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
  },

  /* ── Toast Notifications (Powered by Sonner) ──────────────── */
  toast(message, type = 'info', duration = 4000) {
    const opts = typeof duration === 'object' ? duration : { duration: typeof duration === 'number' ? duration : 4000 };
    if (typeof window.toast !== 'undefined') {
      switch (type) {
        case 'success':
          return window.toast.success(message, opts);
        case 'error':
          return window.toast.error(message, opts);
        case 'warning':
          return window.toast.warning(message, opts);
        case 'info':
        default:
          return window.toast.info(message, opts);
      }
    } else {
      console.warn('Toast library not yet initialized:', message);
    }
  },
  showToast(message, type = 'info', duration = 4000) {
    return this.toast(message, type, duration);
  },

  /* ── Button Loading State Manager ─────────────────────────── */
  setLoading(btn, isLoading = true, loadingText = null) {
    const el = typeof btn === 'string' ? document.querySelector(btn) : btn;
    if (!el) return;

    if (isLoading) {
      if (!el.dataset.origHtml) {
        el.dataset.origHtml = el.innerHTML;
      }
      el.disabled = true;
      el.classList.add('is-loading');
      el.setAttribute('data-loading', 'true');
      
      const spinner = '<span class="btn-spinner"></span>';
      if (loadingText) {
        el.innerHTML = `${spinner}<span>${loadingText}</span>`;
      } else {
        const textSpan = el.querySelector('span:not(.btn-spinner)');
        const currentText = textSpan ? textSpan.textContent.trim() : (el.textContent.trim() || 'Loading...');
        el.innerHTML = `${spinner}<span>${currentText}</span>`;
      }
    } else {
      if (el.dataset.origHtml) {
        el.innerHTML = el.dataset.origHtml;
        delete el.dataset.origHtml;
      }
      el.disabled = false;
      el.classList.remove('is-loading');
      el.removeAttribute('data-loading');
    }
  },

  /* ── Modal ────────────────────────────────────────────────── */
  openModal(title, bodyHTML, footerHTML) {
    const backdrop = document.getElementById('modal-backdrop');
    const titleEl = document.getElementById('modal-title');
    const bodyEl = document.getElementById('modal-body');
    const footerEl = document.getElementById('modal-footer');

    if (titleEl) titleEl.textContent = title;
    if (bodyEl) bodyEl.innerHTML = bodyHTML;
    if (footerEl) footerEl.innerHTML = footerHTML || '';
    if (backdrop) backdrop.classList.remove('hidden');

    // Focus trap
    document.addEventListener('keydown', APP._modalEscHandler);
  },

  closeModal(event) {
    if (event && event.target !== event.currentTarget) return;
    const backdrop = document.getElementById('modal-backdrop');
    if (backdrop) backdrop.classList.add('hidden');
    document.removeEventListener('keydown', APP._modalEscHandler);
  },

  _modalEscHandler(e) {
    if (e.key === 'Escape') APP.closeModal();
  },

  /* ── Universal Confirmation Modal Component Manager ───────── */
  _confirmResolver: null,
  _confirmOptions: null,

  confirm(optionsOrMessage, legacyOnConfirm) {
    return new Promise((resolve) => {
      let opts = {};
      if (typeof optionsOrMessage === 'string') {
        opts = {
          message: optionsOrMessage,
          title: 'Confirm Action',
          type: 'danger',
          confirmText: 'Confirm',
          cancelText: 'Cancel',
          onConfirm: typeof legacyOnConfirm === 'function' ? legacyOnConfirm : null
        };
      } else if (typeof optionsOrMessage === 'object' && optionsOrMessage !== null) {
        opts = {
          title: optionsOrMessage.title || 'Confirm Action',
          message: optionsOrMessage.message || 'Are you sure you want to proceed?',
          type: optionsOrMessage.type || 'danger', // danger | warning | info | success
          confirmText: optionsOrMessage.confirmText || 'Confirm',
          cancelText: optionsOrMessage.cancelText || 'Cancel',
          confirmLoadingText: optionsOrMessage.confirmLoadingText || (optionsOrMessage.type === 'danger' ? 'Deleting...' : 'Processing...'),
          onConfirm: optionsOrMessage.onConfirm || null,
          onCancel: optionsOrMessage.onCancel || null
        };
      }

      APP._confirmResolver = resolve;
      APP._confirmOptions = opts;

      const modal = document.getElementById('global-confirm-modal');
      const box = document.getElementById('confirm-modal-box');
      const titleEl = document.getElementById('confirm-modal-title');
      const msgEl = document.getElementById('confirm-modal-message');
      const btnText = document.getElementById('confirm-modal-btn-text');
      const cancelBtn = document.getElementById('confirm-modal-cancel-btn');
      const actionBtn = document.getElementById('confirm-modal-action-btn');
      const spinner = document.getElementById('confirm-modal-btn-spinner');
      const iconWrap = document.getElementById('confirm-modal-icon-wrap');

      // Fallback if modal HTML not present
      if (!modal) {
        const stripHtml = (opts.message || '').replace(/<[^>]*>?/gm, '');
        const confirmed = window.confirm(stripHtml || 'Are you sure you want to proceed?');
        if (confirmed) {
          if (typeof opts.onConfirm === 'function') opts.onConfirm();
          resolve(true);
        } else {
          if (typeof opts.onCancel === 'function') opts.onCancel();
          resolve(false);
        }
        return;
      }

      if (titleEl) titleEl.textContent = opts.title;
      if (msgEl) msgEl.innerHTML = opts.message;
      if (btnText) btnText.textContent = opts.confirmText;
      if (cancelBtn) cancelBtn.textContent = opts.cancelText;
      if (actionBtn) actionBtn.disabled = false;
      if (cancelBtn) cancelBtn.disabled = false;
      if (spinner) spinner.classList.add('hidden');

      // Set thematic styling based on type
      const type = (opts.type || 'danger').toLowerCase();
      
      if (iconWrap) {
        // Hide all SVGs first
        iconWrap.querySelectorAll('[data-icon-type]').forEach(ic => ic.classList.add('hidden'));

        // Reset icon wrap base classes
        iconWrap.className = 'w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-all duration-200';

        if (actionBtn) {
          actionBtn.className = 'flex-1 py-2.5 px-4 rounded-xl font-semibold text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed';
        }

        if (type === 'danger') {
          iconWrap.classList.add('bg-rose-100', 'text-rose-600', 'ring-8', 'ring-rose-50');
          if (actionBtn) actionBtn.classList.add('bg-rose-600', 'hover:bg-rose-700', 'text-white', 'shadow-rose-600/20');
          const ic = iconWrap.querySelector('[data-icon-type="danger"]');
          if (ic) ic.classList.remove('hidden');
        } else if (type === 'warning') {
          iconWrap.classList.add('bg-amber-100', 'text-amber-600', 'ring-8', 'ring-amber-50');
          if (actionBtn) actionBtn.classList.add('bg-amber-600', 'hover:bg-amber-700', 'text-white', 'shadow-amber-600/20');
          const ic = iconWrap.querySelector('[data-icon-type="warning"]');
          if (ic) ic.classList.remove('hidden');
        } else if (type === 'success') {
          iconWrap.classList.add('bg-emerald-100', 'text-emerald-600', 'ring-8', 'ring-emerald-50');
          if (actionBtn) actionBtn.classList.add('bg-emerald-600', 'hover:bg-emerald-700', 'text-white', 'shadow-emerald-600/20');
          const ic = iconWrap.querySelector('[data-icon-type="success"]');
          if (ic) ic.classList.remove('hidden');
        } else { // info
          iconWrap.classList.add('bg-blue-100', 'text-blue-600', 'ring-8', 'ring-blue-50');
          if (actionBtn) actionBtn.classList.add('bg-blue-600', 'hover:bg-blue-700', 'text-white', 'shadow-blue-600/20');
          const ic = iconWrap.querySelector('[data-icon-type="info"]');
          if (ic) ic.classList.remove('hidden');
        }
      }

      // Display with smooth animation and background blur
      document.body.classList.add('confirm-modal-open');
      modal.classList.remove('hidden');
      requestAnimationFrame(() => {
        modal.classList.remove('opacity-100');
        modal.classList.add('opacity-100');
        if (box) {
          box.classList.remove('scale-95');
          box.classList.add('scale-100');
        }
      });

      document.addEventListener('keydown', APP._confirmEscHandler);
      if (actionBtn) actionBtn.focus();
    });
  },

  async handleConfirmModalAction() {
    const opts = APP._confirmOptions;
    const resolver = APP._confirmResolver;
    const actionBtn = document.getElementById('confirm-modal-action-btn');
    const cancelBtn = document.getElementById('confirm-modal-cancel-btn');
    const btnText = document.getElementById('confirm-modal-btn-text');
    const spinner = document.getElementById('confirm-modal-btn-spinner');

    if (opts && typeof opts.onConfirm === 'function') {
      try {
        if (actionBtn) actionBtn.disabled = true;
        if (cancelBtn) cancelBtn.disabled = true;
        if (spinner) spinner.classList.remove('hidden');
        if (btnText) btnText.textContent = opts.confirmLoadingText || 'Processing...';

        await opts.onConfirm();
        APP.closeConfirmModal(true, false);
        if (resolver) resolver(true);
      } catch (err) {
        console.error('Confirmation action error:', err);
        if (actionBtn) actionBtn.disabled = false;
        if (cancelBtn) cancelBtn.disabled = false;
        if (spinner) spinner.classList.add('hidden');
        if (btnText) btnText.textContent = opts.confirmText || 'Confirm';
      }
    } else {
      APP.closeConfirmModal(true, false);
      if (resolver) resolver(true);
    }
  },

  closeConfirmModal(confirmed = false, triggerResolver = true) {
    const modal = document.getElementById('global-confirm-modal');
    const box = document.getElementById('confirm-modal-box');
    const opts = APP._confirmOptions;
    const resolver = APP._confirmResolver;

    document.removeEventListener('keydown', APP._confirmEscHandler);
    document.body.classList.remove('confirm-modal-open');

    if (modal && box) {
      modal.classList.remove('opacity-100');
      modal.classList.add('opacity-0');
      box.classList.remove('scale-100');
      box.classList.add('scale-95');

      setTimeout(() => {
        modal.classList.add('hidden');
      }, 150);
    }

    if (!confirmed && opts && typeof opts.onCancel === 'function') {
      opts.onCancel();
    }

    if (triggerResolver && resolver) {
      resolver(confirmed);
      APP._confirmResolver = null;
      APP._confirmOptions = null;
    }
  },

  _confirmEscHandler(e) {
    if (e.key === 'Escape') {
      APP.closeConfirmModal(false);
    }
  },

  /* ── Manual Entry Modal ───────────────────────────────────── */
  openManualEntryModal() {
    const today = new Date().toISOString().split('T')[0];
    const bodyHTML = `
      <form id="manual-entry-modal-form" onsubmit="APP.submitManualEntry(event)">
        <div class="space-y-3.5">
          <!-- Student Selector with Avatar Preview -->
          <div>
            <label for="modal-manual-student" class="form-label text-xs mb-1 block">Student / Roster Member</label>
            <div class="flex items-center gap-2.5 p-2 rounded-lg border" style="background:var(--color-surface); border-color:var(--color-border)">
              <div id="modal-student-avatar" class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs text-white shrink-0 shadow-sm" style="background:var(--color-text-muted)">
                ?
              </div>
              <div class="flex-1 min-w-0">
                <select id="modal-manual-student" class="form-input form-select text-xs py-1.5" required onchange="APP._updateManualStudentAvatar(this)">
                  <option value="">Loading roster students...</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Date & Status -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="modal-manual-date" class="form-label text-xs">Date</label>
              <input type="date" id="modal-manual-date" class="form-input text-xs" value="${today}" required>
            </div>
            <div>
              <label for="modal-manual-status" class="form-label text-xs">Status</label>
              <select id="modal-manual-status" class="form-input form-select text-xs">
                <option value="present">Present</option>
                <option value="tardy">Tardy</option>
                <option value="absent">Absent</option>
              </select>
            </div>
          </div>

          <!-- Entry & Exit Time -->
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="modal-manual-entry" class="form-label text-xs">Entry Time</label>
              <input type="time" id="modal-manual-entry" class="form-input text-xs" value="08:00">
            </div>
            <div>
              <label for="modal-manual-exit" class="form-label text-xs">Exit Time (optional)</label>
              <input type="time" id="modal-manual-exit" class="form-input text-xs">
            </div>
          </div>

          <!-- Supporting Document / Image Attachment (Dropzone placeholder) -->
          <div>
            <label class="form-label text-xs mb-1 block">Supporting Document / Photo (Pass / Slip / ID)</label>
            <div class="border-2 border-dashed rounded-lg p-3 text-center cursor-pointer hover:bg-teal-50/20 transition-all" style="border-color:var(--color-border); background:var(--color-surface)" id="modal-manual-drop-zone" onclick="document.getElementById('modal-manual-file').click()">
              <div class="flex items-center justify-center gap-2 mb-1">
                <svg class="w-5 h-5" style="color:var(--color-teal-500)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span class="text-xs font-semibold" style="color:var(--color-text-primary)">Attach or Import Document / Photo</span>
              </div>
              <p class="text-[11px]" style="color:var(--color-text-muted)">Click to browse or drag and drop (PDF, JPG, PNG · max 5MB)</p>
              <input type="file" class="hidden" id="modal-manual-file" accept=".pdf,.jpg,.jpeg,.png" onchange="APP._handleManualFileSelect(event)">
            </div>
            <div id="modal-manual-file-preview" class="hidden mt-2 p-2 rounded-lg flex items-center gap-2 border" style="background:var(--color-teal-100); border-color:var(--color-teal-300)">
              <svg class="w-4 h-4 shrink-0" style="color:var(--color-teal-500)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <span class="text-xs font-medium truncate flex-1" style="color:var(--color-teal-500)" id="modal-manual-file-name"></span>
              <button type="button" class="text-xs font-semibold px-2 py-0.5 rounded text-red-600 hover:bg-red-50 inline-flex items-center gap-1" onclick="APP._removeManualFile()">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                Remove
              </button>
            </div>
          </div>

          <!-- Administrative Notes -->
          <div>
            <label for="modal-manual-notes" class="form-label text-xs">Administrative Notes / Override Reason</label>
            <textarea id="modal-manual-notes" class="form-input text-xs" rows="2" placeholder="e.g., RFID card unreadable, physically verified by teacher..."></textarea>
          </div>
        </div>
      </form>
    `;

    const footerHTML = `
      <button type="button" class="btn btn-secondary btn-sm" onclick="APP.closeModal()">Cancel</button>
      <button type="submit" form="manual-entry-modal-form" id="manual-entry-submit-btn" class="btn btn-primary btn-sm">Save Record</button>
    `;

    APP.openModal('Manual Attendance Entry', bodyHTML, footerHTML);

    // Fetch and populate roster students dynamically
    const rosterUrl = (typeof window.url === 'function') 
      ? window.url('api/teacher/roster/students') 
      : '/api/teacher/roster/students';
    fetch(rosterUrl)
      .then(r => r.json())
      .then(data => {
        const select = document.getElementById('modal-manual-student');
        if (!select) return;
        select.innerHTML = '<option value="">Select or search student...</option>';
        if (data.status === 'success' && Array.isArray(data.students) && data.students.length > 0) {
          data.students.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.student_id;
            opt.setAttribute('data-name', s.full_name || `${s.last_name}, ${s.first_name}`);
            opt.setAttribute('data-number', s.student_number || '');
            opt.setAttribute('data-section', s.section || '');
            opt.setAttribute('data-subject', s.course_title || '');
            opt.textContent = `${s.last_name}, ${s.first_name} · ${s.student_number} (${s.section})`;
            select.appendChild(opt);
          });
        } else {
          select.innerHTML = '<option value="" disabled>No enrolled students found in your roster</option>';
        }
      })
      .catch(err => {
        const select = document.getElementById('modal-manual-student');
        if (select) select.innerHTML = '<option value="" disabled>Error loading roster</option>';
      });
  },

  async submitManualEntry(event) {
    if (event) event.preventDefault();
    const studentSelect = document.getElementById('modal-manual-student');
    const studentId = studentSelect ? studentSelect.value : null;
    const selectedOpt = studentSelect ? studentSelect.options[studentSelect.selectedIndex] : null;
    const subject = selectedOpt ? selectedOpt.getAttribute('data-subject') : '';
    const dateVal = document.getElementById('modal-manual-date') ? document.getElementById('modal-manual-date').value : '';
    const statusVal = document.getElementById('modal-manual-status') ? document.getElementById('modal-manual-status').value : 'present';
    const timeVal = document.getElementById('modal-manual-entry') ? document.getElementById('modal-manual-entry').value : '';
    const notesVal = document.getElementById('modal-manual-notes') ? document.getElementById('modal-manual-notes').value : '';

    if (!studentId) {
      APP.showToast('Please select a student from your roster.', 'error');
      return;
    }

    const submitBtn = document.getElementById('manual-entry-submit-btn');
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Saving...';
    }

    try {
      const manualEntryUrl = (typeof window.url === 'function') 
        ? window.url('api/attendance/manual-entry') 
        : '/api/attendance/manual-entry';
      const resp = await fetch(manualEntryUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          student_id: parseInt(studentId, 10),
          date: dateVal,
          status: statusVal,
          time: timeVal,
          subject: subject,
          notes: notesVal
        })
      });

      const res = await resp.json();
      if (resp.ok && res.status === 'success') {
        APP.closeModal();
        APP.showToast(res.message || 'Manual attendance record saved successfully.', 'success');
        if (typeof window.fetchDailyLedger === 'function') {
          window.fetchDailyLedger();
        }
      } else {
        APP.showToast(res.message || 'Failed to record attendance.', 'error');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Save Record';
        }
      }
    } catch (e) {
      APP.showToast('Network error while saving attendance record.', 'error');
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Save Record';
      }
    }
  },

  _updateManualStudentAvatar(selectElem) {
    const avatar = document.getElementById('modal-student-avatar');
    if (!avatar) return;
    const selected = selectElem.options[selectElem.selectedIndex];
    const name = selected ? selected.getAttribute('data-name') : '';
    if (name) {
      let initials = '';
      const parts = name.trim().split(/[\s,]+/);
      if (parts.length >= 2) {
        initials = (parts[0][0] || '') + (parts[1][0] || '');
      } else if (parts.length === 1 && parts[0].length > 0) {
        initials = parts[0].substring(0, 2);
      }
      avatar.textContent = initials.toUpperCase() || 'ST';
      avatar.style.background = 'var(--color-teal-500)';
    } else {
      avatar.textContent = '?';
      avatar.style.background = 'var(--color-text-muted)';
    }
  },

  _handleManualFileSelect(e) {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    const preview = document.getElementById('modal-manual-file-preview');
    const fileName = document.getElementById('modal-manual-file-name');
    const dropZone = document.getElementById('modal-manual-drop-zone');
    if (preview && fileName) {
      const sizeKB = Math.round(file.size / 1024);
      fileName.textContent = `${file.name} (${sizeKB} KB)`;
      preview.classList.remove('hidden');
    }
    if (dropZone) {
      dropZone.classList.add('hidden');
    }
  },

  _removeManualFile() {
    const preview = document.getElementById('modal-manual-file-preview');
    const dropZone = document.getElementById('modal-manual-drop-zone');
    const fileInput = document.getElementById('modal-manual-file');
    if (preview) preview.classList.add('hidden');
    if (dropZone) dropZone.classList.remove('hidden');
    if (fileInput) fileInput.value = '';
  },

  /* ── Tab Switching ────────────────────────────────────────── */
  switchTab(tabGroupId, tabId) {
    const group = document.getElementById(tabGroupId);
    if (!group) return;

    // Hide all panels
    group.querySelectorAll('[data-tab-panel]').forEach(panel => {
      panel.classList.add('hidden');
    });
    // Deactivate all tab buttons
    group.querySelectorAll('[data-tab-btn]').forEach(btn => {
      btn.classList.remove('border-b-2', 'font-semibold');
      btn.style.borderColor = 'transparent';
      btn.style.color = 'var(--color-text-secondary)';
    });

    // Show target panel
    const panel = group.querySelector(`[data-tab-panel="${tabId}"]`);
    if (panel) panel.classList.remove('hidden');

    // Activate target button
    const btn = group.querySelector(`[data-tab-btn="${tabId}"]`);
    if (btn) {
      btn.classList.add('border-b-2', 'font-semibold');
      btn.style.borderColor = 'var(--color-teal-500)';
      btn.style.color = 'var(--color-text-primary)';
    }
  },

  /* ── Notification Dropdown ────────────────────────────────── */
  toggleNotifications() {
    const dropdown = document.getElementById('notif-dropdown');
    const userDropdown = document.getElementById('user-dropdown');
    if (userDropdown) userDropdown.classList.add('hidden');
    if (dropdown) dropdown.classList.toggle('hidden');
  },

  /* ── User Menu ────────────────────────────────────────────── */
  toggleUserMenu() {
    const dropdown = document.getElementById('user-dropdown');
    const notifDropdown = document.getElementById('notif-dropdown');
    if (notifDropdown) notifDropdown.classList.add('hidden');
    if (dropdown) dropdown.classList.toggle('hidden');
  },

  /* ── Fullscreen Loading Screen Manager ────────────────────── */
  showLoadingScreen(opts = {}) {
    const preloader = document.getElementById('global-app-preloader');
    if (!preloader) return;
    const titleEl = document.getElementById('global-loader-title');
    const subEl = document.getElementById('global-loader-subtitle');
    if (titleEl && opts.title) titleEl.textContent = opts.title;
    if (subEl && opts.subtitle) subEl.textContent = opts.subtitle;
    preloader.classList.remove('preloader-hidden');
  },

  hideLoadingScreen() {
    const preloader = document.getElementById('global-app-preloader');
    if (!preloader) return;
    preloader.classList.add('preloader-hidden');
  },

  /* ── Highlight Active Nav ─────────────────────────────────── */
  highlightNav(pageId) {
    document.querySelectorAll('#sidebar .nav-item').forEach(item => {
      item.classList.remove('active');
      if (item.dataset.page === pageId) {
        item.classList.add('active');
      }
    });
  },

  /* ── Inactivity Auto-Logout Manager (5 Minutes Inactivity, 30s Countdown) ── */
  inactivityManager: {
    INACTIVITY_TIME_MS: 4.5 * 60 * 1000, // 4.5 mins until warning (total = 5 mins / 300s)
    COUNTDOWN_SECONDS: 30,
    
    inactivityTimer: null,
    countdownInterval: null,
    remainingSeconds: 30,
    isWarningShown: false,
    lastActivityTime: Date.now(),
    isInitialized: false,

    init() {
      if (this.isInitialized) return;
      
      // Do NOT run on login/auth forms or password reset modals
      const isAuthPage = document.getElementById('credentials-form') || 
                         document.getElementById('auth-flow-title') ||
                         document.getElementById('reset-email-input');
      if (isAuthPage) return;

      this.isInitialized = true;
      this.bindEvents();
      this.startInactivityTimer();
    },

    bindEvents() {
      const resetHandler = () => {
        const now = Date.now();
        if (now - this.lastActivityTime < 300) return;
        this.lastActivityTime = now;

        if (!this.isWarningShown) {
          this.startInactivityTimer();
        }
      };

      const events = ['mousemove', 'mousedown', 'keydown', 'touchstart', 'scroll', 'click', 'wheel'];
      events.forEach(ev => {
        window.addEventListener(ev, resetHandler, { passive: true });
      });
    },

    startInactivityTimer() {
      if (this.inactivityTimer) clearTimeout(this.inactivityTimer);
      this.inactivityTimer = setTimeout(() => {
        this.showWarning();
      }, this.INACTIVITY_TIME_MS);
    },

    showWarning() {
      this.isWarningShown = true;
      this.remainingSeconds = this.COUNTDOWN_SECONDS;

      let modal = document.getElementById('inactivity-warning-modal');
      let box = document.getElementById('inactivity-modal-box');
      let timerSpan = document.getElementById('inactivity-countdown-timer');

      // If modal DOM element is not already present, inject dynamic fallback
      if (!modal) {
        const div = document.createElement('div');
        div.innerHTML = `
          <div id="inactivity-warning-modal" 
               style="z-index: 999999 !important; backdrop-filter: blur(12px) !important; -webkit-backdrop-filter: blur(12px) !important; background-color: rgba(15, 23, 42, 0.75) !important;"
               class="fixed inset-0 z-[99999] flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-200" 
               role="dialog" 
               aria-modal="true">
            <div id="inactivity-modal-box" 
                 style="z-index: 1000000 !important; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45) !important;"
                 class="bg-white rounded-2xl shadow-2xl border border-slate-200/90 max-w-md w-full p-6 text-center transform scale-95 transition-all duration-200 relative overflow-hidden">
              <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-amber-100 text-amber-600 ring-8 ring-amber-50 shadow-xs">
                <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
              </div>
              <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-1">Session Inactivity Warning</h3>
              <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-4">You have been inactive for nearly 5 minutes. For your security, you will be automatically signed out in:</p>
              <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-rose-50 border-2 border-rose-500 text-rose-600 font-black text-2xl mb-6 shadow-inner tracking-tight">
                <span id="inactivity-countdown-timer">30</span>s
              </div>
              <div class="flex items-center gap-3">
                <button type="button" onclick="APP.inactivityManager.logoutNow()" class="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition cursor-pointer">Sign Out Now</button>
                <button type="button" onclick="APP.inactivityManager.stayLoggedIn()" class="flex-1 py-2.5 px-4 rounded-xl font-semibold text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/20">I'm Still Here</button>
              </div>
            </div>
          </div>
        `;
        document.body.appendChild(div.firstElementChild);
        modal = document.getElementById('inactivity-warning-modal');
        box = document.getElementById('inactivity-modal-box');
        timerSpan = document.getElementById('inactivity-countdown-timer');
      }

      if (timerSpan) timerSpan.textContent = this.remainingSeconds;

      if (modal) {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
          modal.classList.remove('opacity-0');
          if (box) {
            box.classList.remove('scale-95');
            box.classList.add('scale-100');
          }
        });
      }

      // Start 10-second countdown interval
      if (this.countdownInterval) clearInterval(this.countdownInterval);
      this.countdownInterval = setInterval(() => {
        this.remainingSeconds--;
        const timerEl = document.getElementById('inactivity-countdown-timer');
        if (timerEl) timerEl.textContent = this.remainingSeconds;

        if (this.remainingSeconds <= 0) {
          clearInterval(this.countdownInterval);
          this.logoutNow();
        }
      }, 1000);
    },

    stayLoggedIn() {
      this.isWarningShown = false;
      if (this.countdownInterval) clearInterval(this.countdownInterval);

      const modal = document.getElementById('inactivity-warning-modal');
      const box = document.getElementById('inactivity-modal-box');
      if (modal) {
        modal.classList.add('opacity-0');
        if (box) {
          box.classList.remove('scale-100');
          box.classList.add('scale-95');
        }
        setTimeout(() => {
          modal.classList.add('hidden');
        }, 200);
      }

      // Send a lightweight ping to keep backend session alive
      try {
        const logoutBtn = document.querySelector('a[href*="logout"]');
        let basePath = '';
        if (logoutBtn && logoutBtn.getAttribute('href')) {
          const href = logoutBtn.getAttribute('href');
          const idx = href.indexOf('/logout');
          if (idx !== -1) basePath = href.substring(0, idx);
        }
        fetch(basePath + '/api/auth/me', {
          headers: { 'Accept': 'application/json' }
        }).catch(() => {});
      } catch (e) {}

      if (typeof APP !== 'undefined' && APP.toast && APP.toast.success) {
        APP.toast.success('Session extended. Welcome back!');
      }

      this.lastActivityTime = Date.now();
      this.startInactivityTimer();
    },

    logoutNow() {
      if (this.countdownInterval) clearInterval(this.countdownInterval);
      if (this.inactivityTimer) clearTimeout(this.inactivityTimer);

      if (typeof APP !== 'undefined' && APP.showLoadingScreen) {
        APP.showLoadingScreen({
          title: 'Session Inactivity',
          subtitle: 'Signing you out due to inactivity...'
        });
      }

      const logoutBtn = document.querySelector('a[href*="logout"]');
      let logoutUrl = '/logout';
      if (logoutBtn && logoutBtn.getAttribute('href')) {
        logoutUrl = logoutBtn.getAttribute('href');
      } else {
        const themeLink = document.querySelector('link[rel="stylesheet"][href*="Project_theme.css"]');
        if (themeLink && themeLink.getAttribute('href')) {
          const href = themeLink.getAttribute('href');
          const idx = href.indexOf('/Project_theme.css');
          if (idx !== -1) {
            logoutUrl = href.substring(0, idx) + '/logout';
          }
        }
      }

      setTimeout(() => {
        window.location.href = logoutUrl;
      }, 300);
    }
  },

  /* ── Cross-Device Concurrent Login Approval Manager ──────── */
  deviceApprovalManager: {
    POLL_INTERVAL_MS: 1200,
    pollTimer: null,
    countdownInterval: null,
    remainingSeconds: 300,
    activeRequestId: null,
    isModalOpen: false,
    isInitialized: false,

    formatTime(sec) {
      const s = Math.max(0, parseInt(sec, 10) || 0);
      const mins = Math.floor(s / 60);
      const rem = s % 60;
      return `${mins}:${rem < 10 ? '0' : ''}${rem}`;
    },

    init() {
      if (this.isInitialized) return;

      // Do not run on login or reset pages
      const isAuthPage = document.getElementById('credentials-form') || 
                         document.getElementById('auth-flow-title') ||
                         document.getElementById('reset-email-input');
      if (isAuthPage) return;

      this.isInitialized = true;
      this.startPolling();

      // Listen for tab focus / visibility for instantaneous response
      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') {
          this.checkPending();
        }
      });
      window.addEventListener('focus', () => {
        this.checkPending();
      });
    },

    startPolling() {
      if (this.pollTimer) clearInterval(this.pollTimer);
      this.checkPending();
      this.pollTimer = setInterval(() => {
        if (document.visibilityState === 'visible') {
          this.checkPending();
        }
      }, this.POLL_INTERVAL_MS);
    },

    async checkPending() {
      try {
        const basePath = this.getBasePath();
        const res = await fetch(basePath + '/api/auth/check-pending-approval', {
          headers: { 'Accept': 'application/json', 'Cache-Control': 'no-cache' }
        });
        if (!res.ok) return;
        const data = await res.json();

        if (data.status === 'session_replaced') {
          // Session was replaced by an approved login on another device
          if (this.pollTimer) clearInterval(this.pollTimer);
          if (typeof APP !== 'undefined' && APP.showLoadingScreen) {
            APP.showLoadingScreen({
              title: 'Signed Out',
              subtitle: data.message || 'Another device has been authorized to sign into your account.'
            });
          }
          setTimeout(() => {
            window.location.href = basePath + '/login?logged_out=1&msg=' + encodeURIComponent(data.message || 'Session replaced by new login.');
          }, 600);
          return;
        }

        if (data.status === 'has_pending_request' && data.request) {
          this.showModal(data.request);
        } else if (data.status === 'no_pending_request' && this.isModalOpen) {
          this.hideModal();
        }
      } catch (e) {}
    },

    showModal(req) {
      if (this.isModalOpen && this.activeRequestId === req.request_id) return;
      this.activeRequestId = req.request_id;
      this.isModalOpen = true;
      this.remainingSeconds = req.remaining_seconds || 300;
      this.resetButtons();

      const modal = document.getElementById('device-approval-modal');
      const box = document.getElementById('device-approval-box');
      const deviceName = document.getElementById('device-approval-device-name');
      const ipEl = document.getElementById('device-approval-ip');
      const timeEl = document.getElementById('device-approval-time');
      const countdownEl = document.getElementById('device-approval-countdown');
      const reqIdInput = document.getElementById('device-approval-request-id');

      if (deviceName) deviceName.textContent = req.device_info || 'Unknown Device';
      if (ipEl) ipEl.textContent = req.ip_address || '127.0.0.1';
      if (timeEl) timeEl.textContent = req.created_at || 'Just now';
      if (countdownEl) countdownEl.textContent = this.formatTime(this.remainingSeconds);
      if (reqIdInput) reqIdInput.value = req.request_id;

      if (modal) {
        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
          modal.classList.remove('opacity-0');
          if (box) {
            box.classList.remove('scale-95');
            box.classList.add('scale-100');
          }
        });
      }

      if (this.countdownInterval) clearInterval(this.countdownInterval);
      this.countdownInterval = setInterval(() => {
        this.remainingSeconds--;
        if (countdownEl) countdownEl.textContent = this.formatTime(this.remainingSeconds);
        if (this.remainingSeconds <= 0) {
          clearInterval(this.countdownInterval);
          this.hideModal();
        }
      }, 1000);
    },

    hideModal() {
      this.isModalOpen = false;
      this.activeRequestId = null;
      if (this.countdownInterval) clearInterval(this.countdownInterval);
      this.resetButtons();

      const modal = document.getElementById('device-approval-modal');
      const box = document.getElementById('device-approval-box');
      if (modal) {
        modal.classList.add('opacity-0');
        if (box) {
          box.classList.remove('scale-100');
          box.classList.add('scale-95');
        }
        setTimeout(() => {
          modal.classList.add('hidden');
          this.resetButtons();
        }, 200);
      }
    },

    resetButtons() {
      const approveBtn = document.getElementById('device-approval-approve-btn');
      const denyBtn = document.getElementById('device-approval-deny-btn');
      const approveIcon = document.getElementById('device-approval-approve-icon');
      const approveSpinner = document.getElementById('device-approval-approve-spinner');
      const approveText = document.getElementById('device-approval-approve-text');
      const denyIcon = document.getElementById('device-approval-deny-icon');
      const denySpinner = document.getElementById('device-approval-deny-spinner');
      const denyText = document.getElementById('device-approval-deny-text');

      if (approveBtn) {
        approveBtn.disabled = false;
        approveBtn.classList.remove('opacity-60', 'pointer-events-none');
      }
      if (denyBtn) {
        denyBtn.disabled = false;
        denyBtn.classList.remove('opacity-60', 'pointer-events-none');
      }
      if (approveIcon) approveIcon.classList.remove('hidden');
      if (approveSpinner) approveSpinner.classList.add('hidden');
      if (approveText) approveText.textContent = "Yes, That's Me";

      if (denyIcon) denyIcon.classList.remove('hidden');
      if (denySpinner) denySpinner.classList.add('hidden');
      if (denyText) denyText.textContent = "No, Deny Access";
    },

    async respond(action) {
      const reqIdInput = document.getElementById('device-approval-request-id');
      const requestId = this.activeRequestId || (reqIdInput ? reqIdInput.value : '');
      if (!requestId) return;

      const approveBtn = document.getElementById('device-approval-approve-btn');
      const denyBtn = document.getElementById('device-approval-deny-btn');
      const approveIcon = document.getElementById('device-approval-approve-icon');
      const approveSpinner = document.getElementById('device-approval-approve-spinner');
      const approveText = document.getElementById('device-approval-approve-text');
      const denyIcon = document.getElementById('device-approval-deny-icon');
      const denySpinner = document.getElementById('device-approval-deny-spinner');
      const denyText = document.getElementById('device-approval-deny-text');

      // Set loading state on the selected action button and disable both
      if (approveBtn) {
        approveBtn.disabled = true;
        approveBtn.classList.add('opacity-60', 'pointer-events-none');
      }
      if (denyBtn) {
        denyBtn.disabled = true;
        denyBtn.classList.add('opacity-60', 'pointer-events-none');
      }

      if (action === 'approve') {
        if (approveIcon) approveIcon.classList.add('hidden');
        if (approveSpinner) approveSpinner.classList.remove('hidden');
        if (approveText) approveText.textContent = 'Authorizing...';
      } else {
        if (denyIcon) denyIcon.classList.add('hidden');
        if (denySpinner) denySpinner.classList.remove('hidden');
        if (denyText) denyText.textContent = 'Denying Access...';
      }

      try {
        const basePath = this.getBasePath();
        const res = await fetch(basePath + '/api/auth/respond-login-request', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({
            request_id: requestId,
            action: action
          })
        });

        const data = await res.json();

        if (action === 'approve' || data.status === 'approved_and_logged_out') {
          this.hideModal();
          if (typeof APP !== 'undefined' && APP.showLoadingScreen) {
            APP.showLoadingScreen({
              title: 'Device Authorized',
              subtitle: 'Signing out this device...'
            });
          }
          setTimeout(() => {
            window.location.href = data.redirect_url || (basePath + '/login?logged_out=1');
          }, 300);
        } else {
          this.hideModal();
          if (typeof APP !== 'undefined' && APP.toast && APP.toast.warning) {
            APP.toast.warning('Sign-in attempt blocked. Your current session remains secure.');
          } else if (typeof toast !== 'undefined' && toast.warning) {
            toast.warning('Sign-in attempt blocked. Your current session remains secure.');
          }
        }
      } catch (e) {
        console.error('Error responding to login request:', e);
        this.hideModal();
      } finally {
        this.resetButtons();
      }
    },

    getBasePath() {
      const logoutBtn = document.querySelector('a[href*="logout"]');
      if (logoutBtn && logoutBtn.getAttribute('href')) {
        const href = logoutBtn.getAttribute('href');
        const idx = href.indexOf('/logout');
        if (idx !== -1) return href.substring(0, idx);
      }
      const themeLink = document.querySelector('link[rel="stylesheet"][href*="Project_theme.css"]');
      if (themeLink && themeLink.getAttribute('href')) {
        const href = themeLink.getAttribute('href');
        const idx = href.indexOf('/Project_theme.css');
        if (idx !== -1) return href.substring(0, idx);
      }
      return '';
    }
  }
};

/* ── Close dropdowns on outside click ───────────────────────── */
document.addEventListener('click', function(e) {
  const notifBell = document.getElementById('notif-bell');
  const notifDropdown = document.getElementById('notif-dropdown');
  const userBtn = document.getElementById('user-menu-btn');
  const userDropdown = document.getElementById('user-dropdown');

  if (notifDropdown && notifBell && !notifBell.contains(e.target) && !notifDropdown.contains(e.target)) {
    notifDropdown.classList.add('hidden');
  }
  if (userDropdown && userBtn && !userBtn.contains(e.target) && !userDropdown.contains(e.target)) {
    userDropdown.classList.add('hidden');
  }
});

/* ── Sonner Toast API Proxy Methods ──────────────────────────── */
if (typeof APP !== 'undefined' && APP.toast) {
  APP.toast.success = function(msg, opts) {
    return window.toast ? window.toast.success(msg, opts) : APP.toast(msg, 'success', opts);
  };
  APP.toast.error = function(msg, opts) {
    return window.toast ? window.toast.error(msg, opts) : APP.toast(msg, 'error', opts);
  };
  APP.toast.warning = function(msg, opts) {
    return window.toast ? window.toast.warning(msg, opts) : APP.toast(msg, 'warning', opts);
  };
  APP.toast.info = function(msg, opts) {
    return window.toast ? window.toast.info(msg, opts) : APP.toast(msg, 'info', opts);
  };
  APP.toast.message = function(title, desc, opts) {
    return window.toast && window.toast.message ? window.toast.message(title, desc, opts) : APP.toast(title, 'info', opts);
  };
  APP.toast.promise = function(promise, data, opts) {
    return window.toast && window.toast.promise ? window.toast.promise(promise, data, opts) : null;
  };
}

/* ── Auto-open manual entry modal and initialize Inactivity & Device Approval Managers ───── */
document.addEventListener('DOMContentLoaded', function() {
  if (typeof APP !== 'undefined' && APP.inactivityManager && typeof APP.inactivityManager.init === 'function') {
    APP.inactivityManager.init();
  }

  if (typeof APP !== 'undefined' && APP.deviceApprovalManager && typeof APP.deviceApprovalManager.init === 'function') {
    APP.deviceApprovalManager.init();
  }

  if (new URLSearchParams(window.location.search).get('action') === 'manual-entry') {
    setTimeout(function() {
      if (typeof APP !== 'undefined' && APP.openManualEntryModal) {
        APP.openManualEntryModal();
      }
    }, 100);
  }
});


/* ── Declarative [data-confirm] Attribute Delegation ───────── */
document.addEventListener('click', async function(e) {
  const trigger = e.target.closest('[data-confirm]');
  if (!trigger) return;

  // Prevent immediate default action (e.g. form submit or link navigation)
  e.preventDefault();
  e.stopPropagation();

  const message = trigger.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
  const title = trigger.getAttribute('data-confirm-title') || 'Confirm Action';
  const type = trigger.getAttribute('data-confirm-type') || 'danger';
  const confirmText = trigger.getAttribute('data-confirm-btn') || 'Confirm';
  const cancelText = trigger.getAttribute('data-confirm-cancel') || 'Cancel';

  const confirmed = await APP.confirm({
    title,
    message,
    type,
    confirmText,
    cancelText
  });

  if (confirmed) {
    if (trigger.tagName === 'A' && trigger.href) {
      window.location.href = trigger.href;
    } else if (trigger.form) {
      trigger.form.submit();
    } else {
      // Re-trigger click without data-confirm or call custom event
      trigger.removeAttribute('data-confirm');
      trigger.click();
      trigger.setAttribute('data-confirm', message);
    }
  }
}, true);

/* ── Modern Button Ripple Effect & Global Handlers ───────── */
document.addEventListener('click', function(e) {
  const btn = e.target.closest('.btn');
  if (!btn || btn.disabled || btn.classList.contains('is-loading')) return;

  const rect = btn.getBoundingClientRect();
  const circle = document.createElement('span');
  const diameter = Math.max(rect.width, rect.height);
  const radius = diameter / 2;

  circle.style.width = circle.style.height = `${diameter}px`;
  circle.style.left = `${e.clientX - rect.left - radius}px`;
  circle.style.top = `${e.clientY - rect.top - radius}px`;
  circle.classList.add('btn-ripple-wave');

  const existingRipple = btn.querySelector('.btn-ripple-wave');
  if (existingRipple) {
    existingRipple.remove();
  }

  btn.appendChild(circle);
  setTimeout(() => {
    if (circle.parentNode === btn) {
      circle.remove();
    }
  }, 600);
});

// Expose globally for inline and view scripts
window.setButtonLoading = function(btn, isLoading, loadingText) {
  if (window.APP && typeof APP.setLoading === 'function') {
    APP.setLoading(btn, isLoading, loadingText);
  }
};
window.showLoadingScreen = function(opts) {
  if (window.APP && typeof APP.showLoadingScreen === 'function') {
    APP.showLoadingScreen(opts);
  }
};
window.hideLoadingScreen = function() {
  if (window.APP && typeof APP.hideLoadingScreen === 'function') {
    APP.hideLoadingScreen();
  }
};
