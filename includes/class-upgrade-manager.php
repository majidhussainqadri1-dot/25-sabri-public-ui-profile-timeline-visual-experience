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
        $runtime_version = defined('SABRI_PUBLIC_EXPERIENCE_VERSION')
            ? (string) SABRI_PUBLIC_EXPERIENCE_VERSION
            : '';
        $schema_pending = (int) get_option(self::SCHEMA_OPTION, 0) < $target;
        $runtime_pending = $runtime_version !== ''
            && get_option(self::RUNTIME_OPTION, '') !== $runtime_version;
        // After a schema write succeeds but the runtime metadata write fails,
        // the next request must repair the latter without redoing schema work.
        if ($target < 1 || (! $schema_pending && ! $runtime_pending)) {
            return;
        }

        $token = function_exists('wp_generate_uuid4')
            ? (string) wp_generate_uuid4()
            : bin2hex(random_bytes(16));
        if (! self::acquire_lock($token)) {
            return;
        }

        try {
            $schema_pending = (int) get_option(self::SCHEMA_OPTION, 0) < $target;
            $runtime_pending = $runtime_version !== ''
                && get_option(self::RUNTIME_OPTION, '') !== $runtime_version;
            if (! $schema_pending && ! $runtime_pending) {
                return;
            }
            if ($schema_pending) {
                Profile_Router::flush();
                update_option(self::SCHEMA_OPTION, (string) $target, false);
                // A false update_option() return can mean unchanged or write
                // failure. Read back the actual persisted schema state.
                if ((int) get_option(self::SCHEMA_OPTION, 0) !== $target) {
                    throw new \UnexpectedValueException('File 25 schema version was not persisted.');
                }
            }
            if ($runtime_pending) {
                update_option(self::RUNTIME_OPTION, $runtime_version, false);
                if (get_option(self::RUNTIME_OPTION, '') !== $runtime_version) {
                    throw new \UnexpectedValueException('File 25 runtime version was not persisted.');
                }
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

        if (! self::compare_delete_lock($existing)) {
            return false;
        }
        return add_option(self::LOCK_OPTION, $record, '', false);
    }

    private static function release_lock(string $token): void
    {
        $existing = get_option(self::LOCK_OPTION, []);
        if (is_array($existing) && hash_equals((string) ($existing['token'] ?? ''), $token)) {
            self::compare_delete_lock($existing);
        }
    }

    /** Atomically delete ONLY an exact same upgrade lock; otherwise fail closed. */
    private static function compare_delete_lock(array $expected): bool
    {
        global $wpdb;
        if (is_object($wpdb ?? null)
            && is_string($wpdb->options ?? null)
            && preg_match('/^[A-Za-z0-9_]+$/', $wpdb->options) === 1
            && method_exists($wpdb, 'prepare')
            && method_exists($wpdb, 'query')
            && function_exists('maybe_serialize')
        ) {
            $sql = $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name = %s AND BINARY option_value = %s",
                self::LOCK_OPTION,
                maybe_serialize($expected)
            );
            $result = is_string($sql) ? $wpdb->query($sql) : false;
            if (function_exists('wp_cache_delete')) {
                wp_cache_delete(self::LOCK_OPTION, 'options');
            }
            return $result === 1;
        }
        if (PHP_SAPI !== 'cli') {
            return false;
        }
        // CLI-only stubs model the logical state but do not prove DB atomicity.
        if (get_option(self::LOCK_OPTION, null) !== $expected) {
            return false;
        }
        return delete_option(self::LOCK_OPTION) === true;
    }
}
