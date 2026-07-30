# Changelog

## 0.5.0 — Reusable Content Cards and URL-Security Correction

### Added

- A strict `Public_URL::sanitize_same_site()` policy for reusable public visual components.
- A presentation-only `Content_Cards` renderer and versioned contract for article, post, news, video, reel, book, PDF, Doctor, clinic, event, and Marketplace visual variants.
- The public `sabri_visual_experience_render_card()` integration function.
- Bounded title, excerpt, eyebrow, badge, metadata, date, media, and action-field allow lists that ignore native IDs and unknown private fields.
- Single-link keyboard behavior: a title link when there is no action button, or one action link when a button label is supplied.
- Local lazy media, semantic `<article>`/`<time>` markup, responsive compact cards, RTL, forced-colors, reduced-motion, and print behavior.
- Reusable notice, form-field, input, select, textarea, help/error, and responsive-table CSS primitives.
- Contrast-safe `on-primary`, `on-danger`, and surface-aware soft-primary semantic tokens.
- PHP 8.0/8.3 URL-security, content-card, contract, package, and public-helper tests.

### Corrected while reviewing 0.4.0

- Closed a protocol-relative URL escape where `//external.example` was treated as a root-relative action.
- Rejected cross-origin, credential-bearing, HTTPS downgrade, mismatched-port, control-character, backslash, and malformed destinations.
- Converted variable default-copy translation calls into literal WordPress translation strings.
- Stopped File 25 dark-mode CSS from replacing File 20-owned text, surface, page, border, and focus tokens.
- Added distinct danger foreground tokens so filled danger buttons retain contrast in dark mode.
- Replaced fixed light badge backgrounds with a surface-aware semantic value.
- Made shell-connected body classes honor both File 20 detection and the monotonic availability filter.
- Fixed compact cards without media so their body no longer collapses into an empty two-column layout.

### Still pending

- Exact native-module adoption of the new card/form/table catalog in Files 04, 06, 07, 10, 11, 12, 18, 20, and 21.
- File 24 runtime security/privacy/cache contract.
- Knowledge, media, reviews, research, Marketplace, and other optional profile providers.
- Fresh install, upgrade, migration, rollback, viewport and visual-regression evidence, Hostinger staging, real-user workflows, performance, release packaging, deployment, and monitoring.

## 0.4.0 — Unified Global Visual System Foundation

### Added

- The canonical File 25 identity: **Sabri Unified Global Visual Experience and Design System**.
- Global semantic visual tokens, reusable prefixed components, visual states, independent public design assets, and cross-module public integration functions.
- Nested Safe Mode boundaries and design-system Site Health diagnostics.

### Corrected while coding

- Removed stale naming that could create a duplicate File 26.
- Removed the profile-only inline File 20 token bridge.
- Allowed the identity-independent global visual system to load while profile dependencies remain fail-closed.

## 0.3.0 — Native File 21 Timeline Adapter

### Added

- A native read-only `file-21` provider over File 21's visibility-safe `Sabri\HomeNewsFeed\ProfileTimeline::query()` contract.
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

## 0.1.0 — Reviewed 25A/25B Foundation

### Added

- WordPress plugin bootstrap with an early PHP runtime guard, duplicate-copy collision protection, activation/deactivation boundaries, and non-destructive Safe Mode recovery.
- Graded dependency manager with File 00 as the authoritative identity dependency and truthful production-gap reporting for Files 03, 20, 21, and 24.
- Privacy-first Founder, verified-doctor, and permitted-member profile projection.
- Canonical Founder, Doctor, and Member profile routes with canonical redirects and unavailable-section rejection.
- Federated read-only timeline provider contract, bounded registry, global cross-provider pagination, canonical deduplication, and public-safe normalized items.
- Read-only WordPress posts compatibility provider used only when File 21 is absent.
- Public profile and timeline REST endpoints with no-store responses and internal-identifier redaction.
- Responsive profile hero, initials fallback, honest tabs and states, accessible sharing, scoped reduced motion, and File 20 token inheritance.
- Site Health diagnostics, reviewed contract tests, package/local-asset verification, and PHP 8.0/8.3 GitHub Actions CI.

### Corrected after review

- Closed filters that could elevate Founder/Doctor roles, widen visibility, restore denied contact fields, fabricate dependencies, or admit unapproved clinic states.
- Required approved adult identity and current professional evidence before a Doctor profile is publicly verified.
- Prevented active File 21 installations from being bypassed by raw WordPress post queries.
- Rejected private, draft, pending-review, wrong-author, provider-spoofed, externally canonicalized, password-protected, or malformed timeline items.
- Removed provider, native-object, WordPress-user, metrics, and diagnostic identifiers from public REST and timeline output.
- Repaired global timeline pagination, provider bounds, duplicate suppression, partial/truncated states, and accessible previous/next navigation.
- Prevented dead profile tabs, duplicate Overview/About bodies, malformed profile query contexts, alias-slug duplicates, unsafe media references, and stale cache exposure.
- Corrected profile schema entity types, dark-mode contrast, unscoped reduced-motion rules, avatar fallback, and share feedback.
