# Phase 3F-B — Controlled deployment verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

9 November 2026, Europe/Paris. **DEPLOYED AND VERIFIED.** Final promotion completed at 12:21:17; deployment checks and cleanup completed at 12:21:22. Independent final integrity verification completed at 12:22:17.

The installed plugin now contains exactly **78 files**. The approved 15 additions and 8 replacements match the verified staging hashes; all 55 other files, including the installed runtime-enabled bootstrap, are preserved byte-for-byte. All 68 installed PHP files pass syntax checks under PHP 8.4.22. The actual Apache process passed 61 read-only native administration checks. Relevant database state is identical to the original checkpoint.

**STOP before Phase 3F-C.** No Theme was created or imported, no preferences were changed, and no Firefox acceptance or Git operations were performed.

## 1. Authorization and history

The original Phase 3F-B stopped before promotion because strict request-drain proof was unavailable without shared Apache changes. The user's subsequent resume instruction explicitly accepts a proportionate local quiet period with an intact rollback checkpoint, requires available activity checks and disclosure of residual risk, and forbids a separate VirtualHost investigation or unapproved Apache restart.

The previous report was read and preserved in the first resume evidence directory as deployment-report-before-resume.md. The Phase 3F-A manifest, original checkpoint and source remain the deployment authority. This report replaces the earlier stopped status with the final verified result.

One resumed attempt triggered a conservative code rollback because of a private verification-wrapper error. Its recovery, diagnosis and corrected follow-up are recorded in section 9. Production source was not changed to address that error.

Source:

    PROJECT_ROOT

Installed target:

    PROJECT_ROOT

## 2. Revalidated baseline and checkpoint

The original private checkpoint remains intact and was not overwritten:

    PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED

Its checkpoint-sha256.json seal still has SHA-256:

    PRIVATE_VERIFICATION_DIGEST_OMITTED

All 165 sealed files were revalidated, including the complete 63-file installed-plugin-backup, 78-file staged-plugin, inventories and database dumps. The stage still matches current source for every manifest file and the backup for every preserved file. The source bootstrap's intentional difference remains excluded.

The checkpoint is outside the WordPress web root. Its protected root ACL grants the current Windows user, SYSTEM and Administrators access. Database backup files retain those restricted inherited permissions. No wp-config copy, password or command-line database credential was saved. Credentials were read internally as literal configuration tokens and were not printed.

Fresh read-only consistent-snapshot database evidence was also saved for each resume attempt. Backups cover:

- Eight options: Theme library, Papi Jo roles, active plugins, WordPress roles, two SNORDIAN options, home and siteurl, including IDs and autoload fields.
- All two profile-table records, including schema, revision and exact serialized state.
- All six relevant capability/user-level metadata rows; queries also cover the four absent legacy preference keys.
- Table definitions, lossless hexadecimal row snapshots and selected-row SQL dumps.

Backup verification includes file/hash readback, JSON decoding, SQL record counts and hexadecimal/null literal round trips. No SQL dump was executed or restored. These are selected-record backups, not a whole-site database dump.

| Item | Revalidated before deployment and unchanged afterward |
| --- | --- |
| Installation | https://acceptance.example.test; database acceptance.example.test; prefix wp_; WordPress 7.1.4 |
| Required active plugins | Papi Jo, SNORDIAN and official H5P; unchanged eight-entry activation option |
| H5P | Plugin 1.17.10; Core API 1.28 |
| Runtime bootstrap | Existing guarded PJH5PWP_ENABLE_BROWSER_THEME = true |
| Theme library | Aggregate schema 1, revision 31; settings schema 2 |
| Definitions / global default | No custom definitions; Core default, represented by null |
| Publication | Builtin dark/lavender/mint/sunset true; five existing false custom tombstones preserved |
| Papi Jo roles | Schema 1, revision 5; empty explicit role grants |
| Profiles | User 1 revision 12; user 2 revision 4 |
| Theme/Density preferences | Both profiles retain allow_theme and allow_density "0", theme site_default, density original |
| Legacy preference metadata | Zero rows under the four retired pjh5pwp preference keys |

The five unchanged publication tombstones are pjh5pwp_acceptance_mint, pjh5pwp_acceptance_copy, mint_copy, mint_copy_2 and mint_ux_test.

Protected installed bootstrap SHA-256:

    PRIVATE_VERIFICATION_DIGEST_OMITTED

## 3. Local quiet period and residual concurrency risk

Directly observed checks:

- The computer-use inventory showed zero Codex in-app browser tabs and zero MCP App tabs. This task opened no browser tab.
- No matching scheduled acceptance.example.test/wp-cron/wp-cli Windows task was found.
- Three observations before the final promotion, at 12:21:05, 12:21:10 and 12:21:15, found no established inbound HTTP/HTTPS connection on local ports 80/443, no PHP/WP CLI process, no other acceptance.example.test database connection, no recent cron lock and no drift in the selected baseline rows.
- Shared Apache access-log lengths and timestamps were stable throughout those observations.
- Two cron hook groups were due, but no running cron activity was observed. The verification request disabled WP cron and automatic updates; it did not execute due jobs.
- Apache runs as LocalSystem. SYSTEM process command-line/path details were not fully visible; those fields were not treated as proof that all possible background execution was absent.

A temporary gate was prepended to acceptance.example.test's existing root .htaccess. A GET of the normal administration URL returned the explicit 503 maintenance response without loading WordPress. The gate rejected new requests except the randomly named, loopback-only, header-token-protected deployment helper. It affected acceptance.example.test only. VirtualHost configuration, PHP settings and unrelated sites were not changed.

For the final attempt, the gate was verified at 12:21:04 and removed at 12:21:22. The original .htaccess bytes/hash were restored and the helper was removed. A separate, equally bounded window covered the earlier recovered attempt.

**Residual risk:** Apache still provides no per-request scoreboard because mod_status is not loaded. Browser closure is the user's agreement, rather than proof about every hidden application timer. Sampled connections, database activity and logs cannot mathematically prove that every pre-existing PHP request has ended, especially requests using unlimited execution or ignoring client disconnects. The new-request gate also does not terminate an earlier request. This residual risk was disclosed before promotion and accepted under the user's local quiet-period instruction with verified rollback available. No active competing request/write was detected.

No shared Apache restart or module/configuration change was performed.

## 4. Exact deployed manifest

All paths below are relative to the installed plugin. Full per-file SHA-256 values remain in the original checkpoint's deployment-manifest.json and staged-files.json.

| Action | Path |
| --- | --- |
| Add | includes/theme-transfer.php |
| Add | includes/theme-export.php |
| Add | includes/theme-import.php |
| Add | includes/Transfer/class-json-decoder.php |
| Add | includes/Transfer/class-theme-json.php |
| Add | includes/Transfer/class-backdrop-adapter.php |
| Add | includes/Transfer/class-import-planner.php |
| Add | includes/Transfer/class-export-service.php |
| Add | includes/Transfer/class-import-storage-compatibility.php |
| Add | includes/Transfer/class-import-service.php |
| Add | includes/Admin/class-import-review.php |
| Add | includes/Admin/class-transfer-admin.php |
| Add | includes/Admin/class-transfer-download.php |
| Add | includes/Admin/class-transfer-upload.php |
| Add | includes/Admin/class-transfer-view.php |
| Replace | includes/administration.php |
| Replace | includes/class-plugin.php |
| Replace | includes/Admin/class-admin-controller.php |
| Replace | includes/Admin/class-admin-messages.php |
| Replace | includes/Admin/class-admin-view.php |
| Replace | includes/Theme/class-theme-validator.php |
| Replace | includes/Theme/class-theme-operations.php |
| Replace | includes/Theme/class-library-changes.php |

Exactly 15 additions, 8 replacements and 55 preserved files produce 78 installed files. No documentation, tests, fixtures, backups or source-only README.md was installed.

## 5. Controlled Windows promotion

A fresh promotion-candidate copy was made from the sealed stage in the final resume evidence directory, outside the web root. Its exact membership, lengths and hashes were checked before touching the live tree. The original sealed checkpoint was neither moved nor modified.

Under the verified request gate and observed quiet period:

1. Recheck the live 63-file tree and the complete 78-file candidate.
2. Verify resolved move paths are the intended directories; reject existing destination paths and reparse-point source roots.
3. Move the live plugin directory to retired-installed-plugin in the fresh private evidence directory.
4. Move promotion-candidate to the original installed plugin path.
5. Restore/check the previous owner and access-rule permissions on preserved paths, using the same existing file/directory rules for additions.
6. Verify all 78 file hashes, then perform Apache cache handling, native smoke checks, read-only database comparison and installed PHP lint.

The final directory promotion ran from 12:21:16.028 to 12:21:17.309. **The two directory moves are not an atomic filesystem transaction.** The brief transition was covered by the request gate and the authorized local quiet-period procedure. No uncontrolled multi-file overlay was performed.

The retired 63-file tree and original sealed backup are both retained. No activation call, schema migration or runtime-flag edit was used.

## 6. Effective Apache PHP and OPcache handling

