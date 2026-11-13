# Installation and acceptance guide

Stable release prepared: **0.1.1**, with [plugin-owned InnoDB storage](owned-storage-stage-f2.md).
Use the [F8 release record](stage-f8-release.md), [draft notes](release-notes-0.1.1.md)
and [current metadata](release-candidate.json). Beta.2 passed 25/25 user-reported
Firefox tests in F4; beta.3 passed 3/3 user-reported Firefox smoke tests in F7.
Codex did not independently reproduce those manual results or install the stable ZIP.
The original failed F3 preservation observation is retained; its residual uncertainty
is accepted by the project owner and is no longer a release-blocking gate.
Deployment and publication require separate approval.

## Prerequisites

Use single-site WordPress with the declared WordPress 6.5 / PHP 7.4 targets and
the canonical official H5P integration, Core API exactly 1.28. The inspected local
reference is WordPress 7.1.4 / H5P 1.17.10. Other combinations remain unqualified.
PHP needs mysqli and a standard MySQL-backed WordPress wpdb. Private connections
must be nonpersistent and autocommit. InnoDB and exact supported schemas are required
for the plugin-owned State and Profile tables. **WordPress options may use MyISAM**;
Papi Jo never converts or alters WordPress-owned tables. Hosting must permit creation
of these two owned tables and metadata verification. Multisite and nonstandard
adapters remain unsupported and fail closed.

Either legacy Papi Jo Theme or permission option makes an old development installation
unsupported. This version does not migrate, delete or reset that data, even if an
owned table already exists. Preserve the old database/code and use a separate fresh
development installation. Do not delete options to dismiss this warning. See the
[explicit legacy policy](owned-storage-stage-f2.md).

## Install and configure

1. Back up files/database and prepare staging. Check the approved ZIP checksum.
2. Upload the dedicated plugin ZIP in WordPress, or extract the single directory
   `papijo-h5p-themer-wordpress/` into `wp-content/plugins/`.
3. Activate. On a supported single site the installer verifies or creates only
   `<prefix>pjh5pwp_profiles` and `<prefix>pjh5pwp_state`, with no records. Existing compatible bytes and
   revisions are retained. Theme-library initialization remains separate.
4. If setup fails, activation still completes. Administrators see storage notices
   on Plugins and Papi Jo Themes pages. Resolve the hosting/schema problem, then
   use the corresponding **Retry State storage setup** or **Retry Profile storage setup**. The retry requires capability and nonce;
   ordinary requests never run storage DDL. Unsupported existing tables are not repaired.
5. Open **H5P → Papi Jo H5P Themes**, with a Settings fallback when the H5P parent
   is unavailable. Explicitly initialize an absent Theme library.
6. Choose a global default; add/duplicate custom Themes, edit colors and publish
   desired custom Themes. IDs remain stable when names change.
7. Enable appropriate roles for own-Profile preferences. Administrator access is
   capability-based. Theme and Density are saved independently.

An update can bypass activation. Detection remains read-only and offers the same
explicit retry. There is no migration from old options or user metadata. Deactivation
and file removal retain plugin data; uninstall has no deletion callback. Do not
restore an entire database to undo a code-only update.

## SNORDIAN

Active records or loaded SNORDIAN markers suspend both Papi Jo runtime dimensions.
Eligible own-Profile users get a notice instead of selectors/preview, and submitted
preference fields are ignored. Saved values survive. Theme administration and
Export/Import remain available. Never deactivate SNORDIAN as an installation side
effect; a trial of Papi Jo playback needs a separately authorized configuration.

## Color transfer

Use the overview row Export button, selected custom Themes or all custom Themes.
Built-in presets are not custom export entries. Imports accept WordPress colors-only
JSON or compatible Backdrop custom Theme JSON, up to 1 MiB and 100 Themes. Review
IDs, names, descriptions, bases and overrides, then explicitly confirm. The review
expires after 15 minutes and is bound to the session and current library state.
Conflicts do not overwrite existing Themes; imported Themes stay unpublished.
Density, preferences, roles, publication and global defaults are excluded; legacy
Backdrop Density metadata is ignored. Export is not a complete storage backup.

## Accepted runtime limitations

Personal Theme and Density preferences apply to supported H5P content embedded in
ordinary WordPress pages. Papi Jo styling is not currently applied in the WordPress
H5P administration viewing interface or Subscriber Profile > View H5P content.
Both contexts are accepted, deferred limitations. The initial 15-second readiness
budget may also expire, leaving native fallback without automatic personal styling;
this existing limitation remains accepted and unresolved.

## Target-site pilot checks

Verify fresh/repeated activation, expected empty Profile storage, explicit Theme
initialization, role access and independent Theme/Density saves. Exercise custom
Theme create/edit/duplicate/delete/default protection and single/selected/all color
Export/Import; confirm imports remain unpublished and preferences unchanged.
Check qualified DIV/internal iframe playback, lifecycle return and native fallback,
Profile preview, SNORDIAN suspension and saved-data preservation after reactivation.
Test caching/CSP/optimizer behavior and matched assets on the actual host. Use test
accounts and a private staging copy; compare protected state before/after.

Keep the [slow-network limitation](slow-network-readiness-investigation.md) visible
in pilot notes: the 15-second readiness deadline can cause terminal native fallback
for that document. No release should claim this fixed. External embeds, multisite,
broad library/browser support and accessibility certification remain outside the
accepted scope. ZIP installation/manual online acceptance have not been performed
by Stage D.
