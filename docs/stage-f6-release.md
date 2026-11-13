# Stage F6 — controlled local release preparation

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Candidate: **0.1.1-beta.3**, following the verified beta.2 baseline. Numbered beta
candidates are the existing convention. A new identifier preserves the exact
beta.2 bytes accepted in F4; this remains a testing pre-release for owner review,
not an already published stable release. No deployment or publication is authorized.

## Actual scope

The only production PHP implementation edit is explanatory Profile text. Preference handling,
permissions, storage architecture, runtime assets/admission/styling and transfer
algorithms are unchanged. Production version fields and distribution readme change
coherently. Current guides and release checks describe the new candidate; frozen
beta.1/beta.2 metadata, manifests, artifacts and historical reports are retained.

Profile wording:

> Your personal Theme and Density preferences apply to supported H5P content
> embedded in WordPress pages. Styling is not currently applied in the WordPress
> H5P administration viewer or in Subscriber Profile > View H5P content.

## Stage F3 preservation disposition

**DOCUMENTED RISK ACCEPTANCE**, explicitly authorized by the project owner in F6.
The [original F3 preservation check](stage-f3-release.md) **did not pass**. One
theme-related cache-timeout value changed in `wp_options` on each of two existing
test sites, and AUTO_INCREMENT counters moved. Other retained table fingerprints
matched. The writer and trigger remain unidentified; no database corruption was
demonstrated. The observations remain unattributed.

The project owner accepts the residual uncertainty. **No further investigation is
required for this release, and this issue is no longer a release-blocking gate.**
This is a disposition of the risk, not a retrospective PASS or proof of database
preservation. Historical evidence is unchanged. F6 does not access or modify the
affected databases. Deployment/publication approval remains a separate gate.

## Stage F4 Firefox acceptance — user-reported

The project owner reported **F4.1–F4.25: 25/25 manual tests PASS** on a fresh
WordPress installation using beta.2, MyISAM `wp_options`, and InnoDB owned tables.
These are user-reported Firefox results, not automated tests and not independently
reproduced by Codex. They apply to beta.2 and do not certify the new beta.3 bytes.
Exact Firefox, PHP, WordPress, H5P and content-library versions were not supplied
with this acceptance summary; do not infer a broader compatibility matrix.

| Reported tests | Coverage |
| --- | --- |
| F4.1–F4.4 | MyISAM environment, installation/activation, Theme initialization, owned-table engines |
| F4.5–F4.13 | Duplicate Mint, persistence, color override/inheritance, publication, global default/protection/clearing, deletion |
| F4.14–F4.16 | SNORDIAN administration/runtime coexistence and Mint playback after SNORDIAN deactivation |
| F4.17–F4.22 | Subscriber access, Lavender persistence/playback, Large Density persistence/playback, user isolation |
| F4.23–F4.25 | Revocation/global fallback, restoration preserving choices, independent Theme/Density including Lavender with Small Density |

Inherited colors are restored by clearing the override and saving. There is no
separate restoration button. Theme and Density share one role permission for
personal selection; their values and runtime effects are independent.

## Storage, security and compatibility

- WordPress `wp_options`: **MyISAM or InnoDB supported**, with no conversion of
  WordPress-owned tables required or performed by Papi Jo.
- Plugin-owned `<prefix>pjh5pwp_state`: **InnoDB**, with independent
  `theme_library` / `role_permissions` records, revision/exact-byte conditional
  writes and transaction-held permission checks.
- Plugin-owned `<prefix>pjh5pwp_profiles`: **InnoDB**, preserving personal choices
  through role revocation/restoration and SNORDIAN runtime suspension.
- Activation/setup lifecycle, capability/nonce/own-user checks and legacy-state
  refusal remain unchanged. Legacy options are not migrated, deleted or reset.
- Export/import transfers **Theme color definitions only**, including inheritance
  and descriptive metadata; Density, preferences, roles, global defaults and
  publication settings are not transferred. Backdrop Density metadata is ignored.

