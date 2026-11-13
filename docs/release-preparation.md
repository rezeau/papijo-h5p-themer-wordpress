# Proposed release and GitHub preparation

> F8 addendum: **0.1.1** is the approved first stable release version, prepared
> locally from accepted beta.3 with metadata changes only. See [F8](stage-f8-release.md)
> and [draft release notes](release-notes-0.1.1.md). The F6 risk acceptance and original
> failed F3 preservation check remain unchanged. Earlier checkpoint text below is
> historical; deployment, Git operations and publication require separate approval.

> Current F6 addendum: **0.1.1-beta.3** preparation and the project-owner's
> documented risk acceptance are recorded in [F6](stage-f6-release.md).
> The failed F3 preservation check remains historical evidence; its residual
> uncertainty is accepted and is no longer release-blocking. The Stage D/E/F3
> notices and procedures below retain their original checkpoint context.

> Historical Stage D/E preparation record. Current candidate is **0.1.1-beta.2**;
> use [F3 verification](stage-f3-release.md), [installation](installation.md) and
> [release tooling](release-tooling.md). F3 authorizes artifact construction only.
> Historical versions/counts below describe earlier checkpoints, not current inputs.

Stage E2 status: source version **0.1.1-beta.1** and local candidate artifact construction are approved. See [candidate verification](stage-e2-release.md), [release metadata](release-candidate.json) and [clean-install plan](clean-install-test-plan.md). Installation, GitHub and publication remain unapproved. Stage D/E1 statements below record their original checkpoints; current commands are in [release tooling](release-tooling.md).

Stage E1 update: offline audit/build/verify tooling is now implemented; see
[release tooling](release-tooling.md), [public development-source preparation](public-source-preparation.md)
and [E1 verification](stage-e1-readiness.md). Complete tests/fixtures and technical
documentation are retained in the public repository projection. No release ZIP,
version change or Git operation is authorized or performed in E1. The Stage D
design below remains the basis for later E2/E3 acceptance.

Stage D design, 11 November 2026. Source version remains **0.1.0**. Nothing here
is authorization to initialize Git, create a ZIP/repository, deploy, change versions,
commit, tag, push or publish. Those operations belong to separately approved Stage E.

## Installable ZIP allowlist

The exact proposed file list is [release-zip-allowlist.txt](release-zip-allowlist.txt).
It contains **84 files**. It is a closed list: future files must be explicitly reviewed
and added rather than picked up by a recursive wildcard.

| Resource | Inclusion and purpose |
| --- | --- |
| `papijo-h5p-themer-wordpress.php` | One production entry point, activation registration, headers and version constant |
| `includes/` | All 69 listed production PHP files: compatibility, Theme/domain/publication, storage, roles, Profile, administration, color transfer and runtime composition/classes |
| `assets/js/admin.js` | Administration color inputs and isolated live preview |
| `assets/js/profile-preview.js` | Own-Profile representative color preview |
| `assets/js/theme-controller.js` | Qualified Theme/Density runtime and readiness/lifecycle |
| `assets/css/admin.css`, `preview.css`, `profile-preview.css`, `runtime-readiness.css` | All four production stylesheets |
| `presets/moodle-478fed30d2a4.php` | Pinned five-palette resource; original pin/version retained |
| `languages/README.md` | Translation-domain guidance; no translated catalogs currently exist |
| `LICENSE`, `licenses/GPL-2.0.txt`, `licenses/MIT-H5P-THEME-PICKER.txt` | Required retained license resources |
| `readme.txt`, `THIRD-PARTY-NOTICES.md` | Installation/features/limitations and attribution shipped with the plugin |

There is no runtime Composer/npm dependency, vendored picker, transpilation or build
step. WordPress, official H5P and the host database are external requirements;
none are bundled. The `languages` README is informational but deliberately listed.

Excluded: `.local-acceptance/` in its entirety, `.git/` and Git configuration,
`.gitignore`, repository `README.md`, `docs/`, `tests/` (including doubles, fixtures,
HTTP servers, browser harnesses and native lifecycle helpers), tooling, backups,
SQL/configuration/credentials, logs, screenshots, generated reports and prior ZIPs.
The distribution `readme.txt` is self-contained, so the ZIP does not need development
docs. Git ignore rules alone never determine the ZIP contents.

Expected layout:

