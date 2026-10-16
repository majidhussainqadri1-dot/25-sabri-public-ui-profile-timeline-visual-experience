# File 25 — Reviews 955–994: 40 Source Inspections and Exact Native Timeline Parity

**Date:** 16 October 2026. **Repository main baseline:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Unmerged parent:** PR #25 `e899ad92b74f59ef728a4ed28f35eebdf13c18c4`.  
**Master Plan sources:** File25 Complete Public UI, Profile Timeline and Visual Experience Final, §§29–30,37,44–47,53–54,60–65,68,72,80–90; Three Central Consolidated Governing Plan (module single ownership, strict API validation and release separation). Cross-check with native owner File03,08,09,17,20,21,24.

## Evidence methodology

These are **40 separate bounded source-code checks** with different contract subjects, not forty independently executed complete WordPress/Hostinger end-to-end tests. Existing code witnesses do **not** establish installed plugin parity, current database or staging acceptance.

| Review | Requirement / native owner boundary | Reviewed source | Evidence |
|---:|---|---|---|
| 955 | Founder timeline REST uses common strict filter schemas | `includes/class-rest-controller.php` | **Gap → Source corrected** |
| 956 | Doctor/member timeline exposes governed filters | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 957 | Founder timeline resolves same timeline_response | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 958 | REST per_page bound of 50 | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 959 | REST optional year exact validation | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 960 | REST language BCP-style bounded validation | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 961 | REST Unicode topic normalizer | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 962 | Timeline service accepts 120 Unicode Urdu topic chars | `includes/class-timeline-service.php` | **Gap → Source corrected** |
| 963 | Timeline service rejects hidden bidi/format controls | `includes/class-timeline-service.php` | **Gap → Source corrected** |
| 964 | Timeline service fails closed on invalid search | `includes/class-timeline-service.php` | Code witness, runtime not proven |
| 965 | HTML timeline topic Unicode bound | `includes/class-profile-renderer.php` | Code witness, runtime not proven |
| 966 | HTML profile search disabled preference | `includes/class-profile-renderer.php` | Code witness, runtime not proven |
| 967 | Founder books/research public visual section | `includes/class-profile-repository.php` | Code witness, runtime not proven |
| 968 | File03 authorization remains mandatory | `includes/class-visibility-policy.php` | Code witness, runtime not proven |
| 969 | Minor contact public redaction | `includes/class-visibility-policy.php` | Code witness, runtime not proven |
| 970 | Native File09 Doctor verification owner | `includes/class-native-integration.php` | Code witness, runtime not proven |
| 971 | Canonical profile route contracts | `includes/class-profile-repository.php` | Code witness, runtime not proven |
| 972 | File20 single-shell integration | `includes/class-profile-router.php` | Code witness, runtime not proven |
| 973 | File24 security integration owner | `includes/class-file-24-integration.php` | Code witness, runtime not proven |
| 974 | REST global public privacy opt-out | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 975 | REST tabs discover normalized native providers | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 976 | REST tab selection applies admin preferences | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 977 | REST local search opt-out | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 978 | REST ETag + LastModified | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 979 | REST no-store private cache | `includes/class-rest-controller.php` | Code witness, runtime not proven |
| 980 | Timeline provider atomic batch and metadata | `includes/class-timeline-service.php` | Code witness, runtime not proven |
| 981 | Timeline canonical cross-provider dedup | `includes/class-timeline-service.php` | Code witness, runtime not proven |
| 982 | Timeline bounded candidate window | `includes/class-timeline-service.php` | Code witness, runtime not proven |
| 983 | Timeline provider read-only maturity gate | `includes/class-timeline-service.php` | Code witness, runtime not proven |
| 984 | Content card public allow-list | `includes/class-content-cards.php` | Code witness, runtime not proven |
| 985 | Section approved provider IDs | `includes/class-section-registry.php` | Code witness, runtime not proven |
| 986 | Section invalid batch atomic rejection | `includes/class-section-service.php` | Code witness, runtime not proven |
| 987 | Public profile media from native owner | `includes/class-profile-repository.php` | Code witness, runtime not proven |
| 988 | 14 public tabs allowed after admin save | `includes/class-plan-completion.php` | Code witness, runtime not proven |
| 989 | Invalid public preference booleans fail closed | `includes/class-plan-completion.php` | Code witness, runtime not proven |
| 990 | File17 Follow native action suppresses unknown target | `includes/class-plan-completion.php` | Code witness, runtime not proven |
| 991 | File08 Appointment does not invent user-to-booking | `includes/class-current-companion-2026-08-10.php` | Code witness, runtime not proven |
| 992 | Safe Mode profile robots privacy | `includes/class-safe-mode-public.php` | Code witness, runtime not proven |
| 993 | Public visual reduced motion CSS | `assets/css/public.css` | Code witness, runtime not proven |
| 994 | GitHub CI not promoted to deployed live truth | `config/source-completion-matrix.json` | Code witness, runtime not proven |

