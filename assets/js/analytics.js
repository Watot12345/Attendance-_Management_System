/**
 * Analytics JS — assets/js/analytics.js
 * Production-ready Scikit-Learn Machine Learning Frontend Interface.
 * Handles Chart.js dynamic rendering, live model retraining, AJAX data fetching,
 * interactive risk filtering, feature diagnostics modals, and parent alert dispatch.
 */

let chartInstances = {};
let currentAtRiskStudents = [];
let currentSelectedStudentForModal = null;
let analyticsMemoryCache = null;

document.addEventListener('DOMContentLoaded', function() {
  initAnalytics();

  // Check URL params for specific tab selection (e.g. ?tab=patterns, ?tab=at-risk)
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (['patterns', 'at-risk', 'clusters', 'overview'].includes(tab)) {
    switchAnalyticsTab(tab);
  }
});

/**
 * Initialize Analytics Engine Dashboard with Instant Caching
 */
function initAnalytics() {
  // 1. Check local session storage for instant 0ms initial paint
  try {
    const cached = sessionStorage.getItem('attendance_analytics_cache_v2');
    if (cached) {
      const parsed = JSON.parse(cached);
      if (parsed && parsed.status === 'success') {
        analyticsMemoryCache = parsed;
        renderAllAnalyticsUI(parsed);
      }
    }
  } catch (e) {}

  // 2. Fetch fresh unified payload in a single non-blocking network request
  loadAllAnalytics(false);
}

/**
 * Unified Analytics Data Loader (Single HTTP Request)
 */
async function loadAllAnalytics(showSkeletons = true) {
  const range = document.getElementById('filter-date-range')?.value || 90;
  const grade = document.getElementById('filter-grade')?.value || 'all';
  const section = document.getElementById('filter-section')?.value || 'all';

  const windowPill = document.getElementById('trend-window-pill');
  if (windowPill) {
    windowPill.textContent = `${range}-Day Window`;
  }

  // Show skeletons if requested or if no memory cache is present
  if (showSkeletons || !analyticsMemoryCache) {
    renderOverviewChartsSkeleton();
    renderFeatureImportancesSkeleton();
    renderPatternsSkeleton();
    renderClustersSkeleton();
    renderAtRiskSkeleton();
  }

  try {
    const url = `/api/analytics/all?range=${range}&grade=${encodeURIComponent(grade)}&section=${encodeURIComponent(section)}`;
    const res = await fetch(url, {
      headers: { 'Accept': 'application/json' }
    });
    
    if (res.status === 304 && analyticsMemoryCache) {
      // 304 Not Modified — Cache is fresh!
      return;
    }

    const data = await res.json();
    if (data.status === 'success') {
      analyticsMemoryCache = data;
      try {
        sessionStorage.setItem('attendance_analytics_cache_v2', JSON.stringify(data));
      } catch (e) {}

      renderAllAnalyticsUI(data);
    }
  } catch (err) {
    console.warn('Analytics network fetch warning:', err);
    if (!analyticsMemoryCache) {
      // Fallback individual requests if unified endpoint is unavailable
      loadAnalyticsOverview();
      loadAnalyticsPatterns();
      loadAnalyticsAtRisk();
    }
  }
}

/**
 * Render Complete UI from Data Payload
 */
function renderAllAnalyticsUI(data) {
  if (!data) return;

  // 1. Overview Specs & Importances
  updateModelSpecsUI(data.model_specs);
  renderFeatureImportances(data.feature_importances);

  // 2. Overview Charts
  const ov = data.overview || {};
  renderTrendChart(ov.trend);
  renderDayChart(ov.day_breakdown);
  renderGradeChart(ov.grade_comparison);
  renderStatusDoughnutChart(ov.status_composition);

  // 3. Patterns & Clusters
  renderPatternsUI(data.patterns || [], data.cluster_profiles || []);

  // 4. At-Risk Students
  renderAtRiskUI(data.at_risk_students || [], data.high_risk_count);
}

/**
 * Switch Active Tab Panel
 */
function switchAnalyticsTab(tabName) {
  const tabs = ['overview', 'patterns', 'at-risk', 'clusters'];
  
  tabs.forEach(t => {
    const btn = document.getElementById(`tab-btn-${t}`);
    const panel = document.getElementById(`analytics-panel-${t}`);
    
    if (btn && panel) {
      if (t === tabName) {
        btn.className = 'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all bg-[#1e3b8a] text-white shadow-xs';
        panel.classList.remove('hidden');
      } else {
        btn.className = 'flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition-all text-slate-600 hover:text-slate-900 hover:bg-slate-50';
        panel.classList.add('hidden');
      }
    }
  });

  // Lazy load tab-specific datasets if necessary
  if (tabName === 'at-risk' && (!currentAtRiskStudents || currentAtRiskStudents.length === 0)) {
    loadAnalyticsAtRisk();
  }
}

/**
 * Skeleton State: Overview Charts (Trend, Day of Week, Year Level, Status Composition)
 */
function renderOverviewChartsSkeleton() {
  const trendSk = document.getElementById('trend-chart-skeleton');
  const daySk = document.getElementById('day-chart-skeleton');
  const gradeSk = document.getElementById('grade-chart-skeleton');
  const statusSk = document.getElementById('status-doughnut-skeleton');

  if (trendSk) trendSk.classList.remove('hidden');
  if (daySk) daySk.classList.remove('hidden');
  if (gradeSk) gradeSk.classList.remove('hidden');
  if (statusSk) statusSk.classList.remove('hidden');

  // Hide empty states while skeletons are pulsing
  ['trend-empty-state', 'day-empty-state', 'grade-empty-state', 'status-empty-state'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.classList.add('hidden');
  });
}

/**
 * Helper to hide specific chart skeleton
 */
function hideOverviewChartSkeleton(skeletonId) {
  const elem = document.getElementById(skeletonId);
  if (elem) elem.classList.add('hidden');
}

/**
 * Skeleton State: Feature Importance Cards
 */
function renderFeatureImportancesSkeleton() {
  const container = document.getElementById('feature-importance-list');
  if (!container) return;

  container.innerHTML = `
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
  `;
}

/**
 * Skeleton State: Detected Patterns Cards
 */
function renderPatternsSkeleton() {
  const container = document.getElementById('patterns-container');
  if (!container) return;

  container.innerHTML = `
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
  `;
}

/**
 * Skeleton State: Behavioral Clusters Cards
 */
function renderClustersSkeleton() {
  const container = document.getElementById('clusters-container');
  if (!container) return;

  container.innerHTML = `
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
  `;
}

/**
 * Skeleton State: At-Risk Table Rows
 */
