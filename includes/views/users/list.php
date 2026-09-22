<?php 
$page_title = 'User Management'; 
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$db = Database::getConnection();
$users = [];
try {
    $stmt = $db->query("
        SELECT user_id, student_id, employee_id, role, email,
               first_name, last_name, phone, status, last_login_at, created_at
        FROM users
        ORDER BY user_id DESC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}

$totalUsers = count($users);
$adminCount = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'admin'));
$teacherCount = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'teacher'));
$studentCount = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'student'));
$parentCount = count(array_filter($users, fn($u) => ($u['role'] ?? '') === 'parent'));

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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">Admin Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Access &amp; Role Governance</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">User Management</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Institutional directory of administrators, faculty instructors, enrolled students, and registered guardians.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <a href="<?php echo url('users/create'); ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1e3b8a] hover:bg-[#162c69] shadow-md shadow-[#1e3b8a]/20 transition cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Create New User</span>
          </a>
        </div>
      </div>

      <!-- Quick Metrics Overview -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Accounts</div>
          <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= number_format($totalUsers) ?></div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-blue-600 uppercase tracking-wider">Administrators</div>
          <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= number_format($adminCount) ?></div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-sky-600 uppercase tracking-wider">Faculty Teachers</div>
          <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= number_format($teacherCount) ?></div>
        </div>
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs">
          <div class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Active Students</div>
          <div class="text-xl sm:text-2xl font-black text-slate-900 mt-1"><?= number_format($studentCount) ?></div>
        </div>
      </div>

      <!-- Filters & Search Bar -->
      <div class="bg-white rounded-2xl p-4 border border-slate-200/80 shadow-xs flex flex-col md:flex-row items-center justify-between gap-4 mb-6">
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <!-- Search input -->
          <div class="relative w-full sm:w-72">
            <input type="text" id="user-search" oninput="filterUserDirectory()" placeholder="Search name, email, or account ID..." class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] text-slate-800 transition">
            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          </div>

          <!-- Role Filter -->
          <select id="user-role-filter" onchange="filterUserDirectory()" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1e3b8a] font-semibold text-slate-700 transition">
            <option value="all">All Roles</option>
            <option value="admin">Administrators</option>
            <option value="teacher">Faculty / Teachers</option>
            <option value="student">Students</option>
            <option value="parent">Parents</option>
          </select>

          <!-- Status Filter -->
          <select id="user-status-filter" onchange="filterUserDirectory()" class="px-3 py-2 rounded-xl border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:border-[#1e3b8a] font-semibold text-slate-700 transition">
            <option value="all">All Statuses</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
            <option value="suspended">Suspended</option>
          </select>

          <button type="button" onclick="resetUserFilters()" class="px-3.5 py-2 rounded-xl border border-slate-200 text-xs bg-white hover:bg-slate-50 font-semibold text-slate-700 transition cursor-pointer">
            Reset
          </button>
        </div>

        <div class="text-xs text-slate-400 font-medium">
          Showing <strong id="visible-user-count" class="text-slate-800"><?= count($users) ?></strong> of <?= count($users) ?> Users
        </div>
      </div>

      <!-- User Directory Table -->
      <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs" id="usersTable">
            <thead class="bg-slate-50/90 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200">
              <tr>
                <th class="py-3.5 px-4">User Account</th>
                <th class="py-3.5 px-4">Identifier / ID</th>
                <th class="py-3.5 px-4">Assigned Role</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4">Created Date</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody id="users-table-tbody" class="divide-y divide-slate-100 text-slate-700">
              <?php if (empty($users)): ?>
                <tr>
                  <td colspan="6" class="py-12 text-center text-slate-400">
                    <p class="font-bold text-sm text-slate-700">No Users Found</p>
                    <p class="text-xs text-slate-400 mt-0.5">Click Create New User above to add your first account.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($users as $u): 
                  $fullName = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                  if (empty($fullName)) $fullName = 'User #' . $u['user_id'];
                  $initials = strtoupper(substr($u['first_name'] ?? 'U', 0, 1) . substr($u['last_name'] ?? 'A', 0, 1));
                  $role = strtolower($u['role'] ?? 'student');
                  $status = strtolower($u['status'] ?? 'active');
                  $accId = $u['student_id'] ?: ($u['employee_id'] ?: 'USR-' . $u['user_id']);
                  $created = !empty($u['created_at']) ? date('M j, Y', strtotime($u['created_at'])) : '—';
                  $searchText = strtolower($fullName . ' ' . ($u['email'] ?? '') . ' ' . $accId . ' ' . $role);
                ?>
                  <tr class="user-row hover:bg-slate-50/80 transition" data-role="<?= htmlspecialchars($role) ?>" data-status="<?= htmlspecialchars($status) ?>" data-search="<?= htmlspecialchars($searchText) ?>">
                    <!-- User Account -->
                    <td class="py-3.5 px-4">
                      <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-[#1e3b8a]/10 text-[#1e3b8a] font-bold text-xs flex items-center justify-center shrink-0 border border-[#1e3b8a]/20">
                          <?= htmlspecialchars($initials) ?>
                        </div>
                        <div>
                          <div class="font-bold text-slate-900"><?= htmlspecialchars($fullName) ?></div>
                          <div class="text-[11px] text-slate-400"><?= htmlspecialchars($u['email'] ?? 'No email') ?></div>
                        </div>
                      </div>
                    </td>

                    <!-- Identifier / ID -->
                    <td class="py-3.5 px-4">
                      <span class="font-mono font-semibold text-slate-700 text-[11px]"><?= htmlspecialchars($accId) ?></span>
                    </td>

                    <!-- Assigned Role -->
                    <td class="py-3.5 px-4">
                      <?php if ($role === 'admin'): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200/60">
                          Administrator
                        </span>
                      <?php elseif ($role === 'teacher'): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-sky-50 text-sky-700 border border-sky-200/60">
                          Faculty Teacher
                        </span>
                      <?php elseif ($role === 'parent'): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
                          Parent / Guardian
                        </span>
                      <?php else: ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">
                          Student
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Status -->
                    <td class="py-3.5 px-4">
                      <?php if ($status === 'active'): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/60 inline-flex items-center gap-1.5">
                          <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                          Active
                        </span>
                      <?php elseif ($status === 'inactive'): ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200/60 inline-flex items-center gap-1.5">
                          <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                          Inactive
                        </span>
                      <?php else: ?>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-200/60 inline-flex items-center gap-1.5">
                          <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                          Suspended
                        </span>
                      <?php endif; ?>
                    </td>

                    <!-- Created Date -->
                    <td class="py-3.5 px-4 text-slate-500">
                      <?= htmlspecialchars($created) ?>
                    </td>

                    <!-- Actions -->
                    <td class="py-3.5 px-4 text-right">
                      <div class="flex items-center justify-end gap-1.5">
                        <a href="<?php echo url('users/edit?id=' . $u['user_id']); ?>" class="px-2.5 py-1 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition">
                          Edit
                        </a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
function filterUserDirectory() {
  const query = (document.getElementById('user-search').value || '').toLowerCase().trim();
  const role = (document.getElementById('user-role-filter').value || 'all');
  const status = (document.getElementById('user-status-filter').value || 'all');
  const rows = document.querySelectorAll('.user-row');
  let visible = 0;

  rows.forEach(r => {
    const rRole = r.dataset.role;
    const rStatus = r.dataset.status;
    const rSearch = r.dataset.search;

    const matchesQuery = !query || rSearch.includes(query);
    const matchesRole = (role === 'all' || rRole === role);
    const matchesStatus = (status === 'all' || rStatus === status);

    if (matchesQuery && matchesRole && matchesStatus) {
      r.style.display = '';
      visible++;
    } else {
      r.style.display = 'none';
    }
  });

  const countEl = document.getElementById('visible-user-count');
  if (countEl) countEl.textContent = visible;
}

function resetUserFilters() {
  document.getElementById('user-search').value = '';
  document.getElementById('user-role-filter').value = 'all';
  document.getElementById('user-status-filter').value = 'all';
  filterUserDirectory();
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
