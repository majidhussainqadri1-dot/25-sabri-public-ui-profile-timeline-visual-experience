# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

WordPress module for the **Sabri Social Homeopathy Platform**. This is the existing File 25; no duplicate File 26 is created.

## Current corrective candidate — Reviews 695–714

- Runtime: `0.15.0` (source package version unchanged).
- Frozen main audit baseline: `3075224089506fed19af1441ebf3556c2d5230b5`.
- Baseline exact-main CI: **PASS**, run `1784` (source/CI evidence only).
- Corrective branch: `fix/reviews-695-714-timeline-integrity-20261009`.
- Governed File 25 plan: Final Harmonized Specification `2.0` and central consolidated master plan.
- Source review result: **20 scopes; 6 corrected defect rounds, 13 clean source scopes and 1 owner-contract dependency scope**.
- Known unresolved File 25 source defects after the recorded corrections: **0**
- Known owner-contract gaps: File 08 profile booking destination, File 17 target-bound Follow destination, and richer approved non-post timeline sources beyond File 21.
- Hostinger staging accepted: **No**
- Production approved: **No**
- Live deployment verified: **No**. Exact deployed parity verified: **No**.

### Corrective implementation

- Enforced **active UTC start/end and profile-timeline surface** for audited pins. Expired, future, malformed and foreign-surface pin claims lose ordering priority without hiding approved public items.
- Repaired merged Timeline pagination: per-provider 500-item caps no longer impose a single-provider page ceiling across all providers.
- Rejects pages outside bounded aggregate capacity **before** offset multiplication to avoid integer overflow.
- Ordinary 20+1 look-ahead no longer produces a false hard-limit warning; actual 500-item provider cap still warns.
- Year, language, topic, Corrections and search refinements now retrieve the **complete bounded 500-item candidate pool** so older matches can be found.
- **Oldest** ordering now uses the full bounded pool rather than sorting a newest-only preview.
- Added executable regression coverage with real bounded mock providers, multi-provider Page 26, huge-page input, and active/future/expired/foreign pin windows.

### Evidence

```text
config/review695-714-timeline-integrity-ledger.json
config/review715-734-provider-contract-rebase-ledger.json
docs/REVIEWS-715-734-PROVIDER-CONTRACT-REBASE-2026-10-09.md
tests/review695-714-timeline-integrity.php
```

**Evidence boundary:** CI builds and ZIP checks are not installed staging, live deployment or database truth. Exact deployed code, DB/schema, migration state, staging visual/role acceptance and live re-test all remain unverified.

## Source implementation status

File 25 source-owned scope identified in Reviews 695–714 has been corrected; later source changes require fresh exact-head CI evidence. Mutable exact-head CI state is intentionally **not frozen as permanent truth inside source metadata**: the exact current GitHub HEAD must be checked against its current CI run after the final commit. Repository/CI evidence never proves installed Hostinger staging or live deployment.

Machine-readable traceability:

```text
config/source-completion-matrix.json
config/all-chats-directive-matrix.json
config/review595-614-twenty-round-cross-file-completeness-ledger.json
config/review615-634-fresh-cross-file-audit-ledger.json
config/review635-654-plan-completeness-ledger.json
config/review655-674-admin-plan-completeness-ledger.json
config/review675-694-timeline-completeness-ledger.json
config/review695-714-timeline-integrity-ledger.json
config/staging-dependencies.json
```

Current fresh audit record:

```text
docs/REVIEWS-695-714-TIMELINE-INTEGRITY-2026-10-09.md
```

The audited baseline main `e35563b7...` passed exact-head CI run `1689`. The final current-head CI status for any later corrective commit is external evidence and must be queried from GitHub; it is not self-certified by this README. Hostinger staging, Founder acceptance, production approval, exact deployed-code parity, live database/schema state, live migration state and operational acceptance remain separate gates.

## Current ownership and compatibility boundaries

- **File 03:** profile master/public projection, contact consent, canonical public media and canonical profile identity remain File 03-owned.
- **File 08:** current main `70541974ce0ffb16aebef557c3016eb7447662f4`; no reviewed profile-user→booking-destination API is published, so **Appointment remains hidden**.
- **File 09:** current main `cfc5f781a766330314dc98c42abeca0eb7786eba`, runtime `1.3.0`, schema `6`, integration contract `1.1.0`; Doctor-verification truth remains File 09-owned.
- **File 14:** current main `080e2198d84dfb7491bb0b75946e14a5fe118b91`, runtime `1.4.8`, schema `10005`; File 14 remains within File 25's reviewed compatibility family.
- **File 17:** current main `8ae656e51796d1f05865d8be5dca2480443d79ca`; **Follow remains hidden** without a target-bound owner contract.
- **File 20:** current main `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`; sole structural shell/navigation/layout and canonical admin-parent owner.
- **File 21:** current main `f2eb7e95ddea327af36ea725ffb923b029f885e6`; canonical publication/ProfileTimeline owner. Its current `ProfileTimeline::query` remains the authoritative approved post timeline source.
- **File 22:** current main `b7a7f2e69411cbd32f0574fd12d766fb70c01b7a`; create/edit workflow owner.
- **File 23:** current main `dcae138e6073f4d0ff596623deb05b9940b8271b`; private publishing-operations owner.
- **File 24:** current main `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`; security/privacy/compliance/resilience assurance owner.
- **File 26:** current main `bbea3aad466792a4a6a62b53532bbd45c7c592de`; global search/discovery/ranking owner.
- **File 25:** public profile/timeline/card visual presentation, profile-local timeline filtering/sorting, component visual contracts, responsive/accessibility refinement, SEO presentation and visual acceptance.

## Reviews 675–694 corrections

- Added bounded **provider, year, language and topic** secondary timeline filters.
- Added **Latest / Oldest** sorting while preserving governed pin precedence.
- Added the plan-required **Corrections** projection filter over corrected/retracted owner-authorized items.
- Search and secondary filters now preserve compatible URL state and owner preview mode while resetting pagination.
- Filtered empty results now say **No matching public contributions** instead of falsely implying the profile has no publications.
- Added an `aria-live` screen-reader page/item announcement.
- Added REST parity for provider/year/language/topic/sort/search.
- All explicit timeline refinement URLs, including `?sort=latest`, are now noindex to prevent duplicate indexable filter URLs.
- A nonzero `pin_weight` now requires bounded internal audit provenance: reference, actor, reason, surface, start and end. Invalid/unaudited pins are neutralized to weight `0` without dropping the public item.
- Refreshed File 09 R26 and File 14 v1.4.8 repository-source evidence.
- Richer planned timeline types beyond the current File 21 post timeline remain owner-contract dependent; File 25 will not infer publication approval from section-card adapters or foreign storage.

## Reviews 715–734 corrections

- Restarted from newer main `347a4ff4...` after concurrent Reviews 695–714 timeline-integrity work landed; those integrity fixes were preserved rather than overwritten.
- Completed all plan-required Timeline Provider methods across the interface and concrete File 21/WordPress providers.
- Provider registration now executes the sync hook fail-closed; normalized public items are cross-checked against owner canonical URL, visibility, actions and correction state.
- Added **Most Viewed / Most Saved** only when a provider supplies real `privacy_safe` public metrics. Current File 21 publishes no such metric contract, so these sorts remain hidden there.
- Added **Review state / Source state** refinements across renderer, public HTML, service, REST and SEO filtered-query handling.
- Added explicit accessible **Load More** while retaining page fallback and current browser-Back/pagination integrity.
- Refreshed current File 07 source evidence to runtime `1.2.1`, DB `1.1.1`, contract `1.2.1`, projection schema `3`.

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
