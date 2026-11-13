/* Native Density/Theme integration fixtures. No WordPress bootstrap or real browser. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const controllerApi = require('../../assets/js/theme-controller.js');
const { fixture, flush, envelope, examples, clone, P } = require('./theme-controller.js');
let checks = 0;
function eq(a, b, message) { assert.deepEqual(a, b, message); checks++; }
function ok(a, message) { assert.ok(a, message); checks++; }
const native = ['h5p-small', 'h5p-medium', 'h5p-large'];
const state = item => native.filter(name => item.root.classes.has(name));
function set(item, names) { native.forEach(name => item.root.classes.delete(name)); names.forEach(name => item.root.classes.add(name)); }
function body(request, density, example) {
  const b = example ? clone(examples[example]) : envelope(request.id);
  b.request_id = request.id; b.appearance.density = { selection: density, preserve_native: density === 'original' };
  return b;
}
async function deliver(f, density, example) {
  await flush(); const r = f.requests.at(-1); r.respond(body(r, density, example)); await flush();
}
async function refresh(f, density, example) {
  const pending = f.controller.refresh(); await deliver(f, density, example); await pending;
}
const div = { content_id: '10', embed: 'div', library: 'H5P.Accordion 1.0' };
const frame = { content_id: '11', embed: 'iframe', library: 'H5P.Column 1.2' };
async function run() {
  for (const initial of [[], ['h5p-small'], ['h5p-medium'], ['h5p-large'], native]) {
    const f = fixture(); const item = f.items[0]; set(item, initial);
    await deliver(f, 'original'); eq(state(item), initial, 'Original preserves the exact initialized native set');
    eq(item.root.classWrites || 0, 0, 'Original never writes a replacement class');
    eq(item.instance.resizeCalls.length, 0, 'Original no-op never resizes');
    for (const selection of ['small', 'medium', 'large']) {
      await refresh(f, selection); eq(state(item), ['h5p-' + selection], selection + ' native class');
      const writes = item.root.classWrites || 0, resizes = item.instance.resizeCalls.length;
      await f.mutate(); await f.mutate();
      eq(item.root.classWrites || 0, writes, 'Repeated application is idempotent');
      eq(item.instance.resizeCalls.length, resizes, 'No resize loop on idempotent scans');
      await refresh(f, 'original'); eq(state(item), initial, 'Original restores captured classes including empty/multiple/Large');
    }
    f.controller.stop(); eq(state(item), initial, 'Stop preserves native state');
  }
  const independent = fixture(); const item = independent.items[0];
  await deliver(independent, 'small', 'Core + Original');
  eq(item.root.style.getPropertyValue(P), '', 'Core + Small has no Papi Jo colors');
  eq(state(item), ['h5p-small'], 'Core + Small permits authorized Density');
  await refresh(independent, 'large', 'Lavender + Large');
  eq(state(item), ['h5p-large'], 'Lavender + Large');
  eq(item.root.style.getPropertyValue(P), examples['Lavender + Large'].appearance.color_declarations.match(/--h5p-theme-main-cta-base:([^;]+);/)[1], 'Lavender colors applied');
  await refresh(independent, 'medium', 'Mint + Small');
  eq(state(item), ['h5p-medium'], 'Mint + Medium independent of example name');
  const mint = item.root.style.getPropertyValue(P);
  await refresh(independent, 'small', 'Mint + Small');
  eq(item.root.style.getPropertyValue(P), mint, 'Density-only refresh preserves Theme result');
  await refresh(independent, 'small', 'Lavender + Large');
  eq(state(item), ['h5p-small'], 'Theme-only refresh preserves Density result');
  await refresh(independent, 'small', 'Site default + Original');
  eq(state(item), ['h5p-small'], 'Site default Theme + Small');
  eq(item.root.classes.has('h5p-theme'), false, 'No native Theme class forced onto root');
  independent.controller.stop();

  // Stylesheet and API readiness are independent from color readiness.
  const separate = fixture(); const separateItem = separate.items[0];
  separate.win.getComputedStyle = root => ({ getPropertyValue: name => name === '--h5p-theme-scaling' ? '1' : '' });
  await deliver(separate, 'small'); eq(state(separateItem), ['h5p-small'], 'Density does not depend on native color readiness');
  eq(separateItem.root.style.writes, 0, 'Colors wait independently'); separate.controller.stop();
  const styles = fixture(); const si = styles.items[0];
  styles.win.getComputedStyle = root => ({ getPropertyValue: name => name === '--h5p-theme-scaling' ? '' : '#native' });
  await deliver(styles, 'small'); eq(state(si), ['h5p-large'], 'Missing native scaling waits without mutation');
  eq(si.root.style.getPropertyValue(P), '#123', 'Theme works while Density waits');
  styles.win.getComputedStyle = () => ({ getPropertyValue: () => '1' }); styles.doc.emit('load');
  eq(state(si), ['h5p-small'], 'Delayed Density stylesheet load handled'); styles.controller.stop();

  const foreign = fixture(); const fi = foreign.items[0];
  await deliver(foreign, 'small'); fi.root.classes.add('unrelated'); set(fi, ['h5p-medium']); await foreign.mutate();
  eq(state(fi), ['h5p-medium'], 'Conflicting native class set retained');
  await refresh(foreign, 'large'); eq(state(fi), ['h5p-medium'], 'Conflicting root never reacquired');
  eq(fi.root.style.getPropertyValue(P), '#123', 'Density conflict does not block Theme');
  foreign.doc.visibilityState = 'hidden'; foreign.doc.emit('visibilitychange');
  foreign.doc.visibilityState = 'visible'; foreign.doc.emit('visibilitychange'); await foreign.advance(1000); await deliver(foreign, 'small');
  eq(state(fi), ['h5p-medium'], 'Density conflict survives pause/resume');
  foreign.controller.stop(); ok(fi.root.classes.has('unrelated'), 'Unrelated third-party class retained');
  const colorConflict = fixture(); await deliver(colorConflict, 'small');
  colorConflict.items[0].root.style.setProperty(P, '#foreign'); await colorConflict.mutate();
  eq(state(colorConflict.items[0]), ['h5p-small'], 'Color conflict does not release Density'); colorConflict.controller.stop();
  const unseen = fixture(); await deliver(unseen, 'small'); set(unseen.items[0], []);
  unseen.controller.stop(); eq(state(unseen.items[0]), [], 'Stop detects conflict even before observer callback');
  const matching = fixture(); set(matching.items[0], ['h5p-small']); await deliver(matching, 'small');
  eq(matching.items[0].root.classWrites || 0, 0, 'Matching native selection is a class no-op');
  eq(matching.items[0].instance.resizeCalls.length, 0, 'Matching native selection is a resize no-op');
  set(matching.items[0], ['h5p-medium']); matching.controller.stop();
  eq(state(matching.items[0]), ['h5p-medium'], 'Matching pre-existing selection never owned');
  const lateBaseline = fixture(); await deliver(lateBaseline, 'original'); set(lateBaseline.items[0], ['h5p-medium']);
  await refresh(lateBaseline, 'small'); lateBaseline.controller.stop();
  eq(state(lateBaseline.items[0]), ['h5p-medium'], 'Capture at first override includes legitimate untouched native changes');
  const addition = fixture(); await deliver(addition, 'small'); addition.items[0].root.classes.add('h5p-medium');
  await addition.mutate(); await refresh(addition, 'original');
  eq(state(addition.items[0]), ['h5p-small', 'h5p-medium'], 'Third-party addition of another native class is a conflict'); addition.controller.stop();

  const multi = fixture([div, frame]); await deliver(multi, 'small');
  for (const player of multi.items) {
    eq(state(player), ['h5p-small'], 'Distinct DIV/internal frame qualified');
    eq(player.instance.resizeCalls, ['resize'], 'Only qualified instance resized once after actual change');
  }
  eq(multi.items[1].frame.style.writes, 0, 'Papi Jo delegates frame dimensions to Core');
  const nested = multi.items[0].root.append(multi.el('div', 'h5p-content h5p-large', { 'data-content-id': '99' }));
  await multi.mutate(); eq([...nested.classes], ['h5p-content', 'h5p-large'], 'Nested child classes are untouched');
  eq(nested.classWrites || 0, 0, 'No nested child Density writes');
  multi.items[1].root.classes.add('other-controller'); multi.items[0].root.remove(); await multi.mutate();
  eq(state(multi.items[0]), ['h5p-large'], 'Detached uniquely owned root restored');
  eq(multi.items[0].instance.resizeCalls.length, 1, 'Detached player not resized');
  eq(multi.items[1].instance.resizeCalls.length, 1, 'Unrelated frame not resized');
  const replacement = multi.player(div); set(replacement, ['h5p-medium']);
  multi.win.H5P.instances = [replacement.instance]; await multi.mutate();
  eq(state(replacement), ['h5p-small'], 'Replacement qualifies with new registered instance');
  multi.controller.stop(); eq(state(replacement), ['h5p-medium'], 'Replacement has independent captured original');
  eq(state(multi.items[1]), ['h5p-large'], 'Internal iframe restores');
  ok(multi.items[1].root.classes.has('other-controller'), 'Unrelated classes survive restore');
  eq(multi.items[1].frame.count(), 0, 'Frame listeners released');

  const reload = fixture([frame]); await deliver(reload, 'small'); const old = reload.items[0];
  const next = reload.player(frame); next.wrapper.remove(); set(next, ['h5p-medium']);
  old.frame.contentDocument = next.doc; old.frame.contentWindow = next.view; next.view.frameElement = old.frame;
  old.frame.emit('load'); await flush(); eq(state(next), ['h5p-small'], 'Same-frame rewritten document requalified');
  eq(state(old), ['h5p-small'], 'Discarded frame document is not mutated');
  eq(old.instance.resizeCalls.length, 1, 'Discarded instance never resized again');
  reload.controller.stop(); eq(state(next), ['h5p-medium'], 'Reload captures new native original');
  eq(reload.observers.filter(o => o.connected).length, 0, 'Reload observers cleaned');

  for (const [label, edit] of [
    ['duplicate anchor', f => f.doc.body.append(f.el('div', 'h5p-content', { 'data-content-id': '10' }))],
    ['unregistered native-looking root', f => f.win.H5P.instances = []],
    ['wrong main library', f => f.items[0].instance.libraryInfo.versionedName = 'H5P.Other 1.0'],
    ['parent marker', f => f.items[0].instance.parent = {}],
    ['subcontent marker', f => f.items[0].instance.subContentId = 'child'],
    ['non-root runtime', f => f.items[0].instance.isRoot = () => false],
    ['duplicate instance', f => f.win.H5P.instances.push({ ...f.items[0].instance })],
    ['nested player', f => { const outer = f.doc.body.append(f.el('div', 'h5p-content')); outer.append(f.items[0].root); }],
    ['editor', f => f.win.H5PEditor = {}],
    ['editor ancestor', f => { const outer = f.doc.body.append(f.el('div', 'h5p-editor')); outer.append(f.items[0].root); }],
    ['uninitialized', f => f.items[0].root.classes.delete('h5p-initialized')],
    ['external runtime', f => f.win.H5P.externalEmbed = true],
    ['missing resize helper', f => delete f.win.H5P.trigger],
    ['missing event API', f => delete f.items[0].instance.trigger]
  ]) {
    const f = fixture(); edit(f); await deliver(f, 'small'); eq(state(f.items[0]), ['h5p-large'], label + ' unchanged');
    eq(f.items[0].instance.resizeCalls.length, 0, label + ' not resized'); f.controller.stop();
  }
  for (const [label, edit] of [
    ['cross-origin', f => { f.items[0].frame.attrs.src = 'https://other.test/'; Object.defineProperty(f.items[0].frame, 'contentDocument', { get() { throw Error('Forbidden frame access'); } }); }],
    ['srcdoc', f => f.items[0].frame.attrs.srcdoc = 'anything'],
    ['sandbox', f => f.items[0].frame.attrs.sandbox = 'allow-same-origin'],
    ['external embed', f => f.items[0].view.H5P.externalEmbed = true],
    ['foreign document', f => f.items[0].doc.URL = 'https://other.test/']
  ]) {
    const f = fixture([frame]); edit(f); await deliver(f, 'small'); eq(state(f.items[0]), ['h5p-large'], label + ' frame excluded'); f.controller.stop();
  }
  const rebound = fixture(); await deliver(rebound, 'small'); const borrowed = rebound.items[0]; borrowed.root.remove();
  const newRoot = rebound.doc.body.append(rebound.el('div', 'h5p-content h5p-initialized h5p-large', { 'data-content-id': '10' }));
  newRoot.append(rebound.el('div', 'h5p-container h5p-standalone h5p-theme')); await rebound.mutate();
  ok(!newRoot.classes.has('h5p-small'), 'Replacement cannot borrow stale instance'); rebound.controller.stop();
  const ambiguity = fixture(); await deliver(ambiguity, 'small');
  ambiguity.doc.body.append(ambiguity.el('div', 'h5p-content', { 'data-content-id': '10' })); await ambiguity.mutate();
  ambiguity.controller.stop(); eq(state(ambiguity.items[0]), ['h5p-small'], 'New ambiguity forbids restoration to uncertain root');

  const late = fixture(); late.win.H5P.instances = []; await deliver(late, 'small');
  eq(state(late.items[0]), ['h5p-large'], 'Late registration waits'); late.win.H5P.instances.push(late.items[0].instance); await late.advance(50);
  eq(state(late.items[0]), ['h5p-small'], 'Bounded retry applies after registration');
  eq(late.items[0].instance.resizeCalls, ['resize'], 'Late initialized main instance resized'); late.controller.stop();
  const failing = fixture([div, frame]); failing.items[0].instance.trigger = () => { throw Error('library failure'); };
  await deliver(failing, 'small'); eq(state(failing.items[0]), ['h5p-large'], 'Resize failure safely rolls back still-owned Density');
  eq(state(failing.items[1]), ['h5p-small'], 'Resize failure cannot interrupt another player');
  const failedWrites = failing.items[0].root.classWrites; await failing.mutate(); await refresh(failing, 'medium');
  eq(failing.items[0].root.classWrites, failedWrites, 'Broken resize quarantines Density without retry loop');
  eq(failing.items[0].root.style.getPropertyValue(P), '#123', 'Resize failure leaves Theme operational'); failing.controller.stop();
  const legacy = fixture(); const li = legacy.items[0]; delete li.instance.trigger;
  li.instance.$ = { trigger: event => li.instance.resizeCalls.push(event) }; await deliver(legacy, 'small');
  eq(li.instance.resizeCalls, ['resize'], 'Core deprecated event dispatcher supported'); legacy.controller.stop();
  const failureConflict = fixture(); const fc = failureConflict.items[0];
  fc.instance.trigger = () => { set(fc, ['h5p-medium']); throw Error('foreign resize handler'); };
  await deliver(failureConflict, 'small'); eq(state(fc), ['h5p-medium'], 'Resize failure never rolls back a foreign class change'); failureConflict.controller.stop();
  const failureOwner = fixture(); const fo = failureOwner.items[0];
  fo.instance.trigger = () => {
    fo.container.remove(); fo.root.append(failureOwner.el('div', 'h5p-container h5p-standalone h5p-theme'));
    throw Error('owner changed inside resize');
  };
  await deliver(failureOwner, 'small');
  eq(state(fo), ['h5p-small'], 'Resize failure cannot restore a changed ownership tuple');
  const ownerWrites = fo.root.classWrites; await failureOwner.mutate(); failureOwner.controller.stop();
  eq(fo.root.classWrites, ownerWrites, 'Changed owner stays quarantined after resize failure');
  const lateApi = fixture(); delete lateApi.win.H5P.trigger; await deliver(lateApi, 'small');
  eq(state(lateApi.items[0]), ['h5p-large'], 'Missing resize API delays Density');
  lateApi.win.H5P.trigger = (instance, event) => instance.trigger(event); await lateApi.advance(50);
  eq(state(lateApi.items[0]), ['h5p-small'], 'Bounded initialization retry handles late resize API'); lateApi.controller.stop();
  const excluded = fixture(undefined, { noStart: true }); excluded.win.location = new URL('https://fixture.test/wp-admin/post.php');
  eq(controllerApi.create(excluded.win, excluded.manifest), null, 'Administration player factory excluded');
  excluded.win.location = new URL(excluded.doc.URL); excluded.win.H5PEditor = {};
  eq(controllerApi.create(excluded.win, excluded.manifest), null, 'Editor player factory excluded');
  const legacyBody = body({ id: 'a'.repeat(32) }, 'small'); legacyBody.appearance.theme.density = 'large';
  eq(controllerApi.validate(legacyBody, 'a'.repeat(32), 0), null, 'Legacy Theme Density cannot become a second instruction');
  const timeout = fixture(); await deliver(timeout, 'small'); const hanging = timeout.controller.refresh(); await flush();
  await timeout.advance(10000); timeout.requests.at(-1).respond(body(timeout.requests.at(-1), 'medium')); await hanging;
  eq(state(timeout.items[0]), ['h5p-large'], 'Timed-out response never reapplies Density'); timeout.controller.stop();
  const failed = fixture(); await deliver(failed, 'small'); const offline = failed.controller.refresh(); await flush(); failed.requests.at(-1).fail(); await offline;
  eq(state(failed.items[0]), ['h5p-large'], 'Failed request leaves restored native state'); failed.controller.stop();

  const life = fixture(); await deliver(life, 'small');
  for (const event of ['focus', 'pageshow']) {
    life.win.emit(event); eq(state(life.items[0]), ['h5p-large'], event + ' clears stale admission');
    await life.advance(1000); await deliver(life, 'medium'); eq(state(life.items[0]), ['h5p-medium'], event + ' reapplies fresh Density');
  }
  const firstPending = life.controller.refresh(); await flush(); const first = life.requests.at(-1);
  const secondPending = life.controller.refresh(); await flush(); const second = life.requests.at(-1);
  second.respond(body(second, 'small')); await secondPending; first.respond(body(first, 'large')); await firstPending;
  eq(state(life.items[0]), ['h5p-small'], 'Out-of-order Density ignored');
  await life.advance(30000); eq(state(life.items[0]), ['h5p-large'], 'Hard expiry restores native despite pending 25-second renewal');
  await deliver(life, 'medium'); life.doc.visibilityState = 'hidden'; life.doc.emit('visibilitychange');
  eq(state(life.items[0]), ['h5p-large'], 'Hidden tab restores owned Density');
  eq(life.observers.filter(o => o.connected).length, 0, 'Hidden tab disconnects observers');
  const count = life.requests.length; await life.advance(60000); eq(life.requests.length, count, 'No hidden Density polling');
  life.doc.visibilityState = 'visible'; life.doc.emit('visibilitychange'); await deliver(life, 'small');
  eq(state(life.items[0]), ['h5p-small'], 'Visibility return requalifies');
  life.win.emit('pagehide'); eq(state(life.items[0]), ['h5p-large'], 'Pagehide restores');
  life.win.emit('pageshow'); await life.advance(1000); await deliver(life, 'small');
  const pending = life.controller.refresh(); await flush(); const r = life.requests.at(-1); life.jump(30000); r.respond(body(r, 'medium')); await pending;
  eq(state(life.items[0]), ['h5p-large'], 'Expired response cannot apply Density');
  await refresh(life, 'small'); const suspension = life.controller.refresh(); await flush();
  const suspended = clone(examples['SNORDIAN suspension']); suspended.request_id = life.requests.at(-1).id;
  life.requests.at(-1).respond(suspended); await suspension;
  eq(state(life.items[0]), ['h5p-large'], 'SNORDIAN suspended descriptor restores still-owned native state');
  eq(life.items[0].root.style.getPropertyValue(P), '', 'SNORDIAN also restores colors');
  const resizeCount = life.items[0].instance.resizeCalls.length; await life.mutate();
  eq(life.items[0].instance.resizeCalls.length, resizeCount, 'Suspension never reapplies stale instructions');
  await refresh(life, 'small'); set(life.items[0], ['h5p-medium']);
  const suspendForeign = life.controller.refresh(); await flush(); suspended.request_id = life.requests.at(-1).id;
  life.requests.at(-1).respond(suspended); await suspendForeign;
  eq(state(life.items[0]), ['h5p-medium'], 'SNORDIAN foreign Density survives suspension');
  life.controller.stop(); eq(life.timers.size, 0, 'All lifecycle timers cleaned');
  eq(life.win.count() + life.doc.count(), 0, 'All controller listeners removed');
  eq(life.observers.filter(o => o.connected).length, 0, 'All lifecycle observers removed');

  // Execute the installed Core dispatcher and internal frame listener, read-only.
  const coreRoot = process.argv[2];
  if (!coreRoot) throw Error('Provide read-only installed H5P plugin path for Core resize contract verification');
  const core = fs.readFileSync(coreRoot + '/h5p-php-library/js/h5p.js', 'utf8');
  const triggerSource = core.match(/H5P\.trigger = function \(instance, eventType, data, extras\) \{[\s\S]*?\n\};/)[0];
  const coreFixture = fixture([frame]); const ci = coreFixture.items[0], handlers = [], jobs = new Map(); let serial = 0;
  const coreApi = ci.view.H5P; coreApi.on = (instance, event, fn) => handlers.push({ instance, event, fn });
  ci.instance.trigger = event => { ci.instance.resizeCalls.push(event); handlers.filter(h => h.instance === ci.instance && h.event === event).forEach(h => h.fn()); };
  ci.view.parent = { H5P: { isFullscreen: false } }; ci.wrapper.clientHeight = 500;
  ci.frame.getBoundingClientRect = () => ({}); ci.doc.body.scrollHeight = 240;
  ci.wrapper.style.height = 'saved';
  const context = { H5P: coreApi, window: ci.view, instance: ci.instance, resizeDelay: undefined,
    setTimeout: fn => { const id = ++serial; jobs.set(id, fn); return id; }, clearTimeout: id => jobs.delete(id) };
  vm.runInNewContext(triggerSource, context);
  const listener = core.slice(core.indexOf('        var iframe = window.frameElement;'), core.indexOf('      else if (H5P.communicator)')).replace(/\s*\}\s*$/, '');
  vm.runInNewContext(listener, context);
  await deliver(coreFixture, 'small'); eq(jobs.size, 1, 'Actual Core listener schedules internal iframe resize');
  for (const [id, fn] of jobs) { jobs.delete(id); fn(); }
  eq(ci.frame.style.height, '240px', 'Actual Core internal listener sets iframe body height');
  eq(ci.wrapper.style.height, 'saved', 'Actual Core listener restores wrapper height');
  ci.view.parent.H5P.isFullscreen = true; ci.doc.body.scrollHeight = 900;
  await refresh(coreFixture, 'medium'); for (const [id, fn] of jobs) { jobs.delete(id); fn(); }
  eq(ci.frame.style.height, '240px', 'Actual Core fullscreen guard retained'); coreFixture.controller.stop();

  console.log(`Phase 2B-2D JavaScript: ${checks} assertions passed (isolated fixtures and read-only Core resize source; no browser).`);
}
run().catch(error => { console.error(error); process.exitCode = 1; });
