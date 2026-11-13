# Slow-network readiness investigation — 11 November 2026

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Investigation only. No production or installed files changed; no deployment, database access, WordPress bootstrap, commit, release or version change. The new JavaScript file is a characterization test of existing behavior, not a fix.

## Finding and remaining uncertainty

The current controller has a reproducible pre-existing slow-start failure: the total 15-second readiness budget begins in the blocking head bootstrap, but the appearance request starts only at DOMContentLoaded. Slow page resources can exhaust this budget before a request starts or before an otherwise healthy request, H5P instance, stylesheet or iframe sizing completes. An initial failure is terminal for that player's automatic personalization in this document. Original content can therefore finish loading later while both personal Theme and Density remain absent.

This reproduces the reported outcome without an AJAX/network error. It does not establish which branch ran in the user's Chrome session. The precise content ID, request timing, response and Console evidence are unavailable. A real transport failure, policy suspension, invalid response, missing/ambiguous initialization, lifecycle interruption or iframe layout mismatch can produce the same message. Do not classify a canceled AJAX request as the cause without checking whether the total watchdog canceled it.

The current fixed budgets are consistent with the 3G/cache-disabled symptom. They are the leading source-level explanation, not a measured diagnosis of https://acceptance.example.test/.

## Relevant code paths

All JavaScript line numbers below refer to assets/js/theme-controller.js in the investigated 0.1.0 source.

| Path | Current behavior |
| --- | --- |
| bootstrap, lines 100–158 | Arms concealment, sets deadline at head execution +15,000 ms (line 109), installs watchdog, then waits for DOMContentLoaded. After expiry, even later-valid metadata cannot start the controller. |
| finish/timeout, lines 110–133 | Releases the pending marker and owned busy state; emits the fallback status. The timer closes the initial gate. |
| readiness failure callback, lines 239–247 | Adds the ID to initialFailed, restores owned colors and Density. Cancels the outstanding request/releases the descriptor when no viable affected player remains. |
| qualify, lines 262–310 | Requires one exact initialized root, matching H5PIntegration/library and one main instance. Internal iframes require a canonical about:blank document and initialized child root. |
| densityReady and ready, lines 347–353 and 388–392 | Independently check native scaling/API and Theme-variable readiness. |
| scan, lines 423–479 | Skips initialFailed IDs (428). Applies each dimension independently (460–465), but initial reveal requires both readiness checks plus ownership/freshness checks (466–475). |
| reveal, lines 204–217 | Rechecks across two animation frames; internal iframe height must exceed 1px, stay stable and equal the child body's scrollHeight. |
| fetchAppearance, lines 535–574 | One same-origin POST with a separate 10,000 ms transport/body timeout. Non-OK, redirects, wrong content type, invalid JSON/schema, suspension or rejection also lead to fallback. |
| lifecycle, lines 576–627 | Pause/pagehide before successful readiness can fail open; returns use separate gate bookkeeping. Visibility/focus conditions also affect when requests can run. |

The 25-second renewal and 30-second descriptor hard expiry are separate from the initial 15-second concealment budget. They must not be extended to accommodate slow resource loading.

includes/Runtime/class-theme-attachment.php attaches the blocking head assets, inserts an accessible status sibling outside the concealed subtree, and emits the public footer manifest. It does not resolve preferences in public HTML.

includes/Runtime/class-playback-registry.php admits exact validated main-player markup. includes/Runtime/class-wordpress-context.php and the controller exclude external embeds/framed parent pages and editor/admin contexts. includes/Runtime/class-appearance-endpoint.php handles private session-derived transport and preserves suspension/context checks.

## Normal content versus embedded content

Both supported DIV content and WordPress's internal about:blank H5P iframe share the same head-start and request budgets. A displayed activity is not proof that it met the controller's earlier initialization/CSS/layout checks.

The installed official H5P source at h5p/h5p-php-library/js/h5p.js creates the internal iframe document at lines 381–407, inserts its stylesheet and script tags at 416–437, and lets Core resize from the child body's scrollHeight (around 263–295). An internal iframe therefore has a child-resource/initialization phase beyond the parent's DOM readiness. Delays there consume the remaining parent readiness budget. A permanent height mismatch can independently exhaust that budget even with a successful appearance response.

