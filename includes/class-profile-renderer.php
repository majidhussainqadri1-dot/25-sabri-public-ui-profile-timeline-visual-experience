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
            $this->set_unavailable($context);
            return;
        }

        // Internal presentation identity only. Profile_Repository intentionally
        // omits raw IDs from its public DTO; the renderer adds the resolved ID
        // after the public projection is authorized so owner actions, metrics,
        // completion tools and privacy previews can bind to the exact user.
        $profile['user_id'] = (int) $user->ID;

        $preview_mode = Plan_Completion::preview_mode($profile);
        $owner_context = get_current_user_id() === (int) $user->ID || current_user_can('manage_options');
        if ($preview_mode === 'denied'
            || (Plan_Completion::preferences()['public_visibility'] !== true && ! $owner_context)
        ) {
            $this->set_unavailable($context);
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
        $profile = Plan_Completion::apply_profile_preferences($profile);
        $available_sections = array_values((array) ($profile['available_sections'] ?? ['overview']));
        $provider_labels = array_intersect_key($provider_labels, array_flip($available_sections));

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
            $this->set_unavailable($context);
            return;
        }

        global $wp_query;
        $wp_query->is_404 = false;
        $wp_query->is_home = false;
        $wp_query->is_archive = false;
        $wp_query->is_singular = true;
        status_header(200);

        $profile['canonical_url'] = Public_URL::sanitize_same_site($canonical, false);
        $context['section'] = $requested_section;
        $context['available_sections'] = $available_sections;
        $context['breadcrumbs'] = $this->breadcrumbs($profile, $context);
        $context['preview_mode'] = $preview_mode;
        $context['preview_urls'] = $preview_mode !== '' ? Plan_Completion::preview_urls($profile) : [];
        $context['preferences'] = Plan_Completion::preferences();

        $timeline_page_size = max(5, min(50, (int) ($context['preferences']['timeline_page_size'] ?? 20)));
        $timeline = [
            'items' => [],
            'page' => 1,
            'per_page' => $timeline_page_size,
            'has_more' => false,
            'truncated' => false,
            'provider_errors' => [],
            'sort' => 'latest',
            'available_metric_sorts' => [],
        ];
        if ($requested_section === 'timeline') {
            $timeline_filters = $this->timeline_filters($profile);
            $content_type = $this->requested_timeline_content_type($timeline_filters);
            $provider_filters = $this->timeline->available_provider_filters();
            $provider = $this->requested_timeline_key('provider', array_keys($provider_filters));
            $year = $this->requested_timeline_year();
            $language = $this->requested_timeline_language();
            $topic = $this->requested_timeline_text('topic', 120);
            $sort = $this->requested_timeline_sort();
            $review_state = $this->requested_timeline_review_state();
            $source_state = $this->requested_timeline_source_state();
            $search_query = '';
            $search_error = '';
            if (isset($_GET['profile_q'])) {
                if (! is_scalar($_GET['profile_q'])) {
                    $search_error = 'invalid_search_query';
                } else {
                    $raw_search = (string) wp_unslash($_GET['profile_q']);
                    $search_query = Plan_Completion::normalize_search_query($raw_search);
                    if ($raw_search !== '' && $search_query === '') {
                        $search_error = 'invalid_search_query';
                    }
                }
            }
            if (Plan_Completion::preferences()['profile_local_search'] !== true) {
                $search_query = '';
                $search_error = isset($_GET['profile_q']) ? 'search_disabled' : '';
            }
            $context['timeline_filters'] = $timeline_filters;
            $context['timeline_content_type'] = $content_type;
            $context['timeline_provider_filters'] = $provider_filters;
            $context['timeline_provider'] = $provider;
            $context['timeline_year'] = $year;
            $context['timeline_language'] = $language;
            $context['timeline_topic'] = $topic;
            $context['timeline_sort'] = $sort;
            $context['timeline_review_state'] = $review_state;
            $context['timeline_source_state'] = $source_state;
            $context['timeline_metric_sorts'] = [];
            $context['timeline_search_query'] = $search_query;
            $context['timeline_search_error'] = $search_error;
            $context['timeline_filtered_request'] = $this->timeline_filter_request_present();
            $timeline = $this->timeline->get_for_author((int) $user->ID, [
                'page' => max(1, (int) get_query_var('paged')),
                'per_page' => $timeline_page_size,
                'content_type' => $content_type,
                'provider' => $provider,
                'year' => $year,
                'language' => $language,
                'topic' => $topic,
                'sort' => $sort,
                'review_state' => $review_state,
                'source_state' => $source_state,
                'search' => $search_query,
            ]);
            $context['timeline_sort'] = (string) ($timeline['sort'] ?? 'latest');
            $context['timeline_metric_sorts'] = array_values(array_filter(
                (array) ($timeline['available_metric_sorts'] ?? []),
                static fn ($value): bool => is_string($value) && in_array($value, ['most_viewed', 'most_saved'], true)
            ));
            $context['timeline_search_result_count'] = count((array) ($timeline['items'] ?? []));
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

        // The Completion Assistant must report missing owner-authored alt text
        // rather than fabricating it. The render template may use an accessible
        // descriptive fallback, but completion evidence stays bound to File 03's
        // canonical public media metadata. An intentionally private contact choice
        // remains a privacy state rather than fabricated public contact data.
        $completion_profile = $profile;
        if (empty($completion_profile['contacts'])) {
            $completion_profile['contacts'] = ['_privacy_state' => 'not-public'];
        }
        $completion_assistant = $preview_mode === ''
            ? Plan_Completion::completion_assistant($completion_profile)
            : [
                'authorized' => false,
                'complete' => false,
                'missing' => [],
                'preview_modes' => [],
                'preview_urls' => [],
                'default_responsive_preview' => '',
                'edit_url' => '',
            ];
        $completion_assistant['contact_privacy_state'] = empty($profile['contacts']) ? 'not-public' : 'public';

        $GLOBALS['sabri_public_experience_profile_user'] = $user;
        $GLOBALS['sabri_public_experience_profile'] = $profile;
        $GLOBALS['sabri_public_experience_context'] = $context;
        $GLOBALS['sabri_public_experience_timeline'] = $timeline;
        $GLOBALS['sabri_public_experience_provider_section'] = $provider_section;
        $GLOBALS['sabri_public_experience_metrics'] = Plan_Completion::public_metrics($profile);
        $GLOBALS['sabri_public_experience_completion_assistant'] = $completion_assistant;
    }

    /** @param array<string,string> $parts @return array<string,string> */
    public function document_title_parts(array $parts): array
    {
        if (! $this->router->is_profile_request() || empty($GLOBALS['sabri_public_experience_profile']['display_name'])) {
            return $parts;
        }

        $profile = (array) $GLOBALS['sabri_public_experience_profile'];
        $context = (array) ($GLOBALS['sabri_public_experience_context'] ?? []);
        $parts['title'] = $this->page_title($profile, $context);

        return $parts;
    }

    /** @param array<string,bool> $robots @return array<string,bool> */
    public function robots(array $robots): array
    {
        if (! $this->router->is_profile_request()) {
            return $robots;
        }

        $context = (array) ($GLOBALS['sabri_public_experience_context'] ?? []);
        $filtered = ! empty($context['timeline_filtered_request'])
            || sanitize_key((string) ($context['timeline_content_type'] ?? '')) !== ''
            || sanitize_key((string) ($context['timeline_provider'] ?? '')) !== ''
            || trim((string) ($context['timeline_year'] ?? '')) !== ''
            || trim((string) ($context['timeline_language'] ?? '')) !== ''
            || trim((string) ($context['timeline_topic'] ?? '')) !== ''
            || trim((string) ($context['timeline_review_state'] ?? '')) !== ''
            || trim((string) ($context['timeline_source_state'] ?? '')) !== ''
            || (string) ($context['timeline_sort'] ?? 'latest') !== 'latest'
            || trim((string) ($context['timeline_search_query'] ?? '')) !== ''
            || (int) get_query_var('paged') > 1
            || trim((string) ($context['preview_mode'] ?? '')) !== '';
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
        if (! $this->router->is_profile_request()
            || is_404()
            || empty($GLOBALS['sabri_public_experience_profile'])
            || Plan_Completion::preferences()['seo_enabled'] !== true
            || ! empty($GLOBALS['sabri_public_experience_context']['preview_mode'])
        ) {
            return;
        }

        $profile = (array) $GLOBALS['sabri_public_experience_profile'];
        $context = (array) ($GLOBALS['sabri_public_experience_context'] ?? []);
        $page_title = $this->page_title($profile, $context);
        $canonical = Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false);
        if ($canonical === '') {
            return;
        }

        $description_source = trim(wp_strip_all_tags((string) ($profile['bio'] ?? '')));
        if ($description_source === '') {
            $description_source = trim((string) ($profile['headline'] ?? $profile['role_label'] ?? ''));
        }
        $description = wp_trim_words($description_source, 30);

        echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
        if ($description !== '') {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
            echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
        }
        echo '<meta property="og:type" content="profile">' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($page_title) . '">' . "\n";
        echo '<meta property="og:url" content="' . esc_url($canonical) . '">' . "\n";
        $avatar = Public_URL::sanitize_same_site($profile['avatar_url'] ?? '', false);
        if ($avatar !== '') {
            $avatar_alt = trim((string) ($profile['avatar_alt'] ?? ''));
            if ($avatar_alt === '') {
                $avatar_alt = sprintf(
                    __('%s profile photograph', 'sabri-public-experience'),
                    (string) ($profile['display_name'] ?? '')
                );
            }
            echo '<meta property="og:image" content="' . esc_url($avatar) . '">' . "\n";
            echo '<meta property="og:image:alt" content="' . esc_attr($avatar_alt) . '">' . "\n";
        }

        $profile_class = sanitize_key((string) ($profile['class'] ?? 'member'));
        $entity_type = in_array($profile_class, ['pharmacy', 'institution', 'publisher'], true)
            ? 'Organization'
            : 'Person';
        $entity = [
            '@type' => $entity_type,
            'name' => (string) ($profile['display_name'] ?? ''),
            'url' => $canonical,
        ];
        if ($description !== '') {
            $entity['description'] = $description;
        }
        if ($avatar !== '') {
            $entity['image'] = $avatar;
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
            'name' => $page_title,
            'url' => $canonical,
            'mainEntity' => $entity,
            'isPartOf' => [
                '@type' => 'WebSite',
                'name' => 'Sabri Social Homeopathy Platform',
                'url' => home_url('/'),
            ],
            'breadcrumb' => [
                '@type' => 'BreadcrumbList',
                'itemListElement' => array_map(
                    static fn (array $item, int $index): array => [
                        '@type' => 'ListItem',
                        'position' => $index + 1,
                        'name' => (string) $item['label'],
                        'item' => (string) $item['url'],
                    ],
                    $this->breadcrumbs($profile, $context),
                    array_keys($this->breadcrumbs($profile, $context))
                ),
            ],
        ];
        echo '<script type="application/ld+json">'
            . wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . '</script>' . "\n";
    }

    /** @param array<string,mixed> $profile @return array<string,string> */
    private function timeline_filters(array $profile): array
    {
        $base = [
            '' => __('All', 'sabri-public-experience'),
            'post' => __('Posts', 'sabri-public-experience'),
            'corrections' => __('Corrections', 'sabri-public-experience'),
        ];
        $requested = (array) apply_filters(
            'sabri_public_experience/timeline_filters',
            $base,
            $profile
        );
        $filters = [];
        foreach (array_slice($requested, 0, 20, true) as $type => $label) {
            if (! is_scalar($type) || ! is_scalar($label)) {
                continue;
            }
            $raw_type = (string) $type;
            $type = sanitize_key($raw_type);
            $label = sanitize_text_field((string) $label);
            if ($label === ''
                || ($type !== ''
                    && ($type !== $raw_type || preg_match('/^[a-z0-9_-]{1,64}$/', $type) !== 1))
            ) {
                continue;
            }
            $filters[$type] = $label;
        }
        if (! isset($filters[''])) {
            $filters = ['' => __('All', 'sabri-public-experience')] + $filters;
        }

        return $filters;
    }

    /** @param array<string,string> $filters */
    private function requested_timeline_content_type(array $filters): string
    {
        if (! isset($_GET['type'])) {
            $default = sanitize_key((string) (Plan_Completion::preferences()['default_timeline_filter'] ?? ''));
            return $default !== '' && array_key_exists($default, $filters) ? $default : '';
        }
        if (! is_scalar($_GET['type'])) {
            return '';
        }
        $raw = (string) wp_unslash($_GET['type']);
        if ($raw === '') {
            return '';
        }
        $requested = sanitize_key($raw);
        if ($requested === '' || $requested !== $raw || strlen($requested) > 64) {
            return '';
        }

        return array_key_exists($requested, $filters) ? $requested : '';
    }

    private function timeline_filter_request_present(): bool
    {
        foreach (['type', 'provider', 'year', 'language', 'topic', 'sort', 'review_state', 'source_state', 'profile_q'] as $key) {
            if (array_key_exists($key, $_GET)) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $allowed */
    private function requested_timeline_key(string $query_key, array $allowed): string
    {
        if (! isset($_GET[$query_key]) || ! is_scalar($_GET[$query_key])) {
            return '';
        }
        $raw = (string) wp_unslash($_GET[$query_key]);
        if ($raw === '') {
            return '';
        }
        $key = sanitize_key($raw);
        if ($key === '' || $key !== $raw || strlen($key) > 64) {
            return '';
        }

        return in_array($key, $allowed, true) ? $key : '';
    }

    private function requested_timeline_year(): string
    {
        if (! isset($_GET['year']) || ! is_scalar($_GET['year'])) {
            return '';
        }
        $value = (string) wp_unslash($_GET['year']);
        if ($value === '') {
            return '';
        }
        if (preg_match('/^(?:19|20|21)\\d{2}$/', $value) !== 1) {
            return '';
        }
        $year = (int) $value;

        return $year >= 1900 && $year <= ((int) gmdate('Y') + 1) ? $value : '';
    }

    private function requested_timeline_language(): string
    {
        if (! isset($_GET['language']) || ! is_scalar($_GET['language'])) {
            return '';
        }
        $value = trim((string) wp_unslash($_GET['language']));
        if ($value === '' || strlen($value) > 35 || preg_match('/^[A-Za-z]{2,8}(?:-[A-Za-z0-9]{1,8})*$/', $value) !== 1) {
            return '';
        }

        return $value;
    }

    private function requested_timeline_text(string $query_key, int $maximum): string
    {
        if (! isset($_GET[$query_key]) || ! is_scalar($_GET[$query_key])) {
            return '';
        }
        $value = trim(wp_strip_all_tags((string) wp_unslash($_GET[$query_key])));
        if ($value === '' || strlen($value) > $maximum || preg_match('/[\\x00-\\x1F\\x7F]/', $value) === 1) {
            return '';
        }

        return $value;
    }

    private function requested_timeline_sort(): string
    {
        if (! isset($_GET['sort']) || ! is_scalar($_GET['sort'])) {
            return 'latest';
        }
        $value = (string) wp_unslash($_GET['sort']);

        return in_array($value, ['latest', 'oldest', 'most_viewed', 'most_saved'], true) ? $value : 'latest';
    }

    private function requested_timeline_review_state(): string
    {
        if (! isset($_GET['review_state']) || ! is_scalar($_GET['review_state'])) {
            return '';
        }
        $value = (string) wp_unslash($_GET['review_state']);

        return in_array($value, ['published', 'approved', 'reviewed', 'not-required', 'corrected', 'retracted'], true)
            ? $value
            : '';
    }

    private function requested_timeline_source_state(): string
    {
        if (! isset($_GET['source_state']) || ! is_scalar($_GET['source_state'])) {
            return '';
        }
        $value = (string) wp_unslash($_GET['source_state']);

        return in_array($value, ['verified', 'unverified'], true) ? $value : '';
    }

    private function resolve_user(): ?WP_User
    {
        $context = $this->router->context();
        if ($context['type'] === 'founder') {
            return $this->profiles->get_founder();
        }

        return $this->profiles->find_by_slug($context['slug']);
    }

    /** @param array<string,mixed> $profile @param array<string,mixed> $context */
    private function page_title(array $profile, array $context): string
    {
        $name = trim(wp_strip_all_tags((string) ($profile['display_name'] ?? '')));
        $section = sanitize_key((string) ($context['section'] ?? 'overview')) ?: 'overview';
        $labels = (array) ($profile['section_labels'] ?? []);
        if ($section === 'overview' || ! isset($labels[$section]) || ! is_scalar($labels[$section])) {
            return $name;
        }

        $label = trim(wp_strip_all_tags((string) $labels[$section]));

        return $label !== '' && $name !== '' ? $label . ' — ' . $name : $name;
    }

    /** @param array<string,mixed> $profile @param array<string,mixed> $context @return list<array{label:string,url:string}> */
    private function breadcrumbs(array $profile, array $context): array
    {
        $canonical = Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false);
        $section = sanitize_key((string) ($context['section'] ?? 'overview')) ?: 'overview';
        $labels = (array) ($profile['section_labels'] ?? []);
        $profile_base = $canonical;
        if ($section !== 'overview') {
            $suffix = '/' . $section . '/';
            if (str_ends_with($profile_base, $suffix)) {
                $profile_base = substr($profile_base, 0, -strlen($suffix) + 1);
            }
        }
        $profile_base = Public_URL::sanitize_same_site($profile_base, false);

        $items = [
            ['label' => __('Home', 'sabri-public-experience'), 'url' => home_url('/')],
            ['label' => (string) ($profile['display_name'] ?? ''), 'url' => $profile_base !== '' ? $profile_base : $canonical],
        ];
        if ($section !== 'overview' && isset($labels[$section])) {
            $items[] = ['label' => (string) $labels[$section], 'url' => $canonical];
        }

        return array_values(array_filter($items, static function (array $item): bool {
            return $item['label'] !== '' && Public_URL::sanitize_same_site($item['url'], false) !== '';
        }));
    }

    /** @param array<string,mixed> $context */
    private function set_unavailable(array $context): void
    {
        $status = (int) apply_filters(
            'sabri_public_experience/missing_profile_status',
            404,
            $context
        );
        if ($status !== 410) {
            $status = 404;
        }

        global $wp_query;
        $wp_query->set_404();
        status_header($status);
        nocache_headers();
    }
}
