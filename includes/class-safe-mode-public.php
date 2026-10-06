<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Minimal public recovery surface used only while File 25 Safe Mode is active.
 *
 * It deliberately renders no profile, contact, timeline or provider data. File 20
 * may continue to supply the global shell, while File 25 keeps its canonical
 * profile routes recoverable without invoking optional providers or migrations.
 */
final class Safe_Mode_Public
{
    public function __construct(private Profile_Router $router)
    {
    }

    public function register(): void
    {
        $this->router->register();
        add_action('template_redirect', [$this, 'prepare_response'], 0);
        add_filter('template_include', [$this, 'template_include'], PHP_INT_MAX);
        add_filter('document_title_parts', [$this, 'document_title_parts'], PHP_INT_MAX);
        add_filter('wp_robots', [$this, 'robots'], PHP_INT_MAX);
    }

    public function prepare_response(): void
    {
        if (! $this->router->is_profile_request()) {
            return;
        }

        if (function_exists('status_header')) {
            status_header(503);
        }
        if (function_exists('nocache_headers')) {
            nocache_headers();
        }
        if (! headers_sent()) {
            header('Cache-Control: no-store, private, max-age=0');
            header('Retry-After: 300');
        }
    }

    public function template_include(string $template): string
    {
        if (! $this->router->is_profile_request()) {
            return $template;
        }

        return SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/safe-mode-profile.php';
    }

    /** @param array<string,string> $parts @return array<string,string> */
    public function document_title_parts(array $parts): array
    {
        if ($this->router->is_profile_request()) {
            $parts['title'] = __('Profile temporarily unavailable', 'sabri-public-experience');
        }

        return $parts;
    }

    /** @param array<string,bool|string> $robots @return array<string,bool|string> */
    public function robots(array $robots): array
    {
        if ($this->router->is_profile_request()) {
            $robots['noindex'] = true;
            $robots['noarchive'] = true;
            $robots['nofollow'] = true;
        }

        return $robots;
    }
}
