<?php $page_title = 'Alerts'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
          <div>
            <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Alerts</h1>
            <p class="text-sm mt-1" style="color:var(--color-text-secondary)">System notification logs, parent communication history &amp; trigger rules</p>
          </div>
        </div>

        <!-- Top Menu Panel (Tabs) -->
        <div class="bg-white rounded-lg p-1.5 shadow-card border border-gray-100 mb-6 inline-flex flex-wrap gap-1">
          <button id="tab-btn-history" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-semibold transition-all"
                  style="background:var(--color-teal-500); color:#ffffff;"
                  onclick="switchAlertTab('history')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Alert History</span>
          </button>
          <button id="tab-btn-settings" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-medium transition-all"
                  style="background:transparent; color:var(--color-text-secondary);"
                  onclick="switchAlertTab('settings')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span>Alert Settings</span>
          </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 1: ALERT HISTORY                                    -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="alerts-panel-history" class="space-y-6">
          <!-- Stat Cards -->
          <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="bg-white rounded-lg p-4 shadow-card stat-card-present">
              <p class="text-sm" style="color:var(--color-text-secondary)">Sent Alerts</p>
              <p class="stat-number" style="color:var(--color-present)">142</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-card stat-card-tardy">
              <p class="text-sm" style="color:var(--color-text-secondary)">Pending in Queue</p>
              <p class="stat-number" style="color:var(--color-tardy)">3</p>
            </div>
            <div class="bg-white rounded-lg p-4 shadow-card stat-card-absent">
              <p class="text-sm" style="color:var(--color-text-secondary)">Failed Delivery</p>
              <p class="stat-number" style="color:var(--color-absent)">1</p>
            </div>
          </div>

          <!-- Filters -->
          <div class="bg-white rounded-lg shadow-card">
            <div class="p-4 flex flex-wrap items-center gap-3">
              <select class="form-input form-select w-auto text-sm">
                <option>All Statuses</option>
                <option>Sent</option>
                <option>Pending</option>
                <option>Failed</option>
              </select>
              <select class="form-input form-select w-auto text-sm">
                <option>All Trigger Types</option>
                <option>Absent</option>
                <option>Tardy</option>
                <option>Perfect Attendance</option>
              </select>
              <div class="relative flex-1 min-w-[200px]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" class="form-input pl-9 text-sm" placeholder="Search student name or parent email...">
              </div>
              <button type="button" class="btn btn-secondary btn-sm" onclick="APP.showToast('Alert queue refreshed.', 'info')">Refresh</button>
            </div>
          </div>

          <!-- Alert Table -->
          <div class="bg-white rounded-lg shadow-card overflow-x-auto">
            <table class="data-table">
              <thead>
                <tr>
                  <th scope="col">Student</th>
                  <th scope="col">Recipient</th>
                  <th scope="col">Type</th>
                  <th scope="col">Status</th>
                  <th scope="col">Sent At</th>
                  <th scope="col" class="text-right">Action</th>
                </tr>
              </thead>
              <tbody>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-medium">Dela Cruz, Juan</td>
                  <td>parent.delacruz@email.com</td>
                  <td><span class="badge badge-absent">Absent</span></td>
                  <td><span class="badge badge-sent">✓ Sent</span></td>
                  <td class="text-sm" style="color:var(--color-text-secondary)">08:45 AM</td>
                  <td class="text-right">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="APP.openModal('Alert Details', '<p class=text-sm><strong>Recipient:</strong> parent.delacruz@email.com<br><strong>Subject:</strong> BCP Attendance Alert: Juan Dela Cruz marked absent on Sep 7, 2026.<br><strong>Status:</strong> Delivered via SMTP (250 OK).</p>', '<button class=\'btn btn-secondary btn-sm\' onclick=\'APP.closeModal()\'>Close</button>')">View</button>
                  </td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-medium">Santos, Maria</td>
                  <td>maria.parent@email.com</td>
                  <td><span class="badge badge-tardy">Tardy</span></td>
                  <td><span class="badge badge-sent">✓ Sent</span></td>
                  <td class="text-sm" style="color:var(--color-text-secondary)">08:32 AM</td>
                  <td class="text-right">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="APP.openModal('Alert Details', '<p class=text-sm><strong>Recipient:</strong> maria.parent@email.com<br><strong>Subject:</strong> BCP Attendance Alert: Maria Santos arrived 18 mins late.<br><strong>Status:</strong> Delivered via SMTP (250 OK).</p>', '<button class=\'btn btn-secondary btn-sm\' onclick=\'APP.closeModal()\'>Close</button>')">View</button>
                  </td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-medium">Reyes, Pedro</td>
                  <td>reyes.p@email.com</td>
                  <td><span class="badge badge-absent">Absent</span></td>
                  <td><span class="badge badge-pending">⏳ Pending</span></td>
                  <td class="text-sm" style="color:var(--color-text-secondary)">Queued (retry 1/3)</td>
                  <td class="text-right">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="APP.showToast('Resending alert to reyes.p@email.com...', 'info')">Resend</button>
                  </td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-medium">Garcia, Ana</td>
                  <td>garcia.parent@invalid</td>
                  <td><span class="badge badge-absent">Absent</span></td>
                  <td><span class="badge badge-failed">✗ Failed</span></td>
                  <td class="text-sm" style="color:var(--color-text-secondary)">08:46 AM (Bounce)</td>
                  <td class="text-right">
                    <button type="button" class="btn btn-ghost btn-sm text-red-600" onclick="APP.showToast('Invalid recipient address flagged for update.', 'warning')">Fix Email</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 2: ALERT SETTINGS                                   -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="alerts-panel-settings" class="hidden flex justify-center">
          <div class="w-full max-w-xl">
            <div class="bg-white rounded-lg p-6 shadow-card">
              <div class="mb-5 pb-4 border-b border-gray-100">
                <h2 class="text-lg font-bold" style="color:var(--color-text-primary)">Automated Alert Triggers</h2>
                <p class="text-sm mt-0.5" style="color:var(--color-text-secondary)">Define which attendance events automatically dispatch notifications to parents</p>
              </div>

              <form onsubmit="event.preventDefault(); APP.showToast('Alert settings updated successfully.', 'success');">
                <div class="space-y-4 mb-6">
                  <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition-colors">
                    <input type="checkbox" checked class="mt-0.5 w-4 h-4 rounded" style="accent-color:var(--color-teal-500)">
                    <div>
                      <span class="text-sm font-semibold" style="color:var(--color-text-primary)">Send alert when student is tardy</span>
                      <p class="text-xs mt-0.5" style="color:var(--color-text-muted)">Triggers instantly when a student scans in after the morning grace threshold</p>
                    </div>
                  </label>

                  <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition-colors">
                    <input type="checkbox" checked class="mt-0.5 w-4 h-4 rounded" style="accent-color:var(--color-teal-500)">
                    <div>
                      <span class="text-sm font-semibold" style="color:var(--color-text-primary)">Send alert when student is absent</span>
                      <p class="text-xs mt-0.5" style="color:var(--color-text-muted)">Triggers at 9:00 AM for all students enrolled without an entry scan</p>
                    </div>
                  </label>

                  <label class="flex items-start gap-3 p-3 rounded-lg border border-gray-100 hover:bg-slate-50 cursor-pointer transition-colors">
                    <input type="checkbox" checked class="mt-0.5 w-4 h-4 rounded" style="accent-color:var(--color-teal-500)">
                    <div>
                      <span class="text-sm font-semibold" style="color:var(--color-text-primary)">Send alert for perfect attendance honors</span>
                      <p class="text-xs mt-0.5" style="color:var(--color-text-muted)">Notify parents automatically when students receive a perfect attendance certificate</p>
                    </div>
                  </label>
                </div>

                <div class="p-4 rounded-lg mb-6 flex items-start gap-3" style="background:var(--color-surface); border:1px solid var(--color-border)">
                  <span class="text-xl">✉️</span>
                  <div>
                    <h4 class="text-sm font-semibold" style="color:var(--color-text-primary)">Mail Server Configuration</h4>
                    <p class="text-xs mt-0.5" style="color:var(--color-text-secondary)">
                      SMTP host, port, authentication credentials, and sender headers are configured in
                      <a href="/Attendance _Management_System/includes/views/settings/index.php" class="font-medium underline" style="color:var(--color-teal-500)">System Settings</a>.
                    </p>
                  </div>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                  <button type="button" class="btn btn-secondary" onclick="switchAlertTab('history')">Cancel</button>
                  <button type="submit" class="btn btn-primary">Save Alert Configuration</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php $page_js = '<script src="/Attendance _Management_System/assets/js/alerts.js"></script>'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('alerts');</script>
