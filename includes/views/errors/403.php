<?php require_once dirname(__DIR__, 2) . '/core/Router.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Access Denied — Attendance Management System</title>
  <link rel="stylesheet" href="<?php echo url('Project_theme.css'); ?>">
  <link rel="stylesheet" href="<?php echo url('assets/css/output.css'); ?>">
</head>
<body class="min-h-screen flex items-center justify-center" style="background:var(--color-surface)">
  <div class="text-center px-4">
    <div class="w-20 h-20 mx-auto mb-6 rounded-full flex items-center justify-center" style="background:#fee2e2">
      <svg class="w-10 h-10" style="color:var(--color-absent)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
    </div>
    <h1 class="text-5xl font-bold mb-2" style="color:var(--color-absent)">403</h1>
    <h2 class="text-xl font-semibold mb-2" style="color:var(--color-text-primary)">Access Denied</h2>
    <p class="text-sm mb-6" style="color:var(--color-text-secondary)">You don't have permission to access this page. Contact your administrator if you believe this is an error.</p>
    <a href="<?php echo url('dashboard'); ?>" class="btn btn-primary">← Back to Dashboard</a>
  </div>
</body>
</html>
