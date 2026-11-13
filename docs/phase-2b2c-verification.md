# Phase 2B-2C — browser Theme controller verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Source implementation and automated verification PASS.**
Attachment is default disabled. No deployment, live configuration/database changes,
WordPress bootstrap, actual browser acceptance, Git operations or Density work.
Stop at this checkpoint; Phase 2B-2D requires explicit approval.

## Implemented source and dormant delivery

One static parent-page Theme controller consumes the unchanged Phase 2B-2B endpoint
and Phase 2B-2A descriptor/serializer. It validates the complete response, applies
only approved root color properties, preserves inline originals/priorities, and
conditionally restores still-owned changes. DIV and Core-written same-origin
internal iframes have independent root/document/container/main-instance ownership.
Duplicate IDs, nested main candidates, child instances and ambiguous replacements
fail closed. Distinct activities are supported. Iframe rewrites and detached/reinserted
roots receive conservative lifecycle handling.

The default-disabled PHP attachment uses the existing public candidate registry,
fresh shared policy and frontend context. It never uses dependency libraries as
main ownership evidence. An explicitly enabled adapter queues at wp_footer 19,
after body wrappers have been observed, and rechecks admission at script-tag output.
Its only cacheable metadata is public content ID/embed/main-library information and
the same-origin endpoint. It reads no preference or identity. Default configuration
adds no attachment hooks/assets/metadata and changes no live configuration.

The unchanged endpoint authenticates the current viewer, rejects client identity
and preference fields, and sends private/no-store JSON. Browser requests use the
existing custom header, fresh 128-bit correlation, same-origin credentials/mode,
no-store and redirect rejection. Refresh invalidates/restores before fetching;
generation checks reject out-of-order/aborted replies. A 10-second request timeout,
exact schema/JSON/MIME/size checks and request-start-based 30-second expiry fail
closed. Initial load, pageshow, focus, visibility return and active-player expiry
revalidate. Hidden/pagehide states release active resources and admission. Failed
refreshes stop network renewal. No interval or hidden/failure polling is present;
successful visible-player renewals schedule their next 30-second expiry.

Mutation observers have a fixed bound of one parent plus one document per candidate
(64 candidate maximum). Seven finite retries handle late registration, load events
handle stylesheet readiness, mutation scans coalesce and all tracked listeners,
observers/timers are removed on stop. Weak ownership guards do not retain detached
roots through a strong instance-to-root reference.

Property writes use CSSStyleDeclaration methods with empty priority. Restore the
original pair only if the current pair still equals Papi Jo's last actual write.
Third-party changes relinquish that property for the root's lifetime. Existing
matching values are never claimed. Newly ambiguous owners are never touched even
for restoration; their old root is quarantined. No selectors, style blocks,
innerHTML, arbitrary CSS, storage authority, Density classes or resize calls exist.

SNORDIAN blocks attachment and actionable descriptors through the existing shared
detector/policy. Late server suspension suppresses already queued tags. A previously
admitted browser clears appearance and restores only safe owned values; subsequent
foreign/SNORDIAN property changes and priorities survive. Existing administration,
roles, profiles, detector code and SNORDIAN state remain unchanged.

See [the full controller, ownership, lifetime, limitations and activation contract](theme-controller.md).
Later activation is explicitly documented as a separately authorized deployment
and boolean `PJH5PWP_ENABLE_BROWSER_THEME` definition before bootstrap, with cache
handling and refined aggregate runtime diagnostics. No switch was enabled here.
The old aggregate Runtime_Policy styling flags remain false; they are not this
experimental Theme attachment switch and the aggregate runtime remains incomplete.

## References inspected

Read phase-2b2a-verification.md, runtime-appearance.md, phase-2b2b-verification.md,
runtime-transport.md and runtime-transport-examples.json, plus current source.
Consulted the Phase 2B-1 final report in **Add role-based H5P permissions**; that
investigation produced no repository report. Reviewed the authoritative Backdrop
v0.5.0 runtime/ownership implementations at reference commit
`d9b53d706a94dac322b89885bf668145ab3803c4`, without modifying the reference or
copying its learner controls/session-storage authority.

