# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

## Canonical decision

This is the same existing File 25. It is not a new file and does not create File 26. The earlier title, **Complete Public UI, Profile Timeline and Visual Experience**, remains explanatory scope under the broader canonical name.

## Ownership boundary

- File 20 owns the application shell: header, navigation, sidebars, mobile drawers, bottom navigation, widths, and layout resolver.
- File 21 owns Home, News Feed, posts, comments, reactions, moderation, and social behavior.
- File 25 owns the global public design system, visual consistency, profile presentation, profile timeline, responsive refinement, accessibility, reusable public components, visual states, content-card presentation, and visual-regression acceptance.

File 25 must not render a second shell, create a second publication backend, or take ownership of native card objects.

## Runtime contract

Design-system contract version: `1.1.0`

Component contract version: `1.1.0`

Content-card contract version: `1.0.0`

Public integration functions:

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
```

WordPress filters:

```text
sabri_visual_experience/contract
sabri_visual_experience/tokens
sabri_visual_experience/components
sabri_visual_experience/content_cards
sabri_public_experience/design_system_contract
```

The canonical stylesheet handle is:

```text
sabri-visual-design-system
```

The reusable component prefix is:

```text
sabri-ui-
```

## Semantic tokens

File 25 exposes globally unique `--sabri-visual-*` semantic tokens. Where File 20 supplies an equivalent `--sabri-shell-*` token, File 25 inherits it and provides a bounded fallback. File 25 does not replace File 20-owned text, surface, page, border, focus, width, radius, gap, or font-scale values when the shell is connected.

The contract includes primary, strong-primary, soft-primary, on-primary, text, muted, surface, page, border, focus, success, warning, danger, on-danger, radii, spacing, font scale, and content-width tokens.

Contrast-specific foreground tokens prevent one color from being incorrectly reused for both text and filled-control backgrounds.

## Reusable components

Current component primitives:

- container and reading width;
- stack, cluster, and responsive grid;
- generic card and governed content card;
- primary, secondary, and danger buttons;
- neutral, primary, verified, info, success, warning, and danger badges;
- loading, empty, error, success, warning, and unavailable states;
- notice variants;
- field, label, input, select, textarea, help, and field-error styles;
- responsive table wrapper and table;
- skeleton;
- visually hidden utility.

All classes are prefixed. The stylesheet does not globally restyle arbitrary module buttons, cards, headings, forms, tables, or markup.

## Content-card catalog

The visual contract provides presentation variants for:

- article;
- post;
- news;
- video;
- reel;
- book;
- PDF;
- Doctor;
- clinic;
- event;
- Marketplace item.

These are visual variants only. Native modules continue to own content, publication state, permissions, interactions, transactions, metrics, and canonical records.

## URL and media policy

Reusable state and card destinations are exact same-origin by default. The policy rejects protocol-relative, external, credential-bearing, scheme-downgrade, port-mismatch, control-character, backslash, malformed, and fragment-bearing canonical card/media URLs.

External destinations require a future separately governed integration contract rather than a permissive presentation-layer exception.

## Accessibility and responsive rules

The contract includes:

- minimum 44px-compatible interactive targets;
- visible keyboard focus;
- one keyboard link per card destination by default;
- semantic article, heading, list, and time markup;
- logical properties for RTL/LTR compatibility;
- reduced-motion handling;
- forced-colors support;
- mobile container and compact-card refinement;
- responsive table overflow;
- print-safe shadows and breaks;
- local/system fonts only;
- no remote CSS or font dependency.

## Failure behavior

The global design-system layer does not require File 00 identity services. Profile and timeline features remain fail-closed when File 00 is unavailable. File 25 Safe Mode disables File 25 visual overrides without changing native data.

An untitled content card returns no markup. Unsafe actions or images are omitted. Unknown input fields are ignored rather than serialized.

## Acceptance boundary

Source contracts and green CI do not prove visual acceptance. Real module pages, viewport and visual-regression evidence, Urdu RTL, keyboard, screen-reader, forced-colors, reduced-motion, performance, staging, rollback, and Founder acceptance remain mandatory.
