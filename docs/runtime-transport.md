# WordPress runtime admission and appearance transport

Current release status (11 November 2026): Stages A, B and C are completed, deployed locally and manually accepted in Firefox, per maintainer confirmation. Runtime attachment is default-on when PJH5PWP_ENABLE_BROWSER_THEME is undefined or boolean true; every other value disables it. Compatibility/context admission and SNORDIAN suspension remain mandatory. Single-site activation ensures Profile storage; updates use read-only detection and protected retry. See [installation](installation.md). The slow-network readiness limitation remains unresolved. Older phase descriptions below are historical checkpoints.

Historical 8 November 2026 Phase 2B-2B transport checkpoint. Descriptor delivery
is implemented. Phases 2B-2C/2D add default-disabled browser Theme and native Density
source; see [the controller contract](theme-controller.md). Current local deployment/acceptance status is recorded below.

The Phase 2B-2B findings and future-consumer language below record that checkpoint.
Phases 2B-2C/2D implement those browser responsibilities in one separate static asset,
preserving this endpoint schema, authentication, cache policy and 30-second lifetime.
Phase 2B-2E qualifies those invariants with sequential identity/policy fixtures and
hardens browser lifecycle coalescing, expiry renewal and document rewrites. See
[the qualification report and deployment gate](phase-2b2e-verification.md).

At the historical Step 1 checkpoint, changes affected the browser controller only: ordinary
renewal starts at 25 seconds from request initiation, retaining the current
descriptor only until its independent 30-second hard deadline. Explicit refresh,
focus, visibility and pageshow retain conservative restoration. This server transport/schema is unchanged. See
[the current controller contract](theme-controller.md); the historical transport
checkpoint statements below do not supersede this source implementation.

At the Step 2 checkpoint, attachment/readiness changes introduced eligible frontend head loading,
a public readiness stylesheet, exact validated player markers and an inert footer
manifest. The authenticated endpoint, its version-1 descriptor and cache/admission
checks are unchanged. The initial 15-second readiness gate is not an extension of
a descriptor or a replacement for server authentication. At that Step 2 checkpoint no Step 3 return-tab gate was implemented. See theme-controller.md for the current delivery/fallback contract.
## Runtime Step 3 consumer — local acceptance complete

Return-tab/window and BFCache revalidation now use the existing readiness gate
and the same authenticated endpoint. No endpoint field, resolver, admission policy,
cache header or preference storage changes. Retained concealed DOM is not a fresh
authorization; the old 30-second hard deadline is not extended. New descriptors
alone authorize reveal. Current controller documentation supersedes historical
restore-first focus/visibility descriptions and the earlier Step 2-only status.
Steps 1–3 are deployed and complete for the agreed local Firefox scope. All seven final user checks passed; browser-history visual acceptance is not instrumental confirmation of BFCache activation. Compatibility with the subsequently selected WordPress visual theme remains pending. See [the Step 3 verification report](runtime-b3-step3-verification.md) for exact hashes, rollback backup and deferred hardening/release work.

## Composition and admission

`runtime-wordpress.php` loads the unchanged pure descriptor/serializer and four
adapter classes. Plugin shares its existing Runtime_Policy and Profile_Service.
Runtime_Admission delegates every compatibility decision to that policy and only
adds `unsupported_context` when its trusted context predicate is false. It does
not duplicate H5P, Core, multisite or SNORDIAN detection. Malformed decisions and
exceptions fail closed at the descriptor/registry boundary.

H5P must be active, structurally verified at the official plugin paths, and expose
Core API exactly 1.28. Existing multisite restrictions apply. All existing SNORDIAN
signals remain authoritative. Policy is rechecked on every valid endpoint request,
every observed player and every retrieval of attachment candidates. Suspension
skips effective preference resolution and returns no Theme/Density instructions.
`runtime_implemented` is true. `runtime_enabled` reports the shared configuration
decision, independently of compatibility. `future_runtime_permitted` and
`runtime_suspended` retain their compatibility meaning. `runtime_state` is
`suspended` when blockers exist, otherwise `available` or `disabled` according
to configuration. Available does not guarantee qualification of every player.

