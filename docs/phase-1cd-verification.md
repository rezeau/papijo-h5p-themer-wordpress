# Phase 1C-D — administration UX refinements

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

7 November 2026. **Source implementation and automated verification: PASS.**
**Authorized one-file deployment and read-only native verification: PASS.**
**Firefox recheck of these changes: NOT YET TESTED.**

Reviewed administration.md, phase-1cb-verification.md, phase-1cc-acceptance.md and
phase-1cc-firefox-checklist.md before implementation. The user reports successful
completion of all 11 prior Firefox acceptance steps. Those earlier reports retain
their historical pending status; this checkpoint records the subsequent user
confirmation without inventing a detailed Firefox results artifact.

## Files changed

Modified:

- `includes/Admin/class-admin-view.php` — the only changed production file.
- `tests/admin/run.php` — includes the focused UX regression file and labels output.
- `docs/administration.md` — records the refined editor/catalog behavior.

Created:

- `tests/admin/ux-regressions.php` — isolated regression checks.
- `docs/phase-1cd-verification.md` — this report and deployment/recheck handoff.

No controller, service, domain, persistence, presets, assets or plugin version
changes. Read-only comparison of all 43 deployed production files against source
found exactly one difference: `includes/Admin/class-admin-view.php`. The other
42 deployed files match source byte-for-byte. This workspace has no Git repository;
the comparison uses filesystem hashes. No commits, tags, pushes or publication.

## UX implementation

1. The existing theme save redirect remains HTTP 303 to Edit for the same custom
   identifier. The editor shows the exact WordPress-style success notice
   **Theme saved successfully.** The general **Theme library saved.** notice is
   retained for overview operations such as publication and default selection.
2. **Back to theme overview** is an escaped anchor styled with WordPress's
   `button` class, immediately beside **Save theme**, before Reset. It targets
   the clean administration overview URL and cannot submit the form. This appears
   on Create, Duplicate and Edit. Their previous top navigation row is replaced
   to avoid duplicate overview links; other routes keep their existing navigation.
   Clicking Back follows ordinary navigation and discards unsaved changes.
3. Saved themes remain editable, with the existing 33 color fields and editable
   name. Existing validation diagnostics, safely escaped submitted values,
   context/revision handling and recovery behavior remain in place.
4. The overview uses the stored default reference already used by its Selected
   column to omit Delete for the matching custom theme. After a default change,
   rerendering restores Delete for the previous default. Built-ins still expose
   View/Duplicate only. Direct deletion protection remains enforced on the server.

## Security and compatibility

This is presentation-only. Capability admission, nonce binding, strict input
validation, revision/context checks, CAS persistence, publication, default
eligibility and built-in immutability are unchanged. Hiding Delete is a convenience,
not an authorization boundary. Direct default deletion and forged built-in mutation
remain rejected in the tests. Navigation labels use existing translation/escaping;
the overview URL uses `esc_url`. No new scripts or event handlers were introduced.

Foundation tests retain H5P admission and all SNORDIAN detection/suspension cases.
The coexistence suite confirms administration remains usable with four isolated
SNORDIAN signals and runtime stays suspended. No SNORDIAN code was used as an
implementation source. Live coexistence/playback was not repeated in this phase.

## Automated verification

All rows below passed independently on **PHP 7.4.33, 8.3.18 and 8.4.22**:

| Suite | Result per PHP version |
| --- | --- |
| Phase 1A foundation, including actual installed H5P source inspection | 18 scenarios / 441 assertions PASS |
| Phase 1B theme domain | 2,315 assertions PASS, including 919 Backdrop parity assertions |
| Phase 1C-A persistence API doubles | 204 assertions PASS |
| Phase 1C-B/1C-D administration | 304 assertions PASS: original 210 plus 94 focused UX assertions |
| SNORDIAN coexistence | 4 isolated signals / 36 additional assertions PASS |
| PHP syntax | All 55 source/test PHP files PASS |

Also: `node tests/admin/browser-behavior.js` — **30 assertions PASS** using the
existing isolated DOM doubles. This is not a live Firefox visual check.

The 94 focused assertions exercise the real controller's successful save/303
redirect with WordPress API doubles, then the real view with injected local preview
CSS. They cover the exact success notice, editable saved form, a subsequent saved
edit, ordinary overview confirmation, adjacent non-submit anchor/destination on
all three editor routes, no duplicate overview link, invalid input diagnostics and
retained values, both custom-default transitions, Core-default restoration, direct
default-delete rejection and immutable behavior for all five built-ins.