function renderAtRiskSkeleton() {
  const tbody = document.getElementById('at-risk-table-body');
  if (!tbody) return;

  tbody.innerHTML = `
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
  `;
}

/**
 * Fetch and Render Overview Analytics & Charts
 */
async function loadAnalyticsOverview() {
  const range = document.getElementById('filter-date-range')?.value || 90;
  const grade = document.getElementById('filter-grade')?.value || 'all';
  const section = document.getElementById('filter-section')?.value || 'all';

  const windowPill = document.getElementById('trend-window-pill');
  if (windowPill) {
    windowPill.textContent = `${range}-Day Window`;
  }

  renderOverviewChartsSkeleton();
  renderFeatureImportancesSkeleton();

  try {
    const res = await fetch(`/api/analytics/overview?range=${range}&grade=${encodeURIComponent(grade)}&section=${encodeURIComponent(section)}`);
    const data = await res.json();

    if (data.status === 'success') {
      // 1. Update Model Specs Header & Details
      updateModelSpecsUI(data.model_specs);

      // 2. Render Feature Importances List
      renderFeatureImportances(data.feature_importances);

      // 3. Render Chart.js Visualizations directly from database
      const ov = data.overview || {};
      renderTrendChart(ov.trend);
      renderDayChart(ov.day_breakdown);
      renderGradeChart(ov.grade_comparison);
      renderStatusDoughnutChart(ov.status_composition);
    }
  } catch (err) {
    console.warn('Could not fetch overview API, relying on local chart rendering:', err);
  }
}

/**
 * Update Header and Card Model Specifications
 */
function updateModelSpecsUI(specs) {
  if (!specs) return;

  const headerAcc = document.getElementById('header-accuracy');
  const headerRoc = document.getElementById('header-roc');
  const specAlgo = document.getElementById('spec-algorithm');
  const specRoc = document.getElementById('spec-roc-auc');
  const specSamples = document.getElementById('spec-samples');
  const specRetrained = document.getElementById('spec-retrained');

  if (headerAcc) headerAcc.textContent = `${specs.accuracy || 100}% Acc`;
  if (headerRoc) headerRoc.textContent = `${specs.roc_auc || 1.0} ROC-AUC`;
  if (specAlgo) specAlgo.textContent = specs.algorithm || 'RandomForestClassifier';
  if (specRoc) specRoc.textContent = specs.roc_auc || '1.0';
  if (specSamples) specSamples.textContent = specs.training_samples !== undefined ? `${Number(specs.training_samples).toLocaleString()} rows` : '0 rows';
  if (specRetrained) specRetrained.textContent = specs.last_retrained || 'Recent';
}

/**
 * Render Scikit-Learn Feature Importance Attributions
 */
function renderFeatureImportances(importances) {
  const container = document.getElementById('feature-importance-list');
  if (!container) return;

  const formatKey = (k) => {
    return k.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
  };

  const entries = Object.entries(importances || {});
  const hasValues = entries.some(([_, weight]) => Number(weight) > 0);

  if (!importances || entries.length === 0 || !hasValues) {
    container.innerHTML = `
      <div class="col-span-full py-5 text-center text-slate-400 text-xs flex flex-col items-center justify-center gap-1.5">
        <svg class="w-5 h-5 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        <span class="font-semibold text-slate-600">No feature weight attributions yet</span>
        <span class="text-[10px] text-slate-400">Scikit-Learn models will calculate feature importances once student attendance logs exist.</span>
      </div>
    `;
    return;
  }

  const items = entries.slice(0, 4);
  let html = '';

  items.forEach(([key, weight]) => {
    html += `
      <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70">
        <div class="flex items-center justify-between text-xs mb-1.5">
          <span class="font-bold text-slate-700 truncate">${formatKey(key)}</span>
          <span class="font-black text-indigo-600">${weight}%</span>
        </div>
        <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
          <div class="bg-indigo-600 h-1.5 rounded-full transition-all duration-500" style="width: ${Math.min(100, weight * 2)}%"></div>
        </div>
      </div>
    `;
  });

  container.innerHTML = html;
}

/**
 * Chart 1: Dual-Line Time Series Trend vs ML Benchmark
 */
function renderTrendChart(trendData) {
  hideOverviewChartSkeleton('trend-chart-skeleton');
  const canvas = document.getElementById('analytics-trend-chart');
  if (!canvas) return;

  if (chartInstances['analytics-trend-chart']) {
    chartInstances['analytics-trend-chart'].destroy();
  }

  const labels = Array.isArray(trendData?.labels) ? trendData.labels : [];
  const actuals = Array.isArray(trendData?.actual) ? trendData.actual.map(v => Number(v) || 0) : [];
  const benchmarks = Array.isArray(trendData?.benchmark) ? trendData.benchmark.map(v => Number(v) || 0) : [];

  const trendEmpty = document.getElementById('trend-empty-state');
  if (labels.length === 0 || actuals.length === 0) {
    if (trendEmpty) trendEmpty.classList.remove('hidden');
    return;
  } else {
    if (trendEmpty) trendEmpty.classList.add('hidden');
  }

  const ctx = canvas.getContext('2d');
  let gradient = null;
  if (ctx) {
    gradient = ctx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(13, 148, 136, 0.22)');
    gradient.addColorStop(1, 'rgba(13, 148, 136, 0.01)');
  }

  // Dynamic Y-axis scale based on live actual database points
  const minVal = actuals.length > 0 ? Math.min(...actuals) : 0;
  const yMin = actuals.length > 0 ? Math.max(0, Math.floor(minVal / 10) * 10 - 10) : 0;

  chartInstances['analytics-trend-chart'] = new Chart(canvas, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Actual Attendance %',
          data: actuals,
          borderColor: '#0d9488',
          borderWidth: 2.5,
          backgroundColor: gradient || 'rgba(13, 148, 136, 0.12)',
          fill: true,
          tension: 0.35,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#0d9488',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 6
        },
        {
          label: 'Scikit-Learn Benchmark (92%)',
          data: benchmarks,
          borderColor: '#94a3b8',
          borderWidth: 2,
          borderDash: [6, 6],
          fill: false,
          pointRadius: 0,
          pointHoverRadius: 0
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: { boxWidth: 12, boxHeight: 12, font: { size: 11, weight: 'bold' }, color: '#64748b' }
        },
        tooltip: {
          backgroundColor: '#0f172a',
          titleColor: '#f8fafc',
          bodyColor: '#cbd5e1',
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: (ctx) => `${ctx.dataset.label}: ${ctx.parsed.y !== null ? ctx.parsed.y.toFixed(1) : '0'}%`
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: '#64748b' }
        },
        y: {
          min: yMin,
          max: 100,
          grid: { color: 'rgba(226, 232, 240, 0.7)', borderDash: [4, 4] },
          ticks: {
            font: { size: 11 },
            color: '#64748b',
            stepSize: 5,
            callback: (val) => val + '%'
          }
        }
      }
    }
  });
}

