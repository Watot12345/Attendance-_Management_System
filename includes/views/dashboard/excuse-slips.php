<?php
$page_title = 'Submitted Excuse Slips & Review';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="badge badge-present">Teacher Portal</span>
            <span class="text-xs text-slate-500">1st Semester AY 2025–2026</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">Submitted Excuse Slips &amp; Review</h1>
          <p class="text-sm text-slate-500">Review, verify medical/excuse certificates, and approve or reject absence requests submitted by students and parents.</p>
        </div>

        <div class="flex items-center gap-3">
          <button type="button" onclick="APP.toast('Exported excuse slip review history to CSV', 'success')" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-sm transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export Slips CSV</span>
          </button>
        </div>
      </div>

      <!-- KPI Summary Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Pending Review</div>
          <div class="text-2xl font-bold text-amber-600 mt-1">3 Slips</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Requires Decision</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Approved Slips</div>
          <div class="text-2xl font-bold text-emerald-600 mt-1">12 Slips</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Absences Excused</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Rejected Slips</div>
          <div class="text-2xl font-bold text-rose-600 mt-1">2 Slips</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Unexcused Absence</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Received</div>
          <div class="text-2xl font-bold text-slate-800 mt-1">17 Slips</div>
          <div class="text-[11px] text-slate-500 mt-0.5">All Enrolled Classes</div>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-3">
          <!-- Status Filter -->
          <div>
            <label for="filter-status" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Status</label>
            <select id="filter-status" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500" onchange="filterSlips()">
              <option value="all">All Statuses</option>
              <option value="pending" selected>Pending Review (3)</option>
              <option value="approved">Approved (12)</option>
              <option value="rejected">Rejected (2)</option>
            </select>
          </div>

          <!-- Section Filter -->
          <div>
            <label for="filter-class" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Class &amp; Section</label>
            <select id="filter-class" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500" onchange="filterSlips()">
              <option value="all">All Sections (BSIT 3-A, 3-B, 3-C)</option>
              <option value="BSIT 3-A">BSIT 3-A (Web Dev 2)</option>
              <option value="BSIT 3-B">BSIT 3-B (Database Systems)</option>
              <option value="BSIT 3-C">BSIT 3-C (Systems Integration)</option>
            </select>
          </div>

          <!-- Search keyword -->
          <div class="sm:col-span-1 md:col-span-2">
            <label for="search-student" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">Search Student or Reason</label>
            <div class="relative">
              <input type="text" id="search-student" placeholder="Search by student name, ID, or excuse reason..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500" oninput="filterSlips()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Submitted Excuse Slips Table -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
          <h2 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Submitted Excuse Slips Review Queue</h2>
          <span class="text-xs text-slate-500">Showing <strong id="visible-count" class="text-slate-800">3</strong> excuse slip submissions</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200">
              <tr>
                <th class="py-3 px-4">Student</th>
                <th class="py-3 px-4">Section &amp; Course</th>
                <th class="py-3 px-4">Absence Period</th>
                <th class="py-3 px-4">Reason &amp; Documentation</th>
                <th class="py-3 px-4">Submitted By</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody id="slips-tbody" class="divide-y divide-slate-100 text-slate-700">
              
              <!-- Slip 1 -->
              <tr class="slip-row hover:bg-slate-50/80 transition cursor-pointer" data-status="pending" data-section="BSIT 3-A" data-text="juan dela cruz 2026-00123 fever medical certificate" onclick="openReviewModal('Juan Dela Cruz', '2026-00123', 'BSIT 3-A', 'IT301 — Web Development 2', 'Sep 5–6, 2026 (2 Sessions)', 'High fever and viral flu. Medical certificate from QC General Hospital attached.', 'Parent (Maria Dela Cruz)', 'medical_cert_delacruz.pdf', 'pending')">
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800">Juan Dela Cruz</div>
                  <div class="text-[10px] font-mono text-blue-600">2026-00123</div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-semibold text-slate-700">BSIT 3-A</div>
                  <div class="text-[10px] text-slate-400">IT301 — Web Dev 2</div>
                </td>
                <td class="py-3 px-4 font-medium text-slate-700">
                  <div>Sep 5 – 6, 2026</div>
                  <div class="text-[10px] text-slate-400">2 class sessions missed</div>
                </td>
                <td class="py-3 px-4 max-w-xs">
                  <div class="truncate font-medium text-slate-800">High fever and viral flu...</div>
                  <span class="inline-flex items-center gap-1 text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded mt-0.5">
                    📎 medical_cert_delacruz.pdf
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="font-medium text-slate-700">Parent</div>
                  <div class="text-[10px] text-slate-400">Sep 6, 2026 • 09:15 AM</div>
                </td>
                <td class="py-3 px-4">
                  <span class="badge badge-pending font-bold">⏳ Pending Review</span>
                </td>
                <td class="py-3 px-4 text-right" onclick="event.stopPropagation();">
                  <button type="button" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition" onclick="openReviewModal('Juan Dela Cruz', '2026-00123', 'BSIT 3-A', 'IT301 — Web Development 2', 'Sep 5–6, 2026 (2 Sessions)', 'High fever and viral flu. Medical certificate from QC General Hospital attached.', 'Parent (Maria Dela Cruz)', 'medical_cert_delacruz.pdf', 'pending')">
                    Review Slip
                  </button>
                </td>
              </tr>

              <!-- Slip 2 -->
              <tr class="slip-row hover:bg-slate-50/80 transition cursor-pointer" data-status="pending" data-section="BSIT 3-B" data-text="maria santos 2026-00124 dental emergency tooth extraction" onclick="openReviewModal('Maria Santos', '2026-00124', 'BSIT 3-B', 'IT302 — Database Systems 2', 'Sep 3, 2026 (1 Session)', 'Emergency tooth extraction and dental surgery.', 'Student (Self)', 'dental_clearance_santos.jpg', 'pending')">
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800">Maria Santos</div>
                  <div class="text-[10px] font-mono text-blue-600">2026-00124</div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-semibold text-slate-700">BSIT 3-B</div>
                  <div class="text-[10px] text-slate-400">IT302 — Database Systems</div>
                </td>
                <td class="py-3 px-4 font-medium text-slate-700">
                  <div>Sep 3, 2026</div>
                  <div class="text-[10px] text-slate-400">1 session missed</div>
                </td>
                <td class="py-3 px-4 max-w-xs">
                  <div class="truncate font-medium text-slate-800">Emergency dental surgery...</div>
                  <span class="inline-flex items-center gap-1 text-[10px] text-blue-700 bg-blue-50 border border-blue-200 px-1.5 py-0.5 rounded mt-0.5">
                    📎 dental_clearance_santos.jpg
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="font-medium text-slate-700">Student (Self)</div>
                  <div class="text-[10px] text-slate-400">Sep 4, 2026 • 02:30 PM</div>
                </td>
                <td class="py-3 px-4">
                  <span class="badge badge-pending font-bold">⏳ Pending Review</span>
                </td>
                <td class="py-3 px-4 text-right" onclick="event.stopPropagation();">
                  <button type="button" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition" onclick="openReviewModal('Maria Santos', '2026-00124', 'BSIT 3-B', 'IT302 — Database Systems 2', 'Sep 3, 2026 (1 Session)', 'Emergency tooth extraction and dental surgery.', 'Student (Self)', 'dental_clearance_santos.jpg', 'pending')">
                    Review Slip
                  </button>
                </td>
              </tr>

              <!-- Slip 3 -->
              <tr class="slip-row hover:bg-slate-50/80 transition cursor-pointer" data-status="pending" data-section="BSIT 3-C" data-text="gabriel fernandez 2026-00129 family emergency provincial travel" onclick="openReviewModal('Gabriel Fernandez', '2026-00129', 'BSIT 3-C', 'IT303 — Systems Integration', 'Sep 1–2, 2026 (2 Sessions)', 'Urgent family emergency in province. Signed letter from parents provided.', 'Parent (Roberto Fernandez)', 'parent_letter_fernandez.pdf', 'pending')">
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800">Gabriel Fernandez</div>
                  <div class="text-[10px] font-mono text-blue-600">2026-00129</div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-semibold text-slate-700">BSIT 3-C</div>
                  <div class="text-[10px] text-slate-400">IT303 — Systems Integration</div>
                </td>
                <td class="py-3 px-4 font-medium text-slate-700">
                  <div>Sep 1 – 2, 2026</div>
                  <div class="text-[10px] text-slate-400">2 sessions missed</div>
                </td>
                <td class="py-3 px-4 max-w-xs">
                  <div class="truncate font-medium text-slate-800">Urgent family emergency...</div>
                  <span class="inline-flex items-center gap-1 text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded mt-0.5">
                    📎 parent_letter_fernandez.pdf
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="font-medium text-slate-700">Parent</div>
                  <div class="text-[10px] text-slate-400">Sep 2, 2026 • 11:00 AM</div>
                </td>
                <td class="py-3 px-4">
                  <span class="badge badge-pending font-bold">⏳ Pending Review</span>
                </td>
                <td class="py-3 px-4 text-right" onclick="event.stopPropagation();">
                  <button type="button" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition" onclick="openReviewModal('Gabriel Fernandez', '2026-00129', 'BSIT 3-C', 'IT303 — Systems Integration', 'Sep 1–2, 2026 (2 Sessions)', 'Urgent family emergency in province. Signed letter from parents provided.', 'Parent (Roberto Fernandez)', 'parent_letter_fernandez.pdf', 'pending')">
                    Review Slip
                  </button>
                </td>
              </tr>

              <!-- Slip 4 (Approved) -->
              <tr class="slip-row hover:bg-slate-50/80 transition cursor-pointer" data-status="approved" data-section="BSIT 3-A" data-text="pedro reyes 2026-00125 dengue recovery hospital clearance" onclick="openReviewModal('Pedro Reyes', '2026-00125', 'BSIT 3-A', 'IT301 — Web Development 2', 'Aug 28–30, 2026 (3 Sessions)', 'Hospitalized due to dengue fever. Medical certificate and discharge slip attached.', 'Parent (Eduardo Reyes)', 'dengue_discharge_reyes.pdf', 'approved')">
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800">Pedro Reyes</div>
                  <div class="text-[10px] font-mono text-blue-600">2026-00125</div>
                </td>
                <td class="py-3 px-4">
                  <div class="font-semibold text-slate-700">BSIT 3-A</div>
                  <div class="text-[10px] text-slate-400">IT301 — Web Dev 2</div>
                </td>
                <td class="py-3 px-4 font-medium text-slate-700">
                  <div>Aug 28 – 30, 2026</div>
                  <div class="text-[10px] text-slate-400">3 sessions excused</div>
                </td>
                <td class="py-3 px-4 max-w-xs">
                  <div class="truncate font-medium text-slate-800">Hospitalized due to dengue...</div>
                  <span class="inline-flex items-center gap-1 text-[10px] text-emerald-700 bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 rounded mt-0.5">
                    📎 dengue_discharge_reyes.pdf
                  </span>
                </td>
                <td class="py-3 px-4">
                  <div class="font-medium text-slate-700">Parent</div>
                  <div class="text-[10px] text-slate-400">Sep 1, 2026 • 08:30 AM</div>
                </td>
                <td class="py-3 px-4">
                  <span class="badge badge-present font-bold">✓ Approved (Excused)</span>
                </td>
                <td class="py-3 px-4 text-right" onclick="event.stopPropagation();">
                  <button type="button" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition" onclick="openReviewModal('Pedro Reyes', '2026-00125', 'BSIT 3-A', 'IT301 — Web Development 2', 'Aug 28–30, 2026 (3 Sessions)', 'Hospitalized due to dengue fever. Medical certificate and discharge slip attached.', 'Parent (Eduardo Reyes)', 'dengue_discharge_reyes.pdf', 'approved')">
                    View Record
                  </button>
                </td>
              </tr>

            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: EXCUSE SLIP REVIEW & VERIFICATION
