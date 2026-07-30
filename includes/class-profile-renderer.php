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
        private Timeline_Service $timeline,
        private Section_Service $sections
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

        if (! defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();

        $context = $this->router->context();
        $user = $this->resolve_user();
        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if (! $user instanceof WP_User || $profile === null) {
            $this->set_not_found();
            return;
        }

        $provider_labels = $this->sections->available_for_profile((int) $user->ID, $profile);
        $available_sections = array_values(array_filter(
            (array) ($profile['available_sections'] ?? ['overview']),
            'is_string'
        ));
        $available_sections = array_values(array_unique(array_merge($available_sections, array_keys($provider_labels))));
        if (! in_array('overview', $available_sections, true)) {
            array_unshift($available_sections, 'overview');
        }
        $profile['available_sections'] = $available_sections;
        $profile['section_labels'] = array_merge((array) ($profile['section_labels'] ?? []), $provider_labels);

        $requested_type = $context['type'];
        $canonical_type = (string) $profile['class'];
        $requested_section = sanitize_key((string) $context['section']) ?: 'overview';
        $canonical_section = in_array($requested_section, $available_sections, true)
            ? $requested_section
            : 'overview';
        $canonical = $this->profiles->canonical_url($user, $canonical_section);
        $requested_slug = sanitize_title((string) ($context['slug'] ?? ''));
        $canonical_slug = sanitize_title((string) $user->user_nicename);

        if (
            ($requested_type === 'member' && in_array($canonical_type, ['founder', 'doctor'], true))
            || ($requested_type === 'doctor' && $canonical_type !== 'doctor')
            || ($requested_type !== 'founder' && $requested_slug !== $canonical_slug)
        ) {
            wp_safe_redirect($canonical, 301);
            exit;
        }

        if (! in_array($requested_section, $available_sections, true)) {
            $this->set_not_found();
            return;
        }

        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_singular = true;
        status_header(200);

        $profile['canonical_url'] = $canonical;
        $context['section'] = $requested_section;
        $context['available_sections'] = $available_sections;

        $timeline = [
            'items' => [],
            'page' => 1,
            'per_page' => 20,
            'has_more' => false,
            'truncated' => false,
            'provider_errors' => [],
        ];
        if ($requested_section === 'timeline') {
            $content_type = '';
            if (isset($_GET['type']) && is_scalar($_GET['type'])) {
                $content_type = sanitize_key((string) wp_unslash($_GET['type']));
            }
            $timeline = $this->timeline->get_for_author((int) $user->ID, [
                'page' => max(1, (int) get_query_var('paged')),
                'per_page' => 20,
                'content_type' => $content_type,
            ]);
        }

        $provider_section = [
            'section' => '',
            'label' => '',
            'items' => [],
            'provider_error_count' => 0,
            'truncated' => false,
            'is_provider_section' => false,
        ];
        if (isset($provider_labels[$requested_section])) {
            $provider_section = $this->sections->get_section((int) $user->ID, $profile, $requested_section);
        }

        $GLOBALS['sabri_public_experience_profile_user'] = $user;
        $GLOBALS['sabri_public_experience_profile'] = $profile;
        $GLOBALS['sabri_public_experience_context'] = $context;
        $GLOBALS['sabri_public_experience_timeline'] = $timeline;
        $GLOBALS['sabri_public_experience_provider_section'] = $provider_section;
    }

    /** @param array<string,string> $parts @return array<string,string> */
    public function document_title_parts(array $parts): array
    {
        if (! $this->router->is_profile_request() || empty($GLOBALS['sabri_public_experience_profile']['display_name'])) {
            return $parts;
        }

        $profile = (array) $GLOBALS['sabri_public_experience_profile'];
        $context = (array) ($GLOBALS['sabri_public_experience_context'] ?? []);
        $section = sanitize_key((string) ($context['section'] ?? 'overview'));
        $labels = (array) ($profile['section_labels'] ?? []);
        $title = (string) $profile['display_name'];
        if ($section !== 'overview' && isset($labels[$section])) {
            $title = (string) $labels[$section] . ' — ' . $title;
        }
        $parts['title'] = $title;

        return $parts;
    }

    /** @param array<string,bool> $robots @return array<string,bool> */
    public function robots(array $robots): array
    {
        if (! $this->router->is_profile_request()) {
            return $robots;
        }

        $filtered = isset($_GET['type']) || (int) get_query_var('paged') > 1;
        $profile = (array) ($GLOBALS['sabri_public_experience_profile'] ?? []);
        $profile_class = sanitize_key((string) ($profile['class'] ?? ''));
        $professional = in_array($profile_class, ['founder', 'doctor'], true);
        $member_indexing = $profile !== [] && (bool) apply_filters(
            'sabri_public_experience/index_nonprofessional_profile',
            false,
            $profile
        );

        if (is_404() || $filtered || (! $professional && ! $member_indexing)) {
            $robots['noindex'] = true;
            $robots['noarchive'] = true;
            $robots['nofollow'] = is_404();
        }

        return $robots;
    }

    public function render_meta(): void
    {
        if (! $this->router->is_profile_request() || is_404() || empty($GLOBALS['sabri_public_experience_profile'])) {
            return;
        }

        $profile = (array) $GLOBALS['sabri_public_experience_profile'];
        $canonical = esc_url((string) ($profile['canonical_url'] ?? ''));
        if ($canonical === '') {
            return;
        }

        $description_source = trim(wp_strip_all_tags((string) ($profile['bio'] ?? '')));
        if ($description_source === '') {
            $description_source = trim((string) ($profile['headline'] ?? $profile['role_label'] ?? ''));
        }
        $description = wp_trim_words($description_source, 30);

        echo '<link rel="canonical" href="' . $canonical . '">' . "\n";
        if ($description !== '') {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        }
        echo '<meta property="og:type" content="profile">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr((string) ($profile['display_name'] ?? '')) . '">' . "\n";
        echo '<meta property="og:url" content="' . $canonical . '">' . "\n";
        if (! empty($profile['avatar_url'])) {
            echo '<meta property="og:image" content="' . esc_url((string) $profile['avatar_url']) . '">' . "\n";
        }

        $profile_class = sanitize_key((string) ($profile['class'] ?? 'member'));
        $entity_type = in_array($profile_class, ['pharmacy', 'institution', 'publisher'], true)
            ? 'Organization'
            : 'Person';
        $entity = [
            '@type' => $entity_type,
            'name' => (string) ($profile['display_name'] ?? ''),
            'url' => (string) ($profile['canonical_url'] ?? ''),
        ];
        if ($description !== '') {
            $entity['description'] = $description;
        }
        if (! empty($profile['avatar_url'])) {
            $entity['image'] = (string) $profile['avatar_url'];
        }
        if (! empty($profile['headline'])) {
            $entity['jobTitle'] = (string) $profile['headline'];
        }
        $location = array_filter([
            'addressLocality' => (string) ($profile['city'] ?? ''),
            'addressCountry' => (string) ($profile['country'] ?? ''),
        ]);
        if ($location !== []) {
            $entity['address'] = array_merge(['@type' => 'PostalAddress'], $location);
        }
        $professional = (array) ($profile['professional'] ?? []);
        if (! empty($professional['languages'])) {
            $entity['knowsLanguage'] = array_values((array) $professional['languages']);
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'url' => (string) ($profile['canonical_url'] ?? ''),
            'mainEntity' => $entity,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'Sabri Social Homeopathy Platform',
                'url' => home_url('/'),
            ],
        ];
        echo '<script type="application/ld+json">'
            . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . '</script>' . "\n";
    }

    private function resolve_user(): ?WP_User
    {
        $context = $this->router->context();
        if ($context['type'] === 'founder') {
            return $this->profiles->get_founder();
        }

        return $this->profiles->find_by_slug($context['slug']);
    }

    private function set_not_found(): void
    {
        global $wp_query;
        $wp_query->set_404();
        status_header(404);
        nocache_headers();
    }
}
