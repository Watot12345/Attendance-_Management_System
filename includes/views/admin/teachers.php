<?php
$page_title = 'Faculty & Teacher Master Directory';
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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Faculty Master Accounts</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Teacher &amp; Faculty Master Directory</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Manage authorized faculty accounts, provision credentials manually, or batch import instructor profiles via Excel/CSV files containing employee numbers and teacher names.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <!-- Import Excel Button -->
          <button type="button" onclick="openTeacherExcelModal()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 hover:shadow-lg transition flex items-center gap-2 group">
            <svg class="w-4 h-4 text-emerald-200 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Import Faculty via Excel</span>
          </button>

          <!-- Create Manually Button -->
          <button type="button" onclick="openManualTeacherModal()" class="px-4 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20 hover:shadow-lg transition flex items-center gap-2 group">
            <svg class="w-4 h-4 text-purple-200 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span>+ Create Faculty Account Manually</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
          </div>
          <div>
            <div id="stat-total-faculty" class="text-xl font-black text-slate-900">48</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Total Faculty Members</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-emerald-600">46</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Active Teachers Today</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-blue-600">124</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Assigned Class Sections</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-amber-600">2</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">On Official Leave</div>
          </div>
        </div>
      </div>

      <!-- Filters & Live Search -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <!-- Search box -->
          <div class="relative w-full sm:w-80">
            <input type="text" id="search-teacher" placeholder="Search by name, employee ID, department..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-purple-500 text-slate-800 transition shadow-2xs" oninput="filterTeachers()">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>

          <!-- Department filter -->
          <select id="filter-dept" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-purple-500 font-semibold text-slate-700 transition" onchange="filterTeachers()">
            <option value="all">All Departments</option>
            <option value="Computer Studies">Computer Studies (IT / CS / IS)</option>
            <option value="Business Administration">Business Administration</option>
            <option value="General Education">General Education</option>
          </select>

          <!-- Status filter -->
          <select id="filter-status" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-purple-500 font-semibold text-slate-700 transition" onchange="filterTeachers()">
            <option value="all">All Statuses</option>
            <option value="Active">Active Faculty</option>
            <option value="On Leave">On Leave</option>
          </select>
        </div>

        <div class="text-xs text-slate-400 font-medium">
          Showing <span id="visible-teacher-count" class="font-bold text-slate-800">2</span> of <strong class="text-slate-800">48</strong> Faculty Records
        </div>
      </div>

      <!-- Faculty Master Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/90 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4">Employee ID / Number</th>
                <th class="py-3.5 px-4">Faculty Member Name</th>
                <th class="py-3.5 px-4">Department / College</th>
                <th class="py-3.5 px-4">Assigned Subject &amp; Classes</th>
                <th class="py-3.5 px-4">Total Students</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="teachers-table-body" class="divide-y divide-slate-100 text-slate-700">
              <!-- Faculty Row 1 -->
              <tr class="teacher-row hover:bg-slate-50/80 transition" data-dept="Computer Studies" data-status="Active" data-text="EMP-2024-0042 Maria Ramirez m.ramirez@bestlink.edu.ph Computer Studies IT301">
                <td class="py-3.5 px-4 font-mono font-bold text-purple-700">
                  <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>EMP-2024-0042</span>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white font-bold text-xs flex items-center justify-center shrink-0">MR</div>
                    <div>
                      <div class="font-bold text-slate-900">Prof. Maria Ramirez</div>
                      <div class="text-[10px] text-slate-400">m.ramirez@bestlink.edu.ph</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">Computer Studies (IT)</td>
                <td class="py-3.5 px-4">
                  <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-lg bg-blue-50 border border-blue-200 text-blue-700 text-[10.5px] font-bold">IT301 (BSIT 3-A)</span>
                    <span class="px-2 py-0.5 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 text-[10.5px] font-bold">IT302 (BSIT 3-B)</span>
                    <span class="px-2 py-0.5 rounded-lg bg-indigo-50 border border-indigo-200 text-indigo-700 text-[10.5px] font-bold">IT303 (BSIT 3-C)</span>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-900">116 Students</td>
                <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Active Faculty</span></td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button type="button" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition" onclick="APP.toast('Opening assignment manager for Prof. Ramirez', 'info')">Manage</button>
                    <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 transition" onclick="APP.toast('Reset password link sent to instructor email', 'success')" title="Reset Password">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </button>
                  </div>
                </td>
              </tr>

              <!-- Faculty Row 2 -->
              <tr class="teacher-row hover:bg-slate-50/80 transition" data-dept="Computer Studies" data-status="Active" data-text="EMP-2023-0018 Arturo Santos a.santos@bestlink.edu.ph Computer Studies CS401">
                <td class="py-3.5 px-4 font-mono font-bold text-purple-700">
                  <div class="flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>EMP-2023-0018</span>
                  </div>
                </td>
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-purple-600 text-white font-bold text-xs flex items-center justify-center shrink-0">AS</div>
                    <div>
                      <div class="font-bold text-slate-900">Prof. Arturo Santos</div>
                      <div class="text-[10px] text-slate-400">a.santos@bestlink.edu.ph</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">Computer Studies (CS)</td>
                <td class="py-3.5 px-4">
                  <div class="flex flex-wrap gap-1.5">
                    <span class="px-2 py-0.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10.5px] font-bold">CS401 (BSCS 4-A)</span>
                    <span class="px-2 py-0.5 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 text-[10.5px] font-bold">CS402 (BSCS 4-B)</span>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-bold text-slate-900">68 Students</td>
                <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Active Faculty</span></td>
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button type="button" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition" onclick="APP.toast('Opening assignment manager for Prof. Santos', 'info')">Manage</button>
                    <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 transition" onclick="APP.toast('Reset password link sent to instructor email', 'success')" title="Reset Password">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: CREATE FACULTY ACCOUNT MANUALLY -->
