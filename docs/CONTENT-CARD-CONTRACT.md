# File 25 Reusable Content-Card Contract

## Ownership

File 25 owns only the card's visual presentation. The native module remains the sole owner of the underlying post, news item, video, reel, book, PDF, Doctor profile, clinic, event, or Marketplace item.

The renderer does not accept or publish native IDs, WordPress user IDs, patient IDs, provider diagnostics, comments, reactions, private metrics, clinical data, identity evidence, or write operations.

## Public API

```php
sabri_visual_experience_render_card(array $args = []): string
```

Canonical class:

```text
Sabri\PublicExperience\Content_Cards
```

## Supported visual types

- `article`
- `post`
- `news`
- `video`
- `reel`
- `book`
- `pdf`
- `doctor`
- `clinic`
- `event`
- `marketplace-item`

A visual type does not transfer data or business ownership to File 25.

## Accepted display fields

- `type`
- `title` — required
- `url` — exact same-origin or root-relative
- `excerpt`
- `eyebrow`
- `image_url` — exact same-origin or root-relative
- `image_alt`
- `badge`
- `badge_tone`
- `meta` — maximum six unique entries
- `published_at`
- `date_display`
- `action_label`
- `compact`

All other fields are ignored.

## Link policy

The renderer rejects:

- `javascript:` and non-HTTP schemes;
- protocol-relative URLs;
- cross-origin URLs;
- credential-bearing URLs;
- HTTPS-to-HTTP downgrade destinations;
- mismatched ports;
- control characters and backslash authority escapes;
- fragments on canonical card/media URLs.

When `action_label` is absent, the title is the only link. When an action label is present, the title becomes plain text and the action is the only link. This prevents duplicate keyboard stops to the same destination.

## Accessibility

- semantic `<article>` structure;
- heading-based title;
- explicit image alternative text support;
- semantic `<time>` output;
- bounded metadata list;
- contextual action accessible name;
- visible focus inherited from the File 25 design system;
- RTL/LTR logical CSS;
- responsive compact-card fallback;
- forced-colors, reduced-motion, and print handling.

## Failure behavior

An untitled card returns an empty string rather than fabricating content. Unsafe URLs or images are omitted while the remaining safe card content may still render.
