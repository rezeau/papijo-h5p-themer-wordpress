# Stage B — explicit Profile storage installation service

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Historical Stage B checkpoint. Stage C subsequently wires activation and protected
retry in source; see [the current lifecycle](profile-storage-stage-c.md).

Version remains 0.1.0. Source implementation only; no deployment or activation
wiring is part of this stage. Stage A and removal of the local runtime-enable
block have already been accepted. Runtime configuration, compatibility, Profile
authorization, Theme/Density semantics and readiness are unchanged here.

## API

`includes/profiles.php` loads an inert `Profile_Installation` definition. Neither
including it nor constructing the service opens a database connection.

```php
use Papijo\H5PThemerWordPress\Profile\Profile_Installation;
use Papijo\H5PThemerWordPress\Profile\Profile_Store;

$installation = new Profile_Installation(new Profile_Store());
$status = $installation->status();             // Read-only inspection.
$result = $installation->ensure_installed();   // Explicit trusted installer call only.
$future = $installation->upgrade(2);           // Unsupported; no connection or migration.
```

All three operations return `state`, `errors` (constant codes), and `outcome`.
No SQL, exception text, credentials, user IDs, preference values or private Theme
data are returned. This is an infrastructure service, not a new HTTP operation;
Stage C must provide the authorized activation boundary.

| State | Meaning |
| --- | --- |
| `absent` | Read-only inspection confirmed a missing table. |
| `ready` | The supported table and stored format-version markers were verified. |
| `incompatible` | Existing engine, schema or stored version is unsupported; unchanged. |
| `unavailable` | Environment, metadata or InnoDB prerequisites could not be established. |
| `failed` | A definite server rejection prevented creation. |
| `unknown` | CREATE may have taken effect, but acknowledgement or verification failed. |
| `unsupported_upgrade` | No migration is registered; upgrade does nothing. |

Read-only success and the upgrade boundary have outcome `not_attempted`.
Successful ensure has outcome `confirmed`: readiness was verified, not a claim
that this caller created the table. Definite failures use `not_committed`;
uncertain outcomes use `unknown`. An unknown result requires explicit read-only
reinspection, not automatic retries, schema repair or database restoration.

The existing explicit `Profile_Store::install()` API still returns `ready` or
throws a `Storage_Exception`. It now uses the same safe inspection/creation
implementation. Its wrong-engine diagnostic is the more precise
`profile_engine_unsupported`; normal Profile read/save diagnostics are unchanged.

## Safety and lifecycle

1. Reuse the existing private `Owned_WPDB` connection and validated WordPress
   prefix/identifiers. Multisite, nonstandard database adapters and persistent
   connections remain unsupported. Require autocommit before any possible DDL.
2. Read table metadata. An empty result alone is insufficient to authorize CREATE:
   a zero-row SELECT must specifically return missing-table error 1146.
   Inaccessible, contradictory or failed metadata causes a sanitized failure.
3. If a table exists, reuse the existing Profile schema/engine/index/trigger
   verification, then reject any `schema_version <> 1` record marker. No CREATE,
   SET, ALTER, row writes or repair run for an existing compatible/incompatible
   table. Status is installation validation, not a bulk audit/deserialization of
   every preference blob; the unchanged per-user reader still validates exact
   canonical bytes and row/blob revision agreement.
4. Only after confirmed absence, verify InnoDB availability. Add
   `NO_ENGINE_SUBSTITUTION` to this private session's SQL mode so a disappearing
   engine cannot cause fallback to a different engine. No global mode or setting
   changes. Creation still uses the original four-column InnoDB schema:

   ```sql
   CREATE TABLE IF NOT EXISTS `<prefix>pjh5pwp_profiles` (
       user_id BIGINT UNSIGNED NOT NULL,
       schema_version INT UNSIGNED NOT NULL,
       revision BIGINT UNSIGNED NOT NULL,
       state LONGBLOB NOT NULL,
       PRIMARY KEY(user_id)
   ) ENGINE=InnoDB
   ```

5. Verify the complete supported installation after CREATE and retain the same
   private connection identity throughout. Concurrent creators can both succeed
   after verification. An incompatible table appearing during the race is left
   intact; no conversion, DROP or rollback is attempted.
6. Query exceptions, lost acknowledgements, reconnects, unclassified false
   acknowledgements, failed post-creation verification, and close exceptions
   after a CREATE attempt are conservative unknown outcomes. The new opt-in
   `Owned_WPDB::query_once()` prevents WordPress from reconnecting/replaying this
   CREATE on a session that lost the prerequisite SQL mode. Its guard is scoped
   to that call and restored in `finally`; ordinary query/reconnection behavior
   is unchanged. There is one CREATE attempt at most, with no recovery write.

