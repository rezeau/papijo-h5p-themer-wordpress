/* Adapted companion DOM and automatic mutation fixtures; no live browser or storage. */
'use strict';
const assert = require('node:assert/strict');
const cp = require('node:child_process');
const path = require('node:path');
const {fixture,flush} = require('./theme-controller.js');
const examples = JSON.parse(cp.execFileSync('php',['-n',path.join(__dirname,'anonymous-examples.php')],{encoding:'utf8'}));
function dom(f) {
  const E = f.items[0].root.constructor;
  E.prototype.appendChild = function (node) { return this.append(node); };
  E.prototype.removeChild = function (node) { node.remove(); return node; };
  E.prototype.insertBefore = function (node, reference) { node.remove(); const i = this.children.indexOf(reference); assert.ok(i >= 0); this.children.splice(i, 0, node); node.parentNode = this; return node; };
  Object.defineProperty(E.prototype, 'firstChild', { configurable: true, get() { return this.children[0] || null; } });
  Object.defineProperty(E.prototype, 'nextSibling', { configurable: true, get() { const a = this.parentNode?.children; return a?.[a.indexOf(this) + 1] || null; } });
  Object.defineProperty(E.prototype, 'textContent', { configurable: true, get() { return this._text || ''; }, set(value) { this._text = value; this.textWrites = (this.textWrites || 0) + 1; } });
  f.doc.createElement = tag => new E(f.doc, tag);
}
async function drain() { for (let i = 0; i < 250; i++) await Promise.resolve(); }

function automaticMutations(f) {
  const stats = f.mutations = { deliveries: 0, disabledWrites: 0, valueWrites: 0, childWrites: 0, overflow: false };
  const observers = [];
  function changed(target, type) {
    for (const observer of observers) {
      if (!observer.connected || !observer.options[type] ||
          !(observer.target === target || (observer.options.subtree && observer.target.contains(target)))) continue;
      observer.records.push({ target, type });
      if (observer.queued) continue;
      observer.queued = true;
      queueMicrotask(() => {
        observer.queued = false;
        const records = observer.records.splice(0);
        if (!observer.connected || !records.length || stats.overflow) return;
        if (++stats.deliveries > 200) { stats.overflow = true; return; }
        observer.fn(records, observer);
      });
    }
  }
  f.win.MutationObserver = class {
    constructor(fn) { this.fn = fn; this.records = []; observers.push(this); f.observers.push(this); }
    observe(target, options) { this.target = target; this.options = options; this.connected = true; }
    disconnect() { this.connected = false; this.records = []; }
  };
  function attributes(element) {
    const set = element.setAttribute, remove = element.removeAttribute;
    element.setAttribute = function (name, value) {
      set.call(this, name, value);
      // Equal-valued setAttribute is still observable, as with reflected boolean setters.
      changed(this, 'attributes');
    };
    element.removeAttribute = function (name) {
      const existed = this.hasAttribute(name); remove.call(this, name);
      if (existed) changed(this, 'attributes');
    };
  }
  attributes(f.doc.body);
  const insert = f.doc.body.insertBefore;
  f.doc.body.insertBefore = function (element, reference) {
    const result = insert.call(this, element, reference); stats.childWrites++; changed(this, 'childList'); return result;
  };
  const create = f.doc.createElement;
  f.doc.createElement = tag => {
    const element = create(tag); attributes(element);
    for (const name of ['appendChild', 'removeChild']) {
      const original = element[name];
      element[name] = function (...args) {
        const result = original.apply(this, args); stats.childWrites++; changed(this, 'childList'); return result;
      };
    }
    Object.defineProperty(element, 'textContent', {
      get() { return this._text || ''; },
      set(value) { this._text = value; this.textWrites = (this.textWrites || 0) + 1; changed(this, 'childList'); }
    });
    if (tag === 'select' || tag === 'button') {
      Object.defineProperty(element, 'disabled', {
        get() { return this.hasAttribute('disabled'); },
        set(value) { stats.disabledWrites++; if (value) this.setAttribute('disabled', ''); else this.removeAttribute('disabled'); }
      });
    }
    if (tag === 'select') {
      Object.defineProperty(element, 'value', {
        get() { return this._value === undefined ? (this.firstChild?.getAttribute('value') || '') : this._value; },
        set(value) { stats.valueWrites++; this._value = value; }
      });
    }
    return element;
  };
}

const node = (f,s) => f.doc.querySelectorAll(s)[0];
function response(r,key) {
 const sent=Object.fromEntries(new URLSearchParams(r.init.body));
 const body=JSON.parse(JSON.stringify(examples[key || (sent.theme+'/'+sent.density)]));
 body.request_id=r.id;return body;
}
module.exports={fixture,flush,dom,node,response,automaticMutations,drain};
