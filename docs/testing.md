# Automated test inventory

## Stage G4 stable 0.2.0 preparation

G4 preserves the accepted beta.1 implementation and runs the current 45 non-database
commands on PHP 7.4.33, 8.3.18 and 8.4.22, plus the audited public-source projection.
The complete current inventory is 54 commands when the nine native jobs are opted in.
All nine database/lifecycle suites are explicitly excluded under G4's prohibition
on database/installation changes; an exclusion is not a PASS. Version-specific release
checks retain independent frozen beta.1 assertions and verify stable promotion.
See [G4 results and exclusions](stage-g4-release.md). Owner-reported 17/17 G3B Firefox
passes are recorded separately and were not independently reproduced by Codex.
The existing 15-second readiness limitation and accepted G3A database uncertainty remain.
Historical checkpoint records below retain their original scope.

## Stage G2 administration-viewer candidate

Version **0.2.0-beta.1** adds focused PHP access/transport/attachment and JavaScript
admin-context tests. The existing HTTP harness now checks real WordPress nonces
and authenticated cookie/session isolation for this route. All existing frontend
security cases are retained. Native database suites are excluded; no database
inputs are supplied for this context-only task.
See [implementation and pending Firefox scenarios](stage-g2-admin-viewer.md).
Historical F8 and earlier results below are not new beta acceptance.

## Stage F8 stable 0.1.1 preparation

F8 promotes the accepted beta.3 baseline using metadata changes only. Current
commands, results and explicit exclusions are in [F8 verification](stage-f8-release.md).
All nine native database/lifecycle commands are excluded in F8 to respect the
no-database-changes constraint. The F6 evidence below retains its original scope.
F7's 3/3 Firefox smoke passes on beta.3 are user-reported in the F8 owner request;
they are not newly automated or independently reproduced results.

## Stage F6 beta.3 preparation

F6 passes 52/52 commands on each of PHP 7.4.33, 8.3.18 and 8.4.22:
**156/156 PASS, zero exclusions**. Profile rendering/text now passes 255 assertions.
The complete inventory below remains enabled, including all nine native jobs using
fresh copied core and newly created disposable databases. Syntax passes for all
144 PHP files on each version, 13 JavaScript files and seven Python files.
See [F6 scope, commands, risk disposition and verification](stage-f6-release.md).
F4 recorded 25/25 user-reported manual Firefox tests PASS on beta.2; these are
not automated or independently reproduced results, nor beta.3 acceptance.
The original F3 preservation check below remains a historical failure. In F6 the
project owner explicitly accepts its unattributed residual uncertainty, requires
no further investigation for this release and removes it as a release-blocking
gate. Preservation was not proven; original reports/evidence remain unchanged.

## Stage F3 beta.2 verification

Stage F3 passed the complete **52-command inventory** below on each of PHP 7.4.33,
8.3.18 and 8.4.22: **156/156 PASS, zero exclusions**. Native lifecycle fixtures are populated from the independently
verified beta.2 ZIP and use guarded random disposable databases only. No existing
site is bootstrapped. All tests remain enabled, including frozen beta.1 and current
beta.2 input/candidate assertions. Results are finalized in [F3](stage-f3-release.md).

The public-source validation copy also passes 52/52 on PHP 8.4.22, including all
native suites: **208/208 test commands**, no exclusions. The independent existing-site
database preservation check **fails** for cache-timeout/AUTO_INCREMENT drift in two
sites. It is a release blocker, not a failing functional test that was suppressed.
See [F3 findings](stage-f3-release.md). The F2 matrix below remains historical; F3
uses the same command set.


## Current F2 inventory

The consolidated runner includes **52 commands per PHP version**, with no database
exclusions when explicit native inputs and a private copied WordPress fixture are
supplied. All **156/156 commands passed** on the final implementation, with **zero exclusions**.
The Stage D table further below is a historical checkpoint.

