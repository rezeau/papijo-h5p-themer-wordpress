# Runtime Theme/Density flash — implementation plan

Updated 10 November 2026. **Steps 1, 2 and 3 COMPLETE for the agreed local acceptance.example.test acceptance scope.** Step 3 deployment and guarded Apache checks passed; the user reports all seven final Firefox checks PASS. This is local acceptance, not a release or a claim of instrumentally confirmed BFCache activation.

## Final local acceptance and follow-up

- Step 1 complete: periodic renewal accepted; final uninterrupted viewing of at least two minutes showed no periodic flashing.
- Step 2 complete: initial appearance, nested content, same-origin iframe and keyboard acceptance passed in the earlier local baseline.
- Step 3 complete: 45-second return without Mint/incorrect-Density flash, rapid switching, browser-history return, nested QuestionSetPapiJo, same-origin iframe, keyboard/focus and uninterrupted viewing all passed in user-run Firefox testing.
- Browser-history visual acceptance is PASS. Actual BFCache restoration was not instrumentally confirmed and remains unverified. Minor layout movement was acceptable; similar movement was observed with SNORDIAN. No layout optimization is requested.
- The brief “Loading activity appearance” notice is accepted.
- The user subsequently changed the WordPress site theme. Compatibility with that new theme is **PENDING**, not PASS; acceptance above applies to the tested configuration before that change.

Step 3 installed only assets/js/theme-controller.js and includes/Runtime/class-theme-attachment.php. The complete 79-file inventory, other 77 files and original Windows security metadata were verified; database/configuration were unchanged by deployment. Private rollback backup: PRIVATE_EVIDENCE_OMITTED No rollback was needed. Exact installed/rollback hashes and deployment evidence are recorded in [the Step 3 report](runtime-b3-step3-verification.md).

Deferred, without implementation authorization: verify the new WordPress theme; instrument actual BFCache use/first restored frames; broaden slow/failing transport, blocked assets/CSP/optimizers, accessibility/screen-reader, responsive/fullscreen and browser coverage; separately authorized live identity/permission/SNORDIAN transition tests. Automated safety coverage remains evidence, not a claim those manual cases passed. Identifying the original Mint CSS producer remains a diagnostic follow-up, not a local completion prerequisite.

Release preparation remains separate: review the final source diff and runtime enablement/default-disabled bootstrap distinction; rerun appropriate checks for the chosen release candidate; check packaging, documentation/changelog/versioning, administration/Export-Import preservation and a clean-install/upgrade/rollback procedure. No commit, tag, release, publication or further deployment is authorized here.

The original 9 November diagnosis/design and sequence below are retained as **historical planning context**. Present-tense baseline descriptions and original authorization gates there do not describe current completion status.

## 1. Established diagnosis and remaining evidence

The user confirms Firefox initially paints global Mint, then personal Colorblind Friendly, with personal Small Density and SNORDIAN inactive. The previous read-only database inspection agrees with those selections and Subscriber permission. This is user-observed browser evidence, not a Codex browser reproduction.

Verified source behavior:

- Theme_Attachment currently enqueues the parent controller in wp_footer at priority 19. The attachment contains public player metadata, not personal appearance.
- Profile_Service::effective() and Publication_Policy::resolve_learner() resolve one effective Theme: an available permitted personal choice, otherwise global, then Core. Appearance_Service serializes that result and independent Density. There is no deliberate global-response-then-personal-response pipeline in this code.
- The controller fetches the authenticated descriptor asynchronously and waits for a uniquely qualified, initialized main H5P instance and loaded Theme CSS before applying it. Nothing currently prevents an earlier palette being displayed.
- refresh(), resume(), pause() and expiry call invalidate(), which clears the descriptor, restores owned colors/Density and may resize. focus, visibilitychange and pageshow can repeat this even when appearance is unchanged. Coalescing limits requests, but restoration occurs before the coalescing check.
- Ordinary apply()/applyDensity() already avoid identical writes; mutation scans themselves are not evidence of an infinite reinjection loop. The controller changes inline custom properties and native classes, rather than repeatedly inserting stylesheet elements.

The previous targeted JavaScript runs passed 124 Theme, 202 Density and 160 lifecycle assertions. Several explicitly require restoration during refresh, hidden-state transitions and expiry. They certify the old behavior, not absence of browser flashes. No test suites were rerun to prepare this plan.

### Source-level cascade and ordering

