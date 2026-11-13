# WordPress administration — Phase 1C-B

Current status, 11 November 2026: the implemented feature set and Stages A/B/C have been deployed locally and manually accepted in Firefox (maintainer confirmation). See [README](../README.md) and [installation](installation.md) for current behavior. Phase-specific statements below retain their checkpoint context. In particular, runtime is now default-on and compatibility-gated, activation installs Profile storage, and active SNORDIAN suppresses own-Profile selectors/preview as well as Papi Jo playback; the older isolated-preview behavior is superseded. The slow-network readiness limitation remains unresolved.

Implemented against Backdrop H5P Themer v0.5.0, commit
`d9b53d706a94dac322b89885bf668145ab3803c4`. The authoritative source, original
repository, deployed Backdrop, installed H5P and SNORDIAN remain read-only.
No SNORDIAN implementation was used as a porting source.

## Verified integration and replacement architecture

The installed official H5P plugin's `H5P_Plugin::$plugin_slug` is `h5p`.
Its admin class registers `admin_menu` at default priority 10 and calls
`add_menu_page(..., 'view_h5p_contents', $this->plugin_slug, ...)` in
`admin/class-h5p-plugin-admin.php`. Our priority-30 menu callback observes that
actual parent and the current administrator's access. It registers the unique
`pjh5pwp-themes` submenu using `manage_options`. Missing/inaccessible H5P parent
uses `add_options_page` under Settings. Multisite exposes no management page.