| Command/job | PHP 7.4.33 | PHP 8.3.18 | PHP 8.4.22 |
| --- | --- | --- | --- |
| `release/tooling` | PASS | PASS | PASS |
| `run` | PASS | PASS | PASS |
| `domain/run` | PASS | PASS | PASS |
| `storage/run` | PASS | PASS | PASS |
| `admin/run` | PASS | PASS | PASS |
| `admin/coexistence` | PASS | PASS | PASS |
| `profile/run` | PASS | PASS | PASS |
| `profile/preview` | PASS | PASS | PASS |
| `profile/suspension` | PASS | PASS | PASS |
| `profile/installation` | PASS | PASS | PASS |
| `profile/activation` | PASS | PASS | PASS |
| `owned-storage/run` | PASS | PASS | PASS |
| `owned-storage/activation` | PASS | PASS | PASS |
| `roles/run` | PASS | PASS | PASS |
| `transfer/run` | PASS | PASS | PASS |
| `export/run` | PASS | PASS | PASS |
| `import/run` | PASS | PASS | PASS |
| `admin-transfer/run` | PASS | PASS | PASS |
| `runtime/run` | PASS | PASS | PASS |
| `runtime/coexistence` | PASS | PASS | PASS |
| `runtime/admission` | PASS | PASS | PASS |
| `runtime/attachment` | PASS | PASS | PASS |
| `runtime/configuration` | PASS | PASS | PASS |
| `runtime/div-admission` | PASS | PASS | PASS |
| `runtime/readiness` | PASS | PASS | PASS |
| `runtime/plugin-state-verification` | PASS | PASS | PASS |
| `actual-h5p` | PASS | PASS | PASS |
| `backdrop-parity` | PASS | PASS | PASS |
| `runtime/transport` | PASS | PASS | PASS |
| `runtime/qualification` | PASS | PASS | PASS |
| `profile-preview-core-css` | PASS | PASS | PASS |
| `admin/browser-behavior` | PASS | PASS | PASS |
| `profile/preview-behavior` | PASS | PASS | PASS |
| `runtime/theme-controller` | PASS | PASS | PASS |
| `runtime/density-controller` | PASS | PASS | PASS |
| `runtime/lifecycle-controller` | PASS | PASS | PASS |
| `runtime/readiness-controller` | PASS | PASS | PASS |
| `runtime/return-controller` | PASS | PASS | PASS |
| `runtime/slow-readiness-investigation` | PASS | PASS | PASS |
| `admin/headless-browser` | PASS | PASS | PASS |
| `profile/preview-browser` | PASS | PASS | PASS |
| `runtime/http-check` | PASS | PASS | PASS |
| `admin-transfer/http-dom` | PASS | PASS | PASS |
| `storage/mysql` | PASS | PASS | PASS |
| `profile/mysql` | PASS | PASS | PASS |
| `roles/mysql` | PASS | PASS | PASS |
| `import/mysql` | PASS | PASS | PASS |
| `profile/installation-mysql` | PASS | PASS | PASS |
| `owned-storage/mysql` | PASS | PASS | PASS |
| `owned-storage/production-mysql` | PASS | PASS | PASS |
| `profile/activation-wordpress` | PASS | PASS | PASS |
| `owned-storage/wordpress-innodb` | PASS | PASS | PASS |

The release/tooling job runs all 32 adversarial tests, including the retained
frozen E4 manifest assertion and the separate current F2 manifest assertion.
F1 memory contracts pass 564 assertions; F2 production lifecycle passes 39;
F1 native isolation passes 961; F2 native production factories pass 939; native
WordPress lifecycle passes 100 for each options engine, on each PHP version.
All 144 PHP files lint on every version; 13 JavaScript files pass syntax checks
and seven Python files parse without bytecode.

The first full PHP 8.3 run was 50/52: a residual synthetic 84-member expectation
and an authorization-before-suspension rendering regression were corrected.
The original Profile suspension assertions remain; the 93-member assertion
now matches the independently pinned reviewed production inventory. Final full
runs all pass. A separate restricted-token release check could not create its
native Windows junction; the normal-permission final runs exercise that check
successfully. No test was excluded or weakened.

