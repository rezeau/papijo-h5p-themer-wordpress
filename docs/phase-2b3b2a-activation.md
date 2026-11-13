# Phase 2B-3B-2A — Controlled Runtime Activation

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **CURRENT STATUS: authorized retry SUCCEEDED. Papi Jo plugin
ACTIVE, runtime ENABLED, SNORDIAN INACTIVE.** Preferences/content remain unchanged.
Manual Firefox acceptance is pending. See the retry record at the end of this report.

The following sections retain the original attempt's historical evidence.
**Original activation attempt STOPPED; prior safe state restored and
verified. Papi Jo runtime was never enabled.** A verification-helper error after
SNORDIAN deactivation triggered the prepared rollback before the runtime flag was
installed. This was a helper comparison defect, not a demonstrated plugin regression.
Per the requested failure/rollback/stop procedure, no second activation attempt was
made. Manual Firefox runtime acceptance was not performed.

## Preflight

Read the activation preflight, targeted DIV correction and dormant deployment
reports, and runtime attachment/transport/ownership contracts. Inspected the actual
installed bootstrap and gate rather than inferring names from earlier instructions.

Exact installed Papi Jo entry:

`PROJECT_ROOT/papijo-h5p-themer-wordpress.php`

Exact basenames:

- Papi Jo: `papijo-h5p-themer-wordpress/papijo-h5p-themer-wordpress.php`
- SNORDIAN: `snordians-h5p-themer/snordians-h5p-themer.php`
- Official H5P: `h5p/h5p.php`

Preflight passed: all three plugins active; H5P 1.17.10 / Core API 1.28 supported;
SNORDIAN 0.0.12 detected; runtime constant undefined. All 63 installed Papi Jo files
matched source, including the deployed Phase 2B-3B-1C registry correction, hash
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.

Global Theme remained H5P Core, library revision 31. Administrator ID 1 retained
Site default / Original, profile revision 1. Role permission revision 3 and saved
preferences were unchanged. Native read-only administration/previews/profile checks
passed 43 checks. Four existing frontend pages returned 200 with expected H5P and
SNORDIAN assets through the guarded native renderer.

These are automated native/bootstrap/render checks, not browser-executed playback
or DIV initialization proof. Prior user-reported dormant Firefox acceptance remains
historical evidence; no new visual acceptance is claimed.

## Activation mechanism and prepared configuration

The actual existing gate in `Runtime\Theme_Attachment` is the strict boolean PHP
constant `PJH5PWP_ENABLE_BROWSER_THEME`. It must be defined before Plugin boot.
It controls attachment registration and manifest output, with fresh compatibility/
SNORDIAN checks. It does not independently gate the appearance endpoint or stop
controllers already running in open tabs.

The documented plugin-local approach was prepared as a temporary site configuration
block in a staged copy of Papi Jo's own bootstrap:

```php
// Temporary local trial configuration: Phase 2B-3B-2A.
if ( ! defined( 'PJH5PWP_ENABLE_BROWSER_THEME' ) ) {
    define( 'PJH5PWP_ENABLE_BROWSER_THEME', true );
}
```

It respects an existing definition and changes no admission/controller code. The
repository's ordinary source bootstrap remains default-disabled. No wp-config,
external loader, Apache/PHP configuration or new runtime mechanism was introduced.
The staged bootstrap passed syntax on PHP 7.4.33, 8.3.18 and 8.4.22, but **was not
installed** because Step A's verification failed before Step B.

## Checkpoint and recovery artifacts

Local recovery directory, outside the web root:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

- `dormant-bootstrap.php`: fresh exact backup of the installed configuration-bearing
  bootstrap; readable and hash-verified.
- `activation-checkpoint.json`: exact prior active-plugin list, serialized
  `active_plugins` option record/autoload, relevant configuration hashes and full
  administrator profile record.
- `plan.json`: discovered paths, basenames and old/prepared hashes.
- `activated-bootstrap.php`: prepared configuration only, never deployed.
- File/data/schema snapshots: metadata/checksum evidence, not unnecessary full-site
  or full-database copies.

