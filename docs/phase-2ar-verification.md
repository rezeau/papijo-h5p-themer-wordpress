# Phase 2A-R — role-based personalization permissions

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

7 November 2026. **Source implementation, automated security/concurrency tests and
regressions PASS. Phase 2A-R deployment NOT PERFORMED. Firefox acceptance remains
pending separately authorized deployment and test writes.** Phase 2A-H is already
deployed; this work did not change its files, table, users or settings.

## 1. Revised unified permission architecture

The one permission is **Allow personal H5P theme and Density selection**. It grants
both own-Profile controls together. Theme and Density remain independent values.
There are no individual Allow/Deny/Inherit overrides, new WordPress capabilities,
user-list bulk actions, activity controls or runtime styling hooks.

Reviewed phase-2ah-verification.md, phase-2ah-deployment.md,
phase-2a-verification.md, architecture.md, wordpress-persistence.md and the current
source before implementation. The authoritative Backdrop reference exposes
`administer h5p themes` through hook_permission(), uses user_access() for its
administration access callback and delegates role administration to its CMS. Its
learner/Density implementations were inspected as behavior references. WordPress
role/capability and Profile architecture remain the baseline; obsolete permission
or runtime code was not copied. Installed WordPress capability APIs were inspected
read-only; WordPress was not bootstrapped against acceptance.example.test.

## 2. Dynamic role discovery

`Role_Service::registered()` reads `wp_roles()->roles`, retains stable slugs and
translates display labels with `translate_user_role()`. It does not hard-code the
standard roles. It discovers later plugin/custom roles on the next request. Labels
are escaped, never persisted as identifiers and excluded from the nonce's registry
identity, so renaming a label does not change its permission.

Only assigned roles also present in the current registry can authorize. Any enabled
assigned role is sufficient. Unknown/new slugs default denied; roleless and
anonymous users are denied. Deleted-role grants are ignored even on a stale user
object, and omitted from the next explicitly saved role map. See the stable-identity
limitation in section 15 for deletion followed by deliberate slug reuse.

## 3. Administrator automatic access and capability semantics

Personalization calls `get_userdata(target ID)` then **user_can(target user,
'manage_options')**. This grants inherent access independently of role settings,
missing/malformed role configuration or the literal administrator slug. A custom
role named Administrator gains nothing from its display name. A user with actual
manage_options capability qualifies, including a capability-bearing custom role.
WordPress capability mappings, explicit core capability denies and trusted filters
retain their normal semantics; the plugin does not redefine administrator status.

Administration separately requires the **actor's** current_user_can('manage_options')
and single-site scope. Own-Profile edits additionally require current identity equal
to the target and current_user_can('edit_user', target). An actor's administrator
capability cannot authorize a different target. No edit_users capability or another
user's Edit User form serves as an individual permission-grant mechanism.

## 4. Role permissions administration UI

The existing H5P Content → Papi Jo H5P Themes overview now includes a separate
**Role permissions** section and **Save role permissions** action. Each configurable
registered role has the exact unified-permission checkbox. Roles whose capabilities
include manage_options show **Allowed automatically**, without a revocation input.
The target user's effective capability check remains the final authority.

The form follows the existing wrap/widefat/striped/button styling. It is independent
of global default/publication/theme CRUD forms and is also available when the theme
library is absent. It is not displayed within create/edit/copy/delete screens.
GET and rendering do not initialize configuration or profiles. Success/error notices
are specific to role saves. This form displays its result in the current response;
resubmitting a changed successful POST is safely rejected as stale.

The page-load POST route checks capability before dispatch, unslashes once, verifies
a session-bound nonce and validates the complete role set and exact string 0/1
values. Hidden zeroes make intentional unchecking explicit. A final completion
marker and exact expected keys reject truncated/missing submissions, unknown slugs,
automatic-role checkbox forgeries, arrays, booleans and integer flags. Nonces bind
both the entire revisioned role snapshot and the sorted registered slug/automatic
capability identity. Role additions, removals or automatic-status changes invalidate
older forms; label-only changes preserve identity. Malformed stored state is
preserved and visibly blocks saves rather than offering destructive repair.

## 5. Effective permission resolution

`Role_Service::allowed(int target ID)` is the single read resolver:

