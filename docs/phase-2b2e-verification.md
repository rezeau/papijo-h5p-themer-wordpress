# Phase 2B-2E — Cache and Lifecycle Qualification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Automated source qualification PASS.** Four narrowly scoped
controller corrections were made. Runtime attachment remains **disabled by
default**. No deployment, live WordPress bootstrap, live database write,
configuration/activation change, Git commit/tag/push or release occurred.

## A. Executive summary and evidence levels

The existing private transport, server-derived identity, independent Theme/Density
resolution, root ownership and SNORDIAN admission boundaries survived qualification.
The controller now coalesces lifecycle requests, waits for visible DOM readiness,
renews correctly when a scan encounters expiry, and rebinds observers after a
same-Document element-tree rewrite. No PHP production code, endpoint schema,
permission policy, storage, preset, generated CSS or public preference data changed.

- **Automated PASS:** all PHP suites on three requested versions, all JavaScript
  runners, source syntax, loopback HTTP authentication/cache headers and nine
  guarded disposable MySQL runs.
- **Source inspection:** WordPress composition, official H5P rendering/dependency
  paths, native Density/resize semantics, Backdrop serializer/Density reference,
  static asset delivery and protected-file hashes.
- **Simulated behavior:** viewer/role/publication transitions use real Papi Jo
  services with in-memory collaborators; controller lifecycle uses deterministic
  DOM/CSSOM, fetch, event and clock doubles. Extracted Core callbacks run in a VM.
- **Real-browser runtime behavior NOT YET TESTED:** no Firefox acceptance, live
  WordPress players, real cache/CDN session isolation, BFCache, CSS timing or native
  library layout trial. The existing headless Edge test passes for static
  administration previews only; it is not runtime acceptance.

Read all seven requested project documents and the implementation/tests. Consulted
read-only Backdrop v0.5.0 serializer/native-Density source associated with supplied
commit `d9b53d706a94dac322b89885bf668145ab3803c4`. Neither source directory has Git
metadata; the supplied Backdrop commit identity cannot be independently proved
from a local checkout. Preservation uses before/after file hashes.

## B. Exact changed/new source files

Eight pre-existing files modified:

| File | Change |
| --- | --- |
| `assets/js/theme-controller.js` | Lifecycle request coalescing, visible/readiness guard, unified expiry renewal/requalification, document-element observer rebinding |
| `tests/runtime/theme-controller.js` | Preserve all 124 checks; model DOM readiness and bounded lifecycle timing |
| `tests/runtime/density-controller.js` | Preserve all 202 checks; await coalesced lifecycle requests |
| `docs/theme-controller.md` | Current refresh, failure, rewrite and recovery contract |
| `docs/runtime-transport.md` | Link current qualification; endpoint contract unchanged |
| `docs/architecture.md` | Current checkpoint and remaining deployment gate |
| `README.md` | Current source checkpoint and new regression commands |
| `readme.txt` | Current source-only checkpoint |

Four files added:

| New file | Purpose |
| --- | --- |
| `tests/runtime/lifecycle-controller.js` | 160 lifecycle/cache-reuse/cleanup regression assertions |
| `tests/runtime/qualification.php` | 185 sequential identity/policy/cache-isolation assertions |
| `docs/phase-2b2e-deploy-files.txt` | Exact 63-file future runtime/licensing deployment allowlist |
| `docs/phase-2b2e-verification.md` | This report and proposed controlled deployment plan |

No removals. Production PHP, existing PHP tests, presets, CSS, administration JS,
historical reports and all other existing source retain their baseline bytes.
The source snapshot contains 129 pre-existing files; 121 are unchanged, eight
intentionally modified, and four added. Existing uncommitted/source work is
preserved against the snapshot taken before edits.

Local evidence is separate from deployable source under `.local-acceptance/`:
`phase-2b2e-before.json`, `phase-2b2e-initial-tests.json`,
`phase-2b2e-run.ps1`, `phase-2b2e-tests.json`, `phase-2b2e-javascript.json`,
`phase-2b2e-headless.json`, and `phase-2b2e-verification.json`.

## C. Cache isolation

