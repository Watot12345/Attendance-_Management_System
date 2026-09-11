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

      <!-- KPI Summary Cards (Real Database Counts for This Specific Teacher) -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-2xs text-center transition hover:shadow-sm">
          <div class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Pending Review</div>
          <div id="kpi-pending" class="text-2xl font-bold text-amber-600 mt-1"><?php echo $counts['pending']; ?> Slips</div>
          <div class="text-[11px] text-slate-400 mt-0.5">Requires Decision</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-2xs text-center transition hover:shadow-sm">
          <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Approved Slips</div>
          <div id="kpi-approved" class="text-2xl font-bold text-emerald-600 mt-1"><?php echo $counts['approved']; ?> Slips</div>
          <div class="text-[11px] text-slate-400 mt-0.5">Absences Excused</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-2xs text-center transition hover:shadow-sm">
          <div class="text-xs font-semibold text-rose-600 uppercase tracking-wider">Declined Slips</div>
          <div id="kpi-declined" class="text-2xl font-bold text-rose-600 mt-1"><?php echo $counts['declined']; ?> Slips</div>
          <div class="text-[11px] text-slate-400 mt-0.5">Unexcused Absences</div>
        </div>

        <div class="bg-white rounded-xl p-4 border border-slate-200/80 shadow-2xs text-center transition hover:shadow-sm">
          <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Received</div>
          <div id="kpi-total" class="text-2xl font-bold text-slate-800 mt-1"><?php echo $counts['total']; ?> Slips</div>
          <div class="text-[11px] text-slate-400 mt-0.5">Assigned Classes</div>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-2xs mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-3 md:grid-cols-4 gap-3">
          <!-- Status Filter -->
          <div>
            <label for="filter-status" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Status</label>
            <select id="filter-status" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500" onchange="filterSlips()">
              <option value="all">All Statuses (<?php echo $counts['total']; ?>)</option>
              <option value="pending" <?php echo $counts['pending'] > 0 ? 'selected' : ''; ?>>Pending Review (<?php echo $counts['pending']; ?>)</option>
              <option value="approved">Approved (<?php echo $counts['approved']; ?>)</option>
              <option value="declined">Declined (<?php echo $counts['declined']; ?>)</option>
            </select>
          </div>

          <!-- Section / Subject Filter -->
          <div>
            <label for="filter-class" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Class &amp; Subject</label>
            <select id="filter-class" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500" onchange="filterSlips()">
              <option value="all">All Assigned Classes (<?php echo count($slips); ?>)</option>
              <?php foreach ($uniqueSubjects as $subj): ?>
                <option value="<?php echo htmlspecialchars($subj); ?>"><?php echo htmlspecialchars($subj); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Search Keyword -->
          <div class="sm:col-span-1 md:col-span-2">
            <label for="search-student" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Search Student, ID, or Reason</label>
            <div class="relative">
              <input type="text" id="search-student" placeholder="Search by student name, ID number, subject, or reason keyword..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500" oninput="filterSlips()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Submitted Excuse Slips Real Data Table -->
      <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-6">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
          <div class="flex items-center gap-2">
            <h2 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Excuse Slips Review Queue</h2>
            <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-indigo-50 text-indigo-700 border border-indigo-100"><?php echo htmlspecialchars($currentTeacherName); ?></span>
          </div>
          <span class="text-xs text-slate-500">Showing <strong id="visible-count" class="text-slate-800"><?php echo count($slips); ?></strong> excuse slip submissions</span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200 text-[11px]">
              <tr>
                <th class="py-3 px-4">Student</th>
                <th class="py-3 px-4">Section &amp; Subject</th>
                <th class="py-3 px-4">Absence Date</th>
                <th class="py-3 px-4">Reason &amp; Documentation</th>
                <th class="py-3 px-4">Submission Date</th>
                <th class="py-3 px-4">Status</th>
                <th class="py-3 px-4 text-right">Action</th>
              </tr>
            </thead>
            <tbody id="slips-tbody" class="divide-y divide-slate-100 text-slate-700">
              
              <?php if (empty($slips)): ?>
                <tr id="empty-db-row">
                  <td colspan="7" class="py-12 text-center text-slate-400">
                    <svg class="w-10 h-10 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <p class="font-semibold text-slate-600 text-sm">No excuse slips found for <?php echo htmlspecialchars($currentTeacherName); ?></p>
                    <p class="text-xs text-slate-400 mt-1">Student excuse submissions assigned to your classes will appear here for review.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($slips as $slip): 
                  $status = strtolower($slip['status'] ?? 'pending');
                  $isPending = ($status === 'pending');
                  $isApproved = ($status === 'approved');
                  $isDeclined = ($status === 'declined' || $status === 'rejected');

                  $statusBadge = '<span class="badge badge-pending font-bold text-amber-800 bg-amber-50 border-amber-200"> Pending</span>';
                  if ($isApproved) {
                      $statusBadge = '<span class="badge badge-present font-bold text-emerald-800 bg-emerald-50 border-emerald-200"> Approved</span>';
                  } elseif ($isDeclined) {
                      $statusBadge = '<span class="badge badge-absent font-bold text-rose-800 bg-rose-50 border-rose-200"> Declined</span>';
                  }

                  $formattedAbsence = date('M d, Y', strtotime($slip['date_of_absence']));
                  $formattedCreated = date('M d, Y • h:i A', strtotime($slip['created_at']));
                  $hasDoc = !empty($slip['supporting_document']);
                  $docUrl = $hasDoc ? htmlspecialchars($slip['supporting_document']) : '';
                  $docFileName = $hasDoc ? basename(parse_url($slip['supporting_document'], PHP_URL_PATH)) : '';

                  // Parse course code
                  $subjectRaw = $slip['subject'] ?? '';
                  $courseCode = 'IT301';
                  if (stripos($subjectRaw, 'IT302') !== false) $courseCode = 'IT302';
                  elseif (stripos($subjectRaw, 'IT303') !== false) $courseCode = 'IT303';
                  
                  $searchTerms = strtolower($slip['student_name'] . ' ' . $slip['student_number'] . ' ' . $subjectRaw . ' ' . $slip['reason'] . ' ' . $slip['explanation']);
                ?>
                  <tr class="slip-row hover:bg-slate-50/80 transition cursor-pointer" 
                      id="slip-row-<?php echo $slip['excuse_slip_id']; ?>"
                      data-id="<?php echo $slip['excuse_slip_id']; ?>"
                      data-status="<?php echo $status; ?>" 
                      data-section="<?php echo $courseCode; ?>" 
                      data-text="<?php echo htmlspecialchars($searchTerms); ?>" 
                      onclick="openReviewModalFromRow(<?php echo htmlspecialchars(json_encode($slip)); ?>)">
                    
                    <!-- Student -->
                    <td class="py-3 px-4">
                      <div class="font-bold text-slate-800"><?php echo htmlspecialchars($slip['student_name'] ?: 'Student #' . $slip['student_id']); ?></div>
                      <div class="text-[10px] font-mono text-indigo-600 font-semibold"><?php echo htmlspecialchars($slip['student_number']); ?></div>
                    </td>

                    <!-- Section & Subject -->
                    <td class="py-3 px-4">
                      <div class="font-semibold text-slate-700 leading-snug"><?php echo htmlspecialchars($slip['subject']); ?></div>
                      <div class="text-[10px] text-slate-400 mt-0.5">Instructor: <?php echo htmlspecialchars($slip['teacher_name'] ?: 'Faculty'); ?></div>
                    </td>

                    <!-- Absence Date -->
                    <td class="py-3 px-4 font-medium text-slate-700">
                      <div><?php echo $formattedAbsence; ?></div>
                      <span class="inline-block mt-0.5 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                        <?php echo htmlspecialchars($slip['reason']); ?>
                      </span>
                    </td>

                    <!-- Reason & Document -->
                    <td class="py-3 px-4 max-w-xs">
                      <div class="truncate text-slate-800 font-medium" title="<?php echo htmlspecialchars($slip['explanation']); ?>">
                        "<?php echo htmlspecialchars($slip['explanation']); ?>"
                      </div>
                      <?php if ($hasDoc): ?>
                        <button type="button" 
                                onclick="event.stopPropagation(); previewDocument('<?php echo $docUrl; ?>', 'Slip #<?php echo $slip['excuse_slip_id']; ?> - <?php echo htmlspecialchars(addslashes($slip['student_name'])); ?>')" 
                                class="inline-flex items-center gap-1.5 text-[10px] font-semibold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-2 py-0.5 rounded mt-1 transition cursor-pointer">
                          <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                          <span>View Doc</span>
                        </button>
                      <?php else: ?>
                        <span class="text-[10px] text-slate-400 italic block mt-0.5">No document attached</span>
                      <?php endif; ?>
                    </td>

                    <!-- Submission Date -->
                    <td class="py-3 px-4">
                      <div class="font-medium text-slate-700"><?php echo $formattedCreated; ?></div>
                      <div class="text-[10px] font-mono text-slate-400">Slip #<?php echo $slip['excuse_slip_id']; ?></div>
                    </td>

                    <!-- Status Badge -->
                    <td class="py-3 px-4" id="slip-status-col-<?php echo $slip['excuse_slip_id']; ?>">
                      <?php echo $statusBadge; ?>
                    </td>

                    <!-- Action Button -->
                    <td class="py-3 px-4 text-right" onclick="event.stopPropagation();">
                      <button type="button" 
                              onclick="openReviewModalFromRow(<?php echo htmlspecialchars(json_encode($slip)); ?>)" 
                              class="px-3 py-1.5 rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer <?php echo $isPending ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200'; ?>">
                        <?php echo $isPending ? 'Review' : 'Details'; ?>
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