Reinspected installed H5P source: public rendering filter/asset order and Core's
main instance registration, standalone container, libraryInfo/child markers,
internal iframe rewriting and integration assignment. These source findings guide
the conservative ownership checks; no live activity was loaded or Core initialized.

## Exact source changes

Eight existing files modified:

| File | Change |
| --- | --- |
| `includes/class-plugin.php` | Compose the default-disabled attachment with the existing playback registry |
| `includes/runtime-wordpress.php` | Load the attachment definition |
| `tests/runtime/admission.php` | Exercise attachment through the real shared detector/policy, including late SNORDIAN activation |
| `tests/fixtures/plugins/h5p/h5p-php-library/h5p.classes.php` | Add the canonical DIV resolver stub needed by those disposable fixtures |
| `README.md` | Current checkpoint, boundaries, links and reproducible controller test commands |
| `readme.txt` | Current default-disabled source checkpoint |
| `docs/architecture.md` | Implemented Theme versus deferred native Density/acceptance boundaries |
| `docs/runtime-transport.md` | Current-checkpoint note linking the controller while retaining the original transport contract |

Six new files:

| File | Purpose |
| --- | --- |
| `assets/js/theme-controller.js` | Static Theme controller, validation, ownership, restoration and lifecycle |
| `includes/Runtime/class-theme-attachment.php` | Default-disabled public parent-footer delivery |
| `tests/runtime/theme-controller.js` | Deterministic DOM/CSSOM/clock/network and static-bootstrap tests |
| `tests/runtime/attachment.php` | Dormant/explicit delivery, tag, schema, context, suspension and resource-bound tests |
| `docs/theme-controller.md` | Detailed behavior, future activation, limits and deferred work |
| `docs/phase-2b2c-verification.md` | This report |

No source removals. Existing descriptor, serializer, endpoint, context, registry,
policy/detectors, domain, presets, storage, role/profile and administration production
implementations retain their original bytes. Prior verification records are preserved.

## Automated verification

| PHP suite | 7.4.33 | 8.3.18 | 8.4.22 |
| --- | --- | --- | --- |
| Foundation / installed H5P source | 621 PASS | 621 PASS | 621 PASS |
| Domain / Backdrop parity | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Theme storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Administration / UX / role forms | 314 PASS | 314 PASS | 314 PASS |
| Administration SNORDIAN coexistence | 44 PASS | 44 PASS | 44 PASS |
| Role/profile lifecycle | 251 PASS | 251 PASS | 251 PASS |
| Role storage doubles | 46 PASS | 46 PASS | 46 PASS |
| Descriptor/serializer | 548 PASS | 548 PASS | 548 PASS |
| Runtime coexistence | 70 PASS | 70 PASS | 70 PASS |
| Transport/context/security | 329 PASS | 329 PASS | 329 PASS |
| Real policy admission/attachment, 10 scenarios | 73 PASS | 73 PASS | 73 PASS |
| New dormant/explicit attachment | 28 PASS | 28 PASS | 28 PASS |
| Loopback HTTP/cookie/session/CORS | 198 PASS | 198 PASS | 198 PASS |
| Role MySQL | 69 PASS | 69 PASS | 69 PASS |
| Profile MySQL | 56 PASS | 56 PASS | 56 PASS |
| Theme storage MySQL | 49 PASS | 49 PASS | 49 PASS |
| PHP syntax | 86 files PASS | 86 files PASS | 86 files PASS |

**5,215 PHP assertions per version**, including 39 added attachment/policy
assertions (28 new fixture checks and 11 additions to the prior admission suite).
Domain results include the existing 919 authoritative Backdrop parity assertions.
All PHP tests use strict error handling; no compatibility errors remain.

