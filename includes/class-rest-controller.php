<?php

declare(strict_types=1);

namespace Sabri\PublicExperience;

use WP_REST_Request;
use WP_REST_Response;
use WP_User;

if (! defined('ABSPATH')) {
    exit;
}

final class Rest_Controller
{
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
        $public = ['permission_callback' => '__return_true'];
        $slug = [
            'required' => true,
            // Preserve the exact route identity. Validation may reject aliases,
            // but sanitization must never transform one public account slug into
            // another valid account before Profile_Repository checks equality.
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
                'sanitize_callback' => 'absint',
                'validate_callback' => static fn ($value): bool => is_numeric($value) && (int) $value >= 1,
            ],
            'per_page' => [
                'default' => 20,
                'sanitize_callback' => 'absint',
                'validate_callback' => static fn ($value): bool => is_numeric($value) && (int) $value >= 1 && (int) $value <= 50,
            ],
            'content_type' => [
                'default' => '',
                'sanitize_callback' => 'sanitize_key',
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
        return $this->timeline_response(
            $this->profiles->find_by_slug((string) $request['slug']),
            $request
        );
    }

    public function get_knowledge(WP_REST_Request $request): WP_REST_Response
    {
        return $this->section_response(
            $this->profiles->find_by_slug((string) $request['slug']),
            'knowledge',
            $request
        );
    }

    public function get_media(WP_REST_Request $request): WP_REST_Response
    {
        return $this->section_response(
            $this->profiles->find_by_slug((string) $request['slug']),
            'media',
            $request
        );
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

        // Provider identifiers and exception details remain server-side diagnostics.
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
        $canonical = self::canonicalize_for_etag($data);
        $encoded = function_exists('wp_json_encode')
            ? wp_json_encode($canonical)
            : json_encode($canonical);
        $etag = is_string($encoded) ? '"' . hash('sha256', $encoded) . '"' : '';
        $headers = [
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];
        if ($etag !== '') {
            $headers['ETag'] = $etag;
        }
        if ($status === 200 && $etag !== '' && self::request_matches_etag($request, $etag)) {
            return new WP_REST_Response(null, 304, $headers);
        }

        return new WP_REST_Response($data, $status, $headers);
    }

    private static function request_matches_etag(?WP_REST_Request $request, string $etag): bool
    {
        if (! $request instanceof WP_REST_Request || ! method_exists($request, 'get_header')) {
            return false;
        }

        $header = trim((string) $request->get_header('if-none-match'));
        if ($header === '') {
            return false;
        }
        foreach (explode(',', $header) as $candidate) {
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

    private static function canonicalize_for_etag(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }
        if ($value === [] || array_keys($value) === range(0, count($value) - 1)) {
            return array_map([self::class, 'canonicalize_for_etag'], $value);
        }

        ksort($value, SORT_STRING);
        foreach ($value as $key => $item) {
            $value[$key] = self::canonicalize_for_etag($item);
        }

        return $value;
    }
}
