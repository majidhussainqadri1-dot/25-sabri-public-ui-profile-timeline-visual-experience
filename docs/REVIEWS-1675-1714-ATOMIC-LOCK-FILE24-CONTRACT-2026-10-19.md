# File25 — Reviews 1675–1714: Atomic Option Locks, Real File24 Owner and Release Blockers

**Audit date:** 19 October 2026. **Repository main at freeze:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Immediate parent:** unmerged Draft PR #35 at `8d6f957816b01d41783469b3c8eb37531f6bca2c`.

## Source of authority

- Attached original **File25 Complete Public UI/Profile Timeline/Visual Experience Final**, §48 optional pointer index: rebuildable, idempotent, batch/resume/pause/audit, duplicate-safe, deletion aware and owner-linked. §71 administrators; §72 mutation/API; §74 diagnostics; §75 Safe Mode; §76 Repair; §89 test item 45 rebuild pause/resume and release tests.
- Attached **Three Consolidated Central Governing Plans**, §24 canonical owner vs derivative index; §25 API/replay safety; §40 query performance and overlapping background-job locks; §47 staged upgrade/reconciliation/index rebuild, §48 acceptance and release evidence. Staging, backup restore, rollback and Founder sign-off are prerequisites for any Live promotion.
- Attached **File17 Communication Network**: File17 is sole network/follow/message owner, while File25 only renders action links; File20 owns shell; File03 owns public profile data; File21 owns publication and original timeline objects.
- **Newly verified** File24 GitHub repository `majidhussainqadri1-dot/24-sabri-platform-security-privacy-compliance-and-resilience-center`, main SHA `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`. Actual plugin bootstrap declares `SPCRC_VERSION = 0.99.0`; `ModuleRegistry::MAX_MANIFESTS = 100` including its own registry entry. This verifies current repository source identity and capacity semantics **not** deployed parity or installer version.

## Counting protocol

**40 separately numbered targeted checks R1675–1714**, all conducted source-read-only before modifications. **30 scoped existing/source-or-owner witnesses**, **ten deficient check scopes**, grouped into **three real source root causes**. A source witness is not a 100% complete functional requirement; these are not 40 end-to-end WP runs. See full per-check ledger `config/review1675-1714-atomic-leases-file24-contract-audit.json`.

### A. R1675–1679: Index generation still had non-atomic PHP read/delete and read/update races

After previous PR35’s successful sequential generation fencing, actual `get_option()` followed by `delete_option()` and `update_option()` could still interleave with a second worker *between* PHP ownership check and database mutation. The old worker could erase a replacement lease or overwrite its index after passing an earlier PHP check.

**Corrective source candidate:** exact serialized-value conditional MySQL `DELETE FROM wp_options WHERE option_name = ? AND BINARY option_value = ?` for stale reclaim / owner release. Final index publish uses **one** SQL `UPDATE idx INNER JOIN lease` statement: matches the same lease option's serialized generation and the previous running index value, then updates only that one generation's record; zero changed rows fails closed. Autoload=false WordPress individual-option caches are invalidated after direct SQL. The production non-DB fallback **fails closed**; an isolated CLI fixture retains stub behavior and does **not** imply concurrency atomicity.

New PHP regression: `tests/review1675-1714-atomic-db-lock-races.php`, which simulates changes at the exact SQL boundary, checks rejection of overwritten index state and of deleting replacement leases. It models SQL option compare semantics with a fake `$wpdb`; it does not run InnoDB.

**Important limit:** This source pattern increases protection but must still be exercised against real installed WordPress, MySQL/InnoDB, persistent object cache and multiple simultaneous workers. It is not a validated distributed-lock or Stage-Acceptance claim.

### B. R1680–1682: Upgrade Manager stale or exiting worker could release the new owner

`Upgrade_Manager::acquire_lock()` previously did read/TTL check then unconditional delete+add. `release_lock()` read token and unconditionally deleted the WordPress option if it still looked owned. Both sequences could race with a new upgrade worker. Fix uses the same conditional serialized-value SQL DELETE to reclaim and release the exact upgrade option; cannot delete another worker's replacement lease. A failed atomic comparison returns blocked; no silent normal WP fallback. The injection regression simulates a new upgrade token between the stale-check and SQL DELETE.

### C. R1683–1684: File25 silently displaced another File24 manifest at the 99-module ceiling

`File_24_Integration::filter_manifests()` sliced a collection to 98 then forced File25 to the first slot, silently losing one or many unrelated module manifests when the list was full. Actual File24 registry accepts only 99 external manifests after its own. The correction preserves unrelated entries and, if `count >= 99`, defers File25’s manifest with `sabri_public_experience/file24_manifest_capacity_blocked` instead of cutting someone else’s. With 98 external manifests or fewer, File25 is still inserted first. `tests/file24-integration.php` now tests full capacity without owner displacement and the exactly-one-slot boundary.

## Blockers — actual status, not assumed cleared

1. **Cleared in current source evidence:** Unknown File24 repository/head — actual main `a5b8d499...` identified and cross-checked for manifest contract and runtime constant. This does **not** prove live File24 version.
2. **Corrected in Draft Source, not yet staging-tested:** Non-atomic index/upgrade lease logic, and File24 silent manifest truncation. Exact last SHA green CI is required separately.
3. **Still blocked:** Installed/deployed File25 and File24 artifact/version/hash parity; WordPress live `wp_options` and schema; actual migration/upgrade status; real InnoDB race/load tests; File08 native Booking and File17 Follow action acceptance; File20/21/24 staging UX/security; full File25 index pause/resume and rollback evidence; Founder acceptance; live deployment and retest.
4. **WordPress access:** available WPVibe connector `list_sites` reports **no connected sites**. Therefore this session cannot inspect deployed SQL/schema or force site-specific staging acceptance. Connection requires explicit user-side approval. No site installation or live mutation was attempted.

| Mandatory report field | Current evidence |
|---|---|
| Repository `main` HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Candidate source | New unmerged stacked Draft PR atop #35 |
| Deployed File25/File24 Version | Unknown |
| DB Version and `wp_options` | Unknown |
| Migration State | Unknown |
| Real InnoDB/WordPress Concurrency Test | Not performed |
| Staging/Founder Acceptance | Not performed |
| Live Verification Status | Not verified |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

A passing Source CI must never be reported as a live defect resolved without package deployment, full staging acceptance, owner permissions and post-deploy parity/retest.
