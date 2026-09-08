<?php
$page_title = 'Teacher Dashboard';
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
            <span class="badge badge-present">Faculty Portal</span>
            <span class="text-xs text-slate-500">1st Semester 2025–2026</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">Welcome back, Prof. Ramirez!</h1>
          <p class="text-sm text-slate-500">College of Computer Studies • Information Technology Department</p>
        </div>

        <div class="flex items-center gap-3">
          <a href="<?php echo url('teacher/import-roster'); ?>" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Import Class Roster</span>
          </a>
          <a href="<?php echo url('teacher/live-session'); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-md hover:shadow-lg transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Start Attendance</span>
          </a>
        </div>
      </div>

      <!-- Quick Metrics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned Classes</span>
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-slate-800 mt-2">4 Classes</div>
          <p class="text-xs text-slate-500 mt-1">3 Sections • 148 Total Students</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Attendance</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-emerald-600 mt-2">94.8%</div>
          <p class="text-xs text-slate-500 mt-1">140 Present • 5 Late • 3 Absent</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Sessions</span>
            <span class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
              <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
            </span>
          </div>
          <div class="text-2xl font-bold text-slate-800 mt-2">1 Active</div>
          <p class="text-xs text-amber-600 font-medium mt-1">Web Dev 2 (BSIT 3-A)</p>
        </div>

        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">At-Risk Alerts</span>
            <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-rose-600 mt-2">2 Students</div>
          <p class="text-xs text-slate-500 mt-1">> 3 consecutive absences</p>
        </div>
      </div>

      <!-- Main Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Today's Schedule & Classes (Left 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Today's Schedule -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h2 class="font-bold text-slate-800 text-base">Today's Class Schedule</h2>
                <p class="text-xs text-slate-400">Tuesday, September 8, 2026</p>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">3 Sessions Scheduled</span>
            </div>

            <div class="divide-y divide-slate-100">
              <!-- Item 1: Active now -->
              <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-emerald-50/40 border-l-4 border-emerald-500">
                <div class="flex items-start gap-4">
                  <div class="text-center p-2 rounded-lg bg-emerald-100 text-emerald-800 min-w-[70px]">
                    <div class="text-xs font-bold uppercase">08:00 AM</div>
                    <div class="text-[10px]">10:00 AM</div>
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="font-bold text-slate-800">IT301 — Web Development 2</h3>
                      <span class="badge badge-present">● Session Active</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Section: <span class="font-semibold text-slate-700">BSIT 3-A</span> • Lab 304 • 42 Enrolled</p>
                    <div class="text-xs text-emerald-700 font-medium mt-1">36 / 42 scanned (85.7%)</div>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <a href="<?php echo url('teacher/live-session'); ?>" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold shadow-sm transition">
                    Open Live Screen
                  </a>
                  <a href="<?php echo url('teacher/roster?class_id=1'); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition">
                    Roster
                  </a>
                </div>
              </div>

              <!-- Item 2: Upcoming -->
              <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition">
                <div class="flex items-start gap-4">
                  <div class="text-center p-2 rounded-lg bg-slate-100 text-slate-700 min-w-[70px]">
                    <div class="text-xs font-bold uppercase">10:30 AM</div>
                    <div class="text-[10px]">12:30 PM</div>
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="font-bold text-slate-800">IT302 — Database Systems 2</h3>
                      <span class="badge badge-pending">Upcoming</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Section: <span class="font-semibold text-slate-700">BSIT 3-B</span> • Room 402 • 38 Enrolled</p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <a href="<?php echo url('teacher/live-session?class_id=2'); ?>" class="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-xs font-semibold shadow-sm transition">
                    Start Attendance
                  </a>
                  <a href="<?php echo url('teacher/roster?class_id=2'); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition">
                    Roster
                  </a>
                </div>
              </div>

              <!-- Item 3: Afternoon -->
              <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50 transition">
                <div class="flex items-start gap-4">
                  <div class="text-center p-2 rounded-lg bg-slate-100 text-slate-700 min-w-[70px]">
                    <div class="text-xs font-bold uppercase">01:30 PM</div>
                    <div class="text-[10px]">03:30 PM</div>
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <h3 class="font-bold text-slate-800">IT303 — Systems Integration</h3>
                      <span class="badge badge-pending">Upcoming</span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">Section: <span class="font-semibold text-slate-700">BSIT 3-C</span> • Lab 301 • 36 Enrolled</p>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <a href="<?php echo url('teacher/live-session?class_id=3'); ?>" class="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-xs font-semibold shadow-sm transition">
                    Start Attendance
                  </a>
                  <a href="<?php echo url('teacher/roster?class_id=3'); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition">
                    Roster
                  </a>
                </div>
              </div>
            </div>
          </div>

          <!-- My Assigned Classes Overview -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-bold text-slate-800 text-base">My Classes Overview</h2>
              <a href="<?php echo url('teacher/classes'); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700">View All Classes →</a>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="p-4 rounded-xl border border-slate-200 hover:border-blue-400 transition bg-gradient-to-br from-white to-slate-50">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800">BSIT 3-A</span>
                  <span class="text-xs text-emerald-600 font-bold">96.2% avg</span>
                </div>
                <h4 class="font-bold text-slate-800 text-sm">IT301 — Web Development 2</h4>
                <p class="text-xs text-slate-500 mt-1">42 Enrolled • 14 Sessions Held</p>
                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100">
                  <a href="<?php echo url('teacher/roster?class_id=1'); ?>" class="text-xs text-blue-600 hover:underline font-medium">View Roster</a>
                  <span class="text-slate-300">•</span>
                  <a href="<?php echo url('teacher/attendance-history?class_id=1'); ?>" class="text-xs text-slate-600 hover:underline">History</a>
                </div>
              </div>

              <div class="p-4 rounded-xl border border-slate-200 hover:border-blue-400 transition bg-gradient-to-br from-white to-slate-50">
                <div class="flex items-center justify-between mb-2">
                  <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-purple-100 text-purple-800">BSIT 3-B</span>
                  <span class="text-xs text-emerald-600 font-bold">93.5% avg</span>
                </div>
                <h4 class="font-bold text-slate-800 text-sm">IT302 — Database Systems 2</h4>
                <p class="text-xs text-slate-500 mt-1">38 Enrolled • 14 Sessions Held</p>
                <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100">
                  <a href="<?php echo url('teacher/roster?class_id=2'); ?>" class="text-xs text-blue-600 hover:underline font-medium">View Roster</a>
                  <span class="text-slate-300">•</span>
                  <a href="<?php echo url('teacher/attendance-history?class_id=2'); ?>" class="text-xs text-slate-600 hover:underline">History</a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Side: Live Feed & Quick Actions -->
        <div class="space-y-6">
          <!-- Live Check-in Activity Stream -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <h3 class="font-bold text-slate-800 text-sm">Live Attendance Feed</h3>
              </div>
              <span class="text-[11px] text-slate-400">Auto-updating</span>
            </div>

            <div class="space-y-3">
              <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold shrink-0">JD</div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs font-bold text-slate-800 truncate">Juan Dela Cruz</div>
                  <div class="text-[11px] text-slate-500">2026-00123 • BSIT 3-A</div>
                </div>
                <div class="text-right">
                  <span class="badge badge-present text-[10px]">Present</span>
                  <div class="text-[10px] text-slate-400 mt-0.5">08:02 AM</div>
                </div>
              </div>

              <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                <div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold shrink-0">MS</div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs font-bold text-slate-800 truncate">Maria Santos</div>
                  <div class="text-[11px] text-slate-500">2026-00124 • BSIT 3-A</div>
                </div>
                <div class="text-right">
                  <span class="badge badge-present text-[10px]">Present</span>
                  <div class="text-[10px] text-slate-400 mt-0.5">08:04 AM</div>
                </div>
              </div>

              <div class="flex items-center gap-3 p-2.5 rounded-lg bg-amber-50/50 border border-amber-100">
                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold shrink-0">PR</div>
                <div class="flex-1 min-w-0">
                  <div class="text-xs font-bold text-slate-800 truncate">Pedro Reyes</div>
                  <div class="text-[11px] text-slate-500">2026-00125 • BSIT 3-A</div>
                </div>
                <div class="text-right">
                  <span class="badge badge-tardy text-[10px]">Late (+16m)</span>
                  <div class="text-[10px] text-slate-400 mt-0.5">08:16 AM</div>
                </div>
              </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 text-center">
              <a href="<?php echo url('teacher/live-session'); ?>" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Open Full Live Monitor →</a>
            </div>
          </div>

          <!-- Roster Import CTA Box -->
          <div class="bg-gradient-to-br from-blue-900 to-indigo-950 rounded-xl p-5 text-white shadow-md">
            <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center mb-3">
              <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="font-bold text-base mb-1">3-Step Excel Roster Import</h3>
            <p class="text-xs text-blue-200 leading-relaxed mb-4">Upload class rosters directly. The system automatically validates student IDs against the official Master record.</p>
            <a href="<?php echo url('teacher/import-roster'); ?>" class="block w-full text-center py-2 px-3 rounded-lg bg-white text-blue-950 font-bold text-xs hover:bg-blue-50 transition shadow">
              Launch Import Wizard
            </a>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
