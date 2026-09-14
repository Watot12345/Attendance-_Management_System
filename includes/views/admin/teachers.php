<?php
$page_title = 'Faculty & Teacher Master Directory';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__) . '/partials/header.php';

// Initial server-side query
$teachers = [];
$metrics = ['total' => 0, 'active' => 0, 'inactive' => 0, 'depts' => 0];
try {
    $db = Database::getConnection();
    $stmt = $db->query("SELECT * FROM `teachers` ORDER BY id DESC LIMIT 50");
    $teachers = $stmt->fetchAll();

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
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Faculty Master Accounts</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Teacher &amp; Faculty Master Directory</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Manage teacher faculty accounts, create instructor credentials, or bulk import faculty records via CSV/Excel with audit logging.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <button type="button" onclick="downloadTeacherCsvTemplate()" class="px-3.5 py-2.5 rounded-xl bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-2xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>CSV Template</span>
          </button>

          <button type="button" onclick="openTeacherExcelModal()" class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 hover:shadow-lg transition flex items-center gap-2 group">
            <svg class="w-4 h-4 text-emerald-200 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span>Bulk Import CSV/Excel</span>
          </button>

          <button type="button" onclick="openManualTeacherModal()" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 hover:shadow-lg transition flex items-center gap-2 group">
            <svg class="w-4 h-4 text-blue-200 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            <span>+ Add Teacher Account</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics Grid -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
          </div>
          <div>
            <div id="stat-total-faculty" class="text-xl font-black text-slate-900"><?= number_format($metrics['total']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Total Teachers</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div id="stat-active-faculty" class="text-xl font-black text-emerald-600"><?= number_format($metrics['active']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Active Faculty</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
          </div>
          <div>
            <div id="stat-inactive-faculty" class="text-xl font-black text-rose-600"><?= number_format($metrics['inactive']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Inactive (Soft Deleted)</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
          </div>
          <div>
            <div id="stat-depts-faculty" class="text-xl font-black text-blue-600"><?= number_format($metrics['depts']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Academic Depts</div>
          </div>
        </div>
      </div>

      <!-- Filters & Live Search -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <!-- Search box -->
          <div class="relative w-full sm:w-80">
            <input type="text" id="search-teacher" placeholder="Search by name, employee ID, department..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition shadow-2xs" oninput="debounceTeacherSearch()">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>

          <!-- Department filter -->
          <select id="filter-dept" class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-semibold focus:outline-none focus:border-blue-500" onchange="fetchTeachers()">
            <option value="all">All Departments</option>
            <option value="College of Computer Studies">College of Computer Studies</option>
            <option value="College of Business Administration">College of Business Administration</option>
            <option value="College of Criminology">College of Criminology</option>
            <option value="College of Education">College of Education</option>
            <option value="General Academics">General Academics</option>
          </select>

          <!-- Status filter -->
          <select id="filter-status" class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-700 font-semibold focus:outline-none focus:border-blue-500" onchange="fetchTeachers()">
            <option value="all">All Status</option>
            <option value="active">Active Only</option>
            <option value="inactive">Inactive Only</option>
          </select>
        </div>

        <div class="text-xs text-slate-400 font-semibold" id="table-results-counter">
          Showing <?= count($teachers) ?> faculty record(s)
        </div>
      </div>

      <!-- Teachers Table Card -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
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
                <tr class="teacher-row hover:bg-slate-50/80 transition" data-id="<?= $t['id'] ?>">
                  <td class="py-3.5 px-4 font-mono font-bold text-blue-700">
                    <div class="flex items-center gap-1.5">
                      <span class="w-1.5 h-1.5 rounded-full <?= $isActive ? 'bg-emerald-500' : 'bg-slate-300' ?>"></span>
                      <span><?= htmlspecialchars($t['employee_id']) ?></span>
                    </div>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
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
                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Active</span>
                    <?php else: ?>
                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Inactive</span>
                    <?php endif; ?>
                  </td>
                  <td class="py-3.5 px-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                      <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" onclick='openEditTeacherModal(<?= json_encode($t) ?>)' title="Edit Account">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                      </button>
                      <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition" onclick="resetPassword(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['full_name'])) ?>')" title="Reset Password">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                      </button>
                      <?php if ($isActive): ?>
                        <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition" onclick="deactivateTeacher(<?= $t['id'] ?>, '<?= htmlspecialchars(addslashes($t['full_name'])) ?>')" title="Deactivate (Soft Delete)">
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
      </div>
    </main>
  </div>
</div>

<!-- ========================================================================= -->
<!-- MODAL 1: CREATE TEACHER MANUALLY -->
<!-- ========================================================================= -->
<div id="manualTeacherModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-950/60 backdrop-blur-xs flex items-center justify-center p-4">
  <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <div class="px-6 py-5 bg-gradient-to-r from-blue-700 to-indigo-800 text-white flex items-center justify-between">
      <div>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Manual Provisioning</span>
        <h3 class="text-lg font-black tracking-tight mt-1">Create Teacher Faculty Account</h3>
      </div>
      <button type="button" onclick="closeManualTeacherModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="manual-teacher-form" onsubmit="handleManualTeacherSubmit(event)" class="p-6 space-y-4">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Employee ID <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="employee_id" required placeholder="e.g. EMP-2026-0101" class="form-input text-xs font-mono">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Full Name <span class="text-rose-500">*</span>
          </label>
          <input type="text" name="full_name" required placeholder="e.g. Maria C. Santos" class="form-input text-xs">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Email Address <span class="text-rose-500">*</span>
          </label>
          <input type="email" name="email" required placeholder="m.santos@bestlink.edu.ph" class="form-input text-xs">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">
            Academic Department
          </label>
          <select name="department" class="form-input form-select text-xs">
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
          <input type="text" name="position" value="Instructor" class="form-input text-xs">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Number</label>
          <input type="text" name="contact_number" placeholder="0917-123-4567" class="form-input text-xs">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Date Hired</label>
          <input type="date" name="date_hired" value="<?= date('Y-m-d') ?>" class="form-input text-xs">
        </div>
      </div>

      <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between">
        <div>
          <div class="font-bold text-slate-800">Default Password</div>
          <div class="text-[11px] text-slate-500">Instructor can change after initial login.</div>
        </div>
        <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200 font-mono font-bold text-blue-700 text-xs">Teacher@123</span>
      </div>

      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closeManualTeacherModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition">Cancel</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-2">
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
  <div class="relative w-full max-w-xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <div class="px-6 py-5 bg-gradient-to-r from-slate-800 to-slate-900 text-white flex items-center justify-between">
      <div>
        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Faculty Master</span>
        <h3 class="text-lg font-black tracking-tight mt-1">Edit Teacher Account</h3>
      </div>
      <button type="button" onclick="closeEditTeacherModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <form id="edit-teacher-form" onsubmit="handleEditTeacherSubmit(event)" class="p-6 space-y-4">
      <input type="hidden" id="edit-id" name="id">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Employee ID</label>
          <input type="text" id="edit-employee-id" disabled class="form-input text-xs font-mono bg-slate-100 cursor-not-allowed">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Full Name <span class="text-rose-500">*</span></label>
          <input type="text" id="edit-full-name" name="full_name" required class="form-input text-xs">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Email Address <span class="text-rose-500">*</span></label>
          <input type="email" id="edit-email" name="email" required class="form-input text-xs">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Department</label>
          <input type="text" id="edit-department" name="department" class="form-input text-xs">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Position</label>
          <input type="text" id="edit-position" name="position" class="form-input text-xs">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Contact Number</label>
          <input type="text" id="edit-contact" name="contact_number" class="form-input text-xs">
        </div>
        <div>
          <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider mb-1">Status</label>
          <select id="edit-status" name="status" class="form-input form-select text-xs">
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-3">
        <button type="button" onclick="closeEditTeacherModal()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold transition">Cancel</button>
        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 transition flex items-center gap-2">
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
  <div class="relative w-full max-w-3xl bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden animate-in fade-in zoom-in duration-150">
    <div class="px-6 py-5 bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-800 text-white flex items-center justify-between">
      <div>
        <div class="flex items-center gap-2">
          <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-white/20 uppercase tracking-wider">Faculty Batch Importer</span>
          <span class="text-xs text-emerald-200 font-medium">CSV / Excel Ingestion</span>
        </div>
        <h3 class="text-lg font-black tracking-tight mt-1">Import Faculty Master Records</h3>
      </div>
      <button type="button" onclick="closeTeacherExcelModal()" class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center text-white transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="p-6 space-y-5">
      <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 flex items-start gap-3.5">
        <div class="w-8 h-8 rounded-xl bg-emerald-600 text-white flex items-center justify-center shrink-0">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="text-xs text-emerald-950">
          <div class="font-bold mb-0.5">Expected Column Headers in First Row:</div>
          <p class="text-emerald-800 font-mono text-[11px] leading-relaxed">
            employee_id, full_name, email, department, position, contact_number, date_hired
          </p>
        </div>
      </div>

      <!-- File Dropzone -->
      <div id="teacher-excel-dropzone" class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-7 text-center transition cursor-pointer bg-slate-50/70 hover:bg-emerald-50/30 flex flex-col items-center justify-center gap-2 group" onclick="triggerTeacherFileInput()">
        <input type="file" id="teacher-excel-file-input" accept=".csv,.xlsx,.txt" class="hidden" onchange="handleTeacherFileSelected(event)">
        <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center group-hover:scale-110 transition shadow-xs">
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
        <button type="button" onclick="clearTeacherFileInput()" class="text-xs text-rose-600 hover:underline font-semibold">Remove</button>
      </div>

      <!-- Import Summary / Results Area -->
      <div id="import-summary-container" class="hidden space-y-3">
        <div class="p-4 rounded-xl border" id="import-summary-alert">
          <h4 class="text-xs font-bold" id="import-summary-title">Import Finished</h4>
          <div class="grid grid-cols-3 gap-2 mt-2 text-center text-xs">
            <div class="p-2 rounded-lg bg-slate-100">Total: <strong id="summary-total">0</strong></div>
            <div class="p-2 rounded-lg bg-emerald-100 text-emerald-800">Inserted: <strong id="summary-inserted">0</strong></div>
            <div class="p-2 rounded-lg bg-amber-100 text-amber-800">Skipped: <strong id="summary-skipped">0</strong></div>
          </div>
        </div>

        <div id="import-errors-box" class="hidden max-h-48 overflow-y-auto border border-rose-200 rounded-xl p-3 bg-rose-50 text-xs">
          <div class="font-bold text-rose-800 mb-1">Skipped Rows / Validation Errors:</div>
          <ul id="import-errors-list" class="list-disc pl-4 space-y-1 text-rose-700 text-[11px]"></ul>
        </div>
      </div>

      <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
        <button type="button" onclick="downloadTeacherCsvTemplate()" class="text-xs font-bold text-emerald-700 hover:underline flex items-center gap-1.5">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          Download Blank CSV Template
        </button>
        <div class="flex items-center gap-2">
          <button type="button" onclick="closeTeacherExcelModal()" class="btn btn-secondary text-xs">Close</button>
          <button type="button" id="btn-process-import" onclick="processTeacherExcelImport()" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-md shadow-emerald-600/20 transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Start File Ingestion</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
let searchTimeout = null;

function debounceTeacherSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchTeachers();
  }, 300);
}

// Fetch live teachers from API
async function fetchTeachers() {
  const search = document.getElementById('search-teacher').value.trim();
  const dept   = document.getElementById('filter-dept').value;
  const status = document.getElementById('filter-status').value;

  const url = `${window.url('api/teachers')}?search=${encodeURIComponent(search)}&department=${encodeURIComponent(dept)}&status=${encodeURIComponent(status)}`;

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
      document.getElementById('table-results-counter').textContent = `Showing ${data.data.length} faculty record(s)`;
    }
  } catch (err) {
    console.error(err);
  }
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
      <tr class="teacher-row hover:bg-slate-50/80 transition" data-id="${t.id}">
        <td class="py-3.5 px-4 font-mono font-bold text-blue-700">
          <div class="flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full ${isActive ? 'bg-emerald-500' : 'bg-slate-300'}"></span>
            <span>${escapeHtml(t.employee_id)}</span>
          </div>
        </td>
        <td class="py-3.5 px-4">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0">
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
            ? '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">Active</span>'
            : '<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Inactive</span>'
          }
        </td>
        <td class="py-3.5 px-4 text-right">
          <div class="flex items-center justify-end gap-1.5">
            <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition" onclick='openEditTeacherModal(${JSON.stringify(t)})' title="Edit Account">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition" onclick="resetPassword(${t.id}, '${escapeHtml(t.full_name)}')" title="Reset Password">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </button>
            ${isActive ? `
              <button type="button" class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition" onclick="deactivateTeacher(${t.id}, '${escapeHtml(t.full_name)}')" title="Deactivate (Soft Delete)">
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
      fetchTeachers();
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

// Reset Password
async function resetPassword(id, name) {
  if (!confirm(`Generate temporary password for ${name}? This will be logged to notifications.`)) return;

  try {
    const res = await fetch(window.url('api/teachers/reset-password'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const result = await res.json();
    if (result.status === 'success') {
      alert(`Temporary password for ${name}:\n\n${result.temp_password}\n\nPlease share this credential with the instructor.`);
      APP.toast(`Temp password generated: ${result.temp_password}`, 'success');
    } else {
      APP.toast(result.message, 'error');
    }
  } catch (err) {
    APP.toast('Failed to reset password.', 'error');
  }
}

// Soft Delete (Deactivate)
async function deactivateTeacher(id, name) {
  if (!confirm(`Are you sure you want to deactivate faculty member ${name}? This performs a soft-delete.`)) return;

  try {
    const res = await fetch(window.url('api/teachers/delete'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ id })
    });
    const result = await res.json();
    if (result.status === 'success') {
      APP.toast(result.message, 'success');
      fetchTeachers();
    } else {
      APP.toast(result.message, 'error');
    }
  } catch (err) {
    APP.toast('Error deactivating faculty account.', 'error');
  }
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
      const errorsList = document.getElementById('import-errors-list');
      errorsList.innerHTML = '';
      if (summary.errors && summary.errors.length > 0) {
        summary.errors.forEach(err => {
          const li = document.createElement('li');
          li.textContent = `Row ${err.line} [${err.id}]: ${err.reason}`;
          errorsList.appendChild(li);
        });
        errorsBox.classList.remove('hidden');
      } else {
        errorsBox.classList.add('hidden');
      }

      document.getElementById('import-summary-container').classList.remove('hidden');
      APP.toast(`Batch imported: ${summary.inserted} faculty members added.`, 'success');
      fetchTeachers();
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
  const sample = 'EMP-2026-0001,Dr. Elena D. Bautista,e.bautista@bestlink.edu.ph,College of Computer Studies,Department Head,0917-555-0101,2024-06-15\nEMP-2026-0002,Prof. Nelson K. Cruz,n.cruz@bestlink.edu.ph,College of Business Administration,Assistant Professor,0918-555-0102,2025-01-10\n';
  const blob = new Blob([headers + sample], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.setAttribute('download', 'Teacher_Faculty_Master_Template.csv');
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  APP.toast('Template downloaded: Teacher_Faculty_Master_Template.csv', 'info');
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
