# Phase 3D — Atomic import persistence verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

9 November 2026. **Source-only implementation and verification complete.** No deployment, live database modification, administration UI, upload route, schema migration or runtime-controller change.

## Storage compatibility decision

The current aggregate schema remains **1**, settings references **2**, publication **1**, and custom stored definitions **1/2**. `Theme_Validator::definition()` still requires legacy `density`. Phase 3B exchange records/plans remain colors-only and not directly persistable. No candidate definition schema 3 or aggregate schema 2 was adopted.

This phase uses the explicit internal compatibility mechanism permitted by the Phase 3D request. Before implementation, the actual existing `Theme_Operations::duplicate()` builtin-copy path was verified to create legacy metadata `density = large`, with zero overrides and an exact preset pin. That established literal has been named `Theme_Operations::LEGACY_DENSITY_DEFAULT` and reused, without changing the existing creation path's behavior.

Safety was checked against actual consumers: `Palette_Resolver` removes built-in legacy Density and never includes a custom definition's Density in its resolved palette; `Profile_Service::effective()` independently resolves Density from the user's profile (`original`, `small`, `medium`, `large`) and role admission. Existing runtime/transport/Density regressions pass. Therefore `large` is inert compatibility metadata in stored theme definitions, not an instruction to apply Large Density or a new Density configuration setting.

`Import_Storage_Compatibility::definition(record)` validates the canonical color record, copies it, then supplies that existing storage-only default. A null base maps to stored definition schema 1 with no base field; a pinned base maps to existing schema 2 with the exact base. Description absence, label, colors and hex spelling are preserved. The resulting definition is validated again through the unchanged stored-definition validator.

This is an explicitly documented use of an established default, not an inferred incoming value or an arbitrary placeholder. It does not inspect source JSON, profile preferences, global Density settings or native H5P controls. Backdrop's legacy value was already validated/discarded by the unchanged adapter. Tests importing all three legacy values produce the same storage-only metadata; re-export never contains that field. Existing definitions, including their historical Density values, remain unchanged.

This decision supersedes the earlier Phase 3A candidate migration prerequisite for this authorized implementation. Phase 3A/3B historical reports are preserved; the colors-only exchange and detached-plan contracts are unchanged.

## Application API and trust boundary

Explicitly load `includes/theme-import.php`. It loads domain/transfer/storage class definitions and the new application classes but registers no hooks, creates no production store and runs no initialization or migration.

```php
$service = new Import_Service($store); // Existing Snapshot_Store interface.
$review = $service->prepare($json);
// A trusted caller checks errors and obtains explicit review/authorization.
$result = $service->commit($review['data']);
```

This is application wiring, not a live endpoint or a complete cross-request review UI. The service defaults to the same `manage_options` plus single-site admission as the existing export/admin services. Missing WordPress functions, non-Boolean admission and authorization exceptions fail closed. A trusted injected authorizer is available for isolated tests; it is not a request field. Admission runs before parse/read, after the read, and immediately before the CAS write. Unauthorized callers receive no palette/storage diagnostics.

`prepare(json)` uses the existing bounded Phase 3B parser and planner, validates the supplied library and creates an unchanged detached plan. It retains **one private, server-owned pending context** consisting of the original bounded JSON and exact issued plan; another prepare invalidates the old pending context. It writes nothing. The returned plan still has `directly_persistable = false`, validated colors-only records, expected revision, snapshot/catalog context, original input digest, collision results and false publication intents.

`commit(plan)` accepts only the exact pending plan issued by that service instance. Arbitrary standalone planner output, an altered field, an object in place of the array, a different instance or a replay is rejected. There is no deserialization of user-supplied PHP and no application of submitted decoded snapshots. The private original contains only validated detached data, so external mutations of the returned array cannot change it.

Every commit attempt consumes pending state, including rejected, failed or uncertain attempts. The service then rereads the authoritative library, reparses its privately retained original bytes using the same parser and recomputes the full planner result. It requires exact equality to the issued plan, including revision, context, digest, all records, collisions and publication intents. Changes to the catalog or any snapshot member cannot silently rebase a review. Only this revalidated server-owned content is materialized for storage.

