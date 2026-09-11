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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Master Accounts Directory</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Student Accounts &amp; Master Roster</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Manage institutional student accounts, create credentials manually, or batch import students via Excel/CSV spreadsheets containing student names and student numbers.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <!-- Import Excel Button -->
          <button type="button" onclick="openExcelModal()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 hover:shadow-lg transition flex items-center gap-2 group">
            <svg class="w-4 h-4 text-emerald-200 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Import Excel / CSV Roster</span>
          </button>

          <!-- Create Manually Button -->
          <button type="button" onclick="openManualStudentModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 hover:shadow-lg transition flex items-center gap-2 group">
            <svg class="w-4 h-4 text-blue-200 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span>+ Create Account Manually</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          </div>
          <div>
            <div id="stat-total-students" class="text-xl font-black text-slate-900"><?= number_format($totalStudents ?? 0) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Total Enrolled</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-emerald-600"><?= number_format($activeStudents ?? 0) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Active Portal Users</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-indigo-600"><?= number_format($qrPairedStudents ?? 0) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">RFID / QR Paired</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-amber-600"><?= number_format($pendingSetup ?? 0) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Pending Setup</div>
          </div>
        </div>
      </div>

      <!-- Filters, Program Selector & Live Search -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <!-- Search box -->
          <div class="relative w-full sm:w-80">
            <input type="text" id="search-student" placeholder="Search by name, student ID, email..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition shadow-2xs" oninput="filterStudents()">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>

          <!-- Program filter -->
          <select id="filter-program" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 font-semibold text-slate-700 transition cursor-pointer" onchange="filterStudents()">
            <option value="all">All Programs &amp; Courses</option>
            <option value="BSIT">BS Information Technology (BSIT)</option>
            <option value="BSIS">BS Information Systems (BSIS)</option>
            <option value="BSCS">BS Computer Science (BSCS)</option>
            <option value="BSBA">BS Business Administration (BSBA)</option>
          </select>

          <!-- Year Level Filter -->
          <select id="filter-year" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 font-semibold text-slate-700 transition cursor-pointer" onchange="filterStudents()">
            <option value="all">All Year Levels</option>
            <option value="1">1st Year</option>
            <option value="2">2nd Year</option>
            <option value="3">3rd Year</option>
            <option value="4">4th Year</option>
          </select>

          <!-- Status filter -->
          <select id="filter-status" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 font-semibold text-slate-700 transition cursor-pointer" onchange="filterStudents()">
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
            <thead class="bg-slate-50/90 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
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
                  <tr class="student-row hover:bg-slate-50/80 transition" 
                      data-program="<?= htmlspecialchars($st['course']) ?>" 
                      data-year="<?= htmlspecialchars($st['grade_level']) ?>" 
                      data-year-num="<?= (int) ($st['year_level'] ?? 3) ?>" 
                      data-status="<?= strtolower($st['status'] ?? 'active') ?>" 
                      data-text="<?= strtolower($st['student_code'] . ' ' . $fullName . ' ' . $st['email'] . ' ' . $st['course'] . ' ' . $st['grade_level'] . ' ' . $st['section']) ?>">
                    
                    <td class="py-3.5 px-4 font-mono font-bold text-blue-700">
                      <div class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full <?= $st['status'] === 'active' ? 'bg-emerald-500' : 'bg-slate-400' ?>"></span>
                        <span><?= htmlspecialchars($st['student_code']) ?></span>
                      </div>
                    </td>

                    <td class="py-3.5 px-4">
                      <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0">
                          <?= $initials ?>
                        </div>
                        <div>
                          <div class="font-bold text-slate-900"><?= $fullName ?></div>
                          <div class="text-[10px] text-slate-400">ID: #<?= $st['student_id'] ?></div>
                        </div>
                      </div>
                    </td>

                    <td class="py-3.5 px-4 text-slate-600 font-medium"><?= htmlspecialchars($st['email']) ?></td>
                    <td class="py-3.5 px-4 font-semibold text-slate-800"><?= htmlspecialchars($st['course']) ?> • <?= htmlspecialchars($st['grade_level']) ?></td>
                    
                    <td class="py-3.5 px-4">
                      <span class="px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 font-bold text-[10.5px]">
                        <?= htmlspecialchars($st['section']) ?>
                      </span>
                    </td>

                    <td class="py-3.5 px-4">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $st['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600' ?>">
                        <?= ucfirst(htmlspecialchars($st['status'])) ?> Account
                      </span>
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
      </div>
    </main>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: CREATE STUDENT ACCOUNT MANUALLY -->
<!-- ========================================================================= -->
<div id="manualStudentModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <!-- Modal Header -->
    <div class="px-6 py-5 bg-gradient-to-r from-blue-700 to-indigo-700 text-white flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Manual Entry</span>
          <span class="text-xs text-blue-200 font-medium">Admin Form</span>
        </div>
        <h3 class="text-lg font-black tracking-tight mt-1">Create Student Account Manually</h3>
      </div>
      <button type="button" onclick="closeManualStudentModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
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
            <input type="text" id="m-student-id" name="student_id" required placeholder="23011XXXX" data-next-id="<?= htmlspecialchars($nextStudentId ?? '230110007') ?>" class="w-full pl-4 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono font-bold text-slate-800 outline-none transition" style="padding-left: 1rem; padding-right: 4.75rem;">
            <button type="button" onclick="autoGenerateStudentId()" title="Auto-generate Student Number" class="absolute right-1.5 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-bold transition flex items-center gap-1 border border-blue-200 shadow-2xs group cursor-pointer">
              <svg class="w-3.5 h-3.5 text-blue-600 group-hover:rotate-45 transition duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
          <input type="text" id="m-student-name" name="full_name" required placeholder="e.g. Christian Paul D. Ramos" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-semibold text-slate-800 outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Course / Degree -->
        <div>
          <label for="m-course" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Course Program <span class="text-rose-500">*</span>
          </label>
          <select id="m-course" name="course" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 outline-none">
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
          <select id="m-year" name="year_level" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 outline-none">
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
          <input type="text" id="m-section" name="section" required placeholder="e.g. 3-A or 3-B" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 font-semibold text-slate-800 outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Student Email -->
        <div>
          <label for="m-email" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Institutional Email <span class="text-rose-500">*</span>
          </label>
          <input type="email" id="m-email" name="email" required placeholder="student.name@bestlink.edu.ph" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 outline-none">
        </div>

        <!-- Parent Contact Number / Email -->
        <div>
          <label for="m-parent" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Parent/Guardian Mobile or Email
          </label>
          <input type="text" id="m-parent" name="parent_contact" placeholder="0917-000-0000 / parent@email.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-blue-500 font-medium text-slate-800 outline-none">
        </div>
      </div>

      <!-- Default Password Info -->
      <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
        <div>
          <div class="font-bold text-slate-800">Initial Student Password</div>
          <div class="text-[11px] text-slate-500">Defaults to student number. Prompted to change on first login.</div>
        </div>
        <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-mono font-bold text-blue-700 text-xs shadow-2xs">BCP@2026</span>
      </div>

      <!-- Action Footer -->
      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closeManualStudentModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
          Cancel
        </button>
        <button type="submit" id="btn-manual-submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-2">
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
  <div class="relative w-full max-w-3xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <form id="excel-import-form" action="<?= url('admin/students/import') ?>" method="POST" enctype="multipart/form-data">
      <!-- Header -->
      <div class="px-6 py-5 bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-800 text-white flex items-center justify-between">
        <div>
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Bulk CSV / Excel Importer</span>
            <span class="text-xs text-emerald-200 font-medium">Batch Account Provisioning</span>
          </div>
          <h3 class="text-lg font-black tracking-tight mt-1">Import Student Accounts via Spreadsheet</h3>
        </div>
        <button type="button" onclick="closeExcelModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="p-6 space-y-5">
        <!-- Excel Guidelines -->
        <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 flex items-start gap-3.5">
          <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div class="text-xs text-emerald-950">
            <div class="font-bold mb-0.5">Required Spreadsheet Format (.csv, .txt):</div>
            <p class="text-emerald-800 text-[11.5px] leading-relaxed">
              Ensure your spreadsheet includes headers: <strong class="font-mono text-emerald-950">student_id</strong>, <strong class="font-mono text-emerald-950">full_name</strong>, <strong class="font-mono text-emerald-950">email</strong>, <strong class="font-mono text-emerald-950">course</strong>, and <strong class="font-mono text-emerald-950">section</strong>. Student accounts with default credentials (<code class="bg-emerald-200/60 px-1 py-0.5 rounded text-emerald-900 font-semibold">BCP@2026</code>) and class roster mappings will be created automatically.
            </p>
          </div>
        </div>

        <!-- Drag and Drop Dropzone -->
        <div id="excel-dropzone" class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-7 text-center transition cursor-pointer bg-slate-50/70 hover:bg-emerald-50/30 flex flex-col items-center justify-center gap-2 group" onclick="triggerExcelFileInput()">
          <input type="file" id="excel-file-input" name="csv_file" accept=".csv,.txt" class="hidden" onchange="handleExcelFileSelected(event)">
          <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition shadow-xs">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          </div>
          <div class="text-xs font-bold text-slate-800">
            Click to choose CSV spreadsheet or drag and drop here
          </div>
          <div class="text-[11px] text-slate-400">Supported formats: .CSV, .TXT (Comma-separated values)</div>
          <div id="selected-file-pill" class="hidden mt-2 px-3 py-1 rounded-lg bg-emerald-100 border border-emerald-200 text-emerald-800 font-mono text-xs font-bold flex items-center gap-2">
            <span>📄</span>
            <span id="selected-file-name">official_students_2026.csv</span>
          </div>
        </div>

        <!-- Live Parsed Excel Preview Section (Appears after file selected) -->
        <div id="excel-preview-box" class="hidden space-y-3">
          <div class="flex items-center justify-between">
            <div class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
              <span>Parsed Records Preview</span>
              <span id="excel-preview-count" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Ready to Import</span>
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
          <button type="button" onclick="downloadExcelTemplate()" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download CSV Template (.csv)</span>
          </button>

          <div class="flex items-center gap-2.5">
            <button type="button" onclick="closeExcelModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
              Close
            </button>
            <button type="button" id="btn-process-excel" onclick="processExcelImport()" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
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
// Filter students live
function filterStudents() {
  const program = (document.getElementById('filter-program')?.value || 'all').trim();
  const year = (document.getElementById('filter-year')?.value || 'all').trim();
  const status = (document.getElementById('filter-status')?.value || 'all').trim().toLowerCase();
  const query = (document.getElementById('search-student')?.value || '').toLowerCase().trim();

  const rows = document.querySelectorAll('.student-row');
  let visible = 0;

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
      row.style.display = '';
      visible++;
    } else {
      row.style.display = 'none';
    }
  });

  const countElem = document.getElementById('visible-count');
  if (countElem) {
    countElem.textContent = visible;
  }

  // Toggle empty results row
  const noResultsRow = document.getElementById('no-filter-results');
  if (noResultsRow) {
    if (visible === 0 && rows.length > 0) {
      noResultsRow.classList.remove('hidden');
    } else {
      noResultsRow.classList.add('hidden');
    }
  }
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

