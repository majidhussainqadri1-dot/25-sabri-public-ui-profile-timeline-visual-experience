# File 25 Architecture

## Governing rule

**One Unified Public Experience — Native Ownership Preserved — Public Profiles as Trusted Knowledge Gateways — Every Dynamic Action Executed by Its Native Owner.**

## Current source phase

Candidate `0.14.0` corrects the authoritative read boundaries of the existing File 25 implementation. It remains a Draft PR source candidate and does not represent Hostinger staging, merge, production, deployment, or Founder acceptance.

## Ownership

- File 00: identity, membership, Founder identity, approval, suspension, age/guardian policy, eligibility, professional eligibility, and public-profile authorization.
- File 03: profile master data, local photographs/media, and public-contact consent.
- File 08: clinic, availability, appointment, and scheduling truth.
- File 09: Doctor application, credential evidence, independent review, verification lifecycle, and immutable approved professional snapshot.
- Files 06/10/11/12/18: canonical Knowledge, Video, Reels, PDF, and Marketplace content/workflows.
- File 20: global shell, routes, header/navigation, sidebars, drawers, widths, structural layout, recovery, and rollback.
- File 21: Home/News publications, interactions, moderation, and production timeline provider.
- Files 22/23: content creation and private publishing operations.
- File 24: security, privacy, compliance, incidents, audit evidence, cache governance, and resilience.
- File 25: global visual language, public profiles, rebuildable timeline/section projections, reusable components/cards/states, responsive refinement, accessibility, SEO presentation, and visual acceptance.

## Authoritative native adapters

### File 00

`Native_Integration` accepts only File 00 `>=1.2.4 <1.3.0`, exact contract `1.1.2`, `SMC_Contracts::assertions()`, `smc_founder_user_id()`, and `smc_is_founder()`.

The returned assertion is rejected unless its contract version and user ID match exactly. File 25 performs no direct File 00 table read and does not calculate membership, age, guardian, suspension, professional eligibility, or public-profile state.

### File 09

Doctor presentation requires:

1. File 00 membership type `doctor`;
2. approved, eligible, non-suspended File 00 state;
3. File 00 professional verification and `can_practice`;
4. File 09 current verified/approved decision;
5. no revoking File 03 professional status.

Professional fields come from `gdo_get_approved_snapshot()`. File 25 allow-lists public qualification, licensing authority label, experience, specialty, languages, consultation modes, and biography. Private evidence and license numbers do not enter public projection.

### File 08

Clinic projection is accepted only from `swc_get_public_clinic_projection()` or `SWC_Helpers::public_clinic_projection()` with exact contract `1.0.0`. File 25 allow-lists name, address, country, city, hours, and timezone. Contact data continues through File 03 consent. Until File 08 implements the versioned owner contract, clinic projection remains unavailable.

### File 18

File 25 does not use `$wpdb`, `SMP_DB::table()`, or Marketplace SQL. It prefers `smp_get_public_profile_listings()` contract `1.0.0`. For reviewed File 18 `1.2.0-RC1`, a transitional read-only path calls `SMP_Utils::current_seller()`, `SMP_REST::products()`, and `SMP_Activator::marketplace_url()`.

The transitional owner fetch is bounded to the first 50 public DTOs; File 25 projects at most 24 cards. File 18 remains owner of listing eligibility, seller state, moderation, contact, offers, metrics, transactions, and direct-deal history.

## Global visual contract

File 25 exposes:

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_acceptance_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_notice(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
```

The canonical stylesheet handle is `sabri-visual-design-system`; reusable classes use the `sabri-ui-` prefix. File 20 supplies structural shell geometry. File 25 owns visual tokens, component appearance, public profiles/timelines, responsive visual consistency, and visual regression.

## Content cards and optional sections

`Content_Cards` accepts a bounded public display allow list, requires a title, ignores unknown/internal fields, performs no native writes, emits no native IDs, and uses strict same-origin URLs.

Approved optional sections are Knowledge, Media, Reviews, Research, and Marketplace. A tab appears only when an accepted provider produces at least one valid public card.

`Section_Registry` freezes provider ID, version, section, maturity, ownership declaration, and object identity. `Section_Service` bounds candidates and final cards, isolates errors, suppresses duplicates, and returns only public-safe data.

## Public profile projection

File 25 creates no second profile authority. Public projection obeys monotonic restriction:

- filters may revoke but cannot grant denied identity, Doctor state, public visibility, or contact exposure;
- File 25 performs no age calculation;
- unknown ordinary minor/contact state fails closed;
- File 03 consent controls phone, WhatsApp, and email exposure;
- public output excludes user IDs, identity documents, professional evidence, license numbers, patient data, provider IDs, native IDs, and private diagnostics.

## Routing and cache behavior

Canonical routes:

- `/founder/`
- `/doctors/{slug}/`
- `/profile/{slug}/`

Only truthfully renderable sections are exposed. Role/slug mismatches redirect canonically; unavailable sections return non-cacheable errors. Until File 24 supplies an accepted versioned cache partition, profile HTML and REST remain `no-store`.

## Timeline and provider safety

Timeline providers remain read-only. File 25 validates provider identity/version/maturity, public and review state, author binding, dates, media, and canonical URLs before final merge, sort, deduplication, and pagination. Public output excludes provider/native/user IDs and diagnostics.

## Visual acceptance

One evidence manifest declares one `target_commit_sha`. Every surface, viewport, direction, color/motion mode, zoom level, input mode, staging record, and Founder sign-off must match that commit.

Required evidence includes Founder, Doctor, Member, timeline, Knowledge, Media, Marketplace, all visual states, forms, responsive tables, Urdu RTL, keyboard, screen reader, forced colors, reduced motion, zoom, target viewports, and performance.

## Safe failure and acceptance

Missing or incompatible authoritative dependencies prevent the related public projection from booting. File 25 Safe Mode disables its own overrides, records bounded incidents, permits authenticated retry, and leaves native data untouched.

Source lint and green CI do not prove integration or visual completion. Exact packages, real owner contracts, Hostinger staging, migration, rollback, restore, deployment, monitoring, and Founder acceptance remain mandatory.