**JavaScript: 124 new assertions PASS plus 30 existing administration assertions
PASS**, Node 22.23.1, no npm packages. The new tests use real checked-in PHP response
examples and isolated DOM/CSSOM/events/MutationObserver/fetch/monotonic-clock doubles.
They verify all requested transport, CSS, player, lifecycle and coexistence groups,
including exact PHP/browser property inventory, all five pinned built-in IDs,
unknown structures/properties, invalid values/expressions, Core/suspension, original
priorities, matching-value nonownership, external modifications, distinct DIV/frame
players, duplicate IDs, nested children, late registration/styles, detached/reinserted
roots, new roots borrowing old instances, changed containers, same-frame document
rewrite, document.open inherited URL, discarded-document cleanup, cross-origin rejection before document access,
generation ordering, timeout/failure/expiry, pageshow/focus/visibility, bfcache,
bounded retries, listener/observer cleanup and one static-asset bootstrap.

The main full matrix passed with the initial 25 attachment checks and 112 JS
checks. Final targeted runs include all 28 attachment checks on each PHP version,
affected PHP syntax and all 124 JS assertions after ownership review. Results are
retained separately; no unrelated suite needed repetition after those changes.

The Windows sandbox reproduced the previously documented fixture `is_readable()`
false-negative. Normal-process verification resolved it without production changes.
The first full matrix also identified the disposable H5P fixture's absent
determineEmbedType stub; the canonical DIV-only fixture seam was added and the full
matrix passed. The JS late-initialization case caught premature quarantine of a
root with no owned changes; it was corrected. Final review added explicit safeguards
against same-scan reacquisition and strong retention of detached roots.

Loopback HTTP fixtures use the installed WordPress cookie/session and CORS source
with memory-only users/preferences. All SQL regressions use guarded randomly named
`pjh5pwp_test_*` databases on MySQL 9.1.0, then remove them. Existing runners read
literal host/user/password credentials only, ignore wp-config's DB_NAME and load
wpdb without bootstrapping WordPress. The live acceptance.example.test database is never selected.
No new production operation performs persistence.

Local evidence outside deployable source:

- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`

## Preservation, limits and next phase

Before/after SHA-256 comparison establishes 113 pre-existing source files unchanged,
the eight intentional modifications and six additions above, with no removals.
The directory has no Git metadata; hashes preserve the existing uncommitted/source
baseline. Protected comparison covers 518 installed plugin files and 155 Backdrop
reference files: **673 unchanged**, no additions/removals. No acceptance.example.test file was
written. This is a filesystem audit, not a live database checksum audit.

Implemented behavior has only automated fixture coverage. Actual browser playback,
Firefox computed CSS serialization, real stylesheet timing/iframes, CSP and asset
optimizers, live user switching, cache/CDN isolation and visual coverage on official,
Papi Jo and fixed-color libraries still require separately authorized acceptance.
Observers are bounded in number, not in duration while a visible page is active;
their finite retry schedules are separate. Expiry renewals occur while visible
qualified players remain; failures and hidden/absent-player states stop renewal.
Remote policy/account changes are bounded by descriptor expiry, not pushed instantly.

Repeated IDs, unsupported frames/contexts and ambiguous ownership remain excluded.
A newly ambiguous root may retain its old override because writing to it would
violate the ownership boundary; it is quarantined instead. Exact same-value foreign
writes cannot be distinguished. The unchanged server serializer proves preset
provenance; the browser accepts only its fixed safe literal expression inventory.
Overall runtime diagnostic flags remain coarse and need refinement before enabling
this experimental attachment in production. There are no unresolved test failures.

Recommend **Phase 2B-2D — Native Density Controller**, after explicit authorization:
reuse this qualification/lifecycle/transport boundary, independently capture each
initialized native density state, apply only approved native classes, conditionally
restore Original and trigger resize only on the proved main instance. Preserve
Theme independence, fresh admission, SNORDIAN suspension and foreign ownership.
Keep it source-only/default-disabled until its own class/resize/restoration tests
and a separately authorized representative browser acceptance pass are complete.

**Stopped after Phase 2B-2C. Await explicit approval before Phase 2B-2D.**
