<!-- Modal Shell (reusable) — hidden by default, shown via APP.openModal() -->
<div id="modal-backdrop" class="hidden" onclick="APP.closeModal(event)">
  <div class="modal-box" onclick="event.stopPropagation()" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <!-- Modal Header -->
    <div class="flex items-center justify-between mb-4">
      <h2 id="modal-title" class="text-lg font-semibold"></h2>
      <button type="button" onclick="APP.closeModal()" class="p-1.5 rounded-md hover:bg-gray-100" aria-label="Close modal">
        <svg class="w-5 h-5" style="color:var(--color-text-muted)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
      </button>
    </div>
    <!-- Modal Body (filled dynamically) -->
    <div id="modal-body"></div>
    <!-- Modal Footer (optional) -->
    <div id="modal-footer" class="mt-6 flex justify-end gap-3"></div>
  </div>
</div>
