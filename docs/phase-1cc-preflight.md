# Phase 1C-C — administrator acceptance preflight

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Date: 7 November 2026. **Status: stopped at the explicit authorization gate.**
No plugin has been deployed or activated. No site data, table engine, WordPress
configuration, H5P configuration or SNORDIAN configuration has been changed.
No live WordPress request/bootstrap was used for this preflight.

## Finding that blocks full acceptance

**`acceptance.example.test.wp_options` uses MyISAM.** The existing production adapter expressly
requires InnoDB before it reads/initializes the theme aggregate. Its
`WordPress_Snapshot_Store::open()` will reject this backend as
`storage_environment_unsupported`; it does not silently weaken the atomicity
contract or convert tables. Therefore activation/diagnostic/fail-closed checks
could be tested after approval, but full theme catalog management, initialization,
CRUD, publication and default-save acceptance cannot pass on the unchanged backend.

This is a newly verified local-environment incompatibility, not a claim that the
previous disposable InnoDB tests failed. No adapter change or workaround has been
implemented. The other 60 tables need not be converted for this plugin's storage
adapter; its aggregate uses only wp_options.

## Verified installation/database findings

Read all Phase 1A, 1B, 1C-A and 1C-B verification reports, administration.md and
the storage contract. The authoritative Backdrop worktree HEAD still equals
`d9b53d706a94dac322b89885bf668145ab3803c4` (v0.5.0); source remains read-only.

| Item | Verified result |
| --- | --- |
| Test installation | `WORDPRESS_ROOT` |
| Configured canonical home/site URL | `https://acceptance.example.test` |
| WordPress source / stored DB revision | 7.1.4 / 61833, matching |
| Apache configuration | Local HTTP and HTTPS acceptance.example.test virtual hosts; PHP module points to PHP 8.4.22 |
| Actual web PHP/session behavior | Not requested yet; remains for authorized browser acceptance |
| Database / prefix / server | acceptance.example.test / wp_ / MySQL 9.1.0 |
| Database tables | 61 base tables, all MyISAM |
| wp_options | 308 rows; utf8mb4_unicode_520_ci; LONGTEXT value; auto-increment BIGINT primary ID; full unique option_name index; no visible triggers |
| Routines / events | None in this database |
| Official H5P | Installed and active, 1.17.10; stored H5P version matches; source Core API 1.28 |
| SNORDIAN H5P Themer | Installed and active, 0.0.12; stored version matches |
| Existing Papi Jo Themer deployment | No canonical target directory, matching PHP namespace/prefix, activation entry, pjh5pwp options or user metadata found |
| Other Papi Jo components | Package Converter 2.4.0, Usage Report 1.0.4 and Papi Jo H5P libraries; these are independent and must remain intact |
| Other active plugins | Classic Widgets 0.3; Login Logout Menu 1.5.2; User Role Editor 4.67 |
| Active theme | Twenty Twenty-Five |
| Must-use plugins / object-cache or DB drop-ins | No mu-plugins directory or wp-content drop-in files observed |
| Accounts | Two existing users; at least one administrator; role assignments include tutor_instructor/subscriber. No credentials reset, account or role changes made |
| Administrator capabilities | manage_options, activate_plugins, install_plugins, view_h5p_contents and manage_h5p_libraries present in stored administrator role |
| Site files | 8,687 files, 241,338,906 bytes; no links/junctions observed |

Database inspection used mysqli with `SET SESSION TRANSACTION READ ONLY` and a
read-only transaction, then SELECT/SHOW queries only, ending in ROLLBACK. Literal
credentials were parsed without executing wp-config and never printed. MyISAM
does not provide a transactional snapshot, so the transaction's read-only flag
is not presented as a consistency guarantee for these tables. Logical schema/data
fingerprints of all 61 tables matched before and after the export/preflight window:

`PRIVATE_VERIFICATION_DIGEST_OMITTED`

This records observed stability, not proof that another process could never write.
No WordPress startup, web-page visit, plugin init callback or wp-cron invocation
was used. Avoiding startup matters: even ordinary WordPress requests can perform
housekeeping, while SNORDIAN's init can upgrade its own stored version if needed.
Its inspected stored/current versions presently match.

