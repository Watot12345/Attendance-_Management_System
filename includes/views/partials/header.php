<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' — ' : ''; ?>Attendance Management System</title>
  <meta name="description" content="AI-Supported Attendance Management System — Bestlink College of the Philippines">

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Google Fonts (Plus Jakarta Sans + Inter) loaded via Project_theme.css @import -->

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>

  <!-- Project Theme (custom tokens, components) -->
  <link rel="stylesheet" href="/Attendance _Management_System/Project_theme.css">

  <!-- Page-specific CSS slot -->
  <?php if (isset($page_css)) echo $page_css; ?>
</head>
