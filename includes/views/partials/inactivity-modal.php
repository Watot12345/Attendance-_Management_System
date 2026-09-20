<?php
/**
 * Inactivity Auto-Logout Warning Modal Component
 * includes/views/partials/inactivity-modal.php
 */
?>
<!-- Inactivity Auto-Logout Warning Modal -->
<div id="inactivity-warning-modal" 
     style="z-index: 999999 !important; backdrop-filter: blur(12px) !important; -webkit-backdrop-filter: blur(12px) !important; background-color: rgba(15, 23, 42, 0.75) !important;"
     class="fixed inset-0 z-[99999] flex items-center justify-center p-4 hidden opacity-0 transition-opacity duration-200" 
     role="dialog" 
     aria-modal="true" 
     aria-labelledby="inactivity-modal-title">
  
  <div id="inactivity-modal-box" 
       style="z-index: 1000000 !important; box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.15) !important;"
       class="bg-white rounded-2xl shadow-2xl border border-slate-200/90 max-w-md w-full p-6 text-center transform scale-95 transition-all duration-200 relative overflow-hidden">
    
    <!-- Clock / Inactivity Icon -->
    <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 bg-amber-100 text-amber-600 ring-8 ring-amber-50 shadow-xs">
      <svg class="w-8 h-8 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
      </svg>
    </div>

    <!-- Title -->
    <h3 id="inactivity-modal-title" class="text-lg font-bold text-slate-900 tracking-tight mb-1">
      Session Inactivity Warning
    </h3>

    <!-- Message Body -->
    <p class="text-xs sm:text-sm text-slate-500 leading-relaxed mb-4">
      You have been inactive for nearly 1 minute. For your security, you will be automatically signed out in:
    </p>

    <!-- Big Countdown Badge -->
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-rose-50 border-2 border-rose-500 text-rose-600 font-black text-2xl mb-6 shadow-inner tracking-tight">
      <span id="inactivity-countdown-timer">10</span>s
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center gap-3">
      <button type="button" 
              id="inactivity-logout-now-btn" 
              onclick="APP.inactivityManager.logoutNow()" 
              class="flex-1 py-2.5 px-4 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs transition shadow-2xs cursor-pointer">
        Sign Out Now
      </button>

      <button type="button" 
              id="inactivity-stay-logged-in-btn" 
              onclick="APP.inactivityManager.stayLoggedIn()" 
              class="flex-1 py-2.5 px-4 rounded-xl font-semibold text-xs shadow-md transition flex items-center justify-center gap-2 cursor-pointer bg-emerald-600 hover:bg-emerald-700 text-white shadow-emerald-600/20">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span>I'm Still Here</span>
      </button>
    </div>

  </div>
</div>
