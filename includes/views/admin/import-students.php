<?php
$page_title = 'Import Student Master';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body max-w-5xl mx-auto">
      <!-- Breadcrumb & Header -->
      <div class="mb-6">
        <a href="<?php echo url('admin/students'); ?>" class="text-xs font-semibold text-blue-600 hover:underline inline-flex items-center gap-1 mb-2">
          ← Back to Student Master Directory
        </a>
        <h1 class="text-2xl font-bold text-slate-800">Bulk Import Student Master</h1>
        <p class="text-sm text-slate-500">Upload official institutional student enrollments. Teachers validate their class rosters against this master list.</p>
      </div>

      <!-- 3-Step Process Indicator -->
      <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm mb-6">
        <div class="flex items-center justify-between max-w-2xl mx-auto text-xs font-semibold">
          <div class="flex items-center gap-2 text-blue-600">
            <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-xs">1</span>
            <span>Upload Spreadsheet</span>
          </div>
          <div class="w-12 h-0.5 bg-slate-200"></div>
          <div class="flex items-center gap-2 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs">2</span>
            <span>Validate &amp; Preview</span>
          </div>
          <div class="w-12 h-0.5 bg-slate-200"></div>
          <div class="flex items-center gap-2 text-slate-400">
            <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold text-xs">3</span>
            <span>Commit to Master</span>
          </div>
        </div>
      </div>

      <!-- Step 1: Upload Container -->
      <div id="step-1-container" class="bg-white rounded-xl p-8 border border-slate-200 shadow-sm space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h2 class="font-bold text-slate-800 text-base mb-2">1. Select Academic Year &amp; Term</h2>
            <div class="space-y-3">
              <div>
                <label for="admin-term" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Academic Year</label>
                <select id="admin-term" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option>Academic Year 2025–2026 (1st Semester)</option>
                  <option>Academic Year 2025–2026 (2nd Semester)</option>
                </select>
              </div>

              <div>
                <label for="admin-prog" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Target Department / Program</label>
                <select id="admin-prog" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                  <option value="ALL">All Departments (Auto-detect from file)</option>
                  <option value="CCS">College of Computer Studies</option>
                  <option value="CBA">College of Business Administration</option>
                </select>
              </div>
            </div>

            <!-- Excel Schema Guidelines -->
            <div class="mt-6 p-4 rounded-xl bg-blue-50/60 border border-blue-100 text-xs text-blue-900 space-y-1.5">
              <p class="font-bold text-blue-950">Required Excel Headers:</p>
              <ul class="list-disc pl-4 space-y-0.5 text-blue-800">
                <li><code class="font-mono font-bold">student_number</code> (e.g. 2026-00123)</li>
                <li><code class="font-mono font-bold">full_name</code> (e.g. Juan Dela Cruz)</li>
                <li><code class="font-mono font-bold">email</code> (e.g. juan@bestlink.edu.ph)</li>
                <li><code class="font-mono font-bold">program</code> &amp; <code class="font-mono font-bold">section</code> (e.g. BSIT 3-A)</li>
              </ul>
            </div>
          </div>

          <!-- Drag and drop zone -->
          <div>
            <h2 class="font-bold text-slate-800 text-base mb-2">2. Upload Spreadsheet (.xlsx, .csv)</h2>
            <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-blue-500 transition cursor-pointer bg-slate-50 flex flex-col items-center justify-center min-h-[220px]" onclick="simulateAdminFileSelect()">
              <svg class="w-12 h-12 text-blue-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <span class="text-sm font-bold text-slate-700">Click to browse or drop Master Roster Excel</span>
              <p class="text-xs text-slate-400 mt-1">Accepts .xlsx, .xls, .csv up to 15MB</p>
              <span id="admin-file-name" class="mt-3 px-3 py-1 rounded bg-blue-100 text-blue-800 font-mono text-xs font-semibold hidden">official_students_AY2025_2026.xlsx</span>
            </div>

            <div class="mt-4 flex items-center justify-between">
              <a href="#" onclick="alert('Sample Excel Template Downloaded'); return false;" class="text-xs font-semibold text-blue-600 hover:underline">
                Download Master Excel Template (.xlsx)
              </a>
              <button type="button" onclick="goToStep2()" class="px-5 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow transition">
                Validate &amp; Preview Records →
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Step 2: Validation & Preview Container (Initially Hidden) -->
      <div id="step-2-container" class="hidden bg-white rounded-xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-200">
          <div>
            <h2 class="font-bold text-slate-800 text-base">Spreadsheet Validation Summary</h2>
            <p class="text-xs text-slate-500">File: <strong class="text-slate-800">official_students_AY2025_2026.xlsx</strong> • 150 Total Rows Read</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="badge badge-present font-bold">148 Valid Records</span>
            <span class="badge badge-absent font-bold">2 Duplicates Flagged</span>
          </div>
        </div>

        <!-- Preview Table -->
        <div class="overflow-x-auto border border-slate-200 rounded-lg">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200">
              <tr>
                <th class="py-2.5 px-3">Row</th>
                <th class="py-2.5 px-3">Student ID</th>
                <th class="py-2.5 px-3">Full Name</th>
                <th class="py-2.5 px-3">Email</th>
                <th class="py-2.5 px-3">Program &amp; Section</th>
                <th class="py-2.5 px-3">Validation Result</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <tr class="bg-emerald-50/20">
                <td class="py-2.5 px-3 text-slate-400">1</td>
                <td class="py-2.5 px-3 font-mono font-bold text-blue-700">2026-00123</td>
                <td class="py-2.5 px-3 font-semibold text-slate-800">Juan Dela Cruz</td>
                <td class="py-2.5 px-3">juan.delacruz@bestlink.edu.ph</td>
                <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-2.5 px-3"><span class="text-emerald-700 font-bold"> Valid (New Student)</span></td>
              </tr>
              <tr class="bg-emerald-50/20">
                <td class="py-2.5 px-3 text-slate-400">2</td>
                <td class="py-2.5 px-3 font-mono font-bold text-blue-700">2026-00124</td>
                <td class="py-2.5 px-3 font-semibold text-slate-800">Maria Santos</td>
                <td class="py-2.5 px-3">maria.santos@bestlink.edu.ph</td>
                <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-2.5 px-3"><span class="text-emerald-700 font-bold"> Valid (New Student)</span></td>
              </tr>
              <tr class="bg-rose-50/30">
                <td class="py-2.5 px-3 text-slate-400">3</td>
                <td class="py-2.5 px-3 font-mono font-bold text-rose-700">2026-00123</td>
                <td class="py-2.5 px-3 font-semibold text-slate-800">Juan Dela Cruz</td>
                <td class="py-2.5 px-3">juan.delacruz@bestlink.edu.ph</td>
                <td class="py-2.5 px-3"><span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">BSIT 3-A</span></td>
                <td class="py-2.5 px-3"><span class="text-rose-700 font-bold">⚠ Duplicate ID in file (Skipped)</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
          <button type="button" onclick="goToStep1()" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
            ← Choose Different File
          </button>
          <button type="button" onclick="commitMasterRecords()" class="px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-md transition">
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
