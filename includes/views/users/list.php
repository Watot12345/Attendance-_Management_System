<?php $page_title = 'User Management'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
          <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">User Management</h1>
          <a href="<?php echo url('users/create'); ?>" class="btn btn-primary">+ Create User</a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-card mb-4">
          <div class="p-4 flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
              <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
              <input type="text" class="form-input pl-9" placeholder="Search name or email...">
            </div>
            <select class="form-input form-select w-auto">
              <option>All Roles</option>
              <option>Admin</option>
              <option>Teacher</option>
              <option>Student</option>
              <option>Parent</option>
            </select>
            <select class="form-input form-select w-auto">
              <option>Active</option>
              <option>Inactive</option>
              <option>All Status</option>
            </select>
          </div>
        </div>

        <!-- User Table -->
        <div class="bg-white rounded-lg shadow-card overflow-x-auto">
          <table class="data-table">
            <thead>
              <tr>
                <th scope="col">Name</th>
                <th scope="col">Email</th>
                <th scope="col">Role</th>
                <th scope="col">Status</th>
                <th scope="col">Last Login</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td class="font-medium">Santos, Dr. Admin</td>
                <td class="text-sm" style="color:var(--color-text-muted)">admin@bestlink.edu.ph</td>
                <td><span class="badge" style="background:var(--color-teal-100); color:var(--color-teal-500); border-color:var(--color-teal-300)">Admin</span></td>
                <td><span class="badge badge-present">Active</span></td>
                <td class="text-sm" style="color:var(--color-text-muted)">Sep 7, 2026</td>
                <td>
                  <div class="flex gap-1">
                    <a href="<?php echo url('users/edit'); ?>" class="btn btn-ghost btn-sm">Edit</a>
                  </div>
                </td>
              </tr>
              <tr>
                <td class="font-medium">Cruz, Mr. B.</td>
                <td class="text-sm" style="color:var(--color-text-muted)">bcruz@bestlink.edu.ph</td>
                <td><span class="badge badge-excused">Teacher</span></td>
                <td><span class="badge badge-present">Active</span></td>
                <td class="text-sm" style="color:var(--color-text-muted)">Sep 6, 2026</td>
                <td>
                  <div class="flex gap-1">
                    <a href="<?php echo url('users/edit'); ?>" class="btn btn-ghost btn-sm">Edit</a>
                    <button class="btn btn-ghost btn-sm" style="color:var(--color-absent)" onclick="APP.confirm('Delete this user?', function(){})">Delete</button>
                  </div>
                </td>
              </tr>
              <tr>
                <td class="font-medium">Dela Cruz, Juan</td>
                <td class="text-sm" style="color:var(--color-text-muted)">jdelacruz@bestlink.edu.ph</td>
                <td><span class="badge badge-tardy">Student</span></td>
                <td><span class="badge badge-present">Active</span></td>
                <td class="text-sm" style="color:var(--color-text-muted)">Sep 7, 2026</td>
                <td>
                  <div class="flex gap-1">
                    <a href="<?php echo url('users/edit'); ?>" class="btn btn-ghost btn-sm">Edit</a>
                    <button class="btn btn-ghost btn-sm" style="color:var(--color-absent)" onclick="APP.confirm('Delete this user?', function(){})">Delete</button>
                  </div>
                </td>
              </tr>
              <tr>
                <td class="font-medium">Dela Cruz, Maria (Parent)</td>
                <td class="text-sm" style="color:var(--color-text-muted)">maria.delacruzmom@email.com</td>
                <td><span class="badge badge-pending">Parent</span></td>
                <td><span class="badge badge-present">Active</span></td>
                <td class="text-sm" style="color:var(--color-text-muted)">Sep 5, 2026</td>
                <td>
                  <div class="flex gap-1">
                    <a href="<?php echo url('users/edit'); ?>" class="btn btn-ghost btn-sm">Edit</a>
                    <button class="btn btn-ghost btn-sm" style="color:var(--color-absent)" onclick="APP.confirm('Delete this user?', function(){})">Delete</button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>

          <div class="px-4 border-t" style="border-color:var(--color-border)">
            <?php include __DIR__ . '/../partials/pagination.php'; ?>
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