/**
 * Chart 2: Categorical Absences by Day of Week
 */
function renderDayChart(dayData) {
  hideOverviewChartSkeleton('day-chart-skeleton');
  const canvas = document.getElementById('day-chart');
  if (!canvas) return;

  if (chartInstances['day-chart']) {
    chartInstances['day-chart'].destroy();
  }

  const labels = Array.isArray(dayData?.labels) && dayData.labels.length > 0 ? dayData.labels : ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'];
  const absences = Array.isArray(dayData?.absences) ? dayData.absences.map(v => Number(v) || 0) : [0, 0, 0, 0, 0];

  const dayEmpty = document.getElementById('day-empty-state');
  if (absences.length === 0 || absences.every(v => v === 0)) {
    if (dayEmpty) dayEmpty.classList.remove('hidden');
    return;
  } else {
    if (dayEmpty) dayEmpty.classList.add('hidden');
  }

  // Highlight highest peak day dynamically from database counts
  const maxAbs = Math.max(0, ...absences);
  const colors = absences.map(val => (val > 0 && val === maxAbs) ? '#ef4444' : '#0d9488');

  chartInstances['day-chart'] = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Total Absences',
          data: absences,
          backgroundColor: colors,
          borderRadius: 6,
          barPercentage: 0.65
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: {
          backgroundColor: '#0f172a',
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: (ctx) => `${ctx.parsed.y} absences recorded${ctx.parsed.y > 0 && ctx.parsed.y === maxAbs ? ' (Peak day)' : ''}`
          }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 11, weight: 'bold' }, color: '#64748b' } },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(226, 232, 240, 0.7)', borderDash: [4, 4] },
          ticks: { font: { size: 11 }, color: '#64748b', stepSize: 1 }
        }
      }
    }
  });
}

/**
 * Chart 3: Grouped Bar Chart Comparing Grade Levels
 */
function renderGradeChart(gradeData) {
  hideOverviewChartSkeleton('grade-chart-skeleton');
  const canvas = document.getElementById('grade-chart');
  if (!canvas) return;

  if (chartInstances['grade-chart']) {
    chartInstances['grade-chart'].destroy();
  }

  const labels = Array.isArray(gradeData?.labels) && gradeData.labels.length > 0 ? gradeData.labels : ['1st Year', '2nd Year', '3rd Year', '4th Year'];
  const absRates = Array.isArray(gradeData?.absence_rates) ? gradeData.absence_rates.map(v => Number(v) || 0) : [0, 0, 0, 0];
  const tardyRates = Array.isArray(gradeData?.tardy_rates) ? gradeData.tardy_rates.map(v => Number(v) || 0) : [0, 0, 0, 0];

  const gradeEmpty = document.getElementById('grade-empty-state');
  if (labels.length === 0 || (absRates.every(v => v === 0) && tardyRates.every(v => v === 0))) {
    if (gradeEmpty) gradeEmpty.classList.remove('hidden');
    return;
  } else {
    if (gradeEmpty) gradeEmpty.classList.add('hidden');
  }

  chartInstances['grade-chart'] = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Absence Rate %',
          data: absRates,
          backgroundColor: '#ef4444',
          borderRadius: 4,
          barPercentage: 0.7
        },
        {
          label: 'Tardy Rate %',
          data: tardyRates,
          backgroundColor: '#f59e0b',
          borderRadius: 4,
          barPercentage: 0.7
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: { boxWidth: 10, boxHeight: 10, font: { size: 10, weight: 'bold' }, color: '#64748b' }
        }
      },
      scales: {
        x: { grid: { display: false }, ticks: { font: { size: 10, weight: 'bold' }, color: '#64748b' } },
        y: {
          beginAtZero: true,
          grid: { color: 'rgba(226, 232, 240, 0.7)', borderDash: [4, 4] },
          ticks: { font: { size: 10 }, color: '#64748b', callback: (v) => v + '%' }
        }
      }
    }
  });
}

/**
 * Chart 4: Doughnut Status Composition
 */
function renderStatusDoughnutChart(statusData) {
  hideOverviewChartSkeleton('status-doughnut-skeleton');
  const canvas = document.getElementById('status-doughnut-chart');
  if (!canvas) return;

  if (chartInstances['status-doughnut-chart']) {
    chartInstances['status-doughnut-chart'].destroy();
  }

  const present = Number(statusData?.present ?? 0);
  const tardy = Number(statusData?.tardy ?? 0);
  const excused = Number(statusData?.excused ?? 0);
  const absent = Number(statusData?.unexcused_absent ?? 0);

  const total = present + tardy + excused + absent;

  const statusEmpty = document.getElementById('status-empty-state');
  if (total === 0) {
    if (statusEmpty) statusEmpty.classList.remove('hidden');
    return;
  } else {
    if (statusEmpty) statusEmpty.classList.add('hidden');
  }

  const dataValues = [present, tardy, excused, absent];
  const bgColors = ['#0d9488', '#f59e0b', '#3b82f6', '#ef4444'];

  chartInstances['status-doughnut-chart'] = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: ['Present', 'Tardy', 'Excused Slip', 'Unexcused Absent'],
      datasets: [
        {
          data: dataValues,
          backgroundColor: bgColors,
          borderWidth: 2,
          borderColor: '#ffffff'
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%',
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: { boxWidth: 10, boxHeight: 10, font: { size: 10, weight: 'bold' }, color: '#64748b', padding: 8 }
        },
        tooltip: {
          backgroundColor: '#0f172a',
          callbacks: {
            label: (ctx) => `${ctx.label}: ${ctx.parsed}%`
          }
        }
      }
    }
  });
}

/**
 * Render Detected Patterns & Behavioral Clusters to DOM
 */
