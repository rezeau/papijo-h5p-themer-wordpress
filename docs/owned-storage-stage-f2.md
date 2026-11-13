# Stage F2 — fresh-install production storage

Version remains **0.1.1-beta.1**. This working source replaces options-based Theme
and permission persistence with the F1 owned InnoDB backend. It implements no
migration. The verified E4 candidate, deployed installations and historical release
evidence are separate, preserved inputs; no replacement ZIP is built in F2.

## Production architecture

`Plugin::theme_store()` selects `Storage\Owned\Theme_Store`; the default
`Role_Service` selects `Storage\Owned\Role_Store`. Export/Import, administration,
Profile choices and runtime readers inherit that selection through their existing
service boundaries. Old options adapters remain for historical contract tests;
production factories do not select them. The F1 class names are compatibility
aliases to the promoted production classes, avoiding divergent storage algorithms.

The table is `<current-blog-prefix>pjh5pwp_state`:

| Column | Declaration |
| --- | --- |
| record_id | BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, primary key |
| state_key | VARBINARY(32) NOT NULL, unique full-column index |
| schema_version | INT UNSIGNED NOT NULL |
| revision | BIGINT UNSIGNED NOT NULL |
| payload | LONGBLOB NOT NULL |

Engine is InnoDB. The independently keyed records are `theme_library` and
`role_permissions`. Their existing schema versions, serialized bytes and monotonic
revisions are retained. Inserts use uniqueness, not upsert; updates match key,
identity, schema, revision and exact previous bytes. Writes use private
nonpersistent pinned sessions and are never replayed after an uncertain reply.
Permission checks hold a shared row lock across the independent Profile save;
cooperative revocation requires the conflicting exclusive lock. Theme and role
updates remain independent. No cross-table migration transaction is introduced.

The existing Profile schema, installer and persistence algorithm are unchanged.
Automatic administrator authorization additionally checks that owned storage is
available and legacy absence is verified before exposing/saving preferences.
Ordinary users retain target-aware role authorization and transaction-held rechecks.
SNORDIAN still suspends runtime styling and hides preference controls; saved bytes
are retained. No runtime JS, CSS, H5P admission or Density algorithm changes.

## Lifecycle and safety policy

1. Supported single-site activation runs the existing independent Profile setup,
   then owned-state setup. Both create only absent, verified-compatible InnoDB
   tables. They seed no Theme, permission or user preference record.
2. State setup proves absence using metadata and a missing-table probe, checks
   InnoDB availability, prohibits engine substitution, issues at most one
   `CREATE TABLE IF NOT EXISTS`, then verifies columns, indexes, visible triggers,
   engine, session continuity and existing record compatibility.
3. Repeated activation verifies existing bytes without updates or repair.
   Activation failure leaves the plugin active. Its existing callback return
   contract remains the Profile setup result; independent State diagnostics report
   State setup failures. Profile setup can succeed while State setup is blocked.
4. Ordinary requests inspect only. An update that bypasses activation shows a
   missing-storage notice. **Retry State storage setup** is an explicit
   `manage_options`, single-site, POST and nonce protected action. It cannot repair
   an incompatible table. Unknown outcomes require fresh verification.
5. Theme-library initialization remains an explicit, snapshot-bound administration
   submission. Empty permission storage denies ordinary users until an authorized
   grant; administrator access remains capability based when storage is supported.
6. Deactivation/file removal retains data. No uninstall deletion is introduced.

Before production owned-storage operations, a direct read-only query checks for
either `pjh5pwp_theme_library` or `pjh5pwp_role_permissions` in the actual WordPress
options table. **Presence alone blocks**, including empty, malformed or future
values, and even when owned state already exists. Unverifiable absence also fails
closed. Cached Options API values cannot bypass this check.

