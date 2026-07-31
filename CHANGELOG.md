# Changelog

## 0.9.0 — Atomic Providers, File 18 Marketplace, and Commit-Bound Evidence

### Added

- Native read-only `file-18-marketplace` adapter for approved seller-owned public Marketplace listings from the reviewed File 18 `1.1.x` contract.
- Bounded public projection of title, short description, application URL, first public image, category, product type, condition, effective price/currency, deal state, and publication time.
- Server-only SHA-256 `projection_key` support for distinct native objects that share one application URL; the key is never rendered publicly.
- Concrete provider-object identity in registration metadata through `spl_object_id()`.
- One atomic `validated_metadata()` operation for ID, version, section, maturity, ownership, and object identity.
- One target commit SHA for every visual evidence record, staging record, and Founder sign-off.
- Marketplace as a required visual-acceptance surface.
- File 18 provider, privacy, price, author, seller approval, listing status, deal-state, no-write, and package tests.

### Corrected during sixth review

- Prevented a mutable provider object from impersonating another registered provider ID.
- Removed repeated provider maturity and ownership reads after consistency validation.
- Prevented several Marketplace listings from collapsing merely because File 18 currently exposes one application URL rather than item permalinks.
- Rejected mixed-commit visual evidence, impossible normalized timestamps, unsafe evidence schemes, and noncanonical staging URLs.
- Made Marketplace deal-state translations statically extractable by standard WordPress tooling.

### Ownership preserved

- File 18 remains the canonical owner of sellers, listings, moderation, contacts, chat, offers, media/files, reports, metrics, and direct-deal workflows.
- File 25 owns only the normalized public profile visual projection.

### Still pending

- Exact Files 00/03/06/10/11/12/18/20/21/25 staging acceptance.
- File 24 runtime security/privacy/cache contract.
- Native Reviews and Research adapters after exact source contracts become available and are reviewed.
- Captured visual-regression, RTL, accessibility, performance, migration, rollback, deployment, and Founder-acceptance evidence.

## 0.8.0 — Native Media Adapters and Evidence Integrity

### Added

- Native read-only `file-10-video-media` adapter for published non-Reel Video Wall items.
- Native read-only `file-11-reels-media` adapter over File 10 objects, enforcing the approved 60–600 second Reel duration.
- Native read-only `file-12-pdf-media` adapter for published PDF Library documents.
- Exact 0.1.x compatibility gates for Files 10, 11, and 12 and File 11's dependency on the reviewed File 10 contract.
- Strict public card projection for canonical title, permalink, excerpt, thumbnail, category/type, duration/pages/language, and publication time only.
- PHP 8.0/8.3 adapter tests and package rules that forbid write, upload, moderation, interaction, metric, and transaction ownership.
- Visual-acceptance evidence records requiring artifact reference, SHA-256, timestamp, reviewer, staging runtime data, commit binding, and Founder sign-off.

### Corrected during fifth review

- Froze provider maturity and native-ownership metadata in addition to ID, version, and section.
- Counted provider metadata and consistency failures truthfully instead of silently presenting a clean partial result.
- Deduplicated valid cards by exact canonical destination, while preserving case-sensitive paths.
- Rejected boolean placeholders as visual-acceptance evidence and required bounded cryptographically referenced records.
- Added the Media section to the required visual-acceptance surfaces.

### Still pending

- Exact Files 00/03/06/10/11/12/20/21/25 staging acceptance.
- File 24 runtime security/privacy/cache contract.
- Native Reviews, Research, and Marketplace adapters after source review.
- Captured visual-regression, RTL, accessibility, performance, migration, rollback, deployment, and Founder-acceptance evidence.

## 0.7.0 — Immutable Providers and Visual Acceptance Contract

- Froze registered provider ID, version, and section and revalidated them at query time.
- Isolated provider metadata failures and preserved case-sensitive canonical URL paths.
- Added the machine-readable visual-acceptance Definition-of-Done contract.

## 0.6.0 — Governed Optional Profile Sections

### Added

- A read-only `Profile_Section_Provider` contract for native Knowledge, Media, Reviews, Research, and Marketplace modules.
- A bounded registry and service with approved sections, maturity levels, native-ownership denial, failure isolation, deduplication, and content-backed tabs.
- A native read-only File 06 Knowledge adapter.
- A canonical optional-section template, notice renderer, completed reusable components, diagnostics, and tests.

### Corrected

- Prevented dead tabs and arbitrary provider HTML.
- Revalidated mutable provider state.
- Made card timestamps deterministic in UTC and rejected invalid dates.

## 0.5.0 — Reusable Content Cards and URL Security

- Added strict same-origin URL policy, presentation-only content cards, reusable forms/tables/notices, and contrast-safe visual tokens.
- Corrected protocol-relative URLs, cross-origin actions, dark-mode ownership, translation extraction, contrast, and compact-card layout.

## 0.4.0 — Unified Global Visual System Foundation

- Added canonical File 25 identity, global semantic tokens, reusable prefixed components, visual states, independent public assets, nested Safe Mode, and diagnostics.

## 0.3.0 — Native File 21 Timeline Adapter

- Added native read-only File 21 provider, bounded paging, normalization, identifier redaction, and no-write tests.

## 0.2.0 — File 20 Integration and Founder/Doctor Profiles

- Added File 20 integration, canonical author links, structured Founder and verified-Doctor profiles, strict public allow lists, and responsive sections.

## 0.1.0 — Reviewed Foundation

- Added bootstrap, Safe Mode, dependency diagnostics, privacy-first profiles, canonical routes, federated timeline contracts, REST foundations, responsive profile presentation, and CI.
