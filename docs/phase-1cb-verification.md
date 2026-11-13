# Phase 1C-B verification — 7 November 2026

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Phase 1C-B is complete in the project workspace. No live WordPress installation,
activation or administration testing was performed. This report separates verified
isolated behavior from proposed manual acceptance. Stop here pending explicit
approval for any live deployment, activation or site-database writes.

## Review and architecture

Reviewed architecture.md, theme-domain.md, wordpress-persistence.md and all three
previous verification records before implementation, plus the actual Backdrop
admin forms, native color scripts, preview renderer and sample CSS. Source HEAD
was rechecked read-only as `d9b53d706a94dac322b89885bf668145ab3803c4` (v0.5.0).
No foundational namespace, dependency-policy or storage-contract inconsistency
required correction. Earlier documents saying administration was absent described
the prior checkpoint; they have been updated without changing historical reports.

The implemented architecture is documented in [administration.md](administration.md),
including the feature-by-feature Backdrop parity matrix, verified installed H5P
menu/CSS integration points, deliberate host/scope differences and security design.
`Admin_Controller` owns WordPress lifecycle; `Admin_Service` validates requests and
delegates pure plans; `Admin_View` escapes forms; `Theme_Preview` isolates samples.
The existing adapter remains the only production persistence mechanism. The only
domain addition is atomic batch publication composed from existing rules.

## Files created and modified

Created **17 files**, modified **10 files**, deleted **0**. Project total: **75
files**, including **54 PHP files**. No package dependencies were added.

Created production files:

- `includes/administration.php`
- `includes/Admin/class-admin-controller.php`
- `includes/Admin/class-admin-messages.php`
- `includes/Admin/class-admin-service.php`
- `includes/Admin/class-admin-view.php`
- `includes/Admin/class-theme-preview.php`
- `assets/css/admin.css`
- `assets/css/preview.css`
- `assets/js/admin.js`

Created tests:

- `tests/admin/run.php`
- `tests/admin/wordpress-doubles.php`
- `tests/admin/coexistence.php`
- `tests/admin/browser-behavior.js`
- `tests/admin/render-fixtures.php`
- `tests/admin/headless-browser.js`

Created documentation: `docs/administration.md` and this report.

Modified production: `papijo-h5p-themer-wordpress.php`,
`includes/class-plugin.php`, `includes/Admin/class-diagnostic-notices.php`,
`includes/Theme/class-library-changes.php`.

Modified test: `tests/worker.php` allows exactly two additional administration
hooks, while retaining the prohibitions on playback, profile, activity and AJAX
callbacks. Its original assertions were retained; two new hook admissions across
18 scenarios increase its count from 405 to 441.

Modified documentation: `README.md`, `readme.txt`, `THIRD-PARTY-NOTICES.md`,
`docs/architecture.md`, `docs/theme-domain.md`.

Preset data, Color_Properties, validators, resolver, Theme_Operations, publication
policy, all persistence implementations, compatibility classes, existing domain/
storage suites and full license texts remain byte-identical to the starting
project baseline. Only Library_Changes gained the batch plan method.

## Implemented management and preview behavior

Verified in isolation:

- Five immutable built-ins and distinct H5P Core defaults; view/duplicate only
  for built-ins, with server protection against forged edit/delete submissions.
- Custom Add/Edit/rename/copy/delete; permanent IDs, pinned preset inheritance,
  independent custom copies, 33 sparse hex overrides and exact five color groups.
- Blank Edit fields restore inheritance; blank Add fields retain the captured
  global starting overrides. Built-in copies initially have zero overrides.
- New custom/copy identities are unpublished. Visibility is explicit and saved
  as one revision; Site default/Core/Daylight remain fixed available choices.
- Any valid theme can be global default independently of publication. Selected
  defaults cannot be deleted; deleted IDs receive unpublished tombstones.
- Native text/color companions, collapsed groups, expanded invalid groups and
  escaped/preserved submitted fields. A stale deleted source offers copyable
  bounded text rather than recreating it or losing safe edits.
- Saved scriptless preview plus collapsed original-Core comparison on built-in
  View and custom Edit; live valid/invalid/cleared colors and Reset on Edit only.
- Isolated representative HTML rather than playback. Frame CSP blocks requests,
  script permission is absent, and parent CSS does not enter sample frames.
  Both previews keep the same original unclassified Core spacing. No Density
  control, class application, live Density preview or legacy-metadata use exists.
- Scoped WordPress menu/asset hooks; accessible H5P parent or Settings fallback.
  Management remains independent of missing H5P/SNORDIAN suspension; production
  preview assets require verified official H5P/Core API 1.28.

