<?php
$page_title = 'Submitted Excuse Slips & Review';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// 1. Determine active specific teacher ID
// Priority: session (if logged in as teacher) -> URL query parameter -> Default (Prof. Manuel Ramirez, ID 2)
$currentTeacherId = 2;
if (!empty($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'teacher') {
    $currentTeacherId = (int)$_SESSION['user_id'];
} elseif (!empty($_SESSION['teacher_id'])) {
    $currentTeacherId = (int)$_SESSION['teacher_id'];
} elseif (isset($_GET['teacher_id']) && is_numeric($_GET['teacher_id'])) {
    $currentTeacherId = (int)$_GET['teacher_id'];
}

$slips = [];
$allTeachers = [];
$currentTeacher = null;
$currentTeacherName = 'Prof. Manuel Ramirez';
$uniqueSubjects = [];
$counts = [
    'pending'  => 0,
    'approved' => 0,
    'declined' => 0,
    'total'    => 0
];

try {
    $db = Database::getConnection();

    // Fetch list of all registered teachers
    $tStmt = $db->query("SELECT user_id, first_name, last_name, email FROM users WHERE role = 'teacher' ORDER BY first_name ASC");
    $allTeachers = $tStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allTeachers as $t) {
        if ((int)$t['user_id'] === $currentTeacherId) {
            $currentTeacher = $t;
            $currentTeacherName = 'Prof. ' . $t['first_name'] . ' ' . $t['last_name'];
            break;
        }
    }

    // Query excuse slips specifically for this teacher
    $stmt = $db->prepare("
        SELECT 
            es.excuse_slip_id,
            es.student_id,
            es.teacher_id,
            es.subject,
            es.date_of_absence,
            es.reason,
            es.explanation,
            es.status,
            es.declined_reason,
            es.supporting_document,
            es.created_at,
            es.updated_at,
            COALESCE(u.student_id, '2026-00123') AS student_number,
            CONCAT(u.first_name, ' ', u.last_name) AS student_name,
            u.email AS student_email,
            CONCAT(t.first_name, ' ', t.last_name) AS teacher_name
        FROM excuse_slips es
        LEFT JOIN users u ON es.student_id = u.user_id
        LEFT JOIN users t ON es.teacher_id = t.user_id
        WHERE es.teacher_id = ?
        ORDER BY es.created_at DESC, es.excuse_slip_id DESC
    ");
    $stmt->execute([$currentTeacherId]);
    $slips = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($slips as $s) {
        $st = strtolower($s['status'] ?? 'pending');
        if ($st === 'approved') {
            $counts['approved']++;
        } elseif ($st === 'declined' || $st === 'rejected') {
            $counts['declined']++;
        } else {
            $counts['pending']++;
        }
        $counts['total']++;

        if (!empty($s['subject']) && !in_array($s['subject'], $uniqueSubjects)) {
            $uniqueSubjects[] = $s['subject'];
        }
    }
} catch (Exception $e) {
    $slips = [];
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
          <div class="flex items-center gap-2 mb-1 flex-wrap">
            <span class="badge badge-present">Teacher Portal</span>
            <span class="text-xs text-slate-500 font-medium">1st Semester AY 2025–2026</span>
            <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-800 font-bold border border-indigo-200 flex items-center gap-1.5 shadow-2xs">
              <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
              <span><?php echo htmlspecialchars($currentTeacherName); ?></span>
            </span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">Submitted Excuse Slips &amp; Review</h1>
          <p class="text-sm text-slate-500">Reviewing excuse slips specifically assigned to <strong><?php echo htmlspecialchars($currentTeacherName); ?></strong>'s classes. Verify documents and approve or decline clearance requests.</p>
        </div>

        <div class="flex items-center gap-3">
          <!-- Faculty Switcher Dropdown (Shown for Admins / Multi-faculty management) -->
          <?php if (count($allTeachers) > 1 && (($_SESSION['role'] ?? '') !== 'teacher')): ?>
          <div class="flex items-center gap-1.5">
            <label for="teacher-switch" class="text-xs font-semibold text-slate-500 hidden sm:inline">Teacher:</label>
            <select id="teacher-switch" onchange="window.location.href='?teacher_id=' + this.value" class="px-2.5 py-2 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 shadow-2xs focus:outline-none focus:ring-2 focus:ring-emerald-500 cursor-pointer">
              <?php foreach ($allTeachers as $t): ?>
                <option value="<?php echo $t['user_id']; ?>" <?php echo ((int)$t['user_id'] === $currentTeacherId) ? 'selected' : ''; ?>>
                  Prof. <?php echo htmlspecialchars($t['first_name'] . ' ' . $t['last_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endif; ?>

          <button type="button" onclick="exportSlipsToCSV()" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Export Slips CSV</span>
          </button>
        </div>
      </div>

      <!-- KPI Summary Cards (Real Database Counts - Unified Cohesive Color Theme) -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-6 w-full min-w-0 max-w-full">
        <!-- Card 1: Pending Review -->
        <div onclick="setTeacherFilterStatus('pending')" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-blue-400 hover:shadow-sm transition cursor-pointer group w-full min-w-0">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">Pending Review</span>
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
          </div>
          <div class="mt-2 sm:mt-3 flex items-baseline gap-1.5 flex-wrap">
            <span id="kpi-pending" class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900"><?php echo $counts['pending']; ?></span>
            <span class="text-[10px] sm:text-xs font-medium text-slate-400">awaiting decision</span>
          </div>
          <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1 truncate">Action required</p>
        </div>

        <!-- Card 2: Approved Slips -->
        <div onclick="setTeacherFilterStatus('approved')" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-blue-400 hover:shadow-sm transition cursor-pointer group w-full min-w-0">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">Approved Slips</span>
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
          </div>
          <div class="mt-2 sm:mt-3 flex items-baseline gap-1.5 flex-wrap">
            <span id="kpi-approved" class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900"><?php echo $counts['approved']; ?></span>
            <span class="text-[10px] sm:text-xs font-medium text-slate-400">excused</span>
          </div>
          <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1 truncate">Cleared in records</p>
        </div>

        <!-- Card 3: Declined Slips -->
        <div onclick="setTeacherFilterStatus('declined')" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-blue-400 hover:shadow-sm transition cursor-pointer group w-full min-w-0">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">Declined Slips</span>
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
          </div>
          <div class="mt-2 sm:mt-3 flex items-baseline gap-1.5 flex-wrap">
            <span id="kpi-declined" class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900"><?php echo $counts['declined']; ?></span>
            <span class="text-[10px] sm:text-xs font-medium text-slate-400">unexcused</span>
          </div>
          <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1 truncate">Invalid or rejected</p>
        </div>

        <!-- Card 4: Total Received -->
        <div onclick="setTeacherFilterStatus('all')" class="bg-white p-3.5 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:border-blue-400 hover:shadow-sm transition cursor-pointer group w-full min-w-0">
          <div class="flex items-center justify-between gap-1">
            <span class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">Total Received</span>
            <div class="w-7 h-7 sm:w-8 sm:h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-105 transition">
              <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
          </div>
          <div class="mt-2 sm:mt-3 flex items-baseline gap-1.5 flex-wrap">
            <span id="kpi-total" class="text-xl sm:text-2xl md:text-3xl font-black text-slate-900"><?php echo $counts['total']; ?></span>
            <span class="text-[10px] sm:text-xs font-medium text-slate-400">submissions</span>
          </div>
          <p class="text-[10px] sm:text-[11px] text-slate-400 mt-1 truncate">Across assigned classes</p>
        </div>
      </div>

      <!-- Filters & Search Bar (Modern 2-tier Grid) -->
      <div class="bg-white rounded-2xl p-3.5 sm:p-4 border border-slate-200/80 shadow-sm mb-6 w-full min-w-0">
        <div class="grid grid-cols-1 sm:grid-cols-12 gap-2.5">
          <!-- Search Keyword -->
          <div class="sm:col-span-6 relative w-full min-w-0">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input type="text" id="search-student" placeholder="Search student name, ID number, subject, or reason keyword..." class="w-full pl-9 pr-3 py-2 sm:py-2.5 rounded-xl border border-slate-200 text-xs bg-slate-50/60 focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition" oninput="filterSlips()">
          </div>

          <!-- Status Filter -->
          <div class="sm:col-span-3 w-full min-w-0">
            <select id="filter-status" class="w-full px-3 py-2 sm:py-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50/60 hover:bg-white focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer truncate" onchange="filterSlips()">
              <option value="all">All Statuses (<?php echo $counts['total']; ?>)</option>
              <option value="pending" <?php echo $counts['pending'] > 0 ? 'selected' : ''; ?>>Pending Review (<?php echo $counts['pending']; ?>)</option>
              <option value="approved">Approved (<?php echo $counts['approved']; ?>)</option>
              <option value="declined">Declined (<?php echo $counts['declined']; ?>)</option>
            </select>
          </div>

          <!-- Section / Subject Filter -->
          <div class="sm:col-span-3 w-full min-w-0">
            <select id="filter-class" class="w-full px-3 py-2 sm:py-2.5 rounded-xl border border-slate-200 text-xs font-semibold bg-slate-50/60 hover:bg-white focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition cursor-pointer truncate" onchange="filterSlips()">
              <option value="all">All Assigned Classes (<?php echo count($slips); ?>)</option>
              <?php foreach ($uniqueSubjects as $subj): ?>
                <option value="<?php echo htmlspecialchars($subj); ?>"><?php echo htmlspecialchars($subj); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Submitted Excuse Slips Real Data Table -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
          <div class="flex items-center gap-2.5">
            <h2 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Excuse Slips Review Queue</h2>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200/70"><?php echo htmlspecialchars($currentTeacherName); ?></span>
          </div>
          <span class="text-xs text-slate-400">Showing <strong id="visible-count" class="text-slate-700 font-semibold"><?php echo count($slips); ?></strong> submissions</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50/70 text-slate-500 uppercase font-semibold border-b border-slate-100 text-[10px] tracking-wider">
              <tr>
                <th class="py-3 px-4 sm:px-5">Student</th>
                <th class="py-3 px-4 sm:px-5">Subject</th>
                <th class="py-3 px-4 sm:px-5">Absence Date</th>
                <th class="py-3 px-4 sm:px-5">Reason</th>
                <th class="py-3 px-4 sm:px-5">Status</th>
                <th class="py-3 px-4 sm:px-5 text-right">Action</th>
              </tr>
            </thead>
            <tbody id="slips-tbody" class="divide-y divide-slate-100 text-slate-700">
              
              <?php if (empty($slips)): ?>
                <tr id="empty-db-row">
                  <td colspan="6" class="py-12 text-center text-slate-400">
                    <svg class="w-9 h-9 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="font-medium text-slate-600 text-xs">No excuse slips found for <?php echo htmlspecialchars($currentTeacherName); ?></p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Submissions will appear here for your review.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($slips as $slip): 
                  $status = strtolower($slip['status'] ?? 'pending');
                  $isPending = ($status === 'pending');
                  $isApproved = ($status === 'approved');
                  $isDeclined = ($status === 'declined' || $status === 'rejected');

                  $statusBadge = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 border border-amber-200/70"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending</span>';
                  if ($isApproved) {
                      $statusBadge = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/70"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span>';
                  } elseif ($isDeclined) {
                      $statusBadge = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200/70"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Declined</span>';
                  }

                  $formattedAbsence = date('M d, Y', strtotime($slip['date_of_absence']));
                  $hasDoc = !empty($slip['supporting_document']);

                  // Parse course code
                  $subjectRaw = $slip['subject'] ?? '';
                  $courseCode = 'IT301';
                  if (stripos($subjectRaw, 'IT302') !== false) $courseCode = 'IT302';
                  elseif (stripos($subjectRaw, 'IT303') !== false) $courseCode = 'IT303';
                  
                  $searchTerms = strtolower($slip['student_name'] . ' ' . $slip['student_number'] . ' ' . $subjectRaw . ' ' . $slip['reason'] . ' ' . $slip['explanation']);
                ?>
                  <tr class="slip-row hover:bg-slate-50/60 transition cursor-pointer" 
                      id="slip-row-<?php echo $slip['excuse_slip_id']; ?>"
                      data-id="<?php echo $slip['excuse_slip_id']; ?>"
                      data-status="<?php echo $status; ?>" 
                      data-section="<?php echo $courseCode; ?>" 
                      data-text="<?php echo htmlspecialchars($searchTerms); ?>" 
                      onclick="openReviewModalFromRow(<?php echo htmlspecialchars(json_encode($slip)); ?>)">
                    
                    <!-- Student -->
                    <td class="py-3.5 px-4 sm:px-5 max-w-[130px] sm:max-w-[200px]">
                      <div class="font-semibold text-slate-800 text-xs truncate" title="<?php echo htmlspecialchars($slip['student_name'] ?: 'Student #' . $slip['student_id']); ?>"><?php echo htmlspecialchars($slip['student_name'] ?: 'Student #' . $slip['student_id']); ?></div>
                      <div class="text-[11px] font-mono text-slate-400 truncate"><?php echo htmlspecialchars($slip['student_number']); ?></div>
                    </td>

                    <!-- Subject -->
                    <td class="py-3.5 px-4 sm:px-5 max-w-[130px] sm:max-w-[220px]">
                      <div class="font-medium text-slate-700 text-xs truncate" title="<?php echo htmlspecialchars($slip['subject']); ?>"><?php echo htmlspecialchars($slip['subject']); ?></div>
                    </td>

                    <!-- Absence Date (Separate Column) -->
                    <td class="py-3.5 px-4 sm:px-5 whitespace-nowrap">
                      <div class="font-medium text-slate-800 text-xs"><?php echo $formattedAbsence; ?></div>
                    </td>

                    <!-- Reason -->
                    <td class="py-3.5 px-4 sm:px-5 max-w-[110px] sm:max-w-[160px]">
                      <div class="flex items-center gap-1.5 flex-nowrap min-w-0">
                        <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-600 border border-slate-200/60 truncate" title="<?php echo htmlspecialchars($slip['reason']); ?>">
                          <?php echo htmlspecialchars($slip['reason']); ?>
                        </span>
                        <?php if ($hasDoc): ?>
                          <span class="inline-flex items-center gap-1 text-[10px] font-medium text-indigo-600 bg-indigo-50/80 border border-indigo-100 px-1.5 py-0.5 rounded shrink-0" title="Document attached">
                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            <span>Doc</span>
                          </span>
                        <?php endif; ?>
                      </div>
                    </td>

                    <!-- Status Badge -->
                    <td class="py-3.5 px-4 sm:px-5 whitespace-nowrap" id="slip-status-col-<?php echo $slip['excuse_slip_id']; ?>">
                      <?php echo $statusBadge; ?>
                    </td>

                    <!-- Action Button -->
                    <td class="py-3.5 px-4 sm:px-5 text-right whitespace-nowrap" onclick="event.stopPropagation();">
                      <button type="button" 
                              onclick="openReviewModalFromRow(<?php echo htmlspecialchars(json_encode($slip)); ?>)" 
                              class="px-3.5 py-1.5 rounded-lg text-xs font-medium transition cursor-pointer <?php echo $isPending ? 'bg-slate-900 hover:bg-slate-800 text-white shadow-xs' : 'bg-slate-100 hover:bg-slate-200 text-slate-600'; ?>">
                        <?php echo $isPending ? 'Review' : 'View'; ?>
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
                <tr id="no-filter-results-row" class="hidden">
                  <td colspan="6" class="py-12 text-center text-slate-400">
                    <div class="w-10 h-10 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2.5">
                      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </div>
                    <p class="font-semibold text-slate-700 text-xs">No excuse slips match your filter</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Try adjusting your search keyword, status filter, or class selection.</p>
                    <button type="button" onclick="resetFilters()" class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition cursor-pointer">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                      <span>Reset Filters</span>
                    </button>
                  </td>
                </tr>
              <?php endif; ?>

            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL 1: EXCUSE SLIP REVIEW & VERIFICATION (MINIMALIST)
══════════════════════════════════════════════════════════════ -->
<div id="review-modal" class="fixed inset-0 z-50 bg-slate-950/50 backdrop-blur-xs hidden flex items-center justify-center p-3 sm:p-4" onclick="if(event.target === this) closeReviewModal()">
  <div class="bg-white rounded-2xl shadow-xl border border-slate-200/80 w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150 relative min-w-0" onclick="event.stopPropagation()">
    
    <!-- Loading Overlay for Approving / Declining -->
    <div id="review-modal-loading-overlay" class="hidden absolute inset-0 bg-white/90 backdrop-blur-xs flex flex-col items-center justify-center gap-3 z-30 transition-opacity duration-200">
      <div id="review-modal-loading-spinner" class="w-10 h-10 rounded-full border-2 border-emerald-200 border-t-emerald-600 animate-spin"></div>
      <div class="text-center px-4">
        <div id="review-modal-loading-title" class="text-xs font-bold text-slate-800">Processing Decision...</div>
        <div id="review-modal-loading-sub" class="text-[11px] text-slate-400 mt-0.5">Updating excuse clearance record...</div>
      </div>
    </div>
    
    <!-- Modal Header -->
    <div class="px-4 sm:px-5 py-3.5 sm:py-4 border-b border-slate-100 flex items-center justify-between bg-white shrink-0 min-w-0">
      <div class="flex items-center gap-2 sm:gap-2.5 min-w-0">
        <span id="modal-slip-id-badge" class="px-2 py-0.5 rounded text-[11px] font-mono font-medium bg-slate-100 text-slate-600 border border-slate-200/60 shrink-0">Slip #---</span>
        <span id="modal-status-badge" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/70 shrink-0">
          <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending
        </span>
      </div>
      <button type="button" onclick="closeReviewModal()" class="w-7 h-7 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center text-base transition cursor-pointer shrink-0">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-4 sm:p-5 overflow-y-auto space-y-4 flex-1 text-xs min-w-0">
      <input type="hidden" id="modal-slip-id" value="">

      <!-- Student Card -->
      <div class="flex items-center justify-between gap-2 pb-3 border-b border-slate-100 min-w-0">
        <div class="min-w-0 flex-1">
          <h2 id="modal-student-name" class="text-sm sm:text-base font-bold text-slate-900 leading-tight truncate">Student Name</h2>
          <div id="modal-student-id" class="text-[11px] font-mono text-slate-400 mt-0.5 truncate">2026-00123</div>
        </div>
        <div class="text-right shrink-0">
          <span id="modal-category-badge" class="px-2.5 py-1 rounded-lg text-[10px] sm:text-[11px] font-medium bg-slate-100 text-slate-700 border border-slate-200/70 inline-block max-w-[120px] sm:max-w-none truncate">Medical</span>
        </div>
      </div>

      <!-- Quick Meta Grid with Separate Dates -->
      <div class="grid grid-cols-3 gap-2 sm:gap-2.5 p-3 bg-slate-50/70 rounded-xl border border-slate-100 text-[11px] min-w-0">
        <div class="min-w-0">
          <span class="text-slate-400 block font-medium truncate">Subject</span>
          <span id="modal-course-name" class="font-semibold text-slate-800 truncate block mt-0.5">IT301</span>
        </div>
        <div class="min-w-0">
          <span class="text-slate-400 block font-medium truncate">Absence Date</span>
          <span id="modal-dates" class="font-semibold text-slate-800 block mt-0.5 truncate">Sep 11, 2026</span>
        </div>
        <div class="min-w-0">
          <span class="text-slate-400 block font-medium truncate">Submitted Date</span>
          <span id="modal-submitted-date" class="font-semibold text-slate-800 block mt-0.5 truncate">Sep 11, 2026</span>
        </div>
      </div>

      <!-- Explanation -->
      <div>
        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Student Explanation</label>
        <div id="modal-reason" class="p-3 bg-slate-50/50 border border-slate-200/70 rounded-xl text-slate-700 leading-relaxed text-xs break-words">
          Explanation details...
        </div>
      </div>

      <!-- Attached File Preview -->
      <div class="min-w-0">
        <label class="block text-[11px] font-semibold text-slate-500 mb-1">Attachment</label>
        <div id="modal-doc-container" class="p-2.5 bg-slate-50/80 border border-slate-200/70 rounded-xl flex items-center justify-between gap-2 min-w-0">
          <div class="flex items-center gap-2 text-slate-700 font-medium overflow-hidden min-w-0 flex-1">
            <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
            <span id="modal-file-name" class="truncate text-xs font-mono">document.pdf</span>
          </div>
          <button type="button" id="modal-preview-btn" onclick="previewCurrentModalDocument()" class="px-2.5 py-1 rounded-lg bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 font-medium text-[11px] shadow-2xs transition cursor-pointer shrink-0">
            View Attachment
          </button>
        </div>
        <div id="modal-no-doc" class="hidden p-2.5 bg-slate-50 border border-slate-100 rounded-xl text-slate-400 italic text-[11px]">
          No document attached
        </div>
      </div>

      <!-- Instructor Review Notes -->
      <div>
        <label for="review-decision-notes" class="block text-[11px] font-semibold text-slate-500 mb-1">Review Notes <span class="text-slate-400 font-normal">(Optional)</span></label>
        <textarea id="review-decision-notes" rows="2" placeholder="Add remarks or reason..." class="w-full p-2.5 rounded-xl border border-slate-200 text-xs bg-slate-50/50 focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-400/20 focus:border-slate-400 transition"></textarea>
      </div>
    </div>

    <!-- Modal Footer Actions (Approve/Decline on Left, Close on Right) -->
    <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-3 shrink-0">
      <!-- Left: Approve & Decline -->
      <div class="flex items-center gap-2">
        <button type="button" 
                id="modal-approve-btn" 
                onclick="submitReviewDecision('approve')" 
                class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                style="background-color: #059669; color: #ffffff;">
          <svg id="modal-approve-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <svg id="modal-approve-spinner" class="w-3.5 h-3.5 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <span id="modal-approve-text">Approve</span>
        </button>
        <button type="button" 
                id="modal-decline-btn" 
                onclick="submitReviewDecision('reject')" 
                class="px-4 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 active:bg-orange-700 text-white text-xs font-semibold shadow-xs transition flex items-center gap-1.5 cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                style="background-color: #f97316; color: #ffffff;">
          <svg id="modal-decline-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          <svg id="modal-decline-spinner" class="w-3.5 h-3.5 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <span id="modal-decline-text">Decline</span>
        </button>
      </div>

      <!-- Right: Close Button -->
      <button type="button" onclick="closeReviewModal()" class="px-4 py-2 rounded-xl bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold shadow-2xs transition cursor-pointer" style="background-color: #e2e8f0; color: #334155;">
        Close
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL 2: DOCUMENT / MEDICAL CERTIFICATE PREVIEW (MINIMALIST)
══════════════════════════════════════════════════════════════ -->
<div id="teacher-doc-modal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-xs hidden flex items-center justify-center p-4" onclick="if(event.target === this) closeTeacherDocModal()">
  <div class="bg-white rounded-2xl shadow-xl border border-slate-200 max-w-xl w-full overflow-hidden flex flex-col max-h-[85vh]" onclick="event.stopPropagation()">
    <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-white">
      <div class="flex items-center gap-2">
        <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        <h3 id="doc-modal-title" class="font-semibold text-slate-800 text-xs truncate max-w-[280px]">Supporting Document</h3>
      </div>
      <div class="flex items-center gap-2">
        <a id="doc-modal-open-btn" href="#" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1 text-[11px] font-medium text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition inline-flex items-center gap-1">
          <span>Open Full</span>
          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
        <button type="button" onclick="closeTeacherDocModal()" class="w-6 h-6 rounded-md hover:bg-slate-100 text-slate-400 hover:text-slate-700 flex items-center justify-center transition cursor-pointer">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
    </div>
    <div class="p-4 overflow-auto flex items-center justify-center bg-slate-50 min-h-[240px]">
      <img id="doc-modal-img" src="" alt="Document Preview" class="max-h-[60vh] w-auto max-w-full rounded-lg shadow-2xs border border-slate-200/80 object-contain">
    </div>
  </div>
</div>

<script>
var realSlips = <?php echo json_encode($slips); ?>;
var activeModalSlip = null;

function filterSlips() {
  const status = document.getElementById('filter-status').value.toLowerCase();
  const section = document.getElementById('filter-class').value;
  const query = document.getElementById('search-student').value.toLowerCase().trim();

  const rows = document.querySelectorAll('.slip-row');
  let visible = 0;

  rows.forEach(row => {
    const rStatus = (row.getAttribute('data-status') || '').toLowerCase();
    const rSection = row.getAttribute('data-section') || '';
    const rText = (row.getAttribute('data-text') || '').toLowerCase();

    const matchStatus = (status === 'all' || rStatus === status);
    const matchSection = (section === 'all' || rSection.includes(section));
    const matchQuery = (!query || rText.includes(query));

    if (matchStatus && matchSection && matchQuery) {
      row.style.display = '';
      visible++;
    } else {
      row.style.display = 'none';
    }
  });

  const countEl = document.getElementById('visible-count');
  if (countEl) countEl.textContent = visible;

  const noResultsRow = document.getElementById('no-filter-results-row');
  if (noResultsRow) {
    if (visible === 0 && rows.length > 0) {
      noResultsRow.classList.remove('hidden');
    } else {
      noResultsRow.classList.add('hidden');
    }
  }
}

function resetFilters() {
  const filterStatus = document.getElementById('filter-status');
  const filterClass = document.getElementById('filter-class');
  const searchInput = document.getElementById('search-student');

  if (filterStatus) filterStatus.value = 'all';
  if (filterClass) filterClass.value = 'all';
  if (searchInput) searchInput.value = '';

  filterSlips();
}

function setTeacherFilterStatus(statusVal) {
  const filterSelect = document.getElementById('filter-status');
  if (filterSelect) {
    filterSelect.value = statusVal;
  }
  filterSlips();
}

function updateKpiCounters() {
  if (!realSlips) return;
  const pending = realSlips.filter(s => s.status === 'pending').length;
  const approved = realSlips.filter(s => s.status === 'approved').length;
  const declined = realSlips.filter(s => s.status === 'declined' || s.status === 'rejected').length;
  const total = realSlips.length;

  const kpiPending = document.getElementById('kpi-pending');
  const kpiApproved = document.getElementById('kpi-approved');
  const kpiDeclined = document.getElementById('kpi-declined');
  const kpiTotal = document.getElementById('kpi-total');

  if (kpiPending) kpiPending.textContent = `${pending} Slips`;
  if (kpiApproved) kpiApproved.textContent = `${approved} Slips`;
  if (kpiDeclined) kpiDeclined.textContent = `${declined} Slips`;
  if (kpiTotal) kpiTotal.textContent = `${total} Slips`;

  const filterStatus = document.getElementById('filter-status');
  if (filterStatus) {
    const optAll = filterStatus.querySelector('option[value="all"]');
    const optPen = filterStatus.querySelector('option[value="pending"]');
    const optApp = filterStatus.querySelector('option[value="approved"]');
    const optDec = filterStatus.querySelector('option[value="declined"]');
    if (optAll) optAll.textContent = `All Statuses (${total})`;
    if (optPen) optPen.textContent = `Pending Review (${pending})`;
    if (optApp) optApp.textContent = `Approved (${approved})`;
    if (optDec) optDec.textContent = `Declined (${declined})`;
  }
}

function openReviewModalFromRow(slip) {
  activeModalSlip = slip;

  document.getElementById('modal-slip-id').value = slip.excuse_slip_id;
  document.getElementById('modal-slip-id-badge').textContent = `Slip #${slip.excuse_slip_id}`;
  document.getElementById('modal-student-name').textContent = slip.student_name || 'Student';
  document.getElementById('modal-student-id').textContent = slip.student_number || '---';
  document.getElementById('modal-course-name').textContent = slip.subject;
  
  const formattedAbsenceDate = slip.date_of_absence ? new Date(slip.date_of_absence + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : (slip.date_of_absence || '---');
  const formattedCreatedDate = slip.created_at ? new Date(slip.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : '---';

  const modalDates = document.getElementById('modal-dates');
  if (modalDates) modalDates.textContent = formattedAbsenceDate;

  const modalSubmittedDate = document.getElementById('modal-submitted-date');
  if (modalSubmittedDate) modalSubmittedDate.textContent = formattedCreatedDate;

  const catBadge = document.getElementById('modal-category-badge');
  if (catBadge) catBadge.textContent = slip.reason || 'General';

  document.getElementById('modal-reason').textContent = slip.explanation;

  const statusBadge = document.getElementById('modal-status-badge');
  const status = (slip.status || 'pending').toLowerCase();
  const isPending = (status === 'pending');

  if (status === 'approved') {
    statusBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/70';
    statusBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved';
  } else if (status === 'declined' || status === 'rejected') {
    statusBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200/70';
    statusBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Declined';
  } else {
    statusBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200/70';
    statusBadge.innerHTML = '<span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Pending';
  }

  // Handle Document
  const docContainer = document.getElementById('modal-doc-container');
  const noDocContainer = document.getElementById('modal-no-doc');
  const fileNameEl = document.getElementById('modal-file-name');

  if (slip.supporting_document) {
    docContainer.classList.remove('hidden');
    noDocContainer.classList.add('hidden');
    fileNameEl.textContent = slip.supporting_document.split('/').pop() || 'certificate.png';
  } else {
    docContainer.classList.add('hidden');
    noDocContainer.classList.remove('hidden');
  }

  document.getElementById('review-decision-notes').value = slip.declined_reason || '';

  // Reset loading overlay & button states
  const loadingOverlay = document.getElementById('review-modal-loading-overlay');
  if (loadingOverlay) loadingOverlay.classList.add('hidden');

  const approveBtn = document.getElementById('modal-approve-btn');
  const declineBtn = document.getElementById('modal-decline-btn');
  const approveText = document.getElementById('modal-approve-text');
  const declineText = document.getElementById('modal-decline-text');
  const approveSpinner = document.getElementById('modal-approve-spinner');
  const declineSpinner = document.getElementById('modal-decline-spinner');
  const approveIcon = document.getElementById('modal-approve-icon');
  const declineIcon = document.getElementById('modal-decline-icon');

  if (approveBtn) approveBtn.disabled = !isPending;
  if (declineBtn) declineBtn.disabled = !isPending;
  if (approveText) approveText.textContent = isPending ? 'Approve' : 'Already Approved';
  if (declineText) declineText.textContent = isPending ? 'Decline' : 'Already Declined';
  if (approveSpinner) approveSpinner.classList.add('hidden');
  if (declineSpinner) declineSpinner.classList.add('hidden');
  if (approveIcon) approveIcon.classList.remove('hidden');
  if (declineIcon) declineIcon.classList.remove('hidden');

  document.getElementById('review-modal').classList.remove('hidden');
}

function closeReviewModal() {
  document.getElementById('review-modal').classList.add('hidden');
  const loadingOverlay = document.getElementById('review-modal-loading-overlay');
  if (loadingOverlay) loadingOverlay.classList.add('hidden');
  activeModalSlip = null;
}

function previewCurrentModalDocument() {
  if (activeModalSlip && activeModalSlip.supporting_document) {
    previewDocument(activeModalSlip.supporting_document, `Slip #${activeModalSlip.excuse_slip_id} - ${activeModalSlip.student_name}`);
  }
}

function previewDocument(url, title = 'Supporting Medical Certificate') {
  const modal = document.getElementById('teacher-doc-modal');
  const img = document.getElementById('doc-modal-img');
  const titleEl = document.getElementById('doc-modal-title');
  const openBtn = document.getElementById('doc-modal-open-btn');

  titleEl.textContent = title;
  openBtn.href = url;
  img.src = url;

  modal.classList.remove('hidden');
}

function closeTeacherDocModal() {
  document.getElementById('teacher-doc-modal').classList.add('hidden');
}

async function submitReviewDecision(action) {
  if (!activeModalSlip) return;

  const slipId = activeModalSlip.excuse_slip_id;
  const studentName = activeModalSlip.student_name || 'Student';
  const notes = document.getElementById('review-decision-notes').value.trim();
  const approveBtn = document.getElementById('modal-approve-btn');
  const declineBtn = document.getElementById('modal-decline-btn');
  const approveText = document.getElementById('modal-approve-text');
  const declineText = document.getElementById('modal-decline-text');
  const approveSpinner = document.getElementById('modal-approve-spinner');
  const declineSpinner = document.getElementById('modal-decline-spinner');
  const approveIcon = document.getElementById('modal-approve-icon');
  const declineIcon = document.getElementById('modal-decline-icon');
  const loadingOverlay = document.getElementById('review-modal-loading-overlay');
  const loadingTitle = document.getElementById('review-modal-loading-title');
  const loadingSub = document.getElementById('review-modal-loading-sub');
  const loadingSpinner = document.getElementById('review-modal-loading-spinner');

  const isApprove = action === 'approve';

  // Confirmation prompt via universal modal
  const confirmed = await APP.confirm({
    title: isApprove ? 'Approve Absence Clearance?' : 'Decline Excuse Slip?',
    message: isApprove
      ? `Approve absence clearance for <strong class="text-slate-800">${studentName}</strong>?<br><span class="text-xs text-slate-400 mt-1 block">The student attendance record will be marked as Excused.</span>`
      : `Decline excuse slip for <strong class="text-slate-800">${studentName}</strong>?<br><span class="text-xs text-slate-400 mt-1 block">Absence will remain unexcused.</span>`,
    type: isApprove ? 'success' : 'danger',
    confirmText: isApprove ? 'Approve Slip' : 'Decline Slip',
    cancelText: 'Cancel'
  });

  if (!confirmed) return;

  // 1. Button Loading State
  if (approveBtn) approveBtn.disabled = true;
  if (declineBtn) declineBtn.disabled = true;

  if (isApprove) {
    if (approveIcon) approveIcon.classList.add('hidden');
    if (approveSpinner) approveSpinner.classList.remove('hidden');
    if (approveText) approveText.textContent = 'Approving...';
  } else {
    if (declineIcon) declineIcon.classList.add('hidden');
    if (declineSpinner) declineSpinner.classList.remove('hidden');
    if (declineText) declineText.textContent = 'Declining...';
  }

  // 2. Review Modal Loading Overlay
  if (loadingOverlay) {
    if (loadingSpinner) {
      loadingSpinner.className = isApprove
        ? 'w-12 h-12 rounded-full border-3 border-emerald-200 border-t-emerald-600 animate-spin shadow-sm'
        : 'w-12 h-12 rounded-full border-3 border-orange-200 border-t-orange-600 animate-spin shadow-sm';
    }
    if (loadingTitle) {
      loadingTitle.textContent = isApprove ? 'Approving Absence Clearance...' : 'Declining Excuse Slip...';
    }
    if (loadingSub) {
      loadingSub.textContent = `Saving decision for ${studentName} to database...`;
    }
    loadingOverlay.classList.remove('hidden');
  }

  try {
    const endpoint = window.url ? window.url('api/excuses/review') : '/api/excuses/review';
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        excuse_slip_id: slipId,
        action: action,
        notes: notes
      })
    });
    const json = await res.json();

    if (!res.ok || json.status !== 'success') {
      throw new Error(json.message || 'Failed to submit review decision.');
    }

    const newStatus = isApprove ? 'approved' : 'declined';

    // Update in-memory data
    const slipIdx = realSlips.findIndex(s => s.excuse_slip_id == slipId);
    if (slipIdx !== -1) {
      realSlips[slipIdx].status = newStatus;
      realSlips[slipIdx].declined_reason = notes;
    }

    // Update live KPI counters and filter options
    updateKpiCounters();

    // Update row in DOM
    const row = document.getElementById(`slip-row-${slipId}`);
    const statusCol = document.getElementById(`slip-status-col-${slipId}`);

    if (row) {
      row.setAttribute('data-status', newStatus);
      const actionBtn = row.querySelector('button[onclick*="openReviewModalFromRow"]');
      if (actionBtn) {
        actionBtn.className = 'px-3.5 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-600 transition cursor-pointer';
        actionBtn.textContent = 'View';
        if (slipIdx !== -1) {
          actionBtn.setAttribute('onclick', `openReviewModalFromRow(${JSON.stringify(realSlips[slipIdx]).replace(/"/g, '&quot;')})`);
        }
      }

      // Smooth visual highlight flash effect on table row
      const flashClass = isApprove ? 'bg-emerald-50/70' : 'bg-rose-50/70';
      row.classList.add(flashClass, 'transition-colors', 'duration-300');
      setTimeout(() => {
        row.classList.remove(flashClass);
      }, 2000);
    }

    if (statusCol) {
      statusCol.innerHTML = isApprove
        ? '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 border border-emerald-200/70"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved</span>'
        : '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-50 text-rose-700 border border-rose-200/70"><span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Declined</span>';
    }

    closeReviewModal();
    APP.toast(`Excuse slip #${slipId} (${studentName}) has been ${newStatus}.`, 'success');

  } catch (err) {
    console.error('Review decision error:', err);
    APP.toast(err.message || 'Error updating excuse slip.', 'error');
  } finally {
    if (loadingOverlay) loadingOverlay.classList.add('hidden');
    if (approveBtn) approveBtn.disabled = false;
    if (declineBtn) declineBtn.disabled = false;
    if (approveText) approveText.textContent = 'Approve';
    if (declineText) declineText.textContent = 'Decline';
    if (approveSpinner) approveSpinner.classList.add('hidden');
    if (declineSpinner) declineSpinner.classList.add('hidden');
    if (approveIcon) approveIcon.classList.remove('hidden');
    if (declineIcon) declineIcon.classList.remove('hidden');
  }
}

function exportSlipsToCSV() {
  if (!realSlips || realSlips.length === 0) {
    APP.toast('No excuse slip records available to export.', 'warning');
    return;
  }

  const headers = ['Slip ID', 'Student ID', 'Student Name', 'Subject', 'Date of Absence', 'Reason Category', 'Explanation', 'Status', 'Supporting Document', 'Submitted At'];
  const rows = realSlips.map(s => [
    s.excuse_slip_id,
    s.student_number || s.student_id,
    `"${(s.student_name || '').replace(/"/g, '""')}"`,
    `"${(s.subject || '').replace(/"/g, '""')}"`,
    s.date_of_absence,
    `"${(s.reason || '').replace(/"/g, '""')}"`,
    `"${(s.explanation || '').replace(/"/g, '""')}"`,
    s.status,
    s.supporting_document || 'None',
    s.created_at
  ]);

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(r => r.join(','))].join('\n');
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `excuse_slips_review_${new Date().toISOString().split('T')[0]}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  APP.toast('Exported real excuse slips to CSV successfully.', 'success');
}

// Initialize filters on load
document.addEventListener('DOMContentLoaded', () => {
  filterSlips();
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
