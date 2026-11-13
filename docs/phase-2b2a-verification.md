# Phase 2B-2A — runtime descriptor and CSS serializer

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Source-only implementation and automated verification complete.**
Playback integration remains unimplemented/disabled. No deployment or Phase 2B-2B
work was performed.

## Services and architectural basis

`Runtime\Appearance_Service` obtains a fresh shared Runtime_Policy decision, then
projects Profile_Service::effective() into a minimal internal descriptor. Existing
role authorization, automatic Administrator access, publication, global eligibility,
personal fallback, pinned inheritance and independent Density resolution are reused.
Suspended operation skips profile resolution and contains no actionable colors or
Density. Unknown/malformed internal results fail closed.

`Runtime\CSS_Serializer` validates resolved palettes, enforces the fixed supported
property inventory and exact pinned provenance, and emits canonical declarations.
It creates no selector, Density CSS, file, HTTP response or HTML.

`includes/runtime.php` is an inert opt-in loader. Main plugin bootstrap, Plugin,
Runtime_Policy, Profile/Role/UI and all existing production files remain unchanged.
No endpoint, playback callback, script or CSS injection was added.

Reviewed the Phase 2B-1 final investigation in the earlier **Add role-based H5P
permissions** chat (the investigation did not create a repository report), the
Phase 2A-R verification/deployment records, current architecture/domain contracts,
and the authoritative Backdrop runtime serializer. In particular, Original means
preserving the initialized native state, even when Core initializes Large. No
Backdrop browser-tab preference authority or public CSS asset publisher was ported.

See [the descriptor contract, full examples and provenance rules](runtime-appearance.md).

## Exact source changes

All seven files are new. No pre-existing source file was modified or removed.

| File | Purpose |
| --- | --- |
| `includes/Runtime/class-appearance-service.php` | Admission-gated descriptor projection with injected dependencies. |
| `includes/Runtime/class-css-serializer.php` | Strict canonical color declaration serializer. |
| `includes/runtime.php` | Inert loader; not called by main bootstrap. |
| `tests/runtime/run.php` | Real Profile/Role/domain resolution and malicious palette tests; reproducible full examples. |
| `tests/runtime/coexistence.php` | Real Runtime_Policy/detector integration through isolated fixtures. |
| `docs/runtime-appearance.md` | Internal contract, composition, semantics and five full examples. |
| `docs/phase-2b2a-verification.md` | This implementation/verification report. |

Local-only evidence, excluded from deployable source:

- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`
- `PRIVATE_EVIDENCE_OMITTED`

## Automated results

| Suite | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| New descriptor/serializer | 548 PASS | 548 PASS | 548 PASS |
| New runtime coexistence | 70 PASS / 8 scenarios | 70 PASS / 8 scenarios | 70 PASS / 8 scenarios |
| Foundation / installed H5P source | 513 PASS / 18 scenarios | 513 PASS / 18 scenarios | 513 PASS / 18 scenarios |
| Domain / Backdrop parity | 2,315 PASS | 2,315 PASS | 2,315 PASS |
| Theme storage doubles | 204 PASS | 204 PASS | 204 PASS |
| Administration / UX / role form | 314 PASS | 314 PASS | 314 PASS |
| Existing SNORDIAN coexistence | 44 PASS | 44 PASS | 44 PASS |
| Existing roles/profile lifecycle | 251 PASS | 251 PASS | 251 PASS |
| Role storage doubles | 46 PASS | 46 PASS | 46 PASS |
| Role MySQL | 69 PASS | 69 PASS | 69 PASS |
| Profile MySQL | 56 PASS | 56 PASS | 56 PASS |
| Theme storage MySQL | 49 PASS | 49 PASS | 49 PASS |
| PHP syntax | 76 files PASS | 76 files PASS | 76 files PASS |

Per PHP version: **618 new assertions plus 3,861 existing regression assertions**
(4,479 total). Domain totals include 919 authoritative Backdrop parity assertions.
Existing JavaScript isolated DOM regression suite: **30 assertions PASS**.

The new tests cover anonymous/authenticated, authorized/unauthorized/roleless and
nonexistent viewers, automatic Administrator access, revocation/restoration and
deleted roles; all fixed-theme/Density combinations; global changes; all built-ins
despite false publication flags; published/unpublished/deleted custom themes;
unpublished global custom eligibility; invalid theme/global/Density values; failed
profile reads; legacy Density independence; deterministic descriptors/declarations;
hex overrides; exact inherited expressions; forged preset versions/colors,
properties/provenance, injection-like values and unsupported catalog fields;
malformed/throwing dependencies; all four SNORDIAN signals and late activation;
missing/unsupported H5P and multisite. Suspension contains no actionable Theme or
Density and does not call the effective dependency.

The compatibility suite initially reproduced the previously documented Windows
sandbox `is_readable()` false-negative. Normal-process runs resolved that restriction
without changing production detection. PHP 8.4's strict error handler then caught
an implicit-nullability deprecation in the new constructor; explicit nullable syntax
fixed it, and both new suites and syntax were rerun successfully on all versions.
The initial log is retained separately; the final matrix records successful checks.

All SQL checks used existing guarded random `pjh5pwp_test_*` databases on MySQL
9.1.0, then removed them. The runner reads only literal host/user/password values
from wp-config; it ignores that file's DB_NAME. It loads the installed wpdb class,
not WordPress bootstrap. The acceptance.example.test database was never selected by these suites.
No new service requires a database test because it performs no persistence.

## Preservation and limitations

SHA-256 before/after comparison confirms all 102 pre-existing non-private source
files unchanged, with exactly the seven additions listed above. Protected checks
cover the entire acceptance.example.test plugins tree and authoritative Backdrop reference tree:
673 files unchanged, with no additions or removals. Evidence and final source
hashes are in `phase-2b2a-verification.json`. This is a filesystem comparison, not
a live database checksum audit.

No live site bootstrap, activity load, database write, configuration/activation
change, deployment, Git commit, tag, push or release occurred. The WordPress source
directory has no Git metadata, so preservation is established through hashes.
Existing uncommitted/source work and historical reports were retained as found.

Services compute internal intent only. No claim is made about browser application,
actual player qualification, iframe ownership, CSS readiness, resize behavior,
HTTP authentication/cache isolation, refresh/expiry, or Firefox playback acceptance.
Those remain later-stage work. Original is represented explicitly; native class
capture/restoration has not been implemented. A descriptor is not enduring admission
authority: future adapters must recheck shared admission at attachment/transport
boundaries and handle stale browser state.

Existing Profile_Service/Schema failure behavior is preserved, including treating
an invalid whole library snapshot as unavailable rather than selectively repairing
definitions. Real Profile_Store rejects a malformed entire aggregate and existing
Profile_Service uses its safe defaults. No new storage recovery policy is invented.
There are no unresolved failures in this phase's automated checks.

## Next authorized proposal — Phase 2B-2B

Recommend a separately approved dormant admission/transport adapter using these
services. Specify server-derived identity, private uncached delivery, frontend
context qualification, editor/admin-preview/external-embed exclusion, and fresh
SNORDIAN checks at every relevant boundary. Test forged identity, cross-user/cache
isolation, error/suspension responses and coherent policy status before enabling
any attachment. Player mutation, Theme JS and native Density ownership remain
subsequent implementation stages.

**Stop after Phase 2B-2A. Await explicit approval before Phase 2B-2B.**
