<?php
$page_title = 'Import Student Master';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body w-full">
      <!-- Breadcrumb & Header -->
      <div class="mb-6">
        <a href="<?php echo url('admin/students'); ?>" class="text-xs font-semibold text-[#1e3b8a] hover:underline inline-flex items-center gap-1 mb-2">
          ← Back to Student Master Directory
        </a>
        <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Bulk Import Student Master</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">Upload official institutional student enrollments. Teachers validate their class rosters against this master list.</p>
      </div>

      <!-- 3-Step Process Indicator -->
      <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200/80 shadow-xs mb-6 w-full">
        <div class="stepper-flex-between w-full max-w-2xl mx-auto text-xs font-semibold">
          <div class="flex items-center gap-2 text-[#1e3b8a]">
            <span class="w-6 h-6 rounded-full bg-[#1e3b8a] text-white flex items-center justify-center font-bold text-xs shadow-xs">1</span>
            <span class="font-bold">Upload Spreadsheet</span>
          </div>
          <div class="flex-1 h-0.5 bg-slate-200 mx-3"></div>
          <div class="flex items-center gap-2 text-slate-400" id="step-nav-2">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs">2</span>
            <span>Validate &amp; Preview</span>
          </div>
          <div class="flex-1 h-0.5 bg-slate-200 mx-3"></div>
          <div class="flex items-center gap-2 text-slate-400" id="step-nav-3">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs">3</span>
            <span>Commit to Master</span>
          </div>
        </div>
      </div>

      <!-- Step 1: Upload Container -->
      <div id="step-1-container" class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6 w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h2 class="font-bold text-slate-900 text-base mb-2">1. Select Academic Year &amp; Term</h2>
            <div class="space-y-3">
              <div>
                <label for="admin-term" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Academic Year</label>
                <select id="admin-term" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 font-semibold text-slate-800">
                  <option>Academic Year 2025–2026 (1st Semester)</option>
                  <option>Academic Year 2025–2026 (2nd Semester)</option>
                </select>
              </div>

              <div>
                <label for="admin-prog" class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Target Department / Program</label>
                <select id="admin-prog" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs bg-slate-50/60 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 font-semibold text-slate-800">
                  <option value="ALL">All Departments (Auto-detect from file)</option>
                  <option value="CCS">College of Computer Studies</option>
                  <option value="CBA">College of Business Administration</option>
                </select>
              </div>
            </div>

            <!-- Excel Schema Guidelines -->
            <div class="mt-6 p-4 rounded-2xl bg-slate-50 border border-slate-200 text-xs text-slate-700 space-y-1.5">
              <p class="font-bold text-slate-900">Required Excel Headers:</p>
              <ul class="list-disc pl-4 space-y-0.5 text-slate-600 text-[11.5px]">
                <li><code class="font-mono font-bold text-slate-900">student_number</code> (e.g. 2026-00123)</li>
                <li><code class="font-mono font-bold text-slate-900">full_name</code> (e.g. Juan Dela Cruz)</li>
                <li><code class="font-mono font-bold text-slate-900">email</code> (e.g. juan@bestlink.edu.ph)</li>
                <li><code class="font-mono font-bold text-slate-900">program</code> &amp; <code class="font-mono font-bold text-slate-900">section</code> (e.g. BSIT 3-A)</li>
              </ul>
            </div>
          </div>

          <!-- Drag and drop zone -->
          <div>
            <h2 class="font-bold text-slate-900 text-base mb-2">2. Upload Spreadsheet (.xlsx, .csv)</h2>
            <div class="border-2 border-dashed border-slate-300 hover:border-[#1e3b8a] rounded-2xl p-8 text-center transition cursor-pointer bg-slate-50/70 hover:bg-slate-50 flex flex-col items-center justify-center min-h-[220px] group" onclick="simulateAdminFileSelect()">
              <div class="w-12 h-12 rounded-2xl bg-[#1e3b8a]/10 text-[#1e3b8a] flex items-center justify-center mb-3 group-hover:scale-105 transition shadow-xs">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
              </div>
              <span class="text-xs font-bold text-slate-800">Click to browse or drop Master Roster Excel</span>
              <p class="text-[11px] text-slate-400 mt-1">Accepts .xlsx, .xls, .csv up to 15MB</p>
              <span id="admin-file-name" class="mt-3 px-3 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 font-mono text-xs font-bold hidden">official_students_AY2025_2026.xlsx</span>
            </div>

            <div class="mt-4 flex items-center justify-between">
              <a href="#" onclick="alert('Sample Excel Template Downloaded'); return false;" class="text-xs font-semibold text-[#1e3b8a] hover:underline cursor-pointer">
                Download Master Excel Template (.xlsx)
              </a>
              <button type="button" onclick="goToStep2()" class="px-5 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
                Validate &amp; Preview Records →
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: Validation & Preview Container (Initially Hidden) -->
      <div id="step-2-container" class="hidden bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-xs space-y-6 w-full">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
          <div>
            <h2 class="font-bold text-slate-900 text-base">Spreadsheet Validation Summary</h2>
            <p class="text-xs text-slate-500">File: <strong class="text-slate-800">official_students_AY2025_2026.xlsx</strong> • 150 Total Rows Read</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">148 Valid Records</span>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">2 Duplicates Flagged</span>
          </div>
        </div>

        <!-- Preview Table -->
        <div class="overflow-x-auto border border-slate-200 rounded-xl">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] border-b border-slate-200">
              <tr>
                <th class="py-3 px-3.5">Row</th>
                <th class="py-3 px-3.5">Student ID</th>
                <th class="py-3 px-3.5">Full Name</th>
                <th class="py-3 px-3.5">Email</th>
                <th class="py-3 px-3.5">Program &amp; Section</th>
                <th class="py-3 px-3.5">Validation Result</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr class="hover:bg-slate-50/60 transition-colors">
                <td class="py-3 px-3.5 text-slate-400">1</td>
                <td class="py-3 px-3.5 font-mono font-semibold text-[#1e3b8a]">2026-00123</td>
                <td class="py-3 px-3.5 font-bold text-slate-900">Juan Dela Cruz</td>
                <td class="py-3 px-3.5 text-slate-600">juan.delacruz@bestlink.edu.ph</td>
                <td class="py-3 px-3.5"><span class="px-2 py-0.5 rounded-md bg-[#1e3b8a]/10 text-[#1e3b8a] font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-3 px-3.5"><span class="text-emerald-700 font-semibold flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Valid (New Student)</span></td>
              </tr>
              <tr class="hover:bg-slate-50/60 transition-colors">
                <td class="py-3 px-3.5 text-slate-400">2</td>
                <td class="py-3 px-3.5 font-mono font-semibold text-[#1e3b8a]">2026-00124</td>
                <td class="py-3 px-3.5 font-bold text-slate-900">Maria Santos</td>
                <td class="py-3 px-3.5 text-slate-600">maria.santos@bestlink.edu.ph</td>
                <td class="py-3 px-3.5"><span class="px-2 py-0.5 rounded-md bg-[#1e3b8a]/10 text-[#1e3b8a] font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-3 px-3.5"><span class="text-emerald-700 font-semibold flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Valid (New Student)</span></td>
              </tr>
              <tr class="hover:bg-slate-50/60 transition-colors bg-rose-50/30">
                <td class="py-3 px-3.5 text-slate-400">3</td>
                <td class="py-3 px-3.5 font-mono font-semibold text-rose-700">2026-00123</td>
                <td class="py-3 px-3.5 font-bold text-slate-900">Juan Dela Cruz</td>
                <td class="py-3 px-3.5 text-slate-600">juan.delacruz@bestlink.edu.ph</td>
                <td class="py-3 px-3.5"><span class="px-2 py-0.5 rounded-md bg-[#1e3b8a]/10 text-[#1e3b8a] font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-3 px-3.5"><span class="text-rose-700 font-semibold flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Duplicate ID in file (Skipped)</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
          <button type="button" onclick="goToStep1()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
            ← Choose Different File
          </button>
          <button type="button" onclick="commitMasterRecords()" class="px-6 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white font-semibold text-xs shadow-xs transition cursor-pointer">
            Commit 148 Students to Official Master →
          </button>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
function simulateAdminFileSelect() {
  document.getElementById('admin-file-name').classList.remove('hidden');
}

function goToStep2() {
  document.getElementById('step-1-container').classList.add('hidden');
  document.getElementById('step-2-container').classList.remove('hidden');
}

function goToStep1() {
  document.getElementById('step-2-container').classList.add('hidden');
  document.getElementById('step-1-container').classList.remove('hidden');
}

function commitMasterRecords() {
  alert('Successfully committed 148 student master records to the database!');
  window.location.href = '<?php echo url("admin/students"); ?>';
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
