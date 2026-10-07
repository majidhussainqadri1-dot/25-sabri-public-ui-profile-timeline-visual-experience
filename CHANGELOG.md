# Changelog

## 0.15.0 — Admin/Plan/Cross-File Completeness Reviews 655–674 — 2026-10-07

### Corrected

- Removed five administrator preferences that had no observable runtime effect: `timeline_provider_mode`, `accessibility_mode`, `cache_ttl`, `safe_mode_controls`, and `diagnostics_enabled`.
- Kept accessibility, Safe Mode and diagnostics mandatory/non-disableable and kept public profile responses no-store until an accepted cache-partition contract exists.
- Reorganized the control center under the governed File 25 §71 sections.
- Removed unsupported `tablet` from the responsive-preview preference family.
- Stopped fabricating avatar alt evidence before Completion Assistant evaluation; canonical File 03 alt metadata now determines completion, while public rendering retains an accessible descriptive fallback.
- Added bounded `default_timeline_filter` with runtime validation against active approved filters.
- Added bounded `enabled_optional_providers` with explicit disable-all and runtime enforcement for built-in File 06/10/11/12/18 section providers.
- Advanced review/source/release documentation through Reviews 655–674 without self-certifying current-head CI or any staging/live gate.

### Review result

Defect rounds: **657, 658, 659, 660, 662, 673**. The other fourteen review scopes were clean after carrying forward prior corrections.

### Acceptance boundary

These corrections establish File 25 repository-source completeness only. Exact current-head CI must be checked externally after the final commit. Hostinger staging, Founder acceptance, production/live deployment, exact deployed-code parity, live DB/schema, migration state and operations remain separate unverified gates.


## 0.15.0 — Plan/Code/Cross-File Completeness Reviews 635–654 — 2026-10-06

### Corrected

- Completed File 25 §38 View as Public with governed public/member/mobile/desktop/contact/search/social projection modes instead of a label-only preview.
- Suppressed owner/viewer-mutating profile actions and the Completion Assistant inside preview so preview cannot widen authorization or expose owner controls.
- Added usable owner-only preview destinations to the Completion Assistant while preserving native File 03 editing ownership.
- Added bounded `timeline_page_size` (5–50, default 20) and made profile Timeline pagination consume it.
- Reconciled current File 03 contract `1.4.0` public-media DTO shape: File 25 now consumes owner-approved same-origin `url`/`alt`/focal fields without requiring an absent `attachment_id`.
- Added explicit browser-Back scroll restoration using same-tab session state while keeping filters/search/pagination URL-addressable.
- Wired `profile_template` standard/compact/institutional into observable token-based presentation without creating a second shell.
- Reconciled stale README/WordPress-readme/source-status evidence and removed obsolete historical dependency pins from installation instructions.

### Review result

Defect rounds: **638, 639, 640, 641, 652, 653**. The other fourteen review scopes were clean after carrying forward prior corrections.

### Acceptance boundary

These corrections establish File 25 repository-source completeness only. Exact current-head CI must be checked externally after the final commit. Hostinger staging, Founder acceptance, production/live deployment, exact deployed-code parity, live DB/schema, migration state and operations remain separate unverified gates.


## 0.15.0 — Fresh Cross-File Audit Reviews 615–634 — 2026-10-06

### Corrected

- Removed reliance on the unpublished `sabri_file08_public_clinic_projection_v1` profile-action hook. Current File 08 `1.2.15` exposes a bounded clinic read projection and canonical booking routes, but no reviewed profile-user→booking-destination owner contract; File 25 now hides Appointment instead of inferring foreign truth.
- Removed reliance on unpublished File 17 profile-action URL hooks. Follow remains hidden until File 17 publishes a target-bound owner destination. Message is rendered only from a concrete owner-approved URL present in File 03's public projection.
- Refreshed source/dependency/release truth that still described the earlier Reviews 595–614 branch and pending baseline CI after main `19009a...` had already passed run `1540`.

### Review result