The private pending context is deliberately bounded to one review and an in-process service lifetime. This implementation does **not** accept a plan copied from an HTTP request into a new service instance as a trusted review ticket. Phase 3E must implement authenticated, expiring, user/session-bound review transport or equivalent server-side staging, preserve the original revision/context/digest, recheck permissions/nonces and avoid automatic re-review/rebasing. Do not serialize/unserialize service instances from clients. No public signing secret, bearer download/import token or HTTP review flow is introduced in this phase.

Success returns `data = {ids, count, revision}`, empty errors and `outcome = committed`. It returns a summary, not a full internal snapshot. Failure has null data, constant error codes and an explicit outcome. Backend exception messages, SQL and credentials are not reflected.

## Create-only and atomic commit mechanism

`Library_Changes::create_many()` validates the complete current state, validates each definition and rejects existing or repeated custom IDs. A shared private append helper preserves `create()` semantics. It adds all definitions and explicit false publication entries to detached PHP data, then finishes once: **one revision increment**, complete final schema validation, no intermediate persistent state. A batch can use the last available revision without consuming one revision per member.

The import application preflights the complete next aggregate with the existing 4 MiB `Snapshot_Codec` limit. It invokes `Snapshot_Store::compare_and_swap()` exactly once. The unchanged production adapter internally rereads, checks expected revision and full transition, then updates the one serialized aggregate row using option name, physical row ID and a **BINARY exact old-value predicate**. It uses its existing qualified private InnoDB/autocommit session, independent of the global WordPress connection. There is no sequence of per-theme saves.

This is InnoDB **single-statement transaction atomicity**. A known SQL failure rolls back that statement and leaves the previous aggregate; no compensating old-snapshot write is needed or performed. A default/publication change, another import or row recreation after the service read cannot overwrite a winner because the store's internal CAS predicate still applies. A service-level stale/context mismatch rejects before writing; a later race rejects at SQL.

All definitions in a valid import are new customs. Existing identical IDs are collisions, never idempotent success. Duplicate bundle IDs are rejected by the unchanged parser and repeated domain IDs by the batch planner. No merge, skip or rename is added. As in approved earlier phases, `custom:mint` and `builtin:mint` are different identities: creating the custom is allowed without modifying the immutable builtin. A forged builtin token is not a custom ID and fails validation.

New/reused custom publication tokens are explicitly false. Existing global settings, definitions and unrelated publication entries retain their exact values. Whole-row storage physically serializes the aggregate again, as required by the existing adapter; it does not semantically edit unrelated theme records. Profiles, role grants, plugin activation and runtime configuration are not dependencies or write targets. A broken global reference blocks import rather than being implicitly repaired by creation.

### Failure and acknowledgement outcomes

| Result | Meaning and handling |
| --- | --- |
| `storage_conflict`, `not_committed` | Stale context, competing creation or known CAS rejection; this operation did not overwrite the winner. Fresh review is required. |
| `import_write_failed`, `not_committed` | Existing adapter established a no-write failure; retain current state. No automatic retry. |
| `import_committed_cache_failed`, `committed` | Database write was acknowledged; later cache work failed. Do not report rollback or retry the old plan. |
| `import_write_unknown`, `unknown` | Lost acknowledgement/reconnect/exception prevents establishing whether the write applied. Inspect authoritative state before further action. |

Unknown acknowledgement does not mean the database partially applied a bundle: the statement remains whole-or-none, while the caller may not know which outcome happened. Generic exceptions from the write boundary are treated conservatively as unknown. There is no blind retry, visibility restoration or rollback to an old snapshot. The adapter and its backend qualification/concurrency guards are unchanged.

## Exact source changes

Changed existing files:

- `includes/Theme/class-theme-operations.php`: name/reuse the existing `large` builtin-copy metadata default; no behavior change to existing creation.
- `includes/Theme/class-library-changes.php`: add pure batch creation and factor its shared append validation; single-create behavior preserved.

New files:

- `includes/Transfer/class-import-storage-compatibility.php`: explicit color-to-existing-storage adaptation.
- `includes/Transfer/class-import-service.php`: authorization, private issued-plan context, revalidation, size check, one CAS and outcome handling.
- `includes/theme-import.php`: opt-in class-definition entry point.
- `tests/import/run.php`: isolated fault/race/compatibility tests using the actual storage adapter with SQL doubles.
- `tests/import/mysql.php`: disposable real-MySQL tests, SQL fault injection and synchronized independent workers.
- `docs/phase-3d-import-persistence-verification.md`: this report/API/compatibility record.