## H5P and SNORDIAN baselines

The database contains 15 H5P content records and 121 libraries. Proposed playback
fixtures, after approval: content 2 (official H5P.TrueFalse 1.8.21), content 8
(official H5P.InteractiveBook 1.15.1), and content 11 (H5P.InteractiveBookPapiJo
1.17.0). They must be viewed without modifying authored activities or answers.

SNORDIAN's settings page is `options-general.php?page=snordiansh5pthemer-admin`,
separate from our `pjh5pwp-themes` page. Its runtime callback is
`Snordian\H5PThemer\h5p_themer_alter_scripts`, registered at priority 10. It appends
its configuration and runtime scripts. This source was inspected only for
compatibility; no code was used as a basis for our plugin.

Recorded baseline:

- `snordiansh5pthemer_option`: 3,063 bytes; SHA-256
  `PRIVATE_VERIFICATION_DIGEST_OMITTED`.
- Its stored picker summary is Daylight, Density large, 34 color entries.
- `snordiansh5pthemer_version`: 0.0.12.
- `h5p-themer-config.js`: 1,744 bytes; SHA-256
  `PRIVATE_VERIFICATION_DIGEST_OMITTED`.

Runtime suspension after actual activation is **not yet observed**. It is expected
from the verified active record and unchanged policy. Current PHP 8.4 checks pass:
18 foundation scenarios / 441 assertions, including installed H5P source probing,
and four isolated SNORDIAN signals / 36 additional coexistence assertions. These
are isolated checks, not live coexistence or playback certification.

## Backup and rollback readiness

Private artifacts are outside both CMS installations:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

They include configuration secrets and database/user data. They are private backup
material, excluded from the deployment whitelist; do not deploy or publish them.
The temporary client credential file used for mysqldump was removed.

| Artifact | Verification |
| --- | --- |
| `acceptance.example.test-files.zip` | 117,413,543 bytes; all 8,687 file paths, CRCs and per-entry SHA-256 hashes verified; source inventory stable during backup |
| File archive SHA-256 | `PRIVATE_VERIFICATION_DIGEST_OMITTED` |
| `acceptance.example.test-database.sql` | 15,903,932 bytes; mysqldump success, no stderr, 61 CREATE TABLE statements and dump-completion marker |
| SQL export SHA-256 | `PRIVATE_VERIFICATION_DIGEST_OMITTED` |
| Database consistency status | Provisional unlocked export; before/after logical fingerprints match, but not a locked/transactional guarantee |
| Restore rehearsal | Not performed: creating/restoring database records awaits authorization |
| Baselines | Full site file manifests before/after, database schema/data fingerprints, option hashes, PHP/Apache log offsets/hashes and release manifest |

