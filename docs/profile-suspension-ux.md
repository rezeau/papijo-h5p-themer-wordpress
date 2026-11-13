# Profile preferences during SNORDIAN suspension

Current status, 11 November 2026: the implemented feature set and Stages A/B/C have been deployed locally and manually accepted in Firefox (maintainer confirmation). See [README](../README.md) and [installation](installation.md) for current behavior. Phase-specific statements below retain their checkpoint context. In particular, runtime is now default-on and compatibility-gated, activation installs Profile storage, and active SNORDIAN suppresses own-Profile selectors/preview as well as Papi Jo playback; the older isolated-preview behavior is superseded. The slow-network readiness limitation remains unresolved.

11 November 2026. Source implementation and verification only. Version 0.1.0; deployment approval and Firefox acceptance are pending. This supersedes the earlier Profile-preview behavior that allowed the isolated sample to remain visible while SNORDIAN controlled playback.

## Production scope and behavior

Only two production files changed:

- includes/class-plugin.php passes the existing Runtime_Policy::evaluate callback into Profile_Controller. It is the same policy object/callback used by runtime admission; no independent SNORDIAN detector or configuration was added.
- includes/Profile/class-profile-controller.php adds a small server-side suspension check to rendering, validation and deferred saving.

The condition is exactly: runtime_suspended is true AND blockers includes snordian_active. Other suspension causes alone do not trigger this notice. The callback is evaluated fresh when needed; it is not cached as permission or persistence authority.

Existing own-user, edit capability, role and multisite rendering guards run first. For an eligible user during SNORDIAN suspension, the controller renders the Personal H5P preferences heading and this paragraph, then returns before reading Profile/library storage or building preview data:

> Personal H5P appearance settings are currently unavailable because another H5P theme plugin is controlling activity appearance. Your previously saved Theme and Density preferences are preserved.

No Theme selector, Density selector, preview disclosure/iframe, H5P nonce or preview metadata is rendered. Static preview assets remain inert when their markup is absent. No CSS/JavaScript hiding was introduced.

When the condition is false, the existing controls, publication filtering, read-only preview and Profile save lifecycle continue unchanged. Theme administration and its compatibility warning remain unchanged and accessible to authorized administrators.

## Data integrity and stale forms

An unrelated WordPress Profile update has no H5P payload and follows the existing no-op path. Missing fields never become defaults or clear instructions.

An old open form can still contain H5P fields after SNORDIAN becomes active. While suspended, the controller ignores those fields without adding H5P validation errors, so unrelated core Profile updates remain possible. This applies even to invalid/forged H5P fields: they do not reach the preference service or storage.

Suspension is checked again before a queued save reaches save_request(). A newly suspended request is consumed without a preference write; it cannot be replayed after suspension ends. The normal unsuspended path retains existing capability checks, nonce/revision binding, fresh role checks, locking and compare-and-swap persistence.

Profile_Service, Profile_Store, Role_Service, schemas and all runtime components are byte-unchanged. Saved Theme, Density, inert legacy flags and revision are not reset or rewritten. A fresh Profile request after suspension ends displays the stored values again. An open page is not updated in real time; reload is needed to display the current server-side state.

No database, settings, activation, catalog identifier, Export/Import, anonymous/browser-local project or deferred slow-network logic was changed. No production deviation from the approved behavior.

## Tests

New tests/profile/suspension.php: 51 assertions covering heading/notice-only rendering, no nonce/preview/private payload, no extra Profile/library/palette reads, unrelated updates, stale submissions, suspension between validation and save, consumed pending requests, unchanged saved bytes/revision, restoration on reload, other blockers, administrator access, role/capability/other-user/multisite restrictions, and the normal nonce/CAS save path.

Updated tests/admin/coexistence.php: 60 assertions across four isolated real detection signals. It exercises the actual Profile controller registered by Plugin::boot(), rather than constructing a separate detector/controller. Authorized Theme administration remains usable; existing foreign options/hooks and runtime suspension remain unchanged. Existing foundation tests retain the Themes-page warning placement checks.

Primary verification: 30 suites, 6,698 reported assertions, excluding repeated shared-fixture prerequisites:

- PHP: foundation (918), domain (1,396), storage (204), administration (331), coexistence (60), Profile lifecycle (251), preview (104), suspension (51), role storage (46), transfer (196), export (102), import (358), transfer administration (92), runtime descriptors (548), runtime coexistence (70), admission (73), attachment (34), readiness (24), DIV admission (51), transport (329), qualification (185).
- JavaScript: administration (30), Profile preview (50), Theme (124), Density (202), lifecycle (329), readiness (203), return/BFCache (60), existing slow-readiness characterization (239).
- Isolated Chromium Profile and administration fixtures: 38 assertions, including accepted preview colors, disclosure, reset, no requests, sandbox/CSP, keyboard/focus and narrow/zoom behavior.

Additional PHP 7.4.33 verification: syntax checks for both modified production files, suspension (51) and preview (104): 155 assertions. PHP 8.3 syntax checks also passed.

These tests use isolated doubles/static fixtures. H5P source/CSS is read only for existing contracts and browser fixtures. No live WordPress bootstrap, database access, activation changes or deployment occurred. All 82 installed plugin files and 12 SNORDIAN files match the pre-task hashes. Source checks confirm no other production file changed.

## Firefox acceptance after approved deployment

1. With SNORDIAN inactive, verify a permitted user's original Theme/Density choices and the accepted collapsed preview remain normal. Check an existing denied account and an administrator viewing another user: existing restrictions remain.
2. In an explicitly authorized SNORDIAN-active test configuration, reload the permitted user's own Profile. Verify the heading and exact notice, with neither selector, preview nor H5P nonce/data present in page source. The same applies to an administrator's own preferences.
3. Update an unrelated Profile field while the controls are absent. Confirm the update succeeds and H5P preferences are not reset. An old open unsuspended form submitted during suspension must also leave H5P preferences unchanged.
4. After an authorized end to SNORDIAN suspension, reload Profile: the exact saved Theme and Density reappear, with the accepted preview working. Confirm a normal explicit Profile save still works.
5. Verify authorized Papi Jo Theme administration remains accessible and shows its existing compatibility warning while SNORDIAN is active. Actual H5P playback must retain existing coexistence behavior. Check keyboard/screen-reader reading of the heading/notice.

Activation changes in this checklist are future manual/operator actions requiring the appropriate authorization; none were performed during this source-only task.

STOP after source verification. No deployment, commit, push, release or version bump. Evidence: PRIVATE_EVIDENCE_OMITTED


> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.
