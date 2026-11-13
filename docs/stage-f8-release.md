# Stage F8 — first stable release preparation

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Approved stable version: **0.1.1**. Prepared locally from accepted **0.1.1-beta.3**.
Deployment, database changes, Git operations and publication are outside F8 scope.
See [draft release notes](release-notes-0.1.1.md) and [release metadata](release-candidate.json).

## Baseline integrity and acceptance

Before changes, all **250 source files** matched the retained F6 source snapshot.
The audited 93 production files matched both the frozen F6 inputs and retained
beta.3 ZIP payload. Beta.3 remained **153396 bytes**, SHA-256
`PRIVATE_VERIFICATION_DIGEST_OMITTED`;
its production input digest was
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Archive structure, canonical metadata, payload bytes and CRCs were verified.

The four retained F6 result inventories verify **208/208 commands PASS**, with
zero exclusions. F4's **25/25 Firefox acceptance tests on beta.2** and F7's
**3/3 Firefox smoke tests on beta.3** are user-reported. F7 acceptance is supplied
by the F8 project-owner request. Codex did not independently reproduce those
manual results and does not claim new Firefox testing on the stable bytes.

## Change boundary

The production inventory remains the same closed **93-file** allowlist. Only two
payload files change: the entry-point header/runtime version becomes 0.1.1, and
`readme.txt` gets the stable tag, current release description, installation wording
and a new changelog entry. Historical beta changelog sections remain unchanged.
All other 91 payload files are byte-identical to beta.3. No implementation,
stylesheet, script, storage schema, permissions, default handling, runtime or
transfer algorithm changes. Minimums remain **WordPress 6.5 / PHP 7.4**.

Development changes update current release guidance, current input/candidate
metadata and existing release assertions. Frozen beta.3 metadata/inputs are added
as `release-candidate-beta3.json` and `release-f6-inputs.json`; draft notes and this
record are added to the public-source allowlist. Historical F3/F6 reports and all
retained beta artifacts are preserved. The private F8 source diff records every
changed/added file and full textual differences.

## Verification boundary

F8 runs the available non-database regression inventory on PHP 7.4.33, 8.3.18 and
8.4.22, plus validation of a fresh public-source projection. Exact commands and
results are retained in the private Stage F8 output directory. Browser fixtures
use Chromium/Edge; they are not Firefox tests. No existing WordPress installation
or configuration is bootstrapped or modified.

All **nine native database/lifecycle jobs are excluded** because F8 prohibits
database changes, including newly created disposable databases. Excluded suites:
`storage/mysql`, `profile/mysql`, `roles/mysql`, `import/mysql`,
`profile/installation-mysql`, `owned-storage/mysql`,
`owned-storage/production-mysql`, `profile/activation-wordpress` and
`owned-storage/wordpress-innodb`. They are not presented as stable-version passes.
F6 native results remain historical evidence for unchanged implementation bytes.
Current doubles cover owned storage and failure handling, MyISAM option admission,
Theme inheritance/defaults, Profile Theme/Density, authorization/security,
SNORDIAN coexistence, runtime styling and colors-only transfer.

Syntax checks use `php.exe -n -l` for reviewed PHP files on all three versions,
`node --check` for JavaScript and Python `ast.parse` without bytecode; JSON release
metadata is parsed. Production PHP is also checked after safe ZIP extraction.
PHP `version_compare` must place 0.1.1 after beta.3. Full matrix command form:

```text
python -B tests/run-all.py --php <php.exe> --h5p-root <official-H5P-source> --wp-source <copied-core-read-only> --backdrop-root <pinned-Backdrop-source> --browser <Edge.exe> --report-dir <fresh-private-report>
```

Database credentials and WordPress fixture activation arguments are deliberately
omitted. The runner groups its nine database exclusions into one inventory notice;
the nine jobs are enumerated above. Release tooling retains all adversarial policy,
path, junction, license, archive, stale-input and historical metadata assertions.

Working-source results: **43/43 PASS on each PHP version**, or **129/129 commands
PASS**. All 32 tooling tests pass in each run. Source syntax passes for 144 PHP
files on each version, 13 JavaScript files, seven Python files and nine JSON files.
All 80 extracted production PHP files lint on each version. PHP version ordering
passes on all three. The syntax/extraction script records 688 external commands,
all PASS, plus in-process Python/JSON parsing and 93 extracted byte comparisons.

The initial restricted-process PHP 7.4 matrix was 26/43 because fixture reads,
native junction creation and loopback connections were unavailable. An untouched
beta.3 control reproduced the dependency-classification failure. Normal-permission
matrix repeats passed all 43 jobs on every PHP version without weakening or skipping
assertions. Pre-build byte comparisons also caught accidental newline normalization;
original line endings were restored before any stable ZIP was built. No functional
source correction was necessary.

## Risk disposition and accepted limitations

The [original F3 preservation check](stage-f3-release.md) **did not pass**. The
unchanged [F6 record](stage-f6-release.md) documents **DOCUMENTED RISK ACCEPTANCE**:
the project owner accepts residual uncertainty, requires no further investigation
for this release, and removes the release-blocking gate. The cache-timeout writer
and trigger remain unattributed; no corruption was demonstrated. Preservation
was not proven. F8 does not access databases or rewrite historical evidence.

1. No Papi Jo styling in the WordPress H5P administration viewing interface.
2. No Papi Jo styling in Subscriber Profile > View H5P content.
3. Expiry of the initial 15-second readiness budget on slow networks may leave
   native fallback styling without automatic personal styling for that document.

These limitations remain accepted and unresolved. Shared hosting and exhaustive
browser/library/minimum-WordPress combinations remain unverified.

## Packaging and publication gate

Build into fresh Stage F8 directories only, using independently audited approved
inputs. Verify two independent ZIP builds, exact ordered inventory, one top-level
plugin directory, stable metadata, every payload hash/byte, CRCs and absence of
development/private files. Repeat the public-source projection and reproduce the
same ZIP from it. ZIP identity is scoped to the recorded Python/zlib toolchain.

The independently verified `build-a` and `build-b` archives are byte-identical:

- Filename: `papijo-h5p-themer-wordpress-0.1.1.zip`.
- Inventory: **93 production files**; size: **153498 bytes**.
- SHA-256: `PRIVATE_VERIFICATION_DIGEST_OMITTED`.
- Input digest: `PRIVATE_VERIFICATION_DIGEST_OMITTED`.
- Toolchain: Python 3.13.14 / zlib 1.3.1.
- Compared with beta.3: entry-point payload 1669 → 1655 bytes (two version
  strings); distribution readme 8767 → 9266 bytes (stable metadata/release text).
  Compression changes are -5 and +107 bytes respectively, accounting for the
  entire **+102-byte ZIP difference**. All other payload bytes are unchanged.
- All **5262 retained F3/F6 evidence files** remain unchanged, with no additions
  or removals. Frozen F6 candidate/input records were copied byte-for-byte.

Final public-projection reproducibility and its non-database regression results
are retained in the private completed F8 report to avoid a self-referential
projection digest. All 254 reviewed public-source paths are explicitly allowlisted;
no private test reports, generated archives or database credentials are included.

Proposed tag metadata is `v0.1.1`, with `github_prerelease: false`. This creates no
tag or hosted release. Review the completed local report, exact checksum and draft
notes, then obtain explicit publication/deployment authorization. No publication,
installation, database change, commit, tag, push or GitHub/WordPress.org operation
is performed in F8.
