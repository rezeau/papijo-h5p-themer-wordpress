# Phase 2A-H controlled deployment — stopped at backup gate

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

**LATEST STATUS: authorized resumed deployment COMPLETE — PASS.** Only the ten
planned files were deployed and only the empty `wp_pjh5pwp_profiles` InnoDB table
was created. Read-only verification passed. No account creation or profile test
writes were performed. The previous stopped/rehearsal entries below are historical;
the final resumed-deployment handoff is at the end.

**Updated status after authorized investigation: fresh backup/restore verification
PASS. Deployment and live schema installation remain NOT PERFORMED and require
new explicit authorization.** The initial failed rehearsal below is historical;
the investigation and certified fresh backup are documented at the end.

7 November 2026. **Deployment NOT PERFORMED. Live schema installation NOT PERFORMED.**
The user authorized one local deployment and one dedicated table, conditional on
verified baseline and backup. The fresh restore rehearsal failed a meaningful
schema comparison, so operations stopped before any deployment or source DDL.

## Preflight and file backup

Reviewed Phase 2A-H, Phase 2A, Phase 1C-C and Phase 1C-D reports and existing
backup/rollback helpers. Both source and deployed plugin headers report **0.1.0**;
hashes distinguish the implementations. The exact deployed directory is:
`PROJECT_ROOT`.
Its 43 files exactly match the accepted Phase 1C-D manifest. No unexpected plugin
baseline drift was found. Native preflight passed 27 checks with zero warnings
and zero blocked write attempts, using a pre-bootstrap write-veto query filter.
H5P 1.17.10/Core 1.28 and SNORDIAN 0.0.12 remain active; Papi Jo runtime suspended.
The actual WordPress prefix was checked as **wp_**. Native preflight performed
GET/render reads only; it did not submit forms or change profile data.

Required files were identified, **but none deployed**:

| Existing files requiring replacement | New files requiring addition |
| --- | --- |
| includes/Admin/class-admin-view.php | includes/Profile/class-profile-controller.php |
| includes/Admin/class-diagnostic-notices.php | includes/Profile/class-profile-service.php |
| includes/class-plugin.php | includes/Profile/class-profile-state.php |
| papijo-h5p-themer-wordpress.php | includes/Profile/class-profile-store.php |
| readme.txt | includes/profiles.php |

All five affected existing files were copied outside the web root with hash
verification. deployment-plan.json contains each old/new SHA-256. The post-stop
plugin audit confirms the same 43 files, unchanged. There are no staged site files
to remove and no file rollback is necessary.

## Fresh database backup and failure

Private run/backup directory:

```text
PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED
```

Existing file backups: `files-backup` under that directory.
Database dump: **acceptance.example.test-consistent.sql**, 15,304,115 bytes; SHA-256:
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.

The previously qualified structural verifier/dump routine was copied into this
run, with its expected wp_options engine corrected to the already-approved InnoDB
baseline and the corresponding engine negative control inverted. No schema
comparison rule was relaxed. Source tables were READ-locked during snapshot/dump;
no source conversion was executed. The dump had the expected completion marker
and 61 CREATE TABLE statements and was restored into a disposable database.

**All 61 restored table row counts and sorted data checksums matched.** However,
the structural comparator found one difference:

| Property | Locked source snapshot | Restored database |
| --- | ---: | ---: |
| wp_options AUTO_INCREMENT | 8865 | 8905 |

All other compared structural properties matched. AUTO_INCREMENT was retained as
a relevant table property; the discrepancy was not ignored. The backup is therefore
**NOT certified restore-ready**. A different next-ID value does not establish row
loss, but its cause and consistency implications remain unresolved. Concurrent
allocation is a hypothesis, not a verified explanation. No verifier correction or
new rehearsal was attempted after the stop condition.

Locks were released in the helper's finally block. Read-only cleanup verification
confirmed the disposable restore database is absent, temporary credential file is
removed, and **wp_pjh5pwp_profiles remains absent**. The failed-rehearsal artifacts
and dump are retained for investigation. No backup-ready marker was produced.

## Database changes, schema and compatibility status

No source database DDL/DML was issued by this run. Only temporary backup locks,
read queries and disposable restore database creation/import/removal occurred.
The authorized new table was not created; InnoDB/primary-key/revision verification
of that live table is consequently **NOT PERFORMED**. No profile grants/preferences
or accounts were created, and no H5P content/SNORDIAN settings were modified.
Independent external database activity is not ruled out; this run does not claim
a completed before/after live-schema inventory following installation.

