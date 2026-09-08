<?php
$page_title = 'Scan Feedback Result';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';

// Get status from query string, default to success
$status = $_GET['status'] ?? 'success';
if (!in_array($status, ['success', 'wrong_section', 'expired', 'duplicate'])) {
    $status = 'success';
}
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body max-w-2xl mx-auto">
      <!-- Quick State Switcher for Review/Demo -->
      <div class="mb-6 p-2 bg-white rounded-xl border border-slate-200 shadow-sm">
        <div class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider px-3 pt-1 pb-2">Demo: View All 4 Feedback States</div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-1.5">
          <a href="<?php echo url('student/scan-result?status=success'); ?>" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-center transition <?php echo $status === 'success' ? 'bg-emerald-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">
            1. Success
          </a>
          <a href="<?php echo url('student/scan-result?status=wrong_section'); ?>" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-center transition <?php echo $status === 'wrong_section' ? 'bg-amber-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">
            2. Wrong Section
          </a>
          <a href="<?php echo url('student/scan-result?status=expired'); ?>" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-center transition <?php echo $status === 'expired' ? 'bg-rose-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">
            3. Expired QR
          </a>
          <a href="<?php echo url('student/scan-result?status=duplicate'); ?>" class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-center transition <?php echo $status === 'duplicate' ? 'bg-blue-600 text-white shadow-sm' : 'text-slate-600 hover:bg-slate-100'; ?>">
            4. Already Logged
          </a>
        </div>
      </div>

      <!-- Result Card Container -->
      <div class="bg-white rounded-2xl p-8 border border-slate-200 shadow-lg text-center relative overflow-hidden">

        <?php if ($status === 'success'): ?>
          <!-- 1. SUCCESS STATE -->
          <div class="w-20 h-20 mx-auto mb-5 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center shadow-inner">
            <svg class="w-10 h-10 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          </div>
          <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold uppercase tracking-wider mb-2">
            <span>● Verified &amp; Recorded</span>
          </span>
          <h1 class="text-2xl font-bold text-slate-800 mb-2">Attendance Logged Successfully!</h1>
          <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">Your attendance has been officially confirmed and written to the class session log.</p>

          <!-- Verification Details Table -->
          <div class="bg-slate-50 rounded-xl p-4 border border-slate-200 text-left text-xs space-y-2.5 mb-6">
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Student Name</span>
              <span class="font-bold text-slate-800">Juan Dela Cruz (2026-00123)</span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Course &amp; Subject</span>
              <span class="font-bold text-slate-800">IT301 — Web Development 2</span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Enrolled Section</span>
              <span class="font-bold text-blue-700">BSIT 3-A</span>
            </div>
            <div class="flex justify-between pb-2 border-b border-slate-200/60">
              <span class="text-slate-500 font-medium">Timestamp</span>
              <span class="font-bold text-slate-800">Today, 08:05:12 AM</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 font-medium">Status Assigned</span>
              <span class="badge badge-present font-bold">Present (On-Time)</span>
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
          <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">You scanned the attendance code for <strong class="text-slate-700">BSIT 3-A</strong>, but your official enrollment record is in <strong class="text-slate-700">BSIT 3-C</strong>.</p>

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
          <h1 class="text-2xl font-bold text-slate-800 mb-2">QR Code Has Expired</h1>
          <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">The projected QR code rotates every 15 seconds to prevent screenshots. Please look at the classroom screen and scan the active code.</p>

          <div class="bg-rose-50 rounded-xl p-4 border border-rose-200 text-left text-xs space-y-2 mb-6 text-rose-900">
            <p class="font-semibold">Anti-Screenshot Protection:</p>
            <p>Static photos or screenshots taken earlier cannot be submitted after the rotation window. Point your camera directly at the live projector screen.</p>
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
          <p class="text-sm text-slate-500 mb-6 max-w-md mx-auto">Your check-in for this class session was already captured at <strong class="text-slate-700">08:02 AM today</strong>. No duplicate entry is necessary.</p>

          <div class="bg-blue-50 rounded-xl p-4 border border-blue-200 text-left text-xs space-y-2 mb-6 text-blue-900">
            <p class="font-semibold">Database Integrity:</p>
            <p>The system enforces a single unique attendance record per student per session to prevent accidental double-logging.</p>
          </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center justify-center gap-3 pt-2">
          <a href="<?php echo url('student/scanner'); ?>" class="w-full sm:w-auto px-6 py-2.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-semibold text-xs transition shadow flex items-center justify-center gap-2">
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

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
