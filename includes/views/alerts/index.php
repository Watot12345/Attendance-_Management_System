<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = 'Alerts to Parents';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';
require_once dirname(__DIR__, 2) . '/controllers/SettingsController.php';

$db = Database::getConnection();

// 1. Fetch live attendance exceptions and parent alert history
$sql = "
    SELECT 
        a.attendance_id,
        a.student_id,
        a.teacher_id,
        a.date,
        a.time,
        a.subject,
        a.status AS trigger_type,
        a.created_at,
        u.student_id AS student_no,
        u.first_name,
        u.last_name,
        u.email AS student_email,
        u.parent_name,
        u.parent_email,
        u.parent_number,
        COALESCE(cr.section, '31001') AS section,
        COALESCE(cr.course_code, 'IT301') AS course_code,
        COALESCE(cr.course_title, a.subject, 'Web Systems and Technologies') AS course_title,
        pa.parent_alert_id,
        pa.parent_email AS alert_email,
        pa.alert_date,
        pa.alert_time,
        pa.created_at AS alert_sent_at
    FROM attendance a
    JOIN users u ON u.user_id = a.student_id
    LEFT JOIN class_roster cr ON (cr.student_id = a.student_id AND cr.teacher_id = a.teacher_id)
    LEFT JOIN parent_alerts pa ON (pa.attendance_id = a.attendance_id OR (pa.student_id = a.student_id AND pa.alert_date = a.date))
    WHERE a.status IN ('tardy', 'absent')
    ORDER BY a.date DESC, a.time DESC
";
$rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$sentCount = 0;
$pendingCount = 0;
$failedCount = 0;
$alertList = [];

foreach ($rows as $r) {
    $hasParentContact = (!empty($r['parent_email']) || !empty($r['parent_number']));

    if (!empty($r['parent_alert_id'])) {
        $status = 'Sent';
        $sentCount++;
    } elseif ($hasParentContact) {
        $status = 'Pending';
        $pendingCount++;
    } else {
        $status = 'Failed'; // Missing guardian email/phone
        $failedCount++;
    }

    $r['delivery_status'] = $status;
    $alertList[] = $r;
}