External iframe embeds (for example action=h5p_embed), cross-origin frames and WordPress oEmbed pages are deliberately excluded. Their absence of Papi Jo personalization must not be treated as the supported internal-iframe timeout bug. The browser evidence must identify which embed type was actually tested. Existing qualification/transport tests retain those exclusions.

## Relationship to the five UI refinements

Comparison with the deployment manifest captured before those refinements proves that the source and installed controller, attachment and playback registry are byte-identical to their pre-refinement versions. The controller SHA-256 is PRIVATE_VERIFICATION_DIGEST_OMITTED.

The old readiness CSS is an exact 573-byte prefix of the current 932-byte stylesheet. The only addition clips the loading-status sibling visually while retaining its accessible text. It does not select the activity root/iframe, change visibility concealment or placeholder height, alter Theme/scaling variables, send requests or change timers. Fallback status is explicitly excluded from that added selector. The internal iframe body-size check measures the child document, not this parent-page sibling.

Thus the reproduced terminal timeout behavior existed before the UI refinements. There is no direct source-level path from the added clipping rule to loss of both Theme and Density. Site-specific layout effects cannot be completely ruled out without browser evidence, but reverting the loading-label CSS is not justified by these results.

## Characterization tests and results

New test: tests/runtime/slow-readiness-investigation.js. Run from the repository root with node tests/runtime/slow-readiness-investigation.js.

23 deterministic scenarios / 239 assertions pass. For both DIV and internal iframe they establish:

- Normal startup applies both dimensions and dismisses status.
- DOM readiness at 16 seconds emits fallback without starting any appearance request.
- DOM readiness at 12 seconds leaves only three seconds; the total watchdog cancels a request whose valid reply arrives after 3.5 seconds, below its own transport limit.
- The independent 10-second request timeout produces the same message and ignores a late reply.
- H5P initialization or stylesheet completion at 14 seconds succeeds; completion at 16 seconds remains native with fallback, despite a successful early descriptor.
- Theme and Density prerequisites are independent while concealed; missing either at the reveal deadline restores both owned dimensions. Later load events do not recover the initial failure.
- A genuine network rejection produces the same status immediately.
- An internal iframe with a permanent body/height mismatch times out even though Theme and Density had already been applied while concealed.

Existing suites also pass:

| Command (PHP uses -n; root argument is WORDPRESS_ROOT/wp-content/plugins/h5p where required) | Assertions |
| --- | ---: |
| node tests/runtime/theme-controller.js | 124 |
| node tests/runtime/density-controller.js <H5P root> | 202 |
| node tests/runtime/lifecycle-controller.js | 329 |
| node tests/runtime/readiness-controller.js | 203 |
| node tests/runtime/return-controller.js | 60 |
| php -n tests/runtime/attachment.php | 34 |
| php -n tests/runtime/readiness.php | 24 |
| php -n tests/runtime/transport.php <H5P root> | 329 |
| php -n tests/runtime/qualification.php <H5P root> | 185 |

Including the new test: 10 suites, 1,729 reported assertions. DOM/clock fixtures do not run actual Chrome networking, CSS layout or H5P library initialization. The tests characterize timing boundaries; they do not prove which resource was slow on acceptance.example.test. PHP tests read official source without bootstrapping WordPress or connecting to a database. Existing server logs have no new relevant PHP errors, but cannot diagnose client-side deadlines.

## Precise Chrome evidence to collect

1. Open the exact failing page and record its full URL. Open DevTools before reloading. Keep the tested tab visible. In Network select 3G, enable Disable cache and Preserve log, then clear the old Network list. In Console enable Preserve log and clear existing messages. Reload once with Ctrl+R. Use the same steps for a normal DIV activity and a supported internal iframe activity, if available; record any different settings between runs.
2. Record roughly when the fallback message first appears. Let the page finish loading. In Network inspect admin-ajax.php requests whose Payload has action=pjh5pwp_appearance, distinguishing them from H5P embed/other AJAX requests. Record whether no such request exists, its Status, Start Time, Duration, Timing (queueing/stalled, waiting/TTFB and download), Initiator, Content-Type, and whether it was canceled. For completed JSON record appearance.admission.permitted and reason. A canceled request may be an effect of the outer watchdog; it is not proof of server failure.
3. Inspect the waterfall for theme-controller.js, runtime-readiness.css, h5p.js, h5p-theme-variables.css, h5p-theme.css and the tested library scripts/styles. Record their completion relative to DOMContentLoaded and fallback. The internal iframe has additional child resources; enable Group by frame if available. Check for 404/403, blocked/CSP, redirect and certificate errors. Do not change security or coexistence settings to obtain a result.
4. Save relevant Console errors and their stacks, and the Network evidence above. A Console with no errors does not rule out an intentional watchdog fallback: the controller catches errors and does not log reason-specific failure messages.
5. Run the read-only chrome-console-snapshot.js in PRIVATE_EVIDENCE_OMITTED from the top-page Console after the failure. Copy the resulting string's contents. It records public player IDs/manifest, current root/iframe/CSS state, limited controller diagnostics and navigation/resource timings. It sends no requests and changes no DOM. Do not infer timeout reason from diagnostics alone: the exposed statuses do not record the terminal failure cause. Resource Timing may omit canceled requests or child-frame resources; the Network capture remains necessary. Send status/timing/selected response fields rather than Cookie/Authorization headers.
6. Repeat once with No throttling while leaving Disable cache enabled. Compare the same activity and user session. Browser evidence is still required before changing production deadlines or recovery behavior.

