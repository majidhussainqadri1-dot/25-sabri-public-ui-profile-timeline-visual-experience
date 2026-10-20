# File25 — Reviews 1715–1754: Redirect Ownership, Migration Revalidation and Index Provenance
**Date:** 20 October 2026 (Pakistan). **Frozen GitHub main:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Immediate unmerged parent draft PR #40:** `3580641ed896ec14ac79a829e8d23fd36c1f1b75`.

## Original sources reviewed and precise authority

1. Attached **File 25 — Complete Public UI Profile Timeline Visual Experience Final**, §48 Optional Timeline Index (paragraphs 679–713, including rebuildable, idempotent, auditable, deletion-aware owner pointers); §77 URL Migration (paragraphs 1186–1194: old profile URLs, dry-run, collision check, execute, verify, rollback, redirect-chain test), §71 Admin, §72 REST and §89 QA.
2. Attached **Three Central Plans Consolidated Governing Master Plan**, §23 canonical owner separation (paragraphs 198–203), §25 authenticated mutations / replay protection / bounded responses (paragraphs 209–213), §§40–41 bounded SQL/background idempotency (paragraphs 495–497), §§47–50 real staging migration/reconciliation and rollback (paragraphs 533–540).
3. **File 17** owns Network/Follow authorization, **File 20** owns global shell, **File 21** owns canonical published content, **File 24** is native security assurance (File25 contract main at `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb` already reviewed on PR37), and **File 26** owns platform-wide Search; File25's index is only its *derivative public Timeline pointer projection*.

## Exact counting methodology

**40 individually numbered targeted source-to-plan checks R1715–1754**, all performed before any corrective source write. **33 existing source witnesses**, **7 check scopes needing correction/reconciliation**:

- **2 pre-existing deficits, not freshly discovered**: separate unmerged Draft PR #36 already fixed reserved WordPress URL hijack and redirect chain/cycle detection. They remained missing in the PR40 source branch. This run **reconciles** their guards and carries forward the exact independent behavioral regression. Do **not** add the old PR36's 40 numbered checks to this run as new work.
- **5 new confirmed source deficiencies** in **2 distinct root-cause groups**, corrected in this unmerged source candidate.
- These are **not forty full WordPress E2E executions**; source witnesses only establish presence of relevant code, not staging/live runtime.

Machine ledger containing all forty individually numbered scopes and their outcome: `config/review1715-1754-redirect-provenance-reconciliation-audit.json`.

### Carry-forward — R1715–1716 (already known on PR36, branch parity correction)

The PR40 base had not integrated PR36's rejection of homepage, WordPress login/admin/REST/cron/XMLRPC sources and internal redirect chain/cycle detection. This reconciliation ports both defensive constraints, preserving valid independently mapped historical public-profile URLs and adding the original test `tests/review1675-1714-migration-route-guards.php` unchanged. It does **not** claim a second independent discovery. PR36 remains a separate unmerged draft; this branch is its evaluated integration candidate, not evidence of merge or deployment.

### New root cause A — R1717–1718: source aliases and stale approved dry-run activation

The active validator's duplicate map test compared literal `/old` versus `/old/` as distinct. WordPress may canonicalize both to the same route. Moreover `migration('executed')` trusted `status=ready` plus a stored checksum, even if a stored old ready map no longer complied with newly enforced protected-route and multi-hop chain policies.

**Fix:** compare normalized path identities before admitting a second source; re-run the **current exact validator** on the stored approved map immediately before activating it, require zero collisions/invalid records and exact canonical sorted map parity. If the map fails, return safe `approved_map_revalidation_failed` with **no active-map write**. Preserve existing migration read-back, audit status and rollback checks.

### New root cause B — R1719–1721: source-version manifest must be bounded and attributable

An accepted File25 Timeline index with positive `items` could still have zero `source_versions`. The provider-supplied array could have arbitrarily many distinct owner keys and arbitrarily long semver-like strings, which could create an oversized WordPress option record and conceal which owner version a completed index represented.

**Fix:** require at least one nonempty validated owner-version for any positive index item count, at most **64** owner entries, and maximum **64 bytes** each for the owner-key and version value. Malformed or oversized metadata fails the builder contract; normal File21 provider versions remain accepted. These are File25-local, conservative safety limits, **not invented limits attributed to the Word plan**.

## Behavioral regression coverage and CI scope

- `tests/review1675-1714-migration-route-guards.php` from unmerged PR36: root/auth/admin/REST/cron/XMLRPC URLs, nested install paths, 2–3-step chains/cycles, query/slash variants, normal valid routes and 500/501 inventory.
- `tests/review1715-1754-redirect-execute-index-provenance.php` executes private File25 migration validator and execute transition against simulated WordPress options; tests checksum-bearing but no-longer-approved stored maps blocked **before** active write, slash-duplicate collision, valid profile migration execution, and actual native index builder against fake bounded SQL CAS: no-provenance positive index, 65-owner overflow, oversized source-version key/value denied, and normal File21 version accepted.
- Composer governed PHP test manifest declares both tests. Historical review test lineage is extended and the Source Completion Matrix retains **all 25 original requirement groups**.

## Truth and remaining blockers

| Evidence dimension | Status |
|---|---|
| Repository HEAD — GitHub `main` at source freeze | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Corrective source | New **unmerged** stacked Draft branch based on PR40 exact `3580641ed896ec14ac79a829e8d23fd36c1f1b75` |
| Exact final commit CI | Must be fetched after **last** source/document change |
| Deployed active File25 plugin/version/artifact checksum | **Unverified** |
| Database/schema version, `wp_options`, migration state | **Unverified** |
| Installed route inventory incl custom plugins & preexisting redirect chains | **Unverified** |
| Actual File08 target-bound Booking and File17 Follow acceptance | **Unverified** |
| File24 actual deployed compliance/assurance assessment | **Unverified** |
| Real WordPress/MySQL multi-worker/Redis atomic and cache/lock verification | **Unverified** |
| Staging browser/RTL/accessibility, backup/restore, migration dry-run and rollback | **Not accepted** |
| Founder approval | **Not established** |
| Live verification | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

No merged PR, deployed artifact, live fix or production-complete claim follows from a source commit or deterministic CI-built staging candidate. The independent PR36/37/38/40 same-number audit claims may not be added as independent R1675–1714 runs; this next R1715–1754 ledger is distinct.
