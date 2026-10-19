# File 25 — Sabri Unified Global Visual Experience and Design System

**Subtitle:** Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency

WordPress module for the **Sabri Social Homeopathy Platform**. This is the existing File 25; no duplicate File 26 is created.


## Latest manually verified source audit — R1675–1714 (19 October 2026)

Forty scoped reviews against original File25/three central plans and the **verified live GitHub File24 repository**: **30 pre-existing source witnesses, nine deficient source scopes clustered in three root causes, one independent security-acceptance evidence blocker**.

- File25 index leases now use conditional WordPress MySQL SQL compare-and-swap for stale reclaim, owner release and running/final index writes (with explicit option-cache invalidation); fail closed if actual WP database atomic capability is unavailable. Deterministic fake-wpdb interleaving test added. **Real MySQL/WordPress concurrent staging behavior remains unverified.**
- File24 exact current repository HEAD `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb` reconciled in code/contract tests/staging matrix; its 99-external-manifest capacity no longer causes File25 to silently evict other module manifests.
- File24 0.99.0 optional manifest schema fields declared explicitly, while last security test date and verification level intentionally remain blank/unassessed pending real security sign-off.
- [Review report](docs/REVIEWS-1675-1714-ATOMIC-INDEX-FILE24-PARITY-2026-10-19.md) and [40-row ledger](config/review1675-1714-atomic-index-file24-parity-audit.json).
- Outstanding: connected WordPress staging site (none registered via WPVibe), production-equivalent MySQL concurrency/restore/RTL/role testing, File08/File17 target-action acceptance, File24 assessed security evidence, Founder authorization and deployed source/package parity.

## Latest manual source audit — Reviews 1635–1674 (19 October 2026)

Forty new targeted File25/central-plan/source checks: **33 source witnesses; seven deficient check scopes in two root causes**. Index Rebuild now checks generation ownership through final state persistence, refusing a superseded builder's success and preserving a newer worker's observed lock. Invalid owner `source_versions` metadata now rejects the index builder rather than silently disappearing.

- Full numbered ledger: `config/review1635-1674-index-generation-fencing-audit.json`.
- Detailed source report: `docs/REVIEWS-1635-1674-INDEX-GENERATION-FENCING-2026-10-19.md`.
- Native-method regression: `tests/review1635-1674-index-generation-fencing.php`.
- **Release blocker:** these WordPress option-based ownership checks are not atomic across concurrent workers. Only isolated deterministic takeovers tested, not real MySQL/distributed-cache CAS. Draft PRs not deployed; live, DB, File24 and Founder acceptance unverified.

## Latest manual source audit — Reviews 1595–1634 (18 October 2026)

**40 targeted plan/source checks:** 31 existing code witnesses, **nine affected checks in two independent root causes**, corrected in this unmerged stacked draft candidate. Index Rebuild cannot call the native builder until the running generation is persisted, and cannot report success or emit a rebuilt event until the final index state is persisted. Administrator Rebuild and Repair notices now reflect actual busy/failed/partial results and Repair checks its own index/journal writes.

- [Complete technical report](docs/REVIEWS-1595-1634-INDEX-REBUILD-REPAIR-PERSISTENCE-2026-10-18.md).
- 40-row ledger: `config/review1595-1634-index-rebuild-admin-status-audit.json`.
- New PHP failure-injection regressions: `tests/review1595-1634-index-persistence.php` and `tests/review1595-1634-admin-repair-status.php`.
- **Not an installed WordPress, DB or Staging proof:** exact live artifact, database/schema, migrations, File24 owner head, real native index builder and Founder acceptance unverified.

## Latest manual source audit — Reviews 1555–1594 (18 October 2026)

Forty distinct source/contract inspections of File25 Final, the consolidated governing master and native owner interfaces: **35 existing source witnesses and five defective check scopes in two actual root causes**. REST admin idempotency responses now bind a SHA-256 canonical request fingerprint and reject reused keys for different preference payloads/target profiles with 409; missing native public-projection reconciliation can no longer report success from an auto-accepted default. An isolated real-PHP-method regression covers accepted/rejected/replay/error cases.

