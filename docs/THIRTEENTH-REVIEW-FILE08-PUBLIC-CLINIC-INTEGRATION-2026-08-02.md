# File 25 — Thirteenth Review: File 08 Public Clinic Integration

## Governing objective

Replace the File 08 placeholder in File 25's deterministic dependency and acceptance records with the exact reviewed File 08 `0.2.1` public clinic projection contract `1.0.0`, while preserving native ownership and keeping Hostinger staging acceptance explicitly pending.

## Exact reviewed File 08 input

- Runtime: `0.2.1`
- Public clinic projection contract: `1.0.0`
- Authoritative practitioner contract: `1.0.0`
- Source commit: `bd6a10b693991fc518788ef8e3cba49531454821`
- Candidate SHA-256: `36ce0c78aa51396b02bd0705021e66782bfc45b74636ac65b0826bd372103578`
- Source CI: `Corrective Quality #64 / 30744250320`
- File 08 Hostinger staging: `pending`

Required owner symbols:

```php
SWC_VERSION
SWC_PUBLIC_CLINIC_CONTRACT_VERSION
swc_get_public_clinic_projection()
swc_public_clinic_projection_contract()
```

## Ownership law retained

File 08 owns clinic, availability, appointment, authoritative practitioner, and public clinic DTO truth. File 25 owns only bounded public presentation and does not read File 08 tables, appointment records, patient information, private notes, contacts, or native identifiers.

The File 25 adapter accepts only:

- `name`
- dedicated clinic `address`
- `country`
- `city`
- `hours`
- `timezone`

Phone and WhatsApp remain governed by File 03 profile-value ownership and explicit public-contact consent.

## Review and correction round 1

The initial matrix update exposed stale acceptance assertions that still expected File 08 source `0.2.0`. These existed in:

- deterministic release-engineering tests;
- structural package verification;
- Master Plan reconciliation tests;
- the authoritative native-contract fixture;
- the staging test plan and public documentation.

Corrections:

- changed the exact reviewed File 08 package/source to `0.2.1`;
- changed the accepted range to `>=0.2.1 <0.3.0`;
- added exact source commit and candidate digest;
- added all four required owner symbols;
- changed the contract status to `implemented-and-ci-verified-pending-hostinger-staging`;
- exercised File 25 against a simulated exact File 08 `0.2.1` response and contract-introspection endpoint.

## Review and correction round 2

A fresh review found a governance risk: merely changing the package version could be mistaken for completed staging integration. The matrix and test plan therefore needed explicit separation among source verification, candidate integrity, and real Hostinger acceptance.

Corrections:

- froze File 08 source CI evidence and exact candidate SHA-256 in the File 25 matrix;
- required File 08 `staging_status` to remain `pending`;
- expanded the real staging scenario to cover public, private, eligible, ineligible, suspended, revoked, malformed, and empty clinic projections;
- required dedicated clinic-address behavior and exclusion of contacts, identifiers, appointments, and patient data;
- updated release, structural, Master Plan, README, plugin readme, and changelog evidence;
- retained File 25 runtime `0.14.0`, because the integration changes the reviewed dependency input and acceptance evidence rather than File 25's public runtime API.

## Automated boundary

The full File 25 CI suite must pass on the final exact head, including PHP `8.0`, PHP `8.3`, JavaScript, authoritative native contracts, Master Plan reconciliation, deterministic package build-twice, installed-manifest verification, and independent artifact verification.

## Acceptance boundary

This review establishes source-level and deterministic-candidate compatibility only. It does not establish:

- installation of File 08 and File 25 together on canonical Hostinger staging;
- real File 00/File 03/File 07/File 08/File 09 runtime behavior;
- browser, Urdu RTL, accessibility, responsive, visual-regression, or performance acceptance;
- upgrade, rollback, backup restoration, monitoring, or Founder acceptance;
- merge, production approval, or live deployment.
