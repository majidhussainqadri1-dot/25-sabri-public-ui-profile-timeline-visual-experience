# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

## Canonical decision

This is the same existing File 25. It is not a new file and does not create File 26. The earlier title, **Complete Public UI, Profile Timeline and Visual Experience**, remains explanatory scope under the broader canonical name.

## Ownership boundary

- File 20 owns the application shell: header, navigation, sidebars, mobile drawers, bottom navigation, widths, and layout resolver.
- File 21 owns Home, News Feed, posts, comments, reactions, moderation, and social behavior.
- Native modules own canonical Knowledge, Media, Marketplace, Reviews, Research, files, metrics, moderation, and workflows.
- File 25 owns the global public design system, visual consistency, profile presentation, profile timeline, responsive refinement, accessibility, reusable public components, visual states, content-card presentation, and visual-regression acceptance.

File 25 must not render a second shell, create a second publication backend, or take ownership of native card objects.

## Runtime contract

Design-system contract version: `1.4.0`

Optional-section contract version: `1.1.0`

Visual-acceptance contract version: `1.2.0`

Public integration functions:

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_acceptance_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_notice(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
```

WordPress filters:

```text
sabri_visual_experience/contract
sabri_visual_experience/tokens
sabri_visual_experience/components
sabri_visual_experience/content_cards
sabri_visual_experience/acceptance
sabri_public_experience/design_system_contract
```

Canonical stylesheet handle: `sabri-visual-design-system`.

Reusable component prefix: `sabri-ui-`.

## Semantic tokens

File 25 exposes globally unique `--sabri-visual-*` semantic tokens. Where File 20 supplies an equivalent `--sabri-shell-*` token, File 25 inherits it and provides a bounded fallback. File 25 does not replace File 20-owned text, surface, page, border, focus, width, radius, gap, or font-scale values when the shell is connected.

The contract includes primary, strong-primary, soft-primary, on-primary, text, muted, surface, page, border, focus, success, warning, danger, on-danger, radii, spacing, font scale, and content-width tokens.

## Reusable components

Current primitives include container, reading width, stack, cluster, responsive grid, generic and content cards, buttons, badges, loading/empty/error/success/warning/unavailable states, notices, form controls, responsive tables, skeletons, and visually hidden utilities.

All classes are prefixed. File 25 does not globally restyle arbitrary native module markup.

## Content-card catalog

Presentation variants include article, post, news, video, reel, book, PDF, Doctor, clinic, event, and Marketplace item.

These are presentation types only. Native modules retain content, publication state, permissions, interactions, transactions, metrics, files, and canonical records.

## Provider identity and projection rules

The optional-section registry freezes provider ID, version, section, maturity, native-ownership declaration, and concrete object identity. Query-time validation reads mutable metadata once and compares the complete registration-bound snapshot.

A valid canonical same-site item URL is the preferred duplicate identity. A native module that exposes only one application URL may supply an internal `projection_key` with these rules:

- SHA-256 hexadecimal format;
- used only before rendering;
- excluded from the content-card allow list;
- never exposed in HTML or REST;
- does not create a public ID or transfer native ownership.

## URL and media policy

Reusable state and card destinations are exact same-origin by default. The policy rejects protocol-relative, external, credential-bearing, scheme-downgrade, port-mismatch, control-character, backslash, malformed, and fragment-bearing canonical card/media URLs.

External destinations require a separately governed contract.

## Accessibility and responsive rules

The contract includes minimum 44px-compatible targets, visible focus, one keyboard destination per card, semantic markup, RTL/LTR logical properties, reduced motion, forced colors, responsive tables and compact cards, print behavior, and local/system fonts only.

## Visual acceptance evidence

Every accepted evidence manifest declares one exact `target_commit_sha`. Every surface, viewport, direction, color mode, motion mode, zoom level, input mode, staging record, and Founder sign-off must match that commit.

Each matrix record requires pass status, artifact reference, SHA-256 checksum, commit SHA, strict timestamp, and reviewer. Impossible normalized timestamps, mixed-commit records, unsafe evidence schemes, and noncanonical staging URLs fail closed.

Required surfaces include Founder, Doctor, Member, timeline, Knowledge, Media, Marketplace, all visual states, content-card grid, forms, and responsive tables.

## Failure behavior

The global design-system layer does not require File 00 identity services. Profile, timeline, and optional-section features remain fail-closed when their authoritative dependencies are unavailable. Safe Mode disables File 25 visual overrides without changing native data.

An untitled content card returns no markup. Unsafe actions or images are omitted. Unknown fields are ignored rather than serialized.

## Acceptance boundary

Source contracts and green CI do not prove visual acceptance. Real module pages, exact-commit viewport artifacts, Urdu RTL, keyboard, screen-reader, forced-colors, reduced-motion, performance, staging, rollback, and Founder acceptance remain mandatory.
