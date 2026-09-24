# Eleventh Review — Central Master Plan and File 25 Final-Specification Reconciliation

**File:** 25 — Sabri Unified Global Visual Experience and Design System  
**Runtime candidate:** 0.13.0  
**Schema:** 2  
**Date:** 31 July 2026  
**Status:** Source correction and automated QA candidate; Hostinger staging, production, merge, and Founder acceptance are not implied.

## Governing sources applied

This review compared the implementation against:

1. **Sabri Social Homeopathy Platform — Comprehensive Master Plan v2.0**;
2. **Master Plan Amendment v2.1 — Files 22–25**;
3. **File 25 — Complete Public UI, Profile Timeline and Visual Experience — Final Harmonized Specification v2.0**;
4. the reviewed File 03 `0.2.0` public compatibility facade and contact-consent contract;
5. the existing File 20, File 21, File 24, native-provider, packaging, and staging contracts already present in this repository.

The binding ownership interpretation remains:

- File 00: identity, approval, age, visibility, and professional eligibility;
- File 03: profile master data, profile media, and public-contact consent;
- File 20: sole application shell, navigation, sidebars, and layout owner;
- File 21 and native modules: canonical content and action owners;
- File 24: security/privacy/compliance/resilience governance;
- File 25: public presentation, profile navigation, federated read projection, responsive refinement, accessibility, SEO presentation, and visual acceptance.

## Confirmed defects and corrections

### 1. Verified Doctors bypassed contact consent

**Defect:** File 25 automatically displayed Doctor phone/WhatsApp after verification, even when File 03's explicit `_spd_public_contact` consent was absent.

**Correction:** `Visibility_Policy::can_show_contact()` now consumes `SPD_Helpers::can_show_contact()` for every profile class. Founder status is passed only for the canonical File 00 Founder. Missing/throwing File 03 contracts fail closed. File 25 filters may revoke but cannot grant contact exposure.

### 2. Founder public identity was mutable

**Defect:** a File 25 option or public-data filter could change the approved Founder spelling.

**Correction:** `Profile_Repository::FOUNDER_DISPLAY_NAME` freezes the exact approved public name. The historical option remains only for backward compatibility and is not authoritative at runtime.

### 3. External avatar and tracking exposure

**Defect:** WordPress avatar fallback could call an external avatar service; filtered avatar/cover URLs could be cross-origin and later enter profile HTML or OpenGraph metadata.

**Correction:** silent external avatar fallback was removed. Profile, cover, and OpenGraph media now pass the strict same-origin `Public_URL` policy. Missing media uses local initials.

### 4. Doctor profiles forced an empty right sidebar

**Defect:** every Doctor profile requested File 20's three-column layout although File 25 supplied no right-sidebar content.

**Correction:** profile pages default to two columns. Three-column mode is available only after an integration explicitly confirms real right-sidebar content. File 20 remains sole shell/sidebar owner.

### 5. Incomplete profile SEO presentation

**Defect:** canonical and ProfilePage metadata existed, but visible breadcrumbs, `BreadcrumbList`, and `og:image:alt` were absent.

**Correction:** visible accessible breadcrumbs, structured breadcrumbs, and safe OpenGraph image alternative text were added. All breadcrumb/media URLs remain same-origin.

### 6. Deleted/unavailable profile status was undefined

**Defect:** all missing profiles implicitly became 404, with no governed tombstone path.

**Correction:** default remains 404. An authoritative integration may explicitly return 410 through `sabri_public_experience/missing_profile_status`; all other values fail back to 404. Both remain no-store/noindex paths.

### 7. Planned public REST surface was incomplete

**Defect:** the final File 25 specification requires profile Knowledge, Media, and provider-health routes, but only profile and timeline endpoints existed.

**Correction:** added Founder/profile Knowledge and Media routes plus aggregate provider health. HTML and REST use the same allow-listed public card normalizer. Public output excludes provider IDs, versions, native IDs, projection keys, exception details, private metadata, and rendered HTML. REST responses remain no-store/private and now carry deterministic ETags.

### 8. Staging authority versions were stale

**Defect:** the dependency matrix still named File 00 `1.0.1`, File 03 `0.1.0`, and File 25 `0.12.0` despite reviewed corrective sources.

**Correction:** the candidate matrix now records File 00 `1.1.13`, File 03 `0.2.0` with the exact public-contact helper, and File 25 `0.13.0`. These remain pending staging, not accepted.

## Contracts changed

- File 25 runtime: `0.12.0` → `0.13.0`
- Design System contract: `1.7.0` → `1.8.0`
- Content Card contract: `1.1.0` → `1.2.0`
- Structured profile-section public contract: `1.0.0`
- Database schema: unchanged at `2`

No native object, post, profile, contact-consent, security, moderation, transaction, or patient-data ownership was moved into File 25.

## Added regression coverage

- master-plan/File 25 reconciliation markers;
- File 03 contact-consent ownership and no Doctor bypass;
- immutable Founder spelling;
- no external avatar fallback and same-origin media;
- conditional profile sidebar layout;
- visible and structured breadcrumbs;
- 404/410 policy;
- structured Knowledge/Media/provider-health REST routes;
- shared public card normalization;
- provider/native identifier non-disclosure;
- updated exact dependency-matrix versions.

## Remaining acceptance gates

This correction does not establish operational completion. The following remain mandatory:

- exact File 00/03/06/10/11/12/18/20/21/24/25 installation on canonical Hostinger staging;
- real Founder, Doctor, Member, Patient, Student, minor, private, suspended, and rejected profile tests;
- File 03 consent-on/consent-off contact tests;
- real local/external/tampered media tests;
- Knowledge/Media/provider-health REST inspection;
- canonical redirects, 404, and explicit 410 tests;
- Urdu RTL, keyboard, screen-reader, 200%/400% zoom, forced-colors, reduced-motion, mobile/tablet/desktop evidence;
- performance, logs, fresh install, upgrade, rollback, and backup restoration;
- exact-commit visual evidence and Founder acceptance;
- PR review, merge, release, controlled live deployment, and post-deployment monitoring.

## Truthful status

`0.13.0` is a corrected source candidate. Green CI or a deterministic ZIP will not by itself make it staging-accepted, production-complete, merged, deployed, or Founder-approved.