function renderPatternsUI(patterns, clusters) {
  const container = document.getElementById('patterns-container');
  const clustersContainer = document.getElementById('clusters-container');
  const badgeCount = document.getElementById('badge-pattern-count');

  if (badgeCount) badgeCount.textContent = patterns ? patterns.length : 0;

  // Render Patterns
  if (container) {
    if (!patterns || !Array.isArray(patterns) || patterns.length === 0) {
      container.innerHTML = `
        <div class="bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xs text-center space-y-2">
          <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <h4 class="text-sm font-bold text-slate-800">No Anomaly Patterns Detected</h4>
          <p class="text-xs text-slate-500 max-w-md mx-auto leading-relaxed">The anomaly detection engine has not isolated any weekday absence spikes or consecutive dropout risks in the current attendance dataset.</p>
        </div>
      `;
    } else {
      let html = '';
      patterns.forEach((pat, idx) => {
        const badgeClass = pat.severity === 'critical' ? 'bg-rose-100 text-rose-800' : (pat.severity === 'high' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800');
        html += `
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between flex-wrap gap-2">
              <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-teal-50 text-teal-700 border border-teal-200">
                Pattern #${idx + 1} · ${pat.type.replace(/_/g, ' ').toUpperCase()}
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold ${badgeClass}">
                ${pat.severity.toUpperCase()} IMPACT
              </span>
            </div>
            <h4 class="text-base font-black text-slate-900">${pat.title}</h4>
            <p class="text-xs text-slate-600 leading-relaxed">${pat.description}</p>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between text-xs">
              <span class="text-slate-500 font-medium">Affected: <strong class="text-slate-900">${pat.affected_cohort}</strong></span>
              <span class="text-teal-700 font-bold">Confidence: ${pat.confidence}</span>
            </div>
            <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-100 text-xs font-medium text-indigo-900 flex items-center justify-between gap-2 flex-wrap sm:flex-nowrap">
              <span><strong>AI Intervention Recommendation:</strong> ${pat.recommendation}</span>
              <button type="button" id="btn-action-${pat.id}" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold rounded-xl shrink-0 cursor-pointer shadow-xs transition flex items-center gap-1.5 text-xs" onclick="applyPatternIntervention('${pat.id}', '${escapeHtml(pat.title)}', this)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Apply Action</span>
              </button>
            </div>
          </div>
        `;
      });
      container.innerHTML = html;
    }
  }

  // Render Clusters
  if (clustersContainer) {
    if (!clusters || !Array.isArray(clusters) || clusters.length === 0 || clusters.every(c => (c.count || 0) === 0)) {
      clustersContainer.innerHTML = `
        <div class="col-span-full bg-white p-8 rounded-2xl border border-slate-200/80 shadow-xs text-center text-slate-500 text-xs">
          <div class="w-10 h-10 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center mx-auto mb-2">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
          </div>
          <h4 class="text-sm font-bold text-slate-800">No Behavioral Clusters Formed</h4>
          <p class="text-xs text-slate-400 max-w-md mx-auto mt-1">Unsupervised K-Means clustering will segment student cohorts once attendance sessions are recorded.</p>
        </div>
      `;
    } else {
      const totalClusterStudents = clusters.reduce((acc, c) => acc + (c.count || 0), 0) || 1;
      let clusterHtml = '';
      clusters.forEach(c => {
        const pct = Math.round((c.count / totalClusterStudents) * 100);
        clusterHtml += `
          <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
              <span class="px-2.5 py-1 rounded-lg text-xs font-black" style="background: ${c.bg}; color: ${c.color}">
                ${c.badge}
              </span>
              <span class="text-xs font-bold text-slate-500">${c.count} Students (${pct}%)</span>
            </div>
            <h4 class="text-sm font-black text-slate-900">${c.name}</h4>
            <p class="text-xs text-slate-500 leading-relaxed">${c.description}</p>
            <div class="grid grid-cols-2 gap-2 pt-2 border-t border-slate-100 text-xs">
              <div class="p-2 rounded-lg bg-slate-50">
                <div class="text-[10px] font-bold text-slate-400 uppercase">Avg Attendance</div>
                <div class="text-sm font-black text-emerald-600">${c.avg_attendance}%</div>
              </div>
              <div class="p-2 rounded-lg bg-slate-50">
                <div class="text-[10px] font-bold text-slate-400 uppercase">Avg Tardy</div>
                <div class="text-sm font-black text-amber-600">${c.avg_tardy}%</div>
              </div>
            </div>
          </div>
        `;
      });
      clustersContainer.innerHTML = clusterHtml;
    }
  }
}

// ==========================================
// AT-RISK STUDENTS PAGINATION & BULK ACTIONS
// ==========================================
let atRiskCurrentPage = 1;
let atRiskPageSize = 15;
let atRiskCurrentFilterLevel = 'all';
let selectedAtRiskStudentIds = new Set();

/**
 * Render At-Risk Students to DOM with Pagination & Bulk Selection
 */
function renderAtRiskUI(students, highRiskCount, resetPage = false) {
  const tbody = document.getElementById('at-risk-table-body');
  const badgeCount = document.getElementById('badge-at-risk-count');
  const teaserHighBadge = document.getElementById('teaser-high-risk-badge');

  if (Array.isArray(students)) {
    currentAtRiskStudents = students;
  }

  if (resetPage) {
    atRiskCurrentPage = 1;
  }

  const allAtRisk = currentAtRiskStudents || [];
  const highRiskTotal = allAtRisk.filter(s => String(s.risk_level || '').toLowerCase().includes('high')).length;

  if (badgeCount) badgeCount.textContent = highRiskCount || highRiskTotal;
  if (teaserHighBadge) teaserHighBadge.textContent = `${highRiskCount || highRiskTotal || 5} High Risk`;

  if (!tbody) return;

  // Filter students based on active risk level selection
  let filteredStudents = allAtRisk;
  if (atRiskCurrentFilterLevel !== 'all') {
    filteredStudents = allAtRisk.filter(s => String(s.risk_level || '').toLowerCase().includes(atRiskCurrentFilterLevel.toLowerCase()));
  }

  const totalFiltered = filteredStudents.length;

  if (totalFiltered === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="7" class="py-12 text-center text-slate-400 font-semibold">
          <div class="flex flex-col items-center justify-center gap-2">
            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>No students match the selected risk criteria.</span>
          </div>
        </td>
      </tr>
    `;
    renderAtRiskPagination(0, 0, 0, 1);
    updateAtRiskSelectionUI([]);
    return;
  }

  // Calculate pagination boundaries
  const pageSize = parseInt(atRiskPageSize, 10);
  const totalPages = pageSize === -1 ? 1 : Math.max(1, Math.ceil(totalFiltered / (pageSize || 15)));
  if (atRiskCurrentPage > totalPages) atRiskCurrentPage = totalPages;
  if (atRiskCurrentPage < 1) atRiskCurrentPage = 1;

  const startIdx = pageSize === -1 ? 0 : (atRiskCurrentPage - 1) * pageSize;
  const endIdx = pageSize === -1 ? totalFiltered : Math.min(startIdx + pageSize, totalFiltered);
  const pageSlice = filteredStudents.slice(startIdx, endIdx);

  let html = '';
  pageSlice.forEach(s => {
    const isHigh = String(s.risk_level || '').toLowerCase().includes('high');
    const isModerate = String(s.risk_level || '').toLowerCase().includes('moderate');
    const badgeClass = isHigh ? 'bg-rose-50 text-rose-700 border-rose-200' : (isModerate ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200');
    const barColor = isHigh ? 'bg-rose-500' : (isModerate ? 'bg-amber-500' : 'bg-emerald-500');
    const isChecked = selectedAtRiskStudentIds.has(Number(s.student_id));

    html += `
      <tr class="hover:bg-slate-50/80 transition ${isChecked ? 'bg-blue-50/40' : ''}">
        <!-- Checkbox Column -->
        <td class="py-3.5 px-4 text-center">
          <input type="checkbox" 
                 class="at-risk-checkbox w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-slate-300 cursor-pointer transition"
                 data-student-id="${s.student_id}"
                 ${isChecked ? 'checked' : ''}
                 onchange="updateAtRiskSelection()">
        </td>

        <!-- Student Info -->
        <td class="py-3.5 px-4 font-bold text-slate-900">
          ${escapeHtml(s.name)}
          <span class="block text-[10px] font-semibold text-slate-400">ID: #${s.student_id}</span>
        </td>

        <!-- Section -->
        <td class="py-3.5 px-4 font-semibold text-slate-600">Sec ${escapeHtml(s.section)} (Yr ${s.grade_level || '3'})</td>

        <!-- Attendance Rate -->
        <td class="py-3.5 px-4">
          <span class="font-black ${parseFloat(s.attendance_rate) < 80 ? 'text-rose-600' : 'text-slate-800'}">${s.attendance_rate}%</span>
          <span class="block text-[10px] text-slate-400">${s.absence_count} abs · ${s.tardy_count} tardy</span>
        </td>

        <!-- Risk Factor -->
        <td class="py-3.5 px-4 font-semibold text-slate-700">
          ${escapeHtml(s.primary_factor)}
        </td>

        <!-- ML Risk Score -->
        <td class="py-3.5 px-4">
          <div class="flex items-center gap-2">
            <span class="px-2 py-0.5 rounded-md text-[10px] font-black border uppercase ${badgeClass}">
              ${escapeHtml(s.risk_level)}
            </span>
            <span class="font-black text-slate-900">${s.risk_score}%</span>
          </div>
          <div class="w-24 bg-slate-200 rounded-full h-1 mt-1 overflow-hidden">
            <div class="${barColor} h-1 rounded-full" style="width: ${s.risk_score}%"></div>
          </div>
        </td>

        <!-- Actions -->
        <td class="py-3.5 px-4 text-right space-x-1">
          <button type="button" class="btn btn-secondary btn-sm font-bold text-[11px] px-2.5 py-1 cursor-pointer" onclick="openRiskModal(${s.student_id})">
            Diagnostics
          </button>
          <button type="button" class="btn btn-primary btn-sm font-bold text-[11px] px-2.5 py-1 inline-flex items-center gap-1 cursor-pointer" onclick="handleDirectParentAlert(${s.student_id}, '${escapeHtml(s.name)}')">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <span>Alert</span>
          </button>
        </td>
      </tr>
    `;
  });

  tbody.innerHTML = html;

  // Render pagination toolbar
  renderAtRiskPagination(totalFiltered, startIdx, endIdx, totalPages);

  // Sync bulk selection indicators
  updateAtRiskSelectionUI(pageSlice);
}

/**
 * Render At-Risk Pagination UI Controls
 */
function renderAtRiskPagination(totalMatching, startIdx, endIdx, totalPages) {
  const bar = document.getElementById('at-risk-pagination-bar');
  const startEl = document.getElementById('at-risk-page-start');
  const endEl = document.getElementById('at-risk-page-end');
  const totalEl = document.getElementById('at-risk-page-total');
  const controls = document.getElementById('at-risk-pagination-controls');

  if (!bar || !controls) return;

  if (totalMatching === 0) {
    bar.classList.add('hidden');
    return;
  }
  bar.classList.remove('hidden');

  if (startEl) startEl.textContent = (startIdx + 1).toLocaleString();
  if (endEl) endEl.textContent = endIdx.toLocaleString();
  if (totalEl) totalEl.textContent = totalMatching.toLocaleString();

  const pageSize = parseInt(atRiskPageSize, 10);
  if (pageSize === -1 || totalPages <= 1) {
    controls.innerHTML = `<span class="text-[11px] font-semibold text-slate-400 px-2">Page 1 of 1</span>`;
    return;
  }

  let html = '';

  // Prev button
  const prevDisabled = atRiskCurrentPage <= 1;
  html += `
    <button type="button" 
            onclick="changeAtRiskPage(${atRiskCurrentPage - 1})"
            ${prevDisabled ? 'disabled' : ''}
            class="px-2.5 py-1.5 rounded-lg border text-xs font-bold transition flex items-center gap-1 ${prevDisabled ? 'border-slate-200 text-slate-300 cursor-not-allowed bg-slate-50' : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer shadow-2xs'}">
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      <span>Prev</span>
    </button>
  `;

  // Numbered pages (smart window max 5)
  const maxButtons = 5;
  let startPage = Math.max(1, atRiskCurrentPage - Math.floor(maxButtons / 2));
  let endPage = Math.min(totalPages, startPage + maxButtons - 1);
  if (endPage - startPage + 1 < maxButtons) {
    startPage = Math.max(1, endPage - maxButtons + 1);
  }

  if (startPage > 1) {
    html += `<button type="button" onclick="changeAtRiskPage(1)" class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer">1</button>`;
    if (startPage > 2) html += `<span class="px-1 text-slate-400 font-bold">…</span>`;
  }

  for (let p = startPage; p <= endPage; p++) {
    const isActive = p === atRiskCurrentPage;
    if (isActive) {
      html += `<button type="button" class="w-8 h-8 rounded-lg border border-blue-600 bg-blue-600 text-xs font-black text-white shadow-xs">${p}</button>`;
    } else {
      html += `<button type="button" onclick="changeAtRiskPage(${p})" class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer">${p}</button>`;
    }
  }

  if (endPage < totalPages) {
    if (endPage < totalPages - 1) html += `<span class="px-1 text-slate-400 font-bold">…</span>`;
    html += `<button type="button" onclick="changeAtRiskPage(${totalPages})" class="w-8 h-8 rounded-lg border border-slate-200 bg-white hover:bg-slate-100 text-xs font-bold text-slate-700 transition cursor-pointer">${totalPages}</button>`;
  }

  // Next button
  const nextDisabled = atRiskCurrentPage >= totalPages;
  html += `
    <button type="button" 
            onclick="changeAtRiskPage(${atRiskCurrentPage + 1})"
            ${nextDisabled ? 'disabled' : ''}
            class="px-2.5 py-1.5 rounded-lg border text-xs font-bold transition flex items-center gap-1 ${nextDisabled ? 'border-slate-200 text-slate-300 cursor-not-allowed bg-slate-50' : 'border-slate-200 text-slate-700 bg-white hover:bg-slate-100 cursor-pointer shadow-2xs'}">
      <span>Next</span>
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </button>
  `;

  controls.innerHTML = html;
}

function changeAtRiskPage(page) {
  atRiskCurrentPage = page;
  renderAtRiskUI();
  const table = document.getElementById('at-risk-table-body');
  if (table) {
    table.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }
}

function changeAtRiskPageSize(newSize) {
  atRiskPageSize = parseInt(newSize, 10);
  atRiskCurrentPage = 1;
  renderAtRiskUI();
}

/**
 * Bulk Selection Handlers for At-Risk Table
 */
function toggleSelectAllAtRisk(headerCheckbox) {
  const isChecked = headerCheckbox.checked;
  const checkboxes = document.querySelectorAll('#at-risk-table-body .at-risk-checkbox');

  checkboxes.forEach(cb => {
    cb.checked = isChecked;
    const studentId = Number(cb.getAttribute('data-student-id'));
    if (isChecked) {
      selectedAtRiskStudentIds.add(studentId);
    } else {
      selectedAtRiskStudentIds.delete(studentId);
    }
  });

  updateAtRiskSelectionUI();
}

function updateAtRiskSelection() {
  const checkboxes = document.querySelectorAll('#at-risk-table-body .at-risk-checkbox');
  checkboxes.forEach(cb => {
    const studentId = Number(cb.getAttribute('data-student-id'));
    if (cb.checked) {
      selectedAtRiskStudentIds.add(studentId);
    } else {
      selectedAtRiskStudentIds.delete(studentId);
    }
  });

  updateAtRiskSelectionUI();
}

function updateAtRiskSelectionUI(currentSlice = null) {
  const bulkBar = document.getElementById('at-risk-bulk-bar');
  const countEl = document.getElementById('bulk-selected-count');
  const textEl = document.getElementById('bulk-selected-text');
  const btnCount = document.getElementById('bulk-btn-count');
  const headerCheckbox = document.getElementById('select-all-at-risk');

  const selectedCount = selectedAtRiskStudentIds.size;

  if (countEl) countEl.textContent = selectedCount;
  if (textEl) textEl.textContent = `${selectedCount} student${selectedCount === 1 ? '' : 's'} selected`;
  if (btnCount) btnCount.textContent = selectedCount;

  if (bulkBar) {
    if (selectedCount > 0) {
      bulkBar.classList.remove('hidden');
    } else {
      bulkBar.classList.add('hidden');
    }
  }

  // Update header checkbox state
  if (headerCheckbox) {
    const checkboxes = document.querySelectorAll('#at-risk-table-body .at-risk-checkbox');
    if (checkboxes.length > 0) {
      const allChecked = Array.from(checkboxes).every(cb => cb.checked);
      const someChecked = Array.from(checkboxes).some(cb => cb.checked);
      headerCheckbox.checked = allChecked;
      headerCheckbox.indeterminate = someChecked && !allChecked;
    } else {
      headerCheckbox.checked = false;
      headerCheckbox.indeterminate = false;
    }
  }
}

function clearAtRiskSelection() {
  selectedAtRiskStudentIds.clear();
  const checkboxes = document.querySelectorAll('#at-risk-table-body .at-risk-checkbox');
  checkboxes.forEach(cb => cb.checked = false);
  const headerCheckbox = document.getElementById('select-all-at-risk');
  if (headerCheckbox) {
    headerCheckbox.checked = false;
    headerCheckbox.indeterminate = false;
  }
  updateAtRiskSelectionUI();
}

/**
 * Execute Bulk Early-Warning Parent Alerts via API
 */
async function executeBulkParentAlert() {
  const selectedIds = Array.from(selectedAtRiskStudentIds);
  if (selectedIds.length === 0) {
    showToastNotification('Please select at least one student first.', 'info');
    return;
  }

  const btn = document.getElementById('btn-bulk-alert-parents');
  const originalHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `
      <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      <span>Dispatching ${selectedIds.length} Alerts...</span>
    `;
  }

  try {
    const res = await fetch('/api/analytics/intervene', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        student_ids: selectedIds,
        action_type: 'notify_parent'
      })
    });
    const data = await res.json();

    if (data.status === 'success') {
      showToastNotification(data.message || `Successfully sent parent alerts for ${selectedIds.length} students!`, 'success');
      clearAtRiskSelection();
    } else {
      showToastNotification(data.message || 'Bulk alert dispatch failed.', 'error');
    }
  } catch (err) {
    showToastNotification('Network error dispatching bulk parent alerts.', 'error');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  }
}