Administration/Profile functionality is independent of admission. No foreign
settings, callbacks, activation records, content or storage schemas are changed.

## Endpoint and authentication choice

POST to WordPress's `admin_url('admin-ajax.php')`, with a form-encoded body of
exactly two fields:

```text
action=pjh5pwp_appearance&request_id=0123456789abcdef0123456789abcdef
```

Both `wp_ajax_pjh5pwp_appearance` and `wp_ajax_nopriv_pjh5pwp_appearance` invoke the
same handler. Identity comes exclusively from `get_current_user_id()` after normal
WordPress authentication. The endpoint never sets the current user, validates a
client identity itself, issues credentials or changes authentication filters.
Every query parameter and unknown body field is rejected, including `user_id`,
Theme/Density values, CSS, JSONP callbacks and nonces. No preference-write route exists.

The current viewer is resolved on every valid request; no viewer/descriptor result
is memoized. Profile_Service::effective() remains the sole preference boundary:

| Viewer | Effective appearance |
| --- | --- |
| Anonymous or unauthorized authenticated viewer | Global Theme/Core fallback and Original Density |
| Target with `manage_options` | Automatic personal permission, independent Theme/Density |
| Other authorized registered role | Independent saved Theme/Density using existing role permission |
| Revoked/deleted role | Global Theme/Core fallback and Original; saved preferences retained |

Deleted/unavailable personal themes use the existing global/Core fallback without
overwriting the independent Density. Invalid palettes suspend the entire descriptor.

The transport uses established admin-ajax APIs available before the declared
WordPress 6.5 minimum. PHP syntax targets 7.4; verification includes 7.4.33, 8.3.18
and 8.4.22 against the installed WordPress 7.1.4 definitions. This does not claim
a full WordPress 6.5 installation test.

