# Provenance and third-party licenses

## Phase 1B domain and foundation

This independent WordPress plugin is GPL-3.0-or-later; the full text is in
`LICENSE`. Phase 1B includes the literal Backdrop palette catalog and adaptations
of its property definitions, validation, resolution and publication semantics.
No H5P runtime, picker dependency or SNORDIAN WordPress implementation is included.

The architectural reference is H5P Themer for Backdrop v0.5.0, commit
`d9b53d706a94dac322b89885bf668145ab3803c4`. Compatibility identifiers for H5P and
SNORDIAN are recorded only to identify integrations and prevent runtime conflict.
The test fixtures are independent test doubles, not third-party implementations.

## Included palette provenance

The Backdrop catalog records extraction/adaptation on 16 October 2026 from
Moodle revision `478fed30d2a4af58638f2ddbea3603db1d4d8c5f`:

- **Daylight, Lavender, Mint, Sunset:** Copyright (c) 2026 Oliver Tacke, MIT.
  Source: literal theme data in `js/h5p-theme-picker.js`, Git blob
  `0cd5829b33050f1ee3314fe603911c3644134f2e`. The pinned Moodle
  `thirdpartylibs.xml` identifies picker version 0.0.13. The complete
  notice is included in `licenses/MIT-H5P-THEME-PICKER.txt`.
- **Dark:** Copyright 2026 Matheus Mathias, GPL-3.0-or-later. Source:
  `getBuiltInPresets().dark` in `amd/src/presets.js`, Git blob
  `2529338c1ffdec331d84045772fa67e3bdf3c11a`. Retain this attribution and GPLv3.
- Original Backdrop files retain their GPL-2.0-or-later grants; the GPLv2 text
  is preserved in `licenses/GPL-2.0.txt` for the domain adaptations. The combined
  palette-containing plugin preserves the GPL-3.0-or-later and MIT
  requirements described by the Backdrop source's THIRD-PARTY-NOTICES.md.

References:

- [Pinned Moodle picker data](https://github.com/Matheu46/moodle-local_h5pthemer/blob/478fed30d2a4af58638f2ddbea3603db1d4d8c5f/js/h5p-theme-picker.js)
- [Pinned Dark palette](https://github.com/Matheu46/moodle-local_h5pthemer/blob/478fed30d2a4af58638f2ddbea3603db1d4d8c5f/amd/src/presets.js)
- [Picker MIT notice provenance](https://github.com/otacke/h5p-theme-picker/blob/2980767a72dc581ae89104d1ff0c837d5d3f210c/LICENSE)

These are provenance records retained from the inspected Backdrop baseline, not
a claim that the entire upstream picker is bundled or that browser/accessibility
certification transfers to this WordPress foundation. SNORDIAN's WordPress plugin
is not the source for the included palettes or runtime.

## Phase 1C-B administration and preview

The feature mapping, color companion behavior and representative preview adapt
the same authoritative Backdrop v0.5.0 `h5p_themer.admin.inc`,
`h5p_themer.preview.inc`, `js/h5p_themer.admin.js`, `js/h5p_themer.preview.js`
and `css/h5p_themer.preview.css`. Those originals are GPL-2.0-or-later; WordPress
adaptations are GPL-3.0-or-later. The representative sample CSS retains its
original GPL-2.0-or-later notice with selectors changed to our unique prefix.
No SNORDIAN implementation is the source of these forms, scripts or styles.

The preview reads the installed official H5P Core variables CSS at a fixed local
path and embeds those bytes in a scriptless administration iframe. That CSS is
not bundled or modified. It is not enqueued into H5P content or the editor.
