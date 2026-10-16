# File 25 — Reviews 835–874: forty targeted plan/source inspections

**Date:** 16 October 2026. **Verified baseline:** File25 GitHub `main` `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`, corrective stacked source baseline PR #22 `edb226a79769bf15ff4c443ca24ecd139c0c2e6d`. Previous source corrections are in the still-unmerged PRs #20 → #21 → #22; none is the deployed artifact.

**Normative comparison:** File25 Final Harmonized Master Plan v2.0, including File20 vs File25 ownership, federated read-only Timeline, native File03 public-profile/consent projection, File08/17 native action owner constraints, public search, privacy/SEO, QA/rollback; central consolidated governing plan v3.0; corresponding companion docs and current File08/File20/File21 repository sources.

## What forty means

Forty **distinct and explicit source-code inspections**, not forty separate complete test runs or forty approved real-host staging workflows. A 'source witness' means the reviewed implementation point exists. Source presence is not independent runtime behavioral proof. Owner acceptance and exact deployed parity are separate facts.

| Round | Reviewed plan/code scope | Finding |
|---:|---|---|
| 835 | File 03 canonical public profile projection | Source witness; live unverified |
| 836 | File 00/03 public visibility revoke-only | Source witness; live unverified |
| 837 | Minor contact suppression | Source witness; live unverified |
| 838 | Public contact consent monotonicity | Source witness; live unverified |
| 839 | No WordPress biography fallback | Source witness; live unverified |
| 840 | No WordPress display-name fallback | Source witness; live unverified |
| 841 | Monotonic approved public text | Source witness; live unverified |
| 842 | Same-origin canonical public URL | Source witness; live unverified |
| 843 | Founder/Doctor/Member canonical routes | Source witness; live unverified |
| 844 | File 03 public identity DTO version | Source witness; live unverified |
| 845 | File 09 doctor verification freshness | Source witness; live unverified |
| 846 | File 08 clinic projection version adapter | Source witness; live unverified |
| 847 | File 20 shell presentation ownership | Source witness; live unverified |
| 848 | File 24 central policy integration | Source witness; live unverified |
| 849 | HTML global public visibility preference | Source witness; live unverified |
| 850 | REST global public visibility preference | Source witness; live unverified |
| 851 | REST no-store and private cache | Source witness; live unverified |
| 852 | REST ETag bound to response DTO | Source witness; live unverified |
| 853 | REST local search preference parity | Source witness; live unverified |
| 854 | REST invalid-search must not become unfiltered | **Defect found → code corrected** |
| 855 | File 17 Follow action cannot be revived by later filter | **Defect found → code corrected** |
| 856 | File 08 Appointment action cannot be revived by later filter | **Defect found → code corrected** |
| 857 | Profile-action same-site URL guard | Source witness; live unverified |
| 858 | Preview suppresses action links | Source witness; live unverified |
| 859 | Native File 03 edit/privacy destination binding | Source witness; live unverified |
| 860 | File 03-approved native message URL consumption | Source witness; live unverified |
| 861 | Unpublished File 08 booking-boundary honesty | Source witness; live unverified |
| 862 | Bounded timeline provider candidate query | Source witness; live unverified |
| 863 | Timeline canonical item deduplication | Source witness; live unverified |
| 864 | Timeline public DTO projection | Source witness; live unverified |
| 865 | Section-provider non-ownership of native content | Source witness; live unverified |
| 866 | Section batch atomic rejection | Source witness; live unverified |
| 867 | Section canonical-key deduplication | Source witness; live unverified |
| 868 | Escaped future-profile JSON payload | Source witness; live unverified |
| 869 | Future owner-only quality/integrity payload | Source witness; live unverified |
| 870 | Same-site public citation/relationship URLs | Source witness; live unverified |
| 871 | Idempotent upgrade and schema checks | Source witness; live unverified |
| 872 | Data-free Safe Mode and recovery | Source witness; live unverified |
| 873 | Staging acceptance not self-certified | Source witness; live unverified |
| 874 | Live deployment not self-certified | Source witness; live unverified |

## Three confirmed File25 source defects — root causes and fixes

**R854 — invalid REST search silently expands to unfiltered results.** Public timeline route `search` sanitizer used `Plan_Completion::normalize_search_query` on any text and replaced nonscalar input with `''`. When an oversized/invalid query was normalized to `''` before the strict validator, the endpoint could treat it as an intentional *unfiltered* request. **Fix:** only normalize an input that already passes `exact_optional_search`; leave malformed input intact for rejection. `tests/review835-874-native-action-rest-search.php` invokes the actual `register_rest_route` callbacks and checks invalid length, controls, format-character injection and arrays, plus valid search and legitimate empty search.

**R855 — File17 Follow action is not truly fail-closed under subsequent filters.** The Current Companion adapter suppresses the Follow target because no approved File17 target-bound destination exists, but the final File25 `Plan_Completion::action_url` previously accepted the output of an unrestricted filter chain. A later filter could return a plausible same-site URL and reactivate Follow. **Fix:** deny Follow *after all consumers*, inside the final action presenter, without running the unaccepted action filter. A future published/verified File17 contract must remove this temporary gate via reviewed source change, not a silent extension.

**R856 — File08 Appointment action has the same filter-revival defect.** File08 owns clinic/booking and does not currently provide an accepted mapping from Profile user to booking destination, yet an arbitrary same-origin filtered link could appear. **Fix:** same final presentation fail-closed guard, leaving actions such as approved message/report/profile edit unaffected. The regression feeds a fabricated same-origin booking URL into the filter and proves it never appears.

**Classification:** Three source defects were identified and corrected on the review branch. The PHP regression tests establish targeted source behavior when they pass, *not* deployed operational resolution. No new File08/File17 owner destination was invented.

## Extra audit and integration checks

- Source File08 `main` `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`: published public clinic DTO family retained; booking destination contract remains a separate owner responsibility.
- Source File20 `main` `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`: controls header, navigation, skip link and layout; no parallel shell added.
- Source File21 `main` `f2eb7e95ddea327af36ea725ffb923b029f885e6`: keeps publication and post Timeline truth; no duplicate post backend or analytics owner in File25.
- File03 public identity, contacts and public profile projection must still be enforced separately from visual presentation. File09 verifies eligible doctors; File24 owns platform security governance.
- Current File25 public REST no-store, owner privacy, canonical links and scoped robots checks have source witnesses but are not taken as installed/live parity.
- Profile-bound extended Timeline provider acceptance, File08 Appointment, File17 Follow, Hostinger staging and real role/privacy/RTL/accessibility testing remain external gates.

## Mandatory release status (not promoted by source CI)

| Status | Current evidence |
|---|---|
| Repository HEAD (`main`) | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at inspection |
| Corrective branch | Stacked on unmerged PR #22; check latest SHA and exact SHA CI |
| Active Deployed Version | **Unverified** |
| Live DB / Schema Version | **Unverified** |
| Migration State | **Unverified** |
| Hostinger Staging Acceptance | **Not established** |
| Live Verification Status | **Not performed** |

The exact deployed code has not been independently acquired; **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** A GitHub fix is not a live fix. Only fix → exact-head tests → deterministic package → approved staging → deploy → live re-test → parity confirmation may close a live incident.
