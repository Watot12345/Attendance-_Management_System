<?php
$page_title = 'My Assigned Classes & Student Rosters';
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
            <span class="badge badge-present">Teacher Portal</span>
            <span class="text-xs text-slate-500">Academic Year 2025–2026</span>
          </div>
          <h1 class="text-2xl font-bold text-slate-800">My Assigned Classes &amp; Rosters</h1>
          <p class="text-sm text-slate-500">Manage your course sections, view interactive student rosters, and launch live attendance sessions.</p>
        </div>

        <div class="flex items-center gap-3">
          <a href="<?php echo url('teacher/import-roster'); ?>" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium shadow-sm transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
            <span>Import Class Roster</span>
          </a>
          <a href="<?php echo url('teacher/live-session'); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-md hover:shadow-lg transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
            <span>Start Attendance</span>
          </a>
        </div>
      </div>

      <!-- Quick KPI Stats Bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Assigned Classes</div>
          <div class="text-2xl font-bold text-slate-800 mt-1">4 Courses</div>
          <div class="text-[11px] text-slate-500 mt-0.5">3 Academic Sections</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-blue-600 uppercase tracking-wider">Total Enrolled</div>
          <div class="text-2xl font-bold text-blue-700 mt-1">148 Students</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Official Roster Count</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Avg. Attendance</div>
          <div class="text-2xl font-bold text-emerald-600 mt-1">95.6%</div>
          <div class="text-[11px] text-slate-500 mt-0.5">Campus Benchmark: 85%</div>
        </div>
        <div class="bg-white rounded-xl p-4 border border-slate-100 shadow-sm text-center">
          <div class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Sessions Held</div>
          <div class="text-2xl font-bold text-slate-800 mt-1">56 Sessions</div>
          <div class="text-[11px] text-slate-500 mt-0.5">14 Per Course Avg</div>
        </div>
      </div>

      <!-- Course / Year / Section Filter Bar -->
      <div class="bg-white rounded-xl p-4 border border-slate-200 shadow-sm mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
          <!-- Course Program -->
          <div>
            <label for="filter-program" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Program / Course</label>
            <select id="filter-program" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Programs (BSIT, BSCS)</option>
              <option value="BSIT">BSIT — Information Technology</option>
              <option value="BSCS">BSCS — Computer Science</option>
            </select>
          </div>

          <!-- Year Level -->
          <div>
            <label for="filter-year" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Year Level</label>
            <select id="filter-year" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Year Levels</option>
              <option value="3">3rd Year</option>
              <option value="4">4th Year</option>
            </select>
          </div>

          <!-- Section -->
          <div>
            <label for="filter-section" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Section</label>
            <select id="filter-section" class="w-full px-3 py-2 rounded-lg border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterClasses()">
              <option value="all">All Sections</option>
              <option value="BSIT 3-A">BSIT 3-A</option>
              <option value="BSIT 3-B">BSIT 3-B</option>
              <option value="BSIT 3-C">BSIT 3-C</option>
              <option value="BSCS 4-A">BSCS 4-A</option>
            </select>
          </div>

          <!-- Search keyword -->
          <div>
            <label for="search-class" class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Search Subject / Code</label>
            <div class="relative">
              <input type="text" id="search-class" placeholder="e.g. IT301 or Web Dev" class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-sm bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="filterClasses()">
              <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
          </div>
        </div>
      </div>

      <!-- Classes & Rosters Cards Grid -->
      <div id="classes-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6 mb-6">
        
        <!-- Class Card 1 -->
        <div class="class-card bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-400 transition flex flex-col overflow-hidden" data-program="BSIT" data-year="3" data-section="BSIT 3-A" data-title="Web Development 2 IT301">
          <div class="p-6 flex-1">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-blue-100 text-blue-800">BSIT 3-A</span>
                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600">3rd Year</span>
              </div>
              <span class="badge badge-present text-xs">● Session Live</span>
            </div>
            
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-1">IT301 — Web Development 2</h3>
            <p class="text-xs text-slate-500 mb-4 flex items-center gap-2">
              <span>🗓️ Tue / Thu • 08:00 AM – 10:00 AM</span>
              <span>•</span>
              <span>📍 Lab 304</span>
            </p>

            <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100 text-center mb-4">
              <div>
                <div class="text-xs text-slate-400 font-medium">Enrolled</div>
                <div class="text-lg font-bold text-slate-800">42</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 font-medium">Sessions</div>
                <div class="text-lg font-bold text-slate-800">14</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 font-medium">Avg Rate</div>
                <div class="text-lg font-bold text-emerald-600">96.2%</div>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <button type="button" class="px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5" onclick="openRosterModal('BSIT 3-A', 'IT301 — Web Development 2', '3rd Year', 'Tue / Thu • 08:00 AM – 10:00 AM', 'Lab 304', 42, '96.2%')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>View Student Roster</span>
              </button>
              <a href="<?php echo url('teacher/attendance-history?class_id=1'); ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition" title="View Past Attendance Sessions">
                History
              </a>
            </div>
            <a href="<?php echo url('teacher/live-session?class_id=1'); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
              <span>Start QR</span>
            </a>
          </div>
        </div>

        <!-- Class Card 2 -->
        <div class="class-card bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-400 transition flex flex-col overflow-hidden" data-program="BSIT" data-year="3" data-section="BSIT 3-B" data-title="Database Systems 2 IT302">
          <div class="p-6 flex-1">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-purple-100 text-purple-800">BSIT 3-B</span>
                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600">3rd Year</span>
              </div>
              <span class="badge badge-pending text-xs">Ready</span>
            </div>
            
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-1">IT302 — Database Systems 2</h3>
            <p class="text-xs text-slate-500 mb-4 flex items-center gap-2">
              <span>🗓️ Tue / Thu • 10:30 AM – 12:30 PM</span>
              <span>•</span>
              <span>📍 Room 402</span>
            </p>

            <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100 text-center mb-4">
              <div>
                <div class="text-xs text-slate-400 font-medium">Enrolled</div>
                <div class="text-lg font-bold text-slate-800">38</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 font-medium">Sessions</div>
                <div class="text-lg font-bold text-slate-800">14</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 font-medium">Avg Rate</div>
                <div class="text-lg font-bold text-emerald-600">93.5%</div>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <button type="button" class="px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5" onclick="openRosterModal('BSIT 3-B', 'IT302 — Database Systems 2', '3rd Year', 'Tue / Thu • 10:30 AM – 12:30 PM', 'Room 402', 38, '93.5%')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>View Student Roster</span>
              </button>
              <a href="<?php echo url('teacher/attendance-history?class_id=2'); ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition" title="View Past Attendance Sessions">
                History
              </a>
            </div>
            <a href="<?php echo url('teacher/live-session?class_id=2'); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
              <span>Start QR</span>
            </a>
          </div>
        </div>

        <!-- Class Card 3 -->
        <div class="class-card bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-400 transition flex flex-col overflow-hidden" data-program="BSIT" data-year="3" data-section="BSIT 3-C" data-title="Systems Integration Architecture IT303">
          <div class="p-6 flex-1">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-indigo-100 text-indigo-800">BSIT 3-C</span>
                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600">3rd Year</span>
              </div>
              <span class="badge badge-pending text-xs">Ready</span>
            </div>
            
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-1">IT303 — Systems Integration</h3>
            <p class="text-xs text-slate-500 mb-4 flex items-center gap-2">
              <span>🗓️ Tue / Thu • 01:30 PM – 03:30 PM</span>
              <span>•</span>
              <span>📍 Lab 301</span>
            </p>

            <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100 text-center mb-4">
              <div>
                <div class="text-xs text-slate-400 font-medium">Enrolled</div>
                <div class="text-lg font-bold text-slate-800">36</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 font-medium">Sessions</div>
                <div class="text-lg font-bold text-slate-800">14</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 font-medium">Avg Rate</div>
                <div class="text-lg font-bold text-emerald-600">95.0%</div>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <button type="button" class="px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5" onclick="openRosterModal('BSIT 3-C', 'IT303 — Systems Integration', '3rd Year', 'Tue / Thu • 01:30 PM – 03:30 PM', 'Lab 301', 36, '95.0%')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>View Student Roster</span>
              </button>
              <a href="<?php echo url('teacher/attendance-history?class_id=3'); ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition" title="View Past Attendance Sessions">
                History
              </a>
            </div>
            <a href="<?php echo url('teacher/live-session?class_id=3'); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
              <span>Start QR</span>
            </a>
          </div>
        </div>

        <!-- Class Card 4 -->
        <div class="class-card bg-white rounded-2xl border border-slate-200 shadow-sm hover:shadow-md hover:border-blue-400 transition flex flex-col overflow-hidden" data-program="BSCS" data-year="4" data-section="BSCS 4-A" data-title="Software Engineering CS401">
          <div class="p-6 flex-1">
            <div class="flex items-center justify-between mb-3">
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 rounded-md text-xs font-bold bg-emerald-100 text-emerald-800">BSCS 4-A</span>
                <span class="px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600">4th Year</span>
              </div>
              <span class="badge badge-pending text-xs">Ready</span>
            </div>
            
            <h3 class="font-bold text-slate-800 text-lg leading-tight mb-1">CS401 — Software Engineering</h3>
            <p class="text-xs text-slate-500 mb-4 flex items-center gap-2">
              <span>🗓️ Mon / Wed • 09:00 AM – 11:00 AM</span>
              <span>•</span>
              <span>📍 Room 302</span>
            </p>

            <div class="grid grid-cols-3 gap-2 p-3 bg-slate-50 rounded-xl border border-slate-100 text-center mb-4">
              <div>
                <div class="text-xs text-slate-400 font-medium">Enrolled</div>
                <div class="text-lg font-bold text-slate-800">32</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 font-medium">Sessions</div>
                <div class="text-lg font-bold text-slate-800">14</div>
              </div>
              <div>
                <div class="text-xs text-slate-400 font-medium">Avg Rate</div>
                <div class="text-lg font-bold text-emerald-600">97.8%</div>
              </div>
            </div>
          </div>

          <div class="px-6 py-4 bg-slate-50/80 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <button type="button" class="px-3.5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5" onclick="openRosterModal('BSCS 4-A', 'CS401 — Software Engineering', '4th Year', 'Mon / Wed • 09:00 AM – 11:00 AM', 'Room 302', 32, '97.8%')">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <span>View Student Roster</span>
              </button>
              <a href="<?php echo url('teacher/attendance-history?class_id=4'); ?>" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-medium transition" title="View Past Attendance Sessions">
                History
              </a>
            </div>
            <a href="<?php echo url('teacher/live-session?class_id=4'); ?>" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
              <span>Start QR</span>
            </a>
          </div>
        </div>

      </div>
    </main>
  </div>
