# Phase 2B-2B — runtime admission and transport verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Source-only implementation and automated verification PASS.**
No deployment, live WordPress bootstrap, live database changes, browser styling,
Git commit, tag, push or release. Phase 2B-2C remains unauthorized.

## Implemented components

The WordPress runtime adapter composes the unchanged Phase 2B-2A descriptor and
CSS serializer with the existing Runtime_Policy, SNORDIAN detector and
Profile/Role services. Runtime_Admission adds only context exclusion, retaining
the shared compatibility decision. Runtime styling status remains unimplemented
and disabled; an admitted descriptor does not mean browser styling is operational.

Playback_Registry observes ordinary frontend `print_h5p_content` calls without
changing wrapper bytes. It validates main identity/library/embed metadata and the
exact generated DIV/internal iframe wrapper, excludes non-frontend/ambiguous
contexts, supports distinct activities, and revokes repeated IDs. Its candidate
accessor rechecks admission. No dependency list is used as a main-content identity.

Appearance_Endpoint provides authenticated/anonymous admin-ajax dispatch with
server-derived current identity. It rejects unknown inputs and forged identities,
requires a strict same-origin frontend POST/custom header, disables action-scoped
CORS (including early routed OPTIONS), and emits private/no-store JSON. It performs
no writes, content loads, event logging, installation, preference updates or asset
generation. Administrative/Profile behavior remains available with SNORDIAN active.

The candidate accessor is **dormant attachment infrastructure**: nothing currently
emits its data into HTML or attaches a controller. No JS/CSS asset was added or
modified. Browser DOM/player classes, Theme/Density application, property injection,
iframe lifecycle and resize handling remain future work.

Design, REST-versus-AJAX authentication analysis, exact request/response schema,
security/cache headers, expiration rules, source hook contracts and attachment
strategy: [runtime-transport.md](runtime-transport.md). Complete effective response
examples: [runtime-transport-examples.json](runtime-transport-examples.json).

## References inspected

Reviewed phase-2b2a-verification.md, runtime-appearance.md, the Phase 2B-1 final
investigation in the **Add role-based H5P permissions** chat, Phase 2A-R
verification/deployment records, architecture and current source implementations.
There was no separate Phase 2B-1 repository report; the earlier chat contains it.

Inspected the authoritative Backdrop v0.5.0 runtime serializer/attachment source,
reference commit `d9b53d706a94dac322b89885bf668145ab3803c4`. No Backdrop code was
changed or copied as a WordPress browser/session authority.

Reverified installed H5P 1.17.10 signatures and invocation paths: three-argument
asset actions, absent content ID, two-argument content filter after asset setup,
ordinary add_assets in administration previews, editor/external modes, Core
determineEmbedType and parent-written internal iframe documents. Inspected actual
WordPress admin-ajax dispatch, default authentication filters, REST cookie nonce
checks and send_origin_headers before selecting transport. No H5P service was
constructed or live activity loaded during verification.

## Exact source file changes

Seven existing files modified:

| File | Change |
| --- | --- |
| `papijo-h5p-themer-wordpress.php` | Load WordPress runtime adapter definitions |
| `includes/class-plugin.php` | Share existing services; register endpoint/observer; expose dormant candidates |
| `tests/worker.php` | Add filter double and precise new hook expectations; retain old safety assertions |
| `README.md` | Current source checkpoint, runtime boundaries and test commands |
| `readme.txt` | Current source checkpoint and deployment clarification |
| `docs/architecture.md` | Implemented transport versus future browser boundaries |
| `docs/runtime-appearance.md` | Update composition note; descriptor/serializer contract unchanged |

Twelve new source/documentation files:

| File | Purpose |
| --- | --- |
| `includes/runtime-wordpress.php` | Adapter definition loader |
| `includes/Runtime/class-runtime-admission.php` | Shared policy context adapter |
| `includes/Runtime/class-wordpress-context.php` | Frontend and strict same-origin request qualification |
| `includes/Runtime/class-playback-registry.php` | Non-personal request-local main-content candidates |
| `includes/Runtime/class-appearance-endpoint.php` | Read-only identity-derived private transport |
| `tests/runtime/transport.php` | Real Profile/Role/domain descriptor and playback/security contracts |
| `tests/runtime/admission.php` | Real shared-policy/detector endpoint integration in isolated processes |
| `tests/runtime/http-fixture.php` | Loopback-only disposable HTTP fixture with real WP cookie/session/CORS definitions |
| `tests/runtime/http-check.py` | HTTP header/authentication/rejection/user-switch checks |
| `docs/runtime-transport.md` | Stable transport, security, qualification, freshness and deferred controller design |
| `docs/runtime-transport-examples.json` | Six complete reproducible fixture response envelopes |
| `docs/phase-2b2b-verification.md` | This report |

No source removals. Both Phase 2B-2A implementation files and runtime.php remain
byte-for-byte unchanged. Runtime_Policy, detectors, domain, persistence, roles,
profiles, administration, presets, assets and languages retain their original bytes.

Local-only evidence, outside deployable source, under `.local-acceptance/`:
`phase-2b2b-before.json`, `phase-2b2b-run.ps1`, `phase-2b2b-initial-tests.json`,
`phase-2b2b-tests.json`, `phase-2b2b-examples-{7.4.33,8.3.18,8.4.22}.json`, and
`phase-2b2b-verification.json`. Earlier evidence and private backups were preserved.

## Automated results

