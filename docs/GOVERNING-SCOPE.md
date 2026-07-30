# File 25 Governing Scope

This repository implements the Founder-approved plan for **File 25 — Sabri Unified Global Visual Experience and Design System**.

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

This is the same earlier File 25. It is not a new module and does not create File 26.

## Canonical ownership

- File 00 owns identity, roles, age/guardian authority, account status, and contact consent.
- File 03 owns profile master data.
- File 20 owns the global application shell, navigation, sidebars, layout resolver, widths, and shell-level base tokens.
- File 21 owns Home/News publications, posts, comments, interactions, moderation states, and social behavior.
- Files 22 and 23 own content creation and private publishing operations.
- File 24 owns security, privacy, compliance, incident management, cache governance, and resilience.
- File 25 owns the global public design system, public profile presentation, federated profile timeline projection, reusable public components and states, responsive refinement, accessibility, visual consistency, and visual-regression acceptance.

## Non-duplication law

File 25 must not duplicate the File 20 shell or File 21 social/publication backend. It must not duplicate native content bodies, comments, reactions, saves, identity documents, professional evidence, patient information, messages, appointments, marketplace negotiations, or raw analytics. Timeline data is a rebuildable read projection containing canonical native references and lightweight public display metadata only.

## Canonical public routes

- `/founder/`
- `/doctors/{public-slug}/`
- `/profile/{public-slug}/`
- role-appropriate sections such as `/timeline/`, `/knowledge/`, `/media/`, `/clinic/`, `/reviews/`, and `/about/`.

Founder and verified doctors may be publicly discoverable. Other profiles require authoritative public visibility. Minor contacts and private health activity are never public.

## Global visual scope

The same File 25 owns:

- typography, spacing, color, radius, elevation, focus, and content-width standards;
- reusable cards, buttons, badges, grids, loading, empty, error, success, warning, and unavailable states;
- mobile, tablet, and desktop refinement;
- RTL/LTR readiness, keyboard visibility, reduced motion, forced colors, and WCAG-oriented public presentation;
- familiar social interaction presentation without copying another platform;
- one distinctive Sabri visual identity across native modules.

## Delivery phases

1. 25A — Governance, numbering, ownership, and integration contracts.
2. 25B — Plugin foundation, dependencies, Safe Mode, and diagnostics.
3. 25C — File 20 shell/design-token integration.
4. 25D — Founder profile.
5. 25E — Doctor and permitted-member profiles.
6. 25F/25G — Timeline registry and File 21 provider.
7. 25H — Global design-system, reusable components, and cross-module visual contracts.
8. 25I — Optional knowledge/media/review providers and reusable content cards.
9. 25J/25K — responsive, accessibility, privacy, security, performance, SEO, and visual-regression completion.
10. 25L/25M — migration, rollback, staging acceptance, Founder sign-off, and release.

## Completion law

A page rendering, a ZIP file, or green CI alone is not production completion. Fresh install, upgrade, reversible migration, rollback, full-module visual coverage, responsive/accessibility evidence, security/privacy tests, real-user staging acceptance, deployment, and post-deployment monitoring are mandatory.