Twenty fresh scopes were reviewed. Defect rounds: **621, 623, 634**. Rounds **615–620, 622, 624–633** were clean within their assigned source scope after carrying forward prior corrections.

### Acceptance boundary

File 25 source-owned defects identified in this cycle are corrected. Cross-file owner-contract gaps are recorded rather than bypassed. The new candidate still requires exact-head CI; Hostinger staging, Founder acceptance, production/live deployment, deployed-code parity, live DB/schema, live migration state and operations remain unverified.


## 0.15.0 — Twenty-Round Current Cross-File Completion Review 595–614

### Post-review revalidation — 2026-10-06

- Added owner-native, fail-closed profile action resolution for File 03 report/edit/privacy, File 17 Message, File 08 Appointment, File 22 Composer and File 23 Publishing Dashboard.
- Follow is deliberately hidden until File 17 publishes a target-bound Follow URL contract; a generic Network page is not mislabeled as a Follow action.
- Refreshed File 09 current main to `9639f75ba046ac1a36e39d5e9aae56c7bae3279b` and File 23 current main to `dcae138e6073f4d0ff596623deb05b9940b8271b` after both advanced relative to the dated audit.
- Added syntax validation for `assets/js/future-public-experience.js` and a governed post-614 regression gate.
### Corrected

- Reconciled File 25 with current File 00 main membership contract `1.2.3` while preserving the reviewed `1.2.2` compatibility family and zero foreign-table reads.
- Reconciled File 25 with current File 08 public clinic projection `1.1.0` while preserving reviewed `1.0.0` compatibility under the same strict bounded field/exclusion contract.
- Restored fail-closed authorization for high-risk timeline-index rebuild and migration execute/rollback operations; capability alone no longer authorizes them.
- Added a Safe Mode public recovery surface so canonical File 25 profile routes remain recoverable through a data-free, no-store/noindex `503` native-theme template while optional providers and File 25 mutations stay disabled.
- Reconciled current companion repository-head observations and integrated WordPress/PHP minima without converting repository evidence into staging/live evidence.
- Restored the explicit release-documentation contracts required by the File 25 plan.

### Added

- `config/review595-614-twenty-round-cross-file-completeness-ledger.json`
- `tests/review595-614-twenty-round-cross-file-completeness.php`
- `docs/CROSS-FILE-HEAD-AUDIT-2026-10-05.md`
- `ACCESSIBILITY.md`, `PERFORMANCE.md`, `MIGRATION.md`, `ROLLBACK.md`, and `STAGING-ACCEPTANCE.md`
- Current repository-head observations in `config/staging-dependencies.json`.

### Review result

Defect rounds: **596, 599, 610, 611, 613, 614**. The other fourteen rounds found no new source defect in their assigned scope after earlier corrections were carried forward.

### Acceptance boundary

The `0.15.0` branch is a repository source candidate. Exact-head CI, Hostinger-equivalent staging, real browser/accessibility/RTL/performance evidence, Founder acceptance, production deployment, exact deployed-code parity, live DB/schema verification, migration-state verification and operational monitoring remain separate gates.


## 0.14.0 — Three-Plan Corrective Review 174–176

### Corrected

- Preserved canonical File 03-owned phone/WhatsApp values through the default public-contact filter while keeping filter behavior monotonic and substitution-proof.
- Changed index-rebuild and migration execute/rollback authorization from default allow to default deny; strict explicit approval is now required in addition to capability checks.
- Removed the contradictory Security statement that described reviewed File 24 source-contract integration as incomplete while retaining all environment and acceptance limits.

### Added

- Added `config/all-chats-directive-matrix.json` for All-Chats Recovered Directive Register `v2.1` traceability.
- Added the `CHAT-UX-001` accessible green welcome visual primitive; File 20 remains the exclusive invocation, session and 30-day persistence owner.
- Added `tests/review174-176-three-plan-corrections.php` and the corresponding corrective record.

### Acceptance boundary

