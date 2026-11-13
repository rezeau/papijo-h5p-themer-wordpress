# Stage E1 — public source and packaging tooling

11 November 2026. Source preparation completed. Current version is **0.1.0**;
proposed first pre-release remains **0.1.1-beta.1**, not applied. No real release
ZIP, deployment, database write, Git initialization or publication is part of E1.
The unresolved slow-network readiness limitation remains unchanged.

## 1. Files added and modified

Added ten public development files:

- `.gitattributes`: preserve reviewed source/license bytes across future Git checkouts.
- `CONTRIBUTING.md`: current development, testing, policy and release instructions.
- `docs/public-source-allowlist.txt`: complete independent public-repository file list.
- `docs/public-source-preparation.md`: projection, audit, layout and publication procedure.
- `docs/release-tooling.md`: audit/build/verify commands and acceptance boundaries.
- `docs/stage-e1-readiness.md`: this verification report.
- `tools/release_common.py`: safe path/inventory, policy, metadata/license and hash validation.
- `tools/package_release.py`: read-only audit, future deterministic build and verification.
- `tools/prepare_public_source.py`: complete public-source audit and documentation projection.
- `tests/release/test_tooling.py`: offline adversarial validation tests.

Modified four existing files: README.md and docs/release-preparation.md point to
implemented E1 tooling; docs/testing.md distinguishes current 47-command scope
from historical Stage D results; tests/run-all.py includes the new tooling suite.
The ZIP allowlist, .gitignore, production PHP/JS/CSS/presets, distribution readme,
third-party notices and license resources are unchanged. All historical phase,
activation/deployment, Stage A/B/C and slow-network investigation originals remain
byte-identical to the E1 starting snapshot. Private review reports/projection are
additional ignored local material, not public inputs.

## 2. Public-source audit and projection

The complete public policy contains **217 files**: 69 include PHP files, seven
assets, one preset, one languages guide, two license resources, 67 test/fixture
files, 59 documentation/policy files, three tools and eight root files. This is a
complete useful development repository, not a distribution-only subset. Every
reviewed technical document and historical report is retained; every test and
fixture is retained. The independent ZIP policy still contains exactly 84 files.

Private exclusions cover the entire opaque `.local-acceptance` tree, backups,
database snapshots, local configuration/keys/environment files, private JSON,
local deployment/verification scripts, logs/generated reports and build/cache/Git
internals. Those files are not read or copied by the public preparation tool.
Original local evidence is not deleted. Unknown public candidates and unknown
production files fail validation until deliberately reviewed.

The bounded content audit found no common embedded private-key/GitHub/AWS token or
literal database-password definition in public input. Existing fixture passwords,
salts, sessions, error sentinels and local test URLs are synthetic, useful test data.
The operator membership-comparison helper under tests/runtime is retained as a
pure regression test, not a live deployment utility. Generic Edge installation
paths in two browser harnesses contain no personal account/site path and have CLI
overrides. No production file contains a machine-specific filesystem path.

**42 Markdown documents** receive deterministic public projections. Local absolute
paths become source-root placeholders, private evidence destinations become omission
markers, local host/profile identifiers become examples, and private verification
fingerprints/checksums are redacted. Source commit/blob provenance pins, technical
contracts, test findings, historical status and the slow-network investigation are
preserved. Changed documents have visible projection notices. The private originals
are never rewritten. All retained relative Markdown file links resolve within the
217-file projection; a second projection leaves every projected document unchanged.
The scan is bounded and reviewed, not a guarantee of detecting every conceivable
secret format. Review the exact projected file set before any first commit.

A complete source review directory is materialized locally under ignored private
review storage, with its manifest/digests outside the public tree. It has no Git
metadata and no ZIP. All 84 distribution files are byte-identical in the projected
repository. The same source audit and tooling tests run from that complete copy.

## 3. Packaging design and behavior

The existing sorted 84-path ZIP list is independently pinned by its canonical
SHA-256 in the tooling. Additions, omissions, duplicates, case changes or private
entries in the list are rejected. Extra/missing production files, unsafe paths,
nonregular files, symlinks and Windows reparse points/junctions are rejected before
reading payload bytes. Private/ignored trees are never traversed. Development
files remain in the repository and cannot enter the ZIP through broad copying.

`audit --expect-version 0.1.0` creates no archive. It validates WordPress entry point
and header, PHP/WordPress declarations, activation registration, runtime/Profile
installation resources, all JavaScript/CSS/preset files, full pinned licenses and
attribution. Header, runtime constant, stable tag and newest changelog must agree.
The output is an ordered SHA-256/size manifest and whole-input digest. A requested
0.1.1-beta.1 version fails against current source, rather than modifying source.

The future explicit `build` command requires the reviewed manifest, compares two
in-memory builds, verifies all archive bytes and rechecks source before writing a
fresh isolated output directory. It writes only a versioned ZIP, SHA-256 sidecar,
input manifest and verification/toolchain report. It never edits production files.
Existing outputs are refused. No actual-source build command was executed in E1.

Metadata is fixed: one `papijo-h5p-themer-wordpress/` prefix, lexical members,
1980-01-01 timestamp, Unix regular-file 0644 mode, fixed ZIP versions, DEFLATE level 9
and no extra/comment/directory records. Verification checks exact member set/order,
relative paths, sizes/decompressed bytes/hashes, CRCs, encryption/link metadata,
archived release declarations and canonical complete bytes, including rejection
of trailing/prepended data. Pin Python/zlib for identical compressed hashes.
No external network, Git, WordPress or dependency-build operation is invoked.

See [release tooling](release-tooling.md) for commands and limitations. Hashes are
integrity evidence, not cryptographic maintainer signatures. Keep source quiescent
while approving/building inputs; the tool is not a hostile concurrent-writer sandbox.

