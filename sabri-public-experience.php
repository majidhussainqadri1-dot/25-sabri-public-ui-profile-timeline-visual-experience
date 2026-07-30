<?php
/**
 * Plugin Name: Sabri Public Experience
 * Plugin URI:  https://sabrihomeopathy.com/
 * Description: Unified public profiles, federated profile timelines, reusable public cards, and an accessibility-oriented visual experience for the Sabri Social Homeopathy Platform.
 * Version:     0.3.0
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

if (version_compare(PHP_VERSION, '8.0', '<')) {
    if (function_exists('add_action')) {
        add_action('admin_notices', static function () {
            echo '<div class="notice notice-error"><p>'
                . esc_html(sprintf(
                    __('Sabri Public Experience requires PHP 8.0 or newer. Current version: %s', 'sabri-public-experience'),
                    PHP_VERSION
                ))
                . '</p></div>';
        });
    }
    return;
}

$current_file = str_replace('\\', '/', __FILE__);
if (defined('SABRI_PUBLIC_EXPERIENCE_FILE')) {
    $loaded_file = str_replace('\\', '/', (string) SABRI_PUBLIC_EXPERIENCE_FILE);
    if (rtrim($loaded_file, '/') !== rtrim($current_file, '/')) {
        if (function_exists('update_option')) {
            update_option('sabri_public_experience_duplicate_copy', [
                'active_copy' => function_exists('plugin_basename') ? plugin_basename($loaded_file) : basename($loaded_file),
                'ignored_copy' => function_exists('plugin_basename') ? plugin_basename($current_file) : basename($current_file),
                'detected_at_utc' => gmdate('Y-m-d H:i:s'),
            ], false);
        }
        if (function_exists('add_action')) {
            add_action('admin_notices', static function (): void {
                if (! current_user_can('activate_plugins')) {
                    return;
                }
                echo '<div class="notice notice-error"><p>'
                    . esc_html__('A duplicate Sabri Public Experience plugin copy was ignored to prevent class collisions. Remove the obsolete copy before release.', 'sabri-public-experience')
                    . '</p></div>';
            });
        }
    }
    return;
}

if (function_exists('sabri_public_experience_bootstrap')) {
    return;
}

define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.3.0');
define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION', '1');
define('SABRI_PUBLIC_EXPERIENCE_FILE', __FILE__);
define('SABRI_PUBLIC_EXPERIENCE_DIR', plugin_dir_path(__FILE__));
define('SABRI_PUBLIC_EXPERIENCE_URL', plugin_dir_url(__FILE__));

require_once SABRI_PUBLIC_EXPERIENCE_DIR . 'includes/class-safe-mode.php';

$spux_files = [
    'includes/contracts/interface-timeline-provider.php',
    'includes/class-normalized-timeline-item.php',
    'includes/class-native-integration.php',
    'includes/class-dependency-manager.php',
    'includes/class-visibility-policy.php',
    'includes/class-profile-data.php',
    'includes/class-timeline-registry.php',
    'includes/class-timeline-service.php',
    'includes/providers/class-file-21-provider.php',
    'includes/providers/class-wordpress-posts-provider.php',
    'includes/class-profile-repository.php',
    'includes/class-profile-router.php',
    'includes/class-shell-integration.php',
    'includes/class-profile-renderer.php',
    'includes/class-assets.php',
    'includes/class-rest-controller.php',
    'includes/class-system-check.php',
    'includes/class-plugin.php',
];

try {
    \Sabri\PublicExperience\Safe_Mode::begin('file-loading');
    foreach ($spux_files as $spux_file) {
        $path = SABRI_PUBLIC_EXPERIENCE_DIR . $spux_file;
        if (! is_readable($path)) {
            throw new RuntimeException('Required File 25 source file is missing.');
        }
        require_once $path;
    }
    \Sabri\PublicExperience\Safe_Mode::end();
} catch (Throwable $exception) {
    \Sabri\PublicExperience\Safe_Mode::enable('file-loading-exception', $exception);
    \Sabri\PublicExperience\Safe_Mode::end();
    return;
}

function sabri_public_experience_activate(): void
{
    try {
        \Sabri\PublicExperience\Safe_Mode::begin('activation');
        \Sabri\PublicExperience\Plugin::activate();
        \Sabri\PublicExperience\Safe_Mode::end();
    } catch (Throwable $exception) {
        \Sabri\PublicExperience\Safe_Mode::enable('activation-exception', $exception);
        \Sabri\PublicExperience\Safe_Mode::end();
        throw $exception;
    }
}

function sabri_public_experience_deactivate(): void
{
    try {
        \Sabri\PublicExperience\Plugin::deactivate();
    } catch (Throwable $exception) {
        \Sabri\PublicExperience\Safe_Mode::enable('deactivation-exception', $exception);
    }
}

function sabri_public_experience_bootstrap(): void
{
    try {
        \Sabri\PublicExperience\Plugin::instance()->boot();
    } catch (Throwable $exception) {
        \Sabri\PublicExperience\Safe_Mode::enable('bootstrap-exception', $exception);
        \Sabri\PublicExperience\Safe_Mode::end();
        \Sabri\PublicExperience\Safe_Mode::register();
    }
}

register_activation_hook(__FILE__, 'sabri_public_experience_activate');
register_deactivation_hook(__FILE__, 'sabri_public_experience_deactivate');
add_action('plugins_loaded', 'sabri_public_experience_bootstrap', 20);
