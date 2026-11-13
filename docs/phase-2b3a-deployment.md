# Phase 2B-3A — Controlled Dormant Deployment

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Authorized local deployment COMPLETE. Automated deployment
verification PASS. User Firefox acceptance PENDING.** Runtime remains disabled;
SNORDIAN remains active. No live database/schema, WordPress configuration,
activation, role grant or learner preference changes occurred. Phase 2B-3B was
not performed and remains separately unauthorized.

## A–B. Preflight and exact installed path

Read the Phase 2B-2E deployment plan, Phase 2B-2D/2C verification, controller,
transport and appearance contracts, and earlier deployment/backup implementation.
Compared the actual source and installed state rather than reusing old assumptions.

The uniquely discovered and natively loaded plugin entry is:

`PROJECT_ROOT/papijo-h5p-themer-wordpress.php`

Installed directory:

`PROJECT_ROOT/`

Preflight established:

| Item | Observed state, preserved after deployment |
| --- | --- |
| Site | HTTPS static asset returns 200; native WordPress administration and frontend rendering load |
| Versions | WordPress 7.1.4, H5P 1.17.10, Core API 1.28, SNORDIAN 0.0.12; native runner PHP 8.4.22 |
| Activation | Papi Jo, official H5P and SNORDIAN active; all eight active-plugin entries unchanged |
| Catalog | Revision 31, five immutable built-ins, no saved custom definitions |
| Global Theme | Stored null selection, resolving to H5P Core |
| Roles | Seven registered roles; saved role configuration revision 3 with no nonautomatic grants |
| Profiles | Existing InnoDB `wp_pjh5pwp_profiles`, two records; exact values/revisions preserved |
| Content | 15 existing H5P activities; four published H5P pages selected for guarded HTTP checks |
| Runtime | `PJH5PWP_ENABLE_BROWSER_THEME` undefined; no browser attachment hooks or asset |

The two profile records and saved role option legitimately differ from the older
Phase 2A-R deployment report. They were inventoried as current state, backed up
and preserved. No records or defaults were reset to match historical evidence.

HTTPS chain/hostname verification succeeded using installed OpenSSL with the
existing public Wamp CA and `-verify_hostname acceptance.example.test -verify_return_error`.
No browser exception, trust store, certificate, host mapping or TLS/site setting
was changed. Python's stricter default verifier rejected the legacy local CA;
that diagnostic failure was not treated as an application failure or repaired.

## C. Backup locations and verification

Timestamped recovery directory, outside the web root:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

- `files-backup/`: complete original **53-file** installed Papi Jo directory,
  independently hash-verified against the installed inventory.
- `file-acls-before.json`: original Windows file permissions for all 53 files;
  existing-file ACLs remain unchanged after deployment.
- `file-restore-rehearsal/`: disposable reconstruction of all 53 original plugin
  files; every restored hash matches the original inventory.
- `acceptance.example.test-consistent.sql`: fresh consistent database backup, **15,937,230 bytes**.
- Dump SHA-256:
  `PRIVATE_VERIFICATION_DIGEST_OMITTED`.

All **62 tables / 5,855 rows**, structural metadata, row counts and sorted row-data
checksums matched a disposable restore. Ten verifier negative controls passed.
Source counters were validated; insert/rollback probes ran only in the disposable
restored options table. Its database was dropped and absence verified. Temporary
client credentials were removed. Source tables received temporary READ locks for
the consistent mixed-engine backup; no source DML/DDL was executed. Raw restored
DDL notation differed for 57 tables, while complete structural metadata, counters
and data comparison passed; this is not a migration or schema change.

Activation/options/configuration inventories and original hashes are retained
alongside the backup. The SQL and private evidence contain site data and must stay
outside the web root and out of publication. No database restore was applied to
acceptance.example.test.

## D–F. Inventories, exact deployment and hashes

The **133-file** source snapshot exactly matched Phase 2B-2E's final qualification
hashes before deployment. The production/licensing allowlist contains **63 paths**
in [phase-2b2e-deploy-files.txt](phase-2b2e-deploy-files.txt). The installed plugin
started with 53 files, all reconcilable to that allowlist, with no extra files.

