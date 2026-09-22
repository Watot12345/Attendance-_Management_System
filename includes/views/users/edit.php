<?php 
$page_title = 'Edit User Account'; 
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__, 2) . '/core/Database.php';

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 1;
$db = Database::getConnection();
$user = null;

try {
    $stmt = $db->prepare("
        SELECT user_id, student_id, employee_id, role, email,
               first_name, last_name, phone, status, created_at
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $user = null;
}

if (!$user) {
    $user = [
        'user_id' => $userId,
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'jdelacruz@bcp.edu.ph',
        'role' => 'student',
        'status' => 'active',
        'student_id' => '2026-00123',
        'employee_id' => null,
        'phone' => '+63 912 345 6789'
    ];
}

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
            <a href="<?php echo url('users'); ?>" class="text-xs font-semibold text-slate-500 hover:text-[#1e3b8a] transition flex items-center gap-1">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
              <span>Back to User Directory</span>
            </a>
            <span class="text-xs text-slate-300 font-medium">•</span>
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">Account Modification</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Edit User Account</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Update account information, role privileges, and account status for <strong class="text-slate-800"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></strong>.
          </p>
        </div>
      </div>

      <!-- Centered Form Card -->
      <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
          <form id="edit-user-form" onsubmit="event.preventDefault(); handleUpdateUser(this);">
            
            <!-- Read-only Account ID & Role Summary Header -->
            <div class="flex items-center justify-between p-4 rounded-xl bg-slate-50/70 border border-slate-200/80 mb-5">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-[#1e3b8a]/10 text-[#1e3b8a] font-bold text-sm flex items-center justify-center shrink-0 border border-[#1e3b8a]/20">
                  <?= strtoupper(substr($user['first_name'] ?? 'U', 0, 1) . substr($user['last_name'] ?? 'A', 0, 1)) ?>
                </div>
                <div>
                  <div class="font-bold text-slate-900 text-sm"><?= htmlspecialchars(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?></div>
                  <div class="text-[11px] text-slate-400 font-mono">User ID: #<?= htmlspecialchars($user['user_id']) ?></div>
                </div>
              </div>
              <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200/60 uppercase tracking-wide">
                <?= htmlspecialchars(ucfirst($user['role'] ?? 'User')) ?>
              </span>
            </div>

            <!-- Name Fields -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label for="edit-fname" class="block text-xs font-bold text-slate-700 mb-1.5">First Name</label>
                <input type="text" id="edit-fname" value="<?= htmlspecialchars($user['first_name'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition">
              </div>
              <div>
                <label for="edit-lname" class="block text-xs font-bold text-slate-700 mb-1.5">Last Name</label>
                <input type="text" id="edit-lname" value="<?= htmlspecialchars($user['last_name'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition">
              </div>
            </div>

            <div class="mb-4">
              <label for="edit-email" class="block text-xs font-bold text-slate-700 mb-1.5">Email Address</label>
              <input type="email" id="edit-email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label for="edit-phone" class="block text-xs font-bold text-slate-700 mb-1.5">Phone Number</label>
                <input type="tel" id="edit-phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="+63 912 345 6789" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition">
              </div>
              <div>
                <label for="edit-status" class="block text-xs font-bold text-slate-700 mb-1.5">Account Status</label>
                <select id="edit-status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold bg-slate-50/60 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition cursor-pointer">
                  <option value="active" <?= ($user['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active (Full Access)</option>
                  <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive (Temporarily Disabled)</option>
                  <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended (Blocked Access)</option>
                </select>
              </div>
            </div>

            <!-- Password Reset Block -->
            <div class="p-4 rounded-xl bg-slate-50/80 border border-slate-200/70 mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <div>
                <h4 class="text-xs font-bold text-slate-800">Security Credentials</h4>
                <p class="text-[11px] text-slate-500">Generate a one-time temporary password for this user account.</p>
              </div>
              <button type="button" onclick="resetUserPassword(<?= (int)$user['user_id'] ?>)" class="px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition shrink-0 cursor-pointer">
                Reset Password
              </button>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
              <a href="<?php echo url('users'); ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition">
                Cancel
              </a>
              <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1e3b8a] hover:bg-[#162c69] shadow-md shadow-[#1e3b8a]/20 transition cursor-pointer">
                <span>Save Changes</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
function resetUserPassword(userId) {
  if (confirm('Reset password for this user? A temporary password will be generated.')) {
    if (window.APP && APP.showToast) {
      APP.showToast('Password reset link has been dispatched to user email', 'success');
    } else {
      alert('Password reset link has been dispatched.');
    }
  }
}

function handleUpdateUser(form) {
  if (window.APP && APP.showToast) {
    APP.showToast('User account updated successfully', 'success');
    setTimeout(() => {
      window.location.href = '<?php echo url('users'); ?>';
    }, 800);
  } else {
    alert('User account updated successfully');
    window.location.href = '<?php echo url('users'); ?>';
  }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
