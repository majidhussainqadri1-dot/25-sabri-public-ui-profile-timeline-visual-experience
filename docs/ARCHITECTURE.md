# File 25 Architecture

## Canonical identity

**File 25 — Sabri Unified Global Visual Experience and Design System**

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

This is the same existing File 25. No File 26 is created for global visual design or Facebook-familiar social polish.

## Governing rule

**One Unified Public Experience — One Global Visual System — Native Ownership Preserved — Public Profiles as Trusted Knowledge Gateways — Every Dynamic Action Executed by Its Native Owner.**

## Current source phase

The branch contains the reviewed foundation through the global design-system contract, Founder/Doctor public profile foundation, federated timeline registry, and native read-only File 21 adapter. It does not represent staging or production completion.

## Ownership

- File 00: identity, roles, approval, age/guardian authority, public visibility, and professional eligibility.
- File 03: profile master data, photographs, and public-contact consent.
- File 20: global shell, header, navigation, sidebars, mobile drawers, widths, layout resolver, and shell-level base tokens.
- File 21: Home, News, posts, comments, interactions, moderation, and native publication timeline data.
- Files 22/23: content creation and private publishing operations.
- File 24: security, privacy, compliance, incidents, cache partitioning, and resilience.
- File 25: global public design system, public profile templates, rebuildable timeline projection, reusable public components and states, responsive refinement, accessibility, visual consistency, and visual acceptance.

## Global visual contract

File 25 supplies a versioned semantic layer without replacing File 20:

- globally unique `--sabri-visual-*` semantic tokens;
- inheritance from equivalent `--sabri-shell-*` tokens with local fallbacks;
- prefixed `sabri-ui-*` component primitives;
- loading, empty, error, success, warning, unavailable, and skeleton states;
- responsive containers, grids, stacks, and clusters;
- visible focus, logical properties, reduced motion, and forced-colors support;
- no remote fonts, CSS, JavaScript, or broad unprefixed element restyling.

The design-system stylesheet can load independently of File 00. Identity-dependent profiles and timelines remain fail-closed when File 00 is unavailable.

Public integration functions:

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_render_state(array $args = []): string
```

## Public profile projection

File 25 creates no second profile authority. It reads the minimum presentation fields required from File 00 and File 03, then applies monotonic visibility rules:

- a setting or filter may make a surface more restrictive but may not elevate Founder/Doctor status, widen visibility, restore denied contact data, or fabricate a missing dependency;
- public Doctor presentation requires an approved adult File 00 Doctor identity, current professional evidence, and no File 03 rejection/suspension;
- ordinary member contact requires a public approved profile and explicit File 03 public-contact consent;
- minors, unknown-age accounts, private/member-only profiles, and rejected/suspended states fail closed;
- public responses contain no identity documents, registration numbers, patient data, internal WordPress user IDs, or private native identifiers.

## Routing and cache behavior

Canonical routes are:

- `/founder/`
- `/doctors/{slug}/`
- `/profile/{slug}/`

Only sections that can be rendered truthfully are exposed. Role-route mismatch and alias slugs redirect to the canonical destination. Unknown or unavailable sections return a non-cacheable 404 rather than an empty duplicate page.

Until File 24 supplies an audited public/private cache-partition contract, profile HTML and REST responses remain `no-store`.

## Timeline design

Providers expose public, approved, canonical objects through a read-only interface. File 25:

- bounds the provider registry and each provider result set;
- validates provider ID, version, maturity, item identity, public status, review state, author/profile binding, dates, media references, and same-site canonical destinations;
- asks every provider for a bounded newest-candidate pool beginning at provider page one;
- performs the final cross-provider merge, sort, canonical deduplication, and pagination itself;
- returns public-safe items without provider IDs, native object IDs, WordPress user IDs, metrics pointers, or diagnostic details;
- reports partial and truncated states honestly.

The native read-only File 21 adapter consumes `Sabri\HomeNewsFeed\ProfileTimeline::query()`. A File 21-owned provider registered first with canonical ID `file-21` takes precedence. Raw WordPress fallback is allowed only while File 21 is absent; active-but-incompatible File 21 fails closed.

## Integration hooks

- `sabri_visual_experience/contract`
- `sabri_visual_experience/tokens`
- `sabri_visual_experience/components`
- `sabri_public_experience/design_system_contract`
- `sabri_public_experience/dependency/*`
- `sabri_public_experience/profile_by_slug`
- `sabri_public_experience/public_profile_data`
- `sabri_public_experience/can_render_profile`
- `sabri_public_experience/can_show_contact`
- `sabri_public_experience/available_profile_sections`
- `sabri_public_experience/register_timeline_providers`
- `sabri_public_experience/provider_error`
- `sabri_public_experience/safe_mode_changed`

All extension hooks are followed by authoritative revalidation where they could affect public identity, contact, visibility, routing, or timeline admission. Canonical design tokens and ownership fields cannot be silently replaced by another plugin.

## Safe failure

Safe Mode uses nested boundaries so an inner operation cannot clear an outer fatal guard. It disables only File 25 visual/profile overrides, records bounded incident metadata, emits the File 24 integration event, and leaves native data untouched. File 24 remains the canonical incident owner.

## Completion boundary

Source lint, tests, and green CI are necessary but do not replace full-module screenshots, responsive viewport evidence, RTL, keyboard, screen reader, zoom, forced colors, reduced motion, performance, fresh install, upgrade, migration, rollback, backup restoration, staging, deployment, and Founder acceptance.
