<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Privacy-minimized File 25 diagnostic event boundary from governing plan §74.
 *
 * No raw user/profile/content payload is accepted. Security-critical diagnostics
 * request File 24 elevated monitoring through the already-reviewed advisory
 * security-state action; File 24 remains the assurance/incident owner.
 */
final class Observability
{
    public const EVENT_CODES = [
        'provider_unavailable',
        'normalization_failure',
        'duplicate_projection',
        'stale_index',
        'broken_canonical_url',
        'privacy_field_rejection',
        'cache_mismatch',
        'visual_component_failure',
        'slow_query',
        'rest_authorization_rejection',
        'rebuild_failure',
    ];

    private const SECURITY_CRITICAL = [
        'privacy_field_rejection',
        'rest_authorization_rejection',
    ];

    private const CONTEXT_KEYS = [
        'provider_id',
        'operation',
        'route',
        'component',
        'status',
        'duration_ms',
        'reason_code',
    ];

    /** @param array<string,mixed> $context */
    public static function emit(string $code, array $context = []): void
    {
        if (! in_array($code, self::EVENT_CODES, true)) {
            return;
        }

        $safe = [];
        foreach (self::CONTEXT_KEYS as $key) {
            if (! array_key_exists($key, $context) || ! is_scalar($context[$key])) {
                continue;
            }
            $value = trim((string) $context[$key]);
            if ($value === '' || strlen($value) > 160 || preg_match('/[\x00-\x1F\x7F]/', $value) === 1) {
                continue;
            }
            $safe[$key] = $value;
        }

        $event = [
            'code' => $code,
            'module' => 'file-25',
            'occurred_at_utc' => gmdate('Y-m-d H:i:s'),
            'context' => $safe,
            'contains_sensitive_data' => false,
        ];

        if (function_exists('do_action')) {
            do_action('sabri_public_experience/diagnostic_event', $event);
        }

        if (in_array($code, self::SECURITY_CRITICAL, true)
            && class_exists(File_24_Integration::class)
            && File_24_Integration::is_compatible()
            && function_exists('do_action')
        ) {
            do_action('spcrc/request_security_state', File_24_Integration::MODULE_KEY, 'elevated-monitoring', [
                'reason' => 'file-25-diagnostic-' . $code,
                'expires_at' => gmdate('c', time() + (defined('HOUR_IN_SECONDS') ? HOUR_IN_SECONDS : 3600)),
            ]);
        }
    }
}
