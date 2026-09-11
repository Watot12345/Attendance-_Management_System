<?php
$page_title = 'Excuse Slips';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

// Fetch current student excuse slips from database (Juan Dela Cruz, student_id = 1)
$studentId = 1;
$slips = [];

try {
    $db = Database::getConnection();
    $stmt = $db->prepare("
        SELECT 
            es.excuse_slip_id,
            es.student_id,
            es.teacher_id,
            es.subject,
            es.date_of_absence,
            es.reason,
            es.explanation,
            es.status,
            es.declined_reason,
            es.supporting_document,
            es.created_at,
            CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
        FROM excuse_slips es
        LEFT JOIN users t ON es.teacher_id = t.user_id
        WHERE es.student_id = ?
        ORDER BY es.created_at DESC, es.excuse_slip_id DESC
    ");
    $stmt->execute([$studentId]);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $slips = [];
}

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
            <span class="badge badge-present">Student Portal</span>
            <span class="text-xs text-slate-500 font-medium">Juan Dela Cruz • BSIT 3-A</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">Excuse Slips &amp; Absence Clearance</h1>
          <p class="text-sm text-slate-500">Submit justifications, manage requests, and track approval status with Supabase Cloud Storage.</p>
        </div>

      </div>      <!-- Main Content Grid -->
      <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        <!-- Submit Form (4 cols on xl, full width on smaller) -->
        <div class="xl:col-span-4 bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
          <div class="flex items-center justify-between mb-1">
            <h2 class="font-bold text-slate-800 text-base">Submit New Excuse Slip</h2>
            <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100">Step 1</span>
          </div>
          <p class="text-xs text-slate-500 mb-4">Your course instructor will review the attached certificate and update attendance records.</p>

          <form id="excuse-slip-form" onsubmit="event.preventDefault(); submitExcuseSlip();" class="space-y-4">
            <input type="hidden" id="excuse-student-id" value="<?php echo $studentId; ?>">

            <!-- Send to All Subject Teachers Checkbox Banner -->
            <div class="p-3 rounded-xl bg-gradient-to-r from-indigo-50/90 to-blue-50/70 border border-indigo-200/90 shadow-2xs">
              <label class="flex items-start gap-2.5 cursor-pointer select-none">
                <input type="checkbox" id="send-to-all-teachers" name="send_to_all" value="1" onchange="toggleSendToAllTeachers(this.checked)" class="mt-0.5 rounded text-indigo-600 focus:ring-indigo-500 w-4 h-4 cursor-pointer accent-indigo-600">
                <div class="flex-1">
                  <div class="flex items-center gap-1.5 font-bold text-xs text-indigo-950 flex-wrap">
                    <span>Send to All Subject Teachers</span>
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-indigo-200 text-indigo-800">Whole Day Absence</span>
                  </div>
                  <p class="text-[11px] text-indigo-700/90 mt-0.5 leading-snug">
                    Submit once to deliver your excuse slip and documents to all course instructors at the same time.
                  </p>
                </div>
              </label>
            </div>

            <!-- Subject Selection -->
            <div>
              <div class="flex items-center justify-between mb-1.5">
                <label for="excuse-subject" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Class / Subject *</label>
                <span id="subject-all-indicator" class="hidden text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                  All 3 Classes Selected
                </span>
              </div>
              <select id="excuse-subject" name="subject" onchange="handleSubjectDropdownChange(this.value)" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <option value="">Select subject or send to all...</option>
                <option value="ALL" class="font-bold text-indigo-700 bg-indigo-50/70">★ All Subject Teachers (All 3 Enrolled Classes)</option>
                <option disabled>────────────────────────────</option>
                <option value="IT301 — Web Development 2 (Prof. Ramirez)">IT301 — Web Development 2 (Prof. Ramirez)</option>
                <option value="IT302 — Database Systems 2 (Prof. Ramirez)">IT302 — Database Systems 2 (Prof. Ramirez)</option>
                <option value="IT303 — Systems Integration (Prof. Santos)">IT303 — Systems Integration (Prof. Santos)</option>
              </select>

              <!-- Recipient Teachers Summary Pill List -->
              <div id="recipient-teachers-box" class="mt-2 p-2 rounded-lg bg-slate-50 border border-slate-200/80 text-[11px] text-slate-600 space-y-1">
                <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Faculty receiving this request:</div>
                <div id="recipient-pills" class="flex flex-wrap gap-1.5">
                  <span id="pill-ramirez" class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 font-medium transition-opacity">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    Prof. Manuel Ramirez (IT301, IT302)
                  </span>
                  <span id="pill-santos" class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-medium transition-opacity">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    Prof. Jose Santos (IT303)
                  </span>
                </div>
              </div>
            </div>

            <!-- Date of Absence -->
            <div>
              <label for="excuse-date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Date of Absence *</label>
              <input type="date" id="excuse-date" name="date_of_absence" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
            </div>

            <!-- Reason Category -->
            <div>
              <label for="excuse-category" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Reason Category *</label>
              <select id="excuse-category" name="reason" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <option value="Medical / Illness (with doctor's note)">Medical / Illness (with doctor's note)</option>
                <option value="Family / Personal Emergency">Family / Personal Emergency</option>
                <option value="Official Institutional Activity / Competition">Official Institutional Activity / Competition</option>
                <option value="Severe Weather / Transport Disruption">Severe Weather / Transport Disruption</option>
                <option value="Other Valid Reason">Other Valid Reason</option>
              </select>
            </div>

            <!-- Explanation -->
            <div>
              <label for="excuse-reason" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Detailed Explanation *</label>
              <textarea id="excuse-reason" name="explanation" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Please provide specific context and details for your absence..." required></textarea>
            </div>

            <!-- Supporting Document (Supabase Storage) -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Supporting Document (Image or PDF)
              </label>

              <!-- Hidden native file input -->
              <input type="file" id="excuse-file" name="document" accept="image/png,image/jpeg,image/webp,image/gif,application/pdf" class="hidden" onchange="handleFileSelected(this)">

              <!-- Drag & Drop Zone -->
              <div id="drop-zone" onclick="document.getElementById('excuse-file').click()"
                   ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleFileDrop(event)"
                   class="border-2 border-dashed border-slate-300 rounded-lg p-3 text-center hover:border-indigo-400 hover:bg-indigo-50/20 transition cursor-pointer bg-slate-50 relative group">
                <div id="upload-prompt" class="space-y-1">
                  <div class="w-8 h-8 rounded-full bg-indigo-50 text-indigo-600 flex items-center justify-center mx-auto mb-1 group-hover:scale-110 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                  </div>
                  <span class="text-xs text-slate-700 font-medium block">Click or drag &amp; drop document</span>
                  <p class="text-[10px] text-slate-400">PNG, JPG, PDF up to 10MB (Supabase)</p>
                </div>

                <!-- Selected File Preview Card -->
                <div id="file-preview-card" class="hidden text-left bg-white p-2.5 rounded-lg border border-slate-200 flex items-center justify-between gap-2 shadow-2xs">
                  <div class="flex items-center gap-2.5 overflow-hidden">
                    <div id="preview-thumbnail" class="w-9 h-9 rounded bg-slate-100 flex items-center justify-center text-slate-400 overflow-hidden shrink-0 border border-slate-200">
                    </div>
                    <div class="min-w-0">
                      <p id="preview-filename" class="text-xs font-semibold text-slate-800 truncate max-w-[140px]">document.png</p>
                      <p id="preview-filesize" class="text-[10px] text-slate-400">0 KB</p>
                    </div>
                  </div>
                  <button type="button" onclick="event.stopPropagation(); removeSelectedFile();" class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition" title="Remove file">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>
              </div>
            </div>

            <!-- Already-Pending Conflict Alert Banner -->
            <div id="already-pending-alert" class="hidden p-3 rounded-xl bg-amber-50 border border-amber-300 text-amber-900 text-xs shadow-2xs transition-all duration-200">
              <div class="flex items-start gap-2.5">
                <div class="w-6 h-6 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center shrink-0 mt-0.5 font-black text-xs">
                  !
                </div>
                <div class="flex-1 min-w-0">
                  <div class="font-bold text-amber-900 flex items-center gap-1.5 flex-wrap">
                    <span>Already Pending</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.2 rounded bg-amber-200 text-amber-800">Duplicate</span>
                  </div>
                  <div id="already-pending-msg" class="mt-0.5 text-[11px] text-amber-800 leading-snug font-medium">
                    Already pending for this date.
                  </div>
                </div>
              </div>
            </div>

            <!-- Submit Button -->
            <button type="submit" id="submit-btn" class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow transition flex items-center justify-center gap-2 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
              <span id="btn-text">Submit Excuse Slip for Review</span>
              <svg id="btn-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              <svg id="btn-spinner" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
            </button>
          </form>
        </div>

        <!-- Excuse Slips 2-Column Grid & Pagination (8 cols on xl, full on smaller) -->
        <div class="xl:col-span-8 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
            <div>
              <div class="flex items-center gap-2">
                <h2 class="font-bold text-slate-800 text-base">Submitted Requests &amp; Status</h2>
                <span id="cache-indicator" class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-500" title="Client cache active">⚡ Cached</span>
              </div>
              <p class="text-xs text-slate-400 mt-0.5">2-Column view with live Supabase document modals &amp; actions</p>
            </div>

            <!-- Filter / Search Row -->
            <div class="flex items-center gap-2">
              <select id="filter-status" onchange="applyFilters()" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="all">All Statuses</option>
                <option value="pending">Pending Review</option>
                <option value="approved">Approved</option>
                <option value="declined">Declined</option>
              </select>
              <span id="record-counter" class="text-xs font-semibold px-2.5 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-100 shrink-0">
                0 records
              </span>
            </div>
          </div>

          <!-- 2-COLUMN CARDS GRID -->
          <div id="slips-container" class="grid grid-cols-1 md:grid-cols-2 gap-4 min-h-[200px]">
            <!-- JavaScript Populates 2-Column Cards Here -->
          </div>

          <!-- Empty State -->
          <div id="empty-state" class="hidden p-12 text-center border-2 border-dashed border-slate-200 rounded-xl my-4">
            <div class="w-12 h-12 rounded-full bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-2">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <p class="text-sm font-semibold text-slate-700">No excuse slips found</p>
            <p class="text-xs text-slate-400 mt-1">Submit your first slip on the left to request an absence clearance.</p>
          </div>

          <!-- PAGINATION CONTROLS -->
          <div id="pagination-wrapper" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-5 mt-4 border-t border-slate-100">
            <div id="pagination-info" class="text-xs text-slate-500">
              Showing page <strong id="current-page-num" class="text-slate-800">1</strong> of <strong id="total-pages-num" class="text-slate-800">1</strong>
            </div>

            <div class="flex items-center gap-1.5">
              <button type="button" id="btn-prev-page" onclick="changePage(-1)" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-600 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Prev</span>
              </button>

              <div id="pagination-buttons" class="flex items-center gap-1">
                <!-- Page buttons injected via JS -->
              </div>

              <button type="button" id="btn-next-page" onclick="changePage(1)" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-xs font-semibold text-slate-600 disabled:opacity-40 disabled:cursor-not-allowed transition flex items-center gap-1 cursor-pointer">
                <span>Next</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 1. IMAGE / DOCUMENT PREVIEW MODAL                                         -->
<!-- ========================================================================= -->
<div id="doc-preview-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden" onclick="if(event.target === this) closeImageModal();">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-2xl w-full overflow-hidden flex flex-col max-h-[90vh]">
    <!-- Modal Header -->
    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
      <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <h3 id="modal-doc-title" class="font-bold text-slate-800 text-sm">Supporting Medical Document</h3>
          <span class="text-[10px] text-slate-400">Hosted securely on Supabase Cloud Storage</span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a id="modal-doc-external-link" href="#" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition inline-flex items-center gap-1">
          <span>Open Full</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
        <button type="button" onclick="closeImageModal()" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs transition cursor-pointer">
          
        </button>
      </div>
    </div>

    <!-- Modal Image Body -->
    <div class="p-5 overflow-auto flex items-center justify-center bg-slate-900/5 min-h-[280px]">
      <div id="modal-img-spinner" class="hidden text-center">
        <svg class="w-8 h-8 animate-spin text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        <span class="text-xs text-slate-400 mt-2 block">Loading document from Supabase...</span>
      </div>
      <img id="modal-doc-img" src="" alt="Certificate Preview" class="max-h-[65vh] w-auto max-w-full rounded-lg shadow-sm border border-slate-200 object-contain">
    </div>

    <!-- Modal Footer -->
    <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs">
      <span id="modal-doc-slip-ref" class="text-slate-500 font-mono">Slip #---</span>
      <button type="button" onclick="closeImageModal()" class="px-4 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold transition cursor-pointer">
        Close Preview
      </button>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- 2. EDIT / UPDATE EXCUSE SLIP MODAL                                        -->
<!-- ========================================================================= -->
<div id="edit-slip-modal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm hidden" onclick="if(event.target === this) closeEditModal();">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-lg w-full overflow-hidden flex flex-col max-h-[90vh]">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50">
      <div>
        <h3 class="font-bold text-slate-800 text-base">Update Excuse Slip</h3>
        <p class="text-xs text-slate-500">Edit details or upload a replacement certificate to Supabase</p>
      </div>
      <button type="button" onclick="closeEditModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs transition cursor-pointer">
        
      </button>
    </div>

    <form id="edit-slip-form" onsubmit="event.preventDefault(); submitEditSlip();" class="p-6 overflow-y-auto space-y-4 text-xs">
      <input type="hidden" id="edit-slip-id">

      <!-- Subject -->
      <div>
        <label for="edit-subject" class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Class / Subject *</label>
        <select id="edit-subject" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
          <option value="IT301 — Web Development 2 (Prof. Ramirez)">IT301 — Web Development 2 (Prof. Ramirez)</option>
          <option value="IT302 — Database Systems 2 (Prof. Ramirez)">IT302 — Database Systems 2 (Prof. Ramirez)</option>
          <option value="IT303 — Systems Integration (Prof. Santos)">IT303 — Systems Integration (Prof. Santos)</option>
        </select>
      </div>

      <!-- Date of Absence -->
      <div>
        <label for="edit-date" class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Date of Absence *</label>
        <input type="date" id="edit-date" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required min="<?php echo date('Y-m-d'); ?>">
      </div>

      <!-- Reason Category -->
      <div>
        <label for="edit-category" class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Reason Category *</label>
        <select id="edit-category" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
          <option value="Medical / Illness (with doctor's note)">Medical / Illness (with doctor's note)</option>
          <option value="Family / Personal Emergency">Family / Personal Emergency</option>
          <option value="Official Institutional Activity / Competition">Official Institutional Activity / Competition</option>
          <option value="Severe Weather / Transport Disruption">Severe Weather / Transport Disruption</option>
          <option value="Other Valid Reason">Other Valid Reason</option>
        </select>
      </div>

      <!-- Explanation -->
      <div>
        <label for="edit-reason" class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Detailed Explanation *</label>
        <textarea id="edit-reason" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required></textarea>
      </div>

      <!-- Current Document Info & Replacement -->
      <div>
        <label class="block font-semibold text-slate-700 uppercase tracking-wider mb-1">Supporting Document</label>
        <div id="edit-current-doc" class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg flex items-center justify-between mb-2">
          <span class="text-slate-600 font-medium truncate max-w-[240px]" id="edit-current-doc-name">Document attached</span>
          <span class="text-[10px] text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded font-bold">Supabase</span>
        </div>
        <div class="text-[11px] text-slate-500 mb-1">Replace document (optional):</div>
        <input type="file" id="edit-file" accept="image/*,application/pdf" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
      </div>

      <!-- Action Buttons -->
      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold transition cursor-pointer">
          Cancel
        </button>
        <button type="submit" id="edit-save-btn" class="px-5 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold shadow transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60">
          <span id="edit-btn-text">Save Changes</span>
          <svg id="edit-btn-spinner" class="w-4 h-4 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
        </button>
      </div>
    </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- JAVASCRIPT: PAGINATION, CACHING, LAZY-IN-VIEW, MODALS, CRUD               -->
<!-- ========================================================================= -->
<script>
// --- CLIENT CACHING MANAGER ---
const SlipCache = {
  KEY: 'AMS_EXCUSE_SLIPS_CACHE_V2',
  TTL_MS: 30 * 1000,
  get() {
    try {
      const cached = sessionStorage.getItem(this.KEY);
      if (!cached) return null;
      const parsed = JSON.parse(cached);
      if (Date.now() - parsed.timestamp > this.TTL_MS) {
        sessionStorage.removeItem(this.KEY);
        return null;
      }
      return parsed.data;
    } catch (e) {
      return null;
    }
  },
  set(data) {
    try {
      sessionStorage.setItem(this.KEY, JSON.stringify({
        timestamp: Date.now(),
        data: data
      }));
    } catch (e) {}
  },
  invalidate() {
    sessionStorage.removeItem(this.KEY);
  }
};

let allSlips = <?php echo json_encode($slips); ?>;
let filteredSlips = [...allSlips];
let currentPage = 1;
const PAGE_SIZE = 4;
let deletingSlipId = null;

document.addEventListener('DOMContentLoaded', () => {
  const today = new Date().toISOString().split('T')[0];
  const excuseDateInput = document.getElementById('excuse-date');
  if (excuseDateInput) {
    excuseDateInput.min = today;
    if (!excuseDateInput.value) excuseDateInput.value = today;
    excuseDateInput.addEventListener('change', checkPendingConflict);
    excuseDateInput.addEventListener('input', checkPendingConflict);
  }
  const editDateInput = document.getElementById('edit-date');
  if (editDateInput) {
    editDateInput.min = today;
  }

  // Always prioritize fresh live database data from page render
  if (allSlips && Array.isArray(allSlips) && allSlips.length > 0) {
    SlipCache.set(allSlips);
    const badge = document.getElementById('cache-indicator');
    if (badge) badge.textContent = '⚡ Live Database';
  } else {
    const cached = SlipCache.get();
    if (cached && Array.isArray(cached) && cached.length > 0) {
      allSlips = cached;
      const badge = document.getElementById('cache-indicator');
      if (badge) badge.textContent = '⚡ Cached';
    }
  }
  applyFilters();
  checkPendingConflict();
});

async function refreshSlips(force = false) {
  if (!force) {
    const cached = SlipCache.get();
    if (cached) {
      allSlips = cached;
      applyFilters();
      return;
    }
  }

  try {
    const endpoint = window.url ? window.url('api/excuses/list?student_id=1') : '/api/excuses/list?student_id=1';
    const res = await fetch(endpoint);
    const json = await res.json();
    if (json.status === 'success' && Array.isArray(json.data)) {
      allSlips = json.data;
      SlipCache.set(allSlips);
      const badge = document.getElementById('cache-indicator');
      if (badge) badge.textContent = force ? '🔄 Refreshed' : '⚡ Live Database';
      applyFilters();
      if (force && window.APP?.toast) {
        APP.toast('Excuse slips refreshed from database.', 'info');
      }
    }
  } catch (err) {
    console.error('Failed to refresh slips:', err);
  }
}

function applyFilters() {
  const status = document.getElementById('filter-status').value;
  if (status === 'all') {
    filteredSlips = [...allSlips];
  } else if (status === 'declined') {
    filteredSlips = allSlips.filter(s => {
      const st = (s.status || '').toLowerCase();
      return st === 'declined' || st === 'rejected';
    });
  } else {
    filteredSlips = allSlips.filter(s => (s.status || '').toLowerCase() === status.toLowerCase());
  }

  const counter = document.getElementById('record-counter');
  if (counter) {
    counter.textContent = `${filteredSlips.length} record${filteredSlips.length === 1 ? '' : 's'}`;
  }

  currentPage = 1;
  renderCurrentPage();
}

function renderCurrentPage() {
  const container = document.getElementById('slips-container');
  const emptyState = document.getElementById('empty-state');
  const paginationWrapper = document.getElementById('pagination-wrapper');

  container.innerHTML = '';

  if (filteredSlips.length === 0) {
    emptyState.classList.remove('hidden');
    paginationWrapper.classList.add('hidden');
    return;
  }

  emptyState.classList.add('hidden');
  paginationWrapper.classList.remove('hidden');

  const totalPages = Math.ceil(filteredSlips.length / PAGE_SIZE) || 1;
  if (currentPage > totalPages) currentPage = totalPages;
  if (currentPage < 1) currentPage = 1;

  const startIdx = (currentPage - 1) * PAGE_SIZE;
  const pageItems = filteredSlips.slice(startIdx, startIdx + PAGE_SIZE);

  pageItems.forEach((slip) => {
    const card = createSlipCardElement(slip);
    container.appendChild(card);
  });

  document.getElementById('current-page-num').textContent = currentPage;
  document.getElementById('total-pages-num').textContent = totalPages;

  document.getElementById('btn-prev-page').disabled = currentPage <= 1;
  document.getElementById('btn-next-page').disabled = currentPage >= totalPages;

  renderPaginationButtons(totalPages);
}

function renderPaginationButtons(totalPages) {
  const btnContainer = document.getElementById('pagination-buttons');
  btnContainer.innerHTML = '';

  for (let i = 1; i <= totalPages; i++) {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.textContent = i;
    btn.className = `w-7 h-7 rounded-lg text-xs font-semibold transition cursor-pointer ${
      i === currentPage
        ? 'bg-indigo-600 text-white shadow-2xs'
        : 'bg-slate-100 hover:bg-slate-200 text-slate-700'
    }`;
    btn.onclick = () => {
      currentPage = i;
      renderCurrentPage();
    };
    btnContainer.appendChild(btn);
  }
}

function changePage(delta) {
  currentPage += delta;
  renderCurrentPage();
}

function createSlipCardElement(slip) {
  const card = document.createElement('div');
  card.id = `slip-card-${slip.excuse_slip_id}`;
  card.setAttribute('data-slip-id', slip.excuse_slip_id);
  card.className = 'bg-white rounded-xl border p-4 shadow-2xs transition-all duration-300 hover:shadow-md flex flex-col justify-between relative overflow-hidden';

  const status = (slip.status || 'pending').toLowerCase();
  const isDeclined = (status === 'declined' || status === 'rejected');
  const isApproved = (status === 'approved');

  let badgeClass = 'bg-amber-100 text-amber-800 border-amber-200';
  let borderClass = 'border-amber-200 bg-amber-50/20';
  let statusText = 'PENDING REVIEW';

  if (isApproved) {
    badgeClass = 'bg-emerald-100 text-emerald-800 border-emerald-200';
    borderClass = 'border-emerald-200 bg-emerald-50/20';
    statusText = 'APPROVED';
  } else if (isDeclined) {
    badgeClass = 'bg-rose-100 text-rose-800 border-rose-200';
    borderClass = 'border-rose-300 bg-rose-50/30';
    statusText = 'DECLINED';
  }

  borderClass.split(' ').forEach(cls => card.classList.add(cls));

  const absenceFormatted = new Date(slip.date_of_absence + 'T00:00:00').toLocaleDateString('en-US', {
    month: 'short',
    day: '2-digit',
    year: 'numeric'
  });

  const hasDoc = Boolean(slip.supporting_document);
  const docAction = hasDoc
    ? `<button type="button" onclick="openImageModal('${escapeJsStr(slip.supporting_document)}', 'Slip #${slip.excuse_slip_id}')" class="inline-flex items-center gap-1.5 px-2 py-1 rounded-md bg-white border border-indigo-200 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 font-semibold transition shadow-2xs text-[11px] cursor-pointer">
         <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
         <span>View Doc</span>
       </button>`
    : `<span class="text-[11px] text-slate-400 italic">No document</span>`;

  const isPending = status === 'pending';
  const editBtn = isPending
    ? `<button type="button" onclick="openEditModal(${slip.excuse_slip_id})" class="p-1 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition cursor-pointer" title="Edit Slip">
         <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
       </button>`
    : '';

  const deleteBtn = `<button type="button" onclick="openDeleteModal(${slip.excuse_slip_id})" class="p-1 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition cursor-pointer" title="Delete Slip">
       <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
     </button>`;

  // Prominent Declined Reason / Feedback Message Banner
  let declinedBanner = '';
  if (isDeclined) {
    const declineText = slip.declined_reason ? escapeHtml(slip.declined_reason) : 'Declined by instructor during review.';
    const reviewerLabel = slip.teacher_name ? `Instructor Feedback (${escapeHtml(slip.teacher_name)})` : 'Decline Reason';
    declinedBanner = `
      <div class="mb-3 p-2.5 rounded-lg bg-rose-50 border border-rose-200/90 text-rose-900 text-[11px] leading-relaxed shadow-2xs">
        <div class="flex items-center gap-1.5 font-bold text-rose-700 mb-1">
          <svg class="w-3.5 h-3.5 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          <span>${reviewerLabel}:</span>
        </div>
        <div class="pl-5 font-semibold text-rose-900 bg-white/80 p-1.5 rounded border border-rose-100">
          "${declineText}"
        </div>
      </div>
    `;
  }

  // Approval clearance banner
  let approvedBanner = '';
  if (isApproved) {
    approvedBanner = `
      <div class="mb-3 px-2.5 py-1.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 text-[11px] flex items-center gap-1.5 font-medium shadow-2xs">
        <svg class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>Absence cleared &amp; marked as Excused in official records.</span>
      </div>
    `;
  }

  card.innerHTML = `
    <div>
      <div class="flex items-center justify-between gap-1 mb-2">
        <span class="px-2 py-0.5 rounded text-[10px] font-bold border ${badgeClass}">
          ${statusText}
        </span>
        <div class="flex items-center gap-1">
          <span class="text-[10px] font-mono text-slate-400 mr-0.5">#${slip.excuse_slip_id}</span>
          ${editBtn}
          ${deleteBtn}
        </div>
      </div>

      <h3 class="font-bold text-slate-800 text-xs leading-snug line-clamp-1 mb-1" title="${escapeHtml(slip.subject)}">
        ${escapeHtml(slip.subject)}
      </h3>
      <div class="text-[11px] text-slate-500 mb-2">
        <span>📅 ${absenceFormatted}</span> &bull; <span class="font-medium text-slate-700">${escapeHtml(slip.reason)}</span>
      </div>

      <div class="mb-2.5">
        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider block mb-0.5">Your Explanation:</span>
        <p class="text-[11px] text-slate-600 bg-white/90 p-2 rounded-md border border-slate-200/70 line-clamp-2" title="${escapeHtml(slip.explanation)}">
          "${escapeHtml(slip.explanation)}"
        </p>
      </div>

      ${declinedBanner}
      ${approvedBanner}
    </div>

    <div class="pt-2 border-t border-slate-200/60 flex items-center justify-between gap-2">
      ${docAction}
      <span class="text-[10px] text-slate-400">
        ${new Date(slip.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
      </span>
    </div>
  `;

  return card;
}

// --- IMAGE / DOCUMENT MODAL HANDLERS ---
function openImageModal(imgUrl, slipRef = 'Excuse Document') {
  const modal = document.getElementById('doc-preview-modal');
  const img = document.getElementById('modal-doc-img');
  const spinner = document.getElementById('modal-img-spinner');
  const title = document.getElementById('modal-doc-title');
  const extLink = document.getElementById('modal-doc-external-link');
  const refText = document.getElementById('modal-doc-slip-ref');

  title.textContent = `Document — ${slipRef}`;
  refText.textContent = slipRef;
  extLink.href = imgUrl;

  spinner.classList.remove('hidden');
  img.classList.add('hidden');

  img.onload = () => {
    spinner.classList.add('hidden');
    img.classList.remove('hidden');
  };
  img.onerror = () => {
    spinner.classList.add('hidden');
    img.classList.remove('hidden');
  };

  img.src = imgUrl;
  modal.classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeImageModal() {
  document.getElementById('doc-preview-modal').classList.add('hidden');
  document.body.style.overflow = '';
}

// --- EDIT SLIP MODAL HANDLERS ---
function openEditModal(slipId) {
  const slip = allSlips.find(s => Number(s.excuse_slip_id) === Number(slipId));
  if (!slip) return;

  document.getElementById('edit-slip-id').value = slip.excuse_slip_id;
  document.getElementById('edit-subject').value = slip.subject;
  const editDateEl = document.getElementById('edit-date');
  editDateEl.value = slip.date_of_absence;
  editDateEl.min = new Date().toISOString().split('T')[0];
  document.getElementById('edit-category').value = slip.reason;
  document.getElementById('edit-reason').value = slip.explanation;

  const docEl = document.getElementById('edit-current-doc-name');
  if (slip.supporting_document) {
    const filename = slip.supporting_document.split('/').pop();
    docEl.textContent = filename || 'Attached Document';
  } else {
    docEl.textContent = 'No document currently attached';
  }

  document.getElementById('edit-file').value = '';
  document.getElementById('edit-slip-modal').classList.remove('hidden');
  document.body.style.overflow = 'hidden';
}

function closeEditModal() {
  document.getElementById('edit-slip-modal').classList.add('hidden');
  document.body.style.overflow = '';
}

async function submitEditSlip() {
  const slipId = document.getElementById('edit-slip-id').value;
  const subject = document.getElementById('edit-subject').value.trim();
  const dateOfAbsence = document.getElementById('edit-date').value.trim();
  const category = document.getElementById('edit-category').value.trim();
  const reason = document.getElementById('edit-reason').value.trim();
  const fileInput = document.getElementById('edit-file');

  const saveBtn = document.getElementById('edit-save-btn');
  const btnText = document.getElementById('edit-btn-text');
  const btnSpinner = document.getElementById('edit-btn-spinner');

  saveBtn.disabled = true;
  btnText.textContent = 'Saving...';
  btnSpinner.classList.remove('hidden');

  const formData = new FormData();
  formData.append('excuse_slip_id', slipId);
  formData.append('student_id', '1');
  formData.append('subject', subject);
  formData.append('date_of_absence', dateOfAbsence);
  formData.append('reason', category);
  formData.append('explanation', reason);

  if (fileInput.files && fileInput.files[0]) {
    formData.append('document', fileInput.files[0]);
  }

  try {
    const endpoint = window.url ? window.url('api/excuses/update') : '/api/excuses/update';
    const res = await fetch(endpoint, {
      method: 'POST',
      body: formData,
    });
    const json = await res.json();

    if (!res.ok || json.status !== 'success') {
      throw new Error(json.message || 'Failed to update excuse slip.');
    }

    SlipCache.invalidate();

    const idx = allSlips.findIndex(s => Number(s.excuse_slip_id) === Number(slipId));
    if (idx !== -1 && json.data) {
      allSlips[idx] = { ...allSlips[idx], ...json.data };
    }

    closeEditModal();
    applyFilters();

    if (window.APP?.toast) {
      APP.toast('Excuse slip updated successfully!', 'success');
    }
  } catch (err) {
    console.error('Update Error:', err);
    if (window.APP?.toast) {
      APP.toast(err.message || 'Failed to update excuse slip.', 'error');
    }
  } finally {
    saveBtn.disabled = false;
    btnText.textContent = 'Save Changes';
    btnSpinner.classList.add('hidden');
  }
}

// --- DELETE SLIP ACTION (POWERED BY REUSABLE CONFIRMATION MODAL & LOADING EFFECT) ---
async function openDeleteModal(slipId) {
  const slip = allSlips.find(s => Number(s.excuse_slip_id) === Number(slipId));
  const slipSubject = slip ? escapeHtml(slip.subject) : '';

  APP.confirm({
    title: 'Delete Excuse Slip?',
    message: `Are you sure you want to delete <strong class="text-slate-800 font-mono">Slip #${slipId}</strong>${slipSubject ? ` (${slipSubject})` : ''}?<br><span class="text-xs text-slate-400 mt-1 block">This will permanently remove the record and any document in Supabase Storage.</span>`,
    type: 'danger',
    confirmText: 'Yes, Delete',
    confirmLoadingText: 'Deleting Slip...',
    cancelText: 'Cancel',
    async onConfirm() {
      // 1. Loading effect on the specific slip card
      const cardEl = document.getElementById(`slip-card-${slipId}`);
      let loadingOverlay = null;

      if (cardEl) {
        cardEl.classList.add('pointer-events-none', 'ring-2', 'ring-rose-400');
        loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'absolute inset-0 bg-white/85 backdrop-blur-xs flex flex-col items-center justify-center gap-2 z-20 transition-opacity duration-200';
        loadingOverlay.innerHTML = `
          <svg class="w-6 h-6 animate-spin text-rose-600" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          <span class="text-[11px] font-semibold text-rose-700 animate-pulse">Deleting from Supabase...</span>
        `;
        cardEl.appendChild(loadingOverlay);
      }

      // 2. Loading toaster notification
      if (window.APP?.toast) {
        APP.toast(`Deleting excuse slip #${slipId}...`, 'info', 2500);
      }

      try {
        const endpoint = window.url ? window.url('api/excuses/delete') : '/api/excuses/delete';
        const formData = new FormData();
        formData.append('excuse_slip_id', slipId);
        formData.append('student_id', '1');

        const res = await fetch(endpoint, {
          method: 'POST',
          body: formData,
        });
        const json = await res.json();

        if (!res.ok || json.status !== 'success') {
          throw new Error(json.message || 'Failed to delete excuse slip.');
        }

        // 3. Smooth removal animation on card
        if (cardEl) {
          cardEl.style.transform = 'scale(0.9) translateY(10px)';
          cardEl.style.opacity = '0';
          await new Promise(r => setTimeout(r, 260));
        }

        // 4. Update data state and cache
        SlipCache.invalidate();
        allSlips = allSlips.filter(s => Number(s.excuse_slip_id) !== Number(slipId));
        applyFilters();

        // 5. Success toaster notification
        if (window.APP?.toast) {
          APP.toast(`Excuse slip #${slipId} deleted successfully.`, 'success', 4000);
        }

      } catch (err) {
        console.error('Delete Error:', err);
        // Restore card if failed
        if (cardEl) {
          cardEl.classList.remove('pointer-events-none', 'ring-2', 'ring-rose-400');
          if (loadingOverlay) loadingOverlay.remove();
        }
        // Error toaster notification
        if (window.APP?.toast) {
          APP.toast(err.message || 'Failed to delete excuse slip.', 'error', 5000);
        }
        throw err;
      }
    }
  });
}

// --- SUBMIT NEW EXCUSE SLIP ---
function handleDragOver(e) {
  e.preventDefault();
  e.stopPropagation();
  document.getElementById('drop-zone').classList.add('border-indigo-500', 'bg-indigo-50/40');
}

function handleDragLeave(e) {
  e.preventDefault();
  e.stopPropagation();
  document.getElementById('drop-zone').classList.remove('border-indigo-500', 'bg-indigo-50/40');
}

function handleFileDrop(e) {
  e.preventDefault();
  e.stopPropagation();
  document.getElementById('drop-zone').classList.remove('border-indigo-500', 'bg-indigo-50/40');
  
  if (e.dataTransfer && e.dataTransfer.files.length > 0) {
    const fileInput = document.getElementById('excuse-file');
    fileInput.files = e.dataTransfer.files;
    handleFileSelected(fileInput);
  }
}

function handleFileSelected(input) {
  const file = input.files && input.files[0];
  if (!file) return;

  const promptEl = document.getElementById('upload-prompt');
  const previewCard = document.getElementById('file-preview-card');
  const filenameEl = document.getElementById('preview-filename');
  const filesizeEl = document.getElementById('preview-filesize');
  const thumbnailEl = document.getElementById('preview-thumbnail');

  filenameEl.textContent = file.name;
  filesizeEl.textContent = formatBytes(file.size);

  if (file.type.startsWith('image/')) {
    const reader = new FileReader();
    reader.onload = function(e) {
      thumbnailEl.innerHTML = `<img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">`;
    };
    reader.readAsDataURL(file);
  } else {
    thumbnailEl.innerHTML = `<svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>`;
  }

  promptEl.classList.add('hidden');
  previewCard.classList.remove('hidden');
}

function removeSelectedFile() {
  const fileInput = document.getElementById('excuse-file');
  fileInput.value = '';
  document.getElementById('upload-prompt').classList.remove('hidden');
  document.getElementById('file-preview-card').classList.add('hidden');
  document.getElementById('preview-thumbnail').innerHTML = '';
}

function formatBytes(bytes, decimals = 1) {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const dm = decimals < 0 ? 0 : decimals;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

// --- MULTI-TEACHER RECIPIENT TOGGLES & CONFLICT DETECTION ---
function toggleSendToAllTeachers(isAll) {
  const subjectSelect = document.getElementById('excuse-subject');
  const allIndicator = document.getElementById('subject-all-indicator');
  const pillRamirez = document.getElementById('pill-ramirez');
  const pillSantos = document.getElementById('pill-santos');

  if (isAll) {
    subjectSelect.value = 'ALL';
    if (allIndicator) allIndicator.classList.remove('hidden');
    if (pillRamirez) pillRamirez.classList.remove('opacity-40');
    if (pillSantos) pillSantos.classList.remove('opacity-40');
  } else {
    if (subjectSelect.value === 'ALL') {
      subjectSelect.value = '';
    }
    if (allIndicator) allIndicator.classList.add('hidden');
    updateTeacherRecipientPreview(subjectSelect.value);
  }
  checkPendingConflict();
}

function handleSubjectDropdownChange(val) {
  const checkbox = document.getElementById('send-to-all-teachers');
  const allIndicator = document.getElementById('subject-all-indicator');

  if (val === 'ALL') {
    if (checkbox) checkbox.checked = true;
    if (allIndicator) allIndicator.classList.remove('hidden');
  } else {
    if (checkbox) checkbox.checked = false;
    if (allIndicator) allIndicator.classList.add('hidden');
  }
  updateTeacherRecipientPreview(val);
  checkPendingConflict();
}

function updateTeacherRecipientPreview(val) {
  const pillRamirez = document.getElementById('pill-ramirez');
  const pillSantos = document.getElementById('pill-santos');

  if (!val || val === 'ALL') {
    if (pillRamirez) pillRamirez.classList.remove('opacity-40');
    if (pillSantos) pillSantos.classList.remove('opacity-40');
  } else if (val.includes('Santos') || val.includes('IT303')) {
    if (pillRamirez) pillRamirez.classList.add('opacity-40');
    if (pillSantos) pillSantos.classList.remove('opacity-40');
  } else {
    if (pillRamirez) pillRamirez.classList.remove('opacity-40');
    if (pillSantos) pillSantos.classList.add('opacity-40');
  }
}

function checkPendingConflict() {
  const alertBox = document.getElementById('already-pending-alert');
  const alertMsg = document.getElementById('already-pending-msg');
  if (!alertBox || !alertMsg) return false;

  const dateVal = document.getElementById('excuse-date')?.value?.trim();
  const subjectVal = document.getElementById('excuse-subject')?.value?.trim();
  const isSendToAll = Boolean(document.getElementById('send-to-all-teachers')?.checked) || subjectVal === 'ALL';

  if (!dateVal || (!subjectVal && !isSendToAll) || !Array.isArray(allSlips) || allSlips.length === 0) {
    alertBox.classList.add('hidden');
    return false;
  }

  const conflicts = [];
  allSlips.forEach(s => {
    const sDate = s.date_of_absence;
    const sStatus = (s.status || '').toLowerCase();
    if (sDate === dateVal && sStatus === 'pending') {
      const isSantos = (s.subject || '').includes('Santos') || (s.subject || '').includes('IT303') || Number(s.teacher_id) === 3;
      const targetIsSantos = subjectVal.includes('Santos') || subjectVal.includes('IT303');
      const targetIsRamirez = subjectVal.includes('Ramirez') || subjectVal.includes('IT301') || subjectVal.includes('IT302');

      if (isSendToAll) {
        conflicts.push(s);
      } else if (targetIsSantos && isSantos) {
        conflicts.push(s);
      } else if (targetIsRamirez && !isSantos) {
        conflicts.push(s);
      }
    }
  });

  if (conflicts.length > 0) {
    const profNames = [...new Set(conflicts.map(c => {
      const name = c.teacher_name || (c.subject.includes('Santos') ? 'Prof. Santos' : 'Prof. Ramirez');
      return name.replace('Jose Santos', 'Santos').replace('Manuel Ramirez', 'Ramirez');
    }))].join(' & ');
    alertMsg.innerHTML = `Already pending for <strong>${escapeHtml(profNames)}</strong>.`;
    alertBox.classList.remove('hidden');
    return true;
  } else {
    alertBox.classList.add('hidden');
    return false;
  }
}

async function submitExcuseSlip() {
  const form = document.getElementById('excuse-slip-form');
  const submitBtn = document.getElementById('submit-btn');
  const btnText = document.getElementById('btn-text');
  const btnIcon = document.getElementById('btn-icon');
  const btnSpinner = document.getElementById('btn-spinner');

  const subject = document.getElementById('excuse-subject').value.trim();
  const isSendToAll = Boolean(document.getElementById('send-to-all-teachers')?.checked) || subject === 'ALL';
  const dateOfAbsence = document.getElementById('excuse-date').value.trim();
  const category = document.getElementById('excuse-category').value.trim();
  const reason = document.getElementById('excuse-reason').value.trim();
  const studentId = document.getElementById('excuse-student-id').value;
  const fileInput = document.getElementById('excuse-file');

  if (!subject && !isSendToAll) {
    APP.toast('Please select a subject or send to all.', 'warning');
    return;
  }
  if (!dateOfAbsence) {
    APP.toast('Please specify the date of absence.', 'warning');
    return;
  }
  if (!reason) {
    APP.toast('Please provide a reason explanation.', 'warning');
    return;
  }

  // Pre-check for pending conflict in local state
  if (checkPendingConflict()) {
    const alertBox = document.getElementById('already-pending-alert');
    if (alertBox) {
      alertBox.classList.add('ring-4', 'ring-amber-300');
      setTimeout(() => alertBox.classList.remove('ring-4', 'ring-amber-300'), 1500);
      alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    APP.toast('Already pending for this date.', 'warning', 3000);
    return;
  }

  submitBtn.disabled = true;
  btnText.textContent = isSendToAll ? 'Submitting to All Teachers...' : 'Uploading to Supabase & Submitting...';
  btnIcon.classList.add('hidden');
  btnSpinner.classList.remove('hidden');

  const formData = new FormData();
  formData.append('student_id', studentId);
  formData.append('subject', isSendToAll ? 'ALL' : subject);
  if (isSendToAll) {
    formData.append('send_to_all', '1');
  }
  formData.append('date_of_absence', dateOfAbsence);
  formData.append('reason', category);
  formData.append('explanation', reason);

  if (fileInput.files && fileInput.files[0]) {
    formData.append('document', fileInput.files[0]);
  }

  try {
    const endpoint = window.url ? window.url('api/excuses/submit') : '/api/excuses/submit';
    const response = await fetch(endpoint, {
      method: 'POST',
      body: formData,
    });

    const result = await response.json();

    if (!response.ok || result.status !== 'success') {
      if (result.code === 'ALREADY_PENDING' || response.status === 409) {
        const alertBox = document.getElementById('already-pending-alert');
        const alertMsg = document.getElementById('already-pending-msg');
        if (alertBox && alertMsg) {
          alertMsg.innerHTML = escapeHtml(result.message);
          alertBox.classList.remove('hidden');
          alertBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        APP.toast(result.message || 'Already pending for this date.', 'warning', 3500);
        return;
      }
      throw new Error(result.message || 'Failed to submit excuse slip.');
    }

    SlipCache.invalidate();

    const toastMsg = result.message || 'Excuse slip submitted & uploaded to Supabase successfully!';
    APP.toast(toastMsg, 'success', 5000);

    form.reset();
    removeSelectedFile();
    const today = new Date().toISOString().split('T')[0];
    const excuseDateEl = document.getElementById('excuse-date');
    if (excuseDateEl) {
      excuseDateEl.value = today;
      excuseDateEl.min = today;
    }
    const allCheckbox = document.getElementById('send-to-all-teachers');
    if (allCheckbox) allCheckbox.checked = false;
    const allIndicator = document.getElementById('subject-all-indicator');
    if (allIndicator) allIndicator.classList.add('hidden');
    const alertBox = document.getElementById('already-pending-alert');
    if (alertBox) alertBox.classList.add('hidden');
    updateTeacherRecipientPreview('');

    if (result.data) {
      if (Array.isArray(result.data)) {
        allSlips.unshift(...result.data);
      } else {
        allSlips.unshift(result.data);
      }
      applyFilters();
    }

  } catch (error) {
    console.error('Submission Error:', error);
    APP.toast(error.message || 'Error communicating with server.', 'error');
  } finally {
    submitBtn.disabled = false;
    btnText.textContent = 'Submit Excuse Slip for Review';
    btnIcon.classList.remove('hidden');
    btnSpinner.classList.add('hidden');
  }
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

function escapeJsStr(str) {
  if (!str) return '';
  return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

document.addEventListener('keydown', (e) => {
  if (e.key === 'Escape') {
    closeImageModal();
    closeEditModal();
    closeDeleteModal();
  }
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