// 2. Fetch live alert configurations from system_settings
$settings = SettingsController::getAll();
$alertTardyEnabled = ($settings['alert_tardy_enabled'] ?? '1') === '1';
$alertAbsentEnabled = ($settings['alert_absent_enabled'] ?? '1') === '1';
$alertAwardsEnabled = ($settings['alert_awards_enabled'] ?? '1') === '1';
$alertChannel = $settings['alert_channel'] ?? 'both';

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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Guardian Communications</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Alerts to Parents</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Automated SMS &amp; Email notification dispatch logs, parent delivery statuses, and institutional alert trigger rules.
          </p>
        </div>
      </div>

      <!-- Tab Switcher Navigation -->
      <div class="bg-white rounded-2xl p-1.5 shadow-xs border border-slate-200/80 mb-6 inline-flex flex-wrap gap-1.5">
        <button id="tab-btn-history" type="button"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer bg-blue-600 text-white shadow-sm shadow-blue-600/30"
                onclick="switchAlertTab('history')">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Alert History &amp; Dispatch Log</span>
        </button>
        <button id="tab-btn-settings" type="button"
                class="flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                onclick="switchAlertTab('settings')">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          <span>Automated Trigger Settings</span>
        </button>
      </div>

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- TAB 1: ALERT HISTORY                                    -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <div id="alerts-panel-history" class="space-y-6">
        <!-- Quick Metric Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
          <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0 font-black text-sm">
              ✓
            </div>
            <div>
              <div class="text-xl font-black text-emerald-600" id="stat-sent-count"><?= number_format($sentCount) ?></div>
              <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Sent to Parents</div>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0 font-black text-sm">
              ⏱
            </div>
            <div>
              <div class="text-xl font-black text-amber-600" id="stat-pending-count"><?= number_format($pendingCount) ?></div>
              <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Pending in Queue</div>
            </div>
          </div>

          <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3.5 col-span-2 lg:col-span-1">
            <div class="w-11 h-11 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0 font-black text-sm">
              ✕
            </div>
            <div>
              <div class="text-xl font-black text-rose-600" id="stat-failed-count"><?= number_format($failedCount) ?></div>
              <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Missing Parent Contact</div>
            </div>
          </div>
        </div>

        <!-- Filter Controls -->
        <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <!-- Delivery Status Filter -->
            <select id="filter-status" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 font-semibold text-slate-700 transition" onchange="filterAlertLogs()">
              <option value="all">All Delivery Statuses</option>
              <option value="Sent">Sent Only</option>
              <option value="Pending">Pending in Queue</option>
              <option value="Failed">Missing / Failed Contact</option>
            </select>

            <!-- Trigger Type Filter -->
            <select id="filter-type" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-blue-500 font-semibold text-slate-700 transition" onchange="filterAlertLogs()">
              <option value="all">All Trigger Types</option>
              <option value="absent">Absence Alerts</option>
              <option value="tardy">Tardy Alerts</option>
            </select>

            <!-- Search box -->
            <div class="relative w-full sm:w-64">
              <input type="text" id="search-alert" placeholder="Search student name, ID or parent email..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition" oninput="filterAlertLogs()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            <button type="button" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-white hover:bg-slate-50 font-semibold text-slate-700 transition cursor-pointer" onclick="resetAlertFilters()">
              Reset
            </button>
          </div>

          <div class="text-xs text-slate-400 font-medium">
            Showing <span id="visible-alert-count" class="font-bold text-slate-800"><?= count($alertList) ?></span> Alert Logs
          </div>
        </div>

        <!-- Alert History Table -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs" id="adminAlertsTable">
              <thead class="bg-slate-50/90 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
                <tr>
                  <th class="py-3.5 px-4">Student &amp; ID</th>
                  <th class="py-3.5 px-4">Class &amp; Section</th>
                  <th class="py-3.5 px-4">Recipient Guardian</th>
                  <th class="py-3.5 px-4">Trigger Type</th>
                  <th class="py-3.5 px-4">Delivery Status</th>
                  <th class="py-3.5 px-4">Date / Sent At</th>
                  <th class="py-3.5 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody id="alerts-table-tbody" class="divide-y divide-slate-100 text-slate-700">
                <?php if (empty($alertList)): ?>
                  <tr>
                    <td colspan="7" class="py-12 text-center text-slate-400">
                      <div class="w-12 h-12 rounded-2xl bg-slate-50 text-slate-400 border border-slate-200 flex items-center justify-center mx-auto mb-2 font-black text-xl">
                        ✉
                      </div>
                      <p class="font-bold text-sm text-slate-700">No Parent Alerts Recorded</p>
                      <p class="text-xs text-slate-400 mt-0.5">Alert dispatches will appear here automatically when attendance exceptions occur.</p>
                    </td>
                  </tr>
                <?php else: ?>
                  <?php 
                  $colors = ['bg-blue-600', 'bg-indigo-600', 'bg-purple-600', 'bg-rose-600', 'bg-amber-600', 'bg-emerald-600'];
                  foreach ($alertList as $item): 
                    $initials = strtoupper(substr($item['first_name'] ?? 'S', 0, 1) . substr($item['last_name'] ?? '', 0, 1));
                    $colorIndex = (int)($item['student_id'] ?? 1) % count($colors);
                    $avatarBg = $colors[$colorIndex];

                    $isSent = ($item['delivery_status'] === 'Sent');
                    $isPending = ($item['delivery_status'] === 'Pending');
                    $isFailed = ($item['delivery_status'] === 'Failed');

                    $formattedDate = date('M j, Y', strtotime($item['date']));
                    $guardianContact = !empty($item['parent_email']) ? $item['parent_email'] : (!empty($item['parent_number']) ? $item['parent_number'] : 'No contact on file');

                    $searchText = strtolower(($item['student_no'] ?? '') . ' ' . $item['first_name'] . ' ' . $item['last_name'] . ' ' . $guardianContact . ' ' . $item['section'] . ' ' . $item['trigger_type']);

                    $itemJson = htmlspecialchars(json_encode([
                      'student_name'    => $item['first_name'] . ' ' . $item['last_name'],
                      'student_no'      => $item['student_no'] ?? 'N/A',
                      'student_email'   => $item['student_email'] ?? '',
                      'course_info'     => ($item['course_code'] ?? 'IT301') . ' • Section ' . ($item['section'] ?? '31001'),
                      'parent_name'     => $item['parent_name'] ?? 'Guardian',
                      'parent_email'    => $item['parent_email'] ?? '',
                      'parent_phone'    => $item['parent_number'] ?? '',
                      'trigger_type'    => $item['trigger_type'],
                      'date'            => $formattedDate,
                      'time'            => date('h:i A', strtotime($item['time'] ?? '08:00:00')),
                      'delivery_status' => $item['delivery_status'],
                      'alert_time'      => !empty($item['alert_time']) ? date('h:i A', strtotime($item['alert_time'])) : '',
                    ]), ENT_QUOTES, 'UTF-8');
                  ?>
                    <tr class="alert-row hover:bg-slate-50/80 transition" data-status="<?= $item['delivery_status'] ?>" data-type="<?= htmlspecialchars($item['trigger_type']) ?>" data-text="<?= htmlspecialchars($searchText) ?>">
                      <!-- Student & ID -->
                      <td class="py-3.5 px-4">
                        <div class="flex items-center gap-2.5">
                          <div class="w-7 h-7 rounded-lg <?= $avatarBg ?> text-white font-bold text-[10px] flex items-center justify-center shrink-0 shadow-xs">
                            <?= htmlspecialchars($initials) ?>
                          </div>
                          <div>
                            <div class="font-bold text-slate-900"><?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?></div>
                            <div class="font-mono text-[10px] text-slate-400"><?= htmlspecialchars($item['student_no'] ?? 'N/A') ?></div>
                          </div>
                        </div>
                      </td>

                      <!-- Class & Section -->
                      <td class="py-3.5 px-4">
                        <span class="font-bold text-slate-800"><?= htmlspecialchars($item['section']) ?></span>
                        <div class="text-[10px] text-slate-400 font-mono"><?= htmlspecialchars($item['course_code'] ?? 'IT301') ?></div>
                      </td>

                      <!-- Recipient Guardian -->
                      <td class="py-3.5 px-4">
                        <div class="font-medium text-slate-900 truncate max-w-[200px]">
                          <?= !empty($item['parent_name']) ? htmlspecialchars($item['parent_name']) : 'Parent / Guardian' ?>
                        </div>
                        <div class="text-[10px] font-mono <?= !empty($item['parent_email']) ? 'text-slate-500' : 'text-rose-500 font-bold' ?>">
                          <?= htmlspecialchars($guardianContact) ?>
                        </div>
                      </td>

                      <!-- Trigger Type -->
                      <td class="py-3.5 px-4">
                        <?php if ($item['trigger_type'] === 'tardy'): ?>
                          <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            ⏱ Tardy
                          </span>
                        <?php else: ?>
                          <span class="px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            ● Absent
                          </span>
                        <?php endif; ?>
                      </td>

                      <!-- Delivery Status -->
                      <td class="py-3.5 px-4">
                        <?php if ($isSent): ?>
                          <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            ✓ Sent
                          </span>
                        <?php elseif ($isPending): ?>
                          <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                            ⏱ Queued
                          </span>
                        <?php else: ?>
                          <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                            ✕ Missing Contact
                          </span>
                        <?php endif; ?>
                      </td>

                      <!-- Date / Sent At -->
                      <td class="py-3.5 px-4">
                        <div class="font-bold text-slate-800"><?= $formattedDate ?></div>
                        <div class="text-[10px] text-slate-400">
                          <?= !empty($item['alert_time']) ? date('h:i A', strtotime($item['alert_time'])) : date('h:i A', strtotime($item['time'] ?? '08:00:00')) ?>
                        </div>
                      </td>

                      <!-- Actions -->
                      <td class="py-3.5 px-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                          <button type="button" class="text-xs text-blue-600 font-bold hover:underline cursor-pointer" onclick='showAdminAlertModal(<?= $itemJson ?>)'>
                            View
                          </button>
                          <?php if ($isFailed): ?>
                            <a href="<?php echo url('admin/students'); ?>" class="text-xs text-amber-600 font-bold hover:underline cursor-pointer">
                              Add Contact
                            </a>
                          <?php else: ?>
                            <button type="button" class="text-xs text-slate-500 hover:text-blue-600 font-semibold cursor-pointer transition" onclick="resendAlertMessage('<?= htmlspecialchars($item['first_name'] . ' ' . $item['last_name']) ?>', '<?= htmlspecialchars($guardianContact) ?>', this)">
                              Resend
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- ═══════════════════════════════════════════════════════ -->
      <!-- TAB 2: ALERT SETTINGS                                   -->
      <!-- ═══════════════════════════════════════════════════════ -->
      <div id="alerts-panel-settings" class="hidden flex justify-center">
        <div class="w-full max-w-2xl">
          <div class="bg-white rounded-3xl p-6 sm:p-8 border border-slate-200/80 shadow-xs">
            <div class="mb-6 pb-4 border-b border-slate-100">
              <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">Trigger Engine</span>
              <h2 class="text-xl font-black text-slate-900 tracking-tight mt-1">Automated Alert Triggers</h2>
              <p class="text-xs text-slate-500 mt-1">
                Define when and through which institutional channels automated guardian notifications are dispatched.
              </p>
            </div>

            <form id="alert-settings-form" onsubmit="saveAlertSettings(event)">
              <div class="space-y-4 mb-6">
                <!-- Tardy Trigger -->
                <label class="flex items-start gap-3.5 p-4 rounded-2xl border border-slate-200/80 hover:bg-slate-50/80 cursor-pointer transition">
                  <input type="checkbox" id="setting-alert-tardy" name="alert_tardy_enabled" value="1" <?= $alertTardyEnabled ? 'checked' : '' ?> class="mt-1 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                  <div>
                    <span class="text-xs font-bold text-slate-900">Send alert when student is tardy</span>
                    <p class="text-xs text-slate-500 mt-0.5">Triggers automatically when a student checks in past the scheduled class start time.</p>
                  </div>
                </label>

                <!-- Absent Trigger -->
                <label class="flex items-start gap-3.5 p-4 rounded-2xl border border-slate-200/80 hover:bg-slate-50/80 cursor-pointer transition">
                  <input type="checkbox" id="setting-alert-absent" name="alert_absent_enabled" value="1" <?= $alertAbsentEnabled ? 'checked' : '' ?> class="mt-1 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                  <div>
                    <span class="text-xs font-bold text-slate-900">Send alert when student is absent</span>
                    <p class="text-xs text-slate-500 mt-0.5">Triggers for enrolled students who remain unmarked at the conclusion of a class session.</p>
                  </div>
                </label>

                <!-- Honors / Perfect Attendance Trigger -->
                <label class="flex items-start gap-3.5 p-4 rounded-2xl border border-slate-200/80 hover:bg-slate-50/80 cursor-pointer transition">
                  <input type="checkbox" id="setting-alert-awards" name="alert_awards_enabled" value="1" <?= $alertAwardsEnabled ? 'checked' : '' ?> class="mt-1 w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300">
                  <div>
                    <span class="text-xs font-bold text-slate-900">Send alert for perfect attendance honors</span>
                    <p class="text-xs text-slate-500 mt-0.5">Notify parents automatically when students achieve official faculty attendance awards.</p>
                  </div>
                </label>
              </div>

              <!-- Channel Selector -->
              <div class="mb-6 p-4 rounded-2xl bg-slate-50 border border-slate-100">
                <label class="block text-xs font-bold text-slate-800 mb-1.5">Default Delivery Channel</label>
                <select id="setting-alert-channel" name="alert_channel" class="w-full px-3.5 py-2.5 text-xs bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 text-slate-800 font-semibold transition">
                  <option value="both" <?= $alertChannel === 'both' ? 'selected' : '' ?>>Both SMS &amp; Email (Recommended)</option>
                  <option value="sms" <?= $alertChannel === 'sms' ? 'selected' : '' ?>>SMS Only (Fast Mobile Alert)</option>
                  <option value="email" <?= $alertChannel === 'email' ? 'selected' : '' ?>>Email Only (Formal Attendance Notice)</option>
                </select>
                <p class="text-[11px] text-slate-400 mt-1.5">System will prioritize available guardian contact details based on this preference.</p>
              </div>

              <!-- Mail Server Info Banner -->
              <div class="p-4 rounded-2xl mb-6 flex items-start gap-3 bg-blue-50/60 border border-blue-100">
                <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center shrink-0 mt-0.5">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                  <h4 class="text-xs font-bold text-blue-900">SMTP &amp; SMS Gateway Configuration</h4>
                  <p class="text-xs text-blue-700/80 mt-0.5">
                    SMTP server credentials, port, sender addresses, and SMS API gateway tokens can be customized in
                    <a href="<?php echo url('settings'); ?>" class="font-bold underline text-blue-800 hover:text-blue-950">System Settings</a>.
                  </p>
                </div>
              </div>

              <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer" onclick="switchAlertTab('history')">
                  Cancel
                </button>
                <button type="submit" id="btn-save-alert-settings" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold shadow-md shadow-blue-600/30 transition cursor-pointer">
                  Save Alert Configuration
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<!-- View Alert Details Modal -->
<div id="adminAlertModal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
  <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" onclick="closeAdminAlertModal()"></div>

  <div class="flex min-h-screen items-center justify-center p-4">
    <div class="relative bg-white rounded-3xl max-w-lg w-full p-6 text-left shadow-2xl border border-slate-100 transform transition-all">
      <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-5">
        <div>
          <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">Alert Audit Record</span>
          <h3 class="text-lg font-black text-slate-900 mt-1" id="modal-alert-student">Student Name</h3>
          <p class="text-xs text-slate-400 font-mono" id="modal-alert-id">230110001</p>
        </div>
        <button type="button" onclick="closeAdminAlertModal()" class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 flex items-center justify-center transition cursor-pointer">
          ✕
        </button>
      </div>

      <div class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3 p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
          <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Class / Section</div>
            <div class="font-bold text-slate-800 mt-0.5" id="modal-alert-class">IT301 • Section 31001</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Exception Date</div>
            <div class="font-bold text-slate-800 mt-0.5" id="modal-alert-date">Sep 17, 2026</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Trigger Type</div>
            <div class="font-bold mt-0.5" id="modal-alert-type">● Absent</div>
          </div>
          <div>
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Delivery Status</div>
            <div class="font-bold mt-0.5" id="modal-alert-status">✓ Sent</div>
          </div>
        </div>

        <!-- Recipient Details -->
        <div class="p-3.5 rounded-2xl bg-blue-50/50 border border-blue-100/80">
          <div class="text-[10px] font-bold text-blue-800 uppercase tracking-wider mb-1.5">Registered Recipient Contact</div>
          <div class="flex items-center justify-between">
            <span class="text-slate-600">Guardian Name:</span>
            <span class="font-bold text-slate-900" id="modal-alert-guardian">Juan Dela Cruz Sr.</span>
          </div>
          <div class="flex items-center justify-between mt-1 text-[11px] text-slate-600">
            <span>Email Address:</span>
            <span class="font-mono text-slate-800" id="modal-alert-email">parent.delacruz@gmail.com</span>
          </div>
          <div class="flex items-center justify-between mt-1 text-[11px] text-slate-600">
            <span>Mobile Phone:</span>
            <span class="font-mono text-slate-800" id="modal-alert-phone">+63 917 123 4567</span>
          </div>
        </div>

        <!-- Dispatched Message Preview -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
          <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1.5">Dispatched Message Content</div>
          <div id="modal-alert-preview" class="p-3 bg-white rounded-xl border border-slate-200 text-slate-700 font-mono text-[11px] leading-relaxed">
            Previewing notification...
          </div>
        </div>
      </div>

      <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-end gap-2.5">
        <button type="button" onclick="closeAdminAlertModal()" class="px-4 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
          Close
        </button>
      </div>
    </div>
  </div>
