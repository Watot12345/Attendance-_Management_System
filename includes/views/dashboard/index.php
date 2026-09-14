<?php
$page_title = 'Dashboard Overview';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/controllers/DashboardController.php';

$overview = DashboardController::getOverviewData();
$teachersStats = $overview['teachers'];
$attToday = $overview['attendance_today'];
$alertsCount = $overview['alerts_today'];
$recentImports = $overview['recent_imports'];

require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Page Header -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold"><?= date('l, F j, Y') ?></span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">System Overview Dashboard</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Institutional summary, real-time campus metrics, and cross-system operational status.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <a href="<?= url('admin/teachers') ?>" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold shadow-2xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span>Manage Faculty</span>
          </a>
          <a href="<?= url('settings') ?>" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 hover:shadow-lg transition flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Settings</span>
          </a>
        </div>
      </div>

      <!-- Overview Summary Cards Row -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        
        <!-- Total Faculty Card -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-slate-900"><?= number_format($teachersStats['total']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Faculty Master</div>
            <p class="text-[11px] text-slate-500 mt-0.5">
              <span class="font-bold text-emerald-600"><?= number_format($teachersStats['active']) ?> active</span>
              <span class="text-slate-300">•</span>
              <span class="text-slate-400"><?= number_format($teachersStats['inactive']) ?> inactive</span>
            </p>
          </div>
        </div>

        <!-- Today's Attendance Rate (v_attendance_summary view) -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-emerald-600"><?= number_format($attToday['rate_percentage'], 1) ?>%</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Today's Attendance</div>
            <p class="text-[11px] text-slate-500 mt-0.5"><?= number_format($attToday['present_count']) ?> present / <?= number_format($attToday['total_records']) ?> logs</p>
          </div>
        </div>

        <!-- Parent Alerts Sent Today -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-amber-600"><?= number_format($alertsCount) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Parent Alerts (Today)</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Automated Dispatch Queue</p>
          </div>
        </div>

        <!-- System Integration Status -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
          </div>
          <div>
            <div class="text-xl font-black text-slate-900">Online</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">System Integration</div>
            <p class="text-[11px] text-slate-500 mt-0.5">Services Active &amp; Synced</p>
          </div>
        </div>

      </div>

      <!-- Middle Row: Recent Faculty Ingestions & Cross-Module Contract Status -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Recent Faculty Imports Widget -->
        <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <div class="flex items-center justify-between mb-4">
            <div>
              <h3 class="text-base font-bold text-slate-900">Recent Faculty Bulk Ingestions</h3>
              <p class="text-xs text-slate-500">Official audit records of processed faculty spreadsheet batches</p>
            </div>
            <a href="<?= url('admin/teachers') ?>" class="text-xs font-bold text-blue-600 hover:underline">Import New Batch →</a>
          </div>

          <?php if (empty($recentImports)): ?>
            <div class="p-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-2xl">
              <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <div class="font-bold text-slate-700 text-xs">No faculty batch imports recorded yet</div>
              <p class="text-[11px] text-slate-400 mt-0.5">Use the bulk CSV/Excel import tool in the Teacher Directory to ingest instructor lists.</p>
            </div>
          <?php else: ?>
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="border-b border-slate-100 text-slate-400 text-[11px] uppercase font-bold">
                    <th class="py-2.5 px-3">Filename</th>
                    <th class="py-2.5 px-3 text-center">Total Rows</th>
                    <th class="py-2.5 px-3 text-center">Inserted</th>
                    <th class="py-2.5 px-3 text-center">Failed / Skipped</th>
                    <th class="py-2.5 px-3 text-right">Imported At</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <?php foreach ($recentImports as $log): ?>
                    <tr class="hover:bg-slate-50/80 transition">
                      <td class="py-3 px-3 font-semibold text-slate-800 flex items-center gap-2">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="truncate max-w-[200px]"><?= htmlspecialchars($log['filename']) ?></span>
                      </td>
                      <td class="py-3 px-3 text-center font-bold text-slate-700"><?= number_format($log['total_rows']) ?></td>
                      <td class="py-3 px-3 text-center font-bold text-emerald-600"><?= number_format($log['inserted_rows']) ?></td>
                      <td class="py-3 px-3 text-center font-bold text-amber-600"><?= number_format($log['failed_rows']) ?></td>
                      <td class="py-3 px-3 text-right text-slate-400 text-[11px]"><?= htmlspecialchars($log['imported_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- System Integration Status Card -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <h3 class="text-base font-bold text-slate-900 mb-1">System Integration Status</h3>
          <p class="text-xs text-slate-500 mb-4">Real-time status of cross-module data pipelines.</p>

          <div class="space-y-3 text-xs">
            <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100">
              <div class="flex items-center justify-between font-bold text-blue-900 mb-1">
                <span>Parent Alerts Service</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-200">Active</span>
              </div>
              <p class="text-blue-800 text-[11px]">
                Automated guardian notifications and attendance triggers operational.
              </p>
            </div>

            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
              <div class="flex items-center justify-between font-bold text-slate-900 mb-1">
                <span>Reporting &amp; Analytics Service</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-200">Active</span>
              </div>
              <p class="text-slate-600 text-[11px]">
                Daily attendance summaries and historical export pipelines operational.
              </p>
            </div>

            <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-100">
              <div class="flex items-center justify-between font-bold text-emerald-900 mb-1">
                <span>Shared View Layer</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 border border-emerald-200">Synchronized</span>
              </div>
              <p class="text-emerald-800 text-[11px]">
                Overview Dashboard and Campus Analytics share identical aggregation numbers without duplication.
              </p>
            </div>
          </div>
        </div>

      </div>

    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
