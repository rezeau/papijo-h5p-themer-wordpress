# Browser Theme and native Density controller — Phases 2B-2C/2D/2E

Current release status (11 November 2026): Stages A, B and C are completed, deployed locally and manually accepted in Firefox, per maintainer confirmation. Runtime attachment is default-on when PJH5PWP_ENABLE_BROWSER_THEME is undefined or boolean true; every other value disables it. Compatibility/context admission and SNORDIAN suspension remain mandatory. Single-site activation ensures Profile storage; updates use read-only detection and protected retry. See [installation](installation.md). The slow-network readiness limitation remains unresolved. Older phase descriptions below are historical checkpoints.

Runtime Step 3 remains deployed and locally accepted. Stage A subsequently replaced the temporary local opt-in with the shared default-on configuration. Automated fixtures do not certify real browser first paint. No browser preference storage is introduced.

## Current lifecycle — Step 3 contract

This section supersedes historical statements below requiring immediate DOM
restoration on every focus/visibility/pageshow event. Endpoint/resolver semantics
and the original 30-second authorization age are unchanged.

The real head-bootstrap path retains validated Step 2 boundaries privately after
successful initial reveal. On hide/pagehide, it marks only still-qualified known
activities pending before releasing their visible presentation, cancels in-flight
work and renewal, and disconnects observers. It retains ownership records and the
old descriptor's hard deadline. Retained DOM under the gate is not actionable
authorization: fresh() is false during return revalidation and hidden states.
Expiry restores still-owned values even while concealed; timer-throttled expiry
is checked again synchronously on return before any fresh application.

A visible focus/visibility/pageshow transition requalifies, reuses the same marker,
busy/status CSS and one existing authenticated request. Overlapping pending return
signals share a generation/request without repeated teardown or restarted deadlines.
Initial nonpersisted pageshow joins startup; persisted pageshow always enters the
return path unless already part of that same pending return. An actual subsequent
hide aborts the request and invalidates its generation. Explicit refresh also gates
before immediate authenticated revalidation; it does not reuse an old response.

Only a fresh permitted descriptor makes the return actionable. Equal results
reveal after the existing bounded frame/sizing checks with no appearance writes or
resize. Changed dimensions apply by difference. Old hard expiry still releases
owned styling while waiting; each accepted result has its own request-start age.
Scheduled renewal remains at 25 seconds with independent hard expiry at 30 seconds.
Transport/body timeout remains 10 seconds. Suspended, invalid or failed responses
restore only proved ownership before fallback; no failure polling is introduced.

Return readiness is capped at 15 seconds from the visible return. A repeated
switch during an already pending return retains that deadline rather than extending
it. Timers/frame jobs suspend while hidden; wake checks elapsed time before reuse.
A mixed page's still-pending initial activity retains its original head-start bound.
Failed return IDs cannot be automatically styled by later renewals, but a new
explicit/lifecycle transition may start fresh recovery on a still-qualified known
boundary. Initial-readiness failure remains terminal for that page, as in Step 2.
No new marker, CSS selector, endpoint, preference storage or production file exists.

Busy state and loading messages are reused; unrelated page content is not gated.
Focus is restored with preventScroll only to the same connected target following
successful reveal, only if the user has not interacted/focused elsewhere and focus
has fallen back to the document body. The controller never focuses another player
or forces focus after failure. Removed/ambiguous owners and foreign inline/class
writes keep the original quarantine/relinquishment safeguards.

A headless/legacy create() call without the readiness mechanism retains its earlier
conservative restore-first lifecycle. Existing suites continue exercising that
safe compatibility path; the new return-controller suite tests the actual head
bootstrap with the full gate. No security assertions were removed to hide a change.

BFCache pagehide arms known activity boundaries before a possible snapshot and
pageshow revalidates without treating stored pixels as a grant. Browser-managed
snapshot/pre-paint timing, frozen execution and assistive focus behavior cannot be
proved by deterministic fixtures; no absolute pre-paint BFCache guarantee is made.
See runtime-b3-step3-verification.md for the successful local deployment, rollback backup and all seven user Firefox PASS results. Browser-history visual acceptance does not confirm actual BFCache activation. Compatibility with the subsequently selected WordPress theme is pending.
## Shared head/readiness delivery — Steps 2 and 3

