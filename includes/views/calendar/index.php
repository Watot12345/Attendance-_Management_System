<?php $page_title = 'Attendance Calendar'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-3">
          <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Attendance Calendar</h1>
          <button class="btn btn-secondary btn-sm inline-flex items-center gap-1.5" onclick="window.print()">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Print
          </button>
        </div>

        <!-- Student Selector -->
        <div class="bg-white rounded-lg p-4 shadow-card mb-4">
          <label for="cal-student" class="form-label">Student</label>
          <select id="cal-student" class="form-input form-select">
            <option>Dela Cruz, Juan · BCP-001</option>
            <option>Santos, Maria · BCP-002</option>
            <option>Reyes, Pedro · BCP-003</option>
            <option>Garcia, Ana · BCP-004</option>
          </select>
        </div>

        <!-- Calendar -->
        <div class="bg-white rounded-lg shadow-card">
          <!-- Month Navigation -->
          <div class="flex items-center justify-between px-5 py-4 border-b" style="border-color:var(--color-border)">
            <button class="btn btn-ghost btn-sm" onclick="navigateMonth(-1)">← August 2026</button>
            <h3 class="text-lg font-semibold" style="color:var(--color-text-primary)" id="cal-month-title">September 2026</h3>
            <button class="btn btn-ghost btn-sm" onclick="navigateMonth(1)">October 2026 →</button>
          </div>

          <!-- Calendar Grid -->
          <div class="p-5">
            <!-- Day Headers -->
            <div class="grid grid-cols-7 gap-2 mb-2">
              <div class="text-center text-xs font-semibold uppercase" style="color:var(--color-text-muted)">Sun</div>
              <div class="text-center text-xs font-semibold uppercase" style="color:var(--color-text-muted)">Mon</div>
              <div class="text-center text-xs font-semibold uppercase" style="color:var(--color-text-muted)">Tue</div>
              <div class="text-center text-xs font-semibold uppercase" style="color:var(--color-text-muted)">Wed</div>
              <div class="text-center text-xs font-semibold uppercase" style="color:var(--color-text-muted)">Thu</div>
              <div class="text-center text-xs font-semibold uppercase" style="color:var(--color-text-muted)">Fri</div>
              <div class="text-center text-xs font-semibold uppercase" style="color:var(--color-text-muted)">Sat</div>
            </div>

            <!-- Week Rows -->
            <div class="grid grid-cols-7 gap-2" id="cal-grid">
              <!-- Week 1 (Sep 2026 starts on Tuesday) -->
              <div class="cal-day cal-blank"></div>
              <div class="cal-day cal-blank"></div>
              <div class="cal-day cal-present" onclick="showDayDetail(1,'present')">1</div>
              <div class="cal-day cal-present" onclick="showDayDetail(2,'present')">2</div>
              <div class="cal-day cal-tardy" onclick="showDayDetail(3,'tardy')">3</div>
              <div class="cal-day cal-present" onclick="showDayDetail(4,'present')">4</div>
              <div class="cal-day cal-nodata">5</div>

              <!-- Week 2 -->
              <div class="cal-day cal-nodata">6</div>
              <div class="cal-day cal-present" onclick="showDayDetail(7,'present')">7</div>
              <div class="cal-day cal-present" onclick="showDayDetail(8,'present')">8</div>
              <div class="cal-day cal-absent" onclick="showDayDetail(9,'absent')">9</div>
              <div class="cal-day cal-present" onclick="showDayDetail(10,'present')">10</div>
              <div class="cal-day cal-present" onclick="showDayDetail(11,'present')">11</div>
              <div class="cal-day cal-nodata">12</div>

              <!-- Week 3 -->
              <div class="cal-day cal-nodata">13</div>
              <div class="cal-day cal-present" onclick="showDayDetail(14,'present')">14</div>
              <div class="cal-day cal-excused" onclick="showDayDetail(15,'excused')">15</div>
              <div class="cal-day cal-present" onclick="showDayDetail(16,'present')">16</div>
              <div class="cal-day cal-present" onclick="showDayDetail(17,'present')">17</div>
              <div class="cal-day cal-present" onclick="showDayDetail(18,'present')">18</div>
              <div class="cal-day cal-nodata">19</div>

              <!-- Week 4 -->
              <div class="cal-day cal-nodata">20</div>
              <div class="cal-day cal-present" onclick="showDayDetail(21,'present')">21</div>
              <div class="cal-day cal-present" onclick="showDayDetail(22,'present')">22</div>
              <div class="cal-day cal-present" onclick="showDayDetail(23,'present')">23</div>
              <div class="cal-day cal-present" onclick="showDayDetail(24,'present')">24</div>
              <div class="cal-day cal-present" onclick="showDayDetail(25,'present')">25</div>
              <div class="cal-day cal-nodata">26</div>

              <!-- Week 5 -->
              <div class="cal-day cal-nodata">27</div>
              <div class="cal-day cal-present" onclick="showDayDetail(28,'present')">28</div>
              <div class="cal-day cal-present" onclick="showDayDetail(29,'present')">29</div>
              <div class="cal-day cal-present" onclick="showDayDetail(30,'present')">30</div>
              <div class="cal-day cal-blank"></div>
              <div class="cal-day cal-blank"></div>
              <div class="cal-day cal-blank"></div>
            </div>
          </div>

          <!-- Legend -->
          <div class="px-5 py-3 border-t flex flex-wrap gap-4" style="border-color:var(--color-border)">
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#dcfce7; border:1px solid #bbf7d0"></span> Present</span>
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#fef3c7; border:1px solid #fde68a"></span> Tardy</span>
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#fee2e2; border:1px solid #fecaca"></span> Absent</span>
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#dbeafe; border:1px solid #bfdbfe"></span> Excused</span>
            <span class="flex items-center gap-1.5 text-xs"><span class="w-3 h-3 rounded" style="background:#f1f5f9; border:1px solid #e2e8f0"></span> No class</span>
          </div>

          <!-- Summary -->
          <div class="px-5 py-3 border-t" style="border-color:var(--color-border)">
            <p class="text-sm" style="color:var(--color-text-secondary)">
              <strong>Summary:</strong> 18 Present · 1 Tardy · 1 Absent · 1 Excused
            </p>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php $page_js = '<script src="../../../assets/js/calendar.js"></script>'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('calendar');</script>
