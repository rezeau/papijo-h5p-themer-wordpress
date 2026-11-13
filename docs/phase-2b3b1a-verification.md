# Phase 2B-3B-1A — DIV Admission Correction and Test Preparation

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Stopped at the supplied page's DIV-suitability prerequisite.**
No production correction, new regression implementation or deployment was performed.
Papi Jo runtime remains **disabled** and SNORDIAN **active**. The identified page
was inspected read-only; no page, H5P activity or embed mode was changed, and no
replacement was created or selected.

The request requires stopping if the provided page is unsuitable and no safe DIV
acceptance path is available. `https://acceptance.example.test/fitb-pj/` is a normal frontend page,
but its actual main player is a Core-written **internal iframe**, not DIV. It cannot
provide positive DIV runtime acceptance in its current configuration. No alternate
DIV path is assumed under this request's existing-page boundary.

## A. Root cause of the separate DIV rejection

The Phase 2B-3B-1 defect remains present in the unchanged source and installed
`includes/Runtime/class-playback-registry.php:36`.

The official WordPress H5P adapter reads the library's database `embed_types` as
`libraryEmbedTypes`; Core's `loadContent()` copies it into
`content['library']['embedTypes']`. An explicitly present **empty string** can be
valid metadata. Installed Core API 1.28's `determineEmbedType()` retains the requested
type when the library string is empty. For an otherwise valid DIV content record,
the result is DIV. Our registry instead requires the string to match its nonempty
`div`/`iframe` grammar and rejects it before calling Core.

The authoritative preflight memory-only reproduction showed:

| Isolated library metadata | Actual Core result | Current Papi Jo result |
| --- | --- | --- |
| `embedTypes = ''`, requested DIV | DIV | No candidate |
| `embedTypes = 'div'`, otherwise identical | DIV | One candidate |

That earlier probe was reviewed, not counted as a new corrected-code regression
pass in this phase. Current file hashes still match that inspected implementation.

Empty string must not be conflated with a missing key, null, false, array, whitespace,
arbitrary text, an invalid content ID/library identity or a mismatching wrapper.
Even valid metadata cannot bypass frontend context, main-player/browser ownership,
duplicate/nested exclusions or SNORDIAN policy.

## B–C. Minimal correction and exact source-change status

The proposed focused correction remains: accept exactly the present string `''`
alongside the existing valid nonempty grammar, retain string validation and delegate
the result to installed Core. Preserve every identity, wrapper, context, duplicate
and browser-readiness check. Do not treat a stored DIV preference as permission to
ignore a library restricted to iframe.

**This correction was not applied.** The supplied page was checked first so the
explicit stop condition could be honored before implementation/deployment.

Only this report is added to nonprivate repository source:

`docs/phase-2b3b1a-verification.md`

All pre-existing source, tests and historical documentation remain unchanged.
Read-only inspection helpers/evidence are outside deployable source under
`PRIVATE_EVIDENCE_OMITTED`.

## D–E. Checks and test results

| Check | This phase's result |
| --- | --- |
| Current page/activity metadata | Read-only inspection PASS; resolves to iframe |
| Native guarded frontend HTTP | Status 200; one canonical internal iframe; no Papi Jo controller |
| Native disabled-state/admin/profile inspection | 56 checks PASS on PHP 8.4.22 |
| Source/deployed payload reconciliation | All 63 current payload hashes match qualified source |
| Existing backup validation | SQL hash and 53-file backup/restore inventories PASS |
| Database integrity comparison | HOLD: 60 tables match; `wp_options` and `wp_postmeta` drifted during inspection; see investigation below |
| Protected file/configuration comparison | PASS; no changes/additions/removals |
| New corrected-code regression suite | NOT IMPLEMENTED/RUN: suitability stop condition |
| PHP 7.4.33 correction regressions | NOT RUN in this stopped phase |
| PHP 8.3.18 correction regressions | NOT RUN in this stopped phase |
| PHP 8.4.22 correction regressions | NOT RUN; native read-only checks above are distinct |
| JavaScript regressions / source syntax matrix | NOT RERUN; no source correction was made |
| Disposable MySQL regressions | NOT RUN; no persistence change or deployment proceeded |
| Manual Firefox playback/initialization acceptance | NOT PERFORMED in this phase |

