<!-- Empty State -->
<div class="text-center py-12">
  <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background:var(--color-teal-100)">
    <svg class="w-8 h-8" style="color:var(--color-teal-500)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
  </div>
  <h3 class="text-lg font-semibold mb-1" style="color:var(--color-text-primary)">
    <?php echo isset($empty_title) ? htmlspecialchars($empty_title) : 'No data found'; ?>
  </h3>
  <p class="text-sm mb-4" style="color:var(--color-text-secondary)">
    <?php echo isset($empty_message) ? htmlspecialchars($empty_message) : 'There are no records to display at this time.'; ?>
  </p>
  <?php if (isset($empty_action_url) && isset($empty_action_label)): ?>
  <a href="<?php echo htmlspecialchars($empty_action_url); ?>" class="btn btn-primary">
    <?php echo htmlspecialchars($empty_action_label); ?>
  </a>
  <?php endif; ?>
</div>
