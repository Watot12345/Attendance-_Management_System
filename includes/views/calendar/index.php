<?php
$page_title = 'My Attendance Calendar';
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
            <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">Student Portal</span>
            <span class="text-xs text-slate-400 font-medium">•</span>
            <span class="text-xs text-slate-500 font-semibold">Monthly Attendance Calendar</span>
          </div>
          <h1 class="text-2xl lg:text-3xl font-black text-slate-900 tracking-tight">Attendance Calendar</h1>
          <p class="text-xs sm:text-sm text-slate-500 mt-1 max-w-2xl">
            Track your daily class attendance status (Present, Late/Tardy, Absent, Excused) across all enrolled semester courses.
          </p>
        </div>

        <div class="flex items-center gap-2.5">
          <button type="button" onclick="window.print()" class="px-4 py-2.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition flex items-center gap-2">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            <span>Print Attendance</span>
          </button>
        </div>
      </div>

      <!-- Quick Attendance Status Summary -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center font-bold shrink-0">
            
          </div>
          <div>
            <div class="text-lg font-black text-emerald-600">18 Days</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Present</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center font-bold shrink-0">
            ⏱
          </div>
          <div>
            <div class="text-lg font-black text-amber-600">1 Day</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Tardy / Late</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center font-bold shrink-0">
            
          </div>
          <div>
            <div class="text-lg font-black text-rose-600">1 Day</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Unexcused Absent</div>
          </div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-slate-200/80 shadow-xs flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center font-bold shrink-0">
            ✉
          </div>
          <div>
            <div class="text-lg font-black text-blue-600">1 Day</div>
            <div class="text-[11px] text-slate-400 font-semibold uppercase tracking-wider">Excused Slip</div>
          </div>
        </div>
      </div>

      <!-- Main Interactive Calendar Card -->
      <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden mb-8">
        <!-- Month Switcher Header -->
        <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <button type="button" class="p-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 shadow-2xs transition" onclick="APP.toast('Viewing August 2026', 'info')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <h2 class="text-base sm:text-lg font-black text-slate-900 tracking-tight">September 2026</h2>
            <button type="button" class="p-2 rounded-xl bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 shadow-2xs transition" onclick="APP.toast('Viewing October 2026', 'info')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
          </div>

          <div class="flex items-center gap-2 text-xs font-semibold text-slate-500">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            <span>Juan Dela Cruz (BSIT 3-A)</span>
          </div>
        </div>

        <!-- Calendar Days Grid -->
        <div class="p-5 sm:p-7">
          <!-- Weekday Headers -->
          <div class="grid grid-cols-7 gap-2 mb-3 text-center">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sun</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Mon</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Tue</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Wed</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Thu</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-700">Fri</div>
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Sat</div>
          </div>

          <!-- Calendar Grid Cells -->
          <div class="grid grid-cols-7 gap-2 sm:gap-3 text-xs">
            <!-- Week 1 -->
            <div class="h-20 sm:h-24 p-2 rounded-2xl bg-slate-50/50 border border-slate-100 opacity-30"></div>
            <div class="h-20 sm:h-24 p-2 rounded-2xl bg-slate-50/50 border border-slate-100 opacity-30"></div>
            
            <!-- Sep 1 (Present) -->
            <div onclick="showCalendarDayInfo('Sep 1, 2026', 'Present', 'IT311 (08:02 AM) • Present')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] hover:shadow-md transition cursor-pointer flex flex-col justify-between">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800 text-xs">1</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              </div>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <!-- Sep 2 (Present) -->
            <div onclick="showCalendarDayInfo('Sep 2, 2026', 'Present', 'IT312 (10:05 AM) • Present')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] hover:shadow-md transition cursor-pointer flex flex-col justify-between">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800 text-xs">2</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              </div>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <!-- Sep 3 (Tardy) -->
            <div onclick="showCalendarDayInfo('Sep 3, 2026', 'Tardy / Late', 'IT311 (08:22 AM - 22 mins late)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-amber-50/80 border border-amber-200/80 hover:scale-[1.02] hover:shadow-md transition cursor-pointer flex flex-col justify-between">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800 text-xs">3</span>
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
              </div>
              <div class="text-[10px] font-bold text-amber-700 bg-amber-100/70 px-1.5 py-0.5 rounded-md text-center">Late (22m)</div>
            </div>

            <!-- Sep 4 (Present) -->
            <div onclick="showCalendarDayInfo('Sep 4, 2026', 'Present', 'IT312 (10:00 AM) • Present')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] hover:shadow-md transition cursor-pointer flex flex-col justify-between">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800 text-xs">4</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              </div>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <!-- Sep 5 (Weekend) -->
            <div class="h-20 sm:h-24 p-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex flex-col justify-between">
              <span class="font-bold text-slate-400 text-xs">5</span>
              <div class="text-[10px] text-slate-400 text-center">No Class</div>
            </div>

            <!-- Week 2 -->
            <div class="h-20 sm:h-24 p-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex flex-col justify-between">
              <span class="font-bold text-slate-400 text-xs">6</span>
              <div class="text-[10px] text-slate-400 text-center">No Class</div>
            </div>
            
            <div onclick="showCalendarDayInfo('Sep 7, 2026', 'Present', 'IT311 • Present (08:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">7</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 8, 2026', 'Present', 'IT312 • Present (10:01 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between ring-2 ring-indigo-500">
              <div class="flex items-center justify-between">
                <span class="font-bold text-indigo-700 text-xs">8 (Today)</span>
                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
              </div>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <!-- Sep 9 (Absent) -->
            <div onclick="showCalendarDayInfo('Sep 9, 2026', 'Absent', 'IT311 • Missed Class Session')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-rose-50/80 border border-rose-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800 text-xs">9</span>
                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
              </div>
              <div class="text-[10px] font-bold text-rose-700 bg-rose-100/70 px-1.5 py-0.5 rounded-md text-center">Absent </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 10, 2026', 'Present', 'IT312 • Present (09:58 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">10</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 11, 2026', 'Present', 'IT311 • Present (08:04 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">11</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div class="h-20 sm:h-24 p-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex flex-col justify-between">
              <span class="font-bold text-slate-400 text-xs">12</span>
              <div class="text-[10px] text-slate-400 text-center">No Class</div>
            </div>

            <!-- Week 3 -->
            <div class="h-20 sm:h-24 p-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex flex-col justify-between">
              <span class="font-bold text-slate-400 text-xs">13</span>
              <div class="text-[10px] text-slate-400 text-center">No Class</div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 14, 2026', 'Present', 'IT311 • Present (08:02 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">14</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <!-- Sep 15 (Excused) -->
            <div onclick="showCalendarDayInfo('Sep 15, 2026', 'Excused', 'Medical Excuse Slip Approved by Teacher')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-blue-50/80 border border-blue-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <div class="flex items-center justify-between">
                <span class="font-bold text-slate-800 text-xs">15</span>
                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
              </div>
              <div class="text-[10px] font-bold text-blue-700 bg-blue-100/70 px-1.5 py-0.5 rounded-md text-center">Excused ✉</div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 16, 2026', 'Present', 'IT311 • Present (08:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">16</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 17, 2026', 'Present', 'IT312 • Present (10:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">17</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 18, 2026', 'Present', 'IT311 • Present (08:05 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">18</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div class="h-20 sm:h-24 p-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex flex-col justify-between">
              <span class="font-bold text-slate-400 text-xs">19</span>
              <div class="text-[10px] text-slate-400 text-center">No Class</div>
            </div>

            <!-- Week 4 -->
            <div class="h-20 sm:h-24 p-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex flex-col justify-between">
              <span class="font-bold text-slate-400 text-xs">20</span>
              <div class="text-[10px] text-slate-400 text-center">No Class</div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 21, 2026', 'Present', 'IT311 • Present (08:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">21</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 22, 2026', 'Present', 'IT312 • Present (10:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">22</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 23, 2026', 'Present', 'IT311 • Present (08:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">23</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 24, 2026', 'Present', 'IT312 • Present (10:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">24</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 25, 2026', 'Present', 'IT311 • Present (08:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">25</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div class="h-20 sm:h-24 p-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex flex-col justify-between">
              <span class="font-bold text-slate-400 text-xs">26</span>
              <div class="text-[10px] text-slate-400 text-center">No Class</div>
            </div>

            <!-- Week 5 -->
            <div class="h-20 sm:h-24 p-2.5 rounded-2xl bg-slate-50 border border-slate-100 text-slate-400 flex flex-col justify-between">
              <span class="font-bold text-slate-400 text-xs">27</span>
              <div class="text-[10px] text-slate-400 text-center">No Class</div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 28, 2026', 'Present', 'IT311 • Present (08:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">28</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 29, 2026', 'Present', 'IT312 • Present (10:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">29</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div onclick="showCalendarDayInfo('Sep 30, 2026', 'Present', 'IT311 • Present (08:00 AM)')" class="h-20 sm:h-24 p-2.5 rounded-2xl bg-emerald-50/80 border border-emerald-200/80 hover:scale-[1.02] transition cursor-pointer flex flex-col justify-between">
              <span class="font-bold text-slate-800 text-xs">30</span>
              <div class="text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-1.5 py-0.5 rounded-md text-center">Present </div>
            </div>

            <div class="h-20 sm:h-24 p-2 rounded-2xl bg-slate-50/50 border border-slate-100 opacity-30"></div>
            <div class="h-20 sm:h-24 p-2 rounded-2xl bg-slate-50/50 border border-slate-100 opacity-30"></div>
            <div class="h-20 sm:h-24 p-2 rounded-2xl bg-slate-50/50 border border-slate-100 opacity-30"></div>
          </div>
        </div>

        <!-- Legend & Live Detail Footer -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
          <div class="flex flex-wrap items-center gap-4 text-xs font-semibold text-slate-600">
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-emerald-500"></span> Present</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-amber-500"></span> Tardy / Late</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-rose-500"></span> Absent</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-blue-500"></span> Excused Slip</span>
            <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded-md bg-slate-200"></span> No Class</span>
          </div>

          <div class="text-xs font-bold text-indigo-700">
            Current Rate: <span class="text-emerald-600 font-extrabold">95.0% Standing</span>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script>
function showCalendarDayInfo(date, status, details) {
  let badgeColor = 'info';
  if (status === 'Present') badgeColor = 'success';
  if (status.includes('Late') || status.includes('Tardy')) badgeColor = 'warning';
  if (status === 'Absent') badgeColor = 'error';
  APP.toast(`📅 ${date} • Status: ${status} (${details})`, badgeColor);
}
</script>

<?php require_once dirname(__DIR__) . '/partials/footer.php'; ?>
