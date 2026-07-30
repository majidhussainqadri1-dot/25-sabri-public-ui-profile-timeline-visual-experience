<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

final class Profile_Renderer
{
    public function __construct(
        private Profile_Router $router,
        private Profile_Repository $profiles,
        private Timeline_Service $timeline
    ) {
    }

    public function register(): void
    {
        add_filter('template_include', [$this, 'template_include'], 99);
        add_action('template_redirect', [$this, 'prepare_response'], 1);
        add_filter('document_title_parts', [$this, 'document_title_parts']);
        add_filter('wp_robots', [$this, 'robots']);
        add_action('wp_head', [$this, 'render_meta'], 1);
    }

    public function template_include(string $template): string
    {
        if (! $this->router->is_profile_request()) {
            return $template;
        }
        return SABRI_PUBLIC_EXPERIENCE_DIR . 'templates/public-profile.php';
    }

    public function prepare_response(): void
    {
        if (! $this->router->is_profile_request()) {
            return;
        }

        $user = $this->resolve_user();
        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if (! $user instanceof WP_User || $profile === null) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            nocache_headers();
            return;
        }

        $context = $this->router->context();
        $canonical = $this->profiles->canonical_url($user, $context['section']);
        $profile['canonical_url'] = $canonical;
        $requested_type = $context['type'];
        $canonical_type = (string) $profile['class'];
        if (($requested_type === 'member' && in_array($canonical_type, ['founder', 'doctor'], true))
            || ($requested_type === 'doctor' && $canonical_type !== 'doctor')) {
            wp_safe_redirect($canonical, 301);
            exit;
        }

        $content_type = '';
        if (isset($_GET['type']) && is_scalar($_GET['type'])) {
            $content_type = sanitize_key((string) wp_unslash($_GET['type']));
        }

        $GLOBALS['sabri_public_experience_profile_user'] = $user;
        $GLOBALS['sabri_public_experience_profile'] = $profile;
        $GLOBALS['sabri_public_experience_context'] = $context;
        $GLOBALS['sabri_public_experience_timeline'] = $this->timeline->get_for_author(
            (int) $user->ID,
            [
                'page' => max(1, (int) get_query_var('paged')),
                'per_page' => 20,
                'content_type' => $content_type,
            ]
        );

        status_header(200);
    }

    /** @param array<string,string> $parts @return array<string,string> */
    public function document_title_parts(array $parts): array
    {
        if ($this->router->is_profile_request() && ! empty($GLOBALS['sabri_public_experience_profile']['display_name'])) {
            $parts['title'] = (string) $GLOBALS['sabri_public_experience_profile']['display_name'];
        }
        return $parts;
    }

    /** @param array<string,bool> $robots @return array<string,bool> */
    public function robots(array $robots): array
    {
        if ($this->router->is_profile_request() && is_404()) {
            $robots['noindex'] = true;
            $robots['noarchive'] = true;
        }
        if ($this->router->is_profile_request() && isset($_GET['type'])) {
            $robots['noindex'] = true;
        }
        return $robots;
    }

    public function render_meta(): void
    {
        if (! $this->router->is_profile_request() || is_404() || empty($GLOBALS['sabri_public_experience_profile'])) {
            return;
        }

        $profile = (array) $GLOBALS['sabri_public_experience_profile'];
        echo '<link rel="canonical" href="' . esc_url((string) ($profile['canonical_url'] ?? '')) . '">' . "\n";
        echo '<meta name="description" content="' . esc_attr(wp_trim_words((string) ($profile['bio'] ?? ''), 30)) . '">' . "\n";
        echo '<meta property="og:type" content="profile">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr((string) ($profile['display_name'] ?? '')) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url((string) ($profile['canonical_url'] ?? '')) . '">' . "\n";
        if (! empty($profile['avatar_url'])) {
            echo '<meta property="og:image" content="' . esc_url((string) $profile['avatar_url']) . '">' . "\n";
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'url' => (string) ($profile['canonical_url'] ?? ''),
            'mainEntity' => [
                '@type' => 'Person',
                'name' => (string) ($profile['display_name'] ?? ''),
                'description' => wp_trim_words((string) ($profile['bio'] ?? ''), 40),
                'image' => (string) ($profile['avatar_url'] ?? ''),
            ],
        ];
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . '</script>' . "\n";
    }

    private function resolve_user(): ?WP_User
    {
        $context = $this->router->context();
        if ($context['type'] === 'founder') {
            return $this->profiles->get_founder();
        }
        return $this->profiles->find_by_slug($context['slug']);
    }
}