Step 1 was deployed separately and passed user Firefox uninterrupted-viewing
acceptance. Step 2 was deployed separately and has user-confirmed Firefox acceptance. Step 3 is deployed and accepted for the agreed local Firefox scope. Historical phase/checkpoint statements later
in this document describe their original verification, not a new deployment.

Theme_Attachment reuses Playback_Registry admission through attachment_permitted().
With runtime disabled it registers no attachment hooks. When enabled, its
wp_enqueue_scripts callback (priority 5, before the wp_head asset printers) queues
runtime-readiness.css and the synchronous classic parent controller with
in_footer=false. Neither asset enters H5P dependency/iframe bundles. Both use
asset version 0.1.0-readiness-3 without changing the installed bootstrap/version.
No player/profile scan is needed in the head; eligible pages without H5P incur
only public asset overhead and send no appearance request.

The controller head tag carries data-pjh5pwp-bootstrap="1". Async/defer/module
rewrites are rejected. Late suspension, excluded context and a split-origin
endpoint suppress attachment. Public CSS and the head bootstrap must retain their
ordering under CSP/optimizers; no executable inline bootstrap or relaxed CSP is
introduced. If the controller is blocked/disabled the document never arms the
CSS gate and content remains visible.

Playback_Registry continues returning exact original H5P markup. The subsequent
attachment-owned print_h5p_content filter marks only registry-validated exact
markup, adding data-pjh5pwp-pending to the DIV root or internal iframe wrapper.
Required H5P roots, classes, IDs and iframe bytes remain intact. A hidden,
translated status sibling is outside the concealed subtree. The footer emits
an inert hidden span with escaped data-pjh5pwp-manifest containing exactly the
existing version/endpoint/players schema. It contains no viewer, permission,
Theme, Density, nonce or palette. Duplicate IDs and over-limit manifests remain
revoked; orphan presentation markers are released at DOM readiness.

The head bootstrap installs a 15-second watchdog before arming CSS. Visibility
concealment includes descendants (not display:none or opacity), preserving layout
measurement and excluding invisible controls from ordinary keyboard focus.
Internal frames reserve min(12rem, 50vh) while pending. This is a placeholder,
not a claim to know final activity height. There is no server-side inert/busy state
that could trap JavaScript-disabled users. Busy presentation is owned/restored in
JavaScript; foreign busy-attribute changes are preserved.

At DOM readiness one controller consumes the final public manifest and uses the
unchanged authenticated endpoint. Each activity reveals independently only with
a current descriptor, exact initialized main-player proof and loaded Theme/Density
CSS/API. Core/Original and matching native values are legitimate no-ops. After
application/required Density resize, two bounded animation-frame callbacks recheck
qualification, freshness and appearance ownership. Internal iframe height must
exceed 1px, be stable across those callbacks and equal current body.scrollHeight.
No iframe dimensions are assigned by Papi Jo, and no new polling loop or
ResizeObserver is introduced. Existing load/mutation/finite retries may retry
readiness until the original 15-second deadline.

Missing/invalid metadata, suspended/failed/timed-out transport, ownership conflict,
stop/pagehide or readiness timeout fail open to native/foreign appearance. Only
provably owned appearance is restored. Fallback is explicitly labeled and never
counted as successful personalization. No failed initial activity is automatically
repersonalized later in that page lifetime; reload is the recovery path. Successful
siblings keep their valid appearance when another activity times out. The watchdog
is not extended by retries or events; JS timer/frame throttling cannot promise
wall-clock execution in a frozen browser. A later DOM-ready callback after timeout
cannot restart the gate or transport.

The 10-second request/body timeout, 25-second ordinary renewal and independent
30-second hard expiry remain intact. Initial readiness does not re-arm on ordinary renewal. The current Step 3 lifecycle above reuses the same presentation contract for returns.

## Fetch, lifetime and lifecycle

