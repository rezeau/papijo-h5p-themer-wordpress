# Stage D — release consolidation findings

11 November 2026. Source consolidation completed; current plugin version remains
**0.1.0**. The maintainer confirmed that Stages A/B/C and the listed features are
completed, deployed locally and manually accepted in Firefox. This report records
that confirmation separately from the automation performed here.

## 1. Documentation changes

Replaced contradictory current-facing README/readme material with the completed
feature set, bounded compatibility claims, default-on gated runtime, independent
Theme/Density semantics, Profile installation/retry lifecycle, current SNORDIAN
suspension behavior, color Export/Import and known limitations. Added a dedicated
[installation guide](installation.md), [test inventory](testing.md),
[release design](release-preparation.md) and exact
[ZIP allowlist](release-zip-allowlist.txt). The distribution readme is self-contained.

Updated the current-status introductions in controller/transport documentation.
Added current-status pointers to architecture, administration, appearance,
Profile preview and suspension documents so their older phase text cannot be
mistaken for the current release state. Historical phase/deployment/acceptance
reports, including original Stage A/B/C reports and the slow-network investigation,
retain their original checkpoint text. Their old source-only claims are historical,
not current status. No unsupported compatibility or manual acceptance was invented.
The third-party notice received only current wording/grammar corrections.

The entry-point description still says “foundation”; that is cosmetic production
metadata and was deliberately left for separate production-file approval.

## 2. Tests and corrections

Corrected `tests/admin/headless-browser.js` to validate the intended five-form
fixture by operation and ownership rather than the obsolete two-form total.
It checks POST/nonce/snapshot binding, external single-export button target,
selected custom IDs, publication input ownership and both Import links, retaining
all prior edit/palette/reset/layout/immutability/default-delete assertions.
The corrected browser suite passes 28 checks.

Added `tests/run-all.py` as an explicit-input consolidated inventory runner with
separate private logs and opt-in disposable-database/native-WordPress checks.
All available entry points and included helpers are documented in [testing.md](testing.md).

| Environment | Commands passed | Failed | Skipped |
| --- | ---: | ---: | ---: |
| PHP 7.4.33 | 46/46 | 0 | 0 |
| PHP 8.3.18 | 46/46 | 0 | 0 |
| PHP 8.4.22 | 46/46 | 0 | 0 |

Each run includes 28 PHP command variants without a real database, eight isolated
JavaScript suites, two real Chromium static-browser suites, two loopback HTTP
suites and six random-database/native lifecycle suites. WordPress reference:
7.1.4; MySQL 9.1.0; Node.js 22.23.1; Python 3.13; Edge 157.0.4341.46.
There were 138 successful command executions across the final three runs; counts
overlap shared prerequisites and are not unique-assertion coverage.

All 124 PHP files passed syntax checks on all three PHP versions, all 13 JS files
passed syntax checks, and the three Python scripts parsed without bytecode output.
The PHP 7.4 run includes real storage races and native activation, not just lint.
This does not establish compatibility with every WordPress/PHP/MySQL combination.

Earlier restricted-token failures and the two intermediate runner/assertion
corrections are disclosed separately in testing.md and retained in private logs.
Normal process permissions were needed because restricted-token PHP returned false
from is_readable for readable fixtures and loopback checks could not connect.
No production behavior was changed to bypass those checks. No final failures remain.

Native tests used a fresh private copy of WordPress core and current production
plugin files. They wrote only random disposable fixture databases. The credential
source was tokenized for server credentials only; its DB_NAME was never selected.
The native fixture had no copied live configuration or active plugins. Its generated
private configuration was removed, and every harness reported database cleanup.
No real local acceptance-site database was selected, bootstrapped or modified.
No live browser acceptance was rerun during Stage D.

## 3. Source audit

The pre-fixture metadata audit found 1,393 files / 331,377,957 bytes in
`.local-acceptance`, including SQL dumps, a backup ZIP, copied PHP/plugin trees,
private JSON, logs, PowerShell/Python helpers and browser evidence. Stage D added
new private verification logs and a fresh isolated core fixture. Original local
backups/evidence were not deleted or rewritten. This entire directory is forbidden
in both public source and installable artifacts; its contents are not release inputs.

Outside private evidence:

- Production bootstrap/loaders reference production components; no references to
  test fixtures, temporary diagnostics or local deployment helpers were found.
  Pattern checks found no production debug printing, console logging or TODO/FIXME
  diagnostics. Existing sanitized administrator diagnostics are intended features.
- No credential-shaped literal, private key, common GitHub/AWS token, private
  configuration file, SQL dump or generated archive was detected in the proposed
  distribution. Credential-variable usage in storage and test-only fake credentials
  are not embedded production secrets. This is a scoped source audit, not a claim
  that any automated scan proves the absence of every possible secret.
- No absolute local filesystem path was found in production PHP/JS/CSS/presets.
  Browser tests retain generic Windows Edge executable defaults with explicit CLI
  override. Historical docs include personal/local Windows paths, local host names
  and private-evidence pointers; these need a public-doc projection/review before
  publication. They are preserved locally rather than silently rewritten.
- Test doubles, fault injectors, loopback routers, browser harnesses and the
  plugin-state operator helper are development-only files under tests/. Keep
  reviewed test sources in the public repository if desired; exclude all from ZIP.
- No production symlinks/reparse points, vendored dependency tree or build output
  was found. Legacy compatibility fields and explicit installation/fault handling
  were not removed as “cleanup.”

The initial broad credential/path patterns produced theme-token and URL false
positives. Refined path/value checks and source inspection distinguished those
from actual local paths and secrets; no matched secret values are reproduced in
reports. Private evidence is not to be uploaded even when its individual files
have not all been inspected.