Dormant bootstrap SHA-256:
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.

Prepared, never installed bootstrap SHA-256:
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.

The original bootstrap was verified against its backup before the attempt and
again after rollback. Existing deployment recovery evidence was retained; no
whole-site copy or live database restore was made.

## Actions, failure and rollback

1. Invoked supported WordPress `deactivate_plugins()` for the exact SNORDIAN
   basename. No file/settings deletion or uninstall was requested.
2. A native SQL guard allowed only the active-plugin option UPDATE during the
   plugin-management call; other mutations stayed blocked. The helper then checked
   the resulting activation array.
3. That check compared against a numerically reindexed expected array. WordPress
   removes the selected entry with `unset()` and preserves remaining keys.
   SNORDIAN occupied index 6: expected remaining keys were incorrectly 0–6;
   WordPress preserves 0–5 and 7. Strict array comparison therefore failed despite
   the supported deactivation path's legitimate representation.
4. The script immediately restored the dormant bootstrap and called supported
   WordPress `activate_plugin()` for SNORDIAN. The enabled bootstrap was never
   copied. Reactivation succeeded with one permitted activation-record UPDATE,
   no PHP warnings and no other allowed writes.
5. A fresh PHP request independently verified all SNORDIAN signals active, Papi Jo
   active and the runtime flag undefined. No same-request pre-reactivation policy
   snapshot was used as proof of the final state.

| State | Initial | Temporary Step A | Final verified |
| --- | --- | --- | --- |
| Papi Jo plugin | Active | Active | **Active** |
| Runtime constant | Undefined | Undefined | **Undefined** |
| Papi Jo runtime | Disabled | Disabled | **Disabled** |
| SNORDIAN | Active | Temporarily deactivated | **Active** |

No new runtime code, theme selection, Density selection, H5P content, fixture page,
dependency update or configuration repair was introduced. The temporary activation
record change was restored. No retry was made after rollback because the prompt
requires reporting and stopping following an unsuccessful activation attempt.

## Final integrity and smoke verification

- **43 native read-only checks PASS** after rollback: WordPress loads; Papi Jo
  administration, catalog previews and existing profile/role rendering remain intact;
  supported H5P/SNORDIAN states and default-disabled runtime are confirmed.
- Four existing frontend pages (400, 403, 407, 409), including `/aaa/` and
  `/fitb-pj/`, return 200 with SNORDIAN assets and without Papi Jo controller
  attachment in guarded native HTTP rendering.
- All **62 table row counts and data checksums match the preflight snapshot** after
  rollback. Activation, all options, preferences, profiles, posts and all H5P tables
  match. No unrelated background changes were restored or overwritten.
- All **62 structural metadata snapshots match**, including AUTO_INCREMENT values.
  No migration, schema edit or content operation occurred.
- All **8,750 acceptance.example.test file hashes** match their baseline: zero additions, removals
  or changed files, including all protected components and deployed Papi Jo code.
- All **155 Backdrop files** match with zero additions/removals/changes.
- All pre-existing nonprivate repository source files match their baseline. This
  report is the only new nonprivate source file; private checkpoint helpers were
  not deployed.

The native HTTP runner blocks SQL mutations and disables H5P aggregation only in
its disposable process to prevent generated cache files. It does not alter site
aggregation settings, execute player JavaScript, test visual Theme/Density changes
or establish successful DIV initialization. Final native checks report no PHP
warnings; the recorded fatal exception belongs to the verification helper, not a
WordPress/plugin application failure. Browser security and HTTPS settings were not
changed, and no browser automation was attempted.

## Exact rollback procedure for a later approved attempt

The performed rollback is verified above. For any separately authorized retry:

1. Preserve/recheck current state rather than assuming this checkpoint is still
   current; compare the exact target/backup hashes and activation record.
2. Remove the temporary constant block or restore the verified dormant bootstrap.
   Explicitly stop/close trial activity tabs, since removing attachment delivery
   alone does not revoke existing controllers or endpoint renewal.
