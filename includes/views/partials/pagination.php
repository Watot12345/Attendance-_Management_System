<!-- Pagination Controls -->
<nav class="flex items-center justify-between py-3" aria-label="Pagination">
  <p class="text-sm" style="color:var(--color-text-secondary)">
    Showing <span class="font-medium" id="pagination-start">1</span>–<span class="font-medium" id="pagination-end">25</span>
    of <span class="font-medium" id="pagination-total">312</span> results
  </p>
  <div class="flex items-center gap-1">
    <button class="btn btn-secondary btn-sm" id="pagination-prev" disabled>← Prev</button>
    <button class="btn btn-primary btn-sm">1</button>
    <button class="btn btn-secondary btn-sm">2</button>
    <button class="btn btn-secondary btn-sm">3</button>
    <span class="px-2 text-sm" style="color:var(--color-text-muted)">…</span>
    <button class="btn btn-secondary btn-sm">13</button>
    <button class="btn btn-secondary btn-sm" id="pagination-next">Next →</button>
  </div>
</nav>
