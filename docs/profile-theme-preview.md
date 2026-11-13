# Optional Profile Theme preview — source verification

Current status, 11 November 2026: the implemented feature set and Stages A/B/C have been deployed locally and manually accepted in Firefox (maintainer confirmation). See [README](../README.md) and [installation](installation.md) for current behavior. Phase-specific statements below retain their checkpoint context. In particular, runtime is now default-on and compatibility-gated, activation installs Profile storage, and active SNORDIAN suppresses own-Profile selectors/preview as well as Papi Jo playback; the older isolated-preview behavior is superseded. The slow-network readiness limitation remains unresolved.

11 November 2026. Implemented and verified in source only. Version remains 0.1.0. Deployment and Firefox acceptance require separate approval.

## Implementation

The own-Profile Theme selector now has a native, collapsed-by-default disclosure titled “Preview selected Theme”. One compact scriptless iframe displays representative heading/text, action-colored spans, a selected answer and correct/incorrect feedback. Sample elements are noninteractive. No real H5P activity or runtime JavaScript is loaded.

The existing administration Theme_Preview retains its manage_options/single-site guard. Its isolated document construction is shared through document(), with a compact mode and a shared property/value allowlist. Existing administration controls, full samples, Core comparison, live color editing and reset are retained. Compact CSS applies only to the Profile sample document.

Profile_Controller keeps its own-user/edit capability/role/storage guards and its validation/save lifecycle. It captures the library state once and passes that same state and eligible Profile catalog to Profile_Preview. The presenter resolves permitted colors in memory, without another Profile/library read. Static assets are scoped to profile.php and are inert if authorized preview markup is absent; enqueueing does not query roles or storage again.

Core has an empty override set over the official local Core variables; Daylight remains a distinct preset. All five built-ins preserve moodle-478fed30d2a4. Published custom themes use the existing resolver, including preset inheritance and sparse legacy definitions. An unavailable saved choice retains keep_saved and previews the effective default. Unavailable library/global state follows existing Core fallback behavior.

An unpublished global default contributes only effective supported colors under the generic Site default label. Its private name, identifier, description and definition are never serialized. Other unpublished themes are absent from the payload. A Profile administrator receives the same eligible Profile catalog rather than the administration management catalog.

Selection changes replace the complete override stylesheet, update text/title/status, and never change the selector, Density, focus or persistence. The iframe load handler reads the current selection, covering rapid changes before load. Form reset waits for native reset completion and respects cancellation. Server-rendered validation redisplay initializes from the actual redisplayed selection, without claiming it has been saved.

The iframe keeps sandbox="allow-same-origin" without allow-scripts, and the existing CSP: default-src 'none'; style-src 'unsafe-inline'; form-action 'none'; base-uri 'none'. JSON is hex-encoded and attribute-escaped; labels use escaped text/textContent. PHP and JavaScript enforce the 33 supported color properties and safe hex values plus the exact supported preset expressions. No requests, AJAX actions, new form fields, browser storage or save operations are introduced.

SNORDIAN's existing detection supplies an explanatory note when it controls playback. The isolated sample remains available without changing runtime suspension or foreign hooks/settings. Missing/invalid preview assets or inaccessible frame DOM leave the preference selectors available and provide a preview-unavailable message.

Density, frontend readiness, authorization lifetimes, role policy, Profile storage, schema, presets and color Export/Import are unchanged. The anonymous/browser-local preferences project was not touched. No production deviation from the approved design.

## Exact production scope

Modified:

- includes/Admin/class-theme-preview.php
- includes/Profile/class-profile-controller.php
- includes/profiles.php
- assets/css/preview.css

Added:

- includes/Profile/class-profile-preview.php
- assets/js/profile-preview.js
- assets/css/profile-preview.css

Regression changes: tests/profile/run.php updates the hook inventory; tests/admin/coexistence.php adds real four-signal Profile preview coexistence checks. New tests: tests/profile/preview.php, preview-behavior.js and preview-browser.js. This document records implementation and acceptance scope.

## Automated verification

28 suites passed, reporting 6,400 assertions (shared fixture prerequisites are not counted twice):