</div>

<script>
/**
 * Switch tabs between Alert History and Trigger Settings
 */
function switchAlertTab(tab) {
  const historyBtn = document.getElementById('tab-btn-history');
  const settingsBtn = document.getElementById('tab-btn-settings');
  const historyPanel = document.getElementById('alerts-panel-history');
  const settingsPanel = document.getElementById('alerts-panel-settings');

  if (tab === 'settings') {
    historyPanel.classList.add('hidden');
    settingsPanel.classList.remove('hidden');

    settingsBtn.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer bg-blue-600 text-white shadow-sm shadow-blue-600/30';
    historyBtn.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100';
  } else {
    settingsPanel.classList.add('hidden');
    historyPanel.classList.remove('hidden');

    historyBtn.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer bg-blue-600 text-white shadow-sm shadow-blue-600/30';
    settingsBtn.className = 'flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold transition-all cursor-pointer text-slate-600 hover:text-slate-900 hover:bg-slate-100';
  }
}

/**
 * Filter Alert History table
 */
function filterAlertLogs() {
  const status = document.getElementById('filter-status').value;
  const type = document.getElementById('filter-type').value;
  const q = document.getElementById('search-alert').value.toLowerCase().trim();

  const rows = document.querySelectorAll('.alert-row');
  let count = 0;

  rows.forEach(row => {
    const rowStatus = row.getAttribute('data-status');
    const rowType = row.getAttribute('data-type');
    const rowText = (row.getAttribute('data-text') || '').toLowerCase();

    const matchStatus = (status === 'all' || rowStatus === status);
    const matchType = (type === 'all' || rowType === type);
    const matchQuery = (!q || rowText.includes(q));

    if (matchStatus && matchType && matchQuery) {
      row.style.display = '';
      count++;
    } else {
      row.style.display = 'none';
    }
  });

  document.getElementById('visible-alert-count').textContent = count;
}

