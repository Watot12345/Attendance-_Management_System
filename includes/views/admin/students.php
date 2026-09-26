<?php
$page_title = 'Official Student Master Accounts';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Breadcrumb / Portal indicator -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#1e3b8a]/10 text-[#1e3b8a] border border-[#1e3b8a]/20">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Master Accounts Directory</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Student Accounts &amp; Master Roster</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Manage institutional student accounts, create credentials manually, or batch import students via Excel/CSV spreadsheets.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <!-- Import Excel Button -->
          <button type="button" onclick="openExcelModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2 group cursor-pointer">
            <svg class="w-4 h-4 text-emerald-600 group-hover:scale-105 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Import Excel / CSV</span>
          </button>

          <!-- Create Manually Button -->
          <button type="button" onclick="openManualStudentModal()" class="px-4 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 group cursor-pointer">
            <svg class="w-4 h-4 text-sky-200 group-hover:scale-105 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span>+ Create Account Manually</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-[#1e3b8a]/10 border border-[#1e3b8a]/15 flex items-center justify-center text-[#1e3b8a] shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          </div>
          <div>
            <div id="stat-total-students" class="text-xl font-bold text-slate-900"><?= number_format($totalStudents ?? 0) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Total Enrolled</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="text-xl font-bold text-emerald-600"><?= number_format($activeStudents ?? 0) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Active Portal Users</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-700 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
          </div>
          <div>
            <div class="text-xl font-bold text-sky-700"><?= number_format($qrPairedStudents ?? 0) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">RFID / QR Paired</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div>
            <div class="text-xl font-bold text-amber-600"><?= number_format($pendingSetup ?? 0) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Pending Setup</div>
          </div>
        </div>
      </div>

      <!-- Filters, Program Selector & Live Search -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <!-- Search box -->
          <div class="relative w-full sm:w-80">
            <input type="text" id="search-student" placeholder="Search by name, student ID, email..." class="w-full pl-9 pr-9 py-2 text-xs bg-slate-50/60 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-[#1e3b8a] text-slate-800 transition" oninput="debouncedFilterStudents()">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <!-- Loading Spinner -->
            <div id="search-spinner" class="hidden absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none text-[#1e3b8a]">
              <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
              </svg>
            </div>
          </div>

          <!-- Program filter -->
          <select id="filter-program" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50/60 focus:bg-white focus:outline-none focus:border-[#1e3b8a] font-semibold text-slate-700 transition cursor-pointer" onchange="debouncedFilterStudents()">
            <option value="all">All Programs &amp; Courses</option>
            <option value="BSIT">BS Information Technology (BSIT)</option>
            <option value="BSIS">BS Information Systems (BSIS)</option>
            <option value="BSCS">BS Computer Science (BSCS)</option>
            <option value="BSBA">BS Business Administration (BSBA)</option>
          </select>

          <!-- Year Level Filter -->
          <select id="filter-year" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50/60 focus:bg-white focus:outline-none focus:border-[#1e3b8a] font-semibold text-slate-700 transition cursor-pointer" onchange="debouncedFilterStudents()">
            <option value="all">All Year Levels</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
          </select>

          <!-- Status filter -->
          <select id="filter-status" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50/60 focus:bg-white focus:outline-none focus:border-[#1e3b8a] font-semibold text-slate-700 transition cursor-pointer" onchange="debouncedFilterStudents()">
            <option value="all">All Statuses</option>
            <option value="active">Active Accounts Only</option>
            <option value="inactive">Inactive / Suspended</option>
          </select>
        </div>

        <div class="text-xs text-slate-400 font-medium">
          Showing <span id="visible-count" class="font-bold text-slate-800"><?= count($students) ?></span> of <strong class="text-slate-800"><?= number_format($totalStudents ?? 0) ?></strong> Students
        </div>
      </div>

      <!-- Student Master Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/80 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4">Student ID / Number</th>
                <th class="py-3.5 px-4">Student Full Name</th>
                <th class="py-3.5 px-4">Institutional Email</th>
                <th class="py-3.5 px-4">Course &amp; Year Level</th>
                <th class="py-3.5 px-4">Assigned Section</th>
                <th class="py-3.5 px-4">Account Status</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="students-table-body" class="divide-y divide-slate-100 text-slate-700">
              <?php if (empty($students)): ?>
                <tr>
                  <td colspan="7" class="py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center gap-2">
                      <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                      <span class="font-semibold text-sm text-slate-600">No Student Accounts Found</span>
                      <span class="text-xs">Click "+ Create Account Manually" or "Import Excel / CSV" to add students.</span>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($students as $st): ?>
                  <?php 
                    $fullName = htmlspecialchars($st['first_name'] . ' ' . $st['last_name']);
                    $initials = strtoupper(substr($st['first_name'], 0, 1) . substr($st['last_name'], 0, 1));
                  ?>
                  <tr class="student-row hover:bg-slate-50/60 transition-colors" 
                      data-program="<?= htmlspecialchars($st['course']) ?>" 
                      data-year="<?= htmlspecialchars($st['grade_level']) ?>" 
                      data-year-num="<?= (int) ($st['year_level'] ?? 3) ?>" 
                      data-status="<?= strtolower($st['status'] ?? 'active') ?>" 
                      data-text="<?= strtolower($st['student_code'] . ' ' . $fullName . ' ' . $st['email'] . ' ' . $st['course'] . ' ' . $st['grade_level'] . ' ' . $st['section']) ?>">
                    
                    <td class="py-3.5 px-4 font-mono font-semibold text-[#1e3b8a]">
                      <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full <?= $st['status'] === 'active' ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                        <span><?= htmlspecialchars($st['student_code']) ?></span>
                      </div>
                    </td>

                    <td class="py-3.5 px-4">
                      <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#1e3b8a] text-white font-bold text-[10px] flex items-center justify-center shrink-0">
                          <?= $initials ?>
                        </div>
                        <div>
                          <div class="font-bold text-slate-900"><?= $fullName ?></div>
                          <div class="text-[10px] text-slate-400">ID: #<?= $st['student_id'] ?></div>
                        </div>
                      </div>
                    </td>

                    <td class="py-3.5 px-4 text-slate-600 font-medium"><?= htmlspecialchars($st['email']) ?></td>
                    <td class="py-3.5 px-4 font-semibold text-slate-800">
                      <?php if ($st['course'] === 'Not Enrolled' || $st['section'] === 'Not Enrolled Yet'): ?>
                        <span class="text-slate-400 italic text-xs font-normal">Not Enrolled Yet</span>
                      <?php else: ?>
                        <?= htmlspecialchars($st['course']) ?> • <?= htmlspecialchars($st['grade_level']) ?>
                      <?php endif; ?>
                    </td>
                    
                    <td class="py-3.5 px-4">
                      <?php if ($st['section'] === 'Not Enrolled Yet' || empty($st['section']) || $st['section'] === 'Unassigned'): ?>
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-400 font-normal italic text-[10.5px]">
                          Not Enrolled Yet
                        </span>
                      <?php else: ?>
                        <span class="px-2 py-0.5 rounded-md bg-[#1e3b8a]/10 text-[#1e3b8a] font-mono font-bold text-[10.5px]">
                          <?= htmlspecialchars($st['section']) ?>
                        </span>
                      <?php endif; ?>
                    </td>

                    <td class="py-3.5 px-4">
                      <?php if ($st['status'] === 'active'): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">
                          Active Account
                        </span>
                      <?php else: ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                          <?= ucfirst(htmlspecialchars($st['status'])) ?>
                        </span>
                      <?php endif; ?>
                    </td>

                    <td class="py-3.5 px-4 text-right">
                      <div class="flex items-center justify-end gap-2">
                        <span class="px-2 py-0.5 rounded text-[10px] font-mono bg-slate-100 text-slate-600">
                          QR: <?= !empty($st['qr_code']) ? 'Linked' : 'None' ?>
                        </span>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <!-- Empty Filter Results Row -->
                <tr id="no-filter-results" class="hidden">
                  <td colspan="7" class="py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center gap-1.5">
                      <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                      <span class="font-semibold text-xs text-slate-600">No students match your filter criteria</span>
                      <span class="text-[11px] text-slate-400">Try adjusting the Program, Year Level, Status, or Search keywords.</span>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Student Master Table Pagination Footer Bar -->
        <div id="student-pagination-bar" class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <!-- Left: Showing X to Y of Z student record(s) -->
          <div class="text-slate-500 font-medium" id="student-pagination-info">
            Showing <span id="pagination-start" class="font-bold text-slate-800">1</span> to <span id="pagination-end" class="font-bold text-slate-800"><?= min(15, count($students)) ?></span> of <span id="pagination-total" class="font-bold text-slate-800"><?= count($students) ?></span> student record(s)
          </div>

          <!-- Right: Pagination Buttons & Navigation Controls -->
          <div class="flex items-center gap-1.5 flex-wrap" id="student-pagination-controls">
            <!-- Dynamically populated by renderStudentPagination -->
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: CREATE STUDENT ACCOUNT MANUALLY -->
<!-- ========================================================================= -->
<div id="manualStudentModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <!-- Modal Header -->
    <div class="px-6 py-4 bg-[#1e3b8a] text-white flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Manual Entry</span>
          <span class="text-xs text-sky-200 font-medium">Admin Form</span>
        </div>
        <h3 class="text-lg font-bold tracking-tight mt-0.5">Create Student Account Manually</h3>
      </div>
      <button type="button" onclick="closeManualStudentModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Modal Form Body -->
    <form id="manual-student-form" action="<?php echo url('admin/students/store'); ?>" method="POST" class="p-6 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Student Number / ID -->
        <div>
          <label for="m-student-id" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Student Number / ID <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="m-student-id" name="student_id" required placeholder="23011XXXX" data-next-id="<?= htmlspecialchars($nextStudentId ?? '230110007') ?>" class="w-full pl-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] font-mono font-bold text-slate-800 outline-none transition" style="padding-left: 1rem; padding-right: 4.75rem;">
            <button type="button" onclick="autoGenerateStudentId()" title="Auto-generate Student Number" class="absolute right-1.5 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-[#1e3b8a] text-[11px] font-semibold transition flex items-center gap-1 border border-slate-200 cursor-pointer">
              <svg class="w-3.5 h-3.5 text-[#1e3b8a]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
              </svg>
              <span>Auto</span>
            </button>
          </div>
          <div id="m-student-id-feedback" class="text-[11px] mt-1 hidden font-semibold"></div>
        </div>

        <!-- Full Name -->
        <div>
          <label for="m-student-name" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Student Full Name <span class="text-rose-500">*</span>
          </label>
          <input type="text" id="m-student-name" name="full_name" required placeholder="e.g. Christian Paul D. Ramos" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] font-semibold text-slate-800 outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Course / Degree -->
        <div>
          <label for="m-course" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Course Program <span class="text-rose-500">*</span>
          </label>
          <select id="m-course" name="course" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 font-semibold text-slate-800 outline-none">
            <option value="BSIT">BS Information Technology (BSIT)</option>
            <option value="BSIS">BS Information Systems (BSIS)</option>
            <option value="BSCS">BS Computer Science (BSCS)</option>
            <option value="BSBA">BS Business Administration (BSBA)</option>
          </select>
        </div>

        <!-- Year Level -->
        <div>
          <label for="m-year" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Year Level <span class="text-rose-500">*</span>
          </label>
          <select id="m-year" name="year_level" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 font-semibold text-slate-800 outline-none">
            <option value="1st Year">1st Year</option>
            <option value="2nd Year">2nd Year</option>
            <option value="3rd Year" selected>3rd Year</option>
            <option value="4th Year">4th Year</option>
          </select>
        </div>

        <!-- Assigned Section -->
        <div>
          <label for="m-section" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Assigned Section <span class="text-rose-500">*</span>
          </label>
          <div class="relative">
            <input type="text" id="m-section" name="section" required placeholder="e.g. 31001" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 font-bold text-slate-800 outline-none bg-slate-50/70 font-mono transition">
          </div>
          <div id="m-section-capacity-badge" class="text-[11px] mt-1.5 font-medium text-slate-500 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-[#1e3b8a]"></span>
            <span id="m-section-status-text">Auto-assigned based on Course &amp; Year Level</span>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Student Email -->
        <div>
          <label for="m-email-prefix" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Institutional Email <span class="text-rose-500">*</span>
          </label>
          <div class="flex rounded-xl border border-slate-300 overflow-hidden focus-within:ring-2 focus-within:ring-[#1e3b8a]/20 focus-within:border-[#1e3b8a] transition bg-white shadow-2xs">
            <input type="text" id="m-email-prefix" name="email_prefix" required placeholder="student.name" class="w-full px-3.5 py-2.5 text-xs font-mono font-medium text-slate-800 outline-none bg-transparent" autocomplete="off">
            <span class="inline-flex items-center px-3 text-xs font-semibold text-slate-500 bg-slate-50 border-l border-slate-200 select-none shrink-0 font-mono">
              @bcp.edu.ph
            </span>
          </div>
          <input type="hidden" id="m-email" name="email" value="">
        </div>

        <!-- Parent Contact Number / Email -->
        <div>
          <label for="m-parent" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Parent/Guardian Mobile or Email
          </label>
          <input type="text" id="m-parent" name="parent_contact" placeholder="0917-000-0000 / parent@email.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 font-medium text-slate-800 outline-none">
        </div>
      </div>

      <!-- Default Password Info -->
      <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
        <div>
          <div class="font-bold text-slate-800">Initial Student Password</div>
          <div class="text-[11px] text-slate-500">Format: # + 1st &amp; 2nd letter of Last Name + 8080. Prompted to change on first login.</div>
        </div>
        <span id="m-password-preview" class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-mono font-bold text-[#1e3b8a] text-xs shadow-2xs">#La8080</span>
      </div>

      <!-- Action Footer -->
      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closeManualStudentModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
          Cancel
        </button>
        <button type="submit" id="btn-manual-submit" class="px-6 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Save &amp; Create Student Account</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: IMPORT EXCEL / CSV ROSTER FILE -->
<!-- ========================================================================= -->
<div id="excelImportModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <form id="excel-import-form" action="<?= url('admin/students/import') ?>" method="POST" enctype="multipart/form-data">
      <!-- Header -->
      <div class="px-6 py-4 bg-[#1e3b8a] text-white flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Bulk CSV / Excel</span>
            <span class="text-xs text-sky-200 font-medium">Batch Account Provisioning</span>
          </div>
          <h3 class="text-lg font-bold tracking-tight mt-0.5">Import Student Accounts via Spreadsheet</h3>
        </div>
        <button type="button" onclick="closeExcelModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="p-6 space-y-5">
        <!-- Excel Guidelines -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-3.5">
          <div class="w-8 h-8 rounded-xl bg-[#1e3b8a]/10 text-[#1e3b8a] flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="text-xs text-slate-800">
            <div class="font-bold mb-0.5">Required Spreadsheet Format (.csv, .txt):</div>
            <p class="text-slate-600 text-[11.5px] leading-relaxed">
              Ensure your spreadsheet includes headers: <strong class="font-mono text-slate-900">student_id</strong>, <strong class="font-mono text-slate-900">full_name</strong>, <strong class="font-mono text-slate-900">email</strong>, <strong class="font-mono text-slate-900">course</strong>, and <strong class="font-mono text-slate-900">section</strong>. Student accounts with default credentials (<code class="bg-slate-200/70 px-1 py-0.5 rounded text-slate-800 font-semibold font-mono"># + Last Name Initials + 8080 (e.g. #Ra8080)</code>) and class roster mappings will be created automatically.
            </p>
          </div>
        </div>

        <!-- Drag and Drop Dropzone -->
        <div id="excel-dropzone" class="border-2 border-dashed border-slate-300 hover:border-[#1e3b8a] rounded-2xl p-7 text-center transition cursor-pointer bg-slate-50/70 hover:bg-slate-50 flex flex-col items-center justify-center gap-2 group" onclick="triggerExcelFileInput()">
          <input type="file" id="excel-file-input" name="csv_file" accept=".csv,.txt" class="hidden" onchange="handleExcelFileSelected(event)">
          <div class="w-12 h-12 rounded-2xl bg-[#1e3b8a]/10 text-[#1e3b8a] flex items-center justify-center group-hover:scale-105 transition shadow-xs">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          </div>
          <div class="text-xs font-bold text-slate-800">
            Click to choose CSV spreadsheet or drag and drop here
          </div>
          <div class="text-[11px] text-slate-400">Supported formats: .CSV, .TXT (Comma-separated values)</div>
          <div id="selected-file-pill" class="hidden mt-2 px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono text-xs font-bold flex items-center gap-2">
            <span>📄</span>
            <span id="selected-file-name">official_students_2026.csv</span>
          </div>
        </div>

        <!-- Live Parsed Excel Preview Section (Appears after file selected) -->
        <div id="excel-preview-box" class="hidden space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
              <span>Parsed Records Preview</span>
              <span id="excel-preview-count" class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Ready to Import</span>
            </div>
            <span class="text-[11px] text-slate-400 font-medium">Previewing records from chosen file</span>
          </div>

          <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-2 px-3">Student Number</th>
                  <th class="py-2 px-3">Student Name</th>
                  <th class="py-2 px-3">Course</th>
                  <th class="py-2 px-3">Section</th>
                  <th class="py-2 px-3 text-right">Status</th>
                </tr>
              </thead>
              <tbody id="excel-preview-tbody" class="divide-y divide-slate-100">
                <!-- Dynamically populated from actual file -->
              </tbody>
            </table>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
          <button type="button" onclick="downloadExcelTemplate()" class="text-xs font-semibold text-[#1e3b8a] hover:underline flex items-center gap-1.5 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download CSV Template (.csv)</span>
          </button>

          <div class="flex items-center gap-2.5">
            <button type="button" onclick="closeExcelModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
              Close
            </button>
            <button type="button" id="btn-process-excel" onclick="processExcelImport()" class="px-6 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>Create Accounts from CSV</span>
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Pagination configuration & state
var PAGE_SIZE = 15;
var WINDOW_SIZE = 30;
var studentCurrentPage = 1;
var filterDebounceTimer = null;

// Debounced filter handler (0.3 seconds / 300ms delay) with live search spinner
function debouncedFilterStudents() {
  clearTimeout(filterDebounceTimer);

  // Activate loading spinner and subtle table fade immediately on keystroke
  const searchSpinner = document.getElementById('search-spinner');
  const tableBody = document.getElementById('students-table-body');
  if (searchSpinner) searchSpinner.classList.remove('hidden');
  if (tableBody) tableBody.classList.add('opacity-50', 'transition-opacity');

  filterDebounceTimer = setTimeout(() => {
    studentCurrentPage = 1; // Reset to page 1 whenever search query or filter changes
    applyStudentFiltersAndPagination();
  }, 300);
}

// Backward-compatible alias
function filterStudents() {
  debouncedFilterStudents();
}

// Core filtering and pagination engine
function applyStudentFiltersAndPagination() {
  const program = (document.getElementById('filter-program')?.value || 'all').trim();
  const year = (document.getElementById('filter-year')?.value || 'all').trim();
  const status = (document.getElementById('filter-status')?.value || 'all').trim().toLowerCase();
  const query = (document.getElementById('search-student')?.value || '').toLowerCase().trim();

  const rows = Array.from(document.querySelectorAll('.student-row'));
  const matchingRows = [];

  rows.forEach(row => {
    const rowProg = (row.getAttribute('data-program') || '').toUpperCase();
    const rowYear = (row.getAttribute('data-year') || '').toLowerCase();
    const rowYearNum = (row.getAttribute('data-year-num') || '').trim();
    const rowStatus = (row.getAttribute('data-status') || 'active').toLowerCase();
    const rowText = (row.getAttribute('data-text') || '').toLowerCase();

    // 1. Program / Course Filter
    const matchProg = (program === 'all' || rowProg === program.toUpperCase());

    // 2. Year Level Filter (checks numeric or string format, e.g. "3" or "3rd Year")
    const matchYear = (
      year === 'all' || 
      rowYearNum === year || 
      rowYear.includes(year.toLowerCase())
    );

    // 3. Status Filter (Active vs Inactive/Suspended/Pending)
    let matchStatus = true;
    if (status === 'active') {
      matchStatus = (rowStatus === 'active');
    } else if (status === 'inactive') {
      matchStatus = (rowStatus !== 'active');
    }

    // 4. Live Text Search Filter
    const matchQuery = (!query || rowText.includes(query));

    if (matchProg && matchYear && matchStatus && matchQuery) {
      matchingRows.push(row);
    }
  });

  const totalMatching = matchingRows.length;
  const totalPages = Math.ceil(totalMatching / PAGE_SIZE) || 1;

  // Clamp current page within valid bounds
  if (studentCurrentPage > totalPages) {
    studentCurrentPage = totalPages;
  }
  if (studentCurrentPage < 1) {
    studentCurrentPage = 1;
  }

  const startIdx = (studentCurrentPage - 1) * PAGE_SIZE;
  const endIdx = Math.min(startIdx + PAGE_SIZE, totalMatching);

  // Hide all rows, then display only the matching rows belonging to the active page
  rows.forEach(row => {
    row.style.display = 'none';
  });

  matchingRows.slice(startIdx, endIdx).forEach(row => {
    row.style.display = '';
  });

  // Update top visible counter
  const countElem = document.getElementById('visible-count');
  if (countElem) {
    countElem.textContent = totalMatching;
  }

  // Toggle empty results row
  const noResultsRow = document.getElementById('no-filter-results');
  if (noResultsRow) {
    if (totalMatching === 0 && rows.length > 0) {
      noResultsRow.classList.remove('hidden');
    } else {
      noResultsRow.classList.add('hidden');
    }
  }

  // Render bottom pagination controls
  renderStudentPagination(totalMatching, startIdx, endIdx, totalPages);

  // Deactivate loading state once filtering and rendering is complete
  const searchSpinner = document.getElementById('search-spinner');
  const tableBody = document.getElementById('students-table-body');
  if (searchSpinner) searchSpinner.classList.add('hidden');
  if (tableBody) tableBody.classList.remove('opacity-50');
}

// Change active page and re-slice table
function changeStudentPage(newPage) {
  studentCurrentPage = newPage;
  applyStudentFiltersAndPagination();

  // Smooth scroll back to table top on page switch
  const tableContainer = document.getElementById('students-table-body');
  if (tableContainer) {
    tableContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

// Render pagination buttons with 30-page chunk navigation
function renderStudentPagination(totalMatching, startIdx, endIdx, totalPages) {
  const bar = document.getElementById('student-pagination-bar');
  const startEl = document.getElementById('pagination-start');
  const endEl = document.getElementById('pagination-end');
  const totalEl = document.getElementById('pagination-total');
  const controls = document.getElementById('student-pagination-controls');

  if (!bar || !controls) return;

  // The pagination bar remains permanently visible so users always see page context
  bar.classList.remove('hidden');

  if (totalMatching === 0) {
    if (startEl) startEl.textContent = '0';
    if (endEl) endEl.textContent = '0';
    if (totalEl) totalEl.textContent = '0';

    controls.innerHTML = `
      <button type="button" disabled title="Previous Page" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-300 bg-slate-50 text-xs font-bold flex items-center gap-1 cursor-not-allowed">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <span>Prev</span>
      </button>
      <button type="button" disabled class="w-8 h-8 rounded-lg border border-slate-200 bg-slate-100 text-xs font-bold text-slate-400 cursor-not-allowed">
        1
      </button>
      <button type="button" disabled title="Next Page" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-300 bg-slate-50 text-xs font-bold flex items-center gap-1 cursor-not-allowed">
        <span>Next</span>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </button>
    `;
    return;
  }

  if (startEl) startEl.textContent = (startIdx + 1).toLocaleString();
  if (endEl) endEl.textContent = endIdx.toLocaleString();
  if (totalEl) totalEl.textContent = totalMatching.toLocaleString();

  let html = '';

  // Previous Page Button
  const prevDisabled = studentCurrentPage <= 1;
  html += `
    <button type="button" 
            onclick="changeStudentPage(${studentCurrentPage - 1})" 
            ${prevDisabled ? 'disabled' : ''} 
            title="Previous Page"
            class="px-2.5 py-1.5 rounded-lg border text-xs font-bold transition flex items-center gap-1 ${
              prevDisabled 
                ? 'border-slate-200 text-slate-300 bg-slate-50 cursor-not-allowed' 
                : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer shadow-2xs'
            }">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      <span>Prev</span>
    </button>
  `;

  // 30-Page Windowing calculation
  // Displays page numbers in chunks of 30 (1–30, 31–60, 61–90...)
  const currentChunk = Math.floor((studentCurrentPage - 1) / WINDOW_SIZE);
  const windowStart = currentChunk * WINDOW_SIZE + 1;
  const windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);

  // If beyond chunk 1 (e.g. on page 31+), provide First Page and Jump-Back-30 button
  if (windowStart > 1) {
    html += `
      <button type="button" 
              onclick="changeStudentPage(1)" 
              title="Go to Page 1"
              class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer shadow-2xs">
        1
      </button>
      <button type="button" 
              onclick="changeStudentPage(${windowStart - 1})" 
              title="Previous 30 Pages"
              class="px-2 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-500 transition cursor-pointer shadow-2xs">
        «
      </button>
    `;
  }

  // Always render numbered page buttons for the active window
  // e.g. when filtered to 1 page: renders [1]
  // e.g. when filtered to 2 pages: renders [1] [2] (reduced from 3)
  // e.g. when 3 pages: renders [1] [2] [3]
  for (let p = windowStart; p <= windowEnd; p++) {
    const isActive = p === studentCurrentPage;
    if (isActive) {
      html += `
        <button type="button" 
                class="w-8 h-8 rounded-lg border border-blue-600 bg-blue-600 text-xs font-black text-white shadow-xs">
          ${p}
        </button>
      `;
    } else {
      html += `
        <button type="button" 
                onclick="changeStudentPage(${p})" 
                class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer shadow-2xs">
          ${p}
        </button>
      `;
    }
  }

  // If more pages exist past the current 30-page window, provide Jump-Forward-30 and Last Page button
  if (windowEnd < totalPages) {
    html += `
      <button type="button" 
              onclick="changeStudentPage(${windowEnd + 1})" 
              title="Next 30 Pages"
              class="px-2 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-500 transition cursor-pointer shadow-2xs">
        »
      </button>
      <button type="button" 
              onclick="changeStudentPage(${totalPages})" 
              title="Go to Page ${totalPages}"
              class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer shadow-2xs">
        ${totalPages}
      </button>
    `;
  }

  // Next Page Button
  // When on page 30 and user clicks Next, changeStudentPage(31) automatically transitions to the next 30-page chunk
  const nextDisabled = studentCurrentPage >= totalPages;
  html += `
    <button type="button" 
            onclick="changeStudentPage(${studentCurrentPage + 1})" 
            ${nextDisabled ? 'disabled' : ''} 
            title="Next Page"
            class="px-2.5 py-1.5 rounded-lg border text-xs font-bold transition flex items-center gap-1 ${
              nextDisabled 
                ? 'border-slate-200 text-slate-300 bg-slate-50 cursor-not-allowed' 
                : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer shadow-2xs'
            }">
      <span>Next</span>
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
  `;

  controls.innerHTML = html;
}

// Auto-generate new unique student ID (format: 23011XXXX)
function autoGenerateStudentId() {
  const idInput = document.getElementById('m-student-id');
  if (!idInput) return;

  const prefix = '23011';
  let highestSeq = 0;
  let padLength = 4;

  // 1. Read server-provided baseline if available
  const serverBaseline = (idInput.getAttribute('data-next-id') || '').trim();
  const serverMatch = serverBaseline.match(/^23011(\d+)$/);
  if (serverMatch) {
    const sNum = parseInt(serverMatch[1], 10);
    if (!isNaN(sNum)) {
      highestSeq = sNum - 1;
      padLength = Math.max(padLength, serverMatch[1].length);
    }
  }

  // 2. Scan existing student rows in directory to find highest sequence
  const rows = document.querySelectorAll('.student-row');
  rows.forEach(row => {
    const text = (row.getAttribute('data-text') || '');
    const matches = text.match(/23011[-]?(\d+)\b/);
    if (matches && matches[1]) {
      const num = parseInt(matches[1], 10);
      if (!isNaN(num) && num > highestSeq) {
        highestSeq = num;
        padLength = Math.max(padLength, matches[1].length);
      }
    }
  });

  // 3. Increment on repeated clicks if current input value is already >= nextSeq
  const currentVal = idInput.value.trim();
  const currMatch = currentVal.match(/^23011(\d+)$/);
  let nextSeq = highestSeq + 1;
  if (currMatch) {
    const currNum = parseInt(currMatch[1], 10);
    if (!isNaN(currNum) && currNum >= nextSeq) {
      nextSeq = currNum + 1;
    }
  }

  const formattedSeq = String(nextSeq).padStart(padLength, '0');
  const generatedId = `${prefix}${formattedSeq}`;

  idInput.value = generatedId;
  validateStudentIdUniqueness();

  // Visual pulse confirmation
  idInput.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50/50');
  setTimeout(() => {
    idInput.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50/50');
  }, 600);

  if (typeof APP !== 'undefined' && APP.toast) {
    APP.toast.info(`Auto-generated Student ID: ${generatedId}`);
  }
}

// Real-time Student ID uniqueness validation
function validateStudentIdUniqueness() {
  const idInput = document.getElementById('m-student-id');
  const feedback = document.getElementById('m-student-id-feedback');
  const submitBtn = document.getElementById('btn-manual-submit');
  if (!idInput) return true;

  const enteredId = idInput.value.trim();
  if (!enteredId) {
    if (feedback) {
      feedback.classList.add('hidden');
      feedback.textContent = '';
    }
    idInput.classList.remove('border-rose-500', 'text-rose-600', 'focus:ring-rose-500');
    if (submitBtn) submitBtn.disabled = false;
    return true;
  }

  // Scan student rows for exact student ID match
  let conflictName = null;
  const rows = document.querySelectorAll('.student-row');
  for (const row of rows) {
    const firstCol = row.querySelector('td:first-child');
    const rowId = firstCol ? firstCol.textContent.replace(/[^\d]/g, '').trim() : '';
    if (rowId === enteredId) {
      const nameElem = row.querySelector('.font-bold.text-slate-900');
      conflictName = nameElem ? nameElem.textContent.trim() : 'another student';
      break;
    }
  }

  if (conflictName) {
    if (feedback) {
      feedback.classList.remove('hidden', 'text-emerald-600');
      feedback.classList.add('text-rose-600');
      feedback.innerHTML = `⚠️ Student ID <strong>${enteredId}</strong> is already assigned to <strong>${escapeHtml(conflictName)}</strong>.`;
    }
    idInput.classList.add('border-rose-500', 'text-rose-600', 'focus:ring-rose-500');
    if (submitBtn) submitBtn.disabled = true;
    return false;
  } else {
    if (feedback) {
      feedback.classList.add('hidden');
      feedback.textContent = '';
    }
    idInput.classList.remove('border-rose-500', 'text-rose-600', 'focus:ring-rose-500');
    if (submitBtn) submitBtn.disabled = false;
    return true;
  }
}

// Real-time password preview generator (# + Last Name Initials + 8080)
function updatePasswordPreview() {
  const nameInput = document.getElementById('m-student-name');
  const preview = document.getElementById('m-password-preview');
  if (!nameInput || !preview) return;

  const val = nameInput.value.trim();
  if (!val) {
    preview.textContent = '#La8080';
    return;
  }

  const parts = val.split(/\s+/).filter(Boolean);
  const lastName = parts.length > 1 ? parts[parts.length - 1] : parts[0];
  const clean = lastName.replace(/[^a-zA-Z]/g, '');

  let c1 = 'S', c2 = 't';
  if (clean.length >= 2) {
    c1 = clean.charAt(0).toUpperCase();
    c2 = clean.charAt(1).toLowerCase();
  } else if (clean.length === 1) {
    c1 = clean.charAt(0).toUpperCase();
    c2 = 'x';
  }

  preview.textContent = '#' + c1 + c2 + '8080';
}

// Institutional Email handlers
var emailPrefixManuallyEdited = false;

function syncInstitutionalEmail() {
  const prefixInput = document.getElementById('m-email-prefix');
  const hiddenEmail = document.getElementById('m-email');
  if (!prefixInput || !hiddenEmail) return;

  let val = prefixInput.value.trim();
  // Automatically strip @... if admin pasted full email address
  if (val.includes('@')) {
    val = val.split('@')[0].trim();
    prefixInput.value = val;
  }
  hiddenEmail.value = val ? val.toLowerCase() + '@bcp.edu.ph' : '';
}

function autoSuggestEmailPrefix() {
  if (emailPrefixManuallyEdited) return;
  const nameInput = document.getElementById('m-student-name');
  const prefixInput = document.getElementById('m-email-prefix');
  if (!nameInput || !prefixInput) return;

  const val = nameInput.value.trim();
  if (!val) {
    prefixInput.value = '';
    syncInstitutionalEmail();
    return;
  }

  const parts = val.split(/\s+/).filter(Boolean);
  if (parts.length >= 2) {
    const first = parts[0].replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
    const last = parts[parts.length - 1].replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
    prefixInput.value = `${first}.${last}`;
  } else {
    prefixInput.value = parts[0].replace(/[^a-zA-Z0-9]/g, '').toLowerCase();
  }
  syncInstitutionalEmail();
}

// Section Enrollment Counts passed from database
var sectionRosterCounts = <?= json_encode($sectionCounts ?? []) ?>;

// Dynamic Section Assignment & 50-Student Capacity Auto-Rollover (strictly 5 digits: e.g. 31001)
function updateAssignedSection() {
  const courseSelect = document.getElementById('m-course');
  const yearSelect = document.getElementById('m-year');
  const sectionInput = document.getElementById('m-section');
  const statusText = document.getElementById('m-section-status-text');
  if (!courseSelect || !yearSelect || !sectionInput) return;

  const course = (courseSelect.value || 'BSIT').trim().toUpperCase();
  const yearVal = yearSelect.value || '1st Year';
  let yearNum = 1;
  const match = yearVal.match(/\d+/);
  if (match) yearNum = parseInt(match[0], 10);

  let seq = 1;
  let assignedSection = '';
  let currentCount = 0;

  // Scan sequence (e.g. 11001, 21001, 31001, 41001...) until a section with < 50 students is found
  while (seq <= 999) {
    const candidate = `${yearNum}1${String(seq).padStart(3, '0')}`;
    const count = (sectionRosterCounts[candidate] || 0) + (sectionRosterCounts[`${course} ${candidate}`] || 0);
    if (count < 50) {
      assignedSection = candidate;
      currentCount = count;
      break;
    }
    seq++;
  }

  sectionInput.value = assignedSection;

  if (statusText) {
    if (currentCount === 0) {
      statusText.innerHTML = `<span class="text-emerald-600 font-semibold">New Section: 0 / 50 Enrolled</span>`;
    } else {
      statusText.innerHTML = `<span class="text-blue-600 font-semibold">Enrolled: ${currentCount} / 50 Students</span>`;
    }
  }
}

// Manual Student Modal controls
function openManualStudentModal() {
  const modal = document.getElementById('manualStudentModal');
  if (modal) modal.classList.remove('hidden');
  validateStudentIdUniqueness();
  updatePasswordPreview();
  syncInstitutionalEmail();
  updateAssignedSection();
}

function closeManualStudentModal() {
  const modal = document.getElementById('manualStudentModal');
  if (modal) modal.classList.add('hidden');
}

// Excel Import Modal controls
function openExcelModal() {
  document.getElementById('excelImportModal').classList.remove('hidden');
}

function closeExcelModal() {
  document.getElementById('excelImportModal').classList.add('hidden');
}

function triggerExcelFileInput() {
  document.getElementById('excel-file-input').click();
}

function handleExcelFileSelected(event) {
  const file = event.target.files[0];
  if (!file) return;

  document.getElementById('selected-file-name').textContent = file.name;
  document.getElementById('selected-file-pill').classList.remove('hidden');

  // Read CSV locally for instant live preview
  const reader = new FileReader();
  reader.onload = function(e) {
    const text = e.target.result;
    const lines = text.split(/\r\n|\n/).filter(line => line.trim() !== '');
    if (lines.length > 1) {
      // Parse header row
      const headers = lines[0].split(',').map(h => h.trim().replace(/^["']|["']$/g, '').toLowerCase());
      const findIndex = (aliases) => {
        for (const a of aliases) {
          const idx = headers.indexOf(a);
          if (idx !== -1) return idx;
        }
        return -1;
      };

      const idIdx = findIndex(['student_id', 'student_number', 'id', 'student_no']);
      const nameIdx = findIndex(['full_name', 'student_name', 'name']);
      const courseIdx = findIndex(['course', 'program']);
      const secIdx = findIndex(['section']);

      const tbody = document.getElementById('excel-preview-tbody');
      tbody.innerHTML = '';
      const previewRows = lines.slice(1, 6); // Preview first 5 rows

      previewRows.forEach(line => {
        const cols = line.split(',').map(c => c.trim().replace(/^["']|["']$/g, ''));
        const idVal = idIdx !== -1 ? cols[idIdx] : cols[0] || '---';
        const nameVal = nameIdx !== -1 ? cols[nameIdx] : cols[1] || '---';
        const courseVal = courseIdx !== -1 ? cols[courseIdx] : 'BSIT';
        const secVal = secIdx !== -1 ? cols[secIdx] : '3-A';

        const tr = document.createElement('tr');
        tr.className = 'hover:bg-slate-50';
        tr.innerHTML = `
          <td class="py-2 px-3 font-mono font-bold text-blue-700">${escapeHtml(idVal)}</td>
          <td class="py-2 px-3 font-bold text-slate-800">${escapeHtml(nameVal)}</td>
          <td class="py-2 px-3">${escapeHtml(courseVal)}</td>
          <td class="py-2 px-3 font-bold text-blue-700">${escapeHtml(secVal)}</td>
          <td class="py-2 px-3 text-right"><span class="text-emerald-600 font-bold text-[10px]">✓ Valid Row</span></td>
        `;
        tbody.appendChild(tr);
      });

      const countBadge = document.getElementById('excel-preview-count');
      if (countBadge) {
        countBadge.textContent = `${lines.length - 1} Ready to Import`;
      }
      document.getElementById('excel-preview-box').classList.remove('hidden');
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast.info(`Spreadsheet parsed: ${lines.length - 1} student records found.`);
      }
    }
  };
  reader.readAsText(file);
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function downloadExcelTemplate() {
  window.location.href = '<?= url("admin/students/template") ?>';
}

function processExcelImport() {
  const fileInput = document.getElementById('excel-file-input');
  if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
    if (typeof APP !== 'undefined' && APP.toast) {
      APP.toast.error('Please select a CSV file first.');
    } else {
      alert('Please select a CSV file first.');
    }
    return;
  }
  const form = document.getElementById('excel-import-form');
  if (form) {
    const btn = document.getElementById('btn-process-excel');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `
        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
        <span>Importing Accounts...</span>
      `;
    }
    form.submit();
  }
}

// Form validation and URL notification handlers
document.addEventListener('DOMContentLoaded', function() {
  // Real-time listener on manual student input
  const mStudentId = document.getElementById('m-student-id');
  if (mStudentId) {
    mStudentId.addEventListener('input', validateStudentIdUniqueness);
  }

  // Live password preview & email auto-suggestion based on entered name
  const mStudentName = document.getElementById('m-student-name');
  if (mStudentName) {
    mStudentName.addEventListener('input', function() {
      updatePasswordPreview();
      autoSuggestEmailPrefix();
    });
  }

  // Institutional email input listener
  const mEmailPrefix = document.getElementById('m-email-prefix');
  if (mEmailPrefix) {
    mEmailPrefix.addEventListener('input', function() {
      emailPrefixManuallyEdited = this.value.trim().length > 0;
      syncInstitutionalEmail();
    });
  }

  // Dynamic section calculation when Course or Year Level changes
  const mCourse = document.getElementById('m-course');
  if (mCourse) {
    mCourse.addEventListener('change', updateAssignedSection);
  }
  const mYear = document.getElementById('m-year');
  if (mYear) {
    mYear.addEventListener('change', updateAssignedSection);
  }
  updateAssignedSection();

  // Prevent form submission if student ID is duplicated
  const manualForm = document.getElementById('manual-student-form');
  if (manualForm) {
    manualForm.addEventListener('submit', function(e) {
      syncInstitutionalEmail();
      if (!validateStudentIdUniqueness()) {
        e.preventDefault();
        if (typeof APP !== 'undefined' && APP.toast) {
          APP.toast.error('Please resolve conflicting student number before proceeding.');
        }
      }
    });
  }

  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has('created')) {
    const studentName = urlParams.get('created');
    if (typeof APP !== 'undefined' && APP.toast) {
      APP.toast.success('Student account for ' + studentName + ' created successfully!');
    }
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (urlParams.has('imported')) {
    const count = urlParams.get('imported');
    const skipped = urlParams.get('skipped');
    let msg = 'Successfully imported ' + count + ' student account' + (count != 1 ? 's' : '') + '!';
    if (skipped && parseInt(skipped) > 0) {
      msg += ' (' + skipped + ' skipped as duplicates)';
    }
    if (typeof APP !== 'undefined' && APP.toast) {
      APP.toast.success(msg);
    }
    window.history.replaceState({}, document.title, window.location.pathname);
  } else if (urlParams.has('error')) {
    const errorMsg = urlParams.get('error');
    if (typeof APP !== 'undefined' && APP.toast) {
      APP.toast.error(errorMsg);
    }
    window.history.replaceState({}, document.title, window.location.pathname);
  }

  // Initialize client-side 15-per-page pagination and debounced filter state on load
  applyStudentFiltersAndPagination();
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
