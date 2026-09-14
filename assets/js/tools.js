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
 * Open Certificate in New Browser Tab Window
 */
function openToolsCertificateTab(studentName, grade, period) {
  const printWindow = window.open('', '_blank');
  if (!printWindow) {
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast('Pop-up was blocked. Please allow pop-ups for this site.', 'warning');
    }
    return;
  }

  const parts = String(grade || '').split(/[—–-]/);
  const gradeLevel = parts[0] ? parts[0].trim() : 'Secondary Education';
  const sectionLabel = parts[1] ? parts[1].trim() : (grade || 'General Section');
  const issueDate = new Date().toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });

  const docHtml = `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Certificate of Perfect Attendance - ${escapeToolsHtml(studentName)}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800;900&family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,400;1,600&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      background-color: #0b1120;
      color: #1e293b;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 24px 16px;
      -webkit-font-smoothing: antialiased;
    }
    .action-bar {
      position: sticky;
      top: 16px;
      z-index: 1000;
      background: rgba(15, 23, 42, 0.92);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.15);
      border-radius: 9999px;
      padding: 10px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 28px;
      box-shadow: 0 20px 30px -10px rgba(0, 0, 0, 0.5);
    }
    .action-btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 18px;
      border-radius: 9999px;
      font-size: 12.5px;
      font-weight: 700;
      cursor: pointer;
      border: none;
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .btn-print {
      background: linear-gradient(135deg, #d97706, #b45309);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(180, 83, 9, 0.4);
    }
    .btn-print:hover {
      background: linear-gradient(135deg, #b45309, #92400e);
      transform: translateY(-1px);
    }
    .btn-close {
      background: rgba(255, 255, 255, 0.1);
      color: #e2e8f0;
    }
    .btn-close:hover {
      background: rgba(255, 255, 255, 0.2);
      color: #ffffff;
    }
    .action-tip {
      font-size: 11px;
      color: #94a3b8;
      margin-left: 6px;
      padding-left: 14px;
      border-left: 1px solid rgba(255, 255, 255, 0.15);
    }
    .certificate-sheet {
      width: 100%;
      max-width: 1040px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
      position: relative;
      overflow: hidden;
    }
    .cert-frame {
      margin: 20px;
      background-color: #fffdf9;
      border: 8px double #b45309;
      outline: 2px solid #b45309;
      outline-offset: -12px;
      padding: 40px 48px;
      position: relative;
      box-shadow: inset 0 0 35px rgba(180, 83, 9, 0.04);
      text-align: center;
    }
    .cert-corner {
      position: absolute;
      width: 26px;
      height: 26px;
      border-color: #b45309;
      pointer-events: none;
    }
    .cert-corner-tl { top: 16px; left: 16px; border-top: 3px solid; border-left: 3px solid; }
    .cert-corner-tr { top: 16px; right: 16px; border-top: 3px solid; border-right: 3px solid; }
    .cert-corner-bl { bottom: 16px; left: 16px; border-bottom: 3px solid; border-left: 3px solid; }
    .cert-corner-br { bottom: 16px; right: 16px; border-bottom: 3px solid; border-right: 3px solid; }
    .cert-institution {
      font-family: 'Cinzel', serif;
      font-size: 21px;
      font-weight: 800;
      letter-spacing: 2px;
      color: #0f172a;
      text-transform: uppercase;
      line-height: 1.2;
    }
    .cert-college {
      font-size: 13px;
      font-weight: 800;
      letter-spacing: 1.5px;
      color: #b45309;
      text-transform: uppercase;
      margin-top: 3px;
    }
    .cert-address {
      font-size: 10px;
      color: #64748b;
      margin-top: 2px;
      letter-spacing: 0.5px;
    }
    .cert-badge-wrap { margin: 14px 0 10px 0; }
    .cert-badge {
      display: inline-block;
      padding: 4px 18px;
      border-radius: 9999px;
      background: #fef3c7;
      border: 1px solid #f59e0b;
      color: #92400e;
      font-size: 10.5px;
      font-weight: 800;
      letter-spacing: 1.5px;
      text-transform: uppercase;
    }
    .cert-title {
      font-family: 'Playfair Display', serif;
      font-size: 30px;
      font-weight: 900;
      letter-spacing: 1.5px;
      color: #0f172a;
      text-transform: uppercase;
      margin-top: 8px;
      line-height: 1.2;
    }
    .cert-divider {
      width: 140px;
      height: 2px;
      background: linear-gradient(90deg, transparent, #b45309, transparent);
      margin: 8px auto 0 auto;
    }
    .cert-present-text {
      font-family: 'Playfair Display', serif;
      font-style: italic;
      font-size: 13px;
      color: #475569;
      margin: 12px 0 8px 0;
    }
    .cert-student-name {
      font-family: 'Playfair Display', serif;
      font-size: 32px;
      font-weight: 800;
      color: #1e3a8a;
      letter-spacing: 1px;
      display: inline-block;
      padding: 0 28px 4px 28px;
      border-bottom: 2.5px solid #d97706;
      margin-bottom: 8px;
    }
    .cert-meta-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      flex-wrap: wrap;
      margin: 12px auto 16px auto;
    }
    .cert-meta-pill {
      font-size: 11px;
      color: #334155;
      background: #f1f5f9;
      padding: 5px 14px;
      border-radius: 9999px;
      border: 1px solid #e2e8f0;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .cert-meta-section {
      background: #fef3c7;
      border-color: #fcd34d;
      color: #92400e;
      box-shadow: 0 1px 3px rgba(180, 83, 9, 0.1);
    }
    .cert-citation {
      font-family: 'Playfair Display', serif;
      font-size: 14px;
      line-height: 1.75;
      color: #334155;
      max-width: 740px;
      margin: 0 auto 20px auto;
    }
    .cert-signatures {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      margin-top: 28px;
      padding-top: 18px;
      border-top: 1px solid #fde68a;
    }
    .cert-sig-box { text-align: center; }
    .cert-sig-line {
      width: 180px;
      margin: 0 auto 4px auto;
      border-bottom: 1.5px solid #334155;
      padding-bottom: 2px;
      font-size: 12.5px;
      font-weight: 700;
      color: #0f172a;
    }
    .cert-sig-title {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.75px;
      color: #64748b;
      font-weight: 600;
    }
    .cert-footer-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 22px;
      padding-top: 10px;
      border-top: 1px dashed #e2e8f0;
      font-size: 9.5px;
      color: #94a3b8;
      font-family: 'JetBrains Mono', monospace;
    }
    @media print {
      @page { size: landscape; margin: 0.35in; }
      body { background-color: #ffffff !important; padding: 0 !important; }
      .action-bar { display: none !important; }
      .certificate-sheet { box-shadow: none !important; border-radius: 0 !important; max-width: 100% !important; }
      .cert-frame { margin: 0 !important; padding: 36px 40px !important; }
      * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    }
  </style>
</head>
<body>
  <div class="action-bar no-print">
    <button type="button" class="action-btn btn-print" onclick="window.print()">
      <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      <span>Print / Save as PDF</span>
    </button>
    <button type="button" class="action-btn btn-close" onclick="window.close()">
      <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      <span>Close Window</span>
    </button>
    <span class="action-tip">Best result: Landscape orientation with 'Background Graphics' enabled.</span>
  </div>

  <div class="certificate-sheet">
    <div class="cert-frame">
      <div class="cert-corner cert-corner-tl"></div>
      <div class="cert-corner cert-corner-tr"></div>
      <div class="cert-corner cert-corner-bl"></div>
      <div class="cert-corner cert-corner-br"></div>

      <div class="cert-header">
        <h1 class="cert-institution">Bestlink College of the Philippines</h1>
        <p class="cert-college">College of Computer Studies</p>
        <p class="cert-address">1071 Brgy. Kaligayahan Quirino Highway, Novaliches, Quezon City</p>
      </div>

      <div class="cert-badge-wrap">
        <span class="cert-badge">Office of Academic Affairs • Certificate of Recognition</span>
        <h2 class="cert-title">Certificate of Perfect Attendance</h2>
        <div class="cert-divider"></div>
      </div>

      <p class="cert-present-text">This prestigious honor is proudly conferred upon</p>

      <div class="cert-student-name">${escapeToolsHtml(studentName)}</div>

      <div class="cert-meta-row">
        <div class="cert-meta-pill">
          <span style="font-size:9.5px;font-weight:800;color:#64748b;">LEVEL:</span>
          <span style="font-weight:700;color:#0f172a;">${escapeToolsHtml(gradeLevel)}</span>
        </div>
        <div class="cert-meta-pill cert-meta-section">
          <span style="font-size:9.5px;font-weight:800;color:#b45309;">CLASS SECTION:</span>
          <span style="font-weight:700;color:#78350f;">${escapeToolsHtml(sectionLabel)}</span>
        </div>
        <div class="cert-meta-pill">
          <span style="font-size:9.5px;font-weight:800;color:#64748b;">RECORD:</span>
          <span style="font-weight:700;color:#b45309;">100% ATTENDANCE</span>
        </div>
      </div>

      <p class="cert-citation">
        For achieving a <strong style="color:#92400e;">100% Attendance Record</strong> in <strong style="color:#78350f;">${escapeToolsHtml(sectionLabel)}</strong> 
        during <strong>${escapeToolsHtml(period)}</strong>.
      </p>

      <div class="cert-signatures">
        <div class="cert-sig-box">
          <div class="cert-sig-line">Office of the Registrar</div>
          <div class="cert-sig-title">Academic Records</div>
        </div>
        <div class="cert-sig-box">
          <div style="color:#d97706;font-size:14px;letter-spacing:4px;font-weight:900;margin-bottom:4px;">★ ★ ★</div>
          <div class="cert-sig-title">Official Seal</div>
        </div>
        <div class="cert-sig-box">
          <div class="cert-sig-line">${issueDate}</div>
          <div class="cert-sig-title">Date Conferred</div>
        </div>
      </div>

      <div class="cert-footer-bar">
        <span>Control No: BCP-ATT-2026-REG</span>
        <span>Official Academic Document • Bestlink College of the Philippines</span>
      </div>
    </div>
  </div>
</body>
</html>`;

  printWindow.document.open();
  printWindow.document.write(docHtml);
  printWindow.document.close();
  printWindow.focus();
}

