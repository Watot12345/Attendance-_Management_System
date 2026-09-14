<?php
/**
 * Daily Attendance Ledger View — includes/views/attendance/daily.php
 * Displays the live daily attendance roster, real-time KPI metrics,
 * Tardy Logs, Absence Logs, manual attendance entries, and CSV export.
 * Features full SSR for instant rendering (zero CLS) and asynchronous client-side hydration.
 */

$page_title = 'Daily Attendance';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/core/Router.php';

$db = Database::getConnection();

// 1. Resolve logged-in teacher ID (consistent with AttendanceController)
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    session_start();
}
$teacherId = 2;
if (!empty($_SESSION['teacher_id'])) {
    $teacherId = (int)$_SESSION['teacher_id'];
} elseif (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
    $teacherId = (int)$_SESSION['user_id'];
} else {
    $tRow = $db->query("SELECT user_id FROM users WHERE role = 'teacher' ORDER BY user_id ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($tRow) $teacherId = (int)$tRow['user_id'];
}

$selectedDate = trim($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDate)) {
    $selectedDate = date('Y-m-d');
}

// 2. Fetch distinct sections for this teacher (for dynamic filter dropdown)
$secStmt = $db->prepare("SELECT DISTINCT section FROM class_roster WHERE teacher_id = ? ORDER BY section ASC");
$secStmt->execute([$teacherId]);
$availableSections = $secStmt->fetchAll(PDO::FETCH_COLUMN);

// 3. Server-Side Render (SSR) initial ledger query
$sql = "
    SELECT 
        cr.roster_id,
        cr.student_id,
        COALESCE(u.student_id, '2026-00000') AS student_number,
        cr.first_name,
        cr.last_name,
        CONCAT(cr.last_name, ', ', cr.first_name) AS full_name,
        cr.section,
        cr.course_code,
        cr.course_title,
        cr.scheduled_time,
        cr.room_number,
        u.email,
        u.avatar_path,
        a.attendance_id,
        a.date AS attendance_date,
        a.time AS time_in,
        a.status AS raw_status,
        a.qr_session_id,
        es.excuse_slip_id,
        es.status AS excuse_status,
        es.reason AS excuse_reason,
        pa.parent_alert_id,
        pa.alert_time
    FROM class_roster cr
    LEFT JOIN users u ON u.user_id = cr.student_id
    LEFT JOIN attendance a ON a.student_id = cr.student_id 
                          AND a.teacher_id = cr.teacher_id 
                          AND a.date = ? 
                          AND a.subject = cr.course_title
    LEFT JOIN excuse_slips es ON es.student_id = cr.student_id 
                             AND es.teacher_id = cr.teacher_id 
                             AND es.date_of_absence = ? 
                             AND es.subject = cr.course_title
    LEFT JOIN parent_alerts pa ON (pa.attendance_id = a.attendance_id 
                                OR (pa.student_id = cr.student_id AND pa.alert_date = ?))
    WHERE cr.teacher_id = ?
    ORDER BY cr.last_name ASC, cr.first_name ASC
";
$stmt = $db->prepare($sql);
$stmt->execute([$selectedDate, $selectedDate, $selectedDate, $teacherId]);
$rawSsrRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ssrRecords = [];
$ssrKpi = [
    'total_enrolled' => count($rawSsrRows),
    'present'        => 0,
    'tardy'          => 0,
    'absent'         => 0,
    'excused'        => 0,
    'unrecorded'     => 0
];

