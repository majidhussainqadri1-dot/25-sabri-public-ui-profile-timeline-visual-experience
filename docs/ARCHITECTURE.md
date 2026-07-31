# File 25 Architecture

## Governing rule

**One Unified Public Experience — Native Ownership Preserved — Public Profiles as Trusted Knowledge Gateways — Every Dynamic Action Executed by Its Native Owner.**

## Current source phase

This branch implements and reviews phases 25A–25H, continues 25I with governed Knowledge, Media, and Marketplace adapters, and continues 25J with a commit-bound visual-acceptance evidence contract. It does not represent staging or production completion.

## Ownership

- File 00: identity, roles, approval, age/guardian authority, public visibility, and professional eligibility.
- File 03: profile master data, photographs, and public-contact consent.
- Files 06/10/11/12/18: canonical Knowledge, Video, Reels, PDF, Marketplace content and native workflows.
- File 20: global shell, navigation, widths, layout resolver, and base shell tokens.
- File 21: Home/News publications, comments, interactions, moderation, and the production timeline provider.
- Files 22/23: content creation and private publishing operations.
- File 24: security, privacy, compliance, incidents, cache partitioning, and resilience.
- File 25: global public visual language, public profile templates, rebuildable timeline/section projections, reusable components/cards/states, responsive refinement, accessibility, and visual acceptance.

## Global visual contract

File 25 exposes:

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_acceptance_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_notice(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
```

The canonical stylesheet handle is `sabri-visual-design-system`, and reusable classes use the `sabri-ui-` prefix.

File 25 inherits File 20-owned shell tokens. It may define derivative semantic tokens, but it does not replace File 20's connected text, surface, page, border, focus, width, radius, gap, or font-scale decisions.

## Reusable content cards

`Content_Cards` supplies visual variants for article, post, news, video, reel, book, PDF, Doctor, clinic, event, and Marketplace item data.

The renderer accepts only a bounded display allow list, requires a title, ignores unknown/internal fields, performs no native writes, emits no native IDs or diagnostics, uses one keyboard destination per card, applies exact same-origin URL policy, and preserves native ownership.

## Optional profile-section architecture

Approved sections are Knowledge, Media, Reviews, Research, and Marketplace. A tab appears only when an accepted provider produces at least one valid public card.

`Section_Registry` freezes provider ID, version, section, maturity, native-ownership declaration, and concrete object identity. `validated_metadata()` reads every mutable field once and compares the complete registration-bound snapshot before a query. Provider impersonation, self-promotion, ownership drift, metadata exceptions, and section/version mutation fail closed.

`Section_Service` bounds provider candidates and final cards, isolates errors, suppresses duplicates, renders descriptors only through `Content_Cards`, and returns only bounded public status.

A valid canonical item URL is the preferred identity. Native systems with one application URL may provide a private SHA-256 `projection_key`; it is consumed before rendering and never exposed publicly.

Current native read-only adapters:

- File 06 Homeopathy Encyclopedia → Knowledge;
- File 10 Video Wall non-Reels → Media;
- File 11 Reels with 60–600 second validation → Media;
- File 12 PDF Library → Media;
- File 18 approved public seller listings → Marketplace.

Files 06/10/11/12/18 retain canonical content, permissions, moderation, metrics, files, contacts, and workflow ownership.

## URL boundary

`Public_URL::sanitize_same_site()` rejects protocol-relative, cross-origin, credential-bearing, downgrade, port-mismatch, control-character, backslash, malformed, and forbidden-fragment URLs. External navigation requires a separate reviewed contract.

## Public profile projection

File 25 creates no second profile authority. It reads the minimum presentation fields from File 00 and File 03 and applies monotonic visibility:

- filters may narrow but not elevate identity or widen visibility;
- public Doctor presentation requires approved adult identity and current evidence;
- ordinary member contact requires explicit public-contact consent;
- minors, unknown-age, private/member-only, rejected, and suspended profiles fail closed;
- public output excludes identity documents, registration numbers, patient data, user IDs, and native private identifiers.

## Routing and cache behavior

Canonical routes:

- `/founder/`
- `/doctors/{slug}/`
- `/profile/{slug}/`

Only truthfully renderable sections are exposed. Role/slug mismatches redirect canonically; unknown or unavailable sections return non-cacheable 404 responses. Until File 24 provides an audited cache partition, profile HTML and REST remain `no-store`.

## Timeline design

Timeline providers remain read-only. File 25 bounds provider results, validates identity/version/maturity, public/review state, author binding, dates, media and canonical URLs, then performs final merge, sort, deduplication, and pagination. Public output excludes provider/native/user IDs, metrics pointers, and diagnostics.

The WordPress posts provider is registered only while File 21 is absent. Active incompatible File 21 is not bypassed. The File 21 adapter remains `read-only` until exact staging acceptance.

## Visual acceptance architecture

One evidence manifest declares a single `target_commit_sha`. Every required surface, viewport, direction, color mode, motion mode, zoom level, input mode, staging record, and Founder sign-off must match that commit.

Every matrix cell requires pass status, bounded artifact reference, SHA-256, commit SHA, strict ISO timestamp, and reviewer. Impossible timestamps, parser warnings, mixed commits, unsafe references, and noncanonical staging URLs fail closed.

Required surfaces include Founder, Doctor, Member, timeline, Knowledge, Media, Marketplace, all visual states, card grids, forms, and responsive tables.

## Safe failure

A missing identity dependency prevents profile/timeline/section overrides from booting, while the identity-independent design system may continue. File 25 Safe Mode disables File 25 overrides, records bounded incidents, preserves nested boundaries, allows authenticated retry, and leaves native data untouched. File 24 remains the incident owner.

## Acceptance boundary

Source lint and green CI do not prove integration or visual completion. Exact plugin packages, real native pages/content, visual-regression baselines, Urdu RTL, keyboard, screen reader, zoom, forced colors, reduced motion, performance, migration, rollback, staging, deployment, monitoring, and Founder acceptance remain mandatory.
