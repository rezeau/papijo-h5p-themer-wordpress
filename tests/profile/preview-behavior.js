/* Profile preview DOM/event regressions. No network, browser, storage or CMS. */
'use strict';
const assert = require('node:assert/strict'), fs = require('node:fs'), vm = require('node:vm');
const properties = require('../../assets/js/theme-controller.js').properties;
const source = fs.readFileSync('assets/js/profile-preview.js', 'utf8');
const P = '--h5p-theme-background', Q = '--h5p-theme-main-cta-base', E = '--h5p-theme-contrast-cta-light';
const expression = 'color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)';
let checks = 0;
function eq(actual, expected, label) { assert.deepEqual(actual, expected, label); checks++; }
class Element {
  constructor() { this.events = {}; this.attrs = {}; this.dataset = {}; }
  addEventListener(name, fn) { (this.events[name] ||= []).push(fn); }
  emit(name, detail = {}) { const event = { type: name, ...detail }; (this.events[name] || []).forEach(fn => fn(event)); }
  getAttribute(name) { return this.attrs[name]; }
}
function fixture({ loaded = true, initial = 'site_default', edit, sandbox = 'allow-same-origin', ready = 'complete' } = {}) {
  const data = { properties, themes: {
    site_default: { label: 'Site default', colors: { [P]: '#876543', [Q]: '#abc' } },
    core: { label: 'H5P Core defaults', colors: {} },
    'builtin:daylight': { label: 'Daylight', colors: { [P]: '#fff', [Q]: '#123', [E]: expression } },
    'custom:public': { label: '<script>Text only</script>', colors: { [P]: '#123456', [Q]: '#456' } },
    'custom:sparse': { label: 'Sparse legacy', colors: { [P]: '#def' } },
    keep_saved: { label: 'Saved preference unavailable; previewing site default', colors: { [P]: '#876543', [Q]: '#abc' } }
  }, messages: { title: 'Selected Theme preview', current: 'current', unsaved: 'unsaved', unavailable: 'unavailable' } };
  if (edit) edit(data);
  const form = new Element(), select = new Element(), density = new Element(), frame = new Element(), section = new Element(), status = new Element();
  select.value = initial; density.value = 'large'; frame.attrs.sandbox = sandbox;
  const style = { textContent: 'initial' }, heading = { textContent: 'initial' };
  Object.defineProperty(heading, 'innerHTML', { set() { throw new Error('No HTML injection'); } });
  const child = { title: '', getElementById: id => loaded && id === 'pjh5pwp-preview-overrides' ? style : null, querySelector: name => name === 'h2' ? heading : null };
  frame.contentDocument = child;
  section.attrs['data-pjh5pwp-profile-preview'] = JSON.stringify(data);
  section.closest = name => name === 'form' ? form : null;
  form.querySelector = name => name === '#pjh5pwp-theme' ? select : null;
  section.querySelector = name => name === 'iframe' ? frame : name === '[role="status"]' ? status : null;
  const listeners = {}, timers = [];
  const forbidden = () => { throw new Error('Preview attempted network/storage/save/focus'); };
  form.submit = form.requestSubmit = select.focus = frame.focus = forbidden;
  const document = { readyState: ready, querySelectorAll: () => [section], addEventListener: (name, fn) => { listeners[name] = fn; } };
  const context = { document, setTimeout: fn => timers.push(fn), fetch: forbidden, XMLHttpRequest: forbidden, localStorage: new Proxy({}, { get: forbidden }), sessionStorage: new Proxy({}, { get: forbidden }) };
  vm.runInNewContext(source, context);
  function flush() { while (timers.length) timers.shift()(); }
  return {
    data, form, select, density, frame, section, status, style, heading, child,
    boot: () => listeners.DOMContentLoaded?.(),
    selectTheme: token => { select.value = token; select.emit('change'); },
    load: () => { loaded = true; frame.emit('load'); },
    reset: (cancel = false) => { form.emit('reset', { defaultPrevented: cancel }); if (!cancel) select.value = initial; flush(); }
  };
}
const f = fixture();
eq(f.style.textContent, '.pjh5pwp-preview-sample{' + P + ':#876543;' + Q + ':#abc;}', 'Initial resolved default');
eq(f.status.textContent, 'current', 'Initial status does not claim a save');
f.selectTheme('builtin:daylight');
eq(f.style.textContent.includes(E + ':' + expression + ';'), true, 'Pinned expression preserved');
eq(f.status.textContent, 'unsaved', 'Selection change marked unsaved');
f.selectTheme('core'); eq(f.style.textContent, '.pjh5pwp-preview-sample{}', 'Core clears every override');
f.selectTheme('custom:public'); eq(f.heading.textContent, '<script>Text only</script>', 'Label is text');
eq(f.frame.title, 'Selected Theme preview: <script>Text only</script>', 'Descriptive title follows selection');
eq(f.child.title, f.frame.title, 'Child document title also follows selection');
f.selectTheme('custom:sparse');
eq(f.style.textContent, '.pjh5pwp-preview-sample{' + P + ':#def;}', 'Sparse legacy removes stale inherited properties');
f.reset(); eq(f.select.value, 'site_default', 'Native reset remains authoritative');
eq(f.status.textContent, 'current', 'Reset returns to initial status');
eq(f.style.textContent.includes('#876543'), true, 'Reset restores initial colors');
f.selectTheme('core'); f.reset(true); eq(f.select.value, 'core', 'Canceled reset does not change selection');
eq(f.style.textContent, '.pjh5pwp-preview-sample{}', 'Canceled reset preserves preview');
eq(f.density.value, 'large', 'Theme preview never changes Density');
eq(Object.keys(f.density.events), [], 'No Density handlers');
eq(f.select.value, 'core', 'Preview never writes selection');
f.selectTheme('keep_saved'); eq(f.style.textContent.includes('#876543'), true, 'keep_saved uses effective default');
f.selectTheme('custom:hidden'); eq(f.frame.hidden, true, 'Unknown/private token hides stale preview');
eq(f.status.textContent, 'unavailable', 'Unknown token has accessible fallback status');
f.selectTheme('core'); eq(f.frame.hidden, false, 'A valid choice recovers locally');