```text
papijo-h5p-themer-wordpress/
  papijo-h5p-themer-wordpress.php
  includes/                 (69 allowlisted PHP files)
  assets/js/                (3 files)
  assets/css/               (4 files)
  presets/                  (1 file)
  languages/README.md
  licenses/                 (2 files)
  LICENSE
  readme.txt
  THIRD-PARTY-NOTICES.md
```

## Reproducible packaging procedure — proposed, not executed

Implement a small standard-library Python packager in Stage E, after approval:

1. Resolve the source root, read the closed UTF-8 allowlist, reject duplicates,
   absolute paths, backslashes, `..`, symlinks/reparse points, missing entries,
   unexpected extensions and any resolved file outside the source root. Refuse
   every denied/private path even if someone adds it to the allowlist.
2. Require the approved immutable source hash manifest and verify every input
   SHA-256. Require a clean reviewed source inventory, not a recursive copy of this
   workspace. Write outputs only to a fresh dedicated build directory.
3. Compare the PHP `Version:` header, literal `PJH5PWP_VERSION`, `readme.txt` stable
   tag and current changelog entry, plus the approved release label/filename. Current
   metadata must resolve to the approved current `0.1.1-beta.1`. Historical report versions and preset catalog
   pins are not release-version fields. Audit test fixture version literals when
   an approved version change occurs; do not blindly replace historical values.
4. Sort allowlisted relative paths lexically. Use `zipfile.ZipFile`, DEFLATE level 9,
   with explicit `ZipInfo` for each regular file, a fixed timestamp
   `1980-01-01 00:00:00`, Unix file mode `0100644`, fixed creator metadata, no comments
   or extra fields, and exactly the forward-slash prefix
   `papijo-h5p-themer-wordpress/`. Read bytes unchanged; omit explicit directory entries.
   Record Python/zlib versions: byte-identical output is promised only for the
   same pinned toolchain. Identical input hashes must yield identical ZIP hashes.
5. Build twice in independent fresh output directories and compare complete SHA-256.
   Keep the payload filename versioned, for example
   `papijo-h5p-themer-wordpress-0.1.1-beta.1.zip` after version approval. Write its
   checksum and input manifest as separate release evidence; do not add unlisted
   files to the payload.
6. Reopen the archive, run `testzip()` for CRC integrity, reject duplicate names,
   traversal/absolute names, encrypted entries, symlink entries, backslashes and
   extra top-level directories. Compare the exact entry set to prefix + allowlist.
   Stream/decompress each member and compare its hash and size with the input manifest.
   Confirm the entry-point header/version again from archived bytes.
7. Extract into a fresh disposable directory using validated names. Compare the
   complete extracted file set/hashes, run PHP 7.4/8.x syntax checks there, and
   prove no test/private/build file is present. Do not execute extraction against
   a live WordPress installation.
8. Only after review, install the actual approved ZIP in a separate disposable
   WordPress/database fixture and then in an authorized online staging pilot.
   Existing source-tree/native activation tests do not replace this ZIP test.

A packaging tool now supports manifest-only audit, explicit future build and
existing-archive verification. E1 tests reject injected private entries, traversal,
symlinks/junctions, case collisions, extra directories and version mismatches using
synthetic buffers. No real archive has been created; disk packaging/extraction and
fresh ZIP installation remain E2 acceptance work.

## Repository scope and ignore rules

Recommended repository: `rezeau/papijo-h5p-themer-wordpress`, matching the plugin
slug, namespace/text-domain intent and ZIP directory. Name availability has not
been checked and no repository has been created.

A [.gitignore](../.gitignore) is prepared locally, without initializing Git. It
excludes root `.local-acceptance/`, environment/private configuration, keys/certificates,
SQL, archives/build outputs, logs, scratch/backups and local dependency/editor caches.
No evidence or backup was deleted. The rules are proposed for first publication;
Git ignore behavior itself should be checked after Git initialization is separately
approved. Ignore files do not remove already tracked data or protect force-adds.

