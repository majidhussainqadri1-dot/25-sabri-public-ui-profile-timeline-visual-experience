# File25 Reviews 1075–1114 — Unicode route identity and audited pin bounds
Date: 2026-10-17. Candidate is **stacked draft only**, based on unmerged PR #28 at `2c0c022d225b7c638574d7395aa566e2584d1fa4`; GitHub main at review freeze: `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`.

## Audit methodology
Forty distinct bounded source checks, R1075–R1114, were completed **before** source correction. 35 had existing source witnesses; five deficient requirement scopes belong to two root causes. These are not forty installed WordPress or browser E2E tests. Machine-readable per-check ledger: `config/review1075-1114-url-format-pin-bounds-audit.json`.

Authority inspected: `config/central-2026-file25-requirements.json`, `docs/GOVERNING-SCOPE.md`, File25 prior audit lineage and public URL/Timeline owner contracts. The original full File25 and Three Central governing DOCX files were not directly reread in full in this run; independent complete DOCX parity is therefore **not** claimed. Native owner responsibilities remain File03 profile/media/consent, File08 booking, File09 verification, File17 Follow, File20 shell, File21 publication, File24 assurance.

## Confirmed defects and root causes
1. **R1075–R1077 — ambiguous Unicode path identity.** `Public_URL::path_is_safe` rejected dot segments, encoded separators and ASCII controls but did not check Unicode format controls in the raw or recursively percent-decoded path. Raw, once-encoded and twice-encoded U+202E could pass the URL gate. Correction adds `has_format_controls` checks at entry and every bounded decoding layer. Legitimate literal and encoded Urdu paths remain permitted.
2. **R1101–R1102 — pin weight authority silently clamped.** `Normalized_Timeline_Item::exact_bounded_integer` transformed negative or overmaximum integer weights into 0 or 1000. That concealed an invalid provider contract and could give an out-of-contract item a maximum-priority weight when audit metadata was supplied. Correction rejects out-of-range integers instead. Valid 0–1000 weights, mandatory provenance, exact surface and time window remain intact.

## Regressions and acceptance
`tests/review1075-1114-url-format-pin-bounds.php` executes the actual URL sanitizer and immutable Timeline DTO with raw/encoded Unicode controls, legitimate Urdu routes, malformed pin weights, accepted audited maximum, wrong-surface audit and missing provenance. Composer governed manifest updated; the governed shell runner auto-discovers all tests. Exact final HEAD PHP 8.0/8.3, JavaScript syntax, deterministic ZIP/manifest and GitHub Actions must be checked **after** the commit is created; do not infer their results from prior PR #28 green CI.

## Source and deployment boundaries
No File03/08/09/17/20/21/24 owner database or backend was modified. No merge to main; no staging or live deployment. File08 native booking destination, File17 Follow target, exact active WordPress plugin/build/checksum, DB/schema, `wp_options`/migration state, runtime logs, parity, Hostinger real-role/RTL/visual/accessibility QA, rollback rehearsal, Founder acceptance and live retest remain unverified. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
