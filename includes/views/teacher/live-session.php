<?php
$page_title = 'Live Attendance Session';

require_once dirname(__DIR__, 2) . '/core/Database.php';

// Fetch active teacher class info
$db = Database::getConnection();
$teacherId = 2;
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['teacher_id'])) {
    $teacherId = (int)$_SESSION['teacher_id'];
} elseif (!empty($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher') {
    $teacherId = (int)$_SESSION['user_id'];
}

// Fetch all distinct sections for this teacher from class_roster
$secStmt = $db->prepare("
    SELECT DISTINCT section, course_code, course_title, room_number, scheduled_time, schedule_day
    FROM class_roster
    WHERE teacher_id = ?
    ORDER BY section ASC
");
$secStmt->execute([$teacherId]);
$teacherSections = $secStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Fetch currently running QR sessions for this teacher
$actSecStmt = $db->prepare("
    SELECT DISTINCT section
    FROM qr_sessions
    WHERE teacher_id = ? AND `end` > NOW()
");
$actSecStmt->execute([$teacherId]);
$activeSectionsFromDb = $actSecStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

// Check if section passed via query string (?section=31001 or ?class_id=...) or persisted cookie
$selectedSectionKey = trim($_GET['section'] ?? $_COOKIE['ams_selected_section'] ?? '');
if (empty($selectedSectionKey) && !empty($_GET['class_id'])) {
    $idx = (int)$_GET['class_id'] - 1;
    if (isset($teacherSections[$idx])) {
        $selectedSectionKey = $teacherSections[$idx]['section'];
    }
}

$selectedSectionInfo = null;
foreach ($teacherSections as $sec) {
    if ($sec['section'] === $selectedSectionKey) {
        $selectedSectionInfo = $sec;
        break;
    }
}
if (!$selectedSectionInfo && !empty($teacherSections)) {
    $selectedSectionInfo = $teacherSections[0];
    $selectedSectionKey = $selectedSectionInfo['section'];
}

$startTimeFormatted = !empty($selectedSectionInfo['scheduled_time']) ? date('h:i A', strtotime($selectedSectionInfo['scheduled_time'])) : '--:--';
?>
<?php include dirname(__DIR__) . '/partials/header.php'; ?>
<style>
  #qrcode-container {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 240px !important;
    height: 240px !important;
    aspect-ratio: 1 / 1 !important;
    margin: 0 auto !important;
    padding: 20px !important;
    background: #ffffff !important;
    border-radius: 16px !important;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px -1px rgba(0, 0, 0, 0.05) !important;
    border: 1px solid #e2e8f0 !important;
    box-sizing: border-box !important;
    overflow: hidden !important;
  }
  #qrcode-container canvas,
  #qrcode-container img {
    margin: 0 auto !important;
    width: 200px !important;
    height: 200px !important;
    max-width: 200px !important;
    max-height: 200px !important;
    aspect-ratio: 1 / 1 !important;
    object-fit: contain !important;
    image-rendering: -webkit-optimize-contrast !important;
    image-rendering: crisp-edges !important;
    image-rendering: pixelated !important;
  }
  #qrcode-container canvas[style*="display: none"],
  #qrcode-container img[style*="display: none"] {
    display: none !important;
  }
  .qr-card-blur-overlay {
    background: rgba(255, 255, 255, 0.75);
    backdrop-filter: blur(5px);
    -webkit-backdrop-filter: blur(5px);
    transition: opacity 0.25s ease, visibility 0.25s ease;
  }
  .qr-card-blur-overlay.is-hidden {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
  }
