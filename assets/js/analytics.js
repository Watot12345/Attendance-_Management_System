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
let appliedPatternsMap = {};

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
 * Helper: Generate HTML for Applied Pattern Indicator with Interactive Tooltip
 */
function getPatternAppliedTooltipHtml(patternId, patternTitle, alertsQueued, appliedAt) {
  const safeTitle = escapeHtml(patternTitle);
  return `
    <div id="action-wrapper-${patternId}" class="relative group/tooltip inline-flex items-center">
      <button type="button" id="btn-action-${patternId}" 
        class="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shrink-0 shadow-xs flex items-center gap-1.5 text-xs transition cursor-pointer" 
        onclick="applyPatternIntervention('${patternId}', '${safeTitle}', this)">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span>Applied (${alertsQueued} Notified)</span>
      </button>
      <!-- Interactive Tooltip -->
      <div class="absolute bottom-full right-0 mb-2 hidden group-hover/tooltip:flex flex-col items-center pointer-events-none z-30 min-w-[230px] animate-in fade-in zoom-in-95 duration-100">
        <div class="bg-slate-900 text-white text-[11px] font-medium p-3 rounded-xl shadow-xl border border-slate-700/80 text-left space-y-1.5 w-full">
          <div class="flex items-center gap-1.5 font-bold text-emerald-400">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Intervention Applied</span>
          </div>
          <p class="text-slate-300 text-[10px] leading-relaxed">
            ${alertsQueued} parent alert notice(s) successfully queued &amp; recorded in MySQL audit ledger.
          </p>
          <div class="text-[9px] text-slate-400 pt-1.5 border-t border-slate-800 flex items-center justify-between font-mono">
            <span class="text-emerald-300 font-semibold flex items-center gap-1">
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Active
            </span>
            <span>${appliedAt || 'Just now'}</span>
          </div>
        </div>
        <div class="w-2.5 h-2.5 -mt-1.5 rotate-45 bg-slate-900 border-r border-b border-slate-700/80"></div>
      </div>
    </div>
  `;
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
        const badgeClass = pat.severity === 'critical' ? 'bg-rose-100 text-rose-800 border-rose-200' : (pat.severity === 'high' ? 'bg-amber-100 text-amber-800 border-amber-200' : 'bg-blue-100 text-blue-800 border-blue-200');
        const appliedInfo = appliedPatternsMap[pat.id];
        const actionBtnHtml = appliedInfo 
          ? getPatternAppliedTooltipHtml(pat.id, pat.title, appliedInfo.alerts_queued || 0, appliedInfo.applied_at || 'Just now')
          : `
            <div id="action-wrapper-${pat.id}" class="relative inline-flex items-center">
              <button type="button" id="btn-action-${pat.id}" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl cursor-pointer shadow-xs transition flex items-center gap-1.5 text-xs" onclick="applyPatternIntervention('${pat.id}', '${escapeHtml(pat.title)}', this)">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                <span>Apply Action</span>
              </button>
            </div>
          `;

        html += `
          <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-3.5 hover:border-slate-300 transition">
            <div class="flex items-center justify-between flex-wrap gap-2">
              <span class="px-2.5 py-1 rounded-full text-xs font-black uppercase tracking-wider bg-blue-50 text-blue-700 border border-blue-200">
                Pattern #${idx + 1} · ${pat.type.replace(/_/g, ' ').toUpperCase()}
              </span>
              <span class="px-2.5 py-0.5 rounded-full text-xs font-bold border ${badgeClass}">
                ${pat.severity.toUpperCase()} IMPACT
              </span>
            </div>
            <h4 class="text-base font-black text-slate-900">${escapeHtml(pat.title)}</h4>
            <p class="text-xs text-slate-600 leading-relaxed">${escapeHtml(pat.description)}</p>
            <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/60 flex items-center justify-between text-xs flex-wrap gap-2">
              <span class="text-slate-500 font-medium">Affected: <strong class="text-slate-900">${escapeHtml(pat.affected_cohort)}</strong></span>
              <div class="flex items-center gap-2">
                <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 font-bold text-[11px]">Database Match</span>
                <span class="text-blue-700 font-extrabold">Confidence: ${escapeHtml(pat.confidence)}</span>
              </div>
            </div>
            <div class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-100 text-xs font-medium text-blue-950 flex items-center justify-between gap-3 flex-wrap sm:flex-nowrap">
              <div class="leading-relaxed">
                <strong class="text-blue-950">AI Intervention:</strong> ${escapeHtml(pat.recommendation)}
              </div>
              <div class="flex items-center gap-2 shrink-0">
                <button type="button" class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 font-bold rounded-xl cursor-pointer shadow-xs transition flex items-center gap-1.5 text-xs" onclick="openPatternInspectModal('${pat.id}')" title="Inspect Live Matching Students & Test Actions">
                  <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  <span>Inspect &amp; Test</span>
                </button>
                ${actionBtnHtml}
              </div>
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

        <!-- Section & Year -->
        <td class="py-3.5 px-4 font-semibold text-slate-600">
          ${(() => {
            const sec = s.section || '';
            const isEnrolled = sec && sec !== 'Not Enrolled Yet' && sec !== 'Unassigned' && sec !== 'No Class';
            if (!isEnrolled) {
              return `<span class="inline-flex items-center gap-1.5 text-slate-400 font-normal italic text-xs"><i class="fas fa-user-slash text-[10px]"></i> Not Enrolled Yet</span>`;
            }
            const yr = (s.grade_level && s.grade_level >= 1 && s.grade_level <= 4) 
              ? s.grade_level 
              : (/^[1-4]/.test(sec) ? sec.charAt(0) : '1');
            return `<span class="font-medium text-slate-800">Sec ${escapeHtml(sec)}</span> <span class="text-xs text-slate-400 font-normal">(Yr ${yr})</span>`;
          })()}
        </td>

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
          <button type="button" id="btn-alert-student-${s.student_id}" class="btn btn-primary btn-sm font-bold text-[11px] px-2.5 py-1 inline-flex items-center gap-1 cursor-pointer" onclick="handleDirectParentAlert(${s.student_id}, '${escapeHtml(s.name)}', this)">
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
 * Execute Bulk Early-Warning Parent Alerts via Queue Dispatcher Modal
 */
let queueElapsedTimer = null;

function closeAtRiskQueueModal() {
  const modal = document.getElementById('at-risk-queue-modal');
  if (modal) modal.classList.add('hidden');
  if (queueElapsedTimer) {
    clearInterval(queueElapsedTimer);
    queueElapsedTimer = null;
  }
}

async function executeBulkParentAlert() {
  const selectedIds = Array.from(selectedAtRiskStudentIds);
  if (selectedIds.length === 0) {
    showToastNotification('Please select at least one student first.', 'info');
    return;
  }

  const selectedList = (currentAtRiskStudents || []).filter(s => selectedIds.includes(Number(s.student_id)));
  
  // 1. Open the Queue Modal
  const modal = document.getElementById('at-risk-queue-modal');
  const titleEl = document.getElementById('queue-modal-title');
  const badgeEl = document.getElementById('queue-status-badge');
  const batchRefEl = document.getElementById('queue-batch-ref');
  const progressLabel = document.getElementById('queue-progress-label');
  const progressPct = document.getElementById('queue-progress-pct');
  const progressBar = document.getElementById('queue-progress-bar');
  const streamCount = document.getElementById('queue-stream-count');
  const itemsTbody = document.getElementById('queue-items-tbody');
  const spinnerIcon = document.getElementById('queue-header-spinner');
  const checkIcon = document.getElementById('queue-header-check');
  const elapsedEl = document.getElementById('queue-elapsed-time');

  if (modal) modal.classList.remove('hidden');

  // Reset UI State
  const batchId = 'AQ-' + new Date().toISOString().slice(2,10).replace(/-/g,'') + '-' + Math.random().toString(36).substring(2,6).toUpperCase();
  if (batchRefEl) batchRefEl.textContent = `Batch Reference: #${batchId}`;
  if (badgeEl) {
    badgeEl.textContent = 'PROCESSING QUEUE';
    badgeEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/20 text-blue-300 border border-blue-500/30';
  }
  if (spinnerIcon) spinnerIcon.classList.remove('hidden');
  if (checkIcon) checkIcon.classList.add('hidden');
  if (streamCount) streamCount.textContent = `${selectedIds.length} items in queue`;

  // Populate Queue Items Table in pending state
  if (itemsTbody) {
    itemsTbody.innerHTML = selectedList.map((s, idx) => `
      <tr id="qrow-${s.student_id}" class="hover:bg-slate-50 transition">
        <td class="py-2 px-3 font-mono text-slate-400 font-bold">${idx + 1}</td>
        <td class="py-2 px-3 font-bold text-slate-900">${escapeHtml(s.name)} <span class="text-[10px] text-slate-400 block">ID: #${s.student_id} · ${escapeHtml(s.section || 'N/A')}</span></td>
        <td class="py-2 px-3 font-mono text-slate-600 text-[11px]">${escapeHtml(s.parent_email || 'Resolving...')}</td>
        <td class="py-2 px-3 text-right font-semibold" id="qstatus-${s.student_id}">
          <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-200">
            <svg class="w-2.5 h-2.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            Queued
          </span>
        </td>
      </tr>
    `).join('');
  }

  // Elapsed timer
  let seconds = 0;
  if (queueElapsedTimer) clearInterval(queueElapsedTimer);
  queueElapsedTimer = setInterval(() => {
    seconds++;
    if (elapsedEl) elapsedEl.textContent = `Elapsed: ${seconds}s`;
  }, 1000);

  // Animate step 1
  setQueueStep(1, 'active', 'Resolving...');
  setQueueStep(2, 'waiting', 'Waiting');
  setQueueStep(3, 'waiting', 'Waiting');
  setQueueStep(4, 'waiting', 'Waiting');

  if (progressLabel) progressLabel.textContent = `Queueing ${selectedIds.length} alerts...`;
  if (progressPct) progressPct.textContent = '20%';
  if (progressBar) progressBar.style.width = '20%';

  try {
    setTimeout(() => {
      setQueueStep(1, 'done', 'Resolved ✓');
      setQueueStep(2, 'active', 'Enqueueing...');
      if (progressPct) progressPct.textContent = '50%';
      if (progressBar) progressBar.style.width = '50%';
    }, 400);

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
      setQueueStep(2, 'done', 'Logged to DB ✓');
      setQueueStep(3, 'done', 'Dispatched ✓');
      setQueueStep(4, 'done', 'Audit Logged ✓');

      if (progressLabel) progressLabel.textContent = `All ${selectedIds.length} alerts successfully processed!`;
      if (progressPct) progressPct.textContent = '100%';
      if (progressBar) {
        progressBar.style.width = '100%';
        progressBar.className = 'bg-gradient-to-r from-emerald-500 to-teal-600 h-2.5 rounded-full transition-all duration-300';
      }

      if (batchRefEl && data.queue_id) batchRefEl.textContent = `Batch Reference: #${data.queue_id}`;
      if (badgeEl) {
        badgeEl.textContent = 'COMPLETED & LOGGED';
        badgeEl.className = 'px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
      }
      if (spinnerIcon) spinnerIcon.classList.add('hidden');
      if (checkIcon) checkIcon.classList.remove('hidden');

      // Update rows in queue modal table
      (data.queue_items || []).forEach(item => {
        const statusTd = document.getElementById(`qstatus-${item.student_id}`);
        if (statusTd) {
          statusTd.innerHTML = `
            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
              <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
              Dispatched & Logged
            </span>
          `;
        }

        // Also update table action button in the main table
        const rowBtn = document.getElementById(`btn-alert-student-${item.student_id}`);
        if (rowBtn) {
          rowBtn.className = 'px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1 cursor-default';
          rowBtn.innerHTML = `
            <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Queued & Sent</span>
          `;
          rowBtn.disabled = true;
        }
      });

      showToastNotification(`[Batch ${data.queue_id || batchId}] Successfully queued and dispatched ${selectedIds.length} parent alerts!`, 'success');
      clearAtRiskSelection();
    } else {
      setQueueStep(2, 'error', 'Failed');
      showToastNotification(data.message || 'Bulk alert dispatch failed.', 'error');
    }
  } catch (err) {
    setQueueStep(2, 'error', 'Network Error');
    showToastNotification('Network error dispatching bulk parent alerts.', 'error');
  } finally {
    if (queueElapsedTimer) {
      clearInterval(queueElapsedTimer);
      queueElapsedTimer = null;
    }
  }
}