- Audit: `config/review1555-1594-idempotency-reconciliation-audit.json`.
- Technical report: `docs/REVIEWS-1555-1594-IDEMPOTENCY-RECONCILIATION-2026-10-18.md`.
- Test: `tests/review1555-1594-idempotency-reconcile.php`.
- **Draft source candidate, not deployed:** native File24, cache persistence/concurrency, real WP roles/DB/migrations, staging and Founder acceptance remain unverified.

## Latest manual source audit — Reviews 1515–1554 (18 October 2026)

Forty new File25/central/cross-file source checks: 30 source witnesses and **10 deficient check scopes in three root causes**. Corrected partial upgrade's stale runtime version, made all three preferences/history/revision options verify persistence before success (with bounded compensation and HTTP503/admin failure if persistence fails), and rejected malformed REST/admin revision tokens. Two new isolated PHP failure-injection regressions.

- [Full R1515–1554 report](docs/REVIEWS-1515-1554-UPGRADE-PREFERENCES-REVISION-2026-10-18.md)
- JSON evidence: `config/review1515-1554-upgrade-preferences-revision-audit.json`.
- Tests: `tests/review1515-1554-upgrade-runtime-retry.php`, `tests/review1515-1554-preferences-write-integrity.php`.
- **These are not 40 installed runtime acceptance tests.** No merge/deploy or verified live DB/schema/migration state; cross-owner acceptance and Founder approval remain outstanding.

## File25 latest manual source review — R1475–1514 (18 October 2026)

Forty separate targeted File25/central/companion source checks on previous unmerged PR30: 30 source witnesses, ten deficient checks clustered into **three independent root causes**. Source-only corrections reject silent >500 legacy redirect truncation, verify actual stored migration/rollback state and admin status before claiming success, and detect failed schema/runtime option writes via Safe Mode. New isolated tests: `tests/review1475-1514-migration-persistence.php` and `tests/review1475-1514-upgrade-version-persistence.php`. Full report `docs/REVIEWS-1475-1514-MIGRATION-UPGRADE-PERSISTENCE-2026-10-18.md`; 40-row ledger `config/review1475-1514-migration-upgrade-persistence-audit.json`. **Not a deployed fix:** PR stack draft, live source, DB/schema/migrations, File24 exact contract, real staging rollback and Founder approval remain unverified.

## Latest targeted source audit — Reviews 1435–1474 (18 October 2026)

**40 separate plan/source checks:** 29 existing source witnesses and **11 deficient check scopes in three independent root causes**, corrected in this **unmerged draft branch**. Explicit null in three privacy-sensitive preferences no longer restores public defaults; six malformed HTML Timeline filter types no longer broaden native retrieval; and three nonexistent preview URL class call sites now reference the loaded canonical `Public_URL` class.

- Full source ledger: `config/review1435-1474-null-privacy-html-filter-preview-audit.json`.
- Technical report: `docs/REVIEWS-1435-1474-NULL-PRIVACY-HTML-FILTER-PREVIEW-2026-10-18.md`.
- Actual isolated tests: `tests/review1435-1474-null-privacy-preferences.php`, `tests/review1435-1474-timeline-filters.php`, `tests/review1435-1474-preview-url-namespace.php`.
- WordPress/browser/staging/live acceptance, DB/schema, deployed artifact parity, File24 exact repository source and Founder sign-off remain unverified. No GitHub source result proves a live fix.

## Focused source privacy correction — 15 October 2026 (draft PR #20)

Forty separate source-level completeness inspections were performed against File 25's governing plan, central v3.0 and the declared companion ownership boundaries. These are **40 focused checks**, not 40 full staging/live regression runs. Four related File 03 public-profile ownership defects were found: raw WordPress biography fallback, raw display-name fallback, and unconstrained filtered biography/headline substitution. The corrective branch now fails closed when no approved display name is present and permits only redaction/preservation of approved public text.