const late = fixture({ loaded: false });
eq(late.status.textContent, 'unavailable', 'Missing frame document is contained');
late.selectTheme('builtin:daylight'); late.selectTheme('core'); late.selectTheme('custom:public'); late.load();
eq(late.style.textContent.includes('#123456'), true, 'Late load applies only the latest of rapid changes');
eq(late.frame.hidden, false, 'Late loaded document becomes visible');
const resetLate = fixture({ loaded: false, initial: 'custom:public' });
resetLate.selectTheme('core'); resetLate.reset(); resetLate.load();
eq(resetLate.style.textContent.includes('#123456'), true, 'Reset before frame load preserves initial redisplayed choice');
eq(resetLate.status.textContent, 'current', 'Validation-redisplayed choice is initial, without claiming saved');
const early = fixture({ ready: 'loading' }); eq(early.style.textContent, 'initial', 'Waits for DOM');
early.boot(); early.boot(); eq(early.select.events.change.length, 1, 'Boot idempotent');
const altered = fixture({ sandbox: 'allow-same-origin allow-scripts' });
eq(altered.style.textContent, 'initial', 'Unexpected sandbox never accessed');
const inaccessible = fixture();
Object.defineProperty(inaccessible.frame, 'contentDocument', { get() { throw new Error('No access'); } });
inaccessible.selectTheme('core'); eq(inaccessible.status.textContent, 'unavailable', 'Frame access failure contained');
eq(inaccessible.select.value, 'core', 'Access failure preserves choice');
for (const [label, bad] of [
  ['unknown property', { '--evil': '#fff' }], ['CSS injection', { [P]: '#fff;}body{display:none' }],
  ['URL', { [P]: 'url(https://invalid)' }], ['newline', { [P]: '#fff\n' }],
  ['expression on wrong property', { [P]: expression }], ['unknown expression', { [E]: 'var(--evil)' }],
  ['array', []], ['non-string', { [P]: 42 }]
]) {
  const badFrame = fixture({ edit: data => { data.themes.core.colors = bad; } });
  badFrame.selectTheme('core'); eq(badFrame.frame.hidden, true, label + ': fail closed');
  eq(badFrame.status.textContent, 'unavailable', label + ': accessible status');
}
const wrongProperties = fixture({ edit: data => { data.properties = [...properties.slice(1), '--evil']; } });
eq(wrongProperties.style.textContent, 'initial', 'Untrusted allowlist rejected');
const malformed = fixture({ ready: 'loading' }); malformed.section.attrs['data-pjh5pwp-profile-preview'] = '{broken'; malformed.boot();
eq(malformed.style.textContent, 'initial', 'Malformed metadata leaves server preview and controls intact');
const rgba = fixture({ edit: data => { data.themes.core.colors = { [E]: 'rgba(59, 130, 246, 0.15)' }; } });
rgba.selectTheme('core'); eq(rgba.style.textContent.includes('rgba('), true, 'Other exact preset expression allowed');
console.log('Profile preview JavaScript: ' + checks + ' assertions passed; no requests, writes, focus changes or real browser.');