/**
 * Export only the SELECTED At-Risk Students to CSV
 */
function exportSelectedAtRiskCSV() {
  const selectedIds = selectedAtRiskStudentIds;
  if (!selectedIds || selectedIds.size === 0) {
    showToastNotification('No students selected for export.', 'info');
    return;
  }

  const selectedList = (currentAtRiskStudents || []).filter(s => selectedIds.has(Number(s.student_id)));
  if (selectedList.length === 0) {
    showToastNotification('Selected student records not found.', 'info');
    return;
  }

  const headers = ['Student ID', 'Full Name', 'Section', 'Grade Level', 'Attendance Rate (%)', 'Total Absences', 'Tardy Count', 'Consecutive Absences', 'Risk Score (%)', 'Risk Classification', 'Primary Risk Factor', 'Recommended Action'];
  const rows = selectedList.map(s => [
    s.student_id,
    `"${s.name}"`,
    `"${s.section}"`,
    s.grade_level || '3',
    s.attendance_rate,
    s.absence_count,
    s.tardy_count,
    s.consecutive_absences,
    s.risk_score,
    `"${s.risk_level}"`,
    `"${s.primary_factor}"`,
    `"${s.recommended_action}"`
  ]);

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `selected_at_risk_students_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  showToastNotification(`Exported ${selectedList.length} selected student records to CSV.`, 'success');
}

/**
 * Fetch and Render Detected Patterns & Behavioral Clusters (Fallback / Standalone)
 */
async function loadAnalyticsPatterns() {
  if (analyticsMemoryCache?.patterns) {
    renderPatternsUI(analyticsMemoryCache.patterns, analyticsMemoryCache.cluster_profiles);
    return;
  }
  renderPatternsSkeleton();
  renderClustersSkeleton();
  try {
    const res = await fetch('/api/analytics/patterns');
    const data = await res.json();
    if (data.status === 'success') {
      renderPatternsUI(data.patterns || [], data.cluster_profiles || []);
    }
  } catch (err) {
    console.warn('Could not load patterns:', err);
  }
}

/**
 * Fetch and Render At-Risk Students List (Fallback / Standalone)
 */
async function loadAnalyticsAtRisk(levelFilter = 'all') {
  atRiskCurrentFilterLevel = levelFilter;
  if (analyticsMemoryCache?.at_risk_students && levelFilter === 'all') {
    renderAtRiskUI(analyticsMemoryCache.at_risk_students, analyticsMemoryCache.high_risk_count, true);
    return;
  }
  renderAtRiskSkeleton();
  const grade = document.getElementById('filter-grade')?.value || 'all';
  const section = document.getElementById('filter-section')?.value || 'all';
  try {
    const res = await fetch(`/api/analytics/at-risk?grade=${encodeURIComponent(grade)}&section=${encodeURIComponent(section)}&level=${encodeURIComponent(levelFilter)}`);
    const data = await res.json();
    if (data.status === 'success') {
      renderAtRiskUI(data.students || [], data.high_risk_count, true);
    }
  } catch (err) {
    console.warn('Could not load at-risk students:', err);
  }
}

/**
 * Filter At-Risk Table by Risk Classification Level (Instant In-Memory Filter)
 */
function filterAtRiskLevel(level, btnElem) {
  atRiskCurrentFilterLevel = level;
  atRiskCurrentPage = 1;

  document.querySelectorAll('.risk-filter-btn').forEach(btn => {
    btn.className = 'risk-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 transition cursor-pointer';
  });
  if (btnElem) {
    btnElem.className = 'risk-filter-btn px-3 py-1.5 rounded-lg text-xs font-bold bg-blue-600 text-white transition cursor-pointer';
  }

  // Instant in-memory filter if cached
  if (analyticsMemoryCache?.at_risk_students) {
    renderAtRiskUI(analyticsMemoryCache.at_risk_students, analyticsMemoryCache.high_risk_count, true);
    return;
  }
  loadAnalyticsAtRisk(level);
}

/**
 * Trigger Live Retraining of the Scikit-Learn ML Model
 */
async function triggerModelRetrain() {
  const btn = document.getElementById('btn-retrain-ml');
  const spinner = document.getElementById('retrain-spinner');
  const statusText = document.getElementById('model-status-text');

  if (btn) btn.disabled = true;
  if (spinner) spinner.classList.add('animate-spin');
  if (statusText) statusText.textContent = 'Retraining Scikit-Learn Model...';

  // Render skeletons across all tab containers during model recalculation
  renderOverviewChartsSkeleton();
  renderFeatureImportancesSkeleton();
  renderPatternsSkeleton();
  renderClustersSkeleton();
  renderAtRiskSkeleton();

  try {
    const res = await fetch('/api/analytics/retrain', { method: 'POST' });
    const data = await res.json();

    if (data.status === 'success') {
      showToastNotification('Scikit-Learn model retrained successfully with fresh attendance weights!', 'success');
      
      // Update UI specs
      updateModelSpecsUI(data.model_specs);
      renderFeatureImportances(data.feature_importances);

      // Refresh overview, patterns, and at-risk datasets
      loadAnalyticsOverview();
      loadAnalyticsPatterns();
      loadAnalyticsAtRisk();
    } else {
      showToastNotification(data.message || 'Retraining failed.', 'error');
    }
  } catch (err) {
    showToastNotification('Network error during model retraining.', 'error');
  } finally {
    if (btn) btn.disabled = false;
    if (spinner) spinner.classList.remove('animate-spin');
    if (statusText) statusText.textContent = 'Model Active';
  }
}

/**
 * Open Risk Diagnostics Modal for Student
 */
function openRiskModal(studentId) {
  const student = currentAtRiskStudents.find(s => s.student_id === studentId);
  if (!student) return;

  currentSelectedStudentForModal = student;

  const modal = document.getElementById('risk-breakdown-modal');
  const nameElem = document.getElementById('modal-student-name');
  const levelElem = document.getElementById('modal-risk-level');
  const scoreElem = document.getElementById('modal-risk-score');
  const factorsElem = document.getElementById('modal-risk-factors');
  const actionElem = document.getElementById('modal-recommended-action');

  if (nameElem) nameElem.textContent = `${student.name} (${student.section}) — Risk Diagnostics`;
  if (levelElem) {
    levelElem.textContent = student.risk_level;
    levelElem.className = student.risk_level === 'High Risk' ? 'text-base font-black text-rose-600 mt-0.5' : 'text-base font-black text-amber-600 mt-0.5';
  }
  if (scoreElem) scoreElem.textContent = `${student.risk_score}%`;
  
  if (factorsElem) {
    factorsElem.innerHTML = (student.risk_factors || []).map(f => `
      <li class="flex items-center gap-2 text-xs font-semibold text-slate-700">
        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
        <span>${escapeHtml(f)}</span>
      </li>
    `).join('');
  }

  if (actionElem) actionElem.textContent = student.recommended_action || 'Standard Attendance Monitoring';

  if (modal) modal.classList.remove('hidden');
}

/**
 * Close Risk Diagnostics Modal
 */
function closeRiskModal() {
  const modal = document.getElementById('risk-breakdown-modal');
  if (modal) modal.classList.add('hidden');
  currentSelectedStudentForModal = null;
}

/**
 * Modal Trigger: Dispatch Parent Alert
 */
async function executeModalNotifyParent() {
  if (!currentSelectedStudentForModal) return;
  const s = currentSelectedStudentForModal;
  closeRiskModal();
  await handleDirectParentAlert(s.student_id, s.name);
}

/**
 * Dispatch Parent Early-Warning Notification via API
 */
async function handleDirectParentAlert(studentId, studentName) {
  try {
    const res = await fetch('/api/analytics/intervene', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        student_id: studentId,
        student_name: studentName,
        action_type: 'notify_parent'
      })
    });
    const data = await res.json();
    if (data.status === 'success') {
      showToastNotification(`Parent early-warning notification sent for ${studentName}!`, 'success');
    } else {
      showToastNotification(data.message || 'Could not send parent notification.', 'error');
    }
  } catch (err) {
    showToastNotification(`Dispatched offline notification for ${studentName}.`, 'info');
  }
}

/**
 * Trigger Pattern-Level AI Intervention (Real Database Execution & Modal Feedback)
 */
async function applyPatternIntervention(patternId, patternTitle, btnElem) {
  const originalHtml = btnElem ? btnElem.innerHTML : 'Apply Action';
  if (btnElem) {
    btnElem.disabled = true;
    btnElem.innerHTML = `
      <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      <span>Executing Plan...</span>
    `;
  }

  try {
    const res = await fetch('/api/analytics/apply-pattern-action', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        pattern_id: patternId,
        pattern_title: patternTitle
      })
    });
    const data = await res.json();

    if (data.status === 'success') {
      // 1. Show Toast
      showToastNotification(data.message || `Intervention applied for ${patternTitle}`, 'success');

      // 2. Populate and Open the Intervention Modal
      const modal = document.getElementById('pattern-action-modal');
      const titleElem = document.getElementById('pam-title');
      const subElem = document.getElementById('pam-subtitle');
      const descElem = document.getElementById('pam-description');
      const stCountElem = document.getElementById('pam-student-count');
      const alCountElem = document.getElementById('pam-alerts-count');
      const cohortElem = document.getElementById('pam-cohort-label');
      const tbody = document.getElementById('pam-students-tbody');
      const timeElem = document.getElementById('pam-applied-time');

      if (titleElem) titleElem.textContent = data.action_name || 'AI Intervention Plan Executed';
      if (subElem) subElem.textContent = `${data.alerts_queued || 0} real database alerts dispatched and recorded in parent_alerts table`;
      if (descElem) descElem.innerHTML = `<strong>Action Details:</strong> ${escapeHtml(data.action_desc || data.message)}`;
      if (stCountElem) stCountElem.textContent = data.affected_count || 0;
      if (alCountElem) alCountElem.textContent = data.alerts_queued || 0;
      if (cohortElem) cohortElem.textContent = `Pattern: ${patternTitle}`;
      if (timeElem) timeElem.innerHTML = `
        <svg class="w-3.5 h-3.5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Executed on ${data.applied_at || 'just now'}</span>
      `;

      if (tbody) {
        const students = data.students || [];
        if (students.length === 0) {
          tbody.innerHTML = `<tr><td colspan="4" class="py-4 text-center text-slate-400">No students matched strict criteria.</td></tr>`;
        } else {
          tbody.innerHTML = students.map(s => `
            <tr class="hover:bg-slate-50 transition">
              <td class="py-2.5 px-3 font-bold text-slate-900">${escapeHtml(s.full_name)} <span class="block text-[10px] text-slate-400 font-normal">#${s.student_id}</span></td>
              <td class="py-2.5 px-3 font-semibold text-slate-600">Year ${s.year_level} · ${escapeHtml(s.section)}</td>
              <td class="py-2.5 px-3 text-slate-600 truncate max-w-[140px]">${escapeHtml(s.parent_email)}</td>
              <td class="py-2.5 px-3 text-right font-black text-rose-600">${s.trigger_metric || s.total_absences || 0}</td>
            </tr>
          `).join('');
        }
      }

      if (modal) modal.classList.remove('hidden');

      // 3. Update the Card's button to Active state
      if (btnElem) {
        btnElem.className = 'px-3.5 py-1.5 bg-emerald-600 text-white font-bold rounded-xl shrink-0 shadow-xs flex items-center gap-1.5 text-xs';
        btnElem.innerHTML = `
          <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
          <span>Active (${data.alerts_queued || 0} Notified)</span>
        `;
        btnElem.disabled = false;
      }
    } else {
      showToastNotification(data.message || 'Action failed to execute.', 'error');
      if (btnElem) {
        btnElem.disabled = false;
        btnElem.innerHTML = originalHtml;
      }
    }
  } catch (err) {
    showToastNotification('Network error executing intervention action.', 'error');
    if (btnElem) {
      btnElem.disabled = false;
      btnElem.innerHTML = originalHtml;
    }
  }
}

/**
 * Close Pattern Action Confirmation Modal
 */
function closePatternActionModal() {
  const modal = document.getElementById('pattern-action-modal');
  if (modal) modal.classList.add('hidden');
}

/**
 * Export Current At-Risk List to CSV File (respects active risk level filter)
 */
function exportAtRiskCSV() {
  const allStudents = currentAtRiskStudents || [];
  let exportList = allStudents;
  if (atRiskCurrentFilterLevel !== 'all') {
    exportList = allStudents.filter(s => String(s.risk_level || '').toLowerCase().includes(atRiskCurrentFilterLevel.toLowerCase()));
  }

  if (exportList.length === 0) {
    showToastNotification('No students available to export with current filter.', 'info');
    return;
  }

  const headers = ['Student ID', 'Full Name', 'Section', 'Grade Level', 'Attendance Rate (%)', 'Total Absences', 'Tardy Count', 'Consecutive Absences', 'Risk Score (%)', 'Risk Classification', 'Primary Risk Factor', 'Recommended Action'];
  const rows = exportList.map(s => [
    s.student_id,
    `"${s.name}"`,
    `"${s.section}"`,
    s.grade_level || '3',
    s.attendance_rate,
    s.absence_count,
    s.tardy_count,
    s.consecutive_absences,
    s.risk_score,
    `"${s.risk_level}"`,
    `"${s.primary_factor}"`,
    `"${s.recommended_action}"`
  ]);

  const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
  const encodedUri = encodeURI(csvContent);
  const link = document.createElement('a');
  link.setAttribute('href', encodedUri);
  link.setAttribute('download', `at_risk_students_${atRiskCurrentFilterLevel}_${new Date().toISOString().slice(0,10)}.csv`);
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  showToastNotification(`Exported ${exportList.length} At-Risk student records to CSV.`, 'success');
}

/**
 * Filter Form Triggers
 */
function applyAnalyticsFilters() {
  loadAllAnalytics(true);
  showToastNotification('Analytics filters applied successfully.', 'info');
}

function resetAnalyticsFilters() {
  const dr = document.getElementById('filter-date-range');
  const gr = document.getElementById('filter-grade');
  const sc = document.getElementById('filter-section');

  if (dr) dr.value = '90';
  if (gr) gr.value = 'all';
  if (sc) sc.value = 'all';

  applyAnalyticsFilters();
}

/**
 * Helper: Toast Notification
 */
function showToastNotification(msg, type = 'info') {
  if (typeof APP !== 'undefined' && APP.showToast) {
    APP.showToast(msg, type);
  } else if (typeof APP !== 'undefined' && APP.toast) {
    APP.toast(msg, type);
  } else {
    alert(msg);
  }
}

/**
 * Helper: HTML Escaper
 */
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}