</div>

<!-- ══════════════════════════════════════════════════════════════
     MODAL: CLASS STUDENT ROSTER WITH PAGINATION & SEARCH
══════════════════════════════════════════════════════════════ -->
<div id="roster-modal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden flex items-center justify-center p-4">
  <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-5xl max-h-[92vh] flex flex-col overflow-hidden animate-in fade-in zoom-in-95 duration-150">
    <!-- Modal Header -->
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-50 shrink-0">
      <div>
        <div class="flex items-center gap-2 mb-1">
          <span id="modal-section-badge" class="px-2.5 py-0.5 rounded-md text-xs font-bold bg-blue-100 text-blue-800">BSIT 3-A</span>
          <span id="modal-year-badge" class="text-xs text-slate-500 font-medium">3rd Year</span>
          <span class="text-slate-300">•</span>
          <span id="modal-schedule-text" class="text-xs text-slate-500">Tue/Thu • 08:00 AM – 10:00 AM (Lab 304)</span>
        </div>
        <h2 id="modal-course-title" class="text-lg font-bold text-slate-800">IT301 — Web Development 2</h2>
      </div>
      <button type="button" onclick="closeRosterModal()" class="w-8 h-8 rounded-full bg-slate-200 hover:bg-slate-300 text-slate-600 flex items-center justify-center font-bold text-sm transition">
        
      </button>
    </div>

    <!-- Modal Controls & Search Bar -->
    <div class="p-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3 bg-white shrink-0">
      <div class="relative flex-1 min-w-[220px]">
        <input type="text" id="modal-search-input" placeholder="Search enrolled student by ID or name..." class="w-full pl-9 pr-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" oninput="filterModalStudents()">
        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      </div>

      <div class="flex items-center gap-2">
        <select id="modal-status-filter" class="px-3 py-2 rounded-lg border border-slate-200 text-xs bg-slate-50 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500" onchange="filterModalStudents()">
          <option value="all">All Enrolled</option>
          <option value="good">Good Standing (&gt;85%)</option>
          <option value="at-risk">At Risk (&lt;75%)</option>
        </select>
        <button type="button" onclick="APP.toast('Exported official class roster to Excel (.xlsx)', 'success')" class="px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold transition flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
          <span>Export CSV</span>
        </button>
      </div>
    </div>

    <!-- Modal Table Content -->
    <div class="flex-1 overflow-y-auto">
      <table class="w-full text-left text-xs">
        <thead class="bg-slate-50 text-slate-600 uppercase font-semibold border-b border-slate-200 sticky top-0 z-10">
          <tr>
            <th class="py-2.5 px-4">Student ID</th>
            <th class="py-2.5 px-4">Student Name</th>
            <th class="py-2.5 px-4 text-center">Sessions</th>
            <th class="py-2.5 px-4 text-center">Present</th>
            <th class="py-2.5 px-4 text-center">Late</th>
            <th class="py-2.5 px-4 text-center">Absent</th>
            <th class="py-2.5 px-4 text-center">Excused</th>
            <th class="py-2.5 px-4">Attendance Rate</th>
            <th class="py-2.5 px-4">Status</th>
            <th class="py-2.5 px-4 text-right">Action</th>
          </tr>
        </thead>
        <tbody id="modal-roster-tbody" class="divide-y divide-slate-100 text-slate-700">
          <!-- Populated dynamically via JS -->
        </tbody>
      </table>
    </div>

    <!-- Modal Footer with Pagination Controls -->
    <div class="px-6 py-3.5 border-t border-slate-200 bg-slate-50 flex flex-col sm:flex-row items-center justify-between gap-3 shrink-0">
      <div id="modal-pagination-info" class="text-xs text-slate-500">
        Showing <strong class="text-slate-800">1 to 5</strong> of <strong class="text-slate-800">12</strong> students
      </div>

      <div class="flex items-center gap-1.5" id="modal-pagination-buttons">
        <!-- Pagination Buttons inserted dynamically -->
      </div>

      <div>
        <button type="button" onclick="closeRosterModal()" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-semibold transition">
          Close Roster
        </button>
      </div>
    </div>
  </div>
