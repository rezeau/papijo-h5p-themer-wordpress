# WordPress persistence — Phase 1C-A

> Historical options-based persistence contract. Current F2 production factories
> select the [owned-state backend](owned-storage-stage-f2.md). The old adapters
> remain for regression tests; their InnoDB options checks no longer govern fresh
> production installations.

## Reviewed WordPress behavior

The local reference is WordPress 7.1.4. Inspected source: `wp-includes/option.php`,
`wp-includes/class-wpdb.php` and the options-table declaration in
`wp-admin/includes/schema.php`. No source file was changed.

`update_option()` reads an old value but updates by option name alone, without an
old-value predicate. It also uses caches/filters, and false can mean unchanged
data. `add_option()` checks existence before an upsert, so its later SQL is not an
insert-only concurrent-initialization guarantee. These conclusions come from the
installed implementations and the official [update_option reference](https://developer.wordpress.org/reference/functions/update_option/)
and [add_option reference](https://developer.wordpress.org/reference/functions/add_option/).

Neither API is used for storage writes. The adapter uses WordPress's `wpdb`
connection/query/prepare APIs with a standard WordPress options table. All
aggregate reads and writes match the one fixed key `pjh5pwp_theme_library`; it never
uses SNORDIAN options as storage. Native PHP array serialization preserves the
existing domain schema and sparse/legacy metadata.

## Architecture and usage boundary

`Plugin::instance()->theme_store()` supplies a lazy `WordPress_Snapshot_Store`.
`storage_installation()` supplies its explicit lifecycle service. Constructors,
activation and module inclusion do not initialize data. There is no write hook,
AJAX/REST endpoint, form handler or production migration execution in this checkpoint.

The storage service is not an authorization boundary. Future callers must check
capabilities, nonce and the displayed revision before applying an authorized
domain change plan. Do not expose arbitrary snapshots or migration registrations
to HTTP input. Raw reads are for controlled services/diagnostics, not public output.

The pure `includes/theme-domain.php` still requires no WordPress functions.
`includes/wordpress-storage.php` loads adapter definitions only. `Owned_WPDB` is
loaded lazily when an actual storage operation needs the already-loaded WordPress
database class. No CMS bootstrap or configuration-file loading occurs in production.

## Atomicity and concurrency strategy

Settings, definitions, publication and revision are one serialized option value.
There are no separate partial publication/definition writes.

1. Open a private, nonpersistent WordPress database session for the operation.
   A new session owns its autocommit context; it cannot commit or roll back a
   transaction another plugin opened on the global `$wpdb` connection.
2. Verify the table is InnoDB, its value column is utf8mb4 LONGTEXT, its primary
   option ID is auto-incremented, its full option-name index is unique, no table
   trigger is visible, and the private session uses autocommit. Unsupported
   structure is diagnosed without altering any table.
3. Read the option row directly, retaining its physical `option_id` and exact
   serialized bytes. Check the expected revision and delegate the complete
   transition to `Schema::transition()` (or an approved explicit migration).
4. For initialization, issue a plain INSERT. A unique-key collision cannot replace
   another initializer's data. For updates, issue a single UPDATE matching the
   option name, physical row ID and **BINARY exact old value**. A revision check
   without that SQL predicate would not prevent a read/write race.
5. Transfer serialized bytes using bound hexadecimal data and `UNHEX()` to avoid
   connection-character-set changes to serialized string lengths. The codec
   preserves exact hex case, Unicode, references and legacy-density metadata.
6. One affected row acknowledges success. Zero affected rows with the same session
   means a rejected/stale write. Initialization's duplicate-key error is likewise
   a clean rejection. Return false only for known rejection without this operation
   applying a write.

This single-statement InnoDB strategy relies on server locking and the unique
index, not PHP process locks, callback priorities, transients or the object cache.
The relevant MySQL behavior is documented under [InnoDB locking](https://dev.mysql.com/doc/refman/8.4/en/innodb-locks-set.html).
Qualification used real WordPress `wpdb` and MySQL 9.1.0 with independent PHP
processes. Both processes were synchronized at the SQL-write boundary, after their
internal reads, to exercise the real conditional predicate/unique-key collision.
This does not certify every database/drop-in/plugin combination. Success
acknowledges this write, not an absence of later writers; reread before editing.

The row ID also rejects deletion/recreation between reading and writing, even if
someone restores identical bytes/revision. The supported service never deletes
the aggregate row or resets its revision. External direct option writes, manual
revision rollback, schema changes and query-rewriting plugins are outside the
cooperative-writer guarantee.

## Cache consistency and Options API isolation

Authoritative reads bypass `get_option()` and its object caches/filters. Other
code must use this adapter for this private option; mixing generic Options API
readers/writers can reintroduce stale data or bypass domain transitions.

After a committed write, invalidate the aggregate's normal `options` cache entry
and the WordPress `alloptions`/`notoptions` entries. These are native cache keys,
not extra storage options; eviction does not modify unrelated option rows. Never
populate a potentially stale shared cache after a write. Cross-request cache
eviction cannot guarantee linearizable generic `get_option()` reads; adapter
reads remain authoritative because they query the database directly.

WordPress's `wpdb::prepare()` installs a placeholder-removal filter for its own
connection. Private sessions remove only their own such filter when closing.
SNORDIAN/foreign callbacks are never removed or invoked by this cleanup.

## Failure semantics and recovery

`Storage_Exception` carries a constant diagnostic code and an outcome:

| Outcome | Meaning and required handling |
| --- | --- |
| `not_committed` | Read/preflight/conversion failed, or SQL failed on the same session without applying a row change. Preserve current data. |
| `committed` | The write was acknowledged but a subsequent cache callback failed. Do not repeat the old plan. |
| `unknown` | A write exception, reconnect/retry or unrecognized write result prevents a reliable outcome claim. Reload and inspect the authoritative revision/state. |

The installed `wpdb::query()` can reconnect and repeat a query after a lost
connection. A repeated CAS may then affect zero rows even though the first attempt
committed. The adapter compares connection thread IDs before treating rejection
as certain. Unknown outcomes are exceptions, not false/no-write results. Cache
eviction is attempted after an uncertain outcome too. No automatic overwrite,
blind retry, rollback to an old snapshot or destructive recovery follows.

There is no multi-statement transaction to partially restore: a supported write
is one aggregate-row statement. An acknowledged failure preserves the old row;
a lost acknowledgement may leave the new row and must be diagnosed honestly.
Database connection/selection failures produce storage diagnostics rather than a
WordPress fatal error page. Constant messages do not expose SQL or credentials.

## Installation and schema upgrades

The domain envelope remains **version 1**; definitions remain schema 1/2, global
references use schema 2 and publication uses schema 1. No data schema was changed
just to manufacture a migration.

- `status()` is read-only and reports absent/current/malformed/unsupported/newer
  state. The Plugins-screen notice uses this path, never an installation method.
- `initialize()` is explicit. It inserts `Schema::initial()` only into an absent
  row. Repeated calls return the existing validated snapshot unchanged. A
  concurrent initializer can win; the loser rereads rather than overwriting.
- `upgrade()` is explicit, versioned and revision-conditional. It is a no-op for
  valid current data. Newer, unknown or malformed data is preserved and blocked.
- The production migration registry is currently **empty**: no persisted release
  predates version 1. There is no guessed v0 conversion. Future shipped steps
  implement `Migration_Step`, validate their complete source schema, convert purely
  one version at a time, preserve revision until the final increment, and validate
  the final domain snapshot. The adapter accepts only that exact prepared plan.
- Inject the same trusted registry into the store and installation service when
  adding future migrations. Each real step requires preservation tests and review;
  the framework cannot certify an arbitrary unreviewed converter's semantics.

The test-only v0-to-v1 step proves upgrade mechanics and preservation but represents
no historical production schema. Unknown-schema handling is the default behavior
in this release. There are no migrations on activation, normal reads or notices.

## Supported backend and limits

Single-site standard MySQL-backed `wpdb`, InnoDB, utf8mb4, unique option names and
auto-increment primary IDs are required. Multisite, database drop-ins/routing,
persistent connection hosts and visible options-table triggers fail closed.
Metadata access must be available; all tests use a standard table without DDL
changes during a write. No MariaDB or alternate-host qualification is claimed.

The serialized aggregate is capped at **4 MiB** and uses `autoload='off'`. Packet
limits still apply because hex transport expands query size. Oversize/malformed
values fail without truncation or reset. Native unserialization disables classes,
rejects scalars/trailing/noncanonical serialized data, and never treats stored
serialized null as an absent row. Domain validation remains the authority for
theme rules; storage does not duplicate those rules.

If a site's shared options table cannot meet these requirements, the proposed
alternative is a separately authorized plugin-owned InnoDB table with an explicit
revision column and the same aggregate/CAS contract. No table conversion or
alternative schema is created automatically. Live installation, backend migration
and deployment remain unperformed pending approval.

## Phase 2A-R role configuration and profile authorization

`Role_Store` uses the same private-session, InnoDB options-table validation,
unique INSERT, physical row ID, exact-byte UPDATE, monotonic revision, hex transport
and cache invalidation pattern as Phase 1C-A. Its fixed option is
`pjh5pwp_role_permissions`; it never writes the theme-library option. The schema-1
role envelope contains `schema_version`, `revision` and a sorted sparse true-grant
map keyed by stable role slugs. No DDL, option-table alteration or data migration
is performed. An explicit authorized Save persists a changed map; default-denial
views/no-ops do not initialize it. Unknown or malformed state is never repaired.

Role_Service binds each admin nonce to the complete current envelope and current
role-slug/automatic-capability registry. It validates the complete submitted set,
rejects unknown/missing/noncanonical fields, and invokes CAS. A concurrent winner
invalidates the stale submission. Deleted-role grants are ignored by authorization
and pruned at the next explicit changed-map save. New slugs default denied.

For non-administrator preference commits, `with_locked_snapshot()` starts a short
private-session read transaction, takes `LOCK IN SHARE MODE` on the fixed role row,
and checks the current grant before running the existing independent Profile_Store
CAS. It rolls back the read-only transaction and closes the lock session afterward.
A cooperative role update needs an exclusive lock and cannot pass this shared lock
until the profile CAS finishes. A revocation that won first is observed by the
locking read and rejects the profile write. No WordPress core fields or ambient
transaction are included. No role data is changed by the locking transaction.
Read/lock failures block the preference write. The existing profile statement's
committed/conflict/unknown classifications remain unchanged; cleanup cannot mask
an acknowledged profile commit. Administrator access does not depend on this row.

The profile table remains structurally and byte-format compatible with Phase 2A-H.
Historical allow_theme/allow_density values remain inert; only theme and Density
have HTTP write paths. No existing profile row is initialized, rewritten or scanned
on deployment/GET. See [Phase 2A-R verification](phase-2ar-verification.md) for the
real locking tests and boundaries (including external role/user writers and actual
network loss).
