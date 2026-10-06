# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

WordPress module for the **Sabri Social Homeopathy Platform**. This is the existing File 25; no duplicate File 26 is created.

## Current corrective candidate

- Runtime: `0.15.0`
- Schema: `2`
- Fresh audit branch: `fix/reviews-615-634-fresh-cross-file-audit-20261006`
- Baseline main SHA reviewed: `19009a0934970d63ca60fda1fe9df1ac7288d42a`
- Baseline main exact-head CI: **PASS** — run `1540`
- Governing product plan: Master Plan `v3.0` / consolidated governing addenda
- Governing File 25 plan: Final Harmonized Specification `2.0`
- Source correction status: **Reviews 615–634 corrected; current candidate exact-head CI pending**
- Known unresolved File 25 source defects after the recorded corrections: **0**
- Known cross-file owner-contract limitations: **2** — File 08 profile→booking destination; File 17 target-bound Follow destination
- Hostinger staging accepted: **No**
- Production approved: **No**
- Live deployment verified: **No**

The fresh Reviews `615–634` audit rechecked File 25 against its own final plan, the central governing plan, and current companion repository source. It found three defect rounds: `621`, `623`, and `634`.

## Source implementation status

File 25 source-owned scope is complete after the fresh corrections, but source completion does not imply that every companion owner already publishes every desired cross-file action contract.

Machine-readable traceability:

```text
config/source-completion-matrix.json
config/all-chats-directive-matrix.json
config/review595-614-twenty-round-cross-file-completeness-ledger.json
config/review615-634-fresh-cross-file-audit-ledger.json
config/staging-dependencies.json
```

Current fresh audit record:

```text
docs/REVIEWS-615-634-FRESH-CROSS-FILE-AUDIT-2026-10-06.md
```

The **new candidate** remains pending its own exact-head CI until this branch is tested. The already-merged baseline main `19009a...` passed exact-head CI run `1540`. Neither fact establishes Hostinger staging, Founder acceptance, production approval, exact deployed-code parity, live database/schema state, live migration state, or operational acceptance.

## Current ownership and compatibility boundaries

- **File 00:** identity, membership, suspension and public-profile authorization remain owner-native; File 25 consumes bounded assertions and reads no File 00 tables.
- **File 03:** profile master/public projection, contact consent, report/edit/privacy destinations and canonical profile identity remain File 03-owned.
- **File 08:** current main `70541974ce0ffb16aebef557c3016eb7447662f4`, runtime `1.2.15`, schema `3.4.0`, public clinic projection `1.1.0`. It publishes the bounded clinic read projection and booking routes, but no reviewed API that maps a File 25 profile user directly to a clinic public reference/booking destination. **Appointment action remains hidden** until File 08 publishes such an owner contract.
- **File 09:** current main `9639f75ba046ac1a36e39d5e9aae56c7bae3279b`; Doctor verification truth remains File 09-owned.
- **File 17:** current main `8ae656e51796d1f05865d8be5dca2480443d79ca`; relationships and messaging remain File 17-owned. Current reviewed source does not expose a target-bound Follow destination contract for File 25, so **Follow remains hidden**. Message is rendered only when a concrete owner-approved internal message URL is present in File 03's public projection.
- **File 18:** seller/listing truth remains owner-native; File 25 consumes owner-executed public DTOs only.
- **File 20:** current main `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`; sole structural shell/navigation/layout owner.
- **File 21:** current main `f2eb7e95ddea327af36ea725ffb923b029f885e6`; canonical publication/ProfileTimeline owner.
- **File 22:** current main `b7a7f2e69411cbd32f0574fd12d766fb70c01b7a`; create/edit workflow owner.
- **File 23:** current main `dcae138e6073f4d0ff596623deb05b9940b8271b`; private publishing-operations owner.
- **File 24:** current main `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`; security/privacy/compliance/resilience assurance owner.
- **File 26:** current main `bbea3aad466792a4a6a62b53532bbd45c7c592de`; global search/discovery/ranking owner.
- **File 25:** public profile/timeline/card visual presentation, component visual contracts, responsive/accessibility refinement, SEO presentation and visual acceptance.

## Reviews 615–634 corrections

- Removed the unpublished File 08 profile-action hook assumption. File 25 no longer invents an Appointment destination; it hides the action until File 08 publishes an authoritative target-bound booking contract.
- Removed unpublished File 17 Follow/Message URL-hook assumptions. Follow remains hidden; Message uses only a concrete URL already supplied through the canonical public profile projection.
- Preserved File 03 report/edit/privacy, File 22 Composer and File 23 Publishing Dashboard owner destinations behind their current capability/contract checks.
- Refreshed source-completion, dependency and staging-test truth so prior branch/CI statements are not represented as current candidate evidence.
- Preserved every staging/live/production gate as false until actual environment evidence exists.

## Existing visual and public contracts retained

- File 20 remains the only structural shell owner.
- File 03 contact consent cannot be widened by File 25 filters.
- Founder spelling is immutable: `Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed`.
- Profile, cover, card, and OpenGraph media use strict same-origin validation.
- External avatar-service fallback is disabled; privacy-safe initials are used.
- Public profiles default to two columns; three columns require real owner-declared sidebar content.
- Visible/structured breadcrumbs, safe `og:image:alt`, and governed 404/410 handling remain active.
- Knowledge, Media, Reviews, Research, and Marketplace sections remain bounded, optional, read-only projections.
- Public REST and HTML share one public card allow list and do not disclose provider/native identifiers.
- Profile HTML and REST remain `no-store` until a versioned cache-partition contract is reviewed.

## Public integration API

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_acceptance_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_notice(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
sabri_visual_experience_render_welcome(array $args = []): string
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
php tools/verify-source-completion.php
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

- Exact Files 00/03/06/08/09/10/11/12/18/20/21/24/25 installation on canonical Hostinger staging.
- Real File 08 current `1.2.15` public/private/eligible/ineligible/suspended/revoked/empty clinic projections and File 25 rendering behavior.
- Real Founder, Doctor, Member, Patient, Student, minor, suspended, rejected, private, and wrong-author workflows.
- File 03 consent-on/consent-off, File 09 expiry/revocation, and File 18 owner-DTO tests.
- Urdu RTL, keyboard, screen reader, 200%/400% zoom, forced colors, reduced motion, target viewports, and visual regression evidence.
- Fresh install, upgrade, rollback, backup restore, performance, logs, monitoring, and Founder acceptance.
- PR review, controlled merge, release, live deployment, and post-deployment observation.