Declared minimums remain WordPress 6.5 and PHP 7.4. Existing inspected references
are WordPress 7.1.4, H5P 1.17.10 / Core API exactly 1.28, and MySQL 9.1.0; these do
not certify all hosts, browsers, libraries or version combinations. Single-site,
standard wpdb/mysqli, private connections and supported owned schemas are required.

## Accepted, deferred runtime limitations

1. No Papi Jo styling in the WordPress H5P administration viewing interface.
2. No Papi Jo styling in Subscriber Profile > View H5P content.
3. The initial 15-second readiness budget can expire, leaving native fallback
   without automatic personal styling for that document.

Personal Theme and Density preferences work for supported H5P content embedded in
ordinary WordPress pages. These limitations remain accepted and unresolved; F6
does not change runtime loading, timing, styling or scope.

## Verification and artifacts

F6 full regressions pass **52/52 commands on each of PHP 7.4.33, 8.3.18 and
8.4.22: 156/156 PASS, zero exclusions**. This includes 32 adversarial tooling tests,
Profile rendering/text (255 assertions), Theme/Density independence, permission
revocation/restoration, SNORDIAN coexistence, colors-only transfer, native owned
storage races/locking and WordPress lifecycle with both MyISAM and InnoDB options.
Native references are WordPress 7.1.4 / MySQL 9.1.0. Each native harness reports
disposable database cleanup; no existing installation/database is bootstrapped.

All 144 PHP files lint on each PHP version; 13 JavaScript files pass `node --check`
and seven Python files parse without bytecode. PHP version ordering confirms
beta.3 follows beta.2. The initial restricted-token tooling run was 31/32 because
temporary Windows junction creation failed. The normal-permission repeat and all
final matrix runs pass all 32 tests without skipped or weakened assertions.

Commands use the existing runner:

```text
python -B tests/run-all.py --php <php.exe> --h5p-root <official-H5P-source> --wp-source <private-copied-core> --backdrop-root <pinned-Backdrop-source> --browser <Edge.exe> --report-dir <fresh-private-report> --database-credentials <literal-server-credentials> --wordpress-fixture <fresh-private-wordpress-fixture>
python -B tests/release/test_tooling.py
php.exe -n -l <each-reviewed-PHP-file>
node --check <each-reviewed-JavaScript-file>
```

Only literal server credentials are read, never executed or printed; the source
DB_NAME is not selected. Native tests create new guarded random databases and
remove them. The fresh F6 fixture contains 3335 byte-verified core files (maximum
path 228), excludes the source configuration, and is not an existing site.
Browser fixtures use Chromium/Edge; Firefox evidence remains the user-reported F4
results. Shared-hosting and exhaustive minimum-version coverage remain unverified.

The local artifact is **93 files, 153396 bytes**, SHA-256
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Production input digest:
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Python 3.13.14 / zlib 1.3.1 build the exact approved inputs in independent fresh
directories. Archive verification checks structure/metadata/CRCs and every payload
byte. No development/private files enter the ZIP. A build from the complete
public-source projection must reproduce the same archive. Final public-projection
validation and preservation results are retained in the external private F6 report
and `artifacts.json`, avoiding a self-referential public projection digest.
Historical F3 results remain 156/156 commands PASS across PHP 7.4.33, 8.3.18 and
8.4.22, plus 52/52 from the public-source projection. Historical functional passes
did not override the failed preservation check.

The installable policy remains exactly **93 production files**. Beta.2 is preserved
at 153067 bytes with SHA-256
`PRIVATE_VERIFICATION_DIGEST_OMITTED`.
Its frozen public metadata/input records are [beta.2 metadata](release-candidate-beta2.json)
and [F3 inputs](release-f3-inputs.json). [Current metadata](release-candidate.json)
describes beta.3. Private test/build reports remain outside the public source tree.

## Next action

Present the exact local candidate, verification evidence, risk disposition and
accepted limitations for project-owner review. A separately authorized beta.3
Firefox installation/wording smoke check is advisable before publication. Do not
claim F4 was rerun on beta.3. Git commits, tags, pushes, deployment and publication
remain unperformed and require separate authorization.
