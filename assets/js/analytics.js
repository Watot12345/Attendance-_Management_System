/**
 * Analytics JS — analytics.js
 * Comprehensive mock data, proper graph types (Line, Bar, Grouped Bar, Doughnut),
 * interactive filter updates, tab switcher, and pattern / at-risk modals.
 */

let chartInstances = {};

document.addEventListener('DOMContentLoaded', function() {
  // Initialize all analytics charts with proper chart types and mock data
  initAnalyticsCharts();

  // Check URL param for tab (e.g. ?tab=patterns, ?tab=at-risk)
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab === 'patterns' || tab === 'at-risk') {
    switchAnalyticsTab(tab);
  }
});

/**
 * Initialize all 4 charts with methodologically correct types & realistic mock data
 */
function initAnalyticsCharts() {
  // 1. 90-Day Trend vs ML Benchmark (Time Series Continuous Trend -> Dual Line Chart)
  initTrendChart();

  // 2. Absences by Day of Week (Discrete Categorical Intervals -> Bar Chart with Monday Highlight)
  initDayChart();

  // 3. Grade-Level Absence & Tardy Comparison (Cohort Multi-Variable -> Grouped Bar Chart)
  initGradeChart();

  // 4. Overall Attendance Status Composition (Part-to-Whole Composition -> Doughnut Chart)
  initStatusDoughnutChart();
}

/**
 * Chart 1: Time Series Dual-Line Chart
 */
