# Development and maintenance

This is the complete development source for Papi Jo H5P Themer for WordPress.
Production code, independent test fixtures, architecture/technical records and
offline release tooling are maintained together. The installable plugin has its
own smaller, independent 93-file policy.

## Prerequisites and tests

Installation has no Composer/npm/build dependency. Development tests use PHP CLI,
Node.js (22 or later for the browser harness WebSocket API), Python (3.9 or later
for tooling) and optionally Chromium/Edge. H5P source-dependent checks need a
read-only canonical official H5P source copy; parity checks optionally need the
pinned Backdrop v0.5.0 source. External sources are supplied by the maintainer;
the tooling does not download dependencies or contact a network service.

```text
python -B tests/release/test_tooling.py
python -B tools/package_release.py audit --expect-version 0.2.0
python -B tools/prepare_public_source.py
```

These commands validate the 0.2.0 source and synthetic in-memory archives; they do not build
a real plugin ZIP. No WordPress bootstrap or database connection is involved.
The public audit prints hashes and the complete proposed public file list.

For existing PHP/JS/browser/HTTP checks, replace the argument placeholders:

```text
python -B tests/run-all.py --php <php.exe> --h5p-root <H5P-source> --wp-source <WordPress-source> --backdrop-root <Backdrop-source> --browser <chromium.exe> --report-dir <fresh-private-report-directory>
```

With parity enabled, the complete inventory has **54 commands**: 45 non-database
commands and nine explicitly opted-in disposable database/native-lifecycle commands.
Use `--database-credentials <literal-config-source>` and `--wordpress-fixture <new-private-copied-core>`
to include every native job; the source DB_NAME is never used for test writes. See
[testing](docs/testing.md) for the complete matrix and isolation rules.
Never point native lifecycle tests at a real WordPress installation. Only a fresh
private core fixture and random disposable databases are permitted. Test password,
session and secret-like constants are deliberate fake values, not live credentials.

Keep test logs and native fixtures in an ignored private workspace. On Windows,
restricted process tokens may make PHP report readable fixtures as unreadable;
use an authorized normal CLI rather than weakening compatibility assertions.

## Change discipline

Keep production changes separate from release tooling/documentation changes.
Preserve Theme/Density independence, no-store session-derived appearance, exact
runtime admission, conditional ownership/restoration, storage revision checks and
SNORDIAN suspension. A passing slow-readiness characterization suite records an
unresolved defect; it does not permit claiming that defect fixed.

Run checks appropriate to the changed layer. PHP production syntax must remain
compatible with the declared 7.4 target. Native WordPress/database/browser results
apply to their exact recorded matrix, not all hosting or content-library versions.
Do not update a version, install/deploy, modify active plugins or publish without
the maintainer's authorization for that stage.

## Two inclusion policies

- `docs/public-source-allowlist.txt` covers the complete useful development tree:
  production, all reviewed tests/fixtures, all technical/historical docs, tools,
  maintenance guidance, license resources and Git configuration rules.
- `docs/release-zip-allowlist.txt` covers only the 93 installable plugin files.
  Tests, tools, repository docs and private data cannot enter the plugin ZIP.

Add new useful public development files to the public policy after review. New
production files require review of the ZIP policy as well; its approved path set
is pinned independently in the tooling. Never copy directories broadly to package.
Unknown production files and unknown public source candidates are validation failures.

Use [the projection procedure](docs/public-source-preparation.md) to prepare public
copies of historical docs without altering private originals. A projected repository
has the same complete file layout and can run the same tools and tests.

## Release work

See [release tooling](docs/release-tooling.md). `audit` is read-only unless explicitly
given a new manifest filename; `build` is the separate artifact-creation command.
Stage G4 authorizes stable 0.2.0 metadata/documentation preparation, local verification
and packaging only. The accepted beta.1 implementation is preserved; 17 G3B Firefox
passes are owner-reported. See [G4](docs/stage-g4-release.md) for current results,
explicit native-suite exclusions, the unchanged readiness limit and accepted G3A
database uncertainty. Deployment and Git/publication require separate approval.
New versions must agree
across the plugin header/runtime constant, readme/changelog, tag and artifact.
Use GitHub pre-releases for test releases and retain the documented readiness limits.
Preserve license texts and attribution. `.gitattributes` prevents newline conversion
so reviewed source/license hashes survive a Git checkout on another platform.

Beta.2 has 93 production files; the preserved E4 beta.1 candidate has 84.
See [F8 release preparation](docs/stage-f8-release.md) and [F2 storage integration](docs/owned-storage-stage-f2.md).
Frozen beta.1/beta.2/beta.3 metadata/input assertions and current candidate/input assertions are
validated independently. Existing sites with pre-F2 options have no automatic migration.
