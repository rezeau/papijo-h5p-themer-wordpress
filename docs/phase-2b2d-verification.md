# Phase 2B-2D — Native H5P Density Controller verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Source implementation and automated verification PASS.**
Shared browser attachment remains **disabled by default**. No deployment, live
WordPress bootstrap, live database/configuration/activation changes, browser
acceptance, Git commit, tag, push or release was performed.

## Implementation and native semantics

Extended the existing static `assets/js/theme-controller.js`; no second controller,
preference resolver, endpoint, PHP production change, database schema, browser
preference storage or CSS asset was introduced. The unchanged appearance descriptor
is the only input. Existing server authentication, role/publication/profile
resolution, private transport, attachment admission and cache isolation remain intact.

Small/Medium/Large apply exactly one of `h5p-small`, `h5p-medium`, `h5p-large` on
the proved outer main `.h5p-content`. Only these three tokens are changed; unrelated
classes, nested children, Core stylesheets and H5PIntegration are untouched.
Core API 1.28 adds Density on that root and defaults to Large (`js/h5p.js:107-109`).
Its native `styles/h5p-theme-variables.css` provides Small base spacing/scaling
0.6, Medium 0.8 and Large 1. Small's stylesheet rules use the base `.h5p-content`
selector; Papi Jo still explicitly selects `h5p-small` as requested.

Original does not imply Small. An untouched root receives no class write and no
resize. Before the first actual override, capture its initialized native token set,
including initialized Large, an empty set or multiple tokens. While Original remains
untouched, later legitimate native changes remain authoritative; capture occurs at
the first override rather than at manifest discovery. A pre-existing matching
selection is not claimed. Original restores the captured set only while it is
still owned. No replacement default is invented.

Theme and Density have independent ownership and readiness. Color application uses
the existing native color readiness. Density requires the same fully qualified main
player plus a direct `.h5p-theme` standalone container, computed native
`--h5p-theme-scaling`, Core's trigger function and a supported instance event API.
Density can operate while color readiness waits, and vice versa. Explicit Core
releases only color overrides and still allows Small/Medium/Large. Tests cover
Core + Small, Lavender + Large, Mint + Medium, Site default + Small and both
single-dimension refreshes. Legacy Theme Density never enters application; the
browser rejects extra Theme descriptor fields, and the unchanged PHP descriptor
tests verify legacy metadata independence through the real resolver.

## Ownership, contexts and lifecycle

Reuse the existing root/document/container/API/main-instance tuple and manifest
main-library verification. Density does not infer eligibility from a native token.
Ordinary DIV and canonical same-origin Core-written internal iframe players qualify.
Nested/child instances, duplicate IDs/instances, unregistered or mismatched players,
editors, admin pages, external embeds, cross-origin/sandbox/srcdoc frames and
ambiguous owners remain excluded. No selector modifies unrelated players.

Keep the original and last written native token sets independently of the color
property map. Before a subsequent Density write/restoration, compare the entire
current native set to the last written set. A mismatch relinquishes Density
ownership and weakly blocks reacquisition for that root for the controller's
lifetime, including pause/resume. Foreign native replacements, removals and additions
survive. Unrelated class changes are retained without causing conflict. A Density
conflict does not block Theme, nor does a color conflict release Density.

Restoration rechecks the original player tuple. Detached DIV roots with intact,
unique ownership can be restored but are not resized. Replacements qualify with
their own registered instance and native baseline; a replacement cannot borrow an
old instance. Internal iframe rewrites release old observers and qualify the new
document. Discarded documents and newly ambiguous/changed owners are not mutated,
even to restore; they are quarantined. This can conservatively leave a previous
override on an uncertain old root until its application replaces/removes it.

The existing lifecycle handles initial/late initialization, DOM replacement,
descriptor refresh, pageshow/bfcache, focus, visibility, expiry, suspension and stop.
No Density observer, listener, timer, event subscription or retry schedule was added.
Existing observers are bounded to the parent plus one internal document per candidate
(maximum 65); they remain attached while active. Mutations coalesce at 25 ms.
Seven finite retries (50/100/250/500/1000/2000/4000 ms) and existing resource-load
events handle delayed instances/styles/API. No continuous DOM polling exists.

The current contract specifies **30 seconds**, so that lifetime is preserved instead
of the request's suggested 60-second ceiling. Refresh invalidates admission and
restores both dimensions before fetching. Thus a Theme-only refresh can temporarily
restore/reapply Density, with corresponding actual-change resize events; the final
Density selection remains independent. Generation/correlation checks, monotonic
request-start expiry, timeout, private credentials/no-store options and rejection
of malformed/out-of-order/expired responses remain unchanged. Hidden/pagehide/stop
restore safe ownership and release resources. Failures and suspension do not poll;
successful visible-player expiry renewals use the existing single expiry timer.

