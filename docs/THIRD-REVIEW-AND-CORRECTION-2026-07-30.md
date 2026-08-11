# Third Review and Correction — 2026-07-30

## Scope reviewed

This review covers the File 25 `0.4.0` global design-system foundation, reusable visual states, File 20 token inheritance, public integration functions, Safe Mode changes, profile adoption, package checks, and CI evidence.

## Defects found and corrected

### 1. Protocol-relative URL escape in reusable state actions

The former state URL policy accepted any string beginning with `/`. A destination such as `//external.example/path` therefore bypassed the HTTP/HTTPS parser and could become an external protocol-relative link.

**Correction:** introduced `Public_URL::sanitize_same_site()` with bounded length, control-character and backslash rejection, protocol-relative and encoded-authority rejection, exact scheme/host/port matching, credential rejection, optional fragment denial, and fail-closed behavior when the site origin cannot be established.

### 2. External and downgrade destinations were not fail-closed

Reusable visual states accepted arbitrary HTTP/HTTPS hosts. That exceeded File 25's presentation ownership and could permit external-navigation or HTTPS-to-HTTP downgrade behavior.

**Correction:** state actions and content cards now allow only root-relative, safe fragment/query, or exact same-origin destinations. External navigation requires a future separately governed contract.

### 3. Translation extraction weakness in default state copy

Default copy was passed to the translation function through variable array values. Runtime translation could work, but standard WordPress extraction tools cannot reliably discover variable strings.

**Correction:** every default state string is now passed through literal translation calls.

### 4. Dark-mode semantic tokens overrode File 20-owned surface tokens

The `0.4.0` stylesheet replaced text, surface, page, border, and focus values whenever File 20 selected dark mode. This weakened File 20's canonical token authority and could create cross-module inconsistency.

**Correction:** File 25 now inherits File 20-owned surface/text tokens unchanged. It supplies only contrast-safe derivative tokens that File 20 does not currently expose. A complete dark fallback is limited to `sabri-visual-shell-degraded` mode.

### 5. Danger-button contrast was not stable across themes

One danger token served both text/border and filled-button backgrounds, while the button foreground remained white. A light dark-mode danger color could therefore produce insufficient contrast.

**Correction:** added separate `--sabri-visual-on-danger` and `--sabri-visual-on-primary` semantic foreground tokens, plus theme-aware values.

### 6. Badge background was fixed to a light color

The original badge used a fixed pale-orange background, which was unsuitable on dark surfaces.

**Correction:** added `--sabri-visual-primary-soft` and a surface-aware `color-mix()` value with a static fallback.

### 7. Shell-connected diagnostics relied only on a constant

A defined File 20 version constant could mark the visual system connected even when the integration availability filter had explicitly revoked the shell contract.

**Correction:** shell detection now requires both the File 20 constant and the monotonic dependency filter.

### 8. Global component catalog remained too small for cross-module adoption

The initial foundation covered containers, cards, buttons, badges, states, and skeletons but lacked governed content cards, form fields, tables, and notices.

**Correction:** started the next 25H segment with a reusable content-card contract and CSS primitives for fields, inputs, selects, textareas, notices, and responsive tables.

## New coding started after correction

Runtime `0.5.0` adds:

- `Public_URL` same-origin security policy;
- `Content_Cards` presentation-only renderer;
- article, post, news, video, reel, book, PDF, Doctor, clinic, event, and Marketplace visual variants;
- strict title/excerpt/meta/media/badge/action allow lists;
- no native-object ownership and no internal identifier output;
- single-link keyboard behavior by default;
- bounded metadata and ISO date output;
- lazy local media;
- public `sabri_visual_experience_render_card()` integration function;
- content-card, notice, field, input, textarea, select, and table CSS;
- print, forced-colors, reduced-motion, RTL, and responsive handling;
- dedicated PHP 8.0/8.3 tests and package checks.

## Acceptance boundary

The work is source-contract complete only. It does not establish cross-module adoption, visual-regression screenshots, exact RTL/browser evidence, staging acceptance, File 24 security/cache integration, performance acceptance, release packaging, or production readiness.
