<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Current reviewed File 20 compatibility boundary.
 *
 * Repository evidence is deliberately not staging/live evidence. This class
 * only prevents File 25 from treating an arbitrary SABRI_SHELL_VERSION
 * constant as a valid structural-shell contract.
 */
final class File_20_Integration
{
    public const REVIEWED_MINIMUM_VERSION = '1.4.12';
    public const REVIEWED_MAXIMUM_VERSION = '1.5.0';
    public const REVIEWED_CONTRACT_VERSION = '1.0.0';
    public const REVIEWED_SOURCE_COMMIT = '291486b22c7ed94b8be041192375b6d9b077fac5';

    public static function register(): void
    {
        add_filter(
            'sabri_public_experience/dependency/application_shell',
            [self::class, 'filter_availability'],
            5,
            1
        );
    }

    public static function is_compatible(): bool
    {
        if (! defined('SABRI_SHELL_VERSION')) {
            return false;
        }

        $version = trim((string) SABRI_SHELL_VERSION);
        if (preg_match('/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version) !== 1
            || version_compare($version, self::REVIEWED_MINIMUM_VERSION, '<')
            || version_compare($version, self::REVIEWED_MAXIMUM_VERSION, '>=')
            || ! class_exists('Sabri\\UnifiedShell\\CentralPlanContract')
            || ! defined('Sabri\\UnifiedShell\\CentralPlanContract::CONTRACT_VERSION')
        ) {
            return false;
        }

        $contract = trim((string) constant('Sabri\\UnifiedShell\\CentralPlanContract::CONTRACT_VERSION'));
        if (! hash_equals(self::REVIEWED_CONTRACT_VERSION, $contract)) {
            return false;
        }

        // When WordPress exposes hook inspection, require File 20 to have
        // actually published its current central-plan contract registry.
        if (function_exists('has_filter') && has_filter('sabri_shell_contract_registry') === false) {
            return false;
        }

        return true;
    }

    public static function filter_availability(mixed $available): bool
    {
        return $available === true && self::is_compatible();
    }

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'owner' => 'file-20',
            'reviewed_minimum_version' => self::REVIEWED_MINIMUM_VERSION,
            'reviewed_maximum_version_exclusive' => self::REVIEWED_MAXIMUM_VERSION,
            'reviewed_contract_version' => self::REVIEWED_CONTRACT_VERSION,
            'reviewed_source_commit' => self::REVIEWED_SOURCE_COMMIT,
            'runtime_compatible' => self::is_compatible(),
            'structural_shell_owner' => 'file-20',
            'visual_token_owner' => 'file-25',
            'staging_accepted' => false,
            'production_accepted' => false,
            'live_verified' => false,
        ];
    }
}
