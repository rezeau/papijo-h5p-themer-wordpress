=== Papi Jo H5P Themer for WordPress ===
Contributors: rezeau
Tags: h5p, accessibility
Requires at least: 6.5
Requires PHP: 7.4
Stable tag: 0.3.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Independent Theme and Density preferences for supported WordPress H5P playback.

== Description ==

Version 0.3.0 integrates anonymous Theme and Density selection into the main plugin.
An independent administrator checkbox enables it and is disabled by default.
One initially collapsed H5P appearance disclosure above the first eligible activity
controls admitted activities on that page. Saved browser-local preferences apply
automatically; Theme, Density and Restore defaults remain independent of signed-in
users' database preferences. Published custom Themes are supported.

End-user dropdowns no longer offer H5P Core defaults as a new choice. Existing Core
preferences and administrator Core options are preserved. The standalone Anonymous
companion is no longer needed and must be deactivated; its preferences are not migrated.

Theme editing, publication, global defaults, color Export/Import, role-based Profile
preferences and the qualified official H5P administration activity viewer are retained.
Active SNORDIAN suspends Papi Jo appearance and Profile controls while preserving
saved preferences; Theme administration remains available. SNORDIAN is never modified.

== Installation ==

Back up first. Deactivate the standalone Anonymous companion. Install the dedicated
ZIP and activate the main plugin. Initialize absent Theme storage explicitly on
Papi Jo H5P Themes. Configure Themes, publication and role permissions as needed.
Anonymous selection requires its separate checkbox. Existing compatible main-plugin
storage is retained; do not reset data for an ordinary update. See README and
installation documentation for requirements and legacy-development limitations.

== Requirements and limitations ==

Single-site WordPress 6.5+, PHP 7.4+, standard MySQL-backed wpdb/mysqli, and InnoDB
for the two plugin-owned tables. WordPress options may use MyISAM or InnoDB.
Qualified canonical official H5P integration with Core API exactly 1.28 is required;
local acceptance used H5P 1.17.10. No universal compatibility certification is claimed.
Native same-origin iframe/DIV playback only; external/sandboxed embeds, repeated
content IDs, new IDs inserted after manifest generation and arbitrary admin contexts
remain excluded. Maximum 64 distinct admitted IDs. Initial 15-second readiness limits
and a brief anonymous styling delay remain. Deactivation/deletion preserve saved data.

== Changelog ==

= 0.3.0 =
* Integrated anonymous Theme and Density selection, with default-off administrator permission.
* Initially collapsed shared selector, browser-local preferences and published custom Themes.
* Simplified end-user dropdowns while retaining saved Core preferences and administrator options.
* Quieter collapsed status and accessible legend; existing compatibility/suspension safeguards retained.

= 0.2.1 =

* Clarify the first-installation Theme-storage warning and link to the registered Themes administration page.
* Show setup instructions only for an absent Theme library; initialized storage remains unaffected.
* Preserve explicit administrator initialization, storage, permissions, preferences and uninstall/reinstall data retention.
* Owner reports Firefox Tests 1 and 2 PASS; Test 3 accepted via isolated automated tests. The absent warning was not manually rendered in Firefox.

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
