<!-- Flash/Toast Messages (rendered server-side, animated via JS) -->
<?php if (isset($flash_message)): ?>
<script>
  document.addEventListener('DOMContentLoaded', function() {
    APP.toast('<?php echo htmlspecialchars($flash_message); ?>', '<?php echo isset($flash_type) ? $flash_type : 'info'; ?>');
  });
</script>
<?php endif; ?>