1. Deny multisite, nonexistent users and invalid/nonpositive IDs.
2. Allow the target's manage_options capability without consulting configuration.
3. Otherwise read validated authoritative role configuration and registered roles.
4. Allow any assigned, still-registered, enabled role; otherwise deny.

`Profile_Service::personalized()` delegates to it. Rendering, preference planning
and `effective()` use that decision. The deferred save rechecks the same role-grant
predicate under a shared lock through `while_allowed()` before invoking profile
CAS. No UI visibility is trusted for authorization. Future runtime code must use
this service and independently pass the unchanged Runtime_Policy suspension gate;
no runtime consumer was implemented here.

## 6. Removal of individual permission controls

Profile_Controller no longer renders administrator grant checkboxes on Edit User
or anywhere else. Another user's Edit User page renders no personal controls.
Profile_Service accepts only theme and density; allow_theme, allow_density,
unknown/mixed fields and cross-user preference forgeries reject the entire plan.
Failed-form redisplay also reads only those two preference fields. The obsolete
administrator-target method and permission save branches are removed. No replacement
override API, usermeta writer, bulk operation or second grant mechanism exists.

## 7. Preservation of personal preferences

Authorized users, including administrators, see both selectors on their own Profile:

| Preference | Choices / meaning |
| --- | --- |
| Theme | Use site default, H5P Core, five built-ins, published resolvable custom themes |
| Density | Original, Small, Medium, Large; Original means no native override |

Stable tokens, explicit Core versus global, all five built-ins and independent
legacy-density handling remain unchanged. Unpublished/deleted custom themes are
not offered as new selections. An unavailable saved token is retained through the
existing keep_saved sentinel, with safe global/Core fallback and explicit replacement.

Revocation hides both controls, rejects submitted preferences and makes effective()
use global/Core plus Original. Saved preferences remain untouched; restoration
reuses them subject to normal theme availability. Unrelated core-field saves, absent
plugin payloads and missing preference members cannot reset stored preferences.
Viewing an authorized or unauthorized Profile creates no record.

## 8. Role configuration persistence

The new fixed WordPress option is **pjh5pwp_role_permissions**, with autoload off.
Schema 1 is canonical serialized PHP data in this order:

```php
array('schema_version' => 1, 'revision' => 0,
      'roles' => array('subscriber' => true));
```

The role map is sorted and sparse: only true grants are stored. The first changed
save uses revision zero; subsequent changes increment exactly once. A default-denial
no-op keeps an absent option absent; existing no-ops preserve revision after an
authoritative nonce/snapshot check. Unknown schema, extra fields, malformed grants,
noncanonical bytes, invalid revisions or oversized serialized data fail closed.
The existing Snapshot_Codec limit is 4 MiB, with classes disabled on unserialization.

Role_Store reuses WordPress_Connections, Snapshot_Codec and Storage_Exception, and
follows the established Phase 1C-A option CAS pattern: private nonpersistent session,
standard InnoDB/utf8mb4/unique-name/primary-ID/autocommit/trigger validation,
parameterized hex transport, plain unique INSERT, and conditional UPDATE matching
physical row ID plus BINARY exact previous bytes. It does not use add_option() or
update_option() read-modify-write. Whole-map updates cannot partly apply. Concurrent
administrators get one winner and one conflict, without rebase, merge or blind retry.

Authoritative reads bypass the Options API cache. Committed/unknown writes evict
the option/alloptions/notoptions cache keys. Unknown acknowledgements and committed
cache failures have distinct diagnostics and never claim rollback.

## 9. Profile-state persistence and revocation serialization

The Phase 2A-H table remains `{$wpdb->prefix}pjh5pwp_profiles`. Its source adapter
is byte-for-byte unchanged and matches the deployed adapter hash. The unique user
primary key, canonical version-1 aggregate, SQL schema/revision/bytes agreement,
private sessions, exact-state/revision CAS, safe concurrent first INSERT, no-op
behavior, strict validation and unknown-outcome handling are preserved.

For role-authorized preference writes, Role_Store takes a short shared lock on its
role option using START TRANSACTION and SELECT ... LOCK IN SHARE MODE. Under that
current locking read, Role_Service rechecks the assigned registered role grant and
holds the lock through the existing, independently committed profile CAS. It then
rolls back the read-only lock transaction and closes its session. It never writes
role data or includes core profile fields in that transaction. Administrator saves
bypass the configuration dependency and shared lock.

