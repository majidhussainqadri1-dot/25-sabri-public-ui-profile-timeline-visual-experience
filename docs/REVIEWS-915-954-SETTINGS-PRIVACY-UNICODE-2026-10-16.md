# File 25 — Reviews 915–954: Forty targeted Source/Plan Checks

**Date:** 16 October 2026. **Main repository baseline:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Correction base:** `aa18aae531fbd6b05a887673bf7d16cac38f6842`, unmerged draft PR #24, itself stacked on draft #23, #22, #21 and #20. **Scope:** actual Word File25 Final Harmonized master specification, Three Central Consolidated Governing Master Plan, and companion owner File03/08/09/17/20/21/24 canonical boundaries. Distinguish GitHub source from installed staging/production.

## Accounting

**40 distinct targeted source inspections** (not forty independent full codebase audits or live tests), **27 source witnesses**, **13 failing requirement/source scopes corrected in five root-cause groups**. Some checks are multiple distinct consequences of one root cause (e.g. save roundtrip loses both PDF and Marketplace), so do not describe 13 separate root causes. A source witness is not proof every runtime state has been tested.

| Review | Governing plan / module boundary checked | Evidence |
|---:|---|---|
| 915 | Plan admits fourteen defined public sections | Source evidence present |
| 916 | Enabled public tabs cap covers all fourteen | **Defect corrected in source** |
| 917 | Featured section order cap covers all fourteen | **Defect corrected in source** |
| 918 | Save roundtrip retains Marketplace tab | **Defect corrected in source** |
| 919 | Save roundtrip retains PDF tab | **Defect corrected in source** |
| 920 | Tab names restricted to canonical allow-list | Source evidence present |
| 921 | Malformed enabled tabs cannot reopen hidden tabs | **Defect corrected in source** |
| 922 | Malformed optional provider list cannot activate all | **Defect corrected in source** |
| 923 | Malformed public_visibility fails closed | **Defect corrected in source** |
| 924 | Malformed public_metrics fails closed | **Defect corrected in source** |
| 925 | Malformed profile_local_search fails closed | **Defect corrected in source** |
| 926 | File03 identity ownership and publication eligibility | Source evidence present |
| 927 | Minor public contact refusal | Source evidence present |
| 928 | No independent WordPress biography fallback | Source evidence present |
| 929 | No independent WP display name fallback | Source evidence present |
| 930 | Owner-only preview permission | Source evidence present |
| 931 | Private preview noindex | Source evidence present |
| 932 | Server public visibility kill switch | Source evidence present |
| 933 | REST public visibility kill switch | Source evidence present |
| 934 | Native enabled tab allow-list applied | Source evidence present |
| 935 | 120-Unicode-character REST search budget | **Defect corrected in source** |
| 936 | 120-Unicode-character REST topic budget | **Defect corrected in source** |
| 937 | HTML topic matches Unicode character budget | **Defect corrected in source** |
| 938 | Normalize search rejects Unicode bidi overrides | Source evidence present |
| 939 | REST topic rejects hidden Unicode controls | **Defect corrected in source** |
| 940 | REST/HTML normalized optional provider tabs | Source evidence present |
| 941 | Local REST search opt-out honored | Source evidence present |
| 942 | Malformed REST search rejected | Source evidence present |
| 943 | Bounded REST page size | Source evidence present |
| 944 | Private REST no-store response headers | Source evidence present |
| 945 | File20 shell layout ownership | Source evidence present |
| 946 | File17 Follow requires owner destination | Source evidence present |
| 947 | File08 booking URL requires owner contract | Source evidence present |
| 948 | Native Section Provider identity consistency | Source evidence present |
| 949 | Public Section Card allow-list normalization | Source evidence present |
| 950 | File25 does not create a global Search Engine | Source evidence present |
| 951 | RTL and logical CSS | Source evidence present |
| 952 | Reduced motion presentation | Source evidence present |
| 953 | Staging acceptance not falsely promoted | Source evidence present |
| 954 | Live deployed parity not falsely promoted | Source evidence present |

## Root-cause analysis and fixes

### A. R916–R919: default fourteen public sections truncated on administrative save

