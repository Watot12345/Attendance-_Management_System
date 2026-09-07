<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Attendance Management System</title>
  <meta name="description" content="Login to the AI-Supported Attendance Management System — Bestlink College of the Philippines">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="/Attendance _Management_System/Project_theme.css">
</head>
<body class="min-h-screen flex items-center justify-center" style="background-color:var(--color-navy-950)">

  <div class="w-full max-w-md px-4">
    <!-- Logo & Institution -->
    <div class="text-center mb-8">
      <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-white/15 flex items-center justify-center">
        <span class="text-2xl font-bold text-white">BCP</span>
      </div>
      <h1 class="text-lg font-semibold text-white">Bestlink College of the Philippines</h1>
    </div>

    <!-- Login Card -->
    <div class="bg-white rounded-lg p-8" style="box-shadow:var(--shadow-modal)">
      <h2 class="text-xl font-semibold mb-1" style="color:var(--color-text-primary)">Attendance Management System</h2>
      <p class="text-sm mb-6" style="color:var(--color-text-secondary)">Sign in to continue</p>

      <form id="login-form" onsubmit="event.preventDefault();">
        <!-- Email -->
        <div class="mb-4">
          <label for="login-email" class="form-label">Email address</label>
          <input type="email" id="login-email" name="email" class="form-input" placeholder="admin@bestlink.edu.ph" required autocomplete="email">
        </div>

        <!-- Password -->
        <div class="mb-6">
          <label for="login-password" class="form-label">Password</label>
          <div class="relative">
            <input type="password" id="login-password" name="password" class="form-input pr-10" placeholder="••••••••" required autocomplete="current-password">
            <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 p-0.5" style="color:var(--color-text-muted)" onclick="togglePasswordVisibility()" aria-label="Show password">
              <svg id="eye-icon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
          </div>
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-primary w-full justify-center btn-lg mb-4" onclick="handleLogin()">
          Sign In
        </button>

        <p class="text-center text-sm" style="color:var(--color-text-muted)">
          Forgot password? Contact your administrator.
        </p>
      </form>
    </div>

    <!-- Footer -->
    <p class="text-center text-sm mt-6" style="color:rgba(255,255,255,0.4)">
      Academic Year 2025–2026
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
      // Static demo — redirect to dashboard
      window.location.href = '/Attendance _Management_System/includes/views/dashboard/index.php';
    }
  </script>
</body>
</html>
