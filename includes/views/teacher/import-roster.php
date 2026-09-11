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

              <!-- Row 3: Schedule Date & Time, Room Number -->
              <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5 pt-1">
                <!-- 7. Schedule Date & Time -->
                <div class="sm:col-span-2">
                  <label for="target-schedule" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Schedule (Days &amp; Time) <span class="text-red-500">*</span>
                  </label>
                  <input type="text" id="target-schedule" name="schedule" class="form-input text-xs" placeholder="e.g. Mon / Wed • 08:00 AM – 10:00 AM" value="Mon / Wed • 08:00 AM – 10:00 AM" required>
                </div>

                <!-- 8. Room (Number) -->
                <div>
                  <label for="target-room-num" class="form-label text-xs font-semibold uppercase tracking-wider mb-1 block text-slate-700">
                    Room (Number) <span class="text-red-500">*</span>
                  </label>
                  <input type="number" id="target-room-num" name="room_num" min="100" max="999" class="form-input text-xs font-mono" placeholder="e.g. 402" value="402" required>
                </div>
              </div>

              <div class="p-2.5 bg-blue-50/70 border border-blue-200/80 rounded-lg text-[11px] text-blue-900 flex items-center gap-2">
                <span class="font-bold text-blue-800">Class Identifier Preview:</span>
                <span id="class-preview-summary" class="font-semibold text-blue-950 font-mono">BSIT 3-1 (NA) · IT301: Web Systems and Technologies (Room 402)</span>
              </div>
            </div>

            <!-- File Dropzone (Spec Section 6) -->
            <div class="mb-6">
              <label class="form-label text-xs font-semibold uppercase tracking-wider mb-1.5 block text-text-secondary">
                Select Excel File (.xlsx, .csv) <span class="text-red-500">*</span>
              </label>
              <div class="border-2 border-dashed border-slate-200 hover:border-teal-400 bg-slate-50 hover:bg-teal-50/20 rounded-xl p-8 text-center cursor-pointer transition-colors"
                   onclick="document.getElementById('excel-file-input').click()">
                <div class="w-12 h-12 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center mx-auto mb-3">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-sm font-semibold text-text-primary">Click to browse or drag &amp; drop Excel file</p>
                <p class="text-xs text-text-muted mt-1">Expected columns: <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">student_number</code>, <code class="bg-white px-1.5 py-0.5 rounded border border-slate-200">full_name</code></p>
                <input type="file" id="excel-file-input" class="hidden" accept=".xlsx,.xls,.csv" onchange="handleFileChosen(this)">
              </div>
              <div id="chosen-file-badge" class="hidden mt-3 p-3 bg-teal-50 border border-teal-200 rounded-lg flex items-center justify-between text-xs font-medium text-teal-800">
                <span id="chosen-file-name">students_bsit3a_roster.xlsx</span>
                <span class="text-emerald-600 font-bold">Ready for Validation </span>
              </div>
            </div>

            <!-- Download Template -->
            <div class="p-3.5 bg-slate-50 rounded-lg border border-slate-200 flex items-center justify-between text-xs mb-6">
              <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-text-secondary">Need the sample Excel format?</span>
              </div>
              <a href="#" onclick="APP.showToast('Downloaded Roster_Template.xlsx', 'info'); return false;" class="font-bold text-teal-600 hover:text-teal-700">Download Template (.xlsx)</a>
            </div>

            <div class="flex justify-end gap-3 pt-2">
              <a href="<?php echo url('teacher/classes'); ?>" class="btn btn-secondary">Cancel</a>
              <button type="submit" class="btn btn-primary">Proceed to Preview &amp; Validation →</button>
            </div>
          </form>
        </div>

        <!-- ════ STEP 2: PREVIEW & VALIDATE (Full Width) ════ -->
        <div id="wizard-step-2" class="hidden bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 md:p-8 w-full">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200 mb-6">
            <div>
              <h2 class="text-lg font-bold font-display text-slate-900">Step 2: Roster Validation Preview</h2>
              <p class="text-xs text-slate-500 mt-0.5">Checked 6 rows against Official Student Master Database</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-emerald-100 text-emerald-800 border border-emerald-200">4 Valid &amp; Ready</span>
              <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-amber-100 text-amber-800 border border-amber-200">1 Duplicate</span>
              <span class="px-2.5 py-1 text-xs font-bold rounded-lg bg-rose-100 text-rose-800 border border-rose-200">1 Not Found (Skipped)</span>
            </div>
          </div>

          <!-- Alert for Missing Master Records (Spec Section 8) -->
          <div class="p-4 bg-amber-50/80 border border-amber-200/80 rounded-xl flex items-start gap-3 mb-6 text-xs text-amber-900">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div>
              <p class="font-bold">Student Master Protection Active</p>
              <p class="mt-0.5 text-amber-800">Unrecognized student numbers are flagged and will NOT create unauthorized accounts. Only verified official students will be enrolled.</p>
            </div>
          </div>

          <!-- Preview Table (Full Width) -->
          <div class="overflow-x-auto border border-slate-200 rounded-xl mb-6">
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
              <tbody>
                <tr>
                  <td class="font-mono text-xs font-bold text-slate-800">2026-00123</td>
                  <td class="font-medium text-slate-900">Juan Dela Cruz</td>
                  <td class="text-slate-700 font-medium">Juan Dela Cruz (BSIT 3)</td>
                  <td><span class="badge badge-present"> Master Match</span></td>
                  <td><span class="text-xs text-emerald-600 font-semibold">Will Enroll</span></td>
                </tr>
                <tr>
                  <td class="font-mono text-xs font-bold text-slate-800">2026-00124</td>
                  <td class="font-medium text-slate-900">Maria Santos</td>
                  <td class="text-slate-700 font-medium">Maria Santos (BSIT 3)</td>
                  <td><span class="badge badge-tardy">⚠ Already Enrolled</span></td>
                  <td><span class="text-xs text-amber-600 font-semibold">Skip Duplicate</span></td>
                </tr>
                <tr>
                  <td class="font-mono text-xs font-bold text-slate-800">2026-00125</td>
                  <td class="font-medium text-slate-900">Pedro Reyes</td>
                  <td class="text-slate-700 font-medium">Pedro Reyes (BSIT 3)</td>
                  <td><span class="badge badge-present"> Master Match</span></td>
                  <td><span class="text-xs text-emerald-600 font-semibold">Will Enroll</span></td>
                </tr>
                <tr>
                  <td class="font-mono text-xs font-bold text-slate-800">2026-00126</td>
                  <td class="font-medium text-slate-900">Ana Mendoza</td>
                  <td class="text-slate-700 font-medium">Ana Mendoza (BSIT 3)</td>
                  <td><span class="badge badge-present"> Master Match</span></td>
                  <td><span class="text-xs text-emerald-600 font-semibold">Will Enroll</span></td>
                </tr>
                <tr class="bg-rose-50/50">
                  <td class="font-mono text-xs font-bold text-rose-700">2026-99999</td>
                  <td class="text-rose-800 font-medium">Unregistered Student</td>
                  <td class="text-rose-600 italic">No record found in Student Master</td>
                  <td><span class="badge badge-absent"> Not in Master</span></td>
                  <td><span class="text-xs text-rose-600 font-semibold">Skipped</span></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="flex justify-between items-center pt-2">
            <button type="button" class="btn btn-secondary" onclick="backToStep1()">← Back to Upload</button>
            <button type="button" class="btn btn-primary" onclick="confirmImport()">Confirm &amp; Import 4 Students </button>
          </div>
        </div>

        <!-- ════ STEP 3: SUCCESS CONFIRMATION ════ -->
        <div id="wizard-step-3" class="hidden bg-white rounded-2xl shadow-xs border border-slate-200/80 p-8 text-center max-w-xl mx-auto">
          <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          </div>
          <h2 class="text-2xl font-bold font-display text-slate-900 mb-2">Roster Imported Successfully!</h2>
          <p class="text-sm text-slate-500 mb-6">4 students have been officially enrolled into <span id="step-3-target-text" class="text-slate-800"><strong>BSIT 3-1 (NA) · IT301: Web Systems and Technologies (Room 402)</strong></span>. The class roster is now ready for attendance sessions.</p>

          <div class="flex justify-center gap-3">
            <a href="<?php echo url('teacher/roster'); ?>" class="btn btn-primary">View Class Roster</a>
            <a href="<?php echo url('teacher/live-session'); ?>" class="btn btn-secondary">Start Attendance Session</a>
          </div>
        </div>
      </main>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

  <script>
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
      const course = document.getElementById('target-course-program').value;
      const year = document.getElementById('target-year-level').value.replace(/[^0-9]/g, '') || '3';
      const sec = document.getElementById('target-section-num').value || '1';
      const major = document.getElementById('target-major').value;
      const code = document.getElementById('target-course-code').value.toUpperCase() || 'IT301';
      const title = document.getElementById('target-course-title').value || 'Course Title';
      const room = document.getElementById('target-room-num').value || '402';

      const majorStr = (major && major !== 'Core') ? ` (${major})` : '';
      const summaryText = `${course} ${year}-${sec}${majorStr} · ${code}: ${title} (Room ${room})`;

      const el = document.getElementById('class-preview-summary');
      if (el) el.textContent = summaryText;

      const step3Text = document.getElementById('step-3-target-text');
      if (step3Text) step3Text.innerHTML = `<strong>${summaryText}</strong>`;
    }

    // Attach input listeners for live preview
    document.addEventListener('DOMContentLoaded', () => {
      ['target-course-program', 'target-year-level', 'target-section-num', 'target-major', 'target-course-code', 'target-course-title', 'target-schedule', 'target-room-num'].forEach(id => {
        const input = document.getElementById(id);
        if (input) {
          input.addEventListener('input', updatePreviewSummary);
          input.addEventListener('change', updatePreviewSummary);
        }
      });
      updatePreviewSummary();
    });

    function handleFileChosen(input) {
      if (input.files && input.files[0]) {
        document.getElementById('chosen-file-name').textContent = input.files[0].name;
        document.getElementById('chosen-file-badge').classList.remove('hidden');
      }
    }

    function goToStep2(e) {
      e.preventDefault();
      updatePreviewSummary();
      document.getElementById('wizard-step-1').classList.add('hidden');
      document.getElementById('wizard-step-2').classList.remove('hidden');
      document.getElementById('step-indicator-2').classList.remove('opacity-60');
      document.getElementById('step-indicator-2').querySelector('span').className = 'w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center font-bold text-sm';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function backToStep1() {
      document.getElementById('wizard-step-2').classList.add('hidden');
      document.getElementById('wizard-step-1').classList.remove('hidden');
    }

    function confirmImport() {
      document.getElementById('wizard-step-2').classList.add('hidden');
      document.getElementById('wizard-step-3').classList.remove('hidden');
      document.getElementById('step-indicator-3').classList.remove('opacity-60');
      document.getElementById('step-indicator-3').querySelector('span').className = 'w-8 h-8 rounded-full bg-teal-600 text-white flex items-center justify-center font-bold text-sm';
      APP.showToast('Class roster successfully enrolled.', 'success');
    }
  </script>
  <script>APP.highlightNav('classes');</script>
