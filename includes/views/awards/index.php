<?php
$page_title = 'Perfect Attendance Award Tool';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$teacherId = $_SESSION['teacher_id'] ?? $_SESSION['user']['user_id'] ?? 2;
$teacherName = $_SESSION['user']['full_name'] ?? 'Instructor';
$assignedSections = [];

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT DISTINCT section, course_title 
        FROM class_roster 
        WHERE teacher_id = ? 
        ORDER BY section ASC
    ");
    $stmt->execute([$teacherId]);
    $assignedSections = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$bcpLogoPath = dirname(__DIR__, 3) . '/assets/images/bcp-logo.png';
$bcpLogoDataUri = file_exists($bcpLogoPath)
    ? 'data:image/png;base64,' . base64_encode(file_get_contents($bcpLogoPath))
    : '';

require_once dirname(__DIR__) . '/partials/header.php';
?>

<style>
/* Authentic Certificate Styling */
.certificate-frame {
  position: relative;
  background-color: #fffdf9;
  border: 6px double #b45309;
  outline: 2px solid #b45309;
  outline-offset: -10px;
  padding: 36px 32px;
  box-shadow: inset 0 0 24px rgba(180, 83, 9, 0.05);
}

.cert-corner {
  position: absolute;
  width: 24px;
  height: 24px;
  border-color: #b45309;
  pointer-events: none;
}
.cert-corner-tl { top: 14px; left: 14px; border-top: 3px solid #b45309; border-left: 3px solid #b45309; }
.cert-corner-tr { top: 14px; right: 14px; border-top: 3px solid #b45309; border-right: 3px solid #b45309; }
.cert-corner-bl { bottom: 14px; left: 14px; border-bottom: 3px solid #b45309; border-left: 3px solid #b45309; }
.cert-corner-br { bottom: 14px; right: 14px; border-bottom: 3px solid #b45309; border-right: 3px solid #b45309; }

.cert-meta-row {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 12px;
  flex-wrap: wrap;
  margin: 12px auto 16px auto;
}
.cert-meta-pill {
  font-size: 11px;
  background: #f8fafc;
  padding: 5px 14px;
  border-radius: 9999px;
  border: 1px solid #e2e8f0;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  color: #475569;
}
.cert-meta-section {
  background: #fef3c7;
  border-color: #fcd34d;
  color: #92400e;
  font-weight: 700;
  box-shadow: 0 1px 2px rgba(180, 83, 9, 0.1);
}
.cert-meta-section strong {
  color: #78350f;
}

@media print {
  * {
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  body > *:not(.app-modal-root),
  .app-layout,
  .main-content,
  .page-body,
  .no-print,
  .modal-footer,
  .modal-header,
  .app-modal-close {
    display: none !important;
  }
  .app-modal-container,
  .app-modal-body,
  #batch-print-container,
  .certificate-page {
    display: block !important;
    visibility: visible !important;
    position: static !important;
    overflow: visible !important;
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
    width: 100% !important;
    padding: 0 !important;
    margin: 0 !important;
  }
  .certificate-page {
    page-break-after: always !important;
    break-after: page !important;
    margin-bottom: 24px !important;
  }
  .certificate-frame {
    border: 6px double #b45309 !important;
    outline: 2px solid #b45309 !important;
    outline-offset: -10px !important;
    background-color: #fffdf9 !important;
    padding: 36px 28px !important;
  }
  .certificate-logo {
    width: 80px !important;
    height: 80px !important;
    object-fit: contain !important;
    margin: 0 auto 8px auto !important;
    display: block !important;
    background: transparent !important;
  }
}
</style>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Teacher Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Recognition &amp; Honors</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Perfect Attendance Award Tool</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Evaluate your assigned class sections to identify, award, and generate printable certificates for students with 100% attendance records.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="exportAwardsCsv()" class="px-4 py-2.5 rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Export CSV</span>
          </button>
          <button type="button" onclick="generateBatchCertificates()" class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md shadow-amber-600/20 transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Batch Export Certificates (PDF)</span>
          </button>
        </div>
      </div>

      <!-- Criteria Card -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs mb-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4 flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span>Award Criteria &amp; Target Section</span>
            <span id="criteria-badge" class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-bold text-[10px] border border-amber-200">0 Absences · 0 Tardies</span>
          </div>
          <span class="text-xs text-slate-400 font-normal">Instructor: <strong><?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?></strong></span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
          <!-- Class Section Selector -->
          <div>
            <label for="award-section" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Target Assigned Class</label>
            <select id="award-section" onchange="calculateAwards()" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-800 outline-none">
              <option value="ALL" selected>All My Assigned Classes</option>
              <?php foreach ($assignedSections as $sec): ?>
                <option value="<?= htmlspecialchars($sec['section'], ENT_QUOTES, 'UTF-8') ?>">
                  Section <?= htmlspecialchars($sec['section'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars($sec['course_title'], ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Evaluation Period -->
          <div>
            <label for="award-period" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Evaluation Period</label>
            <select id="award-period" onchange="handlePeriodChange(this.value); calculateAwards();" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-800 outline-none">
              <option value="month" selected>Monthly Honors (September 2026)</option>
              <option value="last_month">Previous Month (August 2026)</option>
              <option value="sem">Full 1st Semester (AY 2025–2026)</option>
              <option value="custom">Custom Date Range...</option>
            </select>
          </div>

          <!-- Strictness -->
          <div>
            <label for="award-strictness" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Threshold Standard</label>
            <select id="award-strictness" onchange="updateThresholdBadge(this.value); calculateAwards();" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-800 outline-none">
              <option value="100" selected>100% Flawless Attendance (0 Absences, 0 Lates)</option>
              <option value="98">98%+ High Honors (Max 1 Excused Slip)</option>
            </select>
          </div>
        </div>

        <!-- Hidden Custom Dates Row (only shown when Custom is selected) -->
        <div id="custom-date-row" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100 mb-4">
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Start Date</label>
            <input type="date" id="award-start-date" value="<?= date('Y-m-01') ?>" onchange="calculateAwards()" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium">
          </div>
          <div>
            <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">End Date</label>
            <input type="date" id="award-end-date" value="<?= date('Y-m-d') ?>" onchange="calculateAwards()" class="w-full px-3.5 py-2 rounded-xl border border-slate-300 text-xs font-medium">
          </div>
        </div>

        <div class="flex items-center justify-end pt-2">
          <button type="button" id="btn-calculate-awards" onclick="calculateAwards()" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span id="btn-calculate-text">Scan &amp; Calculate Eligible Awardees</span>
          </button>
        </div>
      </div>

      <!-- Eligible Awardees Table -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div class="flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Eligible Perfect Attendance Candidates</h3>
            <span id="award-count-badge" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
              0 Students Qualified
            </span>
          </div>
          <span id="award-period-subtitle" class="text-[11px] text-slate-400 font-medium">
            Evaluating attendance records...
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4">Student ID</th>
                <th class="py-3.5 px-4">Student Name</th>
                <th class="py-3.5 px-4">Class Section</th>
                <th class="py-3.5 px-4 text-center">Total Sessions</th>
                <th class="py-3.5 px-4 text-center">Present Rate</th>
                <th class="py-3.5 px-4 text-center">Absences / Tardies</th>
                <th class="py-3.5 px-4 text-right">Award Action</th>
              </tr>
            </thead>
            <tbody id="awards-tbody" class="divide-y divide-slate-100 text-slate-700">
              <tr>
                <td colspan="7" class="text-center py-10 text-slate-400 text-sm">
                  Click <strong>Scan &amp; Calculate Eligible Awardees</strong> to evaluate attendance logs.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/modal.php'; ?>
<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

<script>
var currentCandidates = [];
var currentMeta = {};

function updateThresholdBadge(val) {
  const badge = document.getElementById('criteria-badge');
  if (!badge) return;
  if (val === '98') {
    badge.textContent = '98%+ Rate · Max 1 Excused Slip';
  } else {
    badge.textContent = '0 Absences · 0 Tardies · 100% Rate';
  }
}

function handlePeriodChange(val) {
  const customRow = document.getElementById('custom-date-row');
  const startInput = document.getElementById('award-start-date');
  const endInput = document.getElementById('award-end-date');

  if (val === 'custom') {
    if (customRow) customRow.classList.remove('hidden');
    return;
  } else {
    if (customRow) customRow.classList.add('hidden');
  }

  const now = new Date();
  if (val === 'month') {
    startInput.value = '2026-09-01';
    endInput.value = '2026-09-30';
  } else if (val === 'last_month') {
    startInput.value = '2026-08-01';
    endInput.value = '2026-08-31';
  } else if (val === 'sem') {
    startInput.value = '2026-08-01';
    endInput.value = '2026-12-31';
  }
}

function getPeriodLabel() {
  const periodSel = document.getElementById('award-period');
  if (periodSel && periodSel.value !== 'custom') {
    return periodSel.options[periodSel.selectedIndex].text;
  }
  const start = document.getElementById('award-start-date') ? document.getElementById('award-start-date').value : '';
  const end = document.getElementById('award-end-date') ? document.getElementById('award-end-date').value : '';
  return `${start} to ${end}`;
}

async function calculateAwards() {
  const section = document.getElementById('award-section') ? document.getElementById('award-section').value : 'ALL';
  const start = document.getElementById('award-start-date') ? document.getElementById('award-start-date').value : '2026-09-01';
  const end = document.getElementById('award-end-date') ? document.getElementById('award-end-date').value : '2026-09-30';
  const threshold = document.getElementById('award-strictness') ? document.getElementById('award-strictness').value : '100';

  const btn = document.getElementById('btn-calculate-awards');
  const btnText = document.getElementById('btn-calculate-text');
  if (btn) btn.disabled = true;
  if (btnText) btnText.textContent = 'Calculating Candidates...';

  try {
    const params = new URLSearchParams({
      section: section,
      start_date: start,
      end_date: end,
      threshold: threshold
    });

    const endpoint = (typeof window.url === 'function')
      ? window.url(`api/teacher/awards/calculate?${params.toString()}`)
      : `/api/teacher/awards/calculate?${params.toString()}`;

    const resp = await fetch(endpoint);
    const data = await resp.json();

    if (resp.ok && data.status === 'success') {
      currentCandidates = data.candidates || [];
      currentMeta = data;
      renderCandidatesTable(data);
      if (typeof APP !== 'undefined' && APP.showToast) {
        APP.showToast(`Identified ${data.total_eligible} eligible perfect attendance awardees!`, 'success');
      }
    } else {
      if (typeof APP !== 'undefined' && APP.showToast) {
        APP.showToast(data.message || 'Failed to calculate awards.', 'error');
      }
    }
  } catch (err) {
    console.error('Awards calculation error:', err);
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast('Network error while calculating awards.', 'error');
    }
  } finally {
    if (btn) btn.disabled = false;
    if (btnText) btnText.textContent = 'Scan & Calculate Eligible Awardees';
  }
}

function renderCandidatesTable(data) {
  const tbody = document.getElementById('awards-tbody');
  const badge = document.getElementById('award-count-badge');
  const subtitle = document.getElementById('award-period-subtitle');

  if (badge) {
    badge.textContent = `${data.total_eligible} Students Qualified`;
  }
  if (subtitle) {
    const secLabel = data.section === 'ALL' ? 'All Assigned Classes' : `Section ${escapeHtml(data.section)}`;
    subtitle.textContent = `${secLabel} · ${escapeHtml(data.start_date)} to ${escapeHtml(data.end_date)} (${data.total_sessions_held} sessions held)`;
  }

  if (!tbody) return;

  if (!data.candidates || data.candidates.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="text-center py-10 text-slate-400 text-sm">
          No students met the <strong>${data.threshold}%</strong> attendance criteria for the selected range.
          ${data.total_sessions_held === 0 ? '<br><span class="text-xs text-amber-600 mt-1 block">Note: 0 class sessions were recorded in this date range.</span>' : ''}
        </td>
      </tr>
    `;
    return;
  }

  tbody.innerHTML = data.candidates.map((c, idx) => {
    const rateVal = c.effective_rate !== undefined ? c.effective_rate : c.attendance_rate;
    const rateBadge = rateVal >= 100 
      ? '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">100.0%</span>'
      : `<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-800">${rateVal}%</span>`;

    return `
      <tr class="hover:bg-amber-50/30 transition">
        <td class="py-3.5 px-4 font-mono font-bold text-slate-800">
          ${escapeHtml(c.student_number)}
        </td>
        <td class="py-3.5 px-4">
          <div class="font-bold text-slate-900">${escapeHtml(c.full_name)}</div>
          <div class="text-[10px] text-slate-400">${escapeHtml(c.email || 'No email')}</div>
        </td>
        <td class="py-3.5 px-4 font-semibold text-slate-800">
          ${escapeHtml(c.section)}
        </td>
        <td class="py-3.5 px-4 text-center font-bold text-slate-800">
          ${c.present_count} / ${c.total_sessions}
        </td>
        <td class="py-3.5 px-4 text-center">
          ${rateBadge}
        </td>
        <td class="py-3.5 px-4 text-center text-slate-500 font-semibold">
          ${c.absent_count} / ${c.tardy_count}
          ${c.excused_count > 0 ? `<span class="text-[10px] text-teal-600 block">(${c.excused_count} excused)</span>` : ''}
        </td>
        <td class="py-3.5 px-4 text-right">
          <div class="inline-flex items-center gap-1.5 justify-end">
            <button type="button" onclick="editCandidate(${idx})" class="px-2.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-[11px] transition cursor-pointer inline-flex items-center gap-1" title="Edit student info or certificate details">
              <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              <span>Edit</span>
            </button>
            <button type="button" onclick="previewCertificate(${idx})" class="px-2.5 py-1.5 rounded-lg border border-slate-300 hover:bg-slate-100 text-slate-700 font-semibold text-[11px] transition cursor-pointer inline-flex items-center gap-1" title="Preview certificate modal">
              <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <span>Preview</span>
            </button>
            <button type="button" onclick="generateCertificate(${idx})" class="px-3 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] transition shadow-xs cursor-pointer inline-flex items-center gap-1.5" title="Generate and open certificate in a new browser tab window">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
              <span>Generate Certificate</span>
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function resolveSectionClean(candidate) {
  let sec = (candidate && candidate.section) ? String(candidate.section).trim() : '';
  if (!sec) {
    const secSelect = document.getElementById('award-section');
    if (secSelect && secSelect.value && secSelect.value !== 'ALL') {
      sec = secSelect.value.trim();
    }
  }
  if (!sec) return 'General';
  return sec.replace(/^section\s*:?\s*/i, '').trim() || sec;
}

function buildCertificateHtml(candidate, periodText) {
  const studentName = escapeHtml(candidate.full_name || `${candidate.first_name} ${candidate.last_name}`);
  const studentNumber = escapeHtml(candidate.student_number || '230110001');
  const sectionClean = escapeHtml(resolveSectionClean(candidate));
  const course = escapeHtml(candidate.course_title || 'Web Systems and Technologies');
  const rate = candidate.effective_rate !== undefined ? candidate.effective_rate : (candidate.attendance_rate || 100);
  const issueDate = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
  const teacherName = candidate.signatory_name || <?= json_encode($teacherName) ?>;
  const awardTitle = candidate.award_title || 'Certificate of Perfect Attendance';
  const logoSrc = <?= json_encode($bcpLogoDataUri) ?> || ((typeof window.url === 'function') ? window.url('assets/images/bcp-logo.png') : '/assets/images/bcp-logo.png');

  return `
    <div class="certificate-page my-2">
      <div class="certificate-frame text-center">
        <!-- Corner Accents -->
        <div class="cert-corner cert-corner-tl"></div>
        <div class="cert-corner cert-corner-tr"></div>
        <div class="cert-corner cert-corner-bl"></div>
        <div class="cert-corner cert-corner-br"></div>

        <!-- School Crest -->
        <div class="mb-3 text-center">
          <img src="${logoSrc}" alt="Bestlink College of the Philippines" class="certificate-logo w-24 h-24 mx-auto mb-2 object-contain block">
          <h1 class="text-base sm:text-lg font-black tracking-widest text-[#0f172a] uppercase font-serif leading-tight">
            BESTLINK COLLEGE OF THE PHILIPPINES
          </h1>
          <p class="text-xs font-bold tracking-wider text-[#b45309] uppercase mt-0.5 font-sans">
            College of Computer Studies
          </p>
          <p class="text-[10px] text-slate-500 tracking-tight mt-0.5 font-sans">
            1071 Brgy. Kaligayahan Quirino Highway, Novaliches, Quezon City
          </p>
        </div>

        <!-- Academic Ribbon & Award Title -->
        <div class="my-3 text-center">
          <span class="inline-block px-4 py-0.5 rounded-full border border-amber-400/80 bg-amber-50 text-[10px] font-extrabold tracking-widest text-amber-900 uppercase shadow-2xs">
            Office of Academic Affairs • Certificate of Recognition
          </span>
          <h2 class="text-2xl sm:text-3xl font-black tracking-wider text-slate-900 mt-2 font-serif uppercase">
            ${escapeHtml(awardTitle)}
          </h2>
          <div class="w-36 h-0.5 bg-gradient-to-r from-transparent via-amber-500 to-transparent mx-auto mt-2"></div>
        </div>

        <p class="text-xs italic text-slate-600 my-2 font-serif">This certificate is proudly presented to</p>

        <!-- Recipient Student Name -->
        <div class="my-3 text-center">
          <div class="text-2xl sm:text-3xl font-bold font-serif text-[#1e3a8a] tracking-wide inline-block border-b-2 border-amber-400 pb-1 px-8">
            ${studentName}
          </div>
        </div>

        <!-- Student Metadata Pill Bar: Section & Student ID -->
        <div class="cert-meta-row">
          <div class="cert-meta-pill">
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">STUDENT ID:</span>
            <span class="font-mono font-bold text-slate-800 text-xs">${studentNumber}</span>
          </div>
          <div class="cert-meta-pill cert-meta-section">
            <span class="text-[10px] uppercase tracking-wider text-amber-700 font-extrabold">CLASS SECTION:</span>
            <span class="font-bold text-amber-950 text-xs">Section ${sectionClean}</span>
          </div>
          <div class="cert-meta-pill">
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">COURSE:</span>
            <span class="font-semibold text-slate-800 text-xs">${course}</span>
          </div>
          <div class="cert-meta-pill">
            <span class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">RECORD:</span>
            <span class="font-bold text-amber-700 text-xs">${rate}% ATTENDANCE</span>
          </div>
        </div>

        <!-- Simple, Clear Citation Body -->
        <p class="text-xs sm:text-sm text-slate-700 max-w-xl mx-auto leading-relaxed text-center my-3 font-serif">
          For achieving a <strong class="text-amber-800 font-black">${rate}% Attendance Record</strong> in 
          <strong class="text-slate-900">${course}</strong>, Section <strong class="text-amber-900 font-bold">${sectionClean}</strong>, 
          during <strong class="text-slate-900">${escapeHtml(periodText)}</strong>.
        </p>

        <!-- Signature Lines & Date -->
        <div class="grid grid-cols-2 gap-12 pt-7 mt-5 border-t border-amber-200/80 text-xs text-center max-w-lg mx-auto">
          <div>
            <div class="w-48 mx-auto border-b-2 border-slate-700 pb-1 font-bold text-slate-900 text-xs">${escapeHtml(teacherName)}</div>
            <div class="text-[10px] uppercase tracking-wider text-slate-500 mt-1 font-semibold">Course Instructor</div>
          </div>
          <div>
            <div class="w-48 mx-auto border-b-2 border-slate-700 pb-1 font-bold text-slate-900 text-xs">${issueDate}</div>
            <div class="text-[10px] uppercase tracking-wider text-slate-500 mt-1 font-semibold">Date Conferred</div>
          </div>
        </div>

        <!-- Verification / Control Footer -->
        <div class="flex items-center justify-between text-[10px] text-slate-400 pt-5 mt-4 border-t border-slate-100 font-mono">
          <span>Control No: BCP-ATT-2026-${studentNumber}</span>
          <span>Official Academic Certificate • Bestlink College of the Philippines</span>
        </div>
      </div>
    </div>
  `;
}

function buildFullCertificateDocument(candidates, periodText, pageTitle) {
  const teacherName = <?= json_encode($teacherName) ?>;
  const logoSrc = <?= json_encode($bcpLogoDataUri) ?> || ((typeof window.url === 'function') ? window.url('assets/images/bcp-logo.png') : '/assets/images/bcp-logo.png');
  const issueDate = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

  const certificateSheetsHtml = candidates.map((cand, idx) => {
    const studentName = escapeHtml(cand.full_name || `${cand.first_name} ${cand.last_name}`);
    const studentNumber = escapeHtml(cand.student_number || '230110001');
    const sectionClean = escapeHtml(resolveSectionClean(cand));
    const course = escapeHtml(cand.course_title || 'Web Systems and Technologies');
    const rate = cand.effective_rate !== undefined ? cand.effective_rate : (cand.attendance_rate || 100);

    return `
      <div class="certificate-sheet">
        <div class="cert-frame">
          <!-- Corner Accents -->
          <div class="cert-corner cert-corner-tl"></div>
          <div class="cert-corner cert-corner-tr"></div>
          <div class="cert-corner cert-corner-bl"></div>
          <div class="cert-corner cert-corner-br"></div>

          <!-- Header -->
          <div class="cert-header">
            <img src="${logoSrc}" alt="Bestlink College of the Philippines Logo" class="cert-logo">
            <h1 class="cert-institution">Bestlink College of the Philippines</h1>
            <p class="cert-college">College of Computer Studies</p>
            <p class="cert-address">1071 Brgy. Kaligayahan Quirino Highway, Novaliches, Quezon City</p>
          </div>

          <!-- Academic Ribbon & Award Title -->
          <div class="cert-badge-wrap">
            <span class="cert-badge">Office of Academic Affairs • Certificate of Recognition</span>
            <h2 class="cert-title">Certificate of Perfect Attendance</h2>
            <div class="cert-divider"></div>
          </div>

          <p class="cert-present-text">This certificate is proudly presented to</p>

          <!-- Student Name -->
          <div class="cert-name-wrap">
            <div class="cert-student-name">${studentName}</div>
          </div>

          <!-- Student Credentials Pill Bar -->
          <div class="cert-meta-row">
            <div class="cert-meta-pill">
              <span class="cert-meta-label">STUDENT ID:</span>
              <span class="cert-meta-val font-mono">${studentNumber}</span>
            </div>
            <div class="cert-meta-pill cert-meta-section">
              <span class="cert-meta-label">CLASS SECTION:</span>
              <span class="cert-meta-val font-bold">Section ${sectionClean}</span>
            </div>
            <div class="cert-meta-pill">
              <span class="cert-meta-label">COURSE:</span>
              <span class="cert-meta-val">${course}</span>
            </div>
            <div class="cert-meta-pill">
              <span class="cert-meta-label">RECORD:</span>
              <span class="cert-meta-val font-bold text-amber-700">${rate}% ATTENDANCE</span>
            </div>
          </div>

          <!-- Simple, Clear Citation Body -->
          <p class="cert-citation">
            For achieving a <strong class="highlight-stat">${rate}% Attendance Record</strong> in 
            <strong class="highlight-course">${course}</strong>, Section <strong class="highlight-section">${sectionClean}</strong>, 
            during <strong>${escapeHtml(periodText)}</strong>.
          </p>

          <!-- Signature Lines -->
          <div class="cert-signatures">
            <div class="cert-sig-box">
              <div class="cert-sig-line">${escapeHtml(teacherName)}</div>
              <div class="cert-sig-title">Course Instructor</div>
            </div>
            <div class="cert-sig-box cert-seal-box">
              <div class="cert-seal-symbol">★ ★ ★</div>
              <div class="cert-sig-title">Academic Excellence Seal</div>
            </div>
            <div class="cert-sig-box">
              <div class="cert-sig-line">${issueDate}</div>
              <div class="cert-sig-title">Date Conferred</div>
            </div>
          </div>

          <!-- Footer / Serial Bar -->
          <div class="cert-footer-bar">
            <span>Control No: BCP-ATT-2026-${studentNumber}</span>
            <span>Official Academic Certificate • Bestlink College of the Philippines</span>
          </div>
        </div>
      </div>
    `;
  }).join('');

  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>${escapeHtml(pageTitle)}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      background-color: #0b1120;
      color: #1e293b;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 24px 16px;
      -webkit-font-smoothing: antialiased;
    }

    /* Top Sticky Action Toolbar */
    .action-bar {
      position: sticky;
      top: 16px;
      z-index: 1000;
      background: rgba(15, 23, 42, 0.92);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 9999px;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
      box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
    }
    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 18px;
      border-radius: 9999px;
      font-size: 12.5px;
      font-weight: 700;
      cursor: pointer;
      border: none;
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .btn-print {
      background: linear-gradient(135deg, #d97706, #b45309);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(180, 83, 9, 0.4);
    }
    .btn-print:hover {
      background: linear-gradient(135deg, #b45309, #92400e);
      transform: translateY(-1px);
      box-shadow: 0 6px 16px rgba(180, 83, 9, 0.5);
    }
    .btn-close {
      background: rgba(255, 255, 255, 0.1);
      color: #e2e8f0;
    }
    .btn-close:hover {
      background: rgba(255, 255, 255, 0.2);
      color: #ffffff;
    }
    .action-tip {
      font-size: 11px;
      color: #94a3b8;
      margin-left: 6px;
      padding-left: 14px;
      border-left: 1px solid rgba(255, 255, 255, 0.15);
    }

    /* Certificate Sheet - Landscape presentation */
    .certificate-sheet {
      width: 100%;
      max-width: 1040px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
      margin-bottom: 32px;
      position: relative;
      overflow: hidden;
      page-break-after: always;
      break-after: page;
    }
    .certificate-sheet:last-child {
      margin-bottom: 0;
      page-break-after: auto;
      break-after: auto;
    }

    /* Outer Double Border & Parchment Styling */
    .cert-frame {
      margin: 20px;
      background-color: #fffdf9;
      border: 8px double #b45309;
      outline: 2px solid #b45309;
      outline-offset: -12px;
      padding: 40px 48px;
      position: relative;
      box-shadow: inset 0 0 35px rgba(180, 83, 9, 0.04);
      text-align: center;
    }

    /* Corner Accents */
    .cert-corner {
      position: absolute;
      width: 26px;
      height: 26px;
      border-color: #b45309;
      pointer-events: none;
    }
    .cert-corner-tl { top: 16px; left: 16px; border-top: 3px solid; border-left: 3px solid; }
    .cert-corner-tr { top: 16px; right: 16px; border-top: 3px solid; border-right: 3px solid; }
    .cert-corner-bl { bottom: 16px; left: 16px; border-bottom: 3px solid; border-left: 3px solid; }
    .cert-corner-br { bottom: 16px; right: 16px; border-bottom: 3px solid; border-right: 3px solid; }

    /* Header */
    .cert-header {
      margin-bottom: 16px;
    }
    .cert-logo {
      width: 82px;
      height: 82px;
      margin: 0 auto 10px auto;
      object-fit: contain;
      display: block;
    }
    .cert-institution {
      font-family: 'Cinzel', serif;
      font-size: 21px;
      font-weight: 800;
      letter-spacing: 2px;
      color: #0f172a;
      text-transform: uppercase;
      line-height: 1.2;
    }
    .cert-college {
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 1.5px;
      color: #b45309;
      text-transform: uppercase;
      margin-top: 3px;
    }
    .cert-address {
      font-size: 10px;
      color: #64748b;
      margin-top: 2px;
      letter-spacing: 0.5px;
    }

    /* Ribbon & Award Title */
    .cert-badge-wrap {
      margin: 14px 0 10px 0;
    }
    .cert-badge {
      display: inline-block;
      padding: 4px 18px;
      border-radius: 9999px;
      background: #fef3c7;
      border: 1px solid #f59e0b;
      color: #92400e;
      font-size: 10.5px;
      font-weight: 800;
      letter-spacing: 1.5px;
      text-transform: uppercase;
    }
    .cert-title {
      font-family: 'Playfair Display', serif;
      font-size: 30px;
      font-weight: 900;
      letter-spacing: 1.5px;
      color: #0f172a;
      text-transform: uppercase;
      margin-top: 8px;
      line-height: 1.2;
    }
    .cert-divider {
      width: 140px;
      height: 2px;
      background: linear-gradient(90deg, transparent, #b45309, transparent);
      margin: 8px auto 0 auto;
    }

    /* Presentation */
    .cert-present-text {
      font-family: 'Playfair Display', serif;
      font-style: italic;
      font-size: 13px;
      color: #475569;
      margin: 12px 0 8px 0;
    }

    /* Student Name */
    .cert-name-wrap {
      margin-bottom: 8px;
    }
    .cert-student-name {
      font-family: 'Playfair Display', serif;
      font-size: 32px;
      font-weight: 800;
      color: #1e3a8a;
      letter-spacing: 1px;
      display: inline-block;
      padding: 0 28px 4px 28px;
      border-bottom: 2.5px solid #d97706;
    }

    /* Credentials Metadata Row (Prominent Section) */
    .cert-meta-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      flex-wrap: wrap;
      margin: 12px auto 16px auto;
    }
    .cert-meta-pill {
      font-size: 11px;
      color: #334155;
      background: #f1f5f9;
      padding: 5px 14px;
      border-radius: 9999px;
      border: 1px solid #e2e8f0;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .cert-meta-label {
      font-size: 9.5px;
      font-weight: 800;
      letter-spacing: 0.75px;
      color: #64748b;
    }
    .cert-meta-val {
      font-weight: 700;
      color: #0f172a;
    }
    .cert-meta-section {
      background: #fef3c7;
      border-color: #fcd34d;
      color: #92400e;
      box-shadow: 0 1px 3px rgba(180, 83, 9, 0.1);
    }
    .cert-meta-section .cert-meta-label {
      color: #b45309;
    }
    .cert-meta-section .cert-meta-val {
      color: #78350f;
    }

    /* Citation Body */
    .cert-citation {
      font-family: 'Playfair Display', serif;
      font-size: 14px;
      line-height: 1.75;
      color: #334155;
      max-width: 740px;
      margin: 0 auto 20px auto;
    }
    .highlight-stat {
      color: #92400e;
      font-weight: 800;
    }
    .highlight-course {
      color: #0f172a;
      font-weight: 700;
    }
    .highlight-section {
      color: #78350f;
      font-weight: 800;
    }

    /* Signatures */
    .cert-signatures {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-top: 28px;
      padding-top: 18px;
      border-top: 1px solid #fde68a;
    }
    .cert-sig-box {
      text-align: center;
    }
    .cert-sig-line {
      width: 180px;
      margin: 0 auto 4px auto;
      border-bottom: 1.5px solid #334155;
      padding-bottom: 2px;
      font-size: 12.5px;
      font-weight: 700;
      color: #0f172a;
    }
    .cert-sig-title {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.75px;
      color: #64748b;
      font-weight: 600;
    }
    .cert-seal-box {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .cert-seal-symbol {
      color: #d97706;
      font-size: 14px;
      letter-spacing: 4px;
      font-weight: 900;
      margin-bottom: 4px;
    }

    /* Certificate Serial / Footer */
    .cert-footer-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 22px;
      padding-top: 10px;
      border-top: 1px dashed #e2e8f0;
      font-size: 9.5px;
      color: #94a3b8;
      font-family: 'JetBrains Mono', monospace;
    }

    /* Print media setup */
    @media print {
      @page {
        size: landscape;
        margin: 0.35in;
      }
      body {
        background-color: #ffffff !important;
        padding: 0 !important;
        color: #000000 !important;
      }
      .action-bar {
        display: none !important;
      }
      .certificate-sheet {
        box-shadow: none !important;
        border-radius: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        margin: 0 !important;
        page-break-after: always !important;
        break-after: page !important;
      }
      .cert-frame {
        margin: 0 !important;
        padding: 36px 40px !important;
        background-color: #fffdf9 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
    }
  </style>
</head>
<body>
  <!-- Action Toolbar (Screen Only) -->
  <div class="action-bar no-print">
    <button type="button" class="action-btn btn-print" onclick="window.print()">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print / Save as PDF</span>
    </button>
    <button type="button" class="action-btn btn-close" onclick="window.close()">
      <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      <span>Close Window</span>
    </button>
    <span class="action-tip">
      <strong>${candidates.length}</strong> Certificate${candidates.length > 1 ? 's' : ''} • Best result: Landscape orientation with 'Background Graphics' enabled.
    </span>
  </div>

  <!-- Rendered Certificate Sheet(s) -->
  ${certificateSheetsHtml}
</body>
</html>`;
}

function openCertificatesWindow(candidates, periodText, pageTitle) {
  if (!candidates || candidates.length === 0) {
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast('No candidates available to generate certificate.', 'warning');
    }
    return;
  }

  const printWindow = window.open('', '_blank');
  if (!printWindow) {
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast('Pop-up was blocked. Please allow pop-ups for this site to open certificates in a new tab.', 'warning');
    }
    if (candidates.length === 1 && currentCandidates) {
      const idx = currentCandidates.indexOf(candidates[0]);
      if (idx !== -1) previewCertificate(idx);
    }
    return;
  }

  const docHtml = buildFullCertificateDocument(candidates, periodText, pageTitle);
  printWindow.document.open();
  printWindow.document.write(docHtml);
  printWindow.document.close();
  printWindow.focus();
}

function generateCertificate(index) {
  if (!currentCandidates || !currentCandidates[index]) return;
  const cand = currentCandidates[index];
  const periodText = getPeriodLabel();
  const title = `Certificate of Perfect Attendance - ${cand.full_name || 'Student'}`;
  openCertificatesWindow([cand], periodText, title);
}

function previewCertificate(index) {
  if (!currentCandidates || !currentCandidates[index]) return;
  const cand = currentCandidates[index];
  const periodText = getPeriodLabel();

  const bodyHTML = buildCertificateHtml(cand, periodText);
  const footerHTML = `
    <button type="button" class="btn btn-secondary btn-sm" onclick="APP.closeModal()">Close</button>
    <button type="button" class="btn btn-primary btn-sm inline-flex items-center gap-1.5" onclick="generateCertificate(${index})">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      Open in New Window / Print
    </button>
  `;

  APP.openModal(`Certificate Preview: ${cand.full_name}`, bodyHTML, footerHTML);
}

function generateBatchCertificates() {
  if (!currentCandidates || currentCandidates.length === 0) {
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast('No eligible candidates to generate certificates for.', 'warning');
    }
    return;
  }

  const periodText = getPeriodLabel();
  const secName = document.getElementById('award-section') ? document.getElementById('award-section').value : 'ALL';
  const secTitle = secName === 'ALL' ? 'All Sections' : `Section ${secName}`;
  const title = `Batch Certificates - ${secTitle} (${currentCandidates.length} Students)`;

  openCertificatesWindow(currentCandidates, periodText, title);

  if (typeof APP !== 'undefined' && APP.showToast) {
    APP.showToast(`Opened ${currentCandidates.length} certificates in a new browser tab ready to print!`, 'success');
  }
}

function editCandidate(idx) {
  if (!currentCandidates || !currentCandidates[idx]) return;
  const cand = currentCandidates[idx];

  const modalTitle = `Edit Candidate: ${cand.full_name || 'Student'}`;
  const defaultTeacherName = <?= json_encode($teacherName) ?>;
  const currentSignatory = cand.signatory_name || defaultTeacherName;
  const currentAwardTitle = cand.award_title || 'Certificate of Perfect Attendance';

  const bodyHTML = `
    <form id="form-edit-candidate" onsubmit="event.preventDefault(); saveCandidateEdit(${idx});" class="space-y-4">
      <input type="hidden" id="edit-student-id" value="${cand.student_id}">
      
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">First Name</label>
          <input type="text" id="edit-first-name" value="${escapeHtml(cand.first_name || '')}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Last Name</label>
          <input type="text" id="edit-last-name" value="${escapeHtml(cand.last_name || '')}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Student Number / ID</label>
          <input type="text" id="edit-student-number" value="${escapeHtml(cand.student_number || '')}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address</label>
          <input type="email" id="edit-email" value="${escapeHtml(cand.email || '')}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Class Section</label>
          <input type="text" id="edit-section" value="${escapeHtml(cand.section || '')}" required class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Course / Subject</label>
          <input type="text" id="edit-course-title" value="${escapeHtml(cand.course_title || '')}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Instructor Signatory</label>
          <input type="text" id="edit-signatory-name" value="${escapeHtml(currentSignatory)}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Award Title</label>
          <input type="text" id="edit-award-title" value="${escapeHtml(currentAwardTitle)}" class="w-full px-3 py-2 rounded-xl border border-slate-300 text-xs font-medium focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
      </div>

      <div class="p-3 bg-amber-50 rounded-xl border border-amber-200/80 text-[11px] text-amber-900 leading-relaxed">
        <strong>Notice:</strong> Saved changes will update student records directly in the database and be reflected on preview and generated certificates.
      </div>
    </form>
  `;

  const footerHTML = `
    <button type="button" class="btn btn-secondary btn-sm" onclick="APP.closeModal()">Cancel</button>
    <button type="button" id="btn-save-edit" onclick="saveCandidateEdit(${idx})" class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
      <span id="btn-save-edit-text">Save Changes</span>
    </button>
  `;

  APP.openModal(modalTitle, bodyHTML, footerHTML);
}

async function saveCandidateEdit(idx) {
  if (!currentCandidates || !currentCandidates[idx]) return;
  const cand = currentCandidates[idx];

  const firstName = (document.getElementById('edit-first-name')?.value || '').trim();
  const lastName = (document.getElementById('edit-last-name')?.value || '').trim();
  const studentNumber = (document.getElementById('edit-student-number')?.value || '').trim();
  const email = (document.getElementById('edit-email')?.value || '').trim();
  const section = (document.getElementById('edit-section')?.value || '').trim();
  const courseTitle = (document.getElementById('edit-course-title')?.value || '').trim();
  const signatoryName = (document.getElementById('edit-signatory-name')?.value || '').trim();
  const awardTitle = (document.getElementById('edit-award-title')?.value || '').trim();

  if (!firstName || !lastName) {
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast('First name and last name are required.', 'warning');
    }
    return;
  }

  const saveBtn = document.getElementById('btn-save-edit');
  const saveBtnText = document.getElementById('btn-save-edit-text');
  if (saveBtn) saveBtn.disabled = true;
  if (saveBtnText) saveBtnText.textContent = 'Saving...';

  try {
    const formData = new FormData();
    formData.append('student_id', cand.student_id);
    formData.append('first_name', firstName);
    formData.append('last_name', lastName);
    formData.append('student_number', studentNumber);
    formData.append('email', email);
    formData.append('section', section);
    formData.append('course_title', courseTitle);

    const endpoint = (typeof window.url === 'function')
      ? window.url('api/teacher/awards/update-candidate')
      : '/api/teacher/awards/update-candidate';

    const resp = await fetch(endpoint, {
      method: 'POST',
      body: formData
    });
    const result = await resp.json();

    if (resp.ok && result.status === 'success') {
      cand.first_name = firstName;
      cand.last_name = lastName;
      cand.full_name = `${lastName}, ${firstName}`;
      cand.student_number = studentNumber;
      cand.email = email;
      cand.section = section;
      cand.course_title = courseTitle || cand.course_title;
      cand.signatory_name = signatoryName;
      cand.award_title = awardTitle;

      renderCandidatesTable(currentMeta);
      APP.closeModal();
      if (typeof APP !== 'undefined' && APP.showToast) {
        APP.showToast('Student information and award details updated successfully!', 'success');
      }
    } else {
      if (typeof APP !== 'undefined' && APP.showToast) {
        APP.showToast(result.message || 'Failed to update student details.', 'error');
      }
    }
  } catch (err) {
    console.error('Update candidate error:', err);
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast('Network error while saving changes.', 'error');
    }
  } finally {
    if (saveBtn) saveBtn.disabled = false;
    if (saveBtnText) saveBtnText.textContent = 'Save Changes';
  }
}

function exportAwardsCsv() {
  const section = document.getElementById('award-section') ? document.getElementById('award-section').value : 'ALL';
  const start = document.getElementById('award-start-date') ? document.getElementById('award-start-date').value : '2026-09-01';
  const end = document.getElementById('award-end-date') ? document.getElementById('award-end-date').value : '2026-09-30';
  const threshold = document.getElementById('award-strictness') ? document.getElementById('award-strictness').value : '100';

  const params = new URLSearchParams({
    section: section,
    start_date: start,
    end_date: end,
    threshold: threshold,
    export: 'csv'
  });

  const endpoint = (typeof window.url === 'function')
    ? window.url(`api/teacher/awards/calculate?${params.toString()}`)
    : `/api/teacher/awards/calculate?${params.toString()}`;

  window.location.href = endpoint;
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[m]);
}

// Initial calculation on page load
document.addEventListener('DOMContentLoaded', () => {
  calculateAwards();
});
</script>
