/**
 * Alerts JS — alerts.js
 * Tab switching between Alert History and Alert Settings
 */

document.addEventListener('DOMContentLoaded', function() {
  // Check URL param for tab (e.g. ?tab=settings, ?tab=history)
  const urlParams = new URLSearchParams(window.location.search);
  const tab = urlParams.get('tab');
  if (tab === 'settings') {
    switchAlertTab('settings');
  }
});

/**
 * Switch between Alert History and Alert Settings tabs
 */
function switchAlertTab(tab) {
  const historyBtn = document.getElementById('tab-btn-history');
  const settingsBtn = document.getElementById('tab-btn-settings');
  const historyPanel = document.getElementById('alerts-panel-history');
  const settingsPanel = document.getElementById('alerts-panel-settings');

  if (!historyBtn || !settingsBtn || !historyPanel || !settingsPanel) return;

  if (tab === 'settings') {
    historyPanel.classList.add('hidden');
    settingsPanel.classList.remove('hidden');

    settingsBtn.style.background = 'var(--color-teal-500)';
    settingsBtn.style.color = '#ffffff';
    settingsBtn.classList.add('font-semibold');
    settingsBtn.classList.remove('font-medium');

    historyBtn.style.background = 'transparent';
    historyBtn.style.color = 'var(--color-text-secondary)';
    historyBtn.classList.remove('font-semibold');
    historyBtn.classList.add('font-medium');
  } else {
    settingsPanel.classList.add('hidden');
    historyPanel.classList.remove('hidden');

    historyBtn.style.background = 'var(--color-teal-500)';
    historyBtn.style.color = '#ffffff';
    historyBtn.classList.add('font-semibold');
    historyBtn.classList.remove('font-medium');

    settingsBtn.style.background = 'transparent';
    settingsBtn.style.color = 'var(--color-text-secondary)';
    settingsBtn.classList.remove('font-semibold');
    settingsBtn.classList.add('font-medium');
  }
}
