# Phase 3E — Export/import administration verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

9 November 2026. **Source implementation and testing complete. Not deployed.** No live database mutation, activation change, runtime-controller change, schema migration, Git operation or live Firefox acceptance.

## Administration controls

Export/import is integrated into the existing `pjh5pwp-themes` administration page. The accessible H5P submenu / Settings fallback, page-load hook, `manage_options` capability, single-site restriction and page-only assets remain in place. No top-level menu, REST/AJAX endpoint, standalone upload route or JavaScript dependency was added.

The overview retains global default, learner publication and all previous Theme operations. Each custom row gains an **Export colors** button. The overview also has **Import theme colors**, a labeled custom-only checkbox selection with **Export selected colors**, and a separate **Export all custom colors** form. Published and unpublished customs are eligible. Built-in/Core rows receive no export button or independent export record. A custom with an ID matching a builtin remains a separate custom identity, as approved earlier.

Single-export buttons reference external forms through the native HTML `form` attribute. Their forms are emitted after the existing visibility form closes, so clicking Export cannot submit visibility and there are no nested forms. Bulk selection and All use separate forms. No export confirmation screen is introduced. Empty custom collections show an explanatory message instead of export forms. All exports retain Phase 3B's 100-record / 1 MiB bounds; oversized All operations require a smaller explicit selection rather than silently omitting records.

The Import page has an ordinary labeled multipart JSON file input, **Validate and preview**, and **Back to theme overview**. It supports the unchanged WordPress colors-only bundles and qualified Backdrop single custom objects through the existing parser, adapter and Phase 3D service. Existing create/edit/duplicate/delete, builtin view/duplicate and profile/role interfaces are preserved. Normal import success follows POST/303/GET back to the overview and reports the created count with an unpublished notice.

## Request, authorization and nonce boundaries

`Admin_Controller::load()` handles transfer operations only on this page's POST hook, after the existing capability/single-site gate and private headers. It unslashes WordPress form fields exactly once. Uploaded file bytes are read separately and never unslashed. Unknown or malformed fields are rejected; empty/truncated POSTs produce an upload/POST-limit message instead of falling through to another mutation.

`Transfer_Admin` handles only `export`, `import_preview` and `import_commit`. Every operation enforces the existing administrator policy; the underlying application services enforce it again. Export/preview nonce actions are operation/source/revision/context-bound through the existing `Admin_Service::nonce_action()` convention. A single-export source is its selected custom ID; the bulk form source is `bulk`, with the user's list then strictly validated by Phase 3C. Commit nonces bind the review signature, original revision and snapshot context. Nonces are not a substitute for capabilities or the review HMAC.

Export checks the displayed revision and full snapshot/catalog context against an authoritative store read, then supplies that exact detached snapshot to the unchanged Phase 3C export service through a read-only reader. This avoids changing the meaning of a stale selection or mixing snapshots if another administrator edits later. The download represents that verified read and makes no revision or publication change.

The plugin's existing lazy admin factory now supplies `Transfer_Admin`; loading definitions and registering the admin hooks still does not open storage or write. No new runtime hook or controller is registered.

## Download delivery

The Phase 3C service supplies the complete JSON and safe filename contract. `Transfer_Download` validates that contract and the filename before sending:

```text
Content-Type: application/json; charset=UTF-8
Content-Disposition: attachment; filename="papijo-h5p-theme-colors-<id>.json"
Cache-Control: private, no-store
Pragma: no-cache
Expires: 0
X-Content-Type-Options: nosniff
Content-Length: <exact JSON bytes>
```

Multiple-record downloads use the unchanged bulk filename. Filenames use only the approved fixed prefix and validated ASCII ID; uploaded names and Theme labels cannot enter headers. Delivery refuses to begin after output has already sent headers. The controller's production terminal callback sends the validated JSON and exits before page rendering; tests inject an explicit terminal seam or capture header/body emission. No export file is written to disk, uploads, media storage or a persistent server cache.

The actual wire JSON, serializer, Backdrop compatibility and sparse inheritance are unchanged. WordPress exports do not contain Density, preferences, publication/default settings or runtime flags.

## Upload handling

`Transfer_Upload` accepts only the one expected PHP upload entry. It checks upload error codes, integer declared size, the genuine `is_uploaded_file()` provenance, and actual bytes read with a hard maximum of **1 MiB plus one byte for overflow detection**. It rejects declared/actual size mismatch, incomplete uploads, oversized data and unverified temporary paths. Filename and client MIME are not trusted to determine content. A JSON-looking executable filename cannot cause execution: nothing is included, evaluated, renamed or moved into public storage; content must pass the existing domain parser.

