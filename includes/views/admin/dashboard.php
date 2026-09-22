<?php
$page_title = 'Institutional Admin Dashboard';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/controllers/DashboardController.php';

$overview = DashboardController::getOverviewData();
$studentsStats = $overview['students'];
$teachersStats = $overview['teachers'];
$attToday = $overview['attendance_today'];
$attOverall = $overview['attendance_overall'];
$excuseSlips = $overview['excuse_slips'];
$academic = $overview['academic'];
$recentAttendance = $overview['recent_attendance'];
$recentAuditLogs = $overview['recent_audit_logs'];
$recentImports = $overview['recent_imports'];

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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#1e3b8a]/10 text-[#1e3b8a] border border-[#1e3b8a]/20">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-medium"><?= date('l, F j, Y') ?></span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Institutional Administration Dashboard</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Live institutional governance, real-time campus attendance rates, and compliance oversight.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-2.5 shrink-0">
          <a href="<?= url('admin/teachers') ?>" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            <span>Faculty Master</span>
          </a>
          <a href="<?= url('admin/students') ?>" class="px-3.5 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Manage Students</span>
          </a>
          <a href="<?= url('admin/reports') ?>" class="px-4 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#1e3b8a]/90 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Campus Reports</span>
          </a>
        </div>
      </div>

      <!-- Dynamic Institutional Metrics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        
        <!-- 1. Student Master Card -->
        <a href="<?= url('admin/students') ?>" class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5 hover:border-[#1e3b8a]/40 transition group">
          <div class="w-11 h-11 rounded-xl bg-[#1e3b8a]/10 border border-[#1e3b8a]/15 flex items-center justify-center text-[#1e3b8a] group-hover:scale-105 transition shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
          </div>
          <div>
            <div class="text-xl font-bold text-slate-900"><?= number_format($studentsStats['total']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Student Master</div>
            <p class="text-[11px] text-slate-500 mt-0.5">
              <span class="font-semibold text-emerald-600"><?= number_format($studentsStats['active']) ?> active</span>
              <span class="text-slate-300">•</span>
              <span class="text-slate-400"><?= number_format($studentsStats['inactive']) ?> inactive</span>
            </p>
          </div>
        </a>

        <!-- 2. Faculty Master Card -->
        <a href="<?= url('admin/teachers') ?>" class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5 hover:border-[#1e3b8a]/40 transition group">
          <div class="w-11 h-11 rounded-xl bg-sky-50 border border-sky-100 flex items-center justify-center text-sky-700 group-hover:scale-105 transition shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
          </div>
          <div>
            <div class="text-xl font-bold text-slate-900"><?= number_format($teachersStats['total']) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Faculty Master</div>
            <p class="text-[11px] text-slate-500 mt-0.5">
              <span class="font-semibold text-emerald-600"><?= number_format($teachersStats['active']) ?> active</span>
              <span class="text-slate-300">•</span>
              <span class="text-slate-400"><?= number_format($teachersStats['inactive']) ?> inactive</span>
            </p>
          </div>
        </a>

        <!-- 3. Campus Attendance Rate Card -->
        <a href="<?= url('dashboard/analytics') ?>" class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5 hover:border-emerald-400 transition group">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 group-hover:scale-105 transition shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <div class="text-xl font-bold text-emerald-600"><?= number_format($attToday['rate_percentage'], 1) ?>%</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider"><?= htmlspecialchars($attToday['session_label']) ?></div>
            <p class="text-[11px] text-slate-500 mt-0.5">
              <?= number_format($attToday['present_count']) ?> present
              <?php if (!empty($attToday['tardy_count'])): ?> • <?= number_format($attToday['tardy_count']) ?> late<?php endif; ?>
              / <?= number_format($attToday['total_records']) ?> logs
            </p>
          </div>
        </a>

        <!-- 4. Excuse Slips Compliance Card -->
        <a href="<?= url('dashboard/excuse-slips') ?>" class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5 hover:border-amber-400 transition group">
          <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 group-hover:scale-105 transition shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <div>
            <div class="text-xl font-bold <?= $excuseSlips['pending'] > 0 ? 'text-amber-600' : 'text-slate-900' ?>">
              <?= number_format($excuseSlips['pending']) ?> Pending
            </div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Excuse Slips</div>
            <p class="text-[11px] text-slate-500 mt-0.5">
              <?= number_format($excuseSlips['approved']) ?> approved • <?= number_format($excuseSlips['total']) ?> total
            </p>
          </div>
        </a>

      </div>

      <!-- Main Overview Grid: Left (Recent Attendance & Classes) | Right (Audit Trail & Shortcuts) -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        
        <!-- Left 2 Cols: Live Attendance Feed & Class Breakdown -->
        <div class="lg:col-span-2 space-y-6">

          <!-- Live Campus Attendance Activity Feed -->
          <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span>Recent Campus Attendance Activity</span>
                </h3>
                <p class="text-xs text-slate-500">Live student check-in records logged in database</p>
              </div>
              <a href="<?= url('dashboard/analytics') ?>" class="text-xs font-semibold text-[#1e3b8a] hover:underline">Full Analytics →</a>
            </div>

            <?php if (empty($recentAttendance)): ?>
              <div class="p-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-2xl">
                <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="font-bold text-slate-700 text-xs">No attendance logs recorded yet</div>
                <p class="text-[11px] text-slate-400 mt-0.5">Student check-ins via QR scanner or faculty roll-call will appear here live.</p>
              </div>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                  <thead>
                    <tr class="border-b border-slate-100 text-slate-400 text-[10px] uppercase font-bold tracking-wider">
                      <th class="py-2.5 px-3">Student</th>
                      <th class="py-2.5 px-3">Section / Course</th>
                      <th class="py-2.5 px-3">Instructor</th>
                      <th class="py-2.5 px-3 text-center">Session Date &amp; Time</th>
                      <th class="py-2.5 px-3 text-right">Status</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100">
                    <?php foreach ($recentAttendance as $att): ?>
                      <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="py-3 px-3">
                          <div class="font-bold text-slate-900"><?= htmlspecialchars($att['student_name']) ?></div>
                          <div class="font-mono text-[10px] text-slate-400"><?= htmlspecialchars($att['student_number']) ?></div>
                        </td>
                        <td class="py-3 px-3">
                          <span class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 font-semibold text-[10px]"><?= htmlspecialchars($att['section']) ?></span>
                          <div class="text-[10px] text-slate-500 mt-0.5 truncate max-w-[180px]"><?= htmlspecialchars($att['subject']) ?></div>
                        </td>
                        <td class="py-3 px-3 text-slate-600 font-medium">
                          <?= htmlspecialchars($att['teacher_name'] ?: 'Instructor') ?>
                        </td>
                        <td class="py-3 px-3 text-center text-slate-500 font-mono text-[11px]">
                          <div><?= date('M j, Y', strtotime($att['date'])) ?></div>
                          <div class="text-[10px] text-slate-400"><?= date('h:i A', strtotime($att['time'])) ?></div>
                        </td>
                        <td class="py-3 px-3 text-right">
                          <?php if ($att['status'] === 'present'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Present</span>
                          <?php elseif ($att['status'] === 'tardy'): ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">Late</span>
                          <?php else: ?>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60">Absent</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Academic Sections Breakdown -->
          <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-base font-bold text-slate-900">Academic Sections Roster Summary</h3>
                <p class="text-xs text-slate-500">Official class sections and enrolled student density</p>
              </div>
              <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">
                <?= number_format($academic['total_sections']) ?> Sections • <?= number_format($academic['total_students']) ?> Enrollments
              </span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <?php foreach ($academic['sections'] as $sec): ?>
                <div class="p-4 rounded-xl border border-slate-200/80 hover:border-slate-300 bg-slate-50/50 transition flex items-center justify-between">
                  <div>
                    <div class="flex items-center gap-2 mb-1">
                      <span class="px-2 py-0.5 rounded-md bg-[#1e3b8a]/10 text-[#1e3b8a] font-mono font-bold text-[10px]">
                        Section <?= htmlspecialchars($sec['section']) ?>
                      </span>
                      <span class="text-[10px] text-slate-400 font-bold"><?= htmlspecialchars($sec['course_code']) ?></span>
                    </div>
                    <div class="text-xs font-bold text-slate-800"><?= htmlspecialchars($sec['course_title']) ?></div>
                  </div>
                  <div class="text-right">
                    <div class="text-lg font-black text-slate-900"><?= (int)$sec['student_count'] ?></div>
                    <div class="text-[10px] text-slate-400 uppercase font-semibold">Enrolled</div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

        </div>

        <!-- Right Col: System Activity & Administrative Actions -->
        <div class="space-y-6">

          <!-- Real System Activity & Audit Trail -->
          <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-base font-bold text-slate-900">Recent System Activity</h3>
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60">Live Audit</span>
            </div>

            <?php if (empty($recentAuditLogs)): ?>
              <p class="text-xs text-slate-400 italic">No audit records logged yet.</p>
            <?php else: ?>
              <div class="space-y-3">
                <?php foreach ($recentAuditLogs as $log): ?>
                  <div class="p-3 rounded-xl bg-slate-50/60 border border-slate-100 text-xs">
                    <div class="flex items-center justify-between font-semibold text-slate-800 mb-1">
                      <span class="capitalize flex items-center gap-1.5 font-bold">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#1e3b8a]"></span>
                        <?= htmlspecialchars($log['action']) ?> Activity
                      </span>
                      <span class="text-[10px] text-slate-400 font-normal">
                        <?= date('M j, h:i A', strtotime($log['created_at'])) ?>
                      </span>
                    </div>
                    <p class="text-slate-600 text-[11px] leading-relaxed line-clamp-2">
                      <?= htmlspecialchars($log['description']) ?>
                    </p>
                    <?php if (!empty($log['actor_name'])): ?>
                      <div class="text-[10px] text-slate-400 mt-1">
                        By: <span class="font-medium text-slate-600"><?= htmlspecialchars($log['actor_name']) ?></span>
                        (<?= htmlspecialchars(ucfirst($log['actor_role'] ?: 'system')) ?>)
                      </div>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Official Governance Shortcuts -->
          <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
            <h3 class="text-base font-bold text-slate-900 mb-3">Governance Quick Actions</h3>
            <div class="grid grid-cols-1 gap-2 text-xs">
              <a href="<?= url('admin/students') ?>" class="p-3 rounded-xl border border-slate-200/80 hover:border-[#1e3b8a]/40 hover:bg-slate-50/60 transition flex items-center justify-between group">
                <span class="font-semibold text-slate-700 group-hover:text-[#1e3b8a]">Student Directory &amp; IDs</span>
                <span class="text-slate-400 group-hover:text-[#1e3b8a]">→</span>
              </a>
              <a href="<?= url('admin/teachers') ?>" class="p-3 rounded-xl border border-slate-200/80 hover:border-[#1e3b8a]/40 hover:bg-slate-50/60 transition flex items-center justify-between group">
                <span class="font-semibold text-slate-700 group-hover:text-[#1e3b8a]">Faculty Master &amp; CSV Import</span>
                <span class="text-slate-400 group-hover:text-[#1e3b8a]">→</span>
              </a>
              <a href="<?= url('dashboard/excuse-slips') ?>" class="p-3 rounded-xl border border-slate-200/80 hover:border-[#1e3b8a]/40 hover:bg-slate-50/60 transition flex items-center justify-between group">
                <span class="font-semibold text-slate-700 group-hover:text-[#1e3b8a]">Excuse Slips &amp; Clearances</span>
                <span class="text-slate-400 group-hover:text-[#1e3b8a]">→</span>
              </a>
              <a href="<?= url('teacher/awards') ?>" class="p-3 rounded-xl border border-slate-200/80 hover:border-[#1e3b8a]/40 hover:bg-slate-50/60 transition flex items-center justify-between group">
                <span class="font-semibold text-slate-700 group-hover:text-[#1e3b8a]">Perfect Attendance Awards Tool</span>
                <span class="text-slate-400 group-hover:text-[#1e3b8a]">→</span>
              </a>
              <a href="<?= url('admin/reports') ?>" class="p-3 rounded-xl border border-slate-200/80 hover:border-[#1e3b8a]/40 hover:bg-slate-50/60 transition flex items-center justify-between group">
                <span class="font-semibold text-slate-700 group-hover:text-[#1e3b8a]">Export Institutional CSV Reports</span>
                <span class="text-slate-400 group-hover:text-[#1e3b8a]">→</span>
              </a>
            </div>
          </div>

        </div>

      </div>

    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
