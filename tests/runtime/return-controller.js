'use strict';
const assert = require('node:assert/strict');
const { setup, div, frame } = require('./readiness-controller.js');
const { flush, envelope, P, examples, clone } = require('./theme-controller.js');
let checks = 0;
function eq(a, b, why) { assert.deepEqual(a, b, why); checks++; }
function counts(f) { return f.items.map(i => [i.root.style.writes, i.root.classWrites || 0, i.instance.resizeCalls.length]); }
async function ready(players = [div, frame]) { const f = setup(players); await f.start(); await f.reply(); await f.advance(32); return f; }
async function run() {
  const f = await ready(); const before = counts(f);
  f.win.emit('pageshow', { persisted: false }); await flush(); eq(f.requests.length, 1, 'Initial pageshow does not cause duplicate startup fetch');
  f.doc.visibilityState = 'hidden'; f.doc.emit('visibilitychange');
  eq(f.boundaries.every(e => e.node.hasAttribute('data-pjh5pwp-pending')), true, 'Conceal before background/BFCache snapshot');
  eq(counts(f), before, 'Hiding retains owned DOM while old grant valid; no native teardown');
  await f.advance(5000); f.doc.visibilityState = 'visible';
  f.doc.emit('visibilitychange'); for (let n = 0; n < 20; n++) { f.win.emit('focus'); f.win.emit('pageshow', { persisted: true }); f.doc.emit('visibilitychange'); }
  await flush(); eq(f.requests.length, 2, 'One request covers return event burst');
  eq(counts(f), before, 'No style/resize churn before authenticated return response');
  await f.reply(); eq(f.pending(), true, 'No reveal before frame qualification'); await f.advance(32);
  eq(counts(f), before, 'Identical fresh response reveals without mutations/resizes'); eq(f.pending(), false, 'Return successfully visible'); f.clean();

  for (const absence of [1000, 40000]) {
    const f = await ready(); f.win.emit('pagehide'); await f.advance(absence);
    eq(f.pending(), true, 'Snapshot boundary remains concealed during absence');
    eq(f.items[0].root.style.getPropertyValue(P), absence < 30000 ? '#123' : '', 'Old authorization expires independently while concealed');
    f.win.emit('pageshow', { persisted: true }); await f.advance(1000); await f.reply('#456', 'medium'); await f.advance(32);
    eq(f.pending(), false, 'BFCache before/after deadline requires fresh successful reveal');
    eq(f.items[0].root.style.getPropertyValue(P), '#456', 'Changed identity/selection response replaces old appearance');
    eq(f.items[0].root.classes.has('h5p-medium'), true, 'Changed Density applied'); f.clean();
  }
  const frozen = await ready(); frozen.win.emit('pagehide'); frozen.jump(40000); frozen.win.emit('pageshow', { persisted: true }); await flush();
  eq(frozen.items[0].root.style.getPropertyValue(P), '', 'Return catches expiry even when BFCache suspended timers'); eq(frozen.pending(), true, 'Expired snapshot cannot be authorized/revealed');
  await frozen.reply(); await frozen.advance(32); eq(frozen.pending(), false, 'Fresh descriptor recovers frozen timer restoration'); frozen.clean();

  for (const fault of ['network', 'timeout', 'invalid', 'suspended', 'permission']) {
    const f = await ready(); f.win.emit('focus'); await f.advance(1000); const r = f.requests.at(-1);
    if (fault === 'network') { r.fail(); }
    else if (fault === 'timeout') { await f.advance(10000); }
    else if (fault === 'invalid') { r.respond('{broken'); }
    else if (fault === 'suspended') { const b = clone(examples['SNORDIAN suspension']); b.request_id = r.id; r.respond(b); }
    else { const b = envelope(r.id, '', true); r.respond(b); }
    await flush(); await f.advance(32);
    eq(f.pending(), false, fault + ': no permanently concealed activity');
    eq(f.items[0].root.style.getPropertyValue(P), '', fault + ': old personal appearance released');
    if (fault !== 'permission') { const n = f.requests.length; await f.advance(60000); eq(f.requests.length, n, fault + ': no failure polling'); }
    if (fault === 'timeout') { r.respond(envelope(r.id, P + ':#789;')); await flush(); eq(f.items[0].root.style.getPropertyValue(P), '', 'Late timed-out response rejected'); }
    if (fault === 'network') { f.win.emit('focus'); await f.advance(1000); await f.reply('#789', 'medium'); await f.advance(32); eq(f.pending(), false, 'Later deliberate lifecycle transition can recover bounded failure'); eq(f.items[0].root.style.getPropertyValue(P), '#789', 'Recovery uses a new authenticated result'); }
    f.clean();
  }
  const expiry = await ready(); await expiry.advance(28968); expiry.win.emit('focus'); await flush();
  eq(expiry.pending(), true, 'Return pending at old grant boundary'); await expiry.advance(1000);
  eq(expiry.items[0].root.style.getPropertyValue(P), '', '30s hard expiry is not extended by revalidation');
  eq(expiry.pending(), true, 'Native fallback concealed while fresh replacement still possible'); await expiry.reply(); await expiry.advance(32); eq(expiry.pending(), false, 'Independent new grant may reveal after old expiry'); expiry.clean();

  const rapid = await ready(); rapid.win.emit('focus'); await rapid.advance(1000); const old = rapid.requests.at(-1);
  rapid.doc.visibilityState = 'hidden'; rapid.doc.emit('visibilitychange'); eq(old.init.signal.aborted, true, 'A genuine new hide cancels in-flight return');
  rapid.doc.visibilityState = 'visible'; rapid.doc.emit('visibilitychange'); await rapid.advance(1000); await rapid.reply('#789', 'medium'); old.respond(envelope(old.id, P + ':#456;')); await flush(); await rapid.advance(32);
  eq(rapid.items[0].root.style.getPropertyValue(P), '#789', 'Canceled generation cannot override newer user response'); rapid.clean();

  const bound = await ready(); bound.items[0].root.stylesReady = false; bound.win.emit('focus'); await bound.advance(1000); await bound.reply();
  for (let n = 0; n < 13; n++) { await bound.advance(1000); bound.win.emit('focus'); }
  await bound.advance(1000); eq(bound.pending(), false, 'Event bursts never extend 15s return readiness bound');
  eq(bound.items[0].root.style.getPropertyValue(P), '', 'Bounded fallback removes owned personal appearance'); bound.clean();

  const conflict = await ready(); conflict.win.emit('focus'); await conflict.advance(1000);
  conflict.items[0].root.style.setProperty(P, '#abc', 'important'); await conflict.reply(); await conflict.advance(32);
  eq(conflict.pending(0), false, 'Foreign conflict reveals explicit fallback'); eq(conflict.items[0].root.style.getPropertyValue(P), '#abc', 'Foreign inline priority survives return failure');
  eq(conflict.pending(1), false, 'Unaffected iframe reveals independently'); conflict.clean();

  const removed = await ready(); removed.win.emit('pagehide'); removed.items[0].root.remove(); removed.win.emit('pageshow', { persisted: true }); await removed.advance(1000); await removed.reply(); await removed.advance(15000);
  eq(removed.pending(), false, 'Lost original instance/boundary cannot remain hidden'); removed.clean();

  const focus = await ready([div]); const button = focus.items[0].container.append(focus.el('button')); focus.doc.activeElement = button;
  focus.win.emit('focus'); focus.doc.activeElement = focus.doc.body; await focus.advance(1000); await focus.reply(); await focus.advance(32);
  eq(focus.doc.activeElement, button, 'Same connected keyboard target restored after successful reveal'); eq(button.focusOptions.preventScroll, true, 'Focus restoration does not scroll');
  focus.win.emit('focus'); focus.doc.activeElement = focus.doc.body; focus.doc.emit('pointerdown'); await focus.advance(1000); await focus.reply(); await focus.advance(32);
  eq(focus.doc.activeElement, focus.doc.body, 'Intervening user interaction prevents focus stealing'); focus.clean();
  for (const [color, density, expected] of [['#456', 'small', [1, 0, 0]], ['#123', 'medium', [0, 2, 1]]]) {
    const f = await ready(); const before = counts(f); const pending = f.boot.refresh(); await flush();
    eq(f.pending(), true, 'Explicit identity/policy refresh concealed before any teardown'); await f.reply(color, density); await pending; await f.advance(32);
    eq(counts(f).map((row, n) => row.map((value, k) => value - before[n][k])), [expected, expected], 'Only changed Theme/Density dimension mutates on return'); f.clean();
  }
  const boot = setup(); await boot.start(); boot.win.emit('focus'); boot.win.emit('pageshow', { persisted: false }); await flush(); eq(boot.requests.length, 1, 'Startup focus/pageshow join initial authenticated request'); await boot.reply(); await boot.advance(32); boot.clean();
  const mixed = setup([div, frame]); mixed.items[1].root.stylesReady = false; await mixed.start(); await mixed.reply(); await mixed.advance(32);
  mixed.doc.visibilityState = 'hidden'; mixed.doc.emit('visibilitychange'); await mixed.advance(14968);
  eq(mixed.pending(1), false, 'Completed sibling cannot suspend slow initial activity head-start 15s bound'); mixed.clean();
  eq(f.win.count() + f.doc.count(), 0, 'Stop removes return and interaction listeners');
  console.log('Return/BFCache readiness: ' + checks + ' assertions passed (deterministic fixtures; no browser).');
}
run().catch(e => { console.error(e); process.exitCode = 1; });