No storage schema, migration registry, persistence adapter, Phase 3B parser/format/Backdrop adapter/planner, export service, existing test, runtime controller, profile/role service or admin UI changed. No extra dependency was installed.

## PHP results per version

Every suite below passed independently on each requested PHP version. All seven new/changed PHP files passed syntax checks on all versions.

| Suite | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| New import persistence doubles | 358 PASS | 358 PASS | 358 PASS |
| Export application | 102 PASS | 102 PASS | 102 PASS |
| Phase 3B transfer domain | 196 PASS | 196 PASS | 196 PASS |
| Domain with actual Backdrop source oracle | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Existing storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Administration | 314 PASS | 314 PASS | 314 PASS |
| Administration coexistence | 44 PASS | 44 PASS | 44 PASS |
| Profile/role services | 251 PASS | 251 PASS | 251 PASS |
| Role storage doubles | 46 PASS | 46 PASS | 46 PASS |
| Runtime domain | 548 PASS | 548 PASS | 548 PASS |
| Runtime coexistence | 70 PASS | 70 PASS | 70 PASS |
| Attachment | 28 PASS | 28 PASS | 28 PASS |
| Transport/context | 329 PASS | 329 PASS | 329 PASS |
| Foundation, 17 isolated scenarios | 590 PASS | 590 PASS | 590 PASS |
| New real disposable MySQL import suite | 37 PASS | 37 PASS | 37 PASS |

Per version: **5,395 isolated assertions plus 37 real-MySQL assertions = 5,432**. The 919 Backdrop parity assertions are included in the domain count, and 94 focused UX assertions are included in administration's 314. Counts are not duplicated. Tests ran under an authorized normal Windows token to avoid the previously documented restricted-token fixture/network limitations.

The new tests cover single/native/multiple imports; all three Backdrop legacy Density values; unpinned Core/pinned inheritance; sparse and absent optional fields; whole-bundle invalid-last rejection; duplicate/custom collisions; separate builtin namespace; unpublished creation; unchanged definitions/defaults/profile/role fixtures; malformed/oversized/deep input; modified plans and replay/cross-instance rejection; capability/multisite/type/exception denial; revocation immediately before CAS; revision/context staleness; two write-boundary competing-import scenarios; final aggregate overflow; one remaining revision; known write failure, lost acknowledgement and acknowledged cache failure. All failure results have no partial snapshot/record output.

### Real disposable database verification

The new harness uses actual WordPress `wpdb`, the unchanged `WordPress_Snapshot_Store` and private connections against **MySQL 9.1.0 / InnoDB**. It reads only literal DB_HOST/DB_USER/DB_PASSWORD from the supplied credential source, never executes `wp-config.php`, ignores its DB_NAME and initially connects without selecting a database. It creates a random `pjh5pwp_import_test_<16 hex>` database, checks the name before every bootstrap/cleanup, defines test DB_NAME only to that name and drops only that database in `finally`. Worker credentials are passed on private stdin, not printed or placed in command arguments. Each final run confirmed successful cleanup.

Actual checks include:

- Two-member import with one revision and hidden publication, exact existing definition/default preservation and colors-only re-export.
- A native Backdrop import demonstrating incoming `small` cannot select stored legacy metadata.
- Existing-ID and stale-revision rejection without replacing state.
- A disposable table CHECK constraint depending on the final imported ID rejects the **single complete UPDATE**; InnoDB statement rollback leaves the first member absent and the old aggregate exact. This is the meaningful final-member failure case in a one-row design: there is no second independent theme write to fail midway. The constraint exists only in the random test database and is removed there; it is not a production schema/migration change.
- A real read-only private session rejects the UPDATE and preserves the whole old row.
- Four two-process races per PHP version, alternating overlapping/disjoint IDs. Both processes pause inside the adapter **after its internal read and immediately before the actual SQL UPDATE**. Each issues one write attempt; exactly one full two-member batch wins, one revision advances, and the loser's tail is absent with a known conflict result.
- A separate ambient transaction updates a profile sentinel while import commits through its private session. Rolling back the ambient work restores the sentinel without undoing the import, proving the importer did not commit another caller's transaction.
- Unrelated role/profile sentinel values, existing global/default theme and exact serialized row bytes remain correct through failures/races.

