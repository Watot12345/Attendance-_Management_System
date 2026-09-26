<?php
/**
 * Institutional Attendance & Class Roster Reports / Export Center
 * Path: includes/views/admin/reports.php (also routed via /exports and /reports)
 */
$page_title = 'Export Center & Roster Analytics';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

// Server-side Caching & Query Optimization Layer
$cacheKey = 'ams_exports_roster_data_v4';
$cacheTtl = 60; // 60 seconds TTL
$forceRefresh = (isset($_GET['refresh']) && $_GET['refresh'] === '1') || isset($_GET['ajax']);

$rosterData = [];
$fromCache = false;
$cachedAt = time();

if (!$forceRefresh && isset($_SESSION[$cacheKey]) && is_array($_SESSION[$cacheKey]) && (time() - ($_SESSION[$cacheKey]['timestamp'] ?? 0)) < $cacheTtl) {
    $rosterData = $_SESSION[$cacheKey]['data'];
    $cachedAt = $_SESSION[$cacheKey]['timestamp'];
    $fromCache = true;
} else {
    try {
        $db = Database::getConnection();
        $sql = "
            SELECT 
                cr.roster_id,
                cr.student_id as roster_student_id,
                cr.first_name,
                cr.last_name,
                cr.section,
                cr.course_code,
                cr.course_title,
                cr.course,
                cr.major,
                cr.year_level,
                cr.room_number,
                cr.scheduled_time,
                cr.schedule_day,
                u.user_id,
                COALESCE(u.student_id, cr.student_id) AS user_student_number,
                u.email as user_email,
                u.status as user_account_status,
                CASE WHEN u.user_id IS NOT NULL THEN 1 ELSE 0 END AS is_in_users_table,
                COALESCE(att.total_attendance, 0) AS total_attendance,
                COALESCE(att.present_count, 0) AS present_count,
                COALESCE(att.tardy_count, 0) AS tardy_count,
                COALESCE(att.absent_count, 0) AS absent_count,
                CASE 
                    WHEN COALESCE(att.total_attendance, 0) > 0 
                    THEN ROUND(((COALESCE(att.present_count, 0) + COALESCE(att.tardy_count, 0)) / att.total_attendance) * 100, 1)
                    ELSE NULL 
                END AS attendance_rate
            FROM (
                SELECT 
                    MIN(roster_id) as roster_id,
                    student_id,
                    first_name,
                    last_name,
                    section,
                    course_code,
                    MAX(course_title) as course_title,
                    MAX(course) as course,
                    MAX(major) as major,
                    MAX(year_level) as year_level,
                    MAX(room_number) as room_number,
                    MAX(scheduled_time) as scheduled_time,
                    MAX(schedule_day) as schedule_day
                FROM class_roster
                GROUP BY section, course_code, student_id, first_name, last_name
            ) cr
            LEFT JOIN users u ON (
                (cr.student_id IS NOT NULL AND cr.student_id != '' AND u.student_id = cr.student_id)
                OR (u.user_id = cr.student_id)
            )
            LEFT JOIN (
                SELECT 
                    student_id,
                    COUNT(*) as total_attendance,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
                    SUM(CASE WHEN status = 'tardy' THEN 1 ELSE 0 END) as tardy_count,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
                FROM attendance
                GROUP BY student_id
            ) att ON (att.student_id = cr.student_id OR att.student_id = u.user_id)
            ORDER BY cr.section ASC, cr.last_name ASC, cr.first_name ASC
        ";
        $stmt = $db->query($sql);
        $rosterData = $stmt->fetchAll();
        $cachedAt = time();
        $_SESSION[$cacheKey] = [
            'timestamp' => $cachedAt,
            'data' => $rosterData
        ];
    } catch (Exception $e) {
        $rosterData = [];
        $dbError = $e->getMessage();
    }
}

// Compute aggregate metrics for summary KPI cards
$totalStudents = count($rosterData);
$registeredCount = 0;
$totalPresent = 0;
$totalTardy = 0;
$totalAbsent = 0;
$sectionsMap = [];

foreach ($rosterData as $row) {
    if (!empty($row['is_in_users_table'])) {
        $registeredCount++;
    }
    $totalPresent += (int)$row['present_count'];
    $totalTardy += (int)$row['tardy_count'];
    $totalAbsent += (int)$row['absent_count'];
    
    $sec = $row['section'] ?: 'Unassigned';
    if (!isset($sectionsMap[$sec])) {
        $sectionsMap[$sec] = [
            'count' => 0,
            'present' => 0,
            'tardy' => 0,
            'absent' => 0,
            'total_sessions' => 0,
            'registered' => 0
        ];
    }
    $sectionsMap[$sec]['count']++;
    $sectionsMap[$sec]['present'] += (int)$row['present_count'];
    $sectionsMap[$sec]['tardy'] += (int)$row['tardy_count'];
    $sectionsMap[$sec]['absent'] += (int)$row['absent_count'];
    $sectionsMap[$sec]['total_sessions'] += (int)$row['total_attendance'];
    if (!empty($row['is_in_users_table'])) {
        $sectionsMap[$sec]['registered']++;
    }
}

$unregisteredCount = $totalStudents - $registeredCount;
$registeredPct = $totalStudents > 0 ? round(($registeredCount / $totalStudents) * 100, 1) : 0;
$totalSessionsAll = $totalPresent + $totalTardy + $totalAbsent;
$overallRate = $totalSessionsAll > 0 ? round((($totalPresent + $totalTardy) / $totalSessionsAll) * 100, 1) : null;
$uniqueSections = array_keys($sectionsMap);
sort($uniqueSections);

// Prepare Section Comparison chart data
$sectionChartLabels = [];
$sectionChartRates = [];
$sectionChartEnrolled = [];
foreach ($sectionsMap as $secName => $secStats) {
    $sectionChartLabels[] = 'Sec ' . $secName;
    $secTot = $secStats['present'] + $secStats['tardy'] + $secStats['absent'];
    $secRate = $secTot > 0 ? round((($secStats['present'] + $secStats['tardy']) / $secTot) * 100, 1) : 0;
    $sectionChartRates[] = $secRate;
    $sectionChartEnrolled[] = $secStats['count'];
}

// Course (e.g. BSIT) and Major (e.g. NA, IM, IS) aggregation
$coursesMap = [];
$courseToMajorsMap = [];
$allMajors = [];
$majorsCountMap = [];

foreach ($rosterData as $row) {
    $courseName = !empty($row['course']) ? trim($row['course']) : 'Unspecified Course';
    $majorName = !empty($row['major']) ? trim($row['major']) : 'N/A';

    if (!isset($coursesMap[$courseName])) {
        $coursesMap[$courseName] = 0;
    }
    $coursesMap[$courseName]++;

    if (!isset($courseToMajorsMap[$courseName])) {
        $courseToMajorsMap[$courseName] = [];
    }
    if (!in_array($majorName, $courseToMajorsMap[$courseName], true)) {
        $courseToMajorsMap[$courseName][] = $majorName;
    }

    if (!in_array($majorName, $allMajors, true)) {
        $allMajors[] = $majorName;
    }

    if (!isset($majorsCountMap[$majorName])) {
        $majorsCountMap[$majorName] = 0;
    }
    $majorsCountMap[$majorName]++;
}
ksort($coursesMap);
sort($allMajors);
ksort($majorsCountMap);

$uniqueCourses = array_keys($coursesMap);
$majorChartLabels = array_keys($majorsCountMap);
$majorChartCounts = array_values($majorsCountMap);

// Realtime AJAX Polling Handler (Returns fresh dataset as JSON)
if (isset($_GET['ajax']) || (isset($_GET['format']) && $_GET['format'] === 'json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'timestamp' => time(),
        'cachedAt' => $cachedAt,
        'fromCache' => $fromCache,
        'summary' => [
            'totalStudents' => $totalStudents,
            'registeredCount' => $registeredCount,
            'unregisteredCount' => $unregisteredCount,
            'totalPresent' => $totalPresent,
            'totalTardy' => $totalTardy,
            'totalAbsent' => $totalAbsent,
            'totalSessionsAll' => $totalSessionsAll,
            'overallRate' => $overallRate,
            'sectionLabels' => $sectionChartLabels,
            'sectionRates' => $sectionChartRates,
            'sectionEnrolled' => $sectionChartEnrolled,
            'majorLabels' => $majorChartLabels,
            'majorCounts' => $majorChartCounts,
            'uniqueCourses' => $uniqueCourses,
            'coursesMap' => $coursesMap,
            'courseToMajorsMap' => $courseToMajorsMap,
            'allMajors' => $allMajors,
            'majorsCountMap' => $majorsCountMap,
            'uniqueSections' => $uniqueSections
        ],
        'data' => $rosterData
    ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    exit;
}

require_once dirname(__DIR__) . '/partials/header.php';
?>

<!-- Ensure Chart.js is loaded -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

<style>
/* Sidebar sticky locking at full viewport height on desktop only */
@media (min-width: 1024px) {
  #sidebar {
    position: sticky !important;
    top: 0 !important;
    height: 100vh !important;
    max-height: 100vh !important;
    align-self: flex-start !important;
    flex-shrink: 0 !important;
    z-index: 40 !important;
  }
}

/* Ensure select dropdowns and canvas elements never cause horizontal overflow */
select {
  max-width: 100% !important;
  min-width: 0 !important;
  text-overflow: ellipsis !important;
  box-sizing: border-box !important;
}
.chart-box-wrapper {
  position: relative !important;
  width: 100% !important;
  min-width: 0 !important;
  max-width: 100% !important;
  overflow: hidden !important;
}
.chart-box-wrapper canvas {
  max-width: 100% !important;
  width: 100% !important;
}