For the public repository use a **fresh allowlisted staging tree**, containing the
production/distribution files, root README and ignore file, reviewed test sources,
and a curated documentation set. Never initialize/push the entire workspace
without a staged-file audit. Historical reports remain intact locally. Several
contain absolute Windows paths, private evidence pointers and local deployment
assumptions. Before first commit, select public-safe current documents and prepare
public copies/excerpts of useful contracts/investigations, resolve every Markdown
link, and either exclude the historical reports or obtain explicit review of their
public disclosure. Do not rewrite their local original records. E1 implements an
explicit complete public source manifest and deterministic documentation projection,
with every reviewed test/fixture and technical/historical document retained. Final
review and publication approval are still required. Repository archives include
development tests/docs/tools; the installable ZIP must not.

## License and attribution

The entry point, README/readme and overall notice declare GPL-3.0-or-later. Full
GPLv3 text is present in `LICENSE`; GPLv2 text is retained for adaptations and the
representative preview stylesheet's GPL-2.0-or-later notice. Four palettes retain
Oliver Tacke's 2026 MIT notice; Dark retains Matheus Mathias's 2026 GPL attribution.
Backdrop and Moodle commit/blob pins are recorded. Domain parity tests check the
pinned Backdrop source; no upstream picker, H5P implementation or SNORDIAN runtime
is distributed. Test fixtures are independent doubles and excluded from the ZIP.

All listed notices/resources must accompany source and binary-source distribution.
Stage D found no missing local notice resource or new vendored dependency. Remote
pinned GitHub provenance URLs could not be fetched during this review; the local
record and parity evidence are retained, not presented as fresh upstream license
authentication. Recheck provenance links before publication. A grammar correction
in the notice does not change its attribution or grants.

## Version, changelog and pre-release conventions

Recommend **0.1.1-beta.1**, with future tag `v0.1.1-beta.1` and artifact
`papijo-h5p-themer-wordpress-0.1.1-beta.1.zip`. It sorts after the installed `0.1.0`;
`0.1.0-beta.1` would sort before it. Stage E2 approved this value and current production/distribution version fields
now use `0.1.1-beta.1`; the accepted installed site remains `0.1.0`. Historical
version records and preset pins remain unchanged.

Use one reviewed version across the production header/constant, distribution
readme, current changelog, release tag/title, asset filename and checksums. Describe
actual changes under each version; retain old entries and historical report versions.
The source-only Stage D changes are recorded under current 0.1.0, not advertised as
an already published release. Never replace an already published version's bytes.
SemVer describes pre-release identifiers and precedence in its
[specification](https://semver.org/spec/v2.0.0.html).

Prepare release notes covering features, exact tested references, target-only
minimums, single-site/storage requirements, SNORDIAN behavior, explicit installer
asset instructions, preservation/rollback and the unresolved readiness failure.
Prepare a GitHub draft only after repository authorization; attach the verified
plugin ZIP/checksum, set **pre-release**, and leave it unpublished until the final
approval. GitHub supports draft releases, attached artifacts and the pre-release
flag; see [official release instructions](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository).
Do not add a “latest stable” or production-ready claim. GitHub automatic source
archives are not substitutes for the dedicated plugin ZIP.

## Stage E gates and implementation sequence

1. Review Stage D findings; approve the exact Stage E scope and version separately.
2. Prepare public documentation projection and an exact repository file manifest;
   check private-file rejection and link closure. Preserve all local evidence.
3. With version approval, make only the coherent metadata/version update (and any
   separately approved production-header wording). Run the full inventory and
   verify ordering/cache asset versions. The slow-network fix stays a separate task.
4. With packaging approval, implement the dry-run/allowlisted packager and rejection
   tests, then build twice and verify ZIP structure, hashes, syntax and manifest.
5. With installation approval, test that ZIP on a fresh disposable WordPress and
   database; verify activation, degraded setup/retry, explicit Theme initialization,
   default/role/Profile/transfer behavior, reactivation preservation and SNORDIAN
   suspension. Do not use acceptance.example.test as a fixture or change its active plugins.
6. With online pilot approval, install on a private backed-up staging site, test
   target hosting/browser/library/cache behavior and document acceptance of the
   unresolved readiness limitation. Keep pilot credentials/evidence private.
7. With Git/repository approval, initialize only the audited public tree; inspect
   staged files (including secret/path scan and ignore checks) before committing.
   Create the approved GitHub repository and draft/tag only when authorized.
8. Present the exact source revision, artifact hashes, release notes, test outcomes
   and remaining risks for final publication approval. Publish only on that approval.

No step here authorizes deployment, a version change, Git initialization or release
creation now. A readiness/runtime correction requires separate production approval.


> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.
