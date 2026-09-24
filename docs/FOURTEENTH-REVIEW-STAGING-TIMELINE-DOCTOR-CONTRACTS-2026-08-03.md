# File 25 — Fourteenth Fresh Review: Installed Staging, Timeline Privacy, and Doctor Contracts

## Governing objective

Perform a new adversarial review of File 25 runtime `0.14.0` after the File 08 `0.2.1` integration. Previous green CI, deterministic packages, and review records are evidence only; they do not suppress fresh defect discovery or imply Hostinger staging, production, merge, or Founder acceptance.

## Exact reviewed starting point

- Repository: `25-sabri-public-ui-profile-timeline-visual-experience`
- Branch: `feature/25a-25b-foundation`
- Starting commit: `20db5d876fe543ac202b4067300dd1983b69b011`
- Pull request: Draft PR `#1`
- Runtime: `0.14.0`
- Schema: `2`
- File 08 input: runtime `0.2.1`, public clinic contract `1.0.0`

## Confirmed defects

### 1. Installed staging-probe schema drift — High

`config/staging-test-plan.json` used schema `2`, but the installed `Staging_Probe::verify_test_plan()` still accepted only schema `1`. Therefore the governed installed package could never pass the real `test_plan_available` preflight gate even though source CI was green.

### 2. Public timeline thumbnail abstraction leak — High

`Normalized_Timeline_Item::to_public_array()` removed several provider/native identifiers but still emitted `thumbnail_reference`. The WordPress fallback provider populated that field with a numeric attachment ID. Other providers could also provide an ungoverned cross-origin image URL.

### 3. Non-deterministic timeline date parsing — High

The timeline normalizer used permissive `DateTimeImmutable` construction. Relative values and timezone-less ambiguous values could be accepted, altering ordering, ETags, cache identity, and reproducibility according to execution date or server timezone.

### 4. File 09 decision/snapshot agreement incomplete — High

File 25 required File 00 Doctor eligibility, a File 09 decision, and the File 09 helper, but did not independently require a current absolute validity date, a valid immutable fingerprint, and a non-empty approved public professional snapshot before publishing the Doctor class.

### 5. File 09 clinic text crossed the File 08 ownership boundary — High

The File 09 professional snapshot allow-list retained `clinic`, and `profile_value('clinic_name')` could read it. File 08 is the canonical clinic owner. File 09 may verify credentials, but its snapshot must not become an alternative clinic presentation source in File 25.

## Correction round 1

### Installed staging plan

- synchronized `Staging_Probe::verify_test_plan()` to schema `2`;
- required unique, well-shaped scenario records;
- required the governed environment, integrity, File 00, File 08, File 09, File 18, privacy, responsive, RTL, accessibility, resilience, rollback, performance, and Founder-acceptance scenarios;
- required disabled registration and indexing;
- required all source/CI/staging/production acceptance flags to remain false;
- added negative tests for obsolete schema, missing scenario, duplicate scenario, and false green-CI acceptance.

### Public timeline privacy

- removed `thumbnail_reference` from public output;
- allowed only a strict same-site `thumbnail_url`;
- changed the WordPress fallback provider from attachment ID to owner-generated thumbnail URL;
- added tests proving native IDs and cross-origin thumbnails are not public.

### Deterministic dates

- accepted only strict MySQL UTC timestamps or ISO timestamps carrying `Z`/numeric offset;
- normalized accepted values to UTC `Y-m-dTH:i:sZ`;
- rejected relative, timezone-less ISO, malformed, and invalid-calendar values;
- added deterministic UTC-normalization regressions.

### Doctor decision and snapshot

- required File 00 approved, eligible, non-suspended Doctor assertions with professional verification and `can_practice`;
- required File 09 helper agreement;
- required verified/approved decision state;
- required a 64-character immutable fingerprint;
- required a valid, non-expired absolute `verified_until` date;
- required a non-empty allow-listed approved snapshot;
- retained File 03 narrowing authority and revoke-only extension behavior;
- added missing-snapshot, malformed-fingerprint, expired-date, invalid-date, rejected-decision, and helper-disagreement regressions.

## Fresh correction round 2

A separate post-correction ownership review found that the File 09 snapshot still admitted the `clinic` field and a `clinic_name` alias. This could silently reintroduce a second clinic authority despite the new File 08 contract.

Corrections:

- removed `clinic` from the File 09-to-File 25 snapshot allow-list;
- removed the `clinic_name` snapshot alias;
- added a hostile fixture containing legacy File 09 clinic text;
- proved that neither `doctor_approved_snapshot()` nor `profile_value('clinic_name')` exposes it;
- preserved File 08 as the sole clinic presentation source.

## Executable evidence added

- `tests/staging-probe.php`
- `tests/timeline-provider-metadata.php`
- `tests/authoritative-native-contracts.php`

The corrections are also protected by the existing PHP 8.0/8.3 suite, JavaScript syntax gate, deterministic build-twice package, installed-manifest verification, independent artifact verifier, direct-SQL prohibitions, privacy tests, owner-bound provider tests, and structural checks.

## Acceptance boundary

This cycle corrects source and deterministic-candidate defects. It does not establish:

- installation on canonical Hostinger staging;
- real multi-plugin behavior with exact Files 00/03/08/09/18/20/21/24;
- real public/private/suspended/revoked Doctor and clinic cases;
- Urdu RTL, browser, keyboard, screen-reader, zoom, forced-colors, responsive, visual-regression, or performance acceptance;
- upgrade, rollback, backup restoration, cache behavior, monitoring, or Founder acceptance;
- merge, production release, or live deployment.

The pull request remains Draft and the merge gate remains closed until the exact final head passes CI/package verification and all later operational gates are completed.