/* Minimalist Table & Text Truncation System */
.table-text-truncate {
  max-width: 170px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
@media (min-width: 640px) {
  .table-text-truncate {
    max-width: 220px;
  }
}
@media (min-width: 1024px) {
  .table-text-truncate {
    max-width: 260px;
  }
}
</style>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body px-3 py-4 sm:px-6 sm:py-6 md:px-8 max-w-7xl mx-auto space-y-4 sm:space-y-6 w-full min-w-0 max-w-full">
      
      <!-- Top Banner / Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 sm:p-6 rounded-2xl border border-slate-200/80 shadow-xs w-full min-w-0 max-w-full overflow-hidden">
        <div class="min-w-0 flex-1">
          <div class="flex flex-wrap items-center gap-1.5 sm:gap-2 mb-2">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60 shrink-0">
              <svg class="w-3 h-3 text-[#1e3b8a]" fill="currentColor" viewBox="0 0 20 20"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/></svg>
              Export Center &amp; Analytics
            </span>
            <span class="text-[11px] sm:text-xs text-slate-400 font-medium break-words">Class Roster &bull; Users Verification &bull; Attendance Ledger</span>
          </div>
          <h1 class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900 tracking-tight break-words">Institutional Attendance Reports</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl break-words">
            Live joined aggregation of <strong class="text-slate-700">Class Rosters</strong>, <strong class="text-slate-700">Portal User Accounts</strong>, and <strong class="text-slate-700">Attendance Session Counts</strong>.
          </p>
        </div>

        <!-- Header Action Export Button -->
        <div class="flex items-center gap-2 w-full sm:w-auto shrink-0">
          <!-- Advanced Export Options Modal Trigger -->
          <button type="button" onclick="openExportModal()" title="Open Export Configuration Dialog" class="w-full sm:w-auto justify-center inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1e3b8a] hover:bg-[#162c69] shadow-md shadow-[#1e3b8a]/20 transition cursor-pointer">
            <svg class="w-4 h-4 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Export Reports...</span>
          </button>
        </div>
      </div>

      <!-- Filter Controls & Search Bar (Placed at Top) -->
      <div class="bg-white rounded-2xl p-3.5 sm:p-5 border border-slate-200/80 shadow-xs space-y-3.5 w-full min-w-0 max-w-full overflow-hidden">
        
        <!-- Top Tier: Full-Width Spacious Search Bar -->
        <div class="relative w-full min-w-0">
          <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
          <input type="text" id="filter-search" oninput="applyFilters()" placeholder="Search student name, student ID, course, section, subject, email..." class="w-full min-w-0 pl-10 sm:pl-11 pr-4 py-2.5 sm:py-3 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition placeholder:text-slate-400">
        </div>

        <!-- Bottom Tier: Filter Dropdowns & Reset Action -->
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:flex lg:flex-wrap items-center gap-2 w-full min-w-0">
          <!-- Course Filter (e.g. BSIT) -->
          <select id="filter-course" onchange="onCourseFilterChange()" class="w-full lg:w-auto px-3 py-2 sm:py-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50/60 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition cursor-pointer truncate">
            <option value="">All Courses (<?php echo count($uniqueCourses); ?>)</option>
            <?php foreach ($uniqueCourses as $c): ?>
              <option value="<?php echo htmlspecialchars($c); ?>"><?php echo htmlspecialchars($c); ?> (<?php echo $coursesMap[$c]; ?>)</option>
            <?php endforeach; ?>
          </select>

          <!-- Major Filter (e.g. NA, IM, IS - Cascaded based on Course) -->
          <select id="filter-major" onchange="applyFilters()" class="w-full lg:w-auto px-3 py-2 sm:py-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50/60 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition cursor-pointer truncate">
            <option value="">All Majors (<?php echo count($allMajors); ?>)</option>
            <?php foreach ($allMajors as $m): ?>
              <option value="<?php echo htmlspecialchars($m); ?>"><?php echo htmlspecialchars($m); ?> (<?php echo $majorsCountMap[$m] ?? 0; ?>)</option>
            <?php endforeach; ?>
          </select>

          <!-- Section Filter -->
          <select id="filter-section" onchange="applyFilters()" class="w-full lg:w-auto px-3 py-2 sm:py-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50/60 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition cursor-pointer truncate">
            <option value="">All Sections (<?php echo count($uniqueSections); ?>)</option>
            <?php foreach ($uniqueSections as $sec): ?>
              <option value="<?php echo htmlspecialchars($sec); ?>">Section <?php echo htmlspecialchars($sec); ?></option>
            <?php endforeach; ?>
          </select>

          <!-- User Account Status Filter -->
          <select id="filter-user-status" onchange="applyFilters()" class="w-full lg:w-auto px-3 py-2 sm:py-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50/60 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition cursor-pointer truncate">
            <option value="">All User Statuses</option>
            <option value="registered">Registered in Users (<?php echo $registeredCount; ?>)</option>
            <option value="unregistered">Roster Only (<?php echo $unregisteredCount; ?>)</option>
          </select>

          <!-- Attendance Rate Filter -->
          <select id="filter-attendance" onchange="applyFilters()" class="w-full lg:w-auto px-3 py-2 sm:py-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50/60 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition cursor-pointer truncate">
            <option value="">All Attendance Rates</option>
            <option value="high">&ge; 90% (Good / Excellent)</option>
            <option value="mid">75% – 89% (Moderate)</option>
            <option value="low">&lt; 75% (At-Risk)</option>
            <option value="nodata">No Attendance Data (—)</option>
          </select>

          <!-- Reset Filters -->
          <button type="button" onclick="resetFilters()" class="col-span-2 sm:col-span-1 lg:w-auto px-3.5 py-2 sm:py-2.5 rounded-xl text-xs font-bold text-slate-600 hover:text-slate-900 bg-slate-100 hover:bg-slate-200 border border-slate-200/60 transition text-center flex items-center justify-center gap-1.5 cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span>Reset</span>
          </button>
        </div>

        <!-- Filter Metrics Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-[11px] sm:text-xs text-slate-500 pt-2.5 border-t border-slate-100 w-full min-w-0">
          <div class="min-w-0 break-words">
            Showing <strong id="filter-visible-count" class="text-slate-800"><?php echo $totalStudents; ?></strong> of <strong class="text-slate-800"><?php echo $totalStudents; ?></strong> total students
          </div>
          <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span> Registered User</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-amber-400"></span> Roster Only</span>
          </div>
        </div>
      </div>

      <!-- KPI Summary Cards (Unified Cohesive Color Theme) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4 w-full min-w-0 max-w-full">
        <!-- Card 1: Total Roster -->
        <div class="bg-white p-3 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-blue-400 transition w-full min-w-0">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">Enrolled Roster</span>
            <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
          </div>
          <div class="mt-2 sm:mt-3 flex items-baseline gap-1 sm:gap-2 flex-wrap">
            <span class="text-lg sm:text-2xl md:text-3xl font-black text-slate-900" id="kpi-total-students"><?php echo number_format($totalStudents); ?></span>
            <span class="text-[10px] sm:text-xs font-medium text-slate-400">students</span>
          </div>
          <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1 truncate" id="kpi-sections-count">Across <?php echo count($uniqueSections); ?> class sections</p>
        </div>

        <!-- Card 2: Registered in Users Table -->
        <div class="bg-white p-3 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-blue-400 transition w-full min-w-0">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">In Users Table</span>
            <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
          </div>
          <div class="mt-2 sm:mt-3 flex items-baseline gap-1 sm:gap-2 flex-wrap">
            <span class="text-lg sm:text-2xl md:text-3xl font-black text-slate-900" id="kpi-registered-pct"><?php echo $registeredPct; ?>%</span>
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 truncate" id="kpi-registered-counts">(<?php echo $registeredCount; ?>/<?php echo $totalStudents; ?>)</span>
          </div>
          <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1 truncate" id="kpi-unregistered-desc"><?php echo $unregisteredCount; ?> roster-only</p>
        </div>

        <!-- Card 3: Overall Attendance Rate -->
        <div class="bg-white p-3 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-blue-400 transition w-full min-w-0">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">Attendance Rate</span>
            <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
          </div>
          <div class="mt-2 sm:mt-3 flex items-baseline gap-1 sm:gap-2 flex-wrap">
            <span class="text-lg sm:text-2xl md:text-3xl font-black text-slate-900" id="kpi-attendance-rate"><?php echo $overallRate !== null ? $overallRate . '%' : '—'; ?></span>
            <span class="text-[10px] sm:text-xs font-medium text-slate-400">overall</span>
          </div>
          <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1 truncate" id="kpi-total-sessions-desc"><?php echo $totalSessionsAll > 0 ? number_format($totalSessionsAll) . ' total records' : 'No sessions yet'; ?></p>
        </div>

        <!-- Card 4: Attendance Status Mix -->
        <div class="bg-white p-3 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden group hover:border-blue-400 transition w-full min-w-0">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">Session Counts</span>
            <div class="w-6 h-6 sm:w-8 sm:h-8 rounded-lg sm:rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
            </div>
          </div>
          <div class="mt-2 sm:mt-3 flex items-center justify-between text-[10px] sm:text-xs font-bold">
            <span class="text-emerald-600" id="kpi-present-count">P: <?php echo $totalPresent; ?></span>
            <span class="text-amber-600" id="kpi-tardy-count">T: <?php echo $totalTardy; ?></span>
            <span class="text-rose-600" id="kpi-absent-count">A: <?php echo $totalAbsent; ?></span>
          </div>
          <div class="w-full bg-slate-100 h-2 rounded-full mt-2.5 overflow-hidden flex">
            <?php 
              $pWidth = $totalSessionsAll > 0 ? ($totalPresent / $totalSessionsAll) * 100 : 70;
              $tWidth = $totalSessionsAll > 0 ? ($totalTardy / $totalSessionsAll) * 100 : 20;
              $aWidth = $totalSessionsAll > 0 ? ($totalAbsent / $totalSessionsAll) * 100 : 10;
            ?>
            <div id="kpi-bar-present" style="width: <?php echo $pWidth; ?>%" class="bg-emerald-500 h-full transition-all duration-300"></div>
            <div id="kpi-bar-tardy" style="width: <?php echo $tWidth; ?>%" class="bg-amber-400 h-full transition-all duration-300"></div>
            <div id="kpi-bar-absent" style="width: <?php echo $aWidth; ?>%" class="bg-rose-500 h-full transition-all duration-300"></div>
          </div>
        </div>
      </div>

      <!-- Visual Analytics & Chart.js Container (2 Columns: Left = Section Attendance Rates, Right = Row of Attendance Breakdown & Students by Major) -->
      <div id="export-charts-container" class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 w-full min-w-0 max-w-full">
        
        <!-- Left Column: Section Attendance Rates -->
        <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between w-full min-w-0 max-w-full overflow-hidden">
          <div class="flex items-center justify-between mb-2">
            <div class="min-w-0">
              <h3 class="font-bold text-slate-800 text-xs sm:text-sm truncate">Section Attendance Rates</h3>
              <p class="text-[10px] sm:text-[11px] text-slate-400 truncate">Average % attendance across class sections</p>
            </div>
            <span class="p-1.5 rounded-lg bg-slate-50 text-slate-400 shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </span>
          </div>
          <div class="chart-box-wrapper flex items-center justify-center flex-1" style="height: 210px; min-height: 200px;">
            <canvas id="chartSectionRates" style="max-height: 210px;"></canvas>
          </div>
        </div>

        <!-- Right Column: 2 Charts in a Row on tablet/desktop, stacked on mobile -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 sm:gap-4 w-full min-w-0 max-w-full">
          <!-- Chart: Attendance Status Breakdown -->
          <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between w-full min-w-0 max-w-full overflow-hidden">
            <div class="flex items-center justify-between mb-2">
              <div class="min-w-0">
                <h3 class="font-bold text-slate-800 text-xs truncate">Attendance Breakdown</h3>
                <p class="text-[10px] text-slate-400 truncate">Present vs Tardy vs Absent</p>
              </div>
              <span class="p-1 rounded bg-slate-50 text-slate-400 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
              </span>
            </div>
            <div class="chart-box-wrapper flex items-center justify-center flex-1" style="height: 190px; min-height: 180px;">
              <canvas id="chartAttendanceStatus" style="max-height: 190px;"></canvas>
            </div>
          </div>

          <!-- Chart: Academic Majors / Programs Distribution (Pie Chart) -->
          <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between w-full min-w-0 max-w-full overflow-hidden">
            <div class="flex items-center justify-between mb-2">
              <div class="min-w-0">
                <h3 class="font-bold text-slate-800 text-xs truncate">Students by Major</h3>
                <p class="text-[10px] text-slate-400 truncate">Enrolled count per major</p>
              </div>
              <span class="p-1 rounded bg-slate-50 text-slate-400 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222"/></svg>
              </span>
            </div>
            <div class="chart-box-wrapper flex items-center justify-center flex-1" style="height: 190px; min-height: 180px;">
              <canvas id="chartMajorDistribution" style="max-height: 190px;"></canvas>
            </div>
          </div>
        </div>

      </div>

      <!-- Main Ledger Table (Minimalist & Precision Truncated) -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden w-full min-w-0 max-w-full">
        <div class="px-4 sm:px-5 py-3.5 sm:py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-2 bg-slate-50/40">
          <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600 shrink-0"></span>
            <h2 class="font-bold text-slate-900 text-xs sm:text-sm tracking-tight truncate">Class Roster &amp; Attendance Registry Ledger</h2>
          </div>
          <div class="flex items-center gap-2 text-[11px] text-slate-400 shrink-0">
            <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-500 font-medium">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Live Dataset
            </span>
            <span class="sm:hidden inline-flex items-center gap-1 text-[10px] text-blue-600 font-semibold bg-blue-50 px-2 py-0.5 rounded-md">
              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
              Scroll &rarr;
            </span>
          </div>
        </div>

        <div class="overflow-x-auto w-full max-w-full">
          <table class="w-full text-left text-xs min-w-[720px]" id="roster-attendance-table">
            <thead class="bg-slate-50/80 text-slate-500 uppercase font-bold text-[10px] sm:text-[11px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3 px-4 font-semibold">Student Info</th>
                <th class="py-3 px-4 font-semibold">Section &amp; Academic Program</th>
                <th class="py-3 px-4 font-semibold">Schedule &amp; Course</th>
                <th class="py-3 px-4 text-center font-semibold">Account Status</th>
                <th class="py-3 px-4 text-center font-semibold">Sessions (P / T / A)</th>
                <th class="py-3 px-4 text-right font-semibold">Attendance Rate</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700 bg-white" id="roster-table-body">
              <?php if (empty($rosterData)): ?>
                <tr>
                  <td colspan="6" class="py-16 px-4 text-center">
                    <div class="max-w-md mx-auto flex flex-col items-center">
                      <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                      </div>
                      <h3 class="text-sm font-bold text-slate-800">No Roster Records Found</h3>
                      <p class="text-xs text-slate-400 mt-1 max-w-sm">No class roster or student enrolment data has been registered in the database yet.</p>
                    </div>
                  </td>
                </tr>
              <?php else: ?>
                <!-- Filter Empty State (Shown when 0 rows match search/filters) -->
                <tr id="filter-empty-state" class="hidden">
                  <td colspan="6" class="py-16 px-4 text-center">
                    <div class="max-w-md mx-auto flex flex-col items-center">
                      <div class="w-14 h-14 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mb-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                      </div>
                      <h3 class="text-sm font-bold text-slate-800">No Students Found</h3>
                      <p class="text-xs text-slate-400 mt-1 max-w-sm">No roster records match your active search terms or filter selection. Try adjusting your keywords or clearing filters.</p>
                      <button type="button" onclick="resetFilters()" class="mt-4 inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 border border-blue-200 transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Reset All Filters</span>
                      </button>
                    </div>
                  </td>
                </tr>
                <?php foreach ($rosterData as $index => $row): 
                  $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
                  $initials = strtoupper(substr(trim($row['first_name'] ?: 'S'), 0, 1) . substr(trim($row['last_name'] ?: 'N'), 0, 1));
                  $isInUsers = !empty($row['is_in_users_table']);
                  $hasData = (int)$row['total_attendance'] > 0 && $row['attendance_rate'] !== null;
                  $rate = $hasData ? (float)$row['attendance_rate'] : null;
                  $rateBadgeClass = $rate !== null 
                    ? ($rate >= 90 ? 'bg-emerald-50 text-emerald-700 border-emerald-200/80' : ($rate >= 75 ? 'bg-amber-50 text-amber-700 border-amber-200/80' : 'bg-rose-50 text-rose-700 border-rose-200/80'))
                    : 'bg-slate-50 text-slate-400 border-slate-200 font-semibold';
                  $studentNum = $row['user_student_number'] ?: ($row['roster_student_id'] ?: 'N/A');
                  $userEmail = !empty($row['user_email']) ? trim($row['user_email']) : '';
                  $courseTitle = !empty($row['course_title']) ? trim($row['course_title']) : 'Web Systems';
                  $courseCode = !empty($row['course_code']) ? trim($row['course_code']) : 'IT301';
                  $roomNum = !empty($row['room_number']) ? trim($row['room_number']) : '402';
                  $schedDay = !empty($row['schedule_day']) ? trim($row['schedule_day']) : 'Mon';
                  $schedTime = !empty($row['scheduled_time']) ? trim($row['scheduled_time']) : '08:00';
                  $courseName = !empty($row['course']) ? trim($row['course']) : 'BSIT';
                  $majorName = !empty($row['major']) ? trim($row['major']) : 'N/A';
                  $yearLvl = !empty($row['year_level']) ? trim($row['year_level']) : '3';
                ?>
                  <tr class="hover:bg-slate-50/60 transition roster-row" 
                      data-index="<?php echo $index; ?>"
                      data-student-name="<?php echo strtolower(htmlspecialchars($fullName)); ?>"
                      data-student-id="<?php echo strtolower(htmlspecialchars($studentNum)); ?>"
                      data-section="<?php echo htmlspecialchars($row['section']); ?>"
                      data-course="<?php echo strtolower(htmlspecialchars(!empty($row['course']) ? trim($row['course']) : 'Unspecified Course')); ?>"
                      data-major="<?php echo strtolower(htmlspecialchars(!empty($row['major']) ? trim($row['major']) : 'N/A')); ?>"
                      data-search="<?php echo strtolower(htmlspecialchars($fullName . ' ' . $studentNum . ' ' . ($row['section'] ?? '') . ' ' . ($row['course'] ?? '') . ' ' . ($row['major'] ?? '') . ' ' . ($row['course_code'] ?? '') . ' ' . ($row['course_title'] ?? '') . ' ' . ($row['user_email'] ?? ''))); ?>"
                      data-email="<?php echo strtolower(htmlspecialchars($row['user_email'] ?? '')); ?>"
                      data-is-registered="<?php echo $isInUsers ? 'registered' : 'unregistered'; ?>"
                      data-rate="<?php echo $rate !== null ? $rate : '-1'; ?>">
                    
                    <!-- Student Info -->
                    <td class="py-3.5 px-4">
                      <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-7 h-7 rounded-lg bg-[#1e3b8a] text-white font-bold text-[10px] flex items-center justify-center shrink-0">
                          <?php echo $initials; ?>
                        </div>
                        <div class="min-w-0 flex-1">
                          <div class="font-bold text-slate-900 text-xs sm:text-sm truncate max-w-[170px] sm:max-w-[210px]" title="<?php echo htmlspecialchars($fullName); ?>">
                            <?php echo htmlspecialchars($fullName); ?>
                          </div>
                          <div class="text-[10px] text-slate-400 font-mono flex items-center gap-1.5 truncate max-w-[170px] sm:max-w-[210px]" title="Student ID: <?php echo htmlspecialchars($studentNum); ?>">
                            <span>ID: #<?php echo htmlspecialchars($studentNum); ?></span>
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Section & Academic Details -->
                    <td class="py-3 px-4 min-w-0">
                      <div class="flex items-center gap-1.5 flex-wrap">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60">
                          Sec <?php echo htmlspecialchars($row['section']); ?>
                        </span>
                      </div>
                      <div class="text-[11px] text-slate-500 font-medium truncate max-w-[160px] sm:max-w-[190px] mt-1" title="<?php echo htmlspecialchars($courseName . ' • Major: ' . $majorName . ' • Yr ' . $yearLvl); ?>">
                        <span class="font-bold text-slate-700"><?php echo htmlspecialchars($courseName); ?></span>
                        <?php if (!empty($row['major'])): ?>
                          &bull; <span class="text-slate-600"><?php echo htmlspecialchars($row['major']); ?></span>
                        <?php endif; ?>
                        &bull; Yr <?php echo htmlspecialchars($yearLvl); ?>
                      </div>
                    </td>

                    <!-- Schedule & Course -->
                    <td class="py-3 px-4 min-w-0">
                      <div class="font-semibold text-slate-800 text-xs truncate max-w-[180px] sm:max-w-[230px]" title="<?php echo htmlspecialchars($courseCode . ' - ' . $courseTitle); ?>">
                        <span class="font-bold text-slate-900"><?php echo htmlspecialchars($courseCode); ?></span>
                        <span class="text-slate-600">- <?php echo htmlspecialchars($courseTitle); ?></span>
                      </div>
                      <div class="text-[11px] text-slate-400 truncate max-w-[180px] sm:max-w-[230px] mt-0.5" title="Room <?php echo htmlspecialchars($roomNum); ?> • <?php echo htmlspecialchars($schedDay . ' ' . $schedTime); ?>">
                        Rm <?php echo htmlspecialchars($roomNum); ?> &bull; <?php echo htmlspecialchars($schedDay); ?> <?php echo htmlspecialchars($schedTime); ?>
                      </div>
                    </td>

                    <!-- In Users Table? (Account Status) -->
                    <td class="py-3 px-4 text-center min-w-0">
                      <?php if ($isInUsers): ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
                          <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                          Registered
                        </span>
                        <?php if (!empty($userEmail)): ?>
                          <div class="text-[10px] text-slate-400 mt-0.5 truncate max-w-[130px] mx-auto" title="<?php echo htmlspecialchars($userEmail); ?>"><?php echo htmlspecialchars($userEmail); ?></div>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                          <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
                          Roster Only
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Attendance Counts (Sessions) -->
                    <td class="py-3 px-4 text-center">
                      <div class="inline-flex items-center gap-1.5 bg-slate-50/80 px-2.5 py-1 rounded-lg border border-slate-200/60 font-semibold text-xs">
                        <span class="text-emerald-600 font-bold" title="Present"><?php echo $row['present_count']; ?>P</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-amber-600 font-bold" title="Tardy"><?php echo $row['tardy_count']; ?>T</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-rose-600 font-bold" title="Absent"><?php echo $row['absent_count']; ?>A</span>
                        <span class="text-slate-400 text-[10px] font-normal ml-0.5" title="Total Sessions">(<?php echo $row['total_attendance']; ?>)</span>
                      </div>
                    </td>

                    <!-- Attendance Rate -->
                    <td class="py-3 px-4 text-right">
                      <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border <?php echo $rateBadgeClass; ?>">
                        <span><?php echo $rate !== null ? number_format($rate, 1) . '%' : '—'; ?></span>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Table Pagination Footer Bar -->
        <div id="roster-pagination-bar" class="px-4 sm:px-5 py-3.5 border-t border-slate-100 bg-slate-50/40 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs">
          <!-- Left: Record Stats -->
          <div class="flex items-center gap-2">
            <span class="text-slate-500 font-medium" id="roster-pagination-info">
              Showing <strong class="text-slate-800 font-bold" id="roster-page-start">1</strong> to <strong class="text-slate-800 font-bold" id="roster-page-end">10</strong> of <strong class="text-slate-800 font-bold" id="roster-page-total"><?php echo count($rosterData); ?></strong> students
            </span>
          </div>

          <!-- Right: Page Navigation Buttons (Previous / Numbers / Next) -->
          <div class="flex items-center gap-1.5 self-center sm:self-auto flex-wrap" id="roster-pagination-controls">
            <!-- Buttons dynamically populated by renderRosterPagination -->
          </div>
        </div>

      </div>

    </main>
  </div>
</div>

<!-- ========================================== -->
<!-- EXPORT CONFIGURATION MODAL DIALOG          -->
<!-- ========================================== -->
<div id="export-modal-backdrop" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-3 sm:p-4 transition-all">
  <div class="bg-white rounded-2xl sm:rounded-3xl shadow-2xl border border-slate-200 max-w-lg w-full max-h-[92vh] flex flex-col overflow-hidden transform transition-all">
    
    <!-- Modal Header -->
    <div class="p-4 sm:p-6 border-b border-slate-100 flex items-center justify-between bg-gradient-to-r from-slate-50 to-white shrink-0">
      <div class="flex items-center gap-2.5 sm:gap-3">
        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0">
          <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        </div>
        <div>
          <h3 class="text-base sm:text-lg font-black text-slate-900 leading-tight">Export Reports &amp; Ledger</h3>
          <p class="text-[11px] sm:text-xs text-slate-500">Configure format, chart snapshot inclusions, and data scope</p>
        </div>
      </div>
      <button type="button" onclick="closeExportModal()" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Modal Form Body -->
    <div class="p-4 sm:p-6 space-y-4 sm:space-y-5 overflow-y-auto max-h-[62vh] sm:max-h-[66vh]">
      
      <!-- 1. Format Choice -->
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">1. Select Export Format</label>
        <div class="grid grid-cols-3 gap-2 sm:gap-3">
          <!-- Excel Option -->
          <div id="format-card-excel" onclick="selectExportFormat('excel')" class="cursor-pointer border-2 border-emerald-500 bg-emerald-50/40 rounded-xl sm:rounded-2xl p-2.5 sm:p-3 flex flex-col items-center justify-center text-center gap-1.5 sm:gap-2 transition">
            <input type="radio" name="export_format" value="excel" checked class="hidden">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">
              XLS
            </div>
            <div>
              <div class="font-bold text-xs text-slate-900">Excel</div>
              <div class="text-[10px] text-slate-400 hidden xs:block">Spreadsheet</div>
            </div>
          </div>

          <!-- Word Option -->
          <div id="format-card-word" onclick="selectExportFormat('word')" class="cursor-pointer border-2 border-slate-200 hover:border-blue-400 rounded-xl sm:rounded-2xl p-2.5 sm:p-3 flex flex-col items-center justify-center text-center gap-1.5 sm:gap-2 transition">
            <input type="radio" name="export_format" value="word" class="hidden">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
              DOC
            </div>
            <div>
              <div class="font-bold text-xs text-slate-900">Word</div>
              <div class="text-[10px] text-slate-400 hidden xs:block">Document</div>
            </div>
          </div>

          <!-- PDF Option -->
          <div id="format-card-pdf" onclick="selectExportFormat('pdf')" class="cursor-pointer border-2 border-slate-200 hover:border-rose-400 rounded-xl sm:rounded-2xl p-2.5 sm:p-3 flex flex-col items-center justify-center text-center gap-1.5 sm:gap-2 transition">
            <input type="radio" name="export_format" value="pdf" class="hidden">
            <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-xl bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs">
              PDF
            </div>
            <div>
              <div class="font-bold text-xs text-slate-900">PDF</div>
              <div class="text-[10px] text-slate-400 hidden xs:block">Print / PDF</div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2. Export Content (Text Data Only vs With Charts) -->
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">2. Report Content &amp; Visuals</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 text-xs">
          <!-- Text Data Only Option -->
          <label class="cursor-pointer border-2 border-slate-200 hover:border-slate-300 rounded-xl sm:rounded-2xl p-3 sm:p-3.5 flex items-start gap-2.5 sm:gap-3 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/30">
            <input type="radio" name="export_content_type" value="text_only" class="mt-0.5 text-blue-600 focus:ring-blue-500">
            <div>
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M3 6h18M3 18h18"/></svg>
                <span>Only Text Data</span>
              </div>
              <p class="text-[10px] text-slate-500 mt-1 leading-snug">Export clean student ledger table only (no embedded charts).</p>
            </div>
          </label>

          <!-- With Visual Charts Option -->
          <label class="cursor-pointer border-2 border-blue-600 bg-blue-50/30 rounded-xl sm:rounded-2xl p-3 sm:p-3.5 flex items-start gap-2.5 sm:gap-3 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/30">
            <input type="radio" name="export_content_type" value="with_charts" checked class="mt-0.5 text-blue-600 focus:ring-blue-500">
            <div>
              <div class="font-bold text-slate-900 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
                <span>With Charts</span>
                <span class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-blue-100 text-blue-700">Full</span>
              </div>
              <p class="text-[10px] text-slate-500 mt-1 leading-snug">Embed active visual graphs (Attendance Breakdown, Rates, Majors) with table.</p>
            </div>
          </label>
        </div>
      </div>

      <!-- 3. Data Scope (Row Filtering) -->
      <div>
        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">3. Data Scope (Rows)</label>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5 sm:gap-3 text-xs">
          <label class="cursor-pointer border-2 border-slate-200 hover:border-slate-300 rounded-xl sm:rounded-2xl p-3 flex items-center gap-2.5 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/30">
            <input type="radio" name="export_scope" value="filtered" checked class="text-blue-600">
            <div>
              <div class="font-bold text-slate-800">Filtered Records Only</div>
              <div class="text-[10px] text-slate-400" id="modal-scope-filtered-count"><?php echo $totalStudents; ?> rows match filters</div>
            </div>
          </label>
          <label class="cursor-pointer border-2 border-slate-200 hover:border-slate-300 rounded-xl sm:rounded-2xl p-3 flex items-center gap-2.5 transition has-[:checked]:border-blue-600 has-[:checked]:bg-blue-50/30">
            <input type="radio" name="export_scope" value="all" class="text-blue-600">
            <div>
              <div class="font-bold text-slate-800">All Database Records</div>
              <div class="text-[10px] text-slate-400"><?php echo $totalStudents; ?> total student rows</div>
            </div>
          </label>
        </div>
      </div>

      <!-- 4. Report Filename -->
      <div>
        <label for="export-filename" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">4. Filename</label>
        <input type="text" id="export-filename" value="AMS_Attendance_Roster_Report_<?php echo date('Y-m-d'); ?>" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">
      </div>

    </div>

    <!-- Modal Footer Actions -->
    <div class="p-4 sm:p-6 border-t border-slate-100 flex flex-col-reverse sm:flex-row items-stretch sm:items-center justify-end gap-2.5 sm:gap-3 bg-slate-50/50 shrink-0">
      <button type="button" onclick="closeExportModal()" class="w-full sm:w-auto px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 transition text-center">
        Cancel
      </button>
      <button type="button" onclick="executeExportProcess()" id="btn-run-export" class="w-full sm:w-auto px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition flex items-center justify-center gap-2 text-center">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>Generate &amp; Download</span>
      </button>
    </div>

  </div>
</div>

<!-- ========================================== -->
<!-- JAVASCRIPT: CHARTS, SEARCH & EXPORT ENGINE -->
<!-- ========================================== -->
<script>
// Client-side Dataset Cache & Global State
window.AMS_EXPORTS_CACHE = {
  records: <?php echo json_encode($rosterData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>,
  summary: {
    totalStudents: <?php echo $totalStudents; ?>,
    registeredCount: <?php echo $registeredCount; ?>,
    unregisteredCount: <?php echo $unregisteredCount; ?>,
    totalPresent: <?php echo $totalPresent; ?>,
    totalTardy: <?php echo $totalTardy; ?>,
    totalAbsent: <?php echo $totalAbsent; ?>,
    overallRate: <?php echo json_encode($overallRate); ?>,
    sectionLabels: <?php echo json_encode($sectionChartLabels); ?>,
    sectionRates: <?php echo json_encode($sectionChartRates); ?>,
    sectionEnrolled: <?php echo json_encode($sectionChartEnrolled); ?>,
    majorLabels: <?php echo json_encode($majorChartLabels); ?>,
    majorCounts: <?php echo json_encode($majorChartCounts); ?>,
    uniqueCourses: <?php echo json_encode($uniqueCourses); ?>,
    coursesMap: <?php echo json_encode($coursesMap); ?>,
    courseToMajorsMap: <?php echo json_encode($courseToMajorsMap); ?>,
    allMajors: <?php echo json_encode($allMajors); ?>
  },
  charts: {}
};

// 1. Chart Initialization
function initExportCharts() {
  if (typeof Chart === 'undefined') {
    console.warn('Chart.js library not ready yet, retrying in 200ms...');
    setTimeout(initExportCharts, 200);
    return;
  }

  const s = window.AMS_EXPORTS_CACHE.summary;

  // Chart 1: Attendance Breakdown
  try {
    const ctxStatus = document.getElementById('chartAttendanceStatus');
    if (ctxStatus) {
      if (window.AMS_EXPORTS_CACHE.charts.status) {
        window.AMS_EXPORTS_CACHE.charts.status.destroy();
      }
      const hasData = (s.totalPresent + s.totalTardy + s.totalAbsent) > 0;
      window.AMS_EXPORTS_CACHE.charts.status = new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
          labels: hasData ? ['Present', 'Tardy', 'Absent'] : ['No Attendance Logged Yet'],
          datasets: [{
            data: hasData ? [s.totalPresent, s.totalTardy, s.totalAbsent] : [1],
            backgroundColor: hasData ? ['#10B981', '#F59E0B', '#EF4444'] : ['#E2E8F0'],
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: hasData ? 4 : 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 10 } } },
            tooltip: {
              callbacks: {
                label: function(item) {
                  const isPlaceholder = item.chart.data.labels[0] === 'No Attendance Logged Yet' || item.chart.data.labels[0] === 'No Attendance Logged';
                  if (isPlaceholder) return ' No attendance records logged';
                  return ` ${item.label}: ${item.raw} sessions`;
                }
              }
            }
          },
          cutout: '68%'
        }
      });
    }
  } catch (err) {
    console.error('Error rendering chartAttendanceStatus:', err);
  }

  // Chart 2: Section Performance
  try {
    const ctxSection = document.getElementById('chartSectionRates');
    if (ctxSection) {
      if (window.AMS_EXPORTS_CACHE.charts.section) {
        window.AMS_EXPORTS_CACHE.charts.section.destroy();
      }
      const hasSections = s.sectionLabels && s.sectionLabels.length > 0;
      window.AMS_EXPORTS_CACHE.charts.section = new Chart(ctxSection, {
        type: 'bar',
        data: {
          labels: hasSections ? s.sectionLabels : ['No Sections'],
          datasets: [{
            label: 'Attendance Rate %',
            data: hasSections ? s.sectionRates : [0],
            backgroundColor: '#3B82F6',
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
              max: 100,
              ticks: { font: { size: 9 }, callback: (v) => v + '%' },
              grid: { color: '#F1F5F9' }
            },
            x: {
              ticks: { font: { size: 9 } },
              grid: { display: false }
            }
          },
          plugins: {
            legend: { display: false },
            tooltip: {
              callbacks: {
                label: (item) => ` Attendance Rate: ${item.raw > 0 ? item.raw + '%' : '0% / No Sessions'}`
              }
            }
          }
        }
      });
    }
  } catch (err) {
    console.error('Error rendering chartSectionRates:', err);
  }

  // Chart 3: Majors / Academic Programs Distribution
  try {
    const ctxMajor = document.getElementById('chartMajorDistribution');
    if (ctxMajor) {
      if (window.AMS_EXPORTS_CACHE.charts.major) {
        window.AMS_EXPORTS_CACHE.charts.major.destroy();
      }
      const hasMajors = s.majorLabels && s.majorLabels.length > 0;
      const palette = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#6366F1', '#14B8A6', '#F97316'];
      window.AMS_EXPORTS_CACHE.charts.major = new Chart(ctxMajor, {
        type: 'pie',
        data: {
          labels: hasMajors ? s.majorLabels : ['No Majors Recorded'],
          datasets: [{
            data: hasMajors ? s.majorCounts : [1],
            backgroundColor: hasMajors ? palette.slice(0, s.majorLabels.length) : ['#E2E8F0'],
            borderWidth: 2,
            borderColor: '#ffffff',
            hoverOffset: hasMajors ? 4 : 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { 
              position: 'bottom', 
              labels: { 
                boxWidth: 10, 
                font: { size: 10 },
                generateLabels: function(chart) {
                  const data = chart.data;
                  const isPlaceholder = data.labels[0] === 'No Majors Recorded' || data.labels[0] === 'No Students / Majors';
                  if (data.labels.length && data.datasets.length && !isPlaceholder) {
                    return data.labels.map((label, i) => {
                      const count = data.datasets[0].data[i];
                      return {
                        text: `${label} (${count})`,
                        fillStyle: data.datasets[0].backgroundColor[i] || '#3B82F6',
                        hidden: false,
                        index: i
                      };
                    });
                  }
                  return Chart.defaults.plugins.legend.labels.generateLabels(chart);
                }
              } 
            },
            tooltip: {
              callbacks: {
                label: function(item) {
                  const isPlaceholder = item.chart.data.labels[0] === 'No Majors Recorded' || item.chart.data.labels[0] === 'No Students / Majors';
                  if (isPlaceholder) return ' No major records found';
                  const total = item.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                  const pct = total > 0 ? ((item.raw / total) * 100).toFixed(1) : 0;
                  return ` ${item.label}: ${item.raw} students (${pct}%)`;
                }
              }
            }
          }
        }
      });
    }
  } catch (err) {
    console.error('Error rendering chartMajorDistribution:', err);
  }
}

