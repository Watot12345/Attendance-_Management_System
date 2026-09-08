<?php
$page_title = 'Courses & Sections Management';
require_once dirname(__DIR__, 2) . '/core/Router.php';
require_once dirname(__DIR__) . '/partials/header.php';
?>

<div class="app-layout">
  <?php require_once dirname(__DIR__) . '/partials/sidebar.php'; ?>

  <div class="main-content">
    <?php require_once dirname(__DIR__) . '/partials/navbar.php'; ?>

    <main class="page-body">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
          <div class="flex items-center gap-2 mb-1">
            <span class="badge badge-present">Administrator Portal</span>
            <span class="text-xs text-slate-500">Academic Structure &amp; Sections</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">Academic Programs, Courses &amp; Sections</h1>
          <p class="text-sm text-slate-500">Manage degree programs, course offerings, and official student sections.</p>
        </div>

        <div class="flex items-center gap-3">
          <button type="button" onclick="alert('Add Section');" class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow transition">
            + Create New Section
          </button>
        </div>
      </div>

      <!-- Sections Grid -->
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Section 1 -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:border-blue-400 transition">
          <div class="flex items-center justify-between mb-2">
            <span class="px-2.5 py-1 rounded bg-blue-100 text-blue-800 font-bold text-xs">BSIT 3-A</span>
            <span class="badge badge-present text-xs">Active</span>
          </div>
          <h3 class="font-bold text-slate-800 text-base mb-1">BS in Information Technology</h3>
          <p class="text-xs text-slate-500 mb-3">3rd Year • Day Shift • 42 Enrolled Students</p>

          <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs space-y-1 mb-4">
            <div class="font-semibold text-slate-700">Assigned Courses:</div>
            <div class="text-slate-600">• IT301 Web Dev 2 (Prof. Ramirez)</div>
            <div class="text-slate-600">• IT302 Database 2 (Prof. Ramirez)</div>
            <div class="text-slate-600">• IT303 Systems Integration (Prof. Santos)</div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-emerald-600 font-bold">96.2% Avg Attendance</span>
            <button type="button" class="text-xs text-blue-600 hover:underline font-medium" onclick="alert('Manage BSIT 3-A');">Edit Section</button>
          </div>
        </div>

        <!-- Section 2 -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:border-blue-400 transition">
          <div class="flex items-center justify-between mb-2">
            <span class="px-2.5 py-1 rounded bg-purple-100 text-purple-800 font-bold text-xs">BSIT 3-B</span>
            <span class="badge badge-present text-xs">Active</span>
          </div>
          <h3 class="font-bold text-slate-800 text-base mb-1">BS in Information Technology</h3>
          <p class="text-xs text-slate-500 mb-3">3rd Year • Day Shift • 38 Enrolled Students</p>

          <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs space-y-1 mb-4">
            <div class="font-semibold text-slate-700">Assigned Courses:</div>
            <div class="text-slate-600">• IT301 Web Dev 2 (Prof. Ramirez)</div>
            <div class="text-slate-600">• IT302 Database 2 (Prof. Ramirez)</div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-emerald-600 font-bold">93.5% Avg Attendance</span>
            <button type="button" class="text-xs text-blue-600 hover:underline font-medium" onclick="alert('Manage BSIT 3-B');">Edit Section</button>
          </div>
        </div>

        <!-- Section 3 -->
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-5 hover:border-blue-400 transition">
          <div class="flex items-center justify-between mb-2">
            <span class="px-2.5 py-1 rounded bg-emerald-100 text-emerald-800 font-bold text-xs">BSCS 4-A</span>
            <span class="badge badge-present text-xs">Active</span>
          </div>
          <h3 class="font-bold text-slate-800 text-base mb-1">BS in Computer Science</h3>
          <p class="text-xs text-slate-500 mb-3">4th Year • Day Shift • 32 Enrolled Students</p>

          <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 text-xs space-y-1 mb-4">
            <div class="font-semibold text-slate-700">Assigned Courses:</div>
            <div class="text-slate-600">• CS401 Software Eng (Prof. Santos)</div>
            <div class="text-slate-600">• CS402 Capstone Project 2</div>
          </div>

          <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
            <span class="text-xs text-emerald-600 font-bold">97.8% Avg Attendance</span>
            <button type="button" class="text-xs text-blue-600 hover:underline font-medium" onclick="alert('Manage BSCS 4-A');">Edit Section</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
