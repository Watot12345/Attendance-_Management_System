<?php require_once dirname(__DIR__, 2) . '/core/Router.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — ' : ''; ?>Attendance Management System</title>
  <meta name="description" content="AI-Supported Attendance Management System — Bestlink College of the Philippines">


  <!-- Google Fonts (Plus Jakarta Sans + Inter) loaded via Project_theme.css @import -->

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

  <!-- Project Theme (custom tokens, components) -->
  <link rel="stylesheet" href="<?php echo url('Project_theme.css'); ?>">
  <link rel="stylesheet" href="<?php echo url('assets/css/output.css'); ?>">

  <!-- Page-specific CSS slot -->
  <?php if (isset($page_css)) echo $page_css; ?>

  <script>
  window.APP = window.APP || {};
  window.APP.baseUrl = '<?= url() ?>';
  window.url = function(path = '') {
    const base = window.APP.baseUrl.replace(/\/$/, '');
    const cleanPath = path.replace(/^\//, '');
    return cleanPath ? `${base}/${cleanPath}` : (base || '/');
  };
</script>
</head>
