=== Papi Jo H5P Themer for WordPress ===
Contributors: rezeau
Tags: h5p, accessibility
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Independent Theme and Density preferences for supported WordPress H5P playback.

== Description ==

Version 0.2.0 is prepared locally from accepted 0.2.0-beta.1 with no functional
changes. It supports Administrator and authorized Subscriber Theme and Density in
the official H5P administration activity viewer, using personal preferences and
Global Default fallback on the exact admin.php?page=h5p&task=show&id=<ID> screen.
Content-access checks and session-bound nonces remain mandatory. The project owner
reported all 17 G3B Firefox tests PASS; Codex did not independently reproduce them.
Published stable 0.1.1 and its artifacts remain unchanged. Theme-library and
role-permission state use a plugin-owned InnoDB table. WordPress options may use
MyISAM or InnoDB and are never converted by Papi Jo. The separate InnoDB Profile
table, Theme/Density runtime behavior and SNORDIAN coexistence are preserved.
Legacy pre-F2 Theme/permission options are detected and refused without migration,
reset or deletion. Preserve those installations and use a separate fresh site.
The accepted beta.3 baseline passed 3/3 user-reported Firefox smoke tests in F7;
beta.2 passed 25/25 user-reported Firefox acceptance tests in F4. These manual
results were not independently reproduced by Codex. Stable 0.2.0 preparation changes
version and release metadata/documentation only relative to accepted 0.2.0-beta.1. Publication and deployment require separate approval.

Features include five immutable built-in Themes (Daylight, Lavender, Mint, Sunset,
Dark); custom Themes with pinned inheritance; publication and global defaults;
Theme color Export/Import; role-based own-Profile Theme and independent Density
preferences; isolated representative color previews; and qualified frontend DIV
and internal H5P iframe playback. There are no per-activity selectors.

Theme color JSON exports one, selected or all custom Themes, including unpublished
Themes. Import validates WordPress colors-only or compatible Backdrop custom Theme
JSON, then requires review and confirmation. Upload limit: 1 MiB / 100 Themes.
Existing Themes are not overwritten; new Themes remain unpublished. Density,
personal preferences, publication, roles and global defaults are not transferred.
Legacy Backdrop Density metadata is ignored.

Runtime attachment defaults on when PJH5PWP_ENABLE_BROWSER_THEME is undefined or
boolean true; false and all other values disable it. Compatibility and context
admission remain mandatory. Active SNORDIAN suspends both Papi Jo Theme and Density
playback. Eligible Profile users then see an explanatory notice instead of Papi Jo
selectors/preview; submissions are ignored and saved preferences are preserved.
Theme administration and transfer remain available. SNORDIAN files, options,
callbacks and activation state are never changed by Papi Jo.

== Requirements and limitations ==

Declared targets: WordPress 6.5 and PHP 7.4 or later; not a complete tested matrix.
Inspected local reference: WordPress 7.1.4, official H5P 1.17.10, Core API exactly
1.28. Canonical h5p/h5p.php integration, class/method locations and asset emitters
are checked. Version alone does not prove compatibility. Missing/unsupported H5P
allows activation and diagnostics, with runtime suspended.

Single-site only. Storage needs standard MySQL-backed wpdb, mysqli, InnoDB,
autocommit, nonpersistent private connections and supported owned-table schemas.
Theme/role state uses a plugin-owned InnoDB State table; WordPress options may use
MyISAM. Legacy options-based development installations are refused without migration
or reset. Preserve their data/code and use a separate fresh development installation.
The former Administrator and Subscriber viewing limitations are resolved for
the accepted official H5P activity viewer only. This does not support arbitrary
wp-admin pages or the separate external h5p_embed endpoint. Supported ordinary-page
playback, DIV/internal iframe rendering, multiple activities, independent personal
Theme/Density, SNORDIAN suspension/restoration and permission revocation/restoration
were covered by owner-reported G3B Firefox acceptance. Legacy activities using an
H5P API earlier than 1.28 receive no Papi Jo Theme or Density styling.
External/cross-origin embeds, framed parent pages, editors, content lists, results,
unrelated administration pages and ambiguous players remain excluded. Library-specific variable/class use is required.
No universal browser/library/hosting or accessibility certification is claimed.

Unresolved slow-network readiness limitation: the initial 15-second head-start
budget can expire before DOM readiness, H5P/CSS initialization or iframe sizing.
Native content is revealed with fallback, but automatic personal Theme/Density
may remain absent for that document. Characterization tests do not resolve this.
Slow-network readiness has not been fully verified; the owner may investigate
separately on an online WordPress test site. Timeout behavior is unchanged.

G3A preserved all 28 schemas, Papi Jo Themes/defaults, preferences, role permissions
and plugin-owned tables. The wp_options row fingerprint changed and reported next
AUTO_INCREMENT changed from 262 to 263; attribution remains unverified. The owner
accepts this for stable 0.2.0: ACCEPTED RESIDUAL RISK — NOT A RELEASE BLOCKER.
Full-database preservation is not a PASS. No database reset is part of this release.

Descriptor authority expires after 30 seconds; remote changes have no instant push.
Asset order, CSP, optimizers and caching need target-site testing. No multisite,
automatic legacy migration, uninstall deletion or translated catalogs are supplied.

== Installation ==

