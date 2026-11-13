# Stage F3 — beta.2 release candidate

Candidate: **0.1.1-beta.2**, for fresh-installation Firefox acceptance. This stage
builds and verifies artifacts only. No deployment or existing-site database write
was issued by this work. The final existing-database preservation check nevertheless
failed as detailed below; unchanged databases cannot be certified. No Git repository
or release is initialized or published.

## Release implications

F2 production integration uses `<prefix>pjh5pwp_state`, an InnoDB table with separately
keyed `theme_library` and `role_permissions` records. Exact serialized payloads,
stable identities, schema/revision checks, unique initialization, conditional writes
and transaction-held permission locking remain unchanged by F3. The independent
InnoDB Profile table and Theme/Density runtime algorithms are preserved.

WordPress options may use MyISAM or InnoDB. Papi Jo does not convert or alter
WordPress-owned tables. Activation verifies or creates empty State/Profile tables;
Theme initialization and role grants remain explicit. Read-only status, protected
setup retry, failed/unknown outcomes and incompatible-table refusal are documented
in [installation](installation.md) and [F2 architecture](owned-storage-stage-f2.md).

Legacy pre-F2 Theme/permission options are not migrated, deleted or reset. Either
legacy option blocks Theme/permission editing and preference writes, including when
owned state exists. Preserve the old code/database and use a separate fresh site.
Do not delete legacy options to bypass the guard, or downgrade F2/F3 databases to
code that cannot read their owned state.

## Artifacts and verification

Build outputs belong only to the new Stage F3 output directory. Approved membership
remains **93 production files**. Public source additionally retains all tests,
technical/historical documentation, tools and F1 compatibility aliases. The previous
E2/E4 beta.1 ZIPs, manifests, checksums, projections and acceptance evidence are
preserved; [beta.1 metadata](release-candidate-beta1.json) remains byte-identical.
Current [candidate metadata](release-candidate.json) describes beta.2 only.

The production version header, runtime/asset version constant, distribution stable
tag and newest changelog agree on beta.2. F3 changes release metadata/documentation
only; storage/runtime source remains the verified F2 implementation.

The independently built ZIPs in `build-a/` and `build-b/` are byte-identical:

- Filename: `papijo-h5p-themer-wordpress-0.1.1-beta.2.zip`
- Distribution: **93 files**, **153067 bytes**, one correctly named plugin directory.
- ZIP SHA-256: `PRIVATE_VERIFICATION_DIGEST_OMITTED`
- Canonical production-input digest: `PRIVATE_VERIFICATION_DIGEST_OMITTED`
- Toolchain: Python 3.13.14, zlib 1.3.1.

The archive has the eight owned-storage classes and loader, with production
composition selecting those adapters. Every entry is an approved distribution
file; tests, fixtures, foundation aliases, tools, technical docs and private data
are absent. Full archive/member bytes, CRCs, canonical metadata and source equality
are verified. Compared with F2, **91/93 payload files are byte-identical**; only the
version entry point and distribution readme change. All production paths are unchanged.

The original beta.1 E4 ZIP remains at its recorded SHA-256
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Historical conversion commands in older acceptance reports describe those earlier
checkpoints; they are not current installation instructions. Current guidance
requires InnoDB only for owned tables and never conversion of WordPress options.

## Complete automated matrix

| Source | PHP | Commands | Exclusions |
| --- | --- | --- | --- |
| Current beta.2 | 7.4.33 | 52/52 PASS | 0 |
| Current beta.2 | 8.3.18 | 52/52 PASS | 0 |
| Current beta.2 | 8.4.22 | 52/52 PASS | 0 |

All **156/156 required commands pass**. Native tests use MySQL 9.1.0 and copied
WordPress 7.1.4. The matrix includes F1 isolated memory/native contracts, actual
production factories with MyISAM options, synchronized initialization/CAS races,
role-revocation/Profile-save serialization and native WordPress lifecycle with
both MyISAM and InnoDB options. The complete job list is retained in [testing](testing.md).

All 32 release-tooling tests pass, including exact current/frozen manifests,
independent production policy, unsafe paths, duplicate members, Windows junctions,
corruption and stale inputs. No assertion is disabled or relaxed. All 144 PHP files
lint on each version; 13 JavaScript files and seven Python files pass syntax checks.
PHP version ordering confirms beta.2 follows beta.1 on every version.

