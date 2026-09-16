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

$db = Database::getConnection();

// 1. Resolve Active Student (supports testing switcher)
$selectedStudentId = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
if ($selectedStudentId > 0) {
    $_SESSION['active_student_test_id'] = $selectedStudentId;
}

$activeStudentId = $_SESSION['active_student_test_id'] ?? (int)($_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? 1);

// Fetch current student profile
$stStmt = $db->prepare("
    SELECT user_id, student_id, first_name, last_name, email 
    FROM users 
    WHERE user_id = ? AND role = 'student' 
    LIMIT 1
");
$stStmt->execute([$activeStudentId]);
$currentStudent = $stStmt->fetch(PDO::FETCH_ASSOC);

if (!$currentStudent) {
    // Fallback to first student in users table
    $currentStudent = $db->query("
        SELECT user_id, student_id, first_name, last_name, email 
        FROM users 
        WHERE role = 'student' 
        ORDER BY user_id ASC 
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);
}

$studentUserId = (int)($currentStudent['user_id'] ?? 1);
$studentName = trim(($currentStudent['first_name'] ?? 'Juan') . ' ' . ($currentStudent['last_name'] ?? 'Dela Cruz'));
$studentNumber = $currentStudent['student_id'] ?: ('23011000' . $studentUserId);

// Fetch all students for test switcher
$allStudents = $db->query("
    SELECT user_id, student_id, first_name, last_name 
    FROM users 
    WHERE role = 'student' 
    ORDER BY user_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch Live Attendance Metrics for This Student
$metricsStmt = $db->prepare("
    SELECT 
        COUNT(*) AS total_sessions,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN status = 'tardy' THEN 1 ELSE 0 END) AS tardy_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) AS absent_count
    FROM attendance
    WHERE student_id = ?
");
$metricsStmt->execute([$studentUserId]);
$metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC) ?: [
    'total_sessions' => 0,
    'present_count' => 0,
    'tardy_count' => 0,
    'absent_count' => 0
];

