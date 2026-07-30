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

Current foundation includes:

* canonical Founder, Doctor, and permitted Member profile routes;
* privacy-first public profile projection;
* read-only timeline provider contract and registry;
* safe WordPress post compatibility provider;
* public profile and timeline REST endpoints;
* responsive, keyboard-visible profile UI;
* Site Health checks;
* fail-closed dependency handling and Safe Mode.

== Installation ==

1. Install the required Sabri Membership Core dependency.
2. Upload the plugin folder to `/wp-content/plugins/`.
3. Activate the plugin.
4. Configure the Founder user ID through the platform integration contract.
5. Refresh permalinks if profile routes are not resolved automatically.

== Changelog ==

= 0.1.0 =
* Initial 25A/25B coding foundation.
