<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" onclick="APP.closeSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="flex flex-col">
  <!-- Logo -->
  <div class="px-4 py-5 flex items-center gap-3 border-b border-white/10">
    <div class="w-9 h-9 rounded-lg bg-white/15 flex items-center justify-center text-white font-bold text-sm">BCP</div>
    <div>
      <div class="text-white text-sm font-semibold leading-tight">Attendance</div>
      <div class="text-xs text-slate-400 leading-tight">Management System</div>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="flex-1 py-3 px-3 space-y-0.5 overflow-y-auto">
    <!-- Menu Panel Section -->
    <div class="nav-section" style="margin-top: 0.25rem;">Menu Panel</div>
    <a href="/Attendance _Management_System/includes/views/dashboard/index.php" class="nav-item" data-page="dashboard">
      <span>🏠</span> Dashboard
    </a>
    <a href="/Attendance _Management_System/includes/views/dashboard/excuse-slips.php" class="nav-item" data-page="excuse-slips">
      <span>📝</span> Excuse slips
    </a>
    <a href="/Attendance _Management_System/includes/views/dashboard/analytics.php" class="nav-item" data-page="analytics">
      <span>📈</span> Analytics
    </a>
    <a href="/Attendance _Management_System/includes/views/dashboard/tools.php" class="nav-item" data-page="tools">
      <span>🛠️</span> Tools
    </a>

    <!-- Attendance Section -->
    <div class="nav-section">Attendance</div>
    <a href="/Attendance _Management_System/includes/views/attendance/daily.php" class="nav-item" data-page="daily">
      <span>📋</span> Daily Log
    </a>
    <a href="/Attendance _Management_System/includes/views/attendance/scan.php" class="nav-item" data-page="scan">
      <span>📷</span> Scan RFID/QR
    </a>
    <a href="/Attendance _Management_System/includes/views/attendance/daily.php?tab=tardy" class="nav-item" data-page="tardy">
      <span>⏰</span> Tardy &amp; Absence
    </a>
    <a href="/Attendance _Management_System/includes/views/attendance/teachers.php" class="nav-item" data-page="teachers">
      <span>👨‍🏫</span> Teacher Attendance
    </a>
    <a href="/Attendance _Management_System/includes/views/calendar/index.php" class="nav-item" data-page="calendar">
      <span>📅</span> Calendar
    </a>

    <!-- Management Section -->
    <div class="nav-section">Management</div>
    <a href="/Attendance _Management_System/includes/views/users/list.php" class="nav-item" data-page="users">
      <span>👥</span> Users
    </a>
    <a href="/Attendance _Management_System/includes/views/alerts/index.php" class="nav-item" data-page="alerts">
      <span>🔔</span> Alerts
    </a>
    <a href="/Attendance _Management_System/includes/views/settings/index.php" class="nav-item" data-page="settings">
      <span>⚙️</span> Settings
    </a>
  </nav>

  <!-- User Info -->
  <div class="px-4 py-3 border-t border-white/10">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-full bg-slate-600 flex items-center justify-center text-white text-xs font-semibold">AS</div>
      <div class="flex-1 min-w-0">
        <div class="text-sm text-white truncate">Admin Santos</div>
        <div class="text-xs text-slate-400">Administrator</div>
      </div>
    </div>
    <a href="/Attendance _Management_System/includes/views/auth/login.php" class="nav-item mt-2 text-red-400 hover:text-red-300">
      <span>→</span> Logout
    </a>
  </div>
</aside>
