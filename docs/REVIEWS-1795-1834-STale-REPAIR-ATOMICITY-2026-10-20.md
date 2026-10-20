# File 25 — R1795–R1834: Forty Governing/Owner Source Checks, Stale Repair CAS
**Review date:** 20 October 2026 (Pakistan Standard Time). **Audited source:** unmerged Draft PR #42 at `25b3fdf52f743cbef34a3c37eeae9cc2e2d9629e`. **Main:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **New corrective branch:** `fix/file25-r1795-1834-stale-repair-cas-20261020`.

## Governing documents and methods
The user provided **File 25 — Complete Public UI, Profile Timeline and Visual Experience, Final Harmonized Specification 2.0**. This review covers its sections 17–90, especially §22 canonical ownership, §§23–24 dependencies, §§29–38 profile presentation/actions, §§39–48 timeline, §§59–69 privacy/SEO, §72 APIs, §76 Repair, §77 migrations, §89 staging, and §90 Definition of Done. The Three Central Consolidated Governing Master Plan and the known native owner contracts of Files 00/03/08/09/11/14/17/20/21/22/23/24/26 are cross-reference boundaries.

**Exactly forty distinct targeted source/plan checks (R1795–R1834)** are recorded in `config/review1795-1834-cross-owner-source-completeness-audit.json`. These are forty focused code-presence/boundary inspections; they are **not** forty independently repeated complete code audits, installed WordPress tests, real browser tests, or production observations. Evidence classes: **34 source witnesses, 2 owner contract pending, 1 partial provider contract pending, 2 documented contract only, and 1 confirmed source defect.** A witness is never proof of full functional correctness.

## Confirmed defect and correction — R1834
**Source before fix:** `Plan_Completion::repair()` used an unguarded `update_option(INDEX_OPTION, $index)` after observing that a Timeline index had been running over 600 seconds. A concurrent rebuild could acquire a replacement lease/index between observation and write. A stale administrative Repair could then overwrite its state, contradicting the File25 §76 safe repair requirement and the existing generation-fenced rebuild path.

**Source change:** `Plan_Completion::reconcile_stale_index()` now uses the same File25-only lock and guarded SQL compare/replace system as its rebuild code. It refuses live leases and corrupt leases; reclaims an expired lock only through exact SQL compare/delete; obtains an exclusive repair generation; requires the originally observed index still to match; compares BOTH exact index and repair lease at the SQL write boundary; then releases only that exact lease. Loss of the race reports **partial**, not complete. File21 publications, File03 profiles, patient records and other owners are untouched.

**Regression:** `tests/review1595-1634-admin-repair-status.php` now invokes the actual repair method and checks failed CAS, normal CAS, live lock, worker overtaking in a simulated SQL race, expired lock reclamation, no-op behavior and status-bound admin notices. `tests/fixtures/fake-atomic-index-wpdb.php` has a new isolated repair race scenario. These are deterministic isolated mocks and do not replace real MySQL multiworker concurrency, object-cache, staging or live tests.

## Cross-file and external blockers (not silently 'fixed')
1. **File08** appointment target-bound owner contract remains unavailable to the currently accepted File25 integration; the action must remain hidden rather than using a guessed booking target.
2. **File17** target-bound Follow owner contract remains outstanding; File25 may display an action only after owner-native authorization/destination exists.
3. **File21 and other approved owners**: the publication timeline bridge is present, but additional non-post providers require legitimate public item/author/visibility contracts and real installed integration tests. File25 must not manufacture them.
4. **File20** retains structural shell ownership; **File24** retains cross-cutting security assurance; **File26** retains global search/ranking. No parallel backend or authority was created.
5. **WordPress access:** WPVibe account returned no registered sites. An attempted safe connection to `https://sabrihomeopathy.com` returned **Cloudflare HTTP 530**. This alone does not determine the failing hop or establish a root cause. No live write was attempted.
6. **Staging/Live evidence**: deployed plugin version/checksum, database/schema, wp_options/migrations, runtime errors, installed native owner API versions, backup/restore, rollback, RTL/mobile, real roles, performance, multiworker SQL and Founder acceptance remain unverified.

## Release truth
| Field | Actual status |
|---|---|
| Repository main HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at initial freeze |
| Corrective source | New unmerged child branch of PR #42 |
| Deployed plugin/version/checksum | Unknown |
| Live database/schema version | Unknown |
| Migration state | Unknown |
| Regression / exact branch CI | Must be checked on final SHA |
| Staging acceptance | Pending |
| Live verification | Not performed |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** No release, merge, deployment, database repair or site operation is authorized by a source audit or a green CI run alone. Finish: exact SHA CI → independent review → approved merge → reproducible ZIP → verified staging + rollback → Founder acceptance → deployment → live re-test + parity confirmation.
