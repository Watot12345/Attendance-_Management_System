<?php $page_title = 'Edit User'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6 flex items-start justify-center" style="background:var(--color-surface)">
        <div class="w-full max-w-lg">
          <div class="mb-6">
            <a href="<?php echo url('users'); ?>" class="text-sm" style="color:var(--color-teal-500)">← Back to Users</a>
            <h1 class="text-2xl font-bold mt-2" style="color:var(--color-text-primary)">Edit User</h1>
          </div>

          <div class="bg-white rounded-lg p-6 shadow-card">
            <form onsubmit="event.preventDefault();">
              <!-- Role (read-only) -->
              <div class="mb-4">
                <label class="form-label">Role</label>
                <div class="form-input" style="background:var(--color-surface); cursor:not-allowed">Student</div>
              </div>

              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label for="edit-fname" class="form-label">First Name</label>
                  <input type="text" id="edit-fname" class="form-input" value="Juan">
                </div>
                <div>
                  <label for="edit-lname" class="form-label">Last Name</label>
                  <input type="text" id="edit-lname" class="form-input" value="Dela Cruz">
                </div>
              </div>

              <div class="mb-4">
                <label for="edit-email" class="form-label">Email</label>
                <input type="email" id="edit-email" class="form-input" value="jdelacruz@bestlink.edu.ph">
              </div>

              <div class="mb-4">
                <label for="edit-phone" class="form-label">Phone</label>
                <input type="tel" id="edit-phone" class="form-input" value="+63 912 345 6789">
              </div>

              <div class="mb-4">
                <label for="edit-status" class="form-label">Status</label>
                <select id="edit-status" class="form-input form-select">
                  <option value="active" selected>Active</option>
                  <option value="inactive">Inactive</option>
                  <option value="suspended">Suspended</option>
                </select>
              </div>

              <hr class="my-4" style="border-color:var(--color-border)">

              <!-- Student-specific -->
              <h3 class="text-base font-semibold mb-3" style="color:var(--color-text-primary)">Student Details</h3>
              <div class="mb-4">
                <label class="form-label">Student Code</label>
                <input type="text" class="form-input" value="BCP-001" readonly style="background:var(--color-surface)">
              </div>
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label class="form-label">Grade</label>
                  <select class="form-input form-select"><option selected>Grade 7</option></select>
                </div>
                <div>
                  <label class="form-label">Section</label>
                  <select class="form-input form-select"><option selected>Section A</option></select>
                </div>
              </div>

              <hr class="my-4" style="border-color:var(--color-border)">

              <!-- Reset Password -->
              <div class="p-4 rounded-lg mb-4" style="background:var(--color-surface)">
                <p class="text-sm font-medium mb-2" style="color:var(--color-text-primary)">Password</p>
                <button type="button" class="btn btn-secondary btn-sm">Reset Password</button>
                <p class="form-helper mt-1">A temporary password will be generated and must be changed at next login</p>
              </div>

              <div class="flex justify-end gap-3">
                <a href="<?php echo url('users'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
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

<script>APP.highlightNav('users');</script>
