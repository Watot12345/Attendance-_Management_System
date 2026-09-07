/**
 * Tools JS — tools.js
 * Unified tab switcher, awards calculation, row count display, certificate generation
 */

document.addEventListener('DOMContentLoaded', function() {
  // Check URL param for tab (e.g. ?tab=awards, ?tab=exports)
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab === 'exports') {
    switchToolsTab('exports');
  }

  // Export type row estimates
  document.querySelectorAll('input[name="export-type"]').forEach(radio => {
    radio.addEventListener('change', function() {
      const estRows = document.getElementById('est-rows');
      if (estRows) {
        const counts = {
          attendance: 312,
          tardy: 45,
          absence: 28,
          teacher: 150,
          excuses: 22,
          awards: 14
        };
        estRows.textContent = counts[this.value] || 0;
      }
    });
  });
});

/**
 * Switch between Awards and Export tabs
 */
function switchToolsTab(tab) {
  const awardsBtn = document.getElementById('tab-btn-awards');
  const exportsBtn = document.getElementById('tab-btn-exports');
  const awardsPanel = document.getElementById('tools-panel-awards');
  const exportsPanel = document.getElementById('tools-panel-exports');

  if (!awardsBtn || !exportsBtn || !awardsPanel || !exportsPanel) return;

  if (tab === 'exports') {
    awardsPanel.classList.add('hidden');
    exportsPanel.classList.remove('hidden');

    exportsBtn.style.background = 'var(--color-teal-500)';
    exportsBtn.style.color = '#ffffff';
    exportsBtn.classList.add('font-semibold');
    exportsBtn.classList.remove('font-medium');

    awardsBtn.style.background = 'transparent';
    awardsBtn.style.color = 'var(--color-text-secondary)';
    awardsBtn.classList.remove('font-semibold');
    awardsBtn.classList.add('font-medium');
  } else {
    exportsPanel.classList.add('hidden');
    awardsPanel.classList.remove('hidden');

    awardsBtn.style.background = 'var(--color-teal-500)';
    awardsBtn.style.color = '#ffffff';
    awardsBtn.classList.add('font-semibold');
    awardsBtn.classList.remove('font-medium');

    exportsBtn.style.background = 'transparent';
    exportsBtn.style.color = 'var(--color-text-secondary)';
    exportsBtn.classList.remove('font-semibold');
    exportsBtn.classList.add('font-medium');
  }
}

/**
 * Trigger award calculation and smooth scroll to results
 */
function showAwardResults() {
  const results = document.getElementById('award-results');
  if (results) {
    results.classList.remove('hidden');
    results.scrollIntoView({ behavior: 'smooth' });
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast('Awards calculated! 14 students qualify.', 'success');
    }
  }
}

/**
 * Preview Certificate in Modal
 */
function previewCertificate(studentName, grade, period) {
  if (typeof APP === 'undefined' || !APP.openModal) return;

  const bodyHTML = `
    <div class="text-center p-6 border-4 border-amber-300 rounded-xl bg-amber-50/50 space-y-4">
      <div class="text-4xl">🏆</div>
      <h3 class="text-xs tracking-widest uppercase font-bold text-amber-700">Bestlink College of the Philippines</h3>
      <h2 class="text-2xl font-serif font-bold text-slate-800">Certificate of Perfect Attendance</h2>
      <p class="text-xs text-slate-500">This certificate is proudly presented to</p>
      <p class="text-xl font-bold text-teal-700 underline decoration-teal-400 decoration-2">${studentName}</p>
      <p class="text-xs text-slate-600">${grade}</p>
      <p class="text-xs text-slate-500 mt-2">For achieving 100% punctuality and perfect attendance during <strong>${period}</strong>.</p>
      <div class="pt-4 flex justify-between text-xs text-slate-400 border-t border-amber-200">
        <span>Principal's Office</span>
        <span>Date: September 7, 2026</span>
      </div>
    </div>
  `;

  const footerHTML = `
    <button type="button" class="btn btn-secondary btn-sm" onclick="APP.closeModal()">Close</button>
    <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">🖨 Print Certificate</button>
  `;

  APP.openModal('Certificate Preview', bodyHTML, footerHTML);
}
