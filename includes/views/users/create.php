<?php 
$page_title = 'Create User Account'; 
require_once dirname(__DIR__, 2) . '/core/Router.php';
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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200/60">New Account Provisioning</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Create User Account</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Provision authentication credentials and role permissions for a student, faculty teacher, or administrator.
          </p>
        </div>
      </div>

      <!-- Centered Card Form -->
      <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-6 sm:p-8">
          <form id="create-user-form" onsubmit="event.preventDefault(); handleCreateUser(this);">
            
            <!-- Role Selection -->
            <div class="mb-5">
              <label for="user-role" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Assign Role &amp; Permissions</label>
              <select id="user-role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm font-semibold bg-slate-50/60 hover:bg-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition cursor-pointer" onchange="toggleRoleFields(this.value)">
                <option value="student" selected>Student (Learner Portal Access)</option>
                <option value="teacher">Faculty Teacher (Attendance Marking &amp; Rosters)</option>
                <option value="admin">Institutional Administrator (Full Oversight)</option>
                <option value="parent">Parent / Guardian (Alert Notifications)</option>
              </select>
            </div>

            <!-- Basic Information -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label for="user-fname" class="block text-xs font-bold text-slate-700 mb-1.5">First Name</label>
                <input type="text" id="user-fname" required placeholder="e.g. Juan" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
              </div>
              <div>
                <label for="user-lname" class="block text-xs font-bold text-slate-700 mb-1.5">Last Name</label>
                <input type="text" id="user-lname" required placeholder="e.g. Dela Cruz" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
              </div>
            </div>

            <div class="mb-4">
              <label for="user-email" class="block text-xs font-bold text-slate-700 mb-1.5">Institutional Email Address</label>
              <input type="email" id="user-email" required placeholder="e.g. jdelacruz@bcp.edu.ph" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
              <div>
                <label for="user-password" class="block text-xs font-bold text-slate-700 mb-1.5">Initial Password</label>
                <input type="password" id="user-password" required placeholder="Minimum 8 characters" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
                <p class="text-[11px] text-slate-400 mt-1">Default temporary password for first-time sign-in.</p>
              </div>
              <div>
                <label for="user-phone" class="block text-xs font-bold text-slate-700 mb-1.5">Contact Phone Number (Optional)</label>
                <input type="tel" id="user-phone" placeholder="e.g. +63 912 345 6789" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
              </div>
            </div>

            <!-- Student-Specific Fields -->
            <div id="student-fields" class="pt-4 border-t border-slate-100 space-y-4">
              <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Student Academic Details</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="student-code" class="block text-xs font-bold text-slate-700 mb-1.5">Student Number / LRN</label>
                  <input type="text" id="student-code" placeholder="e.g. 2026-00123" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
                </div>
                <div>
                  <label for="student-section" class="block text-xs font-bold text-slate-700 mb-1.5">Assigned Section</label>
                  <input type="text" id="student-section" placeholder="e.g. 31001" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
                </div>
              </div>
            </div>

            <!-- Teacher-Specific Fields -->
            <div id="teacher-fields" class="pt-4 border-t border-slate-100 space-y-4 hidden">
              <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Faculty Details</h3>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label for="teacher-empid" class="block text-xs font-bold text-slate-700 mb-1.5">Faculty Employee ID</label>
                  <input type="text" id="teacher-empid" placeholder="e.g. EMP-101" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
                </div>
                <div>
                  <label for="teacher-dept" class="block text-xs font-bold text-slate-700 mb-1.5">Academic Department</label>
                  <input type="text" id="teacher-dept" placeholder="e.g. College of Computer Studies" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-xs sm:text-sm bg-slate-50/50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-[#1e3b8a]/20 focus:border-[#1e3b8a] transition placeholder:text-slate-400">
                </div>
              </div>
            </div>

            <!-- Form Actions -->
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 mt-6">
              <a href="<?php echo url('users'); ?>" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition">
                Cancel
              </a>
              <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-[#1e3b8a] hover:bg-[#162c69] shadow-md shadow-[#1e3b8a]/20 transition cursor-pointer">
                <span>Create User Account</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
function toggleRoleFields(role) {
  const sFields = document.getElementById('student-fields');
  const tFields = document.getElementById('teacher-fields');
  if (sFields) sFields.classList.toggle('hidden', role !== 'student');
  if (tFields) tFields.classList.toggle('hidden', role !== 'teacher');
}

function handleCreateUser(form) {
  if (window.APP && APP.showToast) {
    APP.showToast('User account provisioned successfully', 'success');
    setTimeout(() => {
      window.location.href = '<?php echo url('users'); ?>';
    }, 800);
  } else {
    alert('User account provisioned successfully');
    window.location.href = '<?php echo url('users'); ?>';
  }
}
</script>

<?php include __DIR__ . '/../partials/footer.php'; ?>
