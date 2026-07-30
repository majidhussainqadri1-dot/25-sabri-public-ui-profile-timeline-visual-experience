<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use Sabri\PublicExperience\Providers\WordPress_Posts_Provider;

if (! defined('ABSPATH')) {
    exit;
}

final class Plugin
{
    private static ?self $instance = null;
    private bool $booted = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate(): void
    {
        if (version_compare(PHP_VERSION, Dependency_Manager::MINIMUM_PHP, '<')) {
            deactivate_plugins(plugin_basename(SABRI_PUBLIC_EXPERIENCE_FILE));
            wp_die(esc_html__('Sabri Public Experience requires PHP 8.0 or newer.', 'sabri-public-experience'));
        }

        add_option('sabri_public_experience_schema_version', SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION, '', false);
        add_option('sabri_public_experience_safe_mode', '0', '', false);
        add_option('sabri_public_experience_founder_display_name', 'Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed', '', false);
        Profile_Router::flush();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules(false);
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        load_plugin_textdomain('sabri-public-experience', false, dirname(plugin_basename(SABRI_PUBLIC_EXPERIENCE_FILE)) . '/languages');

        $native = new Native_Integration();
        $dependencies = new Dependency_Manager($native);
        if (! $dependencies->runtime_is_supported()) {
            add_action('admin_notices', static function () use ($dependencies): void {
                if (! current_user_can('activate_plugins')) {
                    return;
                }
                echo '<div class="notice notice-error"><p>';
                echo esc_html(sprintf(
                    __('Sabri Public Experience is in fail-closed mode. Missing required dependencies: %s', 'sabri-public-experience'),
                    implode(', ', $dependencies->get_blockers())
                ));
                echo '</p></div>';
            });
            return;
        }

        if (get_option('sabri_public_experience_safe_mode', '0') === '1') {
            add_action('admin_notices', static function (): void {
                if (current_user_can('manage_options')) {
                    echo '<div class="notice notice-warning"><p>' . esc_html__('Sabri Public Experience Safe Mode is active. Public profile overrides are disabled.', 'sabri-public-experience') . '</p></div>';
                }
            });
            return;
        }

        $visibility = new Visibility_Policy($native);
        $profiles = new Profile_Repository($visibility, $native);
        $router = new Profile_Router();
        $registry = new Timeline_Registry();

        /**
         * Register richer native providers without modifying File 25 internals.
         * File 21's production provider must use the canonical `file-21` ID.
         *
         * @param Timeline_Registry $registry
         */
        do_action('sabri_public_experience/register_timeline_providers', $registry);

        if ($registry->get('file-21') === null) {
            $registry->register(new WordPress_Posts_Provider());
        }

        $timeline = new Timeline_Service($registry);
        $renderer = new Profile_Renderer($router, $profiles, $timeline);

        $router->register();
        $renderer->register();
        (new Assets($router))->register();
        (new Rest_Controller($profiles, $timeline))->register();
        (new System_Check($dependencies, $registry))->register();

        do_action('sabri_public_experience/booted', $this, $registry);
    }
}
