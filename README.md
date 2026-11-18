# Papi Jo H5P Themer for WordPress

Maintenance candidate **0.2.1** improves only the first-installation Theme-storage warning and its administration link. Existing initialized libraries do not show it. Storage, explicit initialization and uninstall/reinstall retention are unchanged. See [0.2.1 release notes](docs/release-notes-0.2.1.md). The project owner reports Firefox Tests 1 and 2 PASS; Test 3 is accepted using isolated automated tests. The absent-storage warning was not manually rendered in Firefox. This candidate has not been installed or published.

Independent Theme and Density preferences for supported WordPress H5P playback.
This is not a fork of SNORDIAN’s H5P Themer.

**Historical v0.2.0 preparation**, prepared locally under [Stage G4](docs/stage-g4-release.md)
from accepted 0.2.0-beta.1 without functional changes. It supports Theme and Density
in the official H5P administration activity viewer, with authenticated content
access, personal preferences and Global Default fallback. All 17 Stage G3B Firefox
tests were reported PASS by the project owner; Codex did not independently reproduce
them. The two former viewing limitations are resolved only for this accepted official
viewer context. See [0.2.0 release notes](docs/release-notes-0.2.0.md).

Published stable **0.1.1** and its tag/ZIP are unchanged. Its historical preparation
record is [F8](docs/stage-f8-release.md).
[F2 storage integration](docs/owned-storage-stage-f2.md) puts Theme and role state in
a plugin-owned InnoDB State table; personal preferences use the separate InnoDB
Profile table. WordPress options may use MyISAM or InnoDB; no WordPress-table
conversion is required. Legacy pre-F2 options are preserved and refused without
migration, deletion or reset; use a separate fresh installation.

Stage F4 recorded **25/25 user-reported manual Firefox tests PASS** on beta.2,
including MyISAM options, owned InnoDB storage, Themes, personal preferences and
SNORDIAN coexistence. Stage F7 recorded **3/3 user-reported Firefox smoke tests
PASS** on beta.3, as confirmed in the F8 owner request. Codex did not independently
reproduce these manual results. F8 does not claim fresh Firefox testing of stable bytes.

The original [F3 preservation check](docs/stage-f3-release.md) did not pass: two
existing test sites had cache-timeout/AUTO_INCREMENT changes. Their writer and
trigger remain unidentified; other retained table fingerprints matched and no
database corruption was demonstrated. Under **DOCUMENTED RISK ACCEPTANCE**, the
project owner accepts the residual uncertainty, requires no further investigation
for this release and removes that release-blocking gate. Preservation was not proven;
the original evidence is unchanged. See the [F6 disposition](docs/stage-f6-release.md).

G3A recorded all 28 database schemas, Themes/defaults, preferences, permissions
and owned tables preserved, but the wp_options row fingerprint and reported next
AUTO_INCREMENT changed (262 to 263), with attribution unverified. The owner accepts
this uncertainty for 0.2.0: **ACCEPTED RESIDUAL RISK — NOT A RELEASE BLOCKER**.
This is not a full-database preservation PASS. The 15-second readiness limitation
remains unresolved; slow-network readiness is not fully verified.

Previous beta.1/beta.2/beta.3 artifacts, metadata and evidence are preserved. Deployment,
Git operations and publication require separate authorization.

## Features

- Five immutable built-in Themes: Daylight, Lavender, Mint, Sunset and Dark.
- Custom Themes with pinned preset inheritance, sparse hexadecimal color overrides,
  duplication, stable identifiers and editable names.
- Independent global Theme default and custom Theme publication controls.
- Theme color Export/Import: one, selected or all custom Themes; validated JSON,
  review and explicit confirmation before new unpublished Themes are created.
- WordPress own-Profile Theme selection and isolated representative color preview.
- Independent Density preferences: Original, Small, Medium and Large. Theme changes
  do not change Density, and color transfers exclude Density and preferences.
- Role-based access to personal preferences, with capability-based administrator access.
- Runtime Theme and native Density application to qualified frontend DIV players
  and supported same-origin internal H5P iframes, plus the official H5P activity
  viewer for Administrators and authorized Subscribers; multiple activities are supported.
- Conservative SNORDIAN conflict detection, sanitized diagnostics and explicit
  storage setup/retry behavior.

## Requirements and compatibility

