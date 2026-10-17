# File 25 — Reviews 1475–1514
## Evidence boundaries
Source audit against exact parent eed35e80ca93ff6ef86982e8ef88b95b870ff02b; main 347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e. Forty distinct static checks were completed **before** the correction. See config/review1475-1514-audit-ledger.json for all paths and line witnesses.
## Result
39 source witnesses; one source-level defect scope; one independent root cause; one corrected source file. A source witness proves presence of the cited source construct, **not** live behavior, complete contractual compliance or runtime acceptance.
## Defect (R1500)
In templates/partials/profile-hero.php:53, a generic `<div class="spux-actions" aria-label="...">` has an accessible name without a nameable role. WAI-ARIA prohibits naming the implicit generic role. The labelled group of profile actions may not be announced as intended by assistive technologies.
## Root cause and correction
Missing semantic group role. Preserve markup, action ownership, translated label and keyboard behavior; add `role="group"` to the existing div. Add tests/review1475-1514-action-group-aria.php as an automated static regression (discovered by tests/run-governed-suite.sh).
## Governing comparison
Repository docs/GOVERNING-SCOPE.md and config/central-2026-file25-requirements.json establish File25 visual/accessibility ownership and File20 shell, File03 profile/contact, File08 clinic, File09 doctor decision, File21 feed and File24 security boundaries. Available Library excerpts include the central platform master plan and File03/08/21. The original complete File25 plan, Three Central Consolidated Governing Master Plan and all seven original File03/08/09/17/20/21/24 contracts were **not fully inspected**; clause-by-clause conformity remains open.
## Historical lineage
Parent 1435–1474 at eed35e80ca93ff6ef86982e8ef88b95b870ff02b; earlier 1395–1434 draft PR #29 at cd4a146e17fa83aec340efa5257af91dc77b5222. Preserve stacked parent branch and do not merge into main.
## Required exact-head gates
PHP 8.0, PHP 8.3, JavaScript and deterministic packaging CI on the final commit; record actual outcomes only. Hostinger plugin/build, DB/schema, wp_options/migrations, logs, staging parity and live parity have not been verified.
**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
