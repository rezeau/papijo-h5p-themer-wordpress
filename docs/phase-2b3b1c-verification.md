# Phase 2B-3B-1C — Targeted DIV Admission Correction

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Implementation, automated qualification and controlled one-file
deployment COMPLETE.** Papi Jo runtime remains **disabled** and SNORDIAN **active**.
Manual Firefox runtime acceptance remains pending. No content, embed preference,
activation, WordPress configuration or protected component was changed.

## A–B. Reproduction and minimal correction

The root cause matches Phase 2B-3B-1 exactly. The official H5P WordPress adapter
projects the library database `embed_types` into `libraryEmbedTypes`; installed
Core's `loadContent()` preserves it as `content['library']['embedTypes']`.
An explicitly present empty string is legitimate: `determineEmbedType('div', '')`
returns DIV. Papi Jo rejected it using a nonempty-only grammar before invoking Core.

The new isolated regression first failed on the unchanged implementation at
“Empty and nonempty valid metadata admit only the proved DIV”. After the correction,
the same regression passes against actual installed H5P Core API 1.28 definitions.
No Core service, live content renderer or live activation is used by this test.

The correction permits the exact empty string **only when the validated content
embed preference is exactly `div`**. It still requires a string and still delegates
effective resolution to Core, then verifies the exact canonical wrapper. Missing,
null, false, arrays, whitespace and malformed declarations remain rejected.
An iframe-only library with a stored DIV preference still resolves to iframe; a
DIV wrapper conflicting with that result is rejected. Empty iframe declarations
retain their prior exclusion because this correction is limited to DIV.

All identity/library/version checks, duplicate detection, frontend context,
canonical iframe checks, browser root ownership/readiness, nested-instance exclusion,
SNORDIAN admission and transport authentication/cache policy remain unchanged.
No selector fallback, forced Theme class, native Density override or arbitrary
H5P-like element admission was added.

## C–D. Exact source changes and focused tests

One pre-existing source file modified:

- `includes/Runtime/class-playback-registry.php`: narrowly permit present empty
  library metadata for requested DIV, retaining the original nonempty grammar.

Two files added:

- `tests/runtime/div-admission.php`: **51 focused assertions** using the real
  installed Core resolver and existing isolated CMS/policy seams.
- `docs/phase-2b3b1c-verification.md`: this report.

No other source/test/documentation files were intentionally changed. Historical
reports and existing work are preserved. The Phase 2B-3B-1B investigation report
does **not** exist; that investigation ended before writing it. This phase uses
fresh metadata plus the actual source and earlier completed reports.

The focused suite covers explicit empty and nonempty DIV metadata; missing/null/
false/array/whitespace/invalid metadata; Core resolution conflicting with wrapper;
invalid IDs and repeated IDs; external/editor/unsupported embed values; admin,
AJAX, preview, customization, embed and feed contexts; combined/nested wrappers;
valid internal iframe and excluded empty iframe; foreign iframe wrappers;
default-disabled attachment; initial and late SNORDIAN suspension; and absence of
actionable suspended appearance. Existing browser suites additionally retain
main-instance/root, nested-child and ambiguous-owner exclusion checks.

## E–G. Automated results

| Check | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| New DIV admission suite | 51 PASS | 51 PASS | 51 PASS |
| Existing complete PHP/HTTP/disposable-MySQL matrix | 5,400 PASS | 5,400 PASS | 5,400 PASS |
| Total reported assertions | **5,451 PASS** | **5,451 PASS** | **5,451 PASS** |
| PHP syntax | 88 files PASS | 88 files PASS | 88 files PASS |

All suites passed before deployment. Counts exclude repeated prerequisite assertions
executed internally when one fixture includes another fixture. Existing matrix
coverage includes foundation/installed H5P source, Backdrop/domain parity, theme/role/
profile storage and lifecycle, administration/coexistence, descriptor/serializer,
transport/context/security, admission/attachment, cache/identity qualification and
native WordPress cookie/session/CORS loopback fixtures.

Node JavaScript: administration **30**, Theme **124**, Density/Core source **202**,
lifecycle **160**: **516 assertions PASS**. All **7 JavaScript files** pass syntax.
The existing headless Edge static administration regression adds **24 assertions
PASS**. It operates on disposable loopback fixtures, not live WordPress playback.

Disposable MySQL 9.1.0: role **69**, profile **56**, theme storage **49** assertions
per PHP version, included above. All nine suite databases were removed. Tests use
guarded random disposable database names, never the live database; credentials are
read as literals, not by executing wp-config. No new persistence behavior or schema
was introduced.

## H–I. Deployment, hashes and recovery

Installed path was rediscovered/verified as:

`PROJECT_ROOT/`

**Exact deployed file list: one file**

`includes/Runtime/class-playback-registry.php`

| Hash | SHA-256 |
| --- | --- |
| Original installed / fresh backup | `PRIVATE_VERIFICATION_DIGEST_OMITTED` |
| Corrected source / installed | `PRIVATE_VERIFICATION_DIGEST_OMITTED` |

Fresh targeted backup:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

Backup bytes were compared to the original installed file before deployment and
reverified afterward. Existing Phase 2B-3A SQL recovery hash, 53-file backup and
53-file restore rehearsal remain verified. No new whole-database backup was needed
for this file-only change.

The deployment script revalidated all pre-existing site file hashes immediately
before copying, verified resolved destination containment, checked every regression
exit code and confirmed the default-disabled native preflight. It staged and
atomically replaced only this PHP file; no maintenance marker or unrelated installed
file was needed. Source/destination hash and installed PHP syntax passed. Copy/syntax
failure handling could restore the hash-verified original automatically.

