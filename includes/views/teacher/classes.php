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
          <div class="flex items-center gap-2 mb-1">
            <span class="badge badge-present">Teacher Portal</span>
            <span class="text-xs text-slate-500">Academic Year 2025–2026</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">My Assigned Classes &amp; Rosters</h1>
          <p class="text-sm text-slate-500">Manage your course sections, view interactive student rosters, and launch live attendance sessions.</p>
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

      <!-- Quick KPI Stats Bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned Classes</div>
          <div class="text-2xl font-bold text-slate-800 mt-1"><?= count($classes) ?> Course<?= count($classes) !== 1 ? 's' : '' ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5"><?= count($allSections) ?> Academic Section<?= count($allSections) !== 1 ? 's' : '' ?></div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Total Enrolled</div>
          <div class="text-2xl font-bold text-blue-700 mt-1"><?= number_format($totalEnrolledAll) ?> Students</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Official Roster Count</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Avg. Attendance</div>
          <div class="text-2xl font-bold text-emerald-600 mt-1"><?= $overallAvgRate ?>%</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Campus Benchmark: 85%</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Sessions Held</div>
          <div class="text-2xl font-bold text-slate-800 mt-1"><?= $totalSessionsAll ?> Sessions</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Recorded QR Sessions</div>
        </div>
      </div>

      <!-- Course / Year / Section Filter Bar -->
      <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <!-- Course Program -->
          <div>
            <label for="filter-program" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Program / Course</label>
            <select id="filter-program" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Programs</option>
              <?php foreach ($allPrograms as $p): ?>
                <option value="<?= htmlspecialchars($p) ?>"><?= htmlspecialchars($p) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Year Level -->
          <div>
            <label for="filter-year" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Year Level</label>
            <select id="filter-year" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Year Levels</option>
              <?php foreach ($allYears as $y): ?>
                <?php $yOrd = match((int)$y) { 1 => '1st Year', 2 => '2nd Year', 3 => '3rd Year', 4 => '4th Year', default => $y . 'th Year' }; ?>
                <option value="<?= (int)$y ?>"><?= $yOrd ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Section -->
          <div>
            <label for="filter-section" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Section</label>
            <select id="filter-section" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Sections</option>
              <?php foreach ($allSections as $s): ?>
                <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Search keyword -->
          <div>
            <label for="search-class" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Search Subject / Code</label>
            <div class="relative">
              <input type="text" id="search-class" placeholder="e.g. IT301 or Web Dev" class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="filterClasses()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Classes & Rosters Cards Grid -->
      <div id="classes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-6">
        <?php if (empty($classes)): ?>
          <div class="col-span-2 bg-white rounded-2xl p-12 text-center border border-slate-200 text-slate-400">
            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <div class="text-base font-bold text-slate-700">No Assigned Classes Found</div>
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
            <div class="class-card bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-400 transition flex flex-col overflow-hidden"
                 data-program="<?= $programCode ?>"
                 data-year="<?= $yearNum ?>"
                 data-section="<?= $sectionCode ?>"
                 data-title="<?= strtolower($courseCode . ' ' . $courseTitle . ' ' . $sectionCode) ?>">
              <div class="p-6 flex-1">
                <div class="flex items-center justify-between mb-3">
                  <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800"><?= $sectionCode ?></span>
                    <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600"><?= $yearOrdinal ?></span>
                  </div>
                  <span class="badge badge-present text-xs">● Active Class</span>
                </div>
                
                <h3 class="font-bold text-slate-800 text-lg leading-tight mb-1"><?= $courseCode ?> — <?= $courseTitle ?></h3>
                <p class="text-xs text-slate-500 mb-4 flex items-center gap-2">
                  <span>🗓️ <?= $scheduleFormatted ?></span>
                  <span>•</span>
                  <span>📍 Room <?= $roomFormatted ?></span>
                </p>

                <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100 text-center mb-4">
                  <div>
                    <div class="text-xs text-slate-400 font-medium">Enrolled</div>
                    <div class="text-lg font-bold text-slate-800"><?= $enrolled ?></div>
                  </div>
                  <div>
                    <div class="text-xs text-slate-400 font-medium">Sessions</div>
                    <div class="text-lg font-bold text-slate-800"><?= $sessionCount ?></div>
                  </div>
                  <div>
                    <div class="text-xs text-slate-400 font-medium">Avg Rate</div>
                    <div class="text-lg font-bold text-emerald-600"><?= $avgRate ?></div>
                  </div>
                </div>
              </div>

              <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                  <button type="button" class="px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5"
                          onclick="openRosterModal('<?= $sectionCode ?>', '<?= $courseCode ?> — <?= addslashes($courseTitle) ?>', '<?= $yearOrdinal ?>', '<?= addslashes($scheduleFormatted) ?>', 'Room <?= addslashes($roomFormatted) ?>', <?= $enrolled ?>, '<?= $avgRate ?>')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <span>View Student Roster</span>
                  </button>
                  <a href="<?php echo url('teacher/attendance-history?section=' . urlencode($cls['section'])); ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition" title="View Past Attendance Sessions">
                    History
                  </a>
                </div>
                <a href="<?php echo url('teacher/live-session?section=' . urlencode($cls['section'])); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                  <span>Start QR</span>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: CLASS STUDENT ROSTER WITH PAGINATION & SEARCH
