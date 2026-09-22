<?php
/**
 * Student Attendance History — includes/views/student/history.php
 * Displays live attendance history, overall attendance statistics, punctuality metrics,
 * and filtered session records for the authenticated student.
 */

if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}

$page_title = 'My Attendance History';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/controllers/StudentController.php';

$db = Database::getConnection();

// 1. Resolve Active Student (supports testing switcher and session fallback)
$selectedStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($selectedStudentId > 0) {
    $_SESSION['active_student_test_id'] = $selectedStudentId;
}

$studentUserId = $_SESSION['active_student_test_id'] ?? StudentController::resolveCurrentStudentId();

// Fetch student profile details
$studStmt = $db->prepare("
    SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email,
           r.course, r.section, r.year_level
    FROM users u
    LEFT JOIN class_roster r ON r.student_id = u.user_id
    WHERE u.user_id = ?
    LIMIT 1
");
$studStmt->execute([$studentUserId]);
$currentStudent = $studStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentStudent) {
    // Fallback to first student in users table
    $currentStudent = $db->query("
        SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email,
               r.course, r.section, r.year_level
        FROM users u
        LEFT JOIN class_roster r ON r.student_id = u.user_id
        WHERE u.role = 'student' 
        ORDER BY u.user_id ASC 
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
    $studentUserId = (int)($currentStudent['user_id'] ?? 1);
}

$studentName = $currentStudent ? trim(($currentStudent['first_name'] ?? 'Juan') . ' ' . ($currentStudent['last_name'] ?? 'Dela Cruz')) : 'Student';
$studentNumber = !empty($currentStudent['student_id']) ? $currentStudent['student_id'] : ('23011' . str_pad((string)$studentUserId, 4, '0', STR_PAD_LEFT));
$studentSection = $currentStudent['section'] ?? 'BSIT 3-A';

// Fetch all students for test switcher
$allStudents = $db->query("
    SELECT user_id, student_id, first_name, last_name 
    FROM users 
    WHERE role = 'student' 
    ORDER BY user_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Live Attendance Metrics & Streak for This Student
$allAttStmt = $db->prepare("
    SELECT a.attendance_id, a.date, a.time, a.subject, a.status
    FROM attendance a
    WHERE a.student_id = :sid
    ORDER BY a.date DESC, a.time DESC
");
$allAttStmt->execute([':sid' => $studentUserId]);
$allStudentRecords = $allAttStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$totalSessions = count($allStudentRecords);
$presentCount = 0;
$tardyCount = 0;
$absentCount = 0;
$consecutiveAbsences = 0;
$streakActive = true;

foreach ($allStudentRecords as $r) {
    $st = strtolower($r['status'] ?? 'present');
    if ($st === 'absent') {
        $absentCount++;
        if ($streakActive) {
            $consecutiveAbsences++;
        }
    } elseif ($st === 'present' || $st === 'tardy' || $st === 'late') {
        if ($st === 'present') $presentCount++;
        if ($st === 'tardy' || $st === 'late') $tardyCount++;
        $streakActive = false;
    }
}

// Fetch approved excuse slips count
$excuseStmt = $db->prepare("
    SELECT COUNT(*) 
    FROM excuse_slips 
    WHERE student_id = ? AND status = 'approved'
");
$excuseStmt->execute([$studentUserId]);
$approvedExcuses = (int)$excuseStmt->fetchColumn();

// Attendance and Punctuality Percentages
$attendanceRate = ($totalSessions > 0) ? round((($presentCount + $tardyCount) / $totalSessions) * 100, 1) : 100.0;
$onTimeRate     = ($totalSessions > 0) ? round(($presentCount / $totalSessions) * 100, 1) : 100.0;

// 3. Fetch Enrolled Courses for Filter Dropdown
$enrolledStmt = $db->prepare("
    SELECT DISTINCT cr.course_code, cr.course_title 
    FROM class_roster cr 
    WHERE cr.student_id = ?
    ORDER BY cr.course_code ASC
");
$enrolledStmt->execute([$studentUserId]);
$enrolledSubjects = $enrolledStmt->fetchAll(PDO::FETCH_ASSOC);

// Distinct subjects from attendance table if roster is empty
$attSubjects = $db->prepare("SELECT DISTINCT subject FROM attendance WHERE student_id = ?");
$attSubjects->execute([$studentUserId]);
$recordedSubjects = $attSubjects->fetchAll(PDO::FETCH_COLUMN);

// 4. Read Filter Parameters
$filterSubject = trim($_GET['subject'] ?? 'all');
$filterStatus  = strtolower(trim($_GET['status'] ?? 'all'));

// 5. Query Filtered Attendance Records
$attWhere = ["a.student_id = :student_id"];
$attParams = [':student_id' => $studentUserId];

if ($filterSubject !== '' && $filterSubject !== 'all') {
    $attWhere[] = "(cr.course_code = :subject OR a.subject LIKE :subject_term)";
    $attParams[':subject'] = $filterSubject;
    $attParams[':subject_term'] = '%' . $filterSubject . '%';
}

if ($filterStatus !== '' && $filterStatus !== 'all') {
    $normalizedStatus = ($filterStatus === 'late') ? 'tardy' : $filterStatus;
    $attWhere[] = "a.status = :status";
    $attParams[':status'] = $normalizedStatus;
}

$attWhereSql = implode(' AND ', $attWhere);

$recordsStmt = $db->prepare("
    SELECT 
        a.attendance_id,
        a.date,
        a.time,
        a.subject,
        a.status,
        a.qr_session_id,
        COALESCE(cr.section, :default_sec) AS section,
        cr.course_code,
        cr.course_title,
        cr.room_number,
        CONCAT(t.first_name, ' ', t.last_name) AS instructor_name
    FROM attendance a
    LEFT JOIN users t ON a.teacher_id = t.user_id
    LEFT JOIN class_roster cr ON (cr.student_id = a.student_id AND cr.teacher_id = a.teacher_id)
    WHERE {$attWhereSql}
    ORDER BY a.date DESC, a.time DESC
");
$attParams[':default_sec'] = $studentSection;
$recordsStmt->execute($attParams);
$attendanceRecords = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);

// 5.1 Pagination Configuration & Calculation
$perPage = isset($_GET['per_page']) ? max(5, min(100, (int)$_GET['per_page'])) : 10;
$totalRecords = count($attendanceRecords);
$totalPages = max(1, (int)ceil($totalRecords / $perPage));
$currentPage = isset($_GET['page']) ? max(1, min($totalPages, (int)$_GET['page'])) : 1;
$offset = ($currentPage - 1) * $perPage;
$pagedRecords = array_slice($attendanceRecords, $offset, $perPage);
$fromRecord = $totalRecords > 0 ? $offset + 1 : 0;
$toRecord = min($offset + $perPage, $totalRecords);

if (!function_exists('getStudentHistoryUrl')) {
    function getStudentHistoryUrl($page, $studentUserId, $filterSubject, $filterStatus, $perPage = 10) {
        $params = [];
        if (!empty($studentUserId)) $params['student_id'] = $studentUserId;
        if (!empty($filterSubject) && $filterSubject !== 'all') $params['subject'] = $filterSubject;
        if (!empty($filterStatus) && $filterStatus !== 'all') $params['status'] = $filterStatus;
        if ($perPage !== 10) $params['per_page'] = $perPage;
        $params['page'] = $page;
        return url('student/history?' . http_build_query($params));
    }
}

// 6. Map Approved Excuse Slips by Date for Badge Annotations
$slipsStmt = $db->prepare("
    SELECT excuse_slip_id, date_of_absence, status, reason
    FROM excuse_slips
    WHERE student_id = ?
");
$slipsStmt->execute([$studentUserId]);
$excuseSlips = $slipsStmt->fetchAll(PDO::FETCH_ASSOC);
$approvedSlips = [];
foreach ($excuseSlips as $slip) {
    if ($slip['status'] === 'approved') {
        $approvedSlips[$slip['date_of_absence']] = $slip;
    }
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
          <div class="flex flex-wrap items-center gap-2 mb-1">
            <span class="badge badge-present">Student Portal</span>
            <span class="text-xs text-slate-500 font-semibold">
              <?php echo htmlspecialchars($studentName); ?> &bull; <?php echo htmlspecialchars($studentNumber); ?>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
              Live Database
            </span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">My Attendance History &amp; Records</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Track all your verified check-ins, punctuality, approved excuse slips, and course attendance percentages.
          </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
          <!-- Student Switcher for Testing -->
          <?php if (!empty($allStudents)): ?>
            <form method="GET" class="inline-flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">
              <span class="text-xs text-slate-500 font-semibold uppercase tracking-wider">Test Student:</span>
              <select name="student_id" onchange="this.form.submit()" class="text-xs font-semibold px-2 py-1 border border-indigo-200 rounded-md bg-indigo-50 text-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <?php foreach ($allStudents as $st): ?>
                  <option value="<?php echo $st['user_id']; ?>" <?php echo ($st['user_id'] == $studentUserId) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name'] . ' (' . ($st['student_id'] ?: '23011000' . $st['user_id']) . ')'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          <?php endif; ?>

          <a href="<?php echo url('student/scanner'); ?>" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Scan Today's QR</span>
          </a>
          <a href="<?php echo url('student/excuse-slips'); ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition">
            File Excuse Slip
          </a>
        </div>
      </div>

      <?php if ($consecutiveAbsences >= 3): ?>
        <!-- CRITICAL: 3+ Consecutive Absence Dropout Indicator Banner -->
        <div class="mb-6 p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 shadow-sm text-slate-800">
          <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
            <div class="flex items-start gap-3.5">
              <div class="w-11 h-11 rounded-xl bg-rose-600 text-white flex items-center justify-center font-black text-xl shrink-0 shadow-sm">
                !
              </div>
              <div>
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="px-2.5 py-0.5 rounded-md text-[11px] font-black uppercase tracking-wider bg-rose-600 text-white">
                    3+ Consecutive Absence Dropout Indicator
                  </span>
                  <span class="px-2.5 py-0.5 rounded-md text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                    <?php echo (int)$consecutiveAbsences; ?> Consecutive Unexcused Absences
                  </span>
                </div>
                <h3 class="text-base font-black text-rose-900 mt-1.5">
                  Academic Alert: You are flagged on the Dropout Risk Watchlist
                </h3>
                <p class="text-xs text-rose-700 mt-1 leading-relaxed max-w-3xl">
                  Under college institutional policy, having <strong>3 or more consecutive unexcused absences</strong> flags a student for severe dropout vulnerability. Automated summary notices are submitted to your instructor and registered parent email. To prevent unofficial dropping or academic disqualification, please file a medical or official excuse slip immediately or consult your instructor.
                </p>
              </div>
            </div>
            <div class="flex items-center gap-2.5 shrink-0 w-full md:w-auto">
              <a href="<?php echo url('student/excuse-slips'); ?>" class="w-full md:w-auto text-center px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition">
                File Excuse Slip Now
              </a>
            </div>
          </div>
        </div>
      <?php elseif ($absentCount >= 3): ?>
        <!-- MODERATE: Attendance Caution Banner -->
        <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-300 text-slate-800 shadow-xs">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-amber-500 text-white flex items-center justify-center font-black text-sm shrink-0">
                ⚠
              </div>
              <div>
                <div class="text-xs font-bold text-amber-900">
                  Attendance Caution: <?php echo (int)$absentCount; ?> Total Absences Recorded
                </div>
                <p class="text-[11px] text-amber-700 mt-0.5">
                  Maintain regular attendance to avoid triggering the 3+ Consecutive Absence Dropout Watchlist. Be sure to submit verified excuse slips for valid absences.
                </p>
              </div>
            </div>
            <a href="<?php echo url('student/excuse-slips'); ?>" class="text-xs font-bold text-amber-800 hover:text-amber-900 underline whitespace-nowrap">
              Submit Excuse Slip →
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Performance Overview KPI Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <!-- Total Sessions -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-slate-400 font-semibold uppercase">Total Sessions</div>
          <div class="text-2xl font-black text-slate-800 mt-1"><?php echo $totalSessions; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">
            <?php echo count($enrolledSubjects) ?: max(1, count($recordedSubjects)); ?> Subject(s) Enrolled
          </div>
        </div>

        <!-- Present -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-emerald-600 font-semibold uppercase">Present</div>
          <div class="text-2xl font-black text-emerald-600 mt-1"><?php echo $presentCount; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">
            <?php echo $onTimeRate; ?>% on-time rate
          </div>
        </div>

        <!-- Late / Tardy -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-amber-600 font-semibold uppercase">Late / Tardy</div>
          <div class="text-2xl font-black text-amber-600 mt-1"><?php echo $tardyCount; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">
            <?php echo ($totalSessions > 0) ? round(($tardyCount / $totalSessions) * 100, 1) : 0; ?>% tardiness rate
          </div>
        </div>

        <!-- Absences -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-rose-600 font-semibold uppercase">Absences</div>
          <div class="text-2xl font-black text-rose-600 mt-1"><?php echo $absentCount; ?></div>
          <div class="text-[11px] <?php echo $consecutiveAbsences >= 3 ? 'text-rose-600 font-bold' : 'text-slate-500'; ?> mt-0.5">
            <?php echo $consecutiveAbsences > 0 ? "{$consecutiveAbsences} Consecutive" : "{$approvedExcuses} Excused"; ?>
          </div>
        </div>
      </div>

      <!-- Filters & Search -->
      <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          <!-- Subject Filter -->
          <div>
            <select id="attendance-filter-subject" onchange="applyAttendanceFilters()" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
              <option value="all">All Enrolled Subjects</option>
              <?php if (!empty($enrolledSubjects)): ?>
                <?php foreach ($enrolledSubjects as $sub): ?>
                  <option value="<?php echo htmlspecialchars(strtolower($sub['course_code'])); ?>" <?php echo (strtolower($filterSubject) === strtolower($sub['course_code'])) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($sub['course_code'] . ' — ' . $sub['course_title']); ?>
                  </option>
                <?php endforeach; ?>
              <?php elseif (!empty($recordedSubjects)): ?>
                <?php foreach ($recordedSubjects as $rSub): ?>
                  <option value="<?php echo htmlspecialchars(strtolower($rSub)); ?>" <?php echo (strtolower($filterSubject) === strtolower($rSub)) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($rSub); ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <!-- Status Filter -->
          <div>
            <select id="attendance-filter-status" onchange="applyAttendanceFilters()" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
              <option value="all" <?php echo ($filterStatus === 'all') ? 'selected' : ''; ?>>All Statuses</option>
              <option value="present" <?php echo ($filterStatus === 'present') ? 'selected' : ''; ?>>Present Only</option>
              <option value="tardy" <?php echo ($filterStatus === 'tardy' || $filterStatus === 'late') ? 'selected' : ''; ?>>Late / Tardy Only</option>
              <option value="absent" <?php echo ($filterStatus === 'absent') ? 'selected' : ''; ?>>Absent Only</option>
            </select>
          </div>

          <button type="button" id="btn-clear-filters" onclick="resetAttendanceFilters()" class="hidden text-xs text-indigo-600 hover:underline font-semibold ml-2 cursor-pointer">
            Clear Filters
          </button>
        </div>

        <div class="text-xs text-slate-400">
          Showing <strong id="filter-summary-text" class="text-slate-700"><?php echo min(10, count($attendanceRecords)); ?> of <?php echo count($attendanceRecords); ?> verified record(s)</strong>
        </div>
      </div>

      <!-- Attendance Records Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h2 id="table-header-title" class="text-sm font-bold text-slate-800">Historical Attendance Log (<?php echo count($attendanceRecords); ?> Records)</h2>
          <span id="table-page-subtitle" class="text-xs text-slate-400">Chronological Record &bull; Instant Pagination</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200">
              <tr>
                <th class="py-3 px-4">Date &amp; Time</th>
                <th class="py-3 px-4">Course / Subject</th>
                <th class="py-3 px-4">Section</th>
                <th class="py-3 px-4">Instructor</th>
                <th class="py-3 px-4">Method</th>
                <th class="py-3 px-4">Status</th>
              </tr>
            </thead>
            <tbody id="attendance-tbody" class="divide-y divide-slate-100 text-slate-700">
              <!-- Empty state row -->
              <tr id="no-attendance-row" class="<?php echo empty($attendanceRecords) ? '' : 'hidden'; ?>">
                <td colspan="6" class="text-center py-12 text-slate-400">
                  <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                  <p class="text-sm font-semibold text-slate-600">No attendance records found</p>
                  <p class="text-xs text-slate-400 mt-1">You do not have any recorded sessions matching this filter.</p>
                </td>
              </tr>

              <?php if (!empty($attendanceRecords)): ?>
                <?php foreach ($attendanceRecords as $idx => $row): 
                  $status = strtolower($row['status'] ?? 'present');
                  $normStatus = ($status === 'late') ? 'tardy' : $status;
                  $dateFormatted = date('M d, Y', strtotime($row['date']));
                  $timeFormatted = !empty($row['time']) ? date('h:i A', strtotime($row['time'])) : '—';
                  $courseDisplay = $row['course_title'] ? ($row['course_code'] . ' — ' . $row['course_title']) : ($row['subject'] ?: 'Web Systems');
                  $roomDisplay   = !empty($row['room_number']) ? 'Room ' . $row['room_number'] : 'Lab / Classroom';
                  $sectionDisplay = $row['section'] ?: '31001';
                  $instructor    = !empty(trim($row['instructor_name'] ?? '')) ? 'Prof. ' . trim($row['instructor_name']) : 'Faculty Instructor';
                  $subjectSearchKey = strtolower(trim(($row['course_code'] ?? '') . ' ' . ($row['course_title'] ?? '') . ' ' . ($row['subject'] ?? '')));

                  // Verification method
                  if (!empty($row['qr_session_id'])) {
                      $methodBadge = '<span class="inline-flex items-center gap-1 text-[11px] text-slate-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Dynamic QR</span>';
                  } elseif ($status === 'absent') {
                      $methodBadge = '<span class="inline-flex items-center gap-1 text-[11px] text-rose-600"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Auto-Absence</span>';
                  } else {
                      $methodBadge = '<span class="inline-flex items-center gap-1 text-[11px] text-blue-600"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Manual Entry</span>';
                  }

                  // Status badge (with excuse detection)
                  $hasApprovedSlip = isset($approvedSlips[$row['date']]);
                ?>
                  <tr class="attendance-row hover:bg-slate-50/80 transition" 
                      data-subject="<?php echo htmlspecialchars($subjectSearchKey); ?>"
                      data-course-code="<?php echo htmlspecialchars(strtolower($row['course_code'] ?? '')); ?>"
                      data-status="<?php echo htmlspecialchars($normStatus); ?>">
                    <td class="py-3 px-4 font-semibold text-slate-800 whitespace-nowrap">
                      <?php echo htmlspecialchars($dateFormatted); ?> &bull; <?php echo htmlspecialchars($timeFormatted); ?>
                    </td>
                    <td class="py-3 px-4">
                      <div class="font-bold text-slate-800"><?php echo htmlspecialchars($courseDisplay); ?></div>
                      <div class="text-[10px] text-slate-400"><?php echo htmlspecialchars($roomDisplay); ?></div>
                    </td>
                    <td class="py-3 px-4">
                      <span class="px-2 py-0.5 rounded bg-blue-100 text-blue-800 font-bold text-[10px]">
                        <?php echo htmlspecialchars($sectionDisplay); ?>
                      </span>
                    </td>
                    <td class="py-3 px-4 text-slate-600">
                      <?php echo htmlspecialchars($instructor); ?>
                    </td>
                    <td class="py-3 px-4 whitespace-nowrap">
                      <?php echo $methodBadge; ?>
                    </td>
                    <td class="py-3 px-4 whitespace-nowrap">
                      <?php if ($status === 'present'): ?>
                        <span class="badge badge-present font-bold">● Present</span>
                      <?php elseif ($status === 'tardy' || $status === 'late'): ?>
                        <span class="badge badge-tardy font-bold">● Late / Tardy</span>
                      <?php elseif ($hasApprovedSlip): ?>
                        <span class="badge badge-excused font-bold">● Excused (Slip #<?php echo $approvedSlips[$row['date']]['excuse_slip_id']; ?>)</span>
                      <?php else: ?>
                        <span class="badge badge-absent font-bold">● Absent</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Dynamic Client-Side Pagination Controls Footer -->
        <div id="attendance-pagination-bar" class="px-5 py-3.5 bg-slate-50/90 border-t border-slate-200/80 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
          <div id="attendance-showing-info" class="text-slate-500 font-medium">
            Showing <span id="pag-from" class="font-bold text-slate-800">1</span> to <span id="pag-to" class="font-bold text-slate-800">10</span> of <span id="pag-total" class="font-bold text-slate-800"><?php echo count($attendanceRecords); ?></span> records
          </div>

          <div class="flex items-center gap-1.5 flex-wrap">
            <!-- Prev Button -->
            <button type="button" id="btn-prev-page" onclick="changeAttendancePage(currentAttendancePage - 1)" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-semibold transition inline-flex items-center gap-1 shadow-2xs cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:bg-slate-100/60 disabled:text-slate-400">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              <span>Prev</span>
            </button>

            <!-- Numbered Buttons Container -->
            <div id="pagination-numbers" class="flex items-center gap-1">
              <!-- Dynamically populated without page refresh -->
            </div>

            <!-- Next Button -->
            <button type="button" id="btn-next-page" onclick="changeAttendancePage(currentAttendancePage + 1)" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-semibold transition inline-flex items-center gap-1 shadow-2xs cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:bg-slate-100/60 disabled:text-slate-400">
              <span>Next</span>
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
/**
 * Instant Client-Side Pagination & Filtering (No Page Reload)
 */
let currentAttendancePage = 1;
const attendancePerPage = 10;

function getFilteredAttendanceRows() {
  const subjectFilter = (document.getElementById('attendance-filter-subject')?.value || 'all').toLowerCase();
  const statusFilter = (document.getElementById('attendance-filter-status')?.value || 'all').toLowerCase();
  const allRows = Array.from(document.querySelectorAll('.attendance-row'));

  return allRows.filter(row => {
    const rowSubject = (row.getAttribute('data-subject') || '').toLowerCase();
    const rowCourseCode = (row.getAttribute('data-course-code') || '').toLowerCase();
    const rowStatus = (row.getAttribute('data-status') || '').toLowerCase();

    const matchesSubject = (subjectFilter === 'all') || rowCourseCode === subjectFilter || rowSubject.includes(subjectFilter);
    const matchesStatus = (statusFilter === 'all') || (rowStatus === statusFilter);

    return matchesSubject && matchesStatus;
  });
}

function renderAttendanceTable() {
  const filteredRows = getFilteredAttendanceRows();
  const allRows = Array.from(document.querySelectorAll('.attendance-row'));
  const total = filteredRows.length;
  const totalPages = Math.max(1, Math.ceil(total / attendancePerPage));

  // Keep current page within valid bounds
  if (currentAttendancePage > totalPages) currentAttendancePage = totalPages;
  if (currentAttendancePage < 1) currentAttendancePage = 1;

  const startIndex = (currentAttendancePage - 1) * attendancePerPage;
  const endIndex = startIndex + attendancePerPage;

  // Hide all rows first
  allRows.forEach(row => { row.style.display = 'none'; });

  // Show only rows belonging to the current page
  filteredRows.forEach((row, idx) => {
    if (idx >= startIndex && idx < endIndex) {
      row.style.display = '';
    }
  });

  // Empty state handling
  const noRecordsRow = document.getElementById('no-attendance-row');
  if (noRecordsRow) {
    noRecordsRow.className = total === 0 ? '' : 'hidden';
  }

  // Update summary counts
  const fromRecord = total > 0 ? startIndex + 1 : 0;
  const toRecord = Math.min(endIndex, total);

  const pagFrom = document.getElementById('pag-from');
  const pagTo = document.getElementById('pag-to');
  const pagTotal = document.getElementById('pag-total');
  const filterSummary = document.getElementById('filter-summary-text');
  const tableTitle = document.getElementById('table-header-title');
  const tableSubtitle = document.getElementById('table-page-subtitle');
  const clearBtn = document.getElementById('btn-clear-filters');

  if (pagFrom) pagFrom.textContent = fromRecord;
  if (pagTo) pagTo.textContent = toRecord;
  if (pagTotal) pagTotal.textContent = total;
  if (filterSummary) filterSummary.innerHTML = `<strong>${fromRecord}–${toRecord}</strong> of <strong>${total} verified record(s)</strong>`;
  if (tableTitle) tableTitle.textContent = `Historical Attendance Log (${total} Records)`;
  if (tableSubtitle) tableSubtitle.innerHTML = `Chronological Record &bull; Page ${currentAttendancePage} of ${totalPages}`;

  const subjectVal = document.getElementById('attendance-filter-subject')?.value || 'all';
  const statusVal = document.getElementById('attendance-filter-status')?.value || 'all';
  if (clearBtn) {
    clearBtn.classList.toggle('hidden', subjectVal === 'all' && statusVal === 'all');
  }

  // Render pagination buttons
  renderAttendancePaginationControls(totalPages);

  // Update URL silently without reloading
  try {
    const url = new URL(window.location.href);
    if (currentAttendancePage > 1) {
      url.searchParams.set('page', currentAttendancePage);
    } else {
      url.searchParams.delete('page');
    }
    if (subjectVal !== 'all') {
      url.searchParams.set('subject', subjectVal);
    } else {
      url.searchParams.delete('subject');
    }
    if (statusVal !== 'all') {
      url.searchParams.set('status', statusVal);
    } else {
      url.searchParams.delete('status');
    }
    window.history.replaceState({}, '', url.toString());
  } catch (e) {}
}

function renderAttendancePaginationControls(totalPages) {
  const paginationBar = document.getElementById('attendance-pagination-bar');
  const numbersContainer = document.getElementById('pagination-numbers');
  const btnPrev = document.getElementById('btn-prev-page');
  const btnNext = document.getElementById('btn-next-page');

  if (!paginationBar || !numbersContainer) return;

  if (totalPages <= 1) {
    paginationBar.classList.add('hidden');
    return;
  }
  paginationBar.classList.remove('hidden');

  if (btnPrev) btnPrev.disabled = (currentAttendancePage <= 1);
  if (btnNext) btnNext.disabled = (currentAttendancePage >= totalPages);

  let html = '';
  const startP = Math.max(1, currentAttendancePage - 2);
  const endP = Math.min(totalPages, currentAttendancePage + 2);

  if (startP > 1) {
    html += `<button type="button" onclick="changeAttendancePage(1)" class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-semibold transition flex items-center justify-center shadow-2xs cursor-pointer">1</button>`;
    if (startP > 2) {
      html += `<span class="px-1 text-slate-400 font-medium select-none">...</span>`;
    }
  }

  for (let p = startP; p <= endP; p++) {
    if (p === currentAttendancePage) {
      html += `<button type="button" class="w-8 h-8 rounded-lg bg-blue-600 text-white font-bold flex items-center justify-center shadow-xs cursor-default">${p}</button>`;
    } else {
      html += `<button type="button" onclick="changeAttendancePage(${p})" class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-semibold transition flex items-center justify-center shadow-2xs cursor-pointer">${p}</button>`;
    }
  }

  if (endP < totalPages) {
    if (endP < totalPages - 1) {
      html += `<span class="px-1 text-slate-400 font-medium select-none">...</span>`;
    }
    html += `<button type="button" onclick="changeAttendancePage(${totalPages})" class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-slate-700 font-semibold transition flex items-center justify-center shadow-2xs cursor-pointer">${totalPages}</button>`;
  }

  numbersContainer.innerHTML = html;
}

function changeAttendancePage(page) {
  currentAttendancePage = page;
  renderAttendanceTable();
}

function applyAttendanceFilters() {
  currentAttendancePage = 1;
  renderAttendanceTable();
}

function resetAttendanceFilters() {
  const subj = document.getElementById('attendance-filter-subject');
  const stat = document.getElementById('attendance-filter-status');
  if (subj) subj.value = 'all';
  if (stat) stat.value = 'all';
  currentAttendancePage = 1;
  renderAttendanceTable();
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', function() {
  const params = new URLSearchParams(window.location.search);
  const initialPage = parseInt(params.get('page'), 10);
  if (!isNaN(initialPage) && initialPage > 1) {
    currentAttendancePage = initialPage;
  }
  renderAttendanceTable();
});
</script>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
