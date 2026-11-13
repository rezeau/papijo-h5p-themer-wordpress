# Component boundaries

Current storage composition is documented in [Stage F2](owned-storage-stage-f2.md):
Theme and role services use owned InnoDB state, Profile storage is unchanged,
and legacy options are detected and refused without migration.

Current status, 11 November 2026: the implemented feature set and Stages A/B/C have been deployed locally and manually accepted in Firefox (maintainer confirmation). See [README](../README.md) and [installation](installation.md) for current behavior. Phase-specific statements below retain their checkpoint context. In particular, runtime is now default-on and compatibility-gated, activation installs Profile storage, and active SNORDIAN suppresses own-Profile selectors/preview as well as Papi Jo playback; the older isolated-preview behavior is superseded. The slow-network readiness limitation remains unresolved.

## Implemented in Phase 1A

All production PHP uses `Papijo\H5PThemerWordPress` and child namespaces. Global
constants use `PJH5PWP_`; any future procedural identifiers use `pjh5pwp_`.
Options/usermeta must use `pjh5pwp_`; classes, JS identifiers and asset handles
must use `pjh5pwp-` (hyphenated JS keys require bracket notation).

| Component | Responsibility |
| --- | --- |
| `Plugin` | Compose services; register lifecycle, translations, diagnostics and administration |
| `Compatibility\WordPress_Environment` | Read installation headers and activation records |
| `Compatibility\H5P_Dependency` | Verify exact Core API and loaded integration capabilities without constructing services |
| `Compatibility\Hook_Inspector` | Verify literal asset-action calls in the loaded method, ignoring comments/string decoys |
| `Compatibility\Snordian_Compatibility` | Detect canonical activation and loaded identifiers; never invoke/change foreign code |
| `Compatibility\Runtime_Policy` | Fresh common gate for both future colors and Density; multisite fails closed |
| `Admin\Diagnostic_Notices` | Escaped, translated notices restricted to manage_options |

SNORDIAN's namespace, constant, basename and callback are compatibility facts,
not dependencies or copied implementation. This policy deliberately does not
remove its callbacks, change its options/configuration script, or deactivate it.
An installed inactive copy is harmless; loaded markers conservatively suspend.
The policy is independent of callback ordering and must be rechecked by every
future endpoint/asset adapter. No prior permitted snapshot grants lasting authority.

## Phase 1B domain boundaries

The authorized Phase 1B scope narrowed the earlier adapter proposal to interfaces
and pure schemas. Phase 1C-A now adds the separately authorized production adapter.
See [theme-domain contracts](theme-domain.md) for field-level design and differences.

| Component | Implemented responsibility |
| --- | --- |
| `Theme\Preset_Catalog`, `Color_Properties` | Exact immutable palettes, provenance and property/group definitions |
| `Theme\Theme_Validator`, `Palette_Resolver` | Strict definitions/references and sparse pinned inheritance |
| `Theme\Theme_Operations` | Independent copies, captured Add seeds and override clearing |
| `Publication\Publication_Policy` | Catalog filtering, global eligibility, fallback and deletion protection |
| `Theme\Library_Changes` | Pure revisioned creation/edit/publication/default/deletion plans |
| `Storage\Schema`, `Snapshot_Store` | Versioned option schema, transition invariants and atomic storage interface |

The main file loads class definitions only. No constructor attaches domain hooks,
reads storage, applies palettes or creates defaults. The Phase 1A compatibility
policy remains unchanged. Phase 1C-B additionally registers administration menu and
page-asset hooks. No playback hooks are registered.

## Phase 1C-A persistence

`WordPress_Snapshot_Store` implements the storage interface with exact-byte,
row-identity conditional SQL. It delegates data/transition rules to the existing
domain schema and approved migrations. `Snapshot_Codec` handles native option
serialization without object instantiation. `WordPress_Connections` opens private
`Owned_WPDB` sessions to avoid ambient transactions. `Storage_Installation` provides
explicit idempotent initialization/upgrades and read-only status. `Schema_Migrations`
provides a versioned, default-empty registry for future audited steps.