3. Reactivate only the exact SNORDIAN basename through supported WordPress plugin
   management. Check its settings/version/role-capability state before and after;
   do not uninstall, delete settings or save its theme configuration.
4. Verify plugin states in a fresh request, restore/compare only the authorized
   activation/configuration state, and verify content/schema/protected integrity.
   Do not restore an entire database or unrelated background changes.

## Remaining issue and readiness

The activation helper must compare plugin membership or normalize both arrays
consistently, rather than expect WordPress to renumber preserved keys. Correcting
that inspection helper requires no protected-code or plugin runtime change. Its
corrected check should be demonstrated in memory before another state-changing run.

**The site is not currently enabled for Firefox runtime acceptance.** No visual
Theme/Density success or DIV initialization is claimed. Report the verified rollback
and wait for explicit authorization before another activation attempt. Personal
preferences remain Site default/Original and global Theme remains H5P Core.

Additional evidence: `native-checkpoint.json`, `native-reactivate.json`,
`native-admin-pre.json` (fresh restored-state inspection), `rollback.json`,
`inventory-before/after.json`, `database-before/after.json`, `schema-before/after.json`,
native HTTP captures and `final-verification.json` in the recovery directory above.

**Original attempt stopped with Papi Jo ACTIVE, runtime DISABLED, SNORDIAN ACTIVE.
The separately authorized retry below supersedes that historical final state.**

## Authorized retry — successful activation

The user explicitly authorized correcting the helper and retrying the same sequence.
Inspected installed WordPress `deactivate_plugins()`: it unsets the removed entry
and stores the remaining array without reindexing. The checkpoint and recorded
failure confirm SNORDIAN at index 6, leaving keys 0–5 and 7. There was no materially
different root cause or protected-code defect.

### Verification correction and regression

Added `tests/runtime/plugin-state-verification.php`, an operator-helper regression
module that is **not loaded by the production plugin or deployed**. It supplies
`pjh5pwp_same_plugin_members()`: require arrays of nonempty strings, equal counts,
no duplicates, then check each expected basename with strict `in_array()` membership.
Ordering and numeric keys are irrelevant. Missing/extra/wrong entries still fail.

The copied retry helper uses that function both for checkpoint-drift comparison and
post-deactivation membership, retaining WordPress `is_plugin_active()` checks and
the exact one-authorized-UPDATE check. The original failed-run evidence was preserved.

**Ten regression cases PASS on each of PHP 7.4.33, 8.3.18 and 8.4.22**: sparse keys,
different order, missing/wrong/duplicate entries, invalid types, empty identifiers,
empty lists, and the exact old false-failure reproduction. The old positional
comparison fails for that reproduction while membership succeeds and SNORDIAN is
absent. Retry-helper syntax passes; prepared configuration syntax passes on all
three PHP versions. No runtime-controller code was changed.

### Fresh baseline, checkpoint and execution

Fresh recovery directory:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

New preflight verified all 63 installed files against source, including the deployed
DIV correction; native administration/profile checks passed 43 checks; four guarded
frontend responses passed with runtime absent and SNORDIAN present. Global Core,
administrator Site default/Original, role permissions and safe plugin states were
reconfirmed. A fresh exact bootstrap backup and activation record were captured and
verified in `dormant-bootstrap.php`, `activation-checkpoint.json` and `plan.json`.
No unnecessary full-site/database copy was made.

Executed in order:

1. Supported WordPress `deactivate_plugins()` removed only the exact SNORDIAN
   basename. One active_plugins UPDATE was permitted; membership/API checks passed
   with non-sequential keys.
2. A fresh PHP request verified SNORDIAN inactive, no loaded SNORDIAN detector
   signals, and fresh compatibility admission permitted while the gate was absent.
3. Atomically installed the already documented temporary strict-boolean constant
   block in Papi Jo's deployed bootstrap. No new runtime mechanism was introduced.
