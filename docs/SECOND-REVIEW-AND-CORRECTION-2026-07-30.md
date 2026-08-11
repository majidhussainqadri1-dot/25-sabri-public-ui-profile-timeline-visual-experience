# File 25 Second Review and Correction — 2026-07-30

## Governing correction

The Founder confirmed that the existing File 25 is now canonically named **Sabri Unified Global Visual Experience and Design System**, with the subtitle **Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency**. No File 26 is created for this scope.

## Review basis

The review covered the current `0.3.0` source, File 20 shell boundaries, File 21 timeline integration, profile rendering, assets, Safe Mode, diagnostics, tests, documentation, and the newly clarified global visual-system ownership.

## Defects found

### 1. Canonical scope and naming drift

Several runtime and governance files still described File 25 mainly as a profile/public-experience plugin. They did not record its canonical ownership of the global public design system and could lead to a duplicate File 26.

**Correction:** Runtime identity is promoted to `0.4.0` under the canonical name. Governance, architecture, readme, changelog, PR scope, and package checks are aligned to the same decision.

### 2. Design assets were profile-only

The asset loader returned immediately outside File 25 profile routes. Other public modules therefore had no canonical File 25 semantic tokens or reusable state/component primitives.

**Correction:** A separate `design-system.css` is loaded on supported public front-end requests. Profile CSS and JavaScript remain conditional to profile routes.

### 3. No stable cross-module design-system API

Companion modules had no versioned way to discover File 25 tokens, component classes, visual states, ownership boundaries, or asset handle.

**Correction:** Added `Design_System` and `Components` contracts, public integration functions, and WordPress filters. Canonical tokens and component names cannot be silently redefined by another plugin.

### 4. Inline File 20 token bridge

The previous File 20 integration printed a profile-only inline style block. This duplicated static token mapping, complicated future Content Security Policy, and did not serve the global scope.

**Correction:** Removed the inline bridge. The local versioned stylesheet now inherits File 20 variables through CSS fallbacks.

### 5. Hard identity dependency disabled the visual system

The earlier bootstrap stopped all File 25 assets when File 00 was unavailable, although the global design system does not require identity data.

**Correction:** The global visual contract and stylesheet boot independently. Profile and timeline behavior remains fail-closed until required identity dependencies are available.

### 6. Safe Mode boundary was not nested-safe

Safe Mode stored one Boolean boundary. A nested `begin()` followed by `end()` could clear an outer fatal guard or attribute an incident to the wrong boundary.

**Correction:** Replaced the single boundary with a bounded stack. Nested boundaries restore the parent correctly, extra `end()` calls are harmless, and fatal shutdown events are emitted to the File 24 integration hook.

### 7. Global visual states were not reusable

Loading, empty, error, success, warning, and unavailable states existed only as local markup conventions.

**Correction:** Added an escaped reusable renderer and prefixed state classes, plus skeleton, cards, buttons, layout primitives, visible focus, reduced motion, and forced-colors support.

### 8. Diagnostics did not inspect the design-system contract

Site Health could report dependencies and providers but not whether File 25's global visual contract and local stylesheet existed consistently.

**Correction:** Added a direct Site Health design-system test and expanded package verification.

## Security and ownership preserved

- File 20 remains the sole global shell owner.
- File 21 remains the Home/News/social owner.
- File 25 adds no publication, comment, reaction, identity, clinical, message, appointment, or analytics store.
- Global CSS uses only prefixed component classes and semantic custom properties; it does not restyle arbitrary module markup.
- No remote font, CSS, or JavaScript dependency was introduced.
- Unsafe state action URLs and executable text are rejected.

## New executable evidence

- `tests/design-system.php`
- `tests/safe-mode.php`
- expanded PHP 8.0/8.3 CI
- expanded structure/local-asset/write-boundary verification

## Remaining acceptance gates

This correction does not prove final visual acceptance. Real Files 00/03/20/21/25 staging, full module coverage, mobile/tablet/desktop screenshots, Urdu RTL, keyboard, screen reader, zoom, forced colors, reduced motion, performance, migration, rollback, backup restoration, and Founder approval remain mandatory.
