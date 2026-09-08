<?php
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$pathOnly = trim(parse_url($currentUri, PHP_URL_PATH) ?? '', '/');

$isTeacher = (str_starts_with($pathOnly, 'teacher/') || $pathOnly === 'teacher');
$isStudent = (str_starts_with($pathOnly, 'student/') || $pathOnly === 'student');
$isAdmin = !$isTeacher && !$isStudent;

$userName = $isTeacher ? 'Prof. M. Ramirez' : ($isStudent ? 'Juan Dela Cruz' : 'Admin Santos');
$userRole = $isTeacher ? 'Faculty / Instructor' : ($isStudent ? 'Student (BSIT 3-A)' : 'Administrator');
$userInitials = $isTeacher ? 'MR' : ($isStudent ? 'JD' : 'AS');
$userAvatarBg = $isTeacher ? 'bg-emerald-600' : ($isStudent ? 'bg-indigo-600' : 'bg-blue-600');
?>
<!-- Topbar -->
<header id="topbar" class="h-16 px-4 lg:px-7 bg-white/90 backdrop-blur-md border-b border-slate-200/80 sticky top-0 z-30 flex items-center justify-between gap-4">
  <!-- Left: Mobile Hamburger, Brand, & Global Search -->
  <div class="flex items-center gap-3.5 flex-1 max-w-xl">
    <button id="sidebar-toggle" class="lg:hidden p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" onclick="APP.toggleSidebar()" aria-label="Toggle sidebar">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    <!-- Institution / Campus Mark -->
    <div class="hidden sm:flex items-center gap-3">
      <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-blue-700 to-indigo-600 text-white font-black text-xs flex items-center justify-center shadow-xs">
        BCP
      </div>
      <div>
        <div class="text-xs font-bold text-slate-900 leading-tight tracking-tight">Bestlink College of the Philippines</div>
        <div class="text-[10.5px] text-slate-400 font-medium leading-tight">AI Attendance Intelligence System</div>
      </div>
    </div>

    <!-- Quick Global Search -->
    <div class="hidden md:flex items-center flex-1 max-w-xs ml-3">
      <div class="relative w-full">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" placeholder="Quick search student, class, code..." class="w-full pl-9 pr-12 py-1.5 text-xs bg-slate-100/80 hover:bg-slate-100 focus:bg-white border border-slate-200/70 focus:border-teal-500 rounded-xl outline-none text-slate-700 placeholder:text-slate-400 transition" onkeydown="if(event.key==='Enter') APP.toast('Searching system records for: ' + this.value, 'info')">
        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-semibold text-slate-400 px-1.5 py-0.5 rounded bg-white border border-slate-200 shadow-2xs pointer-events-none">⌘K</span>
      </div>
    </div>
  </div>

  <!-- Center: Live Academic Term Pill -->
  <div class="hidden xl:flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-slate-100/90 border border-slate-200/80 text-xs text-slate-600 shadow-2xs">
    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
    <span class="font-bold text-slate-800">1st Semester AY 2025–2026</span>
    <span class="text-slate-300">•</span>
    <span class="text-slate-500 font-medium">Live Campus Mode</span>
  </div>

  <!-- Right: Quick Role Switcher, Notification & Profile -->
  <div class="flex items-center gap-2.5">
    <!-- Quick Role Switch Pill -->
    <div class="flex items-center gap-1 p-1 bg-slate-100/90 rounded-xl border border-slate-200/80 shadow-2xs">
      <a href="<?php echo url('dashboard'); ?>" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition <?php echo $isAdmin ? 'bg-white text-blue-700 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800'; ?>">Admin</a>
      <a href="<?php echo url('teacher/dashboard'); ?>" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition <?php echo $isTeacher ? 'bg-white text-emerald-700 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800'; ?>">Teacher</a>
      <a href="<?php echo url('student/calendar'); ?>" class="px-2.5 py-1 rounded-lg text-xs font-semibold transition <?php echo $isStudent ? 'bg-white text-indigo-700 shadow-xs font-bold' : 'text-slate-500 hover:text-slate-800'; ?>">Student</a>
    </div>

    <!-- Notification Bell -->
    <div class="relative">
      <button id="notif-bell" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" onclick="APP.toggleNotifications()" aria-label="Notifications">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span class="absolute top-1.5 right-1.5 w-4 h-4 bg-rose-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-xs animate-pulse">3</span>
      </button>

      <!-- Notification Dropdown -->
      <div id="notif-dropdown" class="hidden absolute right-0 top-full mt-2 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 flex items-center justify-between bg-slate-50/80">
          <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Campus Alerts</h3>
          <span class="text-[11px] font-semibold text-blue-600 hover:underline cursor-pointer" onclick="APP.toast('All notifications marked as read', 'success')">Mark read</span>
        </div>
        <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
          <div class="p-3.5 hover:bg-slate-50 transition cursor-pointer">
            <div class="flex items-center gap-2 mb-1">
              <span class="badge badge-absent text-[10px]">● Absence Alert</span>
              <span class="text-[10px] text-slate-400">10m ago</span>
            </div>
            <p class="text-xs text-slate-700 font-medium leading-relaxed">Pedro Reyes (BSIT 3-A) missed 3 consecutive class sessions.</p>
          </div>
          <div class="p-3.5 hover:bg-slate-50 transition cursor-pointer">
            <div class="flex items-center gap-2 mb-1">
              <span class="badge badge-tardy text-[10px]">● Tardy Log</span>
              <span class="text-[10px] text-slate-400">25m ago</span>
            </div>
            <p class="text-xs text-slate-700 font-medium leading-relaxed">Maria Santos scanned QR 18 minutes past class start time.</p>
          </div>
        </div>
        <div class="p-2.5 bg-slate-50/80 border-t border-slate-100 text-center">
          <a href="<?php echo url('alerts'); ?>" class="text-xs font-bold text-blue-600 hover:underline">View All Notifications →</a>
        </div>
      </div>
    </div>

    <!-- User Profile Dropdown -->
    <div class="relative">
      <button id="user-menu-btn" class="flex items-center gap-2.5 p-1 rounded-xl hover:bg-slate-100 transition" onclick="APP.toggleUserMenu()">
        <div class="w-8 h-8 rounded-xl <?php echo $userAvatarBg; ?> text-white flex items-center justify-center font-bold text-xs shadow-xs">
          <?php echo $userInitials; ?>
        </div>
        <div class="hidden md:block text-left">
          <div class="text-xs font-bold text-slate-800 leading-tight"><?php echo $userName; ?></div>
          <div class="text-[10px] text-slate-400 leading-tight"><?php echo $userRole; ?></div>
        </div>
        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
      </button>

      <div id="user-dropdown" class="hidden absolute right-0 top-full mt-2 w-56 bg-white rounded-2xl shadow-2xl border border-slate-200 z-50 py-1.5 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 bg-slate-50/80">
          <div class="text-xs font-bold text-slate-800"><?php echo $userName; ?></div>
          <div class="text-[11px] text-slate-500"><?php echo $userRole; ?></div>
        </div>
        <a href="<?php echo url('profile'); ?>" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
          <span>My Profile</span>
        </a>
        <a href="<?php echo url('settings'); ?>" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition">
          <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <span>Preferences &amp; Settings</span>
        </a>
        <div class="border-t border-slate-100 my-1"></div>
        <a href="<?php echo url('auth'); ?>" class="flex items-center gap-2.5 px-4 py-2.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 transition">
          <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          <span>Sign Out</span>
        </a>
      </div>
    </div>
  </div>
</header>

