<?php
$page_title = 'Institutional Attendance Reports';
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
            <span class="badge badge-present">Administrator Portal</span>
            <span class="text-xs text-slate-500">Institutional Analytics &amp; Compliance</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">Campus Attendance Reports</h1>
          <p class="text-sm text-slate-500">Aggregate attendance rates, section comparisons, at-risk alerts, and CHED compliance exports.</p>
        </div>

        <div class="flex items-center gap-3">
          <button type="button" onclick="window.print();" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print Report</span>
          </button>
          <button type="button" onclick="alert('Exporting Campus Attendance Summary to Excel (.xlsx)...');" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Export to Excel</span>
          </button>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <div>
            <label for="report-dept" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Department</label>
            <select id="report-dept" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option>All Colleges</option>
              <option>College of Computer Studies</option>
              <option>College of Business Administration</option>
            </select>
          </div>

          <div>
            <label for="report-term" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Term</label>
            <select id="report-term" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option>1st Semester AY 2025–2026</option>
              <option>2nd Semester AY 2024–2025</option>
            </select>
          </div>

          <div>
            <label for="report-start" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">From Date</label>
            <input type="date" id="report-start" value="2026-08-01" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>

          <div>
            <label for="report-end" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">To Date</label>
            <input type="date" id="report-end" value="2026-09-08" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
          </div>
        </div>
      </div>

      <!-- Section Performance Table -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
          <h2 class="font-bold text-slate-800 text-sm">Class &amp; Section Attendance Performance</h2>
          <span class="text-xs text-slate-400">AY 2025–2026</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200">
              <tr>
                <th class="py-3 px-4">Section Code</th>
                <th class="py-3 px-4">Course / Program</th>
                <th class="py-3 px-4">Enrolled</th>
                <th class="py-3 px-4">Total Sessions</th>
                <th class="py-3 px-4">Present %</th>
                <th class="py-3 px-4">Tardy %</th>
                <th class="py-3 px-4">Absent %</th>
                <th class="py-3 px-4">Compliance Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4 font-bold text-blue-700">BSIT 3-A</td>
                <td class="py-3 px-4 font-semibold text-slate-800">BS Information Technology</td>
                <td class="py-3 px-4">42</td>
                <td class="py-3 px-4">14</td>
                <td class="py-3 px-4 font-bold text-emerald-600">96.2%</td>
                <td class="py-3 px-4 text-amber-600">2.4%</td>
                <td class="py-3 px-4 text-slate-500">1.4%</td>
                <td class="py-3 px-4"><span class="badge badge-present font-bold">Excellent</span></td>
              </tr>

              <tr class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4 font-bold text-purple-700">BSIT 3-B</td>
                <td class="py-3 px-4 font-semibold text-slate-800">BS Information Technology</td>
                <td class="py-3 px-4">38</td>
                <td class="py-3 px-4">14</td>
                <td class="py-3 px-4 font-bold text-emerald-600">93.5%</td>
                <td class="py-3 px-4 text-amber-600">3.8%</td>
                <td class="py-3 px-4 text-slate-500">2.7%</td>
                <td class="py-3 px-4"><span class="badge badge-present font-bold">Good</span></td>
              </tr>

              <tr class="hover:bg-slate-50/80 transition">
                <td class="py-3 px-4 font-bold text-emerald-700">BSCS 4-A</td>
                <td class="py-3 px-4 font-semibold text-slate-800">BS Computer Science</td>
                <td class="py-3 px-4">32</td>
                <td class="py-3 px-4">14</td>
                <td class="py-3 px-4 font-bold text-emerald-600">97.8%</td>
                <td class="py-3 px-4 text-amber-600">1.2%</td>
                <td class="py-3 px-4 text-slate-500">1.0%</td>
                <td class="py-3 px-4"><span class="badge badge-present font-bold">Excellent</span></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