An exclusive role update cannot pass an already-held shared grant lock until that
authorized profile write finishes. If revocation won first, the locking read sees
the revoked map and performs no profile SQL. Thus overlapping cooperative saves
have an explicit order; revocation never partially edits a profile aggregate.
Lock/read failures block preference commits. Cleanup does not mask an acknowledged
profile commit. The shared lock also serializes unrelated role-map updates briefly;
callbacks are limited to the profile CAS, with no user interaction or network work.

## 10. Schema and migration implications

**No new table, DDL, structural migration or profile data migration is required.**
The existing profile schema/version and canonical field order stay unchanged.
Historical allow_theme/allow_density flags remain strictly validated, inert
compatibility bytes. Their stored values are preserved on preference changes;
they never authorize, render controls or accept HTTP updates. Defaults remain zero.
This works for populated deployed tables, not just the previously empty table.

No profile scan, rewrite, bulk initialization or usermeta import occurs. Old
prototype usermeta still causes explicit review rather than unsafe fallback. The
theme-library option/schema and its adapter are unchanged. The new role option is
independent, initialized only by an explicit changed-map Save; activation creates
nothing. Removing inert compatibility fields in a future release would require a
separately reviewed data-format migration. Rolling back to old code can restore
the historical meaning of those flags and requires deliberate review.

## 11. Security and WordPress lifecycle guarantees

The existing show/edit Profile hooks, late user_profile_update_errors validation
and deferred profile_update commit remain. Core errors prevent plugin commits.
The nonce includes the complete monotonic profile record, preventing stale/ABA
submissions. A deferred save replans from a fresh snapshot and uses final profile
CAS, plus the locked current role check. A stale or forged mixed submission cannot
partly update either personal field. Programmatic core updates with no queued
plugin submission do nothing to Papi Jo state.

Core fields may already have saved before a Papi Jo conflict/failure. The error
explicitly says so, reports the outcome and asks for reload/review; no WordPress
rollback is claimed. There are no REST/AJAX/public write endpoints. Output uses
WordPress translation and escaping. Target identity comes from WordPress hooks,
not a submitted user ID. Capability and nonce checks are independent.

## 12. Actual automated results

All suites passed separately on **PHP 7.4.33, 8.3.18 and 8.4.22**, with real
MySQL **9.1.0** for SQL suites:

| Suite | Per-version result |
| --- | --- |
| Unified roles/profile authorization, preferences, lifecycle | 251 assertions |
| Role storage faults, stale writes, cleanup and CAS doubles | 46 assertions |
| Role real MySQL | 69 assertions; seven administrator races plus learner/revocation serialization |
| Existing profile real MySQL | 56 assertions; five two-process races and fresh-process reload |
| Foundation / actual H5P admission | 18 scenarios, 513 assertions |
| Domain / authoritative Backdrop parity | 2,315 assertions, including 919 parity assertions |
| Existing theme storage doubles | 204 assertions |
| Existing theme real MySQL | 49 assertions; six updates plus initialization race |
| Administration / UX / new role form integration | 314 assertions, including the existing 94 UX assertions |
| SNORDIAN coexistence | Four isolated signals, 44 additional assertions |
| Syntax | All 71 production/test/preset PHP files |

Existing JavaScript DOM suite: **30 assertions PASS**. No new browser certification
is claimed. Initial sandbox runs exposed the documented Windows is_readable()
false-negative and blocked local sockets; authorized normal-process runs resolved
these environmental restrictions without changing production detection code.

Coverage includes automatic/capability-based administrator access, independence
from configuration, default/granted/revoked subscribers, custom/new/renamed/deleted
roles, multiple roles, invalid identifiers, capability/nonce failures, truncated and
forged submissions, stale/repeated forms, all 28 fixed-theme/Density combinations,
publication/deletion fallback, stored flag inertness, restoration, no record on GET,
strict profile validation, core error handling, profile ABA/final-CAS races,
missing/malformed/unavailable role configuration, unknown acknowledgements and
unchanged foreign SNORDIAN state. Integration tests exercise the actual admin
controller route, role-specific notices and absence from theme editors.

SQL workers synchronize after their adapter reads, immediately before the actual
INSERT/conditional UPDATE. Role races verify one row, one winner, one revision and
the full winning map. The lock test pauses a learner immediately before profile
SQL with its shared grant lock held; an independent exclusive role UPDATE receives
actual lock timeout 1205. The learner then commits its complete profile, revocation
commits after lock release, and a subsequent learner request is denied without
changing profile values or revision. Real SQL read-only failures retain full state.

