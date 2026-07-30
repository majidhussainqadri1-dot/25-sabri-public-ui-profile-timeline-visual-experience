=== Sabri Unified Global Visual Experience and Design System ===
Contributors: majidhussainqadri1-dot
Tags: design-system, profiles, timeline, accessibility, responsive, public-ui, content-cards, profile-sections
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.6.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

File 25 global public design system, reusable components, public profiles, federated timelines, optional profile sections, responsive refinement, and accessibility for the Sabri Social Homeopathy Platform.

== Description ==

This is the same existing File 25, canonically named “Sabri Unified Global Visual Experience and Design System,” with the subtitle “Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency.” No duplicate File 26 is created for this scope.

The reviewed 0.6.0 source foundation includes:

* global semantic visual tokens that inherit File 20 without duplicating its application shell;
* contrast-safe light, dark, system, and shell-degraded visual derivatives;
* reusable containers, layouts, cards, buttons, badges, notices, form controls, tables, skeletons, and visual states;
* escaped public state, notice, and content-card rendering helpers;
* presentation-only cards for article, post, news, video, reel, book, PDF, Doctor, clinic, event, and Marketplace visual variants;
* exact same-origin URL enforcement against protocol-relative, external, downgrade, credential-bearing, malformed, port-mismatched, and backslash destinations;
* a bounded optional profile-section provider contract for Knowledge, Media, Reviews, Research, and Marketplace;
* content-backed tab visibility so optional sections remain hidden until an accepted native provider returns approved public cards;
* provider ID/version/section/maturity validation, runtime revalidation, native-ownership denial, exception isolation, deduplication, and bounded results;
* nested non-destructive Safe Mode and truthful Site Health diagnostics;
* canonical Founder, verified-Doctor, and permitted-Member profile routes;
* File 00-authoritative public profile and contact projection;
* a bounded read-only timeline provider contract and registry;
* public-status, author, provider-identity, and same-site canonical validation;
* a native read-only File 21 adapter using `Sabri\HomeNewsFeed\ProfileTimeline::query()`;
* stable bounded paging across File 21 native pages with local normalization and redaction;
* public profile and timeline REST endpoints with no-store and identifier redaction;
* File 20 shell integration without creating a second header, navigation, sidebar, drawer, or bottom navigation;
* structured Founder and verified-Doctor profiles using privacy-safe public allow lists;
* visible focus, RTL/LTR logical properties, reduced motion, forced colors, print handling, local/system fonts, and responsive components;
* PHP 8.0/8.3 timeline, profile, File 21, design-system, URL-security, content-card, optional-section, Safe Mode, package, and JavaScript CI.

This release is not production-complete. Exact multi-plugin staging, File 24 runtime integration, native Knowledge/Media/Reviews/Research/Marketplace adapters, visual-regression evidence, migration, rollback, real-user testing, performance, packaging, and deployment remain pending.

== Installation ==

1. Install and activate the approved Sabri Membership Core dependency for profile and timeline features.
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Activate the plugin only on a backed-up staging installation.
4. Confirm File 00 Founder identity and refresh permalinks if profile routes are unresolved.
5. Review Site Health; source contracts and reusable components are not production-acceptance evidence.

== Changelog ==

= 0.6.0 =
* Added a bounded read-only optional profile-section provider contract and registry.
* Added content-backed Knowledge, Media, Reviews, Research, and Marketplace profile tabs.
* Added strict provider ID, version, section, maturity, ownership, item-limit, deduplication, and exception-isolation rules.
* Added a canonical optional-section template and Site Health diagnostics.
* Completed the reusable component class map and added `sabri_visual_experience_render_notice()`.
* Added PHP 8.0/8.3 optional-section provider tests and package rules.

= 0.5.0 =
* Added exact same-origin URL security for reusable visual actions and cards.
* Added reusable presentation-only content cards for eleven platform content/profile types.
* Added notice, form-field, input, select, textarea, responsive table, print, and expanded contrast tokens.
* Corrected protocol-relative link escape, dark-theme File 20 token overrides, variable translation strings, badge theming, danger-button contrast, and shell-detection accuracy.
* Added `sabri_visual_experience_render_card()` and PHP 8.0/8.3 content-card security tests.

= 0.4.0 =
* Added the canonical global visual-system contract, reusable visual states, semantic tokens, independent public design assets, and nested Safe Mode boundaries.

= 0.3.0 =
* Added a native read-only File 21 timeline adapter, bounded native paging, public normalization, health evidence, and no-write tests.

= 0.2.0 =
* Added File 20 shell-contract integration and the first complete Founder and verified-Doctor public profile presentations.

= 0.1.0 =
* Initial 25A/25B coding foundation, followed by source review, defect correction, and strengthened CI.
