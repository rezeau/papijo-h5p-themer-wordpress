# Phase 1C-C — deployment verification

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

7 November 2026. **FINAL HANDOFF: automated/native acceptance PASS; manual Firefox acceptance NOT YET TESTED.**

Finalized from the saved verification artifacts and the user's confirmation of the
previous session's end state. This handoff performed documentation edits and
read-only artifact inspection only. No WordPress bootstrap, live database query,
deployment, activation, conversion or acceptance test was repeated. The stored
state below is the last verified state, not a newly executed live-site audit.

| Area | Final status |
| --- | --- |
| Consistent backup/restore and structural verification | PASS |
| wp_options-only conversion and data preservation | PASS |
| 43-file deployment, hashes and native activation | PASS |
| H5P/SNORDIAN detection and runtime suspension | PASS |
| 75 native administrator/persistence checks | PASS |
| Two-process concurrency and fresh-process reload | PASS |
| Database/file preservation audits | PASS |
| Manual Firefox interface, JavaScript and playback checks | NOT YET TESTED |

The preflight and HTTPS investigation are historical snapshots. Their references
to an undeployed plugin describe those earlier checkpoints; this final report and
the later activation/final artifacts establish the deployed, active state.

The user explicitly authorized deployment/activation and available non-browser checks after the HTTPS investigation. No browser was used during this resumed deployment. Apache, certificates, trust stores, WordPress URLs and browser security settings remain unchanged. No warning was bypassed.
## 1. Verifier correction

The previous raw SHOW CREATE TABLE comparison was replaced with actual structural metadata from information_schema. It compares table names, engines, row formats, auto-increment values, table collations/options/comments; ordered column definitions including types, defaults, nullability, charsets, collations, attributes and expressions; primary/secondary indexes including uniqueness, order, prefix lengths, visibility and expressions; constraints, key-column usage, foreign-key rules, checks, partition definitions and trigger definitions.

Only the source/restore database's own schema identifier is mapped to a common marker. Index cardinality, physical allocation/statistics, column access privileges and trigger creation timestamps are excluded because they are not table-definition semantics. Meaningful schema properties remain compared. Raw DDL is retained for audit, with no string-stripping substitution for structural checks.

During verifier development, an unavailable partition ENGINE field was corrected to use the engine from TABLES, and a negative-control test was fixed to toggle an index's actual uniqueness value. Those verifier-only attempts changed no source tables; their disposable restores/credentials were cleaned up. The final rehearsal used a fresh dump after these corrections.

## 2. Fresh consistent backup and restore — PASS

Private successful-run directory:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

- Held ordinary READ locks on all 61 acceptance.example.test tables during snapshot, dump and source comparison. No READ LOCAL or global server lock.
- Restored all 61 tables into `pjh5pwp_restore_17ec581a47b6b28d`.
- All 61 structural definitions, row counts and data checksums matched: 5,834 total rows.
- The same 57 raw DDL notation differences remained, while resolved column/table charsets and collations matched exactly.
- Ten negative controls rejected engine, column-type, charset, collation, index-uniqueness, foreign-key, table-option, row-count, checksum and missing-table changes.
- Source structure/data remained unchanged through backup verification.
- Disposable database removed, temporary client credentials removed, locks released.
- Fresh dump: 15,903,913 bytes; SHA-256 `PRIVATE_VERIFICATION_DIGEST_OMITTED`.
- `backup-verified.json` records restore_ready=true and successful cleanup. Original schemas, options rows and all comparison snapshots are retained privately.

This completes the restore-readiness gate that stopped the earlier attempt. The previous failed raw-text comparison remains documented in the earlier private artifacts; no acceptance.example.test rollback or source restoration was performed.

## 3. wp_options-only conversion — PASS

Executed exactly:

`ALTER TABLE wp_options ENGINE=InnoDB`

| Check | Result |
| --- | --- |
| Original/result engine | MyISAM / InnoDB |
| Options rows before/after | 308 / 308 |
| Data checksum before/after | Both `PRIVATE_VERIFICATION_DIGEST_OMITTED` |
| Other structural properties | All preserved |
| Other 60 tables | All schemas, row counts and data checksums unchanged |
| Conversion warnings | None |