Deployment hash/syntax checks and new-profile-hook verification were not reached.
Earlier source/disposable tests remain documented separately. No Apache/HTTPS/URL,
activation, runtime code, commit, tag, push or publication change occurred.

## Rollback and next authorization

Nothing was deployed or installed, so no rollback is needed or authorized. Do not
restore this uncertified dump over acceptance.example.test. Retain the earlier verified backups;
any database restore would need a separate state/recovery assessment. The file
backups are verified copies of the still-current deployed baseline.

Resume only after authorization to investigate the next-ID discrepancy and repeat
a fresh backup/restore rehearsal. A successful verified rehearsal must precede
deployment, explicit creation of only wp_pjh5pwp_profiles, hash/syntax/native
checks and a database audit showing only that new empty table. Existing users,
H5P content, SNORDIAN settings and all 61 old tables must be preserved.

## Ordered Firefox checklist — deferred

After successful separately resumed deployment, read-only acceptance can:

1. Open normal Firefox at https://acceptance.example.test and verify ordinary WordPress/H5P admin.
2. Confirm existing theme administration and separate SNORDIAN settings remain
   accessible and Papi Jo runtime remains suspended.
3. Inspect Edit User without saving: both new permission boxes must default off
   for a user with no state. Inspect own Profile: no unauthorized personal controls.
4. Review logs and report read-only acceptance results.

**Do not save profile forms or create learner accounts yet.** Four permission
combinations, actual preference saves, stale tabs and revocation tests remain
deferred until explicit profile-test-write authorization. No further development
or automatic deployment resumption is authorized by this report.

## Authorized AUTO_INCREMENT investigation — resolved

The discrepancy was **stale INFORMATION_SCHEMA statistics**, not a change made
by restoration. Historical artifacts establish this chain:

| Evidence | wp_options next counter |
| --- | ---: |
| Original locked snapshot, cached INFORMATION_SCHEMA.TABLES | 8865 |
| Same original snapshot, SHOW CREATE TABLE | 8905 |
| Original dump CREATE TABLE | 8905 |
| Restored SHOW CREATE TABLE and INFORMATION_SCHEMA | 8905 |
| Highest existing option_id in original 291 rows | 8903 |

The original source's own DDL already specified 8905. The restored counter did not
advance from a true source counter of 8865: the comparator combined fresh DDL with
cached dynamic metadata. Maximum ID 8903 also demonstrates why 8865 was not a
credible fresh next-ID reading.

A new read-only source connection on MySQL **9.1.0**, InnoDB wp_options, reproduced
the cause directly: session `information_schema_stats_expiry=86400` returned 8865;
`SET SESSION information_schema_stats_expiry=0` returned 8905, matching SHOW CREATE
and MAX(option_id)=8903. This changes only the private audit session, not server
configuration or live table definitions/data. No ANALYZE TABLE was run.

