<?php
$page_title = 'Manual Attendance Entry';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

// Resolve teacher
$teacherId = $_SESSION['teacher_id'] ?? $_SESSION['user']['user_id'] ?? 2;
$rosterStudents = [];
try {
    $db = Database::getConnection();
    $rStmt = $db->prepare("
        SELECT cr.roster_id, cr.student_id, cr.first_name, cr.last_name,
               cr.section, cr.course_code, cr.course_title,
               COALESCE(u.student_id, '230110001') AS student_number
        FROM class_roster cr
        LEFT JOIN users u ON u.user_id = cr.student_id
        WHERE cr.teacher_id = ?
        ORDER BY cr.last_name ASC, cr.first_name ASC
    ");
    $rStmt->execute([$teacherId]);
    $rosterStudents = $rStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

include __DIR__ . '/../partials/header.php';
?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Back Navigation & Title -->
        <div class="mb-6">
          <a href="<?php echo url('attendance/daily'); ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-600 hover:text-teal-700 mb-2 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            Back to Daily Attendance Log
          </a>
          <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Manual Attendance Entry</h1>
          <p class="text-sm mt-1" style="color:var(--color-text-secondary)">Record or override attendance with supporting documentation or physical hall pass verification.</p>
        </div>

        <!-- Manual Entry Form Card -->
        <div class="max-w-2xl bg-white rounded-xl shadow-card p-6 md:p-8 border border-gray-100">
          <form id="standalone-manual-form" onsubmit="submitStandaloneManualEntry(event)">
            <div class="space-y-5">
              <!-- Student Selection with Avatar Preview -->
              <div>
                <label for="page-manual-student" class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--color-text-secondary)">Student</label>
                <div class="flex items-center gap-3 p-3 rounded-lg border" style="background:var(--color-surface); border-color:var(--color-border)">
                  <div id="page-student-avatar" class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm text-white shrink-0 shadow-sm" style="background:var(--color-text-muted)">
                    ?
                  </div>
                  <div class="flex-1 min-w-0">
                    <select id="page-manual-student" class="form-input form-select text-sm py-2" required onchange="updatePageStudentAvatar(this)">
                      <option value="">Select or search student...</option>
                      <?php foreach ($rosterStudents as $st): ?>
                        <option value="<?= (int)$st['student_id'] ?>" 
                                data-name="<?= htmlspecialchars($st['first_name'] . ' ' . $st['last_name'], ENT_QUOTES, 'UTF-8') ?>" 
                                data-number="<?= htmlspecialchars((string)$st['student_number'], ENT_QUOTES, 'UTF-8') ?>" 
                                data-section="<?= htmlspecialchars($st['section'], ENT_QUOTES, 'UTF-8') ?>"
                                data-subject="<?= htmlspecialchars($st['course_title'], ENT_QUOTES, 'UTF-8') ?>">
                          <?= htmlspecialchars($st['last_name'] . ', ' . $st['first_name'] . ' · ' . $st['student_number'] . ' (' . $st['section'] . ')', ENT_QUOTES, 'UTF-8') ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>

              <!-- Date & Status -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="page-manual-date" class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--color-text-secondary)">Date</label>
                  <input type="date" id="page-manual-date" class="form-input text-sm" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div>
                  <label for="page-manual-status" class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--color-text-secondary)">Attendance Status</label>
                  <select id="page-manual-status" class="form-input form-select text-sm">
                    <option value="present">Present</option>
                    <option value="tardy">Tardy</option>
                    <option value="absent">Absent</option>
                    <option value="excused">Excused</option>
                  </select>
                </div>
              </div>

              <!-- Entry & Exit Time -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="page-manual-entry" class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--color-text-secondary)">Entry Time</label>
                  <input type="time" id="page-manual-entry" class="form-input text-sm" value="08:00">
                </div>
                <div>
                  <label for="page-manual-exit" class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--color-text-secondary)">Exit Time (Optional)</label>
                  <input type="time" id="page-manual-exit" class="form-input text-sm">
                </div>
              </div>

              <!-- Supporting Document / Image Attachment -->
              <div>
                <label class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--color-text-secondary)">Supporting Document / Image (Proof / Excuse Slip / ID)</label>
                <div class="border-2 border-dashed rounded-lg p-5 text-center cursor-pointer hover:bg-teal-50/20 transition-all" style="border-color:var(--color-border); background:var(--color-surface)" id="page-drop-zone" onclick="document.getElementById('page-manual-file').click()">
                  <div class="flex items-center justify-center gap-2 mb-1.5">
                    <svg class="w-6 h-6" style="color:var(--color-teal-500)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span class="text-sm font-semibold" style="color:var(--color-text-primary)">Attach or Import Document / Photo</span>
                  </div>
                  <p class="text-xs" style="color:var(--color-text-muted)">Click to browse or drag and drop physical pass, medical note, or ID snapshot (PDF, JPG, PNG · max 5MB)</p>
                  <input type="file" class="hidden" id="page-manual-file" accept=".pdf,.jpg,.jpeg,.png" onchange="handlePageFileSelect(event)">
                </div>
                <div id="page-file-preview" class="hidden mt-2 p-3 rounded-lg flex items-center gap-3 border" style="background:var(--color-teal-100); border-color:var(--color-teal-300)">
                  <svg class="w-4 h-4 shrink-0" style="color:var(--color-teal-500)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                  <span class="text-sm font-medium truncate flex-1" style="color:var(--color-teal-500)" id="page-file-name"></span>
                  <button type="button" class="text-xs font-semibold px-2.5 py-1 rounded text-red-600 hover:bg-red-50 inline-flex items-center gap-1" onclick="removePageFile()">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    Remove
                  </button>
                </div>
              </div>

              <!-- Administrative Notes -->
              <div>
                <label for="page-manual-notes" class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block" style="color:var(--color-text-secondary)">Administrative Notes / Reason for Override</label>
                <textarea id="page-manual-notes" class="form-input text-sm" rows="3" placeholder="e.g., RFID tag damaged or forgotten, verified by supervisor with ID pass..."></textarea>
              </div>

              <!-- Actions -->
              <div class="flex items-center justify-end gap-3 pt-3 border-t" style="border-color:var(--color-border)">
                <a href="<?php echo url('attendance/daily'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Attendance Record</button>
              </div>
            </div>
          </form>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script>
    function updatePageStudentAvatar(selectElem) {
      const avatar = document.getElementById('page-student-avatar');
      if (!avatar) return;
      const selected = selectElem.options[selectElem.selectedIndex];
      if (selected && selected.value) {
        const name = selected.getAttribute('data-name') || '';
        const parts = name.trim().split(/\s+/);
        let initials = '?';
        if (parts.length >= 2) {
          initials = (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        } else if (parts.length === 1 && parts[0].length > 0) {
          initials = parts[0][0].toUpperCase();
        }
        avatar.textContent = initials;
        avatar.style.background = 'var(--color-teal-500)';
      } else {
        avatar.textContent = '?';
        avatar.style.background = 'var(--color-text-muted)';
      }
    }

    function handlePageFileSelect(e) {
      const file = e.target.files && e.target.files[0];
      if (!file) return;
      const preview = document.getElementById('page-file-preview');
      const fileName = document.getElementById('page-file-name');
      const dropZone = document.getElementById('page-drop-zone');
      if (preview && fileName) {
        const sizeKB = Math.round(file.size / 1024);
        fileName.textContent = `${file.name} (${sizeKB} KB)`;
        preview.classList.remove('hidden');
      }
      if (dropZone) {
        dropZone.classList.add('hidden');
      }
    }

    function removePageFile() {
      const preview = document.getElementById('page-file-preview');
      const dropZone = document.getElementById('page-drop-zone');
      const fileInput = document.getElementById('page-manual-file');
      if (preview) preview.classList.add('hidden');
      if (dropZone) dropZone.classList.remove('hidden');
      if (fileInput) fileInput.value = '';
    }

    async function submitStandaloneManualEntry(e) {
      if (e) e.preventDefault();
      const select = document.getElementById('page-manual-student');
      const studentId = select ? select.value : '';
      const selectedOpt = select ? select.options[select.selectedIndex] : null;
      const subject = selectedOpt ? selectedOpt.getAttribute('data-subject') : '';
      const dateVal = document.getElementById('page-manual-date') ? document.getElementById('page-manual-date').value : '';
      const statusVal = document.getElementById('page-manual-status') ? document.getElementById('page-manual-status').value : 'present';
      const timeVal = document.getElementById('page-manual-entry') ? document.getElementById('page-manual-entry').value : '';
      const notesVal = document.getElementById('page-manual-notes') ? document.getElementById('page-manual-notes').value : '';

      if (!studentId) {
        APP.showToast('Please select a student from your roster.', 'error');
        return;
      }

      const submitBtn = document.querySelector('#standalone-manual-form button[type="submit"]');
      if (submitBtn && window.APP && typeof APP.setLoading === 'function') {
        APP.setLoading(submitBtn, true, 'Saving Attendance Record...');
      } else if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';
      }

      try {
        const endpoint = (typeof window.url === 'function')
          ? window.url('api/attendance/manual-entry')
          : '/api/attendance/manual-entry';

        const resp = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
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
          APP.showToast(res.message || 'Manual attendance record saved successfully.', 'success');
          setTimeout(() => {
            window.location.href = (typeof window.url === 'function') ? window.url('attendance/daily') : '/attendance/daily';
          }, 1000);
        } else {
          APP.showToast(res.message || 'Failed to save record.', 'error');
          if (submitBtn && window.APP && typeof APP.setLoading === 'function') {
            APP.setLoading(submitBtn, false);
          } else if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Attendance Record';
          }
        }
      } catch (err) {
        APP.showToast('Network error while saving record.', 'error');
        if (submitBtn && window.APP && typeof APP.setLoading === 'function') {
          APP.setLoading(submitBtn, false);
        } else if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = 'Save Attendance Record';
        }
      }
    }

    APP.highlightNav('attendance');
  </script>
</body>
</html>
