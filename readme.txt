=== Papi Jo H5P Themer for WordPress ===
Contributors: rezeau
Tags: h5p, accessibility
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 0.1.1
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Independent Theme and Density preferences for supported WordPress H5P playback.

== Description ==

Version 0.1.1 is the first stable public release, prepared locally for review. Theme-library and
role-permission state use a plugin-owned InnoDB table. WordPress options may use
MyISAM or InnoDB and are never converted by Papi Jo. The separate InnoDB Profile
table, Theme/Density runtime behavior and SNORDIAN coexistence are preserved.
Legacy pre-F2 Theme/permission options are detected and refused without migration,
reset or deletion. Preserve those installations and use a separate fresh site.
The accepted beta.3 baseline passed 3/3 user-reported Firefox smoke tests in F7;
beta.2 passed 25/25 user-reported Firefox acceptance tests in F4. These manual
results were not independently reproduced by Codex. Stable preparation changes
version and release metadata only. Publication and deployment require separate approval.

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
Papi Jo styling works for supported H5P content embedded in ordinary WordPress
pages. It is not currently applied in the WordPress H5P administration viewing
interface or Subscriber Profile > View H5P content. Both limitations are accepted
and deferred. External/cross-origin embeds, framed parent pages, editor/admin
previews and ambiguous players are excluded. Library-specific variable/class use is required.
No universal browser/library/hosting or accessibility certification is claimed.

Unresolved slow-network readiness limitation: the initial 15-second head-start
budget can expire before DOM readiness, H5P/CSS initialization or iframe sizing.
Native content is revealed with fallback, but automatic personal Theme/Density
may remain absent for that document. Characterization tests do not resolve this.
Descriptor authority expires after 30 seconds; remote changes have no instant push.
Asset order, CSP, optimizers and caching need target-site testing. No multisite,
automatic legacy migration, uninstall deletion or translated catalogs are supplied.

== Installation ==

Use the dedicated installable plugin ZIP from a future approved release, not a
GitHub automatic source archive. It must have one top-level directory named
papijo-h5p-themer-wordpress. Stage F8 prepares version 0.1.1 locally from the
accepted beta.3 baseline. Installation and publication require separate approval.

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
