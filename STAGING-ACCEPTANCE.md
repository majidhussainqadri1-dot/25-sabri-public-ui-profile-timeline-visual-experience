# File 25 Staging Acceptance

## Exact-artifact rule

Staging acceptance applies only to the exact File 25 package and exact companion package set recorded in the candidate manifest. Repository source, historical CI, a ZIP, or a green unit suite is not staging acceptance.

## Mandatory acceptance areas

The staging run must verify the current File 00 membership/identity contract, File 03 canonical profile contract and route parity, File 07 directory destinations, File 08 clinic projection, File 09 verification state, File 14 conversion placement, File 20 shell ownership, File 21 ProfileTimeline, File 22 create/edit destination, File 23 private publishing dashboard destination, File 24 assurance contract, and optional File 06/10/11/12/18 public providers.

It must also verify Founder/Doctor/Member/minor/private/suspended states; anonymous public reading; action-time authorization; no private or clinical leakage; Safe Mode; migration/rollback; browser/device widths; Urdu/Arabic RTL and English LTR; keyboard/screen reader/zoom/reduced motion; performance/degraded-network behavior; backup/restore; and deterministic artifact checksums.

## V12.1 File25 source candidate acceptance cases (not yet executed)

- **Timeline status-truth:** Test invalid search, disabled search, malformed filters, ordinary empty, filtered empty, later-page empty, all-native-provider outage, partial outage with approved results, 500-item bounded truncation and retry/reset links. No case may infer that a hidden/private contribution exists. Verify both HTML and REST and ensure no unauthorized native query is broadened.
- **Welcome cross-owner contract:** Test exact File20 WelcomeFrequency owner implementation together with File25 renderer under guest/member, keyboard/Escape/Skip/Close, one polite heading announcement, reduced motion, 30-day guest cookie/member persistence, storage disabled, Safe Mode, request failures and focus restoration. File20's required announcement target and exact registered native callback must be present. Confirm neither File20 nor File25 creates a second shell or frequency record.
- **Honest freshness (V12-026):** Verify stale owner dates show their true source date, a supplier note never replaces the displayed date, future timestamps do not masquerade as completed updates, and screen readers speak date/uncertainty. Test real browser/locales and clock-skew behavior; do not extrapolate from source tests.
- **Owner-truth injection (reconciled PR16):** Try a malicious third-party WordPress visual filter supplying a fabricated translated medical biography, forged institutional relationship/citation, arbitrary Profile verification/issuer/date and fake "updated live" marker. All unsupported claims must be absent. File03/09 owner-authorized public verification still shows, and native Timeline publication/update facts remain truthful. Do not enable optional features without a versioned native-source contract and role/privacy/translation review.
- **Publication/update/provenance (new PR46):** Test publication-only, genuine update, same-instant update, invalid/future update, duplicate canonical URLs across owner providers (must not transfer badges/dates), explicit native verified/unverified flags, English/Urdu date display, UTC-day consistency and no duplicate server-rendered publication time. A green Node helper test is not a real browser or assistive technology witness.
- **Evidence time integrity (PR #47):** A source-only Visual Acceptance manifest must reject future-dated screenshot, Staging and Founder-signoff records **and** any Founder sign-off recorded before required screenshots, viewports, visual/interaction modes, or the staging environment (bounded 300-second clock skew only). Independently open/hash evidence bytes and verify actual reviewer approval; metadata validator acceptance alone is insufficient.
- **Timeline sub-second chronology (PR #47):** Exercise File21-authorized publications/updates within the same second, including reversed microsecond instants and UTC offset variants. Updates must never predate published instants. Confirm retained public UTC-seconds wire format and honest Published/Updated UI, including archived/retracted items when owner-authorized.
- **Visual evidence:** Require actual screenshots and keyboard/screen-reader checks for filtered-empty, permission-denied and offline across representative 320px–1920px, Urdu RTL / English LTR, 200%/400% zoom and forced-colors/reduced-motion states. Evidence references must be linked to the exact installed artifact SHA; a manifest validator alone is not screenshot verification.

## Post-audit PR49 new mandatory, still-unexecuted Staging scenarios

- **File03 member index privacy:** A third-party plugin returning boolean `true` for `index_nonprofessional_profile` must not cause a member/patient/teacher or minor to become indexable. Public Doctor/Founder ordinary pages should retain legitimate SEO subject to all other privacy gates. Check robots headers + HTML meta + real search indexing policy rather than relying only on PHP stub.
- **Public metrics owner authority:** A generic `public_metrics` hook returning high plausible counts must not cause a public profile card or REST payload to publish those numbers. Current dashboard must explicitly say that enabling this setting does not produce approved counters. Test a future typed owner version only after File21 and privacy acceptance.
- **Provider recovery and revocation:** Invalid late card, query exception, ineligible, unavailable, recoverable and availability flips (including positive→negative→positive) must not reuse a stale cached card or publish phantom truncation; test same-instance and real WordPress callback/permission changes.
- **500-item Timeline transaction:** 499 valid + invalid final row must generate a provider error but no 500-item truncation claim. A 500-valid source must honestly show the boundary; healthy other sources must remain readable.
- **File09 withheld credential:** Test verified Doctor whose File03 approved professional DTO deliberately omits qualifications/council/specialty while File09 still has a private approval snapshot. No File09-only fields may render in public metadata, profile, REST or screenshots.
- **REST full-representation cache:** A public biography changes without an updated nested date; If-Modified-Since must not trigger 304 or suppress new content. Matching full-response ETag may use 304 when valid; Last-Modified alone is informational.
- **Current index CAS:** Inject initial failed SQL UPDATE; do not build or announce success. Then confirm actual MySQL lock reclaim, competing generations, cache invalidation and rollback on Staging; a fake-wpdb fixture alone is insufficient.

## Current truth

All external acceptance flags remain **pending**. This document is a checklist/contract, not evidence that Hostinger staging, production, live deployment or operations have passed.
