# Offline release tooling

Current stable release candidate: **0.2.0**, under [G4 stable preparation authorization](stage-g4-release.md). Published stable 0.1.1 is unchanged.
See [G4 preparation](stage-g4-release.md), [release notes](release-notes-0.2.0.md) and [current metadata](release-candidate.json).
Deployment and publication require separate approval. The original failed F3
preservation check and F6 documented risk acceptance are retained without alteration.

Beta.1, beta.2 and beta.3 artifacts and evidence are preserved. Frozen assertions use
`release-candidate-beta1.json` / `release-e4-inputs.json`,
`release-candidate-beta2.json` / `release-f3-inputs.json` and
`release-candidate-beta3.json` / `release-f6-inputs.json`.
Accepted G2 metadata/inputs are frozen in `release-candidate-0.2.0-beta.1.json`
and `release-g2-inputs.json`; all 91 non-metadata/documentation payloads remain identical.
G4 records 17/17 owner-reported Firefox passes, scoped resolution of the two viewing
limitations, unchanged readiness and accepted G3A database uncertainty. It excludes
the nine database/lifecycle jobs under the no-database-changes constraint.
Current checks separately require equality of stable audited inputs,
`development-inputs.json` and `release-candidate.json`. F8 changes only version and
release metadata/documentation and their checks. F8 excludes all nine native
database/lifecycle jobs because its authorization prohibits database changes;
F6's 208/208 PASS results remain historical evidence, not fresh stable native tests.

## Independent policies

The ZIP uses the closed, sorted [93-file working-source policy](release-zip-allowlist.txt). Its
canonical path list is independently SHA-256 pinned in `tools/release_common.py`.
Deleting, adding, duplicating or changing case in that policy fails validation.
Changing the pin requires deliberate review when production membership changes.

The [public repository policy](public-source-allowlist.txt) includes every reviewed
production, test/fixture, technical/historical doc, tool and maintenance file.
It is deliberately larger. A file being excluded from ZIP is no reason to exclude
it from the development repository. Both policies refuse private evidence/config,
backups, database snapshots and generated artifacts.

## Read-only input audit

```text
python -B tools/package_release.py audit --expect-version 0.2.0
```

This checks all 93 working-source files, exact production inventory (including unreviewed files
with unexpected extensions), safe relative names, duplicates/case collisions,
regular file types, every path component and Windows reparse points/junctions.
Private evidence and ignored output trees are never traversed. Tests/docs/tools
are outside the ZIP but remain available for independent public-source auditing.

Validation checks the entry-point PHP opening tag and WordPress header; exact name,
WordPress 6.5/PHP 7.4 targets, text/domain paths and license declarations; exactly
one runtime version constant matching header, readme stable tag and newest changelog;
activation registration; required runtime/Profile/loaders/assets/preset files;
full pinned license bytes and attribution/provenance markers. These structural
checks do not replace PHP/browser/native tests or authenticate remote provenance.

The output JSON contains sorted path/byte-size/SHA-256 entries, version and target
metadata, and a canonical whole-input-manifest digest. It contains no machine root,
credential or clock value. To save it, use `--manifest-out <new-private-json-file>`;
the parent must already exist and the file must not exist. No source is changed.
Requesting a version different from the current source fails; the tool never rewrites version fields.

## Authorized local build command

The G4 request authorizes stable 0.2.0 local builds. Installation requires separate approval:

```text
python -B tools/package_release.py build --expect-version <approved-version> --approved-manifest <reviewed-inputs.json> --output-dir <fresh-output-directory>
```

The build rereads and validates current source and requires exact equality with
the reviewed manifest. It does not create or edit production/version fields.
It creates two ZIP byte streams and compares them before writing anything. It
validates their entry set, bytes and CRCs, then rechecks source immediately before
writing to a new isolated directory. Existing output is refused. Output inside
source is limited to ignored build/private trees, never production directories.
Keep the source quiescent during audit/build; the tool is not a hostile concurrent
filesystem sandbox or a cryptographic signature system.

Archive names contain exactly `papijo-h5p-themer-wordpress/` plus each approved
relative path, in lexical order. Files have timestamp 1980-01-01 00:00:00, Unix
regular-file mode 0644, fixed creator/extractor metadata, DEFLATE level 9 and no
comments/extra fields/directory entries. No network, WordPress, Git, npm or Composer
operation is invoked. Pin Python/zlib for identical compressed archive hashes.

