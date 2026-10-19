# File25 Source Audit — Reviews 1675–1714: Atomic Index Fencing and File24 Current Contract
**Date:** 19 October 2026; **GitHub main:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`; **unmerged previous PR35 HEAD:** `8d6f957816b01d41783469b3c8eb37531f6bca2c`.

## Governing DOCXs and exact owner truth

- File25 Complete Public UI Profile Timeline Final, §48 optional derived Timeline Index (idempotent, resumable, duplicate-safe), §71 admin, §§75–77 repair and migration, §89 index rebuild pause/resume and staging requirements.
- Three Central Consolidated Governing Plan §§24–25 and §38: native owners and immutable authorization; §§47–50: dependency staging, concurrency, backups, source/package parity and rollback before production.
- File21 NG30: publication and native source-version ownership remains with File21.
- **File24 actual canonical repository identified**: `majidhussainqadri1-dot/24-sabri-platform-security-privacy-compliance-and-resilience-center`, exact `main` commit **`a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`**. Its `ContractCompatibilityPolicy::MANIFEST_CURRENT/MINIMUM` = `1.2.0`; `ModuleRegistry` enforces named inventory fields and marks missing security test timestamps `unassessed`. The previous File25 reviewed commit `0be43b3f424d7b53865587b2770479ca33f51a0b` was **82 source commits behind** current File24 main.

**Method:** 40 distinct source/plan/cross-file checks R1675–1714, completed before corrective modifications. 30 code witnesses, ten deficient scopes clustered into two root causes. These are **not forty installed WordPress E2E tests**. Complete numbered evidence: `config/review1675-1714-atomic-lock-file24-contract-audit.json`.

## Root cause A: R1675–1680, real race in index option operations

WordPress `get_option` followed by separate `delete_option`/`update_option` allowed interleavings: one older worker could observe its expired-generation lock, then mutate/delete the newly installed owner's lease; the final index state could be overwritten after a generation check.

**Source fix:** introduced `Index_Atomic_Options`, using a prepared, single MySQL `UPDATE ... INNER JOIN` conditional on both expected File25-owned index row and exact serialized generation lock. Lock reclaim/release uses a single conditional `DELETE ... WHERE option_name AND BINARY option_value`. Serialized comparisons are byte-exact, not collation-insensitive. The bounded generation lock is now used for both running and final index-state transitions. Failed atomic operations report failure and cannot fall back to unsafe WordPress option mutations. Cache invalidation follows successful DB mutation. No File21, File24 or other owner tables are written.

**Tests:** `tests/review1675-1714-atomic-index-concurrency.php` simulates a rival installing an index/lock at the very moment the conditional SQL UPDATE or DELETE executes, a failed expired-lease reclamation, normal valid completion and unavailable atomic DB backend. Earlier `review1595...` and `review1635...` tests were migrated to a shared `tests/fixtures/fake-atomic-index-wpdb.php` with the same conditional SQL operands. **Real MySQL/InnoDB multi-worker stress, persistent cache invalidation and installed rollback remain unverified** until a connected staging site is available.

## Root cause B: R1681–1684, outdated File24 source and manifest contract

The File25 adapter embedded an old File24 source pin and `contract_version:1.0.0`. The exact File24 current-main policy now requires version `1.2.0`, and its `ModuleRegistry` requires `tables`, `files`, `secret_classes`, `exporters`, `erasers`, `emergency_callbacks`, `verification_level` and other enumerated fields for completeness.

**Source fix:** update reviewed SHA pin to real File24 `main` and File25's integration/manifest contract to `1.2.0`; declare explicit bounded File25-only inventory fields, no fictional tables, vendors or secrets. `last_security_test` remains **blank**, so File24 must still consider this a non-accepted **unassessed** assurance manifest until independent security testing actually occurs. `tests/file24-integration.php` updated to assert version, SHA and required v1.2 inventory.

## Remaining release blockers: exact truth table

| Dimension | Status |
|---|---|
| GitHub main | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Corrective source | Stacked **unmerged draft** above PR35; exact final SHA requires fresh CI check |
| File24 actual owner repository identity | **Verified** |
| File24 v1.2 source compatibility | **Draft corrective adapter**; still needs real File24 staging handshake |
| Genuine concurrent MySQL index lease | Conditional SQL implemented; **real DB/multi-process proof pending** |
| WPVibe connected WordPress sites | **0**, no live/staging inspection possible through connector until user authorizes one |
| Deployed version, package checksum and database/schema | **Unknown** |
| Installed `wp_options`, migration and rollback state | **Unknown** |
| Founder staging/production approval, fresh role/RTL/privacy QA and live re-test | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

Repository-level CI passing, a PR or a staging-candidate ZIP is not a verified staging deploy, installed code parity or a Resolved live incident. Do not merge/deploy unmerged PRs without Founder approval.
