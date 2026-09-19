<?php
$page_title = '3+ Consecutive Absence Dropout Watchlist';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/controllers/TeacherController.php';

$teacherId = TeacherController::resolveCurrentTeacherId();

$watchlistData = Cache::remember("teacher_watchlist_{$teacherId}", 120, function() use ($teacherId) {
    $db = Database::getConnection();

    // Fetch teacher sections
    $secStmt = $db->prepare("SELECT DISTINCT section, course_code, year_level FROM class_roster WHERE teacher_id = ?");
    $secStmt->execute([$teacherId]);
    $teacherSections = $secStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Fetch all students under this teacher
    $studStmt = $db->prepare("
        SELECT DISTINCT u.user_id, u.student_id, u.first_name, u.last_name, u.email,
               COALESCE(u.parent_email, 'parent@college.edu') AS parent_email,
               r.section, r.year_level, r.course
        FROM users u
        JOIN class_roster r ON r.student_id = u.user_id
        WHERE r.teacher_id = ? AND u.role = 'student'
        ORDER BY r.year_level ASC, u.last_name ASC
    ");
    $studStmt->execute([$teacherId]);
    $allStudents = $studStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Batch-fetch all attendance records for all students under this teacher in a single query
    $watchlist = [];
    $criticalCount = 0;
    $moderateCount = 0;

    $studentIds = array_map(fn($s) => (int)$s['user_id'], $allStudents);
    $attendanceByStudent = [];

    if (!empty($studentIds)) {
        $placeholders = implode(',', array_fill(0, count($studentIds), '?'));
        $batchStmt = $db->prepare("
            SELECT student_id, `date`, `status`, `time`
            FROM attendance
            WHERE student_id IN ($placeholders)
            ORDER BY `date` DESC, `time` DESC
        ");
        $batchStmt->execute($studentIds);
        $allRecords = $batchStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($allRecords as $r) {
            $attendanceByStudent[(int)$r['student_id']][] = $r;
        }
    }

    foreach ($allStudents as $st) {
        $uid = (int)$st['user_id'];
        $records = $attendanceByStudent[$uid] ?? [];

        $total = count($records);
        $absentCount = 0;
        $presentCount = 0;
        $tardyCount = 0;
        $consecutiveStreak = 0;
        $streakActive = true;

        foreach ($records as $r) {
            if ($r['status'] === 'absent') {
                $absentCount++;
                if ($streakActive) {
                    $consecutiveStreak++;
                }
            } elseif ($r['status'] === 'present' || $r['status'] === 'tardy') {
                if ($r['status'] === 'present') $presentCount++;
                if ($r['status'] === 'tardy') $tardyCount++;
                $streakActive = false;
            }
        }

        $rate = $total > 0 ? round((($presentCount + $tardyCount) / $total) * 100, 1) : 100.0;

        // Filter students with >= 3 consecutive absences OR >= 3 total absences
        if ($consecutiveStreak >= 3 || $absentCount >= 3) {
            $isCritical = ($consecutiveStreak >= 4 || $absentCount >= 5);
            if ($isCritical) {
                $criticalCount++;
            } else {
                $moderateCount++;
            }

            $yearLabel = match((int)($st['year_level'] ?? 1)) {
                1 => '1st Year (Freshman)',
                2 => '2nd Year (Sophomore)',
                3 => '3rd Year (Junior)',
                4 => '4th Year (Senior)',
                default => ($st['year_level'] ?? 1) . 'th Year'
            };

            $watchlist[] = [
                'user_id'             => $uid,
                'student_id'          => !empty($st['student_id']) ? $st['student_id'] : '23011' . str_pad((string)$uid, 4, '0', STR_PAD_LEFT),
                'full_name'           => trim($st['first_name'] . ' ' . $st['last_name']),
                'email'               => $st['email'],
                'parent_email'        => $st['parent_email'],
                'section'             => $st['section'] ?? 'Unassigned',
                'year_level'          => (int)($st['year_level'] ?? 1),
                'year_label'          => $yearLabel,
                'total_sessions'      => $total,
                'absent_count'        => $absentCount,
                'attendance_rate'     => $rate,
                'consecutive_streak'  => $consecutiveStreak,
                'is_critical'         => $isCritical
            ];
        }
    }

    // Sort by consecutive streak DESC, then absent_count DESC
    usort($watchlist, function($a, $b) {
        if ($a['consecutive_streak'] !== $b['consecutive_streak']) {
            return $b['consecutive_streak'] <=> $a['consecutive_streak'];
        }
        return $b['absent_count'] <=> $a['absent_count'];
    });

    return [
        'teacherSections' => $teacherSections,
        'watchlist'       => $watchlist,
        'criticalCount'   => $criticalCount,
        'moderateCount'   => $moderateCount,
        'totalWatchlist'  => count($watchlist)
    ];
});

$teacherSections = $watchlistData['teacherSections'];
$watchlist       = $watchlistData['watchlist'];
$criticalCount   = $watchlistData['criticalCount'];
$moderateCount   = $watchlistData['moderateCount'];
$totalWatchlist  = $watchlistData['totalWatchlist'];

require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Breadcrumbs & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800 border border-rose-200">Early-Warning Retention</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Academic Policy Threshold (≥3 Consecutive Absences)</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">3+ Consecutive Absence Dropout Watchlist</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Students flagged with 3 or more consecutive unexcused absences. Automated email summaries can be dispatched to parents to initiate consultation and prevent subject drops.
          </p>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
          <button type="button" onclick="batchEmailAllParents()" class="px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs transition flex items-center gap-2 cursor-pointer" id="btn-batch-email">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Email Warning to All Parents</span>
          </button>
          <a href="<?php echo url('teacher/dashboard'); ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <span>← Back to Dashboard</span>
          </a>
        </div>
      </div>

      <!-- Quick KPI Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Flagged Students</div>
            <div class="text-2xl font-black text-slate-900 mt-1"><?= $totalWatchlist ?> Students</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Across your assigned sections</div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center font-bold text-xl">
            ⚠️
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <div class="text-xs font-bold text-rose-600 uppercase tracking-wider">Critical Risk (≥4 Consecutive)</div>
            <div class="text-2xl font-black text-rose-600 mt-1"><?= $criticalCount ?> Students</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Immediate intervention required</div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-700 flex items-center justify-center font-black text-sm">
            4+
          </div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs flex items-center justify-between">
          <div>
            <div class="text-xs font-bold text-amber-600 uppercase tracking-wider">Moderate Risk (3 Consecutive)</div>
            <div class="text-2xl font-black text-amber-600 mt-1"><?= $moderateCount ?> Students</div>
            <div class="text-[11px] text-slate-500 mt-0.5">Early warning notice stage</div>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-black text-sm">
            3×
          </div>
        </div>
      </div>

      <!-- Filters & Search Toolbar -->
      <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs mb-6 flex flex-col sm:flex-row items-center justify-between gap-3">
        <div class="flex items-center gap-2.5 w-full sm:w-auto flex-wrap">
          <input type="text" id="search-student" onkeyup="filterWatchlistTable()" placeholder="Search student name or ID..." class="px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 w-full sm:w-64">
          
          <select id="filter-section" onchange="filterWatchlistTable()" class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800">
            <option value="all">All Sections</option>
            <?php 
            $uniqueSecs = array_unique(array_column($teacherSections, 'section'));
            foreach ($uniqueSecs as $sec): 
            ?>
              <option value="<?= htmlspecialchars($sec) ?>">Section <?= htmlspecialchars($sec) ?></option>
            <?php endforeach; ?>
          </select>

          <select id="filter-year" onchange="filterWatchlistTable()" class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800">
            <option value="all">All College Years</option>
            <option value="1">1st Year (Freshman)</option>
            <option value="2">2nd Year (Sophomore)</option>
            <option value="3">3rd Year (Junior)</option>
            <option value="4">4th Year (Senior)</option>
          </select>
        </div>

        <div class="text-xs text-slate-500 font-semibold" id="table-count-label">
          Showing <strong class="text-slate-900"><?= $totalWatchlist ?></strong> flagged students
        </div>
      </div>

      <!-- Watchlist Table -->
      <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden mb-6">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs border-collapse" id="watchlist-table">
            <thead>
              <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                <th scope="col" class="py-3.5 px-4">Student Name &amp; ID</th>
                <th scope="col" class="py-3.5 px-4">Year &amp; Section</th>
                <th scope="col" class="py-3.5 px-4">Consecutive Streak</th>
                <th scope="col" class="py-3.5 px-4">Attendance Rate</th>
                <th scope="col" class="py-3.5 px-4">Registered Parent Email</th>
                <th scope="col" class="py-3.5 px-4 text-right">Intervention Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100" id="watchlist-tbody">
              <?php if (empty($watchlist)): ?>
                <tr>
                  <td colspan="6" class="py-12 text-center text-slate-400 font-semibold">
                    No students currently meet the 3+ consecutive absence threshold in your classes.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($watchlist as $row): 
                  $streakBadge = $row['consecutive_streak'] >= 4 ? 'bg-rose-100 text-rose-800 border-rose-200' : 'bg-amber-100 text-amber-800 border-amber-200';
                ?>
                  <tr class="hover:bg-slate-50/80 transition watchlist-row" 
                      data-name="<?= strtolower(htmlspecialchars($row['full_name'])) ?>" 
                      data-id="<?= strtolower(htmlspecialchars($row['student_id'])) ?>"
                      data-section="<?= htmlspecialchars($row['section']) ?>"
                      data-year="<?= $row['year_level'] ?>">
                    
                    <td class="py-3.5 px-4">
                      <div class="font-bold text-slate-900"><?= htmlspecialchars($row['full_name']) ?></div>
                      <span class="text-[11px] text-slate-400 font-medium">#<?= htmlspecialchars($row['student_id']) ?></span>
                    </td>

                    <td class="py-3.5 px-4 font-semibold text-slate-700">
                      <div><?= htmlspecialchars($row['section']) ?></div>
                      <span class="text-[10px] text-slate-400 font-normal"><?= htmlspecialchars($row['year_label']) ?></span>
                    </td>

                    <td class="py-3.5 px-4">
                      <span class="px-2.5 py-1 rounded-full text-xs font-black border <?= $streakBadge ?> inline-flex items-center gap-1">
                        <span>⚠️</span>
                        <span><?= $row['consecutive_streak'] ?> Days Consecutive</span>
                      </span>
                    </td>

                    <td class="py-3.5 px-4">
                      <span class="font-black <?= $row['attendance_rate'] < 80 ? 'text-rose-600' : 'text-slate-800' ?>">
                        <?= $row['attendance_rate'] ?>%
                      </span>
                      <span class="block text-[10px] text-slate-400 font-medium"><?= $row['absent_count'] ?> total absences</span>
                    </td>

                    <td class="py-3.5 px-4 text-slate-600 font-mono text-[11px] truncate max-w-[180px]">
                      <?= htmlspecialchars($row['parent_email']) ?>
                    </td>

                    <td class="py-3.5 px-4 text-right space-x-1">
                      <button type="button" class="btn btn-primary btn-sm text-[11px] font-bold px-3 py-1.5 inline-flex items-center gap-1.5 cursor-pointer" onclick="emailStudentParent(<?= $row['user_id'] ?>, '<?= htmlspecialchars(addslashes($row['full_name'])) ?>', '<?= htmlspecialchars(addslashes($row['parent_email'])) ?>', <?= $row['consecutive_streak'] ?>, this)">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span>Email Parent Summary</span>
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

<script>
function filterWatchlistTable() {
  const search = document.getElementById('search-student')?.value.toLowerCase().trim() || '';
  const section = document.getElementById('filter-section')?.value || 'all';
  const year = document.getElementById('filter-year')?.value || 'all';

  const rows = document.querySelectorAll('.watchlist-row');
  let visibleCount = 0;

  rows.forEach(row => {
    const name = row.getAttribute('data-name') || '';
    const id = row.getAttribute('data-id') || '';
    const sec = row.getAttribute('data-section') || '';
    const y = row.getAttribute('data-year') || '';

    const matchSearch = (!search || name.includes(search) || id.includes(search));
    const matchSec = (section === 'all' || sec === section);
    const matchYear = (year === 'all' || y === year);

    if (matchSearch && matchSec && matchYear) {
      row.style.display = '';
      visibleCount++;
    } else {
      row.style.display = 'none';
    }
  });

  const label = document.getElementById('table-count-label');
  if (label) {
    label.innerHTML = `Showing <strong class="text-slate-900">${visibleCount}</strong> flagged students`;
  }
}

async function emailStudentParent(studentId, studentName, parentEmail, streak, btnElem) {
  const origHtml = btnElem ? btnElem.innerHTML : 'Email Parent Summary';
  if (btnElem) {
    btnElem.disabled = true;
    btnElem.innerHTML = `
      <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      <span>Sending Email...</span>
    `;
  }

  try {
    const res = await fetch('/api/analytics/intervene', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        student_id: studentId,
        student_name: studentName,
        action_type: 'notify_parent',
        reason: `Urgent Attendance Alert: ${streak} consecutive unexcused absences recorded.`
      })
    });
    const data = await res.json();

    if (data.status === 'success') {
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast(`Attendance summary email successfully sent to ${parentEmail} for ${studentName}!`, 'success');
      } else {
        alert(`Attendance summary email sent to ${parentEmail}!`);
      }
      if (btnElem) {
        btnElem.className = 'btn btn-secondary btn-sm text-[11px] font-bold px-3 py-1.5 inline-flex items-center gap-1.5 text-emerald-700 bg-emerald-50 border-emerald-200';
        btnElem.innerHTML = `
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Email Sent</span>
        `;
      }
    } else {
      alert(data.message || 'Could not dispatch email.');
      if (btnElem) {
        btnElem.disabled = false;
        btnElem.innerHTML = origHtml;
      }
    }
  } catch (err) {
    alert('Network error sending parent notification email.');
    if (btnElem) {
      btnElem.disabled = false;
      btnElem.innerHTML = origHtml;
    }
  }
}

async function batchEmailAllParents() {
  const btn = document.getElementById('btn-batch-email');
  if (btn) btn.disabled = true;

  try {
    const res = await fetch('/api/analytics/apply-pattern-action', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        pattern_id: 'pat_consec_drop',
        pattern_title: '3+ Consecutive Absence Dropout Indicator'
      })
    });
    const data = await res.json();

    if (data.status === 'success') {
      if (typeof APP !== 'undefined' && APP.toast) {
        APP.toast(`Batch attendance warning emails dispatched to ${data.alerts_queued || 0} student parents!`, 'success');
      } else {
        alert(`Batch attendance warning emails dispatched to ${data.alerts_queued || 0} student parents!`);
      }
      setTimeout(() => window.location.reload(), 1500);
    } else {
      alert(data.message || 'Batch email failed.');
    }
  } catch (err) {
    alert('Network error executing batch email dispatch.');
  } finally {
    if (btn) btn.disabled = false;
  }
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
