/* Read-only Profile Theme selection preview. No requests, storage or Density handling. GPL-3.0-or-later. */
(function () {
  'use strict';
  var properties = [
    'main-cta-base', 'secondary-cta-base', 'alternative-base', 'background', 'focus',
    'main-cta-light', 'main-cta-dark', 'contrast-cta', 'contrast-cta-white', 'contrast-cta-light',
    'secondary-cta-light', 'secondary-cta-dark', 'secondary-contrast-cta', 'secondary-contrast-cta-hover',
    'alternative-light', 'alternative-dark', 'alternative-darker', 'text-primary', 'text-secondary',
    'text-third', 'ui-base', 'stroke-1', 'stroke-2', 'stroke-3', 'feedback-correct-main',
    'feedback-correct-secondary', 'feedback-correct-third', 'feedback-incorrect-main',
    'feedback-incorrect-secondary', 'feedback-incorrect-third', 'feedback-neutral-main',
    'feedback-neutral-secondary', 'feedback-neutral-third'
  ].map(function (name) { return '--h5p-theme-' + name; });
  function valid(name, value) {
    return typeof value === 'string' && !/[\r\n]/.test(value) &&
      (/^#(?:[a-fA-F0-9]{3}|[a-fA-F0-9]{6})$/.test(value) ||
       name === '--h5p-theme-contrast-cta-light' && [
         'color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%)', 'rgba(59, 130, 246, 0.15)'
       ].indexOf(value) !== -1);
  }
  function boot() {
    document.querySelectorAll('[data-pjh5pwp-profile-preview]').forEach(function (section) {
      if (section.dataset.pjh5pwpReady) { return; }
      var data;
      try { data = JSON.parse(section.getAttribute('data-pjh5pwp-profile-preview')); } catch (error) { return; }
      var form = section.closest('form'), select = form && form.querySelector('#pjh5pwp-theme');
      var frame = section.querySelector('iframe'), status = section.querySelector('[role="status"]');
      if (!select || !frame || !status || frame.getAttribute('sandbox') !== 'allow-same-origin' ||
          !data || !data.themes || !data.messages || !Array.isArray(data.properties) ||
          data.properties.length !== properties.length || data.properties.some(function (p, i) { return p !== properties[i]; })) { return; }
      section.dataset.pjh5pwpReady = '1';
      var initial = select.value;
      function unavailable() { frame.hidden = true; status.textContent = data.messages.unavailable; }
      function apply() {
        var token = select.value;
        if (!Object.prototype.hasOwnProperty.call(data.themes, token)) { unavailable(); return; }
        var theme = data.themes[token];
        if (!theme || typeof theme.label !== 'string' || !theme.colors || typeof theme.colors !== 'object' || Array.isArray(theme.colors)) { unavailable(); return; }
        var names = Object.keys(theme.colors);
        if (names.some(function (p) { return properties.indexOf(p) === -1 || !valid(p, theme.colors[p]); })) { unavailable(); return; }
        try {
          var doc = frame.contentDocument, style = doc && doc.getElementById('pjh5pwp-preview-overrides');
          var heading = doc && doc.querySelector('h2');
          if (!style || !heading) { unavailable(); return; }
          // Replace every override, including clearing to Core's empty set.
          style.textContent = '.pjh5pwp-preview-sample{' + names.map(function (p) { return p + ':' + theme.colors[p] + ';'; }).join('') + '}';
          heading.textContent = theme.label;
          doc.title = data.messages.title + ': ' + theme.label;
          frame.title = doc.title; frame.hidden = false;
          status.textContent = token === initial ? data.messages.current : data.messages.unsaved;
        } catch (error) { unavailable(); }
      }
      select.addEventListener('change', apply);
      frame.addEventListener('load', apply); // Reads the latest selection, never a captured older one.
      form.addEventListener('reset', function (event) { setTimeout(function () { if (!event.defaultPrevented) { apply(); } }, 0); });
      apply();
    });
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
}());
