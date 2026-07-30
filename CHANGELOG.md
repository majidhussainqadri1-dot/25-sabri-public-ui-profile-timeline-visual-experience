# Changelog

## 0.4.0 — Unified Global Visual Experience and Design System

### Added

- The Founder-approved canonical identity **File 25 — Sabri Unified Global Visual Experience and Design System**, retaining **Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency** as the explanatory subtitle.
- An explicit architectural contract that this is the same File 25 and must not create a duplicate File 26.
- A versioned global design-system API exposing ownership, semantic tokens, reusable component classes, visual states, and the canonical stylesheet handle.
- Global `--sabri-visual-*` tokens inheriting File 20 `--sabri-shell-*` variables with local fallbacks.
- Prefixed `sabri-ui-*` containers, reading widths, stacks, clusters, grids, cards, buttons, badges, skeletons, and loading/empty/error/success/warning/unavailable states.
- Public integration functions `sabri_visual_experience_contract()` and `sabri_visual_experience_render_state()`.
- WordPress filters for design-system contract, tokens, and components.
- Visible focus, 44px-compatible targets, logical RTL/LTR properties, reduced-motion handling, forced-colors support, responsive containers, and local/system-font policy.
- A direct Site Health design-system test and PHP 8.0/8.3 design-system contract coverage.

### Corrected after second review

- Split the global design-system stylesheet from profile-only CSS/JavaScript so all supported public modules can consume File 25 without loading profile behavior.
- Allowed the identity-independent visual system to boot when File 00 is unavailable while keeping profile and timeline behavior fail-closed.
- Removed the profile-only inline File 20 token bridge; static versioned CSS now owns semantic inheritance and is compatible with future CSP hardening.
- Replaced the single Safe Mode boundary flag with a nested boundary stack so inner operations cannot clear an outer fatal guard or misattribute an incident.
- Emitted fatal Safe Mode changes through the File 24 integration event and bounded incident fields.
- Added package rules that require the canonical name, design-system runtime, prefixed components, File 20 ownership denial, no inline bridge, forced-colors support, and nested Safe Mode protection.
- Updated governance, architecture, decision log, README, WordPress readme, changelog, CI, and PR scope to one canonical File 25 ownership model.

### Still pending

- Cross-module adoption by native module templates and full reusable content-card catalog.
- Exact Files 00/03/20/21/25 staging and promotion of the File 21 provider beyond read-only.
- File 24 runtime security/privacy/cache contract.
- Knowledge, media, reviews, research, Marketplace, and other optional providers.
- Fresh install, upgrade, migration, rollback, viewport screenshots, Urdu RTL, keyboard, screen reader, zoom, forced-colors, reduced-motion, performance, Hostinger staging, real-user workflows, release packaging, deployment, and monitoring.

## 0.3.0 — Native File 21 Timeline Adapter

### Added

- A native read-only `file-21` provider over File 21's existing visibility-safe `Sabri\HomeNewsFeed\ProfileTimeline::query()` contract.
- Bounded multi-page retrieval using File 21's fixed twenty-item native page size, followed by File 25 normalization, same-site canonical enforcement, global sorting, deduplication, and pagination.
- Provider health evidence that explicitly records read-only behavior and denies native content ownership.
- PHP 8.0/8.3 contract coverage for native version detection, availability, maturity, multi-page retrieval, stable native offsets, identifier redaction, unsupported-content rejection, and disabled-state fail-closed behavior.
- Package checks that prohibit write primitives in the File 21 adapter and require the adapter runtime/test files.

### Corrected while coding

- Fixed a discovered native-page offset defect: the final File 21 request now keeps `per_page=20` instead of shrinking the page size and overlapping earlier results.
- Preserved native-owner precedence: a future provider registered by File 21 itself with the canonical `file-21` ID wins before File 25's compatibility adapter.
- Preserved fail-closed behavior: an active but incompatible File 21 installation is never bypassed through raw WordPress post queries.
- Prevented source-level integration from self-promoting to staging-accepted or production-accepted status; exact multi-plugin staging remains mandatory.

## 0.2.0 — File 20 Integration and Complete Founder Profile Foundation

### Added

- A bounded File 20 integration layer that consumes the real `sabri_shell_layout_mode` contract and inherited shell custom properties without rendering a second header, navigation, sidebar, or global shell.
- Canonical public author links, profile-route body classes, shell connection diagnostics, and a versioned public shell contract.
- Structured Founder public data for mission, vision, objectives, methodology, experience, research areas, location, and an ordered publication list.
- A complete Founder Overview, Books and Research, About, and Clinic and Contact presentation using only approved File 03/File 00 data.
- Structured verified-Doctor public data for qualification, institution, reviewing authority, specialization, experience, languages, consultation modes, studied books, and approved clinic details.
- A verified-Doctor Overview with an explicit verification-scope notice and no treatment-outcome guarantee.
- A strict public-data allow-list normalizer that excludes identity evidence, registration numbers, internal user/clinic IDs, private notes, and patient data.
- Dedicated PHP 8.0/8.3 profile-data contract tests and package checks for the new runtime, templates, and local assets.
- Nonprofessional public-profile `noindex/noarchive` defaults, richer ProfilePage metadata, and privacy-safe Person/Organization schema fields.

### Corrected while coding

- Prevented File 03's older Founder display name from overriding the approved File 25 public spelling.
- Prevented dead Founder Books and Research tabs by enabling the section only when approved source data exists.
- Preserved the native-owner boundary: publications remain labels/index entries until their owning Book, Learn, Encyclopedia, Video, PDF, or Research provider is accepted.
- Prevented public filters from changing identity class, verified state, contacts, canonical routes, or section authority.
- Kept all profile HTML and REST responses non-cacheable until the File 24 privacy/cache contract is operational.

## 0.1.0 — Reviewed Foundation

- Added WordPress bootstrap, Safe Mode, dependency diagnostics, privacy-first profiles, canonical routes, federated timeline contracts, REST foundations, responsive profile presentation, and CI.
- Corrected identity elevation, visibility widening, contact restoration, active File 21 bypass, private/draft timeline admission, identifier leakage, pagination, dead tabs, cache exposure, schema, contrast, reduced motion, avatar fallback, and share feedback defects.
