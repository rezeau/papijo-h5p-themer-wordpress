# Stage C — Profile storage activation and protected retry

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Version 0.1.0. Source only; Stage B and Stage C have not been deployed to acceptance.example.test.
No real acceptance.example.test database was selected or bootstrapped during this work. Stage A
runtime configuration, SNORDIAN detection, runtime readiness, Theme/Density
semantics, Profile permissions, binary state and revision CAS are unchanged.

## Production changes

- `includes/class-plugin.php`: native activation calls the approved installation
  service; lazy `profile_installation()` accessor; register the Profile storage
  administration boundary during boot.
- `includes/profiles.php`: load the new inert administration definition.
- `includes/Profile/class-profile-storage-admin.php`: scoped read-only diagnostics
  and one capability/nonce-protected retry POST.

The entry point and existing compatibility notice class are unchanged. There is
no deactivation/uninstall callback, upgrade migration or new database option.
Stage B's service/store/owned-connection changes remain prerequisites.

## Lifecycle behavior

`Plugin::activate()` retains the compatibility evaluation, then invokes
`Profile_Installation::ensure_installed()` once on a supported single site.
Multisite and network activation return a sanitized unsupported result without
opening Profile storage. Storage setup is independent of H5P/SNORDIAN runtime
admission; SNORDIAN suspension never changes.

The Stage B service validates an existing compatible table without DDL or row
writes. Confirmed absence permits creation of exactly the supported empty InnoDB
table, followed by verification. Unsupported schema/engine/version, unavailable
metadata, denied creation and uncertain outcomes never trigger repair, migration,
DROP, preference initialization, or database rollback. Its installer-only no-replay
guard and private-session SQL-mode checks remain in force.

Failure does not emit activation output, call wp_die, return WP_Error to WordPress,
or deactivate the plugin. WordPress completes activation normally. Subsequent
administrator notices use fresh read-only status to report missing/incompatible/
unavailable storage. No additional option/transient stores an activation error.
An uncertain CREATE that actually completed can therefore be reconciled as ready
by read-only inspection; incomplete setup remains visible.

Native deactivation only changes WordPress's own activation record. Papi Jo never
removes or resets the table, preferences, Theme data or permissions.

## Updates without activation

Updates/silent activation need not execute activation callbacks. The next visit
to Plugins or the Papi Jo Themes page detects missing/unsupported Profile storage
read-only and offers explicit retry. No schema operation runs from boot,
plugins_loaded, init, notices, frontend, Profile rendering or ordinary admin GET.
There is no update hook that attempts opportunistic database repair. This also
works for the first upgrade from code which did not register a storage lifecycle
hook, and avoids relying on the old code loaded during an updater request.

## Administrator notices and retry

Notices are visible only to `manage_options` on Plugins (including its network
variant) and the Papi Jo Themes administration page, including translated parent
menu prefixes. Unrelated screens and unauthorized users do not invoke the status
factory. On multisite the notice is informational, with no retry form/database
access. Existing Profile-specific storage warnings and role restrictions remain.

An unready single-site notice contains **Retry Profile storage setup**, posting
to `admin-post.php` with action `pjh5pwp_profile_storage_retry` and a dedicated
WordPress nonce. The handler independently enforces:

1. `manage_options` and a supported single site, before any storage access;
2. POST method (405 for authorized GET);
3. scalar, current-user/session-bound nonce for the dedicated action (403 on
   missing, invalid, malformed or unauthorized requests).

There is no anonymous route. No SQL, table name, user ID, schema version or Theme
definition comes from request fields. Valid requests call the same idempotent
service and redirect with 303 to one of two fixed local admin destinations.
The return field accepts only the literal `themes`; every other value falls back
to Plugins. No supplied URL is used.

An allowlisted URL result supplies immediate retry feedback without persistence.
It is never authority for setup, a migration or success: the notice always reads
actual storage status. Forging a success result cannot conceal missing storage.
All text/attributes/URLs are escaped and dynamic exception/SQL details are never
rendered. Compatible/ready storage normally produces no notice or retry button.
The SNORDIAN compatibility warning remains only on Themes; it was not moved or
suppressed by these new Profile diagnostics.

