<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Places File 25 controls inside the canonical File 20 administration
 * architecture when the reviewed shell contract is present. The historical
 * standalone File 25 menu remains only as a degraded continuity fallback.
 */
final class Admin_Integration
{
    public const CONTRACT_VERSION = '1.0.0';
    public const SHELL_PARENT_SLUG = 'sabri-shell';

    /** @var list<string> */
    private const PLAN_SECTIONS = [
        'public-experience-overview',
        'profile-templates',
        'timeline-providers',
        'content-cards',
        'public-visibility',
        'responsive-preview',
        'accessibility',
        'seo-presentation',
        'cache-and-index',
        'adapter-health',
        'migration',
        'system-check',
        'repair',
        'safe-mode',
        'diagnostics',
    ];

    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'rehome'], 100);
        add_filter('sabri_public_experience/admin_integration_contract', [self::class, 'filter_contract']);
    }

    public static function rehome(): void
    {
        if (! File_20_Integration::is_compatible()) {
            return;
        }

        // Remove the continuity-only top-level page and the component-lab child
        // that was provisionally attached to it earlier in admin_menu.
        remove_submenu_page('sabri-public-experience', 'sabri-public-experience-component-lab');
        remove_menu_page('sabri-public-experience');

        $plan = new Plan_Completion();
        add_submenu_page(
            self::SHELL_PARENT_SLUG,
            __('File 25 Visual Experience', 'sabri-public-experience'),
            __('Public Experience', 'sabri-public-experience'),
            'manage_options',
            'sabri-public-experience',
            [$plan, 'render_admin']
        );

        $future = new Future_Public_Experience();
        add_submenu_page(
            self::SHELL_PARENT_SLUG,
            __('File 25 Component Laboratory', 'sabri-public-experience'),
            __('Component Laboratory', 'sabri-public-experience'),
            'manage_options',
            'sabri-public-experience-component-lab',
            [$future, 'render_component_lab']
        );
    }

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'owner' => 'file-25',
            'canonical_admin_architecture_owner' => 'file-20',
            'parent_slug' => self::SHELL_PARENT_SLUG,
            'standalone_menu_mode' => 'degraded-fallback-only',
            'plan_sections' => self::PLAN_SECTIONS,
            'component_lab_private' => true,
            'staging_accepted' => false,
        ];
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function filter_contract(mixed $contract): array
    {
        return array_merge(is_array($contract) ? $contract : [], self::contract());
    }
}
