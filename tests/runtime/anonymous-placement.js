/* Two focused placement cases. No browser, WordPress bootstrap or live storage. */
'use strict';
const assert = require('node:assert/strict');
const path = require('node:path');
const api = require('../../assets/js/anonymous-controller.js');
const { fixture } = require('./anonymous-fixtures.js');
const { dom, node, response } = require('./anonymous-fixtures.js');
const { automaticMutations, drain } = require('./anonymous-fixtures.js');
let checks = 0;
function eq(actual, expected, message) { assert.ok(actual === expected, message); checks++; }
const first = { content_id: '9', embed: 'iframe', library: 'H5P.DialogcardsPapiJo 1.17' };
const second = { content_id: '1', embed: 'div', library: 'H5P.Accordion 1.0' };
const values = r => Object.fromEntries(new URLSearchParams(r.init.body));
async function test(reversedInitialization) {
  const f = fixture([first, second], { noStart: true }); dom(f); automaticMutations(f);
  f.doc.URL = 'https://fixture.test/multiple/'; f.win.location = new URL(f.doc.URL);
  // Actual main registry sorts IDs, independently of DOM positions.
  f.manifest.players = [second, first];
  const prefix = 'pjh5pwp:anonymous:v1:' + encodeURIComponent('https://fixture.test/') + ':';
  const saved = new Map([[prefix + 'theme', '"builtin:dark"'], [prefix + 'density', '"small"']]);
  f.win.localStorage = { getItem: key => saved.get(key) ?? null,
    setItem: (key, value) => saved.set(key, value), removeItem: key => saved.delete(key) };
  if (reversedInitialization) {
    f.items[0].frame.classList.remove('h5p-initialized');
    f.items[0].root.classList.remove('h5p-initialized');
  }
  const metadata = { version: 1, page: '/multiple/', endpoint: f.manifest.endpoint, home: 'https://fixture.test/' };
  const grants = f.manifest.players.map(player => ({ player, nonce: '123456abcd' }));
  const controller = api.createGeneral(f.win, f.manifest, metadata, grants, 'https://fixture.test/main/theme-controller.js');
  assert.ok(controller); controller.start(); await drain();
  const panel = node(f, '[data-pjh5pwp-anonymous-selector]');
  eq(f.doc.querySelectorAll('[data-pjh5pwp-anonymous-selector]').length, 1, 'Exactly one selector before responses');
  eq(panel.nextSibling, f.items[0].wrapper, 'Selector immediately precedes DOM-first H5P even when manifest/initialization order differs');
  eq(panel.nextSibling === f.items[1].root, false, 'No selector above the later activity');
  const laterRequest = f.requests.find(r => JSON.parse(values(r).player).content_id === second.content_id);
  laterRequest.respond(response(laterRequest)); await drain();
  eq(f.items[1].root.classList.contains('h5p-small'), true, 'Later activity can initialize/apply first');
  eq(panel.nextSibling, f.items[0].wrapper, 'First response does not relocate selector');
  if (reversedInitialization) {
    eq(f.items[0].root.style.writes, 0, 'Uninitialized earlier activity remains untouched');
    f.items[0].frame.classList.add('h5p-initialized');
    f.items[0].root.classList.add('h5p-initialized'); await f.mutate();
  }
  const earlierRequest = f.requests.find(r => JSON.parse(values(r).player).content_id === first.content_id);
  earlierRequest.respond(response(earlierRequest)); await drain();
  eq(controller.snapshot().state, 'applied', 'Both activities finish appearance application');
  eq(f.items.every(i => i.root.classList.contains('h5p-small')), true, 'Shared Density reaches both activities');
  const property = api.properties[0];
  eq(f.items[0].root.style.getPropertyValue(property), f.items[1].root.style.getPropertyValue(property), 'Same selected Theme reaches both activities');
  assert.notEqual(f.items[0].root.style.getPropertyValue(property), ''); checks++;
  const start = f.requests.length;
  const theme = node(f, 'select[id="pjh5pwp-anonymous-theme"]'); theme.value = 'builtin:lavender'; theme.emit('change');
  const themeRequests = f.requests.slice(start);
  eq(themeRequests.length, 2, 'Theme change requests appearance for both activities');
  const expectedTheme = api.colors(response(themeRequests[0]).appearance.color_declarations)[property];
  for (const request of themeRequests) request.respond(response(request)); await drain();
  eq(f.items.every(i => i.root.style.getPropertyValue(property) === expectedTheme), true, 'Both activities receive the newly selected Theme palette');
  eq(f.requests.slice(start).every(r => values(r).theme === 'builtin:lavender'), true, 'One Theme control refreshes both activities');
  eq(f.items[0].root.style.getPropertyValue(property), f.items[1].root.style.getPropertyValue(property), 'Changed Theme agrees across both activities');
  const densityStart = f.requests.length;
  const density = node(f, 'select[id="pjh5pwp-anonymous-density"]'); density.value = 'large'; density.emit('change');
  eq(f.requests.length - densityStart, 2, 'Density change requests appearance for both activities');
  for (const request of f.requests.slice(densityStart)) request.respond(response(request)); await drain();
  eq(f.items.every(i => i.root.classList.contains('h5p-large')), true, 'One Density control changes both activities');
  await f.mutate(); await drain();
  eq(f.doc.querySelectorAll('[data-pjh5pwp-anonymous-selector]').length, 1, 'Repeated scans never duplicate selector');
  eq(panel.nextSibling, f.items[0].wrapper, 'Selector stays above DOM-first activity after both initialize and selection changes');
  eq(f.mutations.overflow, false, 'Automatic mutation delivery settles');
  controller.stop(); eq(f.timers.size, 0, 'All timers cleaned up');
}
(async () => {
  await test(false); await test(true);
  console.log(JSON.stringify({ tests: 2, assertions: checks, browser_automation: false, live_wordpress: false }));
})().catch(error => { console.error(error); process.exitCode = 1; });
