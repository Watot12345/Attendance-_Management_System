<?php
$page_title = 'My Attendance History';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/controllers/StudentController.php';

$studentId = StudentController::resolveCurrentStudentId();
$db = Database::getConnection();

// Fetch student profile details
$studStmt = $db->prepare("
    SELECT u.user_id, u.student_id, u.first_name, u.last_name, u.email,
           r.course, r.section, r.year_level
    FROM users u
    LEFT JOIN class_roster r ON r.student_id = u.user_id
    WHERE u.user_id = ?
    LIMIT 1
");
$studStmt->execute([$studentId]);
$student = $studStmt->fetch(PDO::FETCH_ASSOC);
$studentName = $student ? trim($student['first_name'] . ' ' . $student['last_name']) : 'Student';
$studentCode = !empty($student['student_id']) ? $student['student_id'] : '23011' . str_pad((string)$studentId, 4, '0', STR_PAD_LEFT);
$studentSection = $student['section'] ?? 'BSIT 3-A';

// Fetch all attendance records from database for this student
$attStmt = $db->prepare("
    SELECT a.attendance_id, a.date, a.time, a.subject, a.status, a.verified_by,
           COALESCE(r.section, :sec) AS section,
           COALESCE(r.course, 'BSIT') AS course
    FROM attendance a
    LEFT JOIN class_roster r ON r.student_id = a.student_id
    WHERE a.student_id = :sid
    ORDER BY a.date DESC, a.time DESC
");
$attStmt->execute([':sid' => $studentId, ':sec' => $studentSection]);
$records = $attStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Compute metrics & streak
$totalSessions = count($records);
$presentCount = 0;
$tardyCount = 0;
$absentCount = 0;
$consecutiveAbsences = 0;
$streakActive = true;

foreach ($records as $r) {
    if ($r['status'] === 'absent') {
        $absentCount++;
        if ($streakActive) {
            $consecutiveAbsences++;
        }
    } elseif ($r['status'] === 'present' || $r['status'] === 'tardy') {
        if ($r['status'] === 'present') $presentCount++;
        if ($r['status'] === 'tardy') $tardyCount++;
        $streakActive = false;
    }
}

$rate = $totalSessions > 0 ? round((($presentCount + $tardyCount) / $totalSessions) * 100, 1) : 100.0;

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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">Student Portal</span>
            <span class="text-xs text-slate-500 font-semibold"><?php echo htmlspecialchars($studentName); ?> • <?php echo htmlspecialchars($studentCode); ?></span>
          </div>
          <h1 class="text-2xl font-black text-slate-900 tracking-tight">My Attendance History &amp; Records</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1">
            Review your historical course attendance records, punctuality tracking, and official dropout risk status.
          </p>
        </div>

        <div class="flex items-center gap-3">
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

      <!-- Overall Performance Overview Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs text-center">
          <div class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Total Sessions</div>
          <div class="text-2xl font-black text-slate-800 mt-1"><?php echo $totalSessions; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5"><?php echo $rate; ?>% Overall Rate</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs text-center">
          <div class="text-[11px] text-emerald-600 font-bold uppercase tracking-wider">Present</div>
          <div class="text-2xl font-black text-emerald-600 mt-1"><?php echo $presentCount; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">Verified Check-ins</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs text-center">
          <div class="text-[11px] text-amber-600 font-bold uppercase tracking-wider">Late / Tardy</div>
          <div class="text-2xl font-black text-amber-600 mt-1"><?php echo $tardyCount; ?></div>
          <div class="text-[11px] text-slate-500 mt-0.5">Within Grace Period</div>
        </div>
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs text-center">
          <div class="text-[11px] text-rose-600 font-bold uppercase tracking-wider">Unexcused Absences</div>
          <div class="text-2xl font-black text-rose-600 mt-1"><?php echo $absentCount; ?></div>
          <div class="text-[11px] <?php echo $consecutiveAbsences >= 3 ? 'text-rose-600 font-bold' : 'text-slate-500'; ?> mt-0.5">
            <?php echo $consecutiveAbsences; ?> Consecutive
          </div>
        </div>
      </div>

      <!-- Attendance Records Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
          <h2 class="text-sm font-bold text-slate-800">Historical Attendance Log (<?php echo count($records); ?> Records)</h2>
          <span class="text-xs text-slate-400">Chronological Record</span>
        </div>
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200">
              <tr>
                <th class="py-3 px-4">Date &amp; Time</th>
                <th class="py-3 px-4">Course / Subject</th>
                <th class="py-3 px-4">Section</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4">Verified By</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-slate-700">
              <?php if (empty($records)): ?>
                <tr>
                  <td colspan="5" class="py-8 text-center text-slate-400">
                    No attendance records found for this student.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($records as $r): ?>
                  <?php
                    $dTime = date('M d, Y', strtotime($r['date']));
                    if (!empty($r['time'])) {
                        $dTime .= ' • ' . date('h:i A', strtotime($r['time']));
                    }
                    $status = strtolower($r['status'] ?? 'present');
                    $badgeClass = match($status) {
                        'present' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                        'tardy'   => 'bg-amber-100 text-amber-800 border-amber-200',
                        'absent'  => 'bg-rose-100 text-rose-800 border-rose-200',
                        'excused' => 'bg-blue-100 text-blue-800 border-blue-200',
                        default   => 'bg-slate-100 text-slate-700 border-slate-200'
                    };
                  ?>
                  <tr class="hover:bg-slate-50/80 transition">
                    <td class="py-3 px-4 font-semibold text-slate-800"><?php echo htmlspecialchars($dTime); ?></td>
                    <td class="py-3 px-4">
                      <div class="font-bold text-slate-800"><?php echo htmlspecialchars($r['subject'] ?: 'Academic Class'); ?></div>
                    </td>
                    <td class="py-3 px-4">
                      <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-bold text-[10px]">
                        <?php echo htmlspecialchars($r['section'] ?: '31001'); ?>
                      </span>
                    </td>
                    <td class="py-3 px-4">
                      <span class="px-2.5 py-0.5 rounded-full border text-[11px] font-bold capitalize <?php echo $badgeClass; ?>">
                        ● <?php echo htmlspecialchars($status); ?>
                      </span>
                    </td>
                    <td class="py-3 px-4 text-slate-500">
                      <?php echo htmlspecialchars($r['verified_by'] ?: 'Faculty Instructor'); ?>
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
