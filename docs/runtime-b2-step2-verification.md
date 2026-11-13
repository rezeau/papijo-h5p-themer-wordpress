# Runtime B2 Step 2 — initial readiness verification

9 November 2026. **Source implementation and automated verification complete; not deployed.** Step 3 is not implemented. Step 1's reported Firefox uninterrupted-viewing PASS is user evidence; no Firefox or live WordPress test was performed in this step.

## Exact boundaries

Production (four files only):

- assets/js/theme-controller.js
- includes/Runtime/class-theme-attachment.php
- includes/Runtime/class-playback-registry.php
- assets/css/runtime-readiness.css (new)

Tests:

- tests/runtime/attachment.php (updated public delivery assertions)
- tests/runtime/theme-controller.js (two DOM-attribute methods added to shared fixture support; existing assertions unchanged)
- tests/runtime/readiness-controller.js (new)
- tests/runtime/readiness.php (new)

Documentation: docs/theme-controller.md, docs/runtime-transport.md and this report.

No Profile/Role/Publication/Storage/Transfer/endpoint/serializer/controller-resolution changes. No new preference channel, schema, database, credentials or viewer-specific HTML. No source or installed bootstrap change.

## Implemented behavior

Eligible frontend wp_enqueue_scripts priority 5 queues a blocking head stylesheet and the existing controller in the head. This hook runs before the normal wp_head stylesheet/script printers. Admission delegates to the existing playback registry; unlike footer discovery it does not need candidates yet. Async/defer/module bootstrap tags are rejected, and late suspension still suppresses the script. The public asset version is 0.1.0-readiness-2, independent of the untouched bootstrap version.

The registry still returns original H5P HTML and records exact validated markup. A subsequent presentation filter marks only that identical accepted HTML: the DIV root or internal iframe wrapper, with a hidden translated status sibling. It preserves the actual iframe bytes/identifiers and adds no enclosing H5P container. Arbitrary modified markup, external frames and excluded contexts are not marked. Final footer candidates retain the original public version/endpoint/players schema on an inert hidden element.

The head controller installs its 15-second watchdog before arming CSS. Only explicit pending markers are concealed with visibility:hidden, including descendants; activity layout remains measurable. Iframe wrappers reserve min(12rem, 50vh). With disabled/blocked JavaScript there is no armed class, no server-side inert/busy state and no visible loading status. This is not an estimate of final activity height or a zero-layout-shift promise.

At DOM readiness, malformed/missing/ambiguous metadata and orphan markers fail open. Valid candidates use one existing authenticated request. Each activity independently waits for a current descriptor, unique initialized main instance, Theme/scaling CSS and Density event API. Appearance is applied before two bounded animation-frame checks. Core/Original and matching-native values can be ready without owned writes. Internal frame height must be greater than 1px, stable across callbacks and equal to its current body's scrollHeight; H5P alone sets dimensions. No new polling loop, ResizeObserver or cosmetic sleep is introduced.

The loading status is outside concealed content; busy state is restored without overwriting a later foreign value. Visibility concealment prevents ordinary focus/keyboard access; actual browser/assistive-technology behavior still requires acceptance. CSS ensures a successful sibling's hidden loading status cannot reappear while another player waits.

Failure, suspension, request timeout, unsupported ownership or the total head-start deadline reveal explicitly labeled native/foreign fallback after restoring only proved ownership. Failed initial IDs are terminal for automatic personalization in that document; reload is the recovery path. If all affected players fail, the request generation is canceled, preventing even an otherwise fresh-age late reply from acting. A successful sibling retains its valid appearance. Deadlines do not restart on retries/events. Stop/pagehide also release initial presentation state; no return-tab gate is added.

## Preserved Step 1 and plan details

The existing independent 25-second renewal/30-second hard expiry, 10-second request/body timeout, differential writes, generation ordering and foreign ownership/quarantine checks remain. Completed players never re-arm initial readiness during renewal. All three Step 1 JS suites still pass without changing their assertions, including conservative focus/visibility/pageshow restoration.

