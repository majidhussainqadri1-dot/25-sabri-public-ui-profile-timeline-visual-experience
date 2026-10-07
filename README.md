# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

WordPress module for the **Sabri Social Homeopathy Platform**. This is the existing File 25; no duplicate File 26 is created.

## Current corrective candidate

- Runtime: `0.15.0`
- Schema: `2`
- Fresh audit branch: `fix/reviews-655-674-admin-plan-completeness-20261007`
- Baseline main SHA reviewed: `2d02c93356b050313e30e29aeceb57080771c2a5`
- Baseline main exact-head CI: **PASS** — run `1638`
- Governing product plan: Master Plan `v3.0` / consolidated governing addenda
- Governing File 25 plan: Final Harmonized Specification `2.0`
- Source correction status: **Reviews 655–674 corrected**
- Known unresolved File 25 source defects after the recorded corrections: **0**
- Known cross-file owner-contract limitations: **2** — File 08 profile→booking destination; File 17 target-bound Follow destination
- Hostinger staging accepted: **No**
- Production approved: **No**
- Live deployment verified: **No**

Reviews `655–674` performed a new plan-to-code and cross-file audit focused on administrator configuration truth, timeline defaults, optional provider enablement, Completion Assistant evidence and current owner boundaries. Six defect rounds were corrected: `657`, `658`, `659`, `660`, `662`, and `673`.

## Source implementation status

File 25 source-owned scope is complete after the recorded corrections. Mutable exact-head CI state is intentionally **not frozen as a permanent truth inside source metadata**: the exact current GitHub HEAD must be checked against its current CI run after the final commit. Repository/CI evidence never proves installed Hostinger staging or live deployment.

Machine-readable traceability:

```text
config/source-completion-matrix.json
config/all-chats-directive-matrix.json
config/review595-614-twenty-round-cross-file-completeness-ledger.json
config/review615-634-fresh-cross-file-audit-ledger.json
config/review635-654-plan-completeness-ledger.json
config/review655-674-admin-plan-completeness-ledger.json
config/staging-dependencies.json
```

Current fresh audit record:

```text
docs/REVIEWS-655-674-ADMIN-PLAN-COMPLETENESS-2026-10-07.md
```

The audited baseline main `2d02c933...` passed exact-head CI run `1638`. The final current-head CI status for any later corrective commit is external evidence and must be queried from GitHub; it is not self-certified by this README. Hostinger staging, Founder acceptance, production approval, exact deployed-code parity, live database/schema state, live migration state and operational acceptance remain separate gates.

## Current ownership and compatibility boundaries

- **File 00:** identity, membership, suspension and public-profile authorization remain owner-native; File 25 consumes bounded assertions and reads no File 00 tables.
- **File 03:** profile master/public projection, contact consent, canonical public media, report/edit/privacy destinations and canonical profile identity remain File 03-owned.
- **File 08:** current main `70541974ce0ffb16aebef557c3016eb7447662f4`, runtime `1.2.15`, schema `3.4.0`, public clinic projection `1.1.0`. No reviewed profile-user→booking-destination API is published, so **Appointment remains hidden**.
- **File 09:** current main `448d41f34586369ca5875693583b9cd8a6133167`, runtime `1.3.0`, schema `6`, integration contract `1.1.0`; Doctor-verification truth remains File 09-owned.
- **File 14:** current main `f64e7d17268daff4e3097c18ad510116e6eaf105`, runtime `1.4.6`, schema `10005`; File 14 consumes File 25 visual contracts while clinic/directory/verification destination truth remains with native owners.
- **File 17:** current main `8ae656e51796d1f05865d8be5dca2480443d79ca`; relationships and messaging remain File 17-owned. **Follow remains hidden** without a target-bound owner contract. Message appears only when File 03's public projection supplies a concrete same-site owner-approved destination.
- **File 20:** current main `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`; sole structural shell/navigation/layout and canonical admin-parent owner.
- **File 21:** current main `f2eb7e95ddea327af36ea725ffb923b029f885e6`; canonical publication/ProfileTimeline owner.
- **File 22:** current main `b7a7f2e69411cbd32f0574fd12d766fb70c01b7a`; create/edit workflow owner.
- **File 23:** current main `dcae138e6073f4d0ff596623deb05b9940b8271b`; private publishing-operations owner.
- **File 24:** current main `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`; security/privacy/compliance/resilience assurance owner.
- **File 26:** current main `bbea3aad466792a4a6a62b53532bbd45c7c592de`; global search/discovery/ranking owner.
- **File 25:** public profile/timeline/card visual presentation, component visual contracts, responsive/accessibility refinement, profile-local filtering/search, SEO presentation and visual acceptance.

## Reviews 655–674 corrections

- Removed five misleading no-op preferences: `timeline_provider_mode`, `accessibility_mode`, `cache_ttl`, `safe_mode_controls`, and `diagnostics_enabled`. Mandatory accessibility, Safe Mode and diagnostics cannot be disabled by presentation preferences; profile responses remain no-store until an accepted cache-partition contract exists.
- Grouped the administrator control center under the governed File 25 sections instead of one undifferentiated settings table.
- Removed the unsupported `tablet` responsive-preview preference; every selectable value now maps to a real governed preview mode.
- Stopped fabricating avatar alt evidence before the Completion Assistant, so missing canonical File 03 alt metadata can be reported while public rendering still has an accessible descriptive fallback.
- Added bounded `default_timeline_filter`, applied only when the key exists in the active approved filter registry.
- Added bounded `enabled_optional_providers`; the five built-in File 06/10/11/12/18 section providers can be individually disabled or all disabled without affecting required File 21 timeline ownership.
- Reconciled source/dependency/release documentation and preserved every staging/live/production gate as unverified until actual environment evidence exists.

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
