<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

final class Assets
{
    public function __construct(private Profile_Router $router)
    {
    }

    public function register(): void
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        if (! Design_System::should_enqueue()) {
            return;
        }

        // The public canonical handle includes both stable semantic primitives
        // and the current component-completion layer. Companion modules should
        // depend on `sabri-visual-design-system`, never on the internal core.
        wp_register_style(
            'sabri-visual-design-system-core',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/css/design-system.css',
            [],
            SABRI_PUBLIC_EXPERIENCE_VERSION
        );
        wp_enqueue_style(
            'sabri-visual-design-system',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/css/design-system-components.css',
            ['sabri-visual-design-system-core'],
            SABRI_PUBLIC_EXPERIENCE_VERSION
        );

        if (! $this->router->is_profile_request()) {
            return;
        }

        wp_enqueue_style(
            'sabri-public-experience',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/css/public.css',
            ['sabri-visual-design-system'],
            SABRI_PUBLIC_EXPERIENCE_VERSION
        );
        wp_enqueue_style(
            'sabri-public-experience-profile-sections',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/css/profile-sections.css',
            ['sabri-public-experience'],
            SABRI_PUBLIC_EXPERIENCE_VERSION
        );
        wp_enqueue_script(
            'sabri-public-experience',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/js/public.js',
            [],
            SABRI_PUBLIC_EXPERIENCE_VERSION,
            true
        );
        wp_script_add_data('sabri-public-experience', 'strategy', 'defer');
        wp_localize_script('sabri-public-experience', 'sabriPublicExperience', [
            'linkCopied' => __('Link copied.', 'sabri-public-experience'),
            'shareSucceeded' => __('Profile shared.', 'sabri-public-experience'),
            'shareFailed' => __('Unable to share this profile.', 'sabri-public-experience'),
        ]);
    }
}
