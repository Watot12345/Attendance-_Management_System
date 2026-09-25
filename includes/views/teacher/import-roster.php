<?php
$page_title = 'Import Class Roster';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/controllers/StudentController.php';
require_once dirname(__DIR__, 2) . '/controllers/SettingsController.php';

// Fetch active term / semester policy configured in admin settings
$activeTermRaw = (string) SettingsController::get('semester', 'first semester');
$academicYearRaw = (string) SettingsController::get('academic_year', '2025-2026');
$activeSemesterNum = (stripos($activeTermRaw, 'second') !== false || stripos($activeTermRaw, '2') !== false) ? 2 : 1;
$activeSemesterLabel = ($activeSemesterNum === 2) ? '2nd Semester' : '1st Semester';
$headerTermDisplay = "{$activeSemesterLabel} AY {$academicYearRaw}";

$db = Database::getConnection();
$initialSection = StudentController::resolveSection($db, 'BSIT', 3, $activeSemesterNum);

// Get initial section capacity count
$cntStmt = $db->prepare("SELECT COUNT(*) FROM class_roster WHERE section = :sec OR section = :sec_legacy");
$cntStmt->execute([':sec' => $initialSection, ':sec_legacy' => 'BSIT ' . $initialSection]);
$initialSectionCount = (int) $cntStmt->fetchColumn();