<!-- ========================================================================= -->
<div id="manualTeacherModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-2xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <!-- Header -->
    <div class="px-6 py-5 bg-gradient-to-r from-purple-700 to-indigo-700 text-white flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Faculty Provisioning</span>
          <span class="text-xs text-purple-200 font-medium">Manual Entry</span>
        </div>
        <h3 class="text-lg font-black tracking-tight mt-1">Create Teacher / Faculty Account</h3>
      </div>
      <button type="button" onclick="closeManualTeacherModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Form -->
    <form id="manual-teacher-form" onsubmit="handleManualTeacherSubmit(event)" class="p-6 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Employee ID -->
        <div>
          <label for="t-emp-id" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Employee ID / Number <span class="text-rose-500">*</span>
          </label>
          <input type="text" id="t-emp-id" required placeholder="e.g. EMP-2026-0099" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-mono font-bold text-slate-800 outline-none">
        </div>

        <!-- Full Name -->
        <div>
          <label for="t-name" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Faculty Full Name &amp; Title <span class="text-rose-500">*</span>
          </label>
          <input type="text" id="t-name" required placeholder="e.g. Prof. Ricardo V. Gomez, MSIT" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-purple-500 focus:border-purple-500 font-semibold text-slate-800 outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <!-- Department -->
        <div>
          <label for="t-dept" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Academic Department <span class="text-rose-500">*</span>
          </label>
          <select id="t-dept" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-purple-500 font-semibold text-slate-800 outline-none">
            <option value="Computer Studies">College of Computer Studies</option>
            <option value="Business Administration">College of Business Administration</option>
            <option value="General Education">General Education Department</option>
          </select>
        </div>

        <!-- Email -->
        <div>
          <label for="t-email" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Official Faculty Email <span class="text-rose-500">*</span>
          </label>
          <input type="email" id="t-email" required placeholder="r.gomez@bestlink.edu.ph" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-purple-500 font-medium text-slate-800 outline-none">
        </div>
      </div>

      <!-- Initial Assigned Subject -->
      <div>
        <label for="t-subjects" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
          Assigned Course / Subjects &amp; Sections
        </label>
        <input type="text" id="t-subjects" placeholder="e.g. IT301 (BSIT 3-A), IT302 (BSIT 3-B)" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-purple-500 font-medium text-slate-800 outline-none">
      </div>

      <!-- Default Password Info -->
      <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
        <div>
          <div class="font-bold text-slate-800">Faculty Initial Password</div>
          <div class="text-[11px] text-slate-500">Auto-generated credential sent via institutional email.</div>
        </div>
        <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-mono font-bold text-purple-700 text-xs shadow-2xs">BCP@Faculty2026</span>
      </div>

      <!-- Action Footer -->
      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closeManualTeacherModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
          Cancel
        </button>
        <button type="submit" class="px-6 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold shadow-md shadow-purple-600/20 transition flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Save &amp; Create Faculty Account</span>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: IMPORT FACULTY EXCEL / CSV FILE -->
