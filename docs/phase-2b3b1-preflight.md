# Phase 2B-3B-1 — Runtime Activation Preflight

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

8 November 2026. **Read-only preflight complete. Runtime NOT activated; SNORDIAN
NOT deactivated.** The user reports Phase 2B-3A Firefox acceptance **7/7 PASS**.
That supersedes the pending acceptance recorded in the historical deployment report;
it does not constitute acceptance of the still-disabled Papi Jo runtime.

**Finding requiring correction:** the deployed player registry excludes all three
existing DIV activities because their library `embed_types` is an empty string,
which installed H5P Core legitimately resolves as DIV. A memory-only probe confirms
the mismatch. Do not begin the planned DIV runtime acceptance until this plugin-only
defect is corrected and verified under separate authorization.

No implementation, deployment, preference/settings save, activation change, cache
clear, new database backup, live data write or protected-code modification occurred.

## A. Current environment and evidence

Inspected the actual deployed implementation and the five requested documents:
Phase 2B-3A deployment, Phase 2B-2E verification, controller, transport and appearance
contracts. The unique installed plugin directory is:

`PROJECT_ROOT/`

| Item | Current observation |
| --- | --- |
| Papi Jo / official H5P / SNORDIAN | All active; activation list unchanged |
| Versions | WordPress 7.1.4; H5P 1.17.10; Core API 1.28; SNORDIAN 0.0.12 |
| Runtime constant | Undefined in native bootstrap; no controller attachment hooks |
| Shared policy | Suspended for `snordian_active`; no actionable appearance for anonymous/current users |
| Catalog | Five pinned built-ins; library revision 31; no saved custom themes |
| Global Theme | Stored null selection, resolving to H5P Core |
| Role permission map | Revision 3, no nonautomatic grants; Administrator automatically authorized |
| Administrator | Existing user ID 1; saved `site_default` Theme and `original` Density; profile revision 1 |
| Profile table | Existing InnoDB table, two intact records; exact profile hashes match deployment |
| Existing content | 15 H5P activities; published ordinary pages 400, 403, 407 and 409 |
| SNORDIAN selection | Lavender / Large; settings/version records preserved |
| Deployment payload | All 63 installed file hashes still match qualified source |

The native read-only inspection passes 56 existing disabled-state/admin/profile
checks with no PHP warnings or blocked mutation attempts. It does not load H5P
content or render a new frontend page, avoiding the cache-generation side effect
found during the earlier deployment. Current playback functionality is supported
by the user's **7/7 Firefox acceptance**, not a new automated browser trial.

Direct database inspection uses a read-only session and unmodified installed Core's
static embed resolver. A path-only WordPress function seam allows loading its
definitions without bootstrapping the CMS. The defect probe uses isolated arrays
and callbacks; it does not admit a live player or enable any controller.

## B. Exact activation mechanism

`includes/Runtime/class-theme-attachment.php:13` enables attachment only when:

```php
defined( 'PJH5PWP_ENABLE_BROWSER_THEME' )
    && true === constant( 'PJH5PWP_ENABLE_BROWSER_THEME' )
```

This is a **PHP boolean constant**, not a WordPress option, role permission,
profile flag, cookie, browser storage value or existing administration switch.
Truthy strings/numbers do not enable it. It is currently undefined.

- `Plugin::boot()` constructs Theme_Attachment and calls `register()`.
- `register()` evaluates the gate before registering footer priority 19 and the
  own-script-tag filter. With the current default, neither hook is registered.
- `manifest()` rechecks the gate, then fresh shared admission, frontend context,
  public player candidates, count limit and same-origin endpoint.
- Enqueue/tag output revalidate the manifest. The parent script is static;
  public metadata contains only endpoint/content/embed/library identities.
- The browser independently qualifies main roots and consumes private responses.
  Only then can it apply approved colors and native Density classes.

**The constant does not gate the appearance endpoint.** Endpoint hooks register
regardless of attachment state. Its permission comes from fresh shared compatibility
policy and existing profile/role resolution. With SNORDIAN inactive, a valid request
can receive an actionable descriptor even if browser attachment is disabled.
Admission alone does not create a controller on a page.