One initial test run failed because the new test rendered a controller-created
preview without loading the dependency class that production bootstrap supplies.
The test now uses the established injected-preview fixture after exercising the
real controller redirect. No production dependency or preview behavior was changed.
All final runs pass. The final added assertions were rerun on all three versions,
with syntax rechecked for the changed test file.

Reproduction from the project directory, using each available PHP binary:

```powershell
$phpPath = 'PHP_CLI'
& $phpPath -n tests/run.php 'WORDPRESS_ROOT/wp-content/plugins/h5p'
& $phpPath -n tests/domain/run.php 'BACKDROP_REFERENCE_ROOT'
& $phpPath -n tests/storage/run.php
& $phpPath -n tests/admin/run.php
& $phpPath -n tests/admin/coexistence.php
node tests/admin/browser-behavior.js
```

The foundation/domain suites inspect official H5P and authoritative Backdrop
files read-only; they do not bootstrap either CMS. Persistence and administration
use isolated API/storage doubles. The opt-in real-MySQL suite, live administrator
persistence checks and two-process live test were not rerun: this phase makes no
storage changes and deliberately opens no database connection. Their historical
Phase 1C-C results are not represented as new runs.

## Protected-file verification

Before/after recursive SHA-256 manifests matched across **903 protected files**:

| Protected location | Files | Result |
| --- | ---: | --- |
| Authoritative Backdrop v0.5.0 source, excluding .git | 154 | Unchanged |
| Original Backdrop source, excluding .git | 65 | Unchanged |
| Installed backdrop2 H5P Themer | 72 | Unchanged |
| Entire acceptance.example.test plugin directory, including H5P, SNORDIAN and deployed Papi Jo | 508 | Unchanged |
| Backdrop active configuration | 102 | Unchanged |
| acceptance.example.test/wp-config.php | 1 | Unchanged |
| backdrop2/settings.php | 1 | Unchanged |

No WordPress bootstrap, database connection or database writes were performed.
Consequently no activation, configuration, theme-data or content changes were
made by this checkpoint. No database snapshot/checksum audit was rerun, so this
does not assert absence of independent site changes by other processes. Apache,
certificates and browser security settings were not touched. No runtime styling,
Density injection, activity controls or learner-profile features were introduced.

## Minimal deployment plan — subsequently executed under explicit approval

Only this production file needs replacement:

- Source: `PROJECT_ROOT/includes/Admin/class-admin-view.php`
- Destination: `PROJECT_ROOT/includes/Admin/class-admin-view.php`

SHA-256 recorded during this checkpoint:

```text
Existing deployed file:
PRIVATE_VERIFICATION_DIGEST_OMITTED
Verified source replacement:
PRIVATE_VERIFICATION_DIGEST_OMITTED
```

After explicit approval:

1. Recheck source and target hashes against these values; stop on an unexpected
   difference. Preserve a verified copy of the existing target outside the web root.
2. Stage and syntax-check the one new PHP file, then replace only the target file
   using a same-directory replacement to avoid a partially copied PHP request.
3. Verify the deployed hash equals the source hash and that no other plugin file
   changed. Do not deploy tests, documentation, private artifacts or the whole tree.
4. Reload the administration interface and perform the Firefox recheck below.
   No activation cycle, database migration or configuration change is needed.
5. If errors occur, restore only the backed-up old view file and verify its original
   hash. This file deployment requires no database restore. Any user-created recheck
   data/default changes should be reversed explicitly using the recorded starting
   state rather than overwriting the database.

This plan was subsequently executed after the user's explicit single-file
deployment authorization. Results and retained evidence are recorded below.

## Short Firefox recheck — after approved deployment

Use normal Firefox at **https://acceptance.example.test/wp-admin/admin.php?page=pjh5pwp-themes**.
Do not deactivate SNORDIAN or change its settings.

1. Record the current global default and confirm the separate Papi Jo/SNORDIAN
   administration entries and Papi Jo runtime suspension notice remain present.
2. Create a uniquely named disposable custom theme, for example `ux_recheck_a`.
   Save: confirm **Theme saved successfully.**, remain on Edit, and see **Back to
   theme overview** beside Save. Change the name/color and save again.
3. Enter an invalid color and Save: verify the error and unsaved values persist.
   Correct it, save, then follow Back. Confirm it returns to the overview without
   another save or error. For a separate unsaved name change, following Back must
   leave the last saved name intact.
