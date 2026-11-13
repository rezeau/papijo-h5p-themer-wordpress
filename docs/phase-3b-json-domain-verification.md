# Phase 3B — JSON color domain verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

9 November 2026. **Implemented and verified in source only.** No deployment, live import/export, migration, administration handler or runtime-controller change.

## Approved boundary and implemented contract

The revised Phase 3A colors-only format and the user's subsequent **detached import-plan** decision control this implementation. The earlier candidate definition-schema 3 / aggregate-schema 2 design is **not implemented or selected here**. Current storage schemas, persistence adapters, migration registry and runtime controllers are unchanged. No placeholder Density value is introduced.

Load `includes/theme-transfer.php` explicitly for pure transfer services. It loads existing domain definitions and four transfer classes, but registers no hooks, creates no service instance, opens no database and adds nothing to normal plugin bootstrap. No installed-plugin file was changed.

Every service result uses `data` / `errors`. Failure has null data and constant machine error codes, with no partial accepted records, submitted payload, SQL, paths or exception traces in the result. Host authorization and contextual escaping remain responsibilities of future integration.

### WordPress format

`format = papijo-h5p-theme-colors`, integer `format_version = 1`, and a nonempty `themes` array. Records have exactly required `id`, `label`, `base_preset`, `colors`, and optional `description`. Base is null for sparse Core inheritance or an exact known preset ID/version. Colors are sparse opaque hex overrides. No Density, publication, default, profile, role, runtime or host metadata is accepted in bundle records.

`Theme_JSON::serialize(records)` validates canonical detached PHP records and produces pretty UTF-8 JSON with a trailing newline. Records sort by ASCII ID, color keys sort by string, field order and preset pin order are fixed, empty colors encode as `{}`. Labels, descriptions, whitespace, hex spelling/case and optional-description absence are preserved. No resolved preset expressions are flattened into custom overrides.

`Theme_JSON::export(snapshot, ids)` validates a supplied existing aggregate and projects only selected custom definitions through a whitelist. Legacy schema 1 maps to null base; schema 2 retains its exact preset pin. All-custom export can supply the explicit custom-ID list. Selected built-in tokens or missing IDs reject the entire export. It does not read WordPress options, stream downloads or increment revisions. Changing only a legacy definition's Density has no effect on exported bytes.

`Theme_JSON::parse(json)` decodes/validates either a WordPress bundle or qualified native Backdrop object and returns a list of canonical detached PHP color records. The stored-definition validator does not accept these as current stored definitions.

### Backdrop source compatibility

`Backdrop_Adapter::project()` accepts one native custom object of schema integer 1 or 2 with:

- `_config_name` exactly `h5p_themer.theme.<id>` and an explicit valid matching `id`;
- required label, legacy Density and colors, optional description and Boolean `_config_static`;
- schema 2's exact supported `base_preset`; schema 1 cannot carry a base;
- legacy `density` exactly `small`, `medium` or `large`.

After source-shape validation, the adapter projects only identity/name/description/colors and the supported inheritance reference. Density and both host metadata fields are discarded. A native empty color array is converted to an empty map; nonempty color arrays fail. All canonical color rules then run through the same validator as bundle records. The adapter has no write or preferences dependency and does not load Backdrop PHP into WordPress. Backdrop v0.5.0 remains unchanged.

One bounded JSON decoder serves both formats. Format recognition uses an exact bundle marker or native `_config_name`; a hybrid, unknown marker or unsupported bundle never falls back to permissive native handling. Native input is a one-record batch, not an archive or arbitrary config importer. Native schema-1 support here does not change Backdrop's own schema-2-only create handler.

Unsupported: missing ID/metadata, invalid legacy Density, settings/visibility configs, preset catalog dumps, native arrays, archives, compressed/URL input, unknown fields, versions or pins, live custom parents, Density-bearing bundle records, and nonempty color lists. The illustrative partial record from the task is correctly rejected without inventing its missing ID.

### Detached create-only plan

`Import_Planner::prepare(json, snapshot)` validates the entire source and existing aggregate, checks all custom destinations, and returns:

```text
kind: detached_color_import_plan
directly_persistable: false
expected_revision: unchanged supplied revision
snapshot_context: SHA-256 witness of supplied snapshot and immutable catalog
input_digest: SHA-256 of exact original JSON bytes
records: validated colors-only detached records
collisions: { checked_ids: [...], conflicts: [] }
publication_intents: { custom:<id>: false, ... }
```