</style>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include dirname(__DIR__) . '/partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include dirname(__DIR__) . '/partials/navbar.php'; ?>

      <!-- Content Area -->
      <main class="flex-1 p-6 bg-surface">
        <!-- Minimalist Live Header -->
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">
          <div>
            <div class="flex items-center gap-2 mb-2">
              <span id="header-status-badge" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-slate-100 text-slate-500">
                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                <span>Inactive</span>
              </span>
            </div>
            <h1 class="text-2xl font-bold text-text-primary tracking-tight" id="header-course-title">
              <?= htmlspecialchars($selectedSectionInfo['section'] ?? '') ?> · <?= htmlspecialchars($selectedSectionInfo['course_code'] ?? '') ?> (<?= htmlspecialchars($selectedSectionInfo['course_title'] ?? 'No Classes Assigned') ?>)
            </h1>
            <p class="text-sm text-text-secondary mt-0.5" id="header-course-sub">
              Room <span id="header-room-number"><?= htmlspecialchars($selectedSectionInfo['room_number'] ?? '402') ?></span> · Started <span id="header-scheduled-time"><?= $startTimeFormatted ?></span> · <span id="header-live-clock" class="font-mono text-slate-600">--:--:-- --</span>
            </p>
          </div>

          <!-- Controls & Dynamic Section Selector -->
          <div class="flex flex-wrap items-center gap-3">
            <!-- Scalable Custom Section Switcher Dropdown & Combobox -->
            <div class="relative" id="section-switcher-container">
              <!-- Trigger Button -->
              <button type="button" 
                      id="section-switcher-btn"
                      onclick="toggleSectionSwitcherDropdown()"
                      aria-haspopup="listbox"
                      aria-expanded="false"
                      class="flex items-center gap-2.5 bg-slate-100/90 hover:bg-slate-200/80 rounded-xl px-3.5 py-2 transition-all duration-150 cursor-pointer text-left select-none">
                <div class="w-7 h-7 rounded-lg bg-teal-100 text-teal-700 flex items-center justify-center shrink-0">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div class="flex flex-col min-w-0 pr-1">
                  <div class="flex items-center gap-1.5">
                    <span class="text-xs font-bold text-slate-800 tracking-tight" id="switcher-active-section">Section <?= htmlspecialchars($selectedSectionInfo['section'] ?? 'None') ?></span>
                    <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded bg-white text-slate-600" id="switcher-active-code"><?= htmlspecialchars($selectedSectionInfo['course_code'] ?? 'N/A') ?></span>
                  </div>
                  <span class="text-[11px] text-slate-500 truncate" id="switcher-active-sub">Rm <?= htmlspecialchars($selectedSectionInfo['room_number'] ?? '402') ?> · <?= $startTimeFormatted ?></span>
                </div>
                <svg id="switcher-chevron" class="w-4 h-4 text-slate-400 ml-1 transition-transform duration-200 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              </button>

              <!-- Floating Dropdown Popover Menu -->
              <div id="section-switcher-dropdown" 
                   class="hidden absolute left-0 sm:right-0 sm:left-auto top-full mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-xl z-50 overflow-hidden transform origin-top transition-all duration-150">
                <!-- Dropdown Header & Search -->
                <div class="p-3 bg-slate-50/90">
                  <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wider">Select Class Section</span>
                    <span class="text-[11px] font-semibold text-slate-500 bg-white px-2 py-0.5 rounded-full"><?= count($teacherSections) ?> sections</span>
                  </div>
                  <?php if (count($teacherSections) >= 3): ?>
                  <div class="relative">
                    <input type="text" 
                           id="section-switcher-search" 
                           oninput="filterSectionDropdownList()" 
                           placeholder="Search section or course..." 
                           class="w-full pl-8 pr-3 py-1.5 text-xs bg-white rounded-lg focus:outline-none focus:ring-1 focus:ring-teal-500 transition">
                    <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                  </div>
                  <?php endif; ?>
                </div>

                <!-- Section Items List -->
                <div class="p-1.5 max-h-72 overflow-y-auto space-y-1" id="section-switcher-list" role="listbox">
                  <?php foreach ($teacherSections as $sec): ?>
                    <?php 
                      $secVal = htmlspecialchars($sec['section']);
                      $isAct = in_array($sec['section'], $activeSectionsFromDb, true);
                      $isSelected = ($sec['section'] === $selectedSectionKey);
                    ?>
                    <button type="button" 
                            role="option"
                            id="section-tab-<?= $secVal ?>"
                            data-section="<?= $secVal ?>"
                            data-search-text="<?= strtolower($secVal . ' ' . $sec['course_code'] . ' ' . $sec['course_title']) ?>"
                            aria-selected="<?= $isSelected ? 'true' : 'false' ?>"
                            onclick="selectSectionFromDropdown('<?= $secVal ?>')"
                            class="section-switcher-option w-full p-2.5 rounded-xl text-left transition-all duration-150 flex items-start justify-between gap-3 cursor-pointer select-none <?= $isSelected ? 'bg-teal-50 text-slate-900' : 'hover:bg-slate-50 text-slate-700' ?>">
                      <div class="flex items-start gap-2.5 min-w-0">
                        <div class="mt-0.5 w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 <?= $isSelected ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600' ?>">
                          <?= substr($secVal, -2) ?>
                        </div>
                        <div class="min-w-0">
                          <div class="flex items-center gap-1.5">
                            <span class="text-xs font-bold text-slate-800">Section <?= $secVal ?></span>
                            <span class="text-[10px] font-semibold px-1.5 py-0.2 rounded <?= $isSelected ? 'bg-teal-100 text-teal-800' : 'bg-slate-100 text-slate-600' ?>"><?= htmlspecialchars($sec['course_code']) ?></span>
                          </div>
                          <p class="text-[11px] text-slate-500 truncate mt-0.5"><?= htmlspecialchars($sec['course_title']) ?></p>
                          <p class="text-[10px] text-slate-400 mt-0.5">Room <?= htmlspecialchars($sec['room_number'] ?? '402') ?> · <?= htmlspecialchars($sec['schedule_day'] ?? 'Mon') ?> <?= date('h:i A', strtotime($sec['scheduled_time'])) ?></p>
                        </div>
                      </div>
                      <div class="flex items-center pt-0.5 shrink-0">
                        <span class="switcher-opt-check text-teal-600 <?= $isSelected ? '' : 'hidden' ?>">
                          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        </span>
                      </div>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- Synchronized select for accessibility / fallback -->
            <select id="section-select" class="hidden" onchange="onSectionChange(this.value)" aria-label="Select Class Section">
              <?php foreach ($teacherSections as $sec): ?>
                <?php $isAct = in_array($sec['section'], $activeSectionsFromDb, true); ?>
                <option value="<?= htmlspecialchars($sec['section']) ?>" <?= $sec['section'] === $selectedSectionKey ? 'selected' : '' ?> data-is-active="<?= $isAct ? '1' : '0' ?>">
                  Section <?= htmlspecialchars($sec['section']) ?> (<?= htmlspecialchars($sec['course_code']) ?>)<?= $isAct ? ' ● LIVE' : '' ?>
                </option>
              <?php endforeach; ?>
            </select>

            <button type="button" id="btn-header-generate" class="btn btn-primary text-xs font-bold flex items-center gap-2 shadow-xs cursor-pointer" onclick="manualGenerateQR(this)" title="Generate dynamic QR session">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span>Generate QR</span>
            </button>
            <button type="button" id="btn-header-close-session" class="btn btn-danger text-xs font-semibold shadow-sm flex items-center gap-2 cursor-pointer disabled:opacity-40 disabled:cursor-not-allowed disabled:pointer-events-none transition-all" onclick="openCloseSessionModal()" title="No active session to close" disabled>
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
              <span>Close Session</span>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 mb-6">
          <!-- ════ LEFT COLUMN: DYNAMIC QR CODE CARD ════ -->
          <div class="lg:col-span-5 bg-white rounded-2xl shadow-card border border-slate-100 p-6 flex flex-col items-center justify-between text-center relative overflow-hidden" id="qr-card-container">
            <!-- ════ BLURRY LOADING OVERLAY IN FRONT OF DYNAMIC QR ════ -->
            <div id="qr-card-loading-overlay" class="absolute inset-0 z-30 flex flex-col items-center justify-center qr-card-blur-overlay">
              <div class="relative w-14 h-14 mb-3 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-2 border-slate-200"></div>
                <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-teal-600 border-r-teal-500 animate-spin"></div>
                <div class="w-7 h-7 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center shadow-xs">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </div>
              </div>
              <p id="qr-loading-text" class="text-xs font-bold text-slate-800 tracking-wide">Loading...</p>
              <p id="qr-loading-sub" class="text-[11px] text-slate-500 mt-0.5">Please wait</p>
            </div>

            <div class="w-full flex flex-col items-center">
              <div class="w-full flex items-center justify-between pb-3 mb-4 border-b border-slate-100">
                <span class="text-xs font-bold uppercase tracking-wider text-teal-700">Dynamic Anti-Screenshot QR</span>
                <span class="text-xs font-mono bg-teal-50 border border-teal-200 text-teal-800 font-bold px-2.5 py-0.5 rounded-full" id="token-display">READY</span>
              </div>

              <!-- Animated Rotating Countdown Bar -->
              <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mb-5">
                <div id="qr-timer-bar" class="bg-gradient-to-r from-teal-500 to-emerald-500 h-2 transition-all duration-1000 ease-linear" style="width: 0%;"></div>
              </div>

              <!-- 1. Ready To Start Box (Square Box) -->
              <div id="qr-ready-box" class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-200 rounded-2xl mx-auto w-[250px] h-[250px] aspect-square transition-all text-center animate-fade-in">
                <div class="w-14 h-14 rounded-2xl bg-teal-100/80 text-teal-600 flex items-center justify-center mx-auto mb-2.5 shadow-xs">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                </div>
                <h4 class="text-xs font-bold text-slate-800 line-clamp-1" id="ready-box-title">Section <?= htmlspecialchars($selectedSectionKey) ?></h4>
                <p class="text-[11px] text-slate-500 mt-0.5 mb-3 leading-tight">
                  Ready to start session
                </p>
                <button type="button" class="btn btn-primary text-xs font-bold py-2 px-4 shadow-sm rounded-xl flex items-center gap-1.5 mx-auto cursor-pointer" onclick="manualGenerateQR(this)">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>Generate QR</span>
                </button>
              </div>

              <!-- 2. Active Dynamic QR Box Container (Perfect Square Box) -->
              <div id="qr-active-box" class="hidden flex-col items-center justify-center mx-auto transition-all">
                <div id="qrcode-container" class="w-[240px] h-[240px] aspect-square flex items-center justify-center mx-auto">
                  <!-- QR Code Rendered dynamically via qrcode-generator.js -->
                </div>
                <div class="flex items-center justify-center gap-1.5 mt-3 text-xs font-medium text-slate-600">
                  <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span>Expires in <span id="countdown-text" class="text-slate-800 font-bold">30m 00s</span></span>
                </div>
              </div>

              <!-- 3. Empty State Box (Square Box) -->
              <div id="qr-empty-box" class="hidden flex-col items-center justify-center p-6 bg-slate-50 border-2 border-dashed border-slate-300 rounded-2xl mx-auto w-[250px] h-[250px] aspect-square text-center animate-fade-in">
                <div class="w-14 h-14 rounded-2xl bg-slate-200/80 text-slate-400 flex items-center justify-center mx-auto mb-2.5 shadow-inner">
                  <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4 class="text-xs font-bold text-slate-800">Session Closed</h4>
                <p class="text-[11px] text-slate-500 mt-0.5 mb-3 leading-tight">
                  Attendance window ended
                </p>
                <button type="button" class="btn btn-primary text-xs font-bold py-2 px-4 shadow-sm rounded-xl flex items-center gap-1.5 mx-auto cursor-pointer" onclick="manualGenerateQR(this)">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  <span>Generate QR</span>
                </button>
              </div>

              <!-- Controls in Card (Active State): Pause/Resume & Rotate QR -->
              <div id="qr-refresh-btn-wrap" class="hidden mt-4 flex items-center justify-center gap-2.5">
                <button type="button" id="btn-pause-resume-session" class="btn btn-secondary text-xs font-bold flex items-center gap-1.5 py-2 px-3.5 shadow-2xs rounded-xl cursor-pointer hover:bg-slate-100 transition" onclick="togglePauseResumeSession()" title="Pause countdown & scanning">
                  <svg id="icon-pause-session" class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <svg id="icon-resume-session" class="w-3.5 h-3.5 text-teal-600 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  <span id="text-pause-resume">Pause</span>
                </button>
                <button type="button" class="btn btn-primary text-xs font-bold flex items-center gap-1.5 py-2 px-3.5 shadow-2xs rounded-xl cursor-pointer" onclick="manualGenerateQR(this)" title="Rotate QR code">
                  <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  <span>Rotate QR</span>
                </button>
              </div>

              <p id="qr-footer-note" class="text-xs text-text-muted mt-4">
                Students scan with an authenticated device enrolled in <strong>Section <?= htmlspecialchars($selectedSectionKey) ?></strong>. Screenshots expire after 30 minutes.
              </p>
            </div>
          </div>

          <!-- ════ RIGHT COLUMN: LIVE FEED & METRICS FROM DATABASE ════ -->
          <div class="lg:col-span-7 flex flex-col gap-5">
            <!-- Attendance Counters with Uniform Clean Styling -->
            <div class="grid grid-cols-4 gap-3" id="metric-cards-container">
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center relative overflow-hidden">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Enrolled</p>
                <div class="metric-skeleton hidden my-1.5 h-7 w-12 mx-auto bg-slate-200/80 rounded-lg animate-pulse"></div>
                <p class="metric-val text-2xl font-bold text-slate-800 mt-0.5" id="metric-enrolled">0</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center relative overflow-hidden">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Present</p>
                <div class="metric-skeleton hidden my-1.5 h-7 w-12 mx-auto bg-slate-200/80 rounded-lg animate-pulse"></div>
                <p class="metric-val text-2xl font-bold text-slate-800 mt-0.5" id="metric-present">0</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center relative overflow-hidden">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Tardy</p>
                <div class="metric-skeleton hidden my-1.5 h-7 w-12 mx-auto bg-slate-200/80 rounded-lg animate-pulse"></div>
                <p class="metric-val text-2xl font-bold text-slate-800 mt-0.5" id="metric-tardy">0</p>
              </div>
              <div class="bg-white p-4 rounded-xl shadow-card border border-slate-100 text-center relative overflow-hidden">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Pending</p>
                <div class="metric-skeleton hidden my-1.5 h-7 w-12 mx-auto bg-slate-200/80 rounded-lg animate-pulse"></div>
                <p class="metric-val text-2xl font-bold text-slate-800 mt-0.5" id="metric-pending">0</p>
              </div>
            </div>

            <!-- Live Scan Stream Card -->
            <div class="bg-white rounded-xl shadow-card border border-slate-100 flex-1 flex flex-col overflow-hidden min-h-[420px]">
              <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-text-primary flex items-center gap-2">
                  <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-teal-500"></span>
                  </span>
                  <span>Live Student Check-In Feed</span>
                </h3>
                <div class="flex items-center gap-2">
                  <span class="text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 rounded-full" id="feed-count">0 checked in</span>
                  <button type="button" id="view-all-btn" onclick="openPresentStudentsModal()" class="px-2.5 py-1 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 rounded-lg transition flex items-center gap-1.5 shadow-2xs cursor-pointer" title="View all present students">
                    <span>View All</span>
                    <span id="view-all-count-badge" class="px-1.5 py-0.2 bg-teal-600 text-white rounded-full text-[10px] font-bold">0</span>
                  </button>
                  <button type="button" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition cursor-pointer" onclick="loadLiveFeed(true, this)" title="Refresh feed">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                  </button>
                </div>
              </div>

              <!-- Live Stream List from DB -->
              <div class="p-4 overflow-y-auto space-y-2.5 max-h-[380px] flex-1" id="live-feed-list">
                <!-- Skeletons rendered dynamically while loading -->
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- ════ CLOSE SESSION CONFIRMATION MODAL ════ -->
  <div id="close-session-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 animate-scale-in">
      <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      </div>

      <h3 class="text-xl font-bold text-center text-text-primary mb-1">Close Attendance Session?</h3>
      <p class="text-sm text-text-secondary text-center mb-5">
        Closing the session stops QR scanning and will <strong>automatically create Absence records</strong> in the database for students enrolled in section <strong id="modal-section-name"><?= htmlspecialchars($selectedSectionKey) ?></strong> who have not checked in yet.
      </p>

      <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 text-xs space-y-1.5 mb-6">
        <div class="flex justify-between font-medium text-slate-700">
          <span>Present Students:</span>
          <span class="text-emerald-600 font-bold" id="modal-present-count">0</span>
        </div>
        <div class="flex justify-between font-medium text-slate-700">
          <span>Tardy Students:</span>
          <span class="text-amber-600 font-bold" id="modal-tardy-count">0</span>
        </div>
        <div class="flex justify-between font-bold text-rose-700 border-t border-slate-200 pt-1.5">
          <span>Unrecorded ➔ Marked Absent:</span>
          <span id="modal-pending-count">0</span>
        </div>
      </div>

      <div class="flex justify-end gap-3">
        <button type="button" class="btn btn-secondary flex-1 cursor-pointer" onclick="closeModal()">Cancel</button>
        <button type="button" id="confirm-close-btn" class="btn btn-danger flex-1 cursor-pointer" onclick="executeCloseSession(this)">Close Session</button>
      </div>
    </div>
  </div>

  <!-- ════ ALL PRESENT STUDENTS MODAL ════ -->
  <div id="present-students-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full flex flex-col max-h-[88vh] animate-scale-in overflow-hidden">
      <!-- Modal Header -->
      <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center shadow-2xs font-bold">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <h3 class="text-lg font-bold text-slate-900">Present &amp; Checked-In Students</h3>
            <p class="text-xs text-slate-500">Live attendance records for today's session (<span id="modal-header-section"><?= htmlspecialchars($selectedSectionKey) ?></span>)</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <span id="modal-present-badge-count" class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
            0 Students
          </span>
          <button type="button" onclick="closePresentStudentsModal()" class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100 transition cursor-pointer">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>
      </div>

      <!-- Filter / Search Bar -->
      <div class="p-4 border-b border-slate-100 bg-white flex flex-col sm:flex-row gap-3 items-center justify-between">
        <div class="relative w-full sm:w-72">
          <input type="text" id="present-search-input" oninput="filterPresentModalList()" placeholder="Search by student name or ID..." class="w-full pl-9 pr-3 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-teal-500 transition">
          <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <div class="flex items-center gap-1.5 w-full sm:w-auto">
          <button type="button" onclick="setPresentFilter('all')" id="filter-tab-all" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-teal-600 text-white shadow-2xs cursor-pointer">All (<span id="modal-tab-all-count">0</span>)</button>
          <button type="button" onclick="setPresentFilter('present')" id="filter-tab-present" class="px-2.5 py-1 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer">Present (<span id="modal-tab-present-count">0</span>)</button>
          <button type="button" onclick="setPresentFilter('tardy')" id="filter-tab-tardy" class="px-2.5 py-1 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer">Tardy (<span id="modal-tab-tardy-count">0</span>)</button>
        </div>
      </div>

      <!-- Modal Body (Scrollable List) -->
      <div class="p-5 overflow-y-auto space-y-2.5 flex-1 min-h-[260px] max-h-[460px]" id="modal-present-list">
        <!-- Rendered dynamically -->
      </div>

      <!-- Modal Footer -->
      <div class="p-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between text-xs text-slate-500">
        <span id="modal-footer-stats">Showing 0 verified records</span>
        <button type="button" onclick="closePresentStudentsModal()" class="btn btn-secondary btn-sm py-1.5 px-4 cursor-pointer">Close</button>
      </div>
    </div>
  </div>

  <?php include dirname(__DIR__) . '/partials/footer.php'; ?>
  <script src="<?= url('assets/js/qrcode-generator.js') ?>"></script>

  <script>
    const availableSections = <?= json_encode($teacherSections) ?>;
    let currentSection = <?= json_encode($selectedSectionKey) ?>;

    const ROTATION_INTERVAL_SECONDS = 1800; // 30 Minutes
    let remainingSeconds = 0;
    let activeQrCode = null;
    let activeSessionId = null;
    let qrGenerator = null;
    let timerInterval = null;
    let liveFeedPolling = null;
    let activeSectionsList = <?= json_encode($activeSectionsFromDb) ?> || [];
    let isSessionPaused = false;

    // Real-Time Digital Clock
    function updateRealtimeClock() {
      const now = new Date();
      let h = now.getHours();
      const m = String(now.getMinutes()).padStart(2, '0');
      const s = String(now.getSeconds()).padStart(2, '0');
      const ampm = h >= 12 ? 'PM' : 'AM';
      h = h % 12 || 12;
      const timeFormatted = `${String(h).padStart(2, '0')}:${m}:${s} ${ampm}`;
      
      const liveClockEl = document.getElementById('header-live-clock');
      if (liveClockEl) liveClockEl.textContent = timeFormatted;

      const pillEl = document.getElementById('header-clock-pill');
      if (pillEl) pillEl.textContent = timeFormatted;
    }
    setInterval(updateRealtimeClock, 1000);
    updateRealtimeClock();

    function formatCountdown(totalSecs) {
      if (totalSecs <= 0) return '00m 00s';
      const mins = Math.floor(totalSecs / 60);
      const secs = totalSecs % 60;
      return `${mins.toString().padStart(2, '0')}m ${secs.toString().padStart(2, '0')}s`;
    }

    function formatTimeAMPM(timeStr) {
      if (!timeStr) return '08:00 AM';
      const parts = timeStr.split(':');
      let h = parseInt(parts[0], 10);
      const m = parts[1] || '00';
      const ampm = h >= 12 ? 'PM' : 'AM';
      h = h % 12;
      h = h ? h : 12;
      return `${String(h).padStart(2, '0')}:${m} ${ampm}`;
    }

    // ── LOADING STATE CONTROLLERS (Card-Scoped, No Full-Page Blocking) ──

    function showQrLoading(show = true, title = 'Loading...', sub = 'Please wait') {
      const overlay = document.getElementById('qr-card-loading-overlay');
      if (!overlay) return;
      const titleEl = document.getElementById('qr-loading-text');
      const subEl = document.getElementById('qr-loading-sub');
      if (titleEl && title) titleEl.textContent = title;
      if (subEl && sub) subEl.textContent = sub;
      if (show) {
        overlay.classList.remove('hidden');
        overlay.classList.remove('is-hidden');
      } else {
        overlay.classList.add('is-hidden');
        setTimeout(() => {
          if (overlay.classList.contains('is-hidden')) {
            overlay.classList.add('hidden');
          }
        }, 280);
      }
    }

    function setMetricsLoading(show = true) {
      const container = document.getElementById('metric-cards-container');
      if (!container) return;
      const valEls = container.querySelectorAll('.metric-val');
      const skelEls = container.querySelectorAll('.metric-skeleton');
      valEls.forEach(el => {
        if (show) el.classList.add('hidden');
        else el.classList.remove('hidden');
      });
      skelEls.forEach(el => {
        if (show) el.classList.remove('hidden');
        else el.classList.add('hidden');
      });
    }

    function setLiveFeedLoading(show = true) {
      const list = document.getElementById('live-feed-list');
      if (!list || !show) return;
      list.innerHTML = `
        <div class="space-y-2.5 animate-pulse py-1">
          <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-slate-200"></div>
              <div class="space-y-1.5">
                <div class="h-3.5 w-28 bg-slate-200 rounded"></div>
                <div class="h-2.5 w-36 bg-slate-100 rounded"></div>
              </div>
            </div>
            <div class="h-5 w-16 bg-slate-200 rounded-full"></div>
          </div>
          <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-slate-200"></div>
              <div class="space-y-1.5">
                <div class="h-3.5 w-32 bg-slate-200 rounded"></div>
                <div class="h-2.5 w-24 bg-slate-100 rounded"></div>
              </div>
            </div>
            <div class="h-5 w-16 bg-slate-200 rounded-full"></div>
          </div>
          <div class="p-3 bg-slate-50 border border-slate-100 rounded-xl flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full bg-slate-200"></div>
              <div class="space-y-1.5">
                <div class="h-3.5 w-24 bg-slate-200 rounded"></div>
                <div class="h-2.5 w-32 bg-slate-100 rounded"></div>
              </div>
            </div>
            <div class="h-5 w-16 bg-slate-200 rounded-full"></div>
          </div>
        </div>
      `;
    }

    function setHeaderStatusLoading(show = true) {
      const badge = document.getElementById('header-status-badge');
      if (!badge) return;
      if (show) {
        badge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-teal-50 text-teal-700';
        badge.innerHTML = `
          <svg class="w-3 h-3 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
          <span class="text-[11px]">Syncing...</span>
        `;
      }
    }

    function toggleSectionSwitcherDropdown(forceClose = false) {
      const dropdown = document.getElementById('section-switcher-dropdown');
      const chevron = document.getElementById('switcher-chevron');
      const btn = document.getElementById('section-switcher-btn');
      if (!dropdown) return;

      const isHidden = dropdown.classList.contains('hidden');
      if (forceClose || !isHidden) {
        dropdown.classList.add('hidden');
        if (chevron) chevron.classList.remove('rotate-180');
        if (btn) btn.setAttribute('aria-expanded', 'false');
      } else {
        dropdown.classList.remove('hidden');
        if (chevron) chevron.classList.add('rotate-180');
        if (btn) btn.setAttribute('aria-expanded', 'true');
        const searchInput = document.getElementById('section-switcher-search');
        if (searchInput) {
          searchInput.value = '';
          filterSectionDropdownList('');
          setTimeout(() => searchInput.focus(), 50);
        }
      }
    }

    function selectSectionFromDropdown(secVal) {
      toggleSectionSwitcherDropdown(true);
      if (String(secVal) !== String(currentSection)) {
        onSectionChange(secVal);
      }
    }

    function filterSectionDropdownList(query = null) {
      const input = document.getElementById('section-switcher-search');
      const q = (query !== null ? query : (input ? input.value : '')).toLowerCase().trim();
      const options = document.querySelectorAll('.section-switcher-option');
      options.forEach(opt => {
        const text = opt.getAttribute('data-search-text') || '';
        if (!q || text.includes(q)) {
          opt.classList.remove('hidden');
        } else {
          opt.classList.add('hidden');
        }
      });
    }

    // Dismiss dropdown on click outside or Escape
    document.addEventListener('click', (e) => {
      const container = document.getElementById('section-switcher-container');
      if (container && !container.contains(e.target)) {
        toggleSectionSwitcherDropdown(true);
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        toggleSectionSwitcherDropdown(true);
      }
    });

    function updateSectionTabsUI(currentSec, activeSecs = activeSectionsList) {
      const secObj = availableSections.find(s => String(s.section) === String(currentSec)) || availableSections[0];

      // 1. Update trigger button elements
      const swActiveSec = document.getElementById('switcher-active-section');
      if (swActiveSec) swActiveSec.textContent = `Section ${secObj.section}`;

      const swActiveCode = document.getElementById('switcher-active-code');
      if (swActiveCode) swActiveCode.textContent = secObj.course_code || 'IT301';

      const swActiveSub = document.getElementById('switcher-active-sub');
      if (swActiveSub) swActiveSub.textContent = `Rm ${secObj.room_number || '402'} · ${formatTimeAMPM(secObj.scheduled_time)}`;

      // 2. Update list options
      const options = document.querySelectorAll('.section-switcher-option');
      options.forEach(opt => {
        const secVal = opt.getAttribute('data-section');
        const isSelected = String(secVal) === String(currentSec);

        opt.setAttribute('aria-selected', isSelected ? 'true' : 'false');

        // Selection styling
        if (isSelected) {
          opt.className = 'section-switcher-option w-full p-2.5 rounded-xl text-left transition-all duration-150 flex items-start justify-between gap-3 cursor-pointer select-none bg-teal-50 text-slate-900';
        } else {
          opt.className = 'section-switcher-option w-full p-2.5 rounded-xl text-left transition-all duration-150 flex items-start justify-between gap-3 cursor-pointer select-none hover:bg-slate-50 text-slate-700';
        }

        const checkIcon = opt.querySelector('.switcher-opt-check');
        if (checkIcon) {
          if (isSelected) checkIcon.classList.remove('hidden');
          else checkIcon.classList.add('hidden');
        }
      });

      // Synchronize hidden select if present
      const select = document.getElementById('section-select');
      if (select && select.value !== currentSec) {
        select.value = currentSec;
      }
    }

    function updateSectionLiveChips(activeSecs) {
      activeSectionsList = activeSecs || [];
      updateSectionTabsUI(currentSection, activeSectionsList);
      // Update dropdown option tags
      const select = document.getElementById('section-select');
      if (select) {
        Array.from(select.options).forEach(opt => {
          const secVal = opt.value;
          const isAct = activeSectionsList.includes(secVal);
          const secObj = availableSections.find(s => s.section === secVal);
          const courseCode = secObj ? secObj.course_code : 'IT301';
          opt.textContent = `Section ${secVal} (${courseCode})${isAct ? ' ● LIVE' : ''}`;
        });
      }
    }

    // ── CLIENT-SIDE CACHE LAYER (Instant Zero-Flicker Section Switching) ──
    const SESSION_CACHE_KEY = 'ams_live_session_cache_v2';
    window.AMS_SESSION_CACHE = {};

    function getSectionCache(sec) {
      const sKey = String(sec);
      if (window.AMS_SESSION_CACHE && window.AMS_SESSION_CACHE[sKey]) {
        return window.AMS_SESSION_CACHE[sKey];
      }
      try {
        const raw = sessionStorage.getItem(SESSION_CACHE_KEY);
        if (raw) {
          window.AMS_SESSION_CACHE = JSON.parse(raw) || {};
          return window.AMS_SESSION_CACHE[sKey] || null;
        }
      } catch (e) {}
      return null;
    }

    function setSectionCache(sec, patch) {
      const sKey = String(sec);
      window.AMS_SESSION_CACHE = window.AMS_SESSION_CACHE || {};
      window.AMS_SESSION_CACHE[sKey] = Object.assign({}, window.AMS_SESSION_CACHE[sKey] || {}, patch, {
        cachedAt: Date.now()
      });
      try {
        sessionStorage.setItem(SESSION_CACHE_KEY, JSON.stringify(window.AMS_SESSION_CACHE));
      } catch (e) {}
    }

    function applyCachedSectionState(secVal) {
      const cached = getSectionCache(secVal);
      if (!cached) return false;

      // 1. Restore QR Session State if valid
      if (cached.hasActive && cached.session && cached.session.qr_code) {
        const elapsedSecs = Math.floor((Date.now() - (cached.cachedAt || Date.now())) / 1000);
        const adjExpires = (cached.session.expires_in_seconds || 1800) - elapsedSecs;
        if (adjExpires > 5) {
          const adjSession = Object.assign({}, cached.session, { expires_in_seconds: adjExpires });
          showActiveQrState(adjSession);
        } else {
          showEmptyQrState();
        }
      } else if (cached.isReady) {
        showReadyToGenerateState();
      }

      // 2. Restore Feed & Metric Numbers instantly
      if (cached.metrics) {
        renderLiveFeed(cached.checkins || [], cached.metrics, cached.all_today_checkins || [], secVal);
      }

      return true;
    }

    // ── SECTION SWITCH HANDLER ──

    function onSectionChange(sectionVal) {
      // 1. Immediately pause background polling to prevent concurrent collisions
      if (liveFeedPolling) {
        clearInterval(liveFeedPolling);
        liveFeedPolling = null;
      }

      currentSection = String(sectionVal);
      activeQrCode = null;
      activeSessionId = null;
      isSessionPaused = false;
      updatePauseResumeUI();
      setCloseSessionButtonState(false);
      window.cachedActiveCheckins = [];
      window.cachedAllTodayCheckins = [];
      window.lastRenderSignature = '';

      // Persist selected section across refreshes (localStorage, cookie, URL query param)
      try {
        localStorage.setItem('ams_selected_section', currentSection);
      } catch (e) {}
      document.cookie = 'ams_selected_section=' + encodeURIComponent(currentSection) + '; path=/; max-age=2592000; SameSite=Lax';
      if (window.history && window.history.replaceState) {
        const url = new URL(window.location.href);
        url.searchParams.set('section', currentSection);
        window.history.replaceState(null, '', url.toString());
      }

      updateSectionTabsUI(currentSection, activeSectionsList);

      const secObj = availableSections.find(s => String(s.section) === String(sectionVal)) || availableSections[0];

      // 2. Update header details dynamically
      const titleEl = document.getElementById('header-course-title');
      if (titleEl) {
        titleEl.textContent = `${secObj.section} · ${secObj.course_code} (${secObj.course_title})`;
      }

      const schedEl = document.getElementById('header-scheduled-time');
      if (schedEl) schedEl.textContent = formatTimeAMPM(secObj.scheduled_time);

      const roomEl = document.getElementById('header-room-number');
      if (roomEl) roomEl.textContent = secObj.room_number || '402';

      const noteEl = document.getElementById('qr-footer-note');
      if (noteEl) {
        noteEl.innerHTML = `Students scan with an authenticated device enrolled in <strong>Section ${escapeHtml(secObj.section)}</strong>. Screenshots expire after 30 minutes.`;
      }

      const readyTitleEl = document.getElementById('ready-box-title');
      if (readyTitleEl) {
        readyTitleEl.textContent = `Section ${secObj.section}`;
      }

      const modalSecName = document.getElementById('modal-section-name');
      if (modalSecName) modalSecName.textContent = secObj.section;

      const modalHeaderSec = document.getElementById('modal-header-section');
      if (modalHeaderSec) modalHeaderSec.textContent = secObj.section;

      updateSectionLiveChips(activeSectionsList);

      // 3. Try to apply cached state for instant rendering (Zero Loading Flicker)
      const hasCachedState = applyCachedSectionState(sectionVal);

      if (!hasCachedState) {
        // Only show lightweight loading skeleton if no cache exists for this section yet
        showQrLoading(true, `Loading Section ${sectionVal}...`, 'Syncing dynamic QR & roster...');
        setMetricsLoading(true);
        setLiveFeedLoading(true);
        setHeaderStatusLoading(true);
      }

      // 4. Fetch active session & load live feed in background (Stale-While-Revalidate)
      Promise.all([
        checkActiveSession(sectionVal),
        loadLiveFeed(true)
      ]).finally(() => {
        if (String(currentSection) === String(sectionVal)) {
          setMetricsLoading(false);
          setLiveFeedLoading(false);
          setTimeout(() => {
            showQrLoading(false);
          }, 120);
        }
        // Resume background polling
        if (!liveFeedPolling) {
          liveFeedPolling = setInterval(() => loadLiveFeed(false), 3000);
        }
      });
    }

    function renderQRCode(code6Digits) {
      const container = document.getElementById('qrcode-container');
      if (!container) return;
      container.innerHTML = '';
      
      qrGenerator = new QRCode(container, {
        width: 200,
        height: 200,
        colorDark: '#000000',
        colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.H
      });
      // Generate standard 6-digit QR code
      qrGenerator.makeCode(String(code6Digits));

      // qrcodejs creates both a <canvas> and an <img>. Remove the unused one so only 1 single element exists
      setTimeout(() => {
        const canvasEl = container.querySelector('canvas');
        const imgEl = container.querySelector('img');
        if (imgEl && imgEl.src && imgEl.src.length > 50) {
          if (canvasEl) canvasEl.remove();
          imgEl.style.display = 'block';
        } else if (canvasEl) {
          if (imgEl) imgEl.remove();
          canvasEl.style.display = 'block';
        }
      }, 50);
    }

    function setCloseSessionButtonState(hasActive) {
      const btn = document.getElementById('btn-header-close-session');
      if (!btn) return;
      if (hasActive) {
        btn.disabled = false;
        btn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
        btn.classList.add('cursor-pointer');
        btn.title = 'Close active attendance session';
      } else {
        btn.disabled = true;
        btn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
        btn.classList.remove('cursor-pointer');
        btn.title = 'No active session to close';
      }
    }

    function updatePauseResumeUI() {
      const btnText = document.getElementById('text-pause-resume');
      const pauseIcon = document.getElementById('icon-pause-session');
      const resumeIcon = document.getElementById('icon-resume-session');
      const statusBadge = document.getElementById('header-status-badge');
      const tokenDisplay = document.getElementById('token-display');

      if (isSessionPaused) {
        if (btnText) btnText.textContent = 'Resume';
        if (pauseIcon) pauseIcon.classList.add('hidden');
        if (resumeIcon) resumeIcon.classList.remove('hidden');
        if (tokenDisplay) tokenDisplay.textContent = 'PAUSED';
        if (statusBadge) {
          statusBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-amber-50 text-amber-800 border border-amber-200/70';
          statusBadge.innerHTML = `
            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
            <span>Session Paused</span>
          `;
        }
      } else {
        if (btnText) btnText.textContent = 'Pause';
        if (pauseIcon) pauseIcon.classList.remove('hidden');
        if (resumeIcon) resumeIcon.classList.add('hidden');
        if (tokenDisplay && activeQrCode) tokenDisplay.textContent = activeQrCode;
        if (statusBadge && activeQrCode) {
          statusBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/70';
          statusBadge.innerHTML = `
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span>Active Session</span>
          `;
        }
      }
    }

    function togglePauseResumeSession() {
      if (!activeQrCode && !activeSessionId) {
        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast('No active session is running to pause.', 'info');
        }
        return;
      }

      isSessionPaused = !isSessionPaused;
      if (isSessionPaused) {
        if (timerInterval) {
          clearInterval(timerInterval);
          timerInterval = null;
        }
        updatePauseResumeUI();
        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast('Attendance QR timer paused.', 'info');
        }
      } else {
        updatePauseResumeUI();
        if (remainingSeconds > 0) {
          if (timerInterval) clearInterval(timerInterval);
          timerInterval = setInterval(tickTimer, 1000);
        }
        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast('Attendance QR timer resumed.', 'success');
        }
      }
    }

    function showReadyToGenerateState() {
      const readyBox = document.getElementById('qr-ready-box');
      const activeBox = document.getElementById('qr-active-box');
      const emptyBox = document.getElementById('qr-empty-box');
      const refreshBtn = document.getElementById('qr-refresh-btn-wrap');

      if (readyBox) { readyBox.classList.remove('hidden'); readyBox.classList.add('flex'); }
      if (activeBox) { activeBox.classList.add('hidden'); activeBox.classList.remove('flex'); }
      if (emptyBox) { emptyBox.classList.add('hidden'); emptyBox.classList.remove('flex'); }
      if (refreshBtn) refreshBtn.classList.add('hidden');

      activeQrCode = null;
      activeSessionId = null;
      isSessionPaused = false;
      updatePauseResumeUI();
      setCloseSessionButtonState(false);

      const tokenDisplay = document.getElementById('token-display');
      if (tokenDisplay) tokenDisplay.textContent = 'READY';

      // Set Inactive / Ready Badge
      const statusBadge = document.getElementById('header-status-badge');
      if (statusBadge) {
        statusBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-slate-100 text-slate-500';
        statusBadge.innerHTML = `
          <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
          <span class="text-[11px]">Inactive</span>
        `;
      }

      const countdownText = document.getElementById('countdown-text');
      if (countdownText) countdownText.textContent = '30m 00s';
      const timerBar = document.getElementById('qr-timer-bar');
      if (timerBar) timerBar.style.width = '0%';

      if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
      }
    }

    function showActiveQrState(session) {
      const readyBox = document.getElementById('qr-ready-box');
      const activeBox = document.getElementById('qr-active-box');
      const emptyBox = document.getElementById('qr-empty-box');
      const refreshBtn = document.getElementById('qr-refresh-btn-wrap');

      if (readyBox) { readyBox.classList.add('hidden'); readyBox.classList.remove('flex'); }
      if (activeBox) { activeBox.classList.remove('hidden'); activeBox.classList.add('flex'); }
      if (emptyBox) { emptyBox.classList.add('hidden'); emptyBox.classList.remove('flex'); }
      if (refreshBtn) refreshBtn.classList.remove('hidden');

      activeQrCode = session.qr_code;
      activeSessionId = session.qr_session_id;
      remainingSeconds = session.expires_in_seconds || ROTATION_INTERVAL_SECONDS;
      isSessionPaused = false;
      updatePauseResumeUI();
      setCloseSessionButtonState(true);

      const tokenDisplay = document.getElementById('token-display');
      if (tokenDisplay) tokenDisplay.textContent = session.qr_code;

      // Set Minimalist Active Badge
      const statusBadge = document.getElementById('header-status-badge');
      if (statusBadge) {
        statusBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-emerald-50 text-emerald-700';
        statusBadge.innerHTML = `
          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
          <span class="text-[11px] font-semibold">Live</span>
        `;
      }

      renderQRCode(session.qr_code);
      updateTimerDisplay();

      if (timerInterval) clearInterval(timerInterval);
      timerInterval = setInterval(tickTimer, 1000);
    }

    function showEmptyQrState() {
      const readyBox = document.getElementById('qr-ready-box');
      const activeBox = document.getElementById('qr-active-box');
      const emptyBox = document.getElementById('qr-empty-box');
      const refreshBtn = document.getElementById('qr-refresh-btn-wrap');

      if (readyBox) { readyBox.classList.add('hidden'); readyBox.classList.remove('flex'); }
      if (activeBox) { activeBox.classList.add('hidden'); activeBox.classList.remove('flex'); }
      if (emptyBox) { emptyBox.classList.remove('hidden'); emptyBox.classList.add('flex'); }
      if (refreshBtn) refreshBtn.classList.add('hidden');

      activeQrCode = null;
      activeSessionId = null;
      isSessionPaused = false;
      updatePauseResumeUI();
      setCloseSessionButtonState(false);

      const tokenDisplay = document.getElementById('token-display');
      if (tokenDisplay) tokenDisplay.textContent = 'EXPIRED';

      // Set Inactive Badge
      const statusBadge = document.getElementById('header-status-badge');
      if (statusBadge) {
        statusBadge.className = 'inline-flex items-center gap-1.5 px-2.5 py-0.5 text-xs font-medium rounded-full bg-slate-100 text-slate-500';
        statusBadge.innerHTML = `
          <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
          <span class="text-[11px]">Closed</span>
        `;
      }

      const countdownText = document.getElementById('countdown-text');
      if (countdownText) countdownText.textContent = '00m 00s';
      const timerBar = document.getElementById('qr-timer-bar');
      if (timerBar) timerBar.style.width = '0%';

      if (timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
      }
    }

    function tickTimer() {
      if (remainingSeconds <= 0) {
        showEmptyQrState();
        return;
      }
      remainingSeconds--;
      updateTimerDisplay();
      if (remainingSeconds <= 0) {
        showEmptyQrState();
        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast('QR session expired (30 mins limit reached).', 'warning');
        }
      }
    }

    function updateTimerDisplay() {
      document.getElementById('countdown-text').textContent = formatCountdown(remainingSeconds);
      const pct = Math.min(100, Math.max(0, (remainingSeconds / ROTATION_INTERVAL_SECONDS) * 100));
      const bar = document.getElementById('qr-timer-bar');
      bar.style.width = pct + '%';
      
      // Color shifts when under 5 minutes
      if (remainingSeconds <= 300) {
        bar.className = 'bg-gradient-to-r from-amber-500 to-rose-500 h-2 transition-all duration-1000 ease-linear';
      } else {
        bar.className = 'bg-gradient-to-r from-teal-500 to-emerald-500 h-2 transition-all duration-1000 ease-linear';
      }
    }

    // Generate / Rotate 6-digit QR session in database for the selected section
    async function manualGenerateQR(btnEl = null) {
      const targetBtn = btnEl || document.getElementById('btn-header-generate') || document.querySelector('.btn-primary');
      if (window.APP && typeof APP.setLoading === 'function' && targetBtn) {
        APP.setLoading(targetBtn, true, 'Generating...');
      }
      showQrLoading(true, 'Generating QR Code...', 'Creating 6-digit dynamic session...');

      try {
        const res = await fetch('<?= url("api/teacher/qr-session/generate") ?>', {
          method: 'POST',
          headers: { 
            'Content-Type': 'application/json',
            'Accept': 'application/json' 
          },
          body: JSON.stringify({ section: currentSection })
        });
        const data = await res.json();
        if (res.ok && data.status === 'success') {
          setSectionCache(currentSection, {
            hasActive: true,
            session: data.session,
            isReady: false
          });
          showActiveQrState(data.session);
          if (!activeSectionsList.includes(currentSection)) {
            activeSectionsList.push(currentSection);
            updateSectionLiveChips(activeSectionsList);
          }
          if (window.APP && typeof APP.showToast === 'function') {
            APP.showToast(`New 6-digit QR generated for Section ${currentSection} (${data.session.qr_code}) — 30m window started!`, 'success');
          }
          loadLiveFeed(true);
        } else {
          if (window.APP && typeof APP.showToast === 'function') {
            APP.showToast(data.message || 'Could not generate QR session', 'error');
          } else {
            alert(data.message || 'Could not generate QR session');
          }
        }
      } catch (err) {
        console.error('Error generating QR:', err);
        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast('Network error generating QR code', 'error');
        }
      } finally {
        setTimeout(() => {
          showQrLoading(false);
        }, 150);
        if (window.APP && typeof APP.setLoading === 'function' && targetBtn) {
          APP.setLoading(targetBtn, false);
        }
      }
    }

    // Check active session on load (DO NOT automatically generate QR if none exists)
    async function checkActiveSession(secVal) {
      const targetSec = secVal || currentSection;
      try {
        const res = await fetch(`<?= url("api/teacher/qr-session/active") ?>?section=${encodeURIComponent(targetSec)}`, {
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (String(targetSec) !== String(currentSection)) {
          return;
        }
        if (res.ok && data.active_sections) {
          updateSectionLiveChips(data.active_sections);
        }
        if (res.ok && data.has_active_session && data.session) {
          setSectionCache(targetSec, {
            hasActive: true,
            session: data.session,
            isReady: false
          });
          showActiveQrState(data.session);
        } else {
          setSectionCache(targetSec, {
            hasActive: false,
            session: null,
            isReady: true
          });
          showReadyToGenerateState();
        }
      } catch (err) {
        console.error('Error loading active session:', err);
        if (String(targetSec) === String(currentSection)) {
          showReadyToGenerateState();
        }
      }
    }

    window.cachedActiveCheckins = [];
    window.cachedAllTodayCheckins = [];
    window.currentPresentFilter = 'all';
    window.lastRenderSignature = '';

    // Load Live Attendance Feed & Metrics directly from database (NO FALLBACKS - Real Counts Only)
    async function loadLiveFeed(isManualRefresh = false, refreshBtnEl = null) {
      const reqSec = currentSection;
      const refreshIcon = refreshBtnEl ? refreshBtnEl.querySelector('svg') : null;
      if (refreshIcon) {
        refreshIcon.classList.add('animate-spin', 'text-teal-600');
      }

      try {
        const url = `<?= url("api/teacher/attendance/live-feed") ?>?section=${encodeURIComponent(reqSec)}`;
        const res = await fetch(url, {
          headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();

        // Discard response if user switched to another section while request was pending!
        if (String(reqSec) !== String(currentSection)) {
          return;
        }

        if (res.ok && data.status === 'success') {
          setSectionCache(reqSec, {
            checkins: data.checkins || [],
            metrics: data.metrics || {},
            all_today_checkins: data.all_today_checkins || []
          });

          const signature = JSON.stringify({
            sec: reqSec,
            activeCount: (data.checkins || []).length,
            allCount: (data.all_today_checkins || []).length,
            present: data.metrics?.present,
            tardy: data.metrics?.tardy,
            enrolled: data.metrics?.enrolled,
            pending: data.metrics?.pending,
            hasActive: data.has_active_session,
            lastId: data.checkins?.[0]?.attendance_id || 0
          });

          if (isManualRefresh || signature !== window.lastRenderSignature) {
            window.lastRenderSignature = signature;
            renderLiveFeed(data.checkins || [], data.metrics || {}, data.all_today_checkins || [], data.section || reqSec);
          }
        }
      } catch (err) {
        console.error('Error fetching live feed:', err);
      } finally {
        if (String(reqSec) === String(currentSection)) {
          setMetricsLoading(false);
        }
        if (refreshIcon) {
          setTimeout(() => {
            refreshIcon.classList.remove('animate-spin', 'text-teal-600');
          }, 350);
        }
      }
    }

    function renderLiveFeed(activeCheckins, metrics, allTodayCheckins, responseSection = null) {
      // Guard against out-of-order rendering for another section
      if (responseSection && String(responseSection) !== String(currentSection)) {
        return;
      }

      window.cachedActiveCheckins = activeCheckins || [];
      window.cachedAllTodayCheckins = (allTodayCheckins && allTodayCheckins.length > 0) 
        ? allTodayCheckins 
        : (window.cachedAllTodayCheckins.length > 0 ? window.cachedAllTodayCheckins : activeCheckins || []);

      // Ensure skeletons are turned off
      setMetricsLoading(false);

      // Update counters (Strict real data: show 0 if no data, never show numbers from other sections)
      const enrolledCount = (metrics && metrics.enrolled !== undefined) ? metrics.enrolled : 0;
      const presentCount = (metrics && metrics.present !== undefined) ? metrics.present : 0;
      const tardyCount = (metrics && metrics.tardy !== undefined) ? metrics.tardy : 0;
      const pendingCount = (metrics && metrics.pending !== undefined) ? metrics.pending : 0;

      document.getElementById('metric-enrolled').textContent = enrolledCount;
      document.getElementById('metric-present').textContent = presentCount;
      document.getElementById('metric-tardy').textContent = tardyCount;
      document.getElementById('metric-pending').textContent = pendingCount;
      
      const totalCheckedIn = (metrics && metrics.total_checked_in !== undefined) ? metrics.total_checked_in : window.cachedAllTodayCheckins.length;
      const activeCheckedIn = (activeCheckins && activeCheckins.length > 0) ? activeCheckins.length : 0;
      
      document.getElementById('feed-count').textContent = activeQrCode 
        ? `${activeCheckedIn} in active session` 
        : `${totalCheckedIn} checked in today`;
      document.getElementById('view-all-count-badge').textContent = totalCheckedIn;

      // Update Modal summary numbers
      document.getElementById('modal-present-count').textContent = presentCount;
      document.getElementById('modal-tardy-count').textContent = tardyCount;
      document.getElementById('modal-pending-count').textContent = `${pendingCount} students`;

      // Update Modal tab counts from all today records
      document.getElementById('modal-tab-all-count').textContent = totalCheckedIn;
      document.getElementById('modal-tab-present-count').textContent = presentCount;
      document.getElementById('modal-tab-tardy-count').textContent = tardyCount;
      document.getElementById('modal-present-badge-count').textContent = `${totalCheckedIn} Students`;

      const list = document.getElementById('live-feed-list');

      // If session is closed / no active checkins, show clean waiting state for the upcoming session
      if (!activeCheckins || activeCheckins.length === 0) {
        list.innerHTML = `
          <div class="flex flex-col items-center justify-center py-12 text-slate-400 text-center animate-fade-in">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-2 text-slate-400">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <p class="text-sm font-semibold text-slate-600">${activeQrCode ? 'Waiting for Student Scans' : 'No Active Live Scans'}</p>
            <p class="text-xs text-slate-400 mt-0.5">
              ${activeQrCode ? 'Students scanning the current 6-digit dynamic QR will appear here in real-time.' : 'Generate a 6-digit QR code to start receiving live student check-ins.'}
            </p>
            ${totalCheckedIn > 0 ? `
              <button type="button" onclick="openPresentStudentsModal()" class="mt-4 px-3 py-1.5 text-xs font-bold text-teal-700 bg-teal-50 hover:bg-teal-100 border border-teal-200 rounded-xl transition cursor-pointer flex items-center gap-1.5 shadow-2xs">
                <span>View All ${totalCheckedIn} Students Recorded Today →</span>
              </button>
            ` : ''}
          </div>
        `;
        return;
      }

      // ONLY SHOW TOP 5 ITEMS IN THE ACTIVE LIVE FEED
      const top5Checkins = activeCheckins.slice(0, 5);

      let html = top5Checkins.map(item => {
        let badgeClass = 'badge-present';
        let badgeText = '● Present';
        let borderBg = 'bg-emerald-50/70 border-emerald-200';
        let avatarBg = 'bg-emerald-600';

        if (item.status === 'tardy') {
          badgeClass = 'badge-tardy';
          badgeText = '● Tardy';
          borderBg = 'bg-amber-50/70 border-amber-200';
          avatarBg = 'bg-amber-600';
        } else if (item.status === 'absent') {
          badgeClass = 'bg-rose-100 text-rose-800 border border-rose-200 text-xs px-2 py-0.5 rounded-full font-semibold';
          badgeText = '● Absent';
          borderBg = 'bg-rose-50/70 border-rose-200';
          avatarBg = 'bg-rose-600';
        }

        return `
          <div class="p-3 ${borderBg} border rounded-xl flex items-center justify-between animate-fade-in transition hover:shadow-2xs">
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-full ${avatarBg} text-white flex items-center justify-center font-bold text-xs shadow-xs">
                ${escapeHtml(item.initials)}
              </div>
              <div>
                <h4 class="text-sm font-bold text-slate-800">${escapeHtml(item.student_name)}</h4>
                <p class="text-xs font-mono text-slate-600">${escapeHtml(item.student_number)} · Dynamic 6-Digit QR</p>
              </div>
            </div>
            <div class="text-right">
              <span class="${badgeClass}">${badgeText}</span>
              <p class="text-[11px] text-text-muted mt-0.5 font-mono">${escapeHtml(item.time)}</p>
            </div>
          </div>
        `;
      }).join('');

      // If more than 5 in active session, or if multiple sessions exist today, show the View All banner
      if (activeCheckins.length > 5 || totalCheckedIn > activeCheckins.length) {
        html += `
          <div class="pt-2 text-center">
            <button type="button" onclick="openPresentStudentsModal()" class="w-full py-2 px-3 text-xs font-bold text-teal-700 hover:text-teal-800 bg-teal-50/70 hover:bg-teal-100/80 border border-teal-200 rounded-xl transition flex items-center justify-center gap-2 cursor-pointer shadow-2xs">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <span>View All ${totalCheckedIn} Present &amp; Checked-In Students →</span>
            </button>
          </div>
        `;
      }

      list.innerHTML = html;

      // If modal is currently open, refresh its content too
      if (!document.getElementById('present-students-modal').classList.contains('hidden')) {
        renderPresentModalList();
      }
    }

    function openPresentStudentsModal() {
      document.getElementById('present-students-modal').classList.remove('hidden');
      document.getElementById('present-search-input').value = '';
      window.currentPresentFilter = 'all';
      updateFilterTabsUI();
      renderPresentModalList();
    }

    function closePresentStudentsModal() {
      document.getElementById('present-students-modal').classList.add('hidden');
    }

    function setPresentFilter(filter) {
      window.currentPresentFilter = filter;
      updateFilterTabsUI();
      renderPresentModalList();
    }

    function updateFilterTabsUI() {
      const tabs = ['all', 'present', 'tardy'];
      tabs.forEach(t => {
        const btn = document.getElementById(`filter-tab-${t}`);
        if (!btn) return;
        if (window.currentPresentFilter === t) {
          btn.className = 'px-2.5 py-1 text-xs font-bold rounded-lg bg-teal-600 text-white shadow-2xs cursor-pointer';
        } else {
          btn.className = 'px-2.5 py-1 text-xs font-semibold rounded-lg text-slate-600 hover:bg-slate-100 cursor-pointer';
        }
      });
    }

    function filterPresentModalList() {
      renderPresentModalList();
    }

    function renderPresentModalList() {
      const listEl = document.getElementById('modal-present-list');
      const searchVal = (document.getElementById('present-search-input').value || '').trim().toLowerCase();
      const filter = window.currentPresentFilter || 'all';

      // Use all today checkins (retained even when session is closed)
      let items = (window.cachedAllTodayCheckins && window.cachedAllTodayCheckins.length > 0)
        ? window.cachedAllTodayCheckins
        : window.cachedActiveCheckins;

      // Filter by status tab
      if (filter === 'present') {
        items = items.filter(i => i.status === 'present');
      } else if (filter === 'tardy') {
        items = items.filter(i => i.status === 'tardy');
      }

      // Filter by search keyword
      if (searchVal) {
        items = items.filter(i => 
          (i.student_name && i.student_name.toLowerCase().includes(searchVal)) ||
          (i.student_number && String(i.student_number).toLowerCase().includes(searchVal)) ||
          (i.subject && i.subject.toLowerCase().includes(searchVal))
        );
      }

      const totalRecords = window.cachedAllTodayCheckins.length || window.cachedActiveCheckins.length;
      document.getElementById('modal-footer-stats').textContent = `Showing ${items.length} of ${totalRecords} total student records for Section ${currentSection}`;

      if (items.length === 0) {
        listEl.innerHTML = `
          <div class="flex flex-col items-center justify-center py-12 text-slate-400 text-center">
            <svg class="w-10 h-10 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm font-semibold text-slate-600">No matching students found</p>
            <p class="text-xs text-slate-400 mt-0.5">Try clearing your search query or selecting a different status tab.</p>
          </div>
        `;
        return;
      }

      listEl.innerHTML = items.map((item, idx) => {
        let badgeClass = 'badge-present';
        let badgeText = '● Present';
        let borderBg = 'bg-white border-slate-200';
        let avatarBg = 'bg-emerald-600';

        if (item.status === 'tardy') {
          badgeClass = 'badge-tardy';
          badgeText = '● Tardy';
          borderBg = 'bg-amber-50/40 border-amber-200';
          avatarBg = 'bg-amber-600';
        } else if (item.status === 'absent') {
          badgeClass = 'bg-rose-100 text-rose-800 border border-rose-200 text-xs px-2 py-0.5 rounded-full font-semibold';
          badgeText = '● Absent';
          borderBg = 'bg-rose-50/40 border-rose-200';
          avatarBg = 'bg-rose-600';
        }

        return `
          <div class="p-3.5 ${borderBg} border rounded-xl flex items-center justify-between hover:bg-slate-50 transition shadow-2xs">
            <div class="flex items-center gap-3">
              <span class="text-xs font-mono font-bold text-slate-400 w-5 text-right">${idx + 1}.</span>
              <div class="w-10 h-10 rounded-full ${avatarBg} text-white flex items-center justify-center font-bold text-xs shadow-xs shrink-0">
                ${escapeHtml(item.initials)}
              </div>
              <div>
                <h4 class="text-sm font-bold text-slate-900">${escapeHtml(item.student_name)}</h4>
                <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mt-0.5">
                  <span class="font-semibold text-slate-700">${escapeHtml(item.student_number)}</span>
                  <span>•</span>
                  <span>${escapeHtml(item.subject || 'Web Systems')}</span>
                </div>
              </div>
            </div>
            <div class="text-right shrink-0">
              <span class="${badgeClass}">${badgeText}</span>
              <p class="text-[11px] text-slate-500 mt-1 font-mono">${escapeHtml(item.time)}</p>
            </div>
          </div>
        `;
      }).join('');
    }

    function escapeHtml(str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function openCloseSessionModal() {
      if (!activeQrCode && !activeSessionId) {
        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast('No active QR session is running to close.', 'info');
        }
        return;
      }
      document.getElementById('close-session-modal').classList.remove('hidden');
    }

    function closeModal() {
      document.getElementById('close-session-modal').classList.add('hidden');
    }

    async function executeCloseSession(btnEl = null) {
      const btn = btnEl || document.getElementById('confirm-close-btn');
      if (btn && window.APP && typeof APP.setLoading === 'function') {
        APP.setLoading(btn, true, 'Closing...');
      } else if (btn) {
        btn.disabled = true;
        btn.textContent = 'Closing...';
      }
      showQrLoading(true, 'Closing Session...', 'Processing absences...');

      try {
        const res = await fetch('<?= url("api/teacher/qr-session/close") ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          },
          body: JSON.stringify({ 
            qr_session_id: activeSessionId,
            section: currentSection
          })
        });
        const data = await res.json();

        closeModal();
        showEmptyQrState();

        // Remove from active sections list and clear cache for closed session
        activeSectionsList = activeSectionsList.filter(s => s !== currentSection);
        updateSectionLiveChips(activeSectionsList);
        setSectionCache(currentSection, {
          hasActive: false,
          session: null,
          isReady: false
        });

        // Clear active session in local state
        activeQrCode = null;
        activeSessionId = null;
        window.cachedActiveCheckins = [];

        // Reload feed to get updated absences & today's totals
        loadLiveFeed(true);

        if (window.APP && typeof APP.showToast === 'function') {
          APP.showToast(data.message || 'Session closed successfully.', 'success');
        }
      } catch (err) {
        console.error('Error closing session:', err);
        closeModal();
      } finally {
        showQrLoading(false);
        if (btn && window.APP && typeof APP.setLoading === 'function') {
          APP.setLoading(btn, false);
        } else if (btn) {
          btn.disabled = false;
          btn.textContent = 'Close Session';
        }
      }
    }

    // Lifecycle setup
    document.addEventListener('DOMContentLoaded', () => {
      // Check if URL or localStorage has a persisted section to restore
      let persistedSec = null;
      try {
        const urlParams = new URLSearchParams(window.location.search);
        const urlSec = urlParams.get('section');
        const storedSec = localStorage.getItem('ams_selected_section');
        persistedSec = urlSec || storedSec;
      } catch (e) {}

      if (persistedSec && availableSections.some(s => String(s.section) === String(persistedSec)) && String(persistedSec) !== String(currentSection)) {
        onSectionChange(persistedSec);
        return;
      }

      updateSectionTabsUI(currentSection, activeSectionsList);

      // 1. Check for instantaneous cache restore (Zero Loading Flicker)
      const hasCachedState = applyCachedSectionState(currentSection);

      if (!hasCachedState) {
        // Only show initial loaders if no cached snapshot is present
        setMetricsLoading(true);
        setLiveFeedLoading(true);
        setHeaderStatusLoading(true);
        showQrLoading(true, `Loading Section ${currentSection}...`, 'Checking session status...');
      }

      // 2. Fetch fresh status & database metrics in background (Stale-While-Revalidate)
      Promise.all([
        checkActiveSession(currentSection),
        loadLiveFeed(true)
      ]).finally(() => {
        setMetricsLoading(false);
        setLiveFeedLoading(false);
        setTimeout(() => {
          showQrLoading(false);
        }, 120);
      });

      // 3. Poll real database feed every 3 seconds
      liveFeedPolling = setInterval(() => loadLiveFeed(false), 3000);
    });

    window.addEventListener('beforeunload', () => {
      if (timerInterval) clearInterval(timerInterval);
      if (liveFeedPolling) clearInterval(liveFeedPolling);
    });
  </script>
</body>
</html>
<script>APP.highlightNav('classes');</script>