// 2. Dynamic Course-to-Major Cascading Helper
function updateMajorDropdown(selectedCourse) {
  const majorEl = document.getElementById('filter-major');
  if (!majorEl) return;

  const currentMajor = (majorEl.value || '').toLowerCase().trim();
  const selectedCourseNorm = (selectedCourse || '').toLowerCase().trim();

  // Extract distinct majors and student counts for the chosen course (or all courses)
  const majorCounts = {};
  const records = window.AMS_EXPORTS_CACHE.records || [];
  
  records.forEach(r => {
    const rCourse = (r.course || 'Unspecified Course').toLowerCase().trim();
    if (!selectedCourseNorm || rCourse === selectedCourseNorm) {
      const m = (r.major && r.major.trim()) ? r.major.trim() : 'N/A';
      majorCounts[m] = (majorCounts[m] || 0) + 1;
    }
  });

  const distinctMajors = Object.keys(majorCounts).sort();
  const headerLabel = selectedCourseNorm ? `${selectedCourse.toUpperCase()} Majors` : 'All Majors';
  majorEl.innerHTML = `<option value="">${headerLabel} (${distinctMajors.length})</option>`;
  
  let stillSelected = false;
  distinctMajors.forEach(m => {
    const opt = document.createElement('option');
    opt.value = m;
    opt.textContent = `${m} (${majorCounts[m]})`;
    if (m.toLowerCase().trim() === currentMajor) {
      opt.selected = true;
      stillSelected = true;
    }
    majorEl.appendChild(opt);
  });

  // If the previously selected major doesn't exist in the new course, reset selection
  if (!stillSelected && currentMajor !== '') {
    majorEl.value = '';
  }
}

