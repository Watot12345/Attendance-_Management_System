/**
 * Exports JS — exports.js
 * Radio toggle, format toggle, row count display
 */

// Placeholder — backend integration will update estimated row count
document.addEventListener('DOMContentLoaded', function() {
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
