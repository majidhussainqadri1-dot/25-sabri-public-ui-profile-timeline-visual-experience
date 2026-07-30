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
        if (! $this->router->is_profile_request()) {
            return;
        }

        wp_enqueue_style(
            'sabri-public-experience',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/css/public.css',
            [],
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