The fresh output directory contains:

- `papijo-h5p-themer-wordpress-<version>.zip`
- its `.zip.sha256` sidecar (digest and artifact filename)
- `inputs.json` (reviewed payload manifest)
- `verification.json` (archive digest/size/member count, input digest and toolchain)

The manifest and verification sidecars stay outside the ZIP. Byte identity of the
payload manifest is portable; compressed ZIP identity is checked for the recorded
Python/zlib toolchain. ZIP build never includes all development files by accident.

## Refreshing a candidate after an approved source change

There is no separate refresh command. Use the existing audit/build/verify sequence
under explicit authorization for the source change and new artifacts:

1. Preserve the old candidate JSON, frozen input/public manifests, ZIPs, checksum
   sidecars and reports. Record their hashes before work. Use a new stage output
   directory; never overwrite or relabel an older artifact. A retained version
   and filename do not identify identical bytes: use the stage and SHA-256.
2. Run `audit --expect-version <approved-version> --manifest-out <new-inputs.json>`.
   Review the exact path/hash delta against the old frozen inputs and the approved
   change. Freeze this newly audited manifest; do not edit hashes to bypass a check.
3. Run `build` independently into two fresh directories using that manifest, then
   `verify` each archive. Compare full ZIP bytes, hashes and sidecars. Inspect the
   changed payload and safely extract/lint in a new non-WordPress directory.
4. Refresh `docs/release-candidate.json` from the actual verified archive digest,
   byte count and manifest's canonical `inputs_sha256`. Retain the old hashes as
   history and record the refresh stage. This JSON is outside the installable
   payload, so this does not introduce a circular input hash. Keep the version
   unchanged only when the maintainer explicitly authorizes it.
5. Run the complete automated matrix, including native disposable lifecycle and
   database suites. Every command must pass without exclusions. Leave candidate
   metadata, stale-manifest, exact-membership and all other assertions intact.
6. Finalize the current documentation and refresh record. Add new development
   documents to the public-source policy; production membership still requires
   its own independent ZIP-policy review. Recheck relevant documentation/tooling
   validation after documentation-only edits.
7. Materialize the final public-source projection twice into fresh directories,
   retain its manifests outside the public trees, and compare every byte and the
   projection digests. Audit/test the projected source itself. A build from that
   source must match the same approved installable inputs and ZIP bytes.
8. Record file counts, manifest-file hashes, canonical input/projection digests,
   toolchain, matrix results and preserved-site/historical-artifact comparisons.
   Keep private credentials, fixtures and raw evidence outside the public tree.

Any source change affecting a frozen manifest requires a new audit and fresh
outputs. Documentation-only changes outside the ZIP still require regeneration of
the public projection. Artifact refresh does not authorize installation, deployment,
Git initialization or publication, nor extend earlier manual acceptance to new bytes.

## Existing-archive verification

```text
python -B tools/package_release.py verify --expect-version <approved-version> --approved-manifest <reviewed-inputs.json> --archive <existing-plugin.zip>
```

Verification checks the reviewed inputs, exact ordered member list/prefix,
duplicate/case-colliding or unsafe paths, file sizes and decompressed bytes/hashes,
CRC integrity, encryption/directory/link metadata, release declarations inside the
archive, canonical headers and absence of prepended/trailing data. It reconstructs
the canonical stream with the same toolchain and compares complete bytes. It
does not extract or execute content and never overwrites an archive.

## Test coverage and boundaries

`python -B tests/release/test_tooling.py` uses synthetic 93-path payloads, real
retained license text, and in-memory adversarial ZIPs. The actual source is audited
and hashed but never passed to an archive writer during E1. Tests intercept the
future build's file writer to check sidecar naming/hashes without writing ZIPs.
They cover missing/extra resources, policy/path attacks, symlink and reparse guards,
a real Windows junction, version/header/changelog/licensing failures, deterministic
metadata/order, changed/missing/duplicate/private archive entries, corruption,
trailing data, stale manifests, output preservation and public documentation checks.

Stage E2 constructs and independently verifies repeated disk builds, extraction
and syntax. These steps do not install or activate the ZIP. The clean-install
plan requires subsequent approval. Online pilot and GitHub operations remain
separate authorized work. The slow-network readiness
limitation is unchanged and must remain in pilot/release notes.
