/**
 * Scanner JS — scanner.js
 * QR scan overlay animation, direction toggle, auto-dismiss timer
 */

function showScanOverlay(status) {
  const overlay = document.getElementById('scan-overlay');
  const statusText = document.getElementById('scan-status-text');
  const countdownEl = document.getElementById('scan-countdown');
  if (!overlay) return;

  // Set status appearance
  overlay.className = '';
  overlay.classList.add('status-' + status);
  overlay.classList.remove('hidden');

  if (statusText) {
    statusText.textContent = status === 'tardy' ? 'TARDY' : 'PRESENT';
  }

  // Countdown auto-dismiss
  let count = 3;
  if (countdownEl) countdownEl.textContent = count;

  const timer = setInterval(() => {
    count--;
    if (countdownEl) countdownEl.textContent = count;
    if (count <= 0) {
      clearInterval(timer);
      overlay.classList.add('hidden');
    }
  }, 1000);
}

// Demo: click overlay to dismiss early
document.addEventListener('DOMContentLoaded', function() {
  const overlay = document.getElementById('scan-overlay');
  if (overlay) {
    overlay.addEventListener('click', function() {
      overlay.classList.add('hidden');
    });
  }
});