- Source correction and behavioral regression evidence do not establish Hostinger staging, real-role browser/RTL/accessibility acceptance, Founder acceptance, merge, production, live deployment, or operations.

## 0.14.0 — Source Implementation Completion Declaration

### Added

- Added `config/source-completion-matrix.json`, a machine-readable traceability record joining the Definitive Master Plan `v3.0` and File 25 Final Harmonized Specification `2.0` to source evidence.
- Added `tools/verify-source-completion.php` and made it part of the governed Composer test suite.
- Added `docs/SOURCE-IMPLEMENTATION-COMPLETION-DECLARATION-2026-08-04.md`.

### Completion boundary

- File 25 source implementation is declared complete within its approved canonical scope.
- Known unresolved source defects at this declaration point: `0`.
- Hostinger staging, real installed integrations, browser/accessibility/RTL evidence, Founder acceptance, merge, production, live deployment and operational evidence remain separate deferred gates.
- Any new defect, dependency change, security finding or user evidence reopens review and correction.

## 0.14.0 — Authoritative Contracts and Foreign-Table Decoupling

### Corrected

- Reconciled File 25 with the Sabri Platform Master Plan `v3.0` and File 20 harmonized plan `v4.1`.
- Replaced stale File 00 assumptions with exact runtime `1.2.4` and public assertion contract `1.1.2`.
- Made `SMC_Contracts::assertions()` the authoritative membership, eligibility, suspension, guardian-policy, can-practice, and public-profile source.
- Removed File 25 direct access to File 00 professional-credential and clinic tables.
- Removed locally derived Doctor verification based on role, qualification fields, or license-expiry queries.
- Required a current File 09 `1.1.0` verification decision and immutable approved snapshot in addition to File 00 Doctor eligibility.
- Removed File 25 age calculation. Explicit File 00 minor/guardian assertions govern; unknown ordinary contact state fails closed.
- Replaced the earlier File 08 placeholder with the exact reviewed File 08 `0.2.1` public clinic projection contract `1.0.0`, bound to source commit `bd6a10b693991fc518788ef8e3cba49531454821` and candidate SHA-256 `36ce0c78aa51396b02bd0705021e66782bfc45b74636ac65b0826bd372103578`.
- Required File 08 owner APIs `swc_get_public_clinic_projection()` and `swc_public_clinic_projection_contract()` while retaining File 25's bounded allow-list and zero foreign-table reads.
- Replaced File 18 direct SQL with owner-executed public DTO APIs. The reviewed `1.2.0-RC1` transitional adapter is bounded and read-only.
- Updated the File 00/03/08/09/18/20/21/24/25 dependency matrix and Hostinger staging scenarios.

### Added

- `tests/authoritative-native-contracts.php` for File 00/08/09 fail-closed ownership behavior, now exercised against File 08 `0.2.1` and contract introspection `1.0.0`.
- File 18 owner-API regression coverage prohibiting `$wpdb`, `SMP_DB::table`, and native table queries in File 25.
- Master Plan v3.0, File 20 v4.1, File 00 `1.2.4`, File 08 `0.2.1`, File 09 `1.1.0`, File 18 `1.2.0-RC1`, and File 25 `0.14.0` reconciliation gates.
- Exact File 08 commit, candidate digest, required symbols, source CI evidence, and pending-Hostinger state in deterministic release-engineering and structural verification.

### File 08 integration review rounds

- **Round 1:** synchronized the dependency matrix and tests to File 08 `0.2.1`; corrected stale structural and Master Plan assertions that still expected `0.2.0`.
- **Round 2:** strengthened exact package, commit, digest, owner-symbol, contract-state, and real-staging scenario checks so source/CI verification cannot be mislabeled as Hostinger acceptance.

### Acceptance boundary

- File 08 contract implementation and source/package verification are complete, but exact multi-plugin Hostinger runtime acceptance remains pending.
- Browsers, Urdu RTL, accessibility, performance, upgrade, rollback, restore, monitoring, Founder acceptance, PR merge, release, and live deployment remain pending.
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
