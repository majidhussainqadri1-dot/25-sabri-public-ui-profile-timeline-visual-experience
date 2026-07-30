# Changelog

## 0.6.0 — Governed Optional Profile Sections

### Added

- A read-only `Profile_Section_Provider` contract for native Knowledge, Media, Reviews, Research, and Marketplace modules.
- A bounded `Section_Registry` with unique validated provider IDs, semantic versions, approved sections, approved maturity levels, and explicit denial of native-content ownership.
- A `Section_Service` that revalidates provider state, bounds candidates, suppresses duplicates, isolates failures, and renders descriptors only through File 25 content cards.
- Content-backed profile navigation: optional tabs appear only when an accepted provider returns at least one valid approved public card.
- A canonical optional-section template with honest partial, truncated, and empty states.
- Optional-section Site Health diagnostics, PHP 8.0/8.3 tests, package rules, and governance documentation.
- A public `sabri_visual_experience_render_notice()` helper and a completed reusable component class map.
- A canonical component-completion stylesheet included through the public `sabri-visual-design-system` handle.

### Corrected during fourth review

- Prevented optional-section labels from becoming dead tabs before a governed provider and approved public content exist.
- Prevented arbitrary raw provider HTML from entering public profiles.
- Revalidated mutable provider maturity and native-ownership claims at query time rather than trusting registration-time state forever.
- Isolated provider exceptions and exposed only bounded public status, never provider identifiers or diagnostics.
- Made date-only and offset-less public-card timestamps deterministic in UTC and rejected invalid calendar dates.
- Completed notice title/message, disabled-control, placeholder, table-caption, focus-within, forced-colors, and print presentation.

### Still pending

- Exact native adapters for Knowledge, Media, Reviews, Research, and Marketplace after source-contract review.
- Exact Files 00/03/20/21/25 staging and File 24 runtime integration.
- Visual-regression, RTL, accessibility, performance, migration, rollback, deployment, and Founder acceptance evidence.

## 0.5.0 — Reusable Content Cards and URL-Security Correction

### Added

- A strict `Public_URL::sanitize_same_site()` policy for reusable public visual components.
- A presentation-only `Content_Cards` renderer and versioned contract for article, post, news, video, reel, book, PDF, Doctor, clinic, event, and Marketplace visual variants.
- The public `sabri_visual_experience_render_card()` integration function.
- Bounded title, excerpt, eyebrow, badge, metadata, date, media, and action-field allow lists that ignore native IDs and unknown private fields.
- Single-link keyboard behavior, local lazy media, semantic markup, responsive cards, RTL, forced-colors, reduced-motion, and print behavior.
- Reusable notice, form-field, input, select, textarea, help/error, and responsive-table CSS primitives.
- Contrast-safe `on-primary`, `on-danger`, and surface-aware soft-primary semantic tokens.

### Corrected while reviewing 0.4.0

- Closed protocol-relative, cross-origin, credential-bearing, downgrade, port-mismatch, control-character, backslash, and malformed URL paths.
- Converted variable default-copy translation calls into literal WordPress translation strings.
- Stopped File 25 dark-mode CSS from replacing File 20-owned theme tokens.
- Corrected danger-button contrast, badge theming, shell detection, and compact cards without media.

## 0.4.0 — Unified Global Visual System Foundation

- Added the canonical File 25 identity, global semantic tokens, reusable prefixed components, visual states, independent public design assets, nested Safe Mode, and design-system diagnostics.
- Removed stale naming, the profile-only inline token bridge, and identity coupling from the global visual layer.

## 0.3.0 — Native File 21 Timeline Adapter

- Added the native read-only `file-21` provider, bounded paging, normalization, health evidence, identifier redaction, and no-write tests.
- Corrected native-page overlap, provider precedence, active File 21 bypass, and premature maturity promotion.

## 0.2.0 — File 20 Integration and Founder/Doctor Profiles

- Added File 20 shell-contract integration, canonical author links, structured Founder and verified-Doctor profiles, strict public allow lists, responsive sections, metadata, and profile tests.
- Corrected Founder naming, dead tabs, native ownership, filter elevation, and cache exposure.

## 0.1.0 — Reviewed Foundation

- Added bootstrap, Safe Mode, dependency diagnostics, privacy-first profiles, canonical routes, federated timeline contracts, REST foundations, responsive profile presentation, and CI.
- Corrected identity elevation, visibility widening, contact restoration, File 21 bypass, private timeline admission, identifier leakage, pagination, dead tabs, cache exposure, schema, contrast, motion, avatar, and sharing defects.
