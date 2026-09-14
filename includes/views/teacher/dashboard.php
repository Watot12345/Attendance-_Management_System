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
$schedule = $overview['schedule'];
$classesOverview = $overview['classes_overview'];
$liveFeed = $overview['live_feed'];
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

      <!-- Quick Metrics Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Metric 1: Assigned Classes -->
        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned Classes</span>
            <span class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-slate-800 mt-2" id="kpi-total-classes">
            <?php echo $classesMetric['total_classes'] > 0 ? "{$classesMetric['total_classes']} Classes" : '0 Classes'; ?>
          </div>
          <p class="text-xs text-slate-500 mt-1" id="kpi-classes-sub">
            <?php echo "{$classesMetric['total_sections']} Sections • {$classesMetric['total_students']} Total Students"; ?>
          </p>
        </div>

        <!-- Metric 2: Today's Attendance -->
        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Today's Attendance</span>
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold text-emerald-600 mt-2" id="kpi-attendance-rate">
            <?php echo $attendanceMetric['total_records'] > 0 ? "{$attendanceMetric['rate_percentage']}%" : ($attendanceMetric['rate_percentage'] > 0 ? "{$attendanceMetric['rate_percentage']}%" : '0.0%'); ?>
          </div>
          <p class="text-xs text-slate-500 mt-1" id="kpi-attendance-sub">
            <?php 
            if ($attendanceMetric['total_records'] > 0) {
              echo "{$attendanceMetric['present_count']} Present • {$attendanceMetric['tardy_count']} Late • {$attendanceMetric['absent_count']} Absent";
            } else {
              echo 'No attendance recorded today';
            }
            ?>
          </p>
        </div>

        <!-- Metric 3: Active Sessions -->
        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Sessions</span>
            <span class="w-8 h-8 rounded-lg <?php echo $activeSession['is_active'] ? 'bg-amber-50 text-amber-600' : 'bg-slate-50 text-slate-400'; ?> flex items-center justify-center">
              <?php if ($activeSession['is_active']): ?>
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-ping"></span>
              <?php else: ?>
                <span class="w-2.5 h-2.5 rounded-full bg-slate-300"></span>
              <?php endif; ?>
            </span>
          </div>
          <div class="text-2xl font-bold text-slate-800 mt-2" id="kpi-active-sessions">
            <?php echo $activeSession['is_active'] ? '1 Active' : '0 Active'; ?>
          </div>
          <p class="text-xs <?php echo $activeSession['is_active'] ? 'text-amber-600 font-medium' : 'text-slate-400'; ?> mt-1 truncate" id="kpi-active-sub">
            <?php echo htmlspecialchars($activeSession['description']); ?>
          </p>
        </div>

        <!-- Metric 4: At-Risk Alerts -->
        <div class="bg-white rounded-xl p-5 border border-slate-100 shadow-sm">
          <div class="flex items-center justify-between">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">At-Risk Alerts</span>
            <span class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
          </div>
          <div class="text-2xl font-bold <?php echo $atRiskCount > 0 ? 'text-rose-600' : 'text-slate-800'; ?> mt-2" id="kpi-risk-count">
            <?php echo "{$atRiskCount} " . ($atRiskCount === 1 ? 'Student' : 'Students'); ?>
          </div>
          <p class="text-xs text-slate-500 mt-1">≥ 3 recorded absences</p>
        </div>
      </div>

      <!-- Main Columns -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Today's Schedule & Classes (Left 2 cols) -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Today's Schedule -->
          <div class="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
              <div>
                <h2 class="font-bold text-slate-800 text-base">Today's Class Schedule</h2>
                <p class="text-xs text-slate-400"><?php echo htmlspecialchars($currentDay); ?></p>
              </div>
              <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-medium">
                <?php echo count($schedule); ?> <?php echo count($schedule) === 1 ? 'Session' : 'Sessions'; ?> Scheduled
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
                      <div class="text-center p-2 rounded-lg <?php echo $isActive ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'; ?> min-w-[70px]">
                        <div class="text-xs font-bold uppercase"><?php echo htmlspecialchars($startTime); ?></div>
                        <div class="text-[10px]"><?php echo htmlspecialchars($endTime); ?></div>
                      </div>
                      <div>
                        <div class="flex items-center gap-2">
                          <h3 class="font-bold text-slate-800"><?php echo htmlspecialchars($item['course_code'] . ' — ' . $item['course_title']); ?></h3>
                          <?php if ($isActive): ?>
                            <span class="badge badge-present">● Session Active</span>
                          <?php else: ?>
                            <span class="badge badge-pending">Upcoming</span>
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
                  <h4 class="font-bold text-slate-700 text-sm">No scheduled classes for today</h4>
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

          <!-- Roster Import CTA Box -->
          <div class="bg-gradient-to-br from-blue-900 to-indigo-950 rounded-xl p-5 text-white shadow-md">
            <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center mb-3">
              <svg class="w-5 h-5 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <h3 class="font-bold text-base mb-1">3-Step Excel Roster Import</h3>
            <p class="text-xs text-blue-200 leading-relaxed mb-4">Upload class rosters directly. The system automatically validates student IDs against the official Master record.</p>
            <a href="<?php echo url('teacher/import-roster'); ?>" class="block w-full text-center py-2 px-3 rounded-lg bg-white text-blue-950 font-bold text-xs hover:bg-blue-50 transition shadow">
              Launch Import Wizard
            </a>
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
        kpiRate.textContent = d.today_attendance.rate_percentage + '%';
        if (kpiSub && d.today_attendance.total_records > 0) {
          kpiSub.textContent = `${d.today_attendance.present_count} Present • ${d.today_attendance.tardy_count} Late • ${d.today_attendance.absent_count} Absent`;
        }
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