Installed official H5P add_assets() obtains dependency assets, runs h5p_alter_library_scripts/styles, then enqueues DIV assets or places iframe URLs in H5PIntegration. Its print_h5p_content filter runs before the resulting player HTML is returned to the page.

For internal iframes, H5P writes a new document. getHeadTags() emits Core styles, content styles, Core scripts, content scripts and shared integration settings, in that order. Parent CSS cannot style the child root, but can conceal its iframe boundary. H5P then adds its native Density class from H5PIntegration.theme.density, defaulting to Large, before constructing/registering the instance. Papi Jo acts later and requests another resize after an actual Density change.

Papi Jo's normal inline root declarations override ordinary inherited/stylesheet custom properties; competing important declarations, foreign inline writes and child-specific variables still matter. Source order alone cannot identify the winning rule in a particular activity.

**Unresolved:** the actual rule or earlier script producing the initial Mint colors. The reviewed Papi Jo path does not establish it. Existing H5P/content CSS, other customization, cached assets or earlier runtime activity are hypotheses only. Do not clear caches, change plugins or alter defaults to investigate without authorization. A passive Firefox capture should identify the initial winning variable declarations, stylesheet URLs, inline values and appearance responses. The correction must not depend on a guessed Mint producer.

## 2. Recommended architecture

Keep the current resolver, authenticated endpoint, descriptor schema, palette validation, ownership proof and native Density implementation. Add a public presentation-readiness layer and separate request lifetime from DOM ownership lifetime.

Invariants:

1. Personal values never enter shared page HTML, local/session storage, cookies or a second preference resolver.
2. Descriptor freshness remains strictly less than 30 seconds from request start, including download/parsing time. Starting another request never extends an old grant.
3. A response can act only on its current generation, visible document and still-qualified player tuple. Old-page, aborted and superseded responses remain inert.
4. Explicit identity/policy uncertainty, suspension and hard expiry cannot keep an old personal appearance visible merely to avoid flashing.
5. Readiness flags authorize presentation only. They never substitute for server admission or DOM qualification.

### A. Establish readiness before first visible paint

**Earliest reliable boundaries:** eligible-front-end wp_head for public CSS/bootstrapping; print_h5p_content for identifying the actual supported player before its markup reaches the browser. A footer hook or MutationObserver alone is too late for this guarantee.

Proposed attachment flow:

- Enqueue a small render-blocking readiness stylesheet and move the existing controller asset to synchronous head loading on runtime-enabled, admitted ordinary frontend pages. Do not add async/defer to this bootstrap. Reuse the existing admission/context rules; no profile read is added here.
- At head time the player registry is normally empty. Do not pretend candidates are already known or pre-scan shortcodes. Allow the public head bootstrap on eligible pages; a page without players makes no appearance request. This accepts small static-asset overhead in exchange for reliable ordering.
- The head controller installs the bounded fail-open watchdog before arming its document-level readiness capability. Static CSS only conceals explicitly marked player boundaries while that capability is armed. If the controller is blocked or JavaScript disabled, the capability is never armed and players remain visible.
- Add an attachment-owned print_h5p_content filter after the existing registry observer. Mark only the exact HTML/ID already accepted by the registry, with a plugin-owned presentation attribute. Keep H5P's required classes, IDs and wrapper structure intact. Do not wrap roots in extra H5P containers.
- Keep the complete public candidate manifest at the footer, emitted as an escaped JSON data attribute on an inert hidden element. The head-loaded controller consumes it at DOM readiness, then starts the existing single appearance request. No executable inline bootstrap or viewer-specific manifest is required. The current manifest validation remains authoritative.
- On DOM readiness, immediately release markers not represented by a valid final manifest, including duplicate-ID revocation, over-limit pages, late suspension and missing footer metadata. The watchdog also covers missing footer/controller startup failure.

The existing registry can continue returning original HTML; the subsequent attachment filter adds presentation markers. Expose only a narrowly named registry attachment-admission query delegating to its existing policy/context checks, so head eligibility does not create a second detector. This is an explicit change to the old attachment/HTML contract and requires new tests.

**Supported coverage:** normal frontend DIV players and same-origin H5P-generated about:blank iframes. Gate the DIV root or the parent iframe wrapper, respectively. Independent/external iframe embeds, editor/admin previews and arbitrary cross-origin players remain excluded. An H5P activity on an ordinary standalone frontend page is covered; a separate external embed document is not newly supported.

