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
        private Timeline_Service $timeline
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
            'sanitize_callback' => 'sanitize_title',
            'validate_callback' => static fn ($value): bool => is_string($value)
                && $value !== ''
                && ! ctype_digit($value)
                && strlen($value) <= 200,
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
    }

    public function get_founder_profile(): WP_REST_Response
    {
        return $this->profile_response($this->profiles->get_founder());
    }

    public function get_founder_timeline(WP_REST_Request $request): WP_REST_Response
    {
        return $this->timeline_response($this->profiles->get_founder(), $request);
    }

    public function get_profile(WP_REST_Request $request): WP_REST_Response
    {
        return $this->profile_response($this->profiles->find_by_slug((string) $request['slug']));
    }

    public function get_timeline(WP_REST_Request $request): WP_REST_Response
    {
        return $this->timeline_response(
            $this->profiles->find_by_slug((string) $request['slug']),
            $request
        );
    }

    private function profile_response(?WP_User $user): WP_REST_Response
    {
        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if ($profile === null) {
            return $this->response(['code' => 'profile_not_found'], 404);
        }

        return $this->response($profile, 200);
    }

    private function timeline_response(?WP_User $user, WP_REST_Request $request): WP_REST_Response
    {
        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if (! $user instanceof WP_User || $profile === null) {
            return $this->response(['code' => 'profile_not_found'], 404);
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
        return $this->response($public_result, 200);
    }

    /** @param array<string,mixed> $data */
    private function response(array $data, int $status): WP_REST_Response
    {
        return new WP_REST_Response($data, $status, [
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
