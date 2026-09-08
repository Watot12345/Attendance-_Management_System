<?php $page_title = 'Create User'; ?>
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
            <h1 class="text-2xl font-bold mt-2" style="color:var(--color-text-primary)">Create User</h1>
          </div>

          <div class="bg-white rounded-lg p-6 shadow-card">
            <form onsubmit="event.preventDefault();">
              <!-- Role -->
              <div class="mb-4">
                <label for="user-role" class="form-label">Role</label>
                <select id="user-role" class="form-input form-select" onchange="toggleRoleFields(this.value)">
                  <option value="student">Student</option>
                  <option value="teacher">Teacher</option>
                  <option value="admin">Admin</option>
                  <option value="parent">Parent</option>
                </select>
              </div>

              <!-- Base Fields -->
              <div class="grid grid-cols-2 gap-4 mb-4">
                <div>
                  <label for="user-fname" class="form-label">First Name</label>
                  <input type="text" id="user-fname" class="form-input" placeholder="Juan">
                </div>
                <div>
                  <label for="user-lname" class="form-label">Last Name</label>
                  <input type="text" id="user-lname" class="form-input" placeholder="Dela Cruz">
                </div>
              </div>

              <div class="mb-4">
                <label for="user-email" class="form-label">Email</label>
                <input type="email" id="user-email" class="form-input" placeholder="user@bestlink.edu.ph">
              </div>

              <div class="mb-4">
                <label for="user-password" class="form-label">Password</label>
                <input type="password" id="user-password" class="form-input" placeholder="Minimum 8 characters">
                <p class="form-helper">Auto-generated password recommended for batch creation</p>
              </div>

              <div class="mb-4">
                <label for="user-phone" class="form-label">Phone (optional)</label>
                <input type="tel" id="user-phone" class="form-input" placeholder="+63 912 345 6789">
              </div>

              <!-- Student-specific fields -->
              <div id="student-fields">
                <hr class="my-4" style="border-color:var(--color-border)">
                <h3 class="text-base font-semibold mb-3" style="color:var(--color-text-primary)">Student Details</h3>
                <div class="mb-4">
                  <label for="student-code" class="form-label">Student Code (LRN)</label>
                  <input type="text" id="student-code" class="form-input" placeholder="e.g., 123456789012">
                </div>
                <div class="grid grid-cols-2 gap-4 mb-4">
                  <div>
                    <label for="student-grade" class="form-label">Grade Level</label>
                    <select id="student-grade" class="form-input form-select">
                      <option>Grade 7</option>
                      <option>Grade 8</option>
                      <option>Grade 9</option>
                      <option>Grade 10</option>
                    </select>
                  </div>
                  <div>
                    <label for="student-section" class="form-label">Section</label>
                    <select id="student-section" class="form-input form-select">
                      <option>Section A</option>
                      <option>Section B</option>
                      <option>Section C</option>
                    </select>
                  </div>
                </div>
                <div class="mb-4">
                  <label for="student-parent" class="form-label">Linked Parent Account</label>
                  <select id="student-parent" class="form-input form-select">
                    <option value="">None (create later)</option>
                    <option>Dela Cruz, Maria (Parent)</option>
                  </select>
                </div>
              </div>

              <!-- Teacher-specific fields -->
              <div id="teacher-fields" class="hidden">
                <hr class="my-4" style="border-color:var(--color-border)">
                <h3 class="text-base font-semibold mb-3" style="color:var(--color-text-primary)">Teacher Details</h3>
                <div class="mb-4">
                  <label for="teacher-empid" class="form-label">Employee ID</label>
                  <input type="text" id="teacher-empid" class="form-input" placeholder="EMP-XX">
                </div>
                <div class="mb-4">
                  <label for="teacher-dept" class="form-label">Department</label>
                  <select id="teacher-dept" class="form-input form-select">
                    <option>Mathematics</option>
                    <option>Science</option>
                    <option>English</option>
                    <option>Filipino</option>
                    <option>Social Studies</option>
                  </select>
                </div>
              </div>

              <!-- Parent-specific fields -->
              <div id="parent-fields" class="hidden">
                <hr class="my-4" style="border-color:var(--color-border)">
                <h3 class="text-base font-semibold mb-3" style="color:var(--color-text-primary)">Parent Details</h3>
                <div class="mb-4">
                  <label for="parent-child" class="form-label">Linked Student</label>
                  <select id="parent-child" class="form-input form-select">
                    <option value="">Select student...</option>
                    <option>Dela Cruz, Juan · BCP-001</option>
                    <option>Santos, Maria · BCP-002</option>
                  </select>
                </div>
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-3 mt-6">
                <a href="<?php echo url('users'); ?>" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create User</button>
              </div>
            </form>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php $page_js = '<script src="../../../assets/js/users.js"></script>'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('users');</script>
