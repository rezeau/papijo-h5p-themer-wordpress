# Phase 3C — Custom Theme export application verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

9 November 2026. **Source-only implementation complete.** No deployment, live WordPress integration, UI change, route, HTTP delivery or import persistence.

## Implemented application service

`Transfer\Export_Service` accepts the existing `Storage\Snapshot_Store` interface and an optional trusted authorization callable. Its constructor is inert. `prepare(selection)` authorizes, validates selection, reads one detached authoritative snapshot, rechecks authorization, validates the whole library and invokes the existing Phase 3B `Theme_JSON::export()` service. There is no second serializer or alternate palette resolver.

Load `includes/theme-export.php` explicitly to use it. This entry point loads the unchanged Phase 3B entry point plus the export service; it does not construct a store/service, register hooks or change the plugin bootstrap. Future authorized wiring can supply the existing plugin `theme_store()` adapter. Tests use only a disposable fake store whose write method throws.

Selections are exact PHP array contracts, not HTTP input parsing:

```php
array( 'mode' => 'single', 'id' => 'ocean' )
array( 'mode' => 'selected', 'ids' => array( 'ocean', 'amber' ) )
array( 'mode' => 'all' )
```

Single and selected IDs denote customs only. A built-in token such as `builtin:mint` is invalid; a bare `mint` requires an actual custom definition with that ID. It never synthesizes an export from built-in Mint. Selected lists must be nonempty, zero-based lists of unique valid IDs, with no extra selection fields. Unknown IDs reject the complete operation. All-custom export with no customs gives `empty_export_selection`; it does not manufacture an empty bundle. An all-custom collection over 100 records is rejected, while an explicit smaller selection remains available. No omission, truncation, splitting or automatic renaming occurs.

The whole supplied library must validate, including unselected definitions and its global reference. Malformed/newer/unsupported data are not repaired or skipped. An absent store produces `storage_absent`. Store failures produce a constant `export_storage_unavailable`, without exception details. Errors always return null data, never a partial payload or download metadata.

## Eligibility and portable content

**Published and unpublished custom themes are both eligible**, as approved in Phase 3A. Publication affects personal selection, not administrator export. Neither publication nor the current global default selects or filters export records.

The unchanged Phase 3B projection/serializer preserves IDs, exact names, optional descriptions, sparse hex spelling/case and base references. Schema-1 customs retain Core inheritance through null `base_preset`; schema-2 customs retain the exact known preset ID/version. It does not export resolved inherited expressions or expand sparse overrides. Empty colors remain a JSON object, and absent descriptions remain absent.

Format remains `papijo-h5p-theme-colors`, integer `format_version: 1`, and `themes`. No Density, personal preferences, role grants, global selection, revision, runtime flags, credentials or unrelated configuration fields enter the JSON projection. Exporting a globally selected or hidden custom does not expose the selection/publication state. Changing only stored legacy Density produces an identical download contract.

The Phase 3B serializer, parser, compatibility adapter, detached planner, validator and format documentation are unchanged. This phase adds no Backdrop-native export mode and does not implement import persistence.

## Authorization boundary

By default, the service checks the same management policy as `Admin_Service::allowed()`: `current_user_can('manage_options')` and not multisite. If WordPress capability/multisite functions are unavailable, it denies. Authorization is checked before selection diagnostics or any store read and again after the read, before returning storage diagnostics or palettes. Only literal Boolean true authorizes; callback exceptions fail closed.

The optional callable is trusted dependency injection for test/application wiring, not a request parameter or caller-supplied permission flag. It must represent this management policy in future production wiring. No UI control is relied on as enforcement. The domain serializer remains a low-level pure service; future administrator operations must enter through the application authorization boundary.

The service has no authenticated HTTP request to verify and does not implement a nonce mechanism here. Later delivery integration must add an operation/selection/revision/context-bound WordPress nonce, retain capability enforcement, and reject stale displayed context before invoking delivery. Neither the service nor its result is a signed download ticket or a public endpoint. Its output represents the single snapshot read; it neither changes revision nor claims that later concurrent edits cannot occur.

## Download contract

Success returns `array('data' => <contract>, 'errors' => array())` with:

| Member | Value |
| --- | --- |
| `json` | Complete validated Phase 3B JSON string, including its trailing newline |
| `mime_type` | `application/json` |
| `encoding` | `UTF-8` |
| `filename` | `papijo-h5p-theme-colors-<id>.json` for one resulting custom; `papijo-h5p-theme-colors.json` for several |
| `disposition` | `attachment` |
| `cache_control` | `private, no-store` |
| `nosniff` | Boolean true; later delivery should map this to `X-Content-Type-Options: nosniff` |

Filenames use only a fixed ASCII prefix and an already validated ID, never labels, descriptions, submitted paths or timestamps. Single and selected/all operations that yield the same single ID use the same filename. Bulk filename and JSON are independent of selection/repository iteration order. JSON record/color ordering is provided by Phase 3B.

These are metadata values, not headers sent by this service. It does not call `header()`, print the body, exit, write a file, create a media attachment or persist exported JSON. Tests confirm no output or header changes and execute subsequent assertions after service calls.

## Limits and failure behavior

Phase 3B limits remain authoritative: up to 100 custom records, at most 1,048,576 encoded JSON bytes, fixed color allowlist and existing identifier/text/preset constraints. Selected-count/type/ID/duplicate failures occur before a repository read. All-custom count is checked after the one snapshot read and before serialization. Serialization failures return no partial output.

Resource tests exercise exact 100-record success, 101-record all/selection rejection, a valid explicit subset of a larger library, and the final byte-bound invariant. Phase 3B regression tests additionally exercise accepted byte boundaries, oversized/deep input, malformed UTF-8/JSON and duplicate member detection. No size limit or wire field was changed to implement export.

Service error codes are bounded constants: `forbidden`, `invalid_export_selection`, `duplicate_export_selection`, `export_storage_unavailable`, `storage_absent`, `invalid_theme_library`, `empty_export_selection`, `invalid_theme_count`, `export_unavailable`, plus unchanged Phase 3B export errors such as `missing_custom_theme`. Storage exceptions containing SQL/credentials/paths are caught without reflecting their messages.

No service operation calls `compare_and_swap()`, changes settings, definitions, publication, revision or preferences, or initializes absent storage. It has no profile, role, runtime or plugin-activation dependency. Native Density remains independent.

## Exact source changes

No existing file changed. Four new files:

- `includes/Transfer/class-export-service.php`: read-only application boundary and download preparation.
- `includes/theme-export.php`: opt-in entry point, without bootstrap registration.
- `tests/export/run.php`: isolated application tests with a read-counting, write-forbidden store and capability doubles.
- `docs/phase-3c-export-verification.md`: this report and API documentation.

No dependency installation, schema change, persistence adaptation, UI/controller edit or unrelated refactor was needed.

## PHP verification

All suites passed independently on each requested version:

| Suite | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| New export application | 102 PASS | 102 PASS | 102 PASS |
| Phase 3B transfer | 196 PASS | 196 PASS | 196 PASS |
| Domain with actual Backdrop oracle | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Administration | 314 PASS | 314 PASS | 314 PASS |
| Administration SNORDIAN coexistence | 44 PASS | 44 PASS | 44 PASS |
| Profiles/role services | 251 PASS | 251 PASS | 251 PASS |
| Role storage doubles | 46 PASS | 46 PASS | 46 PASS |
| Runtime domain | 548 PASS | 548 PASS | 548 PASS |
| Runtime coexistence | 70 PASS | 70 PASS | 70 PASS |
| Runtime attachment | 28 PASS | 28 PASS | 28 PASS |
| Transport/context | 329 PASS | 329 PASS | 329 PASS |
| Foundation compatibility, 17 isolated scenarios | 590 PASS | 590 PASS | 590 PASS |

Total **5,037 assertions per version**, including 919 Backdrop parity assertions within the domain count. Administration's 94 focused UX assertions are included in 314, not added twice. Syntax validation of all three new PHP files passed on all three versions.

