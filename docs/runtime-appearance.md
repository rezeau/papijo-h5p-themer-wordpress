# Runtime appearance descriptor — Phase 2B-2A

Current status, 11 November 2026: the implemented feature set and Stages A/B/C have been deployed locally and manually accepted in Firefox (maintainer confirmation). See [README](../README.md) and [installation](installation.md) for current behavior. Phase-specific statements below retain their checkpoint context. In particular, runtime is now default-on and compatibility-gated, activation installs Profile storage, and active SNORDIAN suppresses own-Profile selectors/preview as well as Papi Jo playback; the older isolated-preview behavior is superseded. The slow-network readiness limitation remains unresolved.

8 November 2026. Internal PHP contract only. These services do not apply styling,
register hooks, serve HTTP, create assets or change stored preferences.

## Composition and trust boundary

`includes/runtime.php` loads the domain and two inert runtime class definitions.
Phase 2B-2B now loads it through `runtime-wordpress.php` in the main bootstrap.
The pure loader itself still registers no hooks. The WordPress composition reuses
these services; see [the transport contract](runtime-transport.md). Direct composition:

```php
require_once $plugin_directory . '/includes/runtime.php';

$appearance = new \Papijo\H5PThemerWordPress\Runtime\Appearance_Service(
    array( $runtime_policy, 'evaluate' ), // Existing Runtime_Policy instance.
    array( $profiles, 'effective' )      // Existing Profile_Service instance.
);
$descriptor = $appearance->describe( $server_authenticated_viewer_id );
```

The ID must be an integer obtained by a future adapter from server authentication;
zero denotes anonymous. It is an internal input, never a descriptor field. The
constructor callbacks are trusted dependency-injection seams, not request data.
Neither new class calls WordPress APIs. Storage and authorization reads belong
to the injected existing services. There are no writes in this composition.

Each `describe()` call first invokes the shared admission dependency. It requires
`future_runtime_permitted === true`, `runtime_suspended === false` and an empty
blocker array. A suspended/malformed decision skips the effective-profile callback.
No decision or descriptor is cached. Admission permits computation only; it does
not mean playback integration is implemented or enabled. The existing policy's
`runtime_implemented` and `runtime_enabled` fields remain false and unchanged.

`Profile_Service::effective()` remains the only preference boundary. It uses the
existing Role_Service, Publication_Policy::resolve_learner(), theme catalog and
Palette_Resolver. The descriptor does not independently evaluate roles, publication,
saved tokens or inheritance.

## Exact fields

| Field | Contract |
| --- | --- |
| `admission` | Exactly `permitted` (boolean) and `reason` (null or the constant below). |
| `theme` | Null when suspended; otherwise exactly `source`, `kind`, `id`, `version`. |
| `theme.source` | `personal`, `global`, or `core`, preserved from publication resolution. Explicit personal Core has source `personal`; a valid global Core has source `global`. |
| `theme.kind` | Effective `core`, `builtin` or `custom`. |
| `theme.id` | Null for Core; otherwise the validated effective theme identifier. This identifies a theme, never a user. |
| `theme.version` | Pinned catalog version for a built-in; null for Core/custom. Custom inheritance provenance is verified before projection, and is not exposed. |
| `color_declarations` | Deterministic string of approved `property:value;` pairs. Empty for Core and suspension. No selector, braces, HTML, Density, labels or arbitrary metadata. |
| `density` | Null when suspended; otherwise exactly `selection` and `preserve_native`. |
| `density.selection` | `original`, `small`, `medium`, `large`. |
| `density.preserve_native` | True exactly for Original. Preserve the actual initialized native state; do not infer a Small/Medium/Large default or remove native classes. |

Suspension reasons are `snordian_active` (shared policy blocker), `runtime_policy`
(other blockers or malformed/inconsistent admission), `admission_unavailable`
(admission dependency threw), and `appearance_unavailable` (invalid internal viewer
input, failed effective dependency, invalid resolution or rejected palette).
All suspended descriptors have null Theme/Density and empty declarations.
Exceptions and arbitrary blocker text are not returned. Serializer failure suspends
both appearance dimensions; it never returns partial CSS or invents fallback rules.

No user ID, role, profile record, storage revision, complete catalog, label or
user-supplied CSS is included. This array is not an HTTP response schema. It has
no nonce, revision, expiry, cache header, transport version or player selector.

## Effective selection semantics

| Viewer/selection | Theme | Density |
| --- | --- | --- |
| Anonymous or authenticated unauthorized | Current global default, then Core | Original |
| Authorized (including automatic Administrator access) | Available saved choice | Independently saved choice |
| Use site default | Current global, resolved afresh | Independently saved choice |
| Explicit H5P Core | Core; no Papi Jo colors | Independently saved choice |
| Invalid/deleted/unpublished personal custom | Existing global/Core fallback | Independently saved choice |
| Invalid/unavailable global | Core fallback | Independently saved choice |
| Shared admission suspended | No Theme instruction | No Density instruction |

