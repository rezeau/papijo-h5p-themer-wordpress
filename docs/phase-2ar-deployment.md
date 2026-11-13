# Phase 2A-R controlled acceptance.example.test deployment

8 November 2026. **Authorized deployment COMPLETE; read-only verification PASS.**
No role grants, configuration saves, profile writes or account creation occurred.
Firefox acceptance remains pending. Phase 2B has not started.

## Preflight and backup verification

Read the Phase 2A-R verification and Phase 2A-H deployment/verification reports.
The deployed plugin matched the captured Phase 2A-H file baseline exactly. All 24
entries in the Phase 2A-R source manifest matched their recorded hashes. The 12-file
payload below was selected from that verified manifest; source code was not edited.
Existing restore-certified Phase 2A-H SQL backup was present and its hash matched.

The database had legitimately drifted since that backup: wp_users now contained
three users rather than two, and options/usermeta data had changed. These changes
predated this deployment. Structural comparison found only advanced AUTO_INCREMENT
counters, with no engine, column, index, constraint or other schema change. No
migration or unsafe role configuration was found. Deployment was held until a
fresh consistent backup and restore rehearsal passed.

Fresh recovery directory, outside the web root:
`PRIVATE_EVIDENCE_OMITTED` in the source project.

- Dump: `acceptance.example.test-consistent.sql`, **15,936,648 bytes**.
- SHA-256: `PRIVATE_VERIFICATION_DIGEST_OMITTED`.
- **62 tables / 5,853 rows**, all structures, row counts and sorted data checksums
  matched the disposable restore. Ten verifier negative controls passed.
- Source tables were temporarily READ-locked for a consistent mixed-engine backup;
  no source DML or DDL was performed. Source remained unchanged during rehearsal.
- Counter probes were confined to the disposable restored wp_options. The disposable
  database was dropped and its absence verified. Temporary client credentials were
  removed. The dump hash was rechecked after deployment.
- All seven replaced files have verified old-byte backups under `files-backup/`.
  The five new files are recorded as previously absent in `plan.json`.

## Exact deployed files

Paths below are relative to
`PROJECT_ROOT/`.

- `includes/roles.php` — new
- `includes/Role/class-role-state.php` — new
- `includes/Role/class-role-store.php` — new
- `includes/Role/class-role-service.php` — new
- `includes/Role/class-role-view.php` — new
- `includes/administration.php` — replaced
- `includes/profiles.php` — replaced
- `includes/Admin/class-admin-controller.php` — replaced
- `includes/Profile/class-profile-service.php` — replaced
- `includes/Profile/class-profile-controller.php` — replaced
- `includes/Profile/class-profile-state.php` — replaced
- `readme.txt` — replaced

Only these 12 files changed in a complete before/after acceptance.example.test filesystem audit.
No tests, private helpers, SQL backup or source docs were copied into the web root.
Definitions were staged before their loaders, with per-file atomic replacement.
A temporary standard `.maintenance` gate prevented new mixed-version requests and
was removed after syntax/hash checks. No activation cycle was performed.
All **44 deployed PHP files** passed syntax checks on PHP 8.4.22; all 12 payload
hashes match the verified source. Version remains 0.1.0.

## Configuration initialization and role defaults

`pjh5pwp_role_permissions` was absent before deployment and remains absent. This is
valid default-denial state; the documented implementation needs no initialization
write until an explicit changed-map Save. No option, grant, capability or user
preference was added or updated.

The seven registered roles are administrator, editor, author, contributor,
subscriber, lp_teacher and tutor_instructor. Administrator displays **Allowed
automatically** through manage_options capability. The other six roles have no
grants and their checkboxes are unchecked. The absent option contains no obsolete
role identifiers. The configuration was not silently populated or repaired.

## Security, compatibility and concurrency checks

Native read-only verification passed **52 checks**, using WordPress 7.1.4 and
PHP 8.4.22. The query filter rejected any SQL mutation before execution; no blocked
write attempts or PHP warnings occurred in the successful check, including shutdown.
Current-user selection was process-local and did not create login sessions or
change accounts.

- The registered Papi Jo Themes page callback renders Role permissions and its
  explicit Save action. Existing theme storage remains ready at revision 31.
- The existing administrator resolves as automatically authorized and sees both
  own-Profile Theme and Density selectors without a role grant.
- The two other existing users resolve denied and see neither selector. All three
  profile records remain absent. Viewing forms creates no profile state.
- Edit User rendering for other users has no individual permission or preference
  fields. Own-Profile rendering contains no historical individual grant controls.
- H5P 1.17.10 / Core API 1.28 remains supported. SNORDIAN 0.0.12 stays active;
  its priority-10 callback and both original script paths remain intact. Separate
  menus, five immutable previews, Create navigation and existing administration
  render checks pass. Synthetic Edit/delete markup checks used memory only.
