# Changelog

## 0.13.0 — Master-Plan and File 25 Final-Specification Reconciliation

### Corrected during eleventh review

- Removed the verified-Doctor contact privacy bypass. File 25 now consumes File 03's canonical `SPD_Helpers::can_show_contact()` decision for Founder, Doctor, and permitted Member contact rendering; filters may only revoke.
- Froze the approved Founder public spelling as `Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed` instead of trusting a mutable presentation option or extension filter.
- Removed silent external avatar-service fallback and restricted profile, cover, and OpenGraph media to same-origin URLs.
- Stopped every Doctor profile from forcing an empty third File 20 shell column; three-column mode now requires declared real right-sidebar content.
- Added an explicit default 404 and opt-in 410 tombstone policy for unavailable/deleted public profiles.

### Added

- Visible accessible profile breadcrumbs and `BreadcrumbList` structured data.
- Safe `og:image:alt` output.
- One public card allow-list shared by HTML and REST.
- Structured Founder/profile Knowledge and Media endpoints.
- Aggregate provider-health endpoint without provider IDs, versions, native IDs, object counts, or exception details.
- Deterministic REST ETags while retaining `no-store, private` cache policy.
- Master-plan reconciliation regression test and CI gate.
- Updated staging expectations for File 00 `1.1.13`, File 03 `0.2.0`, and File 25 `0.13.0`.

### Ownership and acceptance boundary

- File 20 remains the only shell/sidebar owner.
- File 03 remains profile/contact-consent owner.
- File 21 and native modules remain canonical content owners.
- File 24 remains security/privacy/compliance/resilience owner.
- Hostinger staging, runtime privacy tests, visual/accessibility evidence, rollback, deployment, monitoring, and Founder acceptance remain pending.

## 0.12.0 — Exact File 24 Integration and No-Store Security Boundary

### Added

- Reviewed File 24 integration for the exact `SPCRC_VERSION` runtime contract.
- Fail-closed compatibility range `>=0.25.3 <0.26.0`.
- Bounded `file-25-public-experience` manifest through `spcrc/module_manifests`.
- Site Health diagnostics for absent, incompatible, and compatible File 24 states.
- Advisory `elevated-monitoring` request when File 25 enters Safe Mode.
- File 24 integration contract, staging-matrix records, regression tests, CI gate, and review documentation.

### Corrected during tenth review

- Removed File 25's ineffective File 24 detection through nonexistent `SABRI_SECURITY_CENTER_VERSION` and `SABRI_SPRC_VERSION` constants.
- Replaced it with the exact reviewed File 24 constant `SPCRC_VERSION`.
- Kept HTML profile responses explicitly `no-store, private` in addition to the existing no-store REST responses.
- Prevented duplicate or forged File 25 manifests from overriding the canonical bounded manifest.
- Preserved File 24 ownership of security governance, privacy orchestration, audit evidence, incidents, controls, findings, and resilience.

### Acceptance boundary

- File 24 source compatibility is reviewed, but File 24 and File 25 remain pending exact Hostinger staging.
- No versioned shared-cache partition contract has been accepted.
- File 25 claims no File 24 operational capabilities or privacy operations.
- PR merge, deployment, visual/accessibility evidence, rollback proof, and Founder acceptance remain pending.

## 0.11.0 — Provider Route Parity and Hostinger Installed-Candidate Preflight

### Added

- Read-only `Staging_Probe` contract for the exact canonical Hostinger staging host.
- Installed extracted-package verification against `STAGING-MANIFEST.json`, including exact file-set, SHA-256, byte-size, path, symlink, version, and expected-commit checks.
- Privacy-safe WP-CLI command: `wp sabri file25 staging-probe --expected-commit=<sha>`.
- Site Health staging-preflight test that reports fail-closed gates without granting acceptance.
- Machine-readable `config/staging-test-plan.json` with environment, route, privacy, content, RTL, accessibility, resilience, rollback, performance, and Founder-acceptance scenarios.
- Schema version 2 and a bounded upgrade coordinator with lock ownership, stale-lock recovery, one-time rewrite refresh, and idempotence.

### Corrected during ninth review

- Fixed a canonical routing contradiction: `research` and `marketplace` were approved optional sections but absent from the profile rewrite pattern and request whitelist.
- Replaced the duplicated hard-coded provider route list with `Section_Registry::approved_sections()` parity.
- Prevented upgraded sites from retaining stale rewrite rules after provider destinations became routable.
- Added exact installed-candidate verification so a downloaded ZIP cannot be silently altered, partially extracted, or mixed with stale files before manual staging tests.

### Acceptance boundary

- The staging probe is read-only and contains no user or patient data.
- A passing preflight permits manual Hostinger staging tests only.
- Live remains untouched; PR merge, deployment, visual evidence, rollback evidence, and Founder sign-off remain pending.

## 0.10.0 — Artifact Integrity and Deterministic Staging Candidate

### Added

- Deterministic staging-package builder using a bounded payload allow list and `SOURCE_DATE_EPOCH`.
- Embedded `STAGING-MANIFEST.json`, detached manifest, and detached SHA-256 checksum.
- Archive reopening and verification of entries, hashes, sizes, root prefix, and manifest equality.
- Development-file exclusion and exact staging dependency matrix.
- CI byte-for-byte reproducibility, independent artifact verification, and temporary workflow artifact.

### Corrected during seventh and eighth reviews

- Rejected arbitrary external, traversal, absolute, query, fragment, control-character, and backslash evidence references.
- Required evidence byte size and allow-listed media type in addition to SHA-256, timestamp, reviewer, and target commit.
- Hardened build-path and symlink boundaries and removed hard-coded workflow filenames.

## 0.9.0 — Atomic Providers, File 18 Marketplace, and Commit-Bound Evidence

- Added native read-only File 18 Marketplace profile adapter.
- Bound provider metadata to concrete registered objects and made validation atomic.
- Added server-only SHA-256 projection keys for native objects sharing an application URL.
- Bound all visual evidence and Founder sign-off to one exact commit.

## 0.8.0 — Native Media Adapters and Evidence Integrity

- Added File 10 Video Wall, File 11 Reels, and File 12 PDF Library read-only adapters.
- Froze provider maturity/ownership metadata and strengthened evidence records.

## 0.7.0 — Immutable Providers and Visual Acceptance Contract

- Froze provider identity/version/section metadata and added the machine-readable Definition of Done.

## 0.6.0 — Governed Optional Profile Sections

- Added bounded Knowledge, Media, Reviews, Research, and Marketplace provider sections and File 06 adapter.

## 0.5.0 — Reusable Content Cards and URL Security

- Added same-origin URL policy, content cards, forms, tables, notices, and contrast-safe tokens.

## 0.4.0 — Unified Global Visual System Foundation

- Added canonical global semantic tokens, components, nested Safe Mode, and diagnostics.

## 0.3.0 — Native File 21 Timeline Adapter

- Added native read-only File 21 provider and bounded timeline normalization.

## 0.2.0 — File 20 Integration and Founder/Doctor Profiles

- Added File 20 integration, Founder/Doctor profiles, public allow lists, and responsive sections.

## 0.1.0 — Reviewed Foundation

- Added bootstrap, dependency diagnostics, privacy-first profiles, routes, timeline contracts, REST foundations, and CI.
