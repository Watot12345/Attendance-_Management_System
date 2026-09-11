<?php
/**
 * Universal Confirmation Modal Component
 * Reusable throughout the entire application.
 * Triggered via JavaScript:
 *   - await APP.confirm({ title, message, type, confirmText, cancelText })
 *   - APP.confirm({ title, message, onConfirm: async () => { ... } })
 *   - APP.confirm("Are you sure?", () => { ... })
 *   - Or declarative: <button data-confirm="Delete this?" data-confirm-type="danger">
 */
?>
<!-- Universal Confirmation Modal (Component) -->
<div id="global-confirm-modal" 
     style="z-index: 999999 !important; backdrop-filter: blur(12px) !important; -webkit-backdrop-filter: blur(12px) !important; background-color: rgba(15, 23, 42, 0.72) !important;"
     class="fixed inset-0 z-[99999] flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-200" 
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="confirm-modal-title"
     onclick="if(event.target === this) APP.closeConfirmModal(false)">
  
  <div id="confirm-modal-box" 
       style="z-index: 1000000 !important; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.15) !important;"
       class="bg-white rounded-2xl shadow-2xl border border-slate-200/90 max-w-md w-full p-6 text-center transform scale-95 transition-all duration-200 relative overflow-hidden" 
       onclick="event.stopPropagation()">
    
    <!-- Close Icon Button -->
    <button type="button" 
            onclick="APP.closeConfirmModal(false)" 
            class="absolute top-4 right-4 text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer" 
            aria-label="Close dialog">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>

    <!-- Thematic Icon Wrapper (Danger, Warning, Info, Success) -->
    <div id="confirm-modal-icon-wrap" class="w-14 h-14 rounded-2xl flex items-center justify-center mx-auto mb-4 transition-all duration-200 bg-rose-100 text-rose-600 ring-8 ring-rose-50">
      
      <!-- Danger Icon (Trash / Alert) -->
      <svg data-icon-type="danger" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
      </svg>

      <!-- Warning Icon (Exclamation Triangle) -->
      <svg data-icon-type="warning" class="w-7 h-7 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>

      <!-- Info Icon (Information Circle) -->
      <svg data-icon-type="info" class="w-7 h-7 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>

      <!-- Success Icon (Checkmark Circle) -->
      <svg data-icon-type="success" class="w-7 h-7 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>

    <!-- Title -->
    <h3 id="confirm-modal-title" class="text-lg font-bold text-slate-800 tracking-tight mb-2">
      Confirm Action
    </h3>

    <!-- Message Body -->
    <div id="confirm-modal-message" class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-6 font-normal">
      Are you sure you want to perform this action?
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
      <button type="button" 
              id="confirm-modal-cancel-btn" 
              onclick="APP.closeConfirmModal(false)" 
              class="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition shadow-2xs cursor-pointer">
        Cancel
      </button>

      <button type="button" 
              id="confirm-modal-action-btn" 
              onclick="APP.handleConfirmModalAction()" 
              class="flex-1 py-2.5 px-4 rounded-xl font-semibold text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer bg-rose-600 hover:bg-rose-700 text-white shadow-rose-600/20 disabled:opacity-60 disabled:cursor-not-allowed">
        <span id="confirm-modal-btn-text">Confirm</span>
        <svg id="confirm-modal-btn-spinner" class="w-3.5 h-3.5 animate-spin hidden" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
      </button>
    </div>

  </div>
</div>
