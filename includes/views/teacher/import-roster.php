<?php
$page_title = 'Import Class Roster';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <!-- Content Area -->
    <main class="page-body">
      <!-- Back Navigation & Title -->
      <div class="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <a href="<?php echo url('teacher/classes'); ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-teal-600 hover:text-teal-700 mb-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to My Classes</span>
          </a>
          <h1 class="text-2xl font-bold font-display text-slate-900">Import Student Roster (Excel)</h1>
          <p class="text-sm text-slate-500 mt-0.5">Enroll verified students into your assigned section. Student identities are validated against the official master database.</p>
        </div>
      </div>

      <!-- 3-Step Wizard Header (Full Width) -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-5 mb-6 w-full">
        <div class="flex items-center justify-between w-full max-w-4xl mx-auto">
          <!-- Step 1 -->
          <div class="flex items-center gap-3" id="step-indicator-1">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-teal-600 to-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm">1</span>
            <div class="text-left">
              <p class="text-xs font-bold text-teal-700 uppercase tracking-wider">Step 1</p>
              <p class="text-xs text-slate-500 font-medium">Class Details &amp; Upload</p>
            </div>
          </div>
          <div class="flex-1 h-0.5 bg-slate-200 mx-4"></div>

          <!-- Step 2 -->
          <div class="flex items-center gap-3 opacity-60" id="step-indicator-2">
            <span class="w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm">2</span>
            <div class="text-left">
              <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Step 2</p>
              <p class="text-xs text-slate-500 font-medium">Validate &amp; Preview</p>
            </div>
          </div>
          <div class="flex-1 h-0.5 bg-slate-200 mx-4"></div>

          <!-- Step 3 -->
          <div class="flex items-center gap-3 opacity-60" id="step-indicator-3">
            <span class="w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm">3</span>
            <div class="text-left">
              <p class="text-xs font-bold text-slate-600 uppercase tracking-wider">Step 3</p>
              <p class="text-xs text-slate-500 font-medium">Confirm Enrollment</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ════ STEP 1: FULL WIDTH CLASS DETAILS & EXCEL UPLOAD ════ -->
      <div id="wizard-step-1" class="bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 md:p-8 w-full">
        <form onsubmit="goToStep2(event)">
          <!-- Target Course, Year, Section, Major, Code, Title, Schedule, and Room Fields (Full Width) -->
          <div class="mb-6 p-6 bg-slate-50/80 border border-slate-200/80 rounded-2xl space-y-5 w-full">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-teal-500"></span>
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Target Class &amp; Section Details</h3>
              </div>
              <span class="text-xs text-slate-500 font-medium bg-white px-2.5 py-1 rounded-full border border-slate-200">1st Semester AY 2025–2026</span>
            </div>

              <!-- Row 1: Course (BSIT/BSIS), Year Level, Section Number, Major -->
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
                <!-- 1. Course Option (BSIT, BSIS) -->
                <div>
                  <label for="target-course-program" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Course <span class="text-red-500">*</span>
                  </label>
                  <select id="target-course-program" name="course_program" class="form-input form-select text-xs" required onchange="handleCourseChange()">
                    <option value="BSIT" selected>BSIT (Information Technology)</option>
                    <option value="BSIS">BSIS (Information Systems)</option>
                  </select>
                </div>

                <!-- 2. Year Level Option -->
                <div>
                  <label for="target-year-level" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Year Level <span class="text-red-500">*</span>
                  </label>
                  <select id="target-year-level" name="year_level" class="form-input form-select text-xs" required>
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year" selected>3rd Year</option>
                    <option value="4th Year">4th Year</option>
                  </select>
                </div>

                <!-- 3. Section (Number) -->
                <div>
                  <label for="target-section-num" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Section (Number) <span class="text-red-500">*</span>
                  </label>
                  <input type="number" id="target-section-num" name="section_num" min="1" max="99" class="form-input text-xs" placeholder="e.g. 1" value="1" required>
                </div>

                <!-- 4. Major (Option based on Course: NA, IM, IS for IT) -->
                <div>
                  <label for="target-major" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Major / Track <span class="text-red-500">*</span>
                  </label>
                  <select id="target-major" name="major" class="form-input form-select text-xs" required>
                    <option value="NA" selected>NA (Network Administration)</option>
                    <option value="IM">IM (Information Management)</option>
                    <option value="IS">IS (Information Security / Systems)</option>
                    <option value="Core">None / Core General</option>
                  </select>
                </div>
              </div>

              <!-- Row 2: Course Code & Course Title -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-1">
                <!-- 5. Course Code Input -->
                <div>
                  <label for="target-course-code" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Course Code <span class="text-red-500">*</span>
                  </label>
                  <input type="text" id="target-course-code" name="course_code" class="form-input text-xs font-mono font-bold uppercase" placeholder="e.g. IT301" value="IT301" required>
                </div>

                <!-- 6. Course Title Input -->
                <div class="sm:col-span-2">
                  <label for="target-course-title" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Course Title <span class="text-red-500">*</span>
                  </label>
                  <input type="text" id="target-course-title" name="course_title" class="form-input text-xs" placeholder="e.g. Web Systems and Technologies" value="Web Systems and Technologies" required>
                </div>
              </div>

              <!-- Row 3: Schedule Day, Scheduled Time, Room Number -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-1">
                <!-- 7a. Schedule Day (Monday to Saturday) -->
                <div>
                  <label for="target-schedule-day" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Schedule Day <span class="text-red-500">*</span>
                  </label>
                  <select id="target-schedule-day" name="schedule_day" class="form-input text-xs" required>
                    <option value="Monday" selected>Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                  </select>
                  <p class="text-[10px] text-slate-500 mt-1">Class meeting day (Monday - Saturday)</p>
                </div>

                <!-- 7b. Scheduled Time (Time input) -->
                <div>
                  <label for="target-scheduled-time" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Scheduled Time <span class="text-red-500">*</span>
                  </label>
                  <input type="time" id="target-scheduled-time" name="scheduled_time" class="form-input text-xs font-mono" value="08:00" required>
                  <p class="text-[10px] text-slate-500 mt-1">Class session start time</p>
                </div>

                <!-- 8. Room (Number) -->
                <div>
                  <label for="target-room-num" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Room (Number) <span class="text-red-500">*</span>
                  </label>
                  <input type="number" id="target-room-num" name="room_num" min="100" max="999" class="form-input text-xs font-mono" placeholder="e.g. 402" value="402" required>
                  <p class="text-[10px] text-slate-500 mt-1">Campus classroom / laboratory</p>
                </div>
              </div>

              <div class="p-2.5 bg-blue-50/70 border border-blue-200/80 rounded-lg text-[11px] text-blue-900 flex items-center gap-2">
                <span class="font-bold text-blue-800">Class Identifier Preview:</span>
                <span id="class-preview-summary" class="font-semibold text-blue-950 font-mono">BSIT 3-1 (NA) · IT301: Web Systems and Technologies (Room 402)</span>
              </div>
            </div>

            <!-- File Dropzone (Spec Section 6) -->
            <!-- File Dropzone (Spec Section 6) -->
            <div class="mb-6">
              <label class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block text-text-secondary">
                Select Excel or CSV File (.xlsx, .xls, .csv) <span class="text-red-500">*</span>
              </label>
              <div id="dropzone-area" class="border-2 border-dashed border-slate-200 hover:border-teal-400 bg-slate-50 hover:bg-teal-50/20 rounded-xl p-8 text-center cursor-pointer transition-colors"
                   onclick="document.getElementById('excel-file-input').click()"
                   ondragover="handleDragOver(event)"
                   ondragleave="handleDragLeave(event)"
                   ondrop="handleFileDrop(event)">
                <div id="dropzone-icon" class="w-12 h-12 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center mx-auto mb-3 transition-transform">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p id="dropzone-text-main" class="text-sm font-semibold text-text-primary">Click to browse or drag &amp; drop Excel / CSV file</p>
                <p id="dropzone-text-sub" class="text-xs text-text-muted mt-1">Supports <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">.xlsx</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">.xls</code>, or <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">.csv</code></p>
                <p class="text-xs text-slate-500 mt-1">Required columns: <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">student_id</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">first_name</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">middle_initial</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">last_name</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">extension</code> <span class="text-slate-500">(optional)</span></p>
                <input type="file" id="excel-file-input" class="hidden" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" onclick="this.value = null" onchange="handleFileChosen(this)">
              </div>

              <!-- Prominent Selected File Details Card -->
              <div id="chosen-file-badge" class="hidden mt-4 p-4 bg-gradient-to-r from-teal-50/90 to-emerald-50/70 border border-teal-200/90 rounded-xl shadow-xs transition-all">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                  <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-teal-600 text-white flex items-center justify-center shrink-0 shadow-xs">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div>
                      <div class="flex items-center gap-2">
                        <span id="chosen-file-name" class="font-bold text-slate-900 text-sm">test_roster.xlsx</span>
                        <span id="chosen-file-size" class="text-[11px] font-mono text-slate-500 font-semibold">(14.2 KB)</span>
                      </div>
                      <p class="text-xs text-slate-600 mt-0.5">Spreadsheet successfully parsed and ready for schema validation.</p>
                    </div>
                  </div>
                  <div class="flex items-center gap-2 shrink-0">
                    <span id="chosen-file-status" class="px-3 py-1 text-xs font-bold rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs">3 Students Extracted</span>
                    <button type="button" onclick="clearSelectedFile()" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-white rounded-lg transition" title="Remove file">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                  </div>
                </div>

                <!-- Instant Extracted Preview Table in Step 1 -->
                <div id="step-1-mini-preview" class="hidden mt-3.5 pt-3.5 border-t border-teal-200/70">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold text-teal-900 uppercase tracking-wider flex items-center gap-1.5">
                      <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      File Content Quick Preview (First 5 Rows):
                    </span>
                    <span id="step-1-mini-count" class="text-[11px] font-semibold text-teal-800">Showing 3 rows</span>
                  </div>
                  <div class="overflow-x-auto bg-white rounded-lg border border-teal-200/80 shadow-2xs">
                    <table class="w-full text-left text-xs">
                      <thead class="bg-teal-50/50 text-teal-900 font-semibold border-b border-teal-100">
                        <tr>
                          <th class="py-2 px-3">Student ID</th>
                          <th class="py-2 px-3">First Name</th>
                          <th class="py-2 px-3">M.I.</th>
                          <th class="py-2 px-3">Last Name</th>
                          <th class="py-2 px-3">Ext.</th>
                        </tr>
                      </thead>
                      <tbody id="step-1-mini-tbody" class="divide-y divide-slate-100 font-mono text-[11px]">
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <!-- Download CSV Template -->
            <div class="p-3.5 bg-slate-50 rounded-xl border border-slate-200/90 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs mb-6">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center font-bold shrink-0">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div>
                  <span class="font-bold text-slate-800">Need the student roster CSV template?</span>
                  <p class="text-[11px] text-slate-500">Requires <code class="font-mono text-teal-700 font-bold">student_id</code>, <code class="font-mono text-teal-700 font-bold">first_name</code>, <code class="font-mono text-teal-700 font-bold">middle_initial</code>, <code class="font-mono text-teal-700 font-bold">last_name</code>, and optional <code class="font-mono text-teal-700 font-bold">extension</code> (e.g. Jr., III).</p>
                </div>
              </div>
              <a href="<?php echo url('teacher/roster/template'); ?>" onclick="downloadRosterCsvTemplate(event)" class="font-bold text-teal-600 hover:text-teal-700 hover:underline flex items-center gap-1.5 shrink-0 bg-white px-3.5 py-2 rounded-lg border border-teal-300 shadow-2xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Download CSV Template (.csv)</span>
              </a>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <a href="<?php echo url('teacher/classes'); ?>" class="btn btn-secondary">Cancel</a>
              <button type="submit" id="btn-proceed-step-2" class="btn btn-primary flex items-center gap-2">
                <span>Proceed to Preview &amp; Validation →</span>
              </button>
            </div>
          </form>
        </div>

        <!-- ════ STEP 2: PREVIEW & VALIDATE (Full Width) ════ -->
        <div id="wizard-step-2" class="hidden bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 md:p-8 w-full">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 mb-6">
            <div>
              <h2 class="text-lg font-bold font-display text-slate-900">Step 2: Roster Validation Preview</h2>
              <p class="text-xs text-slate-500 mt-0.5" id="step-2-count-header">Checked rows against Official Student Master &amp; Class Database</p>
            </div>
            <div class="flex flex-wrap items-center gap-2" id="step-2-kpi-badges">
              <span id="kpi-valid-badge" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200">0 Valid &amp; Ready</span>
              <span id="kpi-dup-badge" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-100 text-amber-800 border border-amber-200">0 Duplicate</span>
              <span id="kpi-unreg-badge" class="px-2.5 py-1 text-xs font-bold rounded-lg bg-blue-100 text-blue-800 border border-blue-200">0 New Master</span>
            </div>
          </div>

          <!-- Alert for Duplicate Roster (Hidden by default) -->
          <div id="duplicate-warning-banner" class="hidden p-4 bg-amber-50 border border-amber-300 rounded-xl flex items-start gap-3 mb-6 text-xs text-amber-900 shadow-2xs">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
              <p class="font-bold text-amber-900" id="duplicate-warning-title">Roster Already Exists in Database</p>
              <p class="mt-0.5 text-amber-800" id="duplicate-warning-msg">All students in this spreadsheet are already enrolled in this class.</p>
            </div>
          </div>

          <!-- Alert for Missing Master Records (Spec Section 8) -->
          <div id="master-protect-banner" class="p-4 bg-slate-50 border border-slate-200 rounded-xl flex items-start gap-3 mb-6 text-xs text-slate-700">
            <svg class="w-5 h-5 text-teal-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            <div>
              <p class="font-bold text-slate-800">Database Duplicate &amp; Master Validation Active</p>
              <p class="mt-0.5 text-slate-600">Students already registered on this course roster will be skipped automatically to prevent duplicate records.</p>
            </div>
          </div>

          <!-- Preview Table (Full Width) -->
          <div class="relative overflow-x-auto border border-slate-200 rounded-xl mb-6 min-h-[160px]">
            <!-- Table Loading Overlay -->
            <div id="table-loading-overlay" class="hidden absolute inset-0 bg-white/80 backdrop-blur-xs flex flex-col items-center justify-center z-10 gap-2">
              <svg class="w-7 h-7 animate-spin text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span class="text-xs font-semibold text-slate-700" id="table-loading-text">Validating roster against database...</span>
            </div>

            <table class="data-table w-full">
              <thead>
                <tr>
                  <th>Student Number</th>
                  <th>Excel Name</th>
                  <th>Master Match</th>
                  <th>Validation Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody id="step-2-table-body">
                <tr>
                  <td class="font-mono text-xs font-bold text-slate-800">2026-00123</td>
                  <td class="font-medium text-slate-900">Juan A. Dela Cruz Jr.</td>
                  <td class="text-slate-700 font-medium">Juan Dela Cruz (Official Student)</td>
                  <td><span class="badge badge-present">✓ Ready to Enroll</span></td>
                  <td><span class="text-xs text-emerald-600 font-semibold">Will Enroll</span></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex justify-between items-center pt-2">
            <button type="button" class="btn btn-secondary" onclick="backToStep1()">← Back to Upload</button>
            <button type="button" id="btn-confirm-import" class="btn btn-primary flex items-center gap-2" onclick="confirmImport()">
              <span>Confirm &amp; Import Students</span>
            </button>
          </div>
        </div>

        <!-- ════ STEP 3: SUCCESS CONFIRMATION ════ -->
        <div id="wizard-step-3" class="hidden bg-white rounded-2xl shadow-xs border border-slate-200/80 p-8 text-center max-w-xl mx-auto">
          <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h2 class="text-2xl font-bold font-display text-slate-900 mb-2">Roster Imported Successfully!</h2>
          <p class="text-sm text-slate-500 mb-6" id="step-3-subtext">Students have been officially enrolled into the class roster.</p>

          <div id="step-3-summary-card" class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-left text-xs mb-6 space-y-2">
            <div class="flex justify-between border-b border-slate-200/70 pb-2">
              <span class="text-slate-500">Target Class:</span>
              <span id="step-3-class-name" class="font-bold text-slate-800">IT301 (BSIT 3-1)</span>
            </div>
            <div class="flex justify-between border-b border-slate-200/70 pb-2">
              <span class="text-slate-500">Schedule &amp; Room:</span>
              <span id="step-3-schedule" class="font-semibold text-slate-800">Monday 08:00 AM (Room 402)</span>
            </div>
            <div class="flex justify-between border-b border-slate-200/70 pb-2">
              <span class="text-slate-500">Enrolled Count:</span>
              <span id="step-3-enrolled-count" class="font-bold text-emerald-600">0 Students</span>
            </div>
            <div class="flex justify-between text-amber-700" id="step-3-skipped-row">
              <span>Skipped (Duplicates):</span>
              <span id="step-3-skipped-count" class="font-bold">0</span>
            </div>
          </div>

          <div class="flex justify-center gap-3">
            <a href="<?php echo url('teacher/classes'); ?>" class="btn btn-primary">View Class Roster</a>
            <a href="<?php echo url('teacher/live-session'); ?>" class="btn btn-secondary">Start Attendance Session</a>
            <button type="button" class="btn btn-secondary" onclick="resetWizard()">Import Another Roster</button>
          </div>
        </div>
      </main>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

  <!-- SheetJS (Local with CDN Fallback) -->
  <script src="<?php echo url('assets/js/xlsx.full.min.js'); ?>"></script>
  <script>
    if (typeof XLSX === 'undefined') {
      const s = document.createElement('script');
      s.src = 'https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js';
      document.head.appendChild(s);
    }
  </script>

  <script>
    let uploadedRosterData = [];

    function handleCourseChange() {
      const course = document.getElementById('target-course-program').value;
      const majorSelect = document.getElementById('target-major');
      majorSelect.innerHTML = '';

      if (course === 'BSIT') {
        majorSelect.innerHTML = `
          <option value="NA" selected>NA (Network Administration)</option>
          <option value="IM">IM (Information Management)</option>
          <option value="IS">IS (Information Security / Systems)</option>
          <option value="Core">None / Core General</option>
        `;
      } else if (course === 'BSIS') {
        majorSelect.innerHTML = `
          <option value="BA" selected>BA (Business Analytics)</option>
          <option value="ES">ES (Enterprise Systems)</option>
          <option value="Core">None / Core General</option>
        `;
      }
      updatePreviewSummary();
    }

    function updatePreviewSummary() {
      const details = getTargetClassDetails();
      const majorStr = (details.major && details.major !== 'Core') ? ` (${details.major})` : '';
      const schedStr = ` · ${details.schedule_day} ${formatTimeToAmPm(details.scheduled_time)}`;
      const summaryText = `${details.course} ${details.year_level}-${details.section_num}${majorStr} · ${details.course_code}: ${details.course_title} (Room ${details.room_num}${schedStr})`;

      const el = document.getElementById('class-preview-summary');
      if (el) el.textContent = summaryText;

      const step3Text = document.getElementById('step-3-target-text');
      if (step3Text) step3Text.innerHTML = `<strong>${escapeHtml(summaryText)}</strong>`;
    }

    function formatTimeToAmPm(timeStr) {
      if (!timeStr) return '08:00 AM';
      const parts = timeStr.split(':');
      const h = parseInt(parts[0], 10);
      const m = parts[1] || '00';
      const ampm = h >= 12 ? 'PM' : 'AM';
      const h12 = h % 12 || 12;
      return `${String(h12).padStart(2, '0')}:${m} ${ampm}`;
    }

    function getTargetClassDetails() {
      const course = document.getElementById('target-course-program')?.value || 'BSIT';
      const yearInput = document.getElementById('target-year-level')?.value || '3';
      const yearLevel = yearInput.replace(/[^0-9]/g, '') || '3';
      const sectionNum = document.getElementById('target-section-num')?.value || '1';
      const major = document.getElementById('target-major')?.value || '';
      const courseCode = (document.getElementById('target-course-code')?.value || 'IT301').trim().toUpperCase();
      const courseTitle = (document.getElementById('target-course-title')?.value || 'Web Systems and Technologies').trim();
      const scheduleDay = document.getElementById('target-schedule-day')?.value || 'Monday';
      const scheduledTime = document.getElementById('target-scheduled-time')?.value || '08:00';
      const roomNum = document.getElementById('target-room-num')?.value || '402';

      return {
        course: course,
        year_level: yearLevel,
        section_num: sectionNum,
        section: `${course} ${yearLevel}-${sectionNum}`,
        major: (major && major !== 'Core') ? major : '',
        course_code: courseCode,
        course_title: courseTitle,
        schedule_day: scheduleDay,
        scheduled_time: scheduledTime,
        room_num: roomNum,
        room_number: roomNum
      };
    }

    // Attach input listeners for live preview
    document.addEventListener('DOMContentLoaded', () => {
      ['target-course-program', 'target-year-level', 'target-section-num', 'target-major', 'target-course-code', 'target-course-title', 'target-schedule-day', 'target-scheduled-time', 'target-room-num'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
          input.addEventListener('input', updatePreviewSummary);
          input.addEventListener('change', updatePreviewSummary);
        }
      });
      updatePreviewSummary();
    });

    function downloadRosterCsvTemplate(e) {
      if (e) e.preventDefault();
      const endpoint = '<?php echo url("teacher/roster/template"); ?>';
      window.location.href = endpoint;
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast('Downloading class_roster_template.csv...', 'info');
      } else if (typeof APP !== 'undefined' && APP.showToast) {
        APP.showToast('Downloading class_roster_template.csv...', 'info');
      }
    }

    // Drag and drop handlers
    function handleDragOver(e) {
      e.preventDefault();
      e.stopPropagation();
      const dropzone = document.getElementById('dropzone-area');
      if (dropzone) {
        dropzone.classList.add('border-teal-500', 'bg-teal-50/40');
      }
    }

    function handleDragLeave(e) {
      e.preventDefault();
      e.stopPropagation();
      const dropzone = document.getElementById('dropzone-area');
      if (dropzone) {
        dropzone.classList.remove('border-teal-500', 'bg-teal-50/40');
      }
    }

    function handleFileDrop(e) {
      e.preventDefault();
      e.stopPropagation();
      const dropzone = document.getElementById('dropzone-area');
      if (dropzone) {
        dropzone.classList.remove('border-teal-500', 'bg-teal-50/40');
      }
      if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length > 0) {
        processRosterFile(e.dataTransfer.files[0]);
      }
    }

    function formatFileSize(bytes) {
      if (!bytes || bytes <= 0) return '0 B';
      if (bytes < 1024) return bytes + ' B';
      if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
      return (bytes / 1048576).toFixed(1) + ' MB';
    }

    function clearSelectedFile() {
      uploadedRosterData = [];
      const fileInput = document.getElementById('excel-file-input');
      if (fileInput) fileInput.value = '';
      const badge = document.getElementById('chosen-file-badge');
      if (badge) badge.classList.add('hidden');
      const miniPreview = document.getElementById('step-1-mini-preview');
      if (miniPreview) miniPreview.classList.add('hidden');

      const dropzone = document.getElementById('dropzone-area');
      if (dropzone) {
        dropzone.classList.remove('border-teal-500', 'bg-teal-50/20');
        dropzone.classList.add('border-slate-200');
      }
      const textMain = document.getElementById('dropzone-text-main');
      if (textMain) textMain.textContent = 'Click to browse or drag & drop Excel / CSV file';
      const textSub = document.getElementById('dropzone-text-sub');
      if (textSub) textSub.innerHTML = 'Supports <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">.xlsx</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">.xls</code>, or <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200 font-mono text-[11px] text-teal-700 font-bold">.csv</code>';

      const proceedBtn = document.getElementById('btn-proceed-step-2');
      if (proceedBtn) {
        proceedBtn.innerHTML = `<span>Proceed to Preview &amp; Validation →</span>`;
      }
    }

    function handleFileChosen(input) {
      if (input.files && input.files[0]) {
        processRosterFile(input.files[0]);
      }
    }

    function processRosterFile(file) {
      if (!file) return;
      const fileName = file.name;
      const lowerName = fileName.toLowerCase();

      if (!lowerName.endsWith('.xlsx') && !lowerName.endsWith('.xls') && !lowerName.endsWith('.csv')) {
        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast('Please select an Excel (.xlsx, .xls) or CSV (.csv) file.', 'warning');
        }
        return;
      }

      // Show selected card and details
      document.getElementById('chosen-file-name').textContent = fileName;
      const sizeEl = document.getElementById('chosen-file-size');
      if (sizeEl) sizeEl.textContent = `(${formatFileSize(file.size)})`;

      document.getElementById('chosen-file-badge').classList.remove('hidden');

      const statusEl = document.getElementById('chosen-file-status');
      statusEl.className = 'px-3 py-1 text-xs font-bold rounded-lg bg-teal-100 text-teal-800 border border-teal-300 flex items-center gap-1.5';
      statusEl.innerHTML = '<svg class="w-3.5 h-3.5 animate-spin inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reading file...';

      const reader = new FileReader();

      reader.onload = function(e) {
        try {
          let rows = [];
          if (typeof XLSX !== 'undefined') {
            const data = new Uint8Array(e.target.result);
            const workbook = XLSX.read(data, { type: 'array' });
            const firstSheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[firstSheetName];
            rows = XLSX.utils.sheet_to_json(worksheet, { defval: '', raw: false });
          } else if (lowerName.endsWith('.csv')) {
            const text = new TextDecoder('utf-8').decode(e.target.result);
            rows = parseCsvText(text);
          }

          uploadedRosterData = normalizeExtractedRows(rows);

          if (uploadedRosterData.length === 0) {
            statusEl.className = 'px-3 py-1 text-xs font-bold rounded-lg bg-rose-100 text-rose-800 border border-rose-300';
            statusEl.textContent = 'No valid rows found';
            if (typeof APP !== 'undefined' && APP.toast) {
              APP.toast('No student rows found. File must have student_id, first_name, and last_name columns.', 'warning', 5000);
            }
          } else {
            statusEl.className = 'px-3 py-1 text-xs font-bold rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300 shadow-2xs';
            statusEl.textContent = `✓ ${uploadedRosterData.length} Students Extracted`;

            // Update dropzone styling
            const dropzone = document.getElementById('dropzone-area');
            if (dropzone) {
              dropzone.classList.remove('border-slate-200');
              dropzone.classList.add('border-teal-500', 'bg-teal-50/20');
            }
            const textMain = document.getElementById('dropzone-text-main');
            if (textMain) {
              textMain.innerHTML = `✓ Loaded: <strong class="text-teal-800">${escapeHtml(fileName)}</strong> (${uploadedRosterData.length} students)`;
            }
            const textSub = document.getElementById('dropzone-text-sub');
            if (textSub) {
              textSub.textContent = 'Click or drop a different file to replace';
            }

            // Render live mini-preview table in Step 1
            const miniPreview = document.getElementById('step-1-mini-preview');
            const miniTbody = document.getElementById('step-1-mini-tbody');
            const miniCount = document.getElementById('step-1-mini-count');

            if (miniTbody) {
              miniTbody.innerHTML = '';
              const previewSlice = uploadedRosterData.slice(0, 5);
              previewSlice.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50 transition';
                tr.innerHTML = `
                  <td class="py-2 px-3 font-bold text-slate-800">${escapeHtml(row.student_id)}</td>
                  <td class="py-2 px-3 text-slate-900 font-sans">${escapeHtml(row.first_name)}</td>
                  <td class="py-2 px-3 text-slate-600 font-sans">${escapeHtml(row.middle_initial || '-')}</td>
                  <td class="py-2 px-3 text-slate-900 font-medium font-sans">${escapeHtml(row.last_name)}</td>
                  <td class="py-2 px-3 text-slate-500 font-sans">${escapeHtml(row.extension || '-')}</td>
                `;
                miniTbody.appendChild(tr);
              });

              if (miniCount) {
                miniCount.textContent = `Showing ${previewSlice.length} of ${uploadedRosterData.length} extracted students`;
              }
              if (miniPreview) {
                miniPreview.classList.remove('hidden');
              }
            }

            // Update proceed button text
            const proceedBtn = document.getElementById('btn-proceed-step-2');
            if (proceedBtn) {
              proceedBtn.innerHTML = `<span>Proceed to Preview &amp; Validation (${uploadedRosterData.length} Students) →</span>`;
            }

            if (typeof APP !== 'undefined' && APP.toast) {
              APP.toast(`Spreadsheet parsed: ${uploadedRosterData.length} students found.`, 'info', 3000);
            }
          }
        } catch (err) {
          console.error('Error parsing file:', err);
          statusEl.className = 'px-3 py-1 text-xs font-bold rounded-lg bg-rose-100 text-rose-800 border border-rose-300';
          statusEl.textContent = 'Failed to read spreadsheet file';
          if (typeof APP !== 'undefined' && APP.toast) {
            APP.toast('Could not read the spreadsheet file. Please check format.', 'error', 5000);
          }
        }
      };

      reader.readAsArrayBuffer(file);
    }

    function normalizeExtractedRows(rows) {
      if (!Array.isArray(rows) || rows.length === 0) return [];
      const normalized = [];

      rows.forEach(row => {
        let studentId = '';
        let firstName = '';
        let middleInitial = '';
        let lastName = '';
        let extension = '';

        for (const key of Object.keys(row)) {
          const cleanKey = key.trim().toLowerCase().replace(/[\s\-_.]+/g, '');
          const val = row[key] !== null && row[key] !== undefined ? String(row[key]).trim() : '';

          if (['studentid', 'studentno', 'studentnumber', 'idnumber', 'id', 'studentnum'].includes(cleanKey)) {
            if (!studentId && val) studentId = val;
          } else if (['firstname', 'givenname', 'fname'].includes(cleanKey)) {
            if (!firstName && val) firstName = val;
          } else if (['middleinitial', 'middlename', 'mi'].includes(cleanKey)) {
            if (!middleInitial && val) middleInitial = val;
          } else if (['lastname', 'familyname', 'surname', 'lname'].includes(cleanKey)) {
            if (!lastName && val) lastName = val;
          } else if (['extension', 'ext', 'suffix', 'nameextension'].includes(cleanKey)) {
            if (!extension && val) extension = val;
          }
        }

        if (studentId) {
          normalized.push({
            student_id: studentId,
            first_name: firstName,
            middle_initial: middleInitial.replace(/\.$/, ''),
            last_name: lastName,
            extension: extension
          });
        }
      });

      return normalized;
    }

    function parseCsvText(csvText) {
      const lines = csvText.split(/\r\n|\n/).map(l => l.trim()).filter(l => l.length > 0);
      if (lines.length <= 1) return [];

      const headers = lines[0].split(',').map(h => h.replace(/^["']|["']$/g, '').trim());
      const rows = [];

      for (let i = 1; i < lines.length; i++) {
        const match = lines[i].match(/(".*?"|[^",\s]+)(?=\s*,|\s*$)/g) || lines[i].split(',');
        const vals = match.map(v => v.replace(/^["']|["']$/g, '').trim());
        const obj = {};
        headers.forEach((h, idx) => {
          obj[h] = vals[idx] || '';
        });
        rows.push(obj);
      }
      return rows;
    }

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text || '';
      return div.innerHTML;
    }

    async function goToStep2(e) {
      if (e) e.preventDefault();
      updatePreviewSummary();

      if (!uploadedRosterData || uploadedRosterData.length === 0) {
        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast('Please select or upload a valid Excel or CSV roster file first.', 'warning', 4000);
        }
        return;
      }

      const classDetails = getTargetClassDetails();
      if (!classDetails.course_code || !classDetails.course_title) {
        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast('Please complete course code and title.', 'warning');
        }
        return;
      }

      // Button loading effect
      const proceedBtn = document.getElementById('btn-proceed-step-2');
      const originalProceedHtml = proceedBtn ? proceedBtn.innerHTML : '';
      if (proceedBtn) {
        proceedBtn.disabled = true;
        proceedBtn.innerHTML = `
          <svg class="w-4 h-4 animate-spin text-white inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          <span>Validating against Database...</span>
        `;
      }

      // Switch view to Step 2
      document.getElementById('wizard-step-1').classList.add('hidden');
      document.getElementById('wizard-step-2').classList.remove('hidden');
      document.getElementById('step-indicator-2').classList.remove('opacity-60');
      document.getElementById('step-indicator-2').querySelector('span').className = 'w-9 h-9 rounded-xl bg-gradient-to-tr from-teal-600 to-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm';
      window.scrollTo({ top: 0, behavior: 'smooth' });

      // Show table loading overlay
      const overlay = document.getElementById('table-loading-overlay');
      if (overlay) overlay.classList.remove('hidden');
      const loadingText = document.getElementById('table-loading-text');
      if (loadingText) loadingText.textContent = `Validating ${uploadedRosterData.length} records against official master & class database...`;

      try {
        const res = await fetch('<?php echo url("api/teacher/roster/validate"); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ...classDetails,
            students: uploadedRosterData
          })
        });

        const data = await res.json();

        if (!res.ok || (!data.success && data.status !== 'success')) {
          throw new Error(data.message || 'Validation request failed.');
        }

        renderValidatedStep2(data, classDetails);

      } catch (err) {
        console.error('Validation error:', err);
        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast(err.message || 'Server error while validating roster records.', 'error', 5000);
        }
      } finally {
        if (overlay) overlay.classList.add('hidden');
        if (proceedBtn) {
          proceedBtn.disabled = false;
          proceedBtn.innerHTML = originalProceedHtml;
        }
      }
    }

    function renderValidatedStep2(data, classDetails) {
      const { students, valid_count, duplicate_count, unregistered_count, all_duplicate, all_unregistered } = data;
      const tbody = document.getElementById('step-2-table-body');
      if (tbody) {
        tbody.innerHTML = '';
        students.forEach((s) => {
          const miStr = s.middle_initial ? (s.middle_initial.endsWith('.') ? s.middle_initial : s.middle_initial + '.') : '';
          const parts = [s.first_name, miStr, s.last_name, s.extension].filter(p => p && p.trim().length > 0);
          const studentName = parts.length > 0 ? parts.join(' ') : 'Student';

          const tr = document.createElement('tr');
          if (s.status_type === 'unregistered') {
            tr.className = 'bg-rose-50/50 transition';
          } else if (s.status_type === 'duplicate') {
            tr.className = 'bg-amber-50/60 transition';
          } else {
            tr.className = 'hover:bg-slate-50 transition';
          }

          let masterCol = '';
          let statusCol = '';
          let actionCol = '';

          if (s.status_type === 'unregistered') {
            masterCol = `<span class="text-slate-500 italic">Not in Student Master</span> <span class="text-[11px] text-rose-700 font-bold bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">✗ Unregistered</span>`;
            statusCol = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                          <svg class="w-3.5 h-3.5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                          Cannot Enroll
                         </span>`;
            actionCol = `<span class="text-xs text-rose-600 font-bold">Skipped (Not in Master)</span>`;
          } else if (s.status_type === 'duplicate') {
            masterCol = `<span class="text-slate-800 font-semibold">${escapeHtml(s.master_name || studentName)}</span> <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">✓ Official Master</span>`;
            statusCol = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                          <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                          ⚠ Already Enrolled
                         </span>`;
            actionCol = `<span class="text-xs text-amber-700 font-bold">Skip Duplicate</span>`;
          } else {
            masterCol = `<span class="text-slate-800 font-semibold">${escapeHtml(s.master_name || studentName)}</span> <span class="text-[11px] text-emerald-700 font-bold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">✓ Official Master</span>`;
            statusCol = `<span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                          <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                          ✓ Ready to Enroll
                         </span>`;
            actionCol = `<span class="text-xs text-emerald-600 font-bold">Will Enroll</span>`;
          }

          tr.innerHTML = `
            <td class="font-mono text-xs font-bold text-slate-800">${escapeHtml(s.student_id)}</td>
            <td class="font-medium text-slate-900">${escapeHtml(studentName)}</td>
            <td class="text-slate-700 font-medium text-xs">${masterCol}</td>
            <td>${statusCol}</td>
            <td>${actionCol}</td>
          `;
          tbody.appendChild(tr);
        });
      }

      // Update KPI Badges
      const validBadge = document.getElementById('kpi-valid-badge');
      if (validBadge) {
        validBadge.textContent = `${valid_count} Ready to Enroll`;
        validBadge.className = valid_count > 0
          ? 'px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-300'
          : 'px-2.5 py-1 text-xs font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200';
      }

      const dupBadge = document.getElementById('kpi-dup-badge');
      if (dupBadge) {
        dupBadge.textContent = `${duplicate_count} Duplicate`;
        dupBadge.className = duplicate_count > 0 
          ? 'px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-100 text-amber-900 border border-amber-300'
          : 'px-2.5 py-1 text-xs font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200';
      }

      const unregBadge = document.getElementById('kpi-unreg-badge');
      if (unregBadge) {
        unregBadge.textContent = `${unregistered_count} Not in Master`;
        unregBadge.className = unregistered_count > 0
          ? 'px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-100 text-rose-900 border border-rose-300'
          : 'px-2.5 py-1 text-xs font-bold rounded-lg bg-slate-100 text-slate-600 border border-slate-200';
      }

      // Count header
      const countHeader = document.getElementById('step-2-count-header');
      if (countHeader) {
        countHeader.textContent = `Validated ${students.length} student${students.length > 1 ? 's' : ''} against database records for ${classDetails.course_code} (${classDetails.section})`;
      }

      // Handle Duplicates and Warning Toaster
      const warnBanner = document.getElementById('duplicate-warning-banner');
      const warnTitle = document.getElementById('duplicate-warning-title');
      const warnMsg = document.getElementById('duplicate-warning-msg');
      const confirmBtn = document.getElementById('btn-confirm-import');

      if (all_unregistered) {
        if (warnBanner) {
          warnBanner.className = 'p-4 bg-rose-50 border border-rose-300 rounded-xl flex items-start gap-3 mb-6 text-xs text-rose-900 shadow-2xs';
          warnBanner.classList.remove('hidden');
        }
        if (warnTitle) warnTitle.textContent = 'No Registered Students Found';
        if (warnMsg) warnMsg.textContent = `None of the ${students.length} student IDs in this file exist in the official Student Master List. Please verify IDs with the Registrar.`;

        if (confirmBtn) {
          confirmBtn.disabled = true;
          confirmBtn.className = 'btn bg-rose-500 hover:bg-rose-500 text-white cursor-not-allowed opacity-80 flex items-center gap-2';
          confirmBtn.innerHTML = `<span>⚠ No Registered Students to Enroll</span>`;
        }

        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast('Warning: No students found in the official master list. None can be enrolled.', 'error', 5500);
        }
      } else if (all_duplicate) {
        if (warnBanner) {
          warnBanner.className = 'p-4 bg-amber-50 border border-amber-300 rounded-xl flex items-start gap-3 mb-6 text-xs text-amber-900 shadow-2xs';
          warnBanner.classList.remove('hidden');
        }
        if (warnTitle) warnTitle.textContent = 'Roster Already Exists in Database';
        if (warnMsg) warnMsg.textContent = `All ${students.length} students in this spreadsheet are already enrolled in ${classDetails.course_code} (${classDetails.section}). No new enrollments will be created.`;

        if (confirmBtn) {
          confirmBtn.disabled = true;
          confirmBtn.className = 'btn bg-amber-500 hover:bg-amber-500 text-white cursor-not-allowed opacity-80 flex items-center gap-2';
          confirmBtn.innerHTML = `<span>⚠ All Students Already Enrolled</span>`;
        }

        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast('Warning: All students in this spreadsheet are already enrolled in this class roster.', 'warning', 5000);
        }
      } else if (valid_count === 0) {
        if (warnBanner) {
          warnBanner.className = 'p-4 bg-amber-50 border border-amber-300 rounded-xl flex items-start gap-3 mb-6 text-xs text-amber-900 shadow-2xs';
          warnBanner.classList.remove('hidden');
        }
        if (warnTitle) warnTitle.textContent = 'No Valid Students to Enroll';
        if (warnMsg) warnMsg.textContent = `All students are either already enrolled (${duplicate_count}) or not registered in the student master list (${unregistered_count}).`;

        if (confirmBtn) {
          confirmBtn.disabled = true;
          confirmBtn.className = 'btn bg-amber-500 hover:bg-amber-500 text-white cursor-not-allowed opacity-80 flex items-center gap-2';
          confirmBtn.innerHTML = `<span>⚠ No Valid Students to Enroll</span>`;
        }
      } else if (duplicate_count > 0 || unregistered_count > 0) {
        if (warnBanner) {
          warnBanner.className = 'p-4 bg-amber-50 border border-amber-300 rounded-xl flex items-start gap-3 mb-6 text-xs text-amber-900 shadow-2xs';
          warnBanner.classList.remove('hidden');
        }
        if (warnTitle) warnTitle.textContent = 'Notice: Skipped Records Detected';
        
        const skippedNotes = [];
        if (duplicate_count > 0) skippedNotes.push(`${duplicate_count} duplicate student(s)`);
        if (unregistered_count > 0) skippedNotes.push(`${unregistered_count} unregistered ID(s)`);
        
        if (warnMsg) warnMsg.textContent = `${skippedNotes.join(' and ')} will be skipped. ${valid_count} official student(s) will be enrolled.`;

        if (confirmBtn) {
          confirmBtn.disabled = false;
          confirmBtn.className = 'btn btn-primary flex items-center gap-2';
          confirmBtn.innerHTML = `<span>Confirm &amp; Import ${valid_count} Students</span>`;
        }

        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast(`Warning: ${skippedNotes.join(' and ')} will be skipped automatically.`, 'warning', 5000);
        }
      } else {
        if (warnBanner) warnBanner.classList.add('hidden');
        if (confirmBtn) {
          confirmBtn.disabled = false;
          confirmBtn.className = 'btn btn-primary flex items-center gap-2';
          confirmBtn.innerHTML = `<span>Confirm &amp; Import ${valid_count} Students</span>`;
        }
      }
    }

    function backToStep1() {
      document.getElementById('wizard-step-2').classList.add('hidden');
      document.getElementById('wizard-step-1').classList.remove('hidden');
      document.getElementById('step-indicator-2').classList.add('opacity-60');
      document.getElementById('step-indicator-2').querySelector('span').className = 'w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function confirmImport() {
      const confirmBtn = document.getElementById('btn-confirm-import');
      if (confirmBtn && confirmBtn.disabled) return;

      const classDetails = getTargetClassDetails();
      if (!uploadedRosterData || uploadedRosterData.length === 0) {
        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast('No student data available to import.', 'warning');
        }
        return;
      }

      // Show loading spinner on confirm button and overlay
      const originalBtnHtml = confirmBtn ? confirmBtn.innerHTML : '';
      if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = `
          <svg class="w-4 h-4 animate-spin text-white inline mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
          </svg>
          <span>Enrolling into Database...</span>
        `;
      }

      const overlay = document.getElementById('table-loading-overlay');
      if (overlay) overlay.classList.remove('hidden');
      const loadingText = document.getElementById('table-loading-text');
      if (loadingText) loadingText.textContent = 'Enrolling students into class_roster database table...';

      try {
        const res = await fetch('<?php echo url("api/teacher/roster/import"); ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            ...classDetails,
            students: uploadedRosterData
          })
        });

        const data = await res.json();

        if (!res.ok || (!data.success && data.status !== 'success')) {
          if (data.all_duplicate) {
            if (typeof APP !== 'undefined' && APP.toast) {
              APP.toast(data.message || 'Warning: All students in this roster are already enrolled in this class.', 'warning', 5000);
            }
            const warnBanner = document.getElementById('duplicate-warning-banner');
            if (warnBanner) warnBanner.classList.remove('hidden');
            if (confirmBtn) {
              confirmBtn.disabled = true;
              confirmBtn.className = 'btn bg-amber-500 hover:bg-amber-500 text-white cursor-not-allowed opacity-80 flex items-center gap-2';
              confirmBtn.innerHTML = `<span>⚠ All Students Already Enrolled</span>`;
            }
            return;
          }
          throw new Error(data.message || 'Failed to import roster into database.');
        }

        // Success response!
        if (data.skipped > 0) {
          const notes = [];
          if (data.duplicate_count > 0) notes.push(`${data.duplicate_count} duplicate(s)`);
          if (data.unregistered_count > 0) notes.push(`${data.unregistered_count} unregistered`);
          if (typeof APP !== 'undefined' && APP.toast) {
            APP.toast(`Notice: ${notes.join(' and ')} were skipped.`, 'warning', 5000);
          }
        }
        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast(data.message || `Class roster successfully imported (${data.imported} students).`, 'success', 5000);
        }

        // Populate Step 3 details
        const step3ClassName = document.getElementById('step-3-class-name');
        if (step3ClassName) {
          step3ClassName.textContent = `${data.details.course_code} · ${data.details.course} ${data.details.year_level}-${data.details.section}${data.details.major ? ' (' + data.details.major + ')' : ''}`;
        }

        const step3Schedule = document.getElementById('step-3-schedule');
        if (step3Schedule) {
          step3Schedule.textContent = `${data.details.schedule_day} ${formatTimeToAmPm(data.details.scheduled_time)} (Room ${data.details.room_number})`;
        }

        const step3Enrolled = document.getElementById('step-3-enrolled-count');
        if (step3Enrolled) {
          step3Enrolled.textContent = `${data.imported} Students Enrolled`;
        }

        const step3SkippedRow = document.getElementById('step-3-skipped-row');
        const step3SkippedCount = document.getElementById('step-3-skipped-count');
        if (step3SkippedRow && step3SkippedCount) {
          if (data.skipped > 0) {
            const skippedBreakdown = [];
            if (data.duplicate_count > 0) skippedBreakdown.push(`${data.duplicate_count} Duplicates`);
            if (data.unregistered_count > 0) skippedBreakdown.push(`${data.unregistered_count} Unregistered`);
            step3SkippedCount.textContent = skippedBreakdown.join(', ') + ' Skipped';
            step3SkippedRow.classList.remove('hidden');
          } else {
            step3SkippedRow.classList.add('hidden');
          }
        }

        const step3Subtext = document.getElementById('step-3-subtext');
        if (step3Subtext) {
          step3Subtext.innerHTML = `${data.imported} students have been officially enrolled into <span class="font-bold text-slate-800">${escapeHtml(data.details.course_code)} (${escapeHtml(data.details.section)})</span>. The class roster is now ready for attendance sessions.`;
        }

        // Switch to Step 3
        document.getElementById('wizard-step-2').classList.add('hidden');
        document.getElementById('wizard-step-3').classList.remove('hidden');
        document.getElementById('step-indicator-3').classList.remove('opacity-60');
        document.getElementById('step-indicator-3').querySelector('span').className = 'w-9 h-9 rounded-xl bg-gradient-to-tr from-teal-600 to-emerald-600 text-white flex items-center justify-center font-bold text-sm shadow-sm';
        window.scrollTo({ top: 0, behavior: 'smooth' });

      } catch (err) {
        console.error('Import error:', err);
        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast(err.message || 'Failed to complete import.', 'error', 5000);
        }
      } finally {
        if (overlay) overlay.classList.add('hidden');
        if (confirmBtn && !confirmBtn.disabled) {
          confirmBtn.innerHTML = originalBtnHtml;
        }
      }
    }

    function resetWizard() {
      clearSelectedFile();

      document.getElementById('wizard-step-3').classList.add('hidden');
      document.getElementById('wizard-step-2').classList.add('hidden');
      document.getElementById('wizard-step-1').classList.remove('hidden');

      document.getElementById('step-indicator-2').classList.add('opacity-60');
      document.getElementById('step-indicator-2').querySelector('span').className = 'w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm';
      document.getElementById('step-indicator-3').classList.add('opacity-60');
      document.getElementById('step-indicator-3').querySelector('span').className = 'w-9 h-9 rounded-xl bg-slate-200 text-slate-700 flex items-center justify-center font-bold text-sm';

      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  </script>
  <script>APP.highlightNav('classes');</script>
