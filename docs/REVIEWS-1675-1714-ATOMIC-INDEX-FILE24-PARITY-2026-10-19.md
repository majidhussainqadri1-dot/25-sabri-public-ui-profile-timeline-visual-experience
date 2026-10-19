# File25 — Reviews 1675–1714: Atomic Index Generation and Exact File24 Owner Contract

**Review date:** 19 October 2026 (Pakistan). **Frozen File25 GitHub main:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Unmerged parent draft PR #35 SHA:** `8d6f957816b01d41783469b3c8eb37531f6bca2c`.

## Original governing Word plans and exact companion source

- File25 Final §48 requires optional provider-linked Timeline Index that is idempotent, resumable, pauseable, duplicate-safe, deletion-aware and privacy compatible; §71–77 requires accurate admin/rebuild/repair/upgrade/migration behaviors; §89 acceptance item #6 requires File24 connected and #45 requires index pause/resume; §90 requires actual owner, stage and rollback sign-offs.
- Consolidated Three Central Governing Plans §39 establishes File24 assurance **without taking native authorization or privacy enforcement**, and §§47–50 require tested package/source parity, real WordPress DB, concurrency/index rebuild, staging rollback and Founder authorization.
- Independently verified File24 actual canonical GitHub repository **`majidhussainqadri1-dot/24-sabri-platform-security-privacy-compliance-and-resilience-center`**, exact **main `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`**. Its `ModuleRegistry.php` has `MAX_MANIFESTS=100` with one self manifest (99 filtered external slots), and missing semantic evidence fields force `posture=unassessed`. Its plugin entry defines `SPCRC_VERSION=0.99.0`. These are **repository source facts**, not evidence of the installed File24 version.

## Scope, number of defects and discipline

**40 individual targeted source/plan inspections R1675–1714 finished read-only before patching.** There were **30 existing source witnesses**, **nine confirmed deficient check scopes grouped in three root causes**, and **one separate assurance evidence blocker (R1684)**. No affected source defects were fabricated from a missing staging screenshot. Every check is preserved in `config/review1675-1714-atomic-index-file24-parity-audit.json`. These are not 40 end-to-end runtime executions.

### Root cause 1: R1675–1679 — index lease and projection option writes were not atomic

Prior R1635–1674 fencing checked the generation using `get_option()`, then deleted/replaced rows using separate `delete_option()` or `update_option()`; a different worker could take ownership after the read. This was an actual **time-of-check/time-of-use** (TOCTOU) race, even with prior PHP generation checks.

**Corrective source:** For a bootstrapped real WordPress runtime, stale-lease reclamation and release now use one conditional SQL `DELETE` matching the **exact serialized previously observed lock**. Initial running-state creation and final index-state update use single conditional SQL `INSERT ... SELECT` or `UPDATE ... JOIN` statements matching the exact lock plus the expected prior index state. WordPress option caches (`options`, `notoptions`, `alloptions`) are invalidated before readback. If atomic wpdb capability is missing in actual WordPress, the protected workflow **fails closed**; the old non-atomic option stub fallback is used only for isolated PHP fixtures without `WPINC`.

**Executable regression:** `tests/review1675-1714-atomic-index-generation.php` uses a deterministic fake wpdb to force another generation's ownership transition **between** PHP preflight and conditional SQL update, force a race immediately before lease-delete, deny database writes, verify cache invalidation, and verify normal success. Existing rebuild/repair/owner tests remain.

**Crucial acceptance boundary:** Fake-wpdb behavior validates intended conditional SQL logic, **not actual WordPress MySQL/InnoDB plus persistent object cache, real concurrent HTTP/PHP workers, PHP-FPM restarts, host time drift or long-lived lease expiry**. File25 cannot pass Staging QA #45 until actual rebuild pause/resume and owner integration are exercised. Do not claim atomic multi-worker safety is production accepted from isolated CI alone.

### Root cause 2: R1680 — stale reviewed File24 source pin

File25's security-adapter contract was pinned to historical File24 SHA `0be43b3f424d7b53865587b2770479ca33f51a0b` even though current File24 main, independently retrieved and examined, is `a5b8d499...`. File25's staging scenario already cited the new File24 HEAD, creating inconsistent source assertions and a legitimate mismatch.

**Correction:** Source `File_24_Integration::REVIEWED_SOURCE_COMMIT`, governed contract tests, `config/staging-dependencies.json` and related master-plan tests now agree on the current **source repository** HEAD. File24 runtime acceptance range remains >=0.99.0 and <1.0.0. The deployed File24 SHA/version still requires separate verification.

### Root cause 3: R1681–1683 — File25 silently displaced another owner's assurance manifest

`File_24_Integration::filter_manifests()` originally truncated incoming external File24 manifests to 98 before unshifting File25. Under 99+ existing external manifests, this **silently drops** another file's registration, contrary to single-owner integration and the canonical File24 registry. The File25 manifest also omitted optional typed contract fields the current File24 0.99.0 validator tracks for completeness.

**Correction:** If 99 or more other owners already occupy the external capacity, File25 leaves their collection **unchanged**, emits a capacity-blocked event, and does **not** misrepresent its own File24 registration as accepted. If capacity exists, File25 retains one canonical self-manifest without dropping unrelated entries. Declare the canonical optional manifest field names using truthful empty lists; never invent clinical data, security capabilities, ASVS verification, independent scans or a last-security-test date. `tests/file24-integration.php` now checks 98/99/150 external owners and current File24 source pin.

### External release blocker R1684 — independent security evidence

File24's exact validator demands genuine `last_security_test`, `verification_level` and semantic evidence before it grants fully assessed posture. In this run, no installed security test/report or authorized approval was obtained. They remain intentionally **blank and unassessed**. A fabricated security date would introduce a new compliance defect, not remove a blocker.

## Other blockers with an explicit unblocking path

1. **WordPress access:** The WPVibe integration is connected at the ChatGPT account level but **no WordPress sites are registered**. The specified staging host is `sabrisocialstaging.sabrihomeopathy.com`, but no WordPress site connection, database snapshot or deployed package source/checksum has been retrieved. The user must authorize a site connection, then read-only Live Reality Freeze and staging parity can begin.
2. **Hostinger staging:** Real WP + InnoDB concurrency/lease-expiry, File24 collector integration, fresh install, DB/schema and migrations, File08 booking and File17 target Follow acceptance, 320px–1920px responsive/RTL/accessibility and backup/rollback must be tested.
3. **Approval/deployment:** Draft stacked PRs must be reviewed/merged in dependency order, the exact tested release packaged, Founder acceptance recorded, then an explicitly authorized staging rollout and eventually production deployment/live retest. Repository green CI never implies any of those gates.

## Mandatory independent final release-truth report

| Field | Verified status |
|---|---|
| Repository main HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Corrective source | This new stacked **draft** above PR #35 |
| File24 canonical repository main | `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb` |
| Exact final SHA GitHub CI | Must be checked **after** all final writes |
| Active deployed File25/File24 packages and checksums | **Unverified** |
| Database/schema/wp_options | **Unverified** |
| Migration/rollback state | **Unverified** |
| Real concurrent WordPress/InnoDB acceptance | **Not performed** |
| File24 assessed security test/signoff | **Not available** |
| Staging / Founder acceptance | **Not performed / outstanding** |
| Live Verification Status | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

This report does **not** claim the full optional Index §48 pause/resume, File24 assurance acceptance or live defects are resolved. The authoritative final obstacle count separates source defects fixed in the draft from external evidence still required.