**Layout/accessibility:** use visibility-based concealment of the activity, not display:none or removal, so H5P can initialize and measure. Hide descendants defensively as well; third-party visibility overrides must be tested. Keep a separate accessible loading/status element outside the concealed subtree and mark the boundary busy. Pending activities must not receive keyboard focus, including focus entering the iframe. Do not move focus or reset an interacting activity merely because renewal begins. If inert is used, prove it does not break H5P initialization/sizing in Firefox; retain a tested focus fallback. Do not use opacity alone, which leaves invisible interactive controls.

An internal iframe initially has height 1px, so visibility alone cannot preserve its final footprint. Reserve a modest public minimum placeholder height (proposed 12rem, capped for small viewports), retain an already-known height on revalidation, and let H5P own final iframe sizing. Do not claim zero initial page layout shift when the final activity height is unknowable. Never store measured sizes or user appearance in preferences.

**Reveal condition:** current permitted descriptor; exact main-player proof; both requested appearance dimensions applied or legitimately no-op (Core/Original/matching native values); necessary native Density resize dispatched; iframe sizing settled sufficiently to avoid revealing the old layout. Use a bounded next-render-frame check following the existing resize callback path, not a fixed cosmetic sleep or a new ResizeObserver/polling loop. Record desired state separately from owned mutations so matching-native values count as ready. One slow activity must not hold other ready activities hidden.

**Bounds/fallback:** retain the 10-second fetch/body timeout; cap an initial readiness window at 15 seconds from gate arming, without extending it for event bursts or retries. Continue the existing finite H5P discovery retries and load/mutation events within that window. A missing/unsupported API or unknown owner is never force-styled. On timeout, malformed response, suspension or readiness failure: restore only provably owned overrides, remove the gate/busy state and expose native/foreign styling, with a concise nonblocking fallback notice. Never label this fallback as a successful personal rendering. Do not restart a failed first-render window automatically or allow late responses to produce a new surprise transition in the same failed generation.

The initial watchdog covers a head script that loaded but whose later controller work fails. If the head script never executes, CSS is unarmed and content is visible. A frozen browser/event loop cannot provide an absolute wall-clock reveal guarantee; test recovery as soon as execution resumes. CSP/optimizers must preserve required head order; incompatible rewriting is a compatibility failure, not grounds to weaken security or hide indefinitely.

### B. Lifecycle without routine teardown

Split the current invalidate() responsibilities into request cancellation/generation management and owned-style release. Preserve existing value/priority/class ownership comparisons and quarantine rules.

| Situation | Proposed action |
| --- | --- |
| Mutation scan, initialization retry, already handled duplicate event | Requalify; preserve valid styling; no request, writes or resize solely for the event. |
| Ordinary scheduled renewal while visible and grant remains valid | Start one replacement request; retain current appearance only until its original deadline. Apply a validated replacement by difference, then replace its deadline. |
| Actual focus return, hidden-to-visible, pagehide/pageshow/BFCache, explicit refresh/account-policy transition | Treat identity as uncertain. Gate before exposure, cancel obsolete work and require a new authenticated response. Do not infer unchanged identity from matching Theme values. |
| Suspension, malformed/failed response, confirmed permission denial | Revoke actionability; restore only still-owned styling; ungate to native/foreign fallback. No automatic failure polling. |
| Hard expiry without replacement | Gate, invalidate the old grant and restore owned styles at the deadline. A pending request may finish only within its own timeout/freshness limits. Otherwise bounded fallback. |
| Removed/replaced/ambiguous root or foreign overwrite | Retain existing quarantine/relinquishment rules; never reclaim foreign styling. Release only presentation state proven to belong to this controller. |

For focus/visibility/pageshow bursts, treat all events for one return as one transition: one generation change and one request, not cancellation on each signal. Head bootstrap records initial/return transitions; footer initialization and later pageshow must not cause an avoidable second initial request. Same-page focus while an earlier return is pending joins that pending validation. A focus event alone cannot prove the account stayed unchanged.

On pagehide, arm the presentation gate before a possible BFCache snapshot. Keep qualification/ownership records as needed, cancel requests, and stop active observation. On restoration requalify the real document/instance, never apply an old descriptor across the page boundary. Firefox must verify whether the stored snapshot and first restored frame honor the gate; pageshow alone is too late to guarantee that no old pixels are painted. Never blanket-hide an already focused/visible activity merely because unrelated focus bubbles arrive.

