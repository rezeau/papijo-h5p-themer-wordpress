# Runtime Step 3 — return-tab/window and BFCache stabilization

10 November 2026. **Source verification, local Step 3 deployment and agreed Firefox acceptance PASS. Steps 1–3 complete for the agreed local scope.** Firefox results below are user-reported manual observations, not an agent browser reproduction. Actual BFCache activation and compatibility with the subsequently selected WordPress theme are not confirmed.

## Exact changes

Production: assets/js/theme-controller.js; includes/Runtime/class-theme-attachment.php (asset version only, 0.1.0-readiness-3).

Tests: tests/runtime/theme-controller.js (event.persisted and focus support in fixtures); tests/runtime/readiness-controller.js (reusable fixture export; existing assertions unchanged); tests/runtime/return-controller.js (new).

Documentation: docs/theme-controller.md; docs/runtime-transport.md; this report.

There are no new production files, CSS/public markup changes, endpoint/resolver changes or authorization-lifetime changes. Private boundary bookkeeping reuses Step 2 markers, status/busy handling, 15-second readiness and frame/height checks. The asset-version increment prevents stale Step 2 JavaScript under new source deployment; it is not a bootstrap/configuration change.

## Behavior and safety

Hide/pagehide conceals only previously validated, still-qualified activity boundaries before a possible snapshot. Owned DOM values remain concealed rather than being visibly torn down; current authorization is not reused on return. Requests/generations are canceled, observers are disconnected and renewal stops. The old hard deadline is retained; at expiry still-owned styling is restored while concealed. BFCache/frozen-timer restoration rechecks elapsed age before action.

Visible return uses one fresh authenticated request for an overlapping focus/visibility/pageshow burst. Each player independently reveals only after fresh permitted appearance, exact main-instance qualification, ownership and sizing checks. Identical results produce zero color/class writes and resize calls. Changed Theme/Density affects only its dimension. A genuine subsequent hide cancels the pending generation. Explicit refresh also conceals before immediate revalidation. Initial nonpersisted pageshow/focus does not cause an extra startup request.

The 25-second scheduled renewal, independent 30-second authorization hard expiry and 10-second transport/body timeout are unchanged. A 15-second return window is not reset by repeated events. Hidden execution cancels return frame/watchdog jobs, and wake checks the retained deadline; mixed pages preserve any slow sibling's original Step 2 head-start deadline. Invalid, suspended, timed-out or failed requests restore only proved ownership and reveal labeled fallback. No failure polling or silent stale-grant reuse occurs.

Foreign inline priorities and Density tokens remain authoritative; uncertain owners remain quarantined. Failed return activities stay blocked from automatic renewal styling until a new deliberate lifecycle/explicit transition can freshly qualify them. Initial-readiness failure retains Step 2's reload recovery semantics. Successful siblings remain independent. Stop releases all owned state and listeners/timers.

Keyboard target restoration is conditional: same connected target, successful reveal, no intervening user interaction/focus, and focus still at body/that target; preventScroll avoids movement. Failure does not force focus. User Firefox keyboard/focus acceptance passed; broader assistive-technology coverage remains deferred.

The legacy create() API without a readiness gate remains conservatively restore-first, allowing safe compatibility and keeping its security assertions intact. New tests exercise the production head-bootstrap/gate path rather than replacing those assertions.

## Automated results

| Suite | PASS assertions |
| --- | ---: |
| Theme JavaScript | 124 |
| Density JavaScript + read-only actual Core resize source | 202 |
| Step 1 lifecycle JavaScript | 329 |
| Step 2 initial-readiness JavaScript | 188 |
| Step 3 return/BFCache JavaScript | 60 |
| PHP attachment | 34 |
| PHP readiness markup/admission | 22 |
| PHP transport/context | 329 |
| PHP identity/permission qualification | 185 |
| PHP SNORDIAN coexistence | 70 |
| PHP transfer administration | 92 |

Total: **1,635 reported assertions**, plus a separate **22-assertion PHP 7.4 compatibility run**. PHP 7.4 syntax check of the changed attachment and Node controller syntax check pass. PHP primary runs use 8.4.22; coexistence uses normal Windows permissions for the known fixture readability limitation. No database or live CMS is bootstrapped. Shared fixture prerequisite assertions are not counted twice. No final test failures or removed/weakened security assertions.

New coverage includes before/after-expiry BFCache, frozen timers, event bursts, rapid switching/cancellation, initial startup coalescing, network/invalid/suspension/permission outcomes, timeouts and late replies, hard expiry during return, bounded CSS-readiness fallback, mixed initial/return players, foreign styling, lost roots, independent DIV/iframe reveal, differential writes/resizes, recovery and non-stealing focus. Existing initial and renewal suites preserve their guarantees. These are simulated DOM/time/session responses, not browser pixels or actual account changes.

## Confirmed local deployment — 10 November 2026

