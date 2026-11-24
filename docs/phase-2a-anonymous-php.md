# Phase 2A â€” anonymous PHP integration checkpoint

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Baseline: public checkout fast-forwarded to c7312690129b107beec4bae561b80a8d21160fe8.
Development snapshot: PRIVATE_EVIDENCE_OMITTED;
266 source files archived and read-back verified; snapshot.json records SHA-256 values.
Private evidence and generated caches are outside the source snapshot.

The independent `pjh5pwp_allow_anonymous_appearance` option defaults off. Only
manage_options administrators on single-site may explicitly save the strict
boolean form with nonce and completeness checks. Reads recognize WordPress's
boolean true database representation (`1` as a string); other truthy inputs
cannot enable permission. No option is created on activation or ordinary reads.

Anonymous PHP uses existing publication, palette, schema, compatibility and
appearance services. The new endpoint is separate, anonymous-only, read-only,
same-origin, bounded, player/page-token protected and no-store. Identity precedes
nonce authorization. Profiles and role preferences are not used by this module.

`Delivery_Mode::ANONYMOUS_READY` is fixed false. Production anonymous requests
return runtime_pending even with permission saved. The composition root and
attachment support one delivery decision, but only the existing authenticated
or default controller can be emitted. No anonymous script, selector or grants
are emitted. No JavaScript or CSS changed. No live handoff was implemented.

Anonymous default responses redact the real Theme identity with an opaque
custom/effective identifier accepted by the unchanged existing controller.
The new anonymous endpoint uses the companion's effective/null identity shape.
Both conceal real unpublished identities; Phase 2B may align the shapes when its
controller is integrated. Authenticated descriptors are unchanged.

Focused checks on PHP 8.3.18, without WordPress bootstrap/database:
- tests/runtime/anonymous.php: 91 assertions.
- tests/admin/anonymous.php: 8 assertions.
- tests/runtime/run.php: 548 assertions.
- tests/runtime/attachment.php: 34 assertions.
- tests/runtime/transport.php with installed H5P source definitions: 330 assertions.
- tests/domain/run.php: 1396 assertions, including publication rules.
- Syntax checks: every new/modified PHP file passed.

The existing tests/admin/run.php fails at Custom edit live metadata. The exact
same failure was reproduced in the untouched v0.2.1 snapshot; it was not fixed.
No browser acceptance, database tests or broad version matrix was run.

This is not a release candidate. The plugin retains its v0.2.1 version metadata;
release assertions/manifests remain historical pending later approved packaging.
Source/installer allowlists include the new files. No deployment, public commit,
push, release change or companion modification occurred. Phase 2B must explicitly
implement and verify the new controller/selector before changing readiness.

## File inventory

Modified:
- `docs/public-source-allowlist.txt`
- `docs/release-zip-allowlist.txt`
- `includes/Admin/class-admin-controller.php`
- `includes/Runtime/class-appearance-endpoint.php`
- `includes/Runtime/class-theme-attachment.php`
- `includes/administration.php`
- `includes/class-plugin.php`
- `includes/runtime-wordpress.php`
- `tests/runtime/transport.php`

Added:
- `docs/phase-2a-anonymous-php.md`
- `includes/Anonymous/class-appearance-endpoint.php`
- `includes/Anonymous/class-permission.php`
- `includes/Anonymous/class-player-grants.php`
- `includes/Anonymous/class-public-appearance.php`
- `includes/Runtime/class-delivery-mode.php`
- `tests/admin/anonymous.php`
- `tests/runtime/anonymous.php`
