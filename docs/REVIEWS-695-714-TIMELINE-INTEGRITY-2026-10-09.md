# File 25 — Reviews 695–714: Timeline Integrity and Cross-File Source Audit
Date: 2026-10-09

## Frozen starting evidence
Repository main: `3075224089506fed19af1441ebf3556c2d5230b5`.
Exact-main CI: run `1784`, ID `37904435708`, success.
This is **repository truth**. Installed staging, deployed package, live schema, migration state and operational acceptance were **not** verified.

Governing sources: File 25 Final Harmonized Specification 2.0 (§§39–48, 60, 68, 72, 89–90) and the consolidated central master plan. Companion source HEADs were frozen separately in `config/review695-714-timeline-integrity-ledger.json`.

## Twenty independent scopes
| Round | Scope | Source result |
|---|---|---|
| 695 | Starting HEAD/CI | Clean |
| 696 | Time-bound pin approvals | Corrected |
| 697 | Multi-provider aggregate pagination | Corrected |
| 698 | Overflow-safe page offsets | Corrected |
| 699 | False positive retrieval truncation | Corrected |
| 700 | Older matches under secondary filters | Corrected |
| 701 | True bounded oldest ordering | Corrected |
| 702 | Invalid page rejection | Clean |
| 703 | Atomic provider failure isolation | Clean |
| 704 | Private identifier and audit redaction | Clean |
| 705 | Same-site canonicals and deduplication | Clean |
| 706 | Corrections/retractions safety | Clean |
| 707 | REST/HTML filtering parity | Clean |
| 708 | Filtered URL noindex | Clean |
| 709 | Accessible pagination and Back state | Clean |
| 710 | Files 03/08/09 source boundaries | Clean |
| 711 | Files 14/17/20 source boundaries | Clean |
| 712 | Files 21/22/23 source boundaries | Clean |
| 713 | Files 24/26 source boundaries | Clean |
| 714 | Richer non-post timeline owners | Owner-contract dependency |

**Result:** 6 defect rounds, 13 clean source-review rounds, 1 external owner-contract dependency round.

## Corrected root causes
**696 — Pinning had a recorded time window but did not enforce it.** An approved pin whose start was in the future or end was in the past could still receive ordering preference. Pin authority now requires current UTC time in the inclusive approved window and the exact `profile-timeline` presentation surface. Invalid, expired, future or foreign-surface pins lose ordering weight, not public item visibility. Internal audit evidence remains redacted from public output.

**697 — Multi-provider pagination used one provider's page ceiling.** With two or more authorizing providers, pages beyond the first 500-item source window could be hidden. Maximum page is now bounded by the count of registered providers times the 500-item per-provider cap.

**698 — Valid but huge page integers risked arithmetic overflow.** The service now rejects any page above the absolute bounded aggregate capacity **before** multiplying `(page - 1) * per_page`, returning a transparent empty/truncated result with the requested page preserved.

**699 — Ordinary look-ahead was incorrectly described as hard truncation.** A provider returning 21 candidate items to support a 20-item page is normal. A safe-retrieval-limit warning now requires the real 500-item per-provider cap, not an ordinary page look-ahead. Historical Review 63 was updated to assert both ordinary look-ahead and actual hard-cap behavior.

**700 — Page-one-only post-filtering hid older matches.** The service now obtains a bounded 500-item candidate window when processing year, language, topic, Corrections or search refinement. Native owner authorization and strict privacy filters remain intact.

**701 — Oldest sorting applied only to newest candidates.** `sort=oldest` now uses the bounded full candidate window. Where source history exceeds 500 per provider, truncation remains explicit; this implementation does not claim exhaustive unbounded history.

## Behavioral regression evidence
`tests/review695-714-timeline-integrity.php` checks normal page look-ahead, history filtered by topic/language/year, oldest ordering, page 26 across two independent providers, `PHP_INT_MAX` page overflow and active/future/expired/wrong-surface pin authority.

## Unresolved native-owner contracts
- File 08: profile-specific canonical appointment booking destination contract.
- File 17: target-bound Follow destination contract.
- Richer non-post timeline content: official owner-authorized timeline projections beyond File 21's approved post stream.

File 25 does not manufacture foreign publication consent, add another global search backend, duplicate File 20's shell or seize File 24's assurance role.

## Acceptance distinction
Source-level fixes and automated tests are not Hostinger staging acceptance and are not live deployment verification. Exact deployed version, installed package parity, live DB/schema version and migration state remain **unverified**. Staging, production, real-device visual regression, rollback and live re-test are separate mandatory gates.
