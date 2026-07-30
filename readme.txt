=== Sabri Unified Global Visual Experience and Design System ===
Contributors: majidhussainqadri1-dot
Tags: design-system, public-ui, profiles, timeline, accessibility, responsive, rtl
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.4.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

File 25 global design system, public UI, profile timeline, responsive refinement, accessibility, and visual consistency for the Sabri Social Homeopathy Platform.

== Description ==

File 25 is canonically **Sabri Unified Global Visual Experience and Design System**.

Subtitle: **Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency**.

This is the same existing File 25 and does not create File 26. File 20 remains the sole application-shell owner; File 21 remains the Home, News, posts, comments, interactions, moderation, and social owner.

The reviewed 0.4.0 source includes:

* global `--sabri-visual-*` semantic tokens inheriting File 20 shell tokens with safe fallbacks;
* prefixed `sabri-ui-*` cards, buttons, badges, layouts, skeletons, and loading/empty/error/success/warning/unavailable states;
* a versioned design-system contract, public integration functions, and WordPress filters;
* global public CSS that does not depend on File 00 identity services;
* profile/timeline features that remain fail-closed when required identity dependencies are absent;
* removal of the former profile-only inline token bridge;
* nested Safe Mode boundaries with correct fatal attribution;
* early PHP and duplicate-copy guards;
* canonical Founder, verified-Doctor, and permitted-Member profile routes;
* File 00-authoritative identity, visibility, and contact projection;
* structured Founder and verified-Doctor public presentations;
* a bounded read-only timeline provider contract and registry;
* a native read-only File 21 adapter using `Sabri\HomeNewsFeed\ProfileTimeline::query()`;
* same-site canonical validation, author/provider binding, deduplication, pagination, and identifier redaction;
* public profile/timeline REST endpoints with no-store;
* visible focus, 44px-compatible targets, logical RTL/LTR properties, reduced motion, forced colors, and responsive refinement;
* PHP 8.0/8.3 design-system, Safe Mode, timeline, File 21 adapter, profile, package, and JavaScript CI.

This release is not production-complete. Exact multi-plugin staging, File 24 runtime integration, cross-module visual adoption, full viewport/RTL/accessibility/performance evidence, migration, rollback, real-user testing, packaging, deployment, and monitoring remain pending.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` on a backed-up staging installation.
2. Activate File 25.
3. Confirm the File 25 global design-system Site Health test is available.
4. Install and activate approved File 00/03/20/21 integrations for profile and timeline functionality.
5. Refresh permalinks if profile routes are unresolved.
6. Treat read-only source integration and green CI as development evidence, not production acceptance.

== Changelog ==

= 0.4.0 =
* Renamed the same existing File 25 to Sabri Unified Global Visual Experience and Design System; no File 26 is created.
* Added the global semantic token and reusable component/state contract.
* Added independent global design-system asset loading while profile features remain identity-dependent and fail-closed.
* Removed the inline File 20 token bridge and retained one shell owner.
* Corrected Safe Mode to preserve nested recovery boundaries.
* Added design-system, Safe Mode, package, and Site Health evidence.

= 0.3.0 =
* Added a native read-only File 21 timeline adapter over File 21's visibility-safe ProfileTimeline query contract.
* Added bounded native paging, normalization, provider health evidence, and no-write ownership checks.

= 0.2.0 =
* Added File 20 shell-contract integration and Founder/verified-Doctor public profile foundations.

= 0.1.0 =
* Initial foundation followed by source review and defect correction.