All five built-ins remain eligible for authorized personal selection, even when
their stored publication flags are false. A valid custom global default remains
globally eligible without being published for personal selection. Legacy theme
Density metadata never enters the independent Density result.

Existing storage failures and schema validation retain their current semantics:
Profile_Service uses safe preference defaults after a failed profile read and treats
an invalid library snapshot as unavailable. This layer does not salvage invalid
records or repair storage. The real Profile_Store rejects malformed aggregates;
tests also inject invalid raw Density values to cover Profile_Service's defensive
Original fallback. Valid saved preferences are never rewritten during resolution.

## CSS validation and provenance

`CSS_Serializer::serialize($resolved_palette)` returns exactly
`array('data' => $declarations, 'errors' => array())`, or
`array('data' => null, 'errors' => array('invalid_runtime_colors'))`.
It accepts resolved palette data, not raw CSS or a selector.

1. Require a known kind and an array of colors. Core must have an empty color map.
2. Load built-ins by exact ID/version from Preset_Catalog. Require the entire
   built-in color map to match the immutable preset, independent of map ordering.
3. Require a valid custom ID. Validate any custom `base_preset` with Theme_Validator
   and load that exact pinned preset. Standalone custom palettes remain hex-only.
4. Use only Color_Properties::supported() (33 approved Core API 1.28 properties).
   Custom hexadecimal values use the existing anchored three/six-digit validator;
   case and bytes are retained. No general CSS-expression parser is introduced.
5. A non-hex value is trusted only when it exactly matches the local pinned
   preset's value for that same property. Merely supplying `provenance`, a valid
   base name, or a catalog expression on another property never authorizes it.
6. The unsupported catalog-only `--h5p-theme-contrast-cta-dark` is never emitted.
   Its exact inherited/preset value can be recognized and omitted. Unpinned,
   modified or arbitrary unsupported properties reject the whole palette.
   The caller's `unsupported_properties` metadata cannot expand this exception.
7. Iterate the canonical supported-property list, emitting `property:value;`
   without spaces between declarations. Output is independent of input-map order.

This follows the conceptual validator in the authoritative Backdrop v0.5.0
`h5p_themer.runtime.inc`, reference commit
`d9b53d706a94dac322b89885bf668145ab3803c4`. Its fixed-selector wrapper and public
asset publisher are intentionally outside this service. The existing pinned
`moodle-478fed30d2a4` catalog supplies literal values and upstream provenance;
neither its five definitions nor any Backdrop source was changed.

## Examples

The five complete descriptors below are generated by the real runtime service
through the isolated Profile/Role/domain fixtures. The global default for these
examples is Lavender. JSON is used here only to display PHP array values, not to
specify transport. Regenerate on stdout with:

```powershell
php -n tests/runtime/run.php --examples
```

