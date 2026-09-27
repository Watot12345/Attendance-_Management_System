<?php
/**
 * Bestlink College of the Philippines - Faculty Account Activation View
 * Provides visual confirmation when a teacher activates their account via the email link.
 */

$status  = $activationStatus ?? 'invalid';
$message = $activationMessage ?? 'Please check your activation link.';
$teacher = $teacher ?? [];
$fullName = htmlspecialchars($teacher['full_name'] ?? 'Faculty Member');
$email = htmlspecialchars($teacher['email'] ?? '');
$employeeId = htmlspecialchars($teacher['employee_id'] ?? '');
$examplePass = htmlspecialchars($teacher['example_pass'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Activation — Bestlink College of the Philippines</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            sans: ['"Plus Jakarta Sans"', 'sans-serif'],
            mono: ['"JetBrains Mono"', 'monospace'],
          },
          colors: {
            brand: {
              50: '#eff6ff',
              100: '#dbeafe',
              500: '#3b82f6',
              800: '#1e40af',
              900: '#1e3b8a',
              950: '#172554',
            }
          }
        }
      }
    }
  </script>
  <style>
    @keyframes pulse-subtle {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(1.05); opacity: 0.85; }
    }
    .pulse-animation {
      animation: pulse-subtle 3s ease-in-out infinite;
    }
  </style>