</div>

<script>
// Filter Course Offerings on Main Page
function filterClasses() {
  const program = document.getElementById('filter-program').value;
  const year = document.getElementById('filter-year').value;
  const section = document.getElementById('filter-section').value;
  const query = document.getElementById('search-class').value.toLowerCase().trim();

  const cards = document.querySelectorAll('.class-card');
  cards.forEach(card => {
    const cardProg = card.getAttribute('data-program');
    const cardYear = card.getAttribute('data-year');
    const cardSec = card.getAttribute('data-section');
    const cardTitle = card.getAttribute('data-title').toLowerCase();

    const matchProg = (program === 'all' || cardProg === program);
    const matchYear = (year === 'all' || cardYear === year);
    const matchSec = (section === 'all' || cardSec === section);
    const matchQuery = (!query || cardTitle.includes(query));

    if (matchProg && matchYear && matchSec && matchQuery) {
      card.style.display = 'flex';
    } else {
      card.style.display = 'none';
    }
  });
}

// Mock student roster data
const mockStudents = [
  { id: '2026-00123', name: 'Juan Dela Cruz', email: 'juan.delacruz@bestlink.edu.ph', sessions: 14, present: 14, late: 0, absent: 0, excused: 0, rate: 100, status: 'Active' },
  { id: '2026-00124', name: 'Maria Santos', email: 'maria.santos@bestlink.edu.ph', sessions: 14, present: 13, late: 1, absent: 0, excused: 0, rate: 96.4, status: 'Active' },
  { id: '2026-00125', name: 'Pedro Reyes', email: 'pedro.reyes@bestlink.edu.ph', sessions: 14, present: 11, late: 2, absent: 1, excused: 0, rate: 85.7, status: 'Active' },
  { id: '2026-00126', name: 'Ana Mendoza', email: 'ana.mendoza@bestlink.edu.ph', sessions: 14, present: 14, late: 0, absent: 0, excused: 0, rate: 100, status: 'Active' },
  { id: '2026-00127', name: 'Carlos Garcia', email: 'carlos.garcia@bestlink.edu.ph', sessions: 14, present: 12, late: 1, absent: 1, excused: 0, rate: 90.0, status: 'Active' },
  { id: '2026-00128', name: 'Elena Bautista', email: 'elena.bautista@bestlink.edu.ph', sessions: 14, present: 13, late: 0, absent: 1, excused: 1, rate: 92.8, status: 'Active' },
  { id: '2026-00129', name: 'Gabriel Fernandez', email: 'gabriel.f@bestlink.edu.ph', sessions: 14, present: 10, late: 1, absent: 3, excused: 0, rate: 71.4, status: 'At Risk' },
  { id: '2026-00130', name: 'Hannah Morales', email: 'hannah.m@bestlink.edu.ph', sessions: 14, present: 14, late: 0, absent: 0, excused: 0, rate: 100, status: 'Active' },
  { id: '2026-00131', name: 'Ivan Alcantara', email: 'ivan.a@bestlink.edu.ph', sessions: 14, present: 13, late: 1, absent: 0, excused: 0, rate: 96.4, status: 'Active' },
  { id: '2026-00132', name: 'Jasmine Cruz', email: 'jasmine.c@bestlink.edu.ph', sessions: 14, present: 12, late: 2, absent: 0, excused: 0, rate: 92.8, status: 'Active' },
  { id: '2026-00133', name: 'Kevin Ocampo', email: 'kevin.o@bestlink.edu.ph', sessions: 14, present: 14, late: 0, absent: 0, excused: 0, rate: 100, status: 'Active' },
  { id: '2026-00134', name: 'Liza Manalo', email: 'liza.m@bestlink.edu.ph', sessions: 14, present: 9, late: 2, absent: 3, excused: 0, rate: 64.2, status: 'At Risk' }
];

