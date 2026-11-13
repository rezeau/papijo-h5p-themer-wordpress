# Phase 3A — Custom Theme color export/import design

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

Date: 8 November 2026. Status: investigation and proposal only; implementation is not authorized.

## Scope and controlling clarification

**Theme Export/Import handles THEME COLORS ONLY.** The user's later clarification supersedes the initial request to preserve Density in transfer files. Transfer preserves theme names, stable identifiers, inheritance and sparse color definitions. No exported JSON contains Density. Import must neither import nor modify Density settings or preferences. Density remains entirely independent.

This phase creates only this report. No source code, installed plugin, database, activation state, runtime flag, preferences, permissions or Backdrop implementation has been changed. No live test themes, commits, tags, pushes or releases were created. Tests described below use existing memory-only fixtures.

The user's current operating baseline is Papi Jo ACTIVE, runtime ENABLED and SNORDIAN ACTIVE, with SNORDIAN taking runtime precedence. The 18 passed manual Firefox tests are user-supplied acceptance evidence, not tests repeated here. Earlier deployment documents describe other historical states; they do not supersede this instruction. No live CMS bootstrap, database query or activation check was performed in this investigation.

**Recommendation, pending approval:** keep one small, versioned, colors-only JSON bundle for single and bulk WordPress exports, and accept native Backdrop single custom-theme exports through a narrow input adapter. Validate their legacy `density` property as source-format metadata, then discard it before shared color validation and persistence planning. Do not copy it into a stored definition, preferences or settings. Use one bounded JSON decoder, shared color validation and strict create-only atomic commits. Native-input compatibility is feasible without transferring Density; native-output compatibility remains outside scope. The previously proposed density-free persistence prerequisite still needs approval before implementation; accepting native input does not itself settle or require its proposed schema numbers. Do not fabricate a legacy Density value to bypass validation.

**Reconsideration, 8 November 2026:** the latest user clarification supersedes this report's earlier blanket rejection of Density-bearing native Backdrop exports. Accepting and discarding recognized source metadata is permitted; importing or modifying Density behavior/state remains prohibited. The compatibility below is proposed support to implement after authorization, not an already available importer.

All sections marked **Verified** describe inspected code or observed test results. All sections marked **Proposed — requires approval** describe future work, including numerical limits and version numbers. Authorization to investigate is not authorization to execute a migration or implement Phase 3B.

## 1. Evidence and source baseline — Verified

Inspected source roots:

- WordPress: `PROJECT_ROOT`.
- Authoritative Backdrop module: `BACKDROP_REFERENCE_ROOT`.
- Supporting Backdrop Core definitions, read only: `LOCAL_PATH_OMITTED`, declaring Backdrop 1.35.2.

The authoritative module's `h5p_themer.info` declares version **0.5.0**. Read-only Git HEAD lookup returned `d9b53d706a94dac322b89885bf668145ab3803c4`, matching the WordPress baseline documentation. Git status could not establish worktree cleanliness: WordPress has no Git repository in this supplied directory, and Backdrop's Git status reported that it must be run in a work tree. No Git configuration was repaired. File-content comparison, rather than a claim of clean Git status, protects this investigation.

No applicable `AGENTS.md` was found in either project or the WordPress ancestor directories examined. Existing architecture, persistence, administration, baseline and phase-verification documents were read alongside implementation. Backdrop's configuration documentation contains historical release/Density guidance; current behavior below is grounded in actual v0.5.0 functions.

| Source, relative to its root | Evidence used |
| --- | --- |
| Backdrop `h5p_themer.config.inc` | `h5p_themer_config_field_errors()`, `h5p_themer_config_load()`, `h5p_themer_selection_reference()`, `h5p_themer_theme_resolve()` |
| Backdrop `h5p_themer.presets.inc` | Immutable preset identities, exact catalog version, palette values |
| Backdrop `h5p_themer.module` | Configuration registration, access, native sync validation/application guards |
| Backdrop `h5p_themer.import.inc` | Native single-object decode, create-only validation, locked submission, failure handling |
| Backdrop `h5p_themer.admin.inc` | Independent copies, Add seeds, destination/default checks, shared write lock |
| Backdrop `h5p_themer.visibility-publication.inc`, `h5p_themer.install` | Restrictive publication writes, verification, schema witness and retired updates |
| Backdrop `docs/configuration.md`, `docs/administration.md`, `tests/native-theme-import.php` | Documented compatibility limits and existing test cases |
| Backdrop Core `modules/config/config.admin.inc` | Native single export/import and full-site archive export |
| Backdrop Core `includes/config.inc` | Storage JSON encoding/decoding, metadata, save behavior, archive contents |
| WordPress `includes/Theme/class-theme-validator.php` | Strict stored-definition validation and required legacy Density field |
| WordPress `includes/Theme/class-palette-resolver.php`, `class-theme-operations.php`, `class-library-changes.php` | Inheritance, copies, mutation plans, publication and revision changes |
| WordPress `includes/Publication/class-publication-policy.php` | Availability, typed identities, defaults and deletion protection |
| WordPress `includes/Storage/class-schema.php`, `class-snapshot-codec.php`, `class-wordpress-snapshot-store.php` | Aggregate shape, limits, atomic transition and exact-byte CAS |
| WordPress `includes/Storage/class-schema-migrations.php`, `class-storage-installation.php` | Explicit upgrade framework; no shipped migration steps currently registered |
| WordPress `includes/Admin/class-admin-controller.php`, `class-admin-service.php`, `class-admin-view.php`, `class-admin-messages.php` | Management page lifecycle, capability/nonce/context checks, escaped output |
| WordPress `includes/Profile/class-profile-service.php`, `class-profile-store.php`; `includes/Role/class-role-state.php` | Independent preferences and role storage |
| WordPress `includes/Compatibility/class-runtime-policy.php`; `includes/Runtime/class-theme-attachment.php`, `class-appearance-service.php` | SNORDIAN precedence and runtime flag independence |

## 2. Backdrop storage and transfer behavior — Verified

### Versions and stored objects

Module release **0.5.0**, installed update/schema witness **1003**, definition schema **1 or 2**, settings schema **1 or 2**, and visibility policy schema **1** are different version domains. None is an existing portable bundle version.

