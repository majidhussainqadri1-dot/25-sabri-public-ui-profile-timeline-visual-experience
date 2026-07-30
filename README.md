# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

Production-grade WordPress module for the **Sabri Social Homeopathy Platform**.

This is the same existing File 25. No File 26 is created for global visual design, responsive polish, profile timelines, or Facebook-familiar social presentation.

## Current implementation status

The Draft PR now contains:

- 25A — governance, ownership, and contracts;
- 25B — plugin foundation, Safe Mode, and diagnostics;
- 25C — File 20 shell/design-token integration;
- the first coding segment of 25D/25E — Founder and verified-Doctor profiles;
- 25F/25G — federated timeline registry and native read-only File 21 adapter;
- the first coding segment of 25H — global design-system and reusable visual-state foundation.

Implemented in `0.4.0`:

- canonical File 25 global visual-system identity and explicit no-File-26 contract;
- global `--sabri-visual-*` semantic tokens that inherit File 20 `--sabri-shell-*` values without duplicating the shell;
- prefixed `sabri-ui-*` containers, cards, buttons, badges, layouts, loading, empty, error, success, warning, unavailable, and skeleton states;
- global public CSS loaded independently from identity-dependent profile features;
- versioned cross-module design-system contract, public integration functions, and WordPress filters;
- visible keyboard focus, 44px-compatible targets, logical RTL/LTR properties, reduced motion, forced colors, responsive containers, and local/system fonts;
- removal of the former profile-only inline token bridge;
- nested Safe Mode boundaries that preserve outer fatal guards;
- early PHP guard, duplicate-copy protection, activation boundaries, and non-destructive recovery;
- File 00-authoritative identity, age, role, visibility, and professional-eligibility checks;
- privacy-first Founder, verified-Doctor, and permitted-member profile projection;
- canonical `/founder/`, `/doctors/{slug}/`, and `/profile/{slug}/` routes;
- structured Founder and verified-Doctor public sections with strict public-data allow lists;
- federated timeline validation, same-site canonical enforcement, deduplication, and global pagination;
- native read-only File 21 adapter over `Sabri\HomeNewsFeed\ProfileTimeline::query()`;
- public profile/timeline REST endpoints with no-store and identifier redaction;
- truthful Site Health, package, PHP 8.0/8.3, JavaScript, design-system, profile, timeline, adapter, and Safe Mode tests.

## Architecture boundary

- **File 00** remains authoritative for identity, approval, age, visibility, and professional eligibility.
- **File 03** remains the profile-master and contact-consent owner.
- **File 20** remains the sole global application-shell owner.
- **File 21** remains the Home, News, posts, comments, interactions, moderation, and social owner.
- **Files 22/23** own creation and private publishing operations.
- **File 24** remains the security, privacy, compliance, cache, incident, and resilience owner.
- **File 25** owns the global public design system, profile presentation, federated profile timeline, responsive refinement, accessibility, visual consistency, and visual acceptance.

File 25 does not duplicate shell markup, native content bodies, comments, reactions, identity evidence, patient information, messages, appointments, clinic records, or analytics ledgers.

## Public integration API

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_render_state(array $args = []): string
```

Canonical stylesheet handle:

```text
sabri-visual-design-system
```

## Review records

- [`docs/SECOND-REVIEW-AND-CORRECTION-2026-07-30.md`](docs/SECOND-REVIEW-AND-CORRECTION-2026-07-30.md)
- [`docs/DESIGN-SYSTEM-CONTRACT.md`](docs/DESIGN-SYSTEM-CONTRACT.md)
- [`docs/REVIEW-AND-CORRECTION-2026-07-30.md`](docs/REVIEW-AND-CORRECTION-2026-07-30.md)
- [`docs/PHASE-25C-25D-IMPLEMENTATION.md`](docs/PHASE-25C-25D-IMPLEMENTATION.md)
- [`docs/FILE21-TIMELINE-ADAPTER-IMPLEMENTATION.md`](docs/FILE21-TIMELINE-ADAPTER-IMPLEMENTATION.md)
- [`docs/GOVERNING-SCOPE.md`](docs/GOVERNING-SCOPE.md)
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/DECISION-LOG.md`](docs/DECISION-LOG.md)

The Founder-approved Microsoft Word specification remains the full project-governance source for File 25.

## Quality commands

```bash
composer validate --strict --no-check-publish
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
php tests/run.php
php tests/profile-data.php
php tests/file21-provider.php
php tests/design-system.php
php tests/safe-mode.php
php tools/verify-structure.php
node --check assets/js/public.js
```

## Known production gaps

- Exact Files 00/03/20/21/25 staging is required before File 21 maturity promotion.
- File 24 currently has no runtime security/privacy/cache contract available to File 25.
- Knowledge, Media, Reviews, Research, Marketplace, and other optional providers remain later phases.
- Cross-module visual adoption, viewport screenshots, Urdu RTL, keyboard, screen reader, zoom, forced colors, reduced motion, performance, fresh install, upgrade, migration, rollback, backup restoration, Hostinger staging, release packaging, deployment, and monitoring remain mandatory.

## Release policy

Code presence, a ZIP, or green CI is not production completion. File 25 remains Draft until every staged Definition-of-Done gate is evidenced and accepted.
