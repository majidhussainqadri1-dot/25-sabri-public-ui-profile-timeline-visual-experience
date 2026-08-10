<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Canonical design-system contract for File 25. */
final class Design_System
{
    public const CONTRACT_VERSION = '1.9.0';
    public const CANONICAL_NAME = 'Sabri Unified Global Visual Experience and Design System';
    public const SUBTITLE = 'Complete Public UI, Profile Timeline, Responsive Refinement and Visual Consistency';

    public function register(): void
    {
        add_filter('body_class', [$this, 'body_classes'], 5);
        add_filter('sabri_visual_experience/contract', [self::class, 'filter_contract']);
        add_filter('sabri_public_experience/design_system_contract', [self::class, 'filter_contract']);
        add_filter('sabri_visual_experience/tokens', [self::class, 'filter_tokens']);
        add_filter('sabri_visual_experience/components', [self::class, 'filter_components']);
        add_filter('sabri_visual_experience/content_cards', [Content_Cards::class, 'filter_contract']);
        add_filter('sabri_visual_experience/acceptance', [Visual_Acceptance::class, 'filter_contract']);
    }

    /** @param list<string> $classes @return list<string> */
    public function body_classes(array $classes): array
    {
        if (! self::should_enqueue()) {
            return $classes;
        }

        $classes[] = 'sabri-visual-system';
        $classes[] = 'sabri-visual-system-v1';
        $classes[] = function_exists('is_rtl') && is_rtl()
            ? 'sabri-visual-direction-rtl'
            : 'sabri-visual-direction-ltr';
        $classes[] = self::shell_is_available()
            ? 'sabri-visual-shell-connected'
            : 'sabri-visual-shell-degraded';

        return array_values(array_unique($classes));
    }

