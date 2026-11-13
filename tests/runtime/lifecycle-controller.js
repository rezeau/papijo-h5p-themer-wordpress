/* Cache reuse and lifecycle qualification. Deterministic doubles, no live browser/CMS. */
'use strict';
const assert = require('node:assert/strict');
const { fixture, flush, envelope, examples, clone, P } = require('./theme-controller.js');
let checks = 0;
function eq(a, b, message) { assert.deepEqual(a, b, message); checks++; }
function ok(a, message) { assert.ok(a, message); checks++; }
const div = { content_id: '10', embed: 'div', library: 'H5P.Accordion 1.0' };
const frame = { content_id: '11', embed: 'iframe', library: 'H5P.Column 1.2' };
const density = root => ['h5p-small', 'h5p-medium', 'h5p-large'].filter(n => root.classes.has(n));
function appearance(r, color = '#123', selection = 'small') {
  const b = envelope(r.id, P + ':' + color + ';');
  b.appearance.density = { selection, preserve_native: selection === 'original' }; return b;
}
function suspended(r) { const b = clone(examples['SNORDIAN suspension']); b.request_id = r.id; return b; }
async function deliver(f, body) { await flush(); const r = f.requests.at(-1); r.respond(body ? body(r) : appearance(r)); await flush(); }
function clean(f) {
  f.controller.stop(); eq(f.timers.size, 0, 'Teardown clears every timer');
  eq(f.observers.filter(o => o.connected).length, 0, 'Teardown disconnects every observer');
  eq(f.win.count() + f.doc.count() + f.items.reduce((n, i) => n + (i.frame?.count() || 0) + (i.doc === f.doc ? 0 : i.doc.count()), 0), 0, 'Teardown removes lifecycle/document/frame listeners');
}
function mutations(item) {
  return [item.root.style.writes, item.root.classWrites || 0, item.instance.resizeCalls.length];
}
async function scheduledRenewalChecks() {
  const stable = fixture([div, frame]); await deliver(stable);
  const original = stable.items.map(mutations);
  await stable.advance(24999); eq(stable.requests.length, 1, 'No ordinary renewal before request-start +25s');
  await stable.advance(1); eq(stable.requests.length, 2, 'Exactly one replacement starts at +25s');
  stable.items.forEach((i, n) => {
    eq(i.root.style.getPropertyValue(P), '#123', 'Pending renewal retains valid Theme');
    eq(density(i.root), ['h5p-small'], 'Pending renewal retains valid Density');
    eq(mutations(i), original[n], 'Starting renewal performs no appearance mutation or resize');
  });
  await deliver(stable); await stable.mutate();
  stable.items.forEach((i, n) => eq(mutations(i), original[n], 'Identical renewal and observer scan perform zero writes/resizes'));
  await stable.advance(24974); eq(stable.requests.length, 2, 'Replacement renewal uses its own request start (observer scan consumed 25ms)');
  await stable.advance(1); eq(stable.requests.length, 3, 'Next ordinary renewal at 50s, not the old 30s deadline');
  await deliver(stable); clean(stable);

  for (const [label, color, selection, delta] of [
    ['Theme only', '#456', 'small', [1, 0, 0]],
    ['Density only', '#123', 'medium', [0, 2, 1]],
    ['Both dimensions', '#456', 'medium', [1, 2, 1]]
  ]) {
    const f = fixture([div, frame]); await deliver(f); const before = f.items.map(mutations);
    await f.advance(25000); await deliver(f, r => appearance(r, color, selection));
    f.items.forEach((item, n) => {
      eq(mutations(item).map((x, k) => x - before[n][k]), delta, label + ': only actual differences mutate/resize');
      eq(item.root.style.getPropertyValue(P), color, label + ': correct Theme');
      eq(density(item.root), ['h5p-' + selection], label + ': correct Density');
    }); clean(f);
  }

  // The replacement has a distinct grant lifetime: expiry of the original may
  // release styling, but must neither authorize reuse nor cancel that replacement.
  for (const delay of [4999, 5000, 5001]) {
    const f = fixture(); await deliver(f); await f.advance(25000); const r = f.requests.at(-1);
    await f.advance(delay);
    eq(f.requests.length, 2, 'No second request when old deadline meets pending renewal');
    eq(f.items[0].root.style.getPropertyValue(P), delay < 5000 ? '#123' : '', 'Old Theme released exactly at 30s');
    eq(density(f.items[0].root), delay < 5000 ? ['h5p-small'] : ['h5p-large'], 'Old Density released exactly at 30s');
    ok(!r.init.signal.aborted, 'Old descriptor expiry does not abort current replacement');
    r.respond(appearance(r, '#456', 'medium')); await flush();
    eq(f.items[0].root.style.getPropertyValue(P), '#456', 'Valid new grant accepted before/at/after OLD expiry');
    eq(density(f.items[0].root), ['h5p-medium'], 'New independent Density grant accepted');
    await f.advance(55000 - (25000 + delay));
    eq(f.items[0].root.style.getPropertyValue(P), '', 'Replacement grant itself expires at request-start +30s');
    clean(f);
  }

  const timeout = fixture(); await deliver(timeout); await timeout.advance(25000);
  const hanging = timeout.requests.at(-1);
  await timeout.advance(4999); eq(density(timeout.items[0].root), ['h5p-small'], 'Hanging replacement cannot remove old grant prematurely');
  await timeout.advance(1); eq(density(timeout.items[0].root), ['h5p-large'], 'Hanging replacement cannot extend old grant');
  await timeout.advance(5000); ok(hanging.init.signal.aborted, 'Replacement times out at its own +10s');
  hanging.respond(appearance(hanging)); await flush();
  eq(timeout.items[0].root.style.getPropertyValue(P), '', 'Timed-out renewal response cannot reapply Theme');
  await timeout.advance(60000); eq(timeout.requests.length, 2, 'Failed scheduled renewal does not poll'); clean(timeout);

  for (const [label, response] of [
    ['network failure', r => r.fail()],
    ['invalid response', r => r.respond('{bad')],
    ['SNORDIAN suspension', r => r.respond(suspended(r))]
  ]) {
    const f = fixture(); await deliver(f); await f.advance(25000);
    response(f.requests.at(-1)); await flush();
    eq(f.items[0].root.style.getPropertyValue(P), '', label + ': revoke Theme immediately');
    eq(density(f.items[0].root), ['h5p-large'], label + ': restore native Density');
    await f.advance(60000); eq(f.requests.length, 2, label + ': no failure/suspension polling'); clean(f);
  }

  // A delayed body/timer must enforce the current request's age, too.
  for (const age of [9999, 10000, 29999, 30000, 30001]) {
    const f = fixture(); await deliver(f); await f.advance(25000); const r = f.requests.at(-1);
    f.jump(age); r.respond(appearance(r, '#456')); await flush();
    eq(f.items[0].root.style.getPropertyValue(P), age < 10000 ? '#456' : '', 'Replacement own timeout/freshness boundary: ' + age);
    clean(f);
  }
  const parsing = fixture(); await deliver(parsing); await parsing.advance(25000);
  const r = parsing.requests.at(-1); let finish;
  r.respond(null, { text: () => new Promise(resolve => { finish = resolve; }) }); await flush();
  await parsing.advance(10000); finish(JSON.stringify(appearance(r))); await flush();
  eq(parsing.items[0].root.style.getPropertyValue(P), '', 'Timed-out renewal body parsing cannot publish a descriptor'); clean(parsing);

  for (const event of ['focus', 'pageshow', 'visibilitychange']) {
    const f = fixture(); await deliver(f); await f.advance(25000); const old = f.requests.at(-1);
    if (event === 'visibilitychange') { f.doc.visibilityState = 'hidden'; f.doc.emit(event); f.doc.visibilityState = 'visible'; f.doc.emit(event); }
    else { f.win.emit(event); }
    eq(f.items[0].root.style.getPropertyValue(P), '', event + ': conservative immediate Theme restoration retained');
    eq(density(f.items[0].root), ['h5p-large'], event + ': conservative Density restoration retained');
    ok(old.init.signal.aborted, event + ': cancels scheduled renewal generation');
    await f.advance(1000); await deliver(f, req => appearance(req, '#789', 'medium'));
    old.respond(appearance(old, '#456', 'large')); await flush();
    eq(f.items[0].root.style.getPropertyValue(P), '#789', event + ': stale generation cannot replace new identity/policy result');
    eq(density(f.items[0].root), ['h5p-medium'], event + ': stale Density rejected'); clean(f);
  }
  const explicit = fixture(); await deliver(explicit); await explicit.advance(25000); const superseded = explicit.requests.at(-1);
  const pending = explicit.controller.refresh(); await flush();
  ok(superseded.init.signal.aborted, 'Explicit refresh supersedes scheduled renewal');
  await deliver(explicit, suspended); await pending;
  superseded.respond(appearance(superseded)); await flush();
  eq(explicit.items[0].root.style.getPropertyValue(P), '', 'Superseded renewal cannot reverse newer suspension'); clean(explicit);

  for (const response of ['valid', 'suspended', 'expiry']) {
    const f = fixture(); await deliver(f); await f.advance(25000);
    const item = f.items[0]; item.root.style.setProperty(P, '#abc', 'important');
    item.root.classList.remove('h5p-small'); item.root.classList.add('h5p-medium');
    const before = mutations(item);
    if (response === 'expiry') { await f.advance(5000); }
    else { await deliver(f, response === 'suspended' ? suspended : req => appearance(req, '#456', 'large')); }
    eq(item.root.style.getPropertyValue(P), '#abc', 'Renewal ' + response + ': foreign Theme survives');
    eq(density(item.root), ['h5p-medium'], 'Renewal ' + response + ': foreign Density survives');
    eq(mutations(item), before, 'Renewal ' + response + ': foreign state neither restored nor resized');
    clean(f);
  }
}
async function run() {
  await scheduledRenewalChecks();
  // Reuse identical public HTML/manifest while the private response changes viewers.
  const f = fixture([div, frame]); const manifest = JSON.stringify(f.manifest);
  f.items.forEach(i => i.root.style.setProperty(P, '#abc', 'important'));
  await deliver(f);
  for (const [label, response, color, selection] of [
    ['User B', r => appearance(r, '#456', 'medium'), '#456', 'h5p-medium'],
    ['logout/unauthorized', r => { const b = clone(examples['Site default + Original']); b.request_id = r.id; return b; }, '#834DD5', 'h5p-large'],
    ['explicit Core', r => envelope(r.id, '', true), '#abc', 'h5p-large'],
    ['permission restored', r => appearance(r), '#123', 'h5p-small']
  ]) {
    const pending = f.controller.refresh();
    f.items.forEach(i => { eq(i.root.style.getPropertyValue(P), '#abc', label + ' releases prior Theme before fetch'); eq(density(i.root), ['h5p-large'], label + ' releases prior Density'); });
    await deliver(f, response); await pending;
    f.items.forEach(i => { eq(i.root.style.getPropertyValue(P), color, label + ' reflects current response'); eq(density(i.root), [selection], label + ' has independent Density'); });
    eq(JSON.stringify(f.manifest), manifest, 'Viewer appearance never changes public attachment metadata');
  }
  // Failure, Core and suspension all release ownership, but only validated Core
  // may still instruct a non-Original Density. Transport errors invent no choice.
  let pending = f.controller.refresh(); await deliver(f, r => { const b = envelope(r.id, '', true); b.appearance.density = { selection: 'medium', preserve_native: false }; return b; }); await pending;
  eq(density(f.items[0].root), ['h5p-medium'], 'Valid explicit Core can act on Density');
  pending = f.controller.refresh(); await flush(); f.requests.at(-1).fail(); await pending;
  eq(density(f.items[0].root), ['h5p-large'], 'Network failure restores actual original instead of inventing Core + Density');
  eq(f.items[0].root.style.getPropertyPriority(P), 'important', 'Failure restores original Theme priority');
  const failures = f.requests.length; await f.advance(120000); eq(f.requests.length, failures, 'Temporary unavailability does not poll');
  pending = f.controller.refresh(); await deliver(f); await pending;
  const older = f.controller.refresh(); await flush(); const admitted = f.requests.at(-1);
  const newer = f.controller.refresh(); await deliver(f, suspended); await newer;
  admitted.respond(appearance(admitted)); await older;
  f.items.forEach(i => { eq(i.root.style.getPropertyValue(P), '#abc', 'Stale admission cannot override newer suspension'); eq(density(i.root), ['h5p-large'], 'Stale Density cannot override suspension'); });
  pending = f.controller.refresh(); await deliver(f); await pending;
  eq(density(f.items[1].root), ['h5p-small'], 'Suspended-to-admitted needs a fresh valid response'); clean(f);

  // Browser event clusters revoke immediately and issue at most one trailing
  // request per second; explicit session refresh bypasses this event cooldown.
  const burst = fixture(); await deliver(burst);
  for (let i = 0; i < 100; i++) { burst.win.emit('focus'); burst.win.emit('pageshow'); burst.doc.emit('visibilitychange'); }
  await flush(); eq(burst.items[0].root.style.getPropertyValue(P), '', 'Event cluster revokes immediately');
  eq(burst.requests.length, 1, 'Event burst does not issue immediate duplicate requests');
  await burst.advance(999); eq(burst.requests.length, 1, 'Coalesced request waits for cooldown');
  await burst.advance(1); eq(burst.requests.length, 2, 'One trailing request covers all clustered events');
  await deliver(burst); eq(density(burst.items[0].root), ['h5p-small'], 'Trailing response reapplies fresh appearance');
  const explicit = burst.controller.refresh(); await deliver(burst, r => appearance(r, '#789', 'medium')); await explicit;
  eq(burst.requests.length, 3, 'Known account transition refresh remains immediate');
  burst.win.emit('focus'); burst.controller.stop(); await burst.advance(60000);
  eq(burst.requests.length, 3, 'Stop cancels pending trailing event request'); clean(burst);

  const pendingBurst = fixture(); await flush(); const initial = pendingBurst.requests[0];
  pendingBurst.win.emit('focus'); pendingBurst.win.emit('pageshow');
  ok(initial.init.signal.aborted, 'Lifecycle change aborts old in-flight generation');
  initial.respond(appearance(initial)); await flush();
  eq(pendingBurst.items[0].root.style.writes, 0, 'Aborted response cannot style during coalescing');
  await pendingBurst.advance(1000); await deliver(pendingBurst); clean(pendingBurst);

  const hidden = fixture(undefined, { noStart: true }); hidden.doc.visibilityState = 'hidden'; hidden.controller.start();
  hidden.win.emit('pageshow'); hidden.win.emit('focus'); await hidden.advance(60000);
  eq(hidden.requests.length, 0, 'Initially hidden page does not fetch'); eq(hidden.observers.length, 0, 'Initially hidden page does not observe');
  hidden.doc.visibilityState = 'visible'; hidden.doc.emit('visibilitychange'); await deliver(hidden);
  hidden.win.emit('focus'); hidden.doc.visibilityState = 'hidden'; hidden.doc.emit('visibilitychange');
  await hidden.advance(60000); eq(hidden.requests.length, 1, 'Hide cancels pending lifecycle refresh');
  eq(hidden.timers.size, 0, 'Hidden page leaves no timers'); clean(hidden);

  const early = fixture(undefined, { noStart: true }); early.doc.readyState = 'loading'; early.controller.start();
  early.win.emit('focus'); early.win.emit('pageshow'); await flush(); eq(early.requests.length, 0, 'Early lifecycle signals wait for DOM readiness');
  early.doc.readyState = 'interactive'; early.doc.emit('DOMContentLoaded'); await deliver(early); clean(early);

  // A mutation scan may beat a throttled expiry timer. It must renew rather than
  // cancel the only renewal mechanism and leave a visible page inert forever.
  const boundary = fixture(); await deliver(boundary); boundary.jump(30000);
  boundary.doc.emit('load'); await flush(); eq(boundary.requests.length, 2, 'Scan at expiry renews immediately');
  eq(density(boundary.items[0].root), ['h5p-large'], 'Expired admission is released before renewal');
  await boundary.advance(1); eq(boundary.requests.length, 2, 'Competing expiry callback causes no second renewal');
  await deliver(boundary); clean(boundary);

  const slow = fixture(); await flush(); await slow.advance(9000); await deliver(slow);
  await slow.advance(20999); eq(density(slow.items[0].root), ['h5p-small'], 'Slow response uses remaining request-start lifetime');
  await slow.advance(1); eq(slow.requests.length, 2, 'Early renewal already pending at the original hard deadline');
  slow.requests.at(-1).fail(); await flush(); await slow.advance(60000); eq(slow.requests.length, 2, 'Expiry failure stops renewals'); clean(slow);

  const parsing = fixture(); await flush(); const parseRequest = parsing.requests[0]; let finishText;
  parseRequest.respond(null, { text: () => new Promise(resolve => { finishText = resolve; }) }); await flush();
  await parsing.advance(10000); finishText(JSON.stringify(appearance(parseRequest))); await flush();
  eq(parsing.items[0].root.style.writes, 0, 'Body parsing completing after timeout cannot apply'); clean(parsing);

  const absent = fixture(); await deliver(absent); absent.items[0].root.remove(); await absent.mutate();
  await absent.advance(30000); eq(absent.requests.length, 1, 'No automatic expiry renewal after last player removed');
  eq(density(absent.items[0].root), ['h5p-large'], 'Detached player restores native Density'); clean(absent);
  const unobservedRemoval = fixture(); await deliver(unobservedRemoval); unobservedRemoval.items[0].root.remove();
  await unobservedRemoval.advance(30000); eq(unobservedRemoval.requests.length, 1, 'Expiry requalifies removal even before observer delivery'); clean(unobservedRemoval);

  // Same Document, replaced documentElement: rebind the observer without retaining
  // old tree listeners. Both parent and canonical internal-frame realms qualify.
  for (const p of [div, frame]) {
    const rewrite = fixture([p]); await deliver(rewrite); const item = rewrite.items[0], oldElement = item.doc.documentElement;
    const writes = item.root.style.writes;
    const fresh = rewrite.player(p); fresh.root.remove(); if (fresh.wrapper) fresh.wrapper.remove();
    const newElement = rewrite.el('html', p.embed === 'iframe' ? 'h5p-iframe' : '');
    const newBody = newElement.append(rewrite.el('body')); newBody.append(fresh.root);
    function adopt(n) { n.ownerDocument = item.doc; n.children.forEach(adopt); } adopt(newElement);
    item.doc.documentElement = newElement; item.doc.body = newBody;
    if (p.embed === 'iframe') {
      item.view.H5P.instances = [fresh.instance]; item.frame.emit('load');
    } else { rewrite.win.H5P.instances = [fresh.instance]; item.doc.emit('load'); }
    await flush();
    eq(fresh.root.style.getPropertyValue(P), '#123', p.embed + ' rewritten tree qualified');
    eq(density(fresh.root), ['h5p-small'], p.embed + ' rewritten tree uses new native baseline');
    eq(item.root.style.writes, writes, 'Old uncertain tree is quarantined without restoration writes');
    ok(!rewrite.observers.some(o => o.connected && o.node === oldElement), 'Observer releases replaced document element');
    ok(rewrite.observers.some(o => o.connected && o.node === newElement), 'Observer watches current document element');
    eq(item.doc.count(), 1 + (p.embed === 'div' ? 1 : 0), 'Rewrite does not accumulate document load listeners');
    clean(rewrite);
  }
  const churn = fixture([frame]); await deliver(churn); const anchor = churn.items[0].frame;
  for (let i = 0; i < 20; i++) {
    const next = churn.player(frame); next.wrapper.remove(); anchor.contentDocument = next.doc; anchor.contentWindow = next.view; next.view.frameElement = anchor;
    anchor.emit('load'); await flush(); eq(churn.observers.filter(o => o.connected).length, 2, 'Repeated iframe rewrites retain exactly two active observers');
    eq(anchor.count(), 1, 'Repeated iframe rewrites retain one load listener');
  }
  clean(churn);
  console.log(`Phase 2B-2E lifecycle JavaScript: ${checks} assertions passed (simulated clocks, DOM and sessions; no real browser).`);
}
run().catch(error => { console.error(error); process.exitCode = 1; });