The original web findings were revalidated through the actual acceptance.example.test Apache process:

| Setting | Observed |
| --- | --- |
| PHP / SAPI | 8.4.22 / apache2handler |
| opcache.enable | 1 |
| opcache.validate_timestamps | 1 |
| opcache.revalidate_freq | 2 seconds |
| opcache.file_update_protection | 2 seconds |
| opcache.restrict_api | Empty |
| opcache.preload / file_cache | Empty / empty |

After promotion, the guarded helper validated each changed file's hash and called opcache_invalidate(path, true) for exactly the 23 approved paths. **All 23 calls returned true; all 23 paths were absent from the active script cache immediately afterward.** The following fresh web smoke request loaded the deployed classes successfully.

No CLI cache reset, global OPcache reset, PHP settings change or Apache restart was used. Cache invalidation was also handled before/after the earlier code rollback, as recorded in section 9.

## 7. Post-deployment integrity and database comparison

Passed after the final promotion:

- Exactly 78 installed files; every installed hash equals the stage.
- All 23 manifest hashes match source and all 55 preserved hashes match the backup.
- Installed bootstrap retains the protected SHA-256 above.
- All 68 installed PHP files pass PHP 8.4.22 syntax checks.
- All 12 new classes load in the Apache smoke request.
- All 465 files belonging to other installed plugins, including SNORDIAN and H5P, remain unchanged.
- WordPress wp-admin, wp-includes and authoritative Backdrop source fingerprints remain unchanged.
- All 835 original source files remain unchanged; only this report and the new private evidence directories are excluded from that comparison.
- Final helper absence and original .htaccess hash were independently checked after cleanup.

Fingerprint definition: Base64 SHA-256 of sorted absolute-path + space + per-file SHA-256 lines joined with LF, including hidden files.

| Tree | Files | Final fingerprint |
| --- | ---: | --- |
| Installed Papi Jo plugin | 78 | +38OcMZi0FFoOAJyhon68UzNnYIeYSxbzHmKHZG6hXM= |
| All acceptance.example.test plugins | 543 | GV9vUeIZZll1Xr+prUIuEL0VjURxnYvEKgvTNERXSMY= |
| wp-admin | 593 | dz4InhTVdJ9fm0Ua4AzPHhBE5TH4+JX5F/6tLoARihA= |
| wp-includes | 2729 | w6HkRy4C/Xk/+G3Gz/NbEZDTp+lAkSUbYxFudbg2u+E= |
| Authoritative Backdrop source | 155 | mc7Fafa1mTiuJuwKrTxKbLfM84gyGNH0bS1naKEsX9s= |
| Original source, excluding new evidence/report | 835 | 28coxQvjKHeQGDBfFNrYQDy5s27KIeoJ13R6hmUtDLg= |

For the unrelated-plugin check, replacing only the current Papi Jo rows in the complete inventory with its recorded 63-file baseline reconstructs the exact original 528-file fingerprint, 4wnKOVEdlXge0LWnS26HijfDQKtTBTms1xoJrnR5aVw=. Thus unrelated files were not silently omitted from the comparison.

Relevant database rows and captured table definitions compare exactly with both the original checkpoint and the fresh resume baseline. The complete selected snapshot SHA-256 remains:

    PRIVATE_VERIFICATION_DIGEST_OMITTED

| Option | Identical before/after value SHA-256 |
| --- | --- |
| pjh5pwp_theme_library | PRIVATE_VERIFICATION_DIGEST_OMITTED |
| pjh5pwp_role_permissions | PRIVATE_VERIFICATION_DIGEST_OMITTED |
| active_plugins | PRIVATE_VERIFICATION_DIGEST_OMITTED |
| wp_user_roles | PRIVATE_VERIFICATION_DIGEST_OMITTED |
| snordiansh5pthemer_option | PRIVATE_VERIFICATION_DIGEST_OMITTED |
| snordiansh5pthemer_version | PRIVATE_VERIFICATION_DIGEST_OMITTED |
| home and siteurl, each | PRIVATE_VERIFICATION_DIGEST_OMITTED |

Unchanged profile state SHA-256:

- User 1: PRIVATE_VERIFICATION_DIGEST_OMITTED.
- User 2: PRIVATE_VERIFICATION_DIGEST_OMITTED.

Papi Jo, SNORDIAN and official H5P remain active. Theme revision, definitions, global default, publication, role grants, capabilities and Theme/Density preferences are unchanged. The runtime-enable bootstrap remains true, and the web compatibility policy still gives active SNORDIAN precedence.

## 8. Nonmutating native administration smoke checks

