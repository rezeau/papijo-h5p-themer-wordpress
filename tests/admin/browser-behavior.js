/* Isolated DOM/event doubles for admin.js. No browser, WordPress, network or saves. */
'use strict';
const fs = require('fs');
const path = require('path');
const vm = require('vm');
const assert = require('assert');
let checks = 0;
function same(a, b, message) { assert.deepStrictEqual(b, a, message); checks++; }
function check(value, message) { assert.ok(value, message); checks++; }
class Element {
  constructor() { this.dataset = {}; this.events = {}; this.attributes = {}; }
  addEventListener(event, callback) { (this.events[event] ||= []).push(callback); }
  dispatchEvent(event) { (this.events[event.type] || []).forEach(fn => fn(event)); }
  setAttribute(key, value) { this.attributes[key] = value; }
  getAttribute(key) { return this.attributes[key]; }
}
const suffixes = [
  'main-cta-base','main-cta-light','main-cta-dark','contrast-cta','contrast-cta-white','contrast-cta-light','focus',
  'secondary-cta-base','secondary-cta-light','secondary-cta-dark','secondary-contrast-cta','secondary-contrast-cta-hover',
  'background','ui-base','alternative-base','alternative-light','alternative-dark','alternative-darker',
  'text-primary','text-secondary','text-third','stroke-1','stroke-2','stroke-3',
  'feedback-correct-main','feedback-correct-secondary','feedback-correct-third',
  'feedback-incorrect-main','feedback-incorrect-secondary','feedback-incorrect-third',
  'feedback-neutral-main','feedback-neutral-secondary','feedback-neutral-third'
];
const properties = suffixes.map(suffix => '--h5p-theme-' + suffix);
function fixture({ expression = false, legacy = false, sandbox = 'allow-same-origin', loaded = true } = {}) {
  const form = new Element();
  const saved = { [properties[0]]: '#123', [properties[1]]: '#abcdef' };
  const inherited = legacy ? {} : Object.fromEntries(properties.map(p => [p, '#fff']));
  if (expression) { inherited[properties[5]] = 'color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)'; }
  const fields = properties.map(p => {
    const field = new Element();
    field.value = saved[p] || ''; field.defaultValue = field.value;
    field.dataset = { pjh5pwpColor: p, pjh5pwpInherited: inherited[p] || '' };
    const picker = new Element(); picker.hidden = true;
    const status = new Element();
    const row = { querySelector: selector => selector === '.pjh5pwp-picker' ? picker : status };
    field.closest = () => row;
    field.picker = picker; field.status = status;
    return field;
  });
  const style = { textContent: '' };
  const frame = new Element(); frame.attributes.sandbox = sandbox;
  frame.contentDocument = { getElementById: id => id === 'pjh5pwp-preview-overrides' && loaded ? style : null };
  const status = new Element();
  const section = new Element(); section.dataset.pjh5pwpPreview = JSON.stringify({ properties, saved, inherited });
  section.querySelector = selector => selector === '.pjh5pwp-preview-frame' ? frame : status;
  form.querySelectorAll = selector => selector === '[data-pjh5pwp-color]' ? fields : [section];
  const listeners = {}, timers = [];
  const document = { readyState: 'loading', querySelectorAll: () => [form], addEventListener: (event, fn) => { listeners[event] = fn; } };
  const context = { document, window: { 'pjh5pwp-admin': { invalid: 'invalid', inherited: 'inherit', expression: 'expression', saved: 'saved', unsaved: 'unsaved', unavailable: 'unavailable' } }, setTimeout: fn => timers.push(fn), Event: class { constructor(type) { this.type = type; } } };
  vm.runInNewContext(fs.readFileSync(path.join(__dirname, '../../assets/js/admin.js'), 'utf8'), context);
  function boot() { listeners.DOMContentLoaded(); }
  function input(index, value) { fields[index].value = value; fields[index].dispatchEvent({ type: 'input' }); }
  function reset() { form.dispatchEvent({ type: 'reset' }); fields.forEach(f => { f.value = f.defaultValue; }); while (timers.length) { timers.shift()(); } }
  boot();
  return { form, fields, style, frame, status, boot, input, reset, loaded: () => { loaded = true; frame.dispatchEvent({ type: 'load' }); } };
}
const f = fixture({ expression: true });
same('saved', f.status.textContent, 'Starts with saved palette');
same(false, f.fields[0].picker.hidden, 'Native picker revealed with JS');
same('#112233', f.fields[0].picker.value, 'Shorthand expanded only for picker');
same('#123', f.fields[0].value, 'Stored shorthand text preserved');
same(true, f.fields[5].picker.disabled, 'Expression is not fabricated as a hexadecimal color');
same('expression', f.fields[5].status.textContent, 'Expression picker status explained');
f.input(5, '#abc'); same(false, f.fields[5].picker.disabled, 'Explicit override enables picker');
f.input(0, '#aBcDeF'); check(f.style.textContent.includes(properties[0] + ':#aBcDeF;'), 'Valid edit reaches frame style');
same('unsaved', f.status.textContent, 'Unsaved status');
f.input(0, '#abcdef\n'); same('invalid', f.fields[0].status.textContent, 'Trailing newline rejected like PHP');
check(f.style.textContent.includes(properties[0] + ':#aBcDeF;'), 'Invalid edit keeps last valid property');
f.input(1, '#987'); check(f.style.textContent.includes(properties[1] + ':#987;'), 'Other edits still apply with one invalid field');
f.input(0, 'url(https://evil.invalid/)'); check(!f.style.textContent.includes('evil'), 'Invalid CSS excluded');
f.input(0, ''); check(f.style.textContent.includes(properties[0] + ':#fff;'), 'Clear restores inherited palette');
f.input(5, ''); check(f.style.textContent.includes('color-mix('), 'Clear restores inherited expression');
f.fields[0].picker.value = '#aabbcc'; f.fields[0].picker.dispatchEvent({ type: 'input' });
same('#aabbcc', f.fields[0].value, 'Native picker creates explicit override');
check(f.style.textContent.includes(properties[0] + ':#aabbcc;'), 'Picker updates preview');
f.reset(); same('#123', f.fields[0].value, 'Reset restores initial text');
same('#112233', f.fields[0].picker.value, 'Reset synchronizes swatch');
same('saved', f.status.textContent, 'Reset restores saved status');
check(f.style.textContent.includes(properties[0] + ':#123;'), 'Reset restores saved preview');
f.boot(); same(1, f.fields[0].events.input.length - 1, 'Boot is idempotent: one companion and one preview handler');
const legacy = fixture({ legacy: true });
legacy.input(0, ''); check(!legacy.style.textContent.includes(properties[0] + ':'), 'Legacy clear removes override and lets Core CSS inherit');
same(true, legacy.fields[0].picker.disabled, 'Unknown Core value does not invent a swatch');
legacy.input(0, '#321'); same(false, legacy.fields[0].picker.disabled, 'Explicit legacy hex enables picker');
const badSandbox = fixture({ sandbox: 'allow-same-origin allow-scripts' });
same('', badSandbox.style.textContent, 'Unexpected sandbox blocks live preview access');
const late = fixture({ loaded: false });
same('unavailable', late.status.textContent, 'Missing frame style safely reported');
late.input(0, '#789'); late.loaded(); check(late.style.textContent.includes(properties[0] + ':#789;'), 'Late frame load applies current valid fields');
const inaccessible = fixture();
Object.defineProperty(inaccessible.frame, 'contentDocument', { get() { throw new Error('Access denied'); } });
inaccessible.input(0, '#456'); same('unavailable', inaccessible.status.textContent, 'Frame access failures contained');
same('#456', inaccessible.fields[0].value, 'Preview failure preserves editable fields');
console.log('Phase 1C-B isolated DOM behavior: ' + checks + ' assertions passed.');