The existing version-1 admin-ajax endpoint is unchanged. Each POST contains exactly
`action=pjh5pwp_appearance` and a fresh cryptographic 128-bit hexadecimal request ID.
Requests use `credentials: same-origin`, `mode: same-origin`, `cache: no-store`,
`redirect: error`, the existing public request header, form encoding and a
same-origin referrer policy. The browser supplies Origin/Referer and WordPress
authenticates the current session. No client identity or preference is submitted.
The endpoint's Origin/authentication/cache/CORS protections remain unchanged.

Initial visible DOM readiness, pageshow (including bfcache), window focus and visibility
return revoke the previous generation immediately. Automatic lifecycle requests
are coalesced with a one-second minimum between request starts: clustered signals
share one trailing timer, without extending its deadline. Even a pending request
is invalidated immediately; a late reply cannot apply during that delay. Explicit
`refresh()` bypasses this event cooldown and cancels any trailing timer. Initial
hidden/loading documents wait for visible DOM readiness. Hidden/pagehide states clear admission, restore
safe owned properties, abort pending requests and disconnect observers/listeners
for tracked documents/frames. Pageshow resumes with fresh qualification. The public
controller's `refresh()` method is available for a future site's known session
transition integration; normal navigation already uses pagehide/pageshow. It does
not accept a preference or viewer argument. `stop()` restores safe ownership and
removes all controller listeners, timers and observers permanently.

Explicit `refresh()` and the existing lifecycle transitions clear admission and
restore still-owned overrides before fetching. Ordinary scheduled renewal instead
retains styling under the current, independently expiring descriptor.
The previous request is aborted, and generation checks reject its result even if
abort is ignored. Correlation must match the latest request. Failed HTTP, redirects,
wrong MIME, oversized/malformed JSON or invalid schemas yield no styling. Requests
time out after 10 seconds, including body parsing; completion checks elapsed time
even if the timeout callback was delayed. No automatic network retry follows failure/suspension.

The existing **30-second** maximum age is preserved, rather than adopting the
suggested 60-second ceiling. Age uses `performance.now()` from request initiation;
network time consumes that lifetime. Already expired responses fail closed. Each
accepted descriptor schedules renewal at **25 seconds from request start**, plus
a separate hard-expiry timer at **30 seconds from that same start**. Starting a
replacement never moves the old deadline. Only a validated replacement installs
its own request-start deadline and timers. Unchanged replacements make no color,
Density-class or resize writes; changed dimensions are applied by difference.

Expiry releases still-owned styling even while a replacement is pending. It does
not cancel that replacement: a reply within its own 10-second timeout and 30-second
age limit can establish a new grant after the old one expires. Response handling
also releases an expired old grant before replacement if its timer was delayed.
A DOM/load scan finding expiry follows the same rule. With no pending request,
expiry can start one refresh only if visible, active and still tracking qualified
players. Requalification precedes renewal, including removal before observer delivery.
This produces approximately one scheduled renewal per 25 seconds on an active
page (about 20% more requests than the earlier 30-second schedule); there is no
independent interval/status poll, hidden
page polling, failure polling or absent-player renewal. Focus/pageshow can request
earlier revalidation. Server-side changes are discovered on that next request;
instant remote logout, revocation or SNORDIAN notification is not implemented.

In-memory descriptor state is discarded on invalidation and never enters page,
localStorage, sessionStorage, a service worker, generated CSS or an asset cache.
Browser timer throttling can delay a timer callback; hidden/pagehide restoration
and revalidation on return prevent delayed callbacks from authorizing new styling.

## Main-player qualification

Every candidate must independently establish all of the following:

- Exactly one matching parent-document DIV or internal iframe anchor for its ID,
  with no nested main-player/wrapper ancestor or editor exclusion marker.
- Matching public `H5PIntegration.contents['cid-N'].library` and main manifest
  identity; dependency-library names are never ownership evidence.
- An initialized `.h5p-content` with the exact ID and one direct
  `.h5p-container.h5p-standalone` child.
