<?php $page_title = 'Attendance History & Audit Logs'; ?>
<?php include dirname(__DIR__) . '/partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include dirname(__DIR__) . '/partials/navbar.php'; ?>

      <!-- Content Area -->
      <main class="flex-1 p-6 bg-surface">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <div>
            <h1 class="text-2xl font-bold text-text-primary">Attendance History &amp; Audit Logs</h1>
            <p class="text-sm text-text-secondary mt-0.5">Review session records, export reports, and perform authorized manual corrections with audit logging.</p>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" class="btn btn-secondary" onclick="APP.showToast('Exported Attendance Excel', 'success')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
              Export Excel
            </button>
          </div>
        </div>

        <!-- Filter Row -->
        <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 mb-6 flex flex-wrap items-center gap-4">
          <div class="flex-1 min-w-[200px]">
            <label class="text-xs font-semibold uppercase text-text-muted mb-1 block">Search Student</label>
            <input type="text" class="form-input text-sm" placeholder="Search by student number or name...">
          </div>
          <div class="w-48">
            <label class="text-xs font-semibold uppercase text-text-muted mb-1 block">Class / Section</label>
            <select class="form-input form-select text-sm">
              <option>BSIT 3-A · IT311</option>
              <option>BSIT 3-B · IT312</option>
              <option>BSCS 2-A · CS211</option>
            </select>
          </div>
          <div class="w-40">
            <label class="text-xs font-semibold uppercase text-text-muted mb-1 block">Status</label>
            <select class="form-input form-select text-sm">
              <option>All Statuses</option>
              <option>Present</option>
              <option>Late</option>
              <option>Absent</option>
              <option>Excused</option>
            </select>
          </div>
        </div>

        <!-- Attendance Records Table (Spec Section 21) -->
        <div class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden mb-8">
          <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <h3 class="text-base font-bold text-text-primary">Session Attendance Log — Sep 7, 2026</h3>
            <span class="text-xs text-text-muted">6 total records</span>
          </div>

          <div class="overflow-x-auto">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Student Number</th>
                  <th>Student Name</th>
                  <th>Status</th>
                  <th>Time In</th>
                  <th>Verification</th>
                  <th>Remarks / Audit</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="font-mono text-xs font-semibold text-slate-700">2026-00123</td>
                  <td class="font-semibold text-text-primary">Juan Dela Cruz</td>
                  <td><span class="badge badge-present">● Present</span></td>
                  <td>08:04:12 AM</td>
                  <td>Dynamic QR</td>
                  <td class="text-xs text-text-muted">On time</td>
                  <td>
                    <button class="btn btn-ghost btn-sm text-xs" onclick="openCorrectionModal('2026-00123', 'Juan Dela Cruz', 'present')">Correct</button>
                  </td>
                </tr>
                <tr>
                  <td class="font-mono text-xs font-semibold text-slate-700">2026-00124</td>
                  <td class="font-semibold text-text-primary">Maria Santos</td>
                  <td><span class="badge badge-present">● Present</span></td>
                  <td>08:07:33 AM</td>
                  <td>Dynamic QR</td>
                  <td class="text-xs text-text-muted">On time</td>
                  <td>
                    <button class="btn btn-ghost btn-sm text-xs" onclick="openCorrectionModal('2026-00124', 'Maria Santos', 'present')">Correct</button>
                  </td>
                </tr>
                <tr>
                  <td class="font-mono text-xs font-semibold text-slate-700">2026-00127</td>
                  <td class="font-semibold text-text-primary">Carlo Villanueva</td>
                  <td><span class="badge badge-tardy">● Tardy (+22m)</span></td>
                  <td>08:22:45 AM</td>
                  <td>Dynamic QR</td>
                  <td class="text-xs text-text-muted">Late check-in</td>
                  <td>
                    <button class="btn btn-ghost btn-sm text-xs" onclick="openCorrectionModal('2026-00127', 'Carlo Villanueva', 'late')">Correct</button>
                  </td>
                </tr>
                <tr class="bg-rose-50/30">
                  <td class="font-mono text-xs font-semibold text-rose-700">2026-00125</td>
                  <td class="font-semibold text-rose-900">Pedro Reyes</td>
                  <td><span class="badge badge-absent"> Absent</span></td>
                  <td>—</td>
                  <td>Auto-Absence</td>
                  <td class="text-xs text-rose-600">Did not scan before session close</td>
                  <td>
                    <button class="btn btn-ghost btn-sm text-xs text-teal-700 font-bold" onclick="openCorrectionModal('2026-00125', 'Pedro Reyes', 'absent')">Correct Status</button>
                  </td>
                </tr>
                <tr class="bg-rose-50/30">
                  <td class="font-mono text-xs font-semibold text-rose-700">2026-00129</td>
                  <td class="font-semibold text-rose-900">Robert Cruz</td>
                  <td><span class="badge badge-absent"> Absent</span></td>
                  <td>—</td>
                  <td>Auto-Absence</td>
                  <td class="text-xs text-rose-600">Did not scan before session close</td>
                  <td>
                    <button class="btn btn-ghost btn-sm text-xs text-teal-700 font-bold" onclick="openCorrectionModal('2026-00129', 'Robert Cruz', 'absent')">Correct Status</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ════ AUDIT LOG SECTION (Spec Section 22) ════ -->
        <div class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-slate-50">
            <div>
              <h3 class="text-base font-bold text-text-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Attendance Correction Audit Trail
              </h3>
              <p class="text-xs text-text-muted mt-0.5">Immutable log of authorized manual overrides and reason justifications</p>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Student Name</th>
                  <th>Modified By</th>
                  <th>Previous Status</th>
                  <th>New Status</th>
                  <th>Reason Justification</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="text-xs text-text-muted font-mono">2026-09-07 09:30:00</td>
                  <td class="font-bold text-slate-800">Carlo Villanueva</td>
                  <td class="text-xs font-semibold text-teal-700">Prof. Bernardo Cruz</td>
                  <td><span class="badge badge-absent">Absent</span></td>
                  <td><span class="badge badge-tardy">Late (+22m)</span></td>
                  <td class="text-xs text-slate-700 italic">"Student presented medical certificate for clinic visit during roll call."</td>
                </tr>
                <tr>
                  <td class="text-xs text-text-muted font-mono">2026-09-05 14:10:22</td>
                  <td class="font-bold text-slate-800">Maria Santos</td>
                  <td class="text-xs font-semibold text-teal-700">Prof. Bernardo Cruz</td>
                  <td><span class="badge badge-absent">Absent</span></td>
                  <td><span class="badge badge-excused">Excused</span></td>
                  <td class="text-xs text-slate-700 italic">"Official Dean's list seminar representative excused slip approved."</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- ════ MANUAL CORRECTION MODAL (Spec Section 21 & 22) ════ -->
  <div id="correction-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
        <h3 class="text-lg font-bold text-text-primary">Manual Attendance Override</h3>
        <button type="button" onclick="closeCorrectionModal()" class="text-text-muted hover:text-slate-800"></button>
      </div>

      <form onsubmit="submitCorrection(event)">
        <div class="mb-3">
          <label class="text-xs font-semibold text-text-muted uppercase">Student</label>
          <p class="text-sm font-bold text-slate-800" id="modal-student-name">Pedro Reyes (2026-00125)</p>
        </div>

        <div class="mb-4">
          <label class="form-label text-xs font-semibold uppercase text-text-secondary mb-1.5 block">
            New Attendance Status <span class="text-red-500">*</span>
          </label>
          <select id="modal-new-status" class="form-input form-select" required>
            <option value="present">Present</option>
            <option value="late">Tardy / Late</option>
            <option value="excused">Excused (Medical / Approved)</option>
            <option value="absent">Absent</option>
          </select>
        </div>

        <div class="mb-5">
          <label class="form-label text-xs font-semibold uppercase text-text-secondary mb-1.5 block">
            Reason for Correction (Required for Audit Log) <span class="text-red-500">*</span>
          </label>
          <textarea id="modal-reason" class="form-input text-sm" rows="3" placeholder="e.g. Phone battery died, verified present in classroom by instructor..." required></textarea>
        </div>

        <div class="flex justify-end gap-3">
          <button type="button" class="btn btn-secondary" onclick="closeCorrectionModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Save &amp; Log Audit Record</button>
        </div>
      </form>
    </div>
  </div>

  <?php include dirname(__DIR__) . '/partials/footer.php'; ?>

  <script>
    function openCorrectionModal(studentNo, studentName, currentStatus) {
      document.getElementById('modal-student-name').textContent = `${studentName} (${studentNo})`;
      document.getElementById('correction-modal').classList.remove('hidden');
    }

    function closeCorrectionModal() {
      document.getElementById('correction-modal').classList.add('hidden');
    }

    function submitCorrection(e) {
      e.preventDefault();
      const reason = document.getElementById('modal-reason').value;
      closeCorrectionModal();
      APP.showToast('Attendance updated and change logged to audit trail.', 'success');
    }
  </script>
</body>
</html>
<script>APP.highlightNav('attendance');</script>
