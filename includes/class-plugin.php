<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use Sabri\PublicExperience\Providers\File_06_Knowledge_Provider;
use Sabri\PublicExperience\Providers\File_10_Video_Media_Provider;
use Sabri\PublicExperience\Providers\File_11_Reels_Media_Provider;
use Sabri\PublicExperience\Providers\File_12_Pdf_Media_Provider;
use Sabri\PublicExperience\Providers\File_18_Marketplace_Provider;
use Sabri\PublicExperience\Providers\File_21_Provider;
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
            wp_die(esc_html__('Sabri Unified Global Visual Experience requires PHP 8.0 or newer.', 'sabri-public-experience'));
        }
        if (version_compare((string) $wp_version, Dependency_Manager::MINIMUM_WORDPRESS, '<')) {
            deactivate_plugins(plugin_basename(SABRI_PUBLIC_EXPERIENCE_FILE));
            wp_die(esc_html__('Sabri Unified Global Visual Experience requires WordPress 6.5 or newer.', 'sabri-public-experience'));
        }

        update_option('sabri_public_experience_schema_version', SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION, false);
        update_option('sabri_public_experience_runtime_version', SABRI_PUBLIC_EXPERIENCE_VERSION, false);
        add_option('sabri_public_experience_safe_mode', '0', '', false);
        add_option('sabri_public_experience_founder_display_name', Profile_Repository::FOUNDER_DISPLAY_NAME, '', false);
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
        (new Plan_Completion())->register();

        $router = new Profile_Router();
        $file_24 = new File_24_Integration($router);
        $file_24->register();

        $native = new Native_Integration();
        $dependencies = new Dependency_Manager($native);
        $timeline_registry = new Timeline_Registry();
        $section_registry = new Section_Registry();
        $staging_probe = new Staging_Probe($dependencies, $timeline_registry, $section_registry);
        (new System_Check($dependencies, $timeline_registry, $section_registry, $staging_probe, $file_24))->register();
        Staging_CLI::register($staging_probe);

        if (Safe_Mode::is_active()) {
            Safe_Mode::end();
            return;
        }

        (new Upgrade_Manager())->register();
        (new Design_System())->register();
        (new Assets($router))->register();
        do_action('sabri_visual_experience/design_system_booted', Design_System::contract());

        if (! $dependencies->runtime_is_supported()) {
            add_action('admin_notices', static function () use ($dependencies): void {
                if (! current_user_can('activate_plugins')) {
                    return;
                }
                echo '<div class="notice notice-error"><p>';
                echo esc_html(sprintf(
                    __('File 25 profile and timeline features are fail-closed. Missing required dependencies: %s', 'sabri-public-experience'),
                    implode(', ', $dependencies->get_blockers())
                ));
                echo '</p></div>';
            });
            Safe_Mode::end();
            return;
        }

        $visibility = new Visibility_Policy($native);
        $profiles = new Profile_Repository($visibility, $native);

        try {
            do_action('sabri_public_experience/register_timeline_providers', $timeline_registry);
        } catch (\Throwable $exception) {
            do_action('sabri_public_experience/provider_registration_error', $exception);
        }

        if ($timeline_registry->get('file-21') === null && $native->home_news_available()) {
            self::register_timeline_provider($timeline_registry, new File_21_Provider());
        }
        if ($timeline_registry->get('file-21') === null && ! $native->home_news_available()) {
            self::register_timeline_provider($timeline_registry, new WordPress_Posts_Provider());
        }

        try {
            do_action('sabri_public_experience/register_section_providers', $section_registry);
        } catch (\Throwable $exception) {
            do_action('sabri_public_experience/section_provider_registration_error', $exception);
        }

        self::register_section_provider($section_registry, 'file-06-knowledge', new File_06_Knowledge_Provider());
        self::register_section_provider($section_registry, 'file-10-video-media', new File_10_Video_Media_Provider());
        self::register_section_provider($section_registry, 'file-11-reels-media', new File_11_Reels_Media_Provider());
        self::register_section_provider($section_registry, 'file-12-pdf-media', new File_12_Pdf_Media_Provider());
        self::register_section_provider($section_registry, 'file-18-marketplace', new File_18_Marketplace_Provider());

        $timeline = new Timeline_Service($timeline_registry);
        $sections = new Section_Service($section_registry);
        $renderer = new Profile_Renderer($router, $profiles, $timeline, $sections);

        $router->register();
        (new Shell_Integration($router, $profiles, $native))->register();
        $renderer->register();
        (new Rest_Controller($profiles, $timeline, $sections))->register();

        do_action('sabri_public_experience/booted', $this, $timeline_registry, $section_registry);
        Safe_Mode::end();
    }

    private static function register_timeline_provider(Timeline_Registry $registry, Contracts\Timeline_Provider $provider): void
    {
        try {
            if ($provider->is_available()) {
                $registry->register($provider);
            }
        } catch (\Throwable $exception) {
            do_action('sabri_public_experience/provider_registration_error', $exception);
        }
    }

    private static function register_section_provider(Section_Registry $registry, string $canonical_id, Contracts\Profile_Section_Provider $provider): void
    {
        if ($registry->get($canonical_id) !== null) {
            return;
        }
        try {
            if ($provider->is_available()) {
                $registry->register($provider);
            }
        } catch (\Throwable $exception) {
            do_action('sabri_public_experience/section_provider_registration_error', $exception);
        }
    }
}
