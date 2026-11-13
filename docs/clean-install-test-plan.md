# Disposable ZIP installation test — plan only

> F8 addendum: stable **0.1.1** is prepared locally from the accepted beta.3
> baseline. See [F8](stage-f8-release.md) and [current metadata](release-candidate.json).
> F7's 3/3 beta.3 Firefox smoke tests are user-reported. No stable ZIP installation
> or database operation is authorized in F8. The beta.3 procedure below is retained
> as an earlier plan, not current stable installation authorization.

Candidate: **0.1.1-beta.3**. Do not execute this installation procedure until the
maintainer approves it. Stage F6 prepares files locally; it does not deploy the
candidate. F4 records 25/25 user-reported Firefox tests on beta.2, not beta.3. Existing automated native-lifecycle tests use source copies and
random disposable databases, separately from this planned artifact test.

## Fixed boundaries and inputs

- Use the verified `papijo-h5p-themer-wordpress-0.1.1-beta.3.zip`, its SHA-256 sidecar,
  reviewed `inputs.json`, and [release metadata](release-candidate.json).
- Use a new private directory named `clean-install-wordpress` below the project's
  ignored local acceptance area. It must not exist yet and must not be a link,
  junction, live site or earlier fixture. Do not reuse the regression fixture.
  Keep it directly below that area rather than a deeply nested report directory;
  preflight the longest copied core path against Windows path-length support.
  Do not truncate or omit WordPress core files to work around a path limit.
- Bind only to `127.0.0.1:8812`, after proving that port is unused. The intended
  WordPress home/site URL is exactly `http://127.0.0.1:8812`, with plain permalinks.
  No Wamp/Apache virtual host, DNS, certificate or live-site change is needed.
- Use clean WordPress 7.1.4 core and PHP 8.4.22 with mysqli, then repeat the
  agreed compatible PHP checks as needed. WordPress 6.5 remains a declared target,
  not a newly verified native-install result.
- Provision only a random `pjh5pwp_zip_install_beta3_<16-lowercase-hex>` database.
  A setup helper must refuse every other name, and must never use DB_NAME from
  an existing site's configuration. A dedicated fixture DB account is preferable;
  if local server credentials are read, parse literal host/user/password only.
- Keep generated credentials/salts, snapshots and logs private. Do not copy a real
  wp-config.php, database, uploads, users, active-plugin options or SNORDIAN settings.
  The accepted local site's plugin remains 0.1.0 and its SNORDIAN remains active.

## Exact execution sequence after approval

1. Record the candidate ZIP/manifest hashes. Run:

   ```text
   python -B tools/package_release.py verify --expect-version 0.1.1-beta.3 --approved-manifest <reviewed-inputs.json> --archive <candidate.zip>
   ```

   Compare the result with the sidecar and release metadata. Refuse mismatched or
   stale bytes. Record the current accepted-site file hashes read-only; do not
   bootstrap it or select its database.
2. Create the new fixture directory exclusively. Copy only WordPress core root
   PHP files excluding wp-config.php, wp-admin and wp-includes, plus a clean stock
   WordPress theme. Create empty wp-content/plugins and wp-content/uploads. Verify
   no Papi Jo source directory is present: the plugin must arrive through the ZIP.
3. Create the random allowed database and a **new** fixture wp-config.php using its
   name, table prefix `zipbeta3_`, utf8mb4, generated fixture-only salts and private
   admin credentials. Set WP_HOME and WP_SITEURL to the URL above, environment
   type local, DISABLE_WP_CRON and AUTOMATIC_UPDATER_DISABLED true, WP_AUTO_UPDATE_CORE
   false, WP_HTTP_BLOCK_EXTERNAL true and mail interception for the fixture.
   Do not set DISALLOW_FILE_MODS: the test must exercise WordPress's real ZIP upload.
4. Start the selected PHP CLI using its normal mysqli-enabled ini, with a hidden
   process and captured private stdout/stderr:

   ```text
   php.exe -S 127.0.0.1:8812 -t <fresh-clean-install-wordpress-directory>
   ```

   Record that exact process ID. Stop if any URL/DB/config check identifies a real
   site. Open `/wp-admin/install.php` on that loopback URL and complete installation
   using only the generated fixture administrator. No external browser session,
   cookies or production user account is copied.
