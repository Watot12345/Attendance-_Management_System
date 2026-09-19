<?php
$page_title = 'Teacher Dashboard';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/controllers/TeacherController.php';

$teacherId = TeacherController::resolveCurrentTeacherId();
$overview = TeacherController::getTeacherDashboardOverview($teacherId);

$teacher = $overview['teacher'];
$classesMetric = $overview['classes_metric'];
$attendanceMetric = $overview['today_attendance'];
$activeSession = $overview['active_session'];
$atRiskCount = $overview['at_risk_count'];
$pendingExcusesCount = $overview['pending_excuses_count'] ?? 0;
$schedule = $overview['schedule'];
$isTodaySchedule = $overview['is_today_schedule'] ?? true;
$classesOverview = $overview['classes_overview'];
$liveFeed = $overview['live_feed'];
$recentExcuses = $overview['recent_excuses'] ?? [];
$currentDay = $overview['current_day'];

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
          <div class="flex items-center gap-2 mb-1">
            <span class="badge badge-present">Faculty Portal</span>
            <span class="text-xs text-slate-500">Academic Year 2025–2026</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800" id="header-teacher-greeting">Welcome back, <?php echo htmlspecialchars($teacher['name']); ?>!</h1>
          <p class="text-sm text-slate-500" id="header-teacher-department"><?php echo htmlspecialchars($teacher['department']); ?></p>
        </div>

        <div class="flex items-center gap-3">
          <a href="<?php echo url('teacher/import-roster'); ?>" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Import Class Roster</span>
          </a>
          <a href="<?php echo url('teacher/live-session'); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-md hover:shadow-lg transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Start Attendance</span>
          </a>
        </div>
      </div>

      <?php if ($atRiskCount > 0): ?>
        <!-- Dropout Warning Banner for Faculty -->
        <div class="rounded-2xl p-4 sm:p-5 bg-rose-50 border border-rose-200 text-rose-900 shadow-xs mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-rose-100 border border-rose-200 text-rose-700 flex items-center justify-center font-bold text-lg shrink-0">
              ⚠️
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-sm font-black text-rose-950">3+ Consecutive Absence Dropout Alert</h3>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-black bg-rose-200 text-rose-900">
                  <?= $atRiskCount ?> Students Flagged
                </span>
              </div>
              <p class="text-xs text-rose-800 mt-1 leading-relaxed">
                Students have reached the critical 3+ consecutive unexcused absence threshold. Automated parent summary emails can be dispatched to initiate academic consultation.
              </p>
            </div>
          </div>
          <a href="<?php echo url('teacher/consecutive-absences'); ?>" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition shrink-0 flex items-center gap-1.5 self-start sm:self-auto">
            <span>Review Watchlist &amp; Email Parents →</span>
          </a>
        </div>
      <?php endif; ?>

      <!-- Quick Metrics Cards (All 5 in 1 Grid Row) -->
      <div class="kpi-row-5 mb-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.875rem;">
        <!-- Metric 1: Assigned Classes -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition flex flex-col justify-between min-h-[114px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Assigned Classes</span>
            <span class="w-7 h-7 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </span>
          </div>
          <div class="my-1">
            <div class="text-xl sm:text-2xl font-black text-slate-800" id="kpi-total-classes">
              <?php echo $classesMetric['total_classes'] > 0 ? "{$classesMetric['total_classes']} " . ($classesMetric['total_classes'] === 1 ? 'Class' : 'Classes') : '0 Classes'; ?>
            </div>
          </div>
          <p class="text-[11px] text-slate-500 truncate" id="kpi-classes-sub" title="<?php echo "{$classesMetric['total_sections']} Sections • {$classesMetric['total_students']} Students"; ?>">
            <?php echo "{$classesMetric['total_sections']} Sections • {$classesMetric['total_students']} Students"; ?>
          </p>
        </div>

        <!-- Metric 2: Attendance Rate -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition flex flex-col justify-between min-h-[114px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">
              <?php echo $attendanceMetric['is_all_time'] ? 'All-Time Rate' : 'Today\'s Attendance'; ?>
            </span>
            <span class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
          </div>
          <p class="text-[11px] text-slate-500 truncate" id="kpi-attendance-sub" title="<?php echo $attendanceMetric['total_records'] > 0 ? "{$attendanceMetric['present_count']} Present • {$attendanceMetric['tardy_count']} Late • {$attendanceMetric['absent_count']} Absent" : "{$attendanceMetric['total_all_time']} term records • 0 today"; ?>">
            <?php 
            if ($attendanceMetric['total_records'] > 0) {
              echo "{$attendanceMetric['present_count']} Pres • {$attendanceMetric['tardy_count']} Late";
            } elseif (!empty($attendanceMetric['is_all_time'])) {
              echo "{$attendanceMetric['total_all_time']} term records";
            } else {
              echo 'No records yet';
            }
            ?>
          </p>
        </div>

        <!-- Metric 3: Active Sessions -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm hover:shadow-md transition flex flex-col justify-between min-h-[114px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider truncate">Active Sessions</span>
            <span class="w-7 h-7 rounded-lg <?php echo $activeSession['is_active'] ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-400'; ?> flex items-center justify-center shrink-0">
              <?php if ($activeSession['is_active']): ?>
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
              <?php else: ?>
                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
              <?php endif; ?>
            </span>
          </div>
          <div class="my-1">
            <div class="text-xl sm:text-2xl font-black text-slate-800" id="kpi-active-sessions">
              <?php echo $activeSession['is_active'] ? '1 Active' : '0 Active'; ?>
            </div>
          </div>
          <p class="text-[11px] <?php echo $activeSession['is_active'] ? 'text-amber-600 font-semibold' : 'text-slate-400'; ?> truncate" id="kpi-active-sub" title="<?php echo htmlspecialchars($activeSession['description']); ?>">
            <?php echo htmlspecialchars($activeSession['description']); ?>
          </p>
        </div>

        <!-- Metric 4: Pending Excuse Slips -->
        <a href="<?php echo url('teacher/excuse-slips'); ?>" class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm hover:shadow-md hover:border-amber-300 transition group flex flex-col justify-between min-h-[114px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-400 group-hover:text-amber-600 transition uppercase tracking-wider truncate">Pending Excuses</span>
            <span class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
          </div>
          <div class="my-1">
            <div class="text-xl sm:text-2xl font-black <?php echo $pendingExcusesCount > 0 ? 'text-amber-600' : 'text-slate-800'; ?>" id="kpi-pending-excuses">
              <?php echo "{$pendingExcusesCount} " . ($pendingExcusesCount === 1 ? 'Slip' : 'Slips'); ?>
            </div>
          </div>
          <p class="text-[11px] text-slate-500 group-hover:text-amber-600 font-medium truncate transition">
            <?php echo $pendingExcusesCount > 0 ? 'Review slips →' : 'All reviewed'; ?>
          </p>
        </a>

        <!-- Metric 5: At-Risk / Dropout Watchlist -->
        <a href="<?php echo url('teacher/consecutive-absences'); ?>" class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm hover:shadow-md hover:border-rose-300 transition group flex flex-col justify-between min-h-[114px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-400 group-hover:text-rose-600 transition uppercase tracking-wider truncate">Dropout Watchlist</span>
            <span class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
          </div>
          <div class="my-1">
            <div class="text-xl sm:text-2xl font-black <?php echo $atRiskCount > 0 ? 'text-rose-600' : 'text-slate-800'; ?>" id="kpi-risk-count">
              <?php echo "{$atRiskCount} " . ($atRiskCount === 1 ? 'Student' : 'Students'); ?>
            </div>
          </div>
          <p class="text-[11px] text-slate-500 group-hover:text-rose-600 font-medium truncate transition">≥ 3 consecutive absences</p>
        </a>
      </div>

      <!-- Main Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Today's Schedule & Classes (Left 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Today's Schedule -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h2 class="font-bold text-slate-800 text-base">
                  <?php echo $isTodaySchedule ? "Today's Class Schedule" : "Assigned Weekly Schedule"; ?>
                </h2>
                <p class="text-xs text-slate-400">
                  <?php echo $isTodaySchedule ? htmlspecialchars($currentDay) : "No sessions scheduled for today (" . date('l') . ") • Showing weekly roster schedule"; ?>
                </p>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
                <?php echo count($schedule); ?> <?php echo count($schedule) === 1 ? 'Class' : 'Classes'; ?>
              </span>
            </div>

            <div class="divide-y divide-slate-100" id="schedule-container">
              <?php if (!empty($schedule)): ?>
                <?php foreach ($schedule as $idx => $item): 
                  $isActive = ($item['status'] === 'active');
                  $startTime = !empty($item['scheduled_time']) ? date('h:i A', strtotime($item['scheduled_time'])) : '08:00 AM';
                  $endTime = !empty($item['scheduled_time']) ? date('h:i A', strtotime($item['scheduled_time'] . ' +2 hours')) : '10:00 AM';
                  $enrolled = (int)($item['enrolled_count'] ?? 0);
                  $scanned = (int)($item['scanned_today'] ?? 0);
                  $scanPct = $enrolled > 0 ? round(($scanned / $enrolled) * 100, 1) : 0;
                ?>
                  <div class="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 <?php echo $isActive ? 'bg-emerald-50/40 border-l-4 border-emerald-500' : 'hover:bg-slate-50 transition'; ?>">
                    <div class="flex items-start gap-4">
                      <div class="text-center p-2 rounded-lg <?php echo $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'; ?> min-w-[75px]">
                        <?php if (!empty($item['schedule_day']) && !$isTodaySchedule): ?>
                          <div class="text-[10px] font-bold uppercase text-blue-600 mb-0.5"><?php echo htmlspecialchars($item['schedule_day']); ?></div>
                        <?php endif; ?>
                        <div class="text-xs font-bold uppercase"><?php echo htmlspecialchars($startTime); ?></div>
                        <div class="text-[10px] text-slate-500"><?php echo htmlspecialchars($endTime); ?></div>
                      </div>
                      <div>
                        <div class="flex items-center gap-2">
                          <h3 class="font-bold text-slate-800"><?php echo htmlspecialchars($item['course_code'] . ' — ' . $item['course_title']); ?></h3>
                          <?php if ($isActive): ?>
                            <span class="badge badge-present">● Session Active</span>
                          <?php elseif ($isTodaySchedule): ?>
                            <span class="badge badge-pending">Upcoming Today</span>
                          <?php else: ?>
                            <span class="badge badge-pending">Weekly Roster</span>
                          <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                          Section: <span class="font-semibold text-slate-700"><?php echo htmlspecialchars($item['section']); ?></span> • 
                          Room <?php echo htmlspecialchars($item['room_number']); ?> • 
                          <?php echo $enrolled; ?> Enrolled
                        </p>
                        <?php if ($scanned > 0): ?>
                          <div class="text-xs text-emerald-700 font-medium mt-1">
                            <?php echo "{$scanned} / {$enrolled} scanned ({$scanPct}%)"; ?>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                      <?php if ($isActive): ?>
                        <a href="<?php echo url('teacher/live-session'); ?>" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 text-xs font-semibold shadow-sm transition">
                          Open Live Screen
                        </a>
                      <?php else: ?>
                        <a href="<?php echo url('teacher/live-session'); ?>" class="px-3.5 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-xs font-semibold shadow-sm transition">
                          Start Attendance
                        </a>
                      <?php endif; ?>
                      <a href="<?php echo url('teacher/classes'); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition">
                        Roster
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="p-8 text-center">
                  <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </div>
                  <h4 class="font-bold text-slate-700 text-sm">No scheduled classes found</h4>
                  <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Upload or import your section rosters to populate class schedules automatically.</p>
                  <a href="<?php echo url('teacher/import-roster'); ?>" class="inline-flex items-center gap-2 mt-4 px-4 py-2 rounded-lg bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm transition">
                    Import Class Roster
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- My Assigned Classes Overview -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="font-bold text-slate-800 text-base">My Classes Overview</h2>
              <a href="<?php echo url('teacher/classes'); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700">View All Classes →</a>
            </div>

            <?php if (!empty($classesOverview)): ?>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="classes-overview-grid">
                <?php foreach ($classesOverview as $idx => $cls): ?>
                  <div class="p-4 rounded-xl border border-slate-200 hover:border-blue-400 transition bg-gradient-to-br from-white to-slate-50">
                    <div class="flex items-center justify-between mb-2">
                      <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800"><?php echo htmlspecialchars($cls['section']); ?></span>
                      <span class="text-xs text-emerald-600 font-bold"><?php echo $cls['avg_rate']; ?>% avg</span>
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($cls['course_code'] . ' — ' . $cls['course_title']); ?></h4>
                    <p class="text-xs text-slate-500 mt-1"><?php echo "{$cls['enrolled_count']} Enrolled • {$cls['sessions_held']} Sessions Held"; ?></p>
                    <div class="flex items-center gap-2 mt-3 pt-3 border-t border-slate-100">
                      <a href="<?php echo url('teacher/classes'); ?>" class="text-xs text-blue-600 hover:underline font-medium">View Roster</a>
                      <span class="text-slate-300">•</span>
                      <a href="<?php echo url('teacher/attendance-history'); ?>" class="text-xs text-slate-600 hover:underline">History</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="p-6 text-center border border-dashed border-slate-200 rounded-xl">
                <p class="text-xs text-slate-400">No active classes enrolled under your account.</p>
                <a href="<?php echo url('teacher/import-roster'); ?>" class="text-xs text-blue-600 font-semibold hover:underline mt-1 inline-block">Enroll students via Roster Import →</a>
              </div>
            <?php endif; ?>
          </div>

          <!-- Row below My Classes: Submitted Excuse Slips (left) & 3-Step Excel Roster Import (right) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Submitted Excuse Slips Container -->
            <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <h3 class="font-bold text-slate-800 text-sm">Submitted Excuse Slips</h3>
                  </div>
                  <a href="<?php echo url('teacher/excuse-slips'); ?>" class="text-[11px] font-semibold text-blue-600 hover:text-blue-700">Review All →</a>
                </div>

                <?php if (!empty($recentExcuses)): ?>
                  <div class="space-y-2.5">
                    <?php foreach ($recentExcuses as $exc): 
                      $isPending = ($exc['status'] === 'pending');
                      $isApproved = ($exc['status'] === 'approved');
                      $badge = $isPending ? 'bg-amber-100 text-amber-800' : ($isApproved ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600');
                    ?>
                      <div class="p-3 rounded-lg border <?php echo $isPending ? 'border-amber-200 bg-amber-50/40' : 'border-slate-100 bg-slate-50/60'; ?> flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                          <div class="flex items-center gap-2">
                            <span class="font-bold text-xs text-slate-800 truncate"><?php echo htmlspecialchars($exc['first_name'] . ' ' . $exc['last_name']); ?></span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded <?php echo $badge; ?> uppercase tracking-wider"><?php echo htmlspecialchars($exc['status']); ?></span>
                          </div>
                          <p class="text-[11px] text-slate-500 truncate mt-0.5"><?php echo htmlspecialchars($exc['reason']); ?></p>
                          <div class="text-[10px] text-slate-400 mt-1">Date: <?php echo date('M d, Y', strtotime($exc['date_of_absence'])); ?></div>
                        </div>
                        <?php if ($isPending): ?>
                          <a href="<?php echo url('teacher/excuse-slips'); ?>" class="shrink-0 px-2.5 py-1 rounded text-[11px] font-bold bg-amber-600 hover:bg-amber-700 text-white transition">Review</a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="text-xs text-slate-400 py-4 text-center">No submitted excuse slips on record.</p>
                <?php endif; ?>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-100 text-center">
                <a href="<?php echo url('teacher/excuse-slips'); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Open Excuse Slip Management →</a>
              </div>
            </div>

            <!-- 3-Step Excel Roster Import Container -->
            <div class="bg-gradient-to-br from-blue-900 to-indigo-950 rounded-xl p-5 text-white shadow-md flex flex-col justify-between">
              <div>
                <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center mb-3">
                  <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <h3 class="font-bold text-base mb-1">3-Step Excel Roster Import</h3>
                <p class="text-xs text-blue-200 leading-relaxed mb-4">Upload class rosters directly. The system automatically validates student IDs against the official Master record.</p>

                <div class="space-y-2 mb-4">
                  <div class="flex items-center gap-2 text-xs text-blue-100">
                    <span class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center text-[10px] font-bold">1</span>
                    <span>Download Excel template</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-blue-100">
                    <span class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center text-[10px] font-bold">2</span>
                    <span>Populate Student IDs &amp; Section</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-blue-100">
                    <span class="w-5 h-5 rounded-full bg-white/10 flex items-center justify-center text-[10px] font-bold">3</span>
                    <span>Upload &amp; Auto-sync Roster</span>
                  </div>
                </div>
              </div>

              <a href="<?php echo url('teacher/import-roster'); ?>" class="block w-full text-center py-2.5 px-3 rounded-lg bg-white text-blue-950 font-bold text-xs hover:bg-blue-50 transition shadow">
                Launch Import Wizard →
              </a>
            </div>
          </div>
        </div>

        <!-- Right Side: Live Feed & Quick Actions -->
        <div class="space-y-6">
          <!-- Live Check-in Activity Stream -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm p-5">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <h3 class="font-bold text-slate-800 text-sm">Live Attendance Feed</h3>
              </div>
              <span class="text-[11px] text-slate-400" id="live-feed-sync-status">Auto-updating</span>
            </div>

            <div class="space-y-3" id="live-feed-container">
              <?php if (!empty($liveFeed)): ?>
                <?php foreach ($liveFeed as $item): 
                  $isLate = ($item['status'] === 'tardy');
                  $isAbsent = ($item['status'] === 'absent');
                  $badgeClass = $isLate ? 'badge-tardy' : ($isAbsent ? 'badge-absent' : 'badge-present');
                  $statusLabel = $isLate ? 'Late' : ($isAbsent ? 'Absent' : 'Present');
                  $avatarBg = $isLate ? 'bg-amber-100 text-amber-700' : ($isAbsent ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700');
                ?>
                  <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
                    <div class="w-8 h-8 rounded-full <?php echo $avatarBg; ?> flex items-center justify-center text-xs font-bold shrink-0">
                      <?php echo htmlspecialchars($item['initials']); ?>
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="text-xs font-bold text-slate-800 truncate"><?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?></div>
                      <div class="text-[11px] text-slate-500"><?php echo htmlspecialchars($item['student_code'] . ' • ' . $item['section']); ?></div>
                    </div>
                    <div class="text-right shrink-0">
                      <span class="badge <?php echo $badgeClass; ?> text-[10px]"><?php echo $statusLabel; ?></span>
                      <div class="text-[10px] text-slate-400 mt-0.5"><?php echo htmlspecialchars($item['time_formatted']); ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="p-6 text-center text-slate-400">
                  <p class="text-xs">No attendance check-ins logged yet today.</p>
                </div>
              <?php endif; ?>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 text-center">
              <a href="<?php echo url('teacher/live-session'); ?>" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Open Full Live Monitor →</a>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
// Dynamic polling for live attendance stream
(function() {
  const syncStatus = document.getElementById('live-feed-sync-status');
  const feedContainer = document.getElementById('live-feed-container');

  async function pollOverview() {
    try {
      const res = await fetch('<?php echo url("api/teacher/dashboard/overview"); ?>');
      if (!res.ok) return;
      const json = await res.json();
      if (!json.success || !json.data) return;

      const d = json.data;

      // Update KPI metrics
      const kpiRate = document.getElementById('kpi-attendance-rate');
      const kpiSub = document.getElementById('kpi-attendance-sub');
      if (kpiRate && d.today_attendance) {
        if (d.today_attendance.rate_percentage > 0) {
          kpiRate.innerHTML = d.today_attendance.rate_percentage + '%' + (d.today_attendance.is_all_time ? ' <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">Term Avg</span>' : '');
        } else {
          kpiRate.textContent = '0.0%';
        }
        if (kpiSub) {
          if (d.today_attendance.total_records > 0) {
            kpiSub.textContent = `${d.today_attendance.present_count} Present • ${d.today_attendance.tardy_count} Late • ${d.today_attendance.absent_count} Absent`;
          } else if (d.today_attendance.is_all_time) {
            kpiSub.textContent = `${d.today_attendance.total_all_time || 0} term records • 0 today`;
          } else {
            kpiSub.textContent = 'No attendance recorded yet';
          }
        }
      }

      // Update Active Sessions
      const kpiActive = document.getElementById('kpi-active-sessions');
      const kpiActiveSub = document.getElementById('kpi-active-sub');
      if (kpiActive && d.active_session) {
        kpiActive.textContent = d.active_session.is_active ? '1 Active' : '0 Active';
        if (kpiActiveSub) {
          kpiActiveSub.textContent = d.active_session.description;
          kpiActiveSub.className = 'text-xs mt-1 truncate ' + (d.active_session.is_active ? 'text-amber-600 font-medium' : 'text-slate-400');
        }
      }

      // Update Pending Excuses
      const kpiExcuses = document.getElementById('kpi-pending-excuses');
      if (kpiExcuses && typeof d.pending_excuses_count !== 'undefined') {
        kpiExcuses.textContent = `${d.pending_excuses_count} ${d.pending_excuses_count === 1 ? 'Slip' : 'Slips'}`;
      }

      // Update Risk Count
      const kpiRisk = document.getElementById('kpi-risk-count');
      if (kpiRisk && typeof d.at_risk_count !== 'undefined') {
        kpiRisk.textContent = `${d.at_risk_count} ${d.at_risk_count === 1 ? 'Student' : 'Students'}`;
      }

      // Update Live Feed items
      if (feedContainer && Array.isArray(d.live_feed) && d.live_feed.length > 0) {
        feedContainer.innerHTML = d.live_feed.map(item => {
          const isLate = (item.status === 'tardy');
          const isAbsent = (item.status === 'absent');
          const badgeClass = isLate ? 'badge-tardy' : (isAbsent ? 'badge-absent' : 'badge-present');
          const statusLabel = isLate ? 'Late' : (isAbsent ? 'Absent' : 'Present');
          const avatarBg = isLate ? 'bg-amber-100 text-amber-700' : (isAbsent ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700');

          return `
            <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50 border border-slate-100">
              <div class="w-8 h-8 rounded-full ${avatarBg} flex items-center justify-center text-xs font-bold shrink-0">
                ${item.initials || 'ST'}
              </div>
              <div class="flex-1 min-w-0">
                <div class="text-xs font-bold text-slate-800 truncate">${item.first_name} ${item.last_name}</div>
                <div class="text-[11px] text-slate-500">${item.student_code} • ${item.section}</div>
              </div>
              <div class="text-right shrink-0">
                <span class="badge ${badgeClass} text-[10px]">${statusLabel}</span>
                <div class="text-[10px] text-slate-400 mt-0.5">${item.time_formatted || ''}</div>
              </div>
            </div>
          `;
        }).join('');
      }
    } catch (e) {
      console.warn('Live overview polling paused:', e);
    }
  }

  // Poll every 12 seconds
  setInterval(pollOverview, 12000);
})();
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
