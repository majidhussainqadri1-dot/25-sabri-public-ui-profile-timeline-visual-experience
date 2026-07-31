=== Sabri Unified Global Visual Experience and Design System ===
Contributors: majidhussainqadri1-dot
Tags: design-system, profiles, timeline, accessibility, responsive, public-ui, content-cards, profile-sections, visual-regression, staging-package
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.11.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

File 25 global public design system, reusable components, public profiles, federated timelines, governed Knowledge, Media, and Marketplace profile sections, artifact-integrity visual acceptance, and deterministic staging release engineering for the Sabri Social Homeopathy Platform.

== Description ==

This is the same existing File 25, canonically named “Sabri Unified Global Visual Experience and Design System,” with the subtitle “Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency.” No duplicate File 26 is created for this scope.

The reviewed 0.11.0 source foundation includes:

* global semantic visual tokens that inherit File 20 without duplicating its application shell;
* reusable containers, layouts, cards, buttons, badges, notices, form controls, tables, skeletons, and visual states;
* exact same-origin URL enforcement for reusable actions, cards, and media;
* a bounded optional profile-section provider contract for Knowledge, Media, Reviews, Research, and Marketplace;
* immutable provider ID, version, section, maturity, ownership, and concrete-object identity;
* atomic provider metadata validation and truthful bounded failure reporting;
* native read-only File 06 Knowledge, File 10 Video Wall, File 11 Reels, File 12 PDF Library, File 18 Marketplace, and File 21 timeline adapters;
* strict public-status, author/owner, password, version-range, and no-write boundaries;
* canonical Founder, verified-Doctor, and permitted-Member profile routes;
* exact route parity for every approved Knowledge, Media, Reviews, Research, and Marketplace provider section;
* a schema-2, lock-guarded, idempotent rewrite migration for newly routable provider sections;
* a machine-readable visual-acceptance matrix bound to one target commit;
* artifact references restricted to the governed `artifacts/` root with SHA-256, byte size, media type, timestamp, reviewer, and exact commit binding;
* a deterministic staging ZIP builder with embedded/detached manifests, checksum, safe paths, and development-file exclusion;
* an embedded-manifest installed-candidate verifier that rejects tampering, missing/extra files, symlinks, unsafe paths, wrong commit, and version mismatch;
* an exact Hostinger staging preflight for the canonical staging host, HTTPS, disabled registration, noindex, Safe Mode, dependencies, and expected commit;
* a privacy-safe WP-CLI staging command and machine-readable staging scenario plan;
* an explicit rule that source contracts, a ZIP, preflight, and green CI are not visual or production acceptance;
* PHP 8.0/8.3 and JavaScript CI.

This release is not production-complete. Exact multi-plugin staging, File 24 runtime integration, native Reviews/Research adapters, captured visual-regression evidence, rollback, real-user testing, performance, deployment, and monitoring remain pending.

== Installation ==

1. Install and activate the reviewed dependency packages listed in `config/staging-dependencies.json` on a backed-up staging installation.
2. Verify the detached SHA-256 checksum before extracting the File 25 staging ZIP.
3. Upload the `sabri-public-experience` folder to `/wp-content/plugins/`.
4. Activate File 25 only on staging and confirm File 00 Founder identity.
5. Run `wp sabri file25 staging-probe --expected-commit=<sha>` and correct every fail-closed gate.
6. Schema 2 refreshes expanded provider routes once; confirm Marketplace and Research route parity.
7. Review Site Health and visual acceptance; a package and green CI are not production acceptance.

== Changelog ==

= 0.11.0 =
* Fixed unreachable Research and Marketplace profile sections by deriving rewrite routes from the approved provider-section registry.
* Added an idempotent schema-2 rewrite migration with bounded lock and stale-lock recovery.
* Added read-only installed-candidate verification against `STAGING-MANIFEST.json`.
* Added the exact Hostinger staging preflight, privacy-safe WP-CLI command, and machine-readable staging test plan.
* Preserved live, File 20 shell ownership, native module ownership, Draft PR status, and the rule that preflight does not imply acceptance.

= 0.10.0 =
* Added artifact-integrity evidence and deterministic staging packaging.
* Added independent downloaded-artifact verification and exact dependency matrix.

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
