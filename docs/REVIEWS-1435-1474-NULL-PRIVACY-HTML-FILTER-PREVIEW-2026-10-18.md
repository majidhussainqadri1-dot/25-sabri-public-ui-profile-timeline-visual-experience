# File 25 — Reviews 1435–1474 (18 October 2026)

## Exact source and governing evidence

- Main HEAD at freeze: `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`; latest previous unmerged Draft PR #29 HEAD `cd4a146e17fa83aec340efa5257af91dc77b5222` (Reviews 1395–1434).
- File25 **Final** Word source, reviewed §§42–47 (public Timeline), §54 (local search boundary), §§64–65 (security/privacy), §69 (metrics), §§71–72 (admin + REST), §§89–90 (staging and Definition of Done).
- Consolidated **Three Central Governing Plans** reviewed §§20–24 (design/shell/ownership), §§37–38 (privacy and deny-by-default), §50 (release).
- File21 NG30 Amended reviewed §26.2 (public projection privacy), §27 (accessibility), §§31–32 (exact-build CI).
- Fresh GitHub main source refs: File03 `636e3ef965423887f810718abec3cd1c11c3659d`; File08 `92e2422d6a235632810ed4981f151fc3250784aa`; File09 `cfc5f781a766330314dc98c42abeca0eb7786eba`; File17 `8ae656e51796d1f05865d8be5dca2480443d79ca`; File20 `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`; File21 `f2eb7e95ddea327af36ea725ffb923b029f885e6`. File24 exact repository HEAD **unverified** (looked-up repository path returned 404).

**Method:** 40 discrete targeted plan-to-source checks (R1435–1474); 29 existing source witnesses; 11 deficient check scopes in **three** independent root causes; corrected in source candidate. These are **not** 40 complete WordPress end-to-end runs and do not establish production deployment parity.

## Corrections after all 40 inspections

1. **R1435–1437: Explicit null opt-outs in File25 preferences.** In `Plan_Completion::normalize_preferences`, `$input[$key] ?? $definition['default']` treated an **explicit** `null` as absence. For `public_visibility`, `public_metrics`, and `profile_local_search` this could silently restore the valid default `true` rather than fail closed. Changed to `array_key_exists` semantics so missing keys preserve the default/current state but invalid present null is passed to the strict sensitive-boolean validator and becomes `false`. Isolated real method test `tests/review1435-1474-null-privacy-preferences.php` exercises absent/explicit null/other invalid/valid and sparse updates.

2. **R1438–1443: Malformed HTML Timeline filters broadened the request.** On an invalid nonempty `type`, `provider`, `year`, `language`, `topic`, or `sort`, the renderer's previous parsing could convert targeted constraints to `''` or default `latest`; public Timeline retrieval would still run. The new `invalid_timeline_filter_request()` checks supplied versus normalized values, explicitly rejects malformed nonscalar/nonempty inputs, and uses existing `search_error`/no-retrieval gate. Empty values intentionally selected for All remain permitted. Isolated real private-method behavioral test `tests/review1435-1474-timeline-filters.php` covers valid, invalid, array, null and combined constraints. No global search owner or Timeline backend was introduced.

3. **R1444–1445: Non-existent class in preview/public URL rendering.** Three occurrences in `templates/public-profile.php` called `SabriPublicExperiencePublic_URL::sanitize_same_site` (undefined). Replaced with the actual `\\Sabri\\PublicExperience\\Public_URL` class preserving same-site URL rules. Source and loaded-class existence regression: `tests/review1435-1474-preview-url-namespace.php`. All three call sites correspond to two review scopes.

## Review bookkeeping and test scope

Complete 40-row evidence: `config/review1435-1474-null-privacy-html-filter-preview-audit.json`. Original 25 central completion groups remain unchanged. The Composer test manifest records new tests and earlier search regressions; `tests/run-governed-suite.sh` already auto-discovers eligible PHP tests, so absence from the metadata manifest **alone** is not a missing runtime test.

Only existing File25 presentation/preference sources were changed: no File03/08/09/17/20/21 owner table writes or File24 security-policy replacement. The tests are isolated PHP/source contracts. Browser, real-role WordPress security, SQLite/MySQL, RTL, accessible forms, stress testing, actual staging and live version parity have not been executed by this review.

## Truthful acceptance status

| Required dimension | Status |
|---|---|
| Repository main HEAD | `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e` |
| Corrective source | New stacked draft PR based on unmerged #29 |
| Exact corrective HEAD CI | Must be verified on final unchanged commit |
| Active deployed plugin/package/version/checksum | Unknown |
| DB/schema version and live `wp_options` | Unknown |
| Migration state / rollback evidence | Unknown |
| File08 Booking, File17 Follow, File24 direct native acceptance | Not fully verified |
| Real Staging tests and Founder approval | Not verified |
| Live Verification Status | **Not verified** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** Do not label any live issue Resolved without exact deployed-code evidence, DB/schema/migration freeze, deployment parity and live re-test.