- One registered main `H5P.instances` entry for that ID, with exact library machine
  name/version, no parent/subContentId, and true `isRoot()` when exposed. Nested
  `newRunnable()` children are not independently registered/styled.
- A stable root/document/container/API/instance tuple. An already observed instance
  cannot qualify another root. Duplicate anchors/instances are ambiguous.
- Native Theme readiness: `.h5p-theme` on the main container and a nonempty computed
  native main color property. Native Theme classes are never added or forced.

An internal frame additionally requires the canonical ID/wrapper, initialized
iframe class, literal `src="about:blank"`, no srcdoc or sandbox, an accessible
`html.h5p-iframe` document whose URL is about:blank or exactly the parent's URL
(ignoring fragments, accounting for document.open), exactly one direct body root, the expected
frameElement/defaultView, the parent's same integration object and
`H5P.externalEmbed === false`. A non-about:blank src is rejected before any child
document access. Unavailable cross-origin contexts fail closed. Same-origin URL
embeds with an actual src are also excluded; only Core-written internal frames
qualify in this phase.

The controller watches the parent document and at most one internal document/load
listener per manifest candidate (at most 65 mutation observers total). Mutation
callbacks coalesce to one scan per 25 ms; there is no DOM polling interval. Load
events and lifecycle/refresh events can start seven finite initialization retries:
50, 100, 250, 500, 1000, 2000 and 4000 ms. These cover late instance registration
without DOM mutations. Resource-load events handle delayed stylesheets. Replacement
documents release obsolete observers, and removed frames release load listeners.
Rewriting an existing Document's documentElement also disconnects its old observer
and replaces its load listener before observing the new element tree.
Late changes beyond this retry window need a DOM/load/lifecycle/refresh event.

Diagnostics are available through `diagnostics()` as public content IDs and fixed
status codes (`unqualified`, `awaiting-styles`, `qualified`, `ownership-lost`,
`instance-root-changed`). No response values, errors, profiles or private identity
are logged. A qualified diagnostic describes ownership/readiness, not admission.

## Color validation and conditional restoration

The browser requires the exact transport/envelope/appearance/Theme/Density shapes,
including consistent Original metadata. Density is consumed independently. Core
requires an empty color map; suspension requires null Theme/Density and empty CSS.
Unknown fields and inconsistent combinations are rejected.

The fixed 33-property allowlist matches `Color_Properties::supported()`; automated
tests compare the inventories. Declaration parsing rejects unknown/duplicate
properties, extra declarations, selectors, braces, URLs, priorities, whitespace
injections, malformed/trailing values and unrecognized expressions. Three/six-digit
hex colors are accepted. The only non-hex forms are the exact pinned color-mix and
dark rgba literals for `--h5p-theme-contrast-cta-light`. Built-in IDs/versions and
full property counts are validated; custom IDs follow the existing syntax. The
unchanged PHP serializer remains the authority proving built-in/custom inheritance
provenance. The descriptor intentionally does not expose custom base provenance;
the browser restricts expressions to this tiny safe literal inventory without
re-resolving preferences or inventing a second inheritance policy.

Color writes use only `CSSStyleDeclaration.setProperty()`/`removeProperty()` on each
qualified root. No innerHTML, dynamic stylesheet or selector injection is used.
Compatible descendants inherit from the main root.

Before the first actual write, capture that property's exact inline value and
priority. Application uses empty priority and records the CSSOM's resulting value
and priority. A matching existing value is not claimed or rewritten. Repeated
application is idempotent. Theme switches release removed properties; Core,
suspension, failure, expiry, visibility changes and stop release all still-owned
properties. Restore the original value/priority only when the current pair equals
Papi Jo's last written pair. Otherwise relinquish that property and permanently
block reacquisition on that root for this controller's lifetime. Unrelated
properties and third-party changes remain intact. Original `important` priorities
are restored, but new Theme overrides never request `important`.

