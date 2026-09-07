/**
 * Excuses JS — excuses.js
 * Unified tab switcher, file upload preview, slide-in review panel
 */

document.addEventListener('DOMContentLoaded', function() {
  // File upload click handler
  const dropZone = document.getElementById('file-drop-zone');
  const fileInput = document.getElementById('excuse-file');

  if (dropZone && fileInput) {
    dropZone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', handleFileSelect);
  }

  // Check URL param for tab (e.g. ?tab=submit)
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab === 'submit') {
    switchExcuseTab('submit');
  }

  // Close slide panel on Escape
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      closeReviewPanel();
    }
  });
});

/**
 * Switch between Review Queue and Submit Excuse Slip tabs in the top menu panel
 */
function switchExcuseTab(tab) {
  const reviewBtn = document.getElementById('tab-btn-review');
  const submitBtn = document.getElementById('tab-btn-submit');
  const reviewPanel = document.getElementById('excuse-panel-review');
  const submitPanel = document.getElementById('excuse-panel-submit');

  if (!reviewBtn || !submitBtn || !reviewPanel || !submitPanel) return;

  if (tab === 'submit') {
    reviewPanel.classList.add('hidden');
    submitPanel.classList.remove('hidden');

    submitBtn.style.background = 'var(--color-teal-500)';
    submitBtn.style.color = '#ffffff';
    submitBtn.classList.add('font-semibold');
    submitBtn.classList.remove('font-medium');

    reviewBtn.style.background = 'transparent';
    reviewBtn.style.color = 'var(--color-text-secondary)';
    reviewBtn.classList.remove('font-semibold');
    reviewBtn.classList.add('font-medium');
  } else {
    submitPanel.classList.add('hidden');
    reviewPanel.classList.remove('hidden');

    reviewBtn.style.background = 'var(--color-teal-500)';
    reviewBtn.style.color = '#ffffff';
    reviewBtn.classList.add('font-semibold');
    reviewBtn.classList.remove('font-medium');

    submitBtn.style.background = 'transparent';
    submitBtn.style.color = 'var(--color-text-secondary)';
    submitBtn.classList.remove('font-semibold');
    submitBtn.classList.add('font-medium');
  }
}

/**
 * Handle submit form submission
 */
function handleExcuseSubmit(e) {
  e.preventDefault();
  if (typeof APP !== 'undefined' && APP.showToast) {
    APP.showToast('Excuse slip submitted successfully! Pending admin review.', 'success');
  }
  // Reset form
  const form = document.getElementById('excuse-submit-form');
  if (form) form.reset();
  removeFile();

  // Switch back to Review Queue tab
  switchExcuseTab('review');
}

function handleFileSelect(e) {
  const file = e.target.files[0];
  if (!file) return;

  const preview = document.getElementById('file-preview');
  const fileName = document.getElementById('file-name');
  const dropZone = document.getElementById('file-drop-zone');

  if (preview && fileName) {
    fileName.textContent = file.name;
    preview.classList.remove('hidden');
  }
  if (dropZone) {
    dropZone.classList.add('hidden');
  }
}

function removeFile() {
  const preview = document.getElementById('file-preview');
  const dropZone = document.getElementById('file-drop-zone');
  const fileInput = document.getElementById('excuse-file');

  if (preview) preview.classList.add('hidden');
  if (dropZone) dropZone.classList.remove('hidden');
  if (fileInput) fileInput.value = '';
}

function openReviewPanel(slipId) {
  const panel = document.getElementById('slide-panel');
  if (panel) panel.classList.add('open');
}

function closeReviewPanel() {
  const panel = document.getElementById('slide-panel');
  if (panel) panel.classList.remove('open');
}