foreach ($rawSsrRows as $r) {
    $method = '—';
    if (!empty($r['attendance_id'])) {
        $method = !empty($r['qr_session_id']) ? 'QR' : 'Manual';
    }

    $rawStatus = $r['raw_status'];
    $displayStatus = 'unrecorded';

    if ($rawStatus === 'present') {
        $displayStatus = 'present';
        $ssrKpi['present']++;
    } elseif ($rawStatus === 'tardy') {
        $displayStatus = 'tardy';
        $ssrKpi['tardy']++;
    } elseif ($rawStatus === 'absent') {
        if (!empty($r['excuse_status']) && strtolower($r['excuse_status']) === 'approved') {
            $displayStatus = 'excused';
            $ssrKpi['excused']++;
        } else {
            $displayStatus = 'absent';
            $ssrKpi['absent']++;
        }
    } else {
        $ssrKpi['unrecorded']++;
    }

    $minutesLate = 0;
    if ($rawStatus === 'tardy' && !empty($r['time_in']) && !empty($r['scheduled_time'])) {
        $scheduledSec = strtotime("1970-01-01 " . $r['scheduled_time']);
        $arrivalSec   = strtotime("1970-01-01 " . $r['time_in']);
        if ($arrivalSec > $scheduledSec) {
            $minutesLate = (int)round(($arrivalSec - $scheduledSec) / 60);
        }
    }

    $ssrRecords[] = array_merge($r, [
        'method'            => $method,
        'status'            => $displayStatus,
        'minutes_late'      => $minutesLate,
        'time_formatted'    => !empty($r['time_in']) ? date('h:i A', strtotime($r['time_in'])) : '—',
        'parent_alert_sent' => !empty($r['parent_alert_id']),
    ]);
}

