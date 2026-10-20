# File25 — Reviews 1755–1794: Redirect Loop, Core Route, and Runtime Integrity
**Date:** Tuesday, 20 October 2026, Asia/Karachi. **Source repo:** `majidhussainqadri1-dot/25-sabri-public-ui-profile-timeline-visual-experience`. **Frozen main:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Parent:** unmerged Draft PR #41 at `67394ae6e46b2cc7c3aab99cb7e5c5f450a99d6a`.

## Governing exact Word plans and owner boundaries
- Attached **File25 Complete Public UI/Profile Timeline Final** §77 URL Migration (old URLs, redirect map, dry-run, collision, execute, verify, rollback and redirect-chain test), §89 staging acceptance items 18–19 and 48–50, and §90 Definition of Done.
- Attached **Three Central Consolidated Governing Plan** RCD-032 non-destructive/reversible migration; RCD-034 distinct specified/coded/QA-green/staging/live truths; RCD-035 exact PR SHA and release status evidence; RCD-036 comprehensive file/master/owner/route/role/security/privacy/rollback/source review. §§47–49 staging backup+restore+negative role tests before deployment.
- Companion File03 canonical profile data, File17 communication/follow actions, File20 global shell, File21 publication timeline, File24 security assurance, File26 global search ownership. This branch edits ONLY File25's existing URL Migration code and its tests/docs. PR41 already carries atomically guarded File25-only derived index options and current File24 v1.2 manifest; those are source commitments, not staging acceptance.

## Review discipline and exact counts
**40 distinct targeted plan/source inspections R1755–1794 completed before any source patch.** 28 prior code witnesses; **12 identified deficient scopes across FOUR distinct root causes**, corrected in this new unmerged draft source candidate. Machine-readable 40-row ledger: `config/review1755-1794-redirect-runtime-safety-audit.json`. These are not forty full WordPress/browser tests or forty real database migrations.

### Root cause 1: R1755–1757 — Same-path redirect loops
Previously File25 compared `Public_URL::canonical_same_site_identity($from)` to the entire target URL identity. A relative source `/old` and absolute same-site target `https://example.test/old` compared unequal, even though both are the same request path. Likewise `/old?query=1` and a query-only destination `?query=1` could be admitted. The runtime matcher ignored the incoming query, leading to a repeated 301/308.
**Correction:** Require an absolute pathname or an approved same-origin full URL destination, extract the destination pathname, and reject any same-path target regardless of query/trailing slash/relative-vs-absolute syntax. The validator still preserves 301/308 and strict same-site URL policy.

### Root cause 2: R1758–1760 — Encoded protected WordPress routes
`protected_legacy_redirect_source()` previously regex-matched raw source path only. Percent-encoded `wp-admin`/`wp-login.php` spellings and double-escaped aliases could evade it; source path validation also did not apply the already-existing Public_URL path safety policy. The new source validator uses that path safety policy and checks bounded repeatedly decoded protected route spellings, including core paths and script/path suffix forms. Existing route ownership is unchanged.

### Root cause 3: R1761–1763 — Untrusted active migration option
`apply_legacy_redirects()` previously matched entries from the active `wp_options` map without rechecking the source pathname, bounded map size, or a self-loop. A stale/corrupt injected active map could redirect WordPress core routes or itself even if a new dry run would fail. The runtime handler now bounds map count at 500 and verifies source/incoming path safety, destination type, redirect status, and non-equal path identity before redirecting.

### Root cause 4: R1764–1766 — Slash-only aliases mismatch
The validator treats `/legacy` and `/legacy/` as one collision identity, but the runtime handler matched exact raw strings: an approved mapping could silently fail for the other slash variant. Runtime matching now uses that bounded path identity after source/incoming path verification. Dangerous percent-encoded requests fail closed rather than being normalized to a protected route.

## Executable regression evidence
`tests/review1755-1794-redirect-validation-runtime.php` invokes File25's actual `validated_redirect_map()` and `apply_legacy_redirects()` code with isolated WordPress stubs. The runtime's `wp_safe_redirect()` is intercepted by a test exception so redirect target/status can be asserted without PHP exiting the process. Tests cover: same-path absolute and query targets; query-only target; one- and two-layer encoded WordPress login/admin paths; traversal and encoded slash; valid destinations, duplicates and chains; oversized 501-item map; injected active-map login/self-loop; and both slash-alias directions.

Composer governed PHP manifest updated; historical lineage tests extended; all original 25 source-completion requirement groups retained. Changes are source-only and not merged.

## Separate, non-fabricated release and blocker report

| Evidence required | Status |
|---|---|
| GitHub main (source truth) | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at review freeze |
| Fix | New **Draft unmerged PR** based on exact PR41 `67394ae6e46b2cc7c3aab99cb7e5c5f450a99d6a` |
| Final exact SHA PHP8.0/8.3/JS/package CI | Verify independently after final changes |
| Installed plugin, deployed code/version/checksum | **Unverified** |
| WordPress database/schema, `wp_options`, migration state | **Unverified** |
| WordPress connection | **Not available**; WPVibe reports no connected sites |
| Real MariaDB/MySQL concurrent index CAS with persistent object cache | **Not tested here** |
| File08 booking, File17 follow, File24 staging security assurance | **Acceptance outstanding** |
| Staging backup, restore, rollback, RTL, actual real-role privacy/browser tests | **Outstanding** |
| Founder acceptance and production deployment | **Not authorized/performed** |
| Live re-test and live verification status | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

No production incident can be labeled Resolved on a GitHub PR or CI result. Deployment remains gated by exact code/package checksum parity, staging/rollback proof, cross-file acceptance, Founder approval and live postdeployment re-test.
