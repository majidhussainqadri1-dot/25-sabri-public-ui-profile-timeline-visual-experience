<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Machine-readable Definition-of-Done matrix for File 25 visual acceptance. */
final class Visual_Acceptance
{
    public const CONTRACT_VERSION = '1.0.0';

    private const VIEWPORTS = [
        'mobile-small' => ['width' => 320, 'height' => 568],
        'mobile-modern' => ['width' => 390, 'height' => 844],
        'tablet-portrait' => ['width' => 768, 'height' => 1024],
        'tablet-landscape' => ['width' => 1024, 'height' => 768],
        'desktop' => ['width' => 1440, 'height' => 900],
        'desktop-wide' => ['width' => 1920, 'height' => 1080],
    ];

    private const DIRECTIONS = ['ltr', 'rtl'];
    private const COLOR_MODES = ['light', 'dark', 'forced-colors'];
    private const MOTION_MODES = ['normal', 'reduced'];
    private const ZOOM_LEVELS = [100, 200, 400];
    private const INPUT_MODES = ['keyboard', 'pointer', 'touch', 'screen-reader'];
    private const REQUIRED_SURFACES = [
        'founder-overview',
        'doctor-overview',
        'member-overview',
        'profile-timeline',
        'knowledge-section',
        'loading-state',
        'empty-state',
        'error-state',
        'success-state',
        'warning-state',
        'unavailable-state',
        'content-card-grid',
        'form-controls',
        'responsive-table',
    ];

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'owner' => 'file-25',
            'shell_owner' => 'file-20',
            'viewports' => self::VIEWPORTS,
            'directions' => self::DIRECTIONS,
            'color_modes' => self::COLOR_MODES,
            'motion_modes' => self::MOTION_MODES,
            'zoom_levels' => self::ZOOM_LEVELS,
            'input_modes' => self::INPUT_MODES,
            'required_surfaces' => self::REQUIRED_SURFACES,
            'evidence_required' => true,
            'source_contract_is_acceptance' => false,
            'green_ci_is_acceptance' => false,
            'staging_required' => true,
            'founder_signoff_required' => true,
        ];
    }

    /** @param mixed $contract @return array<string,mixed> */
    public static function filter_contract(mixed $contract): array
    {
        $base = is_array($contract) ? $contract : [];

        return array_merge($base, self::contract());
    }

    /** @param array<string,mixed> $evidence @return list<string> */
    public static function validate_evidence(array $evidence): array
    {
        $errors = [];
        foreach (self::REQUIRED_SURFACES as $surface) {
            if (! isset($evidence['surfaces'][$surface]) || ! is_array($evidence['surfaces'][$surface])) {
                $errors[] = 'Missing surface evidence: ' . $surface;
            }
        }

        foreach (array_keys(self::VIEWPORTS) as $viewport) {
            if (empty($evidence['viewports'][$viewport])) {
                $errors[] = 'Missing viewport evidence: ' . $viewport;
            }
        }

        foreach (self::DIRECTIONS as $direction) {
            if (empty($evidence['directions'][$direction])) {
                $errors[] = 'Missing direction evidence: ' . $direction;
            }
        }

        foreach (self::COLOR_MODES as $mode) {
            if (empty($evidence['color_modes'][$mode])) {
                $errors[] = 'Missing color-mode evidence: ' . $mode;
            }
        }

        foreach (self::MOTION_MODES as $mode) {
            if (empty($evidence['motion_modes'][$mode])) {
                $errors[] = 'Missing motion evidence: ' . $mode;
            }
        }

        foreach (self::ZOOM_LEVELS as $zoom) {
            if (empty($evidence['zoom_levels'][(string) $zoom])) {
                $errors[] = 'Missing zoom evidence: ' . $zoom;
            }
        }

        foreach (self::INPUT_MODES as $mode) {
            if (empty($evidence['input_modes'][$mode])) {
                $errors[] = 'Missing input-mode evidence: ' . $mode;
            }
        }

        if (empty($evidence['staging_environment'])) {
            $errors[] = 'Missing staging environment evidence.';
        }
        if (empty($evidence['founder_signoff'])) {
            $errors[] = 'Missing Founder sign-off evidence.';
        }

        return $errors;
    }
}
