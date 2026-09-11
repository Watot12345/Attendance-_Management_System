<?php
// Detect active role and subpage from URI
$currentUri = $_SERVER['REQUEST_URI'] ?? '';
$pathOnly = trim(parse_url($currentUri, PHP_URL_PATH) ?? '', '/');

$isTeacher = (str_starts_with($pathOnly, 'teacher/') || $pathOnly === 'teacher');
$isStudent = (str_starts_with($pathOnly, 'student/') || $pathOnly === 'student');
$isAdmin = !$isTeacher && !$isStudent;

if (!function_exists('isActiveLink')) {
    function isActiveLink(string $target, string $current): bool {
        $cleanCurrent = trim(parse_url($current, PHP_URL_PATH) ?? '', '/');
        $cleanTarget = trim($target, '/');
        return ($cleanCurrent === $cleanTarget);
    }
}
?>
<!-- Sidebar Overlay (mobile) -->
<div id="sidebar-overlay" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm z-40 hidden lg:hidden" onclick="APP.closeSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="flex flex-col h-screen sticky top-0 shrink-0 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 border-r border-slate-800/80 text-slate-200 z-40 shadow-2xl">
  <!-- Brand Logo Header -->
  <div class="px-5 py-4 flex items-center justify-between border-b border-slate-800/80 shrink-0">
    <a href="<?php echo url($isTeacher ? 'teacher/dashboard' : ($isStudent ? 'student/calendar' : 'dashboard')); ?>" class="flex items-center gap-3 group">
      <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-700 via-blue-600 to-indigo-500 flex items-center justify-center text-white font-black text-sm shadow-md shadow-blue-500/20 group-hover:scale-105 transition duration-200">
        BCP
      </div>
      <div>
        <div class="text-white text-sm font-bold tracking-tight leading-tight group-hover:text-blue-400 transition">Attendance</div>
        <div class="text-[11px] text-slate-400 font-medium tracking-wide leading-tight">Management System</div>
      </div>
    </a>
  </div>

  <!-- Role Status Pill & 1-Click Switch -->
  <div class="px-4 py-2.5 bg-slate-900/90 border-b border-slate-800/80 shrink-0 flex items-center justify-between">
    <div class="flex items-center gap-2">
      <span class="relative flex h-2 w-2">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full <?php echo $isTeacher ? 'bg-emerald-400' : ($isStudent ? 'bg-indigo-400' : 'bg-blue-400'); ?> opacity-75"></span>
        <span class="relative inline-flex rounded-full h-2 w-2 <?php echo $isTeacher ? 'bg-emerald-500' : ($isStudent ? 'bg-indigo-500' : 'bg-blue-500'); ?>"></span>
      </span>
      <span class="text-[11px] font-bold uppercase tracking-wider text-slate-300">
        <?php echo $isTeacher ? 'Faculty Portal' : ($isStudent ? 'Student Portal' : 'Admin Portal'); ?>
      </span>
    </div>
    <a href="<?php echo url('auth'); ?>" class="text-[11px] font-semibold text-blue-400 hover:text-blue-300 transition px-2 py-0.5 rounded bg-white/5 hover:bg-white/10" title="Switch Portal Role">
      Switch Role
    </a>
  </div>

  <!-- Navigation Links -->
  <nav class="flex-1 py-3.5 px-3 space-y-1 overflow-y-auto no-scrollbar">

    <?php if ($isTeacher): ?>
      <!-- TEACHER NAVIGATION -->
      <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Faculty Menu</div>
      
      <a href="<?php echo url('teacher/dashboard'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('teacher/dashboard', $pathOnly) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo isActiveLink('teacher/dashboard', $pathOnly) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Overview Dashboard</span>
      </a>

      <a href="<?php echo url('teacher/classes'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('teacher/classes', $pathOnly) || isActiveLink('teacher/roster', $pathOnly)) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('teacher/classes', $pathOnly) || isActiveLink('teacher/roster', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
        <span>My Classes &amp; Rosters</span>
      </a>

      <a href="<?php echo url('teacher/import-roster'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('teacher/import-roster', $pathOnly) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo isActiveLink('teacher/import-roster', $pathOnly) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
        <span>Import Class Roster</span>
      </a>

      <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-4">Attendance Operations</div>
      
      <!-- Daily Attendance Marking -->
      <a href="<?php echo url('teacher/daily-attendance'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('teacher/daily-attendance', $pathOnly) || isActiveLink('attendance/daily', $pathOnly)) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('teacher/daily-attendance', $pathOnly) || isActiveLink('attendance/daily', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        <span>Daily Attendance Marking</span>
      </a>

      <!-- Start Live QR Session -->
      <a href="<?php echo url('teacher/live-session'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('teacher/live-session', $pathOnly) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-emerald-400 hover:text-emerald-300 hover:bg-emerald-950/30 border border-emerald-500/20'; ?>">
        <svg class="w-4 h-4 shrink-0 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        <span>Start Live QR Session</span>
      </a>

      <!-- Tardy & Absence Logs -->
      <a href="<?php echo url('teacher/tardy-logs'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('teacher/tardy-logs', $pathOnly) || isActiveLink('alerts/history', $pathOnly)) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('teacher/tardy-logs', $pathOnly) || isActiveLink('alerts/history', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Tardy &amp; Absence Logs</span>
      </a>

      <!-- Submitted Excuse Slips Review -->
      <a href="<?php echo url('teacher/excuse-slips'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('teacher/excuse-slips', $pathOnly) || isActiveLink('dashboard/excuse-slips', $pathOnly) || isActiveLink('excuses/review', $pathOnly)) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('teacher/excuse-slips', $pathOnly) || isActiveLink('dashboard/excuse-slips', $pathOnly) || isActiveLink('excuses/review', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Submitted Excuse Slips</span>
      </a>

      <!-- Perfect Attendance Award Tool -->
      <a href="<?php echo url('teacher/awards'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('teacher/awards', $pathOnly) || isActiveLink('awards', $pathOnly) || isActiveLink('dashboard/tools', $pathOnly)) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('teacher/awards', $pathOnly) || isActiveLink('awards', $pathOnly) || isActiveLink('dashboard/tools', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-amber-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/></svg>
        <span>Perfect Attendance Award Tool</span>
      </a>

      <!-- Attendance History & Audit -->
      <a href="<?php echo url('teacher/attendance-history'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('teacher/attendance-history', $pathOnly) || isActiveLink('teacher/attendance/history', $pathOnly) || isActiveLink('teacher/history', $pathOnly)) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('teacher/attendance-history', $pathOnly) || isActiveLink('teacher/attendance/history', $pathOnly) || isActiveLink('teacher/history', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-emerald-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        <span>Attendance History &amp; Audit</span>
      </a>

    <?php elseif ($isStudent): ?>
      <!-- STUDENT NAVIGATION -->
      <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Student Portal</div>
      
      <!-- Scan Attendance QR -->
      <a href="<?php echo url('student/scanner'); ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('student/scanner', $pathOnly) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-bold' : 'text-indigo-400 hover:text-indigo-300 hover:bg-indigo-950/30 border border-indigo-500/20'; ?>">
        <svg class="w-4 h-4 shrink-0 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
        <span>Scan Attendance QR</span>
      </a>

      <!-- Attendance Calendar (Present, Late, Absent, Excused) -->
      <a href="<?php echo url('student/calendar'); ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('student/calendar', $pathOnly) || isActiveLink('calendar', $pathOnly)) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('student/calendar', $pathOnly) || isActiveLink('calendar', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <span>Attendance Calendar</span>
      </a>

      <!-- Excuse Slip Submission -->
      <a href="<?php echo url('student/excuse-slips'); ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('student/excuse-slips', $pathOnly) || isActiveLink('excuses/submit', $pathOnly)) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('student/excuse-slips', $pathOnly) || isActiveLink('excuses/submit', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        <span>Excuse Slip Submission</span>
      </a>

      <!-- Attendance History -->
      <a href="<?php echo url('student/history'); ?>" class="nav-item flex items-center gap-3 px-3 py-2.5 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('student/history', $pathOnly) ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo isActiveLink('student/history', $pathOnly) ? 'text-white' : 'text-slate-400 group-hover:text-indigo-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
        <span>Attendance History</span>
      </a>

    <?php else: ?>
      <!-- ADMIN PORTAL NAVIGATION -->
      <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Institutional Oversight</div>
      
      <!-- Overview Dashboard -->
      <a href="<?php echo url('dashboard'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('dashboard', $pathOnly) || isActiveLink('admin/dashboard', $pathOnly)) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('dashboard', $pathOnly) || isActiveLink('admin/dashboard', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-blue-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        <span>Overview Dashboard</span>
      </a>

      <!-- Analytics Dashboard -->
      <a href="<?php echo url('dashboard/analytics'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo (isActiveLink('dashboard/analytics', $pathOnly) || isActiveLink('analytics', $pathOnly) || isActiveLink('analytics/dashboard', $pathOnly)) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo (isActiveLink('dashboard/analytics', $pathOnly) || isActiveLink('analytics', $pathOnly) || isActiveLink('analytics/dashboard', $pathOnly)) ? 'text-white' : 'text-slate-400 group-hover:text-blue-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span>Analytics Dashboard</span>
      </a>

      <!-- Alerts to Parents -->
      <a href="<?php echo url('alerts'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('alerts', $pathOnly) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo isActiveLink('alerts', $pathOnly) ? 'text-white' : 'text-slate-400 group-hover:text-blue-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span>Alerts to Parents</span>
      </a>

      <!-- CSV / Excel Export -->
      <a href="<?php echo url('exports'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('exports', $pathOnly) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo isActiveLink('exports', $pathOnly) ? 'text-white' : 'text-slate-400 group-hover:text-blue-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        <span>CSV / Excel Export</span>
      </a>

      <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-4">Master Data &amp; Accounts</div>

      <!-- Student Master Accounts -->
      <a href="<?php echo url('admin/students'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('admin/students', $pathOnly) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo isActiveLink('admin/students', $pathOnly) ? 'text-white' : 'text-slate-400 group-hover:text-blue-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        <span>Student Master Accounts</span>
      </a>

      <!-- Teacher Faculty Master -->
      <a href="<?php echo url('admin/teachers'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('admin/teachers', $pathOnly) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo isActiveLink('admin/teachers', $pathOnly) ? 'text-white' : 'text-slate-400 group-hover:text-blue-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
        <span>Teacher Faculty Master</span>
      </a>

      <!-- System Settings -->
      <a href="<?php echo url('settings'); ?>" class="nav-item flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold transition group <?php echo isActiveLink('settings', $pathOnly) ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-300 hover:text-white hover:bg-slate-800/80'; ?>">
        <svg class="w-4 h-4 shrink-0 <?php echo isActiveLink('settings', $pathOnly) ? 'text-white' : 'text-slate-400 group-hover:text-blue-400'; ?> transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        <span>System Settings</span>
      </a>
    <?php endif; ?>

  </nav>

  <!-- User Account Card Footer -->
  <div class="p-3 bg-slate-950/80 border-t border-slate-800/80 shrink-0">
    <div class="flex items-center gap-3 p-2 rounded-xl bg-slate-900/60 border border-slate-800">
      <div class="w-8 h-8 rounded-lg <?php echo $isTeacher ? 'bg-emerald-600' : ($isStudent ? 'bg-indigo-600' : 'bg-blue-600'); ?> flex items-center justify-center text-white text-xs font-bold shadow-xs">
        <?php echo $isTeacher ? 'MR' : ($isStudent ? 'JD' : 'AS'); ?>
      </div>
      <div class="flex-1 min-w-0">
        <div class="text-xs text-white truncate font-bold">
          <?php echo $isTeacher ? 'Prof. M. Ramirez' : ($isStudent ? 'Juan Dela Cruz' : 'Admin Santos'); ?>
        </div>
        <div class="text-[10px] text-slate-400 truncate">
          <?php echo $isTeacher ? 'Faculty / Instructor' : ($isStudent ? 'BSIT 3-A • 2026-00123' : 'Administrator'); ?>
        </div>
      </div>
      <a href="<?php echo url('auth'); ?>" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition" title="Sign Out">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
      </a>
    </div>
  </div>
</aside>
