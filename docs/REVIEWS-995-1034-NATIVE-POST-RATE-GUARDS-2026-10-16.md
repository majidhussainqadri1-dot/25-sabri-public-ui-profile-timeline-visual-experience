# File 25 — Reviews 995–1034: 40 native public-projection and rate-limit checks

**Review date:** 2026-10-16. **Main source baseline:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Corrective parent:** unmerged PR #26 `70ad2ad8856b3938114931ba27904d3820b910ec`. This is a *separate*, stacked, unmerged source candidate; no installed runtime evidence has been obtained.

## Governing Word-plan basis

1. File25 Final Harmonized Plan: §§40–48 (public-author Timeline and native providers), §§60–65 (security/privacy and availability), §72 (permission callbacks, rate limiting, strict validation), §§80–90 (security, role and staging acceptance).
2. Consolidated Three Central Governing Master Plan: §§11–12,16 (privacy, accounts and publishing authority), §§23–25 (native owner, API/event contracts), §§36–42 (privacy and resilience), §§47–50 (deployment and Definition of Done).
3. File21 Home/News Plan: §12 author Timeline bridge, §22 native adapters, §§26–27 patient/privacy/accessibility, §§32–36 QA and staging.
4. Source-owner comparison: File03, File08, File17, File20, File21 repository main SHAs are in `config/review995-1034-native-post-rate-guards-audit.json`. File24's exact HEAD was not established this turn, so its status is **unverified**, rather than an inferred source version.

**Scope accuracy:** These are forty *distinct targeted source/requirement inspections*, **not forty full WordPress execution cycles**, and code presence cannot confirm the installed site behaves correctly.

## All forty source inspections

| Round | Source / cross-owner requirement | Finding |
|---:|---|---|
| 995 | WordPress native fallback limited to post type | Source witness; runtime unverified |
| 996 | WordPress public post approval requires explicit true | **Defect corrected on source branch** |
| 997 | WordPress public approval rejects truthy strings | **Defect corrected on source branch** |
| 998 | WordPress post excludes password protection | Source witness; runtime unverified |
| 999 | WordPress exact author ID recheck | Source witness; runtime unverified |
| 1000 | WordPress publish status only | Source witness; runtime unverified |
| 1001 | File21 native ProfileTimeline projection owner | Source witness; runtime unverified |
| 1002 | File21 native status error gate | Source witness; runtime unverified |
| 1003 | File21 bounded native pagination | Source witness; runtime unverified |
| 1004 | File21 safe Normalized_Timeline_Item construction | Source witness; runtime unverified |
| 1005 | REST rate limit requires usable storage | **Defect corrected on source branch** |
| 1006 | REST quota writes cannot fail open | **Defect corrected on source branch** |
| 1007 | REST anonymous/user IP bucket HMAC | Source witness; runtime unverified |
| 1008 | REST rate-limit window bounded | Source witness; runtime unverified |
| 1009 | REST per-window threshold bounded | Source witness; runtime unverified |
| 1010 | REST public response Cache-Control no-store | Source witness; runtime unverified |
| 1011 | REST conditional ETag | Source witness; runtime unverified |
| 1012 | REST Last-Modified guard | Source witness; runtime unverified |
| 1013 | REST global profile visibility opt-out | Source witness; runtime unverified |
| 1014 | REST Founder timeline strict filter schemas | Source witness; runtime unverified |
| 1015 | REST profiles via File03-approved public projection | Source witness; runtime unverified |
| 1016 | File03 public visibility cannot be enlarged by filter | Source witness; runtime unverified |
| 1017 | File00 membership DTO strict Boolean fields | Source witness; runtime unverified |
| 1018 | File09 Doctor verification contract version | Source witness; runtime unverified |
| 1019 | File08 native clinic contract version gate | Source witness; runtime unverified |
| 1020 | File17 Follow target absent fails closed | Source witness; runtime unverified |
| 1021 | Timeline provider maturity/contract checks | Source witness; runtime unverified |
| 1022 | Timeline exact author/profile ID checks | Source witness; runtime unverified |
| 1023 | Timeline provider batch atomic source identity | Source witness; runtime unverified |
| 1024 | Timeline canonical item dedup across providers | Source witness; runtime unverified |
| 1025 | Timeline public DTO omits raw native identity | Source witness; runtime unverified |
| 1026 | Timeline pin temporal and actor audit boundaries | Source witness; runtime unverified |
| 1027 | Public section card DTO normalization | Source witness; runtime unverified |
| 1028 | Public section malformed provider batch rejection | Source witness; runtime unverified |
| 1029 | File03 public profile does not fall back to raw WP bio | Source witness; runtime unverified |
| 1030 | File20 exclusive shell structural ownership | Source witness; runtime unverified |
| 1031 | File24 separated security integration | Source witness; runtime unverified |
| 1032 | Profile canonical same-site URL sanitizer | Source witness; runtime unverified |
| 1033 | Safe Mode public robots noindex | Source witness; runtime unverified |
| 1034 | GitHub staging/live deployment evidence separated | Source witness; runtime unverified |