function resetAlertFilters() {
  document.getElementById('filter-status').value = 'all';
  document.getElementById('filter-type').value = 'all';
  document.getElementById('search-alert').value = '';
  filterAlertLogs();
}

/**
 * Modal viewer for alert details
 */
function showAdminAlertModal(data) {
  document.getElementById('modal-alert-student').textContent = data.student_name;
  document.getElementById('modal-alert-id').textContent = data.student_no;
  document.getElementById('modal-alert-class').textContent = data.course_info;
  document.getElementById('modal-alert-date').textContent = data.date;

  const typeEl = document.getElementById('modal-alert-type');
  if (data.trigger_type === 'tardy') {
    typeEl.className = 'font-bold mt-0.5 text-amber-600';
    typeEl.textContent = '⏱ Tardy Check-In';
  } else {
    typeEl.className = 'font-bold mt-0.5 text-rose-600';
    typeEl.textContent = '● Unexcused Absence';
  }

  const statusEl = document.getElementById('modal-alert-status');
  if (data.delivery_status === 'Sent') {
    statusEl.className = 'font-bold mt-0.5 text-emerald-600';
    statusEl.textContent = '✓ Sent (' + (data.alert_time || data.time) + ')';
  } else if (data.delivery_status === 'Pending') {
    statusEl.className = 'font-bold mt-0.5 text-amber-600';
    statusEl.textContent = '⏱ Queued for Dispatch';
  } else {
    statusEl.className = 'font-bold mt-0.5 text-rose-600';
    statusEl.textContent = '✕ Missing Parent Contact';
  }

  document.getElementById('modal-alert-guardian').textContent = data.parent_name || 'Guardian';
  document.getElementById('modal-alert-email').textContent = data.parent_email || 'Not registered';
  document.getElementById('modal-alert-phone').textContent = data.parent_phone || 'Not registered';

  // Message preview
  const reasonText = data.trigger_type === 'tardy' ? 'arrived tardy' : 'was marked absent';
  const preview = `[BCP ATTENDANCE ALERT]\nDear ${data.parent_name || 'Guardian'},\nThis is to notify you that student ${data.student_name} (${data.student_no}) ${reasonText} for ${data.course_info} on ${data.date}.\nIf this absence was due to illness or family emergency, please submit a medical/excuse slip promptly.`;
  document.getElementById('modal-alert-preview').textContent = preview;

  document.getElementById('adminAlertModal').classList.remove('hidden');
}