F1 `owned-storage/run` contracts exercise the shared storage algorithm through
compatibility aliases; `owned-storage/mysql` preserves isolated injected-factory
coverage. F2 `owned-storage/activation` covers production lifecycle composition;
`owned-storage/production-mysql` runs the same native races through real production
connection factories and default authorization. Native WordPress lifecycle runs on
both MyISAM and InnoDB options. Existing historical options-adapter suites remain
in the inventory; they do not establish current production backend selection.

Example native production-factory variant:

```text
PHP_CLI tests/owned-storage/mysql.php WORDPRESS_ROOT CREDENTIAL_LITERAL_SOURCE --production
```

All native jobs select guarded random disposable databases and verify deletion.
No existing site database is selected, no release ZIP is generated, and the
private copied core fixture is not a deployed WordPress site.


Stage F1 adds two **isolated, explicitly loaded** development entry points for
plugin-owned state; they do not replace the production adapters or enter the
installable ZIP. See [F1 schema, guarantees and test commands](owned-storage-stage-f1.md).
Their memory/fault and disposable-native results are recorded separately from
the unchanged 47-command production inventory below.

Stage E4 verifies the current **47-command inventory on PHP 7.4.33, 8.3.18 and
8.4.22**, including all six disposable database/native source-copy lifecycle
commands: 47/47 pass on each version, no failures or skips. The tooling suite now
has 32 tests, including candidate metadata and the requested acknowledgments.
See [refreshed candidate verification](stage-e4-release.md) and the historical
[E2 verification](stage-e2-release.md). E3's reported 8/8 manual Firefox acceptance
applies to the original E2 ZIP. The refreshed E4 ZIP is not installed; that
separately approved procedure is in [the clean-install plan](clean-install-test-plan.md).

Stage E1 adds `tests/release/test_tooling.py` to the consolidated runner. The
current inventory is 47 commands with explicit disposable-database inputs, or 41
non-database commands with parity enabled. See [release tooling](release-tooling.md)
and [E1 verification](stage-e1-readiness.md). The Stage D counts below retain their
original completed-run scope; E1 does not claim to rerun real-database tests.

Stage D verification, 11 November 2026. The complete inventory below passed on
PHP **7.4.33**, **8.3.18** and **8.4.22**: **46/46 commands per version**, no exclusions
or remaining failures. JavaScript/HTTP/browser commands were repeated with each
PHP-generated fixture set; these are not independent browser engines. Tools were
Node.js 22.23.1, Python 3.13 and Microsoft Edge 157.0.4341.46 (Chromium headless).
Native fixture reference: WordPress 7.1.4 and MySQL 9.1.0. This is not a complete
WordPress/PHP/database/browser compatibility matrix.

## Consolidated runner

`tests/run-all.py` runs each command in a separate process, records exit codes and
private per-command logs, and continues after a failed command. No dependencies
need installing through npm or Composer. Use a fresh private report directory.
Replace the angle-bracket arguments before running the following command:

```text
python -B tests/run-all.py --php <php.exe> --h5p-root <official-H5P-source-root> --wp-source <WordPress-source-root> --backdrop-root <pinned-Backdrop-source-root> --browser <chromium.exe> --report-dir .local-acceptance/<new-run-name>
```

The Backdrop argument is optional and enables source parity checks. Browser and
H5P arguments are required so source-dependent checks cannot silently skip.
Report output stays private; do not attach raw logs to a public repository/release.

The command above excludes real-database tests unless explicitly given
`--database-credentials <literal-server-credentials-file>`. These tests read only
literal DB_HOST/DB_USER/DB_PASSWORD; they never select that file's DB_NAME or
bootstrap that site's WordPress. They create/drop only random databases under
`pjh5pwp_test_*`, `pjh5pwp_import_test_*`, `pjh5pwp_install_test_*` and
`pjh5pwp_lifecycle_test_*`. mysqli and create/drop permissions are required.
Use a normal PHP ini with mysqli enabled so child storage-test workers inherit it.

