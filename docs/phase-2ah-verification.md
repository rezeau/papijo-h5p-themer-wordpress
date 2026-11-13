# Phase 2A-H — dedicated-table implementation and verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

**Current status: approved source implementation COMPLETE; isolated and disposable
MySQL verification PASS. Deployment/activation/live DDL NOT PERFORMED.** The user
subsequently approved the dedicated-table design below. The earlier investigation
is preserved at the end as historical context, not current implementation status.

## Final implemented schema and lifecycle

`Profile_Store` owns `{$wpdb->prefix}pjh5pwp_profiles` (normally
`wp_pjh5pwp_profiles`). Explicit `install()` creates it with **ENGINE=InnoDB**:

| Column | Definition |
| --- | --- |
| user_id | BIGINT UNSIGNED NOT NULL PRIMARY KEY |
| schema_version | INT UNSIGNED NOT NULL |
| revision | BIGINT UNSIGNED NOT NULL |
| state | LONGBLOB NOT NULL |

Schema version 1 stores canonical native serialized PHP bytes containing
`schema_version`, `revision`, and `values`. Values are four separate fields:
allow_theme/allow_density strings 0 or 1, a stable theme token, and Density
original/small/medium/large. Defaults deny both permissions, follow global and
use original Density. Revision starts at zero on the first actual change and
increments once per successful subsequent mutation; no-op does not increment.
The SQL revision/version and serialized envelope must agree. Canonical field order,
strict schema, integer bounds, known values, a 4096-byte cap and unserialization
with classes disabled reject malformed/newer state. There is no destructive repair.

`install()` is an explicit trusted-operator PHP API, not an HTTP endpoint. It has
no activation/GET/save wiring and does not create any user record. It is idempotent
and verifies the resulting engine, four column types/nullability, primary key,
absence of extra indexes/triggers and session autocommit. Existing incompatible
tables are rejected, never converted/rebuilt. Version 1 is the only supported
format; future layout changes need a separately audited installer/migration.
Structural readiness does not silently migrate unknown records.

Production reads and writes use a private `Owned_WPDB` session, fixed validated
table names and parameterized SQL. They require standard single-site wpdb and
nonpersistent connections; unsupported tables or unavailable storage fail closed.
Profile rendering shows a clear unavailable/install/review message and no saveable
plugin fields. Effective resolution denies grants safely, follows global/Core and
uses original Density if profile storage is unavailable.

Old Phase 2A's four usermeta keys are **not a fallback store**. If any exists for
the target, reads fail with `profile_legacy_review_required`. No importer or metadata
deletion was invented for the undeployed prototype. An external installation with
those keys needs explicit audited review, not silently ignored or merged state.

## Atomicity and concurrency

First writes use plain INSERT, with the user primary key enforcing uniqueness.
The preliminary read detects conflicts/legacy state; it is not what guarantees
initialization safety. Two simultaneous absent-state inserts cannot both succeed.
Duplicate error 1062 is a conflict; other SQL failures are database errors.

Existing updates are one InnoDB autocommit statement replacing the complete state,
matching user ID, schema version, expected revision and byte-exact expected state.
No unconditional read-modify-write update exists. Both permission and preference
saves use the same aggregate/CAS: unrelated fields are retained from the expected
record and any intervening change invalidates the whole submission. No automatic
rebase, retry, merge or compensating write occurs.

Results distinguish committed, conflict, validation_failed and noop. Exceptions
separate database/environment/state failures from an unknown acknowledgement.
An explicit failed SQL statement leaves the previous aggregate unchanged. A lost
acknowledgement/session change can mean the **whole** aggregate committed; it never
means some fields were accepted independently. The adapter checks connection identity
around SQL and reports unknown instead of claiming rollback or retrying blindly.
It does not promise crash durability beyond the actual InnoDB/server configuration.
No-op verifies authoritative expected state and returns without SQL mutation; its
linearization point is that read. It does not create an absent default record.

## Form and authorization integration

`Profile_Service` now accepts a state adapter; its default is the new production
store. Rendering captures one record for both displayed fields and nonce witness.
The nonce includes the revision-bearing record, so changing values back to old
values does not revive an old form. Failed-form redisplay retains the original
nonce. Deferred save replans against a fresh record, then performs CAS against that
same snapshot; a change after validation still loses at SQL.

Capabilities, own-account preference admission, administrator-only grants, strict
payload validation, independent controls, publication/custom fallback, explicit
Core/global distinction and form escaping remain. Only present authorized fields
enter the next state. Revocation is used on subsequent authoritative resolutions;
no usermeta permission cache determines access. A learner cannot submit a grant.

