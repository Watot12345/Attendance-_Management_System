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
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="text-xs text-slate-400 font-medium">Student Portal</span>
            <span class="text-xs text-slate-300">•</span>
            <span class="text-xs text-slate-500 font-medium">Academic Calendar</span>
          </div>
          <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Attendance Calendar</h1>
          <p class="text-xs text-slate-500 mt-0.5">
            View your monthly class attendance logs, excused absences, and academic schedule.
          </p>
        </div>

        <div class="flex items-center gap-2">
          <a href="<?php echo url('student/scanner'); ?>" class="px-3.5 py-2 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Scan QR Code</span>
          </a>
          <button type="button" onclick="window.print()" class="px-3 py-2 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print</span>
          </button>
        </div>
      </div>

      <?php if (!empty($calendarData['consecutive_absences']) && $calendarData['consecutive_absences'] >= 3): ?>
        <!-- CRITICAL: 3+ Consecutive Absence Dropout Indicator Banner -->
        <div class="mb-5 p-4 rounded-xl bg-rose-50 border border-rose-200 text-slate-800">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-rose-600 text-white">
                  Alert
                </span>
                <span class="text-xs font-bold text-rose-900">
                  <?php echo (int)$calendarData['consecutive_absences']; ?> Consecutive Unexcused Absences Detected
                </span>
              </div>
              <p class="text-xs text-rose-700 mt-1">
                You have 3 or more consecutive unexcused absences. Please file an official excuse slip or speak with your instructor immediately to prevent academic penalties.
              </p>
            </div>
            <a href="<?php echo url('student/excuse-slips'); ?>" class="text-xs font-semibold px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white transition shrink-0">
              File Excuse Slip
            </a>
          </div>
        </div>
      <?php elseif (!empty($calendarData['has_dropout_warning'])): ?>
        <!-- MODERATE: Attendance Caution Banner -->
        <div class="mb-5 p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-slate-800">
          <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
            <div class="text-xs text-amber-900">
              <strong class="font-bold">Attendance Notice:</strong> <?php echo (int)($calendarData['total_all_absences'] ?? $kpis['absent_days']); ?> total absences recorded. Maintain regular attendance to avoid academic warnings.
            </div>
            <a href="<?php echo url('student/excuse-slips'); ?>" class="text-xs font-semibold text-amber-800 hover:underline whitespace-nowrap">
              Submit Excuse Slip →
            </a>
          </div>
        </div>
      <?php endif; ?>

      <!-- Minimalist Attendance Metrics -->
      <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 mb-5">
        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
          <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Present</div>
          <div class="text-xl font-bold text-slate-900 mt-0.5" id="kpi-present-days">
            <?php echo $kpis['present_days']; ?> <span class="text-xs font-normal text-slate-400">days</span>
          </div>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
          <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Late / Tardy</div>
          <div class="text-xl font-bold text-slate-900 mt-0.5" id="kpi-tardy-days">
            <?php echo $kpis['tardy_days']; ?> <span class="text-xs font-normal text-slate-400">days</span>
          </div>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
          <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Absent</div>
          <div class="text-xl font-bold text-slate-900 mt-0.5" id="kpi-absent-days">
            <?php echo $kpis['absent_days']; ?> <span class="text-xs font-normal text-slate-400">days</span>
          </div>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs">
          <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Excused</div>
          <div class="text-xl font-bold text-slate-900 mt-0.5" id="kpi-excused-days">
            <?php echo $kpis['excused_days']; ?> <span class="text-xs font-normal text-slate-400">days</span>
          </div>
        </div>

        <div class="bg-white p-3.5 rounded-xl border border-slate-200 shadow-xs col-span-2 sm:col-span-1">
          <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">No Class</div>
          <div class="text-xl font-bold text-slate-900 mt-0.5" id="kpi-noclass-days">
            <?php echo $kpis['no_class_days']; ?> <span class="text-xs font-normal text-slate-400">days</span>
          </div>
        </div>
      </div>

      <!-- Main Interactive Calendar Card -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden mb-6">
        <!-- Month Switcher Header -->
        <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <h2 class="text-sm sm:text-base font-bold text-slate-900" id="calendar-month-label">
              <?php echo htmlspecialchars($monthLabel); ?>
            </h2>
            <div class="flex items-center gap-1">
              <a href="<?php echo url('student/calendar?month=' . $prevMonth . '&year=' . $prevYear); ?>" class="p-1 rounded-md border border-slate-200 hover:bg-slate-50 text-slate-600 transition" title="Previous Month">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
              </a>
              <a href="<?php echo url('student/calendar?month=' . $nextMonth . '&year=' . $nextYear); ?>" class="p-1 rounded-md border border-slate-200 hover:bg-slate-50 text-slate-600 transition" title="Next Month">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
              </a>
            </div>
          </div>

          <div class="text-xs font-medium text-slate-500">
            <?php echo htmlspecialchars($student['name'] . ' • ' . $student['section']); ?>
          </div>
        </div>

        <!-- Calendar Days Grid -->
        <div class="p-4 sm:p-5">
          <!-- Weekday Headers -->
          <div class="grid grid-cols-7 gap-1.5 mb-2 text-center">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 py-1">Sun</div>
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 py-1">Mon</div>
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 py-1">Tue</div>
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 py-1">Wed</div>
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 py-1">Thu</div>
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-600 py-1">Fri</div>
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-400 py-1">Sat</div>
          </div>

          <!-- Calendar Grid Cells (Clean Flat Boxes, No Circle Dots) -->
          <div class="grid grid-cols-7 gap-1.5 sm:gap-2 text-xs" id="calendar-grid-cells">
            <!-- Leading empty offset days -->
            <?php for ($i = 0; $i < $firstDayOffset; $i++): ?>
              <div class="h-16 sm:h-20 p-2 rounded-lg bg-slate-50/40 border border-slate-100 opacity-30"></div>
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
                class="h-16 sm:h-20 p-2 rounded-lg border transition flex flex-col justify-between <?php echo $day['bg_class']; ?> <?php echo $isClickable ? 'cursor-pointer hover:border-slate-300' : ''; ?>"
              >
                <div class="flex items-center justify-between">
                  <span class="text-xs font-semibold <?php echo $day['is_today'] ? 'text-blue-600 font-bold' : ($day['is_weekend'] ? 'text-slate-400' : 'text-slate-700'); ?>">
                    <?php echo $day['day']; ?>
                  </span>
                  <?php if ($day['is_today']): ?>
                    <span class="text-[9px] font-bold text-blue-600 uppercase tracking-tight">Today</span>
                  <?php endif; ?>
                </div>

                <?php if (!empty($day['badge_text'])): ?>
                  <div class="text-[10px] font-medium px-1 py-0.5 rounded text-center border truncate <?php echo $day['badge_class']; ?>">
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
              <div class="h-16 sm:h-20 p-2 rounded-lg bg-slate-50/40 border border-slate-100 opacity-30"></div>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Legend & Footer -->
        <div class="px-5 py-3 bg-slate-50/70 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-slate-500">
          <div class="flex flex-wrap items-center gap-3">
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-emerald-500"></span> Present</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-amber-500"></span> Late</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-rose-500"></span> Absent</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-sky-500"></span> Excused</span>
            <span class="inline-flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded bg-slate-400"></span> Holiday / Suspension</span>
          </div>

          <div class="font-medium text-slate-600">
            Monthly Rate: <strong class="text-slate-900 font-bold"><?php echo $kpis['rate_percentage']; ?>%</strong>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- Day Detail Modal -->