The existing source has no installed UI or local control file for defining this
constant. Activation needs a definition before Plugin boot. Editing `wp-config.php`,
PHP/Apache configuration or adding an external loader is unnecessary and outside
the proposed scope. The smallest plugin-only trial mechanism is a clearly marked,
temporary boolean definition in **our own main plugin file**, after its ABSPATH
guard and before boot can run. A guarded definition must respect an existing
definition rather than override it. Remove that block to restore the present default.
This would be an explicitly approved source/deployment change; it was not made here.

**Immediate reversal has a limit:** removing/setting the definition false stops
attachment on freshly generated pages, but does not stop a controller in an open
tab, revoke its current descriptor, or suspend its endpoint renewal. Cached HTML
can also contain an earlier script tag. Use the explicit tab/endpoint/SNORDIAN
rollback procedure below. Do not describe the constant alone as an immediate
server-side kill switch.

If a server-controlled kill switch is required, the smallest additional plugin-only
design is to reuse one strict activation decision at both attachment and appearance
admission, returning a nonactionable suspended descriptor when disabled. It needs
focused default-off/late-disable/suspension tests and separate implementation
approval. It is not implemented or assumed by this trial plan.

## C. SNORDIAN identity and preservation

Exact WordPress identifier:

`snordians-h5p-themer/snordians-h5p-themer.php`

Installed directory:

`WORDPRESS_ROOT/wp-content/plugins/snordians-h5p-themer/`

Current native detection finds all four signals: active-plugin entry, version
constant, loaded namespace and original priority-10
`Snordian\H5PThemer\h5p_themer_alter_scripts` callback.

Relevant records are `snordiansh5pthemer_option` and
`snordiansh5pthemer_version` (currently 0.0.12). The selected Lavender/Large settings
and generated `h5p-themer-config.js` must remain untouched. Deactivation has no
registered plugin-specific deactivation hook. **Uninstall is different**: its
uninstall callback deletes settings and removes capabilities. Never click Delete,
run uninstall, remove callbacks manually or save/reset SNORDIAN settings.

Normal reactivation runs `on_activation()`, which checks H5P version and maps
`manage_h5p_theme_global_settings` from `manage_h5p_libraries`. Read-only comparison
of all seven current roles predicts **no capability changes**: every mapping already
matches. H5P's version option is 1.17.10, so the unsupported-dependency transient
branch is not expected. Recheck these invariants immediately before a future trial;
stop if they have changed. This is source analysis, not a simulated live reactivation.

## D. Proposed activation/deactivation sequence — NOT EXECUTED

Only after the DIV correction, test-page prerequisites and explicit trial approval:

1. Keep Papi Jo active. Verify current hashes, backups, administrator defaults,
   Core global default, unchanged role map and SNORDIAN Lavender/Large settings.
2. Close existing H5P activity tabs. Old SNORDIAN JavaScript sets document-level
   colors and H5PIntegration Density; deactivation cannot remove that state from
   an already loaded document. Do not reuse its history/BFCache as a clean baseline.
3. Apply the approved temporary definition in our plugin bootstrap **while SNORDIAN
   is still active**. Check a fresh request remains suspended and no controller tag
   is emitted. This stage cannot authorize concurrent styling through the normal
   fresh admission path.
4. In WordPress Plugins, use **Deactivate** for the exact SNORDIAN entry above.
   Do not uninstall or visit/save its settings. This necessarily changes WordPress's
   activation bookkeeping, not SNORDIAN's saved theme configuration.
5. Wait for that request to finish. Use a new ordinary frontend document and a fresh
   server request. Loaded SNORDIAN functions/constants cannot be unloaded halfway
   through the deactivation PHP request, so same-request observations are insufficient.
6. In Firefox bypass the page/browser cache for the test document. Confirm it contains
   no SNORDIAN script references, the Papi Jo controller loads once, the current
   appearance response is admitted, and Core/Original playback works before changing
   personal selections.

