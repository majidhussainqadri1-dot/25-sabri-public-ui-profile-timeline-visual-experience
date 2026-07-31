<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Reviewed, bounded integration with File 24.
 *
 * File 24 remains the canonical owner of security governance, privacy
 * orchestration, audit evidence, incidents and resilience. File 25 only
 * publishes its own manifest, keeps profile responses non-cacheable until a
 * later accepted cache-partition contract exists, and requests advisory
 * monitoring when File 25 enters Safe Mode.
 */
final class File_24_Integration
{
    public const CONTRACT_VERSION = '1.0.0';
    public const MODULE_KEY = 'file-25-public-experience';
    public const REVIEWED_MINIMUM_VERSION = '0.25.3';
    public const REVIEWED_MAXIMUM_VERSION = '0.26.0';

    private static bool $pending_safe_mode_request = false;

    public function __construct(private Profile_Router $router)
    {
    }

    public function register(): void
    {
        // File 24 collects manifests at init:50. A late filter priority keeps
        // File 25's canonical self-description after ordinary extensions.
        add_filter('spcrc/module_manifests', [self::class, 'filter_manifests'], 999, 1);
        add_action('init', [self::class, 'dispatch_pending_safe_mode_request'], 60);
        add_action('sabri_public_experience/safe_mode_changed', [self::class, 'safe_mode_changed'], 10, 2);
        add_action('send_headers', [$this, 'send_profile_headers'], 100);
    }

    /** @return array<string,mixed> */
    public static function contract(): array
    {
        return [
            'contract_version' => self::CONTRACT_VERSION,
            'file_24_constant' => 'SPCRC_VERSION',
            'reviewed_version_range' => '>=' . self::REVIEWED_MINIMUM_VERSION . ' <' . self::REVIEWED_MAXIMUM_VERSION,
            'module_key' => self::MODULE_KEY,
            'manifest_filter' => 'spcrc/module_manifests',
            'security_state_action' => 'spcrc/request_security_state',
            'cache_mode' => 'no-store-until-versioned-partition-contract',
            'owns_security_governance' => false,
            'owns_privacy_orchestration' => false,
            'owns_incidents' => false,
            'owns_native_content' => false,
            'staging_acceptance_implied' => false,
            'production_acceptance_implied' => false,
        ];
    }

    public static function current_version(): string
    {
        return defined('SPCRC_VERSION') ? substr(trim((string) SPCRC_VERSION), 0, 64) : '';
    }

    public static function version_is_compatible(string $version): bool
    {
        $version = trim($version);

        return $version !== ''
            && version_compare($version, self::REVIEWED_MINIMUM_VERSION, '>=')
            && version_compare($version, self::REVIEWED_MAXIMUM_VERSION, '<');
    }

    public static function is_compatible(): bool
    {
        return self::version_is_compatible(self::current_version());
    }

    /** @param mixed $manifests @return list<mixed> */
    public static function filter_manifests(mixed $manifests): array
    {
        $incoming = is_array($manifests) ? array_values($manifests) : [];
        $filtered = [];
        foreach ($incoming as $manifest) {
            if (! is_array($manifest)) {
                $filtered[] = $manifest;
                continue;
            }
            $key = function_exists('sanitize_key')
                ? sanitize_key((string) ($manifest['module_key'] ?? ''))
                : strtolower(trim((string) ($manifest['module_key'] ?? '')));
            if ($key === self::MODULE_KEY) {
                continue;
            }
            $filtered[] = $manifest;
        }

        // File 24 accepts at most 99 external manifests after its own manifest.
        // Keep File 25 inside that bounded collection without inventing data for
        // or mutating any other module.
        $filtered = array_slice($filtered, 0, 98);
        array_unshift($filtered, self::manifest());

        return $filtered;
    }

    /** @return array<string,mixed> */
    public static function manifest(): array
    {
        return [
            'module_key' => self::MODULE_KEY,
            'name' => 'Sabri Unified Global Visual Experience and Design System',
            'version' => defined('SABRI_PUBLIC_EXPERIENCE_VERSION')
                ? (string) SABRI_PUBLIC_EXPERIENCE_VERSION
                : '',
            'owner' => 'File 25',
            'posture' => 'foundation',
            'data_classes' => [
                'C0 Public Presentation Metadata',
                'C1 Internal Package and Visual Evidence References',
            ],
            'public_routes' => [
                '/founder/',
                '/doctors/{slug}/',
                '/profile/{slug}/',
                '/wp-json/sabri-public/v1/founder',
                '/wp-json/sabri-public/v1/profiles/{slug}',
            ],
            'private_routes' => [
                '/wp-admin/site-health.php',
                'wp-cli:sabri file25 staging-probe',
            ],
            'capabilities' => [],
            'external_vendors' => [],
            'privacy_operations' => [],
            'last_security_test' => '',
        ];
    }

    /** @param array<string,mixed> $incident */
    public static function safe_mode_changed(bool $active, array $incident = []): void
    {
        unset($incident);
        if (! $active) {
            self::$pending_safe_mode_request = false;
            return;
        }

        self::$pending_safe_mode_request = true;
        if (function_exists('did_action') && did_action('init') > 0) {
            self::dispatch_pending_safe_mode_request();
        }
    }

    public static function dispatch_pending_safe_mode_request(): void
    {
        if (! self::$pending_safe_mode_request || ! self::is_compatible()) {
            return;
        }

        self::$pending_safe_mode_request = false;
        do_action('spcrc/request_security_state', self::MODULE_KEY, 'elevated-monitoring', [
            'reason' => 'file-25-safe-mode',
            'expires_at' => gmdate('c', time() + HOUR_IN_SECONDS),
        ]);
    }

    public function send_profile_headers(): void
    {
        if (headers_sent() || ! $this->router->is_profile_request()) {
            return;
        }

        if (function_exists('nocache_headers')) {
            nocache_headers();
        }
        header('Cache-Control: no-store, private, max-age=0, must-revalidate', true);
        header('Pragma: no-cache', true);
        header('Expires: Wed, 11 Jan 1984 05:00:00 GMT', true);
        header('X-Content-Type-Options: nosniff', true);
        header('Referrer-Policy: strict-origin-when-cross-origin', true);
        header('Vary: Cookie, Accept-Language', false);
    }
}