## Resize integration and failure handling

After an actual connected-root native class-set change, recheck the tuple and call
that realm's `H5P.trigger(instance, 'resize')` exactly once. No-op scans and matching
native selections do not resize. No global/window resize is dispatched and no
unrelated or child instance is targeted. No iframe dimensions are written by Papi Jo.

Inspected installed Core initialization: it installs the internal frame resize
listener before registering the main instance (`h5p.js:264-299,364-367`). The listener
coalesces resize callbacks with a 1 ms delay, skips fullscreen and computes iframe
height from `body.scrollHeight`, temporarily retaining and then restoring wrapper
height. Core's dispatcher (`h5p.js:2304`) supports both the modern instance event API
and the deprecated jQuery API. The new suite executes those exact read-only source
fragments in an isolated VM and verifies iframe height, wrapper restoration and
the fullscreen guard. These are source-contract checks, not real browser sizing.

A synchronous resize exception restores only still-owned Density and blocks further
Density attempts on that root, leaving Theme and other players operational. A native
change inside the failed callback survives. If the callback changes player ownership,
rollback also rechecks the tuple and quarantines the old root without writing to the
new owner. Repeated mutation/refresh scans do not retry the failing Density path.
Asynchronous Core/library errors cannot be caught by a synchronous dispatch wrapper.

## SNORDIAN coexistence and security

No server attachment, activation constant, shared policy, detector, transport,
resolver, Profile/Role service or administration code changed. Existing PHP tests
verify default-disabled attachment, all four SNORDIAN signals, late suspension at
tag output, no actionable suspended appearance and preserved administration/profile
behavior. Already-admitted browser fixtures receiving suspension restore still-owned
Theme and native Density, retain foreign class/property changes and never reapply
stale instructions. SNORDIAN files, callbacks, settings and activation remain untouched.

The asset is static and contains no viewer-specific values. Current viewer identity
still comes from WordPress. There are no browser-supplied user IDs, new controls,
browser storage authority, preference values in shared HTML/assets, per-user CSS,
dependency-cache changes, new routes or production writes. The unchanged
`PJH5PWP_ENABLE_BROWSER_THEME` boolean gate remains disabled by default and now gates
the shared Theme/Density asset. No live definition/configuration was changed.
Coarse aggregate runtime diagnostic flags retain their previous values pending
separately authorized diagnostic refinement and real qualification.

## Exact source changes

Seven pre-existing files modified:

| File | Change |
| --- | --- |
| `assets/js/theme-controller.js` | Consume independent Density; conditional class ownership/restoration, native readiness and targeted safe resize |
| `tests/runtime/theme-controller.js` | Extend DOM/event doubles, export reusable fixture helpers, retain all 124 prior assertions |
| `README.md` | Current checkpoint, Density test command and next-phase boundary |
| `readme.txt` | Current source-only/default-disabled checkpoint |
| `docs/architecture.md` | Shared Theme/Density implementation and remaining qualification boundaries |
| `docs/runtime-transport.md` | Current-checkpoint note; endpoint contract unchanged |
| `docs/theme-controller.md` | Density semantics, ownership, readiness, resize and limitations |

Two files added:

| File | Purpose |
| --- | --- |
| `tests/runtime/density-controller.js` | 202 native Density integration and read-only Core resize assertions |
| `docs/phase-2b2d-verification.md` | This report |

No source removals. All PHP, presets, other assets and historical verification
reports retain their original bytes. Existing source/uncommitted work was preserved.

Local evidence, separate from deployable source, under `.local-acceptance/`:
`phase-2b2d-before.json`, `phase-2b2d-run.ps1`, `phase-2b2d-initial-tests.json`,
`phase-2b2d-tests.json`, `phase-2b2d-javascript.json`,
`phase-2b2d-interim-audit.json` and `phase-2b2d-verification.json`.

## Automated results

