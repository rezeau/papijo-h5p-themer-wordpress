# Stage G4 — stable 0.2.0 release preparation

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Stable candidate **0.2.0** is promoted locally from accepted **0.2.0-beta.1**.
This stage authorizes release preparation only: no WordPress/database changes,
deployment, modification of the public Git checkout, commit, tag, push or publication.
The [release notes](release-notes-0.2.0.md) define supported scope and retained limits.
Historical G2/G3A evidence and published 0.1.1 artifacts remain unchanged.

## Reconciled Stage G3B acceptance

All results below were reported by the project owner in the G4 brief, on accepted
0.2.0-beta.1 using Firefox. **Codex did not independently reproduce them.** Stable
version/documentation bytes were not deployed or manually retested; the accepted
runtime implementation is preserved without functional changes.

| Test | Owner-reported result | Scenario |
| --- | --- | --- |
| G3B-1 | PASS | Administrator Global Default Theme |
| G3B-2 | PASS | Administrator personal Theme |
| G3B-3 | PASS | Administrator personal Large Density |
| G3B-4 | PASS | Subscriber personal Theme |
| G3B-5 | PASS | Subscriber personal Small Density |
| G3B-6 | PASS | Ordinary-page frontend regression |
| G3B-7 | PASS | Subscriber Original Theme fallback |
| G3B-8 | PASS | SNORDIAN activation coexistence |
| G3B-9 | PASS | Styling restored after SNORDIAN deactivation |
| G3B-10 | PASS | Internal iframe rendering |
| G3B-11 | PASS | DIV rendering |
| G3B-12 | PASS | Subscriber permission revocation |
| G3B-13 | PASS | Subscriber permission restoration |
| G3B-14 | PASS | Unrelated administration-page isolation |
| G3B-15 | PASS | Final frontend regression |
| G3B-16 | PASS | Two H5P activities on the same page: official True/False and Papi Jo DragText, both respecting Subscriber personal Theme and Density |
| G3B-17 | PASS | Legacy H5P activity using an API version earlier than 1.28 receives no Papi Jo Theme or Density styling |

Both former viewing limitations are resolved only for Administrator and authorized
Subscriber use of the exact official H5P activity viewer. This does not extend
support to arbitrary wp-admin pages or the separate external `h5p_embed` endpoint.

## Accepted database uncertainty

G3A recorded all 28 schemas unchanged, Papi Jo Themes and defaults preserved,
personal preferences preserved, role permissions preserved and plugin-owned tables
preserved. The `wp_options` row fingerprint changed and reported next AUTO_INCREMENT
changed from **262 to 263**. Attribution remains **unverified**.

The project owner explicitly accepts this residual uncertainty for stable 0.2.0:
**ACCEPTED RESIDUAL RISK — NOT A RELEASE BLOCKER.**

Full-database preservation is **NOT PASS**. G4 neither modifies a database nor resets
AUTO_INCREMENT. Original G3A evidence is preserved; historical F3 failure and F6
risk acceptance retain their original scope.

## Readiness and compatibility limits

The existing 15-second readiness timeout remains unchanged and outside 0.2.0 scope.
Slow page/H5P/CSS/iframe initialization may leave native fallback without automatic
personal styling. Slow-network readiness has not been fully verified; the owner
may investigate separately on an online WordPress test site. Passing characterization
tests are not evidence of a fix. Compatibility is limited to the recorded local
reference and automated matrix in the release notes, without broader certification.

## Promotion review

Only two installable files differ from beta.1: `papijo-h5p-themer-wordpress.php`
changes its header and version constant to 0.2.0; `readme.txt` updates distribution
documentation, current support, owner-reported acceptance, risks and changelog.
The other 91 payload files are byte-identical. Runtime PHP/JavaScript/CSS, nonce and
capability enforcement, frontend protocol, admin context restrictions, SNORDIAN,
legacy API exclusion, schema/storage code and readiness are unchanged. Package
membership remains the pinned 93-file policy. Metadata-normalized entry-point bytes
must match beta.1 exactly. Complete source diffs and payload comparisons are retained
in the private G4 report; its final verification record also checks historical files
and the untouched public checkout.

## Automated verification and exclusions

The current non-database matrix has 45 commands, including release security/tooling,
frontend and admin viewer access/nonce/capability tests, SNORDIAN, unsupported API
admission, JavaScript/browser fixtures and loopback HTTP tests with native WordPress
cookie/session and nonce functions. It runs on PHP 7.4.33, 8.3.18 and 8.4.22; the
audited public projection is independently tested on PHP 8.4.22. H5P and WordPress
source copies are read as dependencies; no installed site is bootstrapped.
Source PHP and extracted production PHP are linted on all three versions; all source
JavaScript, Python and JSON are syntax checked. These tests do not reproduce manual
Firefox acceptance or prove live-database preservation.

Development matrix: **135/135 commands verified PASS** across the three PHP versions.
The first normal-permission matrix passed 134/135; PHP 8.4.22's Profile browser fixture
hit a temporary `DevToolsActivePort` EBUSY lock. That unchanged fixture passed on its
single targeted retry (38 assertions). Initial restricted-token failures are retained
as environment diagnostics, not discarded or reported as product passes. No production
or test-harness behavior was changed. Release-tooling passes all 32 tests, including
the native Windows junction test. The private final record separately reports the
independent public-source matrix result.

Syntax: **692/692 external commands PASS**, covering 145 source PHP files and 80
extracted production PHP files on each PHP version, three version-order checks and
14 JavaScript files. Seven Python files and 13 JSON files parse successfully.
All 93 extracted member bytes match source.

The following nine suites are **excluded, not PASS**, because G4 prohibits database
and installation changes, including disposable writes:

- storage/mysql
- profile/mysql
- roles/mysql
- import/mysql
- profile/installation-mysql
- owned-storage/mysql
- owned-storage/production-mysql
- profile/activation-wordpress
- owned-storage/wordpress-innodb

No database credentials or native installation fixture are supplied. Broad online,
slow-network, other-browser/hosting/library and minimum-WordPress acceptance is not
claimed. No separate stable-byte deployment/manual acceptance is performed.

## Packaging and next stage

Independent builds use the development source and fresh audited public-source
projections, outside the public Git checkout. They must have equal approved input
manifests and complete ZIP bytes. Each ZIP is independently verified; its 93 members
are extracted only into a private non-WordPress directory and compared to source.
Current artifact filename, size, SHA-256 and input digest are recorded in
[release metadata](release-candidate.json). The full local report records output
paths, comparisons against beta.1 and published 0.1.1, checksums and final results.

Stable artifact: `papijo-h5p-themer-wordpress-0.2.0.zip`, **93 files / 156,969 bytes**.
SHA-256: `PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Python 3.13.14 and zlib 1.3.1 are the recorded packaging toolchain.
Compared with beta.1: two changed distribution files, 91 unchanged, +545 ZIP bytes.
Compared with published 0.1.1: eight changed files, 85 unchanged, +3,471 ZIP bytes.
The latter includes the six accepted G2 runtime changes (controller, appearance
endpoint, playback registry, theme attachment, WordPress context and plugin wiring)
plus version/distribution documentation; no new runtime changes are made in G4.

Recommended next stage: owner review followed by a separately authorized stable
deployment and target-site smoke check; Git/publication remain a later explicitly
authorized step. G4 stops after local preparation.