Custom definitions are native named configuration objects, registered as the `h5p_themer.theme` collection with `name_key = id` and `label_key = label`. A custom identity `ocean` is stored as `h5p_themer.theme.ocean`, conventionally in `h5p_themer.theme.ocean.json` for file storage. Settings and learner visibility are separate objects, not theme fields.

The exact accepted definition field structure is:

| Field | Current Backdrop contract |
| --- | --- |
| `schema_version` | Required integer 1 or 2 |
| `id` | Required string matching `[a-z][a-z0-9_]{0,63}` and the configuration-name suffix exactly |
| `label` | Required nonblank valid UTF-8 string, maximum 255 bytes, no ASCII control characters or DEL |
| `description` | Optional valid UTF-8 string, maximum 4096 bytes; ordinary tabs and line breaks permitted |
| `density` | Required legacy metadata, exactly `large`, `medium` or `small`; identified here solely to explain existing storage and incompatibility, not proposed for transfer |
| `colors` | Required sparse map of the 33 supported color properties to exact `#RGB` or `#RRGGBB` strings |
| `base_preset` | Required only for schema 2; exactly `id` and `version` for a recognized immutable preset |
| `_config_name` | Optional to the domain validator, but required by native single import; must equal the named object |
| `_config_static` | Optional Boolean Backdrop request-cache metadata |

All other fields are rejected. The required-field list changes for schema 2, so a schema-1 object cannot carry `base_preset`. Native metadata is not a parent relationship or publication state. Core strips `_config_name` on storage read and adds it on save/export. `_config_static` controls request caching; it is not a portable theme setting.

No complete native-definition JSON example is presented as a proposed transfer format: the current native shape necessarily contains the out-of-scope field. The colors-only examples in section 5 deliberately use a different format identifier.

### Identity, inheritance and sparse colors

The five built-ins are `daylight`, `lavender`, `mint`, `sunset` and `dark`, pinned to **`moodle-478fed30d2a4`**. That pin is independent of the module release. Built-in and custom identities are typed separately; a custom `mint` does not replace built-in Mint. Labels can change without changing references. Duplicate labels are not an identity collision.

Schema 1 has no pinned base: omitted color properties follow the H5P Core cascade. Schema 2 overlays only stored sparse hex overrides onto its exact built-in preset. Empty overrides are valid. Trusted built-in expressions remain in the catalog; they are not copied into custom overrides. The catalog has 34 literal properties; `--h5p-theme-contrast-cta-dark` is catalog-only and cannot be a custom override.

There is **no live custom-parent graph**. Duplicating a custom copies its sparse colors, existing preset pin and other stored metadata into an independent identity. Adding from a custom default likewise captures an independent seed. Later source edits do not propagate. Preserve the actual preset relationship, not an invented `parent_theme` or `extends` field. Cycles and topological batch ordering therefore have no role in the existing model.

Saved legacy Density is accepted for existing definition round trips, but current learner playback uses independent Density selection. It must not be used to derive anything in the proposed color transfer service.

### Export capabilities

Backdrop provides native configuration export, not a module-owned portable colors-only exporter:

- `config_export_single_form_update_export()` adds `_config_name` to one configuration object and encodes it into an export textarea. It uses the native configuration accessor; environment-specific overrides may influence that accessor. It does not promise an overrides-free color projection.
- `ConfigDatabaseStorage::encode()` and `ConfigFileStorage::encode()` use pretty JSON through `backdrop_json_encode()`. Empty PHP color arrays can encode as `[]`; the current module validator accepts both empty JSON array/object representations after associative decoding.
- `config_download_full_export()` exports **all site configuration** as `config.tar.gz`, containing named JSON files. This is not a theme-only bundle and must not be copied as the WordPress export design. The archive can contain unrelated site settings.

Individual definition fields are reusable across hosts, but native export requires adaptation to satisfy colors-only transfer. Native whole-site archives are outside the proposed input formats.

### Native create-only import, validation and errors

The module alters Backdrop's existing `config_import_single_form` handlers. `_h5p_themer_import_decode()` requires one JSON configuration object with a string `_config_name`. For theme objects, `_h5p_themer_import_check()` permits **only new schema-2 customs** with a known exact base pin. Schema-1 definitions remain valid stored data but are not accepted by this creation handler.

The handler requires both `synchronize configuration` and authenticated `administer h5p themes` access, compatible H5P, usable publication/activation state, no conflicting destination/policy overrides and no active config synchronization. It validates through native `Config::validateData()` and rejects validation that changes the supplied record. It checks destination storage existence, including malformed files; duplicates are rejected without overwrite or automatic rename. A matching built-in ID is allowed in the separate custom namespace.

Stored and effective global defaults are checked: importing an ID already referenced by an unresolved default is rejected to prevent creation from silently activating that reference. On submission, the handler re-decodes input, checks the validated candidate and repeats dependency checks under the shared `h5p_themer_admin_write` lock, acquired with a 120-second timeout argument. Restrictive unpublished policy is saved and verified before the definition is saved and read back. Other handlers retain their original ordering.

This is **not a multi-object transaction**. A fault can leave a false publication tombstone or a saved hidden definition. Error text acknowledges uncertain persistence and does not automatically restore visibility. Native `Config::save()` alone does not run the module's validation/import hooks. Low-level writers can bypass the coordinated path.

Generic theme/policy create/update/delete synchronization remains blocked at validation and application, including mixed plans. Settings-only and unrelated imports retain their separate native behavior. Historical retired-import update messages do not negate the currently present single-theme creation handler.

Native Form API supplies the session-bound form-token mechanism; tests cover invalid tokens, permission failures, lock races, duplicates and partial writes. Module code does not establish dedicated theme-upload byte/count limits. The single form accepts pasted text, and native `decode()` uses associative `json_decode()` without an explicit module depth limit or duplicate-member detection. Do not claim the proposed WordPress parser hardening already exists in Backdrop.

## 3. WordPress storage and reusable services — Verified

### Persistence and definition model

`Storage\Schema::OPTION` is the single-site option **`pjh5pwp_theme_library`**. Its current aggregate is a native serialized PHP array, not a promised JSON interchange format:

