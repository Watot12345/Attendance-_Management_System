var x = (i) => {
  throw TypeError(i);
};
var C = (i, t, e) => t.has(i) || x("Cannot " + e);
var T = (i, t, e) => t.has(i) ? x("Cannot add the same private member more than once") : t instanceof WeakSet ? t.add(i) : t.set(i, e);
var d = (i, t, e) => (C(i, t, "access private method"), e);
const R = `<button type="button" class="sonner-toast-close">
    <svg xmlns="http://www.w3.org/2000/svg"
         width="10"
         height="10"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="2"
         stroke-linecap="round"
         stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
        <path d="M18 6l-12 12" />
        <path d="M6 6l12 12" />
    </svg>
</button>
<div class="sonner-toast-content-container">
    {{ slot }}
    <button type="button" data-sonner-action-button="false">{{ action_label }}</button>
</div>

`, E = `<div data-toast-plain>{{ message }}</div>
`, P = `<div data-toast-description>
    <div data-title>{{title}}</div>
    <div data-description>{{description}}</div>
</div>`, H = `<div data-toast-container-horizontal data-toast-level="success">
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="currentColor"
  >
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    <path
      d="M17 3.34a10 10 0 1 1 -14.995 8.984l-.005 -.324l.005 -.324a10 10 0 0 1 14.995 -8.336zm-1.293 5.953a1 1 0 0 0 -1.32 -.083l-.094 .083l-3.293 3.292l-1.293 -1.292l-.094 -.083a1 1 0 0 0 -1.403 1.403l.083 .094l2 2l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l4 -4l.083 -.094a1 1 0 0 0 -.083 -1.32z"
    />
  </svg>
  <div data-toast-level-message>{{message}}</div>
</div>
`, z = `<div data-toast-container-horizontal data-toast-level="info">
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="currentColor"
  >
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    <path
      d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -19.995 .324l-.005 -.324l.004 -.28c.148 -5.393 4.566 -9.72 9.996 -9.72zm0 9h-1l-.117 .007a1 1 0 0 0 0 1.986l.117 .007v3l.007 .117a1 1 0 0 0 .876 .876l.117 .007h1l.117 -.007a1 1 0 0 0 .876 -.876l.007 -.117l-.007 -.117a1 1 0 0 0 -.764 -.857l-.112 -.02l-.117 -.006v-3l-.007 -.117a1 1 0 0 0 -.876 -.876l-.117 -.007zm.01 -3l-.127 .007a1 1 0 0 0 0 1.986l.117 .007l.127 -.007a1 1 0 0 0 0 -1.986l-.117 -.007z"
    />
  </svg>
  <div data-toast-level-message>{{message}}</div>
</div>
`, B = `<div data-toast-container-horizontal data-toast-level="warning">
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="currentColor"
  >
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    <path
      d="M12 1.67c.955 0 1.845 .467 2.39 1.247l.105 .16l8.114 13.548a2.914 2.914 0 0 1 -2.307 4.363l-.195 .008h-16.225a2.914 2.914 0 0 1 -2.582 -4.2l.099 -.185l8.11 -13.538a2.914 2.914 0 0 1 2.491 -1.403zm.01 13.33l-.127 .007a1 1 0 0 0 0 1.986l.117 .007l.127 -.007a1 1 0 0 0 0 -1.986l-.117 -.007zm-.01 -7a1 1 0 0 0 -.993 .883l-.007 .117v4l.007 .117a1 1 0 0 0 1.986 0l.007 -.117v-4l-.007 -.117a1 1 0 0 0 -.993 -.883z"
    />
  </svg>
  <div data-toast-level-message>{{message}}</div>
</div>
`, L = `<div data-toast-container-horizontal data-toast-level="error">
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="currentColor"
  >
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    <path
      d="M17 3.34a10 10 0 1 1 -15 8.66l.005 -.324a10 10 0 0 1 14.995 -8.336m-5 11.66a1 1 0 0 0 -1 1v.01a1 1 0 0 0 2 0v-.01a1 1 0 0 0 -1 -1m0 -7a1 1 0 0 0 -1 1v4a1 1 0 0 0 2 0v-4a1 1 0 0 0 -1 -1"
    />
  </svg>
  <div data-toast-level-message>{{message}}</div>
</div>
`, D = `<div data-toast-container-horizontal>
  <div data-toast-promise-running data-show="true"></div>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width="20"
    height="20"
    viewBox="0 0 24 24"
    fill="currentColor"
    data-toast-promise-completed 
    data-show="false"
  >
    <path stroke="none" d="M0 0h24v24H0z" fill="none" />
    <path
      d="M17 3.34a10 10 0 1 1 -14.995 8.984l-.005 -.324l.005 -.324a10 10 0 0 1 14.995 -8.336zm-1.293 5.953a1 1 0 0 0 -1.32 -.083l-.094 .083l-3.293 3.292l-1.293 -1.292l-.094 -.083a1 1 0 0 0 -1.403 1.403l.083 .094l2 2l.094 .083a1 1 0 0 0 1.226 0l.094 -.083l4 -4l.083 -.094a1 1 0 0 0 -.083 -1.32z"
    />
  </svg>
  <div data-toast-promise-message></div>
</div>
`;
function M(i, t) {
  return i.replace(/{{ ?(\w+) ?}}/g, (h, n) => n in t ? t[n] : "").trim();
}
function c(i, t) {
  const e = M(i, t);
  return R.replace(/{{ ?slot ?}}/g, e.trim());
}
var v, y, b;
class A {
  constructor(t) {
    T(this, v);
    this.id = `toast-${Math.random().toString(26).substring(4)}-${Date.now()}`, this.options = t, this.toast = document.createElement("li"), this.toast.setAttribute("id", this.id), this.setXPosition(t.xPosition || "right"), this.setYPosition(t.yPosition || "bottom"), this.isExpanded = !1, this.hidden = !1, this.timeStarted = Date.now(), this.removalTimer = null, this.lastRemovalPaused = Date.now(), this.duration = t.duration || 0, this.remainingTimeToRemove = this.duration, this.height = 0, this.onUpdate = null, this.onClose = null, this.onRemove = null, this.canRemove = !0, d(this, v, y).call(this);
  }
  get element() {
    return this.toast;
  }
  updateHeight() {
    this.height = this.toast.getBoundingClientRect().height;
  }
  setCollapsedHeight(t) {
    this.toast.style.setProperty("--collapsed-height", `${t}px`);
  }
  setFront(t) {
    this.isFront = t, this.toast.dataset.front = t.toString();
  }
  setXPosition(t) {
    this.xPosition = t, this.toast.dataset.xPosition = t;
  }
  setYPosition(t) {
    this.yPosition = t, this.toast.dataset.yPosition = t;
  }
  setIndex(t) {
    this.index = t, this.element.style.setProperty("--index", String(t));
  }
  show() {
    this.toast.dataset.hidden = "false", this.hidden = !1;
  }
  hide() {
    this.toast.dataset.hidden = "true", this.hidden = !0;
  }
  setMounted() {
    setTimeout(() => {
      this.toast.dataset.mounted = "true";
    }, 10), this.canRemove && d(this, v, b).call(this);
  }
  setSpaceAbove(t) {
    this.element.style.setProperty("--space-above", `${t}px`);
  }
  setExpanded() {
    this.isExpanded = !0, this.toast.dataset.expanded = "true", this.duration > 0 && this.canRemove && this.pauseRemoval();
  }
  setCollapsed() {
    this.isExpanded = !1, this.toast.dataset.expanded = "false", this.duration > 0 && this.canRemove && !this.removalTimer && this.resumeRemoval();
  }
  setTheme(t) {
    this.toast.dataset.theme = t;
  }
  pauseRemoval() {
    this.removalTimer && clearTimeout(this.removalTimer), this.removalTimer = null, this.remainingTimeToRemove = Math.max(
      0,
      this.remainingTimeToRemove - (Date.now() - this.timeStarted)
    );
  }
  resumeRemoval() {
    this.removalTimer = setTimeout(() => {
      this.remove(), this.removalTimer = null;
    }, this.remainingTimeToRemove + 1e3), this.timeStarted = Date.now();
  }
  remove() {
    var t;
    this.hide(), (t = this.onClose) == null || t.call(this, this.toast.id), this.removalTimer && clearTimeout(this.removalTimer), setTimeout(() => {
      this.element.remove(), this.onRemove && (this.onRemove(this.toast.id), this.onClose = null, this.onUpdate = null, this.onRemove = null);
    }, 500);
  }
}
v = new WeakSet(), y = function() {
  var e, h;
  switch (this.options.type) {
    case "plain":
      this.toast.innerHTML = c(E, {
        id: this.id,
        message: this.options.message || ""
      });
      break;
    case "description":
      this.toast.innerHTML = c(P, {
        id: this.id,
        title: this.options.message || "",
        description: this.options.description || ""
      });
      break;
    case "success":
      this.toast.innerHTML = c(H, {
        id: this.id,
        message: this.options.message || ""
      });
      break;
    case "info":
      this.toast.innerHTML = c(z, {
        id: this.id,
        message: this.options.message || ""
      });
      break;
    case "warning":
      this.toast.innerHTML = c(B, {
        id: this.id,
        message: this.options.message || ""
      });
      break;
    case "error":
      this.toast.innerHTML = c(L, {
        id: this.id,
        message: this.options.message || ""
      });
      break;
    case "custom":
      if (!this.options.template_id)
        throw new Error("Custom toasts require a template_id");
      const n = document.getElementById(this.options.template_id);
      if (!n)
        throw new Error("Template not found: " + this.options.template_id);
      let a = this.options.toastData || {};
      a.id = this.id, this.toast.innerHTML = c(n.innerHTML, a);
      break;
    case "promise":
      let l = function() {
        var p, f, g;
        (p = this.toast.querySelector("[data-toast-promise-running]")) == null || p.setAttribute("data-show", "false"), (f = this.toast.querySelector("[data-toast-promise-completed]")) == null || f.setAttribute("data-show", "true"), this.canRemove = !0, d(this, v, b).call(this), this.isExpanded && this.pauseRemoval(), (g = this.onUpdate) == null || g.call(this, this.id);
      }, r = function(p) {
        var g;
        const f = (g = this.toast) == null ? void 0 : g.querySelector(
          "[data-toast-promise-message]"
        );
        f.innerHTML = p;
      };
      this.canRemove = !1, this.toast.innerHTML = c(D, {
        id: this.id,
        message: this.options.message || ""
      });
      const s = this.options.promiseOptions;
      r.bind(this)((s == null ? void 0 : s.loadingMessage) || ""), s == null || s.promise.then(() => {
        r.bind(this)(
          (s == null ? void 0 : s.successMessage) || (s == null ? void 0 : s.loadingMessage) || ""
        ), l.bind(this)();
      }).catch(() => {
        r.bind(this)(
          (s == null ? void 0 : s.errorMessage) || (s == null ? void 0 : s.loadingMessage) || ""
        ), l.bind(this)();
      });
      break;
  }
  this.toast.dataset.sonnerToast = "", this.toast.dataset.theme = this.options.theme || "light", this.toast.dataset.mounted = "false", this.toast.dataset.hidden = "false", this.toast.dataset.expanded = "false", this.toast.dataset.xPosition = this.xPosition, this.toast.dataset.yPosition = this.yPosition, this.toast.dataset.type = this.options.type, this.toast.dataset.richColors = this.options.useRichColors ? "true" : "false", this.options.closeButton && (this.toast.style.setProperty(
    "--close-button-display",
    "var(--close-button-visible-display)"
  ), (e = this.toast.querySelector(".sonner-toast-close")) == null || e.addEventListener("click", (n) => {
    n.stopPropagation(), this.remove(), this.removalTimer && clearTimeout(this.removalTimer);
  }));
  const t = this.toast.querySelector(
    "[data-sonner-action-button]"
  );
  this.options.action ? (t.setAttribute("data-sonner-action-button", "true"), t.innerHTML = M(t.innerHTML, {
    action_label: ((h = this.options.action) == null ? void 0 : h.label) || "Action"
  }), t.addEventListener("click", () => {
    var a;
    let n = (a = this.options.action) == null ? void 0 : a.onClick();
    (n == null || n == null || n !== !1) && this.remove();
  })) : t.remove();
}, b = function() {
  this.duration > 0 && (this.removalTimer && clearTimeout(this.removalTimer), this.removalTimer = setTimeout(() => {
    this.remove(), this.removalTimer = null;
  }, this.duration));
};
var m, k, w;
class S {
  constructor() {
    T(this, m);
    this.toasts = [];
    const t = document.getElementById("sonner-toast-container");
    if (!t)
      throw new Error("No container found");
    this.container = t, this.maxToasts = parseInt(this.container.getAttribute("max-toasts") || "3"), this.isToastsExpanded = (this.container.getAttribute("expanded") || "false") === "true", this.expandedByDefault = this.isToastsExpanded, this.expandedByDefault || (this.container.addEventListener(
      "mouseenter",
      d(this, m, k).bind(this)
    ), this.container.addEventListener(
      "mouseleave",
      d(this, m, w).bind(this)
    ), this.container.addEventListener("mouseout", d(this, m, w).bind(this)), this.container.addEventListener(
      "mousemove",
      d(this, m, w).bind(this)
    ));
    let e = this.container.getAttribute("close-button") || "false";
    this.enableCloseButton = e == "true", this.container.getAttribute("theme") == "system" && window.matchMedia(
      "(prefers-color-scheme: dark)"
    ).addEventListener("change", (a) => {
      this.refresh();
    });
  }
  get isDarkTheme() {
    return this.container.getAttribute("theme") == "system" ? window.matchMedia(
      "(prefers-color-scheme: dark)"
    ).matches : this.container.getAttribute("theme") == "dark";
  }
  get positions() {
    const t = this.container.getAttribute("position") || "bottom-right", [e, h] = t.split("-");
    return {
      xPosition: h,
      yPosition: e
    };
  }
  create(t) {
    const e = this.container, h = e.getAttribute("duration");
    if (!t.duration && h && (t.duration = parseInt(h)), (t.closeButton == null || t.closeButton == null) && (t.closeButton = this.enableCloseButton), t.theme == null || t.theme == null) {
      let a = this.container.getAttribute("theme");
      (a == "light" || a == "dark") && (t.theme = a);
    }
    (t.useRichColors == null || t.useRichColors == null) && this.container.getAttribute("rich-colors") == "true" && (t.useRichColors = !0);
    const n = new A({
      ...t
    });
    e.appendChild(n.element), n.updateHeight(), this.toasts.push(n), this.refresh(), n.setMounted(), n.onUpdate = (a) => {
      this.refresh();
    }, n.onClose = (a) => {
      this.toasts = this.toasts.filter((l) => l.id != a), this.refresh();
    }, n.onRemove = (a) => {
    }, this.expandedByDefault && n.setExpanded();
  }
  expand() {
    for (const t of this.toasts)
      t.setExpanded();
    this.isToastsExpanded = !0;
  }
  collapse() {
    for (const t of this.toasts)
      t.setCollapsed();
    this.isToastsExpanded = !1;
  }
  refresh() {
    if (this.toasts.length === 0)
      return;
    const { xPosition: t, yPosition: e } = this.positions, h = this.isDarkTheme;
    this.toasts.forEach((r, s) => {
      let p = this.toasts.length - s;
      r.setFront(!1), r.setIndex(p), r.setXPosition(t), r.setYPosition(e), r.setTheme(h ? "dark" : "light"), p > this.maxToasts ? r.hide() : r.show();
    });
    let n = this.toasts[this.toasts.length - 1], a = 0, l = 0;
    for (let r = this.toasts.length - 1; r >= 0; r--) {
      const s = this.toasts[r];
      s.hidden || (a += l, s.setCollapsedHeight(n.height), s.setSpaceAbove(a), l = s.height + 10);
    }
    n.setFront(!0);
  }
}
m = new WeakSet(), k = function(t) {
  this.expand();
}, w = function(t) {
  if (this.toasts.length === 0)
    return;
  const e = [
    ...Array.from(this.toasts).map((s) => s.element.getBoundingClientRect())
  ], h = Math.min(...e.map((s) => s.left)), n = Math.min(...e.map((s) => s.top)), a = Math.max(...e.map((s) => s.right)), l = Math.max(...e.map((s) => s.bottom));
  t.clientX >= h && t.clientX <= a && t.clientY >= n && t.clientY <= l || this.collapse();
};
let o;
typeof document < "u" && document.addEventListener("DOMContentLoaded", () => {
  o = new S();
});
function u(i, t = {}) {
  o == null || o.create({
    message: i,
    type: "plain",
    action: t.action,
    duration: t.duration
  });
}
u.message = function(i, t, e = {}) {
  o == null || o.create({
    type: "description",
    message: i,
    description: t,
    action: e.action,
    duration: e.duration
  });
};
u.info = function(i, t = {}) {
  o == null || o.create({
    type: "info",
    message: i,
    action: t.action,
    duration: t.duration
  });
};
u.success = function(i, t = {}) {
  o == null || o.create({
    type: "success",
    message: i,
    action: t.action,
    duration: t.duration
  });
};
u.warning = function(i, t = {}) {
  o == null || o.create({
    type: "warning",
    message: i,
    action: t.action,
    duration: t.duration
  });
};
u.error = function(i, t = {}) {
  o == null || o.create({
    type: "error",
    message: i,
    action: t.action,
    duration: t.duration
  });
};
u.promise = function(i, t, e = {}) {
  o == null || o.create({
    type: "promise",
    promiseOptions: {
      promise: i,
      loadingMessage: t.loading,
      successMessage: t.success,
      errorMessage: t.error
    },
    action: e.action,
    duration: e.duration
  });
};
u.custom = function(i, t, e = {}) {
  o == null || o.create({
    type: "custom",
    toastData: t,
    template_id: i,
    action: e.action,
    duration: e.duration
  });
};
typeof window < "u" && (window.toast = u);
export {
  S as Toaster,
  u as toast
};
