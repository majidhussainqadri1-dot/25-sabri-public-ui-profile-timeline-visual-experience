# File 25 — Reviews 1595–1634: Index Rebuild and Admin Repair Persistence (18 October 2026)

## Governing documents and exact source freeze

The source truth for this audit was File25 GitHub `main` **347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e**, with previous unmerged draft PR #33 HEAD **37e228baa3f6affdb98cd8ab9d7c2cb797b61367**. Neither is the verified deployed WordPress package.

- Original attached **File25 Complete Public UI / Profile Timeline / Visual Experience Final**, §§64–65 domain security and privacy, §71 admin workflows, §72 REST, §74 observability, §75 Safe Mode, §76 Repair, §§80–90 QA, staging and Done.
- Original attached **Three Central Consolidated Governing Plans**, §24 canonical owners/idempotent events, §25 authorized and rate-limited mutations with audit, §38 security/fail-closed behavior, RCD034–036 separated source/staging/live truth and documented review results.
- Original attached **File21 NG30 Amended**, §§33.7–33.8 index/database/migration smoke, rollback evidence and §35 post-deployment parity and retention.

The scope included native File03 profile DTO, File08 clinic, File09 verification, File17 follow, File20 shell, File21 timeline, and File24 assurance boundaries. No data-owner repositories were edited. File24's exact owner repository/head and actual active installed version remain unverified.

## 40 separate targeted checks — honest count

- **40** numbered plan-to-source inspections R1595–1634 completed **before changes**.
- **31** source witnesses for existing positive guards; **nine** affected check scopes grouped in **two distinct code root causes**, corrected in this unmerged branch.
- Number-by-number ledger: `config/review1595-1634-index-rebuild-admin-status-audit.json`.
- These are **not 40 whole WordPress integration runs**. The isolated PHP fixture tests are narrower.

### Root cause A — R1595–1599: Rebuild index state never verified persisted

`Plan_Completion::rebuild_index()` called `update_option(INDEX_OPTION, running)` and later `update_option(INDEX_OPTION, final)` without any stored-state readback. If the generation marker failed to persist, it still invoked the external Timeline index builder. If a completed index failed to persist, it still emitted `timeline_index_rebuilt`, returned complete, and `rest_rebuild_index` could return HTTP 200 although WordPress was still holding a stale/running projection.

**Correction:** Validate the stored running state before invoking the builder; on failure release the bounded lock and return a safe `index_state_persistence_failed` failure. After the builder, read back the final stored state before declaring success. If not persisted, emit `timeline_index_storage_failed` and return a bounded failure. Emit `timeline_index_rebuilt` **only for genuinely persisted complete** states. REST's existing 503 on non-complete now applies to these genuine persistence failures. Test `tests/review1595-1634-index-persistence.php` runs actual private method with WordPress option-write failures for both start and commit, as well as success.

### Root cause B — R1600–1603: Admin success notices and partial Repair

`handle_rebuild()` always displayed `index_rebuild_finished` even on busy/failed attempts. `handle_repair()` always displayed `repair_complete`, including partial repair and journal write failure. Furthermore `repair()` marked the stale-index sub-operation true unconditionally, even if its `update_option()` failed.

**Correction:** Admin index notices branch on actual complete/busy/failed statuses. Admin repair shows complete only for a complete result plus persisted File25 repair-state journal; otherwise `repair_partial`. Stale-index repair sub-operation readbacks its persisted index and reports `false` if the owner-state write failed. The test `tests/review1595-1634-admin-repair-status.php` executes the repair method under denied and permitted index writes, plus a static check for actual admin redirect notice dispatch. The admin redirect's terminating `exit` was **not** tested inside a real HTTP WordPress session.

## Tests, matrix and release truth

Both isolated PHP regressions are declared in Composer's governed test manifest (suite also auto-discovers PHP files). Source-completion matrix preserves **all original 25 core requirement groups**; historical review-range tests are retained and extended.

| Required status | Verified source information |
|---|---|
| Repository main HEAD at audit | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Candidate source | New unmerged stacked draft branch on #33 |
| Exact final commit CI | Requires last-commit GitHub check-runs before any Green claim |
| Deployed plugin/version/build/checksum | **Unverified** |
| DB/schema and `wp_options` | **Unverified** |
| Migration, index and cache actual live state | **Unverified** |
| Full Staging WordPress/MySQL, RTL/accessibility, role and rollback | **Not accepted/tested by this review** |
| File08 Booking, File17 Follow native acceptance, File24 exact owner | **Outstanding** |
| Founder sign-off | **Not established** |
| Live Verification Status | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

Do not confuse a green source repository check or a CI-built staging *candidate* package with an installed, parity-verified staging/production version. The existing external Native File21 index-builder contract has not been verified accepted under real WordPress roles and DB concurrency.
