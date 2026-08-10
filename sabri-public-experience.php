<?php
/**
 * Plugin Name: Sabri Unified Global Visual Experience and Design System
 * Plugin URI:  https://sabrihomeopathy.com/
 * Description: File 25 global design system, public UI, profile timeline, responsive refinement, accessibility, visual consistency, governed native content adapters, File 24 security integration, File 03 contact-consent enforcement, structured public section APIs, artifact-integrity visual acceptance, and deterministic staging release engineering for the Sabri Social Homeopathy Platform.
 * Version:     0.14.0
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
                    __('Sabri Unified Global Visual Experience requires PHP 8.0 or newer. Current version: %s', 'sabri-public-experience'),
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
                    . esc_html__('A duplicate File 25 visual-system plugin copy was ignored to prevent class and CSS ownership collisions. Remove the obsolete copy before release.', 'sabri-public-experience')
                    . '</p></div>';
            });
        }
    }
    return;
}

if (function_exists('sabri_public_experience_bootstrap')) {
    return;
}

define('SABRI_PUBLIC_EXPERIENCE_VERSION', '0.14.0');
define('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION', '2');
define('SABRI_PUBLIC_EXPERIENCE_FILE', __FILE__);
define('SABRI_PUBLIC_EXPERIENCE_DIR', plugin_dir_path(__FILE__));
define('SABRI_PUBLIC_EXPERIENCE_URL', plugin_dir_url(__FILE__));

require_once SABRI_PUBLIC_EXPERIENCE_DIR . 'includes/class-safe-mode.php';

$spux_files = [
    'includes/class-public-url.php',
    'includes/class-components.php',
    'includes/class-content-cards.php',
    'includes/class-visual-acceptance.php',
    'includes/class-staging-probe.php',
    'includes/class-staging-cli.php',
    'includes/class-upgrade-manager.php',
    'includes/class-design-system.php',
    'includes/contracts/interface-timeline-provider.php',
    'includes/contracts/interface-profile-section-provider.php',
    'includes/class-normalized-timeline-item.php',
    'includes/class-file-24-integration.php',
    'includes/class-file-20-integration.php',
    'includes/class-native-integration.php',
    'includes/class-dependency-manager.php',
    'includes/class-visibility-policy.php',
    'includes/class-profile-data.php',
    'includes/class-timeline-registry.php',
    'includes/class-timeline-service.php',
    'includes/class-section-registry.php',
    'includes/class-section-service.php',
    'includes/providers/class-file-06-knowledge-provider.php',
    'includes/providers/class-file-10-video-media-provider.php',
    'includes/providers/class-file-11-reels-media-provider.php',
    'includes/providers/class-file-12-pdf-media-provider.php',
    'includes/providers/class-file-18-marketplace-provider.php',
    'includes/providers/class-file-21-provider.php',
    'includes/providers/class-wordpress-posts-provider.php',
    'includes/class-profile-repository.php',
    'includes/class-profile-router.php',
    'includes/class-shell-integration.php',
    'includes/class-profile-renderer.php',
    'includes/class-assets.php',
    'includes/class-rest-controller.php',
    'includes/class-system-check.php',
    'includes/class-plan-completion.php',
    'includes/class-forty-round-hardening.php',
    'includes/class-three-plan-corrections.php',
    'includes/class-central-plan-2026-corrections.php',
    'includes/class-future-public-experience.php',
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
    \Sabri\PublicExperience\Three_Plan_Corrections::register();
    \Sabri\PublicExperience\Central_Plan_2026_Corrections::register();
    \Sabri\PublicExperience\Safe_Mode::end();
} catch (Throwable $exception) {
    \Sabri\PublicExperience\Safe_Mode::enable('file-loading-exception', $exception);
    \Sabri\PublicExperience\Safe_Mode::end();
    return;
}

if (! function_exists('sabri_visual_experience_contract')) {
    function sabri_visual_experience_contract(): array
    {
        $contract = \Sabri\PublicExperience\Design_System::contract();

        return function_exists('apply_filters')
            ? (array) apply_filters('sabri_visual_experience/contract', $contract)
            : \Sabri\PublicExperience\Central_Plan_2026_Corrections::filter_contract($contract);
    }
}

if (! function_exists('sabri_visual_experience_acceptance_contract')) {
    function sabri_visual_experience_acceptance_contract(): array
    {
        return \Sabri\PublicExperience\Visual_Acceptance::contract();
    }
}

if (! function_exists('sabri_visual_experience_render_state')) {
    function sabri_visual_experience_render_state(array $args = []): string
    {
        return \Sabri\PublicExperience\Components::render_state($args);
    }
}

if (! function_exists('sabri_visual_experience_render_notice')) {
    function sabri_visual_experience_render_notice(array $args = []): string
    {
        return \Sabri\PublicExperience\Components::render_notice($args);
    }
}

if (! function_exists('sabri_visual_experience_render_card')) {
    function sabri_visual_experience_render_card(array $args = []): string
    {
        return \Sabri\PublicExperience\Content_Cards::render($args);
    }
}

if (! function_exists('sabri_visual_experience_render_welcome')) {
    function sabri_visual_experience_render_welcome(array $args = []): string
    {
        return \Sabri\PublicExperience\Three_Plan_Corrections::render_welcome_panel($args);
    }
}

if (! function_exists('sabri_public_render_profile_card')) {
    function sabri_public_render_profile_card(array $profile = []): string
    {
        return \Sabri\PublicExperience\Content_Cards::render(['type' => 'profile', 'profile' => $profile, 'title' => (string) ($profile['display_name'] ?? '')]);
    }
}

if (! function_exists('sabri_public_render_content_card')) {
    function sabri_public_render_content_card(array $args = []): string
    {
        return \Sabri\PublicExperience\Content_Cards::render($args);
    }
}

if (! function_exists('sabri_public_render_badge')) {
    function sabri_public_render_badge(string $label, string $type = 'default'): string
    {
        return '<span class="spux-badge spux-badge--' . esc_attr(sanitize_key($type)) . '">' . esc_html($label) . '</span>';
    }
}

if (! function_exists('sabri_public_render_empty_state')) {
    function sabri_public_render_empty_state(array $args = []): string
    {
        return \Sabri\PublicExperience\Components::render_state(array_merge(['type' => 'empty'], $args));
    }
}

if (! function_exists('sabri_public_render_author_header')) {
    function sabri_public_render_author_header(array $profile = []): string
    {
        return '<header class="spux-author-header"><strong>' . esc_html((string) ($profile['display_name'] ?? '')) . '</strong></header>';
    }
}

if (! function_exists('sabri_public_register_timeline_provider')) {
    function sabri_public_register_timeline_provider($provider): void
    {
        do_action('sabri_public_experience/external_timeline_provider', $provider);
    }
}

if (! function_exists('sabri_public_register_card_variant')) {
    function sabri_public_register_card_variant(string $variant, callable $renderer): void
    {
        do_action('sabri_public_experience/register_card_variant', sanitize_key($variant), $renderer);
    }
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
