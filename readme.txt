=== Sabri Public Experience ===
Contributors: majidhussainqadri1-dot
Tags: profiles, timeline, accessibility, responsive, public-ui
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Unified public profiles, federated public timelines, reusable cards, and an accessibility-oriented visual experience for the Sabri Social Homeopathy Platform.

== Description ==

File 25 is the public presentation layer. It preserves native data ownership, consumes authoritative identity and privacy contracts, and never creates a duplicate publication backend.

The reviewed 0.3.0 foundation now includes:

* early PHP compatibility and duplicate-copy guards;
* non-destructive Safe Mode and truthful Site Health diagnostics;
* canonical Founder, verified-Doctor, and permitted-Member profile routes;
* File 00-authoritative public profile and contact projection;
* a bounded read-only timeline provider contract and registry;
* public-status, author, provider-identity, and same-site canonical validation;
* a native read-only File 21 adapter using `Sabri\HomeNewsFeed\ProfileTimeline::query()`;
* stable bounded paging across File 21's native twenty-item pages, with local normalization and redaction;
* a WordPress post fallback used only while File 21 is absent, never when File 21 is active but incompatible;
* public profile and timeline REST endpoints with no-store and identifier redaction;
* File 20 shell integration through its real layout filter and CSS custom properties, without duplicating the application shell;
* canonical public author links and role/section body classes;
* a structured Founder profile with mission, vision, objectives, books, research, methodology, experience, approved clinic information, and contact safety;
* a structured verified-Doctor profile with qualification, institution, authority, specialization, experience, languages, consultation modes, and verification-scope notice;
* privacy-safe allow-list normalization that excludes identity evidence, registration numbers, private notes, patient data, and internal IDs;
* responsive cards, honest available sections, initials fallback, accessible sharing, keyboard-visible UI, and scoped reduced motion;
* PHP 8.0/8.3 timeline, File 21 adapter, public-profile, package, and JavaScript CI.

This release is not production-complete. The File 21 source adapter remains read-only until exact multi-plugin staging acceptance; File 24's runtime contract, optional knowledge/media/review providers, staging, migration, rollback, real-user testing, accessibility evidence, packaging, and deployment remain pending.

== Installation ==

1. Install and activate the approved Sabri Membership Core dependency.
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Activate the plugin only on a backed-up staging installation.
4. Confirm File 00 Founder identity and refresh permalinks if routes are unresolved.
5. Review Site Health; a read-only File 21 adapter is not production-acceptance evidence.

== Changelog ==

= 0.3.0 =
* Added a native read-only File 21 timeline adapter over File 21's visibility-safe ProfileTimeline query contract.
* Added bounded native paging, public item normalization, provider health evidence, no-write ownership checks, and PHP 8.0/8.3 contract tests.
* Preserved fail-closed behavior: raw WordPress fallback is not used when File 21 is active but incompatible.

= 0.2.0 =
* Added File 20 shell-contract integration and the first complete Founder and verified-Doctor public profile presentations.
* Added bounded Founder, professional, and clinic public-data normalization with dedicated PHP 8.0/8.3 contract tests.
* Added public author-link canonicalization, nonprofessional-profile noindex defaults, richer structured metadata, and responsive section components.

= 0.1.0 =
* Initial 25A/25B coding foundation, followed by source review, defect correction, and strengthened CI.