Only these **13 differing files** were copied. Paths are relative to the installed
directory above:

| File | Operation |
| --- | --- |
| `assets/js/theme-controller.js` | Added |
| `includes/runtime.php` | Added |
| `includes/runtime-wordpress.php` | Added |
| `includes/Runtime/class-appearance-service.php` | Added |
| `includes/Runtime/class-css-serializer.php` | Added |
| `includes/Runtime/class-runtime-admission.php` | Added |
| `includes/Runtime/class-wordpress-context.php` | Added |
| `includes/Runtime/class-playback-registry.php` | Added |
| `includes/Runtime/class-appearance-endpoint.php` | Added |
| `includes/Runtime/class-theme-attachment.php` | Added |
| `papijo-h5p-themer-wordpress.php` | Replaced |
| `includes/class-plugin.php` | Replaced |
| `readme.txt` | Replaced |

The other **50 installed files** were already identical and were left untouched.
No source tests, docs, helper, dump, fixture, local artifact or development file
entered the web root. No plugin or table was reinstalled; no activation cycle ran.

The script checked each resolved destination stayed inside the discovered plugin
directory and rechecked all source/old-destination/backup hashes immediately before
copying. A temporary standard WordPress `.maintenance` marker guarded the coherent
file replacement; it was removed afterward. No Core source or persistent
configuration was changed. New definitions/loaders were staged first, bootstrap
before the composition root, using per-file atomic replacements. Failure handling
could restore the exact prior files automatically.

Final installed inventory: **63 files**. Every payload path matches the qualified
source SHA-256; there are no unexpected additions or removals. All **53 installed
PHP files** pass PHP 8.4.22 syntax and both installed JavaScript files pass Node
syntax. Backup and payload hashes were rechecked after deployment.

The actual Apache HTTPS server returns the new static controller with bytes equal
to the qualified source, SHA-256:

`PRIVATE_VERIFICATION_DIGEST_OMITTED`.

Static asset ETag/Last-Modified are expected for this nonpersonal file. They are
absent from the appearance response. No runtime activation or cache/settings
write was needed. The controller URL was newly introduced by this deployment.

## G. Database, schema and configuration comparison

**Zero persistent database changes.** After native checks, all 62 table structures,
row counts, data checksums and AUTO_INCREMENT metadata match the fresh preflight
snapshot. No table changed, appeared or disappeared. This includes all options,
users/usermeta, profiles/revisions, H5P content/dependency/cache records and
SNORDIAN-related data. The original two profile rows remain present.

Native service-level hashes independently match for the full theme library, role
configuration and each existing user's profile. The global Core default, catalog
revision 31 and role revision 3 remain unchanged. WordPress configuration bytes,
activation records, SNORDIAN settings and unrelated plugin files are unchanged.
No initialize, migration, profile save, role save or theme save was performed.

## H–I. Disabled runtime and SNORDIAN coexistence

The disabled guarantee passed at independent boundaries:

1. Native WordPress bootstrap finds the activation constant undefined, as before.
2. Default-disabled attachment registers neither its footer nor script-tag hook.
3. Native policy reports `runtime_suspended=true`, `future_runtime_permitted=false`
   and `runtime_enabled=false`, with blocker `snordian_active`.
4. All four SNORDIAN signals remain present: activation record, version constant,
   loaded namespace and original priority-10 runtime callback.
5. Four existing frontend pages contain H5P integration/players and SNORDIAN assets,
   with no Papi Jo controller tag, manifest or browser-controller attachment.
6. The registered endpoint returns no actionable descriptor for anonymous and all
   three current users in native process-local current-user checks.
7. A full installed WordPress `admin-ajax.php` HTTP dispatch returns status 200 with
   reason `snordian_active`, null Theme/Density and empty color declarations.

Only the dormant registry and private endpoint hooks were added. No Papi Jo runtime
callback was added to H5P's dependency asset actions. Original SNORDIAN script paths
and native renderer integration remain the same before/after. Papi Jo does not
apply Theme values or Density classes while this gate is disabled. Visual styling
and browser-executed playback remain part of the user's Firefox checklist.

## J. Native and automated post-deployment checks