The activation gate is site-wide, not administrator-only. Coordinate the local test
window so other viewers/tabs do not receive an unintended experiment. Do not add a
new permission or audience feature merely for this trial.

For the final coexistence test, leave one **Papi Jo-only** trial document open,
reactivate SNORDIAN from administration, then explicitly refresh appearance in that
old document. It has not loaded SNORDIAN JavaScript, so server suspension can be
verified without two browser controllers styling that player. After nonactionable
suspension and restoration, close/stop it, disable the temporary gate and load a new
SNORDIAN-only document. Never inject SNORDIAN scripts into the old document.

## E. Existing backups and rollback

Verified existing recovery directory:

`PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED`

- Database dump: 15,937,230 bytes; SHA-256
  `PRIVATE_VERIFICATION_DIGEST_OMITTED` still matches.
- All 53 original plugin backup files and the 53-file restore rehearsal still match
  their recorded inventories.
- Existing database restore certificate covers 62 tables/5,855 rows, ten verifier
  negative controls and removed disposable restore database. No new restore or
  backup was necessary for this read-only phase.
- Current theme/role/profile state matches the deployment baseline. Options/usermeta
  bytes have drifted since deployment, consistent with subsequent site/session use;
  the backup remains a verified historical recovery point, not an exact copy of
  every current housekeeping/session record. Do not overwrite those later records
  merely to undo a runtime trial.

**Runtime rollback:** stop/close all trial controllers; remove the temporary Papi Jo
definition; reactivate the exact SNORDIAN plugin via WordPress; restore administrator
Theme to Site default and Density to Original through the authorized Profile UI;
verify global Theme remains Core; open fresh SNORDIAN-only pages and verify suspension.
If a tab is unresponsive, close it. Removing the gate alone is insufficient.

The existing public `stop()` method can be used as an explicitly authorized emergency
trial action in each activity tab:

```js
window['pjh5pwp-theme-controller']?.stop()
```

It restores only still-owned styling and tears down the controller. Its window
object remains present and cannot be restarted; reload for a new controller.
Ambiguous/foreign ownership still prevents unsafe restoration. Closing the document
and loading a fresh one avoids retaining that uncertain old DOM.

Restoring preference **values** does not mean rewinding the profile's CAS revision:
normal authorized saves advance revisions. Do not use SQL to restore an old revision.
Global Core needs no save if it was never changed.

**Full deployment rollback** is a separate contingency: restore the earlier 53-file
release and remove only the ten recorded runtime additions as documented in Phase
2B-3A. It is unnecessary for normal trial shutdown. The currently qualified source
also preserves the dormant bootstrap bytes for removal of a temporary activation
definition without reverting the entire deployment.

## F. Browser, HTTPS, H5P and OPcache

Use the user's normal Firefox profile, which already trusts `https://acceptance.example.test`.
Chromium and Python's stricter trust handling differ for the legacy local certificate.
No Apache, certificate, URL, browser-security or trust-setting change is needed or
proposed. No browser was navigated during this preflight.

No full-page cache plugin, `advanced-cache.php`, `object-cache.php` or local
`.user.ini` was found in the inspected site. The site's .htaccess contains ordinary
WordPress rewrite rules. This is local configuration evidence, not a guarantee about
every possible external cache. Fresh HTML is essential when removing SNORDIAN script
references or adding the public controller manifest. A Firefox cache-bypassing reload
or temporary Network-panel Disable Cache during an approved trial is sufficient as
the first check; no cache was cleared now.

The controller is outside H5P's dependency bundle. Changing its PHP gate or admitting
a DIV candidate does not inherently require deleting H5P dependency/aggregate caches.
Those caches can legitimately generate files/database rows on ordinary playback;
do not clear or regenerate them as a preflight probe. SNORDIAN asset hooks run after
Core dependency retrieval and must be absent from the newly generated test HTML.