**Counts:** 37 requirement/source witnesses; **3 corrected source-gap checks, representing 2 root causes**. Checks 955, 962 and 963 were the only confirmed gaps in this review scope. No runtime/staging/live completions are inferred.

## Root cause 1 — R955: Founder timeline REST routes lacked strict filter schema

File25 §44 explicitly requires primary and secondary Timeline filters, and §72 requires validated versioned public REST endpoints. The Founder Timeline route had only page/per_page in `args`, whereas `profiles/{slug}/timeline` had schemas for `content_type`, `provider`, `year`, `language`, `topic`, `sort` and `search` too. Both routed through exactly the same `timeline_response()`. A filter request to Founder was therefore not subject to the equivalent WordPress route-level input validation.

**Fix:** Use a single `$timeline_args` array containing pagination and all seven filter schemas for both Founder and standard Timeline REST routes; don't change authority or allow-list. `tests/review955-994-founder-timeline-rest-unicode.php` captures real `register_rest_route()` registrations, compares validation/sanitation callbacks per field and runs malicious/valid inputs. No native File21 publishing data is copied.

## Root cause 2 — R962–963: Timeline Service rejected Urdu and failed to reject invisible format controls

In preceding Reviews 915–954, public REST and HTML topic/search processing were corrected to accept up to **120 Unicode codepoints** (bounded **480 UTF-8 input bytes**) and reject bidi/format controls. But `Timeline_Service::exact_optional_text()`, the last gate before native Timeline providers, still enforced `strlen($value) > 120` and ASCII control-character checks only. As a result, valid long Urdu/emoji topics displayed as accepted upstream could yield empty Timeline results, while hidden bidi override characters would not be blocked at the service boundary.

**Fix:** Adopt the same bounded `Plan_Completion::normalize_search_query` policy at the service boundary with Unicode codepoint counting. The new regression calls the **actual** `Timeline_Service::get_for_author()` against a minimal registry fixture and proves accepted 120-codepoint topics reach the native provider, while 121 codepoints, huge values, and bidi override inputs fail before any provider invocation. The test separately checks REST schema parity.

## Governing release and integration boundaries

No File25-owned parallel identity, network, clinical booking, global shell, news-feed, provider datasets or security center was added. File03 remains authoritative for identity/contacts, File09 for verification, File08 for booking and clinical records, File17 for Follow actions, File20 for shell, File21 for publication/Timeline sources, File24 for security. Unaccepted File08/17 destinations remain fail-closed.

| Mandatory check | Evidenced status |
|---|---|
| Repository main HEAD at source freeze | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Exact corrective source | stacked candidate PR; current HEAD and CI must be checked |
| Deployed version | **Unverified** |
| Live DB/schema version | **Unverified** |
| Migration state / wp_options | **Unverified** |
| Staging real-role / RTL / privacy acceptance | **Not established** |
| Live verification | **Not performed** |
| Founder sign-off | **Not established** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** GitHub green tests are source truth, not proof of a deployed solution. Required: owners' contracts → code → exact-head CI → signed deterministic package → installed staging/DB/migration audit → controlled deploy → live retest → checksum parity.
