<?php
$page_title = 'Personal Settings';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/controllers/SettingsController.php';
require_once dirname(__DIR__, 2) . '/controllers/UserController.php';

$user = UserController::getCurrentUserProfile();
$prefs = SettingsController::getUserPreferences($user['user_id']);
$isAdmin = ($user['role'] === 'admin');

require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">Personal Workspace</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Personal Settings</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Customize your personal notification channels, display density, sound alerts, and default export formats.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="savePersonalPrefs()" id="btn-save-prefs-top" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 hover:shadow-lg transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Save Preferences</span>
          </button>
        </div>
      </div>

      <?php if ($isAdmin): ?>
      <!-- Admin Notice: Separation between Personal and System Settings -->
      <div class="mb-6 p-4 rounded-2xl bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-blue-600 text-white flex items-center justify-center font-bold text-sm shrink-0 shadow-xs">
            ⚙
          </div>
          <div>
            <h4 class="text-xs font-bold text-blue-950">Looking for Institutional System Settings?</h4>
            <p class="text-[11px] text-blue-700">These settings are personal to your account. To edit campus hours, active term, or global tardy rules, visit System Settings.</p>
          </div>
        </div>
        <a href="<?php echo url('settings'); ?>" class="px-3.5 py-1.5 rounded-xl bg-white hover:bg-blue-50 border border-blue-200 text-blue-700 text-xs font-bold shadow-2xs transition shrink-0">
          Open System Settings →
        </a>
      </div>
      <?php endif; ?>

      <form id="personal-prefs-form" onsubmit="event.preventDefault(); savePersonalPrefs();" class="max-w-4xl space-y-6">

        <!-- 1. Personal Notifications -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900">Personal Notifications</h3>
              <p class="text-xs text-slate-500">Configure how and when you receive automated updates and alerts.</p>
            </div>
          </div>

          <div class="space-y-3">
            <label class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 hover:bg-slate-100/80 transition border border-slate-200 cursor-pointer">
              <input type="checkbox" name="notify_email" value="1" <?php echo ($prefs['notify_email'] ?? '1') === '1' ? 'checked' : ''; ?> class="mt-0.5 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
              <div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">Email Attendance Notifications</span>
                <p class="text-[11px] text-slate-500">Send an email alert to <?php echo htmlspecialchars($user['email']); ?> for recorded check-ins and absences.</p>
              </div>
            </label>

            <label class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 hover:bg-slate-100/80 transition border border-slate-200 cursor-pointer">
              <input type="checkbox" name="notify_sms" value="1" <?php echo ($prefs['notify_sms'] ?? '0') === '1' ? 'checked' : ''; ?> class="mt-0.5 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
              <div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">SMS Urgent Notifications</span>
                <p class="text-[11px] text-slate-500">Receive SMS notifications for critical alerts (requires verified mobile phone number in profile).</p>
              </div>
            </label>

            <label class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 hover:bg-slate-100/80 transition border border-slate-200 cursor-pointer">
              <input type="checkbox" name="notify_excuses" value="1" <?php echo ($prefs['notify_excuses'] ?? '1') === '1' ? 'checked' : ''; ?> class="mt-0.5 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
              <div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">Excuse Slip Status Updates</span>
                <p class="text-[11px] text-slate-500">Get notified immediately when an excuse slip is approved, rejected, or submitted for review.</p>
              </div>
            </label>
          </div>
        </div>

        <!-- 2. Workspace & Display Behavior -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900">Workspace &amp; Interface Preferences</h3>
              <p class="text-xs text-slate-500">Adjust interface layout density, sound chimes, and dashboard behavior.</p>
            </div>
          </div>

          <div class="space-y-3">
            <label class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 hover:bg-slate-100/80 transition border border-slate-200 cursor-pointer">
              <input type="checkbox" name="sound_effects" value="1" <?php echo ($prefs['sound_effects'] ?? '1') === '1' ? 'checked' : ''; ?> class="mt-0.5 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
              <div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">Sound Effects on Attendance Scan</span>
                <p class="text-[11px] text-slate-500">Play an audible chime confirmation when scanning or recording attendance.</p>
              </div>
            </label>

            <label class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 hover:bg-slate-100/80 transition border border-slate-200 cursor-pointer">
              <input type="checkbox" name="compact_tables" value="1" <?php echo ($prefs['compact_tables'] ?? '0') === '1' ? 'checked' : ''; ?> class="mt-0.5 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
              <div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">Compact Table View</span>
                <p class="text-[11px] text-slate-500">Reduce table row padding for denser data scanning on larger desktop displays.</p>
              </div>
            </label>

            <label class="flex items-start gap-3.5 p-3.5 rounded-xl bg-slate-50/70 hover:bg-slate-100/80 transition border border-slate-200 cursor-pointer">
              <input type="checkbox" name="auto_refresh_feed" value="1" <?php echo ($prefs['auto_refresh_feed'] ?? '1') === '1' ? 'checked' : ''; ?> class="mt-0.5 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
              <div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">Auto-Refresh Live Feeds</span>
                <p class="text-[11px] text-slate-500">Automatically stream live check-in updates on the overview dashboard every 12 seconds.</p>
              </div>
            </label>
          </div>
        </div>

        <!-- 3. Export Defaults & Session Security -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900">Reports &amp; Session Inactivity</h3>
              <p class="text-xs text-slate-500">Default download format and session timeout protection.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Preferred Export Download Format</label>
              <select name="preferred_export" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                <option value="xlsx" <?php echo ($prefs['preferred_export'] ?? 'xlsx') === 'xlsx' ? 'selected' : ''; ?>>Microsoft Excel (.xlsx)</option>
                <option value="csv" <?php echo ($prefs['preferred_export'] ?? 'xlsx') === 'csv' ? 'selected' : ''; ?>>Comma Separated Values (.csv)</option>
              </select>
              <p class="text-[11px] text-slate-400 mt-1">Default format applied when downloading attendance summaries.</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Session Timeout Warning</label>
              <select name="session_warning" class="w-full px-3.5 py-2.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                <option value="1" <?php echo ($prefs['session_warning'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled (Alert 5 mins before expiry)</option>
                <option value="0" <?php echo ($prefs['session_warning'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled (Silent expiration)</option>
              </select>
              <p class="text-[11px] text-slate-400 mt-1">Warns you before your security session token times out.</p>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex justify-end gap-3 pt-2">
          <button type="submit" id="btn-save-prefs-bottom" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/20 hover:shadow-lg transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Save Preferences</span>
          </button>
        </div>
      </form>
    </main>
  </div>
</div>

<script>
async function savePersonalPrefs() {
  const form = document.getElementById('personal-prefs-form');
  const formData = new FormData(form);
  const data = {};

  // Boolean checkboxes: send '1' if checked, '0' if unchecked
  const boolFields = ['notify_email', 'notify_sms', 'notify_excuses', 'sound_effects', 'compact_tables', 'auto_refresh_feed'];
  boolFields.forEach(f => {
    data[f] = formData.has(f) ? '1' : '0';
  });

  data['preferred_export'] = formData.get('preferred_export') || 'xlsx';
  data['session_warning']  = formData.get('session_warning') || '1';

  const btnTop = document.getElementById('btn-save-prefs-top');
  const btnBottom = document.getElementById('btn-save-prefs-bottom');
  if (btnTop) btnTop.disabled = true;
  if (btnBottom) btnBottom.disabled = true;

  try {
    const res = await fetch(window.url('api/user/preferences/save'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await res.json();
    if (result.success || result.status === 'success') {
      APP.toast('Personal settings saved successfully!', 'success');
    } else {
      APP.toast(result.message || 'Failed to save settings.', 'error');
    }
  } catch (err) {
    APP.toast('An error occurred while saving personal settings.', 'error');
  } finally {
    if (btnTop) btnTop.disabled = false;
    if (btnBottom) btnBottom.disabled = false;
  }
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

