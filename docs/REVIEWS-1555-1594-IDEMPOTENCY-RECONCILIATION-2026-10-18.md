# File 25 — Reviews 1555–1594: REST Idempotency and Projection Reconciliation (18 October 2026)

## Exact governing source and boundary

The baseline frozen for this source audit was GitHub `main` at `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`; unmerged previous Draft PR #32 at `be1ce45a4caee03b7498ccc9cf7287d3e12866f1`. This audit and corrections are **not** evidence of installed WordPress code.

Source documents actually read: (1) original File25 Complete Public UI/Profile Timeline Final, §§64–65 security/privacy, §71 administration, §72 REST, §74 observability, §§80–90 staging and release proof; (2) Three Central Consolidated Governing Plans §§24–25: separate canonical data owners, versioned mutation APIs with idempotency/replay protection, authenticated object authorization; §38 security and §§47–48 deploy/QA; (3) File21 NG30-Amended §§21.4–21.5 persisted event and audit validity, §§29–35 diagnostic/retry/rollback. File03 canonical public profile, File08 appointment owner, File09 doctor decision, File17 social graph, File20 structural shell, File21 publication and File24 security assurance remain separate from File25.

**Method:** Forty distinct numbered source/plan checks completed **before** corrections. Thirty-five existing source witnesses and **five deficient checks across two independent code root causes**, corrected in this unmerged source candidate. These are not forty installed WordPress/browser/staging tests. The detailed machine ledger is `config/review1555-1594-idempotency-reconciliation-audit.json`.

## Confirmed root cause 1 — R1555–1557

`Plan_Completion::mutation_preflight()` previously keyed an idempotent response only by actor, endpoint operation and the caller-supplied Idempotency Key. It replayed any cached success without comparing the actual mutation intent. Consequently the **same** key supplied for a different `POST /preferences` privacy payload could receive a success for an **earlier different** payload; similarly two different `reconcile-profile` user IDs could receive the same recorded success.

**Change:** Compute a deterministic SHA-256 fingerprint of a canonicalized preferences JSON body (including the revision) or the exact target user ID for reconcile-profile. Store the fingerprint with the cached success and compare it **before** reusing the receipt. Replaying the same request (including equivalent associative JSON order) continues to work. Reuse for changed payload/target or an old receipt lacking the fingerprint now returns safe HTTP **409 `idempotency_payload_conflict`**, without executing a different mutation. Malformed receipts fail with a retryable 503. Fingerprints, rather than raw sensitive preference payloads, are stored in the receipt.

**Remaining limitation:** WordPress Transient persistence failure/eviction and multi-process replay concurrency are not proven solved by this change. The separate ten-second operation rate option reduces duplicate concurrency but is not a transactional substitute. Production cache topology, atomic replay admission, and concurrency/load tests remain an explicit release gate.

## Confirmed root cause 2 — R1562–1563

`rest_reconcile_profile()` defaulted the external `sabri_public_experience/reconcile_profile_projection` extension response to `accepted=true`, so even with **no actual accepted owner projection adapter**, simply calling `clean_user_cache()` could emit `profile_reconciled` and report HTTP 200 success.

**Change:** The default is now `accepted=false`. Positive success requires a native extension/adapter response with **strict boolean** `accepted === true`, exact `scope === file-25-public-projection` and `owner_data_mutated === false`. Missing, false, string-like, wrong-scope, and mutating-owner assertions fail with safe HTTP **502 `reconciliation_contract_failed`**, without emitting the success event. No native owner data is edited or duplicated.

## Executable evidence and source change discipline

New regression `tests/review1555-1594-idempotency-reconcile.php` executes the actual `Plan_Completion` REST mutation methods under bounded WordPress stubs. It tests: successful privacy opt-out, key replay with reordered equivalent payload, conflicting payload 409 and privacy unchanged, legacy unbound receipt denial, missing adapter 502/no event, malformed accepted flag 502, owner-mutation assertion denial, wrong scope denial, approved adapter success and exactly one success event, same key for different target 409. PHP governed suite auto-discovers tests and Composer metadata also lists this new regression.

The numbered Review ledger, test/manifest, historical lineage tests, README, CHANGELOG and source completion matrix are updated without changing its 25 original requirement groups. No File03, File08, File09, File17, File20, File21 or File24 owner database/source has been edited.

## Separate truth values and blockers

| Category | State |
|---|---|
| Source repository `main` | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` at freeze |
| Corrective source | New **unmerged stacked draft** above PR #32 |
| Exact final candidate CI | Confirm via GitHub check-runs on final immutable HEAD |
| Deployed/active WordPress File25 package/version/checksum | **Unverified** |
| WordPress live database/schema, `wp_options`, migration state | **Unverified** |
| File24 exact owner repository contract | **Unverified** |
| Real cache concurrency and WP role/permission/privacy/RTL tests | **Not performed** |
| Hostinger staging / backup / rollback / Founder acceptance | **Not established** |
| Live verification | **Not performed** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** A green source CI or successful draft PR is **not** deployed remediation.
