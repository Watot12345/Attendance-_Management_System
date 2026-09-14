<?php
$page_title = 'Institutional Admin Dashboard';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/controllers/DashboardController.php';

$overview = DashboardController::getOverviewData();
$teachersStats = $overview['teachers'];
$attToday = $overview['attendance_today'];
$recentImports = $overview['recent_imports'];

// Dynamic student stats
$studentStats = ['total' => 0, 'active' => 0, 'inactive' => 0];
try {
    $db = Database::getConnection();
    $sStmt = $db->query("
        SELECT 
            COUNT(*) AS total,
            SUM(status = 'active') AS active_count,
            SUM(status != 'active') AS inactive_count
        FROM `users` 
        WHERE `role` = 'student'
    ");
    $sRes = $sStmt->fetch();
    if ($sRes) {
        $studentStats = [
            'total'    => (int)($sRes['total'] ?? 0),
            'active'   => (int)($sRes['active_count'] ?? 0),
            'inactive' => (int)($sRes['inactive_count'] ?? 0)
        ];
    }
} catch (Exception $e) {}

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
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Live System Oversight</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Attendance Administration Dashboard</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">Master record governance, real-time campus attendance rates, and compliance oversight.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0">
          <a href="<?php echo url('admin/teachers'); ?>" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-2xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span>Faculty Master</span>
          </a>
          <a href="<?php echo url('admin/reports'); ?>" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 hover:shadow-lg transition flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Generate Campus Report</span>
          </a>
        </div>
      </div>

      <!-- Dynamic Institutional Metrics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Student Master Card -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-slate-900"><?= number_format($studentStats['total']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Student Master</div>
            <p class="text-[11px] text-slate-500 mt-0.5"><?= number_format($studentStats['active']) ?> Active • <?= number_format($studentStats['inactive']) ?> Inactive</p>
          </div>
        </div>

        <!-- Faculty Master Card -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-slate-900"><?= number_format($teachersStats['total']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Faculty Master</div>
            <p class="text-[11px] text-slate-500 mt-0.5"><?= number_format($teachersStats['active']) ?> Active • <?= number_format($teachersStats['inactive']) ?> Inactive</p>
          </div>
        </div>

        <!-- Parent Alerts Today Card -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-amber-600"><?= number_format($overview['alerts_today']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Parent Alerts (Today)</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Automated Dispatch Queue</p>
          </div>
        </div>

        <!-- Today's Campus Rate (v_attendance_summary) -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-emerald-600"><?= number_format($attToday['rate_percentage'], 1) ?>%</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Today's Campus Rate</div>
            <p class="text-[11px] text-slate-500 mt-0.5"><?= number_format($attToday['present_count']) ?> present / <?= number_format($attToday['total_records']) ?> records</p>
          </div>
        </div>
      </div>

      <!-- Two Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left 2 Cols: Master Records Governance & Recent Ingestions -->
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
                <h3 class="font-bold text-slate-800 text-sm mb-1">Teacher Faculty Master</h3>
                <p class="text-xs text-slate-500">Faculty directory, employee codes, and bulk CSV/Excel imports.</p>
              </a>
            </div>
          </div>

          <!-- Recent Ingestion Audit Trail -->
          <div class="bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-bold text-slate-800 text-base">Recent Faculty Bulk Ingestions</h2>
              <a href="<?= url('admin/teachers') ?>" class="text-xs font-semibold text-purple-600 hover:underline">Import Faculty Batch →</a>
            </div>

            <?php if (empty($recentImports)): ?>
              <div class="p-6 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
                <div class="font-bold text-slate-600 text-xs">No faculty batch imports in database</div>
                <p class="text-[11px] text-slate-400 mt-1">Uploaded spreadsheets will be logged to <code>faculty_import_logs</code>.</p>
              </div>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr class="border-b border-slate-100 text-slate-400 text-[11px] uppercase font-bold">
                      <th class="py-2.5 px-3">Filename</th>
                      <th class="py-2.5 px-3 text-center">Total</th>
                      <th class="py-2.5 px-3 text-center">Inserted</th>
                      <th class="py-2.5 px-3 text-center">Failed</th>
                      <th class="py-2.5 px-3 text-right">Date</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recentImports as $log): ?>
                      <tr class="hover:bg-slate-50 transition">
                        <td class="py-2.5 px-3 font-semibold text-slate-800"><?= htmlspecialchars($log['filename']) ?></td>
                        <td class="py-2.5 px-3 text-center"><?= number_format($log['total_rows']) ?></td>
                        <td class="py-2.5 px-3 text-center font-bold text-emerald-600"><?= number_format($log['inserted_rows']) ?></td>
                        <td class="py-2.5 px-3 text-center font-bold text-amber-600"><?= number_format($log['failed_rows']) ?></td>
                        <td class="py-2.5 px-3 text-right text-slate-400 text-[11px]"><?= htmlspecialchars($log['imported_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Right Col: Bulk Import & System Rules -->
        <div class="space-y-6">
          <div class="bg-gradient-to-br from-slate-900 to-slate-950 rounded-2xl p-6 text-white shadow-md border border-slate-800">
            <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 flex items-center justify-center mb-3">
              <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            </div>
            <h3 class="font-bold text-base mb-1">Bulk Faculty CSV/Excel Import</h3>
            <p class="text-xs text-slate-300 leading-relaxed mb-4">
              Import faculty master records in bulk with automated header validation and duplicate prevention.
            </p>
            <a href="<?php echo url('admin/teachers'); ?>" class="block w-full text-center py-2.5 px-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-600/20 transition">
              Open Faculty Importer
            </a>
          </div>

          <!-- System Integrity Guardrails -->
          <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <h3 class="font-bold text-slate-800 text-sm mb-3">System Operating Status</h3>
            <ul class="text-xs text-slate-600 space-y-2.5">
              <li class="flex items-start gap-2">
                <span class="text-emerald-600 font-bold">✓</span>
                <span><strong>Parent Alerts:</strong> Automated guardian notification system active.</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-emerald-600 font-bold">✓</span>
                <span><strong>Attendance Metrics:</strong> Real-time attendance rate calculation synchronized.</span>
              </li>
              <li class="flex items-start gap-2">
                <span class="text-emerald-600 font-bold">✓</span>
                <span><strong>Audit Trail:</strong> Full batch import logging and verification enabled.</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