`Plugin::theme_store()` and `storage_installation()` expose lazy services. No
activation/install/upgrade hook calls their write methods. The Plugins screen can
inspect storage and show escaped administrator diagnostics. Phase 1C-B adds explicit
administration forms over the same adapter. Private database sessions and option-cache
eviction retain their existing guarantees. See [persistence guarantees](wordpress-persistence.md).

## Phase 1C-B administration

`includes/administration.php` loads inert definitions. `Admin_Controller` registers
`admin_menu` (priority 30), `admin_enqueue_scripts` and the resulting page's `load-`
hook. It uses `pjh5pwp-themes` under the observed `h5p` menu when accessible,
otherwise under Settings. Only `manage_options` on single-site is admitted.

`Admin_Service` verifies capabilities, POST shapes, action-specific nonces, the
captured snapshot/preset digest and revision. It prepares only existing domain
plans and commits through `Snapshot_Store`. `Library_Changes::publish_many()`
composes existing per-theme validation into one overview publication revision.
There is no schema change. `Admin_View` escapes the overview, default/publication,
create/edit/copy/delete forms and read-only palettes. `Admin_Messages` translates
diagnostics. `Theme_Preview` produces scriptless sandboxed HTML samples; parent
administration JS updates only their fixed style node. No H5P player is started.

GET, activation and notices never initialize Theme storage. The absent-state page offers
an explicit capability/nonce/context-protected Initialize POST. There are no
automatic upgrades, destructive recovery or foreign settings writes. Domain
management remains available with SNORDIAN active or H5P missing; previews require
the verified supported H5P integration and fixed readable Core CSS.

See [administration/parity design](administration.md) and the
[Phase 1C-B verification and acceptance checklist](phase-1cb-verification.md).

## Phase 2A profile preferences

Phase 2A-H now supplies `Profile_State` and `Profile_Store`: one explicit-install
InnoDB table, unique user primary key, canonical versioned aggregate and private
session exact-state/revision CAS. No usermeta fallback or implicit migration exists.
See [hardening verification](phase-2ah-verification.md) for the final schema,
installation gate, guarantees and tests. The prototype's four-key write loop is
removed. Stage C now ensures the empty supported table during single-site native
activation. Existing tables/records remain unchanged; failures leave activation
available with administrator-only diagnostics and an authorized retry POST.
Ordinary page requests only inspect storage. Updates which skip activation offer
explicit retry; no deactivation cleanup or implicit migration exists. See
[Profile storage lifecycle](profile-storage-stage-c.md).

`includes/roles.php` loads the Role_State/Store/Service/View definitions without
I/O. Role_Service::allowed(target ID) is the authoritative resolver: target user's
`manage_options` capability, then any assigned and currently registered enabled
role, else denial. Administration separately checks the actor's `manage_options`;
preferences additionally require own identity and `edit_user`. Display labels and
historical profile flags never authorize. Role discovery uses `wp_roles()`.

Role_Store follows the Phase 1C-A private-session exact-byte/physical-row-ID option
CAS pattern for `pjh5pwp_role_permissions`. Its own schema-1 envelope contains one
monotonic revision and a sorted sparse map of stable role slugs to true. Missing
configuration denies non-administrators; malformed configuration is preserved and
blocked. No new table or theme-library schema change is needed.

`Profile_Service` and `Profile_Controller` retain the established own-Profile
validation/deferred-commit lifecycle, independent theme/Density controls and profile
CAS. Individual permission forms and handlers have been removed. The existing
profile schema stays version 1: its two historical flags are inert compatibility
bytes, validated and retained but never used for authorization or HTTP updates.
Role-authorized preference commits recheck authorization under a short shared lock
on the role option, held through the private-session profile CAS. Role revocations
therefore serialize with preference writes; core WordPress fields remain outside
this protocol. Administrator preference commits bypass role configuration.