Hooks remain show_user_profile, edit_user_profile, user_profile_update_errors and
profile_update. Core validation errors prevent plugin commits. Core profile fields
may already have saved when a plugin CAS conflicts/fails: the error explicitly
states this, gives the result code and requires reload/review. No core-profile
rollback is claimed. Conflicts detected at validation say the profile may be stale
or its nonce invalid; post-validation races return `conflict`. A failed validation
or conflict does not partially save Papi Jo fields. Programmatic user updates with
no queued validated plugin request still do nothing to plugin state.

## Files created and modified

New production: `includes/Profile/class-profile-state.php` and
`includes/Profile/class-profile-store.php`.

Modified production: `includes/profiles.php`,
`includes/Profile/class-profile-service.php`,
`includes/Profile/class-profile-controller.php`.

New tests: `tests/profile/memory-store.php` (aggregate double retaining original
fixture vocabulary), `tests/profile/mysql.php` (real wpdb/disposable SQL suite).
Modified tests: `tests/profile/run.php`, `tests/admin/coexistence.php`.
Updated documentation: this report, architecture.md, phase-2a-verification.md and
readme.txt (replaces the obsolete usermeta storage description).
Private test/hash evidence is retained in
`PRIVATE_EVIDENCE_OMITTED`, not deployed.

## Actual test results

Passed separately on **PHP 7.4.33, 8.3.18 and 8.4.22**, MySQL **9.1.0**:

| Suite | Per-version result |
| --- | --- |
| Profile/form/security regression | 134 assertions (original 124 plus hardening cases) |
| Profile real MySQL | 56 assertions, five two-process SQL-barrier races, fresh-process reload |
| Foundation | 18 scenarios / 513 assertions |
| Domain/Backdrop parity | 2,315 assertions including 919 parity assertions |
| Theme persistence doubles | 204 assertions |
| Theme persistence real MySQL | 49 assertions, six update races plus initialization race |
| Administration/refinements | 304 assertions |
| SNORDIAN coexistence | 4 signals / 44 assertions |
| Syntax | All 63 production/test PHP files |

Existing DOM doubles also pass 30 assertions. No live browser certification is
claimed. Disposable databases use random pjh5pwp_test_* names; credentials are
read as literals and passed privately to workers, never persisted or printed.
Connections select only those disposable databases, never acceptance.example.test. Temporary
tables contain synthetic user IDs, no copied WordPress user data, and are removed
with the test databases. The actual installed wpdb source is loaded independently
without CMS bootstrap. Initial test-fixture runs exposed a test-only double-prefix
table-name mismatch; correcting the fixture made the real adapter runs pass.

The SQL barrier pauses both independent processes immediately before their actual
INSERT/conditional UPDATE. Tests verify one winner, one conflict, exactly one row,
one revision and the entire winning aggregate for initialization, administrator
grants, learner theme, learner Density and revocation versus preference saves.
They also test exact all-four-field replacement, actual read-only-session SQL
failure leaving the entire prior record unchanged, stale/repeated requests, no-op,
invalid values, unknown/malformed state, legacy-key rejection, first/repeated
installation, and refusal to convert a MyISAM table. A changed-session simulation
after a real successful SQL commit verifies unknown-outcome classification; this
is not a real network-disconnect/replication fault test.

Existing profile security tests still cover four grant combinations, self-grants,
cross-user writes, capabilities, nonce/forged/missing payloads, publication/deletion
fallback, Core/global distinction, escaping and independent Density. Added cases
cover no-op revision preservation, ABA rejection and a grant revocation between
service validation and final CAS. Foreign SNORDIAN options/callbacks remain unchanged
in fixtures and runtime suspension remains in force.

Reproduction, with each available PHP executable:

```powershell
$phpPath = 'PHP_CLI'
& $phpPath -n tests/profile/run.php
& $phpPath -n -d extension_dir=LOCAL_PATH_OMITTED -d extension=mysqli tests/profile/mysql.php WORDPRESS_ROOT WORDPRESS_ROOT/wp-config.php
```

The two acceptance.example.test paths above are read-only **source/credential-literal references**,
not the selected test database. No existing user data is read into the fixture.

## Preservation, remaining risks and deployment gate

All **903 protected files** match before/after hashes. No acceptance.example.test file, database,
user record, activation, SNORDIAN setting, H5P content, Apache/HTTPS configuration,
Backdrop source/runtime, runtime feature, commit, tag, push or publication changed.
No live database audit or CMS bootstrap was performed. Unrelated external database
changes are not ruled out by the filesystem audit.