```json
{
    "Core + Original": {
        "admission": {
            "permitted": true,
            "reason": null
        },
        "theme": {
            "source": "personal",
            "kind": "core",
            "id": null,
            "version": null
        },
        "color_declarations": "",
        "density": {
            "selection": "original",
            "preserve_native": true
        }
    },
    "Lavender + Large": {
        "admission": {
            "permitted": true,
            "reason": null
        },
        "theme": {
            "source": "personal",
            "kind": "builtin",
            "id": "lavender",
            "version": "moodle-478fed30d2a4"
        },
        "color_declarations": "--h5p-theme-main-cta-base:#834DD5;--h5p-theme-secondary-cta-base:#000000;--h5p-theme-alternative-base:#f3edff;--h5p-theme-background:#F3EEFA;--h5p-theme-focus:#F1EBFA;--h5p-theme-main-cta-light:#8347DD;--h5p-theme-main-cta-dark:#692EC2;--h5p-theme-contrast-cta:#ffffff;--h5p-theme-contrast-cta-white:#834DD5;--h5p-theme-contrast-cta-light:color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%);--h5p-theme-secondary-cta-light:#333333;--h5p-theme-secondary-cta-dark:#311B50;--h5p-theme-secondary-contrast-cta:#F3F1F5;--h5p-theme-secondary-contrast-cta-hover:#ffffff;--h5p-theme-alternative-light:#f8f5ff;--h5p-theme-alternative-dark:#e8d9ff;--h5p-theme-alternative-darker:#ddc7ff;--h5p-theme-text-primary:#101729;--h5p-theme-text-secondary:#1b1346;--h5p-theme-text-third:#737373;--h5p-theme-ui-base:#FFFFFF;--h5p-theme-stroke-1:#ead8f6;--h5p-theme-stroke-2:#E7EDF6;--h5p-theme-stroke-3:#F6F7FA;--h5p-theme-feedback-correct-main:#256D1D;--h5p-theme-feedback-correct-secondary:#f3fcf0;--h5p-theme-feedback-correct-third:#cff1c2;--h5p-theme-feedback-incorrect-main:#a13236;--h5p-theme-feedback-incorrect-secondary:#faf0f4;--h5p-theme-feedback-incorrect-third:#f6dce7;--h5p-theme-feedback-neutral-main:#E6C81D;--h5p-theme-feedback-neutral-secondary:#5E4817;--h5p-theme-feedback-neutral-third:#F0EBCB;",
        "density": {
            "selection": "large",
            "preserve_native": false
        }
    },
    "Mint + Small": {
        "admission": {
            "permitted": true,
            "reason": null
        },
        "theme": {
            "source": "personal",
            "kind": "builtin",
            "id": "mint",
            "version": "moodle-478fed30d2a4"
        },
        "color_declarations": "--h5p-theme-main-cta-base:#187571;--h5p-theme-secondary-cta-base:#000000;--h5p-theme-alternative-base:#FAF4E6;--h5p-theme-background:#FCF9F2;--h5p-theme-focus:#F1EBFA;--h5p-theme-main-cta-light:#1C7E79;--h5p-theme-main-cta-dark:#004D45;--h5p-theme-contrast-cta:#ffffff;--h5p-theme-contrast-cta-white:#17948b;--h5p-theme-contrast-cta-light:color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%);--h5p-theme-secondary-cta-light:#333333;--h5p-theme-secondary-cta-dark:#311B50;--h5p-theme-secondary-contrast-cta:#F3F1F5;--h5p-theme-secondary-contrast-cta-hover:#ffffff;--h5p-theme-alternative-light:#FCF9F2;--h5p-theme-alternative-dark:#F2E3BF;--h5p-theme-alternative-darker:#EEDAAA;--h5p-theme-text-primary:#101729;--h5p-theme-text-secondary:#05252e;--h5p-theme-text-third:#737373;--h5p-theme-ui-base:#FFFFFF;--h5p-theme-stroke-1:#cceae8;--h5p-theme-stroke-2:#E7EDF6;--h5p-theme-stroke-3:#F6F7FA;--h5p-theme-feedback-correct-main:#256D1D;--h5p-theme-feedback-correct-secondary:#f3fcf0;--h5p-theme-feedback-correct-third:#cff1c2;--h5p-theme-feedback-incorrect-main:#a13236;--h5p-theme-feedback-incorrect-secondary:#faf0f4;--h5p-theme-feedback-incorrect-third:#f6dce7;--h5p-theme-feedback-neutral-main:#E6C81D;--h5p-theme-feedback-neutral-secondary:#5E4817;--h5p-theme-feedback-neutral-third:#F0EBCB;",
        "density": {
            "selection": "small",
            "preserve_native": false
        }
    },
    "Site default + Original": {
        "admission": {
            "permitted": true,
            "reason": null
        },
        "theme": {
            "source": "global",
            "kind": "builtin",
            "id": "lavender",
            "version": "moodle-478fed30d2a4"
        },
        "color_declarations": "--h5p-theme-main-cta-base:#834DD5;--h5p-theme-secondary-cta-base:#000000;--h5p-theme-alternative-base:#f3edff;--h5p-theme-background:#F3EEFA;--h5p-theme-focus:#F1EBFA;--h5p-theme-main-cta-light:#8347DD;--h5p-theme-main-cta-dark:#692EC2;--h5p-theme-contrast-cta:#ffffff;--h5p-theme-contrast-cta-white:#834DD5;--h5p-theme-contrast-cta-light:color-mix(in srgb, var(--h5p-theme-main-cta-base), transparent 90%);--h5p-theme-secondary-cta-light:#333333;--h5p-theme-secondary-cta-dark:#311B50;--h5p-theme-secondary-contrast-cta:#F3F1F5;--h5p-theme-secondary-contrast-cta-hover:#ffffff;--h5p-theme-alternative-light:#f8f5ff;--h5p-theme-alternative-dark:#e8d9ff;--h5p-theme-alternative-darker:#ddc7ff;--h5p-theme-text-primary:#101729;--h5p-theme-text-secondary:#1b1346;--h5p-theme-text-third:#737373;--h5p-theme-ui-base:#FFFFFF;--h5p-theme-stroke-1:#ead8f6;--h5p-theme-stroke-2:#E7EDF6;--h5p-theme-stroke-3:#F6F7FA;--h5p-theme-feedback-correct-main:#256D1D;--h5p-theme-feedback-correct-secondary:#f3fcf0;--h5p-theme-feedback-correct-third:#cff1c2;--h5p-theme-feedback-incorrect-main:#a13236;--h5p-theme-feedback-incorrect-secondary:#faf0f4;--h5p-theme-feedback-incorrect-third:#f6dce7;--h5p-theme-feedback-neutral-main:#E6C81D;--h5p-theme-feedback-neutral-secondary:#5E4817;--h5p-theme-feedback-neutral-third:#F0EBCB;",
        "density": {
            "selection": "original",
            "preserve_native": true
        }
    },
    "Suspended due to SNORDIAN": {
        "admission": {
            "permitted": false,
            "reason": "snordian_active"
        },
        "theme": null,
        "color_declarations": "",
        "density": null
    }
}
```