The explicit legacy policy is refusal, not migration or reset: use a separate fresh
development installation for F2. Preserve an old site's files/database and its
compatible earlier code if it still needs its old data. Do not delete legacy
options to dismiss the warning: doing so could sever custom Themes and permissions.
There is no in-plugin importer, reset, dual-write, fallback or automatic conversion.
Concurrent out-of-band legacy writes are unsupported; do not run old and new code
against one database. No guarantee is claimed for rogue database writers.

## Diagnostics

WordPress options may use MyISAM or InnoDB; the plugin never converts or alters
that table. Owned-state diagnostics identify missing storage, unsupported owned
engine, invalid schema/key/version/payload, unverifiable legacy absence, unavailable
metadata/InnoDB, failed CREATE and unknown outcomes. Raw database or exception
details are not rendered. Plugins and Themes pages show the current verified
status; URL result values cannot authorize setup or override current status.

Legacy refusal says this version does not migrate legacy state, preserves it, and
requires a separate fresh development installation. It offers no reset or retry
that suggests migration has occurred. Profile setup remains a distinct operation.
No current diagnostic instructs an administrator to convert `wp_options`.

## Automated verification

F1 adapter contract and isolated native suites remain independently runnable.
`tests/owned-storage/activation.php` exercises production lifecycle composition,
legacy presence, safe failure and the State setup permission/nonce/method boundary.
`tests/owned-storage/mysql.php --production` additionally exercises actual production
connection factories and default Profile authorization with MyISAM options, native
CAS/initialization races, role-revocation/Profile-save serialization and late legacy
refusal. Native WordPress lifecycle runs exercise real activation and admin-post
hooks on separately created MyISAM and InnoDB fresh installations, Theme CRUD,
publication/defaults, role grants/revocation, preferences, stale forms, setup
failures/recovery, incompatible owned tables and legacy refusal.

All native harnesses create random guarded disposable databases, select only those
databases, and verify cleanup. Source WordPress core and literal connection
credentials are inputs; a source site's DB_NAME is never selected. Test fixture
DDL/faults affect only disposable tables. Production performs no WordPress-table
DDL or preference/permission writes through the WordPress Options API.

Final verification: **52/52 commands PASS on each of PHP 7.4.33, 8.3.18 and
8.4.22** (156/156; zero exclusions). All 32 release-tooling tests pass. F1 isolated
memory/native coverage is 564/961 assertions; F2 lifecycle and production-native
coverage is 39/939. Native WordPress 7.1.4 / MySQL 9.1.0 lifecycle passes 100
assertions on each options engine, on every PHP version. Static checks cover 144
PHP files on all versions, 13 JavaScript files and seven Python files.
The complete command matrix is recorded in [testing](testing.md).

Preservation checks match all seven historical ZIP hashes, 11,577 historical
E2/E4 evidence files and 1,356 installed plugin/H5P/SNORDIAN/configuration files.
The version, E4 candidate metadata and frozen artifact hashes are unchanged.
The read-only public-source audit includes all **245 files** and confirms exact
identity for all **93 production inputs**. No F2 ZIP exists. No existing site
DB was selected for native writes; these preservation checks do not claim a
byte-for-byte snapshot guarantee for independently active live databases.

No automated failures remain. Real Firefox/shared-hosting acceptance and an
approved candidate rebuild/deployment remain outstanding. The deferred H5P admin
View and slow-network issues are unchanged.

## Packaging and review

The reviewed production policy grows from the frozen E4 **84** files to **93**:
`includes/owned-storage.php` and eight `includes/Storage/Owned/` classes. Foundation
aliases, disposable installers, tests and technical documents are public development
source only. They are excluded from the installable payload.

`release-candidate.json` still describes the preserved E4 artifact. Its frozen
input manifest is retained as `release-e4-inputs.json` and checked against the
candidate hash and original path-set pin. `development-inputs.json` separately pins
the actual F2 production inputs; release tests require exact equality to the
read-only current audit. No assertion is bypassed to make changed source match a
historical ZIP. A future approved candidate refresh must audit/build this source
and update release metadata together. No F2 release artifact exists yet.

## Remaining risks and Firefox acceptance proposal

