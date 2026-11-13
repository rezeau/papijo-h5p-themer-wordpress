# Phase 1C-C — HTTPS trust investigation

> Public documentation projection: machine paths, private evidence references, local host/profile identifiers and verification fingerprints are replaced with placeholders. Technical findings and original checkpoint claims are retained; historical operator commands are records, not current deployment instructions. The private development original is preserved. Use CONTRIBUTING.md for current commands.

7 November 2026. Browser acceptance is paused at the user's request. HTTPS remains
`https://acceptance.example.test`; no Apache, WordPress URL, certificate, trust-store or Firefox
exception changes have been made. No browser warning was bypassed.

## Findings

The acceptance browser was the **Codex/ChatGPT in-app browser**, distinct from the
user's normal Firefox profile. Opening https://acceptance.example.test failed with
`net::ERR_CERT_AUTHORITY_INVALID` before an application page was available.

Read-only checks establish:

| Check | Result |
| --- | --- |
| Apache HTTPS virtual host | acceptance.example.test on port 443; configured leaf is `WORDPRESS_ROOT.crt` |
| Actual TLS peer certificate | Matches that configured leaf exactly |
| Leaf subject | CN=acceptance.example.test, OU=Wampserver, O=Otomatic & Cie |
| Issuer | Local Otomatic & Cie CA |
| Leaf validity | 6 October 2025–6 October 2037; not currently expired |
| Local CA validity | 31 July 2025–4 August 2030; not currently expired |
| Signature/chain against the explicitly supplied local CA | OpenSSL offline verify: OK |
| Windows CurrentUser/LocalMachine Root and intermediate CA stores | No exact match for the local Wamp CA in any of these four stores |
| Windows offline chain check | `UntrustedRoot`, even with the issuing CA supplied as chain-building material rather than trusted root |
| TLS-only probe with verification failure enforced | Fails issuer verification: unable to get local issuer certificate, code 20 |
| Firefox default-release cert9.db | Contains the exact local issuing CA certificate |
| Firefox default-release cert_override.txt | Already contains acceptance.example.test:443 and the exact live leaf fingerprint |
| Leaf Subject Alternative Name | Absent; the certificate has no extensions |

Leaf DER SHA-256 fingerprint:

`PRIVATE_VERIFICATION_DIGEST_OMITTED`

Local CA SHA-1 thumbprint, used for exact Windows-store matching:

`PRIVATE_VERIFICATION_DIGEST_OMITTED`

The Firefox profile inspected was
`LOCAL_PATH_OMITTED`,
identified by profiles.ini as the installation's default profile. The older
BROWSER_PROFILE_OMITTED profile did not contain those matching entries. Reading cert9.db
used SQLite read-only/immutable mode; neither profile was edited.

## Interpretation and limits

**The evidence points to a browser/profile trust difference.** Firefox has local
CA/certificate-exception configuration that is not shared with the acceptance
browser. The reported authority error is consistent with the issuing CA not being
trusted in that separate browser context. This is not evidence that the intended
HTTPS URL should be changed. Firefox supports browser-specific local CA handling;
see [Mozilla's CA documentation](https://support.mozilla.org/en-US/kb/setting-certificate-authorities-firefox).

There is also an independent compatibility concern: the leaf identifies acceptance.example.test
only through its Common Name and has no SAN. Chromium removed ordinary CN fallback;
see the [Chromium certificate compatibility issue](https://issues.chromium.org/issues/40087027).
Consequently, trusting the CA alone is not guaranteed to make this leaf acceptable
to that browser. No attempt was made to bypass or test past its authority warning.

The in-app browser's private certificate store was not directly enumerated: the
available browser API did not expose that trust database. Windows-store results
are evidence about the host, not a claim that every in-app verification path uses
only those stores. The Firefox findings establish saved configuration, not a new
browser-page acceptance result. A native Firefox accessibility observation failed
before navigation/testing; no Firefox acceptance was completed.

## What was and was not changed

Diagnostics read public certificate files, the existing Apache configuration,
Windows certificate stores and the Firefox public certificate/exception database.
The TLS probe used `-verify_return_error`, exchanged no HTTP application request,
and did not accept an untrusted peer. Offline explicit-CA verification was a local
diagnostic operation, not a trust installation or browser override. Revocation was
disabled only on the temporary offline .NET chain object, not in the OS/browser.

No certificate/private key was replaced, generated or installed. No trust setting,
saved exception, TLS-validation flag, WordPress URL, Apache directive or hosts entry
was modified. The earlier explicitly authorized wp_options conversion had already
passed before this investigation; it does not affect certificate verification.
The Papi Jo plugin remains undeployed and inactive.

Evidence is stored privately under
`PRIVATE_EVIDENCE_OMITTED` as
certificate-diagnostics.json, windows-certificate-trust.json and
tls-handshake-diagnostic.txt. These are excluded from the deployment payload.

## Next step

Report these findings before resuming browser acceptance. Retain the current site
configuration. Using the user's existing Firefox environment for manual checks
would preserve its current trust setup, provided the site opens there normally.
No automated warning bypass or certificate/trust repair is authorized or proposed
as an automatic next action. Any change to browser trust or server certificates
would need a separate decision; none has been made.