<!-- ========================================================================= -->
<div id="teacherExcelModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-3xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <!-- Header -->
    <div class="px-6 py-5 bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-800 text-white flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Faculty Batch Importer</span>
          <span class="text-xs text-emerald-200 font-medium">Spreadsheet Ingestion</span>
        </div>
        <h3 class="text-lg font-black tracking-tight mt-1">Import Teacher Accounts via Excel / CSV</h3>
      </div>
      <button type="button" onclick="closeTeacherExcelModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
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
          <div class="font-bold mb-0.5">Required Faculty Spreadsheet Format (.xlsx, .xls, .csv):</div>
          <p class="text-emerald-800 text-[11.5px] leading-relaxed">
            Ensure your spreadsheet contains columns: <strong class="font-mono text-emerald-950">employee_number</strong>, <strong class="font-mono text-emerald-950">faculty_name</strong>, <strong class="font-mono text-emerald-950">department</strong>, and <strong class="font-mono text-emerald-950">email</strong>.
          </p>
        </div>
      </div>

      <!-- Drag and Drop Dropzone -->
      <div id="teacher-excel-dropzone" class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-7 text-center transition cursor-pointer bg-slate-50/70 hover:bg-emerald-50/30 flex flex-col items-center justify-center gap-2 group" onclick="triggerTeacherFileInput()">
        <input type="file" id="teacher-excel-file-input" accept=".xlsx,.xls,.csv" class="hidden" onchange="handleTeacherFileSelected(event)">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition shadow-xs">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
        </div>
        <div class="text-xs font-bold text-slate-800">
          Click to choose Faculty Excel spreadsheet or drag and drop here
        </div>
        <div class="text-[11px] text-slate-400">Supported formats: .XLSX, .XLS, .CSV up to 25MB</div>
        <div id="teacher-selected-file-pill" class="hidden mt-2 px-3 py-1 rounded-lg bg-emerald-100 border border-emerald-200 text-emerald-800 font-mono text-xs font-bold flex items-center gap-2">
          <span>📄</span>
          <span id="teacher-selected-file-name">official_faculty_2026.xlsx</span>
        </div>
      </div>

      <!-- Live Parsed Excel Preview Section (Appears after file selected) -->
      <div id="teacher-preview-box" class="hidden space-y-3">
        <div class="flex items-center justify-between">
          <div class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-2">
            <span>Parsed Faculty Records Preview</span>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">2 Ready to Import</span>
          </div>
          <span class="text-[11px] text-slate-400 font-medium">Valid headers detected</span>
        </div>

        <div class="max-h-48 overflow-y-auto rounded-xl border border-slate-200">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] border-b border-slate-200">
              <tr>
                <th class="py-2 px-3">Employee Number</th>
                <th class="py-2 px-3">Faculty Name</th>
                <th class="py-2 px-3">Department</th>
                <th class="py-2 px-3">Email</th>
                <th class="py-2 px-3 text-right">Status</th>
              </tr>
            </thead>
            <tbody id="teacher-preview-tbody" class="divide-y divide-slate-100">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
        <button type="button" onclick="downloadTeacherExcelTemplate()" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          <span>Download Faculty Template (.xlsx)</span>
        </button>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="closeTeacherExcelModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
            Close
          </button>
          <button type="button" onclick="processTeacherExcelImport()" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Create Faculty Accounts from Excel</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Filter live