Declared minimums are **WordPress 6.5** and **PHP 7.4**. These are compatibility
targets, not a tested matrix of every version combination. The inspected local
reference is WordPress 7.1.4, official H5P 1.17.10 and **H5P Core API exactly 1.28**.
The plugin requires the canonical `h5p/h5p.php` integration and verifies class
locations, public API signatures and literal asset-hook emitters. An H5P version
number alone does not establish compatibility; unrecognized integrations fail closed.

Storage requires single-site WordPress, standard MySQL-backed `wpdb`, mysqli,
nonpersistent private connections, autocommit, InnoDB and the supported table
schemas for the owned State and Profile tables. WordPress options may use MyISAM
or InnoDB and are read only to detect unsupported legacy Papi Jo state. Unsupported backends are diagnosed without repair.
See [installation](docs/installation.md) and [persistence contract](docs/wordpress-persistence.md).

Missing/inactive/unsupported H5P does not prevent activation or administrative
diagnostics. No `Requires Plugins` header is declared for that reason. Multisite
management and runtime are unsupported. Palette previews are representative
samples; content libraries must actually use the relevant H5P variables/classes.
There is no claim of universal library support or accessibility certification.

## Installation and first use

A verified candidate ZIP contains one directory named
`papijo-h5p-themer-wordpress/`. Use the dedicated plugin ZIP release asset when
available; GitHub’s automatic source archives are not the designed installer.
Review the locally prepared 0.2.0 artifact, checksum and inputs in [release metadata](docs/release-candidate.json)
and [G4 preparation](docs/stage-g4-release.md). This release supports fresh installations;
old pre-F2 installations have no migration path in this release. Installation requires
separate approval. See the [clean-install test plan](docs/clean-install-test-plan.md).

1. Back up the target site and use a staging site for the first installation.
2. Install the official H5P plugin and verify the compatibility requirements above.
3. Upload the approved plugin ZIP through **Plugins → Add New → Upload Plugin**,
   or place its top-level directory under `wp-content/plugins/`, then activate.
4. Activation ensures the supported empty State and Profile tables. Existing compatible
   records remain unchanged. Failure leaves the plugin active and shows
   administrator-only storage diagnostics; use the corresponding State or Profile setup retry
   after resolving the underlying hosting issue.
5. Open **H5P → Papi Jo H5P Themes**, or **Settings → Papi Jo H5P Themes** if the
   H5P parent is unavailable. Initialize an absent Theme library explicitly.
6. Choose the global default, create/publish custom Themes and grant the relevant
   roles access to personal preferences. Eligible users choose Theme and Density
   independently in their own WordPress Profile.

Updates that skip the activation callback use read-only storage detection and an
explicit retry POST. Ordinary page loads never create/repair storage.
Activation creates no user preference records and does not initialize Theme or
role records. There is no legacy options/usermeta migration; legacy Theme/role
options block F2 administration and preference writes without changing stored data. Deactivation and
removing the plugin preserve saved data; no uninstall cleanup handler is provided.

## Runtime and SNORDIAN coexistence

Runtime attachment is **default-on and compatibility-gated**. An undefined
`PJH5PWP_ENABLE_BROWSER_THEME` or boolean `true` enables configuration; boolean
`false` and every other value disable attachment. This is a developer constant,
not an administrator checkbox. Enabled configuration does not override admission.

Active SNORDIAN records, loaded namespace/constant or detected runtime callback
suspend both Papi Jo Theme and Density playback. Installed but inactive SNORDIAN
alone does not suspend playback. Papi Jo never deactivates SNORDIAN or alters its
callbacks, options or files. During SNORDIAN suspension, eligible own-Profile users
see an explanatory notice instead of Papi Jo selectors/preview, and preference
submissions are ignored. Existing preferences remain saved. Theme administration
and color transfer remain available, with the coexistence warning. F4
user-reported acceptance covers suspension and restoration on its beta.2 test site.

The controller obtains session-derived appearance through a private no-store
endpoint. It does not place personal preferences in shared HTML or browser storage.
Readiness conceals qualified pending activities with a bounded native fallback.
See [controller](docs/theme-controller.md) and [transport](docs/runtime-transport.md).

## Theme color Export/Import

The overview exports a custom Theme, selected custom Themes or all custom Themes,
published or unpublished. Built-in presets are distributed resources and are not
custom export entries. JSON transfers colors, inheritance and Theme descriptive
metadata; it excludes global defaults, publication settings, roles, user preferences
and Density. It is not a full plugin backup.