| Surface | Finding and evidence |
| --- | --- |
| Frontend HTML | Registry returns wrapper bytes unchanged. Enabled attachment emits only version, endpoint and public main-content ID/embed/library metadata. Sequential identity/role/global/publication tests produce identical script tags without preference reads. |
| H5PIntegration | This plugin only reads main-library identity; it never writes personal Theme/Density into integration settings. |
| H5P library scripts/styles and dependency caches | No Papi Jo runtime asset action mutates these lists. Footer attachment stays outside the Core dependency pipeline. Inspected installed H5P `add_assets()`/`alter_assets()` ordering. |
| Shared JS/CSS | One static controller; no viewer-specific file bytes/URLs. No runtime CSS file or personalized stylesheet is generated. Preview/admin CSS is static. |
| Appearance endpoint | Every valid request resolves the current server viewer. Exact request schema rejects a user ID or preferences. Success, suspension and error responses are private/no-store with CDN/proxy no-store, Cookie/Origin variation, no CORS grant and no ETag/Last-Modified reuse. HTTP fixtures exercise real headers and signed WordPress cookie/session validation. |
| Browser descriptor | Correlated current-generation data in memory only, request-start lifetime at most 30 seconds; no localStorage/sessionStorage, service worker or persistent preference authority. |

One reused endpoint/attachment and identical correlation input were tested through
anonymous → User A → User B → logout → User A. A and B retain distinct personal
Theme and Density. Anonymous/unauthorized viewers receive current global Theme
and Original Density. Admission suspension yields null Theme/Density and empty
declarations. No preference state leaks across these disposable identities.

This qualifies **Papi Jo Theme/Density contributions**, not arbitrary WordPress
page contents. Installed official H5P can preload content-user-state independently
in `get_content_settings()`. This phase does not alter or certify that feature's
site-cache policy. Actual proxy/CDN rules, optimizers and cache reuse require
future browser/site checks; headers cannot force a misconfigured proxy to comply.

## D. Authentication and permission transitions

Fresh responses reflect anonymous/authenticated/logout/A-to-B changes, role
assignment and role-grant revocation/restoration, personal Theme-only/Density-only
changes, global default changes, custom publication/unpublication/republication,
custom deletion and invalidation. Existing real Profile/Role/Publication services
remain the authority. Saved selections survive loss of permission and become
effective again after a grant. Automatic Administrator access is retained.

Malformed whole-library aggregates retain the existing Core fallback, independently
of saved Density. Deleted/unpublished personal customs fall back through the current
global selection. No fixture read changes stored preferences, revision or grants.
The composition still calls WordPress `get_current_user_id()` for each request;
client identity fields are rejected before the identity callback.

Tabs do not need to reload: visible qualified players renew successful admission
at expiry and revalidate on lifecycle events. Explicit `refresh()` is immediate
for a known account transition. There is no pushed remote logout, grant or
SNORDIAN notification; changes are discovered by the next successful request.
A response cannot identify an unannounced cookie change after its own server-side
authentication. Timer/event delivery limits are recorded in section O.

## E. Descriptor freshness and failure policy

Initial fetch waits for visible DOM readiness. Pageshow/BFCache restoration, focus
and visibility return immediately invalidate old admission and restore safe owned
styling. Automatic events share a trailing timer with a one-second minimum between
request starts; repeated events do not extend that deadline. Explicit refresh
bypasses this cooldown, cancels its timer and aborts older requests. Hidden,
pagehide and stop cancel the trailing timer too. A 300-event burst is tested.

Each POST uses same-origin credentials/mode/referrer, no-store, redirect rejection,
the fixed public header and a cryptographic 128-bit request ID. Generation and
correlation checks defeat stale, aborted and out-of-order replies, including old
admission after a newer SNORDIAN suspension. A 10-second timeout covers fetching
and body consumption. MIME, status, redirect, size, JSON and exact schema checks
reject unverifiable responses. Slow network time consumes the 30-second lifetime;
already expired responses never apply.

The controller distinguishes these inputs:

