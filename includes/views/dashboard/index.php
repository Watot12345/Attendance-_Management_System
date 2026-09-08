<?php $page_title = 'Dashboard'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <!-- Content Area -->
      <main class="flex-1 p-6 bg-surface">
        <!-- Page Header -->
        <div class="mb-6">
          <h1 class="text-2xl font-bold text-text-primary">Good morning, Dr. Santos</h1>
          <p class="text-sm mt-1 text-text-secondary">Wednesday, September 7, 2026</p>
        </div>

        <!-- Stat Cards Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
          <!-- Present -->
          <div class="bg-white rounded-lg p-5 shadow-card stat-card-present">
            <p class="text-sm mb-1 text-text-secondary">Present Today</p>
            <p class="stat-number text-present">247</p>
            <p class="text-xs mt-1 text-present">↑ 12 from yesterday</p>
          </div>
          <!-- Tardy -->
          <div class="bg-white rounded-lg p-5 shadow-card stat-card-tardy">
            <p class="text-sm mb-1 text-text-secondary">Tardy</p>
            <p class="stat-number text-tardy">18</p>
            <p class="text-xs mt-1 text-present">↓ 3 from yesterday</p>
          </div>
          <!-- Absent -->
          <div class="bg-white rounded-lg p-5 shadow-card stat-card-absent">
            <p class="text-sm mb-1 text-text-secondary">Absent</p>
            <p class="stat-number text-absent">12</p>
            <p class="text-xs mt-1 text-absent">↑ 2 from yesterday</p>
          </div>
          <!-- Excused -->
          <div class="bg-white rounded-lg p-5 shadow-card stat-card-excused">
            <p class="text-sm mb-1 text-text-secondary">Excused</p>
            <p class="stat-number text-excused">5</p>
            <p class="text-xs mt-1 text-text-muted">—</p>
          </div>
        </div>

        <!-- Middle Row: Trend Chart + ML Insight -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
          <!-- 30-Day Trend Chart -->
          <div class="lg:col-span-2 bg-white rounded-lg p-5 shadow-card">
            <h3 class="text-lg font-semibold mb-4 text-text-primary">30-Day Attendance Trend</h3>
            <div class="w-full h-60 bg-surface rounded-lg flex items-center justify-center">
              <canvas id="trend-chart"></canvas>
            </div>
          </div>

          <!-- ML Insight Card -->
          <div class="ml-insight-card">
            <p class="text-xs font-semibold uppercase tracking-wider mb-2 flex items-center gap-1.5 text-teal-500">
              <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              Pattern Detected
            </p>
            <h4 class="text-lg font-semibold mb-2 text-text-primary">Absences spike on Mondays</h4>
            <p class="text-sm mb-3 text-text-secondary">
              Students in Grade 7 Section A have 3× more absences on Mondays vs other days. Based on 90 days of attendance data.
            </p>
            <div class="flex items-center justify-between">
              <span class="text-sm font-medium text-text-secondary">12 students affected</span>
              <a href="<?php echo url('dashboard/analytics?tab=patterns'); ?>" class="btn btn-ghost btn-sm">View Details →</a>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-lg shadow-card">
          <div class="px-5 py-4 border-b border-border">
            <h3 class="text-lg font-semibold text-text-primary">Recent Activity</h3>
          </div>
          <div class="divide-y divide-slate-100">
            <div class="px-5 py-3 flex items-center gap-3">
              <span class="text-xs font-medium px-2 py-0.5 rounded bg-teal-100 text-teal-500">08:32</span>
              <span class="text-sm">Dela Cruz, Juan — <span class="badge badge-present">● Present</span></span>
              <span class="text-xs ml-auto text-text-muted">QR Scan</span>
            </div>
            <div class="px-5 py-3 flex items-center gap-3">
              <span class="text-xs font-medium px-2 py-0.5 rounded bg-teal-100 text-teal-500">08:31</span>
              <span class="text-sm">Santos, Maria — <span class="badge badge-tardy">● Tardy</span></span>
              <span class="text-xs ml-auto text-text-muted">RFID</span>
            </div>
            <div class="px-5 py-3 flex items-center gap-3">
              <span class="text-xs font-medium px-2 py-0.5 rounded bg-teal-100 text-teal-500">08:28</span>
              <span class="text-sm">Garcia, Ana — <span class="badge badge-present">● Present</span></span>
              <span class="text-xs ml-auto text-text-muted">RFID</span>
            </div>
            <div class="px-5 py-3 flex items-center gap-3">
              <span class="text-xs font-medium px-2 py-0.5 rounded bg-teal-100 text-teal-500">08:25</span>
              <span class="text-sm">Lim, Jenny — <span class="badge badge-present">● Present</span></span>
              <span class="text-xs ml-auto text-text-muted">QR Scan</span>
            </div>
            <div class="px-5 py-3 flex items-center gap-3">
              <span class="text-xs font-medium px-2 py-0.5 rounded bg-teal-100 text-teal-500">08:22</span>
              <span class="text-sm">Cruz, Robert — <span class="badge badge-present">● Present</span></span>
              <span class="text-xs ml-auto text-text-muted">RFID</span>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php $page_js = '<script src="' . url('assets/js/dashboard.js') . '"></script>'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('dashboard');</script>
