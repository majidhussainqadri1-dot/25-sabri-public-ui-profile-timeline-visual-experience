# File 25 Governing Scope

This repository implements the Founder-approved plan for **File 25 — Complete Public UI, Profile Timeline and Visual Experience**.

## Canonical ownership

- File 00 owns identity, roles, age/guardian authority, account status, and contact consent.
- File 03 owns profile master data.
- File 20 owns the global application shell, navigation, sidebars, layout resolver, widths, and base design tokens.
- File 21 owns Home/News publications, interactions, moderation states, and the production publication timeline provider.
- Files 22 and 23 own content creation and private publishing operations.
- File 24 owns security, privacy, compliance, incident management, and resilience.
- File 25 owns public profile presentation, federated timeline projection, reusable public cards and states, responsive refinement, accessibility, and visual acceptance.

## Non-duplication law

File 25 must not duplicate native content bodies, comments, reactions, saves, identity documents, professional evidence, patient information, messages, appointments, marketplace negotiations, or raw analytics. Timeline data is a rebuildable read projection containing canonical native references and lightweight public display metadata only.

## Canonical public routes

- `/founder/`
- `/doctors/{public-slug}/`
- `/profile/{public-slug}/`
- role-appropriate sections such as `/timeline/`, `/knowledge/`, `/media/`, `/clinic/`, `/reviews/`, and `/about/`.

Founder and verified doctors may be publicly discoverable. Other profiles require authoritative public visibility. Minor contacts and private health activity are never public.

## Delivery phases

1. 25A — Governance, numbering, ownership, and integration contracts.
2. 25B — Plugin foundation, dependencies, Safe Mode, and diagnostics.
3. 25C — File 20 design-system integration.
4. 25D — Founder profile.
5. 25E — Doctor and permitted-member profiles.
6. 25F/25G — Timeline registry and File 21 production provider.
7. 25H/25I — Optional knowledge/media providers and reusable cards.
8. 25J/25K — responsive, accessibility, privacy, security, performance, and SEO completion.
9. 25L/25M — migration, rollback, staging acceptance, Founder sign-off, and release.

## Completion law

A page rendering, a ZIP file, or green CI alone is not production completion. Fresh install, upgrade, reversible migration, rollback, responsive/accessibility evidence, security/privacy tests, real-user staging acceptance, deployment, and post-deployment monitoring are mandatory.