No new table exists on acceptance.example.test and the active plugin remains at Phase 1C-D.
Multisite, database routing/drop-ins, external writers to our table and schema
changes during writes are unsupported. MariaDB/other server versions, true network
loss, browser session/nonce transport and production durability configurations are
not certified. User deletion does not yet remove an orphaned plugin row; account
IDs must not be manually reused. No automatic cleanup/import is introduced. The
permissions and preferences remain separate fields of one atomic aggregate.

## Proposed controlled deployment — still requires explicit approval

1. Verify fresh backups/restore readiness and deployed/source hashes; confirm no
   unexpected old four-key data. A legacy finding stops for an explicit import or
   quarantine decision, with no automatic write or discard.
2. Back up affected deployed files outside the web root; stage the entire coherent
   Phase 2A plus hardening release (including two new state/store definitions).
   Do not deploy tests/private evidence. Prevent mixed-version requests while
   switching composition/loaders. Syntax/hash-verify the payload.
3. Obtain explicit authorization for live DDL. In a trusted, capability-checked
   operator/CLI context, call `Profile_Store::install()` for the correct single-site
   prefix. Verify InnoDB/schema. Do not add silent activation or GET installation.
   Until installation succeeds, profile storage is visibly unavailable and grants
   fail closed. No users need bulk initialization.
4. Read-only bootstrap/menu/hooks/storage checks first. Real profile writes require
   a separately agreed disposable-account acceptance scope. Preserve real users,
   H5P and SNORDIAN configuration throughout. Stop on errors.
5. Rollback restores the coherent pre-profile code and preserves any new table/data
   for review. Do not roll back to unsafe four-key profile writers, drop the table,
   or claim file rollback restores user data. Core fields are independent.

## Proposed Firefox acceptance — not executed

1. In normal Firefox at https://acceptance.example.test, confirm existing themes/admin navigation,
   SNORDIAN administration/runtime and Papi Jo suspension remain intact.
2. Using separately authorized disposable accounts, verify all four combinations
   of administrator permissions and learner own-profile controls/defaults.
3. Test global versus Core, all built-ins, published custom choices, unavailable
   preservation/replacement and independent four-value Density preferences.
4. Open two administrator tabs at the same revision, save one, then submit the
   other: stale form must fail without overwriting the winner. Repeat learner
   preference tabs and administrator revocation versus an older learner form.
5. Confirm revocation hides controls/effective use while retaining saved preferences.
   Confirm unrelated/missing fields and core validation errors do not change plugin
   state. Check clear core-versus-plugin failure messages and repeat/no-op behavior.
6. Restore only approved test data, inspect logs and report results. No Density or
   theme runtime application should occur. No Phase 2B work is authorized.

## Historical investigation — superseded implementation status below

**Status: investigation complete; hardening implementation NOT STARTED. Deployment
remains PAUSED.** The user explicitly requires approval before a dedicated table
or significant migration. The recommendation below crosses that gate. Existing
Phase 2A code still has its documented persistence limitations and must not deploy.

## Root cause and verified WordPress behavior

Reviewed phase-2a-verification.md, wordpress-persistence.md, architecture.md,
Profile_Service, Profile_Controller, installed WordPress meta.php, user-edit.php,
admin/includes/user.php and its standard usermeta table declaration.

Current keys and defaults:

| Key | Meaning | Missing value |
| --- | --- | --- |
| pjh5pwp_allow_theme | Administrator theme-selection grant | Disabled |
| pjh5pwp_allow_density | Independent administrator Density grant | Disabled |
| pjh5pwp_theme | Stable personal theme token | Follow global |
| pjh5pwp_density | original/small/medium/large | Original |

`plan()` checks capabilities, a nonce bound to current metadata, strict fields and
current theme availability, then returns changed fields. `commit()` loops through
that patch in submitted order, invoking update_user_meta once per changed field.
Failure after the first successful call leaves the preceding fields saved. Its
subsequent read can distinguish an unchanged value from some failures but cannot
undo earlier writes or prove an uncertain write was not committed.

Two requests can both validate against the same old values before either writes.
An earlier permission decision may then be overwritten by the later stale request.
An administrator permission change can also race a learner preference update after
its final grant check. Context nonces are not database locks or conditional writes.
Since the current nonce binds values rather than a monotonic revision, an old form
may also become apparently current after values change and return to their old
values. A revision must distinguish those histories.

Installed WordPress meta.php confirms:

- add_metadata(unique=true) checks COUNT, then independently inserts. This is not
  uniqueness enforcement under concurrent initialization.
