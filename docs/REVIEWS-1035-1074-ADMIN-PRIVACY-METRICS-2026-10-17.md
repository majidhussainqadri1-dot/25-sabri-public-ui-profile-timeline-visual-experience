# File 25 — Reviews 1035–1074: 40 targeted source inspections

**Date:** 17 October 2026. **Repository baseline:** main `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. Parent source is unmerged draft PR #27 `6b4ab2eda27706a61819c455634f1142f574dbbd`, stacked on unmerged PRs #20–#26. GitHub evidence is source truth only.

## Actual governing Word documents reviewed

- File25 Complete Public UI Profile Timeline Visual Experience, specifically §64 server-side capability, object permissions, §65 privacy, §69 public metric thresholds, §71 admin, §72 REST, QA/staging §§80–90.
- Sabri Three Central Plans Consolidated Governing Master Plan 2026, specifically §15 roles and privileges, §24 canonical ownership, §37 privacy lifecycle, §38 security's deny-by-default requirement, §§48–50 integration/staging Definition of Done.
- File03 public profile consent, File08 clinic, File09 native Doctor verification, File17 Follow, File20 shell, File21 publication, File24 central security boundaries.

Forty **separate targeted source checks** were conducted. These were **not** forty entire WordPress test executions, forty staging journeys, or proof of the currently installed production package.

| Review | Governance/source contract | Finding |
|---:|---|---|
| 1035 | Immutable native owner capability floor on operations | **Source defect corrected** |
| 1036 | Extension admin capability cannot reduce RBAC | **Source defect corrected** |
| 1037 | Unauthenticated administrative API denied | Existing source evidence |
| 1038 | Additional extension capability checked separately | Existing source evidence |
| 1039 | High-risk authorization exact true required | Existing source evidence |
| 1040 | Unknown admin preference fields rejected | Existing source evidence |
| 1041 | Revision conflict (optimistic locking) | Existing source evidence |
| 1042 | REST mutation preflight gate | Existing source evidence |
| 1043 | Admin post nonce anti-CSRF | Existing source evidence |
| 1044 | Approved 14 public tabs administrative persistence | Existing source evidence |
| 1045 | Malformed critical public booleans fail closed | Existing source evidence |
| 1046 | File00 native assertions strictly typed | Existing source evidence |
| 1047 | Malformed profile visibility hook fails closed | **Source defect corrected** |
| 1048 | Nonstring File03 visibility hook cannot cast to public | **Source defect corrected** |
| 1049 | File03 public profile contract binding | Existing source evidence |
| 1050 | File09 Doctor verification native source | Existing source evidence |
| 1051 | File08 Clinic projection version/owner boundary | Existing source evidence |
| 1052 | File03 eligibility cannot be enlarged by visual filter | Existing source evidence |
| 1053 | Minor contacts forced private | Existing source evidence |
| 1054 | Global public metrics admin opt-out | Existing source evidence |
| 1055 | Malformed metric threshold never becomes zero | **Source defect corrected** |
| 1056 | Below-threshold metric aggregate never leaks | **Source defect corrected** |
| 1057 | Public metrics exact allowlisted keys | Existing source evidence |
| 1058 | Public metrics bounded integer value | Existing source evidence |
| 1059 | Public metric allow gate accepts exact true | Existing source evidence |
| 1060 | Profile robots hook scoped only to File25 | Existing source evidence |
| 1061 | Private profile OG/JSONLD suppression | Existing source evidence |
| 1062 | Private profile cache no-store | Existing source evidence |
| 1063 | Unavailable profile uses 404/410 no-cache | Existing source evidence |
| 1064 | Public REST visibility preference respected | Existing source evidence |
| 1065 | Public REST no-store cache headers | Existing source evidence |
| 1066 | REST quota storage failure rejected | Existing source evidence |
| 1067 | File20 structural shell ownership remains distinct | Existing source evidence |
| 1068 | File24 centralized security integration separate | Existing source evidence |
| 1069 | Same-origin canonical URL boundary | Existing source evidence |
| 1070 | Public Timeline author identity verification | Existing source evidence |
| 1071 | Timeline native provider metadata exact identity | Existing source evidence |
| 1072 | Section provider invalid batch atomicity | Existing source evidence |
| 1073 | Safe Mode prevents public indexing | Existing source evidence |
| 1074 | No File25 duplicate File20 global header | Existing source evidence |

## Corrective accounting

- **40** specific checks; **34** source witnesses; **six** check scopes requiring source corrections; **three** distinct root causes. A pair of checks can be two consequences of one source defect.
- **R1035–1036: Minimum owner capability could be silently lowered.** `Plan_Completion::can_operate()` previously tested only `capability_for()`, which a filter could replace with `read`. Authenticated users with read privilege could then pass admin setting/repair preflight instead of the native `manage_options` floor. The correction requires **both** the immutable native operation capability and an optional filter-provided stricter capability. High-risk explicit-owner authorization is preserved.
- **R1047–1048: Malformed File03 public-visibility restrictions failed open.** `Native_Integration::public_visibility()` previously returned original `public` on invalid filter output and cast nonstring values to string. The correction checks exact allowed strings `public`, `members`, `private` and fails closed to `private` on malformed response, without adding any new public-eligibility authority.
- **R1055–1056: Invalid metric thresholds could show low-count aggregates.** A malformed `public_metric_minimum` changed to `0`, potentially removing a privacy threshold. Now that metric is omitted on invalid threshold. Valid nonnegative integer thresholds and `public_metric_allowed === true` remain authoritative.

## Executable regressions

- `tests/review1035-1074-owner-capability-metrics.php`: actual owner capability preflight under simulated lower-capability extension, valid administrator, and public metrics with invalid, low/high, approved/revoked thresholds.
- `tests/review1035-1074-native-visibility.php`: native File00 and File03 fixtures call actual `Native_Integration::public_visibility()`, checking valid `public`/`members`/`private` and malformed string/array/object/boolean values. 
- Tests are enrolled in the Composer governed manifest and must pass on the **exact corrective HEAD**.

## Separate owner and release truth

There are **no** changes to File03 identity storage, File08 booking ownership, File09 Doctor verification, File17 Follow state, File20 structural shell, File21 publications or File24 security authority. This source-level patch does not supply missing File08/17 native destination contracts.

| Required category | Evidence status |
|---|---|
| Repository main HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at audit freeze |
| Corrective branch | Unmerged stacked source candidate |
| Active Deployed Version | **Unverified** |
| DB/schema version | **Unverified** |
| wp_options and migration state | **Unverified** |
| Installed-package checksum parity | **Unverified** |
| Staging real-role/RBAC/privacy/RTL acceptance | **Not performed** |
| Founder acceptance | **Not established** |
| Live Verification Status | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** Do not treat exact-head source CI as installed runtime verification. A controlled staging artifact, role tests, DB/schema/migration evidence, deployment, live re-test and checksum parity are required before resolving a live defect.