The production reader only reads PHP's temporary uploaded file. PHP manages its request cleanup. Source tests create and remove private disposable temporary files; these are fixtures, not production upload persistence. There is no paste field or competing input source in the UI. The existing parser still enforces UTF-8, JSON syntax, duplicate member/ID detection, exact format/schema/field rules, recognized preset pins and hex color limits. The UI does not implement a second parser or relaxed compatibility path.

## Preview and secure confirmation

A valid preview shows the number of themes, each ID/name, optional description, exact base preset/version (or Core inheritance), and override count. It explains create-only behavior, unpublished creation and unchanged Density/settings/preferences. Native Backdrop legacy Density is explicitly described as ignored. Names, descriptions, identifiers, messages, attribute values and URLs receive contextual escaping. The preview is a data table, not executable HTML/CSS from the upload.

The approved bounded original-byte transport is implemented **without server-side preview persistence**:

1. The Phase 3D service prepares the file read-only. Preview must match the revision/context from the initiating form. There are no transient, option, profile, session-state or file writes by the review mechanism.
2. `Import_Review` creates a **15-minute** review envelope containing original JSON as canonical Base64, expected revision, snapshot/catalog context, SHA-256 of the exact original bytes, expiry and HMAC-SHA-256. The HMAC uses the server's existing WordPress nonce salt and is explicitly bound to current user ID and logged-in session token, with a versioned domain separator. Neither secret nor session token is sent in the form.
3. The original bounded payload and authenticated envelope travel only in escaped hidden POST fields, never URLs. There is no posted decoded plan or snapshot. Base64 has an explicit maximum of **1,398,104 characters** for 1 MiB of original bytes. Server POST limits must allow this confirmation overhead; a server may impose a lower effective upload limit.
4. Commit requires the explicit confirmation checkbox and its separately bound WordPress nonce. The envelope's exact shape/types, bounded canonical Base64, hashes, HMAC, current user/session and expiry are verified. Changes to payload, revision, context, digest or expiry cannot be relabeled as a valid old preview. Salt/session changes invalidate reviews. Secret unavailability fails closed.
5. From the authenticated original bytes, a **new Phase 3D service instance** prepares a new private plan against current authoritative state. Its revision, context and exact original-byte digest must equal the authenticated preview witnesses. A newer revision, catalog change, collision or context mismatch rejects rather than silently rebasing.
6. That same service instance then commits its own newly issued private plan, retaining all Phase 3D origin checks, repeated validation, write-time collision checks, 4 MiB aggregate limit and one-CAS persistence. The UI never adopts an arbitrary submitted Phase 3B array as persistable data.

This resolves the cross-request boundary while keeping Phase 3D unchanged. The stateless envelope itself is not a one-use storage token: successful import changes the monotonic revision and creates the IDs, so replay is rejected by collision/context/CAS checks. There is no automatically retried commit. Unknown or acknowledged-cache-failure results show dedicated instructions to inspect the library and do not re-display a confirmation action or claim a rollback. Review expiration and user/session binding are stricter than relying on WordPress's ordinary nonce lifetime alone.

## Errors and existing behavior

There are actionable notices for missing/incomplete/oversized uploads, truncated POSTs, invalid/unsupported Theme JSON, unique identifiers, hex/preset/version requirements, collisions, invalid nonce, altered/expired/session-mismatched reviews, stale library context, read failures, whole-import write rejection, aggregate limits, committed cache failure and uncertain outcomes. The Phase 3D generic invalid-input result is retained: the UI explains the relevant format/color/pin/identity requirements instead of introducing another validator or reflecting raw uploaded content. Invalid previews provide no commit form or partial accepted batch.

Error pages escape constant translated messages and do not display SQL, credentials, exception traces, temporary paths or arbitrary client filenames. Existing user preferences, role grants and global default are not fields of transfer forms. Publication remains a separate explicit action after import. The existing storage-only compatibility metadata from Phase 3D remains in effect, independent of playback Density; neither import review nor export changes that behavior.

## Exact changed and new files

Changed existing files:

- `includes/administration.php`: load transfer/UI class definitions without side effects.
- `includes/class-plugin.php`: supply the lazy transfer factory to the existing admin controller; runtime wiring unchanged.
- `includes/Admin/class-admin-controller.php`: page-specific transfer POST dispatch, terminal download, import rendering and success redirect; existing management dispatch remains.
- `includes/Admin/class-admin-view.php`: import navigation, custom-row export buttons and separate overview export forms.
- `includes/Admin/class-admin-messages.php`: safe transfer/security/outcome notices.
- `tests/import/mysql.php`: optional `--admin-ui` inclusion inside the existing validated disposable database; default Phase 3D checks unchanged.

New files:

