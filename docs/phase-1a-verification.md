# Phase 1A verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Verification is restricted to source inspection, syntax checks, isolated CLI
workers and source-file integrity comparison. No WordPress bootstrap, live plugin
activation, database write, configuration mutation or deployment is authorized.

## Automated test coverage

`tests/run.php` isolates each scenario in a new PHP process. Scenarios cover:

- Missing/inactive H5P and active but unloaded integration.
- Supported integration, unsupported and malformed Core API.
- Missing public methods, absent action emitters, string/comment decoys and wrong
  reference parameters.
- Foreign class origins and autoloader exceptions.
- Installed inactive SNORDIAN, active records, constant, namespace and priority-zero
  callback signals; callbacks are preserved and never invoked.
- Fresh admission after a conflict appears or disappears.
- Deferred multisite and network activation records.
- Idempotent initialization, read-only activation handling, translation timing,
  administrator-only diagnostics, plugin identity, and no runtime/profile hooks.
- Optional inspection of the actual installed H5P definitions, without running
  the plugin bootstrap or service constructors.

Fixtures contain only test-owned method shapes and identity markers, not copied
H5P or SNORDIAN implementations. The fixture methods throw if service work is
attempted. The actual source probe is not a live WordPress integration test.

## Results

Verified on 6 November 2026:

| Check | Result |
| --- | --- |
| PHP 7.4.33 CLI suite, including installed H5P source probe | 18 scenarios, 405 assertions, zero failures |
| PHP 8.3.18 CLI suite, including installed H5P source probe | 18 scenarios, 405 assertions, zero failures |
| PHP 8.4.22 CLI suite, including installed H5P source probe | 18 scenarios, 405 assertions, zero failures |
| PHP syntax on each of those versions | All 16 PHP files pass, including fixtures |
| Bootstrap header/identity and version consistency | PASS in isolated checks |
| Production namespace/constant/procedural-prefix collisions in installed plugin PHP | None found |
| Full license texts against the authoritative Backdrop reference | All three byte-identical |
| Protected-file snapshots | 860 files across seven protected targets unchanged |

Protected targets are the v0.5.0 and original Backdrop source trees (excluding
Git internals), backdrop2's installed Themer and active configuration directory,
the entire acceptance.example.test plugins directory, acceptance.example.test/wp-config.php, and
backdrop2/settings.php. Deterministic inventories include relative paths and
SHA-256 content hashes, so additions/deletions as well as byte changes are checked.
This is a scoped integrity comparison, not whole-machine filesystem monitoring.

The initial restricted-token CLI run could not pass the installed-file check:
Windows reported `is_readable()` false for an existing fixture. The same read-only
suite passed under the approved normal token. No production workaround weakens
the conservative file-readability check.

The Phase 1A code and tests never open a database connection, load WordPress,
change activation records, deploy to a CMS, or perform commit/tag/push operations.
No settings or user metadata are initialized. Actual future H5P feature adapters
remain unregistered, including on the supported/no-conflict path.

## Files created (26)

- Entry point: `papijo-h5p-themer-wordpress.php`.
- Production classes: `includes/class-plugin.php`,
  `includes/Admin/class-diagnostic-notices.php`, and the five compatibility files
  `class-wordpress-environment.php`, `class-hook-inspector.php`,
  `class-h5p-dependency.php`, `class-snordian-compatibility.php`,
  `class-runtime-policy.php` in `includes/Compatibility/`.
- Documentation: `README.md`, `readme.txt`, `docs/baseline.md`,
  `docs/architecture.md`, this verification record, and `languages/README.md`.
- Licensing: `LICENSE`, `THIRD-PARTY-NOTICES.md`,
  `licenses/GPL-2.0.txt`, `licenses/MIT-H5P-THEME-PICKER.txt`.
- Tests: `tests/run.php`, `tests/worker.php`, `tests/fixtures/foreign-classes.php`,
  `tests/fixtures/snordian-marker.php`,
  `tests/fixtures/plugins/h5p/h5p.php`,
  `tests/fixtures/plugins/h5p/public/class-h5p-plugin.php`,
  `tests/fixtures/plugins/h5p/h5p-php-library/h5p.classes.php`, and
  `tests/fixtures/plugins/snordians-h5p-themer/snordians-h5p-themer.php`.

Live installation, WordPress-admin display and actual activation remain untested
until separately approved. No WordPress Plugin Check installation is present in
acceptance.example.test; the foundation does not install one.
