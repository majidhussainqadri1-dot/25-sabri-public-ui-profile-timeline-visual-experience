<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

if (! defined('ABSPATH') && PHP_SAPI !== 'cli') {
    exit;
}

/**
 * Strict same-origin URL policy for reusable public visual components.
 *
 * File 25 components are presentation primitives, not an external-navigation
 * authority. Protocol-relative, credential-bearing, downgrade, malformed, and
 * cross-origin destinations therefore fail closed.
 */
final class Public_URL
{
    private const MAX_LENGTH = 2048;

    public static function sanitize_same_site(mixed $value, bool $allow_fragment = true): string
    {
        if (! is_scalar($value)) {
            return '';
        }

        $url = trim((string) $value);
        if ($url === '' || strlen($url) > self::MAX_LENGTH) {
            return '';
        }
        if (preg_match('/[\x00-\x20\x7F]/', $url) === 1
            || preg_match('/%(?:00|0a|0d|7f)/i', $url) === 1
            || str_contains($url, '\\')
        ) {
            return '';
        }

        if (! $allow_fragment && str_contains($url, '#')) {
            return '';
        }

        if (str_starts_with($url, '#')) {
            $fragment = substr($url, 1);
            if (! $allow_fragment
                || $fragment === ''
                || strlen($fragment) > 190
                || preg_match('/^[A-Za-z0-9_.:%-]+$/', $fragment) !== 1
                || ! self::component_is_safe($fragment, false)
            ) {
                return '';
            }

            return $url;
        }

        if (str_starts_with($url, '?')) {
            return self::component_is_safe(substr($url, 1), true) ? self::escape_raw($url) : '';
        }

        if (str_starts_with($url, '/')) {
            $lower = strtolower($url);
            $relative = parse_url($url);
            if (str_starts_with($url, '//')
                || str_starts_with($lower, '/%2f')
                || str_starts_with($lower, '/%5c')
                || ! is_array($relative)
                || ! self::path_is_safe((string) ($relative['path'] ?? ''))
                || ! self::component_is_safe((string) ($relative['query'] ?? ''), true)
                || ($allow_fragment && ! self::component_is_safe((string) ($relative['fragment'] ?? ''), false))
            ) {
                return '';
            }

            return self::escape_raw($url);
        }

        $parts = parse_url($url);
        if (! is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return '';
        }
        if (isset($parts['user']) || isset($parts['pass'])
            || ! self::path_is_safe((string) ($parts['path'] ?? ''))
            || ! self::component_is_safe((string) ($parts['query'] ?? ''), true)
            || ($allow_fragment && ! self::component_is_safe((string) ($parts['fragment'] ?? ''), false))
        ) {
            return '';
        }

        if (! function_exists('home_url')) {
            // Deterministic CLI fixture origin only; production remains fail-closed.
            return PHP_SAPI === 'cli' && $scheme === 'https' && $host === 'example.test'
                ? self::escape_raw($url)
                : '';
        }
        $home = parse_url((string) home_url('/'));
        if (! is_array($home)) {
            return '';
        }

        $home_scheme = strtolower((string) ($home['scheme'] ?? ''));
        $home_host = strtolower(rtrim((string) ($home['host'] ?? ''), '.'));
        if ($scheme !== $home_scheme || $host !== $home_host) {
            return '';
        }
        if (self::port($parts, $scheme) !== self::port($home, $home_scheme)) {
            return '';
        }

        return self::escape_raw($url);
    }

    /**
     * Produce one deterministic identity for an already permitted same-site URL.
     * Default ports and trailing host dots are normalized; path case and query
     * semantics remain intact. The public URL itself is not rewritten.
     */
    public static function canonical_same_site_identity(mixed $value, bool $allow_fragment = false): string
    {
        $safe = self::sanitize_same_site($value, $allow_fragment);
        if ($safe === '') {
            return '';
        }
        if (str_starts_with($safe, '#') || str_starts_with($safe, '?')) {
            return $safe;
        }

        $parts = parse_url($safe);
        if (! is_array($parts)) {
            return '';
        }

        $path = (string) ($parts['path'] ?? '/');
        $path = $path === '' ? '/' : $path;
        $path = $path === '/' ? '/' : rtrim($path, '/');
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?' . $parts['query'] : '';
        $fragment = $allow_fragment && isset($parts['fragment']) && $parts['fragment'] !== ''
            ? '#' . $parts['fragment']
            : '';

        if (! isset($parts['scheme']) && ! isset($parts['host'])) {
            return $path . $query . $fragment;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return '';
        }
        $port_number = self::port($parts, $scheme);
        $default_port = $scheme === 'https' ? 443 : 80;
        $port = $port_number === $default_port ? '' : ':' . $port_number;

        return $scheme . '://' . $host . $port . $path . $query . $fragment;
    }


    private static function path_is_safe(string $path): bool
    {
        if ($path === '') {
            return true;
        }
        if (strlen($path) > 2048
            || preg_match('/%(?![0-9A-Fa-f]{2})/', $path) === 1
            || preg_match('/[\x00-\x1F\x7F\\\\]/', $path) === 1
        ) {
            return false;
        }

        // Decode repeatedly because different proxies, servers, WordPress, and
        // browsers may consume different encoding layers. Any separator,
        // control byte, backslash, or dot-only segment at any bounded layer is
        // an ambiguous public-route identity and therefore fails closed.
        $decoded = $path;
        for ($depth = 0; $depth < 6; $depth++) {
            if (preg_match('#(?:^|/)(?:\.{1,2})(?:/|$)#', $decoded) === 1
                || preg_match('/[\x00-\x1F\x7F\\\\]/', $decoded) === 1
            ) {
                return false;
            }
            if (preg_match('/%(?:2f|5c)/i', $decoded) === 1) {
                return false;
            }
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                return true;
            }
            $decoded = $next;
        }

        // More than six effective decoding layers are never a legitimate
        // canonical public route and create implementation-dependent identity.
        return rawurldecode($decoded) === $decoded;
    }


    private static function component_is_safe(string $component, bool $allow_separators): bool
    {
        if ($component === '') {
            return true;
        }
        if (strlen($component) > 2048
            || preg_match('/%(?![0-9A-Fa-f]{2})/', $component) === 1
            || preg_match('/[\x00-\x1F\x7F\\\\]/', $component) === 1
            || self::has_format_controls($component)
        ) {
            return false;
        }

        $decoded = $component;
        for ($depth = 0; $depth < 6; $depth++) {
            if (preg_match('/[\x00-\x1F\x7F\\\\]/', $decoded) === 1
                || self::has_format_controls($decoded)
                || (! $allow_separators && preg_match('/[\/?#]/', $decoded) === 1)
            ) {
                return false;
            }
            $next = rawurldecode($decoded);
            if ($next === $decoded) {
                return true;
            }
            $decoded = $next;
        }

        return rawurldecode($decoded) === $decoded;
    }

    private static function has_format_controls(string $value): bool
    {
        return preg_match('/[\x{00AD}\x{061C}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}-\x{206F}\x{FEFF}]/u', $value) === 1;
    }

    /** @param array<string,mixed> $parts */
    private static function port(array $parts, string $scheme): int
    {
        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        return $scheme === 'https' ? 443 : 80;
    }

    private static function escape_raw(string $url): string
    {
        if (function_exists('esc_url_raw')) {
            return (string) esc_url_raw($url, ['http', 'https']);
        }

        return $url;
    }
}
