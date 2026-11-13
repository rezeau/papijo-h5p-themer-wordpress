# Phase 2A — user permissions and profile preferences

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

**Historical initial implementation report.** Phase 2A-H subsequently replaces
the unsafe usermeta write loop with an approved source-only dedicated InnoDB
adapter. See [current hardening results](phase-2ah-verification.md). Deployment
remains paused; the four-key storage and limitations below describe the prototype.

7 November 2026. **Source implementation and isolated tests: PASS. Deployment:
NOT PERFORMED. Native/browser profile acceptance: NOT YET TESTED.**

The user confirms the original 11 Firefox acceptance steps and four Phase 1C-D
rechecks passed. This is user-reported baseline evidence, not new profile evidence.
Reviewed architecture.md, theme-domain.md, wordpress-persistence.md,
administration.md, phase-1cc-acceptance.md and phase-1cd-verification.md, plus the
installed WordPress profile flow, before implementing Phase 2A. No dependency,
SNORDIAN suspension, preset, domain-schema or aggregate storage change was needed.

## Files created and modified

Created:

- `includes/profiles.php`
- `includes/Profile/class-profile-service.php`
- `includes/Profile/class-profile-controller.php`
- `tests/profile/run.php`
- `docs/phase-2a-verification.md`

Modified:

- `papijo-h5p-themer-wordpress.php` — load inert profile definitions.
- `includes/class-plugin.php` — compose/register lazy profile services.
- `includes/Admin/class-diagnostic-notices.php` — accurate implementation status.
- `includes/Admin/class-admin-view.php` — accurate profile/publication help only.
- `tests/worker.php` — admit exactly four new profile hooks; preserve all former
  assertions against foreign mutations and playback/activity hooks.
- `tests/admin/coexistence.php` — add profile planning/rendering under each
  independent SNORDIAN detection signal.
- `README.md`, `readme.txt`, `docs/architecture.md` — current scope and boundaries.

Private verification evidence is retained in
`PRIVATE_EVIDENCE_OMITTED`; it is excluded from deployment.
No preset, theme domain, aggregate persistence, asset or runtime implementation
was changed. Plugin version remains 0.1.0. No commit/tag/push/publication.

## Verified WordPress lifecycle and implementation

Read-only inspection used the actual WordPress 7.1.4 sources:

- `wp-admin/user-edit.php`: `update-user_$user_id` nonce and `edit_user` capability
  checks precede `personal_options_update` / `edit_user_profile_update`, and those
  actions precede `edit_user()`. Therefore they are too early for persistence.
  `show_user_profile` renders the own Profile page and `edit_user_profile` renders
  another user's Edit User page.
- `wp-admin/includes/user.php`: `user_profile_update_errors` receives the error
  object, update flag and target user. Any accumulated error returns before
  `wp_update_user()`.
- `wp-includes/user.php`: `profile_update` fires after an existing user is updated.

`Profile_Controller` registers only show/edit rendering, late
`user_profile_update_errors` validation and `profile_update` persistence. Validation
queues a request only when it and core validation have no errors. A failed core
update never reaches its commit. Commit consumes a target-specific pending request
and rechecks capabilities, nonce/context, current grants and available selections.
It does not save on unrelated programmatic user updates without a queued request.

`Profile_Service` reads metadata and a lazy authoritative library snapshot, plans
only explicitly submitted fields, and writes only its fixed metadata allowlist
using `update_user_meta`. Constructors, registration, rendering, resolution and
activation never create metadata or initialize theme storage. No SQL is added.

## Metadata and defaults

| Key | Meaning | Stored values / absent behavior |
| --- | --- | --- |
| `pjh5pwp_allow_theme` | Administrator permission | Exact string `1` grants; `0` denies; missing/malformed denies |
| `pjh5pwp_allow_density` | Independent administrator permission | Same exact-grant rule |
| `pjh5pwp_theme` | Personal preference | `site_default`, `core`, `builtin:<id>`, `custom:<id>`; missing effectively follows global |
| `pjh5pwp_density` | Independent personal preference | `original`, `small`, `medium`, `large`; missing/malformed effectively original |

Permission and preference fields are separate keys, not roles or capabilities.
There is no metadata initialization/migration and no REST registration/exposure.
Existing malformed values do not grant permission or become CSS. An omitted
field is preserved, not interpreted as revocation/reset. Checkbox forms include
an explicit hidden `0` for each rendered permission, so unchecking is intentional.
The two grants and the two preferences can be updated independently.

## Authorization and forms

- All operations require single-site and `current_user_can('edit_user', target)`.
- Permissions are managed on another user's Edit User page only, requiring both
  `manage_options` and `edit_users`. The own Profile page exposes preferences,
  never permission grants, including for an administrator editing their own profile.
- Preferences require authenticated identity equal to the target and the relevant
  freshly read exact grant. An administrator editing another user can manage
  permissions but cannot silently replace that user's personal preferences.
