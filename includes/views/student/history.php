<?php
$page_title = 'My Attendance History';
require_once dirname(__DIR__, 2) . '/core/Router.php';
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
            <span class="text-xs text-slate-500">Juan Dela Cruz • 2026-00123</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">My Attendance History &amp; Records</h1>
          <p class="text-sm text-slate-500">Track all your check-ins, punctuality, excused leaves, and overall course attendance percentages.</p>
        </div>

        <div class="flex items-center gap-3">
          <a href="<?php echo url('student/scanner'); ?>" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Scan Today's QR</span>
          </a>
          <a href="<?php echo url('student/excuse-slips'); ?>" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium transition">
            File Excuse Slip
          </a>
        </div>
      </div>

      <!-- Overall Performance Overview Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-slate-400 font-semibold uppercase">Total Sessions</div>
          <div class="text-2xl font-bold text-slate-800 mt-1">42</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Across 4 Subjects</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-emerald-600 font-semibold uppercase">Present</div>
          <div class="text-2xl font-bold text-emerald-600 mt-1">39</div>
          <div class="text-[11px] text-slate-500 mt-0.5">92.8% on-time rate</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-amber-600 font-semibold uppercase">Late / Tardy</div>
          <div class="text-2xl font-bold text-amber-600 mt-1">2</div>
          <div class="text-[11px] text-slate-500 mt-0.5">&lt; 15 min average</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-rose-600 font-semibold uppercase">Absences</div>
          <div class="text-2xl font-bold text-rose-600 mt-1">1</div>
          <div class="text-[11px] text-slate-500 mt-0.5">1 Excused</div>
        </div>
      </div>

      <!-- Filters & Search -->
      <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          <!-- Subject Filter -->
          <select id="filter-subject" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="all">All Enrolled Subjects</option>
            <option value="IT301">IT301 — Web Development 2</option>
            <option value="IT302">IT302 — Database Systems 2</option>
            <option value="IT303">IT303 — Systems Integration</option>
            <option value="CS401">CS401 — Software Engineering</option>
          </select>

          <!-- Status Filter -->
          <select id="filter-status" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="all">All Statuses</option>
            <option value="present">Present Only</option>
            <option value="late">Late Only</option>
            <option value="absent">Absent Only</option>
            <option value="excused">Excused Only</option>
          </select>
        </div>

        <div class="text-xs text-slate-400">
          Showing <strong class="text-slate-700">10 recent records</strong>
        </div>
      </div>

      <!-- Attendance Records Table -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200">
              <tr>
                <th class="py-3 px-4">Date &amp; Time</th>
                <th class="py-3 px-4">Course / Subject</th>
                <th class="py-3 px-4">Section</th>
                <th class="py-3 px-4">Instructor</th>
                <th class="py-3 px-4">Method</th>
                <th class="py-3 px-4">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4 font-semibold text-slate-800">Sep 08, 2026 • 08:05 AM</td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800">IT301 — Web Development 2</div>
                  <div class="text-[10px] text-slate-400">Lab 304</div>
                </td>
                <td class="py-3 px-4"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-3 px-4">Prof. M. Ramirez</td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center gap-1 text-[11px] text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Dynamic QR
                  </span>
                </td>
                <td class="py-3 px-4"><span class="badge badge-present font-bold">● Present</span></td>
              </tr>

              <tr class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4 font-semibold text-slate-800">Sep 03, 2026 • 08:02 AM</td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800">IT301 — Web Development 2</div>
                  <div class="text-[10px] text-slate-400">Lab 304</div>
                </td>
                <td class="py-3 px-4"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-3 px-4">Prof. M. Ramirez</td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center gap-1 text-[11px] text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Dynamic QR
                  </span>
                </td>
                <td class="py-3 px-4"><span class="badge badge-present font-bold">● Present</span></td>
              </tr>

              <tr class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4 font-semibold text-slate-800">Sep 01, 2026 • 10:48 AM</td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800">IT302 — Database Systems 2</div>
                  <div class="text-[10px] text-slate-400">Room 402</div>
                </td>
                <td class="py-3 px-4"><span class="px-2 py-0.5 rounded bg-purple-100 text-purple-800 font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-3 px-4">Prof. M. Ramirez</td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center gap-1 text-[11px] text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Dynamic QR
                  </span>
                </td>
                <td class="py-3 px-4"><span class="badge badge-tardy font-bold">● Late (+18m)</span></td>
              </tr>

              <tr class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4 font-semibold text-slate-800">Aug 28, 2026 • 08:00 AM</td>
                <td class="py-3 px-4">
                  <div class="font-bold text-slate-800">IT301 — Web Development 2</div>
                  <div class="text-[10px] text-slate-400">Lab 304</div>
                </td>
                <td class="py-3 px-4"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-3 px-4">Prof. M. Ramirez</td>
                <td class="py-3 px-4">
                  <span class="inline-flex items-center gap-1 text-[11px] text-slate-600">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Manual Correction
                  </span>
                </td>
                <td class="py-3 px-4"><span class="badge badge-excused font-bold">● Excused (Slip #204)</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