function filterTeachers() {
  const dept = document.getElementById('filter-dept').value;
  const status = document.getElementById('filter-status').value;
  const query = document.getElementById('search-teacher').value.toLowerCase().trim();

  const rows = document.querySelectorAll('.teacher-row');
  let visible = 0;

  rows.forEach(row => {
    const rowDept = row.getAttribute('data-dept');
    const rowStatus = row.getAttribute('data-status');
    const rowText = (row.getAttribute('data-text') || '').toLowerCase();

    const matchDept = (dept === 'all' || rowDept.includes(dept));
    const matchStatus = (status === 'all' || rowStatus === status);
    const matchQuery = (!query || rowText.includes(query));

    if (matchDept && matchStatus && matchQuery) {
      row.style.display = '';
      visible++;
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('visible-teacher-count').textContent = visible;
}

// Manual Faculty Modal
function openManualTeacherModal() {
  document.getElementById('manualTeacherModal').classList.remove('hidden');
}

function closeManualTeacherModal() {
  document.getElementById('manualTeacherModal').classList.add('hidden');
}

function handleManualTeacherSubmit(e) {
  e.preventDefault();
  const id = document.getElementById('t-emp-id').value.trim();
  const name = document.getElementById('t-name').value.trim();
  const dept = document.getElementById('t-dept').value;
  const email = document.getElementById('t-email').value.trim();
  const subjects = document.getElementById('t-subjects').value.trim() || 'General Faculty Assignment';

  if (!id || !name || !email) {
    APP.toast('Please fill out all required fields.', 'error');
    return;
  }

  // Get Initials
  const initials = name.replace('Prof. ', '').replace('Dr. ', '').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();

  const tbody = document.getElementById('teachers-table-body');
  const tr = document.createElement('tr');
  tr.className = 'teacher-row hover:bg-slate-50/80 transition bg-purple-50/30';
  tr.setAttribute('data-dept', dept);
  tr.setAttribute('data-status', 'Active');
  tr.setAttribute('data-text', `${id} ${name} ${email} ${dept} ${subjects}`);

  tr.innerHTML = `
    <td class="py-3.5 px-4 font-mono font-bold text-purple-700">
      <div class="flex items-center gap-1.5">
        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
        <span>${id}</span>
      </div>
    </td>
    <td class="py-3.5 px-4">
      <div class="flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-xl bg-purple-600 text-white font-bold text-xs flex items-center justify-center shrink-0">${initials}</div>
        <div>
          <div class="font-bold text-slate-900">${name}</div>
          <div class="text-[10px] text-emerald-600 font-bold">● Just Created (Manual)</div>
        </div>
      </div>
    </td>
    <td class="py-3.5 px-4 font-semibold text-slate-800">${dept}</td>
    <td class="py-3.5 px-4">
      <div class="flex flex-wrap gap-1.5">
        <span class="px-2 py-0.5 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 text-[10.5px] font-bold">${subjects}</span>
      </div>
    </td>
    <td class="py-3.5 px-4 font-bold text-slate-900">35 Students</td>
    <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Active Faculty</span></td>
    <td class="py-3.5 px-4 text-right">
      <div class="flex items-center justify-end gap-2">
        <button type="button" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition" onclick="APP.toast('Opening assignment manager for ${name}', 'info')">Manage</button>
        <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 transition" onclick="APP.toast('Reset password link sent to instructor email', 'success')" title="Reset Password">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        </button>
      </div>
    </td>
  `;

  tbody.insertBefore(tr, tbody.firstChild);

  // Update counter
  const totalElem = document.getElementById('stat-total-faculty');
  if (totalElem) {
    const curr = parseInt(totalElem.textContent.replace(/,/g, '')) || 48;
    totalElem.textContent = (curr + 1).toLocaleString();
  }

  filterTeachers();
  closeManualTeacherModal();
  document.getElementById('manual-teacher-form').reset();
  APP.toast(`Faculty account for ${name} (${id}) created successfully!`, 'success');
}

// Teacher Excel Modal
function openTeacherExcelModal() {
  document.getElementById('teacherExcelModal').classList.remove('hidden');
}

function closeTeacherExcelModal() {
  document.getElementById('teacherExcelModal').classList.add('hidden');
}

function triggerTeacherFileInput() {
  document.getElementById('teacher-excel-file-input').click();
}

function handleTeacherFileSelected(event) {
  const file = event.target.files[0];
  if (!file) return;

  document.getElementById('teacher-selected-file-name').textContent = file.name;
  document.getElementById('teacher-selected-file-pill').classList.remove('hidden');

  const tbody = document.getElementById('teacher-preview-tbody');
  tbody.innerHTML = `
    <tr class="hover:bg-slate-50">
      <td class="py-2 px-3 font-mono font-bold text-purple-700">EMP-2026-0088</td>
      <td class="py-2 px-3 font-bold text-slate-800">Prof. Elena D. Bautista</td>
      <td class="py-2 px-3">College of Computer Studies</td>
      <td class="py-2 px-3">e.bautista@bestlink.edu.ph</td>
      <td class="py-2 px-3 text-right"><span class="text-emerald-600 font-bold text-[10px]"> Valid Row</span></td>
    </tr>
    <tr class="hover:bg-slate-50">
      <td class="py-2 px-3 font-mono font-bold text-purple-700">EMP-2026-0089</td>
      <td class="py-2 px-3 font-bold text-slate-800">Prof. Nelson K. Cruz</td>
      <td class="py-2 px-3">College of Business Admin</td>
      <td class="py-2 px-3">n.cruz@bestlink.edu.ph</td>
      <td class="py-2 px-3 text-right"><span class="text-emerald-600 font-bold text-[10px]"> Valid Row</span></td>
    </tr>
  `;

  document.getElementById('teacher-preview-box').classList.remove('hidden');
  APP.toast('Spreadsheet parsed: 2 valid faculty records identified.', 'info');
}

function downloadTeacherExcelTemplate() {
  APP.toast('Faculty_Master_Template.xlsx downloaded to your browser.', 'success');
}

function processTeacherExcelImport() {
  const filePill = document.getElementById('teacher-selected-file-pill');
  if (filePill.classList.contains('hidden')) {
    APP.toast('Please select or drop a Faculty Excel/CSV file first.', 'warning');
    return;
  }

  const sampleImported = [
    { id: 'EMP-2026-0088', name: 'Prof. Elena D. Bautista', email: 'e.bautista@bestlink.edu.ph', dept: 'Computer Studies', subjects: 'CS101 (BSCS 1-A)', initials: 'EB', bg: 'bg-teal-600' },
    { id: 'EMP-2026-0089', name: 'Prof. Nelson K. Cruz', email: 'n.cruz@bestlink.edu.ph', dept: 'Business Administration', subjects: 'BA201 (BSBA 2-A)', initials: 'NC', bg: 'bg-indigo-600' }
  ];

  const tbody = document.getElementById('teachers-table-body');
  sampleImported.forEach(tc => {
    const tr = document.createElement('tr');
    tr.className = 'teacher-row hover:bg-slate-50/80 transition bg-emerald-50/30';
    tr.setAttribute('data-dept', tc.dept);
    tr.setAttribute('data-status', 'Active');
    tr.setAttribute('data-text', `${tc.id} ${tc.name} ${tc.email} ${tc.dept} ${tc.subjects}`);

    tr.innerHTML = `
      <td class="py-3.5 px-4 font-mono font-bold text-purple-700">
        <div class="flex items-center gap-1.5">
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
          <span>${tc.id}</span>
        </div>
      </td>
      <td class="py-3.5 px-4">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-xl ${tc.bg} text-white font-bold text-xs flex items-center justify-center shrink-0">${tc.initials}</div>
          <div>
            <div class="font-bold text-slate-900">${tc.name}</div>
            <div class="text-[10px] text-emerald-600 font-bold">● Imported via Excel</div>
          </div>
        </div>
      </td>
      <td class="py-3.5 px-4 font-semibold text-slate-800">${tc.dept}</td>
      <td class="py-3.5 px-4">
        <div class="flex flex-wrap gap-1.5">
          <span class="px-2 py-0.5 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 text-[10.5px] font-bold">${tc.subjects}</span>
        </div>
      </td>
      <td class="py-3.5 px-4 font-bold text-slate-900">42 Students</td>
      <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Active Faculty</span></td>
      <td class="py-3.5 px-4 text-right">
        <div class="flex items-center justify-end gap-2">
          <button type="button" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold text-[11px] transition" onclick="APP.toast('Opening assignment manager for ${tc.name}', 'info')">Manage</button>
          <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-rose-600 transition" onclick="APP.toast('Reset password link sent to instructor email', 'success')" title="Reset Password">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
          </button>
        </div>
      </td>
    `;
    tbody.insertBefore(tr, tbody.firstChild);
  });

  const totalElem = document.getElementById('stat-total-faculty');
  if (totalElem) {
    const curr = parseInt(totalElem.textContent.replace(/,/g, '')) || 48;
    totalElem.textContent = (curr + sampleImported.length).toLocaleString();
  }

  filterTeachers();
  closeTeacherExcelModal();
  APP.toast(`Successfully imported and provisioned ${sampleImported.length} faculty accounts from Excel!`, 'success');
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
