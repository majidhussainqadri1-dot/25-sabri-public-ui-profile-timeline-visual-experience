# File 25 — Reviews 275–354 — Second Eighty-Round Corrective Record

Date: 2026-08-10

This is a fresh second 80-round source review of File 25 against the current companion repository contracts and the governing central/File 25 plans. Each defect-bearing review was corrected immediately before continuing. Repository/source evidence is not Hostinger staging, production/live or operational evidence.

## Defect-bearing reviews

**Reviews 275–316 and Review 354** found defects. **Reviews 317–353** found no new source-owned defect.

### Reviews 275–284 — current File 03/File 09 authority reconciliation

- 275: stale File 03 accepted source range → corrected to `>=1.2.0-rc2 <1.3.0`.
- 276: removed File 03 helper API family → migrated to `spd_get_public_profile()` + `spd_get_profile_contract_manifest()`.
- 277: File 03 contract 1.4.0 not required end-to-end → made explicit.
- 278: stale File 09 accepted range → corrected to current `1.3.0` candidate family.
- 279: current File 09 consumer contract not authoritative → bound to integration contract `1.1.0` and `gdo_file03_doctor_eligibility()`.
- 280: Doctor verification lacked current File 03 public-projection cross-check → now requires File 00 + File 09 + File 03 public Doctor truth.
- 281: contact visibility used removed File 03 helper → consumes File 03 public contact DTO; File 25 hooks revoke only.
- 282: legacy `_spd_profile_photo_id` / `_spd_cover_photo_id` remained a media source → removed as authority.
- 283: obsolete media purpose `profile` remained → current File 03 `avatar` / `cover` purposes adopted.
- 284: ProfileRepository was only partially bound to the current File 03 DTO → identity, media and public-contact projections reconciled.

### Reviews 285–299 — companion visual/dependency/staging contracts

- 285: companion modules consumed generic visual aliases not published from canonical File 25 tokens → one-way `companion-token-bridge.css` added.
- 286: stale File 03 staging dependency evidence → exact current source/DB/contract/head recorded.
- 287: stale File 09 staging dependency evidence → current 1.3.0/1.1.0 feature-candidate truth recorded without merge/live claim.
- 288: stale File 22 baseline → current `1.0.0-rc.3`, REST 1.2.0 and exact head recorded.
- 289: File 07 missing from cross-module visual acceptance → exact 1.2.0/contract1.2.0 baseline added.
- 290: File 14 missing from visual-consumer acceptance → exact 1.4.1 and `#087A4E` gate added.
- 291: File 23 stable main and FPI24 branch truth conflated/omitted → recorded separately.
- 292: activation/acceptance matrix omitted File 07/File 14 → expanded integrated staging plan.
- 293: no current File 03 public DTO staging lens → added.
- 294: no current File 09 decision staging lens → added.
- 295: File 22 staging lens referenced older candidate → corrected.
- 296: File 03 UUID/public-ID route family versus File 25 slug family was not gated → added mandatory staging canonical/redirect/SEO parity gate.
- 297: token bridge lacked staging visual QA → added.
- 298: DependencyManager did not expose current File 03 requirements → corrected.
- 299: DependencyManager did not expose current File 09 requirements → corrected.

### Reviews 300–316 — executable regression/release hardening

- 300: authoritative native-contract test fixture modeled stale File 03/File 09 contracts → rebuilt around current APIs and negative cases.
- 301: File 25 `/profile/{slug}` rewrite could shadow File 03 UUID route → UUID-shaped first segments reserved for File 03.
- 302: master-plan reconciliation regression encoded stale dependency schema/contracts → updated to schema 3/current heads.
- 303: independent artifact verifier encoded stale dependency schema/contracts → updated.
- 304: artifact verification exposed missing explicit File 07 `required_contract_version` → added 1.2.0.
- 305: legacy native bootstrap fixture remained stale → migrated.
- 306: File 09 state/fingerprint values were normalized before trust evaluation → exact raw canonical representation now required.
- 307: regression evidence literal accidentally interpolated `$user_id` → fixed literal-safe assertion.
- 308: release-engineering regression encoded schema 2/old companion truth → reconciled to schema 3/current contracts.
- 309: three-plan contact regression still used obsolete File 03 contact semantics → rebuilt for current DTO/revoke-only behavior.
- 310: central-plan regression encoded older File 22 baseline → current File 22/File 23 truth recorded.
- 311: owner-contract regression encoded stale File 03 helpers → updated.
- 312: profile-media regression encoded legacy purpose/usermeta → updated to avatar/cover public DTO authority.
- 313: contact-monotonicity regression encoded legacy founder/profile-value contact source → updated.
- 314: global structural verifier encoded stale File 03/File 09/schema2 assumptions → rebuilt for current schema 3/current companions.
- 315: exact CI exposed an actual runtime warning: a literal null/control byte existed in the REST `If-None-Match` regex → replaced with source-safe `/[\x00-\x1F\x7F]/` validation; governed suite passed afterward.
- 316: CI still warned that `actions/checkout@v4` targeted deprecated Node 20 → all checkout steps upgraded to `actions/checkout@v5`.

### Review 354 — final evidence-gate correction

The newly added second-cycle regression gate searched for rendered job-label strings `PHP 8.0` / `PHP 8.3`, although the workflow source represents these as the executable YAML matrix `php: ["8.0", "8.3"]`. This produced a false final-gate failure. The QA assertion itself was corrected; the production PHP matrix was already correct.

## Reviews 317–353 — reviewed clean

These 37 independent lenses rechecked File 00 identity/visibility authority; current File 03 DTO minimization, contact and media; File 09 decision exactness; File 08 clinic exclusions; Files 07/14 visual boundaries; File 18 owner DTO/no SQL; Files 20/21/22/23/24/26 ownership; UUID route reservation and external canonical-route parity boundary; slug exactness; Founder/Doctor/member/minor privacy; timeline provider/author/date/pagination/filter behavior; optional provider maturity; REST minimization/no-store/ETag; content-card allow-lists; FUT24 public-payload minimization; same-origin client URL handling; localization; reduced motion/forced colors/low-data scope; action/share behavior; Quality Score isolation; owner/admin tools; Safe Mode; upgrade/uninstall; deterministic package/integrity defenses; and source-versus-staging/live truth separation.

## External acceptance blocker intentionally not patched in File 25

The current File 03 source has a UUID/public-ID profile route family while the File 25 governing plan contains plan-approved public slug presentation routes. File 25 now avoids shadowing the UUID family, but one canonical indexable URL, permanent alias redirects, redirect-loop/chain absence and duplicate-SEO absence require exact multi-plugin Hostinger staging evidence. No third route family was invented and no repository-only claim is being promoted to staging/live truth.

## Machine and executable evidence

- Machine ledger: `config/review275-354-eighty-round-ledger.json`
- Executable regression: `tests/review275-354-eighty-round-current-companions.php`
- Current dependency matrix: `config/staging-dependencies.json` schema 3
- Current staging plan: `config/staging-test-plan.json` schema 3

## Truth boundary

Hostinger staging accepted: false. Founder staging acceptance: false. Production accepted: false. Live deployed: false. Operational: false. Exact deployed code verified: false.
