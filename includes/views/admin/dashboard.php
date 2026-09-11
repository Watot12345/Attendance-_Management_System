<?php
$page_title = 'Institutional Admin Dashboard';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Welcome Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="badge badge-present">Administrator Portal</span>
            <span class="text-xs text-slate-500">Academic Year 2025–2026</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">Bestlink College Attendance Administration</h1>
          <p class="text-sm text-slate-500">Master record governance, real-time campus attendance rates, and compliance oversight.</p>
        </div>

        <div class="flex items-center gap-3">
          <a href="<?php echo url('admin/import-students'); ?>" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Import Student Master</span>
          </a>
          <a href="<?php echo url('admin/reports'); ?>" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Generate Campus Report</span>
          </a>
        </div>
      </div>

      <!-- Institutional Metrics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Student Master</span>
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-slate-800 mt-2">1,248</div>
          <p class="text-xs text-slate-500 mt-1">1,230 Active • 18 Inactive</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Faculty Master</span>
            <span class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-slate-800 mt-2">64 Teachers</div>
          <p class="text-xs text-slate-500 mt-1">4 Academic Departments</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Sections</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-slate-800 mt-2">32 Sections</div>
          <p class="text-xs text-slate-500 mt-1">118 Class Offerings</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Campus Rate</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-emerald-600 mt-2">95.4%</div>
          <p class="text-xs text-slate-500 mt-1">+1.2% from last week</p>
        </div>
      </div>

      <!-- Two Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Master Records Governance -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Quick Management Shortcuts -->
          <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
            <h2 class="font-bold text-slate-800 text-base mb-4">Official Master Records Governance</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <a href="<?php echo url('admin/students'); ?>" class="p-4 rounded-xl border border-slate-200 hover:border-blue-400 hover:bg-blue-50/40 transition group">
                <div class="w-9 h-9 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1">Student Master</h3>
                <p class="text-xs text-slate-500">Manage official student IDs, names, and academic statuses.</p>
              </a>

              <a href="<?php echo url('admin/teachers'); ?>" class="p-4 rounded-xl border border-slate-200 hover:border-purple-400 hover:bg-purple-50/40 transition group">
                <div class="w-9 h-9 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center mb-3 group-hover:scale-105 transition">
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </div>
                <h3 class="font-bold text-slate-800 text-sm mb-1">Teacher Master</h3>
                <p class="text-xs text-slate-500">Faculty directory, employee codes, and department assignments.</p>
              </a>
            </div>
          </div>

          <!-- Department Attendance Breakdown -->
          <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-bold text-slate-800 text-base">Department Attendance Performance</h2>
              <a href="<?php echo url('admin/reports'); ?>" class="text-xs font-semibold text-blue-600 hover:underline">View Comprehensive Breakdown →</a>
            </div>

            <div class="space-y-4">
              <div>
                <div class="flex justify-between text-xs font-semibold mb-1">
                  <span class="text-slate-700">College of Computer Studies (BSIT, BSCS)</span>
                  <span class="text-emerald-600">96.4% (420 / 436 Present)</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                  <div class="bg-emerald-500 h-2.5 rounded-full" style="width: 96.4%"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-xs font-semibold mb-1">
                  <span class="text-slate-700">College of Business Administration (BSBA)</span>
                  <span class="text-emerald-600">94.8% (380 / 401 Present)</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                  <div class="bg-emerald-500 h-2.5 rounded-full" style="width: 94.8%"></div>
                </div>
              </div>

              <div>
                <div class="flex justify-between text-xs font-semibold mb-1">
                  <span class="text-slate-700">College of Hospitality Management (BSHM)</span>
                  <span class="text-emerald-600">93.2% (280 / 300 Present)</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                  <div class="bg-emerald-500 h-2.5 rounded-full" style="width: 93.2%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Col: Bulk Import & System Rules -->
        <div class="space-y-6">
          <!-- Bulk Import Callout -->
          <div class="bg-gradient-to-br from-slate-900 to-blue-950 rounded-xl p-6 text-white shadow-md">
            <div class="w-10 h-10 rounded-lg bg-white/10 flex items-center justify-center mb-3">
              <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </div>
            <h3 class="font-bold text-base mb-1">Bulk Master Excel Import</h3>
            <p class="text-xs text-blue-200 leading-relaxed mb-4">
              Import student master records in bulk before the semester starts. Class teachers will validate enrollments against this official master.
            </p>
            <a href="<?php echo url('admin/import-students'); ?>" class="block w-full text-center py-2.5 px-3 rounded-lg bg-blue-600 hover:bg-blue-500 text-white font-bold text-xs shadow transition">
              Upload Master Spreadsheet
            </a>
          </div>

          <!-- System Integrity Guardrails -->
          <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <h3 class="font-bold text-slate-800 text-sm mb-3">System Architecture Guardrails</h3>
            <ul class="text-xs text-slate-600 space-y-2.5">
              <li class="flex items-start gap-2">
                <span class="text-emerald-600 font-bold"></span>
                <span><strong>Role Isolation:</strong> Only Admin can create student accounts; teachers only manage class enrollments.</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-emerald-600 font-bold"></span>
                <span><strong>Anti-Screenshot QR:</strong> 15-second rotation window on live teacher session projectors.</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-emerald-600 font-bold"></span>
                <span><strong>Audit Trail:</strong> Manual attendance adjustments require justification and author logging.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