4. A fresh request verified the flag strictly true, Papi Jo/H5P active, SNORDIAN
   inactive, attachment hooks present, Papi Jo administration rendering and unchanged
   configuration/profile hashes. Administrator appearance is admitted Core/Original.
5. Completed guarded native HTTP and final data/schema/file audits successfully.

**No rollback was needed or performed during this retry.** The original attempt's
rollback remains recorded above. Retry recovery artifacts remain ready.

### Final state and integrity

| Item | Final verified state |
| --- | --- |
| Papi Jo plugin | **ACTIVE** |
| `PJH5PWP_ENABLE_BROWSER_THEME` | **Boolean true in deployed Papi Jo bootstrap** |
| Papi Jo attachment/runtime gate | **ENABLED** |
| SNORDIAN | **INACTIVE; installed files/settings retained** |
| Global Theme | H5P Core, unchanged |
| Administrator preference | Site default / Original, unchanged |
| Role permissions | Unchanged |

Deployed bootstrap SHA-256 is now
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Its dormant backup remains
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Repository production source remains default-disabled and unchanged; the installed
bootstrap's temporary local configuration is a deliberate, recorded difference.
Do not overwrite that configuration inadvertently during a later deployment.

All 62 live tables retain their structures. **61 tables match exactly in row counts
and data checksums**; wp_options differs as expected for the authorized active-plugin
change. Native guards permitted exactly the one activation UPDATE and blocked other
writes. Papi Jo catalog/role option hashes, SNORDIAN settings/version, WordPress role
capabilities, all profile records, users/usermeta, post contents and all H5P tables
match the checkpoint. No schema, content, preference or unrelated repair occurred.

All 8,750 site files were compared: only our deployed bootstrap changed, with zero
additions/removals. Every other site file and all 155 Backdrop files match exactly,
including Core/H5P/SNORDIAN and wp-config. No protected component was changed.

### Read-only smoke checks and Firefox readiness

All four guarded ordinary frontend responses (400, 403, 407, 409) return 200, contain
one Papi Jo controller manifest and no SNORDIAN assets. Public manifests include the
correct DIV candidate 3 on pages 400/403 and internal iframe activities 5/6 on
407/409. This proves server attachment eligibility, **not browser initialization**.
The installed admin-ajax dispatch returns admitted Core/Original with empty color
declarations, private/no-store/CDN/proxy directives and no CORS/conditional-cache
disclosure. Native administrator resolution independently gives the same selections.

The guarded renderer blocks SQL mutations and disables asset aggregation only in
its disposable process; live H5P aggregation configuration is unchanged. It executes
no browser JavaScript. No visual Theme/Density or DIV initialization success is
claimed. No Firefox or other browser acceptance was performed automatically.

The inherited aggregate policy fields `runtime_enabled`/`runtime_implemented` still
report false, as previously documented. Actual enabled-state evidence is the strict
constant, registered attachment, admitted response and emitted public manifest;
those coarse fields were not changed or used as the activation switch.

The site is now ready for the user's separately performed Firefox acceptance.
Activity 3 remains a technical DIV fixture, not positive visual Theme acceptance;
`/fitb-pj/` remains the internal iframe fixture. Use fresh documents rather than
old SNORDIAN-loaded tabs. Preferences have not been changed for testing.

To roll back after further authorization, restore the verified dormant bootstrap,
stop/close trial tabs, reactivate only SNORDIAN through WordPress, and verify states
in a fresh request. Do not restore unrelated database state. The constant alone
does not stop controllers already running in open tabs.

Retry evidence includes `native-deactivate.json`, `native-verify-inactive.json`,
`native-verify-enabled.json`, `activation-result.json`, `http-post.json`, endpoint/
HTML captures, database/schema snapshots, `file-integrity.json` and
`final-verification.json` under the fresh recovery directory.

**STOP. Left enabled as authorized: Papi Jo ACTIVE, runtime ENABLED, SNORDIAN INACTIVE.
Await the user's manual Firefox results. No commit, tag, push or release occurred.**
