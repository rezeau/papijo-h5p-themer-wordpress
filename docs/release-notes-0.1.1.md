# Papi Jo H5P Themer for WordPress 0.1.1

Draft for local review — first stable public release, not yet published.

Papi Jo provides Theme management and independent personal Theme and Density
preferences for supported H5P content embedded in ordinary WordPress pages.

- Five built-in presets: Daylight, Lavender, Mint, Sunset and Dark.
- Custom Themes with preset inheritance, color overrides, duplication, publication
  controls and a global default. Clear an override and save to restore inheritance.
- Personal Theme and Density preferences (Original, Small, Medium and Large),
  representative Profile previews and WordPress role permissions. One permission
  grants personal selection; Theme and Density remain independent choices.
- Plugin-owned InnoDB State and Profile tables. WordPress options may use MyISAM
  or InnoDB; Papi Jo does not convert WordPress-owned tables.
- SNORDIAN coexistence: while SNORDIAN is active, Papi Jo suspends its runtime
  styling and personal selectors, preserves saved choices, and keeps Theme
  administration and transfer available.
- Theme colors-only JSON export/import, including inheritance and descriptive
  metadata. Import creates new unpublished Themes after validation and review.
  Density, personal preferences, roles, defaults and publication are not transferred.

Version 0.1.1 promotes the accepted 0.1.1-beta.3 baseline with version and release
metadata changes only. F6 recorded 208/208 automated command passes; F4 recorded
25/25 user-reported Firefox tests on beta.2, and F7 recorded 3/3 user-reported
Firefox smoke tests on beta.3. Those manual results were not independently
reproduced by Codex. Current F8 verification and exclusions are recorded in the
[release preparation report](stage-f8-release.md).

Declared minimums remain WordPress 6.5 and PHP 7.4. Single-site WordPress,
canonical official H5P integration with Core API exactly 1.28, standard MySQL-backed
wpdb/mysqli and supported owned InnoDB schemas are required. Declared targets do
not certify every hosting, browser or H5P library combination. Legacy pre-F2
options-based installations are refused without migration, reset or deletion;
preserve them and use a separate fresh installation. See [installation](installation.md).

Known accepted limitations:

1. Papi Jo styling is not applied in the WordPress H5P administration viewer.
2. Papi Jo styling is not applied through Subscriber Profile > View H5P content.
3. On slow networks, expiry of the initial 15-second readiness timeout can leave
   native fallback styling without automatic personal styling for that document.

The original F3 database-preservation check did not pass. Its unattributed
cache-timeout/AUTO_INCREMENT observations remain unchanged. The project owner
accepted the residual uncertainty in F6; further investigation is not required
for this release and that risk is no longer release-blocking. Preservation was
not proven. See the unchanged [F6 disposition](stage-f6-release.md).

Use the dedicated `papijo-h5p-themer-wordpress-0.1.1.zip` installer and its checksum,
not an automatic repository source archive. Exact artifact details are in
[release metadata](release-candidate.json). Deployment and publication await
explicit project-owner approval.