Disconnected roots can be restored only if their original ownership tuple can
still be proved with no competing ID anchor. Safe reinsertion qualifies afresh.
A changed document/instance/container or newly ambiguous owner is never mutated,
even to restore an earlier value: its old root is quarantined and weakly tracked
so it cannot be recaptured with a stale baseline. Such a root may retain its last
override until the application replaces/removes it; this conservative limitation
protects the new/ambiguous owner. A discarded iframe document is not touched after
replacement. Third-party writes that reproduce the exact same value and priority
are observationally indistinguishable; no browser-only ownership marker can infer
that unseen intent.

## Native Density strategy, ownership and resize

Core API 1.28's `js/h5p.js:107-109` adds `h5p-` plus the configured Density to the
outer `.h5p-content`, defaulting to Large. `styles/h5p-theme-variables.css` supplies
native scaling (0.6/0.8/1 for Small/Medium/Large). No Core stylesheet, integration
setting, dependency cache or replacement spacing CSS is changed.

The validated descriptor's `density.selection` is the only browser Density input.
Small, Medium and Large mean exactly one `h5p-small`, `h5p-medium` or `h5p-large`
token on the qualified outer root. Original leaves an untouched root untouched.
Before the first actual override, capture the current initialized native token set,
including no token or several tokens. Return to Original restores that exact set,
including initialized Large; it never substitutes Small. Capture is delayed until
an actual write so legitimate native changes while untouched remain authoritative.
A matching native selection is neither written nor claimed. All other classes stay intact.

The existing full main-player ownership proof is shared, with independent readiness:
Density also requires `.h5p-theme` on the direct standalone container, nonempty
computed `--h5p-theme-scaling`, Core's trigger helper and a supported instance event
API. Missing styles/API use the existing finite initialization retries and load/DOM
events. No new observers, listeners, controller, timers or polling are added.
Color readiness does not gate Density; Density readiness does not gate colors.
Explicit Core releases colors while allowing non-Original Density. Theme-only and
Density-only descriptor refreshes preserve the other dimension's resulting selection.
Legacy Theme Density is never read or inferred; extra descriptor fields are rejected.

Keep the captured set and last written set separately from color-property ownership.
Before changing/restoring an owned Density, compare the entire native token set with
the last written set. Any mismatch relinquishes Density ownership and weakly blocks
reacquisition for that root for this controller's lifetime, including pause/resume.
Color ownership continues independently. Unrelated class changes do not cause a
conflict. On Original/invalidation/suspension/cleanup, restore only an unchanged,
still-owned set on the same proved player tuple. Detached DIV roots can be restored
when unique ownership remains provable, but are not resized. Replacements need their
own registered instance and baseline. Changed/ambiguous tuples are quarantined;
discarded iframe documents are never touched. As with colors, exact same-state
foreign writes or changes made and undone between scans cannot be distinguished.

After an actual connected-root change, call that realm's
`H5P.trigger(qualifiedInstance, 'resize')`, including when restoring Original. Recheck
the tuple first. Do not dispatch a global/window resize, resize child instances,
write iframe dimensions or patch Core. The installed Core registers main instances
after attaching its internal iframe resize listener (`h5p.js:264-299,364-367`). That
listener coalesces callbacks, skips fullscreen, and updates the frame height after
1 ms using body.scrollHeight. `H5P.trigger` (`h5p.js:2304`) supports modern instance
events and the deprecated jQuery event API. Papi Jo dispatches once per actual class
set change; no-op scans do not resize. Core/library callbacks own subsequent sizing.

A synchronous resize exception restores still-owned Density, blocks further Density
attempts for that root, and leaves Theme and other players operational. Foreign
native changes made inside the failed callback survive. Asynchronous Core/library
errors are beyond the synchronous dispatcher boundary. A library can ignore the
event or not consume native spacing; class application alone cannot guarantee
visible effects or correct library layout without browser acceptance.

Explicit refresh and conservative lifecycle transitions still revoke admission and
restore both dimensions before requesting a fresh descriptor. Ordinary scheduled
renewal retains the current appearance only within its original validity: a
Theme-only replacement leaves matching Density untouched, and a Density-only
replacement leaves matching colors untouched. Failure/suspension immediately releases
owned styling; a pending renewal never extends the old grant. The 30-second contract, generation ordering,
expiry, visibility, pageshow/focus, retry limits and complete cleanup remain shared.
Diagnostic codes still describe the existing ownership/Theme readiness boundary;
separate production Theme/Density status reporting remains deferred.

