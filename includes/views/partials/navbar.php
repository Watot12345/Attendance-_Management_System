<!-- Topbar -->
<header id="topbar">
  <!-- Mobile Hamburger -->
  <button id="sidebar-toggle" class="lg:hidden p-2 rounded-md hover:bg-gray-100" onclick="APP.toggleSidebar()" aria-label="Toggle sidebar">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
  </button>

  <!-- Logo (mobile) / System name (desktop) -->
  <div class="flex items-center gap-2">
    <span class="hidden lg:inline text-sm font-semibold" style="color:var(--color-navy-950)">BCP</span>
    <span class="hidden lg:inline text-sm" style="color:var(--color-text-secondary)">Attendance Management System</span>
  </div>

  <!-- Spacer -->
  <div class="flex-1"></div>

  <!-- Notification Bell -->
  <div class="relative">
    <button id="notif-bell" class="relative p-2 rounded-md hover:bg-gray-100" onclick="APP.toggleNotifications()" aria-label="Notifications">
      <svg class="w-5 h-5" style="color:var(--color-text-secondary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
      <!-- Alert count badge -->
      <span class="absolute -top-0.5 -right-0.5 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-semibold">3</span>
    </button>

    <!-- Notification Dropdown -->
    <div id="notif-dropdown" class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-lg shadow-modal border border-gray-100 z-50">
      <div class="px-4 py-3 border-b border-gray-100">
        <h3 class="text-sm font-semibold">Notifications</h3>
      </div>
      <div class="py-2 max-h-72 overflow-y-auto">
        <div class="px-4 py-2.5 hover:bg-gray-50 cursor-pointer">
          <div class="flex items-center gap-2">
            <span class="badge badge-absent">● Absent</span>
            <span class="text-xs" style="color:var(--color-text-muted)">08:45 AM</span>
          </div>
          <p class="text-sm mt-1">Juan Dela Cruz marked absent. Alert sent to parent.</p>
        </div>
        <div class="px-4 py-2.5 hover:bg-gray-50 cursor-pointer">
          <div class="flex items-center gap-2">
            <span class="badge badge-tardy">● Tardy</span>
            <span class="text-xs" style="color:var(--color-text-muted)">08:32 AM</span>
          </div>
          <p class="text-sm mt-1">Maria Santos arrived 18 minutes late via RFID.</p>
        </div>
        <div class="px-4 py-2.5 hover:bg-gray-50 cursor-pointer">
          <div class="flex items-center gap-2">
            <span class="badge badge-pending">● Pending</span>
            <span class="text-xs" style="color:var(--color-text-muted)">Yesterday</span>
          </div>
          <p class="text-sm mt-1">New excuse slip from Pedro Reyes awaiting review.</p>
        </div>
      </div>
      <div class="px-4 py-2 border-t border-gray-100">
        <a href="/Attendance _Management_System/includes/views/alerts/index.php" class="text-sm font-medium" style="color:var(--color-teal-500)">View all alerts →</a>
      </div>
    </div>
  </div>

  <!-- User Dropdown -->
  <div class="relative">
    <button id="user-menu-btn" class="flex items-center gap-2 p-1.5 rounded-md hover:bg-gray-100" onclick="APP.toggleUserMenu()">
      <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-sm font-semibold" style="color:var(--color-navy-950)">AS</div>
      <span class="hidden md:inline text-sm font-medium">Admin</span>
      <svg class="w-4 h-4 hidden md:inline" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div id="user-dropdown" class="hidden absolute right-0 top-full mt-2 w-48 bg-white rounded-lg shadow-modal border border-gray-100 z-50 py-1">
      <a href="/Attendance _Management_System/includes/views/users/profile.php" class="block px-4 py-2 text-sm hover:bg-gray-50">My Profile</a>
      <a href="/Attendance _Management_System/includes/views/settings/index.php" class="block px-4 py-2 text-sm hover:bg-gray-50">Settings</a>
      <hr class="my-1 border-gray-100">
      <a href="/Attendance _Management_System/includes/views/auth/login.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-50">Logout</a>
    </div>
  </div>
</header>
