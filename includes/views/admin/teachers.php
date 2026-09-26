<?php
$page_title = 'Faculty & Teacher Master Directory';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__) . '/partials/header.php';

// Initial server-side query
$teachers = [];
$metrics = ['total' => 0, 'active' => 0, 'inactive' => 0, 'depts' => 0];
$initialLimit = 10;
$initialPage = 1;
$initialTotal = 0;
$initialTotalPages = 1;
$initialStart = 0;
$initialEnd = 0;

try {
    $db = Database::getConnection();

    $statsStmt = $db->query("
        SELECT 
            COUNT(*) AS total,
            SUM(status = 'active') AS active_count,
            SUM(status = 'inactive') AS inactive_count,
            COUNT(DISTINCT department) AS dept_count
        FROM `teachers`
    ");
    $rawMetrics = $statsStmt->fetch();
    if ($rawMetrics) {
        $metrics = [
            'total'    => (int)($rawMetrics['total'] ?? 0),
            'active'   => (int)($rawMetrics['active_count'] ?? 0),
            'inactive' => (int)($rawMetrics['inactive_count'] ?? 0),
            'depts'    => (int)($rawMetrics['dept_count'] ?? 0)
        ];
    }

    $initialTotal = $metrics['total'];
    $initialTotalPages = max(1, (int)ceil($initialTotal / $initialLimit));
    $initialStart = $initialTotal > 0 ? 1 : 0;
    $initialEnd = min($initialLimit, $initialTotal);

    $stmt = $db->prepare("SELECT * FROM `teachers` ORDER BY id DESC LIMIT ?");
    $stmt->bindValue(1, $initialLimit, PDO::PARAM_INT);
    $stmt->execute();
    $teachers = $stmt->fetchAll();
} catch (Exception $e) {
    // Database connection or table not yet seeded
}
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Breadcrumb & Page Title -->
      <div class="mb-6">
        <div class="flex items-center gap-2 mb-1.5">
          <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#1e3b8a]/10 text-[#1e3b8a] border border-[#1e3b8a]/20">Admin Portal</span>
          <span class="text-xs text-slate-400 font-medium">•</span>
          <span class="text-xs text-slate-500 font-semibold">Faculty Master Accounts</span>
        </div>
        <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Teacher &amp; Faculty Master Directory</h1>
        <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
          Manage teacher faculty accounts, search instructor credentials, or bulk import and export faculty records.
        </p>
      </div>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-[#1e3b8a]/10 border border-[#1e3b8a]/15 flex items-center justify-center text-[#1e3b8a] shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
          </div>
          <div>
            <div id="stat-total-faculty" class="text-xl font-bold text-slate-900"><?= number_format($metrics['total']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Total Teachers</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div id="stat-active-faculty" class="text-xl font-bold text-emerald-600"><?= number_format($metrics['active']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Active Faculty</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
          </div>
          <div>
            <div id="stat-inactive-faculty" class="text-xl font-bold text-rose-600"><?= number_format($metrics['inactive']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Inactive Accounts</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-700 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div>
            <div id="stat-depts-faculty" class="text-xl font-bold text-sky-700"><?= number_format($metrics['depts']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Academic Depts</div>
          </div>
        </div>
      </div>

      <!-- Filters, Search & Action Bar -->
      <div class="bg-white rounded-2xl p-3 sm:p-4 border border-slate-200/80 shadow-xs mb-6">
        <div class="flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-3">
          <!-- Left: Search & Filters Group -->
          <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-0">
            <!-- Search box -->
            <div class="relative flex-1 sm:flex-initial sm:w-64 md:w-72 min-w-[180px]">
              <input type="text" id="search-teacher" placeholder="Search by name, ID, department..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50/60 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-[#1e3b8a] text-slate-800 transition shadow-2xs" oninput="debounceTeacherSearch()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <!-- Department filter -->
            <select id="filter-dept" class="px-3 py-2 text-xs bg-slate-50/60 border border-slate-200 rounded-xl text-slate-700 font-semibold focus:outline-none focus:border-[#1e3b8a] min-w-[130px] cursor-pointer" onchange="fetchTeachers(1)">
              <option value="all">All Departments</option>
              <option value="College of Computer Studies">College of Computer Studies</option>
              <option value="College of Business Administration">College of Business Administration</option>
              <option value="College of Criminology">College of Criminology</option>
              <option value="College of Education">College of Education</option>
              <option value="General Academics">General Academics</option>
            </select>

            <!-- Status filter -->
            <select id="filter-status" class="px-3 py-2 text-xs bg-slate-50/60 border border-slate-200 rounded-xl text-slate-700 font-semibold focus:outline-none focus:border-[#1e3b8a] min-w-[100px] cursor-pointer" onchange="fetchTeachers(1)">
              <option value="all">All Status</option>
              <option value="active">Active Only</option>
              <option value="inactive">Inactive Only</option>
            </select>
          </div>

          <!-- Right: Consolidated Action Controls -->
          <div class="flex flex-wrap items-center gap-2 shrink-0">
            <!-- Combined Export Dropdown -->
            <div class="relative inline-block text-left" id="export-dropdown-wrapper">
              <button type="button" onclick="toggleExportDropdown(event)" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer" title="Export faculty roster">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Export Roster</span>
                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>

              <!-- Dropdown Menu -->
              <div id="export-dropdown-menu" class="hidden absolute right-0 mt-1.5 w-56 bg-white rounded-2xl shadow-xl border border-slate-200 py-1.5 z-30 animate-in fade-in zoom-in duration-100">
                <div class="px-3 py-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">Choose Export Format</div>
                <button type="button" onclick="exportTeacherRoster('csv')" class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#1e3b8a] flex items-center gap-2.5 transition cursor-pointer">
                  <span class="w-6 h-6 rounded-lg bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-[10px] border border-emerald-100">CSV</span>
                  <div>
                    <div class="font-bold">Export as CSV (.csv)</div>
                    <div class="text-[10px] text-slate-400">Standard comma-separated file</div>
                  </div>
                </button>
                <button type="button" onclick="exportTeacherRoster('excel')" class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-[#1e3b8a] flex items-center gap-2.5 transition cursor-pointer">
                  <span class="w-6 h-6 rounded-lg bg-blue-50 text-[#1e3b8a] flex items-center justify-center font-bold text-[10px] border border-blue-100">XLS</span>
                  <div>
                    <div class="font-bold">Export as Excel (.xlsx)</div>
                    <div class="text-[10px] text-slate-400">Styled workbook for Microsoft Excel</div>
                  </div>
                </button>
                <div class="border-t border-slate-100 my-1"></div>
                <button type="button" onclick="downloadTeacherCsvTemplate(); closeExportDropdown();" class="w-full text-left px-3.5 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50 hover:text-slate-900 flex items-center gap-2.5 transition cursor-pointer">
                  <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                  <span>Download Blank Template</span>
                </button>
              </div>
            </div>

            <!-- Modal Button 1: Bulk Import CSV/Excel -->
            <button type="button" onclick="openTeacherExcelModal()" class="px-3.5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition flex items-center gap-1.5 cursor-pointer shadow-xs whitespace-nowrap" title="Bulk Import Faculty">
              <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
              <span>Bulk Import</span>
            </button>

            <!-- Modal Button 2: + Add Teacher Account -->
            <button type="button" onclick="openManualTeacherModal()" class="px-4 py-2 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer whitespace-nowrap" title="Add Teacher Account">
              <svg class="w-4 h-4 text-sky-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
              </svg>
              <span>+ Add Teacher</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Teachers Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200 text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                <th class="py-3.5 px-4">Employee ID</th>
                <th class="py-3.5 px-4">Faculty Name &amp; Email</th>
                <th class="py-3.5 px-4">Department &amp; Position</th>
                <th class="py-3.5 px-4">Contact Number</th>
                <th class="py-3.5 px-4">Date Hired</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="teachers-table-body" class="divide-y divide-slate-100">
              <?php if (empty($teachers)): ?>
                <tr id="no-teachers-row">
                  <td colspan="7" class="py-12 text-center text-slate-400">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    </div>
                    <div class="font-bold text-slate-700">No Teacher Accounts Found</div>
                    <p class="text-xs text-slate-400 mt-1">Add a teacher manually or import a batch via CSV/Excel.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($teachers as $t): 
                  $isActive = ($t['status'] === 'active');
                  $initials = strtoupper(substr($t['full_name'], 0, 2));
                ?>
                <tr class="teacher-row hover:bg-slate-50/60 transition-colors" data-id="<?= $t['id'] ?>">
                  <td class="py-3.5 px-4 font-mono font-semibold text-[#1e3b8a]">
                    <div class="flex items-center gap-1.5">
                      <span class="w-1.5 h-1.5 rounded-full <?= $isActive ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                      <span><?= htmlspecialchars($t['employee_id']) ?></span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-xl bg-[#1e3b8a] text-white font-bold text-xs flex items-center justify-center shrink-0">
                        <?= htmlspecialchars($initials) ?>
                      </div>
                      <div>
                        <div class="font-bold text-slate-900"><?= htmlspecialchars($t['full_name']) ?></div>
                        <div class="text-[11px] text-slate-400"><?= htmlspecialchars($t['email']) ?></div>
                      </div>
                    </div>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="font-semibold text-slate-800"><?= htmlspecialchars($t['department'] ?: 'General Academics') ?></div>
                    <div class="text-[11px] text-slate-400"><?= htmlspecialchars($t['position'] ?: 'Faculty') ?></div>
                  </td>
                  <td class="py-3.5 px-4 font-medium text-slate-600">
                    <?= htmlspecialchars($t['contact_number'] ?: '—') ?>
                  </td>
                  <td class="py-3.5 px-4 text-slate-500">
                    <?= htmlspecialchars($t['date_hired'] ?: '—') ?>
                  </td>
                  <td class="py-3.5 px-4">
                    <?php if ($isActive): ?>
                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Active</span>
                    <?php else: ?>
                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3.5 px-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-[#1e3b8a] hover:bg-slate-100 transition cursor-pointer" onclick='openEditTeacherModal(<?= json_encode($t) ?>)' title="Edit Account">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                      </button>
                      <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition cursor-pointer" onclick="openResetPasswordModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['full_name'])) ?>', '<?= htmlspecialchars(addslashes($t['employee_id'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($t['email'] ?? '')) ?>')" title="Reset Password">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                      </button>
                      <?php if ($isActive): ?>
                        <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" onclick="openDeactivateTeacherModal(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['full_name'])) ?>', '<?= htmlspecialchars(addslashes($t['employee_id'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($t['email'] ?? '')) ?>')" title="Deactivate">
                          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                        </button>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="px-5 py-3.5 bg-slate-50/80 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <!-- Left: Showing X to Y of Z faculty records -->
          <div class="text-slate-500 font-medium" id="table-results-counter">
            Showing <span id="pagination-start" class="font-bold text-slate-800"><?= $initialStart ?></span> to <span id="pagination-end" class="font-bold text-slate-800"><?= $initialEnd ?></span> of <span id="pagination-total" class="font-bold text-slate-800"><?= $initialTotal ?></span> faculty record(s)
          </div>

          <!-- Right: Rows per page & Prev/Next buttons -->
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-1.5 text-slate-500">
              <span class="text-[11px] font-medium text-slate-400">Rows per page:</span>
              <select id="teacher-page-limit" class="px-2 py-1 bg-white border border-slate-200 rounded-lg text-xs font-semibold text-slate-700 focus:outline-none focus:border-[#1e3b8a] shadow-2xs cursor-pointer" onchange="changeTeacherPageLimit()">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>

            <div class="flex items-center gap-1.5" id="pagination-controls">
              <button type="button" id="btn-prev-page" onclick="changeTeacherPage(-1)" disabled class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-semibold text-slate-700 shadow-2xs transition flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                <span>Previous</span>
              </button>
              <span class="text-xs font-semibold px-2.5 py-1 rounded-md bg-white border border-slate-200 text-slate-700 shadow-2xs whitespace-nowrap">
                Page <span id="current-page-display" class="font-bold text-[#1e3b8a]">1</span> of <span id="total-pages-display" class="font-bold"><?= $initialTotalPages ?></span>
              </span>
              <button type="button" id="btn-next-page" onclick="changeTeacherPage(1)" <?= ($initialTotalPages <= 1) ? 'disabled' : '' ?> class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed text-xs font-semibold text-slate-700 shadow-2xs transition flex items-center gap-1 cursor-pointer">
                <span>Next</span>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </button>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: CREATE TEACHER MANUALLY -->
<!-- ========================================================================= -->
<div id="manualTeacherModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <div class="px-6 py-4 bg-[#1e3b8a] text-white flex items-center justify-between">
      <div>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Manual Provisioning</span>
        <h3 class="text-lg font-bold tracking-tight mt-0.5">Create Teacher Faculty Account</h3>
      </div>
      <button type="button" onclick="closeManualTeacherModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="manual-teacher-form" onsubmit="handleManualTeacherSubmit(event)" class="p-6 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Employee ID <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="employee_id" required placeholder="e.g. t22012033" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-mono font-bold focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Full Name <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="full_name" required placeholder="e.g. Maria C. Santos" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Email Address <span class="text-rose-500">*</span>
          </label>
          <input type="email" name="email" required placeholder="m.santos@bestlink.edu.ph" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Academic Department
          </label>
          <select name="department" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
            <option value="College of Computer Studies">College of Computer Studies</option>
            <option value="College of Business Administration">College of Business Administration</option>
            <option value="College of Criminology">College of Criminology</option>
            <option value="College of Education">College of Education</option>
            <option value="General Academics">General Academics</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Position</label>
          <input type="text" name="position" value="Instructor" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Number</label>
          <input type="text" name="contact_number" placeholder="0917-123-4567" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Date Hired</label>
          <input type="date" name="date_hired" value="<?= date('Y-m-d') ?>" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
      </div>

      <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
        <div>
          <div class="font-bold text-slate-800">Default Password Rule</div>
          <div class="text-[11px] text-slate-500">Auto-generated: #(first letter of surname + second letter lowercase)8080</div>
        </div>
        <span class="px-2.5 py-1 rounded-lg bg-amber-50 border border-amber-200 font-mono font-bold text-amber-900 text-xs">#Surname8080 (e.g. #Me8080)</span>
      </div>

      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closeManualTeacherModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">Cancel</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          Save Teacher Account
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 2: EDIT TEACHER -->
<!-- ========================================================================= -->
<div id="editTeacherModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <div class="px-6 py-4 bg-[#1e3b8a] text-white flex items-center justify-between">
      <div>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Faculty Master</span>
        <h3 class="text-lg font-bold tracking-tight mt-0.5">Edit Teacher Account</h3>
      </div>
      <button type="button" onclick="closeEditTeacherModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="edit-teacher-form" onsubmit="handleEditTeacherSubmit(event)" class="p-6 space-y-4">
      <input type="hidden" id="edit-id" name="id">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Employee ID</label>
          <input type="text" id="edit-employee-id" disabled class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs font-mono bg-slate-100 cursor-not-allowed">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name <span class="text-rose-500">*</span></label>
          <input type="text" id="edit-full-name" name="full_name" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address <span class="text-rose-500">*</span></label>
          <input type="email" id="edit-email" name="email" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Department</label>
          <input type="text" id="edit-department" name="department" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Position</label>
          <input type="text" id="edit-position" name="position" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Number</label>
          <input type="text" id="edit-contact" name="contact_number" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Status</label>
          <select id="edit-status" name="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs font-semibold focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closeEditTeacherModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">Cancel</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          Update Account
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 3: BULK IMPORT CSV / EXCEL FILE -->
<!-- ========================================================================= -->
<div id="teacherExcelModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <div class="px-6 py-4 bg-[#1e3b8a] text-white flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Faculty Batch Importer</span>
          <span class="text-xs text-sky-200 font-medium">CSV / Excel Ingestion</span>
        </div>
        <h3 class="text-lg font-bold tracking-tight mt-0.5">Import Faculty Master Records</h3>
      </div>
      <button type="button" onclick="closeTeacherExcelModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="p-6 space-y-5">
      <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-start gap-3.5">
        <div class="w-8 h-8 rounded-xl bg-[#1e3b8a]/10 text-[#1e3b8a] flex items-center justify-center shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="text-xs text-slate-800">
          <div class="font-bold mb-0.5">Expected Column Headers in First Row:</div>
          <p class="text-slate-600 font-mono text-[11px] leading-relaxed">
            employee_id, full_name, email, department, position, contact_number, date_hired
          </p>
        </div>
      </div>

      <!-- File Dropzone -->
      <div id="teacher-excel-dropzone" class="border-2 border-dashed border-slate-300 hover:border-[#1e3b8a] rounded-2xl p-7 text-center transition cursor-pointer bg-slate-50/70 hover:bg-slate-50 flex flex-col items-center justify-center gap-2 group" onclick="triggerTeacherFileInput()">
        <input type="file" id="teacher-excel-file-input" accept=".csv,.xlsx,.txt" class="hidden" onchange="handleTeacherFileSelected(event)">
        <div class="w-12 h-12 rounded-2xl bg-[#1e3b8a]/10 text-[#1e3b8a] flex items-center justify-center group-hover:scale-105 transition shadow-xs">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
        </div>
        <div class="text-xs font-bold text-slate-800">
          Click to choose CSV or Excel spreadsheet, or drag &amp; drop file here
        </div>
        <div class="text-[11px] text-slate-400">Supported formats: .csv, .xlsx (Max 10MB)</div>
      </div>

      <!-- Selected File Pill -->
      <div id="teacher-selected-file-pill" class="hidden p-3 rounded-xl bg-emerald-50 border border-emerald-200 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span id="teacher-selected-file-name" class="text-xs font-bold text-emerald-900"></span>
        </div>
        <button type="button" onclick="clearTeacherFileInput()" class="text-xs text-rose-600 hover:underline font-semibold cursor-pointer">Remove</button>
      </div>

      <!-- Import Summary / Results Area -->
      <div id="import-summary-container" class="hidden space-y-3">
        <div class="p-4 rounded-xl border border-slate-200 bg-slate-50" id="import-summary-alert">
          <h4 class="text-xs font-bold text-slate-800" id="import-summary-title">Import Finished</h4>
          <div class="grid grid-cols-3 gap-2 mt-2 text-center text-xs">
            <div class="p-2 rounded-lg bg-white border border-slate-200">Total: <strong id="summary-total">0</strong></div>
            <div class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800">Inserted: <strong id="summary-inserted">0</strong></div>
            <div class="p-2 rounded-lg bg-amber-50 border border-amber-200 text-amber-800">Skipped: <strong id="summary-skipped">0</strong></div>
          </div>
        </div>

        <div id="import-errors-box" class="hidden border border-amber-200 rounded-xl overflow-hidden bg-white shadow-xs">
          <div class="px-4 py-2.5 bg-amber-50/80 border-b border-amber-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
              <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
              <span class="font-bold text-amber-900 text-xs">Skipped Duplicates &amp; Row Issues</span>
            </div>
            <span id="skipped-badge-count" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-200 text-amber-900">0 Skipped</span>
          </div>
          <div class="max-h-56 overflow-y-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 border-b border-slate-200 sticky top-0">
                <tr>
                  <th class="py-2 px-3">Row</th>
                  <th class="py-2 px-3">Employee ID</th>
                  <th class="py-2 px-3">Email Address</th>
                  <th class="py-2 px-3">Reason / Details</th>
                </tr>
              </thead>
              <tbody id="import-errors-table-body" class="divide-y divide-slate-100 font-medium text-slate-700">
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
        <button type="button" onclick="downloadTeacherCsvTemplate()" class="text-xs font-semibold text-[#1e3b8a] hover:underline flex items-center gap-1.5 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          <span>Download Blank CSV Template</span>
        </button>
        <div class="flex items-center gap-2">
          <button type="button" onclick="closeTeacherExcelModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">Close</button>
          <button type="button" id="btn-process-import" onclick="processTeacherExcelImport()" class="px-5 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Start File Ingestion</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 4: RESET FACULTY PASSWORD -->
<!-- ========================================================================= -->
<div id="resetPasswordModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-amber-600 to-amber-700 text-white flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        </div>
        <div>
          <h3 class="text-base font-bold tracking-tight">Reset Faculty Password</h3>
          <p class="text-xs text-amber-100">Provision a new temporary credential</p>
        </div>
      </div>
      <button type="button" onclick="closeResetPasswordModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Step 1: Confirmation State -->
    <div id="reset-modal-confirm-step" class="p-6 space-y-4">
      <input type="hidden" id="reset-teacher-id">

      <!-- Target Teacher Card -->
      <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
        <div class="flex items-center justify-between">
          <span class="text-slate-500 font-medium">Faculty Member:</span>
          <span id="reset-teacher-name" class="font-bold text-slate-800 text-sm"></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-500 font-medium">Employee ID:</span>
          <span id="reset-teacher-emp-id" class="font-mono font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200"></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-500 font-medium">Email Address:</span>
          <span id="reset-teacher-email" class="font-mono text-slate-600"></span>
        </div>
      </div>

      <!-- Warning Notice -->
      <div class="p-3 rounded-xl bg-amber-50/80 border border-amber-200 text-amber-900 text-xs flex items-start gap-2.5">
        <svg class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div>
          <strong>Are you sure?</strong> Generating a temporary password will overwrite the current password for this instructor and will be logged to system notifications.
        </div>
      </div>

      <!-- Action Footer -->
      <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2.5">
        <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
          Cancel
        </button>
        <button type="button" id="btn-confirm-reset-pass" onclick="executePasswordReset()" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
          <span>Generate Temp Password</span>
        </button>
      </div>
    </div>

    <!-- Step 2: Result State (Generated Temp Password) -->
    <div id="reset-modal-result-step" class="p-6 space-y-4 hidden">
      <!-- Success Banner -->
      <div class="text-center space-y-1">
        <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-600 mx-auto flex items-center justify-center">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h4 class="text-base font-bold text-slate-900">Password Reset Successful!</h4>
        <p class="text-xs text-slate-500">A new temporary credential has been generated for <strong id="reset-result-name" class="text-slate-800"></strong>.</p>
      </div>

      <!-- Password Card with Copy Action -->
      <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-2">
        <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Temporary Password</div>
        <div class="flex items-center justify-center gap-2">
          <span id="reset-result-password" class="text-xl font-mono font-black text-amber-700 bg-amber-50 border border-amber-200 px-4 py-1.5 rounded-xl select-all tracking-wider"></span>
          <button type="button" onclick="copyTempPassword()" id="btn-copy-temp-pass" class="px-3 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 shadow-2xs cursor-pointer" title="Copy to clipboard">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
            <span id="copy-btn-text">Copy</span>
          </button>
        </div>
        <p class="text-[11px] text-slate-500 pt-1">Please copy or forward this temporary password to the faculty member.</p>
      </div>

      <!-- Security / Login Note -->
      <div class="p-3 rounded-xl bg-blue-50/80 border border-blue-200 text-blue-950 text-xs flex items-center gap-2">
        <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>The instructor will be required to configure a new personal password on their next sign in.</span>
      </div>

      <!-- Action Footer -->
      <div class="pt-2 border-t border-slate-100 flex items-center justify-end">
        <button type="button" onclick="closeResetPasswordModal()" class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white text-xs font-semibold shadow-xs transition cursor-pointer">
          Done &amp; Close
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 5: DISABLE / DEACTIVATE FACULTY MEMBER -->
<!-- ========================================================================= -->
<div id="deactivateTeacherModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-md bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <!-- Header -->
    <div class="px-6 py-4 bg-gradient-to-r from-rose-600 to-rose-700 text-white flex items-center justify-between">
      <div class="flex items-center gap-2.5">
        <div class="w-9 h-9 rounded-xl bg-white/20 flex items-center justify-center text-white shrink-0">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        </div>
        <div>
          <h3 class="text-base font-bold tracking-tight">Disable Faculty Account</h3>
          <p class="text-xs text-rose-100">Suspend instructor access &amp; permissions</p>
        </div>
      </div>
      <button type="button" onclick="closeDeactivateTeacherModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition cursor-pointer">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="p-6 space-y-4">
      <input type="hidden" id="deactivate-teacher-id">

      <!-- Target Teacher Card -->
      <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-2 text-xs">
        <div class="flex items-center justify-between">
          <span class="text-slate-500 font-medium">Faculty Member:</span>
          <span id="deactivate-teacher-name" class="font-bold text-slate-800 text-sm"></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-500 font-medium">Employee ID:</span>
          <span id="deactivate-teacher-emp-id" class="font-mono font-bold text-slate-700 bg-white px-2 py-0.5 rounded border border-slate-200"></span>
        </div>
        <div class="flex items-center justify-between">
          <span class="text-slate-500 font-medium">Email Address:</span>
          <span id="deactivate-teacher-email" class="font-mono text-slate-600"></span>
        </div>
      </div>

      <!-- Warning Callout -->
      <div class="p-3 rounded-xl bg-rose-50/80 border border-rose-200 text-rose-900 text-xs flex items-start gap-2.5">
        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        <div class="space-y-1">
          <div class="font-bold">Are you sure you want to disable this account?</div>
          <p class="text-[11px] text-rose-800 leading-relaxed">
            Deactivating will immediately prevent this faculty member from logging into the portal and launching attendance sessions. Their historical records and assigned classes will be preserved.
          </p>
        </div>
      </div>

      <!-- Action Footer -->
      <div class="pt-2 border-t border-slate-100 flex items-center justify-end gap-2.5">
        <button type="button" onclick="closeDeactivateTeacherModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
          Cancel
        </button>
        <button type="button" id="btn-confirm-deactivate-teacher" onclick="confirmDeactivateTeacher()" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
          <span>Disable Account</span>
        </button>
      </div>
    </div>
  </div>
</div>
    </div>
  </div>
</div>

<script>
var searchTimeout = null;
var currentTeacherPage = 1;
var teacherPageLimit = 10;
var totalTeacherPages = <?= $initialTotalPages ?>;

function debounceTeacherSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchTeachers(1);
  }, 300);
}

// Fetch live teachers from API with pagination
async function fetchTeachers(page = currentTeacherPage) {
  currentTeacherPage = page;
  const search = document.getElementById('search-teacher').value.trim();
  const dept   = document.getElementById('filter-dept').value;
  const status = document.getElementById('filter-status').value;
  const limitSelect = document.getElementById('teacher-page-limit');
  teacherPageLimit = limitSelect ? parseInt(limitSelect.value, 10) : 10;

  const url = `${window.url('api/teachers')}?page=${currentTeacherPage}&limit=${teacherPageLimit}&search=${encodeURIComponent(search)}&department=${encodeURIComponent(dept)}&status=${encodeURIComponent(status)}`;

  try {
    const res = await fetch(url);
    const data = await res.json();
    if (data.status === 'success') {
      renderTeachersTable(data.data);
      if (data.metrics) {
        document.getElementById('stat-total-faculty').textContent = Number(data.metrics.total).toLocaleString();
        document.getElementById('stat-active-faculty').textContent = Number(data.metrics.active).toLocaleString();
        document.getElementById('stat-inactive-faculty').textContent = Number(data.metrics.inactive).toLocaleString();
        document.getElementById('stat-depts-faculty').textContent = Number(data.metrics.depts).toLocaleString();
      }

      // Update Pagination UI
      const pag = data.pagination || { page: 1, limit: teacherPageLimit, total: data.data.length, total_pages: 1 };
      totalTeacherPages = Math.max(1, pag.total_pages);
      currentTeacherPage = Math.min(pag.page, totalTeacherPages);

      const total = pag.total;
      const start = total === 0 ? 0 : ((currentTeacherPage - 1) * pag.limit) + 1;
      const end = Math.min(currentTeacherPage * pag.limit, total);

      const startEl = document.getElementById('pagination-start');
      const endEl = document.getElementById('pagination-end');
      const totalEl = document.getElementById('pagination-total');
      const pageDisplay = document.getElementById('current-page-display');
      const totalPagesDisplay = document.getElementById('total-pages-display');
      const btnPrev = document.getElementById('btn-prev-page');
      const btnNext = document.getElementById('btn-next-page');

      if (startEl) startEl.textContent = start.toLocaleString();
      if (endEl) endEl.textContent = end.toLocaleString();
      if (totalEl) totalEl.textContent = total.toLocaleString();
      if (pageDisplay) pageDisplay.textContent = currentTeacherPage;
      if (totalPagesDisplay) totalPagesDisplay.textContent = totalTeacherPages;

      if (btnPrev) btnPrev.disabled = (currentTeacherPage <= 1);
      if (btnNext) btnNext.disabled = (currentTeacherPage >= totalTeacherPages);
    }
  } catch (err) {
    console.error(err);
  }
}

function changeTeacherPage(delta) {
  const targetPage = currentTeacherPage + delta;
  if (targetPage >= 1 && targetPage <= totalTeacherPages) {
    fetchTeachers(targetPage);
  }
}

function changeTeacherPageLimit() {
  fetchTeachers(1);
}

function renderTeachersTable(teachers) {
  const tbody = document.getElementById('teachers-table-body');
  if (!teachers || teachers.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="py-12 text-center text-slate-400">
          <div class="font-bold text-slate-700">No Teacher Accounts Found</div>
          <p class="text-xs text-slate-400 mt-1">Try adjusting your search criteria or add a new faculty account.</p>
        </td>
      </tr>
    `;
    return;
  }

  let html = '';
  teachers.forEach(t => {
    const isActive = (t.status === 'active');
    const initials = (t.full_name || 'FC').substring(0, 2).toUpperCase();
    html += `
      <tr class="teacher-row hover:bg-slate-50/60 transition-colors" data-id="${t.id}">
        <td class="py-3.5 px-4 font-mono font-semibold text-[#1e3b8a]">
          <div class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full ${isActive ? 'bg-emerald-500' : 'bg-slate-300'}"></span>
            <span>${escapeHtml(t.employee_id)}</span>
          </div>
        </td>
        <td class="py-3.5 px-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-[#1e3b8a] text-white font-bold text-xs flex items-center justify-center shrink-0">
              ${escapeHtml(initials)}
            </div>
            <div>
              <div class="font-bold text-slate-900">${escapeHtml(t.full_name)}</div>
              <div class="text-[11px] text-slate-400">${escapeHtml(t.email)}</div>
            </div>
          </div>
        </td>
        <td class="py-3.5 px-4">
          <div class="font-semibold text-slate-800">${escapeHtml(t.department || 'General Academics')}</div>
          <div class="text-[11px] text-slate-400">${escapeHtml(t.position || 'Faculty')}</div>
        </td>
        <td class="py-3.5 px-4 font-medium text-slate-600">${escapeHtml(t.contact_number || '—')}</td>
        <td class="py-3.5 px-4 text-slate-500">${escapeHtml(t.date_hired || '—')}</td>
        <td class="py-3.5 px-4">
          ${isActive 
            ? '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Active</span>'
            : '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">Inactive</span>'
          }
        </td>
        <td class="py-3.5 px-4 text-right">
          <div class="flex items-center justify-end gap-1.5">
            <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-[#1e3b8a] hover:bg-slate-100 transition cursor-pointer" onclick='openEditTeacherModal(${JSON.stringify(t)})' title="Edit Account">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition cursor-pointer" onclick="openResetPasswordModal(${t.id}, '${escapeHtml(t.full_name)}', '${escapeHtml(t.employee_id || '')}', '${escapeHtml(t.email || '')}')" title="Reset Password">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </button>
            ${isActive ? `
              <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer" onclick="openDeactivateTeacherModal(${t.id}, '${escapeHtml(t.full_name)}', '${escapeHtml(t.employee_id || '')}', '${escapeHtml(t.email || '')}')" title="Deactivate">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
              </button>
            ` : ''}
          </div>
        </td>
      </tr>
    `;
  });
  tbody.innerHTML = html;
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// Modal Handlers
function openManualTeacherModal() {
  document.getElementById('manualTeacherModal').classList.remove('hidden');
}
function closeManualTeacherModal() {
  document.getElementById('manualTeacherModal').classList.add('hidden');
}

async function handleManualTeacherSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch(window.url('api/teachers'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.status === 'success') {
      APP.toast(result.message, 'success');
      closeManualTeacherModal();
      form.reset();
      fetchTeachers(1);
    } else {
      APP.toast(result.message || 'Validation error', 'error');
    }
  } catch (err) {
    APP.toast('Failed to create faculty account.', 'error');
  }
}

// Edit Modal
function openEditTeacherModal(teacher) {
  document.getElementById('edit-id').value = teacher.id;
  document.getElementById('edit-employee-id').value = teacher.employee_id;
  document.getElementById('edit-full-name').value = teacher.full_name;
  document.getElementById('edit-email').value = teacher.email;
  document.getElementById('edit-department').value = teacher.department || '';
  document.getElementById('edit-position').value = teacher.position || 'Instructor';
  document.getElementById('edit-contact').value = teacher.contact_number || '';
  document.getElementById('edit-status').value = teacher.status || 'active';
  document.getElementById('editTeacherModal').classList.remove('hidden');
}
function closeEditTeacherModal() {
  document.getElementById('editTeacherModal').classList.add('hidden');
}

async function handleEditTeacherSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const data = Object.fromEntries(formData.entries());

  try {
    const res = await fetch(window.url('api/teachers/update'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    const result = await res.json();
    if (result.status === 'success') {
      APP.toast(result.message, 'success');
      closeEditTeacherModal();
      fetchTeachers();
    } else {
      APP.toast(result.message || 'Update failed', 'error');
    }
  } catch (err) {
    APP.toast('Error updating teacher account.', 'error');
  }
}

// Reset Password Modal Workflow
let currentResetTeacher = null;

function openResetPasswordModal(id, name, employeeId = '', email = '') {
  currentResetTeacher = { id, name, employeeId, email };

  const idInput = document.getElementById('reset-teacher-id');
  const nameEl = document.getElementById('reset-teacher-name');
  const empEl = document.getElementById('reset-teacher-emp-id');
  const emailEl = document.getElementById('reset-teacher-email');

  if (idInput) idInput.value = id;
  if (nameEl) nameEl.textContent = name || 'Faculty Member';
  if (empEl) empEl.textContent = employeeId || 'N/A';
  if (emailEl) emailEl.textContent = email || 'N/A';

  // Show Step 1, hide Step 2
  const confirmStep = document.getElementById('reset-modal-confirm-step');
  const resultStep = document.getElementById('reset-modal-result-step');
  if (confirmStep) confirmStep.classList.remove('hidden');
  if (resultStep) resultStep.classList.add('hidden');

  const btn = document.getElementById('btn-confirm-reset-pass');
  if (btn) {
    btn.disabled = false;
    btn.innerHTML = `
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
      <span>Generate Temp Password</span>
    `;
  }

  const modal = document.getElementById('resetPasswordModal');
  if (modal) modal.classList.remove('hidden');
}

function closeResetPasswordModal() {
  const modal = document.getElementById('resetPasswordModal');
  if (modal) modal.classList.add('hidden');
  currentResetTeacher = null;
}

async function executePasswordReset() {
  if (!currentResetTeacher || !currentResetTeacher.id) return;

  const btn = document.getElementById('btn-confirm-reset-pass');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `
      <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
      <span>Generating...</span>
    `;
  }

  try {
    const res = await fetch(window.url('api/teachers/reset-password'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: currentResetTeacher.id })
    });
    const result = await res.json();

    if (result.status === 'success') {
      // Transition to Step 2 (result view)
      const confirmStep = document.getElementById('reset-modal-confirm-step');
      const resultStep = document.getElementById('reset-modal-result-step');
      if (confirmStep) confirmStep.classList.add('hidden');
      if (resultStep) resultStep.classList.remove('hidden');

      const resNameEl = document.getElementById('reset-result-name');
      const resPassEl = document.getElementById('reset-result-password');
      if (resNameEl) resNameEl.textContent = currentResetTeacher.name;
      if (resPassEl) resPassEl.textContent = result.temp_password;

      // Reset copy button state
      const copyBtnText = document.getElementById('copy-btn-text');
      if (copyBtnText) copyBtnText.textContent = 'Copy';

      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast(`Temporary password generated for ${currentResetTeacher.name}`, 'success');
      }
    } else {
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast(result.message || 'Failed to reset password.', 'error');
      } else {
        alert(result.message || 'Failed to reset password.');
      }
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
          <span>Generate Temp Password</span>
        `;
      }
    }
  } catch (err) {
    if (typeof APP !== 'undefined' && APP.toast) {
      APP.toast('Failed to reset password: ' + err.message, 'error');
    }
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        <span>Generate Temp Password</span>
      `;
    }
  }
}

function copyTempPassword() {
  const passEl = document.getElementById('reset-result-password');
  const copyBtnText = document.getElementById('copy-btn-text');
  if (!passEl) return;

  const textToCopy = passEl.textContent.trim();
  navigator.clipboard.writeText(textToCopy).then(() => {
    if (copyBtnText) copyBtnText.textContent = 'Copied!';
    if (typeof APP !== 'undefined' && APP.toast) {
      APP.toast('Temporary password copied to clipboard!', 'success');
    }
    setTimeout(() => {
      if (copyBtnText) copyBtnText.textContent = 'Copy';
    }, 2500);
  }).catch(() => {
    if (copyBtnText) copyBtnText.textContent = 'Copied!';
  });
}

// Backward-compatible resetPassword alias
function resetPassword(id, name, employeeId = '', email = '') {
  openResetPasswordModal(id, name, employeeId, email);
}

// Soft Delete (Deactivate) Modal Workflow
let currentDeactivateTeacher = null;

function openDeactivateTeacherModal(id, name, employeeId = '', email = '') {
  currentDeactivateTeacher = { id, name, employeeId, email };

  const idInput = document.getElementById('deactivate-teacher-id');
  const nameEl = document.getElementById('deactivate-teacher-name');
  const empEl = document.getElementById('deactivate-teacher-emp-id');
  const emailEl = document.getElementById('deactivate-teacher-email');

  if (idInput) idInput.value = id;
  if (nameEl) nameEl.textContent = name || 'Faculty Member';
  if (empEl) empEl.textContent = employeeId || 'N/A';
  if (emailEl) emailEl.textContent = email || 'N/A';

  const btn = document.getElementById('btn-confirm-deactivate-teacher');
  if (btn) {
    btn.disabled = false;
    btn.innerHTML = `
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
      <span>Disable Account</span>
    `;
  }

  const modal = document.getElementById('deactivateTeacherModal');
  if (modal) modal.classList.remove('hidden');
}

function closeDeactivateTeacherModal() {
  const modal = document.getElementById('deactivateTeacherModal');
  if (modal) modal.classList.add('hidden');
  currentDeactivateTeacher = null;
}

async function confirmDeactivateTeacher() {
  if (!currentDeactivateTeacher || !currentDeactivateTeacher.id) return;

  const btn = document.getElementById('btn-confirm-deactivate-teacher');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `
      <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
      <span>Disabling...</span>
    `;
  }

  try {
    const res = await fetch(window.url('api/teachers/delete'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id: currentDeactivateTeacher.id })
    });
    const result = await res.json();

    if (result.status === 'success') {
      closeDeactivateTeacherModal();
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast(result.message || 'Faculty account disabled successfully.', 'success');
      }
      fetchTeachers();
    } else {
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast(result.message || 'Failed to disable account.', 'error');
      } else {
        alert(result.message || 'Failed to disable account.');
      }
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = `
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
          <span>Disable Account</span>
        `;
      }
    }
  } catch (err) {
    if (typeof APP !== 'undefined' && APP.toast) {
      APP.toast('Failed to disable account: ' + err.message, 'error');
    }
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
        <span>Disable Account</span>
      `;
    }
  }
}

// Backward-compatible deactivateTeacher alias
function deactivateTeacher(id, name, employeeId = '', email = '') {
  openDeactivateTeacherModal(id, name, employeeId, email);
}

// Excel / CSV File Handlers
function openTeacherExcelModal() {
  document.getElementById('teacherExcelModal').classList.remove('hidden');
}
function closeTeacherExcelModal() {
  document.getElementById('teacherExcelModal').classList.add('hidden');
}
function triggerTeacherFileInput() {
  document.getElementById('teacher-excel-file-input').click();
}
function handleTeacherFileSelected(e) {
  const file = e.target.files[0];
  if (!file) return;
  document.getElementById('teacher-selected-file-name').textContent = file.name;
  document.getElementById('teacher-selected-file-pill').classList.remove('hidden');
  document.getElementById('import-summary-container').classList.add('hidden');
}
function clearTeacherFileInput() {
  document.getElementById('teacher-excel-file-input').value = '';
  document.getElementById('teacher-selected-file-pill').classList.add('hidden');
  document.getElementById('import-summary-container').classList.add('hidden');
}

async function processTeacherExcelImport() {
  const fileInput = document.getElementById('teacher-excel-file-input');
  if (!fileInput.files || fileInput.files.length === 0) {
    APP.toast('Please select a CSV or Excel file first.', 'warning');
    return;
  }

  const btn = document.getElementById('btn-process-import');
  btn.disabled = true;
  btn.innerHTML = `
    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
    <span>Ingesting Records...</span>
  `;

  const formData = new FormData();
  formData.append('file', fileInput.files[0]);

  try {
    const res = await fetch(window.url('api/teachers/import'), {
      method: 'POST',
      body: formData
    });
    const result = await res.json();

    if (result.status === 'success') {
      const summary = result.summary;
      document.getElementById('summary-total').textContent = summary.total;
      document.getElementById('summary-inserted').textContent = summary.inserted;
      document.getElementById('summary-skipped').textContent = summary.skipped;

      const summaryAlert = document.getElementById('import-summary-alert');
      if (summary.skipped === 0) {
        summaryAlert.className = 'p-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-900';
        document.getElementById('import-summary-title').textContent = `✓ Ingestion Complete: All ${summary.inserted} faculty records inserted!`;
      } else {
        summaryAlert.className = 'p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-900';
        document.getElementById('import-summary-title').textContent = `Partial Ingestion: ${summary.inserted} inserted, ${summary.skipped} skipped.`;
      }

      const errorsBox = document.getElementById('import-errors-box');
      const tableBody = document.getElementById('import-errors-table-body');
      const badgeCount = document.getElementById('skipped-badge-count');
      if (tableBody) tableBody.innerHTML = '';
      if (badgeCount) badgeCount.textContent = `${summary.skipped} Skipped`;

      if (summary.errors && summary.errors.length > 0) {
        summary.errors.forEach(err => {
          const tr = document.createElement('tr');
          tr.className = 'hover:bg-slate-50 transition border-b border-slate-100';

          let reasonBadge = '';
          const field = err.field || '';
          if (field === 'duplicate_email') {
            reasonBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">Duplicate Email</span>`;
          } else if (field === 'duplicate_id') {
            reasonBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-100 text-orange-900 border border-orange-300">Duplicate ID</span>`;
          } else {
            reasonBadge = `<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Validation Error</span>`;
          }

          tr.innerHTML = `
            <td class="py-2.5 px-3 font-mono text-[11px] font-bold text-slate-700">Row ${err.line}</td>
            <td class="py-2.5 px-3 font-mono text-[11px] font-bold text-slate-900">${err.id || 'N/A'}</td>
            <td class="py-2.5 px-3 text-[11px] font-mono text-slate-600">${err.email || 'N/A'}</td>
            <td class="py-2.5 px-3 text-[11px]">
              <div class="flex items-center gap-2 flex-wrap">
                ${reasonBadge}
                <span class="text-slate-600">${err.reason}</span>
              </div>
            </td>
          `;
          tableBody.appendChild(tr);
        });
        errorsBox.classList.remove('hidden');
      } else {
        errorsBox.classList.add('hidden');
      }

      document.getElementById('import-summary-container').classList.remove('hidden');
      APP.toast(`Batch imported: ${summary.inserted} faculty members added.`, 'success');
      fetchTeachers(1);
    } else {
      APP.toast(result.message || 'Import failed.', 'error');
    }
  } catch (err) {
    APP.toast('Network error during file ingestion.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = `
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
      <span>Start File Ingestion</span>
    `;
  }
}

// Download Blank Template
function downloadTeacherCsvTemplate() {
  const headers = 'employee_id,full_name,email,department,position,contact_number,date_hired\n';
  const sample = 't22012033,Dr. Elena D. Bautista,e.bautista@bestlink.edu.ph,College of Computer Studies,Department Head,0917-555-0101,2024-06-15\nt22012034,Prof. Nelson K. Cruz,n.cruz@bestlink.edu.ph,College of Business Administration,Assistant Professor,0918-555-0102,2025-01-10\n';
  const blob = new Blob([headers + sample], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', 'Teacher_Faculty_Master_Template.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  APP.toast('Template downloaded: Teacher_Faculty_Master_Template.csv', 'info');
}

// Export Live Faculty Master Roster (CSV or Excel)
function exportTeacherRoster(format) {
  format = format || 'csv';
  const searchInput = document.getElementById('search-teacher');
  const deptInput   = document.getElementById('filter-dept');
  const statusInput = document.getElementById('filter-status');

  const search = searchInput ? searchInput.value.trim() : '';
  const dept   = deptInput ? deptInput.value : '';
  const status = statusInput ? statusInput.value : '';

  closeExportDropdown();

  const exportUrl = `${window.url('api/teachers/export')}?format=${encodeURIComponent(format)}&search=${encodeURIComponent(search)}&department=${encodeURIComponent(dept)}&status=${encodeURIComponent(status)}`;
  const label = (format === 'excel' || format === 'xlsx') ? 'Excel (.xlsx)' : 'CSV (.csv)';
  APP.toast(`Generating ${label} export...`, 'info');
  window.location.href = exportUrl;
}

// Dropdown Menu Helpers
function toggleExportDropdown(e) {
  if (e) e.stopPropagation();
  const menu = document.getElementById('export-dropdown-menu');
  if (menu) menu.classList.toggle('hidden');
}

function closeExportDropdown() {
  const menu = document.getElementById('export-dropdown-menu');
  if (menu) menu.classList.add('hidden');
}

// Close dropdown on outside click
document.addEventListener('click', (e) => {
  const wrapper = document.getElementById('export-dropdown-wrapper');
  if (wrapper && !wrapper.contains(e.target)) {
    closeExportDropdown();
  }
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