- update_metadata selects existing IDs, falling back to add_metadata when absent.
  Updates ordinarily match user ID and meta key; multiple matching rows can change.
- A nonempty prev_value adds a value predicate, but does not establish a unique
  per-user row or safe absent-record initialization. Empty prev_value does not
  provide such a predicate. Serialized values also inherit column comparison rules;
  this is not a general byte-exact revision CAS API.
- False is overloaded for no change/failure. Metadata caches and hooks do not turn
  these calls into a multi-key transaction.

The actual WordPress schema declares only the umeta_id primary key and nonunique
user_id/meta_key indexes. The saved Phase 1C-D database-after.json audit records
wp_usermeta as **MyISAM** with those indexes. This is historical installation
evidence, not a new live schema query. No acceptance.example.test connection was opened here.
The previously approved wp_options conversion does not establish InnoDB usermeta.

## WordPress profile lifecycle

The existing controller hooks show_user_profile / edit_user_profile for rendering,
user_profile_update_errors at PHP_INT_MAX for validation, and profile_update for
deferred saving. Core's personal_options_update / edit_user_profile_update run
before edit_user finishes validation, so writing there would regress safety.
Core validation errors prevent wp_update_user and our profile_update commit.

A successful core profile update precedes our Papi Jo persistence. A Papi Jo
conflict/failure must explicitly say that core fields may already have saved and
that the Papi Jo state needs reload/review. There is no implemented or proposed
rollback of WordPress core fields. A new adapter must not share an ambient core
transaction or claim atomicity spanning core and plugin data.

## Disposable MySQL investigation — verified, not production implementation

Ran `PRIVATE_EVIDENCE_OMITTED` on PHP 8.4.22/mysqli using a new
random `pjh5pwp_test_*` database. Credentials were read as literals, never executed,
printed or persisted. Neither connection selected acceptance.example.test. Both temporary tables
copied usermeta-style primary/nonunique indexes; no proposed production table was
created. A two-connection deliberately interleaved schedule made both initializers
observe absence before either inserted.

| Probe | MyISAM | InnoDB |
| --- | --- | --- |
| Two initializers for identical user/key | Both succeed: two rows | Both succeed: two rows |
| UPDATE followed by ROLLBACK | Changed value remains | Original restored |

Expected results PASS; disposable database removal verified. Raw results retained
in `PRIVATE_EVIDENCE_OMITTED`. This demonstrates absent-key
uniqueness and engine transaction limitations. It does **not** demonstrate that
every single MyISAM UPDATE tears, nor prove a proposed hardening adapter works.
It is not a two-process production-adapter concurrency qualification.

## Recommended architecture — pending approval

Prefer a plugin-owned **InnoDB** table named
`{$wpdb->prefix}pjh5pwp_profiles`, with one row per user:

| Proposed column | Purpose |
| --- | --- |
| user_id BIGINT UNSIGNED PRIMARY KEY | Database-enforced one-record-per-user initialization |
| schema_version | Exact supported state format |
| revision BIGINT UNSIGNED | Monotonic version, checked for overflow |
| state LONGBLOB | Strictly validated canonical bytes for separate permissions and preferences |

State payload contains separate `permissions` and `preferences` maps; defaults
deny both grants, follow global theme and retain original Density. Unknown schema,
unknown fields, malformed state and duplicates in any legacy input fail closed
with diagnostics; no destructive repair. No foreign keys or changes to core user
tables are proposed. User deletion/orphan cleanup policy needs explicit definition
and tests; no automatic deletion or export of legacy keys is authorized here.

Proposed write protocol:

1. Read authoritative state through a private standard WordPress database session,
   bypassing stale user-meta caches. Bind nonce to target, displayed revision and
   state witness. Retain that expected state through validation and deferred save.
2. Validate capability, identity, independent current grants and all submitted
   fields. Construct a full next state by applying only authorized present fields.
3. For absence, attempt one INSERT with a database-enforced unique user key. For
   an existing row, issue one conditional UPDATE matching user ID, schema,
   expected revision and byte-exact old state, incrementing revision once.
4. Exactly one concurrent writer can succeed against an expected revision. A
   zero-row mismatch is an explicit conflict; never rebase, merge or retry silently.
   Permission/preference races use the same aggregate/revision: a revoked-grant
   winner therefore prevents an old authorized preference save from committing.
5. Classify lost acknowledgements/reconnections as unknown outcome. Do not blindly
   retry, report success or compensate. Require fresh authoritative inspection.
   Missing fields preserve state. Identical no-op requests verify expected revision
   without incrementing; repeated old mutating requests reject as stale.

