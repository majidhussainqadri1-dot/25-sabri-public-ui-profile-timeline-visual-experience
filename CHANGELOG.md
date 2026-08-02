# Changelog

## 0.14.0 — Authoritative Contracts and Foreign-Table Decoupling

### Corrected

- Reconciled File 25 with the Sabri Platform Master Plan `v3.0` and File 20 harmonized plan `v4.1`.
- Replaced stale File 00 assumptions with exact runtime `1.2.4` and public assertion contract `1.1.2`.
- Made `SMC_Contracts::assertions()` the authoritative membership, eligibility, suspension, guardian-policy, can-practice, and public-profile source.
- Removed File 25 direct access to File 00 professional-credential and clinic tables.
- Removed locally derived Doctor verification based on role, qualification fields, or license-expiry queries.
- Required a current File 09 `1.1.0` verification decision and immutable approved snapshot in addition to File 00 Doctor eligibility.
- Removed File 25 age calculation. Explicit File 00 minor/guardian assertions govern; unknown ordinary contact state fails closed.
- Added a fail-closed File 08 public-clinic projection contract `1.0.0`; clinic output remains unavailable until File 08 implements the owner API.
- Replaced File 18 direct SQL with owner-executed public DTO APIs. The reviewed `1.2.0-RC1` transitional adapter is bounded and read-only.
- Updated the File 00/03/08/09/18/20/21/24/25 dependency matrix and Hostinger staging scenarios.

### Added

- `tests/authoritative-native-contracts.php` for File 00/08/09 fail-closed ownership behavior.
- File 18 owner-API regression coverage prohibiting `$wpdb`, `SMP_DB::table`, and native table queries in File 25.
- Master Plan v3.0, File 20 v4.1, File 00 `1.2.4`, File 09 `1.1.0`, File 18 `1.2.0-RC1`, and File 25 `0.14.0` reconciliation gates.
- CI, Composer, deterministic release-engineering, staging-test, and structural verification updates.

### Acceptance boundary

- File 08 public clinic contract, exact multi-plugin Hostinger staging, browsers, Urdu RTL, accessibility, performance, upgrade, rollback, restore, monitoring, Founder acceptance, PR merge, release, and live deployment remain pending.
- Green CI or a deterministic package is not staging or production acceptance.

## 0.13.0 — Master-Plan and File 25 Final-Specification Reconciliation

- Removed the verified-Doctor contact privacy bypass and made File 03 consent authoritative.
- Froze the Founder public spelling, removed external avatar fallback, and enforced same-origin media.
- Prevented empty forced third-column Doctor layouts.
- Added accessible breadcrumbs, structured metadata, 404/410 handling, structured Knowledge/Media APIs, provider health, shared public card normalization, and deterministic ETags.

## 0.12.0 — Exact File 24 Integration and No-Store Security Boundary

- Added exact `SPCRC_VERSION` compatibility, bounded File 25 manifest, advisory monitoring, and no-store profile policy.
- Preserved File 24 ownership of security, privacy, compliance, incident, audit, and resilience governance.

## 0.11.0 — Provider Route Parity and Hostinger Preflight

- Added installed-package verification, Hostinger preflight, schema-2 upgrade coordination, and optional-section route parity.

## 0.10.0 — Artifact Integrity and Deterministic Staging Candidate

- Added deterministic package building, embedded/detached manifests, checksums, and independent artifact verification.

## 0.9.0 — Atomic Providers and Commit-Bound Evidence

- Added immutable provider metadata, initial File 18 projection, server-only projection keys, and exact-commit visual evidence.

## 0.8.0 — Native Media Adapters

- Added File 10 Video, File 11 Reels, and File 12 PDF read-only adapters.

## 0.7.0 — Visual Acceptance Contract

- Added immutable provider identity and machine-readable visual Definition of Done.

## 0.6.0 — Optional Profile Sections

- Added bounded Knowledge, Media, Reviews, Research, and Marketplace sections and File 06 adapter.

## 0.5.0 — Content Cards and URL Security

- Added same-origin URL policy, reusable cards, forms, tables, notices, and contrast-safe tokens.

## 0.4.0 — Global Visual System Foundation

- Added semantic tokens, reusable components, nested Safe Mode, and diagnostics.

## 0.3.0 — File 21 Timeline Adapter

- Added native read-only File 21 timeline normalization.

## 0.2.0 — File 20 Integration and Profiles

- Added File 20 shell integration and Founder/Doctor profile foundations.

## 0.1.0 — Reviewed Foundation

- Added bootstrap, dependency diagnostics, privacy-first profiles, routes, timeline contracts, REST foundations, and CI.
