# File 25 — Reviews 635–654 Plan/Code/Cross-File Completeness Audit — 2026-10-06

## Evidence boundary

This document records repository-source review evidence only. The fresh audit began from main SHA `210b4b250fd0f996b1fb6b9eb5471dec831f6dfa`, whose exact main GitHub Actions run `1601` completed successfully. The corrections made after that baseline form a new candidate. Exact current-head CI is external mutable evidence and is intentionally not self-certified inside this source record.

Nothing here establishes Hostinger staging acceptance, Founder acceptance, production/live deployment, exact deployed-package parity, live database/schema version, live migration state, rollback rehearsal, or operational monitoring.

## Governing basis

The audit re-read the File 25 Final Harmonized Specification 2.0, the central governing plan and current companion repository source. Canonical ownership remains unchanged:

- File 03 owns profile truth and public/audience projection.
- File 08 owns clinic/appointment truth.
- File 17 owns relationship/messaging truth.
- File 20 owns structural shell/navigation/layout.
- File 21 owns publication/ProfileTimeline truth.
- File 22 owns authoring/Composer.
- File 23 owns private publishing operations.
- File 24 owns cross-cutting assurance governance.
- File 26 owns global search/discovery/ranking.
- File 25 owns public profile/timeline/component visual presentation and visual acceptance.

## Twenty review scopes

| Review | Scope | Result |
|---:|---|---|
| 635 | Repository baseline and exact-CI truth | Clean |
| 636 | File 25 final plan / central ownership | Clean |
| 637 | File 20 shell/design boundary | Clean |
| 638 | Profile Actions / View as Public | Defect corrected |
| 639 | Completion Assistant / preview access | Defect corrected |
| 640 | Timeline page size / browser Back | Defect corrected |
| 641 | File 03 public projection/privacy/media | Defect corrected |
| 642 | File 08 clinic/appointment boundary | Clean |
| 643 | File 17 Follow/Message boundary | Clean |
| 644 | File 21 publication/ProfileTimeline | Clean |
| 645 | File 22 Composer | Clean |
| 646 | File 23 Publishing Dashboard | Clean |
| 647 | File 24 security/privacy assurance | Clean |
| 648 | File 26 search/ranking boundary | Clean |
| 649 | REST / Component API | Clean |
| 650 | Observability / System Check | Clean |
| 651 | Safe Mode / Repair / Migration / Rollback | Clean |
| 652 | profile_template admin setting | Defect corrected |
| 653 | Release/status/documentation truth | Defect corrected |
| 654 | Final cross-file parity / external truth | Clean |

Defect rounds: **638, 639, 640, 641, 652, 653**.

## Review 638 — View as Public was nominal rather than complete

The plan requires owner preview modes for public, member, mobile, desktop, contact visibility, search-engine and social share-card presentation while explicitly forbidding authorization widening.

Before this audit, File 25 recognized the preview query values but rendered only a generic “Preview mode” notice. More importantly, the logged-in owner still saw owner controls such as Edit Profile, Manage Privacy, Composer and Publishing Dashboard while supposedly viewing the public projection.

Correction:

- all seven governed preview destinations are generated from the canonical same-site profile URL;
- preview receives explicit noindex/noarchive handling already present in the profile pipeline;
- owner/viewer-mutating profile actions are suppressed while preview is active;
- the Completion Assistant itself is suppressed inside preview;
- mobile/desktop projection frames now have observable responsive presentation;
- contact preview shows only already-authorized public contact projection;
- search preview shows bounded public snippet data;
- social preview shows bounded public share-card data;
- preview never grants data or authorization unavailable in the underlying public projection.

## Review 639 — Completion Assistant exposed names, not usable preview workflows

The plan requires the owner-only completion assistant to expose public/mobile/search preview access. The source returned only a list of mode names and the template did not render destinations.

Correction:

- Completion Assistant now returns same-site preview URLs for all governed modes;
- the owner can open those destinations directly;
- native edit remains delegated through the canonical owner route;
- preview state never edits File 00/File 03 truth.

## Review 640 — timeline page-size control and browser-Back restoration were missing

The plan explicitly permits timeline page size as an audited File 25 setting and requires Browser Back scroll restoration in staging acceptance.

Before correction:

- profile timeline page size was hard-coded to `20`;
- no explicit browser-Back scroll restoration existed.

Correction:

- added `timeline_page_size`, integer bounded `5..50`, default `20`;
- the profile renderer passes the governed value to the timeline service;
- filter/search/page state remains URL-addressable;
- public JavaScript stores scroll position only in same-tab `sessionStorage` and restores it only for browser `back_forward` navigation;
- storage/privacy failure degrades safely without breaking the public page.

## Review 641 — current File 03 media DTO shape had drifted from File 25's consumer

Current File 03 main `636e3ef965423887f810718abec3cd1c11c3659d` remains contract `1.4.0`, but its public media projection publishes bounded same-origin `url`, `alt`, `focal_x` and `focal_y` fields. It does not require or publish a private `attachment_id` in that public DTO.

File 25 still required `attachment_id` before accepting avatar/cover media. Therefore a valid current File 03 public profile could lose its avatar/cover in File 25 and the Completion Assistant could falsely report missing image/alt data.

Correction:

- added a bounded current File 03 public-media adapter that accepts only same-origin URL, canonical alt text and bounded focal coordinates;
- kept the historical attachment-ID helper only as compatibility metadata, not as a prerequisite for current media;
- profile rendering now consumes the canonical owner-approved URL directly;
- canonical avatar alt metadata is preserved and the template uses a descriptive fallback only when the owner supplies no alt;
- no File 03 table or private media identifier is required.

## Review 652 — profile_template was a dead governed setting

`profile_template` already existed with `standard`, `compact` and `institutional` values, but the public profile did not consume it.

Correction:

- the normalized preference is projected into a bounded `data-spux-template` state;
- compact and institutional variants now have observable token-based CSS behavior;
- no arbitrary CSS/JavaScript, second shell or layout ownership transfer was introduced.

## Review 653 — stale release/status documentation

The prior README still described Reviews 615–634 as a candidate with pending exact-head CI even though those corrections had already merged to main and exact main run `1601` had passed. The WordPress `readme.txt` also retained obsolete historical installation pins, including an old File 08 runtime.

Correction:

- README now identifies baseline main `210b4b...` and run `1601` as historical audited evidence;
- current-head CI is described as external mutable evidence that must be queried after the final commit;
- WordPress readme no longer treats historical companion versions as installed truth;
- installation requires exact dependency/package/checksum verification from the current dependency matrix and generated staging manifest.

## Cross-file limitations deliberately not bypassed

Two desired public actions still depend on owner contracts not published by their canonical owner sources:

1. File 08 does not publish an authoritative profile-user → booking-destination contract consumable by File 25, so Appointment remains hidden.
2. File 17 does not publish an authoritative target-bound Follow destination contract consumable by File 25, so Follow remains hidden. Message appears only if File 03's current public projection contains a concrete same-site owner-approved message destination.

These are recorded dependencies, not justification for File 25 to query foreign storage or invent routes.

## Result

All File 25-owned source defects discovered in Reviews 635–654 were corrected. External release gates remain unclaimed until actual staging/live evidence exists.