Security checks cover separate capability and nonce admission, strict POST shapes,
action/source/revision/full-snapshot-and-preset binding, invalid references, UTF-8
and domain bounds, built-in mutation attempts, stale global Add seeds and a write
race after form validation. Domain plans commit only through the existing CAS.
Known failed, unknown and committed/cache-failed outcomes remain distinct; no
automatic retry, rollback or destructive reset occurs. Invalid submitted CSS
appears only as escaped form text and never enters saved preview CSS.

## Automated and regression results

All checks below passed. Counts are per runtime unless stated otherwise.

| Suite | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| Phase 1A foundation, including actual installed H5P source-only probe | 18 scenarios / 441 assertions | 18 / 441 | 18 / 441 |
| Phase 1B domain | 2,315 assertions | 2,315 | 2,315 |
| Included Backdrop parity subset | 919 of the above | 919 | 919 |
| Phase 1C-A persistence doubles | 204 assertions | 204 | 204 |
| Phase 1C-A real wpdb, disposable MySQL 9.1.0 | 49 assertions | 49 | 49 |
| Phase 1C-B forms, rendering and lifecycle | 210 assertions | 210 | 210 |
| Phase 1C-B SNORDIAN coexistence | 4 signals / 36 additional assertions | 4 / 36 | 4 / 36 |
| PHP syntax | 54 files | 54 | 54 |

The 919 parity checks are not additional to the 2,315 domain total. The coexistence
workers also execute the existing foundation worker before their 36 additional
assertions; those repeated foundation checks are not counted twice here.

JavaScript: **30 isolated DOM/event assertions** and **24 real Chromium static-page
assertions** passed on Node 22.23.1 with a disposable headless Edge profile.
Browser fixtures covered overview, Edit, built-in View, Add, built-in Copy and
protected Delete; checked actual computed colors, Core comparison, parent-CSS
isolation, unchanged spacing, native reset, narrow-screen overflow and immutable
forms. This is not certification of a live WordPress page or Firefox acceptance.
Application and test JavaScript syntax checks passed. Temporary profiles and
loopback servers were cleaned up; no generated fixtures were installed in a CMS.

The PHP suites bootstrap neither CMS. Administration tests use isolated API doubles
and memory storage; coexistence uses independent marker/activation/callback
fixtures, not SNORDIAN code. Its four signals prove management and rendering work
while runtime remains suspended, without changing foreign options or callbacks.

Disposable MySQL tests use the installed `wpdb` class, create only a random
`pjh5pwp_test_` plus 16-hex-digit database, and exercise six two-process writer
races plus an initialization race on each PHP runtime. All disposable databases
were removed. Their target validation excludes the site database. They read only
literal host/user/password credentials without executing wp-config or selecting
its WordPress database. The adapter/storage tests themselves remain unchanged.

Commands, from the project directory (repeat PHP commands with each binary):

```powershell
php -n tests/run.php 'WORDPRESS_ROOT/wp-content/plugins/h5p'
php -n tests/domain/run.php 'BACKDROP_REFERENCE_ROOT'
php -n tests/storage/run.php
php tests/storage/mysql.php 'WORDPRESS_ROOT' 'WORDPRESS_ROOT/wp-config.php'
php -n tests/admin/run.php
php -n tests/admin/coexistence.php
node --check assets/js/admin.js
node tests/admin/browser-behavior.js
node tests/admin/headless-browser.js 'PHP_CLI' 'WORDPRESS_ROOT/wp-content/plugins/h5p/h5p-php-library/styles/h5p-theme-variables.css'
```

The optional browser harness serves only fixed in-memory fixtures and own assets
over loopback, prohibits POST, uses a uniquely named temporary profile, then
closes only its own browser/profile. It never navigates to acceptance.example.test/backdrop2.
Restricted Windows PHP tokens misreport `is_readable()` for readable fixtures;
the successful runs used authorized normal CLI access. No permission rejection
left required work incomplete.

## Protected-file verification and scope

Before/after SHA-256 path/content inventories matched for **860 files across seven
targets**, including additions/deletions within those inventories:

| Protected target | Files |
| --- | ---: |
| Authoritative v0.5.0 worktree, excluding .git | 154 |
| Original Backdrop repository, excluding .git | 65 |
| Deployed backdrop2/modules/h5p_themer | 72 |
| Entire acceptance.example.test/wp-content/plugins | 465 |
| Backdrop active configuration directory | 102 |
| acceptance.example.test/wp-config.php | 1 |
| backdrop2/settings.php | 1 |

Aggregate inventory hashes include:

- Authoritative source: `PRIVATE_VERIFICATION_DIGEST_OMITTED`
- Installed WordPress plugins: `PRIVATE_VERIFICATION_DIGEST_OMITTED`
- Deployed Backdrop Themer: `PRIVATE_VERIFICATION_DIGEST_OMITTED`