<!-- ══════════════════════════════════════════════════════════════
     MODAL 1: EXCUSE SLIP REVIEW & VERIFICATION
══════════════════════════════════════════════════════════════ -->
<div id="review-modal" class="fixed inset-0 z-50 bg-slate-950/60 backdrop-blur-sm hidden flex items-center justify-center p-4" onclick="if(event.target === this) closeReviewModal()">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-2xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150 relative" onclick="event.stopPropagation()">
    
    <!-- Loading Overlay for Approving / Declining -->
    <div id="review-modal-loading-overlay" class="hidden absolute inset-0 bg-white/90 backdrop-blur-xs flex flex-col items-center justify-center gap-3 z-30 transition-opacity duration-200">
      <div id="review-modal-loading-spinner" class="w-12 h-12 rounded-full border-3 border-emerald-200 border-t-emerald-600 animate-spin shadow-sm"></div>
      <div class="text-center px-4">
        <div id="review-modal-loading-title" class="text-sm font-bold text-slate-800">Processing Review Decision...</div>
        <div id="review-modal-loading-sub" class="text-xs text-slate-500 mt-1 font-medium">Communicating with server and updating clearance...</div>
      </div>
    </div>
    
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span id="modal-status-badge" class="badge badge-pending font-bold text-xs"> Pending Review</span>
          <span id="modal-slip-id-badge" class="px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-100 text-indigo-800 font-mono">Slip #---</span>
        </div>
        <h2 id="modal-student-name" class="text-lg font-bold text-slate-800">Student Name</h2>
      </div>
      <button type="button" onclick="closeReviewModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center font-bold text-sm transition cursor-pointer">
        
      </button>
    </div>

    <!-- Modal Body -->
    <div class="p-6 overflow-y-auto space-y-4 flex-1 text-xs">
      <input type="hidden" id="modal-slip-id" value="">

      <div class="grid grid-cols-2 gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
        <div>
          <span class="text-slate-400 font-semibold block uppercase text-[10px]">Student ID Number</span>
          <span id="modal-student-id" class="font-mono font-bold text-indigo-700 text-sm">2026-00123</span>
        </div>
        <div>
          <span class="text-slate-400 font-semibold block uppercase text-[10px]">Enrolled Course / Subject</span>
          <span id="modal-course-name" class="font-bold text-slate-800 text-sm">IT301 — Web Development 2</span>
        </div>
        <div>
          <span class="text-slate-400 font-semibold block uppercase text-[10px]">Date of Absence</span>
          <span id="modal-dates" class="font-bold text-slate-800">Sep 11, 2026</span>
        </div>
        <div>
          <span class="text-slate-400 font-semibold block uppercase text-[10px]">Reason Category</span>
          <span id="modal-category" class="font-bold text-slate-800">Medical / Illness</span>
        </div>
      </div>

      <!-- Detailed Reason / Explanation -->
      <div>
        <label class="block font-semibold text-slate-700 mb-1">Stated Explanation from Student:</label>
        <div id="modal-reason" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-slate-800 leading-relaxed font-medium">
          Explanation details...
        </div>
      </div>

      <!-- Attached File Preview Card -->
      <div>
        <label class="block font-semibold text-slate-700 mb-1">Supporting Document (Supabase Cloud Storage):</label>
        <div id="modal-doc-container" class="p-3.5 bg-indigo-50/50 border border-indigo-100 rounded-xl flex items-center justify-between">
          <div class="flex items-center gap-2 text-indigo-900 font-semibold overflow-hidden">
            <svg class="w-5 h-5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span id="modal-file-name" class="truncate max-w-sm">document.pdf</span>
          </div>
          <button type="button" id="modal-preview-btn" onclick="previewCurrentModalDocument()" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow-2xs transition cursor-pointer">
            Preview File
          </button>
        </div>
        <div id="modal-no-doc" class="hidden p-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-400 italic">
          No document attached with this request.
        </div>
      </div>

      <!-- Instructor Review Notes / Declined Reason -->
      <div>
        <label for="review-decision-notes" class="block font-semibold text-slate-700 mb-1">Instructor Review Notes / Comments:</label>
        <textarea id="review-decision-notes" rows="2" placeholder="e.g. Valid medical certificate verified. Absences excused in gradebook." class="w-full p-2.5 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <div class="px-6 py-4 border-t border-slate-200 bg-slate-50 flex items-center justify-between gap-3 shrink-0">
      <button type="button" onclick="closeReviewModal()" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition cursor-pointer">
        Close
      </button>

      <div class="flex items-center gap-2">
        <button type="button" 
                id="modal-decline-btn" 
                onclick="submitReviewDecision('reject')" 
                class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
          <svg id="modal-decline-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          <svg id="modal-decline-spinner" class="w-3.5 h-3.5 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <span id="modal-decline-text">Decline / Reject</span>
        </button>
        <button type="button" 
                id="modal-approve-btn" 
                onclick="submitReviewDecision('approve')" 
                class="px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
          <svg id="modal-approve-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <svg id="modal-approve-spinner" class="w-3.5 h-3.5 animate-spin hidden" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <span id="modal-approve-text">Approve &amp; Excuse</span>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL 2: DOCUMENT / MEDICAL CERTIFICATE PREVIEW
