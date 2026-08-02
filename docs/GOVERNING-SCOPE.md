# File 25 Governing Scope

This repository implements the Founder-approved scope for:

## File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

This is the existing File 25. No File 26 is created.

## Current governing baseline

- Sabri Social Homeopathy Platform Definitive Master Plan 2026 `v3.0`.
- File 20 Harmonized Master Plan 2026 `v4.1`.
- File 00 runtime `1.2.4`, public assertion contract `1.1.2`.
- File 25 corrective source candidate `0.14.0`, schema `2`.

## Canonical ownership

- **File 00:** identity, membership state, Founder identity, age/guardian policy, approval, suspension, eligibility, professional eligibility, and public-profile authorization.
- **File 03:** profile master data, profile media, and explicit public-contact consent.
- **File 08:** clinic, doctor availability, appointment, and scheduling truth.
- **File 09:** Doctor application, private credential evidence, independent review, verification, renewal, suspension, revocation, appeal, and immutable approved professional snapshot.
- **File 18:** Marketplace seller, listing, moderation, contact, offer, metric, transaction, and direct-deal truth.
- **File 20:** global application shell, routes, header, navigation, sidebars, drawers, layout resolver, widths, and structural recovery.
- **File 21:** Home/News publications, comments, interactions, moderation states, and production publication timeline provider.
- **Files 22 and 23:** creation orchestration and private publishing operations.
- **File 24:** security, privacy, compliance, incident, audit-evidence, cache-governance, and resilience plane.
- **File 25:** public visual system, visual tokens, public profile presentation, federated read projections, reusable cards/states, responsive refinement, accessibility, SEO presentation, visual regression, and visual acceptance.

## Authoritative read laws

1. File 25 consumes File 00 only through runtime `>=1.2.4 <1.3.0`, exact contract `1.1.2`, `SMC_Contracts::assertions()`, and Founder helper functions.
2. File 25 does not query File 00 applications, professional credentials, clinics, guardian records, or contact-verification tables.
3. Doctor public status requires both File 00 eligible/can-practice assertions and File 09 current verified/approved decision.
4. Professional presentation fields come only from File 09's immutable approved snapshot; private credential evidence and license numbers are not projected.
5. File 25 performs no age calculation. Explicit File 00 minor/guardian assertions govern; unknown ordinary contact state fails closed.
6. Clinic projection requires File 08 public contract `1.0.0`; until the owner contract exists, the clinic section remains unavailable.
7. File 25 consumes Marketplace data only through File 18 owner-executed public DTO APIs. Direct File 18 table queries are prohibited.
8. File 03 contact consent remains mandatory even when another module exposes professional or clinic contact data.

## Non-duplication law

File 25 must not duplicate native content bodies, comments, reactions, saves, identity documents, professional evidence, patient information, messages, appointments, Marketplace transactions, clinic records, or raw analytics. Timeline and section data are rebuildable read projections containing canonical references and lightweight public display metadata only.

A File 25 card type is a presentation variant. It transfers no permission, workflow, moderation, metric, transaction, or native-data ownership.

## Link and media law

Reusable File 25 components accept exact same-origin or root-relative destinations only. Protocol-relative, cross-origin, credential-bearing, downgrade, mismatched-port, control-character, backslash, malformed, and forbidden-fragment canonical URLs fail closed. External destinations require a separate governed contract.

## Public routes

- `/founder/`
- `/doctors/{public-slug}/`
- `/profile/{public-slug}/`
- truthful available sections such as `/timeline/`, `/knowledge/`, `/media/`, `/clinic/`, `/reviews/`, `/research/`, `/marketplace/`, and `/about/`.

Founder and authoritatively verified Doctors may be publicly discoverable. Other profiles require File 00 public-profile authorization. Minor contacts and private health activity are never public.

## Completion law

A rendered page, green CI, deterministic ZIP, or installed-package preflight is not production completion. Exact Hostinger staging, owner-contract integration, fresh install, upgrade, rollback, restore, responsive/accessibility evidence, security/privacy testing, real-user workflows, deployment, monitoring, and Founder acceptance remain mandatory.
