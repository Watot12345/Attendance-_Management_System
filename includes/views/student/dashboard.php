<?php
$page_title = 'Student Dashboard';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Student Hero Card -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-xl mb-6 relative overflow-hidden border border-slate-800">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span class="px-2.5 py-1 rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-bold uppercase tracking-wider border border-indigo-500/30">
                Official Student Portal
              </span>
              <span class="text-xs text-slate-400">AY 2025–2026</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold font-display">Welcome, Juan Dela Cruz!</h1>
            <p class="text-slate-300 text-sm mt-1">Student No: <strong class="text-white font-mono bg-white/10 px-2 py-0.5 rounded">2026-00123</strong> · BS Information Technology (3rd Year)</p>
          </div>

          <!-- Big Scan Button CTA -->
          <a href="<?php echo url('student/scanner'); ?>" class="btn btn-lg flex items-center justify-center gap-3 py-4 px-6 text-base font-bold bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white shadow-lg shadow-indigo-500/30 rounded-xl transition duration-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Scan Live Attendance QR</span>
          </a>
        </div>
        <!-- Decorative Background Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>
      </div>

        <!-- Student Attendance Stats Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <div class="bg-white rounded-xl p-5 shadow-card border border-slate-100">
            <p class="text-xs font-semibold uppercase text-text-muted">Enrolled Subjects</p>
            <p class="stat-number text-slate-800">2</p>
            <p class="text-xs text-teal-600 font-medium mt-1">BSIT 3-A &amp; BSIT 3-B</p>
          </div>

          <div class="bg-white rounded-xl p-5 shadow-card border border-slate-100">
            <p class="text-xs font-semibold uppercase text-text-muted">Overall Attendance Rate</p>
            <p class="stat-number text-emerald-600">95.0%</p>
            <p class="text-xs text-emerald-600 font-medium mt-1">Excellent Standing ✓</p>
          </div>

          <div class="bg-white rounded-xl p-5 shadow-card border border-slate-100">
            <p class="text-xs font-semibold uppercase text-text-muted">Total Present</p>
            <p class="stat-number text-emerald-600">19</p>
            <p class="text-xs text-text-muted mt-1">Across all sessions</p>
          </div>

          <div class="bg-white rounded-xl p-5 shadow-card border border-slate-100">
            <p class="text-xs font-semibold uppercase text-text-muted">Total Late / Absent</p>
            <p class="stat-number text-slate-700">1 <span class="text-sm font-normal text-text-muted">/ 0</span></p>
            <p class="text-xs text-amber-600 font-medium mt-1">1 Tardy log recorded</p>
          </div>
        </div>

        <div class="mb-8">
          <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-text-primary">My Active Attendance Courses</h2>
            <a href="<?= url('student/history') ?>" class="text-xs font-semibold text-teal-600 hover:text-teal-700">Attendance Log →</a>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Subject 1 -->
            <div class="bg-white rounded-xl shadow-card border border-slate-100 p-5 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between gap-2 mb-2">
                  <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-teal-50 text-teal-700 border border-teal-200">BSIT 3-A</span>
                  <span class="badge badge-present">Active</span>
                </div>
                <h3 class="text-lg font-bold text-text-primary">IT311: Web Systems and Technologies</h3>
                <p class="text-xs text-text-muted mt-0.5">Instructor: Prof. Bernardo Cruz · Room 402</p>

                <div class="bg-slate-50 p-3 rounded-lg mt-4 text-xs space-y-1">
                  <div class="flex justify-between text-text-secondary">
                    <span>Schedule:</span>
                    <span class="font-medium text-slate-800">Mon/Wed 08:00 AM – 10:00 AM</span>
                  </div>
                  <div class="flex justify-between text-text-secondary">
                    <span>Your Attendance Rate:</span>
                    <span class="font-bold text-emerald-600">95% (18/19 Present)</span>
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                <a href="<?= url('student/scanner') ?>" class="btn btn-primary btn-sm flex-1 justify-center">Scan for this Class</a>
                <a href="<?= url('student/history') ?>" class="btn btn-secondary btn-sm">History</a>
              </div>
            </div>

            <!-- Subject 2 -->
            <div class="bg-white rounded-xl shadow-card border border-slate-100 p-5 flex flex-col justify-between">
              <div>
                <div class="flex items-center justify-between gap-2 mb-2">
                  <span class="px-2.5 py-0.5 text-xs font-bold rounded bg-teal-50 text-teal-700 border border-teal-200">BSIT 3-B</span>
                  <span class="badge badge-present">Active</span>
                </div>
                <h3 class="text-lg font-bold text-text-primary">IT312: Advanced Database Management</h3>
                <p class="text-xs text-text-muted mt-0.5">Instructor: Prof. Bernardo Cruz · Room 403</p>

                <div class="bg-slate-50 p-3 rounded-lg mt-4 text-xs space-y-1">
                  <div class="flex justify-between text-text-secondary">
                    <span>Schedule:</span>
                    <span class="font-medium text-slate-800">Tue/Thu 10:00 AM – 12:00 PM</span>
                  </div>
                  <div class="flex justify-between text-text-secondary">
                    <span>Your Attendance Rate:</span>
                    <span class="font-bold text-emerald-600">100% (1/1 Present)</span>
                  </div>
                </div>
              </div>

              <div class="mt-4 pt-3 border-t border-slate-100 flex items-center gap-2">
                <a href="<?= url('student/scanner') ?>" class="btn btn-primary btn-sm flex-1 justify-center">Scan for this Class</a>
                <a href="<?= url('student/history') ?>" class="btn btn-secondary btn-sm">History</a>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Check-ins -->
        <div class="bg-white rounded-xl shadow-card border border-slate-100 overflow-hidden">
          <div class="px-5 py-4 border-b border-border flex items-center justify-between">
            <h3 class="text-base font-bold text-text-primary">My Recent Check-ins</h3>
            <a href="<?= url('student/history') ?>" class="text-xs font-semibold text-teal-600 hover:text-teal-700">Full History →</a>
          </div>
          <div class="overflow-x-auto">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Class / Section</th>
                  <th>Time In</th>
                  <th>Method</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>Today (Sep 7, 2026)</td>
                  <td class="font-medium">BSIT 3-A · IT311 (Web Systems)</td>
                  <td>08:04:12 AM</td>
                  <td>Dynamic QR</td>
                  <td><span class="badge badge-present">● Present</span></td>
                </tr>
                <tr>
                  <td>Sep 5, 2026</td>
                  <td class="font-medium">BSIT 3-A · IT311 (Web Systems)</td>
                  <td>08:02:40 AM</td>
                  <td>Dynamic QR</td>
                  <td><span class="badge badge-present">● Present</span></td>
                </tr>
                <tr>
                  <td>Sep 3, 2026</td>
                  <td class="font-medium">BSIT 3-B · IT312 (Advanced DB)</td>
                  <td>10:01:15 AM</td>
                  <td>Dynamic QR</td>
                  <td><span class="badge badge-present">● Present</span></td>
                </tr>
              </tbody>
            </table>
          </div>
      </main>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
<script>APP.highlightNav('dashboard');</script>