══════════════════════════════════════════════════════════════ -->
<div id="roster-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span id="modal-section-badge" class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-100 text-blue-800">BSIT 3-A</span>
          <span id="modal-year-badge" class="text-xs text-slate-500 font-medium">3rd Year</span>
          <span class="text-slate-300">•</span>
          <span id="modal-schedule-text" class="text-xs text-slate-500">Tue/Thu • 08:00 AM – 10:00 AM (Lab 304)</span>
        </div>
        <h2 id="modal-course-title" class="text-lg font-bold text-slate-800">IT301 — Web Development 2</h2>
      </div>
      <button type="button" onclick="closeRosterModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center font-bold text-sm transition">
        
      </button>
    </div>

    <!-- Modal Controls & Search Bar -->
    <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-white shrink-0">
      <div class="relative flex-1 min-w-[220px]">
        <input type="text" id="modal-search-input" placeholder="Search enrolled student by ID or name..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="filterModalStudents()">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <div class="flex items-center gap-2">
        <select id="modal-status-filter" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterModalStudents()">
          <option value="all">All Enrolled</option>
          <option value="good">Good Standing (&gt;85%)</option>
          <option value="at-risk">At Risk (&lt;75%)</option>
        </select>
        <button type="button" onclick="exportRosterCSV()" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition flex items-center gap-1.5">
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

<script>
// Filter Course Offerings on Main Page
function filterClasses() {
  const program = document.getElementById('filter-program').value;
  const year = document.getElementById('filter-year').value;
  const section = document.getElementById('filter-section').value;
  const query = document.getElementById('search-class').value.toLowerCase().trim();

  const cards = document.querySelectorAll('.class-card');
  cards.forEach(card => {
    const cardProg = card.getAttribute('data-program');
    const cardYear = card.getAttribute('data-year');
    const cardSec = card.getAttribute('data-section');
    const cardTitle = card.getAttribute('data-title').toLowerCase();

    const matchProg = (program === 'all' || cardProg === program);
    const matchYear = (year === 'all' || cardYear === year);
    const matchSec = (section === 'all' || cardSec === section);
    const matchQuery = (!query || cardTitle.includes(query));

    if (matchProg && matchYear && matchSec && matchQuery) {
      card.style.display = 'flex';
    } else {
      card.style.display = 'none';
    }
  });
}

// Section rosters passed dynamically from live database
const sectionRosters = <?= json_encode($rostersBySection, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
let currentSection = '';
let currentSectionRoster = [];
let filteredList = [];
let currentPage = 1;
const pageSize = 5;

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
  prevBtn.className = `px-2.5 py-1 rounded text-xs font-semibold ${currentPage <= 1 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'}`;
  prevBtn.textContent = '« Prev';
  prevBtn.onclick = () => setModalPage(currentPage - 1);
  btnContainer.appendChild(prevBtn);

  // Page Numbers
  for (let i = 1; i <= totalPages; i++) {
    const pageBtn = document.createElement('button');
    pageBtn.type = 'button';
    pageBtn.className = `w-7 h-7 rounded text-xs font-bold transition ${i === currentPage ? 'bg-blue-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'}`;
    pageBtn.textContent = i;
    pageBtn.onclick = () => setModalPage(i);
    btnContainer.appendChild(pageBtn);
  }

  // Next
  const nextBtn = document.createElement('button');
  nextBtn.type = 'button';
  nextBtn.disabled = (currentPage >= totalPages);
  nextBtn.className = `px-2.5 py-1 rounded text-xs font-semibold ${currentPage >= totalPages ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'}`;
  nextBtn.textContent = 'Next »';
  nextBtn.onclick = () => setModalPage(currentPage + 1);
  btnContainer.appendChild(nextBtn);
}

function escapeHtml(text) {
  const div = document.createElement('div');
  div.textContent = text || '';
  return div.innerHTML;
}

// Auto open modal if ?section or ?roster is in URL
window.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const targetSec = urlParams.get('section') || urlParams.get('roster') || urlParams.get('class_id');
  if (targetSec && sectionRosters[targetSec]) {
    openRosterModal(targetSec, 'Section ' + targetSec, 'Official Roster', 'Academic Year 2025–2026', 'Room 402', sectionRosters[targetSec].length, '100%');
  }
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
