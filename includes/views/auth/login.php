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
        <p class="text-xs text-slate-500 mt-0.5">Sign in with your institutional credentials or choose a quick role:</p>
      </div>

      <!-- Quick Demo Switcher -->
      <div class="mb-5 p-3.5 bg-slate-50 rounded-xl border border-slate-200/80">
        <div class="flex items-center justify-between mb-2.5">
          <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">⚡ 1-Click Demo Accounts</span>
          <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Instant Auth</span>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <button type="button" onclick="quickFill('m.ramirez@bcp.edu.ph', 'teacher123')" class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 hover:border-emerald-500 hover:bg-emerald-50/60 transition group shadow-2xs text-left cursor-pointer">
            <span class="w-7 h-7 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold mb-1.5 group-hover:bg-emerald-600 group-hover:text-white transition">TC</span>
            <span class="text-xs font-bold text-slate-800">Teacher</span>
            <span class="text-[10px] text-slate-400 truncate">Prof. Ramirez</span>
          </button>
          <button type="button" onclick="quickFill('juan.delacruz@bcp.edu.ph', 'student123')" class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 hover:border-indigo-500 hover:bg-indigo-50/60 transition group shadow-2xs text-left cursor-pointer">
            <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold mb-1.5 group-hover:bg-indigo-600 group-hover:text-white transition">ST</span>
            <span class="text-xs font-bold text-slate-800">Student</span>
            <span class="text-[10px] text-slate-400 truncate">Juan Dela Cruz</span>
          </button>
          <button type="button" onclick="quickFill('admin@bcp.edu.ph', 'admin123')" class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-white border border-slate-200 hover:border-blue-500 hover:bg-blue-50/60 transition group shadow-2xs text-left cursor-pointer">
            <span class="w-7 h-7 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold mb-1.5 group-hover:bg-blue-600 group-hover:text-white transition">AD</span>
            <span class="text-xs font-bold text-slate-800">Admin</span>
            <span class="text-[10px] text-slate-400 truncate">Governance</span>
          </button>
        </div>
      </div>

      <!-- Alert Box for Errors/Notifications -->
      <div id="alert-banner" class="hidden mb-4 p-3 rounded-xl text-xs font-medium flex items-start gap-2.5">
        <svg id="alert-icon" class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"></svg>
        <span id="alert-message"></span>
      </div>

      <?php if (!empty($_GET['logged_out'])): ?>
      <div class="mb-4 p-3 rounded-xl text-xs font-medium bg-emerald-50 text-emerald-800 border border-emerald-200 flex items-center gap-2">
        <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>You have been successfully signed out.</span>
      </div>
      <?php endif; ?>

      <!-- Traditional / Live Login Form -->
      <form id="login-form" onsubmit="event.preventDefault(); handleRealLogin();">
        <div class="mb-4">
          <label for="login-identifier" class="form-label text-xs">Institutional Email, Student ID, or Employee ID</label>
          <input type="text" id="login-identifier" name="email" class="form-input text-xs" placeholder="e.g. m.ramirez@bcp.edu.ph or 2026-00123" required autocomplete="username" value="m.ramirez@bcp.edu.ph">
        </div>

        <div class="mb-5">
          <div class="flex items-center justify-between mb-1">
            <label for="login-password" class="form-label text-xs mb-0">Password</label>
            <span class="text-[10px] text-slate-400">Default: <code class="text-slate-600 bg-slate-100 px-1 py-0.5 rounded">teacher123</code> / <code class="text-slate-600 bg-slate-100 px-1 py-0.5 rounded">student123</code></span>
          </div>
          <div class="relative">
            <input type="password" id="login-password" name="password" class="form-input text-xs pr-10" placeholder="••••••••" required autocomplete="current-password" value="teacher123">
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5 text-slate-400 hover:text-slate-600" onclick="togglePasswordVisibility()" aria-label="Show password">
              <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>

        <!-- Submit Button -->
        <button type="submit" id="submit-btn" class="w-full py-2.5 px-4 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-500 hover:to-emerald-500 text-white font-bold text-sm shadow-md hover:shadow-lg transition duration-150 flex items-center justify-center gap-2 mb-3 cursor-pointer">
          <span id="btn-text">Sign In to Portal</span>
          <svg id="btn-arrow" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
          <svg id="btn-spinner" class="hidden animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
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

    function quickFill(identifier, password) {
      document.getElementById('login-identifier').value = identifier;
      document.getElementById('login-password').value = password;
      handleRealLogin();
    }

    function showAlert(msg, isError = true) {
      const banner = document.getElementById('alert-banner');
      const text = document.getElementById('alert-message');
      const icon = document.getElementById('alert-icon');
      
      banner.classList.remove('hidden', 'bg-rose-50', 'text-rose-800', 'border-rose-200', 'bg-emerald-50', 'text-emerald-800', 'border-emerald-200');
      
      if (isError) {
        banner.classList.add('bg-rose-50', 'text-rose-800', 'border', 'border-rose-200');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>';
        icon.className = 'w-4 h-4 shrink-0 mt-0.5 text-rose-600';
      } else {
        banner.classList.add('bg-emerald-50', 'text-emerald-800', 'border', 'border-emerald-200');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>';
        icon.className = 'w-4 h-4 shrink-0 mt-0.5 text-emerald-600';
      }
      
      text.textContent = msg;
    }

    async function handleRealLogin() {
      const identifier = document.getElementById('login-identifier').value.trim();
      const password   = document.getElementById('login-password').value;
      const btn        = document.getElementById('submit-btn');
      const btnText    = document.getElementById('btn-text');
      const btnArrow   = document.getElementById('btn-arrow');
      const btnSpinner = document.getElementById('btn-spinner');

      if (!identifier || !password) {
        showAlert('Please provide both credentials and password.');
        return;
      }

      // Set loading state
      btn.disabled = true;
      btn.classList.add('opacity-75', 'cursor-not-allowed');
      btnText.textContent = 'Authenticating...';
      btnArrow.classList.add('hidden');
      btnSpinner.classList.remove('hidden');

      try {
        const res = await fetch('<?php echo url("api/auth/login"); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ identifier, password })
        });

        const data = await res.json();

        if (res.ok && data.status === 'success') {
          showAlert(data.message || 'Login successful! Redirecting...', false);
          setTimeout(() => {
            window.location.href = data.redirect_url;
          }, 400);
        } else {
          showAlert(data.message || 'Invalid username/email or password.');
          btn.disabled = false;
          btn.classList.remove('opacity-75', 'cursor-not-allowed');
          btnText.textContent = 'Sign In to Portal';
          btnArrow.classList.remove('hidden');
          btnSpinner.classList.add('hidden');
        }
      } catch (err) {
        // Network fallback
        console.error('Login error:', err);
        showAlert('Authentication service encountered an issue. Please try again.');
        btn.disabled = false;
        btn.classList.remove('opacity-75', 'cursor-not-allowed');
        btnText.textContent = 'Sign In to Portal';
        btnArrow.classList.remove('hidden');
        btnSpinner.classList.add('hidden');
      }
    }
  </script>
</body>
</html>
