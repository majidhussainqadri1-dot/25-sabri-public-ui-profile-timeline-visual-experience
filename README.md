# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

Production-grade WordPress module for the **Sabri Social Homeopathy Platform**. This is the same File 25; no duplicate File 26 is created.

## Current source scope

The Draft PR contains governance and safety, File 20 shell-token integration, Founder/Doctor profile foundations, the federated File 21 timeline, the reusable global visual system, governed optional profile sections, native File 06 Knowledge, File 10/11/12 Media, and File 18 Marketplace projections, artifact-integrity visual acceptance, deterministic staging release engineering, and installed-candidate preflight.

Implemented in `0.11.0`:

- canonical File 25 visual ownership while File 20 remains the only application-shell owner;
- reusable `sabri-ui-*` components, states, notices, forms, tables, and content cards;
- strict same-origin links and deterministic UTC dates;
- bounded optional sections limited to Knowledge, Media, Reviews, Research, and Marketplace;
- immutable provider metadata and concrete-object identity;
- native read-only File 06, File 10, File 11, File 12, File 18, and File 21 projections;
- strict public-status, owner, password, version-range, privacy, and no-write boundaries;
- exact route parity between `Profile_Router` and every approved provider section;
- schema-2 one-time rewrite migration with bounded locking and stale-lock recovery;
- exact-commit visual evidence with governed artifact paths, SHA-256, byte size, media type, timestamp, and reviewer;
- deterministic staging ZIP generation with embedded/detached manifests and checksum;
- independent downloaded-artifact verification;
- read-only installed-candidate verification against `STAGING-MANIFEST.json`;
- exact Hostinger staging-host, HTTPS, registration-disabled, noindex, Safe Mode, dependency, expected-commit, and package-integrity gates;
- privacy-safe WP-CLI preflight: `wp sabri file25 staging-probe --expected-commit=<sha>`;
- machine-readable `config/staging-test-plan.json` covering routes, privacy negatives, RTL, accessibility, rollback, performance, and Founder acceptance;
- PHP 8.0/8.3 and JavaScript CI.

## Architecture boundary

- **File 00:** identity, approval, age, visibility, professional eligibility.
- **File 03:** profile master data and contact consent.
- **File 20:** global shell, navigation, sidebars, layout resolver, shell tokens.
- **File 21:** Home, News, posts, comments, interactions, moderation, social behavior.
- **Files 22/23:** creation and private publishing operations.
- **File 24:** security, privacy, compliance, cache, incidents, resilience.
- **Files 06/10/11/12/18 and later native modules:** canonical content, permissions, moderation, metrics, files, transactions, and workflows.
- **File 25:** public visual projection, profile navigation, reusable components, responsive refinement, accessibility, visual acceptance, and staging-candidate packaging.

File 25 stores no duplicate publication bodies, reactions, comments, patient information, identity evidence, native metrics, seller contacts, offers, or transactions.

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

## Staging package

```bash
php tools/build-staging-package.php \
  --output-dir=build/staging-package \
  --commit=<40-character-commit-sha> \
  --source-date-epoch=<commit-unix-timestamp>
```

Independent verification:

```bash
php tools/verify-staging-artifact.php \
  --artifact=/path/to/downloaded-workflow-artifact.zip \
  --artifact-sha256=<expected-outer-sha256>
```

Outputs use the current semantic version and contain the embedded `STAGING-MANIFEST.json`. The matrix at `config/staging-dependencies.json` does not imply staging or production acceptance.

## Hostinger staging preflight

After installing the exact candidate on `sabrisocialstaging.sabrihomeopathy.com`, keep registration and WordPress search indexing disabled, then run:

```bash
wp sabri file25 staging-probe \
  --expected-commit=<40-character-candidate-commit>
```

The command verifies the installed file set, embedded manifest, SHA-256 hashes, sizes, commit, canonical staging host, HTTPS, privacy settings, Safe Mode, and dependencies. Exit code `2` means a fail-closed gate remains incomplete. Passing preflight only permits manual staging tests.

The governed scenario list is `config/staging-test-plan.json`.

## Review and contract records

- `docs/NINTH-REVIEW-ROUTE-PARITY-AND-HOSTINGER-PREFLIGHT-2026-07-31.md`
- `docs/HOSTINGER-STAGING-PROBE-AND-RUNBOOK.md`
- `docs/EIGHTH-REVIEW-AND-INDEPENDENT-ARTIFACT-VERIFICATION-2026-07-31.md`
- `docs/SEVENTH-REVIEW-AND-STAGING-PACKAGE-2026-07-31.md`
- `docs/STAGING-PACKAGE-CONTRACT.md`
- `docs/SIXTH-REVIEW-AND-MARKETPLACE-2026-07-31.md`
- `docs/FILE18-MARKETPLACE-ADAPTER-IMPLEMENTATION.md`
- `docs/OPTIONAL-PROFILE-SECTIONS-CONTRACT.md`
- `docs/CONTENT-CARD-CONTRACT.md`
- `docs/DESIGN-SYSTEM-CONTRACT.md`
- `docs/ARCHITECTURE.md`
- `docs/GOVERNING-SCOPE.md`

## Quality commands

```bash
composer validate --strict --no-check-publish
composer test
node --check assets/js/public.js
php tools/verify-staging-artifact.php --artifact=/path/to/downloaded-workflow-artifact.zip --artifact-sha256=<expected-outer-sha256>
```

## Remaining acceptance gates

- Exact Files 00/03/06/10/11/12/18/20/21/25 Hostinger staging.
- Accepted File 24 runtime security/privacy/cache contract.
- Native Reviews and Research adapters after exact source review.
- Actual viewport, Urdu RTL, accessibility, and performance evidence.
- Fresh install, upgrade, rollback, backup restoration, real-user workflows, deployment, monitoring, and Founder acceptance.

Code presence, a ZIP, artifact verification, installed preflight, or green CI is not production completion.