## 4. Verification results

- **31/31 new offline tooling tests pass**, including missing/unexpected production
  files, modified policies, traversal/device/control paths, duplicate/case collisions,
  symlink/reparse guards and a real temporary Windows junction; header/version/
  changelog/license/attribution failures; deterministic order/metadata/hash behavior;
  changed/missing/private/duplicate archive members; CRC corruption, encryption flags,
  trailing data, stale approved manifests, output preservation, public completeness,
  projection idempotence/link closure and production identity.
- ZIP test payloads are synthetic BytesIO buffers. Real source is audited/hashed
  only. The future build's writer is intercepted to test sidecar names/hashes without
  writing a ZIP. Temporary synthetic fixtures are cleaned only after validating
  their generated directory paths. No existing backup/evidence is removed.
- **41/41 non-database inventory commands pass on PHP 8.3.18**, including all 40
  existing non-database PHP/JS/browser/HTTP variants plus the new tooling entry.
  The final expanded 31-test tooling suite is verified separately and in the public
  development projection. Browser tests use Chromium/Edge static fixtures, not a new
  live Firefox acceptance claim.
- **9/9 focused PHP commands pass on each of 7.4.33 and 8.4.22**: actual-H5P
  foundation, administration, Profile installation/activation/suspension, runtime
  configuration/attachment/readiness and transfer administration. These are
  memory/source fixtures only, with no real database or WordPress bootstrap.
- All Python tooling/test scripts parse without bytecode output. Current PHP source
  and the 84 distribution resources are unchanged from the accepted E1 baseline.
  Complete Stage D 46-command/database results remain historical evidence; E1
  deliberately does not rerun the six opted-in database/native lifecycle commands.

An initial tooling run exposed an overly broad directory exclusion that hid the
new tests/release directory, plus a negative test's literal path confusing the
public scan. Both were corrected before final verification. The restricted Windows
token could not create the native test junction; authorized normal process execution
passed it without skipping or weakening that check. No new test remains failing.

Tools used: Python 3.13, Node.js 22.23.1, PHP 7.4.33/8.3.18/8.4.22 and Edge static
browser fixtures. Test results do not expand the declared WordPress/browser/hosting
compatibility matrix. No live site database is selected or modified. Existing
WordPress/H5P definitions used by old suites are read only.

## 5. Public Git plan and release conventions

Use the full projected 217-file layout: production, tests/fixtures, docs, tools,
README/CONTRIBUTING, Git policy files and attribution. Keep private originals and
review logs outside that tree. The explicit public policy must match every public
candidate; the ZIP policy remains independently smaller. Verify staged files and
actual ignore behavior only after Git initialization is separately approved.
`.gitattributes` prevents EOL conversion from invalidating reviewed license hashes.

Recommended repository name: `rezeau/papijo-h5p-themer-wordpress` (not created or
availability-checked here). After version approval, use tag `v0.1.1-beta.1`, title
`0.1.1-beta.1 (testing pre-release)` and installer asset
`papijo-h5p-themer-wordpress-0.1.1-beta.1.zip`, with SHA-256 information. Set the
GitHub pre-release designation. Automatic source archives contain development files
and are not the dedicated installer. Version remains 0.1.0 throughout E1.

Release notes should state test audience/features/changes, exact tested matrix,
target-only minimums, prerequisites, dedicated asset/hash instructions, data
preservation, pilot outcomes, unresolved readiness/other exclusions, attribution
and private-safe reproduction guidance. Draft/publication operations require their
own authorization; E1 performs none.

## 6. Licensing and provenance

No new vendored dependency or missing local notice was identified. The three full
license texts are pinned and unchanged, GPL-3.0-or-later metadata agrees, Backdrop
adaptation/GPLv2-or-later notices remain, and MIT/GPL palette credits for Oliver
Tacke and Matheus Mathias remain in source and distribution notices. The existing
Backdrop parity run remains green. License validation checks retained local bytes
and markers, not legal certification or fresh upstream authentication.

The pinned remote provenance links were unavailable during Stage D and have not
been newly authenticated in this offline E1 work. Preserve their exact commit/blob
pins and recheck source/license provenance before publication. No new licensing
conflict was discovered; upstream verification remains an open publication item.

## 7. Recommended E2/E3 procedure

**E2 — approved version and actual artifact acceptance:** approve 0.1.1-beta.1 and
its precise production metadata changes first. Update header/constant/readme/current
changelog coherently; review active test fixture version literals individually,
without rewriting old phase reports or preset pins. Rerun required regressions;
audit fresh inputs and approve the new manifest. Separately authorize disk artifact
creation, execute two independent builds with the recorded toolchain, compare ZIP
hashes, run `verify`, safely extract/lint and check exact files. Install that actual
ZIP only in an approved fresh disposable WordPress/database, then verify activation,
degraded setup/retry, explicit Theme initialization, permissions, independent Profile
preferences, transfer and preservation/coexistence. E1's source-copy tests do not
establish this artifact-delivery result.

**E3 — private pilot and controlled GitHub preparation:** approve a backed-up online
staging target and test configuration. Verify target H5P/Core/storage/grants, browser,
content libraries, CSP/caching/assets and the unresolved readiness fallback. Do not
change the accepted local site or deactivate its active SNORDIAN. Accept the known
limitation explicitly or authorize a separate readiness fix before claiming more.
Regenerate/review the complete public projection and hashes, then obtain Git/
repository/commit/tag/draft preparation approval. Audit the exact staged public
files, ignore behavior and links. Present source revision, ZIP/input hashes, release
notes and pilot results for final publication approval. Publish only afterward.

E1 stops after verification. No real ZIP, version bump, Git initialization, remote
repository, commit, push, tag, deployment, SNORDIAN change or publication occurred.