<div id="calendar-day-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 hidden p-4">
  <div class="bg-white rounded-xl max-w-sm w-full p-5 shadow-lg border border-slate-200">
    <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-3">
      <div>
        <h3 id="modal-day-title" class="font-bold text-slate-900 text-sm">Date</h3>
        <p class="text-xs text-slate-400">Attendance Details</p>
      </div>
      <button type="button" onclick="closeCalendarModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-md transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="space-y-2 mb-4">
      <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">Status</span>
        <span id="modal-status-badge" class="px-2 py-0.5 rounded text-xs font-semibold border">Status</span>
      </div>
      <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
        <p id="modal-day-details" class="text-xs text-slate-700 leading-relaxed"></p>
      </div>
    </div>

    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
      <button type="button" onclick="closeCalendarModal()" class="px-3 py-1.5 rounded-lg border border-slate-200 text-slate-600 text-xs font-medium hover:bg-slate-50 transition">
        Close
      </button>
      <a href="<?php echo url('student/excuse-slips'); ?>" class="px-3 py-1.5 rounded-lg bg-[#1e3b8a] hover:bg-[#172554] text-white text-xs font-medium transition">
        Excuse Slips
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

  if (modalTitle) modalTitle.textContent = date;
  if (modalBadge) {
    modalBadge.textContent = status || 'Class Day';
    modalBadge.className = 'px-2 py-0.5 rounded text-xs font-medium border ';
    if (rawStatus === 'present') {
      modalBadge.className += 'bg-emerald-50 text-emerald-700 border-emerald-200';
    } else if (rawStatus === 'tardy' || rawStatus === 'late') {
      modalBadge.className += 'bg-amber-50 text-amber-700 border-amber-200';
    } else if (rawStatus === 'absent') {
      modalBadge.className += 'bg-rose-50 text-rose-700 border-rose-200';
    } else if (rawStatus === 'excused') {
      modalBadge.className += 'bg-sky-50 text-sky-700 border-sky-200';
    } else if (rawStatus === 'holiday' || rawStatus === 'suspension') {
      modalBadge.className += 'bg-slate-100 text-slate-700 border-slate-200';
    } else {
      modalBadge.className += 'bg-slate-50 text-slate-600 border-slate-200';
    }
  }

  if (modalDetails) {
    modalDetails.textContent = details || 'No additional logs recorded for this day.';
  }

  if (modal) {
    modal.classList.remove('hidden');
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