```text
schema_version: 1
revision: nonnegative integer
settings: { schema_version: 2, default_theme: null or typed reference }
definitions: custom ID -> validated definition of schema 1 or 2
publication: { schema_version: 1, themes: optional typed token -> Boolean }
```

`Theme_Validator` implements the same ID, label, description, sparse color and pinned-base rules as Backdrop, but rejects both `_config_*` fields. It also currently requires legacy `density` on every stored custom definition. Simply removing that field from imported JSON and passing the remaining record to `Library_Changes::create()` fails validation. No colors-only importer or exporter currently exists.

`Palette_Resolver` overlays a pinned base for schema 2 and leaves schema 1 sparse against Core. Resolved WordPress palettes omit legacy Density entirely. `Profile_Service::effective()` obtains playback Density from independent profile values. `Theme_Operations` copies custom definitions independently; built-in duplication currently creates schema 2 with zero overrides and legacy metadata. Existing Add/Edit forms expose no theme Density editor.

Publication is separate. `Library_Changes::create()` adds a custom definition and explicitly sets `custom:<id>` false, including reused tombstones. Replace preserves publication. Delete keeps a false tombstone. `Publication_Policy` fixes Site default/Core/Daylight availability, validates custom existence and protects the selected global default from deletion. A valid hidden theme can still be the global default. The whole snapshot validator requires the global reference to resolve; malformed or broken-default aggregates are not eligible for normal writes. An importer must fail closed, not repair the default by adding a missing ID.

`Theme_Operations::duplicate()` and `Library_Changes::create()` reject destination collisions. Existing typed namespaces permit `custom:mint` alongside `builtin:mint`. IDs remain immutable on Edit; display names may change. Import must not reuse Replace or synthesize a request to Add, because Add captures the site's default rather than the incoming record's exact inheritance.

### Atomicity and revision safety

`WordPress_Snapshot_Store` commits definitions, settings, publication and revision together. It uses a private nonpersistent database connection and a single conditional SQL statement, not an unlocked `get_option()` / `update_option()` pair. Updates match the fixed option name, physical row ID and exact **BINARY old serialized bytes**, after expected-revision and domain transition checks. Initialization uses insert-only behavior. No successful import needs one write per theme or a transaction spanning other options.

The adapter qualifies the standard InnoDB options table, utf8mb4 LONGTEXT, complete unique option-name index, auto-increment primary ID, absence of visible triggers and autocommit. Multisite, routing/drop-ins and persistent connections are unsupported. These existing limits must remain in force.

`Snapshot_Codec::MAX_BYTES` is **4,194,304 bytes** for the entire serialized aggregate, including existing definitions and publication tombstones. It disables object instantiation on unserialization and rejects noncanonical/malformed stored data. This is a storage limit, not an existing JSON-upload limit. Future import must also check the size of the complete next aggregate.

`Schema::transition()` requires exactly one revision increment and false publication for new/deleted IDs. `Library_Changes::publish_many()` demonstrates composing a batch into one revision, but there is currently **no `create_many()`**. Calling `create()` repeatedly advances the revision repeatedly and cannot be submitted as one valid next transition without a properly designed batch planner.

Storage outcomes are deliberately distinct: `not_committed`, `committed` after acknowledged write with a later cache failure, and `unknown` for lost acknowledgement/reconnect/ambiguous results. False means known rejection. No importer may flatten these into “nothing imported,” automatically retry an uncertain write or restore an old snapshot over a newer writer.

The migration registry is empty. `Migration_Step`, `Schema_Migrations` and `Storage_Installation::upgrade()` provide explicit, revision-conditional machinery; activation and reads do not execute upgrades. This is reusable infrastructure, not an already implemented colors-only migration.

### Permissions, UI and independent state

`Admin_Service::allowed()` requires `current_user_can('manage_options')` and single-site operation. Management remains available with SNORDIAN active or H5P unavailable; runtime admission is a different concern. Do not transplant Backdrop's H5P compatibility gate into color transfer authorization.

`Admin_Controller` owns the `pjh5pwp-themes` page, under the accessible H5P menu or Settings fallback. Page-specific `load-$hook` handles POST before rendering; assets are restricted to that page. `Admin_Service::submit()` uses exact per-operation key allowlists, capability checks, operation/source/revision/context-bound WordPress nonces and a full-snapshot plus preset-catalog context hash. `Admin_View` escapes text, attributes and URLs; `Admin_Messages` maps constant diagnostics. GET/render/status are read only, with explicit storage initialization as a separate action.

The existing submit allowlist does not accept import/export. Future handlers must be explicit additions to the administration boundary, not arbitrary snapshot input or a bypass through runtime endpoints. Uploaded file bytes must be treated separately from WordPress-slashed form fields; do not unslash or sanitize JSON bytes as text form data.

Profiles live in the prefixed `pjh5pwp_profiles` table; the profile store also detects obsolete four-key usermeta state rather than migrating it silently. Role grants live under `pjh5pwp_role_permissions`. Runtime attachment uses the strict Boolean constant `PJH5PWP_ENABLE_BROWSER_THEME`, with fresh compatibility/SNORDIAN checks. None is part of a theme transfer. The transfer service needs no profile store, role write service, activation API or runtime-controller change.

## 4. Compatibility options — Proposed, requires approval

| Option | Benefit | Limitation under the colors-only requirement | Assessment |
| --- | --- | --- | --- |
| A. Exact native Backdrop JSON | Direct native single-object interchange, existing schema validators | Native definitions require `density`; omitting it is invalid. Native schema-1 and bulk imports are also unsupported there. | Cannot satisfy the clarified requirement unchanged. Do not export native definitions. |
| B. A versioned bundle using Backdrop-compatible color concepts | One single/bulk transport; names, IDs, sparse hex values and exact preset pins retained; no host settings | Records must be a colors-only projection, not complete Backdrop-native records. WordPress import needs density-free domain support. | **Recommended**, with the unavoidable compatibility difference made explicit. |
| C. Separate native/WordPress compatibility modes | Could accommodate native configuration workflows | Separate UI modes and JSON parsers would duplicate security and commit logic | No separate modes needed. Add native source-shape validation/projection to Option B's shared input pipeline. |

### Native Backdrop input adapter — Proposed, requires approval