require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <!-- Content Area -->
    <main class="page-body">
      <!-- Back Navigation & Title Header -->
      <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <a href="<?php echo url('teacher/classes'); ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition mb-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back to My Classes</span>
          </a>
          <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Import Student Roster</h1>
            <span class="text-xs font-semibold text-[#1e3b8a] bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200"><?php echo htmlspecialchars($headerTermDisplay); ?></span>
          </div>
          <p class="text-xs text-slate-500 mt-1">Enroll verified students into your assigned class section. Student records are automatically validated against the official master list.</p>
        </div>
        <div class="flex items-center gap-2">
          <a href="<?php echo url('teacher/roster/template'); ?>" onclick="downloadRosterCsvTemplate(event)" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Sample Template (.csv)</span>
          </a>
        </div>
      </div>

      <!-- 3-Step Wizard Stepper Header (Flex Space Between) -->
      <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-4 sm:p-5 mb-6">
        <div class="stepper-flex-between">
          <!-- Step 1 -->
          <div class="flex items-center gap-2.5 sm:gap-3" id="step-indicator-1">
            <span class="w-8 h-8 rounded-lg bg-[#1e3b8a] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0 transition-colors" id="step-badge-1">1</span>
            <div class="min-w-0">
              <p class="text-[10px] sm:text-[11px] font-bold text-slate-900 uppercase tracking-wider truncate">Step 1</p>
              <p class="text-[11px] sm:text-xs text-slate-600 font-medium truncate">Class &amp; Upload</p>
            </div>
          </div>

          <!-- Step 2 -->
          <div class="flex items-center gap-2.5 sm:gap-3 opacity-50" id="step-indicator-2">
            <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center font-bold text-xs shrink-0 transition-colors" id="step-badge-2">2</span>
            <div class="min-w-0">
              <p class="text-[10px] sm:text-[11px] font-bold text-slate-600 uppercase tracking-wider truncate">Step 2</p>
              <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Validate &amp; Preview</p>
            </div>
          </div>

          <!-- Step 3 -->
          <div class="flex items-center gap-2.5 sm:gap-3 opacity-50" id="step-indicator-3">
            <span class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center font-bold text-xs shrink-0 transition-colors" id="step-badge-3">3</span>
            <div class="min-w-0">
              <p class="text-[10px] sm:text-[11px] font-bold text-slate-600 uppercase tracking-wider truncate">Step 3</p>
              <p class="text-[11px] sm:text-xs text-slate-400 font-medium truncate">Confirmation</p>
            </div>
          </div>
        </div>
      </div>

      <!-- ════════════ STEP 1: FULL-WIDTH CLASS DETAILS & EXCEL UPLOAD ════════════ -->
      <div id="wizard-step-1">
        <form onsubmit="goToStep2(event)">
          <div class="space-y-6">
            
            <!-- CARD 1: Class & Academic Assignment (Full Width) -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-5 sm:p-6 space-y-5">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#1e3b8a] flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                  </div>
                  <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Class &amp; Section Assignment</h3>
                    <p class="text-[11px] text-slate-400">Configure the academic details for this enrollment</p>
                  </div>
                </div>
                <span class="text-[11px] font-medium text-slate-500 bg-slate-50 px-2.5 py-1 rounded-md border border-slate-200">Required <span class="text-rose-500">*</span></span>
              </div>

              <!-- Hidden Active Semester from Admin Settings -->
              <input type="hidden" id="target-semester" name="semester" value="<?php echo $activeSemesterNum; ?>">

              <!-- Row 1: Course Program & Year Level (Side by Side 50/50) -->
              <div class="form-row-2">
                <!-- Course Program -->
                <div>
                  <label for="target-course-program" class="text-[11px] font-bold uppercase text-slate-800 mb-1.5 block tracking-wider">
                    Course Program <span class="text-rose-500">*</span>
                  </label>
                  <select id="target-course-program" name="course_program" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs bg-white font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition cursor-pointer shadow-2xs" required onchange="handleCourseChange()">
                    <option value="BSIT" selected>BSIT (Information Technology)</option>
                    <option value="BSIS">BSIS (Information Systems)</option>
                  </select>
                </div>

                <!-- Year Level -->
                <div>
                  <label for="target-year-level" class="text-[11px] font-bold uppercase text-slate-800 mb-1.5 block tracking-wider">
                    Year Level <span class="text-rose-500">*</span>
                  </label>
                  <select id="target-year-level" name="year_level" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs bg-white font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition cursor-pointer shadow-2xs" required onchange="handleClassAttributeChange()">
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year" selected>3rd Year</option>
                    <option value="4th Year">4th Year</option>
                  </select>
                </div>
              </div>

              <!-- Row 2: Section & Major / Track (Side by Side 50/50) -->
              <div class="form-row-2">
                <!-- Section (5-Digit Format) -->
                <div>
                  <div class="flex items-center justify-between mb-1.5">
                    <label for="target-section-display" class="text-[11px] font-bold uppercase text-slate-800 tracking-wider">
                      Section <span class="text-rose-500">*</span>
                    </label>
                    <span id="target-section-capacity-pill" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">Capacity: <?php echo $initialSectionCount; ?>/50</span>
                  </div>
                  <input type="text" id="target-section-display" name="section_display" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs font-mono font-bold uppercase text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition shadow-2xs" placeholder="e.g. 31001" value="<?php echo htmlspecialchars($initialSection); ?>" oninput="handleSectionManualInput(this.value)" required>
                  <input type="hidden" id="target-section" name="section" value="<?php echo htmlspecialchars($initialSection); ?>">
                  <input type="hidden" id="target-section-num" name="section_num" value="<?php echo substr($initialSection, 2); ?>">
                </div>

                <!-- Major / Track -->
                <div>
                  <label for="target-major" class="text-[11px] font-bold uppercase text-slate-800 mb-1.5 block tracking-wider">
                    Major / Track <span class="text-rose-500">*</span>
                  </label>
                  <select id="target-major" name="major" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs bg-white font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition cursor-pointer shadow-2xs" required onchange="updatePreviewSummary()">
                    <option value="NA" selected>NA (Network Administration)</option>
                    <option value="IM">IM (Information Management)</option>
                    <option value="IS">IS (Information Security / Systems)</option>
                    <option value="Core">None / Core General</option>
                  </select>
                </div>
              </div>

              <!-- Row 3: Course Code & Course Title (Side by Side 50/50 Equal Width) -->
              <div class="form-row-2">
                <div>
                  <label for="target-course-code" class="text-[11px] font-bold uppercase text-slate-800 mb-1.5 block tracking-wider">
                    Course Code <span class="text-rose-500">*</span>
                  </label>
                  <input type="text" id="target-course-code" name="course_code" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs font-mono font-bold uppercase text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition shadow-2xs" placeholder="e.g. IT301" value="IT301" required>
                </div>
                <div>
                  <label for="target-course-title" class="text-[11px] font-bold uppercase text-slate-800 mb-1.5 block tracking-wider">
                    Course Title <span class="text-rose-500">*</span>
                  </label>
                  <input type="text" id="target-course-title" name="course_title" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs font-medium text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition shadow-2xs" placeholder="e.g. Web Systems and Technologies" value="Web Systems and Technologies" required>
                </div>
              </div>

              <!-- Row 4: Meeting Day, Session Time, Room Number (1 Row with 3 Columns) -->
              <div class="form-row-3">
                <div>
                  <label for="target-schedule-day" class="text-[11px] font-bold uppercase text-slate-800 mb-1.5 block tracking-wider">
                    Meeting Day <span class="text-rose-500">*</span>
                  </label>
                  <select id="target-schedule-day" name="schedule_day" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs bg-white font-medium text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition cursor-pointer shadow-2xs" required>
                    <option value="Monday" selected>Monday</option>
                    <option value="Tuesday">Tuesday</option>
                    <option value="Wednesday">Wednesday</option>
                    <option value="Thursday">Thursday</option>
                    <option value="Friday">Friday</option>
                    <option value="Saturday">Saturday</option>
                  </select>
                </div>
                <div>
                  <label for="target-scheduled-time" class="text-[11px] font-bold uppercase text-slate-800 mb-1.5 block tracking-wider">
                    Session Time <span class="text-rose-500">*</span>
                  </label>
                  <input type="time" id="target-scheduled-time" name="scheduled_time" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs font-mono text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition shadow-2xs" value="08:00" required>
                </div>
                <div>
                  <label for="target-room-num" class="text-[11px] font-bold uppercase text-slate-800 mb-1.5 block tracking-wider">
                    Room No. <span class="text-rose-500">*</span>
                  </label>
                  <input type="number" id="target-room-num" name="room_num" min="100" max="999" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 text-xs font-mono text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] focus:border-transparent transition shadow-2xs" placeholder="e.g. 402" value="402" required>
                </div>
              </div>

              <!-- Live Class Identity Summary Card -->
              <div class="p-3.5 bg-blue-50/50 rounded-xl border border-blue-100 flex items-start gap-3">
                <div class="w-6 h-6 rounded-md bg-[#1e3b8a] text-white flex items-center justify-center shrink-0 mt-0.5">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="text-[10px] font-bold text-[#1e3b8a] uppercase tracking-wider">Live Class Identity Preview:</div>
                  <div id="class-preview-summary" class="font-semibold text-slate-900 text-xs mt-0.5 truncate">
                    BSIT <?php echo htmlspecialchars($initialSection); ?> (NA) · IT301: Web Systems and Technologies (Room 402 · Monday 08:00 AM)
                  </div>
                </div>
              </div>

            </div>

            <!-- CARD 2: Spreadsheet Upload & Template Zone (Full Width) -->
            <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-5 sm:p-6 space-y-4 transition-all hover:shadow-md">
              <div class="flex items-center justify-between border-b border-slate-100 pb-3.5">
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-blue-50 text-[#1e3b8a] flex items-center justify-center shrink-0">
                    <!-- Cloud Upload Icon -->
                    <svg class="w-4 h-4 text-[#1e3b8a]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                  </div>
                  <div>
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Spreadsheet File</h3>
                    <p class="text-[11px] text-slate-400">Upload Excel or CSV student roster</p>
                  </div>
                </div>
                <div class="flex items-center gap-1.5">
                  <span class="text-[10px] font-mono font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200">.xlsx</span>
                  <span class="text-[10px] font-mono font-bold bg-slate-100 text-slate-700 px-2 py-0.5 rounded border border-slate-200">.csv</span>
                  
                  <!-- Download Template Icon & Button with Minimal Tooltip -->
                  <div class="relative group/tooltip inline-flex items-center">
                    <a href="<?php echo url('teacher/roster/template'); ?>" onclick="downloadRosterCsvTemplate(event)" 
                       class="flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold text-[#1e3b8a] bg-blue-50 hover:bg-[#1e3b8a] hover:text-white border border-blue-200 transition-all shadow-2xs"
                       aria-label="Download Template">
                      <!-- Download Icon -->
                      <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                      </svg>
                      <span class="text-[11px]">Template</span>
                    </a>

                    <!-- Minimal Message Tooltip -->
                    <div class="absolute bottom-full right-0 mb-1.5 hidden group-hover/tooltip:flex flex-col items-center pointer-events-none z-30">
                      <span class="bg-slate-900 text-white text-[10px] font-medium px-2 py-1 rounded-md shadow-md whitespace-nowrap">
                        Download .csv template
                      </span>
                      <div class="w-1.5 h-1.5 -mt-0.5 rotate-45 bg-slate-900"></div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Interactive Dropzone (Full Width) -->
              <div id="dropzone-area" class="w-full border-2 border-dashed border-slate-200 hover:border-[#1e3b8a] bg-slate-50/70 hover:bg-blue-50/30 rounded-xl p-7 text-center cursor-pointer transition-all group"
                   onclick="document.getElementById('excel-file-input').click()"
                   ondragover="handleDragOver(event)"
                   ondragleave="handleDragLeave(event)"
                   ondrop="handleFileDrop(event)">
                <!-- Prominent Cloud Upload Icon in Dropzone -->
                <div id="dropzone-icon" class="w-14 h-14 rounded-2xl bg-blue-50 text-[#1e3b8a] border border-blue-200/80 shadow-2xs flex items-center justify-center mx-auto mb-3 transition-transform group-hover:scale-105">
                  <svg class="w-7 h-7 text-[#1e3b8a]" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                  </svg>
                </div>
                <p id="dropzone-text-main" class="text-xs font-bold text-slate-800 group-hover:text-[#1e3b8a] transition-colors">Click to browse or drag &amp; drop spreadsheet</p>
                <p id="dropzone-text-sub" class="text-[11px] text-slate-500 mt-1">Supports Microsoft Excel (<code class="font-mono text-slate-700 font-bold">.xlsx</code>, <code class="font-mono text-slate-700 font-bold">.xls</code>) or <code class="font-mono text-slate-700 font-bold">.csv</code></p>
                
                <div class="mt-3.5 pt-3 border-t border-slate-200/60 flex flex-wrap justify-center gap-1.5 text-[10px]">
                  <span class="text-slate-400 font-medium">Required columns:</span>
                  <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-700 font-semibold">student_id</span>
                  <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-700 font-semibold">first_name</span>
                  <span class="font-mono bg-white px-1.5 py-0.5 rounded border border-slate-200 text-slate-700 font-semibold">last_name</span>
                </div>
                <input type="file" id="excel-file-input" class="hidden" accept=".xlsx,.xls,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" onclick="this.value = null" onchange="handleFileChosen(this)">
              </div>

              <!-- Selected File Card (Hidden initially) -->
              <div id="chosen-file-badge" class="hidden mt-4 p-4 bg-slate-50 border border-slate-200 rounded-xl shadow-xs transition-all">
                <div class="flex items-center justify-between gap-3">
                  <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-lg bg-[#1e3b8a] text-white flex items-center justify-center shrink-0">
                      <svg class="w-4 h-4 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </div>
                    <div class="min-w-0">
                      <div class="flex items-center gap-1.5">
                        <span id="chosen-file-name" class="font-bold text-slate-900 text-xs truncate">roster.xlsx</span>
                        <span id="chosen-file-size" class="text-[11px] font-mono text-slate-500 shrink-0">(14 KB)</span>
                      </div>
                      <span id="chosen-file-status" class="inline-block mt-0.5 px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300">3 Students Extracted</span>
                    </div>
                  </div>
                  <button type="button" onclick="clearSelectedFile()" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-white rounded-md transition shrink-0" title="Remove file">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </div>

                <!-- Quick Mini Preview Table (First 5 Rows) -->
                <div id="step-1-mini-preview" class="hidden mt-3 pt-3 border-t border-slate-200">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-[10px] font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1">
                      <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                      Instant Preview (First 5 Rows)
                    </span>
                    <span id="step-1-mini-count" class="text-[10px] font-medium text-slate-500">3 rows</span>
                  </div>
                  <div class="overflow-x-auto bg-white rounded-lg border border-slate-200 max-h-36 overflow-y-auto">
                    <table class="w-full text-left text-[11px]">
                      <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 sticky top-0">
                        <tr>
                          <th class="py-1.5 px-2">Student ID</th>
                          <th class="py-1.5 px-2">Name</th>
                        </tr>
                      </thead>
                      <tbody id="step-1-mini-tbody" class="divide-y divide-slate-100 font-mono text-[11px]">
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>

            </div>

          </div>

          <!-- Bottom Action Bar -->
          <div class="mt-6 pt-4 border-t border-slate-200/80 flex items-center justify-between">
            <a href="<?php echo url('teacher/classes'); ?>" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition">
              Cancel
            </a>
            <button type="submit" id="btn-proceed-step-2" class="px-5 py-2.5 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer">
              <span>Proceed to Preview &amp; Validation →</span>
            </button>
          </div>
        </form>
      </div>

      <!-- ════════════ STEP 2: PREVIEW & VALIDATE (MINIMALIST) ════════════ -->
      <div id="wizard-step-2" class="hidden">
        
        <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 p-5 sm:p-6 space-y-4">
          
          <!-- Header: Title & Minimalist Inline Stat Badges -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3.5 border-b border-slate-100">
            <div>
              <h2 class="text-sm font-bold text-slate-900 tracking-tight">Roster Validation Preview</h2>
              <p class="text-[11px] text-slate-400 mt-0.5" id="step-2-count-header">Checking records against official student master list</p>
            </div>
            
            <!-- Sleek Minimalist Stat Chips -->
            <div class="flex items-center gap-2 flex-wrap" id="step-2-kpi-badges">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Ready:</span>
                <strong id="kpi-valid-val" class="font-bold">0</strong>
              </span>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200/70">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                <span>Duplicate:</span>
                <strong id="kpi-dup-val" class="font-bold">0</strong>
              </span>
              <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/70">
                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                <span>Unregistered:</span>
                <strong id="kpi-unreg-val" class="font-bold">0</strong>
              </span>
            </div>
          </div>

          <!-- Alert for Duplicate / Unregistered Warning Banner (Sleek Minimal) -->
          <div id="duplicate-warning-banner" class="hidden p-3 rounded-lg bg-amber-50/70 border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5">
            <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <div class="min-w-0 flex-1">
              <span class="font-bold text-amber-900" id="duplicate-warning-title">Notice:</span>
              <span class="text-amber-800 ml-1" id="duplicate-warning-msg">Duplicate records will be skipped automatically.</span>
            </div>
          </div>

          <!-- Search & Filter Controls -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
            <!-- Search Input -->
            <div class="relative flex-1 max-w-xs">
              <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <input type="text" id="step-2-search-input" placeholder="Search by student ID or name..." oninput="filterStep2Table()" class="w-full pl-8 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-white text-slate-800 focus:outline-none focus:ring-2 focus:ring-[#1e3b8a] transition placeholder:text-slate-400">
            </div>

            <!-- Status Filter Tabs (Minimalist Pill design) -->
            <div class="flex items-center gap-1 bg-slate-100/70 p-1 rounded-lg border border-slate-200/60 self-start sm:self-auto">
              <button type="button" onclick="setStep2Filter('all')" id="filter-tab-all" class="px-2.5 py-1 rounded-md text-xs font-bold bg-[#1e3b8a] text-white shadow-2xs transition">All</button>
              <button type="button" onclick="setStep2Filter('valid')" id="filter-tab-valid" class="px-2.5 py-1 rounded-md text-xs font-medium text-slate-600 hover:text-slate-900 transition">Ready</button>
              <button type="button" onclick="setStep2Filter('duplicate')" id="filter-tab-duplicate" class="px-2.5 py-1 rounded-md text-xs font-medium text-slate-600 hover:text-slate-900 transition">Duplicates</button>
              <button type="button" onclick="setStep2Filter('unregistered')" id="filter-tab-unregistered" class="px-2.5 py-1 rounded-md text-xs font-medium text-slate-600 hover:text-slate-900 transition">Unregistered</button>
            </div>
          </div>

          <!-- Minimalist Table Container -->
          <div class="relative overflow-x-auto rounded-lg border border-slate-200/80 min-h-[160px]">
            <div id="table-loading-overlay" class="hidden absolute inset-0 bg-white/90 backdrop-blur-xs flex flex-col items-center justify-center z-10 gap-2">
              <svg class="w-6 h-6 animate-spin text-[#1e3b8a]" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-20" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              <span class="text-xs font-medium text-slate-700" id="table-loading-text">Validating roster against database...</span>
            </div>

            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50/80 text-[11px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200">
                <tr>
                  <th class="py-2.5 px-3.5">Student ID</th>
                  <th class="py-2.5 px-3.5">Uploaded Name</th>
                  <th class="py-2.5 px-3.5">Master List Match</th>
                  <th class="py-2.5 px-3.5">Status</th>
                  <th class="py-2.5 px-3.5 text-right">Action</th>
                </tr>
              </thead>
              <tbody id="step-2-table-body" class="divide-y divide-slate-100">
              </tbody>
            </table>

            <div id="step-2-empty-search" class="hidden py-8 text-center text-slate-400 text-xs">
              No student records matched your search or filter.
            </div>
          </div>

          <!-- Action Footer -->
          <div class="flex items-center justify-between pt-2 border-t border-slate-100">
            <button type="button" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition cursor-pointer" onclick="backToStep1()">
              ← Back to Class Details
            </button>
            <button type="button" id="btn-confirm-import" disabled class="px-5 py-2 rounded-lg bg-[#1e3b8a] text-white text-xs font-bold shadow-2xs transition flex items-center gap-2 opacity-50 cursor-not-allowed" onclick="confirmImport()">
              <span>Confirm &amp; Import Students</span>
            </button>
          </div>

        </div>

      </div>

      <!-- ════════════ STEP 3: SUCCESS CONFIRMATION ════════════ -->
      <div id="wizard-step-3" class="hidden bg-white rounded-2xl shadow-xs border border-slate-200/80 p-8 sm:p-10 text-center w-full">
        <div class="w-14 h-14 bg-emerald-50 text-emerald-600 border border-emerald-200 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-xs">
          <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight mb-1.5">Roster Imported Successfully!</h2>
        <p class="text-xs text-slate-500 mb-6" id="step-3-subtext">Students have been officially enrolled into the class roster and are ready for attendance sessions.</p>

        <div id="step-3-summary-card" class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-left text-xs mb-6 space-y-2.5">
          <div class="flex justify-between border-b border-slate-200/70 pb-2">
            <span class="text-slate-500 font-medium">Target Class:</span>
            <span id="step-3-class-name" class="font-bold text-slate-900">IT301 (BSIT 3-1)</span>
          </div>
          <div class="flex justify-between border-b border-slate-200/70 pb-2">
            <span class="text-slate-500 font-medium">Schedule &amp; Room:</span>
            <span id="step-3-schedule" class="font-medium text-slate-800">Monday 08:00 AM (Room 402)</span>
          </div>
          <div class="flex justify-between border-b border-slate-200/70 pb-2">
            <span class="text-slate-500 font-medium">Enrolled Count:</span>
            <span id="step-3-enrolled-count" class="font-bold text-emerald-700">0 Students</span>
          </div>
          <div class="flex justify-between text-amber-700" id="step-3-skipped-row">
            <span class="font-medium">Skipped Records:</span>
            <span id="step-3-skipped-count" class="font-bold">0</span>
          </div>
        </div>

        <div class="flex justify-center items-center gap-3 flex-wrap">
          <a id="step-3-view-roster-btn" href="<?php echo url('teacher/classes'); ?>" class="px-5 py-2.5 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span>View Class Roster</span>
          </a>
          <a href="<?php echo url('teacher/live-session'); ?>" class="px-5 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs transition inline-flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Start Attendance</span>
          </a>
          <button type="button" class="px-4 py-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition cursor-pointer" onclick="resetWizard()">
            Import Another Roster
          </button>
          <a href="<?php echo url('teacher/classes'); ?>" class="px-5 py-2.5 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold shadow-xs transition">
            Done
          </a>
        </div>
      </div>

    </main>
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
  var uploadedRosterData = [];
  var validatedStudentsData = [];
  var currentStep2Filter = 'all';

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
    handleClassAttributeChange();
  }

  async function handleClassAttributeChange() {
    const course = document.getElementById('target-course-program')?.value || 'BSIT';
    const yearInput = document.getElementById('target-year-level')?.value || '3';
    const yearLevel = yearInput.replace(/[^0-9]/g, '') || '3';
    const semesterVal = document.getElementById('target-semester')?.value || '<?php echo $activeSemesterNum; ?>';
    const semester = semesterVal.replace(/[^0-9]/g, '') || '<?php echo $activeSemesterNum; ?>';

    const fallbackSection = `${yearLevel}${semester}001`;
    const displayEl = document.getElementById('target-section-display');
    const hiddenEl = document.getElementById('target-section');
    const hiddenNumEl = document.getElementById('target-section-num');
    const capacityPill = document.getElementById('target-section-capacity-pill');

    if (displayEl) displayEl.value = fallbackSection;
    if (hiddenEl) hiddenEl.value = fallbackSection;
    if (hiddenNumEl) hiddenNumEl.value = '001';
    updatePreviewSummary();

    try {
      const url = '<?php echo url("api/teacher/roster/resolve-section"); ?>' +
                  `?course=${encodeURIComponent(course)}&year_level=${encodeURIComponent(yearLevel)}&semester=${encodeURIComponent(semester)}`;
      const res = await fetch(url);
      if (res.ok) {
        const data = await res.json();
        if (data && data.success && data.section) {
          if (displayEl) displayEl.value = data.section;
          if (hiddenEl) hiddenEl.value = data.section;
          if (hiddenNumEl) hiddenNumEl.value = data.section.slice(2);
          if (capacityPill) {
            const count = data.current_count || 0;
            const maxCap = data.max_capacity || 50;
            if (count >= maxCap) {
              capacityPill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200';
              capacityPill.textContent = `Capacity: ${count}/${maxCap} (Full)`;
            } else {
              capacityPill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200';
              capacityPill.textContent = `Capacity: ${count}/${maxCap}`;
            }
          }
          updatePreviewSummary();
        }
      }
    } catch (err) {
      console.warn('Could not query live section status, using fallback', err);
    }
  }

  var sectionCheckTimeout = null;
  function handleSectionManualInput(val) {
    val = (val || '').trim();
    const hiddenEl = document.getElementById('target-section');
    const hiddenNumEl = document.getElementById('target-section-num');
    if (hiddenEl) hiddenEl.value = val;
    if (hiddenNumEl) hiddenNumEl.value = val.length >= 3 ? val.slice(2) : val;
    updatePreviewSummary();

    clearTimeout(sectionCheckTimeout);
    sectionCheckTimeout = setTimeout(() => {
      checkManualSectionCapacity(val);
    }, 400);
  }

  async function checkManualSectionCapacity(sec) {
    if (!sec) return;
    const course = document.getElementById('target-course-program')?.value || 'BSIT';
    const yearInput = document.getElementById('target-year-level')?.value || '3';
    const yearLevel = yearInput.replace(/[^0-9]/g, '') || '3';
    const semesterVal = document.getElementById('target-semester')?.value || '1';
    const semester = semesterVal.replace(/[^0-9]/g, '') || '1';
    const capacityPill = document.getElementById('target-section-capacity-pill');

    try {
      const url = '<?php echo url("api/teacher/roster/resolve-section"); ?>' +
                  `?course=${encodeURIComponent(course)}&year_level=${encodeURIComponent(yearLevel)}&semester=${encodeURIComponent(semester)}`;
      const res = await fetch(url);
      if (res.ok) {
        const data = await res.json();
        if (capacityPill && data) {
          const count = data.current_count || 0;
          const maxCap = data.max_capacity || 50;
          if (count >= maxCap) {
            capacityPill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-800 border border-amber-200';
            capacityPill.textContent = `Capacity: ${count}/${maxCap} (Full)`;
          } else {
            capacityPill.className = 'text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200';
            capacityPill.textContent = `Capacity: ${count}/${maxCap}`;
          }
        }
      }
    } catch (err) {}
  }

  function updatePreviewSummary() {
    const details = getTargetClassDetails();
    const majorStr = (details.major && details.major !== 'Core') ? ` (${details.major})` : '';
    const schedStr = ` · ${details.schedule_day} ${formatTimeToAmPm(details.scheduled_time)}`;
    const summaryText = `${details.course} ${details.section}${majorStr} · ${details.course_code}: ${details.course_title} (Room ${details.room_num}${schedStr})`;

    const el = document.getElementById('class-preview-summary');
    if (el) el.textContent = summaryText;
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
    const semesterVal = document.getElementById('target-semester')?.value || '<?php echo $activeSemesterNum; ?>';
    const semester = semesterVal.replace(/[^0-9]/g, '') || '<?php echo $activeSemesterNum; ?>';
    const displayVal = document.getElementById('target-section-display')?.value?.trim();
    const section = displayVal || document.getElementById('target-section')?.value || `${yearLevel}${semester}001`;
    const sectionNum = document.getElementById('target-section-num')?.value || (section.length >= 3 ? section.slice(2) : '001');
    const major = document.getElementById('target-major')?.value || '';
    const courseCode = (document.getElementById('target-course-code')?.value || 'IT301').trim().toUpperCase();
    const courseTitle = (document.getElementById('target-course-title')?.value || 'Web Systems and Technologies').trim();
    const scheduleDay = document.getElementById('target-schedule-day')?.value || 'Monday';
    const scheduledTime = document.getElementById('target-scheduled-time')?.value || '08:00';
    const roomNum = document.getElementById('target-room-num')?.value || '402';

    return {
      course: course,
      year_level: yearLevel,
      semester: semester,
      section_num: sectionNum,
      section: section,
      major: (major && major !== 'Core') ? major : '',
      course_code: courseCode,
      course_title: courseTitle,
      schedule_day: scheduleDay,
      scheduled_time: scheduledTime,
      room_num: roomNum,
      room_number: roomNum
    };
  }

  document.addEventListener('DOMContentLoaded', () => {
    ['target-course-program', 'target-year-level', 'target-section-display', 'target-major', 'target-course-code', 'target-course-title', 'target-schedule-day', 'target-scheduled-time', 'target-room-num'].forEach(id => {
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

  // Drag and Drop handlers
  function handleDragOver(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('dropzone-area');
    if (dropzone) {
      dropzone.classList.add('border-[#1e3b8a]', 'bg-blue-50/50');
    }
  }

  function handleDragLeave(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('dropzone-area');
    if (dropzone) {
      dropzone.classList.remove('border-[#1e3b8a]', 'bg-blue-50/50');
    }
  }

  function handleFileDrop(e) {
    e.preventDefault();
    e.stopPropagation();
    const dropzone = document.getElementById('dropzone-area');
    if (dropzone) {
      dropzone.classList.remove('border-[#1e3b8a]', 'bg-blue-50/50');
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
      dropzone.classList.remove('border-[#1e3b8a]', 'bg-blue-50/20');
      dropzone.classList.add('border-slate-200');
    }
    const textMain = document.getElementById('dropzone-text-main');
    if (textMain) textMain.textContent = 'Click to browse or drag & drop file';

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

    document.getElementById('chosen-file-name').textContent = fileName;
    const sizeEl = document.getElementById('chosen-file-size');
    if (sizeEl) sizeEl.textContent = `(${formatFileSize(file.size)})`;

    document.getElementById('chosen-file-badge').classList.remove('hidden');

    const statusEl = document.getElementById('chosen-file-status');
    statusEl.className = 'inline-flex items-center gap-1.5 px-2 py-0.5 text-[10px] font-bold rounded-md bg-blue-100 text-[#1e3b8a] border border-blue-200';
    statusEl.innerHTML = '<svg class="w-3 h-3 animate-spin inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Reading file...';

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
          statusEl.className = 'inline-block px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-100 text-rose-800 border border-rose-300';
          statusEl.textContent = 'No valid rows found';
          if (typeof APP !== 'undefined' && APP.toast) {
            APP.toast('No student rows found. File must have student_id, first_name, and last_name columns.', 'warning', 5000);
          }
        } else {
          statusEl.className = 'inline-block px-2 py-0.5 text-[10px] font-bold rounded-md bg-emerald-100 text-emerald-800 border border-emerald-300';
          statusEl.textContent = `✓ ${uploadedRosterData.length} Students Extracted`;

          const dropzone = document.getElementById('dropzone-area');
          if (dropzone) {
            dropzone.classList.remove('border-slate-200');
            dropzone.classList.add('border-emerald-500', 'bg-emerald-50/20');
          }
          const textMain = document.getElementById('dropzone-text-main');
          if (textMain) {
            textMain.innerHTML = `✓ Loaded: <strong class="text-emerald-900">${escapeHtml(fileName)}</strong> (${uploadedRosterData.length} students)`;
          }

          // Mini preview
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
                <td class="py-1 px-2 font-bold text-slate-800">${escapeHtml(row.student_id)}</td>
                <td class="py-1 px-2 text-slate-900 font-sans">${escapeHtml(row.first_name)} ${escapeHtml(row.last_name)}</td>
              `;
              miniTbody.appendChild(tr);
            });

            if (miniCount) {
              miniCount.textContent = `${previewSlice.length} of ${uploadedRosterData.length} rows`;
            }
            if (miniPreview) {
              miniPreview.classList.remove('hidden');
            }
          }

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
        statusEl.className = 'inline-block px-2 py-0.5 text-[10px] font-bold rounded-md bg-rose-100 text-rose-800 border border-rose-300';
        statusEl.textContent = 'Failed to read file';
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

    const proceedBtn = document.getElementById('btn-proceed-step-2');
    const originalProceedHtml = proceedBtn ? proceedBtn.innerHTML : '';
    if (proceedBtn) {
      proceedBtn.disabled = true;
      proceedBtn.innerHTML = `
        <svg class="w-3.5 h-3.5 animate-spin text-white inline mr-1.5" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
          <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Validating against Database...</span>
      `;
    }

    // Switch view to Step 2
    document.getElementById('wizard-step-1').classList.add('hidden');
    document.getElementById('wizard-step-2').classList.remove('hidden');

    // Ensure Confirm button in Step 2 is strictly disabled while validating
    const confirmBtn = document.getElementById('btn-confirm-import');
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.className = 'px-5 py-2.5 rounded-lg bg-[#1e3b8a] text-white text-xs font-bold shadow-2xs transition flex items-center gap-2 opacity-50 cursor-not-allowed';
      confirmBtn.innerHTML = `
        <svg class="w-3.5 h-3.5 animate-spin text-white inline mr-1.5" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
          <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Validating Roster...</span>
      `;
    }
    
    // Update Stepper indicators
    document.getElementById('step-indicator-1').classList.remove('opacity-50');
    document.getElementById('step-badge-1').className = 'w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0';
    document.getElementById('step-badge-1').textContent = '✓';

    document.getElementById('step-indicator-2').classList.remove('opacity-50');
    document.getElementById('step-badge-2').className = 'w-8 h-8 rounded-lg bg-[#1e3b8a] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0';
    document.getElementById('step-indicator-2').querySelector('p.text-slate-400')?.classList.remove('text-slate-400');

    window.scrollTo({ top: 0, behavior: 'smooth' });

    const overlay = document.getElementById('table-loading-overlay');
    if (overlay) overlay.classList.remove('hidden');
    const loadingText = document.getElementById('table-loading-text');
    if (loadingText) loadingText.textContent = `Validating ${uploadedRosterData.length} records against official database...`;

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
      if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.className = 'px-5 py-2.5 rounded-lg bg-rose-500 text-white text-xs font-bold opacity-80 cursor-not-allowed flex items-center gap-2';
        confirmBtn.innerHTML = `<span>⚠ Validation Incomplete</span>`;
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
    validatedStudentsData = students || [];

    // Update KPI counts
    document.getElementById('kpi-valid-val').textContent = valid_count;
    document.getElementById('kpi-dup-val').textContent = duplicate_count;
    document.getElementById('kpi-unreg-val').textContent = unregistered_count;

    const countHeader = document.getElementById('step-2-count-header');
    if (countHeader) {
      countHeader.textContent = `Validated ${students.length} student${students.length > 1 ? 's' : ''} for ${classDetails.course_code} (${classDetails.section})`;
    }

    renderFilteredStep2Table();

    // Handle warning banners and buttons
    const warnBanner = document.getElementById('duplicate-warning-banner');
    const warnTitle = document.getElementById('duplicate-warning-title');
    const warnMsg = document.getElementById('duplicate-warning-msg');
    const confirmBtn = document.getElementById('btn-confirm-import');

    if (all_unregistered) {
      if (warnBanner) {
        warnBanner.className = 'p-4 bg-rose-50 border border-rose-200 rounded-xl flex items-start gap-3 text-xs text-rose-900 shadow-xs';
        warnBanner.classList.remove('hidden');
      }
      if (warnTitle) warnTitle.textContent = 'No Registered Students Found';
      if (warnMsg) warnMsg.textContent = `None of the ${students.length} student IDs exist in the official Student Master List.`;

      if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.className = 'px-5 py-2.5 rounded-lg bg-rose-500 text-white text-xs font-bold opacity-80 cursor-not-allowed';
        confirmBtn.innerHTML = `<span>⚠ No Registered Students to Enroll</span>`;
      }
    } else if (all_duplicate) {
      if (warnBanner) {
        warnBanner.className = 'p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3 text-xs text-amber-900 shadow-xs';
        warnBanner.classList.remove('hidden');
      }
      if (warnTitle) warnTitle.textContent = 'All Students Already Enrolled';
      if (warnMsg) warnMsg.textContent = `All ${students.length} students in this spreadsheet are already enrolled in ${classDetails.course_code} (${classDetails.section}).`;

      if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.className = 'px-5 py-2.5 rounded-lg bg-amber-500 text-white text-xs font-bold opacity-80 cursor-not-allowed';
        confirmBtn.innerHTML = `<span>⚠ All Students Already Enrolled</span>`;
      }
    } else if (valid_count === 0) {
      if (warnBanner) {
        warnBanner.className = 'p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3 text-xs text-amber-900 shadow-xs';
        warnBanner.classList.remove('hidden');
      }
      if (warnTitle) warnTitle.textContent = 'No Valid Students to Enroll';
      if (warnMsg) warnMsg.textContent = `All students are either already enrolled (${duplicate_count}) or unregistered (${unregistered_count}).`;

      if (confirmBtn) {
        confirmBtn.disabled = true;
        confirmBtn.className = 'px-5 py-2.5 rounded-lg bg-amber-500 text-white text-xs font-bold opacity-80 cursor-not-allowed';
        confirmBtn.innerHTML = `<span>⚠ No Valid Students to Enroll</span>`;
      }
    } else if (duplicate_count > 0 || unregistered_count > 0) {
      if (warnBanner) {
        warnBanner.className = 'p-4 bg-amber-50 border border-amber-200 rounded-xl flex items-start gap-3 text-xs text-amber-900 shadow-xs';
        warnBanner.classList.remove('hidden');
      }
      if (warnTitle) warnTitle.textContent = 'Notice: Skipped Records Detected';
      
      const skippedNotes = [];
      if (duplicate_count > 0) skippedNotes.push(`${duplicate_count} duplicate(s)`);
      if (unregistered_count > 0) skippedNotes.push(`${unregistered_count} unregistered ID(s)`);
      if (warnMsg) warnMsg.textContent = `${skippedNotes.join(' and ')} will be skipped automatically. ${valid_count} official student(s) will be enrolled.`;

      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.className = 'px-5 py-2.5 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-bold shadow-xs transition cursor-pointer';
        confirmBtn.innerHTML = `<span>Confirm &amp; Import ${valid_count} Students</span>`;
      }
    } else {
      if (warnBanner) warnBanner.classList.add('hidden');
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.className = 'px-5 py-2.5 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-bold shadow-xs transition cursor-pointer';
        confirmBtn.innerHTML = `<span>Confirm &amp; Import ${valid_count} Students</span>`;
      }
    }
  }

  function setStep2Filter(filterType) {
    if (currentStep2Filter === filterType) return;
    currentStep2Filter = filterType;
    ['all', 'valid', 'duplicate', 'unregistered'].forEach(t => {
      const btn = document.getElementById(`filter-tab-${t}`);
      if (btn) {
        if (t === filterType) {
          btn.className = 'px-2.5 py-1 rounded-md text-xs font-bold bg-[#1e3b8a] text-white shadow-2xs transition';
        } else {
          btn.className = 'px-2.5 py-1 rounded-md text-xs font-medium text-slate-600 hover:text-slate-900 transition';
        }
      }
    });

    const tbody = document.getElementById('step-2-table-body');
    if (tbody) {
      tbody.style.opacity = '0.35';
      tbody.style.transition = 'opacity 0.08s ease';
    }

    setTimeout(() => {
      renderFilteredStep2Table();
      if (tbody) {
        tbody.style.opacity = '1';
      }
    }, 60);
  }

  function filterStep2Table() {
    renderFilteredStep2Table();
  }

  function renderFilteredStep2Table() {
    const searchVal = (document.getElementById('step-2-search-input')?.value || '').trim().toLowerCase();
    const tbody = document.getElementById('step-2-table-body');
    const emptyNotice = document.getElementById('step-2-empty-search');
    if (!tbody) return;

    tbody.innerHTML = '';
    let renderedCount = 0;

    validatedStudentsData.forEach(s => {
      // Category filter check
      if (currentStep2Filter !== 'all' && s.status_type !== currentStep2Filter) {
        return;
      }

      // Search term check
      const miStr = s.middle_initial ? (s.middle_initial.endsWith('.') ? s.middle_initial : s.middle_initial + '.') : '';
      const parts = [s.first_name, miStr, s.last_name, s.extension].filter(p => p && p.trim().length > 0);
      const studentName = parts.length > 0 ? parts.join(' ') : (s.excel_name || 'Student');

      if (searchVal) {
        const idMatch = (s.student_id || '').toLowerCase().includes(searchVal);
        const nameMatch = studentName.toLowerCase().includes(searchVal);
        const masterMatch = (s.master_name || '').toLowerCase().includes(searchVal);
        if (!idMatch && !nameMatch && !masterMatch) {
          return;
        }
      }

      renderedCount++;
      const tr = document.createElement('tr');
      if (s.status_type === 'unregistered') {
        tr.className = 'bg-rose-50/20 hover:bg-rose-50/50 transition';
      } else if (s.status_type === 'duplicate') {
        tr.className = 'bg-amber-50/20 hover:bg-amber-50/50 transition';
      } else {
        tr.className = 'hover:bg-slate-50/80 transition';
      }

      let masterCol = '';
      let statusCol = '';
      let actionCol = '';

      if (s.status_type === 'unregistered') {
        masterCol = `<span class="text-slate-400 italic text-xs">Not in Master List</span>`;
        statusCol = `<span class="text-xs font-semibold text-rose-500">Unregistered</span>`;
        actionCol = `<span class="text-xs text-rose-400">Skipped</span>`;
      } else if (s.status_type === 'duplicate') {
        masterCol = `<span class="text-slate-800 font-medium">${escapeHtml(s.master_name || studentName)}</span>`;
        statusCol = `<span class="text-xs font-semibold text-amber-600">Enrolled</span>`;
        actionCol = `<span class="text-xs text-amber-500">Skip Duplicate</span>`;
      } else {
        masterCol = `<span class="text-slate-800 font-medium">${escapeHtml(s.master_name || studentName)}</span>`;
        statusCol = `<span class="text-xs font-bold text-emerald-600">Ready</span>`;
        actionCol = `<span class="text-xs text-emerald-600 font-medium">Will Enroll</span>`;
      }

      tr.innerHTML = `
        <td class="py-2.5 px-3.5 font-mono font-bold text-slate-800">${escapeHtml(s.student_id)}</td>
        <td class="py-2.5 px-3.5 font-medium text-slate-900">${escapeHtml(studentName)}</td>
        <td class="py-2.5 px-3.5 text-slate-700 font-medium">${masterCol}</td>
        <td class="py-2.5 px-3.5">${statusCol}</td>
        <td class="py-2.5 px-3.5 text-right">${actionCol}</td>
      `;
      tbody.appendChild(tr);
    });

    if (emptyNotice) {
      if (renderedCount === 0) {
        emptyNotice.classList.remove('hidden');
      } else {
        emptyNotice.classList.add('hidden');
      }
    }
  }

  function backToStep1() {
    document.getElementById('wizard-step-2').classList.add('hidden');
    document.getElementById('wizard-step-1').classList.remove('hidden');

    const confirmBtn = document.getElementById('btn-confirm-import');
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.className = 'px-5 py-2 rounded-lg bg-[#1e3b8a] text-white text-xs font-bold shadow-2xs transition flex items-center gap-2 opacity-50 cursor-not-allowed';
      confirmBtn.innerHTML = `<span>Confirm &amp; Import Students</span>`;
    }
    
    // Reset Stepper
    document.getElementById('step-badge-1').className = 'w-8 h-8 rounded-lg bg-[#1e3b8a] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0';
    document.getElementById('step-badge-1').textContent = '1';
    document.getElementById('step-indicator-2').classList.add('opacity-50');
    document.getElementById('step-badge-2').className = 'w-8 h-8 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center font-bold text-xs shrink-0';
    
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

    const originalBtnHtml = confirmBtn ? confirmBtn.innerHTML : '';
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.classList.add('opacity-60', 'cursor-not-allowed');
      confirmBtn.innerHTML = `
        <svg class="w-3.5 h-3.5 animate-spin text-white inline mr-1.5" viewBox="0 0 24 24" fill="none">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
          <path class="opacity-80" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span>Importing &amp; Enrolling Students...</span>
      `;
    }

    const overlay = document.getElementById('table-loading-overlay');
    if (overlay) overlay.classList.remove('hidden');
    const loadingText = document.getElementById('table-loading-text');
    if (loadingText) loadingText.textContent = 'Enrolling students into class roster database...';

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
            APP.toast(data.message || 'Warning: All students in this roster are already enrolled.', 'warning', 5000);
          }
          const warnBanner = document.getElementById('duplicate-warning-banner');
          if (warnBanner) warnBanner.classList.remove('hidden');
          if (confirmBtn) {
            confirmBtn.disabled = true;
            confirmBtn.className = 'px-5 py-2.5 rounded-lg bg-amber-500 text-white text-xs font-bold opacity-80 cursor-not-allowed';
            confirmBtn.innerHTML = `<span>⚠ All Students Already Enrolled</span>`;
          }
          return;
        }
        throw new Error(data.message || 'Failed to import roster into database.');
      }

      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast(data.message || `Class roster successfully imported (${data.imported} students).`, 'success', 5000);
      }

      // Update View Class Roster button URL with query parameters for automatic filter & selection
      const viewRosterBtn = document.getElementById('step-3-view-roster-btn');
      if (viewRosterBtn && data.details) {
        const params = new URLSearchParams();
        if (data.details.section) params.set('section', data.details.section);
        if (data.details.course_code) params.set('search', data.details.course_code);
        viewRosterBtn.href = '<?php echo url("teacher/classes"); ?>?' + params.toString();
      }

      // Populate Step 3
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
          step3SkippedCount.textContent = skippedBreakdown.join(', ');
          step3SkippedRow.classList.remove('hidden');
        } else {
          step3SkippedRow.classList.add('hidden');
        }
      }

      const step3Subtext = document.getElementById('step-3-subtext');
      if (step3Subtext) {
        step3Subtext.innerHTML = `${data.imported} students have been officially enrolled into <span class="font-bold text-slate-800">${escapeHtml(data.details.course_code)} (${escapeHtml(data.details.section)})</span>.`;
      }

      // Switch to Step 3
      document.getElementById('wizard-step-2').classList.add('hidden');
      document.getElementById('wizard-step-3').classList.remove('hidden');
      
      // Update Stepper indicators
      document.getElementById('step-badge-2').className = 'w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0';
      document.getElementById('step-badge-2').textContent = '✓';

      document.getElementById('step-indicator-3').classList.remove('opacity-50');
      document.getElementById('step-badge-3').className = 'w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0';
      document.getElementById('step-badge-3').textContent = '✓';

      window.scrollTo({ top: 0, behavior: 'smooth' });

    } catch (err) {
      console.error('Import error:', err);
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast(err.message || 'Failed to complete import.', 'error', 5000);
      }
      if (confirmBtn) {
        confirmBtn.disabled = false;
        confirmBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        confirmBtn.innerHTML = originalBtnHtml;
      }
    } finally {
      if (overlay) overlay.classList.add('hidden');
    }
  }

  function resetWizard() {
    clearSelectedFile();

    document.getElementById('wizard-step-3').classList.add('hidden');
    document.getElementById('wizard-step-2').classList.add('hidden');
    document.getElementById('wizard-step-1').classList.remove('hidden');

    const confirmBtn = document.getElementById('btn-confirm-import');
    if (confirmBtn) {
      confirmBtn.disabled = true;
      confirmBtn.className = 'px-5 py-2 rounded-lg bg-[#1e3b8a] text-white text-xs font-bold shadow-2xs transition flex items-center gap-2 opacity-50 cursor-not-allowed';
      confirmBtn.innerHTML = `<span>Confirm &amp; Import Students</span>`;
    }

    document.getElementById('step-badge-1').className = 'w-8 h-8 rounded-lg bg-[#1e3b8a] text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0';
    document.getElementById('step-badge-1').textContent = '1';

    document.getElementById('step-indicator-2').classList.add('opacity-50');
    document.getElementById('step-badge-2').className = 'w-8 h-8 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center font-bold text-xs shrink-0';
    document.getElementById('step-badge-2').textContent = '2';

    document.getElementById('step-indicator-3').classList.add('opacity-50');
    document.getElementById('step-badge-3').className = 'w-8 h-8 rounded-lg bg-slate-100 text-slate-600 border border-slate-200 flex items-center justify-center font-bold text-xs shrink-0';
    document.getElementById('step-badge-3').textContent = '3';

    window.scrollTo({ top: 0, behavior: 'smooth' });
  }
</script>