| Input | Policy |
| --- | --- |
| Valid actionable descriptor | Apply independent validated colors/native Density to each qualified root within its deadline |
| Explicit Core | Release owned colors; retain the descriptor's independently authorized Density |
| Valid suspension | Release still-owned colors and Density; no actionable instruction and no automatic renewal |
| Failed/unverifiable response | Release still-owned styling to its captured originals, without synthesizing a Core/default/Density choice; no automatic network retry |

One successful descriptor schedules one expiry callback. A scan that discovers
expiry takes the same path, avoiding timer/scan races. Expiry requalifies players
before renewal, including removals whose observer callback has not run. Renew only
while visible qualified records remain. Successful leases can renew again at their
deadlines; there is no independent polling interval. Failure/suspension/hidden or
absent-player expiry stops renewal. Recovery needs a lifecycle event or explicit
refresh. DOM initialization retries are seven finite delays, not network retries.

## F. Player lifecycle and cleanup

Existing and new tests cover early controller/late H5P/API/style readiness,
distinct simultaneous DIV/internal-iframe players, duplicate IDs/instances,
nested children, foreign/unqualified roots, detach/reinsert, replacement roots,
iframe reload/document replacement, same-Document documentElement replacement,
DOM replacement, pageshow/pagehide, simulated BFCache, hidden return and teardown.

The actual unique root/document/anchor/container/API/main-instance/library tuple
remains mandatory. A new root cannot borrow an old instance. Nested/child and
ambiguous players receive no independent application. Cross-origin, srcdoc,
sandbox, external/URL embeds, framed parent, editors and administration remain
excluded. No qualification check was weakened.

Observers bind to the current element tree even when Document identity survives
`document.open()`. Rewrites disconnect old observers and replace load listeners.
Twenty repeated iframe rewrites retain exactly two active observers (parent and
current internal document) and one frame load listener. Overall bound remains one
parent plus at most one realm per candidate: 65 observers for a 64-player manifest.
Mutation scans coalesce at 25 ms. Stop disconnects observers, removes document/frame
and lifecycle listeners, clears every timer, aborts requests and releases records.
Weak ownership guards do not strongly retain discarded roots. The fixture's own
observer history is diagnostic test data, not a production retention structure.

## G–H. Theme and Density ownership

Existing suites retain all checks for initial/repeated application, switching,
explicit Core, global defaults, invalid CSS/property rejection, original inline
values/priorities, publication fallback, suspension, safe detached cleanup and
third-party modifications. Unknown properties/expressions and arbitrary CSS remain
rejected. Only allowed color properties on qualified roots are written.

Density uses only native `h5p-small`, `h5p-medium`, `h5p-large` tokens. Original
captures/restores the actual initialized native set at the first actual override,
including Large, another valid token, no token or multiple tokens. Unrelated
classes survive. Matching pre-existing states are never claimed. Native class
conflicts relinquish Density; property conflicts relinquish only that color.
Subsequent cleanup does not overwrite foreign values/priorities or class sets.

Resize targets only the proved main instance after a real connected-root class
change. No unrelated player/global resize, detached-root resize or no-op loop is
introduced. Synchronous resize errors safely relinquish Density independently.
The unchanged source-contract fixture executes installed Core dispatcher/iframe
resize fragments, including wrapper restoration and fullscreen guard.

Final Theme-only responses preserve the independent Density choice and final
Density-only responses preserve Theme. **Refresh still temporarily restores both
dimensions before fetching**, so unchanged Density can resize twice and unchanged
Theme can briefly revert. This is an existing conservative authorization policy,
not a claim of uninterrupted styling. The identity-free protocol cannot know in
advance whether a refresh is only cosmetic or a permission/account revocation.
Removing this transient restoration needs a separately reviewed failure/authority
policy; it was not silently changed during hardening. Browser flicker/layout impact
remains a runtime-trial acceptance item.

## I. SNORDIAN coexistence

Existing suites use all four real detector signals and fresh shared policy;
attachment checks include late suspension between queuing and tag output. New
sequential tests simulate admitted → suspended → admitted; browser fixtures reject
stale admission after a newer suspension and restore both still-owned dimensions.
Foreign/SNORDIAN-owned style values, priorities and native class changes survive.
Suspended responses never contain actionable colors or Density and skip preference
resolution. Administration/profile functionality remains covered independently.