**WordPress can safely accept a native Backdrop custom-theme JSON object while ignoring its Density property.** The verified source validators establish a bounded, known shape; WordPress does not need Backdrop's CMS, configuration service, import hooks or permission model. Accepting source syntax does not authorize applying all of its fields.

Use the same bounded, duplicate-aware JSON decoder specified in section 8 for both formats. Then dispatch by an unambiguous top-level shape: a bundle with the exact `format` marker, or a native object with `_config_name` matching `h5p_themer.theme.<id>`. Reject a hybrid containing both markers, unknown/missing markers or an unsupported bundle version; do not fall back to native decoding after a bundle error. A native object becomes a one-record color batch. Native arrays or archives are not bulk input.

The adapter performs source-specific validation before projecting a fresh whitelist record:

1. Require native `schema_version` integer 1 or 2; valid matching `_config_name` suffix and `id`; required `label`, `density`, `colors`; optional `description` and Boolean `_config_static`. Schema 2 additionally requires the exact recognized `base_preset`; schema 1 must not have a base. Reject every other field. Validate names, descriptions and colors through shared pure rules, not a relaxed compatibility copy.
2. Accept `density` only as one of the source contract's exact strings `small`, `medium`, `large`. Validation recognizes the source format; it does not select a Density value. Missing, null, scalar of another type, object, invalid string or extra Density-related fields fail closed. Never use an incoming value to satisfy WordPress's required legacy stored field.
3. Check `_config_name` against the ID and validate `_config_static` if present; discard both host metadata fields after validation. Never derive a filesystem path or write a configuration object from `_config_name`.
4. Project exactly `id`, `label`, optional `description`, `colors` and `base_preset`. Schema 1 maps to null base (Core cascade); schema 2 preserves its exact pin. Accept the native export's empty `colors: []` only as a source-specific empty-map case, converting it to the canonical empty color object. Reject nonempty color lists. WordPress bundles retain the stricter object-only rule.
5. Pass this color record through the same proposed canonical color validator, collision checks, review, batch planner and CAS commit as bundle records. The adapter cannot write anything. Discarded metadata must be absent from the canonical record and persistence plan, and must not be retained as theme provenance. Bind review to the exact original bytes/digest, even for ignored metadata, so a changed file requires review again.

Display the source format and a plain review notice: **“Backdrop theme colors only. Legacy Density metadata is ignored. Existing Density preferences are unchanged.”** Do not offer an Apply Density checkbox. The original bounded request bytes may contain the metadata during validation/review; no Density value reaches settings/profile services or the stored theme. This is an explicit projection, not silently accepting arbitrary unknown fields.

| Input/case | Proposed WordPress support |
| --- | --- |
| Colors-only bundle, one or many customs | Supported subject to all shared limits and validation |
| Native single schema-2 custom export, known preset pin and valid legacy metadata | Supported; discard legacy Density/host metadata, preserve sparse colors and pin |
| Native single schema-1 custom export | Supported; preserve sparse Core inheritance. This is WordPress import support, not a claim that Backdrop's own create-only handler accepts schema 1. |
| Native custom ID also matching a built-in ID | Supported in the separate custom namespace; never overwrites a built-in |
| Valid native `_config_static` and empty native color array | Validate/discard cache metadata; normalize only the empty source container as described above |
| Missing/invalid native Density, mismatched name/ID, unknown schema/pin/fields | Unsupported; reject before any write |
| Density field inside a WordPress colors-only bundle | Unsupported; reject rather than extending the bundle contract |
| Backdrop settings/visibility objects, built-in catalog dumps, custom-parent graphs | Unsupported; reject even if some color-looking fields are present |
| Native arrays/mixed-record bundles, whole-site archives, compressed input or URLs | Unsupported; no extraction, partial import or best-effort filtering |
| Colliding destination IDs or duplicate records/members | Unsupported; shared whole-operation rejection |

**Simplest safe approach:** one decoder, one small format-specific source validator/projector, one canonical color validator and one persistence path. Direct reuse of the current stored-definition validator alone is insufficient: it rejects host metadata and requires a Density field. Its ID/hex/preset/name rules can be factored or reused without weakening them; the proposed canonical color validation is shared after projection. Do not load authoritative Backdrop PHP into production WordPress. A second JSON parser, generic config importer or separate compatibility UI mode is unnecessary.

This format can be implemented by other hosts, but Backdrop v0.5.0 does not currently consume it. Reverse native import is unavailable without adaptation and Backdrop-side authorization. No Backdrop changes are proposed for this phase. The exact shared preset pin and color rules give semantic color compatibility, not byte-for-byte native configuration compatibility.

### Internal storage prerequisite

**Recommended candidate, subject to Phase 3B approval:** add a new stored custom-definition schema, provisionally **3**, containing identity, name, optional description, sparse colors and a nullable exact `base_preset`, with **no Density member**. Null means the original schema-1 Core cascade; a preset object means the original schema-2 pinned inheritance. Imported themes use this density-free representation. Existing schema-1/2 definitions remain unchanged, including all their legacy metadata; export only projects their color fields.

Do not make Density optional in old schema numbers without acknowledging that their contract changed. Do not inject `large`, copy the current user's preference or read a site's Density setting to make a new imported definition pass the old validator. No such workaround is approved.

A new definition schema also requires an explicit library-format compatibility decision. Conservatively, version the aggregate reader/writer contract too, provisionally **2**, rather than allowing older software to believe it understands definitions it will reject. A shipped version-1-to-2 migration would copy settings, all existing definitions and publication unchanged, changing only format version and the one revision required by the migration framework. No profile, role, activation or Density values are migration targets.

This is a domain/storage compatibility change, not a Density feature change. It requires pure validator/resolver support for the new definition schema, extension of the trusted migration registry and preservation tests. Existing scalar/hex/preset validation can be factored and reused. Runtime controllers, attachment policy and browser scripts need no change. The schema-2-only inheritance branch in `Palette_Resolver` must explicitly recognize the new schema; accepting a new record without that change would lose its preset inheritance.

