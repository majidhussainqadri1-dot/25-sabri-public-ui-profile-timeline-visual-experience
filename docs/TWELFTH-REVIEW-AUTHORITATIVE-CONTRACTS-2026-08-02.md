# Twelfth Review — Authoritative Contracts and Foreign-Table Decoupling

**File:** 25 — Sabri Unified Global Visual Experience and Design System  
**Candidate:** 0.14.0  
**Schema:** 2  
**Date:** 2 August 2026  
**Status:** corrective source and automated-QA candidate; Hostinger staging, merge, production, live deployment, and Founder acceptance are not implied.

## Governing sources

This correction applies:

1. Sabri Social Homeopathy Platform Definitive Master Plan 2026 v3.0;
2. File 20 Harmonized Master Plan 2026 v4.1;
3. File 00 four-round reviewed Master Plan and runtime 1.2.4 / contract 1.1.2;
4. File 25 final harmonized public UI/profile/timeline specification;
5. reviewed File 03 0.2.0, File 08 0.2.0, File 09 1.1.0, File 18 1.2.0-RC1, File 20 1.2.0, and File 24 0.25.3 source boundaries.

## Defects confirmed

### Stale File 00 contract

File 25 still accepted an older File 00 source range and used compatibility paths that could read foreign data directly. The current canonical File 00 release is 1.2.4 with public assertion contract 1.1.2.

### Local Doctor verification derivation

Doctor status could be inferred from locally available professional fields, role-like data, or queried license state. This competed with File 09's verification workflow and File 00's final eligibility assertion.

### Foreign File 00 SQL

File 25 read `smc_professional_credentials` and `smc_clinics`. These are non-owned tables and create schema coupling, privacy risk, and duplicate truth paths.

### Foreign File 18 SQL

The Marketplace profile provider queried File 18's seller and product tables directly. File 25 thereby duplicated listing eligibility and owner-query behavior.

### Local age interpretation

File 25 locally interpreted age-related fields. The governing plans require File 00 to own age/guardian eligibility and File 25 to consume versioned assertions only.

### Stale dependency and release evidence

The dependency matrix, tests, WordPress stable tag, README, changelog, structural verification, Composer suite, and CI still encoded older versions and ownership assumptions.

## Corrections implemented

### File 00

- Require runtime `>=1.2.4 <1.3.0`.
- Require exact public contract `1.1.2`.
- Consume `SMC_Contracts::assertions()` and validate contract version and user binding.
- Consume Founder identity only through `smc_founder_user_id()` and `smc_is_founder()`.
- Remove every File 25 File 00 table read.
- Keep extensions monotonic: filters may revoke an authoritative allowance but cannot grant a denied identity, Doctor state, visibility, or contact state.

### File 09

- Require reviewed source range `>=1.1.0 <1.2.0`.
- Consume `gdo_get_verification_decision()`, `gdo_get_approved_snapshot()`, and `gdo_user_is_verified()`.
- Require File 00 membership type `doctor`, approval, eligibility, professional verification, and `can_practice` together with a current File 09 verified/approved decision.
- Project only allow-listed professional fields from the immutable approved snapshot.
- Exclude license numbers and private evidence from File 25 presentation.

### File 08

- Define public clinic projection contract `1.0.0`.
- Consume only `swc_get_public_clinic_projection()` or `SWC_Helpers::public_clinic_projection()`.
- Allow-list clinic name, address, country, city, hours, and timezone.
- Keep clinic projection unavailable until File 08 supplies the required contract.
- Do not project clinic phone/WhatsApp through this path; File 03 contact consent remains authoritative.

### File 18

- Remove `$wpdb`, `SMP_DB::table()`, and native SQL from File 25's Marketplace provider.
- Prefer future `smp_get_public_profile_listings()` contract `1.0.0`.
- Provide a bounded transitional read path for reviewed File 18 `1.2.0-RC1` using owner APIs `SMP_Utils::current_seller()`, `SMP_REST::products()`, and `SMP_Activator::marketplace_url()`.
- Limit the transitional owner query to the first 50 public DTOs and File 25 output to 24 cards.
- Preserve File 18 ownership of seller/listing eligibility, moderation, contact, offers, metrics, transactions, and direct-deal history.

### Age and guardian policy

File 25 performs no age calculation. It consumes explicit File 00 minor/guardian assertions when available. For ordinary profiles, an unknown minor/contact state fails closed.

## Regression coverage

- authoritative File 00 contract/version/user binding;
- denied Doctor state cannot be granted by a File 25 filter;
- File 09 approved-snapshot professional projection;
- File 08 public-clinic allow list and contact exclusion;
- no File 00/08/09 foreign table reads;
- no File 18 `$wpdb`, `SMP_DB::table`, or `SELECT p.*` coupling;
- bounded File 18 transitional owner API;
- Master Plan v3.0 and File 20 v4.1 matrix reconciliation;
- release identity 0.14.0 across plugin header, constant, WordPress stable tag, matrix, and package;
- deterministic builder, installed-manifest verification, and independent artifact verification.

## Remaining mandatory gates

- File 08 implementation and review of public clinic projection contract 1.0.0;
- exact reviewed dependency installation on canonical Hostinger staging;
- real File 00 assertion, File 03 consent, File 09 expiry/revocation, and File 18 public DTO workflows;
- Founder, Doctor, Member, Patient, Student, minor, suspended, rejected, private, and wrong-author cases;
- Urdu RTL, keyboard, screen reader, zoom, forced colors, reduced motion, target viewports, and visual regression;
- fresh install, upgrade, rollback, backup restoration, performance, logs, monitoring, and Founder acceptance;
- PR review, merge, release, controlled live deployment, and post-deployment observation.

## Truthful conclusion

Version 0.14.0 is an authoritative-contract correction candidate. It closes the known source-level ownership defects described above. It is not a claim that external owner contracts, Hostinger staging, operational acceptance, production release, or live deployment are complete.