On any invalid record or destination collision, data is null; no partial plan or partial success is returned. Successful collision results show all checked IDs with no conflicts. The full-state context/digest are witnesses for future review/concurrency binding, not authorization or a replacement for a future storage CAS.

Existing customs, including identical records, collide; there is no overwrite, merge, skip or automatic rename. A custom ID matching a built-in ID is allowed in the distinct custom namespace, with only a `custom:<id>` unpublished intent. Catalog entries are never mutation targets. A stale true publication token produces a false intent without changing the supplied snapshot. Empty bundles, invalid/broken-default snapshots and exhausted revision fail.

The plan is deliberately neither an updated library snapshot nor a persistable stored theme definition. Tests prove `Schema::validate(plan)`, current `Theme_Validator::definition(record)` and `Library_Changes::create(snapshot, record)` reject it. No revision increment, serialization into the options row, persistence callback or migration follows preparation. Future persistence-format adaptation must be separately authorized and must revalidate context/collisions, materialize the complete next state, check its 4 MiB limit and perform one atomic commit. That limit cannot honestly be certified against an unspecified future stored representation in this phase.

## Exact file changes

Changed existing source file:

- `includes/Theme/class-theme-validator.php`: add colors-only `color_record()` and extract existing name/description/color checks into shared private helpers. Existing definition schema, required legacy field, error ordering, ID/base rules and storage contract remain intact. Domain parity passes unchanged.

New files:

- `includes/theme-transfer.php`: opt-in pure entry point.
- `includes/Transfer/class-json-decoder.php`: bounded decode plus duplicate-member scan.
- `includes/Transfer/class-backdrop-adapter.php`: native source validation/projection.
- `includes/Transfer/class-theme-json.php`: canonical record parsing, bundle serialization and snapshot export projection.
- `includes/Transfer/class-import-planner.php`: detached create-only plans.
- `tests/transfer/run.php`: disposable in-memory tests.
- `docs/phase-3b-json-domain-verification.md`: this verification and service documentation.

No existing test, storage, profile, role, preset, admin, runtime or Phase 3A documentation file changed. The source tree has no usable Git repository here; verification uses content hashes, not a claimed clean Git diff.

## Resource and security verification

Implemented bounds: input/output **1,048,576 bytes**, native decoder depth **16**, bundle **1–100** themes, existing **64-character** ASCII ID limit, **255-byte** label and **4096-byte** description limits. Color keys remain restricted to the existing 33-property allowlist; exact `#RGB` / `#RRGGBB` only. Native pins use the existing immutable catalog `moodle-478fed30d2a4`. No validators are weakened.

JSON syntax is verified first with `json_decode(..., false, 16, JSON_THROW_ON_ERROR)`; invalid UTF-8 is rejected without substitution. A bounded traversal of the already syntax-validated text rejects duplicate decoded member names before any associative domain conversion, including escaped-equivalent keys and duplicates inside nested objects/arrays. No regex JSON grammar, eval, unserialize or user-selected PHP class is used. JSON objects remain plain data objects until shape validation; arrays/maps are distinguished explicitly. Duplicate detection does not confuse equal names in separate objects.

Tests cover malformed/scalar/list roots, trailing data, BOM/invalid UTF-8, wrong types, unknown fields, false/unsupported schema versions, duplicate IDs/members, malicious colors, metadata errors, exact accepted byte/count/string boundaries, oversized/deep input, empty objects, native empty-array adaptation, all-or-nothing valid-first/invalid-last batches, immutable built-ins, custom collisions and a large valid export including encoding overhead. Result errors are constant codes. No source payload is returned on failure.

Density verification covers all three accepted Backdrop legacy values, invalid/missing/type-confused values, exclusion from canonical records and plans, colors-only re-export and invariance under stored legacy Density changes. Independent preferences/role fixtures and supplied settings/defaults remain unchanged. There is no Density setting, default assignment or new Density configuration API in this implementation.

## PHP results

All counts below passed independently on **each** specified PHP version. Syntax checks passed for all seven new/changed PHP files on each version.

| Suite | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| New transfer domain | 196 PASS | 196 PASS | 196 PASS |
| Existing domain with actual Backdrop source oracle | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Administration (includes 94 focused UX checks) | 314 PASS | 314 PASS | 314 PASS |
| Administration SNORDIAN coexistence | 44 PASS | 44 PASS | 44 PASS |
| Profile/role services | 251 PASS | 251 PASS | 251 PASS |
| Role storage doubles | 46 PASS | 46 PASS | 46 PASS |
| Runtime domain | 548 PASS | 548 PASS | 548 PASS |
| Runtime coexistence | 70 PASS | 70 PASS | 70 PASS |
| Attachment | 28 PASS | 28 PASS | 28 PASS |
| Transport/context with read-only installed H5P source | 329 PASS | 329 PASS | 329 PASS |
| Foundation compatibility (17 isolated scenarios) | 590 PASS | 590 PASS | 590 PASS |

