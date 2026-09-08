<!-- Flash/Toast Messages (rendered server-side, animated via JS) -->
<?php if (isset($flash_message)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof APP !== 'undefined' && APP.showToast) {
      APP.showToast(<?php echo json_encode($flash_message); ?>, <?php echo json_encode(isset($flash_type) ? $flash_type : 'info'); ?>);
    }
  });
</script>
<?php endif; ?>