`Plan_Completion::SECTIONS` explicitly contains **14** approved keys, ending in `pdfs`, `marketplace`. The `enabled_public_tabs` and `featured_section_order` schema limits were both **12**. Saving a valid complete comma-separated admin list truncated the last two keys, making default public sections unexpectedly disappear and preventing fully customized ordering. The review branch raises both list maxima to 14. Actual `sanitize_preferences`, stored `preferences()` and `apply_profile_preferences` roundtrips are tested.

### B. R921–R922: malformed list fail-open

An invalid nonempty `enabled_public_tabs` list previously normalized to its entire default list, restoring tabs deliberately hidden in admin. An invalid `enabled_optional_providers` list similarly re-enabled all five optional providers. These are presentation privacy/consent and maturity controls. The correction uses conservative **overview-only** and **no optional providers** when every explicit entry is invalid, while preserving normal full default on new installations and valid partial subsets. File25 never replaces native owner authorization.

### C. R923–R925: explicit malformed booleans re-enabled disabled public features

Because `public_visibility`, `public_metrics`, and `profile_local_search` all default to true, an unrecognized explicit value (including malformed or partially migrated state) silently reset each to true. The correction makes malformed explicit values false, without changing absent-key fresh-install defaults or recognized 0/1/true/false values. Source regression checks invalid inputs and unchanged valid preferences.

### D. R935–R937: byte-length exclusion of multilingual search and topic text

`Plan_Completion::normalize_search_query` used Unicode characters when `mbstring` was available, but REST rejected searches using `strlen > 120`. REST and HTML topic filters likewise enforced **120 bytes**, which prematurely rejects many otherwise valid Urdu, Arabic, CJK and emoji strings. The correction makes the actual validation limit **120 Unicode codepoints**, with an independent **480-byte maximum** for safely bounded processing. The counting fallback uses `preg_match_all('/./us')` where mbstring is absent, avoiding English-only behavior.

### E. R939: Unicode formatting/bidi attack rejected by search but not topic

REST `exact_optional_text` formerly checked ASCII control characters only. The correction shares File25's `Plan_Completion::normalize_search_query` strict format-control policy with topic normalization, and HTML topic filtering applies the same policy. This preserves normal multilingual input while rejecting zero-width/bidirectional override injection.

**False positive excluded:** R938 initially appeared missing in a narrowly matched source string; direct inspection confirmed existing search normalization already rejects U+202E and related controls, so it is recorded as a witness, not a defect.

## Executable regression and CI

- `tests/review915-954-preferences-privacy-unicode.php` runs genuine PHP preference normalization, storage readback, profile tab restriction, malformed privacy values and Unicode search normalization.
- `tests/review915-954-rest-html-unicode-parity.php` executes actual private REST and HTML Timeline validators on Urdu, four-byte UTF8, 121-character overflow, oversized input and hidden bidi controls.
- Both tests are registered in `composer.json` governed test manifest, retaining PHP 8.0/8.3 compatibility.
- CI results are only valid after confirming the **exact final commit**; historical checks cannot prove a later revision.

## Native owner boundaries

| Owner | Current repository evidence | File25 relationship |
|---|---|---|
| File03 | `636e3ef965423887f810718abec3cd1c11c3659d` | Public profile, identity, consent, media and visibility authorization |
| File08 | `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03` | Native clinic and booking target, no invented user-to-booking URL |
| File17 | `8ae656e51796d1f05865d8be5dca2480443d79ca` | Follow/relationship action owner, native destination needed |
| File20 | `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca` | Global shell, navigation and skip link |
| File21 | `f2eb7e95ddea327af36ea725ffb923b029f885e6` | Native post/editorial content and source Timeline |
| File09/24 | Governing Word specifications | Doctor verification and central security/privacy respectively |

## Unresolved acceptance and exact deployed-state truth

| Required evidence | Status |
|---|---|
| Repository main HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at audit freeze |
| Corrective source | Separate unmerged draft candidate, stacked above #24 |
| Deployed Version | **Unverified** |
| DB Version / Schema | **Unverified** |
| wp_options / Migration State | **Unverified** |
| Staging owner acceptance | **Not verified** |
| Real-role RTL/a11y/privacy E2E | **Not performed** |
| Founder acceptance | **Not recorded** |
| Live Verification Status | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** File25 cannot be called production-resolved based on source CI. Required: resolve owner dependencies → exact package/checksum → staging artifact + DB/migration check → controlled merge/deploy → live regression → deployment parity.
