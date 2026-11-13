# Stage F1 — isolated plugin-owned InnoDB foundation

12 November 2026. This development-only foundation is explicitly loaded from
`foundation/owned-storage/bootstrap.php`. Production loaders, factories, hooks,
Profile storage, runtime behavior, version and the verified E4 candidate remain
unchanged. No migration, backend selection, production installer, conversion or
cutover is implemented.

## Proposed schema

The table name is derived from the site prefix as `<prefix>pjh5pwp_state`.
`State_Schema::table_name()` validates the prefix/name and MySQL's 64-byte limit.
The native fixture uses a nonstandard `course_27_` prefix.
Validation permits the WordPress prefix alphabet without imposing a trailing
underscore; ordinary prefix concatenation also works with a bare or empty prefix.

```sql
CREATE TABLE IF NOT EXISTS <prefix>pjh5pwp_state (
    record_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    state_key VARBINARY(32) NOT NULL,
    schema_version INT UNSIGNED NOT NULL,
    revision BIGINT UNSIGNED NOT NULL,
    payload LONGBLOB NOT NULL,
    PRIMARY KEY (record_id),
    UNIQUE KEY state_key (state_key)
) ENGINE=InnoDB;
```

F1 supports only the binary keys `theme_library` and `role_permissions`. Creation
leaves both absent; a read never initializes either record. Payloads preserve the
existing canonical schema-1 PHP serialization, including Unicode, hex case, sparse
Theme overrides and legacy metadata. Envelope schema/revision columns must agree
exactly with the serialized values. Unknown versions and malformed payloads fail
closed; F1 does not convert them. The existing 4 MiB codec limit applies.

The auto-increment record ID remains stable during supported updates. Every UPDATE
matches key, record ID, schema version, revision and exact old payload bytes. This
rejects a same-revision byte change or deletion/recreation between the internal read
and SQL write. Initializers use a plain INSERT and the unique state-key index; no
upsert can replace a competing initializer. Supported operations never delete rows
or reset revisions. Direct SQL, manual identity/revision resets and noncooperative
writers remain outside the existing cooperative-writer contract.

## Adapter architecture and lifecycle

- `State_Schema` defines and verifies the exact columns, primary/unique indexes,
  binary payload/key types, InnoDB engine, no visible triggers and autocommit.
- `State_Table` owns the shared read/CAS/permission-lock implementation. Its
  mandatory connection factory supplies a new private owned session per operation.
  It does not choose a WordPress database or backend itself. Factory injection is
  trusted composition, never an HTTP/table-name input boundary.
- `Theme_Store` implements the existing `Snapshot_Store` interface and enforces
  the unchanged Theme schema/transition rules. It requires explicit injection.
- The new `Role_Store` implements the existing `Role_Service` collaborator methods
  (`read`, `compare_and_swap`, `with_locked_snapshot`). Production's Role_Store
  class/default selection is unchanged.
- `Disposable_Installation` is a test-environment creation boundary. Before any
  DDL it verifies `SELECT DATABASE()` matches `pjh5pwp_f1_<16 lowercase hex>`.
  It verifies confirmed absence, engine availability, private-session continuity
  and `NO_ENGINE_SUBSTITUTION`, issues one non-replayed CREATE, then verifies the
  schema. Existing incompatible tables are preserved without repair. `status()`
  describes table structure/availability, not a full scan certifying every record.

Reads bypass WordPress Options API caches. Writes affect only the owned state
table, so no WordPress options-cache invalidation is needed. No core options,
SNORDIAN values, legacy metadata or Profile rows are read by the state adapter.
The separate existing Profile adapter continues its own bounded legacy checks.

State reads/metadata verification pin the private session identity. CAS and CREATE
use `Owned_WPDB::query_once()` to suppress automatic reconnect/replay. Known SQL
rejection reports `not_committed`; lost acknowledgement/connection continuity is
`unknown` and requires read-only reconciliation. No blind retry, restoration or
automatic engine/schema repair occurs. Cleanup does not mask an acknowledged
Profile callback or state write.

## Permission locking