let currentPage = 1;
const pageSize = 5;
let filteredList = [...mockStudents];

function openRosterModal(section, course, year, schedule, room, enrolledCount, avgRate) {
  document.getElementById('modal-section-badge').textContent = section;
  document.getElementById('modal-year-badge').textContent = year;
  document.getElementById('modal-schedule-text').textContent = schedule + ' (' + room + ')';
  document.getElementById('modal-course-title').textContent = course;

  document.getElementById('modal-search-input').value = '';
  document.getElementById('modal-status-filter').value = 'all';

  filteredList = [...mockStudents];
  currentPage = 1;
  renderModalTable();

  document.getElementById('roster-modal').classList.remove('hidden');
}

function closeRosterModal() {
  document.getElementById('roster-modal').classList.add('hidden');
}

function filterModalStudents() {
  const query = document.getElementById('modal-search-input').value.toLowerCase().trim();
  const statusFilter = document.getElementById('modal-status-filter').value;

  filteredList = mockStudents.filter(s => {
    const matchQuery = (!query || s.id.toLowerCase().includes(query) || s.name.toLowerCase().includes(query) || s.email.toLowerCase().includes(query));
    let matchStatus = true;
    if (statusFilter === 'good') matchStatus = (s.rate >= 85);
    if (statusFilter === 'at-risk') matchStatus = (s.rate < 75);
    return matchQuery && matchStatus;
  });

  currentPage = 1;
  renderModalTable();
}

