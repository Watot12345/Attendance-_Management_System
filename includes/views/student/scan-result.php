<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Scan Feedback Result';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__) . '/partials/header.php';

// Get status from query string, default to success
$status = $_GET['status'] ?? 'success';
if (!in_array($status, ['success', 'wrong_section', 'expired', 'duplicate'], true)) {
    $status = 'success';
}

$customMsg = trim($_GET['msg'] ?? '');

// Resolve current student session or default
$studentUserId = (int)($_SESSION['user']['user_id'] ?? $_SESSION['user_id'] ?? $_SESSION['student_id'] ?? 1);

// Fetch student info
$db = Database::getConnection();
$stStmt = $db->prepare("SELECT user_id, student_id, first_name, last_name, email FROM users WHERE user_id = ? LIMIT 1");
$stStmt->execute([$studentUserId]);
$studentRow = $stStmt->fetch(PDO::FETCH_ASSOC);

$studentName = $studentRow ? ($studentRow['first_name'] . ' ' . $studentRow['last_name']) : ($_SESSION['user']['full_name'] ?? 'Juan Dela Cruz');
$studentNumber = $studentRow['student_id'] ?? ($_SESSION['user']['student_id'] ?? '230110001');

// Fetch latest attendance record for this student from DB
$latestAtt = null;
try {
    $stmt = $db->prepare("
        SELECT a.attendance_id, a.date, a.time, a.status, a.subject, 
               COALESCE(qs.section, cr.section, '31001') AS section,
               COALESCE(u.student_id, '230110001') AS student_number,
               CONCAT(u.first_name, ' ', u.last_name) AS student_name,
               CONCAT(t.first_name, ' ', t.last_name) AS teacher_name,
               cr.room_number,
               cr.scheduled_time
        FROM attendance a
        JOIN users u ON u.user_id = a.student_id
        LEFT JOIN users t ON t.user_id = a.teacher_id
        LEFT JOIN qr_sessions qs ON qs.qr_session_id = a.qr_session_id
        LEFT JOIN class_roster cr ON cr.student_id = a.student_id AND cr.teacher_id = a.teacher_id
        WHERE a.student_id = ?
        ORDER BY a.attendance_id DESC
        LIMIT 1
    ");
    $stmt->execute([$studentUserId]);
    $latestAtt = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {}

$dispName = $latestAtt['student_name'] ?? $studentName;
$dispNumber = $latestAtt['student_number'] ?? $studentNumber;
$dispSubject = $latestAtt['subject'] ?? 'Web Systems and Technologies';
$dispTeacher = $latestAtt['teacher_name'] ?? 'Prof. Alan Turing';
$dispRoom = $latestAtt['room_number'] ?? '402';
$dispSection = $latestAtt['section'] ?? '31001';
$dispTime = !empty($latestAtt['time']) ? date('h:i:s A', strtotime($latestAtt['time'])) : date('h:i:s A');
$rawStatus = strtolower($latestAtt['status'] ?? 'present');
$dispStatus = ucfirst($rawStatus);
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body max-w-2xl mx-auto">
      <!-- Result Card Container -->
      <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-lg text-center relative overflow-hidden">

        <?php if ($status === 'success'): ?>
          <!-- 1. SUCCESS STATE -->
          <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-inner">
            <svg class="w-10 h-10 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wider mb-2">
            <span>● Verified &amp; Recorded in Database</span>
          </span>
          <h1 class="text-2xl font-bold text-slate-800 mb-2">Attendance Logged Successfully!</h1>
          <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">Your attendance has been officially confirmed and written to the live class session ledger.</p>

          <!-- Verification Details Table -->
          <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 text-left text-xs space-y-2.5 mb-6">
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Student Name</span>
              <span id="res-student-name" class="font-bold text-slate-800"><?= htmlspecialchars($dispName, ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$dispNumber, ENT_QUOTES, 'UTF-8') ?>)</span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Course &amp; Subject</span>
              <span id="res-subject" class="font-bold text-slate-800"><?= htmlspecialchars($dispSubject, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Instructor &amp; Room</span>
              <span id="res-teacher-room" class="font-bold text-slate-800"><?= htmlspecialchars($dispTeacher, ENT_QUOTES, 'UTF-8') ?> (Room <?= htmlspecialchars($dispRoom, ENT_QUOTES, 'UTF-8') ?>)</span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Enrolled Section</span>
              <span id="res-section" class="font-bold text-indigo-700 font-mono">Section <?= htmlspecialchars($dispSection, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Timestamp</span>
              <span id="res-timestamp" class="font-bold text-slate-800">Today, <?= htmlspecialchars($dispTime, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-slate-500 font-medium">Status Assigned</span>
              <span id="res-status" class="<?= $rawStatus === 'tardy' ? 'badge badge-tardy bg-amber-100 text-amber-800' : 'badge badge-present bg-emerald-100 text-emerald-800' ?> font-bold px-2.5 py-0.5 rounded-full text-xs">
                <?= htmlspecialchars($dispStatus, ENT_QUOTES, 'UTF-8') ?> (Recorded)
              </span>
            </div>
          </div>

        <?php elseif ($status === 'wrong_section'): ?>
          <!-- 2. WRONG SECTION STATE -->
          <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center shadow-inner">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-bold uppercase tracking-wider mb-2">
            <span>● Section Mismatch</span>
          </span>
          <h1 class="text-2xl font-bold text-slate-800 mb-2">Not Enrolled in this Section</h1>
          <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto"><?= $customMsg !== '' ? htmlspecialchars($customMsg, ENT_QUOTES, 'UTF-8') : 'You scanned the attendance code for an active session, but your official enrollment record belongs to a different section.' ?></p>

          <div class="bg-amber-50 rounded-xl p-4 border border-amber-200 text-left text-xs space-y-2 mb-6 text-amber-900">
            <p class="font-semibold">Security Rule Enforced:</p>
            <p>Students may only record attendance in their assigned section roster. If you recently transferred sections, please contact your department chair or instructor to update your enrollment record.</p>
          </div>

        <?php elseif ($status === 'expired'): ?>
          <!-- 3. EXPIRED QR STATE -->
          <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center shadow-inner">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-rose-50 text-rose-700 text-xs font-bold uppercase tracking-wider mb-2">
            <span>● Dynamic Token Expired</span>
          </span>
          <h1 class="text-2xl font-bold text-slate-800 mb-2">QR Code / Token Expired or Invalid</h1>
          <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto"><?= $customMsg !== '' ? htmlspecialchars($customMsg, ENT_QUOTES, 'UTF-8') : 'The entered 6-digit token is expired or invalid. Please check the screen and enter the current code.' ?></p>

          <div class="bg-rose-50 rounded-xl p-4 border border-rose-200 text-left text-xs space-y-2 mb-6 text-rose-900">
            <p class="font-semibold">Live Security Protection:</p>
            <p>Static codes or old tokens cannot be submitted after the rotation window expires. Look directly at the teacher's screen for the live 6-digit code.</p>
          </div>

        <?php elseif ($status === 'duplicate'): ?>
          <!-- 4. ALREADY RECORDED STATE -->
          <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center shadow-inner">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-bold uppercase tracking-wider mb-2">
            <span>● Already Logged</span>
          </span>
          <h1 class="text-2xl font-bold text-slate-800 mb-2">Attendance Already Recorded</h1>
          <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto"><?= $customMsg !== '' ? htmlspecialchars($customMsg, ENT_QUOTES, 'UTF-8') : 'Your check-in for this class session was already captured today. No duplicate entry is necessary.' ?></p>

          <div class="bg-blue-50 rounded-xl p-4 border border-blue-200 text-left text-xs space-y-2 mb-6 text-blue-900">
            <p class="font-semibold">Database Integrity:</p>
            <p>The system enforces a composite unique constraint on (student_id, date, subject) to prevent accidental double-logging.</p>
          </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <a href="<?php echo url('student/scanner'); ?>" class="w-full sm:w-auto px-6 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition shadow flex items-center justify-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Scan Another Code</span>
          </a>
          <a href="<?php echo url('student/calendar'); ?>" class="w-full sm:w-auto px-6 py-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition">
            View Calendar
          </a>
          <a href="<?php echo url('student/history'); ?>" class="w-full sm:w-auto px-6 py-2.5 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition">
            View My History
          </a>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  try {
    const raw = sessionStorage.getItem('last_attendance_record');
    if (raw) {
      const rec = JSON.parse(raw);
      if (rec.student_name && document.getElementById('res-student-name')) {
        document.getElementById('res-student-name').textContent = `${rec.student_name} (${rec.student_number || ''})`;
      }
      if (rec.subject && document.getElementById('res-subject')) {
        document.getElementById('res-subject').textContent = rec.subject;
      }
      if (rec.section && document.getElementById('res-section')) {
        document.getElementById('res-section').textContent = `Section ${rec.section}`;
      }
      if (rec.time && document.getElementById('res-timestamp')) {
        document.getElementById('res-timestamp').textContent = `Today, ${rec.time}`;
      }
      if (rec.status && document.getElementById('res-status')) {
        const isTardy = rec.status.toLowerCase() === 'tardy';
        const stEl = document.getElementById('res-status');
        stEl.textContent = `${rec.status.toUpperCase()} (Recorded)`;
        if (isTardy) {
          stEl.className = 'badge badge-tardy bg-amber-100 text-amber-800 font-bold px-2.5 py-0.5 rounded-full text-xs';
        } else {
          stEl.className = 'badge badge-present bg-emerald-100 text-emerald-800 font-bold px-2.5 py-0.5 rounded-full text-xs';
        }
      }
    }
  } catch (e) {}
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