- Review ledger: `docs/REVIEWS-715-754-FORTY-SCOPE-PRIVACY-BOUNDARY-2026-10-15.md`.
- Tests: `tests/profile-data.php`; verify the exact corrective branch CI externally.
- Recorded source presence in other audit scopes is not proof of complete behavioral coverage.
- External File 08 booking, File 17 Follow and multi-source native timeline contracts remain unresolved.
- Neither staging, production, DB/schema/migration state nor exact live deployed parity is verified.


## Latest source candidate — Reviews 1035–1074 (17 October 2026)

Forty new targeted source inspections against File25 Final, the Consolidated Three Central Plans and File03/08/09/17/20/21/24 owner boundaries revealed **six deficient requirement checks across three underlying defects**: owner administration could be lowered to `read` by the capability filter; malformed native File03 visibility restriction filters could restore public status; malformed metrics aggregation thresholds could become zero and expose low-count metrics. These source deficiencies have corrective implementations and two actual PHP regressions in this stacked **unmerged draft candidate**.

- Detailed 40-row ledger: `config/review1035-1074-admin-privacy-metrics-audit.json`.
- Technical report: `docs/REVIEWS-1035-1074-ADMIN-PRIVACY-METRICS-2026-10-17.md`.
- Governed behavioral regressions: `tests/review1035-1074-owner-capability-metrics.php`, `tests/review1035-1074-native-visibility.php`.
- Source checks are **not** 40 installed WordPress or live E2E tests. Hostinger deployed package, DB/migration parity, real role QA, Founder acceptance, File08 Booking, File17 Follow and controlled live re-test remain outstanding.

## Previous source candidate — Reviews 995–1034 (16 October 2026)

Forty new targeted plan/source inspections against File25, the consolidated governing plan and native File03/08/17/20/21 ownership: 36 existing source witnesses and four deficient check scopes in two root causes. Fixed File25's WordPress core post compatibility adapter so a public approval extension must return **strict boolean `true`** rather than any truthy value. Hardened public REST read rate limiting so missing storage, invalid bucket, or failed quota persistence produces safe retryable **503**, while a valid exceeded quota still returns **429**. This does not modify native File21 publishing or File24's global abuse authority.

- Full numbered audit: `config/review995-1034-native-post-rate-guards-audit.json`.
- Technical report: `docs/REVIEWS-995-1034-NATIVE-POST-RATE-GUARDS-2026-10-16.md`.
- PHP8.0/PHP8.3 behavioral regressions: `tests/review995-1034-wordpress-post-approval.php` and `tests/review995-1034-rest-rate-storage.php`.
- **40 targeted source checks are not 40 real installed WordPress tests.** This draft is stacked on PR #26 (and all preceding draft PRs); real cache topology, concurrent quotas, File08/17 owner URLs, deployed checksums/DB/migrations, staging and Founder acceptance remain external gates.

## Earlier corrective source candidate — Reviews 955–994 (16 October 2026)

Forty targeted File25 vs central/companion source inspections revealed three deficient source scopes in two root-cause groups. The Founder Timeline REST registration offered only pagination validation while the Doctor/Member Timeline registered all seven required secondary filter schemas. Both routes now use one canonical `$timeline_args` schema. The final `Timeline_Service` previously enforced a byte-based 120-character topic limit and accepted hidden bidi/format controls even though REST and HTML now applied Unicode/codepoint safety. It now shares their bounded Unicode normalization and strict control policy.

- Full 40-row scope/owner ledger: `config/review955-994-founder-timeline-unicode-audit.json`.
- Technical report: `docs/REVIEWS-955-994-FOUNDER-TIMELINE-UNICODE-2026-10-16.md`.
- Governed executable test: `tests/review955-994-founder-timeline-rest-unicode.php`, using actual route registrations and a native-provider fixture to confirm accepted/rejected Topic filtering.
- **Do not confuse 40 targeted source checks with 40 end-to-end live test runs.** This candidate is stacked on unmerged draft PR #25 (and #24–#20); native File08 Booking, File17 Follow, role/RTL/a11y staging, exact installed version/checksum, DB/migrations, Founder approval, deploy and live retest remain pending.

