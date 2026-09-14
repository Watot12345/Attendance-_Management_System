<!-- Global Application Loading Screen Component -->
<div id="global-app-preloader" class="global-preloader fixed inset-0 z-[9999999] flex flex-col items-center justify-center bg-slate-950 text-white select-none transition-all duration-300">
  <!-- Subtle Glowing Radial Ambient Lights -->
  <div class="absolute w-96 h-96 rounded-full bg-teal-500/10 blur-3xl pointer-events-none -top-20 -left-20 animate-pulse"></div>
  <div class="absolute w-96 h-96 rounded-full bg-indigo-600/10 blur-3xl pointer-events-none -bottom-20 -right-20 animate-pulse" style="animation-delay: 1s;"></div>

  <!-- Central Loader Content Box -->
  <div class="relative z-10 flex flex-col items-center text-center px-6 max-w-sm w-full animate-fade-in">
    
    <!-- Multi-ring Orbital Futuristic Spinner -->
    <div class="relative w-20 h-20 mb-5 flex items-center justify-center">
      <!-- Outer Rotating Gradient Ring -->
      <div class="absolute inset-0 rounded-full border-2 border-transparent border-t-teal-400 border-r-teal-500/50 animate-spin" style="animation-duration: 1.1s;"></div>
      
      <!-- Middle Reverse Pulsing Glow Ring -->
      <div class="absolute inset-1.5 rounded-full border-2 border-transparent border-b-indigo-400 border-l-blue-500/40 animate-spin" style="animation-duration: 1.8s; animation-direction: reverse;"></div>
      
      <!-- Inner Pulse Core -->
      <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-teal-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-teal-500/25 animate-pulse">
        <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
        </svg>
      </div>
    </div>

    <!-- Branding Text -->
    <div class="space-y-1 mb-4">
      <div class="text-[10px] uppercase font-bold tracking-widest text-teal-400">Bestlink College of the Philippines</div>
      <h2 id="global-loader-title" class="text-base font-extrabold text-white tracking-tight">AI Attendance System</h2>
      <p id="global-loader-subtitle" class="text-xs text-slate-400 font-medium">Initializing workspace & attendance services...</p>
    </div>

    <!-- Progress Indicator Bar -->
    <div class="w-44 h-1.5 bg-slate-900 rounded-full overflow-hidden border border-slate-800/80 relative shadow-inner">
      <div class="loader-progress-bar-shimmer absolute inset-0 bg-gradient-to-r from-transparent via-teal-400 to-transparent"></div>
    </div>
  </div>
</div>
