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
- the continuing 25H segment — global design system, reusable states, notices, content cards, forms, and tables;
- the first 25I foundation — governed optional Knowledge, Media, Reviews, Research, and Marketplace profile sections.

Implemented in `0.6.0`:

- canonical File 25 global visual-system identity and explicit no-File-26 contract;
- global `--sabri-visual-*` semantic tokens inheriting File 20 without duplicating its shell;
- contrast-safe semantic derivatives and a canonical public stylesheet handle composed from core and component-completion layers;
- prefixed `sabri-ui-*` containers, layouts, cards, buttons, badges, notices, form controls, tables, visual states, and skeletons;
- escaped same-origin public helpers for visual states, notices, and content cards;
- presentation-only cards for article, post, news, video, reel, book, PDF, Doctor, clinic, event, and Marketplace variants;
- strict same-origin URL enforcement and deterministic UTC public-card dates;
- a bounded optional profile-section provider interface, registry, service, and template;
- approved optional sections limited to Knowledge, Media, Reviews, Research, and Marketplace;
- content-backed tab visibility: no accepted public card means no tab;
- provider ID/version/section/maturity validation, runtime revalidation, native-ownership denial, exception isolation, deduplication, and bounded retrieval;
- nested Safe Mode, truthful Site Health diagnostics, and no native data writes;
- privacy-first Founder, verified-Doctor, and permitted-member profile routes and projections;
- federated timeline validation and a native read-only File 21 adapter;
- public profile/timeline REST endpoints with `no-store` and identifier redaction;
- PHP 8.0/8.3, JavaScript, package, design-system, URL, card, optional-section, profile, timeline, adapter, and Safe Mode tests.

## Architecture boundary

- **File 00** owns identity, approval, age, visibility, and professional eligibility.
- **File 03** owns profile master data and contact consent.
- **File 20** remains the sole global application-shell owner.
- **File 21** owns Home, News, posts, comments, interactions, moderation, and social behavior.
- **Files 22/23** own creation and private publishing operations.
- **File 24** owns security, privacy, compliance, cache, incidents, and resilience.
- **Native optional modules** own Knowledge, Media, Reviews, Research, Marketplace records, permissions, workflows, metrics, and transactions.
- **File 25** owns public visual projection, profile navigation, reusable components, responsive refinement, accessibility, visual consistency, and visual acceptance.

File 25 does not duplicate shell markup, native content bodies, comments, reactions, identity evidence, patient information, messages, appointments, clinic records, Marketplace transactions, or analytics ledgers.

## Public integration API

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_notice(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
```

Optional native modules register read-only profile-section providers through:

```php
do_action('sabri_public_experience/register_section_providers', $registry);
```

Canonical stylesheet handle:

```text
sabri-visual-design-system
```

## Review and contract records

- [`docs/FOURTH-REVIEW-AND-CORRECTION-2026-07-31.md`](docs/FOURTH-REVIEW-AND-CORRECTION-2026-07-31.md)
- [`docs/OPTIONAL-PROFILE-SECTIONS-CONTRACT.md`](docs/OPTIONAL-PROFILE-SECTIONS-CONTRACT.md)
- [`docs/THIRD-REVIEW-AND-CORRECTION-2026-07-30.md`](docs/THIRD-REVIEW-AND-CORRECTION-2026-07-30.md)
- [`docs/CONTENT-CARD-CONTRACT.md`](docs/CONTENT-CARD-CONTRACT.md)
- [`docs/DESIGN-SYSTEM-CONTRACT.md`](docs/DESIGN-SYSTEM-CONTRACT.md)
- [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md)
- [`docs/GOVERNING-SCOPE.md`](docs/GOVERNING-SCOPE.md)
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
php tests/content-cards.php
php tests/section-providers.php
php tests/safe-mode.php
php tools/verify-structure.php
node --check assets/js/public.js
```

## Known production gaps

- Exact Files 00/03/20/21/25 staging is required before File 21 maturity promotion.
- File 24 has no accepted runtime security/privacy/cache contract available to File 25 yet.
- Native Knowledge, Media, Reviews, Research, and Marketplace adapters still require exact source-contract review and staging.
- Visual-regression baselines, viewport screenshots, Urdu RTL, keyboard, screen reader, zoom, forced colors, reduced motion, performance, fresh install, upgrade, migration, rollback, backup restoration, Hostinger staging, release packaging, deployment, and monitoring remain mandatory.

## Release policy

Code presence, a ZIP, or green CI is not production completion. File 25 remains Draft until every staged Definition-of-Done gate is evidenced and accepted.
