<?php
$page_title = 'My Profile';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/controllers/UserController.php';

$user = UserController::getCurrentUserProfile();

require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Breadcrumb & Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">Account Identity</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">User Credentials &amp; Profile</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">My Profile</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            View and manage your personal account identity, contact information, and security password.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <a href="<?php echo url('personal-settings'); ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Personal Preferences →</span>
          </a>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 max-w-6xl">
        
        <!-- Left Column: User Identity Card -->
        <div class="lg:col-span-1 space-y-6">
          <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs text-center relative overflow-hidden">
            <div class="absolute top-0 left-0 right-0 h-24 bg-[#1e3b8a]"></div>

            <div class="relative pt-6">
              <div class="w-24 h-24 mx-auto rounded-2xl bg-white p-1 shadow-md border border-slate-100 flex items-center justify-center mb-3.5">
                <div class="w-full h-full rounded-xl bg-[#1e3b8a] text-white flex items-center justify-center font-black text-2xl shadow-inner" id="profile-avatar-initials">
                  <?php echo htmlspecialchars($user['initials']); ?>
                </div>
              </div>

              <h2 class="text-lg font-black text-slate-900 leading-tight" id="profile-display-name">
                <?php echo htmlspecialchars($user['full_name']); ?>
              </h2>
              <div class="inline-flex items-center gap-1.5 mt-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
                <span><?php echo htmlspecialchars($user['display_role']); ?></span>
              </div>

              <div class="mt-6 pt-5 border-t border-slate-100 text-left space-y-3 text-xs">
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-semibold">Account Status</span>
                  <span class="inline-flex items-center gap-1 text-emerald-700 font-bold bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Active</span>
                  </span>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-semibold">Campus ID</span>
                  <span class="font-mono font-bold text-slate-800">
                    <?php echo htmlspecialchars($user['employee_id'] ?: ($user['student_id'] ?: 'USR-' . $user['user_id'])); ?>
                  </span>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-semibold">Department</span>
                  <span class="font-semibold text-slate-700 text-right truncate max-w-[180px]" title="<?php echo htmlspecialchars($user['department']); ?>">
                    <?php echo htmlspecialchars($user['department']); ?>
                  </span>
                </div>

                <?php if (!empty($user['section'])): ?>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-semibold">Section</span>
                  <span class="font-semibold text-slate-700">
                    <?php echo htmlspecialchars($user['section']); ?>
                  </span>
                </div>
                <?php endif; ?>

                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-semibold">Member Since</span>
                  <span class="font-semibold text-slate-600">
                    <?php echo date('M Y', strtotime($user['created_at'])); ?>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Account Security Summary -->
          <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Authentication Security</h3>
            <div class="space-y-2.5 text-xs text-slate-600">
              <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-emerald-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>Password encryption enabled with secure Bcrypt algorithm.</span>
              </div>
              <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                <span>Role-based access controls active for <?php echo htmlspecialchars($user['display_role']); ?>.</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Profile Edit & Password Forms -->
        <div class="lg:col-span-2 space-y-6">
          
          <!-- Card 1: Personal Details Form -->
          <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">Personal Information</h3>
                <p class="text-xs text-slate-500">Update your official name and contact phone number.</p>
              </div>
            </div>

            <form id="profile-info-form" onsubmit="event.preventDefault(); saveProfileInfo();" class="space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">First Name</label>
                  <input type="text" name="first_name" id="field-first-name" required value="<?php echo htmlspecialchars($user['first_name']); ?>" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Last Name</label>
                  <input type="text" name="last_name" id="field-last-name" required value="<?php echo htmlspecialchars($user['last_name']); ?>" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                </div>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Institutional Email Address</label>
                <div class="relative">
                  <input type="email" value="<?php echo htmlspecialchars($user['email']); ?>" readonly class="w-full px-3.5 py-2.5 text-xs bg-slate-100/80 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed">
                  <span class="absolute right-3 top-2.5 text-[10px] font-bold text-slate-400 bg-slate-200/70 px-2 py-0.5 rounded">Campus Identity</span>
                </div>
                <p class="text-[11px] text-slate-400 mt-1">Official institutional email tied to your campus identity. Contact IT to request change.</p>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Contact Phone / Mobile</label>
                  <input type="tel" name="phone" id="field-phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+63 912 345 6789" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                  <p class="text-[11px] text-slate-400 mt-1">Used for urgent SMS and verification alerts.</p>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Assigned Role</label>
                  <input type="text" value="<?php echo htmlspecialchars($user['display_role']); ?>" readonly class="w-full px-3.5 py-2.5 text-xs bg-slate-100/80 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed">
                </div>
              </div>

              <div class="flex justify-end pt-2">
                <button type="submit" id="btn-save-profile" class="px-5 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#162c69] text-white text-xs font-bold shadow-md shadow-[#1e3b8a]/20 hover:shadow-lg transition flex items-center gap-2 cursor-pointer">
                  <svg class="w-4 h-4 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                  <span>Save Profile Changes</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Card 2: Password Security Form -->
          <div class="bg-white rounded-2xl p-6 sm:p-7 border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3 mb-5">
              <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">Change Account Password</h3>
                <p class="text-xs text-slate-500">Ensure your account is using a long, strong password.</p>
              </div>
            </div>

            <form id="password-form" onsubmit="event.preventDefault(); updatePassword();" class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Current Password</label>
                <input type="password" name="current_password" id="field-current-pwd" required placeholder="Enter current account password" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">New Password</label>
                  <input type="password" name="new_password" id="field-new-pwd" minlength="8" required placeholder="Minimum 8 characters" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 mb-1.5">Confirm New Password</label>
                  <input type="password" name="confirm_password" id="field-confirm-pwd" minlength="8" required placeholder="Re-type new password" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                </div>
              </div>

              <div class="flex justify-end pt-2">
                <button type="submit" id="btn-update-pwd" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-black text-white text-xs font-bold shadow-md hover:shadow-lg transition flex items-center gap-2 cursor-pointer">
                  <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>Update Password</span>
                </button>
              </div>
            </form>
          </div>

        </div>
      </div>
    </main>
  </div>