</head>
<body class="h-full font-sans antialiased text-slate-800 flex flex-col justify-between selection:bg-brand-900 selection:text-white">

  <!-- Header Branding -->
  <header class="w-full bg-[#1e3b8a] text-white border-b border-blue-900 shadow-md">
    <div class="max-w-4xl mx-auto px-4 py-4 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-white/10 backdrop-blur-xs flex items-center justify-center font-bold text-white border border-white/20 shadow-xs">
          BCP
        </div>
        <div>
          <h1 class="text-sm font-extrabold tracking-tight uppercase">Bestlink College of the Philippines</h1>
          <p class="text-[11px] text-blue-200">Attendance Management &amp; Faculty Portal</p>
        </div>
      </div>
      <a href="<?= url('login') ?>" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-white/10 hover:bg-white/20 transition-colors text-white">
        <span>Sign-In Portal</span>
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
      </a>
    </div>
  </header>

  <!-- Main Card Container -->
  <main class="flex-1 flex items-center justify-center p-4 py-10">
    <div class="w-full max-w-lg bg-white rounded-3xl shadow-xl shadow-slate-200/60 border border-slate-200/80 overflow-hidden">
      
      <!-- Top Decorative Banner -->
      <div class="h-2 w-full <?= ($status === 'success') ? 'bg-gradient-to-r from-emerald-500 via-teal-500 to-[#1e3b8a]' : (($status === 'already_active') ? 'bg-gradient-to-r from-blue-500 to-[#1e3b8a]' : 'bg-gradient-to-r from-amber-500 to-rose-500') ?>"></div>

      <div class="p-6 sm:p-8">

        <?php if ($status === 'success'): ?>
          <!-- SUCCESS STATE -->
          <div class="text-center">
            <div class="w-16 h-16 rounded-2xl bg-emerald-50 border border-emerald-200/60 text-emerald-600 flex items-center justify-center mx-auto mb-4 shadow-sm pulse-animation">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
              </svg>
            </div>

            <div class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-emerald-50 text-emerald-700 border border-emerald-200/60 mb-2">
              Account Activated
            </div>

            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
              Welcome to BCP Faculty!
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-sm mx-auto">
              Hello, <strong class="text-slate-800"><?= $fullName ?></strong>. Your faculty account has been successfully verified and activated.
            </p>
          </div>

          <!-- Credentials Summary Card -->
          <div class="mt-6 rounded-2xl bg-slate-50 border border-slate-200 p-4 sm:p-5 space-y-3">
            <div class="text-[11px] font-bold text-[#1e3b8a] uppercase tracking-wider flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              <span>Faculty Sign-In Credentials</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
              <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                <span class="block text-[10px] text-slate-400 font-semibold uppercase">Email Address</span>
                <span class="font-bold text-slate-800 break-all"><?= $email ?></span>
              </div>
              <?php if (!empty($employeeId)): ?>
              <div class="p-2.5 rounded-xl bg-white border border-slate-200/80">
                <span class="block text-[10px] text-slate-400 font-semibold uppercase">Employee ID</span>
                <span class="font-mono font-bold text-[#1e3b8a]"><?= $employeeId ?></span>
              </div>
              <?php endif; ?>
            </div>

            <?php if (!empty($examplePass)): ?>
            <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200/80 text-amber-900 text-xs">
              <div class="font-bold flex items-center gap-1">
                <span>Default Password Formula:</span>
              </div>
              <p class="text-[11px] text-amber-800 mt-0.5">
                Use your default password: <code class="font-mono font-bold bg-amber-200/60 px-1.5 py-0.5 rounded text-amber-900"><?= $examplePass ?></code>
              </p>
            </div>
            <?php endif; ?>
          </div>

          <!-- Primary CTA Button -->
          <div class="mt-6">
            <a href="<?= url('login') ?>" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-[#1e3b8a] hover:bg-blue-900 text-white font-bold text-sm shadow-lg shadow-blue-900/20 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
              <span>Proceed to Sign-In Portal</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
          </div>

        <?php elseif ($status === 'already_active'): ?>
          <!-- ALREADY ACTIVE STATE -->
          <div class="text-center">
            <div class="w-16 h-16 rounded-2xl bg-blue-50 border border-blue-200/60 text-blue-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>

            <div class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200/60 mb-2">
              Already Active
            </div>

            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
              Account Already Activated
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-sm mx-auto">
              Your faculty account is already active and ready to use. You can proceed directly to the sign-in portal.
            </p>
          </div>

          <div class="mt-8">
            <a href="<?= url('login') ?>" class="w-full inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-2xl bg-[#1e3b8a] hover:bg-blue-900 text-white font-bold text-sm shadow-lg shadow-blue-900/20 transition-all hover:scale-[1.01] active:scale-[0.99] cursor-pointer">
              <span>Sign In Now</span>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
            </a>
          </div>

        <?php elseif ($status === 'expired'): ?>
          <!-- EXPIRED STATE -->
          <div class="text-center">
            <div class="w-16 h-16 rounded-2xl bg-amber-50 border border-amber-200/60 text-amber-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>

            <div class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/60 mb-2">
              Link Expired
            </div>

            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
              Activation Link Expired
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-sm mx-auto">
              This activation token has expired (links are valid for 48 hours). Enter your email below to request a fresh activation link.
            </p>
          </div>

          <!-- Interactive Resend Form -->
          <form id="resend-form" onsubmit="handleResendActivation(event)" class="mt-6 space-y-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Institutional Email or Employee ID</label>
              <input type="text" id="resend-identifier" required value="<?= $email ?: $employeeId ?>" placeholder="e.g. yourname@gmail.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
            </div>

            <div id="resend-feedback" class="hidden p-3 rounded-xl text-xs"></div>

            <button type="submit" id="resend-btn" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#1e3b8a] hover:bg-blue-900 text-white font-bold text-xs shadow-md transition-all cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span>Resend Activation Email</span>
            </button>
          </form>

          <div class="mt-4 text-center">
            <a href="<?= url('login') ?>" class="text-xs font-semibold text-slate-500 hover:text-[#1e3b8a] transition-colors">
              &larr; Return to Sign-In Portal
            </a>
          </div>

        <?php else: ?>
          <!-- INVALID / CORRUPTED STATE -->
          <div class="text-center">
            <div class="w-16 h-16 rounded-2xl bg-rose-50 border border-rose-200/60 text-rose-600 flex items-center justify-center mx-auto mb-4 shadow-sm">
              <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
              </svg>
            </div>

            <div class="inline-block px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-rose-50 text-rose-700 border border-rose-200/60 mb-2">
              Invalid Token
            </div>

            <h2 class="text-2xl font-extrabold text-slate-900 tracking-tight">
              Invalid Activation Link
            </h2>
            <p class="text-xs sm:text-sm text-slate-500 mt-2 max-w-sm mx-auto">
              <?= htmlspecialchars($message) ?>
            </p>
          </div>

          <!-- Interactive Resend Form -->
          <form id="resend-form" onsubmit="handleResendActivation(event)" class="mt-6 space-y-3">
            <div>
              <label class="block text-[11px] font-bold text-slate-600 uppercase tracking-wider mb-1">Enter Email or Employee ID to Resend</label>
              <input type="text" id="resend-identifier" required placeholder="e.g. yourname@gmail.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 text-xs focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] outline-none">
            </div>

            <div id="resend-feedback" class="hidden p-3 rounded-xl text-xs"></div>

            <button type="submit" id="resend-btn" class="w-full inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-[#1e3b8a] hover:bg-blue-900 text-white font-bold text-xs shadow-md transition-all cursor-pointer">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
              <span>Dispatch New Activation Link</span>
            </button>
          </form>

          <div class="mt-4 text-center">
            <a href="<?= url('login') ?>" class="text-xs font-semibold text-slate-500 hover:text-[#1e3b8a] transition-colors">
              &larr; Back to Sign-In Portal
            </a>
          </div>
        <?php endif; ?>

      </div>

      <!-- Footer Help Note -->
      <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 text-center text-[11px] text-slate-400">
        Need assistance? Contact the <strong class="text-slate-600">Faculty &amp; Academic Affairs Office</strong> or email <a href="mailto:support@bcp.edu.ph" class="text-[#1e3b8a] hover:underline font-semibold">support@bcp.edu.ph</a>.
      </div>
    </div>
  </main>

  <!-- Interactive JavaScript for Resend Actions -->
  <script>
    async function handleResendActivation(e) {
      e.preventDefault();
      const identifier = document.getElementById('resend-identifier').value.trim();
      const btn = document.getElementById('resend-btn');
      const feedback = document.getElementById('resend-feedback');

      if (!identifier) return;

      btn.disabled = true;
      btn.innerHTML = `<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg> <span>Sending...</span>`;
      feedback.className = 'hidden';

      try {
        const res = await fetch('<?= url("api/auth/resend-activation") ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ identifier })
        });
        const data = await res.json();

        if (res.ok && data.status === 'success') {
          feedback.className = 'p-3 rounded-xl text-xs bg-emerald-50 border border-emerald-200 text-emerald-800 font-semibold';
          feedback.textContent = data.message || 'Activation email sent successfully! Please check your Gmail.';
        } else if (data.status === 'already_active') {
          feedback.className = 'p-3 rounded-xl text-xs bg-blue-50 border border-blue-200 text-blue-800 font-semibold';
          feedback.innerHTML = `${data.message} <a href="<?= url('login') ?>" class="underline font-bold ml-1">Go to Sign In &rarr;</a>`;
        } else {
          feedback.className = 'p-3 rounded-xl text-xs bg-rose-50 border border-rose-200 text-rose-800 font-semibold';
          feedback.textContent = data.message || 'Could not send activation link. Please try again.';
        }
      } catch (err) {
        feedback.className = 'p-3 rounded-xl text-xs bg-rose-50 border border-rose-200 text-rose-800 font-semibold';
        feedback.textContent = 'Network or server error. Please try again later.';
      } finally {
        btn.disabled = false;
        btn.innerHTML = `<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> <span>Resend Activation Email</span>`;
      }
    }
  </script>
</body>
</html>
