=== Sabri Public Experience ===
Contributors: majidhussainqadri1-dot
Tags: profiles, timeline, accessibility, responsive, public-ui
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Unified public profiles, federated public timelines, reusable cards, and an accessibility-oriented visual experience for the Sabri Social Homeopathy Platform.

== Description ==

File 25 is the public presentation layer. It preserves native data ownership, consumes authoritative identity and privacy contracts, and never creates a duplicate publication backend.

The reviewed 25A/25B foundation includes:

* early PHP compatibility and duplicate-copy guards;
* non-destructive Safe Mode and Site Health diagnostics;
* canonical Founder, verified-Doctor, and permitted-Member profile routes;
* File 00-authoritative public profile and contact projection;
* a bounded read-only timeline provider contract and registry;
* public-status, author, provider-identity, and same-site canonical validation;
* a WordPress post fallback used only while File 21 is absent;
* public profile and timeline REST endpoints with no-store and identifier redaction;
* responsive profile presentation, honest available sections, initials fallback, accessible sharing, and keyboard-visible UI;
* PHP 8.0/8.3 contract, package, and JavaScript CI.

This release is not production-complete. File 21's production timeline provider, File 24's runtime contract, optional knowledge/media providers, staging, migration, rollback, real-user testing, accessibility evidence, packaging, and deployment remain pending.

== Installation ==

1. Install and activate the approved Sabri Membership Core dependency.
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Activate the plugin only on a backed-up staging installation.
4. Confirm File 00 Founder identity and refresh permalinks if routes are unresolved.
5. Review Site Health; production gaps must not be treated as a completed release.

== Changelog ==

= 0.1.0 =
* Initial 25A/25B coding foundation, followed by source review, defect correction, and strengthened CI.