// ==========================================
// ROSTER PAGINATION & FILTER LOGIC
// ==========================================
let rosterCurrentPage = 1;
let rosterPageSize = 10;
let rosterMatchingRows = [];

function onCourseFilterChange() {
  const courseEl = document.getElementById('filter-course');
  const selectedCourse = courseEl ? courseEl.value : '';
  updateMajorDropdown(selectedCourse);
  applyFilters(true);
}

// 3. Client-side instant filter handler & dynamic chart/KPI updater with pagination
function applyFilters(resetToPageOne = false) {
  if (resetToPageOne) {
    rosterCurrentPage = 1;
  }

  const searchEl = document.getElementById('filter-search');
  const courseEl = document.getElementById('filter-course');
  const majorEl = document.getElementById('filter-major');
  const sectionEl = document.getElementById('filter-section');
  const userEl = document.getElementById('filter-user-status');
  const rateEl = document.getElementById('filter-attendance');

  const searchQuery = (searchEl ? searchEl.value : '').toLowerCase().trim();
  const courseFilter = (courseEl ? courseEl.value : '').toLowerCase().trim();
  const majorFilter = (majorEl ? majorEl.value : '').toLowerCase().trim();
  const sectionFilter = sectionEl ? sectionEl.value : '';
  const userStatusFilter = userEl ? userEl.value : '';
  const rateFilter = rateEl ? rateEl.value : '';

  const rows = document.querySelectorAll('#roster-table-body tr.roster-row');
  let visibleCount = 0;
  const visibleRecords = [];
  rosterMatchingRows = [];

  rows.forEach(row => {
    const sName = row.getAttribute('data-student-name') || '';
    const sId = row.getAttribute('data-student-id') || '';
    const sSec = row.getAttribute('data-section') || '';
    const sCourse = (row.getAttribute('data-course') || '').toLowerCase().trim();
    const sMajor = (row.getAttribute('data-major') || '').toLowerCase().trim();
    const sSearch = (row.getAttribute('data-search') || '').toLowerCase().trim();
    const isReg = row.getAttribute('data-is-registered') || '';
    const rateVal = parseFloat(row.getAttribute('data-rate') || '-1');
    const rowIndex = parseInt(row.getAttribute('data-index'));

    // Search query match
    const matchesSearch = !searchQuery || sSearch.includes(searchQuery);

    // Course match (e.g. bsit)
    const matchesCourse = !courseFilter || sCourse === courseFilter;

    // Major match (e.g. na, im, is)
    const matchesMajor = !majorFilter || sMajor === majorFilter;

    // Section match
    const matchesSection = !sectionFilter || sSec === sectionFilter;

    // User status match
    const matchesUser = !userStatusFilter || isReg === userStatusFilter;

    // Rate match
    let matchesRate = true;
    if (rateFilter === 'high') matchesRate = rateVal >= 90;
    else if (rateFilter === 'mid') matchesRate = rateVal >= 75 && rateVal < 90;
    else if (rateFilter === 'low') matchesRate = rateVal >= 0 && rateVal < 75;
    else if (rateFilter === 'nodata') matchesRate = rateVal < 0;

    if (matchesSearch && matchesCourse && matchesMajor && matchesSection && matchesUser && matchesRate) {
      rosterMatchingRows.push(row);
      visibleCount++;
      if (window.AMS_EXPORTS_CACHE.records && window.AMS_EXPORTS_CACHE.records[rowIndex]) {
        visibleRecords.push(window.AMS_EXPORTS_CACHE.records[rowIndex]);
      }
    } else {
      row.style.display = 'none';
    }
  });

  // Store all filtered records for comprehensive export
  window.AMS_EXPORTS_CACHE.filteredRecords = visibleRecords;

  // Pagination slicing
  const pageSize = parseInt(rosterPageSize, 10) || 10;
  const totalPages = Math.max(1, Math.ceil(visibleCount / pageSize));
  if (rosterCurrentPage > totalPages) rosterCurrentPage = totalPages;
  if (rosterCurrentPage < 1) rosterCurrentPage = 1;

  const startIdx = (rosterCurrentPage - 1) * pageSize;
  const endIdx = Math.min(startIdx + pageSize, visibleCount);

  rosterMatchingRows.forEach((row, idx) => {
    if (idx >= startIdx && idx < endIdx) {
      row.style.display = '';
    } else {
      row.style.display = 'none';
    }
  });

  // Render Pagination Bar
  renderRosterPagination(visibleCount, startIdx, endIdx, totalPages);

  // Update visible counts
  const countEl = document.getElementById('filter-visible-count');
  if (countEl) countEl.textContent = visibleCount;
  
  const scopeEl = document.getElementById('modal-scope-filtered-count');
  if (scopeEl) scopeEl.textContent = `${visibleCount} rows match filters`;

  // Toggle filter empty state
  const emptyRow = document.getElementById('filter-empty-state');
  if (emptyRow) {
    if (visibleCount === 0 && rows.length > 0) {
      emptyRow.classList.remove('hidden');
    } else {
      emptyRow.classList.add('hidden');
    }
  }

  // Recalculate Dynamic Aggregates for Charts & KPIs across ALL matching records
  let totalStudents = visibleRecords.length;
  let registeredCount = 0;
  let totalPresent = 0;
  let totalTardy = 0;
  let totalAbsent = 0;
  const secMap = {};
  const majorMap = {};

  visibleRecords.forEach(r => {
    if (parseInt(r.is_in_users_table) === 1) {
      registeredCount++;
    }
    const p = parseInt(r.present_count || 0);
    const t = parseInt(r.tardy_count || 0);
    const a = parseInt(r.absent_count || 0);
    totalPresent += p;
    totalTardy += t;
    totalAbsent += a;

    const sec = r.section || 'Unassigned';
    if (!secMap[sec]) {
      secMap[sec] = { count: 0, present: 0, tardy: 0, absent: 0 };
    }
    secMap[sec].count++;
    secMap[sec].present += p;
    secMap[sec].tardy += t;
    secMap[sec].absent += a;

    const m = (r.major && r.major.trim()) ? r.major.trim() : 'N/A';
    majorMap[m] = (majorMap[m] || 0) + 1;
  });

  const unregisteredCount = totalStudents - registeredCount;
  const registeredPct = totalStudents > 0 ? ((registeredCount / totalStudents) * 100).toFixed(1) : '0';
  const totalSessionsAll = totalPresent + totalTardy + totalAbsent;
  const overallRate = totalSessionsAll > 0 ? (((totalPresent + totalTardy) / totalSessionsAll) * 100).toFixed(1) : null;

  const secLabels = Object.keys(secMap).sort();
  const secRates = secLabels.map(s => {
    const tot = secMap[s].present + secMap[s].tardy + secMap[s].absent;
    return tot > 0 ? Math.round(((secMap[s].present + secMap[s].tardy) / tot) * 1000) / 10 : 0;
  });

  const majorLabels = Object.keys(majorMap).sort();
  const majorCounts = majorLabels.map(m => majorMap[m]);

  // 1. Update KPI Summary Cards
  const kpiTotal = document.getElementById('kpi-total-students');
  if (kpiTotal) kpiTotal.textContent = totalStudents.toLocaleString();

  const kpiSec = document.getElementById('kpi-sections-count');
  if (kpiSec) kpiSec.textContent = `Across ${secLabels.length} class section${secLabels.length === 1 ? '' : 's'}`;

  const kpiRegPct = document.getElementById('kpi-registered-pct');
  if (kpiRegPct) kpiRegPct.textContent = `${registeredPct}%`;

  const kpiRegCounts = document.getElementById('kpi-registered-counts');
  if (kpiRegCounts) kpiRegCounts.textContent = `(${registeredCount}/${totalStudents})`;

  const kpiUnregDesc = document.getElementById('kpi-unregistered-desc');
  if (kpiUnregDesc) kpiUnregDesc.textContent = `${unregisteredCount} roster-only / unregistered`;

  const kpiRate = document.getElementById('kpi-attendance-rate');
  if (kpiRate) kpiRate.textContent = overallRate !== null ? `${overallRate}%` : '—';

  const kpiSessDesc = document.getElementById('kpi-total-sessions-desc');
  if (kpiSessDesc) kpiSessDesc.textContent = totalSessionsAll > 0 ? `${totalSessionsAll.toLocaleString()} total check-in records` : 'No recorded sessions yet';

  const kpiP = document.getElementById('kpi-present-count');
  if (kpiP) kpiP.textContent = `P: ${totalPresent}`;
  const kpiT = document.getElementById('kpi-tardy-count');
  if (kpiT) kpiT.textContent = `T: ${totalTardy}`;
  const kpiA = document.getElementById('kpi-absent-count');
  if (kpiA) kpiA.textContent = `A: ${totalAbsent}`;

  const pBar = document.getElementById('kpi-bar-present');
  const tBar = document.getElementById('kpi-bar-tardy');
  const aBar = document.getElementById('kpi-bar-absent');
  if (pBar && tBar && aBar) {
    const pWidth = totalSessionsAll > 0 ? (totalPresent / totalSessionsAll) * 100 : 0;
    const tWidth = totalSessionsAll > 0 ? (totalTardy / totalSessionsAll) * 100 : 0;
    const aWidth = totalSessionsAll > 0 ? (totalAbsent / totalSessionsAll) * 100 : 0;
    pBar.style.width = pWidth + '%';
    tBar.style.width = tWidth + '%';
    aBar.style.width = aWidth + '%';
  }

  // 2. Dynamically update Chart 1: Attendance Status Breakdown
  if (window.AMS_EXPORTS_CACHE.charts.status) {
    const chart = window.AMS_EXPORTS_CACHE.charts.status;
    const hasAttendance = totalSessionsAll > 0;
    chart.data.labels = hasAttendance ? ['Present', 'Tardy', 'Absent'] : ['No Attendance Logged Yet'];
    chart.data.datasets[0].data = hasAttendance ? [totalPresent, totalTardy, totalAbsent] : [1];
    chart.data.datasets[0].backgroundColor = hasAttendance ? ['#10B981', '#F59E0B', '#EF4444'] : ['#E2E8F0'];
    chart.data.datasets[0].hoverOffset = hasAttendance ? 4 : 0;
    chart.update();
  }

  // 3. Dynamically update Chart 2: Section Performance Rates
  if (window.AMS_EXPORTS_CACHE.charts.section) {
    const chart = window.AMS_EXPORTS_CACHE.charts.section;
    const hasSections = secLabels.length > 0;
    chart.data.labels = hasSections ? secLabels.map(s => 'Sec ' + s) : ['No Sections'];
    chart.data.datasets[0].data = hasSections ? secRates : [0];
    chart.update();
  }

  // 4. Dynamically update Chart 3: Students by Major
  if (window.AMS_EXPORTS_CACHE.charts.major) {
    const chart = window.AMS_EXPORTS_CACHE.charts.major;
    const hasMajors = majorLabels.length > 0;
    const palette = ['#3B82F6', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899', '#06B6D4', '#6366F1', '#14B8A6', '#F97316'];
    chart.data.labels = hasMajors ? majorLabels : ['No Majors Recorded'];
    chart.data.datasets[0].data = hasMajors ? majorCounts : [1];
    chart.data.datasets[0].backgroundColor = hasMajors ? palette.slice(0, majorLabels.length) : ['#E2E8F0'];
    chart.data.datasets[0].hoverOffset = hasMajors ? 4 : 0;
    chart.update();
  }
}

/**
 * Render Roster Pagination UI Buttons & Stats
 */
function renderRosterPagination(totalMatching, startIdx, endIdx, totalPages) {
  const bar = document.getElementById('roster-pagination-bar');
  const startEl = document.getElementById('roster-page-start');
  const endEl = document.getElementById('roster-page-end');
  const totalEl = document.getElementById('roster-page-total');
  const controls = document.getElementById('roster-pagination-controls');

  if (!bar || !controls) return;

  if (totalMatching === 0) {
    bar.classList.add('hidden');
    return;
  }
  bar.classList.remove('hidden');

  if (startEl) startEl.textContent = (startIdx + 1).toLocaleString();
  if (endEl) endEl.textContent = endIdx.toLocaleString();
  if (totalEl) totalEl.textContent = totalMatching.toLocaleString();

  if (totalPages <= 1) {
    controls.innerHTML = `
      <span class="text-[11px] font-semibold text-slate-400 px-2">Page 1 of 1</span>
    `;
    return;
  }

  let html = '';

  // Previous button
  const prevDisabled = rosterCurrentPage <= 1;
  html += `
    <button type="button" 
            onclick="changeRosterPage(${rosterCurrentPage - 1})"
            ${prevDisabled ? 'disabled' : ''}
            class="px-3 py-1.5 rounded-xl border text-xs font-semibold transition inline-flex items-center gap-1.5 ${prevDisabled ? 'border-slate-200/60 text-slate-300 cursor-not-allowed bg-slate-50' : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer shadow-2xs'}">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      <span>Previous</span>
    </button>
  `;

  // Page Numbers with Smart Window (max 5 buttons visible)
  const maxButtons = 5;
  let startPage = Math.max(1, rosterCurrentPage - Math.floor(maxButtons / 2));
  let endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage + 1 < maxButtons) {
    startPage = Math.max(1, endPage - maxButtons + 1);
  }

  if (startPage > 1) {
    html += `
      <button type="button" onclick="changeRosterPage(1)" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-xs font-semibold text-slate-700 transition cursor-pointer shadow-2xs flex items-center justify-center">1</button>
    `;
    if (startPage > 2) {
      html += `<span class="px-1 text-slate-400 font-bold">…</span>`;
    }
  }

  for (let p = startPage; p <= endPage; p++) {
    const isActive = p === rosterCurrentPage;
    if (isActive) {
      html += `
        <button type="button" class="w-8 h-8 rounded-xl bg-[#1e3b8a] text-xs font-black text-white shadow-xs flex items-center justify-center">${p}</button>
      `;
    } else {
      html += `
        <button type="button" onclick="changeRosterPage(${p})" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-xs font-semibold text-slate-700 transition cursor-pointer shadow-2xs flex items-center justify-center">${p}</button>
      `;
    }
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) {
      html += `<span class="px-1 text-slate-400 font-bold">…</span>`;
    }
    html += `
      <button type="button" onclick="changeRosterPage(${totalPages})" class="w-8 h-8 rounded-xl border border-slate-200 bg-white hover:bg-slate-100 text-xs font-semibold text-slate-700 transition cursor-pointer shadow-2xs flex items-center justify-center">${totalPages}</button>
    `;
  }

  // Next button
  const nextDisabled = rosterCurrentPage >= totalPages;
  html += `
    <button type="button" 
            onclick="changeRosterPage(${rosterCurrentPage + 1})"
            ${nextDisabled ? 'disabled' : ''}
            class="px-3 py-1.5 rounded-xl border text-xs font-semibold transition inline-flex items-center gap-1.5 ${nextDisabled ? 'border-slate-200/60 text-slate-300 cursor-not-allowed bg-slate-50' : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer shadow-2xs'}">
      <span>Next</span>
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
  `;

  controls.innerHTML = html;
}