Automated verification does not replace real Firefox or shared-hosting acceptance.
E4 code cannot read F2 owned state. Do not downgrade an F2 database in place;
retain compatible code/database backups together and use separate fresh fixtures.
Standard single-site wpdb/mysqli, private connections, autocommit and InnoDB for
owned tables remain required. Denied CREATE, restrictive metadata grants,
unsupported database adapters and future schemas fail closed. Minimum WordPress
6.5 is a declared target, not an exhaustive tested core-version matrix.

After separate deployment approval, use disposable Firefox acceptance sites:

1. Fresh MyISAM options: activate, verify empty InnoDB State/Profile tables and
   unchanged options engine. Repeat activation; initialize Themes explicitly.
2. Repeat on fresh InnoDB options. Verify presets and absent default role grants.
3. Create/edit/copy/publish/delete a custom Theme; select global defaults; check
   stale tabs cannot overwrite new changes and default-delete protection remains.
4. Grant subscriber access; save independent Theme/Density choices and preview;
   revoke access and confirm later saves fail while previous choices remain stored.
5. Check admitted DIV/internal-iframe playback, native fallback and lifecycle
   return without changing the unresolved slow-network/admin-View limitations.
6. Activate SNORDIAN: notice replaces selectors, submitted fields are ignored,
   playback is suspended; remove the conflict and confirm saved choices return.
7. On disposable fault fixtures only, test denied CREATE, missing storage/retry,
   incompatible owned schema/engine and legacy options; no repair/reset/migration
   and no WordPress-table conversion should occur.
8. Check color Export/Import review/confirmation and preservation across
   deactivation/reactivation. Compare protected options and Profile bytes.

No live-site deployment, database repair, Git action, publication, release rebuild,
version change or legacy migration is authorized by this stage.

## Source file inventory

The source changes below exclude private test logs, snapshots and the disposable
WordPress fixture. No source files are deleted.

Created (13):

- `docs/development-inputs.json`
- `docs/owned-storage-stage-f2.md`
- `docs/release-e4-inputs.json`
- `includes/Storage/Owned/class-legacy-guard.php`
- `includes/Storage/Owned/class-role-store.php`
- `includes/Storage/Owned/class-state-connections.php`
- `includes/Storage/Owned/class-state-installation.php`
- `includes/Storage/Owned/class-state-schema.php`
- `includes/Storage/Owned/class-state-storage-admin.php`
- `includes/Storage/Owned/class-state-table.php`
- `includes/Storage/Owned/class-theme-store.php`
- `includes/owned-storage.php`
- `tests/owned-storage/activation.php`

Modified (33):

- `CONTRIBUTING.md`
- `README.md`
- `docs/architecture.md`
- `docs/installation.md`
- `docs/public-source-allowlist.txt`
- `docs/public-source-preparation.md`
- `docs/release-tooling.md`
- `docs/release-zip-allowlist.txt`
- `docs/testing.md`
- `docs/wordpress-persistence.md`
- `foundation/owned-storage/class-role-store.php`
- `foundation/owned-storage/class-state-schema.php`
- `foundation/owned-storage/class-state-table.php`
- `foundation/owned-storage/class-theme-store.php`
- `includes/Admin/class-admin-messages.php`
- `includes/Admin/class-diagnostic-notices.php`
- `includes/Profile/class-profile-controller.php`
- `includes/Profile/class-profile-service.php`
- `includes/Role/class-role-service.php`
- `includes/class-plugin.php`
- `includes/wordpress-storage.php`
- `readme.txt`
- `tests/admin/coexistence.php`
- `tests/admin/run.php`
- `tests/owned-storage/fake-database.php`
- `tests/owned-storage/mysql.php`
- `tests/owned-storage/native-support.php`
- `tests/owned-storage/run.php`
- `tests/profile/activation-wordpress.php`
- `tests/release/test_tooling.py`
- `tests/run-all.py`
- `tests/worker.php`
- `tools/release_common.py`