## Automated verification

- `tests/profile/activation.php`: 94 assertions on PHP 8.4.22 and PHP 7.4.33.
  Exercises the real registered activation callback and plugin composition with
  disposable memory/fault fixtures: empty/repeated setup, degraded failures,
  independent SNORDIAN suspension, update detection, translated screen scope,
  authorization, method/nonce/type/session checks, fixed redirects, forged GET
  results, sanitized failures, uncertain outcomes and unsupported multisite.
- `tests/profile/activation-wordpress.php`: 47 assertions on each PHP version
  using a private copy of WordPress 7.1.4 and a fresh random MySQL 9.1.0 database.
  Covers native activation/deactivation/reactivation, saved bytes/revisions and
  protected options/capabilities, wrong engine/schema/version, injected metadata/
  CREATE-permission/lost-acknowledgement failures, real silent activation, real
  users/nonces, unauthorized requests and actual registered retry dispatch.
  Mail/external HTTP/cron are disabled. Only a disposable SNORDIAN marker plugin
  is active; the installed SNORDIAN plugin and real acceptance.example.test are never loaded.
- Stage B real installer: 133 assertions, three synchronized installation races,
  real no-replay disconnection and ordinary reconnection controls.
- Existing real MySQL suites: Profile 56 assertions and five writer races; roles
  69 assertions, seven administrator races and serialized revocation; Theme
  storage 49 assertions and six writer races plus initialization race.
- 24 PHP suites and eight JavaScript suites passed, covering domain/inheritance,
  administration, Profile preview/suspension/permissions, color transfer and
  runtime configuration/admission/Theme/Density/readiness. Seven selected PHP
  suites additionally passed on PHP 7.4.33.
- All disposable databases are removed after their runs. Native fixture configs
  containing test-server credentials are removed in finally. The copied core is
  test infrastructure under .local-acceptance, never a release payload.

The native fixture harness only accepts a private `wordpress-fixture` directory
inside this repository's `.local-acceptance`. Prepare it with copied `wp-admin`
and `wp-includes`, plus root `wp-load.php`, `wp-settings.php`, `wp-blog-header.php`,
`wp-cron.php` and `index.php`; do not copy an installed wp-config or user content.
Copy the current source entry point/includes/assets/presets beneath its private
`wp-content/plugins/papijo-h5p-themer-wordpress` directory. Then run:

```powershell
php -n tests/profile/activation.php
php tests/profile/activation-wordpress.php '<private root>/wordpress-fixture' '<server-credentials-literal-file>'
```

The harness parses only DB_HOST/DB_USER/DB_PASSWORD literals, never executes that
file or selects its DB_NAME, and validates its random
`pjh5pwp_lifecycle_test_<16 hex>` database name. It generates a private config and
installs WordPress only in that database. Use an explicitly authorized test server
with disposable database creation/removal permission.

The initial native harness ABSPATH guard and one broad-test runner argument were
corrected before final successful runs. Neither was a production-code defect.
The previously documented legacy admin headless form-count test was not rerun or
resolved by this stage. Real restricted CREATE grants and unavailable engines
are covered through scoped fault injection rather than server/account changes.

## Deployment and acceptance remaining

Deploy only after separate approval, accounting for all pending Stage B and C
dependencies: owned wpdb adapter, Profile store, installation service, new Profile
storage admin, Profile loader and plugin composition root. The entry point remains
canonical. Use fresh backups and the targeted protected-record integrity gate;
whole-database equality is not required.

On existing acceptance.example.test, compatible storage should produce no new warning and no
schema/row changes. Firefox acceptance should verify the Plugins/Themes pages,
existing SNORDIAN warning scope, notice-only eligible Profiles, learner diagnostic
privacy, and retained administration/preview behavior when applicable. Activation,
deactivation/reactivation and failure/retry UI should be exercised on a fresh
disposable site; never remove the real Profile table to manufacture a failure.

Before public release, extend qualification beyond the tested WordPress 7.1.4 /
MySQL 9.1.0 baseline to the declared minimum WordPress and online MySQL/MariaDB
environments. No migration, multisite support or deferred slow-network fix is
introduced here. Deployment, online testing and release remain separate approvals.
