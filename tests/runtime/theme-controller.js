/* Deterministic DOM/CSSOM/fetch/clock fixtures. No browser, CMS or live site. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const crypto = require('node:crypto');
const vm = require('node:vm');
const api = require('../../assets/js/theme-controller.js');
let checks = 0;
function eq(actual, expected, message) { assert.deepEqual(actual, expected, message); checks++; }
function ok(value, message) { assert.ok(value, message); checks++; }
const P = '--h5p-theme-main-cta-base', Q = '--h5p-theme-background';
const examples = JSON.parse(fs.readFileSync('docs/runtime-transport-examples.json', 'utf8'));
const clone = value => JSON.parse(JSON.stringify(value));
function envelope(id, css = P + ':#123;', core = false) {
  const body = clone(examples['Core + Original']); body.request_id = id;
  if (!core) { body.appearance.theme = { source: 'personal', kind: 'custom', id: 'fixture', version: null }; }
  body.appearance.color_declarations = core ? '' : css;
  return body;
}
class Events {
  constructor() { this.listeners = new Map(); }
  addEventListener(name, fn) { if (!this.listeners.has(name)) this.listeners.set(name, new Set()); this.listeners.get(name).add(fn); }
  removeEventListener(name, fn) { this.listeners.get(name)?.delete(fn); }
  emit(name, detail = {}) { for (const fn of [...(this.listeners.get(name) || [])]) fn({ type: name, target: this, ...detail }); }
  count() { return [...this.listeners.values()].reduce((a, set) => a + set.size, 0); }
}
class Style {
  constructor() { this.values = new Map(); this.writes = 0; }
  getPropertyValue(name) { return this.values.get(name)?.value || ''; }
  getPropertyPriority(name) { return this.values.get(name)?.priority || ''; }
  setProperty(name, value, priority = '') { this.values.set(name, { value, priority }); this.writes++; }
  removeProperty(name) { this.values.delete(name); this.writes++; }
}
class Element extends Events {
  constructor(doc, tag, classes = '', attrs = {}) {
    super(); this.ownerDocument = doc; this.tag = tag; this.classes = new Set(classes.split(' ').filter(Boolean));
    this.attrs = attrs; this.children = []; this.parentNode = null; this.style = new Style();
  }
  get parentElement() { return this.parentNode instanceof Element ? this.parentNode : null; }
  get classList() {
    return { contains: name => this.classes.has(name),
      add: name => { this.classes.add(name); this.classWrites = (this.classWrites || 0) + 1; },
      remove: name => { this.classes.delete(name); this.classWrites = (this.classWrites || 0) + 1; } };
  }
  get id() { return this.getAttribute('id'); }
  get isConnected() { return this.ownerDocument.documentElement.contains(this); }
  append(node) { node.remove(); this.children.push(node); node.parentNode = this; return node; }
  remove() { if (this.parentNode?.children) this.parentNode.children = this.parentNode.children.filter(n => n !== this); this.parentNode = null; }
  contains(node) { return node === this || this.children.some(child => child.contains(node)); }
  getAttribute(name) { return this.attrs[name] ?? null; }
  hasAttribute(name) { return Object.hasOwn(this.attrs, name); }
  setAttribute(name, value) { this.attrs[name] = String(value); }
  removeAttribute(name) { delete this.attrs[name]; }
  focus(options) { this.ownerDocument.activeElement = this; this.focusOptions = options; this.focusCalls = (this.focusCalls || 0) + 1; }
  matches(selector) {
    return selector.split(',').some(raw => {
      const s = raw.trim(), tag = s.match(/^[a-z]+/i)?.[0];
      if (tag && tag !== this.tag) return false;
      for (const [, name] of s.matchAll(/\.([\w-]+)/g)) if (!this.classes.has(name)) return false;
      for (const [, name, val] of s.matchAll(/\[([\w-]+)(?:="([^"]*)")?\]/g)) {
        if (!this.hasAttribute(name) || (val !== undefined && this.getAttribute(name) !== val)) return false;
      }
      return true;
    });
  }
  closest(selector) { return this.matches(selector) ? this : this.parentElement?.closest(selector) || null; }
}
class Doc extends Events {
  constructor(url = 'https://fixture.test/lesson/', iframe = false) {
    super(); this.URL = url; this.readyState = 'complete'; this.visibilityState = 'visible';
    this.documentElement = new Element(this, 'html', iframe ? 'h5p-iframe' : '');
    this.body = this.documentElement.append(new Element(this, 'body'));
  }
  querySelectorAll(selector) {
    const nodes = []; const visit = n => { if (n.matches(selector)) nodes.push(n); n.children.forEach(visit); };
    visit(this.documentElement); return nodes;
  }
}
async function flush() { for (let i = 0; i < 12; i++) await Promise.resolve(); }
function fixture(players = [{ content_id: '10', embed: 'div', library: 'H5P.Accordion 1.0' }], options = {}) {
  const win = new Events(), doc = new Doc(), timers = new Map(), observers = [], requests = [];
  let time = 0, serial = 0;
  const h5p = () => ({ instances: [], trigger: (instance, event) => {
    if (instance.trigger !== undefined) instance.trigger(event);
    else if (instance.$?.trigger !== undefined) instance.$.trigger(event);
  } });
  Object.assign(win, { document: doc, URL, location: new URL(doc.URL), H5P: h5p(),
    H5PIntegration: { contents: {} }, crypto: { getRandomValues: a => crypto.randomFillSync(a) },
    AbortController, performance: { now: () => time },
    getComputedStyle: root => ({ getPropertyValue: name => root.stylesReady === false ? '' : (root.style.getPropertyValue(name) || '#native') }),
    setTimeout: (fn, delay) => { const id = ++serial; timers.set(id, { fn, at: time + delay }); return id; },
    clearTimeout: id => timers.delete(id),
    MutationObserver: class { constructor(fn) { this.fn = fn; observers.push(this); } observe(node) { this.node = node; this.connected = true; } disconnect() { this.connected = false; } },
    fetch: (url, init) => new Promise((resolve, reject) => {
      const id = new URLSearchParams(init.body).get('request_id');
      requests.push({ url, init, id, fail: () => reject(new Error('offline')), respond: (body = envelope(id), changes = {}) => resolve({ ok: true, redirected: false,
        headers: { get: () => 'application/json; charset=UTF-8' }, text: () => Promise.resolve(typeof body === 'string' ? body : JSON.stringify(body)), ...changes }) });
    })
  });
  win.top = win; doc.defaultView = win;
  const manifest = { version: 1, endpoint: 'https://fixture.test/wp-admin/admin-ajax.php', players };
  function player(p, iframeDocument) {
    let realm = doc, view = win, frame, wrapper;
    if (p.embed === 'iframe') {
      wrapper = doc.body.append(new Element(doc, 'div', 'h5p-iframe-wrapper'));
      frame = wrapper.append(new Element(doc, 'iframe', 'h5p-iframe h5p-initialized', { id: 'h5p-iframe-' + p.content_id, 'data-content-id': p.content_id, src: 'about:blank' }));
      realm = iframeDocument || new Doc('about:blank', true);
      view = { document: realm, frameElement: frame, H5P: { ...h5p(), externalEmbed: false }, H5PIntegration: win.H5PIntegration, getComputedStyle: win.getComputedStyle };
      realm.defaultView = view; frame.contentDocument = realm; frame.contentWindow = view;
    }
    const root = realm.body.append(new Element(realm, 'div', 'h5p-content h5p-initialized h5p-large', { 'data-content-id': p.content_id }));
    const container = root.append(new Element(realm, 'div', 'h5p-container h5p-standalone h5p-theme'));
    const [machine, version] = p.library.split(' '), [majorVersion, minorVersion] = version.split('.');
    const instance = { contentId: p.content_id, libraryInfo: { versionedName: p.library, machineName: machine, majorVersion, minorVersion }, isRoot: () => true,
      resizeCalls: [], trigger(event) { this.resizeCalls.push(event); } };
    view.H5P.instances.push(instance); win.H5PIntegration.contents['cid-' + p.content_id] = { library: p.library };
    return { p, root, container, frame, wrapper, instance, doc: realm, view };
  }
  const items = players.map(p => player(p));
  const controller = api.create(win, manifest);
  if (!options.noStart) controller.start();
  async function advance(ms) {
    const target = time + ms;
    for (;;) {
      const next = [...timers].filter(([, t]) => t.at <= target).sort((a, b) => a[1].at - b[1].at)[0];
      if (!next) break;
      time = next[1].at; timers.delete(next[0]); next[1].fn(); await flush();
    }
    time = target; await flush();
  }
  async function mutate() { observers.filter(o => o.connected).forEach(o => o.fn([])); await advance(25); }
  return { win, doc, items, manifest, controller, requests, timers, observers, player, advance, mutate,
    el: (...args) => new Element(doc, ...args), jump: ms => { time += ms; } };
}
async function run() {
  const requestId = 'a'.repeat(32);
  for (const body of Object.values(examples)) ok(api.validate(body, requestId, 0), 'Real PHP transport examples accepted');
  const builtinIds = [...fs.readFileSync('presets/moodle-478fed30d2a4.php', 'utf8').matchAll(/'id' => '([a-z]+)'/g)].map(m => m[1]);
  for (const id of builtinIds) {
    const body = clone(examples['Lavender + Large']); body.appearance.theme.id = id;
    ok(api.validate(body, requestId, 0), 'Canonical pinned builtin ID accepted: ' + id);
  }
  const phpProperties = fs.readFileSync('includes/Theme/class-color-properties.php', 'utf8').split('public static function groups')[0].match(/--h5p-theme-[a-z0-9-]+/g);
  eq(api.properties, phpProperties, 'Browser allowlist exactly matches approved PHP inventory');
  for (const [label, edit] of [
    ['unknown structure', b => b.extra = 1], ['private field', b => b.appearance.user_id = 5],
    ['unknown property', b => b.appearance.color_declarations = '--evil:#fff;'],
    ['URL', b => b.appearance.color_declarations = P + ':url(https://evil/);'],
    ['selector', b => b.appearance.color_declarations = 'body{' + P + ':#fff;}'],
    ['bad expression', b => b.appearance.color_declarations = '--h5p-theme-contrast-cta-light:var(--evil);'],
    ['expression wrong property', b => b.appearance.color_declarations = P + ':rgba(59, 130, 246, 0.15);'],
    ['duplicate', b => b.appearance.color_declarations += b.appearance.color_declarations],
    ['trailing newline', b => b.appearance.color_declarations = P + ':#fff\n;'],
    ['priority injection', b => b.appearance.color_declarations = P + ':#fff!important;'],
    ['missing semicolon', b => b.appearance.color_declarations = P + ':#fff'],
    ['density shape', b => b.appearance.density.preserve_native = false],
    ['Core with colors', b => b.appearance.theme = clone(examples['Core + Original'].appearance.theme)],
    ['bad theme', b => b.appearance.theme.id = '../bad'], ['bad expiry', b => b.refresh.max_age_seconds = 60],
    ['reuse', b => b.refresh.reuse = true], ['bad correlation', b => b.request_id = 'b'.repeat(32)],
    ['false admission with CSS', b => b.appearance.admission.permitted = false]
  ]) { const body = envelope(requestId); edit(body); eq(api.validate(body, requestId, 0), null, label + ' rejected'); }
  eq(api.validate(envelope(requestId), requestId, 30000), null, 'Expired descriptor rejected');
  eq(api.validate(envelope(requestId), requestId, -1), null, 'Invalid age rejected');
  ok(api.validate(envelope(requestId, '--h5p-theme-contrast-cta-light:rgba(59, 130, 246, 0.15);'), requestId, 1), 'Exact pinned dark expression accepted');
  const f = fixture(); await flush();
  const root = f.items[0].root, style = root.style;
  style.setProperty(P, '#original', 'important'); style.setProperty('--unrelated', 'keep', 'important');
  f.requests[0].respond(); await flush();
  eq(style.getPropertyValue(P), '#123', 'Initial DIV application'); eq(style.getPropertyPriority(P), '', 'No important application');
  eq([...root.classes], ['h5p-content', 'h5p-initialized', 'h5p-large'], 'Density classes unchanged');
  const writes = style.writes; await f.mutate(); eq(style.writes, writes, 'Idempotent scan performs no writes');
  let promise = f.controller.refresh(); await flush();
  eq(style.getPropertyValue(P), '#original', 'Refresh clears admission and restores original');
  eq(style.getPropertyPriority(P), 'important', 'Original priority preserved');
  f.requests.at(-1).respond(envelope(f.requests.at(-1).id, P + ':#456;' + Q + ':#abc;')); await promise;
  eq(style.getPropertyValue(P), '#456', 'Theme switch'); eq(style.getPropertyValue(Q), '#abc', 'Additional property');
  promise = f.controller.refresh(); await flush(); f.requests.at(-1).respond(envelope(f.requests.at(-1).id, '', true)); await promise;
  eq(style.getPropertyValue(P), '#original', 'Core restoration'); eq(style.getPropertyValue(Q), '', 'Core removes only owned newly introduced property');
  eq(style.getPropertyValue('--unrelated'), 'keep', 'Unrelated property preserved');
  promise = f.controller.refresh(); await flush(); f.requests.at(-1).respond(); await promise;
  style.setProperty(P, '#thirdparty', 'important'); await f.mutate();
  promise = f.controller.refresh(); await flush(); f.requests.at(-1).respond(); await promise;
  eq(style.getPropertyValue(P), '#thirdparty', 'Third-party change relinquishes property across refresh');
  f.controller.stop(); eq(style.getPropertyPriority(P), 'important', 'Third-party priority retained on stop');
  eq(f.timers.size, 0, 'All timers cleaned'); eq(f.observers.filter(o => o.connected).length, 0, 'All observers disconnected');
  eq(f.win.count() + f.doc.count(), 0, 'All listeners removed');

  const multi = fixture([{ content_id: '10', embed: 'div', library: 'H5P.Accordion 1.0' }, { content_id: '11', embed: 'iframe', library: 'H5P.Column 1.2' }]);
  await flush(); multi.requests[0].respond(); await flush();
  for (const item of multi.items) eq(item.root.style.getPropertyValue(P), '#123', 'Distinct DIV/iframe players independently applied');
  const old = multi.items[1], oldDoc = old.doc;
  const replacement = multi.player(old.p); old.wrapper.remove(); await multi.mutate(); replacement.frame.emit('load'); await flush();
  eq(replacement.root.style.getPropertyValue(P), '#123', 'Replaced iframe document qualified');
  ok(!multi.observers.some(o => o.connected && o.node === oldDoc.documentElement), 'Old iframe observer disconnected');
  const detached = multi.items[0].root; detached.remove(); await multi.mutate();
  eq(detached.style.getPropertyValue(P), '', 'Detached root restores with intact unique ownership proof');
  multi.doc.body.append(detached); await multi.mutate(); eq(detached.style.getPropertyValue(P), '#123', 'Reinserted root safely reacquired');
  const nested = detached.append(multi.el('div', 'h5p-content h5p-initialized', { 'data-content-id': '99' }));
  nested.append(multi.el('div', 'h5p-container h5p-theme h5p-standalone')); await multi.mutate();
  eq(nested.style.writes, 0, 'Nested child receives no independent overrides');
  multi.controller.stop(); eq(replacement.frame.count(), 0, 'Iframe load listeners removed');

  const reload = fixture([{ content_id: '10', embed: 'iframe', library: 'H5P.Accordion 1.0' }]);
  await flush(); reload.requests[0].respond(); await flush();
  const prior = reload.items[0], priorWrites = prior.root.style.writes;
  const rewritten = reload.player(prior.p); rewritten.wrapper.remove();
  prior.frame.contentDocument = rewritten.doc; prior.frame.contentWindow = rewritten.view;
  rewritten.view.frameElement = prior.frame; prior.frame.emit('load'); await flush();
  eq(rewritten.root.style.getPropertyValue(P), '#123', 'Same iframe rewritten document requalified');
  eq(prior.root.style.writes, priorWrites, 'Discarded iframe document not mutated after replacement');
  ok(!reload.observers.some(o => o.connected && o.node === prior.doc.documentElement), 'Rewritten iframe disconnects prior document observer');
  reload.controller.stop();
  const inheritedURL = fixture([{ content_id: '10', embed: 'iframe', library: 'H5P.Accordion 1.0' }]);
  inheritedURL.items[0].doc.URL = inheritedURL.doc.URL + '#inherited';
  await flush(); inheritedURL.requests[0].respond(); await flush();
  eq(inheritedURL.items[0].root.style.getPropertyValue(P), '#123', 'Internal document.open caller URL accepted'); inheritedURL.controller.stop();

  const rebound = fixture(); await flush(); rebound.requests[0].respond(); await flush();
  rebound.items[0].root.remove();
  const cloneRoot = rebound.doc.body.append(rebound.el('div', 'h5p-content h5p-initialized', { 'data-content-id': '10' }));
  cloneRoot.append(rebound.el('div', 'h5p-container h5p-standalone h5p-theme')); await rebound.mutate();
  eq(cloneRoot.style.writes, 0, 'New root cannot borrow the previous root instance'); rebound.controller.stop();
  const replacedContainer = fixture(); await flush(); replacedContainer.requests[0].respond(); await flush();
  replacedContainer.items[0].container.remove();
  replacedContainer.items[0].root.append(replacedContainer.el('div', 'h5p-container h5p-standalone h5p-theme'));
  const containerWrites = replacedContainer.items[0].root.style.writes; await replacedContainer.mutate();
  eq(replacedContainer.items[0].root.style.writes, containerWrites, 'Changed container ownership cannot be reacquired in the same scan'); replacedContainer.controller.stop();

  const already = fixture(undefined, { noStart: true }); already.items[0].root.style.setProperty(P, '#123');
  already.controller.start(); await flush(); already.requests[0].respond(); await flush();
  const unchangedWrites = already.items[0].root.style.writes; already.controller.stop();
  eq(already.items[0].root.style.writes, unchangedWrites, 'Matching pre-existing value never claimed or rewritten');

  const suspend = fixture(); await flush(); suspend.requests[0].respond(); await flush();
  suspend.items[0].root.style.setProperty(P, '#snordian', 'important');
  const suspending = suspend.controller.refresh(); await flush();
  const suspendedBody = clone(examples['SNORDIAN suspension']); suspendedBody.request_id = suspend.requests.at(-1).id;
  suspend.requests.at(-1).respond(suspendedBody); await suspending;
  eq(suspend.items[0].root.style.getPropertyValue(P), '#snordian', 'Later suspension leaves SNORDIAN-owned change intact');
  eq(suspend.items[0].root.style.getPropertyPriority(P), 'important', 'Later suspension preserves foreign priority'); suspend.controller.stop();

  for (const [label, edit] of [
    ['duplicate ID', f => f.doc.body.append(f.el('div', 'h5p-content', { 'data-content-id': '10' }))],
    ['child owner', f => f.items[0].instance.parent = {}], ['subcontent owner', f => f.items[0].instance.subContentId = 'child'],
    ['non-root', f => f.items[0].instance.isRoot = () => false], ['library mismatch', f => f.items[0].instance.libraryInfo.majorVersion = 9],
    ['ambiguous instances', f => f.win.H5P.instances.push({ ...f.items[0].instance })],
    ['uninitialized classes', f => f.items[0].root.classes.delete('h5p-initialized')],
    ['H5P-like element only', f => f.win.H5P.instances = []],
    ['nested main', f => { const outer = f.doc.body.append(f.el('div', 'h5p-content')); outer.append(f.items[0].root); }],
    ['editor', f => f.win.H5PEditor = {}]
  ]) {
    const bad = fixture(); edit(bad); await flush(); bad.requests[0].respond(); await flush();
    eq(bad.items[0].root.style.writes, 0, label + ' unchanged'); bad.controller.stop();
  }
  const frameP = [{ content_id: '10', embed: 'iframe', library: 'H5P.Accordion 1.0' }];
  for (const [label, edit] of [
    ['external frame', f => { const frame = f.items[0].frame; frame.attrs.src = 'https://other.test/'; Object.defineProperty(frame, 'contentDocument', { get() { f.crossOriginTouches = (f.crossOriginTouches || 0) + 1; throw new Error('Cross-origin access attempted'); } }); }],
    ['srcdoc frame', f => f.items[0].frame.attrs.srcdoc = 'anything'],
    ['sandbox frame', f => f.items[0].frame.attrs.sandbox = 'allow-same-origin'],
    ['foreign integration', f => f.items[0].view.H5PIntegration = {}],
    ['unrelated internal document URL', f => f.items[0].doc.URL = 'https://fixture.test/unrelated/'],
    ['external iframe runtime', f => f.items[0].view.H5P.externalEmbed = true]
  ]) {
    const bad = fixture(frameP); edit(bad); await flush(); bad.requests[0].respond(); await flush();
    eq(bad.items[0].root.style.writes, 0, label + ' rejected');
    if (label === 'external frame') eq(bad.crossOriginTouches || 0, 0, 'Cross-origin src rejected before document access');
    bad.controller.stop();
  }
  const late = fixture(); late.win.H5P.instances = []; late.items[0].root.stylesReady = false;
  await flush(); late.requests[0].respond(); await flush(); eq(late.items[0].root.style.writes, 0, 'Late initialization waits');
  late.win.H5P.instances.push(late.items[0].instance); await late.advance(50);
  eq(late.items[0].root.style.writes, 0, 'Stylesheet readiness waits');
  late.items[0].root.stylesReady = true; late.doc.emit('load'); await flush();
  eq(late.items[0].root.style.getPropertyValue(P), '#123', 'Delayed stylesheet load applies'); late.controller.stop();
  const bounded = fixture(); bounded.win.H5P.instances = []; await flush(); bounded.requests[0].fail(); await flush();
  await bounded.advance(20000); eq(bounded.timers.size, 0, 'Initialization retries terminate after bounded schedule');
  ok(bounded.observers.filter(o => o.connected).length <= 1, 'Observer count bounded by candidate documents'); bounded.controller.stop();

  const loading = fixture(undefined, { noStart: true }); loading.doc.readyState = 'loading'; loading.controller.start(); await flush();
  eq(loading.requests.length, 0, 'Loading document waits for DOM readiness');
  loading.doc.readyState = 'interactive'; loading.doc.emit('DOMContentLoaded'); await flush(); eq(loading.requests.length, 1, 'DOMContentLoaded starts initial fetch'); loading.controller.stop();

  const stale = fixture(); await flush(); stale.requests[0].respond(); await flush();
  const r1 = stale.controller.refresh(); await flush(); const first = stale.requests.at(-1);
  const r2 = stale.controller.refresh(); await flush(); const second = stale.requests.at(-1);
  ok(first.init.signal.aborted, 'Superseded request aborted');
  second.respond(envelope(second.id, P + ':#222;')); await r2;
  first.respond(envelope(first.id, P + ':#111;')); await r1;
  eq(stale.items[0].root.style.getPropertyValue(P), '#222', 'Out-of-order response ignored even if abort ignored');
  let req = stale.controller.refresh(); await flush(); const expired = stale.requests.at(-1); stale.jump(30000); expired.respond(); await req;
  eq(stale.items[0].root.style.getPropertyValue(P), '', 'Already expired delivery is non-actionable');
  for (const [label, response] of [
    ['network failure', r => r.fail()], ['malformed JSON', r => r.respond('{bad')], ['HTTP error', r => r.respond(null, { ok: false })],
    ['redirect', r => r.respond(null, { redirected: true })], ['wrong MIME', r => r.respond(null, { headers: { get: () => 'text/html' } })],
    ['unknown response field', r => r.respond({ ...envelope(r.id), user_id: 1 })],
    ['SNORDIAN suspension', r => { const b = clone(examples['SNORDIAN suspension']); b.request_id = r.id; r.respond(b); }]
  ]) {
    req = stale.controller.refresh(); await flush(); stale.requests.at(-1).respond(); await req;
    req = stale.controller.refresh(); await flush(); response(stale.requests.at(-1)); await req;
    eq(stale.items[0].root.style.getPropertyValue(P), '', label + ' clears owned overrides');
  }
  req = stale.controller.refresh(); await flush(); stale.requests.at(-1).respond(); await req;
  const oldCount = stale.requests.length; await stale.advance(30000);
  eq(stale.requests.length, oldCount + 1, 'One 25-second replacement remains pending at hard expiry');
  eq(stale.items[0].root.style.getPropertyValue(P), '', 'Hard expiry restores while replacement is pending');
  stale.requests.at(-1).fail(); await flush(); const failedCount = stale.requests.length;
  await stale.advance(120000); eq(stale.requests.length, failedCount, 'Failed refresh never polls');
  for (const event of ['focus', 'pageshow']) { stale.win.emit(event); await stale.advance(1000); stale.requests.at(-1).respond(); await flush(); eq(stale.items[0].root.style.getPropertyValue(P), '#123', event + ' refreshes'); }
  stale.doc.visibilityState = 'hidden'; stale.doc.emit('visibilitychange'); await flush();
  eq(stale.items[0].root.style.getPropertyValue(P), '', 'Hidden tab revokes admission');
  const hiddenCount = stale.requests.length; await stale.advance(60000); eq(stale.requests.length, hiddenCount, 'No hidden polling');
  stale.doc.visibilityState = 'visible'; stale.doc.emit('visibilitychange'); await flush(); stale.requests.at(-1).respond(); await flush();
  eq(stale.items[0].root.style.getPropertyValue(P), '#123', 'Visibility return refreshes');
  stale.win.emit('pagehide'); eq(stale.observers.filter(o => o.connected).length, 0, 'Pagehide releases observers for bfcache');
  stale.win.emit('pageshow'); await stale.advance(1000); stale.requests.at(-1).respond(); await flush();
  eq(stale.items[0].root.style.getPropertyValue(P), '#123', 'Bfcache pageshow requalifies');
  req = stale.controller.refresh(); await flush(); await stale.advance(10000); ok(stale.requests.at(-1).init.signal.aborted, 'Hanging request timeout');
  stale.requests.at(-1).respond(); await req; eq(stale.items[0].root.style.getPropertyValue(P), '', 'Late timed-out response ignored'); stale.controller.stop();

  const ambiguity = fixture(); await flush(); ambiguity.requests[0].respond(); await flush();
  ambiguity.doc.body.append(ambiguity.el('div', 'h5p-content', { 'data-content-id': '10' }));
  const before = ambiguity.items[0].root.style.writes; await ambiguity.mutate(); ambiguity.controller.stop();
  eq(ambiguity.items[0].root.style.writes, before, 'New ambiguity forbids even restoration writes');
  const dormant = fixture(undefined, { noStart: true }); await flush(); eq(dormant.requests.length, 0, 'Factory is dormant until explicitly started');
  ok(dormant.controller.diagnostics().every(d => Object.keys(d).join(',') === 'content_id,status'), 'Diagnostics contain no private fields');
  eq(api.create(dormant.win, { ...dormant.manifest, endpoint: 'https://other.test/wp-admin/admin-ajax.php' }), null, 'Cross-origin endpoint rejected');
  eq(api.create(dormant.win, { ...dormant.manifest, players: [dormant.manifest.players[0], dormant.manifest.players[0]] }), null, 'Duplicate manifest rejected');
  dormant.win.top = {}; eq(api.create(dormant.win, dormant.manifest), null, 'Framed parent rejected');
  const automatic = fixture(undefined, { noStart: true });
  automatic.doc.currentScript = { getAttribute: () => JSON.stringify(automatic.manifest) };
  const source = fs.readFileSync('assets/js/theme-controller.js', 'utf8');
  const context = { window: automatic.win, document: automatic.doc };
  vm.runInNewContext(source, context); await flush();
  eq(automatic.requests.length, 1, 'Static asset boots only from public script attribute');
  vm.runInNewContext(source, context); await flush(); eq(automatic.requests.length, 1, 'Duplicate asset execution attaches no second controller');
  automatic.win['pjh5pwp-theme-controller'].stop();
  const missing = fixture(undefined, { noStart: true });
  vm.runInNewContext(source, { window: missing.win, document: missing.doc }); await flush();
  eq(missing.requests.length, 0, 'Asset without attachment attribute remains dormant');
  const init = f.requests[0].init;
  eq([init.method, init.credentials, init.cache, init.mode, init.redirect], ['POST', 'same-origin', 'no-store', 'same-origin', 'error'], 'Transport cache/session/origin options');
  eq([...new URLSearchParams(init.body).keys()], ['action', 'request_id'], 'Only allowed request fields');
  eq(init.headers['X-PJH5PWP-Request'], 'appearance-v1', 'Required public transport header');
  console.log(`Phase 2B-2C JavaScript: ${checks} assertions passed (isolated fixtures; no real browser).`);
}
module.exports = { fixture, flush, envelope, examples, clone, P, Q };
if (require.main === module) run().catch(error => { console.error(error); process.exitCode = 1; });
