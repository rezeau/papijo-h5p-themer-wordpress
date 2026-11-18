# v0.2.1 maintenance release candidate

Prepared 18 November 2026 from published stable v0.2.0. This candidate is for final review only; it has not been installed, committed, tagged or published.

The Plugins-page warning now says **Papi Jo H5P Themer — Setup required**, explains how to reach H5P Content → Papi Jo H5P Themes and click Initialize theme storage, and links through `menu_page_url(Admin_Controller::SLUG, false)`, including the registered Settings fallback. It explains that initialization requires an explicit administrator action to protect existing data.

Only an absent Theme library produces this warning. A valid initialized library, including one retained across reinstallation, suppresses it. The existing administrator capability and Plugins-screen restrictions remain. Missing-table, schema, legacy-storage and other failure diagnostics remain separate. This is a usability improvement, not a database defect, migration or reset. Initialization POST/nonce handling, schemas, role permissions, preferences, runtime Theme/Density behavior and uninstall data retention are unchanged.

The owner reports Firefox Tests 1 and 2 PASS and accepts Test 3 using isolated automated tests. The actual uninitialized warning was not manually rendered in Firefox. These reports concern the accepted implementation before candidate packaging; no manual candidate-ZIP installation is claimed.

Compatibility remains WordPress 6.5+, PHP 7.4+, single-site with the qualified official H5P integration/Core API exactly 1.28. The 15-second readiness limitation, external-embed/multisite exclusions and accepted G3A database uncertainty remain as documented in [v0.2.0 notes](release-notes-0.2.0.md). This preparation does not establish a full-database preservation PASS.

The non-database release matrix runs on PHP 7.4.33, 8.3.18 and 8.4.22. Nine native database/lifecycle jobs are deliberately excluded; no WordPress database is modified. Exact commands, results, syntax checks, archive manifests and reproducibility evidence are retained in the private preparation report. Deterministic packaging uses the unchanged 93-file ZIP policy and excludes tests and private evidence.
