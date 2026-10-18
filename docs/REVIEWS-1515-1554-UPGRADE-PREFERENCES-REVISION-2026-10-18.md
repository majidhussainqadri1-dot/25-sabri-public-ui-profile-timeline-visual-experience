# File 25 — Reviews 1515–1554: runtime-version, preference commit and revision integrity

**Audit date:** 18 October 2026 (Pakistan, UTC+05:00). **Source baseline:** `main` 347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e; previous unmerged draft PR #31 HEAD `900893b7a65c542df30a77a6752c493733964d90`.

## Governing Word plans used

1. Original attached **File 25 — Complete Public UI, Profile Timeline and Visual Experience — Final**, §§64–65 privacy and authorization, §71 File25 administrator preferences, §72 strict REST and safe errors, §§74–77 observability/Safe Mode/repair/migration, §§89–90 staging acceptance and Definition of Done.
2. Original attached **Three Central Plans Consolidated Governing Master Plan**, §§24.9 RCD-030–038: plan-first source review, non-destructive/idempotent migrations, distinct specified/coded/QA-green/live states, exact evidence and rollback documentation.
3. Original attached **File 21 NG30 Amended**, §33.7 migration smoke/log/rollback evidence, §33.8 database and rollback reports, §35 verified backup/release preflight.
4. Related canonical owner source boundaries: File00/03 identity, File08 Clinic, File09 Doctor verification, File17 Follow, File20 shell, File21 timeline, File24 security assurance. No native owner storage was modified in this branch; File24 exact repository/head remains unverified.

## Method and exact counts

**40 independent targeted plan/code checks (R1515–1554)** completed read-only before patching. **30 existing source witnesses**, **10 deficient check scopes**, **3 independent root causes**. The 40-row machine ledger is `config/review1515-1554-upgrade-preferences-revision-audit.json`. These checks are **not** 40 complete WordPress integrations, not forty migrations, and not proof of a deployed package.

### Cause 1 — R1515–1517: Runtime-version retry blocked by already-current schema

`Upgrade_Manager::maybe_upgrade()` previously exited whenever `sabri_public_experience_schema_version` reached the required schema. If a previous upgrade persisted schema successfully but failed writing `sabri_public_experience_runtime_version`, the later successful run would never repair the stale runtime marker. Correction computes schema-pending and runtime-pending **independently**, acquires the existing upgrade lock for either, rechecks after locking, and only flushes rewrite rules if schema still needs upgrading. Runtime marker write is read back and failures still route to the existing Safe Mode. `tests/review1515-1554-upgrade-runtime-retry.php` simulates failed runtime-only write, lock release, then successful retry without unnecessary rewrite flushing. Previous schema upgrade regressions are retained.

### Cause 2 — R1518–1522: Preference option writes falsely returned success

`Plan_Completion::write_preferences()` previously ignored `update_option()` results for the public preferences, revision metadata and bounded audit history; it unconditionally emitted `preferences_updated` and returned `status=updated`, so REST could report HTTP 200 while a privacy opt-out was not persisted. Correction uses **read-after-write verification on all three File25-owned option records**, attempts compensating writes back to their previous records if any write fails, emits a bounded failure event (only failed option name, compensation status, operation source), and returns failed state. REST maps failure to retryable HTTP 503 with compensation flag; admin redirects to a failure notice, never success. WordPress options are not an atomic multi-row transaction; even attempted rollback can fail under a real database outage. Actual WordPress/MySQL/backup rehearsals remain required.

### Cause 3 — R1523–1524: Invalid optimistic revision silently accepted

An explicitly invalid REST `revision` formerly parsed to `null`, which was treated exactly as “no revision” and bypassed the conflict guard. Invalid or absent admin-form revision also previously collapsed to `null`. Corrections: explicitly malformed REST revision returns HTTP 400; missing/invalid admin revision redirects to `revision_invalid` **without writing preferences**. Valid but stale revision still returns conflict.

## Tests and source bookkeeping

- `tests/review1515-1554-upgrade-runtime-retry.php`: actual upgrade manager with failed/successful metadata option store and no repeated schema flush.
- `tests/review1515-1554-preferences-write-integrity.php`: actual File25 preference writer with three different failed option writes, rollback verification, bounded failure event, successful revision advancement, invalid REST revision 400, REST write failure 503 and conflict preservation.
- Governed test runner auto-discovers PHP tests, Composer declares both; prior tests retained.
- `config/source-completion-matrix.json`: new followup without deleting or modifying the 25 canonical base requirement groups. Historical range tests extended rather than bypassed.

## Source, staging and live truth

| Status dimension | Evidence at audit |
|---|---|
| Repository main HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Corrective source | Unmerged stacked draft candidate on PR #31 |
| Exact corrective HEAD CI | Verify externally *after* final write |
| Deployed version/package checksum | **Unverified** |
| WordPress live DB/schema and `wp_options` | **Unverified** |
| Migration state / schema parity | **Unverified** |
| Staging MySQL/RBAC/privacy/RTL and rollback rehearsal | **Not performed** |
| Founder acceptance | **Not established** |
| Live Verification Status | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

A green repository CI checks code/test-package quality; it cannot mark an installed staging or production incident “Resolved.”