══════════════════════════════════════════════════════════════ -->
<div id="teacher-doc-modal" class="fixed inset-0 z-50 bg-slate-950/70 backdrop-blur-sm hidden flex items-center justify-center p-4" onclick="if(event.target === this) closeTeacherDocModal()">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-2xl w-full overflow-hidden flex flex-col max-h-[90vh]" onclick="event.stopPropagation()">
    <div class="px-5 py-3.5 border-b border-slate-100 flex items-center justify-between bg-slate-50">
      <div class="flex items-center gap-2">
        <div class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <div>
          <h3 id="doc-modal-title" class="font-bold text-slate-800 text-sm">Supporting Medical Certificate</h3>
          <span class="text-[10px] text-slate-400 font-mono">Supabase Storage</span>
        </div>
      </div>
      <div class="flex items-center gap-2">
        <a id="doc-modal-open-btn" href="#" target="_blank" rel="noopener noreferrer" class="px-2.5 py-1 text-xs font-semibold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg transition inline-flex items-center gap-1">
          <span>Open Full</span>
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
        </a>
        <button type="button" onclick="closeTeacherDocModal()" class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs transition cursor-pointer">
          
        </button>
      </div>
    </div>
    <div class="p-5 overflow-auto flex items-center justify-center bg-slate-900/5 min-h-[280px]">
      <img id="doc-modal-img" src="" alt="Document Preview" class="max-h-[65vh] w-auto max-w-full rounded-lg shadow-sm border border-slate-200 object-contain">
    </div>
    <div class="px-5 py-3 border-t border-slate-100 bg-slate-50 flex items-center justify-end">
      <button type="button" onclick="closeTeacherDocModal()" class="px-4 py-1.5 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition cursor-pointer">
        Close Preview
      </button>
    </div>
  </div>