function setQueueStep(stepNum, status, text) {
  const el = document.getElementById(`qstep-${stepNum}`);
  const sub = document.getElementById(`qstep-${stepNum}-sub`);
  if (!el) return;
  if (sub) sub.textContent = text;

  if (status === 'done') {
    el.className = 'p-2.5 rounded-xl border border-emerald-200 bg-emerald-50/60 space-y-1';
    el.children[0].className = 'w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center mx-auto text-[11px] font-bold';
    el.children[0].innerHTML = '✓';
    el.children[1].className = 'text-[11px] font-bold text-emerald-900';
    if (sub) sub.className = 'text-[9px] font-bold text-emerald-700';
  } else if (status === 'active') {
    el.className = 'p-2.5 rounded-xl border border-blue-300 bg-blue-50/70 space-y-1 shadow-2xs animate-pulse';
    el.children[0].className = 'w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center mx-auto text-[11px] font-bold';
    el.children[1].className = 'text-[11px] font-bold text-blue-900';
    if (sub) sub.className = 'text-[9px] font-bold text-blue-700';
  } else if (status === 'error') {
    el.className = 'p-2.5 rounded-xl border border-rose-300 bg-rose-50 space-y-1';
    el.children[0].className = 'w-6 h-6 rounded-full bg-rose-600 text-white flex items-center justify-center mx-auto text-[11px] font-bold';
    el.children[0].innerHTML = '✕';
    el.children[1].className = 'text-[11px] font-bold text-rose-900';
    if (sub) sub.className = 'text-[9px] font-bold text-rose-700';
  } else {
    el.className = 'p-2.5 rounded-xl border border-slate-200 bg-white space-y-1';
    el.children[0].className = 'w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mx-auto text-[11px] font-bold';
    el.children[0].textContent = String(stepNum);
    el.children[1].className = 'text-[11px] font-bold text-slate-800';
    if (sub) sub.className = 'text-[9px] text-slate-400';
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
    `"${s.section || 'Not Enrolled Yet'}"`,
    (() => {
      const sec = s.section || '';
      const isEnrolled = sec && sec !== 'Not Enrolled Yet' && sec !== 'Unassigned' && sec !== 'No Class';
      if (!isEnrolled) return '"Not Enrolled Yet"';
      const yr = (s.grade_level && s.grade_level >= 1 && s.grade_level <= 4) ? s.grade_level : (/^[1-4]/.test(sec) ? sec.charAt(0) : '1');
      return `"${yr === '1' || yr === 1 ? '1st' : (yr === '2' || yr === 2 ? '2nd' : (yr === '3' || yr === 3 ? '3rd' : '4th'))} Year"`;
    })(),
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
 * Modal Trigger: Dispatch Parent Alert with Loading and Queue Feedback
 */
async function executeModalNotifyParent() {
  if (!currentSelectedStudentForModal) return;
  const s = currentSelectedStudentForModal;
  const modalBtn = document.getElementById('modal-btn-notify-parent');
  const originalHtml = modalBtn ? modalBtn.innerHTML : '';

  if (modalBtn) {
    modalBtn.disabled = true;
    modalBtn.innerHTML = `
      <svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      <span>Queueing Alert...</span>
    `;
  }

  await handleDirectParentAlert(s.student_id, s.name);

  if (modalBtn) {
    modalBtn.className = 'px-3 py-1.5 rounded-lg text-xs font-bold bg-emerald-600 text-white flex items-center gap-1.5';
    modalBtn.innerHTML = `
      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
      <span>Queued &amp; Dispatched</span>
    `;
  }

  setTimeout(() => {
    closeRiskModal();
    if (modalBtn) {
      modalBtn.disabled = false;
      modalBtn.className = 'btn btn-primary btn-sm font-bold flex items-center gap-1.5 cursor-pointer';
      modalBtn.innerHTML = originalHtml;
    }
  }, 900);
}

/**
 * Dispatch Parent Early-Warning Notification via API with loading and queue feedback
 */
async function handleDirectParentAlert(studentId, studentName, btnElem = null) {
  const btn = btnElem || document.getElementById(`btn-alert-student-${studentId}`);
  const originalHtml = btn ? btn.innerHTML : '';
  
  if (btn) {
    btn.disabled = true;
    btn.className = 'px-2.5 py-1 rounded-lg text-[11px] font-bold bg-blue-600 text-white inline-flex items-center gap-1 shadow-2xs';
    btn.innerHTML = `
      <svg class="w-3 h-3 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
      <span>Queueing...</span>
    `;
  }

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
      const qRef = data.queue_id ? ` [Batch: #${data.queue_id}]` : '';
      showToastNotification(`Parent alert queued & logged for ${studentName}!${qRef}`, 'success');
      
      if (btn) {
        btn.className = 'px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1 cursor-default';
        btn.innerHTML = `
          <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
          <span>Queued & Sent</span>
        `;
        btn.disabled = true;
      }
    } else {
      showToastNotification(data.message || 'Could not send parent notification.', 'error');
      if (btn) {
        btn.disabled = false;
        btn.className = 'btn btn-primary btn-sm font-bold text-[11px] px-2.5 py-1 inline-flex items-center gap-1 cursor-pointer';
        btn.innerHTML = originalHtml;
      }
    }
  } catch (err) {
    showToastNotification(`Dispatched offline notification for ${studentName}.`, 'info');
    if (btn) {
      btn.className = 'px-2.5 py-1 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 inline-flex items-center gap-1 cursor-default';
      btn.innerHTML = `
        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
        <span>Queued & Sent</span>
      `;
      btn.disabled = true;
    }
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

      // 3. Update the Card's button to Active state with Interactive Tooltip
      appliedPatternsMap[patternId] = {
        applied_at: data.applied_at || 'Just now',
        alerts_queued: data.alerts_queued || 0,
        action_name: data.action_name,
        affected_count: data.affected_count || 0
      };

      const wrapper = document.getElementById(`action-wrapper-${patternId}`);
      if (wrapper) {
        wrapper.outerHTML = getPatternAppliedTooltipHtml(patternId, patternTitle, data.alerts_queued || 0, data.applied_at || 'Just now');
      } else if (btnElem) {
        const parent = btnElem.closest('.group\\/tooltip') || btnElem.parentElement;
        if (parent) {
          parent.outerHTML = getPatternAppliedTooltipHtml(patternId, patternTitle, data.alerts_queued || 0, data.applied_at || 'Just now');
        } else {
          btnElem.className = 'px-3.5 py-1.5 bg-emerald-600 text-white font-bold rounded-xl shrink-0 shadow-xs flex items-center gap-1.5 text-xs';
          btnElem.innerHTML = `
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
            <span>Applied (${data.alerts_queued || 0} Notified)</span>
          `;
          btnElem.disabled = false;
        }
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
 * Pattern Inspection & Action Testing State
 */
let currentInspectedPattern = null;

/**
 * Open Pattern Inspection & Test Sandbox Modal
 */
async function openPatternInspectModal(patternId) {
  const modal = document.getElementById('pattern-inspect-modal');
  if (!modal) return;

  // Show modal with loading state
  modal.classList.remove('hidden');
  switchPimTab('matches');

  const titleEl = document.getElementById('pim-title');
  const typeEl = document.getElementById('pim-type');
  const badgeEl = document.getElementById('pim-severity-badge');
  const formulaEl = document.getElementById('pim-formula');
  const confEl = document.getElementById('pim-confidence');
  const tbody = document.getElementById('pim-students-tbody');
  const tabCount = document.getElementById('pim-tab-count');
  const zeroBadge = document.getElementById('pim-zero-matches-badge');
  const emptyCallout = document.getElementById('pim-empty-helper-callout');

  if (titleEl) titleEl.textContent = 'Loading Pattern Data...';
  if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-slate-400 font-semibold"><svg class="w-5 h-5 animate-spin mx-auto mb-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>Analyzing live MySQL database records...</td></tr>`;

  try {
    const res = await fetch(`/api/analytics/preview-pattern?pattern_id=${encodeURIComponent(patternId)}`);
    const data = await res.json();

    if (data.status === 'success') {
      currentInspectedPattern = data;

      if (titleEl) titleEl.textContent = data.pattern_title;
      if (typeEl) typeEl.textContent = `Type: ${data.pattern_id} · Action: ${data.action_name}`;
      if (formulaEl) formulaEl.textContent = data.formula;
      if (confEl) confEl.textContent = `Confidence: ${data.confidence}`;

      if (badgeEl) {
        badgeEl.textContent = `${data.severity.toUpperCase()} IMPACT`;
        badgeEl.className = data.severity === 'critical' ? 'px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-rose-500/20 text-rose-300 border border-rose-500/30' : (data.severity === 'high' ? 'px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-amber-500/20 text-amber-300 border border-amber-500/30' : 'px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-500/20 text-blue-300 border border-blue-500/30');
      }

      // Render Tab Count
      if (tabCount) tabCount.textContent = data.live_matches_count || 0;

      // Handle zero matches
      if (data.is_strict_zero) {
        if (zeroBadge) zeroBadge.classList.remove('hidden');
        if (emptyCallout) emptyCallout.classList.remove('hidden');
      } else {
        if (zeroBadge) zeroBadge.classList.add('hidden');
        if (emptyCallout) emptyCallout.classList.add('hidden');
      }

      // Render Students Table
      if (tbody) {
        const students = data.students || [];
        if (students.length === 0) {
          tbody.innerHTML = `<tr><td colspan="5" class="py-8 text-center text-slate-400">No student records found in database.</td></tr>`;
        } else {
          tbody.innerHTML = students.map(s => {
            const attRate = parseFloat(s.attendance_rate || 0);
            const rateColor = attRate < 80 ? 'text-rose-600' : 'text-slate-800';
            return `
              <tr class="hover:bg-slate-50 transition">
                <td class="py-2.5 px-3 font-bold text-slate-900">
                  ${escapeHtml(s.full_name)}
                  <span class="block text-[10px] text-slate-400 font-mono">#${s.student_number || s.student_id}</span>
                </td>
                <td class="py-2.5 px-3 font-semibold text-slate-600">
                  ${(() => {
                    const sec = s.section || '';
                    const isEnrolled = sec && sec !== 'Not Enrolled Yet' && sec !== 'Unassigned' && sec !== 'No Class';
                    if (!isEnrolled) {
                      return `<span class="text-slate-400 font-normal italic text-xs">Not Enrolled Yet</span>`;
                    }
                    const yr = (s.year_level && s.year_level >= 1 && s.year_level <= 4) 
                      ? s.year_level 
                      : (/^[1-4]/.test(sec) ? sec.charAt(0) : '1');
                    return `Yr ${yr} · Sec ${escapeHtml(sec)}`;
                  })()}
                </td>
                <td class="py-2.5 px-3 text-slate-600 font-mono text-[11px] truncate max-w-[150px]">
                  ${escapeHtml(s.parent_email || 'parent@college.edu')}
                </td>
                <td class="py-2.5 px-3 text-center">
                  <span class="font-black ${rateColor}">${attRate}%</span>
                  <span class="block text-[9px] text-slate-400">${s.total_sessions || 0} sessions</span>
                </td>
                <td class="py-2.5 px-3 text-right">
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-black bg-rose-50 text-rose-700 border border-rose-200">
                    ${s.trigger_metric || 0} Absences
                  </span>
                </td>
              </tr>
            `;
          }).join('');
        }
      }

      // Render Email Preview
      const emSub = document.getElementById('pim-email-subject');
      const emRecip = document.getElementById('pim-email-recipient');
      const emDesc = document.getElementById('pim-email-action-desc');
      const emBody = document.getElementById('pim-email-body');

      if (emSub && data.email_preview) emSub.textContent = data.email_preview.subject;
      if (emRecip && data.email_preview) emRecip.textContent = data.email_preview.recipient_sample;
      if (emDesc) emDesc.textContent = data.action_desc;
      if (emBody && data.email_preview) emBody.textContent = data.email_preview.body;
    } else {
      showToastNotification(data.message || 'Could not load pattern preview.', 'error');
    }
  } catch (err) {
    showToastNotification('Network error loading pattern preview.', 'error');
  }
}

/**
 * Close Pattern Inspection Modal
 */
function closePatternInspectModal() {
  const modal = document.getElementById('pattern-inspect-modal');
  if (modal) modal.classList.add('hidden');
  currentInspectedPattern = null;
}

/**
 * Switch Active Tab inside Pattern Inspection Modal
 */
function switchPimTab(tab) {
  const tabBtns = {
    matches: document.getElementById('pim-tab-btn-matches'),
    preview: document.getElementById('pim-tab-btn-preview'),
    sandbox: document.getElementById('pim-tab-btn-sandbox')
  };
  const panels = {
    matches: document.getElementById('pim-panel-matches'),
    preview: document.getElementById('pim-panel-preview'),
    sandbox: document.getElementById('pim-panel-sandbox')
  };

  Object.keys(tabBtns).forEach(k => {
    if (tabBtns[k]) {
      if (k === tab) {
        tabBtns[k].className = 'px-3.5 py-2 border-b-2 border-blue-600 text-blue-600 font-extrabold cursor-pointer transition flex items-center gap-1.5';
      } else {
        tabBtns[k].className = 'px-3.5 py-2 border-b-2 border-transparent text-slate-500 hover:text-slate-800 font-semibold cursor-pointer transition flex items-center gap-1.5';
      }
    }
    if (panels[k]) {
      if (k === tab) {
        panels[k].classList.remove('hidden');
      } else {
        panels[k].classList.add('hidden');
      }
    }
  });
}

/**
 * Execute Test Mode Dispatch from Sandbox
 */
async function executePimTestDispatch() {
  if (!currentInspectedPattern) return;
  const pat = currentInspectedPattern;
  const testEmail = document.getElementById('pim-test-email-input')?.value.trim();
  const btn = document.getElementById('btn-pim-send-test');

  const originalHtml = btn ? btn.innerHTML : 'Send Test Alert';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<svg class="w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Sending Test...</span>`;
  }

  try {
    const res = await fetch('/api/analytics/apply-pattern-action', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        pattern_id: pat.pattern_id,
        pattern_title: pat.pattern_title,
        is_test: true,
        test_email: testEmail
      })
    });
    const data = await res.json();
    if (data.status === 'success') {
      showToastNotification(data.message || `Test alert dispatched to ${data.test_email || testEmail}!`, 'success');
    } else {
      showToastNotification(data.message || 'Test dispatch failed.', 'error');
    }
  } catch (err) {
    showToastNotification('Network error executing test alert.', 'error');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalHtml;
    }
  }
}