function escapeToolsHtml(str) {
  if (str === null || str === undefined) return '';
  return String(str).replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
  })[m]);
}

/**
 * Preview Certificate in Modal
 */
function previewCertificate(studentName, grade, period) {
  if (typeof APP === 'undefined' || !APP.openModal) return;

  const parts = String(grade || '').split(/[—–-]/);
  const gradeLevel = parts[0] ? parts[0].trim() : 'Secondary Education';
  const sectionLabel = parts[1] ? parts[1].trim() : (grade || 'General Section');

  const bodyHTML = `
    <div class="text-center p-6 border-4 border-amber-300 rounded-2xl bg-amber-50/40 space-y-4 relative">
      <div class="w-16 h-16 mx-auto rounded-full bg-amber-100 flex items-center justify-center text-amber-600 shadow-sm">
        <svg class="w-9 h-9" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 3v4a5 5 0 005 5h4a5 5 0 005-5V3H5zm0 2H3a2 2 0 002 2v-2zm14 0h2a2 2 0 01-2 2V5zm-7 7v5m-4 4h8m-6-4h4"/></svg>
      </div>
      <h3 class="text-xs tracking-widest uppercase font-bold text-amber-700">Bestlink College of the Philippines</h3>
      <h2 class="text-2xl font-serif font-bold text-slate-800">Certificate of Perfect Attendance</h2>
      <p class="text-xs text-slate-500 italic">This certificate is proudly presented to</p>
      <p class="text-2xl font-bold font-serif text-blue-900 border-b-2 border-amber-400 inline-block px-6 pb-1">${escapeToolsHtml(studentName)}</p>
      
      <div class="flex items-center justify-center gap-2 pt-1">
        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 text-xs font-semibold border border-slate-200">${escapeToolsHtml(gradeLevel)}</span>
        <span class="px-3 py-0.5 rounded-full bg-amber-100 text-amber-900 text-xs font-bold border border-amber-300 shadow-2xs">${escapeToolsHtml(sectionLabel)}</span>
      </div>

      <p class="text-xs text-slate-600 max-w-md mx-auto mt-2 leading-relaxed">
        For achieving a <strong>100% Attendance Record</strong> during <strong>${escapeToolsHtml(period)}</strong>.
      </p>

      <div class="pt-4 flex justify-between text-xs text-slate-400 border-t border-amber-200">
        <span>Office of Academic Affairs</span>
        <span>Date: September 7, 2026</span>
      </div>
    </div>
  `;

  const safeStudent = JSON.stringify(studentName);
  const safeGrade = JSON.stringify(grade);
  const safePeriod = JSON.stringify(period);

  const footerHTML = `
    <button type="button" class="btn btn-secondary btn-sm" onclick="APP.closeModal()">Close</button>
    <button type="button" class="btn btn-primary btn-sm inline-flex items-center gap-1.5" onclick="openToolsCertificateTab(${safeStudent}, ${safeGrade}, ${safePeriod})">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
      Open in New Window / Print
    </button>
  `;

  APP.openModal('Certificate Preview', bodyHTML, footerHTML);
}