- Papi Jo runtime remains suspended, disabled and unimplemented. No playback theme
  CSS, native Density classes, activity selectors or runtime hooks were added.
- Profile_Store is unchanged from Phase 2A-H. Its InnoDB table, unique user primary
  key, schema/version validation, monotonic revision and exact-state CAS remain
  intact. The tested role shared-lock/profile-CAS code matches deployed hashes.
  Source qualification on PHP 7.4/8.3/8.4 already covered profile races and actual
  role-revocation serialization in disposable MySQL databases. Those tests were
  not repeated against real profiles; no live write/concurrency certification is
  claimed. Native deployment checks read/validate storage only.

An initial postdeployment helper assumed a literal page-hook name and failed to
find it. The helper was corrected to inspect WordPress's actual registered callback;
verification then passed. No deployed source was changed to accommodate the helper.

## Database change inventory and H5P preservation

**Zero persistent changes in acceptance.example.test.** All 62 table structures, row counts and
sorted row-data SHA-256 checksums matched the fresh predeployment snapshot after
native verification. This includes users, usermeta, options, all H5P content tables
and `wp_pjh5pwp_profiles`, which remains InnoDB with zero rows. There was no DDL,
migration, role configuration initialization, preference update, user creation,
activation change or content modification. The backup rehearsal created/dropped
only a random disposable database, with test inserts confined to that restore.
No WordPress core, Apache or HTTPS configuration changed. No commit/tag/push or
publication occurred.

## Deleted-role-slug precaution

Role slugs are stable identities; display-label changes preserve grants. Deleted
roles cannot authorize while absent from the registry. Historic grants are pruned
on the next explicit role save. Recreating the identical slug before that pruning
can reuse its stored identity/grant; incarnation history is not tracked.
**Save the role configuration while a removed role is absent before reusing its
slug.** No historical grants exist now because the role option is absent. No role
was deleted, recreated or granted during deployment. Future saves still require
explicit authorization under the current stop condition.

## Rollback instructions

1. Obtain/confirm rollback scope, prevent new mixed-version requests and verify
   current files against `plan.json`; investigate any subsequent changes first.
2. Restore the seven replaced files from the hash-verified `files-backup/` tree as
   a coherent Phase 2A-H release. Restore old loaders/controllers/services together.
3. Once old loaders no longer depend on them, remove only the five new files listed
   above. Verify every old hash from `plan.json`, full PHP syntax and native checks;
   remove the maintenance gate after the coherent release is ready.
4. Preserve the role option (if later created), all preferences and the existing
   profile table for review. Reintroducing old code restores the historic meaning
   of inert permission flags, so inspect those values before enabling old writers.
   File rollback does not roll back core fields or database state.
5. No database restore is needed for this deployment's zero-data-change result.
   Do not drop the profile table or overwrite the live database automatically.
   Any database recovery requires a separate assessment and authorization; use the
   fresh verified 62-table backup rather than the older pre-table dump.

## Ordered Firefox acceptance checklist

Currently authorized checks are **view-only**; do not click Save role permissions,
Update Profile or Update User. Do not create learner accounts or change theme data.

1. Open https://acceptance.example.test/wp-admin/ in normal Firefox and sign in normally.
2. Open H5P Content → Papi Jo H5P Themes. Confirm existing catalog, previews,
   navigation, global default and publication display, plus the Role permissions
   section. Confirm all seven roles and current display labels are shown.
3. Confirm Administrator says Allowed automatically without a revocation checkbox;
   all other role permission boxes must remain unchecked. Do not save.
4. Open the administrator's own Profile without saving: both Theme and Density
   selectors should be visible with safe defaults/retained values.
5. Open another existing user's Edit User without saving: no individual H5P grant
   checkboxes or preference controls should appear. If a non-administrator session
   is already available, view its own Profile and confirm both controls are hidden.
6. Inspect SNORDIAN settings without saving and view existing H5P content. Confirm
   unchanged behavior/appearance, active SNORDIAN and Papi Jo suspension notices.
7. Report layout, labels, keyboard navigation or PHP/browser errors. Stop here.

Only after separate explicit role-change/profile-write authorization: test grants,
revocation/restoration, independent preference round trips, stale role/profile tabs,
validation failures and custom-role lifecycle using an agreed disposable-user/test
scope. The full ordered write-acceptance plan remains in phase-2ar-verification.md.

## Evidence and stop

Private evidence: `plan.json`, `files-before.json`, `file-audit.json`,
`backup-verified.json`, `locked-database-baseline.json`, `restored-snapshot.json`,
`database-before.json`, `database-after.json`, `database-comparison.json`,
`native-readonly.json`, `phase-2ar-native.json`, `final-verification.json` and
`files-backup/` under the recovery directory above. Backup files contain private
site data and must remain outside the web root and out of publication.

**Deployment verification stops here. Await explicit authorization for role changes
or profile test writes. No Phase 2B work is authorized.**


> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.