**Accounting:** 36 source-presence/contract witnesses, **four deficient review scopes in two root causes** (R996, R997, R1005, R1006). No additional defect is claimed for R1030: File20 ownership is correctly delegated to `includes/class-file-20-integration.php`, missed only by a too-narrow initial source search.

## Root-cause group A — Native WordPress post approval wrongly coerced to Boolean

`WordPress_Posts_Provider::get_public_author_items()` obtained the `sabri_public_experience/wordpress_post_is_public_approved` value with `(bool) apply_filters(...)`. PHP considers any nonempty string—including `'false'`—truthy; an incorrectly typed compatibility extension could therefore enlarge public publication eligibility. The plan requires native-owner-approved publication projection and fail-closed public gates.

**Source fix:** Do not coerce; require `$approved === true`. Post type remains WordPress `post` only; exact author, public status and passwordless checks are unchanged. This does not create a parallel publishing store or rewrite File21.

**Executable regression:** `tests/review995-1034-wordpress-post-approval.php` executes the real adapter against a controlled published-post fixture; strings, integers, null, array and false are denied, while strict boolean true remains approved.

## Root-cause group B — REST rate limit passed requests without enforceable quota state

`Rest_Controller::allow_public_request()` previously returned `true` when no persistent cache nor transient API existed or a request bucket was unavailable; it also ignored failed `set_transient` calls. A storage outage could disable the intended §72 rate limit while reporting reads as permitted. 

**Source fix:** When neither quota backend is available, bucket derivation is invalid, transient persistence fails, or no stored counter can be obtained, return a safe and retryable HTTP **503** via `WP_Error`, rather than silently permit public requests. The valid 120-request/minute default and explicit HTTP **429** once the bounded quota is exceeded remain unchanged. No File24 global rate-limiting backend is duplicated.

**Executable regression:** `tests/review995-1034-rest-rate-storage.php` calls the real public REST permission callback with a simulated storage-write failure and a recoverable store. It checks 503 on failed persistence and 429 after two allowed calls in a reduced test threshold.

**Known remaining limitation:** WordPress default transient read-and-write increments are not guaranteed atomic under concurrent distributed requests; a production File24 / cache-topology load test remains a release gate. Source acceptance alone cannot claim effective resilience under abuse traffic.

## Native dependencies, source/deployed truth and required release evidence

| Required evidence | Verified scope |
|---|---|
| Repository `main` at review freeze | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Source candidate | Unmerged, stacked above PR #26 |
| File03 / File08 / File17 / File20 / File21 | Repository HEADs recorded; **not** production plugin versions |
| File24 repository/current contract | No exact HEAD established in this review |
| Deployed Version / artifact checksum | **Unverified** |
| DB Version / Schema / migrations | **Unverified** |
| Staging actual dependency checks, native action contracts and real-role/RTL/a11y privacy acceptance | **Not complete** |
| Founder acceptance | **Not recorded** |
| Live Verification Status | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

Do not mark a live incident Resolved because GitHub CI passes. The required sequence remains source fix → tests → deterministic package → staging installed and schema/migration review → controlled deployment → real live re-test → checksum and contract parity confirmation.