| Suite | Assertions |
| --- | ---: |
| PHP foundation / 17 isolated compatibility scenarios | 918 |
| PHP domain | 1,396 |
| PHP storage doubles | 204 |
| PHP administration including focused UX | 331 |
| PHP administration/Profile SNORDIAN coexistence / four signals | 52 |
| PHP Profile/roles lifecycle | 251 |
| New PHP Profile preview | 104 |
| PHP role storage | 46 |
| PHP transfer domain | 196 |
| PHP export | 102 |
| PHP import | 358 |
| PHP transfer administration | 92 |
| PHP runtime descriptors | 548 |
| PHP runtime coexistence | 70 |
| PHP runtime admission | 73 |
| PHP attachment | 34 |
| PHP readiness markup | 24 |
| PHP DIV admission | 51 |
| PHP transport/context | 329 |
| PHP runtime qualification | 185 |
| Existing admin JavaScript behavior | 30 |
| New Profile preview JavaScript | 50 |
| Existing Theme JavaScript | 124 |
| Existing Density JavaScript | 202 |
| Existing lifecycle JavaScript | 329 |
| Existing readiness JavaScript | 203 |
| Existing return/BFCache JavaScript | 60 |
| New isolated Chromium Profile and administration fixture checks | 38 |

PHP primary checks use PHP 8.3.18 with -n. Source-only transport, qualification, DIV and Density checks read official H5P source under WORDPRESS_ROOT/wp-content/plugins/h5p; they do not boot WordPress or access its database. Normal Windows permissions avoid the previously documented restricted-token is_readable() issue.

Additional PHP 7.4.33 verification: syntax checks passed for all four changed/new PHP production files, and Profile preview (104) plus administration (331) suites passed: 435 additional assertions. JavaScript syntax checks passed.

The new browser command, from repository root:

```powershell
node tests/profile/preview-browser.js PHP_CLI WORDPRESS_ROOT/wp-content/plugins/h5p/h5p-php-library/styles/h5p-theme-variables.css
```

This starts a disposable headless Chromium profile and loopback static-fixture server. It verifies actual colors, scriptless/noninteractive markup, exact sandbox/CSP, keyboard disclosure, no focus movement, rapid changes/reset, no interaction requests, private-global metadata absence, narrow layout and 200% CSS zoom. It also checks the existing administration full preview, Core comparison, 33 editable fields, live color edits and reset. It never opens a acceptance.example.test page. The browser profile/server are cleaned up. CSS zoom and static fixtures do not replace Firefox desktop zoom or actual WordPress/screen-reader acceptance.

New PHP/DOM coverage includes denied users and other-user editing, one Profile/library read, exact presets, Core versus Daylight, inherited and legacy customs, publication filtering, unavailable state/keep_saved, validation redisplay, unsafe CSS/labels/metadata, original form fields, read-only behavior, independent Density, missing assets, late iframe load and canceled reset. Four real SNORDIAN detection fixtures confirm the note/sample while runtime suspension and foreign configuration/hooks remain intact.

## Focused Firefox acceptance after approved deployment

1. Using an existing permitted account, open its own Profile. Verify the disclosure is directly beneath Theme, initially collapsed, and opens/closes using keyboard Enter/Space. Check a denied account and an administrator viewing another user's Profile: no personal preview or preview payload.
2. Open the sample and cycle Core, Daylight and the other four presets. Check both the sample and its descriptive title. Confirm no editable palette or interactive sample controls appear and focus remains at the selector.
3. Choose a published inherited custom theme and switch rapidly between it, Core and presets, including while the frame is first loading. Confirm latest-selection colors and no stale overrides. Repeat on a validation-error redisplay and with native form reset if available.
4. With existing test data for an unpublished global default, confirm Site default shows its colors under a generic label. Inspect page source/preview metadata for absence of the private name, identifier and definition. For an unavailable saved preference, confirm keep_saved is retained and its sample uses the effective fallback.
5. Clear the Network panel after initial page load. Change Theme repeatedly and open/close the disclosure: no new preview requests or save operations. Leave without Update Profile and reopen to confirm the preference was not saved. Separately confirm the normal Update Profile path still saves permitted choices.
6. Change Density: sample colors/size stay independent. Verify the existing administration preview, color editing, Core comparison and reset remain normal.
7. Where SNORDIAN is already active in an authorized test configuration, confirm the explanatory note and isolated sample; actual playback remains under SNORDIAN. Do not toggle plugin activation as part of this source-only task.
8. Test a narrow window and Firefox 200% zoom: no horizontal clipping or unreachable sample content; normal vertical frame scrolling is acceptable. Check screen-reader disclosure/title/status announcements, keyboard focus and contrast of the surrounding Profile controls. With JavaScript disabled, the initial static sample and preference form remain usable; unsaved selections cannot update the static sample.

## Limits and stop

The sample is representative, not a guarantee for authored H5P library layouts or custom palette contrast. It intentionally does not preview Density. Client data is a page-load snapshot; later publication/global changes require reload, and save-time authorization remains fresh. Large eligible catalogs increase initial HTML size linearly. Preview failure never blocks preference submission.

Source/installed hashes and scoped changes are recorded privately under PRIVATE_EVIDENCE_OMITTED No installed files, database values, settings, plugin activation or versions were changed. No commit, push, release or deployment. STOP pending deployment approval.


> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.