SNORDIAN remains as supplied on acceptance.example.test; no activation record was queried or
changed by live bootstrap, no callback removed, and no foreign source/configuration
was written. Detector transitions are isolated doubles, not live deactivation.

## J. Defects and smallest corrections

| Reproduction | Root cause | Correction and regression |
| --- | --- | --- |
| Emit focus/pageshow/visibility repeatedly while a request is pending or a descriptor is active | Every resume started an immediate refresh, repeatedly aborting/refetching and restoring | Revoke immediately and share one bounded trailing lifecycle request; test bursts, pending stale replies, explicit-refresh bypass, hide/stop cancellation |
| Emit focus/pageshow before DOM readiness, or start an initially hidden page then emit pageshow | Resume set active without checking visibility/readiness | Wait for visible non-loading document; test no early network/observer work and later successful readiness |
| Run a load/mutation scan at the 30-second deadline before the expiry callback | Scan invalidated and canceled the only expiry timer without renewing | Shared expiry path restores/requalifies/renews; test exact deadline race, slow response lifetime, and player removal before observer delivery |
| Replace an internal/parent documentElement but retain the Document object | Observer registry keyed only on Document and kept observing the discarded element | Track the observed element, disconnect/rebind on replacement and replace load listener; test both realms and repeated frame churn |

The initial JavaScript run correctly exposed existing tests' assumption of immediate
lifecycle fetches; their clocks now await the coalesced request and retain all prior
assertions. No protected correction was required. No unrelated refactoring occurred.

## K–M. Automated results

| PHP suite | 7.4.33 | 8.3.18 | 8.4.22 |
| --- | --- | --- | --- |
| Foundation / installed H5P source (18 scenarios) | 621 PASS | 621 PASS | 621 PASS |
| Domain / Backdrop parity (includes 919 parity checks) | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Theme storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Administration / UX / role forms | 314 PASS | 314 PASS | 314 PASS |
| Administration SNORDIAN coexistence | 44 PASS | 44 PASS | 44 PASS |
| Role/profile lifecycle | 251 PASS | 251 PASS | 251 PASS |
| Role storage doubles | 46 PASS | 46 PASS | 46 PASS |
| Descriptor / serializer | 548 PASS | 548 PASS | 548 PASS |
| Runtime coexistence (8 scenarios) | 70 PASS | 70 PASS | 70 PASS |
| Transport / context / security | 329 PASS | 329 PASS | 329 PASS |
| Shared policy admission / attachment (10 scenarios) | 73 PASS | 73 PASS | 73 PASS |
| Default-disabled / explicit attachment | 28 PASS | 28 PASS | 28 PASS |
| New sequential cache / identity qualification | 185 PASS | 185 PASS | 185 PASS |
| Loopback HTTP / cookies / sessions / CORS | 198 PASS | 198 PASS | 198 PASS |
| Role MySQL | 69 PASS | 69 PASS | 69 PASS |
| Profile MySQL | 56 PASS | 56 PASS | 56 PASS |
| Theme storage MySQL | 49 PASS | 49 PASS | 49 PASS |
| PHP syntax | 87 files PASS | 87 files PASS | 87 files PASS |

**5,400 PHP assertions per version**, 16,200 across the three versions. Counts
exclude duplicate prerequisite assertions executed when a fixture includes an
earlier fixture. The Windows restricted token reproduced the documented PHP
`is_readable()` false negative; normal-process verification passed without changing
production detection or weakening fixtures. Initial failure evidence is retained.

Node **22.23.1**: administration doubles **30**, existing Theme **124**, existing
Density/Core source **202**, new lifecycle **160**: **516 assertions PASS**. Syntax
checks cover all **7 JavaScript files**. All controller suites ran after the final
production edit. The existing static headless Edge administration runner adds
**24 browser assertions PASS** (separate from the 516 simulated assertions).

