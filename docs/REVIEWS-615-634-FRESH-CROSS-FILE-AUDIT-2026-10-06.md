# File 25 — Fresh Cross-File Audit Reviews 615–634 — 2026-10-06

## Evidence boundary

This record is repository-source evidence. The audited File 25 baseline was main SHA `19009a0934970d63ca60fda1fe9df1ac7288d42a`, whose GitHub CI run `1540` completed successfully. The corrections in this review form a new candidate and require their own exact-head CI. Nothing in this document proves Hostinger staging, production/live deployment, deployed package parity, live database/schema version, or live migration state.

## Governing sources

The audit used the File 25 Final Harmonized Specification 2.0 and governing addenda, the consolidated/definitive central platform plan, and current related repository source. Canonical ownership was preserved: File 20 shell/layout, File 21 publication/ProfileTimeline, File 22 authoring, File 23 private publishing operations, File 24 assurance governance, File 26 global search/ranking, and native domain owners retain their data/workflow truth.

## Twenty review scopes

| Review | Scope | Result |
|---:|---|---|
| 615 | Repository baseline and CI truth | Clean |
| 616 | File 25 plan scope/ownership | Clean |
| 617 | Central plan / File 20 visual boundary | Clean |
| 618 | File 00 identity/membership | Clean |
| 619 | File 03 profile/routes/edit/privacy/report | Clean |
| 620 | File 07 directory/discovery boundary | Clean |
| 621 | File 08 clinic/appointment action contract | Defect corrected |
| 622 | File 09 verification | Clean |
| 623 | File 17 Follow/Message action contracts | Defect corrected |
| 624 | File 18 Marketplace boundary | Clean |
| 625 | File 20 shell/admin integration | Clean |
| 626 | File 21 timeline/publication bridge | Clean |
| 627 | File 22 Composer destination | Clean |
| 628 | File 23 Publishing Dashboard | Clean |
| 629 | File 24 security/privacy assurance | Clean |
| 630 | File 26 search/ranking boundary | Clean |
| 631 | REST/component/action security | Clean |
| 632 | Safe Mode/repair/migration/rollback | Clean |
| 633 | Accessibility/performance/i18n/SEO/metrics | Clean in source scope; external evidence pending |
| 634 | Release/status/documentation truth | Defect corrected |

## Defect 621 — File 08 Appointment action assumption

Current File 08 main SHA `70541974ce0ffb16aebef557c3016eb7447662f4` publishes runtime `1.2.15`, schema `3.4.0`, public-clinic contract `1.1.0`, the read APIs `swc_get_public_clinic_projection()` / `swc_public_clinic_projection_contract()`, and canonical booking routes such as `/appointments/book/{doctor_or_clinic}`. Its bounded File 25 clinic projection deliberately exposes only name/address/country/city/hours/timezone and does not expose a clinic public reference or a target-bound profile booking URL.

File 25 previously called an unpublished `sabri_file08_public_clinic_projection_v1` filter as though it were an owner action contract. That was repository-contract drift. The correction removes this invented dependency. File 25 now hides Appointment until File 08 publishes an authoritative profile-user→booking-destination contract. File 25 does not query File 08 storage or guess a clinic reference.

## Defect 623 — File 17 Follow/Message action assumptions

Current File 17 main SHA `8ae656e51796d1f05865d8be5dca2480443d79ca` owns relationship and messaging state. Its current source includes the relationship graph and Messages routes, but the reviewed source does not publish the File25-specific `sabri_file17_profile_action_url_v1` or `sabri_network_message_profile_url` hooks File 25 had started to rely upon.

The correction removes both invented hook dependencies. Follow remains hidden until File 17 publishes a target-bound owner destination. Message is rendered only if File 03's canonical public personal-site projection already contains a concrete same-site `internal_message_url`; otherwise Message also hides. A generic Network page is never mislabeled as a target-bound action.

## Defect 634 — stale source-status evidence

README and completion metadata still described the older Reviews 595–614 branch and its pending exact-head CI even after main advanced to `19009a...` and main CI run `1540` passed. Current documentation now distinguishes the successfully tested baseline main from the new Reviews 615–634 candidate, whose exact-head CI is a separate gate.

## Result

All File 25-owned source defects discovered in Reviews 615–634 were corrected. Two desired cross-file actions remain unavailable because their canonical owners do not currently publish the required destination contracts; File 25 records these as dependency limitations and fails closed instead of duplicating owner logic.

External acceptance remains pending: Hostinger staging, real-role/browser/RTL/accessibility/performance evidence, Founder sign-off, production/live deployment, exact deployed-code parity, live database/schema verification, migration-state verification, rollback rehearsal and operational monitoring.