## 4. Packaging allowlist

The [closed 84-file manifest](release-zip-allowlist.txt) comprises the entry point,
69 include PHP files, three JS/four CSS assets, one preset file, the languages README,
two license files, root LICENSE, distribution readme and third-party notices.
It resolves safely to existing files, has no duplicates/case collisions and
contains no tests/docs/private entries. Required PHP definitions/resources are
included. No archive was created.

The ZIP must contain exactly one top-level directory:
`papijo-h5p-themer-wordpress/`. The [release design](release-preparation.md) specifies
fixed metadata, sorted paths, source hashes, repeat-build comparison, CRC/member
verification, safe extraction, metadata consistency and fresh-install validation.
Installation has no Composer/npm or compilation requirement. The public repository
and ZIP have different intended file sets.

## 5. Ignore rules and publication scope

Prepared [.gitignore](../.gitignore) locally; Git has not been initialized. Its
rules exclude `.local-acceptance/`, secrets/configuration, SQL, backups, build/archive
output, logs, bytecode/dependency/editor caches. This is the proposed rule set for
publication. Ignore rules are defense in depth: Stage E must build a fresh
allowlisted public tree, inspect staged files and verify ignore behavior after
separately approved initialization. Do not force-add private evidence or rely on
ignore rules to remove already tracked data.

Historical documentation is not yet a public-file manifest. Stage E must choose
public-safe contracts/excerpts, preserve the local originals, remove private/local
context from public copies and verify link closure before a first commit.

## 6. Licensing and attribution

GPL-3.0-or-later declarations are consistent across entry point and current docs.
Root LICENSE contains GPLv3 text; licenses/ retains GPLv2 and Oliver Tacke's MIT
notice. The preset header retains four MIT palette attributions and Dark's Matheus
Mathias GPL attribution. Preview CSS retains its GPL-2.0-or-later original notice.
Backdrop/Moodle revision/blob pins and adaptation provenance remain recorded.
No H5P runtime, upstream picker or SNORDIAN implementation is included in the ZIP
list. The pinned Backdrop parity variant passes 919 source comparisons.

No missing local license resource or new dependency was identified. Pinned remote
GitHub provenance links could not be fetched in this session; local provenance and
parity evidence are the review basis. Verify link accessibility during publication
preparation; do not present this as fresh authentication of upstream grants.

## 7. Recommended version

Recommend **0.1.1-beta.1**, tag **v0.1.1-beta.1**, asset
**papijo-h5p-themer-wordpress-0.1.1-beta.1.zip**, after separate version/packaging
approval. PHP version_compare confirms it is newer than installed 0.1.0; a
0.1.0-beta.1 identifier would be older. Keep the future header/constant/readme,
changelog, release tag/title and filename coherent. Current version fields are
unchanged at **0.1.0**. The preset data pin is independent of release versions.

## 8. Release blockers and risks

| Milestone | Remaining gates |
| --- | --- |
| Clean installation from ZIP | Approve version and packaging implementation; build/verify the actual ZIP; exercise upload/extraction and activation of that artifact in a fresh disposable WordPress/database. Native source-copy activation is green but does not prove ZIP delivery. Verify degraded storage/retry and explicit Theme initialization. |
| Private online pilot | Approve the exact staging site, backup and installation; confirm official H5P/Core API/storage/grants and actual PHP/browser/library/cache/CSP behavior; test the artifact and preservation/rollback. Authorize any SNORDIAN configuration transition explicitly; its active state currently suspends Papi Jo playback. |
| Public GitHub pre-release | Approve a public source manifest, public-safe docs/link closure, version, Git initialization/repository and release preparation. Audit staged files/secrets and artifact checksums, then obtain final publication approval. Record the limited tested matrix and pilot outcome or explicitly label any untested path. |

**Unresolved slow-network readiness limitation:** the initial 15-second budget
starts in the head bootstrap; DOM readiness, H5P/CSS initialization or iframe layout
may complete too late. Native fallback can become terminal for automatic personal
Theme/Density in that document. The passing characterization test preserves this
finding; it is not a fix. This blocks a promise of reliable slow-network
personalization. A testing pre-release needs explicit acceptance of the documented
limitation or a separately approved runtime correction and its own verification.
No such production change was made here.

Other limits remain: single-site only; exact integration/Core qualification; no
external embeds/editor playback; no universal library/browser/accessibility claim;
no automatic legacy migration/uninstall cleanup; eventual rather than instant
cross-session policy-change detection; and asset-order/cache sensitivity. Declared
WordPress 6.5 minimum is a target, not a newly tested native WordPress 6.5 result.
A pre-release can retain these scoped limitations without implying broad support.

There are no outstanding automated failures in the final supported local inventory.
No successful ZIP installation, online pilot or public release is claimed.

## 9. Proposed Stage E

Follow the approval-separated sequence in [release preparation](release-preparation.md#stage-e-gates-and-implementation-sequence):
public source/docs projection; coherent approved version metadata; deterministic
allowlisted packager and rejection tests; two-build integrity check; disposable
ZIP installation; authorized private online pilot; audited Git/repository/draft
preparation; final publication approval. Keep any readiness fix separate.

## Preserved boundaries and stop

SHA-256 comparison confirms all **82 protected production/license/resource files**
are unchanged, including entry point, includes/, assets/, presets/, languages/ and
license texts. Header, runtime constant and stable tag remain 0.1.0. No Git metadata
or new archive exists. All changes are documentation, test code, the proposed
ignore file and private local verification material.

No deployment, installed plugin edit, real site database write, SNORDIAN change,
version bump, Git initialization, repository creation, commit, tag, push or publication
was performed. Stage D stops here for review and approval of any Stage E work.
