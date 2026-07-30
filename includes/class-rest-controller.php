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
        register_rest_route('sabri-public/v1', '/profiles/(?P<slug>[^/]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'get_profile'],
            'permission_callback' => '__return_true',
            'args' => [
                'slug' => [
                    'required' => true,
                    'sanitize_callback' => 'sanitize_title',
                ],
            ],
        ]);

        register_rest_route('sabri-public/v1', '/profiles/(?P<slug>[^/]+)/timeline', [
            'methods' => 'GET',
            'callback' => [$this, 'get_timeline'],
            'permission_callback' => '__return_true',
            'args' => [
                'slug' => ['required' => true, 'sanitize_callback' => 'sanitize_title'],
                'page' => ['default' => 1, 'sanitize_callback' => 'absint'],
                'per_page' => ['default' => 20, 'sanitize_callback' => 'absint'],
                'content_type' => ['default' => '', 'sanitize_callback' => 'sanitize_key'],
            ],
        ]);
    }

    public function get_profile(WP_REST_Request $request): WP_REST_Response
    {
        $user = $this->profiles->find_by_slug((string) $request['slug']);
        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if ($profile === null) {
            return new WP_REST_Response(['code' => 'profile_not_found'], 404);
        }
        return new WP_REST_Response($profile, 200, ['Cache-Control' => 'public, max-age=60']);
    }

    public function get_timeline(WP_REST_Request $request): WP_REST_Response
    {
        $user = $this->profiles->find_by_slug((string) $request['slug']);
        $profile = $user instanceof WP_User ? $this->profiles->get_public_profile($user) : null;
        if (! $user instanceof WP_User || $profile === null) {
            return new WP_REST_Response(['code' => 'profile_not_found'], 404);
        }

        $result = $this->timeline->get_for_author((int) $user->ID, [
            'page' => min(10000, max(1, (int) $request['page'])),
            'per_page' => min(50, max(1, (int) $request['per_page'])),
            'content_type' => (string) $request['content_type'],
        ]);
        return new WP_REST_Response($result, 200, ['Cache-Control' => 'public, max-age=30']);
    }
}
