<?php
/**
 * Plugin Name: Sabri Public Experience
 * Plugin URI:  https://sabrihomeopathy.com/
 * Description: Unified public profiles, federated profile timelines, reusable public cards, and an accessibility-oriented visual experience for the Sabri Social Homeopathy Platform.
 * Version:     0.1.0
 * Author:      Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed
 * Text Domain: sabri-public-experience
 * Requires at least: 6.5
 * Requires PHP: 8.0
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.1.0');
define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION', '1');
define('SABRI_PUBLIC_EXPERIENCE_FILE', __FILE__);
define('SABRI_PUBLIC_EXPERIENCE_DIR', plugin_dir_path(__FILE__));
define('SABRI_PUBLIC_EXPERIENCE_URL', plugin_dir_url(__FILE__));

$spux_files = [
    'includes/contracts/interface-timeline-provider.php',
    'includes/class-normalized-timeline-item.php',
    'includes/class-native-integration.php',
    'includes/class-dependency-manager.php',
    'includes/class-visibility-policy.php',
    'includes/class-timeline-registry.php',
    'includes/class-timeline-service.php',
    'includes/providers/class-wordpress-posts-provider.php',
    'includes/class-profile-repository.php',
    'includes/class-profile-router.php',
    'includes/class-profile-renderer.php',
    'includes/class-assets.php',
    'includes/class-rest-controller.php',
    'includes/class-system-check.php',
    'includes/class-plugin.php',
];

foreach ($spux_files as $spux_file) {
    require_once SABRI_PUBLIC_EXPERIENCE_DIR . $spux_file;
}

register_activation_hook(
    __FILE__,
    static function (): void {
        \Sabri\PublicExperience\Plugin::activate();
    }
);

register_deactivation_hook(
    __FILE__,
    static function (): void {
        \Sabri\PublicExperience\Plugin::deactivate();
    }
);

add_action(
    'plugins_loaded',
    static function (): void {
        \Sabri\PublicExperience\Plugin::instance()->boot();
    },
    20
);