## Earlier source review candidate — Reviews 915–954 (16 October 2026)

Forty **targeted source checks**, not forty full live/staging E2E runs, found 13 deficient plan/source scopes grouped into five root causes. The candidate branch corrects (1) the 14-vs-12 public-tab and section-order save truncation, (2) malformed enabled-tab/provider lists that could reopen disabled features, (3) explicit malformed public_visibility/public_metrics/profile_local_search booleans that could fail open, (4) 120-byte instead of 120-Unicode-character REST/HTML search and topic limits and (5) missing strict bidi/format-control rejection in REST topics. Two actual PHP8.0/8.3 behavior regressions cover saving, readback, approved public sections and Unicode RTL text, without asserting installed runtime parity.

- Full 40-inspection ledger: `config/review915-954-settings-unicode-audit.json`.
- Root-cause report: `docs/REVIEWS-915-954-SETTINGS-PRIVACY-UNICODE-2026-10-16.md`.
- Governed regressions: `tests/review915-954-preferences-privacy-unicode.php`, `tests/review915-954-rest-html-unicode-parity.php`.
- Source change is stacked above **unmerged draft PR #24** (and #23–#20); current `main` and live deployment are separate realities. File08 Booking, File17 Follow, Hostinger staging/migrations, Founder acceptance and exact installed checksums remain unverified.

## Previous review candidate — Reviews 875–914 (16 October 2026)

Forty new, distinct requirement-to-source inspections against File25 v2.0, the Three Central Plans v3.0 and File03/08/09/17/20/21/24 native owners identified **seven** source gaps within two root-cause groups. Corrected REST profile responses now return the same genuinely available native-provider Knowledge/Media/other sections as HTML, subject to File25 enabled-tab preferences. Six optional public Timeline REST filters (`content_type`, `provider`, `year`, `language`, `topic`, `sort`) no longer convert malformed inputs into valid empty/default values before validation. Both changes have actual REST-controller PHPUnit-independent behavioral regressions.

- Audit: `config/review875-914-rest-query-provider-parity-audit.json`.
- Human report: `docs/REVIEWS-875-914-REST-QUERY-PROVIDER-PARITY-2026-10-16.md`.
- New governed regression: `tests/review875-914-rest-query-provider-parity.php`.
- All fixes are **source-only in a stacked unmerged PR**, not evidence of staging, live DB, deployed plugin checksums or production acceptance.
- File08 Booking and File17 Follow still require owner-approved target-bound destination contracts. Additional native Timeline projections, role-based Hostinger staging, Founder acceptance and live re-test remain outstanding.

## Historical corrective candidate — Reviews 835–874 (16 October 2026)

Forty separate source-level inspections against File25 v2.0, central v3.0 and File03/08/09/17/20/21/24 native owner boundaries found **three** File25 source defects and corrected them on this stacked review branch. Unaccepted File17 Follow and File08 Appointment targets can no longer be restored through subsequent arbitrary `action_url` filters, even with same-site URLs. Invalid REST Timeline `search` input is no longer transformed into an accepted empty/all-results request before validation. A PHP 8.0/8.3 regression uses actual route callbacks and the private action projector. These are 40 focused inspections, **not** 40 Hostinger/Live test cycles.

- Detailed inspection log: `config/review835-874-native-action-rest-search-audit.json`.
- Human audit: `docs/REVIEWS-835-874-NATIVE-ACTIONS-REST-SEARCH-2026-10-16.md`.
- Regression: `tests/review835-874-native-action-rest-search.php`.
- Stacked upon **unmerged** PRs #22, #21 and #20. Check actual candidate SHA CI; do not infer live deployed state from source.
- Remaining: approved native File08 booking & File17 Follow contracts, additional File21-native Timeline bridges, Hostinger staging, Founder acceptance, DB/schema/migration checks, deployed checksums and live retest.

## Historical corrective candidate — Reviews 795–834 (15 October 2026)

