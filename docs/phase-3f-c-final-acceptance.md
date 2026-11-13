# Phase 3F-C — Final acceptance

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

9 November 2026. **Recommendation: PASS.** The targeted live integrity check passed at 14:09 Europe/Paris. The known informational-notice wording remains a nonblocking maintenance item.

## Evidence boundaries

- **User-confirmed manual acceptance:** 24 Firefox tests PASS, covering single/selected/all custom exports, colors-only JSON, validation/preview/cancel, confirmed unpublished import, duplicate rejection, native Backdrop import and its 25 overrides, Density independence, Core-default preservation, SNORDIAN coexistence and existing H5P playback. This is the user's supplied aggregate result; an individual 24-row test log was not supplied or reconstructed. Firefox tests were not repeated here.
- **Existing automated evidence:** the completed [Phase 3D report](phase-3d-import-persistence-verification.md) and [Phase 3E report](phase-3e-admin-ui-verification.md), together with the corresponding existing test cases described below. Their historical results are cited, not represented as new runs.
- **New verification:** one explicit read-only consistent-snapshot transaction against acceptance.example.test, pure in-memory Backdrop parsing/export, and comparison of all 78 installed file hashes with the verified Phase 3F-B stage. No live WordPress bootstrap or HTTP request was made. No regression suite or disposable database was needed.

## Live integrity — new PASS

The current aggregate validates under the installed schema. Its revision is **35**, versus **31** before acceptance: a delta of **4**, consistent with the four authorized Theme additions. The comparison permits those additions instead of incorrectly requiring the pre-acceptance aggregate bytes/revision.

| Exact Theme identifier | Preset | Explicit overrides | Publication |
| --- | --- | ---: | --- |
| export_test_mint | mint | 0 | Unpublished |
| export_test_lavender | lavender | 0 | Unpublished |
| import_test_mint | mint | 0 | Unpublished |
| colorblind_friendly | daylight | 25 | Unpublished |

These are exactly the four stored custom definitions; each internal identifier matches its storage key. Every preset uses version **moodle-478fed30d2a4**. Zero explicit overrides on the Mint/Lavender records retain preset inheritance.

The global default remains **H5P Core (null)** and the entire settings record matches Phase 3F-B. Publication equals the previous policy plus precisely the four new false custom entries: the existing builtin flags and five historical tombstones are unchanged. Papi Jo role permissions, WordPress roles, capability/legacy preference metadata, activation list, home/siteurl and both SNORDIAN options match the Phase 3F-B records byte-for-byte. Papi Jo, official H5P and SNORDIAN therefore remain active.

| User | Profile revision | Theme | Density | allow_theme / allow_density | Comparison with 3F-B |
| --- | ---: | --- | --- | --- | --- |
| 1 | 12 | site_default | original | 0 / 0 | Entire record identical |
| 2 | 4 | site_default | original | 0 / 0 | Entire record identical |

Profile state SHA-256 values remain user 1 **PRIVATE_VERIFICATION_DIGEST_OMITTED** and user 2 **PRIVATE_VERIFICATION_DIGEST_OMITTED**. Current Theme-option SHA-256 is **PRIVATE_VERIFICATION_DIGEST_OMITTED**; its change is expected from acceptance.

All **78 installed files** still match the Phase 3F-B staged hashes, including the runtime-enabled installed bootstrap. This connects the existing implementation/test evidence to the deployed code without another broad audit.

## Preview security — PASS using existing evidence

Phase 3E records **92 isolated transfer-administration assertions**, **51 HTTP/DOM checks**, and **47 disposable-MySQL assertions including 10 UI integration assertions**, per previously tested PHP version. Those results were not rerun for this final check.