$totalSessions = (int)($metrics['total_sessions'] ?? 0);
$presentCount  = (int)($metrics['present_count'] ?? 0);
$tardyCount    = (int)($metrics['tardy_count'] ?? 0);
$absentCount   = (int)($metrics['absent_count'] ?? 0);

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
        cr.section,
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
$recordsStmt->execute($attParams);
$attendanceRecords = $recordsStmt->fetchAll(PDO::FETCH_ASSOC);

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
            <span class="text-xs text-slate-500 font-medium">
              <?php echo htmlspecialchars($studentName); ?> &bull; <?php echo htmlspecialchars($studentNumber); ?>
            </span>
            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
              Live Database
            </span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">My Attendance History &amp; Records</h1>
          <p class="text-sm text-slate-500">Track all your verified check-ins, punctuality, approved excuse slips, and course attendance percentages.</p>
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

          <a href="<?php echo url('student/scanner'); ?>" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Scan Today's QR</span>
          </a>
          <a href="<?php echo url('student/excuse-slips'); ?>" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium transition">
            File Excuse Slip
          </a>
        </div>
      </div>

      <!-- Real Performance Overview Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <!-- Total Sessions -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-slate-400 font-semibold uppercase">Total Sessions</div>
          <div class="text-2xl font-bold text-slate-800 mt-1"><?php echo $totalSessions; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">
            <?php echo count($enrolledSubjects) ?: max(1, count($recordedSubjects)); ?> Subject(s) Enrolled
          </div>
        </div>

        <!-- Present -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-emerald-600 font-semibold uppercase">Present</div>
          <div class="text-2xl font-bold text-emerald-600 mt-1"><?php echo $presentCount; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">
            <?php echo $onTimeRate; ?>% on-time rate
          </div>
        </div>

        <!-- Late / Tardy -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-amber-600 font-semibold uppercase">Late / Tardy</div>
          <div class="text-2xl font-bold text-amber-600 mt-1"><?php echo $tardyCount; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">
            <?php echo ($totalSessions > 0) ? round(($tardyCount / $totalSessions) * 100, 1) : 0; ?>% tardiness rate
          </div>
        </div>

        <!-- Absences -->
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs text-rose-600 font-semibold uppercase">Absences</div>
          <div class="text-2xl font-bold text-rose-600 mt-1"><?php echo $absentCount; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">
            <?php echo $approvedExcuses; ?> Excused
          </div>
        </div>
      </div>

      <!-- Filters & Search -->
      <form method="GET" action="<?php echo url('student/history'); ?>" class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
        <input type="hidden" name="student_id" value="<?php echo $studentUserId; ?>">

        <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
          <!-- Subject Filter -->
          <div>
            <select name="subject" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="this.form.submit()">
              <option value="all">All Enrolled Subjects</option>
              <?php if (!empty($enrolledSubjects)): ?>
                <?php foreach ($enrolledSubjects as $sub): ?>
                  <option value="<?php echo htmlspecialchars($sub['course_code']); ?>" <?php echo ($filterSubject === $sub['course_code']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($sub['course_code'] . ' — ' . $sub['course_title']); ?>
                  </option>
                <?php endforeach; ?>
              <?php elseif (!empty($recordedSubjects)): ?>
                <?php foreach ($recordedSubjects as $rSub): ?>
                  <option value="<?php echo htmlspecialchars($rSub); ?>" <?php echo ($filterSubject === $rSub) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($rSub); ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>

          <!-- Status Filter -->
          <div>
            <select name="status" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" onchange="this.form.submit()">
              <option value="all" <?php echo ($filterStatus === 'all') ? 'selected' : ''; ?>>All Statuses</option>
              <option value="present" <?php echo ($filterStatus === 'present') ? 'selected' : ''; ?>>Present Only</option>
              <option value="tardy" <?php echo ($filterStatus === 'tardy' || $filterStatus === 'late') ? 'selected' : ''; ?>>Late / Tardy Only</option>
              <option value="absent" <?php echo ($filterStatus === 'absent') ? 'selected' : ''; ?>>Absent Only</option>
            </select>
          </div>

          <?php if ($filterSubject !== 'all' || $filterStatus !== 'all'): ?>
            <a href="<?php echo url('student/history?student_id=' . $studentUserId); ?>" class="text-xs text-indigo-600 hover:underline font-semibold ml-2">
              Clear Filters
            </a>
          <?php endif; ?>
        </div>

        <div class="text-xs text-slate-400">
          Showing <strong class="text-slate-700"><?php echo count($attendanceRecords); ?> verified record(s)</strong>
        </div>
      </form>

      <!-- Attendance Records Table -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
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
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <?php if (empty($attendanceRecords)): ?>
                <tr>
                  <td colspan="6" class="text-center py-12 text-slate-400">
                    <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <p class="text-sm font-semibold text-slate-600">No attendance records found</p>
                    <p class="text-xs text-slate-400 mt-1">You do not have any recorded sessions matching this filter.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($attendanceRecords as $row): 
                  $status = strtolower($row['status']);
                  $dateFormatted = date('M d, Y', strtotime($row['date']));
                  $timeFormatted = !empty($row['time']) ? date('h:i A', strtotime($row['time'])) : '—';
                  $courseDisplay = $row['course_title'] ? ($row['course_code'] . ' — ' . $row['course_title']) : ($row['subject'] ?: 'Web Systems');
                  $roomDisplay   = !empty($row['room_number']) ? 'Room ' . $row['room_number'] : 'Lab / Classroom';
                  $sectionDisplay = $row['section'] ?: '31001';
                  $instructor    = !empty($row['instructor_name']) ? 'Prof. ' . $row['instructor_name'] : 'Prof. Ramirez';

                  // Verification method
                  if (!empty($row['qr_session_id'])) {
                      $methodLabel = 'Dynamic QR';
                      $methodBadge = '<span class="inline-flex items-center gap-1 text-[11px] text-slate-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Dynamic QR</span>';
                  } elseif ($status === 'absent') {
                      $methodLabel = 'Auto-Absence';
                      $methodBadge = '<span class="inline-flex items-center gap-1 text-[11px] text-rose-600"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Auto-Absence</span>';
                  } else {
                      $methodLabel = 'Manual Entry';
                      $methodBadge = '<span class="inline-flex items-center gap-1 text-[11px] text-blue-600"><span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Manual Correction</span>';
                  }

                  // Status badge (with excuse detection)
                  $hasApprovedSlip = isset($approvedSlips[$row['date']]);
                ?>
                  <tr class="hover:bg-slate-50/80 transition">
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
                      <?php elseif ($status === 'tardy'): ?>
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
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