The forty source inspections in `config/review795-834-seo-privacy-boundary-audit.json` identified and corrected **five** File25-owned privacy/SEO issues: File25's global robots filter improperly affected non-File25 pages; private owner-visible profiles lacked mandatory noindex; such profiles could emit social/structured metadata; an existing nofollow could be cleared; member-profile indexing accepted truthy, non-boolean plugin values. These are five source defects corrected on the unmerged branch, not live incidents proven resolved. `tests/review795-834-seo-scope-privacy.php` exercises actual PHP behavior on PHP 8.0 and 8.3. Full report: `docs/REVIEWS-795-834-SEO-PRIVACY-SCOPE-2026-10-15.md`.

The review also confirmed that RTL is implemented via logical CSS and the File25 direction class; File20 already owns the global skip link. External File08 booking/File17 Follow destination APIs, richer native Timeline, real-role staging, exact deployed checksum, live DB/schema/migration and live re-test remain pending. This candidate is **stacked on unmerged PRs #21 and #20**; a green source CI does not establish production parity.

## Earlier source corrective candidate — Reviews 755–794 (15 October 2026)

- **40 distinct source inspections**, not forty deployed end-to-end journeys. Six REST-facing policy gaps were corrected and one separate default-tab omission was found and corrected after the scoped checks.
- Public GET profiles, Timelines, Knowledge and Media now consistently honor File 25 `public_visibility` and administrator-approved enabled public tabs; disabled profile-local search cannot be bypassed through REST.
- `reviews` and `research` are available in the governed tab preference schema, without fabricating native content or provider publication approval.
- The apparent Skip Link omission was ruled out: File 20 owns and renders the global skip link and target.
- Executable regression: `tests/review755-794-rest-policy-parity.php`; audit: `config/review755-794-rest-public-policy-audit.json` and `docs/REVIEWS-755-794-REST-PUBLIC-POLICY-PARITY-2026-10-15.md`.
- Corrective branch is stacked on the still-unmerged PR #20; this and other draft branches must not be mistaken for GitHub main or the installed plugin.
- **Source QA only:** Exact latest-branch CI must be checked independently. No staging/production approval, live DB/schema/migration or deployed-code parity is asserted.

## Historical corrective candidate — Reviews 695–714

- Runtime: `0.15.0` (source package version unchanged).
- Frozen main audit baseline: `3075224089506fed19af1441ebf3556c2d5230b5`.
- Baseline exact-main CI: **PASS**, run `1784` (source/CI evidence only).
- Corrective branch: `fix/reviews-695-714-timeline-integrity-20261009`.
- Governed File 25 plan: Final Harmonized Specification `2.0` and central consolidated master plan.
- Source review result: **20 scopes; 6 corrected defect rounds, 13 clean source scopes and 1 owner-contract dependency scope**.
- Known unresolved File 25 source defects after the recorded corrections: **0**
- Known owner-contract gaps: File 08 profile booking destination, File 17 target-bound Follow destination, and richer approved non-post timeline sources beyond File 21.
- Hostinger staging accepted: **No**
- Production approved: **No**
- Live deployment verified: **No**. Exact deployed parity verified: **No**.

### Corrective implementation

- Enforced **active UTC start/end and profile-timeline surface** for audited pins. Expired, future, malformed and foreign-surface pin claims lose ordering priority without hiding approved public items.
- Repaired merged Timeline pagination: per-provider 500-item caps no longer impose a single-provider page ceiling across all providers.
- Rejects pages outside bounded aggregate capacity **before** offset multiplication to avoid integer overflow.
- Ordinary 20+1 look-ahead no longer produces a false hard-limit warning; actual 500-item provider cap still warns.
- Year, language, topic, Corrections and search refinements now retrieve the **complete bounded 500-item candidate pool** so older matches can be found.
- **Oldest** ordering now uses the full bounded pool rather than sorting a newest-only preview.
- Added executable regression coverage with real bounded mock providers, multi-provider Page 26, huge-page input, and active/future/expired/foreign pin windows.