══════════════════════════════════════════════════════════════ -->
<div id="review-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span id="modal-status-badge" class="badge badge-pending font-bold text-xs">⏳ Pending Review</span>
          <span id="modal-section-badge" class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800">BSIT 3-A</span>
        </div>
        <h2 id="modal-student-name" class="text-lg font-bold text-slate-800">Juan Dela Cruz</h2>
      </div>
      <button type="button" onclick="closeReviewModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center font-bold text-sm transition">
        ✕
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-6 overflow-y-auto space-y-4 flex-1 text-xs">
      <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
        <div>
          <span class="text-slate-400 font-semibold block uppercase text-[10px]">Student ID Number</span>
          <span id="modal-student-id" class="font-mono font-bold text-blue-700 text-sm">2026-00123</span>
        </div>
        <div>
          <span class="text-slate-400 font-semibold block uppercase text-[10px]">Enrolled Course</span>
          <span id="modal-course-name" class="font-bold text-slate-800 text-sm">IT301 — Web Development 2</span>
        </div>
        <div>
          <span class="text-slate-400 font-semibold block uppercase text-[10px]">Absence Period</span>
          <span id="modal-dates" class="font-bold text-slate-800">Sep 5–6, 2026</span>
        </div>
        <div>
          <span class="text-slate-400 font-semibold block uppercase text-[10px]">Submitted By</span>
          <span id="modal-submitted-by" class="font-bold text-slate-800">Parent (Maria Dela Cruz)</span>
        </div>
      </div>

      <!-- Detailed Reason -->
      <div>
        <label class="block font-semibold text-slate-700 mb-1">Stated Reason for Absence:</label>
        <div id="modal-reason" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 leading-relaxed">
          High fever and viral flu. Medical certificate from QC General Hospital attached.
        </div>
      </div>

      <!-- Attached File Preview -->
      <div>
        <label class="block font-semibold text-slate-700 mb-1">Supporting Medical / Excuse Certificate:</label>
        <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl flex items-center justify-between">
          <div class="flex items-center gap-2 text-emerald-800 font-semibold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span id="modal-file-name">medical_cert_delacruz.pdf</span>
          </div>
          <button type="button" onclick="APP.toast('Opening verified attachment certificate...', 'info')" class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs shadow-xs transition">
            Preview File
          </button>
        </div>
      </div>

      <!-- Teacher Review Notes -->
      <div>
        <label for="review-decision-notes" class="block font-semibold text-slate-700 mb-1">Instructor Review Notes / Comments (optional):</label>
        <textarea id="review-decision-notes" rows="2" placeholder="e.g. Valid medical certificate verified. Absences excused in class gradebook." class="w-full p-2.5 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between gap-3 shrink-0">
      <button type="button" onclick="closeReviewModal()" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition">
        Cancel
      </button>

      <div class="flex items-center gap-2">
        <button type="button" onclick="rejectExcuseSlip()" class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          <span>Reject Slip</span>
        </button>
        <button type="button" onclick="approveExcuseSlip()" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Approve &amp; Excuse Absences</span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function filterSlips() {
  const status = document.getElementById('filter-status').value;
  const section = document.getElementById('filter-class').value;
  const query = document.getElementById('search-student').value.toLowerCase().trim();

  const rows = document.querySelectorAll('.slip-row');
  let visible = 0;

  rows.forEach(row => {
    const rStatus = row.getAttribute('data-status');
    const rSection = row.getAttribute('data-section');
    const rText = row.getAttribute('data-text').toLowerCase();

    const matchStatus = (status === 'all' || rStatus === status);
    const matchSection = (section === 'all' || rSection === section);
    const matchQuery = (!query || rText.includes(query));

    if (matchStatus && matchSection && matchQuery) {
      row.style.display = '';
      visible++;
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('visible-count').textContent = visible;
}

function openReviewModal(name, id, section, course, dates, reason, submittedBy, file, status) {
  document.getElementById('modal-student-name').textContent = name;
  document.getElementById('modal-student-id').textContent = id;
  document.getElementById('modal-section-badge').textContent = section;
  document.getElementById('modal-course-name').textContent = course;
  document.getElementById('modal-dates').textContent = dates;
  document.getElementById('modal-reason').textContent = reason;
  document.getElementById('modal-submitted-by').textContent = submittedBy;
  document.getElementById('modal-file-name').textContent = file;

  const statusBadge = document.getElementById('modal-status-badge');
  if (status === 'approved') {
    statusBadge.className = 'badge badge-present font-bold text-xs';
    statusBadge.textContent = '✓ Approved';
  } else if (status === 'rejected') {
    statusBadge.className = 'badge badge-absent font-bold text-xs';
    statusBadge.textContent = '✕ Rejected';
  } else {
    statusBadge.className = 'badge badge-pending font-bold text-xs';
    statusBadge.textContent = '⏳ Pending Review';
  }

  document.getElementById('review-decision-notes').value = '';
  document.getElementById('review-modal').classList.remove('hidden');
}

function closeReviewModal() {
  document.getElementById('review-modal').classList.add('hidden');
}

function approveExcuseSlip() {
  const student = document.getElementById('modal-student-name').textContent;
  closeReviewModal();
  APP.toast('Approved excuse slip for ' + student + '. Absences marked as Excused.', 'success');
}

function rejectExcuseSlip() {
  const student = document.getElementById('modal-student-name').textContent;
  closeReviewModal();
  APP.toast('Excuse slip for ' + student + ' has been rejected.', 'info');
}

// Initial filter on page load
document.addEventListener('DOMContentLoaded', () => {
  filterSlips();
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