If the first capture is ambiguous, use non-pausing Sources logpoints in the loaded theme-controller.js and reload. Do not use pausing breakpoints for timing diagnosis; stopping execution can itself cause deadline failures. Useful logpoint expressions at the current source lines are:

| Location | Logpoint expression |
| --- | --- |
| 109, head bootstrap deadline assignment | "PJ bootstrap", win.performance.now(), doc.readyState |
| 132, inside timeout() | "PJ total watchdog", win.performance.now(), doc.readyState, entries.size |
| 544, inside transport-timeout callback | "PJ request watchdog", now(), now() - start, request === pending |
| 559, completed-body age check | "PJ response age", now(), age |
| 561, descriptor validation result | "PJ descriptor", now(), !!parsed, parsed && parsed.suspended |

These change only DevTools instrumentation, not plugin files. Clear the logpoints after the capture. The initial bootstrap timestamp is more precise than assuming script resource responseEnd equals script execution time.

## Recommended correction, conditional on the capture

Do not increase/remove a timeout or change CSS based solely on this shared fallback message. First identify which deadline or prerequisite actually failed.

If the total watchdog is confirmed, the smallest conceptual correction is to distinguish resource-delay expiry from a terminal initialization/transport/policy failure. Preserve a finite fail-open concealment limit. If delayed resources subsequently become ready, any proposed recovery must requalify the same eligible player, obtain a fresh authorized descriptor, independently apply Theme/Density, and coordinate reveal safely. Bound that recovery; do not blindly retry genuine request failures or revive canceled/stale responses. Reusing the discarded descriptor or extending its 30-second lifetime is unsafe. Implementation details and the resulting visible transition require approval first.

This entails an explicit UX tradeoff: content revealed natively at the hard concealment limit may visibly change later when personalization recovers. Arbitrarily slow resources, a fixed concealment bound and guaranteed personalized first visible content cannot all be assured simultaneously. A resource-phase budget redesign is another option, but simply moving the same terminal timeout later postpones the failure rather than resolving this distinction.

If the 10-second request timeout or invalid/suspended response is confirmed, investigate the request/response or its server-side cause; a loading-CSS change will not fix it. A genuine failure should retain native fallback. If an iframe height/qualification issue is confirmed, target that exact compatibility condition without removing the 1px/ownership/freshness guards.

## Future fix regression requirements and normal-network risks

Retain these new characterization cases, updating expected recovery only for explicitly approved resource-delay cases. Add stale/late-response rejection during recovery, bounded retries, successful-sibling independence, fresh permission/SNORDIAN suspension, independent color/scaling readiness, Core/Original no-ops, iframe resize stability, DOM-ready-after-watchdog, missing/blocked script/manifest, genuine network/JSON failures, busy/status/focus restoration, hidden-tab/BFCache behavior and unchanged normal-network request/write/resize counts. Real Chrome 3G/no-cache and normal-network DIV/internal-iframe acceptance remains required.

Risks of a future fix include a native-to-personal appearance transition, longer concealment if budgets move, extra requests/resize work, repeated gate arming, focus disruption and stale authority if recovery is not freshly validated. Normal-network successful behavior currently passes unchanged; this investigation introduces none of those production risks.

STOP: investigation complete; exact Chrome trigger awaits evidence. No production fix is implemented or deployed. Await approval before implementation.
