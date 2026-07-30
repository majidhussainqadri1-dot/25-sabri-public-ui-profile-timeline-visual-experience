# Decision Log

## 2026-07-30 — Canonical name upgraded without a new file

The existing module is now canonically named **File 25 — Sabri Unified Global Visual Experience and Design System**.

Its explanatory subtitle is **Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency**. The former title remains inside this subtitle. This is an upgrade of the same File 25, not a new module.

## 2026-07-30 — File 26 is prohibited for this scope

Global visual design, Facebook-familiar social presentation, profile timeline, responsive polish, accessibility, and visual consistency must not be split into a duplicate File 26. A second visual owner would create competing CSS, tokens, components, and integration rules.

## 2026-07-30 — File 20 shell and File 25 visual ownership are separate

File 20 remains the only global application-shell owner. File 25 owns the global public visual system rendered inside that shell and inherits File 20 shell tokens through semantic `--sabri-visual-*` aliases.

## 2026-07-30 — Global design system is identity-independent

The File 25 semantic token and component layer may boot without File 00. Identity-dependent profile and timeline behavior remains fail-closed when File 00 is unavailable.

## 2026-07-30 — Prefixed components only

Global design-system CSS uses `sabri-ui-*` component classes and `--sabri-visual-*` tokens. It must not broadly restyle arbitrary module elements or reproduce File 20 shell markup.

## 2026-07-30 — No inline token bridge

File 20 token inheritance is implemented in the local versioned stylesheet rather than a profile-only inline `<style>` block. This reduces duplication and supports future Content Security Policy hardening.

## 2026-07-30 — Nested Safe Mode boundaries

Safe Mode must preserve outer recovery boundaries during nested work. Inner `end()` calls may not disable outer fatal monitoring or misattribute incidents.

## 2026-07-30 — File number frozen

The module is **File 25**, not File 22 or File 24.

## 2026-07-30 — No duplicate shell

File 20 remains the only global application-shell owner. File 25 inherits its layout and base design tokens.

## 2026-07-30 — No duplicate publication backend

The profile timeline is a read-only federated projection. Native modules remain canonical.

## 2026-07-30 — File 00 authority is non-overridable

Founder identity, account approval, age, public visibility, and professional eligibility must be established by File 00. File 25 hooks may narrow presentation but may not elevate identity or widen access.

## 2026-07-30 — Privacy-first profiles

Founder and verified Doctors may be public. Other profiles require approved adult identity and authoritative public visibility. Minor, unknown-age, private/member-only, rejected, and suspended states fail closed.

## 2026-07-30 — Public contact is separately authorized

Ordinary-member Phone/WhatsApp display requires both a public approved profile and explicit File 03 public-contact consent. Founder and verified-Doctor professional contact remains subject to age, status, and native approval.

## 2026-07-30 — Graded dependencies

File 00 is the hard profile/timeline runtime dependency. Files 03, 20, 21, and 24 are mandatory production integrations but do not all block early foundation coding. The File 25 global design-system layer itself remains identity-independent.

## 2026-07-30 — Active File 21 may not be bypassed

The WordPress-post compatibility provider is registered only while File 21 is absent. If File 21 is active but incompatible, File 25 shows an honest unavailable timeline instead of querying around File 21.

## 2026-07-30 — Federated pagination contract frozen

Providers return a bounded newest-candidate pool beginning at provider page one. File 25 performs final cross-provider validation, merge, sort, deduplication, and pagination.

## 2026-07-30 — Same-site canonical timeline

Foundation timeline items must point to credential-free, fragment-free, exact same-scheme/host/port canonical destinations. External destination support, if ever approved, requires a separate governed provider contract.

## 2026-07-30 — No dead public tabs

File 25 exposes only sections it can render truthfully. Filters may hide current sections but may not create new tabs until a canonical section-provider registry exists.

## 2026-07-30 — No-store until File 24 cache contract

Public profile HTML and REST responses remain non-cacheable until File 24 supplies an audited public/private cache partition and invalidation contract.

## 2026-07-30 — Safe Mode is local recovery only

File 25 Safe Mode disables File 25 overrides, records bounded incident metadata, and permits authenticated retry. File 24 remains the canonical security and incident owner.

## 2026-07-30 — Green CI is not completion

Source lint, tests, and CI are necessary but do not replace fresh install, upgrade, migration, rollback, staging, real-user, responsive, accessibility, performance, deployment, and monitoring evidence.
