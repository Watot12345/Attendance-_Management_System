  <!-- Toast Container -->
  <div id="toast-container"></div>

  <!-- Global JS -->
  <script src="<?php echo url('assets/js/app.js'); ?>"></script>

  <!-- Page-specific JS slot -->
  <?php if (isset($page_js)) echo $page_js; ?>
</body>
</html>
