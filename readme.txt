=== Sabri Unified Global Visual Experience and Design System ===
Contributors: majidhussainqadri1-dot
Tags: design-system, profiles, timeline, accessibility, responsive, public-ui, content-cards, profile-sections, visual-regression, staging-package, security-integration
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.13.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

File 25 global public design system, public profiles, native contact-consent enforcement, structured Knowledge/Media APIs, governed native sections, reviewed File 24 security integration, artifact-integrity visual acceptance, and deterministic staging release engineering for the Sabri Social Homeopathy Platform.

== Description ==

This is the same existing File 25, canonically named “Sabri Unified Global Visual Experience and Design System,” with the subtitle “Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency.” No duplicate File 26 is created for this scope.

The reviewed 0.13.0 source foundation includes:

* global semantic visual tokens that inherit File 20 without duplicating its application shell;
* reusable containers, layouts, cards, buttons, badges, notices, forms, tables, skeletons, and visual states;
* exact same-origin URL enforcement for reusable actions, cards, profile media, and OpenGraph media;
* File 03 public-contact consent for Founder, Doctor, and permitted Member contact rendering;
* immutable canonical Founder public spelling;
* privacy-safe initials instead of an external avatar-service fallback;
* conditional profile right sidebars only when real File 20 sidebar content exists;
* visible and structured profile breadcrumbs, safe OpenGraph image alternative text, and an explicit 404/410 missing-profile policy;
* governed Knowledge, Media, Reviews, Research, and Marketplace profile-section contracts;
* structured Knowledge and Media REST projections plus aggregate provider health without provider/native IDs;
* immutable provider identity and bounded read-only native adapters;
* canonical Founder, verified-Doctor, and permitted-Member profile routes;
* schema-2 route parity and idempotent rewrite migration;
* artifact-integrity visual acceptance bound to one exact commit;
* deterministic staging ZIPs, embedded/detached manifests, checksums, and independent verification;
* installed-candidate integrity verification and exact Hostinger staging preflight;
* exact File 24 `SPCRC_VERSION` detection for reviewed versions `>=0.25.3 <0.26.0`;
* a bounded `file-25-public-experience` manifest supplied through `spcrc/module_manifests`;
* no File 25 claim over security governance, privacy orchestration, incidents, audit evidence, or native content;
* profile HTML and REST responses kept `no-store` until a later versioned cache-partition contract is reviewed and accepted;
* advisory File 24 elevated monitoring when File 25 enters Safe Mode;
* PHP 8.0/8.3 and JavaScript CI.

This release is not production-complete. Exact multi-plugin Hostinger staging, visual/accessibility evidence, rollback, performance, deployment, monitoring, and Founder acceptance remain pending. Source compatibility and green automated tests do not constitute staging or production acceptance.

== Installation ==

1. Install and activate the reviewed dependency packages listed in `config/staging-dependencies.json` on a backed-up staging installation.
2. Verify the detached SHA-256 checksum before extracting the File 25 staging ZIP.
3. Upload the `sabri-public-experience` folder to `/wp-content/plugins/`.
4. Activate File 25 only on staging and confirm File 00 Founder identity and File 03 public-contact consent behavior.
5. Activate the reviewed File 24 `0.25.x` candidate and confirm the `file-25-public-experience` manifest in File 24 diagnostics.
6. Run `wp sabri file25 staging-probe --expected-commit=<sha>` and correct every fail-closed gate.
7. Review Site Health, manual workflows, accessibility, visual evidence, upgrade, rollback, and backup restoration.

== Changelog ==

= 0.13.0 =
* Reconciled implementation with the central Master Plan, the Files 22–25 numbering amendment, and the final File 25 specification.
* Corrected the verified-Doctor contact privacy bypass; every public contact now consumes File 03 consent and filters can only revoke.
* Froze the canonical Founder public spelling and removed silent external avatar-service fallback.
* Restricted profile and OpenGraph media to same-origin URLs.
* Stopped Doctor profiles from forcing an empty third shell column.
* Added visible/structured breadcrumbs, `og:image:alt`, and an explicit filtered 404/410 tombstone policy.
* Added structured Knowledge and Media REST endpoints, aggregate provider health, shared card normalization, and deterministic ETags without exposing provider/native IDs.
* Updated the staging dependency matrix to reviewed File 00 `1.1.13`, File 03 `0.2.0`, and File 25 `0.13.0` expectations.

= 0.12.0 =
* Reviewed the exact File 24 `0.25.3` source contract and corrected File 25's incorrect File 24 constant detection.
* Added the bounded File 25 module manifest through File 24's documented `spcrc/module_manifests` contract.
* Added exact `>=0.25.3 <0.26.0` compatibility gating and fail-closed production dependency reporting.
* Added advisory elevated monitoring when File 25 enters Safe Mode.
* Enforced no-store profile response headers until a versioned cache-partition contract is reviewed and accepted.
* Added Site Health, design-contract, staging-matrix, CI, and regression coverage without claiming staging or production acceptance.

= 0.11.0 =
* Fixed unreachable Research and Marketplace profile sections.
* Added schema-2 route upgrade, installed-candidate verification, and Hostinger staging preflight.

= 0.10.0 =
* Added artifact-integrity evidence and deterministic staging packaging.

= 0.9.0 =
* Added immutable provider identity, File 18 Marketplace adapter, and exact-commit visual evidence.

= 0.8.0 =
* Added native File 10/11/12 Media adapters and evidence integrity.

= 0.7.0 =
* Added immutable optional-provider metadata and visual-acceptance Definition of Done.

= 0.6.0 =
* Added governed optional profile sections and File 06 Knowledge adapter.

= 0.5.0 =
* Added same-origin URL security and reusable content cards.

= 0.4.0 =
* Added the canonical global visual-system contract and nested Safe Mode.

= 0.3.0 =
* Added native read-only File 21 timeline adapter.

= 0.2.0 =
* Added File 20 shell integration and Founder/Doctor profile foundations.

= 0.1.0 =
* Initial reviewed foundation.
