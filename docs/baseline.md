# Phase 1A source baseline — 6 November 2026

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

## Verified facts

- Primary source: `BACKDROP_REFERENCE_ROOT`.
- Release metadata: `0.5.0`.
- HEAD: `d9b53d706a94dac322b89885bf668145ab3803c4`, dated
  4 November 2026 at 15:32:13 +01:00, subject
  `Release v0.5.0 universal native Density`.
- All 31 distribution-allowlist files match that commit after CRLF normalization.
  This statement does not certify every development/documentation file as clean.
- Original repository HEAD remains
  `39d03814d0d0600dad436cb8ed4e07377057c4d0`, dated 18 October 2026.
- `acceptance.example.test` files declare WordPress 7.1.4, official H5P 1.17.10 and Core API 1.28.
  SNORDIAN's installed plugin declares 0.0.12. Phase 0's read-only database query
  established that H5P and SNORDIAN were both active. Phase 1A did not query or
  modify the database; its optional installed-source probe simulates activation.
- The project folder was empty before Phase 1A. No applicable AGENTS.md was found.
- File-content snapshots protect both Backdrop source trees, the deployed module,
  Backdrop active configuration, every existing acceptance.example.test plugin, and both CMS
  configuration files during this phase. See the verification record for results.

## Documentation reconciliation for this port

Reconciliation is recorded here; upstream files are not edited.

The v0.5.0 README/release notes retain earlier R3-B statements that no release
commit/package/publication/deployment had occurred and that R4 remained pending.
The later release commit is now verified. Treat those preparation statements as
historical context, not a denial of the observed commit. Publication/package
availability and final R4 evidence remain unverified; do not infer them from the
commit subject or rewrite historical browser results as new tests.

Phase 0 established that backdrop2 is not an exact v0.5.0 module deployment. Its
31 allowlisted files match the universal-density experiment; 27 match v0.5.0.
The four differences are version metadata and explanatory text in
`h5p_themer.info`, `h5p_themer.admin.inc`, `h5p_themer.install` and
`h5p_themer.module`. The native Density PHP and JavaScript match v0.5.0 exactly.
The served JavaScript hash was verified in Phase 0, and activity 35 served
transport 5 / policy 4 metadata. These HTTP observations were not browser tests.

The authoritative behavior is universal **structural eligibility**, not a
universal visual-compatibility guarantee. Density uses transport 5 / policy 4,
Core API exactly 1.28, and uniquely owned ready top-level DIV/internal-iframe roots.
Nested children are not direct Density/resize targets. Original spacing remains
before an explicit preference. Theme-density fields are legacy metadata; theme
reset does not reset Density. Live Density preview remains deferred.

## Accepted WordPress design decisions

1. Theme selection and Density selection permissions are independent.
2. Retain theme publication controls.
3. Offer Original Density, Small, Medium and Large.
4. Apply profile changes after reload.
5. Initially prioritize anonymous defaults in external embeds.
6. Defer multisite support.
7. Preserve palette provenance and license notices.
8. Keep controls exclusively in administration/profile interfaces, never in
   playback activities or H5P content-editing forms.

These decisions supersede Backdrop's tab-session controls and its Density
dependency on learner theme selection. None is implemented as a feature in 1A.
