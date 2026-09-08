<?php
$page_title = 'My Enrolled Classes';
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
            <span class="text-xs text-slate-500">BSIT • 3rd Year • Section 3-A</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">My Enrolled Subjects</h1>
          <p class="text-sm text-slate-500">Official course roster enrollments for 1st Semester Academic Year 2025–2026.</p>
        </div>

        <div class="flex items-center gap-3">
          <a href="<?php echo url('student/scanner'); ?>" class="px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Scan Attendance</span>
          </a>
        </div>
      </div>

      <!-- Enrolled Classes Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Class 1 -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-indigo-400 transition">
          <div>
            <div class="flex items-center justify-between mb-3">
              <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800">BSIT 3-A</span>
              <span class="badge badge-present text-xs">96.8% Rate</span>
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-1">IT301 — Web Development 2</h3>
            <p class="text-xs text-slate-500 mb-4">Instructor: <strong class="text-slate-700">Prof. M. Ramirez</strong> • Lab 304</p>

            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs space-y-1.5 mb-4">
              <div class="flex justify-between text-slate-600">
                <span>Schedule:</span>
                <span class="font-semibold text-slate-800">Tue / Thu • 08:00 AM – 10:00 AM</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>Sessions Attended:</span>
                <span class="font-semibold text-slate-800">14 / 14 Sessions (100%)</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>Absences / Late:</span>
                <span class="font-semibold text-slate-800">0 Absent • 1 Late</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <a href="<?php echo url('student/history?subject=IT301'); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">View Subject Attendance Log →</a>
            <a href="<?php echo url('student/scanner'); ?>" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition">Scan QR</a>
          </div>
        </div>

        <!-- Class 2 -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-indigo-400 transition">
          <div>
            <div class="flex items-center justify-between mb-3">
              <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-purple-100 text-purple-800">BSIT 3-A</span>
              <span class="badge badge-present text-xs">92.5% Rate</span>
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-1">IT302 — Database Systems 2</h3>
            <p class="text-xs text-slate-500 mb-4">Instructor: <strong class="text-slate-700">Prof. M. Ramirez</strong> • Room 402</p>

            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs space-y-1.5 mb-4">
              <div class="flex justify-between text-slate-600">
                <span>Schedule:</span>
                <span class="font-semibold text-slate-800">Tue / Thu • 10:30 AM – 12:30 PM</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>Sessions Attended:</span>
                <span class="font-semibold text-slate-800">13 / 14 Sessions</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>Absences / Late:</span>
                <span class="font-semibold text-slate-800">1 Absent (Excused) • 1 Late</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <a href="<?php echo url('student/history?subject=IT302'); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">View Subject Attendance Log →</a>
            <a href="<?php echo url('student/scanner'); ?>" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition">Scan QR</a>
          </div>
        </div>

        <!-- Class 3 -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6 flex flex-col justify-between hover:border-indigo-400 transition">
          <div>
            <div class="flex items-center justify-between mb-3">
              <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-100 text-indigo-800">BSIT 3-A</span>
              <span class="badge badge-present text-xs">95.0% Rate</span>
            </div>
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-1">IT303 — Systems Integration</h3>
            <p class="text-xs text-slate-500 mb-4">Instructor: <strong class="text-slate-700">Prof. A. Santos</strong> • Lab 301</p>

            <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs space-y-1.5 mb-4">
              <div class="flex justify-between text-slate-600">
                <span>Schedule:</span>
                <span class="font-semibold text-slate-800">Mon / Wed • 01:30 PM – 03:30 PM</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>Sessions Attended:</span>
                <span class="font-semibold text-slate-800">14 / 14 Sessions</span>
              </div>
              <div class="flex justify-between text-slate-600">
                <span>Absences / Late:</span>
                <span class="font-semibold text-slate-800">0 Absent • 0 Late</span>
              </div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <a href="<?php echo url('student/history?subject=IT303'); ?>" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700">View Subject Attendance Log →</a>
            <a href="<?php echo url('student/scanner'); ?>" class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 transition">Scan QR</a>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
