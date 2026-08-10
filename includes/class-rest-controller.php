<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

final class Rest_Controller
{
    private const DEFAULT_PUBLIC_RATE_LIMIT = 120;
    private const DEFAULT_PUBLIC_RATE_WINDOW = 60;

    public function __construct(
        private Profile_Repository $profiles,
        private Timeline_Service $timeline,
        private Section_Service $sections
    ) {
    }

    public function register(): void
    {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes(): void
    {
        $public = ['permission_callback' => [$this, 'allow_public_request']];
        $slug = [
            'required' => true,
            'sanitize_callback' => static fn ($value): string => is_string($value) ? $value : '',
            'validate_callback' => static function ($value): bool {
                if (! is_string($value) || $value === '' || strlen($value) > 200 || ctype_digit($value)) {
                    return false;
                }
                if (trim($value) !== $value || preg_match('/[\x00-\x20\x7F]/', $value) === 1) {
                    return false;
                }

                return sanitize_title($value) === $value;
            },
        ];
        $pagination = [
            'page' => [
                'default' => 1,
                'sanitize_callback' => [self::class, 'sanitize_exact_positive_integer'],
                'validate_callback' => static fn ($value): bool => self::exact_positive_integer($value, PHP_INT_MAX) !== null,
            ],
            'per_page' => [
                'default' => 20,
                'sanitize_callback' => [self::class, 'sanitize_exact_positive_integer'],
                'validate_callback' => static fn ($value): bool => self::exact_positive_integer($value, 50) !== null,
            ],
            'content_type' => [
                'default' => '',
                'sanitize_callback' => static fn ($value): string => is_string($value) ? $value : '',
                'validate_callback' => static fn ($value): bool => self::exact_optional_key($value) !== null,
            ],
        ];

        register_rest_route('sabri-public/v1', '/founder', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_founder_profile'],
        ]);
        register_rest_route('sabri-public/v1', '/founder/timeline', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_founder_timeline'],
            'args' => $pagination,
        ]);
        register_rest_route('sabri-public/v1', '/founder/knowledge', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_founder_knowledge'],
        ]);
        register_rest_route('sabri-public/v1', '/founder/media', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_founder_media'],
        ]);
        register_rest_route('sabri-public/v1', '/profiles/(?P<slug>[a-zA-Z0-9_-]+)', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_profile'],
            'args' => ['slug' => $slug],
        ]);
        register_rest_route('sabri-public/v1', '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/timeline', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_timeline'],
            'args' => ['slug' => $slug] + $pagination,
        ]);
        register_rest_route('sabri-public/v1', '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/knowledge', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_knowledge'],
            'args' => ['slug' => $slug],
        ]);
        register_rest_route('sabri-public/v1', '/profiles/(?P<slug>[a-zA-Z0-9_-]+)/media', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_media'],
            'args' => ['slug' => $slug],
        ]);
        register_rest_route('sabri-public/v1', '/providers/health', $public + [
            'methods' => 'GET',
            'callback' => [$this, 'get_provider_health'],
        ]);
    }

    public function allow_public_request(WP_REST_Request $request): bool|WP_Error
    {
        $limit = self::DEFAULT_PUBLIC_RATE_LIMIT;
        $window = self::DEFAULT_PUBLIC_RATE_WINDOW;
        if (function_exists('apply_filters')) {
            $limit = (int) apply_filters('sabri_public_experience/rest_rate_limit', $limit, $request);
            $window = (int) apply_filters('sabri_public_experience/rest_rate_window', $window, $request);
        }
        $limit = max(1, min(10000, $limit));
        $window = max(10, min(3600, $window));

        if (! function_exists('wp_cache_get') && ! function_exists('get_transient')) {
            return true;
        }

        $bucket = self::request_rate_bucket($request, $window);
        if ($bucket === '') {
            return true;
        }
        $key = 'sabri_f25_rl_' . $bucket;
        $count = null;

        if (function_exists('wp_cache_add') && function_exists('wp_cache_incr')) {
            if (wp_cache_add($key, 1, 'sabri-file25-rate', $window)) {
                $count = 1;
            } else {
                $incremented = wp_cache_incr($key, 1, 'sabri-file25-rate');
                if (is_int($incremented)) {
                    $count = $incremented;
                }
            }
        }

        if ($count === null && function_exists('get_transient') && function_exists('set_transient')) {
            $stored = get_transient($key);
            $count = is_int($stored) && $stored > 0 ? $stored + 1 : 1;
            set_transient($key, $count, $window);
        }

        if ($count !== null && $count > $limit) {
            return new WP_Error('sabri_public_rate_limited', __('Too many requests. Please try again shortly.', 'sabri-public-experience'), [
                'status' => 429,
                'retry_after' => $window,
            ]);
        }

        return true;
    }

    public function get_founder_profile(?WP_REST_Request $request = null): WP_REST_Response
    {
        return $this->profile_response($this->profiles->get_founder(), $request);
    }

    public function get_founder_timeline(WP_REST_Request $request): WP_REST_Response
    {
        return $this->timeline_response($this->profiles->get_founder(), $request);
    }

    public function get_founder_knowledge(?WP_REST_Request $request = null): WP_REST_Response
    {
        return $this->section_response($this->profiles->get_founder(), 'knowledge', $request);
    }

    public function get_founder_media(?WP_REST_Request $request = null): WP_REST_Response
    {
        return $this->section_response($this->profiles->get_founder(), 'media', $request);
    }

    public function get_profile(WP_REST_Request $request): WP_REST_Response
    {
        return $this->profile_response($this->profiles->find_by_slug((string) $request['slug']), $request);
    }

    public function get_timeline(WP_REST_Request $request): WP_REST_Response
    {
        return $this->timeline_response($this->profiles->find_by_slug((string) $request['slug']), $request);
    }

    public function get_knowledge(WP_REST_Request $request): WP_REST_Response
    {
        return $this->section_response($this->profiles->find_by_slug((string) $request['slug']), 'knowledge', $request);
    }

    public function get_media(WP_REST_Request $request): WP_REST_Response
    {
        return $this->section_response($this->profiles->find_by_slug((string) $request['slug']), 'media', $request);
    }

    public function get_provider_health(?WP_REST_Request $request = null): WP_REST_Response
    {
        return $this->response($this->sections->public_health(), 200, $request);
    }

    private function profile_response(?WP_User $user, ?WP_REST_Request $request = null): WP_REST_Response
    {
        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if ($profile === null) {
            return $this->response(['code' => 'profile_not_found'], 404, $request);
        }

        return $this->response($profile, 200, $request);
    }

    private function timeline_response(?WP_User $user, WP_REST_Request $request): WP_REST_Response
    {
        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if (! $user instanceof WP_User || $profile === null) {
            return $this->response(['code' => 'profile_not_found'], 404, $request);
        }

        $result = $this->timeline->get_for_author((int) $user->ID, [
            'page' => max(1, (int) $request['page']),
            'per_page' => min(50, max(1, (int) $request['per_page'])),
            'content_type' => (string) $request['content_type'],
        ]);
        $public_result = [
            'items' => $result['items'],
            'page' => $result['page'],
            'per_page' => $result['per_page'],
            'has_more' => $result['has_more'],
            'truncated' => $result['truncated'],
            'partial' => $result['provider_errors'] !== [],
        ];

        return $this->response($public_result, 200, $request);
    }

    private function section_response(?WP_User $user, string $section, ?WP_REST_Request $request = null): WP_REST_Response
    {
        $section = sanitize_key($section);
        if (! in_array($section, ['knowledge', 'media'], true)) {
            return $this->response(['code' => 'section_not_found'], 404, $request);
        }

        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if (! $user instanceof WP_User || $profile === null) {
            return $this->response(['code' => 'profile_not_found'], 404, $request);
        }

        $result = $this->sections->get_public_section((int) $user->ID, $profile, $section);

        return $this->response([
            'contract_version' => $result['contract_version'],
            'section' => $result['section'],
            'label' => $result['label'],
            'items' => $result['items'],
            'truncated' => $result['truncated'],
            'partial' => $result['provider_error_count'] > 0,
        ], 200, $request);
    }

    /** @param array<string,mixed> $data */
    private function response(array $data, int $status, ?WP_REST_Request $request = null): WP_REST_Response
    {
        $entries = 0;
        $cacheable = true;
        $canonical = self::canonicalize_for_etag($data, 0, $entries, $cacheable);
        $encoded = $cacheable
            ? (function_exists('wp_json_encode') ? wp_json_encode($canonical) : json_encode($canonical))
            : false;
        $etag = is_string($encoded) ? '"' . hash('sha256', $encoded) . '"' : '';
        $last_modified = self::latest_last_modified($data);
        $headers = [
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($etag !== '') {
            $headers['ETag'] = $etag;
        }
        if ($last_modified !== '') {
            $headers['Last-Modified'] = $last_modified;
        }
        if ($status === 200 && $etag !== '' && self::request_matches_etag($request, $etag)) {
            return new WP_REST_Response(null, 304, $headers);
        }
        if ($status === 200 && $last_modified !== '' && ! self::request_has_if_none_match($request)
            && self::request_matches_last_modified($request, $last_modified)
        ) {
            return new WP_REST_Response(null, 304, $headers);
        }

        return new WP_REST_Response($data, $status, $headers);
    }

    private static function request_rate_bucket(WP_REST_Request $request, int $window): string
    {
        $route = method_exists($request, 'get_route') ? (string) $request->get_route() : 'public';
        $identity = '';
        if (function_exists('get_current_user_id')) {
            $user_id = (int) get_current_user_id();
            if ($user_id > 0) {
                $identity = 'u:' . $user_id;
            }
        }
        if ($identity === '') {
            $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
            $identity = filter_var($remote, FILTER_VALIDATE_IP) !== false ? 'ip:' . $remote : 'anonymous';
        }
        $salt = function_exists('wp_salt') ? (string) wp_salt('auth') : 'sabri-file25-rate-limit';
        $slot = intdiv(time(), max(1, $window));

        return substr(hash_hmac('sha256', $route . '|' . $identity . '|' . $slot, $salt), 0, 40);
    }

    private static function request_matches_etag(?WP_REST_Request $request, string $etag): bool
    {
        if (! $request instanceof WP_REST_Request || ! method_exists($request, 'get_header')) {
            return false;
        }

        $header = trim((string) $request->get_header('if-none-match'));
        if ($header === ''
            || strlen($header) > 4096
            || preg_match('/[\x00-\x1F\x7F]/', $header) === 1
        ) {
            return false;
        }
        $candidates = explode(',', $header);
        if (count($candidates) > 32) {
            return false;
        }
        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate === '*') {
                return true;
            }
            if (str_starts_with($candidate, 'W/')) {
                $candidate = trim(substr($candidate, 2));
            }
            if ($candidate !== '' && hash_equals($etag, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private static function request_has_if_none_match(?WP_REST_Request $request): bool
    {
        return $request instanceof WP_REST_Request
            && method_exists($request, 'get_header')
            && trim((string) $request->get_header('if-none-match')) !== '';
    }

    private static function request_matches_last_modified(?WP_REST_Request $request, string $last_modified): bool
    {
        if (! $request instanceof WP_REST_Request || ! method_exists($request, 'get_header')) {
            return false;
        }
        $header = trim((string) $request->get_header('if-modified-since'));
        if ($header === '' || strlen($header) > 128 || preg_match('/[\x00-\x1F\x7F]/', $header) === 1) {
            return false;
        }
        $requested = strtotime($header);
        $current = strtotime($last_modified);

        return is_int($requested) && is_int($current) && $requested >= $current;
    }

    /** @param array<string,mixed> $data */
    private static function latest_last_modified(array $data): string
    {
        $latest = 0;
        $entries = 0;
        self::collect_latest_timestamp($data, $latest, $entries, 0);

        return $latest > 0 ? gmdate('D, d M Y H:i:s \G\M\T', $latest) : '';
    }

    private static function collect_latest_timestamp(mixed $value, int &$latest, int &$entries, int $depth): void
    {
        if ($depth > 12 || $entries >= 4096 || ! is_array($value)) {
            return;
        }
        foreach ($value as $key => $item) {
            if (++$entries >= 4096) {
                return;
            }
            if (is_array($item)) {
                self::collect_latest_timestamp($item, $latest, $entries, $depth + 1);
                continue;
            }
            if (! is_string($key) || ! is_string($item) || trim($item) === '') {
                continue;
            }
            if (! in_array(strtolower($key), ['updated_at','published_at','date_gmt','modified_gmt','created_at','updated_gmt'], true)) {
                continue;
            }
            $timestamp = strtotime($item);
            if (is_int($timestamp) && $timestamp > $latest) {
                $latest = $timestamp;
            }
        }
    }

    public static function sanitize_exact_positive_integer(mixed $value): int|string
    {
        return self::exact_positive_integer($value, PHP_INT_MAX) ?? '';
    }

    private static function exact_positive_integer(mixed $value, int $maximum): ?int
    {
        if (is_int($value)) {
            return $value >= 1 && $value <= $maximum ? $value : null;
        }
        if (! is_string($value) || strlen($value) > 19 || preg_match('/^[1-9][0-9]*$/', $value) !== 1) {
            return null;
        }
        $integer = (int) $value;

        return (string) $integer === $value && $integer <= $maximum ? $integer : null;
    }

    private static function exact_optional_key(mixed $value): ?string
    {
        if (! is_string($value) || strlen($value) > 64) {
            return null;
        }
        if ($value === '') {
            return '';
        }

        return preg_match('/^[a-z0-9][a-z0-9_-]{0,63}$/', $value) === 1 ? $value : null;
    }

    private static function canonicalize_for_etag(
        mixed $value,
        int $depth = 0,
        int &$entries = 0,
        bool &$cacheable = true
    ): mixed {
        if ($depth > 12 || $entries >= 4096) {
            $cacheable = false;
            return null;
        }
        $entries++;
        if (! is_array($value)) {
            if (is_object($value) || is_resource($value)) {
                $cacheable = false;
                return null;
            }
            return $value;
        }

        $list = $value === [] || array_keys($value) === range(0, count($value) - 1);
        $normalized = [];
        foreach ($value as $key => $item) {
            if ($entries >= 4096) {
                $cacheable = false;
                break;
            }
            $normalized[$key] = self::canonicalize_for_etag($item, $depth + 1, $entries, $cacheable);
            if (! $cacheable) {
                break;
            }
        }
        if (! $list) {
            ksort($normalized, SORT_STRING);
        }

        return $normalized;
    }
}