All database writes used new random pjh5pwp_test_* databases and synthetic IDs.
acceptance.example.test was never selected by test connections. Literal credentials were read
without executing wp-config.php and were neither printed nor persisted. Cleanup
removed the databases; the new role suite and existing profile suite verify absence.
No live site bootstrap, account creation or live table write was performed.

Reproduction (repeat with each PHP version and corresponding extension directory):

```powershell
$phpPath = 'PHP_CLI'
& $phpPath -n tests/profile/run.php
& $phpPath -n tests/roles/run.php
& $phpPath -n tests/admin/run.php
& $phpPath -n -d extension_dir=LOCAL_PATH_OMITTED -d extension=mysqli tests/roles/mysql.php WORDPRESS_ROOT WORDPRESS_ROOT/wp-config.php
```

Those acceptance.example.test paths are read-only source/credential references, never the selected
test database. The private complete runner and raw evidence are in
PRIVATE_EVIDENCE_OMITTED and phase-2ar-tests.json.

## 13. Files created and modified

Created production: includes/roles.php and includes/Role/class-role-state.php,
class-role-store.php, class-role-service.php, class-role-view.php.

Modified production: includes/administration.php, includes/profiles.php,
includes/Admin/class-admin-controller.php, includes/Profile/class-profile-service.php,
class-profile-controller.php and class-profile-state.php (compatibility comment only).
The existing composition root uses the new lazy defaults through these loaders;
its file and the main plugin entry point did not need modification.

Created tests: tests/roles/run.php, mysql.php and admin-regressions.php.
Modified tests: tests/profile/run.php, memory-store.php; tests/admin/run.php,
coexistence.php; tests/storage/fake-database.php (shared-read transaction support).
Existing profile/mysql.php and the theme production adapters remain unchanged.

Created this report. Updated README.md, readme.txt, docs/architecture.md and
docs/wordpress-persistence.md. Private manifests/test logs/runner under
.local-acceptance are local evidence and must not be deployed. No asset, preset,
domain, compatibility policy or runtime code changed. Version remains 0.1.0.

## 14. Protected-file verification

Before/after recursive SHA-256 checks found **zero changed, removed or added files**
in the protected roots. The historical 903-file protection scope is still covered;
its current non-Git inventory is **909 files**, reflecting six additional deployed
files since the earlier baseline. The broader captured inventory, including Git
metadata, contains 1,117 files, also unchanged during this work.

Scopes: both Backdrop source repositories, installed Backdrop Themer, the complete
acceptance.example.test plugins tree, Backdrop active configuration, wp-config.php and Backdrop
settings.php. Evidence: phase-2ar-protected-before.json and
phase-2ar-protected-verification.json in .local-acceptance. The source/deployed
Profile_Store hash also matches. This is a filesystem preservation audit, not a
new live database checksum audit or a claim about unrelated external activity.

No acceptance.example.test plugin/database/user, SNORDIAN file/setting/activation, WordPress core,
Apache/HTTPS configuration or protected Backdrop file was changed. No deployment,
commit, tag, push or publication occurred. Papi Jo playback styling and native
Density application remain unimplemented and suspended with SNORDIAN active.

## 15. Remaining limits

- Firefox session/nonce transport, real profile submissions and visual/accessibility
  acceptance still require the separately approved deployment/test scope below.
- Standard single-site wpdb, private nonpersistent connections and compatible
  InnoDB options/profile tables remain required. Multisite, routing/drop-ins,
  MariaDB and alternate database server versions are not certified.
- The shared-lock protocol covers cooperative Papi Jo role-option and profile
  writes. WordPress/third-party changes to user roles/capabilities, manual direct
  SQL writers, DDL, forced connection termination and true network loss are outside
  an atomic cross-system guarantee. Database lock waits fail safely; no retry is
  automatic. A changed-session simulation tests acknowledgement classification,
  not real transport failure or server crash durability.
- Slugs define role identity. A deleted role never authorizes while absent. Its
  stored grant is pruned on the next explicit role save; deliberately recreating
  the identical slug before that save reuses the same stored identity. This version
  does not maintain role incarnation history or hook third-party role deletions.
  Before reusing a removed slug, save the role configuration while it is absent.
