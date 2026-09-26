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
$filterDate      = isset($_GET['date']) ? trim($_GET['date']) : 'all';
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
  <?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php include dirname(__DIR__) . '/partials/navbar.php'; ?>

    <!-- Content Area -->
    <main class="page-body">
      <!-- Page Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-md text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/80">Faculty Records</span>
            <span class="px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
              ● Live Data
            </span>
          </div>
          <h1 class="text-2xl font-bold tracking-tight text-slate-900">Attendance History &amp; Audit Logs</h1>
          <p class="text-xs text-slate-500 mt-0.5">
            Review verified session records, export class reports, and perform authorized manual corrections with audit logging.
          </p>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" class="px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-1.5" onclick="exportAttendanceCsv()">
            <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span>Export CSV</span>
          </button>
        </div>
      </div>

      <!-- Filter Form with Debounced Live Controls -->
      <form id="attendance-filter-form" onsubmit="event.preventDefault(); applyAttendanceFiltersAndPagination();" class="bg-white p-4 rounded-xl shadow-xs border border-slate-200/80 mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
          <!-- Search Student -->
          <div>
            <label class="text-[11px] font-bold uppercase text-slate-600 mb-1.5 block">Search Student</label>
            <div class="relative flex items-center">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
              </div>
              <input 
                type="text" 
                id="search-attendance"
                name="search" 
                value="<?php echo htmlspecialchars($filterSearch); ?>" 
                class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-400" 
                placeholder="Search student number, name..."
                oninput="debouncedFilterAttendance()"
              >
            </div>
          </div>

          <!-- Class / Section -->
          <div>
            <label class="text-[11px] font-bold uppercase text-slate-600 mb-1.5 block">Class / Section</label>
            <select id="filter-section" name="section" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 cursor-pointer" onchange="debouncedFilterAttendance()">
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
            <label class="text-[11px] font-bold uppercase text-slate-600 mb-1.5 block">Session Date</label>
            <input 
              type="date" 
              id="filter-date"
              name="date" 
              value="<?php echo htmlspecialchars(($filterDate !== 'all' && $filterDate !== '') ? $filterDate : ''); ?>" 
              class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 cursor-pointer" 
              onchange="debouncedFilterAttendance()"
            >
          </div>

          <!-- Status Filter -->
          <div>
            <label class="text-[11px] font-bold uppercase text-slate-600 mb-1.5 block">Status</label>
            <select id="filter-status" name="status" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 cursor-pointer" onchange="debouncedFilterAttendance()">
              <option value="all" <?php echo ($filterStatus === 'all' || $filterStatus === '') ? 'selected' : ''; ?>>All Statuses</option>
              <option value="present" <?php echo ($filterStatus === 'present') ? 'selected' : ''; ?>>Present</option>
              <option value="tardy" <?php echo ($filterStatus === 'tardy' || $filterStatus === 'late') ? 'selected' : ''; ?>>Tardy / Late</option>
              <option value="absent" <?php echo ($filterStatus === 'absent') ? 'selected' : ''; ?>>Absent</option>
            </select>
          </div>
        </div>

        <div class="flex items-center justify-between mt-3 pt-3 border-t border-slate-100 text-xs">
          <div class="text-slate-500 text-[11px]">
            Showing records for <strong><?php echo htmlspecialchars($loggedTeacherName); ?></strong>
          </div>
          <div class="flex items-center gap-2">
            <button type="button" onclick="resetAttendanceFilters()" class="text-slate-500 hover:text-slate-800 font-semibold px-2.5 py-1 rounded hover:bg-slate-100 transition text-xs">
              Reset Filters
            </button>
            <button type="button" onclick="applyAttendanceFiltersAndPagination()" class="px-3.5 py-1.5 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-semibold shadow-xs transition">
              Apply Filters
            </button>
          </div>
        </div>
      </form>

      <!-- Attendance Records Table with Debounced Loading & Pagination -->
      <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 overflow-hidden mb-8">
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
                ● <span id="summary-present"><?php echo $sessionPresent; ?></span>&nbsp;Present
              </span>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                ● <span id="summary-tardy"><?php echo $sessionTardy; ?></span>&nbsp;Tardy
              </span>
              <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                ● <span id="summary-absent"><?php echo $sessionAbsent; ?></span>&nbsp;Absent
              </span>
              <span class="text-xs font-semibold px-2.5 py-1 bg-slate-100 text-slate-700 rounded-full">
                <span id="summary-total"><?php echo count($attendanceRecords); ?></span> total
              </span>
            </div>
          </div>

          <div class="relative overflow-x-auto min-h-[160px]">
            <!-- Table Loading Overlay (Debounced Live Search & Filter) -->
            <div id="table-loading-overlay" class="hidden absolute inset-0 bg-white/80 backdrop-blur-[2px] z-20 flex items-center justify-center transition-all duration-200">
              <div class="inline-flex items-center gap-2.5 px-4 py-2.5 rounded-xl bg-white border border-slate-200/90 shadow-lg text-xs font-bold text-slate-700">
                <svg class="w-4 h-4 text-blue-600 animate-spin" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                </svg>
                <span>Filtering attendance records...</span>
              </div>
            </div>

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
              <tbody id="attendance-table-body">
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
                    $timeDisplay = ($isAbsent && (empty($row['time']) || $row['time'] === '00:00:00')) ? '—' : date('h:i:s A', strtotime($row['time']));
                  ?>
                    <tr 
                      class="attendance-row <?php echo $isAbsent ? 'bg-rose-50/30' : 'hover:bg-slate-50/70'; ?> transition"
                      data-student-number="<?php echo htmlspecialchars($studentNumber); ?>"
                      data-student-name="<?php echo htmlspecialchars($fullName); ?>"
                      data-section="<?php echo htmlspecialchars($row['section'] ?: '—'); ?>"
                      data-status="<?php echo htmlspecialchars($status); ?>"
                      data-date="<?php echo htmlspecialchars($row['date']); ?>"
                      data-time="<?php echo htmlspecialchars($timeDisplay); ?>"
                      data-verification="<?php echo htmlspecialchars($verifLabel); ?>"
                      data-remarks="<?php echo htmlspecialchars($row['subject'] ?: 'Web Systems'); ?>"
                      data-text="<?php echo htmlspecialchars(strtolower($studentNumber . ' ' . $fullName . ' ' . ($row['section'] ?: '') . ' ' . $status . ' ' . ($row['subject'] ?: '') . ' ' . $timeDisplay . ' ' . $verifLabel . ' ' . $row['date'])); ?>"
                    >
                      <td class="font-mono text-xs font-semibold <?php echo $isAbsent ? 'text-rose-700' : 'text-slate-700'; ?>">
                        <?php echo htmlspecialchars($studentNumber); ?>
                      </td>
                      <td>
                        <div class="flex items-center gap-2.5">
                          <div class="w-7 h-7 rounded-lg bg-[#1e3b8a] text-white font-bold text-[10px] flex items-center justify-center shrink-0">
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
                <!-- Empty Filter Results Row -->
                <tr id="no-attendance-filter-results" class="hidden">
                  <td colspan="8" class="text-center py-12 text-slate-400">
                    <div class="flex flex-col items-center justify-center gap-2">
                      <svg class="w-10 h-10 mx-auto mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                      </svg>
                      <p class="text-sm font-semibold text-slate-600">No attendance records match your filter criteria</p>
                      <p class="text-xs text-slate-400 mt-1">Try adjusting the Student Name/Number, Section, Date, or Status.</p>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Attendance Table Pagination Footer Bar -->
          <div id="attendance-pagination-bar" class="px-5 py-3.5 bg-slate-50/90 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
            <!-- Left: Showing X to Y of Z attendance record(s) -->
            <div class="text-slate-500 font-medium" id="attendance-pagination-info">
              Showing <span id="pagination-start" class="font-bold text-slate-800">1</span> to <span id="pagination-end" class="font-bold text-slate-800"><?= min(15, count($attendanceRecords)) ?></span> of <span id="pagination-total" class="font-bold text-slate-800"><?= count($attendanceRecords) ?></span> attendance record(s)
            </div>

            <!-- Right: Pagination Buttons & Navigation Controls -->
            <div class="flex items-center gap-1.5 flex-wrap" id="attendance-pagination-controls">
              <!-- Dynamically populated by renderAttendancePagination -->
            </div>
          </div>
        </div>

        <!-- ════ AUDIT LOG SECTION ════ -->
        <div class="bg-white rounded-xl shadow-xs border border-slate-200/80 overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/60">
            <div>
              <h3 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Attendance Correction Audit Trail
              </h3>
              <p class="text-xs text-slate-500 mt-0.5">Immutable ledger of authorized manual overrides, dynamic QR check-ins, and anti-proxy events.</p>
            </div>
            <span class="text-[11px] font-semibold text-slate-600 bg-white px-2.5 py-1 border border-slate-200 rounded-lg">
              Recent <?php echo count($auditLogs); ?> entries
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
                        $eventBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">Manual Override</span>';
                    } elseif ($isVoided) {
                        $eventBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200">Proxy Voided</span>';
                    } elseif ($isQrScan) {
                        $eventBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">QR Check-in</span>';
                    } else {
                        $eventBadge = '<span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">Audit Record</span>';
                    }

                    // Modifier label
                    if ($log['mod_role'] === 'teacher') {
                        $modifier = 'Prof. ' . htmlspecialchars($log['mod_first_name'] . ' ' . $log['mod_last_name']);
                        $modifierClass = 'text-slate-800 font-semibold';
                    } elseif ($log['mod_role'] === 'student') {
                        $modifier = 'Student (' . htmlspecialchars($log['mod_first_name'] . ' ' . $log['mod_last_name']) . ')';
                        $modifierClass = 'text-slate-700 font-medium';
                    } else {
                        $modifier = 'System Service';
                        $modifierClass = 'text-slate-500 italic';
                    }
                  ?>
                    <tr class="hover:bg-slate-50/60 transition">
                      <td class="text-xs text-slate-500 font-mono whitespace-nowrap">
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
  <div id="correction-modal" class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-md w-full p-6 animate-scale-in border border-slate-200/80">
      <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
        <div>
          <h3 class="text-base font-bold text-slate-900">Manual Attendance Override</h3>
          <p class="text-xs text-slate-500 mt-0.5">Authorized status modification with mandatory reason justification.</p>
        </div>
        <button type="button" onclick="closeCorrectionModal()" class="w-8 h-8 rounded-lg flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
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
            <span class="text-slate-500 uppercase font-semibold text-[10px]">Student Record:</span>
            <span class="font-bold text-slate-700 font-mono text-[11px]" id="modal-student-current-status-badge"></span>
          </div>
          <p class="text-sm font-bold text-slate-900 mt-1" id="modal-student-name">Loading...</p>
        </div>

        <div class="mb-4">
          <label class="text-[11px] font-bold uppercase text-slate-600 mb-1.5 block">
            New Attendance Status <span class="text-rose-500">*</span>
          </label>
          <select id="modal-new-status" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 cursor-pointer" required>
            <option value="present">Present (On Time)</option>
            <option value="tardy">Tardy / Late</option>
            <option value="absent">Absent</option>
          </select>
        </div>

        <div class="mb-5">
          <label class="text-[11px] font-bold uppercase text-slate-600 mb-1.5 block">
            Reason for Correction (Recorded in Audit Log) <span class="text-rose-500">*</span>
          </label>
          <textarea 
            id="modal-reason" 
            class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50/80 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-400 leading-relaxed" 
            rows="3" 
            placeholder="e.g. Student presented clinic slip, phone camera hardware error, verified present by teacher..." 
            required
          ></textarea>
          <span class="text-[11px] text-slate-400 mt-1 block">This justification is permanently stored in the audit trail.</span>
        </div>

        <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button type="button" class="px-3.5 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition" onclick="closeCorrectionModal()">Cancel</button>
          <button type="submit" id="modal-submit-btn" class="px-4 py-2 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-semibold shadow-xs transition">
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
      const searchVal = (document.getElementById('search-attendance')?.value || '').toLowerCase().trim();
      const sectionVal = (document.getElementById('filter-section')?.value || 'all').trim();
      const dateVal = (document.getElementById('filter-date')?.value || '').trim();
      const statusVal = (document.getElementById('filter-status')?.value || 'all').trim().toLowerCase();

      const allRows = Array.from(document.querySelectorAll('.attendance-row'));
      const matchingRows = allRows.filter(row => {
        const rowSec = (row.getAttribute('data-section') || '').trim();
        const rowDate = (row.getAttribute('data-date') || '').trim();
        const rowStatus = (row.getAttribute('data-status') || '').toLowerCase().trim();
        const rowText = (row.getAttribute('data-text') || '').toLowerCase();

        const matchSearch = (!searchVal || rowText.includes(searchVal));
        const matchSection = (sectionVal === 'all' || sectionVal === '' || rowSec === sectionVal);
        const matchDate = (!dateVal || dateVal === 'all' || rowDate === dateVal);
        let matchStatus = true;
        if (statusVal === 'present') matchStatus = (rowStatus === 'present');
        else if (statusVal === 'tardy' || statusVal === 'late') matchStatus = (rowStatus === 'tardy' || rowStatus === 'late');
        else if (statusVal === 'absent') matchStatus = (rowStatus === 'absent');

        return matchSearch && matchSection && matchDate && matchStatus;
      });

      if (!matchingRows.length) {
        if (typeof APP !== 'undefined' && APP.showToast) {
          APP.showToast('No records available to export for the current filter criteria.', 'warning');
        } else {
          alert('No records available to export.');
        }
        return;
      }

      let csvContent = 'Student Number,Student Name,Section,Status,Date,Time In,Verification,Remarks\n';

      matchingRows.forEach(tr => {
        const studentNo = tr.getAttribute('data-student-number') || '';
        const studentName = tr.getAttribute('data-student-name') || '';
        const section = tr.getAttribute('data-section') || '';
        const status = tr.getAttribute('data-status') || '';
        const date = tr.getAttribute('data-date') || '';
        const timeIn = tr.getAttribute('data-time') || '';
        const verification = tr.getAttribute('data-verification') || '';
        const remarks = tr.getAttribute('data-remarks') || '';

        const line = [
          `"${studentNo.replace(/"/g, '""')}"`,
          `"${studentName.replace(/"/g, '""')}"`,
          `"${section.replace(/"/g, '""')}"`,
          `"${status.replace(/"/g, '""')}"`,
          `"${date.replace(/"/g, '""')}"`,
          `"${timeIn.replace(/"/g, '""')}"`,
          `"${verification.replace(/"/g, '""')}"`,
          `"${remarks.replace(/"/g, '""')}"`
        ].join(',');

        csvContent += line + '\n';
      });

      const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
      const link = document.createElement('a');
      const dateStr = document.getElementById('filter-date') ? (document.getElementById('filter-date').value || 'all') : 'all';
      link.href = URL.createObjectURL(blob);
      link.setAttribute('download', `attendance_history_${dateStr || 'records'}.csv`);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      if (typeof APP !== 'undefined' && APP.showToast) {
        APP.showToast(`Exported ${matchingRows.length} attendance records to CSV.`, 'success');
      }
    }

    /* ══════════════════════════════════════════════════════════════
       ATTENDANCE PAGINATION & DEBOUNCED LIVE FILTERING (15 PER PAGE)
       ══════════════════════════════════════════════════════════════ */
    const PAGE_SIZE = 15;
    const WINDOW_SIZE = 30;
    let attendanceCurrentPage = 1;
    let attendanceFilterDebounceTimer = null;

    // Debounced filter handler (0.3 seconds / 300ms delay) with prominent table loading overlay
    function debouncedFilterAttendance() {
      clearTimeout(attendanceFilterDebounceTimer);

      // Show prominent table loading overlay immediately upon typing or filter change
      const tableOverlay = document.getElementById('table-loading-overlay');
      if (tableOverlay) tableOverlay.classList.remove('hidden');

      attendanceFilterDebounceTimer = setTimeout(() => {
        attendanceCurrentPage = 1; // Reset to page 1 whenever search query or filter changes
        applyAttendanceFiltersAndPagination();
      }, 300);
    }

    // Backward-compatible alias
    function filterAttendance() {
      debouncedFilterAttendance();
    }

    // Core filtering and pagination engine
    function applyAttendanceFiltersAndPagination() {
      const searchVal = (document.getElementById('search-attendance')?.value || '').toLowerCase().trim();
      const sectionVal = (document.getElementById('filter-section')?.value || 'all').trim();
      const dateVal = (document.getElementById('filter-date')?.value || '').trim();
      const statusVal = (document.getElementById('filter-status')?.value || 'all').trim().toLowerCase();

      const rows = Array.from(document.querySelectorAll('.attendance-row'));
      const matchingRows = [];

      let countPres = 0;
      let countTardy = 0;
      let countAbs = 0;

      rows.forEach(row => {
        const rowSec = (row.getAttribute('data-section') || '').trim();
        const rowDate = (row.getAttribute('data-date') || '').trim();
        const rowStatus = (row.getAttribute('data-status') || '').toLowerCase().trim();
        const rowText = (row.getAttribute('data-text') || '').toLowerCase();

        // 1. Search Query Match
        const matchSearch = (!searchVal || rowText.includes(searchVal));

        // 2. Section Filter Match
        const matchSection = (sectionVal === 'all' || sectionVal === '' || rowSec === sectionVal);

        // 3. Date Filter Match
        const matchDate = (!dateVal || dateVal === 'all' || rowDate === dateVal);

        // 4. Status Filter Match
        let matchStatus = true;
        if (statusVal === 'present') {
          matchStatus = (rowStatus === 'present');
        } else if (statusVal === 'tardy' || statusVal === 'late') {
          matchStatus = (rowStatus === 'tardy' || rowStatus === 'late');
        } else if (statusVal === 'absent') {
          matchStatus = (rowStatus === 'absent');
        }

        if (matchSearch && matchSection && matchDate && matchStatus) {
          matchingRows.push(row);
          if (rowStatus === 'present') countPres++;
          else if (rowStatus === 'tardy' || rowStatus === 'late') countTardy++;
          else if (rowStatus === 'absent') countAbs++;
        }
      });

      const totalMatching = matchingRows.length;
      const totalPages = Math.ceil(totalMatching / PAGE_SIZE) || 1;

      // Clamp current page within valid bounds
      if (attendanceCurrentPage > totalPages) {
        attendanceCurrentPage = totalPages;
      }
      if (attendanceCurrentPage < 1) {
        attendanceCurrentPage = 1;
      }

      const startIdx = (attendanceCurrentPage - 1) * PAGE_SIZE;
      const endIdx = Math.min(startIdx + PAGE_SIZE, totalMatching);

      // Hide all rows, then display only the matching rows belonging to the active page
      rows.forEach(row => {
        row.style.display = 'none';
      });

      matchingRows.slice(startIdx, endIdx).forEach(row => {
        row.style.display = '';
      });

      // Update summary counters
      const presElem = document.getElementById('summary-present');
      const tardyElem = document.getElementById('summary-tardy');
      const absElem = document.getElementById('summary-absent');
      const totalElem = document.getElementById('summary-total');
      if (presElem) presElem.textContent = countPres;
      if (tardyElem) tardyElem.textContent = countTardy;
      if (absElem) absElem.textContent = countAbs;
      if (totalElem) totalElem.textContent = totalMatching;

      // Toggle empty results row
      const noResultsRow = document.getElementById('no-attendance-filter-results');
      if (noResultsRow) {
        if (totalMatching === 0 && rows.length > 0) {
          noResultsRow.classList.remove('hidden');
        } else {
          noResultsRow.classList.add('hidden');
        }
      }

      // Render bottom pagination controls
      renderAttendancePagination(totalMatching, startIdx, endIdx, totalPages);

      // Deactivate table loading overlay once filtering and rendering is complete
      const tableOverlay = document.getElementById('table-loading-overlay');
      if (tableOverlay) tableOverlay.classList.add('hidden');
    }

    // Change active page and re-slice table
    function changeAttendancePage(newPage) {
      attendanceCurrentPage = newPage;
      applyAttendanceFiltersAndPagination();

      // Smooth scroll back to table top on page switch
      const tableContainer = document.getElementById('attendance-records-table');
      if (tableContainer) {
        tableContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }
    }

    // Render pagination buttons with 30-page chunk navigation
    function renderAttendancePagination(totalMatching, startIdx, endIdx, totalPages) {
      const bar = document.getElementById('attendance-pagination-bar');
      const startEl = document.getElementById('pagination-start');
      const endEl = document.getElementById('pagination-end');
      const totalEl = document.getElementById('pagination-total');
      const controls = document.getElementById('attendance-pagination-controls');

      if (!bar || !controls) return;

      // The pagination bar remains permanently visible so users always see page context
      bar.classList.remove('hidden');

      if (totalMatching === 0) {
        if (startEl) startEl.textContent = '0';
        if (endEl) endEl.textContent = '0';
        if (totalEl) totalEl.textContent = '0';

        controls.innerHTML = `
          <button type="button" disabled title="Previous Page" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-300 bg-slate-50 text-xs font-bold flex items-center gap-1 cursor-not-allowed">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <span>Prev</span>
          </button>
          <button type="button" disabled class="w-8 h-8 rounded-lg border border-slate-200 bg-slate-100 text-xs font-bold text-slate-400 cursor-not-allowed">
            1
          </button>
          <button type="button" disabled title="Next Page" class="px-2.5 py-1.5 rounded-lg border border-slate-200 text-slate-300 bg-slate-50 text-xs font-bold flex items-center gap-1 cursor-not-allowed">
            <span>Next</span>
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
          </button>
        `;
        return;
      }

      if (startEl) startEl.textContent = (startIdx + 1).toLocaleString();
      if (endEl) endEl.textContent = endIdx.toLocaleString();
      if (totalEl) totalEl.textContent = totalMatching.toLocaleString();

      let html = '';

      // Previous Page Button
      const prevDisabled = attendanceCurrentPage <= 1;
      html += `
        <button type="button" 
                onclick="changeAttendancePage(${attendanceCurrentPage - 1})" 
                ${prevDisabled ? 'disabled' : ''} 
                title="Previous Page"
                class="px-2.5 py-1.5 rounded-lg border text-xs font-bold transition flex items-center gap-1 ${
                  prevDisabled 
                    ? 'border-slate-200 text-slate-300 bg-slate-50 cursor-not-allowed' 
                    : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer shadow-2xs'
                }">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
          <span>Prev</span>
        </button>
      `;

      // 30-Page Windowing calculation
      // Displays page numbers in chunks of 30 (1–30, 31–60, 61–90...)
      const currentChunk = Math.floor((attendanceCurrentPage - 1) / WINDOW_SIZE);
      const windowStart = currentChunk * WINDOW_SIZE + 1;
      const windowEnd = Math.min(totalPages, windowStart + WINDOW_SIZE - 1);

      // If beyond chunk 1 (e.g. on page 31+), provide First Page and Jump-Back-30 button
      if (windowStart > 1) {
        html += `
          <button type="button" 
                  onclick="changeAttendancePage(1)" 
                  title="Go to Page 1"
                  class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer shadow-2xs">
            1
          </button>
          <button type="button" 
                  onclick="changeAttendancePage(${windowStart - 1})" 
                  title="Previous 30 Pages"
                  class="px-2 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-500 transition cursor-pointer shadow-2xs">
            «
          </button>
        `;
      }

      // Always render numbered page buttons for the active window
      // e.g. when filtered to 1 page: renders [1]
      // e.g. when filtered to 2 pages: renders [1] [2] (automatically reduced)
      // e.g. when 3 pages: renders [1] [2] [3]
      for (let p = windowStart; p <= windowEnd; p++) {
        const isActive = p === attendanceCurrentPage;
        if (isActive) {
          html += `
            <button type="button" 
                    class="w-8 h-8 rounded-lg border border-blue-600 bg-blue-600 text-xs font-black text-white shadow-xs">
              ${p}
            </button>
          `;
        } else {
          html += `
            <button type="button" 
                    onclick="changeAttendancePage(${p})" 
                    class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer shadow-2xs">
              ${p}
            </button>
          `;
        }
      }

      // If more pages exist past the current 30-page window, provide Jump-Forward-30 and Last Page button
      if (windowEnd < totalPages) {
        html += `
          <button type="button" 
                  onclick="changeAttendancePage(${windowEnd + 1})" 
                  title="Next 30 Pages"
                  class="px-2 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-500 transition cursor-pointer shadow-2xs">
            »
          </button>
          <button type="button" 
                  onclick="changeAttendancePage(${totalPages})" 
                  title="Go to Page ${totalPages}"
                  class="px-2.5 py-1.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer shadow-2xs">
            ${totalPages}
          </button>
        `;
      }

      // Next Page Button
      // When on page 30 and user clicks Next, changeAttendancePage(31) automatically transitions to the next 30-page chunk
      const nextDisabled = attendanceCurrentPage >= totalPages;
      html += `
        <button type="button" 
                onclick="changeAttendancePage(${attendanceCurrentPage + 1})" 
                ${nextDisabled ? 'disabled' : ''} 
                title="Next Page"
                class="px-2.5 py-1.5 rounded-lg border text-xs font-bold transition flex items-center gap-1 ${
                  nextDisabled 
                    ? 'border-slate-200 text-slate-300 bg-slate-50 cursor-not-allowed' 
                    : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer shadow-2xs'
                }">
          <span>Next</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </button>
      `;

      controls.innerHTML = html;
    }

    // Reset all filters to default state
    function resetAttendanceFilters() {
      const searchInput = document.getElementById('search-attendance');
      const secSelect = document.getElementById('filter-section');
      const dateInput = document.getElementById('filter-date');
      const statusSelect = document.getElementById('filter-status');

      if (searchInput) searchInput.value = '';
      if (secSelect) secSelect.value = 'all';
      if (dateInput) dateInput.value = '';
      if (statusSelect) statusSelect.value = 'all';

      debouncedFilterAttendance();
    }

    // Initialize pagination and filtering on page load
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', applyAttendanceFiltersAndPagination);
    } else {
      applyAttendanceFiltersAndPagination();
    }

    if (typeof APP !== 'undefined' && APP.highlightNav) {
      APP.highlightNav('attendance');
    }
  </script>

  <?php include dirname(__DIR__) . '/partials/footer.php'; ?>
