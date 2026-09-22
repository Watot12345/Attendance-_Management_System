<?php
/**
 * Cross-Device Concurrent Login Approval Modal Component
 * includes/views/partials/device-approval-modal.php
 */
?>
<!-- Device Login Authorization Modal -->
<div id="device-approval-modal" 
     style="z-index: 999998 !important; backdrop-filter: blur(14px) !important; -webkit-backdrop-filter: blur(14px) !important; background-color: rgba(15, 23, 42, 0.82) !important;"
     class="fixed inset-0 z-[99998] flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-200" 
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="device-approval-title">
  
  <div id="device-approval-box" 
       style="z-index: 999999 !important; box-shadow: 0 30px 70px -15px rgba(0, 0, 0, 0.5), 0 0 0 1px rgba(255, 255, 255, 0.2) !important;"
       class="bg-white rounded-2xl shadow-2xl border border-slate-200/90 max-w-md w-full p-6 text-center transform scale-95 transition-all duration-200 relative overflow-hidden">
    
    <!-- Top Security Shield Badge -->
    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-amber-100 text-amber-600 ring-8 ring-amber-50 shadow-xs relative">
      <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
      <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-rose-400 opacity-75"></span>
        <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-rose-500"></span>
      </span>
    </div>

    <!-- Title & Question -->
    <h3 id="device-approval-title" class="text-lg sm:text-xl font-bold text-slate-900 tracking-tight mb-1">
      Someone is trying to log in
    </h3>
    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-4">
      A sign-in attempt was detected from another device. <strong>Is that you?</strong>
    </p>

    <!-- Attempt Metadata Box -->
    <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-3.5 text-left mb-4 space-y-2 text-xs">
      <div class="flex items-center justify-between">
        <span class="text-slate-400 font-medium flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
          Device / Browser
        </span>
        <strong id="device-approval-device-name" class="text-slate-800 font-semibold font-mono text-[11px] truncate max-w-[200px]">Chrome on Windows</strong>
      </div>

      <div class="flex items-center justify-between border-t border-slate-200/50 pt-1.5">
        <span class="text-slate-400 font-medium flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
          IP Address
        </span>
        <span id="device-approval-ip" class="text-slate-700 font-mono text-[11px]">127.0.0.1</span>
      </div>

      <div class="flex items-center justify-between border-t border-slate-200/50 pt-1.5">
        <span class="text-slate-400 font-medium flex items-center gap-1.5">
          <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          Time Detected
        </span>
        <span id="device-approval-time" class="text-slate-700 text-[11px]">Just now</span>
      </div>
    </div>

    <!-- Live Expiry Warning -->
    <div class="flex items-center justify-between text-[11px] text-slate-400 mb-4 px-1">
      <span>Auto-denies in:</span>
      <span class="font-mono font-bold text-amber-700 bg-amber-50 border border-amber-200 px-2 py-0.5 rounded-md">
        <span id="device-approval-countdown">5:00</span>
      </span>
    </div>

    <!-- Hidden Input for Active Request ID -->
    <input type="hidden" id="device-approval-request-id" value="">

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row items-stretch gap-2.5">
      <!-- Reject Button -->
      <button type="button" 
              id="device-approval-deny-btn" 
              onclick="APP.deviceApprovalManager.respond('reject')" 
              class="flex-1 py-2.5 px-4 rounded-xl border border-rose-200 bg-rose-50 hover:bg-rose-100 active:bg-rose-200 text-rose-700 font-bold text-xs transition-all duration-150 flex items-center justify-center gap-2 cursor-pointer shadow-2xs disabled:opacity-60 disabled:cursor-not-allowed">
        <svg id="device-approval-deny-icon" class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
        </svg>
        <svg id="device-approval-deny-spinner" class="w-4 h-4 animate-spin hidden text-rose-600 flex-shrink-0" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="device-approval-deny-text">No, Deny Access</span>
      </button>

      <!-- Approve Button -->
      <button type="button" 
              id="device-approval-approve-btn" 
              onclick="APP.deviceApprovalManager.respond('approve')" 
              class="flex-1 py-2.5 px-4 rounded-xl font-bold text-xs shadow-md transition-all duration-150 flex items-center justify-center gap-2 cursor-pointer bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white shadow-emerald-600/20 disabled:opacity-60 disabled:cursor-not-allowed">
        <svg id="device-approval-approve-icon" class="w-4 h-4 text-white flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <svg id="device-approval-approve-spinner" class="w-4 h-4 animate-spin hidden text-white flex-shrink-0" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span id="device-approval-approve-text">Yes, That's Me</span>
      </button>
    </div>

    <p class="text-[10px] text-slate-400 mt-2.5 text-center">
      Clicking "Yes" will log in the other device and sign out this session.
    </p>

  </div>
</div>