For native activation also supply `--wordpress-fixture <private-wordpress-fixture>`:
create a **new** directory named `wordpress-fixture` underneath this workspace's
`.local-acceptance/`, copy only clean WordPress core root PHP files (excluding
wp-config.php), wp-admin and wp-includes, then install the current allowlisted
plugin copy under its wp-content/plugins directory. Do not copy live wp-content,
configuration, uploads, credentials or active plugins. The native harness refuses
non-private roots, creates a random database and private configuration, blocks
external HTTP/mail/update work, and removes its generated configuration/database.
Retain the copied fixture/evidence rather than deleting local backups.

Stage D ran all six database/lifecycle entries using explicit arguments. Each
harness reported database cleanup; the private native wp-config.php was absent
afterward. No real acceptance-site database was selected or bootstrapped.

## Inventory and results

Every row below passed on all three PHP versions. Counts are the suites' reported
assertions/checks for one run, not a claim of unique assertions: some entry points
load shared suites, parity reruns domain checks and the actual-H5P variant repeats
the foundation matrix. Do not add these counts as independent coverage.

| Entry point / variant | Reported checks | Result on 7.4 / 8.3 / 8.4 |
| --- | ---: | --- |
| `tests/run.php` | 1003 | PASS / PASS / PASS |
| `tests/domain/run.php` | 1396 | PASS / PASS / PASS |
| `tests/storage/run.php` | 204 | PASS / PASS / PASS |
| `tests/admin/run.php` | 331 | PASS / PASS / PASS |
| `tests/admin/coexistence.php` | 60 | PASS / PASS / PASS |
| `tests/profile/run.php` | 251 | PASS / PASS / PASS |
| `tests/profile/preview.php` | 104 | PASS / PASS / PASS |
| `tests/profile/suspension.php` | 51 | PASS / PASS / PASS |
| `tests/profile/installation.php` | 354 | PASS / PASS / PASS |
| `tests/profile/activation.php` | 94 | PASS / PASS / PASS |
| `tests/roles/run.php` | 46 | PASS / PASS / PASS |
| `tests/transfer/run.php` | 196 | PASS / PASS / PASS |
| `tests/export/run.php` | 102 | PASS / PASS / PASS |
| `tests/import/run.php` | 358 | PASS / PASS / PASS |
| `tests/admin-transfer/run.php` | 92 | PASS / PASS / PASS |
| `tests/runtime/run.php` | 548 | PASS / PASS / PASS |
| `tests/runtime/coexistence.php` | 70 | PASS / PASS / PASS |
| `tests/runtime/admission.php` | 73 | PASS / PASS / PASS |
| `tests/runtime/attachment.php` | 34 | PASS / PASS / PASS |
| `tests/runtime/configuration.php` | 1001 | PASS / PASS / PASS |
| `tests/runtime/div-admission.php` | 51 | PASS / PASS / PASS |
| `tests/runtime/readiness.php` | 24 | PASS / PASS / PASS |
| `tests/runtime/plugin-state-verification.php` | 10 | PASS / PASS / PASS |
| `tests/run.php + actual H5P source` | 1047 | PASS / PASS / PASS |
| `tests/domain/run.php + pinned Backdrop source` | 2315 | PASS / PASS / PASS |
| `tests/runtime/transport.php` | 329 | PASS / PASS / PASS |
| `tests/runtime/qualification.php` | 185 | PASS / PASS / PASS |
| `tests/profile/preview.php + official variables CSS` | 104 | PASS / PASS / PASS |
| `tests/admin/browser-behavior.js` | 30 | PASS / PASS / PASS |
| `tests/profile/preview-behavior.js` | 50 | PASS / PASS / PASS |
| `tests/runtime/theme-controller.js` | 124 | PASS / PASS / PASS |
| `tests/runtime/density-controller.js` | 202 | PASS / PASS / PASS |
| `tests/runtime/lifecycle-controller.js` | 329 | PASS / PASS / PASS |
| `tests/runtime/readiness-controller.js` | 203 | PASS / PASS / PASS |
| `tests/runtime/return-controller.js` | 60 | PASS / PASS / PASS |
| `tests/runtime/slow-readiness-investigation.js` | 239 | PASS / PASS / PASS |
| `tests/admin/headless-browser.js` | 28 | PASS / PASS / PASS |
| `tests/profile/preview-browser.js` | 38 | PASS / PASS / PASS |
| `tests/runtime/http-check.py` | 198 | PASS / PASS / PASS |
| `tests/admin-transfer/http-dom.py` | 51 | PASS / PASS / PASS |
| `tests/storage/mysql.php` | 49 | PASS / PASS / PASS |
| `tests/profile/mysql.php` | 56 | PASS / PASS / PASS |
| `tests/roles/mysql.php` | 69 | PASS / PASS / PASS |
| `tests/import/mysql.php --admin-ui` | 47 + 10 admin UI | PASS / PASS / PASS |
| `tests/profile/installation-mysql.php` | 133 | PASS / PASS / PASS |
| `tests/profile/activation-wordpress.php` | 47 | PASS / PASS / PASS |