The conversion was separately gated on the verified dump hash and readiness marker, plus a fresh source comparison. Full before/after metadata and `conversion-result.json` are retained in the parent private run directory. No other source DDL/DML was executed by the conversion helper. Existing H5P content and SNORDIAN configuration values are covered by the preserved data checksums.

## 4. Backup/state recheck and deployment — PASS

Before copying, reverified the restore-ready backup and its SHA-256, and compared all 61 current database structures/counts/data checksums against the successful post-conversion snapshot. The site matched. All 8,687 existing WordPress files matched the verified file inventory. No prior Papi Jo deployment/activation/theme option existed.

Copied the exact **43-file / 195,965-byte** release into:

`PROJECT_ROOT`

Method: independent directory copy with exclusive destination/file creation, no links, no overwrite or merge. Every deployed path/size/SHA-256 matches the staged manifest. Tests, documentation and private backups/audit helpers were excluded. All **34 deployed PHP files** pass PHP 8.4.22 syntax checks. No plugin implementation code was changed during this acceptance phase.

Release manifest digest:

`PRIVATE_VERIFICATION_DIGEST_OMITTED`

## 5. Native WordPress activation — PASS

Activated only `papijo-h5p-themer-wordpress/papijo-h5p-themer-wordpress.php` through WordPress's native `activate_plugin()` API, in a private CLI process using the existing administrator's WordPress capabilities. This replaced the earlier browser activation method under the user's explicit non-browser authorization; the deployment copy method remained unchanged.

- WordPress 7.1.4 / PHP CLI 8.4.22 loaded the real installed plugins normally.
- Native activation returned success, without PHP warnings or unexpected activation output.
- All seven existing active entries were preserved; only Papi Jo Themer was added.
- A fresh subsequent WordPress process loaded the newly active plugin successfully.
- Activation and ordinary bootstrap/menu/preview reads did **not** create the theme option.
- Theme storage was initialized afterward by an explicit authorized Admin_Service operation using a native WordPress nonce.

Private CLI processes disabled scheduled/background/external updates and the browser fatal/recovery presenter only within those processes. No wp-config.php, site option or browser security setting was changed to do so. PHP errors remained captured. Tests selected existing administrator/nonadministrator identities in process; they did not authenticate over HTTP, create accounts, change roles or alter user metadata.

## 6. WordPress/H5P/SNORDIAN compatibility — PASS for available native checks

| Check | Verified result |
| --- | --- |
| Official H5P | Active 1.17.10, loaded original classes, supported exact Core API 1.28 |
| SNORDIAN | Active 0.0.12; active-record, constant, namespace and runtime-callback signals all detected |
| Runtime policy | runtime_state=suspended; blocker=snordian_active; future_runtime_permitted=false; runtime_enabled=false; runtime_implemented=false |
| SNORDIAN runtime callback | Preserved at priority 10 |
| Native H5P script alteration hook | Continues to supply SNORDIAN's config and runtime script paths; no Papi Jo playback callback added |
| Administration menus | Papi Jo submenu registered under H5P Content; SNORDIAN retains a separate options page |
| SNORDIAN settings server markup | Existing callback renders Daylight/large; no form submission/save |
| Our callbacks/assets | Only lifecycle/diagnostic/admin-page callbacks; own CSS/JS enqueued only for own admin page; valid bracket-notation inline configuration |
| Browser/HTTPS | No browser test, certificate bypass or trust/configuration change in this deployment |

The native script paths remain:

- `https://acceptance.example.test/wp-content/plugins/snordians-h5p-themer/h5p-themer-config.js`
- `https://acceptance.example.test/wp-content/plugins/snordians-h5p-themer/js/h5p-themer.js`

SNORDIAN's generated config file hash remains:

`PRIVATE_VERIFICATION_DIGEST_OMITTED`

These results verify loaded integrations, settings rendering, registered asset paths, suspension and preservation. Actual playback appearance/interaction is reserved for the user's Firefox checks; native execution is not a substitute for visual certification.

## 7. Native administrator/persistence acceptance — PASS

**75 checks passed** against the actual deployed services, native WordPress permission/nonce functions, standard wpdb and production storage adapter on acceptance.example.test. Covered:

- Explicit versioned initialization and rejection of repeated stale initialization.
- Five stable built-ins; built-in Edit/Delete rejection and read-only saved views.
- Built-in/custom duplication, sparse overrides, exact hex case/shorthand, clearing to pinned inheritance, rename with stable ID and independent preset-pinned copies.
- New custom unpublished, explicit publication/unpublication, unpublished global eligibility, no auto-publication and selected-default deletion protection.
- Invalid color, truncated fields, unexpected Density input and missing references rejected without changing the library.
- Native invalid nonce, anonymous and existing nonadministrator capability rejection.
- Stale edits, relabeled revisions/nonces and stale snapshot contexts rejected without overwriting the winning state.
- Two independent native WordPress workers captured the same revision before either submitted: exactly one committed, the other returned storage_conflict, and revision advanced exactly once.
- Actual server markup contains all 33 text/native-color pairs, five groups, scriptless saved/live/Core preview frames and no Density/profile control fields; rendering remains read-only.
- A fresh process reads the same valid snapshot through the production adapter and native Options API; autoload is off.

HTML/server generation was checked, but no actual browser form submission, HTTP session authentication, JavaScript live-color interaction, native picker dialog, layout or accessibility behavior is claimed here.

One final private CLI harness initially assumed SNORDIAN's full web-admin hook name, while its minimal menu scaffold registered a different name. That harness run produced warnings/a fatal in the private test log. The lookup was corrected to find the actual registered callback; the final run passed. No deployed plugin code or SNORDIAN code was changed. Activation, the 75-check suite and the successful final run recorded no PHP warnings. The earlier private log was retained rather than erased.

## 8. Final stored state and preservation

- Plugin remains active for the user's Firefox acceptance.
- Theme aggregate `pjh5pwp_theme_library`: schema 1, revision **15**, autoload **off**.
- Global default: **H5P Core defaults**.
- Custom themes remaining: **0**. Both temporary acceptance themes were deleted through domain operations.
- Original optional built-in publication flags remain true. Two deleted test identities remain false tombstones by domain design; they do not appear as available themes.
- SNORDIAN remains active, Daylight/large, with its option/version hashes unchanged.
- All 60 tables other than wp_options retain their original structures, row counts and data checksums, including all H5P content/results/libraries and all WordPress users/user metadata/posts.
- wp_options has 309 rows: one new theme aggregate. Only these five option names differ from the deployment baseline:

```text
active_plugins
pjh5pwp_theme_library
cron
_site_transient_update_plugins
_site_transient_timeout_wp_theme_files_patterns-a02fe29c9ff1f4cad8238abcd25f82bb
```

The three transient/cron changes were first observed on native bootstrap before plugin activation and are normal WordPress housekeeping. The activation list and our single aggregate are the explicitly authorized application changes. No other option values/autoload settings changed.

All **8,687 pre-existing WordPress files** are unchanged. Exactly **43 approved plugin files** were added and match the manifest; the installed plugins inventory is now 508 files (465 originals + 43 additions). The six other protected targets, including both Backdrop source trees, deployed Backdrop Themer/configuration and CMS settings, match their prior protected hashes. No original protected file was modified.

The verified recovery SQL dump remains available and hash-valid. No temporary client credentials remain. No rollback or source data restoration was needed. No other table engine was changed; no schema migration or alternate storage table was introduced.

## 9. Evidence and limitations

Private deployment evidence:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

Contains preflight/deployment results, native pre-/post-activation policy results, native menu/preview/hook results, the 75-check/race report, fresh-process final report, before/final database and option-hash audits, file-preservation audit and retained private diagnostic log. These helpers/records are outside the web root and excluded from the plugin payload.

Evidence inspected for this final handoff:

| Artifact | Evidence |
| --- | --- |
| rehearsal-final/backup-verified.json and acceptance.example.test-consistent.sql | Successful 61-table restore proof; SQL SHA-256 rechecked read-only and still matches |
| conversion-result.json | InnoDB conversion, equal 308-row checksums and unchanged other 60 tables |
| deployment-result.json | Exact 43-file copy/hash verification; activated=false describes the copy step only |
| activation-result.json | Subsequent activation success, retained existing active entries and empty warning list |
| native-after-activation.json | Fresh bootstrap, supported H5P and four SNORDIAN detection signals; runtime suspended |
| native-admin-readonly.json | Separate menus, saved preview generation and original SNORDIAN asset paths |
| native-admin-persistence.json | 75 checks; Mint worker rejected with storage_conflict, Dark worker committed; final revision 15 |
| native-final.json | Fresh storage reload, scoped assets, SNORDIAN read-only settings render and suspended policy |
| final-audit.json and final-option-hashes.json | Only five documented option changes; no other table changes |
| final-file-preservation.json | All 8,687 prior files unchanged; exactly 43 expected additions; unchanged SNORDIAN config hash |
| wordpress-php-errors.private.log | Earlier private CLI callback-lookup failure; retained and explained above, not an activation failure |