function setModalPage(page) {
  currentPage = page;
  renderModalTable();
}

function renderModalTable() {
  const tbody = document.getElementById('modal-roster-tbody');
  tbody.innerHTML = '';

  const total = filteredList.length;
  const startIdx = (currentPage - 1) * pageSize;
  const endIdx = Math.min(startIdx + pageSize, total);
  const pageItems = filteredList.slice(startIdx, endIdx);

  if (pageItems.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" class="text-center py-8 text-slate-400">No student records found matching your query.</td></tr>`;
  } else {
    pageItems.forEach(s => {
      const isAtRisk = s.rate < 75;
      const statusBadge = isAtRisk
        ? `<span class="badge badge-absent font-bold">At Risk</span>`
        : `<span class="badge badge-present font-bold">Good Standing</span>`;

      const rateColor = s.rate >= 90 ? 'text-emerald-600' : (s.rate >= 75 ? 'text-blue-600' : 'text-rose-600');
      const barColor = s.rate >= 90 ? 'bg-emerald-500' : (s.rate >= 75 ? 'bg-blue-500' : 'bg-rose-500');

      const tr = document.createElement('tr');
      tr.className = 'hover:bg-slate-50/80 transition';
      tr.innerHTML = `
        <td class="py-2.5 px-4 font-mono font-bold text-blue-700">${s.id}</td>
        <td class="py-2.5 px-4">
          <div class="font-bold text-slate-800">${s.name}</div>
          <div class="text-[10px] text-slate-400">${s.email}</div>
        </td>
        <td class="py-2.5 px-4 text-center font-bold text-slate-700">${s.sessions}</td>
        <td class="py-2.5 px-4 text-center font-bold text-emerald-600">${s.present}</td>
        <td class="py-2.5 px-4 text-center font-bold text-amber-600">${s.late}</td>
        <td class="py-2.5 px-4 text-center font-bold text-rose-600">${s.absent}</td>
        <td class="py-2.5 px-4 text-center font-bold text-blue-600">${s.excused}</td>
        <td class="py-2.5 px-4">
          <div class="flex items-center gap-2">
            <div class="w-16 bg-slate-100 rounded-full h-2 overflow-hidden">
              <div class="${barColor} h-2 rounded-full" style="width: ${s.rate}%"></div>
            </div>
            <span class="font-bold text-xs ${rateColor}">${s.rate}%</span>
          </div>
        </td>
        <td class="py-2.5 px-4">${statusBadge}</td>
        <td class="py-2.5 px-4 text-right">
          <a href="<?php echo url('teacher/attendance-history'); ?>?student=${s.id}" class="text-xs text-blue-600 hover:underline font-semibold">History</a>
        </td>
      `;
      tbody.appendChild(tr);
    });
  }

  // Update pagination info
  document.getElementById('modal-pagination-info').innerHTML = `Showing <strong class="text-slate-800">${total > 0 ? startIdx + 1 : 0} to ${endIdx}</strong> of <strong class="text-slate-800">${total}</strong> students`;

  // Update pagination buttons
  const totalPages = Math.ceil(total / pageSize) || 1;
  const btnContainer = document.getElementById('modal-pagination-buttons');
  btnContainer.innerHTML = '';

  // Prev
  const prevBtn = document.createElement('button');
  prevBtn.type = 'button';
  prevBtn.disabled = (currentPage <= 1);
  prevBtn.className = `px-2.5 py-1 rounded text-xs font-semibold ${currentPage <= 1 ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'}`;
  prevBtn.textContent = '« Prev';
  prevBtn.onclick = () => setModalPage(currentPage - 1);
  btnContainer.appendChild(prevBtn);

  // Page Numbers
  for (let i = 1; i <= totalPages; i++) {
    const pageBtn = document.createElement('button');
    pageBtn.type = 'button';
    pageBtn.className = `w-7 h-7 rounded text-xs font-bold transition ${i === currentPage ? 'bg-blue-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'}`;
    pageBtn.textContent = i;
    pageBtn.onclick = () => setModalPage(i);
    btnContainer.appendChild(pageBtn);
  }

  // Next
  const nextBtn = document.createElement('button');
  nextBtn.type = 'button';
  nextBtn.disabled = (currentPage >= totalPages);
  nextBtn.className = `px-2.5 py-1 rounded text-xs font-semibold ${currentPage >= totalPages ? 'opacity-40 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'}`;
  nextBtn.textContent = 'Next »';
  nextBtn.onclick = () => setModalPage(currentPage + 1);
  btnContainer.appendChild(nextBtn);
}

// Auto open modal if ?class_id or ?roster is in URL
window.addEventListener('DOMContentLoaded', () => {
  const urlParams = new URLSearchParams(window.location.search);
  const classId = urlParams.get('class_id') || urlParams.get('roster');
  if (classId === '1') {
    openRosterModal('BSIT 3-A', 'IT301 — Web Development 2', '3rd Year', 'Tue / Thu • 08:00 AM – 10:00 AM', 'Lab 304', 42, '96.2%');
  } else if (classId === '2') {
    openRosterModal('BSIT 3-B', 'IT302 — Database Systems 2', '3rd Year', 'Tue / Thu • 10:30 AM – 12:30 PM', 'Room 402', 38, '93.5%');
  } else if (classId === '3') {
    openRosterModal('BSIT 3-C', 'IT303 — Systems Integration', '3rd Year', 'Tue / Thu • 01:30 PM – 03:30 PM', 'Lab 301', 36, '95.0%');
  } else if (classId === '4') {
    openRosterModal('BSCS 4-A', 'CS401 — Software Engineering', '4th Year', 'Mon / Wed • 09:00 AM – 11:00 AM', 'Room 302', 32, '97.8%');
  }
});
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