| Suite | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| New transport/context/security | 329 PASS | 329 PASS | 329 PASS |
| New real admission/detector integration | 62 PASS / 10 scenarios | 62 PASS / 10 scenarios | 62 PASS / 10 scenarios |
| New loopback HTTP/cookie/session/CORS | 198 PASS | 198 PASS | 198 PASS |
| Phase 2B-2A descriptor/serializer | 548 PASS | 548 PASS | 548 PASS |
| Phase 2B-2A runtime coexistence | 70 PASS / 8 scenarios | 70 PASS / 8 scenarios | 70 PASS / 8 scenarios |
| Foundation / installed H5P source | 621 PASS / 18 scenarios | 621 PASS / 18 scenarios | 621 PASS / 18 scenarios |
| Domain / authoritative Backdrop parity | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Theme storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Administration / UX / role form | 314 PASS | 314 PASS | 314 PASS |
| Existing SNORDIAN coexistence | 44 PASS | 44 PASS | 44 PASS |
| Role/profile lifecycle | 251 PASS | 251 PASS | 251 PASS |
| Role storage doubles | 46 PASS | 46 PASS | 46 PASS |
| Role MySQL | 69 PASS | 69 PASS | 69 PASS |
| Profile MySQL | 56 PASS | 56 PASS | 56 PASS |
| Theme storage MySQL | 49 PASS | 49 PASS | 49 PASS |
| PHP syntax | 84 files PASS | 84 files PASS | 84 files PASS |

Per version: **5,176 assertions PASS**, including **589 dedicated new assertions**.
The foundation suite retains its previous assertions and gains 108 checks from
the four newly expected hooks and two callback-contract assertions in each of 18
scenarios. Domain totals include 919 authoritative Backdrop parity assertions.
Existing isolated JavaScript DOM regressions: **30 assertions PASS**.

Transport tests reuse the real Profile_Service, Role_Service, palette resolver and
serializer with memory-only storage seams. Coverage includes anonymous, automatic
Administrator, authorized Subscriber and revoked/unauthorized Subscriber; current
role/global changes; all requested descriptor examples and fallback; independent
Density; forged IDs/values; exact response fields; no metadata/catalog leakage;
fresh identity across login/logout/user switching; malformed contexts and iframe
metadata; unchanged shared wrapper bytes/candidates across viewers; no profile
reads during page qualification; no writes, controller or CSS attachment.

Admission workers use real Runtime_Policy/detectors for compatible/missing/inactive
H5P, wrong/malformed Core, multisite, all four SNORDIAN signals and late activation.
Suspension skips profile resolution and contains no actionable instructions.

HTTP tests run disposable PHP development servers bound only to 127.0.0.1, using
installed WordPress pluggable cookie generation/parsing/HMAC and real session-token
verification classes, the hook API and actual send_origin_headers. User/session
storage and effective appearance inputs are disposable in-memory fixtures; real
role/profile resolution is covered separately. They verify real headers on success,
400/403/405 and routed OPTIONS, including removal of pre-existing wildcard CORS,
credentials, ETag and Last-Modified. Real signed cookies identify separate users;
forged HMAC, absent/revoked sessions, expired cookies and cleared-cookie logout use
anonymous appearance. Test fixture login cookies are never production credentials.

All MySQL regressions used guarded random `pjh5pwp_test_*` databases on MySQL 9.1.0,
then dropped them. The existing runners read only literal host/user/password from
wp-config and ignore its DB_NAME; installed wpdb runs without WordPress bootstrap.
No new production service writes to a database or requires a new schema test.

The documented Windows sandbox is_readable false-negative and loopback network
restriction required authorized normal-process verification. Early fixture runs
also caught a missing fixture function, closure serialization and duplicate WP_Hook
loading; these harness defects were corrected. Initial matrix output is retained.
The final matrix passed, and affected transport/HTTP/syntax checks were rerun after
the final context hardening and Subscriber/cache-neutrality cases. Six example
envelopes serialize identically on all three versions and match the checked-in JSON.

## Preservation, limitations and next phase

Before/after SHA-256 comparison establishes **102 pre-existing source files
unchanged, seven intentionally modified and twelve added**. There is no Git
metadata in the WordPress source directory; hashes establish preservation of
existing work. Protected comparison covers **518 installed plugin files plus
155 authoritative Backdrop files: 673 unchanged**, without additions/removals.
No acceptance.example.test file was written. This is a protected filesystem comparison, not a live
database checksum audit. Live WordPress was never bootstrapped or selected as a
test database. No configuration/activation changes or deployment were performed.

The full real WordPress/H5P HTTP stack, Firefox playback, actual CDN/page-cache
configuration and browser session-transition behavior were not exercised: they
would require separately authorized deployment/live-site work. The loopback harness
does not claim a full admin-ajax WordPress bootstrap or real database authentication
test. Minimum WordPress 6.5 is an API compatibility target, not an installed-version
matrix result. Strict missing-referrer/origin, split-origin and altered-wrapper cases
fail closed; repeated IDs are deliberately unsupported. Same-origin HTTP checks
cannot prove DOM/player ownership; final browser verification remains mandatory.

No browser lifecycle exists to consume expiration, discard old asynchronous
responses, remove applied styles or detect remote account/policy changes. The
version-1 response documents the future consumer's generation/correlation checks
and 30-second maximum admission lifetime; it does not implement those behaviors.
There is currently no runtime styling to become stale. External embeds stay excluded.

Recommend **Phase 2B-2C — Browser Theme Controller**, after explicit authorization:
attach one static parent-page asset using the dormant candidate seam, emit only
non-personal public attachment metadata, validate final player ownership, consume
private responses with generation/expiry handling, and apply/remove only Theme
instructions under fresh admission. Test cached-page reuse, session transitions,
late SNORDIAN activation, multiple activities and internal iframe document timing.
Keep native Density class application, Original-state restoration and associated
resize behavior outside that Theme-only phase unless separately authorized.

**Stop here. Await explicit approval before Phase 2B-2C.**