### Evidence

```text
config/review695-714-timeline-integrity-ledger.json
docs/REVIEWS-695-714-TIMELINE-INTEGRITY-2026-10-09.md
tests/review695-714-timeline-integrity.php
```

**Evidence boundary:** CI builds and ZIP checks are not installed staging, live deployment or database truth. Exact deployed code, DB/schema, migration state, staging visual/role acceptance and live re-test all remain unverified.

## Source implementation status

File 25 source-owned scope identified in Reviews 695–714 has been corrected; later source changes require fresh exact-head CI evidence. Mutable exact-head CI state is intentionally **not frozen as permanent truth inside source metadata**: the exact current GitHub HEAD must be checked against its current CI run after the final commit. Repository/CI evidence never proves installed Hostinger staging or live deployment.

Machine-readable traceability:

```text
config/source-completion-matrix.json
config/all-chats-directive-matrix.json
config/review595-614-twenty-round-cross-file-completeness-ledger.json
config/review615-634-fresh-cross-file-audit-ledger.json
config/review635-654-plan-completeness-ledger.json
config/review655-674-admin-plan-completeness-ledger.json
config/review675-694-timeline-completeness-ledger.json
config/review695-714-timeline-integrity-ledger.json
config/staging-dependencies.json
```

Current fresh audit record:

```text
docs/REVIEWS-695-714-TIMELINE-INTEGRITY-2026-10-09.md
```

The audited baseline main `e35563b7...` passed exact-head CI run `1689`. The final current-head CI status for any later corrective commit is external evidence and must be queried from GitHub; it is not self-certified by this README. Hostinger staging, Founder acceptance, production approval, exact deployed-code parity, live database/schema state, live migration state and operational acceptance remain separate gates.

## Current ownership and compatibility boundaries

- **File 03:** profile master/public projection, contact consent, canonical public media and canonical profile identity remain File 03-owned.
- **File 08:** reviewed current main `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`, source runtime `1.2.16`; public clinic contract `1.1.0` remains within the File 25 supported family. No reviewed profile-user→booking-destination API is published, so **Appointment remains hidden**. This is source-only evidence, not staging parity.
- **File 09:** current main `cfc5f781a766330314dc98c42abeca0eb7786eba`, runtime `1.3.0`, schema `6`, integration contract `1.1.0`; Doctor-verification truth remains File 09-owned.
- **File 14:** current main `080e2198d84dfb7491bb0b75946e14a5fe118b91`, runtime `1.4.8`, schema `10005`; File 14 remains within File 25's reviewed compatibility family.
- **File 17:** current main `8ae656e51796d1f05865d8be5dca2480443d79ca`; **Follow remains hidden** without a target-bound owner contract.
- **File 20:** current main `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`; sole structural shell/navigation/layout and canonical admin-parent owner.
- **File 21:** current main `f2eb7e95ddea327af36ea725ffb923b029f885e6`; canonical publication/ProfileTimeline owner. Its current `ProfileTimeline::query` remains the authoritative approved post timeline source.
- **File 22:** current main `b7a7f2e69411cbd32f0574fd12d766fb70c01b7a`; create/edit workflow owner.
- **File 23:** current main `dcae138e6073f4d0ff596623deb05b9940b8271b`; private publishing-operations owner.
- **File 24:** current main `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`; security/privacy/compliance/resilience assurance owner.
- **File 26:** current main `bbea3aad466792a4a6a62b53532bbd45c7c592de`; global search/discovery/ranking owner.
- **File 25:** public profile/timeline/card visual presentation, profile-local timeline filtering/sorting, component visual contracts, responsive/accessibility refinement, SEO presentation and visual acceptance.

## Reviews 675–694 corrections

