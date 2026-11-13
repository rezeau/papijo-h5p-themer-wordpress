'use strict';
const assert = require('node:assert/strict'), fs = require('node:fs');
const api = require('../../assets/js/theme-controller.js');
const { fixture, flush, envelope, P, examples, clone } = require('./theme-controller.js');
let checks = 0;
function eq(a, b, text) { assert.deepEqual(a, b, text); checks++; }
const div = { content_id: '10', embed: 'div', library: 'H5P.QuestionSet 1.0' };
const frame = { content_id: '11', embed: 'iframe', library: 'H5P.Column 1.2' };
function setup(players = [div], metadata = true) {
  const f = fixture(players, { noStart: true }); f.doc.readyState = 'loading';
  f.win.requestAnimationFrame = fn => f.win.setTimeout(fn, 16);
  f.win.cancelAnimationFrame = id => f.win.clearTimeout(id);
  f.boot = api.bootstrap(f.win);
  eq(typeof f.boot.refresh, 'function', 'Existing explicit identity/policy refresh API remains available');
  eq(f.doc.documentElement.classList.contains('pjh5pwp-readiness-armed'), true, 'Gate armed before activity markers enter DOM');
  f.boundaries = f.items.map(item => {
    const node = item.wrapper || item.root; node.setAttribute('data-pjh5pwp-pending', item.p.content_id);
    if (item.frame) { item.frame.style.height = '200px'; item.doc.body.scrollHeight = 200; }
    const status = f.doc.body.append(f.el('span', 'pjh5pwp-readiness-status', { 'data-pjh5pwp-status': item.p.content_id, 'data-pjh5pwp-fallback-text': 'Native fallback' }));
    status.textContent = 'Loading activity appearance…'; status.hidden = true;
    return { node, status };
  });
  if (metadata) { f.meta = f.doc.body.append(f.el('span', '', { 'data-pjh5pwp-manifest': JSON.stringify(f.manifest) })); }
  f.start = async () => { f.doc.readyState = 'interactive'; f.doc.emit('DOMContentLoaded'); await flush(); };
  f.reply = async (color = '#123', density = 'small') => {
    const r = f.requests.at(-1), body = envelope(r.id, color ? P + ':' + color + ';' : '', !color);
    body.appearance.density = { selection: density, preserve_native: density === 'original' }; r.respond(body); await flush();
  };
  f.pending = n => f.boundaries[n || 0].node.hasAttribute('data-pjh5pwp-pending');
  f.clean = () => { f.boot.stop(); eq(f.timers.size, 0, 'All watchdog/frame/controller timers cleaned'); eq(f.observers.filter(o => o.connected).length, 0, 'No live observer after stop'); };
  return f;
}
async function run() {
  const css = fs.readFileSync('assets/css/runtime-readiness.css', 'utf8');
  eq(css.includes('visibility: hidden !important'), true, 'Hidden descendants cannot expose focusable controls');
  eq(css.includes('opacity:'), false, 'No opacity-only interaction trap');
  eq(css.includes('min(12rem, 50vh)'), true, 'Modest responsive iframe placeholder');
  eq(css.includes('[hidden] { display: none !important; }'), true, 'Ready status remains hidden while siblings load');
  const loadingRule = css.match(/\.pjh5pwp-readiness-armed \.pjh5pwp-readiness-status:not\(\[data-pjh5pwp-fallback\]\)\s*\{([^}]+)\}/);
  eq(!!loadingRule, true, 'Only pending status is visually hidden; fallback stays visible');
  for (const declaration of ['position: absolute;', 'width: 1px;', 'height: 1px;', 'overflow: hidden;', 'clip-path: inset(50%);']) {
    eq(loadingRule[1].includes(declaration), true, 'Visually hidden accessible loading status: ' + declaration);
  }
  eq(/display:\s*none|visibility:\s*hidden/.test(loadingRule[1]), false, 'Pending status remains in accessibility tree');
  for (const [color, density] of [['#123', 'small'], ['#123', 'original'], [null, 'small'], [null, 'original']]) {
    const f = setup([div, frame]); await f.start(); eq(f.requests.length, 1, 'One endpoint request for all main activities');
    eq(f.pending(), true, 'No provisional palette revealed');
    eq(f.boundaries[0].node.getAttribute('aria-busy'), 'true', 'Pending content marked busy');
    eq(f.boundaries[0].status.hidden, false, 'Loading status available to assistive technology');
    eq(f.boundaries[0].status.textContent, 'Loading activity appearance…', 'Loading announcement preserved');
    await f.reply(color, density); eq(f.pending(), true, 'Application precedes reveal frame');
    await f.advance(32);
    f.items.forEach((item, n) => {
      eq(f.pending(n), false, 'Both DIV/iframe reveal with complete appearance');
      eq(item.root.style.getPropertyValue(P), color || '', 'First revealed color is resolved descriptor/Core');
      eq(item.root.classes.has('h5p-small'), density === 'small', 'First revealed Density is resolved or native Original');
      eq(f.boundaries[n].node.hasAttribute('aria-busy'), false, 'Busy state removed');
      eq(f.boundaries[n].status.hidden, true, 'Loading status removed from accessibility presentation');
    });
    const before = f.items.map(i => [i.root.style.writes, i.root.classWrites || 0, i.instance.resizeCalls.length]);
    await f.advance(24968); await f.reply(color, density); await f.advance(32);
    eq(f.items.map(i => [i.root.style.writes, i.root.classWrites || 0, i.instance.resizeCalls.length]), before, 'Renewal never re-arms gate or writes/resizes identical appearance');
    f.clean();
  }
  const slow = setup([div, frame]); slow.items[1].root.stylesReady = false;
  await slow.start(); await slow.advance(9000); await slow.reply(); await slow.advance(32);
  eq(slow.pending(0), false, 'Ready player independent of slow CSS sibling'); eq(slow.pending(1), true, 'Iframe waits for loaded Theme/scaling CSS');
  slow.items[1].root.stylesReady = true; await slow.mutate(); await slow.advance(32); eq(slow.pending(1), false, 'CSS readiness reveals after existing observer scan'); slow.clean();
  const init = setup(); init.items[0].root.classList.remove('h5p-initialized'); await init.start(); await init.reply(); await init.advance(14000);
  eq(init.pending(), true, 'Slow H5P initialization remains gated'); init.items[0].root.classList.add('h5p-initialized'); await init.mutate(); await init.advance(32); eq(init.pending(), false, 'Late qualified main instance reveals within total budget'); init.clean();
  const sizing = setup([frame]); sizing.items[0].frame.style.height = '1px'; await sizing.start(); await sizing.reply(); await sizing.advance(32); eq(sizing.pending(), true, '1px iframe cannot count as ready');
  sizing.items[0].frame.style.height = '240px'; await sizing.mutate(); await sizing.advance(32); eq(sizing.pending(), true, 'Stale frame height cannot reveal a different body layout');
  sizing.items[0].doc.body.scrollHeight = 240; await sizing.mutate(); await sizing.advance(32); eq(sizing.pending(), false, 'H5P-sized iframe reveals without assigning its height'); sizing.clean();
  const timeout = setup(); timeout.items[0].root.stylesReady = false; await timeout.start(); await timeout.reply(); await timeout.advance(14999);
  eq(timeout.pending(), true, '15s window never shortened by initialization scans'); await timeout.advance(1);
  eq(timeout.pending(), false, '15s bound releases native fallback'); eq(timeout.boundaries[0].status.textContent, 'Native fallback', 'Fallback explicitly distinguished');
  timeout.items[0].root.stylesReady = true; await timeout.mutate(); eq(timeout.items[0].root.style.getPropertyValue(P), '', 'Failed initial activity never late-applies stale appearance'); timeout.clean();
  for (const fault of ['missing', 'invalid', 'suspended', 'network', 'timeout', 'stop']) {
    const f = setup([div], fault !== 'missing');
    if (fault === 'invalid') { f.meta.setAttribute('data-pjh5pwp-manifest', '{bad'); }
    await f.start();
    if (fault === 'suspended') { const r = f.requests.at(-1), b = clone(examples['SNORDIAN suspension']); b.request_id = r.id; r.respond(b); await flush(); }
    if (fault === 'network') { f.requests.at(-1).fail(); await flush(); }
    if (fault === 'timeout') { await f.advance(10000); const r = f.requests.at(-1); r.respond(envelope(r.id)); await flush(); }
    if (fault === 'stop') { f.boot.stop(); }
    eq(f.pending(), false, fault + ': no permanently hidden activity');
    eq(f.items[0].root.style.getPropertyValue(P), '', fault + ': native appearance preserved'); f.clean();
  }
  const missingStartup = setup(); await missingStartup.advance(15000); eq(missingStartup.doc.documentElement.classList.contains('pjh5pwp-readiness-armed'), false, 'Missing DOM/footer startup watchdog fails open'); await missingStartup.start(); eq(missingStartup.requests.length, 0, 'Late startup cannot renew failed gate'); missingStartup.clean();
  const late = setup(); await late.advance(9000); await late.start(); const delayed = late.requests.at(-1); await late.advance(6000);
  eq(delayed.init.signal.aborted, true, 'Total head deadline cancels request even when its own 10s timeout is later');
  delayed.respond(envelope(delayed.id)); await flush(); eq(late.items[0].root.style.writes, 0, 'Failed initial generation never accepts late fresh-age response'); late.clean();
  const duplicate = setup(); duplicate.doc.body.append(duplicate.el('div', 'h5p-content', { 'data-content-id': '10' })); await duplicate.start(); await duplicate.reply(); await duplicate.advance(15000); eq(duplicate.pending(), false, 'Ambiguous owner eventually ungated'); eq(duplicate.items[0].root.style.writes, 0, 'Ambiguous owner never styled'); duplicate.clean();
  const foreign = setup(); await foreign.start(); await foreign.reply(); foreign.items[0].root.style.setProperty(P, '#abc', 'important'); await foreign.mutate(); await foreign.advance(32); eq(foreign.pending(), false, 'Foreign conflict reveals fallback'); eq(foreign.items[0].root.style.getPropertyValue(P), '#abc', 'Foreign priority survives'); foreign.clean();
  const nested = setup([div, frame]);
  const children = nested.items.map(item => {
    const child = item.container.append(nested.el('div', 'h5p-question-set h5p-column-content'));
    child.ownerDocument = item.doc;
    item.view.H5P.instances.push({ contentId: 'sub-99', parent: item.instance, resizeCalls: [] }); return child;
  });
  await nested.start(); await nested.reply(); await nested.advance(32);
  eq(nested.boundaries.every(e => !e.node.hasAttribute('data-pjh5pwp-pending')), true, 'Nested QuestionSet/Column descendants do not invalidate unique main players');
  eq(children.map(c => c.style.writes), [0, 0], 'Nested content styled only by main-root inheritance'); nested.clean();
  const url = setup(); url.meta.setAttribute('data-pjh5pwp-manifest', JSON.stringify({ ...url.manifest, endpoint: 'https://[' })); await url.start(); eq(url.pending(), false, 'Malformed URL releases initial marker immediately'); eq(url.requests.length, 0, 'Malformed public manifest never sends appearance request'); url.clean();
  const empty = setup([]); empty.meta.setAttribute('data-pjh5pwp-manifest', JSON.stringify({ ...empty.manifest, players: [] })); await empty.start(); eq(empty.requests.length, 0, 'No players means no transport'); empty.clean();
  const blocked = fixture(undefined, { noStart: true }); blocked.doc.readyState = 'loading';
  eq(blocked.doc.documentElement.classList.contains('pjh5pwp-readiness-armed'), false, 'Disabled/blocked script never arms CSS');
  eq(api.bootstrap(blocked.win), null, 'Missing frame API stays fail-open');
  eq(blocked.doc.documentElement.classList.contains('pjh5pwp-readiness-armed'), false, 'Unsupported browser not concealed');
  console.log('Initial readiness: ' + checks + ' assertions passed (DOM/clock/frame fixtures; no browser).');
}
module.exports = { setup, div, frame };
if (require.main === module) run().catch(e => { console.error(e); process.exitCode = 1; });