| Required property | Supporting evidence |
| --- | --- |
| Preview writes nothing | tests/admin-transfer/run.php checks zero writes and exact original state; tests/admin-transfer/mysql-checks.php checks exact SQL state after preview. |
| Cancel writes nothing | User's manual cancellation PASS; Transfer_View renders Cancel as an ordinary overview link, and Admin_Controller's GET load path returns before POST dispatch. Phase 3E documents stateless previews with no server-side preview persistence. This is combined manual/control-flow evidence, not a newly claimed dedicated Cancel test. |
| Expired/tampered review rejected | Existing isolated tests reject expiry after 901 seconds and changes to payload, revision, context, digest, signature and expiry. SQL integration rejects a forged payload without changing the aggregate. |
| Different user/session rejected | Existing isolated tests change user/session and the same user's login session; HTTP fixtures cover cross-session rejection. |
| Stale revision cannot commit | Existing isolated tests cover stale preview and CAS-time races; SQL integration covers a genuine intervening import. |
| Nonces/capabilities enforced | Existing isolated tests reject bad preview/commit nonces and denied capabilities, including rejection before repository access. |

These are isolated fixture/security results; the user's manual acceptance supplies the live browser context. No expiry, tampering, collision or privilege attack was attempted on acceptance.example.test during this phase.

## Atomic batch import — PASS using existing evidence

Phase 3D's **358 import-double assertions** and **37 real disposable-MySQL assertions**, plus Phase 3E's UI integration checks, cover:

- A complete valid two-Theme batch written together, exactly one aggregate UPDATE and one revision increment, with both imports unpublished.
- Existing-ID/duplicate collisions and invalid final records rejecting the complete batch, without a partial accepted snapshot or new definitions.
- An actual disposable InnoDB CHECK constraint rejecting the final member's aggregate UPDATE and preserving the entire previous row; a read-only database session also rejects persistence without partial state.
- Synchronized competing writers where one whole batch wins and the loser's tail is absent.
- Exact existing default/definition preservation, including legacy Density, and unchanged independent role/profile sentinels.

The production schema stores the library in one row, so the real SQL failure checks whole-statement atomicity rather than pretending there are separate per-Theme INSERTs. Lost acknowledgement remains an explicitly uncertain **whole-batch** outcome; the tests verify honest reporting instead of claiming rollback or retrying blindly. No such failure was induced live here.

## Native Backdrop compatibility — new PASS

Read-only native reference:

    LOCAL_PATH_OMITTED

The native file SHA-256 remains **PRIVATE_VERIFICATION_DIGEST_OMITTED**. Its exact Daylight pin is **daylight @ moodle-478fed30d2a4**. All **25 override names and values** compare exactly with the live WordPress definition, including hex spelling/case; this checks sparse overrides, not resolved palette output.

The installed pure adapter produces a canonical record with **no density field** from native metadata density=small. The existing WordPress definition has the separately authorized storage-compatibility default density=large. That legacy storage field is not the incoming Backdrop value and is not a learner Density preference; this result must not be described as a density-free storage schema.

Pure in-memory export of the live Colorblind Friendly snapshot retains all 25 overrides and the exact pin and excludes Density. Its JSON SHA-256 is **PRIVATE_VERIFICATION_DIGEST_OMITTED**, matching the earlier expected colors-only projection. No live export/import submission or file upload was performed.

## Deferred maintenance and limits

The outdated Plugins-page informational notice about early development phases and unimplemented playback colors/Density is recorded as **deferred wording maintenance**, based on the user's identified known issue. It was not edited or newly browser-tested. It does not block this acceptance recommendation.

No unresolved functional/security blocker was found within this targeted scope. Manual results remain user-supplied; automated adversarial/atomicity results are existing isolated evidence. The live findings describe one consistent snapshot and do not exclude later external edits. These boundaries do not require another full regression or browser pass for the requested closeout.

Private evidence, outside the web root:

    PROJECT_ROOT/PRIVATE_EVIDENCE_OMITTED

It contains verify.php, live-selected-snapshot.json and verification-summary.json with all comparisons. Database access used literal credential extraction, a read-only transaction and rollback, with no credentials printed. All four acceptance Themes were preserved. No live file/data changes, cleanup, deployment, Apache restart, migrations, Git commit/tag/push or release occurred.

**Phase 3F-C: PASS recommendation. STOP and await further authorization.**
