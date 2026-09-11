  <!-- Reusable Global Confirmation Modal Component -->
  <?php require_once __DIR__ . '/confirmation-modal.php'; ?>

  <!-- Sonner Toast Container -->
  <ol id="sonner-toast-container"
      position="top-right"
      max-toasts="5"
      rich-colors="true"
      close-button="true"
      theme="light">
  </ol>

  <!-- Sonner Toast Library -->
  <script src="<?php echo url('assets/dist/vanilla-sonner.umd.min.js'); ?>"></script>

  <!-- Global JS -->
  <script src="<?php echo url('assets/js/app.js'); ?>"></script>

  <!-- Page-specific JS slot -->
  <?php if (isset($page_js)) echo $page_js; ?>
</body>
</html>