Deployment PASS at 12:10 Europe/Paris, using the corrected isolated-tested Windows ACL runner. Quiet-window, source/Step 2 hashes, 79-file inventory, activation and database preconditions passed. Exactly these installed files changed under PROJECT_ROOT:

| File | Bytes | Installed Step 3 SHA-256 |
| --- | ---: | --- |
| assets/js/theme-controller.js | 39,006 | PRIVATE_VERIFICATION_DIGEST_OMITTED |
| includes/Runtime/class-theme-attachment.php | 3,933 | PRIVATE_VERIFICATION_DIGEST_OMITTED |

All 79 installed files were accounted for; the other 77 remained unchanged. Owner/group, DACL/ACE order, inheritance/control flags, attributes and parent security metadata were preserved. Registry and readiness CSS stayed at Step 2; protected runtime-enabled bootstrap hash remained PRIVATE_VERIFICATION_DIGEST_OMITTED. Source candidates were unchanged. Papi Jo remained active; SNORDIAN inactive at deployment verification.

Private backup/evidence directory (outside web root): PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED

| Backup | Step 2 original | Bytes | SHA-256 |
| --- | --- | ---: | --- |
| original-0 | assets/js/theme-controller.js | 33,679 | PRIVATE_VERIFICATION_DIGEST_OMITTED |
| original-1 | includes/Runtime/class-theme-attachment.php | 3,933 | PRIVATE_VERIFICATION_DIGEST_OMITTED |

Both backup byte sets, private permissions and separate security metadata/checksums were verified before publication. Earlier incomplete preparation bundles are not the rollback source. **Rollback not required.** Any later authorized rollback must restore both matching originals and saved security metadata through the verified helper, verify the 79-file Step 2 inventory and consistent version-2 delivery, and leave registry/CSS/bootstrap/database/preferences untouched.

Guarded Apache PHP 8.4.22 HTTP verification PASS: both CSS and JS referenced ver=0.1.0-readiness-3; CSS preceded the blocking head controller; no async/defer/module rewrite; footer manifest and readiness markers present; both assets returned 200; controller response hash matched Step 3; no mixed versions, PHP warnings or fatal errors. The temporary helper was removed. The probe disabled aggregation only for its request; this is not a claim of every cached/optimized delivery path passing.

The read-only guard blocked WordPress block-pattern housekeeping writes. Database content fingerprint stayed PRIVATE_VERIFICATION_DIGEST_OMITTED; preference/activation fingerprints and checked configuration hashes were unchanged by deployment. No cache clearing, service restart or Git operation occurred. These are deployment-time findings; the user subsequently changed the WordPress visual theme.

Evidence in that directory: deployment-result.json, bundle.json, installed-before.json, installed-after.json, state-before.json, state-after.json, http-page-step3.json, http-assets.json and cleanup.json.

## Final user-reported Firefox acceptance — all seven PASS

| Check | Result and scope |
| --- | --- |
| Return after 45 seconds | PASS: no Mint Theme or incorrect-Density flash. |
| Rapid tab switching | PASS: stable appearance and interaction. |
| Browser-history return | PASS visually: correct appearance; minor acceptable layout movement. Actual BFCache activation was not instrumentally confirmed. |
| Nested QuestionSetPapiJo | PASS: correct Theme, Density and interaction. |
| Same-origin iframe | PASS: brief “Loading activity appearance” notice accepted. |
| Keyboard navigation and focus | PASS. |
| Uninterrupted viewing for at least two minutes | PASS: no periodic flashing. |

Together with the earlier Step 1/2 Firefox baseline, this completes Steps 1, 2 and 3 for the agreed local acceptance scope. Similar minor layout rearrangement was observed with SNORDIAN; that observation does not establish its cause or a broader coexistence PASS. No further layout optimization is requested.

## Pending compatibility, deferred hardening and release work

**New WordPress visual theme compatibility: PENDING.** The user changed the site theme after these tests; no explicit compatibility result is available for that theme. Do not transfer the prior PASS to the new configuration without testing.

Deferred manual hardening: instrument actual BFCache activation and first restored frames; slow/failed requests and blocked assets, CSP/optimizer/cached-delivery paths, broader browser/library coverage, responsive/fullscreen sizing and screen-reader behavior. Live identity/permission/SNORDIAN transitions need separate authorization. Existing automated boundary/failure tests passed but do not replace those manual checks. Original Mint-rule identification remains unproven. Browser-managed saved pixels and frozen execution still prevent an absolute pre-paint/wall-clock guarantee; no immediate server-push revocation is claimed.

Release preparation is separate: source review, appropriate final checks, version/changelog/package review, explicit runtime-enabled versus default-disabled bootstrap handling, clean install/upgrade/rollback validation, and administration/Export-Import preservation smoke coverage. No release readiness or public deployment is implied by local acceptance.

Documentation finalization changed no production code, installed files, database/configuration/preferences or activation. No commit, push, tag or publication. **STOP: await further instructions.**


> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.
