<?php $page_title = 'RFID / QR Scanner'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
          <div class="flex items-center gap-3">
            <a href="/Attendance _Management_System/includes/views/attendance/daily.php" class="text-sm" style="color:var(--color-teal-500)">← Back</a>
            <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">RFID / QR Scanner</h1>
          </div>
          <button type="button" class="btn btn-secondary btn-sm" onclick="APP.openManualEntryModal()">Manual Entry</button>
        </div>

        <!-- Scanner Area -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <!-- RFID Panel -->
          <div class="bg-white rounded-lg p-8 shadow-card text-center">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:var(--color-teal-100)">
              <span class="text-3xl">📡</span>
            </div>
            <h3 class="text-lg font-semibold mb-2" style="color:var(--color-text-primary)">RFID Tap</h3>
            <p class="text-sm mb-4" style="color:var(--color-text-secondary)">Tap your ID card on the RFID reader</p>
            <div class="w-full h-24 rounded-lg flex items-center justify-center" style="background:var(--color-surface); border:2px dashed var(--color-border)">
              <p class="text-sm" style="color:var(--color-text-muted)">Waiting for RFID scan...</p>
            </div>
          </div>

          <!-- QR Panel -->
          <div class="bg-white rounded-lg p-8 shadow-card text-center">
            <div class="w-20 h-20 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:var(--color-teal-100)">
              <span class="text-3xl">📷</span>
            </div>
            <h3 class="text-lg font-semibold mb-2" style="color:var(--color-text-primary)">QR Code Camera</h3>
            <p class="text-sm mb-4" style="color:var(--color-text-secondary)">Show your QR code to the camera</p>
            <div id="qr-reader" class="w-full rounded-lg overflow-hidden" style="height:240px; background:var(--color-navy-950); display:flex; align-items:center; justify-content:center;">
              <div class="text-center">
                <div class="w-32 h-32 mx-auto border-2 rounded-lg mb-3" style="border-color:var(--color-teal-300)"></div>
                <p class="text-sm" style="color:var(--color-text-muted)">Camera feed placeholder</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Divider -->
        <div class="flex items-center gap-4 mb-6">
          <hr class="flex-1" style="border-color:var(--color-border)">
          <span class="text-sm font-medium" style="color:var(--color-text-muted)">OR SEARCH</span>
          <hr class="flex-1" style="border-color:var(--color-border)">
        </div>

        <!-- Search Fallback -->
        <div class="bg-white rounded-lg p-5 shadow-card mb-6">
          <div class="flex flex-col sm:flex-row gap-3">
            <input type="text" class="form-input flex-1" placeholder="Student ID / Name...">
            <button class="btn btn-primary" onclick="showScanOverlay('present')">Find & Record</button>
          </div>
        </div>

        <!-- Direction Toggle -->
        <div class="bg-white rounded-lg p-5 shadow-card">
          <p class="text-sm font-medium mb-3" style="color:var(--color-text-primary)">Direction</p>
          <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="direction" value="entry" checked class="w-4 h-4" style="accent-color:var(--color-teal-500)">
              <span class="text-sm font-medium">Entry</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
              <input type="radio" name="direction" value="exit" class="w-4 h-4" style="accent-color:var(--color-teal-500)">
              <span class="text-sm font-medium">Exit</span>
            </label>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Scan Confirmation Overlay (hidden by default) -->
  <div id="scan-overlay" class="hidden status-present">
    <div class="text-center text-white">
      <!-- Student Photo Placeholder -->
      <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-white/20 flex items-center justify-center text-3xl font-bold">JD</div>
      <h2 class="text-xl font-bold mb-1">Juan Dela Cruz</h2>
      <p class="text-sm opacity-80 mb-6">Grade 7 · Section A</p>

      <!-- Status Band -->
      <div class="bg-white/20 rounded-lg px-8 py-4 mb-6 inline-block">
        <p class="text-3xl font-bold" id="scan-status-text">PRESENT</p>
        <p class="text-sm opacity-80 mt-1">08:04 AM · QR Scan</p>
      </div>

      <p class="text-sm opacity-60" id="scan-dismiss">Dismissing in <span id="scan-countdown">3</span>s...</p>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php $page_js = '<script src="/Attendance _Management_System/assets/js/scanner.js"></script>'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('scan');</script>