Use the dedicated installable plugin ZIP from a future approved release, not a
GitHub automatic source archive. It must have one top-level directory named
papijo-h5p-themer-wordpress. Stage G4 prepares stable 0.2.0 locally from the
accepted 0.2.0-beta.1 baseline. Installation and publication require separate approval.

1. Back up the target site and test in staging first. Install official H5P.
2. Upload the approved ZIP through Plugins > Add New > Upload Plugin and activate.
3. Single-site activation ensures empty supported State and Profile InnoDB tables;
   compatible existing records are preserved. Failure leaves the plugin active
   and shows administrator-only storage diagnostics with a protected retry POST.
4. Open H5P > Papi Jo H5P Themes (Settings fallback if the H5P parent is absent).
   Initialize an absent Theme library explicitly. Configure defaults, publication
   and role access, then use own-Profile Theme/Density preferences.

Updates that skip activation use read-only detection and explicit retry. Ordinary
page loads never create/repair storage. No user records, Theme defaults or
role records are created by activation. Storage setup is independent of SNORDIAN
runtime suspension. Deactivation/removal preserves data; there is no uninstall
cleanup or automatic legacy-usermeta migration.

== Frequently Asked Questions ==

= Are Theme and Density linked? =
No. Theme controls colors; Density uses independent native H5P classes. Changing
or importing colors does not change Density or personal preferences.

= Is this a fork of SNORDIAN? =
No. It is independent and conservatively suspends its playback while SNORDIAN is active.

== Changelog ==

= 0.2.0 =
* Stable promotion of accepted 0.2.0-beta.1; no functional runtime changes.
* Official H5P activity viewer: Administrator and authorized Subscriber Theme/Density.
* Personal preferences, Global Default fallback, DIV/internal iframe and multiple activities.
* SNORDIAN coexistence, permission revocation/restoration and frontend compatibility retained.
* Legacy H5P API content excluded; arbitrary admin pages and external h5p_embed unsupported.
* 17/17 owner-reported G3B Firefox tests PASS; not independently reproduced by Codex.
* Two viewing limitations resolved only in the accepted official viewer context.
* Existing 15-second readiness limitation and accepted G3A database uncertainty retained.

= 0.2.0-beta.1 =
* Local candidate: reuse Theme and Density runtime on the official H5P activity viewer.
* Exact screen/route, official-class provenance, Core API 1.28 and content-access gates.
* Session-bound nonce and fresh ownership/capability checks for all registered players.
* Frontend protocol, fallbacks, SNORDIAN suspension, storage and saved preferences retained.
* Profile/list/editor/results/external-embed/unrelated admin contexts remain excluded.
* Administration and Subscriber viewing limitations await manual Firefox acceptance.

= 0.1.1 =
* First stable public release, promoted from the accepted 0.1.1-beta.3 baseline.
* Five built-in Themes, custom Themes and inheritance, publication and global defaults.
* Independent personal Theme/Density preferences with WordPress role permissions.
* Plugin-owned InnoDB storage; supports MyISAM WordPress options without conversion.
* SNORDIAN coexistence and Theme colors-only JSON import/export.
* Version and release metadata only; functionality and accepted limitations unchanged.

= 0.1.1-beta.3 =
* Correct Profile guidance for supported page embeds and deferred viewing contexts.
* Record beta.2 user-reported Firefox acceptance (25/25) and accepted limitations.
* Record project-owner acceptance of the unattributed F3 preservation uncertainty.
* Storage, permissions, runtime behavior and colors-only transfer are unchanged.

= 0.1.1-beta.2 =
* Fresh-install Theme and role-permission state in a plugin-owned InnoDB table.
* Supports MyISAM WordPress options without converting WordPress-owned tables.
* Safe empty State-table activation/setup retry and precise storage diagnostics.
* Legacy pre-F2 options are preserved and refused; no automatic migration or reset.
* Conditional writes, permission locking, Profile storage and SNORDIAN coexistence retained.
* Theme/Density runtime behavior and known deferred limitations remain unchanged.

= 0.1.1-beta.1 =
* First testing pre-release candidate with coherent release and asset-version metadata.
* Consolidated public development source, documentation projection and deterministic ZIP tooling.
* README acknowledgments added; existing licenses and third-party attribution retained.
* F2 fresh-install source uses owned InnoDB Theme/role storage with MyISAM WordPress options support.
* Legacy options are preserved and refused without migration or reset; State setup has protected retry.
* Theme/Density runtime algorithms and Profile table schema remain unchanged.
* Slow-network readiness fallback remains an unresolved limitation.

= 0.1.0 =
* Independent foundation, compatibility diagnostics and SNORDIAN coexistence.
* Five pinned presets, custom Theme administration, inheritance, publication and global defaults.
* Theme color Export/Import with validation, review and explicit confirmation.
* Revision-checked Theme/role storage, own-Profile preferences and isolated previews.
* Independent native Density and qualified browser Theme runtime with readiness/lifecycle handling.
* Stage A: compatibility-gated runtime attachment enabled by default.
* Stages B/C: Profile installation service, native activation and protected setup retry.
* Stage D: documentation and test consolidation; version remains unchanged.

== License and third-party notices ==

GPL-3.0-or-later. LICENSE, THIRD-PARTY-NOTICES.md and licenses/ retain the Backdrop
adaptation, Moodle palette, Oliver Tacke MIT and Matheus Mathias GPL attribution.
No H5P runtime, upstream picker or SNORDIAN implementation is bundled.
