# Papi Jo H5P Themer for WordPress

Version **0.3.0** integrates anonymous visitor Theme and Density selection into
one WordPress plugin. Release preparation is complete only after package and public
source checks; publication and deployment require separate approval.

## Appearance for visitors and signed-in users

Administrators can enable **Allow anonymous visitors to select their H5P appearance**
on the **Papi Jo H5P Themes** overview. This independent permission is **off by default**.
When enabled, one **H5P appearance** disclosure appears above the first eligible
activity on each page. It starts collapsed on every page load. Opening it reveals
Theme, Density and Restore defaults controls; saved appearance applies automatically
without opening it. One selection applies to all eligible activities on the page.

Anonymous Theme and Density preferences stay in site-scoped browser localStorage.
They are independent of signed-in users' database preferences and role permissions.
If browser storage is unavailable, choices work for the current page. Restore defaults
clears the two anonymous preference keys and selects Site default / Original Density.
No account preference is read or changed by anonymous selection. Sign-in state changes
release owned appearance and request a manual reload; there is no live controller handoff.

Available choices include Site default, eligible built-in Themes and published custom
Themes. An unavailable saved custom Theme falls back to Site default. Theme and Density
are independent. H5P Core defaults is no longer offered as a new end-user choice in
anonymous or WordPress Profile dropdowns. Its internal mode and administrator options
remain available. Existing Core preferences remain effective and appear as a disabled
saved-preference entry; opening/saving a Profile or changing Density does not convert them.
Routine success status is visually hidden while the disclosure is closed; warnings,
errors and reload instructions remain visible, and live announcements remain accessible.

The standalone **Papi Jo H5P Themer Anonymous** companion is no longer required.
Deactivate it before using 0.3.0; do not run it alongside the integrated plugin.
Standalone browser preferences are not migrated. No elaborate legacy migration is provided.

## Theme administration and compatibility

The plugin retains five built-in Themes (Daylight, Lavender, Mint, Sunset, Dark),
custom Theme editing and pinned inheritance, publication, a global default, color
Export/Import, role-based Profile Theme and independent Density preferences, and the
qualified official H5P administration activity viewer. Imported custom Themes remain
unpublished until an administrator publishes them.

Active SNORDIAN or its loaded markers suspend Papi Jo Theme and Density runtime
for signed-in and anonymous visitors. Profile selectors/preview are suspended and
saved preferences preserved. Theme administration and transfer remain available.
Papi Jo does not modify SNORDIAN code, settings or activation state.

Declared requirements: WordPress 6.5+, PHP 7.4+, single-site WordPress, standard
MySQL-backed wpdb/mysqli and InnoDB for the plugin-owned State/Profile tables.
WordPress options may use MyISAM or InnoDB; Papi Jo never converts them.
Runtime admission requires the qualified canonical official H5P integration and
Core API exactly 1.28. Local acceptance used official H5P 1.17.10; this is not a
universal WordPress, H5P library, browser or hosting compatibility claim.

Qualified native same-origin iframe and DIV activities are supported. External or
sandboxed embeds, editors, nested subactivities and arbitrary admin contexts are
excluded. Pages support up to 64 distinct admitted content IDs; repeated IDs and
entirely new IDs inserted after manifest generation remain unsupported. Known iframe
replacement requires fresh admission. The existing 15-second readiness limitation
and brief initial anonymous styling delay remain; no unrelated readiness fix is claimed.

## Installation, acceptance and license

See [installation](docs/installation.md), [appearance guide](docs/appearance-guide.md),
[administration](docs/administration.md) and [0.3.0 release notes](docs/release-notes-0.3.0.md).
Use the dedicated installer ZIP rather than a GitHub-generated source archive.
Deactivation and deletion preserve plugin-owned data; no automatic uninstall erasure.

The maintainer reports all **13 Firefox acceptance tests and both interface refinement
tests passed** on accepted 0.3.0-dev.1. These were user-performed tests; Codex did not
independently reproduce browser acceptance. Final 0.3.0 changes are release metadata
and documentation only relative to that accepted candidate. Historical evidence remains
in the repository; the pre-existing administrator preview test failure reproduced on
v0.2.1 was not addressed or counted as a pass.

GPL-3.0-or-later. See [LICENSE](LICENSE), [third-party notices](THIRD-PARTY-NOTICES.md)
and the bundled full license texts for preserved attribution and provenance.