Import accepts the WordPress colors-only format and compatible native Backdrop
custom Theme JSON. Legacy Backdrop Density metadata is ignored. Uploads are limited
to 1 MiB and 100 Themes; validation and a session-bound review precede explicit
confirmation. Existing Themes are not overwritten; new Themes remain unpublished.
Invalid, conflicting or stale reviews are rejected. See the installation guide
for transfer and preservation checks.

## Known limitations

- Official administration support is limited to the canonical
  `wp-admin/admin.php?page=h5p&task=show&id=<ID>` activity viewer. The former
  Administrator/Subscriber viewing limitations are resolved in that accepted context;
  arbitrary wp-admin pages and the separate external `h5p_embed` endpoint are excluded.
  Supported H5P embeds in ordinary WordPress pages retain personal styling.
- Unsupported legacy content using H5P API earlier than 1.28 receives no Papi Jo
  Theme or Density styling; theme-aware markup and variables remain required.
- **Unresolved slow-network readiness limitation:** the initial 15-second budget
  starts in the head bootstrap, while the appearance request waits for DOM readiness.
  Slow page resources, H5P initialization, styles or internal iframe sizing can
  exhaust it. Native content is revealed with fallback, and automatic personal
  Theme/Density application may remain absent for that document even after loading
  finishes. Passing characterization tests do not fix it. See the
  [investigation](docs/slow-network-readiness-investigation.md).
- External/cross-origin embeds, H5P embed endpoints, framed parent documents,
  editor/admin previews, feeds and ambiguous/unproved players are excluded.
- Authenticated appearance is revalidated on lifecycle transitions and renewal;
  there is no push notification or immediate detection of every remote account,
  role, publication or SNORDIAN change. A descriptor has a 30-second hard lifetime.
- Optimizers/CSP must preserve the required head asset ordering. Broad browser,
  hosting, content-library and cache/CDN compatibility remain unqualified.
- No per-activity selectors, multisite support, automatic data migration or uninstall
  deletion are implemented. Only the English source strings are supplied.

## Development and verification

There is no Composer, npm or compilation step for installation. Production PHP,
JavaScript, CSS and pinned palettes ship as source. Local tests require PHP CLI,
Node.js and Python; browser fixtures additionally use Chromium/Edge. Optional real
database tests use random disposable databases, never the live site database.

See [test inventory and commands](docs/testing.md),
[Stage D findings](docs/stage-d-release-consolidation.md), and the
[proposed packaging/publication procedure](docs/release-preparation.md).
Stage E1 adds [offline packaging validation](docs/release-tooling.md) and a
[complete public development-source projection](docs/public-source-preparation.md).
See [CONTRIBUTING.md](CONTRIBUTING.md) for current maintenance commands. Production,
all reviewed tests/fixtures, technical documentation and tooling belong in the
public repository; the independent 93-file working-source policy applies only to the installer ZIP.
Stage G4 prepares the stable 0.2.0 ZIP and audited public-source projection. See
[G4 preparation](docs/stage-g4-release.md) and [release metadata](docs/release-candidate.json).
ZIP installation, Git initialization and publication remain separately controlled.
Older phase reports retain their original checkpoint status; they are historical
records, not current deployment instructions.

## Acknowledgments

Papi Jo H5P Themer for WordPress was developed by Joseph Rézeau with assistance
from ChatGPT and Codex (OpenAI) for technical discussions, implementation, testing,
and code review.

The author also wishes to acknowledge previous work on H5P theming by:

- Oliver Tacke (SNORDIAN), for his H5P Themer plugin for WordPress.
- Matheus Mathias, for his H5P Themer plugin for Moodle.

These earlier projects contributed to the development of H5P theming solutions
across different platforms.

Papi Jo H5P Themer for WordPress is an independent project and is not affiliated
with or officially maintained by the authors of those earlier plugins.

## License and attribution

GPL-3.0-or-later; see [LICENSE](LICENSE) and
[third-party notices](THIRD-PARTY-NOTICES.md). The Backdrop v0.5.0 reference is pinned
at `d9b53d706a94dac322b89885bf668145ab3803c4`. Palette provenance and MIT/GPL
notices are retained. No upstream picker, H5P runtime or SNORDIAN implementation
is bundled; checked-in test doubles are separate from production distribution.