Apache's PHPIniDir points to its `bin/php.ini`, a symlink to PHP 8.4.22's
`phpForApache.ini`. Read-only loading of that configuration reports OPcache enabled,
timestamp validation enabled, revalidation frequency 2 seconds and file-update
protection 2 seconds. CLI OPcache is disabled. These are configuration observations,
not a live web-SAPI cache inspection. After an approved PHP file edit, allow normal
timestamp revalidation and confirm behavior on a fresh request. A CLI OPcache reset
would not reset Apache's cache and is not a useful workaround. If stale behavior
persists, stop and investigate before any targeted web-SAPI invalidation; no global
reset, service restart or configuration change is pre-authorized.

## G. Diagnostic readiness

Existing Firefox developer tools are sufficient for a supervised first trial;
no permanent debugging interface is needed.

| Question | Existing observation |
| --- | --- |
| Controller downloaded? | Network request for `assets/js/theme-controller.js`, once in the parent; inspect its public manifest attribute |
| Controller constructed? | `window['pjh5pwp-theme-controller']` exists; this alone does not prove active admission |
| Root qualified? | `.diagnostics()` returns public content IDs and fixed statuses; `qualified` describes ownership/Theme readiness, not current authorization or Density success |
| Endpoint admitted? | Network POST `action=pjh5pwp_appearance` with fresh `request_id`, status 200 and validated `appearance.admission`; inspect actual private/no-store headers |
| Theme applied? | Inspector on the exact outer root: approved inline `--h5p-theme-*` values match the private response; compare computed values to the native baseline |
| Density applied? | Inspector on the exact outer root: native Small/Medium/Large token and computed `--h5p-theme-scaling`; unrelated/nested classes remain intact |
| Targeted resize? | Temporary Debugger breakpoint at controller `q.api.trigger(q.instance, 'resize')` (current line 204); inspect the qualified content ID/instance and call stack, then resume |
| Suspension? | Fresh response says `snordian_active`, null Theme/Density and empty declarations; old Papi Jo-only tab releases still-owned changes; fresh SNORDIAN page has no Papi Jo tag |

Read-only inspection examples for the future trial:

```js
window['pjh5pwp-theme-controller']?.diagnostics()
```

Use Firefox's iframe execution/Inspector context for internal-frame roots. Do not
select a nested child or assume `H5P.instances[0]` identifies the target. An unchanged
Large/Original class set can correctly cause no resize. A refresh may restore/reapply
both dimensions and therefore produce more than one real-change resize; no-op scans
must not loop. Do not monkey-patch H5P or add permanent counters.

The aggregate PHP diagnostics currently hard-code `runtime_implemented=false` and
`runtime_enabled=false`, even if attachment is experimentally enabled later. They
are not reliable proof of browser activation. Use Network/root evidence for this
supervised trial. Coherent aggregate reporting remains a separate plugin-only
improvement before production activation, as already recorded in Phase 2B-2E.

## H. Smallest meaningful first Firefox test — PLANNED ONLY

