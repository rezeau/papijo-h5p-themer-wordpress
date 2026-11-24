# Installation and updates — 0.3.0

## Requirements

Use single-site WordPress 6.5+, PHP 7.4+, standard MySQL-backed wpdb/mysqli and
InnoDB for the plugin-owned State/Profile tables. WordPress options may use MyISAM
or InnoDB; they are never converted. Runtime checks require the qualified canonical
official H5P integration and Core API exactly 1.28. H5P 1.17.10 was used for local
acceptance. Other configurations are not automatically qualified.

## Install or update

1. Back up files/database. Verify the dedicated installer ZIP checksum.
2. Deactivate the standalone Anonymous companion if present; it is no longer needed
   and should not be active alongside the integrated main plugin. Remove obsolete
   companion development definitions from your own development configuration if used.
   Do not modify official H5P, its libraries/activities, SNORDIAN or unrelated data.
3. Upload the dedicated main-plugin ZIP or replace its plugin directory. Activate
   for a first installation. An ordinary code update must preserve compatible tables,
   custom Themes, role permissions and personal preferences; do not reset them.
4. Activation verifies/creates the empty plugin-owned State/Profile tables on a
   supported installation. Open H5P Content → Papi Jo H5P Themes (Settings fallback
   if needed), then click Initialize theme storage only when the Theme library is absent.
   Schema/setup failures produce diagnostics; unsupported data is not silently repaired.
5. Configure global defaults, custom Themes/publication and own-Profile role permissions.
6. Optionally enable Allow anonymous visitors to select their H5P appearance. It
   defaults off and is independent of signed-in role permissions. See the
   [appearance guide](appearance-guide.md) for storage, controls and saved Core choices.
7. Refresh affected page/asset caches so the updated controller and metadata arrive
   together. Confirm the desired appearance in your own supported activities.

No companion preference migration or elaborate legacy migration is provided. Old
pre-F2 options/usermeta development formats are preserved and refused rather than
silently imported or deleted; use a backed-up fresh development installation if needed.
Deactivation and plugin deletion retain owned data. Theme color exports are not a
complete database backup. Import creates unpublished Themes after explicit review.

## SNORDIAN and scope

Active SNORDIAN/loaded markers suspend both Papi Jo runtime dimensions and Profile
controls, preserving preferences. Administration and color transfer remain available.
Papi Jo never changes SNORDIAN settings or activation. Select your desired themer
explicitly; installation must not deactivate SNORDIAN automatically.

Supported playback is qualified native same-origin iframe/DIV embedding and the
accepted official H5P administration viewer for authenticated users. External embeds,
multisite, arbitrary admin contexts, repeated IDs and new IDs after manifest generation
remain excluded. Up to 64 distinct IDs per page. Existing readiness deadlines and the
brief initial anonymous delay remain. See [README](../README.md) and
[release notes](release-notes-0.3.0.md); no broader compatibility is promised.