    public static function should_enqueue(): bool
    {
        if (function_exists('is_admin') && is_admin()) {
            return false;
        }
        if (function_exists('wp_doing_ajax') && wp_doing_ajax()) {
            return false;
        }
        if (function_exists('wp_doing_cron') && wp_doing_cron()) {
            return false;
        }
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return false;
        }
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return false;
        }

        foreach (['is_feed', 'is_embed', 'is_robots', 'is_favicon', 'is_trackback'] as $conditional) {
            if (function_exists($conditional) && $conditional()) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'file' => 25,
            'canonical_name' => self::CANONICAL_NAME,
            'subtitle' => self::SUBTITLE,
            'contract_version' => self::CONTRACT_VERSION,
            'runtime_version' => defined('SABRI_PUBLIC_EXPERIENCE_VERSION')
                ? (string) SABRI_PUBLIC_EXPERIENCE_VERSION
                : '',
            'global_shell_owner' => 'file-20',
            'visual_system_owner' => 'file-25',
            'design_token_owner' => 'file-25',
            'structural_layout_owner' => 'file-20',
            'security_governance_owner' => 'file-24',
            'profile_master_owner' => 'file-00-file-03',
            'public_contact_consent_owner' => 'file-03',
            'shell_detected' => self::shell_is_available(),
            'scope' => [
                'global-design-system',
                'public-ui',
                'profile-timeline',
                'responsive-refinement',
                'visual-consistency',
                'accessibility',
                'visual-regression',
                'reusable-content-cards',
                'optional-profile-sections',
                'visual-acceptance-evidence',
                'deterministic-staging-packaging',
                'installed-staging-preflight',
                'file-24-module-manifest',
                'no-store-profile-boundary',
                'native-contact-consent',
                'same-origin-profile-media',
                'conditional-profile-layout',
                'profile-breadcrumbs',
                'structured-profile-section-rest',
                'latest-central-requirement-ledger',
                'file20-shell-screenshot-regression',
                'tracking-free-profile-deep-link-qr-presentation',
            ],
            'tokens' => self::tokens(),
            'components' => Components::contract(),
            'content_cards' => Content_Cards::contract(),
            'visual_acceptance' => Visual_Acceptance::contract(),
            'optional_sections' => [
                'contract_version' => '1.2.0',
                'allowed_sections' => Section_Registry::approved_sections(),
                'registration_hook' => 'sabri_public_experience/register_section_providers',
                'provider_interface' => 'Sabri\\PublicExperience\\Contracts\\Profile_Section_Provider',
                'owns_native_content' => false,
                'requires_content_before_tab' => true,
                'provider_metadata_bound_to_concrete_object' => true,
                'public_projection_contract' => Section_Service::PUBLIC_CONTRACT_VERSION,
                'internal_projection_key' => [
                    'field' => 'projection_key',
                    'format' => 'sha256',
                    'rendered_publicly' => false,
                    'purpose' => 'deduplicate native objects that share one application URL',
                ],
            ],
            'public_rest' => [
                'namespace' => 'sabri-public/v1',
                'profile' => '/profiles/{public-slug}',
                'timeline' => '/profiles/{public-slug}/timeline',
                'knowledge' => '/profiles/{public-slug}/knowledge',
                'media' => '/profiles/{public-slug}/media',
                'provider_health' => '/providers/health',
                'structured_cards' => true,
                'provider_ids_public' => false,
                'native_ids_public' => false,
                'no_store' => true,
                'etag' => true,
            ],
            'profile_presentation' => [
                'founder_display_name' => Profile_Repository::FOUNDER_DISPLAY_NAME,
                'external_avatar_fallback' => false,
                'same_origin_media_only' => true,
                'doctor_contact_requires_file_03_consent' => true,
                'right_sidebar_requires_real_content' => true,
                'breadcrumb_html' => true,
                'breadcrumb_schema' => true,
                'missing_profile_default_status' => 404,
                'explicit_tombstone_status' => 410,
                'doctor_structured_data_type' => 'Physician',
                'profile_qr_image_filter' => 'sabri_public_experience/profile_qr_image_url',
                'profile_qr_same_site_only' => true,
                'profile_qr_tracking_free_required' => true,
                'profile_qr_hidden_when_unavailable' => true,
            ],
            'latest_governing_requirements' => [
                'file_specific' => ['F25-CEN-01', 'F25-CEN-02'],
                'acceptance_journeys' => ['AJ-04', 'AJ-31', 'AJ-32', 'AJ-33', 'AJ-39', 'AJ-40'],
                'global_search_discovery_ranking_owner' => 'file-26',
                'matrix' => 'config/central-2026-file25-requirements.json',
                'source_complete_is_external_acceptance' => false,
            ],
            'file_24_integration' => File_24_Integration::contract(),
            'staging_probe' => Staging_Probe::contract(),
            'staging_package' => [
                'contract_version' => '1.0.0',
                'builder' => 'tools/build-staging-package.php',
                'dependency_matrix' => 'config/staging-dependencies.json',
                'package_root' => 'sabri-public-experience',
                'manifest' => 'STAGING-MANIFEST.json',
                'checksum' => 'sha256',
                'deterministic_source_date_epoch' => true,
                'development_files_excluded' => true,
                'staging_acceptance_implied' => false,
                'production_acceptance_implied' => false,
            ],
            'asset_handle' => 'sabri-visual-design-system',
            'css_prefix' => 'sabri-ui-',
            'duplicates_file_20_shell' => false,
            'creates_file_26' => false,
        ];
    }

    /** @return array<string,array<string,string>> */
    public static function tokens(): array
    {
        return [
            // File 25 is the canonical visual-token owner. File 20 may consume
            // these values, but must not supply competing color/typography/
            // spacing/radius settings. Only structural width inherits File 20.
            'color-primary' => self::token('--sabri-visual-primary', '', '#087A4E'),
            'color-primary-strong' => self::token('--sabri-visual-primary-strong', '', '#065C3B'),
            'color-primary-soft' => self::token('--sabri-visual-primary-soft', '', '#E7F5EE'),
            'color-on-primary' => self::token('--sabri-visual-on-primary', '', '#ffffff'),
            'color-text' => self::token('--sabri-visual-text', '', '#171717'),
            'color-muted' => self::token('--sabri-visual-muted', '', '#5f6368'),
            'color-surface' => self::token('--sabri-visual-surface', '', '#ffffff'),
            'color-page' => self::token('--sabri-visual-page', '', '#f7f5f1'),
            'color-border' => self::token('--sabri-visual-border', '', '#dfe2e6'),
            'color-focus' => self::token('--sabri-visual-focus', '', '#0b57d0'),
            'color-success' => self::token('--sabri-visual-success', '', '#137333'),
            'color-warning' => self::token('--sabri-visual-warning', '', '#8a4b08'),
            'color-danger' => self::token('--sabri-visual-danger', '', '#b42318'),
            'color-on-danger' => self::token('--sabri-visual-on-danger', '', '#ffffff'),
            'radius-control' => self::token('--sabri-visual-radius-control', '', '0.75rem'),
            'radius-card' => self::token('--sabri-visual-radius-card', '', '1rem'),
            'space-layout' => self::token('--sabri-visual-layout-gap', '', '1.5rem'),
            'font-scale' => self::token('--sabri-visual-font-scale', '', '1'),
            'content-wide' => self::token('--sabri-visual-content-wide', '--sabri-shell-max-width', '100rem'),
        ];
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function filter_contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];

        return array_merge($base, self::contract());
    }

    /** @param mixed $tokens @return array<string,array<string,string>> */
    public static function filter_tokens(mixed $tokens): array
    {
        $base = is_array($tokens) ? $tokens : [];

        return array_merge($base, self::tokens());
    }

    /** @param mixed $components @return array<string,mixed> */
    public static function filter_components(mixed $components): array
    {
        $base = is_array($components) ? $components : [];

        return array_merge($base, Components::contract());
    }

    private static function shell_is_available(): bool
    {
        if (! defined('SABRI_SHELL_VERSION')) {
            return false;
        }

        return ! function_exists('apply_filters')
            || (bool) apply_filters('sabri_public_experience/dependency/application_shell', true);
    }

    /** @return array<string,string> */
    private static function token(string $variable, string $inherits, string $fallback): array
    {
        return [
            'css_variable' => $variable,
            'inherits' => $inherits,
            'fallback' => $fallback,
        ];
    }
}
