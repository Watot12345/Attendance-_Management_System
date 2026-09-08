<?php require_once dirname(__DIR__, 2) . '/core/Router.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Attendance Management System</title>
  <meta name="description" content="Login to the AI-Supported Attendance Management System — Bestlink College of the Philippines">
  <link rel="stylesheet" href="<?php echo url('Project_theme.css'); ?>">
  <link rel="stylesheet" href="<?php echo url('assets/css/output.css'); ?>">
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden bg-gradient-to-br from-slate-950 via-slate-900 to-indigo-950 text-slate-100 p-4">

  <!-- Background Decorative Glowing Orbs -->
  <div class="absolute top-1/4 -left-20 w-96 h-96 bg-blue-600/15 rounded-full blur-3xl pointer-events-none"></div>
  <div class="absolute bottom-1/4 -right-20 w-96 h-96 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>

  <div class="w-full max-w-md relative z-10 py-8">
    <!-- Logo & Institution Header -->
    <div class="text-center mb-6">
      <div class="w-16 h-16 mx-auto mb-3 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white font-black text-2xl shadow-xl shadow-blue-500/30">
        BCP
      </div>
      <h1 class="text-lg font-bold text-white tracking-tight">Bestlink College of the Philippines</h1>
      <p class="text-xs text-slate-400 font-medium mt-0.5">AI-Supported Attendance Intelligence Portal</p>
    </div>

    <!-- Login Card with Glassmorphism -->
    <div class="bg-white rounded-2xl p-7 sm:p-8 shadow-2xl border border-slate-200/80 text-slate-800">
      <div class="mb-5">
        <h2 class="text-xl font-extrabold text-slate-900 font-display">System Authentication</h2>
        <p class="text-xs text-slate-500 mt-0.5">Sign in to your institutional portal or select a quick demo role:</p>
      </div>

      <!-- 1-Click Interactive Role Switcher Demo -->
      <div class="mb-6 p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
        <div class="flex items-center justify-between mb-2.5">
          <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">⚡ 1-Click Role Switcher Demo</span>
          <span class="text-[10px] font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Live Mockup</span>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <a href="<?php echo url('teacher/dashboard'); ?>" class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/60 transition group shadow-2xs">
            <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold mb-1.5 group-hover:bg-emerald-600 group-hover:text-white transition">TC</span>
            <span class="text-xs font-bold text-slate-800">Teacher</span>
            <span class="text-[10px] text-slate-400 truncate">Prof. Ramirez</span>
          </a>
          <a href="<?php echo url('student/dashboard'); ?>" class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50/60 transition group shadow-2xs">
            <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold mb-1.5 group-hover:bg-indigo-600 group-hover:text-white transition">ST</span>
            <span class="text-xs font-bold text-slate-800">Student</span>
            <span class="text-[10px] text-slate-400 truncate">Juan Dela Cruz</span>
          </a>
          <a href="<?php echo url('dashboard'); ?>" class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 hover:border-blue-500 hover:bg-blue-50/60 transition group shadow-2xs">
            <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold mb-1.5 group-hover:bg-blue-600 group-hover:text-white transition">AD</span>
            <span class="text-xs font-bold text-slate-800">Admin</span>
            <span class="text-[10px] text-slate-400 truncate">Governance</span>
          </a>
        </div>
      </div>

      <!-- Traditional Login Form -->
      <form id="login-form" onsubmit="event.preventDefault(); handleLogin();">
        <div class="mb-4">
          <label for="login-email" class="form-label text-xs">Institutional Email</label>
          <input type="email" id="login-email" name="email" class="form-input text-xs" placeholder="faculty@bestlink.edu.ph" required autocomplete="email" value="teacher@bestlink.edu.ph">
        </div>

        <div class="mb-5">
          <label for="login-password" class="form-label text-xs">Password</label>
          <div class="relative">
            <input type="password" id="login-password" name="password" class="form-input text-xs pr-10" placeholder="••••••••" required autocomplete="current-password" value="password123">
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600" onclick="togglePasswordVisibility()" aria-label="Show password">
              <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-bold text-sm shadow-md hover:shadow-lg transition duration-150 flex items-center justify-center gap-2 mb-3">
          <span>Sign In to System</span>
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
        </button>

        <p class="text-center text-[11px] text-slate-400">
          Need account assistance? Contact your department administrator.
        </p>
      </form>
    </div>

    <!-- Footer Copyright -->
    <p class="text-center text-xs mt-6 text-slate-500 font-medium">
      1st Semester AY 2025–2026 • AI-Supported Attendance System
    </p>
  </div>

  <script>
    function togglePasswordVisibility() {
      const input = document.getElementById('login-password');
      const icon = document.getElementById('eye-icon');
      if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L6.11 6.11m3.768 3.768L6.11 6.11m0 0L3 3m3.11 3.11l4.242 4.243m6.768 6.768L21 21m-3.11-3.11l-4.243-4.243m4.243 4.243l-4.242-4.242"/>';
      } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
      }
    }

    function handleLogin() {
      const email = document.getElementById('login-email').value.toLowerCase();
      if (email.includes('teacher') || email.includes('ramirez') || email.includes('faculty')) {
        window.location.href = '<?php echo url("teacher/dashboard"); ?>';
      } else if (email.includes('student') || email.includes('juan')) {
        window.location.href = '<?php echo url("student/calendar"); ?>';
      } else {
        window.location.href = '<?php echo url("dashboard"); ?>';
      }
    }
  </script>
</body>
</html>
