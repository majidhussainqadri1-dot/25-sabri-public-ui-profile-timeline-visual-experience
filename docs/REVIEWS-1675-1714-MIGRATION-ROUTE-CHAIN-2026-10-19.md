# File25 Reviews R1675–1714 — Migration Route Ownership and Redirect Chains
**Date:** 19 October 2026. **Frozen repository main:** `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`. **Immediate unmerged base:** Draft PR #35 HEAD `8d6f957816b01d41783469b3c8eb37531f6bca2c`.

## Source-of-truth plans reviewed
- The attached **File25 Complete Public UI Profile Timeline Visual Experience Final** §77 URL Migration requires identifying retired **profile** URLs, a validated redirect map, dry-run, collision detection, execution, verification, rollback and **redirect-chain testing** (paragraphs 1186–1194); §64 Security Integration and §83 unsafe redirect tests.
- The attached **Three Central Consolidated Governing Master Plan** §§22–25/38 enforces separate File00 authentication and File20 structural shell ownership, strict owner authorization, safe public routes, and fresh migration/staging/rollback evidence.
- Companion File17 owns Network/Messages; File21 owns publication data; File26 search; File25 must not create a second global router or redirect unrelated authentication/core WordPress entrypoints.

## Independent read-only reviews before patches

Exactly **40** specific source/plan review checks R1675–1714. **30 existing source witnesses**, **10 confirmed deficient check scopes in two independent root causes**. Each numbered check and its file is in `config/review1675-1714-migration-route-chain-audit.json`.

These are not 40 end-to-end WordPress runs. Positive source witnesses merely identify code inspected.

### Group A — R1675–1679: old profile map could hijack reserved WordPress routes

`Plan_Completion::validated_redirect_map()` accepted any absolute, syntactically valid same-site source pathname, including `/`, `/wp-login.php`, `/wp-admin/`, `/wp-json/`, `/wp-cron.php`, `/xmlrpc.php`, and subdirectory equivalents. Such routes are not retired public profile URLs; a mistakenly approved File25 migration could redirect login, the homepage, or WordPress infrastructure.

**Source correction:** reject root and reserved auth/admin/REST/cron/XMLRPC paths at dry-run validation time. Valid independent retired public-profile URLs remain eligible, pending actual site inventory/Founder approval. This is defense in depth; unrelated *custom* plugin routes still require explicit site-specific route collision inventory.

### Group B — R1680–1684: migration chains and cycles were not detected

The validator rejected a route redirecting to itself and duplicate `from` entries, but did **not** compare a destination's path to any other source pathname in the same map. Two-step and three-step chains, cycles, query-bearing destination chains, and trailing-slash variants could be approved and cause redirects with unexpected loops.

**Source correction:** after validated map construction, build normalized source pathname identities (trailing slash removed except for root). Compare every validated redirect target pathname against the source set; count and refuse any multi-hop chain, cycle, query-bearing or slash-equivalent source/destination relationship. Existing approved checksum/journal state machine, dry-run `ready` versus `blocked`, active state persistence, rollback, and same-site URL checks remain intact.

### Failure-injection / behavioral tests

`tests/review1675-1714-migration-route-guards.php` exercises the actual private validator and dry-run through bounded WordPress stubs. It explicitly rejects homepage, nested auth/admin/REST/cron/XMLRPC routes, 2-hop chains, 2/3 cycles, slash and query aliases, and tests two independent valid redirects, checksum-bearing dry run, 500-entry valid inventory, and 501-entry rejection. It is registered in Composer's governed test manifest and auto-discovered by the PHP test runner.

A repository test fixture is **not** proof of actual installed rewrite configuration, migration inventory, database, link chains outside File25, or live behavior.

## Exact source versus installed reality

| Evidence | Verified in this review |
|---|---|
| Main source HEAD at freeze | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Parent source | `8d6f957816b01d41783469b3c8eb37531f6bca2c`, draft PR35, unmerged |
| Source changes | New stacked **draft candidate** (not merged/deployed) |
| Exact final source CI | Must be checked from final immutable SHA |
| Installed/deployed plugin code/version/checksum | **Unverified** |
| Production WordPress database/schema / wp_options | **Unverified** |
| Migration dry-run, active map, backups, rollback state | **Unverified** |
| Real route collision inventory for custom modules | **Unverified** |
| Staging acceptance: auth, redirects, real 301/308, rollback, RTL, mobile | **Not established** |
| Founder acceptance / live retest | **Not established** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

Never claim that a green GitHub CI constitutes deployed/live resolution. The corrective draft awaits exact-HEAD CI, controlled merge, artifact/package parity, staging migration/backup rehearsal and Founder approval.
