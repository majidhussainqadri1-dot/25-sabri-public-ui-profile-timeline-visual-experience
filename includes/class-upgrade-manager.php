<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/** Bounded, idempotent File 25 schema and rewrite upgrade coordinator. */
final class Upgrade_Manager
{
    private const LOCK_OPTION = 'sabri_public_experience_upgrade_lock';
    private const SCHEMA_OPTION = 'sabri_public_experience_schema_version';
    private const RUNTIME_OPTION = 'sabri_public_experience_runtime_version';
    private const LOCK_TTL = 300;

    public function register(): void
    {
        add_action('init', [self::class, 'maybe_upgrade'], 100);
    }

    public static function maybe_upgrade(): void
    {
        $target = defined('SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION')
            ? (int) SABRI_PUBLIC_EXPERIENCE_SCHEMA_VERSION
            : 0;
        if ($target < 1 || (int) get_option(self::SCHEMA_OPTION, 0) >= $target) {
            return;
        }

        $token = function_exists('wp_generate_uuid4')
            ? (string) wp_generate_uuid4()
            : bin2hex(random_bytes(16));
        if (! self::acquire_lock($token)) {
            return;
        }

        try {
            if ((int) get_option(self::SCHEMA_OPTION, 0) >= $target) {
                return;
            }
            Profile_Router::flush();
            update_option(self::SCHEMA_OPTION, (string) $target, false);
            if (defined('SABRI_PUBLIC_EXPERIENCE_VERSION')) {
                update_option(self::RUNTIME_OPTION, (string) SABRI_PUBLIC_EXPERIENCE_VERSION, false);
            }
        } catch (\Throwable $exception) {
            if (class_exists(Safe_Mode::class)) {
                Safe_Mode::enable('upgrade-exception', $exception);
            }
        } finally {
            self::release_lock($token);
        }
    }

    private static function acquire_lock(string $token): bool
    {
        $record = ['token' => $token, 'created_at' => time()];
        if (add_option(self::LOCK_OPTION, $record, '', false)) {
            return true;
        }

        $existing = get_option(self::LOCK_OPTION, []);
        $created_at = is_array($existing) ? (int) ($existing['created_at'] ?? 0) : 0;
        if ($created_at > 0 && (time() - $created_at) <= self::LOCK_TTL) {
            return false;
        }

        delete_option(self::LOCK_OPTION);

        return add_option(self::LOCK_OPTION, $record, '', false);
    }

    private static function release_lock(string $token): void
    {
        $existing = get_option(self::LOCK_OPTION, []);
        if (is_array($existing) && hash_equals((string) ($existing['token'] ?? ''), $token)) {
            delete_option(self::LOCK_OPTION);
        }
    }
}
