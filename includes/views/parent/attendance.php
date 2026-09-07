<?php $page_title = "My Child's Attendance"; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <!-- Minimal Parent Sidebar -->
    <div id="sidebar-overlay" onclick="APP.closeSidebar()"></div>
    <aside id="sidebar" class="flex flex-col">
      <div class="px-4 py-5 flex items-center gap-3 border-b border-white/10">
        <div class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center text-white font-bold text-sm">BCP</div>
        <div>
          <div class="text-white text-sm font-semibold leading-tight">Parent Portal</div>
          <div class="text-xs text-slate-400 leading-tight">Attendance System</div>
        </div>
      </div>
      <nav class="flex-1 py-3 px-3 space-y-0.5">
        <a href="#" class="nav-item active"><span>📅</span> Attendance</a>
        <a href="#" class="nav-item"><span>🔔</span> Alert History</a>
        <a href="/Attendance _Management_System/includes/views/dashboard/excuse-slips.php?tab=submit" class="nav-item"><span>📝</span> Submit Excuse</a>
      </nav>
      <div class="px-4 py-3 border-t border-white/10">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-full bg-slate-600 flex items-center justify-center text-white text-xs font-semibold">MD</div>
          <div class="flex-1 min-w-0">
            <div class="text-sm text-white truncate">Maria Dela Cruz</div>
            <div class="text-xs text-slate-400">Parent</div>
          </div>
        </div>
        <a href="/Attendance _Management_System/includes/views/auth/login.php" class="nav-item mt-2 text-red-400 hover:text-red-300"><span>→</span> Logout</a>
      </div>
    </aside>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <div class="mb-6">
          <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">My Child's Attendance</h1>
          <p class="text-sm mt-1" style="color:var(--color-text-secondary)">Juan Dela Cruz · Grade 7 Section A</p>
        </div>

        <!-- Read-only Calendar (simplified) -->
        <div class="bg-white rounded-lg shadow-card mb-6">
          <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--color-border)">
            <button class="btn btn-ghost btn-sm">← Aug</button>
            <h3 class="text-lg font-semibold" style="color:var(--color-text-primary)">September 2026</h3>
            <button class="btn btn-ghost btn-sm">Oct →</button>
          </div>
          <div class="p-5">
            <div class="grid grid-cols-7 gap-2 mb-2">
              <div class="text-center text-xs font-semibold" style="color:var(--color-text-muted)">S</div>
              <div class="text-center text-xs font-semibold" style="color:var(--color-text-muted)">M</div>
              <div class="text-center text-xs font-semibold" style="color:var(--color-text-muted)">T</div>
              <div class="text-center text-xs font-semibold" style="color:var(--color-text-muted)">W</div>
              <div class="text-center text-xs font-semibold" style="color:var(--color-text-muted)">T</div>
              <div class="text-center text-xs font-semibold" style="color:var(--color-text-muted)">F</div>
              <div class="text-center text-xs font-semibold" style="color:var(--color-text-muted)">S</div>
            </div>
            <div class="grid grid-cols-7 gap-2">
              <div class="cal-day cal-blank"></div><div class="cal-day cal-blank"></div>
              <div class="cal-day cal-present">1</div><div class="cal-day cal-present">2</div><div class="cal-day cal-tardy">3</div><div class="cal-day cal-present">4</div><div class="cal-day cal-nodata">5</div>
              <div class="cal-day cal-nodata">6</div><div class="cal-day cal-present">7</div><div class="cal-day cal-present">8</div><div class="cal-day cal-absent">9</div><div class="cal-day cal-present">10</div><div class="cal-day cal-present">11</div><div class="cal-day cal-nodata">12</div>
              <div class="cal-day cal-nodata">13</div><div class="cal-day cal-present">14</div><div class="cal-day cal-excused">15</div><div class="cal-day cal-present">16</div><div class="cal-day cal-present">17</div><div class="cal-day cal-present">18</div><div class="cal-day cal-nodata">19</div>
            </div>
          </div>
          <div class="px-5 py-3 border-t flex flex-wrap gap-3" style="border-color:var(--color-border)">
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#dcfce7; border:1px solid #bbf7d0"></span> Present</span>
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#fef3c7; border:1px solid #fde68a"></span> Tardy</span>
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#fee2e2; border:1px solid #fecaca"></span> Absent</span>
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#dbeafe; border:1px solid #bfdbfe"></span> Excused</span>
          </div>
        </div>

        <!-- Recent Alerts -->
        <div class="bg-white rounded-lg shadow-card mb-6">
          <div class="px-5 py-4 border-b" style="border-color:var(--color-border)">
            <h3 class="text-lg font-semibold" style="color:var(--color-text-primary)">Recent Alerts</h3>
          </div>
          <div class="divide-y" style="border-color:#f1f5f9">
            <div class="px-5 py-3 flex items-center gap-3">
              <span class="badge badge-absent">● Absent</span>
              <span class="text-sm">Sep 9, 2026 — Your child was absent</span>
            </div>
            <div class="px-5 py-3 flex items-center gap-3">
              <span class="badge badge-tardy">● Tardy</span>
              <span class="text-sm">Sep 3, 2026 — Your child arrived 18 minutes late</span>
            </div>
          </div>
        </div>

        <!-- Submit Excuse CTA -->
        <a href="/Attendance _Management_System/includes/views/dashboard/excuse-slips.php?tab=submit" class="btn btn-primary">📝 Submit Excuse Slip</a>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>