The backup/conversion artifacts are in the verified-run directory identified in
sections 2–3; the remaining artifacts are in the deployment directory above.
No missing browser evidence has been inferred from these native results.

The [HTTPS investigation](phase-1cc-https-investigation.md) remains applicable: the in-app browser does not share the user's Firefox CA/exception setup and the leaf also lacks SAN. No attempt was made to fix or bypass either issue. The user will test visually in normal Firefox.

No product-code defect was found by the available native checks. Browser login/HTTP nonce transport, rendered layout, live/reset JavaScript, actual H5P playback and Firefox accessibility remain manual acceptance items. No broad cross-browser/library certification is claimed.

## 10. Rollback information — recorded, not executed

No rollback was needed or performed. The verified SQL backup is the complete
**pre-conversion/pre-deployment** database, so restoring it would also revert
activation/theme-library changes and discard later work. It must not be imported
as routine manual-test cleanup.

Recovery material remains in the private directories recorded above:

- Verified SQL and restore proof: section 2's rehearsal-final directory.
- Original options schema/rows: options-original-schema.json and
  options-original-rows.private.json in that directory; before/after conversion
  snapshots in its parent.
- Verified file backup: acceptance.example.test-files.zip in
  `PRIVATE_EVIDENCE_OMITTED`, as documented in the preflight.
- Deployment manifest and final audits: staged-release/deployment directories.

If a later Firefox result requires recovery, stop writes and preserve the current
state/logs first. Follow the documented preflight procedure: consider only our
plugin's deactivation/quarantine, preserve all other activation entries and
SNORDIAN settings, and export new theme data before any cleanup. An engine reversal,
option/database restore or full file restore requires a fresh review and explicit
authorization; do not overwrite intervening unrelated changes. None of these
actions is authorized by this documentation-only handoff.

## 11. Manual Firefox acceptance — NOT YET TESTED

Use the [concise Firefox checklist](phase-1cc-firefox-checklist.md).

Open **H5P Content → Papi Jo H5P Themes** in the user's normal Firefox session:

```text
https://acceptance.example.test/wp-admin/admin.php?page=pjh5pwp-themes
```

Verify catalog/immutable previews; copy/edit/rename and native color groups; live/clear/reset previews; publication/default/deletion behavior; stale tabs/access; and unchanged SNORDIAN/official/Papi Jo playback. Inspect SNORDIAN settings without saving. No Initialize action is needed: storage is already ready.

**Exact first step:** in your normal Firefox profile, open the URL above, sign in
with your existing WordPress administrator account if needed, and confirm that the
Papi Jo H5P Themes overview loads with the five built-ins, Core default and SNORDIAN
warning. Do not initialize again, deactivate SNORDIAN or change certificate settings.

## 12. Final Phase 1C-C status

**Deployment and authorized automated/native verification: COMPLETE — PASS.**
**Manual Firefox acceptance: NOT YET TESTED — awaiting user results.**

Current handoff state: deployed/active plugin; wp_options InnoDB; initialized schema
1/revision 15/autoload off; Core global default; zero custom themes; SNORDIAN active
and unchanged; Papi Jo runtime suspended. No unresolved product defect was found
by the native checks. Browser presentation, live-preview/reset behavior, HTTP
session/nonce transport and actual playback coexistence remain unverified manually.
The documented in-app TLS limitation remains unchanged and was not bypassed.

Only this report and the manual checklist were edited during finalization. No site
files/data, plugin activation/settings, Apache/certificates or production code were
changed, and no completed tests were repeated. No commit, tag, push or publication
occurred. **Stop here and wait for the user's Firefox acceptance results; do not
propose or start the next development phase.**
