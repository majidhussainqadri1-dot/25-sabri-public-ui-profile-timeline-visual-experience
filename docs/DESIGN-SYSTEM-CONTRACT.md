# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

## Canonical decision

This is the same existing File 25. It is not a new file and does not create File 26. The earlier title, **Complete Public UI, Profile Timeline and Visual Experience**, remains the explanatory subtitle under the broader canonical name.

## Ownership boundary

- File 20 owns the application shell: header, navigation, sidebars, mobile drawers, bottom navigation, widths, and layout resolver.
- File 21 owns Home, News Feed, posts, comments, reactions, moderation, and social behavior.
- File 25 owns the global public design system, visual consistency, profile presentation, profile timeline, responsive refinement, accessibility, reusable public components, visual states, and visual regression acceptance.

File 25 must not render a second shell or create a second publication backend.

## Runtime contract

Contract version: `1.0.0`

Public integration functions:

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_render_state(array $args = []): string
```

WordPress filters:

```text
sabri_visual_experience/contract
sabri_visual_experience/tokens
sabri_visual_experience/components
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

File 25 exposes globally unique `--sabri-visual-*` semantic tokens. Where File 20 supplies an equivalent `--sabri-shell-*` token, File 25 inherits it and provides a bounded fallback. This preserves one shell while enabling one consistent visual language across native modules.

The initial contract includes primary, strong-primary, text, muted, surface, page, border, focus, success, warning, danger, radii, spacing, font scale, and content-width tokens.

## Reusable components

Initial component primitives:

- container;
- reading width;
- stack;
- cluster;
- responsive grid;
- card;
- primary, secondary, and danger buttons;
- badge;
- loading, empty, error, success, warning, and unavailable states;
- skeleton;
- visually hidden utility.

All classes are prefixed. The stylesheet does not globally restyle arbitrary buttons, cards, headings, forms, or module markup.

## Accessibility and responsive rules

The contract includes:

- minimum 44px-compatible interactive targets;
- visible keyboard focus;
- logical properties for RTL/LTR compatibility;
- reduced-motion handling;
- forced-colors support;
- mobile container refinement;
- local/system fonts only;
- no remote CSS or font dependency.

## Failure behavior

The global design-system layer does not require File 00 identity services. Profile and timeline features remain fail-closed when File 00 is unavailable. File 25 Safe Mode disables File 25 visual overrides without changing native data.

## Acceptance boundary

Source contracts and green CI do not prove visual acceptance. Real module pages, viewport evidence, RTL, keyboard, screen-reader, forced-colors, reduced-motion, performance, staging, rollback, and Founder acceptance remain mandatory.
