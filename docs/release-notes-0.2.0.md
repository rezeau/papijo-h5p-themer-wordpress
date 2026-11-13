# Release notes — 0.2.0

Stable 0.2.0 is prepared locally from the accepted 0.2.0-beta.1 implementation.
Promotion changes version metadata and release documentation only; there are no
functional runtime changes from that accepted candidate. Published 0.1.1 artifacts
remain unchanged. Deployment and publication require separate authorization.

## Supported activity viewing

- The official WordPress H5P administration activity viewer now uses the shared
  Theme and Density runtime, on the exact
  `wp-admin/admin.php?page=h5p&task=show&id=<ID>` route.
- Administrators and authorized Subscribers receive personal Theme and independent
  Density preferences, with Global Default/Core fallback as appropriate.
- Supported DIV and same-origin internal iframe activities retain the existing
  runtime, including multiple distinct activities on one page. G3B included official
  True/False and Papi Jo DragText together with Subscriber Theme and Density.
- SNORDIAN activation suspends Papi Jo styling; styling returns after deactivation.
  Permission revocation falls back without discarding saved choices, and restoration
  permits the choices again. Existing ordinary-page frontend compatibility is retained.
- Unsupported legacy H5P activity content using an API earlier than 1.28 receives
  no Papi Jo Theme or Density styling.

The two former Administrator/Subscriber viewing limitations are resolved for this
accepted official activity-viewer context only. Arbitrary wp-admin pages, content
lists, editors, results, Profile rendering and the separate external `h5p_embed`
endpoint are not additional supported viewing contexts. Context, official-class,
Core API exactly 1.28, content-access, session-bound nonce and capability checks
remain intact. Native H5P variables and theme-aware library markup remain required.

## Acceptance and compatibility limits

All **17/17 Stage G3B Firefox tests were reported PASS by the project owner** on
0.2.0-beta.1. Codex did not independently reproduce those manual tests, and stable
metadata bytes were not separately deployed or manually retested. The accepted
implementation is preserved. See the [complete G4 evidence record](stage-g4-release.md).

The inspected local reference is WordPress 7.1.4, official H5P 1.17.10 and Core API
1.28. PHP 7.4.33, 8.3.18 and 8.4.22 are the automated matrix; WordPress 6.5 and PHP
7.4 remain declared targets rather than a certification of every combination.
Other browsers, hosting, libraries and minimum-WordPress combinations are not newly
qualified. Broad accessibility compatibility is not certified.

## Retained limitations and accepted risk

The existing **15-second readiness timeout is unchanged and outside 0.2.0 scope**.
It can reveal native fallback without automatic personal styling after slow page,
H5P, CSS or iframe initialization. Slow-network readiness has not been fully verified;
passing characterization tests do not fix it. The owner may investigate separately
on an online WordPress test site.

G3A found all 28 database schemas unchanged; Papi Jo Themes/defaults, personal
preferences, role permissions and plugin-owned tables were preserved. The
`wp_options` row fingerprint changed, and reported next AUTO_INCREMENT changed
from 262 to 263. Attribution remains unverified. The project owner explicitly
accepts this uncertainty for stable 0.2.0:

**ACCEPTED RESIDUAL RISK — NOT A RELEASE BLOCKER.**

This is **not a full-database preservation PASS**. No database modification or
AUTO_INCREMENT reset is part of G4. The original G3A evidence and historical F3/F6
records remain unchanged. No schema or storage behavior change is introduced.
