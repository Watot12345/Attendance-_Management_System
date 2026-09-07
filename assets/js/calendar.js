/**
 * Calendar JS — calendar.js
 * Month navigation (static UI), day click detail modal
 */

function navigateMonth(direction) {
  // Static demo — no-op, would reload calendar grid via AJAX
  APP.toast('Month navigation will be connected to backend', 'info');
}

function showDayDetail(day, status) {
  const statusLabels = {
    present: 'Present',
    tardy: 'Tardy',
    absent: 'Absent',
    excused: 'Excused'
  };
  const statusColors = {
    present: 'badge-present',
    tardy: 'badge-tardy',
    absent: 'badge-absent',
    excused: 'badge-excused'
  };

  const bodyHTML = `
    <div class="space-y-3">
      <div class="flex justify-between">
        <span class="text-sm font-medium" style="color:var(--color-text-secondary)">Date</span>
        <span class="text-sm">Sep ${day}, 2026</span>
      </div>
      <div class="flex justify-between">
        <span class="text-sm font-medium" style="color:var(--color-text-secondary)">Status</span>
        <span class="badge ${statusColors[status]}">● ${statusLabels[status]}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-sm font-medium" style="color:var(--color-text-secondary)">Time In</span>
        <span class="text-sm">${status === 'absent' ? '—' : (status === 'tardy' ? '08:18 AM' : '08:02 AM')}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-sm font-medium" style="color:var(--color-text-secondary)">Time Out</span>
        <span class="text-sm">${status === 'absent' ? '—' : '04:15 PM'}</span>
      </div>
      <div class="flex justify-between">
        <span class="text-sm font-medium" style="color:var(--color-text-secondary)">Method</span>
        <span class="text-sm">${status === 'absent' ? '—' : 'RFID'}</span>
      </div>
      ${status === 'tardy' ? `
      <div class="flex justify-between">
        <span class="text-sm font-medium" style="color:var(--color-text-secondary)">Minutes Late</span>
        <span class="text-sm font-medium" style="color:var(--color-tardy)">+18</span>
      </div>` : ''}
      <div class="flex justify-between">
        <span class="text-sm font-medium" style="color:var(--color-text-secondary)">Excuse Slip</span>
        <span class="text-sm">${status === 'excused' ? 'Approved' : 'None submitted'}</span>
      </div>
    </div>
  `;

  const footerHTML = status !== 'excused' ? `
    <a href="/Attendance _Management_System/includes/views/dashboard/excuse-slips.php?tab=submit" class="btn btn-primary btn-sm">Submit Excuse Slip</a>
  ` : '';

  APP.openModal(`Attendance Detail — Sep ${day}`, bodyHTML, footerHTML);
}