Phase 3B must settle and test dual-version read behavior and deployment ordering before adopting these numbers. Existing version-1 libraries must remain readable to runtime while an explicit upgrade is pending; do not install a reader that temporarily treats all existing libraries as invalid. Mutation admission must distinguish legacy-readable from current-writable state. Migration execution remains an explicit, separately authorized operation, never an import side effect. An absent, invalid or upgrade-required library must block import with a clear prerequisite message. If this prerequisite is not approved, export can proceed independently; import stays deferred.

## 5. Portable JSON contract and examples — Proposed, requires approval

Use UTF-8 JSON with exactly three envelope members:

- `format`: exactly `papijo-h5p-theme-colors`.
- `format_version`: integer `1`, independent of stored-definition and aggregate versions.
- `themes`: a nonempty JSON array of custom color records.

Each record has exactly `id`, `label`, `base_preset`, `colors`, and optional `description`. Keep `label` as the existing domain spelling for the displayed Theme name. `base_preset` is required and either null or exactly `{id, version}`. There is no record-level native schema claim. There is no publication, global-default, user, permission, runtime, source-site, credential or Density field.

Single custom theme export:

```json
{
  "format": "papijo-h5p-theme-colors",
  "format_version": 1,
  "themes": [
    {
      "id": "ocean",
      "label": "Ocean",
      "description": "A custom color palette.",
      "base_preset": {
        "id": "mint",
        "version": "moodle-478fed30d2a4"
      },
      "colors": {
        "--h5p-theme-background": "#aBc",
        "--h5p-theme-main-cta-base": "#006FBF"
      }
    }
  ]
}
```

Bulk export uses the same contract. This example preserves a legacy unpinned Core cascade and a pinned preset with no explicit overrides:

```json
{
  "format": "papijo-h5p-theme-colors",
  "format_version": 1,
  "themes": [
    {
      "id": "core_accent",
      "label": "Core accent",
      "base_preset": null,
      "colors": {
        "--h5p-theme-focus": "#123456"
      }
    },
    {
      "id": "mint_copy",
      "label": "Mint copy",
      "base_preset": {
        "id": "mint",
        "version": "moodle-478fed30d2a4"
      },
      "colors": {}
    }
  ]
}
```

Exports must encode `colors` as an object even when empty. The importer should require that JSON shape, rejecting `[]`; this is intentionally stricter than native Backdrop's associative-decoder empty-container behavior. Missing description stays absent; an existing empty description stays empty. Preserve label whitespace, valid Unicode, hex case and shorthand exactly. Do not normalize values or flatten inherited expressions into overrides.

Pins are references to immutable code data, not embedded palette copies. Only known exact ID/version pairs are accepted; unknown pins fail the whole import. Core inheritance is explicit null, not an inferred Daylight fallback. No custom parent references, inheritance cycles, URL-based parents, CSS downloads or inferred dependencies are supported.

## 6. Export rules — Proposed, requires approval

1. Require the existing administrator/single-site gate and a dedicated nonce-bound request. Read one validated authoritative snapshot, without initializing, migrating or repairing it.
2. Select one custom ID, all customs, or an explicit nonempty custom selection for bulk export. Include published and unpublished customs when selected. Typed built-in/Core selections are not exportable custom records; an identically named valid custom is allowed.
3. Project only the approved fields from stored definitions. Never serialize the whole aggregate, a resolved palette, profile state, role option, active-plugin list, PHP configuration or database connection object. Build each record from a whitelist rather than serializing and deleting a few known private keys.
4. Map legacy schema 1 to `base_preset: null`; preserve schema-2 pins exactly. Later density-free definitions use the same wire representation. All existing legacy metadata remains in storage and is absent from the projection.
5. Preserve IDs and names without automatic suffixes. Sort bulk records by ASCII ID and color keys deterministically for readable diffs; ordering does not change sparse semantics. Omitted values remain omitted.
6. Validate and encode the complete output before sending download headers. Fail the entire export for an invalid selected record, incomplete selection, unavailable snapshot, encoding failure or transfer-size/count overflow. Do not silently skip, truncate or emit a partial download.
7. Send attachment JSON with UTF-8, private/no-store headers and `X-Content-Type-Options: nosniff`; use a fixed plugin prefix and validated ID in filenames, never labels or submitted paths. Do not save exports in the public uploads/media library. Do not expose a public download endpoint.

The exported file represents the snapshot read. A later concurrent edit does not invalidate that historical read. If the export request is bound to a displayed revision/context and that context is stale, reject and ask the administrator to refresh instead of silently changing the selection's meaning. Export never increments a revision.

## 7. Import rules and transactional consistency — Proposed, requires approval

### Validation and collision policy

Accept the colors-only envelope in section 5 or one native Backdrop custom object through section 4's adapter. Validate the outer format before treating any object as a theme. Reject unsupported versions, unknown fields, nonobjects, scalars, malformed Unicode and unsupported inheritance. Density is permitted only as validated, discarded legacy metadata in a native object; it remains forbidden in bundle records and canonical color records. Do not coerce versions, trim IDs, repair names or sanitize invalid CSS into an apparently valid palette.

Use existing ID/name/description/hex/property/pin rules through shared domain validation. Color maps contain at most the existing 33 supported properties. Values are exact opaque `#RGB` or `#RRGGBB`; no whitespace, nulls, blanks, alpha values, expressions, URLs, declarations, spacing or typography. A missing override means inherit. No wire value requests deletion of an existing theme's override, because this importer never updates existing themes.

| Collision or reference condition | Required result |
| --- | --- |
| Same custom ID already exists, even if contents are identical | Reject the entire batch, preserve destination |
| Same ID appears twice in the file | Reject the entire batch, even if records are identical |
| ID is valid and matches only a built-in's ID | Create a distinct unpublished custom; never mutate the built-in |
| Two labels match but IDs differ | Allow; display both IDs in review |
| Reused ID has an old publication tombstone | Explicitly reset its token false in the same atomic creation |
| Unknown/missing built-in version or malformed base | Reject the entire batch; no nearest-version or Daylight substitution |
| Unsupported custom-parent field or reference | Reject, not flatten or resolve against another uploaded record |
| Malformed destination library or unresolved global | Reject before planning; recovery is a separate action |

