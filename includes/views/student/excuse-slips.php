<?php
$page_title = 'Excuse Slips';
require_once dirname(__DIR__, 2) . '/core/Router.php';
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
            <span class="badge badge-present">Student Portal</span>
            <span class="text-xs text-slate-500">Juan Dela Cruz • BSIT 3-A</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">Excuse Slips &amp; Absence Clearance</h1>
          <p class="text-sm text-slate-500">Submit medical certificates or valid justifications for missed class sessions.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Submit Form (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-xl p-6 border border-slate-200 shadow-sm">
          <h2 class="font-bold text-slate-800 text-base mb-1">Submit New Excuse Slip</h2>
          <p class="text-xs text-slate-500 mb-4">Your course instructor will review and update your official attendance record accordingly.</p>

          <form id="excuse-slip-form" onsubmit="event.preventDefault(); submitExcuseSlip();" class="space-y-4">
            <!-- Subject -->
            <div>
              <label for="excuse-subject" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Class / Subject *</label>
              <select id="excuse-subject" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <option value="">Select subject...</option>
                <option value="IT301">IT301 — Web Development 2 (Prof. Ramirez)</option>
                <option value="IT302">IT302 — Database Systems 2 (Prof. Ramirez)</option>
                <option value="IT303">IT303 — Systems Integration (Prof. Santos)</option>
              </select>
            </div>

            <!-- Date of Absence -->
            <div>
              <label for="excuse-date" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Date of Absence *</label>
              <input type="date" id="excuse-date" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required value="2026-09-08">
            </div>

            <!-- Reason Category -->
            <div>
              <label for="excuse-category" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Reason Category *</label>
              <select id="excuse-category" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                <option value="Medical">Medical / Illness (with doctor's note)</option>
                <option value="Emergency">Family / Personal Emergency</option>
                <option value="Official">Official Institutional Activity / Competition</option>
                <option value="Other">Other Valid Reason</option>
              </select>
            </div>

            <!-- Explanation -->
            <div>
              <label for="excuse-reason" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Detailed Explanation *</label>
              <textarea id="excuse-reason" rows="3" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Please provide specific context for your absence..." required></textarea>
            </div>

            <!-- Supporting Document -->
            <div>
              <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">Supporting Document (PDF or Photo)</label>
              <div class="border-2 border-dashed border-slate-300 rounded-lg p-4 text-center hover:border-indigo-400 transition cursor-pointer bg-slate-50">
                <svg class="w-6 h-6 text-slate-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                <span class="text-xs text-slate-600 font-medium">Click to upload medical slip or excuse letter</span>
                <p class="text-[10px] text-slate-400 mt-0.5">PNG, JPG, PDF up to 5MB</p>
              </div>
            </div>

            <button type="submit" class="w-full py-2.5 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs shadow transition flex items-center justify-center gap-2">
              <span>Submit Excuse Slip for Review</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </button>
          </form>
        </div>

        <!-- Excuse Slips History (7 cols) -->
        <div class="lg:col-span-7 bg-white rounded-xl border border-slate-200 shadow-sm p-6">
          <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-slate-800 text-base">Submitted Requests &amp; Status</h2>
            <span class="text-xs text-slate-400">Showing 2 records</span>
          </div>

          <div class="space-y-4">
            <!-- Item 1: Approved -->
            <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/30">
              <div class="flex items-start justify-between gap-2 mb-2">
                <div>
                  <span class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-800 text-[10px] font-bold">APPROVED</span>
                  <h3 class="font-bold text-slate-800 text-sm mt-1">IT301 — Web Development 2</h3>
                  <div class="text-xs text-slate-500">Absence Date: <strong class="text-slate-700">Aug 28, 2026</strong> • Category: <strong class="text-slate-700">Medical</strong></div>
                </div>
                <span class="text-[11px] text-slate-400">Slip #204</span>
              </div>
              <p class="text-xs text-slate-600 bg-white/80 p-2.5 rounded-lg border border-slate-100 mb-2">
                "Had high fever and visited college clinic. Medical certificate attached."
              </p>
              <div class="text-[11px] text-emerald-700 font-medium">
                ✓ Reviewed by Prof. M. Ramirez • Attendance record updated to <strong>Excused</strong> with audit trail entry.
              </div>
            </div>

            <!-- Item 2: Pending -->
            <div class="p-4 rounded-xl border border-amber-200 bg-amber-50/30">
              <div class="flex items-start justify-between gap-2 mb-2">
                <div>
                  <span class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">PENDING REVIEW</span>
                  <h3 class="font-bold text-slate-800 text-sm mt-1">IT302 — Database Systems 2</h3>
                  <div class="text-xs text-slate-500">Absence Date: <strong class="text-slate-700">Sep 01, 2026</strong> • Category: <strong class="text-slate-700">Official Activity</strong></div>
                </div>
                <span class="text-[11px] text-slate-400">Slip #208</span>
              </div>
              <p class="text-xs text-slate-600 bg-white/80 p-2.5 rounded-lg border border-slate-100 mb-2">
                "Represented the department in the inter-collegiate Hackathon competition."
              </p>
              <div class="text-[11px] text-amber-700 font-medium">
                ⏳ Awaiting faculty review and endorsement.
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
function submitExcuseSlip() {
  alert('Excuse slip submitted successfully! Your instructor will be notified to review the attached documentation.');
  document.getElementById('excuse-slip-form').reset();
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
