# Phase 1B domain and storage contracts

The pure entry point is `includes/theme-domain.php`. It requires no WordPress
functions or constants. It has no storage instance, translation side effects,
callbacks, stylesheet generation, profile controls or Density controller.

## Data and results

Domain methods use detached PHP arrays. Validation/resolution/change plans return
`data` and `errors`; errors are constant machine codes rather than submitted input
or translated HTML. Host interfaces must translate diagnostics and escape labels
for their output context. A failed validation never returns partially trusted data.
Inputs are not trimmed, case-folded, repaired, persisted or silently migrated.

`Preset_Catalog` exposes the append-only version `moodle-478fed30d2a4` and exactly
`daylight`, `lavender`, `mint`, `sunset`, `dark`. All 34 literal values per preset,
including expressions, case, presentation metadata and provenance are preserved.
The 33 editable property names and five labeled property groups match Backdrop.
`--h5p-theme-contrast-cta-dark` remains catalog-only, never a custom override.
There is no catalog mutation API; returned arrays are copy-on-write values.

Theme definition schemas match Backdrop's domain fields:

| Field | Contract |
| --- | --- |
| `schema_version` | Integer 1 or 2 |
| `id` | 1–64 ASCII characters, lowercase letter first; lowercase letters/digits/underscores |
| `label` | Nonblank UTF-8, at most 255 bytes, no control characters |
| `description` | Optional UTF-8, at most 4096 bytes; ordinary line breaks/tabs permitted |
| `density` | Required legacy metadata: `large`, `medium`, `small`; never a playback preference |
| `colors` | Sparse map of supported properties to exact `#RGB` or `#RRGGBB` strings |
| `base_preset` | Schema 2 only: exactly `id` and pinned `version` of a known builtin |

Unknown fields and malformed/scalar/nested values are rejected. Schema 1 has no
pinned base and retains a sparse Core cascade. Schema 2 overlays sparse hex colors
on its exact immutable base, retaining trusted preset expressions verbatim. Custom
input cannot supply expressions, dimensions, spacing or arbitrary CSS.

Stored schema-2 references are null (Core), `{kind: builtin, id, version}`, or
`{kind: custom, id}`. The validator also understands Backdrop's schema-1 custom-ID
string references when explicitly requested. A missing custom can have a valid
reference shape while failing resolution. `site_default` is a selection-policy
sentinel, never a valid stored global reference. Typed identities allow a custom
ID such as `mint` without changing or shadowing the builtin `mint`.

## Copies and inheritance

Builtin duplication creates a schema-2 custom with zero overrides and an exact
preset pin. Custom duplication copies schema, pin, sparse overrides, legacy
metadata and description into a new identity. It never records a live custom
parent. Subsequent source edits do not affect the duplicate. Destination collisions
are rejected, including malformed existing entries.

Add-from-default captures the valid global theme, preserving its sparse seed and
pin. Core/unset/missing/invalid defaults use pinned Daylight, matching Backdrop's
Add workflow. A new Add description is blank. This creation fallback is separate
from playback-style selection fallback, which uses Core after a broken global.

`with_overrides()` receives a full replacement override map. Blank strings clear
supported keys; null, invalid colors and unknown keys remain errors. Clearing an
Edit override restores the pin or Core cascade. For an Add form, the future UI
must merge valid submitted nonblank overrides onto the captured seed, as Backdrop
does, rather than clearing captured starting colors. No form is implemented here.

## Publication and selection

Publication is a separate schema-1 map, not a field of a theme definition:

```text
{ schema_version: 1, themes: { "custom:example": false, "builtin:dark": true } }
```

`site_default`, `core` and `builtin:daylight` are always available and cannot enter
the editable map. Optional choices require an explicit Boolean true and a valid,
resolvable catalog entry. A true stale/unknown token never creates a theme. Fixed
choices survive malformed/unavailable policy. The fresh seed publishes the other
four builtins, matching Backdrop's fresh initialization; custom creation always
adds/reset its token to false. New definitions are never automatically published.

