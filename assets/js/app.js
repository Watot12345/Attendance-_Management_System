/**
 * AI-Supported Attendance Management System
 * Global JS — app.js
 * Sidebar toggle, toast notifications, modal, confirm dialog,
 * tab switching, notification dropdown, user menu
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

  /* ── Toast Notifications ──────────────────────────────────── */
  toast(message, type = 'info', duration = 4000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const icons = {
      success: '✓',
      error: '✗',
      warning: '⚠',
      info: 'ℹ'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <span class="font-semibold text-base">${icons[type] || 'ℹ'}</span>
      <span class="flex-1">${message}</span>
      <button onclick="this.parentElement.remove()" class="ml-2 opacity-60 hover:opacity-100">&times;</button>
    `;
    container.appendChild(toast);

    setTimeout(() => {
      if (toast.parentElement) {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(8px)';
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        setTimeout(() => toast.remove(), 300);
      }
    }, duration);
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

  /* ── Confirm Dialog ───────────────────────────────────────── */
  confirm(message, onConfirm) {
    APP.openModal(
      'Confirm Action',
      `<p class="text-sm" style="color:var(--color-text-secondary)">${message}</p>`,
      `<button class="btn btn-secondary" onclick="APP.closeModal()">Cancel</button>
       <button class="btn btn-danger" onclick="APP.closeModal(); (${onConfirm})()">Confirm</button>`
    );
  },

  /* ── Manual Entry Modal ───────────────────────────────────── */
  openManualEntryModal() {
    const today = new Date().toISOString().split('T')[0];
    const bodyHTML = `
      <form id="manual-entry-modal-form" onsubmit="event.preventDefault(); APP.closeModal(); APP.showToast('Manual attendance record saved successfully.', 'success');">
        <div class="space-y-3.5">
          <!-- Student Selector with Avatar Preview -->
          <div>
            <label for="modal-manual-student" class="form-label text-xs mb-1 block">Student / Borrower</label>
            <div class="flex items-center gap-2.5 p-2 rounded-lg border" style="background:var(--color-surface); border-color:var(--color-border)">
              <div id="modal-student-avatar" class="w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs text-white shrink-0 shadow-sm" style="background:var(--color-text-muted)">
                ?
              </div>
              <div class="flex-1 min-w-0">
                <select id="modal-manual-student" class="form-input form-select text-xs py-1.5" required onchange="APP._updateManualStudentAvatar(this)">
                  <option value="">Select or search student...</option>
                  <option value="JD" data-name="Juan Dela Cruz" data-id="BCP-001" data-grade="Grade 7 - Sec A">Dela Cruz, Juan · BCP-001 (Grade 7 - Sec A)</option>
                  <option value="MS" data-name="Maria Santos" data-id="BCP-002" data-grade="Grade 7 - Sec A">Santos, Maria · BCP-002 (Grade 7 - Sec A)</option>
                  <option value="PR" data-name="Pedro Reyes" data-id="BCP-003" data-grade="Grade 8 - Sec B">Reyes, Pedro · BCP-003 (Grade 8 - Sec B)</option>
                  <option value="AM" data-name="Alex Moreno" data-id="BCP-019" data-grade="Grade 9 - Sec A">Moreno, Alex · BCP-019 (Grade 9 - Sec A)</option>
                  <option value="AG" data-name="Ana Garcia" data-id="BCP-005" data-grade="Grade 7 - Sec B">Garcia, Ana · BCP-005 (Grade 7 - Sec B)</option>
                  <option value="JL" data-name="Jenny Lim" data-id="BCP-008" data-grade="Grade 8 - Sec B">Lim, Jenny · BCP-008 (Grade 8 - Sec B)</option>
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
                <option value="excused">Excused</option>
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
            <textarea id="modal-manual-notes" class="form-input text-xs" rows="2" placeholder="e.g., RFID card unreadable, physically verified by library staff..."></textarea>
          </div>
        </div>
      </form>
    `;

    const footerHTML = `
      <button type="button" class="btn btn-secondary btn-sm" onclick="APP.closeModal()">Cancel</button>
      <button type="submit" form="manual-entry-modal-form" class="btn btn-primary btn-sm">Save Record</button>
    `;

    APP.openModal('Manual Attendance Entry', bodyHTML, footerHTML);
  },

  _updateManualStudentAvatar(selectElem) {
    const avatar = document.getElementById('modal-student-avatar');
    if (!avatar) return;
    const selected = selectElem.options[selectElem.selectedIndex];
    if (selected && selected.value) {
      avatar.textContent = selected.value;
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

  /* ── Highlight Active Nav ─────────────────────────────────── */
  highlightNav(pageId) {
    document.querySelectorAll('#sidebar .nav-item').forEach(item => {
      item.classList.remove('active');
      if (item.dataset.page === pageId) {
        item.classList.add('active');
      }
    });
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

/* ── Auto-open manual entry modal if ?action=manual-entry ───── */
document.addEventListener('DOMContentLoaded', function() {
  if (new URLSearchParams(window.location.search).get('action') === 'manual-entry') {
    setTimeout(function() {
      if (typeof APP !== 'undefined' && APP.openManualEntryModal) {
        APP.openManualEntryModal();
      }
    }, 100);
  }
});