| PHP suite | 7.4.33 | 8.3.18 | 8.4.22 |
| --- | --- | --- | --- |
| Foundation / installed H5P source (18 scenarios) | 621 PASS | 621 PASS | 621 PASS |
| Domain / Backdrop parity | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Theme storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Administration / UX / role forms | 314 PASS | 314 PASS | 314 PASS |
| Existing SNORDIAN coexistence | 44 PASS | 44 PASS | 44 PASS |
| Role/profile lifecycle | 251 PASS | 251 PASS | 251 PASS |
| Role storage doubles | 46 PASS | 46 PASS | 46 PASS |
| Phase 2B-2A descriptor/serializer | 548 PASS | 548 PASS | 548 PASS |
| Runtime coexistence (8 scenarios) | 70 PASS | 70 PASS | 70 PASS |
| Phase 2B-2B transport/context/security | 329 PASS | 329 PASS | 329 PASS |
| Real policy admission/attachment (10 scenarios) | 73 PASS | 73 PASS | 73 PASS |
| Default-disabled / explicit attachment | 28 PASS | 28 PASS | 28 PASS |
| Loopback HTTP/cookie/session/CORS | 198 PASS | 198 PASS | 198 PASS |
| Role MySQL | 69 PASS | 69 PASS | 69 PASS |
| Profile MySQL | 56 PASS | 56 PASS | 56 PASS |
| Theme storage MySQL | 49 PASS | 49 PASS | 49 PASS |
| PHP syntax | 86 files PASS | 86 files PASS | 86 files PASS |

**5,215 PHP assertions per version**, including 919 authoritative Backdrop parity
assertions. No PHP source changed. Initial restricted-token execution reproduced
the previously documented Windows `is_readable()` false-negative; normal-process
verification resolved it without modifying detection or fixtures.

Node **22.23.1**, no npm dependencies: **202 new Density + 124 existing Theme + 30
existing administration = 356 JavaScript assertions PASS**. Final JS checks include
all production changes. The first native-set fixture caught a class-token branch
error, corrected before the final run. The extracted Core listener fixture needed
Core's surrounding resizeDelay variable; its VM context was corrected. Neither
issue remains in the final results.

All HTTP checks used disposable loopback servers with in-memory fixture identities
and installed WordPress authentication/CORS definitions, without WordPress bootstrap.
All MySQL checks used guarded random `pjh5pwp_test_*` databases on MySQL 9.1.0 and
removed them afterward. Runners read only literal host/user/password from wp-config,
ignore its DB_NAME and load wpdb without bootstrapping the live site. They never
select the live acceptance.example.test database. No new persistence test/schema is required.

## Protected-file audit and acceptance limits

Before/after SHA-256 comparison covers the entire acceptance.example.test filesystem and authoritative
Backdrop reference: **8,731 WordPress-tree files and 155 Backdrop files unchanged**,
with no additions or removals. This includes WordPress Core, official H5P, H5P PHP/JS
Core, SNORDIAN and wp-config. Source baseline: **120 pre-existing files unchanged,
seven intentional modifications, two additions, no removals**. The project directory
has no Git metadata; these snapshots establish preservation against the source found
at this task's start. Hash evidence is retained locally. This is a filesystem audit,
not a live database checksum audit; live database isolation is established by the
guarded test runners and absence of live bootstrap/deployment.

Read all six requested project references and inspected the actual controller,
attachment, descriptor, tests and installed H5P implementation. Consulted the
authoritative Backdrop v0.5.0 native-density implementation associated with supplied
reference commit `d9b53d706a94dac322b89885bf668145ab3803c4`; no Backdrop browser
controls, storage authority or settings transport were ported. The reference
directory has no Git metadata, so the supplied commit identity is not independently
proved by a local checkout.

**Actual Firefox acceptance was not performed and is not claimed.** DOM/CSSOM/event
doubles and extracted Core callbacks do not prove real stylesheet timing, browser
layout, iframe sizing across libraries, fullscreen transitions, visual spacing,
cache/CDN behavior, CSP/asset optimization, session switching or live SNORDIAN
activation. Libraries may ignore resize/native variables; asynchronous callbacks
and library-specific sizing need representative browser acceptance. Fixed-color
libraries may consume spacing differently from native Theme colors.

Native same-set foreign writes, or changes undone between observer scans, remain
observationally indistinguishable. Ambiguous owners may retain old overrides because
restoring them would mutate an unproved owner. Late initialization beyond the finite
retry window needs a DOM/load/lifecycle/refresh event. Policy/account changes are
discovered at refresh/expiry, without pushed remote notifications. No unresolved
automated failures remain.

## Recommended next phase

Recommend **2B-2E — Cache and Lifecycle Qualification**: specify and test cache reuse,
viewer/session transitions, expiry boundaries, late admission/SNORDIAN changes,
multiple players, iframe rewrites and observer cleanup. Define separately authorized
Firefox acceptance for representative native/Papi Jo/fixed-color libraries and
library-specific resize behavior, plus coherent aggregate diagnostics before any
production activation. Deployment and live browser work still require explicit scope.

**Stopped after Phase 2B-2D. Await explicit approval before Phase 2B-2E.**