The domain parity variant includes 919 Backdrop comparisons. Administration's
331 total includes its 111 focused UX checks and the included role regressions.
`import/mysql --admin-ui` includes the additional 10 transfer-administration checks.
The 23 slow-readiness scenarios / 239 assertions characterize the existing defect;
they do not claim to repair it.

## Included helpers and verification modes

`tests/worker.php` and fixture plugin/double files are dependencies of the main
suites, not standalone live-plugin tests. `domain/in-memory-store.php`,
`domain/backdrop-memory.php`, `storage/fake-database.php`, `storage/legacy-step.php`,
`storage/wordpress-doubles.php`, `admin/wordpress-doubles.php`,
`admin/ux-regressions.php`, `roles/admin-regressions.php`,
`profile/memory-store.php`, `profile/installation-fixture.php`,
`admin-transfer/support.php` and `admin-transfer/mysql-checks.php` are loaded by
those suites. Static rendering modes (`admin/render-fixtures.php`, Profile
`--render`, transfer `--render`) are exercised by browser/HTTP checks. HTTP router
fixtures run only in disposable loopback servers. Example-output modes serialize
the already tested contracts; they are not additional acceptance suites.

Additional validation: all **124** PHP files outside private evidence passed
`php -n -l` on each of the three versions; all **13** production/test JavaScript
files passed `node --check`; all three Python test scripts parsed without writing
bytecode. The production/distribution version header/constant/stable tag all
remain 0.1.0. PHP version_compare confirmed proposed 0.1.1-beta.1 sorts above it.

## Corrected administration browser expectation

The old browser check counted exactly two overview forms from the pre-transfer
interface. The current fixture has five: default, publication, one single custom
Theme export, selected export and all-custom export. The replacement verifies the
exact operation/mode set, independent POST forms, nonce and snapshot fields,
external row Export button ownership outside the publication form, custom-only bulk
selection and publication input ownership. Both intended Import links must target
the dedicated Import screen. All earlier palette/edit/reset/narrow-layout/immutable
preset/default-delete assertions remain. The browser suite now passes **28** checks.
The transfer HTTP/DOM suite separately checks upload/preview/confirmation boundaries.
Production behavior was not changed to satisfy the old assertion.

## Earlier failures, resolved separately

The first restricted-token run passed 24/40 commands. Sixteen failures came from
PHP is_readable reporting false despite successful file reads, and unreachable
loopback fixtures under that token. Normal-permission runs resolved them; no
production fallback or compatibility check was weakened.

An intermediate normal run passed 43/45. Its two failures were in Stage D test
work: the new runner omitted the H5P argument required by DIV admission, and the
first replacement Import assertion expected one navigation link when both toolbar
and transfer section intentionally provide it. Both corrections were applied to
tests only and verified in the three final complete runs. No remaining failures
or skips are concealed by the final result.

Headless Chromium fixtures and deterministic clocks do not prove Firefox first
paint, screen-reader behavior or online hosting compatibility. The maintainer's
prior local Firefox acceptance is recorded separately from Stage D automation.
The [slow-network readiness limitation](slow-network-readiness-investigation.md)
remains unresolved. Clean ZIP installation and online pilot acceptance are still
required before claiming those delivery paths verified.
