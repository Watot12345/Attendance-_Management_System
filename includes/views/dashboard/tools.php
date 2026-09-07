<?php $page_title = 'Tools & Utilities'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
          <div>
            <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Tools &amp; Utilities</h1>
            <p class="text-sm mt-1" style="color:var(--color-text-secondary)">Generate perfect attendance awards and export comprehensive attendance datasets</p>
          </div>
        </div>

        <!-- Top Menu Panel (Tabs) -->
        <div class="bg-white rounded-lg p-1.5 shadow-card border border-gray-100 mb-6 inline-flex flex-wrap gap-1">
          <button id="tab-btn-awards" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-semibold transition-all"
                  style="background:var(--color-teal-500); color:#ffffff;"
                  onclick="switchToolsTab('awards')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
            <span>Perfect Attendance Awards</span>
          </button>
          <button id="tab-btn-exports" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-medium transition-all"
                  style="background:transparent; color:var(--color-text-secondary);"
                  onclick="switchToolsTab('exports')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Export Data</span>
          </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 1: PERFECT ATTENDANCE AWARDS                        -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="tools-panel-awards" class="space-y-6">
          <!-- Award Configuration Panel -->
          <div class="bg-white rounded-lg p-6 shadow-card">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold" style="color:var(--color-text-primary)">Award Calculation Criteria</h3>
              <span class="text-xs px-2.5 py-1 rounded bg-amber-50 text-amber-800 font-semibold border border-amber-200">0 Absences · 0 Tardies</span>
            </div>

            <form onsubmit="event.preventDefault(); showAwardResults();">
              <div class="mb-4">
                <label class="form-label text-sm">Award Frequency / Period</label>
                <div class="flex flex-wrap gap-4 mt-1.5">
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="award-type" value="monthly" checked class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                    <span class="text-sm">Monthly Honors</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="award-type" value="quarterly" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                    <span class="text-sm">Quarterly (Grading Period)</span>
                  </label>
                  <label class="flex items-center gap-2 cursor-pointer">
                    <input type="radio" name="award-type" value="semester" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                    <span class="text-sm">Semester Award</span>
                  </label>
                </div>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                  <label for="award-from" class="form-label text-sm">Evaluation Start</label>
                  <input type="date" id="award-from" class="form-input" value="2026-09-01">
                </div>
                <div>
                  <label for="award-to" class="form-label text-sm">Evaluation End</label>
                  <input type="date" id="award-to" class="form-input" value="2026-09-30">
                </div>
              </div>

              <div class="mb-5">
                <label for="award-grade" class="form-label text-sm">Grade Level Filter</label>
                <select id="award-grade" class="form-input form-select">
                  <option>All Grades (High School Division)</option>
                  <option>Grade 7</option>
                  <option>Grade 8</option>
                  <option>Grade 9</option>
                  <option>Grade 10</option>
                </select>
              </div>

              <button type="submit" class="btn btn-primary btn-lg w-full justify-center inline-flex items-center gap-2">
                <svg class="w-5 h-5 text-amber-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4a5 5 0 005 5h4a5 5 0 005-5V3H5zm0 2H3a2 2 0 002 2v-2zm14 0h2a2 2 0 01-2 2V5zm-7 7v5m-4 4h8m-6-4h4"/></svg>
                Calculate Eligible Students
              </button>
            </form>
          </div>

          <!-- Results Section -->
          <div id="award-results">
            <!-- Results Header -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4 gap-3">
              <div>
                <h3 class="text-lg font-bold" style="color:var(--color-text-primary)">Award Recipients — September 2026</h3>
                <p class="text-sm mt-0.5" style="color:var(--color-present)">✓ 14 students qualified with 100% attendance</p>
              </div>
              <div class="flex gap-2">
                <button type="button" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5" onclick="APP.showToast('Batch notifications dispatched to all 14 parents.', 'success')">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                  Notify All Parents
                </button>
                <button type="button" class="btn btn-secondary btn-sm" onclick="APP.showToast('Recipients list exported to CSV.', 'info')">Export List CSV</button>
              </div>
            </div>

            <!-- Award Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <!-- Student Card 1 -->
              <div class="award-card">
                <div class="flex items-center gap-2.5 mb-2">
                  <div class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center shrink-0 text-amber-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4a5 5 0 005 5h4a5 5 0 005-5V3H5zm0 2H3a2 2 0 002 2v-2zm14 0h2a2 2 0 01-2 2V5zm-7 7v5m-4 4h8m-6-4h4"/></svg>
                  </div>
                  <span class="text-xs font-semibold uppercase tracking-wider" style="color:#b45309">Perfect Attendance</span>
                </div>
                <h4 class="text-lg font-semibold" style="color:var(--color-text-primary)">Dela Cruz, Juan</h4>
                <p class="text-sm" style="color:var(--color-text-secondary)">Grade 7 — Section A</p>
                <p class="text-sm mt-2" style="color:var(--color-text-secondary)">September 2026</p>
                <p class="text-xs" style="color:var(--color-text-muted)">22 days · 0 absences · 0 tardies</p>
                <div class="flex gap-2 mt-3 pt-3 border-t border-amber-200/60">
                  <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1.5" onclick="APP.showToast('Notification sent to Juan Dela Cruz parent.', 'success')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Notify
                  </button>
                  <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1.5" onclick="previewCertificate('Dela Cruz, Juan', 'Grade 7 — Section A', 'September 2026')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Certificate
                  </button>
                </div>
              </div>

              <!-- Student Card 2 -->
              <div class="award-card">
                <div class="flex items-center gap-2.5 mb-2">
                  <div class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center shrink-0 text-amber-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4a5 5 0 005 5h4a5 5 0 005-5V3H5zm0 2H3a2 2 0 002 2v-2zm14 0h2a2 2 0 01-2 2V5zm-7 7v5m-4 4h8m-6-4h4"/></svg>
                  </div>
                  <span class="text-xs font-semibold uppercase tracking-wider" style="color:#b45309">Perfect Attendance</span>
                </div>
                <h4 class="text-lg font-semibold" style="color:var(--color-text-primary)">Garcia, Ana</h4>
                <p class="text-sm" style="color:var(--color-text-secondary)">Grade 7 — Section B</p>
                <p class="text-sm mt-2" style="color:var(--color-text-secondary)">September 2026</p>
                <p class="text-xs" style="color:var(--color-text-muted)">22 days · 0 absences · 0 tardies</p>
                <div class="flex gap-2 mt-3 pt-3 border-t border-amber-200/60">
                  <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1.5" onclick="APP.showToast('Notification sent to Ana Garcia parent.', 'success')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Notify
                  </button>
                  <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1.5" onclick="previewCertificate('Garcia, Ana', 'Grade 7 — Section B', 'September 2026')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Certificate
                  </button>
                </div>
              </div>

              <!-- Student Card 3 -->
              <div class="award-card">
                <div class="flex items-center gap-2.5 mb-2">
                  <div class="w-7 h-7 rounded-full bg-amber-100 flex items-center justify-center shrink-0 text-amber-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4a5 5 0 005 5h4a5 5 0 005-5V3H5zm0 2H3a2 2 0 002 2v-2zm14 0h2a2 2 0 01-2 2V5zm-7 7v5m-4 4h8m-6-4h4"/></svg>
                  </div>
                  <span class="text-xs font-semibold uppercase tracking-wider" style="color:#b45309">Perfect Attendance</span>
                </div>
                <h4 class="text-lg font-semibold" style="color:var(--color-text-primary)">Villanueva, Carlo</h4>
                <p class="text-sm" style="color:var(--color-text-secondary)">Grade 8 — Section A</p>
                <p class="text-sm mt-2" style="color:var(--color-text-secondary)">September 2026</p>
                <p class="text-xs" style="color:var(--color-text-muted)">22 days · 0 absences · 0 tardies</p>
                <div class="flex gap-2 mt-3 pt-3 border-t border-amber-200/60">
                  <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1.5" onclick="APP.showToast('Notification sent to Carlo Villanueva parent.', 'success')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    Notify
                  </button>
                  <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1.5" onclick="previewCertificate('Villanueva, Carlo', 'Grade 8 — Section A', 'September 2026')">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Certificate
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 2: EXPORT DATA                                      -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="tools-panel-exports" class="hidden flex justify-center">
          <div class="w-full max-w-xl">
            <div class="bg-white rounded-lg p-6 shadow-card">
              <div class="mb-5 pb-4 border-b border-gray-100">
                <h2 class="text-lg font-bold" style="color:var(--color-text-primary)">Export Attendance Data</h2>
                <p class="text-sm mt-0.5" style="color:var(--color-text-secondary)">Generate downloadable CSV or Excel reports for administrative archiving</p>
              </div>

              <form onsubmit="event.preventDefault(); APP.showToast('Export file generated. Downloading...', 'success');">
                <!-- What to Export -->
                <div class="mb-6">
                  <label class="form-label mb-3 text-sm">Select Dataset</label>
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-slate-50 cursor-pointer transition-colors">
                      <input type="radio" name="export-type" value="attendance" checked class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                      <span class="text-sm font-medium">Daily Attendance Log</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-slate-50 cursor-pointer transition-colors">
                      <input type="radio" name="export-type" value="tardy" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                      <span class="text-sm font-medium">Tardy Records</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-slate-50 cursor-pointer transition-colors">
                      <input type="radio" name="export-type" value="absence" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                      <span class="text-sm font-medium">Absence Tracker</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-slate-50 cursor-pointer transition-colors">
                      <input type="radio" name="export-type" value="teacher" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                      <span class="text-sm font-medium">Faculty Attendance</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-slate-50 cursor-pointer transition-colors">
                      <input type="radio" name="export-type" value="excuses" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                      <span class="text-sm font-medium">Excuse Slip Submissions</span>
                    </label>
                    <label class="flex items-center gap-2 p-2.5 rounded-lg border border-gray-200 hover:bg-slate-50 cursor-pointer transition-colors">
                      <input type="radio" name="export-type" value="awards" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                      <span class="text-sm font-medium">Perfect Attendance List</span>
                    </label>
                  </div>
                </div>

                <!-- Date Range -->
                <div class="mb-4">
                  <label class="form-label text-sm">Date Range</label>
                  <div class="grid grid-cols-2 gap-3">
                    <div>
                      <label class="text-xs font-medium" style="color:var(--color-text-secondary)">Start Date</label>
                      <input type="date" class="form-input mt-1" value="2026-09-01">
                    </div>
                    <div>
                      <label class="text-xs font-medium" style="color:var(--color-text-secondary)">End Date</label>
                      <input type="date" class="form-input mt-1" value="2026-09-30">
                    </div>
                  </div>
                </div>

                <!-- Optional Filters -->
                <div class="mb-5">
                  <label class="form-label text-sm">Filters (Optional)</label>
                  <div class="grid grid-cols-2 gap-3">
                    <select class="form-input form-select text-sm">
                      <option>All Grades</option>
                      <option>Grade 7</option>
                      <option>Grade 8</option>
                      <option>Grade 9</option>
                      <option>Grade 10</option>
                    </select>
                    <select class="form-input form-select text-sm">
                      <option>All Sections</option>
                      <option>Section A</option>
                      <option>Section B</option>
                      <option>Section C</option>
                    </select>
                    <select class="form-input form-select text-sm">
                      <option>All Statuses</option>
                      <option>Present</option>
                      <option>Tardy</option>
                      <option>Absent</option>
                      <option>Excused</option>
                    </select>
                    <select class="form-input form-select text-sm">
                      <option>All Students</option>
                    </select>
                  </div>
                </div>

                <!-- Format -->
                <div class="mb-5">
                  <label class="form-label text-sm">Export File Format</label>
                  <div class="flex gap-5 mt-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" name="export-format" value="csv" checked class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                      <span class="text-sm font-medium">CSV (Comma-Separated)</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input type="radio" name="export-format" value="xlsx" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                      <span class="text-sm font-medium">Excel Spreadsheet (.xlsx)</span>
                    </label>
                  </div>
                </div>

                <!-- Estimated Rows -->
                <div class="p-3.5 rounded-lg mb-6 flex items-center justify-between" style="background:var(--color-surface)">
                  <div>
                    <p class="text-sm" style="color:var(--color-text-secondary)">
                      <strong>Estimated records:</strong> <span id="est-rows" class="font-bold text-teal-700">312</span>
                    </p>
                    <p class="text-xs mt-0.5" style="color:var(--color-text-muted)">Max 10,000 rows per single export</p>
                  </div>
                  <span class="text-xs px-2 py-1 rounded bg-slate-200 text-slate-700 font-semibold">Ready</span>
                </div>

                <!-- Actions -->
                <div class="flex gap-3 pt-2">
                  <button type="button" class="btn btn-secondary flex-1 justify-center" onclick="APP.showToast('Previewing first 20 records...', 'info')">Preview Data</button>
                  <button type="submit" class="btn btn-primary flex-1 justify-center">⬇ Download Export</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php $page_js = '<script src="/Attendance _Management_System/assets/js/tools.js"></script>'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('tools');</script>
