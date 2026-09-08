<?php
$page_title = 'Scan Attendance QR';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body max-w-4xl mx-auto">
      <!-- Breadcrumb & Header -->
      <div class="mb-6 text-center">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-xs font-semibold mb-2">
          <span>Student Portal</span> • <span>Juan Dela Cruz (2026-00123)</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-800">Scan Session Attendance QR</h1>
        <p class="text-sm text-slate-500 max-w-md mx-auto">Point your device camera at the rotating QR code projected on the classroom screen.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
        <!-- Main Scanner Viewfinder (7 cols) -->
        <div class="md:col-span-7 space-y-4">
          <div class="bg-slate-900 rounded-2xl p-6 shadow-xl border border-slate-800 text-white relative overflow-hidden">
            <!-- Camera Viewfinder Mockup -->
            <div class="relative w-full aspect-square max-w-[340px] mx-auto rounded-xl bg-slate-950 border-2 border-slate-700 flex flex-col items-center justify-center overflow-hidden">
              <!-- Grid background simulation -->
              <div class="absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:24px_24px] opacity-40"></div>

              <!-- Animated Scan Laser line -->
              <div class="absolute left-6 right-6 h-0.5 bg-gradient-to-r from-indigo-500 via-emerald-400 to-indigo-500 shadow-[0_0_12px_rgba(52,211,153,0.8)] animate-bounce"></div>

              <!-- Reticle Corners -->
              <div class="w-48 h-48 relative z-10 flex flex-col justify-between">
                <div class="flex justify-between">
                  <div class="w-6 h-6 border-t-4 border-l-4 border-emerald-400 rounded-tl-lg"></div>
                  <div class="w-6 h-6 border-t-4 border-r-4 border-emerald-400 rounded-tr-lg"></div>
                </div>
                <div class="text-center">
                  <span class="px-2.5 py-1 rounded bg-black/60 backdrop-blur text-[11px] font-medium text-slate-300">Align QR within frame</span>
                </div>
                <div class="flex justify-between">
                  <div class="w-6 h-6 border-b-4 border-l-4 border-emerald-400 rounded-bl-lg"></div>
                  <div class="w-6 h-6 border-b-4 border-r-4 border-emerald-400 rounded-br-lg"></div>
                </div>
              </div>
            </div>

            <!-- Viewfinder Controls -->
            <div class="flex items-center justify-between mt-5 pt-4 border-t border-slate-800 text-xs text-slate-400">
              <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                <span>Camera: <strong class="text-white">Active (Back)</strong></span>
              </div>
              <button type="button" class="text-indigo-400 hover:text-indigo-300 transition" onclick="toggleCamera()">Switch Camera</button>
            </div>
          </div>
        </div>

        <!-- Right Side: Manual Token Fallback & Simulation Panel (5 cols) -->
        <div class="md:col-span-5 space-y-4">
          <!-- Manual Token Entry Card -->
          <div class="bg-white rounded-xl p-5 border border-slate-200 shadow-sm">
            <h3 class="font-bold text-slate-800 text-sm mb-1">Backup: Enter Token Manually</h3>
            <p class="text-xs text-slate-500 mb-4">If your camera cannot scan the screen, enter the 6-character session token displayed below the teacher's QR code.</p>

            <form action="<?php echo url('student/scan-result'); ?>" method="GET" class="space-y-3">
              <input type="hidden" name="status" value="success">
              <div>
                <label for="manual-token" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">6-Digit Session Token</label>
                <input type="text" id="manual-token" name="token" maxlength="8" placeholder="e.g. 7X9K2M" class="w-full px-3.5 py-2.5 text-center font-mono font-bold text-lg uppercase tracking-widest rounded-lg border border-slate-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-slate-50 focus:bg-white">
              </div>

              <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow transition flex items-center justify-center gap-2">
                <span>Submit Attendance Token</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
              </button>
            </form>
          </div>

          <!-- Interactive Test Scanner Simulator -->
          <div class="bg-slate-50 rounded-xl p-5 border border-slate-200">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-sm">🧪</span>
              <h3 class="font-bold text-slate-800 text-xs uppercase tracking-wider">Test QR Scan Outcomes</h3>
            </div>
            <p class="text-xs text-slate-500 mb-3">Simulate scanning various tokens to test the 4 distinct feedback states defined in the specification:</p>

            <div class="space-y-2">
              <a href="<?php echo url('student/scan-result?status=success'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                  <span class="font-semibold text-slate-700 group-hover:text-emerald-800">1. Valid Scan (Success)</span>
                </div>
                <span class="text-[11px] text-emerald-600 font-medium">Marked Present →</span>
              </a>

              <a href="<?php echo url('student/scan-result?status=wrong_section'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-amber-500 hover:bg-amber-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                  <span class="font-semibold text-slate-700 group-hover:text-amber-800">2. Wrong Section</span>
                </div>
                <span class="text-[11px] text-amber-600 font-medium">Not Enrolled →</span>
              </a>

              <a href="<?php echo url('student/scan-result?status=expired'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-rose-500 hover:bg-rose-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                  <span class="font-semibold text-slate-700 group-hover:text-rose-800">3. Expired QR Token</span>
                </div>
                <span class="text-[11px] text-rose-600 font-medium">Expired →</span>
              </a>

              <a href="<?php echo url('student/scan-result?status=duplicate'); ?>" class="flex items-center justify-between p-2.5 rounded-lg bg-white border border-slate-200 hover:border-blue-500 hover:bg-blue-50/50 transition text-xs group">
                <div class="flex items-center gap-2">
                  <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                  <span class="font-semibold text-slate-700 group-hover:text-blue-800">4. Already Recorded</span>
                </div>
                <span class="text-[11px] text-blue-600 font-medium">Duplicate →</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
function toggleCamera() {
  alert('Camera switched (simulated front/back sensor).');
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
