# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

Production-grade WordPress module for the **Sabri Social Homeopathy Platform**. This is the same File 25; no duplicate File 26 is created.

## Current source scope

The Draft PR contains governance and safety, File 20 shell-token integration, Founder/Doctor profile foundations, the federated File 21 timeline, reusable global visual components, governed optional profile sections, native File 06 Knowledge, File 10/11/12 Media, File 18 Marketplace projections, exact File 24 integration, artifact-integrity visual acceptance, deterministic staging release engineering, and installed-candidate preflight.

Implemented in `0.12.0`:

- canonical File 25 visual ownership while File 20 remains the only application-shell owner;
- File 24 remains the security, privacy, audit, incident, compliance, and resilience owner;
- reusable `sabri-ui-*` components, states, notices, forms, tables, and content cards;
- strict same-origin links and deterministic UTC dates;
- bounded Knowledge, Media, Reviews, Research, and Marketplace profile sections;
- immutable provider metadata and read-only native projections;
- exact route parity and schema-2 rewrite migration;
- deterministic staging packages and independently verifiable manifests/checksums;
- read-only installed-candidate verification and exact Hostinger staging preflight;
- exact File 24 constant `SPCRC_VERSION` and reviewed source range `>=0.25.3 <0.26.0`;
- bounded File 25 manifest through File 24's `spcrc/module_manifests` contract;
- advisory `elevated-monitoring` request when File 25 enters Safe Mode;
- profile HTML and REST responses kept `no-store` pending a later versioned cache-partition contract;
- Site Health and CI coverage for File 24 compatibility and ownership boundaries;
- PHP 8.0/8.3 and JavaScript CI.

## Architecture boundary

- **File 00:** identity, approval, age, visibility, and professional eligibility.
- **File 03:** profile master data and public-contact consent.
- **File 20:** global shell, navigation, sidebars, layout resolver, and shell tokens.
- **File 21:** Home, News, posts, comments, interactions, moderation, and social behavior.
- **File 24:** security governance, privacy orchestration, audit evidence, incidents, controls, findings, compliance, resilience, and future cache governance.
- **Files 06/10/11/12/18:** canonical content, permissions, moderation, files, metrics, and transactions.
- **File 25:** public visual projection, profile navigation, reusable components, responsive refinement, accessibility, visual acceptance, and staging-candidate packaging.

File 25 does not create incidents, write File 24 audit records, process privacy exports/erasures, manage findings, accept risks, or claim native-content ownership.

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

File 25 registers its bounded File 24 manifest through:

```php
add_filter('spcrc/module_manifests', ...);
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

## Hostinger staging preflight

After installing the exact candidate on `sabrisocialstaging.sabrihomeopathy.com`, keep registration and WordPress search indexing disabled, then run:

```bash
wp sabri file25 staging-probe \
  --expected-commit=<40-character-candidate-commit>
```

Passing preflight permits manual testing only; it is not staging or production acceptance.

## Review and contract records

- `docs/TENTH-REVIEW-FILE24-INTEGRATION-2026-07-31.md`
- `docs/NINTH-REVIEW-ROUTE-PARITY-AND-HOSTINGER-PREFLIGHT-2026-07-31.md`
- `docs/HOSTINGER-STAGING-PROBE-AND-RUNBOOK.md`
- `docs/EIGHTH-REVIEW-AND-INDEPENDENT-ARTIFACT-VERIFICATION-2026-07-31.md`
- `docs/SEVENTH-REVIEW-AND-STAGING-PACKAGE-2026-07-31.md`
- `docs/STAGING-PACKAGE-CONTRACT.md`
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

- Exact Files 00/03/06/10/11/12/18/20/21/24/25 installation on canonical Hostinger staging.
- Exact File 24 + File 25 runtime workflows and later versioned cache-partition acceptance.
- Native Reviews and Research adapters after exact source review.
- Actual viewport, Urdu RTL, accessibility, and performance evidence.
- Fresh install, upgrade, rollback, backup restoration, real-user workflows, deployment, monitoring, and Founder acceptance.

Code presence, source compatibility, a ZIP, artifact verification, installed preflight, or green CI is not production completion.