function changeRosterPage(page) {
  rosterCurrentPage = page;
  applyFilters(false);
  const tableEl = document.getElementById('roster-attendance-table');
  if (tableEl) {
    tableEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

function resetFilters() {
  const search = document.getElementById('filter-search');
  const course = document.getElementById('filter-course');
  const section = document.getElementById('filter-section');
  const user = document.getElementById('filter-user-status');
  const rate = document.getElementById('filter-attendance');
  if (search) search.value = '';
  if (course) course.value = '';
  if (section) section.value = '';
  if (user) user.value = '';
  if (rate) rate.value = '';

  // Reset major options back to all majors with counts
  updateMajorDropdown('');
  rosterCurrentPage = 1;

  applyFilters(true);
}

// 3. Modal management & Format selection
function openExportModal() {
  applyFilters(false);
  const modal = document.getElementById('export-modal-backdrop');
  if (modal) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }
}

function closeExportModal() {
  const modal = document.getElementById('export-modal-backdrop');
  if (modal) {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }
}

function selectExportFormat(fmt) {
  const formats = ['excel', 'word', 'pdf'];
  formats.forEach(f => {
    const card = document.getElementById(`format-card-${f}`);
    const input = card ? card.querySelector('input') : null;
    if (f === fmt) {
      if (input) input.checked = true;
      if (card) {
        card.classList.remove('border-slate-200');
        if (f === 'excel') card.className = 'cursor-pointer border-2 border-emerald-500 bg-emerald-50/40 rounded-2xl p-3 flex flex-col items-center justify-center text-center gap-2 transition';
        if (f === 'word') card.className = 'cursor-pointer border-2 border-blue-500 bg-blue-50/40 rounded-2xl p-3 flex flex-col items-center justify-center text-center gap-2 transition';
        if (f === 'pdf') card.className = 'cursor-pointer border-2 border-rose-500 bg-rose-50/40 rounded-2xl p-3 flex flex-col items-center justify-center text-center gap-2 transition';
      }
    } else {
      if (input) input.checked = false;
      if (card) {
        card.className = 'cursor-pointer border-2 border-slate-200 hover:border-slate-300 rounded-2xl p-3 flex flex-col items-center justify-center text-center gap-2 transition';
      }
    }
  });
}

// Direct 1-click Quick Export from Header
function quickExport(format) {
  selectExportFormat(format);
  const records = getExportRecords('filtered');
  if (!records || records.length === 0) {
    alert('No student records found to export with the current filters.');
    return;
  }
  const filename = 'AMS_Attendance_Roster_Report_' + (new Date().toISOString().split('T')[0]);
  const snapshots = getChartSnapshots();

  if (format === 'excel') exportToExcel(records, snapshots, true, filename);
  else if (format === 'word') exportToWord(records, snapshots, true, filename);
  else if (format === 'pdf') exportToPdf(records, snapshots, true, filename);
}

// Get Chart snapshots as Base64 Data URLs
function getChartSnapshots() {
  const snapshots = {};
  try {
    if (window.AMS_EXPORTS_CACHE.charts.status) {
      snapshots.status = window.AMS_EXPORTS_CACHE.charts.status.toBase64Image('image/png', 1.0);
    }
    if (window.AMS_EXPORTS_CACHE.charts.section) {
      snapshots.section = window.AMS_EXPORTS_CACHE.charts.section.toBase64Image('image/png', 1.0);
    }
    if (window.AMS_EXPORTS_CACHE.charts.major) {
      snapshots.major = window.AMS_EXPORTS_CACHE.charts.major.toBase64Image('image/png', 1.0);
    }
  } catch (e) {
    console.warn('Error capturing chart snapshots:', e);
  }
  return snapshots;
}

// Get the dataset records for export based on scope
function getExportRecords(scope) {
  if (scope === 'all') {
    return window.AMS_EXPORTS_CACHE.records || [];
  }
  // Scope is filtered: use the complete filtered dataset from cache
  if (window.AMS_EXPORTS_CACHE.filteredRecords && window.AMS_EXPORTS_CACHE.filteredRecords.length > 0) {
    return window.AMS_EXPORTS_CACHE.filteredRecords;
  }
  // Fallback: grab from DOM
  const visibleRows = document.querySelectorAll('#roster-table-body tr.roster-row:not([style*="display: none"])');
  const indices = Array.from(visibleRows).map(r => parseInt(r.getAttribute('data-index')));
  return indices.map(idx => window.AMS_EXPORTS_CACHE.records[idx]).filter(Boolean);
}

// Execute Export from Modal
function executeExportProcess() {
  const format = document.querySelector('input[name="export_format"]:checked')?.value || 'excel';
  const scope = document.querySelector('input[name="export_scope"]:checked')?.value || 'filtered';
  const contentMode = document.querySelector('input[name="export_content_type"]:checked')?.value || 'with_charts';
  const includeCharts = contentMode === 'with_charts';
  let filename = (document.getElementById('export-filename')?.value || 'AMS_Report').trim();
  if (!filename) filename = 'AMS_Attendance_Roster_Report_' + (new Date().toISOString().split('T')[0]);

  const records = getExportRecords(scope);
  if (!records || records.length === 0) {
    alert('No student records found to export with the current filter.');
    return;
  }

  const chartImages = includeCharts ? getChartSnapshots() : {};

  if (format === 'excel') {
    exportToExcel(records, chartImages, includeCharts, filename);
  } else if (format === 'word') {
    exportToWord(records, chartImages, includeCharts, filename);
  } else if (format === 'pdf') {
    exportToPdf(records, chartImages, includeCharts, filename);
  }

  closeExportModal();
}

// 1. EXCEL EXPORT GENERATOR
function exportToExcel(records, charts, includeCharts, filename) {
  let html = `
  <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
      body { font-family: Calibri, Arial, sans-serif; }
      .header-title { font-size: 16pt; font-weight: bold; color: #1e3a8a; }
      table { border-collapse: collapse; width: 100%; margin-top: 10px; }
      th { background-color: #2563eb; color: #ffffff; font-weight: bold; text-align: left; padding: 8px; border: 1px solid #1d4ed8; }
      td { padding: 6px 8px; border: 1px solid #cbd5e1; font-size: 10pt; }
      .badge-reg { background-color: #d1fae5; color: #065f46; font-weight: bold; }
      .badge-roster { background-color: #fef3c7; color: #92400e; font-weight: bold; }
    </style>
  </head>
  <body>
    <div class="header-title">BESTLINK COLLEGE OF THE PHILIPPINES</div>
    <div><strong>Attendance Management System — Institutional Roster & Attendance Report</strong></div>
    <div>Generated on: ${new Date().toLocaleString()} | Total Records: ${records.length}</div>
    <br/>
  `;

  if (includeCharts && (charts.status || charts.section || charts.major)) {
    html += `
      <table style="border:none; margin-bottom:15px;">
        <tr>
          ${charts.status ? `<td style="border:none; text-align:center;"><strong>Attendance Distribution</strong><br/><img src="${charts.status}" width="280" /></td>` : ''}
          ${charts.section ? `<td style="border:none; text-align:center;"><strong>Section Performance Rates</strong><br/><img src="${charts.section}" width="320" /></td>` : ''}
          ${charts.major ? `<td style="border:none; text-align:center;"><strong>Students by Major</strong><br/><img src="${charts.major}" width="280" /></td>` : ''}
        </tr>
      </table>
      <br/>
    `;
  }

  html += `
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Student ID / Number</th>
          <th>Last Name</th>
          <th>First Name</th>
          <th>Section</th>
          <th>Course & Year</th>
          <th>Subject / Room</th>
          <th>Schedule</th>
          <th>In Users Table?</th>
          <th>User Email</th>
          <th>Present</th>
          <th>Tardy</th>
          <th>Absent</th>
          <th>Total Sessions</th>
          <th>Attendance Rate (%)</th>
        </tr>
      </thead>
      <tbody>
  `;

  records.forEach((r, idx) => {
    const isReg = parseInt(r.is_in_users_table) === 1;
    const studentNum = r.user_student_number || r.roster_student_id || 'N/A';
    const rateDisplay = (parseInt(r.total_attendance) > 0 && r.attendance_rate !== null) ? r.attendance_rate + '%' : '—';
    html += `
      <tr>
        <td>${idx + 1}</td>
        <td>${studentNum}</td>
        <td>${r.last_name || ''}</td>
        <td>${r.first_name || ''}</td>
        <td>${r.section || ''}</td>
        <td>${r.course || 'BSIT'} ${r.year_level || ''}</td>
        <td>${r.course_code || ''} (Rm ${r.room_number || ''})</td>
        <td>${r.schedule_day || ''} ${r.scheduled_time || ''}</td>
        <td class="${isReg ? 'badge-reg' : 'badge-roster'}">${isReg ? 'Registered (ID: ' + r.user_id + ')' : 'Roster Only'}</td>
        <td>${r.user_email || '—'}</td>
        <td>${r.present_count || 0}</td>
        <td>${r.tardy_count || 0}</td>
        <td>${r.absent_count || 0}</td>
        <td>${r.total_attendance || 0}</td>
        <td>${rateDisplay}</td>
      </tr>
    `;
  });

  html += `
      </tbody>
    </table>
  </body>
  </html>
  `;

  const blob = new Blob([html], { type: 'application/vnd.ms-excel;charset=utf-8' });
  triggerFileDownload(blob, `${filename}.xls`);
}

// 2. WORD EXPORT GENERATOR
function exportToWord(records, charts, includeCharts, filename) {
  let html = `
  <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Class Roster Attendance Report</title>
    <style>
      body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; margin: 20px; }
      .doc-title { font-size: 18pt; font-weight: bold; color: #1e3a8a; text-align: center; }
      .doc-subtitle { font-size: 11pt; color: #64748b; text-align: center; margin-bottom: 20px; }
      .meta-box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; margin-bottom: 20px; border-radius: 6px; }
      table { border-collapse: collapse; width: 100%; font-size: 9pt; margin-top: 10px; }
      th { background-color: #1e40af; color: #ffffff; font-weight: bold; text-align: left; padding: 6px 8px; border: 1px solid #1e3a8a; }
      td { padding: 5px 8px; border: 1px solid #e2e8f0; }
      .registered { color: #047857; font-weight: bold; }
      .unregistered { color: #b45309; font-weight: bold; }
    </style>
  </head>
  <body>
    <div class="doc-title">BESTLINK COLLEGE OF THE PHILIPPINES</div>
    <div class="doc-subtitle">Attendance Management System — Official Class Roster & Attendance Ledger</div>

    <div class="meta-box">
      <strong>Report Summary:</strong><br/>
      Generated: ${new Date().toLocaleString()} &bull; Total Enrolled Students in Report: <strong>${records.length}</strong><br/>
      Includes Class Roster details, user registration verification, and session counts (Present / Tardy / Absent).
    </div>
  `;

  if (includeCharts && (charts.status || charts.section || charts.major)) {
    html += `
      <h3 style="color:#1e3a8a; border-bottom:1px solid #e2e8f0; padding-bottom:4px;">Visual Analytics & Compliance Graphs</h3>
      <table style="border:none; margin-bottom:20px;">
        <tr>
          ${charts.status ? `<td style="border:none; text-align:center;"><img src="${charts.status}" width="260" /><br/><strong>Attendance Breakdown</strong></td>` : ''}
          ${charts.section ? `<td style="border:none; text-align:center;"><img src="${charts.section}" width="300" /><br/><strong>Section Rates</strong></td>` : ''}
          ${charts.major ? `<td style="border:none; text-align:center;"><img src="${charts.major}" width="260" /><br/><strong>Students by Major</strong></td>` : ''}
        </tr>
      </table>
    `;
  }

  html += `
    <h3 style="color:#1e3a8a; border-bottom:1px solid #e2e8f0; padding-bottom:4px;">Class Roster & Attendance Ledger</h3>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Student No.</th>
          <th>Student Name</th>
          <th>Section</th>
          <th>Subject / Room</th>
          <th>Schedule</th>
          <th>In Users Table</th>
          <th>P / T / A</th>
          <th>Rate</th>
        </tr>
      </thead>
      <tbody>
  `;

  records.forEach((r, idx) => {
    const isReg = parseInt(r.is_in_users_table) === 1;
    const studentNum = r.user_student_number || r.roster_student_id || 'N/A';
    const rateDisplay = (parseInt(r.total_attendance) > 0 && r.attendance_rate !== null) ? r.attendance_rate + '%' : '—';
    html += `
      <tr>
        <td>${idx + 1}</td>
        <td>${studentNum}</td>
        <td><strong>${r.last_name || ''}, ${r.first_name || ''}</strong></td>
        <td>${r.section || ''} (${r.course || 'BSIT'})</td>
        <td>${r.course_code || ''} - Rm ${r.room_number || ''}</td>
        <td>${r.schedule_day || ''} ${r.scheduled_time || ''}</td>
        <td class="${isReg ? 'registered' : 'unregistered'}">${isReg ? 'Yes (UID: ' + r.user_id + ')' : 'No (Roster Only)'}</td>
        <td>${r.present_count || 0} / ${r.tardy_count || 0} / ${r.absent_count || 0}</td>
        <td><strong>${rateDisplay}</strong></td>
      </tr>
    `;
  });

  html += `
      </tbody>
    </table>
  </body>
  </html>
  `;

  const blob = new Blob([html], { type: 'application/msword;charset=utf-8' });
  triggerFileDownload(blob, `${filename}.doc`);
}

// 3. PDF / PRINT EXPORT GENERATOR
function exportToPdf(records, charts, includeCharts, filename) {
  const printWin = window.open('', '_blank', 'width=1050,height=800');
  if (!printWin) {
    alert('Please allow popups to generate the print-ready PDF export.');
    return;
  }

  let html = `
  <!DOCTYPE html>
  <html>
  <head>
    <title>${filename}</title>
    <meta charset="utf-8">
    <style>
      @page { size: portrait; margin: 15mm; }
      body { font-family: system-ui, -apple-system, sans-serif; color: #0f172a; margin: 0; font-size: 11px; }
      .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #2563eb; padding-bottom: 12px; margin-bottom: 15px; }
      .title { font-size: 18px; font-weight: 800; color: #1e3a8a; }
      .subtitle { font-size: 11px; color: #64748b; margin-top: 2px; }
      .charts-grid { display: flex; gap: 12px; justify-content: space-between; margin-bottom: 20px; page-break-inside: avoid; }
      .chart-card { flex: 1; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px; text-align: center; }
      .chart-card img { max-width: 100%; height: auto; }
      table { width: 100%; border-collapse: collapse; font-size: 10px; }
      th { background-color: #f1f5f9; color: #334155; font-weight: 700; text-align: left; padding: 6px 8px; border-bottom: 2px solid #cbd5e1; }
      td { padding: 5px 8px; border-bottom: 1px solid #f1f5f9; }
      .badge-reg { color: #047857; font-weight: 700; }
      .badge-roster { color: #b45309; font-weight: 700; }
      @media print {
        button { display: none; }
      }
    </style>
  </head>
  <body>
    <div style="margin-bottom:10px;">
      <button onclick="window.print()" style="background:#2563eb; color:#fff; border:none; padding:8px 16px; border-radius:6px; font-weight:bold; cursor:pointer;">Print / Save as PDF</button>
    </div>
    <div class="header">
      <div>
        <div class="title">Bestlink College of the Philippines</div>
        <div class="subtitle">Attendance Management System &bull; Official Class Roster &amp; Attendance Report</div>
      </div>
      <div style="text-align:right; font-size:10px; color:#64748b;">
        <div>Date: ${new Date().toLocaleDateString()}</div>
        <div>Records: ${records.length} Students</div>
      </div>
    </div>
  `;

  if (includeCharts && (charts.status || charts.section || charts.major)) {
    html += `
      <div class="charts-grid">
        ${charts.status ? `<div class="chart-card"><div style="font-weight:bold; margin-bottom:4px;">Attendance Breakdown</div><img src="${charts.status}" /></div>` : ''}
        ${charts.section ? `<div class="chart-card"><div style="font-weight:bold; margin-bottom:4px;">Section Performance</div><img src="${charts.section}" /></div>` : ''}
        ${charts.major ? `<div class="chart-card"><div style="font-weight:bold; margin-bottom:4px;">Students by Major</div><img src="${charts.major}" /></div>` : ''}
      </div>
    `;
  }

  html += `
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Student ID</th>
          <th>Student Name</th>
          <th>Section</th>
          <th>Course</th>
          <th>Subject / Schedule</th>
          <th>In Users Table</th>
          <th>P / T / A</th>
          <th>Attendance Rate</th>
        </tr>
      </thead>
      <tbody>
  `;

  records.forEach((r, idx) => {
    const isReg = parseInt(r.is_in_users_table) === 1;
    const studentNum = r.user_student_number || r.roster_student_id || 'N/A';
    const rateDisplay = (parseInt(r.total_attendance) > 0 && r.attendance_rate !== null) ? r.attendance_rate + '%' : '—';
    html += `
      <tr>
        <td>${idx + 1}</td>
        <td>${studentNum}</td>
        <td><strong>${r.last_name || ''}, ${r.first_name || ''}</strong></td>
        <td>${r.section || ''}</td>
        <td>${r.course || 'BSIT'} Yr ${r.year_level || '3'}</td>
        <td>${r.course_code || ''} (${r.schedule_day || ''} ${r.scheduled_time || ''})</td>
        <td class="${isReg ? 'badge-reg' : 'badge-roster'}">${isReg ? 'Registered' : 'Roster Only'}</td>
        <td>${r.present_count || 0} / ${r.tardy_count || 0} / ${r.absent_count || 0}</td>
        <td><strong>${rateDisplay}</strong></td>
      </tr>
    `;
  });

  html += `
      </tbody>
    </table>
    <script>
      window.onload = function() {
        setTimeout(function() {
          window.print();
        }, 500);
      };
    <\/script>
  </body>
  </html>
  `;

  printWin.document.open();
  printWin.document.write(html);
  printWin.document.close();
}

// Helper to trigger browser download of Blob
function triggerFileDownload(blob, filename) {
  const url = window.URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.style.display = 'none';
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  setTimeout(() => {
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
  }, 1000);
}

// ==========================================
// REALTIME LIVE SYNC ENGINE
// ==========================================
let realtimeTimer = null;
const realtimeIntervalMs = 5000; // Poll every 5s
let isRealtimeActive = true;
let lastCheckinCount = <?php echo $totalSessionsAll; ?>;

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
}

