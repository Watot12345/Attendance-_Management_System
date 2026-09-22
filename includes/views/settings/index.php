<?php
$page_title = 'System Settings';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/controllers/SettingsController.php';

$settings = SettingsController::getAll();
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Breadcrumb / Portal indicator -->
      <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1.5">
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">General Preferences &amp; Settings</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">System Settings</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Configure automated guardian notifications, default export formats, campus hours, and academic term schedules.
          </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center gap-2.5 shrink-0">
          <a href="<?php echo url('personal-settings'); ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span>My Personal Settings</span>
          </a>
          <button type="button" onclick="saveSettings()" id="save-btn-top" class="px-5 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#162c69] text-white text-xs font-bold shadow-md shadow-[#1e3b8a]/20 hover:shadow-lg transition flex items-center gap-2 group cursor-pointer">
            <svg class="w-4 h-4 text-sky-200 group-hover:scale-110 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Save System Changes</span>
          </button>
        </div>
      </div>

      <!-- Notice: Institutional Settings vs Personal Settings -->
      <div class="mb-6 p-4 rounded-2xl bg-slate-50 border border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs shrink-0">
            🏛
          </div>
          <div>
            <h4 class="text-xs font-bold text-slate-800">Campus Institutional Configuration</h4>
            <p class="text-[11px] text-slate-500">Parameters modified here affect all faculty, students, automated scans, and reports campus-wide.</p>
          </div>
        </div>
        <a href="<?php echo url('personal-settings'); ?>" class="text-xs font-bold text-blue-600 hover:underline shrink-0">
          Edit Personal Preferences →
        </a>
      </div>

      <form id="system-settings-form" onsubmit="event.preventDefault(); saveSettings();" class="max-w-4xl space-y-6">
        
        <!-- Automated Notifications & Parent Alerts -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
              </svg>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900">Automated Notifications &amp; Alerts</h3>
              <p class="text-xs text-slate-500">Manage automated delivery of student attendance updates to parents and guardians.</p>
            </div>
          </div>

          <div class="space-y-4">
            <label class="flex items-center gap-3.5 cursor-pointer p-3.5 rounded-xl bg-slate-50/70 hover:bg-slate-100/80 transition border border-slate-200">
              <input type="checkbox" name="alert_enabled" value="1" <?= ($settings['alert_enabled'] ?? '') === '1' ? 'checked' : '' ?> class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
              <div>
                <span class="text-xs sm:text-sm font-semibold text-slate-800">Enable Automated Guardian Alerts</span>
                <p class="text-[11px] sm:text-xs text-slate-500">Automatically send notifications when students are recorded for attendance events.</p>
              </div>
            </label>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Notification Delivery Channel</label>
                <select name="alert_channel" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                  <option value="" <?= !isset($settings['alert_channel']) ? 'selected' : '' ?>>— Select Delivery Channel —</option>
                  <option value="both" <?= ($settings['alert_channel'] ?? '') === 'both' ? 'selected' : '' ?>>Both (SMS &amp; Email)</option>
                  <option value="sms" <?= ($settings['alert_channel'] ?? '') === 'sms' ? 'selected' : '' ?>>SMS Only</option>
                  <option value="email" <?= ($settings['alert_channel'] ?? '') === 'email' ? 'selected' : '' ?>>Email Only</option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">Preferred delivery method for outbound alerts.</p>
              </div>

              <div>
                <label class="block text-xs font-bold text-slate-700 mb-1.5">Notification Trigger Criteria</label>
                <select name="alert_absent_only" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                  <option value="" <?= !isset($settings['alert_absent_only']) ? 'selected' : '' ?>>— Select Trigger Criteria —</option>
                  <option value="1" <?= ($settings['alert_absent_only'] ?? '') === '1' ? 'selected' : '' ?>>Notify on Absent &amp; Tardy Only</option>
                  <option value="0" <?= ($settings['alert_absent_only'] ?? '') === '0' ? 'selected' : '' ?>>Notify on Every Attendance Event</option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1">When automated notifications should be dispatched.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Reporting & Data Export Preferences -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900">Reports &amp; Data Export Preferences</h3>
              <p class="text-xs text-slate-500">Default settings for generated summaries and downloadable files.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Default Export File Format</label>
              <select name="export_default_format" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                <option value="" <?= !isset($settings['export_default_format']) ? 'selected' : '' ?>>— Select Format —</option>
                <option value="xlsx" <?= ($settings['export_default_format'] ?? '') === 'xlsx' ? 'selected' : '' ?>>Excel Spreadsheet (.xlsx)</option>
                <option value="csv" <?= ($settings['export_default_format'] ?? '') === 'csv' ? 'selected' : '' ?>>Comma Separated Values (.csv)</option>
              </select>
              <p class="text-[11px] text-slate-400 mt-1">Standard format for one-click reports and faculty rosters.</p>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Default Analytics Date Range</label>
              <div class="flex items-center gap-2">
                <input type="number" name="analytics_date_range_default" class="w-32 px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition" min="1" max="365" placeholder="30" value="<?= htmlspecialchars($settings['analytics_date_range_default'] ?? '') ?>">
                <span class="text-xs text-slate-500 font-semibold">days</span>
              </div>
              <p class="text-[11px] text-slate-400 mt-1">Rolling timeframe for attendance trends and summaries.</p>
            </div>
          </div>
        </div>

        <!-- Campus Schedule & Academic Term -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <div class="flex items-center gap-3 mb-4">
            <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
            </div>
            <div>
              <h3 class="text-sm sm:text-base font-bold text-slate-900">Campus Hours &amp; Term Schedule</h3>
              <p class="text-xs text-slate-500">Operating hours and active academic period definitions.</p>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Campus Opening Time</label>
              <input type="time" name="library_open_time" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition" value="<?= htmlspecialchars($settings['library_open_time'] ?? '') ?>">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Campus Closing Time</label>
              <input type="time" name="library_close_time" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition" value="<?= htmlspecialchars($settings['library_close_time'] ?? '') ?>">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Tardy Grace Period (Minutes)</label>
              <input type="number" name="tardy_threshold_minutes" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition" placeholder="15" value="<?= htmlspecialchars($settings['tardy_threshold_minutes'] ?? '') ?>" min="1" max="120">
            </div>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Academic Year</label>
              <input type="text" name="academic_year" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition" placeholder="e.g. 2025-2026" value="<?= htmlspecialchars($settings['academic_year'] ?? '') ?>">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 mb-1.5">Active Term / Semester</label>
              <input type="text" name="semester" class="w-full px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition" placeholder="e.g. Second Semester" value="<?= htmlspecialchars($settings['semester'] ?? '') ?>">
            </div>
          </div>
        </div>

        <!-- Additional System Parameters -->
        <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
          <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
              </div>
              <div>
                <h3 class="text-sm sm:text-base font-bold text-slate-900">Additional System Parameters</h3>
                <p class="text-xs text-slate-500">Optional configuration parameters for campus integrations.</p>
              </div>
            </div>
            <button type="button" onclick="addCustomRow()" class="px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1.5 cursor-pointer">
              <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
              <span>+ Add Parameter</span>
            </button>
          </div>

          <div id="custom-kv-container" class="space-y-3">
            <?php
            $standardKeys = [
              'alert_enabled', 'alert_channel', 'alert_absent_only',
              'export_default_format', 'analytics_date_range_default',
              'library_open_time', 'library_close_time', 'tardy_threshold_minutes',
              'academic_year', 'semester'
            ];
            $hasCustom = false;
            foreach ($settings as $k => $v):
              if (in_array($k, $standardKeys, true)) continue;
              $hasCustom = true;
            ?>
              <div class="flex items-center gap-3 custom-kv-row">
                <input type="text" placeholder="Parameter Name" value="<?= htmlspecialchars($k) ?>" class="w-1/3 px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition custom-key-input">
                <input type="text" placeholder="Value" value="<?= htmlspecialchars($v) ?>" class="flex-1 px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition custom-val-input">
                <button type="button" onclick="this.closest('.custom-kv-row').remove()" class="p-2 text-slate-400 hover:text-rose-600 transition cursor-pointer" title="Remove">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            <?php endforeach; ?>
          </div>
          <p id="empty-custom-msg" class="text-xs text-slate-400 italic py-2 <?= $hasCustom ? 'hidden' : '' ?>">
            No additional parameters configured. Click &ldquo;+ Add Parameter&rdquo; to add one.
          </p>
        </div>

        <!-- Bottom Save Actions -->
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" onclick="saveSettings()" id="save-btn-bottom" class="px-6 py-2.5 rounded-xl bg-[#1e3b8a] hover:bg-[#162c69] text-white text-xs font-bold shadow-md shadow-[#1e3b8a]/20 hover:shadow-lg transition flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4 text-sky-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>Save Changes</span>
          </button>
        </div>

      </form>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

<script>
function addCustomRow(key = '', val = '') {
  document.getElementById('empty-custom-msg')?.classList.add('hidden');
  const container = document.getElementById('custom-kv-container');
  const div = document.createElement('div');
  div.className = 'flex items-center gap-3 custom-kv-row';
  div.innerHTML = `
    <input type="text" placeholder="Parameter Name" value="${key}" class="w-1/3 px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition custom-key-input">
    <input type="text" placeholder="Value" value="${val}" class="flex-1 px-3.5 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition custom-val-input">
    <button type="button" onclick="this.closest('.custom-kv-row').remove()" class="p-2 text-slate-400 hover:text-rose-600 transition cursor-pointer" title="Remove">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
    </button>
  `;
  container.appendChild(div);
}

async function saveSettings() {
  const form = document.getElementById('system-settings-form');
  const formData = new FormData(form);
  const data = {};

  for (const [key, value] of formData.entries()) {
    if (value !== '') {
      data[key] = value;
    }
  }

  if (formData.has('alert_enabled')) {
    data['alert_enabled'] = '1';
  } else {
    data['alert_enabled'] = '0';
  }

  document.querySelectorAll('.custom-kv-row').forEach(row => {
    const k = row.querySelector('.custom-key-input')?.value.trim();
    const v = row.querySelector('.custom-val-input')?.value.trim();
    if (k) {
      data[k] = v;
    }
  });

  const btnTop = document.getElementById('save-btn-top');
  const btnBottom = document.getElementById('save-btn-bottom');
  if (btnTop) btnTop.disabled = true;
  if (btnBottom) btnBottom.disabled = true;

  try {
    const res = await fetch(window.url('api/settings/save'), {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await res.json();
    if (result.status === 'success') {
      APP.toast('Settings saved successfully.', 'success');
      setTimeout(() => {
        window.location.reload();
      }, 800);
    } else {
      APP.toast(result.message || 'Failed to save settings.', 'error');
      if (btnTop) btnTop.disabled = false;
      if (btnBottom) btnBottom.disabled = false;
    }
  } catch (err) {
    APP.toast('An error occurred while saving settings.', 'error');
    if (btnTop) btnTop.disabled = false;
    if (btnBottom) btnBottom.disabled = false;
  }
}
</script>