Phase 2A-R introduced no playback hook or class application. See [Phase 2A-R verification](phase-2ar-verification.md)
for capability semantics, security/concurrency evidence, preserved preferences,
schema compatibility, limitations and deployment/Firefox acceptance gates.

## Phase 2B-2B admission and read-only transport

`runtime-wordpress.php` loads the pure Phase 2B-2A descriptor/serializer and the
WordPress adapter definitions. Plugin composes one shared Profile_Service and
Runtime_Policy. Runtime_Admission only adds context exclusion to that policy;
it never enables browser application. Playback_Registry observes validated main
content through `print_h5p_content` without changing returned HTML, and exposes
request-local non-personal candidates through a dormant Plugin method.

Appearance_Endpoint registers authenticated/anonymous admin-ajax actions. Its
strict same-origin frontend POST boundary resolves the current session identity,
then invokes the existing appearance service. No request field selects a user,
theme or Density. Responses are private/no-store and have no CORS grant. No
personalized values enter HTML, H5P settings, dependency caches or assets.
See [the contract and refresh responsibilities](runtime-transport.md).

## Phase 2B-2C/2D browser Theme and Density boundary — default disabled

`Runtime\Theme_Attachment` consumes the existing registry and checks the same shared
admission at footer queuing and own-tag output. It emits only a public candidate
manifest on one static parent-page script. Default configuration registers no
attachment hooks or assets. No personal value enters shared HTML or H5P assets.

`assets/js/theme-controller.js` validates private transport, qualifies unique main
players, applies approved color properties and conditionally restores original
values/priorities. Generation/expiry and lifecycle handling cover DIV and Core's
same-origin internal iframe documents. Native Density uses a separate owned class
set on each qualified root, captures native originals, conditionally restores them
and dispatches resize to the proved instance. Theme readiness and Density readiness
are evaluated independently. See [controller design and activation](theme-controller.md)
and [Density source verification](phase-2b2d-verification.md). Real browser acceptance is pending.

## Remaining future boundaries — not implemented

| Boundary | Planned responsibility |
| --- | --- |
| `H5P` | Real-browser qualification and separately authorized runtime activation |
| `Density` | Real-browser native class/scaling and library-specific resize acceptance |
| `Compatibility` | Real-site cache/session acceptance and refined aggregate runtime diagnostics |

Do not add empty implementations, settings, routes, endpoints, schema upgrades or
runtime hook registrations merely to reserve these boundaries.

Theme resolution must distinguish Site default, explicit H5P Core defaults, five
immutable built-ins and published custom themes. Pin preset versions; do not
introduce live custom-to-custom inheritance inadvertently. Native Density is
independent of colors. Legacy theme density must not become a playback default.

User identity must come from WordPress authentication, never a submitted user ID.
Keep personal preferences out of public/shared HTML and user-independent asset
URLs. Future runtime CSS should be gated by a current admission decision so stale
cached HTML cannot reactivate our theming alongside SNORDIAN. Phase 2B-2B provides
transport; Phases 2B-2C/2D supply default-disabled Theme and Density application. Persistence evicts only the normal options-cache
entries needed after its own aggregate option changes; authoritative reads bypass them.

## Current checkpoint and deployment gate

Phase 1C-C/1C-D deployment and manual acceptance have completed; the user confirms
11 original Firefox steps and four refinement rechecks passed. Phase 2A-H is
deployed, and Phase 2A-R deployment is recorded separately. Phase 2B-2D is source-only.
Live acceptance needs its own approved scope. Shared Theme/Density attachment remains
disabled by default. Phase 2B-2E cache/lifecycle source qualification is complete;
see [the report and controlled future deployment plan](phase-2b2e-verification.md).
Deployment and any Firefox runtime trial require separate explicit authorization.