function renderTableRowHtml(row, index) {
  const fullName = ((row.first_name || '') + ' ' + (row.last_name || '')).trim();
  const isInUsers = parseInt(row.is_in_users_table) === 1;
  const hasData = parseInt(row.total_attendance || 0) > 0 && row.attendance_rate !== null;
  const rate = hasData ? parseFloat(row.attendance_rate) : null;
  
  let rateBadgeClass = 'bg-slate-50 text-slate-400 border-slate-200 font-semibold';
  if (rate !== null) {
    if (rate >= 90) rateBadgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200/80';
    else if (rate >= 75) rateBadgeClass = 'bg-amber-50 text-amber-700 border-amber-200/80';
    else rateBadgeClass = 'bg-rose-50 text-rose-700 border-rose-200/80';
  }
  const studentNum = String(row.user_student_number || row.roster_student_id || 'N/A');
  const fInitial = row.first_name ? String(row.first_name)[0] : 'S';
  const lInitial = row.last_name ? String(row.last_name)[0] : 'N';
  const initials = (fInitial + lInitial).toUpperCase();
  const courseName = (row.course && String(row.course).trim()) ? String(row.course).trim() : 'BSIT';
  const majorName = (row.major && String(row.major).trim()) ? String(row.major).trim() : 'N/A';
  const yearLvl = String(row.year_level || '3');
  const courseCode = String(row.course_code || 'IT301');
  const courseTitle = String(row.course_title || 'Web Systems');
  const roomNum = String(row.room_number || '402');
  const schedDay = String(row.schedule_day || 'Mon');
  const schedTime = String(row.scheduled_time || '08:00');
  const userEmail = String(row.user_email || '');
  const searchStr = (fullName + ' ' + studentNum + ' ' + (row.section || '') + ' ' + courseName + ' ' + majorName + ' ' + courseCode + ' ' + courseTitle + ' ' + userEmail).toLowerCase();

  return `
    <tr class="hover:bg-slate-50/60 transition roster-row" 
        data-index="${index}"
        data-student-name="${escapeHtml(fullName.toLowerCase())}"
        data-student-id="${escapeHtml(studentNum.toLowerCase())}"
        data-section="${escapeHtml(String(row.section || ''))}"
        data-course="${escapeHtml(courseName.toLowerCase())}"
        data-major="${escapeHtml(majorName.toLowerCase())}"
        data-search="${escapeHtml(searchStr)}"
        data-email="${escapeHtml(userEmail.toLowerCase())}"
        data-is-registered="${isInUsers ? 'registered' : 'unregistered'}"
        data-rate="${rate !== null ? rate : '-1'}">
      
      <!-- Student Info -->
      <td class="py-3 px-4">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-xl ${isInUsers ? 'bg-blue-50 text-blue-700 border border-blue-200/60' : 'bg-slate-100 text-slate-600 border border-slate-200'} font-black flex items-center justify-center text-xs shrink-0 shadow-2xs">
            ${escapeHtml(initials)}
          </div>
          <div class="min-w-0 flex-1">
            <div class="font-bold text-slate-900 text-xs sm:text-sm truncate max-w-[170px] sm:max-w-[210px]" title="${escapeHtml(fullName)}">
              ${escapeHtml(fullName)}
            </div>
            <div class="text-[11px] text-slate-400 font-mono flex items-center gap-1.5 truncate max-w-[170px] sm:max-w-[210px]" title="Student ID: ${escapeHtml(studentNum)}">
              <span>ID: ${escapeHtml(studentNum)}</span>
            </div>
          </div>
        </div>
      </td>

      <!-- Section & Academic Details -->
      <td class="py-3 px-4 min-w-0">
        <div class="flex items-center gap-1.5 flex-wrap">
          <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200/60">
            Sec ${escapeHtml(String(row.section || ''))}
          </span>
        </div>
        <div class="text-[11px] text-slate-500 font-medium truncate max-w-[160px] sm:max-w-[190px] mt-1" title="${escapeHtml(courseName + ' • Major: ' + majorName + ' • Yr ' + yearLvl)}">
          <span class="font-bold text-slate-700">${escapeHtml(courseName)}</span>
          ${row.major ? `&bull; <span class="text-slate-600">${escapeHtml(majorName)}</span>` : ''}
          &bull; Yr ${escapeHtml(yearLvl)}
        </div>
      </td>

      <!-- Schedule & Course -->
      <td class="py-3 px-4 min-w-0">
        <div class="font-semibold text-slate-800 text-xs truncate max-w-[180px] sm:max-w-[230px]" title="${escapeHtml(courseCode + ' - ' + courseTitle)}">
          <span class="font-bold text-slate-900">${escapeHtml(courseCode)}</span>
          <span class="text-slate-600">- ${escapeHtml(courseTitle)}</span>
        </div>
        <div class="text-[11px] text-slate-400 truncate max-w-[180px] sm:max-w-[230px] mt-0.5" title="Room ${escapeHtml(roomNum)} • ${escapeHtml(schedDay + ' ' + schedTime)}">
          Rm ${escapeHtml(roomNum)} &bull; ${escapeHtml(schedDay)} ${escapeHtml(schedTime)}
        </div>
      </td>

      <!-- In Users Table? (Account Status) -->
      <td class="py-3 px-4 text-center min-w-0">
        ${isInUsers 
          ? `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/70">
              <svg class="w-3 h-3 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              Registered
            </span>
            ${userEmail ? `<div class="text-[10px] text-slate-400 mt-0.5 truncate max-w-[130px] mx-auto" title="${escapeHtml(userEmail)}">${escapeHtml(userEmail)}</div>` : ''}`
          : `<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
              <span class="w-1.5 h-1.5 rounded-full bg-slate-400 shrink-0"></span>
              Roster Only
            </span>`
        }
      </td>

      <!-- Attendance Counts (Sessions) -->
      <td class="py-3 px-4 text-center">
        <div class="inline-flex items-center gap-1.5 bg-slate-50/80 px-2.5 py-1 rounded-lg border border-slate-200/60 font-semibold text-xs">
          <span class="text-emerald-600 font-bold" title="Present">${row.present_count || 0}P</span>
          <span class="text-slate-300">/</span>
          <span class="text-amber-600 font-bold" title="Tardy">${row.tardy_count || 0}T</span>
          <span class="text-slate-300">/</span>
          <span class="text-rose-600 font-bold" title="Absent">${row.absent_count || 0}A</span>
          <span class="text-slate-400 text-[10px] font-normal ml-0.5" title="Total Sessions">(${row.total_attendance || 0})</span>
        </div>
      </td>

      <!-- Attendance Rate -->
      <td class="py-3 px-4 text-right">
        <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border ${rateBadgeClass}">
          <span>${rate !== null ? rate.toFixed(1) + '%' : '—'}</span>
        </div>
      </td>
    </tr>
  `;
}


