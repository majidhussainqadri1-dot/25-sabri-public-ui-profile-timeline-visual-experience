# File 25 — Reviews 1475–1514: Migration and Upgrade Persistence (18 October 2026)

## Governing documents and frozen repository reality

Review baseline: File25 `main` **347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e**; immediately preceding stacked and unmerged draft PR #30 HEAD **bc0d362756e4ef012667c63794fc4eb6b654236d**. PR30 is source truth only, not deployed/staging truth.

Original attached File25 complete final Word plan: §64 security / §65 privacy / §§71–72 admin & REST / §75 Safe Mode / §76 Repair / §77 Migration / §89 Staging Acceptance / §90 Definition of Done, including explicit migration-backup-rehearsal and rollback proof. Three Consolidated Central Governing Plans, chapter 24 canonical owners/design/shell, chapter 38 access/security and deployment evidence, and File21 NG30 amended §33 immutable staged package and rollback acceptance. The sources demand reversible and auditable migration; this source audit is not equivalent to that acceptance.

Other owner repositories, current `main` snapshots:
- File03 `636e3ef965423887f810718abec3cd1c11c3659d`;
- File08 `92e2422d6a235632810ed4981f151fc3250784aa`;
- File09 `cfc5f781a766330314dc98c42abeca0eb7786eba`;
- File17 `8ae656e51796d1f05865d8be5dca2480443d79ca`;
- File20 `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`;
- File21 `f2eb7e95ddea327af36ea725ffb923b029f885e6`.
File24's exact repository was **not verified** (candidate path lookup returned 404). No File03/08/09/17/20/21/24 canonical data owners were modified.

## Scope and numerical accounting

**40 independently numbered targeted source checks** (R1475–1514), **30 existing source witnesses**, **10 deficient source check scopes grouped in 3 root causes**. Source witness != whole-repository clean, verified live runtime, production QA or migration acceptance. Read-only checks completed before any patch; one corrective batch follows.

The machine-readable 40-row original finding ledger is `config/review1475-1514-migration-upgrade-persistence-audit.json`.

### Group A — Reviews 1475–1477: Silent legacy route inventory truncation
`Plan_Completion::validated_redirect_map()` used `array_slice($candidate,0,500)` without marking requests longer than 500 as invalid. Dry-run could falsely label a partially migrated 501+ route inventory ready, silently dropping requested routes. Change: reject the entire 501+ input before validation; preserve 500 fully valid entries and same-site/collision/status checks. Test: `tests/review1475-1514-migration-persistence.php`, 501-blocked versus 500-approved.

### Group B — Reviews 1478–1482: Option failure falsely marked as completed migration
`migration('executed'/'rolled-back')` called `update_option` for active legacy redirects but advanced status and flushed rewrites regardless of an actual storage failure. Admin migration handlers also unconditionally redirected to a success notice. Change: compare read-back persisted active redirect map with intended state before changing completion status; mark failed writes blocked with deterministic error codes. Admin status now uses `persist_migration_result()` to verify migration journal read-back, attempt compensating restoration of the previous active map on journal-persistence failure, emit a failure event and show a **blocked** notice instead of success. A compensation write cannot guarantee recovery under a genuinely failed database; its result is recorded in the event, and real DB + backup/restore testing remains mandatory. Test: actual isolated execute/rollback method against simulated failed and successful writes, plus a structural admin journal assertion.

### Group C — Reviews 1483–1484: Schema/runtime persistence parity
`Upgrade_Manager::maybe_upgrade()` previously advanced runtime version even if schema-version `update_option` failed; runtime-version update was also unchecked. Change: check actual stored schema target **before** recording runtime version, then check actual stored runtime version; failure triggers the existing Safe Mode exception path and always releases upgrade lock. Test: simulated failed schema write, failed runtime write, successful retry in `tests/review1475-1514-upgrade-version-persistence.php`.

## Source and release evidence

The existing `tests/run-governed-suite.sh` auto-discovers PHP tests; both new tests are also declared in the Composer governed metadata manifest. Old review-number lineage checks are extended without deleting or altering any of the base **25** source-completion requirements.

| Required dimension | Source truth |
|---|---|
| Repository main HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Corrective branch | Stacked unmerged draft candidate above PR #30 |
| Last commit exact-HEAD CI | Must be fetched from GitHub after final write |
| Deployed plugin/version/build/checksum | Unknown |
| Live database/schema | Unknown |
| Installed `wp_options`, migration and rollback state | Unknown |
| Real WordPress migration backup/rollback rehearsal | Not performed |
| Staging / Founder acceptance | Not performed |
| Live Verification Status | **Unverified** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

Never call a repository-level source correction a resolved live incident. Real upgrade behavior, simultaneous option failures, Hostinger staging, controlled rollback, deployed package parity and post-deployment re-test remain release blockers.
