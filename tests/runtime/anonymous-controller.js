/* Focused general-mode orchestration in the accepted isolated H5P runtime model. */
'use strict';
const assert = require('node:assert/strict');
const api = require('../../assets/js/anonymous-controller.js');
const { fixture, flush } = require('./anonymous-fixtures.js');
const { dom, node, response } = require('./anonymous-fixtures.js');
const { automaticMutations, drain } = require('./anonymous-fixtures.js');
let checks = 0;
function eq(a, b, message) { assert.deepEqual(JSON.parse(JSON.stringify(a)), JSON.parse(JSON.stringify(b)), message); checks++; }
const players = [
  { content_id: '1', embed: 'iframe', library: 'H5P.TrueFalse 1.8' },
  { content_id: '9', embed: 'div', library: 'H5P.DialogcardsPapiJo 1.17' },
  { content_id: '12', embed: 'div', library: 'H5P.Accordion 1.0' }
];
const prefix = 'pjh5pwp:anonymous:v1:' + encodeURIComponent('https://fixture.test/') + ':';
const values = r => Object.fromEntries(new URLSearchParams(r.init.body));
function setup(list = players, map = new Map(), edit) {
  const f = fixture(list, { noStart: true }); dom(f);
  f.doc.URL = 'https://fixture.test/lesson/mixed/?lang=fr'; f.win.location = new URL(f.doc.URL);
  f.writes = []; f.win.localStorage = {
    getItem: k => map.get(k) ?? null,
    setItem: (k, v) => { f.writes.push([k, v]); map.set(k, v); },
    removeItem: k => { f.writes.push([k, null]); map.delete(k); }
  };
  f.metadata = { version: 1, page: '/lesson/mixed/?lang=fr', endpoint: f.manifest.endpoint, home: 'https://fixture.test/' };
  f.grants = list.map(player => ({ player, nonce: '123456abcd' }));
  automaticMutations(f);
  if (edit) edit(f);
  f.controller = api.createGeneral(f.win, f.manifest, f.metadata, f.grants, 'https://fixture.test/main/theme-controller.js');
  f.controller?.start(); return f;
}
async function answer(requests, key) {
  for (const r of requests) r.respond(response(r, key));
  await drain();
}
const counts = f => f.items.map(i => [i.root.style.writes, i.root.classWrites, i.instance.resizeCalls.length]);
function stop(f) {
  f.controller?.stop();
  eq(f.timers.size, 0, 'Stop clears all per-activity timers');
  eq(f.win.listeners.get('storage')?.size || 0, 0, 'Stop clears the single storage listener');
}
async function run() {
  const map = new Map([[prefix + 'theme', '"builtin:dark"'], [prefix + 'density', '"small"']]);
  for (const list of [[players[0]], [players[1]], players]) {
    const f = setup(list, map); await drain();
    eq(f.requests.length, list.length, 'One existing transport lifecycle per distinct activity');
    eq(f.doc.querySelectorAll('[data-pjh5pwp-anonymous-selector]').length, 1, 'One selector for iframe, DIV and mixed pages');
    eq(node(f,'details').hasAttribute('open'),false,'Disclosure starts closed');
    eq(f.win.listeners.get('storage').size, 1, 'One page-level preference scope');
    eq(f.requests.map(r => [JSON.parse(values(r).player).content_id, values(r).theme, values(r).density]), list.map(p => [p.content_id, 'builtin:dark', 'small']), 'All activities request the same site-wide preferences');
    eq(f.requests.map(r => JSON.parse(values(r).player)), list, 'Each request carries its nonce-bound runtime descriptor');
    await answer(f.requests);
    eq(f.controller.snapshot().state, 'applied', 'Every eligible activity applied');
    eq(f.items.every(i => i.root.classList.contains('h5p-small')), true, 'Density applies to every activity');
    eq(f.writes.length, 0, 'Navigation reads existing JSON token keys without writes');
    eq(f.mutations.overflow, false, 'Automatic selector attribute/child mutations settle');
    node(f,'details').setAttribute('open','');
    const before = counts(f), announcements = node(f, '[role="status"]').textWrites;
    const n = f.requests.length; await f.advance(25000);
    eq(counts(f), before, 'Renewal retains every activity appearance');
    await answer(f.requests.slice(n));
    eq(counts(f), before, 'Identical renewal causes no style writes or resize');
    eq(node(f, '[role="status"]').textWrites, announcements, 'Renewal is silent');
    const prior = f.requests.length;
    const select = node(f, 'select[id="pjh5pwp-anonymous-density"]'); select.value = 'large'; select.emit('change');
    await answer(f.requests.slice(prior));
    eq(f.items.every(i => i.root.classList.contains('h5p-large')), true, 'One selector changes every activity');
    eq([...map.keys()].sort(), [prefix + 'density', prefix + 'theme'], 'Storage format and site-wide key scope unchanged');
    const resetStart = f.requests.length; node(f, 'button').emit('click');
    eq(f.requests.slice(resetStart).map(r => [values(r).theme, values(r).density]), list.map(() => ['site_default', 'original']), 'Reset reaches every activity without changing semantics');
    await answer(f.requests.slice(resetStart));
    eq(map.size, 0, 'Reset removes only the existing preference keys');
    stop(f); map.set(prefix + 'density', '"small"'); map.set(prefix + 'theme', '"builtin:dark"');
  }
  const storage = setup(players.slice(0, 2)); await answer(storage.requests);
  const storageStart = storage.requests.length;
  storage.win.emit('storage', { key: prefix + 'density', newValue: '"small"', storageArea: storage.win.localStorage });
  eq(storage.requests.slice(storageStart).map(r => values(r).density), ['small', 'small'], 'Cross-tab preference event refreshes every activity');
  await answer(storage.requests.slice(storageStart));
  eq(storage.writes.length, 0, 'Cross-tab preference event does not rewrite localStorage'); stop(storage);

  const stale = setup(players.slice(0, 2));
  const old = stale.requests.slice(); stale.controller.refresh();
  eq(old.every(r => r.init.signal.aborted), true, 'Shared refresh supersedes every previous generation');
  await answer(old);
  eq(stale.items.every(i => i.root.style.writes === 0), true, 'Stale generations cannot style either activity');
  await answer(stale.requests.slice(old.length));
  eq(stale.controller.snapshot().state, 'applied', 'Fresh generations apply'); stop(stale);

  const replaced = setup(players.slice(0, 2), map); await answer(replaced.requests);
  const oldItem = replaced.items[0], oldWrites = oldItem.root.style.writes;
  oldItem.wrapper.remove(); const nextItem = replaced.player(players[0]);
  const beforeReplacement = replaced.requests.length; await replaced.mutate();
  eq(replaced.requests.length, beforeReplacement + 1, 'Iframe replacement needs fresh appearance admission');
  eq(nextItem.root.style.writes, 0, 'Replacement does not borrow the old document grant');
  await answer(replaced.requests.slice(beforeReplacement));
  eq(nextItem.root.classList.contains('h5p-small'), true, 'Fresh admitted iframe receives the shared appearance');
  eq(oldItem.root.style.writes, oldWrites, 'Detached old iframe document is never rewritten');
  eq(replaced.doc.querySelectorAll('[data-pjh5pwp-anonymous-selector]').length, 1, 'Iframe replacement retains one selector');
  eq(replaced.mutations.overflow, false, 'Replacement selector reanchoring settles'); stop(replaced);

  const late = setup(players.slice(0, 2), new Map(), f => { f.items[1].root.stylesReady = false; });
  await answer(late.requests); await late.advance(15000);
  eq(late.controller.snapshot().state, 'readiness_timeout', 'Genuinely late activity retains the existing 15-second deadline');
  eq(late.items[0].root.style.getPropertyValue(api.properties[0]), '', 'Page failure releases other proved activity owners'); stop(late);

  const auth = setup(players.slice(0, 2));
  const authBody = response(auth.requests[0]);
  Object.assign(authBody, { audience: 'authenticated', reload_required: true, reason: 'authenticated_session', appearance: null, selection: null, catalog: null, fallback_reason: null });
  auth.requests[0].respond(authBody, { ok: false, status: 409 }); await drain();
  eq(auth.controller.snapshot().reload_required, true, 'Authentication transition fails the whole page closed');
  eq(auth.requests[1].init.signal.aborted, true, 'Authentication transition cancels other activity requests');
  await answer(auth.requests.slice(1));
  eq(auth.items.every(i => i.root.style.writes === 0), true, 'Late anonymous responses cannot style authenticated page'); stop(auth);


  const signedIn=setup(players.slice(0,2),map); await answer(signedIn.requests);
  const transitionStart=signedIn.requests.length;signedIn.controller.refresh();
  const transition=signedIn.requests[transitionStart], authResult=response(transition);
  Object.assign(authResult,{audience:'authenticated',reload_required:true,reason:'authenticated_session',appearance:null,selection:null,catalog:null,fallback_reason:null});
  transition.respond(authResult,{ok:false,status:409});await drain();
  eq(signedIn.items.every(i=>i.root.style.getPropertyValue(api.properties[0])===''),true,'Sign-in releases every owned appearance');
  eq(node(signedIn,'[role="status"]').textContent.includes('Reload'),true,'Visible user-initiated reload request');stop(signedIn);

  const unavailable=setup([players[1]],new Map([[prefix+'theme','"custom:deleted"'],[prefix+'density','"small"']]));
  await answer(unavailable.requests);
  eq(node(unavailable,'select[id="pjh5pwp-anonymous-theme"]').value,'site_default','Unavailable custom falls back');
  eq(unavailable.items[0].root.classList.contains('h5p-small'),true,'Theme fallback preserves Density');stop(unavailable);
  const published=setup([players[1]],new Map([[prefix+'theme','"custom:published"']]));await answer(published.requests);
  eq(node(published,'select[id="pjh5pwp-anonymous-theme"]').value,'custom:published','Published custom restored while closed');stop(published);
  const deniedStorage=setup([players[1]],new Map(),f=>Object.defineProperty(f.win,'localStorage',{get(){throw Error('blocked');}}));
  await answer(deniedStorage.requests);
  const selectDensity=node(deniedStorage,'select[id="pjh5pwp-anonymous-density"]');selectDensity.value='small';const count=deniedStorage.requests.length;selectDensity.emit('change');await answer(deniedStorage.requests.slice(count));
  eq(deniedStorage.items[0].root.classList.contains('h5p-small'),true,'Blocked storage works in memory');stop(deniedStorage);
  const legacyMap=new Map([['pjh5pwa:v1:'+encodeURIComponent('https://fixture.test/')+':theme','"builtin:dark"']]);
  const fresh=setup([players[1]],legacyMap);
  eq(values(fresh.requests[0]).theme,'site_default','Standalone keys never migrated');stop(fresh);
  const blocked=setup([players[1]]);await answer(blocked.requests);const next=blocked.requests.length;blocked.controller.refresh();blocked.requests[next].respond({}, {ok:false,status:403});await drain();
  eq(blocked.items[0].root.style.getPropertyValue(api.properties[0]),'','Server suspension releases owned styling');stop(blocked);
  for (const edit of [
    f => { f.grants[0].nonce = 'bad'; },
    f => { f.grants.reverse(); },
    f => { f.grants.pop(); },
    f => { f.metadata.home = 'https://evil.test/'; },
    f => { f.metadata.page = '//evil.test/'; },
    f => { f.grants[0] = { ...f.grants[0], extra: true }; }
  ]) {
    const f = setup(players.slice(0, 2), new Map(), edit);
    eq(f.controller, null, 'Mismatched/untrusted manifest or grant refused');
    eq(f.requests.length, 0, 'Refused metadata sends no request');
    eq(f.doc.querySelectorAll('[data-pjh5pwp-anonymous-selector]').length, 0, 'Refused metadata creates no selector');
  }
  const empty = fixture([], { noStart: true });
  const script = { getAttribute: name => ({ 'data-pjh5pwp-anonymous': '1', 'data-pjh5pwp-anonymous-config': JSON.stringify({ version: 1 }) })[name] };
  const dormant = api.bootstrap(empty.win, script);
  eq(dormant.snapshot().state, 'no_eligible_activities', 'No rendered H5P manifest leaves general mode dormant');
  eq(empty.requests.length, 0, 'No H5P means no AJAX'); dormant.stop();
  console.log(JSON.stringify({ checks, browser_automation: false, live_wordpress: false }));
}
run().catch(error => { console.error(error); process.exitCode = 1; });
