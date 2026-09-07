<?php $page_title = 'Attendance Analytics'; ?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-6" style="background:var(--color-surface)">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
          <div>
            <div class="flex items-center gap-2 mb-1">
              <span class="px-2 py-0.5 rounded text-xs font-semibold tracking-wide uppercase" style="background:var(--color-teal-100); color:var(--color-teal-500)">AI Engine</span>
              <span class="text-xs" style="color:var(--color-text-muted)">RandomForest v1.2 · 87.3% Accuracy</span>
            </div>
            <h1 class="text-2xl font-bold" style="color:var(--color-text-primary)">Attendance Analytics</h1>
            <p class="text-sm mt-0.5" style="color:var(--color-text-secondary)">Predictive pattern recognition &amp; proactive early-warning risk analysis</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              Model Live (Sep 5, 2026)
            </span>
          </div>
        </div>

        <!-- Top Menu Panel (Tabs) -->
        <div class="bg-white rounded-lg p-1.5 shadow-card border border-gray-100 mb-6 inline-flex flex-wrap gap-1">
          <button id="tab-btn-overview" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-semibold transition-all"
                  style="background:var(--color-teal-500); color:#ffffff;"
                  onclick="switchAnalyticsTab('overview')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Overview &amp; Trends</span>
          </button>
          <button id="tab-btn-patterns" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-medium transition-all"
                  style="background:transparent; color:var(--color-text-secondary);"
                  onclick="switchAnalyticsTab('patterns')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span>Detected Patterns</span>
            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold" style="background:#e0f2fe; color:#0369a1;">3</span>
          </button>
          <button id="tab-btn-at-risk" type="button"
                  class="flex items-center gap-2 px-5 py-2.5 rounded-md text-sm font-medium transition-all"
                  style="background:transparent; color:var(--color-text-secondary);"
                  onclick="switchAnalyticsTab('at-risk')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>At-Risk Students</span>
            <span class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold" style="background:#fef3c7; color:#b45309;">8</span>
          </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 1: OVERVIEW & TRENDS                                -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="analytics-panel-overview" class="space-y-6">
          <!-- Split Panel: Chart (2/3) + Filters & Model Info (1/3) -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Chart Card -->
            <div class="lg:col-span-2 bg-white rounded-lg p-5 shadow-card">
              <div class="flex items-center justify-between mb-4">
                <div>
                  <h3 class="text-lg font-semibold" style="color:var(--color-text-primary)">Attendance Trend (90 Days)</h3>
                  <p class="text-xs mt-0.5" style="color:var(--color-text-muted)">Rolling daily percentage vs predicted benchmark</p>
                </div>
                <span class="badge badge-excused">90-Day Window</span>
              </div>
              <div class="w-full rounded-lg" style="height:290px; background:var(--color-surface); display:flex; align-items:center; justify-content:center;">
                <canvas id="analytics-trend-chart"></canvas>
              </div>
            </div>

            <!-- Filter Panel Card -->
            <div class="bg-white rounded-lg p-5 shadow-card flex flex-col justify-between">
              <div>
                <h3 class="text-base font-semibold mb-3" style="color:var(--color-text-primary)">Filter Parameters</h3>
                <div class="space-y-3.5 mb-5">
                  <div>
                    <label class="form-label text-xs">Date Range</label>
                    <select id="filter-date-range" class="form-input form-select text-sm">
                      <option value="90">Aug 1 – Sep 7, 2026 (90 Days)</option>
                      <option value="60">Last 60 days</option>
                      <option value="30">Last 30 days</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label text-xs">Grade Level</label>
                    <select id="filter-grade" class="form-input form-select text-sm">
                      <option value="all">All Grades</option>
                      <option value="7">Grade 7</option>
                      <option value="8">Grade 8</option>
                      <option value="9">Grade 9</option>
                      <option value="10">Grade 10</option>
                    </select>
                  </div>
                  <div>
                    <label class="form-label text-xs">Section</label>
                    <select id="filter-section" class="form-input form-select text-sm">
                      <option value="all">All Sections</option>
                      <option value="A">Section A</option>
                      <option value="B">Section B</option>
                      <option value="C">Section C</option>
                    </select>
                  </div>
                  <button type="button" class="btn btn-primary w-full justify-center text-sm" onclick="applyAnalyticsFilters()">Apply Filters</button>
                </div>
              </div>

              <!-- Model Info Block -->
              <div class="pt-4 border-t border-gray-100">
                <h4 class="text-xs font-bold uppercase tracking-wider mb-2.5" style="color:var(--color-text-secondary)">Model Specifications</h4>
                <div class="space-y-2 text-xs">
                  <div class="flex justify-between py-1 border-b border-gray-50">
                    <span style="color:var(--color-text-muted)">Algorithm</span>
                    <span class="font-medium" style="color:var(--color-text-primary)">RandomForestClassifier</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-gray-50">
                    <span style="color:var(--color-text-muted)">ROC-AUC Score</span>
                    <span class="font-medium text-emerald-600">0.894</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-gray-50">
                    <span style="color:var(--color-text-muted)">Training Records</span>
                    <span class="font-medium" style="color:var(--color-text-primary)">24,850 rows</span>
                  </div>
                  <div class="flex justify-between py-1">
                    <span style="color:var(--color-text-muted)">Last Retrained</span>
                    <span class="font-medium" style="color:var(--color-text-primary)">Sep 1, 2026</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Insight Teasers (Click to switch tabs) -->
          <div>
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold flex items-center gap-2" style="color:var(--color-text-primary)">
                <svg class="w-5 h-5" style="color:var(--color-teal-500)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                Key Insights
              </h3>
              <span class="text-xs" style="color:var(--color-text-muted)">Click any card to inspect full details</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Pattern Card -->
              <div class="ml-insight-card cursor-pointer hover:shadow-md transition-shadow" onclick="switchAnalyticsTab('patterns')">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5" style="color:var(--color-teal-500)">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Pattern Detected
                  </p>
                  <span class="text-xs px-2 py-0.5 rounded bg-teal-50 text-teal-700 font-medium">Monday Spike</span>
                </div>
                <h4 class="text-base font-semibold mb-1" style="color:var(--color-text-primary)">3× Monday Absence Rate in Grade 7</h4>
                <p class="text-sm mb-3" style="color:var(--color-text-secondary)">12 students identified in Grade 7 Section A with consistent Monday absence patterns over 90 days.</p>
                <div class="flex items-center justify-between text-xs pt-2 border-t border-gray-100">
                  <span style="color:var(--color-text-muted)">Confidence: 91.2%</span>
                  <span class="font-medium" style="color:var(--color-teal-500)">Explore Patterns →</span>
                </div>
              </div>

              <!-- At-Risk Card -->
              <div class="ml-insight-card cursor-pointer hover:shadow-md transition-shadow" onclick="switchAnalyticsTab('at-risk')">
                <div class="flex items-center justify-between mb-2">
                  <p class="text-xs font-semibold uppercase tracking-wider flex items-center gap-1.5" style="color:var(--color-alert-high)">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    High Risk Warning
                  </p>
                  <span class="badge badge-high">8 Students Flagged</span>
                </div>
                <h4 class="text-base font-semibold mb-1" style="color:var(--color-text-primary)">Chronic Absence Risk Detected</h4>
                <p class="text-sm mb-3" style="color:var(--color-text-secondary)">8 students exhibit indicators matching historical drop-out and chronic absenteeism profiles.</p>
                <div class="flex items-center justify-between text-xs pt-2 border-t border-gray-100">
                  <span style="color:var(--color-text-muted)">Immediate intervention recommended</span>
                  <span class="font-medium text-red-600">Review Student List →</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Visualizations (3-col: Bar, Grouped Bar, Doughnut) -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Graph 1: Categorical Bar Chart -->
            <div class="bg-white rounded-lg p-5 shadow-card flex flex-col justify-between">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h3 class="text-sm font-semibold" style="color:var(--color-text-primary)">Absences by Day</h3>
                  <p class="text-[11px]" style="color:var(--color-text-muted)">Bar Chart · Categorical Frequencies</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded bg-red-50 text-red-600 font-semibold">Mon Alert</span>
              </div>
              <div class="w-full rounded-lg" style="height:210px; background:var(--color-surface); display:flex; align-items:center; justify-content:center;">
                <canvas id="day-chart"></canvas>
              </div>
            </div>

            <!-- Graph 2: Grouped Multi-Bar Chart -->
            <div class="bg-white rounded-lg p-5 shadow-card flex flex-col justify-between">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h3 class="text-sm font-semibold" style="color:var(--color-text-primary)">Grade Comparison</h3>
                  <p class="text-[11px]" style="color:var(--color-text-muted)">Grouped Bar · Absence vs Tardy %</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded bg-teal-50 text-teal-700 font-semibold">Grades 7–10</span>
              </div>
              <div class="w-full rounded-lg" style="height:210px; background:var(--color-surface); display:flex; align-items:center; justify-content:center;">
                <canvas id="grade-chart"></canvas>
              </div>
            </div>

            <!-- Graph 3: Proportional Part-to-Whole Doughnut Chart -->
            <div class="bg-white rounded-lg p-5 shadow-card flex flex-col justify-between">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h3 class="text-sm font-semibold" style="color:var(--color-text-primary)">Status Composition</h3>
                  <p class="text-[11px]" style="color:var(--color-text-muted)">Doughnut · Part-to-Whole Split</p>
                </div>
                <span class="text-xs px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 font-semibold">Overall</span>
              </div>
              <div class="w-full rounded-lg" style="height:210px; background:var(--color-surface); display:flex; align-items:center; justify-content:center;">
                <canvas id="status-doughnut-chart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 2: DETECTED PATTERNS                                -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="analytics-panel-patterns" class="hidden space-y-4">
          <div class="bg-white rounded-lg p-5 shadow-card mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h3 class="text-lg font-bold" style="color:var(--color-text-primary)">Detected Behavioral Patterns</h3>
              <p class="text-sm mt-0.5" style="color:var(--color-text-secondary)">Patterns isolated by cross-referencing timestamps, student profiles, and historical trends</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5" onclick="APP.showToast('Pattern re-scan scheduled.', 'info')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              Refresh Pattern Engine
            </button>
          </div>

          <!-- Pattern Card 1 -->
          <div class="ml-insight-card bg-white p-6 rounded-lg shadow-card">
            <div class="flex items-center justify-between mb-3">
              <span class="px-2.5 py-1 rounded text-xs font-semibold uppercase tracking-wider inline-flex items-center gap-1.5" style="background:var(--color-teal-100); color:var(--color-teal-500)">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Pattern #1 · Day of Week
              </span>
              <span class="badge badge-high">High Impact</span>
            </div>
            <h4 class="text-xl font-bold mb-2" style="color:var(--color-text-primary)">Absences spike on Mondays (Grade 7 Section A)</h4>
            <p class="text-sm mb-4 leading-relaxed" style="color:var(--color-text-secondary)">
              Students in Grade 7 Section A demonstrate a <strong>3× higher absence rate on Mondays</strong> compared to any other weekday.
              Based on 90 continuous days of attendance logs. This pattern has persisted without interruption over the last 4 consecutive weeks.
            </p>
            <div class="p-3.5 rounded-lg mb-4 flex items-center justify-between" style="background:var(--color-surface)">
              <div class="text-sm">
                <span class="font-semibold" style="color:var(--color-text-primary)">12 students</span>
                <span style="color:var(--color-text-muted)"> regularly trigger this anomaly</span>
              </div>
              <span class="text-xs font-medium" style="color:var(--color-text-secondary)">Root hypothesis: Weekend transition fatigue / Transportation</span>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="btn btn-primary btn-sm" onclick="showPatternModal('Monday Spike (Grade 7 Sec A)', ['Dela Cruz, Juan (BCP-001)', 'Santos, Maria (BCP-002)', 'Alvarez, Kyle (BCP-014)', '9 others...'], 'Schedule parent counseling on weekend study schedules.')">View Affected Students →</button>
            </div>
          </div>

          <!-- Pattern Card 2 -->
          <div class="ml-insight-card bg-white p-6 rounded-lg shadow-card">
            <div class="flex items-center justify-between mb-3">
              <span class="px-2.5 py-1 rounded text-xs font-semibold uppercase tracking-wider inline-flex items-center gap-1.5" style="background:var(--color-teal-100); color:var(--color-teal-500)">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Pattern #2 · Timing Correlation
              </span>
              <span class="badge badge-medium">Medium Impact</span>
            </div>
            <h4 class="text-xl font-bold mb-2" style="color:var(--color-text-primary)">Friday morning tardiness increases across Grades 9–10</h4>
            <p class="text-sm mb-4 leading-relaxed" style="color:var(--color-text-secondary)">
              Tardy records on Friday mornings are <strong>2.1× higher</strong> than Monday–Thursday averages across the entire high school division.
              Peak late arrival window is 7:35 AM – 7:55 AM.
            </p>
            <div class="p-3.5 rounded-lg mb-4 flex items-center justify-between" style="background:var(--color-surface)">
              <div class="text-sm">
                <span class="font-semibold" style="color:var(--color-text-primary)">28 students</span>
                <span style="color:var(--color-text-muted)"> frequently arrived late this month</span>
              </div>
              <span class="text-xs font-medium" style="color:var(--color-text-secondary)">Correlated with Friday transit slowdowns</span>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="btn btn-primary btn-sm" onclick="showPatternModal('Friday Morning Tardiness', ['Moreno, Alex (BCP-019)', 'Reyes, Pedro (BCP-003)', 'Lim, Jenny (BCP-008)', '25 others...'], 'Adjust RFID gate staffing on Friday 7:30-8:00 AM.')">View Affected Students →</button>
            </div>
          </div>

          <!-- Pattern Card 3 -->
          <div class="ml-insight-card bg-white p-6 rounded-lg shadow-card">
            <div class="flex items-center justify-between mb-3">
              <span class="px-2.5 py-1 rounded text-xs font-semibold uppercase tracking-wider inline-flex items-center gap-1.5" style="background:var(--color-teal-100); color:var(--color-teal-500)">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Pattern #3 · Section Variance
              </span>
              <span class="badge badge-high">High Impact</span>
            </div>
            <h4 class="text-xl font-bold mb-2" style="color:var(--color-text-primary)">Section B exhibits 40% higher absence concentration</h4>
            <p class="text-sm mb-4 leading-relaxed" style="color:var(--color-text-secondary)">
              Grade 8 Section B shows a 40% higher net absence rate than Sections A and C combined.
              Further analysis reveals <strong>5 specific students account for 72%</strong> of all accumulated absences in this section.
            </p>
            <div class="p-3.5 rounded-lg mb-4 flex items-center justify-between" style="background:var(--color-surface)">
              <div class="text-sm">
                <span class="font-semibold" style="color:var(--color-text-primary)">5 core students</span>
                <span style="color:var(--color-text-muted)"> driving section-level skew</span>
              </div>
              <span class="text-xs font-medium" style="color:var(--color-text-secondary)">Candidate for individualized student case management</span>
            </div>
            <div class="flex justify-end gap-2">
              <button type="button" class="btn btn-primary btn-sm" onclick="showPatternModal('Section B Absence Concentration', ['Cruz, Robert (BCP-004)', 'Lim, Jenny (BCP-008)', 'Villanueva, Eric (BCP-022)', 'Tan, Chloe (BCP-031)', 'Ramos, Leo (BCP-035)'], 'Coordinate with Grade 8 adviser and guidance office.')">View Affected Students →</button>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 3: AT-RISK STUDENTS                                 -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="analytics-panel-at-risk" class="hidden space-y-4">
          <!-- Warning Banner -->
          <div class="rounded-lg p-4 flex items-start gap-3 shadow-sm" style="background:#fffbeb; border:1px solid #fde68a">
            <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 mt-0.5" style="background:#fde68a">
              <svg class="w-5 h-5 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between">
                <h4 class="font-bold text-sm" style="color:#b45309">8 Students Flagged for Chronic Absence Risk</h4>
                <span class="text-xs px-2 py-0.5 rounded font-semibold bg-amber-100 text-amber-800">Early Intervention</span>
              </div>
              <p class="text-sm mt-0.5" style="color:#92400e">
                Criteria evaluated: 30-day absence velocity, repetitive day-of-week clusterings, unexcused absence ratios, and deviation from grade-level norms.
              </p>
            </div>
          </div>

          <!-- At-Risk Table -->
          <div class="bg-white rounded-lg shadow-card overflow-x-auto">
            <table class="data-table">
              <thead>
                <tr>
                  <th scope="col">Student Name</th>
                  <th scope="col">Grade &amp; Section</th>
                  <th scope="col">Absences (Past 30d)</th>
                  <th scope="col">Risk Factor</th>
                  <th scope="col">Risk Classification</th>
                  <th scope="col" class="text-right">Intervention</th>
                </tr>
              </thead>
              <tbody>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-semibold" style="color:var(--color-text-primary)">Santos, Maria</td>
                  <td>Grade 7 · Sec A</td>
                  <td><span class="font-bold text-red-600">8 / 30 days</span></td>
                  <td class="text-xs" style="color:var(--color-text-secondary)">Monday spike + Consecutive absences</td>
                  <td><span class="badge badge-high">● HIGH RISK</span></td>
                  <td class="text-right space-x-1">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="showStudentRiskModal('Santos, Maria', 'Grade 7 · Sec A', '8 absences (27%)', 'HIGH', 'Rapid escalation in past 2 weeks. Parent contact advised.')">Review</button>
                    <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1" onclick="APP.showToast('Notification queued for Santos, Maria parent.', 'info')"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Alert Parent</button>
                  </td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-semibold" style="color:var(--color-text-primary)">Cruz, Robert</td>
                  <td>Grade 8 · Sec B</td>
                  <td><span class="font-bold text-amber-600">6 / 30 days</span></td>
                  <td class="text-xs" style="color:var(--color-text-secondary)">Unexcused Friday clusters</td>
                  <td><span class="badge badge-medium">● MED RISK</span></td>
                  <td class="text-right space-x-1">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="showStudentRiskModal('Cruz, Robert', 'Grade 8 · Sec B', '6 absences (20%)', 'MEDIUM', 'Unexcused Friday pattern. Needs attendance agreement.')">Review</button>
                    <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1" onclick="APP.showToast('Notification queued for Cruz, Robert parent.', 'info')"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Alert Parent</button>
                  </td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-semibold" style="color:var(--color-text-primary)">Lim, Jenny</td>
                  <td>Grade 8 · Sec B</td>
                  <td><span class="font-bold text-amber-600">5 / 30 days</span></td>
                  <td class="text-xs" style="color:var(--color-text-secondary)">Tardy escalation preceding absence</td>
                  <td><span class="badge badge-medium">● MED RISK</span></td>
                  <td class="text-right space-x-1">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="showStudentRiskModal('Lim, Jenny', 'Grade 8 · Sec B', '5 absences (17%)', 'MEDIUM', 'Tardy rate 40% before missed days.')">Review</button>
                    <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1" onclick="APP.showToast('Notification queued for Lim, Jenny parent.', 'info')"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Alert Parent</button>
                  </td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-semibold" style="color:var(--color-text-primary)">Moreno, Alex</td>
                  <td>Grade 9 · Sec A</td>
                  <td><span class="font-bold text-amber-600">5 / 30 days</span></td>
                  <td class="text-xs" style="color:var(--color-text-secondary)">Periodic midweek absence</td>
                  <td><span class="badge badge-medium">● MED RISK</span></td>
                  <td class="text-right space-x-1">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="showStudentRiskModal('Moreno, Alex', 'Grade 9 · Sec A', '5 absences (17%)', 'MEDIUM', 'Wednesday absence clustering.')">Review</button>
                    <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1" onclick="APP.showToast('Notification queued for Moreno, Alex parent.', 'info')"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Alert Parent</button>
                  </td>
                </tr>
                <tr class="hover:bg-slate-50 transition-colors">
                  <td class="font-semibold" style="color:var(--color-text-primary)">Villanueva, Eric</td>
                  <td>Grade 8 · Sec B</td>
                  <td><span class="font-bold text-amber-600">5 / 30 days</span></td>
                  <td class="text-xs" style="color:var(--color-text-secondary)">Repeated medical excuse backlog</td>
                  <td><span class="badge badge-medium">● MED RISK</span></td>
                  <td class="text-right space-x-1">
                    <button type="button" class="btn btn-secondary btn-sm" onclick="showStudentRiskModal('Villanueva, Eric', 'Grade 8 · Sec B', '5 absences (17%)', 'MEDIUM', 'Multiple unverified excuse slips.')">Review</button>
                    <button type="button" class="btn btn-ghost btn-sm inline-flex items-center gap-1" onclick="APP.showToast('Notification queued for Villanueva, Eric parent.', 'info')"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg> Alert Parent</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Bottom Legend & Actions -->
          <div class="bg-white rounded-lg p-4 shadow-card flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-xs space-y-1" style="color:var(--color-text-secondary)">
              <div class="flex items-center gap-2">
                <span class="badge badge-high">HIGH RISK</span>
                <span>Chronic absence likelihood &gt;80% without immediate counselor contact</span>
              </div>
              <div class="flex items-center gap-2">
                <span class="badge badge-medium">MED RISK</span>
                <span>Early warning indicators present; automated threshold monitoring active</span>
              </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm flex items-center gap-2" onclick="APP.showToast('At-Risk student summary exported to CSV.', 'success')">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <span>Export At-Risk Report</span>
            </button>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
<?php $page_js = '<script src="/Attendance _Management_System/assets/js/analytics.js"></script>'; ?>
<?php include __DIR__ . '/../partials/footer.php'; ?>

<script>APP.highlightNav('analytics');</script>
