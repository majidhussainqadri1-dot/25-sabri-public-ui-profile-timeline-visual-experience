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
- **Publication/update/provenance (new PR46):** Test publication-only, genuine update, same-instant update, invalid/future update, duplicate canonical URLs across owner providers (must not transfer badges/dates), explicit native verified/unverified flags, English/Urdu date display, UTC-day consistency and no duplicate server-rendered publication time. A green Node helper test is not a real browser or assistive technology witness.
- **Evidence time integrity:** A source-only Visual Acceptance manifest must reject future-dated screenshot, Staging and Founder-signoff records. Independent assurance must separately open/hash real evidence bytes and verify who reviewed them; metadata validator acceptance alone is insufficient.
- **Visual evidence:** Require actual screenshots and keyboard/screen-reader checks for filtered-empty, permission-denied and offline across representative 320px–1920px, Urdu RTL / English LTR, 200%/400% zoom and forced-colors/reduced-motion states. Evidence references must be linked to the exact installed artifact SHA; a manifest validator alone is not screenshot verification.

## Current truth

All external acceptance flags remain **pending**. This document is a checklist/contract, not evidence that Hostinger staging, production, live deployment or operations have passed.