function initTrendChart() {
  const canvas = document.getElementById('analytics-trend-chart');
  if (!canvas) return;

  if (chartInstances['analytics-trend-chart']) {
    chartInstances['analytics-trend-chart'].destroy();
  }

  const ctx = canvas.getContext('2d');
  let gradient = null;
  if (ctx) {
    gradient = ctx.createLinearGradient(0, 0, 0, 280);
    gradient.addColorStop(0, 'rgba(13, 148, 136, 0.22)');
    gradient.addColorStop(1, 'rgba(13, 148, 136, 0.01)');
  }

  const labels = [
    'Jun 15', 'Jun 22', 'Jun 29', 'Jul 6', 'Jul 13', 'Jul 20',
    'Jul 27', 'Aug 3', 'Aug 10', 'Aug 17', 'Aug 24', 'Aug 31', 'Sep 7'
  ];

  chartInstances['analytics-trend-chart'] = new Chart(canvas, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Actual Attendance %',
          data: [92.5, 91.8, 93.4, 94.1, 90.5, 93.8, 95.2, 94.6, 91.2, 93.9, 94.8, 92.1, 89.2],
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
          label: 'ML Benchmark Target (92%)',
          data: [92.0, 92.0, 92.0, 92.0, 92.0, 92.0, 92.0, 92.0, 92.0, 92.0, 92.0, 92.0, 92.0],
          borderColor: '#64748b',
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
      interaction: {
        mode: 'index',
        intersect: false
      },
      plugins: {
        legend: {
          display: true,
          position: 'top',
          align: 'end',
          labels: {
            boxWidth: 12,
            boxHeight: 12,
            font: { size: 11 },
            color: '#64748b'
          }
        },
        tooltip: {
          backgroundColor: '#0f172a',
          titleColor: '#f8fafc',
          bodyColor: '#cbd5e1',
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: function(ctx) {
              return `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: '#64748b' }
        },
        y: {
          min: 80,
          max: 100,
          grid: { color: 'rgba(226, 232, 240, 0.7)', borderDash: [4, 4] },
          ticks: {
            font: { size: 11 },
            color: '#64748b',
            stepSize: 5,
            callback: function(val) { return val + '%'; }
          }
        }
      }
    }
  });
}

/**
 * Chart 2: Categorical Bar Chart (Monday highlighted in red to display anomaly)
 */
function initDayChart() {
  const canvas = document.getElementById('day-chart');
  if (!canvas) return;

  if (chartInstances['day-chart']) {
    chartInstances['day-chart'].destroy();
  }

  chartInstances['day-chart'] = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
      datasets: [
        {
          label: 'Total Absences',
          data: [38, 11, 12, 9, 14],
          backgroundColor: [
            '#ef4444', // Monday Spike highlighted in Alert Red
            '#0d9488',
            '#0d9488',
            '#0d9488',
            '#0d9488'
          ],
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
            title: function(items) {
              const dayNames = { Mon: 'Monday', Tue: 'Tuesday', Wed: 'Wednesday', Thu: 'Thursday', Fri: 'Friday' };
              return dayNames[items[0].label] || items[0].label;
            },
            label: function(ctx) {
              const isMon = ctx.label === 'Mon';
              return `${ctx.parsed.y} absences recorded ${isMon ? '(3× weekday average!)' : ''}`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: '#64748b' }
        },
        y: {
          beginAtZero: true,
          max: 45,
          grid: { color: 'rgba(226, 232, 240, 0.7)', borderDash: [4, 4] },
          ticks: { font: { size: 11 }, color: '#64748b', stepSize: 10 }
        }
      }
    }
  });
}

/**
 * Chart 3: Grouped Multi-Bar Chart (Comparing 2 metrics across 4 discrete cohorts)
 */
function initGradeChart() {
  const canvas = document.getElementById('grade-chart');
  if (!canvas) return;

  if (chartInstances['grade-chart']) {
    chartInstances['grade-chart'].destroy();
  }

  chartInstances['grade-chart'] = new Chart(canvas, {
    type: 'bar',
    data: {
      labels: ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'],
      datasets: [
        {
          label: 'Absence Rate',
          data: [8.4, 4.2, 3.8, 3.1],
          backgroundColor: '#ef4444',
          borderRadius: 5,
          barPercentage: 0.7
        },
        {
          label: 'Tardy Rate',
          data: [11.2, 6.5, 5.8, 4.9],
          backgroundColor: '#f59e0b',
          borderRadius: 5,
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
          labels: {
            boxWidth: 10,
            boxHeight: 10,
            font: { size: 11 },
            color: '#64748b'
          }
        },
        tooltip: {
          backgroundColor: '#0f172a',
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: function(ctx) {
              return `${ctx.dataset.label}: ${ctx.parsed.y.toFixed(1)}%`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: { display: false },
          ticks: { font: { size: 11 }, color: '#64748b' }
        },
        y: {
          beginAtZero: true,
          max: 14,
          grid: { color: 'rgba(226, 232, 240, 0.7)', borderDash: [4, 4] },
          ticks: {
            font: { size: 11 },
            color: '#64748b',
            stepSize: 3,
            callback: function(val) { return val + '%'; }
          }
        }
      }
    }
  });
}

/**
 * Chart 4: Proportional Part-to-Whole Doughnut Chart
 */
function initStatusDoughnutChart() {
  const canvas = document.getElementById('status-doughnut-chart');
  if (!canvas) return;

  if (chartInstances['status-doughnut-chart']) {
    chartInstances['status-doughnut-chart'].destroy();
  }

  chartInstances['status-doughnut-chart'] = new Chart(canvas, {
    type: 'doughnut',
    data: {
      labels: ['Present', 'Tardy', 'Excused', 'Unexcused'],
      datasets: [
        {
          data: [89.2, 6.5, 2.8, 1.5],
          backgroundColor: [
            '#10b981', // Present - Emerald
            '#f59e0b', // Tardy - Amber
            '#0d9488', // Excused - Teal
            '#ef4444'  // Unexcused - Red
          ],
          borderWidth: 2,
          borderColor: '#ffffff',
          hoverOffset: 4
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            boxWidth: 10,
            boxHeight: 10,
            padding: 12,
            font: { size: 11 },
            color: '#64748b'
          }
        },
        tooltip: {
          backgroundColor: '#0f172a',
          padding: 10,
          cornerRadius: 8,
          callbacks: {
            label: function(ctx) {
              return ` ${ctx.label}: ${ctx.parsed}%`;
            }
          }
        }
      }
    }
  });
}

/**
 * Apply filters dynamically to all charts
 */
function applyAnalyticsFilters() {
  const dateRange = document.getElementById('filter-date-range')?.value || '90';
  const grade = document.getElementById('filter-grade')?.value || 'all';
  const section = document.getElementById('filter-section')?.value || 'all';

  // Animate trend chart adjustment
  const trend = chartInstances['analytics-trend-chart'];
  if (trend) {
    if (grade === '7') {
      trend.data.datasets[0].data = [91.0, 90.2, 91.5, 92.0, 88.0, 91.5, 93.0, 92.4, 89.1, 91.8, 92.5, 89.5, 87.2];
    } else if (grade === '10') {
      trend.data.datasets[0].data = [94.5, 94.0, 95.2, 96.0, 93.5, 95.8, 96.5, 96.0, 94.8, 95.9, 96.2, 95.1, 94.8];
    } else {
      trend.data.datasets[0].data = [92.5, 91.8, 93.4, 94.1, 90.5, 93.8, 95.2, 94.6, 91.2, 93.9, 94.8, 92.1, 89.2];
    }
    trend.update('active');
  }

  // Animate day chart adjustment
  const day = chartInstances['day-chart'];
  if (day) {
    if (grade === '7') {
      day.data.datasets[0].data = [24, 4, 5, 3, 5]; // Grade 7 accounts for most of the Monday spike
    } else if (grade === '10') {
      day.data.datasets[0].data = [5, 3, 2, 2, 3];
    } else {
      day.data.datasets[0].data = [38, 11, 12, 9, 14];
    }
    day.update('active');
  }

  // Animate doughnut chart adjustment
  const doughnut = chartInstances['status-doughnut-chart'];
  if (doughnut) {
    if (grade === '7') {
      doughnut.data.datasets[0].data = [84.6, 8.8, 3.6, 3.0];
    } else if (grade === '10') {
      doughnut.data.datasets[0].data = [93.4, 4.2, 1.8, 0.6];
    } else {
      doughnut.data.datasets[0].data = [89.2, 6.5, 2.8, 1.5];
    }
    doughnut.update('active');
  }

  if (typeof APP !== 'undefined' && APP.showToast) {
    APP.showToast(`Filters applied for Grade ${grade.toUpperCase()} (${dateRange} days). Visualizations updated.`, 'success');
  }
}

/**
 * Tab switcher for Analytics (Overview, Patterns, At-Risk)
 */
function switchAnalyticsTab(tab) {
  const tabs = ['overview', 'patterns', 'at-risk'];

  tabs.forEach(t => {
    const btn = document.getElementById(`tab-btn-${t}`);
    const panel = document.getElementById(`analytics-panel-${t}`);

    if (!btn || !panel) return;

    if (t === tab) {
      panel.classList.remove('hidden');
      btn.style.background = 'var(--color-teal-500)';
      btn.style.color = '#ffffff';
      btn.classList.add('font-semibold');
      btn.classList.remove('font-medium');
    } else {
      panel.classList.add('hidden');
      btn.style.background = 'transparent';
      btn.style.color = 'var(--color-text-secondary)';
      btn.classList.remove('font-semibold');
      btn.classList.add('font-medium');
    }
  });

  // Re-trigger chart resize when overview panel is made visible
  if (tab === 'overview') {
    setTimeout(() => {
      Object.values(chartInstances).forEach(inst => {
        if (inst && typeof inst.resize === 'function') {
          inst.resize();
        }
      });
    }, 60);
  }
}

/**
 * Display affected students for a pattern
 */
function showPatternModal(title, students, recommendation) {
  if (typeof APP === 'undefined' || !APP.openModal) return;

  const listItems = students.map(s => `<li class="py-1.5 border-b border-gray-100 flex items-center justify-between"><span class="font-medium text-sm text-slate-800">${s}</span><span class="badge badge-pending text-xs">Flagged</span></li>`).join('');

  const bodyHTML = `
    <div class="space-y-4">
      <div class="p-3 rounded-lg bg-teal-50 border border-teal-100">
        <p class="text-xs font-semibold uppercase text-teal-700">Actionable ML Recommendation</p>
        <p class="text-sm mt-1 text-slate-700">${recommendation}</p>
      </div>
      <div>
        <h4 class="text-sm font-semibold text-slate-800 mb-2">Affected Student Sample (${students.length})</h4>
        <ul class="max-h-48 overflow-y-auto divide-y divide-gray-100 bg-slate-50 rounded-lg p-3">
          ${listItems}
        </ul>
      </div>
    </div>
  `;

  const footerHTML = `
    <button type="button" class="btn btn-secondary btn-sm" onclick="APP.closeModal()">Close</button>
    <button type="button" class="btn btn-primary btn-sm" onclick="APP.closeModal(); APP.showToast('Batch alert sent to section advisers.', 'success')">Notify Advisers</button>
  `;

  APP.openModal(`Pattern Details: ${title}`, bodyHTML, footerHTML);
}

/**
 * Display individual student risk detail modal
 */
function showStudentRiskModal(name, grade, stats, riskLevel, notes) {
  if (typeof APP === 'undefined' || !APP.openModal) return;

  const badgeClass = riskLevel === 'HIGH' ? 'badge-high' : 'badge-medium';

  const bodyHTML = `
    <div class="space-y-4">
      <div class="flex items-center justify-between pb-3 border-b border-gray-100">
        <div>
          <h3 class="text-base font-bold text-slate-900">${name}</h3>
          <p class="text-xs text-slate-500">${grade}</p>
        </div>
        <span class="badge ${badgeClass}">${riskLevel} RISK</span>
      </div>

      <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="p-3 bg-slate-50 rounded-lg">
          <p class="text-xs text-slate-400">Absence Frequency</p>
          <p class="font-bold text-slate-800 mt-0.5">${stats}</p>
        </div>
        <div class="p-3 bg-slate-50 rounded-lg">
          <p class="text-xs text-slate-400">Model Confidence</p>
          <p class="font-bold text-emerald-600 mt-0.5">89.4%</p>
        </div>
      </div>

      <div class="p-3 rounded-lg bg-amber-50 border border-amber-100 text-sm">
        <p class="text-xs font-semibold uppercase text-amber-800">Behavioral Indicators</p>
        <p class="text-xs text-amber-900 mt-1">${notes}</p>
      </div>

      <div>
        <label class="form-label text-xs">Intervention Action Log</label>
        <textarea class="form-input text-xs" rows="2" placeholder="Record counselor or teacher notes here..."></textarea>
      </div>
    </div>
  `;

  const footerHTML = `
    <button type="button" class="btn btn-secondary btn-sm" onclick="APP.closeModal()">Close</button>
    <button type="button" class="btn btn-primary btn-sm" onclick="APP.closeModal(); APP.showToast('Parent meeting requested for ${name}.', 'success')">Request Parent Meeting</button>
  `;

  APP.openModal('Student Risk Profile', bodyHTML, footerHTML);
}
