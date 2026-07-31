# Ninth Review — Provider Route Parity and Hostinger Installed-Candidate Preflight

Date: 2026-07-31

## Reviewed basis

The review began from File 25 runtime `0.10.0` at commit `ecb31bbbfc5eb65df4d57dee196a7b63f196e5dc`, after deterministic package creation and independent downloaded-artifact verification had passed.

The next accepted architectural step was Hostinger multi-plugin staging. No live, merge, deployment, or production action was authorized.

## Confirmed defect: approved provider sections were not routable

`Section_Registry` approved Knowledge, Media, Reviews, Research, and Marketplace, while the profile router maintained a separate hard-coded list that omitted Research and Marketplace.

Consequences included a valid Marketplace provider producing a content-backed tab whose canonical destination could return the wrong template or a 404. Green adapter tests therefore did not prove that the provider UI was reachable.

### Correction

`Profile_Router` now owns only core profile destinations and derives all optional destinations directly from `Section_Registry::approved_sections()`. The same generated list drives rewrite patterns and request recognition. Unknown sections and numeric Member slugs remain fail-closed. Route capability alone never creates a tab; accepted public cards are still required.

## Confirmed upgrade defect: existing sites could retain stale rewrites

Adding route alternatives in source is insufficient for upgraded WordPress installations because persisted rewrite rules may remain stale.

### Correction

Schema version `2` adds a bounded `Upgrade_Manager` with one lock-owning upgrader, five-minute stale-lock recovery, one rewrite refresh, schema/runtime recording, idempotent repeat execution, Safe Mode incident capture, and no native-data migration.

## Confirmed staging gap: extracted installed files lacked exact manifest verification

The workflow artifact and plugin ZIP were cryptographically verified, but the installed directory was not checked after upload/extraction for missing, tampered, stale, extra, symbolic, wrong-version, or wrong-commit files.

### Correction

The read-only `Staging_Probe` verifies the installed directory against `STAGING-MANIFEST.json`: exact package identity, version, commit, governed file set, SHA-256, byte sizes, safe paths, root containment, symlink rejection, file/size bounds, and the staging test plan. It returns no file contents or personal, patient, seller, or native record data.

## Exact Hostinger preflight gates

The probe checks:

- host is `sabrisocialstaging.sabrihomeopathy.com`;
- live `sabrihomeopathy.com` is excluded;
- HTTPS is active;
- explicit environment type is not production;
- registration is disabled;
- search indexing is disabled;
- required dependencies are available;
- Safe Mode is inactive;
- the expected exact commit is installed.

A passing result sets only `ready_for_manual_staging_tests = true`. It always preserves `manual_staging_tests_pending = true`, `staging_accepted = false`, and `production_accepted = false`.

## Operational interfaces

WP-CLI:

```bash
wp sabri file25 staging-probe --expected-commit=<40-character-candidate-commit>
```

Exit `0` means ready for manual staging tests; exit `2` means one or more fail-closed gates remain incomplete.

Site Health reports critical, recommended, or good status without converting preflight into staging acceptance.

## Machine-readable manual plan

`config/staging-test-plan.json` governs environment isolation, activation order, Founder/Doctor/Member routes, provider-route parity, privacy negatives, media and Marketplace exclusions, six viewports, Urdu RTL, input/accessibility modes, zoom/forced-colors/reduced-motion, Safe Mode, upgrade/rollback, logs/performance, and exact-commit Founder acceptance.

## Automated evidence added

PHP 8.0 and PHP 8.3 coverage now includes route parity, unknown/numeric route rejection, installed-package integrity, extra/tampered/wrong-commit/unsafe-manifest rejection, test-plan validation, schema-2 idempotence, stale-lock recovery, and extracted candidate verification in packaging CI.

## Remaining boundary

This work does not install on Hostinger. The external gate remains exact Files 00/03/06/10/11/12/18/20/21/25 staging. File 24 remains blocked until exact runtime-contract review. Live remains untouched.