REST was evaluated and rejected for this narrow cached-page consumer. Installed
`rest_cookie_check_errors()` makes a cookie request anonymous without a `wp_rest`
nonce and rejects an invalid nonce. Embedding a session nonce in shared HTML would
break user switching/page-cache reuse; a separate nonce bootstrap adds an endpoint
without benefiting this read-only operation. The REST authentication checks remain
untouched. See [WordPress REST cookie authentication](https://developer.wordpress.org/rest-api/using-the-rest-api/authentication/)
and [anonymous admin-ajax dispatch](https://developer.wordpress.org/reference/hooks/wp_ajax_nopriv__requestaction/).

## Same-origin boundary and CORS

Required request headers:

```http
Origin: https://acceptance.example.test
Referer: https://acceptance.example.test/ordinary-frontend-page/
X-PJH5PWP-Request: appearance-v1
```

Origin must exactly match the configured frontend scheme, hostname and effective
port, and the admin URL must share that origin. No wildcard, subdomain, alternate
scheme or `null` Origin is accepted. Origin cannot contain a path or credentials.
No fallback is made for a missing Origin or Referer. Split-origin frontend/admin
deployments are unsupported. The header's constant value is public, not a nonce
or credential; browsers cannot send it cross-origin without a successful preflight.

When Fetch Metadata is present, require `Sec-Fetch-Site: same-origin`,
`Sec-Fetch-Dest: empty`, and mode `cors` or `same-origin`. Older browsers may omit
those headers; strict Origin, Referer and custom-header checks remain mandatory.
Referrer must be inside the configured home path and exclude wp-admin, wp-json,
external H5P embed URLs, WordPress embed pages, previews, customization/editor
queries and ambiguous encoded/traversal paths. Privacy settings that suppress these
headers fail closed. This check qualifies the transport caller; it cannot prove
the existence or ownership of a browser player.

WordPress `admin-ajax.php` calls `send_origin_headers()` before action dispatch,
and that function can exit on OPTIONS. Therefore an action-scoped `init` guard
rejects invalid requests (including routed preflight) before that call. A scoped
`allowed_http_origins` filter returns an empty list for this action. The handler
also removes all CORS grants before sending JSON, including previously emitted
wildcards/credential headers. Other AJAX actions retain their original CORS list.
An OPTIONS request without any action is handled by WordPress's generic endpoint;
it never resolves appearance or identity in this plugin. The actual appearance
POST must still satisfy all checks, regardless of any unrelated preflight result.

This nonce-free design is restricted to a side-effect-free read: same-origin
custom-header/Origin validation prevents browser cross-site reads and form/image
requests. Standard WordPress session validation and role authorization are retained.

## Stable version-1 response

JSON is the exact Phase 2B-2A descriptor inside a small transport envelope. Fixed
key insertion order and the existing canonical CSS serializer make equivalent
inputs serialize identically; request correlation is the only per-request field.

```json
{
  "version": 1,
  "request_id": "0123456789abcdef0123456789abcdef",
  "refresh": {"max_age_seconds": 30, "reuse": false},
  "appearance": {
    "admission": {"permitted": true, "reason": null},
    "theme": {"source": "personal", "kind": "core", "id": null, "version": null},
    "color_declarations": "",
    "density": {"selection": "original", "preserve_native": true}
  }
}
```

SNORDIAN suspension changes only the descriptor, preserving the envelope:

```json
{
  "admission": {"permitted": false, "reason": "snordian_active"},
  "theme": null,
  "color_declarations": "",
  "density": null
}
```

Complete reproducible envelopes for Core + Original, Lavender + Large, Mint +
Small, Site default + Original, deleted-theme fallback and SNORDIAN suspension
are in [runtime-transport-examples.json](runtime-transport-examples.json). They use
disposable profile fixtures with Lavender as the global default. Regenerate with:

```powershell
php -n tests/runtime/transport.php 'WORDPRESS_ROOT/wp-content/plugins/h5p' --examples
```

| HTTP status | Meaning |
| --- | --- |
| 200 | Descriptor computed, including policy/appearance suspension |
| 400 | Invalid exact input schema; reason `invalid_request` |
| 403 | Unsupported/cross-origin caller; reason `unsupported_context` |
| 405 | Unsupported method; reason `method_not_allowed`, `Allow: POST` |

Rejected requests use the same envelope with null `request_id` and a suspended
descriptor. Descriptor errors remain `snordian_active`, `runtime_policy`,
`admission_unavailable` or `appearance_unavailable`; exceptions are never exposed.
See [the unchanged descriptor fields and CSS provenance rules](runtime-appearance.md).
No user ID, username, email, roles, profile bytes, internal revisions, labels,
catalog or unvalidated CSS is returned. No partial Theme/Density instruction is
returned after suspension.

## Cache isolation and expiration

Every plugin endpoint response, including early errors, sends:

```http
Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0
CDN-Cache-Control: no-store
Surrogate-Control: no-store
Pragma: no-cache
Expires: Wed, 11 Jan 1984 05:00:00 GMT
Vary: Cookie, Origin
Cross-Origin-Resource-Policy: same-origin
X-Content-Type-Options: nosniff
Content-Type: application/json; charset=UTF-8
```

ETag/Last-Modified are removed. No conditional 304 response or persistent
descriptor cache exists. Personal preferences and session nonces are never placed
in public HTML, H5PIntegration, dependency lists/cache entries, stylesheet URLs,
generated files or static assets. Shared HTML can be reused between anonymous and
authenticated viewers because the subsequent response reads the current session.
User switching, logout and role/global changes are resolved on the next request.
The cache headers instruct standards-compliant browsers/proxies/CDNs; no source
implementation can override a proxy deliberately configured to ignore no-store.

`max_age_seconds` is the maximum future consumer's in-memory admission lifetime,
not HTTP cache permission. A future controller must generate a fresh 128-bit hex
`request_id` per request, match the response ID to the outstanding request and
current page/authentication generation, and measure age from request initiation
using a monotonic clock. Discard out-of-order responses and responses already 30
seconds old; never retain them in page, local/session storage, service-worker or
shared caches. Use `credentials: same-origin`, `cache: no-store` and the required
headers. Clear admission on explicit refresh, failed/suspended/expired delivery,
navigation and known login/logout/account transitions. Ordinary scheduled renewal
may retain the current appearance only within its original 30-second grant;
neither starting a request nor receiving an invalid/failed response extends it.
A new valid descriptor has its own request-start deadline. Revalidate before applying
after visibility restoration. Abort/invalidate outstanding requests on generation
changes. A fresh response has no enduring authority over a future session change.

Those browser responsibilities are **documented, not implemented here**. Instant
remote logout/SNORDIAN/role-change notification is not promised. Future polling
must bound undetected changes by this lifetime and fail closed. No styling exists
in this phase that could remain stale.

## Playback qualification and dormant attachment

Reverified installed H5P 1.17.10 source:

| Extension point | Actual contract and context |
| --- | --- |
| `h5p_alter_library_scripts` | Action with `&$scripts`, dependency-library map, embed string; emitted by `alter_assets(&$files, &$dependencies, $embed)` |
| `h5p_alter_library_styles` | Same contract for `&$styles`; no content ID |
| `print_h5p_content` | Filter with wrapper string and complete main content, at the end of `add_assets()` |

The asset hooks run after dependency lookup, before the content filter, and may
not run again for repeated IDs whose settings are already present. Editor storage
uses `editor`; external embed handler uses `external`. Administration content
preview also calls ordinary `add_assets()`, making embed type alone insufficient.
Relevant source: `public/class-h5p-plugin.php:1104-1210`,
`admin/class-h5p-content-admin.php:251`, `admin/class-h5p-plugin-admin.php:170`,
`admin/class-h5p-editor-wordpress-storage.php:181`, and Core `js/h5p.js:381-437`.
These are source findings, not live activity loads.

Playback_Registry observes `print_h5p_content` at maximum priority with two
arguments. It requires a completed ordinary frontend query, GET, no admin/AJAX,
REST/cron/CLI, preview/customizer/feed/WordPress embed, or framed parent document.
It validates canonical positive content/library IDs, main machine name and version,
known embed metadata, uses the real Core determineEmbedType method, and requires
the exact ordinary H5P-generated wrapper. DIV and internal `about:blank` iframe
wrappers qualify. Altered/custom/combined/foreign wrappers fail closed without
changing a byte. Repeated IDs revoke all candidates for that ID for the rest of
the request; distinct activities remain independent. Library dependencies are
never treated as main identity. No content is reloaded, logged or saved.

`Plugin::instance()->playback_candidates()` is the **dormant attachment seam**.
It returns only sorted public content ID/embed/main-library candidates and
rechecks context/policy. No candidates are currently emitted into HTML/settings,
and no frontend script/style is registered or enqueued. This deliberately leaves
the cached-page output format for the browser-controller phase. Candidate state
is request-local, not a transport credential or persisted content registry.

Future Phase 2B-2C should attach a single static, non-personal controller to the
parent frontend document after qualifying content is known; never put it in the
H5P dependency bundle. `print_h5p_content` is too late to select assets through
the earlier asset hooks. A footer attachment must wait until all content has been
observed so later duplicates can revoke an earlier candidate. Any public manifest
must contain only the candidate information and same-origin endpoint location.

The browser must independently verify final DOM/main-library ownership and reject
duplicate/mismatched/nested/foreign players. A PHP candidate or endpoint descriptor
alone cannot authorize a DOM target. Internal iframe documents are rewritten by
Core after initialization; they inherit parent H5PIntegration and set
`H5P.externalEmbed = false`. The future parent controller must handle that isolation
and timing. External H5P embeds, cross-origin players and all editors/previews remain
excluded. No wrapper mutation, Theme/Density CSS, player classes, resize handling,
DOM mutation, lifecycle controller or executable placeholder is included here.

Proceed to Phase 2B-2C only after explicit authorization. Native Density browser
application remains a separate deferred concern, including Original-state capture.


> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.