For non-administrator preference saves, the existing Role_Service is injected with
the new role adapter in the native test. A private transaction takes
`LOCK IN SHARE MODE` on the owned permission record, rechecks authorization and
holds the lock while the unchanged Profile_Service/Profile_Store CAS finishes on
its own connection. A cooperative revocation UPDATE requires an exclusive lock
and waits. If revocation won first, the next save is rejected without Profile SQL.
The separate Theme record remains writable while the role record is locked.

This preserves the production contract's scope: a held live-session lock and
cooperative Papi Jo role writers. It does not create an atomic transaction across
WordPress core capabilities/user membership and Profile storage, or claim new
guarantees for a role-lock session lost during an independent Profile commit.
The administrator capability bypass and default-denial semantics are unchanged.

## Isolated versus production verification

The two additional F1 entry points are deliberately separate from the existing
47-command release inventory:

```text
php -n tests/owned-storage/run.php
php tests/owned-storage/mysql.php <WordPress-core-source-root> <literal-server-credentials-file>
```

The contract suite uses memory/fault doubles; it does not establish SQL locking.
The native suite loads only real WordPress wpdb plus host API doubles and selects
only a new random `pjh5pwp_f1_*` database. It ignores the credential file's DB_NAME
and never executes wp-config or bootstraps a site. Workers use independent PHP
processes synchronized after internal reads and immediately before actual SQL.
Native tests verify:

- MyISAM WordPress-shaped options, InnoDB state, and an existing InnoDB Profile
  table coexist. The original production adapter still rejects MyISAM; new state
  remains absent despite existing legacy options, proving no replacement/migration.
- Eight two-process INSERT/UPDATE races and two concurrent CREATE races, monotonic
  revisions, stable identity, exact byte roundtrip, stale/ABA rejection and fresh
  process reload. Real read-only SQL failure leaves state unchanged. Both state
  adapters commit independently of an ambient WordPress connection transaction,
  without committing its pending unrelated Profile change.
- Real connection termination before writes/CREATE prevents replay. A transport
  fixture detects connection loss after a real committed write but before the
  adapter accepts its acknowledgement; reconciliation retains the complete commit.
  This is not a claim of exhaustive real network/packet-loss qualification.
- A real lock timeout proves exclusive revocation is blocked by the shared grant
  lock. The actual Profile_Service completes its authorized save; revocation then
  succeeds; the next save is denied without any Profile write.
- Core options/usermeta schema, engine and every byte, the existing Profile schema,
  and an unrelated Profile record remain unchanged. Only deliberately seeded
  disposable fixtures perform fault DDL on owned state tables.

All F1 suites and the unchanged production inventory are run across PHP 7.4.33,
8.3.18 and 8.4.22. The private F1 report distinguishes their results and records
cleanup/preservation hashes. Production Theme/Density/runtime, Profile and SNORDIAN
tests still exercise the existing production wiring; they do not certify a future
cutover. The local native server is MySQL 9.1.0, not a broad hosting/MariaDB matrix.

The foundation, tests and this record are in the complete public development policy
but outside the unchanged 84-file ZIP policy. No installer or public-source release
artifact is rebuilt in F1; the frozen E4 files remain historical candidate evidence.

## Stage F2 requirements and remaining risks

F2 must separately design and approve the migration/cutover protocol. It needs a
read-only legacy option reader that validates MyISAM source snapshots without
weakening runtime storage checks; a backup and writer-quiescence strategy across
old requests, workers and deployment nodes; and atomic destination data/completion
metadata with idempotent recovery after unknown outcomes. F1 intentionally exposes
no migration importer or completion-marker writer.

Preserve Theme/default/publication and role-map bytes/revisions, Profile table/data,
default denial and the existing authorization boundaries. Select one authoritative
backend after cutover: no fallback to potentially stale grants. Retain old options
for recovery evidence without dual writes; define downgrade/reverse-migration
restrictions and stale form/import-review behavior. Define production installation,
update-without-activation, backup/restore, table-prefix and deactivation/uninstall
lifecycle behavior without silently creating preferences or converting core tables.

Before production use, qualify additional MySQL/MariaDB environments, privileges,
metadata visibility, schema/engine drift, disconnect/timeout recovery and long-lived
lock behavior. Full automation plus manual acceptance and refreshed release artifacts
are later gates. Multisite, database drop-ins, runtime View enhancements and the
existing slow-network limitation are not expanded or addressed by F1.