No tests, helper, report or development fixture was deployed. No activation cycle,
runtime definition, version bump, cache flush or protected-code change occurred.

**Rollback readiness:** restore only the targeted backup to the same resolved path,
verify the original hash, rerun syntax and read-only native checks. Preserve the
database and all other files. No rollback was needed or executed because the final
correction/deployment checks passed.

## J. Database and configuration comparisons

SELECT-only pre/post inventories cover all 62 live base tables. Independent hashes
also cover all `pjh5pwp_*` options, full theme/role/profile state, active-plugin list,
WordPress role capabilities and SNORDIAN settings/version.

**All Papi Jo-owned state, preferences, role permissions, global default, activation
and SNORDIAN configuration match.** Library revision remains 31 with global H5P
Core; role revision remains 3 with no nonautomatic grants; administrator preferences
remain Site default/Original at revision 1. All profile rows, users/usermeta,
WordPress post contents and every H5P table match their pre-deployment data hashes,
including H5P contents, libraries, cachedasset records and user state/results.

No live INSERT/UPDATE/DELETE, schema operation or configuration write was issued.
Read-only native bootstrap/rendering applies a query guard throughout; known Core
theme-pattern housekeeping attempts are blocked, not allowed through to mutate data.

**Unrelated drift is recorded separately:** `wp_postmeta` alone changed its table
checksum during this interval. Apache independently logged admin-AJAX POSTs at
16:28:29 and 16:30:29 CET. This is consistent with the previously observed background
editor heartbeat, but the exact changed rows/actor cannot be conclusively attributed
from aggregate snapshots and request paths. It is not attributed automatically to
this deployment, normalized away, repaired or rolled back. No Papi Jo/H5P content
state changed, so the request's owned-state/content blocker was not triggered.
This report does **not** claim byte-identical whole-database state.

Configuration file hashes remain unchanged. No database migration was required or
performed; the correction contains no persistence or schema code.

## K. Protected-file integrity

Complete site inventory: **8,750 files**, with exactly one changed path, our deployed
registry file. The other **8,749 site files** match, with **zero additions/removals**.
This includes all WordPress Core, official H5P, H5P PHP/JS Core, SNORDIAN, uploads,
wp-config and the other 62 Papi Jo payload files.

All **155 Backdrop reference files** match with zero changes/additions/removals.
Inspected Apache/PHP/public-certificate configuration hashes also match. Thus every
protected component is unchanged; only the authorized Papi Jo correction was deployed.

## L–M. Live read-only smoke checks and disabled runtime

Native installed WordPress inspection: **43 pre-deployment and 56 post-deployment
checks PASS**, including registered Papi Jo administration, five built-in previews,
role rendering, own-Profile selectors and preservation of existing profile records.
No PHP warnings or unexpected SQL mutation attempts remain in final smoke checks.

Four existing ordinary frontend pages (400, 403, 407, 409), including `/aaa/` and
`/fitb-pj/`, return 200 through the guarded installed WordPress HTTP renderer.
H5PIntegration/main-library script/style metadata matches pre/post, normalizing
only SNORDIAN's existing per-request buster. SNORDIAN assets remain present and no
Papi Jo controller/manifest is attached. The real installed admin-ajax dispatch
returns nonactionable `snordian_active` suspension with private/no-store, CDN/proxy
no-store, Cookie/Origin variation and no CORS/ETag/Last-Modified disclosure.

The renderer runs outside the web root, blocks SQL writes and disables aggregation
only in its disposable process to prevent H5P asset-cache generation. It does not
change live aggregation settings, execute player JavaScript or prove visual playback.
Positive DIV admission was verified in isolated fixtures, not by enabling runtime.

The runtime constant remains undefined. SNORDIAN remains active through all four
native detector signals, and shared policy remains suspended. Neither source nor
deployed bootstrap was edited to activate runtime. The endpoint's authentication,
current-viewer identity and cache protections are unchanged.

## N–O. Limits and readiness for Phase 2B-3B-2

The targeted correction is complete and deployed; no automated correction/deployment
blocker remains. Manual Firefox initialization and enabled-runtime acceptance are
still pending. This phase did not use Windows browser automation or bypass local
HTTPS/browser restrictions.

- Activity 3, AdvancedTextPapiJo 1.3, on `/aaa/` is a technical DIV admission fixture,
  **not visual Theme color acceptance**. Its actual browser initialization remains
  unverified; native ownership/readiness checks must not be forced or weakened.
- `/fitb-pj/`, activity 5 AdvancedBlanksPapiJo 1.4, remains an internal iframe fixture.
  Its library restriction still overrides the stored DIV preference.
- Activities 10/13 remain additional Core-resolved DIV candidates. No new host was
  created and no existing page, library metadata or embed preference was edited.
- The inherited aggregate runtime diagnostic flags and open-tab rollback limitations
  described in the preflight remain unchanged. Disabling the attachment constant
  alone would not stop an already running controller.

Recommend a separately authorized Phase 2B-3B-2 with an explicit activation/tab/
SNORDIAN sequence and manual Firefox acceptance scope. This report grants no runtime
activation, SNORDIAN deactivation or preference-write permission.

Private evidence is under `PRIVATE_EVIDENCE_OMITTED`: baseline/final inventories,
`regressions.json`, `javascript.json`, `headless.json`, `deployment-plan.json`, targeted
backup, `deployment.json`, native pre/post JSON, HTTP captures, owned-state hashes,
`database-comparison.json`, `database-investigation.json`, `file-integrity.json` and
`final-verification.json`.

**STOP. Runtime remains disabled, SNORDIAN active, protected files and owned
configuration/data unchanged. Unrelated postmeta drift is disclosed above. No Git
commit, tag, push or release occurred. Await explicit authorization for the next phase.**