- A plugin nonce binds the target and SHA-256 of all four raw metadata values.
  It supplements WordPress's core profile nonce. Permission/preference changes
  invalidate a previously rendered request. Nonces never replace authorization.
- Input is unslashed once, accepts only a nested fixed field allowlist of strings,
  rejects invalid flags/tokens/arrays/nulls and validates the entire plan before
  saving any member. Target identity comes from WordPress's hook, not our payload.
- Labels, values and nonce attributes are escaped. Failed validation reports a
  WordPress profile error; safely handled submitted fields retain their original
  nonce rather than silently rebasing stale requests. Reload is required after a
  stale/expired context. Missing plugin fields on unrelated updates do nothing.

Four combinations render: neither grant => no personal controls; theme only =>
theme selector; Density only => Density selector; both => both. No activity forms,
AJAX/REST endpoints, user-list bulk controls or new capabilities are added.

## Theme availability and fallback

Profile choices are Use global default, H5P Core, five stable built-ins and
published/resolvable custom themes. They use `Publication_Policy::catalog` and
`resolve_learner`, with one explicit Phase 2A adaptation: **all five built-ins
are always available in profiles**, even if an older administration publication
checkbox disabled one. The request explicitly requires all five. Only the
profile-local policy copy changes; stored publication/domain rules are untouched.
Administration help explains this distinction. Custom publication is never bypassed.

Explicit `core` remains distinct from `site_default`. Unpublished/deleted/invalid
personal references effectively follow the current global, and a broken global
falls back to Core. A hidden custom global can still be used through global
selection, as established by the domain's independent default eligibility rules.

An unavailable saved preference remains stored and is not exposed as an option.
The selector displays a generic **Keep saved preference (currently uses global
default)** choice, alongside valid replacement options. `keep_saved` is a form-only
sentinel, never saved; it is accepted only when the stored token is valid-shaped
and currently unavailable. Thus unrelated profile/Density saves preserve that
reference, while selecting a replacement is explicit. Republish/restoration can
make a preserved preference effective again. Revoking either grant retains its
preference but disables its effective use; restoring the grant restores eligibility.

Missing or malformed aggregate/backend failure exposes only safe fixed choices
and effectively falls back to Core. It never initializes or repairs the library.

## Native Density semantics and runtime separation

The selector has exactly Original Density, Small, Medium, Large, stored as
`original`, `small`, `medium`, `large`. Original means no added override; theme
definitions' legacy density fields never contribute to this preference.

Verified installed Core 1.28 `styles/h5p-theme-variables.css`: unqualified content
uses native small variables; `.h5p-content.h5p-medium` and `.h5p-content.h5p-large`
select native variants. The read-only Backdrop reference
`js/h5p_themer.native-density.js` recognizes `h5p-small`, `h5p-medium`, `h5p-large`
and restores a player's captured original when no override is selected. These
are future runtime references, not copied/applied code in this phase.

No classes, spacing/font algorithm, CSS, player resizing, iframe transport or
Density controller is implemented. `effective()` returns selection data only.
SNORDIAN remains independent; all runtime consumers in a future phase must still
pass the unchanged common suspension policy, even if profile settings permit use.

## Automated tests and regressions

All passed separately on **PHP 7.4.33, 8.3.18, 8.4.22**:

| Suite | Result per PHP version |
| --- | --- |
| Phase 2A isolated profiles | 124 assertions |
| Phase 1A foundation / actual installed H5P source admission | 18 scenarios, 513 assertions |
| Phase 1B domain / authoritative Backdrop parity | 2,315 assertions, including 919 parity assertions |
| Phase 1C-A persistence API doubles | 204 assertions |
| Phase 1C-B/1C-D administration | 304 assertions, including 94 refinement assertions |
| SNORDIAN coexistence | 4 independent signals, 44 additional assertions |
| Syntax | All 59 source/test PHP files |

Existing isolated JavaScript DOM suite: **30 assertions PASS**. No browser test is
claimed. The 72 added foundation assertions admit the four new profile hooks in
18 scenarios; earlier assertions were retained. Eight coexistence assertions add
profile planning/rendering under active SNORDIAN without foreign option/callback
changes and with runtime suspension preserved.

Profile tests cover four combinations, exact/malformed defaults, independent grants,
administrator restrictions, learner own-account service, cross-user denial,
forged mixed payloads, nonce/context failures, missing fields, core validation
failure, delayed persistence, failed-write reporting, invalid identifiers, all
built-ins, published/unpublished/deleted custom references, retention/replacement,
Core versus global, four Density values, legacy isolation, revocation/restoration,
output escaping, storage-read failure and unrelated/forged new-user hook calls
without an existing user ID. Tests use isolated metadata/API doubles.

Run with each available PHP binary:

```powershell
$phpPath = 'PHP_CLI'
& $phpPath -n tests/profile/run.php
& $phpPath -n tests/run.php 'WORDPRESS_ROOT/wp-content/plugins/h5p'
& $phpPath -n tests/domain/run.php 'BACKDROP_REFERENCE_ROOT'
& $phpPath -n tests/storage/run.php
& $phpPath -n tests/admin/run.php
& $phpPath -n tests/admin/coexistence.php
node tests/admin/browser-behavior.js
```