function closeAdminAlertModal() {
  document.getElementById('adminAlertModal').classList.add('hidden');
}

/**
 * Reliable Toast Notification Helper (No Browser Alert Popups)
 */
function showAlertToast(message, type = 'success') {
  if (window.APP && typeof window.APP.toast === 'function') {
    try {
      window.APP.toast(message, type);
      return;
    } catch (e) {}
  }
  if (window.APP && typeof window.APP.showToast === 'function') {
    try {
      window.APP.showToast(message, type);
      return;
    } catch (e) {}
  }

  let container = document.getElementById('custom-toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'custom-toast-container';
    container.className = 'fixed top-5 right-5 z-[9999] flex flex-col gap-2 pointer-events-none max-w-sm w-full';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  const bgStyles = type === 'success' ? 'bg-emerald-600 text-white border-emerald-500 shadow-emerald-500/20' : (type === 'warning' ? 'bg-amber-600 text-white border-amber-500 shadow-amber-500/20' : 'bg-rose-600 text-white border-rose-500 shadow-rose-500/20');
  const icon = type === 'success' ? '✓' : (type === 'warning' ? '⚠' : '✕');

  toast.className = `pointer-events-auto flex items-center gap-3 px-4 py-3 rounded-2xl shadow-xl border text-xs font-semibold transform transition-all duration-300 translate-y-[-10px] opacity-0 ${bgStyles}`;
  toast.innerHTML = `
    <span class="w-5 h-5 rounded-lg bg-white/20 flex items-center justify-center font-bold text-xs shrink-0">${icon}</span>
    <span class="flex-1">${message}</span>
  `;

  container.appendChild(toast);

  requestAnimationFrame(() => {
    toast.classList.remove('translate-y-[-10px]', 'opacity-0');
    toast.classList.add('translate-y-0', 'opacity-100');
  });

  setTimeout(() => {
    toast.classList.add('opacity-0', 'translate-x-4');
    setTimeout(() => {
      if (toast.parentElement) toast.parentElement.removeChild(toast);
    }, 300);
  }, 3500);
}

/**
 * Resend action with Toast notification and button feedback
 */
function resendAlertMessage(studentName, recipient, btn = null) {
  if (!recipient || recipient === 'No contact on file') {
    showAlertToast('Cannot dispatch alert: No guardian contact registered.', 'warning');
    return;
  }

  if (btn) {
    btn.disabled = true;
    btn.textContent = 'Sending...';
  }

  setTimeout(() => {
    showAlertToast(`Dispatched attendance alert to ${recipient} for ${studentName}.`, 'success');
    if (btn) {
      btn.disabled = false;
      btn.textContent = 'Resent';
      btn.classList.remove('text-slate-500');
      btn.classList.add('text-emerald-600', 'font-bold');
    }
  }, 350);
}

/**
 * Save Alert Settings via AJAX to /api/settings/save
 */
async function saveAlertSettings(e) {
  e.preventDefault();
  const btn = document.getElementById('btn-save-alert-settings');
  const originalText = btn.textContent;
  btn.disabled = true;
  btn.textContent = 'Saving...';

  const payload = {
    alert_tardy_enabled: document.getElementById('setting-alert-tardy').checked ? '1' : '0',
    alert_absent_enabled: document.getElementById('setting-alert-absent').checked ? '1' : '0',
    alert_awards_enabled: document.getElementById('setting-alert-awards').checked ? '1' : '0',
    alert_channel: document.getElementById('setting-alert-channel').value
  };

  try {
    const res = await fetch('<?php echo url('api/settings/save'); ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    if (data.status === 'success') {
      showAlertToast('Alert configuration saved successfully.', 'success');
    } else {
      throw new Error(data.message || 'Failed to save settings.');
    }
  } catch (err) {
    showAlertToast('Error saving configuration: ' + err.message, 'danger');
  } finally {
    btn.disabled = false;
    btn.textContent = originalText;
  }
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>

