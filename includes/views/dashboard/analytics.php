<?php 
$page_title = 'Attendance Analytics'; 
require_once dirname(__DIR__, 2) . '/core/Router.php';
?>
<?php include __DIR__ . '/../partials/header.php'; ?>
<body class="min-h-screen bg-slate-50 text-slate-900 font-sans antialiased">
  <div class="flex min-h-screen">
    <?php include __DIR__ . '/../partials/sidebar.php'; ?>

    <div class="flex-1 flex flex-col min-w-0">
      <?php include __DIR__ . '/../partials/navbar.php'; ?>

      <main class="flex-1 p-4 lg:p-7 space-y-6">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs">
          <div>
            <div class="flex items-center gap-2 mb-1.5 flex-wrap">
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold tracking-wide uppercase bg-teal-50 text-teal-700 border border-teal-200">
                Scikit-Learn ML Engine
              </span>
              <span class="text-xs font-semibold text-slate-500 flex items-center gap-1.5" id="header-model-summary">
                RandomForest Classifier · <strong class="text-emerald-600" id="header-accuracy">88.5% Acc</strong> · <strong class="text-indigo-600" id="header-roc">0.894 ROC-AUC</strong>
              </span>
            </div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900">Attendance Analytics &amp; Machine Learning</h1>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Supervised early-warning risk scoring, unsupervised behavioral clustering &amp; temporal anomaly detection</p>
          </div>
          <div class="flex items-center gap-2.5 flex-wrap">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200" id="model-status-badge">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span id="model-status-text">Model Active</span>
            </span>
            <button id="btn-retrain-ml" type="button" class="btn btn-primary btn-sm flex items-center gap-2 font-bold shadow-xs cursor-pointer" onclick="triggerModelRetrain()">
              <svg id="retrain-spinner" class="w-4 h-4 transition-transform duration-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span>Retrain Model</span>
            </button>
          </div>
        </div>

        <!-- Tab Navigation Bar -->
        <div class="bg-white rounded-2xl p-1.5 shadow-xs border border-slate-200/80 inline-flex flex-wrap gap-1">
          <button id="tab-btn-overview" type="button"
                  class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-blue-600 text-white shadow-xs"
                  onclick="switchAnalyticsTab('overview')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            <span>Overview &amp; Trends</span>
          </button>
          <button id="tab-btn-patterns" type="button"
                  class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-50"
                  onclick="switchAnalyticsTab('patterns')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <span>Detected Patterns</span>
            <span id="badge-pattern-count" class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-black bg-blue-50 text-blue-700">3</span>
          </button>
          <button id="tab-btn-at-risk" type="button"
                  class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-50"
                  onclick="switchAnalyticsTab('at-risk')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span>At-Risk Students</span>
            <span id="badge-at-risk-count" class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-black bg-amber-100 text-amber-800">5</span>
          </button>
          <button id="tab-btn-clusters" type="button"
                  class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-50"
                  onclick="switchAnalyticsTab('clusters')">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <span>Behavioral Clusters</span>
            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] font-black bg-purple-50 text-purple-700">K-Means</span>
          </button>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 1: OVERVIEW & TRENDS                                -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="analytics-panel-overview" class="space-y-6">
          <!-- Split Panel: Chart (2/3) + Filters & Model Info (1/3) -->
          <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Chart Card -->
            <div class="lg:col-span-2 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between">
              <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                  <h3 class="text-base sm:text-lg font-bold text-slate-900">Attendance Trend vs Scikit-Learn Benchmark</h3>
                  <p class="text-xs text-slate-500 mt-0.5">Rolling actual attendance percentage vs machine learning baseline projection</p>
                </div>
                <span id="trend-window-pill" class="text-[11px] font-bold px-2.5 py-1 rounded-lg bg-teal-50 text-teal-700 border border-teal-200/80">90-Day Window</span>
              </div>
              <div class="w-full rounded-xl bg-slate-50/70 p-2 relative" style="height:310px;">
                <canvas id="analytics-trend-chart"></canvas>
              </div>
            </div>

            <!-- Filter Panel & Model Specifications Card -->
            <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs flex flex-col justify-between space-y-5">
              <div>
                <div class="flex items-center justify-between mb-3">
                  <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Dynamic Filters</h3>
                  <button type="button" class="text-[11px] font-bold text-blue-600 hover:underline cursor-pointer" onclick="resetAnalyticsFilters()">Reset</button>
                </div>
                <div class="space-y-3">
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Date Range</label>
                    <select id="filter-date-range" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                      <option value="90" selected>Last 90 Days (Full Semester Horizon)</option>
                      <option value="60">Last 60 Days (Midterm Horizon)</option>
                      <option value="30">Last 30 Days (Recent Active Month)</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Year Level</label>
                    <select id="filter-grade" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                      <option value="all" selected>All Year Levels</option>
                      <option value="1">1st Year (Freshman)</option>
                      <option value="2">2nd Year (Sophomore)</option>
                      <option value="3">3rd Year (Junior)</option>
                      <option value="4">4th Year (Senior)</option>
                    </select>
                  </div>
                  <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Section</label>
                    <select id="filter-section" class="w-full px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:border-blue-500 text-slate-800 transition">
                      <option value="all" selected>All Sections</option>
                      <option value="A">Section A</option>
                      <option value="B">Section B</option>
                      <option value="C">Section C</option>
                    </select>
                  </div>
                  <button type="button" class="w-full py-2.5 px-4 rounded-xl text-xs font-bold bg-blue-600 hover:bg-blue-700 text-white transition shadow-xs cursor-pointer mt-1" onclick="applyAnalyticsFilters()">
                    Apply Filters
                  </button>
                </div>
              </div>

              <!-- Real Model Specifications Block -->
              <div class="pt-4 border-t border-slate-100">
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-2.5">Live Scikit-Learn Model Specs</h4>
                <div class="space-y-2 text-xs">
                  <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Algorithm</span>
                    <span class="font-bold text-slate-900" id="spec-algorithm">RandomForestClassifier</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">ROC-AUC Score</span>
                    <span class="font-bold text-emerald-600" id="spec-roc-auc">0.894</span>
                  </div>
                  <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Training Samples</span>
                    <span class="font-bold text-slate-900" id="spec-samples">1,200 rows</span>
                  </div>
                  <div class="flex justify-between py-1">
                    <span class="text-slate-500">Last Retrained</span>
                    <span class="font-bold text-slate-900" id="spec-retrained">Sep 20, 2026</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Feature Importances Progress Bars -->
          <div class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200/80 shadow-xs">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
              <div>
                <h3 class="text-base font-bold text-slate-900">Scikit-Learn Feature Importance Attributions</h3>
                <p class="text-xs text-slate-500">Trained weight contributions calculated by Gini impurity reduction across tree estimators</p>
              </div>
              <span class="text-xs font-semibold px-2.5 py-1 rounded-lg bg-indigo-50 text-indigo-700 border border-indigo-200/70">Tree Ensembles</span>
            </div>
            <div id="feature-importance-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 animate-pulse space-y-2">
                <div class="flex items-center justify-between">
                  <div class="h-3 bg-slate-200 rounded w-24"></div>
                  <div class="h-3 bg-slate-200 rounded w-8"></div>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5"></div>
              </div>
              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 animate-pulse space-y-2">
                <div class="flex items-center justify-between">
                  <div class="h-3 bg-slate-200 rounded w-28"></div>
                  <div class="h-3 bg-slate-200 rounded w-8"></div>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5"></div>
              </div>
              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 animate-pulse space-y-2">
                <div class="flex items-center justify-between">
                  <div class="h-3 bg-slate-200 rounded w-20"></div>
                  <div class="h-3 bg-slate-200 rounded w-8"></div>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5"></div>
              </div>
              <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 animate-pulse space-y-2">
                <div class="flex items-center justify-between">
                  <div class="h-3 bg-slate-200 rounded w-24"></div>
                  <div class="h-3 bg-slate-200 rounded w-8"></div>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5"></div>
              </div>
            </div>
          </div>

          <!-- Quick Insight Teasers -->
          <div>
            <div class="flex items-center justify-between mb-3.5">
              <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Automated Early-Warning Insights</span>
              </h3>
              <span class="text-xs text-slate-400">Click any card to inspect full details</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <!-- Pattern Card Teaser -->
              <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow cursor-pointer group" onclick="switchAnalyticsTab('patterns')">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-teal-600 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    Anomaly Isolated
                  </span>
                  <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-teal-50 text-teal-700 border border-teal-200">Monday Spike</span>
                </div>
                <h4 class="text-sm font-bold text-slate-900 mb-1 group-hover:text-blue-600 transition">3.1× Monday Absence Rate Anomaly</h4>
                <p class="text-xs text-slate-600 mb-3 leading-relaxed">Statistical clustering isolated on Mondays across high school cohorts with Z-score of 2.87.</p>
                <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                  <span class="text-slate-400">Confidence: <strong>94.2%</strong></span>
                  <span class="font-bold text-blue-600">Explore Patterns →</span>
                </div>
              </div>

              <!-- At-Risk Card Teaser -->
              <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow cursor-pointer group" onclick="switchAnalyticsTab('at-risk')">
                <div class="flex items-center justify-between mb-2">
                  <span class="text-xs font-bold uppercase tracking-wider text-rose-600 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    High Risk Alert
                  </span>
                  <span class="text-[11px] font-bold px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 border border-rose-200" id="teaser-high-risk-badge">5 High Risk</span>
                </div>
                <h4 class="text-sm font-bold text-slate-900 mb-1 group-hover:text-rose-600 transition">Consecutive Absence Dropout Vulnerability</h4>
                <p class="text-xs text-slate-600 mb-3 leading-relaxed">Students exceeding 3+ consecutive unexcused absences flagged with &gt;75% risk probability.</p>
                <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-100">
                  <span class="text-slate-400">Proactive Intervention Ready</span>
                  <span class="font-bold text-rose-600">Review Student List →</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Bottom Visualizations (3-col: Bar, Grouped Bar, Doughnut) -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            <!-- Graph 1: Categorical Bar Chart -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h3 class="text-sm font-bold text-slate-900">Absences by Day of Week</h3>
                  <p class="text-[11px] text-slate-500">Anomaly highlight on peak absence day</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-rose-50 text-rose-600 border border-rose-200">Mon Spike</span>
              </div>
              <div class="w-full rounded-xl bg-slate-50/70 p-2 relative" style="height:210px;">
                <canvas id="day-chart"></canvas>
              </div>
            </div>

            <!-- Graph 2: Grouped Multi-Bar Chart -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h3 class="text-sm font-bold text-slate-900">Year Level Comparison</h3>
                  <p class="text-[11px] text-slate-500">Absence Rate vs Tardy Rate %</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">College Cohorts</span>
              </div>
              <div class="w-full rounded-xl bg-slate-50/70 p-2 relative" style="height:210px;">
                <canvas id="grade-chart"></canvas>
              </div>
            </div>

            <!-- Graph 3: Proportional Part-to-Whole Doughnut Chart -->
            <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col justify-between">
              <div class="flex items-center justify-between mb-3">
                <div>
                  <h3 class="text-sm font-bold text-slate-900">Status Composition</h3>
                  <p class="text-[11px] text-slate-500">Total institutional attendance ratio</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-emerald-50 text-emerald-700 border border-emerald-200">Institution</span>
              </div>
              <div class="w-full rounded-xl bg-slate-50/70 p-2 relative" style="height:210px;">
                <canvas id="status-doughnut-chart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 2: DETECTED PATTERNS                                -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="analytics-panel-patterns" class="hidden space-y-4">
          <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h3 class="text-lg font-bold text-slate-900">Machine Learning Detected Patterns</h3>
              <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Statistical anomaly detection &amp; cohort correlations evaluated across student schedules</p>
            </div>
            <button type="button" class="btn btn-secondary btn-sm inline-flex items-center gap-1.5 cursor-pointer font-bold" onclick="loadAnalyticsPatterns()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
              <span>Refresh Patterns</span>
            </button>
          </div>

          <!-- Patterns Container -->
          <div id="patterns-container" class="space-y-4">
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 animate-pulse">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-slate-200 rounded-full w-40"></div>
                <div class="h-5 bg-slate-200 rounded-full w-24"></div>
              </div>
              <div class="h-5 bg-slate-200 rounded w-2/3"></div>
              <div class="space-y-1.5">
                <div class="h-3 bg-slate-100 rounded w-full"></div>
                <div class="h-3 bg-slate-100 rounded w-4/5"></div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                <div class="h-3 bg-slate-200 rounded w-32"></div>
                <div class="h-3 bg-slate-200 rounded w-24"></div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                <div class="h-3 bg-slate-200 rounded w-1/2"></div>
                <div class="h-7 bg-slate-200 rounded-xl w-24"></div>
              </div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 animate-pulse">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-slate-200 rounded-full w-36"></div>
                <div class="h-5 bg-slate-200 rounded-full w-24"></div>
              </div>
              <div class="h-5 bg-slate-200 rounded w-3/4"></div>
              <div class="space-y-1.5">
                <div class="h-3 bg-slate-100 rounded w-full"></div>
                <div class="h-3 bg-slate-100 rounded w-5/6"></div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                <div class="h-3 bg-slate-200 rounded w-28"></div>
                <div class="h-3 bg-slate-200 rounded w-24"></div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                <div class="h-3 bg-slate-200 rounded w-1/2"></div>
                <div class="h-7 bg-slate-200 rounded-xl w-24"></div>
              </div>
            </div>
            <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4 animate-pulse">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-slate-200 rounded-full w-44"></div>
                <div class="h-5 bg-slate-200 rounded-full w-24"></div>
              </div>
              <div class="h-5 bg-slate-200 rounded w-1/2"></div>
              <div class="space-y-1.5">
                <div class="h-3 bg-slate-100 rounded w-full"></div>
                <div class="h-3 bg-slate-100 rounded w-3/4"></div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                <div class="h-3 bg-slate-200 rounded w-36"></div>
                <div class="h-3 bg-slate-200 rounded w-24"></div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between">
                <div class="h-3 bg-slate-200 rounded w-1/2"></div>
                <div class="h-7 bg-slate-200 rounded-xl w-24"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 3: AT-RISK STUDENTS                                 -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="analytics-panel-at-risk" class="hidden space-y-4">
          <!-- Warning Banner -->
          <div class="rounded-2xl p-4 sm:p-5 flex items-start gap-3.5 bg-amber-50/90 border border-amber-200 shadow-xs">
            <div class="w-9 h-9 rounded-xl bg-amber-100 flex items-center justify-center shrink-0 text-amber-700">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </div>
            <div class="flex-1">
              <div class="flex items-center justify-between flex-wrap gap-2">
                <h4 class="font-black text-sm text-amber-900" id="at-risk-header-title">Students Flagged by Scikit-Learn Model</h4>
                <span class="text-xs px-2.5 py-0.5 rounded-full font-bold bg-amber-200 text-amber-900">Proactive Early Intervention</span>
              </div>
              <p class="text-xs text-amber-800/90 mt-1 leading-relaxed">
                Risk probability calculated using <strong>RandomForestClassifier</strong> evaluating absence velocity, consecutive unexcused streaks, day-of-week irregularity, and lack of valid medical excuse documentation.
              </p>
            </div>
          </div>

          <!-- Filter & Action Bar for At-Risk Table -->
          <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Filter Risk:</span>
              <button type="button" class="risk-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white transition cursor-pointer" onclick="filterAtRiskLevel('all', this)">All Levels</button>
              <button type="button" class="risk-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition cursor-pointer" onclick="filterAtRiskLevel('high', this)">High Risk Only</button>
              <button type="button" class="risk-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition cursor-pointer" onclick="filterAtRiskLevel('moderate', this)">Moderate Risk</button>
            </div>
            <button type="button" class="btn btn-secondary btn-sm flex items-center gap-2 font-bold cursor-pointer" onclick="exportAtRiskCSV()">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
              <span>Export Risk List (CSV)</span>
            </button>
          </div>

          <!-- At-Risk Table -->
          <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs border-collapse">
                <thead>
                  <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                    <th scope="col" class="py-3.5 px-4">Student Name</th>
                    <th scope="col" class="py-3.5 px-4">Section</th>
                    <th scope="col" class="py-3.5 px-4">Attendance Rate</th>
                    <th scope="col" class="py-3.5 px-4">Primary Risk Factor</th>
                    <th scope="col" class="py-3.5 px-4">ML Risk Score</th>
                    <th scope="col" class="py-3.5 px-4 text-right">Intervention Action</th>
                  </tr>
                </thead>
                <tbody id="at-risk-table-body" class="divide-y divide-slate-100">
                  <tr class="animate-pulse">
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-28 mb-1"></div><div class="h-2 bg-slate-100 rounded w-14"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-24"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-12 mb-1"></div><div class="h-2 bg-slate-100 rounded w-20"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-36"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-16 mb-1"></div><div class="h-1 bg-slate-200 rounded-full w-24"></div></td>
                    <td class="py-3.5 px-4 text-right"><div class="h-7 bg-slate-200 rounded-lg w-24 inline-block"></div></td>
                  </tr>
                  <tr class="animate-pulse">
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-32 mb-1"></div><div class="h-2 bg-slate-100 rounded w-14"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-20"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-12 mb-1"></div><div class="h-2 bg-slate-100 rounded w-20"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-40"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-16 mb-1"></div><div class="h-1 bg-slate-200 rounded-full w-24"></div></td>
                    <td class="py-3.5 px-4 text-right"><div class="h-7 bg-slate-200 rounded-lg w-24 inline-block"></div></td>
                  </tr>
                  <tr class="animate-pulse">
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-24 mb-1"></div><div class="h-2 bg-slate-100 rounded w-14"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-24"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-12 mb-1"></div><div class="h-2 bg-slate-100 rounded w-20"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-32"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-16 mb-1"></div><div class="h-1 bg-slate-200 rounded-full w-24"></div></td>
                    <td class="py-3.5 px-4 text-right"><div class="h-7 bg-slate-200 rounded-lg w-24 inline-block"></div></td>
                  </tr>
                  <tr class="animate-pulse">
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-36 mb-1"></div><div class="h-2 bg-slate-100 rounded w-14"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-20"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-12 mb-1"></div><div class="h-2 bg-slate-100 rounded w-20"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-28"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-16 mb-1"></div><div class="h-1 bg-slate-200 rounded-full w-24"></div></td>
                    <td class="py-3.5 px-4 text-right"><div class="h-7 bg-slate-200 rounded-lg w-24 inline-block"></div></td>
                  </tr>
                  <tr class="animate-pulse">
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-28 mb-1"></div><div class="h-2 bg-slate-100 rounded w-14"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-24"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3.5 bg-slate-200 rounded w-12 mb-1"></div><div class="h-2 bg-slate-100 rounded w-20"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-36"></div></td>
                    <td class="py-3.5 px-4"><div class="h-3 bg-slate-200 rounded w-16 mb-1"></div><div class="h-1 bg-slate-200 rounded-full w-24"></div></td>
                    <td class="py-3.5 px-4 text-right"><div class="h-7 bg-slate-200 rounded-lg w-24 inline-block"></div></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- TAB 4: BEHAVIORAL CLUSTERS (K-MEANS)                    -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div id="analytics-panel-clusters" class="hidden space-y-4">
          <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
              <h3 class="text-lg font-bold text-slate-900">Unsupervised Student Behavioral Clusters (K-Means)</h3>
              <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Discovered attendance archetypes grouped by multidimensional feature scaling</p>
            </div>
            <span class="text-xs font-bold px-3 py-1.5 rounded-xl bg-purple-50 text-purple-700 border border-purple-200">
              k = 4 Clusters Evaluated
            </span>
          </div>

          <!-- Cluster Cards Grid -->
          <div id="clusters-container" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3 animate-pulse">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-slate-200 rounded-lg w-28"></div>
                <div class="h-3 bg-slate-200 rounded w-24"></div>
              </div>
              <div class="h-4 bg-slate-200 rounded w-1/2"></div>
              <div class="space-y-1">
                <div class="h-3 bg-slate-100 rounded w-full"></div>
                <div class="h-3 bg-slate-100 rounded w-4/5"></div>
              </div>
              <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                <div class="p-2 rounded-lg bg-slate-50 space-y-1">
                  <div class="h-2.5 bg-slate-200 rounded w-16"></div>
                  <div class="h-4 bg-slate-200 rounded w-10"></div>
                </div>
                <div class="p-2 rounded-lg bg-slate-50 space-y-1">
                  <div class="h-2.5 bg-slate-200 rounded w-16"></div>
                  <div class="h-4 bg-slate-200 rounded w-10"></div>
                </div>
              </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3 animate-pulse">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-slate-200 rounded-lg w-28"></div>
                <div class="h-3 bg-slate-200 rounded w-24"></div>
              </div>
              <div class="h-4 bg-slate-200 rounded w-1/2"></div>
              <div class="space-y-1">
                <div class="h-3 bg-slate-100 rounded w-full"></div>
                <div class="h-3 bg-slate-100 rounded w-4/5"></div>
              </div>
              <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                <div class="p-2 rounded-lg bg-slate-50 space-y-1">
                  <div class="h-2.5 bg-slate-200 rounded w-16"></div>
                  <div class="h-4 bg-slate-200 rounded w-10"></div>
                </div>
                <div class="p-2 rounded-lg bg-slate-50 space-y-1">
                  <div class="h-2.5 bg-slate-200 rounded w-16"></div>
                  <div class="h-4 bg-slate-200 rounded w-10"></div>
                </div>
              </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3 animate-pulse">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-slate-200 rounded-lg w-28"></div>
                <div class="h-3 bg-slate-200 rounded w-24"></div>
              </div>
              <div class="h-4 bg-slate-200 rounded w-1/2"></div>
              <div class="space-y-1">
                <div class="h-3 bg-slate-100 rounded w-full"></div>
                <div class="h-3 bg-slate-100 rounded w-4/5"></div>
              </div>
              <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                <div class="p-2 rounded-lg bg-slate-50 space-y-1">
                  <div class="h-2.5 bg-slate-200 rounded w-16"></div>
                  <div class="h-4 bg-slate-200 rounded w-10"></div>
                </div>
                <div class="p-2 rounded-lg bg-slate-50 space-y-1">
                  <div class="h-2.5 bg-slate-200 rounded w-16"></div>
                  <div class="h-4 bg-slate-200 rounded w-10"></div>
                </div>
              </div>
            </div>
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3 animate-pulse">
              <div class="flex items-center justify-between">
                <div class="h-5 bg-slate-200 rounded-lg w-28"></div>
                <div class="h-3 bg-slate-200 rounded w-24"></div>
              </div>
              <div class="h-4 bg-slate-200 rounded w-1/2"></div>
              <div class="space-y-1">
                <div class="h-3 bg-slate-100 rounded w-full"></div>
                <div class="h-3 bg-slate-100 rounded w-4/5"></div>
              </div>
              <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100">
                <div class="p-2 rounded-lg bg-slate-50 space-y-1">
                  <div class="h-2.5 bg-slate-200 rounded w-16"></div>
                  <div class="h-4 bg-slate-200 rounded w-10"></div>
                </div>
                <div class="p-2 rounded-lg bg-slate-50 space-y-1">
                  <div class="h-2.5 bg-slate-200 rounded w-16"></div>
                  <div class="h-4 bg-slate-200 rounded w-10"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>
  </div>

  <!-- Risk Breakdown Modal -->
  <div id="risk-breakdown-modal" class="hidden fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg max-h-[88vh] flex flex-col overflow-hidden transform transition-all animate-in fade-in zoom-in-95 duration-150">
      <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between bg-slate-900 text-white shrink-0">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center font-bold">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
          </div>
          <h3 class="text-sm font-bold text-white tracking-tight" id="modal-student-name">Student Risk Diagnostics</h3>
        </div>
        <button type="button" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer" onclick="closeRiskModal()">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 overscroll-contain">
        <div class="flex items-center justify-between p-3.5 rounded-xl bg-slate-50 border border-slate-200">
          <div>
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Classification Level</div>
            <div class="text-base font-black text-slate-900 mt-0.5" id="modal-risk-level">High Risk</div>
          </div>
          <div class="text-right">
            <div class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Risk Probability</div>
            <div class="text-xl font-black text-rose-600 mt-0.5" id="modal-risk-score">98.5%</div>
          </div>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Key Contributing Features</h4>
          <ul class="space-y-2" id="modal-risk-factors">
            <!-- Factors inserted here -->
          </ul>
        </div>

        <div>
          <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Recommended Intervention Plan</h4>
          <div class="p-3.5 rounded-xl bg-blue-50/80 border border-blue-200 text-xs font-semibold text-blue-950 leading-relaxed" id="modal-recommended-action">
            Immediate counselor phone conference with registered parent/guardian.
          </div>
        </div>
      </div>
      <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-end gap-2 shrink-0">
        <button type="button" class="btn btn-secondary btn-sm font-bold cursor-pointer" onclick="closeRiskModal()">Close</button>
        <button type="button" class="btn btn-primary btn-sm font-bold flex items-center gap-1.5 cursor-pointer" id="modal-btn-notify-parent" onclick="executeModalNotifyParent()">
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          <span>Dispatch Parent Email Alert</span>
        </button>
      </div>
    </div>
  </div>

  <!-- Pattern Action Execution Confirmation Modal -->
  <div id="pattern-action-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-xs p-4 hidden">
    <div class="bg-white rounded-2xl max-w-2xl w-full max-h-[88vh] flex flex-col overflow-hidden shadow-2xl border border-slate-200 animate-in fade-in zoom-in-95 duration-150">
      <div class="px-6 py-4 bg-slate-900 text-white flex items-center justify-between shrink-0 border-b border-slate-800">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 rounded-xl bg-teal-500/20 text-teal-400 border border-teal-500/30 flex items-center justify-center font-bold shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <h3 class="text-sm sm:text-base font-bold text-white tracking-tight" id="pam-title">AI Intervention Plan Executed</h3>
            <p class="text-[11px] text-slate-300 mt-0.5" id="pam-subtitle">Real database alerts dispatched and recorded in parent_alerts table</p>
          </div>
        </div>
        <button type="button" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition cursor-pointer" onclick="closePatternActionModal()">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <div class="p-5 sm:p-6 space-y-4 overflow-y-auto flex-1 overscroll-contain">
        <!-- Summary Stats Card -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div class="p-3 rounded-xl bg-teal-50 border border-teal-200 text-center">
            <div class="text-[10px] font-bold text-teal-700 uppercase tracking-wider">Targeted Students</div>
            <div class="text-xl font-black text-teal-900 mt-0.5" id="pam-student-count">0</div>
          </div>
          <div class="p-3 rounded-xl bg-indigo-50 border border-indigo-200 text-center">
            <div class="text-[10px] font-bold text-indigo-700 uppercase tracking-wider">Alerts Dispatched</div>
            <div class="text-xl font-black text-indigo-900 mt-0.5" id="pam-alerts-count">0</div>
          </div>
          <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-center col-span-2 sm:col-span-1">
            <div class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Audit Log Status</div>
            <div class="text-xs font-black text-emerald-700 mt-1.5 flex items-center justify-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>Logged (MySQL)</span>
            </div>
          </div>
        </div>

        <!-- Description Box -->
        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-700 leading-relaxed" id="pam-description">
          Automated action dispatched.
        </div>

        <!-- Affected Students List Table -->
        <div>
          <div class="flex items-center justify-between mb-2">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500">Targeted Student Cohort (From Database)</h4>
            <span class="text-[11px] text-slate-400 font-semibold" id="pam-cohort-label">Live SQL Match</span>
          </div>
          <div class="rounded-xl border border-slate-200 overflow-hidden">
            <div class="max-h-[220px] overflow-y-auto">
              <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold text-slate-500 sticky top-0 bg-slate-50 z-10">
                  <tr>
                    <th class="py-2.5 px-3">Student Name</th>
                    <th class="py-2.5 px-3">Year &amp; Section</th>
                    <th class="py-2.5 px-3">Parent Email</th>
                    <th class="py-2.5 px-3 text-right">Trigger Absences</th>
                  </tr>
                </thead>
                <tbody id="pam-students-tbody" class="divide-y divide-slate-100 bg-white">
                  <!-- Students inserted here -->
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <div class="px-6 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between shrink-0">
        <span class="text-[11px] text-slate-500 font-medium flex items-center gap-1.5" id="pam-applied-time">
          <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          <span>Executed just now</span>
        </span>
        <button type="button" class="btn btn-primary btn-sm font-bold px-4 cursor-pointer" onclick="closePatternActionModal()">
          Done
        </button>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/../partials/modal.php'; ?>
  <?php include __DIR__ . '/../partials/flash.php'; ?>
  <?php $page_js = '<script src="../../../assets/js/analytics.js"></script>'; ?>
  <?php include __DIR__ . '/../partials/footer.php'; ?>

  <script>
    if (typeof APP !== 'undefined' && APP.highlightNav) {
      APP.highlightNav('analytics');
    }
  </script>
</body>
</html>