4. Select that custom as global default. Confirm Selected and absence of its
   Delete link while Edit/Duplicate remain. Select the recorded original default;
   confirm Delete reappears. Confirm all five built-ins still lack Edit/Delete.
5. Delete only the disposable custom using its confirmation form. Confirm the
   original default is restored, existing themes/content and SNORDIAN settings
   remain intact, and an existing H5P playback looks as before.

## Remaining issues and checkpoint status

No known source-level regression remains. New UX layout and actual Firefox
navigation remain unverified pending the user's manual recheck.
The previous 11/11 Firefox acceptance result is user-confirmed baseline evidence,
not evidence for the new refinement. **Phase 1C-D implementation and authorized
deployment verification are complete. Stop pending manual Firefox acceptance;
no next-phase work is authorized.**

## Authorized deployment results — 7 November 2026

The user subsequently authorized replacement of only
`includes/Admin/class-admin-view.php`, followed by read-only native checks.

| Check | Result |
| --- | --- |
| Exact installed plugin directory | `PROJECT_ROOT` |
| Existing view matches Phase 1C-C baseline hash listed above | PASS |
| Source matches tested Phase 1C-D hash listed above | PASS |
| Verified pre-deployment file backup outside web root | PASS |
| Staged same-directory replacement of only authorized file | PASS |
| Deployed SHA-256 equals source | PASS: `PRIVATE_VERIFICATION_DIGEST_OMITTED` |
| Deployed PHP syntax | PASS on 7.4.33, 8.3.18 and 8.4.22 |
| Native GET/bootstrap and rendering | 27 checks PASS on WordPress 7.1.4 / PHP 8.4.22 |
| Official H5P / Core API | Active and supported: 1.17.10 / 1.28 |
| SNORDIAN | Active: 0.0.12; runtime callback priority 10 and original two script paths preserved |
| Papi Jo runtime styling | Suspended; runtime remains unimplemented/disabled |
| Database preservation | All 61 table structures, row counts and sorted serialized-row SHA-256 checksums unchanged; 5,835 total rows |
| acceptance.example.test file preservation | 8,730 files before/after; exactly the authorized view changed; other 8,729 unchanged |
| PHP warnings | None |
| Firefox visual/navigation acceptance | NOT YET TESTED for this refinement |

The existing deployed file was copied to the private directory below and its hash
verified before replacement. The first replacement call rejected PowerShell's
empty-string conversion of a null backup argument before modifying the target.
The unchanged target, backup and staged hashes were reverified; passing a native
null corrected the call. Replacement then succeeded. No rollback was needed and
no stage file remains.

Native checks loaded the deployed plugin in an existing administrator's CLI user
context without login/session changes. A process-local query filter was installed
before WordPress bootstrap: it admitted SELECT/SHOW/DESCRIBE/EXPLAIN and would
return an empty query to prevent execution of any other wpdb statement. **Zero
write attempts were observed, including shutdown.** Cron/updaters/external HTTP
were disabled only for that CLI process; no WordPress configuration was edited.
Independent SELECT/SHOW-only database snapshots before/after matched all 61 tables.
No activation, migration, theme save or other database mutation was performed.

The actual stored overview, Create form and five immutable saved previews rendered
successfully, and separate Papi Jo/SNORDIAN menus registered. The stored library
was ready at revision 27 with zero custom definitions and remained identical after
rendering. To exercise the new Edit success notice, adjacent navigation and Delete
visibility without creating database records, the native check used a clearly
separate in-memory theme/default fixture and simulated the successful-save flag.
This verifies deployed markup, not a live persistence submission. The original
SNORDIAN script hook still returned its two existing asset paths. Actual browser
layout, clicking, saving and H5P playback appearance remain manual checks.

Private evidence directory:

```text
PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED
```

Retained artifacts: `deployment-result.json`, `native-readonly.json`,
`database-before.json`, `database-after.json`, `database-preservation.json`,
`files-before.json`, `files-after.json`, `file-preservation.json` and the private
audit helpers. The verified rollback backup is **`class-admin-view.php.before`**
in that directory, SHA-256
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Restoring that one file would reverse the deployed code change; no database
restore is required for this deployment. No commit, tag, push, publication,
SNORDIAN setting, H5P content, Apache/certificate or learner/runtime feature change
was made. Await the user's Firefox results before further work.