- `includes/Admin/class-import-review.php`: bounded session-bound HMAC review envelopes.
- `includes/Admin/class-transfer-upload.php`: temporary genuine-upload reader.
- `includes/Admin/class-transfer-admin.php`: nonce/context/request bridge to completed application services.
- `includes/Admin/class-transfer-view.php`: escaped native forms and data preview.
- `includes/Admin/class-transfer-download.php`: validated private attachment delivery.
- `tests/admin-transfer/support.php`: disposable store and form fixtures.
- `tests/admin-transfer/run.php`: isolated UI, controller, review, upload and delivery assertions.
- `tests/admin-transfer/http-fixture.php`: guarded loopback-only PHP request fixture, not a production route.
- `tests/admin-transfer/http-dom.py`: real multipart HTTP checks and form/escaping DOM checks.
- `tests/admin-transfer/mysql-checks.php`: request-handler-to-real-store checks inside the random test database.
- `docs/phase-3e-admin-ui-verification.md`: this report.

No Phase 3B format/parser/Backdrop adapter, Phase 3C export service, Phase 3D import/planning/storage compatibility logic, schema/persistence adapter, runtime controller, JavaScript or protected implementation changed. No package dependencies were added.

## PHP results per version

All suites passed independently on each version. Syntax checks passed for all **15 new/changed PHP files** on each version.

| Suite | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| New transfer administration | 92 PASS | 92 PASS | 92 PASS |
| Phase 3D import doubles | 358 PASS | 358 PASS | 358 PASS |
| Export application | 102 PASS | 102 PASS | 102 PASS |
| Phase 3B transfer | 196 PASS | 196 PASS | 196 PASS |
| Domain with actual Backdrop oracle | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Existing administration | 314 PASS | 314 PASS | 314 PASS |
| Administration coexistence | 44 PASS | 44 PASS | 44 PASS |
| Profiles/role services | 251 PASS | 251 PASS | 251 PASS |
| Role storage | 46 PASS | 46 PASS | 46 PASS |
| Runtime domain | 548 PASS | 548 PASS | 548 PASS |
| Runtime coexistence | 70 PASS | 70 PASS | 70 PASS |
| Attachment | 28 PASS | 28 PASS | 28 PASS |
| Transport/context | 329 PASS | 329 PASS | 329 PASS |
| Foundation compatibility | 590 PASS | 590 PASS | 590 PASS |
| Real disposable MySQL, including new UI checks | 47 PASS | 47 PASS | 47 PASS |
| Real multipart HTTP + form DOM | 51 PASS | 51 PASS | 51 PASS |

Per version: **5,487 isolated PHP assertions + 47 disposable-MySQL assertions = 5,534**, plus **51 HTTP/DOM checks**. The 10 new UI-to-MySQL assertions are included in 47; 919 Backdrop parity assertions are included in 2,315 and 94 UX assertions in 314. Repeated fixture generation for DOM inspection is not counted again as another PHP suite result.

The new isolated checks cover preview no-write semantics/content, all three export controls, unchanged JSON and sparse pins, exact headers/body, malicious filenames/header injection, XSS escaping, explicit confirmation, nonce rejection, altered envelope fields, different user/session, expiry, reconstructed plans across instances, replay collision, missing/incomplete/oversized/mismatched uploads, path provenance, invalid/unsupported JSON, native Backdrop metadata exclusion, existing-ID collision, stale and actual CAS-time races, outcome-specific errors without sensitive details, all existing overview actions, custom-only export forms, actual admin-load dispatch and empty/truncated PHP POST handling.

Tests ran with normal authorized Windows tokens where needed for the previously documented `is_readable()` and local socket restrictions. No production guard was weakened. A fixture brace-placement typo encountered during development was corrected before the final runs; all final syntax and suites pass.

### HTTP and DOM coverage

`http-dom.py` starts PHP's built-in server on an ephemeral **127.0.0.1** port with a random fixture-only access key, and terminates it in `finally`. The fixture accepts only CLI-server/loopback/key-validated access, uses an in-memory store and WordPress API doubles, and never bootstraps a live CMS. Real PHP multipart parsing and the production `is_uploaded_file()` check are exercised. No fixture JSON snapshot is persisted between HTTP requests; the request's write count proves exactly one whole fake-store write at confirmation, while actual persistent effects are tested separately in MySQL.

HTTP checks verify actual private MIME/disposition/nosniff headers, denied unauthorized download, correct multipart upload preview, cross-session and stale-context rejection, confirmation's real 303 redirect and one write, native Backdrop upload, oversized upload and rejection of PHP-looking content as invalid JSON. Static DOM checks parse generated overview/upload/preview HTML, verify no nested forms, correct external-form associations, custom-only exports, multipart encoding, file labels, required confirmation and absence of injected script elements. These are not native WordPress login/session or browser-engine accessibility/Firefox certification.