No automatic rename, replace, merge, skip-identical, partial-success or “overwrite existing” checkbox is proposed. An administrator can explicitly prepare a file with new valid IDs and review it again; the importer does not silently change identity. Reimport after a successful creation therefore gives a collision, not an idempotent success claim.

### Review and commit

1. Check authorization, request shape, nonce and byte limits before parsing or reading a file into unbounded memory.
2. Decode with the common limits in section 8, recognize the source shape, and validate/project native input if applicable. Validate every canonical color record and duplicate ID before constructing a storage candidate. No Density or host metadata survives projection.
3. Read and validate the entire current library. Require the approved density-free storage capability to be installed/current. Capture revision and the existing full-state/preset-catalog context.
4. Produce a read-only review listing record IDs, names, pins/Core inheritance and override counts. State that all new themes will be unpublished and existing defaults/preferences remain unchanged. Any error disables commit for the complete file.
5. Bind commit to the exact validated bytes (SHA-256 digest), operation, revision, context and administrator session. Use an authenticated WordPress nonce action covering these values. A two-request UI may resubmit bounded bytes as form data; re-parse and compare their digest. Do not trust a posted decoded snapshot or put raw file bytes in URLs. A digest alone is not authorization. A server-side staging alternative would need separately specified expiry/cleanup and is not required for the initial bounded design.
6. Recheck capability, nonce, file digest, record validation, context and collisions on commit. Re-read authoritative state. Any revision/context difference rejects the operation and requires a fresh review; never automatically rebase a stale import.
7. A new pure `Library_Changes::create_many()` or equivalent validated batch planner adds all definitions and explicit false publication entries to a detached snapshot, preserving settings, every existing definition and all unrelated publication entries. Advance revision exactly once. Validate the full transition and serialized aggregate limit before persistence.
8. Call the existing qualified `compare_and_swap()` **once**. Its internal read/byte/row predicate handles an edit, default/publication change or competing import occurring after validation. A conflict commits none of this batch. No profile/role writes and no runtime callbacks are needed.
9. Report the committed count only after acknowledged success. A known no-write failure reports rejection; `committed` cache-failure or `unknown` outcomes require authoritative inspection before another attempt. Do not claim all-or-nothing acknowledgement when acknowledgement is unknown: the database statement still applies the whole snapshot or none, but the client may not know which occurred.

This reuses single-row atomicity instead of Backdrop's policy-first, definition-second writes. No PHP lock or multi-table transaction is needed for the supported WordPress backend. Unsupported backends remain blocked. Empty bundles are validation errors, not no-op revision increments.

### Preservation guarantees

Import does not change the global default, existing publication choices, personal theme tokens, any Density preference or activation setting, role grants, plugin states, runtime flag or SNORDIAN callbacks/options. Existing definition arrays, including their historical legacy metadata, are copied unchanged into the next aggregate. New imported definitions have no Density member.

Stale personal tokens that happen to match newly created IDs are not rewritten. Explicit false publication keeps new customs unavailable to personal selection; publication remains a separate deliberate operation. Global-default validity is checked before any creation, so import cannot implicitly repair/activate a broken global reference. No automatic publishing or selecting an imported theme follows success.

## 8. Security requirements — Proposed, requires approval

| Concern | Requirement |
| --- | --- |
| Authorization | Recheck `manage_options` and single-site status on review, export and commit. No subscriber/profile capability substitutes. Nonces do not replace capability checks. |
| CSRF | Dedicated WordPress nonce actions use existing operation/revision/context conventions; commit additionally binds the exact content digest. GET navigation and previews never persist. |
| File limits | Initial proposal: maximum **1 MiB (1,048,576 bytes)** and **100 records** per transfer. Apply the same export ceilings so generated files are reimportable. Reject oversized “all” exports and offer explicit smaller selections; never split or omit silently. These are proposed limits, not observed defaults. |
| Upload handling | Check PHP upload error codes and genuine HTTP-upload provenance for upload input, then read only the bounded temporary file. Independently enforce actual bytes read; do not trust submitted MIME/type/size/name. No `wp_handle_upload()` move into public storage, media attachment or permanent raw-payload retention is needed. Handle server `post_max_size`/upload limits and empty/truncated POSTs explicitly. |
| Paste/review handling | Enforce the same byte bound on textarea and resubmitted review bytes. Account for multipart/form overhead at the server limit. Unslash WordPress form text exactly once; never unslash raw uploaded JSON. Reject ambiguous simultaneous upload/paste sources. |
| JSON limits | Maximum nesting depth **16**, explicit parse errors, strict UTF-8 and no substitution/repair, no comments/trailing data, and strict envelope/record/object/array types. Decode to objects first so maps remain distinguishable from lists before domain-array conversion. Reject duplicate object-member names before lossy associative conversion; ordinary `json_decode()` alone does not detect them. |
| Parser implementation | Phase 3B must select and test a duplicate-aware, bounded parser/token pass compatible with the declared PHP 7.4 target. No regex-only JSON validation, `eval`, object unserialization, arbitrary deserialization or runtime package downloads. Existing snapshot serialization is solely for trusted validated internal data, not uploaded bytes. |
| Schema limits | Exact format version and source-specific field allowlists; bounded strings, theme count and color count; explicit canonical nullable base shape. Native metadata is accepted only under section 4's strict checks and discarded. Density/host metadata inside bundle records and all other unknown fields reject the entire input. No migration instructions come from the file. |
| Aggregate size | Preflight `Snapshot_Codec` on the whole next state against 4 MiB, including already stored themes/tombstones. Input size alone is insufficient. Preserve revision-overflow and backend checks. |
| Escaping | `esc_html`, `esc_attr`, `esc_url` as appropriate. Names/descriptions remain untrusted text. JSON is encoded by a proper encoder and downloaded as data, never interpolated into executable script or CSS. Preview only validated definitions through the existing preview service. |
| Concurrency | Bind full snapshot/catalog context; revalidate and make one CAS write. No automatic retry, form rebasing or partial “best effort” imports. All older open management forms become stale after the single revision increment. |
| Errors | Constant translated codes with bounded record index/field context; safely escape identifiers shown to authorized admins. Never echo SQL, credentials, filesystem paths, stack traces or the full uploaded payload. Differentiate malformed input, conflicts, unsupported storage, committed cache failure and uncertain outcomes. |
| Coexistence | Management does not toggle or bypass runtime admission. SNORDIAN's precedence remains intact whether the Papi Jo attachment flag is enabled or disabled. No runtime assets are enqueued by the transfer service. |

