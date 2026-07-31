# File 25 Hostinger Staging Probe and Runbook

## Authority and safety boundary

Use only:

```text
https://sabrisocialstaging.sabrihomeopathy.com/
```

Do not install, activate, sync, push, publish, or deploy File 25 to:

```text
https://sabrihomeopathy.com/
```

Staging must keep registration disabled, search-engine visibility disabled (`blog_public = 0`), live unchanged, a verified rollback point, and one active File 25 copy.

## Candidate evidence required before upload

Record the outer workflow-artifact SHA-256, inner plugin-ZIP SHA-256, exact 40-character commit, File 25 version, detached manifest, and dependency matrix. Verify the downloaded artifact with:

```bash
php tools/verify-staging-artifact.php \
  --artifact=/path/to/file-25-staging-candidate-<commit>.zip \
  --artifact-sha256=<expected-outer-sha256>
```

## Approved staging module set

Install the reviewed packages for Files 00, 03, 06, 10, 11, 12, 18, 20, 21, and 25. File 24 must not be fabricated or marked accepted; its security/privacy/cache integration remains blocked until exact contract review.

## Installation sequence

1. Confirm the canonical staging host in the browser.
2. Confirm registration and search indexing are disabled.
3. Confirm backup and rollback evidence.
4. Remove obsolete duplicate File 25 copies on staging only.
5. Verify the inner ZIP checksum.
6. Upload the `sabri-public-experience` directory.
7. Activate dependencies in the approved order, then File 25.
8. Confirm no PHP fatal error and no Safe Mode incident.
9. Run the exact-commit preflight.

## Exact-commit preflight

```bash
wp sabri file25 staging-probe \
  --expected-commit=<40-character-candidate-commit>
```

A staging-only alternative is:

```php
define('SABRI_PUBLIC_EXPERIENCE_EXPECTED_COMMIT', '<40-character-candidate-commit>');
```

Remove or update that constant whenever the candidate changes. Never copy it into live configuration.

A successful report must still show:

```json
{
  "ready_for_manual_staging_tests": true,
  "manual_staging_tests_pending": true,
  "staging_accepted": false,
  "production_accepted": false
}
```

## Route verification

Test `/founder/`, `/doctors/{slug}/`, and `/profile/{slug}/`. Approved optional destinations include `/knowledge/`, `/media/`, `/reviews/`, `/research/`, and `/marketplace/`. A destination remains absent from navigation when no accepted provider supplies public content. Unknown destinations fail closed.

## Required negative cases

Verify no public leakage from minor or unknown-age accounts, private/suspended/rejected profiles, expired Doctor evidence, wrong-author content, invalid Reel duration, password-protected PDFs, unapproved sellers, nonpublic listings, unsupported deal states, or seller contacts, identity evidence, chats, offers, reports, metrics, and transactions.

## Visual and accessibility evidence

Capture exact-commit artifacts for 320×568, 390×844, 768×1024, 1024×768, 1440×900, and 1920×1080; English LTR and Urdu RTL; light/dark/forced-colors; normal/reduced motion; 100/200/400% zoom; and keyboard/pointer/touch/screen reader.

## Upgrade and rollback

Test fresh installation, upgrade, schema-2 rewrite refresh, deactivation/reactivation, Safe Mode and authenticated retry, restoration of the prior ZIP, backup restoration, and preservation of all native profiles, publications, media, Marketplace, and patient data.

## Evidence that does not count as acceptance

Source code, a ZIP, deterministic packaging, checksum, green CI, artifact verification, installed-candidate preflight, or a good Site Health result does not by itself constitute staging or production acceptance. Acceptance requires completed workflows, negative privacy tests, visual/accessibility and rollback evidence, log review, and explicit Founder approval against the exact commit.
