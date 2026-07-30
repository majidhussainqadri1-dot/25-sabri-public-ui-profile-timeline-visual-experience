# File 25 Architecture

## Governing rule

**One Unified Public Experience — Native Ownership Preserved — Public Profiles as Trusted Knowledge Gateways — Every Dynamic Action Executed by Its Native Owner.**

## Current source phase

This branch implements and reviews phases 25A–25G and continues phase 25H with the global semantic-token system, reusable visual states, governed content cards, form/table primitives, and cross-module adoption contracts. It does not represent staging or production completion.

## Ownership

- File 00: identity, roles, approval, age/guardian authority, public visibility, and professional eligibility.
- File 03: profile master data, photographs, and public-contact consent.
- File 20: global shell, navigation, widths, layout resolver, and base shell tokens.
- File 21: Home/News publications, comments, interactions, moderation, and the production timeline provider.
- Files 22/23: content creation and private publishing operations.
- File 24: security, privacy, compliance, incidents, cache partitioning, and resilience.
- File 25: global public visual language, public profile templates, rebuildable timeline projection, reusable components/cards/states, responsive refinement, accessibility, and visual acceptance.

## Global visual contract

File 25 exposes a versioned design-system contract through:

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
```

The canonical stylesheet handle is `sabri-visual-design-system`, and reusable classes use the `sabri-ui-` prefix.

File 25 inherits File 20-owned shell tokens. It may define derivative semantic tokens such as strong/soft primary and contrast foregrounds, but it does not replace File 20's connected text, surface, page, border, focus, width, radius, gap, or font-scale decisions.

## Reusable content cards

`Content_Cards` provides visual variants for article, post, news, video, reel, book, PDF, Doctor, clinic, event, and Marketplace item data.

The renderer:

- accepts only a bounded display allow-list;
- requires a title;
- ignores unknown/internal fields;
- performs no native reads or writes;
- outputs no native IDs, patient IDs, metrics, comments, reactions, or diagnostics;
- uses one keyboard destination per card by default;
- requires same-origin canonical/action/media URLs;
- emits semantic article, heading, metadata-list, and time markup;
- leaves publication, permission, moderation, interaction, and transaction ownership with the native module.

## URL boundary

`Public_URL::sanitize_same_site()` rejects:

- protocol-relative destinations;
- cross-origin destinations;
- credential-bearing URLs;
- HTTPS-to-HTTP downgrades;
- mismatched ports;
- control characters and backslashes;
- malformed schemes/hosts;
- fragments where a canonical card/media destination forbids them.

This policy closes presentation-layer open-redirect and authority-escape paths. External navigation, if later required, must use a separately reviewed contract.

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

The WordPress posts provider is a compatibility baseline only. It is registered only while File 21 is absent. An active File 21 installation without a compatible provider yields an honest unavailable timeline instead of bypassing File 21 with raw WordPress queries.

The File 21 source adapter uses `Sabri\HomeNewsFeed\ProfileTimeline::query()`, retains the canonical `file-21` ID, and remains `read-only` until exact multi-plugin staging acceptance.

## Safe failure

A missing required identity dependency prevents profile/timeline overrides from booting, but the identity-independent design-system contract may continue. File 25 Safe Mode disables all File 25 public visual overrides, records bounded incident metadata, preserves nested boundaries, exposes an authenticated administrator retry, and returns control to native modules without deleting data. File 24 remains the canonical incident owner.

## Existing-module compatibility

The implementation consumes currently shipped module contracts without copying data:

- File 00: `SMC_VERSION`, `smc_get_profile()`, `smc_is_founder()`, `smc_user_status()`, and documented owned tables.
- File 03: `SPD_VERSION` and `SPD_Helpers` compatibility reads.
- File 20: `SABRI_SHELL_VERSION`, `sabri_shell_layout_mode`, `sabri-shell-theme-*` classes, and `--sabri-shell-*` tokens.
- File 21: `SABRI_HNF_VERSION` and `Sabri\HomeNewsFeed\ProfileTimeline::query()`.

## Acceptance boundary

Source lint and CI do not prove integration or visual completion. Exact plugin packages, real native module pages, cross-module component adoption, visual-regression baselines, Urdu RTL, keyboard, screen reader, zoom, forced colors, reduced motion, performance, migration, rollback, staging, deployment, and Founder acceptance remain mandatory.