5. Before installing H5P, upload the **candidate ZIP** through Plugins → Add New →
   Upload Plugin → Install Now. Confirm the displayed version 0.1.1-beta.3 and
   activate only this plugin. Verify supported empty InnoDB State and Profile tables are
   created with zero rows, Theme/permission records are not initialized, missing-H5P
   diagnostics appear, and activation completes without PHP output/errors.
   The extracted plugin must have exactly the 93 expected files and original hashes;
   there must be one correctly named directory and no tests, tools or private data.
6. Repeated activation and ordinary Plugins/Theme/Profile/frontend GET requests
   must preserve plugin-owned rows/options and perform no new storage DDL. Initialize the absent
   Theme library only through its explicit administrator action. Verify that
   WordPress options can use MyISAM; repeat on a separate fresh InnoDB-options
   installation. Neither run may convert or alter a WordPress-owned table. Record the change
   as intentional fixture data, not an activation side effect.
7. Install a clean official H5P 1.17.10 package/source with Core API exactly 1.28
   into the fixture. Do not copy any live content/configuration. Use existing
   approved sample content or explicitly approved newly imported fixture content.
   Confirm integration admission, five presets, custom create/edit/duplicate/delete,
   current-default deletion protection, publication and global-default saves.
8. Create fixture learner/administrator accounts and configure fixture role access.
   Verify own-Profile Theme preview and independent Theme/Density saves, denied
   role access, Core/Original options, DIV/internal iframe playback, lifecycle return
   and 30-second expiry behavior. Use Firefox for actual manual acceptance; record
   browser versions and exact content libraries. Do not claim universal support.
9. Export one, selected and all custom Themes; import valid colors-only JSON and
   compatible Backdrop samples. Verify explicit review/confirmation, unpublished
   imports, no overwrites, and unchanged Density/preferences/default/publication.
   Check malformed/oversize upload and stale-review rejection.
10. In a **separate fixture-only coexistence run**, install/activate the legitimate
    SNORDIAN package under explicit fixture authorization. Confirm both Papi Jo
    runtime dimensions suspend, eligible Profile controls/preview become a notice,
    submissions are ignored, and saved preferences/administration/transfer survive.
    Do not deactivate or otherwise modify SNORDIAN on the accepted site.
11. Exercise degraded storage/retry only in additional disposable fixture databases
    or a recorded fresh fork: denied CREATE, incompatible existing schema/engine,
    updates bypassing activation and nonce/capability/method denial. Verify the
    plugin remains active and performs no unsafe repair or preference reset.
    Seed either legacy Theme/role option only in an additional disposable fixture:
    verify explicit legacy refusal with no migration, reset, deletion or overwrite.
    This is a negative fixture, not an upgrade path for existing development sites.
12. Test deactivation/reactivation preservation in the fixture. Test JavaScript/
    asset failure, narrow layout and slow-network fallback. The existing 15-second
    readiness limitation is **unresolved**: document terminal native fallback as
    a known result, not successful personal rendering or a fixed defect.
13. Verify final plugin files match the original ZIP manifest; compare protected
    accepted-site files read-only. Stop the exact recorded loopback process. Retain
    private fixture evidence/backup and the outcome report. Do not delete earlier
    evidence or drop any nonfixture database. Cleanup of this disposable database
    or fixture is a separately scoped operation using its recorded exact identity.

## Acceptance and stop

Accept only if ZIP upload/extraction, version, activation/storage, required features,
negative/security checks and preservation results are recorded with no unexplained
errors or unexpected writes. Report any skipped browser/library/host case and keep
the known readiness limitation explicit. If a check fails, retain evidence and stop;
do not patch the accepted site or relax runtime admission to make it pass.

After local acceptance, prepare a separate private online pilot proposal. Online
deployment, Git/repository creation, commits, tags, release drafts and publication
each remain subject to the maintainer's authorization. None is performed by this plan.