Reject archives, compressed inputs and remote URLs. No decompression, filesystem extraction, external fetch or new public REST endpoint is required. Initial implementation should reuse the page-load administration lifecycle and its private headers. The numerical bounds, duplicate-aware parser choice and review transport require approval/testing before they are frozen.

## 9. Verification strategy and current evidence

### Existing tests inspected — Verified

| Tests/fixtures | Reuse in later stages |
| --- | --- |
| `tests/domain/run.php`, `in-memory-store.php`, `backdrop-memory.php` | Exact presets, sparse maps, schema rules, copy independence, typed identities, publication and Backdrop parity |
| `tests/storage/run.php`, `fake-database.php`, `legacy-step.php` | Aggregate CAS, stale revisions, exact bytes, row recreation, initialization, unknown outcomes, cache faults and migration framework |
| `tests/storage/mysql.php` | Separately opted-in disposable real-MySQL CAS races; not a live database test to run in this phase |
| `tests/admin/run.php`, `wordpress-doubles.php`, `ux-regressions.php`, `coexistence.php` | Capability/nonce/context rejection, escaped output, stale forms, page lifecycle and SNORDIAN management coexistence |
| `tests/profile/run.php`, `tests/roles/run.php`, `tests/roles/admin-regressions.php` | Independent preference/role state preservation |
| `tests/runtime/coexistence.php`, `attachment.php`, `transport.php`, `density-controller.js`, `theme-controller.js` | Existing runtime boundaries and independent Density behavior |
| Backdrop `tests/native-theme-import.php`, `config-protection.php`, `visibility-publication.php` | Reference behavior: schema-2 create-only, duplicates, pins, permissions, locks and nontransactional failure paths |

Only these existing commands were executed for Phase 3A, using PHP 8.3.18 and no CMS bootstrap/database connection:

| Command from WordPress root | Observed result |
| --- | --- |
| `php -n tests/domain/run.php 'BACKDROP_REFERENCE_ROOT'` | **PASS: 2,315 assertions, including 919 Backdrop parity assertions** |
| `php -n tests/storage/run.php` | **PASS: 204 persistence-double assertions** |
| `php -n tests/admin/run.php` | Stopped at `Custom edit live metadata`; not a passing suite |
| `php -n tests/runtime/coexistence.php` | Stopped in the `compatible` dependency fixture; not a passing suite |

The latter two stops are consistent with the repository's documented restricted-Windows-token `is_readable()` limitation. A direct read-only PHP probe confirmed `assets/css/preview.css` exists and `file_get_contents()` reads 4,330 bytes, but `is_readable()` returns false; the H5P fixture class similarly reads 2,300 bytes while reporting unreadable. Preview and hook-source admission explicitly use that predicate. No test/source was changed to bypass it, and no broader administration/runtime pass is claimed. Re-run these suites in an authorized normal PHP CLI environment before later implementation is accepted.

No new transfer tests exist yet and no colors-only import success is claimed. No Backdrop native form tests, live MySQL tests, HTTP probes, browser tests or live theme creation were run here.

Report verification parsed both proposed JSON examples successfully (three records total), checked their envelope/record shapes, IDs, preset pins and hexadecimal values, and confirmed that neither contains a Density field. SHA-256 comparison of all **961 pre-existing files** across the two project roots found no changed or removed file; the only addition was this report. This is source-root integrity evidence, not a new live-site state certification.

For the subsequent compatibility reconsideration, the native Backdrop field validator/import handler and current WordPress stored-definition validator were re-inspected read only. Both export examples were parsed again and remain valid colors-only JSON; obsolete blanket native-input rejection rules were removed throughout the report. A fresh comparison of **807 files in the WordPress project** found only this existing report changed, with no added or removed files. The earlier 961-file evidence belongs to the original two-root investigation, not this follow-up's inventory. No importer implementation, source tests, CMS bootstrap, database operation, deployment or Git mutation was performed for this design update. Adapter behavior still requires future implementation and the tests specified above.

### Required future tests — Proposed, requires approval

1. Colors-only JSON golden fixtures for one/many customs, legacy Core inheritance, exact built-in pins, empty objects, absent/empty descriptions, Unicode names, mixed-case/shorthand hex, and custom IDs matching built-ins. Assert every JSON example and generated payload excludes Density at every level.
2. Projection invariance: changing only legacy stored Density metadata must leave exported color bytes unchanged. Round trips preserve IDs, names, optional description, base relationship and sparse color entries exactly. Export never reads profile/role/runtime settings.
3. Strict parsing: malformed JSON/UTF-8, scalar/list roots, wrong map shapes, duplicate members (including escaped equivalent keys), duplicate IDs, unsupported versions/fields, Density-bearing bundle records, unknown pins, malicious CSS/HTML and byte/depth/count boundaries. Assert zero writes on each failure. Add native schema-1/2 fixtures, all three valid legacy Density strings and invalid/missing/type-confused values, name/ID mismatch, Boolean cache metadata, empty native arrays, nonempty color lists and ambiguous format markers. Assert that valid native Density variants project to identical color records and stored candidates, with no Density member; only the original review digest differs. Verify native/bundle inputs converge on the same collision and CAS path, imported re-export is colors-only, and no profile/settings service is called.
4. Density-free stored-schema validation/resolution; equivalence of color palettes before/after projection, including trusted preset expressions. Legacy definitions remain unchanged. Migration tests preserve every existing legacy metadata value and all settings/publication; no profile/role writes. Explicit schema numbers and migration/rollback policy must be approved first.
5. Batch tests with a valid first and invalid last record; internal duplicates; existing identical IDs; stale tombstones; revision exhaustion; complete next-state overflow; stale context and two administrators racing at the actual CAS boundary. Assert all-or-none definitions/publication and exactly one revision/write on success.
6. Fault outcomes: prewrite failures, known CAS rejection, committed/cache failure and uncertain acknowledgement. No partial-success counts or automatic retries. Capture the winning concurrent state and verify it is preserved.
7. Integration tests for capability revocation between review/commit, nonce/content tampering, upload errors, truncated/oversized POSTs, upload versus paste exclusivity, no-JavaScript review and download headers. Test escaping of names/descriptions and bounded diagnostics.
8. Preservation tests compare all prior profile values (including Original/Small/Medium/Large and permission flags), role state, global default, existing definitions/publication and runtime/activation state before and after successful and rejected operations. Repeat management tests with SNORDIAN active using fixtures; no foreign option/callback changes.
9. Later disposable-site acceptance covers imported Edit/save/preview/delete, independent publication/default actions, repeated-import collision, fresh/stale forms and concurrent admins. Existing Firefox theme/Density scenarios are regression checks, not permission to change their settings during Phase 3A.