No acceptance.example.test/Backdrop bootstrap, plugin installation or activation, site option
initialization/migration, live-site request or site-database write occurred.
The only database writes were to the disposable test databases. This conclusion
is based on executed test paths/target restrictions, not an independent full
before/after snapshot or monitoring of the live database. File verification is
likewise scoped to the listed targets, not the entire machine.

No H5P asset/runtime callbacks, Density controller, activity controls, profile
hooks, learner preferences, multisite implementation, commits, tags, pushes or
publication were introduced or performed. Parent administration CSS/JS and
sandbox sample styles are intentionally present; they do not style H5P playback.
SNORDIAN, official H5P, Backdrop and existing site configuration were not modified.

## Remaining limitations

1. Real WordPress authentication/session nonces, menu presentation, third-party
   admin CSS, browser accessibility and refresh behavior still require approved
   live acceptance. Static Chromium checks do not substitute for those tests.
2. No Firefox/manual keyboard/screen-reader or complete translation-catalog
   acceptance is claimed. Native color-picker appearance varies by browser.
3. Representative samples cannot certify how official/Papi Jo content libraries
   will look. Runtime integration and Density remain unimplemented.
4. Phase 1C-A backend restrictions/qualified CAS guarantees remain: standard
   single-site `wpdb`, MySQL/InnoDB/utf8mb4 metadata, cooperative revisions, no
   unsupported drop-ins or automatic recovery. Aggregate size remains 4 MiB.
5. Missing/unsupported H5P or unreadable Core CSS disables previews with a clear
   message. Schema/backend faults block management writes rather than guessing.
6. The separate schema-upgrade service remains explicit; no real historical
   migration exists or is silently run by this interface.

There is no known failing automated check at the checkpoint boundary.

## Proposed manual WordPress acceptance checklist

**Not executed. Requires explicit approval for deployment, activation and each
live testing scope; none is implied by this plan.** Prefer a disposable copy first.

1. Record authorized site/plugin/database baselines and a recovery plan. Deploy
   only this plugin to the agreed environment, leaving H5P/SNORDIAN/Backdrop intact.
2. Activate once; verify existing diagnostics, active SNORDIAN suspension and no
   theme option written by activation or ordinary page loads.
3. Check the unique H5P submenu and independent SNORDIAN menu. With an authorized
   manage-options test role lacking H5P view capability, check Settings fallback.
   Direct unauthorized page/POST requests must return 403 and reveal no themes.
4. Check absent-state GET is read-only. Explicitly initialize once; refresh/double
   submit must preserve data and reject stale attempts, never overwrite a library.
5. Inspect all five built-ins and original Core comparison. Verify read-only
   palettes, stable IDs, duplicate links and no edit/delete routes that can mutate.
6. Create from Core/built-in/custom defaults. Verify captured Add colors, pinned
   base, unique ID, blank/sparse values and unpublished initial state.
7. Copy built-ins and customs; edit only the saved built-in copy. Rename by Name
   while keeping identifiers/references stable. Confirm no live custom inheritance.
8. Exercise all five groups with mouse/keyboard. Test native picker, hex shorthand,
   clearing, invalid input, error expansion, escaping, no-JS text editing and Reset.
9. Check saved/live previews, Core distinction, inherited expressions, invalid
   last-valid behavior, reset and sample-only isolation in Firefox and Chromium.
   No frame script permission, remote CSS request, activity or Density preview.
10. Publish/unpublish optional themes explicitly. Confirm new copies unpublished,
    fixed choices always available and default eligibility independent of visibility.
11. Select a custom default; delete must be blocked. Select another default, then
    confirm custom deletion; old Edit must fail without recreating the theme.
12. Open two administrator sessions/tabs. Test stale Edit, Add, visibility, default
    and delete, including concurrent submissions. Exactly one current write wins;
    stale safe edits remain copyable and are never silently rebased.
13. Test missing/expired/tampered nonce, action/source/revision/context tampering,
    truncated color fields, unknown/array/null fields and payload-like text. Verify
    no mutation and useful diagnostics. Do not corrupt live storage for testing;
    malformed/backend failures belong in disposable copies.
14. Confirm our assets appear only on our page, no activity/profile controls exist,
    and no playback color/Density injection occurs with SNORDIAN active or inactive.
    Compare SNORDIAN options/callbacks/configuration and unrelated options against
    the authorized baseline. If authorized to test missing H5P, verify fallback
    management and disabled previews without data loss.
15. Review PHP/browser logs, narrow layout, labels/focus/announcements, saved PRG
    refresh behavior, and agreed test-data cleanup/recovery. Record outcomes before
    approving any profile or runtime work.

## Recommended next checkpoint

Controlled WordPress administration acceptance and any fixes it exposes, after
explicit approval. Do not start Profile integration, learner Density, runtime CSS
or activity integration as part of that acceptance. Phase 1C-B ends here; no next
phase implementation, installation, activation, commit or publication is authorized.