- Added bounded **provider, year, language and topic** secondary timeline filters.
- Added **Latest / Oldest** sorting while preserving governed pin precedence.
- Added the plan-required **Corrections** projection filter over corrected/retracted owner-authorized items.
- Search and secondary filters now preserve compatible URL state and owner preview mode while resetting pagination.
- Filtered empty results now say **No matching public contributions** instead of falsely implying the profile has no publications.
- Added an `aria-live` screen-reader page/item announcement.
- Added REST parity for provider/year/language/topic/sort/search.
- All explicit timeline refinement URLs, including `?sort=latest`, are now noindex to prevent duplicate indexable filter URLs.
- A nonzero `pin_weight` now requires bounded internal audit provenance: reference, actor, reason, surface, start and end. Invalid/unaudited pins are neutralized to weight `0` without dropping the public item.
- Refreshed File 09 R26 and File 14 v1.4.8 repository-source evidence.
- Richer planned timeline types beyond the current File 21 post timeline remain owner-contract dependent; File 25 will not infer publication approval from section-card adapters or foreign storage.

## Existing visual and public contracts retained

- File 20 remains the only structural shell owner.
- File 03 contact consent cannot be widened by File 25 filters.
- Founder spelling is immutable: `Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed`.
- Profile, cover, card, and OpenGraph media use strict same-origin validation.
- External avatar-service fallback is disabled; privacy-safe initials are used.
- Public profiles default to two columns; three columns require real owner-declared sidebar content.
- Visible/structured breadcrumbs, safe `og:image:alt`, and governed 404/410 handling remain active.
- Knowledge, Media, Reviews, Research, and Marketplace sections remain bounded, optional, read-only projections.
- Public REST and HTML share one public card allow list and do not disclose provider/native identifiers.
- Profile HTML and REST remain `no-store` until a versioned cache-partition contract is reviewed.

## Public integration API

```php
sabri_visual_experience_contract(): array
sabri_visual_experience_acceptance_contract(): array
sabri_visual_experience_render_state(array $args = []): string
sabri_visual_experience_render_notice(array $args = []): string
sabri_visual_experience_render_card(array $args = []): string
sabri_visual_experience_render_welcome(array $args = []): string
```

Public read routes include:

```text
GET /wp-json/sabri-public/v1/founder
GET /wp-json/sabri-public/v1/founder/timeline
GET /wp-json/sabri-public/v1/founder/knowledge
GET /wp-json/sabri-public/v1/founder/media
GET /wp-json/sabri-public/v1/profiles/{public-slug}
GET /wp-json/sabri-public/v1/profiles/{public-slug}/timeline
GET /wp-json/sabri-public/v1/profiles/{public-slug}/knowledge
GET /wp-json/sabri-public/v1/profiles/{public-slug}/media
GET /wp-json/sabri-public/v1/providers/health
```

## Quality commands

```bash
composer validate --strict --no-check-publish
composer test
node --check assets/js/public.js
php tools/verify-structure.php
php tools/verify-source-completion.php
```

Deterministic staging package:

```bash
php tools/build-staging-package.php \
  --output-dir=build/staging-package \
  --commit=<40-character-commit-sha> \
  --source-date-epoch=<commit-unix-timestamp>
```

Installed staging preflight:

```bash
wp sabri file25 staging-probe --expected-commit=<40-character-candidate-commit>
```

A passing source suite, package verifier, or installed preflight authorizes manual staging evaluation only. It is not merge, staging acceptance, production approval, or live-deployment evidence.

## Remaining mandatory gates

- Exact Files 00/03/06/08/09/10/11/12/18/20/21/24/25 installation on canonical Hostinger staging.
- Real File 08 current `1.2.15` public/private/eligible/ineligible/suspended/revoked/empty clinic projections and File 25 rendering behavior.
- Real Founder, Doctor, Member, Patient, Student, minor, suspended, rejected, private, and wrong-author workflows.
- File 03 consent-on/consent-off, File 09 expiry/revocation, and File 18 owner-DTO tests.
- Urdu RTL, keyboard, screen reader, 200%/400% zoom, forced colors, reduced motion, target viewports, and visual regression evidence.
- Fresh install, upgrade, rollback, backup restore, performance, logs, monitoring, and Founder acceptance.
- PR review, controlled merge, release, live deployment, and post-deployment observation.
