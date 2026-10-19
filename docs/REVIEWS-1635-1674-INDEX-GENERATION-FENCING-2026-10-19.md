# File25 — Reviews 1635–1674: Index Generation Fencing and Provider Version Integrity
**Audit:** 19 October 2026. **Frozen GitHub main:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Unmerged parent draft PR #34:** `75b66c1a565c81759d74486c2cf59a966ecbb5d0`.

## Original governing DOCX evidence
- **File25 Final Master Plan**, §48 Optional Timeline Index (paragraphs 679–713): author/provider-linked derivative index, idempotent, rebuildable, resumable, pauseable, auditable, duplicate-safe and deletion-aware. §71 Admin Configuration and §72 REST. QA §89 (test item 45 requires index rebuild pause/resume).
- **Three Central Consolidated Governing Plan**: canonical owner separation, derivative index is never source of truth (paragraphs 200–204), strict mutation/replay control (paragraphs 209–212), concurrent/background lock idempotency (paragraph 497), and staging tests of upgrades/concurrency/index rebuild (paragraphs 536–540).
- **File21 NG30 Amended**: File21 remains publication canonical owner, with concurrency and replay risk checks (paragraphs 883,1175). File25 holds only minimal public projection pointers, not original articles or private details.

Forty distinct source/plan checks were completed before patching. **33 existing source witnesses, seven deficient check scopes across two independent source root causes**. Numbered check evidence: `config/review1635-1674-index-generation-fencing-audit.json`. These 40 source checks were not 40 complete WordPress integrations nor staging acceptance.

## Root cause A — Reviews 1635–1639: index generation lease fencing

**Original code:** `Plan_Completion::rebuild_index()` released the owner index lock in the builder's `finally` **before** writing its final index state. The release was unconditional. If a long-running worker exceeded the five-minute lease and a newer generation replaced it, the stale worker could delete the newer worker's lock, overwrite its new running state, emit a stale success event and incorrectly report completion.

**Corrective source candidate:** retains lock through terminal index write/event; checks matching unexpired generation lock and matching running-generation index state immediately before commit; reports machine `index_generation_superseded` and refuses stale completion. Cleanup checks generation identity instead of intentionally clearing another owner's lock. Stale lock reclamation also rechecks its earlier observed value.

**Important technical limit:** WordPress `get_option()` followed by `delete_option()` is **not an atomic compare-and-swap**. These changes stop deterministic observed lock replacement scenarios in the regression but **do not conclusively solve simultaneous interleavings**, particularly under a distributed persistent object cache. An exact installed WordPress + MySQL multi-worker/lease-expiry test or atomic conditional lock implementation is required before claiming concurrency safety. Do not report that race risk as fully closed in production.

## Root cause B — Reviews 1640–1641: silent source-version loss

Accepted external index builder metadata `source_versions` was normalized by dropping invalid owner keys/version values. The index could be marked `complete` while a supplied (malformed) File21 or other owner version vanished from its provenance. Now malformed entries fail the **entire** accepted builder contract instead of silently publishing a partial manifest. Valid, bounded source version values still work.

## Tests and other source boundaries

`tests/review1635-1674-index-generation-fencing.php` executes the real File25 index method against isolated WordPress option/filter stubs, simulating takeover of both lock and index by a newer generation, takeover of lock alone, malformed provider version metadata, and a valid completed rebuild. It checks no stale success event, no removal of a newer lock, no stale index overwrite, and preservation of approved File21 version.

The governed Composer test manifest includes this new PHP test. Source-completion matrix preserves all 25 baseline requirements; historical review-range tests remain. No File03/08/09/17/20/21/24 canonical owner repository/data is modified.

## Independent release status

| Dimension | Source-verified status |
|---|---|
| GitHub `main` HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at freeze |
| Corrective source | New unmerged stacked Draft PR atop #34 |
| Exact final commit CI | Must be confirmed after all file updates |
| Active deployed package / checksum parity | Unverified |
| Live database/schema/wp_options / Migration State | Unverified |
| Real WordPress multi-worker index lease stress test | **Unverified, residual risk** |
| File24 exact native security source/owner contract | Unverified |
| Staging, RTL/accessibility, privacy, Founder acceptance | Not completed |
| Live Verification Status | Not performed |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** Green source CI, if obtained, cannot replace installed-source parity or live re-test.