Intended guarantee after implementation/verification: all Papi Jo fields in one
request commit together or remain at the previous state; no lost authorized state
update through ordinary concurrent requests. This does not promise persistence
across storage corruption, arbitrary privileged SQL, arbitrary foreign plugins or
unqualified database replication/routing. Server durability settings and reliable
error/outcome classification must be documented and tested, not assumed.

Revocation resolution must use fresh authoritative data for subsequent calls,
without a long-lived cached permission snapshot. Theme/publication fallback, stable
tokens, keep-saved handling and Density independence remain as in Phase 2A. No
runtime consumer/classes/CSS would be added. The common SNORDIAN gate is untouched.

## Alternatives and why approval is required

A single aggregate in usermeta is not inherently impossible. Existing-row CAS
would solve the multi-field issue, but first creation needs a verified independent
serialization protocol because no unique user/key constraint exists. Advisory
locks require every writer to cooperate and careful ownership/reconnect handling;
they do not make MyISAM transactional. A global table lock would contend with all
WordPress usermeta traffic. Neither protocol has been implemented/qualified here.
Converting shared wp_usermeta and adding a suitable uniqueness mechanism would be
a significant core-table change and is not authorized.

Per-user records in the already-InnoDB options table could reuse its unique option
names and Phase 1C-A CAS approach, but changes the approved usermeta storage
contract, adds per-user options/account-lifecycle concerns, and requires a separate
adapter/schema design. It must not be silently substituted.

The dedicated table is the recommended isolation/performance tradeoff, not a claim
that no other correct design exists. Its creation/upgrade lifecycle and replacement
of four-key storage are significant changes, so implementation stops at this gate.
Do not assume Phase 2A's undeployed status proves no external copy has legacy data.
An approved design should explicitly reject legacy four-key data pending an audited
import; never silently discard grants/preferences or merge conflicting sources.

## Tests and security verification status

No new production hardening or corrected-adapter tests exist yet. All requested
new race/failure guarantees remain **NOT IMPLEMENTED / NOT VERIFIED**. Required
acceptance after architectural approval includes two real processes at a shared
write barrier, absent initialization races, two administrator stale forms, grant
revocation versus learner saves, ABA/revision changes, identical/repeated requests,
failed writes, uncertain acknowledgements, malformed/newer state, invalid/forged
payloads, self-grants, account deletion and fresh-process reload. All database
tests must select a disposable database, never acceptance.example.test.

Existing unchanged regressions were rerun on PHP **7.4.33, 8.3.18, 8.4.22**:

| Suite | Per-version result |
| --- | --- |
| Foundation | 18 scenarios / 513 assertions PASS |
| Theme domain | 2,315 assertions, including 919 Backdrop parity PASS |
| Theme persistence doubles | 204 assertions PASS |
| Administration/refinements | 304 assertions PASS |
| SNORDIAN coexistence | 4 signals / 44 assertions PASS |
| Existing Phase 2A profiles | 124 assertions PASS |
| Syntax | 59 production/test PHP files PASS |

Existing DOM doubles: 30 assertions PASS. These results establish the unchanged
baseline only; they do not eliminate its known persistence defects.

## Files and preservation

Created this report, private disposable probe PHP/results and
`PRIVATE_EVIDENCE_OMITTED`. No production or existing test file was
modified. Before/after hashes match for all **903 protected files** (Backdrop
sources/runtime/configuration, acceptance.example.test plugins including SNORDIAN/H5P/Papi Jo,
wp-config and Backdrop settings). No live CMS bootstrap/site database query or
write occurred. Source reads and the historical audit are distinct from live tests.
No deployment, activation, real user changes, runtime work, Apache/certificate
changes, commit, tag, push or publication occurred.

## Revised deployment plan — blocked pending design and implementation

First obtain architectural approval, implement the selected adapter/schema and
explicit install/upgrade contract, then pass isolated security/regression and
disposable MySQL concurrency/failure/reload tests. Deployment remains separately
gated. A future deployment needs verified backup/restore readiness, baseline hashes,
coherent staging and an explicitly authorized table-creation step. Do not auto-run
DDL on GET or silently import/erase old keys. Rollback must preserve newly saved
aggregate state; reverting to unsafe old profile writers is not a safe recovery
plan. Keep profile writing unavailable until a compatible rollback is defined.

**Decision requested:** approve source-only implementation/testing of the proposed
dedicated InnoDB table and explicit schema lifecycle, with acceptance.example.test deployment and
all live DDL still prohibited. No dedicated-table implementation will begin until
that decision is received.
