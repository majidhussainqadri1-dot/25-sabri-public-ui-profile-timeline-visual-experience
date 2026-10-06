# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

WordPress module for the **Sabri Social Homeopathy Platform**. This is the existing File 25; no duplicate File 26 is created.

## Current corrective candidate

- Runtime: `0.14.0`
- Schema: `2`
- Governing product plan: Master Plan `v3.0`
- Governing shell plan: File 20 `v4.1`
- Source implementation status: **Complete**
- Known unresolved source defects: **0**
- Hostinger staging accepted: **No**
- Production approved: **No**
- Live deployment authorized: **No**

Version `0.14.0` is reconciled against the current File 25 plan, the central governing plan, and current companion repository heads. File 25 now consumes File 08 public clinic contract `1.1.0`, records File 17/19/26 ownership boundaries explicitly, and keeps repository/CI evidence separate from staging/live truth.

## Source implementation completion

The governed File 25 source scope is complete against the Sabri Social Homeopathy Platform Definitive Master Plan `v3.0`, the All-Chats Recovered Directive Register `v2.1`, and File 25 Final Harmonized Specification `2.0`.

Machine-readable traceability:

```text
config/source-completion-matrix.json
config/all-chats-directive-matrix.json
```

Executable verification:

```bash
php tools/verify-source-completion.php
```

Human-readable declaration:

```text
docs/SOURCE-IMPLEMENTATION-COMPLETION-DECLARATION-2026-08-04.md
```

“Source implementation complete” means the governed File 25 source-owned capabilities are implemented, delegated ownership boundaries are enforced, Reviews 20–93 remain executable, and no known unresolved source defect is open. It does **not** mean Hostinger staging, Founder acceptance, production approval, live deployment or operational acceptance.

## Corrected ownership boundaries

- **File 00 `1.2.44`, contract `1.2.3`:** identity, membership state, Founder identity, suspension, eligibility, guardian policy and public-profile authorization.
- **File 03 `1.2.0-rc18`, contract `1.4.0`:** canonical profile master, public profile/personal-site DTO, public contact consent and profile-report/edit routes.
- **File 07 `1.2.0`, contract `1.2.0`:** Doctors Directory domain discovery; platform-wide ranking remains File 26.
- **File 08 `1.2.15`, public clinic contract `1.1.0`:** clinic/appointment truth and owner-executed clinic/appointment projections. File 25 performs no File 08 table writes/reads.
- **File 09 `1.3.0`, integration contract `1.1.0`:** Doctor verification decision and approved professional evidence projection.
- **File 14 `1.4.4`:** Global Clinic USP/conversion consumer; File 25 remains visual-token owner.
- **File 17 `2.1.0`:** Follow/Connect/Message/Calls canonical owner; File 25 may render only current owner-authorized destinations/actions.
- **File 18 `1.2.0-RC1`:** Marketplace truth; File 25 consumes public DTOs only.
- **File 19 `3.0.5`:** sole user-facing notification center/delivery owner; File 25 creates no duplicate notification backend.
- **File 20 `1.4.17`:** sole structural shell/navigation/layout owner.
- **File 21 package `1.0.5` / runtime `1.0.3`:** Home/News/publication/timeline truth.
- **File 22 current main:** canonical Create/edit workflow and `/create/` destination.
- **File 23 `1.2.0` current main:** private Publishing Dashboard and publishing-intelligence management.
- **File 24 `0.99.0`:** security/privacy/compliance/resilience assurance owner.
- **File 26 `1.2.0`, contract `1.2`:** global Search/Discovery/Recommendations/Knowledge Graph/Classification owner.
- **File 25:** canonical visual tokens, public visual projection, profile/timeline presentation, component API, responsive/accessibility refinement and deterministic staging packaging.
## File 08 exact reviewed input

File 25's current source matrix is bound to File 08 main `1.2.15`, schema `3.4.0`, public clinic contract `1.1.0`, source commit `70541974ce0ffb16aebef557c3016eb7447662f4`. This is repository source truth only; Hostinger staging remains pending.

Required owner symbols:

```php
WCA_VERSION
WCA_Contracts::PUBLIC_CLINIC_CONTRACT_VERSION
swc_get_public_clinic_projection()
swc_public_clinic_projection_contract()
```

The bounded compatibility DTO allow list is `name`, dedicated clinic `address`, `country`, `city`, `hours`, and `timezone`; current File 03/File 08 cross-file claims additionally provide validated native clinic/appointment destinations without transferring appointment truth to File 25.
## Version 0.14.0 corrections

- Corrected the canonical-contact filter so consented File 03 phone/WhatsApp values survive the default filter while substitutions remain denied.
- Changed high-risk rebuild/migration authorization to fail closed unless an authoritative provider explicitly returns `true`.
- Added `CHAT-UX-001` welcome visual presentation with File 20 retained as the exclusive first-visit/session/30-day frequency owner.
- Added All-Chats v2.1 directive traceability and corrected the File 24 source-integration status wording in `SECURITY.md`.
- Replaced stale File 00 assumptions with exact `SMC_Contracts::assertions()` contract `1.1.2`.
- Removed File 25 reads from foreign File 00 professional-credential and clinic tables.
- Removed locally derived Doctor verification, qualification-role inference, and license-expiry authority.
- Required both File 00 eligibility/can-practice assertions and File 09 current verification decision.
- Projected professional presentation only from File 09's immutable approved snapshot.
- Removed File 25 age calculation; explicit File 00 minor/guardian assertions govern, while unknown ordinary contact state fails closed.
- Replaced the File 08 placeholder with the exact reviewed `0.2.1` owner contract and pending-Hostinger staging evidence.
- Replaced File 18 direct SQL with owner APIs: future `smp_get_public_profile_listings()` or the bounded `SMP_Utils::current_seller()` + `SMP_REST::products()` transitional path.
- Updated the File 00/03/08/09/18/20/21/24/25 dependency matrix and Hostinger staging scenarios.
- Added the dual-plan source-completion matrix and executable verifier without changing the external acceptance boundary.

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
node --check assets/js/future-public-experience.js
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
- Real File 08 `0.2.1` public/private/eligible/ineligible/suspended/revoked/empty clinic projections and File 25 rendering behavior.
- Real Founder, Doctor, Member, Patient, Student, minor, suspended, rejected, private, and wrong-author workflows.
- File 03 consent-on/consent-off, File 09 expiry/revocation, and File 18 owner-DTO tests.
- Urdu RTL, keyboard, screen reader, 200%/400% zoom, forced colors, reduced motion, target viewports, and visual regression evidence.
- Fresh install, upgrade, rollback, backup restore, performance, logs, monitoring, and Founder acceptance.
- PR review, controlled merge, release, live deployment, and post-deployment observation.
