<?php $page_title = 'System Settings'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <h1 class="text-2xl font-bold mb-6" style="color:var(--color-text-primary)">System Settings</h1>

        <div class="max-w-2xl space-y-6">
          <!-- Library Hours -->
          <div class="bg-white rounded-lg p-6 shadow-card">
            <h3 class="text-lg font-semibold mb-4" style="color:var(--color-text-primary)">Library Hours</h3>
            <div class="grid grid-cols-2 gap-4 mb-4">
              <div>
                <label class="form-label">Open Time</label>
                <input type="time" class="form-input" value="07:30">
              </div>
              <div>
                <label class="form-label">Close Time</label>
                <input type="time" class="form-input" value="17:00">
              </div>
            </div>
            <div>
              <label class="form-label">Tardy Threshold (minutes after open)</label>
              <input type="number" class="form-input w-32" value="15" min="1" max="120">
              <p class="form-helper">Students arriving after this many minutes past open time are marked tardy</p>
            </div>
          </div>

          <!-- Academic Year -->
          <div class="bg-white rounded-lg p-6 shadow-card">
            <h3 class="text-lg font-semibold mb-4" style="color:var(--color-text-primary)">Academic Year</h3>
            <div class="mb-4">
              <label class="form-label">Current Academic Year</label>
              <input type="text" class="form-input" value="2025-2026">
            </div>
            <div>
              <label class="form-label">Current Semester</label>
              <select class="form-input form-select">
                <option>First Semester</option>
                <option selected>Second Semester</option>
              </select>
            </div>
          </div>

          <!-- SMTP Configuration -->
          <div class="bg-white rounded-lg p-6 shadow-card">
            <h3 class="text-lg font-semibold mb-4" style="color:var(--color-text-primary)">Email / SMTP Configuration</h3>
            <div class="space-y-4">
              <div>
                <label class="form-label">SMTP Host</label>
                <input type="text" class="form-input" placeholder="smtp.gmail.com">
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="form-label">SMTP Port</label>
                  <input type="number" class="form-input" value="587">
                </div>
                <div>
                  <label class="form-label">Encryption</label>
                  <select class="form-input form-select">
                    <option>TLS</option>
                    <option>SSL</option>
                    <option>None</option>
                  </select>
                </div>
              </div>
              <div>
                <label class="form-label">SMTP Username</label>
                <input type="email" class="form-input" placeholder="noreply@bestlink.edu.ph">
              </div>
              <div>
                <label class="form-label">SMTP Password</label>
                <input type="password" class="form-input" placeholder="••••••••">
              </div>
              <div>
                <label class="form-label">From Name</label>
                <input type="text" class="form-input" value="BCP Attendance System">
              </div>
              <button type="button" class="btn btn-secondary btn-sm">Send Test Email</button>
            </div>
          </div>

          <!-- Alert Settings -->
          <div class="bg-white rounded-lg p-6 shadow-card">
            <h3 class="text-lg font-semibold mb-4" style="color:var(--color-text-primary)">Alert Settings</h3>
            <div class="space-y-3">
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" checked class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                <span class="text-sm">Enable parent email alerts for tardiness</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" checked class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                <span class="text-sm">Enable parent email alerts for absences</span>
              </label>
              <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" checked class="w-4 h-4" style="accent-color:var(--color-teal-500)">
                <span class="text-sm">Enable perfect attendance notifications</span>
              </label>
            </div>
          </div>

          <!-- Save -->
          <div class="flex justify-end">
            <button class="btn btn-primary btn-lg">Save All Settings</button>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('settings');</script>
