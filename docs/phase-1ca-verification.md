# Phase 1C-A verification — 7 November 2026

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

## Review and implementation scope

Read architecture.md, theme-domain.md and both prior verification reports before
implementation. Reviewed the snapshot interface, schema/transition validator,
in-memory store and pure change plans. The WordPress-specific requirements found
were cached/filtered Options API reads, non-CAS updates, initialization upserts,
possible ambient transactions, serialization/charset handling, connection retries,
and separation of committed/unknown outcomes. These findings were reported before
implementation. Domain schemas, rules, presets and compatibility policy remain intact.

The authoritative Backdrop HEAD is still v0.5.0 commit
`d9b53d706a94dac322b89885bf668145ab3803c4`. All source inspection is read-only.

## Final automated results

| PHP | Phase 1A | Phase 1B | Persistence doubles | Disposable MySQL | Syntax |
| --- | --- | --- | --- | --- | --- |
| 7.4.33 | 18 scenarios / 405 PASS | 2,315 PASS | 204 PASS | 49 PASS | 44 files PASS |
| 8.3.18 | 18 scenarios / 405 PASS | 2,315 PASS | 204 PASS | 49 PASS | 44 files PASS |
| 8.4.22 | 18 scenarios / 405 PASS | 2,315 PASS | 204 PASS | 49 PASS | 44 files PASS |

Counts are assertions unless marked scenarios/files. The 919 Backdrop parity
assertions are included in the 2,315 Phase 1B count. The original regression
workers and domain tests were not modified. A PHP 8.4 nullable-parameter
deprecation found during development was corrected before these final passes.

The disposable server reports **MySQL 9.1.0**. Tests load the actual installed
WordPress `wpdb` class with minimal host-function doubles, not WordPress startup.
Each qualifying PHP run performs six two-process writer races plus a concurrent
initialization race. A barrier pauses both sessions inside the adapter immediately
before their real INSERT/conditional UPDATE, after both have read and validated
the old state; both SQL writes are attempted. Exactly one writer wins, revision advances once,
winner state and custom data survive. Tests also verify exact Unicode/native
serialization, publication/default changes, deletion protection, failed SQL,
independence from an ambient global transaction, private-filter cleanup and
nonfatal missing-database handling.

Fault doubles cover races between read and write, same-revision byte changes,
physical row replacement, initialization collisions, stale revisions, malformed
storage, unsupported backends, known write failures, lost acknowledgements,
post-commit cache failures, and unrelated/SNORDIAN fixture-option preservation.
Synthetic migrations test explicit conversion, failed conversion/write,
preservation of definitions/publication and idempotence. No synthetic v0 step
ships in the production registry.

Commands (select the corresponding PHP binary for each version):

```powershell
php -n tests/run.php 'WORDPRESS_ROOT/wp-content/plugins/h5p'
php -n tests/domain/run.php 'BACKDROP_REFERENCE_ROOT'
php -n tests/storage/run.php
php tests/storage/mysql.php 'WORDPRESS_ROOT' 'WORDPRESS_ROOT/wp-config.php'
```

The MySQL test is explicitly opt-in. It reads only DB_HOST/DB_USER/DB_PASSWORD
literals from the supplied credential source without executing it or reading its
DB_NAME as a target. The first connection selects no database; it creates/selects
a random `pjh5pwp_test_` plus 16-hex-digit database. Every child verifies that
name. The finalizer verifies it again before dropping only that database. All
test databases were removed. No test selected or modified the acceptance.example.test database.

## Safety and protected files

Final SHA-256/path inventory comparison: **860 protected files across seven targets
unchanged**, including additions/deletions in those inventories. Targets are both
Backdrop repositories (excluding Git internals), backdrop2's installed Themer and
active configuration, the whole acceptance.example.test plugins directory, acceptance.example.test/wp-config.php,
and backdrop2/settings.php. Domain implementations/presets, compatibility services,
prior test workers and license texts in this project also remain byte-identical to
Phase 1B. No namespace/prefix collision was found in installed plugin PHP. This
is scoped verification, not whole-machine or independent live-database monitoring.
No WordPress activation, live installation/migration, administration form, runtime
CSS/Density injection, activity selector, commit, tag, push or publication occurred.
Only disposable test databases were written. No acceptance.example.test or Backdrop bootstrap ran.
The official H5P plugin, SNORDIAN and Backdrop were not modified. SNORDIAN options
were never used as storage; only test-owned foreign-option fixtures were examined
by the test harness. Production reads/writes always target the fixed aggregate key.

Native cache invalidation is limited to the aggregate/WordPress options-cache
entries; it does not update foreign option rows. Private `wpdb` placeholder-filter
cleanup removes only callbacks owned by that private connection. Phase 1A's
runtime suspension behavior remains covered by unchanged regressions.

## Files created or modified

Created **16 files**; modified **8 files**; project now contains **58 files**.

New production files: `includes/wordpress-storage.php`;
`includes/Storage/class-storage-exception.php`, `class-snapshot-codec.php`,
`interface-migration-step.php`, `class-schema-migrations.php`,
`class-owned-wpdb.php`, `class-wordpress-connections.php`,
`class-wordpress-snapshot-store.php`, `class-storage-installation.php`.

New tests: all five files in `tests/storage/`: fake-database.php, legacy-step.php,
run.php, wordpress-doubles.php, mysql.php. New documentation: this report and
wordpress-persistence.md.

Modified: main plugin file (definition loader), includes/class-plugin.php (lazy
services), includes/Admin/class-diagnostic-notices.php (read-only status messages),
includes/Storage/interface-snapshot-store.php (document failure outcomes),
README.md, readme.txt, docs/architecture.md, docs/theme-domain.md.

## Limits and Phase 1C-B

See wordpress-persistence.md for the exact backend/CAS/cache guarantees. Atomicity
is qualified on the standard InnoDB options table with cooperative writers, not
arbitrary drop-ins, rewritten SQL, manual revision resets or schema changes.
Uncertain acknowledgements remain explicitly uncertain. There is no destructive
automatic recovery or real historical upgrade conversion to invent for version 1.

Live WordPress activation/admin rendering and authorization/form handlers are not
tested or implemented here. Suggested Phase 1C-B: administrator theme/default/
publication forms, capabilities/nonces, revision-aware submissions, native color
inputs/collapsible groups and agreed previews. Keep profile preferences, activity
selectors, runtime styling and Density controllers outside that scope. Live
activation/data writes require explicit site authorization. Stop after 1C-A.