**Native read-only administration:** 43 preflight and **56 post-deployment checks
PASS**. They load the installed plugin through WordPress, render its registered
administration page, all five immutable previews and role permissions, and inspect
own-Profile controls for each of the three existing users. The administrator sees
both selectors through automatic authorization; other users follow current denial.
Rendering preserves stored records. No login session or persistent current-user
change was created; user selection was process-local.

**Guarded native HTTP:** existing published page IDs **400, 403, 407 and 409** return
200 after following their existing canonical URLs. Their H5PIntegration and library
script/style lists match before/after, normalizing only SNORDIAN's existing
per-request `buster` value. No Papi Jo preference metadata or controller attachment
is introduced. These checks render actual installed WordPress content/templates;
they do not execute H5P playback JavaScript or establish visual acceptance.

HTTP rendering uses a loopback-only runner outside the web root with a query guard
that blocks all SQL mutations, process-local cron/update/external-request guards,
and H5P's existing aggregation opt-out confined to that test process. This prevents
cache generation during smoke tests; **the site's aggregation configuration was
not changed**. Full default-aggregation Apache/browser playback is still a manual
acceptance item. Direct HTTPS checks cover static site accessibility and the served
controller, not authenticated Apache administration or dynamic endpoint dispatch.

The native AJAX response carries all five required cache directives:
`no-cache, must-revalidate, max-age=0, no-store, private`, plus CDN/proxy no-store,
Cookie/Origin variation, no-cache Pragma, expired Expires, nosniff and same-origin
resource policy. No CORS grant, ETag or Last-Modified is returned. WordPress's final
AJAX death handler reorders the same directives; the test checks their exact set.

The full qualified source matrix was rerun before deployment:

| Verification | Result |
| --- | --- |
| PHP 7.4.33 | 5,400 assertions PASS; 87 source/test PHP syntax checks PASS |
| PHP 8.3.18 | 5,400 assertions PASS; 87 syntax checks PASS |
| PHP 8.4.22 | 5,400 assertions PASS; 87 syntax checks PASS |
| JavaScript administration/Theme/Density/lifecycle | 516 assertions PASS |
| Existing static Edge administration fixtures | 24 browser assertions PASS |
| HTTP authentication/cache/CORS fixtures | 198 assertions per PHP version, included above |
| Disposable MySQL 9.1.0 | Nine suite runs PASS; all disposable databases removed |
| Installed payload syntax | 53 PHP + 2 JavaScript files PASS |
| Database restore and file rollback rehearsals | PASS |

No automated runtime Firefox acceptance is claimed. Error-log comparison found no
new Apache/application fatal errors. One PHP startup warning came from the first
helper command redundantly requesting already-loaded mysqli; the invocation was
corrected. Final native runs report no PHP warnings or unexpected mutation attempts.
Expired WordPress theme-pattern-cache maintenance attempts were blocked and logged,
not executed; no Papi Jo write was attempted.

## K. Protected-file integrity

The complete acceptance.example.test filesystem comparison finds exactly the three intended plugin
replacements and ten additions above, with no removals or unrelated final changes.
All **8,687 pre-existing files outside Papi Jo**, including Core, official H5P,
H5P PHP/JS Core, SNORDIAN, uploads and wp-config, retain their baseline hashes.
All **155 authoritative Backdrop files** are unchanged, with no additions/removals.

Thus **8,842 protected/reference files remain unchanged**. WordPress's final file
count is 8,750, up from 8,740 solely because of the ten Papi Jo additions. Protected
source code was never edited. The temporary maintenance marker is absent.

## L. Problems encountered and corrections

- Restricted-token network/database access required the normal-process execution
  used for the authorized local checks. No production check was weakened.
- The native preflight encountered expired WordPress theme-pattern transients.
  The SQL guard blocked their housekeeping writes. These known Core cache attempts
  are reported separately; all unexpected writes still fail verification.
- The first guarded frontend renderer blocked SQL but did not prevent H5P from
  generating two missing aggregate cache files. Both exact paths were absent in
  the baseline; diagnostic copies were retained outside the web root and the two
  additions were removed. A full filesystem audit proved the original baseline
  restored **before deployment**. Subsequent smoke tests use the existing
  process-local aggregation opt-out. No protected code or live database was changed.
