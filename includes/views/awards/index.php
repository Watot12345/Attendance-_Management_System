<?php
$page_title = 'Perfect Attendance Award Tool';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Teacher Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Recognition &amp; Honors</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Perfect Attendance Award Tool</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Evaluate your assigned class sections to identify, award, and generate printable certificates for students with 100% attendance records.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="generateBatchCertificates()" class="px-4 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold shadow-md shadow-amber-600/20 transition flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Batch Export Certificates (PDF)</span>
          </button>
        </div>
      </div>

      <!-- Criteria Card -->
      <div class="bg-white rounded-3xl p-6 border border-slate-200/80 shadow-xs mb-6">
        <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4 flex items-center gap-2">
          <span>Award Criteria &amp; Target Section</span>
          <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-800 font-bold text-[10px] border border-amber-200">0 Absences · 0 Tardies</span>
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
          <!-- Class Section Selector -->
          <div>
            <label for="award-section" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Target Assigned Class</label>
            <select id="award-section" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-800 outline-none">
              <option value="BSIT-3A">IT301 - BSIT 3-A (42 Students)</option>
              <option value="BSIT-3B">IT302 - BSIT 3-B (38 Students)</option>
              <option value="BSIT-3C">IT303 - BSIT 3-C (36 Students)</option>
              <option value="ALL">All My Assigned Classes (116 Students)</option>
            </select>
          </div>

          <!-- Evaluation Period -->
          <div>
            <label for="award-period" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Evaluation Period</label>
            <select id="award-period" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-800 outline-none">
              <option value="month">Monthly Honors (September 2026)</option>
              <option value="midterm">Midterm Grading Period (AY 2025–2026)</option>
              <option value="sem">Full 1st Semester (AY 2025–2026)</option>
            </select>
          </div>

          <!-- Strictness -->
          <div>
            <label for="award-strictness" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Threshold Standard</label>
            <select id="award-strictness" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-emerald-500 font-semibold text-slate-800 outline-none">
              <option value="100">100% Flawless Attendance (0 Absences, 0 Lates)</option>
              <option value="98">98%+ High Honors (Max 1 Excused Slip)</option>
            </select>
          </div>
        </div>

        <div class="flex items-center justify-end">
          <button type="button" onclick="calculateAwards()" class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span>Scan &amp; Calculate Eligible Awardees</span>
          </button>
        </div>
      </div>

      <!-- Eligible Awardees Table -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-800">Eligible Perfect Attendance Candidates</h3>
            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">5 Students Qualified</span>
          </div>
          <span class="text-[11px] text-slate-400 font-medium">BSIT 3-A · September 2026 Evaluation</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4">Rank &amp; Student ID</th>
                <th class="py-3.5 px-4">Student Name</th>
                <th class="py-3.5 px-4">Class Section</th>
                <th class="py-3.5 px-4 text-center">Total Sessions</th>
                <th class="py-3.5 px-4 text-center">Present Rate</th>
                <th class="py-3.5 px-4 text-center">Absences / Tardies</th>
                <th class="py-3.5 px-4 text-right">Award Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr class="hover:bg-amber-50/30 transition">
                <td class="py-3.5 px-4 font-mono font-bold text-amber-700">
                  <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[10px] mr-1.5">🥇 #1</span>
                  2026-00124
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900">Maria Santos</div>
                  <div class="text-[10px] text-slate-400">maria.santos@bestlink.edu.ph</div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">BSIT 3-A</td>
                <td class="py-3.5 px-4 text-center font-bold text-slate-800">20 / 20</td>
                <td class="py-3.5 px-4 text-center"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">100.0%</span></td>
                <td class="py-3.5 px-4 text-center text-slate-500 font-semibold">0 / 0</td>
                <td class="py-3.5 px-4 text-right">
                  <button type="button" onclick="APP.toast('Certificate generated for Maria Santos', 'success')" class="px-3 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] transition shadow-xs">
                    Generate Certificate
                  </button>
                </td>
              </tr>

              <tr class="hover:bg-amber-50/30 transition">
                <td class="py-3.5 px-4 font-mono font-bold text-amber-700">
                  <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[10px] mr-1.5">🥈 #2</span>
                  2026-00126
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900">Ana Lim</div>
                  <div class="text-[10px] text-slate-400">ana.lim@bestlink.edu.ph</div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">BSIT 3-A</td>
                <td class="py-3.5 px-4 text-center font-bold text-slate-800">20 / 20</td>
                <td class="py-3.5 px-4 text-center"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">100.0%</span></td>
                <td class="py-3.5 px-4 text-center text-slate-500 font-semibold">0 / 0</td>
                <td class="py-3.5 px-4 text-right">
                  <button type="button" onclick="APP.toast('Certificate generated for Ana Lim', 'success')" class="px-3 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] transition shadow-xs">
                    Generate Certificate
                  </button>
                </td>
              </tr>

              <tr class="hover:bg-amber-50/30 transition">
                <td class="py-3.5 px-4 font-mono font-bold text-amber-700">
                  <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-800 text-[10px] mr-1.5">🥉 #3</span>
                  2026-00150
                </td>
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900">Jerome A. Valdez</div>
                  <div class="text-[10px] text-slate-400">jerome.valdez@bestlink.edu.ph</div>
                </td>
                <td class="py-3.5 px-4 font-semibold text-slate-800">BSIT 3-A</td>
                <td class="py-3.5 px-4 text-center font-bold text-slate-800">20 / 20</td>
                <td class="py-3.5 px-4 text-center"><span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-100 text-emerald-800">100.0%</span></td>
                <td class="py-3.5 px-4 text-center text-slate-500 font-semibold">0 / 0</td>
                <td class="py-3.5 px-4 text-right">
                  <button type="button" onclick="APP.toast('Certificate generated for Jerome Valdez', 'success')" class="px-3 py-1 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-bold text-[11px] transition shadow-xs">
                    Generate Certificate
                  </button>
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
function calculateAwards() {
  APP.toast('Scanning attendance logs: 5 eligible perfect attendance students identified!', 'success');
}

function generateBatchCertificates() {
  APP.toast('Exporting 5 Perfect Attendance Certificates (.PDF)...', 'success');
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