Editing preserves publication; duplication does not copy it. Deletion leaves a
false tombstone. Recreating the same ID resets publication again. Pure atomic
change plans enforce this, and schema transition validation enforces it at the
future storage boundary too. Read-only catalogs never initialize/repair policy.

Global default eligibility depends on valid existence, independently of learner
publication. Site default can therefore deliver a hidden global theme. A hidden,
deleted or invalid personal selection follows the current global; an invalid
global falls back to Core with constant warning codes. Raw palette resolution
itself never invents a fallback. Explicit Core is distinct from Daylight.

Builtin edit/delete is rejected. A selected custom global cannot be deleted.
Deletion accepts stored plus any future effective global references and fails
closed if that state cannot be verified. Delete/default changes do not alter H5P
content or learner data; these methods only return an unpersisted next snapshot.

## Storage boundary

Reserved single-site option name: `pjh5pwp_theme_library`. No adapter reads or
writes it in Phase 1B. Phase 1C-A now supplies the separately authorized adapter;
see [persistence documentation](wordpress-persistence.md). The versioned aggregate is designed for native WordPress
option serialization, not as a promise of a JSON wire format:

```text
{
  schema_version: 1,
  revision: 0,
  settings: { schema_version: 2, default_theme: null },
  definitions: { /* custom ID -> schema-1/2 definition */ },
  publication: { schema_version: 1, themes: { /* optional token -> Boolean */ } }
}
```

The envelope, settings-reference, definition and publication versions are separate.
Settings/definitions/publication are committed together. Invalid global references,
unknown envelope fields, malformed definitions/policy and noninteger revisions
are rejected for persistence. Bad stored snapshots remain diagnosable, never
auto-repaired. No profile preference or native Density activation field is added.

`Snapshot_Store::read()` returns detached raw data or null for absence; backend
failure throws. `compare_and_swap(expected_revision, next)` must validate the
snapshot and `Schema::transition()` against the exact current revision inside an
atomic operation. Null expected revision initializes only an absent store with
revision 0; updates require current + 1. Failure returns false without any partial
write. Newly created/deleted IDs must carry explicit unpublished tombstones in the
next snapshot. Schema overflow is rejected. Only a test-owned in-memory store
implements this interface in Phase 1B; Phase 1C-A adds `WordPress_Snapshot_Store`.

The production adapter uses conditional SQL, not an unlocked `get_option()` followed
by `update_option()`. Backend atomicity is qualified separately. Authorization,
stale-form handling and live deployment still require their separately approved work.

## Intentional host differences

- WordPress-domain definitions reject Backdrop-only `_config_name`/`_config_static`
  fields. Any future importer must explicitly validate/map those host fields.
- Error codes replace translated Backdrop/Form API messages; property labels and
  preset metadata remain exact source data, to be translated by future UI adapters.
- Resolved palettes omit Backdrop's legacy `density` member entirely. Definition
  round trips/duplicates retain it; changing it cannot change resolved palettes.
- Missing publication policy hides optional choices instead of entering Backdrop's
  legacy “all existing themes visible” mode. Fresh initialized policy still matches
  Backdrop's five-builtin defaults. The new plugin has no legacy installation to migrate.
- Backdrop activation journals, schema witnesses, config overrides and import/Form
  API infrastructure are not copied. The aggregate schema and atomic transition
  contract capture domain safety; the later storage lifecycle is a separate host service.
- Deletion is conservatively blocked when any supplied global cannot resolve,
  even if a malformed global might refer to a different custom ID.

No live custom inheritance, Density profile resolution, CSS serialization/injection,
activity-level UI is introduced by these differences. Phase 1C-A persistence keeps
these domain rules intact and delegates normal transitions to this schema.

Phase 1C-B adds `Library_Changes::publish_many(state, visibility)` for the overview:
every entry goes through the existing `publish()` validation, then only the final
publication map is included in one revisioned snapshot. An invalid member rejects
the entire plan. No schemas, preset data, inheritance or Density rules change.
See [administration](administration.md) for the form boundary.