Initial restricted-token connection could not reach MySQL. The harness cleanup was adjusted to avoid masking failed-connect errors when closing an already closed handle; normal-token execution then passed. No live site bootstrap or live database selection occurred. Four successful disposable runs were performed during development/final verification (one development run plus one final run per requested PHP version), each removing its own database. The failed initial attempt created none.

Reproduction from the source root, selecting the requested PHP binary:

```text
php -n tests/import/run.php
php tests/import/mysql.php WORDPRESS_ROOT WORDPRESS_ROOT/wp-config.php
```

The MySQL command requires the normal PHP configuration with mysqli enabled and authorization to create/drop only disposable databases. All earlier regression commands documented in Phase 3C were rerun unchanged on each version, including read-only installed H5P source arguments for transport and the actual Backdrop root for parity.

## JavaScript regression results

| Command | Result |
| --- | --- |
| `node tests/admin/browser-behavior.js` | 30 PASS |
| `node tests/runtime/theme-controller.js` | 124 PASS |
| `node tests/runtime/density-controller.js WORDPRESS_ROOT/wp-content/plugins/h5p` | 202 PASS |
| `node tests/runtime/lifecycle-controller.js` | 160 PASS |

**516 assertions passed**, using simulated DOM/clocks/sessions and read-only native source. No JavaScript source changed; this is not fresh Firefox/live-browser acceptance.

## Integrity and live-state limits

A preimplementation SHA-256 inventory covered **818 existing WordPress source files**. Final comparison found only the two documented existing-source changes and six new files, with no removals. Existing storage/format/adapter/export/runtime files remain byte-identical to the Phase 3C baseline. This source folder has no usable Git repository; findings are file-inventory comparisons, not a claimed clean Git diff.

Protected before/after inventories match. Digests are Base64 SHA-256 over sorted absolute filename/per-file SHA-256 rows:

| Scope | Files | Matching inventory digest |
| --- | --- | --- |
| Authoritative Backdrop source | 155 | `mc7Fafa1mTiuJuwKrTxKbLfM84gyGNH0bS1naKEsX9s=` |
| Installed `acceptance.example.test/wp-content/plugins` | 528 | `4wnKOVEdlXge0LWnS26HijfDQKtTBTms1xoJrnR5aVw=` |
| Installed `acceptance.example.test/wp-admin` | 593 | `dz4InhTVdJ9fm0Ua4AzPHhBE5TH4+JX5F/6tLoARihA=` |
| Installed `acceptance.example.test/wp-includes` | 2,729 | `w6HkRy4C/Xk/+G3Gz/NbEZDTp+lAkSUbYxFudbg2u+E=` |

The installed configuration-bearing Papi Jo bootstrap, official H5P/PHP/JavaScript Core and SNORDIAN are within the unchanged plugin inventory. No deployment, runtime configuration edit, live admin UI, plugin activation change, live preference/content update or production schema migration was performed. Database writes were confined to verified random disposable databases; no live database was selected or modified by these tests.

SNORDIAN ACTIVE and Papi Jo ACTIVE/runtime ENABLED remain supplied baseline assumptions, **not re-queried live-state PASS claims**. No live database-state snapshot or independent check of concurrent external activity was performed. File integrity and coexistence fixtures cannot certify current activation/preferences. No commit, tag, push, package, release or Git mutation occurred.

## Remaining work for Phase 3E and final stop

The approved source-level atomic persistence contract is implemented with existing storage and no migration. No unresolved blocker remains for synchronous prepare/commit through one server-owned service instance. There is still no live import/upload or export/download UI.

Phase 3E needs separate authorization to implement authenticated administrative review/commit transport, session/user-bound expiring server review context, nonces, stale-form handling, privacy/escaping, bounded upload/paste handling and explicit results for committed/unknown outcomes. It must preserve the plan provenance boundary across requests rather than trusting posted arrays. Later deployment and Firefox acceptance remain separate; uncertain writes require authoritative inspection and must not trigger blind retry or old-snapshot rollback.

**STOP before Phase 3E. No deployment or live administration integration. Wait for explicit authorization.**
