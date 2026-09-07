/**
 * Dashboard JS — dashboard.js
 * 30-Day Attendance Trend Area Line Chart with Bestlink theme tokens
 */

document.addEventListener('DOMContentLoaded', function() {
  const canvas = document.getElementById('trend-chart');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let gradient = null;
  if (ctx) {
    gradient = ctx.createLinearGradient(0, 0, 0, 240);
    gradient.addColorStop(0, 'rgba(13, 148, 136, 0.28)');
    gradient.addColorStop(1, 'rgba(13, 148, 136, 0.02)');
  }

  // 30-day realistic school attendance data points (Aug 9 – Sep 7, 2026)
  const labels = [
    'Aug 9', 'Aug 11', 'Aug 12', 'Aug 13', 'Aug 14', 'Aug 15',
    'Aug 18', 'Aug 19', 'Aug 20', 'Aug 21', 'Aug 22',
    'Aug 25', 'Aug 26', 'Aug 27', 'Aug 28', 'Aug 29',
    'Sep 1', 'Sep 2', 'Sep 3', 'Sep 4', 'Sep 5', 'Sep 7'
  ];

  // Headcount out of 277 enrolled students
  const dataPoints = [
    242, 245, 249, 253, 238, 255,
    258, 251, 247, 254, 260,
    243, 252, 257, 261, 263,
    241, 250, 258, 262, 255, 247
  ];

  new Chart(canvas, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [
        {
          label: 'Daily Attendance',
          data: dataPoints,
          borderColor: '#0d9488',
          borderWidth: 2.5,
          backgroundColor: gradient || 'rgba(13, 148, 136, 0.15)',
          fill: true,
          tension: 0.35,
          pointBackgroundColor: '#ffffff',
          pointBorderColor: '#0d9488',
          pointBorderWidth: 2,
          pointRadius: 3,
          pointHoverRadius: 6,
          pointHoverBackgroundColor: '#0d9488',
          pointHoverBorderColor: '#ffffff',
          pointHoverBorderWidth: 2
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
          display: false
        },
        tooltip: {
          backgroundColor: '#0f172a',
          titleColor: '#f8fafc',
          bodyColor: '#94a3b8',
          padding: 10,
          cornerRadius: 8,
          displayColors: false,
          callbacks: {
            title: function(items) {
              return items[0].label + ', 2026';
            },
            label: function(context) {
              const count = context.parsed.y;
              const pct = ((count / 277) * 100).toFixed(1);
              return `Present: ${count} of 277 (${pct}%)`;
            }
          }
        }
      },
      scales: {
        x: {
          grid: {
            display: false
          },
          ticks: {
            font: { size: 11 },
            color: '#64748b',
            maxRotation: 0,
            autoSkip: true,
            maxTicksLimit: 7
          }
        },
        y: {
          min: 220,
          max: 280,
          grid: {
            color: 'rgba(226, 232, 240, 0.7)',
            borderDash: [4, 4]
          },
          ticks: {
            font: { size: 11 },
            color: '#64748b',
            stepSize: 15,
            callback: function(val) {
              return val + ' st';
            }
          }
        }
      }
    }
  });
});
