<?php
$page_title = 'My Assigned Classes & Student Rosters';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$db = Database::getConnection();

// Resolve active teacher ID
$teacherId = 2;
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['teacher_id'])) {
    $teacherId = (int)$_SESSION['teacher_id'];
} elseif (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
    $teacherId = (int)$_SESSION['user_id'];
}

// 1. Fetch assigned classes from class_roster
$classStmt = $db->prepare("
    SELECT 
        course_code,
        course_title,
        course,
        section,
        year_level,
        schedule_day,
        scheduled_time,
        room_number,
        COUNT(DISTINCT student_id) AS enrolled_count
    FROM class_roster
    WHERE teacher_id = ? AND section IS NOT NULL AND section != ''
    GROUP BY course_code, course_title, course, section, year_level, schedule_day, scheduled_time, room_number
    ORDER BY course_code ASC, section ASC
");
$classStmt->execute([$teacherId]);
$classes = $classStmt->fetchAll(PDO::FETCH_ASSOC);

// If teacher has no classes explicitly assigned, fallback to all active sections in class_roster
if (empty($classes)) {
    $classStmtFallback = $db->query("
        SELECT 
            course_code,
            course_title,
            course,
            section,
            year_level,
            schedule_day,
            scheduled_time,
            room_number,
            COUNT(DISTINCT student_id) AS enrolled_count
        FROM class_roster
        WHERE section IS NOT NULL AND section != ''
        GROUP BY course_code, course_title, course, section, year_level, schedule_day, scheduled_time, room_number
        ORDER BY course_code ASC, section ASC
    ");
    $classes = $classStmtFallback->fetchAll(PDO::FETCH_ASSOC);
}

// 2. Fetch session count & attendance metrics per section using batch queries
$totalEnrolledAll = 0;
$totalSessionsAll = 0;
$allSections = [];
$allPrograms = [];
$allYears = [];
$totalPresentAll = 0;
$totalLogsAll = 0;

// Batch fetch session counts by section
$sessionCountsBySection = [];
try {
    $sessCountStmt = $db->query("
        SELECT section, COUNT(*) as cnt 
        FROM qr_sessions 
        WHERE section IS NOT NULL AND section != '' 
        GROUP BY section
    ");
    if ($sessCountStmt) {
        while ($row = $sessCountStmt->fetch(PDO::FETCH_ASSOC)) {
            $sessionCountsBySection[$row['section']] = (int)$row['cnt'];
        }
    }
} catch (Throwable $e) {}

// Batch fetch attendance summary by section/subject
$attStatsBySection = [];
try {
    $secAttStmt = $db->query("
        SELECT 
            COALESCE(qs.section, a.section, a.subject) as sec_key,
            COUNT(*) as total_records,
            SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN a.status = 'tardy' THEN 1 ELSE 0 END) as tardy_count
        FROM attendance a
        LEFT JOIN qr_sessions qs ON qs.qr_session_id = a.qr_session_id
        GROUP BY COALESCE(qs.section, a.section, a.subject)
    ");
    if ($secAttStmt) {
        while ($row = $secAttStmt->fetch(PDO::FETCH_ASSOC)) {
            if (!empty($row['sec_key'])) {
                $attStatsBySection[$row['sec_key']] = $row;
            }
        }
    }
} catch (Throwable $e) {}

foreach ($classes as &$cls) {
    $sec = $cls['section'];
    $allSections[$sec] = $sec;
    if (!empty($cls['course'])) $allPrograms[$cls['course']] = $cls['course'];
    if (!empty($cls['year_level'])) $allYears[$cls['year_level']] = $cls['year_level'];
    $totalEnrolledAll += (int)($cls['enrolled_count'] ?? 0);

    $sessionCount = $sessionCountsBySection[$sec] ?? 0;
    $totalSessionsAll += $sessionCount;

    $attRow = $attStatsBySection[$sec] ?? ($attStatsBySection[$cls['course_title']] ?? null);
    $totalRecs = (int)($attRow['total_records'] ?? 0);
    $presentRecs = (int)($attRow['present_count'] ?? 0);
    $tardyRecs = (int)($attRow['tardy_count'] ?? 0);

    $totalLogsAll += $totalRecs;
    $totalPresentAll += ($presentRecs + $tardyRecs);

    $cls['session_count'] = $sessionCount;
    $cls['avg_rate'] = ($totalRecs > 0) ? round((($presentRecs + $tardyRecs) / $totalRecs) * 100, 1) : 100.0;
}
unset($cls);

$overallAvgRate = ($totalLogsAll > 0) ? round(($totalPresentAll / $totalLogsAll) * 100, 1) : 100.0;

// 3. Fetch real students for each section
$studentStmt = $db->prepare("
    SELECT 
        r.section,
        r.course_code,
        u.user_id,
        COALESCE(u.student_id, u.user_id) AS student_id,
        u.first_name,
        u.last_name,
        u.email,
        u.status
    FROM class_roster r
    JOIN users u ON r.student_id = u.user_id
    WHERE r.section IS NOT NULL AND r.section != ''
    ORDER BY u.last_name ASC, u.first_name ASC
");
$studentStmt->execute();
$rawStudents = $studentStmt->fetchAll(PDO::FETCH_ASSOC);

// Batch fetch attendance metrics for all students in ONE single query (O(1) memory lookup)
$studentAttMap = [];
try {
    $stAttBulkStmt = $db->query("
        SELECT 
            student_id,
            COUNT(*) as total_logs,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN status = 'tardy' THEN 1 ELSE 0 END) as tardy_count,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count
        FROM attendance
        GROUP BY student_id
    ");
    if ($stAttBulkStmt) {
        while ($row = $stAttBulkStmt->fetch(PDO::FETCH_ASSOC)) {
            $studentAttMap[(int)$row['student_id']] = $row;
        }
    }
} catch (Throwable $e) {}

$rostersBySection = [];
foreach ($rawStudents as $st) {
    $sec = $st['section'];
    if (!isset($rostersBySection[$sec])) {
        $rostersBySection[$sec] = [];
    }

    $att = $studentAttMap[(int)$st['user_id']] ?? null;
    $sLogs = (int)($att['total_logs'] ?? 0);
    $sPres = (int)($att['present_count'] ?? 0);
    $sTardy = (int)($att['tardy_count'] ?? 0);
    $sAbs = (int)($att['absent_count'] ?? 0);
    $rate = $sLogs > 0 ? round((($sPres + $sTardy) / $sLogs) * 100, 1) : 100.0;

    $rostersBySection[$sec][] = [
        'id'       => (string)$st['student_id'],
        'name'     => trim($st['first_name'] . ' ' . $st['last_name']),
        'email'    => $st['email'],
        'sessions' => max($sLogs, 1),
        'present'  => $sPres,
        'late'     => $sTardy,
        'absent'   => $sAbs,
        'excused'  => 0,
        'rate'     => $rate,
        'status'   => ucfirst($st['status'] ?? 'Active')
    ];
}

require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/80">Faculty Portal</span>
            <span class="text-xs text-slate-500 font-medium">Academic Year 2025–2026</span>
          </div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">My Assigned Classes &amp; Rosters</h1>
          <p class="text-xs text-slate-500 mt-0.5">Manage course sections, view interactive student rosters, and launch live attendance sessions.</p>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="openAddStudentModal()" class="px-3.5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
            <span>+ Add Student</span>
          </button>
          <a href="<?php echo url('teacher/import-roster'); ?>" class="px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 hover:text-blue-700 text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Import Roster</span>
          </a>
          <a href="<?php echo url('teacher/live-session'); ?>" class="px-4 py-2 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Start Attendance</span>
          </a>
        </div>
      </div>

      <!-- Quick KPI Stats Bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 mb-6">
        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-blue-300 transition text-left flex flex-col justify-between">
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Assigned Classes</div>
          <div class="text-2xl font-bold tracking-tight text-slate-900 my-1"><?= count($classes) ?> Course<?= count($classes) !== 1 ? 's' : '' ?></div>
          <div class="text-[11px] text-slate-500"><?= count($allSections) ?> Academic Section<?= count($allSections) !== 1 ? 's' : '' ?></div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-blue-300 transition text-left flex flex-col justify-between">
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Enrolled</div>
          <div class="text-2xl font-bold tracking-tight text-blue-700 my-1"><?= number_format($totalEnrolledAll) ?> Students</div>
          <div class="text-[11px] text-slate-500">Official Roster Count</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-blue-300 transition text-left flex flex-col justify-between">
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Avg. Attendance</div>
          <div class="text-2xl font-bold tracking-tight text-emerald-600 my-1"><?= $overallAvgRate ?>%</div>
          <div class="text-[11px] text-slate-500">Campus Benchmark: 85%</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs hover:border-blue-300 transition text-left flex flex-col justify-between">
          <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Sessions Held</div>
          <div class="text-2xl font-bold tracking-tight text-slate-900 my-1"><?= $totalSessionsAll ?> Sessions</div>
          <div class="text-[11px] text-slate-500">Recorded QR Sessions</div>
        </div>
      </div>

      <!-- Course / Year / Section Filter Bar -->
      <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3.5">
          <!-- Course Program -->
          <div>
            <label for="filter-program" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Program / Course</label>
            <select id="filter-program" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Programs</option>
              <?php foreach ($allPrograms as $p): ?>
                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Year Level -->
          <div>
            <label for="filter-year" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Year Level</label>
            <select id="filter-year" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Year Levels</option>
              <?php foreach ($allYears as $y): ?>
                <?php $yOrd = match((int)$y) { 1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', default => $y . 'th Year' }; ?>
                <option value="<?= (int)$y ?>"><?= $yOrd ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Section -->
          <div>
            <label for="filter-section" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Section</label>
            <select id="filter-section" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Sections</option>
              <?php foreach ($allSections as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Search keyword -->
          <div>
            <label for="search-class" class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1.5">Search Subject / Code</label>
            <div class="relative">
              <input type="text" id="search-class" placeholder="e.g. IT301 or Web Dev" class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="filterClasses()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Classes & Rosters Horizontal List -->
      <div id="classes-container" class="space-y-3.5 mb-6">
        <?php if (empty($classes)): ?>
          <div class="bg-white rounded-xl p-12 text-center border border-slate-200 text-slate-400">
            <svg class="w-10 h-10 mx-auto text-slate-300 mb-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <div class="text-sm font-bold text-slate-700">No Assigned Classes Found</div>
            <div class="text-xs text-slate-400 mt-1">Click "Import Class Roster" to upload or enroll students into your class section.</div>
          </div>
        <?php else: ?>
          <?php foreach ($classes as $idx => $cls): ?>
            <?php
              $courseTitle = htmlspecialchars($cls['course_title']);
              $courseCode  = htmlspecialchars($cls['course_code']);
              $sectionCode = htmlspecialchars($cls['section']);
              $programCode = htmlspecialchars($cls['course']);
              $yearNum     = (int)($cls['year_level'] ?? 3);
              $yearOrdinal = match ($yearNum) {
                  1 => '1st Year',
                  2 => '2nd Year',
                  3 => '3rd Year',
                  4 => '4th Year',
                  default => $yearNum . 'th Year',
              };
              $schedDay = $cls['schedule_day'] ?? 'M-W-F';
              $schedTime = !empty($cls['scheduled_time']) ? date('h:i A', strtotime($cls['scheduled_time'])) : 'TBA';
              $scheduleFormatted = htmlspecialchars($schedDay . ' • ' . $schedTime);
              $roomFormatted     = htmlspecialchars((string)($cls['room_number'] ?? 'TBA'));
              $enrolled          = (int)($cls['enrolled_count'] ?? 0);
              $sessionCount      = (int)($cls['session_count'] ?? 0);
              $avgRate           = number_format((float)($cls['avg_rate'] ?? 100), 1) . '%';
            ?>
            <div class="class-card bg-white rounded-xl border border-slate-200/80 shadow-xs hover:border-blue-300 hover:shadow-xs transition p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4"
                 data-program="<?= $programCode ?>"
                 data-year="<?= $yearNum ?>"
                 data-section="<?= $sectionCode ?>"
                 data-title="<?= strtolower($courseCode . ' ' . $courseTitle . ' ' . $sectionCode) ?>">
              
              <!-- Left: Course Info & Badges -->
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2 mb-1.5">
                  <span class="px-2.5 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200/70"><?= $sectionCode ?></span>
                  <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200/60"><?= $yearOrdinal ?></span>
                  <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">● Active</span>
                </div>
                
                <h3 class="font-bold text-slate-900 text-base leading-snug truncate">
                  <span class="text-[#1e3b8a]"><?= $courseCode ?></span> — <?= $courseTitle ?>
                </h3>
                
                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500 mt-1.5">
                  <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span><?= $scheduleFormatted ?></span>
                  </span>
                  <span class="text-slate-300">&middot;</span>
                  <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Room <?= $roomFormatted ?></span>
                  </span>
                </div>
              </div>

              <!-- Center: Class Statistics Strip -->
              <div class="flex items-center gap-4 sm:gap-6 bg-slate-50/80 px-4 py-2.5 rounded-xl border border-slate-200/60 shrink-0">
                <div class="text-center">
                  <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Enrolled</div>
                  <div class="text-base font-bold text-blue-700"><?= $enrolled ?></div>
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <div class="text-center">
                  <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Sessions</div>
                  <div class="text-base font-bold text-slate-800"><?= $sessionCount ?></div>
                </div>
                <div class="w-px h-8 bg-slate-200"></div>
                <div class="text-center">
                  <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Avg Rate</div>
                  <div class="text-base font-bold text-emerald-600"><?= $avgRate ?></div>
                </div>
              </div>

              <!-- Right: Quick Actions -->
              <div class="flex flex-wrap sm:flex-nowrap items-center gap-2 shrink-0">
                <button type="button" class="px-3.5 py-2 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5"
                        onclick="openRosterModal('<?= $sectionCode ?>', '<?= $courseCode ?> — <?= addslashes($courseTitle) ?>', '<?= $yearOrdinal ?>', '<?= addslashes($scheduleFormatted) ?>', 'Room <?= addslashes($roomFormatted) ?>', <?= $enrolled ?>, '<?= $avgRate ?>')">
                  <svg class="w-3.5 h-3.5 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                  <span>Student Roster</span>
                </button>
                <button type="button" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-700 text-xs font-semibold shadow-xs transition flex items-center gap-1"
                        title="Add student manually or import CSV to <?= $sectionCode ?>"
                        onclick="openAddStudentModal('<?= $sectionCode ?>')">
                  <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                  <span>+ Add Student</span>
                </button>
                <a href="<?php echo url('teacher/attendance-history?section=' . urlencode($cls['section'])); ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 hover:text-blue-700 text-xs font-semibold shadow-xs transition" title="View Past Attendance Sessions">
                  History
                </a>
                <a href="<?php echo url('teacher/live-session?section=' . urlencode($cls['section'])); ?>" class="px-3 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                  <span>Start QR</span>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Class List Pagination Bar -->
      <div id="classes-pagination-bar" class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row items-center justify-between gap-3 mb-6">
        <div id="classes-pagination-info" class="text-xs text-slate-500">
          Showing <strong class="text-slate-800" id="classes-start-count">1</strong> to <strong class="text-slate-800" id="classes-end-count">6</strong> of <strong class="text-slate-800" id="classes-total-count"><?= count($classes) ?></strong> assigned classes
        </div>

        <div class="flex items-center gap-1.5" id="classes-pagination-buttons">
          <!-- Populated by JavaScript -->
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: CLASS STUDENT ROSTER WITH PAGINATION & SEARCH
══════════════════════════════════════════════════════════════ -->
<div id="roster-modal" class="fixed inset-0 z-50 bg-[#0f172a]/50 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200/90 w-full max-w-5xl max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between bg-slate-50/70 shrink-0">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span id="modal-section-badge" class="px-2.5 py-0.5 rounded text-xs font-bold bg-blue-50 text-blue-800 border border-blue-200/70">BSIT 3-A</span>
          <span id="modal-year-badge" class="text-xs text-slate-500 font-medium">3rd Year</span>
          <span class="text-slate-300">&middot;</span>
          <span id="modal-schedule-text" class="text-xs text-slate-500">Tue/Thu • 08:00 AM – 10:00 AM (Lab 304)</span>
        </div>
        <h2 id="modal-course-title" class="text-base font-bold text-slate-900">IT301 — Web Development 2</h2>
      </div>
      <button type="button" onclick="closeRosterModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition" aria-label="Close modal">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Modal Controls & Search Bar -->
    <div class="p-3.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-white shrink-0">
      <div class="relative flex-1 min-w-[220px]">
        <input type="text" id="modal-search-input" placeholder="Search enrolled student by ID or name..." class="w-full pl-9 pr-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="filterModalStudents()">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <div class="flex items-center gap-2">
        <select id="modal-status-filter" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterModalStudents()">
          <option value="all">All Enrolled</option>
          <option value="good">Good Standing (&gt;85%)</option>
          <option value="at-risk">At Risk (&lt;75%)</option>
        </select>
        <button type="button" onclick="openAddStudentModal(currentSection)" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition flex items-center gap-1.5 shadow-xs">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          <span>Add Student / CSV</span>
        </button>
        <button type="button" onclick="exportRosterCSV()" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 hover:text-blue-700 text-xs font-semibold transition flex items-center gap-1.5 shadow-xs">
          <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          <span>Export CSV</span>
        </button>
      </div>
    </div>

    <!-- Modal Table Content -->
    <div class="flex-1 overflow-y-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200 sticky top-0 z-10">
          <tr>
            <th class="py-2.5 px-4">Student ID</th>
            <th class="py-2.5 px-4">Student Name</th>
            <th class="py-2.5 px-4 text-center">Sessions</th>
            <th class="py-2.5 px-4 text-center">Present</th>
            <th class="py-2.5 px-4 text-center">Late</th>
            <th class="py-2.5 px-4 text-center">Absent</th>
            <th class="py-2.5 px-4 text-center">Excused</th>
            <th class="py-2.5 px-4">Attendance Rate</th>
            <th class="py-2.5 px-4">Status</th>
            <th class="py-2.5 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody id="modal-roster-tbody" class="divide-y divide-slate-100 text-slate-700">
          <!-- Populated dynamically via JS -->
        </tbody>
      </table>
    </div>

    <!-- Modal Footer with Pagination Controls -->
    <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
      <div id="modal-pagination-info" class="text-xs text-slate-500">
        Showing <strong class="text-slate-800">1 to 5</strong> of <strong class="text-slate-800">12</strong> students
      </div>

      <div class="flex items-center gap-1.5" id="modal-pagination-buttons">
        <!-- Pagination Buttons inserted dynamically -->
      </div>

      <div>
        <button type="button" onclick="closeRosterModal()" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition">
          Close Roster
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: INSERT STUDENT TO SECTION ROSTER (MANUAL OR CSV)
══════════════════════════════════════════════════════════════ -->
<div id="add-student-modal" class="fixed inset-0 z-50 bg-[#0f172a]/60 backdrop-blur-xs hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200/90 w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
    
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-slate-200/80 flex items-center justify-between bg-slate-50/80 shrink-0">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200/80 flex items-center justify-center font-bold shadow-xs">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        </div>
        <div>
          <h2 class="text-base font-bold text-slate-900">Insert Student to Section Roster</h2>
          <p class="text-xs text-slate-500">Enroll an individual student manually or batch append students from CSV.</p>
        </div>
      </div>
      <button type="button" onclick="closeAddStudentModal()" class="w-8 h-8 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition" aria-label="Close modal">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Modal Body (Scrollable) -->
    <div class="p-6 overflow-y-auto space-y-5">
      
      <!-- Target Section Selector -->
      <div>
        <label for="add-student-section-select" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
          Target Class / Section <span class="text-rose-500">*</span>
        </label>
        <div class="relative">
          <select id="add-student-section-select" class="w-full pl-3 pr-8 py-2.5 rounded-lg border border-slate-300 text-xs font-semibold text-slate-800 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-xs" onchange="onTargetSectionChanged()">
            <?php foreach ($classes as $c): ?>
              <option value="<?= htmlspecialchars($c['section']) ?>">
                <?= htmlspecialchars($c['section']) ?> — <?= htmlspecialchars($c['course_code']) ?>: <?= htmlspecialchars($c['course_title']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Mode Selector Tabs -->
      <div class="flex items-center p-1 bg-slate-100 rounded-xl border border-slate-200/80">
        <button type="button" id="tab-btn-manual" onclick="switchAddStudentTab('manual')" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 bg-white text-[#1e3b8a] shadow-xs">
          <svg class="w-4 h-4 text-[#1e3b8a]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <span>Manual Student Entry</span>
        </button>
        <button type="button" id="tab-btn-csv" onclick="switchAddStudentTab('csv')" class="flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900">
          <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          <span>Batch CSV Import</span>
        </button>
      </div>

      <!-- TAB 1: MANUAL ENTRY FORM -->
      <div id="add-student-manual-panel" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <!-- Student ID -->
          <div class="sm:col-span-2">
            <label for="manual-student-id" class="block text-xs font-bold text-slate-700 mb-1">
              Student ID Number <span class="text-rose-500">*</span>
            </label>
            <div class="relative">
              <input type="text" id="manual-student-id" placeholder="e.g. 2024-00123" class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="updateManualPreview()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"/></svg>
            </div>
          </div>

          <!-- First Name -->
          <div>
            <label for="manual-first-name" class="block text-xs font-bold text-slate-700 mb-1">
              First Name <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="manual-first-name" placeholder="e.g. Juan" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="updateManualPreview()">
          </div>

          <!-- Last Name -->
          <div>
            <label for="manual-last-name" class="block text-xs font-bold text-slate-700 mb-1">
              Last Name <span class="text-rose-500">*</span>
            </label>
            <input type="text" id="manual-last-name" placeholder="e.g. Dela Cruz" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="updateManualPreview()">
          </div>

          <!-- Email (Optional) -->
          <div class="sm:col-span-2">
            <label for="manual-email" class="block text-xs font-bold text-slate-700 mb-1 flex items-center justify-between">
              <span>Student Email Address</span>
              <span class="text-[10px] text-slate-400 font-normal">Optional (auto-generated if empty)</span>
            </label>
            <div class="relative">
              <input type="email" id="manual-email" placeholder="e.g. juan.delacruz@student.bcp.edu.ph" class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-300 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="updateManualPreview()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
          </div>
        </div>

        <!-- Live Preview Card -->
        <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 flex items-center justify-between gap-3">
          <div class="flex items-center gap-3 min-w-0">
            <div id="preview-avatar" class="w-9 h-9 rounded-lg bg-[#1e3b8a] text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-xs">
              JD
            </div>
            <div class="min-w-0">
              <div id="preview-name" class="text-xs font-bold text-slate-900 truncate">Juan Dela Cruz</div>
              <div id="preview-id-email" class="text-[11px] text-slate-500 truncate font-mono">2024-00123 &middot; juan.delacruz@student.bcp.edu.ph</div>
            </div>
          </div>
          <div class="shrink-0 flex items-center gap-1.5">
            <span id="preview-section-badge" class="px-2 py-0.5 rounded text-[10px] font-bold bg-blue-50 text-blue-800 border border-blue-200/70">BSIT 3-A</span>
            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">Enrolled</span>
          </div>
        </div>
      </div>

      <!-- TAB 2: CSV BATCH IMPORT PANEL -->
      <div id="add-student-csv-panel" class="space-y-4 hidden">
        <div class="bg-blue-50/60 border border-blue-200/70 rounded-xl p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div class="text-xs text-blue-900">
            <div class="font-bold mb-0.5 flex items-center gap-1.5">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
              <span>CSV File Format Requirements</span>
            </div>
            <p class="text-[11px] text-blue-700">Columns: <code class="bg-blue-100/80 px-1 py-0.5 rounded font-mono font-bold text-blue-800">student_id, first_name, last_name, email</code></p>
          </div>
          <button type="button" onclick="downloadRosterTemplate()" class="shrink-0 px-3 py-1.5 rounded-lg bg-white border border-blue-300 hover:bg-blue-50 text-blue-800 text-xs font-semibold transition flex items-center gap-1.5 shadow-xs">
            <svg class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Download Template</span>
          </button>
        </div>

        <!-- Dropzone / File Picker -->
        <div id="csv-dropzone" class="border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-xl p-6 text-center bg-slate-50/50 hover:bg-blue-50/30 transition cursor-pointer" onclick="document.getElementById('csv-file-input').click()">
          <input type="file" id="csv-file-input" accept=".csv" class="hidden" onchange="handleRosterCsvSelected(this.files)">
          <svg class="w-10 h-10 mx-auto text-slate-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
          <div class="text-xs font-bold text-slate-700" id="csv-drop-title">Click to upload CSV file or drag and drop</div>
          <p class="text-[11px] text-slate-400 mt-0.5" id="csv-drop-subtitle">Maximum 500 student rows per upload</p>
        </div>

        <!-- Parsed Rows Preview Table -->
        <div id="csv-preview-container" class="hidden space-y-2">
          <div class="flex items-center justify-between text-xs">
            <span class="font-bold text-slate-700">Detected Student Rows (<span id="csv-parsed-count" class="text-emerald-700 font-bold">0</span>)</span>
            <button type="button" onclick="resetCsvSelection()" class="text-slate-400 hover:text-rose-600 font-semibold text-[11px]">Clear File</button>
          </div>
          <div class="border border-slate-200 rounded-lg overflow-hidden max-h-48 overflow-y-auto">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 text-slate-600 uppercase font-semibold text-[10px] border-b border-slate-200">
                <tr>
                  <th class="py-1.5 px-3">Student ID</th>
                  <th class="py-1.5 px-3">Full Name</th>
                  <th class="py-1.5 px-3">Email Address</th>
                </tr>
              </thead>
              <tbody id="csv-preview-tbody" class="divide-y divide-slate-100 text-slate-700 text-xs">
                <!-- Injected via JS -->
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Feedback / Alert Notification in Modal -->
      <div id="add-student-feedback" class="hidden p-3 rounded-lg text-xs font-medium"></div>
    </div>

    <!-- Modal Footer -->
    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50/80 flex items-center justify-between gap-3 shrink-0">
      <button type="button" onclick="closeAddStudentModal()" class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 text-xs font-semibold transition">
        Cancel
      </button>
      
      <button type="button" id="btn-submit-add-student" onclick="submitAddStudentForm()" class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2">
        <svg id="btn-submit-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span id="btn-submit-label">Add Student to Section</span>
      </button>
    </div>
  </div>
</div>

<script>
// ==========================================
// MAIN CLASS LIST PAGINATION & FILTERING
// ==========================================
var currentClassPage = 1;
var classPageSize = 5;
var matchingClassCards = [];

function filterClasses() {
  const container = document.getElementById('classes-container');
  if (container) {
    container.style.opacity = '0.4';
    container.style.transition = 'opacity 0.08s ease';
  }

  setTimeout(() => {
    const program = document.getElementById('filter-program').value;
    const year = document.getElementById('filter-year').value;
    const section = document.getElementById('filter-section').value;
    const query = document.getElementById('search-class').value.toLowerCase().trim();

    const cards = Array.from(document.querySelectorAll('.class-card'));
    matchingClassCards = cards.filter(card => {
      const cardProg = card.getAttribute('data-program');
      const cardYear = card.getAttribute('data-year');
      const cardSec = card.getAttribute('data-section');
      const cardTitle = card.getAttribute('data-title').toLowerCase();

      const matchProg = (program === 'all' || cardProg === program);
      const matchYear = (year === 'all' || cardYear === year);
      const matchSec = (section === 'all' || cardSec === section);
      const matchQuery = (!query || cardTitle.includes(query));

      return matchProg && matchYear && matchSec && matchQuery;
    });

    currentClassPage = 1;
    renderClassPagination();
    if (container) container.style.opacity = '1';
  }, 40);
}

function setClassPage(page) {
  currentClassPage = page;
  renderClassPagination();
}

function renderClassPagination() {
  const allCards = Array.from(document.querySelectorAll('.class-card'));
  const total = matchingClassCards.length;
  const totalPages = Math.ceil(total / classPageSize) || 1;

  if (currentClassPage > totalPages) currentClassPage = totalPages;
  if (currentClassPage < 1) currentClassPage = 1;

  const startIdx = (currentClassPage - 1) * classPageSize;
  const endIdx = startIdx + classPageSize;

  allCards.forEach(card => {
    card.style.display = 'none';
  });

  const visibleCards = matchingClassCards.slice(startIdx, endIdx);
  visibleCards.forEach(card => {
    card.style.display = 'flex';
  });

  // Update counts
  const startCountEl = document.getElementById('classes-start-count');
  const endCountEl = document.getElementById('classes-end-count');
  const totalCountEl = document.getElementById('classes-total-count');

  if (startCountEl) startCountEl.textContent = total > 0 ? (startIdx + 1) : 0;
  if (endCountEl) endCountEl.textContent = Math.min(endIdx, total);
  if (totalCountEl) totalCountEl.textContent = total;

  // Render pagination buttons
  const btnContainer = document.getElementById('classes-pagination-buttons');
  if (!btnContainer) return;
  btnContainer.innerHTML = '';

  if (totalPages <= 1) {
    document.getElementById('classes-pagination-bar').classList.toggle('hidden', total === 0);
    return;
  }
  document.getElementById('classes-pagination-bar').classList.remove('hidden');

  // Prev
  const prevBtn = document.createElement('button');
  prevBtn.type = 'button';
  prevBtn.disabled = (currentClassPage <= 1);
  prevBtn.className = `px-3 py-1.5 rounded-lg text-xs font-semibold transition ${currentClassPage <= 1 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-blue-700'}`;
  prevBtn.innerHTML = '&laquo; Prev';
  prevBtn.onclick = () => setClassPage(currentClassPage - 1);
  btnContainer.appendChild(prevBtn);

  // Page Numbers
  for (let i = 1; i <= totalPages; i++) {
    const pageBtn = document.createElement('button');
    pageBtn.type = 'button';
    pageBtn.className = `w-8 h-8 rounded-lg text-xs font-bold transition ${i === currentClassPage ? 'bg-[#1e3b8a] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-blue-700'}`;
    pageBtn.textContent = i;
    pageBtn.onclick = () => setClassPage(i);
    btnContainer.appendChild(pageBtn);
  }

  // Next
  const nextBtn = document.createElement('button');
  nextBtn.type = 'button';
  nextBtn.disabled = (currentClassPage >= totalPages);
  nextBtn.className = `px-3 py-1.5 rounded-lg text-xs font-semibold transition ${currentClassPage >= totalPages ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-blue-700'}`;
  nextBtn.innerHTML = 'Next &raquo;';
  nextBtn.onclick = () => setClassPage(currentClassPage + 1);
  btnContainer.appendChild(nextBtn);
}

// ==========================================
// STUDENT ROSTER MODAL
// ==========================================
var sectionRosters = <?= json_encode($rostersBySection, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
var currentSection = '';
var currentSectionRoster = [];
var filteredList = [];
var currentPage = 1;
var pageSize = 5;

function openRosterModal(section, course, year, schedule, room, enrolledCount, avgRate) {
  currentSection = section;
  document.getElementById('modal-section-badge').textContent = section;
  document.getElementById('modal-year-badge').textContent = year;
  document.getElementById('modal-schedule-text').textContent = schedule + ' (' + room + ')';
  document.getElementById('modal-course-title').textContent = course;

  document.getElementById('modal-search-input').value = '';
  document.getElementById('modal-status-filter').value = 'all';

  currentSectionRoster = sectionRosters[section] || [];
  filteredList = [...currentSectionRoster];
  currentPage = 1;
  renderModalTable();

  document.getElementById('roster-modal').classList.remove('hidden');
}

function closeRosterModal() {
  document.getElementById('roster-modal').classList.add('hidden');
}

function filterModalStudents() {
  const query = document.getElementById('modal-search-input').value.toLowerCase().trim();
  const statusFilter = document.getElementById('modal-status-filter').value;

  filteredList = currentSectionRoster.filter(s => {
    const matchQuery = (!query || s.id.toLowerCase().includes(query) || s.name.toLowerCase().includes(query) || s.email.toLowerCase().includes(query));
    let matchStatus = true;
    if (statusFilter === 'good') matchStatus = (s.rate >= 85);
    if (statusFilter === 'at-risk') matchStatus = (s.rate < 75);
    return matchQuery && matchStatus;
  });

  currentPage = 1;
  renderModalTable();
}

function exportRosterCSV() {
  if (!currentSectionRoster || currentSectionRoster.length === 0) {
    if (typeof APP !== 'undefined' && APP.toast) APP.toast.warning('No student records to export.');
    return;
  }
  let csv = "student_id,full_name,email,sessions,present,late,absent,rate,status\n";
  currentSectionRoster.forEach(s => {
    csv += `"${s.id}","${s.name}","${s.email}",${s.sessions},${s.present},${s.late},${s.absent},"${s.rate}%","${s.status}"\n`;
  });
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `roster_${currentSection || 'students'}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  if (typeof APP !== 'undefined' && APP.toast) {
    APP.toast.success(`Exported ${currentSectionRoster.length} students to CSV.`);
  }
}

function setModalPage(page) {
  currentPage = page;
  renderModalTable();
}

function renderModalTable() {
  const tbody = document.getElementById('modal-roster-tbody');
  tbody.innerHTML = '';

  const total = filteredList.length;
  const startIdx = (currentPage - 1) * pageSize;
  const endIdx = Math.min(startIdx + pageSize, total);
  const pageItems = filteredList.slice(startIdx, endIdx);

  if (pageItems.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-8 text-slate-400">No student records found in section ${escapeHtml(currentSection)}.</td></tr>`;
  } else {
    pageItems.forEach(s => {
      const isAtRisk = s.rate < 75;
      const statusBadge = isAtRisk
        ? `<span class="badge badge-absent font-bold">At Risk</span>`
        : `<span class="badge badge-present font-bold">Good Standing</span>`;

      const rateColor = s.rate >= 90 ? 'text-emerald-600' : (s.rate >= 75 ? 'text-blue-600' : 'text-rose-600');
      const barColor = s.rate >= 90 ? 'bg-emerald-500' : (s.rate >= 75 ? 'bg-blue-500' : 'bg-rose-500');

      const tr = document.createElement('tr');
      tr.className = 'hover:bg-slate-50/80 transition';
      tr.innerHTML = `
        <td class="py-2.5 px-4 font-mono font-bold text-blue-700">${escapeHtml(s.id)}</td>
        <td class="py-2.5 px-4">
          <div class="font-bold text-slate-800">${escapeHtml(s.name)}</div>
          <div class="text-[10px] text-slate-400">${escapeHtml(s.email)}</div>
        </td>
        <td class="py-2.5 px-4 text-center font-bold text-slate-700">${s.sessions}</td>
        <td class="py-2.5 px-4 text-center font-bold text-emerald-600">${s.present}</td>
        <td class="py-2.5 px-4 text-center font-bold text-amber-600">${s.late}</td>
        <td class="py-2.5 px-4 text-center font-bold text-rose-600">${s.absent}</td>
        <td class="py-2.5 px-4 text-center font-bold text-blue-600">${s.excused}</td>
        <td class="py-2.5 px-4">
          <div class="flex items-center gap-2">
            <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
              <div class="${barColor} h-2 rounded-full" style="width: ${s.rate}%"></div>
            </div>
            <span class="font-bold text-xs ${rateColor}">${s.rate}%</span>
          </div>
        </td>
        <td class="py-2.5 px-4">${statusBadge}</td>
        <td class="py-2.5 px-4 text-right">
          <a href="<?php echo url('teacher/attendance-history'); ?>?student=${encodeURIComponent(s.id)}" class="text-xs text-blue-600 hover:underline font-semibold">History</a>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  // Update pagination info
  document.getElementById('modal-pagination-info').innerHTML = `Showing <strong class="text-slate-800">${total > 0 ? startIdx + 1 : 0} to ${endIdx}</strong> of <strong class="text-slate-800">${total}</strong> students`;

  // Update pagination buttons
  const totalPages = Math.ceil(total / pageSize) || 1;
  const btnContainer = document.getElementById('modal-pagination-buttons');
  btnContainer.innerHTML = '';

  // Prev
  const prevBtn = document.createElement('button');
  prevBtn.type = 'button';
  prevBtn.disabled = (currentPage <= 1);
  prevBtn.className = `px-2.5 py-1 rounded text-xs font-semibold ${currentPage <= 1 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-blue-700'}`;
  prevBtn.textContent = '« Prev';
  prevBtn.onclick = () => setModalPage(currentPage - 1);
  btnContainer.appendChild(prevBtn);

  // Page Numbers
  for (let i = 1; i <= totalPages; i++) {
    const pageBtn = document.createElement('button');
    pageBtn.type = 'button';
    pageBtn.className = `w-7 h-7 rounded text-xs font-bold transition ${i === currentPage ? 'bg-[#1e3b8a] text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-blue-700'}`;
    pageBtn.textContent = i;
    pageBtn.onclick = () => setModalPage(i);
    btnContainer.appendChild(pageBtn);
  }

  // Next
  const nextBtn = document.createElement('button');
  nextBtn.type = 'button';
  nextBtn.disabled = (currentPage >= totalPages);
  nextBtn.className = `px-2.5 py-1 rounded text-xs font-semibold ${currentPage >= totalPages ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 hover:text-blue-700'}`;
  nextBtn.textContent = 'Next »';
  nextBtn.onclick = () => setModalPage(currentPage + 1);
  btnContainer.appendChild(nextBtn);
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text || '';
  return div.innerHTML;
}

// ==========================================
// INSERT / ENROLL STUDENT MODAL (MANUAL & CSV)
// ==========================================
var activeAddTab = 'manual';
var parsedCsvRows = [];

function openAddStudentModal(targetSec) {
  const modal = document.getElementById('add-student-modal');
  if (!modal) return;

  const secSelect = document.getElementById('add-student-section-select');
  if (secSelect) {
    if (targetSec) {
      secSelect.value = targetSec;
    } else if (currentSection) {
      secSelect.value = currentSection;
    }
  }

  // Clear inputs
  const idInput = document.getElementById('manual-student-id');
  const fnInput = document.getElementById('manual-first-name');
  const lnInput = document.getElementById('manual-last-name');
  const emInput = document.getElementById('manual-email');

  if (idInput) idInput.value = '';
  if (fnInput) fnInput.value = '';
  if (lnInput) lnInput.value = '';
  if (emInput) emInput.value = '';

  resetCsvSelection();
  clearAddFeedback();
  switchAddStudentTab('manual');
  updateManualPreview();

  modal.classList.remove('hidden');
}

function closeAddStudentModal() {
  const modal = document.getElementById('add-student-modal');
  if (modal) modal.classList.add('hidden');
}

function onTargetSectionChanged() {
  updateManualPreview();
}

function switchAddStudentTab(tab) {
  activeAddTab = tab;
  const manualPanel = document.getElementById('add-student-manual-panel');
  const csvPanel = document.getElementById('add-student-csv-panel');
  const manualBtn = document.getElementById('tab-btn-manual');
  const csvBtn = document.getElementById('tab-btn-csv');
  const submitLabel = document.getElementById('btn-submit-label');

  if (tab === 'manual') {
    if (manualPanel) manualPanel.classList.remove('hidden');
    if (csvPanel) csvPanel.classList.add('hidden');

    if (manualBtn) {
      manualBtn.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 bg-white text-[#1e3b8a] shadow-xs';
    }
    if (csvBtn) {
      csvBtn.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900';
    }
    if (submitLabel) submitLabel.textContent = 'Add Student to Section';
  } else {
    if (manualPanel) manualPanel.classList.add('hidden');
    if (csvPanel) csvPanel.classList.remove('hidden');

    if (manualBtn) {
      manualBtn.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 text-slate-600 hover:text-slate-900';
    }
    if (csvBtn) {
      csvBtn.className = 'flex-1 py-2 px-3 rounded-lg text-xs font-bold transition flex items-center justify-center gap-2 bg-white text-emerald-700 shadow-xs';
    }
    if (submitLabel) {
      submitLabel.textContent = parsedCsvRows.length > 0
        ? `Import ${parsedCsvRows.length} Student${parsedCsvRows.length === 1 ? '' : 's'}`
        : 'Import CSV Students';
    }
  }
}

function updateManualPreview() {
  const idVal = (document.getElementById('manual-student-id')?.value || '').trim();
  const fnVal = (document.getElementById('manual-first-name')?.value || '').trim();
  const lnVal = (document.getElementById('manual-last-name')?.value || '').trim();
  const emVal = (document.getElementById('manual-email')?.value || '').trim();
  const secVal = document.getElementById('add-student-section-select')?.value || 'BSIT 3-A';

  const previewAvatar = document.getElementById('preview-avatar');
  const previewName = document.getElementById('preview-name');
  const previewIdEmail = document.getElementById('preview-id-email');
  const previewSecBadge = document.getElementById('preview-section-badge');

  const firstName = fnVal || 'Juan';
  const lastName = lnVal || 'Dela Cruz';
  const fullName = `${firstName} ${lastName}`;
  const studentId = idVal || '2024-00123';
  const email = emVal || `${firstName.toLowerCase().replace(/[^a-z0-9]/g, '')}.${lastName.toLowerCase().replace(/[^a-z0-9]/g, '')}@student.bcp.edu.ph`;

  const initials = (firstName.charAt(0) + lastName.charAt(0)).toUpperCase() || 'JD';

  if (previewAvatar) previewAvatar.textContent = initials;
  if (previewName) previewName.textContent = fullName;
  if (previewIdEmail) previewIdEmail.textContent = `${studentId} • ${email}`;
  if (previewSecBadge) previewSecBadge.textContent = secVal;
}

function setAddFeedback(type, message) {
  const el = document.getElementById('add-student-feedback');
  if (!el) return;
  el.classList.remove('hidden', 'bg-rose-50', 'text-rose-700', 'border-rose-200', 'bg-emerald-50', 'text-emerald-700', 'border-emerald-200', 'bg-amber-50', 'text-amber-700', 'border-amber-200');

  if (type === 'error') {
    el.classList.add('bg-rose-50', 'text-rose-700', 'border', 'border-rose-200');
  } else if (type === 'success') {
    el.classList.add('bg-emerald-50', 'text-emerald-700', 'border', 'border-emerald-200');
  } else {
    el.classList.add('bg-amber-50', 'text-amber-700', 'border', 'border-amber-200');
  }
  el.innerHTML = message;
}

function clearAddFeedback() {
  const el = document.getElementById('add-student-feedback');
  if (el) {
    el.classList.add('hidden');
    el.innerHTML = '';
  }
}

function setSubmitLoading(isLoading, text) {
  const btn = document.getElementById('btn-submit-add-student');
  const label = document.getElementById('btn-submit-label');
  const icon = document.getElementById('btn-submit-icon');
  if (!btn || !label) return;

  btn.disabled = isLoading;
  if (isLoading) {
    btn.classList.add('opacity-70', 'cursor-not-allowed');
    label.textContent = text || 'Processing...';
    if (icon) {
      icon.outerHTML = '<svg id="btn-submit-icon" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
    }
  } else {
    btn.classList.remove('opacity-70', 'cursor-not-allowed');
    label.textContent = text || (activeAddTab === 'manual' ? 'Add Student to Section' : 'Import CSV Students');
    const curIcon = document.getElementById('btn-submit-icon');
    if (curIcon) {
      curIcon.outerHTML = '<svg id="btn-submit-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>';
    }
  }
}

function submitAddStudentForm() {
  if (activeAddTab === 'manual') {
    submitManualStudent();
  } else {
    submitCsvStudents();
  }
}

async function submitManualStudent() {
  clearAddFeedback();
  const section = (document.getElementById('add-student-section-select')?.value || '').trim();
  const studentId = (document.getElementById('manual-student-id')?.value || '').trim();
  const firstName = (document.getElementById('manual-first-name')?.value || '').trim();
  const lastName = (document.getElementById('manual-last-name')?.value || '').trim();
  const email = (document.getElementById('manual-email')?.value || '').trim();

  if (!section) {
    setAddFeedback('error', 'Please select a target class section.');
    return;
  }
  if (!studentId) {
    setAddFeedback('error', 'Please enter the Student ID number (e.g. 2024-00123).');
    document.getElementById('manual-student-id')?.focus();
    return;
  }
  if (!firstName || !lastName) {
    setAddFeedback('error', 'Please provide both First Name and Last Name.');
    if (!firstName) document.getElementById('manual-first-name')?.focus();
    else document.getElementById('manual-last-name')?.focus();
    return;
  }

  setSubmitLoading(true, 'Adding Student...');

  try {
    const payload = {
      section: section,
      student_id: studentId,
      first_name: firstName,
      last_name: lastName,
      email: email
    };

    const res = await fetch('<?php echo url("api/teacher/classes/add-student"); ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    setSubmitLoading(false);

    if (data.success && data.student) {
      // Append to local section roster
      if (!sectionRosters[section]) sectionRosters[section] = [];
      sectionRosters[section].push(data.student);

      // If roster modal is currently open and viewing this section, refresh it immediately
      if (currentSection === section && !document.getElementById('roster-modal').classList.contains('hidden')) {
        currentSectionRoster = sectionRosters[section];
        filterModalStudents();
      }

      // Update card badge count on DOM
      updateSectionCardEnrolled(section, data.total_enrolled);

      // Show toast
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast.success(data.message || `Enrolled ${firstName} ${lastName} to Section ${section}.`);
      } else {
        alert(data.message || `Successfully enrolled ${firstName} ${lastName} to ${section}.`);
      }

      closeAddStudentModal();
    } else {
      setAddFeedback(data.status === 'warning' ? 'warning' : 'error', data.message || 'Failed to add student. Please check input values.');
    }
  } catch (err) {
    setSubmitLoading(false);
    setAddFeedback('error', 'Network error or server unreachable. Please try again.');
    console.error('Add Student Error:', err);
  }
}

// CSV Batch Handling
function handleRosterCsvSelected(files) {
  if (!files || files.length === 0) return;
  const file = files[0];
  if (!file.name.toLowerCase().endsWith('.csv')) {
    setAddFeedback('error', 'Please select a valid CSV (.csv) file.');
    return;
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const text = e.target.result;
    parsedCsvRows = parseRosterCsvText(text);

    if (parsedCsvRows.length === 0) {
      setAddFeedback('error', 'No valid student rows found in CSV. Expected headers: student_id, first_name, last_name, email.');
      resetCsvSelection();
      return;
    }

    clearAddFeedback();
    renderCsvPreview(file.name);
  };
  reader.readAsText(file);
}

function parseRosterCsvText(text) {
  const lines = text.split(/\r\n|\n|\r/).filter(l => l.trim().length > 0);
  if (lines.length < 2) return [];

  const headerLine = lines[0];
  const headers = headerLine.split(',').map(h => h.trim().replace(/^["']|["']$/g, '').toLowerCase().replace(/[\s\-]/g, '_'));

  const idIdx = headers.findIndex(h => ['student_id', 'id', 'student_number', 'student_no', 'lrn'].includes(h));
  const fnIdx = headers.findIndex(h => ['first_name', 'firstname', 'fname', 'first'].includes(h));
  const lnIdx = headers.findIndex(h => ['last_name', 'lastname', 'lname', 'last', 'surname'].includes(h));
  const nameIdx = headers.findIndex(h => ['full_name', 'fullname', 'name', 'student_name'].includes(h));
  const emailIdx = headers.findIndex(h => ['email', 'email_address', 'mail'].includes(h));

  const results = [];
  for (let i = 1; i < lines.length; i++) {
    const rawLine = lines[i];
    const cols = [];
    let insideQuote = false;
    let currentCol = '';
    for (let c = 0; c < rawLine.length; c++) {
      const ch = rawLine[c];
      if (ch === '"') {
        insideQuote = !insideQuote;
      } else if (ch === ',' && !insideQuote) {
        cols.push(currentCol.trim().replace(/^["']|["']$/g, ''));
        currentCol = '';
      } else {
        currentCol += ch;
      }
    }
    cols.push(currentCol.trim().replace(/^["']|["']$/g, ''));

    const studentId = idIdx !== -1 ? cols[idIdx] : cols[0];
    let firstName = fnIdx !== -1 ? cols[fnIdx] : '';
    let lastName  = lnIdx !== -1 ? cols[lnIdx] : '';
    let email     = emailIdx !== -1 ? cols[emailIdx] : '';

    if (!firstName && !lastName && nameIdx !== -1 && cols[nameIdx]) {
      const parts = cols[nameIdx].trim().split(/\s+/);
      firstName = parts[0] || 'Student';
      lastName = parts.slice(1).join(' ') || 'User';
    }

    if (studentId && (firstName || lastName)) {
      results.push({
        student_id: studentId,
        first_name: firstName,
        last_name: lastName,
        full_name: (firstName + ' ' + lastName).trim(),
        email: email
      });
    }
  }
  return results;
}

function renderCsvPreview(fileName) {
  const container = document.getElementById('csv-preview-container');
  const countEl = document.getElementById('csv-parsed-count');
  const tbody = document.getElementById('csv-preview-tbody');
  const dropTitle = document.getElementById('csv-drop-title');
  const dropSub = document.getElementById('csv-drop-subtitle');
  const submitLabel = document.getElementById('btn-submit-label');

  if (dropTitle) dropTitle.textContent = `Selected: ${fileName}`;
  if (dropSub) dropSub.textContent = `${parsedCsvRows.length} students ready to import`;
  if (countEl) countEl.textContent = parsedCsvRows.length;

  if (tbody) {
    tbody.innerHTML = '';
    parsedCsvRows.slice(0, 8).forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="py-1.5 px-3 font-mono font-bold text-blue-700">${escapeHtml(r.student_id)}</td>
        <td class="py-1.5 px-3 font-medium text-slate-800">${escapeHtml(r.full_name)}</td>
        <td class="py-1.5 px-3 text-slate-500 font-mono text-[11px]">${escapeHtml(r.email || 'auto-generated')}</td>
      `;
      tbody.appendChild(tr);
    });

    if (parsedCsvRows.length > 8) {
      const moreTr = document.createElement('tr');
      moreTr.className = 'bg-slate-50 text-slate-500 text-[11px] italic text-center';
      moreTr.innerHTML = `<td colspan="3" class="py-1.5 px-3">... and ${parsedCsvRows.length - 8} more students</td>`;
      tbody.appendChild(moreTr);
    }
  }

  if (container) container.classList.remove('hidden');
  if (submitLabel) submitLabel.textContent = `Import ${parsedCsvRows.length} Student${parsedCsvRows.length === 1 ? '' : 's'}`;
}

function resetCsvSelection() {
  parsedCsvRows = [];
  const input = document.getElementById('csv-file-input');
  if (input) input.value = '';
  const container = document.getElementById('csv-preview-container');
  if (container) container.classList.add('hidden');
  const dropTitle = document.getElementById('csv-drop-title');
  const dropSub = document.getElementById('csv-drop-subtitle');
  if (dropTitle) dropTitle.textContent = 'Click to upload CSV file or drag and drop';
  if (dropSub) dropSub.textContent = 'Maximum 500 student rows per upload';
  const submitLabel = document.getElementById('btn-submit-label');
  if (submitLabel && activeAddTab === 'csv') submitLabel.textContent = 'Import CSV Students';
}

async function submitCsvStudents() {
  clearAddFeedback();
  const section = (document.getElementById('add-student-section-select')?.value || '').trim();

  if (!section) {
    setAddFeedback('error', 'Please select a target class section.');
    return;
  }
  if (!parsedCsvRows || parsedCsvRows.length === 0) {
    setAddFeedback('error', 'Please upload and parse a valid CSV file first.');
    return;
  }

  setSubmitLoading(true, `Importing ${parsedCsvRows.length} students...`);

  try {
    const payload = {
      section: section,
      students: parsedCsvRows
    };

    const res = await fetch('<?php echo url("api/teacher/classes/import-section"); ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    setSubmitLoading(false);

    if (data.success) {
      if (!sectionRosters[section]) sectionRosters[section] = [];
      if (Array.isArray(data.students)) {
        data.students.forEach(st => {
          sectionRosters[section].push(st);
        });
      }

      if (currentSection === section && !document.getElementById('roster-modal').classList.contains('hidden')) {
        currentSectionRoster = sectionRosters[section];
        filterModalStudents();
      }

      updateSectionCardEnrolled(section, data.total_enrolled);

      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast.success(data.message || `Successfully imported students to ${section}.`);
      } else {
        alert(data.message || `Successfully imported students to ${section}.`);
      }

      closeAddStudentModal();
    } else {
      setAddFeedback('error', data.message || 'Failed to import students from CSV.');
    }
  } catch (err) {
    setSubmitLoading(false);
    setAddFeedback('error', 'Network error or server unreachable. Please try again.');
    console.error('CSV Import Error:', err);
  }
}

function updateSectionCardEnrolled(section, totalCount) {
  // Update card UI
  const cards = document.querySelectorAll(`.class-card[data-section="${CSS.escape(section)}"]`);
  cards.forEach(c => {
    const enrolledEl = c.querySelector('.text-blue-700');
    if (enrolledEl) enrolledEl.textContent = totalCount;
  });

  // Re-calculate all enrolled
  let totalAll = 0;
  for (const s in sectionRosters) {
    totalAll += (sectionRosters[s] || []).length;
  }
  const kpiEl = document.querySelector('.grid .text-blue-700');
  if (kpiEl) kpiEl.textContent = `${totalAll.toLocaleString()} Students`;
}

function downloadRosterTemplate() {
  const currentSec = document.getElementById('add-student-section-select')?.value || 'BSIT 3-A';
  const csvContent = "student_id,first_name,last_name,email\n" +
                     "2024-00101,Juan,Dela Cruz,juan.delacruz@student.bcp.edu.ph\n" +
                     "2024-00102,Maria,Santos,maria.santos@student.bcp.edu.ph\n" +
                     "2024-00103,Mark,Reyes,mark.reyes@student.bcp.edu.ph\n";

  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = `roster_template_${currentSec.replace(/[^a-zA-Z0-9]/g, '_')}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

// Drag & drop dropzone events
window.addEventListener('DOMContentLoaded', () => {
  const dropzone = document.getElementById('csv-dropzone');
  if (dropzone) {
    ['dragenter', 'dragover'].forEach(name => {
      dropzone.addEventListener(name, (e) => {
        e.preventDefault();
        dropzone.classList.add('border-blue-500', 'bg-blue-50/60');
      }, false);
    });
    ['dragleave', 'drop'].forEach(name => {
      dropzone.addEventListener(name, (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-blue-500', 'bg-blue-50/60');
      }, false);
    });
    dropzone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      if (dt && dt.files && dt.files.length > 0) {
        handleRosterCsvSelected(dt.files);
      }
    }, false);
  }
});

// Initialize on page load
window.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const targetSec = urlParams.get('section') || urlParams.get('roster') || urlParams.get('class_id');
  const targetSearch = urlParams.get('search') || urlParams.get('q');
  const targetProgram = urlParams.get('program') || urlParams.get('course');
  const targetYear = urlParams.get('year') || urlParams.get('year_level');

  // Auto populate filters if provided in URL
  if (targetSec && document.getElementById('filter-section')) {
    const secSelect = document.getElementById('filter-section');
    for (let i = 0; i < secSelect.options.length; i++) {
      if (secSelect.options[i].value.toLowerCase() === targetSec.toLowerCase()) {
        secSelect.value = secSelect.options[i].value;
        break;
      }
    }
  }

  if (targetSearch && document.getElementById('search-class')) {
    document.getElementById('search-class').value = targetSearch;
  }

  if (targetProgram && document.getElementById('filter-program')) {
    document.getElementById('filter-program').value = targetProgram;
  }

  if (targetYear && document.getElementById('filter-year')) {
    document.getElementById('filter-year').value = targetYear;
  }

  // Trigger filtering
  filterClasses();

  // Auto open modal if section or roster is in URL
  if (targetSec) {
    const targetCard = Array.from(document.querySelectorAll('.class-card')).find(c => (c.getAttribute('data-section') || '').toLowerCase() === targetSec.toLowerCase());
    if (targetCard) {
      const cardTitle = targetCard.querySelector('h3')?.textContent?.trim() || ('Section ' + targetSec);
      const cardYear = targetCard.getAttribute('data-year') ? (targetCard.getAttribute('data-year') + ' Year') : 'Official Roster';
      const enrolledCount = sectionRosters[targetSec]?.length || 0;
      openRosterModal(targetSec, cardTitle, cardYear, 'Academic Year 2025–2026', 'Campus Room', enrolledCount, '100%');
    } else if (sectionRosters[targetSec]) {
      openRosterModal(targetSec, 'Section ' + targetSec, 'Official Roster', 'Academic Year 2025–2026', 'Campus Room', sectionRosters[targetSec].length, '100%');
    }
  }
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