// Manual Student Modal controls
function openManualStudentModal() {
  const modal = document.getElementById('manualStudentModal');
  if (modal) modal.classList.remove('hidden');
  validateStudentIdUniqueness();
}

function closeManualStudentModal() {
  const modal = document.getElementById('manualStudentModal');
  if (modal) modal.classList.add('hidden');
}

function handleManualStudentSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('m-student-id').value.trim();
  const name = document.getElementById('m-student-name').value.trim();
  const course = document.getElementById('m-course').value;
  const year = document.getElementById('m-year').value;
  const section = document.getElementById('m-section').value.trim();
  const email = document.getElementById('m-email').value.trim();

  if (!id || !name || !section || !email) {
    APP.toast('Please complete all required fields.', 'error');
    return;
  }

  // Get Initials
  const initials = name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

  // Create new table row
  const tbody = document.getElementById('students-table-body');
  const tr = document.createElement('tr');
  tr.className = 'student-row hover:bg-slate-50/80 transition bg-blue-50/30';
  tr.setAttribute('data-program', course);
  tr.setAttribute('data-year', year);
  tr.setAttribute('data-status', 'Active');
  tr.setAttribute('data-text', `${id} ${name} ${email} ${course} ${section}`);

  tr.innerHTML = `
    <td class="py-3.5 px-4 font-mono font-bold text-blue-700">
      <div class="flex items-center gap-1.5">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
        <span>${id}</span>
      </div>
    </td>
    <td class="py-3.5 px-4">
      <div class="flex items-center gap-2.5">
        <div class="w-7 h-7 rounded-lg bg-blue-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0">${initials}</div>
        <div>
          <div class="font-bold text-slate-900">${name}</div>
          <div class="text-[10px] text-emerald-600 font-bold">● Just Created (Manual)</div>
        </div>
      </div>
    </td>
    <td class="py-3.5 px-4 text-slate-600 font-medium">${email}</td>
    <td class="py-3.5 px-4 font-semibold text-slate-800">${course} • ${year}</td>
    <td class="py-3.5 px-4"><span class="px-2.5 py-1 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 font-bold text-[10.5px]">${course} ${section}</span></td>
    <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Active Account</span></td>
    <td class="py-3.5 px-4 text-right">
      <div class="flex items-center justify-end gap-2">
        <button type="button" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition" onclick="APP.toast('Editing student record ${id}', 'info')">Edit</button>
        <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 transition" onclick="APP.toast('Reset password link sent to student email', 'success')" title="Reset Password">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        </button>
      </div>
    </td>
  `;

  tbody.insertBefore(tr, tbody.firstChild);

  // Update counter
  const totalElem = document.getElementById('stat-total-students');
  if (totalElem) {
    const curr = parseInt(totalElem.textContent.replace(/,/g, '')) || 1248;
    totalElem.textContent = (curr + 1).toLocaleString();
  }

  filterStudents();
  closeManualStudentModal();
  document.getElementById('manual-student-form').reset();
  APP.toast(`Student account for ${name} (${id}) created successfully!`, 'success');
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

  // Prevent form submission if student ID is duplicated
  const manualForm = document.getElementById('manual-student-form');
  if (manualForm) {
    manualForm.addEventListener('submit', function(e) {
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
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
