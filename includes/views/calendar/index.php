<?php
$page_title = 'My Attendance Calendar';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/controllers/StudentController.php';

$studentId = StudentController::resolveCurrentStudentId();
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');

$calendarData = StudentController::getStudentCalendarData($studentId, $year, $month);
$student = $calendarData['student'];
$kpis = $calendarData['kpis'];
$days = $calendarData['days'];
$monthLabel = $calendarData['month_label'];
$prevMonth = $calendarData['prev_month'];
$prevYear = $calendarData['prev_year'];
$nextMonth = $calendarData['next_month'];
$nextYear = $calendarData['next_year'];
$firstDayOffset = $calendarData['first_day_offset'];

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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">Student Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Official Academic Calendar</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Attendance Calendar</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Track your daily attendance records, verified excuse slips, and institutional class suspensions/holidays across all semester courses.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <a href="<?php echo url('student/scanner'); ?>" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Scan QR Code</span>
          </a>
          <button type="button" onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print Attendance</span>
          </button>
        </div>
      </div>

      <?php if (!empty($calendarData['consecutive_absences']) && $calendarData['consecutive_absences'] >= 3): ?>
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
                    <?php echo (int)$calendarData['consecutive_absences']; ?> Consecutive Unexcused Absences
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
      <?php elseif (!empty($calendarData['has_dropout_warning'])): ?>
        <!-- MODERATE: Attendance Caution Banner -->
        <div class="mb-6 p-4 rounded-2xl bg-amber-50 border border-amber-300 text-slate-800 shadow-xs">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-lg bg-amber-500 text-white flex items-center justify-center font-black text-sm shrink-0">
                ⚠
              </div>
              <div>
                <div class="text-xs font-bold text-amber-900">
                  Attendance Advisory: <?php echo (int)($calendarData['total_all_absences'] ?? $kpis['absent_days']); ?> Total Absences Recorded
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

      <!-- Quick Attendance Status Summary -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <!-- Present Days -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-base shrink-0">
            ✓
          </div>
          <div>
            <div class="text-lg font-black text-emerald-600" id="kpi-present-days">
              <?php echo $kpis['present_days']; ?> <?php echo $kpis['present_days'] === 1 ? 'Day' : 'Days'; ?>
            </div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Present</div>
          </div>
        </div>

        <!-- Tardy / Late Days -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center font-bold text-base shrink-0">
            ⏱
          </div>
          <div>
            <div class="text-lg font-black text-amber-600" id="kpi-tardy-days">
              <?php echo $kpis['tardy_days']; ?> <?php echo $kpis['tardy_days'] === 1 ? 'Day' : 'Days'; ?>
            </div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Tardy / Late</div>
          </div>
        </div>

        <!-- Unexcused Absent -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center font-bold text-base shrink-0">
            ✕
          </div>
          <div>
            <div class="text-lg font-black text-rose-600" id="kpi-absent-days">
              <?php echo $kpis['absent_days']; ?> <?php echo $kpis['absent_days'] === 1 ? 'Day' : 'Days'; ?>
            </div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Unexcused Absent</div>
          </div>
        </div>

        <!-- Excused Slip -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center font-bold text-base shrink-0">
            ✉
          </div>
          <div>
            <div class="text-lg font-black text-blue-600" id="kpi-excused-days">
              <?php echo $kpis['excused_days']; ?> <?php echo $kpis['excused_days'] === 1 ? 'Day' : 'Days'; ?>
            </div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Excused Slip</div>
          </div>
        </div>

        <!-- No Class / Holidays / Suspensions -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3 col-span-2 sm:col-span-1">
          <div class="w-10 h-10 rounded-xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center font-bold text-base shrink-0">
            ⛱
          </div>
          <div>
            <div class="text-lg font-black text-purple-700" id="kpi-noclass-days">
              <?php echo $kpis['no_class_days']; ?> <?php echo $kpis['no_class_days'] === 1 ? 'Day' : 'Days'; ?>
            </div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">No Class / Breaks</div>
          </div>
        </div>
      </div>

      <!-- Main Interactive Calendar Card -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <!-- Month Switcher Header -->
        <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3">
            <a href="<?php echo url('student/calendar?month=' . $prevMonth . '&year=' . $prevYear); ?>" class="p-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 shadow-2xs transition" title="Previous Month">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight" id="calendar-month-label">
              <?php echo htmlspecialchars($monthLabel); ?>
            </h2>
            <a href="<?php echo url('student/calendar?month=' . $nextMonth . '&year=' . $nextYear); ?>" class="p-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 shadow-2xs transition" title="Next Month">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
          </div>

          <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span><?php echo htmlspecialchars($student['name'] . ' (' . $student['section'] . ')'); ?></span>
          </div>
        </div>

        <!-- Calendar Days Grid -->
        <div class="p-5 sm:p-7">
          <!-- Weekday Headers -->
          <div class="grid grid-cols-7 gap-2 mb-3 text-center">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sun</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Mon</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Tue</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Wed</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Thu</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Fri</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sat</div>
          </div>

          <!-- Calendar Grid Cells -->
          <div class="grid grid-cols-7 gap-2 sm:gap-3 text-xs" id="calendar-grid-cells">
            <!-- Leading empty offset days -->
            <?php for ($i = 0; $i < $firstDayOffset; $i++): ?>
              <div class="h-20 sm:h-24 p-2 rounded-2xl bg-slate-50/50 border border-slate-100 opacity-30"></div>
            <?php endfor; ?>

            <!-- Real Calendar Days of Current Month -->
            <?php foreach ($days as $day): 
              $isClickable = !empty($day['details']);
              $dateFormatted = date('M j, Y', strtotime($day['date']));
              $escapedDate = htmlspecialchars($dateFormatted, ENT_QUOTES);
              $escapedBadge = htmlspecialchars($day['badge_text'], ENT_QUOTES);
              $escapedDetails = htmlspecialchars($day['details'], ENT_QUOTES);
            ?>
              <div 
                <?php if ($isClickable): ?>
                  onclick="showCalendarDayInfo('<?php echo $escapedDate; ?>', '<?php echo $escapedBadge; ?>', '<?php echo $escapedDetails; ?>', '<?php echo $day['status']; ?>')"
                <?php endif; ?>
                class="h-20 sm:h-24 p-2.5 rounded-2xl border transition flex flex-col justify-between <?php echo $day['bg_class']; ?> <?php echo $isClickable ? 'cursor-pointer' : ''; ?>"
              >
                <div class="flex items-center justify-between">
                  <span class="font-bold text-xs <?php echo $day['is_today'] ? 'text-indigo-700 font-extrabold' : 'text-slate-800'; ?>">
                    <?php echo $day['day']; ?><?php echo $day['is_today'] ? ' <span class="text-[10px] text-indigo-600 font-semibold hidden sm:inline">(Today)</span>' : ''; ?>
                  </span>

                  <?php if ($day['status'] === 'present'): ?>
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <?php elseif ($day['status'] === 'tardy'): ?>
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                  <?php elseif ($day['status'] === 'absent'): ?>
                    <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                  <?php elseif ($day['status'] === 'excused'): ?>
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                  <?php elseif ($day['status'] === 'suspension'): ?>
                    <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                  <?php elseif ($day['status'] === 'holiday'): ?>
                    <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                  <?php endif; ?>
                </div>

                <?php if (!empty($day['badge_text'])): ?>
                  <div class="text-[10px] font-bold px-1.5 py-0.5 rounded-md text-center border truncate <?php echo $day['badge_class']; ?>">
                    <?php echo htmlspecialchars($day['badge_text']); ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>

            <!-- Trailing padding cells to complete row -->
            <?php 
              $totalCells = $firstDayOffset + count($days);
              $trailingCells = ($totalCells % 7 === 0) ? 0 : (7 - ($totalCells % 7));
              for ($i = 0; $i < $trailingCells; $i++): 
            ?>
              <div class="h-20 sm:h-24 p-2 rounded-2xl bg-slate-50/50 border border-slate-100 opacity-30"></div>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Legend & Live Detail Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs font-semibold text-slate-600">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-emerald-500"></span> Present</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-amber-500"></span> Tardy / Late</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-rose-500"></span> Absent</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-blue-500"></span> Excused Slip</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-purple-500"></span> Holiday</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-amber-600"></span> Suspended</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-slate-200"></span> Weekend</span>
          </div>

          <div class="text-xs font-bold text-indigo-700">
            Standing Rate: <span class="text-emerald-600 font-extrabold"><?php echo $kpis['rate_percentage']; ?>% Overall</span>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Day Detail Modal -->
