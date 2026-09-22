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
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">Faculty Portal</span>
            <span class="text-xs text-slate-500 font-medium">Academic Year 2025–2026</span>
          </div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900" id="header-teacher-greeting">Welcome back, <?php echo htmlspecialchars($teacher['name']); ?>!</h1>
          <p class="text-xs text-slate-500 mt-0.5" id="header-teacher-department"><?php echo htmlspecialchars($teacher['department']); ?></p>
        </div>

        <div class="flex items-center gap-2.5">
          <a href="<?php echo url('teacher/import-roster'); ?>" class="px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Import Roster</span>
          </a>
          <a href="<?php echo url('teacher/live-session'); ?>" class="px-4 py-2 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Start Attendance</span>
          </a>
        </div>
      </div>

      <?php if ($atRiskCount > 0): ?>
        <!-- Dropout Warning Alert Banner -->
        <div class="rounded-xl p-4 bg-rose-50/80 border border-rose-200/90 text-rose-900 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xs">
          <div class="flex items-start gap-3">
            <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-sm shrink-0 mt-0.5">
              ⚠️
            </div>
            <div>
              <div class="flex items-center gap-2 flex-wrap">
                <h3 class="text-xs font-bold text-rose-950 uppercase tracking-wider">3+ Consecutive Absence Dropout Alert</h3>
                <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-200 text-rose-900">
                  <?= $atRiskCount ?> Students Flagged
                </span>
              </div>
              <p class="text-xs text-rose-800 mt-0.5 leading-relaxed">
                Students have reached the critical 3+ consecutive unexcused absence threshold. Review watchlist to initiate academic retention consultation.
              </p>
            </div>
          </div>
          <a href="<?php echo url('teacher/consecutive-absences'); ?>" class="px-3.5 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs transition shrink-0 self-start sm:self-auto flex items-center gap-1">
            <span>Review Watchlist &rarr;</span>
          </a>
        </div>
      <?php endif; ?>

      <!-- Quick Metrics Cards (5-Grid Row) -->
      <div class="kpi-row-5 mb-6" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem;">
        <!-- Metric 1: Assigned Classes -->
        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-slate-300 transition flex flex-col justify-between min-h-[110px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">Assigned Classes</span>
            <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </span>
          </div>
          <div class="my-1">
            <div class="text-2xl font-bold tracking-tight text-slate-900" id="kpi-total-classes">
              <?php echo $classesMetric['total_classes'] > 0 ? "{$classesMetric['total_classes']} " . ($classesMetric['total_classes'] === 1 ? 'Class' : 'Classes') : '0 Classes'; ?>
            </div>
          </div>
          <p class="text-[11px] text-slate-500 truncate" id="kpi-classes-sub" title="<?php echo "{$classesMetric['total_sections']} Sections • {$classesMetric['total_students']} Students"; ?>">
            <?php echo "{$classesMetric['total_sections']} Sections • {$classesMetric['total_students']} Students"; ?>
          </p>
        </div>

        <!-- Metric 2: Attendance Rate -->
        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-slate-300 transition flex flex-col justify-between min-h-[110px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">
              <?php echo $attendanceMetric['is_all_time'] ? 'All-Time Rate' : 'Today\'s Attendance'; ?>
            </span>
            <span class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
          </div>
          <div class="my-1">
            <div class="text-2xl font-bold tracking-tight text-emerald-600" id="kpi-attendance-rate">
              <?php echo $attendanceMetric['rate_percentage'] > 0 ? "{$attendanceMetric['rate_percentage']}%" : '0.0%'; ?>
            </div>
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
        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-slate-300 transition flex flex-col justify-between min-h-[110px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider truncate">Active Sessions</span>
            <span class="w-6 h-6 rounded-md <?php echo $activeSession['is_active'] ? 'bg-amber-50 text-amber-600' : 'bg-slate-100 text-slate-400'; ?> flex items-center justify-center shrink-0">
              <?php if ($activeSession['is_active']): ?>
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
              <?php else: ?>
                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
              <?php endif; ?>
            </span>
          </div>
          <div class="my-1">
            <div class="text-2xl font-bold tracking-tight text-slate-900" id="kpi-active-sessions">
              <?php echo $activeSession['is_active'] ? '1 Active' : '0 Active'; ?>
            </div>
          </div>
          <p class="text-[11px] <?php echo $activeSession['is_active'] ? 'text-amber-600 font-semibold' : 'text-slate-500'; ?> truncate" id="kpi-active-sub" title="<?php echo htmlspecialchars($activeSession['description']); ?>">
            <?php echo htmlspecialchars($activeSession['description']); ?>
          </p>
        </div>

        <!-- Metric 4: Pending Excuse Slips -->
        <a href="<?php echo url('teacher/excuse-slips'); ?>" class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-amber-300 hover:shadow-xs transition group flex flex-col justify-between min-h-[110px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-500 group-hover:text-amber-600 transition uppercase tracking-wider truncate">Pending Excuses</span>
            <span class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </span>
          </div>
          <div class="my-1">
            <div class="text-2xl font-bold tracking-tight <?php echo $pendingExcusesCount > 0 ? 'text-amber-600' : 'text-slate-900'; ?>" id="kpi-pending-excuses">
              <?php echo "{$pendingExcusesCount} " . ($pendingExcusesCount === 1 ? 'Slip' : 'Slips'); ?>
            </div>
          </div>
          <p class="text-[11px] text-slate-500 group-hover:text-amber-600 font-medium truncate transition">
            <?php echo $pendingExcusesCount > 0 ? 'Review slips &rarr;' : 'All reviewed'; ?>
          </p>
        </a>

        <!-- Metric 5: At-Risk / Dropout Watchlist -->
        <a href="<?php echo url('teacher/consecutive-absences'); ?>" class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-rose-300 hover:shadow-xs transition group flex flex-col justify-between min-h-[110px]">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[11px] font-bold text-slate-500 group-hover:text-rose-600 transition uppercase tracking-wider truncate">Dropout Watchlist</span>
            <span class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center shrink-0">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
          </div>
          <div class="my-1">
            <div class="text-2xl font-bold tracking-tight <?php echo $atRiskCount > 0 ? 'text-rose-600' : 'text-slate-900'; ?>" id="kpi-risk-count">
              <?php echo "{$atRiskCount} " . ($atRiskCount === 1 ? 'Student' : 'Students'); ?>
            </div>
          </div>
          <p class="text-[11px] text-slate-500 group-hover:text-rose-600 font-medium truncate transition">&ge; 3 consecutive absences</p>
        </a>
      </div>

      <!-- Main Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Today's Schedule & Classes (Left 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Today's Schedule -->
          <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h2 class="font-bold text-slate-900 text-sm">
                  <?php echo $isTodaySchedule ? "Today's Class Schedule" : "Assigned Weekly Schedule"; ?>
                </h2>
                <p class="text-xs text-slate-500">
                  <?php echo $isTodaySchedule ? htmlspecialchars($currentDay) : "No sessions scheduled for today (" . date('l') . ") • Showing weekly roster schedule"; ?>
                </p>
              </div>
              <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 text-xs font-medium border border-slate-200/60">
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
                  <div class="p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 <?php echo $isActive ? 'bg-emerald-50/30 border-l-4 border-emerald-500' : 'hover:bg-slate-50/70 transition'; ?>">
                    <div class="flex items-start gap-3.5">
                      <div class="text-center p-2 rounded-lg <?php echo $isActive ? 'bg-emerald-100/80 text-emerald-900 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200/60'; ?> min-w-[76px] shrink-0">
                        <?php if (!empty($item['schedule_day']) && !$isTodaySchedule): ?>
                          <div class="text-[10px] font-bold uppercase text-slate-600 mb-0.5"><?php echo htmlspecialchars($item['schedule_day']); ?></div>
                        <?php endif; ?>
                        <div class="text-xs font-bold uppercase"><?php echo htmlspecialchars($startTime); ?></div>
                        <div class="text-[10px] text-slate-500"><?php echo htmlspecialchars($endTime); ?></div>
                      </div>
                      <div>
                        <div class="flex items-center gap-2 flex-wrap">
                          <h3 class="font-bold text-slate-900 text-sm"><?php echo htmlspecialchars($item['course_code'] . ' — ' . $item['course_title']); ?></h3>
                          <?php if ($isActive): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">● Live Active</span>
                          <?php elseif ($isTodaySchedule): ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Upcoming Today</span>
                          <?php else: ?>
                            <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60">Weekly Roster</span>
                          <?php endif; ?>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                          Section <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($item['section']); ?></span> &middot; 
                          Room <?php echo htmlspecialchars($item['room_number']); ?> &middot; 
                          <?php echo $enrolled; ?> Enrolled
                        </p>
                        <?php if ($scanned > 0): ?>
                          <div class="text-[11px] text-emerald-700 font-medium mt-1 flex items-center gap-1.5">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            <span><?php echo "{$scanned} of {$enrolled} checked in ({$scanPct}%)"; ?></span>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                      <?php if ($isActive): ?>
                        <a href="<?php echo url('teacher/live-session'); ?>" class="px-3.5 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition">
                          Open Live Screen
                        </a>
                      <?php else: ?>
                        <a href="<?php echo url('teacher/live-session'); ?>" class="px-3.5 py-1.5 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-semibold shadow-xs transition">
                          Start Attendance
                        </a>
                      <?php endif; ?>
                      <a href="<?php echo url('teacher/classes'); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition">
                        Roster
                      </a>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="p-8 text-center">
                  <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                  </div>
                  <h4 class="font-bold text-slate-700 text-xs uppercase tracking-wider">No scheduled classes found</h4>
                  <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">Upload or import your section rosters to populate class schedules automatically.</p>
                  <a href="<?php echo url('teacher/import-roster'); ?>" class="inline-flex items-center gap-1.5 mt-3 px-3.5 py-1.5 rounded-lg bg-[#1e3b8a] text-white text-xs font-semibold hover:bg-[#172554] shadow-xs transition">
                    <span>Import Class Roster</span>
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- My Assigned Classes Overview -->
          <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h2 class="font-bold text-slate-900 text-sm">My Classes Overview</h2>
                <p class="text-xs text-slate-500">Summary of enrolled sections and attendance rates</p>
              </div>
              <a href="<?php echo url('teacher/classes'); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700 flex items-center gap-1">View All &rarr;</a>
            </div>

            <?php if (!empty($classesOverview)): ?>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5" id="classes-overview-grid">
                <?php foreach ($classesOverview as $idx => $cls): ?>
                  <div class="p-4 rounded-xl border border-slate-200/80 bg-white hover:border-slate-300 hover:bg-slate-50/40 transition">
                    <div class="flex items-center justify-between mb-2">
                      <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-slate-100 text-slate-800 border border-slate-200/60"><?php echo htmlspecialchars($cls['section']); ?></span>
                      <span class="text-xs text-emerald-600 font-bold"><?php echo $cls['avg_rate']; ?>% avg</span>
                    </div>
                    <h4 class="font-bold text-slate-900 text-xs"><?php echo htmlspecialchars($cls['course_code'] . ' — ' . $cls['course_title']); ?></h4>
                    <p class="text-[11px] text-slate-500 mt-1"><?php echo "{$cls['enrolled_count']} Enrolled • {$cls['sessions_held']} Sessions Held"; ?></p>
                    <div class="flex items-center gap-2 mt-3 pt-2.5 border-t border-slate-100 text-xs">
                      <a href="<?php echo url('teacher/classes'); ?>" class="text-blue-600 hover:underline font-semibold text-[11px]">View Roster</a>
                      <span class="text-slate-300">&middot;</span>
                      <a href="<?php echo url('teacher/attendance-history'); ?>" class="text-slate-600 hover:underline text-[11px]">History</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="p-6 text-center border border-dashed border-slate-200 rounded-xl">
                <p class="text-xs text-slate-400">No active classes enrolled under your account.</p>
                <a href="<?php echo url('teacher/import-roster'); ?>" class="text-xs text-blue-600 font-semibold hover:underline mt-1 inline-block">Enroll students via Roster Import &rarr;</a>
              </div>
            <?php endif; ?>
          </div>

          <!-- Row below My Classes: Submitted Excuse Slips (left) & 3-Step Excel Roster Import (right) -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Submitted Excuse Slips Container -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between mb-3.5">
                  <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                    <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Excuse Slips</h3>
                  </div>
                  <a href="<?php echo url('teacher/excuse-slips'); ?>" class="text-[11px] font-semibold text-blue-600 hover:text-blue-700">Review All &rarr;</a>
                </div>

                <?php if (!empty($recentExcuses)): ?>
                  <div class="space-y-2">
                    <?php foreach ($recentExcuses as $exc): 
                      $isPending = ($exc['status'] === 'pending');
                      $isApproved = ($exc['status'] === 'approved');
                      $badge = $isPending ? 'bg-amber-50 text-amber-800 border border-amber-200' : ($isApproved ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200');
                    ?>
                      <div class="p-2.5 rounded-lg border <?php echo $isPending ? 'border-amber-200/70 bg-amber-50/30' : 'border-slate-100 bg-slate-50/50'; ?> flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                          <div class="flex items-center gap-1.5 flex-wrap">
                            <span class="font-bold text-xs text-slate-800 truncate"><?php echo htmlspecialchars($exc['first_name'] . ' ' . $exc['last_name']); ?></span>
                            <span class="text-[10px] font-bold px-1.5 py-0.2 rounded <?php echo $badge; ?> uppercase"><?php echo htmlspecialchars($exc['status']); ?></span>
                          </div>
                          <p class="text-[11px] text-slate-500 truncate mt-0.5"><?php echo htmlspecialchars($exc['reason']); ?></p>
                          <div class="text-[10px] text-slate-400 mt-0.5"><?php echo date('M d, Y', strtotime($exc['date_of_absence'])); ?></div>
                        </div>
                        <?php if ($isPending): ?>
                          <a href="<?php echo url('teacher/excuse-slips'); ?>" class="shrink-0 px-2 py-1 rounded text-[11px] font-bold bg-amber-600 hover:bg-amber-700 text-white transition">Review</a>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <p class="text-xs text-slate-400 py-6 text-center">No submitted excuse slips on record.</p>
                <?php endif; ?>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-100 text-center">
                <a href="<?php echo url('teacher/excuse-slips'); ?>" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Open Excuse Slip Management &rarr;</a>
              </div>
            </div>

            <!-- 3-Step Excel Roster Import Minimalist Card -->
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5 flex flex-col justify-between">
              <div>
                <div class="flex items-center gap-2 mb-2">
                  <span class="w-6 h-6 rounded-md bg-slate-100 text-slate-700 flex items-center justify-center font-bold text-xs">
                    <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                  </span>
                  <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Excel Roster Import</h3>
                </div>
                <p class="text-xs text-slate-500 leading-relaxed mb-3">Upload class section rosters with automated student ID validation against Master records.</p>

                <div class="space-y-1.5 mb-4">
                  <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span class="w-4 h-4 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-[10px] font-bold">1</span>
                    <span>Download Excel template</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span class="w-4 h-4 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-[10px] font-bold">2</span>
                    <span>Populate Student IDs &amp; Section</span>
                  </div>
                  <div class="flex items-center gap-2 text-xs text-slate-600">
                    <span class="w-4 h-4 rounded-full bg-slate-100 text-slate-700 flex items-center justify-center text-[10px] font-bold">3</span>
                    <span>Upload &amp; Auto-sync Roster</span>
                  </div>
                </div>
              </div>

              <a href="<?php echo url('teacher/import-roster'); ?>" class="block w-full text-center py-2 px-3 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-800 font-semibold text-xs transition shadow-xs">
                Launch Import Wizard &rarr;
              </a>
            </div>
          </div>
        </div>

        <!-- Right Side: Live Feed & Quick Actions -->
        <div class="space-y-6">
          <!-- Live Check-in Activity Stream -->
          <div class="bg-white rounded-xl border border-slate-200/80 shadow-xs p-5">
            <div class="flex items-center justify-between mb-4">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <h3 class="font-bold text-slate-900 text-xs uppercase tracking-wider">Live Attendance Feed</h3>
              </div>
              <span class="text-[11px] text-slate-400" id="live-feed-sync-status">Auto-updating</span>
            </div>

            <div class="space-y-2.5" id="live-feed-container">
              <?php if (!empty($liveFeed)): ?>
                <?php foreach ($liveFeed as $item): 
                  $isLate = ($item['status'] === 'tardy');
                  $isAbsent = ($item['status'] === 'absent');
                  $badgeClass = $isLate ? 'badge-tardy' : ($isAbsent ? 'badge-absent' : 'badge-present');
                  $statusLabel = $isLate ? 'Late' : ($isAbsent ? 'Absent' : 'Present');
                  $avatarBg = $isLate ? 'bg-amber-100 text-amber-800' : ($isAbsent ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-800');
                ?>
                  <div class="flex items-center gap-3 p-2.5 rounded-lg bg-slate-50/70 border border-slate-200/60">
                    <div class="w-7 h-7 rounded-full <?php echo $avatarBg; ?> flex items-center justify-center text-[11px] font-bold shrink-0">
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
              <a href="<?php echo url('teacher/live-session'); ?>" class="text-xs font-semibold text-emerald-600 hover:text-emerald-700">Open Full Live Monitor &rarr;</a>
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
