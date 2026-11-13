# Stage G2 — official H5P administration-viewer candidate

Local development version: **0.2.0-beta.1**. Published stable **0.1.1** and its
historical artifacts remain unchanged. No deployment, Git operation or publication
is authorized. The two accepted viewing-context limitations remain **open pending
Firefox acceptance**; automated fixtures are not manual acceptance.

## Context and implementation

Only the canonical official screen and route
`wp-admin/admin.php?page=h5p&task=show&id=<ID>` are eligible. Page attachment requires
the actual `toplevel_page_h5p` screen, matching WordPress page globals and canonical
query/URI, GET/top-document context, authentication, official H5P class provenance
and Core API exactly 1.28. The shared runtime policy still checks the supported
official integration, multisite exclusions and SNORDIAN before player admission.

Viewing access mirrors official H5P: `view_h5p_contents`, plus
`view_others_h5p_contents` or exact content ownership. A prepared, read-only query
fetches only the activity ID and owner from the official H5P content table. No
H5P service is constructed and no content filtering, view logging, schema change
or preference write is introduced by this adapter.

The existing registry admits only authorized players. Scoped `admin_enqueue_scripts`
and `admin_footer` hooks attach the same readiness CSS, controller and player
manifest used on ordinary pages. A session/user/route-bound WordPress nonce appears
in matching head attachment and footer metadata. Both JavaScript entry guards
require that proof and the exact canonical viewing URL. Repeated IDs are rejected;
distinct authorized players share the descriptor while keeping existing root and
iframe ownership checks.

The original frontend manifest and two-field POST schema remain unchanged.
The admin branch adds exactly `admin_content_id`, `admin_nonce` and `admin_players`.
The endpoint retains the same origin/Fetch Metadata/custom-header/method/no-store
checks; it validates the session-bound nonce and freshly rechecks viewing capability
and ownership for the route and every player. Referrer/URL/DOM markers alone do not
authorize a descriptor. A valid nonce never substitutes for current content access.

Existing Profile/Role/Publication/Appearance services still resolve personal
selection, global/Core fallback and independent Density. Users losing personal
selection fall back without changing saved preferences. Losing H5P viewing access
denies the admin request. SNORDIAN suspends both dimensions through the same fresh
policy. Existing CSS variable application, native Density classes, resize, internal
iframe handling, readiness, descriptor expiry and cleanup are reused.

## Excluded and pending contexts

Profile forms, H5P content lists, editors, results, unrelated administration screens,
external `h5p_embed`, cross-origin/sandbox/srcdoc frames and ambiguous players remain
excluded. Profile's representative preview is unchanged. The Subscriber label is
not treated as a separate renderer: a permitted Subscriber using the official
`task=show` route is a test target; any different route remains unsupported.

The initial 15-second readiness timeout remains unchanged and can leave native
fallback without automatic personal styling on slow networks. Broad library,
browser/hosting and minimum-WordPress combinations are not newly certified.
Native H5P theme variables and the library's theme-aware markup must exist; this
change does not force legacy libraries into a new styling system.

The original F3 preservation check did not pass. The unchanged F6 record retains
**DOCUMENTED RISK ACCEPTANCE**, not proof of preservation or an attribution of
cache-timeout/AUTO_INCREMENT observations. No historical evidence is rewritten.

## Automated verification

Focused PHP tests use the installed official H5P class definitions without service
construction, a read-only content-identity double and real existing Profile/Role/
Appearance services. They cover Administrator/Subscriber access, ownership and
other-content permissions, every-player authorization, invalid routes/IDs/screens,
nonce/session forgery, revocation, global/Core fallback and SNORDIAN.

JavaScript fixtures run the shared controller for DIV and internal iframe players,
all Density choices/resizing, multiple distinct and duplicate players, matching
head/footer proof, forged markers, excluded pages, readiness and cleanup. The
loopback HTTP harness additionally uses native WordPress cookie/session validation
and nonce creation/verification, backed by fixture identity/ownership seams.
It does not bootstrap an installed site or connect to a database.

Working-source matrix: **45/45 commands PASS on each PHP 7.4.33, 8.3.18 and
8.4.22 (135/135)**, including all 32 release-tooling tests per run. Focused admin
PHP tests pass 137 assertions, admin JavaScript 106 and extended HTTP transport
332 on each version. Syntax passes for 145 source PHP files on all three versions,
14 JavaScript files, seven Python files and 11 JSON files. All 80 extracted
production PHP files lint on all three versions. The private syntax report records
**692 external syntax/version commands PASS**, plus Python/JSON parsing and all
93 extracted payload byte comparisons.

The consolidated runner retains the previous security suites and adds the two
focused jobs. Exact final commands, results, source diffs, syntax checks, package
identity and preservation comparisons are retained in the private G2 report.
Database/lifecycle suites are excluded: this context-only task does not require
database/schema changes or installation/activation. This exclusion is not a PASS.
The nine excluded jobs are storage/mysql, profile/mysql, roles/mysql, import/mysql,
profile/installation-mysql, owned-storage/mysql, owned-storage/production-mysql,
profile/activation-wordpress and owned-storage/wordpress-innodb. Final public-source
projection verification and reproducible beta-package details are recorded outside
the public tree to avoid a self-referential projection digest.

## Proposed Firefox acceptance after separate deployment authorization

1. On `wp-beta-test`, verify the candidate/version and record the exact viewing URL
   for Administrator and Subscriber; do not infer a renderer from its menu label.
2. Test personal Mint/Lavender/custom Themes and Original/Small/Medium/Large Density
   on modern DIV and internal iframe activities, including resize/fullscreen.
3. Verify independent Theme/Density changes, explicit Core, site default, unpublished/
   deleted saved Theme fallback and global/Core fallback.
4. Revoke/restore personal-selection permission and confirm fallback/preserved choices.
   Separately verify denied H5P viewing capability and own/others-content access.
5. Verify SNORDIAN suspension and restoration without preference loss.
6. Verify Profile/list/editor/results/unrelated admin screens and external embeds
   receive no new Papi Jo runtime attachment.
7. Check ordinary-page playback, multiple distinct players, duplicate-ID rejection,
   browser back/forward/tab return and iframe reload/replacement.
8. Check slow/failed transport: native content is revealed, with the known 15-second
   limitation retained; inspect focus, status announcements and admin layout.

Live H5P views log viewing data through the official plugin. These scenarios are
plans only and require explicit deployment/manual-acceptance authorization.
