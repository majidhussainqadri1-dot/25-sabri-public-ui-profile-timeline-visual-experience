# File 25 — Reviews 795–834: 40 independent source inspections and SEO/privacy correction
**Audit date:** 15 October 2026.  
**Repository `main` frozen for comparison:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`.  
**Corrective parent:** `7ed66229bed5b6825378eed1046477fc4be40ed9` (unmerged draft PR #21, which itself is stacked on PR #20).  
**Governing source:** File 25 Final Harmonized Specification v2.0, specifically §§29–30, 60, 65, 68, 72, 80–90, and its central 70-requirement addendum; Three Central Plans Consolidated Governing Master Plan 2026 (public/private semantics, source owner, no duplicate domain backends and release gates).

## Evidence boundary

These are **40 independently targeted code inspections** with explicit technical subjects, NOT 40 end-to-end WordPress executions, not forty full independent software-test cycles, and not Hostinger live acceptance. A located source guard is not proof that every runtime path behaves correctly.

| Round | Scope | Source outcome |
|---:|---|---|
| 795 | Founder/Doctor/Member route disambiguation | Evidence located — runtime unverified |
| 796 | Canonical role mismatch redirect | Evidence located — runtime unverified |
| 797 | Unavailable profile fail-closed | Evidence located — runtime unverified |
| 798 | Owner/Admin preview authorization | Evidence located — runtime unverified |
| 799 | Server HTML public-visibility switch | Evidence located — runtime unverified |
| 800 | REST public-visibility switch | Evidence located — runtime unverified |
| 801 | SEO noindex isolated from unrelated WordPress pages | Defect corrected on branch |
| 802 | Owner-only hidden profile robots noindex | Defect corrected on branch |
| 803 | Owner-only hidden profile prevents OpenGraph/JSON-LD | Defect corrected on branch |
| 804 | No weakening of earlier nofollow | Defect corrected on branch |
| 805 | Exact boolean nonprofessional indexing opt-in | Defect corrected on branch |
| 806 | Native approved public-profile eligibility | Evidence located — runtime unverified |
| 807 | File03 contract-version owner projection | Evidence located — runtime unverified |
| 808 | No independent WordPress bio fallback | Evidence located — runtime unverified |
| 809 | No independent WordPress display-name fallback | Evidence located — runtime unverified |
| 810 | Monotonic File03 public bio/headline | Evidence located — runtime unverified |
| 811 | Minor contact refusal | Evidence located — runtime unverified |
| 812 | Approved File03 contact consent | Evidence located — runtime unverified |
| 813 | File09 doctor verification authoritative | Evidence located — runtime unverified |
| 814 | File08 public clinic read adapter | Evidence located — runtime unverified |
| 815 | Safe same-origin canonical URL | Evidence located — runtime unverified |
| 816 | Filtered Timeline noindex | Evidence located — runtime unverified |
| 817 | Pagination secondary pages noindex | Evidence located — runtime unverified |
| 818 | Owner-safe OpenGraph avatar URL | Evidence located — runtime unverified |
| 819 | Public REST response no-store | Evidence located — runtime unverified |
| 820 | Conditional ETag handling | Evidence located — runtime unverified |
| 821 | Bounded public REST rate limiter | Evidence located — runtime unverified |
| 822 | REST permission callback | Evidence located — runtime unverified |
| 823 | REST tab visibility parity | Evidence located — runtime unverified |
| 824 | REST search opt-out parity | Evidence located — runtime unverified |
| 825 | Timeline provider batch validation | Evidence located — runtime unverified |
| 826 | Normalized DTO public output | Evidence located — runtime unverified |
| 827 | Duplicate canonical timeline normalization | Evidence located — runtime unverified |
| 828 | Bounded timeline candidates/pagination | Evidence located — runtime unverified |
| 829 | File24 security integration module | Evidence located — runtime unverified |
| 830 | File20 global Skip Link owned externally | File20 owns; no duplicate |
| 831 | RTL through logical CSS and design-system direction | Evidence located — runtime unverified |
| 832 | Reduced-motion support | Evidence located — runtime unverified |
| 833 | File08 Booking and File17 Follow owner destination acceptance | External verification pending |
| 834 | Exact deployed DB/schema/migration and live/staging parity | External verification pending |

**Accounting:** 32 source-witness checks, 5 verified source-code defects corrected, 1 confirmed File20-owned navigation responsibility, 2 external evidence blockers (File08/17 owner action contracts and exact installed staging/live DB/migrations). The RTL query on only `public.css` was a false alarm: File 25 has logical CSS properties and a direction-aware Design System. No duplicate RTL code was added.

## Root cause and five corrections

**R801 — sitewide SEO regression:** `Plan_Completion::profile_robots` is hooked into global WordPress `wp_robots`. The old implementation applied File 25 `seo_enabled=false` to unrelated File20/File21/News pages too, even though the File25 plan expressly forbids sitewide SEO ownership. **Fix:** require a real File 25 profile route before applying its options.

**R802 — private owner profile indexing:** `Profile_Renderer::robots` accepted an owner-only view with `public_visibility=false` yet did not unconditionally apply `noindex/noarchive`. **Fix:** those outputs remain nonindexable regardless of professional role and admin/owner context; the File25-scoped `profile_robots` confirms the same.

**R803 — private OpenGraph/JSON-LD:** `Profile_Renderer::render_meta` could emit profile titles, descriptions, OpenGraph tags, image and JSON-LD even on the owner-only route when the public visibility setting was off. **Fix:** skip all File25 metadata for globally private profiles.

**R804 — monotonic nofollow regression:** filtered/nonprofessional noindex path assigned `nofollow = is_404()`, thereby **turning off** an earlier `nofollow=true` assigned by another owner/security filter for an otherwise 200 response. **Fix:** on 404 set `nofollow=true`; otherwise never reset an existing value.

**R805 — unsafe indexing permission coercion:** indexing an ordinary public member used `(bool) apply_filters(...)`. An extension returning a truthy string `'yes'` could accidentally grant search indexing. **Fix:** require strict `=== true`, aligned with File25's established fail-closed permission adapters.

**Behavioral regression:** `tests/review795-834-seo-scope-privacy.php` loads the actual PHP classes and exercises unrelated pages, File25 own routes, public/hidden owner profiles, previews, strict member indexing and preexisting nofollow signals. CI should verify PHP 8.0 and PHP 8.3 plus JavaScript and deterministic package on the exact final head, not on an earlier commit.

## Native owner crosschecks

- **File 20** `8a4dbcaf...`: the Shell owns the global skip link/target and its chrome. File25 does not duplicate that role.
- **File 21** `f2eb7e95...`: retains Home/News/official post publishing and canonical Timeline item owner authority. The File25 SEO option must not alter those pages.
- **File 08** `9dcc8d16...`: clinic public contract `1.1.0` source witness only; user-to-booking destination remains not accepted.
- **File 17**: target-bound Follow destination remains outside File25 and not accepted.
- **File 03/00/09/24**: profile, consent, membership, doctor verification and security authority are never replaced by File25 visual filters.

## Release and incident status

| Required field | Verified state |
|---|---|
| Main repository HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at audit start |
| Corrected source commit | Check exact PR branch HEAD and corresponding CI |
| Deployed Version | Unverified |
| DB Version / Schema | Unverified |
| Migration State | Unverified |
| Staging Acceptance | Not confirmed |
| Live Verification Status | Not performed |
| Merge | Not performed |

**Do not claim resolved in production without** exact installed plugin checksums, active versions, DB/schema and migration verification, staging cross-module acceptance, deploy and live retest. Historical CI/PR evidence is not live truth. Staging dependencies and public/native owner contracts are separate gates.
