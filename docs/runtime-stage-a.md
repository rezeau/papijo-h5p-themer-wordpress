# Runtime release architecture — Stage A

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

11 November 2026. Source implemented and verified only. Version 0.1.0. No deployment, entry-point modification, Profile installation change, database access or Git/package/release operation.

## Configuration and diagnostics

Runtime_Policy::configured() is the single default configuration decision. Undefined PJH5PWP_ENABLE_BROWSER_THEME and explicit boolean true enable attachment. Boolean false and all other values disable it conservatively. Theme_Attachment uses this method through its existing injectable callback; callback failure still fails closed. No administrator setting was added.

Configuration is distinct from admission. Compatibility is still freshly evaluated for H5P Core API 1.28, canonical integration capabilities, multisite and every existing SNORDIAN signal. Frontend context, same-origin endpoint, canonical player identity, manifest bounds, tag constraints and downstream transport checks remain unchanged. Enabled attachment hooks may be registered while a plugin is incompatible, but admission suppresses assets/markers/metadata at use.

Diagnostic fields:

- runtime_implemented: true, meaning implementation exists, not that it is active on every page.
- runtime_enabled: the configuration decision only. It can be true while SNORDIAN blocks runtime.
- future_runtime_permitted: existing compatibility admission, unchanged; not a new configuration toggle.
- runtime_suspended and blockers: existing compatibility rejection/reasons, unchanged. Profile suspension continues using these fields plus snordian_active.
- runtime_state: suspended if compatibility blockers exist; otherwise available if configured or disabled if opted out. The separate fields retain the reason when configuration is disabled and compatibility also fails.

The context adapter still reports suspended on an unsupported context. Available describes compatible configured runtime, not guaranteed DOM/library qualification. The configuration opt-out controls attachment, as before; it does not remove the existing private read-only endpoint or change its authorization/schema.

## Exact changes

Production: includes/Compatibility/class-runtime-policy.php; includes/Runtime/class-theme-attachment.php.

Tests updated: tests/worker.php; tests/admin/coexistence.php; tests/runtime/admission.php; tests/runtime/attachment.php; tests/runtime/div-admission.php. These replace historical foundation/default-disabled assumptions and provide the real shared policy to standalone fixtures. Bootstrap tests still assert that registering attachment hooks itself never enqueues assets. Asset queues are captured and checked by the new matrix, rather than silently allowed.

New test: tests/runtime/configuration.php. Documentation: README.md, readme.txt, docs/theme-controller.md, docs/runtime-transport.md and this report. Earlier phase histories remain historical evidence.

Both source and installed plugin entry points are unchanged. The installed temporary true block remains in place. Theme/Density controller JavaScript, readiness CSS, Profile implementation, persistence, roles, presets and color transfer files are unchanged. Stage B automatic Profile installation is not implemented.

## Verification

33 current focused suites passed, reporting 8,016 assertions. This comprises the checkpoint's 32 relevant suites (6,947 assertions), the expanded foundation checks (+68), and the new configuration suite (+1,001). Shared fixture prerequisites are not counted twice.

The new suite runs 88 isolated processes: 11 constant modes crossed with compatible, missing H5P, wrong API, multisite and four SNORDIAN signals. Modes cover undefined, true, false, integers 0/1, strings true/false/empty, null, array and float. It checks truthful diagnostics, real Plugin boot hook registration, default attachment metadata and CSS/JS delivery, context rejection, late collision/tag suppression and restored admission after the fixture conflict ends.

Existing PHP suites passed for compatibility, descriptors, admission, attachment, readiness, transport, identity/permission qualification, DIV admission, Profile preview/suspension, roles, administration, inheritance/domain, storage and export/import. Existing JavaScript suites passed for administration/Profile previews, Theme, Density, lifecycle, initial readiness, returns/BFCache and slow-readiness characterization. The deferred slow-network behavior is deliberately unchanged.

Disposable Chromium static fixtures passed 38 checks over accepted Profile and administration previews. Loopback HTTP fixtures passed 198 private-authentication/header checks and 51 transfer boundary checks. No live WordPress bootstrap/database was used. Source reads of installed H5P/CSS are read-only.

PHP 7.4.33 additionally passed both production syntax checks, the 1,001-assertion configuration suite and the 34-assertion attachment suite (1,035 assertions). PHP 8.4.22 production syntax checks passed. Primary PHP suites used 8.3.18.

The separately documented legacy tests/admin/headless-browser.js overview-form-count assertion is not repaired by this scoped stage and is not claimed as green here. It expects two forms despite the existing Export UI's five. The current preview browser suite verifies administration editing/reset independently.

## Controlled deployment recommendation — not executed

1. Obtain deployment approval. Recheck the approved source hashes and installed inventory; record current activation/configuration and database fingerprints read-only.
2. Back up the two installed production files and their SHA-256/access metadata. Deploy only those files as a coherent pair. Deploy the shared policy first so the consumer cannot call a missing configured() method.
3. Leave the installed entry point and temporary true block untouched during this Stage A deployment. Verify exact bytes, PHP syntax and all other installed/SNORDIAN/configuration files unchanged.
4. Use guarded read-only WordPress verification to confirm the shared configuration/compatibility diagnostics, preserved collision prevention, successful HTTP response and no new errors. Compare database/activation fingerprints without changing preferences.
5. Perform separately authorized Firefox acceptance in existing permitted configurations: normal DIV/internal iframe Theme/Density, Profile preview, active-SNORDIAN notice-only Profile and Themes warning. Do not toggle SNORDIAN as an unapproved deployment side effect.
6. Verify undefined/false/invalid override behavior in an isolated installation. Removing the installed temporary block or changing site configuration is a separate approved operation. Reload pages after code/configuration transitions; no live push cancellation is claimed.

No asset version bump is required for this PHP-only stage because served controller/readiness bytes are unchanged. A later release must still coordinate cache handling and canonical packaging. Default-on makes the already documented slow-network limitation reachable without a manual opt-in; this stage neither fixes nor weakens that fallback.

STOP pending deployment approval. Private hashes/inventory evidence: PRIVATE_EVIDENCE_OMITTED