- Existing canonical redirects and JSON-escaped iframe asset URLs required helper
  handling; SNORDIAN's per-request buster is normalized for comparisons only.
- An initially byte-exact Cache-Control assertion failed because real WordPress
  reorders the same five directives at `wp_die()`. Source inspection and the final
  HTTP capture confirmed identical policy. The helper now checks the exact directive
  set, including private/no-store; no production change or rollback was required.

No unresolved critical deployment failure remains. No unrelated repair was made.

## M. Rollback readiness

Rollback was rehearsed locally and remains available; it was **not executed on
the installed plugin** because final deployment checks passed.

1. Confirm no subsequent changes against `plan.json` and the final inventory.
2. Use a temporary standard maintenance gate while restoring a coherent release.
3. Restore the three replaced files from `files-backup/`, coordinating the old
   bootstrap/composition root before removing now-unused definitions.
4. Remove only the ten previously absent additions listed above, after verifying
   their paths and current hashes. Preserve any unexpected/newly changed file for
   investigation. Restore all 53 original hashes and original permissions.
5. Verify PHP/JS syntax, native administration/H5P rendering, absent runtime gate,
   active SNORDIAN, protected hashes and unchanged database, then remove the gate.

Preserve the live database, profile table, saved role option and learner preferences.
No database restore is needed for this file-only deployment. A database recovery or
configuration repair would require a demonstrated problem and separate scope.

## N. User Firefox acceptance — NOT YET TESTED

View-only checks; do not save themes, roles, profiles or SNORDIAN settings.

1. Open [Papi Jo Themes](https://acceptance.example.test/wp-admin/admin.php?page=pjh5pwp-themes).
   Confirm the page loads and the suspension/compatibility information is sensible.
2. Check the five built-in Themes and previews; confirm the global default is still
   H5P Core and no catalog item has disappeared.
3. Inspect Role permissions without saving: Administrator is allowed automatically,
   and no other role is granted.
4. Open your own [Profile](https://acceptance.example.test/wp-admin/profile.php). Inspect the Theme
   and Density selectors and their existing selections without updating the profile.
5. Open existing [H5P content](https://acceptance.example.test/?page_id=400) and other familiar
   activities. Check interaction, iframe sizing and normal playback.
6. Confirm familiar SNORDIAN styling remains unchanged. Keep SNORDIAN active and
   Papi Jo runtime disabled; no Papi Jo controller request should appear in Network.
7. Inspect the Firefox console for unexpected errors and report the results.

## O–P. Limits and next recommendation

The dormant deployment is complete and has no remaining automated blocker. User
Firefox playback, visuals, default aggregation/cache behavior, console checks and
actual session/profile UI acceptance remain pending. Native guarded rendering is
not a substitute for those observations. No learner preference round-trip or
live data-write test was performed.

The inherited aggregate runtime diagnostic fields and transient restoration policy
documented in Phase 2B-2E remain unchanged. They do not enable runtime. Any later
runtime trial must assess real browser/cache/session behavior and those limitations.

**Stop and wait for the user's Phase 2B-3A Firefox acceptance.** After that, recommend
a separately scoped Phase 2B-3B proposal. Temporary SNORDIAN deactivation, Papi Jo
runtime activation, configuration changes and preference test writes each require
explicit authorization; none is implied by this deployment or its acceptance.

Evidence is retained in the timestamped recovery directory: `inventory.json`,
`source-before.json`, `installed-before.json`, `plan.json`, `files-before.json`,
`backdrop-before.json`, `backup-verified.json`, restore/schema/counter snapshots,
`file-restore-verified.json`, `native-admin-pre/post.json`, `http-pre/post.json`,
`http-appearance.json`, `native-state-comparison.json`, `database-pre/post.json`,
`database-comparison.json`, `regressions.json`, `javascript.json`,
`file-audit-predeploy/postdeploy.json`, `file-acl-comparison.json`,
`served-controller.json`, `error-log-audit.json` and `final-verification.json`.

**Runtime remains disabled; SNORDIAN remains active. No protected source,
database/schema, configuration or activation changes. Backup and rollback are
verified. No Git commit, tag, push or release occurred.**
