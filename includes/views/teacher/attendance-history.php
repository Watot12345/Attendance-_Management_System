<?php
/**
 * Attendance History & Audit Logs — includes/views/teacher/attendance-history.php
 * Displays live attendance records, filters by section/date/status/search, provides
 * authorized manual attendance corrections, and maintains an immutable audit trail.
 */

require_once dirname(__DIR__, 2) . '/core/Database.php';

$db = Database::getConnection();

// 1. Resolve logged-in teacher ID
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
$teacherId = !empty($_SESSION['teacher_id']) ? (int)$_SESSION['teacher_id'] : (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher' ? (int)$_SESSION['user_id'] : 0);
if ($teacherId <= 0) {
    $rT = $db->query("SELECT user_id FROM users WHERE role = 'teacher' ORDER BY user_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $teacherId = $rT ? (int)$rT['user_id'] : 2;
}

// 1b. Resolve logged-in teacher display name
$tNameStmt = $db->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
$tNameStmt->execute([$teacherId]);
$tUser = $tNameStmt->fetch(PDO::FETCH_ASSOC);
$loggedTeacherName = $tUser ? ('Prof. ' . $tUser['first_name'] . ' ' . $tUser['last_name']) : 'Prof. Ramirez';

// 2. Fetch distinct sections assigned to this teacher from class_roster
$secStmt = $db->prepare("
    SELECT DISTINCT section, course_code, course_title 
    FROM class_roster 
    WHERE teacher_id = ? 
    ORDER BY section ASC
");
$secStmt->execute([$teacherId]);
$sectionsList = $secStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Find latest attendance date for default filter
$latestDateStmt = $db->prepare("SELECT MAX(date) FROM attendance WHERE teacher_id = ?");
$latestDateStmt->execute([$teacherId]);
$dbLatestDate = $latestDateStmt->fetchColumn();
$defaultDate = $dbLatestDate ?: date('Y-m-d');

// 4. Read GET Filter Parameters
$filterSection   = trim($_GET['section'] ?? '');
$filterDate      = isset($_GET['date']) ? trim($_GET['date']) : $defaultDate;
$filterStatus    = strtolower(trim($_GET['status'] ?? 'all'));
$filterSearch    = trim($_GET['search'] ?? '');
$filterStudentId = !empty($_GET['student']) ? (int)$_GET['student'] : (!empty($_GET['student_id']) ? (int)$_GET['student_id'] : 0);

// 5. Build Attendance Records Query
$where = ["a.teacher_id = :teacher_id"];
$params = [':teacher_id' => $teacherId];

if ($filterSection !== '' && $filterSection !== 'all') {
    $where[] = "cr.section = :section";
    $params[':section'] = $filterSection;
}

if ($filterDate !== '' && $filterDate !== 'all') {
    $where[] = "a.date = :date";
    $params[':date'] = $filterDate;
}

if ($filterStatus !== '' && $filterStatus !== 'all') {
    $normalizedStatus = ($filterStatus === 'late') ? 'tardy' : $filterStatus;
    $where[] = "a.status = :status";
    $params[':status'] = $normalizedStatus;
}

if ($filterStudentId > 0) {
    $where[] = "a.student_id = :student_id";
    $params[':student_id'] = $filterStudentId;
}

if ($filterSearch !== '') {
    $where[] = "(u.first_name LIKE :search1 OR u.last_name LIKE :search2 OR u.student_id LIKE :search3 OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search4)";
    $searchTerm = '%' . $filterSearch . '%';
    $params[':search1'] = $searchTerm;
    $params[':search2'] = $searchTerm;
    $params[':search3'] = $searchTerm;
    $params[':search4'] = $searchTerm;
}

$whereSql = implode(' AND ', $where);

$attStmt = $db->prepare("
    SELECT 
        a.attendance_id,
        a.student_id,
        a.date,
        a.time,
        a.status,
        a.qr_session_id,
        a.subject,
        a.updated_at,
        u.student_id AS student_number,
        u.first_name,
        u.last_name,
        u.avatar_path,
        cr.section,
        cr.course_title,
        cr.course_code
    FROM attendance a
    JOIN users u ON a.student_id = u.user_id
    LEFT JOIN class_roster cr ON (cr.student_id = a.student_id AND cr.teacher_id = a.teacher_id)
    WHERE {$whereSql}
    ORDER BY a.date DESC, a.time DESC, u.last_name ASC
");
$attStmt->execute($params);
$attendanceRecords = $attStmt->fetchAll(PDO::FETCH_ASSOC);

// 5b. Compute session counts for summary pills
$sessionPresent = 0;
$sessionTardy   = 0;
$sessionAbsent  = 0;
foreach ($attendanceRecords as $rec) {
    $st = strtolower($rec['status']);
    if ($st === 'present') $sessionPresent++;
    elseif ($st === 'tardy') $sessionTardy++;
    elseif ($st === 'absent') $sessionAbsent++;
}

// 6. Fetch Recent Audit Logs for Attendance
$auditStmt = $db->prepare("
    SELECT 
        al.audit_log_id,
        al.user_id,
        al.action,
        al.description,
        al.reference_type,
        al.reference_id,
        al.created_at,
        u.first_name AS mod_first_name,
        u.last_name AS mod_last_name,
        u.role AS mod_role
    FROM audit_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    WHERE al.reference_type = 'attendance'
    ORDER BY al.created_at DESC
    LIMIT 30
");
$auditStmt->execute();
$auditLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Attendance History & Audit Logs';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <!-- Content Area -->
    <main class="page-body flex-1 p-6 bg-surface">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
          <div>
            <h1 class="text-2xl font-bold text-text-primary flex items-center gap-2">
              <span>Attendance History &amp; Audit Logs</span>
              <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                Live Data
              </span>
            </h1>
            <p class="text-sm text-text-secondary mt-0.5">
              Review verified session records, export reports, and perform authorized manual corrections with audit logging.
            </p>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" class="btn btn-secondary flex items-center gap-2 shadow-sm hover:shadow" onclick="exportAttendanceCsv()">
              <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
              </svg>
              <span>Export CSV / Excel</span>
            </button>
          </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" action="<?php echo url('teacher/attendance-history'); ?>" class="bg-white p-4 rounded-xl shadow-card border border-slate-100 mb-6">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search Student -->
            <div>
              <label class="text-xs font-semibold uppercase text-text-muted mb-1 block">Search Student</label>
              <div class="relative flex items-center">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                  </svg>
                </div>
                <input 
                  type="text" 
                  name="search" 
                  value="<?php echo htmlspecialchars($filterSearch); ?>" 
                  class="form-input text-sm" 
                  style="padding-left: 2.5rem !important;"
                  placeholder="Student number or name..."
                >
              </div>
            </div>

            <!-- Class / Section -->
            <div>
              <label class="text-xs font-semibold uppercase text-text-muted mb-1 block">Class / Section</label>
              <select name="section" class="form-input form-select text-sm" onchange="this.form.submit()">
                <option value="all">All Assigned Sections</option>
                <?php foreach ($sectionsList as $sec): ?>
                  <option value="<?php echo htmlspecialchars($sec['section']); ?>" <?php echo ($filterSection === $sec['section']) ? 'selected' : ''; ?>>
                    Section <?php echo htmlspecialchars($sec['section']); ?> &middot; <?php echo htmlspecialchars($sec['course_code']); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- Date Selector -->
            <div>
              <label class="text-xs font-semibold uppercase text-text-muted mb-1 block">Session Date</label>
              <input 
                type="date" 
                id="filter-date"
                name="date" 
                value="<?php echo htmlspecialchars($filterDate !== 'all' ? $filterDate : ''); ?>" 
                class="form-input text-sm" 
                onchange="this.form.submit()"
              >
            </div>

            <!-- Status Filter -->
            <div>
              <label class="text-xs font-semibold uppercase text-text-muted mb-1 block">Status</label>
              <select name="status" class="form-input form-select text-sm" onchange="this.form.submit()">
                <option value="all" <?php echo ($filterStatus === 'all' || $filterStatus === '') ? 'selected' : ''; ?>>All Statuses</option>
                <option value="present" <?php echo ($filterStatus === 'present') ? 'selected' : ''; ?>>Present</option>
                <option value="tardy" <?php echo ($filterStatus === 'tardy' || $filterStatus === 'late') ? 'selected' : ''; ?>>Tardy / Late</option>
                <option value="absent" <?php echo ($filterStatus === 'absent') ? 'selected' : ''; ?>>Absent</option>
              </select>
            </div>
          </div>

          <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100 text-xs">
            <div class="text-slate-500">
              Showing active filters for <strong><?php echo htmlspecialchars($loggedTeacherName); ?></strong>.
              <?php if ($filterDate && $filterDate !== 'all'): ?>
                Session date: <span class="font-semibold text-slate-700"><?php echo date('M d, Y', strtotime($filterDate)); ?></span>
              <?php else: ?>
                Session date: <span class="font-semibold text-slate-700">All Dates</span>
              <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
              <a href="<?php echo url('teacher/attendance-history'); ?>" class="text-slate-500 hover:text-slate-800 font-medium px-2 py-1 rounded hover:bg-slate-100 transition">
                Reset Filters
              </a>
              <button type="submit" class="btn btn-primary btn-sm text-xs px-3 py-1.5">
                Apply Filters
              </button>
            </div>
          </div>
        </form>

        <!-- Attendance Records Table -->
        <div class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden mb-8">
          <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <div>
              <h3 class="text-base font-bold text-text-primary">
                Session Attendance Log 
                <?php if ($filterDate && $filterDate !== 'all'): ?>
                  — <?php echo date('M d, Y', strtotime($filterDate)); ?>
                <?php endif; ?>
              </h3>
              <p class="text-xs text-text-muted mt-0.5">Verified roster attendance entries captured through live QR sessions and manual logs.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                ● <?php echo $sessionPresent; ?> Present
              </span>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                ● <?php echo $sessionTardy; ?> Tardy
              </span>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                ● <?php echo $sessionAbsent; ?> Absent
              </span>
              <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full">
                <?php echo count($attendanceRecords); ?> total
              </span>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="data-table w-full" id="attendance-records-table">
              <thead>
                <tr>
                  <th>Student Number</th>
                  <th>Student Name</th>
                  <th>Section</th>
                  <th>Status</th>
                  <th>Time In</th>
                  <th>Verification</th>
                  <th>Remarks / Subject</th>
                  <th class="text-center" style="text-align: center !important; width: 140px;">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($attendanceRecords)): ?>
                  <tr>
                    <td colspan="8" class="text-center py-12 text-slate-400">
                      <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                      </svg>
                      <p class="text-sm font-semibold text-slate-600">No attendance records found</p>
                      <p class="text-xs text-slate-400 mt-1">Try selecting another date or clearing your filter criteria.</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($attendanceRecords as $row): 
                    $studentNumber = $row['student_number'] ?: '2026-' . str_pad($row['student_id'], 5, '0', STR_PAD_LEFT);
                    $fullName = trim($row['first_name'] . ' ' . $row['last_name']);
                    $status = strtolower($row['status']);
                    $isAbsent = ($status === 'absent');
                    $isTardy = ($status === 'tardy');
                    $isPresent = ($status === 'present');
                    
                    // Verification source
                    if (!empty($row['qr_session_id'])) {
                        $verifLabel = 'Dynamic QR';
                        $verifBadge = 'bg-blue-50 text-blue-700 border-blue-200';
                        $verifIcon = '<svg class="w-3.5 h-3.5 inline mr-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M4 8h4m0 0V4m0 4h.01M4 16h4m0 0v4m0-4h.01"/></svg>';
                    } elseif ($isAbsent) {
                        $verifLabel = 'Auto-Absence';
                        $verifBadge = 'bg-rose-50 text-rose-700 border-rose-200';
                        $verifIcon = '<svg class="w-3.5 h-3.5 inline mr-1 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
                    } else {
                        $verifLabel = 'Manual Entry';
                        $verifBadge = 'bg-amber-50 text-amber-700 border-amber-200';
                        $verifIcon = '<svg class="w-3.5 h-3.5 inline mr-1 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>';
                    }

                    // Formatted time
                    $timeDisplay = ($isAbsent && empty($row['time'])) ? '—' : date('h:i:s A', strtotime($row['time']));
                  ?>
                    <tr 
                      class="<?php echo $isAbsent ? 'bg-rose-50/30' : 'hover:bg-slate-50/70'; ?> transition"
                      data-student-number="<?php echo htmlspecialchars($studentNumber); ?>"
                      data-student-name="<?php echo htmlspecialchars($fullName); ?>"
                      data-section="<?php echo htmlspecialchars($row['section'] ?: '—'); ?>"
                      data-status="<?php echo htmlspecialchars(ucfirst($status)); ?>"
                      data-time="<?php echo htmlspecialchars($timeDisplay); ?>"
                      data-verification="<?php echo htmlspecialchars($verifLabel); ?>"
                      data-remarks="<?php echo htmlspecialchars($row['subject'] ?: 'Web Systems'); ?>"
                    >
                      <td class="font-mono text-xs font-semibold <?php echo $isAbsent ? 'text-rose-700' : 'text-slate-700'; ?>">
                        <?php echo htmlspecialchars($studentNumber); ?>
                      </td>
                      <td>
                        <div class="flex items-center gap-2.5">
                          <div class="w-7 h-7 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-700 uppercase">
                            <?php echo strtoupper(substr($row['first_name'] ?: 'S', 0, 1) . substr($row['last_name'] ?: 'U', 0, 1)); ?>
                          </div>
                          <span class="font-semibold <?php echo $isAbsent ? 'text-rose-900' : 'text-text-primary'; ?>">
                            <?php echo htmlspecialchars($fullName); ?>
                          </span>
                        </div>
                      </td>
                      <td class="text-xs text-slate-600 font-medium">
                        <?php echo htmlspecialchars($row['section'] ?: '31001'); ?>
                      </td>
                      <td>
                        <?php if ($isPresent): ?>
                          <span class="badge badge-present">● Present</span>
                        <?php elseif ($isTardy): ?>
                          <span class="badge badge-tardy">● Tardy</span>
                        <?php else: ?>
                          <span class="badge badge-absent">● Absent</span>
                        <?php endif; ?>
                      </td>
                      <td class="text-xs text-slate-700 font-mono">
                        <?php echo htmlspecialchars($timeDisplay); ?>
                      </td>
                      <td>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border <?php echo $verifBadge; ?>">
                          <?php echo $verifIcon; ?><?php echo htmlspecialchars($verifLabel); ?>
                        </span>
                      </td>
                      <td class="text-xs text-text-muted">
                        <?php echo htmlspecialchars($row['subject'] ?: 'Web Systems and Technologies'); ?>
                      </td>
                      <td class="text-center" style="text-align: center !important;">
                        <button 
                          type="button" 
                          class="btn btn-ghost btn-sm text-xs font-semibold <?php echo $isAbsent ? 'text-teal-700 hover:text-teal-800 font-bold' : 'text-slate-600 hover:text-slate-900'; ?>"
                          style="margin: 0 auto; display: inline-flex;"
                          onclick="openCorrectionModal(
                            <?php echo (int)$row['student_id']; ?>, 
                            '<?php echo addslashes(htmlspecialchars($fullName)); ?>', 
                            '<?php echo addslashes(htmlspecialchars($studentNumber)); ?>', 
                            '<?php echo addslashes(htmlspecialchars($status)); ?>', 
                            '<?php echo addslashes(htmlspecialchars($row['date'])); ?>', 
                            '<?php echo addslashes(htmlspecialchars($row['time'])); ?>', 
                            '<?php echo addslashes(htmlspecialchars($row['subject'])); ?>'
                          )"
                        >
                          Correct Status
                        </button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ════ AUDIT LOG SECTION ════ -->
        <div class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-border flex items-center justify-between bg-slate-50">
            <div>
              <h3 class="text-base font-bold text-text-primary flex items-center gap-2">
                <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Attendance Correction Audit Trail
              </h3>
              <p class="text-xs text-text-muted mt-0.5">Immutable ledger of authorized manual overrides, dynamic QR check-ins, and anti-proxy events.</p>
            </div>
            <span class="text-xs font-semibold text-slate-500 bg-white px-2.5 py-1 border border-slate-200 rounded-full">
              Showing recent <?php echo count($auditLogs); ?> log entries
            </span>
          </div>

          <div class="overflow-x-auto">
            <table class="data-table w-full">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Action / Event</th>
                  <th>Modified By</th>
                  <th>Log Description &amp; Reason Justification</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($auditLogs)): ?>
                  <tr>
                    <td colspan="4" class="text-center py-8 text-slate-400 text-xs">
                      No audit log entries recorded yet.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($auditLogs as $log): 
                    $createdAt = date('Y-m-d h:i:s A', strtotime($log['created_at']));
                    $isManualOverride = (stripos($log['description'], 'Manual') !== false);
                    $isVoided = (stripos($log['description'], 'Voided') !== false);
                    $isQrScan = (stripos($log['description'], 'Dynamic') !== false);

                    if ($isManualOverride) {
                        $eventBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">Manual Override</span>';
                    } elseif ($isVoided) {
                        $eventBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">Proxy Voided</span>';
                    } elseif ($isQrScan) {
                        $eventBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800 border border-blue-200">QR Check-in</span>';
                    } else {
                        $eventBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-700">Audit Record</span>';
                    }

                    // Modifier label
                    if ($log['mod_role'] === 'teacher') {
                        $modifier = 'Prof. ' . htmlspecialchars($log['mod_first_name'] . ' ' . $log['mod_last_name']);
                        $modifierClass = 'text-teal-700 font-semibold';
                    } elseif ($log['mod_role'] === 'student') {
                        $modifier = 'Student (' . htmlspecialchars($log['mod_first_name'] . ' ' . $log['mod_last_name']) . ')';
                        $modifierClass = 'text-slate-700 font-medium';
                    } else {
                        $modifier = 'System Service';
                        $modifierClass = 'text-slate-500 italic';
                    }
                  ?>
                    <tr class="hover:bg-slate-50/60 transition">
                      <td class="text-xs text-text-muted font-mono whitespace-nowrap">
                        <?php echo htmlspecialchars($createdAt); ?>
                      </td>
                      <td>
                        <?php echo $eventBadge; ?>
                      </td>
                      <td class="text-xs <?php echo $modifierClass; ?> whitespace-nowrap">
                        <?php echo $modifier; ?>
                      </td>
                      <td class="text-xs text-slate-700 leading-relaxed">
                        <?php echo htmlspecialchars($log['description']); ?>
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

  <!-- ════ MANUAL CORRECTION MODAL ════ -->
  <div id="correction-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in border border-slate-100">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
        <div>
          <h3 class="text-lg font-bold text-text-primary">Manual Attendance Override</h3>
          <p class="text-xs text-text-muted">Authorized status modification with mandatory reason justification.</p>
        </div>
        <button type="button" onclick="closeCorrectionModal()" class="text-slate-400 hover:text-slate-700 transition">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
          </svg>
        </button>
      </div>

      <form id="correction-form" onsubmit="submitCorrection(event)">
        <input type="hidden" id="modal-student-id">
        <input type="hidden" id="modal-date">
        <input type="hidden" id="modal-time">
        <input type="hidden" id="modal-subject">

        <div class="bg-slate-50 p-3 rounded-lg border border-slate-200/80 mb-4">
          <div class="flex justify-between items-center text-xs">
            <span class="text-text-muted uppercase font-semibold">Student Record:</span>
            <span class="font-bold text-slate-700 font-mono" id="modal-student-current-status-badge"></span>
          </div>
          <p class="text-sm font-bold text-slate-900 mt-1" id="modal-student-name">Loading...</p>
        </div>

        <div class="mb-4">
          <label class="form-label text-xs font-semibold uppercase text-text-secondary mb-1.5 block">
            New Attendance Status <span class="text-rose-500">*</span>
          </label>
          <select id="modal-new-status" class="form-input form-select text-sm font-medium" required>
            <option value="present">Present (On Time)</option>
            <option value="tardy">Tardy / Late</option>
            <option value="absent">Absent</option>
          </select>
        </div>

        <div class="mb-5">
          <label class="form-label text-xs font-semibold uppercase text-text-secondary mb-1.5 block">
            Reason for Correction (Recorded in Audit Log) <span class="text-rose-500">*</span>
          </label>
          <textarea 
            id="modal-reason" 
            class="form-input text-sm leading-relaxed" 
            rows="3" 
            placeholder="e.g. Student presented clinic slip, phone camera hardware error, verified present by teacher..." 
            required
          ></textarea>
          <span class="text-[11px] text-slate-400 mt-1 block">This justification is permanently stored in the audit trail.</span>
        </div>

        <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
          <button type="button" class="btn btn-secondary text-xs" onclick="closeCorrectionModal()">Cancel</button>
          <button type="submit" id="modal-submit-btn" class="btn btn-primary text-xs font-bold px-4 py-2">
            Save &amp; Log Audit Record
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openCorrectionModal(studentId, studentName, studentNumber, currentStatus, recordDate, recordTime, subject) {
      document.getElementById('modal-student-id').value = studentId;
      document.getElementById('modal-date').value = recordDate || '<?php echo $filterDate; ?>';
      document.getElementById('modal-time').value = recordTime || '<?php echo date('H:i:s'); ?>';
      document.getElementById('modal-subject').value = subject || '';
      document.getElementById('modal-student-name').textContent = `${studentName} (${studentNumber})`;
      
      const badge = document.getElementById('modal-student-current-status-badge');
      badge.textContent = `Current: ${currentStatus.toUpperCase()}`;
      
      const statusSelect = document.getElementById('modal-new-status');
      statusSelect.value = (currentStatus === 'late') ? 'tardy' : currentStatus;
      
      document.getElementById('modal-reason').value = '';
      document.getElementById('correction-modal').classList.remove('hidden');
    }

    function closeCorrectionModal() {
      document.getElementById('correction-modal').classList.add('hidden');
    }

    async function submitCorrection(e) {
      e.preventDefault();
      const studentId = document.getElementById('modal-student-id').value;
      const date = document.getElementById('modal-date').value;
      const time = document.getElementById('modal-time').value;
      const status = document.getElementById('modal-new-status').value;
      const subject = document.getElementById('modal-subject').value;
      const reason = document.getElementById('modal-reason').value.trim();

      if (!reason) {
        if (typeof APP !== 'undefined' && APP.showToast) {
          APP.showToast('Please provide a reason for the audit log.', 'error');
        } else {
          alert('Please provide a reason for the audit log.');
        }
        return;
      }

      const submitBtn = document.getElementById('modal-submit-btn');
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<span class="inline-block animate-spin mr-1">↻</span> Saving...';

      try {
        const apiUrl = window.url ? window.url('api/attendance/manual-entry') : '<?php echo url("api/attendance/manual-entry"); ?>';
        const res = await fetch(apiUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            student_id: parseInt(studentId, 10),
            date: date,
            time: time,
            status: status,
            subject: subject,
            notes: reason,
            override_reason: reason
          })
        });

        const data = await res.json();
        if (res.ok && data.status === 'success') {
          closeCorrectionModal();
          if (typeof APP !== 'undefined' && APP.showToast) {
            APP.showToast(data.message || 'Attendance status updated and audit logged.', 'success');
          }
          setTimeout(() => {
            window.location.reload();
          }, 600);
        } else {
          const errMsg = data.message || 'Failed to update attendance.';
          if (typeof APP !== 'undefined' && APP.showToast) {
            APP.showToast(errMsg, 'error');
          } else {
            alert(errMsg);
          }
        }
      } catch (err) {
        console.error('Error submitting correction:', err);
        if (typeof APP !== 'undefined' && APP.showToast) {
          APP.showToast('Network error while saving correction.', 'error');
        }
      } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Save & Log Audit Record';
      }
    }

    function exportAttendanceCsv() {
      const table = document.getElementById('attendance-records-table');
      if (!table) return;

      const rows = table.querySelectorAll('tbody tr');
      if (!rows.length || rows[0].querySelector('td[colspan]')) {
        if (typeof APP !== 'undefined' && APP.showToast) {
          APP.showToast('No records available to export.', 'warning');
        }
        return;
      }

      let csvContent = 'Student Number,Student Name,Section,Status,Time In,Verification,Remarks\n';

      rows.forEach(tr => {
        const studentNo = tr.getAttribute('data-student-number') || '';
        const studentName = tr.getAttribute('data-student-name') || '';
        const section = tr.getAttribute('data-section') || '';
        const status = tr.getAttribute('data-status') || '';
        const timeIn = tr.getAttribute('data-time') || '';
        const verification = tr.getAttribute('data-verification') || '';
        const remarks = tr.getAttribute('data-remarks') || '';

        const line = [
          `"${studentNo.replace(/"/g, '""')}"`,
          `"${studentName.replace(/"/g, '""')}"`,
          `"${section.replace(/"/g, '""')}"`,
          `"${status.replace(/"/g, '""')}"`,
          `"${timeIn.replace(/"/g, '""')}"`,
          `"${verification.replace(/"/g, '""')}"`,
          `"${remarks.replace(/"/g, '""')}"`
        ].join(',');

        csvContent += line + '\n';
      });

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const dateStr = document.getElementById('filter-date') ? document.getElementById('filter-date').value : 'all';
      link.href = URL.createObjectURL(blob);
      link.setAttribute('download', `attendance_history_${dateStr || 'records'}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      if (typeof APP !== 'undefined' && APP.showToast) {
        APP.showToast('Attendance report exported successfully.', 'success');
      }
    }

    if (typeof APP !== 'undefined' && APP.highlightNav) {
      APP.highlightNav('attendance');
    }
  </script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