HTTP checks use disposable loopback PHP servers with installed WordPress cookie,
session-token and CORS definitions and memory-only identity/storage. They never
bootstrap WordPress or use production cookies. MySQL **9.1.0** tests create only
guarded random `pjh5pwp_test_*` databases, exercise real wpdb/concurrency behavior,
and remove them in finally cleanup. Role/profile suites additionally verify absence.
Only literal host/user/password credentials are read from wp-config; its DB_NAME
is ignored. The live database is never selected and wp-config is never executed.
No new persistence/schema behavior was introduced.

## N. Protected-file integrity

Before/after SHA-256 snapshots cover the entire acceptance.example.test filesystem and authoritative
Backdrop reference: **8,740 WordPress-tree files plus 155 Backdrop files unchanged**,
with no additions/removals. This includes WordPress Core, official H5P, H5P PHP/JS
Core, SNORDIAN, deployed Papi Jo files and wp-config. No activation-constant definition
was found in wp-config, and that file's baseline bytes remain unchanged.

The audit is a filesystem comparison, not a live database checksum. Database
isolation follows from guarded disposable names and no live bootstrap/selection.
The JSON evidence records per-file hashes and source changed/added/removed lists.

## O. Remaining limitations and risks

- Firefox/runtime/site-cache acceptance is outstanding. Static administration
  Edge checks do not establish H5P runtime behavior, CSSOM serialization, CSP,
  optimizer behavior, cross-library layout, fullscreen or iframe sizing.
- Descriptor checks bound authority to 30 seconds from request start. Browser
  throttling, OS sleep or blocked execution can delay restoration callbacks; this
  is not a hard wall-clock guarantee about visible pixels. Known transitions can
  invoke immediate refresh; remote changes have no push signal.
- Failure/suspension stops automatic renewal. Later recovery, or reinsertion after
  an absent-player expiry, needs a lifecycle signal or explicit refresh. Late H5P
  initialization beyond the finite retry window needs a DOM/load/refresh signal.
- Refresh transiently restores both dimensions. Continuous preservation of the
  unchanged dimension is not qualified; trial must assess flicker and resize impact.
- Changed/ambiguous owners may retain earlier overrides because even restoration
  would mutate an unproved owner. Such roots are quarantined. Exact same-value
  foreign writes and changes undone between scans cannot be distinguished.
- Native Density effects and asynchronous resize callbacks depend on each library.
  Source/VM tests cannot guarantee actual layout or catch asynchronous library errors.
- Aggregate policy diagnostics still report the inherited false/not_implemented
  runtime flags. This phase did not add diagnostic features. Coherent enabled-state
  reporting is required before production activation.
- WordPress 6.5 is a compatibility target, not a tested installation matrix.
  External embeds, duplicate IDs, uncertain wrappers and split-origin setups stay
  outside supported runtime scope.

## P. Deployment readiness and controlled plan — NOT EXECUTED

**Ready for a separately authorized deployment while SNORDIAN remains active and
runtime remains disabled. Not approved for production runtime activation.** There
are no failing automated checks or required protected-code corrections. Firefox
acceptance, live cache/session checks, transient refresh behavior and aggregate
diagnostic refinement remain runtime-activation gates.

### Stage 1: deploy dormant source with SNORDIAN active

1. Obtain explicit authorization for this deployment only. Preserve current
   SNORDIAN activation/configuration and the absent/false runtime gate. Take new
   read-only compatibility/activation/configuration observations in that stage;
   do not assume this report proves future live state.
2. Back up the complete currently deployed Papi Jo directory, its file hashes and
   permissions, configuration/activation inventory, and a recoverable database
   backup covering the plugin's theme/role/profile data and normal site state.
   Record unrelated settings/roles and table engines/revisions. No migration,
   initialization, learner save or settings save is required for file deployment.
3. Snapshot protected trees again. Compare the reviewed source hashes to this
   phase's final evidence; stop on unexplained differences. Re-run native PHP,
   JS, loopback HTTP and disposable SQL commands using the documented versions.
4. Deploy exactly the **63 paths** in
   [phase-2b2e-deploy-files.txt](phase-2b2e-deploy-files.txt), relative to this source
   root, into `PROJECT_ROOT`.
   Use the complete reviewed runtime/licensing set so transport/controller classes
   cannot be mixed across phases. Exclude tests, `.local-acceptance`, attachments,
   repository docs and the deployment manifest itself. Do not copy neighboring
   WordPress/H5P/SNORDIAN/Backdrop files. Do not delete unexpected deployed files
   without separately reviewing ownership and backup.