## Protected files and safety

Before/after recursive SHA-256 manifests match for **903 protected files**:
authoritative Backdrop source 154; original source 65; installed Backdrop Themer 72;
entire acceptance.example.test plugins tree 508; Backdrop active configuration 102; wp-config.php 1;
Backdrop settings.php 1. Repository `.git` directories are excluded from source
manifests. Exact aggregate digests and test outputs are retained in private evidence.

No live CMS bootstrap, database connection, write or query was performed this
phase. Therefore no site activation, configuration, H5P content, SNORDIAN options,
Apache or certificate changes were made by this work. No live database checksum
audit was repeated; independent external database changes are not ruled out.
The deployed plugin remains at its accepted Phase 1C-D version.

## Known limits

WordPress user-meta APIs do not supply multi-key transactions or guaranteed
compare-and-swap. Context nonces reject ordinary stale forms; this is not an atomic
concurrency guarantee between final validation and individual metadata writes.
Independent partial patches avoid writing unrelated hidden fields. A backend
failure can leave earlier members of a validated multi-field request saved; it
stops with an explicit failure message rather than claiming success or performing
an unsafe rollback. Core WordPress profile fields have already saved at that point.
Reload/review is required. No theme-aggregate CAS guarantees are claimed for usermeta.

Native HTTP/session integration, browser error redisplay, layout/accessibility and
real metadata round trips remain deployment acceptance tasks. Multisite remains
disabled. No new runtime compatibility, iframe or cache behavior is certified.

## Proposed deployment procedure — NOT AUTHORIZED / NOT EXECUTED

1. Obtain explicit deployment and separately scoped test-write approval. Inventory
   current deployed hashes and verify source; back up changed files outside the
   web root. Any acceptance writes need a verified database backup/recovery plan.
2. Stage the three new production profile files and four modified PHP files
   (main entry, composition root, diagnostic notices, admin view) as one coherent
   release. Include readme.txt if maintaining the installed documentation. Verify
   hashes/syntax and use a controlled replacement so entry/composition never run
   before their required profile definitions are present. Do not copy tests/private
   artifacts. No activation cycle or automatic migration is needed.
3. With separate authorization, use dedicated test accounts for profile writes;
   record only our four initial keys, grants and preferences. Preserve all existing
   user roles/capabilities, H5P content and SNORDIAN state. Verify ordinary bootstrap
   and menus/suspension before acceptance. Stop on unexpected errors.
4. Revert test-only data explicitly and verify unrelated records. File rollback
   restores the coherent prior release; it does not automatically erase usermeta.
   No blanket database restore or metadata deletion is implied.

## Firefox manual acceptance — after deployment approval

1. Open https://acceptance.example.test in normal Firefox. Keep SNORDIAN active. Use an existing
   authorized administrator and a separately approved disposable learner account.
2. Edit that learner: both permissions initially off. Save theme-only, Density-only,
   both, neither; after each, open the learner's own Profile in a separate session
   and confirm exactly the authorized controls appear. No learner grant controls.
3. Enable both. Save global default, explicit Core, each built-in and a published
   disposable custom theme. Reload and verify stable selections. Unpublished
   custom themes must never appear as new choices.
4. Save each of the four Density values. Theme changes must preserve Density and
   Density changes must preserve theme. No activity editor/player selectors appear.
5. Unpublish the selected test custom: the learner sees a generic retained-choice
   warning and global fallback. Unrelated saves preserve that reference; explicitly
   choose a replacement to change it. Repeat after deletion using test-only data.
6. Revoke/regrant permissions: saved preferences remain, controls/effective choices
   follow the grants. Submit an old profile tab: stale context must fail visibly.
7. Trigger a normal WordPress validation error (for example an invalid test email):
   our preferences must not save. Check keyboard/labels, escaping, error redisplay.
8. Confirm SNORDIAN settings/activation/runtime and existing playback are unchanged,
   with Papi Jo runtime suspended. Restore original test data only under its agreed
   acceptance scope and report Firefox results.

## Recommended Phase 2B architecture — design only

Compose the current user grants/preferences and authoritative library through
`Profile_Service::effective()` into a request-local descriptor. Gate **both** theme
and native Density on a fresh common `Runtime_Policy` decision. Anonymous users
use global/Core with no personal Density. Keep descriptors user-private and out
of shared cache keys/assets; validate iframe identity/transport before application.
An eventual native Density controller must capture/restore original classes and
respect external ownership, including original's no-added-override meaning.
Do not implement a new spacing algorithm. Verify official and Papi Jo libraries,
top-level/iframe players, resize behavior, cache isolation and SNORDIAN suspension
in a separately approved runtime phase. **No Phase 2B code has been started.**

Stop here pending explicit Phase 2A deployment approval.