<div id="calendar-day-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-xs hidden p-4">
  <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-100 transform transition-all">
    <div class="flex items-start justify-between mb-4">
      <div class="flex items-center gap-2.5">
        <div id="modal-icon-container" class="w-10 h-10 rounded-2xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-lg">
          📅
        </div>
        <div>
          <h3 id="modal-day-title" class="font-bold text-slate-900 text-base">Sep 14, 2026</h3>
          <p class="text-xs text-slate-500">Class Attendance &amp; Event Record</p>
        </div>
      </div>
      <button type="button" onclick="closeCalendarModal()" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 mb-5">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Classification</span>
        <span id="modal-status-badge" class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
          Present
        </span>
      </div>
      <p id="modal-day-details" class="text-xs text-slate-700 font-medium leading-relaxed">
        Web Systems and Technologies (present at 03:42 PM)
      </p>
    </div>

    <div class="flex items-center justify-end gap-2">
      <button type="button" onclick="closeCalendarModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50 transition">
        Close
      </button>
      <a href="<?php echo url('student/excuse-slips'); ?>" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 shadow-sm transition">
        View Excuse Slips
      </a>
    </div>
  </div>
</div>

<script>
function showCalendarDayInfo(date, status, details, rawStatus) {
  const modal = document.getElementById('calendar-day-modal');
  const modalTitle = document.getElementById('modal-day-title');
  const modalBadge = document.getElementById('modal-status-badge');
  const modalDetails = document.getElementById('modal-day-details');
  const iconContainer = document.getElementById('modal-icon-container');

  if (modalTitle) modalTitle.textContent = date;
  if (modalBadge) {
    modalBadge.textContent = status || 'Class Day';
    modalBadge.className = 'px-2.5 py-0.5 rounded-md text-xs font-bold border ';
    if (rawStatus === 'present') {
      modalBadge.className += 'bg-emerald-100 text-emerald-800 border-emerald-200';
      if (iconContainer) iconContainer.innerHTML = '✓';
    } else if (rawStatus === 'tardy') {
      modalBadge.className += 'bg-amber-100 text-amber-800 border-amber-200';
      if (iconContainer) iconContainer.innerHTML = '⏱';
    } else if (rawStatus === 'absent') {
      modalBadge.className += 'bg-rose-100 text-rose-800 border-rose-200';
      if (iconContainer) iconContainer.innerHTML = '✕';
    } else if (rawStatus === 'excused') {
      modalBadge.className += 'bg-blue-100 text-blue-800 border-blue-200';
      if (iconContainer) iconContainer.innerHTML = '✉';
    } else if (rawStatus === 'holiday') {
      modalBadge.className += 'bg-purple-100 text-purple-800 border-purple-200';
      if (iconContainer) iconContainer.innerHTML = '🎉';
    } else if (rawStatus === 'suspension') {
      modalBadge.className += 'bg-amber-100 text-amber-800 border-amber-200';
      if (iconContainer) iconContainer.innerHTML = '⚠️';
    } else {
      modalBadge.className += 'bg-slate-100 text-slate-700 border-slate-200';
      if (iconContainer) iconContainer.innerHTML = '📅';
    }
  }

  if (modalDetails) {
    modalDetails.textContent = details || 'No additional logs recorded for this day.';
  }

  if (modal) {
    modal.classList.remove('hidden');
  }

  // Also trigger toast for instant feedback
  if (window.APP && typeof APP.toast === 'function') {
    let toastType = 'info';
    if (rawStatus === 'present') toastType = 'success';
    if (rawStatus === 'tardy' || rawStatus === 'suspension') toastType = 'warning';
    if (rawStatus === 'absent') toastType = 'error';
    APP.toast(`${date} • ${status}`, toastType);
  }
}

function closeCalendarModal() {
  const modal = document.getElementById('calendar-day-modal');
  if (modal) modal.classList.add('hidden');
}

// Close modal on background click or Esc key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeCalendarModal();
});
document.getElementById('calendar-day-modal')?.addEventListener('click', function(e) {
  if (e.target === this) closeCalendarModal();
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