5. Verify each destination hash against source, protected trees unchanged and
   database/configuration/activation safety observations unchanged. No schema
   upgrade or activation cycle is planned. Keep `PJH5PWP_ENABLE_BROWSER_THEME`
   absent or strictly false. Admission alone never enables attachment.
6. With SNORDIAN still active, check ordinary frontend pages and qualified
   same-origin appearance requests: no controller attachment by default, and
   suspended descriptors with null Theme/Density and empty declarations. Verify
   SNORDIAN styling, read-only administration/profile rendering and unrelated
   assets remain intact. Any test preference writes need their own approved
   disposable users/data; normal rendering requires no such write.
7. Invalidate affected page and static asset caches as authorized. The plugin's
   asset version is still 0.1.0, so replacing bytes alone does not guarantee a
   browser/CDN downloads them. Verify the served controller hash before any later
   trial. Archive source/deployment/protected hashes and results.

Rollback: restore the backed-up Papi Jo file set, verify its hashes and protected
integrity, and purge affected page/static references. Keep SNORDIAN active and
runtime gate disabled. Do not restore a live database just for a file-only rollback;
use the backup only for an explicitly established data problem with approval.
If a later trial has already enabled runtime, disabling asset delivery alone does
not suspend the endpoint or revoke open-page descriptors. End the trial by the
authorized SNORDIAN restoration, remove/disable the gate, refresh/close trial tabs,
invalidate caches and allow no earlier valid controller generation to persist.

### Stage 2: separately authorized Firefox runtime trial

This is a different authorization: temporary SNORDIAN deactivation/reactivation,
temporary true boolean runtime definition before bootstrap, configuration changes
needed for that definition, named disposable users/content and any preference
writes must all be explicitly in scope. None is authorized or performed now.
Prefer a disposable site clone for this trial. Never modify protected code.

Firefox manual acceptance checklist for that future stage:

- Verify installed versions, backup/rollback readiness, served asset hashes,
  endpoint private/no-store headers and current SNORDIAN/gate status.
- Exercise representative native-Theme, Papi Jo and fixed-color libraries in DIV
  and canonical internal frames; inspect computed approved variables, native
  scaling/classes, actual layout, targeted resize, iframe height and fullscreen.
- Exercise anonymous, authorized A/B, unauthorized, login/logout without tab reload,
  role revoke/grant, personal Theme/Density, global change and custom publication/
  deletion. Reuse cached HTML between accounts; inspect HTML/H5PIntegration/assets
  for absent Papi Jo preferences and verify private requests follow current cookies.
- Exercise explicit Core, Site default and all Density values; preserve initialized
  native Large/other states and unrelated classes/properties/priorities. Assess
  refresh restoration/flicker and resize effects explicitly.
- Test slow/offline/malformed/timeout responses, out-of-order requests, rapid focus/
  visibility/pageshow events, expiry, back/forward and actual BFCache restoration.
- Test multiple activities, duplicate IDs, nested children, detach/reinsert, DOM
  replacement, iframe reload/rewrite and delayed assets. Inspect active observers/
  listeners and confirm complete controller teardown without unrelated changes.
- Restore SNORDIAN using the separately approved procedure, request fresh admission,
  verify Papi Jo suspension/restoration and intact SNORDIAN styling, administration
  and profile preferences. Restore the runtime gate, test users/data and caches to
  their agreed baseline; record hashes and any approved data changes.

## Q. Proposed next stage and authorization boundary

Recommend Stage 1 dormant deployment and coexistence acceptance only, after explicit
approval. Stage 2 Firefox runtime trial requires its own explicit scope including
any temporary SNORDIAN/configuration/preference changes. Unrestricted production
runtime activation also needs the remaining acceptance and diagnostics work.

**Phase 2B-2E stops here. No deployment occurred. Protected files remain unchanged;
controller activation is still disabled by default. Await explicit approval before
deployment or any subsequent phase.**
