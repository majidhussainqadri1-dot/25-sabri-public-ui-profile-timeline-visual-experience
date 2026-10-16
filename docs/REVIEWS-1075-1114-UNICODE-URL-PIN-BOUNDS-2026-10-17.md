# File25 Reviews 1075–1114 — Unicode URL path identity
Date: 2026-10-17. Candidate is stacked on unmerged PR #28 at `2c0c022d225b7c638574d7395aa566e2584d1fa4`; GitHub main at review freeze: `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`.

## Audit method and evidence
Forty separately enumerated source checks R1075–R1114 were inspected before code correction: **37 existing source witnesses and 3 deficient scopes from one root cause**. These are not forty full WordPress/staging/live tests. Per-check ledger: `config/review1075-1114-url-format-pin-bounds-audit.json`.

Authority consulted: `config/central-2026-file25-requirements.json`, `docs/GOVERNING-SCOPE.md`, File25 prior review lineage and public URL/Timeline source contracts. Original complete File25 and Three Central governing DOCX were not directly reread in full during this run, so full DOCX parity is not independently verified. Native File03/08/09/17/20/21/24 ownership remains unchanged.

## Confirmed root cause and correction
**R1075–R1077: Unicode bidi/format-control path ambiguity.** `Public_URL::path_is_safe` rejected ASCII controls, dot-only segments and encoded separators, but omitted Unicode format-control checks for the raw path and each percent-decoding layer. A raw, once-encoded or recursively encoded U+202E path could be accepted as a public route identity. Correction invokes `has_format_controls` before and during bounded decoding; legitimate Urdu paths remain allowed.

## Independent critical correction from exact-head CI
The first candidate also proposed rejecting out-of-range timeline pin weights. Its PHP 8.0 CI failed in `tests/run.php`, which explicitly documents the established contract: integer pin weights are clamped to [0,1000], and missing audit provenance strips ordering authority. **The proposed behavior change was withdrawn**, and R1101/R1102 are recorded as existing bounded-clamping witnesses, not defects. The regression test now asserts historical clamping and rejects noninteger weights. This prevents a speculative contract change from being mislabeled as a correction.

## Regression and release gates
`tests/review1075-1114-url-format-pin-bounds.php` exercises actual URL sanitizer and Timeline DTO, including raw/encoded Unicode controls, valid Urdu paths, existing pin bounds, exact audited maximum and invalid pin audit surface. Composer manifest includes the regression. Verify exact final commit's PHP8.0/PHP8.3, JS syntax, deterministic packaging and CI separately; prior HEAD results do not transfer.

No main merge or staging/live deployment. Exact active WordPress plugin/build/checksum, DB/schema, `wp_options` migration, logs/parity, File08 Booking/File17 Follow owner acceptance, real-role/RTL/visual QA, rollback rehearsal, Founder signoff and live retest are not verified. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
