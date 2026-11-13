# Complete public development repository

The proposed repository is `rezeau/papijo-h5p-themer-wordpress`. No Git repository,
remote repository, commit or release is created by this procedure.

## Included development source

The explicit [public source policy](public-source-allowlist.txt) retains:

- All production PHP, JavaScript, CSS, palettes and license resources.
- All automated tests, synthetic fixtures, fault/concurrency doubles, browser and
  loopback HTTP harnesses, parity checks and native disposable lifecycle tests.
- Every technical/architectural document and useful historical phase report.
- Current README, installation/testing/troubleshooting and maintenance instructions.
- The deterministic packager, validation helpers, public projection tool and tests.
- `.gitignore` and `.gitattributes`, including private-file exclusions and stable bytes.

The public policy is independent of the 93-file installable ZIP policy. Production
must be byte-identical in the development repository and the ZIP input. No tests
or technical documents are dropped just because WordPress does not need them.

## Private exclusions

Never publish `.local-acceptance` or descend into it during source preparation.
It contains backups, SQL snapshots, private configuration/evidence and local-only
deployment/verification helpers. Also exclude Git internals, environment/config/key
files, private JSON, database dumps, archives, generated reports/logs/bytecode,
dependency caches and build/editor output. Existing local files are not deleted.
`.gitignore` reinforces this; a fresh, explicit reviewed projection is the public
input. Unknown public candidates fail validation instead of being silently omitted.

Tests intentionally contain fake credentials/salts, diagnostic sentinels and
synthetic sessions. They remain useful public test fixtures. They do not include
real passwords or site configuration. Browser fixtures have an overridable generic
Windows Edge install default, with no personal account path.

## Historical documentation projection

Run the read-only policy and link audit:

```text
python -B tools/prepare_public_source.py
```

To materialize a new complete development-source tree for review:

```text
python -B tools/prepare_public_source.py --output <fresh-isolated-directory>
```

The destination's parent must already exist. Existing destinations are refused.
Inside the development workspace, only ignored build/private output roots are
allowed; production directories cannot be used as projection destinations.
This is an ordinary source directory, not a plugin ZIP or a Git repository.

Each original Markdown document under docs/ is preserved locally. Public copies
keep the same relative filenames, technical sections, test findings, code examples,
checkpoint status and source commit/blob pins. Transformations replace absolute
machine paths with source-root placeholders, private evidence file references
with omission markers, local host/browser profile identifiers with examples, and
verification fingerprints/checksums with explicit redactions. Private Markdown
links become descriptive text; retained local document links must resolve within
the complete projected file set. Changed copies get a visible projection notice.
The transformation is deterministic and idempotent; it does not silently turn old
acceptance claims into new compatibility guarantees. Historical local helper scripts
remain private; current supported testing/release commands are in CONTRIBUTING.md.

The source audit also rejects common embedded private-key/token shapes and
unprojected machine paths. This is a reviewed bounded scan, not proof that every
possible secret format is detectable. Inspect the exact projected tree and report
before any first commit. Original private evidence is never an input to this scan.

The printed report contains the complete projected file list, SHA-256/byte counts,
changed-document list, projection digest and production-identity result. It does
not copy reports into the public tree. Repeat projection should yield the same
digest and bytes. Check the projected copy's own audit/tests before Git approval.

## Repository and release layout

The public repository keeps the normal source layout: entry point and distribution
readme/license at root; includes/, assets/, presets/, languages/, licenses/, tests/,
docs/ and tools/; README/CONTRIBUTING and Git policy files. GitHub source archives
will contain this complete development tree and are not the dedicated installer.

The current stable release prepared is **0.1.1**. [F8](stage-f8-release.md) prepares the
installable payload and complete public-source projection in a new output directory.
The ZIP contains 93 approved production files; the public tree additionally retains
all tests, foundation compatibility aliases, tools and technical/historical documents.
Production bytes must match exactly. Repeated projections and a ZIP built from the
projected source must reproduce the original source build.

E2/E4 beta.1, F3 beta.2 and F6 beta.3 artifacts, frozen production manifests and acceptance
evidence remain preserved. F2's 245-file source checkpoint is also historical; current membership is
verified from the explicit public allowlist, including F3/F6/F8 reports and retained
beta.1/beta.2/beta.3 candidate metadata. No old output is replaced.

Proposed future tag: `v0.1.1`; title: `0.1.1`;
installer: `papijo-h5p-themer-wordpress-0.1.1.zip`. Stable metadata sets the
pre-release flag to false; publication still requires separate authorization. Repository/tag objects are not created.

Release notes should cover: purpose/test audience; features and changes; exact
tested matrix versus declared targets; storage/H5P/SNORDIAN prerequisites; dedicated
installer filename and checksum; fresh install/update/data preservation; known
slow-network readiness limitation and other exclusions; pilot result; source/license
provenance; and reporting/reproduction instructions without private cookies/config.

After E2 artifact validation and an authorized E3 pilot, review the final staged
public file set, secret/path checks, ignore behavior and link closure before the
first commit. Repository creation, tagging, draft release and final publication
each require the maintainer's authorization. This tooling performs none of them.

Version 0.1.1 retains owned InnoDB storage. WordPress options may use MyISAM. Legacy pre-F2
Theme/permission options are preserved and refused without migration; use a separate
fresh installation. See [F2 storage integration](owned-storage-stage-f2.md).
