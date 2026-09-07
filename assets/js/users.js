/**
 * Users JS — users.js
 * Role-dependent field show/hide, delete confirmation
 */

function toggleRoleFields(role) {
  const fields = {
    student: document.getElementById('student-fields'),
    teacher: document.getElementById('teacher-fields'),
    parent: document.getElementById('parent-fields')
  };

  // Hide all role-specific fields
  Object.values(fields).forEach(el => {
    if (el) el.classList.add('hidden');
  });

  // Show the selected role's fields
  if (fields[role]) {
    fields[role].classList.remove('hidden');
  }
}
