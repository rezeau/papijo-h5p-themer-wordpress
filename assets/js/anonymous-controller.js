/* Anonymous appearance: isolated activity ownership and one site-wide preference selector. No concealment. GPL-3.0-or-later. */
(function (factory) {
  'use strict';
  if (typeof module === 'object' && module.exports) { module.exports = factory(); return; }
  var script = document.currentScript;
  if (!script || script.getAttribute('data-pjh5pwp-anonymous') !== '1' || window.pjh5pwpAnonymousAppearance) { return; }
  try { window.pjh5pwpAnonymousAppearance = factory().bootstrap(window, script); } catch (error) { /* Stay visible and native. */ }
}(function () {
  'use strict';
  var properties = ('main-cta-base secondary-cta-base alternative-base background focus main-cta-light main-cta-dark contrast-cta contrast-cta-white contrast-cta-light secondary-cta-light secondary-cta-dark secondary-contrast-cta secondary-contrast-cta-hover alternative-light alternative-dark alternative-darker text-primary text-secondary text-third ui-base stroke-1 stroke-2 stroke-3 feedback-correct-main feedback-correct-secondary feedback-correct-third feedback-incorrect-main feedback-incorrect-secondary feedback-incorrect-third feedback-neutral-main feedback-neutral-secondary feedback-neutral-third').split(' ').map(function (p) { return '--h5p-theme-' + p; });
  var densities = ['h5p-small', 'h5p-medium', 'h5p-large'];
  function exact(o, keys) { return o && typeof o === 'object' && !Array.isArray(o) && Object.keys(o).length === keys.length && keys.every(function (k) { return Object.prototype.hasOwnProperty.call(o, k); }); }
  function token(s, re) { return typeof s === 'string' && !/[\r\n]/.test(s) && re.test(s); }
  function themeToken(s) { return token(s, /^(?:site_default|core|(?:builtin|custom):[a-z][a-z0-9_]{0,63})$/); }
  function colors(s) {
    if (typeof s !== 'string' || s.length > 8192) { return null; }
    var out = Object.create(null);
    if (!s) { return out; }
    if (s.slice(-1) !== ';') { return null; }
    var parts = s.slice(0, -1).split(';');
    if (parts.length > 33) { return null; }
    for (var i = 0; i < parts.length; i++) {
      var colon = parts[i].indexOf(':'), p = parts[i].slice(0, colon), v = parts[i].slice(colon + 1);
      if (colon < 1 || properties.indexOf(p) < 0 || Object.prototype.hasOwnProperty.call(out, p) ||
          !(token(v, /^#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/) || (p === '--h5p-theme-contrast-cta-light' &&
          ['color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)', 'rgba(59, 130, 246, 0.15)'].indexOf(v) >= 0))) { return null; }
      out[p] = v;
    }
    return out;
  }
  function validate(b, id, age) {
    if (!exact(b, ['version', 'request_id', 'audience', 'reload_required', 'reason', 'refresh', 'appearance', 'selection']) || b.version !== 1 ||
        b.request_id !== id || !token(id, /^[a-f0-9]{32}$/) || !Number.isFinite(age) || age < 0 || age >= 10000 ||
        !exact(b.refresh, ['max_age_seconds', 'reuse']) || b.refresh.max_age_seconds !== 30 || b.refresh.reuse !== false) { return null; }
    if (b.audience === 'authenticated') {
      return b.reload_required === true && b.reason === 'authenticated_session' && b.appearance === null && b.selection === null ? { authenticated: true } : null;
    }
    if (b.audience !== 'anonymous' || b.reload_required !== false || b.reason !== null) { return null; }
    var a = b.appearance, s = b.selection;
    if (!exact(s, ['theme', 'density', 'theme_fallback']) || !themeToken(s.theme) || typeof s.theme_fallback !== 'boolean' ||
        !exact(a, ['admission', 'theme', 'color_declarations', 'density']) || !exact(a.admission, ['permitted', 'reason']) ||
        a.admission.permitted !== true || a.admission.reason !== null || !exact(a.theme, ['source', 'kind', 'id', 'version']) ||
        ['personal', 'global', 'core'].indexOf(a.theme.source) < 0 || !exact(a.density, ['selection', 'preserve_native']) ||
        ['original', 'small', 'medium', 'large'].indexOf(a.density.selection) < 0 || s.density !== a.density.selection ||
        a.density.preserve_native !== (a.density.selection === 'original')) { return null; }
    var palette = colors(a.color_declarations), t = a.theme;
    if (!palette) { return null; }
    if (t.kind === 'core') { if (t.id !== null || t.version !== null || a.color_declarations !== '') { return null; } }
    else if (t.kind === 'builtin') {
      if (['daylight', 'lavender', 'mint', 'sunset', 'dark'].indexOf(t.id) < 0 || t.version !== 'moodle-478fed30d2a4' || Object.keys(palette).length !== 33) { return null; }
    } else if (t.kind !== 'custom' || !token(t.id, /^[a-z][a-z0-9_]{0,63}$/) || t.version !== null) { return null; }
    return { colors: palette, density: a.density.selection, authenticated: false };
  }
  function validMetadata(win, m, gate) {
    if (!exact(gate, ['version', 'endpoint', 'content_id', 'nonce', 'page', 'home', 'player']) || gate.version !== 1 ||
        typeof gate.page !== 'string' || gate.page.length > 2048 || !/^\/(?!\/)[^\x00-\x20\x7f\\#]*$/.test(gate.page) ||
        typeof gate.home !== 'string' || gate.home.length > 2048 ||
        !token(gate.content_id, /^[1-9][0-9]{0,9}$/) || !token(gate.nonce, /^[a-f0-9]{10}$/) ||
        !exact(m, ['version', 'endpoint', 'players']) || m.version !== 1 || !Array.isArray(m.players) || m.players.length !== 1 || m.endpoint !== gate.endpoint) { return false; }
    var home = new win.URL(gate.home), endpoint = new win.URL(m.endpoint), p = m.players[0];
    return home.origin === win.location.origin && !home.username && !home.password && !home.search && !home.hash && home.href === gate.home && home.pathname.slice(-1) === '/' &&
      endpoint.origin === win.location.origin && !endpoint.username && !endpoint.password && !endpoint.search && !endpoint.hash && /\/wp-admin\/admin-ajax\.php$/.test(endpoint.pathname) &&
      exact(p, ['content_id', 'embed', 'library']) && JSON.stringify(p) === JSON.stringify(gate.player) && p.content_id === gate.content_id &&
      ['div', 'iframe'].indexOf(p.embed) >= 0 && token(p.library, /^H5P\.[A-Za-z][A-Za-z0-9]* [0-9]{1,4}\.[0-9]{1,4}$/);
  }
  function validatePublic(b, id, age, requested) {
    if (!exact(b, ['version', 'request_id', 'audience', 'reload_required', 'reason', 'refresh', 'appearance', 'selection', 'catalog', 'fallback_reason']) || b.version !== 1) { return null; }
    var normalized = { version: 1, request_id: b.request_id, audience: b.audience, reload_required: b.reload_required, reason: b.reason, refresh: b.refresh, appearance: b.appearance, selection: b.selection };
    if (b.audience === 'authenticated') { return b.catalog === null && b.fallback_reason === null ? validate(normalized, id, age) : null; }
    if (!exact(b.catalog, ['themes', 'densities']) || !Array.isArray(b.catalog.themes) || !b.catalog.themes.length || b.catalog.themes.length > 64 || JSON.stringify(b.catalog.densities) !== JSON.stringify(['original', 'small', 'medium', 'large'])) { return null; }
    var seen = new Set();
    if (!b.catalog.themes.every(function (c) {
      if (!exact(c, ['value', 'label']) || !themeToken(c.value) || seen.has(c.value) || typeof c.label !== 'string' || !c.label.length || c.label.length > 160) { return false; }
      seen.add(c.value); return true;
    }) || !seen.has('site_default') || !b.selection || !seen.has(b.selection.theme) || b.selection.density !== requested.density ||
      (b.selection.theme_fallback ? b.fallback_reason !== 'theme_unavailable' || b.selection.theme !== 'site_default' : b.fallback_reason !== null || b.selection.theme !== requested.theme)) { return null; }
    var a = b.appearance;
    if (!a || !exact(a.theme, ['source', 'kind', 'id', 'version']) || a.theme.kind !== 'effective' || a.theme.id !== null || a.theme.version !== null) { return null; }
    normalized.appearance = { admission: a.admission, color_declarations: a.color_declarations, density: a.density,
      theme: { source: a.theme.source, kind: 'custom', id: 'effective', version: null } };
    if (!exact(a, ['admission', 'theme', 'color_declarations', 'density'])) { return null; }
    return validate(normalized, id, age);
  }
  function selectionClient(win, home, changed) {
    var doc = win.document, prefix = 'pjh5pwp:anonymous:v1:' + encodeURIComponent(home) + ':', fields = ['theme', 'density'];
    var defaults = { theme: 'site_default', density: 'original' }, desired = { theme: defaults.theme, density: defaults.density }, dirty = {}, unavailable = false;
    var panel, disclosure, fieldset, theme, densitySelect, reset, status, catalog = null, rendered = '', announce = true, awaiting = true, stopped = false, message = 'Appearance applied.';
    function valid(field, value) { return field === 'theme' ? themeToken(value) : ['original', 'small', 'medium', 'large'].indexOf(value) >= 0; }
    function stored(field, raw) { try { if (raw !== null && (typeof raw !== 'string' || raw.length > 192)) { return defaults[field]; } var value = raw === null ? defaults[field] : JSON.parse(raw); return valid(field, value) ? value : defaults[field]; } catch (error) { return defaults[field]; } }
    function storage(op, field, value) { try { var store = win.localStorage; return op === 'getItem' ? store.getItem(prefix + field) : store[op](prefix + field, value); } catch (error) { unavailable = true; return null; } }
    fields.forEach(function (field) { desired[field] = stored(field, storage('getItem', field)); });
    function text(value) { if (status) {
      if (value === 'Appearance applied.' && !unavailable) { status.setAttribute('data-pjh5pwp-routine', '1'); } else { status.removeAttribute('data-pjh5pwp-routine'); }
      status.textContent = value + (unavailable ? ' Preferences are available for this page only; browser storage is unavailable.' : ''); } }
    function choose(field, value, save) {
      if (stopped || !valid(field, value)) { return; }
      desired[field] = value; if (save) { dirty[field] = true; }
      announce = true; awaiting = true; text('Applying appearance…'); changed();
    }
    function onTheme() { choose('theme', theme.value, true); }
    function onDensity() { choose('density', densitySelect.value, true); }
    function onReset() {
      if (stopped) { return; }
      fields.forEach(function (field) { storage('removeItem', field); desired[field] = defaults[field]; }); dirty = {};
      announce = true; awaiting = true; text('Restoring defaults…'); changed();
    }
    function onStorage(event) {
      if (stopped) { return; }
      try { if (event.storageArea && event.storageArea !== win.localStorage) { return; } } catch (error) { unavailable = true; return; }
      fields.forEach(function (field) { if (event.key === prefix + field) { delete dirty[field]; choose(field, stored(field, event.newValue), false); } });
    }
    win.addEventListener('storage', onStorage);
    function element(tag, attrs, value) {
      var node = doc.createElement(tag); Object.keys(attrs || {}).forEach(function (k) { node.setAttribute(k, attrs[k]); });
      if (value !== undefined) { node.textContent = value; } return node;
    }
    function options(select, choices) {
      while (select.firstChild) { select.removeChild(select.firstChild); }
      choices.forEach(function (c) { var option = element('option', { value: c.value }, c.label); if (c.disabled) { option.setAttribute('disabled', ''); } select.appendChild(option); });
    }
    function mount(q) {
      var existing = doc.querySelectorAll('[data-pjh5pwp-anonymous-selector]');
      if (existing.length && (existing.length !== 1 || existing[0] !== panel)) { return false; }
      var anchor = q.frame ? q.frame.parentElement : q.root;
      if (!panel) {
        panel = element('div', { 'class': 'pjh5pwp-anonymous-selector', 'data-pjh5pwp-anonymous-selector': '1' });
        disclosure = element('details');
        disclosure.appendChild(element('summary', {}, 'H5P appearance'));
        panel.appendChild(disclosure);
        fieldset = element('fieldset'); disclosure.appendChild(fieldset);
        fieldset.appendChild(element('legend', {}, 'H5P appearance'));
        var label = element('label', { 'for': 'pjh5pwp-anonymous-theme' }, 'Theme');
        theme = element('select', { id: 'pjh5pwp-anonymous-theme' }); label.appendChild(theme); fieldset.appendChild(label);
        label = element('label', { 'for': 'pjh5pwp-anonymous-density' }, 'Density');
        densitySelect = element('select', { id: 'pjh5pwp-anonymous-density' }); label.appendChild(densitySelect); fieldset.appendChild(label);
        reset = element('button', { type: 'button' }, 'Restore defaults'); fieldset.appendChild(reset);
        status = element('p', { role: 'status', 'aria-live': 'polite', 'aria-atomic': 'true' }); panel.appendChild(status);
        theme.addEventListener('change', onTheme); densitySelect.addEventListener('change', onDensity); reset.addEventListener('click', onReset);
        text('Loading appearance choices…');
      }
      if (panel.parentNode !== anchor.parentNode || panel.nextSibling !== anchor) { anchor.parentNode.insertBefore(panel, anchor); }
      var next = JSON.stringify([catalog, desired.theme === 'core']);
      if (next !== rendered) {
        var choices = catalog ? catalog.themes.filter(function (c) { return c.value !== 'core'; }) : [{ value: 'site_default', label: 'Site default' }];
        if (desired.theme === 'core') { choices = [{ value: 'core', label: 'H5P Core defaults (saved preference)', disabled: true }].concat(choices); }
        options(theme, choices);
        options(densitySelect, ['original', 'small', 'medium', 'large'].map(function (d) { return { value: d, label: d.charAt(0).toUpperCase() + d.slice(1) }; })); rendered = next;
      }
      if (theme.value !== desired.theme) { theme.value = desired.theme; }
      if (densitySelect.value !== desired.density) { densitySelect.value = desired.density; }
      var disabled = stopped || !catalog;
      [theme, densitySelect, reset].forEach(function (control) {
        if (control.disabled !== disabled) { control.disabled = disabled; }
      });
      return true;
    }
    return {
      request: function () { return { theme: desired.theme, density: desired.density }; },
      mount: mount,
      accept: function (body) {
        catalog = body.catalog;
        fields.forEach(function (field) {
          desired[field] = body.selection[field];
          if (dirty[field] || (field === 'theme' && body.selection.theme_fallback)) { storage('setItem', field, JSON.stringify(desired[field])); }
        }); dirty = {};
        message = body.selection.theme_fallback ? 'Selected Theme is unavailable; using Site default.' : 'Appearance applied.';
        awaiting = false; if (body.selection.theme_fallback) { announce = true; }
      },
      applied: function () { if (announce && !awaiting) { text(message); announce = false; } },
      fail: function (reason) {
        stopped = true; win.removeEventListener('storage', onStorage);
        if (theme) { theme.disabled = densitySelect.disabled = reset.disabled = true; }
        text(reason === 'reload_required' ? 'Sign-in state changed. Reload this page to use your WordPress appearance.' : 'Appearance unavailable. Reload this page to try again.');
      },
      stop: function () {
        stopped = true; win.removeEventListener('storage', onStorage);
        if (panel) { theme.removeEventListener('change', onTheme); densitySelect.removeEventListener('change', onDensity); reset.removeEventListener('click', onReset); panel.remove(); }
      }
    };
  }
  function create(win, manifest, gate, sharedSelector) {
    var doc = win.document, p, record = null, descriptor = null, observer, active = false, closed = false, paused = false, reload = false;
    var target = null, frameWatch = null, childWatch = null, requalifying = false, pending = false, quarantined = new WeakSet();
    var state = 'not_started', generation = 0, abort, deadline = 0, expires = 0, retry = null, timers = new Set();
    var selector = sharedSelector || null;
    try { if (!validMetadata(win, manifest, gate)) { return null; } p = manifest.players[0]; } catch (error) { return null; }
    function now() { return win.performance.now(); }
    function later(fn, ms) { var t = win.setTimeout(function () { timers.delete(t); fn(); }, ms); timers.add(t); return t; }
    function clear() { timers.forEach(function (t) { win.clearTimeout(t); }); timers.clear(); retry = null; pending = false; if (abort) { abort.abort(); abort = null; } generation++; }
    function context(restoring) {
      if (!restoring && win.location.pathname + win.location.search !== gate.page) { return false; }
      if (win.top !== win || doc.defaultView !== win || win.H5PEditor || (win.H5P && win.H5P.externalEmbed) || win['pjh5pwp-theme-controller'] ||
          /\/(?:wp-admin|wp-json)(?:\/|$)|\/embed(?:\/|$)/.test(win.location.pathname) || /[?&](?:preview|action|rest_route)=/.test(win.location.search) ||
          doc.documentElement.classList.contains('pjh5pwp-readiness-armed') || doc.querySelectorAll('script[data-pjh5pwp-bootstrap]').length) { return false; }
      return true;
    }
    function safeFrame(frame) {
      return frame && frame.isConnected && frame.ownerDocument === doc && frame.matches('iframe.h5p-iframe') &&
        frame.id === 'h5p-iframe-' + p.content_id && frame.getAttribute('src') === 'about:blank' &&
        !frame.hasAttribute('srcdoc') && !frame.hasAttribute('sandbox') && !frame.hasAttribute('credentialless') &&
        frame.parentElement && frame.parentElement.matches('.h5p-iframe-wrapper') &&
        !frame.closest('.h5p-editor, .h5peditor, [data-pjh5pwp-excluded]') &&
        !(frame.parentElement.parentElement && frame.parentElement.parentElement.closest('.h5p-content, .h5p-iframe-wrapper'));
    }
    function internalDocument(realm) {
      try {
        var childURL = new win.URL(realm.URL), parentURL = new win.URL(doc.URL);
        childURL.hash = ''; parentURL.hash = '';
        return childURL.href === 'about:blank' || childURL.href === parentURL.href;
      } catch (error) { return false; }
    }
    function qualify(restoring) {
      try {
        if (!context(restoring)) { return null; }
        var anchors = doc.querySelectorAll('.h5p-content[data-content-id="' + p.content_id + '"], iframe[data-content-id="' + p.content_id + '"]');
        if (anchors.length !== 1) { return null; }
        var anchor = anchors[0], frame = null, realm = doc, view = win, root = anchor;
        if (p.embed === 'iframe') {
          frame = anchor;
          if (!safeFrame(frame) || !frame.matches('.h5p-initialized')) { return null; }
          // Source/attribute checks precede every child browsing-context access.
          realm = frame.contentDocument; view = frame.contentWindow;
          if (!realm || !view || view.document !== realm || realm.defaultView !== view || view.frameElement !== frame ||
              !internalDocument(realm) || !realm.documentElement.matches('html.h5p-iframe') ||
              view.H5PIntegration !== win.H5PIntegration || !view.H5P || view.H5P.externalEmbed !== false || view.H5PEditor) { return null; }
          var roots = realm.querySelectorAll('.h5p-content[data-content-id]');
          if (roots.length !== 1 || roots[0].parentNode !== realm.body) { return null; }
          root = roots[0];
        } else if (!anchor.matches('div.h5p-content') || doc.defaultView !== win) { return null; }
        var parent = root.parentElement;
        if (quarantined.has(root) || !root.matches('div.h5p-content.h5p-initialized') || !root.isConnected || root.ownerDocument !== realm ||
            root.getAttribute('data-content-id') !== p.content_id || root.closest('.h5p-editor, .h5peditor, [data-pjh5pwp-excluded]') ||
            (parent && parent.closest('.h5p-content, .h5p-iframe-wrapper'))) { return null; }
        var containers = Array.prototype.filter.call(root.children, function (c) { return c.matches('.h5p-container.h5p-standalone.h5p-theme'); });
        var api = view.H5P, settings = win.H5PIntegration && win.H5PIntegration.contents;
        if (containers.length !== 1 || !api || typeof api.trigger !== 'function' || !Array.isArray(api.instances) || !settings ||
            !Object.prototype.hasOwnProperty.call(settings, 'cid-' + p.content_id) || settings['cid-' + p.content_id].library !== p.library) { return null; }
        var instances = api.instances.filter(function (i) { return i && ['string', 'number'].indexOf(typeof i.contentId) >= 0 && String(i.contentId) === p.content_id; });
        if (instances.length !== 1) { return null; }
        var instance = instances[0], info = instance.libraryInfo, parts = p.library.split(/[ .]/);
        if (!info || info.versionedName !== p.library || info.machineName !== parts[0] + '.' + parts[1] || String(info.majorVersion) !== parts[2] || String(info.minorVersion) !== parts[3] ||
            instance.parent != null || instance.subContentId != null || (instance.isRoot !== undefined && (typeof instance.isRoot !== 'function' || instance.isRoot() !== true)) ||
            !(typeof instance.trigger === 'function' || (instance.$ && typeof instance.$.trigger === 'function'))) { return null; }
        return { anchor: anchor, frame: frame, view: view, doc: realm, element: realm.documentElement,
          root: root, container: containers[0], api: api, instance: instance };
      } catch (error) { return null; }
    }
    function same(q, r) { return q && r && ['anchor', 'frame', 'view', 'doc', 'element', 'root', 'container', 'api', 'instance'].every(function (k) { return q[k] === r[k]; }); }
    function unwatchChild() {
      if (childWatch) { childWatch.observer.disconnect(); childWatch.doc.removeEventListener('load', scan, true); childWatch = null; }
    }
    function disconnect() {
      if (observer) { observer.disconnect(); }
      unwatchChild();
      if (frameWatch) { frameWatch.frame.removeEventListener('load', scan); frameWatch = null; }
    }
    function retire() {
      // The old document is no longer a proved owner: release() never writes into its replacement.
      var hadAppearance = record !== null, oldRoot = target && target.root;
      release(); clear(); descriptor = null; target = null; record = null; requalifying = true; state = 'awaiting_runtime';
      if (oldRoot) { quarantined.add(oldRoot); }
      // Repeated rewrites before successful admission cannot extend an unfinished readiness window.
      if (hadAppearance) { deadline = now() + 15000; }
    }
    function watchFrame() {
      if (p.embed !== 'iframe') { return; }
      var nodes = doc.querySelectorAll('.h5p-content[data-content-id="' + p.content_id + '"], iframe[data-content-id="' + p.content_id + '"]');
      if (nodes.length !== 1) { return; }
      var frame = nodes[0];
      if (!safeFrame(frame)) { fail('iframe_refused'); return; }
      if (!frameWatch || frameWatch.frame !== frame) {
        if (frameWatch) { frameWatch.frame.removeEventListener('load', scan); retire(); }
        unwatchChild(); frameWatch = { frame: frame, view: null, doc: null, element: null };
        frame.addEventListener('load', scan);
      }
      try {
        var realm = frame.contentDocument;
        if (!realm) { return; } // Initial empty browsing context can be temporarily unavailable.
        var view = frame.contentWindow;
        if (!view || realm.defaultView !== view || view.document !== realm || view.frameElement !== frame || !internalDocument(realm)) { fail('iframe_identity_refused'); return; }
        if (frameWatch.doc && (frameWatch.doc !== realm || frameWatch.view !== view || frameWatch.element !== realm.documentElement)) { retire(); }
        frameWatch.doc = realm; frameWatch.view = view; frameWatch.element = realm.documentElement;
        if (realm.documentElement && (!childWatch || childWatch.doc !== realm || childWatch.element !== realm.documentElement)) {
          unwatchChild();
          var childObserver = new win.MutationObserver(scan);
          childObserver.observe(realm.documentElement, { subtree: true, childList: true, attributes: true });
          realm.addEventListener('load', scan, true);
          childWatch = { doc: realm, element: realm.documentElement, observer: childObserver };
        }
      } catch (error) { fail('iframe_inaccessible'); }
    }
    function value(root, k) { return [root.style.getPropertyValue(k), root.style.getPropertyPriority(k)]; }
    function equal(a, b) { return JSON.stringify(a) === JSON.stringify(b); }
    function density(root) { return densities.filter(function (c) { return root.classList.contains(c); }); }
    function writeDensity(root, set) { densities.forEach(function (c) { if (root.classList.contains(c) !== (set.indexOf(c) >= 0)) { root.classList[set.indexOf(c) >= 0 ? 'add' : 'remove'](c); } }); }
    function resize(q) { q.api.trigger(q.instance, 'resize'); }
    function restoreColor(r, k) {
      var own = r.owned[k], current = value(r.q.root, k);
      delete r.owned[k];
      if (!equal(current, own.last)) { r.blocked.add(k); return false; }
      if (equal(current, own.native)) { return false; }
      if (own.native[0]) { r.q.root.style.setProperty(k, own.native[0], own.native[1]); } else { r.q.root.style.removeProperty(k); }
      return true;
    }
    function restoreDensity(r) {
      var own = r.density; r.density = null;
      if (!equal(density(r.q.root), own.last)) { r.densityBlocked = true; return false; }
      if (equal(own.last, own.native)) { return false; }
      writeDensity(r.q.root, own.native); return true;
    }
    function release() {
      if (!record) { return; }
      var r = record, q = r.q;
      // Exact root/instance still identifiable; restoration never targets a replacement.
      if (!same(q, qualify(true))) { record = null; return; }
      var changed = false;
      Object.keys(r.owned).forEach(function (k) { changed = restoreColor(r, k) || changed; });
      if (r.density) { changed = restoreDensity(r) || changed; }
      if (changed) { try { resize(q); } catch (error) { /* Native values already restored. */ } }
    }
    function fail(reason) { release(); clear(); descriptor = null; state = reason; active = false; disconnect(); if (selector) { selector.fail(reason); } }
    function scan() {
      if (!active || closed || paused) { return; }
      try {
        if (descriptor && now() >= expires) { fail('appearance_expired'); return; }
        if ((!descriptor || !record) && now() >= deadline) { fail('readiness_timeout'); return; }
        if (!context()) { fail('context_refused'); return; }
        watchFrame();
        if (!active) { return; }
        var q = qualify();
        if (target && !same(target, q)) {
          if (p.embed !== 'iframe') { fail('ownership_lost'); return; }
          retire();
        }
        if (q) { target = q; }
        if (!q || !q.view.getComputedStyle(q.root).getPropertyValue(properties[0]).trim() ||
            !q.view.getComputedStyle(q.root).getPropertyValue('--h5p-theme-scaling').trim()) {
          if (now() >= deadline) { fail('readiness_timeout'); } else if (retry === null) { retry = later(function () { retry = null; scan(); }, 100); }
          return;
        }
        if (requalifying && !pending) { requalifying = false; refresh(); return; }
        if (selector && !selector.mount(q)) { fail('selector_collision'); return; }
        if (!descriptor) { return; }
        if (!record) { record = { q: q, owned: Object.create(null), blocked: new Set(), density: null, densityBlocked: false }; }
        var r = record, changed = false;
        // A changed grant can release colors (Core) without disturbing retained colors/Density.
        Object.keys(r.owned).forEach(function (k) {
          if (!Object.prototype.hasOwnProperty.call(descriptor.colors, k)) { changed = restoreColor(r, k) || changed; }
        });
        Object.keys(descriptor.colors).forEach(function (k) {
          var current = value(q.root, k), own = r.owned[k];
          if (own && !equal(current, own.last)) { delete r.owned[k]; r.blocked.add(k); }
          if (r.blocked.has(k)) { return; }
          var desired = [descriptor.colors[k], ''];
          if (!equal(current, desired)) {
            if (!own) { own = r.owned[k] = { native: current, last: desired }; }
            q.root.style.setProperty(k, desired[0], desired[1]); own.last = value(q.root, k); changed = true;
          }
        });
        if (r.density && !equal(density(q.root), r.density.last)) { r.density = null; r.densityBlocked = true; }
        if (descriptor.density === 'original' && r.density) { changed = restoreDensity(r) || changed; }
        if (descriptor.density !== 'original' && !r.densityBlocked) {
          var desiredDensity = ['h5p-' + descriptor.density], currentDensity = density(q.root);
          if (!equal(currentDensity, desiredDensity)) {
            if (!r.density) { r.density = { native: currentDensity, last: desiredDensity }; }
            writeDensity(q.root, desiredDensity); r.density.last = density(q.root); changed = true;
          }
        }
        state = 'applied';
        if (changed) { resize(q); }
        if (selector) { selector.applied(); }
      } catch (error) { fail('application_failed'); }
    }
    function refresh() {
      if (closed || paused || reload || !active) { return Promise.resolve(); }
      // Recheck elapsed expiry, context and owner even if mutation/timer delivery was delayed.
      var previous = generation; scan();
      if (!active || generation !== previous) { return Promise.resolve(); }
      if (record) { deadline = now() + 15000; }
      clear(); state = 'requesting';
      // Retain the old grant only until its original deadline, including during superseded reads.
      if (descriptor) { later(function () { fail('appearance_expired'); }, Math.max(0, expires - now())); }
      var seq = generation, start = now(), id, request, selection = selector ? selector.request() : null;
      try {
        if (!context()) { fail('context_refused'); return Promise.resolve(); }
        var bytes = new Uint8Array(16); win.crypto.getRandomValues(bytes);
        id = Array.prototype.map.call(bytes, function (b) { return ('0' + b.toString(16)).slice(-2); }).join('');
        abort = new win.AbortController(); pending = true;
        later(function () { if (seq === generation) { fail('transport_timeout'); } }, 10000);
        later(function () { if (seq === generation && !descriptor) { fail('readiness_timeout'); } }, Math.max(0, deadline - now()));
        request = win.fetch(gate.endpoint, { method: 'POST', credentials: 'same-origin', cache: 'no-store', mode: 'same-origin', referrerPolicy: 'same-origin', redirect: 'error', signal: abort.signal,
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8', 'X-PJH5PWP-Request': 'appearance-v1' },
          body: 'action=pjh5pwp_anonymous_appearance&request_id=' + id + '&nonce=' + gate.nonce +
            '&theme=' + encodeURIComponent(selection.theme) + '&density=' + encodeURIComponent(selection.density) +
            '&player=' + encodeURIComponent(JSON.stringify(gate.player)) });
      } catch (error) { fail('transport_failed'); return Promise.resolve(); }
      return request.then(function (response) {
        if (seq !== generation) { return null; }
        if (response.redirected || (!response.ok && response.status !== 409) || !/^application\/json(?:;|$)/i.test(response.headers.get('Content-Type') || '')) { throw new Error('response'); }
        return response.text();
      }).then(function (text) {
        if (seq !== generation) { return; }
        if (typeof text !== 'string' || text.length > 16384) { throw new Error('size'); }
        scan(); // A response can beat throttled frame load/mutation callbacks.
        if (seq !== generation || !active) { return; }
        var body = JSON.parse(text), parsed = validatePublic(body, id, now() - start, selection);
        if (!parsed) { throw new Error('descriptor'); }
        if (parsed.authenticated) { reload = true; fail('reload_required'); return; }
        if (selector) { selector.accept(body); }
        clear(); descriptor = parsed; expires = start + 30000;
        later(function () { fail('appearance_expired'); }, Math.max(0, expires - now()));
        later(refresh, Math.max(0, start + 25000 - now()));
        scan();
      }).catch(function () { if (seq === generation) { fail('transport_failed'); } });
    }
    function start() {
      if (closed || active) { return; }
      if (!selector) { return; }
      active = true; deadline = now() + 15000;
      observer = new win.MutationObserver(scan); observer.observe(doc.documentElement, { subtree: true, childList: true, attributes: true });
      win.addEventListener('pagehide', pause); win.addEventListener('pageshow', resume); win.addEventListener('focus', resume); doc.addEventListener('visibilitychange', visibility); win.addEventListener('popstate', scan); win.addEventListener('hashchange', scan);
      if (doc.visibilityState === 'hidden') { pause(); return; }
      later(function () { if (state !== 'applied') { fail('readiness_timeout'); } }, 15000);
      scan(); if (!pending) { refresh(); }
    }
    function pause() { if (closed) { return; } release(); clear(); descriptor = null; paused = true; state = 'paused'; disconnect(); }
    function resume() {
      if (!paused || closed || reload || doc.visibilityState === 'hidden') { return; }
      paused = false; active = true; deadline = now() + 15000;
      if (observer) { observer.observe(doc.documentElement, { subtree: true, childList: true, attributes: true }); }
      scan(); if (!pending) { refresh(); }
    }
    function visibility() { if (doc.visibilityState === 'hidden') { pause(); } else { resume(); } }
    function stop() {
      release(); clear(); descriptor = null; closed = true; active = false; disconnect();
      win.removeEventListener('pagehide', pause); win.removeEventListener('pageshow', resume); win.removeEventListener('focus', resume); doc.removeEventListener('visibilitychange', visibility); win.removeEventListener('popstate', scan); win.removeEventListener('hashchange', scan);
      state = 'stopped';
      if (selector) { selector.stop(); }
    }
    return Object.freeze({ start: start, refresh: refresh, stop: stop, snapshot: function () { return Object.freeze({ mode: 'anonymous', state: state, reload_required: reload,
      message: reload ? 'Authentication changed; reload this page to use your WordPress appearance.' : '', visibility: 'native_content_visible' }); } });
  }
  /** One shared selector, with the accepted lease/ownership state machine retained per activity. */
  function createGeneral(win, manifest, metadata, grants) {
    var workers = [], selector, terminal = null, closed = false;
    try {
      if (!exact(metadata, ['version', 'page', 'endpoint', 'home']) || metadata.version !== 1 ||
          !exact(manifest, ['version', 'endpoint', 'players']) || manifest.version !== 1 || manifest.endpoint !== metadata.endpoint ||
          !Array.isArray(manifest.players) || !manifest.players.length || manifest.players.length > 64 ||
          !Array.isArray(grants) || grants.length !== manifest.players.length) { return null; }
      var seen = new Set(), gates = manifest.players.map(function (player, index) {
        var grant = grants[index];
        if (!exact(grant, ['player', 'nonce']) || JSON.stringify(grant.player) !== JSON.stringify(player) || seen.has(player.content_id)) { throw new Error('grant'); }
        seen.add(player.content_id);
        var gate = { version: 1, page: metadata.page, endpoint: metadata.endpoint, home: metadata.home, content_id: player.content_id, nonce: grant.nonce, player: player };
        if (!validMetadata(win, { version: 1, endpoint: manifest.endpoint, players: [player] }, gate)) { throw new Error('metadata'); }
        return gate;
      });
      function refresh() { return closed || terminal ? Promise.resolve() : Promise.all(workers.map(function (worker) { return worker.refresh(); })); }
      function fail(reason) {
        if (closed || terminal) { return; } terminal = reason;
        workers.forEach(function (worker) { worker.stop(); }); selector.fail(reason);
      }
      selector = selectionClient(win, metadata.home, refresh);
      var shared = {
        request: selector.request, accept: selector.accept,
        mount: function () {
          // Placement follows rendered DOM order, independently of manifest order or runtime readiness.
          // This selects only a parent-page anchor; workers still prove each activity's appearance ownership.
          var nodes = win.document.querySelectorAll('.h5p-content[data-content-id], iframe[data-content-id]');
          for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i], frame = node.matches('iframe.h5p-iframe');
            if (!node.parentElement || (frame && !node.parentElement.matches('.h5p-iframe-wrapper')) ||
                (!frame && !node.matches('div.h5p-content'))) { continue; }
            if (manifest.players.some(function (player) { return player.content_id === node.getAttribute('data-content-id') && player.embed === (frame ? 'iframe' : 'div'); })) {
              return selector.mount({ frame: frame ? node : null, root: node });
            }
          }
          return false;
        },
        applied: function () { if (workers.every(function (worker) { return worker.snapshot().state === 'applied'; })) { selector.applied(); } },
        fail: fail, stop: function () { /* The page owns the single selector and storage listener. */ }
      };
      workers = gates.map(function (gate) { return create(win, { version: 1, endpoint: manifest.endpoint, players: [gate.player] }, gate, shared); });
      if (workers.some(function (worker) { return !worker; })) { throw new Error('worker'); }
      return Object.freeze({
        start: function () { if (!closed && !terminal) { workers.forEach(function (worker) { if (!terminal) { worker.start(); } }); } },
        refresh: refresh,
        stop: function () { closed = true; workers.forEach(function (worker) { worker.stop(); }); selector.stop(); },
        snapshot: function () {
          var states = workers.map(function (worker) { return worker.snapshot().state; });
          return Object.freeze({ mode: 'anonymous', state: closed ? 'stopped' : (terminal || (states.every(function (s) { return s === 'applied'; }) ? 'applied' : 'awaiting_activities')),
            activities: states, reload_required: terminal === 'reload_required', visibility: 'native_content_visible' });
        }
      });
    } catch (error) { workers.forEach(function (worker) { if (worker) { worker.stop(); } }); if (selector) { selector.stop(); } return null; }
  }
  function bootstrap(win, script) {
    var controller = null, terminal = 'awaiting_manifest', finished = false, timer;
    function ready() {
      if (finished) { return; } finished = true; win.clearTimeout(timer); win.document.removeEventListener('DOMContentLoaded', ready);
      try {
        var nodes = win.document.querySelectorAll('[data-pjh5pwp-manifest]');
        var metadata = JSON.parse(script.getAttribute('data-pjh5pwp-anonymous-config'));
        var grants = win.document.querySelectorAll('[data-pjh5pwp-anonymous-grants]');
        if (nodes.length === 0 && grants.length === 0) { terminal = 'no_eligible_activities'; return; }
        if (nodes.length === 1 && grants.length === 1) {
          controller = createGeneral(win, JSON.parse(nodes[0].getAttribute('data-pjh5pwp-manifest')), metadata, JSON.parse(grants[0].getAttribute('data-pjh5pwp-anonymous-grants')));
        }
        if (controller) { controller.start(); } else { terminal = 'metadata_refused'; }
      } catch (error) { terminal = 'metadata_refused'; }
    }
    timer = win.setTimeout(function () { finished = true; terminal = 'readiness_timeout'; win.document.removeEventListener('DOMContentLoaded', ready); }, 15000);
    if (win.document.readyState === 'loading') { win.document.addEventListener('DOMContentLoaded', ready); } else { ready(); }
    return Object.freeze({ snapshot: function () { return controller ? controller.snapshot() : Object.freeze({ mode: 'anonymous', state: terminal, reload_required: false, visibility: 'native_content_visible' }); },
      refresh: function () { return controller ? controller.refresh() : Promise.resolve(); }, stop: function () { finished = true; win.clearTimeout(timer); win.document.removeEventListener('DOMContentLoaded', ready); if (controller) { controller.stop(); } } });
  }
  return { create: create, createGeneral: createGeneral, bootstrap: bootstrap, validate: validate, validatePublic: validatePublic, colors: colors, properties: properties.slice() };
}));
