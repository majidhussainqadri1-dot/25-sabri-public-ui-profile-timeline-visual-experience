<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Latest Founder-governed reconciliation layer for the 6–7 August 2026
 * central-plan addenda.
 *
 * Historical review ledgers remain immutable. This layer supersedes stale
 * token/color declarations while preserving File 20 as the structural shell
 * owner and File 25 as the canonical visual-token owner.
 */
final class Central_Plan_2026_Corrections
{
    public const CONTRACT_VERSION = '1.1.0';
    public const PRIMARY_GREEN = '#087A4E';
    public const PRIMARY_GREEN_STRONG = '#065C3B';
    public const PRIMARY_GREEN_SOFT = '#E7F5EE';
    public const PRIMARY_ON_GREEN = '#FFFFFF';
    public const FILE_22_REVIEWED_VERSION = '0.4.0';
    public const FILE_22_REVIEWED_COMMIT = '9d749a8e74910aa192c69a3727f5bf2f920af763';
    public const FILE_23_REVIEWED_VERSION = '1.2.0';
    public const FILE_23_REVIEWED_COMMIT = 'a8a8c805f4730998ccb44bd95c87591836561759';

    public static function register(): void
    {
        if (! class_exists(Future_Public_Experience::class)) {
            require_once __DIR__ . '/class-future-public-experience.php';
        }
        (new Future_Public_Experience())->register();
        add_filter('sabri_visual_experience/tokens', [self::class, 'filter_tokens'], 200);
        add_filter('sabri_visual_experience/contract', [self::class, 'filter_contract'], 200);
        add_filter('sabri_public_experience/design_system_contract', [self::class, 'filter_contract'], 200);
        add_action('wp_enqueue_scripts', [self::class, 'attach_styles'], 200);
    }

    /** @param mixed $tokens @return array<string,mixed> */
    public static function filter_tokens(mixed $tokens): array
    {
        $base = is_array($tokens) ? $tokens : [];
        $base['color-primary'] = self::token('--sabri-visual-primary', self::PRIMARY_GREEN);
        $base['color-primary-strong'] = self::token('--sabri-visual-primary-strong', self::PRIMARY_GREEN_STRONG);
        $base['color-primary-soft'] = self::token('--sabri-visual-primary-soft', self::PRIMARY_GREEN_SOFT);
        $base['color-on-primary'] = self::token('--sabri-visual-on-primary', self::PRIMARY_ON_GREEN);

        return $base;
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function filter_contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];
        $base['design_token_owner'] = 'file-25';
        $base['structural_layout_owner'] = 'file-20';
        $base['canonical_primary_color'] = self::PRIMARY_GREEN;
        $base['token_ownership_boundary'] = [
            'file_20' => [
                'global-widths',
                'layout-breakpoints',
                'header-sidebar-dimensions',
                'z-index-and-mount-layers',
                'safe-content-geometry',
            ],
            'file_25' => [
                'colors',
                'typography',
                'spacing-scale',
                'borders',
                'radius',
                'shadows',
                'component-states',
                'profile-timeline-cards',
                'visual-regression',
            ],
            'duplicate_visual_settings_allowed' => false,
        ];

        if (isset($base['current_directives']) && is_array($base['current_directives'])) {
            $base['current_directives']['canonical_primary_color'] = self::PRIMARY_GREEN;
            $base['current_directives']['supersedes_prior_green_value'] = '#15803d';
            $base['current_directives']['governing_correction_date'] = '2026-08-07';
        }

        $base['publishing_experience_integrations'] = [
            'file_22' => [
                'owner' => 'universal-composer',
                'reviewed_version' => self::FILE_22_REVIEWED_VERSION,
                'reviewed_commit' => self::FILE_22_REVIEWED_COMMIT,
                'public_create_route' => '/create/',
                'role' => 'create-edit-draft-preview-submit-orchestration',
                'staging_accepted' => false,
            ],
            'file_23' => [
                'owner' => 'publishing-dashboard',
                'reviewed_version' => self::FILE_23_REVIEWED_VERSION,
                'reviewed_commit' => self::FILE_23_REVIEWED_COMMIT,
                'private_management_route' => '/publishing-dashboard/',
                'role' => 'private-federated-publishing-operations',
                'staging_accepted' => false,
            ],
        ];
        $base['central_plan_reconciliation'] = [
            'contract_version' => self::CONTRACT_VERSION,
            'governing_date' => '2026-08-07',
            'primary_green' => self::PRIMARY_GREEN,
            'file_20_shell_ownership_preserved' => true,
            'file_25_visual_token_ownership_preserved' => true,
            'file_22_and_23_full_publishing_experience_traced' => true,
            'file_26_global_search_owner_preserved' => true,
            'file_specific_requirements' => ['F25-CEN-01', 'F25-CEN-02'],
            'new_plan_matrix' => 'config/central-2026-file25-requirements.json',
            'hostinger_staging_accepted' => false,
            'live_deployed' => false,
            'operational' => false,
        ];
        $base['future_public_experience'] = Future_Public_Experience::contract();

        $base['tokens'] = self::filter_tokens($base['tokens'] ?? []);

        return $base;
    }

    public static function attach_styles(): void
    {
        if (! function_exists('wp_add_inline_style')) {
            return;
        }

        wp_add_inline_style('sabri-visual-design-system', self::css());
    }

    public static function css(): string
    {
        return <<<'CSS'
:root,
body.sabri-primary-green {
    --sabri-visual-primary: #087A4E;
    --sabri-visual-primary-strong: #065C3B;
    --sabri-visual-primary-soft: #E7F5EE;
    --sabri-visual-on-primary: #FFFFFF;
    accent-color: var(--sabri-visual-primary);
}
CSS;
    }

    /** @return array<string,string> */
    private static function token(string $variable, string $fallback): array
    {
        return [
            'css_variable' => $variable,
            'inherits' => '',
            'fallback' => $fallback,
        ];
    }
}
