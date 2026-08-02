# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

WordPress module for the **Sabri Social Homeopathy Platform**. This is the existing File 25; no duplicate File 26 is created.

## Current corrective candidate

- Runtime: `0.14.0`
- Schema: `2`
- Branch: `feature/25a-25b-foundation`
- Pull request: Draft PR #1
- Governing product plan: Master Plan `v3.0`
- Governing shell plan: File 20 `v4.1`
- Hostinger staging accepted: **No**
- Production approved: **No**
- Live deployment authorized: **No**

Version `0.14.0` corrects the authoritative-contract drift identified after the earlier `0.13.0` source review.

## Corrected ownership boundaries

- **File 00 `1.2.4`, contract `1.1.2`:** identity, membership state, Founder identity, suspension, eligibility, guardian policy, professional eligibility, and public-profile authorization.
- **File 03 `0.2.0`:** profile master data, same-origin profile media, and explicit public-contact consent.
- **File 08 `0.2.0`:** clinic, availability, and appointment truth. File 25 consumes only public clinic projection contract `1.0.0`; until that owner contract exists, clinic projection fails closed.
- **File 09 `1.1.0`:** Doctor verification decision and immutable approved professional snapshot.
- **File 18 `1.2.0-RC1`:** seller, listing, moderation, contact, offer, metric, and direct-deal truth. File 25 consumes owner-executed public DTOs only.
- **File 20 `1.2.0`:** sole global shell, route, navigation, sidebar, drawer, and structural layout owner.
- **File 21:** publication, Home/News, interaction, moderation, and timeline truth.
- **File 24 `>=0.25.3 <0.26.0`:** security, privacy, compliance, incident, audit-evidence, and resilience governance.
- **File 25:** visual tokens, public visual projection, profiles, federated timelines, reusable components, responsive refinement, accessibility, SEO presentation, visual acceptance, and deterministic staging packaging.

## Version 0.14.0 corrections

- Replaced stale File 00 assumptions with exact `SMC_Contracts::assertions()` contract `1.1.2`.
- Removed File 25 reads from foreign File 00 professional-credential and clinic tables.
- Removed locally derived Doctor verification, qualification-role inference, and license-expiry authority.
- Required both File 00 eligibility/can-practice assertions and File 09 current verification decision.
- Projected professional presentation only from File 09's immutable approved snapshot.
- Removed File 25 age calculation; explicit File 00 minor/guardian assertions govern, while unknown ordinary contact state fails closed.
- Added a versioned File 08 clinic projection boundary and explicit staging blocker.
- Replaced File 18 direct SQL with owner APIs: future `smp_get_public_profile_listings()` or the bounded `SMP_Utils::current_seller()` + `SMP_REST::products()` transitional path.
- Updated dependency matrix, staging scenarios, Composer tests, CI gates, release-engineering tests, and structural verification.

## Existing visual and public contracts retained

- File 20 remains the only structural shell owner.
- File 03 contact consent cannot be widened by File 25 filters.
- Founder spelling is immutable: `Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed`.
- Profile, cover, card, and OpenGraph media use strict same-origin validation.
- External avatar-service fallback is disabled; privacy-safe initials are used.
- Public profiles default to two columns; three columns require real owner-declared sidebar content.
- Visible/structured breadcrumbs, safe `og:image:alt`, and governed 404/410 handling remain active.
- Knowledge, Media, Reviews, Research, and Marketplace sections remain bounded, optional, read-only projections.
- Public REST and HTML share one public card allow-list and do not disclose provider/native identifiers.
- Profile HTML and REST remain `no-store` until a versioned cache-partition contract is reviewed.

## Public integration API

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_acceptance_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_notice(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
```

Public read routes include:

```text
GET /wp-json/sabri-public/v1/founder
GET /wp-json/sabri-public/v1/founder/timeline
GET /wp-json/sabri-public/v1/founder/knowledge
GET /wp-json/sabri-public/v1/founder/media
GET /wp-json/sabri-public/v1/profiles/{public-slug}
GET /wp-json/sabri-public/v1/profiles/{public-slug}/timeline
GET /wp-json/sabri-public/v1/profiles/{public-slug}/knowledge
GET /wp-json/sabri-public/v1/profiles/{public-slug}/media
GET /wp-json/sabri-public/v1/providers/health
```

## Quality commands

```bash
composer validate --strict --no-check-publish
composer test
node --check assets/js/public.js
php tools/verify-structure.php
```

Deterministic staging package:

```bash
php tools/build-staging-package.php \
  --output-dir=build/staging-package \
  --commit=<40-character-commit-sha> \
  --source-date-epoch=<commit-unix-timestamp>
```

Installed staging preflight:

```bash
wp sabri file25 staging-probe --expected-commit=<40-character-candidate-commit>
```

A passing source suite, package verifier, or installed preflight authorizes manual staging evaluation only. It is not merge, staging acceptance, production approval, or live-deployment evidence.

## Remaining mandatory gates

- File 08 versioned public clinic projection implementation and review.
- Exact Files 00/03/06/08/09/10/11/12/18/20/21/24/25 installation on canonical Hostinger staging.
- Real Founder, Doctor, Member, Patient, Student, minor, suspended, rejected, private, and wrong-author workflows.
- File 03 consent-on/consent-off, File 09 expiry/revocation, and File 18 owner-DTO tests.
- Urdu RTL, keyboard, screen reader, 200%/400% zoom, forced colors, reduced motion, target viewports, and visual regression evidence.
- Fresh install, upgrade, rollback, backup restore, performance, logs, monitoring, and Founder acceptance.
- PR review, controlled merge, release, live deployment, and post-deployment observation.
