/**
 * Lightweight SPA Router — Instant Sidebar Navigation
 * Intercepts sidebar nav clicks, fetches pages via AJAX,
 * swaps <main> content and page-level modals/scripts without full page reload.
 * Uses history.pushState for proper URL + back/forward support.
 */

(function () {
  'use strict';

  /* ── Progress Bar ──────────────────────────────────────────── */
  var bar = document.getElementById('spa-progress-bar');
  var barTimer = null;
  var barProgress = 0;

  function barStart() {
    if (!bar) return;
    clearInterval(barTimer);
    barProgress = 0;
    bar.style.transition = 'none';
    bar.style.opacity = '1';
    bar.style.width = '0%';
    bar.style.background = '';
    // Force reflow
    void bar.offsetHeight;
    bar.style.transition = 'width 0.25s ease';
    barProgress = 30;
    bar.style.width = barProgress + '%';
    barTimer = setInterval(function () {
      if (barProgress < 85) {
        barProgress += (85 - barProgress) * 0.12;
        bar.style.width = barProgress + '%';
      }
    }, 200);
  }

  function barFinish() {
    if (!bar) return;
    clearInterval(barTimer);
    bar.style.transition = 'width 0.15s ease, opacity 0.3s ease 0.15s';
    bar.style.width = '100%';
    setTimeout(function () {
      bar.style.opacity = '0';
      setTimeout(function () { bar.style.width = '0%'; }, 350);
    }, 150);
  }

  function barError() {
    if (!bar) return;
    clearInterval(barTimer);
    bar.style.transition = 'width 0.1s ease';
    bar.style.width = '100%';
    bar.style.background = '#f43f5e';
    setTimeout(function () {
      bar.style.opacity = '0';
      setTimeout(function () {
        bar.style.width = '0%';
        bar.style.background = '';
      }, 350);
    }, 400);
  }

  /* ── Persistent Global Elements Registry ───────────────────── */
  var GLOBAL_ELEMENT_IDS = [
    'spa-progress-bar',
    'sonner-toast-container',
    'global-confirm-modal',
    'confirmation-modal',
    'inactivity-warning-modal',
    'inactivityModal',
    'inactivity-modal',
    'global-app-preloader',
    'sidebar-overlay'
  ];

  function isGlobalElement(el) {
    if (!el || el.nodeType !== 1) return true;
    if (el.classList.contains('app-layout') || el.classList.contains('sidebar-overlay')) return true;
    if (el.id && GLOBAL_ELEMENT_IDS.indexOf(el.id) !== -1) return true;
    if (el.tagName === 'SCRIPT') {
      var src = el.getAttribute('src') || '';
      if (src.indexOf('vanilla-sonner') !== -1 ||
          src.indexOf('app.js') !== -1 ||
          src.indexOf('spa-router.js') !== -1) {
        return true;
      }
    }
    return false;
  }

  /* ── Helpers ───────────────────────────────────────────────── */
  function isSidebarLink(el) {
    var anchor = el.closest ? el.closest('#sidebar a') : null;
    if (!anchor) {
      var node = el;
      while (node && node !== document.body) {
        if (node.tagName === 'A' && node.closest('#sidebar')) { anchor = node; break; }
        node = node.parentElement;
      }
    }
    if (!anchor) return null;
    var href = anchor.getAttribute('href') || '';
    if (!href || href === '#' || href.indexOf('javascript') === 0 ||
        href.indexOf('logout') !== -1 || href.indexOf('//') === 0 ||
        href.indexOf('mailto') === 0) {
      return null;
    }
    try {
      var url = new URL(href, location.origin);
      if (url.origin !== location.origin) return null;
    } catch (_) {}
    return anchor;
  }

  function getMainEl(doc) {
    return (doc || document).querySelector('main') ||
           (doc || document).querySelector('[role="main"]');
  }

  function normalize(u) {
    try { return new URL(u, location.origin).pathname.replace(/\/$/, '') || '/'; }
    catch (_) { return u.replace(/\/$/, '') || '/'; }
  }

  function updateActiveNav(href) {
    var targetPath = normalize(href);
    var links = document.querySelectorAll('#sidebar a');
    links.forEach(function (link) {
      var linkPath = normalize(link.getAttribute('href') || '');
      var isActive = linkPath === targetPath;

      if (isActive) {
        link.classList.remove(
          'text-slate-300', 'hover:text-white', 'hover:bg-slate-800/80',
          'text-rose-400', 'hover:text-rose-300', 'hover:bg-rose-950/30',
          'text-blue-400', 'hover:text-blue-300', 'hover:bg-blue-950/30',
          'border', 'border-rose-500/20', 'border-blue-500/20'
        );
        if (link.classList.contains('text-rose-400')) {
          link.classList.add('bg-rose-600', 'text-white', 'shadow-md', 'shadow-rose-600/30', 'font-bold');
        } else {
          link.classList.add('bg-blue-600', 'text-white', 'shadow-md', 'shadow-blue-600/30', 'font-bold');
        }
        link.querySelectorAll('svg').forEach(function (svg) {
          svg.classList.remove('text-slate-400', 'text-rose-400', 'text-blue-400');
          svg.classList.add('text-white');
        });
      } else {
        link.classList.remove(
          'bg-blue-600', 'bg-rose-600', 'text-white',
          'shadow-md', 'shadow-blue-600/30', 'shadow-rose-600/30', 'font-bold'
        );
        link.classList.add('text-slate-300');
        link.querySelectorAll('svg').forEach(function (svg) {
          svg.classList.remove('text-white');
          svg.classList.add('text-slate-400');
        });
      }
    });
  }

  function cleanupPageElements() {
    var children = Array.from(document.body.children);
    children.forEach(function (el) {
      if (!isGlobalElement(el)) {
        el.remove();
      }
    });
  }

  function insertNewPageElements(doc) {
    var newChildren = Array.from(doc.body.children);
    newChildren.forEach(function (el) {
      if (!isGlobalElement(el) && el.tagName !== 'SCRIPT') {
        var clone = el.cloneNode(true);
        clone.setAttribute('data-spa-page-element', '1');
        document.body.appendChild(clone);
      }
    });
  }

  function collectPageScripts(doc) {
    var scripts = [];
    
    // External scripts in doc.head not yet loaded
    doc.head.querySelectorAll('script').forEach(function (s) {
      var src = s.getAttribute('src');
      if (src && !document.querySelector('head script[src="' + src + '"]')) {
        scripts.push(s);
      }
    });

    // All scripts in body (excluding persistent global bundles)
    doc.body.querySelectorAll('script').forEach(function (s) {
      var src = s.getAttribute('src') || '';
      if (src.indexOf('vanilla-sonner') !== -1 ||
          src.indexOf('app.js') !== -1 ||
          src.indexOf('spa-router.js') !== -1) {
        return;
      }
      scripts.push(s);
    });

    return scripts;
  }

  function executeScriptsSequentially(scripts) {
    var index = 0;
    function next() {
      if (index >= scripts.length) return Promise.resolve();
      var oldScript = scripts[index++];
      return new Promise(function (resolve) {
        var newScript = document.createElement('script');
        Array.from(oldScript.attributes).forEach(function (attr) {
          newScript.setAttribute(attr.name, attr.value);
        });
        if (oldScript.src) {
          if (document.querySelector('script[src="' + oldScript.src + '"]')) {
            resolve();
            return;
          }
          newScript.onload = newScript.onerror = function () {
            resolve();
          };
          document.head.appendChild(newScript);
        } else {
          newScript.textContent = oldScript.textContent;
          document.body.appendChild(newScript);
          resolve();
        }
      }).then(next);
    }
    return next();
  }

  function triggerPageReady(href) {
    try {
      document.dispatchEvent(new Event('DOMContentLoaded'));
    } catch (e) {
      var evt = document.createEvent('Event');
      evt.initEvent('DOMContentLoaded', true, true);
      document.dispatchEvent(evt);
    }

    try {
      document.dispatchEvent(new CustomEvent('spa:navigated', {
        detail: { href: href }
      }));
    } catch (_) {}

    if (window.APP && APP.inactivityManager) {
      APP.inactivityManager.lastActivityTime = Date.now();
    }
  }

  function scrollToTop() {
    var main = document.querySelector('main');
    if (main) main.scrollTop = 0;
    window.scrollTo(0, 0);
  }

  function fadeOut(el, cb) {
    el.style.transition = 'opacity 0.10s ease';
    el.style.opacity = '0';
    setTimeout(cb, 110);
  }

  function fadeIn(el) {
    el.style.transition = 'opacity 0.18s ease';
    el.style.opacity = '0';
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        el.style.opacity = '1';
      });
    });
  }

  /* ── Core navigate function ────────────────────────────────── */
  var _currentController = null;

  function navigate(href, pushState) {
    if (_currentController) {
      try { _currentController.abort(); } catch (_) {}
    }

    barStart();
    updateActiveNav(href);

    // Close mobile sidebar
    if (window.APP && APP.closeSidebar) APP.closeSidebar();

    var controller = null;
    var signal = null;
    if (typeof AbortController !== 'undefined') {
      controller = new AbortController();
      signal = controller.signal;
    }
    _currentController = controller;

    var fetchOptions = { headers: { 'X-SPA-Request': '1' } };
    if (signal) fetchOptions.signal = signal;

    fetch(href, fetchOptions)
      .then(function (res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.text();
      })
      .then(function (html) {
        _currentController = null;

        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');

        var newMain = getMainEl(doc);
        var currentMain = getMainEl(document);

        if (!newMain || !currentMain) {
          window.location.href = href;
          return;
        }

        if (pushState !== false) {
          history.pushState({ spa: true, href: href }, doc.title || '', href);
        }
        document.title = doc.title || document.title;

        fadeOut(currentMain, function () {
          // 1. Swap main content
          currentMain.innerHTML = newMain.innerHTML;
          currentMain.className = newMain.className;

          // 2. Clean up old page modals and insert new page modals
          cleanupPageElements();
          insertNewPageElements(doc);

          // 3. Collect all scripts from the fetched page
          var pageScripts = collectPageScripts(doc);

          // 4. Scroll to top & fade in
          scrollToTop();
          fadeIn(currentMain);
          barFinish();

          // 5. Execute all page scripts sequentially, then trigger DOMContentLoaded
          executeScriptsSequentially(pageScripts).then(function () {
            triggerPageReady(href);
          });
        });
      })
      .catch(function (err) {
        _currentController = null;
        if (err && err.name === 'AbortError') return;
        barError();
        window.location.href = href;
      });
  }

  /* ── Click interception ────────────────────────────────────── */
  document.addEventListener('click', function (e) {
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    if (e.defaultPrevented) return;
    var anchor = isSidebarLink(e.target);
    if (!anchor) return;
    var href = anchor.getAttribute('href');
    if (!href) return;

    // Same page — just scroll
    try {
      var target = new URL(href, location.origin);
      if (target.pathname === location.pathname && !target.search) {
        e.preventDefault();
        scrollToTop();
        return;
      }
    } catch (_) {}

    e.preventDefault();
    navigate(href, true);
  }, true);

  /* ── Back / Forward button ─────────────────────────────────── */
  window.addEventListener('popstate', function () {
    navigate(location.href, false);
  });

  /* ── Mark initial state ────────────────────────────────────── */
  try {
    history.replaceState({ spa: true, href: location.href }, document.title, location.href);
  } catch (_) {}

  /* ── Expose globally ───────────────────────────────────────── */
  window.spaNavigate = navigate;

}());
