# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

WordPress module for the **Sabri Social Homeopathy Platform**. This is the existing File 25; no duplicate File 26 is created.

## Current corrective candidate

- Runtime: `0.15.0`
- Schema: `2`
- Fresh audit branch: `fix/reviews-675-694-portfolio-preview-media-20261008`
- Baseline main SHA reviewed: `e35563b7f3d8ebf0acbbc80982b7bcf2e1b78c0a`
- Baseline main exact-head CI: **PASS** — run `1689`
- Governing product plan: Master Plan `v3.0` / consolidated governing addenda
- Governing File 25 plan: Final Harmonized Specification `2.0`
- Source correction status: **Reviews 675–694 corrected**
- Known unresolved File 25 source defects after the recorded corrections: **0**
- Known cross-file owner-contract limitations: **2** — File 08 profile→booking destination; File 17 target-bound Follow destination
- Hostinger staging accepted: **No**
- Production approved: **No**
- Live deployment verified: **No**

Reviews `675–694` performed a fresh plan-to-code and current-companion audit focused on File 03 cover-media fidelity, Profile Hero completeness, current central RTL preview, §39–47 portfolio Timeline coverage, §46 provider completeness and §43 audited pinning. Eight defect rounds were corrected: `677`, `678`, `679`, `680`, `681`, `682`, `683`, and `693`.

## Source implementation status

File 25 source-owned scope is complete after the recorded corrections. Mutable exact-head CI is intentionally **not frozen as permanent source truth**: the exact current GitHub HEAD must be checked against its current CI run after the final commit. Repository/CI evidence never proves installed Hostinger staging or live deployment.

Machine-readable traceability:

```text
config/source-completion-matrix.json
config/all-chats-directive-matrix.json
config/review595-614-twenty-round-cross-file-completeness-ledger.json
config/review615-634-fresh-cross-file-audit-ledger.json
config/review635-654-plan-completeness-ledger.json
config/review655-674-admin-plan-completeness-ledger.json
config/review675-694-portfolio-preview-media-ledger.json
config/staging-dependencies.json
```

Current fresh audit record:

```text
docs/REVIEWS-675-694-PORTFOLIO-PREVIEW-MEDIA-2026-10-08.md
```

The audited baseline main `e35563b...` passed exact-head CI run `1689`. The final current-head CI status for later corrective commits is external evidence and must be queried from GitHub; it is not self-certified by this README. Hostinger staging, Founder acceptance, production approval, exact deployed-code parity, live database/schema state, live migration state and operational acceptance remain separate gates.

## Current ownership and compatibility boundaries

- **File 00:** identity, membership, suspension and public-profile authorization remain owner-native; File 25 consumes bounded assertions and reads no File 00 tables.
- **File 03:** profile master/public projection, contact consent, canonical public media/focal coordinates, report/edit/privacy destinations and canonical profile identity remain File 03-owned. File 25 renders owner focal metadata but cannot substitute it.
- **File 07:** current main `2f4a89707724fd2b9946600afe10ddab27ec3c2d`, runtime `1.2.1`, DB `1.1.1`, contract `1.2.1`, projection schema `3`; directory/discovery/ranking truth remains File 07-owned.
- **File 08:** current main `70541974ce0ffb16aebef557c3016eb7447662f4`, runtime `1.2.15`, schema `3.4.0`, public clinic projection `1.1.0`. No reviewed profile-user→booking-destination API is published, so **Appointment remains hidden**.
- **File 09:** current main `cfc5f781a766330314dc98c42abeca0eb7786eba`, runtime `1.3.0`, schema `6`, integration contract `1.1.0`; Doctor-verification truth remains File 09-owned.
- **File 14:** current main `f64e7d17268daff4e3097c18ad510116e6eaf105`, runtime `1.4.6`, schema `10005`; File 14 consumes File 25 visual contracts while clinic/directory/verification destination truth remains with native owners.
- **File 17:** current main `8ae656e51796d1f05865d8be5dca2480443d79ca`; relationships and messaging remain File 17-owned. **Follow remains hidden** without a target-bound owner contract. Message appears only when File 03 supplies a concrete same-site owner-approved destination.
- **File 20:** current main `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`; sole structural shell/navigation/layout and canonical admin-parent owner.
- **File 21:** current main `f2eb7e95ddea327af36ea725ffb923b029f885e6`; canonical publication/ProfileTimeline owner. File 25 only normalizes the public read contract.
- **Files 06/10/11/12:** remain native Knowledge/Video/Reel/PDF content owners; File 25 now federates their already-reviewed public section projections into the profile Timeline without copying native data.
- **File 22:** current main `b7a7f2e69411cbd32f0574fd12d766fb70c01b7a`; create/edit workflow owner.
- **File 23:** current main `dcae138e6073f4d0ff596623deb05b9940b8271b`; private publishing-operations owner.
- **File 24:** current main `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`; security/privacy/compliance/resilience assurance owner.
- **File 26:** current main `bbea3aad466792a4a6a62b53532bbd45c7c592de`; global search/discovery/ranking owner.
- **File 25:** public profile/timeline/card visual presentation, profile-local federation/filtering, component contracts, responsive/accessibility refinement, SEO presentation and visual acceptance.

## Reviews 675–694 corrections

- Preserved File 03 public `focal_x`/`focal_y` through to cover `object-position`; the administrator focal preset is fallback-only.
- Completed the Profile Hero brief introduction and bounded truthful public lifecycle/status banner.
- Added explicit private/noindex `rtl` owner/admin preview alongside mobile/desktop and the existing public/member/contact/search/social projections.
- Added `Timeline_Provider_Plan_Contract` containing the complete File 25 §46 provider surface while retaining the older interface as a compatibility floor.
- Federated enabled/available File 06 Knowledge, File 10 Video, File 11 Reels and File 12 PDF public projections into the Profile Timeline through a read-only normalization adapter.
- Added Knowledge/Video/Reel/PDF Timeline filters only when their providers are actually registered.
- Required actor/reason/reference/surface/start/end audit metadata for every non-zero `pin_weight`; future/expired pins no longer affect chronology and pin audit metadata remains private.
- Refreshed File 07/File 09 and release/dependency evidence without promoting repository truth to staging/live truth.

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
