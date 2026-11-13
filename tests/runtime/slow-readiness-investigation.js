/* Characterize existing slow-start behavior. No production changes, browser, CMS or network.
 * Run from repository root: node tests/runtime/slow-readiness-investigation.js
 * These assertions document the current failure paths; they do not endorse their UX.
 */
'use strict';
const assert = require('node:assert/strict');
const { setup, div, frame } = require('./readiness-controller.js');
const { flush, P } = require('./theme-controller.js');
let checks = 0, scenarios = 0;
function eq(actual, expected, message) { assert.deepEqual(actual, expected, message); checks++; }
function original(f, label) {
  const { node, status } = f.boundaries[0], item = f.items[0];
  eq(f.pending(), false, label + ': content no longer concealed');
  eq(node.hasAttribute('aria-busy'), false, label + ': busy state released');
  eq(status.hasAttribute('data-pjh5pwp-fallback'), true, label + ': explicit failure status');
  eq(status.hidden, false, label + ': failure status remains accessible');
  eq(status.textContent, 'Native fallback', label + ': fallback text');
  eq(item.root.style.getPropertyValue(P), '', label + ': no owned Theme override');
  eq(item.root.classes.has('h5p-small'), false, label + ': no owned Density');
  eq(item.root.classes.has('h5p-large'), true, label + ': native Density restored');
}
function styled(f, label) {
  eq(f.pending(), false, label + ': revealed');
  eq(f.boundaries[0].status.hidden, true, label + ': status dismissed');
  eq(f.items[0].root.style.getPropertyValue(P), '#123', label + ': Theme applied');
  eq(f.items[0].root.classes.has('h5p-small'), true, label + ': Density applied');
}
async function scenario(player, label, run) {
  const f = setup([player]);
  try { await run(f); scenarios++; console.log('PASS ' + player.embed + ': ' + label); }
  finally { f.clean(); }
}
async function run() {
  for (const player of [div, frame]) {
    await scenario(player, 'normal startup succeeds', async f => {
      await f.start(); await f.reply(); await f.advance(32); styled(f, 'normal');
    });
    await scenario(player, 'DOM readiness after 15s prevents any appearance request', async f => {
      await f.advance(16000); await f.start();
      eq(f.requests.length, 0, 'No AJAX failure is required: request never started');
      original(f, 'late DOM');
    });
    await scenario(player, 'late DOM leaves only 3s for a healthy request', async f => {
      await f.advance(12000); await f.start(); const request = f.requests[0];
      await f.advance(2999); eq(f.pending(), true, 'Still within head budget');
      await f.advance(1); eq(request.init.signal.aborted, true, 'Head watchdog cancels request at total 15s');
      original(f, 'head watchdog');
      await f.advance(500); await f.reply();
      eq(f.items[0].root.style.writes, 0, 'Otherwise valid 3.5s response cannot recover the failed generation');
      eq(f.requests.length, 1, 'No automatic request retry');
    });
    await scenario(player, '10s transport timeout independently causes the same fallback', async f => {
      await f.start(); const request = f.requests[0];
      await f.advance(9999); eq(f.pending(), true, 'Transport still pending');
      await f.advance(1); eq(request.init.signal.aborted, true, 'Transport deadline aborts before head deadline');
      original(f, 'transport deadline');
      await f.reply(); original(f, 'late transport reply');
    });
    for (const readyAt of [14000, 16000]) {
      await scenario(player, 'main H5P initializes at ' + readyAt + 'ms', async f => {
        f.items[0].root.classList.remove('h5p-initialized');
        await f.start(); await f.reply();
        await f.advance(readyAt);
        f.items[0].root.classList.add('h5p-initialized'); await f.mutate(); await f.advance(32);
        if (readyAt < 15000) styled(f, 'late instance before deadline');
        else { original(f, 'late instance after deadline'); eq(f.requests.length, 1, 'Successful initial response is not retried'); }
      });
      await scenario(player, 'stylesheet load event at ' + readyAt + 'ms', async f => {
        f.items[0].root.stylesReady = false;
        await f.start(); await f.reply(); await f.advance(readyAt);
        f.items[0].root.stylesReady = true;
        f.items[0].doc.emit('load'); await flush(); await f.advance(32);
        if (readyAt < 15000) styled(f, 'late styles before deadline');
        else original(f, 'late styles after deadline');
      });
    }
    for (const delayed of ['theme', 'density']) {
      await scenario(player, 'independent ' + delayed + ' CSS readiness, shared reveal deadline', async f => {
        const state = { theme: delayed !== 'theme', density: delayed !== 'density' };
        f.items[0].view.getComputedStyle = root => ({ getPropertyValue: name => {
          if (name === P && !state.theme || name === '--h5p-theme-scaling' && !state.density) return '';
          return root.style.getPropertyValue(name) || '#native';
        } });
        await f.start(); await f.reply();
        eq(f.pending(), true, 'Coordinated reveal waits for both dimensions');
        eq(f.items[0].root.style.getPropertyValue(P), delayed === 'theme' ? '' : '#123', 'Theme application has its own prerequisite');
        eq(f.items[0].root.classes.has('h5p-small'), delayed === 'theme', 'Density application has its own prerequisite');
        await f.advance(15000); original(f, 'one dimension missed deadline');
        state[delayed] = true; f.items[0].doc.emit('load'); await f.mutate(); await f.advance(32);
        original(f, 'late CSS cannot recover initial failure');
      });
    }
    await scenario(player, 'actual network rejection has the same user message', async f => {
      await f.start(); await f.advance(100); f.requests[0].fail(); await flush();
      original(f, 'network rejection');
    });
  }
  await scenario(frame, 'all resources ready but iframe height never matches body', async f => {
    f.items[0].frame.style.height = '240px';
    await f.start(); await f.reply(); await f.advance(14999);
    eq(f.pending(), true, 'Fresh descriptor and CSS alone do not pass iframe layout check');
    eq(f.items[0].root.style.getPropertyValue(P), '#123', 'Theme was applied while concealed');
    eq(f.items[0].root.classes.has('h5p-small'), true, 'Density was applied while concealed');
    await f.advance(1); original(f, 'iframe sizing deadline');
  });
  console.log('Slow-readiness characterization: ' + scenarios + ' scenarios; ' + checks + ' assertions passed (deterministic fixtures; no live page reproduction).');
}
run().catch(error => { console.error(error); process.exitCode = 1; });