## Coexistence and deferred work

Active SNORDIAN prevents server attachment and actionable descriptors through the
existing detector/policy, even with the experimental constant enabled. Fresh
policy checks suppress an already queued tag after late suspension. A previously
admitted page receiving suspension clears admission and releases only still-owned
color and Density overrides. SNORDIAN-owned value/priority and native token changes
survive restoration. No SNORDIAN
file, hook, setting or activation state is changed; administration/preferences
remain independent and existing regressions verify them.

Real Firefox/WordPress playback, actual computed CSS serialization, stylesheet
timing, fullscreen layouts, shared page caches/CDNs, CSP/optimization plugins and
live account switching remain untested. DOM/CSSOM doubles verify controller logic,
and disposable loopback HTTP fixtures verify transport authentication and headers;
neither is browser acceptance. Unsupported libraries remain native; native Density
effects depend on whether a library consumes Core's scaling variables.
External embeds, editors/admin previews, duplicate IDs and uncertain owners stay
excluded. Phase 2B-2D source verification is recorded in
[its report](phase-2b2d-verification.md). Phase 2B-2E source qualification and the
future deployment plan are recorded in [its report](phase-2b2e-verification.md).
No deployment or Firefox runtime acceptance was performed here. After failure or
suspension there is no expiry renewal; a lifecycle event or explicit refresh is
required to recover. Players reinserted after an absent-player expiry likewise
need one of those refresh signals. No live session or policy notification hook is
installed. Timer throttling/blocked execution may delay restoration itself; 30
seconds bounds descriptor authority in controller checks, not a hard real-time
guarantee about pixels in an unresponsive browser.

## Runtime flash plan — Step 1 source verification (9 November 2026)

Step 1 only is implemented in source. No readiness gate, head/footer attachment
change, return-tab/BFCache stabilization, preference-storage change or PHP change
is included. First navigation and conservative return-tab flashing remain for
later authorized work. Explicit refresh/focus/visibility/pageshow still revoke
and restore immediately; the new retention path is ordinary scheduled renewal only.

Targeted final results:

| Command | Assertions passed |
| --- | ---: |
| node tests/runtime/theme-controller.js | 124 |
| node tests/runtime/density-controller.js with installed H5P source argument | 202 |
| node tests/runtime/lifecycle-controller.js | 329 |
| PHP 8.4.22 -n tests/runtime/attachment.php | 28 |
| PHP 8.4.22 -n tests/runtime/transport.php with installed H5P source argument | 329 |
| PHP 8.4.22 -n tests/runtime/qualification.php with installed H5P source argument | 185 |
| PHP 8.4.22 -n tests/runtime/coexistence.php | 70 |

The lifecycle suite adds 169 assertions covering 25-second renewal; independent
30-second grant expiry; responses before/at/after the old expiry; unchanged
appearance with zero color/class writes or resize calls; differential Theme-only,
Density-only and combined changes; current-request timeout/body parsing despite
delayed timers; stale generations; foreign styling; suspension; and conservative
focus/pageshow/visibility restoration. Other suites retain all original assertions;
only expiry descriptions were clarified to acknowledge a pending early request.

One development test initially overshot a timing boundary because its simulated
mutation scan had advanced the clock by 25ms; the test offset was corrected.
The coexistence fixture initially failed its dependency classification under the
known Windows restricted-token readability limitation, then passed all 70 checks
with normal permissions. No implementation/security assertion was weakened.
These are isolated fixtures and read-only H5P source checks, not Firefox playback
or live database tests. No deployment, live configuration/data change or Git
operation occurred. PHP sources are unchanged, retaining their existing PHP 7.4
compatibility target. A future authorized deployment must handle asset cache
versioning; no installed asset was replaced in this step.

Stop after Step 1 for review and explicit authorization before deployment or Step 2.