The file backup is verified. The SQL export is not claimed to be a fully rehearsed
recovery snapshot. Before an approved engine conversion/deployment, refresh the
backups in an agreed quiet window. Hold READ locks on the known acceptance.example.test tables
through the export if a guaranteed fixed MyISAM dataset is required; do not use a
server-wide lock affecting other CMS databases. Recheck the table inventory and
validate a disposable restore if that preparation is approved. No locks or restore
operations were taken before this gate. MySQL documents the engine limitations of
[mysqldump consistency options](https://dev.mysql.com/doc/refman/9.1/en/mysqldump.html).

Proposed rollback, not executed:

1. Stop acceptance writes and capture any unexpected state/logs. Deactivate only
   our plugin through WordPress if administration is healthy. Preserve the seven
   existing active plugin entries and all SNORDIAN configuration.
2. If a fatal prevents administration, quarantine only the newly deployed plugin
   directory outside the web root, after validating the exact resolved path. If
   required, remove only its exact activation-array entry through a controlled
   recovery procedure. Do not deactivate all plugins or alter SNORDIAN callbacks.
3. Export test theme data before removing it. In an authorized cleanup, remove
   only our newly created aggregate option, `pjh5pwp_theme_library`; there is no
   plugin uninstall hook to rely on. Do not blindly restore the whole options table
   over intervening unrelated changes.
4. If a wp_options engine conversion was approved, record its original MyISAM
   definition and table data. Reverting that conversion or restoring the original
   options table/database needs an agreed quiet state, preservation comparison
   and explicit recovery authorization. It is not an automatic error response.
5. A full site/database restore is a last-resort, separately reviewed operation:
   it would discard changes since the backup. Check live state first. Verify
   restored files/data against manifests, existing activation entries and SNORDIAN
   option/config hashes before reopening the site.

## Exact proposed deployment method

An ordinary independent directory copy is prepared; **no symlink or junction**.
The project is in OneDrive, so a direct runtime link would make unfinished source,
tests and private backups reachable from the site and permit accidental live edits.

Prepared source:

`...\PRIVATE_EVIDENCE_OMITTED`

Exact destination, after approval only:

`PROJECT_ROOT`

The staged payload is **43 files / 195,965 bytes**, with **34 PHP files** passing
PHP 8.4.22 syntax checks. Its full relative-path/content manifest was verified
against the project. Manifest digest:

`PRIVATE_VERIFICATION_DIGEST_OMITTED`

Whitelist: main plugin PHP, readme.txt, LICENSE, THIRD-PARTY-NOTICES.md, and the
complete includes/, assets/, presets/, licenses/ and languages/ directories.
No tests/, docs/, private audit/backup artifacts, .git, or external plugin code
is included. The prepared directory is not inside acceptance.example.test and has not been copied
there. Before copying, require the destination to remain absent, reject reparse
points, recheck the release hashes and refuse any overwrite/merge with old files.

After the backend decision/approval, copy those exact bytes, compare deployed
hashes, then activate only this basename through the normal authenticated Plugins
screen: `papijo-h5p-themer-wordpress/papijo-h5p-themer-wordpress.php`. Do not bulk
activate, change other plugin configurations or run an application schema migration.
No historical plugin migration is needed because the aggregate is absent.

Our activation handler performs only compatibility evaluation. WordPress itself
does write activation state, so activation is a separately authorized database
change even though our handler creates no theme option. This follows the inspected
installed source and [WordPress activation API](https://developer.wordpress.org/reference/functions/activate_plugin/).
Initialize storage only in the later explicitly approved administration test step.

## Risks and decision required

| Risk / limit | Planned handling |
| --- | --- |
| MyISAM options backend | Full acceptance blocked until a separate, explicit backend preparation decision; never bypass the adapter's check |
| Shared wp_options engine conversion | Affects a table used by every plugin; take a consistent fresh backup, allow a short write-blocking window, verify unchanged logical rows/indexes/collation afterward, and leave all other tables untouched |
| Backup consistency | Unlocked MyISAM export is provisional; refresh/rehearse under the approved recovery plan before mutation |
| New plugin execution/fatal | Linted staged payload, controlled single activation, before/after logs, exact-directory quarantine available |
| Diagnostics | Existing php_error.log is 4,286 bytes; apache_error.log 7,220 bytes. Historical errors exist, including old CLI test failures; only newly appended entries during approved tests count as new results |
| Existing plugins/bootstrap housekeeping | Real requests may update sessions/transients/cron/H5P counters or caches. Record and distinguish these from theme writes; do not promise a globally immutable database during browser tests |
| SNORDIAN dual theming | Keep it active/configuration unchanged; verify our suspension and absence of our playback assets. Do not test by disabling SNORDIAN without separate approval |
| Accounts/certificates | Need an authenticated existing administrator session and an authorized nonadministrator session for security tests. No password resets, account creation, role edits or TLS trust/configuration changes are included |
| Browser coverage | No actual Firefox, live WordPress nonce or two-session concurrent-write acceptance yet |

Recommended route **if retaining the requested acceptance.example.test target**: explicitly approve
fresh consistent backup preparation, conversion of **only wp_options** to InnoDB,
verification of preserved existing values/schema details, the 43-file deployment,
single activation and controlled administrator test writes. The other 60 tables
and all H5P/SNORDIAN configurations remain untouched.

Alternatives: approve limited activation/fail-closed checks on the current backend,
accepting that CRUD/publication/default acceptance remains blocked; or separately
authorize a disposable clone with an InnoDB options table for full acceptance while
leaving acceptance.example.test untouched. No clone, engine conversion, plugin-owned replacement
table, adapter redesign or deployment is implied by this report.

## Acceptance results and planned execution

| Area | Current status |
| --- | --- |
| Local source/database/file preflight | Complete, read-only; blocker recorded |
| Backup/staging preparation | File backup and 43-file payload verified; SQL export provisional as qualified above |
| Activation, dependency UI and actual suspension | NOT RUN — approval gate |
| Live theme catalog/forms/previews | NOT RUN — approval gate and MyISAM blocker |
| Real capabilities/nonces/revision/concurrent saves | NOT RUN — approval gate and MyISAM blocker |
| Live SNORDIAN administration/runtime coexistence | NOT RUN — approval gate; baseline captured |
| Official/Papi Jo H5P playback comparison | NOT RUN — approval gate; fixtures identified |
| Firefox manual acceptance | Checklist prepared; NOT RUN |

After approval/backend readiness: record baselines, activate, confirm no fatal/new
warnings and no theme aggregate created by activation/GET, inspect dependency and
SNORDIAN warnings, and confirm runtime remains suspended. Then explicitly initialize
and exercise immutable views/copies, custom CRUD/rename, sparse clears/invalid input,
native pickers/groups, saved/live/reset previews, publication transitions, independent
default eligibility and protected deletion. Use clearly named acceptance themes.

Use two administrator tabs to test stale Edit/Add/visibility/default/delete and
concurrent saves. Test unauthorized requests with an existing authorized test
account, and invalid/missing nonces without changing other users/roles. Rejected
requests must preserve data and never silently rebase submitted edits. Compare
SNORDIAN page/script/config/option baselines and official/Papi Jo playback before
and after, with no activity-level theme/Density controls. Do not interact with
answers or scores merely to test styling. Record normal request/cache changes.

## Concise Firefox checklist for the user

**For after deployment/backend readiness and separate authorization only.**

1. Open H5P → Papi Jo H5P Themes. Confirm catalog, five built-ins, original Core
   choice, SNORDIAN warning and separate Settings → H5P Themer page.
2. View built-ins; confirm no Edit/Delete. Duplicate one, save it, then edit its
   custom copy; confirm it starts unpublished and shows the pinned preset.
3. Expand all five color groups using keyboard and mouse. Check native color
   swatches, #RGB/#RRGGBB text, blank inheritance and invalid-color messages.
4. On custom Edit, change valid colors, enter an invalid color, clear an override
   and press Reset. Confirm saved/live/Core previews, last-valid retention and no
   Density preview/control. Repeat text editing with JavaScript disabled if desired.
5. Rename by Name; publish/unpublish explicitly. Select an unpublished custom as
   global default; deleting it must be blocked. Change the default, then confirm
   deletion of a separate acceptance theme.
6. In two tabs edit the same theme: save one, then submit the older one. Expect a
   conflict, copyable edits and no silent overwrite. Refresh after a successful
   save; it must not repeat the POST.
7. Check narrow layout, labels, keyboard focus, error/preview announcements and
   browser console/PHP logs for new problems.
8. Verify SNORDIAN's administration and existing theme/Density still look the same
   on the agreed official and Papi Jo activities, with no new activity selectors.
   Report each step PASS/FAIL with Firefox version and any reproduction details.

## Safety evidence and stopping point

All 8,687 WordPress installation file paths/sizes/SHA-256 values matched the new
preflight baseline afterward. The seven prior protected targets also match Phase
1C-B: **860 protected files unchanged**, covering both Backdrop source trees,
deployed Backdrop Themer/active config, all installed WordPress plugins and both
CMS settings files. Full database schema/content fingerprints matched before/after.

Only this preflight report and private local backup/audit/staging artifacts were
created. Production plugin source was not changed. No HTTP acceptance, live
installation, activation, migration, configuration change, option/data write,
commit, tag, push, publication, learner-profile or runtime development occurred.

**Await explicit approval and a backend route decision. Do not proceed automatically.**