Creation never inserts user records. Compatible installs preserve schema, every
revision and binary state byte. Legacy usermeta remains untouched and continues
to require the existing explicit legacy review when read. Theme library/default/
publication options, roles, SNORDIAN settings and activation records are outside
the installer's write scope. No version option, table, setting, AJAX endpoint or
request field is added beyond the already supported Profile table.

Ordinary frontend/Profile reads and saves still use the original `verify`, `row`
and CAS path. They never call this installation service. `Plugin::activate()`,
boot, notices and the entry point remain unchanged; activation is read-only.

`CREATE TABLE IF NOT EXISTS` alone does not validate an existing schema, which
is why both preflight and post-creation checks are necessary. MySQL documents
that behavior in its [CREATE TABLE reference](https://dev.mysql.com/doc/refman/8.4/en/create-table.html).

## Verification

- New disposable memory/fault suite: 354 assertions on PHP 8.4.22 and PHP 7.4.33.
  Covers inert construction and upgrade, ordinary-request no-DDL guarantees,
  fresh/repeated/compatible installs, unsupported engine/schema/versions,
  unavailable/hidden metadata, missing CREATE permission, engine prerequisites,
  autocommit, concurrent table appearance, sanitized diagnostics, close failure
  and uncertain operation outcomes without retry/destructive cleanup.
- New real MySQL 9.1.0 suite: 133 assertions on each PHP version, including three
  synchronized two-process installer races per run. Uses real WordPress wpdb
  definitions and a nonstandard `course_27_` prefix, exact saved bytes/revisions,
  existing CAS/revision checks, actual read-only-session DDL rejection, real
  disconnection of the installer's own disposable session, proof of no CREATE
  replay and unchanged ordinary reconnection, preserved legacy/unrelated data
  and verified fixture database removal.
- Missing CREATE privileges, disabled InnoDB, metadata failures and lost replies
  use connection-scoped fault injection. No server account, grant, engine or
  global configuration changes are made to manufacture those conditions.
- Existing real Profile persistence suite: 56 assertions and five writer races.
  Existing role suite: 69 assertions, seven administrator races and serialized
  permission revocation. Both use disposable databases and verify their removal.
  Existing Theme-storage MySQL suite also passed its 49 assertions and six
  two-process writer races plus initialization race after the shared adapter's
  opt-in guard was added; its fixture database was removed.
- 23 PHP regression suites and eight JavaScript suites passed, covering bootstrap,
  domain/inheritance, storage, administration, roles, Profile preview/suspension,
  color transfer, runtime admission/configuration, Theme/Density and readiness.
  Six selected PHP suites additionally passed on PHP 7.4.33.
- No live WordPress bootstrap, acceptance.example.test database selection or deployment occurred.
  Installed plugin/source scope preservation is checked by file hashes. The
  separate previously documented legacy admin headless form-count assertion was
  not rerun; it is not claimed as resolved by this storage stage.

Run the new suites explicitly:

```powershell
php -n tests/profile/installation.php
php tests/profile/installation-mysql.php 'WORDPRESS_ROOT' 'WORDPRESS_ROOT/wp-config.php'
```

The MySQL harness parses only literal server host/user/password credentials,
never executes wp-config or uses its DB_NAME, and selects only validated random
`pjh5pwp_install_test_<16 hex>` fixtures. It requires server permission to create
and remove those disposable databases. Worker credentials travel over stdin.

Initial broad-test command mistakes (included-only admin subtests and omitted H5P
source arguments) were corrected; the final suite logs contain successful runs
through their proper harnesses. No production failure was found in those cases.

## Stage C remaining work

- Separately approve native activation wiring and its failure policy. Invoke the
  service only at that explicit boundary; do not add DDL retries to frontend,
  Profile, notice or ordinary administrator page loads.
- Map constant diagnostics to sanitized administrator feedback, including
  inability to create, unsupported storage and uncertain outcomes. Do not erase
  saved data, grant database permissions, change engines, or deactivate SNORDIAN.
- Keep Profile installation independent of runtime admission/SNORDIAN suspension.
  Deactivation/reactivation must preserve records. No migrations are implemented;
  future schema changes need their own reviewed migration/rollback design.
- Test native fresh activation, repeat activation, activation failures and
  deactivation/reactivation in disposable WordPress installations before any
  separately approved deployment. Extend server coverage beyond the tested
  MySQL 9.1.0 baseline (notably supported online MySQL/MariaDB environments).

No further approval is needed to review this source implementation. Activation
wiring, any real-site installation, deployment and release remain separate work.
