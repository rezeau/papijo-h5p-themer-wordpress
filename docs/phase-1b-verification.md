# Phase 1B verification — 7 November 2026

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

## Foundation review and scope

Read architecture, baseline and Phase 1A verification before editing. The existing
18 scenarios / 405 assertions passed before implementation. No behavioral
foundation inconsistency was found. The earlier architecture proposal included a
production storage adapter; the explicit Phase 1B request narrowed this to an
interface/schema and isolated double. This scope correction was reported before
edits. Backdrop publication-absence and density-projection differences were also
reported before implementation and are detailed in theme-domain.md.

The authoritative source HEAD remains v0.5.0 commit
`d9b53d706a94dac322b89885bf668145ab3803c4`. Its complete non-Git file-content hash
matches the Phase 1A protected baseline; source was inspected and loaded read-only.
No SNORDIAN implementation was imported or used as the domain source.

## Test results

| Runtime | Phase 1A | Phase 1B | Syntax |
| --- | --- | --- | --- |
| PHP 7.4.33 | 18 scenarios / 405 assertions PASS | 2,315 assertions PASS | 30 PHP files PASS |
| PHP 8.3.18 | 18 scenarios / 405 assertions PASS | 2,315 assertions PASS | 30 PHP files PASS |
| PHP 8.4.22 | 18 scenarios / 405 assertions PASS | 2,315 assertions PASS | 30 PHP files PASS |

The 919 Backdrop parity assertions are a subset of the 2,315 domain assertions;
do not add them to that total. Commands, run from this project:

```powershell
php -n tests/run.php 'WORDPRESS_ROOT/wp-content/plugins/h5p'
php -n tests/domain/run.php 'BACKDROP_REFERENCE_ROOT'
```

The domain tests can also run without the optional source path, omitting the
parity block. The oracle loads unmodified Backdrop functions with memory-only
configuration/state collaborators, not a Backdrop installation bootstrap. Its
write methods throw. No oracle function that persists or initializes CMS state
is invoked. Optional H5P probing likewise loads definitions, not its plugin bootstrap.

Coverage includes all five complete catalogs/provenance, all 170 palette values,
33 properties and their groups, exact hexadecimal values, each sparse override
and clearing case, malformed inputs, identifiers, byte limits, schema/reference
versions, expressions, duplicate/Add seeds, immutable builtins, publication/global
eligibility, deleted references, tombstones/recreation, fallbacks, legacy-density
isolation, detached memory storage and rejected stale/invalid/auto-published writes.

The original Phase 1A workers were not changed. Their H5P/SNORDIAN admission,
suspension, lifecycle, translation, no-runtime-hook and no-write checks still pass.
The sole diagnostic change is informational wording acknowledging Phase 1B.

## Safety and integrity

Final SHA-256/path inventory comparison: **860 files across seven protected
targets unchanged**, including additions/deletions in the inventory. Targets are
both Backdrop source trees (excluding Git internals), the installed backdrop2
Themer, backdrop2 active configuration, the entire acceptance.example.test plugins directory,
acceptance.example.test/wp-config.php, and backdrop2/settings.php. The compatibility classes,
composition root, original test workers and license texts in this project also
remain byte-identical to Phase 1A. No production namespace/prefix collision was
found in installed plugin PHP. This is scoped integrity verification, not
whole-machine filesystem monitoring.
No WordPress database connection, CMS bootstrap, site activation, production option
write, configuration change, commit, tag, push or publication occurred in Phase 1B.
Storage writes in tests modify private in-memory arrays only. No activity selector,
profile interface, H5P asset callback, stylesheet or Density controller was added.

The project version remains 0.1.0; namespace, identifiers, compatibility requirements
and dependency/conflict services are preserved. Existing license texts are retained.

## Files created or modified

Created **16 files** (project now contains **42 files**): `includes/theme-domain.php`;
`includes/Theme/class-color-properties.php`, `class-preset-catalog.php`,
`class-theme-validator.php`, `class-palette-resolver.php`,
`class-theme-operations.php`, `class-library-changes.php`;
`includes/Publication/class-publication-policy.php`;
`includes/Storage/interface-snapshot-store.php` and `class-schema.php`;
`presets/moodle-478fed30d2a4.php`; the three files in `tests/domain/`;
`docs/theme-domain.md`; and this report.

Modified **6 files**: `papijo-h5p-themer-wordpress.php` (class-definition loader only),
`includes/Admin/class-diagnostic-notices.php` (informational wording only),
`README.md`, `readme.txt`, `THIRD-PARTY-NOTICES.md`, `docs/architecture.md`.
The historical Phase 1A record and Backdrop baseline document remain unchanged.

## Outstanding work and Phase 1C

No failing automated check is known. WordPress storage persistence, administration
forms/previews, profile preferences, runtime styling, Density and live activation
are unimplemented/untested by design. Intentional host differences and missing
policy behavior are explicit in theme-domain.md. No browser acceptance is claimed.

Proposed Phase 1C is the real atomic storage adapter and authorized administrator
theme/default/publication management, with native color controls and previews in
the agreed scope. Profile and runtime work stay separate. Live installation,
activation and production data writes require explicit authorization. Stop after
Phase 1B and obtain approval before starting any Phase 1C work.
