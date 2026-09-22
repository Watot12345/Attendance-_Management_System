<?php
/**
 * Cross-Device Concurrent Login Approval Modal Component
 * includes/views/partials/device-approval-modal.php
 */
?>
<!-- Device Login Authorization Modal -->
<div id="device-approval-modal" 
     style="z-index: 999998 !important; backdrop-filter: blur(8px) !important; -webkit-backdrop-filter: blur(8px) !important; background-color: rgba(15, 23, 42, 0.65) !important;"
     class="fixed inset-0 z-[99998] flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-200" 
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="device-approval-title">
  
  <div id="device-approval-box" 
       style="z-index: 999999 !important;"
       class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-sm w-full p-6 text-center transform scale-95 transition-all duration-200 relative overflow-hidden">
    
    <!-- Minimalist Security Icon -->
    <div class="w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3.5 bg-slate-100 text-slate-700 border border-slate-200/80">
      <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
    </div>

    <!-- Title & Subtitle -->
    <h3 id="device-approval-title" class="text-base font-bold text-slate-900 tracking-tight mb-1">
      Someone is trying to log in
    </h3>
    <p class="text-xs text-slate-500 leading-relaxed mb-4">
      A sign-in attempt was detected from another device. Is that you?
    </p>

    <!-- Clean Metadata List -->
    <div class="bg-slate-50/80 border border-slate-200/70 rounded-xl p-3 text-left mb-3.5 space-y-2 text-xs">
      <div class="flex items-center justify-between">
        <span class="text-slate-400 font-medium">Device</span>
        <strong id="device-approval-device-name" class="text-slate-800 font-medium text-[11px] truncate max-w-[180px]">Chrome on Windows</strong>
      </div>

      <div class="flex items-center justify-between border-t border-slate-200/60 pt-1.5">
        <span class="text-slate-400 font-medium">IP Address</span>
        <span id="device-approval-ip" class="text-slate-700 font-mono text-[11px]">127.0.0.1</span>
      </div>

      <div class="flex items-center justify-between border-t border-slate-200/60 pt-1.5">
        <span class="text-slate-400 font-medium">Time</span>
        <span id="device-approval-time" class="text-slate-700 text-[11px]">Just now</span>
      </div>
    </div>

    <!-- Live Expiry Warning -->
    <div class="flex items-center justify-between text-[11px] text-slate-400 mb-4 px-0.5">
      <span>Auto-denies in:</span>
      <span class="font-mono font-semibold text-slate-700 bg-slate-100 border border-slate-200 px-2 py-0.5 rounded-md">
        <span id="device-approval-countdown">5:00</span>
      </span>
    </div>

    <!-- Hidden Input for Active Request ID -->
    <input type="hidden" id="device-approval-request-id" value="">

    <!-- Action Buttons -->
    <div class="flex items-center gap-2">
      <!-- Reject Button -->
      <button type="button" 
              id="device-approval-deny-btn" 
              onclick="APP.deviceApprovalManager.respond('reject')" 
              class="flex-1 py-2.5 px-3.5 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 active:bg-slate-100 text-slate-700 font-semibold text-xs transition flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed">
        <svg id="device-approval-deny-icon" class="w-3.5 h-3.5 text-slate-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <svg id="device-approval-deny-spinner" class="w-3.5 h-3.5 animate-spin hidden text-slate-500 flex-shrink-0" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="device-approval-deny-text">Deny Access</span>
      </button>

      <!-- Approve Button -->
      <button type="button" 
              id="device-approval-approve-btn" 
              onclick="APP.deviceApprovalManager.respond('approve')" 
              class="flex-1 py-2.5 px-3.5 rounded-xl font-semibold text-xs shadow-xs transition flex items-center justify-center gap-1.5 cursor-pointer bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white disabled:opacity-60 disabled:cursor-not-allowed">
        <svg id="device-approval-approve-icon" class="w-3.5 h-3.5 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg id="device-approval-approve-spinner" class="w-3.5 h-3.5 animate-spin hidden text-white flex-shrink-0" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="device-approval-approve-text">Yes, It's Me</span>
      </button>
    </div>

    <p class="text-[10px] text-slate-400 mt-2.5 text-center">
      Authorizing will sign in the new device and sign out this session.
    </p>

  </div>
</div>