[MySQL's system-variable documentation](https://dev.mysql.com/doc/refman/8.4/en/server-system-variables.html#sysvar_information_schema_stats_expiry)
identifies TABLES.AUTO_INCREMENT as cached dynamic metadata and documents zero
expiry as the method for fetching fresh engine statistics without updating the
statistics cache. The installed-server reproduction confirms this behavior locally.

The old restore procedure only imported the dump and read snapshots before the
failure; it did not perform inserts in the restored wp_options. The verified dump
already contained the restored counter. There is no evidence of lost/modified
records, restore-side counter inflation or concurrent row changes causing that
specific mismatch. The historical gap between MAX=8903 and next=8905 is valid;
the earlier allocation/deletion/rollback that produced it was not traced and need
not be invented to establish identifier safety.

## Corrected acceptance criteria and verifier

Private schema-audit.php now sets `information_schema_stats_expiry=0` at every
snapshot, on both source and restored sessions. Structural comparison still
includes AUTO_INCREMENT; it is not stripped or blanket-ignored.

Certification requires identical table inventory; structural engine/column/type/
charset/collation/index/primary/unique/foreign/check/trigger/table-option definitions;
identical row counts and sorted serialized-row SHA-256 checksums; and valid fresh
counters on every AUTO_INCREMENT table. Each fresh counter must agree with its
SHOW CREATE value and exceed the maximum stored ID. MySQL's omission of the
default table option AUTO_INCREMENT=1 is interpreted as 1 only for this cross-check.
The first new cross-check attempt stopped on that ordinary default omission before
dumping; correcting this notation assumption did not relax any schema comparison.

Raw CREATE text can differ through explicit CHARACTER SET notation while its
structural properties match. No counter mismatch was needed or accepted in the
successful fresh rehearsal: all fresh structural counters matched exactly.
Data hashes/counts and enforced primary/unique constraints guard against omitted,
modified or duplicated records. Neither row counts nor MAX alone certify the data.
Ten existing negative controls still reject meaningful schema/data changes; an
additional test rejects the old unsafe/stale 8865 counter.

## Certified fresh backup and restore rehearsal — PASS

Fresh backup and private evidence directory:

```text
PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED
```

- Dump: `acceptance.example.test-consistent.sql`, **15,304,115 bytes**.
- SHA-256: `PRIVATE_VERIFICATION_DIGEST_OMITTED`.
- **61 tables / 5,817 rows** restored; all inventories, structures, counts and data
  checksums match. wp_options has 291 rows; source, dump and restore next counter
  **8905**, maximum option_id **8903**. Storage engines and primary/unique
  definitions match. There were 57 raw DDL notation differences but zero structural
  differences. The already-approved wp_options InnoDB engine remains unchanged.
- The source tables were held under READ locks (not READ LOCAL) through snapshot,
  dump and locked source recheck. No live insert/update/delete/DDL was performed.
- After the exact restored snapshot passed, two controlled inserts into the
  disposable restored wp_options generated **8905** and **8906**. Both exceeded
  all existing IDs and were distinct. Each transaction was rolled back. All 61
  restored row counts/data hashes remained unchanged; the disposable counter
  advanced to **8907**, illustrating normal non-reuse after rollback. This was
  an intentional post-verification rehearsal side effect, not a backup defect.
- Disposable database removal and absence verified; temporary client credentials
  removed. Dump SHA-256 rechecked. Final source snapshot still matches the fresh
  baseline exactly. The Papi Jo profile table remains absent on acceptance.example.test.
- All **903 protected file hashes** still match the prior verified baseline.

Key evidence: `counter-investigation.json`, `locked-database-baseline.json`,
`restored-snapshot.json`, `source-counter-validation.json`,
`restored-counter-validation.json`, `backup-verified.json`,
`verifier-negative-controls.json` and `final-verification.json`. Helpers and dump
stay outside the web root and are not deployment payloads. The older failed dump
has not been relabeled as the newly certified backup.

## Remaining risks and resumption decision

The backup verification blocker is resolved. The backup was verified against the
source state at this rehearsal, not against future independent activity. Keep it
private: it contains the full site's data. Before a later deployment, recheck the
backup hash and source-state drift and obtain a new backup if needed. Source
consistency here uses table read locks and assumes no independently concurrent DDL;
future schema drift still requires stopping/review.

**Deployment can safely be considered for resumption after new explicit user
authorization and fresh preflight checks. It has not resumed.** No plugin files,
live schema, profile preferences, real users, SNORDIAN, H5P content or HTTPS
configuration changed. No commit/tag/push/publication occurred. Profile test writes
remain separately unauthorized. Stop here awaiting deployment authorization.

## Final authorized resumption — deployment and verification PASS

The user explicitly authorized resumption after the backup investigation, including
deployment and creation of only the dedicated InnoDB profile-state table. No
profile test writes or learner-account creation were authorized or performed.

### Preflight and backup

- Certified fresh backup remains available; SHA-256 rechecked as
  `PRIVATE_VERIFICATION_DIGEST_OMITTED`.
  It is the 15,304,115-byte `acceptance.example.test-consistent.sql` under
  `PRIVATE_EVIDENCE_OMITTED`, whose 61-table restore
  rehearsal and cleanup passed as documented above.
- Before installation, the current source database still exactly matched that
  backup baseline: all structures/counts/checksums and fresh counters. No new
  backup was needed because no drift was detected.
- All 43 deployed files matched the accepted Phase 1C-D baseline. All ten planned
  source files matched the previously recorded release hashes, with no intervening
  source difference. Source and deployed plugin version headers remain **0.1.0**.
- Native preflight passed 27 checks; H5P and SNORDIAN active, Papi Jo suspended,
  prefix **wp_**, zero warnings/attempted writes. Five replaced files were freshly
  backed up and hash-verified outside the web root.

### Exact file deployment

The existing/new file table earlier in this report is the exact deployed list:
five replacements (admin view, diagnostics, composition root, main entry, readme)
and five new files (four Profile classes plus includes/profiles.php).

Destination:
`PROJECT_ROOT`.
Each file was staged beside its target, hash-checked and replaced. Definitions
preceded the loader, main entry preceded updated composition/hook registration,
so the changed composition did not run without its class definitions. No tests,
private helpers or documentation reports were copied into WordPress.

All deployed hashes match source. All nine deployed PHP files pass syntax checks
on **PHP 7.4.33, 8.3.18 and 8.4.22** (27 checks). A full acceptance.example.test file audit found
8,730 files before and 8,735 after, with exactly the ten planned added/changed
paths and no unrelated file difference. The plugin now contains 48 files.

### Actual schema installation and database inventory

Called the deployed `Profile_Store::install()` once in a trusted native CLI
administrator context. Its exact CREATE statement was allowlisted for this
operation only; other non-read SQL through wpdb remained vetoed. No activation
cycle or automatic migration ran. Installer verification passed.

Actual server DDL:

```sql
CREATE TABLE wp_pjh5pwp_profiles (
  user_id bigint unsigned NOT NULL,
  schema_version int unsigned NOT NULL,
  revision bigint unsigned NOT NULL,
  state longblob NOT NULL,
  PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

The primary key enforces one row per user. The expected four column types,
nullability, revision/schema columns, single unique primary index, absence of
triggers and InnoDB engine were checked against actual database metadata. This
was schema inspection, not a test insert into a real user's state.

After installation: **62 tables**, exactly one added table,
**wp_pjh5pwp_profiles**, **zero rows**. No table removed. All **61 original tables**
match their predeployment structures, row counts and data checksums, including
options, users/usermeta and all H5P content/results/library records. No original
table altered, no user grants/preferences enabled, no SNORDIAN option/activation
or WordPress URL changed. The predeployment backup intentionally lacks the new
empty table; restoring it would revert the whole site and is not a routine rollback.

### Native compatibility and profile checks

Installation process passed **41 checks**. A separate fresh process passed **39
read-only checks**, with no PHP warnings or blocked write attempts:

- WordPress **7.1.4**, PHP **8.4.22**; Papi Jo remains active.
- Official H5P **1.17.10**, Core API **1.28** detected/supported.
- SNORDIAN **0.0.12** active; priority-10 runtime callback and original script
  paths intact. Separate menus and theme previews render. Papi Jo runtime remains
  suspended, disabled and unimplemented.
- Registered profile hooks found: show_user_profile, edit_user_profile,
  user_profile_update_errors and profile_update, owned by Profile_Controller.
- Read-only inspection of the **two existing users** found no profile-state record
  for either. Service defaults deny both permissions, use global theme and original
  Density. Own/edit profile hook rendering exposes no personal theme/Density
  selector without grants. Rendering creates no row; table remains empty.

These are native CLI/API/markup checks, not authenticated browser submissions or
live persistence/concurrency tests. Those behaviors were tested in disposable
databases during source verification; they have not been exercised on real users.

### Evidence and rollback

New private evidence/file-backup directory:

```text
PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED
```

Artifacts include deployment-plan.json (exact paths and old/new hashes),
files-backup, files-before.json, file-audit.json, native-readonly.json,
installation-profile-verification.json, fresh-profile-verification.json,
database-after.json and database-audit.json. Helpers are outside the web root.
No unexpected change or rollback occurred.

If rollback becomes necessary, stop profile writes and restore the coherent
pre-profile release using the five verified backups. Restore the old composition
root before the main entry, then remove only the five newly added profile files
once no requests depend on them. Verify the old 43-file baseline. Preserve the
new table and any later data for review; **do not drop it or restore the whole
database without separate authorization**. Never revert to unsafe four-key profile
writers. File rollback does not undo independently saved core profile fields.

### Remaining limits and ordered Firefox acceptance

No known deployment error remains. Native profile forms, revision CAS and runtime
suspension are installed; real browser layout/session/nonce transport and profile
round trips remain untested here. No real network-loss or alternate-DB qualification
is added. User deletion/orphan cleanup remains a documented source limitation.
There is still no H5P runtime palette or Density application.

Current authorization permits the following **read-only** Firefox recheck:

1. Open https://acceptance.example.test/wp-admin/ in normal Firefox and sign in normally.
2. Open H5P Content → Papi Jo H5P Themes. Confirm catalog, existing previews,
   refinement navigation and SNORDIAN suspension diagnostics remain normal.
3. Open Users → Edit for an existing other user **without saving**. Confirm both
   independent permission boxes are present and unchecked.
4. Open the administrator's own Profile **without saving**. Confirm no unauthorized
   personal H5P theme or Density selector appears.
5. Inspect SNORDIAN settings without saving and an existing H5P activity. Confirm
   its appearance/behavior remains as before; report any warnings or errors.

**Do not click Update User/Update Profile, change permission values, create learner
accounts or submit preference tests yet.** Tests of all four combinations, actual
theme/Density saves, stale tabs and revocation require explicit additional profile
test-write authorization. No further development is started. Deployment verification
stops here awaiting Firefox results and further authorization.