- Existing Phase 2A-H orphan-row cleanup and legacy-usermeta review limitations
  remain. No destructive importer, automatic repair or user-ID reuse policy is added.
- Source has no Git repository metadata; verification uses explicit file/test/hash
  evidence. No commit or deployment artifact is implied by this implementation.

## 16. Proposed controlled deployment — not authorized or performed

1. Obtain explicit Phase 2A-R deployment approval. Review the stable-slug identity
   policy, current registered roles and intended grants. Default is denial for all
   non-administrators; historical individual flags will no longer grant access.
2. Verify fresh backup/restore readiness and deployed/source hashes. Inspect profile
   schema and existing data read-only; preserve populated rows and inert flags. An
   unexpected schema, legacy metadata or malformed role option stops for review.
3. Stage the five new role production files and coherent seven modified production
   files (plus documentation if desired). Exclude tests/private evidence. Prevent
   mixed-version requests during switching and syntax/hash-check the payload.
   No activation cycle, table installer, DDL or profile migration is needed.
4. Verify bootstrap, existing theme administration and automatic administrator own
   Profile controls read-only first. Confirm SNORDIAN stays active and Papi Jo
   runtime suspended. Role Save and learner Profile writes require the explicitly
   approved acceptance scope and backup plan; do not create test users implicitly.
5. Execute the ordered Firefox checklist using separately approved disposable users,
   roles and custom themes. Record the original role option and approved test data.
   Stop on conflicts/storage errors; do not blindly retry unknown outcomes.
6. Rollback restores a coherent pre-2A-R code set and preserves both the role option
   and profile table for review. Review retained historical flags before any old
   authorization code is reintroduced. File rollback does not undo independently
   saved core fields, role configuration or personal preferences. Do not drop the
   profile table, erase preferences or restore the whole database automatically.

## 17. Ordered Firefox acceptance checklist — pending authorization

1. Sign in to https://acceptance.example.test in normal Firefox with an authorized administrator.
   Confirm theme overview, previews, inheritance, publication, global default,
   CRUD navigation and SNORDIAN suspension diagnostics remain intact.
2. Inspect Role permissions. All currently registered roles must appear with current
   labels. Administrator/capability-bearing roles show Allowed automatically and
   no revocation checkbox. Confirm the exact unified label and explicit Save action.
3. Open the administrator's own Profile: both preference controls are available
   without any role grant. Open another user's Edit User: no individual permission
   boxes or preference controls appear. View-only actions must create no profile row.
4. With separately authorized disposable subscriber accounts, confirm default denial.
   Enable Subscriber on the role form, Save, then reload the learner's own Profile
   in a separate session. Both controls must appear together; no grant controls.
5. Save Use site default, explicit Core and each built-in with different Density
   choices. Verify Original/Small/Medium/Large round trips and independence. No
   player/editor selector, Papi Jo runtime class or runtime color change may appear.
6. Publish an approved disposable custom theme and select it. Unpublish/delete it
   under the test scope: new selection disappears, saved preference is retained,
   fallback is safe and unrelated Profile saves do not replace it. Explicitly choose
   a valid replacement and verify stable identity.
7. Revoke Subscriber in an administrator session. Reload learner Profile: both
   controls disappear; submit an older learner tab and require a visible rejection.
   Saved preferences must remain. Restore the grant and verify saved choices return.
8. Open two administrator role tabs at one revision; save different maps. The second
   must fail visibly with no overwrite. Repeat with two learner Profile tabs; check
   stale/ABA rejection and that no part of a losing request is accepted.
9. Under the approved disposable-role scope, create a new role (default denied),
   grant it, rename its display label and verify the grant survives. Verify a
   multi-role user is authorized through any enabled role. Delete the custom role
   and confirm stale assignments do not authorize. Save before reusing its slug.
10. Exercise missing/bad nonce and forged individual/unknown role fields with approved
    test requests. Confirm capability failures, no cross-user preference writes and
    no role writes through Profile submissions. Trigger a core validation error and
    verify Papi Jo state does not commit. Review keyboard labels and error notices.
11. Confirm existing H5P playback/SNORDIAN behavior and all non-test data remain
    unchanged. Restore only the agreed test data, inspect logs and report acceptance.
    Do not deploy further changes or start Phase 2B automatically.

**Stop here. Await explicit deployment authorization.**