### Disposable database coverage

The Phase 3D MySQL harness was rerun with `--admin-ui` on MySQL 9.1.0/InnoDB for each PHP version. It reads only literal server credentials, ignores live DB_NAME, creates a random validated `pjh5pwp_import_test_<16 hex>` database and drops only that database in `finally`; every run reported successful removal.

The original 37 real-storage assertions, including four synchronized two-process races, still pass. The 10 added UI checks call the new request boundary with nonces and session-bound review envelopes: preview leaves SQL state exact, forged confirmation cannot write, a new handler instance confirms the whole two-theme batch in one revision, imports are hidden, color re-export matches exactly, competing writes stale the preview, an actual SQL CHECK failure on the final member leaves no partial first member, and profile/role sentinels remain unchanged. Fixture CHECK constraints and temporary files exist only in the disposable harness; no production schema was changed.

### Reproduction

Use each requested PHP binary from the source root:

```text
php -n tests/admin-transfer/run.php
python -B tests/admin-transfer/http-dom.py LOCAL_PATH_OMITTED<VERSION>\php.exe
php tests/import/mysql.php WORDPRESS_ROOT WORDPRESS_ROOT/wp-config.php --admin-ui
```

All earlier regression commands documented in Phase 3D were rerun on each version, including the actual Backdrop source root for parity and installed H5P source argument for transport. MySQL tests use the normal PHP configuration with mysqli; isolated suites use `-n`. No live HTTP site, H5P content, WordPress database or Firefox session was used.

## JavaScript regression results

| Command | Result |
| --- | --- |
| `node tests/admin/browser-behavior.js` | 30 PASS |
| `node tests/runtime/theme-controller.js` | 124 PASS |
| `node tests/runtime/density-controller.js WORDPRESS_ROOT/wp-content/plugins/h5p` | 202 PASS |
| `node tests/runtime/lifecycle-controller.js` | 160 PASS |

**516 assertions passed.** Existing scripts are byte-identical; new transfer UI works with native forms without JavaScript.

## Integrity and live-state limits

The preimplementation source inventory contained **824 files**. Final comparison identified exactly the six changed files and eleven new files listed above, with none removed. JSON format, Backdrop adapter, domain/application services, persistence adapters and existing runtime controllers match the Phase 3D baseline.

Protected before/after inventories match; digests are Base64 SHA-256 over sorted absolute filename/per-file SHA-256 rows:

| Scope | Files | Matching inventory digest |
| --- | --- | --- |
| Authoritative Backdrop source | 155 | `mc7Fafa1mTiuJuwKrTxKbLfM84gyGNH0bS1naKEsX9s=` |
| Installed `acceptance.example.test/wp-content/plugins` | 528 | `4wnKOVEdlXge0LWnS26HijfDQKtTBTms1xoJrnR5aVw=` |
| Installed `acceptance.example.test/wp-admin` | 593 | `dz4InhTVdJ9fm0Ua4AzPHhBE5TH4+JX5F/6tLoARihA=` |
| Installed `acceptance.example.test/wp-includes` | 2,729 | `w6HkRy4C/Xk/+G3Gz/NbEZDTp+lAkSUbYxFudbg2u+E=` |

The installed Papi Jo configuration-bearing bootstrap, official H5P/PHP/JavaScript Core and SNORDIAN files are included in the unchanged plugin inventory. No deployment, activation change, live UI update, runtime flag/controller edit, live Theme/Density preference change, live database write or schema migration occurred. Database writes were confined to random disposable test databases; no live database was selected. The source folder has no usable Git repository, so integrity findings are file-hash comparisons, not a claimed clean Git diff. No Git commit/tag/push, package or release was created.

SNORDIAN ACTIVE and Papi Jo ACTIVE/runtime ENABLED remain supplied baseline assumptions, not independently re-queried live-state PASS claims. Fixture capability/session/coexistence checks do not certify current production activation or preferences, and file hashes do not rule out concurrent external database activity.

## Remaining work for Phase 3F and final stop

No unresolved source implementation blocker remains. The administration interface is not installed or available on the live site yet. Phase 3F requires explicit authorization for a reviewed deployment package, backups/rollback and preservation of the configured runtime bootstrap, production server upload/POST/output settings, actual WordPress login/session/nonce behavior, and manual Firefox/keyboard acceptance. Test native and WordPress files, single/selected/all downloads, import review expiry/cancellation/staleness, unpublished creation, error outcomes and existing Theme/profile/role/runtime/SNORDIAN coexistence. Server POST limits must support the bounded Base64 confirmation body as well as the initial upload.

**STOP before Phase 3F. Do not deploy. Wait for explicit authorization.**
