<?php
$page_title = 'Tardy & Absence Logs';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Breadcrumb & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Teacher Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Attendance Exceptions Log</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Tardy &amp; Absence Logs</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Detailed log of student tardiness, consecutive absences, automated alert dispatch states, and excuse slip linkages.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="APP.toast('Exporting tardy & absence records (.csv)...', 'info')" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Export Log (CSV)</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0 font-black text-sm">
            ⏱
          </div>
          <div>
            <div class="text-xl font-black text-amber-600">18</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Tardy Logs (This Month)</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0 font-black text-sm">
            ✕
          </div>
          <div>
            <div class="text-xl font-black text-rose-600">12</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Unexcused Absences</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0 font-black text-sm">
            ✉
          </div>
          <div>
            <div class="text-xl font-black text-blue-600">8</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Excused Slips Approved</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 font-black text-sm">
            📲
          </div>
          <div>
            <div class="text-xl font-black text-emerald-600">28 / 30</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Parent Alerts Sent</div>
          </div>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <!-- Class section filter -->
          <select id="filter-class" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 font-semibold text-slate-700 transition" onchange="filterExceptionLogs()">
            <option value="all">All Assigned Classes</option>
            <option value="BSIT 3-A">IT301 - BSIT 3-A</option>
            <option value="BSIT 3-B">IT302 - BSIT 3-B</option>
            <option value="BSIT 3-C">IT303 - BSIT 3-C</option>
          </select>

          <!-- Type filter -->
          <select id="filter-type" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-emerald-500 font-semibold text-slate-700 transition" onchange="filterExceptionLogs()">
            <option value="all">All Exception Types</option>
            <option value="Tardy">Tardy / Late Only</option>
            <option value="Absent">Unexcused Absences</option>
            <option value="Excused">Excused Absence</option>
          </select>

          <!-- Search box -->
          <div class="relative w-full sm:w-64">
            <input type="text" id="search-log" placeholder="Search student name or ID..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-emerald-500 text-slate-800 transition" oninput="filterExceptionLogs()">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
        </div>

        <div class="text-xs text-slate-400 font-medium">
          Showing <span id="visible-log-count" class="font-bold text-slate-800">4</span> Exception Entries
        </div>
      </div>

      <!-- Exception Logs Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/90 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4">Student &amp; ID</th>
                <th class="py-3.5 px-4">Class Section</th>
                <th class="py-3.5 px-4">Date &amp; Time Logged</th>
                <th class="py-3.5 px-4">Type &amp; Severity</th>
                <th class="py-3.5 px-4">Parent Alert Status</th>
                <th class="py-3.5 px-4">Excuse Linkage</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="exception-logs-tbody" class="divide-y divide-slate-100 text-slate-700">
              <tr class="log-row hover:bg-slate-50/80 transition" data-class="BSIT 3-A" data-type="Tardy" data-text="2026-00124 Maria Santos BSIT 3-A Tardy">
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-rose-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0">MS</div>
                    <div>
                      <div class="font-bold text-slate-900">Maria Santos</div>
                      <div class="font-mono text-[10px] text-slate-400">2026-00124</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">BSIT 3-A</td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800">Sep 8, 2026</div>
                  <div class="text-[10px] text-slate-400">08:18 AM (18m late)</div>
                </td>
                <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-amber-100 text-amber-800 border border-amber-200">⏱ Tardy (18 min)</span></td>
                <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">✓ SMS Sent (08:20 AM)</span></td>
                <td class="py-3.5 px-4 text-slate-400">—</td>
                <td class="py-3.5 px-4 text-right">
                  <button type="button" class="text-xs text-emerald-600 font-bold hover:underline" onclick="APP.toast('Viewing check-in audit for Maria Santos', 'info')">View Audit</button>
                </td>
              </tr>

              <tr class="log-row hover:bg-slate-50/80 transition" data-class="BSIT 3-A" data-type="Absent" data-text="2026-00125 Pedro Reyes BSIT 3-A Absent">
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-amber-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0">PR</div>
                    <div>
                      <div class="font-bold text-slate-900">Pedro Reyes</div>
                      <div class="font-mono text-[10px] text-slate-400">2026-00125</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">BSIT 3-A</td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800">Sep 7, 2026</div>
                  <div class="text-[10px] text-slate-400">Session 08:00–10:00 AM</div>
                </td>
                <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-100 text-rose-800 border border-rose-200">✕ Unexcused Absent</span></td>
                <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">✓ SMS &amp; Email Sent</span></td>
                <td class="py-3.5 px-4">
                  <a href="<?php echo url('teacher/excuse-slips'); ?>" class="text-xs font-bold text-blue-600 hover:underline">Review Slip #104 →</a>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button type="button" class="text-xs text-emerald-600 font-bold hover:underline" onclick="APP.toast('Viewing absence audit for Pedro Reyes', 'info')">View Audit</button>
                </td>
              </tr>

              <tr class="log-row hover:bg-slate-50/80 transition" data-class="BSIT 3-C" data-type="Tardy" data-text="2026-00128 Carlo Villanueva BSIT 3-C Tardy">
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-indigo-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0">CV</div>
                    <div>
                      <div class="font-bold text-slate-900">Carlo Villanueva</div>
                      <div class="font-mono text-[10px] text-slate-400">2026-00128</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">BSIT 3-C</td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800">Sep 6, 2026</div>
                  <div class="text-[10px] text-slate-400">01:22 PM (22m late)</div>
                </td>
                <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-amber-100 text-amber-800 border border-amber-200">⏱ Tardy (22 min)</span></td>
                <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">✓ SMS Sent (01:25 PM)</span></td>
                <td class="py-3.5 px-4 text-slate-400">—</td>
                <td class="py-3.5 px-4 text-right">
                  <button type="button" class="text-xs text-emerald-600 font-bold hover:underline" onclick="APP.toast('Viewing check-in audit for Carlo Villanueva', 'info')">View Audit</button>
                </td>
              </tr>

              <tr class="log-row hover:bg-slate-50/80 transition" data-class="BSIT 3-B" data-type="Excused" data-text="2026-00130 Sofia Tan BSIT 3-B Excused">
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-purple-600 text-white font-bold text-[10px] flex items-center justify-center shrink-0">ST</div>
                    <div>
                      <div class="font-bold text-slate-900">Sofia Tan</div>
                      <div class="font-mono text-[10px] text-slate-400">2026-00130</div>
                    </div>
                  </div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">BSIT 3-B</td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-800">Sep 5, 2026</div>
                  <div class="text-[10px] text-slate-400">Medical Exemption</div>
                </td>
                <td class="py-3.5 px-4"><span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-blue-100 text-blue-800 border border-blue-200">✉ Excused Slip #101</span></td>
                <td class="py-3.5 px-4"><span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">Exempted by Faculty</span></td>
                <td class="py-3.5 px-4">
                  <span class="text-xs font-bold text-emerald-600">✓ Approved</span>
                </td>
                <td class="py-3.5 px-4 text-right">
                  <button type="button" class="text-xs text-emerald-600 font-bold hover:underline" onclick="APP.toast('Viewing excused slip detail', 'info')">View Audit</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
function filterExceptionLogs() {
  const cls = document.getElementById('filter-class').value;
  const typ = document.getElementById('filter-type').value;
  const q = document.getElementById('search-log').value.toLowerCase().trim();

  const rows = document.querySelectorAll('.log-row');
  let count = 0;

  rows.forEach(row => {
    const rowClass = row.getAttribute('data-class');
    const rowType = row.getAttribute('data-type');
    const rowText = (row.getAttribute('data-text') || '').toLowerCase();

    const matchClass = (cls === 'all' || rowClass === cls);
    const matchType = (typ === 'all' || rowType === typ);
    const matchQuery = (!q || rowText.includes(q));

    if (matchClass && matchType && matchQuery) {
      row.style.display = '';
      count++;
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('visible-log-count').textContent = count;
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