New tests cover single/selected/all exports, empty collections, unknown IDs, duplicates, built-in exclusion and same-ID customs, hidden/published eligibility, deterministic complete contracts, sparse colors and pins, excluded private fields, invalid/malicious selection shapes, safe names, long valid IDs, generic failures, unavailable/malformed storage, invalid unselected definitions, permission denial before read, authorization exceptions/type confusion/revocation and multisite. Snapshot/profile/role fixture values are unchanged; the fake persistence method throws if invoked.

Reproduce from the source root with each `LOCAL_PATH_OMITTED<VERSION>\php.exe -n`:

```text
tests/export/run.php
tests/transfer/run.php
tests/domain/run.php BACKDROP_REFERENCE_ROOT
tests/storage/run.php
tests/admin/run.php
tests/admin/coexistence.php
tests/profile/run.php
tests/roles/run.php
tests/runtime/run.php
tests/runtime/coexistence.php
tests/runtime/attachment.php
tests/runtime/transport.php WORDPRESS_ROOT/wp-content/plugins/h5p
tests/run.php
```

The full regression runs used an authorized normal Windows token to avoid the previously documented restricted-token `is_readable()` fixture issue. Tests use disposable collaborators and read-only reference/installed H5P source, without live CMS bootstrap or database connections. No real-MySQL, HTTP, live browser or Firefox acceptance was run.

## JavaScript regression verification

| Command | Assertions |
| --- | --- |
| `node tests/admin/browser-behavior.js` | 30 PASS |
| `node tests/runtime/theme-controller.js` | 124 PASS |
| `node tests/runtime/density-controller.js WORDPRESS_ROOT/wp-content/plugins/h5p` | 202 PASS |
| `node tests/runtime/lifecycle-controller.js` | 160 PASS |

**516 assertions passed.** These use simulated DOM/clocks/sessions and read-only Core source, not a live-site certification. No JavaScript changed.

## Source-only integrity

A preimplementation SHA-256 inventory covered all 814 existing WordPress source files. Final comparison found none changed or removed, including all Phase 3B implementation/format files, existing storage and runtime controllers; only the four files listed above were added.

Before/after protected-directory inventories match. Each digest is Base64 SHA-256 of sorted absolute filename plus per-file SHA-256 rows:

| Scope | Files | Matching inventory digest |
| --- | --- | --- |
| Authoritative Backdrop source | 155 | `mc7Fafa1mTiuJuwKrTxKbLfM84gyGNH0bS1naKEsX9s=` |
| Installed `acceptance.example.test/wp-content/plugins` | 528 | `4wnKOVEdlXge0LWnS26HijfDQKtTBTms1xoJrnR5aVw=` |
| Installed `acceptance.example.test/wp-admin` | 593 | `dz4InhTVdJ9fm0Ua4AzPHhBE5TH4+JX5F/6tLoARihA=` |
| Installed `acceptance.example.test/wp-includes` | 2,729 | `w6HkRy4C/Xk/+G3Gz/NbEZDTp+lAkSUbYxFudbg2u+E=` |

The installed Papi Jo bootstrap, official H5P/PHP/JavaScript Core and SNORDIAN files are within the unchanged plugin inventory. No protected component or runtime configuration was edited. No live database connection/write, activation/deactivation operation, preference change or import persistence occurred. SNORDIAN ACTIVE and Papi Jo ACTIVE/runtime ENABLED remain supplied baseline assumptions; they were not independently re-queried. Hashes do not establish live database state or rule out concurrent external changes, and fixture coexistence tests are not a live activation check.

No Git commit, tag, push, package, release or deployment was performed. This supplied source folder has no usable Git repository, so these are file-inventory findings rather than a claimed clean Git diff.

## Remaining work for Phase 3D and stop

No unresolved issue blocks this export application contract. Actual download delivery remains unavailable: future separately authorized administration integration must authenticate requests/nonces, bind selection to displayed context, send the private attachment headers/body, escape diagnostics and add UI/acceptance tests. It must not publish themes or change defaults as part of export.

Phase 3D may build the next authorized import application boundary around detached Phase 3B plans, but current plans remain nonpersistable. Persistence-format adaptation, fresh collision checks, one-CAS commit and uncertain-outcome handling remain separately authorized future work. No density-free storage schema or placeholder legacy Density value is introduced here.

**STOP before Phase 3D. No deployment or administration UI changes. Wait for explicit authorization.**
