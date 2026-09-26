<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Tardy & Absence Logs';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$teacherId = $_SESSION['user_id'] ?? 2;
$db = Database::getConnection();

// 1. Fetch assigned sections for this teacher
$sectionsStmt = $db->prepare("
    SELECT DISTINCT section, course_code, course_title 
    FROM class_roster 
    WHERE teacher_id = ? 
    ORDER BY section ASC
");
$sectionsStmt->execute([$teacherId]);
$teacherSections = $sectionsStmt->fetchAll(PDO::FETCH_ASSOC);

// 2. Fetch attendance exception records (tardy and absent)
$attStmt = $db->prepare("
    SELECT 
        'attendance' AS source,
        a.attendance_id,
        a.student_id,
        a.teacher_id,
        a.date,
        a.time,
        a.subject,
        a.status,
        a.created_at,
        u.student_id AS student_no,
        u.first_name,
        u.last_name,
        u.email,
        u.parent_name,
        u.parent_email,
        u.parent_number,
        u.avatar_path,
        COALESCE(cr.section, '31001') AS section,
        COALESCE(cr.course_code, 'IT301') AS course_code,
        COALESCE(cr.course_title, a.subject, 'Web Systems and Technologies') AS course_title,
        cr.scheduled_time,
        es.excuse_slip_id,
        es.status AS excuse_status,
        es.reason AS excuse_reason,
        pa.parent_alert_id,
        pa.alert_time,
        pa.parent_email AS alert_parent_email
    FROM attendance a
    JOIN users u ON u.user_id = a.student_id
    LEFT JOIN class_roster cr ON (cr.student_id = a.student_id AND cr.teacher_id = a.teacher_id)
    LEFT JOIN excuse_slips es ON (es.student_id = a.student_id AND es.teacher_id = a.teacher_id AND es.date_of_absence = a.date)
    LEFT JOIN parent_alerts pa ON (pa.attendance_id = a.attendance_id OR (pa.student_id = a.student_id AND pa.alert_date = a.date))
    WHERE a.teacher_id = ? AND a.status IN ('tardy', 'absent')
    ORDER BY a.date DESC, a.time DESC
");
$attStmt->execute([$teacherId]);
$attRows = $attStmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Fetch excuse slips for this teacher (including those submitted for sessions without explicit QR attendance)
$slipStmt = $db->prepare("
    SELECT 
        'excuse_slip' AS source,
        NULL AS attendance_id,
        es.student_id,
        es.teacher_id,
        es.date_of_absence AS date,
        COALESCE(cr.scheduled_time, '08:00:00') AS time,
        es.subject,
        CASE WHEN es.status = 'approved' THEN 'excused' ELSE 'absent' END AS status,
        es.created_at,
        u.student_id AS student_no,
        u.first_name,
        u.last_name,
        u.email,
        u.parent_name,
        u.parent_email,
        u.parent_number,
        u.avatar_path,
        COALESCE(cr.section, '31001') AS section,
        COALESCE(cr.course_code, 'IT301') AS course_code,
        COALESCE(cr.course_title, es.subject, 'Web Systems and Technologies') AS course_title,
        cr.scheduled_time,
        es.excuse_slip_id,
        es.status AS excuse_status,
        es.reason AS excuse_reason,
        NULL AS parent_alert_id,
        NULL AS alert_time,
        NULL AS alert_parent_email
    FROM excuse_slips es
    JOIN users u ON u.user_id = es.student_id
    LEFT JOIN class_roster cr ON (cr.student_id = es.student_id AND cr.teacher_id = es.teacher_id)
    WHERE es.teacher_id = ?
");
$slipStmt->execute([$teacherId]);
$slipRows = $slipStmt->fetchAll(PDO::FETCH_ASSOC);

// Combine and deduplicate
$allExceptions = $attRows;
$seenKeys = [];
foreach ($attRows as $r) {
    $seenKeys[$r['student_id'] . '_' . $r['date']] = true;
}
foreach ($slipRows as $sr) {
    $k = $sr['student_id'] . '_' . $sr['date'];
    if (!isset($seenKeys[$k])) {
        $allExceptions[] = $sr;
        $seenKeys[$k] = true;
    }
}
usort($allExceptions, function($a, $b) {
    return strcmp($b['date'] . ' ' . $b['time'], $a['date'] . ' ' . $a['time']);
});

// 4. Calculate KPI metrics
$currentMonth = date('Y-m');
$tardyThisMonth = 0;
$unexcusedAbsences = 0;
foreach ($allExceptions as $row) {
    if ($row['status'] === 'tardy' && substr($row['date'], 0, 7) === $currentMonth) {
        $tardyThisMonth++;
    }
    if ($row['status'] === 'absent' && ($row['excuse_status'] !== 'approved')) {
        $unexcusedAbsences++;
    }
}

$approvedSlipsStmt = $db->prepare("SELECT COUNT(*) FROM excuse_slips WHERE teacher_id = ? AND status = 'approved'");
$approvedSlipsStmt->execute([$teacherId]);
$approvedSlipsCount = (int)$approvedSlipsStmt->fetchColumn();

$parentAlertsStmt = $db->prepare("
    SELECT COUNT(*) FROM parent_alerts pa
    JOIN attendance a ON a.attendance_id = pa.attendance_id
    WHERE a.teacher_id = ?
");
$parentAlertsStmt->execute([$teacherId]);
$parentAlertsSent = (int)$parentAlertsStmt->fetchColumn();
$totalExceptionsCount = count($allExceptions);

require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Breadcrumb & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">Teacher Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Attendance Exceptions Log</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Tardy &amp; Absence Logs</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Detailed log of student tardiness, consecutive absences, automated alert dispatch states, and excuse slip linkages.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="exportExceptionLogsCSV()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            <span>Export Log (CSV)</span>
          </button>
        </div>
      </div>

      <!-- Quick Metrics -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0 font-black text-sm">
            ⏱
          </div>
          <div>
            <div class="text-xl font-black text-amber-600"><?= number_format($tardyThisMonth) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Tardy Logs (This Month)</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0 font-black text-sm">
            ✕
          </div>
          <div>
            <div class="text-xl font-black text-rose-600"><?= number_format($unexcusedAbsences) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Unexcused Absences</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0 font-black text-sm">
            ✉
          </div>
          <div>
            <div class="text-xl font-black text-blue-600"><?= number_format($approvedSlipsCount) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Excused Slips Approved</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 font-black text-sm">
            📲
          </div>
          <div>
            <div class="text-xl font-black text-emerald-600"><?= $parentAlertsSent ?> / <?= max(1, $totalExceptionsCount) ?></div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Parent Alerts Sent</div>
          </div>
        </div>
      </div>

      <!-- Filter Controls -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs mb-6 flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <!-- Class section filter -->
          <select id="filter-class" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 font-semibold text-slate-700 transition" onchange="filterExceptionLogs()">
            <option value="all">All Assigned Classes</option>
            <?php foreach ($teacherSections as $tSec): ?>
              <option value="<?= htmlspecialchars($tSec['section']) ?>">
                <?= htmlspecialchars(($tSec['course_code'] ?? 'Class') . ' - Section ' . $tSec['section']) ?>
              </option>
            <?php endforeach; ?>
          </select>

          <!-- Type filter -->
          <select id="filter-type" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 font-semibold text-slate-700 transition" onchange="filterExceptionLogs()">
            <option value="all">All Exception Types</option>
            <option value="Tardy">Tardy / Late Only</option>
            <option value="Absent">Unexcused Absences</option>
            <option value="Excused">Excused Absence</option>
          </select>

          <!-- Search box -->
          <div class="relative w-full sm:w-64">
            <input type="text" id="search-log" placeholder="Search student name or ID..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition" oninput="filterExceptionLogs()">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>
        </div>

        <div class="text-xs text-slate-400 font-medium">
          Showing <span id="visible-log-count" class="font-bold text-slate-800"><?= count($allExceptions) ?></span> Exception Entries
        </div>
      </div>

      <!-- Exception Logs Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs" id="exceptionLogsTable">
            <thead class="bg-slate-50/90 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4">Student &amp; ID</th>
                <th class="py-3.5 px-4">Class Section</th>
                <th class="py-3.5 px-4">Date &amp; Time Logged</th>
                <th class="py-3.5 px-4">Type &amp; Severity</th>
                <th class="py-3.5 px-4">Parent Alert Status</th>
                <th class="py-3.5 px-4">Excuse Linkage</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="exception-logs-tbody" class="divide-y divide-slate-100 text-slate-700">
              <?php if (empty($allExceptions)): ?>
                <tr>
                  <td colspan="7" class="py-12 text-center text-slate-400">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-500 border border-emerald-100 flex items-center justify-center mx-auto mb-2 font-black text-xl">
                      ✓
                    </div>
                    <p class="font-bold text-sm text-slate-700">No Attendance Exceptions Found</p>
                    <p class="text-xs text-slate-400 mt-0.5">All students have 100% on-time attendance for your classes.</p>
                  </td>
                          <?php else: ?>
                <?php 
                foreach ($allExceptions as $item): 
                  $initials = strtoupper(substr(trim($item['first_name'] ?? 'S'), 0, 1) . substr(trim($item['last_name'] ?? ''), 0, 1));
                  
                  $isTardy = ($item['status'] === 'tardy');
                  $isExcused = ($item['status'] === 'excused' || $item['excuse_status'] === 'approved');
                  $isAbsent = (!$isTardy && !$isExcused);

                  $filterType = $isTardy ? 'Tardy' : ($isExcused ? 'Excused' : 'Absent');

                  // Date formatting
                  $formattedDate = date('M j, Y', strtotime($item['date']));
                  
                  // Lateness calculation
                  $scheduledTimeStr = $item['scheduled_time'] ?? '08:00:00';
                  $actualTimeStr = $item['time'] ?? '08:00:00';
                  $minutesLate = max(5, (int)round((strtotime($actualTimeStr) - strtotime($scheduledTimeStr)) / 60));
                  
                  $searchText = strtolower(($item['student_no'] ?? '') . ' ' . $item['first_name'] . ' ' . $item['last_name'] . ' ' . $item['section'] . ' ' . $filterType . ' ' . ($item['course_code'] ?? ''));

                  $itemJson = htmlspecialchars(json_encode([
                    'student_id'   => $item['student_id'],
                    'student_no'   => $item['student_no'] ?? 'N/A',
                    'name'         => $item['first_name'] . ' ' . $item['last_name'],
                    'email'        => $item['email'] ?? '',
                    'section'      => $item['section'] ?? '31001',
                    'course_code'  => $item['course_code'] ?? 'IT301',
                    'course_title' => $item['course_title'] ?? 'Web Systems and Technologies',
                    'date'         => $formattedDate,
                    'time'         => date('h:i A', strtotime($actualTimeStr)),
                    'status'       => $item['status'],
                    'filter_type'  => $filterType,
                    'minutes_late' => $minutesLate,
                    'parent_name'  => $item['parent_name'] ?? '',
                    'parent_email' => $item['parent_email'] ?? '',
                    'parent_phone' => $item['parent_number'] ?? '',
                    'alert_sent'   => !empty($item['parent_alert_id']),
                    'alert_time'   => !empty($item['alert_time']) ? date('h:i A', strtotime($item['alert_time'])) : '',
                    'excuse_id'    => $item['excuse_slip_id'] ?? null,
                    'excuse_status'=> $item['excuse_status'] ?? null,
                    'excuse_reason'=> $item['excuse_reason'] ?? '',
                  ]), ENT_QUOTES, 'UTF-8');
                ?>
                  <tr class="log-row hover:bg-slate-50/80 transition" data-class="<?= htmlspecialchars($item['section']) ?>" data-type="<?= htmlspecialchars($filterType) ?>" data-text="<?= htmlspecialchars($searchText) ?>">
                    <!-- Student & ID -->
                    <td class="py-3.5 px-4">
                      <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg bg-[#1e3b8a] text-white font-bold text-[10px] flex items-center justify-center shrink-0">
                          <?= htmlspecialchars($initials) ?>
                        </div>
                        <div>
                          <div class="font-bold text-slate-900"><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></div>
                          <div class="font-mono text-[10px] text-slate-400"><?= htmlspecialchars($item['student_no'] ?? 'N/A') ?></div>
                        </div>
                      </div>
                    </td>

                    <!-- Class Section -->
                    <td class="py-3.5 px-4">
                      <span class="font-bold text-slate-800"><?= htmlspecialchars($item['section']) ?></span>
                      <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($item['course_code'] ?? 'IT301') ?></div>
                    </td>

                    <!-- Date & Time Logged -->
                    <td class="py-3.5 px-4">
                      <div class="font-bold text-slate-800"><?= $formattedDate ?></div>
                      <div class="text-[10px] text-slate-400">
                        <?php if ($isTardy): ?>
                          <?= date('h:i A', strtotime($actualTimeStr)) ?> (<?= $minutesLate ?>m late)
                        <?php elseif ($isExcused): ?>
                          <?= !empty($item['excuse_reason']) ? htmlspecialchars((strlen($item['excuse_reason']) > 24) ? substr($item['excuse_reason'], 0, 21) . '...' : $item['excuse_reason']) : 'Official Exemption' ?>
                        <?php else: ?>
                          Session <?= date('h:i A', strtotime($scheduledTimeStr)) ?>
                        <?php endif; ?>
                      </div>
                    </td>

                    <!-- Type & Severity -->
                    <td class="py-3.5 px-4">
                      <?php if ($isTardy): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                          ⏱ Tardy (<?= $minutesLate ?> min)
                        </span>
                      <?php elseif ($isExcused): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                          ✉ Excused Slip #<?= htmlspecialchars($item['excuse_slip_id']) ?>
                        </span>
                      <?php else: ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                          ● Unexcused Absent
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Parent Alert Status -->
                    <td class="py-3.5 px-4">
                      <?php if (!empty($item['parent_alert_id'])): ?>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                          ✓ SMS Sent (<?= !empty($item['alert_time']) ? date('h:i A', strtotime($item['alert_time'])) : 'Dispatched' ?>)
                        </span>
                      <?php elseif ($isExcused): ?>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                          Exempted by Faculty
                        </span>
                      <?php elseif (!empty($item['parent_number']) || !empty($item['parent_email'])): ?>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                          ✓ SMS &amp; Email Sent
                        </span>
                      <?php else: ?>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                          No Parent Contact
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Excuse Linkage -->
                    <td class="py-3.5 px-4">
                      <?php if (!empty($item['excuse_slip_id'])): ?>
                        <?php if ($item['excuse_status'] === 'approved'): ?>
                          <span class="text-xs font-bold text-emerald-600">✓ Approved</span>
                        <?php elseif ($item['excuse_status'] === 'declined'): ?>
                          <span class="text-xs font-bold text-rose-600">✕ Declined (Slip #<?= htmlspecialchars($item['excuse_slip_id']) ?>)</span>
                        <?php else: ?>
                          <a href="<?php echo url('teacher/excuse-slips'); ?>" class="text-xs font-bold text-blue-600 hover:underline">Review Slip #<?= htmlspecialchars($item['excuse_slip_id']) ?> →</a>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-slate-400">—</span>
                      <?php endif; ?>
                    </td>

                    <!-- Actions -->
                    <td class="py-3.5 px-4 text-right">
                      <button type="button" class="text-xs text-blue-600 font-bold hover:underline cursor-pointer" onclick='showAuditModal(<?= $itemJson ?>)'>
                        View Audit
                      </button>
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

<!-- Audit Detail Modal -->
<div id="auditModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeAuditModal()"></div>

  <div class="flex min-h-screen items-center justify-center p-4">
    <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 text-left shadow-2xl border border-slate-100 transform transition-all">
      <!-- Modal Header -->
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <div>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">Attendance Audit</span>
          <h3 class="text-lg font-black text-slate-900 mt-1" id="modal-student-name">Student Name</h3>
          <p class="text-xs text-slate-400 font-mono" id="modal-student-id">230110001</p>
        </div>
        <button type="button" onclick="closeAuditModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
          ✕
        </button>
      </div>

      <!-- Details Grid -->
      <div class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
          <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Course &amp; Section</div>
            <div class="font-bold text-slate-800 mt-0.5" id="modal-class-info">IT301 • Section 31001</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Exception Date</div>
            <div class="font-bold text-slate-800 mt-0.5" id="modal-date-info">Sep 14, 2026</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Severity Status</div>
            <div class="font-bold mt-0.5" id="modal-status-badge">● Absent</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Logged Timestamp</div>
            <div class="font-bold text-slate-800 mt-0.5" id="modal-time-info">08:00 AM</div>
          </div>
        </div>

        <!-- Parent Notification Info -->
        <div class="p-3.5 rounded-2xl bg-blue-50/50 border border-blue-100/80">
          <div class="text-[10px] font-bold text-blue-800 uppercase tracking-wider mb-1.5">Parent Notification Dispatch</div>
          <div class="flex items-center justify-between">
            <span class="text-slate-600">Emergency Alert Status:</span>
            <span class="font-bold text-slate-900" id="modal-parent-status">SMS &amp; Email Sent</span>
          </div>
          <div class="flex items-center justify-between mt-1 text-[11px] text-slate-500">
            <span>Registered Contact:</span>
            <span class="font-mono text-slate-700" id="modal-parent-contact">—</span>
          </div>
        </div>

        <!-- Excuse Slip Linkage -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Excuse Slip Endorsement</div>
          <div id="modal-excuse-content" class="text-slate-600">
            No formal excuse slip submitted for this session.
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
        <button type="button" onclick="closeAuditModal()" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function filterExceptionLogs() {
  const cls = document.getElementById('filter-class').value;
  const typ = document.getElementById('filter-type').value;
  const q = document.getElementById('search-log').value.toLowerCase().trim();

  const rows = document.querySelectorAll('.log-row');
  let count = 0;

  rows.forEach(row => {
    const rowClass = row.getAttribute('data-class');
    const rowType = row.getAttribute('data-type');
    const rowText = (row.getAttribute('data-text') || '').toLowerCase();

    const matchClass = (cls === 'all' || rowClass === cls);
    const matchType = (typ === 'all' || rowType === typ);
    const matchQuery = (!q || rowText.includes(q));

    if (matchClass && matchType && matchQuery) {
      row.style.display = '';
      count++;
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('visible-log-count').textContent = count;
}

function showAuditModal(data) {
  document.getElementById('modal-student-name').textContent = data.name;
  document.getElementById('modal-student-id').textContent = data.student_no;
  document.getElementById('modal-class-info').textContent = (data.course_code || 'IT301') + ' • Section ' + (data.section || '31001');
  document.getElementById('modal-date-info').textContent = data.date;
  document.getElementById('modal-time-info').textContent = data.time + (data.filter_type === 'Tardy' ? ' (' + data.minutes_late + 'm late)' : '');
  
  const badgeEl = document.getElementById('modal-status-badge');
  if (data.filter_type === 'Tardy') {
    badgeEl.className = 'font-bold mt-0.5 text-amber-600';
    badgeEl.textContent = '⏱ Tardy (' + data.minutes_late + ' mins)';
  } else if (data.filter_type === 'Excused') {
    badgeEl.className = 'font-bold mt-0.5 text-blue-600';
    badgeEl.textContent = '✉ Excused Slip #' + (data.excuse_id || '');
  } else {
    badgeEl.className = 'font-bold mt-0.5 text-rose-600';
    badgeEl.textContent = '● Unexcused Absent';
  }

  // Parent status
  const parentStatusEl = document.getElementById('modal-parent-status');
  const parentContactEl = document.getElementById('modal-parent-contact');
  if (data.alert_sent) {
    parentStatusEl.textContent = '✓ SMS Dispatched (' + data.alert_time + ')';
    parentContactEl.textContent = data.parent_email || data.parent_phone || 'Institutional Gateway';
  } else if (data.parent_email || data.parent_phone) {
    parentStatusEl.textContent = '✓ Automated Notification Sent';
    parentContactEl.textContent = data.parent_email || data.parent_phone;
  } else {
    parentStatusEl.textContent = 'Pending Parent Contact';
    parentContactEl.textContent = 'No guardian phone/email on file';
  }

  // Excuse slip content
  const excuseEl = document.getElementById('modal-excuse-content');
  if (data.excuse_id) {
    const stClass = data.excuse_status === 'approved' ? 'text-emerald-600' : (data.excuse_status === 'declined' ? 'text-rose-600' : 'text-amber-600');
    excuseEl.innerHTML = `
      <div class="flex items-center justify-between mb-1">
        <span class="font-bold text-slate-800">Slip #${data.excuse_id}</span>
        <span class="font-bold uppercase text-[10px] ${stClass}">Status: ${data.excuse_status || 'Pending'}</span>
      </div>
      <div class="text-[11px] text-slate-500 italic mb-2">"${data.excuse_reason || 'Medical / Personal Exemption'}"</div>
      <a href="<?php echo url('teacher/excuse-slips'); ?>" class="text-blue-600 font-bold hover:underline inline-flex items-center gap-1">
        Go to Excuse Slip Review &rarr;
      </a>
    `;
  } else {
    excuseEl.innerHTML = '<span class="text-slate-400">No formal excuse slip submitted for this session.</span>';
  }

  document.getElementById('auditModal').classList.remove('hidden');
}

function closeAuditModal() {
  document.getElementById('auditModal').classList.add('hidden');
}

function exportExceptionLogsCSV() {
  const rows = document.querySelectorAll('.log-row');
  const visibleRows = Array.from(rows).filter(r => r.style.display !== 'none');

  if (visibleRows.length === 0) {
    if (window.APP && window.APP.toast) {
      APP.toast('No visible records to export.', 'warning');
    } else {
      alert('No visible records to export.');
    }
    return;
  }

  const csvRows = [
    ['Student ID', 'Student Name', 'Section', 'Date', 'Type', 'Alert Status', 'Excuse Linkage']
  ];

  visibleRows.forEach(row => {
    const cols = row.querySelectorAll('td');
    if (cols.length >= 6) {
      const nameEl = cols[0].querySelector('.font-bold');
      const idEl = cols[0].querySelector('.font-mono');
      const studentName = nameEl ? nameEl.textContent.trim() : '';
      const studentId = idEl ? idEl.textContent.trim() : '';
      
      const secEl = cols[1].querySelector('.font-bold');
      const section = secEl ? secEl.textContent.trim() : '';
      
      const dateEl = cols[2].querySelector('.font-bold');
      const date = dateEl ? dateEl.textContent.trim() : '';
      
      const typeEl = cols[3].querySelector('span');
      const type = typeEl ? typeEl.textContent.trim() : '';
      
      const alertEl = cols[4].querySelector('span');
      const alertStatus = alertEl ? alertEl.textContent.trim() : '';
      
      const excuse = cols[5].textContent.trim();

      csvRows.push([
        `"${studentId.replace(/"/g, '""')}"`,
        `"${studentName.replace(/"/g, '""')}"`,
        `"${section.replace(/"/g, '""')}"`,
        `"${date.replace(/"/g, '""')}"`,
        `"${type.replace(/"/g, '""')}"`,
        `"${alertStatus.replace(/"/g, '""')}"`,
        `"${excuse.replace(/"/g, '""')}"`
      ]);
    }
  });

  const csvContent = '\uFEFF' + csvRows.map(e => Array.isArray(e) ? e.join(',') : e).join('\n');
  const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  const today = new Date().toISOString().split('T')[0];
  a.download = `tardy_absence_logs_${today}.csv`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);

  if (window.APP && window.APP.toast) {
    APP.toast(`Exported ${visibleRows.length} exception records successfully.`, 'success');
  }
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

