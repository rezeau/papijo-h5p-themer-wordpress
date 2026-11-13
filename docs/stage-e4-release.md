# Stage E4 — 0.1.1-beta.1 candidate refresh

12 November 2026. The refreshed local candidate incorporates the approved
`storage_options_engine_unsupported` correction. Version **0.1.1-beta.1** is
unchanged. No deployment, site database modification, Git initialization or
publication is part of E4.

## Scope and earlier acceptance

The maintainer reports Stage E3 clean-install acceptance of the original E2 ZIP:
**8/8 manual Firefox tests**. A fresh installation then exposed insufficient
diagnostics when WordPress options used MyISAM while the separate Profile table
successfully used InnoDB. The approved correction keeps the engine requirement
and safe failure behavior, and explains the administrator-managed correction.

The payload delta from E2 consists of exactly three files:

- `includes/Storage/class-wordpress-snapshot-store.php`: identify a known
  non-InnoDB options engine separately from unverified metadata/other failures.
- `includes/Admin/class-admin-messages.php`: explain that InnoDB is required for
  Theme settings and user-permission consistency; administration is unavailable;
  back up before converting the actual options table; Papi Jo never converts
  WordPress-owned tables automatically; Profile setup retry cannot fix this issue.
- `includes/Admin/class-diagnostic-notices.php`: share that explanation in the
  Plugins-page storage notice as well as the Themes page.

The corresponding storage, administration, native installation and activation
regressions and installation documentation are retained in the public source.
No storage architecture, schema, role authorization lock, Profile installation
implementation, SNORDIAN policy, Theme/Density runtime or version changed in E4.
The H5P admin View enhancement and slow-network issue remain deferred.

## Refresh procedure and historical evidence

The existing [release tooling](release-tooling.md) is used unchanged. The old E2
ZIPs, frozen manifests, metadata, reports and public projections are retained and
hashed as historical evidence. A separate Stage E4 output directory contains all
new builds, projections, manifests and verification records.

A new audited manifest freezes the actual approved production bytes. Independent
build-a/build-b runs require exact equality with it and are verified separately.
Current [candidate metadata](release-candidate.json) is derived from those results,
including the actual installer hash and canonical source-input digest. It records
the E4 stage and previous E2 hashes. No assertion, stale-manifest guard, membership
policy or policy pin is changed. The formerly failing candidate-hash assertion now
passes against the corrected metadata.

The original and refreshed ZIPs have the same version and filename but different
bytes. Select the current E4 directory and checksum; do not identify an artifact
by filename alone. Distribution readme checkpoint prose is retained from E2;
current E3/E4 verification status is recorded here and in the development README.

## Artifacts, distribution and public source

The installer is `papijo-h5p-themer-wordpress-0.1.1-beta.1.zip`, **141,180 bytes**,
with exactly **84 files** under `papijo-h5p-themer-wordpress/`. The unchanged pinned
distribution policy excludes tests, tools, repository documents, private evidence,
configuration and generated files. Its SHA-256 is in current candidate metadata;
the candidate output includes its checksum sidecar, `inputs.json` and
`verification.json`. These sidecars are outside the ZIP.

The complete public-source projection contains **221 files**. All earlier 220
paths remain, with this E4 report added to the public policy. Updated tests,
installation guidance, maintenance tools and technical/historical documents remain
included. Production files are byte-identical to the frozen ZIP inputs. The two
public-source manifests and their checksum sidecars remain outside projected trees.

## Reproducibility and artifact validation

- Two independent disk builds match byte-for-byte, along with their sidecars,
  input manifests and verification records. Each build additionally compares two
  internally generated ZIP streams. Python **3.13.14** / zlib **1.3.1** define the
  compressed-byte reproducibility toolchain.
- Full sorted member names, exact 84-file membership, unique prefix, fixed 1980
  timestamps, regular-file modes, canonical ZIP metadata, CRCs, decompressed bytes,
  declarations and pinned license resources pass the unchanged verifier. No
  encrypted/duplicate/extra/trailing content is accepted.
- A fresh non-WordPress extraction matches all manifest bytes. The engine
  diagnostic code and every required administrator explanation are present in
  the actual ZIP. Extracted PHP is linted, not executed or installed.
- Public source is recreated independently and compared file-by-file and by
  projection digest. Its own public audit/link/private-path checks and release
  tests pass; production identity is preserved. A build from projected source
  matches the same ZIP, establishing a third independent artifact comparison.
- All **124 source PHP files and 71 extracted PHP files** pass syntax checks on
  each of the three PHP versions. All **13 source/test and three extracted
  JavaScript files** pass syntax checks; all seven Python files parse.

## Complete automated inventory

| PHP version | Passed | Failures | Excluded |
| --- | ---: | ---: | ---: |
| 7.4.33 | 47/47 | 0 | 0 |
| 8.3.18 | 47/47 | 0 | 0 |
| 8.4.22 | 47/47 | 0 | 0 |

All **141 command executions** pass, including the unchanged 32-test release suite,
functional PHP suites, official H5P-source checks, pinned Backdrop parity, browser
and HTTP suites, and all six native disposable database/lifecycle suites.
WordPress **7.1.4**, MySQL **9.1.0**, Node.js **22.23.1** and Chromium-based Edge
are the available local references; this does not qualify every supported target.

Native tests select only random disposable databases and report cleanup. Lifecycle
tests use a fresh private core and source-file plugin copy, block external work,
and remove the generated private configuration afterward. They do not bootstrap
or modify either existing WordPress installation. Read-only schema/data fingerprints
and file hashes separately verify preservation of the existing sites.

## Remaining gates

There are no remaining automated failures for this candidate. E3 manual acceptance
belongs to the original artifact; E4's refreshed ZIP has not been installed or
manually accepted. Obtain separate approval before its deployment or installation
verification, and before any online pilot or publication. The existing upstream
provenance/publication review remains a separate gate.

The **slow-network readiness limitation remains unresolved**. Passing its
characterization tests does not claim a fix. No broader browser, library, hosting
or accessibility certification is implied. E4 stops after local verification.
