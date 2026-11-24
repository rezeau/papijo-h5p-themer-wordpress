/* Static parent-page Theme and native Density controller. GPL-3.0-or-later. No preference storage. */
(function (factory) {
  'use strict';
  if (typeof module === 'object' && module.exports) { module.exports = factory(); return; }
  var script = document.currentScript, api = factory();
  if (!script || window['pjh5pwp-theme-controller'] || window.pjh5pwpAnonymousAppearance) { return; }
  try {
    if (script.getAttribute('data-pjh5pwp-bootstrap') === '1') {
      window['pjh5pwp-theme-controller'] = api.bootstrap(window); return;
    }
    var controller = api.create(window, JSON.parse(script.getAttribute('data-pjh5pwp-theme')));
    if (controller) { window['pjh5pwp-theme-controller'] = controller; controller.start(); }
  } catch (error) { /* Missing/altered public attachment metadata stays inert. */ }
}(function () {
  'use strict';
  var properties = [
    'main-cta-base', 'secondary-cta-base', 'alternative-base', 'background', 'focus',
    'main-cta-light', 'main-cta-dark', 'contrast-cta', 'contrast-cta-white', 'contrast-cta-light',
    'secondary-cta-light', 'secondary-cta-dark', 'secondary-contrast-cta', 'secondary-contrast-cta-hover',
    'alternative-light', 'alternative-dark', 'alternative-darker', 'text-primary', 'text-secondary',
    'text-third', 'ui-base', 'stroke-1', 'stroke-2', 'stroke-3', 'feedback-correct-main',
    'feedback-correct-secondary', 'feedback-correct-third', 'feedback-incorrect-main',
    'feedback-incorrect-secondary', 'feedback-incorrect-third', 'feedback-neutral-main',
    'feedback-neutral-secondary', 'feedback-neutral-third'
  ].map(function (name) { return '--h5p-theme-' + name; });
  var expressions = [
    'color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)',
    'rgba(59, 130, 246, 0.15)'
  ]; // Exact expressions for this property in the immutable moodle-478fed30d2a4 catalog.
  var densityClasses = ['h5p-small', 'h5p-medium', 'h5p-large'];
  function exact(value, keys) {
    return value !== null && typeof value === 'object' && !Array.isArray(value) &&
      Object.keys(value).length === keys.length && keys.every(function (key) {
        return Object.prototype.hasOwnProperty.call(value, key);
      });
  }
  function matches(value, pattern) {
    return typeof value === 'string' && pattern.test(value) && !/[\r\n]/.test(value);
  }
  function colors(text) {
    if (typeof text !== 'string' || text.length > 8192) { return null; }
    var result = Object.create(null);
    if (text === '') { return result; }
    if (text.slice(-1) !== ';') { return null; }
    var parts = text.slice(0, -1).split(';');
    if (parts.length > properties.length) { return null; }
    for (var i = 0; i < parts.length; i++) {
      var colon = parts[i].indexOf(':'), name = parts[i].slice(0, colon), value = parts[i].slice(colon + 1);
      if (colon < 1 || properties.indexOf(name) === -1 || Object.prototype.hasOwnProperty.call(result, name) ||
          (!matches(value, /^#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/) &&
           !(name === '--h5p-theme-contrast-cta-light' && expressions.indexOf(value) !== -1))) { return null; }
      result[name] = value;
    }
    return result;
  }
  function validate(body, requestId, age) {
    if (!exact(body, ['version', 'request_id', 'refresh', 'appearance']) || body.version !== 1 ||
        body.request_id !== requestId || !matches(requestId, /^[a-f0-9]{32}$/) ||
        !exact(body.refresh, ['max_age_seconds', 'reuse']) || body.refresh.max_age_seconds !== 30 ||
        body.refresh.reuse !== false || !Number.isFinite(age) || age < 0 || age >= 30000) { return null; }
    var a = body.appearance;
    if (!exact(a, ['admission', 'theme', 'color_declarations', 'density']) ||
        !exact(a.admission, ['permitted', 'reason'])) { return null; }
    if (a.admission.permitted === false) {
      return ['snordian_active', 'runtime_policy', 'admission_unavailable', 'appearance_unavailable', 'authentication_changed'].indexOf(a.admission.reason) !== -1 &&
        a.theme === null && a.density === null && a.color_declarations === '' ? { suspended: true, reason: a.admission.reason } : null;
    }
    if (a.admission.permitted !== true || a.admission.reason !== null ||
        !exact(a.theme, ['source', 'kind', 'id', 'version']) ||
        ['personal', 'global', 'core'].indexOf(a.theme.source) === -1 ||
        !exact(a.density, ['selection', 'preserve_native']) ||
        ['original', 'small', 'medium', 'large'].indexOf(a.density.selection) === -1 ||
        a.density.preserve_native !== (a.density.selection === 'original')) { return null; }
    var theme = a.theme, palette = colors(a.color_declarations);
    if (!palette) { return null; }
    if (theme.kind === 'core') {
      if (theme.id !== null || theme.version !== null || a.color_declarations !== '') { return null; }
    } else if (theme.kind === 'builtin') {
      if (['daylight', 'lavender', 'mint', 'sunset', 'dark'].indexOf(theme.id) === -1 ||
          theme.version !== 'moodle-478fed30d2a4' || Object.keys(palette).length !== 33) { return null; }
    } else if (theme.kind === 'custom') {
      if (!matches(theme.id, /^[a-z][a-z0-9_]{0,63}$/) || theme.version !== null) { return null; }
    } else { return null; }
    return { suspended: false, colors: palette, density: a.density.selection };
  }
  function expectedAudience(win) {
    var scripts = win.document.querySelectorAll('script[data-pjh5pwp-bootstrap="1"]');
    var value = scripts.length === 1 ? scripts[0].getAttribute('data-pjh5pwp-audience') : null;
    return ['authenticated', 'default'].indexOf(value) >= 0 ? value : null;
  }
  function adminGrant(win) {
    try {
      var scripts = win.document.querySelectorAll('script[data-pjh5pwp-bootstrap="1"]');
      if (scripts.length !== 1) { return null; }
      return { content_id: scripts[0].getAttribute('data-pjh5pwp-admin-viewer'),
        nonce: scripts[0].getAttribute('data-pjh5pwp-admin-nonce') };
    } catch (error) { return null; }
  }
  function adminContext(win, grant) {
    try {
      if (!exact(grant, ['content_id', 'nonce']) || !matches(grant.content_id, /^[1-9][0-9]{0,9}$/) ||
          !matches(grant.nonce, /^[a-f0-9]{10}$/)) { return false; }
      var url = new win.URL(win.location.href), parts = url.search.slice(1).split('&'), head = adminGrant(win);
      return url.pathname.endsWith('/wp-admin/admin.php') && !url.hash && parts.length === 3 &&
        parts.every(function (part) { return /^(?:page=h5p|task=show|id=[1-9][0-9]{0,9})$/.test(part); }) &&
        Array.from(url.searchParams.keys()).length === new Set(url.searchParams.keys()).size &&
        url.searchParams.get('page') === 'h5p' && url.searchParams.get('task') === 'show' &&
        url.searchParams.get('id') === grant.content_id && head !== null &&
        head.content_id === grant.content_id && head.nonce === grant.nonce;
    } catch (error) { return false; }
  }
  function pageContext(win, grant) {
    return /\/wp-admin(?:\/|$)/.test(win.location.pathname) ? adminContext(win, grant) : grant === undefined;
  }
  function manifestValid(win, manifest) {
    var admin = manifest && Object.prototype.hasOwnProperty.call(manifest, 'admin_viewer');
    if (!exact(manifest, admin ? ['version', 'endpoint', 'players', 'admin_viewer'] : ['version', 'endpoint', 'players']) || manifest.version !== 1 ||
        !Array.isArray(manifest.players) || !manifest.players.length || manifest.players.length > 64) { return false; }
    if (admin && (!adminContext(win, manifest.admin_viewer) ||
        !manifest.players.some(function (p) { return p && p.content_id === manifest.admin_viewer.content_id; }))) { return false; }
    var endpoint = new win.URL(manifest.endpoint, win.location.href), seen = new Set();
    if (endpoint.origin !== win.location.origin || !/^https?:$/.test(endpoint.protocol) ||
        endpoint.username || endpoint.password || endpoint.search || endpoint.hash ||
        !endpoint.pathname.endsWith('/wp-admin/admin-ajax.php')) { return false; }
    return manifest.players.every(function (p) {
      if (!exact(p, ['content_id', 'embed', 'library']) || !matches(p.content_id, /^[1-9][0-9]{0,9}$/) ||
          ['div', 'iframe'].indexOf(p.embed) === -1 ||
          !matches(p.library, /^H5P\.[A-Za-z][A-Za-z0-9]* [0-9]{1,4}\.[0-9]{1,4}$/) || seen.has(p.content_id)) { return false; }
      seen.add(p.content_id); return true;
    });
  }
  function bootstrap(win) {
    var doc = win.document;
    var grant = /\/wp-admin(?:\/|$)/.test(win.location.pathname) ? adminGrant(win) : undefined;
    if (win.top !== win || win.H5PEditor || !pageContext(win, grant) ||
        doc.readyState !== 'loading' || !win.requestAnimationFrame || !win.cancelAnimationFrame ||
        !win.performance || !win.fetch || !win.MutationObserver || !win.AbortController) { return null; }
    var entries = new Map(), known = new Map(), jobs = new Map(), controller = null, failure = function () {}, closed = false, stopped = false;
    var interaction = 0;
    function interacted() { if (entries.size) { interaction++; } }
    ['pointerdown', 'keydown', 'focusin'].forEach(function (name) { doc.addEventListener(name, interacted, true); });
    var deadline = win.performance.now() + 15000, armed = 'pjh5pwp-readiness-armed';
    function finish(id, success) {
      var e = entries.get(id); if (!e) { return; }
      entries.delete(id);
      if (jobs.has(id)) { win.cancelAnimationFrame(jobs.get(id)); jobs.delete(id); }
      if (!success) { failure(id, !!e.returnWindow); }
      else { known.set(id, e); }
      e.node.removeAttribute('data-pjh5pwp-pending');
      if (e.node.getAttribute('aria-busy') === 'true') {
        if (e.busy === null) { e.node.removeAttribute('aria-busy'); } else { e.node.setAttribute('aria-busy', e.busy); }
      }
      if (e.status) {
        if (success) { e.status.hidden = true; }
        else { e.status.hidden = false; e.status.setAttribute('data-pjh5pwp-fallback', '1'); e.status.textContent = e.status.getAttribute('data-pjh5pwp-fallback-text') || 'Activity appearance unavailable; showing original styling.'; }
      }
      if (success && e.focus && e.focus.isConnected && interaction === e.interaction &&
          (doc.activeElement === doc.body || doc.activeElement === e.focus) && e.node.contains(e.focus)) {
        try { e.focus.focus({ preventScroll: true }); } catch (error) { /* Do not steal or retry focus. */ }
      }
      if (!entries.size) { win.clearTimeout(timer); doc.documentElement.classList.remove(armed); }
    }
    function failAll() { Array.from(entries.keys()).forEach(function (id) { finish(id, false); }); }
    function timeout() {
      closed = true; failAll(); doc.documentElement.classList.remove(armed);
    }
    var timer = win.setTimeout(timeout, 15000); // Install fail-open before enabling CSS.
    doc.documentElement.classList.add(armed);
    function start() {
      doc.removeEventListener('DOMContentLoaded', start);
      var nodes = Array.from(doc.querySelectorAll('[data-pjh5pwp-pending]'));
      var metadata = doc.querySelectorAll('[data-pjh5pwp-manifest]'), manifest = null;
      try { if (metadata.length === 1) { manifest = JSON.parse(metadata[0].getAttribute('data-pjh5pwp-manifest')); } } catch (error) {}
      var valid = false;
      try { valid = !closed && win.performance.now() < deadline && manifestValid(win, manifest); } catch (error) {}
      var ids = valid ? manifest.players.map(function (p) { return p.content_id; }) : [];
      nodes.forEach(function (node) {
        var id = node.getAttribute('data-pjh5pwp-pending');
        var status = doc.querySelectorAll('[data-pjh5pwp-status="' + (/^[0-9]+$/.test(id) ? id : '') + '"]');
        if (ids.indexOf(id) === -1 || nodes.filter(function (n) { return n.getAttribute('data-pjh5pwp-pending') === id; }).length !== 1) {
          node.removeAttribute('data-pjh5pwp-pending');
          if (status.length === 1) {
            if (!valid) { status[0].hidden = false; status[0].setAttribute('data-pjh5pwp-fallback', '1'); status[0].textContent = status[0].getAttribute('data-pjh5pwp-fallback-text') || 'Activity appearance unavailable; showing original styling.'; }
            else { status[0].hidden = true; }
          } return;
        }
        entries.set(id, { node: node, status: status.length === 1 ? status[0] : null, busy: node.getAttribute('aria-busy'), loading: status.length === 1 ? status[0].textContent : '' });
        if (status.length === 1) { status[0].hidden = false; }
        node.setAttribute('aria-busy', 'true');
      });
      if (!valid) { timeout(); return; }
      controller = create(win, manifest, gate);
      if (!controller) { failAll(); }
      else { controller.start(); }
      if (!entries.size) { win.clearTimeout(timer); doc.documentElement.classList.remove(armed); }
    }
    var gate = {
      failAll: failAll,
      onFailure: function (fn) { failure = fn; },
      pending: function (id) { return entries.has(id); },
      hasPending: function () { return entries.size > 0; },
      returnPending: function () { return Array.from(entries.values()).some(function (e) { return e.returnWindow; }); },
      hold: function (records, hidden) {
        if (stopped) { return false; }
        var held = false;
        records.forEach(function (record) {
          var q = record.q, id = q.p.content_id, old = known.get(id);
          if (!old || !old.node.isConnected || old.node !== (q.frame ? q.anchor.parentElement : q.root)) { return; }
          held = true;
          if (entries.has(id)) { return; }
          if (!entries.size) { deadline = hidden ? Infinity : win.performance.now() + 15000; }
          var focus = doc.activeElement;
          entries.set(id, { node: old.node, status: old.status, busy: old.node.getAttribute('aria-busy'), loading: old.loading,
            returnWindow: true, focus: focus && old.node.contains(focus) ? focus : null, interaction: interaction });
          old.node.setAttribute('data-pjh5pwp-pending', id); old.node.setAttribute('aria-busy', 'true');
          if (old.status) { old.status.hidden = false; old.status.removeAttribute('data-pjh5pwp-fallback'); old.status.textContent = old.loading; }
        });
        if (held) {
          doc.documentElement.classList.add(armed);
          if (!hidden) { gate.wake(); }
        }
        return held;
      },
      suspend: function () {
        // A mixed page can still have a slow first-render sibling: its original
        // head-start bound is never suspended/reset by another player's return.
        if (Array.from(entries.values()).every(function (e) { return e.returnWindow; })) { win.clearTimeout(timer); }
        jobs.forEach(function (job) { win.cancelAnimationFrame(job); }); jobs.clear();
      },
      wake: function () {
        if (!entries.size) { return; }
        if (!Number.isFinite(deadline)) { deadline = win.performance.now() + 15000; }
        win.clearTimeout(timer); timer = win.setTimeout(timeout, Math.max(0, deadline - win.performance.now()));
        if (win.performance.now() >= deadline) { timeout(); }
      },
      fail: function (id) { finish(id, false); },
      reveal: function (q, guard) {
        var id = q.p.content_id;
        if (!entries.has(id) || jobs.has(id)) { return; }
        if (win.performance.now() >= deadline) { timeout(); return; }
        jobs.set(id, win.requestAnimationFrame(function () {
          var height = q.frame ? parseFloat(q.frame.style.height) : 0;
          jobs.set(id, win.requestAnimationFrame(function () {
            jobs.delete(id);
            if (win.performance.now() >= deadline) { timeout(); return; }
            if (guard() && (!q.frame || (height > 1 && height === parseFloat(q.frame.style.height) && height === q.doc.body.scrollHeight))) { finish(id, true); }
          }));
        }));
      }
    };
    doc.addEventListener('DOMContentLoaded', start);
    return { stop: function () { stopped = true; closed = true; doc.removeEventListener('DOMContentLoaded', start); if (controller) { controller.stop(); } failAll(); win.clearTimeout(timer); doc.documentElement.classList.remove(armed);
      ['pointerdown', 'keydown', 'focusin'].forEach(function (name) { doc.removeEventListener(name, interacted, true); }); known.clear(); },
      refresh: function () { return controller ? controller.refresh() : Promise.resolve(); },
      diagnostics: function () { return controller ? controller.diagnostics() : []; } };
  }
  function create(win, manifest, readiness) {
    if (win.pjh5pwpAnonymousAppearance) { return null; }
    var audience = expectedAudience(win);
    try {
      if (!manifestValid(win, manifest) || win.top !== win || win.H5PEditor ||
          !pageContext(win, manifest.admin_viewer) ||
          !win.fetch || !win.crypto || !win.crypto.getRandomValues || !win.MutationObserver ||
          !win.AbortController || !win.performance) { return null; }
    } catch (error) { return null; }
    var doc = win.document, active = false, destroyed = false, generation = 0, descriptor = null;
    var request = null, renewal = null, expiry = null, queued = null, retry = null, retryIndex = 0;
    var lifecycle = null, lastRequest = -Infinity;
    var delays = [50, 100, 250, 500, 1000, 2000, 4000];
    var records = new Map(), observers = new Map(), frames = new Map();
    var quarantined = new WeakSet(), blocked = new WeakMap(), instanceRoots = new WeakMap(), diagnostics = new Map();
    var densityBlocked = new WeakSet();
    var removers = [];
    var initialFailed = new Set(), returnFailed = new Set(), returning = false;
    if (readiness) { readiness.onFailure(function (id, returnWindow) {
      (returnWindow ? returnFailed : initialFailed).add(id);
      records.forEach(function (record) { if (record.q.p.content_id === id) { restore(record); } });
      if (!readiness.hasPending() && !Array.from(records.values()).some(function (record) { return !initialFailed.has(record.q.p.content_id) && !returnFailed.has(record.q.p.content_id); })) {
        cancelRequest(); releaseAppearance(); // No failed-generation response can publish later.
      }
    }); }
    function now() { return win.performance.now(); }
    function visible() { return doc.visibilityState === 'visible'; }
    function listen(target, name, fn, capture) {
      target.addEventListener(name, fn, !!capture);
      return function () { target.removeEventListener(name, fn, !!capture); };
    }
    function note(p, code) { diagnostics.set(p.content_id, code); } // Public IDs and fixed codes only.
    function internalDocument(realm) {
      try {
        var childURL = new win.URL(realm.URL), parentURL = new win.URL(doc.URL);
        childURL.hash = ''; parentURL.hash = '';
        // document.open() may replace about:blank's document URL with the caller's URL.
        return childURL.href === 'about:blank' || childURL.href === parentURL.href;
      } catch (error) { return false; }
    }
    function qualify(p, detached) {
      try {
        if (win.H5PEditor || (win.H5P && win.H5P.externalEmbed === true)) { return null; }
        var selector = '[data-content-id="' + p.content_id + '"]';
        var anchors = doc.querySelectorAll('.h5p-content' + selector + ', iframe.h5p-iframe' + selector);
        var anchor = anchors.length === 1 ? anchors[0] : null;
        if (detached && !detached.isConnected && anchors.length === 0) { anchor = detached; }
        if (!anchor || anchor.ownerDocument !== doc ||
            anchor.closest('.h5p-editor, .h5peditor, [data-pjh5pwp-excluded]')) { return null; }
        var frame = p.embed === 'iframe' ? anchor : null, view = win, realm = doc, root = anchor;
        var wrapper = frame ? anchor.parentElement : anchor;
        if (!wrapper || (wrapper.parentElement && wrapper.parentElement.closest('.h5p-content, .h5p-iframe-wrapper'))) { return null; }
        if (frame) {
          if (!frame.matches('iframe.h5p-iframe.h5p-initialized') || frame.id !== 'h5p-iframe-' + p.content_id ||
              !frame.parentElement || !frame.parentElement.matches('.h5p-iframe-wrapper') ||
              frame.hasAttribute('srcdoc') || frame.hasAttribute('sandbox') || frame.getAttribute('src') !== 'about:blank') { return null; }
          // A non-about:blank src is rejected before touching the child browsing context.
          realm = frame.contentDocument; view = frame.contentWindow;
          if (!realm || !view || view.document !== realm || realm.defaultView !== view || view.frameElement !== frame ||
              !internalDocument(realm) || view.H5PIntegration !== win.H5PIntegration ||
              !realm.documentElement.matches('html.h5p-iframe') || !view.H5P || view.H5P.externalEmbed !== false || view.H5PEditor) { return null; }
          var roots = realm.querySelectorAll('.h5p-content[data-content-id]');
          if (roots.length !== 1 || roots[0].parentNode !== realm.body) { return null; }
          root = roots[0];
        } else if (!anchor.matches('div.h5p-content') || realm.defaultView !== win) { return null; }
        if (!root.matches('.h5p-initialized') || root.getAttribute('data-content-id') !== p.content_id ||
            root.ownerDocument !== realm || quarantined.has(root)) { return null; }
        var containers = Array.prototype.filter.call(root.children, function (node) { return node.matches('.h5p-container.h5p-standalone'); });
        var integration = win.H5PIntegration, api = view.H5P;
        if (containers.length !== 1 || !integration || !integration.contents ||
            !Object.prototype.hasOwnProperty.call(integration.contents, 'cid-' + p.content_id) ||
            integration.contents['cid-' + p.content_id].library !== p.library || !api || !Array.isArray(api.instances)) { return null; }
        var instances = api.instances.filter(function (instance) {
          return instance && ['string', 'number'].indexOf(typeof instance.contentId) !== -1 && String(instance.contentId) === p.content_id;
        });
        if (instances.length !== 1) { return null; }
        var instance = instances[0], info = instance.libraryInfo, parts = p.library.split(/[ .]/);
        if (!info || info.versionedName !== p.library || info.machineName !== parts[0] + '.' + parts[1] ||
            String(info.majorVersion) !== parts[2] || String(info.minorVersion) !== parts[3] ||
            (instance.parent !== undefined && instance.parent !== null) ||
            (instance.subContentId !== undefined && instance.subContentId !== null) ||
            (instance.isRoot !== undefined && (typeof instance.isRoot !== 'function' || instance.isRoot() !== true))) { return null; }
        return { p: p, anchor: anchor, frame: frame, root: root, doc: realm, view: view, api: api, instance: instance, container: containers[0] };
      } catch (error) { return null; }
    }
    function same(a, b) {
      return a && b && ['anchor', 'frame', 'root', 'doc', 'view', 'api', 'instance', 'container'].every(function (key) { return a[key] === b[key]; });
    }
    function value(style, name) { return { value: style.getPropertyValue(name), priority: style.getPropertyPriority(name) }; }
    function equal(a, b) { return a.value === b.value && a.priority === b.priority; }
    function densityState(root) {
      return densityClasses.filter(function (name) { return root.classList.contains(name); });
    }
    function sameDensity(a, b) { return a.length === b.length && a.every(function (name, i) { return name === b[i]; }); }
    function writeDensity(root, desired) {
      // Touch only the three native tokens on this exact root, retaining all other classes.
      densityClasses.forEach(function (name) {
        if (desired.indexOf(name) === -1 && root.classList.contains(name)) { root.classList.remove(name); }
        else if (desired.indexOf(name) !== -1 && !root.classList.contains(name)) { root.classList.add(name); }
      });
    }
    function releaseDensity(record) {
      var owned = record.density;
      if (!owned) { return false; }
      record.density = null;
      if (!sameDensity(densityState(record.q.root), owned.last)) {
        densityBlocked.add(record.q.root); return false;
      }
      if (sameDensity(owned.last, owned.original)) { return false; }
      writeDensity(record.q.root, owned.original);
      return true;
    }
    function resizeDensity(record) {
      var q = record.q;
      // Core registers main instances after installing its internal-iframe resize listener.
      // Core's dispatcher supports EventDispatcher and deprecated jQuery event instances.
      if (!q.root.isConnected || !q.anchor.isConnected || !same(q, qualify(q.p))) { return; }
      try { q.api.trigger(q.instance, 'resize'); }
      catch (error) {
        // A broken library must not break playback or create a mutation/resize retry loop.
        if (same(q, qualify(q.p))) { releaseDensity(record); }
        else { record.density = null; quarantined.add(q.root); }
        densityBlocked.add(q.root);
      }
    }
    function densityReady(q) {
      try {
        return q.container.matches('.h5p-theme') && typeof q.api.trigger === 'function' &&
          (typeof q.instance.trigger === 'function' || (q.instance.$ && typeof q.instance.$.trigger === 'function')) &&
          q.view.getComputedStyle(q.root).getPropertyValue('--h5p-theme-scaling').trim() !== '';
      } catch (error) { return false; }
    }
    function applyDensity(record, selection) {
      var root = record.q.root, owned = record.density, current = densityState(root);
      if (owned && !sameDensity(current, owned.last)) {
        record.density = null; densityBlocked.add(root); return;
      }
      if (selection === 'original') {
        if (releaseDensity(record)) { resizeDensity(record); }
        return;
      }
      if (densityBlocked.has(root)) { return; }
      var desired = ['h5p-' + selection];
      if (sameDensity(current, desired)) { return; } // Matching foreign/native state is not claimed.
      if (!owned) { owned = { original: current, last: current }; record.density = owned; }
      writeDensity(root, desired); owned.last = densityState(root);
      resizeDensity(record);
    }
    function releaseProperty(record, name) {
      var owned = record.owned.get(name), style = record.q.root.style;
      if (!owned) { return; }
      if (equal(value(style, name), owned.last)) {
        if (owned.original.value === '') { style.removeProperty(name); }
        else { style.setProperty(name, owned.original.value, owned.original.priority); }
      } else { record.blocked.add(name); }
      record.owned.delete(name);
    }
    function restore(record) {
      if (!record.owned.size && !record.density) { return; }
      // Never mutate a newly ambiguous owner, including during restoration.
      if (!same(record.q, qualify(record.q.p, record.q.anchor))) {
        note(record.q.p, 'ownership-lost'); quarantined.add(record.q.root); record.owned.clear(); record.density = null; return;
      }
      Array.from(record.owned.keys()).forEach(function (name) { releaseProperty(record, name); });
      if (releaseDensity(record)) { resizeDensity(record); }
    }
    function ready(q) {
      try {
        return q.container.matches('.h5p-theme') && q.view.getComputedStyle(q.root).getPropertyValue('--h5p-theme-main-cta-base').trim() !== '';
      } catch (error) { return false; }
    }
    function apply(record, palette) {
      var style = record.q.root.style;
      Array.from(record.owned.keys()).forEach(function (name) {
        if (!Object.prototype.hasOwnProperty.call(palette, name)) { releaseProperty(record, name); }
      });
      Object.keys(palette).forEach(function (name) {
        if (record.blocked.has(name)) { return; }
        var current = value(style, name), owned = record.owned.get(name);
        if (owned && !equal(current, owned.last)) {
          record.blocked.add(name); record.owned.delete(name); return;
        }
        if (!owned && current.value === palette[name] && current.priority === '') { return; }
        if (!owned) { owned = { original: current, last: current }; record.owned.set(name, owned); }
        if (current.value !== palette[name] || current.priority !== '') {
          style.setProperty(name, palette[name], '');
          owned.last = value(style, name); // Preserve the browser's CSSOM serialization.
        }
      });
    }
    function fresh() { return !returning && descriptor && now() < descriptor.deadline && visible(); }
    function watch(realm) {
      var previous = observers.get(realm);
      if (previous && previous.node === realm.documentElement) { return; }
      // document.open() may keep the Document object but replace its element tree.
      if (previous) { previous.observer.disconnect(); previous.remove(); }
      var observer = new win.MutationObserver(schedule);
      observer.observe(realm.documentElement, { subtree: true, childList: true, attributes: true,
        attributeFilter: ['class', 'data-content-id', 'src', 'srcdoc', 'sandbox', 'style'] });
      observers.set(realm, { node: realm.documentElement, observer: observer, remove: listen(realm, 'load', function () { scan(); retries(); }, true) });
    }
    function scan() {
      if (!pageContext(win, manifest.admin_viewer)) { invalidate(); return; }
      if (!active) { return; }
      if (descriptor && now() >= descriptor.deadline) { expire(); return; }
      var found = new Map(), realms = new Set([doc]), frameSet = new Set();
      manifest.players.forEach(function (p) {
        if (initialFailed.has(p.content_id) || returnFailed.has(p.content_id)) { return; }
        // Observe only one canonical internal frame per candidate, before initialization.
        if (p.embed === 'iframe') {
          var nodes = doc.querySelectorAll('iframe.h5p-iframe[data-content-id="' + p.content_id + '"]');
          if (nodes.length === 1 && nodes[0].getAttribute('src') === 'about:blank' &&
              !nodes[0].hasAttribute('srcdoc') && !nodes[0].hasAttribute('sandbox')) {
            var frame = nodes[0]; frameSet.add(frame);
            if (!frames.has(frame)) { frames.set(frame, listen(frame, 'load', function () { scan(); retries(); })); }
            try {
              if (frame.contentDocument && internalDocument(frame.contentDocument) &&
                  frame.contentDocument.documentElement.matches('html.h5p-iframe')) { realms.add(frame.contentDocument); }
            } catch (error) { /* Unavailable/cross-origin document. */ }
          }
        }
        var q = qualify(p);
        if (!q) { note(p, 'unqualified'); return; }
        if (instanceRoots.has(q.instance) && !instanceRoots.get(q.instance).has(q.root)) {
          note(p, 'instance-root-changed'); return;
        }
        if (!instanceRoots.has(q.instance)) { instanceRoots.set(q.instance, new WeakSet([q.root])); }
        realms.add(q.doc); found.set(q.root, q); note(p, ready(q) ? 'qualified' : 'awaiting-styles');
      });
      records.forEach(function (record, root) {
        if (!same(record.q, found.get(root))) { restore(record); records.delete(root); }
      });
      found.forEach(function (q, root) {
        if (quarantined.has(root)) { return; }
        var record = records.get(root);
        if (!record) {
          if (!blocked.has(root)) { blocked.set(root, new Set()); }
          record = { q: q, owned: new Map(), blocked: blocked.get(root), density: null }; records.set(root, record);
        }
        if (!ready(q)) {
          Array.from(record.owned.keys()).forEach(function (name) { releaseProperty(record, name); });
        } else if (fresh()) { apply(record, descriptor.colors); }
        if (!densityReady(q)) {
          if (releaseDensity(record)) { resizeDensity(record); }
        } else if (fresh()) { applyDensity(record, descriptor.density); }
        if (readiness && readiness.pending(q.p.content_id) && fresh()) {
          if (record.blocked.size || densityBlocked.has(root)) { readiness.fail(q.p.content_id); }
          else if (ready(q) && densityReady(q)) {
            readiness.reveal(q, function () {
              return fresh() && same(q, qualify(q.p)) && ready(q) && densityReady(q) &&
                !initialFailed.has(q.p.content_id) && !returnFailed.has(q.p.content_id) && !record.blocked.size && !densityBlocked.has(root) &&
                Object.keys(descriptor.colors).every(function (name) {
                  var owned = record.owned.get(name), current = value(root.style, name);
                  return owned ? equal(current, owned.last) : current.value === descriptor.colors[name] && current.priority === '';
                }) && (descriptor.density === 'original' || sameDensity(densityState(root), ['h5p-' + descriptor.density]));
            });
          }
        }
      });
      observers.forEach(function (entry, realm) {
        if (!realms.has(realm)) { entry.observer.disconnect(); entry.remove(); observers.delete(realm); }
      });
      realms.forEach(watch);
      frames.forEach(function (remove, frame) { if (!frameSet.has(frame)) { remove(); frames.delete(frame); } });
    }
    function schedule() {
      if (active && queued === null) { queued = win.setTimeout(function () { queued = null; scan(); }, 25); }
    }
    function retries() {
      win.clearTimeout(retry); retryIndex = 0;
      function next() {
        if (!active || retryIndex >= delays.length) { retry = null; return; }
        retry = win.setTimeout(function () { scan(); next(); }, delays[retryIndex++]);
      }
      next();
    }
    function cancelRequest() {
      generation++;
      if (request) { request.abort.abort(); win.clearTimeout(request.timeout); request = null; }
    }
    function releaseAppearance() {
      descriptor = null;
      win.clearTimeout(renewal); renewal = null;
      win.clearTimeout(expiry); expiry = null;
      records.forEach(restore);
    }
    function invalidate(failed) {
      cancelRequest(); releaseAppearance();
      if (failed && readiness) { readiness.failAll(); }
    }
    function expire() {
      // Expiring the old grant must not cancel a replacement with its own lifetime.
      releaseAppearance(); scan();
      if (!request && lifecycle === null && active && visible() && records.size > 0) { refresh(); }
    }
    function renew() {
      renewal = null;
      if (!active || !visible()) { return; }
      scan(); // Requalify removal/expiry even if observer or timer delivery was delayed.
      if (fresh() && records.size > 0 && !request) { fetchAppearance(); }
    }
    function refresh() {
      if (!active || !visible()) { return Promise.resolve(); }
      win.clearTimeout(lifecycle); lifecycle = null;
      if (readiness && holdReturn(false)) {
        returning = true; returnFailed.clear();
        if (!readiness.returnPending()) { invalidate(); returning = false; return Promise.resolve(); }
        cancelRequest(); win.clearTimeout(renewal); renewal = null;
        if (descriptor && now() >= descriptor.deadline) { releaseAppearance(); }
        scan(); retries(); return fetchAppearance();
      }
      invalidate(); scan(); retries();
      return fetchAppearance();
    }
    function fetchAppearance() {
      cancelRequest(); // Deliberately leaves the old descriptor and hard timer intact.
      var bytes = new Uint8Array(16);
      try { win.crypto.getRandomValues(bytes); } catch (error) { invalidate(true); return Promise.resolve(); }
      var id = Array.from(bytes, function (n) { return n.toString(16).padStart(2, '0'); }).join('');
      var token = generation, start = now(), abort = new win.AbortController();
      lastRequest = start;
      var pending = { abort: abort, timeout: null }; request = pending;
      pending.timeout = win.setTimeout(function () {
        if (request === pending) { invalidate(true); }
      }, 10000);
      return Promise.resolve().then(function () {
        return win.fetch(manifest.endpoint, { method: 'POST', credentials: 'same-origin', cache: 'no-store',
          mode: 'same-origin', redirect: 'error', referrerPolicy: 'same-origin', signal: abort.signal,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-PJH5PWP-Request': 'appearance-v1' },
          body: 'action=pjh5pwp_appearance&request_id=' + id + (manifest.admin_viewer ?
            '&admin_content_id=' + manifest.admin_viewer.content_id +
            '&admin_nonce=' + manifest.admin_viewer.nonce +
            '&admin_players=' + encodeURIComponent(manifest.players.map(function (p) { return p.content_id; }).join(',')) : (audience ? '&expected_audience=' + audience : '')) });
      }).then(function (response) {
        if ((!response.ok && response.status !== 409) || response.redirected || !/^application\/json(?:;|$)/i.test(response.headers.get('Content-Type') || '')) { throw new Error('response'); }
        return response.text();
      }).then(function (text) {
        if (token !== generation || !active || !visible()) { return; }
        if (text.length > 16384) { throw new Error('size'); }
        var age = now() - start;
        // Body delivery can beat a throttled timeout callback, too.
        if (age >= 10000) { invalidate(true); return; }
        var parsed = validate(JSON.parse(text), id, age);
        if (parsed && parsed.suspended && parsed.reason === 'authentication_changed') {
          stop();
          if (!doc.querySelectorAll('[data-pjh5pwp-auth-reload]').length) {
            var message = doc.createElement('p'); message.setAttribute('data-pjh5pwp-auth-reload', '1'); message.setAttribute('role', 'status');
            message.textContent = 'Sign-in state changed. Reload this page to use the appropriate H5P appearance.'; doc.body.appendChild(message);
          }
          return;
        }
        if (!parsed || parsed.suspended) { invalidate(true); return; }
        // A response/scan can beat a throttled hard-expiry callback. Never let it
        // conceal an expired grant by simply replacing that grant's deadline.
        if (descriptor && now() >= descriptor.deadline) { releaseAppearance(); }
        win.clearTimeout(pending.timeout); request = null;
        win.clearTimeout(renewal); win.clearTimeout(expiry);
        descriptor = { colors: parsed.colors, density: parsed.density, deadline: start + 30000 };
        returning = false;
        renewal = win.setTimeout(renew, Math.max(0, start + 25000 - now()));
        expiry = win.setTimeout(function () {
          expire();
        }, Math.max(0, descriptor.deadline - now()));
        scan();
      }).catch(function () { if (token === generation) { invalidate(true); } });
    }
    function pause() {
      var held = holdReturn(true);
      if (held) { cancelRequest(); win.clearTimeout(renewal); renewal = null; readiness.suspend(); returning = true; }
      else { invalidate(); if (readiness) { readiness.failAll(); } }
      active = false;
      win.clearTimeout(lifecycle); lifecycle = null;
      win.clearTimeout(queued); queued = null; win.clearTimeout(retry); retry = null;
      observers.forEach(function (entry) { entry.observer.disconnect(); entry.remove(); }); observers.clear();
      frames.forEach(function (remove) { remove(); }); frames.clear(); if (!held) { records.clear(); }
    }
    function holdReturn(hidden) {
      if (!readiness) { return false; }
      var qualified = new Map();
      records.forEach(function (record, root) { if (same(record.q, qualify(record.q.p))) { qualified.set(root, record); } });
      manifest.players.forEach(function (p) { var q = qualify(p); if (q && !initialFailed.has(p.content_id) && !qualified.has(q.root)) { qualified.set(q.root, { q: q }); } });
      return readiness.hold(qualified, hidden);
    }
    function resume(event) {
      if (destroyed || !visible() || doc.readyState === 'loading') { return; }
      if (readiness && active && readiness.hasPending() && !returning && request && !(event && event.persisted)) { return; }
      if (readiness && active && (returning && (request || lifecycle !== null) || readiness.returnPending())) { return; }
      if (readiness && (holdReturn(false) || returning)) {
        active = true; returning = true; returnFailed.clear(); readiness.wake();
        if (!readiness.returnPending()) { invalidate(); returning = false; return; }
        cancelRequest(); win.clearTimeout(renewal); renewal = null;
        if (descriptor && now() >= descriptor.deadline) { releaseAppearance(); }
        scan(); retries();
        var wait = Math.max(0, 1000 - (now() - lastRequest));
        if (!wait) { fetchAppearance(); }
        else { lifecycle = win.setTimeout(function () { lifecycle = null; fetchAppearance(); }, wait); }
        return;
      }
      active = true;
      // Revoke immediately, then coalesce clustered browser lifecycle signals.
      // Explicit refresh() remains immediate for a known account/policy transition.
      invalidate(); scan();
      if (lifecycle !== null) { return; }
      var delay = Math.max(0, 1000 - (now() - lastRequest));
      if (!delay) { refresh(); return; }
      lifecycle = win.setTimeout(function () { lifecycle = null; refresh(); }, delay);
    }
    function start() {
      if (active || destroyed || removers.length) { return; }
      var firstShow = true;
      removers.push(listen(win, 'pageshow', function (event) {
        if (readiness && firstShow && !event.persisted && active) { firstShow = false; return; }
        firstShow = false; resume(event);
      }), listen(win, 'pagehide', pause),
        listen(win, 'focus', function () { if (visible()) { resume(); } }),
        listen(doc, 'visibilitychange', function () { if (visible()) { resume(); } else { pause(); } }));
      if (doc.readyState === 'loading') { removers.push(listen(doc, 'DOMContentLoaded', resume)); }
      else { resume(); }
    }
    function stop() { pause(); invalidate(); if (readiness) { readiness.failAll(); } removers.forEach(function (remove) { remove(); }); removers = []; destroyed = true; }
    return { start: start, refresh: refresh, stop: stop,
      diagnostics: function () { return Array.from(diagnostics, function (entry) { return { content_id: entry[0], status: entry[1] }; }); } };
  }
  return { create: create, bootstrap: bootstrap, validate: validate, colors: colors, properties: properties.slice() };
}));
