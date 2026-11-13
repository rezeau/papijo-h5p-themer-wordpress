/* Native color companions and isolated, color-only previews.
 * Adapted behavior from Backdrop v0.5.0; GPL-2.0-or-later original,
 * GPL-3.0-or-later adaptation. No persistence or H5P playback integration.
 */
(function () {
  'use strict';
  var messages = window['pjh5pwp-admin'] || {};
  var valid = function (value) {
    return typeof value === 'string' && (value.length === 4 || value.length === 7) && /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(value);
  };
  var full = function (value) {
    return value.length === 4 ? '#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3] : value;
  };
  function boot() {
    document.querySelectorAll('.pjh5pwp-admin .pjh5pwp-form').forEach(function (form) {
      if (form.dataset.pjh5pwpReady) { return; }
      form.dataset.pjh5pwpReady = '1';
      var fields = Array.from(form.querySelectorAll('[data-pjh5pwp-color]'));
      var companions = [];
      fields.forEach(function (field) {
        var row = field.closest('.pjh5pwp-color-row');
        var picker = row && row.querySelector('.pjh5pwp-picker');
        var status = row && row.querySelector('.pjh5pwp-color-status');
        if (!picker || !status) { return; }
        picker.hidden = false;
        function sync() {
          var inherited = field.dataset.pjh5pwpInherited || '';
          var isValid = valid(field.value);
          var swatch = isValid ? field.value : inherited;
          var bad = field.value !== '' && !isValid;
          picker.disabled = field.disabled || field.readOnly || !valid(swatch);
          if (valid(swatch)) { picker.value = full(swatch); }
          field.setAttribute('aria-invalid', bad ? 'true' : 'false');
          status.textContent = bad ? (messages.invalid || '') : (field.value === '' ? (valid(inherited) ? messages.inherited : messages.expression) || '' : '');
        }
        field.addEventListener('input', sync);
        field.addEventListener('change', sync);
        picker.addEventListener('input', function () {
          if (picker.disabled || !valid(picker.value)) { return; }
          field.value = picker.value;
          field.dispatchEvent(new Event('input', { bubbles: true }));
          field.dispatchEvent(new Event('change', { bubbles: true }));
        });
        companions.push(sync); sync();
      });
      var previews = [];
      form.querySelectorAll('[data-pjh5pwp-preview]').forEach(function (section) {
        var metadata;
        try { metadata = JSON.parse(section.dataset.pjh5pwpPreview); } catch (error) { return; }
        if (!Array.isArray(metadata.properties) || !metadata.saved || !metadata.inherited) { return; }
        var frame = section.querySelector('.pjh5pwp-preview-frame');
        var status = section.querySelector('.pjh5pwp-preview-status');
        if (!frame || !status || frame.getAttribute('sandbox') !== 'allow-same-origin') { return; }
        var properties = metadata.properties.filter(function (property) { return /^--h5p-theme-[a-z0-9-]+$/.test(property); });
        var inputs = {};
        fields.forEach(function (field) { inputs[field.dataset.pjh5pwpColor] = field; });
        if (properties.length !== 33 || new Set(properties).size !== 33 || properties.some(function (p) { return !inputs[p]; })) { return; }
        var last = {};
        function resetLast() { properties.forEach(function (p) { last[p] = valid(metadata.saved[p] || '') ? metadata.saved[p] : ''; }); }
        resetLast();
        function apply() {
          var changed = false;
          properties.forEach(function (p) {
            var value = inputs[p].value;
            if (value === '' || valid(value)) { last[p] = value; }
            if (value !== (metadata.saved[p] || '')) { changed = true; }
          });
          try {
            var style = frame.contentDocument && frame.contentDocument.getElementById('pjh5pwp-preview-overrides');
            if (!style) { status.textContent = messages.unavailable || ''; return; }
            var declarations = '';
            properties.forEach(function (p) {
              var value = last[p] || metadata.inherited[p] || '';
              if (value) { declarations += p + ':' + value + ';'; }
            });
            style.textContent = '.pjh5pwp-preview-sample{' + declarations + '}';
            status.textContent = (changed ? messages.unsaved : messages.saved) || '';
          } catch (error) { status.textContent = messages.unavailable || ''; }
        }
        fields.forEach(function (field) { field.addEventListener('input', apply); field.addEventListener('change', apply); });
        frame.addEventListener('load', apply);
        previews.push(function () { resetLast(); apply(); }); apply();
      });
      form.addEventListener('reset', function () {
        // Native reset updates values after this event, before the next task.
        setTimeout(function () { companions.forEach(function (sync) { sync(); }); previews.forEach(function (reset) { reset(); }); }, 0);
      });
    });
  }
  if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', boot); } else { boot(); }
}());
