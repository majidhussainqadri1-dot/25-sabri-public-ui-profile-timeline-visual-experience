<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Non-destructive recovery boundary for File 25 public overrides.
 *
 * File 24 remains the canonical incident owner. This class only disables File
 * 25 rendering and emits a bounded event that File 24 may ingest.
 */
final class Safe_Mode
{
    private const OPTION = 'sabri_public_experience_safe_mode';
    private const INCIDENT_OPTION = 'sabri_public_experience_safe_mode_incident';

    private static bool $registered = false;
    private static bool $shutdown_registered = false;

    /** @var list<string> */
    private static array $boundary_stack = [];

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }
        self::$registered = true;

        add_action('admin_notices', [self::class, 'notice']);
        add_action('network_admin_notices', [self::class, 'notice']);
        add_action('admin_post_spux_enable_safe_mode', [self::class, 'handle_enable']);
        add_action('admin_post_spux_disable_safe_mode', [self::class, 'handle_disable']);
    }

    public static function register_shutdown_guard(): void
    {
        if (self::$shutdown_registered) {
            return;
        }
        self::$shutdown_registered = true;
        register_shutdown_function([self::class, 'shutdown']);
    }

    public static function begin(string $boundary): void
    {
        $boundary = self::sanitize_boundary($boundary);
        self::$boundary_stack[] = $boundary !== '' ? $boundary : 'runtime';
    }

    public static function end(): void
    {
        if (self::$boundary_stack !== []) {
            array_pop(self::$boundary_stack);
        }
    }

    public static function is_active(): bool
    {
        return get_option(self::OPTION, '0') === '1';
    }

    public static function enable(string $reason, ?\Throwable $exception = null): void
    {
        $incident = [
            'reason' => self::sanitize_boundary($reason),
            'boundary' => self::current_boundary(),
            'occurred_at_utc' => gmdate('Y-m-d H:i:s'),
        ];
        if ($exception !== null) {
            $incident['error_class'] = substr(get_class($exception), 0, 190);
            $incident['error_code'] = (int) $exception->getCode();
            $incident['source_file'] = substr(basename($exception->getFile()), 0, 190);
            $incident['source_line'] = max(0, $exception->getLine());
        }

        update_option(self::OPTION, '1', false);
        update_option(self::INCIDENT_OPTION, $incident, false);
        do_action('sabri_public_experience/safe_mode_changed', true, $incident);
    }

    public static function disable(): void
    {
        update_option(self::OPTION, '0', false);
        do_action('sabri_public_experience/safe_mode_changed', false, [
            'occurred_at_utc' => gmdate('Y-m-d H:i:s'),
        ]);
    }

    public static function shutdown(): void
    {
        if (self::$boundary_stack === []) {
            return;
        }

        $error = error_get_last();
        if (! is_array($error) || ! in_array((int) ($error['type'] ?? 0), [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            return;
        }

        $incident = [
            'reason' => 'fatal-shutdown',
            'boundary' => self::current_boundary(),
            'error_type' => (int) ($error['type'] ?? 0),
            'source_file' => substr(basename((string) ($error['file'] ?? '')), 0, 190),
            'source_line' => max(0, (int) ($error['line'] ?? 0)),
            'occurred_at_utc' => gmdate('Y-m-d H:i:s'),
        ];
        update_option(self::OPTION, '1', false);
        update_option(self::INCIDENT_OPTION, $incident, false);
        do_action('sabri_public_experience/safe_mode_changed', true, $incident);
    }

    public static function handle_enable(): void
    {
        self::authorize('spux_enable_safe_mode');
        self::enable('manual-administrator');
        self::redirect('enabled');
    }

    public static function handle_disable(): void
    {
        self::authorize('spux_disable_safe_mode');
        self::disable();
        self::redirect('disabled');
    }

    public static function notice(): void
    {
        if (! current_user_can('manage_options') || ! self::is_active()) {
            return;
        }

        $incident = get_option(self::INCIDENT_OPTION, []);
        $reason = is_array($incident) ? self::sanitize_boundary((string) ($incident['reason'] ?? 'unknown')) : 'unknown';
        echo '<div class="notice notice-warning"><p><strong>'
            . esc_html__('Sabri Unified Global Visual Experience Safe Mode is active.', 'sabri-public-experience')
            . '</strong> '
            . esc_html__('File 25 public visual overrides are disabled; native WordPress and companion-module data remain untouched.', 'sabri-public-experience')
            . '</p><p>'
            . esc_html(sprintf(__('Recorded reason: %s', 'sabri-public-experience'), $reason))
            . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="spux_disable_safe_mode">';
        wp_nonce_field('spux_disable_safe_mode');
        submit_button(__('Retry File 25', 'sabri-public-experience'), 'secondary', 'submit', false);
        echo '</form></div>';
    }

    /** Exposed only for bounded diagnostics and executable contract tests. */
    public static function current_boundary(): string
    {
        if (self::$boundary_stack === []) {
            return '';
        }

        $boundary = end(self::$boundary_stack);

        return is_string($boundary) ? $boundary : '';
    }

    private static function authorize(string $action): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('You are not allowed to change File 25 Safe Mode.', 'sabri-public-experience'), '', ['response' => 403]);
        }
        check_admin_referer($action);
    }

    private static function redirect(string $state): void
    {
        wp_safe_redirect(add_query_arg('spux_safe_mode', self::sanitize_boundary($state), admin_url('site-health.php')));
        exit;
    }

    private static function sanitize_boundary(string $value): string
    {
        if (function_exists('sanitize_key')) {
            return substr(sanitize_key($value), 0, 64);
        }

        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_\-]/', '-', $value) ?? '';

        return substr(trim($value, '-'), 0, 64);
    }
}