**Expiry scheduling decision:** use the same endpoint/request mechanism to renew five seconds before expiry (25 seconds after request start), while retaining the separate hard deadline at 30 seconds. A single scheduler can select the next renewal/deadline action; the existing request timeout remains independent. Permit only one pending request. A successful response gets its own request-start deadline; a failure does not extend or retry the old grant. No renewal while hidden, after failure, or without qualified players.

This introduces earlier scheduling, not another preference channel or a second fetch per renewal. It increases the steady-state maximum request rate from about one per 30 seconds to one per 25 seconds (about 20%); this is an explicit tradeoff requiring approval. Do not describe it as zero additional asynchronous work. Without an early replacement request, unpredictable network latency and a strict 30-second expiry make a perfectly uninterrupted renewal impossible: the safe alternative is a bounded gated interval at expiry. Do not silently choose stale styling instead.

Identity, permissions, selection/publication and SNORDIAN changes are detected by the existing freshly authenticated endpoint response. There is no server push or immediate detection of an edit in another tab; ordinary visible-page detection is bounded by renewal/expiry, return triggers revalidation, and explicit refresh remains an immediate revocation boundary. No user ID/session witness is added to shared HTML. A same-cookie identity change that generates no detectable browser signal remains the existing bounded-detection limitation; this plan does not claim to solve it with request IDs.

On an unchanged successful revalidation, apply()/applyDensity() should make zero appearance writes and zero resize calls. If Theme changes but Density does not, do not restore/reapply Density. If Density changes, dispatch one connected-root resize after the actual class-set transition. H5P's own resulting callbacks remain outside that count.

## 3. Boundaries, alternatives and risks

Likely production changes only:

- assets/js/theme-controller.js: head bootstrap/public manifest consumption, readiness state, request/ownership separation, lifecycle scheduling and reveal cleanup.
- includes/Runtime/class-theme-attachment.php: head asset ordering, inert footer metadata, validated presentation markers.
- includes/Runtime/class-playback-registry.php: narrowly expose the existing attachment-admission decision; retain player validation and ambiguity revocation.
- New assets/css/runtime-readiness.css: scoped concealment, placeholder/status layout and reduced-motion-safe presentation, with no palette values.

No changes to Profile/Role/Publication/Transfer/Storage services, endpoint schema, serializers, runtime preference resolution, database schema, atomic revisions, administration or Export/Import. Preserve PHP 7.4 syntax and the existing WordPress/H5P admission requirements. No WordPress, H5P Core or SNORDIAN edits. A public cacheable readiness stylesheet is one justified static request; moving the existing controller to head replaces its footer request. All players continue sharing one appearance request per generation.

Rejected alternatives: personal CSS embedded in cacheable HTML (identity leakage/staleness); local/session-storage preference caching (another stale authority); global-first provisional styling (preserves the reported defect); arbitrary sleeps or opacity-only concealment; H5P Core patches; retaining personal styling indefinitely on failed validation. Earlier server-side personalized rendering may remove waiting rather than conceal it, but requires a larger cache/privacy integration and is not the smallest correction.

Primary risks: earlier asset order/CSP/optimizer interaction, head overhead on pages without H5P, inaccurate reserved height, H5P libraries measuring hidden content differently, focus/accessibility disruption, BFCache screenshots, descriptor-expiry regression and accidental foreign-style reclamation. These are acceptance gates, not reasons to expand storage or add another resolver.

## 4. Targeted verification

Change old assertions in tests/runtime/theme-controller.js, density-controller.js and lifecycle-controller.js that require restoration before every ordinary renewal. Replace them with retention only during a valid grant, zero mutation/resize for equal replacement, and mandatory release at hard expiry/failure/suspension. Keep their stale-response, owner-quarantine, original-native-state and timeout checks. Update tests/runtime/attachment.php's footer-only/no-additional-hooks assertions for the explicit new head/public-manifest contract. Retain existing runtime/admission/transport/coexistence contracts.

Add focused readiness tests in tests/runtime/readiness-controller.js and tests/runtime/readiness.php; extend existing fixture support rather than inventing a second runtime controller. Cover:

- Gate armed before supported markup; no appearance request on zero-player/excluded pages; duplicate/over-limit markers released; missing footer/head asset/JS-disabled fail-open.
- Slow AJAX and slow H5P/CSS/iframe readiness independently; only personal appearance is exposed on successful reveal; 15-second total bound and late-result rejection.
- Theme-only, Density-only, combined, Core and Original; correctly ready no-op selections; independent multi-player reveal and reserved layout.
- Startup and return event bursts; pending requests not repeatedly aborted; BFCache/new document qualification and changed roots.
- Responses before/at/after expiry, expiry during parsing, early-renewal failure, hidden timers, backward/invalid elapsed times and request-start lifetime accounting.
- Identity/permission/selection/publication changes, SNORDIAN suspension/resumption, foreign priority/class writes, removal, document.open() replacement and ambiguous IDs.
- Equal replacement: zero style/class writes and zero plugin resize dispatches; actual Density change: one dispatch; bounded observer feedback and no permanently concealed content.

Fixture tests can verify state/order, not real first paint. Required Firefox acceptance on the authorized test site: frame-by-frame capture of Mint versus Colorblind Friendly during navigation with throttled AJAX; slow initialization/CSS; first and restored BFCache frames; short/long tab absences and focus bursts; visible activity across multiple expiry cycles; actual iframe height/fullscreen; keyboard and screen-reader behavior; JavaScript/asset failures and timeout fallback. Identify the initial Mint rule from computed-style/network evidence. Manual account/permission/SNORDIAN changes require separate authorization because they alter live state. Existing Theme administration and single/selected/all Export/Import receive a small preservation smoke check, not a full phase-suite rerun.

## 5. Reviewable sequence and rollback

| Step | Files and intended result | Main risk; verification/acceptance | Rollback |
| --- | --- | --- | --- |
| 0: evidence/contract | Planning only; capture the initial Mint rule and approve timeout, placeholder and early-renewal tradeoff. | No browser reproduction yet. Confirm affected URL/embed and first-paint trace without altering selections. | No code to revert. |
| 1: independent B subset | theme-controller.js; the three existing JS runtime tests; docs/theme-controller.md and docs/runtime-transport.md. Separate request cancellation from release, make ordinary valid-grant renewal differential, add early renewal/hard deadline; retain conservative focus/hide cleanup initially. | Expiry/auth regression. Automated boundary/failure/ownership tests; Firefox unchanged appearance over 30-second cycles. This does not claim to fix return-tab flashes or Problem A. | Restore reviewed prior controller/tests/docs; no data rollback. |
| 2: A readiness | theme-controller.js, class-theme-attachment.php, class-playback-registry.php, new runtime-readiness.css; attachment.php and new readiness tests. Establish pre-paint gate and successful coordinated reveal, fail-open paths and accessibility. | First-paint timing, layout, CSP and invisible-content failures. Automated markup/state tests plus Firefox navigation/slow-network/JS-disabled acceptance. | Restore the whole matching attachment/controller/CSS bundle; verify no pending markers or stale cached assets. |
| 3: complete B returns | theme-controller.js plus lifecycle/density/readiness tests and controller docs. Gate one actual return transition, revalidate once, retain qualified ownership for differential same-result application; pagehide/BFCache handling. | Account transitions, BFCache stored pixels and focus. Boundary/adversarial tests plus Firefox return-tab/account/coexistence acceptance. | Restore the complete step-2 bundle, reverting return behavior together with its tests/docs. |

Problem B can therefore be improved independently, reducing risk by isolating request/expiry behavior before head/markup changes. Its complete flash-free return behavior depends on the gate; it must not be advertised as solved by step 1 alone. Each step is source-first, reviewed/tested before separately authorized backed-up deployment. Runtime assets need a matched cache-busting version; do not accept a new PHP attachment paired with a cached old controller. Rollback restores matching code/assets only and never restores preferences/database dumps.

## Historical architecture decision and original stop

**CONDITIONAL GO** for this scoped architecture, subject to approval of the early-renewal request-rate tradeoff and successful Firefox first-paint, timeout, accessibility and BFCache acceptance. **NO-GO** for a controller-only/footer-only promise of personalized first paint, relaxed expiry, or a claim that the Mint producer has been established.

The fix needs no preference-storage, schema or resolution-semantic changes. It reuses existing asynchronous transport; readiness/reveal and pre-expiry scheduling are explicit additions to lifecycle handling, not a new asynchronous preference mechanism. Waiting cannot be eliminated merely by concealing it; successful first visible activity content must nevertheless use the resolved personal appearance.

At the original planning checkpoint, only this document had been created and implementation awaited authorization. The final local status above supersedes that historical gate. **Current stop: documentation finalized; await further instructions.**


> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.