After correction and explicit activation approval, keep the global Core default and
administrator Site default/Original unchanged. Use the existing single-iframe page
[Guess It](https://acceptance.example.test/?page_id=409), activity 6, as the first candidate;
[Advanced Blanks](https://acceptance.example.test/?page_id=407), activity 5, is another candidate.
Confirm the actual initialized root has the required native Theme container,
computed variables, unique registered main instance and event API. If qualification
fails, stop and report it; do not force a Theme class or weaken readiness checks.

First verify ordinary interaction/layout with an admitted Core/Original descriptor:
empty Papi Jo colors and preservation of the initialized native Density, often Large.
Original does not mean Small. Then save only personal Lavender, keeping Original,
and return focus to the activity tab. Verify the fresh private descriptor, root
variables and visible change. This separates a working transport from a merely
loaded script. Restore Site default before ending this first small test.

## I. Complete staged acceptance sequence — NOT EXECUTED

| Step | Action and expected observation |
| --- | --- |
| 1. Clean baseline | Administrator, global Core, Site default/Original, fresh Papi Jo-only document, ordinary playback and admitted Core descriptor |
| 2. Built-in Theme | Personal Lavender + Original; approved root variables apply, native Density remains its baseline |
| 3. Explicit Core | Personal H5P Core + Original; owned color overrides release; then return to Site default |
| 4. Density | With Core appearance, separately save Small, Medium and Large; native tokens/scaling change and resize targets the proved main instance |
| 5. Original | Restore Original; recover the captured native state, including initialized Large, without assuming Small |
| 6. Independence | Lavender + Small; change only Theme to Mint, then only Density to Medium; final unchanged dimension stays selected. Record transient restoration/flicker during refresh |
| 7. Internal iframe | Verify same-origin Core-written about:blank frame, child document root and layout/fullscreen/height behavior |
| 8. Positive DIV | After registry correction and an ordinary frontend test-page prerequisite, verify activity 10 (QuestionSetPapiJo 1.23) under the same Theme/Density sequence |
| 9. Multiple activities | Pages 400/403 contain distinct activities; verify each eligible main root separately and preserve nested/unsupported roots. Do not claim every library must visibly consume native variables |
| 10. Navigation | Reload, leave/return, back/forward, pageshow/BFCache and focus/visibility; require fresh admission and correct ownership, without repeated-event request storms |
| 11. Endpoint/cache | Inspect same-origin cookies, exact POST fields/correlation, 30-second request-start age, private/no-store/CDN headers and absent preference values in shared HTML/H5PIntegration/assets |
| 12. Suspension | Keep one Papi Jo-only tab, reactivate SNORDIAN in admin, explicitly refresh appearance, verify nonactionable suspension and still-owned restoration; do not load SNORDIAN into that old document |
| 13. Finish | Stop/close trial tabs, remove temporary gate, restore personal Site default/Original, verify global Core and untouched role/SNORDIAN settings, then open fresh SNORDIAN-only pages |

Profile changes use the existing own-Profile UI in a separate tab, never client-supplied
user IDs or browser storage. Return focus to the activity to revalidate; for an
explicitly authorized diagnostic transition the existing `refresh()` method can
request immediate revalidation. It accepts no identity or preference argument.

**DIV prerequisite:** current activities 3, 10 and 13 resolve as DIV with blank
library embed metadata. Activity 10 has source evidence of a native `.h5p-theme`
container. Its only published shortcode hosts are posts 45/49 of types `lp_lesson`
and `lp_quiz`, which are **not registered in the current WordPress bootstrap**.
Do not assume those are usable ordinary frontend test pages or activate another
plugin to make them accessible. Existing public pages 400/403 contain DIV activity
3, AdvancedTextPapiJo, whose attach code does not add a native Theme container; use
it as an unsupported/readiness control, not guaranteed positive Theme/Density proof.

For positive DIV acceptance, separately authorize a disposable ordinary WordPress
page embedding existing activity 10, or identify another already accessible,
native-ready ordinary DIV host. Do not edit H5P content or alter existing pages to
manufacture a pass. No page or content was created/changed during this preflight.

## J. Risks and limits

- The confirmed blank-embed metadata defect blocks the intended existing DIV path.
  Fixing admission does not override browser ownership/native-readiness exclusions.
- No in-memory probe, native bootstrap check or prior dormant Firefox acceptance
  proves the enabled runtime. Every acceptance row above is still unexecuted.
- Old SNORDIAN or Papi Jo documents can survive activation changes or BFCache.
  Fresh documents, endpoint checks and explicit tab cleanup are essential.
- Removing the current constant alone is not endpoint suspension. Successful visible
  controllers can renew; rely on actual stop/close and fresh SNORDIAN policy for rollback.
- Refresh releases both dimensions before fetching, so Theme-only changes can
  briefly restore Density and resize. Evaluate visual impact rather than claiming
  uninterrupted independence. Final choices remain independently resolved.
- CSS inheritance, native class/scaling consumption and asynchronous resize behavior
  vary by library. Ambiguous owners and observationally identical foreign writes
  retain the conservative limitations described in Phase 2B-2E.
- Routine authenticated Firefox navigation can cause normal WordPress session/cache
  housekeeping. The future trial needs an explicit write scope and before/after
  comparison; it must not promise byte-identical whole-database state after normal
  profile/activation operations.

## K. Exact proposed subsequent changes

Nothing in this table has been changed now.

| Surface | Proposed scope, requiring approval |
| --- | --- |
| Source and installed `includes/Runtime/class-playback-registry.php` | Minimal acceptance of the exact empty string as valid Core embed metadata; retain type, ID, library, wrapper, context, duplicate and ownership checks |
| Source tests, especially `tests/runtime/transport.php` | Focused regression against actual Core's blank-metadata DIV resolution, plus malformed metadata and duplicate exclusions; no live tests/data writes |
| Source and installed `papijo-h5p-themer-wordpress.php` | Temporary, clearly marked strict boolean constant definition before boot; remove for rollback; respect an existing definition |
| `wp_options.active_plugins` | Temporarily remove only the exact SNORDIAN identifier, then restore it via WordPress; normal UI may also update `recently_activated`/activation bookkeeping |
| Administrator row in `wp_pjh5pwp_profiles` | Authorized personal Theme/Density saves for the acceptance matrix, then Site default/Original; revision advances naturally; other users untouched |
| Disposable WordPress test-page records, if needed | Explicitly approved ordinary page embedding existing H5P activity 10; no H5P activity/library edit; record ID and agreed cleanup scope |
| Global library, role map and SNORDIAN settings/version | **No planned changes**; compare before/after. Current SNORDIAN activation capability mapping predicts no role-record change |
| `wp-config.php`, Core/H5P/SNORDIAN/Backdrop files, Apache/PHP/HTTPS configuration | **No planned changes** |

Keep source and deployed plugin hashes aligned in the separately authorized
implementation/deployment step. Do not define the gate in wp-config or modify
protected code as a convenience.

## L–M. Correction proposal and recommendation for Phase 2B-3B-2

The deployed `Playback_Registry::observe()` rejects the exact empty `embedTypes`
string before calling Core. Installed Core's `determineEmbedType()` deliberately
leaves the requested DIV/iframe type unchanged when that library field is empty.
The actual loadContent path copies the database field unchanged into the library
metadata. The memory-only reproduction using activity 3's real metadata returns
Core DIV but no candidate; changing only the isolated metadata to `div` yields one
candidate. Both paths leave wrapper bytes unchanged.

**Smallest correction:** allow exactly `''` alongside the already accepted nonempty
grammar, still require a string, then delegate to the existing Core resolver. Do
not accept arbitrary text/null, loosen wrapper identity, force a Theme class, infer
a root from dependencies or bypass suspension. Add a focused regression and rerun
affected qualification tests. This needs no protected-code modification.

Recommend Phase **2B-3B-2 first correct and verify this plugin-only admission gap**,
confirm a usable ordinary positive-DIV test host, and prepare/review the exact
temporary activation diff and runtime rollback procedure. Keep runtime disabled
and SNORDIAN active through that preparatory work unless the next authorization
explicitly includes the actual trial, activation toggle, personal preference writes
and any disposable page creation. Broader Phase 2B-3B authorization does not override
this preflight's stop boundary.

Existing developer tools suffice for supervised diagnostics. A shared endpoint
kill gate or aggregate status refinement may be separately approved if desired;
neither is silently included in the one-line experimental activation mechanism.

## Integrity and stop record

Before/after audits cover all **8,750 acceptance.example.test files**, **155 Backdrop files**, and
the inspected Apache/PHP/public-certificate configuration files. Expected and final
result: no changes/additions/removals. All 63 deployed payload hashes and existing
backup/restore-rehearsal hashes match. All **62 live table structures, row counts
and checksums** remain unchanged during this preflight. Existing repository source
and documentation are preserved; this report is the only new nonprivate source file.

Private inspection evidence (not new backups) is under
`PRIVATE_EVIDENCE_OMITTED`: file/database snapshots,
`current-state-before.json`, `native-admin-post.json`, `domain-comparison.json`,
`admission-probe.json`, `page-map.json`, `post-type-readiness.json`,
`opcache-settings.json`, backup-hash checks and `final-verification.json`.

**STOP. No runtime activation, SNORDIAN deactivation, deployment, cache clearing,
live data/configuration change or implementation was performed. Await explicit
approval before any subsequent modification or runtime trial.**