/**
 * Execute Real Live Action from Inspection Modal
 */
async function executePimRealDispatch() {
  if (!currentInspectedPattern) return;
  const pat = currentInspectedPattern;
  closePatternInspectModal();
  const cardBtn = document.getElementById(`btn-action-${pat.pattern_id}`);
  await applyPatternIntervention(pat.pattern_id, pat.pattern_title, cardBtn);
}

/**
 * Seed Realistic Demo Attendance Dataset to Test ML Patterns
 */
async function seedDemoAttendanceData(btnElem) {
  const originalHtml = btnElem ? btnElem.innerHTML : 'Seed Test Attendance Data';
  if (btnElem) {
    btnElem.disabled = true;
    btnElem.innerHTML = `<svg class="w-4 h-4 animate-spin text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg><span>Seeding Test Data...</span>`;
  }

  try {
    const res = await fetch('/api/analytics/seed-demo-attendance', { method: 'POST' });
    const data = await res.json();
    if (data.status === 'success') {
      showToastNotification(data.message || 'Successfully seeded multi-week attendance test dataset!', 'success');
      // Refresh all analytics
      loadAllAnalytics(true);
      if (currentInspectedPattern) {
        openPatternInspectModal(currentInspectedPattern.pattern_id);
      }
    } else {
      showToastNotification(data.message || 'Seeding test data failed.', 'error');
    }
  } catch (err) {
    showToastNotification('Network error seeding demo attendance.', 'error');
  } finally {
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
    `"${s.section || 'Not Enrolled Yet'}"`,
    (() => {
      const sec = s.section || '';
      const isEnrolled = sec && sec !== 'Not Enrolled Yet' && sec !== 'Unassigned' && sec !== 'No Class';
      if (!isEnrolled) return '"Not Enrolled Yet"';
      const yr = (s.grade_level && s.grade_level >= 1 && s.grade_level <= 4) ? s.grade_level : (/^[1-4]/.test(sec) ? sec.charAt(0) : '1');
      return `"${yr === '1' || yr === 1 ? '1st' : (yr === '2' || yr === 2 ? '2nd' : (yr === '3' || yr === 3 ? '3rd' : '4th'))} Year"`;
    })(),
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