The guarded loopback HTTP helper loaded the installed WordPress/plugin code and invoked the actual registered administration controller using GET semantics. It selected the existing sole administrator only in request memory; no login cookie/session was created. It did not submit the normal admin URL through authentication or perform browser acceptance.

The request disabled cron, automatic updates, external HTTP and file modifications. A WordPress query filter rejected all non-read SQL before execution, including queries from the plugin's private wpdb sessions. Four expected WordPress theme-pattern cache housekeeping attempts, two DELETE and two INSERT operations, were blocked. There were no unexpected attempted mutations or PHP warnings. The request made 45 read queries.

**All 61 assertions passed**, including:

- Native page registration at h5p-content_page_pjh5pwp-themes.
- Required activation states, installed plugin path, runtime constant and Core API 1.28.
- SNORDIAN callback registration and runtime precedence.
- Ready Theme storage at revision 31.
- Existing global-default and role controls.
- Export/import overview section and Import navigation.
- Import page with multipart JSON input, nonce, Validate and preview control, return link and ignored-Backdrop-Density explanation.
- No preview or confirmation state without an upload.
- Existing builtin Mint preview.
- All 12 new transfer/UI classes present.
- Unchanged Theme library after rendering.

The library contains no custom themes, so the verified Export section correctly displays “There are no custom themes to export.” Per-theme and bulk export submission buttons are conditional on custom records and were not exercised by creating a fixture.

No JSON was uploaded, parsed as an import request, previewed or committed. No export POST, Theme creation or preference/default/publication submission was made.

## 9. Verification-wrapper failure and recovery

The first resumed promotion at approximately 12:19 passed file promotion, Apache invalidation and all 61 native checks. Its private shutdown wrapper then evaluated a buffer that WordPress's normal wp_ob_end_flush_all shutdown hook had already closed. ob_get_clean returned false; comparing that with an empty string incorrectly marked the response as failed despite zero unexpected output, zero warnings and no fatal error.

The deployment script stopped normal activity and restored the original code tree:

- Invalidate all 23 deployed paths in Apache before moving the candidate aside.
- Restore the original 63-file directory and verify every hash.
- Invalidate the eight restored replacement paths through Apache.
- Restore .htaccess and remove the helper.
- Independently recheck the restored tree, activation/configuration and exact database snapshot.

Recovery passed. No database dump was restored and no unknown write outcome existed. The failed candidate, complete failure/rollback evidence and earlier report remain preserved.

Only the private verifier was corrected to treat an already-closed buffer as empty; malformed HTTP/JSON output still fails verification. Two isolated PHP 8.4.22 reproductions, with an intact buffer and with WordPress-style shutdown flushing, passed. Production source and the sealed candidate were unchanged.

After diagnosis and completed recovery, a fresh evidence directory was used. Baseline, gate and quiet-period checks were repeated before the final successful promotion. The corrected request passed all 61 assertions and its shutdown checks. This was not a blind retry or a database-write retry.

## 10. Evidence locations and final state

First resume attempt, recovery and original report copy:

    PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED

Its resume-evidence-sha256.json covers 114 files, excluding itself, with seal SHA-256:

    PRIVATE_VERIFICATION_DIGEST_OMITTED

Successful final attempt and retained original 63-file tree:

    PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED

Its resume-evidence-sha256.json covers 97 files, excluding itself, with seal SHA-256:

    PRIVATE_VERIFICATION_DIGEST_OMITTED

Both seals were read back and verified. Important final artifacts include files-before.json, files-after.json, quiet-period.json, activity-window-*.json, request-gate.json, promotion.json, apache-opcache-invalidation.json, native-admin-smoke.json, database-before/after snapshots and summaries, final-protected-integrity.json, cleanup.json and deployment-result.json. Helper files retained as evidence are outside the web root and contain token hashes, not request tokens.

Final installed state is the verified 78-file deployment. No temporary helper or maintenance gate remains. Original .htaccess SHA-256:

    PRIVATE_VERIFICATION_DIGEST_OMITTED

There is no unresolved deployment blocker. The disclosed concurrency limitation is inherent in the authorized local quiet-period procedure; it is not presented as guaranteed request drainage.

## 11. Remaining Phase 3F-C work and stop

Phase 3F-B deployment verification is complete. Full Firefox acceptance, real custom-theme exports/imports, colors-only round trips, qualified native Backdrop input, create-only collision behavior, unpublished creation and independent Density/preferences checks remain for separately authorized Phase 3F-C.

No test Themes or JSON imports, manual Firefox acceptance, migrations, Git commit/tag/push or release were performed. **STOP here and wait for the user's next authorization.**