A public-source validation copy additionally passes **52/52 commands on PHP 8.4.22**,
including all native suites, with zero exclusions: **208/208 test commands overall**.
The initial extra fixture copy exceeded Windows path limits; moving it to a shorter
private root allowed all **3335 core files** to be copied and byte-verified (maximum
path 247). No core file or test was omitted. Delivered projections contain no fixture.

## Preservation gate — failed, retained as a blocker

Read-only before/after fingerprints match the complete `acceptance.example.test` database.
They detect drift in `wp-release-test.wp_options` and `wp-fresh-test.wp_options`:

| Site | Rows before/after | AUTO_INCREMENT before/after | Changed row |
| --- | --- | --- | --- |
| wp-release-test | 187 / 187 | 270 / 272 | One `wp_theme_files_patterns-*` site-transient timeout |
| wp-fresh-test | 179 / 179 | 202 / 204 | One `wp_theme_files_patterns-*` site-transient timeout |

A later read shows release-test AUTO_INCREMENT 273, demonstrating further drift.
Retained per-option hashes identify the exact two option names. Reconstructing the
prior timeout rows and SHOW CREATE strings reproduces the original hashes exactly:
the changed values are expiry timestamps, and the table-definition difference is
only AUTO_INCREMENT. Every other retained option hash and every other table
fingerprint matches. Plugin state, Profile data, role permissions and SNORDIAN
settings match their baselines.

The installed WordPress code can renew theme-pattern caches with a timeout; that is
consistent with these changes, **not proof of the writer or trigger**. Apache common
logs show concurrent local activity but lack virtual-host attribution; database
general and binary logging are OFF. No retained SQL audit establishes the cause.
No repair, restore, option deletion, counter reset or logging change is attempted.
The failed preservation result remains in private evidence and is not rebaselined
or excluded to manufacture a PASS. Requirement 14 is **not fully satisfied**.

File preservation passes: **22689 installed-site files** unchanged, with no added
or removed files, across the three named sites; **19079 historical evidence files**
and all **7 historical ZIP hashes** unchanged. The beta.1 metadata and all original
E2/E4/F1/F2 reports/manifests remain preserved. Preservation of files and passing
functional tests do not override the separate failed database gate.

## Final artifact layout

The new output root is `PRIVATE_EVIDENCE_OMITTED`:

- `build-a/` and `build-b/`: independent canonical ZIPs, input manifests, checksum
  sidecars and archive verification records.
- `approved-inputs.json`: the actual reviewed beta.2 production manifest.
- `public-source/` and `public-source-repeat/`: repeated final public projections,
  **247 files** each; their manifests stay outside the source trees.
- `build-from-public/`: independent rebuild from the final public projection;
  checked against both original builds and the reviewed production inputs.
- `php-7.4.33-final/`, `php-8.3.18-final/`, `php-8.4.22-final/` and
  `public-php-8.4.22/`: complete command logs/results.
- `artifacts.json`: final counts, hashes and reproducibility results, outside the
  public tree to avoid making the projection hash depend on itself.

Canonical projection and manifest-file hashes are recorded in the external
`artifacts.json` after final materialization. All production files remain identical
between source, public projections and ZIP. Exact verification results are finalized
outside the public tree; no original artifact is overwritten.

## Acceptance gate and remaining limitations

After explicit approval, execute the [fresh-install Firefox plan](clean-install-test-plan.md)
with the beta.2 ZIP: MyISAM and InnoDB options on separate disposable sites, empty
owned tables, explicit Theme initialization, CRUD/publication/global defaults,
role grants/revocation, independent Profile Theme/Density preferences, stale forms,
transfer, reactivation preservation, SNORDIAN suspension/restoration and negative
storage/legacy fixtures. Record exact browser/core/H5P/content-library versions.

Declared minimums remain WordPress 6.5 / PHP 7.4; they are not a complete hosting or
core-version compatibility matrix. Standard single-site wpdb/mysqli, private
nonpersistent connections, metadata access and InnoDB for owned tables are required.
Multisite and unsupported adapters fail closed. The H5P admin View enhancement and
slow-network readiness issue remain deferred. This is a testing pre-release, not a
production-readiness claim. Deployment and publication await separate approval. The failed database-preservation
gate also requires review before proceeding; no fully verified release claim is made.


> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.