Historical source qualification results remain documented in the earlier reports;
they are not presented as new results for a correction that has not been implemented.

The native inspection reports no PHP warnings. Its SQL guard blocked four known
WordPress theme-pattern-cache housekeeping attempts; none executed. There were no
unexpected mutation attempts. The frontend renderer also blocks SQL mutations and
uses H5P's existing aggregation opt-out only within its disposable process to avoid
generating cache files. No live configuration or native embed-resolution behavior
was changed by that test process.

## F–G. Findings for the exact supplied URL

Inspected logical site URL: [https://acceptance.example.test/fitb-pj/](https://acceptance.example.test/fitb-pj/).

| Property | Current evidence |
| --- | --- |
| WordPress identity | Published ordinary `page`, ID 407, slug `fitb-pj` |
| Embedded shortcode | One main activity, content ID 5 |
| Main library | `H5P.AdvancedBlanksPapiJo 1.4`, library ID 85 |
| Stored content preference | `wp_h5p_contents.embed_type = 'div'` |
| Library's allowed metadata | `wp_h5p_libraries.embed_types = 'iframe'` |
| Actual installed Core resolution | **iframe** |
| Current native rendered response | HTTP 200 through the installed WordPress guarded loopback renderer |
| Server-rendered main anchor | `iframe#h5p-iframe-5.h5p-iframe`, content ID 5, `src="about:blank"` |
| Integration metadata | `H5PIntegration.contents['cid-5'].library = 'H5P.AdvancedBlanksPapiJo 1.4'` |
| Other independent main anchors | None in the inspected HTML |
| Nested H5P parameters | Reference `H5P.Image 1.1`; this is subcontent, not proof of a second top-level player |
| SNORDIAN assets | Present |
| Papi Jo controller/manifest | Absent |
| Client initialization | Not observed: this inspection did not execute browser JavaScript |

Core explicitly gives the library restriction precedence over the stored content
preference. Since `iframe` does not offer DIV, `determineEmbedType('div', 'iframe')`
returns `iframe`. A stored preference alone is therefore insufficient evidence of
DIV playback. This page does **not** exercise the empty-library-metadata defect.

The current 200 observation comes from read-only native rendering of the actual
installed WordPress templates/content at `/fitb-pj/`, with logical acceptance.example.test HTTPS
origin. It is not a new direct HTTPS Firefox browser acceptance claim. The initial
iframe markup lacks `h5p-initialized`, as expected before client initialization;
this does not establish whether the player's DOM is initialized in the user's
Firefox session. No inference about runtime child instances is made from the
nested image parameter alone.

**Suitability:** not suitable for positive DIV runtime acceptance. It remains an
internal-iframe candidate for a future separately authorized trial, subject to
actual browser readiness/ownership/layout verification. A future DIV correction
must leave this page as iframe; changing it to DIV would modify native H5P behavior
and exceed this task's content/embed-mode boundaries.

## H–I. Backups, hashes and deployment status

Exact installed Papi Jo directory:

`PROJECT_ROOT/`

Existing Phase 2B-3A recovery directory:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

Its 15,937,230-byte SQL backup still matches SHA-256:

`PRIVATE_VERIFICATION_DIGEST_OMITTED`.

All 53 original plugin backup files and all 53 files in the existing rollback
rehearsal match their recorded hashes. All 63 source/deployed payload paths still
match the Phase 2B-3A deployment manifest. No new targeted replacement backup was
necessary because no file was selected for replacement or deployment.

**Exact deployed file list for this phase: empty.** No plugin file, runtime asset,
fixture or helper was copied into acceptance.example.test. No activation constant was added.

## J–L. Native state, database/configuration and protected integrity

Native checks confirm Papi Jo, official H5P and SNORDIAN remain active. The browser
activation constant remains undefined; runtime policy is suspended and disabled.
The ordinary frontend render contains no Papi Jo controller or attachment metadata.
Papi Jo administration/profile rendering continues to pass the existing read-only
checks. SNORDIAN scripts and configuration remain intact. No browser-executed
playback or visual acceptance is claimed.

The global Theme remains H5P Core (library revision 31), role permission map remains
revision 3 with no nonautomatic grants, and both stored profile records remain
unchanged. H5P content, page content, SNORDIAN settings and activation records match
their pre-inspection state. No schema/configuration/cache operation was issued by
this work.

**Whole-database equality did not pass.** Both snapshots contain 62 tables and 5,855
rows. Sixty tables match fully, but `wp_options` and `wp_postmeta` have changed data
checksums. The only structural-metadata difference is wp_options AUTO_INCREMENT
advancing from 8981 to 8983; column/index/engine definitions are unchanged. This was
treated as an additional stop condition, not normalized away or repaired.

Read-only investigation found:

- The two newly allocated option IDs, 8981/8982, belong to the existing WordPress
  theme-pattern-cache transient and its timeout. Row counts remain unchanged.
- The actual Apache log records separate POST requests to `/wp-admin/admin-ajax.php`
  at 14:56:58, 14:58:58 and 15:00:58 CET during the snapshot interval. This task's
  frontend request used a separate guarded loopback server; it did not issue those
  Apache AJAX requests.
- Page 407 has a current `_edit_lock` timestamp aligned with continuing background
  editor activity. Post contents and H5P content tables did not change.

These observations are consistent with concurrent WordPress editor heartbeat and
theme-cache maintenance. The exact actor and complete row-level history cannot be
proved from the aggregate before-snapshot and request log alone. The direct audit
connection is read-only, and native helper SQL mutations remained blocked. No
attempt was made to revert potentially legitimate concurrent activity or to stop
another browser's heartbeat. **This report does not claim the whole live database
remained unchanged.** A later strict database-comparison window should exclude
concurrent editor/background requests by agreement, without changing site security
or configuration.

Before/after file comparison covers all **8,750 acceptance.example.test files**, **155 authoritative
Backdrop files**, and the inspected Apache/PHP/public-certificate configuration
files. **Zero changed, added or removed files** in those inventories. This includes
our deployed plugin as well as all protected components. No protected code was
modified or used as a repair target.

## M. Rollback readiness

No rollback was needed or executed: no correction or deployment took place.
Existing verified deployment recovery artifacts remain available. If this work is
resumed later, take the requested fresh targeted backup immediately before replacing
an installed file, verify its old/new hashes, and preserve unrelated files/data.
Do not restore the whole database merely to undo a plugin file change.

Runtime rollback remains separate from file rollback. The activation constant alone
does not stop an existing open-tab controller; any future trial must account for
explicit controller shutdown/tab closure, fresh documents and SNORDIAN restoration.
No such runtime operation was performed here.

## N–O. Blocking condition and recommendation

The root cause has not changed materially from preflight, and the proposed correction
needs no protected-code changes. The primary blocker is the supplied acceptance
page: its actual player is iframe, so it cannot establish positive DIV behavior.
The request does not permit changing its embed mode or creating another page.
Neither was done. The observed concurrent database drift is a second verification
hold and is documented above rather than reported as an unchanged-database PASS.

**Do not proceed to Phase 2B-3B-2.** Resolve the existing-page prerequisite or
explicitly revise the stop boundary before continuing implementation/deployment.
A genuine existing ordinary frontend DIV host would need its actual Core-resolved
embed metadata and native readiness checked; no replacement is invented here.
The separate empty-metadata correction remains pending and unimplemented.

Evidence is retained under `PRIVATE_EVIDENCE_OMITTED`, including
`page-state-before/after.json`, `page-inspection.json`, the guarded HTML/native logs,
`native-admin-post.json`, database/file before-and-after snapshots, backup-hash
verification, `unexpected-database-drift.json`, `database-drift-investigation.json`,
`database-drift-details.json` and `final-verification.json`. Private inspection helpers are not
runtime assets and were not deployed.

**STOPPED under the requested page-suitability condition and database-drift hold.
Runtime remains disabled; SNORDIAN remains active. Papi Jo preferences/settings,
activation, configuration and protected files match their baseline. Whole-database
drift is reported above. No Git commit, tag, push or release occurred. Await further
authorization.**
