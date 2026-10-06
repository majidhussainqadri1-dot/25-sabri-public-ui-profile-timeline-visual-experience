# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

WordPress module for the **Sabri Social Homeopathy Platform**. This is the existing File 25; no duplicate File 26 is created.

## Current corrective candidate

- Runtime: `0.16.0`
- Schema: `2`
- Branch: `fix/reviews-615-634-plan-completion-20261006`
- Baseline main SHA reviewed: `e5e26696ffb1169884809d34127ed0cae1ba5b50`
- Governing product plan: Master Plan `v3.0` plus current central addenda
- Governing File 25 plan: Final Harmonized Specification `2.0`
- Source correction status: **Reviews 615–634 corrected; exact-head CI pending**
- Known unresolved source defects after the recorded corrections: **0**
- Hostinger staging accepted: **No**
- Founder acceptance: **No**
- Production approved: **No**
- Live deployment verified: **No**

Version `0.16.0` performs a fresh twenty-round plan/cross-file completion audit over the merged `0.15.0` main baseline. It completes the File 25 timeline-provider contract, owner preview surfaces, Founder native appointment presentation, governed timeline page-size setting, privacy-minimized observability taxonomy and current File 09/File 23 repository-truth metadata.

## Source implementation status

The governed File 25 source scope has been re-reviewed through **Reviews 615–634** against the Definitive Master Plan `v3.0`, File 25 Final Harmonized Specification `2.0`, and current companion repository source truth.

Machine-readable traceability:

```text
config/source-completion-matrix.json
config/all-chats-directive-matrix.json
config/review595-614-twenty-round-cross-file-completeness-ledger.json
config/review615-634-twenty-round-plan-completion-ledger.json
config/staging-dependencies.json
```

Current review report:

```text
docs/REVIEWS-615-634-SIXTH-FRESH-PLAN-COMPLETION-2026-10-06.md
```

The `0.16.0` candidate remains **pending exact-head CI** until GitHub Actions completes on the final branch head. Repository source evidence does not establish Hostinger staging, Founder acceptance, production approval, live deployment, deployed database/schema state, migration state, or operational acceptance.

## Current ownership and compatibility boundaries

- **File 00:** current observed main runtime `1.2.44`, DB `1.4.5`, membership contract `1.2.3`. File 25 supports the reviewed `1.2.2` and current `1.2.3` assertion contracts without direct File 00 table reads.
- **File 03:** current observed main `1.2.0-rc18`, contract `1.4.0`; profile master data, canonical URLs, public media and contact-consent truth remain File 03-owned.
- **File 08:** current observed main `1.2.15`, schema `3.4.0`, public clinic projection `1.1.0`. File 25 supports reviewed projection contracts `1.0.0` and `1.1.0` only through the strict public DTO.
- **File 09:** current observed `1.3.0`, integration contract `1.1.0`; Doctor verification truth remains File 09-owned.
- **File 18:** seller/listing truth remains owner-native; File 25 consumes owner-executed public DTOs only.
- **File 20:** current observed `1.4.17`, central-plan contract `1.0.0`; sole global shell/navigation/structural-layout owner.
- **File 21:** current observed package `1.0.5`, runtime `1.0.3`, schema `1.0.0`; canonical publication/ProfileTimeline owner.
- **File 22:** current observed `1.0.0-rc.3`; create/edit workflow owner.
- **File 23:** current observed `1.2.0`; private publishing-operations owner.
- **File 24:** current observed `0.99.0`; security, privacy, compliance, incident, audit-evidence and resilience owner.
- **File 25:** public visual tokens, public profile/timeline presentation, reusable public components, accessibility/responsive refinement, SEO presentation and visual acceptance contracts.

The current integrated staging candidate must satisfy at least **WordPress 7.0** and **PHP 8.1** because current companion requirements are stricter than File 25's standalone minima of WordPress 6.5/PHP 8.0.

## Version 0.16.0 corrections — Reviews 615–634

- Refreshed current File 09 and File 23 repository-source observations without converting source evidence into staging/live truth.
- Completed the section-46 Timeline Provider Contract: normalization, canonical URL, visibility, public actions, public metrics, correction state, sync events and health are now governed at registration/runtime.
- Added Founder native appointment presentation when the native action owner supplies a safe destination.
- Surfaced all seven owner-only profile preview modes: logged-out public, registered member, mobile, desktop, contact visibility, search-engine and social share-card.
- Added bounded `timeline_page_size` configuration (5–50, default 20) and wired it into public rendering.
- Added the complete privacy-minimized diagnostic taxonomy required by section 74, including reviewed File 24 advisory forwarding for security-critical diagnostic classes.
- Preserved all prior review ledgers while advancing current traceability through Reviews 615–634.

## Version 0.15.0 corrections — Reviews 595–614

- Reconciled File 00 current contract `1.2.3` while preserving exact reviewed `1.2.2` compatibility.
- Reconciled File 08 current public clinic projection `1.1.0` while preserving exact reviewed `1.0.0` compatibility and the strict field/exclusion allow-list.
- Changed high-risk timeline-index rebuild and migration execute/rollback authorization to **default deny** unless an explicit authoritative approval adapter returns true.
- Added the Safe Mode public recovery surface: canonical profile routes remain recoverable with a data-free, no-store, noindex `503` native-theme template while optional providers, design assets, migrations and File 25 write paths stay disabled.
- Added explicit root accessibility, performance, migration, rollback and staging-acceptance contracts required by the File 25 release structure.
- Added a dated cross-file current-HEAD audit and current repository-head observations without replacing historical reviewed dependency pins.
- Recorded all twenty rounds and the six defect rounds in a machine-readable ledger.

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