include __DIR__ . '/../partials/header.php';
?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold" style="background:#EFF6FF; color:#1D4ED8">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                Daily Attendance Ledger
              </span>
              <span class="text-xs text-slate-500">Academic Year 2025–2026</span>
            </div>
            <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Daily Attendance</h1>
            <p class="text-sm mt-1" style="color:var(--color-text-secondary)" id="display-date-label">
              <?= date('l, F j, Y', strtotime($selectedDate)) ?>
            </p>
          </div>
          <div class="flex items-center gap-2">
            <!-- Interactive Date Picker Input -->
            <div class="relative flex items-center bg-white border rounded-lg px-2.5 py-1 shadow-2xs" style="border-color:var(--color-border)">
              <svg class="w-4 h-4 mr-1.5 shrink-0" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
              </svg>
              <input type="date" id="daily-date-picker" value="<?= htmlspecialchars($selectedDate) ?>" class="text-xs font-semibold bg-transparent border-0 p-0 text-slate-700 cursor-pointer focus:ring-0" onchange="onDateChanged(this.value)">
            </div>
            <button type="button" class="btn btn-primary btn-sm flex items-center gap-1.5" onclick="APP.openManualEntryModal()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              <span>+ Manual Entry</span>
            </button>
          </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-present">
            <p class="text-sm font-medium" style="color:var(--color-text-secondary)">Present</p>
            <p class="stat-number text-2xl font-bold mt-1" id="kpi-present" style="color:var(--color-present)"><?= $ssrKpi['present'] ?></p>
          </div>
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-tardy">
            <p class="text-sm font-medium" style="color:var(--color-text-secondary)">Tardy</p>
            <p class="stat-number text-2xl font-bold mt-1" id="kpi-tardy" style="color:var(--color-tardy)"><?= $ssrKpi['tardy'] ?></p>
          </div>
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-absent">
            <p class="text-sm font-medium" style="color:var(--color-text-secondary)">Absent</p>
            <p class="stat-number text-2xl font-bold mt-1" id="kpi-absent" style="color:var(--color-absent)"><?= $ssrKpi['absent'] ?></p>
          </div>
          <div class="bg-white rounded-lg p-4 shadow-card stat-card-total">
            <p class="text-sm font-medium" style="color:var(--color-text-secondary)">Total Enrolled</p>
            <p class="stat-number text-2xl font-bold mt-1" id="kpi-total" style="color:var(--color-teal-500)"><?= $ssrKpi['total_enrolled'] ?></p>
          </div>
        </div>

        <!-- Tab Bar: Daily / Tardy Log / Absence Log -->
        <div id="attendance-tabs">
          <div class="flex gap-0 border-b mb-4" style="border-color:var(--color-border)">
            <button type="button" data-tab-btn="daily" class="px-4 py-2.5 text-sm border-b-2 font-semibold transition cursor-pointer" style="border-color:var(--color-teal-500); color:var(--color-text-primary)" onclick="switchLedgerTab('daily')">Daily</button>
            <button type="button" data-tab-btn="tardy" class="px-4 py-2.5 text-sm border-b-2 font-medium transition cursor-pointer" style="border-color:transparent; color:var(--color-text-secondary)" onclick="switchLedgerTab('tardy')">Tardy Log</button>
            <button type="button" data-tab-btn="absence" class="px-4 py-2.5 text-sm border-b-2 font-medium transition cursor-pointer" style="border-color:transparent; color:var(--color-text-secondary)" onclick="switchLedgerTab('absence')">Absence Log</button>
          </div>

          <!-- Filters Toolbar -->
          <div class="bg-white rounded-lg shadow-card mb-4 border border-slate-100">
            <div class="p-4 flex flex-wrap items-center gap-3">
              <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" id="daily-search-input" class="form-input pl-9 text-xs" placeholder="Search student name or ID..." oninput="debounceLedgerSearch()">
              </div>
              <select id="daily-section-select" class="form-input form-select text-xs w-auto cursor-pointer" onchange="fetchDailyLedger()">
                <option value="all">All Sections</option>
                <?php foreach ($availableSections as $sec): ?>
                  <option value="<?= htmlspecialchars($sec) ?>"><?= htmlspecialchars($sec) ?></option>
                <?php endforeach; ?>
              </select>
              <select id="daily-status-select" class="form-input form-select text-xs w-auto cursor-pointer" onchange="fetchDailyLedger()">
                <option value="all">All Status</option>
                <option value="present">Present</option>
                <option value="tardy">Tardy</option>
                <option value="absent">Absent</option>
                <option value="excused">Excused</option>
                <option value="unrecorded">Unrecorded</option>
              </select>
              <button type="button" class="btn btn-secondary btn-sm flex items-center gap-1.5" onclick="exportDailyCsv()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                <span>Export CSV</span>
              </button>
            </div>
          </div>

          <!-- ═══ 1. DAILY TAB ═══ -->
          <div data-tab-panel="daily" id="panel-daily">
            <div class="bg-white rounded-lg shadow-card overflow-x-auto border border-slate-100">
              <table class="data-table w-full">
                <thead>
                  <tr>
                    <th scope="col">Student Name</th>
                    <th scope="col">Student Number</th>
                    <th scope="col">Section / Subject</th>
                    <th scope="col">Time In</th>
                    <th scope="col">Status</th>
                    <th scope="col">Method</th>
                    <th scope="col" class="text-right">Action</th>
                  </tr>
                </thead>
                <tbody id="daily-tbody">
                  <?php if (empty($ssrRecords)): ?>
                    <tr>
                      <td colspan="7" class="text-center py-8 text-slate-400 text-sm">No student records found in your assigned class roster.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($ssrRecords as $row): ?>
                      <tr>
                        <td class="font-medium">
                          <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 shrink-0">
                              <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)) ?>
                            </div>
                            <div>
                              <span class="text-slate-900 font-semibold"><?= htmlspecialchars($row['full_name']) ?></span>
                            </div>
                          </div>
                        </td>
                        <td class="font-mono text-xs"><?= htmlspecialchars($row['student_number']) ?></td>
                        <td>
                          <span class="font-medium text-slate-800"><?= htmlspecialchars($row['section']) ?></span>
                          <span class="text-xs text-slate-400 block truncate max-w-[180px]" title="<?= htmlspecialchars($row['course_title']) ?>"><?= htmlspecialchars($row['course_title']) ?></span>
                        </td>
                        <td class="text-xs font-mono"><?= htmlspecialchars($row['time_formatted']) ?></td>
                        <td>
                          <?php if ($row['status'] === 'present'): ?>
                            <span class="badge badge-present">● Present</span>
                          <?php elseif ($row['status'] === 'tardy'): ?>
                            <span class="badge badge-tardy">● Tardy</span>
                          <?php elseif ($row['status'] === 'absent'): ?>
                            <span class="badge badge-absent">● Absent</span>
                          <?php elseif ($row['status'] === 'excused'): ?>
                            <span class="badge badge-excused">● Excused</span>
                          <?php else: ?>
                            <span class="badge" style="background:#F1F5F9; color:#64748B">● Unrecorded</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <span class="text-xs font-semibold px-2 py-0.5 rounded <?= $row['method'] === 'QR' ? 'bg-blue-50 text-blue-700 border border-blue-200' : ($row['method'] === 'Manual' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'text-slate-400') ?>">
                            <?= htmlspecialchars($row['method']) ?>
                          </span>
                        </td>
                        <td class="text-right">
                          <button type="button" 
                                  class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline cursor-pointer"
                                  data-student-id="<?= (int)$row['student_id'] ?>"
                                  data-student-name="<?= htmlspecialchars($row['full_name'], ENT_QUOTES, 'UTF-8') ?>"
                                  data-status="<?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?>"
                                  onclick="quickEditStudent(this)">
                            Override
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>

              <!-- Footer with dynamic count -->
              <div class="px-4 py-3 border-t flex items-center justify-between text-xs text-slate-500" style="border-color:var(--color-border)">
                <span>Showing <strong id="daily-table-count" class="text-slate-700"><?= count($ssrRecords) ?></strong> roster student(s)</span>
                <span>Real-time database sync</span>
              </div>
            </div>
          </div>

          <!-- ═══ 2. TARDY LOG TAB ═══ -->
          <div data-tab-panel="tardy" id="panel-tardy" class="hidden">
            <div class="bg-white rounded-lg shadow-card overflow-x-auto border border-slate-100">
              <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:var(--color-border)">
                <h3 class="text-base font-semibold" style="color:var(--color-text-primary)">
                  Tardy Log — <span id="tardy-date-header"><?= date('M j, Y', strtotime($selectedDate)) ?></span>
                </h3>
                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 font-semibold border border-amber-200" id="tardy-badge-count">
                  <?= $ssrKpi['tardy'] ?> late
                </span>
              </div>
              <table class="data-table w-full">
                <thead>
                  <tr>
                    <th scope="col">Student Name</th>
                    <th scope="col">Student ID</th>
                    <th scope="col">Time In</th>
                    <th scope="col">Minutes Late</th>
                    <th scope="col">Parent Alert</th>
                  </tr>
                </thead>
                <tbody id="tardy-tbody">
                  <?php
                  $tardyRows = array_filter($ssrRecords, fn($r) => $r['raw_status'] === 'tardy');
                  if (empty($tardyRows)):
                  ?>
                    <tr>
                      <td colspan="5" class="text-center py-8 text-slate-400 text-sm">No tardy records registered for this date.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($tardyRows as $t): ?>
                      <tr>
                        <td class="font-medium text-slate-900"><?= htmlspecialchars($t['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="font-mono text-xs"><?= htmlspecialchars($t['student_number'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="font-mono text-xs"><?= htmlspecialchars($t['time_formatted'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <span class="font-semibold text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                            +<?= (int)$t['minutes_late'] ?> min
                          </span>
                        </td>
                        <td>
                          <?php if ($t['parent_alert_sent']): ?>
                            <span class="badge badge-sent">● Sent</span>
                          <?php else: ?>
                            <span class="badge badge-pending">● Pending</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- ═══ 3. ABSENCE LOG TAB ═══ -->
          <div data-tab-panel="absence" id="panel-absence" class="hidden">
            <div class="bg-white rounded-lg shadow-card overflow-x-auto border border-slate-100">
              <div class="px-5 py-4 border-b flex items-center justify-between" style="border-color:var(--color-border)">
                <h3 class="text-base font-semibold" style="color:var(--color-text-primary)">
                  Absence Log — <span id="absence-date-header"><?= date('M j, Y', strtotime($selectedDate)) ?></span>
                </h3>
                <span class="text-xs px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 font-semibold border border-rose-200" id="absence-badge-count">
                  <?= $ssrKpi['absent'] ?> absent
                </span>
              </div>
              <table class="data-table w-full">
                <thead>
                  <tr>
                    <th scope="col">Student Name</th>
                    <th scope="col">Student ID</th>
                    <th scope="col">Date</th>
                    <th scope="col">Parent Alert</th>
                    <th scope="col">Excuse Slip</th>
                  </tr>
                </thead>
                <tbody id="absence-tbody">
                  <?php
                  $absenceRows = array_filter($ssrRecords, fn($r) => in_array($r['status'], ['absent', 'excused']));
                  if (empty($absenceRows)):
                  ?>
                    <tr>
                      <td colspan="5" class="text-center py-8 text-slate-400 text-sm">No absent records registered for this date.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($absenceRows as $a): ?>
                      <tr>
                        <td class="font-medium text-slate-900"><?= htmlspecialchars($a['full_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="font-mono text-xs"><?= htmlspecialchars($a['student_number'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="text-xs text-slate-600"><?= htmlspecialchars(date('M j, Y', strtotime($selectedDate)), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                          <?php if ($a['parent_alert_sent']): ?>
                            <span class="badge badge-sent">● Sent</span>
                          <?php else: ?>
                            <span class="badge badge-pending">● Pending</span>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php if (!empty($a['excuse_slip_id'])): ?>
                            <a href="<?= url('teacher/excuse-slips?id=' . (int)$a['excuse_slip_id']) ?>" class="text-xs font-semibold text-teal-600 hover:text-teal-700 inline-flex items-center gap-1">
                              <span>View Slip</span>
                              <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </a>
                          <?php else: ?>
                            <span class="text-xs text-slate-400">None Submitted</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
  <?php include __DIR__ . '/../partials/footer.php'; ?>

<script>
APP.highlightNav('daily');

let currentActiveTab = 'daily';
let searchDebounceTimer = null;
let ledgerAbortController = null;

function switchLedgerTab(tabId) {
  currentActiveTab = tabId;
  const tabs = ['daily', 'tardy', 'absence'];
  tabs.forEach(t => {
    const btn = document.querySelector(`[data-tab-btn="${t}"]`);
    const panel = document.getElementById(`panel-${t}`);
    if (t === tabId) {
      if (btn) {
        btn.style.borderColor = 'var(--color-teal-500)';
        btn.style.color = 'var(--color-text-primary)';
        btn.classList.add('font-semibold');
        btn.classList.remove('font-medium');
      }
      if (panel) panel.classList.remove('hidden');
    } else {
      if (btn) {
        btn.style.borderColor = 'transparent';
        btn.style.color = 'var(--color-text-secondary)';
        btn.classList.remove('font-semibold');
        btn.classList.add('font-medium');
      }
      if (panel) panel.classList.add('hidden');
    }
  });
}

function onDateChanged(newDate) {
  if (!newDate) return;
  const d = new Date(newDate + 'T00:00:00');
  const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
  const label = document.getElementById('display-date-label');
  if (label) label.textContent = d.toLocaleDateString('en-US', options);

  const shortOptions = { month: 'short', day: 'numeric', year: 'numeric' };
  const tardyHeader = document.getElementById('tardy-date-header');
  if (tardyHeader) tardyHeader.textContent = d.toLocaleDateString('en-US', shortOptions);
  const absenceHeader = document.getElementById('absence-date-header');
  if (absenceHeader) absenceHeader.textContent = d.toLocaleDateString('en-US', shortOptions);

  fetchDailyLedger();
}

function debounceLedgerSearch() {
  clearTimeout(searchDebounceTimer);
  searchDebounceTimer = setTimeout(() => {
    fetchDailyLedger();
  }, 300); // Explicit 300ms debounce interval
}

function exportDailyCsv() {
  const dateInput = document.getElementById('daily-date-picker');
  const sectionSelect = document.getElementById('daily-section-select');
  const dateVal = dateInput ? dateInput.value : '';
  const secVal = sectionSelect ? sectionSelect.value : 'all';
  const baseUrl = (typeof window.url === 'function') ? window.url('api/attendance/daily') : '/api/attendance/daily';
  window.location.href = `${baseUrl}?date=${encodeURIComponent(dateVal)}&section=${encodeURIComponent(secVal)}&export=csv`;
}

function quickEditStudent(btnElem) {
  if (!btnElem) return;
  const studentId = btnElem.getAttribute('data-student-id');
  const currentStatus = btnElem.getAttribute('data-status');
  APP.openManualEntryModal();
  setTimeout(() => {
    const studentSelect = document.getElementById('modal-manual-student');
    if (studentSelect && studentId) {
      studentSelect.value = studentId;
      APP._updateManualStudentAvatar(studentSelect);
    }
    const statusSelect = document.getElementById('modal-manual-status');
    if (statusSelect && currentStatus && currentStatus !== 'unrecorded') {
      statusSelect.value = (currentStatus === 'excused') ? 'absent' : currentStatus;
    }
  }, 350);
}

/**
 * Client Hydration: Asynchronously fetches ledger rows and refreshes UI.
 * Employs AbortController to cancel in-flight requests and prevent stale race condition overwrites.
 */
async function fetchDailyLedger() {
  // Abort any ongoing in-flight request to guard against race conditions
  if (ledgerAbortController) {
    ledgerAbortController.abort();
  }
  ledgerAbortController = new AbortController();
  const signal = ledgerAbortController.signal;

  const dateInput = document.getElementById('daily-date-picker');
  const sectionSelect = document.getElementById('daily-section-select');
  const statusSelect = document.getElementById('daily-status-select');
  const searchInput = document.getElementById('daily-search-input');

  const dateVal = dateInput ? dateInput.value : '';
  const secVal = sectionSelect ? sectionSelect.value : 'all';
  const statusVal = statusSelect ? statusSelect.value : 'all';
  const searchVal = searchInput ? searchInput.value.trim() : '';

  const params = new URLSearchParams({
    date: dateVal,
    section: secVal,
    status: statusVal,
    search: searchVal
  });

  try {
    const apiEndpoint = (typeof window.url === 'function') 
      ? window.url(`api/attendance/daily?${params.toString()}`)
      : `/api/attendance/daily?${params.toString()}`;
    const resp = await fetch(apiEndpoint, { signal });
    const data = await resp.json();

    if (data.status !== 'success') {
      return;
    }

    // 1. Update KPI numbers
    if (data.metrics) {
      const p = document.getElementById('kpi-present');
      const t = document.getElementById('kpi-tardy');
      const a = document.getElementById('kpi-absent');
      const tot = document.getElementById('kpi-total');
      if (p) p.textContent = data.metrics.present ?? 0;
      if (t) t.textContent = data.metrics.tardy ?? 0;
      if (a) a.textContent = data.metrics.absent ?? 0;
      if (tot) tot.textContent = data.metrics.total_enrolled ?? 0;

      const tb = document.getElementById('tardy-badge-count');
      if (tb) tb.textContent = `${data.metrics.tardy ?? 0} late`;
      const ab = document.getElementById('absence-badge-count');
      if (ab) ab.textContent = `${data.metrics.absent ?? 0} absent`;
    }

    // 2. Render Daily Table Body
    const records = data.records || [];
    const dailyTbody = document.getElementById('daily-tbody');
    const tableCount = document.getElementById('daily-table-count');
    if (tableCount) tableCount.textContent = records.length;

    if (dailyTbody) {
      if (records.length === 0) {
        dailyTbody.innerHTML = `<tr><td colspan="7" class="text-center py-8 text-slate-400 text-sm">No student records match the selected filters.</td></tr>`;
      } else {
        dailyTbody.innerHTML = records.map(r => {
          const initials = ((r.first_name ? r.first_name[0] : '') + (r.last_name ? r.last_name[0] : '')).toUpperCase() || 'ST';
          
          let badgeHtml = '';
          if (r.status === 'present') badgeHtml = '<span class="badge badge-present">● Present</span>';
          else if (r.status === 'tardy') badgeHtml = '<span class="badge badge-tardy">● Tardy</span>';
          else if (r.status === 'absent') badgeHtml = '<span class="badge badge-absent">● Absent</span>';
          else if (r.status === 'excused') badgeHtml = '<span class="badge badge-excused">● Excused</span>';
          else badgeHtml = '<span class="badge" style="background:#F1F5F9; color:#64748B">● Unrecorded</span>';

          const methodClass = r.method === 'QR' ? 'bg-blue-50 text-blue-700 border border-blue-200' : (r.method === 'Manual' ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'text-slate-400');

          return `
            <tr>
              <td class="font-medium">
                <div class="flex items-center gap-2">
                  <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-xs font-bold text-slate-600 shrink-0">
                    ${initials}
                  </div>
                  <div>
                    <span class="text-slate-900 font-semibold">${escapeHtml(r.full_name)}</span>
                  </div>
                </div>
              </td>
              <td class="font-mono text-xs">${escapeHtml(r.student_number)}</td>
              <td>
                <span class="font-medium text-slate-800">${escapeHtml(r.section)}</span>
                <span class="text-xs text-slate-400 block truncate max-w-[180px]" title="${escapeHtml(r.course_title)}">${escapeHtml(r.course_title)}</span>
              </td>
              <td class="text-xs font-mono">${escapeHtml(r.time_formatted)}</td>
              <td>${badgeHtml}</td>
              <td>
                <span class="text-xs font-semibold px-2 py-0.5 rounded ${methodClass}">
                  ${escapeHtml(r.method)}
                </span>
              </td>
              <td class="text-right">
                <button type="button" 
                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline cursor-pointer"
                        data-student-id="${r.student_id}"
                        data-student-name="${escapeHtml(r.full_name)}"
                        data-status="${escapeHtml(r.status)}"
                        onclick="quickEditStudent(this)">
                  Override
                </button>
              </td>
            </tr>
          `;
        }).join('');
      }
    }

    // 3. Render Tardy Table Body
    const tardyTbody = document.getElementById('tardy-tbody');
    if (tardyTbody) {
      const tardies = records.filter(r => r.raw_status === 'tardy');
      if (tardies.length === 0) {
        tardyTbody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-slate-400 text-sm">No tardy records registered for this date.</td></tr>`;
      } else {
        tardyTbody.innerHTML = tardies.map(t => `
          <tr>
            <td class="font-medium text-slate-900">${escapeHtml(t.full_name)}</td>
            <td class="font-mono text-xs">${escapeHtml(t.student_number)}</td>
            <td class="font-mono text-xs">${escapeHtml(t.time_formatted)}</td>
            <td>
              <span class="font-semibold text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                +${t.minutes_late || 0} min
              </span>
            </td>
            <td>
              ${t.parent_alert_sent ? '<span class="badge badge-sent">● Sent</span>' : '<span class="badge badge-pending">● Pending</span>'}
            </td>
          </tr>
        `).join('');
      }
    }

    // 4. Render Absence Table Body
    const absenceTbody = document.getElementById('absence-tbody');
    if (absenceTbody) {
      const absents = records.filter(r => r.status === 'absent' || r.status === 'excused');
      if (absents.length === 0) {
        absenceTbody.innerHTML = `<tr><td colspan="5" class="text-center py-8 text-slate-400 text-sm">No absent records registered for this date.</td></tr>`;
      } else {
        absenceTbody.innerHTML = absents.map(a => `
          <tr>
            <td class="font-medium text-slate-900">${escapeHtml(a.full_name)}</td>
            <td class="font-mono text-xs">${escapeHtml(a.student_number)}</td>
            <td class="text-xs text-slate-600">${escapeHtml(dateVal)}</td>
            <td>
              ${a.parent_alert_sent ? '<span class="badge badge-sent">● Sent</span>' : '<span class="badge badge-pending">● Pending</span>'}
            </td>
            <td>
              ${a.excuse_slip_id ? `<a href="/teacher/excuse-slips?id=${a.excuse_slip_id}" class="text-xs font-semibold text-teal-600 hover:text-teal-700 inline-flex items-center gap-1">View Slip →</a>` : '<span class="text-xs text-slate-400">None Submitted</span>'}
            </td>
          </tr>
        `).join('');
      }
    }

  } catch (err) {
    if (err.name === 'AbortError') {
      // Aborted gracefully by a newer search/filter request; do nothing
      return;
    }
    console.error('Ledger sync error:', err);
  }
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[m]);
}

// Global hook for app.js
window.fetchDailyLedger = fetchDailyLedger;
</script>