</div>

<script>
async function saveProfileInfo() {
  const btn = document.getElementById('btn-save-profile');
  const firstName = document.getElementById('field-first-name')?.value.trim();
  const lastName  = document.getElementById('field-last-name')?.value.trim();
  const phone     = document.getElementById('field-phone')?.value.trim();

  if (!firstName || !lastName) {
    APP.toast('Please provide both first and last name.', 'error');
    return;
  }

  if (btn) btn.disabled = true;

  try {
    const res = await fetch(window.url('api/user/profile/update'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        first_name: firstName,
        last_name: lastName,
        phone: phone
      })
    });

    const result = await res.json();
    if (result.success || result.status === 'success') {
      APP.toast(result.message || 'Profile updated successfully!', 'success');
      
      // Update displayed name & avatar initials on the page
      const dispName = document.getElementById('profile-display-name');
      const dispInit = document.getElementById('profile-avatar-initials');
      if (dispName && result.data?.full_name) {
        dispName.textContent = result.data.full_name;
      }
      if (dispInit && result.data?.initials) {
        dispInit.textContent = result.data.initials;
      }

      // Update topbar menu if elements exist
      const topMenuText = document.querySelector('#user-menu-btn .text-slate-800');
      if (topMenuText && result.data?.full_name) {
        topMenuText.textContent = result.data.full_name;
      }
      const topMenuInit = document.querySelector('#user-menu-btn .w-8.h-8');
      if (topMenuInit && result.data?.initials) {
        topMenuInit.textContent = result.data.initials;
      }
    } else {
      APP.toast(result.message || 'Failed to update profile.', 'error');
    }
  } catch (err) {
    APP.toast('Network or server error while updating profile.', 'error');
  } finally {
    if (btn) btn.disabled = false;
  }
}

async function updatePassword() {
  const btn = document.getElementById('btn-update-pwd');
  const currentPwd = document.getElementById('field-current-pwd')?.value;
  const newPwd     = document.getElementById('field-new-pwd')?.value;
  const confirmPwd = document.getElementById('field-confirm-pwd')?.value;

  if (!currentPwd || !newPwd) {
    APP.toast('Please fill out all password fields.', 'error');
    return;
  }

  if (newPwd.length < 8) {
    APP.toast('New password must be at least 8 characters.', 'error');
    return;
  }

  if (newPwd !== confirmPwd) {
    APP.toast('New password and confirmation do not match.', 'error');
    return;
  }

  if (btn) btn.disabled = true;

  try {
    const res = await fetch(window.url('api/user/password/update'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        current_password: currentPwd,
        new_password: newPwd,
        confirm_password: confirmPwd
      })
    });

    const result = await res.json();
    if (result.success || result.status === 'success') {
      APP.toast(result.message || 'Password updated successfully!', 'success');
      document.getElementById('password-form')?.reset();
    } else {
      APP.toast(result.message || 'Failed to change password.', 'error');
    }
  } catch (err) {
    APP.toast('Error connecting to authentication service.', 'error');
  } finally {
    if (btn) btn.disabled = false;
  }
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