</div>

<script>
let realSlips = <?php echo json_encode($slips); ?>;
let activeModalSlip = null;

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
  document.getElementById('modal-dates').textContent = slip.date_of_absence;
  document.getElementById('modal-category').textContent = slip.reason;
  document.getElementById('modal-reason').textContent = slip.explanation;

  const statusBadge = document.getElementById('modal-status-badge');
  const status = (slip.status || 'pending').toLowerCase();
  const isPending = (status === 'pending');

  if (status === 'approved') {
    statusBadge.className = 'badge badge-present font-bold text-xs';
    statusBadge.textContent = ' Approved';
  } else if (status === 'declined' || status === 'rejected') {
    statusBadge.className = 'badge badge-absent font-bold text-xs';
    statusBadge.textContent = ' Declined';
  } else {
    statusBadge.className = 'badge badge-pending font-bold text-xs';
    statusBadge.textContent = ' Pending Review';
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
  if (approveText) approveText.textContent = isPending ? 'Approve & Excuse' : 'Already Approved';
  if (declineText) declineText.textContent = isPending ? 'Decline / Reject' : 'Already Declined';
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
        : 'w-12 h-12 rounded-full border-3 border-rose-200 border-t-rose-600 animate-spin shadow-sm';
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
        actionBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-semibold shadow-2xs transition cursor-pointer bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200';
        actionBtn.textContent = 'Details';
        if (slipIdx !== -1) {
          actionBtn.setAttribute('onclick', `openReviewModalFromRow(${JSON.stringify(realSlips[slipIdx]).replace(/"/g, '&quot;')})`);
        }
      }

      // Smooth visual highlight flash effect on table row
      const flashClass = isApprove ? 'bg-emerald-50' : 'bg-rose-50';
      row.classList.add(flashClass, 'transition-colors', 'duration-300');
      setTimeout(() => {
        row.classList.remove(flashClass);
      }, 2000);
    }

    if (statusCol) {
      statusCol.innerHTML = isApprove
        ? '<span class="badge badge-present font-bold text-emerald-800 bg-emerald-50 border-emerald-200"> Approved</span>'
        : '<span class="badge badge-absent font-bold text-rose-800 bg-rose-50 border-rose-200"> Declined</span>';
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
    if (approveText) approveText.textContent = 'Approve & Excuse';
    if (declineText) declineText.textContent = 'Decline / Reject';
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
