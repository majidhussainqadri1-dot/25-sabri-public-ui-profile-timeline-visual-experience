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
        global $wp_version;
        if (version_compare(PHP_VERSION, Dependency_Manager::MINIMUM_PHP, '<')) {
            deactivate_plugins(plugin_basename(SABRI_PUBLIC_EXPERIENCE_FILE));
            wp_die(esc_html__('Sabri Public Experience requires PHP 8.0 or newer.', 'sabri-public-experience'));
        }
        if (version_compare((string) $wp_version, Dependency_Manager::MINIMUM_WORDPRESS, '<')) {
            deactivate_plugins(plugin_basename(SABRI_PUBLIC_EXPERIENCE_FILE));
            wp_die(esc_html__('Sabri Public Experience requires WordPress 6.5 or newer.', 'sabri-public-experience'));
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

        Safe_Mode::register();
        Safe_Mode::register_shutdown_guard();
        Safe_Mode::begin('bootstrap');

        load_plugin_textdomain('sabri-public-experience', false, dirname(plugin_basename(SABRI_PUBLIC_EXPERIENCE_FILE)) . '/languages');

        $native = new Native_Integration();
        $dependencies = new Dependency_Manager($native);
        $registry = new Timeline_Registry();
        (new System_Check($dependencies, $registry))->register();

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
            Safe_Mode::end();
            return;
        }

        if (Safe_Mode::is_active()) {
            Safe_Mode::end();
            return;
        }

        $visibility = new Visibility_Policy($native);
        $profiles = new Profile_Repository($visibility, $native);
        $router = new Profile_Router();

        /**
         * Register richer native providers without modifying File 25 internals.
         * File 21's production provider must use the canonical `file-21` ID.
         *
         * @param Timeline_Registry $registry
         */
        try {
            do_action('sabri_public_experience/register_timeline_providers', $registry);
        } catch (\Throwable $exception) {
            do_action('sabri_public_experience/provider_registration_error', $exception);
        }

        if ($registry->get('file-21') === null) {
            try {
                $registry->register(new WordPress_Posts_Provider());
            } catch (\Throwable $exception) {
                do_action('sabri_public_experience/provider_registration_error', $exception);
            }
        }

        $timeline = new Timeline_Service($registry);
        $renderer = new Profile_Renderer($router, $profiles, $timeline);

        $router->register();
        $renderer->register();
        (new Assets($router))->register();
        (new Rest_Controller($profiles, $timeline))->register();

        do_action('sabri_public_experience/booted', $this, $registry);
        Safe_Mode::end();
    }
}
