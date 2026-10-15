# File 25 — Reviews 755–794: 40 source inspections, REST public-policy parity and cross-file review
**Date:** 15 October 2026  
**Baseline:** File 25 `main` `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`; immediate corrective-parent (PR #20) `bc5ed1dcff8c289e6279e3869d0a0a0ca43e0761`.  
**Primary sources:** File 25 Final Harmonized Specification v2.0 §§29–90; Three Central Plans Consolidated Governing Master Plan 2026 (public access, module ownership, UI, security); source contracts of Files 00, 03, 08, 09, 17, 20, 21, 24.  
**Evidence classification:** These are forty distinct, targeted **source inspections**, not forty deployed UI journeys or forty full independent QA suites. A source witness never proves a feature is complete in staging or live.

## Forty review results

| Review | Topic | Result |
|---:|---|---|
| 755 | HTML global visibility | Source witness found (runtime unverified) |
| 756 | REST profile global visibility | Confirmed source gap → corrected |
| 757 | REST timeline global visibility | Confirmed source gap → corrected |
| 758 | REST Knowledge/Media global visibility | Confirmed source gap → corrected |
| 759 | REST timeline tab control | Confirmed source gap → corrected |
| 760 | REST Knowledge/Media tab control | Confirmed source gap → corrected |
| 761 | HTML search opt-out | Source witness found (runtime unverified) |
| 762 | REST search opt-out | Confirmed source gap → corrected |
| 763 | Canonical profile class routes | Source witness found (runtime unverified) |
| 764 | Canonical legacy redirects | Source witness found (runtime unverified) |
| 765 | REST GET-only boundary | Source witness found (runtime unverified) |
| 766 | REST permission callback | Source witness found (runtime unverified) |
| 767 | Public API rate limiting | Source witness found (runtime unverified) |
| 768 | Cache no-store | Source witness found (runtime unverified) |
| 769 | HTTP conditional validators | Source witness found (runtime unverified) |
| 770 | File 00 identity assertions | Source witness found (runtime unverified) |
| 771 | File 03 public DTO | Source witness found (runtime unverified) |
| 772 | File 09 verification authority | Source witness found (runtime unverified) |
| 773 | No second Timeline publisher | Source witness found (runtime unverified) |
| 774 | Same-origin timeline link | Source witness found (runtime unverified) |
| 775 | OpenGraph public media | Source witness found (runtime unverified) |
| 776 | Filtered profile noindex | Source witness found (runtime unverified) |
| 777 | Bounded optional section provider | Source witness found (runtime unverified) |
| 778 | Atomic optional section response | Source witness found (runtime unverified) |
| 779 | Section duplicate suppression | Source witness found (runtime unverified) |
| 780 | Read-only provider boundaries | Source witness found (runtime unverified) |
| 781 | Card canonical URL | Source witness found (runtime unverified) |
| 782 | Public card DTO allowlist | Source witness found (runtime unverified) |
| 783 | Accessible share feedback | Source witness found (runtime unverified) |
| 784 | Browser-back scroll restoration | Source witness found (runtime unverified) |
| 785 | Focus visibility | Source witness found (runtime unverified) |
| 786 | Reduced motion | Source witness found (runtime unverified) |
| 787 | Responsive breakpoints | Source witness found (runtime unverified) |
| 788 | Skip-link owner verification | File 20 responsibility verified |
| 789 | Data-free Safe Mode | Source witness found (runtime unverified) |
| 790 | Upgrade idempotence | Source witness found (runtime unverified) |
| 791 | Protected admin preferences | Source witness found (runtime unverified) |
| 792 | High-risk rebuild approval | Source witness found (runtime unverified) |
| 793 | Reversible migration map | Source witness found (runtime unverified) |
| 794 | Visual acceptance contract | Source witness found (runtime unverified) |

**Numbers:** 40 inspected; 33 had the reviewed source witness; six exposed REST-policy omissions; one Skip Link issue was legitimately delegated to File 20 (not a File 25 coding defect). An additional missing tab-definition defect was established in the follow-up schema inspection and corrected. The CI's first PHP 8.0 execution revealed a test-harness compatibility problem, which was corrected by explicitly allowing private reflection in PHP 8.0.

## Seven File 25 source defects addressed

1. **R756:** public REST profile returned data although the File 25 `public_visibility` setting made HTML public profiles unavailable. The public REST endpoint now returns an indistinguishable 404.
2. **R757:** Timeline REST leaked availability beyond the global public-visibility switch. The Timeline endpoint now honors that switch.
3. **R758:** Knowledge and Media REST ignored the global switch. These endpoints now fail closed.
4. **R759:** hidden Timeline navigation could still be read through REST. The REST Timeline gate now honors `enabled_public_tabs`.
5. **R760:** hidden Knowledge/Media tabs remained queryable through REST. Each REST section route now honors the same enabled tab list.
6. **R762:** a disabled profile-local search UI could still be used through the Timeline REST `search` parameter. Nonempty searches now return an explicit `profile_search_disabled` error; unfiltered reads remain available.
7. **Post-review schema check:** `Plan_Completion::SECTIONS` excluded `reviews` and `research` despite File 25's plan and `Section_Registry` having such sections. Their default/allowed public tab options were restored.

**Single underlying root cause:** HTML renderer, REST projection handlers and configurable section schema had diverged on the same presentation access policy. File 25 must honor those settings at all of its public read surfaces, while preserving File 00/03/09/24 native owner eligibility checks; it must not create new publication/identity data.

**Regression:** `tests/review755-794-rest-policy-parity.php` invokes the actual internal preference gate via reflection and checks both hidden and visible combinations, search opt-out, and handler use. Test suite manifest also includes the case; PHP 8.0 private reflection compatibility is explicit.

## Confirmed delegation: File 20

File 20 `main` source `sabri-unified-application-shell/includes/class-renderer.php` emits an accessible link to `#sabri-shell-main-content` via `wp_body_open`; it also outputs the target after its chrome. File 25 renders a semantic `<main>` but does not need to duplicate the shell skip link. This is **not** an unresolved File 25 defect.

## Unresolved contractual and deployment gates

- File 08 retains clinic/booking ownership. A confirmed doctor-user-to-booking canonical destination is required before an Appointment button may be surfaced; do not invent one.
- File 17 owns target-specific Follow state/actions. Leave Follow hidden until a reviewed native destination contract is accepted.
- File 21 owns publication truth and the present post Timeline bridge. Extended author-centric non-post providers require their own public-author/approval/visibility contracts.
- File 20 sole header/nav/skip link; File 24 owns security and policy governance; File 03 owns public profile/consent fields. No second canonical master or ad-hoc publication backend is permitted.
- Source CI and deterministic ZIP checks are distinct from Hostinger installed plugin packages, active DB/schema/migration, real-role staging, Founder acceptance and live post-deploy re-test.

## Required status report

| Evidence | State |
|---|---|
| Repository Main | Frozen `347a4ff4...` (unmerged baseline) |
| Lower corrective PR | #20, unmerged draft |
| This corrective branch | Source corrections submitted to GitHub; verify exact latest branch CI externally |
| Deployed Version | Unverified |
| Live DB Version | Unverified |
| Migration State | Unverified |
| Hostinger Staging | Unverified/not accepted |
| Live Verification | Unverified; no deployment claim |

**Release rule:** Do not call any live defect resolved until exact installed-version evidence, schema/migration checks, staging acceptance and subsequent live re-test pass. The source audit and CI may justify review/merge preparation, not a claim of operational completion.