Implementation details relative to the plan: head assets use wp_enqueue_scripts before wp_head printing, rather than manually printing assets in wp_head; initial readiness uses two bounded rendering-frame callbacks and a current body-height comparison for internal frames. These keep normal WordPress asset ordering and H5P-owned sizing. No extra production files were necessary. Static CSS is one additional public asset; there is no additional appearance request per player or second transport.

The initial Mint CSS producer remains unproven. Readiness intentionally does not depend on it. Failed readiness and JavaScript-disabled paths expose native/foreign appearance rather than guaranteeing personal styling; only successful reveal claims the resolved appearance.

## Automated verification

Final isolated PHP checks use PHP 8.4.22; the readiness/markup suite also passes on PHP 7.4.33. Installed H5P is read only as a source oracle, without CMS bootstrap or database access.

| Suite | PASS assertions |
| --- | ---: |
| JS Theme controller | 124 |
| JS Density controller with actual Core resize-source checks | 202 |
| JS lifecycle/Step 1 renewal | 329 |
| JS initial readiness | 188 |
| PHP attachment | 34 |
| PHP initial readiness markup/admission | 22 |
| PHP transport/context | 329 |
| PHP sequential identity/permission qualification | 185 |
| PHP SNORDIAN coexistence | 70 |
| PHP transfer administration | 92 |

**1,575 final assertions** in these suites, plus a separate 22-assertion PHP 7.4 compatibility run. PHP 7.4 syntax checks pass for both changed production PHP files; Node syntax checks pass. The PHP readiness harness internally reuses attachment fixtures; those hidden prerequisite assertions are not added again to the counts above.

Readiness checks cover slow transport/initialization/CSS, independent siblings, initial Core/Original and combinations, native Density/no-op, iframe sizes, total 15-second timeout and late-generation cancellation, 10-second transport timeout, missing/invalid metadata, startup never completing, malformed URLs, ambiguous roots, foreign priority, nested QuestionSet/Column-shaped descendants, no-player/unsupported API cases, busy/status behavior and zero further writes/resizes on unchanged renewal. PHP checks cover exact markup, nested-frame preservation, external exclusions, disabled/suspended delivery, duplicate revocation, public metadata and blocking head order. Existing Step 1 suites retain hard expiry, stale parsing/responses, permission transitions and conservative lifecycle assertions.

One new PHP test initially failed because namespace imports from its included fixture were not in the caller's namespace; explicit test imports corrected it. Other early tests informed the stable-height and hidden-status guards before final verification. No security assertion was weakened. Coexistence uses normal Windows permissions to avoid the documented restricted-token fixture readability issue.

## Browser-dependent acceptance and later deployment

Automated DOM doubles do not prove real first paint, pixel timing, keyboard exclusion, assistive announcements or library-specific measurement. Later authorized Firefox acceptance must cover: frame capture of Mint-to-personal navigation under throttled transport; CSS/fonts and slow initialization; DIV/internal iframe/nested QuestionSet/Column; correct first revealed Density and frame height; focus/keyboard/screen reader; JS/controller/CSS failure and 15-second fallback; responsive/fullscreen layout; optimizer/CSP ordering; multi-activity pages and uninterrupted viewing across renewal cycles. CSS blocked independently of script may prevent concealment, so that environment must be treated as a failed compatibility/visual acceptance result. No claimed fix for return-window/tab or BFCache flashes: those remain Step 3.

Later deployment requires separate authorization, a fresh live baseline and exact backups. Deploy the four-file production bundle together, including the new CSS; preserve the installed runtime-enabled bootstrap and every unrelated file. Invalidate changed PHP in Apache appropriately and refresh page/asset caches so new head markup, versioned JS and CSS are consistent. Verify bootstrap/activation/preferences and file hashes, then authorize browser acceptance separately. Do not deploy only the JS as in Step 1.

Rollback must restore the matching pre-Step-2 JS, attachment PHP and registry PHP, and remove only the newly deployed readiness stylesheet if authorized. Restore page/asset references and Apache PHP cache consistently; reload open pages to remove existing readiness DOM/controller state. Never restore database dumps or alter preferences for a code rollback.

No live deployment, database/configuration/activation/preference change, Git operation or Step 3 implementation occurred. **STOP for review and explicit authorization.**
