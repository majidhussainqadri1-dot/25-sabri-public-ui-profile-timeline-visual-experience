<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Founder-approved File 25 Future Public Experience Superset (24 enhancements). */
final class Future_Public_Experience
{
    public const CONTRACT_VERSION = '1.0.0';

    /** @var array<string,array<string,string>> */
    private const FEATURES = [
        'F25-FUT-01' => ['name' => 'Adaptive Public Experience Engine', 'mode' => 'container-responsive'],
        'F25-FUT-02' => ['name' => 'Profile Trust Capsule', 'mode' => 'native-facts-only'],
        'F25-FUT-03' => ['name' => 'Public Provenance Badges', 'mode' => 'native-facts-only'],
        'F25-FUT-04' => ['name' => 'Timeline Time Navigator', 'mode' => 'profile-local-only'],
        'F25-FUT-05' => ['name' => 'Timeline Era Summaries', 'mode' => 'page-local-or-provider'],
        'F25-FUT-06' => ['name' => 'Knowledge Relationship Explorer', 'mode' => 'read-only-provider'],
        'F25-FUT-07' => ['name' => 'Profile Reading Mode', 'mode' => 'browser-local'],
        'F25-FUT-08' => ['name' => 'Accessibility Personalization Layer', 'mode' => 'browser-local'],
        'F25-FUT-09' => ['name' => 'Low Data Mode', 'mode' => 'browser-local'],
        'F25-FUT-10' => ['name' => 'Progressive Enhancement and No-JS Excellence', 'mode' => 'server-first-core'],
        'F25-FUT-11' => ['name' => 'Instant Back Forward Restoration', 'mode' => 'session-only'],
        'F25-FUT-12' => ['name' => 'Elegant View Transitions', 'mode' => 'progressive'],
        'F25-FUT-13' => ['name' => 'Smart Action Sheet', 'mode' => 'native-actions-only'],
        'F25-FUT-14' => ['name' => 'Universal Profile Share Studio', 'mode' => 'tracking-free'],
        'F25-FUT-15' => ['name' => 'Public Profile Snapshot Card', 'mode' => 'public-safe'],
        'F25-FUT-16' => ['name' => 'Profile Translation Switcher', 'mode' => 'approved-provider'],
        'F25-FUT-17' => ['name' => 'Bilingual Side by Side Reading', 'mode' => 'approved-provider'],
        'F25-FUT-18' => ['name' => 'Public Citation and Reference Drawer', 'mode' => 'read-only-provider'],
        'F25-FUT-19' => ['name' => 'Contextual Profile Mini Cards', 'mode' => 'public-safe'],
        'F25-FUT-20' => ['name' => 'Profile Visual Integrity Monitor', 'mode' => 'local-event-only'],
        'F25-FUT-21' => ['name' => 'Content Freshness UX', 'mode' => 'native-timestamps'],
        'F25-FUT-22' => ['name' => 'Privacy Preview Simulator', 'mode' => 'owner-admin-only'],
        'F25-FUT-23' => ['name' => 'Visual State Component Laboratory', 'mode' => 'admin-only'],
        'F25-FUT-24' => ['name' => 'Public Experience Quality Score', 'mode' => 'owner-admin-only'],
    ];

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'owner' => 'file-25',
            'shell_owner' => 'file-20',
            'security_governance_owner' => 'file-24',
            'global_search_discovery_ranking_owner' => 'file-26',
            'profile_data_owner' => 'file-03',
            'membership_identity_owner' => 'file-00',
            'doctor_verification_owner' => 'file-09',
            'publication_owner' => 'file-21',
            'composer_owner' => 'file-22',
            'publishing_operations_owner' => 'file-23',
            'future_enhancements' => self::FEATURES,
            'feature_count' => count(self::FEATURES),
            'duplicate_backend_allowed' => false,
            'raw_sensitive_data_allowed' => false,
            'raw_analytics_warehouse_allowed' => false,
            'third_party_tracking_qr_allowed' => false,
            'client_preferences_are_authorization' => false,
            'no_js_core_required' => true,
            'native_action_execution_required' => true,
        ];
    }

    /** @param array<string,mixed> $contract @return array<string,mixed> */
    public static function filter_contract(array $contract): array
    {
        $contract['future_public_experience'] = self::contract();
        return $contract;
    }

    public function register(): void
    {
        add_filter('sabri_visual_experience/contract', [self::class, 'filter_contract'], 40);
        add_filter('sabri_public_experience/design_system_contract', [self::class, 'filter_contract'], 40);
        add_filter('body_class', [$this, 'body_classes'], 40);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 220);
        add_action('admin_menu', [$this, 'register_component_lab'], 40);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 40);
        add_action('wp_footer', [$this, 'render_public_payload'], 5);
    }

    /** @param list<string> $classes @return list<string> */
    public function body_classes(array $classes): array
    {
        if (! empty($GLOBALS['sabri_public_experience_profile'])) {
            $classes[] = 'spux-future-public-experience';
            $classes[] = 'spux-progressive-enhancement';
        }
        return array_values(array_unique($classes));
    }

    public function enqueue_assets(): void
    {
        if (! Design_System::should_enqueue() || empty($GLOBALS['sabri_public_experience_profile'])) {
            return;
        }
        wp_enqueue_style(
            'sabri-public-experience-future',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/css/future-public-experience.css',
            ['sabri-visual-design-system'],
            SABRI_PUBLIC_EXPERIENCE_VERSION
        );
        wp_enqueue_script(
            'sabri-public-experience-future',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/js/future-public-experience.js',
            ['sabri-public-experience'],
            SABRI_PUBLIC_EXPERIENCE_VERSION,
            true
        );
        wp_script_add_data('sabri-public-experience-future', 'strategy', 'defer');
        wp_localize_script('sabri-public-experience-future', 'sabriFuturePublicExperience', [
            'preferenceUpdated' => __('Display preference updated.', 'sabri-public-experience'),
            'preferencesReset' => __('Display preferences reset.', 'sabri-public-experience'),
            'allYears' => __('All years', 'sabri-public-experience'),
            'timelineYears' => __('Timeline years', 'sabri-public-experience'),
            'eraSummary' => __('Timeline era summary', 'sabri-public-experience'),
            'sourceVerified' => __('Verified source projection', 'sabri-public-experience'),
            'updatedLabel' => __('Updated', 'sabri-public-experience'),
            'actionsLabel' => __('Profile actions', 'sabri-public-experience'),
            'toolbarLabel' => __('Reading and accessibility preferences', 'sabri-public-experience'),
            'readingMode' => __('Reading mode', 'sabri-public-experience'),
            'largerText' => __('Larger text', 'sabri-public-experience'),
            'highContrast' => __('High contrast', 'sabri-public-experience'),
            'moreSpacing' => __('More spacing', 'sabri-public-experience'),
            'simplifiedView' => __('Simplified view', 'sabri-public-experience'),
            'lowData' => __('Low data', 'sabri-public-experience'),
            'reset' => __('Reset', 'sabri-public-experience'),
            'privacyPreview' => __('Privacy preview simulator', 'sabri-public-experience'),
            'publicVisitor' => __('Public visitor', 'sabri-public-experience'),
            'loggedInMember' => __('Logged-in member', 'sabri-public-experience'),
            'contactPresentation' => __('Contact presentation', 'sabri-public-experience'),
            'searchPresentation' => __('Search-result presentation', 'sabri-public-experience'),
            'socialPresentation' => __('Social-preview presentation', 'sabri-public-experience'),
            'mobilePresentation' => __('Mobile presentation', 'sabri-public-experience'),
            'desktopPresentation' => __('Desktop presentation', 'sabri-public-experience'),
            'previewDisclaimer' => __('Presentation simulation only; it never grants another role or bypasses authorization.', 'sabri-public-experience'),
            'qualityLabel' => __('Public Experience Quality', 'sabri-public-experience'),
            'passLabel' => __('Pass', 'sabri-public-experience'),
            'reviewLabel' => __('Review', 'sabri-public-experience'),
            'qualityDisclaimer' => __('Internal quality aid only; it has no public ranking, donation, verification, or visibility effect.', 'sabri-public-experience'),
            'trustAriaLabel' => __('Profile trust information', 'sabri-public-experience'),
            'verifiedPrefix' => __('Verified', 'sabri-public-experience'),
            'shareStudio' => __('Share Studio', 'sabri-public-experience'),
            'copyCleanLink' => __('Copy clean link', 'sabri-public-experience'),
            'printProfileCard' => __('Print profile card', 'sabri-public-experience'),
            'translations' => __('Translations', 'sabri-public-experience'),
            'sideBySide' => __('Side by side', 'sabri-public-experience'),
            'translationNote' => __('Translations are provider-supplied presentation; the original profile remains authoritative.', 'sabri-public-experience'),
            'knowledgeRelationships' => __('Knowledge Relationships', 'sabri-public-experience'),
            'relatedItem' => __('Related item', 'sabri-public-experience'),
            'referencesCitations' => __('References and citations', 'sabri-public-experience'),
            'reference' => __('Reference', 'sabri-public-experience'),
            'publicInformationUpdated' => __('Public information updated', 'sabri-public-experience'),
            'itemsShownForYear' => __('public items shown for', 'sabri-public-experience'),
            'itemsShownOnPage' => __('public items shown on this page.', 'sabri-public-experience'),
            'itemsOnThisPage' => __('public items on this page', 'sabri-public-experience'),
            'linkCopied' => __('Link copied.', 'sabri-public-experience'),
            'copyFailed' => __('Unable to copy the clean profile link.', 'sabri-public-experience'),
        ]);
    }

    public function enqueue_admin_assets(): void
    {
        $page = isset($_GET['page']) && is_scalar($_GET['page']) ? sanitize_key((string) wp_unslash($_GET['page'])) : '';
        if ($page !== 'sabri-public-experience-component-lab') {
            return;
        }
        wp_enqueue_style(
            'sabri-public-experience-future-admin',
            SABRI_PUBLIC_EXPERIENCE_URL . 'assets/css/future-public-experience.css',
            [],
            SABRI_PUBLIC_EXPERIENCE_VERSION
        );
    }

    public function register_component_lab(): void
    {
        add_submenu_page(
            'sabri-public-experience',
            __('File 25 Component Laboratory', 'sabri-public-experience'),
            __('Component Laboratory', 'sabri-public-experience'),
            'manage_options',
            'sabri-public-experience-component-lab',
            [$this, 'render_component_lab']
        );
    }

    public function render_component_lab(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to access the File 25 component laboratory.', 'sabri-public-experience'));
        }
        ?>
        <div class="wrap spux-component-lab">
            <h1><?php esc_html_e('File 25 — Visual State Component Laboratory', 'sabri-public-experience'); ?></h1>
            <p><?php esc_html_e('Internal-only visual reference. It never creates native content or bypasses native authorization.', 'sabri-public-experience'); ?></p>
            <p><button type="button" class="button button-primary"><?php esc_html_e('Primary', 'sabri-public-experience'); ?></button> <button type="button" class="button"><?php esc_html_e('Secondary', 'sabri-public-experience'); ?></button> <span class="spux-badge"><?php esc_html_e('Verified', 'sabri-public-experience'); ?></span> <span class="spux-badge spux-badge--corrected"><?php esc_html_e('Corrected', 'sabri-public-experience'); ?></span></p>
            <div class="spux-trust-capsule"><strong><?php esc_html_e('Trust capsule sample', 'sabri-public-experience'); ?></strong><span><?php esc_html_e('Native facts only', 'sabri-public-experience'); ?></span></div>
            <pre><?php echo esc_html((string) wp_json_encode(self::contract(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)); ?></pre>
        </div>
        <?php
    }

    public function render_public_payload(): void
    {
        $profile = (array) ($GLOBALS['sabri_public_experience_profile'] ?? []);
        if ($profile === []) {
            return;
        }
        $context = (array) ($GLOBALS['sabri_public_experience_context'] ?? []);
        $timeline = (array) ($GLOBALS['sabri_public_experience_timeline'] ?? []);
        $profile_id = self::profile_id($profile);
        $owner_tools = self::owner_or_admin($profile_id);
        $payload = [
            'contract_version' => self::CONTRACT_VERSION,
            'section' => sanitize_key((string) ($context['section'] ?? 'overview')) ?: 'overview',
            'profile' => self::public_profile_payload($profile),
            'trust' => self::trust_payload($profile),
            'translations' => self::translations($profile),
            'relationships' => self::relationships($profile),
            'citations' => self::citations($profile, $context),
            'freshness' => self::freshness($profile),
            'timeline' => self::timeline_meta($timeline),
            'owner_tools' => $owner_tools,
            'preview_links' => $owner_tools ? self::preview_links($profile, $context) : [],
            'quality' => $owner_tools ? self::quality_report($profile) : [],
            'integrity' => $owner_tools ? self::integrity_report($profile) : [],
            'public_ranking_effect' => false,
        ];
        echo '<script type="application/json" id="spux-future-public-payload">'
            . wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)
            . '</script>';
    }

    /** @param array<string,mixed> $profile @return array<string,mixed> */
    private static function public_profile_payload(array $profile): array
    {
        return [
            'name' => self::plain($profile['display_name'] ?? '', 190),
            'headline' => self::plain($profile['headline'] ?? '', 300),
            'role_label' => self::plain($profile['role_label'] ?? '', 190),
            'class' => sanitize_key((string) ($profile['class'] ?? 'member')),
            'verified' => ($profile['verified'] ?? null) === true,
            'url' => Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false),
            'avatar' => Public_URL::sanitize_same_site($profile['avatar_url'] ?? '', false),
            'city' => self::plain($profile['city'] ?? '', 100),
            'country' => self::plain($profile['country'] ?? '', 100),
        ];
    }

    /** @param array<string,mixed> $profile @return array<string,string|bool> */
    private static function trust_payload(array $profile): array
    {
        $default = [
            'verified' => ($profile['verified'] ?? null) === true,
            'label' => self::plain($profile['role_label'] ?? __('Public profile', 'sabri-public-experience'), 190),
            'source_label' => in_array((string) ($profile['class'] ?? ''), ['founder', 'doctor'], true)
                ? __('Institutional verification source', 'sabri-public-experience')
                : __('Public profile source', 'sabri-public-experience'),
            'verified_at' => '',
            'freshness' => '',
            'correction_state' => 'none',
        ];
        $data = apply_filters('sabri_public_experience/profile_trust_capsule', $default, $profile);
        $data = is_array($data) ? $data : $default;
        $state = sanitize_key((string) ($data['correction_state'] ?? 'none'));
        if (! in_array($state, ['none', 'corrected', 'retracted', 'archived'], true)) {
            $state = 'none';
        }
        $verified = $default['verified'] === true;
        if (array_key_exists('verified', $data)) {
            $verified = $verified && $data['verified'] === true;
        }
        return [
            'verified' => $verified,
            'label' => self::plain($data['label'] ?? $default['label'], 190),
            'source_label' => self::plain($data['source_label'] ?? $default['source_label'], 190),
            'verified_at' => $verified ? self::iso_time((string) ($data['verified_at'] ?? '')) : '',
            'freshness' => self::plain($data['freshness'] ?? '', 190),
            'correction_state' => $state,
        ];
    }

    /** @param array<string,mixed> $profile @return list<array<string,string>> */
    private static function translations(array $profile): array
    {
        $data = apply_filters('sabri_public_experience/profile_translations', [], $profile);
        if (! is_array($data)) {
            return [];
        }
        $clean = [];
        foreach (array_slice($data, 0, 8, true) as $code => $row) {
            if (! is_string($code) || ! is_array($row)) {
                continue;
            }
            $code = strtolower(trim($code));
            if (preg_match('/^[a-z]{2,3}(?:-[a-z]{2})?$/', $code) !== 1) {
                continue;
            }
            $label = self::plain($row['label'] ?? strtoupper($code), 80);
            $headline = self::plain($row['headline'] ?? '', 300);
            $bio = self::plain($row['bio'] ?? '', 12000);
            if ($label !== '' && ($headline !== '' || $bio !== '')) {
                $clean[] = ['code' => $code, 'label' => $label, 'headline' => $headline, 'bio' => $bio];
            }
        }
        return $clean;
    }

    /** @param array<string,mixed> $profile @return list<array<string,string>> */
    private static function relationships(array $profile): array
    {
        $data = apply_filters('sabri_public_experience/public_relationships', [], $profile);
        if (! is_array($data)) {
            return [];
        }
        $clean = [];
        foreach (array_slice($data, 0, 40) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $label = self::plain($row['label'] ?? '', 140);
            $url = Public_URL::sanitize_same_site($row['url'] ?? '', false);
            if ($label === '' || $url === '') {
                continue;
            }
            $clean[] = [
                'label' => $label,
                'type' => sanitize_key((string) ($row['type'] ?? 'related')) ?: 'related',
                'relationship' => self::plain($row['relationship'] ?? '', 100),
                'url' => $url,
            ];
        }
        return $clean;
    }

    /** @param array<string,mixed> $profile @param array<string,mixed> $context @return list<array<string,string>> */
    private static function citations(array $profile, array $context): array
    {
        $data = apply_filters('sabri_public_experience/public_citations', [], $profile, $context);
        if (! is_array($data)) {
            return [];
        }
        $clean = [];
        foreach (array_slice($data, 0, 40) as $row) {
            if (! is_array($row)) {
                continue;
            }
            $title = self::plain($row['title'] ?? '', 240);
            $url = Public_URL::sanitize_same_site($row['url'] ?? '', false);
            if ($title !== '' && $url !== '') {
                $clean[] = ['title' => $title, 'source' => self::plain($row['source'] ?? '', 240), 'url' => $url];
            }
        }
        return $clean;
    }

    /** @param array<string,mixed> $profile @return array<string,string> */
    private static function freshness(array $profile): array
    {
        $data = apply_filters('sabri_public_experience/content_freshness', [], $profile);
        if (! is_array($data)) {
            return [];
        }
        $updated = self::iso_time((string) ($data['updated_at'] ?? ''));
        if ($updated === '') {
            return [];
        }
        return ['updated_at' => $updated, 'label' => self::plain($data['label'] ?? '', 190)];
    }

    /** @param array<string,mixed> $timeline @return list<array<string,mixed>> */
    private static function timeline_meta(array $timeline): array
    {
        $clean = [];
        foreach (array_slice((array) ($timeline['items'] ?? []), 0, 100) as $item) {
            if (! is_array($item)) {
                continue;
            }
            $url = Public_URL::sanitize_same_site($item['canonical_url'] ?? '', false);
            if ($url === '') {
                continue;
            }
            $state = sanitize_key((string) ($item['correction_state'] ?? 'none'));
            $clean[] = [
                'url' => $url,
                'source_verified' => sanitize_key((string) ($item['provider_id'] ?? '')) !== '',
                'correction_state' => in_array($state, ['corrected', 'retracted'], true) ? $state : 'none',
                'updated_at' => self::iso_time((string) ($item['updated_at'] ?? $item['published_at'] ?? '')),
            ];
        }
        return $clean;
    }

    /** @param array<string,mixed> $profile @param array<string,mixed> $context @return array<string,string> */
    private static function preview_links(array $profile, array $context): array
    {
        unset($context);
        $canonical = Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false);
        if ($canonical === '') {
            return [];
        }
        $links = [];
        foreach (['public', 'member', 'contact', 'search', 'social', 'mobile', 'desktop'] as $mode) {
            $url = Public_URL::sanitize_same_site(add_query_arg('spux_preview', $mode, $canonical), false);
            if ($url !== '') {
                $links[$mode] = $url;
            }
        }
        return $links;
    }

    /** @param array<string,mixed> $profile @return array<string,mixed> */
    public static function quality_report(array $profile): array
    {
        $checks = [
            'canonical_url' => Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false) !== '',
            'display_name' => self::plain($profile['display_name'] ?? '', 190) !== '',
            'headline' => self::plain($profile['headline'] ?? '', 300) !== '',
            'biography' => self::plain($profile['bio'] ?? '', 12000) !== '',
            'avatar_or_initials' => true,
            'privacy_projection' => is_array($profile['contacts'] ?? []),
            'section_contract' => is_array($profile['available_sections'] ?? []),
        ];
        if (in_array((string) ($profile['class'] ?? ''), ['founder', 'doctor'], true)) {
            $checks['institutional_verification'] = ($profile['verified'] ?? null) === true;
        }
        $extra = apply_filters('sabri_public_experience/quality_checks', [], $profile);
        if (is_array($extra)) {
            foreach (array_slice($extra, 0, 20, true) as $key => $pass) {
                $key = is_string($key) ? sanitize_key($key) : '';
                if ($key !== '' && is_bool($pass) && ! array_key_exists($key, $checks)) {
                    $checks[$key] = $pass;
                }
            }
        }
        $total = max(1, count($checks));
        $passed = count(array_filter($checks, static fn ($value): bool => $value === true));
        return [
            'score' => (int) round(($passed / $total) * 100),
            'passed' => $passed,
            'total' => $total,
            'checks' => $checks,
            'public_ranking_effect' => false,
        ];
    }

    /** @param array<string,mixed> $profile @return array<string,mixed> */
    public static function integrity_report(array $profile): array
    {
        $issues = [];
        if (self::plain($profile['display_name'] ?? '', 190) === '') {
            $issues[] = 'missing_display_name';
        }
        if (Public_URL::sanitize_same_site($profile['canonical_url'] ?? '', false) === '') {
            $issues[] = 'invalid_canonical_url';
        }
        foreach (['avatar_url', 'cover_url'] as $field) {
            if (! empty($profile[$field]) && Public_URL::sanitize_same_site($profile[$field], false) === '') {
                $issues[] = 'unsafe_' . $field;
            }
        }
        return ['status' => $issues === [] ? 'pass' : 'review', 'issue_count' => count($issues), 'issues' => $issues, 'scope' => 'file-25-public-presentation-only'];
    }

    /** @return array{year:int,error:string} */
    public static function timeline_year_request(): array
    {
        if (! isset($_GET['spux_year'])) {
            return ['year' => 0, 'error' => ''];
        }
        if (! is_scalar($_GET['spux_year'])) {
            return ['year' => 0, 'error' => 'invalid_year'];
        }
        $raw = (string) wp_unslash($_GET['spux_year']);
        if (preg_match('/^[12][0-9]{3}$/', $raw) !== 1) {
            return ['year' => 0, 'error' => 'invalid_year'];
        }
        $year = (int) $raw;
        return $year >= 1900 && $year <= (int) gmdate('Y') + 1
            ? ['year' => $year, 'error' => '']
            : ['year' => 0, 'error' => 'invalid_year'];
    }

    /** Public-safe component API for contextual mini cards. @param array<string,mixed> $profile */
    public static function render_profile_mini_card(array $profile): string
    {
        $data = self::public_profile_payload($profile);
        if ($data['name'] === '' || $data['url'] === '') {
            return '';
        }
        $html = '<article class="spux-profile-mini-card" data-spux-mini-card>';
        if ($data['avatar'] !== '') {
            $html .= '<img src="' . esc_url((string) $data['avatar']) . '" alt="" width="64" height="64" loading="lazy">';
        }
        $html .= '<div><strong><a href="' . esc_url((string) $data['url']) . '">' . esc_html((string) $data['name']) . '</a></strong>';
        if ($data['verified'] === true) {
            $html .= '<span class="spux-badge">' . esc_html__('Verified', 'sabri-public-experience') . '</span>';
        }
        if ($data['headline'] !== '') {
            $html .= '<p>' . esc_html((string) $data['headline']) . '</p>';
        }
        $html .= '</div></article>';
        return $html;
    }

    /** @param array<string,mixed> $profile */
    private static function profile_id(array $profile): int
    {
        $id = self::positive_int($profile['user_id'] ?? 0);
        if ($id > 0) {
            return $id;
        }
        $user = $GLOBALS['sabri_public_experience_profile_user'] ?? null;
        return is_object($user) && isset($user->ID) ? self::positive_int($user->ID) : 0;
    }

    private static function owner_or_admin(int $profile_id): bool
    {
        if (function_exists('current_user_can') && current_user_can('manage_options')) {
            return true;
        }
        return $profile_id > 0 && function_exists('get_current_user_id') && get_current_user_id() === $profile_id;
    }

    private static function positive_int(mixed $value): int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : 0;
        }
        if (! is_string($value) || preg_match('/^[1-9][0-9]{0,18}$/', $value) !== 1) {
            return 0;
        }
        $integer = (int) $value;
        return $integer > 0 && (string) $integer === $value ? $integer : 0;
    }

    private static function plain(mixed $value, int $limit): string
    {
        if (! is_scalar($value)) {
            return '';
        }
        $text = function_exists('wp_strip_all_tags') ? wp_strip_all_tags((string) $value, true) : strip_tags((string) $value);
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
        if ($text === '') {
            return '';
        }
        $limit = max(1, min(12000, $limit));
        return function_exists('mb_substr') ? mb_substr($text, 0, $limit) : substr($text, 0, $limit);
    }

    private static function iso_time(string $value): string
    {
        $value = trim($value);
        if ($value === '' || strlen($value) > 40) {
            return '';
        }
        $utc = new \DateTimeZone('UTC');
        $formats = [
            ['!Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:s\Z', $utc],
            ['!Y-m-d\TH:i:s.uP', 'Y-m-d\TH:i:s.uP', null],
            ['!Y-m-d\TH:i:sP', 'Y-m-d\TH:i:sP', null],
            ['!Y-m-d H:i:s', 'Y-m-d H:i:s', $utc],
        ];
        foreach ($formats as [$parse, $round_trip, $timezone]) {
            $date = \DateTimeImmutable::createFromFormat($parse, $value, $timezone);
            $errors = \DateTimeImmutable::getLastErrors();
            if (! $date instanceof \DateTimeImmutable
                || (is_array($errors) && ((int) ($errors['warning_count'] ?? 0) > 0 || (int) ($errors['error_count'] ?? 0) > 0))
                || $date->format($round_trip) !== $value
            ) {
                continue;
            }
            return $date->format('c');
        }
        return '';
    }
}
