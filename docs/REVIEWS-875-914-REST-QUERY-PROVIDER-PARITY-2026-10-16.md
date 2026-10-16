# File 25 — Reviews 875–914: 40 targeted public API and native-owner source inspections

**Review date:** 16 October 2026.  
**File 25 main baseline:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`.  
**Stacked corrective parent PR:** #23 at `11b8fdc13e41c25728aafaaa371ca896414f15b0` (and its predecessors #22, #21, #20 remain unmerged drafts).

## Source hierarchy and precision

- File 25 Final Harmonized Specification v2.0: §§29–30 (canonical routes and profile classes), 37 (native action owners), 46/53 (federated knowledge/Timeline providers), 60–65 (accessibility, privacy/security and RTL), 68 (profile-only SEO), **72 (REST strict validation and public allow-list)**, 80–90 (role-state, staging, migration, release and acceptance).
- Sabri Social Homeopathy Platform Three Central Plans Consolidated Governing Master Plan 2026: release-status separation, canonical data ownership, nonduplicative integrations, real staging/Founder approval gates.
- Related native owners: File03 profile identity and consent, File08 clinical booking, File09 Doctor verification, File17 relationships, File20 application shell, File21 native publication and File24 security.

Forty **specific code inspections** completed; they are **not** 40 complete End-to-End execution cycles, and an existing code witness does not equal accepted staging or verified deployment.

## All forty source inspections

| Inspection | Requirement / cross-file scope | Evidence result |
|---:|---|---|
| 875 | File03 UUID public ID and File25 slug route separation | Source witness, deployment unverified |
| 876 | Founder Doctor and Member canonical routing | Source witness, deployment unverified |
| 877 | File00 and File03 public profile authority | Source witness, deployment unverified |
| 878 | File03 owner-approved text and no independent identity | Source witness, deployment unverified |
| 879 | File09 Doctor verification authority | Source witness, deployment unverified |
| 880 | Minor contact privacy | Source witness, deployment unverified |
| 881 | Contact consent and monotonic revocation | Source witness, deployment unverified |
| 882 | Global SEO hook limited to File25 profile routes | Source witness, deployment unverified |
| 883 | Owner-private OpenGraph metadata suppression | Source witness, deployment unverified |
| 884 | Global public REST opt-out | Source witness, deployment unverified |
| 885 | REST profile dynamic provider tabs match HTML | Source defect → corrected |
| 886 | REST profile respects administrator selected public tabs | Source witness, deployment unverified |
| 887 | REST Timeline can be hidden administratively | Source witness, deployment unverified |
| 888 | REST Knowledge/Media hidden sections unavailable | Source witness, deployment unverified |
| 889 | Profile-local REST search preference | Source witness, deployment unverified |
| 890 | Strict invalid REST search preservation | Source witness, deployment unverified |
| 891 | Strict content_type optional REST validation | Source defect → corrected |
| 892 | Strict provider optional REST validation | Source defect → corrected |
| 893 | Strict year optional REST validation | Source defect → corrected |
| 894 | Strict language optional REST validation | Source defect → corrected |
| 895 | Strict topic optional REST validation | Source defect → corrected |
| 896 | Strict sort optional REST validation | Source defect → corrected |
| 897 | REST per_page strict upper bound 50 | Source witness, deployment unverified |
| 898 | REST page exact positive integer | Source witness, deployment unverified |
| 899 | REST no-store private response | Source witness, deployment unverified |
| 900 | REST ETag and Last-Modified support | Source witness, deployment unverified |
| 901 | Provider registration metadata invariant | Source witness, deployment unverified |
| 902 | Read-only native provider ownership | Source witness, deployment unverified |
| 903 | Section all-or-nothing public card batch | Source witness, deployment unverified |
| 904 | Canonical public card deduplication | Source witness, deployment unverified |
| 905 | Bounded author-centric Timeline provider queries | Source witness, deployment unverified |
| 906 | Timeline provider identity invariant | Source witness, deployment unverified |
| 907 | File17 Follow destination fails closed | Source witness, deployment unverified |
| 908 | File08 Appointment destination fails closed | Source witness, deployment unverified |
| 909 | File20 owns global shell and skip navigation | Source witness, deployment unverified |
| 910 | RTL logical properties and base tokens | Source witness, deployment unverified |
| 911 | Reduced-motion public visual system | Source witness, deployment unverified |
| 912 | File24 central security integration | Source witness, deployment unverified |
| 913 | Safe Mode public noindex/transparent fallback | Source witness, deployment unverified |
| 914 | Staging and live acceptance flags not self-certified | Source witness, deployment unverified |

**Defect accounting:** 33 scoped source witnesses; **7 source-level gaps found and corrected**, belonging to **2 root-cause groups**. The seven failing scopes are Reviews **885, 891, 892, 893, 894, 895, 896**. Other code paths remain subject to independent integration/staging verification.

## Two root-cause groups and corrections

**Root cause A — R885: REST profile tab manifest differed from HTML.** The HTML profile builds its visible Knowledge/Media/other optional tabs by invoking `Section_Service::available_for_profile`, which accepts only normalized native-approved public section cards. The REST `profile_response` previously returned `Profile_Repository`'s base `available_sections` only, so a visible provider tab could be missing from its REST manifest. Corrected REST now follows the same provider discovery and merges only the returned public labels/sections before applying `Plan_Completion::apply_profile_preferences`, respecting administrator tab restrictions and File03 eligibility.

**Root cause B — R891–896: malformed optional REST parameters could be sanitized into valid defaults.** REST optional `content_type`, `provider`, `year`, `language` and `sort` coerced nonscalar/invalid forms to empty or `latest`; `topic` could strip malformed data into an accepted empty filter. Corrected validators preserve invalid input for rejection and normalize only already-valid `topic` text, retaining intentional empty queries and valid multilingual text. This closes a sanitizer/validator contract inconsistency independently of WordPress's runtime validation order.

**Executable regression:** `tests/review875-914-rest-query-provider-parity.php`. This uses the actual REST controller and registered callback schemas with PHP 8.0+ fixture native providers to exercise 6 negative filter classes, positive inputs, real dynamic provider-tab response construction, administrator hidden tabs and public visibility disabling. Governed PHP 8.0/8.3 and JavaScript/artifact CI must pass for the *exact branch HEAD*.

## Companion and Release Truth

- File 03 continues to own public profile authorization/consent; no raw user meta, private profile data or parallel profile DB is introduced.
- File 08 alone owns native appointment/booking destinations; File 17 owns Follow state; neither has an accepted target-bound profile-action destination, so their buttons remain absent.
- File 20 owns global layout, header and navigation; File 21 owns publications and source content; File 24 owns central security/privacy assurance.
- File25 direct adapters remain read-only; no duplicate native publication backend/analytics lake has been introduced.

| Required live incident status | Actual evidence |
|---|---|
| Repository HEAD (`main`) | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at audit |
| Corrective branch | stacked on PR #23; only GitHub-source candidate |
| Deployed Version | **Unverified** |
| DB Version / schema | **Unverified** |
| Migration State | **Unverified** |
| Staging/real-role acceptance | **Not verified** |
| Live Verification Status | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

A source fix plus successful CI does not close a live incident. Root cause → source fix → exact-HEAD CI → package checksum → installed staging + DB/migration review → deployment → live re-test → parity confirmation is required.
