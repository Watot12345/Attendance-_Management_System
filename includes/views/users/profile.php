<?php $page_title = 'My Profile'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6 flex items-start justify-center" style="background:var(--color-surface)">
        <div class="w-full max-w-lg">
          <h1 class="text-2xl font-bold mb-6" style="color:var(--color-text-primary)">My Profile</h1>

          <div class="bg-white rounded-lg p-6 shadow-card">
            <!-- Avatar -->
            <div class="flex items-center gap-4 mb-6">
              <div class="w-16 h-16 rounded-full flex items-center justify-center text-2xl font-bold" style="background:var(--color-teal-100); color:var(--color-teal-500)">AS</div>
              <div>
                <p class="font-semibold" style="color:var(--color-text-primary)">Admin Santos</p>
                <p class="text-sm" style="color:var(--color-text-muted)">Administrator</p>
                <button class="text-sm mt-1 font-medium" style="color:var(--color-teal-500)">Change Avatar</button>
              </div>
            </div>

            <form onsubmit="event.preventDefault();">
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="form-label">First Name</label>
                  <input type="text" class="form-input" value="Admin">
                </div>
                <div>
                  <label class="form-label">Last Name</label>
                  <input type="text" class="form-input" value="Santos">
                </div>
              </div>

              <div class="mb-4">
                <label class="form-label">Email</label>
                <input type="email" class="form-input" value="admin@bestlink.edu.ph" readonly style="background:var(--color-surface); cursor:not-allowed">
                <p class="form-helper">Contact administrator to change email</p>
              </div>

              <div class="mb-6">
                <label class="form-label">Phone</label>
                <input type="tel" class="form-input" value="+63 912 345 6789">
              </div>

              <hr class="my-6" style="border-color:var(--color-border)">

              <h3 class="text-base font-semibold mb-4" style="color:var(--color-text-primary)">Change Password</h3>
              <div class="mb-4">
                <label class="form-label">Current Password</label>
                <input type="password" class="form-input" placeholder="••••••••">
              </div>
              <div class="grid grid-cols-2 gap-4 mb-6">
                <div>
                  <label class="form-label">New Password</label>
                  <input type="password" class="form-input" placeholder="Min 8 characters">
                </div>
                <div>
                  <label class="form-label">Confirm New</label>
                  <input type="password" class="form-input" placeholder="Repeat password">
                </div>
              </div>

              <div class="flex justify-end gap-3">
                <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>