Total: **4,935 assertions per PHP version**; the 919 Backdrop parity assertions are included in the 2,315 domain count. The 94 UX checks are included in the administration count, not added again.

Reproduction: choose `LOCAL_PATH_OMITTED<VERSION>\php.exe`, run with `-n` from the source root, and supply these script arguments:

```text
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

Initial restricted-token admin/coexistence runs hit the previously documented Windows `is_readable()` issue; authorized normal-token reruns passed on all versions. An initial transport invocation omitted its required H5P source argument and was corrected; the successful results above use the explicit argument. No fixture/source admission was weakened. A new test initially assumed a maximum valid export would exceed 1 MiB; the observed export remained under the cap, so the test now checks the actual large valid encoding and its limit. Final transfer reruns passed after these test corrections.

No CMS bootstrap, live database test, browser test or HTTP acceptance was performed. The optional real-MySQL suites are outside this source-only verification.

## JavaScript results

| Command | Result |
| --- | --- |
| `node tests/admin/browser-behavior.js` | 30 PASS |
| `node tests/runtime/theme-controller.js` | 124 PASS |
| `node tests/runtime/density-controller.js WORDPRESS_ROOT/wp-content/plugins/h5p` | 202 PASS |
| `node tests/runtime/lifecycle-controller.js` | 160 PASS |

**516 assertions passed**, using simulated DOM/clocks/sessions and read-only H5P source. These establish regression behavior for existing fixtures, not fresh Firefox certification. No JavaScript source changed.

## Source-only integrity and live-state limits

SHA-256 inventories before/after implementation matched for these complete directories. Digests below hash the sorted absolute filename plus per-file SHA-256 inventory; Base64 encodes the aggregate SHA-256:

| Protected scope | Files | Matching before/after inventory digest |
| --- | --- | --- |
| Authoritative Backdrop source root | 155 | `mc7Fafa1mTiuJuwKrTxKbLfM84gyGNH0bS1naKEsX9s=` |
| `WORDPRESS_ROOT/wp-content/plugins` (installed Papi Jo, official H5P/Core, SNORDIAN and others) | 528 | `4wnKOVEdlXge0LWnS26HijfDQKtTBTms1xoJrnR5aVw=` |
| Installed `wp-admin` | 593 | `dz4InhTVdJ9fm0Ua4AzPHhBE5TH4+JX5F/6tLoARihA=` |
| Installed `wp-includes` | 2,729 | `w6HkRy4C/Xk/+G3Gz/NbEZDTp+lAkSUbYxFudbg2u+E=` |

The 807-file WordPress source baseline identified only the validator change and the new files listed above; no file was removed. Storage schemas/adapters and existing runtime controllers match the baseline. The installed configuration-bearing bootstrap is included in the unchanged plugin inventory, so this work did not alter runtime configuration or deploy source changes.

No live database connection or mutation was made; all mutation tests use memory doubles. No activation API was called against a live CMS. SNORDIAN ACTIVE and Papi Jo ACTIVE/runtime ENABLED remain the supplied operating baseline, and this work performs no action that changes them. Live activation state was not independently re-queried: file hashes and fixture coexistence tests are not a live-database activation certification. The same limitation applies to independently certifying concurrent external database activity; this implementation itself makes no database calls. No commit, tag, push, package or release was created.

## Remaining work and final stop

Phase 3C requires separate authorization for export integration: capability/nonce-bound administrator requests, explicit custom selection, download headers and privacy/error handling. The pure codec is not an upload/download endpoint. Later import integration must add authenticated review, exact digest/context binding, fresh collision checks, the separately authorized persistence-format adapter and one qualified atomic commit with honest uncertain-outcome handling. Current plans are intentionally not persistable and do not promise a committed import.

No schema version was chosen or bumped, and no migration or density-free stored-definition support was added. Those remain a separate decision/work item, independent of accepting native Backdrop input. No unresolved issue blocks the approved detached domain contract; live operation remains unavailable by design.

**STOP after Phase 3B. Wait for explicit Phase 3C authorization.**