async function syncRealtimeData(isManual = false) {
  const spinner = document.getElementById('sync-spinner-icon');
  if (spinner) spinner.classList.add('animate-spin');

  try {
    const res = await fetch('/exports?ajax=1&refresh=1', { cache: 'no-store' });
    if (!res.ok) throw new Error('HTTP ' + res.status);
    const json = await res.json();
    
    if (json && json.status === 'success' && Array.isArray(json.data)) {
      window.AMS_EXPORTS_CACHE.records = json.data;
      if (json.summary) {
        window.AMS_EXPORTS_CACHE.summary = Object.assign(window.AMS_EXPORTS_CACHE.summary || {}, json.summary);
      }

      // Check if attendance session counts or roster size changed
      const newTotalCheckins = (json.summary?.totalPresent || 0) + (json.summary?.totalTardy || 0) + (json.summary?.totalAbsent || 0);
      const currentRowsCount = document.querySelectorAll('#roster-table-body tr.roster-row').length;
      const hasChanges = newTotalCheckins !== lastCheckinCount || json.data.length !== currentRowsCount;
      
      // Update table body DOM
      const tbody = document.getElementById('roster-table-body');
      const emptyRow = document.getElementById('filter-empty-state');
      if (tbody) {
        let rowsHtml = emptyRow ? emptyRow.outerHTML : '';
        json.data.forEach((row, idx) => {
          rowsHtml += renderTableRowHtml(row, idx);
        });
        tbody.innerHTML = rowsHtml;
      }

      // Re-apply filters which dynamically updates Charts & KPI summary cards
      const courseEl = document.getElementById('filter-course');
      updateMajorDropdown(courseEl ? courseEl.value : '');
      applyFilters();

      // Update last sync timer
      const timeEl = document.getElementById('realtime-last-synced');
      if (timeEl) {
        const d = new Date();
        timeEl.textContent = `• ${d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
      lastCheckinCount = newTotalCheckins;
    }
  } catch (err) {
    console.warn('Realtime sync error:', err);
  } finally {
    if (spinner) {
      setTimeout(() => spinner.classList.remove('animate-spin'), 400);
    }
  }
}

function toggleRealtimeSync() {
  isRealtimeActive = !isRealtimeActive;
  const label = document.getElementById('realtime-status-label');
  const btn = document.getElementById('btn-realtime-toggle');
  const pulse = document.getElementById('realtime-pulse');
  const dot = document.getElementById('realtime-dot');

  if (isRealtimeActive) {
    if (label) label.textContent = '(5s)';
    if (btn) btn.textContent = 'Pause';
    if (pulse) pulse.className = 'animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75';
    if (dot) dot.className = 'relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500';
    startRealtimePolling();
    syncRealtimeData(true);
  } else {
    if (label) label.textContent = 'Paused';
    if (btn) btn.textContent = 'Resume';
    if (pulse) pulse.className = 'hidden';
    if (dot) dot.className = 'relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-400';
    if (realtimeTimer) clearInterval(realtimeTimer);
  }
}

function startRealtimePolling() {
  if (realtimeTimer) clearInterval(realtimeTimer);
  realtimeTimer = setInterval(() => {
    if (isRealtimeActive && document.visibilityState !== 'hidden') {
      syncRealtimeData(false);
    }
  }, realtimeIntervalMs);
}

// Bind DOM Events on Load
document.addEventListener('DOMContentLoaded', () => {
  initExportCharts();
  applyFilters(true);
  startRealtimePolling();

  // Escape key to close modal
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeExportModal();
  });
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