WordPress returns the page hook suffix; only its `load-$hook` handles POST and
only its `admin_enqueue_scripts` hook suffix admits assets. This uses the
documented [submenu/page lifecycle](https://developer.wordpress.org/reference/functions/add_submenu_page/).
The actual WordPress `WP_Scripts::localize()` implementation emits a `var`
declaration, so our hyphenated JS configuration uses `wp_add_inline_script` and
`window['pjh5pwp-admin']`, with JSON hex escaping, before the behavior script.

| Component | Responsibility |
| --- | --- |
| `Admin_Controller` | WordPress menu, page-load lifecycle, one unslash, private headers, POST/303/GET, exact page-only assets |
| `Admin_Service` | Capability/single-site admission, strict action shapes, nonce verification, snapshot binding, domain plans and atomic commit |
| `Admin_View` | Escaped HTML, native forms/details, catalog, read-only palettes, recovery text and safe submitted values |
| `Admin_Messages` | Translated plain diagnostics without SQL, credentials or arbitrary exception details |
| `Theme_Preview` | Validated saved palette, supported H5P admission, fixed local assets, sandboxed srcdoc samples |
| `assets/js/admin.js` | Unnamed native picker companions, validation status, frame-only live colors and reset |
| `Library_Changes::publish_many()` | Compose existing publication validation into a single domain change plan/revision |

Constructors/loaders are inert. The controller uses a service factory so ordinary
bootstrap never opens storage. GET and notices never write. If storage is absent,
an explicit Initialize POST creates exactly `Schema::initial()` through the same
adapter. Existing malformed, unknown/newer schemas and unsupported backends are
blocked without overwriting or resetting anything. There is no migration form or
guessed historical upgrade in this checkpoint. The aggregate schema remains 1.

Phase 1C-D refines presentation only: theme creation, duplication and editing keep
the existing POST/303/Edit flow and show **Theme saved successfully.** The editor
has a WordPress `button`-styled **Back to theme overview** anchor immediately after
**Save theme**, replacing its former top navigation row. The anchor never submits
the form; following it discards any unsaved changes. Other routes retain their top
navigation. The overview continues to mark the stored global default as Selected
and omits Delete for that custom theme. Selecting another default restores Delete.
The server still rejects direct or stale attempts to delete the current default.

## Backdrop feature-parity matrix

These mappings come from source inspection, not screenshots of a live CMS.
`h5p_themer.admin.inc` and `h5p_themer.preview.inc` are the main source references.

| Backdrop v0.5.0 feature / function | WordPress result | Qualification |
| --- | --- | --- |
| Overview, `h5p_themer_overview()` | Built-in/Core and custom tables, stable IDs, stored-default indicator, operations and publication checkboxes | WordPress table styling; global form on the same page |
| Immutable preset view, `h5p_themer_preset_view()` | Daylight, Lavender, Mint, Sunset, Dark; complete 34-entry literal read-only palettes; View/Duplicate only | Domain protects against forged edit/delete POST too |
| Core defaults | Explicit selectable default and original Core comparison | Distinct from Daylight; not duplicable |
| Add seed, `_h5p_themer_admin_add_snapshot()` | Captured global definition/palette; Core starts from pinned Daylight | Full snapshot/preset nonce binding replaces Backdrop cached form context |
| Theme form, `h5p_themer_theme_form()` | ID/name/description, pinned base, sparse overrides | ID immutable on Edit; Name can change without breaking references |
| Five groups, `_h5p_themer_admin_color_groups()` | Exact 33-property allowlist and group order from Phase 1B | General open; five color groups collapsed, invalid groups open |
| Color groups | Main controls; Secondary controls; Background and surfaces; Text and borders; Feedback colors | Labels/order retained; no theme Density field |
| Native companion inputs | Named hex text plus unnamed native `type=color` | Shorthand/case preserved in text; swatch expands shorthand only |
| Blank text | Edit/custom Copy clears override and restores inherited value | On Add, blank retains captured starting overrides, matching Backdrop |
| Expression inheritance | Read-only expression shown; no manufactured override | Native picker disabled until an explicit hex exists when no hex base is known |
| Built-in Duplicate | Pin exact ID/version, zero overrides, unpublished copy | Read-only colors until first save; then redirect to Edit |
| Custom Duplicate | Copy sparse overrides, pin, description and legacy metadata independently | No live custom-to-custom parent |
| Visibility submit | One explicit overview Save learner visibility | Fixed Site default/Core/Daylight always available; new custom unpublished |
| `h5p_themer_default_form()` | Any valid built-in/Core/custom default, independent of visibility | Activity/session learner and native Density activation checkboxes deliberately excluded |
| Delete form / `_h5p_themer_admin_delete_errors()` | GET confirmation page, explicit checked POST, global-default protection | Tombstone unpublished atomically with deletion; no silent retargeting |
| Original/stored vs effective configuration | Stored aggregate default and stored editable definition | Backdrop settings.php/config override infrastructure is not imported |
| Config journals/cache snapshots | Existing versioned WordPress aggregate and byte/row CAS | No Backdrop config, output caches or runtime invalidation copied |
| Saved preview, `h5p_themer_preview()` | Built-in/Core View and custom Edit sample frames | Supported official H5P/Core 1.28 assets required; no player bootstrap |
| Live preview | Custom Edit only, saved/unsaved status and Reset | Add/Duplicate have no saved preview; no Density preview |
| Core comparison | Separate collapsed original-Core frame | Same representative markup and Core spacing as themed frame |
| Access gate | `manage_options` and single-site for every page/write | Missing H5P or active SNORDIAN does not block palette management |
| Form errors/context expiry | Escaped fields retained, stale saves rejected/disabled | Deleted source/backend failure offers bounded read-only copyable text |

Removed theme-level Density controls are not resurrected. No activity-level
selectors, profile permissions or preference controls are ported in this phase.
Runtime-oriented Backdrop help text is adjusted to state this plugin's actual
administration-only implementation, rather than suggesting saved themes apply
to playback already.

## Validation, publication and concurrency

Every write rechecks `current_user_can('manage_options')` and rejects multisite
before reading storage. Nonces provide CSRF protection, not authorization;
WordPress explicitly [requires a separate capability check](https://developer.wordpress.org/apis/security/nonces/).
Our nonce action binds operation, source token, revision string and a SHA-256
digest of the entire captured snapshot plus pinned catalog. This digest is a
context witness, not a secret; WordPress authenticates the binding via its nonce.
Changing a submitted revision alone cannot relabel stale values as current.

Accepted POST keys are explicit per action. Tokens are typed fixed references,
never paths, URLs, selectors, arbitrary preset versions or user IDs. Create/Edit/
custom Copy require all 33 supported color text fields (including blanks), so a
truncated form cannot silently clear omitted overrides. Unknown/array/null fields,
CSS expressions in editable colors, unrecognized flags, unexpected Density/base
fields and malformed references fail. The domain remains the authority for IDs,
hex values, UTF-8/byte bounds, pins, publication, deletion and transition invariants.
No sanitation silently trims or transforms a palette.

`Admin_Service` uses `Theme_Operations`, `Library_Changes` and
`Publication_Policy`; handlers do not write definitions/options directly.
Publication uses one revision even when many checkboxes change. New custom IDs
always have explicit false publication; defaults do not auto-publish. IDs selected
as global defaults cannot be deleted. Missing/deleted references fail explicitly;
the Phase 1B fallback policy remains unchanged for future consumers.

Storage rereads/checks the expected revision and applies the existing qualified
exact-byte/row-identity conditional write. A conflict, backend error or uncertain
acknowledgement is never auto-retried. See [persistence guarantees and limits](wordpress-persistence.md).
Known no-write failures may be manually retried against the same context; stale,
invalid nonce, malformed, unknown-outcome or committed/cache-failure forms require
reload/review. No blind rollback or compensating write is introduced.

Text/attribute/URL output uses context-appropriate escaping. The bounded safe
submitted values survive validation errors; invalid color text is displayed as
text and never inserted into preview CSS. Error redisplay retains the original
nonce/context, not a newly rebased submission. A deleted source cannot be silently
recreated from an old Edit; safe text is offered for copying instead.

## Preview isolation

The renderer resolves only validated saved themes and literal pinned preset
expressions. It serializes only the 33 supported color properties, consistent
with Backdrop's serializer; the catalog's unsupported 34th literal property is
displayed read-only but is not an editable/runtime preview override.

Production asset reads are fixed to:

- `WP_PLUGIN_DIR/h5p/h5p-php-library/styles/h5p-theme-variables.css`
- Plugin `assets/css/preview.css`, adapted from the Backdrop representative sample.

Missing, empty, invalid UTF-8, NUL or `</style` CSS fails closed. Complete srcdoc
is attribute-escaped. The frame has only `sandbox="allow-same-origin"` so the
trusted parent can access its style node; it has no script permission. CSP is
`default-src 'none'; style-src 'unsafe-inline'; form-action 'none'; base-uri 'none'`.
There are no scripts, forms, remote assets or H5P player instances inside it.

Samples cover heading/body/secondary/muted text, selected answer, primary and
secondary button states, correct/incorrect feedback, progress and three borders.
The native `h5p-content` class appears only inside srcdoc to obtain original Core
sample spacing. No `h5p-small`, `h5p-medium` or `h5p-large` class is added. Legacy
theme Density is neither serialized nor interpreted by the preview.

Live JS reads trusted allowlisted metadata and named hex inputs, writes only the
fixed style element inside that frame, retains the last valid color per field
while another is invalid, and clears to the inherited preset or original Core
value. Reset waits for the native form reset, resynchronizes swatches and restores
the initial fields/preview. No endpoint, local/session storage or save call exists.
Without JS, text editing/server validation and saved previews remain usable;
picker companions remain hidden. Actual H5P libraries may look different.

## SNORDIAN and scope

Page slug, script/style handle, classes, data attributes and JS configuration use
our unique prefixes. No SNORDIAN options, callbacks or admin routes are storage
dependencies. Active SNORDIAN still suspends future runtime colors and Density;
the administration layer does not consume that suspension as a reason to disable
independent theme management. Preview CSS lives only in our sample frames.

The unchanged compatibility regression tests plus four isolated marker/activation/
callback coexistence workers verify management remains functional with suspension
active, without changing foreign options/callbacks. They are not a live two-plugin
certification. Real WordPress menus, sessions, plugins and browser accessibility
acceptance remain the next separately approved checkpoint.