## 10. Implementation stages and rollback — Proposed, requires approval

| Stage | Scope and independently reviewable exit condition |
| --- | --- |
| **3B: Color domain and JSON contract** | Approve/freeze the colors-only wire schema, common decoder, native source adapter and bounds. Resolve the density-free stored-definition and aggregate-version design, including legacy reads, explicit migration and rollback. Add pure codecs/projections/validators and fixtures only after authorization; no live writes/endpoints/deployment. Prove native legacy Density is discarded, no metadata is fabricated and color inheritance is preserved. Split into 3B-1 wire/source projection and 3B-2 stored-schema/migration design if that makes approval safer. |
| **3C: Export functionality** | Build read-only single/bulk services and download response handling over validated stored snapshots. Test whitelist isolation, deterministic files, bounds, headers and independent Density. No import persistence. Deploy only in a separately approved deployment checkpoint. |
| **3D: Import functionality** | After storage prerequisite approval, implement pure atomic batch creation and the existing store's one-CAS commit service. Tests use memory doubles and, when separately authorized, disposable database tables. Verify collisions, stale forms, all preservation invariants and uncertain outcomes. No automatic storage upgrade or runtime-controller changes. |
| **3E: Administrator UI** | Add overview/custom-row exports and one Import colors flow: choose/paste, validate/review, explicit create commit, result. Recognize both supported source formats without mode selection and disclose ignored Backdrop legacy metadata. Extend strict admin operation allowlists and nonce/context conventions; keep forms separate from visibility/default/role forms. Add clear errors for unsupported native cases, Density-bearing bundles and absent/upgrade-required storage. No publishing/default/permission/Density controls in the transfer flow. |
| **3F: Deployment and Firefox acceptance** | Separately authorize a concrete deployment package, explicit schema-upgrade procedure if adopted, checkpoint/recovery assets and test fixtures. Preserve the configured active runtime bootstrap and SNORDIAN precedence. Validate first on disposable state, then perform only authorized live acceptance. Record Firefox evidence for transfers and regression checks; no release implied. |

The new domain prerequisite makes the original “just reuse current definitions” plan unsafe. Export can be delivered without resolving import storage, but 3D must not start with a default-Density filler or an unversioned schema relaxation.

### Rollback boundaries

This investigation requires no operational rollback; only this report is created. Future source work should be small, reviewable stages with no deployment until its explicit checkpoint.

Before a storage upgrade/new definition type is introduced, export-only code can be removed without data recovery. After a new aggregate/definition version is persisted, **restoring the old plugin is not a sufficient rollback**: current old readers reject newer or incomplete schemas. Phase 3B must specify dual-version read support, a compatible recovery build or an explicitly approved down-conversion/recovery process. Do not claim an old binary can read density-free definitions, or fabricate legacy Density values to make it do so. A pre-upgrade database checkpoint is recovery evidence, not authority to overwrite later concurrent changes.

If an import is acknowledged, its records are unpublished and can remain inert while UI/import admission is withdrawn. Any authorized cleanup must target only that batch's still-unchanged IDs through fresh revision-conditional domain plans, preserve tombstones and recheck current global-default protection. Do not replay the entire preimport snapshot over subsequent administrator work. Unknown outcomes require read-only authoritative inspection first; there is no automatic cleanup or retry.

Deployment must preserve the actual configuration-bearing installed bootstrap: copying the repository's default-disabled bootstrap over an enabled local runtime configuration would violate the baseline. Changes to runtime flags, plugin activation, Density preferences or SNORDIAN precedence are not rollback steps for color transfer. Protected components remain outside the implementation package.

## 11. Risks and decisions still requiring approval

| Decision/risk | Recommendation or required resolution |
| --- | --- |
| Colors-only scope | Settled by the user's clarification; no approval request to reintroduce Density |
| Transfer contract | Approve Option B's colors-only envelope; acknowledge that it is not native Backdrop configuration |
| Density-free persistence | Approve a versioned new definition type and aggregate compatibility/migration plan before import development; proposed schema 3 / aggregate 2 are candidates, not implemented facts |
| Runtime continuity during upgrade | Demonstrate legacy-readable/current-writable admission and controlled deployment ordering; no temporary invalidation of existing runtime palettes |
| Native Backdrop files | Recommend bounded single schema-1/2 custom input through the shared decoder and source adapter; validate/discard legacy Density and host metadata. Support is proposed, not implemented. Archives/settings/unknown or malformed native cases remain unsupported. |
| Collision UX | Whole-batch create-only rejection; no automatic suffixes, skips or overwrite modes |
| Limits/parser/review transport | Approve 1 MiB, 100 themes, depth 16, duplicate-member rejection and authenticated digest-bound review; qualify on PHP 7.4 and supported newer PHP versions |
| Legacy Core inheritance | Preserve as explicit null; do not convert to Daylight or flatten a resolved palette |
| Portability of exact pins | Both hosts share the current catalog, but unknown future versions must fail rather than reinterpret inheritance |
| Rollback after new storage | Approve a concrete compatible recovery strategy; an ordinary code revert is insufficient |
| Existing suite qualification | Administration/coexistence suites stopped under restricted file-readability checks; require normal-CLI reruns before implementation acceptance |
| Live state | Current activation and the 18 Firefox passes are supplied context, not newly queried or recertified here |

**Stop at Phase 3A.** The report recommends a colors-only format and a safe architecture; it authorizes no source change, migration, deployment or Phase 3B implementation. Wait for explicit authorization before starting Phase 3B.
