# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

Production-grade WordPress module for the **Sabri Social Homeopathy Platform**. This is the same File 25; no duplicate File 26 is created.

## Current source scope

The Draft PR contains governance and safety, File 20 shell-token integration, Founder/Doctor profile foundations, the federated File 21 timeline, the reusable global visual system, governed optional profile sections, native File 06 Knowledge projection, native File 10/11/12 Media projections, and the visual-acceptance evidence contract.

Implemented in `0.8.0`:

- canonical File 25 visual ownership while File 20 remains the only application-shell owner;
- reusable `sabri-ui-*` components, states, notices, forms, tables, and content cards;
- strict same-origin links and deterministic UTC dates;
- bounded optional sections limited to Knowledge, Media, Reviews, Research, and Marketplace;
- immutable registered provider ID, version, section, maturity, and ownership metadata;
- truthful provider consistency-error reporting and canonical-destination deduplication;
- native read-only File 06 Homeopathy Encyclopedia adapter;
- native read-only File 10 Video Wall adapter excluding Reels;
- native read-only File 11 Reels adapter enforcing the approved 60–600 second duration;
- native read-only File 12 PDF Library adapter;
- public-status, author, password, post-type, version-range, and no-write revalidation;
- strict visual-evidence records with artifact references, SHA-256 hashes, timestamps, reviewers, staging runtime data, commit binding, and Founder sign-off;
- PHP 8.0/8.3 and JavaScript CI.

## Architecture boundary

- **File 00:** identity, approval, age, visibility, professional eligibility.
- **File 03:** profile master data and contact consent.
- **File 20:** global shell, navigation, sidebars, layout resolver, shell tokens.
- **File 21:** Home, News, posts, comments, interactions, moderation, social behavior.
- **Files 22/23:** creation and private publishing operations.
- **File 24:** security, privacy, compliance, cache, incidents, resilience.
- **Files 06/10/11/12 and later native modules:** canonical content, permissions, moderation, metrics, and workflows.
- **File 25:** public visual projection, profile navigation, reusable components, responsive refinement, accessibility, and visual acceptance.

File 25 stores no duplicate publication bodies, reactions, comments, patient information, identity evidence, native metrics, or transactions.

## Public integration API

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_acceptance_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_notice(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
```

Optional native modules register read-only providers through:

```php
do_action('sabri_public_experience/register_section_providers', $registry);
```

Canonical stylesheet handle: `sabri-visual-design-system`.

## Review and contract records

- `docs/FIFTH-REVIEW-AND-MEDIA-ADAPTERS-2026-07-31.md`
- `docs/OPTIONAL-PROFILE-SECTIONS-CONTRACT.md`
- `docs/CONTENT-CARD-CONTRACT.md`
- `docs/DESIGN-SYSTEM-CONTRACT.md`
- `docs/ARCHITECTURE.md`
- `docs/GOVERNING-SCOPE.md`
- `docs/DECISION-LOG.md`

The Founder-approved Microsoft Word specification remains the full governance source.

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
php tests/section-provider-metadata.php
php tests/file06-knowledge-provider.php
php tests/file10-12-media-providers.php
php tests/visual-acceptance.php
php tests/safe-mode.php
php tools/verify-structure.php
php tools/verify-file06-and-components.php
node --check assets/js/public.js
```

## Remaining acceptance gates

- Exact Files 00/03/06/10/11/12/20/21/25 staging.
- Accepted File 24 runtime security/privacy/cache contract.
- Native Reviews, Research, and Marketplace adapters after exact source review.
- Actual viewport screenshots and visual-regression baselines.
- Urdu RTL, keyboard, screen-reader, zoom, forced-colors, reduced-motion, and performance evidence.
- Fresh install, upgrade, migration, rollback, backup restoration, real-user workflows, release packaging, deployment, monitoring, and Founder acceptance.

Code presence, a ZIP, or green CI is not production completion.
